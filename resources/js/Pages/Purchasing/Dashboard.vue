<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    CurrencyDollarIcon,
    ShoppingCartIcon,
    ClipboardDocumentCheckIcon,
    CheckBadgeIcon,
    FunnelIcon,
    UserGroupIcon,
    ClockIcon,
    ExclamationTriangleIcon,
    SunIcon,
    MoonIcon
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
    stats: Object,
    spendTrend: Array,
    statusDist: Object,
    topSuppliers: Array,
    recentRequests: Array,
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
            titleColor: '#f59e0b',
            bodyColor: '#e2e8f0',
            borderColor: '#f59e0b',
            borderWidth: 1,
            padding: 12,
            titleFont: { family: 'Space Mono', weight: 'bold' },
            bodyFont: { family: 'Space Mono' },
            displayColors: false,
        },
    },
    scales: {
        x: { 
            grid: { color: 'rgba(245, 158, 11, 0.1)', drawBorder: false },
            ticks: { color: '#64748b', font: { family: 'Space Mono', size: 10 } }
        },
        y: { 
            grid: { color: 'rgba(245, 158, 11, 0.1)', drawBorder: false },
            ticks: { color: '#64748b', font: { family: 'Space Mono', size: 10 } }
        },
    },
}));

// -- Chart Data --

// 1. Spend Trend (Line/Bar)
const spendData = computed(() => ({
    labels: props.spendTrend.map(t => t.month),
    datasets: [
        {
            label: 'Monthly Spend',
            data: props.spendTrend.map(t => t.total),
            borderColor: '#f59e0b', // Amber
            backgroundColor: (ctx) => {
                const canvas = ctx.chart.ctx;
                const gradient = canvas.createLinearGradient(0, 0, 0, 300);
                gradient.addColorStop(0, 'rgba(245, 158, 11, 0.4)');
                gradient.addColorStop(1, 'rgba(245, 158, 11, 0.0)');
                return gradient;
            },
            fill: true,
            tension: 0.4,
            borderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6,
            pointBackgroundColor: '#fff',
        }
    ]
}));

// 2. Status Distribution (Doughnut)
// Map statuses to colors
const statusColors = {
    'pending': '#f43f5e', // Rose
    'approved': '#22d3ee', // Cyan
    'partially_approved': '#a855f7', // Purple
    'ordered': '#f59e0b', // Amber
    'completed': '#10b981', // Emerald
    'cancelled': '#64748b', // Slate
};

const statusData = computed(() => {
    const labels = Object.keys(props.statusDist);
    return {
        labels: labels.map(l => l.replace('_', ' ').toUpperCase()),
        datasets: [{
            data: Object.values(props.statusDist),
            backgroundColor: labels.map(l => statusColors[l] || '#94a3b8'),
            borderWidth: 0,
        }]
    };
});

// 3. Top Suppliers (Horizontal Bar)
const supplierData = computed(() => ({
    labels: props.topSuppliers.map(s => s.name),
    datasets: [{
        label: 'Total Spend',
        data: props.topSuppliers.map(s => s.total_spend),
        backgroundColor: '#f59e0b',
        borderRadius: 4,
        barThickness: 15,
    }]
}));
</script>

<template>
    <Head title="Procurement Ops" />

    <AppLayout title="Procurement Ops" :render-header="false">
        <div class="min-h-screen bg-slate-50 dark:bg-[#050510] relative overflow-hidden font-mono text-slate-800 dark:text-cyan-50 selection:bg-cyan-500/30 transition-colors duration-300">
            
            <!-- Dynamic Background -->
            <div class="fixed inset-0 z-0 pointer-events-none">
                <div class="absolute inset-0 bg-gradient-to-b from-amber-500/5 to-slate-100 dark:from-cyan-950/20 dark:to-[#050510]"></div>
                <div class="perspective-grid absolute inset-0 opacity-[0.15] dark:opacity-20"></div>
                <div class="absolute top-[-10%] left-[20%] w-[600px] h-[600px] bg-cyan-500/5 dark:bg-cyan-600/10 blur-[150px] rounded-full animate-float"></div>
                <div class="absolute bottom-[-10%] right-[10%] w-[500px] h-[500px] bg-cyan-550/5 dark:bg-cyan-600/10 blur-[150px] rounded-full animate-float-delayed"></div>
                <div class="stars"></div>
            </div>

            <div class="relative z-10 p-6 space-y-8">
                
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-slate-200 dark:border-white/10 pb-4 backdrop-blur-sm">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-0.5 text-[10px] bg-slate-200/50 dark:bg-white/5 border border-slate-300 dark:border-white/10 rounded text-slate-550 dark:text-slate-400 tracking-[0.2em]">PROC.SYS.3.0</span>
                            <span class="flex items-center gap-1.5 px-2 py-0.5 text-[10px] bg-amber-500/10 border border-amber-500/20 rounded text-amber-600 dark:text-amber-400 tracking-wider animate-pulse">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 dark:bg-amber-400"></span> OPS ACTIVE
                            </span>
                        </div>
                        <h1 class="text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-amber-600 via-slate-900 to-amber-600 dark:from-amber-400 dark:via-white dark:to-amber-400 tracking-widest uppercase dark:glow-text">
                            PROCUREMENT OPS
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
                            <p class="text-[10px] text-amber-600 dark:text-amber-500/70 tracking-[0.2em] mb-1">LOCAL TIME</p>
                            <p class="text-2xl font-bold font-mono text-slate-900 dark:text-white dark:glow-text">{{ time }}</p>
                        </div>
                    </div>
                </div>

                <!-- KPI Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Monthly Spend -->
                    <div class="hud-card group delay-100">
                        <div class="hud-content p-6 h-full relative z-10 bg-white/70 dark:bg-[#0a0a16]/60 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-xl overflow-hidden flex flex-col justify-between shadow-sm dark:shadow-none">
                            <div class="absolute top-0 right-0 p-4 opacity-20 group-hover:opacity-40 transition-opacity">
                                <CurrencyDollarIcon class="h-12 w-12 text-amber-550 dark:text-amber-400" />
                            </div>
                            <div>
                                <p class="text-xs text-slate-505 dark:text-slate-400 tracking-[0.2em] uppercase font-bold mb-1">MONTHLY SPEND</p>
                                <h3 class="text-2xl font-black text-slate-900 dark:text-white dark:glow-text tracking-tight">
                                    {{ formatCurrency(stats.monthly_spend) }}
                                </h3>
                            </div>
                            <div class="mt-4 h-1 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-amber-500 shadow-[0_0_10px_#f59e0b] transition-all duration-1000" :style="{ width: stats.monthly_spend > 0 ? Math.max(Math.min((stats.monthly_spend / 100000000) * 100, 100), 5) + '%' : '0%' }"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Open Orders -->
                    <div class="hud-card group delay-200">
                         <div class="hud-content p-6 h-full relative z-10 bg-white/70 dark:bg-[#0a0a16]/60 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-xl overflow-hidden flex flex-col justify-between shadow-sm dark:shadow-none">
                            <div class="absolute top-0 right-0 p-4 opacity-20 group-hover:opacity-40 transition-opacity">
                                <ShoppingCartIcon class="h-12 w-12 text-cyan-555 dark:text-cyan-400" />
                            </div>
                            <div>
                                <p class="text-xs text-slate-505 dark:text-slate-400 tracking-[0.2em] uppercase font-bold mb-1">OPEN ORDERS</p>
                                <h3 class="text-3xl font-black text-slate-900 dark:text-white dark:glow-text tracking-tight">
                                    {{ formatNumber(stats.open_orders) }}
                                </h3>
                            </div>
                            <div class="mt-4 h-1 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-cyan-500 shadow-[0_0_10px_#06b6d4] transition-all duration-1000" :style="{ width: stats.open_orders > 0 ? Math.max(Math.min((stats.open_orders / 100) * 100, 100), 5) + '%' : '0%' }"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Waiting Approval -->
                    <div class="hud-card group delay-300">
                         <div class="hud-content p-6 h-full relative z-10 bg-white/70 dark:bg-[#0a0a16]/60 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-xl overflow-hidden flex flex-col justify-between shadow-sm dark:shadow-none">
                            <div class="absolute top-0 right-0 p-4 opacity-20 group-hover:opacity-40 transition-opacity">
                                <ClipboardDocumentCheckIcon class="h-12 w-12 text-rose-550 dark:text-rose-400" />
                            </div>
                            <div>
                                <p class="text-xs text-slate-505 dark:text-slate-400 tracking-[0.2em] uppercase font-bold mb-1">WAITING APPROVAL</p>
                                <h3 class="text-3xl font-black text-slate-900 dark:text-white dark:glow-text tracking-tight">
                                    {{ formatNumber(stats.pending_approvals) }}
                                </h3>
                            </div>
                             <div class="mt-4 h-1 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-rose-500 shadow-[0_0_10px_#f43f5e] transition-all duration-1000" :style="{ width: stats.pending_approvals > 0 ? Math.max(Math.min((stats.pending_approvals / 100) * 100, 100), 5) + '%' : '0%' }"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Supplier Perf -->
                    <div class="hud-card group delay-400">
                         <div class="hud-content p-6 h-full relative z-10 bg-white/70 dark:bg-[#0a0a16]/60 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-xl overflow-hidden flex flex-col justify-between shadow-sm dark:shadow-none">
                            <div class="absolute top-0 right-0 p-4 opacity-20 group-hover:opacity-40 transition-opacity">
                                <CheckBadgeIcon class="h-12 w-12 text-emerald-555 dark:text-emerald-400" />
                            </div>
                             <div>
                                <p class="text-xs text-slate-505 dark:text-slate-400 tracking-[0.2em] uppercase font-bold mb-1">VENDOR RATING</p>
                                <h3 class="text-3xl font-black text-slate-900 dark:text-white dark:glow-text tracking-tight">
                                    {{ stats.supplier_performance }}%
                                </h3>
                            </div>
                             <div class="mt-4 h-1 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-emerald-500 shadow-[0_0_10px_#10b981] transition-all duration-1000" :style="{ width: stats.supplier_performance > 0 ? Math.max(Math.min((stats.supplier_performance / 100) * 100, 100), 5) + '%' : '0%' }"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Spend Analysis -->
                    <div class="lg:col-span-2 hud-panel bg-white/75 dark:bg-[#0a0a16]/80 border border-slate-200 dark:border-white/5 shadow-sm dark:shadow-[0_0_20px_rgba(0,0,0,0.5)] min-h-[350px]">
                        <div class="panel-header flex items-center justify-between p-4 border-b border-slate-200 dark:border-white/5 bg-slate-50/50 dark:bg-white/5">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-amber-600 dark:text-amber-300 tracking-widest uppercase">
                                <CurrencyDollarIcon class="h-4 w-4" /> Spend Analysis (6mo)
                            </h3>
                        </div>
                        <div class="panel-body p-4 h-[300px] relative">
                            <Line :data="spendData" :options="commonOptions" />
                        </div>
                    </div>

                    <!-- Order Distribution -->
                    <div class="hud-panel bg-white/75 dark:bg-[#0a0a16]/80 border border-slate-200 dark:border-white/5 shadow-sm dark:shadow-[0_0_20px_rgba(0,0,0,0.5)] min-h-[350px] flex flex-col">
                        <div class="panel-header p-4 border-b border-slate-200 dark:border-white/5 bg-slate-50/50 dark:bg-white/5">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-cyan-600 dark:text-cyan-300 tracking-widest uppercase">
                                <FunnelIcon class="h-4 w-4" /> Order Distribution
                            </h3>
                        </div>
                        <div class="panel-body p-6 flex-1 flex flex-row items-center justify-center gap-8 relative">
                            <div class="w-1/2 h-[250px] relative">
                                <Doughnut 
                                    :data="statusData" 
                                    :options="{ 
                                        ...commonOptions, 
                                        cutout: '75%', 
                                        scales: { x: { display: false }, y: { display: false } },
                                        plugins: { legend: { display: false } } 
                                    }" 
                                />
                                 <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-center pointer-events-none w-full">
                                    <p class="text-[10px] text-slate-550 tracking-[0.2em] uppercase font-bold">TOTAL POs</p>
                                    <p class="text-3xl font-black text-slate-900 dark:text-white dark:glow-text leading-tight">{{ formatNumber(Object.values(props.statusDist).reduce((a, b) => a + b, 0)) }}</p>
                                </div>
                            </div>
                            <div class="w-1/2 space-y-4">
                                <div v-for="(val, label, index) in props.statusDist" :key="label" class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-2.5 h-2.5 rounded-sm" :style="{ backgroundColor: statusData.datasets[0].backgroundColor[index] }"></div>
                                        <span class="text-[10px] font-mono text-slate-500 dark:text-slate-400 uppercase tracking-widest">{{ label }}</span>
                                    </div>
                                    <span class="text-xs font-bold text-slate-800 dark:text-white">{{ val }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bottom Row -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                     <!-- Urgent Requests -->
                     <div class="hud-panel bg-white/75 dark:bg-[#0a0a16]/80 border border-slate-200 dark:border-white/5 shadow-sm dark:shadow-[0_0_20px_rgba(0,0,0,0.5)] overflow-hidden">
                          <div class="panel-header p-4 border-b border-slate-200 dark:border-white/5 bg-slate-50/50 dark:bg-white/5">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-rose-600 dark:text-rose-350 tracking-widest uppercase">
                                <ExclamationTriangleIcon class="h-4 w-4" /> Urgent Requests
                            </h3>
                        </div>
                        <div class="panel-body p-0 overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="text-[10px] text-slate-500 font-bold uppercase tracking-wider border-b border-slate-200 dark:border-white/10 bg-slate-50/50 dark:bg-white/5">
                                        <th class="p-3">Req ID</th>
                                        <th class="p-3">Requester</th>
                                        <th class="p-3">Description</th>
                                        <th class="p-3 text-right">Date</th>
                                        <th class="p-3 text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                                    <tr 
                                        v-for="req in recentRequests" 
                                        :key="req.id" 
                                        class="hover:bg-slate-50 dark:hover:bg-white/5 transition-colors group"
                                    >
                                        <td class="p-3 text-[10px] font-mono text-cyan-600 dark:text-cyan-500/70 border-l-2 border-transparent group-hover:border-rose-500 transition-colors">
                                            {{ req.request_number }}
                                        </td>
                                        <td class="p-3 text-xs font-bold text-slate-800 dark:text-white uppercase">{{ req.requester }}</td>
                                        <td class="p-3 text-xs text-slate-500 dark:text-slate-400 truncate max-w-[200px]">{{ req.description }}</td>
                                        <td class="p-3 text-[10px] text-slate-500 text-right">{{ req.date }}</td>
                                        <td class="p-3 text-center">
                                            <Link 
                                                :href="route('purchasing.requests.show', req.id)" 
                                                class="px-2 py-1 text-[10px] bg-rose-500/20 text-rose-600 dark:text-rose-455 rounded hover:bg-rose-500 hover:text-white transition-colors uppercase tracking-wider"
                                            >
                                                Review
                                            </Link>
                                        </td>
                                    </tr>
                                    <tr v-if="recentRequests.length === 0" class="text-center">
                                        <td colspan="5" class="p-8 text-slate-400 dark:text-slate-500 text-xs uppercase tracking-wider">No pending urgent requests</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                     </div>

                      <!-- Top Suppliers -->
                      <div class="hud-panel bg-white/75 dark:bg-[#0a0a16]/80 border border-slate-200 dark:border-white/5 shadow-sm dark:shadow-[0_0_20px_rgba(0,0,0,0.5)]">
                        <div class="panel-header p-4 border-b border-slate-200 dark:border-white/5 bg-slate-50/50 dark:bg-white/5">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-cyan-600 dark:text-cyan-300 tracking-widest uppercase">
                                <UserGroupIcon class="h-4 w-4" /> Top Suppliers (YTD)
                            </h3>
                        </div>
                        <div class="panel-body p-4 h-[300px]">
                            <Bar 
                                :data="supplierData" 
                                :options="{ 
                                    ...commonOptions, 
                                    indexAxis: 'y', 
                                    plugins: { legend: { display: false } } 
                                }" 
                            />
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

/* Background Effects - Amber Version */
.perspective-grid {
    background-image: 
        linear-gradient(to right, rgba(245, 158, 11, 0.1) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(245, 158, 11, 0.1) 1px, transparent 1px);
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

@keyframes float-delayed {
    0%, 100% { transform: translate(0, 0); }
    50% { transform: translate(20px, -20px); }
}

/* Card Styling */
.hud-card {
    transition: all 0.3s ease;
}
.hud-card:hover {
    transform: translateY(-5px);
    filter: drop-shadow(0 0 10px rgba(245, 158, 11, 0.2));
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
