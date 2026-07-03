<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    CalendarDaysIcon,
    TruckIcon,
    ExclamationTriangleIcon,
    ClockIcon,
    CheckCircleIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    XMarkIcon,
    ArrowTopRightOnSquareIcon,
    SunIcon,
    MoonIcon
} from '@heroicons/vue/24/outline';
import { formatCurrency } from '@/helpers';

const props = defineProps({
    calendarData: Object,
    scheduledPOs: Array,
    overduePOs: Array,
    stats: Object,
    currentMonth: String,
    monthLabel: String,
    year: Number,
    month: Number,
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

// --- Calendar Logic ---
const today = new Date();
const todayDay = today.getDate();
const todayMonth = today.getMonth() + 1;
const todayYear = today.getFullYear();
const isCurrentMonth = computed(() => props.year === todayYear && props.month === todayMonth);

const firstDayOfWeek = computed(() => {
    const d = new Date(props.year, props.month - 1, 1);
    return d.getDay(); // 0=Sun, 1=Mon...
});

const daysInMonth = computed(() => {
    return new Date(props.year, props.month, 0).getDate();
});

const calendarDays = computed(() => {
    const days = [];
    // Empty cells before 1st
    for (let i = 0; i < firstDayOfWeek.value; i++) {
        days.push({ day: null, empty: true });
    }
    // Actual days
    for (let d = 1; d <= daysInMonth.value; d++) {
        const data = props.calendarData?.[d] || null;
        const isToday = isCurrentMonth.value && d === todayDay;
        const isPast = new Date(props.year, props.month - 1, d) < new Date(todayYear, todayMonth - 1, todayDay);
        days.push({
            day: d,
            empty: false,
            isToday,
            isPast: isPast && !isToday,
            count: data?.count || 0,
            orders: data?.orders || [],
        });
    }
    return days;
});

const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

// --- Month Navigation ---
const navigateMonth = (direction) => {
    let newMonth = props.month + direction;
    let newYear = props.year;
    if (newMonth < 1) { newMonth = 12; newYear--; }
    if (newMonth > 12) { newMonth = 1; newYear++; }
    router.get(route('purchasing.delivery-schedule'), { year: newYear, month: newMonth }, { preserveState: true });
};

// --- Day Detail ---
const selectedDay = ref(null);
const selectedDayOrders = ref([]);

const selectDay = (dayObj) => {
    if (dayObj.empty || dayObj.count === 0) {
        selectedDay.value = null;
        selectedDayOrders.value = [];
        return;
    }
    selectedDay.value = dayObj.day;
    selectedDayOrders.value = dayObj.orders;
};

const closeDetail = () => {
    selectedDay.value = null;
    selectedDayOrders.value = [];
};

// --- Status helpers ---
const statusColor = (status) => {
    const colors = {
        draft: 'bg-slate-100 dark:bg-slate-500/20 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-500/30',
        submitted: 'bg-blue-50 dark:bg-blue-500/20 text-blue-650 dark:text-blue-400 border border-blue-100 dark:border-blue-500/30',
        approved: 'bg-emerald-50 dark:bg-emerald-500/20 text-emerald-650 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-500/30',
        ordered: 'bg-amber-50 dark:bg-amber-500/20 text-amber-650 dark:text-amber-400 border border-amber-100 dark:border-amber-500/30',
        partial: 'bg-orange-50 dark:bg-orange-500/20 text-orange-650 dark:text-orange-400 border border-orange-100 dark:border-orange-500/30',
        received: 'bg-green-50 dark:bg-green-500/20 text-green-700 dark:text-green-400 border border-green-100 dark:border-green-550/30',
        completed: 'bg-green-50 dark:bg-green-500/20 text-green-700 dark:text-green-400 border border-green-100 dark:border-green-550/30',
        cancelled: 'bg-rose-50 dark:bg-rose-500/20 text-rose-650 dark:text-rose-400 border border-rose-100 dark:border-rose-500/30',
    };
    return colors[status] || 'bg-slate-100 dark:bg-slate-500/20 text-slate-650 dark:text-slate-400 border border-slate-200 dark:border-slate-500/30';
};
</script>

<template>
    <AppLayout :render-header="false">
        <Head title="Delivery Schedule" />

        <div class="min-h-screen bg-slate-50 dark:bg-[#050510] text-slate-800 dark:text-white font-mono relative overflow-hidden transition-colors duration-300">
            <!-- Dynamic Background -->
            <div class="fixed inset-0 pointer-events-none z-0">
                <div class="absolute inset-0 bg-gradient-to-b from-amber-500/5 to-slate-100 dark:from-cyan-950/20 dark:to-[#050510]"></div>
                <div class="absolute inset-0 perspective-grid opacity-[0.15] dark:opacity-30"></div>
                <div class="absolute top-0 right-0 w-[600px] h-[600px] bg-amber-500/5 dark:bg-amber-500/10 rounded-full blur-[200px] animate-float"></div>
                <div class="absolute bottom-0 left-0 w-[500px] h-[500px] bg-cyan-500/5 dark:bg-cyan-500/10 rounded-full blur-[200px] animate-float-delayed"></div>
            </div>

            <div class="relative z-10 p-4 lg:p-6 max-w-[1600px] mx-auto space-y-6">
                <!-- Header -->
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-white/10 pb-4">
                    <div class="flex items-center gap-4">
                        <h1 class="text-2xl font-black tracking-wider text-amber-650 dark:text-amber-400 uppercase flex items-center gap-3">
                            <CalendarDaysIcon class="h-7 w-7" />
                            Delivery Schedule
                        </h1>
                        <span class="text-xs text-slate-500 tracking-[0.3em] uppercase mt-1 hidden sm:inline-block">/ PROCUREMENT LOGISTICS TIMELINE</span>
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

                        <div class="text-right">
                            <p class="text-3xl font-black text-slate-900 dark:text-white dark:glow-text leading-none">{{ time }}</p>
                        </div>
                    </div>
                </div>

                <!-- KPI Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="hud-card bg-white/70 dark:bg-white/5 backdrop-blur-xl border border-slate-200 dark:border-white/10 rounded-xl p-4 shadow-sm dark:shadow-none">
                        <div class="flex items-center gap-2 mb-2">
                            <TruckIcon class="h-4 w-4 text-amber-600 dark:text-amber-400" />
                            <span class="text-[10px] text-slate-500 tracking-[0.2em] uppercase">Expected This Month</span>
                        </div>
                        <p class="text-3xl font-black text-amber-650 dark:text-amber-400 dark:glow-text">{{ stats.total_expected }}</p>
                    </div>
                    <div class="hud-card bg-white/70 dark:bg-white/5 backdrop-blur-xl border border-slate-200 dark:border-white/10 rounded-xl p-4 shadow-sm dark:shadow-none">
                        <div class="flex items-center gap-2 mb-2">
                            <ExclamationTriangleIcon class="h-4 w-4 text-rose-605" />
                            <span class="text-[10px] text-slate-500 tracking-[0.2em] uppercase">Overdue</span>
                        </div>
                        <p class="text-3xl font-black" :class="stats.overdue_count > 0 ? 'text-rose-650 dark:text-rose-400 dark:glow-text' : 'text-emerald-650 dark:text-emerald-400'">
                            {{ stats.overdue_count }}
                        </p>
                    </div>
                    <div class="hud-card bg-white/70 dark:bg-white/5 backdrop-blur-xl border border-slate-200 dark:border-white/10 rounded-xl p-4 shadow-sm dark:shadow-none">
                        <div class="flex items-center gap-2 mb-2">
                            <CheckCircleIcon class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                            <span class="text-[10px] text-slate-500 tracking-[0.2em] uppercase">Completed</span>
                        </div>
                        <p class="text-3xl font-black text-emerald-655 dark:text-emerald-400">{{ stats.completed_count }}</p>
                    </div>
                    <div class="hud-card bg-white/70 dark:bg-white/5 backdrop-blur-xl border border-slate-200 dark:border-white/10 rounded-xl p-4 shadow-sm dark:shadow-none">
                        <div class="flex items-center gap-2 mb-2">
                            <ClockIcon class="h-4 w-4 text-cyan-600 dark:text-cyan-400" />
                            <span class="text-[10px] text-slate-500 tracking-[0.2em] uppercase">On-Time Rate</span>
                        </div>
                        <p class="text-3xl font-black" :class="stats.on_time_rate !== null ? (stats.on_time_rate >= 80 ? 'text-cyan-650 dark:text-cyan-400' : 'text-amber-650 dark:text-amber-400') : 'text-slate-450 dark:text-slate-500'">
                            {{ stats.on_time_rate !== null ? stats.on_time_rate + '%' : 'N/A' }}
                        </p>
                    </div>
                </div>

                <!-- Calendar + Detail Panel -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Calendar -->
                    <div class="lg:col-span-2 hud-panel bg-white/75 dark:bg-[#0a0a16]/80 border border-slate-200 dark:border-white/5 shadow-sm dark:shadow-[0_0_20px_rgba(0,0,0,0.5)]">
                        <div class="panel-header p-4 border-b border-slate-200 dark:border-white/5 bg-slate-50/50 dark:bg-white/5 flex items-center justify-between">
                            <button @click="navigateMonth(-1)" class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-white/10 transition-colors">
                                <ChevronLeftIcon class="h-5 w-5 text-slate-500 dark:text-slate-400" />
                            </button>
                            <h3 class="text-sm font-bold text-amber-650 dark:text-amber-300 tracking-[0.3em] uppercase">{{ monthLabel }}</h3>
                            <button @click="navigateMonth(1)" class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-white/10 transition-colors">
                                <ChevronRightIcon class="h-5 w-5 text-slate-500 dark:text-slate-400" />
                            </button>
                        </div>
                        <div class="panel-body p-4">
                            <!-- Weekday Headers -->
                            <div class="grid grid-cols-7 gap-1 mb-2">
                                <div v-for="day in weekdays" :key="day" class="text-center text-[10px] text-slate-550 dark:text-slate-500 font-bold tracking-widest uppercase py-2">
                                    {{ day }}
                                </div>
                            </div>
                            <!-- Calendar Grid -->
                            <div class="grid grid-cols-7 gap-1">
                                <div
                                    v-for="(cell, idx) in calendarDays"
                                    :key="idx"
                                    class="aspect-square rounded-lg border transition-all duration-200 flex flex-col items-center justify-center relative cursor-pointer group"
                                    :class="[
                                        cell.empty ? 'border-transparent' : 'border-slate-200 dark:border-white/5 hover:border-amber-500/30',
                                        cell.isToday ? 'bg-amber-500/10 border-amber-500/40 ring-1 ring-amber-500/20' : '',
                                        cell.isPast && !cell.count ? 'opacity-40' : '',
                                        cell.count > 0 ? 'hover:bg-slate-50 dark:hover:bg-white/5' : '',
                                        selectedDay === cell.day ? 'bg-amber-500/20 border-amber-500/50' : '',
                                    ]"
                                    @click="selectDay(cell)"
                                >
                                    <span
                                        v-if="!cell.empty"
                                        class="text-sm font-bold"
                                        :class="cell.isToday ? 'text-amber-600 dark:text-amber-400' : 'text-slate-700 dark:text-slate-400'"
                                    >{{ cell.day }}</span>
                                    <!-- PO Badge -->
                                    <div
                                        v-if="cell.count > 0"
                                        class="absolute bottom-1 left-1/2 -translate-x-1/2 flex items-center gap-0.5"
                                    >
                                        <span class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full text-[9px] font-black bg-amber-500/30 text-amber-700 dark:text-amber-300 border border-amber-500/30 group-hover:bg-amber-550 group-hover:text-white transition-colors">
                                            {{ cell.count }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Day Detail Sidebar -->
                    <div class="hud-panel bg-white/75 dark:bg-[#0a0a16]/80 border border-slate-200 dark:border-white/5 shadow-sm dark:shadow-[0_0_20px_rgba(0,0,0,0.5)]">
                        <div class="panel-header p-4 border-b border-slate-200 dark:border-white/5 bg-slate-50/50 dark:bg-white/5 flex items-center justify-between">
                            <h3 class="text-sm font-bold text-cyan-600 dark:text-cyan-300 tracking-widest uppercase">
                                {{ selectedDay ? `Day ${selectedDay} Details` : 'Select a Day' }}
                            </h3>
                            <button v-if="selectedDay" @click="closeDetail" class="p-1 rounded hover:bg-slate-100 dark:hover:bg-white/10">
                                <XMarkIcon class="h-4 w-4 text-slate-500 dark:text-slate-400" />
                            </button>
                        </div>
                        <div class="panel-body p-4 space-y-3 max-h-[500px] overflow-y-auto">
                            <div v-if="selectedDayOrders.length === 0" class="text-center py-16">
                                <CalendarDaysIcon class="h-12 w-12 text-slate-400 dark:text-slate-600 mx-auto mb-3" />
                                <p class="text-xs text-slate-500 uppercase tracking-wider">
                                    {{ selectedDay ? 'No deliveries on this day' : 'Click a day with deliveries' }}
                                </p>
                            </div>
                            <div
                                v-for="po in selectedDayOrders"
                                :key="po.id"
                                class="bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-lg p-3 hover:border-amber-500/30 transition-colors group shadow-sm dark:shadow-none"
                            >
                                <div class="flex items-center justify-between mb-2">
                                    <Link
                                        :href="route('purchasing.orders.show', po.id)"
                                        class="text-xs font-mono text-amber-600 dark:text-amber-400 hover:text-amber-500 flex items-center gap-1"
                                    >
                                        {{ po.po_number }}
                                        <ArrowTopRightOnSquareIcon class="h-3 w-3 opacity-0 group-hover:opacity-100 transition-opacity" />
                                    </Link>
                                    <span class="text-[9px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider" :class="statusColor(po.status)">
                                        {{ po.status }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-800 dark:text-white font-bold truncate">{{ po.supplier_name }}</p>
                                <div class="flex items-center justify-between mt-1.5">
                                    <span class="text-[10px] text-slate-500">{{ po.warehouse }}</span>
                                    <span class="text-[10px] text-slate-605 dark:text-slate-400 font-mono">{{ formatCurrency(po.total) }}</span>
                                </div>
                                <div class="text-[10px] text-slate-500 mt-1">{{ po.items_count }} item(s)</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Overdue Alerts -->
                <div class="hud-panel bg-white/75 dark:bg-[#0a0a16]/80 border border-slate-200 dark:border-white/5 shadow-sm dark:shadow-[0_0_20px_rgba(0,0,0,0.5)]" v-if="overduePOs.length > 0">
                    <div class="panel-header p-4 border-b border-slate-200 dark:border-white/5 bg-rose-500/5">
                        <h3 class="flex items-center gap-2 text-sm font-bold text-rose-600 dark:text-rose-400 tracking-widest uppercase">
                            <ExclamationTriangleIcon class="h-4 w-4 animate-pulse" />
                            Overdue Deliveries
                            <span class="ml-2 bg-rose-500/20 text-rose-700 dark:text-rose-300 px-2 py-0.5 rounded-full text-[10px]">{{ overduePOs.length }}</span>
                        </h3>
                    </div>
                    <div class="panel-body p-0 overflow-auto max-h-[60vh]">
                        <table class="w-full text-left border-collapse">
                            <thead class="sticky top-0 z-10">
                                <tr class="text-[10px] text-slate-500 font-bold uppercase tracking-wider border-b border-slate-200 dark:border-white/10 bg-slate-50 dark:bg-[#0a0a16]">
                                    <th class="p-3">PO Number</th>
                                    <th class="p-3">Supplier</th>
                                    <th class="p-3">Expected Date</th>
                                    <th class="p-3 text-center">Days Late</th>
                                    <th class="p-3">Status</th>
                                    <th class="p-3 text-right">Total</th>
                                    <th class="p-3 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                                <tr
                                    v-for="po in overduePOs"
                                    :key="po.id"
                                    class="hover:bg-rose-500/5 transition-colors group"
                                >
                                    <td class="p-3 text-xs font-mono text-rose-600 dark:text-rose-400 border-l-2 border-transparent group-hover:border-rose-500 transition-colors">
                                        {{ po.po_number }}
                                    </td>
                                    <td class="p-3 text-xs font-bold text-slate-800 dark:text-white">{{ po.supplier_name }}</td>
                                    <td class="p-3 text-xs text-slate-500 dark:text-slate-400">{{ po.expected_date }}</td>
                                    <td class="p-3 text-center">
                                        <span class="inline-flex items-center justify-center min-w-[28px] px-2 py-0.5 rounded-full text-[10px] font-black"
                                              :class="po.days_overdue > 7 ? 'bg-rose-500/30 text-rose-350 dark:text-rose-300' : 'bg-amber-500/30 text-amber-700 dark:text-amber-300'">
                                            +{{ po.days_overdue }}d
                                        </span>
                                    </td>
                                    <td class="p-3">
                                        <span class="text-[9px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider" :class="statusColor(po.status)">
                                            {{ po.status }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-xs text-slate-500 dark:text-slate-400 font-mono text-right">{{ formatCurrency(po.total) }}</td>
                                    <td class="p-3 text-center">
                                        <Link
                                            :href="route('purchasing.orders.show', po.id)"
                                            class="px-3 py-1 text-[10px] bg-rose-500/10 dark:bg-rose-500/20 text-rose-700 dark:text-rose-400 rounded-lg hover:bg-rose-500 hover:text-white transition-colors uppercase tracking-wider font-bold shadow-sm dark:shadow-none"
                                        >
                                            View
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Empty state for no overdue -->
                <div v-else class="hud-panel bg-white/75 dark:bg-[#0a0a16]/80 border border-slate-200 dark:border-white/5 shadow-sm dark:shadow-[0_0_20px_rgba(0,0,0,0.5)]">
                    <div class="p-8 text-center">
                        <CheckCircleIcon class="h-12 w-12 text-emerald-500/40 mx-auto mb-3" />
                        <p class="text-sm text-emerald-600 dark:text-emerald-400 font-bold uppercase tracking-widest">All Clear</p>
                        <p class="text-xs text-slate-500 mt-1">No overdue deliveries at this time</p>
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
        linear-gradient(to right, rgba(245, 158, 11, 0.1) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(245, 158, 11, 0.1) 1px, transparent 1px);
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
.hud-card:hover { transform: translateY(-5px); filter: drop-shadow(0 0 10px rgba(245, 158, 11, 0.2)); }

.hud-panel {
    backdrop-filter: blur(20px);
    border-radius: 12px;
}

.glow-text { text-shadow: 0 0 10px currentColor; }
.glow-text-red { text-shadow: 0 0 10px rgba(244, 63, 94, 0.6); }
</style>
