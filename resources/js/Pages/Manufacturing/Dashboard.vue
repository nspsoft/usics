<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    BoltIcon,
    CpuChipIcon,
    ShieldCheckIcon,
    ExclamationTriangleIcon,
    ArrowPathIcon,
    ChartBarIcon,
    ClockIcon,
    CubeIcon,
    SunIcon,
    MoonIcon
} from '@heroicons/vue/24/outline';
import { formatNumber } from '@/helpers';
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
    stats: Object,
    shift_data: Array,
    machine_statuses: Array,
    recent_logs: Array,
    trend_data: Array,
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

// --- Chart Options ---
const commonOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { labels: { color: '#94a3b8', font: { family: 'Space Mono' } } },
        tooltip: {
            backgroundColor: 'rgba(5, 5, 16, 0.9)',
            titleColor: '#10b981',
            bodyColor: '#e2e8f0',
            borderColor: '#10b981',
            borderWidth: 1,
            padding: 12,
            titleFont: { family: 'Space Mono', weight: 'bold' },
            bodyFont: { family: 'Space Mono' },
            displayColors: false,
        },
    },
    scales: {
        x: { 
            grid: { color: 'rgba(16, 185, 129, 0.1)', drawBorder: false },
            ticks: { color: '#64748b', font: { family: 'Space Mono', size: 10 } }
        },
        y: { 
            grid: { color: 'rgba(16, 185, 129, 0.1)', drawBorder: false },
            ticks: { color: '#64748b', font: { family: 'Space Mono', size: 10 } }
        },
    },
}));

// -- Chart Data --

// 1. OEE Gauge (Doughnut)
const oeeChartData = computed(() => ({
    labels: ['Efficiency', 'Remaining'],
    datasets: [{
        data: [props.stats.oee, 100 - props.stats.oee],
        backgroundColor: ['#10b981', 'rgba(16, 185, 129, 0.1)'],
        borderWidth: 0,
        circumference: 240,
        rotation: 240,
        cutout: '85%',
    }]
}));

// 2. Output Trend (Line)
const trendChartData = computed(() => ({
    labels: props.trend_data.map(d => d.date),
    datasets: [{
        label: 'Production Output',
        data: props.trend_data.map(d => d.total),
        borderColor: '#22d3ee',
        backgroundColor: 'rgba(34, 211, 238, 0.1)',
        fill: true,
        tension: 0.4,
        pointRadius: 4,
    }]
}));
</script>

<template>
    <Head title="Production Intelligence" />

    <AppLayout title="Production Intelligence" :render-header="false">
        <div class="min-h-screen bg-slate-50 dark:bg-[#050510] relative overflow-hidden font-mono text-slate-800 dark:text-emerald-50 selection:bg-emerald-500/30 transition-colors duration-300">
            
            <!-- Dynamic Background -->
            <div class="fixed inset-0 z-0 pointer-events-none">
                <div class="absolute inset-0 bg-gradient-to-b from-emerald-500/5 to-slate-100 dark:from-emerald-950/20 dark:to-[#050510]"></div>
                <div class="perspective-grid absolute inset-0 opacity-[0.15] dark:opacity-20"></div>
                <div class="absolute top-[-10%] left-[20%] w-[600px] h-[600px] bg-emerald-500/5 dark:bg-emerald-600/10 blur-[150px] rounded-full animate-float"></div>
                <div class="stars"></div>
            </div>

            <div class="relative z-10 p-6 space-y-8">
                
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-slate-200 dark:border-white/10 pb-4 backdrop-blur-sm">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-0.5 text-[10px] bg-slate-200/50 dark:bg-white/5 border border-slate-300 dark:border-white/10 rounded text-slate-500 dark:text-slate-400 tracking-[0.2em]">HUB.PROD.5.0</span>
                            <span class="flex items-center gap-1.5 px-2 py-0.5 text-[10px] bg-emerald-500/10 border border-emerald-500/20 rounded text-emerald-600 dark:text-emerald-400 tracking-wider animate-pulse">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 dark:bg-emerald-400"></span> SYSTEMS LIVE
                            </span>
                        </div>
                        <h1 class="text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-emerald-600 via-slate-900 to-cyan-600 dark:from-emerald-400 dark:via-white dark:to-cyan-400 tracking-widest uppercase dark:glow-text">
                            PRODUCTION INTELLIGENCE
                        </h1>
                    </div>
                    
                    <div class="flex items-center gap-6">
                        <!-- Theme Toggle Button -->
                        <button 
                            @click="toggleTheme"
                            class="p-2.5 rounded-xl border border-slate-200 dark:border-white/10 bg-white/70 dark:bg-white/5 hover:bg-slate-50 dark:hover:bg-white/10 text-slate-700 dark:text-cyan-400 transition-all hover:scale-105 shadow-sm dark:shadow-none"
                            :title="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
                        >
                            <SunIcon v-if="isDark" class="h-5 w-5 text-amber-500" />
                            <MoonIcon v-else class="h-5 w-5 text-indigo-600" />
                        </button>

                        <div class="text-right hidden md:block">
                            <p class="text-[10px] text-emerald-600 dark:text-emerald-500/70 tracking-[0.2em] mb-1">LOCAL TIME</p>
                            <p class="text-2xl font-bold font-mono text-slate-900 dark:text-white dark:glow-text">{{ time }}</p>
                        </div>
                    </div>
                </div>

                <!-- KPI Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Today's Output -->
                    <div class="hud-card group">
                        <div class="hud-content p-6 h-full relative z-10 bg-white/70 dark:bg-[#0a0a16]/60 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-xl overflow-hidden flex flex-col justify-between shadow-sm dark:shadow-none">
                            <div class="absolute top-0 right-0 p-4 opacity-20 group-hover:opacity-40 transition-opacity">
                                <CubeIcon class="h-12 w-12 text-emerald-600 dark:text-emerald-400" />
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 tracking-[0.2em] uppercase font-bold mb-1">TODAY'S OUTPUT</p>
                                <h3 class="text-3xl font-black text-slate-900 dark:text-white dark:glow-text tracking-tight">
                                    {{ formatNumber(stats.today_qty) }}
                                </h3>
                            </div>
                            <div class="mt-4 flex items-center justify-between text-[10px]">
                                <span class="text-emerald-600 dark:text-emerald-500/70 font-bold">GROWTH: +{{ stats.growth }}%</span>
                                <span class="text-slate-450 dark:text-slate-500">YESTERDAY: {{ formatNumber(stats.yesterday_qty) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Quality Rate -->
                    <div class="hud-card group">
                        <div class="hud-content p-6 h-full relative z-10 bg-white/70 dark:bg-[#0a0a16]/60 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-xl overflow-hidden flex flex-col justify-between shadow-sm dark:shadow-none">
                            <div class="absolute top-0 right-0 p-4 opacity-20 group-hover:opacity-40 transition-opacity">
                                <ShieldCheckIcon class="h-12 w-12 text-cyan-600 dark:text-cyan-400" />
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 tracking-[0.2em] uppercase font-bold mb-1">QUALITY RATE</p>
                                <h3 class="text-3xl font-black text-slate-900 dark:text-white dark:glow-text tracking-tight">
                                    {{ stats.quality }}%
                                </h3>
                            </div>
                             <div class="mt-4 h-1 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-cyan-500 shadow-[0_0_10px_#22d3ee]" :style="{ width: `${stats.quality}%` }"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Performance -->
                    <div class="hud-card group">
                        <div class="hud-content p-6 h-full relative z-10 bg-white/70 dark:bg-[#0a0a16]/60 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-xl overflow-hidden flex flex-col justify-between shadow-sm dark:shadow-none">
                            <div class="absolute top-0 right-0 p-4 opacity-20 group-hover:opacity-40 transition-opacity">
                                <BoltIcon class="h-12 w-12 text-amber-600 dark:text-amber-400" />
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 tracking-[0.2em] uppercase font-bold mb-1">PERFORMANCE</p>
                                <h3 class="text-3xl font-black text-slate-900 dark:text-white dark:glow-text tracking-tight">
                                    {{ stats.performance }}%
                                </h3>
                            </div>
                             <div class="mt-4 h-1 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-amber-500 shadow-[0_0_10px_#f59e0b]" :style="{ width: `${stats.performance}%` }"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Availability -->
                    <div class="hud-card group">
                        <div class="hud-content p-6 h-full relative z-10 bg-white/70 dark:bg-[#0a0a16]/60 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-xl overflow-hidden flex flex-col justify-between shadow-sm dark:shadow-none">
                            <div class="absolute top-0 right-0 p-4 opacity-20 group-hover:opacity-40 transition-opacity">
                                <ClockIcon class="h-12 w-12 text-indigo-600 dark:text-indigo-400" />
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 tracking-[0.2em] uppercase font-bold mb-1">AVAILABILITY</p>
                                <h3 class="text-3xl font-black text-slate-900 dark:text-white dark:glow-text tracking-tight">
                                    {{ stats.availability }}%
                                </h3>
                            </div>
                             <div class="mt-4 h-1 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-indigo-500 shadow-[0_0_10px_#6366f1]" :style="{ width: `${stats.availability}%` }"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Section -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- OEE Result Gauge -->
                    <div class="hud-panel bg-white/75 dark:bg-[#0a0a16]/80 border border-slate-200 dark:border-white/5 shadow-sm dark:shadow-[0_0_20px_rgba(0,0,0,0.5)] p-8 flex flex-col items-center justify-center relative overflow-hidden group">
                        <div class="absolute top-4 left-4">
                            <h3 class="text-sm font-bold text-emerald-600 dark:text-emerald-400 tracking-widest uppercase">OVERALL OEE RESULT</h3>
                        </div>
                        <div class="panel-body p-6 flex-1 flex flex-row items-center justify-center gap-8 relative w-full">
                            <div class="w-1/2 h-[250px] relative">
                                <Doughnut 
                                    :data="oeeChartData" 
                                    :options="{ 
                                        ...commonOptions, 
                                        cutout: '75%',
                                        plugins: { legend: { display: false } },
                                        scales: { x: { display: false }, y: { display: false } }
                                    }" 
                                />
                                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-center pointer-events-none w-full">
                                    <p class="text-3xl font-black text-slate-900 dark:text-white dark:glow-text mb-1 leading-tight">{{ stats.oee }}%</p>
                                    <p class="text-[10px] text-emerald-650 dark:text-emerald-500/50 tracking-[0.2em] font-bold">OEE</p>
                                </div>
                            </div>
                            <div class="w-1/2 space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-mono text-slate-500 dark:text-slate-400 uppercase tracking-widest">WORLD CLASS</span>
                                    <span class="text-xs font-bold text-slate-800 dark:text-white">85%</span>
                                </div>
                                <div class="flex items-center justify-between border-t border-slate-200 dark:border-white/5 pt-4">
                                    <span class="text-[10px] font-mono text-slate-500 dark:text-slate-400 uppercase tracking-widest">STATUS</span>
                                    <span class="text-xs font-bold uppercase" :class="stats.oee >= 85 ? 'text-emerald-650 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400'">
                                        {{ stats.oee >= 85 ? 'Optimal' : 'Sub-Optimal' }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between border-t border-slate-200 dark:border-white/5 pt-4">
                                    <span class="text-[10px] font-mono text-slate-500 dark:text-slate-400 uppercase tracking-widest">TREND</span>
                                    <span class="text-xs font-bold text-emerald-650 dark:text-emerald-400 uppercase">IMPROVING</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Shift Productivity -->
                    <div class="lg:col-span-2 hud-panel bg-white/75 dark:bg-[#0a0a16]/80 border border-slate-200 dark:border-white/5 shadow-sm dark:shadow-[0_0_20px_rgba(0,0,0,0.5)] flex flex-col">
                        <div class="panel-header p-4 border-b border-slate-200 dark:border-white/5 bg-slate-50/50 dark:bg-white/5 flex items-center justify-between">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-emerald-600 dark:text-emerald-300 tracking-widest uppercase">
                                <ChartBarIcon class="h-4 w-4" /> Shift Productivity (Live)
                            </h3>
                        </div>
                        <div class="panel-body p-6 flex-1 space-y-6">
                            <div v-for="shift in shift_data" :key="shift.name" class="space-y-2">
                                <div class="flex justify-between items-end">
                                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">{{ shift.name }}</span>
                                    <span class="text-xs text-emerald-600 dark:text-emerald-400 font-mono">{{ formatNumber(shift.output) }} / {{ formatNumber(shift.target) }} units</span>
                                </div>
                                <div class="h-3 bg-slate-100 dark:bg-slate-900 rounded-full border border-slate-200 dark:border-white/5 overflow-hidden">
                                    <div 
                                        class="h-full bg-gradient-to-r from-emerald-600 to-emerald-400 shadow-[0_0_15px_rgba(16,185,129,0.3)] transition-all duration-1000"
                                        :style="{ width: `${Math.min((shift.output / shift.target) * 100, 100)}%` }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bottom Row -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Machine Status Feed -->
                    <div class="hud-panel bg-white/75 dark:bg-[#0a0a16]/80 border border-slate-200 dark:border-white/5 shadow-sm dark:shadow-[0_0_20px_rgba(0,0,0,0.5)]">
                        <div class="panel-header p-4 border-b border-slate-200 dark:border-white/5 bg-slate-50/50 dark:bg-white/5">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-cyan-600 dark:text-cyan-300 tracking-widest uppercase">
                                <CpuChipIcon class="h-4 w-4" /> Live Machine Status
                            </h3>
                        </div>
                        <div class="panel-body p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div v-for="machine in machine_statuses" :key="machine.id" class="p-4 bg-white dark:bg-white/5 border border-slate-200 dark:border-white/5 rounded-lg flex items-center justify-between group hover:border-emerald-500/30 transition-colors shadow-sm dark:shadow-none">
                                <div class="flex items-center gap-3">
                                    <div class="w-1.5 h-8 rounded-full" :class="machine.status === 'RUNNING' ? 'bg-emerald-500 shadow-[0_0_8px_#10b981]' : (machine.status === 'DOWNTIME' ? 'bg-rose-500 shadow-[0_0_8px_#f43f5e]' : 'bg-slate-400')"></div>
                                    <div>
                                        <p class="text-xs font-black text-slate-800 dark:text-white tracking-widest">{{ machine.name }}</p>
                                        <p class="text-[10px] text-slate-500">{{ machine.status }} • LAST UPDATE: {{ machine.last_update }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400">{{ formatNumber(machine.last_qty) }}</p>
                                    <p class="text-[8px] text-slate-450 dark:text-slate-600 uppercase">UNITS</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Logs -->
                    <div class="hud-panel bg-white/75 dark:bg-[#0a0a16]/80 border border-slate-200 dark:border-white/5 shadow-sm dark:shadow-[0_0_20px_rgba(0,0,0,0.5)] overflow-hidden">
                        <div class="panel-header p-4 border-b border-slate-200 dark:border-white/5 bg-slate-50/50 dark:bg-white/5">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-emerald-600 dark:text-emerald-300 tracking-widest uppercase">
                                <ArrowPathIcon class="h-4 w-4" /> Latest Production Logs
                            </h3>
                        </div>
                        <div class="panel-body p-0 overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="text-[10px] text-slate-500 font-bold uppercase tracking-wider border-b border-slate-200 dark:border-white/10 bg-slate-50/50 dark:bg-white/5">
                                        <th class="p-3">Time</th>
                                        <th class="p-3">Work Order</th>
                                        <th class="p-3">Product</th>
                                        <th class="p-3">Machine</th>
                                        <th class="p-3 text-right">Qty</th>
                                        <th class="p-3 text-right">Reject</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                                    <tr v-for="log in recent_logs" :key="log.id" class="hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                                        <td class="p-3 text-xs text-emerald-600 dark:text-emerald-400 font-mono">{{ log.time }}</td>
                                        <td class="p-3 text-xs font-bold text-slate-800 dark:text-white">{{ log.work_order }}</td>
                                        <td class="p-3 text-[10px] text-slate-500 dark:text-slate-300 uppercase truncate max-w-[150px]">{{ log.product }}</td>
                                        <td class="p-3 text-xs text-cyan-600 dark:text-cyan-400 font-mono">{{ log.machine }}</td>
                                        <td class="p-3 text-xs font-bold text-right text-slate-800 dark:text-white">{{ formatNumber(log.qty) }}</td>
                                        <td class="p-3 text-xs font-bold text-right text-rose-600 dark:text-rose-500">{{ formatNumber(log.rejects) }}</td>
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
    background-image: 
        linear-gradient(to right, rgba(16, 185, 129, 0.1) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(16, 185, 129, 0.1) 1px, transparent 1px);
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
    filter: drop-shadow(0 0 10px rgba(16, 185, 129, 0.2));
}

.hud-panel {
    backdrop-filter: blur(20px);
    border-radius: 12px;
}

/* Text Effects */
.glow-text {
    text-shadow: 0 0 10px currentColor;
}
</style>
