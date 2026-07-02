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
    InformationCircleIcon,
    EyeIcon
} from '@heroicons/vue/24/outline';
import { formatCurrency, formatNumber } from '@/helpers';
import axios from 'axios';

const props = defineProps({
    products: Array,
    default_params: Object
});

import { watch } from 'vue';

const params = ref({ 
    slitting_fee: 250,
    slitting_scrap: 3,
    blanking_fee: 550,
    blanking_scrap: 18,
    welding_fee: 1200,
    welding_scrap: 8,
    shearing_fee: 350,
    shearing_scrap: 5,
    ...props.default_params
});

const loading = ref(false);
const error = ref(null);
const analysisResult = ref(null);

// Pre-fill defaults based on SKU prefixes and current params
const getSkuDefaults = (sku) => {
    const s = (sku || '').toUpperCase();
    if (s.startsWith('SC-')) {
        return { processing_fee: params.value.slitting_fee, scrap_recovery: params.value.slitting_scrap };
    } else if (s.startsWith('FG-BLNK-') || s.startsWith('FG-COMP-')) {
        return { processing_fee: params.value.blanking_fee, scrap_recovery: params.value.blanking_scrap };
    } else if (s.startsWith('FG-TWB-')) {
        return { processing_fee: params.value.welding_fee, scrap_recovery: params.value.welding_scrap };
    } else {
        return { processing_fee: params.value.shearing_fee, scrap_recovery: params.value.shearing_scrap };
    }
};

const productsList = ref(props.products.map(p => {
    const defaults = getSkuDefaults(p.sku);
    return {
        ...p,
        processing_fee: defaults.processing_fee,
        scrap_recovery: defaults.scrap_recovery,
        is_fee_dirty: false,
        is_scrap_dirty: false,
    };
}));

// Set dirty-flags to prevent global parameter changes from overwriting manual overrides
const markFeeDirty = (product) => {
    product.is_fee_dirty = true;
};
const markScrapDirty = (product) => {
    product.is_scrap_dirty = true;
};

// Watch global process parameters and dynamically update products that aren't dirty
watch(
    () => [
        params.value.slitting_fee,
        params.value.slitting_scrap,
        params.value.blanking_fee,
        params.value.blanking_scrap,
        params.value.welding_fee,
        params.value.welding_scrap,
        params.value.shearing_fee,
        params.value.shearing_scrap,
    ],
    () => {
        productsList.value.forEach(p => {
            const sku = p.sku.toUpperCase();
            if (sku.startsWith('SC-')) {
                if (!p.is_fee_dirty) p.processing_fee = params.value.slitting_fee;
                if (!p.is_scrap_dirty) p.scrap_recovery = params.value.slitting_scrap;
            } else if (sku.startsWith('FG-BLNK-') || sku.startsWith('FG-COMP-')) {
                if (!p.is_fee_dirty) p.processing_fee = params.value.blanking_fee;
                if (!p.is_scrap_dirty) p.scrap_recovery = params.value.blanking_scrap;
            } else if (sku.startsWith('FG-TWB-')) {
                if (!p.is_fee_dirty) p.processing_fee = params.value.welding_fee;
                if (!p.is_scrap_dirty) p.scrap_recovery = params.value.welding_scrap;
            } else {
                if (!p.is_fee_dirty) p.processing_fee = params.value.shearing_fee;
                if (!p.is_scrap_dirty) p.scrap_recovery = params.value.shearing_scrap;
            }
        });
        
        // Update general fallback for backend API validation
        params.value.processing_fee = params.value.shearing_fee;
        params.value.scrap_recovery = params.value.shearing_scrap;
    },
    { deep: true }
);

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

// Machine Details Mapping
const getMachineDetails = (sku) => {
    const s = (sku || '').toUpperCase();
    if (s.startsWith('SC-')) {
        return { name: 'Slitting Machine', image: '/images/slitting_machine.png', desc: 'Memotong mother coil lebar menjadi slit coil dengan lebar kustom.' };
    } else if (s.startsWith('FG-BLNK-') || s.startsWith('FG-COMP-')) {
        return { name: 'Blanking Press Machine', image: '/images/blanking_press.png', desc: 'Mencetak plat lembaran menjadi bentuk disc brake blank atau komponen khusus.' };
    } else if (s.startsWith('FG-TWB-')) {
        return { name: 'Laser Welding Machine', image: '/images/laser_welder.png', desc: 'Penyambungan dua plat dengan ketebalan/spesifikasi berbeda menggunakan laser.' };
    } else {
        return { name: 'Shearing Machine', image: '/images/shearing_machine.png', desc: 'Memotong plat gulungan (mother coil) menjadi lembaran persegi panjang (sheared sheets).' };
    }
};

const selectedProductModal = ref(null);
const viewProductDetails = (product) => {
    selectedProductModal.value = product;
};
const closeModal = () => {
    selectedProductModal.value = null;
};

// Real-time calculation helper
const calculateCostDetails = (p) => {
    if (!p) return null;
    const rawCost = (params.value.lme_price * params.value.exchange_rate) / 1000;
    const scrapDiscount = rawCost * (p.scrap_recovery / 100) * 0.4;
    const suggestedCost = rawCost + p.processing_fee - scrapDiscount;
    const recommendedPrice = suggestedCost / (1 - (params.value.target_margin / 100));
    const minPrice = suggestedCost / (1 - ((params.value.target_margin - 3) / 100));
    const maxPrice = suggestedCost / (1 - ((params.value.target_margin + 4) / 100));
    
    return {
        rawCost,
        scrapDiscount,
        suggestedCost,
        recommendedPrice,
        minPrice,
        maxPrice
    };
};

// AI Process Cost Calculator
const showCalcModal = ref(false);
const calcMachineType = ref('slitting'); // 'slitting' | 'blanking' | 'welding' | 'shearing'
const calcForm = ref({
    investment: 1200000000,
    useful_life: 10,
    working_hours_monthly: 200,
    power: 45,
    load_factor: 80,
    electricity_tariff: 1500,
    operator_count: 2,
    operator_salary: 30000,
    maintenance_monthly: 4000000,
    hourly_output: 1500
});

const openCalculator = (type) => {
    calcMachineType.value = type;
    
    // Load from saved database settings if available, otherwise use hardcoded fallbacks
    const saved = props.default_params['calc_' + type];
    if (saved) {
        calcForm.value = { ...saved };
    } else {
        if (type === 'slitting') {
            calcForm.value = {
                investment: 1500000000,
                useful_life: 10,
                working_hours_monthly: 200,
                power: 55,
                load_factor: 70,
                electricity_tariff: 1500,
                operator_count: 3,
                operator_salary: 32000,
                maintenance_monthly: 6000000,
                hourly_output: 1800
            };
        } else if (type === 'blanking') {
            calcForm.value = {
                investment: 1800000000,
                useful_life: 12,
                working_hours_monthly: 200,
                power: 75,
                load_factor: 75,
                electricity_tariff: 1500,
                operator_count: 2,
                operator_salary: 30000,
                maintenance_monthly: 8000000,
                hourly_output: 1200
            };
        } else if (type === 'welding') {
            calcForm.value = {
                investment: 2500000000,
                useful_life: 8,
                working_hours_monthly: 220,
                power: 90,
                load_factor: 85,
                electricity_tariff: 1500,
                operator_count: 2,
                operator_salary: 35000,
                maintenance_monthly: 12000000,
                hourly_output: 800
            };
        } else {
            // Shearing / Fallback
            calcForm.value = {
                investment: 600000000,
                useful_life: 10,
                working_hours_monthly: 180,
                power: 30,
                load_factor: 60,
                electricity_tariff: 1500,
                operator_count: 2,
                operator_salary: 28000,
                maintenance_monthly: 3000000,
                hourly_output: 1000
            };
        }
    }
    showCalcModal.value = true;
};

// Real-time calculation results
const calcResults = computed(() => {
    const f = calcForm.value;
    
    // 1. Depreciation per hour
    const depreciationPerHour = f.investment / (f.useful_life * 12 * f.working_hours_monthly);
    
    // 2. Electricity cost per hour
    const electricityPerHour = f.power * (f.load_factor / 100) * f.electricity_tariff;
    
    // 3. Operator cost per hour
    const operatorPerHour = f.operator_count * f.operator_salary;
    
    // 4. Maintenance cost per hour
    const maintenancePerHour = f.maintenance_monthly / f.working_hours_monthly;
    
    // Total Machine Hour Rate (MHR)
    const machineHourRate = depreciationPerHour + electricityPerHour + operatorPerHour + maintenancePerHour;
    
    // Cost per KG
    const costPerKg = f.hourly_output > 0 ? (machineHourRate / f.hourly_output) : 0;
    
    return {
        depreciationPerHour,
        electricityPerHour,
        operatorPerHour,
        maintenancePerHour,
        machineHourRate,
        costPerKg: Math.round(costPerKg * 100) / 100 // round to 2 decimals
    };
});

const applyCalcResults = () => {
    const feeKey = calcMachineType.value + '_fee';
    params.value[feeKey] = Math.round(calcResults.value.costPerKg);
    showCalcModal.value = false;
    
    // Auto-update non-dirty products of this type
    productsList.value.forEach(p => {
        const sku = p.sku.toUpperCase();
        const prefixMatch = 
            (calcMachineType.value === 'slitting' && sku.startsWith('SC-')) ||
            (calcMachineType.value === 'blanking' && (sku.startsWith('FG-BLNK-') || sku.startsWith('FG-COMP-'))) ||
            (calcMachineType.value === 'welding' && sku.startsWith('FG-TWB-')) ||
            (calcMachineType.value === 'shearing' && !sku.startsWith('SC-') && !sku.startsWith('FG-BLNK-') && !sku.startsWith('FG-COMP-') && !sku.startsWith('FG-TWB-'));
            
        if (prefixMatch && !p.is_fee_dirty) {
            p.processing_fee = params.value[feeKey];
        }
    });
};

const isSavingCalc = ref(false);
const applyAndSaveCalcResults = async () => {
    isSavingCalc.value = true;
    try {
        const feeKey = calcMachineType.value + '_fee';
        const scrapKey = calcMachineType.value + '_scrap';
        const finalFee = Math.round(calcResults.value.costPerKg);
        const finalScrap = params.value[scrapKey];

        const response = await axios.post(route('pricing-intelligence.save-calculator'), {
            machine_type: calcMachineType.value,
            fee: finalFee,
            scrap: finalScrap,
            calc_form: calcForm.value
        });

        if (response.data.success) {
            // Update in-memory props so next open loads these new defaults
            props.default_params['calc_' + calcMachineType.value] = { ...calcForm.value };
            props.default_params[feeKey] = finalFee;
            props.default_params[scrapKey] = finalScrap;

            // Apply locally to form parameters
            applyCalcResults();
            
            alert(`Berhasil menyimpan pengaturan default & menerapkan tarif baru: Rp ${finalFee}/kg ke kategori ${calcMachineType.value.toUpperCase()}!`);
        } else {
            alert('Gagal menyimpan default ke database.');
        }
    } catch (e) {
        console.error(e);
        alert('Terjadi kesalahan saat menyimpan pengaturan ke database.');
    } finally {
        isSavingCalc.value = false;
    }
};

// Copy price to clipboard helper
const copyToClipboard = (value) => {
    navigator.clipboard.writeText(value);
    alert('Harga berhasil disalin ke papan klip!');
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

                        <!-- Biaya & Scrap per Mesin (Compact) -->
                        <div class="pt-4 border-t border-slate-200 dark:border-slate-800 space-y-3">
                            <div class="flex items-center gap-2 pb-0.5">
                                <SparklesIcon class="h-4 w-4 text-cyan-400" />
                                <h4 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-wider">Default per Mesin</h4>
                            </div>
                            
                            <!-- Slitting -->
                            <div class="flex items-center justify-between gap-3 p-2 bg-slate-50/50 dark:bg-slate-900/30 rounded-xl border border-slate-200 dark:border-slate-800">
                                <div class="w-1/2 min-w-[110px]">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-[10px] font-black text-indigo-500 dark:text-indigo-400 uppercase tracking-wider block">1. Slitting (SC-)</span>
                                        <button @click="openCalculator('slitting')" class="p-0.5 rounded text-slate-400 hover:text-indigo-400 hover:bg-indigo-500/10 transition-colors" title="Buka Kalkulator AI Ongkos Proses">
                                            <SparklesIcon class="h-3 w-3" />
                                        </button>
                                    </div>
                                    <span class="text-[9px] text-slate-400 block -mt-0.5 lowercase font-medium">slit coil</span>
                                </div>
                                <div class="flex items-center gap-2 w-1/2 justify-end">
                                    <div class="relative w-20">
                                        <span class="absolute left-2 top-1/2 -translate-y-1/2 text-[9px] font-bold text-slate-500">Rp</span>
                                        <input type="number" v-model.number="params.slitting_fee" class="form-input w-full rounded-lg text-xs py-1.5 pl-6 pr-1 text-center font-bold" placeholder="Fee" title="Ongkos (IDR/kg)" />
                                    </div>
                                    <div class="relative w-16">
                                        <input type="number" v-model.number="params.slitting_scrap" class="form-input w-full rounded-lg text-xs py-1.5 pl-1 pr-4 text-center font-bold" placeholder="Scrap" title="Scrap (%)" />
                                        <span class="absolute right-1.5 top-1/2 -translate-y-1/2 text-[9px] font-bold text-slate-500">%</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Blanking -->
                            <div class="flex items-center justify-between gap-3 p-2 bg-slate-50/50 dark:bg-slate-900/30 rounded-xl border border-slate-200 dark:border-slate-800">
                                <div class="w-1/2 min-w-[110px]">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-[10px] font-black text-emerald-500 dark:text-emerald-400 uppercase tracking-wider block">2. Blanking (FG-BLNK)</span>
                                        <button @click="openCalculator('blanking')" class="p-0.5 rounded text-slate-400 hover:text-emerald-400 hover:bg-emerald-500/10 transition-colors" title="Buka Kalkulator AI Ongkos Proses">
                                            <SparklesIcon class="h-3 w-3" />
                                        </button>
                                    </div>
                                    <span class="text-[9px] text-slate-400 block -mt-0.5 lowercase font-medium">plat cetak</span>
                                </div>
                                <div class="flex items-center gap-2 w-1/2 justify-end">
                                    <div class="relative w-20">
                                        <span class="absolute left-2 top-1/2 -translate-y-1/2 text-[9px] font-bold text-slate-500">Rp</span>
                                        <input type="number" v-model.number="params.blanking_fee" class="form-input w-full rounded-lg text-xs py-1.5 pl-6 pr-1 text-center font-bold" placeholder="Fee" title="Ongkos (IDR/kg)" />
                                    </div>
                                    <div class="relative w-16">
                                        <input type="number" v-model.number="params.blanking_scrap" class="form-input w-full rounded-lg text-xs py-1.5 pl-1 pr-4 text-center font-bold" placeholder="Scrap" title="Scrap (%)" />
                                        <span class="absolute right-1.5 top-1/2 -translate-y-1/2 text-[9px] font-bold text-slate-500">%</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Laser Welding -->
                            <div class="flex items-center justify-between gap-3 p-2 bg-slate-50/50 dark:bg-slate-900/30 rounded-xl border border-slate-200 dark:border-slate-800">
                                <div class="w-1/2 min-w-[110px]">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-[10px] font-black text-rose-500 dark:text-rose-400 uppercase tracking-wider block">3. Laser (FG-TWB)</span>
                                        <button @click="openCalculator('welding')" class="p-0.5 rounded text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 transition-colors" title="Buka Kalkulator AI Ongkos Proses">
                                            <SparklesIcon class="h-3 w-3" />
                                        </button>
                                    </div>
                                    <span class="text-[9px] text-slate-400 block -mt-0.5 lowercase font-medium">las laser</span>
                                </div>
                                <div class="flex items-center gap-2 w-1/2 justify-end">
                                    <div class="relative w-20">
                                        <span class="absolute left-2 top-1/2 -translate-y-1/2 text-[9px] font-bold text-slate-500">Rp</span>
                                        <input type="number" v-model.number="params.welding_fee" class="form-input w-full rounded-lg text-xs py-1.5 pl-6 pr-1 text-center font-bold" placeholder="Fee" title="Ongkos (IDR/kg)" />
                                    </div>
                                    <div class="relative w-16">
                                        <input type="number" v-model.number="params.welding_scrap" class="form-input w-full rounded-lg text-xs py-1.5 pl-1 pr-4 text-center font-bold" placeholder="Scrap" title="Scrap (%)" />
                                        <span class="absolute right-1.5 top-1/2 -translate-y-1/2 text-[9px] font-bold text-slate-500">%</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Shearing -->
                            <div class="flex items-center justify-between gap-3 p-2 bg-slate-50/50 dark:bg-slate-900/30 rounded-xl border border-slate-200 dark:border-slate-800">
                                <div class="w-1/2 min-w-[110px]">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-[10px] font-black text-cyan-500 dark:text-cyan-400 uppercase tracking-wider block">4. Shearing (Lainnya)</span>
                                        <button @click="openCalculator('shearing')" class="p-0.5 rounded text-slate-400 hover:text-cyan-400 hover:bg-cyan-500/10 transition-colors" title="Buka Kalkulator AI Ongkos Proses">
                                            <SparklesIcon class="h-3 w-3" />
                                        </button>
                                    </div>
                                    <span class="text-[9px] text-slate-400 block -mt-0.5 lowercase font-medium">potong biasa</span>
                                </div>
                                <div class="flex items-center gap-2 w-1/2 justify-end">
                                    <div class="relative w-20">
                                        <span class="absolute left-2 top-1/2 -translate-y-1/2 text-[9px] font-bold text-slate-500">Rp</span>
                                        <input type="number" v-model.number="params.shearing_fee" class="form-input w-full rounded-lg text-xs py-1.5 pl-6 pr-1 text-center font-bold" placeholder="Fee" title="Ongkos (IDR/kg)" />
                                    </div>
                                    <div class="relative w-16">
                                        <input type="number" v-model.number="params.shearing_scrap" class="form-input w-full rounded-lg text-xs py-1.5 pl-1 pr-4 text-center font-bold" placeholder="Scrap" title="Scrap (%)" />
                                        <span class="absolute right-1.5 top-1/2 -translate-y-1/2 text-[9px] font-bold text-slate-500">%</span>
                                    </div>
                                </div>
                            </div>
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
                                <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Mesin Produksi</th>
                                <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Ongkos Proses</th>
                                <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Faktor Scrap</th>
                                <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">HPP Saat Ini</th>
                                <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Harga Jual Aktif</th>
                                <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider text-right text-blue-400">Saran HPP Baru</th>
                                <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider text-right text-emerald-400">Saran Harga Jual</th>
                                <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Deviasi Jual</th>
                                <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800/50">
                            <tr v-for="p in productsList" :key="p.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/20 transition-colors">
                                <td class="px-4 py-3 text-xs font-mono text-slate-900 dark:text-white">{{ p.sku }}</td>
                                <td class="px-4 py-3 text-xs text-slate-900 dark:text-white font-medium">{{ p.name }}</td>
                                <td class="px-4 py-3 text-xs">
                                    <div class="flex items-center gap-2">
                                        <img 
                                            :src="getMachineDetails(p.sku).image" 
                                            class="w-10 h-7 rounded object-cover border border-slate-200 dark:border-slate-800 shadow-sm shrink-0"
                                            :alt="getMachineDetails(p.sku).name"
                                        />
                                        <span class="text-[11px] text-slate-600 dark:text-slate-400 font-semibold truncate max-w-[120px]">{{ getMachineDetails(p.sku).name }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-xs text-center">
                                    <div class="inline-flex items-center gap-1">
                                        <input 
                                            type="number" 
                                            v-model.number="p.processing_fee" 
                                            @input="markFeeDirty(p)"
                                            class="w-16 px-1.5 py-0.5 text-xs text-center border border-slate-300 dark:border-slate-800 rounded bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 font-semibold" 
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
                                            @input="markScrapDirty(p)"
                                            class="w-12 px-1.5 py-0.5 text-xs text-center border border-slate-300 dark:border-slate-800 rounded bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 font-semibold" 
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
                                        <p class="text-[10px] text-slate-500 font-normal text-right">Min: {{ formatCurrency(getProductSuggestion(p.sku).min_selling_price) }} | Max: {{ formatCurrency(getProductSuggestion(p.sku).max_selling_price) }}</p>
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
                                
                                <td class="px-4 py-3 text-xs text-center">
                                    <button 
                                        @click="viewProductDetails(p)"
                                        class="p-1.5 text-indigo-500 hover:text-indigo-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors inline-flex items-center"
                                        title="Lihat Detail Cara Kerja AI & Kalkulasi"
                                    >
                                        <EyeIcon class="h-4 w-4" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Interactive Detail Modal -->
            <div v-if="selectedProductModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-lg transition-all animate-in fade-in duration-300">
                <div class="relative max-w-4xl w-full glass-card border border-white/10 rounded-[32px] overflow-hidden bg-slate-900/95 text-slate-100 shadow-2xl p-6 md:p-8 flex flex-col md:flex-row gap-6 md:gap-8 animate-in zoom-in-95 duration-300">
                    
                    <!-- Close button -->
                    <button @click="closeModal" class="absolute top-4 right-4 p-2 rounded-full hover:bg-white/10 transition-colors text-slate-400 hover:text-white">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <!-- Left Column: Production Machine Information -->
                    <div class="md:w-1/2 flex flex-col justify-between border-b md:border-b-0 md:border-r border-white/10 pb-6 md:pb-0 md:pr-6">
                        <div class="space-y-4">
                            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-[10px] font-bold uppercase tracking-wider">
                                Lini Produksi & Mesin
                            </div>
                            <h3 class="text-xl font-bold text-white flex items-center gap-2">
                                {{ getMachineDetails(selectedProductModal.sku).name }}
                            </h3>
                            <div class="relative group overflow-hidden rounded-2xl border border-white/10">
                                <img 
                                    :src="getMachineDetails(selectedProductModal.sku).image" 
                                    class="w-full h-48 md:h-56 object-cover transform group-hover:scale-105 transition-transform duration-500" 
                                    :alt="getMachineDetails(selectedProductModal.sku).name"
                                />
                                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/70 via-transparent to-transparent"></div>
                            </div>
                            <p class="text-xs text-slate-400 leading-relaxed">
                                {{ getMachineDetails(selectedProductModal.sku).desc }} Mesin ini dikonfigurasi khusus dengan ongkos kerja (shearing/slitting fee) dan faktor pemulihan scrap optimal sesuai spesifikasi pelat baja.
                            </p>
                        </div>
                        <div class="mt-4 pt-4 border-t border-white/5 flex gap-2">
                            <span class="text-xs text-slate-500">SKU:</span>
                            <span class="text-xs font-mono text-slate-300 font-bold">{{ selectedProductModal.sku }}</span>
                        </div>
                    </div>

                    <!-- Right Column: Cost Formula & AI Analysis -->
                    <div class="md:w-1/2 flex flex-col justify-between">
                        <div class="space-y-6">
                            <div>
                                <h4 class="text-sm font-bold text-slate-400 uppercase tracking-widest mb-1">Nama Produk</h4>
                                <h3 class="text-lg font-bold text-white leading-snug">{{ selectedProductModal.name }}</h3>
                            </div>

                            <!-- Cost Calculations Breakdown -->
                            <div class="bg-slate-950/50 rounded-2xl p-4 border border-white/5 space-y-3">
                                <h5 class="text-xs font-bold text-indigo-400 uppercase tracking-wider border-b border-white/5 pb-2">Rincian Kalkulasi Harga Pokok (HPP)</h5>
                                
                                <div class="space-y-2 text-xs">
                                    <div class="flex justify-between">
                                        <span class="text-slate-400">1. Raw Material LME Cost/Kg</span>
                                        <span class="font-mono text-slate-200">
                                            {{ formatCurrency(calculateCostDetails(selectedProductModal).rawCost) }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-400">2. Processing/Slitting Fee</span>
                                        <span class="font-mono text-emerald-400">
                                            + {{ formatCurrency(selectedProductModal.processing_fee) }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-400">3. Scrap Recovery Discount (40%)</span>
                                        <span class="font-mono text-red-400">
                                            - {{ formatCurrency(calculateCostDetails(selectedProductModal).scrapDiscount) }}
                                        </span>
                                    </div>
                                    <div class="border-t border-white/5 pt-2 flex justify-between font-bold">
                                        <span class="text-white">Estimasi HPP Sugerisasi</span>
                                        <span class="font-mono text-blue-400">
                                            {{ formatCurrency(calculateCostDetails(selectedProductModal).suggestedCost) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Selling Price Recommendations -->
                            <div class="bg-indigo-600/10 rounded-2xl p-4 border border-indigo-500/20 space-y-3">
                                <h5 class="text-xs font-bold text-indigo-400 uppercase tracking-wider border-b border-indigo-500/5 pb-2">Rekomendasi Harga Jual (Margin {{ params.target_margin }}%)</h5>
                                
                                <div class="space-y-2 text-xs">
                                    <div class="flex justify-between">
                                        <span class="text-slate-400">Minimum Selling Price (-3%)</span>
                                        <span class="font-mono text-slate-300">
                                            {{ formatCurrency(calculateCostDetails(selectedProductModal).minPrice) }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-400">Maximum Selling Price (+4%)</span>
                                        <span class="font-mono text-slate-300">
                                            {{ formatCurrency(calculateCostDetails(selectedProductModal).maxPrice) }}
                                        </span>
                                    </div>
                                    <div class="border-t border-white/10 pt-2 flex justify-between font-black text-sm">
                                        <span class="text-white">Harga Rekomendasi Jual</span>
                                        <span class="font-mono text-emerald-400">
                                            {{ formatCurrency(calculateCostDetails(selectedProductModal).recommendedPrice) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="mt-6 flex gap-3">
                            <button 
                                @click="copyToClipboard(Math.round(calculateCostDetails(selectedProductModal).recommendedPrice))"
                                class="flex-1 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold uppercase tracking-wider transition-colors shadow-lg shadow-indigo-600/20"
                            >
                                Salin Harga
                            </button>
                            <button 
                                @click="closeModal"
                                class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-bold uppercase tracking-wider transition-colors"
                            >
                                Tutup
                            </button>
                        </div>
                    </div>

                </div>
            </div>

            <!-- AI Process Cost Calculator Modal -->
            <div v-if="showCalcModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/65 backdrop-blur-md transition-all animate-in fade-in duration-200">
                <div class="relative max-w-5xl w-full glass-card border border-white/10 rounded-[32px] overflow-hidden bg-slate-900/95 text-slate-100 shadow-2xl p-6 md:p-8 flex flex-col lg:flex-row gap-6 md:gap-8 max-h-[90vh] overflow-y-auto animate-in zoom-in-95 duration-200">
                    
                    <!-- Close Button -->
                    <button @click="showCalcModal = false" class="absolute top-4 right-4 p-2 rounded-full hover:bg-white/10 transition-colors text-slate-400 hover:text-white">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <!-- Left: Inputs Section -->
                    <div class="lg:w-7/12 space-y-4">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-indigo-500/10 border border-indigo-500/30 flex items-center justify-center text-indigo-400">
                                <SparklesIcon class="h-4 w-4" />
                            </div>
                            <div>
                                <h3 class="text-lg font-black text-white uppercase tracking-wider">Kalkulator Biaya Proses AI</h3>
                                <p class="text-[10px] text-slate-400">Kalkulasi Machine Hour Rate (MHR) & estimasi biaya proses per kg secara presisi untuk kategori: <span class="font-bold text-indigo-400 uppercase">{{ calcMachineType }}</span></p>
                            </div>
                        </div>

                        <!-- Param Inputs Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                            <!-- 1. Depresiasi -->
                            <div class="space-y-3 p-3.5 bg-slate-950/40 rounded-2xl border border-white/5">
                                <h4 class="text-[10px] font-black text-indigo-400 uppercase tracking-wider">A. Biaya Depresiasi Mesin</h4>
                                <div class="space-y-2">
                                    <div>
                                        <label class="block text-[9px] text-slate-400 font-bold uppercase mb-1">Nilai Investasi Mesin (IDR)</label>
                                        <input type="number" v-model.number="calcForm.investment" class="form-input w-full text-xs rounded-lg py-1.5 px-3 font-semibold" />
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-[9px] text-slate-400 font-bold uppercase mb-1">Umur (Tahun)</label>
                                            <input type="number" v-model.number="calcForm.useful_life" class="form-input w-full text-xs rounded-lg py-1.5 px-3 font-semibold text-center" />
                                        </div>
                                        <div>
                                            <label class="block text-[9px] text-slate-400 font-bold uppercase mb-1">Jam/Bulan</label>
                                            <input type="number" v-model.number="calcForm.working_hours_monthly" class="form-input w-full text-xs rounded-lg py-1.5 px-3 font-semibold text-center" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. Energi -->
                            <div class="space-y-3 p-3.5 bg-slate-950/40 rounded-2xl border border-white/5">
                                <h4 class="text-[10px] font-black text-emerald-400 uppercase tracking-wider">B. Konsumsi Energi Listrik</h4>
                                <div class="space-y-2">
                                    <div>
                                        <label class="block text-[9px] text-slate-400 font-bold uppercase mb-1">Daya Listrik Motor (kW)</label>
                                        <input type="number" v-model.number="calcForm.power" class="form-input w-full text-xs rounded-lg py-1.5 px-3 font-semibold" />
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-[9px] text-slate-400 font-bold uppercase mb-1">Load factor (%)</label>
                                            <input type="number" v-model.number="calcForm.load_factor" class="form-input w-full text-xs rounded-lg py-1.5 px-3 font-semibold text-center" />
                                        </div>
                                        <div>
                                            <label class="block text-[9px] text-slate-400 font-bold uppercase mb-1">Tarif (Rp/kWh)</label>
                                            <input type="number" v-model.number="calcForm.electricity_tariff" class="form-input w-full text-xs rounded-lg py-1.5 px-3 font-semibold text-center" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 3. Operator -->
                            <div class="space-y-3 p-3.5 bg-slate-950/40 rounded-2xl border border-white/5">
                                <h4 class="text-[10px] font-black text-rose-400 uppercase tracking-wider">C. Tenaga Kerja Langsung</h4>
                                <div class="space-y-2">
                                    <div>
                                        <label class="block text-[9px] text-slate-400 font-bold uppercase mb-1">Upah Operator/Jam/Orang</label>
                                        <input type="number" v-model.number="calcForm.operator_salary" class="form-input w-full text-xs rounded-lg py-1.5 px-3 font-semibold" />
                                    </div>
                                    <div>
                                        <label class="block text-[9px] text-slate-400 font-bold uppercase mb-1">Jumlah Crew / Operator</label>
                                        <input type="number" v-model.number="calcForm.operator_count" class="form-input w-full text-xs rounded-lg py-1.5 px-3 font-semibold text-center" />
                                    </div>
                                </div>
                            </div>

                            <!-- 4. Maintenance & Output -->
                            <div class="space-y-3 p-3.5 bg-slate-950/40 rounded-2xl border border-white/5">
                                <h4 class="text-[10px] font-black text-cyan-400 uppercase tracking-wider">D. Maintenance & Output</h4>
                                <div class="space-y-2">
                                    <div>
                                        <label class="block text-[9px] text-slate-400 font-bold uppercase mb-1">Perawatan Bulanan (Dies/Blade/Oli)</label>
                                        <input type="number" v-model.number="calcForm.maintenance_monthly" class="form-input w-full text-xs rounded-lg py-1.5 px-3 font-semibold" />
                                    </div>
                                    <div>
                                        <label class="block text-[9px] text-slate-400 font-bold uppercase mb-1">Rata-rata Output Aktual (Kg/Jam)</label>
                                        <input type="number" v-model.number="calcForm.hourly_output" class="form-input w-full text-xs rounded-lg py-1.5 px-3 font-semibold text-center font-black text-indigo-400" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Outputs & Real-time formulas explanation -->
                    <div class="lg:w-5/12 flex flex-col justify-between p-4 bg-slate-950/80 rounded-3xl border border-white/10">
                        <div class="space-y-5">
                            <h3 class="text-sm font-black text-white uppercase tracking-wider border-b border-white/5 pb-2">Rincian Formula & Hasil Kalkulasi</h3>
                            
                            <div class="space-y-2.5 text-xs">
                                <!-- Step 1 -->
                                <div class="flex justify-between items-start border-b border-white/5 pb-2">
                                    <div>
                                        <p class="font-bold text-slate-300">1. Depresiasi per Jam</p>
                                        <p class="text-[9px] text-slate-500 italic">Investasi / (Tahun * 12 * Jam)</p>
                                    </div>
                                    <span class="font-mono text-slate-200 font-bold">{{ formatCurrency(calcResults.depreciationPerHour) }}</span>
                                </div>
                                <!-- Step 2 -->
                                <div class="flex justify-between items-start border-b border-white/5 pb-2">
                                    <div>
                                        <p class="font-bold text-slate-300">2. Konsumsi Listrik per Jam</p>
                                        <p class="text-[9px] text-slate-500 italic">kW * Load% * Rp/kWh</p>
                                    </div>
                                    <span class="font-mono text-slate-200 font-bold">{{ formatCurrency(calcResults.electricityPerHour) }}</span>
                                </div>
                                <!-- Step 3 -->
                                <div class="flex justify-between items-start border-b border-white/5 pb-2">
                                    <div>
                                        <p class="font-bold text-slate-300">3. Gaji Operator per Jam</p>
                                        <p class="text-[9px] text-slate-500 italic">Jumlah Crew * Gaji per jam</p>
                                    </div>
                                    <span class="font-mono text-slate-200 font-bold">{{ formatCurrency(calcResults.operatorPerHour) }}</span>
                                </div>
                                <!-- Step 4 -->
                                <div class="flex justify-between items-start border-b border-white/5 pb-2">
                                    <div>
                                        <p class="font-bold text-slate-300">4. Perawatan per Jam</p>
                                        <p class="text-[9px] text-slate-500 italic">Biaya Bulanan / Jam Kerja</p>
                                    </div>
                                    <span class="font-mono text-slate-200 font-bold">{{ formatCurrency(calcResults.maintenancePerHour) }}</span>
                                </div>
                                
                                <!-- Machine Hour Rate -->
                                <div class="p-3 bg-indigo-500/10 border border-indigo-500/20 rounded-xl flex justify-between items-center my-3">
                                    <div>
                                        <p class="text-[10px] font-black text-indigo-400 uppercase tracking-widest">Machine Hour Rate (MHR)</p>
                                        <p class="text-[8px] text-slate-400 italic">Total Ongkos Operasional Mesin per Jam</p>
                                    </div>
                                    <span class="font-mono text-sm text-indigo-400 font-black">{{ formatCurrency(calcResults.machineHourRate) }}/jam</span>
                                </div>

                                <!-- Output per Kg final -->
                                <div class="p-3.5 bg-emerald-500/10 border border-emerald-500/20 rounded-xl space-y-1">
                                    <div class="flex justify-between items-center">
                                        <span class="text-[10px] font-black text-emerald-400 uppercase tracking-widest">Biaya Proses per Kg</span>
                                        <span class="font-mono text-base text-emerald-400 font-black">Rp {{ calcResults.costPerKg }}</span>
                                    </div>
                                    <p class="text-[8.5px] text-slate-400 leading-normal italic text-right border-t border-white/5 pt-1">
                                        Formula: Rp {{ formatNumber(calcResults.machineHourRate) }} (MHR) / {{ formatNumber(calcForm.hourly_output) }} kg (Kapasitas Output/Jam)
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="mt-6 flex flex-col sm:flex-row gap-2">
                            <button 
                                @click="showCalcModal = false"
                                class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-bold uppercase tracking-wider transition-colors"
                            >
                                Batal
                            </button>
                            <button 
                                @click="applyCalcResults"
                                class="px-4 py-2.5 bg-slate-700 hover:bg-slate-600 text-slate-200 rounded-xl text-xs font-bold uppercase tracking-wider transition-colors text-center"
                                title="Terapkan biaya proses ke tabel simulasi hanya untuk sesi ini."
                            >
                                Terapkan Sementara
                            </button>
                            <button 
                                @click="applyAndSaveCalcResults"
                                :disabled="isSavingCalc"
                                class="flex-1 px-4 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white rounded-xl text-xs font-bold uppercase tracking-wider transition-all shadow-lg shadow-blue-600/20 text-center flex items-center justify-center gap-2"
                                title="Terapkan biaya proses ke simulasi dan simpan nilai-nilai input ini secara permanen sebagai default database."
                            >
                                <ArrowPathIcon v-if="isSavingCalc" class="h-3.5 w-3.5 animate-spin" />
                                <SparklesIcon v-else class="h-3.5 w-3.5 text-cyan-400" />
                                {{ isSavingCalc ? 'Menyimpan...' : 'Simpan & Terapkan' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
