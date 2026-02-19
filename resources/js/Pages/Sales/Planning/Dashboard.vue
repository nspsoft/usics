<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';
import axios from 'axios';
import { 
    ChartBarIcon, 
    CalendarDaysIcon, 
    ClockIcon, 
    TruckIcon,
    XMarkIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
    BanknotesIcon,
    ExclamationTriangleIcon,
    DocumentTextIcon,
    UserGroupIcon,
    ChevronRightIcon,
    LightBulbIcon,
    CheckCircleIcon,
    ExclamationCircleIcon,
    ShieldExclamationIcon,
    InformationCircleIcon,
} from '@heroicons/vue/24/outline';
import { formatNumber, formatCurrency } from '@/helpers';
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
  LineElement,
  PointElement,
  Filler,
} from 'chart.js';
import { Bar } from 'vue-chartjs';

ChartJS.register(CategoryScale, LinearScale, BarElement, LineElement, PointElement, Title, Tooltip, Legend, Filler);

const props = defineProps({
    chartData: Array,
    stats: Object,
    month: String,
    topCustomers: Array,
    bottomCustomers: Array,
    deliveryTimeline: Array,
    analysis: Object,
});

const currentMonth = ref(props.month);
const showDetails = ref(false);
const detailsData = ref([]);
const selectedCustomer = ref(null);
const isLoadingDetails = ref(false);
const animatedValues = ref({});
const showDeliveryModal = ref(false);
const selectedDeliveryDay = ref(null);
const showInfoModal = ref(false);

watch(currentMonth, (val) => {
    router.get(route('sales.planning.dashboard'), { month: val }, { preserveState: true, replace: true });
});

// Animate counters on mount
onMounted(() => {
    const targets = {
        achievement: props.stats?.achievement_pct ?? 0,
        forecast: props.stats?.total_forecast_qty ?? 0,
        actual: props.stats?.total_actual_qty ?? 0,
        revenue: props.stats?.revenue_this_month ?? 0,
    };
    animatedValues.value = { achievement: 0, forecast: 0, actual: 0, revenue: 0 };
    
    const duration = 1200;
    const start = performance.now();
    const animate = (now) => {
        const progress = Math.min((now - start) / duration, 1);
        const ease = 1 - Math.pow(1 - progress, 3); // easeOutCubic
        animatedValues.value = {
            achievement: Math.round(targets.achievement * ease * 10) / 10,
            forecast: Math.round(targets.forecast * ease),
            actual: Math.round(targets.actual * ease),
            revenue: Math.round(targets.revenue * ease),
        };
        if (progress < 1) requestAnimationFrame(animate);
    };
    requestAnimationFrame(animate);
});

const handleChartClick = (event, elements) => {
    if (elements.length > 0) {
        const index = elements[0].index;
        const customer = props.chartData[index];
        openDetails(customer);
    }
};

const openDetails = async (customer) => {
    if (!customer) return;
    selectedCustomer.value = customer;
    showDetails.value = true;
    isLoadingDetails.value = true;
    detailsData.value = [];
    
    try {
        const response = await axios.get(route('sales.planning.dashboard.details'), {
            params: { customer_id: customer.id, month: currentMonth.value }
        });
        detailsData.value = response.data;
    } catch (error) {
        console.error('Failed to fetch details:', error);
        // Close modal on error so user isn't stuck with blur
        showDetails.value = false;
        selectedCustomer.value = null;
    } finally {
        isLoadingDetails.value = false;
    }
};

const closeDetails = () => {
    showDetails.value = false;
    selectedCustomer.value = null;
};

const openDeliveryDetails = (day) => {
    if (!day || !day.items.length) return;
    selectedDeliveryDay.value = day;
    showDeliveryModal.value = true;
};

const closeDeliveryDetails = () => {
    showDeliveryModal.value = false;
    selectedDeliveryDay.value = null;
};

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    onClick: handleChartClick,
    interaction: { mode: 'index', intersect: false },
    onHover: (event, elements) => {
        if (event.native?.target) event.native.target.style.cursor = elements.length > 0 ? 'pointer' : 'default';
    },
    plugins: {
        legend: { position: 'top', labels: { color: '#94a3b8', font: { size: 11, weight: '600' }, usePointStyle: true, pointStyle: 'rectRounded' } },
        title: { display: false },
        tooltip: {
            backgroundColor: 'rgba(15,23,42,0.95)',
            titleFont: { size: 13, weight: 'bold' },
            bodyFont: { size: 12 },
            padding: 12,
            cornerRadius: 10,
            mode: 'index',
            intersect: false,
            callbacks: {
                afterBody: () => '\n👉 Klik untuk lihat per-item',
                label: (ctx) => {
                    const val = formatNumber(ctx.raw || 0);
                    return ` ${ctx.dataset.label}: ${val}`;
                }
            }
        }
    },
    scales: {
        x: { grid: { display: false }, ticks: { color: '#64748b', font: { size: 10, weight: '600' }, maxRotation: 45 } },
        y: { grid: { color: 'rgba(148,163,184,0.1)' }, ticks: { color: '#64748b', font: { size: 10 }, callback: (v) => formatNumber(v) } },
    },
};

const chartDataComputed = computed(() => {
    if (!props.chartData) return { labels: [], datasets: [] };
    return {
        labels: props.chartData.map(d => d.customer || 'Unknown'),
        datasets: [
            {
                label: 'Forecast',
                backgroundColor: 'rgba(139,92,246,0.7)', // Purple
                borderRadius: 4, barThickness: 18,
                data: props.chartData.map(d => Number(d.forecast || 0))
            },
            {
                label: 'Sales Order',
                backgroundColor: 'rgba(16,185,129,0.8)', // Emerald
                borderRadius: 4, barThickness: 18,
                data: props.chartData.map(d => Number(d.actual || 0))
            },
            {
                label: 'Delivery',
                backgroundColor: 'rgba(245, 158, 11, 0.8)', // Amber
                borderRadius: 4, barThickness: 18,
                data: props.chartData.map(d => Number(d.delivery || 0))
            }
        ]
    };
});

const achievementColor = computed(() => {
    const v = props.stats?.achievement_pct ?? 0;
    if (v >= 90) return { ring: 'text-emerald-500', bg: 'bg-emerald-500', label: 'Excellent', gradient: 'from-emerald-500 to-emerald-600' };
    if (v >= 70) return { ring: 'text-blue-500', bg: 'bg-blue-500', label: 'Good', gradient: 'from-blue-500 to-blue-600' };
    if (v >= 50) return { ring: 'text-amber-500', bg: 'bg-amber-500', label: 'Average', gradient: 'from-amber-500 to-amber-600' };
    return { ring: 'text-red-500', bg: 'bg-red-500', label: 'Critical', gradient: 'from-red-500 to-red-600' };
});

const trendIcon = (current, prev) => {
    if (current > prev) return 'up';
    if (current < prev) return 'down';
    return 'flat';
};

const trendPct = (current, prev) => {
    if (!prev || prev === 0) return 0;
    return Math.round(((current - prev) / prev) * 100);
};

// Use helper for consistency
const fmtNum = formatNumber;
const fmtCurrency = formatCurrency;

const getAchievementBarColor = (pct) => {
    if (pct >= 90) return 'bg-emerald-500';
    if (pct >= 70) return 'bg-blue-500';
    if (pct >= 50) return 'bg-amber-500';
    return 'bg-red-500';
};

const getAchievementTextColor = (pct) => {
    if (pct >= 90) return 'text-emerald-600 dark:text-emerald-400';
    if (pct >= 70) return 'text-blue-600 dark:text-blue-400';
    if (pct >= 50) return 'text-amber-600 dark:text-amber-400';
    return 'text-red-600 dark:text-red-400';
};
</script>

<template>
    <Head title="Sales Planning Dashboard" />

    <AppLayout title="Sales Planning Dashboard" :renderHeader="false">
        <div class="p-6 bg-slate-50 dark:bg-slate-950 min-h-[calc(100vh-130px)]" v-if="stats">
            <!-- ═══ CUSTOM PAGE HEADER ═══ -->
            <div class="mb-8 flex justify-between items-end border-b border-slate-200 dark:border-slate-800 pb-6">
                <div class="flex items-center gap-4">
                    <h1 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">
                        Sales Planning Dashboard
                    </h1>
                    <button @click="showInfoModal = true" class="flex items-center gap-2 px-4 py-2 bg-indigo-500 text-white rounded-xl hover:bg-indigo-600 transition-all shadow-lg shadow-indigo-500/20 active:scale-95" title="Panduan Sumber Data">
                        <DocumentTextIcon class="w-5 h-5" />
                        <span class="text-sm font-bold">Data Info</span>
                    </button>
                </div>
                <div class="flex items-center gap-4 bg-white dark:bg-slate-900 p-2 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400 ml-2">Period</span>
                    <input 
                        v-model="currentMonth"
                        type="month" 
                        class="rounded-xl border border-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 text-sm px-4 py-2"
                    >
                </div>
            </div>

            <div class="space-y-6">
                
                <!-- ═══ ROW 1: KPI CARDS ═══ -->
                <div class="grid grid-cols-2 lg:grid-cols-6 gap-4">
                    
                    <!-- Achievement Gauge -->
                    <div class="col-span-2 bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 rounded-full blur-[60px] opacity-20" :class="achievementColor.bg"></div>
                        <div class="relative z-10 flex items-center gap-6">
                            <!-- SVG Gauge -->
                            <div class="relative w-28 h-28 shrink-0">
                                <svg class="w-28 h-28 -rotate-90" viewBox="0 0 120 120">
                                    <circle cx="60" cy="60" r="50" stroke-width="10" fill="none" class="stroke-slate-200 dark:stroke-slate-800"></circle>
                                    <circle cx="60" cy="60" r="50" stroke-width="10" fill="none" stroke-linecap="round"
                                        :class="achievementColor.ring.replace('text-', 'stroke-')"
                                        :stroke-dasharray="314"
                                        :stroke-dashoffset="314 - (314 * Math.min(stats.achievement_pct, 100) / 100)"
                                        style="transition: stroke-dashoffset 1.2s ease-out"
                                    ></circle>
                                </svg>
                                <div class="absolute inset-0 flex flex-col items-center justify-center">
                                    <span class="text-2xl font-black" :class="achievementColor.ring">{{ animatedValues.achievement || 0 }}%</span>
                                </div>
                            </div>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Achievement</p>
                                <p class="text-sm font-bold" :class="achievementColor.ring">{{ achievementColor.label }}</p>
                                <p class="text-xs text-slate-500 mt-1">Target: {{ fmtNum(stats.total_forecast_qty) }}</p>
                                <p class="text-xs text-slate-500">Actual: {{ fmtNum(stats.total_actual_qty) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Total Forecast -->
                    <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200 dark:border-slate-800 shadow-sm">
                        <div class="flex justify-between items-start mb-3">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Forecast</p>
                            <div class="p-2 bg-violet-500/10 rounded-lg"><ChartBarIcon class="w-4 h-4 text-violet-500" /></div>
                        </div>
                        <p class="text-2xl font-black text-slate-900 dark:text-white">{{ fmtNum(animatedValues.forecast) }}</p>
                        <div class="mt-2 flex items-center gap-1" v-if="stats.prev_forecast_qty">
                            <component :is="trendIcon(stats.total_forecast_qty, stats.prev_forecast_qty) === 'up' ? ArrowTrendingUpIcon : ArrowTrendingDownIcon" 
                                class="w-3.5 h-3.5" 
                                :class="trendIcon(stats.total_forecast_qty, stats.prev_forecast_qty) === 'up' ? 'text-emerald-500' : 'text-red-500'" />
                            <span class="text-[10px] font-bold" :class="trendIcon(stats.total_forecast_qty, stats.prev_forecast_qty) === 'up' ? 'text-emerald-500' : 'text-red-500'">
                                {{ trendPct(stats.total_forecast_qty, stats.prev_forecast_qty) }}% vs prev
                            </span>
                        </div>
                    </div>

                    <!-- Total Actual -->
                    <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200 dark:border-slate-800 shadow-sm">
                        <div class="flex justify-between items-start mb-3">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Actual</p>
                            <div class="p-2 bg-emerald-500/10 rounded-lg"><TruckIcon class="w-4 h-4 text-emerald-500" /></div>
                        </div>
                        <p class="text-2xl font-black text-slate-900 dark:text-white">{{ fmtNum(animatedValues.actual) }}</p>
                        <div class="mt-2 flex items-center gap-1" v-if="stats.prev_actual_qty">
                            <component :is="trendIcon(stats.total_actual_qty, stats.prev_actual_qty) === 'up' ? ArrowTrendingUpIcon : ArrowTrendingDownIcon" 
                                class="w-3.5 h-3.5" 
                                :class="trendIcon(stats.total_actual_qty, stats.prev_actual_qty) === 'up' ? 'text-emerald-500' : 'text-red-500'" />
                            <span class="text-[10px] font-bold" :class="trendIcon(stats.total_actual_qty, stats.prev_actual_qty) === 'up' ? 'text-emerald-500' : 'text-red-500'">
                                {{ trendPct(stats.total_actual_qty, stats.prev_actual_qty) }}% vs prev
                            </span>
                        </div>
                    </div>

                    <!-- Revenue -->
                    <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200 dark:border-slate-800 shadow-sm">
                        <div class="flex justify-between items-start mb-3">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Revenue (SO)</p>
                            <div class="p-2 bg-blue-500/10 rounded-lg"><BanknotesIcon class="w-4 h-4 text-blue-500" /></div>
                        </div>
                        <p class="text-lg font-black text-slate-900 dark:text-white">{{ fmtCurrency(animatedValues.revenue) }}</p>
                        <div class="mt-2 flex items-center gap-1" v-if="stats.revenue_prev_month">
                            <component :is="stats.revenue_change_pct >= 0 ? ArrowTrendingUpIcon : ArrowTrendingDownIcon" 
                                class="w-3.5 h-3.5" 
                                :class="stats.revenue_change_pct >= 0 ? 'text-emerald-500' : 'text-red-500'" />
                            <span class="text-[10px] font-bold" :class="stats.revenue_change_pct >= 0 ? 'text-emerald-500' : 'text-red-500'">
                                {{ stats.revenue_change_pct }}% vs prev
                            </span>
                        </div>
                    </div>

                    <!-- Delayed -->
                    <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200 dark:border-slate-800 shadow-sm" :class="stats.delayed_schedules > 0 ? 'ring-1 ring-red-500/20' : ''">
                        <div class="flex justify-between items-start mb-3">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Delayed</p>
                            <div class="p-2 rounded-lg" :class="stats.delayed_schedules > 0 ? 'bg-red-500/10' : 'bg-slate-100 dark:bg-slate-800'">
                                <ExclamationTriangleIcon class="w-4 h-4" :class="stats.delayed_schedules > 0 ? 'text-red-500' : 'text-slate-400'" />
                            </div>
                        </div>
                        <p class="text-2xl font-black" :class="stats.delayed_schedules > 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-900 dark:text-white'">{{ stats.delayed_schedules }}</p>
                        <p class="text-[10px] text-slate-400 mt-1">schedules overdue</p>
                    </div>
                </div>

                <!-- ═══ ROW 2: CHART + RANKING ═══ -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Chart -->
                    <div class="lg:col-span-2 bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200 dark:border-slate-800 shadow-sm">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-base font-bold text-slate-800 dark:text-white">Forecast vs Actual Performance</h3>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Click bar to drill down</span>
                        </div>
                        <div class="h-80 cursor-pointer">
                            <Bar :data="chartDataComputed" :options="chartOptions" />
                        </div>

                        <!-- ═══ EXECUTIVE ANALYSIS & RECOMMENDATIONS ═══ -->
                        <div v-if="analysis" class="mt-8 pt-6 border-t border-slate-100 dark:border-slate-800">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <!-- Analysis Summary -->
                                <div>
                                    <div class="flex items-center gap-2 mb-4">
                                        <div class="p-1.5 bg-indigo-500/10 rounded-lg">
                                            <ChartBarIcon class="w-4 h-4 text-indigo-500" />
                                        </div>
                                        <h4 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">Resume Analisa</h4>
                                    </div>
                                    <div class="space-y-4">
                                        <div v-for="(insight, idx) in analysis.insights" :key="idx" class="flex gap-3">
                                            <div class="mt-0.5">
                                                <div class="w-1.5 h-1.5 rounded-full bg-slate-400 mt-1.5"></div>
                                            </div>
                                            <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed font-medium">
                                                {{ insight }}
                                            </p>
                                        </div>
                                        <div class="p-3 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-dashed border-slate-200 dark:border-slate-700">
                                            <p class="text-[10px] text-slate-500 uppercase font-black mb-1">Gap Terbesar</p>
                                            <p class="text-xs font-bold text-slate-900 dark:text-white">
                                                <span class="text-indigo-500">{{ analysis.top_gap_customer }}</span> memiliki selisih forecast vs realisasi tertinggi bulan ini.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Actionable Recommendations -->
                                <div>
                                    <div class="flex items-center gap-2 mb-4">
                                        <div class="p-1.5 bg-amber-500/10 rounded-lg">
                                            <LightBulbIcon class="w-4 h-4 text-amber-500" />
                                        </div>
                                        <h4 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">Rekomendasi Perbaikan</h4>
                                    </div>
                                    <div class="space-y-3">
                                        <div v-for="(rec, idx) in analysis.recommendations" :key="idx" 
                                            class="p-3 rounded-xl border transition-all hover:shadow-md"
                                            :class="[
                                                rec.type === 'danger' ? 'bg-red-50 dark:bg-red-900/10 border-red-100 dark:border-red-900/20' : 
                                                rec.type === 'warning' ? 'bg-amber-50 dark:bg-amber-900/10 border-amber-100 dark:border-amber-900/20' : 
                                                rec.type === 'success' ? 'bg-emerald-50 dark:bg-emerald-900/10 border-emerald-100 dark:border-emerald-900/20' : 
                                                'bg-blue-50 dark:bg-blue-900/10 border-blue-100 dark:border-blue-900/20'
                                            ]">
                                            <div class="flex items-start gap-3">
                                                <component :is="
                                                    rec.type === 'danger' ? ShieldExclamationIcon : 
                                                    rec.type === 'warning' ? ExclamationCircleIcon : 
                                                    rec.type === 'success' ? CheckCircleIcon : 
                                                    InformationCircleIcon
                                                " class="w-4 h-4 mt-0.5" :class="[
                                                    rec.type === 'danger' ? 'text-red-600' : 
                                                    rec.type === 'warning' ? 'text-amber-600' : 
                                                    rec.type === 'success' ? 'text-emerald-600' : 
                                                    'text-blue-600'
                                                ]" />
                                                <div>
                                                    <p class="text-[11px] font-black uppercase tracking-tight" :class="[
                                                        rec.type === 'danger' ? 'text-red-700 dark:text-red-400' : 
                                                        rec.type === 'warning' ? 'text-amber-700 dark:text-amber-400' : 
                                                        rec.type === 'success' ? 'text-emerald-700 dark:text-emerald-400' : 
                                                        'text-blue-700 dark:text-blue-400'
                                                    ]">{{ rec.title }}</p>
                                                    <p class="text-[11px] text-slate-600 dark:text-slate-400 mt-0.5 leading-relaxed font-medium">{{ rec.text }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top / Bottom Customers -->
                    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                        <div class="p-5 border-b border-slate-100 dark:border-slate-800">
                            <h3 class="text-base font-bold text-slate-800 dark:text-white flex items-center gap-2">
                                <UserGroupIcon class="w-5 h-5 text-indigo-500" /> Customer Ranking
                            </h3>
                        </div>
                        <div class="divide-y divide-slate-100 dark:divide-slate-800">
                            <!-- Top 5 -->
                            <div class="p-4">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-500 mb-3">🏆 Top Performers</p>
                                <div v-for="(c, i) in topCustomers" :key="'top-'+i" 
                                    class="flex items-center gap-3 py-2 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 rounded-lg px-2 -mx-2 transition-colors"
                                    @click="openDetails(c)">
                                    <span class="w-6 h-6 rounded-full bg-emerald-500/10 text-emerald-600 text-[10px] font-black flex items-center justify-center">{{ i + 1 }}</span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-semibold text-slate-700 dark:text-slate-300 truncate">{{ c.customer }}</p>
                                        <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-1.5 mt-1">
                                            <div :class="getAchievementBarColor(c.achievement)" class="h-1.5 rounded-full transition-all duration-700" :style="{ width: Math.min(c.achievement, 100) + '%' }"></div>
                                        </div>
                                    </div>
                                    <span class="text-xs font-bold shrink-0" :class="getAchievementTextColor(c.achievement)">{{ c.achievement }}%</span>
                                </div>
                                <p v-if="!topCustomers?.length" class="text-xs text-slate-400 italic">No data</p>
                            </div>
                            <!-- Bottom 5 -->
                            <div class="p-4">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-red-500 mb-3">⚠️ Needs Attention</p>
                                <div v-for="(c, i) in bottomCustomers" :key="'bot-'+i" 
                                    class="flex items-center gap-3 py-2 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 rounded-lg px-2 -mx-2 transition-colors"
                                    @click="openDetails(c)">
                                    <span class="w-6 h-6 rounded-full bg-red-500/10 text-red-600 text-[10px] font-black flex items-center justify-center">{{ i + 1 }}</span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-semibold text-slate-700 dark:text-slate-300 truncate">{{ c.customer }}</p>
                                        <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-1.5 mt-1">
                                            <div :class="getAchievementBarColor(c.achievement)" class="h-1.5 rounded-full transition-all duration-700" :style="{ width: Math.min(c.achievement, 100) + '%' }"></div>
                                        </div>
                                    </div>
                                    <span class="text-xs font-bold shrink-0" :class="getAchievementTextColor(c.achievement)">{{ c.achievement }}%</span>
                                </div>
                                <p v-if="!bottomCustomers?.length" class="text-xs text-slate-400 italic">No data</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══ ROW 3: DELIVERY TIMELINE + AR SUMMARY ═══ -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Delivery Timeline -->
                    <div class="lg:col-span-2 bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200 dark:border-slate-800 shadow-sm">
                        <div class="flex justify-between items-center mb-5">
                            <h3 class="text-base font-bold text-slate-800 dark:text-white flex items-center gap-2">
                                <CalendarDaysIcon class="w-5 h-5 text-amber-500" /> Delivery Timeline (7 Days)
                            </h3>
                            <span class="px-2.5 py-1 bg-amber-500/10 rounded-lg text-[10px] font-bold text-amber-600">{{ stats.upcoming_schedules }} upcoming</span>
                        </div>
                        
                        <div v-if="deliveryTimeline?.length" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-7 gap-4">
                            <div v-for="day in deliveryTimeline" :key="day.date" 
                                class="rounded-2xl p-5 border transition-all duration-300 hover:shadow-xl cursor-pointer group flex flex-col min-h-[180px]"
                                :class="day.is_today ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20 ring-1 ring-indigo-500/20' : 'border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50'"
                                @click="openDeliveryDetails(day)">
                                <div class="text-center mb-3">
                                    <p class="text-xs font-bold uppercase tracking-wider mb-0.5" :class="day.is_today ? 'text-indigo-600' : 'text-slate-400'">{{ day.day_label }}</p>
                                    <p class="text-xl font-black" :class="day.is_today ? 'text-indigo-600' : 'text-slate-800 dark:text-slate-200'">{{ day.date_label }}</p>
                                </div>
                                <div class="text-center mb-4">
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold" 
                                        :class="day.is_today ? 'bg-indigo-500 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-400'">
                                        {{ day.count }} delivery
                                    </span>
                                </div>
                                <div class="space-y-1.5 flex-1">
                                    <div v-for="(item, idx) in day.items.slice(0, 3)" :key="idx" class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                                        <span class="font-semibold text-slate-700 dark:text-slate-300">{{ item.customer }}</span>
                                    </div>
                                    <p v-if="day.count > 3" class="text-[11px] text-indigo-500 font-bold italic group-hover:underline mt-1">+{{ day.count - 3 }} more</p>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-10">
                            <CalendarDaysIcon class="w-10 h-10 text-slate-300 dark:text-slate-600 mx-auto mb-3" />
                            <p class="text-sm text-slate-400">No scheduled deliveries in the next 7 days</p>
                        </div>
                    </div>

                    <!-- AR Summary -->
                    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                        <div class="p-5 border-b border-slate-100 dark:border-slate-800">
                            <h3 class="text-base font-bold text-slate-800 dark:text-white flex items-center gap-2">
                                <DocumentTextIcon class="w-5 h-5 text-teal-500" /> Accounts Receivable
                            </h3>
                        </div>
                        <div class="p-5 space-y-4">
                            <!-- Outstanding AR -->
                            <div class="p-4 rounded-xl bg-gradient-to-r from-red-500/5 to-red-500/10 border border-red-100 dark:border-red-900/30">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-red-500 mb-1">Total Outstanding</p>
                                <p class="text-xl font-black text-red-600 dark:text-red-400">{{ fmtCurrency(stats.total_ar) }}</p>
                                <p class="text-[10px] text-slate-500 mt-1">{{ stats.open_invoices_count }} open invoices</p>
                            </div>

                            <!-- Overdue -->
                            <div class="flex items-center gap-4 p-3 rounded-xl" :class="stats.overdue_invoices > 0 ? 'bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800' : 'bg-slate-50 dark:bg-slate-800/50'">
                                <div class="p-2 rounded-lg" :class="stats.overdue_invoices > 0 ? 'bg-amber-500/20' : 'bg-slate-200 dark:bg-slate-700'">
                                    <ClockIcon class="w-5 h-5" :class="stats.overdue_invoices > 0 ? 'text-amber-600' : 'text-slate-400'" />
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Overdue Invoices</p>
                                    <p class="text-lg font-bold" :class="stats.overdue_invoices > 0 ? 'text-amber-600' : 'text-slate-700 dark:text-slate-300'">{{ stats.overdue_invoices }}</p>
                                </div>
                            </div>

                            <!-- Collection -->
                            <div class="flex items-center gap-4 p-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/10 border border-emerald-100 dark:border-emerald-900/30">
                                <div class="p-2 bg-emerald-500/20 rounded-lg">
                                    <BanknotesIcon class="w-5 h-5 text-emerald-600" />
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Collection This Month</p>
                                    <p class="text-lg font-bold text-emerald-600">{{ fmtCurrency(stats.collection_this_month) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══ ROW 4: QUICK ACTIONS ═══ -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-sm">
                    <div class="flex flex-wrap items-center justify-center gap-3">
                        <a :href="route('sales.planning.forecast.index')" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl transition-colors shadow-sm">
                            <ChartBarIcon class="w-4 h-4" /> View All Forecasts
                        </a>
                        <a :href="route('sales.planning.forecast.index')" class="inline-flex items-center gap-2 px-5 py-2.5 bg-violet-600 hover:bg-violet-700 text-white text-sm font-bold rounded-xl transition-colors shadow-sm">
                            <DocumentTextIcon class="w-4 h-4" /> Import Forecast
                        </a>
                        <a :href="route('sales.planning.forecast.export', { month: currentMonth })" class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-xl transition-colors shadow-sm">
                            <ArrowTrendingUpIcon class="w-4 h-4" /> Export Report
                        </a>
                    </div>
                </div>

            </div>
        </div>

        <!-- ═══ DETAIL MODAL ═══ -->
        <div v-if="showDetails" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" @click="closeDetails"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

                <div class="relative z-10 inline-block align-bottom bg-white dark:bg-slate-900 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-5xl sm:w-full border border-slate-200 dark:border-slate-800">
                    <div class="px-6 pt-6 pb-5">
                        <div class="flex justify-between items-center mb-5">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                                    Item Accuracy: {{ selectedCustomer?.customer }}
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Overall achievement: 
                                    <span class="font-bold" :class="getAchievementTextColor(selectedCustomer?.achievement ?? 0)">
                                        {{ selectedCustomer?.achievement ?? 0 }}%
                                    </span>
                                </p>
                            </div>
                            <button @click="closeDetails" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors">
                                <XMarkIcon class="w-5 h-5 text-slate-400" />
                            </button>
                        </div>

                        <div v-if="isLoadingDetails" class="py-16 text-center">
                            <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-slate-300 border-t-indigo-600"></div>
                            <p class="mt-3 text-sm text-slate-500">Loading item data...</p>
                        </div>
                        
                        <div v-else class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700">
                            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-400">
                                <thead class="bg-slate-50 dark:bg-slate-800 text-[10px] uppercase font-bold tracking-wider text-slate-500 dark:text-slate-400">
                                    <tr>
                                        <th class="px-4 py-3">Product Item</th>
                                        <th class="px-4 py-3 text-right">Forecast</th>
                                        <th class="px-4 py-3 text-right">Actual</th>
                                        <th class="px-4 py-3 text-right">Delivery</th>
                                        <th class="px-4 py-3 text-right">Achievement %</th>
                                        <th class="px-4 py-3 text-right">Variance</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <tr v-for="item in detailsData" :key="item.product_id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                        <td class="px-4 py-3">
                                            <div class="font-semibold text-slate-900 dark:text-white">{{ item.name }}</div>
                                            <div class="text-[10px] text-slate-400 font-mono">{{ item.sku }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-right font-mono font-semibold">{{ fmtNum(item.forecast) }} <span class="text-[10px] text-slate-400">{{ item.unit }}</span></td>
                                        <td class="px-4 py-3 text-right font-mono font-semibold text-blue-600 dark:text-blue-400">{{ fmtNum(item.actual) }} <span class="text-[10px] text-slate-400">{{ item.unit }}</span></td>
                                        <td class="px-4 py-3 text-right font-mono font-semibold text-amber-600 dark:text-amber-400">{{ fmtNum(item.delivery) }} <span class="text-[10px] text-slate-400">{{ item.unit }}</span></td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <div class="w-16 bg-slate-200 dark:bg-slate-700 rounded-full h-1.5">
                                                    <div :class="getAchievementBarColor(item.achievement)" class="h-1.5 rounded-full transition-all" :style="{ width: Math.min(item.achievement, 100) + '%' }"></div>
                                                </div>
                                                <span class="font-bold text-xs" :class="getAchievementTextColor(item.achievement)">
                                                    {{ item.achievement.toFixed(1) }}%
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-right font-mono font-semibold" :class="item.variance >= 0 ? 'text-emerald-600' : 'text-red-600'">
                                            {{ item.variance > 0 ? '+' : '' }}{{ fmtNum(item.variance) }}
                                        </td>
                                    </tr>
                                    <tr v-if="!detailsData.length">
                                        <td colspan="5" class="px-4 py-8 text-center text-slate-400">No item data found</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- ═══ DELIVERY TIMELINE MODAL ═══ -->
        <div v-if="showDeliveryModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" @click="closeDeliveryDetails"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

                <div class="relative z-10 inline-block align-bottom bg-white dark:bg-slate-900 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full border border-slate-200 dark:border-slate-800">
                    <div class="px-6 pt-6 pb-5">
                        <div class="flex justify-between items-center mb-5">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <CalendarDaysIcon class="w-6 h-6 text-amber-500" />
                                    Delivery Schedule: {{ selectedDeliveryDay?.date_label }}
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Total {{ selectedDeliveryDay?.count }} deliveries planned
                                </p>
                            </div>
                            <button @click="closeDeliveryDetails" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors">
                                <XMarkIcon class="w-5 h-5 text-slate-400" />
                            </button>
                        </div>

                        <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700">
                            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-400">
                                <thead class="bg-slate-50 dark:bg-slate-800 text-[10px] uppercase font-bold tracking-wider text-slate-500 dark:text-slate-400">
                                    <tr>
                                        <th class="px-4 py-3">Customer</th>
                                        <th class="px-4 py-3">Product Item</th>
                                        <th class="px-4 py-3 text-right">Quantity</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <tr v-for="(item, idx) in selectedDeliveryDay?.items" :key="idx" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                        <td class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-300">
                                            {{ item.customer }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="font-medium text-slate-900 dark:text-white">{{ item.product }}</div>
                                            <div class="text-[10px] text-slate-400 font-mono">{{ item.sku }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-right font-mono font-bold text-amber-600 dark:text-amber-400">
                                            {{ fmtNum(item.qty) }} <span class="text-[10px] text-slate-400 font-normal ml-1">{{ item.unit }}</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ DATA SOURCE INFORMATION MODAL ═══ -->
        <div v-if="showInfoModal" class="fixed inset-0 z-[60] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" @click="showInfoModal = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

                <div class="relative z-10 inline-block align-bottom bg-white dark:bg-slate-900 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full border border-slate-200 dark:border-slate-800">
                    <div class="px-8 py-7">
                        <div class="flex justify-between items-center mb-6">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-indigo-500/10 rounded-lg">
                                    <DocumentTextIcon class="w-6 h-6 text-indigo-600" />
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">Panduan Sumber Data & Formula</h3>
                                    <p class="text-sm text-slate-500">Penjelasan asal angka dan perhitungan di dashboard ini</p>
                                </div>
                            </div>
                            <button @click="showInfoModal = false" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors">
                                <XMarkIcon class="w-5 h-5 text-slate-400" />
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Left Column -->
                            <div class="space-y-6">
                                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700">
                                    <h4 class="font-bold text-slate-800 dark:text-slate-200 mb-2 flex items-center gap-2">
                                        <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div> Achievement (%)
                                    </h4>
                                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-2">
                                        <code class="bg-white dark:bg-slate-900 px-1.5 py-0.5 rounded border border-slate-200 dark:border-slate-700 font-bold text-indigo-600">(Total Actual / Total Forecast) x 100%</code>
                                    </p>
                                    <p class="text-xs text-slate-500 italic">Persentase pencapaian berdasarkan perbandingan antara Sales Order yang masuk dengan perencanaan Sales Forecast.</p>
                                </div>

                                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700">
                                    <h4 class="font-bold text-slate-800 dark:text-slate-200 mb-2 flex items-center gap-2">
                                        <div class="w-1.5 h-1.5 rounded-full bg-violet-500"></div> Total Forecast
                                    </h4>
                                    <p class="text-sm text-slate-600 dark:text-slate-400">
                                        Total dari kolom <span class="font-bold">qty_forecast</span> pada tabel <code class="text-xs">sales_forecasts</code> untuk periode bulan yang dipilih.
                                    </p>
                                </div>

                                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700">
                                    <h4 class="font-bold text-slate-800 dark:text-slate-200 mb-2 flex items-center gap-2">
                                        <div class="w-1.5 h-1.5 rounded-full bg-blue-500"></div> Total Actual (SO)
                                    </h4>
                                    <p class="text-sm text-slate-600 dark:text-slate-400">
                                        Total dari kolom <span class="font-bold">qty</span> pada tabel <code class="text-xs">sales_order_items</code> (hanya pesanan yang tidak dibatalkan).
                                    </p>
                                </div>
                            </div>

                            <!-- Right Column -->
                            <div class="space-y-6">
                                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700">
                                    <h4 class="font-bold text-slate-800 dark:text-slate-200 mb-2 flex items-center gap-2">
                                        <div class="w-1.5 h-1.5 rounded-full bg-amber-500"></div> Delivery (Chart)
                                    </h4>
                                    <p class="text-sm text-slate-600 dark:text-slate-400">
                                        Total kolom <span class="font-bold">qty_delivered</span> dari tabel <code class="text-xs">delivery_order_items</code> (berstatus Shipped/Delivered).
                                    </p>
                                </div>

                                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700">
                                    <h4 class="font-bold text-slate-800 dark:text-slate-200 mb-2 flex items-center gap-2">
                                        <div class="w-1.5 h-1.5 rounded-full bg-red-500"></div> Delayed
                                    </h4>
                                    <p class="text-sm text-slate-600 dark:text-slate-400">
                                        Jumlah jadwal pada tabel <code class="text-xs">delivery_schedules</code> yang sudah lewat tanggal kirimnya namun kuantitasnya belum terpenuhi.
                                    </p>
                                </div>

                                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700">
                                    <h4 class="font-bold text-slate-800 dark:text-slate-200 mb-2 flex items-center gap-2">
                                        <div class="w-1.5 h-1.5 rounded-full bg-teal-500"></div> Accounts Receivable
                                    </h4>
                                    <p class="text-sm text-slate-600 dark:text-slate-400">
                                        Total sisa piutang (<span class="font-bold">balance</span>) dari tabel <code class="text-xs">sales_invoices</code> untuk semua invoice berstatus Overdue/Partial/Sent.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 pt-6 border-t border-slate-100 dark:border-slate-800">
                            <p class="text-xs text-slate-400 text-center flex items-center justify-center gap-2">
                                <ExclamationTriangleIcon class="w-4 h-4" /> Data diperbarui secara otomatis setiap kali halaman dimuat berdasarkan catatan terbaru di database.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
