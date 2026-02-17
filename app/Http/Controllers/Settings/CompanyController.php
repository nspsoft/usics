<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        ]);

        $company = Company::first();
        $settings = $company->settings ?? [];
        
        // Preserve existing structure but update AI settings
        $settings['ai'] = [
            'ai_driver' => $request->ai_driver,
            'gemini_api_key' => $request->gemini_api_key,
            'gemini_model' => $request->gemini_model,
            'ollama_url' => $request->ollama_url,
            'ollama_model' => $request->ollama_model,
        ];

        $company->settings = $settings;
        $company->save();

        if ($request->has('whatsapp_bot_instruction')) {
            AppSetting::set('whatsapp_bot_instruction', $request->whatsapp_bot_instruction, 'whatsapp', 'WhatsApp Bot Personality');
        }

        return redirect()->back()->with('success', 'AI Configuration updated successfully.');
    }
}
