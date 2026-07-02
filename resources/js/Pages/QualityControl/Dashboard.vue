<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ShieldCheckIcon,
    BeakerIcon,
    ExclamationTriangleIcon,
    DocumentChartBarIcon,
    BoltIcon,
    CheckBadgeIcon,
    ClipboardDocumentCheckIcon,
    MagnifyingGlassIcon,
    ArrowPathIcon,
    DocumentTextIcon,
    XCircleIcon // For defects
} from '@heroicons/vue/24/outline';
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
    recent_inspections: Array,
    charts: Object,
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
            grid: { color: 'rgba(34, 211, 238, 0.1)', drawBorder: false },
            ticks: { color: '#64748b', font: { family: 'Space Mono', size: 10 } }
        },
        y: { 
            grid: { color: 'rgba(34, 211, 238, 0.1)', drawBorder: false },
            ticks: { color: '#64748b', font: { family: 'Space Mono', size: 10 } }
        },
    },
}));

const activeTab = ref('trend');

const spcChartData = computed(() => {
    if (!props.charts || !props.charts.spc) return { labels: [], datasets: [] };
    const spc = props.charts.spc;
    return {
        labels: spc.labels,
        datasets: [
            {
                label: 'Actual Value',
                data: spc.values,
                borderColor: '#22d3ee', // Cyan
                backgroundColor: 'rgba(34, 211, 238, 0.1)',
                borderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                fill: false,
                tension: 0.1,
            },
            {
                label: 'CL (Mean)',
                data: spc.cl,
                borderColor: 'rgba(16, 185, 129, 0.8)', // Emerald
                borderWidth: 1.5,
                pointRadius: 0,
                fill: false,
            },
            {
                label: 'UCL (Control Max)',
                data: spc.ucl,
                borderColor: 'rgba(244, 63, 94, 0.8)', // Rose
                borderWidth: 1.5,
                borderDash: [5, 5],
                pointRadius: 0,
                fill: false,
            },
            {
                label: 'LCL (Control Min)',
                data: spc.lcl,
                borderColor: 'rgba(244, 63, 94, 0.8)', // Rose
                borderWidth: 1.5,
                borderDash: [5, 5],
                pointRadius: 0,
                fill: false,
            },
            {
                label: 'USL (Spec Max)',
                data: spc.usl,
                borderColor: 'rgba(245, 158, 11, 0.8)', // Amber
                borderWidth: 1.5,
                borderDash: [2, 4],
                pointRadius: 0,
                fill: false,
            },
            {
                label: 'LSL (Spec Min)',
                data: spc.lsl,
                borderColor: 'rgba(245, 158, 11, 0.8)', // Amber
                borderWidth: 1.5,
                borderDash: [2, 4],
                pointRadius: 0,
                fill: false,
            }
        ]
    };
});

const spcChartOptions = computed(() => ({
    ...commonOptions.value,
    plugins: {
        ...commonOptions.value.plugins,
        legend: {
            position: 'top',
            labels: { color: '#94a3b8', font: { family: 'Space Mono', size: 9 } }
        }
    },
    scales: {
        ...commonOptions.value.scales,
        y: {
            ...commonOptions.value.scales.y,
            ticks: {
                ...commonOptions.value.scales.y.ticks,
                callback: (value) => value.toFixed(2) + ' mm'
            }
        }
    }
}));

// Map QC Chart Data to Theme
const trendChartData = computed(() => {
    if (!props.charts || !props.charts.trend) return { labels: [], datasets: [] };
    
    return {
        labels: props.charts.trend.labels,
        datasets: [
            {
                label: 'Pass',
                data: props.charts.trend.datasets[0].data, // Assumes 0 is Pass
                borderColor: '#10b981', // Emerald
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                borderWidth: 2,
                borderRadius: 4,
            },
            {
                label: 'Fail',
                data: props.charts.trend.datasets[1].data, // Assumes 1 is Fail
                borderColor: '#f43f5e', // Rose
                backgroundColor: 'rgba(244, 63, 94, 0.1)',
                borderWidth: 2,
                borderRadius: 4,
            }
        ]
    };
});

const defectChartData = computed(() => {
    if (!props.charts || !props.charts.defects) return { labels: [], datasets: [] };

    return {
        labels: props.charts.defects.labels,
        datasets: [{
            data: props.charts.defects.datasets[0].data,
            backgroundColor: [
                '#f43f5e', // Rose
                '#f59e0b', // Amber
                '#22d3ee', // Cyan
                '#8b5cf6', // Violet
                '#ec4899', // Pink
            ],
            borderWidth: 0,
            hoverOffset: 15,
            cutout: '70%',
        }]
    };
});

const getStatusColor = (status) => {
    const s = status.toLowerCase();
    if (['pass', 'approved'].includes(s)) return 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20';
    if (['conditional_pass'].includes(s)) return 'text-amber-400 bg-amber-500/10 border-amber-500/20';
    return 'text-rose-400 bg-rose-500/10 border-rose-500/20';
};
</script>

<template>
    <Head title="Quality Control" />

    <AppLayout title="Quality Control Dashboard" :render-header="false">
        <div class="min-h-screen bg-[#050510] relative overflow-hidden font-mono text-cyan-50 selection:bg-cyan-500/30">
            
            <!-- Dynamic Background -->
            <div class="fixed inset-0 z-0 pointer-events-none">
                <div class="absolute inset-0 bg-gradient-to-b from-cyan-950/20 to-[#050510]"></div>
                <div class="perspective-grid absolute inset-0 opacity-20"></div>
                <!-- QC Specific: Green/Red glow instead of just Cyan -->
                <div class="absolute top-[-10%] right-[20%] w-[600px] h-[600px] bg-emerald-600/10 blur-[150px] rounded-full animate-float"></div>
                <div class="stars"></div>
            </div>

            <div class="relative z-10 p-6 space-y-8">
                
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-white/10 pb-4 backdrop-blur-sm">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-0.5 text-[10px] bg-white/5 border border-white/10 rounded text-slate-400 tracking-[0.2em]">QC.MOD.3.0</span>
                            <span class="flex items-center gap-1.5 px-2 py-0.5 text-[10px] bg-emerald-500/10 border border-emerald-500/20 rounded text-emerald-400 tracking-wider animate-pulse">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> SYSTEMS NOMINAL
                            </span>
                        </div>
                        <h1 class="text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 via-white to-cyan-400 tracking-widest uppercase glow-text">
                            QUALITY GUARD
                        </h1>
                    </div>
                    
                    <div class="flex items-center gap-6">
                        <div class="text-right hidden md:block border-r border-white/10 pr-6">
                            <p class="text-[10px] text-cyan-500/70 tracking-[0.2em] mb-1">SYSTEM TIME</p>
                            <p class="text-2xl font-bold font-mono text-white glow-text">{{ time }}</p>
                        </div>
                        <div class="text-right hidden md:block">
                            <p class="text-[10px] text-cyan-500/70 tracking-[0.2em] mb-1">MODULE</p>
                            <p class="text-2xl font-bold font-mono text-white glow-text">QC-MAIN</p>
                        </div>
                    </div>
                </div>

                <!-- KPI Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="hud-card group">
                        <div class="hud-content p-6 h-full relative z-10 bg-[#0a0a16]/60 backdrop-blur-xl border border-white/5 rounded-xl overflow-hidden flex flex-col justify-between">
                            <div class="absolute top-0 right-0 p-4 opacity-20 group-hover:opacity-40 transition-opacity">
                                <ClipboardDocumentCheckIcon class="h-12 w-12 text-cyan-400" />
                            </div>
                            <div>
                                <p class="text-xs text-slate-400 tracking-[0.2em] uppercase font-bold mb-1">TOTAL INSPECTIONS</p>
                                <h3 class="text-3xl font-black text-white glow-text tracking-tight">{{ stats.total_inspections }}</h3>
                            </div>
                            <div class="mt-4 flex items-center justify-between text-[10px]">
                                <span class="text-cyan-500/70">ALL TIME RECORD</span>
                                <div class="h-1.5 w-24 bg-slate-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-cyan-400 w-full animate-pulse"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="hud-card group">
                        <div class="hud-content p-6 h-full relative z-10 bg-[#0a0a16]/60 backdrop-blur-xl border border-white/5 rounded-xl overflow-hidden flex flex-col justify-between">
                            <div class="absolute top-0 right-0 p-4 opacity-20 group-hover:opacity-40 transition-opacity">
                                <CheckBadgeIcon class="h-12 w-12 text-emerald-400" />
                            </div>
                            <div>
                                <p class="text-xs text-slate-400 tracking-[0.2em] uppercase font-bold mb-1">PASS RATE (30 DAYS)</p>
                                <h3 class="text-3xl font-black text-white glow-text tracking-tight">{{ stats.pass_rate }}%</h3>
                            </div>
                            <div class="mt-4 flex items-center justify-between text-[10px]">
                                <span class="text-emerald-500/70">QUALITY TARGET > 95%</span>
                                <span class="text-emerald-400">{{ stats.pass_rate >= 95 ? 'OPTIMAL' : 'BELOW TARGET' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="hud-card group">
                        <div class="hud-content p-6 h-full relative z-10 bg-[#0a0a16]/60 backdrop-blur-xl border border-white/5 rounded-xl overflow-hidden flex flex-col justify-between">
                            <div class="absolute top-0 right-0 p-4 opacity-20 group-hover:opacity-40 transition-opacity">
                                <ExclamationTriangleIcon class="h-12 w-12 text-rose-400" />
                            </div>
                            <div>
                                <p class="text-xs text-slate-400 tracking-[0.2em] uppercase font-bold mb-1">OPEN NCRs</p>
                                <h3 class="text-3xl font-black text-rose-500 glow-text tracking-tight">{{ stats.open_ncrs }}</h3>
                            </div>
                            <div class="mt-4 flex items-center justify-between text-[10px]">
                                <span class="text-rose-500/70">CRITICAL DEFECTS</span>
                                <Link href="/qc/ncr" class="text-rose-400 hover:underline font-bold animate-pulse">RESOLVE NOW</Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Analysis Row (Charts) -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Trend Chart -->
                    <div class="lg:col-span-2 hud-panel flex flex-col h-[400px]">
                        <div class="panel-header p-4 border-b border-white/5 bg-white/5 flex items-center justify-between">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-cyan-300 tracking-widest uppercase">
                                <DocumentChartBarIcon class="h-4 w-4" /> {{ activeTab === 'trend' ? 'INSPECTION TREND (14 DAYS)' : 'SPC CONTROL CHART (THICKNESS)' }}
                            </h3>
                            <div class="flex items-center bg-white/5 rounded-lg p-0.5 border border-white/10">
                                <button 
                                    @click="activeTab = 'trend'" 
                                    class="px-3 py-1 text-[10px] font-bold rounded transition-all"
                                    :class="activeTab === 'trend' ? 'bg-cyan-500/20 text-cyan-400 border border-cyan-500/30' : 'text-slate-400 hover:text-white'"
                                >
                                    TREND
                                </button>
                                <button 
                                    @click="activeTab = 'spc'" 
                                    class="px-3 py-1 text-[10px] font-bold rounded transition-all"
                                    :class="activeTab === 'spc' ? 'bg-cyan-500/20 text-cyan-400 border border-cyan-500/30' : 'text-slate-400 hover:text-white'"
                                >
                                    SPC CHART
                                </button>
                            </div>
                        </div>
                        <div class="panel-body p-6 flex-1">
                            <Bar v-if="activeTab === 'trend'" :data="trendChartData" :options="commonOptions" />
                            <Line v-else :data="spcChartData" :options="spcChartOptions" />
                        </div>
                    </div>

                    <!-- Defect Distribution -->
                    <div class="hud-panel flex flex-col h-[400px]">
                        <div class="panel-header p-4 border-b border-white/5 bg-white/5 flex items-center justify-between">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-rose-300 tracking-widest uppercase">
                                <XCircleIcon class="h-4 w-4" /> DEFECT ANATOMY
                            </h3>
                        </div>
                        <div class="panel-body p-6 flex-1 flex flex-col items-center justify-center relative">
                             <div class="w-full h-[250px] relative">
                                <Doughnut 
                                    :data="defectChartData" 
                                    :options="{ 
                                        ...commonOptions, 
                                        cutout: '75%', 
                                        scales: { x: { display: false }, y: { display: false } },
                                        plugins: { legend: { display: false } } 
                                    }" 
                                />
                                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-center pointer-events-none w-full">
                                    <p class="text-[10px] text-slate-500 tracking-[0.2em] uppercase font-bold">TOP DEFECTS</p>
                                    <p class="text-2xl font-black text-white glow-text leading-tight">ANALYSIS</p>
                                </div>
                            </div>
                            <div class="mt-4 w-full grid grid-cols-2 gap-2">
                                <div v-for="(val, index) in defectChartData.labels.slice(0, 4)" :key="index" class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-sm" :style="{ backgroundColor: defectChartData.datasets[0].backgroundColor[index] }"></div>
                                    <span class="text-[8px] font-mono text-slate-400 uppercase tracking-widest truncate">{{ val }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Operational Row -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                     <!-- Quick Actions HUD -->
                    <div class="hud-panel h-[400px] flex flex-col">
                         <div class="panel-header p-4 border-b border-white/5 bg-white/5 flex items-center justify-between">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-indigo-300 tracking-widest uppercase">
                                <BoltIcon class="h-4 w-4" /> QUICK COMMANDS
                            </h3>
                        </div>
                        <div class="panel-body p-6 grid grid-cols-2 gap-4 overflow-y-auto">
                            <Link :href="route('qc.incoming.index')" class="p-4 bg-white/5 border border-white/5 rounded-xl hover:border-indigo-500/50 hover:bg-indigo-500/10 transition-all group flex flex-col justify-center items-center text-center">
                                <MagnifyingGlassIcon class="h-8 w-8 text-indigo-400 mb-2 group-hover:scale-110 transition-transform"/>
                                <span class="text-xs font-bold text-white tracking-widest uppercase">IQC Check</span>
                                <span class="text-[8px] text-slate-500 uppercase mt-1">Raw Materials</span>
                            </Link>

                            <Link :href="route('qc.in-process.index')" class="p-4 bg-white/5 border border-white/5 rounded-xl hover:border-cyan-500/50 hover:bg-cyan-500/10 transition-all group flex flex-col justify-center items-center text-center">
                                <BeakerIcon class="h-8 w-8 text-cyan-400 mb-2 group-hover:scale-110 transition-transform"/>
                                <span class="text-xs font-bold text-white tracking-widest uppercase">IPQC Check</span>
                                <span class="text-[8px] text-slate-500 uppercase mt-1">Production</span>
                            </Link>

                             <Link :href="route('qc.ncr.index')" class="p-4 bg-white/5 border border-white/5 rounded-xl hover:border-rose-500/50 hover:bg-rose-500/10 transition-all group flex flex-col justify-center items-center text-center">
                                <ExclamationTriangleIcon class="h-8 w-8 text-rose-400 mb-2 group-hover:scale-110 transition-transform"/>
                                <span class="text-xs font-bold text-white tracking-widest uppercase">NCR Manager</span>
                                <span class="text-[8px] text-slate-500 uppercase mt-1">Defects</span>
                            </Link>
                             
                             <Link :href="route('qc.coa.create')" class="p-4 bg-white/5 border border-white/5 rounded-xl hover:border-emerald-500/50 hover:bg-emerald-500/10 transition-all group flex flex-col justify-center items-center text-center">
                                <DocumentTextIcon class="h-8 w-8 text-emerald-400 mb-2 group-hover:scale-110 transition-transform"/>
                                <span class="text-xs font-bold text-white tracking-widest uppercase">COA GEN</span>
                                <span class="text-[8px] text-slate-500 uppercase mt-1">Certificates</span>
                            </Link>
                        </div>
                    </div>

                    <!-- Recent Inspections List -->
                    <div class="hud-panel h-[400px] flex flex-col">
                        <div class="panel-header p-4 border-b border-white/5 bg-white/5 flex items-center justify-between">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-emerald-300 tracking-widest uppercase">
                                <ArrowPathIcon class="h-4 w-4" /> LIVE FEED
                            </h3>
                            <span class="text-[10px] text-emerald-500/50 animate-pulse">● UPDATING</span>
                        </div>
                         <div class="panel-body panel-body-scroll p-0 pb-8 flex-1 overflow-y-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="text-[10px] text-slate-500 font-bold uppercase tracking-wider border-b border-white/10 bg-white/5">
                                        <th class="p-3">Reference</th>
                                        <th class="p-3">Status</th>
                                        <th class="p-3">Inspector</th>
                                        <th class="p-3 text-right">Time</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5">
                                    <tr v-for="inspection in recent_inspections" :key="inspection.id" class="hover:bg-emerald-500/5 transition-colors group">
                                        <td class="p-3">
                                            <p class="text-xs text-emerald-400 font-mono font-bold">{{ inspection.reference_type?.split('\\').pop() }}</p>
                                            <p class="text-[10px] text-slate-500">#{{ inspection.reference_id }}</p>
                                        </td>
                                        <td class="p-3">
                                            <span class="px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-widest border" :class="getStatusColor(inspection.status)">
                                                {{ inspection.status }}
                                            </span>
                                        </td>
                                        <td class="p-3 text-[10px] text-slate-300 uppercase">{{ inspection.inspector }}</td>
                                        <td class="p-3 text-xs font-bold text-slate-500 text-right font-mono">{{ inspection.date }}</td>
                                    </tr>
                                    <tr v-if="recent_inspections.length === 0">
                                        <td colspan="4" class="p-6 text-center text-xs text-slate-500 uppercase tracking-widest">
                                            No Data Stream
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

/* Custom HUD Scrollbar */
.panel-body-scroll {
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.1) transparent;
}

.panel-body-scroll::-webkit-scrollbar {
    width: 4px;
}

.panel-body-scroll::-webkit-scrollbar-track {
    background: transparent;
}

.panel-body-scroll::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
}

.panel-body-scroll::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.2);
}

/* Background Effects */
.perspective-grid {
    background-image: 
        linear-gradient(to right, rgba(34, 211, 238, 0.1) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(34, 211, 238, 0.1) 1px, transparent 1px);
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

/* HUD Styling */
.hud-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.hud-card:hover {
    transform: translateY(-5px) scale(1.02);
    filter: drop-shadow(0 0 15px rgba(34, 211, 238, 0.3));
}

.hud-panel {
    background: rgba(10, 10, 22, 0.8);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    box-shadow: 0 0 40px rgba(0, 0, 0, 0.8);
    transition: border-color 0.3s ease;
}
.hud-panel:hover {
    border-color: rgba(34, 211, 238, 0.3);
}

/* Text Effects */
.glow-text {
    text-shadow: 0 0 10px currentColor;
}

/* Custom Scrollbar for modern feel */
::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
::-webkit-scrollbar-track {
    background: #050510;
}
::-webkit-scrollbar-thumb {
    background: #1e293b;
    border-radius: 3px;
}
::-webkit-scrollbar-thumb:hover {
    background: #334155;
}
</style>
