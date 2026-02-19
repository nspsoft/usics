<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    SparklesIcon, 
    KeyIcon, 
    EyeIcon, 
    EyeSlashIcon,
    CheckBadgeIcon,
    ArrowLeftIcon,
    ExclamationTriangleIcon,
    ChatBubbleLeftRightIcon,
    CpuChipIcon,
    InformationCircleIcon,
    GlobeAltIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    settings: Object,
    email_settings: Object,
    whatsapp_bot_instruction: String
});

const showKey = ref(false);

const form = useForm({
    ai_driver: props.settings?.ai_driver || 'gemini',
    gemini_api_key: props.settings?.gemini_api_key || '',
    gemini_model: props.settings?.gemini_model || 'gemini-1.5-flash',
    ollama_url: props.settings?.ollama_url || 'http://localhost:11434',
    ollama_model: props.settings?.ollama_model || 'llama3',
    whatsapp_bot_instruction: props.whatsapp_bot_instruction || '',
    email_settings: {
        imap_host: props.email_settings?.imap_host || '',
        imap_port: props.email_settings?.imap_port || '993',
        imap_encryption: props.email_settings?.imap_encryption || 'ssl',
        imap_username: props.email_settings?.imap_username || '',
        imap_password: props.email_settings?.imap_password || '',
        
        // SMTP Settings
        smtp_host: props.email_settings?.smtp_host || '',
        smtp_port: props.email_settings?.smtp_port || '587',
        smtp_encryption: props.email_settings?.smtp_encryption || 'tls',
        smtp_username: props.email_settings?.smtp_username || '',
        smtp_password: props.email_settings?.smtp_password || '',
        from_address: props.email_settings?.from_address || '',
        from_name: props.email_settings?.from_name || '',
    }
});

const submit = () => {
    form.post(route('settings.ai.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Success flash handled by controller
        }
    });
};

const geminiModels = [
    { label: 'Gemini 2.5 Flash (Latest - Recommended)', value: 'gemini-2.5-flash' },
    { label: 'Gemini 2.5 Pro (Most Intelligent)', value: 'gemini-2.5-pro' },
    { label: 'Gemini 2.0 Flash (Fast)', value: 'gemini-2.0-flash' },
    { label: 'Gemini 1.5 Flash (Stable)', value: 'gemini-1.5-flash' },
];

const ollamaModels = [
    { label: 'Qwen 3 VL (8B)', value: 'qwen3-vl:8b' },
    { label: 'Qwen 2.5 (7B)', value: 'qwen2.5:7b' },
    { label: 'Gemma 3 (4B)', value: 'gemma3:4b' },
];

const aiDrivers = [
    { label: 'Google Gemini (Cloud)', value: 'gemini' },
    { label: 'Ollama (Local)', value: 'ollama' },
];
</script>

<template>
    <Head title="AI Configuration" />
    
    <AppLayout title="AI Configuration">
        <div class="max-w-full px-4 sm:px-6 lg:px-8 mx-auto space-y-6 pb-20">
            <!-- Header section -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <a 
                        :href="route('settings.index')"
                        class="p-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-slate-900 dark:hover:text-white transition-colors"
                    >
                        <ArrowLeftIcon class="h-5 w-5" />
                    </a>
                    <div>
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white uppercase tracking-tight">AI Configuration</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Manage Google Gemini AI engine settings for ERP automation</p>
                    </div>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-500 animate-pulse">
                    <SparklesIcon class="h-7 w-7" />
                 </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- AI Engine Settings -->
                <div class="glass-card rounded-2xl p-8 border border-white/5 shadow-2xl">
                    <div class="flex items-center gap-3 text-blue-400 mb-8 border-b border-slate-200 dark:border-slate-800 pb-4">
                        <CpuChipIcon class="h-6 w-6" />
                        <h4 class="text-sm font-black uppercase tracking-widest">AI Engine Settings</h4>
                    </div>

                    <div class="space-y-8">
                        <!-- DRIVER SELECTION -->
                        <div class="space-y-2">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1 flex items-center gap-2">
                                <CpuChipIcon class="h-3 w-3" />
                                AI Provider
                            </label>
                            <select v-model="form.ai_driver" class="form-input appearance-none bg-inherit">
                                <option v-for="driver in aiDrivers" :key="driver.value" :value="driver.value">
                                    {{ driver.label }}
                                </option>
                            </select>
                        </div>

                        <!-- GEMINI SETTINGS -->
                        <div v-if="form.ai_driver === 'gemini'" class="space-y-8 animate-fade-in-up">
                            <!-- API KEY -->
                            <div class="space-y-2">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1 flex items-center gap-2">
                                    <KeyIcon class="h-3 w-3" />
                                    Gemini API Key
                                </label>
                                <div class="relative group">
                                    <input 
                                        v-model="form.gemini_api_key" 
                                        :type="showKey ? 'text' : 'password'" 
                                        class="form-input pr-12" 
                                        placeholder="Enter your Google AI Studio API Key..." 
                                        required 
                                    />
                                    <button 
                                        type="button"
                                        @click="showKey = !showKey"
                                        class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 hover:text-blue-400 transition-colors"
                                    >
                                        <EyeIcon v-if="!showKey" class="h-5 w-5" />
                                        <EyeSlashIcon v-else class="h-5 w-5" />
                                    </button>
                                </div>
                                <p class="text-[10px] text-slate-500 mt-2 px-1">
                                    Get your API key from <a href="https://aistudio.google.com/app/apikey" target="_blank" class="text-blue-400 hover:underline">Google AI Studio</a>.
                                </p>
                            </div>

                            <!-- MODEL SELECTION -->
                            <div class="space-y-2">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1 flex items-center gap-2">
                                    <CpuChipIcon class="h-3 w-3" />
                                    Model Name
                                </label>
                                <select v-model="form.gemini_model" class="form-input appearance-none bg-inherit">
                                    <option v-for="model in geminiModels" :key="model.value" :value="model.value">
                                        {{ model.label }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- OLLAMA SETTINGS -->
                        <div v-if="form.ai_driver === 'ollama'" class="space-y-8 animate-fade-in-up">
                            <!-- SERVER URL -->
                            <div class="space-y-2">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1 flex items-center gap-2">
                                    <GlobeAltIcon class="h-3 w-3" />
                                    Ollama Server URL
                                </label>
                                <input 
                                    v-model="form.ollama_url" 
                                    type="text" 
                                    class="form-input" 
                                    placeholder="http://localhost:11434" 
                                    required 
                                />
                                <p class="text-[10px] text-slate-500 mt-2 px-1">
                                    Ensure your Ollama instance is running and accessible. Default: <code>http://localhost:11434</code>
                                </p>
                            </div>

                            <!-- MODEL NAME -->
                            <div class="space-y-2">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1 flex items-center gap-2">
                                    <CpuChipIcon class="h-3 w-3" />
                                    Ollama Model Name
                                </label>
                                <div class="relative">
                                    <input 
                                        v-model="form.ollama_model" 
                                        type="text" 
                                        list="ollama-models"
                                        class="form-input" 
                                        placeholder="e.g. llama3, mistral, qwen2" 
                                        required 
                                    />
                                    <datalist id="ollama-models">
                                        <option v-for="model in ollamaModels" :key="model.value" :value="model.value">
                                            {{ model.label }}
                                        </option>
                                    </datalist>
                                </div>
                                <p class="text-[10px] text-slate-500 mt-2 px-1">
                                    Select a recommended model or type your own (must be pulled locally via <code>ollama pull</code>).
                                </p>
                            </div>
                        </div>

                        <!-- EMAIL CONNECTIVITY SETTINGS -->
                        <div class="pt-8 border-t border-slate-200 dark:border-slate-800 space-y-6">
                            <div class="flex items-center gap-3 text-indigo-400">
                                <GlobeAltIcon class="h-6 w-6" />
                                <h4 class="text-sm font-black uppercase tracking-widest">Email (IMAP) Connectivity</h4>
                            </div>

                            <div class="grid md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">IMAP Host</label>
                                    <input v-model="form.email_settings.imap_host" type="text" class="form-input" placeholder="imap.gmail.com" />
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Port</label>
                                        <input v-model="form.email_settings.imap_port" type="text" class="form-input" placeholder="993" />
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Encryption</label>
                                        <select v-model="form.email_settings.imap_encryption" class="form-input bg-inherit">
                                            <option value="ssl">SSL</option>
                                            <option value="tls">TLS</option>
                                            <option value="null">None</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Username</label>
                                    <input v-model="form.email_settings.imap_username" type="text" class="form-input" placeholder="email@example.com" />
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Password</label>
                                    <input v-model="form.email_settings.imap_password" type="password" class="form-input" placeholder="••••••••" />
                                </div>
                            </div>
                            <p class="text-[10px] text-slate-500 mt-2 px-1 italic">
                                * Digunakan oleh AI Inbox untuk membaca email masuk secara otomatis.
                            </p>
                        </div>

                        <!-- SMTP SETTINGS -->
                        <div class="pt-8 border-t border-slate-200 dark:border-slate-800 space-y-6">
                            <div class="flex items-center gap-3 text-cyan-400">
                                <GlobeAltIcon class="h-6 w-6" />
                                <h4 class="text-sm font-black uppercase tracking-widest">Email Sending (SMTP)</h4>
                            </div>

                            <div class="grid md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">SMTP Host</label>
                                    <input v-model="form.email_settings.smtp_host" type="text" class="form-input" placeholder="smtp.gmail.com" />
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Port</label>
                                        <input v-model="form.email_settings.smtp_port" type="text" class="form-input" placeholder="587" />
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Encryption</label>
                                        <select v-model="form.email_settings.smtp_encryption" class="form-input bg-inherit">
                                            <option value="tls">TLS</option>
                                            <option value="ssl">SSL</option>
                                            <option value="null">None</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Username</label>
                                    <input v-model="form.email_settings.smtp_username" type="text" class="form-input" placeholder="email@example.com" />
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Password</label>
                                    <input v-model="form.email_settings.smtp_password" type="password" class="form-input" placeholder="••••••••" />
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">From Address</label>
                                    <input v-model="form.email_settings.from_address" type="email" class="form-input" placeholder="admin@jidoka.co.id" />
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">From Name</label>
                                    <input v-model="form.email_settings.from_name" type="text" class="form-input" placeholder="Jidoka Admin" />
                                </div>
                            </div>
                            <p class="text-[10px] text-slate-500 mt-2 px-1 italic">
                                * Digunakan untuk mengirim email balasan dari sistem.
                            </p>
                        </div>

                        <!-- WHATSAPP BOT PERSONALITY (INTEGRATED) -->
                        <div class="pt-8 border-t border-slate-200 dark:border-slate-800 space-y-6">
                            <div class="flex items-center gap-3 text-emerald-400">
                                <ChatBubbleLeftRightIcon class="h-6 w-6" />
                                <h4 class="text-sm font-black uppercase tracking-widest">WhatsApp Bot Personality</h4>
                            </div>

                            <div class="space-y-4">
                                <div class="bg-emerald-500/5 rounded-2xl p-4 border border-emerald-500/10 flex gap-3">
                                    <InformationCircleIcon class="h-5 w-5 text-emerald-400 shrink-0 mt-0.5" />
                                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                                        Instruksi ini akan menjadi dasar bagi AI (Gemini) dalam merespon setiap pesan WhatsApp. 
                                        Anda bisa menentukan nada bicara, gaya bahasa, dan informasi penting.
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1 flex items-center gap-2">
                                        <SparklesIcon class="h-3 w-3" />
                                        Custom Bot Instructions / Prompt
                                    </label>
                                    <textarea 
                                        v-model="form.whatsapp_bot_instruction" 
                                        class="form-input min-h-[150px] resize-none focus:ring-emerald-500/50 focus:border-emerald-500" 
                                        placeholder="Contoh: Gunakan bahasa Indonesia yang santai tapi sopan..."
                                    ></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Guidance Info -->
                <div class="bg-blue-500/5 rounded-2xl p-6 border border-blue-500/10 flex gap-4">
                    <InformationCircleIcon class="h-6 w-6 text-blue-400 shrink-0" />
                    <div class="space-y-2">
                        <h5 class="text-sm font-bold text-slate-900 dark:text-white">Why use Gemini AI?</h5>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                            JICOS uses Gemini to automate repetitive tasks like Purchase Order extraction, document OCR, and intelligent data matching. 
                            <strong class="text-blue-400">Gemini 2.0 Flash</strong> is highly recommended for its perfect balance of speed and visual understanding.
                        </p>
                    </div>
                </div>

                <!-- How to Get API Key -->
                <div class="bg-emerald-500/5 rounded-2xl p-6 border border-emerald-500/10">
                    <h5 class="text-sm font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                        <KeyIcon class="h-5 w-5 text-emerald-500" />
                        Cara Mendapatkan API Key (Gratis)
                    </h5>
                    <ol class="space-y-3 text-xs text-slate-500 dark:text-slate-400">
                        <li class="flex gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-emerald-500/20 text-emerald-500 font-bold flex items-center justify-center text-[10px]">1</span>
                            <span>Buka <a href="https://aistudio.google.com/app/apikey" target="_blank" class="text-blue-400 hover:underline font-semibold">Google AI Studio</a> dan login dengan akun Google</span>
                        </li>
                        <li class="flex gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-emerald-500/20 text-emerald-500 font-bold flex items-center justify-center text-[10px]">2</span>
                            <span>Klik <strong>"Create API Key"</strong> dan pilih <strong>"Create API key in new project"</strong></span>
                        </li>
                        <li class="flex gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-emerald-500/20 text-emerald-500 font-bold flex items-center justify-center text-[10px]">3</span>
                            <span>Copy API Key yang muncul (format: <code class="bg-slate-800 px-2 py-0.5 rounded text-emerald-400">AIzaSy...</code>) dan paste di field di atas</span>
                        </li>
                    </ol>
                </div>

                <!-- Free Tier Info -->
                <div class="grid md:grid-cols-2 gap-4">
                    <div class="bg-amber-500/5 rounded-2xl p-6 border border-amber-500/10">
                        <h5 class="text-sm font-bold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
                            <SparklesIcon class="h-5 w-5 text-amber-500" />
                            Free Tier (Tanpa Billing)
                        </h5>
                        <ul class="space-y-2 text-xs text-slate-500 dark:text-slate-400">
                            <li class="flex items-center gap-2">
                                <ExclamationTriangleIcon class="h-4 w-4 text-amber-500" />
                                <span><strong>5 requests/menit</strong> - Limit per menit</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <ExclamationTriangleIcon class="h-4 w-4 text-amber-500" />
                                <span><strong>20 requests/hari</strong> - Limit harian (RPD)</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <CheckBadgeIcon class="h-4 w-4 text-emerald-500" />
                                <span><strong>Gratis</strong> - Tidak perlu kartu kredit</span>
                            </li>
                        </ul>
                        <p class="mt-3 text-[10px] text-amber-600 font-medium">
                            ⚠️ Free tier terbatas! Untuk production, gunakan Paid Tier.
                        </p>
                    </div>

                    <div class="bg-purple-500/5 rounded-2xl p-6 border border-purple-500/10">
                        <h5 class="text-sm font-bold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
                            <CpuChipIcon class="h-5 w-5 text-purple-500" />
                            Paid Tier (Dengan Billing)
                        </h5>
                        <ul class="space-y-2 text-xs text-slate-500 dark:text-slate-400">
                            <li class="flex items-center gap-2">
                                <CheckBadgeIcon class="h-4 w-4 text-purple-500" />
                                <span><strong>2.000 requests/menit</strong> - Untuk high volume</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <CheckBadgeIcon class="h-4 w-4 text-purple-500" />
                                <span><strong>$300 free credits</strong> - Untuk user baru Google Cloud</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <CheckBadgeIcon class="h-4 w-4 text-purple-500" />
                                <span><strong>Kartu Debit Indonesia</strong> - BCA, Mandiri (logo Visa/MC)</span>
                            </li>
                        </ul>
                        <p class="mt-3 text-[10px] text-purple-600 font-medium">
                            💰 1000 PO/bulan ≈ Rp 15.000 - 75.000 (sangat murah!)
                        </p>
                    </div>
                </div>

                <!-- Action Button -->
                <div class="flex justify-end pt-4">
                    <button 
                        type="submit" 
                        :disabled="form.processing"
                        class="inline-flex items-center gap-3 px-10 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-50 text-white rounded-2xl font-black uppercase tracking-widest text-xs shadow-2xl shadow-blue-500/30 transition-all active:scale-95 disabled:opacity-50 group overflow-hidden relative"
                    >
                        <span class="relative z-10 flex items-center gap-2">
                            <CheckBadgeIcon class="h-5 w-5 transition-transform group-hover:scale-125" />
                            Update AI Configuration
                        </span>
                        <div class="absolute inset-0 bg-white/20 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700 skew-x-[-20deg]"></div>
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<style scoped>
@reference "../../../css/app.css";

.form-input {
    @apply block w-full px-5 py-4 bg-slate-50 dark:bg-slate-900/40 border border-slate-200 dark:border-white/5 rounded-2xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none transition-all duration-300;
}
select.form-input {
    @apply cursor-pointer;
}

.glass-card {
    background: rgba(255, 255, 255, 0.02);
    backdrop-filter: blur(20px);
}
</style>
