<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    CubeIcon,
    ArrowPathIcon,
    ArchiveBoxIcon,
    Bars3Icon,
    Square3Stack3DIcon,
    BoltIcon,
    ClockIcon,
    ChartBarIcon,
    TruckIcon
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
    trends: Array,
    stockByCategory: Array,
    stockByWarehouse: Array,
    recentMovements: Array,
    lowStockItems: Array,
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
const commonOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { labels: { color: '#94a3b8', font: { family: 'Space Mono' } } },
        tooltip: {
            backgroundColor: 'rgba(5, 5, 16, 0.9)',
            titleColor: '#22d3ee',
            bodyColor: '#e2e8f0',
            borderColor: '#22d3ee',
            borderWidth: 1,
            padding: 12,
            titleFont: { family: 'Space Mono', weight: 'bold' },
            bodyFont: { family: 'Space Mono' },
            displayColors: false,
        },
    },
    scales: {
        x: { 
            grid: { color: 'rgba(6, 182, 212, 0.1)', drawBorder: false },
            ticks: { color: '#64748b', font: { family: 'Space Mono', size: 10 } }
        },
        y: { 
            grid: { color: 'rgba(6, 182, 212, 0.1)', drawBorder: false },
            ticks: { color: '#64748b', font: { family: 'Space Mono', size: 10 } }
        },
    },
}));

// -- Chart Data --

// 1. Stock Trends (Area Chart)
const trendData = computed(() => ({
    labels: props.trends.map(t => t.date),
    datasets: [
        {
            label: 'Incoming',
            data: props.trends.map(t => t.incoming),
            borderColor: '#10b981', // Emerald
            backgroundColor: (ctx) => {
                const canvas = ctx.chart.ctx;
                const gradient = canvas.createLinearGradient(0, 0, 0, 300);
                gradient.addColorStop(0, 'rgba(16, 185, 129, 0.4)');
                gradient.addColorStop(1, 'rgba(16, 185, 129, 0.0)');
                return gradient;
            },
            fill: true,
            tension: 0.4,
            borderWidth: 2,
            pointRadius: 0,
            pointHoverRadius: 6
        },
        {
            label: 'Outgoing',
            data: props.trends.map(t => t.outgoing),
            borderColor: '#8b5cf6', // Violet
            backgroundColor: (ctx) => {
                const canvas = ctx.chart.ctx;
                const gradient = canvas.createLinearGradient(0, 0, 0, 300);
                gradient.addColorStop(0, 'rgba(139, 92, 246, 0.4)');
                gradient.addColorStop(1, 'rgba(139, 92, 246, 0.0)');
                return gradient;
            },
            fill: true,
            tension: 0.4,
            borderWidth: 2,
            pointRadius: 0,
            pointHoverRadius: 6
        }
    ]
}));

// 2. Stock by Category (Doughnut)
const categoryData = computed(() => ({
    labels: props.stockByCategory.map(c => c.name),
    datasets: [{
        data: props.stockByCategory.map(c => c.count),
        backgroundColor: [
            '#22d3ee', // Cyan
            '#10b981', // Emerald
            '#8b5cf6', // Violet
            '#f59e0b', // Amber
            '#f43f5e', // Rose
        ],
        borderWidth: 0,
        hoverOffset: 10
    }]
}));

// 3. Stock by Warehouse (Bar)
const warehouseData = computed(() => ({
    labels: props.stockByWarehouse.map(w => w.name),
    datasets: [{
        label: 'Items Stored',
        data: props.stockByWarehouse.map(w => w.total_qty),
        backgroundColor: '#06b6d4',
        borderRadius: 4,
        barThickness: 30,
    }]
}));

</script>

<template>
    <Head title="Inventory Command" />

    <AppLayout title="Inventory Command" :render-header="false">
        <div class="min-h-screen bg-[#050510] relative overflow-hidden font-mono text-cyan-50 selection:bg-cyan-500/30">
            
            <!-- Dynamic Background -->
            <div class="fixed inset-0 z-0 pointer-events-none">
                <div class="absolute inset-0 bg-gradient-to-b from-emerald-950/20 to-[#050510]"></div>
                <div class="perspective-grid absolute inset-0 opacity-20"></div>
                <div class="absolute top-[-10%] right-[20%] w-[600px] h-[600px] bg-emerald-600/10 blur-[150px] rounded-full animate-float"></div>
                <div class="stars"></div>
            </div>

            <div class="relative z-10 p-6 space-y-8">
                
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-white/10 pb-4 backdrop-blur-sm">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-0.5 text-[10px] bg-white/5 border border-white/10 rounded text-slate-400 tracking-[0.2em]">INV.MOD.2.0</span>
                            <span class="flex items-center gap-1.5 px-2 py-0.5 text-[10px] bg-cyan-500/10 border border-cyan-500/20 rounded text-cyan-400 tracking-wider animate-pulse">
                                <span class="w-1.5 h-1.5 rounded-full bg-cyan-400"></span> STOCK REALTIME
                            </span>
                        </div>
                        <h1 class="text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 via-white to-emerald-400 tracking-widest uppercase glow-text">
                            INVENTORY COMMAND
                        </h1>
                    </div>
                    
                    <div class="flex items-center gap-6">
                        <div class="text-right hidden md:block">
                            <p class="text-[10px] text-emerald-500/70 tracking-[0.2em] mb-1">LOCAL TIME</p>
                            <p class="text-2xl font-bold font-mono text-white glow-text">{{ time }}</p>
                        </div>
                    </div>
                </div>

                <!-- KPI Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Valuation -->
                    <div class="hud-card group delay-100">
                        <div class="hud-content p-6 h-full relative z-10 bg-[#0a0a16]/60 backdrop-blur-xl border border-white/5 rounded-xl overflow-hidden flex flex-col justify-between">
                            <div class="absolute top-0 right-0 p-4 opacity-20 group-hover:opacity-40 transition-opacity">
                                <BoltIcon class="h-12 w-12 text-cyan-400" />
                            </div>
                            <div>
                                <p class="text-xs text-slate-400 tracking-[0.2em] uppercase font-bold mb-1">TOTAL VALUATION</p>
                                <h3 class="text-2xl font-black text-white glow-text tracking-tight">
                                    {{ formatCurrency(stats.total_valuation) }}
                                </h3>
                            </div>
                            <div class="mt-4 h-1 bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-cyan-500 w-[80%] shadow-[0_0_10px_#06b6d4]"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Active Items -->
                    <div class="hud-card group delay-200">
                         <div class="hud-content p-6 h-full relative z-10 bg-[#0a0a16]/60 backdrop-blur-xl border border-white/5 rounded-xl overflow-hidden flex flex-col justify-between">
                            <div class="absolute top-0 right-0 p-4 opacity-20 group-hover:opacity-40 transition-opacity">
                                <CubeIcon class="h-12 w-12 text-emerald-400" />
                            </div>
                            <div>
                                <p class="text-xs text-slate-400 tracking-[0.2em] uppercase font-bold mb-1">ACTIVE SKUs</p>
                                <h3 class="text-3xl font-black text-white glow-text tracking-tight">
                                    {{ formatNumber(stats.active_items) }}
                                </h3>
                            </div>
                            <div class="mt-4 h-1 bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-emerald-500 w-[65%] shadow-[0_0_10px_#10b981]"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Turnover -->
                    <div class="hud-card group delay-300">
                         <div class="hud-content p-6 h-full relative z-10 bg-[#0a0a16]/60 backdrop-blur-xl border border-white/5 rounded-xl overflow-hidden flex flex-col justify-between">
                            <div class="absolute top-0 right-0 p-4 opacity-20 group-hover:opacity-40 transition-opacity">
                                <ArrowPathIcon class="h-12 w-12 text-violet-400" />
                            </div>
                            <div>
                                <p class="text-xs text-slate-400 tracking-[0.2em] uppercase font-bold mb-1">TURNOVER (30D)</p>
                                <h3 class="text-3xl font-black text-white glow-text tracking-tight">
                                    {{ formatNumber(stats.turnover_rate) }} <span class="text-sm font-normal text-slate-500">items</span>
                                </h3>
                            </div>
                             <div class="mt-4 h-1 bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-violet-500 w-[50%] shadow-[0_0_10px_#8b5cf6]"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Warehouse Usage -->
                    <div class="hud-card group delay-400">
                         <div class="hud-content p-6 h-full relative z-10 bg-[#0a0a16]/60 backdrop-blur-xl border border-white/5 rounded-xl overflow-hidden flex flex-col justify-between">
                            <div class="absolute top-0 right-0 p-4 opacity-20 group-hover:opacity-40 transition-opacity">
                                <ArchiveBoxIcon class="h-12 w-12 text-amber-400" />
                            </div>
                             <div>
                                <p class="text-xs text-slate-400 tracking-[0.2em] uppercase font-bold mb-1">ITEMS STORED</p>
                                <h3 class="text-3xl font-black text-white glow-text tracking-tight">
                                    {{ formatNumber(stats.warehouse_usage) }}
                                </h3>
                            </div>
                             <div class="mt-4 h-1 bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-amber-500 w-[75%] shadow-[0_0_10px_#f59e0b]"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Stock Trends -->
                    <div class="lg:col-span-2 hud-panel min-h-[350px]">
                        <div class="panel-header flex items-center justify-between p-4 border-b border-white/5 bg-white/5">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-emerald-300 tracking-widest uppercase">
                                <ChartBarIcon class="h-4 w-4" /> Stock Movement Trends
                            </h3>
                        </div>
                        <div class="panel-body p-4 h-[300px] relative">
                            <Line :data="trendData" :options="commonOptions" />
                        </div>
                    </div>

                    <!-- Category Dist -->
                    <div class="hud-panel min-h-[350px] flex flex-col">
                        <div class="panel-header p-4 border-b border-white/5 bg-white/5">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-cyan-300 tracking-widest uppercase">
                                <Square3Stack3DIcon class="h-4 w-4" /> Top Categories
                            </h3>
                        </div>
                        <div class="panel-body p-6 flex-1 flex flex-row items-center justify-center gap-8 relative">
                            <div class="w-1/2 h-[250px] relative">
                                <Doughnut 
                                    :data="categoryData" 
                                    :options="{ 
                                        ...commonOptions, 
                                        cutout: '70%', 
                                        scales: { x: { display: false }, y: { display: false } },
                                        plugins: { legend: { display: false } } 
                                    }" 
                                />
                                 <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-center pointer-events-none w-full">
                                    <p class="text-[10px] text-slate-500 tracking-[0.2em] uppercase font-bold">CATEGORIES</p>
                                    <p class="text-3xl font-black text-white glow-text leading-tight">{{ props.stockByCategory.length }}</p>
                                </div>
                            </div>
                            <div class="w-1/2 space-y-4">
                                <div v-for="(item, index) in categoryData.labels" :key="item" class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-2.5 h-2.5 rounded-sm" :style="{ backgroundColor: categoryData.datasets[0].backgroundColor[index] }"></div>
                                        <span class="text-[10px] font-mono text-slate-400 uppercase tracking-widest">{{ item }}</span>
                                    </div>
                                    <span class="text-xs font-bold text-white">{{ formatNumber(categoryData.datasets[0].data[index]) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Low Stock Alerts Row -->
                <div v-if="lowStockItems && lowStockItems.length > 0" class="hud-panel border-rose-500/30">
                    <div class="panel-header p-4 border-b border-rose-500/20 bg-rose-500/10 flex items-center justify-between">
                        <h3 class="flex items-center gap-2 text-sm font-bold text-rose-400 tracking-widest uppercase glow-text">
                            <BoltIcon class="h-4 w-4" /> Critical Low Stock Alerts
                        </h3>
                        <span class="px-3 py-1 bg-rose-500/20 text-rose-300 text-xs font-bold rounded border border-rose-500/30 animate-pulse">
                            {{ lowStockItems.length }} ITEMS REQUIRE ATTENTION
                        </span>
                    </div>
                    <div class="panel-body p-0 overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="text-[10px] text-rose-300 font-bold uppercase tracking-wider border-b border-rose-500/10 bg-rose-500/5">
                                    <th class="p-3 pl-6">SKU</th>
                                    <th class="p-3">Product Name</th>
                                    <th class="p-3 text-center">Available Stock</th>
                                    <th class="p-3 text-center">Reorder Point</th>
                                    <th class="p-3 text-right pr-6">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-rose-500/10">
                                <tr v-for="item in lowStockItems" :key="item.id" class="hover:bg-rose-500/5 transition-colors group">
                                    <td class="p-3 pl-6 text-[10px] font-mono text-rose-200 border-l-2 border-transparent group-hover:border-rose-500">{{ item.sku }}</td>
                                    <td class="p-3 text-xs font-bold text-white">{{ item.name }}</td>
                                    <td class="p-3 text-center">
                                        <span class="font-mono text-rose-400 font-bold text-lg glow-text">{{ formatNumber(item.available_stock) }}</span>
                                        <span class="text-[10px] text-slate-500 ml-1">{{ item.unit }}</span>
                                    </td>
                                    <td class="p-3 text-center font-mono text-slate-400 text-xs">{{ formatNumber(item.reorder_point) }}</td>
                                    <td class="p-3 text-right pr-6">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30">
                                            MUST REORDER
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Bottom Row -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                     <!-- Recent Movements -->
                     <div class="hud-panel overflow-hidden">
                         <div class="panel-header p-4 border-b border-white/5 bg-white/5">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-violet-300 tracking-widest uppercase">
                                <TruckIcon class="h-4 w-4" /> Recent Activities
                            </h3>
                        </div>
                        <div class="panel-body p-0 overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="text-[10px] text-slate-500 font-bold uppercase tracking-wider border-b border-white/10 bg-white/5">
                                        <th class="p-3">SKU</th>
                                        <th class="p-3">Item</th>
                                        <th class="p-3">Type</th>
                                        <th class="p-3 text-right">Qty</th>
                                        <th class="p-3 text-right">Date</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5">
                                    <tr 
                                        v-for="move in recentMovements" 
                                        :key="move.id" 
                                        class="hover:bg-white/5 transition-colors group"
                                    >
                                        <td class="p-3 text-[10px] font-mono text-cyan-500/70 border-l-2 border-transparent group-hover:border-cyan-500 transition-colors">
                                            {{ move.sku }}
                                        </td>
                                        <td class="p-3 text-xs font-bold text-white uppercase">{{ move.product }}</td>
                                        <td class="p-3">
                                            <span 
                                                class="px-2 py-0.5 text-[10px] font-bold border rounded uppercase"
                                                :class="{
                                                    'bg-emerald-500/10 text-emerald-400 border-emerald-500/30': move.type === 'in',
                                                    'bg-violet-500/10 text-violet-400 border-violet-500/30': move.type === 'out',
                                                    'bg-amber-500/10 text-amber-400 border-amber-500/30': move.type === 'transfer'
                                                }"
                                            >
                                                {{ move.type }}
                                            </span>
                                        </td>
                                        <td class="p-3 text-xs font-mono text-right text-white">{{ formatNumber(move.qty) }}</td>
                                        <td class="p-3 text-[10px] text-slate-500 text-right">{{ move.date }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                     </div>

                     <!-- Warehouse Capacity -->
                     <div class="hud-panel">
                        <div class="panel-header p-4 border-b border-white/5 bg-white/5">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-cyan-300 tracking-widest uppercase">
                                <ArchiveBoxIcon class="h-4 w-4" /> Warehouse Utilization
                            </h3>
                        </div>
                        <div class="panel-body p-4 h-[300px]">
                            <Bar :data="warehouseData" :options="commonOptions" />
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
    background: rgba(10, 10, 22, 0.6);
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
