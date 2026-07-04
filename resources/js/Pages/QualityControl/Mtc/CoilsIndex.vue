<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    MagnifyingGlassIcon,
    ArrowPathIcon,
    SparklesIcon,
    DocumentTextIcon,
    CheckCircleIcon,
    XCircleIcon,
    ExclamationTriangleIcon,
    QuestionMarkCircleIcon,
    SunIcon,
    MoonIcon
} from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';

const props = defineProps({
    items: Object,
    products: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');
const complianceStatus = ref(props.filters.compliance_status || '');
const productId = ref(props.filters.product_id || '');

const applyFilters = () => {
    router.get(route('qc.mtc.coils'), {
        search: search.value,
        compliance_status: complianceStatus.value,
        product_id: productId.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

watch(search, debounce(() => {
    applyFilters();
}, 400));

watch([complianceStatus, productId], () => {
    applyFilters();
});

const clearFilters = () => {
    search.value = '';
    complianceStatus.value = '';
    productId.value = '';
    router.get(route('qc.mtc.coils'));
};

const getStatusBadge = (status) => {
    const isLight = isLightMode.value;
    switch (status) {
        case 'pass':
            return {
                bg: isLight
                    ? 'bg-emerald-50 border-emerald-200 text-emerald-700'
                    : 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400',
                text: 'Memenuhi Standar',
                icon: CheckCircleIcon
            };
        case 'fail':
            return {
                bg: isLight
                    ? 'bg-rose-50 border-rose-200 text-rose-700 font-black'
                    : 'bg-rose-500/10 border-rose-500/20 text-rose-400 font-black',
                text: 'Di Luar Standar',
                icon: XCircleIcon
            };
        case 'warning':
            return {
                bg: isLight
                    ? 'bg-amber-50 border-amber-250 text-amber-700'
                    : 'bg-amber-500/10 border-amber-500/20 text-amber-400',
                text: 'Peringatan',
                icon: ExclamationTriangleIcon
            };
        default:
            return {
                bg: isLight
                    ? 'bg-slate-100 border-slate-200 text-slate-600'
                    : 'bg-slate-500/10 border-slate-500/20 text-slate-400',
                text: 'Belum Dicek',
                icon: QuestionMarkCircleIcon
            };
    }
};

const formatChem = (val) => {
    if (val === null || val === undefined) return '-';
    return Number(val).toFixed(4);
};

// --- Theme Reactive Sync ---
const isLightMode = ref(false);
const toggleTheme = () => {
    isLightMode.value = !isLightMode.value;
    if (isLightMode.value) {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    } else {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    }
};

let observer;
onMounted(() => {
    isLightMode.value = !document.documentElement.classList.contains('dark');
    observer = new MutationObserver(() => {
        isLightMode.value = !document.documentElement.classList.contains('dark');
    });
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
});

onUnmounted(() => {
    if (observer) observer.disconnect();
});
</script>

<template>
    <AppLayout title="MTC Coils Registry" :render-header="false">
        <div class="p-6 space-y-6 bg-slate-50 dark:bg-[#030310] min-h-screen text-slate-800 dark:text-slate-100 font-sans transition-colors duration-300 relative">
            
            <!-- Dynamic Background -->
            <div class="fixed inset-0 z-0 pointer-events-none">
                <div class="absolute inset-0 bg-gradient-to-br from-cyan-500/5 to-slate-100 dark:from-cyan-955/20 dark:to-[#030310]"></div>
            </div>

            <div class="relative z-10 space-y-6 max-w-7xl mx-auto">
                <!-- Header -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b border-slate-200 dark:border-cyan-500/10 pb-6">
                    <div>
                        <h1 class="text-3xl font-extrabold tracking-wider text-transparent bg-clip-text bg-gradient-to-r from-cyan-605 via-slate-800 to-cyan-650 dark:from-cyan-400 dark:to-emerald-400 uppercase font-mono">
                            MTC Coils Registry
                        </h1>
                        <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">
                            Daftar riwayat seluruh coil baja yang diekstraksi dari sertifikat Mill Test Certificate (MTC)
                        </p>
                    </div>

                    <div class="flex items-center gap-6">
                        
                    </div>
                </div>

                <!-- Advanced Search & Filter Bar -->
                <div class="bg-white/75 dark:bg-gradient-to-r dark:from-slate-950 dark:to-slate-900 border border-slate-200 dark:border-slate-800/80 p-5 rounded-2xl shadow-sm dark:shadow-xl space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Search Box -->
                        <div class="relative">
                            <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-500 uppercase tracking-wider font-mono mb-1.5">Cari Coil / Heat No</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <MagnifyingGlassIcon class="h-4 w-4 text-slate-500" />
                                </span>
                                <input
                                    type="text"
                                    v-model="search"
                                    placeholder="Cari Coil No atau Heat No..."
                                    class="w-full bg-white dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-xl pl-9 pr-4 py-2 text-sm text-slate-800 dark:text-slate-200 focus:outline-none focus:border-cyan-500 transition"
                                />
                            </div>
                        </div>

                        <!-- Product Filter -->
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-500 uppercase tracking-wider font-mono mb-1.5">Filter Produk Database</label>
                            <select
                                v-model="productId"
                                class="w-full bg-white dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-800 dark:text-slate-200 focus:outline-none focus:border-cyan-500 transition"
                            >
                                <option value="" class="bg-white dark:bg-slate-900">Semua Produk...</option>
                                <option v-for="p in products" :key="p.id" :value="p.id" class="bg-white dark:bg-slate-900 text-slate-805">{{ p.name }}</option>
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-555 uppercase tracking-wider font-mono mb-1.5">Filter Status Kepatuhan</label>
                            <select
                                v-model="complianceStatus"
                                class="w-full bg-white dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-805 dark:text-slate-200 focus:outline-none focus:border-cyan-500 transition"
                            >
                                <option value="" class="bg-white dark:bg-slate-900">Semua Status...</option>
                                <option value="pass" class="bg-white dark:bg-slate-900">Memenuhi Standar (Pass)</option>
                                <option value="fail" class="bg-white dark:bg-slate-900">Di Luar Standar (Fail)</option>
                                <option value="warning" class="bg-white dark:bg-slate-900">Peringatan (Warning)</option>
                                <option value="unchecked" class="bg-white dark:bg-slate-900">Belum Dicek</option>
                            </select>
                        </div>

                        <!-- Clear filters button -->
                        <div class="flex items-end">
                            <button
                                @click="clearFilters"
                                class="w-full flex items-center justify-center gap-2 px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-855 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:border-slate-350 dark:hover:border-slate-650 text-xs font-bold uppercase tracking-wider font-mono text-slate-600 dark:text-slate-300 rounded-xl transition shadow-sm cursor-pointer"
                            >
                                <ArrowPathIcon class="w-4 h-4" />
                                Reset Filter
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Registry Table Card -->
                <div class="bg-white/75 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-2xl shadow-sm dark:shadow-2xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-100 dark:bg-slate-900/40 border-b border-slate-200 dark:border-slate-800 text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider font-mono">
                                    <th class="px-6 py-4">COIL NO (PRODUCT NO)</th>
                                    <th class="px-6 py-4">HEAT NO</th>
                                    <th class="px-6 py-4">DIMENSI (SIZE)</th>
                                    <th class="px-6 py-4">BERAT (KG)</th>
                                    <th class="px-6 py-4">PRODUK USICS</th>
                                    <th class="px-6 py-4">ANALISIS KIMIA (%)</th>
                                    <th class="px-6 py-4">KEPATUHAN (COMPLIANCE)</th>
                                    <th class="px-6 py-4">MTC REFERENSI</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-900/60 text-slate-700 dark:text-slate-200">
                                <tr 
                                    v-for="item in items.data" 
                                    :key="item.id"
                                    class="hover:bg-slate-50 dark:hover:bg-slate-900/30 transition-colors"
                                >
                                    <td class="px-6 py-4 font-semibold text-slate-900 dark:text-slate-200 font-mono text-xs">
                                        {{ item.product_no || '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-xs font-mono text-slate-600 dark:text-slate-300">
                                        {{ item.heat_no || '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-xs text-slate-600 dark:text-slate-300 font-mono">
                                        {{ item.size || '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-xs text-slate-600 dark:text-slate-300 font-mono">
                                        {{ item.weight_kg ? Number(item.weight_kg).toLocaleString('id-ID') : '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-xs text-slate-500 dark:text-slate-400 max-w-[200px] truncate" :title="item.product?.name">
                                        {{ item.product ? item.product.name : 'Tidak Terpetakan' }}
                                    </td>
                                    <td class="px-6 py-4 text-[10px] font-mono text-slate-500 dark:text-slate-400 space-y-0.5">
                                        <div>C: <span class="text-slate-705 dark:text-slate-300">{{ formatChem(item.chemical_ladle?.C) }}</span> | Mn: <span class="text-slate-705 dark:text-slate-300">{{ formatChem(item.chemical_ladle?.Mn) }}</span></div>
                                        <div>P: <span class="text-slate-705 dark:text-slate-300">{{ formatChem(item.chemical_ladle?.P) }}</span> | S: <span class="text-slate-705 dark:text-slate-300">{{ formatChem(item.chemical_ladle?.S) }}</span></div>
                                    </td>
                                    <td class="px-6 py-4 text-xs">
                                        <div 
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full border text-[10px] font-bold uppercase tracking-wider font-mono animate-pulse"
                                            :class="getStatusBadge(item.compliance_status).bg"
                                            :title="item.compliance_notes"
                                        >
                                            <component :is="getStatusBadge(item.compliance_status).icon" class="w-3.5 h-3.5" />
                                            {{ getStatusBadge(item.compliance_status).text }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-xs">
                                        <Link 
                                            :href="route('qc.mtc.show', item.document.id)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 border border-slate-200 dark:bg-slate-900 dark:border-slate-800 rounded-lg text-xs font-medium text-slate-650 dark:text-slate-300 hover:text-cyan-600 dark:hover:text-cyan-400 hover:border-cyan-500/40 transition cursor-pointer"
                                        >
                                            <DocumentTextIcon class="w-4 h-4" />
                                            <span>{{ item.document.certificate_number || 'Buka Dokumen' }}</span>
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
