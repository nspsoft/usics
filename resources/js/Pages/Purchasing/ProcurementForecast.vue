<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ChartBarSquareIcon,
    ExclamationTriangleIcon,
    ShoppingCartIcon,
    ClockIcon,
    CurrencyDollarIcon,
    FireIcon,
    ArrowTrendingUpIcon,
    ArrowTopRightOnSquareIcon,
    SunIcon,
    MoonIcon,
} from '@heroicons/vue/24/outline';
import { formatNumber, formatCurrency } from '@/helpers';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    Title,
    Tooltip,
    Legend,
    Filler,
} from 'chart.js';
import { Bar, Line } from 'vue-chartjs';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title, Tooltip, Legend, Filler);

const props = defineProps({
    reorderAlerts: Array,
    spendTrend: Array,
    topConsumed: Array,
    consumptionTrend: Array,
    stats: Object,
    lookbackMonths: Number,
});

// --- Clock ---
const time = ref('');
let timer;

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
    const tick = () => { 
        time.value = new Date().toLocaleTimeString('en-US', { 
            hour12: false, 
            hour: '2-digit', 
            minute: '2-digit', 
            second: '2-digit' 
        }); 
    };
    tick(); 
    timer = setInterval(tick, 1000);

    isLightMode.value = !document.documentElement.classList.contains('dark');
    observer = new MutationObserver(() => {
        isLightMode.value = !document.documentElement.classList.contains('dark');
    });
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
});

onUnmounted(() => {
    clearInterval(timer);
    if (observer) observer.disconnect();
});

// --- Chart Options ---
const chartOptions = computed(() => {
    const gridColor = isLightMode.value ? 'rgba(0, 0, 0, 0.05)' : 'rgba(245, 158, 11, 0.1)';
    const tickColor = isLightMode.value ? '#475569' : '#64748b';
    const legendColor = isLightMode.value ? '#1e293b' : '#94a3b8';
    
    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { labels: { color: legendColor, font: { family: 'Space Mono', size: 10 } } },
            tooltip: {
                backgroundColor: isLightMode.value ? 'rgba(255, 255, 255, 0.95)' : 'rgba(5, 5, 16, 0.9)',
                titleColor: '#f59e0b',
                bodyColor: isLightMode.value ? '#1e293b' : '#e2e8f0',
                borderColor: '#f59e0b',
                borderWidth: 1,
                padding: 12,
                titleFont: { family: 'Space Mono', weight: 'bold' },
                bodyFont: { family: 'Space Mono' },
                displayColors: false,
            },
        },
        scales: {
            x: { grid: { color: gridColor, drawBorder: false }, ticks: { color: tickColor, font: { family: 'Space Mono', size: 10 } } },
            y: { grid: { color: gridColor, drawBorder: false }, ticks: { color: tickColor, font: { family: 'Space Mono', size: 10 } } },
        },
    };
});

// --- Chart Data ---
const spendData = computed(() => ({
    labels: props.spendTrend.map(t => t.month),
    datasets: [{
        label: 'Monthly Spend',
        data: props.spendTrend.map(t => t.total),
        backgroundColor: isLightMode.value ? 'rgba(245, 158, 11, 0.15)' : 'rgba(245, 158, 11, 0.3)',
        borderColor: '#f59e0b',
        borderWidth: 2,
        tension: 0.4,
        fill: true,
    }],
}));

const consumptionData = computed(() => ({
    labels: props.consumptionTrend.map(t => t.month),
    datasets: [{
        label: 'Material Consumption',
        data: props.consumptionTrend.map(t => t.total_out),
        backgroundColor: isLightMode.value ? 'rgba(6, 182, 212, 0.15)' : 'rgba(6, 182, 212, 0.3)',
        borderColor: '#06b6d4',
        borderWidth: 2,
        tension: 0.4,
        fill: true,
    }],
}));

const topConsumedData = computed(() => ({
    labels: props.topConsumed.map(t => t.product_name?.substring(0, 20) || 'Unknown'),
    datasets: [{
        label: 'Qty Consumed',
        data: props.topConsumed.map(t => t.total_consumed),
        backgroundColor: [
            'rgba(245, 158, 11, 0.6)', 'rgba(6, 182, 212, 0.6)', 'rgba(168, 85, 247, 0.6)',
            'rgba(34, 197, 94, 0.6)', 'rgba(239, 68, 68, 0.6)', 'rgba(59, 130, 246, 0.6)',
            'rgba(236, 72, 153, 0.6)', 'rgba(234, 179, 8, 0.6)', 'rgba(14, 165, 233, 0.6)',
            'rgba(249, 115, 22, 0.6)',
        ],
        borderWidth: 0,
        borderRadius: 4,
    }],
}));

// --- Urgency helpers ---
const urgencyStyle = (urgency) => {
    if (isLightMode.value) {
        return {
            critical: 'bg-rose-50 text-rose-700 border-rose-200',
            warning: 'bg-amber-50 text-amber-700 border-amber-200',
            normal: 'bg-slate-100 text-slate-600 border-slate-200',
        }[urgency] || 'bg-slate-100 text-slate-600';
    }
    return {
        critical: 'bg-rose-500/20 text-rose-400 border-rose-500/30',
        warning: 'bg-amber-500/20 text-amber-400 border-amber-500/30',
        normal: 'bg-slate-500/20 text-slate-400 border-slate-500/30',
    }[urgency] || 'bg-slate-500/20 text-slate-400';
};

const urgencyLabel = (urgency) => ({
    critical: '🔴 CRITICAL',
    warning: '🟡 WARNING',
    normal: '🟢 NORMAL',
    }[urgency] || 'UNKNOWN');

const stockBarWidth = (current, reorder) => {
    if (reorder <= 0) return '100%';
    const pct = Math.min(100, Math.max(0, (current / reorder) * 100));
    return pct + '%';
};

const stockBarColor = (current, reorder, min) => {
    if (current <= min) return 'bg-rose-500';
    if (current <= reorder) return 'bg-amber-500';
    return 'bg-emerald-500';
};
</script>

<template>
    <AppLayout :render-header="false">
        <Head title="Procurement Forecast" />

        <div class="min-h-screen bg-slate-50 dark:bg-[#050510] text-slate-800 dark:text-white font-mono relative overflow-hidden transition-colors duration-300">
            <!-- Dynamic Background -->
            <div class="fixed inset-0 pointer-events-none z-0">
                <div class="absolute inset-0 bg-gradient-to-b from-purple-500/5 to-slate-100 dark:from-cyan-950/20 dark:to-[#050510]"></div>
                <div class="absolute inset-0 perspective-grid opacity-[0.15] dark:opacity-30"></div>
                <div class="absolute top-0 right-0 w-[600px] h-[600px] bg-purple-500/5 dark:bg-purple-500/10 rounded-full blur-[200px] animate-float"></div>
                <div class="absolute bottom-0 left-0 w-[500px] h-[500px] bg-amber-500/5 dark:bg-amber-500/10 rounded-full blur-[200px] animate-float-delayed"></div>
            </div>

            <div class="relative z-10 p-4 lg:p-6 max-w-[1600px] mx-auto space-y-6">
                <!-- Header -->
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-white/10 pb-4">
                    <div>
                        <h1 class="text-2xl font-black tracking-wider text-purple-600 dark:text-purple-400 uppercase flex items-center gap-3">
                            <ChartBarSquareIcon class="h-7 w-7" />
                            Procurement Forecast
                        </h1>
                        <p class="text-xs text-slate-500 tracking-[0.3em] uppercase mt-1">DEMAND INTELLIGENCE & REORDER ANALYSIS</p>
                    </div>
                    
                    <div class="flex items-center gap-6">
                        

                        <div class="text-right">
                            <p class="text-3xl font-black text-slate-900 dark:text-white tracking-widest leading-none">{{ time }}</p>
                            <p class="text-[10px] text-slate-555 dark:text-slate-600 uppercase tracking-widest mt-1">Lookback: {{ lookbackMonths }} months</p>
                        </div>
                    </div>
                </div>

                <!-- KPI Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                    <div class="hud-card bg-white/70 dark:bg-white/5 backdrop-blur-xl border border-slate-200 dark:border-white/10 rounded-xl p-4 shadow-sm dark:shadow-none">
                        <div class="flex items-center gap-2 mb-2">
                            <ExclamationTriangleIcon class="h-4 w-4 text-rose-500 dark:text-rose-400" />
                            <span class="text-[10px] text-slate-500 tracking-[0.15em] uppercase">Reorder Alerts</span>
                        </div>
                        <p class="text-3xl font-black" :class="stats.reorder_alerts > 0 ? 'text-rose-600 dark:text-rose-400 dark:glow-text-red' : 'text-emerald-600 dark:text-emerald-400'">
                            {{ stats.reorder_alerts }}
                        </p>
                    </div>
                    <div class="hud-card bg-white/70 dark:bg-white/5 backdrop-blur-xl border border-slate-200 dark:border-white/10 rounded-xl p-4 shadow-sm dark:shadow-none">
                        <div class="flex items-center gap-2 mb-2">
                            <FireIcon class="h-4 w-4 text-red-655 dark:text-red-500" />
                            <span class="text-[10px] text-slate-500 tracking-[0.15em] uppercase">Critical</span>
                        </div>
                        <p class="text-3xl font-black" :class="stats.critical_count > 0 ? 'text-red-655 dark:text-red-500 dark:glow-text-red' : 'text-emerald-600 dark:text-emerald-400'">
                            {{ stats.critical_count }}
                        </p>
                    </div>
                    <div class="hud-card bg-white/70 dark:bg-white/5 backdrop-blur-xl border border-slate-200 dark:border-white/10 rounded-xl p-4 shadow-sm dark:shadow-none">
                        <div class="flex items-center gap-2 mb-2">
                            <CurrencyDollarIcon class="h-4 w-4 text-amber-600 dark:text-amber-400" />
                            <span class="text-[10px] text-slate-500 tracking-[0.15em] uppercase">Monthly Spend</span>
                        </div>
                        <p class="text-xl font-black text-amber-600 dark:text-amber-400 dark:glow-text">{{ formatCurrency(stats.total_monthly_spend) }}</p>
                    </div>
                    <div class="hud-card bg-white/70 dark:bg-white/5 backdrop-blur-xl border border-slate-200 dark:border-white/10 rounded-xl p-4 shadow-sm dark:shadow-none">
                        <div class="flex items-center gap-2 mb-2">
                            <ShoppingCartIcon class="h-4 w-4 text-cyan-600 dark:text-cyan-400" />
                            <span class="text-[10px] text-slate-500 tracking-[0.15em] uppercase">Active POs</span>
                        </div>
                        <p class="text-3xl font-black text-cyan-600 dark:text-cyan-400">{{ stats.active_pos }}</p>
                    </div>
                    <div class="hud-card bg-white/70 dark:bg-white/5 backdrop-blur-xl border border-slate-200 dark:border-white/10 rounded-xl p-4 shadow-sm dark:shadow-none">
                        <div class="flex items-center gap-2 mb-2">
                            <ClockIcon class="h-4 w-4 text-purple-600 dark:text-purple-400" />
                            <span class="text-[10px] text-slate-500 tracking-[0.15em] uppercase">Avg Lead Time</span>
                        </div>
                        <p class="text-3xl font-black text-purple-600 dark:text-purple-400">{{ stats.avg_lead_time }}<span class="text-sm text-slate-500 ml-1 font-normal">days</span></p>
                    </div>
                </div>

                <!-- Reorder Alerts Table -->
                <div class="hud-panel bg-white/75 dark:bg-[#0a0a16]/80 border border-slate-200 dark:border-white/10 rounded-xl overflow-hidden shadow-sm dark:shadow-[0_0_20px_rgba(0,0,0,0.5)]" v-if="reorderAlerts.length > 0">
                    <div class="panel-header p-4 border-b border-slate-200 dark:border-white/5 bg-rose-500/5 flex items-center justify-between">
                        <h3 class="flex items-center gap-2 text-sm font-bold text-rose-600 dark:text-rose-400 tracking-widest uppercase">
                            <ExclamationTriangleIcon class="h-4 w-4 animate-pulse" />
                            Reorder Suggestions
                            <span class="ml-2 bg-rose-500/10 dark:bg-rose-500/20 text-rose-700 dark:text-rose-300 px-2 py-0.5 rounded-full text-[10px]">{{ reorderAlerts.length }}</span>
                        </h3>
                    </div>
                    <div class="panel-body p-0 overflow-auto max-h-[30vh]">
                        <table class="w-full text-left border-collapse">
                            <thead class="sticky top-0 z-10">
                                <tr class="text-[10px] text-slate-500 font-bold uppercase tracking-wider border-b border-slate-200 dark:border-white/10 bg-slate-50 dark:bg-[#0a0a16]">
                                    <th class="p-3">Product</th>
                                    <th class="p-3">SKU</th>
                                    <th class="p-3 text-center">Stock Level</th>
                                    <th class="p-3 text-right">Current</th>
                                    <th class="p-3 text-right">Reorder Pt</th>
                                    <th class="p-3 text-right">Avg/Mo</th>
                                    <th class="p-3 text-center">Days Left</th>
                                    <th class="p-3 text-right">Incoming</th>
                                    <th class="p-3 text-center">Urgency</th>
                                    <th class="p-3 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                                <tr
                                    v-for="item in reorderAlerts"
                                    :key="item.id"
                                    class="hover:bg-slate-50 dark:hover:bg-white/5 transition-colors group"
                                    :class="item.urgency === 'critical' ? 'bg-rose-500/[0.02] dark:bg-rose-500/5' : ''"
                                >
                                    <td class="p-3 text-xs font-bold text-slate-800 dark:text-white truncate max-w-[180px] border-l-2 border-transparent group-hover:border-amber-500 transition-colors">
                                        {{ item.name }}
                                    </td>
                                    <td class="p-3 text-[10px] font-mono text-slate-550 dark:text-slate-550">{{ item.sku }}</td>
                                    <td class="p-3">
                                        <div class="w-full bg-slate-200 dark:bg-white/5 rounded-full h-2 overflow-hidden">
                                            <div
                                                class="h-full rounded-full transition-all"
                                                :class="stockBarColor(item.current_stock, item.reorder_point, item.min_stock)"
                                                :style="{ width: stockBarWidth(item.current_stock, item.reorder_point) }"
                                            ></div>
                                        </div>
                                    </td>
                                    <td class="p-3 text-xs text-slate-800 dark:text-white font-mono text-right">{{ formatNumber(item.current_stock) }} <span class="text-slate-500 dark:text-slate-600">{{ item.unit }}</span></td>
                                    <td class="p-3 text-xs text-amber-700 dark:text-amber-400/60 font-mono text-right">{{ formatNumber(item.reorder_point) }}</td>
                                    <td class="p-3 text-xs text-cyan-700 dark:text-cyan-400/60 font-mono text-right">{{ formatNumber(item.avg_consumption) }}</td>
                                    <td class="p-3 text-center">
                                        <span
                                            v-if="item.days_remaining !== null"
                                            class="inline-flex items-center min-w-[36px] px-2 py-0.5 rounded-full text-[10px] font-black justify-center"
                                            :class="item.days_remaining <= (item.lead_time_days || 7) ? 'bg-rose-500/10 dark:bg-rose-500/30 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-500/20' : 'bg-amber-50 dark:bg-amber-500/20 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-500/20'"
                                        >
                                            {{ item.days_remaining }}d
                                        </span>
                                        <span v-else class="text-[10px] text-slate-400 dark:text-slate-600">—</span>
                                    </td>
                                    <td class="p-3 text-xs text-emerald-700 dark:text-emerald-400/60 font-mono text-right">
                                        {{ item.incoming_qty > 0 ? formatNumber(item.incoming_qty) : '—' }}
                                    </td>
                                    <td class="p-3 text-center">
                                        <span class="text-[9px] font-bold px-2 py-0.5 rounded-full border" :class="urgencyStyle(item.urgency)">
                                            {{ urgencyLabel(item.urgency) }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-center">
                                        <Link
                                            :href="route('purchasing.requests.create', { product_id: item.id, qty: item.reorder_qty || item.shortage })"
                                            class="px-3 py-1 text-[10px] bg-amber-500/10 dark:bg-amber-500/20 text-amber-700 dark:text-amber-400 border border-amber-250 dark:border-amber-500/30 rounded-lg hover:bg-amber-500 hover:text-white dark:hover:text-black transition-colors uppercase tracking-wider font-bold inline-flex items-center gap-1 shadow-sm"
                                        >
                                            Create PR
                                            <ArrowTopRightOnSquareIcon class="h-3 w-3" />
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- No alerts -->
                <div v-else class="hud-panel bg-white/75 dark:bg-[#0a0a16]/80 border border-slate-200 dark:border-white/10 rounded-xl overflow-hidden shadow-sm dark:shadow-[0_0_20px_rgba(0,0,0,0.5)]">
                    <div class="p-8 text-center">
                        <ShoppingCartIcon class="h-12 w-12 text-emerald-555 mx-auto mb-3" />
                        <p class="text-sm text-emerald-600 dark:text-emerald-400 font-bold uppercase tracking-widest">Stock Levels Healthy</p>
                        <p class="text-xs text-slate-550 mt-1">No products below reorder point</p>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Spend Trend -->
                    <div class="hud-panel bg-white/75 dark:bg-[#0a0a16]/80 border border-slate-200 dark:border-white/10 rounded-xl overflow-hidden shadow-sm dark:shadow-none">
                        <div class="panel-header p-4 border-b border-slate-200 dark:border-white/5 bg-slate-50 dark:bg-white/5">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-amber-605 dark:text-amber-300 tracking-widest uppercase">
                                <CurrencyDollarIcon class="h-4 w-4" /> Spend Trend
                            </h3>
                        </div>
                        <div class="panel-body p-4 h-[280px]">
                            <Line :data="spendData" :options="{ ...chartOptions, plugins: { ...chartOptions.plugins, legend: { display: false } } }" />
                        </div>
                    </div>

                    <!-- Consumption Trend -->
                    <div class="hud-panel bg-white/75 dark:bg-[#0a0a16]/80 border border-slate-200 dark:border-white/10 rounded-xl overflow-hidden shadow-sm dark:shadow-none">
                        <div class="panel-header p-4 border-b border-slate-200 dark:border-white/5 bg-slate-50 dark:bg-white/5">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-cyan-605 dark:text-cyan-300 tracking-widest uppercase">
                                <ArrowTrendingUpIcon class="h-4 w-4" /> Consumption Trend
                            </h3>
                        </div>
                        <div class="panel-body p-4 h-[280px]">
                            <Line :data="consumptionData" :options="{ ...chartOptions, plugins: { ...chartOptions.plugins, legend: { display: false } } }" />
                        </div>
                    </div>

                    <!-- Top Consumed -->
                    <div class="hud-panel bg-white/75 dark:bg-[#0a0a16]/80 border border-slate-200 dark:border-white/10 rounded-xl overflow-hidden shadow-sm dark:shadow-none">
                        <div class="panel-header p-4 border-b border-slate-200 dark:border-white/5 bg-slate-50 dark:bg-white/5">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-purple-650 dark:text-purple-300 tracking-widest uppercase">
                                <FireIcon class="h-4 w-4" /> Top Consumed
                            </h3>
                        </div>
                        <div class="panel-body p-4 h-[280px]">
                            <Bar :data="topConsumedData" :options="{ ...chartOptions, indexAxis: 'y', plugins: { ...chartOptions.plugins, legend: { display: false } } }" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&display=swap');

.font-mono { font-family: 'Space Mono', monospace; }

.perspective-grid {
    background-image:
        linear-gradient(to right, rgba(168, 85, 247, 0.08) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(168, 85, 247, 0.08) 1px, transparent 1px);
    background-size: 40px 40px;
    transform: perspective(500px) rotateX(60deg) translateY(-100px) scale(2);
    animation: grid-move 20s linear infinite;
    transform-origin: top;
}

@keyframes grid-move { 0% { background-position: 0 0; } 100% { background-position: 0 40px; } }
@keyframes float { 0%, 100% { transform: translate(0, 0); } 50% { transform: translate(-20px, 20px); } }
@keyframes float-delayed { 0%, 100% { transform: translate(0, 0); } 50% { transform: translate(20px, -20px); } }

.animate-float { animation: float 15s ease-in-out infinite; }
.animate-float-delayed { animation: float-delayed 18s ease-in-out infinite; }

.hud-card { transition: all 0.3s ease; }
.hud-card:hover { transform: translateY(-5px); }
.dark .hud-card:hover { filter: drop-shadow(0 0 10px rgba(168, 85, 247, 0.2)); }

.hud-panel {
    backdrop-filter: blur(20px);
}

.glow-text { text-shadow: 0 0 10px currentColor; }
.glow-text-red { text-shadow: 0 0 10px rgba(244, 63, 94, 0.6); }
</style>
