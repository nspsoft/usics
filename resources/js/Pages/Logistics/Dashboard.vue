<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    TruckIcon,
    MapPinIcon,
    ClockIcon,
    ExclamationCircleIcon,
    ArrowTrendingUpIcon,
    IdentificationIcon,
    GlobeAsiaAustraliaIcon,
    UserIcon
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
    pipeline: Object,
    fleet: Array,
    recent: Array,
    trend: Array,
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

// --- Theme Reactive Sync ---
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
                titleColor: '#3b82f6',
                bodyColor: isLight ? '#1e293b' : '#e2e8f0',
                borderColor: '#3b82f6',
                borderWidth: 1,
                padding: 12,
                titleFont: { family: 'Space Mono', weight: 'bold' },
                bodyFont: { family: 'Space Mono' },
                displayColors: false,
            },
        },
        scales: {
            x: { 
                grid: { color: isLight ? 'rgba(0, 0, 0, 0.05)' : 'rgba(59, 130, 246, 0.1)', drawBorder: false },
                ticks: { color: isLight ? '#475569' : '#64748b', font: { family: 'Space Mono', size: 10 } }
            },
            y: { 
                grid: { color: isLight ? 'rgba(0, 0, 0, 0.05)' : 'rgba(59, 130, 246, 0.1)', drawBorder: false },
                ticks: { color: isLight ? '#475569' : '#64748b', font: { family: 'Space Mono', size: 10 } }
            },
        },
    };
});

// -- Chart Data --

// 1. Fleet Utilization (Doughnut)
const utilization = 85.4; // Mockup or calculate from stats
const fleetChartData = computed(() => {
    const isLight = isLightMode.value;
    return {
        labels: ['Active', 'Standby'],
        datasets: [{
            data: [utilization, 100 - utilization],
            backgroundColor: ['#3b82f6', isLight ? 'rgba(59, 130, 246, 0.08)' : 'rgba(59, 130, 246, 0.1)'],
            borderWidth: 0,
            circumference: 240,
            rotation: 240,
            cutout: '80%',
        }]
    };
});

// 2. Shipment Trend (Line)
const trendChartData = computed(() => ({
    labels: props.trend.map(d => d.date),
    datasets: [{
        label: 'Shipments',
        data: props.trend.map(d => d.total),
        borderColor: '#f97316',
        backgroundColor: 'rgba(249, 115, 22, 0.1)',
        fill: true,
        tension: 0.4,
        pointRadius: 4,
    }]
}));

const getStatusClass = (status) => {
    const isLight = isLightMode.value;
    switch (status.toLowerCase()) {
        case 'available': return isLight ? 'text-emerald-700 bg-emerald-50 border-emerald-200' : 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20';
        case 'on road': return isLight ? 'text-blue-700 bg-blue-50 border-blue-200' : 'text-blue-400 bg-blue-500/10 border-blue-500/20';
        case 'maintenance': return isLight ? 'text-amber-700 bg-amber-50 border-amber-200' : 'text-amber-400 bg-amber-500/10 border-amber-500/20';
        default: return isLight ? 'text-slate-700 bg-slate-100 border-slate-200' : 'text-slate-400 bg-slate-500/10 border-slate-500/20';
    }
};

const getDoStatusClass = (status) => {
    const isLight = isLightMode.value;
    switch (status.toLowerCase()) {
        case 'delivered': return isLight ? 'text-emerald-700 border-emerald-200 bg-emerald-50' : 'text-emerald-400';
        case 'shipped': return isLight ? 'text-blue-700 border-blue-200 bg-blue-50' : 'text-blue-400';
        case 'packed': return isLight ? 'text-indigo-700 border-indigo-200 bg-indigo-50' : 'text-indigo-400';
        case 'picking': return isLight ? 'text-amber-700 border-amber-200 bg-amber-50' : 'text-amber-400';
        default: return 'text-slate-500';
    }
};

</script>

<template>
    <Head title="Logistics Intelligence" />

    <AppLayout title="Logistics Intelligence" :render-header="false">
        <div class="min-h-screen bg-slate-50 dark:bg-[#050510] relative overflow-hidden font-mono text-slate-800 dark:text-slate-50 selection:bg-blue-500/30 transition-colors duration-300">
            
            <!-- Dynamic Background -->
            <div class="fixed inset-0 z-0 pointer-events-none">
                <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-slate-100 dark:from-blue-955/20 dark:to-[#050510]"></div>
                <div class="perspective-grid absolute inset-0 opacity-[0.05] dark:opacity-20"></div>
                <div class="absolute top-[-10%] right-[20%] w-[600px] h-[600px] bg-blue-500/5 dark:bg-blue-600/10 blur-[150px] rounded-full animate-float"></div>
                <div class="stars"></div>
            </div>

            <div class="relative z-10 p-6 space-y-8 max-w-7xl mx-auto">
                
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-slate-200 dark:border-white/10 pb-4 backdrop-blur-sm">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-0.5 text-[10px] bg-slate-200 dark:bg-white/5 border border-slate-350 dark:border-white/10 rounded text-slate-500 dark:text-slate-400 tracking-[0.2em] uppercase font-bold">LOGISTICS.CORE.v4</span>
                            <span class="flex items-center gap-1.5 px-2 py-0.5 text-[10px] bg-blue-500/10 border border-blue-500/20 rounded text-blue-700 dark:text-blue-400 tracking-wider animate-pulse uppercase font-bold">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 dark:bg-blue-400"></span> FLEET OPS ONLINE
                            </span>
                        </div>
                        <h1 class="text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-600 via-slate-800 to-orange-600 dark:from-blue-400 dark:via-white dark:to-orange-400 tracking-widest uppercase dark:glow-text">
                            LOGISTICS INTELLIGENCE
                        </h1>
                    </div>
                    
                    <div class="flex items-center gap-6">
                        <div class="text-right hidden md:block">
                            <p class="text-[10px] text-blue-600 dark:text-blue-500/70 tracking-[0.2em] mb-1">NETWORK TIME</p>
                            <p class="text-2xl font-bold font-mono text-slate-900 dark:text-white dark:glow-text">{{ time }}</p>
                        </div>
                    </div>
                </div>

                <!-- KPI Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Active Shipments -->
                    <div class="hud-card group">
                        <div class="hud-content p-6 h-full relative z-10 bg-white/70 dark:bg-[#0a0a16]/60 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-xl overflow-hidden flex flex-col justify-between shadow-sm dark:shadow-none">
                            <div class="absolute top-0 right-0 p-4 opacity-20 group-hover:opacity-40 transition-opacity">
                                <GlobeAsiaAustraliaIcon class="h-12 w-12 text-blue-500 dark:text-blue-400" />
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 tracking-[0.2em] uppercase font-bold mb-1">ACTIVE SHIPMENTS</p>
                                <h3 class="text-3xl font-black text-slate-900 dark:text-white dark:glow-text tracking-tight">
                                    {{ stats.active_shipments }}
                                </h3>
                            </div>
                            <div class="mt-4 flex items-center justify-between text-[10px] text-slate-500 font-bold">
                                <span class="text-blue-600 dark:text-blue-500/70">ON TRANSIT: 12</span>
                                <span class="text-slate-400 dark:text-slate-500">PENDING: 8</span>
                            </div>
                        </div>
                    </div>

                    <!-- Available Vehicles -->
                    <div class="hud-card group">
                        <div class="hud-content p-6 h-full relative z-10 bg-white/70 dark:bg-[#0a0a16]/60 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-xl overflow-hidden flex flex-col justify-between shadow-sm dark:shadow-none">
                            <div class="absolute top-0 right-0 p-4 opacity-20 group-hover:opacity-40 transition-opacity">
                                <TruckIcon class="h-12 w-12 text-emerald-650 dark:text-emerald-400" />
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 tracking-[0.2em] uppercase font-bold mb-1">FLEET STATUS</p>
                                <h3 class="text-3xl font-black text-slate-900 dark:text-white dark:glow-text tracking-tight">
                                    {{ stats.available_vehicles }} <span class="text-xs text-slate-400 dark:text-slate-500 font-normal">READY</span>
                                </h3>
                            </div>
                             <div class="mt-4 h-1 bg-slate-200 dark:bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-emerald-500 shadow-[0_0_10px_#10b981] transition-all duration-1000" :style="{ width: stats.available_vehicles > 0 ? Math.max(Math.min((stats.available_vehicles / 50) * 100, 100), 5) + '%' : '0%' }"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Delayed Deliveries -->
                    <div class="hud-card group">
                        <div class="hud-content p-6 h-full relative z-10 bg-white/70 dark:bg-[#0a0a16]/60 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-xl overflow-hidden flex flex-col justify-between shadow-sm dark:shadow-none border-l-4 border-l-rose-500">
                            <div class="absolute top-0 right-0 p-4 opacity-20 group-hover:opacity-40 transition-opacity">
                                <ExclamationCircleIcon class="h-12 w-12 text-rose-500 dark:text-rose-400" />
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 tracking-[0.2em] uppercase font-bold mb-1">DELAYED ITEMS</p>
                                <h3 class="text-3xl font-black text-rose-650 dark:text-rose-500 dark:glow-text tracking-tight">
                                    {{ stats.delayed_deliveries }}
                                </h3>
                            </div>
                             <div class="mt-4 flex items-center justify-between text-[10px] text-slate-500 font-bold">
                                <span class="text-rose-600 dark:text-rose-500/70">ACTION REQUIRED</span>
                                <span class="text-slate-400 dark:text-slate-500">AVG DELAY: 2.1H</span>
                            </div>
                        </div>
                    </div>

                    <!-- Avg Delivery Time -->
                    <div class="hud-card group">
                        <div class="hud-content p-6 h-full relative z-10 bg-white/70 dark:bg-[#0a0a16]/60 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-xl overflow-hidden flex flex-col justify-between shadow-sm dark:shadow-none">
                            <div class="absolute top-0 right-0 p-4 opacity-20 group-hover:opacity-40 transition-opacity">
                                <ClockIcon class="h-12 w-12 text-orange-500 dark:text-orange-400" />
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 tracking-[0.2em] uppercase font-bold mb-1">AVG LEAD TIME</p>
                                <h3 class="text-3xl font-black text-slate-900 dark:text-white dark:glow-text tracking-tight">
                                    {{ stats.avg_delivery_time }} <span class="text-xs text-slate-400 dark:text-slate-500 font-normal">DAYS</span>
                                </h3>
                            </div>
                             <div class="mt-4 flex items-center justify-between text-[10px] text-slate-500 font-bold">
                                <span class="text-orange-600 dark:text-orange-500/70">EFFICIENCY: 94%</span>
                                <span class="text-slate-400 dark:text-slate-500 font-medium">TARGET: 1.5D</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Layout -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- Fleet Utilization Gauge -->
                    <div class="hud-panel p-8 flex flex-col items-center justify-center relative overflow-hidden" :class="isLightMode ? 'light-mode-panel' : 'dark-mode-panel'">
                        <div class="absolute top-4 left-4">
                            <h3 class="text-sm font-bold text-blue-600 dark:text-blue-400 tracking-widest uppercase">FLEET UTILIZATION</h3>
                        </div>
                        <div class="panel-body p-6 flex-1 flex flex-col sm:flex-row items-center justify-center gap-8 relative w-full mt-4">
                            <div class="w-full sm:w-1/2 h-[200px] relative flex justify-center">
                                <Doughnut 
                                    :data="fleetChartData" 
                                    :options="{ 
                                        ...commonOptions, 
                                        cutout: '75%',
                                        plugins: { legend: { display: false } },
                                        scales: { x: { display: false }, y: { display: false } }
                                    }" 
                                />
                                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-center pointer-events-none w-full">
                                    <p class="text-3xl font-black text-slate-900 dark:text-white dark:glow-text mb-1 leading-tight">{{ utilization }}%</p>
                                    <p class="text-[10px] text-blue-600 dark:text-blue-500/50 tracking-[0.2em] font-bold">UTILITY</p>
                                </div>
                            </div>
                            <div class="w-full sm:w-1/2 space-y-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-2.5 h-2.5 rounded-sm bg-blue-500 shadow-[0_0_8px_#3b82f6]"></div>
                                        <span class="text-[10px] font-mono text-slate-500 dark:text-slate-400 uppercase tracking-widest">ON ROAD</span>
                                    </div>
                                    <span class="text-xs font-bold text-slate-800 dark:text-white uppercase font-mono">18 UNITS</span>
                                </div>
                                <div class="flex items-center justify-between border-t border-slate-100 dark:border-white/5 pt-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-2.5 h-2.5 rounded-sm bg-blue-200 dark:bg-blue-500/10 border border-blue-300 dark:border-blue-500/20"></div>
                                        <span class="text-[10px] font-mono text-slate-500 dark:text-slate-400 uppercase tracking-widest">STANDBY</span>
                                    </div>
                                    <span class="text-xs font-bold text-slate-600 dark:text-slate-400 uppercase font-mono">4 UNITS</span>
                                </div>
                                <div class="pt-4">
                                    <p class="text-[10px] text-slate-405 dark:text-slate-500 mb-1 tracking-widest uppercase">CAPACITY LIMIT</p>
                                    <div class="h-1 bg-slate-200 dark:bg-slate-800 rounded-full overflow-hidden">
                                        <div class="h-full bg-blue-500 shadow-[0_0_10px_#3b82f6]" :style="{ width: utilization + '%' }"></div>
                                    </div>
                                    <p class="text-[9px] text-blue-600 dark:text-blue-500/50 mt-1 uppercase font-bold">15.0T MAX LOAD</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Shipment Trend -->
                    <div class="lg:col-span-2 hud-panel flex flex-col" :class="isLightMode ? 'light-mode-panel' : 'dark-mode-panel'">
                        <div class="panel-header p-4 border-b border-slate-100 dark:border-white/5 bg-slate-50 dark:bg-white/5 flex items-center justify-between">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-orange-605 dark:text-orange-300 tracking-widest uppercase">
                                <ArrowTrendingUpIcon class="h-4 w-4" /> SHIPMENT VOLUME (7D)
                            </h3>
                        </div>
                        <div class="panel-body p-6 flex-1">
                            <div class="h-[250px]">
                                <Line :data="trendChartData" :options="commonOptions" />
                            </div>
                        </div>
                    </div>

                    <!-- Delivery Pipeline -->
                    <div class="lg:col-span-3 hud-panel overflow-hidden" :class="isLightMode ? 'light-mode-panel' : 'dark-mode-panel'">
                        <div class="panel-header p-4 border-b border-slate-100 dark:border-white/5 bg-slate-50 dark:bg-white/5">
                            <h3 class="text-sm font-bold text-blue-600 dark:text-blue-300 tracking-widest uppercase">DELIVERY PIPELINE</h3>
                        </div>
                        <div class="p-6">
                            <div class="flex flex-col md:flex-row justify-between items-center gap-4 relative">
                                <!-- Pipeline Steps -->
                                <div v-for="(count, status) in pipeline" :key="status" class="flex-1 w-full md:w-auto relative z-10">
                                    <div class="bg-white/70 border border-slate-200 hover:border-blue-550 dark:bg-white/5 dark:border-white/5 p-4 rounded-xl hover:bg-slate-100/50 dark:hover:bg-white/10 transition-colors group cursor-pointer shadow-sm dark:shadow-none">
                                        <p class="text-[10px] text-slate-500 dark:text-slate-500 uppercase tracking-widest mb-1 font-bold">{{ status }}</p>
                                        <div class="flex items-end justify-between">
                                            <span class="text-2xl font-black text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors font-mono">{{ count }}</span>
                                            <span class="text-[10px] text-blue-600 dark:text-blue-500/50 font-bold">UNITS</span>
                                        </div>
                                    </div>
                                </div>
                                <!-- Connectors (Desktop only) -->
                                <div class="hidden md:block absolute top-1/2 left-0 w-full h-px bg-gradient-to-r from-blue-500/0 via-blue-550/20 to-blue-500/0 -z-0"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Fleet Status Feed -->
                    <div class="lg:col-span-2 hud-panel" :class="isLightMode ? 'light-mode-panel' : 'dark-mode-panel'">
                        <div class="panel-header p-4 border-b border-slate-100 dark:border-white/5 bg-slate-50 dark:bg-white/5 flex items-center justify-between">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-blue-650 dark:text-blue-300 tracking-widest uppercase">
                                <IdentificationIcon class="h-4 w-4" /> LIVE FLEET GPS STATUS
                            </h3>
                            <Link href="/logistics/fleet" class="text-[10px] text-blue-600 dark:text-blue-400 hover:underline font-bold">VIEW FULL FLEET</Link>
                        </div>
                        <div class="panel-body p-0 overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="text-[10px] text-slate-500 font-bold uppercase tracking-wider border-b border-slate-100 dark:border-white/10 bg-slate-100/50 dark:bg-white/5">
                                        <th class="p-4">LICENSE PLATE</th>
                                        <th class="p-4">VEHICLE TYPE</th>
                                        <th class="p-4">DRIVER</th>
                                        <th class="p-4 text-center">STATUS</th>
                                        <th class="p-4 text-right">LAST PING</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-white/5 text-slate-700 dark:text-slate-300">
                                    <tr v-for="v in fleet" :key="v.id" class="hover:bg-slate-50 dark:hover:bg-white/5 transition-colors group">
                                        <td class="p-4 text-sm font-bold text-slate-900 dark:text-white tracking-widest font-mono">{{ v.plate }}</td>
                                        <td class="p-4 text-xs text-slate-500 dark:text-slate-400 uppercase font-mono">{{ v.type }}</td>
                                        <td class="p-4 text-xs font-mono text-blue-700 dark:text-blue-400 flex items-center gap-2 font-bold">
                                            <UserIcon class="h-3 w-3" /> {{ v.driver }}
                                        </td>
                                        <td class="p-4 text-center">
                                            <span :class="getStatusClass(v.status)" class="px-2 py-0.5 rounded text-[10px] border tracking-wider font-bold">
                                                {{ v.status }}
                                            </span>
                                        </td>
                                        <td class="p-4 text-right text-[10px] text-slate-450 dark:text-slate-500 group-hover:text-blue-605 dark:group-hover:text-blue-400 transition-colors uppercase font-mono">
                                            {{ v.last_update }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Recent Shipments -->
                    <div class="hud-panel" :class="isLightMode ? 'light-mode-panel' : 'dark-mode-panel'">
                        <div class="panel-header p-4 border-b border-slate-100 dark:border-white/5 bg-slate-50 dark:bg-white/5 flex items-center justify-between">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-orange-605 dark:text-orange-300 tracking-widest uppercase">
                                <MapPinIcon class="h-4 w-4" /> RECENT DELIVERY LOGS
                            </h3>
                        </div>
                        <div class="panel-body p-4 space-y-4">
                            <div v-for="do_item in recent" :key="do_item.id" class="p-3 bg-white/70 border border-slate-200 hover:border-orange-500/30 dark:bg-white/5 dark:border-white/5 rounded-lg hover:border-orange-500/30 transition-colors shadow-sm dark:shadow-none">
                                <div class="flex justify-between items-start mb-2">
                                    <span class="text-xs font-bold text-slate-800 dark:text-white font-mono">{{ do_item.do_number }}</span>
                                    <span :class="getDoStatusClass(do_item.status)" class="text-[8px] font-black uppercase tracking-widest border border-current px-1.5 rounded">
                                        {{ do_item.status }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-end">
                                    <div class="space-y-1">
                                        <p class="text-[10px] text-slate-500 dark:text-slate-400 uppercase truncate max-w-[120px] font-bold">{{ do_item.customer }}</p>
                                        <p class="text-[9px] text-orange-600 dark:text-orange-500/70 font-mono">{{ do_item.vehicle }}</p>
                                    </div>
                                    <span class="text-[10px] text-slate-450 dark:text-slate-500 font-mono">{{ do_item.date }}</span>
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
@import url('https://fonts.googleapis.com/css2?family=Space+Mono:ital,wght@0,400;0,700;1,400&display=swap');

.font-mono {
    font-family: 'Space Mono', monospace;
}

/* Background Effects */
.perspective-grid {
    background-image: 
        linear-gradient(to right, rgba(59, 130, 246, 0.1) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(59, 130, 246, 0.1) 1px, transparent 1px);
    background-size: 50px 50px;
    transform: perspective(600px) rotateX(60deg) translateY(-100px) scale(2);
    animation: grid-move 30s linear infinite;
    transform-origin: top;
}

@keyframes grid-move {
    0% { background-position: 0 0; }
    100% { background-position: 0 50px; }
}

@keyframes float {
    0%, 100% { transform: translate(0, 0); }
    50% { transform: translate(20px, -20px); }
}

/* Card Styling */
.hud-card {
    transition: all 0.3s ease;
}
.hud-card:hover {
    transform: translateY(-5px);
    filter: drop-shadow(0 0 15px rgba(59, 130, 246, 0.2));
}

.hud-panel {
    backdrop-filter: blur(20px);
    border-radius: 12px;
    transition: background-color 0.3s, border-color 0.3s;
}

.dark-mode-panel {
    background: rgba(10, 10, 22, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 0 30px rgba(0, 0, 0, 0.7);
}

.light-mode-panel {
    background: rgba(255, 255, 255, 0.75);
    border: 1px solid rgba(0, 0, 0, 0.08);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
}

.dark .glow-text {
    text-shadow: 0 0 10px currentColor;
}
</style>
