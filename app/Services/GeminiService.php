<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;
use App\Models\AppSetting;

class GeminiService
{
    protected string $driver;
    protected string $apiKey;
    protected string $model;
    protected string $baseUrl;
    
    // Ollama specific
    protected string $ollamaUrl;
    protected string $ollamaModel;

    protected string $customBotInstruction;

    public function __construct()
    {
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

        $this->customBotInstruction = (string) AppSetting::get('whatsapp_bot_instruction', '');
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
        if ($this->driver === 'ollama') {
            return $this->extractPODataOllama($filePath, $mimeType);
        }

        return $this->extractPODataGemini($filePath, $mimeType);
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
            $result = $response->json();
            $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;
            
            Log::info('Gemini API Raw Response: ' . substr($text ?? 'NULL', 0, 500));
            
            if ($text) {
                // Clean markdown code blocks if present
                $text = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($text));
                return json_decode($text, true);
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
        
        Extract and return ONLY a valid JSON object with this exact structure:
        {
            \"po_number\": \"the PO number or null\",
            \"po_date\": \"YYYY-MM-DD format or null\",
            \"delivery_date\": \"YYYY-MM-DD format or null\",
            \"customer_name\": \"name of the BUYER company\",
            \"customer_address\": \"address if visible or null\",
            \"items\": [
                {
                    \"description\": \"product name/description\",
                    \"qty\": 100,
                    \"unit\": \"Pcs\",
                    \"unit_price\": 15000,
                    \"total_price\": 1500000
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
    public function analyzeCustomerIntent(string $message, ?array $customerContext = null): array
    {
        $contextInfo = $customerContext ? "Customer Name: {$customerContext['name']}" : "Unknown customer";
        
        $systemInstruction = $this->customBotInstruction 
            ? "Personality & Context: {$this->customBotInstruction}"
            : "You are a friendly and professional customer service assistant for PT SPINDO, an Indonesian steel pipe manufacturer.";

        $prompt = "{$systemInstruction}
Analyze this message and classify the intent precisely.

Context: {$contextInfo}
Message: \"{$message}\"

Intents:
- greeting: Sapaan awal (Halo, Selamat siang, Pagi, etc.)
- casual_chat: Percakapan santai non-teknis (tanya kabar, terima kasih, ok, siap, etc.)
- order_status: Tanya status pesanan/pengiriman (status SO, nomor DO, dll)
- invoice_check: Tanya tagihan/piutang/invoice
- product_catalog: Tanya katalog produk/harga/pipa
- request_quotation: Minta penawaran harga
- faq: Pertanyaan umum tentang perusahaan
- unknown: Tidak bisa diklasifikasikan

Return JSON strictly: { \"intent\": \"...\", \"parameters\": { \"order_number\": \"...\", \"product_name\": \"...\" }, \"confidence\": 0.9 }";

        if ($this->driver === 'ollama') {
            $result = $this->callOllama($prompt, true);
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
    public function generateFAQResponse(string $question): string
    {
        $systemInstruction = $this->customBotInstruction 
            ? "Personality & Context: {$this->customBotInstruction}"
            : "You are a helpful customer service assistant for PT SPINDO.";

        $prompt = "{$systemInstruction}
Answer this customer question in Indonesian, friendly and concise (max 200 chars):
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

        // Gemini
        try {
            $response = Http::timeout(30)->post("{$this->baseUrl}?key={$this->apiKey}", [
                'contents' => [['parts' => [['text' => $prompt]]]],
            ]);
            if ($response->successful()) {
                return $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? "Maaf, saya tidak mengerti.";
            }
            
            return "AI Error (" . $response->status() . "): " . substr($response->body(), 0, 200);
        } catch (\Exception $e) {
            return "Maaf, terjadi kesalahan sistem AI: " . $e->getMessage();
        }

        return "Terima kasih atas pertanyaan Anda. Silakan hubungi CS kami (AI No Response / Null).";
    }

    /**
     * Analyze forecast accuracy and provide AI-powered recommendations.
     *
     * @param array $forecastData Array of forecast rows with keys: customer, product, period, forecast, actual, accuracy
     * @return string Markdown-formatted analysis text
     */
    public function analyzeForecastAccuracy(array $forecastData): string
    {
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
}
