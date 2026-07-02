<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MtcExtractionService
{
    /**
     * The JSON schema that Gemini must return for MTC extraction.
     */
    protected array $responseSchema = [
        'type' => 'OBJECT',
        'properties' => [
            'header' => [
                'type' => 'OBJECT',
                'properties' => [
                    'supplier_name' => ['type' => 'STRING', 'description' => 'Full name of the steel supplier/manufacturer (e.g. PT. KRAKATAU POSCO, NIPPON STEEL)'],
                    'certificate_number' => ['type' => 'STRING', 'description' => 'Certificate number or document ID'],
                    'date_of_issue' => ['type' => 'STRING', 'description' => 'Date of issue in YYYY-MM-DD format'],
                    'order_no' => ['type' => 'STRING', 'description' => 'Order number'],
                    'po_no' => ['type' => 'STRING', 'description' => 'Purchase Order number'],
                    'commodity' => ['type' => 'STRING', 'description' => 'Type of commodity (e.g. HOT ROLLED COIL, COLD ROLLED COIL)'],
                    'spec_and_type' => ['type' => 'STRING', 'description' => 'Specification and type (e.g. API 5L BM PSL2, JIS G3132)'],
                    'customer' => ['type' => 'STRING', 'description' => 'Customer name if present'],
                ],
            ],
            'items' => [
                'type' => 'ARRAY',
                'items' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'product_no' => ['type' => 'STRING', 'description' => 'Product number or coil ID'],
                        'heat_no' => ['type' => 'STRING', 'description' => 'Heat number'],
                        'size' => ['type' => 'STRING', 'description' => 'Dimensions (e.g. 6.02x1045xC)'],
                        'quantity' => ['type' => 'INTEGER', 'description' => 'Quantity'],
                        'weight_kg' => ['type' => 'NUMBER', 'description' => 'Weight in kilograms'],
                        'position' => ['type' => 'STRING', 'description' => 'Position: T(Top), M(Middle), B(Bottom)'],
                        'yp_mpa' => ['type' => 'NUMBER', 'description' => 'Yield Point in MPa'],
                        'ts_mpa' => ['type' => 'NUMBER', 'description' => 'Tensile Strength in MPa'],
                        'el_percent' => ['type' => 'NUMBER', 'description' => 'Elongation percentage'],
                        'yr_percent' => ['type' => 'NUMBER', 'description' => 'Yield Ratio percentage'],
                        'bend_test' => ['type' => 'STRING', 'description' => 'Bend test result (e.g. Good, Pass)'],
                        'impact_test_data' => [
                            'type' => 'ARRAY',
                            'items' => [
                                'type' => 'OBJECT',
                                'properties' => [
                                    'position' => ['type' => 'STRING'],
                                    'energy_joule' => ['type' => 'NUMBER'],
                                    'sf_percent' => ['type' => 'NUMBER'],
                                    'temperature' => ['type' => 'STRING'],
                                ],
                            ],
                        ],
                        'chemical_ladle' => [
                            'type' => 'OBJECT',
                            'description' => 'Chemical composition from Ladle Analysis (Division L)',
                            'properties' => [
                                'C' => ['type' => 'NUMBER'], 'Si' => ['type' => 'NUMBER'],
                                'Mn' => ['type' => 'NUMBER'], 'P' => ['type' => 'NUMBER'],
                                'S' => ['type' => 'NUMBER'], 'Cr' => ['type' => 'NUMBER'],
                                'Ni' => ['type' => 'NUMBER'], 'B' => ['type' => 'NUMBER'],
                                'Cu' => ['type' => 'NUMBER'], 'Mo' => ['type' => 'NUMBER'],
                                'Nb' => ['type' => 'NUMBER'], 'Ti' => ['type' => 'NUMBER'],
                                'V' => ['type' => 'NUMBER'], 'CEQ' => ['type' => 'NUMBER'],
                            ],
                        ],
                        'chemical_product' => [
                            'type' => 'OBJECT',
                            'description' => 'Chemical composition from Product Analysis (Division P)',
                            'properties' => [
                                'C' => ['type' => 'NUMBER'], 'Si' => ['type' => 'NUMBER'],
                                'Mn' => ['type' => 'NUMBER'], 'P' => ['type' => 'NUMBER'],
                                'S' => ['type' => 'NUMBER'], 'Cr' => ['type' => 'NUMBER'],
                                'Ni' => ['type' => 'NUMBER'], 'B' => ['type' => 'NUMBER'],
                                'Cu' => ['type' => 'NUMBER'], 'Mo' => ['type' => 'NUMBER'],
                                'Nb' => ['type' => 'NUMBER'], 'Ti' => ['type' => 'NUMBER'],
                                'V' => ['type' => 'NUMBER'], 'CEQ' => ['type' => 'NUMBER'],
                            ],
                        ],
                    ],
                ],
            ],
            'confidence_score' => ['type' => 'NUMBER', 'description' => 'Overall confidence score from 0 to 100'],
            'notes' => ['type' => 'STRING', 'description' => 'Any additional notes, warnings about unreadable fields, or special observations'],
        ],
    ];

    /**
     * Extract data from an MTC document using Gemini 2.5 Flash Vision API.
     *
     * @param string $filePath  Path relative to storage/app
     * @param string $mimeType  MIME type of the file
     * @return array  Extracted data or error
     */
    public function extract(string $filePath, string $mimeType): array
    {
        $company = \App\Models\Company::first();
        $aiSettings = $company->settings['ai'] ?? [];
        $driver = $aiSettings['ai_driver'] ?? 'gemini';

        $apiKey = null;
        $model = 'gemini-2.5-flash';
        $isOpenRouter = false;

        if ($driver === 'openrouter') {
            $apiKey = $aiSettings['openrouter_api_key'] ?? null;
            $model = $aiSettings['openrouter_model'] ?? 'google/gemini-2.5-flash';
            $isOpenRouter = true;
        } else {
            $apiKey = $aiSettings['gemini_api_key'] ?? config('services.gemini.key');
            $model = $aiSettings['gemini_model'] ?? 'gemini-2.5-flash';
        }

        // Force upgrading legacy models to gemini-2.5-flash for optimal vision quality
        if ($model === 'gemini-1.5-flash') {
            $model = 'gemini-2.5-flash';
        }

        if (!$apiKey) {
            return [
                'success' => false,
                'error' => 'AI Provider is not configured. Please set up your API Key in AI Configuration settings.',
            ];
        }

        try {
            // Read the file and encode to base64
            $fullPath = Storage::disk('local')->path($filePath);
            if (!file_exists($fullPath)) {
                return ['success' => false, 'error' => 'File not found: ' . $filePath];
            }

            $fileContent = file_get_contents($fullPath);
            $base64Data = base64_encode($fileContent);

            if ($isOpenRouter) {
                // OpenRouter (OpenAI Compatible) API call
                $payload = [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => $this->getExtractionPrompt(),
                                ],
                                [
                                    'type' => 'image_url',
                                    'image_url' => [
                                        'url' => "data:{$mimeType};base64,{$base64Data}",
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'temperature' => 0.1,
                    'response_format' => [
                        'type' => 'json_object',
                        'schema' => $this->responseSchema,
                    ],
                ];

                $response = Http::timeout(120)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $apiKey,
                    ])
                    ->post('https://openrouter.ai/api/v1/chat/completions', $payload);

                if (!$response->successful()) {
                    Log::error('MTC Extraction OpenRouter API Error', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    return [
                        'success' => false,
                        'error' => 'OpenRouter API error: ' . ($response->json('error.message') ?? $response->body()),
                    ];
                }

                $responseData = $response->json();
                $textContent = $responseData['choices'][0]['message']['content'] ?? null;
            } else {
                // Native Google Gemini API call
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
                
                $payload = [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                [
                                    'inlineData' => [
                                        'mimeType' => $mimeType,
                                        'data' => $base64Data,
                                    ],
                                ],
                                [
                                    'text' => $this->getExtractionPrompt(),
                                ],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.1,
                        'responseMimeType' => 'application/json',
                        'responseSchema' => $this->responseSchema,
                    ],
                ];

                $response = Http::timeout(120)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post($url, $payload);

                if (!$response->successful()) {
                    Log::error('MTC Extraction API Error', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    return [
                        'success' => false,
                        'error' => 'Gemini API error: ' . ($response->json('error.message') ?? $response->body()),
                    ];
                }

                $responseData = $response->json();
                $textContent = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? null;
            }

            if (!$textContent) {
                return [
                    'success' => false,
                    'error' => 'Empty response from Gemini. The document may not be readable.',
                ];
            }

            // Parse the JSON response
            $extractedData = json_decode($textContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('MTC Extraction JSON Parse Error', [
                    'raw_response' => $textContent,
                    'json_error' => json_last_error_msg(),
                ]);
                return [
                    'success' => false,
                    'error' => 'Failed to parse AI response as JSON.',
                    'raw_response' => $textContent,
                ];
            }

            return [
                'success' => true,
                'data' => $extractedData,
                'raw_response' => $extractedData,
            ];

        } catch (\Exception $e) {
            Log::error('MTC Extraction Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'error' => 'Extraction failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Build the extraction prompt for Gemini Vision.
     */
    protected function getExtractionPrompt(): string
    {
        return <<<'PROMPT'
You are an expert at reading Mill Test Certificates (MTC) from steel coil suppliers worldwide.
Your task is to extract ALL data from this MTC document into a structured JSON format.

IMPORTANT RULES:
1. Read the document carefully — it may be from any supplier (Krakatau Posco, Krakatau Steel, Nippon Steel, JFE, POSCO, CSC, Baosteel, TATA Steel, etc.)
2. The document language may be English, Japanese, Korean, Chinese, or mixed — extract all data regardless of language
3. For dates, always convert to YYYY-MM-DD format
4. For chemical composition, distinguish between Ladle Analysis (L) and Product Analysis (P)
5. For B (Boron), if the unit is ppm, convert to percentage (divide by 10000). If value seems like ppm (e.g. 1, 2, 3), keep as-is since MTC sometimes reports B in ppm directly
6. Extract ALL items/rows from the table — each product/coil number is a separate item
7. If a field is not present or unreadable, use null
8. Set confidence_score from 0-100 based on how confident you are in the overall extraction accuracy
9. In notes, mention any fields that were difficult to read or any assumptions made
10. For CEQ (Carbon Equivalent), calculate if not provided using: CEQ = C + Mn/6 + (Ni+Cu)/15 + (Cr+Mo+V)/5
11. Impact test data should include all readings (position 1, 2, 3 and Average)

Extract the data now.
PROMPT;
    }

    /**
     * Get supported MIME types for MTC document upload.
     */
    public static function getSupportedMimeTypes(): array
    {
        return [
            'application/pdf',
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/webp',
            'image/tiff',
        ];
    }

    /**
     * Get maximum file size in bytes (20MB).
     */
    public static function getMaxFileSize(): int
    {
        return 20 * 1024 * 1024;
    }
}
