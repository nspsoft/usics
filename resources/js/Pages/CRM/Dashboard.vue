<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { 
    BanknotesIcon, 
    FunnelIcon, 
    TrophyIcon, 
    PresentationChartLineIcon,
    ArrowTrendingUpIcon,
    ClockIcon,
    ArrowRightIcon,
    BoltIcon,
    ChartPieIcon,
    SunIcon,
    MoonIcon
} from '@heroicons/vue/24/outline';
import { Bar, Line, Doughnut } from 'vue-chartjs';
import { 
    Chart as ChartJS, 
    Title, 
    Tooltip, 
    Legend, 
    BarElement, 
    CategoryScale, 
    LinearScale, 
    PointElement, 
    LineElement, 
    ArcElement,
    Filler 
} from 'chart.js';

ChartJS.register(
    Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, 
    PointElement, LineElement, ArcElement, Filler
);

const props = defineProps({
    kpi: Object,
    funnel: Object,
    forecast: Object,
    recent_deals: Array,
    sources: Array
});

// --- Real-time Clock ---
const time = ref('');
const updateTime = () => {
    const now = new Date();
    time.value = now.toLocaleTimeString('en-US', { hour12: false, hour: '2-digit', minute: '2-digit', second: '2-digit' });
};
let timer;

// --- Theme Reactive Sync ---
const isDark = ref(true);
const toggleTheme = () => {
    isDark.value = !isDark.value;
    if (isDark.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
};

let observer;
onMounted(() => {
    updateTime();
    timer = setInterval(updateTime, 1000);
    
    isDark.value = document.documentElement.classList.contains('dark');
    observer = new MutationObserver(() => {
        isDark.value = document.documentElement.classList.contains('dark');
    });
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
});
onUnmounted(() => {
    clearInterval(timer);
    if (observer) observer.disconnect();
});

// === CHART CONFIGURATIONS ===
const commonOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: 'rgba(5, 5, 16, 0.9)',
            titleColor: '#d946ef',
            bodyColor: '#e2e8f0',
            borderColor: '#d946ef',
            borderWidth: 1,
            padding: 12,
            titleFont: { family: 'Space Mono', weight: 'bold' },
            bodyFont: { family: 'Space Mono' },
            displayColors: false,
        }
    },
    scales: {
        x: {
            grid: { color: 'rgba(217, 70, 239, 0.1)', drawBorder: false },
            ticks: { color: '#64748b', font: { family: 'Space Mono', size: 10 } }
        },
        y: {
            grid: { color: 'rgba(217, 70, 239, 0.1)', drawBorder: false },
            ticks: { color: '#64748b', font: { family: 'Space Mono', size: 10 } }
        }
    }
};

// 1. Funnel Chart (Rotated Bar)
const funnelData = {
    labels: ['Prospecting', 'Negotiation', 'Closed Won'],
    datasets: [{
        data: [props.funnel.prospecting, props.funnel.negotiation, props.funnel.closed_won],
        backgroundColor: [
            'rgba(34, 211, 238, 0.6)', // Cyan
            'rgba(217, 70, 239, 0.6)', // Fuchsia
            'rgba(16, 185, 129, 0.6)'  // Emerald
        ],
        borderColor: [
            'rgba(34, 211, 238, 1)', 
            'rgba(217, 70, 239, 1)', 
            'rgba(16, 185, 129, 1)'
        ],
        borderWidth: 1,
        barPercentage: 0.6,
        borderRadius: 4,
    }]
};
const funnelOptions = {
    ...commonOptions,
    indexAxis: 'y', // Makes it horizontal
    plugins: { ...commonOptions.plugins }
};

// 2. Revenue Forecast Chart
const forecastData = {
    labels: props.forecast.months,
    datasets: [
        {
            label: 'Projected',
            data: props.forecast.projected,
            borderColor: '#22d3ee', // Cyan
            backgroundColor: (ctx) => {
                const canvas = ctx.chart.ctx;
                const gradient = canvas.createLinearGradient(0, 0, 0, 300);
                gradient.addColorStop(0, 'rgba(34, 211, 238, 0.2)');
                gradient.addColorStop(1, 'rgba(34, 211, 238, 0.0)');
                return gradient;
            },
            borderWidth: 2,
            tension: 0.4,
            fill: true,
            pointBackgroundColor: '#fff',
            pointBorderColor: '#22d3ee',
            pointBorderWidth: 2,
            borderDash: [5, 5]
        },
        {
            label: 'Actual',
            data: props.forecast.actual,
            borderColor: '#d946ef', // Fuchsia
            backgroundColor: (ctx) => {
                const canvas = ctx.chart.ctx;
                const gradient = canvas.createLinearGradient(0, 0, 0, 300);
                gradient.addColorStop(0, 'rgba(217, 70, 239, 0.4)');
                gradient.addColorStop(1, 'rgba(217, 70, 239, 0.0)');
                return gradient;
            },
            borderWidth: 2,
            tension: 0.4,
            fill: true,
            pointBackgroundColor: '#fff',
            pointBorderColor: '#d946ef',
            pointBorderWidth: 2
        }
    ]
};

// 3. Source Distribution (Doughnut)
const sourceData = {
    labels: props.sources.map(s => s.source),
    datasets: [{
        data: props.sources.map(s => s.total),
        backgroundColor: [
            '#22d3ee', // Cyan
            '#d946ef', // Fuchsia
            '#10b981', // Emerald
            '#f59e0b', // Amber
            '#8b5cf6'  // Violet
        ],
        borderWidth: 0,
        hoverOffset: 10
    }]
};
const sourceOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '75%',
    plugins: {
        legend: {
            display: false
        }
    }
};

const formatCurrency = (value) => new Intl.NumberFormat('id-ID', { 
    style: 'currency', currency: 'IDR', maximumFractionDigits: 0 
}).format(value);

const formatNumber = (value) => new Intl.NumberFormat('id-ID').format(value);
</script>

<template>
    <Head title="CRM Intelligence" />

    <AppLayout title="CRM Intelligence" :render-header="false">
        <div class="min-h-screen bg-slate-50 dark:bg-[#050510] relative overflow-hidden font-mono text-slate-800 dark:text-cyan-50 selection:bg-fuchsia-500/30 transition-colors duration-300">
            
            <!-- Dynamic Background -->
            <div class="fixed inset-0 z-0 pointer-events-none">
                <div class="absolute inset-0 bg-gradient-to-b from-cyan-500/5 to-slate-100 dark:from-cyan-950/20 dark:to-[#050510]"></div>
                <div class="perspective-grid absolute inset-0 opacity-[0.15] dark:opacity-20"></div>
                <div class="absolute top-[-10%] left-[20%] w-[600px] h-[600px] bg-cyan-500/5 dark:bg-cyan-600/10 blur-[150px] rounded-full animate-float"></div>
            </div>

            <div class="relative z-10 p-6 space-y-8">
                
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-slate-200 dark:border-white/10 pb-4 backdrop-blur-sm">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-0.5 text-[10px] bg-slate-200/50 dark:bg-white/5 border border-slate-300 dark:border-white/10 rounded text-slate-500 dark:text-slate-400 tracking-[0.2em]">HUB.CRM.4.0</span>
                            <span class="flex items-center gap-1.5 px-2 py-0.5 text-[10px] bg-cyan-500/10 border border-cyan-500/20 rounded text-cyan-600 dark:text-cyan-400 tracking-wider animate-pulse">
                                <span class="w-1.5 h-1.5 rounded-full bg-cyan-500 dark:bg-cyan-400"></span> INTELLIGENCE ACTIVE
                            </span>
                        </div>
                        <h1 class="text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-cyan-600 via-slate-900 to-fuchsia-600 dark:from-cyan-400 dark:via-white dark:to-fuchsia-400 tracking-widest uppercase dark:glow-text">
                            CRM INTELLIGENCE COMMAND
                        </h1>
                    </div>
                    
                    <div class="flex items-center gap-6">
                        

                        <div class="text-right hidden md:block">
                            <p class="text-[10px] text-cyan-600 dark:text-cyan-500/70 tracking-[0.2em] mb-1">LOCAL TIME</p>
                            <p class="text-2xl font-bold font-mono text-slate-900 dark:text-white dark:glow-text">{{ time }}</p>
                        </div>
                    </div>
                </div>

                <!-- KPI Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Total Revenue (Emerald) -->
                    <div class="hud-card group delay-100">
                        <div class="hud-content p-6 h-full relative z-10 bg-white/70 dark:bg-[#0a0a16]/60 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-xl overflow-hidden flex flex-col justify-between shadow-sm dark:shadow-none">
                            <div class="absolute top-0 right-0 p-4 opacity-20 group-hover:opacity-40 transition-opacity">
                                <BanknotesIcon class="h-12 w-12 text-emerald-500 dark:text-emerald-400" />
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 tracking-[0.2em] uppercase font-bold mb-1">TOTAL REVENUE</p>
                                <h3 class="text-2xl font-black text-slate-900 dark:text-white dark:glow-text tracking-tight">
                                    {{ formatCurrency(props.kpi.revenue) }}
                                </h3>
                            </div>
                             <!-- Win Rate Progress as decor -->
                            <div class="mt-4 flex items-center gap-2">
                                <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold bg-emerald-500/10 px-1 rounded border border-emerald-500/20">+12% GROWTH</span>
                            </div>
                        </div>
                    </div>

                    <!-- Active Pipelines (Cyan) -->
                    <div class="hud-card group delay-200">
                        <div class="hud-content p-6 h-full relative z-10 bg-white/70 dark:bg-[#0a0a16]/60 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-xl overflow-hidden flex flex-col justify-between shadow-sm dark:shadow-none">
                            <div class="absolute top-0 right-0 p-4 opacity-20 group-hover:opacity-40 transition-opacity">
                                <FunnelIcon class="h-12 w-12 text-cyan-500 dark:text-cyan-400" />
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 tracking-[0.2em] uppercase font-bold mb-1">ACTIVE PIPELINES</p>
                                <h3 class="text-3xl font-black text-slate-900 dark:text-white dark:glow-text tracking-tight">
                                    {{ formatNumber(props.kpi.active_pipelines) }}
                                </h3>
                            </div>
                            <div class="mt-4 h-1 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-cyan-500 shadow-[0_0_10px_#22d3ee] transition-all duration-1000" :style="{ width: props.kpi.active_pipelines > 0 ? Math.max(Math.min((props.kpi.active_pipelines / 100) * 100, 100), 5) + '%' : '0%' }"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Win Rate (Fuchsia) -->
                    <div class="hud-card group delay-300">
                        <div class="hud-content p-6 h-full relative z-10 bg-white/70 dark:bg-[#0a0a16]/60 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-xl overflow-hidden flex flex-col justify-between shadow-sm dark:shadow-none">
                            <div class="absolute top-0 right-0 p-4 opacity-20 group-hover:opacity-40 transition-opacity">
                                <TrophyIcon class="h-12 w-12 text-fuchsia-550 dark:text-fuchsia-400" />
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 tracking-[0.2em] uppercase font-bold mb-1">WIN RATE</p>
                                <h3 class="text-3xl font-black text-slate-900 dark:text-white dark:glow-text tracking-tight">
                                    {{ props.kpi.win_rate }}%
                                </h3>
                            </div>
                            <div class="mt-4 h-1 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-fuchsia-500 shadow-[0_0_10px_#d946ef]" 
                                     :style="{ width: `${props.kpi.win_rate}%` }"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Monthly Target (Amber) -->
                    <div class="hud-card group delay-400">
                        <div class="hud-content p-6 h-full relative z-10 bg-white/70 dark:bg-[#0a0a16]/60 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-xl overflow-hidden flex flex-col justify-between shadow-sm dark:shadow-none">
                            <div class="absolute top-0 right-0 p-4 opacity-20 group-hover:opacity-40 transition-opacity">
                                <ArrowTrendingUpIcon class="h-12 w-12 text-amber-500 dark:text-amber-400" />
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 tracking-[0.2em] uppercase font-bold mb-1">MONTHLY TARGET</p>
                                <h3 class="text-2xl font-black text-slate-900 dark:text-white dark:glow-text tracking-tight">
                                    {{ Math.round((props.kpi.current_month_revenue / props.kpi.monthly_target) * 100) }}%
                                </h3>
                            </div>
                            <div class="mt-4 h-1 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-amber-500 shadow-[0_0_10px_#f59e0b]" 
                                     :style="{ width: `${Math.min((props.kpi.current_month_revenue / props.kpi.monthly_target) * 100, 100)}%` }"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Revenue Forecast -->
                    <div class="lg:col-span-2 hud-panel bg-white/75 dark:bg-[#0a0a16]/80 border border-slate-200 dark:border-white/5 shadow-sm dark:shadow-[0_0_20px_rgba(0,0,0,0.5)] min-h-[350px]">
                        <div class="panel-header flex items-center justify-between p-4 border-b border-slate-200 dark:border-white/5 bg-slate-50/50 dark:bg-white/5">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-fuchsia-600 dark:text-fuchsia-300 tracking-widest uppercase">
                                <PresentationChartLineIcon class="h-4 w-4" /> Revenue Forecast (AI Projection)
                            </h3>
                        </div>
                        <div class="panel-body p-4 h-[300px] relative">
                            <Line :data="forecastData" :options="commonOptions" />
                        </div>
                    </div>

                    <!-- Sales Funnel -->
                    <div class="hud-panel bg-white/75 dark:bg-[#0a0a16]/80 border border-slate-200 dark:border-white/5 shadow-sm dark:shadow-[0_0_20px_rgba(0,0,0,0.5)] min-h-[350px]">
                        <div class="panel-header p-4 border-b border-slate-200 dark:border-white/5 bg-slate-50/50 dark:bg-white/5">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-cyan-600 dark:text-cyan-300 tracking-widest uppercase">
                                <FunnelIcon class="h-4 w-4" /> Pipeline Stage Analysis
                            </h3>
                        </div>
                        <div class="panel-body p-4 h-[300px]">
                            <Bar :data="funnelData" :options="funnelOptions" />
                        </div>
                    </div>
                </div>

                <!-- Bottom Row -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                     <!-- Activity Feed -->
                     <div class="hud-panel bg-white/75 dark:bg-[#0a0a16]/80 border border-slate-200 dark:border-white/5 shadow-sm dark:shadow-[0_0_20px_rgba(0,0,0,0.5)] overflow-hidden">
                          <div class="panel-header p-4 border-b border-slate-200 dark:border-white/5 bg-slate-50/50 dark:bg-white/5">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-emerald-600 dark:text-emerald-300 tracking-widest uppercase">
                                <ClockIcon class="h-4 w-4" /> Recent Opportunities
                            </h3>
                        </div>
                        <div class="panel-body p-0 overflow-x-auto overflow-y-auto max-h-[350px] relative">
                            <table class="w-full text-left border-collapse min-w-max">
                                <thead class="sticky top-0 z-20 bg-slate-50/95 dark:bg-[#0a0a16]/95 backdrop-blur-md shadow-sm border-b border-slate-200 dark:border-white/10">
                                    <tr class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">
                                        <th class="p-4 pl-6">Customer</th>
                                        <th class="p-4">Deal Name</th>
                                        <th class="p-4 text-center">Status</th>
                                        <th class="p-4 text-right pr-6">Value</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                                    <tr v-for="deal in recent_deals" :key="deal.id" class="hover:bg-slate-50 dark:hover:bg-white/5 transition-colors group">
                                        <td class="p-4 pl-6 text-[10px] font-mono text-cyan-600 dark:text-cyan-500/70 border-l-2 border-transparent group-hover:border-cyan-500 transition-colors">
                                            {{ deal.customer }}
                                        </td>
                                        <td class="p-4 text-xs font-bold text-slate-800 dark:text-white uppercase">{{ deal.name }}</td>
                                        <td class="p-4 text-center">
                                            <span class="px-2 py-0.5 text-[10px] font-bold border rounded uppercase"
                                                :class="{
                                                    'bg-emerald-500/10 text-emerald-650 dark:text-emerald-400 border-emerald-500/30': deal.status === 'closed_won',
                                                    'bg-cyan-500/10 text-cyan-650 dark:text-cyan-400 border-cyan-500/30': deal.status === 'prospecting',
                                                    'bg-fuchsia-500/10 text-fuchsia-650 dark:text-fuchsia-400 border-fuchsia-500/30': deal.status === 'negotiation',
                                                    'bg-rose-500/10 text-rose-650 dark:text-rose-400 border-rose-500/30': deal.status === 'closed_lost'
                                                }">
                                                {{ deal.status.replace('_', ' ') }}
                                            </span>
                                        </td>
                                        <td class="p-4 pr-6 text-xs font-mono text-right text-slate-800 dark:text-white">{{ formatCurrency(deal.amount) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                     </div>

                      <!-- Lead Sources (Doughnut) -->
                      <div class="hud-panel bg-white/75 dark:bg-[#0a0a16]/80 border border-slate-200 dark:border-white/5 shadow-sm dark:shadow-[0_0_20px_rgba(0,0,0,0.5)] min-h-[350px] flex flex-col">
                        <div class="panel-header p-4 border-b border-slate-200 dark:border-white/5 bg-slate-50/50 dark:bg-white/5">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-fuchsia-600 dark:text-fuchsia-300 tracking-widest uppercase">
                                <ChartPieIcon class="h-4 w-4" /> Lead Source Intelligence
                            </h3>
                        </div>
                        <div class="panel-body p-6 flex-1 flex flex-row items-center justify-center gap-8 relative">
                            <div class="w-1/2 h-[200px] relative">
                                <Doughnut 
                                    :data="sourceData" 
                                    :options="sourceOptions" 
                                    
                                />
                                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-center pointer-events-none w-full">
                                    <p class="text-[10px] text-slate-550 tracking-[0.2em] uppercase font-bold">TOTAL SOURCES</p>
                                    <p class="text-3xl font-black text-slate-900 dark:text-white dark:glow-text leading-tight">{{ props.sources.length }}</p>
                                </div>
                            </div>
                            <div class="w-1/2 space-y-3">
                                <div v-for="(val, index) in props.sources" :key="index" class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 rounded-sm" :style="{ backgroundColor: sourceData.datasets[0].backgroundColor[index] }"></div>
                                        <span class="text-[10px] font-mono text-slate-500 dark:text-slate-400 uppercase tracking-widest">{{ val.source }}</span>
                                    </div>
                                    <span class="text-xs font-bold text-slate-800 dark:text-white">{{ val.total }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Jersey+10&family=Space+Mono:ital,wght@0,400;0,700;1,400&display=swap');

.font-mono {
    font-family: 'Space Mono', monospace;
}

/* Background Effects */
.perspective-grid {
    background-image: 
        linear-gradient(to right, rgba(6, 182, 212, 0.1) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(6, 182, 212, 0.1) 1px, transparent 1px);
    background-size: 40px 40px;
    transform: perspective(500px) rotateX(60deg) translateY(-100px) scale(2);
    animation: grid-move 20s linear infinite;
    transform-origin: top;
}

@keyframes grid-move {
    0% { background-position: 0 0; }
    100% { background-position: 0 40px; }
}

@keyframes float {
    0%, 100% { transform: translate(0, 0); }
    50% { transform: translate(-20px, 20px); }
}

/* Card Styling */
.hud-card {
    transition: all 0.3s ease;
}
.hud-card:hover {
    transform: translateY(-5px);
    filter: drop-shadow(0 0 10px rgba(6, 182, 212, 0.2));
}

.hud-panel {
    backdrop-filter: blur(20px);
    border-radius: 12px;
    overflow: hidden;
}

/* Text Effects */
.glow-text {
    text-shadow: 0 0 10px currentColor;
}
</style>
