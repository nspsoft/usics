<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Company;
use App\Services\GeminiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenRouterIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->company = Company::create([
            'code' => 'TEST-COMP',
            'name' => 'Test Company',
            'email' => 'test@comp.com',
            'settings' => [
                'ai' => [
                    'ai_driver' => 'gemini',
                    'gemini_api_key' => 'old_key',
                    'gemini_model' => 'gemini-1.5-flash',
                ]
            ]
        ]);
    }

    /**
     * Test updating AI settings to OpenRouter.
     */
    public function test_can_update_ai_settings_to_openrouter()
    {
        $response = $this->actingAs($this->user)
            ->post(route('settings.ai.update'), [
                'ai_driver' => 'openrouter',
                'openrouter_api_key' => 'sk-or-v1-test-key-12345',
                'openrouter_model' => 'deepseek/deepseek-chat',
                'whatsapp_bot_instruction' => 'Test CS Bot',
            ]);

        $response->assertRedirect();
        
        $this->company->refresh();
        $aiSettings = $this->company->settings['ai'];

        $this->assertEquals('openrouter', $aiSettings['ai_driver']);
        $this->assertEquals('sk-or-v1-test-key-12345', $aiSettings['openrouter_api_key']);
        $this->assertEquals('deepseek/deepseek-chat', $aiSettings['openrouter_model']);
    }

    /**
     * Test validation rules for openrouter input fields.
     */
    public function test_validation_requires_openrouter_fields_when_selected()
    {
        $response = $this->actingAs($this->user)
            ->post(route('settings.ai.update'), [
                'ai_driver' => 'openrouter',
                'openrouter_api_key' => '',
                'openrouter_model' => '',
            ]);

        $response->assertSessionHasErrors(['openrouter_api_key', 'openrouter_model']);
    }

    /**
     * Test that GeminiService correctly calls the OpenRouter API when the openrouter driver is active.
     */
    public function test_gemini_service_calls_openrouter_api_when_active()
    {
        // 1. Configure OpenRouter as active driver
        $this->company->update([
            'settings' => [
                'ai' => [
                    'ai_driver' => 'openrouter',
                    'openrouter_api_key' => 'sk-or-v1-mock-key',
                    'openrouter_model' => 'google/gemini-2.5-flash',
                ]
            ]
        ]);

        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => '{"intent": "greeting", "parameters": {}, "confidence": 0.95}'
                        ]
                    ]
                ]
            ], 200)
        ]);

        $service = new GeminiService();
        $result = $service->analyzeCustomerIntent('Halo selamat pagi');

        $this->assertEquals('greeting', $result['intent']);

        // Verify request structure
        Http::assertSent(function ($request) {
            return $request->url() === 'https://openrouter.ai/api/v1/chat/completions' &&
                $request->hasHeader('Authorization', 'Bearer sk-or-v1-mock-key') &&
                $request['model'] === 'google/gemini-2.5-flash' &&
                is_array($request['messages']) &&
                $request['messages'][0]['role'] === 'user' &&
                str_contains($request['messages'][0]['content'], 'Intent');
        });
    }

    /**
     * Test that non-supported features (like audio minutes extraction) warning-log and return null.
     */
    public function test_gemini_service_returns_null_for_audio_extraction_under_openrouter()
    {
        $this->company->update([
            'settings' => [
                'ai' => [
                    'ai_driver' => 'openrouter',
                    'openrouter_api_key' => 'sk-or-v1-mock-key',
                    'openrouter_model' => 'google/gemini-2.5-flash',
                ]
            ]
        ]);

        $service = new GeminiService();
        
        // This should return null directly without sending HTTP requests
        $result = $service->generateMeetingMinutesFromAudio('/path/to/mock.mp3', 'audio/mp3');

        $this->assertNull($result);
    }
}
