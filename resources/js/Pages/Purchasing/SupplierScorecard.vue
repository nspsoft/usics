<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    TrophyIcon,
    UserGroupIcon,
    ClockIcon,
    ArrowTrendingUpIcon,
    StarIcon,
    ExclamationTriangleIcon,
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

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, ArcElement, Title, Tooltip, Legend, Filler);

const props = defineProps({
    suppliers: Array,
    stats: Object,
    gradeDistribution: Object,
    spendBySupplier: Array,
    onTimeTrend: Array,
    period: Number,
});

// --- Clock ---
const time = ref('');
let timer;
onMounted(() => {
    const tick = () => { time.value = new Date().toLocaleTimeString('en-US', { hour12: false, hour: '2-digit', minute: '2-digit', second: '2-digit' }); };
    tick(); timer = setInterval(tick, 1000);
});
onUnmounted(() => clearInterval(timer));

// --- Chart shared ---
const chartOpts = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { labels: { color: '#94a3b8', font: { family: 'Space Mono', size: 10 } } },
        tooltip: {
            backgroundColor: 'rgba(5,5,16,0.9)', titleColor: '#10b981', bodyColor: '#e2e8f0',
            borderColor: '#10b981', borderWidth: 1, padding: 12,
            titleFont: { family: 'Space Mono', weight: 'bold' }, bodyFont: { family: 'Space Mono' }, displayColors: false,
        },
    },
    scales: {
        x: { grid: { color: 'rgba(16,185,129,0.1)', drawBorder: false }, ticks: { color: '#64748b', font: { family: 'Space Mono', size: 10 } } },
        y: { grid: { color: 'rgba(16,185,129,0.1)', drawBorder: false }, ticks: { color: '#64748b', font: { family: 'Space Mono', size: 10 } } },
    },
};

// On-time trend line
const onTimeTrendData = computed(() => ({
    labels: props.onTimeTrend.map(t => t.month),
    datasets: [{
        label: 'On-Time %',
        data: props.onTimeTrend.map(t => t.rate),
        borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.2)',
        borderWidth: 2, tension: 0.4, fill: true, pointRadius: 4, pointBackgroundColor: '#10b981',
    }],
}));

// Spend bar
const spendData = computed(() => ({
    labels: props.spendBySupplier.map(s => s.name),
    datasets: [{
        label: 'Total Spend',
        data: props.spendBySupplier.map(s => s.spend),
        backgroundColor: [
            'rgba(16,185,129,0.6)', 'rgba(245,158,11,0.6)', 'rgba(6,182,212,0.6)',
            'rgba(168,85,247,0.6)', 'rgba(239,68,68,0.6)', 'rgba(59,130,246,0.6)',
            'rgba(236,72,153,0.6)', 'rgba(234,179,8,0.6)', 'rgba(14,165,233,0.6)',
            'rgba(249,115,22,0.6)',
        ],
        borderWidth: 0, borderRadius: 4,
    }],
}));

// Grade Doughnut
const gradeColors = { A: '#10b981', B: '#06b6d4', C: '#f59e0b', D: '#f97316', F: '#ef4444' };
const gradeData = computed(() => ({
    labels: Object.keys(props.gradeDistribution),
    datasets: [{
        data: Object.values(props.gradeDistribution),
        backgroundColor: Object.keys(props.gradeDistribution).map(g => gradeColors[g] || '#64748b'),
        borderWidth: 0,
    }],
}));

// --- Helpers ---
const gradeStyle = (grade) => ({
    A: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/40',
    B: 'bg-cyan-500/20 text-cyan-400 border-cyan-500/40',
    C: 'bg-amber-500/20 text-amber-400 border-amber-500/40',
    D: 'bg-orange-500/20 text-orange-400 border-orange-500/40',
    F: 'bg-rose-500/20 text-rose-400 border-rose-500/40',
}[grade] || 'bg-slate-500/20 text-slate-400');

const scoreColor = (score) => {
    if (score >= 90) return 'text-emerald-400';
    if (score >= 80) return 'text-cyan-400';
    if (score >= 70) return 'text-amber-400';
    if (score >= 60) return 'text-orange-400';
    return 'text-rose-400';
};

const scoreBarWidth = (score) => Math.min(100, Math.max(0, score)) + '%';
const scoreBarColor = (score) => {
    if (score >= 90) return 'bg-emerald-500';
    if (score >= 80) return 'bg-cyan-500';
    if (score >= 70) return 'bg-amber-500';
    if (score >= 60) return 'bg-orange-500';
    return 'bg-rose-500';
};
</script>

<template>
    <AppLayout :render-header="false">
        <Head title="Supplier Scorecard" />

        <div class="min-h-screen bg-[#050510] text-white font-mono relative overflow-hidden">
            <div class="fixed inset-0 pointer-events-none z-0">
                <div class="absolute inset-0 perspective-grid opacity-30"></div>
                <div class="absolute top-0 right-0 w-[600px] h-[600px] bg-emerald-500/10 rounded-full blur-[200px] animate-float"></div>
                <div class="absolute bottom-0 left-0 w-[500px] h-[500px] bg-cyan-500/10 rounded-full blur-[200px] animate-float-delayed"></div>
            </div>

            <div class="relative z-10 p-4 lg:p-6 max-w-[1600px] mx-auto space-y-6">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-black tracking-wider text-emerald-400 uppercase flex items-center gap-3">
                            <TrophyIcon class="h-7 w-7" />
                            Supplier Scorecard
                        </h1>
                        <p class="text-xs text-slate-500 tracking-[0.3em] uppercase mt-1">VENDOR PERFORMANCE ANALYTICS</p>
                    </div>
                    <div class="text-right">
                        <p class="text-3xl font-black text-white/10 tracking-widest">{{ time }}</p>
                        <p class="text-[10px] text-slate-600 uppercase tracking-widest mt-1">Period: {{ period }} months</p>
                    </div>
                </div>

                <!-- KPI Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                    <div class="hud-card bg-white/5 backdrop-blur-xl border border-white/10 rounded-xl p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <UserGroupIcon class="h-4 w-4 text-emerald-400" />
                            <span class="text-[10px] text-slate-500 tracking-[0.15em] uppercase">Active Suppliers</span>
                        </div>
                        <p class="text-3xl font-black text-emerald-400 glow-text">{{ stats.total_suppliers }}</p>
                    </div>
                    <div class="hud-card bg-white/5 backdrop-blur-xl border border-white/10 rounded-xl p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <ClockIcon class="h-4 w-4 text-cyan-400" />
                            <span class="text-[10px] text-slate-500 tracking-[0.15em] uppercase">Avg On-Time</span>
                        </div>
                        <p class="text-3xl font-black" :class="stats.avg_on_time >= 80 ? 'text-cyan-400' : 'text-amber-400'">
                            {{ stats.avg_on_time }}%
                        </p>
                    </div>
                    <div class="hud-card bg-white/5 backdrop-blur-xl border border-white/10 rounded-xl p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <StarIcon class="h-4 w-4 text-amber-400" />
                            <span class="text-[10px] text-slate-500 tracking-[0.15em] uppercase">Avg Score</span>
                        </div>
                        <p class="text-3xl font-black" :class="scoreColor(stats.avg_score)">{{ stats.avg_score }}</p>
                    </div>
                    <div class="hud-card bg-white/5 backdrop-blur-xl border border-white/10 rounded-xl p-4 lg:col-span-2">
                        <div class="flex items-center gap-2 mb-2">
                            <TrophyIcon class="h-4 w-4 text-yellow-400" />
                            <span class="text-[10px] text-slate-500 tracking-[0.15em] uppercase">Top Supplier</span>
                        </div>
                        <p class="text-lg font-black text-yellow-400 truncate">{{ stats.top_supplier }}</p>
                        <p class="text-[10px] text-slate-500 mt-0.5">Score: {{ stats.top_score }}</p>
                    </div>
                </div>

                <!-- Scorecard Table -->
                <div class="hud-panel">
                    <div class="panel-header p-4 border-b border-white/5 bg-emerald-500/5 flex items-center justify-between">
                        <h3 class="flex items-center gap-2 text-sm font-bold text-emerald-400 tracking-widest uppercase">
                            <StarIcon class="h-4 w-4" />
                            Supplier Rankings
                            <span class="ml-2 bg-emerald-500/20 text-emerald-300 px-2 py-0.5 rounded-full text-[10px]">{{ suppliers.length }}</span>
                        </h3>
                    </div>
                    <div class="panel-body p-0 overflow-auto max-h-[30vh]">
                        <table class="w-full text-left border-collapse">
                            <thead class="sticky top-0 z-10">
                                <tr class="text-[10px] text-slate-500 font-bold uppercase tracking-wider border-b border-white/10 bg-[#0a0a16]">
                                    <th class="p-3 w-8">#</th>
                                    <th class="p-3">Supplier</th>
                                    <th class="p-3 text-center">Score</th>
                                    <th class="p-3 text-center">Grade</th>
                                    <th class="p-3 text-center">On-Time %</th>
                                    <th class="p-3 text-center">Return %</th>
                                    <th class="p-3 text-center">Avg Days</th>
                                    <th class="p-3 text-right">POs</th>
                                    <th class="p-3 text-right">Spend</th>
                                    <th class="p-3 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <tr
                                    v-for="(s, idx) in suppliers"
                                    :key="s.id"
                                    class="hover:bg-white/5 transition-colors group"
                                >
                                    <td class="p-3 text-xs font-mono border-l-2 border-transparent group-hover:border-emerald-500 transition-colors"
                                        :class="idx < 3 ? 'text-yellow-400 font-black' : 'text-slate-600'">
                                        {{ idx + 1 }}
                                    </td>
                                    <td class="p-3">
                                        <p class="text-xs font-bold text-white truncate max-w-[200px]">{{ s.name }}</p>
                                        <p class="text-[10px] text-slate-600 font-mono">{{ s.code }}</p>
                                    </td>
                                    <td class="p-3">
                                        <div class="flex flex-col items-center gap-1">
                                            <span class="text-sm font-black" :class="scoreColor(s.overall_score)">{{ s.overall_score }}</span>
                                            <div class="w-16 bg-white/5 rounded-full h-1.5 overflow-hidden">
                                                <div class="h-full rounded-full transition-all" :class="scoreBarColor(s.overall_score)" :style="{ width: scoreBarWidth(s.overall_score) }"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-3 text-center">
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg border text-sm font-black" :class="gradeStyle(s.grade)">
                                            {{ s.grade }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-center">
                                        <span class="text-xs font-mono" :class="s.on_time_rate !== null ? (s.on_time_rate >= 80 ? 'text-emerald-400' : s.on_time_rate >= 60 ? 'text-amber-400' : 'text-rose-400') : 'text-slate-600'">
                                            {{ s.on_time_rate !== null ? s.on_time_rate + '%' : '—' }}
                                        </span>
                                        <p class="text-[9px] text-slate-600">{{ s.on_time_count }}✓ / {{ s.late_count }}✗</p>
                                    </td>
                                    <td class="p-3 text-center">
                                        <span class="text-xs font-mono" :class="s.return_rate <= 2 ? 'text-emerald-400' : s.return_rate <= 5 ? 'text-amber-400' : 'text-rose-400'">
                                            {{ s.return_rate }}%
                                        </span>
                                    </td>
                                    <td class="p-3 text-center">
                                        <span class="text-xs font-mono" :class="s.avg_fulfillment !== null ? (s.avg_fulfillment <= 7 ? 'text-emerald-400' : s.avg_fulfillment <= 14 ? 'text-amber-400' : 'text-rose-400') : 'text-slate-600'">
                                            {{ s.avg_fulfillment !== null ? s.avg_fulfillment + 'd' : '—' }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-right text-xs text-slate-400 font-mono">{{ s.total_pos }}</td>
                                    <td class="p-3 text-right text-xs text-slate-400 font-mono">{{ formatCurrency(s.total_spend) }}</td>
                                    <td class="p-3 text-center">
                                        <Link
                                            :href="route('purchasing.suppliers.show', s.id)"
                                            class="px-3 py-1 text-[10px] bg-emerald-500/20 text-emerald-400 rounded-lg hover:bg-emerald-500 hover:text-black transition-colors uppercase tracking-wider font-bold"
                                        >Detail</Link>
                                    </td>
                                </tr>
                                <tr v-if="suppliers.length === 0">
                                    <td colspan="10" class="p-8 text-center text-slate-500 text-xs uppercase tracking-wider">No supplier data available</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- On-Time Trend -->
                    <div class="hud-panel">
                        <div class="panel-header p-4 border-b border-white/5 bg-white/5">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-emerald-300 tracking-widest uppercase">
                                <ArrowTrendingUpIcon class="h-4 w-4" /> On-Time Trend
                            </h3>
                        </div>
                        <div class="panel-body p-4 h-[280px]">
                            <Line :data="onTimeTrendData" :options="{ ...chartOpts, plugins: { legend: { display: false } }, scales: { ...chartOpts.scales, y: { ...chartOpts.scales.y, min: 0, max: 100 } } }" />
                        </div>
                    </div>

                    <!-- Spend Distribution -->
                    <div class="hud-panel">
                        <div class="panel-header p-4 border-b border-white/5 bg-white/5">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-amber-300 tracking-widest uppercase">
                                <StarIcon class="h-4 w-4" /> Top Spend
                            </h3>
                        </div>
                        <div class="panel-body p-4 h-[280px]">
                            <Bar :data="spendData" :options="{ ...chartOpts, indexAxis: 'y', plugins: { legend: { display: false } } }" />
                        </div>
                    </div>

                    <!-- Grade Distribution -->
                    <div class="hud-panel">
                        <div class="panel-header p-4 border-b border-white/5 bg-white/5">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-cyan-300 tracking-widest uppercase">
                                <TrophyIcon class="h-4 w-4" /> Grade Distribution
                            </h3>
                        </div>
                        <div class="panel-body p-4 h-[280px] flex items-center justify-center">
                            <div class="relative w-56 h-56" v-if="Object.keys(gradeDistribution).length > 0">
                                <Doughnut :data="gradeData" :options="{ responsive: true, maintainAspectRatio: false, cutout: '70%', plugins: { legend: { position: 'bottom', labels: { color: '#94a3b8', font: { family: 'Space Mono', size: 10 }, padding: 16 } } } }" />
                            </div>
                            <p v-else class="text-slate-500 text-xs uppercase tracking-wider">No grade data</p>
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
        linear-gradient(to right, rgba(16, 185, 129, 0.08) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(16, 185, 129, 0.08) 1px, transparent 1px);
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
.hud-card:hover { transform: translateY(-5px); filter: drop-shadow(0 0 10px rgba(16, 185, 129, 0.2)); }

.hud-panel {
    background: rgba(10, 10, 22, 0.6);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
    overflow: hidden;
}

.glow-text { text-shadow: 0 0 10px currentColor; }
</style>
