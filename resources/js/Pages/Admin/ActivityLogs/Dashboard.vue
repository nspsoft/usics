<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    CalendarIcon, 
    UsersIcon, 
    ChartBarIcon, 
    ArrowPathIcon,
    ShieldCheckIcon,
    BellAlertIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline';
import { Line, Pie, Bar } from 'vue-chartjs';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
    ArcElement,
    BarElement
} from 'chart.js';

ChartJS.register(
    Title, Tooltip, Legend, 
    LineElement, PointElement, 
    CategoryScale, LinearScale, 
    ArcElement, BarElement
);

const props = defineProps({
    stats: Object,
    filters: Object,
});

const startDate = ref(props.filters.start_date);
const endDate = ref(props.filters.end_date);
const chartMode = ref('global'); // 'global', 'users', 'cumulative'

const updateFilters = () => {
    router.get(route('admin.activity-logs.dashboard'), {
        start_date: startDate.value,
        end_date: endDate.value,
    }, { preserveState: true });
};

// Chart Data: Activity Trend
const trendData = computed(() => {
    const labels = props.stats.trend.map(d => d.date);
    
    if (chartMode.value === 'global') {
        return {
            labels,
            datasets: [{
                label: 'Global Activity',
                data: props.stats.trend.map(d => d.count),
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6,
            }]
        };
    } else if (chartMode.value === 'users') {
        const colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'];
        return {
            labels,
            datasets: props.stats.trendByUser.map((user, index) => ({
                label: user.user_name,
                data: labels.map(date => user.data[date] || 0),
                borderColor: colors[index % colors.length],
                backgroundColor: 'transparent',
                tension: 0.4,
                pointRadius: 3,
                borderWidth: 2
            }))
        };
    } else {
        // Cumulative mode
        let sum = 0;
        const cumulativeData = props.stats.trend.map(d => {
            sum += d.count;
            return sum;
        });
        return {
            labels,
            datasets: [{
                label: 'Cumulative Activity',
                data: cumulativeData,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6,
            }]
        };
    }
});

// Chart Data: Action Distribution
const eventData = computed(() => ({
    labels: props.stats.events.map(e => e.label),
    datasets: [{
        data: props.stats.events.map(e => e.count),
        backgroundColor: [
            'rgba(34, 197, 94, 0.7)',  // Created
            'rgba(59, 130, 246, 0.7)',  // Updated
            'rgba(239, 68, 68, 0.7)',   // Deleted
            'rgba(168, 85, 247, 0.7)',  // Access/Auth
            'rgba(100, 116, 139, 0.7)', // Others
        ],
        borderWidth: 0
    }]
}));

// Chart Data: Module Activity
const moduleData = computed(() => ({
    labels: props.stats.modules.map(m => m.name),
    datasets: [{
        label: 'Actions by Module',
        data: props.stats.modules.map(m => m.count),
        backgroundColor: 'rgba(59, 130, 246, 0.5)',
        borderRadius: 8
    }]
}));

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: chartMode.value === 'users' || chartMode.value === 'cumulative',
            position: 'top',
            align: 'end',
            labels: {
                color: '#94a3b8',
                boxWidth: 10,
                usePointStyle: true,
                pointStyle: 'circle',
                font: { size: 10 }
            }
        },
        tooltip: {
            mode: 'index',
            intersect: false,
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            grid: { color: 'rgba(255, 255, 255, 0.05)' },
            ticks: { color: '#64748b' }
        },
        x: {
            grid: { display: false },
            ticks: { color: '#64748b' }
        }
    }
}));

const pieOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
            labels: { color: '#94a3b8', boxWidth: 12, padding: 15 }
        },
        tooltip: {
            callbacks: {
                label: (context) => ` ${context.label}: ${context.raw} actions (Click to breakdown)`
            }
        }
    },
    onClick: (event, elements) => {
        if (elements.length > 0) {
            const index = elements[0].index;
            selectedAction.value = props.stats.events[index].label;
        } else {
            selectedAction.value = null;
        }
    }
}));

const selectedAction = ref(null);

const breakdownData = computed(() => {
    if (!selectedAction.value) return null;
    
    const data = props.stats.actionByUser[selectedAction.value] || [];
    // Sort by count descending
    const sorted = [...data].sort((a, b) => b.count - a.count).slice(0, 5);
    
    return {
        labels: sorted.map(item => item.user_name),
        datasets: [{
            label: `Users - ${selectedAction.value}`,
            data: sorted.map(item => item.count),
            backgroundColor: 'rgba(59, 130, 246, 0.5)',
            borderRadius: 8
        }]
    };
});
</script>

<template>
    <Head title="User Activity Dashboard" />

    <AppLayout :render-header="false">
        <div class="mb-8">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div class="flex items-center gap-4">
                    <Link :href="route('admin.activity-logs.index')" class="p-2 bg-slate-100 dark:bg-slate-800 rounded-xl text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition-all">
                        <ArrowLeftIcon class="h-5 w-5" />
                    </Link>
                    <div>
                        <h1 class="text-3xl font-bold text-slate-900 dark:text-white flex items-center gap-3">
                            User Activity Dashboard
                            <span class="px-2.5 py-0.5 rounded-full bg-blue-500/10 text-blue-500 text-xs font-medium border border-blue-500/20">Alpha v1.0</span>
                        </h1>
                        <p class="text-sm text-slate-500 mt-1">Real-time behavior analytics and system audit insights.</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 bg-white dark:bg-slate-900 p-1.5 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800">
                    <input type="date" v-model="startDate" class="bg-transparent border-0 text-sm dark:text-slate-300 focus:ring-0 w-36" @change="updateFilters" />
                    <span class="text-slate-300 dark:text-slate-700">to</span>
                    <input type="date" v-model="endDate" class="bg-transparent border-0 text-sm dark:text-slate-300 focus:ring-0 w-36" @change="updateFilters" />
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white/70 dark:bg-slate-900/40 backdrop-blur-xl border border-white dark:border-slate-800 shadow-2xl shadow-slate-200/50 dark:shadow-none p-6 rounded-3xl relative overflow-hidden group">
                    <div class="absolute -right-4 -top-4 h-24 w-24 bg-blue-500/10 rounded-full blur-2xl group-hover:bg-blue-500/20 transition-all"></div>
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-blue-500/10 rounded-2xl text-blue-500">
                            <ChartBarIcon class="h-6 w-6" />
                        </div>
                        <div>
                            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Actions</p>
                            <h3 class="text-2xl font-bold text-slate-900 dark:text-white">{{ stats.total.toLocaleString() }}</h3>
                        </div>
                    </div>
                </div>

                <div class="bg-white/70 dark:bg-slate-900/40 backdrop-blur-xl border border-white dark:border-slate-800 shadow-2xl shadow-slate-200/50 dark:shadow-none p-6 rounded-3xl relative overflow-hidden group">
                    <div class="absolute -right-4 -top-4 h-24 w-24 bg-emerald-500/10 rounded-full blur-2xl group-hover:bg-emerald-500/20 transition-all"></div>
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-emerald-500/10 rounded-2xl text-emerald-500">
                            <ArrowPathIcon class="h-6 w-6" />
                        </div>
                        <div>
                            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Actions Today</p>
                            <h3 class="text-2xl font-bold text-slate-900 dark:text-white">{{ stats.today.toLocaleString() }}</h3>
                        </div>
                    </div>
                </div>

                <div class="bg-white/70 dark:bg-slate-900/40 backdrop-blur-xl border border-white dark:border-slate-800 shadow-2xl shadow-slate-200/50 dark:shadow-none p-6 rounded-3xl relative overflow-hidden group">
                    <div class="absolute -right-4 -top-4 h-24 w-24 bg-purple-500/10 rounded-full blur-2xl group-hover:bg-purple-500/20 transition-all"></div>
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-purple-500/10 rounded-2xl text-purple-500">
                            <UsersIcon class="h-6 w-6" />
                        </div>
                        <div>
                            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Active Users</p>
                            <h3 class="text-2xl font-bold text-slate-900 dark:text-white">{{ stats.topUsers.length }}</h3>
                        </div>
                    </div>
                </div>

                <div class="bg-white/70 dark:bg-slate-900/40 backdrop-blur-xl border border-white dark:border-slate-800 shadow-2xl shadow-slate-200/50 dark:shadow-none p-6 rounded-3xl relative overflow-hidden group">
                    <div class="absolute -right-4 -top-4 h-24 w-24 bg-amber-500/10 rounded-full blur-2xl group-hover:bg-amber-500/20 transition-all"></div>
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-amber-500/10 rounded-2xl text-amber-500">
                            <ShieldCheckIcon class="h-6 w-6" />
                        </div>
                        <div>
                            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Security Health</p>
                            <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Optimal</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
                <div class="lg:col-span-2 bg-white/70 dark:bg-slate-900/40 backdrop-blur-xl border border-white dark:border-slate-800 shadow-2xl shadow-slate-200/50 dark:shadow-none p-8 rounded-[2.5rem]">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h4 class="text-lg font-bold text-slate-900 dark:text-white">Daily Activity Trend</h4>
                            <p class="text-xs text-slate-500 mt-1" v-if="chartMode === 'users'">Menampilkan data 5 user teraktif.</p>
                        </div>
                        <div class="flex items-center gap-2 p-1 bg-slate-100 dark:bg-slate-800 rounded-xl">
                            <button 
                                @click="chartMode = 'global'"
                                :class="chartMode === 'global' ? 'bg-white dark:bg-slate-900 text-blue-500 shadow-sm' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white'"
                                class="px-3 py-1.5 text-xs font-bold rounded-lg transition-all"
                            >
                                Global
                            </button>
                            <button 
                                @click="chartMode = 'users'"
                                :class="chartMode === 'users' ? 'bg-white dark:bg-slate-900 text-blue-500 shadow-sm' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white'"
                                class="px-3 py-1.5 text-xs font-bold rounded-lg transition-all"
                            >
                                Per User
                            </button>
                            <button 
                                @click="chartMode = 'cumulative'"
                                :class="chartMode === 'cumulative' ? 'bg-white dark:bg-slate-900 text-blue-500 shadow-sm' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white'"
                                class="px-3 py-1.5 text-xs font-bold rounded-lg transition-all"
                            >
                                Accumulation
                            </button>
                        </div>
                    </div>
                    <div class="h-80">
                        <Line :data="trendData" :options="chartOptions" />
                    </div>
                </div>

                <div class="bg-white/70 dark:bg-slate-900/40 backdrop-blur-xl border border-white dark:border-slate-800 shadow-2xl shadow-slate-200/50 dark:shadow-none p-8 rounded-[2.5rem]">
                    <div class="flex items-center justify-between mb-8">
                        <h4 class="text-lg font-bold text-slate-900 dark:text-white">Action Distribution</h4>
                        <button v-if="selectedAction" @click="selectedAction = null" class="text-[10px] font-bold text-blue-500 hover:underline">Reset</button>
                    </div>
                    
                    <div v-if="!selectedAction" class="h-80 transition-all">
                        <Pie :data="eventData" :options="pieOptions" />
                    </div>

                    <div v-else class="h-80 transition-all">
                        <div class="flex items-center gap-2 mb-4">
                            <span class="px-2 py-0.5 rounded bg-blue-500/10 text-blue-500 text-[10px] font-bold uppercase">{{ selectedAction }} BREAKDOWN</span>
                        </div>
                        <Bar :data="breakdownData" :options="chartOptions" />
                    </div>
                </div>
            </div>

            <!-- Bottom Row -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Modules & Users -->
                <div class="bg-white/70 dark:bg-slate-900/40 backdrop-blur-xl border border-white dark:border-slate-800 shadow-2xl shadow-slate-200/50 dark:shadow-none p-8 rounded-[2.5rem]">
                    <h4 class="text-lg font-bold text-slate-900 dark:text-white mb-6">Most Active Modules</h4>
                    <div class="space-y-4">
                        <div v-for="mod in stats.modules" :key="mod.name" class="flex items-center justify-between group">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-xs font-bold text-slate-500 group-hover:bg-blue-500/10 group-hover:text-blue-500 transition-colors">
                                    {{ mod.name.charAt(0) }}
                                </div>
                                <span class="text-sm font-medium text-slate-600 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white transition-colors">{{ mod.name }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-24 h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-blue-500 rounded-full" :style="{ width: (mod.count / stats.modules[0].count * 100) + '%' }"></div>
                                </div>
                                <span class="text-xs font-bold text-slate-400 w-8 text-right">{{ mod.count }}</span>
                            </div>
                        </div>
                    </div>

                    <h4 class="text-lg font-bold text-slate-900 dark:text-white mt-12 mb-6">Top Contributors</h4>
                    <div class="space-y-4">
                        <div v-for="user in stats.topUsers" :key="user.name" class="flex items-center justify-between p-3 rounded-2xl bg-slate-50/50 dark:bg-slate-800/30 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all cursor-default">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-sm font-bold text-white">
                                    {{ user.name.charAt(0) }}
                                </div>
                                <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ user.name }}</span>
                            </div>
                            <span class="px-3 py-1 bg-white dark:bg-slate-900 rounded-lg text-xs font-bold text-blue-500 shadow-sm border border-slate-100 dark:border-slate-800">{{ user.count }}</span>
                        </div>
                    </div>
                </div>

                <!-- Recent High-Impact Events -->
                <div class="lg:col-span-2 bg-white/70 dark:bg-slate-900/40 backdrop-blur-xl border border-white dark:border-slate-800 shadow-2xl shadow-slate-200/50 dark:shadow-none p-8 rounded-[2.5rem]">
                    <div class="flex items-center justify-between mb-8">
                        <h4 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <BellAlertIcon class="h-5 w-5 text-amber-500" />
                            Recent Critical Events
                        </h4>
                        <Link :href="route('admin.activity-logs.index')" class="text-xs font-bold text-blue-500 hover:underline">View All Logs</Link>
                    </div>
                    
                    <div class="space-y-6">
                        <div v-for="item in stats.recent" :key="item.id" class="flex gap-4 p-4 rounded-3xl bg-slate-50/30 dark:bg-slate-800/20 border border-transparent hover:border-slate-100 dark:hover:border-slate-800 transition-all group">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 rounded-2xl bg-white dark:bg-slate-900 shadow-sm flex items-center justify-center text-slate-400 group-hover:text-blue-500 transition-colors">
                                    <ChartBarIcon class="h-6 w-6" />
                                </div>
                            </div>
                            <div class="flex-grow">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-bold text-slate-400 uppercase tracking-tighter">{{ item.subject }}</span>
                                    <span class="text-[10px] font-medium text-slate-500">{{ item.time }}</span>
                                </div>
                                <p class="text-sm text-slate-700 dark:text-slate-200 mb-2 font-medium">
                                    <span class="text-blue-500 font-bold">{{ item.user }}</span> {{ item.description }}
                                </p>
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-900 text-[10px] text-slate-500">ID: #{{ item.id }}</span>
                                    <span class="px-2 py-0.5 rounded-lg bg-amber-500/10 text-amber-500 text-[10px] font-bold">Requires Review</span>
                                </div>
                            </div>
                        </div>

                        <div v-if="stats.recent.length === 0" class="py-12 text-center">
                            <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4">
                                <ShieldCheckIcon class="h-8 w-8 text-slate-400" />
                            </div>
                            <p class="text-sm text-slate-500">No high-impact events detected in this period.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
