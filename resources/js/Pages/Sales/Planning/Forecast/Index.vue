<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
import { ref, watch, computed, onMounted } from 'vue';
import { 
    MagnifyingGlassIcon, 
    ArrowUpTrayIcon,
    XMarkIcon,
    ChevronUpIcon,
    ChevronDownIcon,
    UserIcon,
    ChartBarIcon,
    TableCellsIcon,
    ArrowLeftCircleIcon,
    SparklesIcon,
    TrashIcon,
} from '@heroicons/vue/24/outline';
import { formatNumber } from '@/helpers';
import {
    Chart as ChartJS,
    CategoryScale, LinearScale, PointElement, LineElement, BarElement,
    Title, Tooltip, Legend, Filler,
} from 'chart.js';
import { Bar } from 'vue-chartjs';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title, Tooltip, Legend, Filler);

const props = defineProps({
    forecasts: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const month = ref(props.filters.month || '');
const sortField = ref(props.filters.sort || 'period');
const sortDirection = ref(props.filters.direction || 'desc');
const importModalOpen = ref(false);
const activeView = ref('chart');

// ─── Chart State ───
const chartLevel = ref('summary');
const chartData = ref(null);
const chartLoading = ref(false);
const chartBreadcrumb = ref([{ label: 'All Customers', level: 'summary' }]);
const selectedCustomerId = ref(null);
const selectedProductId = ref(null);

const loadChartData = async () => {
    chartLoading.value = true;
    try {
        const params = new URLSearchParams({
            search: search.value || '',
            level: chartLevel.value,
            month: month.value || '',
        });
        if (selectedCustomerId.value) params.set('customer_id', selectedCustomerId.value);
        if (selectedProductId.value) params.set('product_id', selectedProductId.value);
        const res = await fetch(route('sales.planning.forecast.chart-data') + '?' + params.toString());
        chartData.value = await res.json();
    } catch (e) {
        console.error('Chart fetch error:', e);
    } finally {
        chartLoading.value = false;
    }
};

const drillDown = (item) => {
    if (chartLevel.value === 'summary') {
        chartLevel.value = 'customer';
        selectedCustomerId.value = item.id;
        chartBreadcrumb.value.push({ label: item.name, level: 'customer', id: item.id });
        loadChartData();
    } else if (chartLevel.value === 'customer') {
        chartLevel.value = 'item';
        selectedProductId.value = item.id;
        chartBreadcrumb.value.push({ label: item.name, level: 'item', id: item.id });
        loadChartData();
    }
};

const drillUp = (index) => {
    const target = chartBreadcrumb.value[index];
    chartBreadcrumb.value = chartBreadcrumb.value.slice(0, index + 1);
    chartLevel.value = target.level;
    if (target.level === 'summary') { selectedCustomerId.value = null; selectedProductId.value = null; }
    else if (target.level === 'customer') { selectedProductId.value = null; }
    loadChartData();
};

onMounted(() => { loadChartData(); });

// ─── Chart.js Configs ───
const summaryChartData = computed(() => {
    if (!chartData.value || chartData.value.level !== 'summary') return null;
    const d = chartData.value.data;
    return {
        labels: d.map(c => c.name),
        datasets: [
            { label: 'Forecast', data: d.map(c => c.forecast), backgroundColor: 'rgba(139,92,246,0.7)', borderRadius: 4, barThickness: 22 },
            { label: 'Actual (Order)', data: d.map(c => c.actual), backgroundColor: 'rgba(16,185,129,0.8)', borderRadius: 4, barThickness: 22 },
        ],
    };
});

const customerChartData = computed(() => {
    if (!chartData.value || chartData.value.level !== 'customer') return null;
    const d = chartData.value.data;
    return {
        labels: d.map(p => p.name),
        datasets: [
            { label: 'Forecast', data: d.map(p => p.forecast), backgroundColor: 'rgba(139,92,246,0.7)', borderRadius: 4 },
            { label: 'Actual (Order)', data: d.map(p => p.actual), backgroundColor: 'rgba(16,185,129,0.8)', borderRadius: 4 },
        ],
    };
});

const itemChartData = computed(() => {
    if (!chartData.value || chartData.value.level !== 'item') return null;
    const d = chartData.value.data;
    return {
        labels: d.map(t => t.label),
        datasets: [
            { type: 'bar', label: 'Forecast', data: d.map(t => t.forecast), backgroundColor: 'rgba(139,92,246,0.4)', borderRadius: 3, order: 2 },
            { type: 'bar', label: 'Actual', data: d.map(t => t.actual), backgroundColor: 'rgba(16,185,129,0.7)', borderRadius: 3, order: 2 },
            { type: 'line', label: 'Cum. Forecast', data: d.map(t => t.cum_forecast), borderColor: '#8b5cf6', borderDash: [6,3], borderWidth: 2, pointRadius: 3, pointBackgroundColor: '#8b5cf6', fill: false, tension: 0.3, order: 1 },
            { type: 'line', label: 'Cum. Actual', data: d.map(t => t.cum_actual), borderColor: '#10b981', borderWidth: 2.5, pointRadius: 4, pointBackgroundColor: '#10b981', fill: false, tension: 0.3, order: 1 },
        ],
    };
});

const horizontalBarOpts = computed(() => ({
    responsive: true, maintainAspectRatio: false, indexAxis: 'y',
    onClick: (evt, elements) => { if (elements.length && chartData.value) drillDown(chartData.value.data[elements[0].index]); },
    plugins: { legend: { position: 'top', labels: { color: '#64748b', font: { size: 11, weight: 'bold' } } }, tooltip: { padding: 10 } },
    scales: {
        x: { grid: { color: 'rgba(0,0,0,0.06)' }, ticks: { color: '#64748b', font: { size: 10 } } },
        y: { grid: { display: false }, ticks: { color: '#334155', font: { size: 11, weight: '600' } } },
    },
}));

const verticalBarOpts = computed(() => ({
    responsive: true, maintainAspectRatio: false,
    onClick: (evt, elements) => { if (elements.length && chartData.value) drillDown(chartData.value.data[elements[0].index]); },
    plugins: { legend: { position: 'top', labels: { color: '#64748b', font: { size: 11, weight: 'bold' } } }, tooltip: { padding: 10 } },
    scales: {
        x: { grid: { display: false }, ticks: { color: '#334155', font: { size: 10, weight: '600' }, maxRotation: 45 } },
        y: { grid: { color: 'rgba(0,0,0,0.06)' }, ticks: { color: '#64748b', font: { size: 10 } } },
    },
}));

const comboChartOpts = computed(() => ({
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { position: 'top', labels: { color: '#64748b', font: { size: 11, weight: 'bold' } } }, tooltip: { padding: 10 } },
    scales: {
        x: { grid: { display: false }, ticks: { color: '#334155', font: { size: 10 }, maxRotation: 45 } },
        y: { grid: { color: 'rgba(0,0,0,0.06)' }, ticks: { color: '#64748b', font: { size: 10 } } },
    },
}));

// ─── AI Analysis State ───
const aiAnalyzing = ref(false);
const aiResult = ref('');
const showAiPanel = ref(false);

const runAiAnalysis = async () => {
    aiAnalyzing.value = true;
    showAiPanel.value = true;
    aiResult.value = '';
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const res = await fetch(route('sales.planning.forecast.analyze'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({ search: search.value, month: month.value }),
        });
        const data = await res.json();
        
        if (data.error) {
            aiResult.value = `### ⚠️ Error\n${data.error}`;
        } else {
            aiResult.value = data.analysis || 'Tidak ada hasil analisis dari AI.';
        }
    } catch (e) {
        aiResult.value = '### ⚠️ Connection Error\nGagal menghubungi server. Pastikan koneksi internet stabil.';
        console.error(e);
    } finally {
        aiAnalyzing.value = false;
    }
};

// ─── Deletion Logic ───
const deleteForecast = (forecast) => {
    if (confirm(`Are you sure you want to delete forecast for ${forecast.customer?.name} - ${forecast.product?.name}?`)) {
        router.delete(route('sales.planning.forecast.destroy', forecast.id), {
            onSuccess: () => {
                loadChartData();
            }
        });
    }
};

const bulkDelete = () => {
    const periodLabel = month.value ? formatMonth(month.value + '-01') : 'ALL PERIODS';
    const searchLabel = search.value ? `matching "${search.value}"` : 'all matching data';
    
    if (confirm(`⚠️ WARNING: This will PERMANENTLY delete all forecast records for ${periodLabel} ${searchLabel}.\n\nAre you sure you want to continue?`)) {
        router.post(route('sales.planning.forecast.bulk-delete'), {
            month: month.value,
            search: search.value,
        }, {
            onSuccess: () => {
                loadChartData();
            }
        });
    }
};

// Simple markdown to HTML converter
const renderMarkdown = (text) => {
    if (!text) return '';
    return text
        .replace(/### (.+)/g, '<h3 class="text-base font-bold text-slate-800 dark:text-slate-200 mt-5 mb-2">$1</h3>')
        .replace(/## (.+)/g, '<h2 class="text-lg font-bold text-slate-900 dark:text-white mt-6 mb-3">$1</h2>')
        .replace(/\*\*(.+?)\*\*/g, '<strong class="font-semibold">$1</strong>')
        .replace(/\*(.+?)\*/g, '<em>$1</em>')
        .replace(/^- (.+)/gm, '<li class="ml-4 text-sm text-slate-600 dark:text-slate-400">$1</li>')
        .replace(/(<li.*<\/li>\n?)+/g, '<ul class="list-disc space-y-1 my-2">$&</ul>')
        .replace(/\n{2,}/g, '<br/><br/>')
        .replace(/\n/g, '<br/>');
};

const fileInput = ref(null);

const form = useForm({
    file: null,
    sales_name: '',
});

const sort = (field) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }
    handleSearch();
};

const handleSearch = () => {
    router.get(route('sales.planning.forecast.index'), { 
        search: search.value, 
        month: month.value,
        sort: sortField.value,
        direction: sortDirection.value,
    }, { preserveState: true, replace: true });
};

watch([search, month], () => {
    handleSearch();
    loadChartData();
});

const openImportModal = () => {
    importModalOpen.value = true;
};

const closeImportModal = () => {
    importModalOpen.value = false;
    form.reset();
};

const submitImport = () => {
    if (form.file) {
        form.post(route('sales.planning.forecast.import'), {
            onSuccess: () => closeImportModal(),
        });
    }
};

const getAccuracyColor = (accuracy) => {
    if (accuracy >= 90) return '#10b981'; // emerald-500
    if (accuracy >= 75) return '#3b82f6'; // blue-500
    if (accuracy >= 50) return '#f59e0b'; // amber-500
    return '#ef4444'; // red-500
};

const getAccuracyClass = (accuracy) => {
    if (accuracy >= 90) return 'text-emerald-600 dark:text-emerald-400';
    if (accuracy >= 75) return 'text-blue-600 dark:text-blue-400';
    if (accuracy >= 50) return 'text-amber-600 dark:text-amber-400';
    return 'text-red-600 dark:text-red-400';
};

const formatMonth = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('id-ID', { year: 'numeric', month: 'long' });
};

const formatDateShort = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short' });
};
</script>

<template>
    <Head title="Sales Forecast" />

    <AppLayout title="Sales Forecast">
        <template #header>
            <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">
                Sales Forecast
            </h2>
        </template>

        <div class="p-4 sm:p-6 lg:p-8">
            <!-- Main Container -->
            <div class="glass-card rounded-2xl overflow-hidden p-6">
                    <!-- Actions Row -->
                    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                        <div class="flex gap-4 w-full md:w-auto">
                            <div class="relative w-full md:w-64">
                                <input 
                                    v-model="search"
                                    type="text" 
                                    placeholder="Search Customer / Product..." 
                                    class="w-full pl-10 pr-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:ring-blue-500 focus:border-blue-500"
                                >
                                <MagnifyingGlassIcon class="w-5 h-5 text-slate-400 absolute left-3 top-2.5" />
                            </div>
                            <input 
                                v-model="month"
                                type="month" 
                                class="rounded-lg border border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:ring-blue-500 focus:border-blue-500"
                            >
                        </div>
                        
                        <div class="flex items-center gap-3">
                            <!-- View Toggle -->
                            <div class="flex items-center bg-slate-200 dark:bg-slate-700 rounded-lg p-0.5">
                                <button @click="activeView = 'chart'" 
                                    :class="activeView === 'chart' ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'"
                                    class="px-3 py-1.5 rounded-md text-xs font-bold uppercase tracking-wider transition-all flex items-center gap-1">
                                    <ChartBarIcon class="w-3.5 h-3.5" /> Chart
                                </button>
                                <button @click="activeView = 'table'" 
                                    :class="activeView === 'table' ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'"
                                    class="px-3 py-1.5 rounded-md text-xs font-bold uppercase tracking-wider transition-all flex items-center gap-1">
                                    <TableCellsIcon class="w-3.5 h-3.5" /> Table
                                </button>
                            </div>
                            <a 
                                :href="route('sales.planning.forecast.export', { search, month })"
                                class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors text-sm"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                                </svg>
                                Export
                            </a>
                            <button 
                                @click="openImportModal"
                                class="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg transition-colors text-sm"
                            >
                                <ArrowUpTrayIcon class="w-4 h-4" />
                                Import
                            </button>
                            <button 
                                @click="runAiAnalysis"
                                :disabled="aiAnalyzing"
                                class="flex items-center gap-2 bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-700 hover:to-purple-700 text-white px-4 py-2 rounded-lg transition-all text-sm shadow-md hover:shadow-lg disabled:opacity-50"
                            >
                                <SparklesIcon class="w-4 h-4" :class="{ 'animate-spin': aiAnalyzing }" />
                                {{ aiAnalyzing ? 'Analyzing...' : 'AI Analysis' }}
                            </button>
                            <button 
                                @click="bulkDelete"
                                class="flex items-center gap-2 bg-red-50 hover:bg-red-100 text-red-600 px-3 py-2 rounded-lg transition-all text-sm border border-red-200"
                                title="Bulk delete based on current month/search filter"
                            >
                                <TrashIcon class="w-4 h-4" />
                                Clear Data
                            </button>
                        </div>
                    </div>

                    <!-- ═══ CHART SECTION ═══ -->
                    <div v-if="activeView === 'chart'" class="rounded-2xl p-6 mb-6 border border-slate-200 dark:border-slate-700/50 shadow-lg bg-white/50 dark:bg-slate-800/30">
                        <!-- Breadcrumb -->
                        <div class="flex items-center gap-2 mb-4 text-sm">
                            <template v-for="(crumb, idx) in chartBreadcrumb" :key="idx">
                                <span v-if="idx > 0" class="text-slate-400">/</span>
                                <button @click="drillUp(idx)" 
                                    :class="idx === chartBreadcrumb.length - 1 ? 'text-violet-600 dark:text-violet-400 font-bold' : 'text-slate-500 hover:text-violet-600 dark:hover:text-slate-300'"
                                    class="transition-colors">
                                    {{ crumb.label }}
                                </button>
                            </template>
                            <button v-if="chartBreadcrumb.length > 1" @click="drillUp(chartBreadcrumb.length - 2)" class="ml-2 p-1 rounded-full hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                                <ArrowLeftCircleIcon class="w-5 h-5 text-slate-400" />
                            </button>
                        </div>

                        <!-- KPI Cards -->
                        <div v-if="chartData && chartData.kpi" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                            <div class="bg-violet-50 dark:bg-violet-900/30 border border-violet-200 dark:border-violet-800 rounded-xl p-4 text-center">
                                <p class="text-[10px] text-violet-500 dark:text-violet-400 font-bold uppercase tracking-wider">Total Forecast</p>
                                <p class="text-2xl font-black text-violet-700 dark:text-violet-300 mt-1">{{ formatNumber(chartData.kpi.total_forecast) }}</p>
                            </div>
                            <div class="bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-xl p-4 text-center">
                                <p class="text-[10px] text-emerald-500 dark:text-emerald-400 font-bold uppercase tracking-wider">Total Actual (Order)</p>
                                <p class="text-2xl font-black text-emerald-700 dark:text-emerald-300 mt-1">{{ formatNumber(chartData.kpi.total_actual) }}</p>
                            </div>
                            <div class="border rounded-xl p-4 text-center" :class="chartData.kpi.achievement >= 90 ? 'bg-green-50 dark:bg-green-900/30 border-green-200 dark:border-green-800' : chartData.kpi.achievement >= 70 ? 'bg-amber-50 dark:bg-amber-900/30 border-amber-200 dark:border-amber-800' : 'bg-red-50 dark:bg-red-900/30 border-red-200 dark:border-red-800'">
                                <p class="text-[10px] font-bold uppercase tracking-wider" :class="chartData.kpi.achievement >= 90 ? 'text-green-500' : chartData.kpi.achievement >= 70 ? 'text-amber-500' : 'text-red-500'">Achievement</p>
                                <p class="text-2xl font-black mt-1" :class="chartData.kpi.achievement >= 90 ? 'text-green-700 dark:text-green-300' : chartData.kpi.achievement >= 70 ? 'text-amber-700 dark:text-amber-300' : 'text-red-700 dark:text-red-300'">{{ chartData.kpi.achievement }}%</p>
                            </div>
                            <div class="border rounded-xl p-4 text-center" :class="chartData.kpi.gap < 0 ? 'bg-red-50 dark:bg-red-900/30 border-red-200 dark:border-red-800' : 'bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700'">
                                <p class="text-[10px] font-bold uppercase tracking-wider" :class="chartData.kpi.gap < 0 ? 'text-red-500' : 'text-slate-500'">Gap</p>
                                <p class="text-2xl font-black mt-1" :class="chartData.kpi.gap < 0 ? 'text-red-700 dark:text-red-300' : 'text-slate-700 dark:text-slate-300'">{{ formatNumber(chartData.kpi.gap) }}</p>
                            </div>
                        </div>

                        <!-- Chart Area -->
                        <div class="relative" style="height: 380px;">
                            <div v-if="chartLoading" class="absolute inset-0 flex items-center justify-center bg-white/60 dark:bg-slate-900/60 z-10 rounded-xl">
                                <div class="flex items-center gap-3 text-slate-500">
                                    <svg class="animate-spin h-6 w-6" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    <span class="text-sm font-medium">Loading chart data...</span>
                                </div>
                            </div>
                            <Bar v-if="chartLevel === 'summary' && summaryChartData" :key="'fc-summary'" :data="summaryChartData" :options="horizontalBarOpts" />
                            <Bar v-else-if="chartLevel === 'customer' && customerChartData" :key="'fc-customer-' + selectedCustomerId" :data="customerChartData" :options="verticalBarOpts" />
                            <Bar v-else-if="chartLevel === 'item' && itemChartData" :key="'fc-item-' + selectedProductId" :data="itemChartData" :options="comboChartOpts" />
                            <div v-else-if="!chartLoading" class="flex items-center justify-center h-full text-slate-400">
                                <p class="text-sm">No forecast data available.</p>
                            </div>
                        </div>
                        <p v-if="chartLevel !== 'item'" class="text-center text-[10px] text-slate-400 mt-3 italic">Click a bar to drill down into details</p>
                    </div>

                    <!-- ═══ AI ANALYSIS PANEL ═══ -->
                    <Transition
                        enter-active-class="transition-all duration-300 ease-out"
                        enter-from-class="opacity-0 -translate-y-4 scale-95"
                        enter-to-class="opacity-100 translate-y-0 scale-100"
                        leave-active-class="transition-all duration-200 ease-in"
                        leave-from-class="opacity-100 translate-y-0 scale-100"
                        leave-to-class="opacity-0 -translate-y-2 scale-95"
                    >
                        <div v-if="showAiPanel" class="rounded-2xl p-6 mb-6 border border-violet-200 dark:border-violet-800/50 shadow-xl bg-gradient-to-br from-violet-50/80 to-purple-50/50 dark:from-violet-950/30 dark:to-purple-950/20 backdrop-blur-sm">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-2">
                                    <SparklesIcon class="w-5 h-5 text-violet-600 dark:text-violet-400" />
                                    <h3 class="text-sm font-bold text-violet-800 dark:text-violet-300 uppercase tracking-wider">AI Forecast Analysis</h3>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button v-if="!aiAnalyzing" @click="runAiAnalysis" class="text-xs text-violet-600 dark:text-violet-400 hover:text-violet-800 font-medium flex items-center gap-1 transition-colors">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        Re-analyze
                                    </button>
                                    <button @click="showAiPanel = false" class="p-1 rounded-full hover:bg-violet-200 dark:hover:bg-violet-800 transition-colors">
                                        <XMarkIcon class="w-4 h-4 text-violet-500" />
                                    </button>
                                </div>
                            </div>

                            <!-- Loading State -->
                            <div v-if="aiAnalyzing" class="flex flex-col items-center justify-center py-12 gap-4">
                                <div class="relative">
                                    <div class="w-12 h-12 rounded-full border-4 border-violet-200 dark:border-violet-800 border-t-violet-600 dark:border-t-violet-400 animate-spin"></div>
                                    <SparklesIcon class="w-5 h-5 text-violet-600 absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2" />
                                </div>
                                <div class="text-center">
                                    <p class="text-sm font-medium text-violet-700 dark:text-violet-300">AI sedang menganalisis data forecast...</p>
                                    <p class="text-xs text-violet-500 dark:text-violet-400 mt-1">Ini mungkin memerlukan beberapa detik</p>
                                </div>
                            </div>

                            <!-- Results -->
                            <div v-else-if="aiResult" class="prose prose-sm dark:prose-invert max-w-none text-slate-700 dark:text-slate-300 leading-relaxed" v-html="renderMarkdown(aiResult)"></div>
                        </div>
                    </Transition>

                    <!-- Table Wrapper -->
                    <div v-show="activeView === 'table'" class="rounded-2xl glass-card overflow-hidden">
                        <div class="overflow-x-auto overflow-y-auto max-h-[600px]">
                            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-400">
                                <thead class="bg-slate-50 dark:bg-slate-700/50 text-xs uppercase font-semibold text-slate-500 dark:text-slate-300">
                                    <tr class="border-b border-slate-200 dark:border-slate-700">
                                        <th @click="sort('period')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">
                                            <div class="flex items-center gap-1">
                                                Period
                                                <span v-if="sortField === 'period'" class="text-blue-600 dark:text-blue-400">
                                                    <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                                    <ChevronDownIcon v-else class="h-3 w-3" />
                                                </span>
                                            </div>
                                        </th>
                                        <th @click="sort('customer_name')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">
                                            <div class="flex items-center gap-1">
                                                Customer
                                                <span v-if="sortField === 'customer_name'" class="text-blue-600 dark:text-blue-400">
                                                    <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                                    <ChevronDownIcon v-else class="h-3 w-3" />
                                                </span>
                                            </div>
                                        </th>
                                        <th @click="sort('product_name')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">
                                            <div class="flex items-center gap-1">
                                                Product
                                                <span v-if="sortField === 'product_name'" class="text-blue-600 dark:text-blue-400">
                                                    <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                                    <ChevronDownIcon v-else class="h-3 w-3" />
                                                </span>
                                            </div>
                                        </th>
                                        <th @click="sort('qty_forecast')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 text-right cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">
                                            <div class="flex items-center justify-end gap-1">
                                                Qty Forecast
                                                <span v-if="sortField === 'qty_forecast'" class="text-blue-600 dark:text-blue-400">
                                                    <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                                    <ChevronDownIcon v-else class="h-3 w-3" />
                                                </span>
                                            </div>
                                        </th>
                                        <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">Qty PO (Actual)</th>
                                        <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">Accuracy</th>
                                        <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">Sales Name</th>
                                        <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">Unit</th>
                                        <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">Unit</th>
                                        <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">Notes</th>
                                        <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <tr v-for="forecast in forecasts.data" :key="forecast.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors">
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400 uppercase font-medium">
                                            {{ formatMonth(forecast.period) }}
                                        </td>
                                        <td class="px-4 py-2">
                                            <div class="text-sm font-semibold text-slate-900 dark:text-white">{{ forecast.customer?.name }}</div>
                                            <div class="text-[10px] text-slate-500 font-mono tracking-tight">{{ forecast.customer?.code }}</div>
                                        </td>
                                        <td class="px-4 py-2">
                                            <div class="text-sm font-medium text-slate-900 dark:text-white">{{ forecast.product?.name }}</div>
                                            <div class="text-[10px] text-slate-500 font-mono tracking-tight">{{ forecast.product?.sku }}</div>
                                        </td>
                                        <td class="px-4 py-2 text-right font-mono text-sm text-slate-900 dark:text-white">
                                            {{ formatNumber(forecast.qty_forecast) }}
                                        </td>
                                        <td class="px-4 py-2 text-right font-mono text-sm">
                                            <span :class="{
                                                'text-blue-500 font-bold': forecast.qty_actual > 0,
                                                'text-slate-400': forecast.qty_actual === 0
                                            }">
                                                {{ formatNumber(forecast.qty_actual) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 text-center">
                                            <div class="flex flex-col items-center">
                                                <span :class="getAccuracyClass(forecast.accuracy)" class="font-bold text-[10px] tracking-tighter">
                                                    {{ forecast.accuracy }}%
                                                </span>
                                                <div class="w-12 h-1 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden mt-0.5">
                                                    <div 
                                                        class="h-full transition-all duration-500" 
                                                        :style="{ width: forecast.accuracy + '%', backgroundColor: getAccuracyColor(forecast.accuracy) }"
                                                    ></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2">
                                            <div class="text-sm font-medium text-slate-900 dark:text-white truncate max-w-[120px]" :title="forecast.sales_name">{{ forecast.sales_name || '-' }}</div>
                                            <div class="text-[9px] text-slate-500 uppercase flex items-center gap-1" v-if="forecast.created_by">
                                                <UserIcon class="w-2.5 h-2.5" />
                                                {{ forecast.created_by_user?.name || 'User' }} • {{ formatDateShort(forecast.created_at) }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-2 text-sm text-slate-500 font-mono">{{ forecast.product?.unit?.code }}</td>
                                        <td class="px-4 py-2 text-[11px] italic text-slate-500 dark:text-slate-400 line-clamp-1 truncate max-w-[150px]" :title="forecast.notes">{{ forecast.notes || '-' }}</td>
                                        <td class="px-4 py-2 text-center">
                                            <button @click="deleteForecast(forecast)" class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all group">
                                                <TrashIcon class="w-4 h-4" />
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="forecasts.data.length === 0">
                                        <td colspan="9" class="px-4 py-12 text-center text-slate-500 whitespace-nowrap">
                                            No forecast data found. Import Excel to get started.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pagination Wrapper -->
                    <div class="mt-6">
                        <Pagination :links="forecasts.links" />
                    </div>
                </div>
            </div>

        <!-- Import Modal -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="importModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
                <div class="bg-white dark:bg-slate-800 rounded-2xl w-full max-w-md p-6 shadow-2xl border border-slate-200 dark:border-slate-700">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white">
                            Import Forecast Data
                        </h3>
                        <button @click="closeImportModal" class="text-slate-500 hover:text-slate-900 dark:text-white transition-colors">
                            <XMarkIcon class="h-6 w-6" />
                        </button>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Sales Name</label>
                        <input 
                            v-model="form.sales_name" 
                            type="text"
                            placeholder="Enter salesperson name"
                            class="w-full rounded-lg border border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:ring-blue-500 focus:border-blue-500"
                        >
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-3">Excel/CSV File</label>
                        <div class="flex flex-col items-center justify-center border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-xl p-8 hover:border-blue-500/50 transition-all relative group bg-slate-50/50 dark:bg-slate-900/50">
                            <input 
                                type="file" 
                                ref="fileInput"
                                @change="(e) => form.file = e.target.files[0]"
                                class="absolute inset-0 opacity-0 cursor-pointer z-20"
                                accept=".xlsx,.xls,.csv"
                            />
                            <div class="flex flex-col items-center transition-transform group-hover:scale-105 duration-300">
                                <div class="p-3 rounded-full bg-blue-50 dark:bg-blue-900/30 mb-3">
                                    <ArrowUpTrayIcon class="h-8 w-8 text-blue-600 dark:text-blue-400" />
                                </div>
                                <p class="text-sm text-slate-600 dark:text-slate-300 font-semibold mb-1">
                                    {{ form.file ? form.file.name : 'Click or drag file to upload' }}
                                </p>
                                <p class="text-xs text-slate-500">Maximum size: 2MB</p>
                            </div>
                            
                            <div class="mt-4 z-30 relative py-1 px-3 rounded-lg bg-blue-50 dark:bg-blue-900/40 border border-blue-100 dark:border-blue-800/50">
                                <a 
                                    :href="route('sales.planning.forecast.template')"
                                    class="flex items-center gap-1.5 text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                    Download Template
                                </a>
                            </div>
                        </div>
                        <div v-if="form.errors.file" class="text-red-500 text-xs mt-2 font-medium">{{ form.errors.file }}</div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button 
                            @click="submitImport"
                            :disabled="!form.file || form.processing"
                            class="flex-1 rounded-xl bg-blue-600 py-3 text-sm font-bold text-white hover:bg-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-lg shadow-blue-500/20"
                        >
                            {{ form.processing ? 'Importing...' : 'Start Import' }}
                        </button>
                        <button 
                            @click="closeImportModal"
                            class="flex-1 rounded-xl bg-slate-100 dark:bg-slate-700 py-3 text-sm font-bold text-slate-600 dark:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-600 transition-all font-mono uppercase tracking-wider"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </AppLayout>
</template>
