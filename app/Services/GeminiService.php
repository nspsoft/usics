<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;
use App\Models\AppSetting;

class GeminiService
{
    protected bool $isConfigured = false;
    protected ?string $driver = null;
    protected ?string $apiKey = null;
    protected ?string $model = null;
    protected ?string $baseUrl = null;
    protected ?string $ollamaUrl = null;
    protected ?string $ollamaModel = null;
    protected ?string $openrouterApiKey = null;
    protected ?string $openrouterModel = null;
    protected ?string $customBotInstruction = null;

    public function __construct()
    {
        // Defer configuration to ensureConfigured()
    }

    protected function ensureConfigured()
    {
        if ($this->isConfigured) return;

        try {
            $company = \App\Models\Company::first();
            $aiSettings = $company?->settings['ai'] ?? [];

            $this->driver = $aiSettings['ai_driver'] ?? 'gemini';
            
            // Gemini Config
            $this->apiKey = $aiSettings['gemini_api_key'] ?? config('services.gemini.key');
            $this->model = $aiSettings['gemini_model'] ?? 'gemini-1.5-flash';
            $this->baseUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent";

            // Ollama Config
            $this->ollamaUrl = $aiSettings['ollama_url'] ?? 'http://localhost:11434';
            $this->ollamaModel = $aiSettings['ollama_model'] ?? 'llama3';

            // OpenRouter Config
            $this->openrouterApiKey = $aiSettings['openrouter_api_key'] ?? null;
            $this->openrouterModel = $aiSettings['openrouter_model'] ?? 'google/gemini-2.5-flash';

            $this->customBotInstruction = (string) AppSetting::get('whatsapp_bot_instruction', '');
            $this->isConfigured = true;
        } catch (\Exception $e) {
            Log::error('GeminiService configuration failed: ' . $e->getMessage());
        }
    }

    /**
     * Call OpenRouter API
     */
    protected function callOpenRouter(string $prompt, ?string $filePath = null, ?string $mimeType = null, bool $jsonMode = false): ?array
    {
        $this->ensureConfigured();
        if (!$this->openrouterApiKey) {
            Log::error('OpenRouter API Key is not configured.');
            return null;
        }

        if ($filePath) {
            try {
                $base64Data = base64_encode(file_get_contents($filePath));
                $messageContent = [
                    [
                        'type' => 'text',
                        'text' => $prompt
                    ]
                ];

                if ($mimeType === 'application/pdf') {
                    $messageContent[] = [
                        'type' => 'file',
                        'file' => [
                            'filename' => basename($filePath) ?: 'document.pdf',
                            'file_data' => "data:application/pdf;base64,{$base64Data}"
                        ]
                    ];
                } elseif (str_starts_with($mimeType, 'image/')) {
                    $messageContent[] = [
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => "data:{$mimeType};base64,{$base64Data}"
                        ]
                    ];
                } else {
                    $messageContent = $prompt;
                }
            } catch (\Exception $e) {
                Log::error('OpenRouter file base64 encoding failed: ' . $e->getMessage());
                $messageContent = $prompt;
            }
        } else {
            $messageContent = $prompt;
        }

        $payload = [
            'model' => $this->openrouterModel,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $messageContent
                ]
            ],
        ];

        if ($jsonMode) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        try {
            $response = Http::timeout(120)
                ->withHeaders([
                    'Authorization' => "Bearer {$this->openrouterApiKey}",
                    'HTTP-Referer' => url('/'),
                    'X-Title' => 'USICS ERP',
                ])
                ->post('https://openrouter.ai/api/v1/chat/completions', $payload);

            if ($response->successful()) {
                $result = $response->json();
                $text = $result['choices'][0]['message']['content'] ?? null;

                Log::info('OpenRouter Raw Response: ' . substr($text ?? 'NULL', 0, 500));

                if ($text) {
                    if ($jsonMode) {
                        // Extract JSON if there is leading/trailing text
                        if (preg_match('/\{.*\}/s', $text, $matches)) {
                            return json_decode($matches[0], true);
                        }
                        return json_decode(trim($text), true);
                    }
                    return ['text' => $text];
                }
            } else {
                Log::error('OpenRouter API Error: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Exception in GeminiService (OpenRouter): ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Extract PO data from an image or PDF file.
     *
     * @param string $filePath Absolute path to the file
     * @param string $mimeType Mime type of the file
     * @return array|null Extracted data as an associative array
     */
    public function extractPOData(string $filePath, string $mimeType): ?array
    {
        $this->ensureConfigured();
        $data = null;
        if ($this->driver === 'ollama') {
            $data = $this->extractPODataOllama($filePath, $mimeType);
        } elseif ($this->driver === 'openrouter') {
            $data = $this->callOpenRouter($this->getPOExtractionPrompt(), $filePath, $mimeType, true);
        } else {
            $data = $this->extractPODataGemini($filePath, $mimeType);
        }

        if ($data) {
            // Handle array response (if Gemini returns a list of POs, take the first one)
            if (array_key_exists(0, $data) && is_array($data[0])) {
                $data = $data[0];
            }

            // Determine currency
            $companyCurrency = \App\Models\Company::first()?->currency ?? 'IDR';
            $currency = $data['currency'] ?? $companyCurrency;

            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as &$item) {
                    if (isset($item['unit_price'])) {
                        $item['unit_price'] = $this->cleanAndParsePrice($item['unit_price'], $currency);
                    }
                    if (isset($item['total_price'])) {
                        $item['total_price'] = $this->cleanAndParsePrice($item['total_price'], $currency);
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Clean and parse extracted price to a proper float based on currency rules.
     */
    public function cleanAndParsePrice(mixed $price, string $currency = 'IDR'): float
    {
        if (is_null($price)) {
            return 0.0;
        }

        $isIdr = strtoupper(trim($currency)) === 'IDR';

        // If it's a string, clean and parse based on separators
        if (is_string($price)) {
            $price = trim($price);
            
            // Remove non-numeric/separator characters
            $price = preg_replace('/[^\d.,]/', '', $price);
            
            if ($price === '') {
                return 0.0;
            }

            // If it contains both dot and comma
            if (strpos($price, '.') !== false && strpos($price, ',') !== false) {
                $lastDot = strrpos($price, '.');
                $lastComma = strrpos($price, ',');
                if ($lastDot > $lastComma) {
                    // Dot is decimal, comma is thousands (e.g. 1,234,567.89)
                    $price = str_replace(',', '', $price);
                } else {
                    // Comma is decimal, dot is thousands (e.g. 1.234.567,89)
                    $price = str_replace('.', '', $price);
                    $price = str_replace(',', '.', $price);
                }
                
                $val = floatval($price);
                if ($isIdr) {
                    // In IDR, decimals are not used for transactions, so if it has decimal cents, round it
                    return round($val);
                }
                return $val;
            }

            // If it contains only a dot
            if (strpos($price, '.') !== false) {
                $parts = explode('.', $price);
                $lastPart = end($parts);
                if (strlen($lastPart) === 3) {
                    // Dot followed by 3 digits is thousands separator (e.g. 137.170 -> 137170)
                    return floatval(str_replace('.', '', $price));
                }
                
                $val = floatval($price);
                if ($isIdr && $val > 0 && $val < 1000) {
                    // Float value like 137.17 (truncated from 137.170) or 1.5 (truncated from 1.500)
                    if (floor($val) != $val || $val < 90) {
                        return $val * 1000;
                    }
                }
                return $val;
            }

            // If it contains only a comma
            if (strpos($price, ',') !== false) {
                $parts = explode(',', $price);
                $lastPart = end($parts);
                if (strlen($lastPart) === 3) {
                    // Comma followed by 3 digits is thousands separator (e.g. 137,170 -> 137170)
                    return floatval(str_replace(',', '', $price));
                }
                
                // Comma as decimal separator in Indonesian (e.g. 137,17 -> 137.17)
                $val = floatval(str_replace(',', '.', $price));
                if ($isIdr && $val > 0 && $val < 1000) {
                    if (floor($val) != $val || $val < 90) {
                        return $val * 1000;
                    }
                }
                return $val;
            }

            return floatval($price);
        }

        // If it's already float/int
        $val = floatval($price);
        if ($isIdr && $val > 0 && $val < 1000) {
            // Correct decimal values or values below 90 IDR (e.g., 1.5 -> 1500, 1.0 -> 1000, 137.17 -> 137170)
            if (floor($val) != $val || $val < 90) {
                return $val * 1000;
            }
        }

        return $val;
    }

    /**
     * Gemini Implementation
     */
    protected function extractPODataGemini(string $filePath, string $mimeType): ?array
    {
        if (!$this->apiKey) {
            Log::error('Gemini API Key is not configured.');
            return null;
        }

        $fileData = base64_encode(file_get_contents($filePath));
        $prompt = $this->getPOExtractionPrompt();

        try {
            $response = Http::timeout(120)->post("{$this->baseUrl}?key={$this->apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                            [
                                'inline_data' => [
                                    'mime_type' => $mimeType,
                                    'data' => $fileData
                                ]
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'response_mime_type' => 'application/json',
                ]
            ]);

            return $this->parseResponse($response);
        } catch (\Exception $e) {
            Log::error('Exception in GeminiService (Gemini): ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Ollama Implementation
     */
    protected function extractPODataOllama(string $filePath, string $mimeType): ?array
    {
        $textContent = '';

        // Extract text based on file type
        try {
            if ($mimeType === 'application/pdf') {
                $parser = new Parser();
                $pdf = $parser->parseFile($filePath);
                $textContent = $pdf->getText();
            } else {
                // For images, assuming text-only models for now (or reliance on filename/context).
                // Ideally, we'd use a vision model here if configured.
                // For now, return null or try to extract from filename/headers if possible?
                // Let's just return null and log for now as "Text extraction from image not supported for local LLM yet".
                Log::warning('Ollama image extraction requires vision model support. Sending empty context.');
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Failed to parse PDF text: ' . $e->getMessage());
            return null;
        }

        $prompt = $this->getPOExtractionPrompt() . "\n\nDOCUMENT CONTENT:\n" . substr($textContent, 0, 15000); // Limit context to avoid context window issues

        return $this->callOllama($prompt, true);
    }

    protected function callOllama(string $prompt, bool $jsonMode = false): ?array
    {
        try {
            $payload = [
                'model' => $this->ollamaModel,
                'prompt' => $prompt,
                'stream' => false,
            ];

            if ($jsonMode) {
                $payload['format'] = 'json';
            }

            // Clean up URL (remove trailing slash)
            $url = rtrim($this->ollamaUrl, '/');
            $response = Http::timeout(120)->post("{$url}/api/generate", $payload);

            if ($response->successful()) {
                $result = $response->json();
                $text = $result['response'] ?? null;
                
                Log::info('Ollama Raw Response: ' . substr($text ?? 'NULL', 0, 500));

                if ($text && $jsonMode) {
                    $decoded = json_decode($text, true);
                    return $decoded;
                }
                
                return $jsonMode ? null : ['text' => $text]; // Standardization-ish
            } else {
                Log::error('Ollama API Error: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Exception in GeminiService (Ollama): ' . $e->getMessage());
        }

        return null;
    }

    protected function parseResponse($response)
    {
        if ($response->successful()) {
            try {
                $result = $response->json();
                $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;
                
                Log::info('Gemini API Raw Response: ' . substr($text ?? 'NULL', 0, 500));
                
                if ($text) {
                    // Extract JSON if there is leading/trailing text
                    if (preg_match('/\{.*\}/s', $text, $matches)) {
                        $json = $matches[0];
                        return json_decode($json, true);
                    }
                    
                    // Fallback to previous cleaning logic
                    $text = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($text));
                    return json_decode($text, true);
                }
            } catch (\Throwable $e) {
                Log::error('Gemini Parse Error: ' . $e->getMessage());
            }
        } else {
            Log::error('Gemini API Error: ' . $response->body());
        }
        return null;
    }

    protected function getPOExtractionPrompt(): string
    {
        return "You are an expert document reader. Analyze this Purchase Order (PO) text content carefully.
        
        Look for:
        - The PO/Order number (labeled as 'PO No', 'No. PO', 'Order No', 'Nomor PO', etc.)
        - The customer/buyer company name (the company SENDING the order). Note: In extracted text, usually the header contains the sender info.
        - Date of the PO
        - All line items with quantities and prices
        
        CRITICAL - IDENTIFYING THE CUSTOMER:
        - The CUSTOMER/BUYER is typically the entity named in the header or 'From:'.
        - The 'To:' or 'Vendor:' field usually indicates PT. SPINDO (us).
        
        CRITICAL - EXTRACTING ITEMS:
        - Many POs have SEPARATE columns for 'Material Number' / 'Part Number' / 'Item Code' / 'Material code' AND 'Material Name' / 'Description' / 'Product Name'.
        - You MUST extract them into SEPARATE fields: 'material_number' and 'description'.
        - 'material_number' should contain ONLY the code/part number (e.g. 'Y9DBZ00004088', 'DXC49A', '500-12').
        - 'description' should contain ONLY the human-readable product name/description (e.g. 'Cardboard pad SNP', 'Bracket Knob Joint').
        - Do NOT merge or concatenate material_number into description. Keep them strictly separated.
        - If the document has no separate material number column, set material_number to null.
        
        CRITICAL - INDONESIAN NUMBER FORMATTING (Thousands vs Decimals):
        - Indonesian documents often use dot (.) as a thousands separator and comma (,) as a decimal separator (e.g., 137.170 means 137170, and 548.680 means 548680).
        - To prevent losing trailing zeros or separator formatting, you MUST extract both \"unit_price\" and \"total_price\" as STRINGS containing the exact characters, dots, and commas as they appear in the original document (e.g., \"137.170\", \"1.500.000\", \"548.680\", \"12,50\", or \"90\").
        
        Extract and return ONLY a valid JSON object with this exact structure:
        {
            \"po_number\": \"the PO number or null\",
            \"po_date\": \"YYYY-MM-DD format or null\",
            \"delivery_date\": \"YYYY-MM-DD format or null\",
            \"customer_name\": \"name of the BUYER company\",
            \"customer_address\": \"address if visible or null\",
            \"currency\": \"currency detected (e.g. 'IDR', 'USD'), default to 'IDR'\",
            \"items\": [
                {
                    \"material_number\": \"part/material code or null\",
                    \"description\": \"product name ONLY, not the material number\",
                    \"qty\": 100,
                    \"unit\": \"Pcs\",
                    \"unit_price\": \"15.000\",
                    \"total_price\": \"1.500.000\"
                }
            ]
        }
        
        Return pure JSON without any markdown formatting or backticks.";
    }

    /**
     * Extract Delivery Note (Surat Jalan) data from an image or PDF.
     */
    public function extractDeliveryNoteData(string $filePath, string $mimeType): ?array
    {
        $this->ensureConfigured();
        if ($this->driver === 'ollama') {
             if ($mimeType === 'application/pdf') {
                try {
                    $parser = new Parser();
                    $pdf = $parser->parseFile($filePath);
                    $textContent = $pdf->getText();
                    $prompt = $this->getDNExtractionPrompt() . "\n\nDOCUMENT CONTENT:\n" . substr($textContent, 0, 15000);
                    return $this->callOllama($prompt, true);
                } catch (\Exception $e) {
                    Log::error('Failed to parse DN PDF: ' . $e->getMessage());
                }
             }
             return null;
        }
        if ($this->driver === 'openrouter') {
            return $this->callOpenRouter($this->getDNExtractionPrompt(), $filePath, $mimeType, true);
        }

        return $this->extractDeliveryNoteDataGemini($filePath, $mimeType);
    }

    protected function extractDeliveryNoteDataGemini(string $filePath, string $mimeType): ?array
    {
        if (!$this->apiKey) return null;

        $fileData = base64_encode(file_get_contents($filePath));
        $prompt = $this->getDNExtractionPrompt();

        try {
            $response = Http::timeout(120)->post("{$this->baseUrl}?key={$this->apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                            ['inline_data' => ['mime_type' => $mimeType, 'data' => $fileData]]
                        ]
                    ]
                ],
                'generationConfig' => ['response_mime_type' => 'application/json']
            ]);
            return $this->parseResponse($response);
        } catch (\Exception $e) {
            Log::error('Exception in GeminiService (DN Gemini): ' . $e->getMessage());
        }
        return null;
    }

    protected function getDNExtractionPrompt(): string
    {
        return "You are an expert document reader. Analyze this Delivery Note (Surat Jalan/DO) text content carefully.
        
        Look for:
        - The SUPPLIER/SENDER name (usually in header).
        - The PO Number reference.
        - The Delivery Note Number.
        - Date.
        - All line items.
        
        Extract and return ONLY a valid JSON object with this exact structure:
        {
            \"supplier_name\": \"name of the SUPPLIER\",
            \"dn_number\": \"Delivery Note / Surat Jalan Number\",
            \"po_number\": \"The referenced PO Number if visible, or null\",
            \"date\": \"YYYY-MM-DD format or null\",
            \"items\": [
                {
                    \"description\": \"product name/description\",
                    \"qty\": 100,
                    \"unit\": \"Pcs\",
                    \"remarks\": \"any notes if available\"
                }
            ]
        }
        
        Return pure JSON without any markdown formatting or backticks.";
    }

    /**
     * Analyze customer intent (Gemini/Ollama)
     */
    public function analyzeCustomerIntent(string $message, ?array $customerContext = null, array $conversationHistory = []): array
    {
        $this->ensureConfigured();
        $contextInfo = $customerContext ? "Customer Name: {$customerContext['name']}" : "Unknown customer";
        
        $systemInstruction = $this->customBotInstruction 
            ? "Personality & Context: {$this->customBotInstruction}"
            : "You are a friendly and professional customer service assistant for PT JIDOKA.";

        // Build conversation history context
        $historyText = '';
        if (!empty($conversationHistory)) {
            $historyText = "\n\nRecent conversation history (for context):\n";
            foreach ($conversationHistory as $msg) {
                $role = $msg['role'] === 'customer' ? 'Customer' : 'Bot';
                $historyText .= "{$role}: {$msg['message']}\n";
            }
        }

        $prompt = "{$systemInstruction}
Analyze this message and classify the intent precisely.

Context: {$contextInfo}{$historyText}
New Message: \"{$message}\"

Intents:
- greeting: Sapaan awal (Halo, Selamat siang, Pagi, etc.)
- casual_chat: Percakapan santai non-teknis (tanya kabar, terima kasih, ok, siap, etc.)
- order_status: Tanya status pesanan/pengiriman (status SO, nomor DO, dll)
- invoice_check: Tanya tagihan/piutang/invoice
- product_catalog: Tanya katalog produk/harga/pipa
- request_quotation: Minta penawaran harga
- faq: Pertanyaan umum tentang perusahaan
- unknown: Tidak bisa diklasifikasikan

IMPORTANT: Use the conversation history to understand follow-up messages. If the customer says something like 'kalau yang 6 inch?' after asking about pipe prices, classify it as 'product_catalog' and extract the product name from context.

Return JSON strictly: { \"intent\": \"...\", \"parameters\": { \"order_number\": \"...\", \"product_name\": \"...\" }, \"confidence\": 0.9 }";

        if ($this->driver === 'ollama') {
            $result = $this->callOllama($prompt, true);
            return $result ?? ['intent' => 'unknown', 'parameters' => []];
        }
        if ($this->driver === 'openrouter') {
            $result = $this->callOpenRouter($prompt, null, null, true);
            return $result ?? ['intent' => 'unknown', 'parameters' => []];
        }

        // Gemini Logic
        try {
            $response = Http::timeout(30)->post("{$this->baseUrl}?key={$this->apiKey}", [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => ['response_mime_type' => 'application/json'],
            ]);
            $result = $this->parseResponse($response);
            return $result ?? ['intent' => 'unknown', 'parameters' => []];
        } catch (\Exception $e) {
            Log::error('Intent Error: ' . $e->getMessage());
        }
        return ['intent' => 'unknown', 'parameters' => []];
    }

    /**
     * FAQ Response
     */
    public function generateFAQResponse(string $question, array $conversationHistory = []): string
    {
        $this->ensureConfigured();
        $systemInstruction = $this->customBotInstruction 
            ? "Personality & Context: {$this->customBotInstruction}"
            : "You are a helpful customer service assistant for PT JIDOKA.";

        // Build conversation history context
        $historyText = '';
        if (!empty($conversationHistory)) {
            $historyText = "\n\nRecent conversation for context:\n";
            foreach ($conversationHistory as $msg) {
                $role = $msg['role'] === 'customer' ? 'Customer' : 'Bot';
                $historyText .= "{$role}: {$msg['message']}\n";
            }
        }

        $prompt = "{$systemInstruction}
Answer this customer question in Indonesian, friendly and concise (max 200 chars).{$historyText}
Question: \"{$question}\"";

        Log::info("Gemini System Instruction Used: " . $systemInstruction);

        if ($this->driver === 'ollama') {
            try {
                $payload = ['model' => $this->ollamaModel, 'prompt' => $prompt, 'stream' => false];
                $url = rtrim($this->ollamaUrl, '/');
                $res = Http::timeout(30)->post("{$url}/api/generate", $payload);
                if ($res->successful()) return $res->json()['response'] ?? "Maaf, error.";
            } catch (\Exception $e) {}
            return "Maaf, layanan sedang sibuk.";
        }
        if ($this->driver === 'openrouter') {
            $res = $this->callOpenRouter($prompt, null, null, false);
            return $res['text'] ?? "Maaf, asisten virtual sedang sibuk.";
        }

        // Gemini
        try {
            $response = Http::timeout(30)->post("{$this->baseUrl}?key={$this->apiKey}", [
                'contents' => [['parts' => [['text' => $prompt]]]],
            ]);
            if ($response->successful()) {
                return $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? "Maaf, saya tidak mengerti.";
            }
            
            Log::error("Gemini API Error ({$response->status()}): " . substr($response->body(), 0, 500));
        } catch (\Exception $e) {
            Log::error("Gemini Critical Error: " . $e->getMessage());
        }

        return "Maaf, saat ini asisten virtual sedang sibuk/penuh. Silakan hubungi Customer Service kami secara langsung atau coba beberapa saat lagi.";
    }

    /**
     * Analyze email content for intent, sentiment, and urgency.
     */
    public function analyzeEmailContent(string $body): array
    {
        $this->ensureConfigured();
        $prompt = "Analyze this incoming email and provide a structured classification.
        
        EMAIL CONTENT:
        \"{$body}\"
        
        Classify the following:
        1. intent: The primary purpose of the email. Choose from: 
           - 'order_status' (checking status of an order)
           - 'request_quotation' (asking for price/quote)
           - 'purchase_order' (sending a formal order)
           - 'complaint' (problem with product/service)
           - 'payment_info' (payment confirmation/invoice inquiry)
           - 'general_inquiry' (asking about company/catalog)
           - 'casual' (greetings, thank you)
           - 'unknown'
        
        2. sentiment: Tone of the email. Choose from: 'positive', 'neutral', 'frustrated', 'urgent'.
        
        3. urgency: A score from 0.0 to 1.0 (1.0 being critical/emergency).
        
        4. summary: A very brief (max 100 characters) summary of the request.
        
        5. suggest_reply: A professional draft response in Indonesian based on typical customer service norms.
        
        Return strictly JSON:
        {
            \"intent\": \"...\",
            \"sentiment\": \"...\",
            \"urgency\": 0.8,
            \"summary\": \"...\",
            \"suggest_reply\": \"...\"
        }";

        if ($this->driver === 'ollama') {
            $result = $this->callOllama($prompt, true);
            return $result ?? ['intent' => 'unknown', 'sentiment' => 'neutral', 'urgency' => 0];
        }
        if ($this->driver === 'openrouter') {
            $result = $this->callOpenRouter($prompt, null, null, true);
            return $result ?? ['intent' => 'unknown', 'sentiment' => 'neutral', 'urgency' => 0];
        }

        try {
            $response = Http::timeout(60)->post("{$this->baseUrl}?key={$this->apiKey}", [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => ['response_mime_type' => 'application/json'],
            ]);
            
            $result = $this->parseResponse($response);
            return $result ?? ['intent' => 'unknown', 'sentiment' => 'neutral', 'urgency' => 0];
        } catch (\Exception $e) {
            Log::error('Email Analysis Error: ' . $e->getMessage());
        }

        return ['intent' => 'unknown', 'sentiment' => 'neutral', 'urgency' => 0];
    }

    /**
     * Analyze forecast accuracy and provide AI-powered recommendations.
     *
     * @param array $forecastData Array of forecast rows with keys: customer, product, period, forecast, actual, accuracy
     * @return string Markdown-formatted analysis text
     */
    public function analyzeForecastAccuracy(array $forecastData): string
    {
        $this->ensureConfigured();
        // Build data table for the prompt
        $dataRows = '';
        foreach ($forecastData as $row) {
            $gap = $row['actual'] - $row['forecast'];
            $gapPct = $row['forecast'] > 0 ? round(($gap / $row['forecast']) * 100, 1) : 0;
            $direction = $gap >= 0 ? 'OVER' : 'UNDER';
            $dataRows .= "| {$row['customer']} | {$row['product']} | {$row['period']} | " .
                         number_format($row['forecast'], 0) . " | " .
                         number_format($row['actual'], 0) . " | " .
                         "{$row['accuracy']}% | {$direction} {$gapPct}% |\n";
        }

        $totalFc = array_sum(array_column($forecastData, 'forecast'));
        $totalAct = array_sum(array_column($forecastData, 'actual'));
        $overallAcc = $totalFc > 0 ? round(($totalAct / $totalFc) * 100, 1) : 0;
        $totalGap = $totalAct - $totalFc;

        $prompt = "You are a senior demand planning analyst at PT SPINDO, a steel pipe manufacturer in Indonesia.
Analyze the following Sales Forecast vs Actual Order data and provide actionable insights.

## Data Summary
- **Overall Forecast Accuracy**: {$overallAcc}%
- **Total Forecast**: " . number_format($totalFc, 0) . "
- **Total Actual Orders**: " . number_format($totalAct, 0) . "
- **Total Gap**: " . number_format($totalGap, 0) . " (" . ($totalGap >= 0 ? 'over' : 'under') . "-forecast)

## Detailed Data
| Customer | Product | Period | Forecast | Actual | Accuracy | Gap |
|----------|---------|--------|----------|--------|----------|-----|
{$dataRows}

## Instructions
Respond in **Bahasa Indonesia** with markdown formatting. Provide a comprehensive analysis with these sections:

### 📊 Ringkasan Akurasi
- Overall performance assessment
- Top performing & worst performing customers/products

### 🔍 Pola yang Teridentifikasi
- Over-forecast vs under-forecast patterns
- Seasonal or customer-specific trends
- Products with consistent deviation

### 🎯 Root Cause Analysis
- Likely reasons for forecast inaccuracies
- External factors that may have contributed

### 💡 Rekomendasi Perbaikan
- Specific, actionable recommendations per customer/product
- Process improvements for better forecasting
- Suggested adjustments for next period

### 📈 Strategi Peningkatan Akurasi
- Short-term quick wins
- Medium-term process changes
- Long-term systemic improvements

Keep the analysis practical, data-driven, and focused on actionable insights. Use bullet points for clarity.";

        if ($this->driver === 'ollama') {
            try {
                $payload = [
                    'model' => $this->ollamaModel,
                    'prompt' => $prompt,
                    'stream' => false,
                ];
                $url = rtrim($this->ollamaUrl, '/');
                $res = Http::timeout(120)->post("{$url}/api/generate", $payload);
                if ($res->successful()) {
                    return $res->json()['response'] ?? 'Maaf, gagal menganalisis data.';
                }
            } catch (\Exception $e) {
                Log::error('Forecast Analysis Ollama Error: ' . $e->getMessage());
            }
            return 'Maaf, layanan AI sedang tidak tersedia. Silakan coba lagi nanti.';
        }
        if ($this->driver === 'openrouter') {
            $res = $this->callOpenRouter($prompt, null, null, false);
            return $res['text'] ?? 'Maaf, gagal menganalisis data.';
        }

        // Gemini
        try {
            $response = Http::timeout(120)->post("{$this->baseUrl}?key={$this->apiKey}", [
                'contents' => [['parts' => [['text' => $prompt]]]],
            ]);
            if ($response->successful()) {
                return $response->json()['candidates'][0]['content']['parts'][0]['text']
                    ?? 'Maaf, gagal menganalisis data.';
            } else {
                Log::error('Forecast Analysis Gemini Error: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Forecast Analysis Exception: ' . $e->getMessage());
        }

        return 'Maaf, layanan AI sedang tidak tersedia. Silakan coba lagi nanti.';
    }

    /**
     * Extract Delivery Schedule Matrix data from an image.
     */
    public function extractDeliveryScheduleMatrix(string $filePath, string $mimeType): ?array
    {
        $this->ensureConfigured();
        if ($this->driver === 'ollama') return null;
        if ($this->driver === 'openrouter') {
            return $this->callOpenRouter($this->getDeliveryScheduleMatrixPrompt(), $filePath, $mimeType, true);
        }
        if (!$this->apiKey) {
            Log::error('Gemini API Key is not configured for Matrix Extraction.');
            return null;
        }

        $fileData = base64_encode(file_get_contents($filePath));
        $prompt = $this->getDeliveryScheduleMatrixPrompt();

        // Use longer timeout for thinking models (gemini-2.5-*)
        $isThinkingModel = str_contains($this->model, '2.5');
        $timeout = $isThinkingModel ? 300 : 120;

        try {
            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                            ['inline_data' => ['mime_type' => $mimeType, 'data' => $fileData]]
                        ]
                    ]
                ],
                'generationConfig' => ['response_mime_type' => 'application/json']
            ];

            // For thinking models, limit thinking budget to speed up response
            if ($isThinkingModel) {
                $payload['generationConfig']['thinkingConfig'] = [
                    'thinkingBudget' => 1024,
                ];
            }

            $response = Http::timeout($timeout)->post("{$this->baseUrl}?key={$this->apiKey}", $payload);

            Log::info('Matrix Extraction Response Status: ' . $response->status());
            return $this->parseResponse($response);
        } catch (\Exception $e) {
            Log::error('Exception in GeminiService (Matrix): ' . $e->getMessage());
        }
        return null;
    }

    protected function getDeliveryScheduleMatrixPrompt(): string
    {
        return "You are an expert at reading complex logistics tables. Analyze this Delivery Schedule Matrix carefully.

        The table columns usually represent dates (e.g. 02-Feb, 03-Feb, etc.).
        The table rows represent Products/Materials (with Code and Supplier).
        
        CRITICAL - MATRIX STRUCTURE:
        - The rows contain a 'Code' column (e.g. 'LHB02', 'CFT01').
        - The columns from left to right represent specific dates in a month (e.g. Feb-26).
        - Cells contain the QUANTITY scheduled for that specific Product on that specific Date.
        
        YOUR TASK:
        Convert this matrix into a flat list of individual schedule entries. 
        Each entry must contain:
        1. product_code: The code found in the 'Code' column.
        2. date: The date from the column header (formatted as YYYY-MM-DD). If the year is not visible, assume 2026.
        3. qty: The quantity value in the cell.
        4. supplier_name: The name from the 'Supplier' column.
        
        Output format:
        Extract and return ONLY a valid JSON object with this exact structure:
        {
            \"month_year\": \"Feb 2026\",
            \"items\": [
                {
                    \"product_code\": \"LHB02\",
                    \"date\": \"2026-02-02\",
                    \"qty\": 1000,
                    \"supplier_name\": \"Jidoka\"
                },
                ...
            ]
        }
        
        IMPORTANT:
        - Skip cells that are empty or contain only a dot (.).
        - Only include entries where quantity > 0.
        - Return pure JSON without any markdown formatting.";
    }

    /**
     * Generate meeting minutes, summary, and action items from raw meeting notes or transcripts.
     */
    public function generateMeetingMinutes(string $rawNotes): ?array
    {
        $this->ensureConfigured();
        
        $usersList = \App\Models\User::select('id', 'name')->get()->map(function($u) {
            return "ID: {$u->id}, Name: {$u->name}";
        })->implode("\n");

        $prompt = "You are an expert executive secretary and AI assistant.
Analyze the following raw meeting notes, transcript, or bullet points, and generate a structured Minutes of Meeting (Notulen Rapat) in **Bahasa Indonesia**.

Here is the list of active users in the system who can be assigned as Chairperson, Secretary, or PIC for Action Items:
{$usersList}

RAW MEETING CONTENT:
\"{$rawNotes}\"

YOUR TASK:
1. Generate a structured title/agenda for the meeting.
2. Structure the 'discussion_notes' into neat paragraphs with agenda headings and clear bullet points. Write this strictly in **Bahasa Indonesia**.
3. Identify all Action Items (Tugas Tindak Lanjut) mentioned in the notes.
4. For each Action Item, extract:
   - 'description': What needs to be done.
   - 'pic_id': The ID of the user assigned to this task (match from the user list above. If a name matches closely, use their ID. If no match is found, assign a default or leave it empty).
   - 'due_date': The deadline for the task (in YYYY-MM-DD format. If no date is mentioned, estimate a reasonable deadline, e.g. 7 days from today. Assume today is " . now()->toDateString() . ").

Output format:
Extract and return ONLY a valid JSON object with this exact structure:
{
    \"title\": \"Proposed Meeting Title\",
    \"discussion_notes\": \"Detailed structured minutes of discussion in Indonesian\",
    \"action_items\": [
        {
            \"description\": \"Task description\",
            \"pic_id\": 1,
            \"due_date\": \"YYYY-MM-DD\"
        }
    ]
}

Return pure JSON without any markdown formatting or backticks.";

        if ($this->driver === 'ollama') {
            return $this->callOllama($prompt, true);
        }
        if ($this->driver === 'openrouter') {
            return $this->callOpenRouter($prompt, null, null, true);
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(120)->post("{$this->baseUrl}?key={$this->apiKey}", [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => ['response_mime_type' => 'application/json']
            ]);
            return $this->parseResponse($response);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Exception in GeminiService (Meeting AI): ' . $e->getMessage());
        }
        return null;
    }

    /**
     * Generate meeting minutes, summary, and action items directly from an uploaded audio file.
     */
    public function generateMeetingMinutesFromAudio(string $filePath, string $mimeType): ?array
    {
        $this->ensureConfigured();

        // Local model Ollama usually does not support direct multimodal audio input as easily,
        // so we restrict direct audio parsing to the Gemini API.
        if ($this->driver === 'ollama') {
            Log::warning('Ollama does not natively support direct audio multimodal parsing in this context.');
            return null;
        }
        if ($this->driver === 'openrouter') {
            Log::warning('OpenRouter does not natively support direct audio multimodal parsing in this context.');
            return null;
        }

        if (!$this->apiKey) {
            Log::error('Gemini API Key is not configured for Audio Processing.');
            return null;
        }

        $fileData = base64_encode(file_get_contents($filePath));
        $prompt = $this->getAudioMeetingMinutesPrompt();

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(180)->post("{$this->baseUrl}?key={$this->apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                            [
                                'inline_data' => [
                                    'mime_type' => $mimeType,
                                    'data' => $fileData
                                ]
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'response_mime_type' => 'application/json'
                ]
            ]);

            return $this->parseResponse($response);
        } catch (\Exception $e) {
            Log::error('Exception in GeminiService (Audio Meeting AI): ' . $e->getMessage());
        }
        return null;
    }

    /**
     * Get prompt for audio meeting minutes extraction.
     */
    protected function getAudioMeetingMinutesPrompt(): string
    {
        $usersList = \App\Models\User::select('id', 'name')->get()->map(function($u) {
            return "ID: {$u->id}, Name: {$u->name}";
        })->implode("\n");

        return "You are an expert executive secretary and AI assistant.
Listen to this audio recording of a meeting carefully, transcribe/understand it, and generate a structured Minutes of Meeting (Notulen Rapat) in **Bahasa Indonesia**.

Here is the list of active users in the system who can be assigned as Chairperson, Secretary, or PIC for Action Items:
{$usersList}

YOUR TASK:
1. Generate a structured title/agenda for the meeting based on the spoken content.
2. Structure the 'discussion_notes' into neat paragraphs with agenda headings and clear bullet points. Write this strictly in **Bahasa Indonesia**.
3. Identify all Action Items (Tugas Tindak Lanjut) mentioned in the audio.
4. For each Action Item, extract:
   - 'description': What needs to be done.
   - 'pic_id': The ID of the user assigned to this task (match from the user list above. If a name matches closely, use their ID. If no match is found, assign a default or leave it empty).
   - 'due_date': The deadline for the task (in YYYY-MM-DD format. If no date is mentioned, estimate a reasonable deadline, e.g. 7 days from today. Assume today is " . now()->toDateString() . ").

Output format:
Extract and return ONLY a valid JSON object with this exact structure:
{
    \"title\": \"Proposed Meeting Title\",
    \"discussion_notes\": \"Detailed structured minutes of discussion in Indonesian\",
    \"action_items\": [
        {
            \"description\": \"Task description\",
            \"pic_id\": 1,
            \"due_date\": \"YYYY-MM-DD\"
        }
    ]
}

Return pure JSON without any markdown formatting or backticks.";
    }

    /**
     * Analyze supplier intent for Purchasing bot
     */
    public function analyzeSupplierIntent(string $message, ?array $supplierContext = null, array $conversationHistory = []): array
    {
        $this->ensureConfigured();
        $contextInfo = $supplierContext ? "Supplier Name: {$supplierContext['name']}" : "Unknown supplier";
        
        $purchasingInstruction = AppSetting::get('purchasing_whatsapp_bot_instruction', '');
        $systemInstruction = $purchasingInstruction 
            ? "Personality & Context: {$purchasingInstruction}"
            : "You are a friendly and professional procurement/purchasing assistant for PT JIDOKA RESULT INDONESIA. Your job is to serve our suppliers and vendors.";

        $historyText = '';
        if (!empty($conversationHistory)) {
            $historyText = "\n\nRecent conversation history (for context):\n";
            foreach ($conversationHistory as $msg) {
                $role = $msg['role'] === 'supplier' ? 'Supplier' : 'Bot';
                $historyText .= "{$role}: {$msg['message']}\n";
            }
        }

        $prompt = "{$systemInstruction}
Analyze this message from a Supplier/Vendor and classify the intent precisely.

Context: {$contextInfo}{$historyText}
New Message: \"{$message}\"

Intents:
- greeting: Sapaan awal (Halo, Selamat siang, Pagi, dll)
- casual_chat: Percakapan santai (terima kasih, ok, baik, siap, dll)
- po_status: Tanya status Purchase Order / pesanan pembelian yang kami kirimkan ke supplier
- grn_status: Tanya status penerimaan barang / Goods Receipt Note (GRN) di gudang kami
- rfq_status: Tanya info RFQ (Request for Quotation) atau permintaan penawaran harga
- supplier_invoice: Tanya status pembayaran invoice tagihan dari supplier ke kami
- faq: Pertanyaan umum seputar kantor kami (lokasi kirim, jam bongkar muat, dll)
- unknown: Tidak bisa diklasifikasikan

Return JSON strictly: { \"intent\": \"...\", \"parameters\": { \"po_number\": \"...\", \"rfq_number\": \"...\" }, \"confidence\": 0.9 }";

        if ($this->driver === 'ollama') {
            $result = $this->callOllama($prompt, true);
            return $result ?? ['intent' => 'unknown', 'parameters' => []];
        }
        if ($this->driver === 'openrouter') {
            $result = $this->callOpenRouter($prompt, null, null, true);
            return $result ?? ['intent' => 'unknown', 'parameters' => []];
        }

        try {
            $response = Http::timeout(30)->post("{$this->baseUrl}?key={$this->apiKey}", [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => ['response_mime_type' => 'application/json'],
            ]);
            $result = $this->parseResponse($response);
            return $result ?? ['intent' => 'unknown', 'parameters' => []];
        } catch (\Exception $e) {
            Log::error('Supplier Intent Error: ' . $e->getMessage());
        }
        return ['intent' => 'unknown', 'parameters' => []];
    }

    /**
     * FAQ Response for Supplier Bot
     */
    public function generateSupplierFAQResponse(string $question, array $conversationHistory = []): string
    {
        $this->ensureConfigured();
        $purchasingInstruction = AppSetting::get('purchasing_whatsapp_bot_instruction', '');
        $systemInstruction = $purchasingInstruction 
            ? "Personality & Context: {$purchasingInstruction}"
            : "You are a helpful purchasing assistant for PT JIDOKA RESULT INDONESIA, dealing with suppliers.";

        $historyText = '';
        if (!empty($conversationHistory)) {
            $historyText = "\n\nRecent conversation for context:\n";
            foreach ($conversationHistory as $msg) {
                $role = $msg['role'] === 'supplier' ? 'Supplier' : 'Bot';
                $historyText .= "{$role}: {$msg['message']}\n";
            }
        }

        $prompt = "{$systemInstruction}
Answer this supplier question in Indonesian, friendly and concise (max 200 chars).{$historyText}
Question: \"{$question}\"";

        if ($this->driver === 'ollama') {
            try {
                $payload = ['model' => $this->ollamaModel, 'prompt' => $prompt, 'stream' => false];
                $url = rtrim($this->ollamaUrl, '/');
                $res = Http::timeout(30)->post("{$url}/api/generate", $payload);
                if ($res->successful()) return $res->json()['response'] ?? "Maaf, error.";
            } catch (\Exception $e) {}
            return "Maaf, layanan sedang sibuk.";
        }
        if ($this->driver === 'openrouter') {
            $res = $this->callOpenRouter($prompt, null, null, false);
            return $res['text'] ?? "Maaf, asisten virtual sedang sibuk.";
        }

        try {
            $response = Http::timeout(30)->post("{$this->baseUrl}?key={$this->apiKey}", [
                'contents' => [['parts' => [['text' => $prompt]]]],
            ]);
            if ($response->successful()) {
                return $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? "Maaf, saya tidak mengerti.";
            }
        } catch (\Exception $e) {
            Log::error("Supplier Gemini FAQ Error: " . $e->getMessage());
        }

        return "Maaf, saat ini asisten virtual purchasing sedang sibuk. Silakan hubungi bagian pengadaan kami secara langsung.";
    }

    /**
     * Analyze bank statement transactions and match them semantically against pending invoices.
     *
     * @param array $transactions List of bank statement transactions
     * @param array $invoices List of pending invoices
     * @param string $type Either 'sales' or 'purchase'
     * @return array|null JSON decoded response from LLM containing the matches
     */
    public function analyzeBankReconciliation(array $transactions, array $invoices, string $type): ?array
    {
        $this->ensureConfigured();

        // Simplify payload data sent to the AI to save tokens and prevent context limit issues
        $cleanTransactions = array_map(function($t) {
            return [
                'id' => $t['id'],
                'date' => is_string($t['transaction_date']) ? $t['transaction_date'] : (\Carbon\Carbon::parse($t['transaction_date'])->format('Y-m-d')),
                'description' => $t['description'],
                'amount' => $t['amount'],
                'reference_number' => $t['reference_number'] ?? null,
            ];
        }, $transactions);

        $cleanInvoices = array_map(function($i) use ($type) {
            $isSales = $type === 'sales';
            return [
                'id' => $i['id'],
                'invoice_number' => $i['invoice_number'],
                'partner_name' => $isSales ? ($i['customer']['name'] ?? '') : ($i['supplier']['name'] ?? ''),
                'total_amount' => $isSales ? ($i['total'] ?? 0) : ($i['total_amount'] ?? 0),
                'balance_due' => $isSales ? ($i['balance'] ?? 0) : (($i['total_amount'] ?? 0) - ($i['paid_amount'] ?? 0)),
                'due_date' => $i['due_date'] ?? null,
            ];
        }, $invoices);

        $prompt = "You are a smart bank reconciliation assistant. Match the following bank statement transactions (unreconciled) against the pending invoices.
        
        Type of reconciliation: " . ($type === 'sales' ? 'Customer Payments (Credit/Uang Masuk)' : 'Supplier Payments (Debit/Uang Keluar)') . "
        
        UNRECONCILED TRANSACTIONS:
        " . json_encode($cleanTransactions, JSON_PRETTY_PRINT) . "
        
        PENDING INVOICES:
        " . json_encode($cleanInvoices, JSON_PRETTY_PRINT) . "
        
        INSTRUCTIONS:
        1. For each transaction, try to find the best matching invoice from the pending invoices list.
        2. Perform semantic analysis on the transaction description (news/remarks, sender name, etc.) and match it with invoice numbers, partner/company names, or amounts.
        3. Even if names or numbers do not match exactly, use context (e.g. abbreviations, initials, common Indonesian typos/variations, or exact amounts) to propose matches.
        4. Suggest an allocated_amount. It should be the minimum of transaction amount and the invoice balance_due.
        5. For each match, provide a confidence score from 0.0 to 1.0 (1.0 being 100% sure) and a brief reason in Bahasa Indonesia explaining the match.
        
        Return strictly a JSON object with this format:
        {
            \"matches\": [
                {
                    \"bank_transaction_id\": 12,
                    \"invoice_id\": 45,
                    \"allocated_amount\": 15000000,
                    \"confidence\": 0.95,
                    \"reason\": \"Nama pengirim 'Budi' cocok dengan customer 'PT Budi Abadi' dan nominal transfer pas.\"
                }
            ]
        }
        
        Return pure JSON without any markdown formatting or backticks. If no matches are found, return {\"matches\": []}.";

        if ($this->driver === 'ollama') {
            return $this->callOllama($prompt, true);
        }
        if ($this->driver === 'openrouter') {
            return $this->callOpenRouter($prompt, null, null, true);
        }

        // Gemini Logic
        try {
            $response = Http::timeout(120)->post("{$this->baseUrl}?key={$this->apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'response_mime_type' => 'application/json',
                ]
            ]);

            return $this->parseResponse($response);
        } catch (\Exception $e) {
            Log::error('AI Reconciliation Analysis Error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Analyze costing module elements using LLM
     */
    public function analyzeCosting(string $mode, array $costElements, string $totalValue): ?array
    {
        $this->ensureConfigured();

        $prompt = "You are an expert Manufacturing Cost Accountant and Financial AI Auditor.
        
        Analyze the following costing data for the module: " . strtoupper($mode) . "
        
        COST ELEMENTS:
        " . json_encode($costElements, JSON_PRETTY_PRINT) . "
        
        TOTAL VALUE: {$totalValue}
        
        INSTRUCTIONS:
        1. Perform a thorough costing audit or margin/overhead analysis based on the mode:
           - For 'production': Audit the variance of raw materials, direct labor, factory overhead. Suggest where the efficiency leaks might be (e.g. material price fluctuations, labor overtime, idle machines).
           - For 'overhead': Analyze the overhead allocation drivers. Suggest optimization of cost drivers (machine hours vs labor hours) and evaluate the absorption rate.
           - For 'profitability': Analyze the gross margin, COGS vs OPEX, and identify pricing anomalies, margin leaks, or sensitivity to raw material price changes.
        2. Provide your findings, recommendations, and a brief action plan.
        3. Write the response in professional Bahasa Indonesia. Keep it structured, formatting with bullet points and bold highlights.
        4. Return the response as a JSON object in this format:
        {
            \"analysis\": \"<write the complete HTML-formatted or Markdown-formatted analysis text here. Use bold text, line breaks, bullet points to make it visually beautiful and premium.>\",
            \"score\": 0.85,
            \"leaks_detected\": 2,
            \"recommendation\": \"Ringkasan rekomendasi utama dalam 1 kalimat.\"
        }
        
        Return pure JSON without any markdown formatting or backticks.
        ";

        if ($this->driver === 'ollama') {
            return $this->callOllama($prompt, true);
        }
        if ($this->driver === 'openrouter') {
            return $this->callOpenRouter($prompt, null, null, true);
        }

        // Gemini Logic
        try {
            $response = Http::timeout(120)->post("{$this->baseUrl}?key={$this->apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'response_mime_type' => 'application/json',
                ]
            ]);

            return $this->parseResponse($response);
        } catch (\Exception $e) {
            Log::error('AI Costing Analysis Error: ' . $e->getMessage());
        }

        return null;
    }

    public function analyzeStockShortages(array $shortageData): ?array
    {
        $this->ensureConfigured();

        $prompt = "You are an expert Material Requirements Planning (MRP) and Inventory Control Analyst AI.
        
        Analyze the following stock shortage and Sales Order demand data:
        
        SHORTAGE DATA:
        " . json_encode($shortageData, JSON_PRETTY_PRINT) . "
        
        INSTRUCTIONS:
        1. Examine each product with a shortage.
        2. Check if a shortage can be resolved by Reclassification (Stock Reclass) from an equivalent product that has available stock.
        3. If it cannot be resolved by Reclassification, determine the procurement route:
           - If it is a purchased product (is_purchased = true, is_manufactured = false), recommend creating a Purchase Order (PO). Check the 'historical_suppliers' list and choose the supplier with the lowest 'cheapest_price'. If no historical suppliers exist, recommend the 'default_supplier_id' and 'default_supplier_name'.
           - If it is a manufactured product (is_manufactured = true) with production_type = 'internal', recommend a Work Order (Internal).
           - If it is a manufactured product with production_type = 'subcontract', recommend a Work Order (Subcontract) and suggest the supplier_id if provided.
        4. For any recommended Work Order (internal or subcontract), check if the required BOM components are short. If BOM components are short, recommend POs for those raw materials (applying the cheapest price supplier check from 'historical_suppliers' if available).
        5. Write the analysis_summary in professional Bahasa Indonesia. Make it clean and structured.
        6. Return the response as a JSON object in this exact format:
        {
            \"reclassifications\": [
                {
                    \"source_product_id\": 1,
                    \"source_sku\": \"SKU-1\",
                    \"source_name\": \"Product Source Name\",
                    \"target_product_id\": 2,
                    \"target_sku\": \"SKU-2\",
                    \"target_name\": \"Product Target Name\",
                    \"qty\": 15.0,
                    \"reason\": \"Reason text in Bahasa Indonesia\"
                }
            ],
            \"purchase_orders\": [
                {
                    \"product_id\": 3,
                    \"sku\": \"SKU-3\",
                    \"name\": \"Product Name\",
                    \"qty\": 50.0,
                    \"supplier_id\": 5,
                    \"supplier_name\": \"Supplier Name\",
                    \"reason\": \"Reason text in Bahasa Indonesia\"
                }
            ],
            \"work_orders\": [
                {
                    \"product_id\": 4,
                    \"sku\": \"SKU-4\",
                    \"name\": \"Product Name\",
                    \"qty\": 30.0,
                    \"production_type\": \"internal\",
                    \"supplier_id\": null,
                    \"supplier_name\": \"\",
                    \"reason\": \"Reason text in Bahasa Indonesia\"
                }
            ],
            \"analysis_summary\": \"<Write a complete markdown analysis of the stock situation, highlighting the reclassifications and procurement plans in Bahasa Indonesia.>\"
        }
        
        Return pure JSON without any markdown formatting or backticks.
        ";

        if ($this->driver === 'ollama') {
            return $this->callOllama($prompt, true);
        }
        if ($this->driver === 'openrouter') {
            return $this->callOpenRouter($prompt, null, null, true);
        }

        try {
            $response = Http::timeout(120)->post("{$this->baseUrl}?key={$this->apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'response_mime_type' => 'application/json',
                ]
            ]);

            return $this->parseResponse($response);
        } catch (\Exception $e) {
            Log::error('AI Stock Analysis Error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Transcribe an audio file to text using Gemini.
     */
    public function transcribeAudio(string $filePath, string $mimeType): ?string
    {
        $this->ensureConfigured();

        if ($this->driver === 'ollama') {
            Log::warning('Ollama does not natively support direct audio transcription in this context.');
            return null;
        }

        if ($this->driver === 'openrouter') {
            if (!$this->openrouterApiKey) {
                Log::error('OpenRouter API Key is not configured.');
                return null;
            }

            // Map mimeType to format string (e.g. audio/mp3 -> mp3, audio/ogg -> ogg, etc.)
            $format = 'mp3';
            if (str_contains($mimeType, 'ogg') || str_contains($mimeType, 'opus')) {
                $format = 'ogg';
            } elseif (str_contains($mimeType, 'wav')) {
                $format = 'wav';
            } elseif (str_contains($mimeType, 'm4a')) {
                $format = 'm4a';
            }

            try {
                $base64Data = base64_encode(file_get_contents($filePath));
                
                $payload = [
                    'model' => $this->openrouterModel,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => "Dengarkan audio ini dengan seksama dan transkripsikan seluruh isi ucapannya ke dalam bentuk teks bahasa Indonesia secara lengkap dan akurat. Jangan tambahkan penjelasan lain, hanya teks hasil transkripsi."
                                ],
                                [
                                    'type' => 'input_audio',
                                    'input_audio' => [
                                        'data' => $base64Data,
                                        'format' => $format
                                    ]
                                ]
                            ]
                        ]
                    ]
                ];

                $response = Http::timeout(120)
                    ->withHeaders([
                        'Authorization' => "Bearer {$this->openrouterApiKey}",
                        'HTTP-Referer' => url('/'),
                        'X-Title' => 'USICS ERP',
                    ])
                    ->post('https://openrouter.ai/api/v1/chat/completions', $payload);

                if ($response->successful()) {
                    $result = $response->json();
                    return $result['choices'][0]['message']['content'] ?? null;
                }
                
                Log::error('OpenRouter Audio Transcription Failed: ' . $response->body());
            } catch (\Exception $e) {
                Log::error('Exception in GeminiService (transcribeAudio OpenRouter): ' . $e->getMessage());
            }
            
            return null;
        }

        if (!$this->apiKey) {
            Log::error('Gemini API Key is not configured for Audio Processing.');
            return null;
        }

        try {
            $fileData = base64_encode(file_get_contents($filePath));
            $prompt = "Dengarkan audio ini dengan seksama dan transkripsikan seluruh isi ucapannya ke dalam bentuk teks bahasa Indonesia secara lengkap dan akurat. Jangan tambahkan penjelasan lain, hanya teks hasil transkripsi.";

            $response = Http::timeout(60)->post("{$this->baseUrl}?key={$this->apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                            [
                                'inline_data' => [
                                    'mime_type' => $mimeType,
                                    'data' => $fileData
                                ]
                            ]
                        ]
                    ]
                ]
            ]);

            if ($response->successful()) {
                $result = $response->json();
                return $result['candidates'][0]['content']['parts'][0]['text'] ?? null;
            }

            Log::error('Gemini Audio Transcription Failed: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Exception in GeminiService (transcribeAudio): ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Download audio file from URL and transcribe it.
     */
    public function transcribeAudioFromUrl(string $url): ?string
    {
        try {
            Log::info('Downloading audio file for transcription from: ' . $url);
            $response = Http::timeout(60)->get($url);
            if (!$response->successful()) {
                Log::error('Failed to download audio file from url: ' . $url . ' Status: ' . $response->status());
                return null;
            }

            $content = $response->body();
            // Determine mimetype from URL, headers, or default to audio/ogg
            $contentType = $response->header('Content-Type');
            
            // Clean content type (e.g. "audio/ogg; codecs=opus" -> "audio/ogg")
            if ($contentType) {
                $contentType = explode(';', $contentType)[0];
            } else {
                $contentType = 'audio/ogg';
            }
            
            // If the URL has an extension, map it to a proper mime type
            $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
            if ($extension === 'mp3') {
                $contentType = 'audio/mp3';
            } elseif ($extension === 'wav') {
                $contentType = 'audio/wav';
            } elseif ($extension === 'm4a') {
                $contentType = 'audio/m4a';
            }

            // Write to a temporary file in storage/app/tmp
            $tempDir = storage_path('app/tmp');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            $tempFile = $tempDir . '/' . uniqid('voice_') . '.tmp';
            file_put_contents($tempFile, $content);

            $transcription = $this->transcribeAudio($tempFile, $contentType);

            // Clean up temp file
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }

            if ($transcription) {
                Log::info('Successfully transcribed audio to: ' . $transcription);
            } else {
                Log::warning('Audio transcription returned null');
            }

            return $transcription;
        } catch (\Exception $e) {
            Log::error('transcribeAudioFromUrl exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Solve the Vehicle Routing Problem (VRP) using Gemini AI.
     */
    public function solveVrp(array $vehicles, array $deliveryOrders, float $depotLat, float $depotLng): ?array
    {
        $this->ensureConfigured();

        $prompt = "You are a professional logistics coordinator and Vehicle Routing Problem (VRP) optimization engine.
        
        Your goal is to optimize delivery routes for a fleet of vehicles delivering orders to various customer locations from a central depot.
        
        DEPOT COORDINATES (Start & End Point):
        Latitude: {$depotLat}
        Longitude: {$depotLng}
        
        AVAILABLE VEHICLES:
        " . json_encode($vehicles, JSON_PRETTY_PRINT) . "
        
        PENDING DELIVERY ORDERS:
        " . json_encode($deliveryOrders, JSON_PRETTY_PRINT) . "
        
        INSTRUCTIONS:
        1. Group/cluster the delivery orders into optimized routes/shipments.
        2. Assign each shipment to ONE vehicle.
        3. Make sure the total weight of all delivery orders in a single shipment does not exceed the vehicle's capacity (capacity_weight).
        4. Sort the stops in each route logically based on geographic proximity to form a sequence (Stop 1 -> Stop 2 -> Stop 3).
        5. For each shipment, calculate:
           - Total weight (sum of order weights).
           - Total estimated distance (Manhattan or Haversine distance sequence from depot -> Stop 1 -> Stop 2 -> ... -> depot, in km).
           - Suggested travel allowance (Uang Jalan) in Rupiah (IDR). Provide a realistic recommendation (e.g. Rp 100.000 - Rp 500.000 depending on distance and number of stops).
           - Route description (e.g., \"Rute Jakarta Barat - Tangerang\").
        6. Return the response as a JSON object in this exact format:
        {
            \"shipments\": [
                {
                    \"vehicle_id\": 1,
                    \"vehicle_plate\": \"B 1234 ABC\",
                    \"driver_name\": \"Supir A\",
                    \"route_name\": \"Route description\",
                    \"total_weight\": 1200.0,
                    \"estimated_distance_km\": 35.5,
                    \"suggested_allowance\": 250000,
                    \"stops\": [
                        {
                            \"sequence\": 1,
                            \"delivery_order_id\": 5,
                            \"do_number\": \"DO-202607-0001\",
                            \"customer_name\": \"Customer X\",
                            \"address\": \"Address X\",
                            \"latitude\": -6.123,
                            \"longitude\": 106.456,
                            \"weight\": 500.0
                        }
                    ]
                }
            ],
            \"unassigned_orders\": [
                {
                    \"delivery_order_id\": 6,
                    \"do_number\": \"DO-202607-0002\",
                    \"reason\": \"Reason why it could not be assigned (e.g., all vehicle capacities exceeded)\"
                }
            ]
        }
        
        Return pure JSON without any markdown formatting or backticks.
        ";

        if ($this->driver === 'ollama') {
            return $this->callOllama($prompt, true);
        }
        if ($this->driver === 'openrouter') {
            return $this->callOpenRouter($prompt, null, null, true);
        }

        try {
            $response = Http::timeout(120)->post("{$this->baseUrl}?key={$this->apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'response_mime_type' => 'application/json',
                ]
            ]);

            return $this->parseResponse($response);
        } catch (\Exception $e) {
            Log::error('AI VRP Optimization Error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Analyze dynamic pricing for steel products using Gemini AI.
     */
    public function analyzeDynamicPricing(array $products, array $params): ?array
    {
        $this->ensureConfigured();

        $prompt = "You are a professional industrial steel pricing analyst and AI financial manager for USICS (United Steel Intelligence Control System).
        
        Your task is to calculate and suggest optimal selling prices for a list of steel products based on cost data, market parameters, and pricing strategies.
        
        PRICING PARAMETERS:
        - LME Steel Price: {$params['lme_price']} USD / Metric Ton
        - USD to IDR Exchange Rate: {$params['exchange_rate']} IDR
        - Target Margin: {$params['target_margin']}%
        - Processing/Slitting Fee: {$params['processing_fee']} IDR / kg
        - Scrap Recovery Factor: {$params['scrap_recovery']}%
        
        PRODUCTS TO ANALYZE:
        " . json_encode($products, JSON_PRETTY_PRINT) . "
        
        FORMULA/LOGIC INSTRUCTIONS:
        1. Calculate the raw material LME cost per kg in IDR: (LME Price * Exchange Rate) / 1000.
        2. Combine the product's base cost_price with the raw material price change relative to typical base costs. Or calculate from scratch:
           - Raw Material Cost = (LME Price * Exchange Rate) / 1000.
           - Processing Cost = Use the product's specific `processing_fee` if it is present and not null, otherwise fallback to the global Processing/Slitting Fee parameter.
           - Scrap Recovery Factor = Use the product's specific `scrap_recovery` if it is present and not null, otherwise fallback to the global Scrap Recovery Factor parameter.
           - Scrap Recovery Discount = Raw Material Cost * (Scrap Recovery Factor / 100) * 0.4 (assuming scrap value is 40% of prime steel value).
           - Suggested Cost Price = Raw Material Cost + Processing Cost - Scrap Recovery Discount.
           - Use the Suggested Cost Price or the product's actual cost_price (whichever is higher/more appropriate) as the base suggested cost.
        3. Recommended Selling Price = Suggested Cost Price / (1 - (Target Margin / 100)).
        4. Min Selling Price = Suggested Cost Price / (1 - ((Target Margin - 3) / 100)).
        5. Max Selling Price = Suggested Cost Price / (1 - ((Target Margin + 4) / 100)).
        6. Determine the Market Trend (bullish, stable, bearish) based on LME steel price level (e.g. above 600 USD/ton is bullish, 500-600 is stable, below 500 is bearish).
        7. Provide a detailed markdown strategic recommendation report explaining market factors, inventory holding tips, and customer pricing policies.
        
        Return the response as a JSON object in this exact format:
        {
            \"market_trend\": \"bullish|stable|bearish\",
            \"market_trend_explanation\": \"Short explanation of trend.\",
            \"pricing_suggestions\": [
                {
                    \"sku\": \"RM-STEEL-001\",
                    \"suggested_cost_price\": 16500,
                    \"min_selling_price\": 18000,
                    \"recommended_selling_price\": 19500,
                    \"max_selling_price\": 21000,
                    \"margin_percentage\": 15.4,
                    \"rationale\": \"Brief rationale explaining cost breakdown and why this price is recommended.\"
                }
            ],
            \"strategic_recommendations\": \"### Analisis Pasar & Strategi Harga\\n\\n1. **Kondisi Pasar:** ...\\n2. **Rekomendasi Inventory:** ...\"
        }
        
        Return pure JSON without any markdown formatting or backticks.
        ";

        if ($this->driver === 'ollama') {
            return $this->callOllama($prompt, true);
        }
        if ($this->driver === 'openrouter') {
            return $this->callOpenRouter($prompt, null, null, true);
        }

        try {
            $response = Http::timeout(120)->post("{$this->baseUrl}?key={$this->apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'response_mime_type' => 'application/json',
                ]
            ]);

            return $this->parseResponse($response);
        } catch (\Exception $e) {
            Log::error('AI Pricing Intelligence Error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Run AI Predictive Maintenance Analysis using Gemini AI.
     */
    public function analyzePredictiveMaintenance(array $machines, array $spareparts, array $breakdowns): ?array
    {
        $this->ensureConfigured();

        $prompt = "You are a professional industrial plant engineer and AI Predictive Maintenance Consultant.
        
        Your goal is to analyze the telemetry, health scores, breakdown history, and sparepart stocks of a steel pipe manufacturing plant, identify risks, and recommend proactive maintenance actions and sparepart purchases.
        
        MACHINES DATA:
        " . json_encode($machines, JSON_PRETTY_PRINT) . "
        
        CRITICAL SPAREPARTS:
        " . json_encode($spareparts, JSON_PRETTY_PRINT) . "
        
        RECENT BREAKDOWNS:
        " . json_encode($breakdowns, JSON_PRETTY_PRINT) . "
        
        INSTRUCTIONS:
        1. Diagnose the machines with low health scores or imminent predicted failure dates. Provide a clear engineering diagnosis and recommended preventive maintenance actions for each.
        2. Identify critical sparepart shortages. Determine which parts should be ordered immediately to support upcoming preventive maintenance or mitigate breakdown risks.
        3. Recommend specific purchase quantities for each sparepart.
        4. Return the results as a JSON object in this exact format:
        {
            \"critical_machines\": [
                {
                    \"machine_id\": 1,
                    \"machine_name\": \"Slitter Machine A\",
                    \"machine_code\": \"SLT-A\",
                    \"health_score\": 65,
                    \"diagnosis\": \"Frequent breakdown logs indicate alignment issues or high wear rate on the blades.\",
                    \"recommended_actions\": \"Schedule calibration check, inspect main arbor alignment, and prepare replacement blades.\",
                    \"priority\": \"High\"
                }
            ],
            \"sparepart_recommendations\": [
                {
                    \"sparepart_id\": 3,
                    \"part_number\": \"SP-SLT-BLADE-01\",
                    \"name\": \"Slitter Blade 300mm\",
                    \"recommended_qty\": 4,
                    \"priority\": \"High\",
                    \"justification\": \"Required for upcoming scheduled calibration and blade replacement on Slitter Machine A due to high wear rate.\"
                }
            ],
            \"general_insights\": \"Summary of plant health and suggestions for improving overall equipment effectiveness (OEE).\"
        }
        
        Return pure JSON without any markdown formatting or backticks.
        ";

        if ($this->driver === 'ollama') {
            return $this->callOllama($prompt, true);
        }
        if ($this->driver === 'openrouter') {
            return $this->callOpenRouter($prompt, null, null, true);
        }

        try {
            $response = Http::timeout(120)->post("{$this->baseUrl}?key={$this->apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'response_mime_type' => 'application/json',
                ]
            ]);

            return $this->parseResponse($response);
        } catch (\Exception $e) {
            Log::error('AI Predictive Maintenance Error: ' . $e->getMessage());
        }

        return null;
    }
}


