<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    CurrencyDollarIcon,
    ShoppingCartIcon,
    DocumentTextIcon,
    ChartPieIcon,
    BoltIcon,
    ClockIcon,
    UserCircleIcon,
    ArrowTrendingUpIcon
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
    salesTrend: Array,
    statusDist: Object,
    topCustomers: Array,
    recentOrders: Array,
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

// --- Formatters ---
const formatMillions = (val) => {
    if (Math.abs(val) >= 1000000) {
        return (val / 1000000).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' jt';
    }
    return val.toLocaleString('id-ID');
};

// --- Chart Options ---
const commonOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { labels: { color: '#94a3b8', font: { family: 'Space Mono' } } },
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
            callbacks: {
                label: (context) => {
                    let label = context.dataset.label || '';
                    if (label) label += ': ';
                    if (context.parsed.y !== undefined) {
                        label += formatCurrency(context.parsed.y);
                        // Add millions summary in parenthesis
                        if (Math.abs(context.parsed.y) >= 1000000) {
                            label += ` (${formatMillions(context.parsed.y)})`;
                        }
                    } else if (context.parsed.x !== undefined) {
                        label += formatCurrency(context.parsed.x);
                        if (Math.abs(context.parsed.x) >= 1000000) {
                            label += ` (${formatMillions(context.parsed.x)})`;
                        }
                    }
                    return label;
                }
            }
        },
    },
    scales: {
        x: { 
            grid: { color: 'rgba(217, 70, 239, 0.1)', drawBorder: false },
            ticks: { color: '#64748b', font: { family: 'Space Mono', size: 10 } }
        },
        y: { 
            grid: { color: 'rgba(217, 70, 239, 0.1)', drawBorder: false },
            ticks: { 
                color: '#64748b', 
                font: { family: 'Space Mono', size: 10 },
                callback: (value) => formatMillions(value)
            }
        },
    },
}));

// -- Chart Data --

// 1. Sales Trend (Line Chart)
const trendData = computed(() => ({
    labels: props.salesTrend.map(t => t.month),
    datasets: [
        {
            label: 'Monthly Revenue',
            data: props.salesTrend.map(t => t.total),
            borderColor: '#d946ef', // Fuchsia
            backgroundColor: (ctx) => {
                const canvas = ctx.chart.ctx;
                const gradient = canvas.createLinearGradient(0, 0, 0, 300);
                gradient.addColorStop(0, 'rgba(217, 70, 239, 0.4)');
                gradient.addColorStop(1, 'rgba(217, 70, 239, 0.0)');
                return gradient;
            },
            fill: true,
            tension: 0.4,
            borderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6,
            pointBackgroundColor: '#fff'
        }
    ]
}));

// 2. Status Distribution (Doughnut)
const statusColors = {
    'draft': '#64748b',
    'confirmed': '#22d3ee',
    'processing': '#f59e0b',
    'shipped': '#8b5cf6',
    'delivered': '#10b981',
    'cancelled': '#f43f5e',
};

const statusData = computed(() => {
    const labels = Object.keys(props.statusDist);
    return {
        labels: labels.map(l => l.toUpperCase()),
        datasets: [{
            data: Object.values(props.statusDist),
            backgroundColor: labels.map(l => statusColors[l] || '#94a3b8'),
            borderWidth: 0,
            hoverOffset: 10
        }]
    };
});

// 3. Top Customers (Bar)
const customerData = computed(() => ({
    labels: props.topCustomers.map(c => c.name),
    datasets: [{
        label: 'Revenue contribution',
        data: props.topCustomers.map(c => c.total_revenue),
        backgroundColor: '#22d3ee',
        borderRadius: 4,
        barThickness: 25,
    }]
}));

</script>

<template>
    <Head title="Sales Command" />

    <AppLayout title="Sales Command" :render-header="false">
        <div class="min-h-screen bg-[#050510] relative overflow-hidden font-mono text-cyan-50 selection:bg-fuchsia-500/30">
            
            <!-- Dynamic Background -->
            <div class="fixed inset-0 z-0 pointer-events-none">
                <div class="absolute inset-0 bg-gradient-to-b from-fuchsia-950/20 to-[#050510]"></div>
                <div class="perspective-grid absolute inset-0 opacity-20"></div>
                <div class="absolute top-[-10%] right-[20%] w-[600px] h-[600px] bg-fuchsia-600/10 blur-[150px] rounded-full animate-float"></div>
                <div class="stars"></div>
            </div>

            <div class="relative z-10 p-6 space-y-8">
                
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-white/10 pb-4 backdrop-blur-sm">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-0.5 text-[10px] bg-white/5 border border-white/10 rounded text-slate-400 tracking-[0.2em]">HUB.SALES.4.0</span>
                            <span class="flex items-center gap-1.5 px-2 py-0.5 text-[10px] bg-fuchsia-500/10 border border-fuchsia-500/20 rounded text-fuchsia-400 tracking-wider animate-pulse">
                                <span class="w-1.5 h-1.5 rounded-full bg-fuchsia-400"></span> REVENUE STREAM ACTIVE
                            </span>
                        </div>
                        <h1 class="text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-fuchsia-400 via-white to-fuchsia-400 tracking-widest uppercase glow-text">
                            SALES HUB COMMAND
                        </h1>
                    </div>
                    
                    <div class="flex items-center gap-6">
                        <div class="text-right hidden md:block">
                            <p class="text-[10px] text-fuchsia-500/70 tracking-[0.2em] mb-1">LOCAL TIME</p>
                            <p class="text-2xl font-bold font-mono text-white glow-text">{{ time }}</p>
                            <!-- DEBUG INFO -->
                            <div class="mt-2 text-[8px] text-slate-500 font-mono text-right opacity-50 hover:opacity-100 transition-opacity">
                                <p>SVR: {{ stats.debug_server_time }}</p>
                                <p>LAST: {{ stats.debug_latest_order }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KPI Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Revenue -->
                    <div class="hud-card group delay-100">
                        <div class="hud-content p-6 h-full relative z-10 bg-[#0a0a16]/60 backdrop-blur-xl border border-white/5 rounded-xl overflow-hidden flex flex-col justify-between">
                            <div class="absolute top-0 right-0 p-4 opacity-20 group-hover:opacity-40 transition-opacity">
                                <CurrencyDollarIcon class="h-12 w-12 text-fuchsia-400" />
                            </div>
                            <div>
                                <p class="text-xs text-slate-400 tracking-[0.2em] uppercase font-bold mb-1">MONTHLY REVENUE</p>
                                <h3 class="text-2xl font-black text-white glow-text tracking-tight">
                                    {{ formatCurrency(stats.monthly_revenue) }}
                                </h3>
                            </div>
                            <div class="mt-4 h-1 bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-fuchsia-500 shadow-[0_0_10px_#d946ef] transition-all duration-1000" :style="{ width: stats.monthly_revenue > 0 ? Math.max(Math.min((stats.monthly_revenue / 1000000000) * 100, 100), 5) + '%' : '0%' }"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Count -->
                    <div class="hud-card group delay-200">
                         <div class="hud-content p-6 h-full relative z-10 bg-[#0a0a16]/60 backdrop-blur-xl border border-white/5 rounded-xl overflow-hidden flex flex-col justify-between">
                            <div class="absolute top-0 right-0 p-4 opacity-20 group-hover:opacity-40 transition-opacity">
                                <ShoppingCartIcon class="h-12 w-12 text-cyan-400" />
                            </div>
                            <div>
                                <p class="text-xs text-slate-400 tracking-[0.2em] uppercase font-bold mb-1">TOTAL ORDERS</p>
                                <h3 class="text-3xl font-black text-white glow-text tracking-tight">
                                    {{ formatNumber(stats.order_count) }}
                                </h3>
                            </div>
                            <div class="mt-4 h-1 bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-cyan-500 shadow-[0_0_10px_#22d3ee] transition-all duration-1000" :style="{ width: stats.order_count > 0 ? Math.max(Math.min((stats.order_count / 100) * 100, 100), 5) + '%' : '0%' }"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Quotations -->
                    <div class="hud-card group delay-300">
                         <div class="hud-content p-6 h-full relative z-10 bg-[#0a0a16]/60 backdrop-blur-xl border border-white/5 rounded-xl overflow-hidden flex flex-col justify-between">
                            <div class="absolute top-0 right-0 p-4 opacity-20 group-hover:opacity-40 transition-opacity">
                                <DocumentTextIcon class="h-12 w-12 text-amber-400" />
                            </div>
                            <div>
                                <p class="text-xs text-slate-400 tracking-[0.2em] uppercase font-bold mb-1">PENDING QUOTES</p>
                                <h3 class="text-3xl font-black text-white glow-text tracking-tight">
                                    {{ formatNumber(stats.pending_quotations) }}
                                </h3>
                            </div>
                             <div class="mt-4 h-1 bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-amber-500 shadow-[0_0_10px_#f59e0b] transition-all duration-1000" :style="{ width: stats.pending_quotations > 0 ? Math.max(Math.min((stats.pending_quotations / 50) * 100, 100), 5) + '%' : '0%' }"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Avg Order Value -->
                    <div class="hud-card group delay-400">
                         <div class="hud-content p-6 h-full relative z-10 bg-[#0a0a16]/60 backdrop-blur-xl border border-white/5 rounded-xl overflow-hidden flex flex-col justify-between">
                            <div class="absolute top-0 right-0 p-4 opacity-20 group-hover:opacity-40 transition-opacity">
                                <ArrowTrendingUpIcon class="h-12 w-12 text-emerald-400" />
                            </div>
                             <div>
                                <p class="text-xs text-slate-400 tracking-[0.2em] uppercase font-bold mb-1">AVG ORDER VALUE</p>
                                <h3 class="text-2xl font-black text-white glow-text tracking-tight">
                                    {{ formatCurrency(stats.avg_order_value) }}
                                </h3>
                            </div>
                             <div class="mt-4 h-1 bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-emerald-500 shadow-[0_0_10px_#10b981] transition-all duration-1000" :style="{ width: stats.avg_order_value > 0 ? Math.max(Math.min((stats.avg_order_value / 10000000) * 100, 100), 5) + '%' : '0%' }"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Sales Trend -->
                    <div class="lg:col-span-2 hud-panel min-h-[350px]">
                        <div class="panel-header flex items-center justify-between p-4 border-b border-white/5 bg-white/5">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-fuchsia-300 tracking-widest uppercase">
                                <BoltIcon class="h-4 w-4" /> Revenue Performance (6mo)
                            </h3>
                        </div>
                        <div class="panel-body p-4 h-[300px] relative">
                            <Line :data="trendData" :options="commonOptions" />
                        </div>
                    </div>

                    <!-- Status Dist -->
                    <div class="hud-panel min-h-[350px] flex flex-col">
                        <div class="panel-header p-4 border-b border-white/5 bg-white/5">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-cyan-300 tracking-widest uppercase">
                                <ChartPieIcon class="h-4 w-4" /> Order Pipeline
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
                                    <p class="text-[10px] text-slate-500 tracking-[0.2em] uppercase font-bold">STATUSES</p>
                                    <p class="text-3xl font-black text-white glow-text leading-tight">{{ Object.keys(props.statusDist).length }}</p>
                                </div>
                            </div>
                            <div class="w-1/2 space-y-4">
                                <div v-for="(val, label, index) in props.statusDist" :key="label" class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-2.5 h-2.5 rounded-sm" :style="{ backgroundColor: statusData.datasets[0].backgroundColor[index] }"></div>
                                        <span class="text-[10px] font-mono text-slate-400 uppercase tracking-widest">{{ label }}</span>
                                    </div>
                                    <span class="text-xs font-bold text-white">{{ val }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bottom Row -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                     <!-- Recent Orders -->
                     <div class="hud-panel overflow-hidden">
                         <div class="panel-header p-4 border-b border-white/5 bg-white/5">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-fuchsia-300 tracking-widest uppercase">
                                <ShoppingCartIcon class="h-4 w-4" /> Recent Transactions
                            </h3>
                        </div>
                        <div class="panel-body p-0 overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="text-[10px] text-slate-500 font-bold uppercase tracking-wider border-b border-white/10 bg-white/5">
                                        <th class="p-3">Order #</th>
                                        <th class="p-3">Customer</th>
                                        <th class="p-3">Status</th>
                                        <th class="p-3 text-right">Amount</th>
                                        <th class="p-3 text-right">Date</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5">
                                    <tr 
                                        v-for="order in recentOrders" 
                                        :key="order.id" 
                                        class="hover:bg-white/5 transition-colors group"
                                    >
                                        <td class="p-3 text-[10px] font-mono text-fuchsia-500/70 border-l-2 border-transparent group-hover:border-fuchsia-500 transition-colors">
                                            {{ order.so_number }}
                                        </td>
                                        <td class="p-3 text-xs font-bold text-white uppercase">{{ order.customer }}</td>
                                        <td class="p-3">
                                            <span 
                                                class="px-2 py-0.5 text-[10px] font-bold border rounded uppercase"
                                                :class="{
                                                    'bg-cyan-500/10 text-cyan-400 border-cyan-500/30': order.status === 'confirmed',
                                                    'bg-amber-500/10 text-amber-400 border-amber-500/30': order.status === 'processing',
                                                    'bg-emerald-500/10 text-emerald-400 border-emerald-500/30': order.status === 'delivered',
                                                    'bg-slate-500/10 text-slate-400 border-slate-500/30': order.status === 'draft',
                                                }"
                                            >
                                                {{ order.status }}
                                            </span>
                                        </td>
                                        <td class="p-3 text-xs font-mono text-right text-white">{{ formatCurrency(order.amount) }}</td>
                                        <td class="p-3 text-[10px] text-slate-500 text-right">{{ order.date }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                     </div>

                     <!-- Top Customers -->
                     <div class="hud-panel">
                        <div class="panel-header p-4 border-b border-white/5 bg-white/5">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-cyan-300 tracking-widest uppercase">
                                <UserCircleIcon class="h-4 w-4" /> Top Client Revenue
                            </h3>
                        </div>
                        <div class="panel-body p-4 h-[500px]">
                            <Bar 
                                :data="customerData" 
                                :options="{ 
                                    ...commonOptions, 
                                    indexAxis: 'y', 
                                    plugins: { 
                                        legend: { display: false },
                                        tooltip: {
                                            callbacks: {
                                                label: (context) => {
                                                    return `Revenue: ${formatCurrency(context.parsed.x)} (${formatMillions(context.parsed.x)})`;
                                                }
                                            }
                                        }
                                    },
                                    scales: {
                                        x: {
                                            grid: { color: 'rgba(34, 211, 238, 0.1)', drawBorder: false },
                                            ticks: { 
                                                color: '#64748b', 
                                                font: { family: 'Space Mono', size: 10 },
                                                callback: (value) => formatMillions(value)
                                            }
                                        },
                                        y: {
                                            grid: { display: false },
                                            ticks: { color: '#64748b', font: { family: 'Space Mono', size: 10 } }
                                        }
                                    }
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

/* Background Effects */
.perspective-grid {
    background-image: 
        linear-gradient(to right, rgba(217, 70, 239, 0.1) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(217, 70, 239, 0.1) 1px, transparent 1px);
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
    filter: drop-shadow(0 0 10px rgba(217, 70, 239, 0.2));
}

.hud-panel {
    background: rgba(10, 10, 22, 0.8);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
    overflow: hidden;
}

/* Text Effects */
.glow-text {
    text-shadow: 0 0 10px currentColor;
}
</style>
