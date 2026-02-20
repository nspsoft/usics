<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class CompanyController extends Controller
{
    public function index()
    {
        $company = Company::first();
        return Inertia::render('Settings/CompanyProfile', [
            'company' => $company
        ]);
    }

    public function update(Request $request)
    {
        $company = Company::first() ?? new Company();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
            'tax_id' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'currency' => 'nullable|string|max:3',
            'timezone' => 'nullable|string|max:255',
            'logo_file' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('logo_file')) {
            if ($company->logo && str_starts_with($company->logo, '/storage/')) {
                $oldPath = str_replace('/storage/', '', $company->logo);
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('logo_file')->store('logos', 'public');
            $validated['logo'] = '/storage/' . $path;
        }

        $company->fill($validated);
        
        if (!$company->code) {
            $company->code = 'COMP-' . strtoupper(substr(uniqid(), -4));
        }

        $company->save();

        return redirect()->back()->with('success', 'Company profile updated successfully.');
    }

    public function aiSettings()
    {
        $company = Company::first();
        $defaultSettings = [
            'ai_driver' => 'gemini',
            'gemini_api_key' => '',
            'gemini_model' => 'gemini-2.5-flash', // Updated default
            'ollama_url' => 'http://localhost:11434',
            'ollama_model' => 'llama3',
        ];

        $defaultInstruction = "Anda adalah Customer Service Jidoka AI yang ramah. Tugas Anda adalah melayani Customer dan Staff PT JIDOKA dengan sopan, ceria, dan membantu.";

        return Inertia::render('Settings/AISettings', [
            'settings' => array_merge($defaultSettings, $company->settings['ai'] ?? []),
            'email_settings' => $company->settings['email'] ?? [
                'imap_host' => '',
                'imap_port' => '993',
                'imap_encryption' => 'ssl',
                'imap_username' => '',
                'imap_password' => '',
                'smtp_host' => '',
                'smtp_port' => '587',
                'smtp_encryption' => 'tls',
                'smtp_username' => '',
                'smtp_password' => '',
                'from_address' => '',
                'from_name' => '',
            ],
            'emeterai_settings' => $company->settings['emeterai'] ?? [
                'enabled' => false,
                'client_id' => '',
                'secret_key' => '',
            ],
            'whatsapp_bot_instruction' => (string) AppSetting::get('whatsapp_bot_instruction', $defaultInstruction)
        ]);
    }

    public function updateAiSettings(Request $request)
    {
        $request->validate([
            'ai_driver' => 'required|in:gemini,ollama',
            'gemini_api_key' => 'nullable|required_if:ai_driver,gemini|string',
            'gemini_model' => 'nullable|required_if:ai_driver,gemini|string',
            'ollama_url' => 'nullable|required_if:ai_driver,ollama|url',
            'ollama_model' => 'nullable|required_if:ai_driver,ollama|string',
            'whatsapp_bot_instruction' => 'nullable|string',
            'email_settings' => 'nullable|array',
            'emeterai_settings' => 'nullable|array',
        ]);

        $company = Company::first();
        $settings = $company->settings ?? [];
        
        Log::info('Updating AI Settings. Request has email_settings: ' . ($request->has('email_settings') ? 'Yes' : 'No'));

        // Preserve existing structure but update AI settings
        $settings['ai'] = [
            'ai_driver' => $request->ai_driver,
            'gemini_api_key' => $request->gemini_api_key,
            'gemini_model' => $request->gemini_model,
            'ollama_url' => $request->ollama_url,
            'ollama_model' => $request->ollama_model,
        ];

        // Handle Email Settings if present in request (Fix for persistence)
        if ($request->has('email_settings')) {
            $emailSettings = $request->email_settings;
            // Ensure no null values for critical fields
            $settings['email'] = [
                'imap_host' => $emailSettings['imap_host'] ?? '',
                'imap_port' => $emailSettings['imap_port'] ?? '993',
                'imap_encryption' => $emailSettings['imap_encryption'] ?? 'ssl',
                'imap_username' => $emailSettings['imap_username'] ?? '',
                'imap_password' => $emailSettings['imap_password'] ?? '',
                'smtp_host' => $emailSettings['smtp_host'] ?? '',
                'smtp_port' => $emailSettings['smtp_port'] ?? '587',
                'smtp_encryption' => $emailSettings['smtp_encryption'] ?? 'tls',
                'smtp_username' => $emailSettings['smtp_username'] ?? '',
                'smtp_password' => $emailSettings['smtp_password'] ?? '',
                'from_address' => $emailSettings['from_address'] ?? '',
                'from_name' => $emailSettings['from_name'] ?? '',
            ];
        }

        // Handle e-Meterai Settings
        if ($request->has('emeterai_settings')) {
            $emeteraiSettings = $request->emeterai_settings;
            $settings['emeterai'] = [
                'enabled' => (bool) ($emeteraiSettings['enabled'] ?? false),
                'client_id' => $emeteraiSettings['client_id'] ?? '',
                'secret_key' => $emeteraiSettings['secret_key'] ?? '',
            ];
        }

        $company->settings = $settings;
        $company->save();

        if ($request->has('whatsapp_bot_instruction')) {
            AppSetting::set('whatsapp_bot_instruction', $request->whatsapp_bot_instruction, 'whatsapp', 'WhatsApp Bot Personality');
        }

        return redirect()->back()->with('success', 'AI Configuration updated successfully.');
    }
}
