<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    BanknotesIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
    WalletIcon,
    ArrowPathIcon,
    ChartBarIcon,
    ChartPieIcon,
    ClockIcon,
    ArrowUpIcon,
    ArrowDownIcon
} from '@heroicons/vue/24/outline';
import { formatNumber, formatCurrency } from '@/helpers';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    ArcElement,
    Title,
    Tooltip,
    Legend,
    Filler,
} from 'chart.js';
import { Bar, Doughnut, Line } from 'vue-chartjs';

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    ArcElement,
    Title,
    Tooltip,
    Legend,
    Filler
);

const props = defineProps({
    kpi: Object,
    structure: Object,
    cash_flow: Object,
    performance: Object,
    asset_composition: Array,
    expense_breakdown: Array,
});

const navigationTabs = [
    { name: 'Financial Hub', href: '/finance/dashboard', active: true },
    { name: 'General Ledger', href: '/finance/ledger', active: false },
    { name: 'Profit & Loss', href: '/finance/reports', active: false },
    { name: 'AP & AR', href: '/finance/payment-monitoring', active: false },
    { name: 'Costing Engine', href: '/costing/production', active: false },
];

// --- Theme Toggle & Reactive Sync ---
const isLightMode = ref(false);
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

// --- Real-time Clock ---
const time = ref('');
const updateTime = () => {
    const now = new Date();
    time.value = now.toLocaleTimeString('en-US', { hour12: false, hour: '2-digit', minute: '2-digit', second: '2-digit' });
};
let timer;
onMounted(() => {
    updateTime();
    timer = setInterval(updateTime, 1000);
});
onUnmounted(() => clearInterval(timer));

// --- Chart Options ---
const commonOptions = computed(() => {
    const isLight = isLightMode.value;
    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { labels: { color: isLight ? '#475569' : '#94a3b8', font: { family: 'Space Mono' } } },
            tooltip: {
                backgroundColor: isLight ? 'rgba(255, 255, 255, 0.95)' : 'rgba(5, 5, 16, 0.9)',
                titleColor: isLight ? '#d97706' : '#f59e0b',
                bodyColor: isLight ? '#1e293b' : '#e2e8f0',
                borderColor: isLight ? '#d97706' : '#f59e0b',
                borderWidth: 1,
                padding: 12,
                titleFont: { family: 'Space Mono', weight: 'bold' },
                bodyFont: { family: 'Space Mono' },
                displayColors: false,
            },
        },
        scales: {
            x: { 
                grid: { color: isLight ? 'rgba(0, 0, 0, 0.05)' : 'rgba(245, 158, 11, 0.1)', drawBorder: false },
                ticks: { color: isLight ? '#475569' : '#64748b', font: { family: 'Space Mono', size: 10 } }
            },
            y: { 
                grid: { color: isLight ? 'rgba(0, 0, 0, 0.05)' : 'rgba(245, 158, 11, 0.1)', drawBorder: false },
                ticks: { color: isLight ? '#475569' : '#64748b', font: { family: 'Space Mono', size: 10 } }
            },
        },
    };
});

// -- Chart Data --

// 1. Monthly Performance (Bar)
const performanceData = computed(() => ({
    labels: props.performance.months,
    datasets: [
        {
            label: 'Revenue',
            data: props.performance.revenue,
            backgroundColor: '#fbbf24', // Amber 400
            borderRadius: 4,
        },
        {
            label: 'Expenses',
            data: props.performance.expenses,
            backgroundColor: '#f87171', // Red 400
            borderRadius: 4,
        }
    ]
}));

// 2. Asset Composition (Doughnut)
const assetData = computed(() => ({
    labels: props.asset_composition.map(a => a.name),
    datasets: [{
        data: props.asset_composition.map(a => a.total),
        backgroundColor: ['#f59e0b', '#3b82f6', '#10b981', '#8b5cf6', '#ec4899'],
        borderWidth: 0,
        hoverOffset: 15,
        cutout: '70%',
    }]
}));

// 3. Cash Flow Trend (Line)
const cashFlowData = computed(() => ({
    labels: props.cash_flow.dates,
    datasets: [
        {
            label: 'Inflow',
            data: props.cash_flow.inflow,
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            fill: true,
            tension: 0.4,
            pointRadius: 0,
        },
        {
            label: 'Outflow',
            data: props.cash_flow.outflow,
            borderColor: '#f43f5e',
            backgroundColor: 'rgba(244, 63, 94, 0.1)',
            fill: true,
            tension: 0.4,
            pointRadius: 0,
        }
    ]
}));

</script>

<template>
    <Head title="Financial Intelligence" />

    <AppLayout title="Financial Intelligence" :render-header="false">
        <div class="min-h-screen bg-slate-50 dark:bg-[#050510] relative overflow-hidden font-mono text-slate-800 dark:text-amber-50 selection:bg-amber-500/30 transition-colors duration-300">
            
            <!-- Dynamic Background -->
            <div class="fixed inset-0 z-0 pointer-events-none">
                <div class="absolute inset-0 bg-gradient-to-b from-amber-100/10 to-slate-50 dark:from-amber-950/20 dark:to-[#050510] transition-colors duration-300"></div>
                <div class="perspective-grid absolute inset-0 opacity-10 dark:opacity-20" :style="isLightMode ? 'background-image: linear-gradient(to right, rgba(0, 0, 0, 0.03) 1px, transparent 1px), linear-gradient(to bottom, rgba(0, 0, 0, 0.03) 1px, transparent 1px)' : ''"></div>
                <div class="absolute top-[-10%] left-[20%] w-[600px] h-[600px] bg-amber-500/5 dark:bg-amber-600/10 blur-[150px] rounded-full animate-float"></div>
                <div class="stars dark:block hidden"></div>
            </div>

            <div class="relative z-10 p-6 space-y-8">
                
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-slate-200 dark:border-white/10 pb-4 backdrop-blur-sm transition-colors duration-300">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-0.5 text-[10px] bg-slate-200/60 dark:bg-white/5 border border-slate-300 dark:border-white/10 rounded text-slate-500 dark:text-slate-400 tracking-[0.2em] transition-colors">FIN.RADAR.7.0</span>
                            <span class="flex items-center gap-1.5 px-2 py-0.5 text-[10px] bg-amber-500/10 border border-amber-500/20 rounded text-amber-700 dark:text-amber-400 tracking-wider animate-pulse">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span> REAL-TIME LEDGER
                            </span>
                        </div>
                        <h1 class="text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-amber-600 via-slate-800 to-amber-700 dark:from-amber-400 dark:via-white dark:to-amber-500 tracking-widest uppercase glow-text">
                            FINANCIAL COMMAND
                        </h1>
                    </div>
                    
                    <div class="flex items-center gap-6">
                        <div class="text-right hidden md:block border-r border-slate-200 dark:border-white/10 pr-6 transition-colors">
                            <p class="text-[10px] text-amber-600 dark:text-amber-500/70 tracking-[0.2em] mb-1">LOCAL TIME</p>
                            <p class="text-2xl font-bold font-mono text-slate-800 dark:text-white glow-text transition-colors">{{ time }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <button class="px-4 py-2 bg-slate-100 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-slate-600 dark:text-slate-400 rounded-xl text-xs font-bold flex items-center gap-2 hover:bg-slate-200 dark:hover:bg-white/10 transition-all cursor-pointer">
                                <ArrowPathIcon class="h-4 w-4" /> REFRESH
                            </button>
                            <button class="px-4 py-2 bg-amber-600 text-white rounded-xl text-xs font-bold shadow-lg shadow-amber-500/30 hover:scale-105 transition-all uppercase tracking-widest cursor-pointer">
                                + NEW ENTRY
                            </button>
                        </div>
                    </div>
                </div>

                <!-- HUD Navigation Tabs -->
                <div class="flex items-center gap-2 mb-8 bg-slate-100 dark:bg-white/5 p-1 rounded-xl border border-slate-200/80 dark:border-white/5 overflow-x-auto no-scrollbar relative z-10 transition-colors">
                    <Link 
                        v-for="tab in navigationTabs" 
                        :key="tab.name"
                        :href="tab.href"
                        class="px-6 py-2 rounded-lg text-xs font-bold tracking-widest transition-all duration-300 whitespace-nowrap"
                        :class="tab.active 
                            ? 'bg-amber-500/15 text-amber-700 dark:text-amber-400 border border-amber-500/20 dark:border-amber-500/30 shadow-sm' 
                            : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-300 hover:bg-slate-200/50 dark:hover:bg-white/5'"
                    >
                        {{ tab.name }}
                    </Link>
                </div>

                <!-- KPI Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Net Worth / Equity -->
                    <div class="hud-card group">
                        <div class="hud-content p-6 h-full relative z-10 bg-white/80 dark:bg-[#0a0a16]/60 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-xl overflow-hidden flex flex-col justify-between transition-colors duration-300">
                            <div class="absolute top-0 right-0 p-4 opacity-20 group-hover:opacity-40 transition-opacity">
                                <BanknotesIcon class="h-12 w-12 text-amber-500 dark:text-amber-400" />
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 tracking-[0.2em] uppercase font-bold mb-1">TOTAL EQUITY</p>
                                <h3 class="text-2xl font-black text-slate-800 dark:text-white glow-text tracking-tight transition-colors">
                                    {{ formatCurrency(props.kpi.total_equity) }}
                                </h3>
                            </div>
                            <div class="mt-4 flex items-center justify-between text-[10px]">
                                <span class="text-amber-600 dark:text-amber-500/70 font-bold">LIQUIDITY RATIO</span>
                                <span class="text-slate-700 dark:text-white font-bold">{{ props.kpi.current_ratio }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Revenue -->
                    <div class="hud-card group">
                        <div class="hud-content p-6 h-full relative z-10 bg-white/80 dark:bg-[#0a0a16]/60 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-xl overflow-hidden flex flex-col justify-between transition-colors duration-300">
                            <div class="absolute top-0 right-0 p-4 opacity-20 group-hover:opacity-40 transition-opacity">
                                <ArrowTrendingUpIcon class="h-12 w-12 text-emerald-500 dark:text-emerald-400" />
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 tracking-[0.2em] uppercase font-bold mb-1">MONTHLY REVENUE</p>
                                <h3 class="text-2xl font-black text-slate-800 dark:text-white glow-text tracking-tight transition-colors">
                                    {{ formatCurrency(props.kpi.revenue) }}
                                </h3>
                            </div>
                             <div class="mt-4 h-1 bg-slate-200 dark:bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-emerald-500 shadow-[0_0_10px_#10b981] transition-all duration-1000" :style="{ width: props.kpi.revenue > 0 ? Math.max(Math.min((props.kpi.revenue / 1000000000) * 100, 100), 5) + '%' : '0%' }"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Liabilities -->
                    <div class="hud-card group">
                        <div class="hud-content p-6 h-full relative z-10 bg-white/80 dark:bg-[#0a0a16]/60 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-xl overflow-hidden flex flex-col justify-between transition-colors duration-300">
                            <div class="absolute top-0 right-0 p-4 opacity-20 group-hover:opacity-40 transition-opacity">
                                <ArrowTrendingDownIcon class="h-12 w-12 text-rose-500 dark:text-rose-400" />
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 tracking-[0.2em] uppercase font-bold mb-1">PAYABLES / LIAB.</p>
                                <h3 class="text-2xl font-black text-rose-600 dark:text-rose-500 glow-text tracking-tight transition-colors">
                                    {{ formatCurrency(props.kpi.total_liabilities) }}
                                </h3>
                            </div>
                             <div class="mt-4 flex items-center justify-between text-[10px]">
                                <span class="text-rose-600 dark:text-rose-500/70 font-bold">DEBT LOAD</span>
                                <span class="text-slate-500 font-bold">MANAGEABLE</span>
                            </div>
                        </div>
                    </div>

                    <!-- Cash on Hand -->
                    <div class="hud-card group">
                        <div class="hud-content p-6 h-full relative z-10 bg-white/80 dark:bg-[#0a0a16]/60 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-xl overflow-hidden flex flex-col justify-between transition-colors duration-300">
                            <div class="absolute top-0 right-0 p-4 opacity-20 group-hover:opacity-40 transition-opacity">
                                <WalletIcon class="h-12 w-12 text-cyan-500 dark:text-cyan-400" />
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 tracking-[0.2em] uppercase font-bold mb-1">CASH POSITION</p>
                                <h3 class="text-2xl font-black text-slate-800 dark:text-white glow-text tracking-tight transition-colors">
                                    {{ formatCurrency(props.kpi.total_assets) }}
                                </h3>
                            </div>
                             <div class="mt-4 flex items-center justify-between text-[10px]">
                                <span class="text-cyan-600 dark:text-cyan-500/70 font-bold">PROFIT MARGIN</span>
                                <span class="text-slate-700 dark:text-white font-bold">{{ props.kpi.profit_margin }}%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Analysis Row -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Revenue Analytics -->
                    <div class="lg:col-span-2 hud-panel flex flex-col h-[400px]">
                        <div class="panel-header p-4 border-b border-slate-200/60 dark:border-white/5 bg-slate-100/50 dark:bg-white/5 flex items-center justify-between transition-colors">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-amber-700 dark:text-amber-300 tracking-widest uppercase">
                                <ChartBarIcon class="h-4 w-4" /> REVENUE VS EXPENSES (6M)
                            </h3>
                        </div>
                        <div class="panel-body p-6 flex-1">
                            <Bar :data="performanceData" :options="commonOptions" />
                        </div>
                    </div>

                    <!-- Asset Distribution -->
                    <div class="hud-panel flex flex-col h-[400px]">
                        <div class="panel-header p-4 border-b border-slate-200/60 dark:border-white/5 bg-slate-100/50 dark:bg-white/5 flex items-center justify-between transition-colors">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-cyan-700 dark:text-cyan-300 tracking-widest uppercase">
                                <ChartPieIcon class="h-4 w-4" /> ASSET ANATOMY
                            </h3>
                        </div>
                        <div class="panel-body p-6 flex-1 flex flex-col items-center justify-center relative">
                             <div class="w-full h-[250px] relative">
                                <Doughnut 
                                    :data="assetData" 
                                    :options="{ 
                                        ...commonOptions, 
                                        cutout: '75%', 
                                        scales: { x: { display: false }, y: { display: false } },
                                        plugins: { legend: { display: false } } 
                                    }" 
                                />
                                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-center pointer-events-none w-full">
                                    <p class="text-[10px] text-slate-400 dark:text-slate-505 tracking-[0.2em] uppercase font-bold">NET ASSETS</p>
                                    <p class="text-xl font-black text-slate-800 dark:text-white leading-tight uppercase transition-colors">LIQUID</p>
                                </div>
                            </div>
                            <div class="mt-4 w-full grid grid-cols-2 gap-2">
                                <div v-for="(val, index) in assetData.labels.slice(0, 4)" :key="index" class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-sm" :style="{ backgroundColor: assetData.datasets[0].backgroundColor[index] }"></div>
                                    <span class="text-[8px] font-mono text-slate-500 dark:text-slate-400 uppercase tracking-widest truncate">{{ val }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Secondary Row -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Cash Flow Feed -->
                    <div class="hud-panel flex flex-col h-[400px]">
                         <div class="panel-header p-4 border-b border-slate-200/60 dark:border-white/5 bg-slate-100/50 dark:bg-white/5 flex items-center justify-between transition-colors">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-emerald-700 dark:text-emerald-300 tracking-widest uppercase">
                                <ClockIcon class="h-4 w-4" /> CASH FLOW PULSE
                            </h3>
                            <span class="text-[10px] text-emerald-600 dark:text-emerald-505 animate-pulse font-bold">● LIVE STREAM</span>
                        </div>
                        <div class="panel-body p-6 flex-1">
                            <Line :data="cashFlowData" :options="commonOptions" />
                        </div>
                    </div>

                    <!-- Expense Breakdown Table -->
                    <div class="hud-panel h-[400px] flex flex-col overflow-hidden">
                        <div class="panel-header p-4 border-b border-slate-200/60 dark:border-white/5 bg-slate-100/50 dark:bg-white/5 transition-colors">
                            <h3 class="text-sm font-bold text-rose-700 dark:text-rose-300 tracking-widest uppercase">TOP OPEX CATEGORIES</h3>
                        </div>
                        <div class="panel-body p-0 overflow-y-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="text-[10px] text-slate-500 font-bold uppercase tracking-wider border-b border-slate-200 dark:border-white/10 bg-slate-100/40 dark:bg-white/5">
                                        <th class="p-4">CATEGORY</th>
                                        <th class="p-4 text-right">TOTAL (JT)</th>
                                        <th class="p-4 text-right">DISTRIBUTION</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200/60 dark:divide-white/5">
                                    <tr v-for="exp in props.expense_breakdown" :key="exp.category" class="hover:bg-slate-100/40 dark:hover:bg-white/5 transition-colors group">
                                        <td class="p-4 text-xs font-bold text-slate-800 dark:text-white uppercase tracking-wider transition-colors">{{ exp.category }}</td>
                                        <td class="p-4 text-xs font-mono text-right text-rose-600 dark:text-rose-400 transition-colors">{{ formatNumber(exp.total / 1000000) }}jt</td>
                                        <td class="p-4">
                                            <div class="w-full bg-slate-200 dark:bg-slate-900 h-1.5 rounded-full overflow-hidden">
                                                <div class="bg-rose-500 h-full" :style="{ width: `${Math.min((exp.total / props.kpi.expenses) * 100, 100)}%` }"></div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Space+Mono:ital,wght@0,400;0,700;1,400&display=swap');

.font-mono {
    font-family: 'Space Mono', monospace;
}

/* Background Effects */
.perspective-grid {
    background-size: 50px 50px;
    transform: perspective(600px) rotateX(60deg) translateY(-100px) scale(2);
    animation: grid-move 30s linear infinite;
    transform-origin: top;
}

.dark .perspective-grid {
    background-image: 
        linear-gradient(to right, rgba(245, 158, 11, 0.1) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(245, 158, 11, 0.1) 1px, transparent 1px);
}

@keyframes grid-move {
    0% { background-position: 0 0; }
    100% { background-position: 0 50px; }
}

@keyframes float {
    0%, 100% { transform: translate(0, 0); }
    50% { transform: translate(-20px, 20px); }
}

/* HUD Styling */
.hud-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.hud-card:hover {
    transform: translateY(-5px) scale(1.02);
}
.dark .hud-card:hover {
    filter: drop-shadow(0 0 15px rgba(245, 158, 11, 0.3));
}

.hud-panel {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(0, 0, 0, 0.08);
    border-radius: 12px;
    box-shadow: 0 4px 30px rgba(0, 0, 0, 0.03);
    transition: border-color 0.3s ease;
}
.dark .hud-panel {
    background: rgba(10, 10, 22, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 0 40px rgba(0, 0, 0, 0.8);
}
.hud-panel:hover {
    border-color: rgba(245, 158, 11, 0.3);
}

/* Text Effects */
.dark .glow-text {
    text-shadow: 0 0 10px currentColor;
}

/* Custom Scrollbar */
::-webkit-scrollbar {
    width: 6px;
}
::-webkit-scrollbar-track {
    background: transparent;
}
.dark ::-webkit-scrollbar-track {
    background: #050510;
}
::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}
.dark ::-webkit-scrollbar-thumb {
    background: #1e293b;
}
::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
.dark ::-webkit-scrollbar-thumb:hover {
    background: #334155;
}
</style>
