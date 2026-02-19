<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    ChartPieIcon, 
    CalculatorIcon, 
    ArrowPathIcon,
    ArrowUpCircleIcon,
    BeakerIcon,
    CpuChipIcon,
    Square3Stack3DIcon
} from '@heroicons/vue/24/outline';

defineProps({
    title: String
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

const costElements = [
    { name: 'Raw Materials', value: 'Rp 450.000.000', percentage: 65, color: '#3b82f6' },
    { name: 'Direct Labor', value: 'Rp 120.000.000', percentage: 18, color: '#8b5cf6' },
    { name: 'Factory Overhead', value: 'Rp 85.000.000', percentage: 12, color: '#22d3ee' },
    { name: 'Other Costs', value: 'Rp 35.000.000', percentage: 5, color: '#64748b' },
];

const navigationTabs = [
    { name: 'Financial Hub', href: '/finance/dashboard', active: false },
    { name: 'General Ledger', href: '/finance/ledger', active: false },
    { name: 'Profit & Loss', href: '/finance/reports', active: false },
    { name: 'AP & AR', href: '/finance/ap_&_ar_monitoring', active: false },
    { name: 'Costing Engine', href: '/costing/production', active: true },
];
</script>

<template>
    <Head :title="title" />
    
    <AppLayout :title="title" :render-header="false">
        <div class="min-h-screen bg-[#050510] relative overflow-hidden font-mono text-cyan-50 selection:bg-cyan-500/30">
            
            <!-- Dynamic Background -->
            <div class="fixed inset-0 z-0 pointer-events-none">
                <div class="absolute inset-0 bg-gradient-to-b from-indigo-950/20 to-[#050510]"></div>
                <div class="perspective-grid absolute inset-0 opacity-20"></div>
                <div class="absolute top-[-10%] right-[20%] w-[600px] h-[600px] bg-indigo-600/10 blur-[150px] rounded-full animate-float"></div>
                <div class="stars"></div>
            </div>

            <div class="relative z-10 p-6 space-y-8">
                
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-white/10 pb-4 backdrop-blur-sm">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-0.5 text-[10px] bg-white/5 border border-white/10 rounded text-slate-400 tracking-[0.2em]">CST.MNG.x64</span>
                            <span class="flex items-center gap-1.5 px-2 py-0.5 text-[10px] bg-cyan-500/10 border border-cyan-500/20 rounded text-cyan-400 tracking-wider animate-pulse">
                                <span class="w-1.5 h-1.5 rounded-full bg-cyan-400"></span> ENGINE ONLINE
                            </span>
                        </div>
                        <h1 class="text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 via-white to-indigo-400 tracking-widest uppercase glow-text">
                            ACTUAL COSTING
                        </h1>
                    </div>
                    
                    <div class="flex items-center gap-6">
                        <div class="text-right hidden md:block border-r border-white/10 pr-6">
                            <p class="text-[10px] text-cyan-500/70 tracking-[0.2em] mb-1">NETWORK TIME</p>
                            <p class="text-2xl font-bold font-mono text-white glow-text">{{ time }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <button class="px-4 py-2 bg-white/5 border border-white/10 text-slate-400 rounded-xl text-xs font-bold flex items-center gap-2 hover:bg-white/10 transition-all uppercase tracking-widest">
                                <ArrowPathIcon class="h-4 w-4" /> Recalculate
                            </button>
                        </div>
                    </div>
                </div>

                <!-- HUD Navigation Tabs -->
                <div class="flex items-center gap-2 mb-8 bg-white/5 p-1 rounded-xl border border-white/5 overflow-x-auto no-scrollbar relative z-10">
                    <Link 
                        v-for="tab in navigationTabs" 
                        :key="tab.name"
                        :href="tab.href"
                        class="px-6 py-2 rounded-lg text-xs font-bold tracking-widest transition-all duration-300 whitespace-nowrap"
                        :class="tab.active 
                            ? 'bg-cyan-500/20 text-cyan-400 border border-cyan-500/30' 
                            : 'text-slate-500 hover:text-slate-300 hover:bg-white/5'"
                    >
                        {{ tab.name }}
                    </Link>
                </div>

                <!-- Alert/Context Banner -->
                <div class="hud-panel p-6 overflow-hidden relative group">
                    <div class="relative z-10 flex flex-col md:flex-row items-center gap-6">
                        <div class="h-16 w-16 bg-cyan-500/10 border border-cyan-500/20 rounded-2xl flex items-center justify-center shrink-0">
                            <CpuChipIcon class="h-8 w-8 text-cyan-400" />
                        </div>
                        <div>
                            <h3 class="text-lg font-black tracking-tight uppercase text-white glow-text">Costing Engine Activation</h3>
                            <p class="text-slate-400 max-w-4xl text-xs leading-relaxed mt-1 italic">
                                Blueprinting phase: Implementing real-time **Actual Costing** engine. This module calculates COGS (HPP) by aggregating Raw Material (FIFO/Weighted), Direct Labor, and allocated Overhead for every Work Order.
                            </p>
                        </div>
                    </div>
                    <div class="absolute right-0 top-0 h-full w-1/3 bg-gradient-to-l from-cyan-500/5 to-transparent skew-x-12 translate-x-1/2"></div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Left: Cost Breakdown -->
                    <div class="lg:col-span-2 hud-panel p-8">
                        <div class="flex items-center justify-between mb-8">
                            <div>
                                <h3 class="text-xl font-black text-white uppercase tracking-tighter glow-text">Production Cost Breakdown</h3>
                                <p class="text-[10px] text-cyan-500 font-bold uppercase tracking-[0.2em] mt-1 pulse-opacity">Status: Calculation Running</p>
                            </div>
                            <div class="p-3 bg-white/5 border border-white/10 rounded-2xl">
                                <ChartPieIcon class="h-6 w-6 text-cyan-400" />
                            </div>
                        </div>

                        <div class="space-y-8">
                            <div v-for="cost in costElements" :key="cost.name" class="group">
                                <div class="flex justify-between items-end mb-2">
                                    <div>
                                        <span class="text-sm font-black text-white group-hover:text-cyan-400 transition-colors uppercase tracking-tight">{{ cost.name }}</span>
                                        <div class="text-[10px] text-slate-500 font-mono tracking-tighter uppercase">{{ cost.percentage }}% OF TOTAL COGS</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm font-black text-white font-mono glow-text transition-all group-hover:scale-110">{{ cost.value }}</div>
                                    </div>
                                </div>
                                <div class="w-full bg-slate-900 h-2 rounded-full overflow-hidden border border-white/5 p-[1px]">
                                    <div class="h-full rounded-full transition-all duration-1000 delay-300 shadow-[0_0_10px_currentColor]" :style="{ width: `${cost.percentage}%`, backgroundColor: cost.color, color: cost.color }"></div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-12 pt-8 border-t border-white/10 flex items-center justify-between">
                            <div class="text-slate-500 text-[10px] font-bold uppercase tracking-[0.2em]">Total Estimated COGS (HPP)</div>
                            <div class="text-4xl font-black text-cyan-400 glow-text tracking-tighter">Rp 690.400.000</div>
                        </div>
                    </div>

                    <!-- Right: Analysis Controls -->
                    <div class="space-y-6">
                        <div class="hud-panel p-6 border-l-4 border-cyan-500 relative overflow-hidden group">
                            <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:opacity-10 transition-opacity">
                                <CalculatorIcon class="h-24 w-24 text-white" />
                            </div>
                            <div class="relative z-10 flex flex-col h-full">
                                <div class="flex items-center gap-4 mb-4">
                                    <div class="p-3 bg-cyan-500/10 text-cyan-500 border border-cyan-500/20 rounded-2xl">
                                        <Square3Stack3DIcon class="h-6 w-6" />
                                    </div>
                                    <h4 class="font-black text-white uppercase tracking-tighter">Variance Analytics</h4>
                                </div>
                                <p class="text-[10px] text-slate-400 leading-relaxed mb-6 uppercase tracking-wider">
                                    Comparing **Standard Cost** vs **Actual Cost**. Automatic detection of efficiency leaks and material variance.
                                </p>
                                <button class="w-full py-4 bg-cyan-600 text-white rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] hover:scale-105 hover:bg-cyan-500 transition-all shadow-lg shadow-cyan-900/40">
                                    RUN VARIANCE AUDIT
                                </button>
                            </div>
                        </div>

                        <div class="hud-panel p-6 border-l-4 border-indigo-500 group relative overflow-hidden">
                             <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:opacity-10 transition-opacity">
                                <ArrowUpCircleIcon class="h-24 w-24 text-white" />
                            </div>
                            <div class="relative z-10">
                                <div class="flex items-center gap-4 mb-4">
                                    <div class="p-3 bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 rounded-2xl">
                                        <BeakerIcon class="h-6 w-6" />
                                    </div>
                                    <h4 class="font-black text-white uppercase tracking-tighter">Margin Pulse</h4>
                                </div>
                                <p class="text-[10px] text-slate-400 leading-relaxed mb-6 uppercase tracking-wider">
                                    Live profitability detection based on current selling price and actual manufacturing HPP.
                                </p>
                                <div class="p-4 bg-[#0a0a20] rounded-2xl border border-emerald-500/20 text-center shadow-inner shadow-emerald-500/10">
                                    <div class="text-[10px] font-bold text-emerald-500 uppercase mb-1 tracking-widest">NET MARGIN ESTIMATE</div>
                                    <div class="text-3xl font-black text-emerald-400 glow-text">+22.4%</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Technical Specs / Footer -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 pb-12">
                    <div v-for="i in 4" :key="i" class="p-4 bg-white/5 border border-white/10 rounded-xl">
                        <div class="text-[8px] text-slate-500 uppercase mb-1 tracking-[0.2em]">ENGINE.PARAM.0{{ i }}</div>
                        <div class="text-xs font-bold text-slate-300 uppercase tracking-widest">ENABLED</div>
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
        linear-gradient(to right, rgba(34, 211, 238, 0.1) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(34, 211, 238, 0.1) 1px, transparent 1px);
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
    50% { transform: translate(20px, 20px); }
}

/* HUD Styling */
.hud-panel {
    background: rgba(10, 10, 22, 0.8);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    box-shadow: 0 0 40px rgba(0, 0, 0, 0.8);
    transition: all 0.3s ease;
}
.hud-panel:hover {
    border-color: rgba(34, 211, 238, 0.3);
}

/* Text Effects */
.glow-text {
    text-shadow: 0 0 10px currentColor;
}

.pulse-opacity {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
}

/* Custom Scrollbar */
::-webkit-scrollbar {
    width: 6px;
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
