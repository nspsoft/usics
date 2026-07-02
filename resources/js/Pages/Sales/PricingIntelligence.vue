<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    ArrowLeftIcon, 
    SparklesIcon, 
    ArrowPathIcon,
    CurrencyDollarIcon, 
    BanknotesIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
    MinusIcon,
    InformationCircleIcon
} from '@heroicons/vue/24/outline';
import { formatCurrency, formatNumber } from '@/helpers';
import axios from 'axios';

const props = defineProps({
    products: Array,
    default_params: Object
});

const params = ref({ ...props.default_params });
const loading = ref(false);
const error = ref(null);
const analysisResult = ref(null);

// Pre-fill defaults based on SKU prefixes
const getSkuDefaults = (sku) => {
    const s = (sku || '').toUpperCase();
    if (s.startsWith('SC-')) {
        // Slit Coil / Slitting
        return { processing_fee: 250, scrap_recovery: 3 };
    } else if (s.startsWith('FG-BLNK-') || s.startsWith('FG-COMP-')) {
        // Blanking / Components
        return { processing_fee: 550, scrap_recovery: 18 };
    } else if (s.startsWith('FG-TWB-')) {
        // Tailored Welded Blanks
        return { processing_fee: 1200, scrap_recovery: 8 };
    } else {
        // Fallback/Others (e.g. Shearing / general plates)
        return { processing_fee: 350, scrap_recovery: 5 };
    }
};

const productsList = ref(props.products.map(p => {
    const defaults = getSkuDefaults(p.sku);
    return {
        ...p,
        processing_fee: defaults.processing_fee,
        scrap_recovery: defaults.scrap_recovery,
    };
}));

const runAnalysis = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await axios.post(route('sales.pricing-intelligence.analyze'), {
            products: productsList.value,
            params: params.value
        });
        if (response.data.success) {
            analysisResult.value = response.data.data;
        } else {
            error.value = response.data.error || 'Terjadi kesalahan saat menganalisis.';
        }
    } catch (e) {
        console.error(e);
        error.value = e.response?.data?.error || 'Gagal terhubung dengan server AI. Periksa koneksi atau kunci API Anda.';
    } finally {
        loading.value = false;
    }
};

const getProductSuggestion = (sku) => {
    if (!analysisResult.value || !analysisResult.value.pricing_suggestions) return null;
    return analysisResult.value.pricing_suggestions.find(s => s.sku === sku) || null;
};

</script>

<template>
    <Head title="AI Pricing Intelligence" />

    <AppLayout title="Pricing Intelligence">
        <div class="max-w-full px-4 sm:px-6 lg:px-8 mx-auto space-y-6 pb-20">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <Link href="/sales/dashboard" class="p-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-slate-900 dark:hover:text-white transition-colors">
                        <ArrowLeftIcon class="h-5 w-5" />
                    </Link>
                    <div>
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white uppercase tracking-tight">Pricing Intelligence</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400 flex flex-wrap items-center gap-x-2 gap-y-1">
                            <span>Analisis margin & simulasi harga jual produk baja berbasis AI</span>
                            <span class="text-slate-300 dark:text-slate-700 hidden sm:inline">•</span>
                            <Link href="/sales/information?tab=manual#manual-pricing-intelligence" class="text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300 font-bold inline-flex items-center gap-1 transition-colors">
                                <InformationCircleIcon class="h-4 w-4" /> Cara Kerja AI
                            </Link>
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-3 bg-amber-500/10 text-amber-500 px-4 py-2 rounded-2xl border border-amber-500/20 text-sm font-semibold animate-pulse">
                    <SparklesIcon class="h-5 w-5" />
                    <span>Powered by Gemini AI</span>
                </div>
            </div>

            <!-- Parameters Grid -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <!-- Inputs Form -->
                <div class="xl:col-span-1 glass-card rounded-2xl p-6 space-y-6">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white border-b border-white/5 pb-3">Simulasi Parameter</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 flex items-center gap-2">
                                <CurrencyDollarIcon class="h-4 w-4 text-blue-400" /> Harga LME Steel Billet (USD/Ton)
                            </label>
                            <input type="number" v-model.number="params.lme_price" class="form-input w-full rounded-xl" placeholder="Contoh: 580" required />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 flex items-center gap-2">
                                <BanknotesIcon class="h-4 w-4 text-emerald-400" /> Kurs Rupiah (IDR per USD)
                            </label>
                            <input type="number" v-model.number="params.exchange_rate" class="form-input w-full rounded-xl" placeholder="Contoh: 16000" required />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 flex items-center gap-2">
                                <ArrowTrendingUpIcon class="h-4 w-4 text-purple-400" /> Target Margin (%)
                            </label>
                            <input type="number" v-model.number="params.target_margin" class="form-input w-full rounded-xl" placeholder="Contoh: 15" required />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 flex items-center gap-2">
                                <InformationCircleIcon class="h-4 w-4 text-cyan-400" /> Ongkos Slitting/Potong (IDR/kg)
                            </label>
                            <input type="number" v-model.number="params.processing_fee" class="form-input w-full rounded-xl" placeholder="Contoh: 350" required />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 flex items-center gap-2">
                                <InformationCircleIcon class="h-4 w-4 text-amber-400" /> Faktor Pemulihan Scrap (%)
                            </label>
                            <input type="number" v-model.number="params.scrap_recovery" class="form-input w-full rounded-xl" placeholder="Contoh: 5" required />
                        </div>
                    </div>

                    <button 
                        @click="runAnalysis" 
                        :disabled="loading"
                        class="w-full inline-flex items-center justify-center gap-3 px-6 py-3.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white rounded-xl font-bold uppercase tracking-widest text-xs shadow-xl shadow-blue-500/20 transition-all active:scale-95 disabled:opacity-50"
                    >
                        <ArrowPathIcon v-if="loading" class="h-5 w-5 animate-spin" />
                        <SparklesIcon v-else class="h-5 w-5" />
                        {{ loading ? 'Menganalisis...' : 'Analisis Harga AI' }}
                    </button>
                </div>

                <!-- Analysis Summary Panel -->
                <div class="xl:col-span-2 glass-card rounded-2xl p-6 flex flex-col justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white border-b border-white/5 pb-3">Rangkuman Hasil Analisis AI</h3>
                        
                        <div v-if="error" class="mt-4 bg-red-500/10 border border-red-500/20 text-red-500 p-4 rounded-xl text-sm">
                            {{ error }}
                        </div>

                        <div v-else-if="!analysisResult" class="mt-8 flex flex-col items-center justify-center text-center p-12 text-slate-400">
                            <SparklesIcon class="h-16 w-16 text-slate-300 dark:text-slate-700 mb-4 animate-pulse" />
                            <p class="font-medium text-sm">Silakan klik tombol "Analisis Harga AI" di panel sebelah kiri untuk memproses kalkulasi harga sugerisasi.</p>
                        </div>

                        <div v-else class="mt-4 space-y-6">
                            <!-- Trend Info -->
                            <div class="flex items-center gap-4 p-4 rounded-xl bg-slate-50 dark:bg-slate-900/50 border border-white/5">
                                <div class="p-3 rounded-xl" :class="[
                                    analysisResult.market_trend === 'bullish' ? 'bg-emerald-500/10 text-emerald-500' :
                                    analysisResult.market_trend === 'bearish' ? 'bg-red-500/10 text-red-500' :
                                    'bg-blue-500/10 text-blue-500'
                                ]">
                                    <ArrowTrendingUpIcon v-if="analysisResult.market_trend === 'bullish'" class="h-6 w-6" />
                                    <ArrowTrendingDownIcon v-else-if="analysisResult.market_trend === 'bearish'" class="h-6 w-6" />
                                    <MinusIcon v-else class="h-6 w-6" />
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Tren Pasar:</span>
                                        <span class="text-sm font-black uppercase tracking-wider" :class="[
                                            analysisResult.market_trend === 'bullish' ? 'text-emerald-500' :
                                            analysisResult.market_trend === 'bearish' ? 'text-red-500' :
                                            'text-blue-500'
                                        ]">{{ analysisResult.market_trend }}</span>
                                    </div>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ analysisResult.market_trend_explanation }}</p>
                                </div>
                            </div>

                            <!-- Markdown report -->
                            <div class="prose prose-slate dark:prose-invert max-w-none text-xs leading-relaxed max-h-[250px] overflow-y-auto pr-2 bg-slate-50/50 dark:bg-slate-900/30 p-4 rounded-xl border border-white/5">
                                <div class="whitespace-pre-line">{{ analysisResult.strategic_recommendations }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Products List Suggestion -->
            <div class="glass-card rounded-2xl p-6 overflow-hidden">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Saran Harga Produk</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800 text-left">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-900/50">
                                <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">SKU</th>
                                <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Nama Produk</th>
                                <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Ongkos Proses</th>
                                <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Faktor Scrap</th>
                                <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">HPP Saat Ini</th>
                                <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Harga Jual Aktif</th>
                                <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider text-right text-blue-400">Saran HPP Baru</th>
                                <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider text-right text-emerald-400">Saran Harga Jual</th>
                                <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Deviasi Jual</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800/50">
                            <tr v-for="p in productsList" :key="p.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/20 transition-colors">
                                <td class="px-4 py-3 text-xs font-mono text-slate-900 dark:text-white">{{ p.sku }}</td>
                                <td class="px-4 py-3 text-xs text-slate-900 dark:text-white">{{ p.name }}</td>
                                <td class="px-4 py-3 text-xs text-center">
                                    <div class="inline-flex items-center gap-1">
                                        <input 
                                            type="number" 
                                            v-model.number="p.processing_fee" 
                                            class="w-16 px-1.5 py-0.5 text-xs text-center border border-slate-300 dark:border-slate-800 rounded bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500" 
                                            min="0"
                                        />
                                        <span class="text-[10px] text-slate-400">/kg</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-xs text-center">
                                    <div class="inline-flex items-center gap-1">
                                        <input 
                                            type="number" 
                                            v-model.number="p.scrap_recovery" 
                                            class="w-12 px-1.5 py-0.5 text-xs text-center border border-slate-300 dark:border-slate-800 rounded bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500" 
                                            min="0" 
                                            max="100"
                                        />
                                        <span class="text-[10px] text-slate-400">%</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-500 dark:text-slate-400 text-right">{{ formatCurrency(p.cost_price) }}</td>
                                <td class="px-4 py-3 text-xs text-slate-900 dark:text-white text-right font-semibold">{{ formatCurrency(p.selling_price) }}</td>
                                
                                <td class="px-4 py-3 text-xs text-blue-400 text-right font-medium">
                                    <span v-if="getProductSuggestion(p.sku)">
                                        {{ formatCurrency(getProductSuggestion(p.sku).suggested_cost_price) }}
                                    </span>
                                    <span v-else class="text-slate-400">-</span>
                                </td>
                                
                                <td class="px-4 py-3 text-xs text-emerald-400 text-right font-black">
                                    <div v-if="getProductSuggestion(p.sku)">
                                        <p>{{ formatCurrency(getProductSuggestion(p.sku).recommended_selling_price) }}</p>
                                        <p class="text-[10px] text-slate-500 font-normal">Min: {{ formatCurrency(getProductSuggestion(p.sku).min_selling_price) }} | Max: {{ formatCurrency(getProductSuggestion(p.sku).max_selling_price) }}</p>
                                    </div>
                                    <span v-else class="text-slate-400">-</span>
                                </td>

                                <td class="px-4 py-3 text-xs text-center font-bold">
                                    <div v-if="getProductSuggestion(p.sku)">
                                        <span v-if="getProductSuggestion(p.sku).recommended_selling_price > p.selling_price" class="text-red-400 px-2 py-0.5 rounded bg-red-400/10">
                                            +{{ (((getProductSuggestion(p.sku).recommended_selling_price - p.selling_price) / p.selling_price) * 100).toFixed(1) }}%
                                        </span>
                                        <span v-else class="text-emerald-400 px-2 py-0.5 rounded bg-emerald-400/10">
                                            {{ (((getProductSuggestion(p.sku).recommended_selling_price - p.selling_price) / p.selling_price) * 100).toFixed(1) }}%
                                        </span>
                                    </div>
                                    <span v-else class="text-slate-400">-</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
