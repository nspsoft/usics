<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    ChatBubbleLeftRightIcon, 
    KeyIcon, 
    EyeIcon, 
    EyeSlashIcon,
    CheckBadgeIcon,
    DevicePhoneMobileIcon,
    InformationCircleIcon,
    ArrowLeftIcon,
    GlobeAltIcon,
    ClipboardDocumentIcon,
    StarIcon,
    PaperAirplaneIcon,
    SignalIcon,
    BookOpenIcon,
    SparklesIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    settings: Object
});

const page = usePage();
const showToken = ref(false);
const copied = ref(false);

// Test connection state
const testPhone = ref('');
const testLoading = ref(false);
const testResult = ref(null);

const providers = [
    { 
        id: 'fonnte', 
        name: 'Fonnte', 
        price: 'Rp 50.000/bulan',
        website: 'https://fonnte.com',
        recommended: false,
        color: 'emerald'
    },
    { 
        id: 'wablas', 
        name: 'Wablas ⭐', 
        price: 'Rp 50.000/bulan',
        website: 'https://wablas.com',
        recommended: true,
        color: 'blue'
    },
];

const form = useForm({
    whatsapp_provider: props.settings?.whatsapp_provider || 'fonnte',
    fonnte_api_token: props.settings?.fonnte_api_token || '',
    fonnte_device: props.settings?.fonnte_device || '',
    wablas_api_token: props.settings?.wablas_api_token || '',
    wablas_device: props.settings?.wablas_device || '',
    wablas_server_url: props.settings?.wablas_server_url || 'https://pati.wablas.com',
    whatsapp_bot_instruction: props.settings?.whatsapp_bot_instruction || '',
});

const selectedProvider = computed(() => providers.find(p => p.id === form.whatsapp_provider));

const submit = () => {
    form.post(route('settings.whatsapp.update'), {
        preserveScroll: true,
    });
};

const webhookUrl = computed(() => {
    const base = page.props.appUrl || window.location.origin;
    return `${base}/api/whatsapp/webhook`;
});

const copyWebhook = () => {
    navigator.clipboard.writeText(webhookUrl.value);
    copied.value = true;
    setTimeout(() => copied.value = false, 2000);
};

// Test connection function
const testConnection = async () => {
    if (!testPhone.value) {
        testResult.value = { success: false, message: 'Masukkan nomor WhatsApp tujuan!' };
        return;
    }
    
    testLoading.value = true;
    testResult.value = null;
    
    try {
        const token = usePage().props.csrf_token;
        
        const response = await axios.post(route('settings.whatsapp.test'), {
            phone: testPhone.value
        }, {
            headers: {
                'X-CSRF-TOKEN': token
            }
        });
        
        testResult.value = response.data;
    } catch (error) {
        testResult.value = { 
            success: false, 
            message: error.response?.data?.message || 'Terjadi error: ' + error.message 
        };
    } finally {
        testLoading.value = false;
    }
};
</script>

<template>
    <Head title="WhatsApp Configuration" />
    
    <AppLayout title="WhatsApp Configuration">
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
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white uppercase tracking-tight">WhatsApp Bot Configuration</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Configure WhatsApp gateway for customer service bot</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <a href="/whatsapp_guide.html" target="_blank" class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white rounded-xl shadow-lg shadow-indigo-500/20 text-xs font-bold uppercase tracking-widest transition-all hover:scale-105">
                        <BookOpenIcon class="h-4 w-4" />
                        Panduan & Demo
                    </a>
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-500">
                        <ChatBubbleLeftRightIcon class="h-7 w-7" />
                    </div>
                 </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Provider Selection -->
                <div class="bg-white dark:bg-white/5 rounded-2xl p-8 border border-slate-200 dark:border-white/5 shadow-lg dark:shadow-2xl">
                    <div class="flex items-center gap-3 text-purple-600 dark:text-purple-400 mb-6 border-b border-slate-200 dark:border-slate-800 pb-4">
                        <StarIcon class="h-6 w-6" />
                        <h4 class="text-sm font-black uppercase tracking-widest">Pilih Provider Gateway</h4>
                    </div>

                    <div class="grid md:grid-cols-2 gap-4">
                        <button 
                            v-for="provider in providers" 
                            :key="provider.id"
                            type="button"
                            @click="form.whatsapp_provider = provider.id"
                            class="relative p-6 rounded-2xl border-2 transition-all text-left group"
                            :class="form.whatsapp_provider === provider.id 
                                ? `border-${provider.color}-500 bg-${provider.color}-50 dark:bg-${provider.color}-500/10 shadow-lg shadow-${provider.color}-500/20` 
                                : 'border-slate-300 dark:border-slate-700 hover:border-slate-400 dark:hover:border-slate-600 bg-slate-50 dark:bg-slate-900/40'"
                        >
                            <!-- Recommended Badge -->
                            <div v-if="provider.recommended" class="absolute -top-2 -right-2 bg-yellow-500 text-yellow-900 text-[10px] font-black px-2 py-0.5 rounded-full">
                                RECOMMENDED
                            </div>

                            <div class="flex items-center gap-3 mb-3">
                                <div 
                                    class="w-4 h-4 rounded-full border-2 flex items-center justify-center transition-colors"
                                    :class="form.whatsapp_provider === provider.id 
                                        ? `border-${provider.color}-500` 
                                        : 'border-slate-400 dark:border-slate-600'"
                                >
                                    <div 
                                        v-if="form.whatsapp_provider === provider.id" 
                                        class="w-2 h-2 rounded-full"
                                        :class="`bg-${provider.color}-500`"
                                    ></div>
                                </div>
                                <h5 class="text-lg font-bold text-slate-900 dark:text-white">{{ provider.name }}</h5>
                            </div>
                            <p class="text-sm text-slate-600 dark:text-slate-400">{{ provider.price }}</p>
                            <a :href="provider.website" target="_blank" class="text-xs text-blue-400 hover:underline mt-1 inline-block">
                                {{ provider.website }}
                            </a>
                        </button>
                    </div>
                </div>

                <!-- Fonnte Settings -->
                <div v-if="form.whatsapp_provider === 'fonnte'" class="bg-white dark:bg-white/5 rounded-2xl p-8 border border-slate-200 dark:border-white/5 shadow-lg dark:shadow-2xl">
                    <div class="flex items-center gap-3 text-emerald-600 dark:text-emerald-400 mb-8 border-b border-slate-200 dark:border-slate-800 pb-4">
                        <DevicePhoneMobileIcon class="h-6 w-6" />
                        <h4 class="text-sm font-black uppercase tracking-widest">Fonnte Gateway Settings</h4>
                    </div>

                    <div class="space-y-8">
                        <div class="space-y-2">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1 flex items-center gap-2">
                                <KeyIcon class="h-3 w-3" />
                                Fonnte API Token
                            </label>
                            <div class="relative group">
                                <input 
                                    v-model="form.fonnte_api_token" 
                                    :type="showToken ? 'text' : 'password'" 
                                    class="form-input pr-12" 
                                    placeholder="Enter your Fonnte API Token..." 
                                />
                                <button 
                                    type="button"
                                    @click="showToken = !showToken"
                                    class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 hover:text-emerald-400 transition-colors"
                                >
                                    <EyeIcon v-if="!showToken" class="h-5 w-5" />
                                    <EyeSlashIcon v-else class="h-5 w-5" />
                                </button>
                            </div>
                            <p class="text-[10px] text-slate-500 mt-2 px-1">
                                Get your API token from <a href="https://fonnte.com" target="_blank" class="text-emerald-400 hover:underline">Fonnte Dashboard</a> → Device → API.
                            </p>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1 flex items-center gap-2">
                                <DevicePhoneMobileIcon class="h-3 w-3" />
                                Device ID / Phone Number
                            </label>
                            <input 
                                v-model="form.fonnte_device" 
                                type="text" 
                                class="form-input" 
                                placeholder="e.g. 6281234567890" 
                            />
                        </div>
                    </div>
                </div>

                <!-- Wablas Settings -->
                <div v-if="form.whatsapp_provider === 'wablas'" class="bg-white dark:bg-white/5 rounded-2xl p-8 border border-slate-200 dark:border-white/5 shadow-lg dark:shadow-2xl">
                    <div class="flex items-center gap-3 text-blue-600 dark:text-blue-400 mb-8 border-b border-slate-200 dark:border-slate-800 pb-4">
                        <DevicePhoneMobileIcon class="h-6 w-6" />
                        <h4 class="text-sm font-black uppercase tracking-widest">Wablas Gateway Settings ⭐</h4>
                    </div>

                    <div class="space-y-8">
                        <div class="space-y-2">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1 flex items-center gap-2">
                                <KeyIcon class="h-3 w-3" />
                                Wablas API Token
                            </label>
                            <div class="relative group">
                                <input 
                                    v-model="form.wablas_api_token" 
                                    :type="showToken ? 'text' : 'password'" 
                                    class="form-input pr-12" 
                                    placeholder="Enter your Wablas API Token..." 
                                />
                                <button 
                                    type="button"
                                    @click="showToken = !showToken"
                                    class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 hover:text-blue-400 transition-colors"
                                >
                                    <EyeIcon v-if="!showToken" class="h-5 w-5" />
                                    <EyeSlashIcon v-else class="h-5 w-5" />
                                </button>
                            </div>
                            <p class="text-[10px] text-slate-500 mt-2 px-1">
                                Get your API token from <a href="https://wablas.com" target="_blank" class="text-blue-400 hover:underline">Wablas Dashboard</a> → API Key.
                            </p>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1 flex items-center gap-2">
                                <DevicePhoneMobileIcon class="h-3 w-3" />
                                Device / Phone Number
                            </label>
                            <input 
                                v-model="form.wablas_device" 
                                type="text" 
                                class="form-input" 
                                placeholder="e.g. 6281234567890" 
                            />
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1 flex items-center gap-2">
                                <GlobeAltIcon class="h-3 w-3" />
                                Wablas Server URL
                            </label>
                            <input 
                                v-model="form.wablas_server_url" 
                                type="text" 
                                class="form-input" 
                                placeholder="e.g. https://pati.wablas.com" 
                            />
                            <p class="text-[10px] text-slate-500 mt-2 px-1">
                                Default: <code>https://pati.wablas.com</code>. Check your Wablas dashboard for the correct domain.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="glass-card rounded-2xl p-8 border border-white/5 shadow-2xl">
            <div class="flex items-center gap-3 text-emerald-400 mb-8 border-b border-slate-200 dark:border-slate-800 pb-4">
                <SparklesIcon class="h-6 w-6" />
                <h4 class="text-sm font-black uppercase tracking-widest">WhatsApp Bot Personality</h4>
            </div>

            <div class="space-y-6">
                <div class="space-y-4">
                    <div class="bg-emerald-500/5 rounded-2xl p-4 border border-emerald-500/10 flex gap-3">
                        <InformationCircleIcon class="h-5 w-5 text-emerald-400 shrink-0 mt-0.5" />
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                            Instruksi ini akan menjadi dasar bagi AI (Gemini) dalam merespon setiap pesan WhatsApp. 
                            Anda bisa menentukan nada bicara, gaya bahasa (formal/santai), dan informasi penting yang harus selalu disampaikan.
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1 flex items-center gap-2">
                            <BookOpenIcon class="h-3 w-3" />
                            Custom Bot Instructions / Prompt
                        </label>
                        <textarea 
                            v-model="form.whatsapp_bot_instruction" 
                            class="form-input min-h-[120px] resize-none" 
                            placeholder="Contoh: Gunakan bahasa Indonesia yang ramah dan santai. Selalu sapa dengan 'Halo kak'. Jika pelanggan bertanya tentang stok pipa, arahkan untuk cek status pesanan..."
                        ></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Webhook URL Info -->
                <div class="bg-white dark:bg-white/5 rounded-2xl p-8 border border-slate-200 dark:border-white/5 shadow-lg dark:shadow-2xl">
                    <div class="flex items-center gap-3 text-cyan-600 dark:text-cyan-400 mb-6 border-b border-slate-200 dark:border-slate-800 pb-4">
                        <GlobeAltIcon class="h-6 w-6" />
                        <h4 class="text-sm font-black uppercase tracking-widest">Webhook Configuration</h4>
                    </div>

                    <div class="space-y-4">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">
                            Your Webhook URL (Copy this to {{ selectedProvider?.name || 'Provider' }} Dashboard)
                        </label>
                        <div class="flex gap-2">
                            <input 
                                :value="webhookUrl" 
                                type="text" 
                                class="form-input flex-1 font-mono text-sm" 
                                readonly 
                            />
                            <button 
                                type="button"
                                @click="copyWebhook"
                                class="px-4 py-3 bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl flex items-center gap-2 transition-colors"
                            >
                                <ClipboardDocumentIcon class="h-5 w-5" />
                                <span class="text-xs font-bold">{{ copied ? 'Copied!' : 'Copy' }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Test Connection -->
                <div class="bg-white dark:bg-white/5 rounded-2xl p-8 border border-slate-200 dark:border-white/5 shadow-lg dark:shadow-2xl">
                    <div class="flex items-center gap-3 text-orange-600 dark:text-orange-400 mb-6 border-b border-slate-200 dark:border-slate-800 pb-4">
                        <SignalIcon class="h-6 w-6" />
                        <h4 class="text-sm font-black uppercase tracking-widest">🔌 Test Connection</h4>
                    </div>

                    <div class="space-y-4">
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Kirim pesan test ke nomor WhatsApp untuk memverifikasi koneksi. Pastikan Anda sudah <strong>Save Configuration</strong> terlebih dahulu!
                        </p>
                        
                        <div class="flex gap-2">
                            <input 
                                v-model="testPhone" 
                                type="text" 
                                class="form-input flex-1" 
                                placeholder="Masukkan nomor WA tujuan (e.g. 6281234567890)" 
                            />
                            <button 
                                type="button"
                                @click="testConnection"
                                :disabled="testLoading"
                                class="px-6 py-3 bg-orange-600 hover:bg-orange-500 disabled:bg-slate-600 text-white rounded-xl flex items-center gap-2 transition-colors"
                            >
                                <PaperAirplaneIcon v-if="!testLoading" class="h-5 w-5" />
                                <svg v-else class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span class="text-xs font-bold">{{ testLoading ? 'Sending...' : 'Send Test' }}</span>
                            </button>
                        </div>

                        <!-- Result Message -->
                        <div v-if="testResult" 
                            class="p-4 rounded-xl text-sm font-medium"
                            :class="testResult.success ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20'"
                        >
                            <div class="flex items-center gap-2">
                                <CheckBadgeIcon v-if="testResult.success" class="h-5 w-5" />
                                <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <span>{{ testResult.message }}</span>
                            </div>
                            <p v-if="testResult.provider" class="text-xs opacity-70 mt-1 ml-7">
                                Provider: {{ testResult.provider.toUpperCase() }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Pricing Comparison -->
                <div class="grid md:grid-cols-2 gap-4">
                    <div class="bg-emerald-500/5 rounded-2xl p-6 border border-emerald-500/10">
                        <h5 class="text-sm font-bold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
                            💰 Fonnte
                        </h5>
                        <ul class="space-y-2 text-xs text-slate-500 dark:text-slate-400">
                            <li class="flex items-center gap-2">
                                <CheckBadgeIcon class="h-4 w-4 text-emerald-500" />
                                <span><strong>Rp 50.000/bulan</strong></span>
                            </li>
                            <li class="flex items-center gap-2">
                                <CheckBadgeIcon class="h-4 w-4 text-emerald-500" />
                                <span>Unlimited messages</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <CheckBadgeIcon class="h-4 w-4 text-emerald-500" />
                                <span>API sederhana</span>
                            </li>
                        </ul>
                    </div>

                    <div class="bg-blue-500/5 rounded-2xl p-6 border border-blue-500/10 relative">
                        <div class="absolute -top-2 -right-2 bg-yellow-500 text-yellow-900 text-[10px] font-black px-2 py-0.5 rounded-full">
                            ⭐ RECOMMENDED
                        </div>
                        <h5 class="text-sm font-bold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
                            💰 Wablas
                        </h5>
                        <ul class="space-y-2 text-xs text-slate-500 dark:text-slate-400">
                            <li class="flex items-center gap-2">
                                <CheckBadgeIcon class="h-4 w-4 text-blue-500" />
                                <span><strong>Rp 50.000/bulan</strong></span>
                            </li>
                            <li class="flex items-center gap-2">
                                <CheckBadgeIcon class="h-4 w-4 text-blue-500" />
                                <span>Support Button & List</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <CheckBadgeIcon class="h-4 w-4 text-blue-500" />
                                <span>API lebih lengkap</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Bot Capabilities -->
                <div class="bg-purple-500/5 rounded-2xl p-6 border border-purple-500/10">
                    <h5 class="text-sm font-bold text-slate-900 dark:text-white mb-3">
                        🤖 Bot Capabilities
                    </h5>
                    <div class="grid md:grid-cols-4 gap-4 text-xs text-slate-500 dark:text-slate-400">
                        <div class="flex items-center gap-2">
                            <CheckBadgeIcon class="h-4 w-4 text-purple-500" />
                            <span>Cek status pesanan</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <CheckBadgeIcon class="h-4 w-4 text-purple-500" />
                            <span>Cek tagihan & invoice</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <CheckBadgeIcon class="h-4 w-4 text-purple-500" />
                            <span>Katalog produk & harga</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <CheckBadgeIcon class="h-4 w-4 text-purple-500" />
                            <span>Request quotation</span>
                        </div>
                    </div>
                </div>

                <!-- Action Button -->
                <div class="flex justify-end pt-4">
                    <button 
                        type="submit" 
                        :disabled="form.processing"
                        class="inline-flex items-center gap-3 px-10 py-4 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white rounded-2xl font-black uppercase tracking-widest text-xs shadow-2xl shadow-emerald-500/30 transition-all active:scale-95 disabled:opacity-50 group overflow-hidden relative"
                    >
                        <span class="relative z-10 flex items-center gap-2">
                            <CheckBadgeIcon class="h-5 w-5 transition-transform group-hover:scale-125" />
                            Save WhatsApp Configuration
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
    @apply block w-full px-5 py-4 bg-slate-50 dark:bg-slate-900/40 border border-slate-200 dark:border-white/5 rounded-2xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 outline-none transition-all duration-300;
}

.glass-card {
    background: rgba(255, 255, 255, 0.02);
    backdrop-filter: blur(20px);
}
</style>
