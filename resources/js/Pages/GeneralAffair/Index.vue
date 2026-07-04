<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    PresentationChartBarIcon,
    ClipboardDocumentListIcon,
    CubeIcon,
    CalendarDaysIcon,
    TruckIcon,
    MapPinIcon,
    DocumentPlusIcon,
    ClipboardDocumentCheckIcon,
    ClockIcon,
    WrenchIcon,
    UserIcon,
    BuildingOfficeIcon,
    ExclamationTriangleIcon,
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
import { Bar, Doughnut } from 'vue-chartjs';

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
    section: String,
    tickets_stats: Object,
    assets_stats: Object,
    pm_stats: Object,
    fleet_stats: Object,
    requests_stats: Object,
    recent_tickets: Array,
    recent_bookings: Array,
    asset_categories: Object,
    ticket_statuses: Object,
});

// --- Real-time Clock ---
const time = ref('');
const updateTime = () => {
    const now = new Date();
    time.value = now.toLocaleTimeString('id-ID', { 
        hour12: false, 
        hour: '2-digit', 
        minute: '2-digit', 
        second: '2-digit' 
    });
};

const isDark = ref(true);
let themeObserver;

let clockTimer;
onMounted(() => {
    updateTime();
    clockTimer = setInterval(updateTime, 1000);

    isDark.value = !document.documentElement.classList.contains('light');
    themeObserver = new MutationObserver(() => {
        isDark.value = !document.documentElement.classList.contains('light');
    });
    themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
});
onUnmounted(() => {
    clearInterval(clockTimer);
    if (themeObserver) themeObserver.disconnect();
});

// --- Menu Options (for quick access bar) ---
const navigationItems = [
    { key: 'tickets', name: 'Service Tickets', href: '/general-affair/tickets', icon: ClipboardDocumentListIcon, count: props.tickets_stats?.open || 0, color: 'text-amber-600 dark:text-amber-400' },
    { key: 'assets', name: 'Assets & Facilities', href: '/general-affair/assets', icon: CubeIcon, count: props.assets_stats?.total || 0, color: 'text-cyan-600 dark:text-cyan-400' },
    { key: 'pm', name: 'Preventive Maintenance', href: '/general-affair/pm-schedules', icon: CalendarDaysIcon, count: props.pm_stats?.overdue || 0, color: 'text-rose-600 dark:text-rose-500 animate-pulse' },
    { key: 'fleet', name: 'Armada & Fleet', href: '/general-affair/fleet', icon: TruckIcon, count: props.fleet_stats?.total || 0, color: 'text-teal-600 dark:text-teal-400' },
    { key: 'vehicles', name: 'Peminjaman Kendaraan', href: '/general-affair/vehicle-bookings', icon: ClipboardDocumentCheckIcon, count: props.fleet_stats?.pending_bookings || 0, color: 'text-emerald-600 dark:text-emerald-400' },
    { key: 'locations', name: 'Locations & Denah', href: '/general-affair/locations', icon: MapPinIcon, color: 'text-indigo-600 dark:text-indigo-400' },
    { key: 'requests', name: 'Requests (PR)', href: '/general-affair/requests', icon: DocumentPlusIcon, count: props.requests_stats?.draft || 0, color: 'text-sky-600 dark:text-sky-400' },
];

// --- Chart Configurations ---
const commonChartOptions = computed(() => {
    const textColor = isDark.value ? '#94a3b8' : '#475569';
    const gridColor = isDark.value ? 'rgba(6, 182, 212, 0.08)' : 'rgba(0, 0, 0, 0.05)';
    const tooltipBg = isDark.value ? 'rgba(5, 16, 22, 0.95)' : 'rgba(255, 255, 255, 0.95)';
    const tooltipBorder = isDark.value ? '#06b6d4' : '#cbd5e1';
    const tooltipTextColor = isDark.value ? '#e2e8f0' : '#1e293b';

    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { 
                labels: { 
                    color: textColor, 
                    font: { family: 'Space Mono', size: 10 } 
                } 
            },
            tooltip: {
                backgroundColor: tooltipBg,
                titleColor: isDark.value ? '#06b6d4' : '#0891b2',
                bodyColor: tooltipTextColor,
                borderColor: tooltipBorder,
                borderWidth: 1,
                padding: 12,
                titleFont: { family: 'Space Mono', weight: 'bold' },
                bodyFont: { family: 'Space Mono' },
                displayColors: true,
            },
        },
        scales: {
            x: { 
                grid: { color: gridColor, drawBorder: false },
                ticks: { color: isDark.value ? '#64748b' : '#475569', font: { family: 'Space Mono', size: 9 } }
            },
            y: { 
                grid: { color: gridColor, drawBorder: false },
                ticks: { color: isDark.value ? '#64748b' : '#475569', font: { family: 'Space Mono', size: 9 } }
            },
        },
    };
});

// 1. Ticket Status Breakdown (Doughnut)
const ticketStatusesData = computed(() => {
    const s = props.ticket_statuses || {};
    const open = s['Open'] || 0;
    const inProgress = s['In Progress'] || 0;
    const resolved = s['Resolved'] || 0;
    const hasData = (open + inProgress + resolved) > 0;

    return {
        labels: ['Open', 'In Progress', 'Resolved'],
        datasets: [{
            data: hasData ? [open, inProgress, resolved] : [3, 2, 5],
            backgroundColor: ['#ef4444', '#f59e0b', '#10b981'],
            hoverBackgroundColor: ['#f43f5e', '#fbbf24', '#34d399'],
            borderWidth: 0,
            cutout: '75%',
        }]
    };
});

// 2. Asset Categories Distribution (Bar)
const assetCategoriesData = computed(() => {
    const cats = props.asset_categories || {};
    const labels = Object.keys(cats);
    const data = Object.values(cats);
    const hasData = labels.length > 0;

    return {
        labels: hasData ? labels : ['Elektronik', 'Furnitur', 'Kendaraan', 'Alat Berat', 'Lainnya'],
        datasets: [{
            label: 'Total Asset',
            data: hasData ? data : [12, 18, 5, 3, 9],
            backgroundColor: 'rgba(6, 182, 212, 0.5)',
            borderColor: '#06b6d4',
            borderWidth: 1.5,
            borderRadius: 6,
            hoverBackgroundColor: 'rgba(6, 182, 212, 0.8)',
        }]
    };
});

// Helpers
const getPriorityBadge = (prio) => {
    if (!prio) return 'text-slate-400 border-slate-500/30';
    const clean = prio.toLowerCase();
    if (clean === 'critical') return 'text-rose-500 bg-rose-500/10 border-rose-500/20';
    if (clean === 'high') return 'text-amber-500 bg-amber-500/10 border-amber-500/20';
    if (clean === 'medium') return 'text-cyan-400 bg-cyan-500/10 border-cyan-500/20';
    return 'text-slate-400 bg-slate-500/10 border-slate-500/20';
};

const getTicketStatusBadge = (status) => {
    if (!status) return 'text-slate-400 border-slate-500/30';
    const clean = status.toLowerCase();
    if (clean === 'open') return 'text-rose-400 border-rose-400/20';
    if (clean === 'in_progress') return 'text-amber-400 border-amber-400/20';
    return 'text-emerald-400 border-emerald-400/20';
};

const getBookingStatusBadge = (status) => {
    if (!status) return 'text-slate-400 border-slate-500/30';
    const clean = status.toLowerCase();
    if (clean === 'completed') return 'text-emerald-400 border-emerald-400/30 bg-emerald-500/5';
    if (clean === 'active') return 'text-cyan-400 border-cyan-400/30 bg-cyan-500/5 animate-pulse';
    if (clean === 'approved') return 'text-blue-400 border-blue-400/30 bg-blue-500/5';
    if (clean === 'rejected') return 'text-rose-400 border-rose-400/30 bg-rose-500/5';
    return 'text-amber-400 border-amber-400/30 bg-amber-500/5';
};

const formatPrice = (price) => {
    if (!price) return 'Rp 0';
    return 'Rp ' + Number(price).toLocaleString('id-ID');
};
</script>

<template>
    <Head title="General Affair Command Hub" />

    <AppLayout title="General Affair Command Hub" :render-header="false">
        <div class="min-h-screen bg-slate-50 dark:bg-[#02070b] relative overflow-hidden font-mono text-slate-800 dark:text-slate-50 selection:bg-cyan-500/30 transition-colors duration-300">
             
            <!-- Techy Ambient Background -->
            <div class="fixed inset-0 z-0 pointer-events-none">
                <div class="absolute inset-0 bg-gradient-to-b from-cyan-100/10 to-white dark:from-cyan-950/20 dark:to-[#02070b]"></div>
                <div class="perspective-grid absolute inset-0 opacity-5 dark:opacity-15"></div>
                <div class="absolute top-[-10%] left-[20%] w-[600px] h-[600px] bg-cyan-300/10 dark:bg-cyan-500/10 blur-[150px] rounded-full animate-float"></div>
                <div class="absolute bottom-[-10%] right-[10%] w-[500px] h-[500px] bg-teal-500/5 blur-[120px] rounded-full animate-float" style="animation-delay: -3s;"></div>
                <div class="stars hidden dark:block"></div>
            </div>

            <div class="relative z-10 p-6 space-y-6">
                 
                <!-- Main Header -->
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-slate-200 dark:border-cyan-500/20 pb-4 backdrop-blur-sm transition-colors">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-0.5 text-[9px] bg-slate-200/50 dark:bg-cyan-950/50 border border-slate-300 dark:border-cyan-500/30 rounded text-slate-650 dark:text-cyan-400 tracking-[0.25em] transition-colors">GA.FACILITY.SYS.v3</span>
                            <span class="flex items-center gap-1.5 px-2 py-0.5 text-[9px] bg-cyan-100 dark:bg-cyan-500/10 border border-cyan-200 dark:border-cyan-500/20 rounded text-cyan-600 dark:text-cyan-400 font-bold tracking-wider animate-pulse transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full bg-cyan-500 dark:bg-cyan-400"></span> GA COMMAND HUB ONLINE
                            </span>
                        </div>
                        <h1 class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-cyan-600 via-slate-800 to-teal-650 dark:from-cyan-400 dark:via-white dark:to-teal-400 tracking-widest uppercase glow-text">
                            GENERAL AFFAIR OPERATIONS
                        </h1>
                    </div>
                    
                    <div class="flex items-center gap-6">
                        <div class="text-right hidden md:block">
                            <p class="text-[9px] text-cyan-600 dark:text-cyan-500/70 tracking-[0.2em] mb-1">LOCAL TIME</p>
                            <p class="text-xl font-bold font-mono text-slate-800 dark:text-white glow-text transition-colors">{{ time }}</p>
                        </div>
                    </div>
                </div>

                <!-- Navigation Quick-Access Ribbons -->
                <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
                    <Link
                        v-for="item in navigationItems"
                        :key="item.key"
                        :href="item.href"
                        class="quick-card bg-white dark:bg-[#030e17]/80 hover:bg-cyan-50 dark:hover:bg-cyan-950/20 border border-slate-200 dark:border-cyan-500/10 hover:border-cyan-400 dark:hover:border-cyan-500/40 px-3 py-3 rounded-xl transition-all flex flex-col justify-between group h-20 shadow-sm dark:shadow-lg dark:shadow-black/40"
                    >
                        <div class="flex justify-between items-start">
                            <component :is="item.icon" class="h-5 w-5 text-slate-450 dark:text-slate-400 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors" />
                            <span v-if="item.count !== undefined" class="text-xs font-black tracking-tight" :class="item.color">{{ item.count }}</span>
                        </div>
                        <span class="text-[9px] font-bold text-slate-500 dark:text-slate-400 group-hover:text-slate-800 dark:group-hover:text-white uppercase tracking-wider truncate mt-2">{{ item.name }}</span>
                    </Link>
                </div>

                <!-- Core Metric KPI Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Service Tickets -->
                    <div class="hud-card group">
                        <div class="hud-content p-6 h-full bg-white dark:bg-[#030e17]/85 border border-slate-200 dark:border-cyan-500/15 rounded-2xl overflow-hidden flex flex-col justify-between relative shadow-sm dark:shadow-xl dark:shadow-black/50 transition-colors">
                            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                                <ClipboardDocumentListIcon class="h-12 w-12 text-cyan-500 dark:text-cyan-400" />
                            </div>
                            <div>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 tracking-[0.2em] uppercase font-bold mb-1">ACTIVE TICKETS</p>
                                <h3 class="text-3xl font-black text-slate-800 dark:text-white glow-text tracking-tight flex items-baseline gap-2 transition-colors">
                                    {{ tickets_stats?.open + tickets_stats?.in_progress || 0 }}
                                    <span class="text-xs text-slate-400 dark:text-slate-500 font-normal">/ {{ tickets_stats?.total || 0 }} Total</span>
                                </h3>
                            </div>
                            <div class="mt-4 flex items-center justify-between text-[10px]">
                                <span class="text-rose-600 dark:text-rose-400 font-bold">OPEN: {{ tickets_stats?.open || 0 }}</span>
                                <span class="text-amber-600 dark:text-amber-400 font-bold">IN PROGRESS: {{ tickets_stats?.in_progress || 0 }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Total Assets Value -->
                    <div class="hud-card group">
                        <div class="hud-content p-6 h-full bg-white dark:bg-[#030e17]/85 border border-slate-200 dark:border-cyan-500/15 rounded-2xl overflow-hidden flex flex-col justify-between relative shadow-sm dark:shadow-xl dark:shadow-black/50 transition-colors">
                            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                                <CubeIcon class="h-12 w-12 text-teal-500 dark:text-teal-400" />
                            </div>
                            <div>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 tracking-[0.2em] uppercase font-bold mb-1">ASSET INVENTORY</p>
                                <h3 class="text-xl font-black text-slate-800 dark:text-white glow-text tracking-tight mt-1 transition-colors">
                                    {{ formatPrice(assets_stats?.total_value) }}
                                </h3>
                                <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">Total value of {{ assets_stats?.total || 0 }} registered items</p>
                            </div>
                            <div class="mt-4 flex items-center justify-between text-[10px] border-t border-slate-100 dark:border-cyan-500/10 pt-2 transition-colors">
                                <span class="text-cyan-600 dark:text-cyan-400 font-bold">Baik: {{ assets_stats?.good || 0 }}</span>
                                <span class="text-slate-450 dark:text-slate-500">Active: {{ assets_stats?.active || 0 }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- PM Schedules -->
                    <div class="hud-card group">
                        <div class="hud-content p-6 h-full bg-white dark:bg-[#030e17]/85 border border-slate-200 dark:border-cyan-500/15 rounded-2xl overflow-hidden flex flex-col justify-between relative shadow-sm dark:shadow-xl dark:shadow-black/50 transition-colors">
                            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                                <CalendarDaysIcon class="h-12 w-12 text-rose-500 dark:text-rose-400" />
                            </div>
                            <div>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 tracking-[0.2em] uppercase font-bold mb-1">PREVENTIVE MAINTENANCE</p>
                                <h3 class="text-3xl font-black tracking-tight transition-colors" :class="pm_stats?.overdue > 0 ? 'text-rose-600 dark:text-rose-500 animate-pulse' : 'text-slate-800 dark:text-white'">
                                    {{ pm_stats?.overdue || 0 }}
                                    <span class="text-xs text-slate-400 dark:text-slate-500 font-normal">OVERDUE</span>
                                </h3>
                            </div>
                            <div class="mt-4 h-1 bg-slate-200 dark:bg-slate-800 rounded-full overflow-hidden transition-colors">
                                <div 
                                    class="h-full bg-cyan-500 dark:shadow-[0_0_10px_#06b6d4]" 
                                    :style="{ width: pm_stats?.total ? ((pm_stats?.active / pm_stats?.total) * 100) + '%' : '0%' }"
                                ></div>
                            </div>
                            <div class="mt-2 flex justify-between text-[9px] text-slate-450 dark:text-slate-500 transition-colors">
                                <span>Active PM tasks: {{ pm_stats?.active || 0 }}</span>
                                <span>Total: {{ pm_stats?.total || 0 }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Fleet Availability -->
                    <div class="hud-card group">
                        <div class="hud-content p-6 h-full bg-white dark:bg-[#030e17]/85 border border-slate-200 dark:border-cyan-500/15 rounded-2xl overflow-hidden flex flex-col justify-between relative shadow-sm dark:shadow-xl dark:shadow-black/50 transition-colors">
                            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                                <TruckIcon class="h-12 w-12 text-emerald-500 dark:text-emerald-400" />
                            </div>
                            <div>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 tracking-[0.2em] uppercase font-bold mb-1">FLEET AVAILABILITY</p>
                                <h3 class="text-3xl font-black text-slate-800 dark:text-white glow-text tracking-tight transition-colors">
                                    {{ fleet_stats?.available || 0 }} <span class="text-xs text-slate-400 dark:text-slate-500 font-normal">/ {{ fleet_stats?.total || 0 }} READY</span>
                                </h3>
                            </div>
                            <div class="mt-4 flex items-center justify-between text-[10px]">
                                <span class="text-emerald-600 dark:text-emerald-400 font-bold">In Use: {{ fleet_stats?.in_use || 0 }}</span>
                                <span class="text-amber-600 dark:text-amber-400 font-bold">Maint: {{ fleet_stats?.maintenance || 0 }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts & Stats Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- Ticket Status Breakdown (Doughnut) -->
                    <div class="hud-panel p-6 flex flex-col relative">
                        <div class="absolute top-4 left-4 pb-2">
                            <h3 class="text-xs font-bold text-cyan-600 dark:text-cyan-400 tracking-widest uppercase">Service Tickets</h3>
                        </div>
                        <div class="p-4 flex-1 flex flex-col justify-center items-center gap-6 mt-4">
                            <div class="w-[180px] h-[180px] relative">
                                <Doughnut 
                                    :data="ticketStatusesData" 
                                    :options="{ 
                                        ...commonChartOptions, 
                                        cutout: '75%',
                                        plugins: { legend: { display: false } },
                                        scales: { x: { display: false }, y: { display: false } }
                                    }" 
                                />
                                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-center pointer-events-none w-full">
                                    <p class="text-2xl font-black text-slate-800 dark:text-white glow-text leading-none transition-colors">{{ tickets_stats?.total || 0 }}</p>
                                    <p class="text-[9px] text-cyan-600 dark:text-cyan-500/50 tracking-widest font-bold mt-1 transition-colors">TICKETS</p>
                                </div>
                            </div>

                            <div class="w-full grid grid-cols-3 gap-2 text-center">
                                <div class="p-2 bg-rose-50 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-500/10 rounded-xl transition-colors">
                                    <p class="text-[9px] text-rose-600 dark:text-rose-400 uppercase font-black tracking-wider">Open</p>
                                    <p class="text-base font-black text-slate-800 dark:text-white mt-0.5 transition-colors">{{ tickets_stats?.open || 0 }}</p>
                                </div>
                                <div class="p-2 bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-500/10 rounded-xl transition-colors">
                                    <p class="text-[9px] text-amber-600 dark:text-amber-400 uppercase font-black tracking-wider">Progress</p>
                                    <p class="text-base font-black text-slate-800 dark:text-white mt-0.5 transition-colors">{{ tickets_stats?.in_progress || 0 }}</p>
                                </div>
                                <div class="p-2 bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-500/10 rounded-xl transition-colors">
                                    <p class="text-[9px] text-emerald-600 dark:text-emerald-400 uppercase font-black tracking-wider">Resolved</p>
                                    <p class="text-base font-black text-slate-800 dark:text-white mt-0.5 transition-colors">{{ tickets_stats?.resolved || 0 }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Asset Categories Breakdown (Bar) -->
                    <div class="lg:col-span-2 hud-panel flex flex-col p-6">
                        <div class="pb-2">
                            <h3 class="text-xs font-bold text-cyan-600 dark:text-cyan-400 tracking-widest uppercase">Asset Distribution by Category</h3>
                        </div>
                        <div class="flex-1 mt-4 h-[240px]">
                            <Bar :data="assetCategoriesData" :options="commonChartOptions" />
                        </div>
                    </div>

                    <!-- Live Service Tickets feed -->
                    <div class="lg:col-span-2 hud-panel flex flex-col overflow-hidden">
                        <div class="p-4 border-b border-slate-200 dark:border-cyan-500/10 bg-slate-50 dark:bg-cyan-950/10 flex justify-between items-center transition-colors">
                            <h3 class="text-xs font-bold text-cyan-600 dark:text-cyan-400 tracking-widest uppercase flex items-center gap-1.5">
                                <ClipboardDocumentListIcon class="h-4 w-4" /> Live Service Tickets Feed
                            </h3>
                            <Link href="/general-affair/tickets" class="text-[9px] font-black text-cyan-650 dark:text-cyan-400 hover:underline">View All</Link>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-slate-100 dark:bg-cyan-950/20 text-slate-550 dark:text-slate-500 font-bold uppercase tracking-wider border-b border-slate-200 dark:border-cyan-500/10 transition-colors">
                                        <th class="p-3 pl-4">Ticket</th>
                                        <th class="p-3">Complaint / Issue</th>
                                        <th class="p-3">Location</th>
                                        <th class="p-3">Priority</th>
                                        <th class="p-3 text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-cyan-500/5 transition-colors">
                                    <tr v-for="t in recent_tickets" :key="t.id" class="hover:bg-slate-100/50 dark:hover:bg-cyan-500/[0.02] transition-colors group">
                                        <td class="p-3 pl-4 font-bold text-slate-800 dark:text-white font-mono tracking-widest transition-colors">{{ t.ticket_code }}</td>
                                        <td class="p-3">
                                            <div class="font-bold text-slate-700 dark:text-slate-200 truncate max-w-xs transition-colors">{{ t.title }}</div>
                                            <div class="text-[10px] text-slate-400 dark:text-slate-500 truncate max-w-xs transition-colors">{{ t.description }}</div>
                                        </td>
                                        <td class="p-3 text-slate-600 dark:text-slate-400 font-medium transition-colors">{{ t.ga_location?.name || '-' }}</td>
                                        <td class="p-3">
                                            <span :class="getPriorityBadge(t.priority)" class="px-2 py-0.5 border rounded text-[9px] font-bold uppercase">
                                                {{ t.priority }}
                                            </span>
                                        </td>
                                        <td class="p-3 text-center">
                                            <span :class="getTicketStatusBadge(t.status)" class="border border-current px-2 py-0.5 rounded text-[9px] font-black uppercase">
                                                {{ t.status }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr v-if="!recent_tickets || recent_tickets.length === 0">
                                        <td colspan="5" class="p-8 text-center text-slate-400 dark:text-slate-500 italic transition-colors">
                                            Belum ada Service Ticket terdaftar.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Recent Vehicle Bookings feed -->
                    <div class="hud-panel flex flex-col overflow-hidden">
                        <div class="p-4 border-b border-slate-200 dark:border-cyan-500/10 bg-slate-50 dark:bg-cyan-950/10 flex justify-between items-center transition-colors">
                            <h3 class="text-xs font-bold text-cyan-600 dark:text-cyan-400 tracking-widest uppercase flex items-center gap-1.5">
                                <ClipboardDocumentCheckIcon class="h-4 w-4" /> Vehicle Bookings Log
                            </h3>
                            <Link href="/general-affair/vehicle-bookings" class="text-[9px] font-black text-cyan-650 dark:text-cyan-400 hover:underline">View All</Link>
                        </div>
                        <div class="p-4 divide-y divide-slate-100 dark:divide-cyan-500/5 overflow-y-auto max-h-[300px] transition-colors">
                            <div v-for="b in recent_bookings" :key="b.id" class="py-3 flex flex-col gap-1 first:pt-0 last:pb-0 hover:bg-slate-50 dark:hover:bg-cyan-500/[0.01] transition-colors rounded-lg px-2">
                                <div class="flex justify-between items-start">
                                    <span class="text-xs font-bold text-slate-700 dark:text-white truncate max-w-[150px] transition-colors">{{ b.user?.name || 'Requester' }}</span>
                                    <span :class="getBookingStatusBadge(b.status)" class="px-1.5 py-0.5 border rounded text-[8px] font-black uppercase tracking-widest">
                                        {{ b.status }}
                                    </span>
                                </div>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate transition-colors">{{ b.purpose }}</p>
                                <div class="flex justify-between items-end text-[9px] text-slate-400 dark:text-slate-500 mt-1 transition-colors">
                                    <span>Dest: <span class="text-cyan-600 dark:text-cyan-400 font-bold transition-colors">{{ b.destination }}</span></span>
                                    <span class="font-bold text-slate-500 dark:text-slate-400 transition-colors">{{ b.vehicle ? b.vehicle.license_plate : 'No Vehicle Assign' }}</span>
                                </div>
                            </div>
                            <div v-if="!recent_bookings || recent_bookings.length === 0">
                                <div class="p-8 text-center text-slate-400 dark:text-slate-500 italic text-xs transition-colors">
                                    Belum ada peminjaman kendaraan aktif.
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

/* Background Animated Grid */
.perspective-grid {
    background-image: 
        linear-gradient(to right, rgba(6, 182, 212, 0.08) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(6, 182, 212, 0.08) 1px, transparent 1px);
    background-size: 50px 50px;
    transform: perspective(500px) rotateX(60deg) translateY(-80px) scale(2.2);
    animation: grid-move 40s linear infinite;
    transform-origin: top;
}

@keyframes grid-move {
    0% { background-position: 0 0; }
    100% { background-position: 0 50px; }
}

@keyframes float {
    0%, 100% { transform: translate(0, 0); }
    50% { transform: translate(15px, -15px); }
}

/* Cyber HUD Panels & Cards */
.quick-card {
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}
:root.dark .quick-card {
    box-shadow: inset 0 0 12px rgba(6, 182, 212, 0.03);
}
.quick-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(6, 182, 212, 0.1);
}
:root.dark .quick-card:hover {
    box-shadow: 0 0 15px rgba(6, 182, 212, 0.15), inset 0 0 8px rgba(6, 182, 212, 0.05);
}

.hud-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.hud-card:hover {
    transform: translateY(-4px);
    filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.05));
}
:root.dark .hud-card:hover {
    filter: drop-shadow(0 0 20px rgba(6, 182, 212, 0.18));
}

.hud-panel {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(25px);
    border: 1px solid rgba(226, 232, 240, 1);
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
}
:root.dark .hud-panel {
    background: rgba(3, 14, 23, 0.85);
    border: 1px solid rgba(6, 182, 212, 0.15);
    box-shadow: 0 0 40px rgba(0, 0, 0, 0.7);
}

/* Glowing Neon Text */
.glow-text {
    text-shadow: none;
}
:root.dark .glow-text {
    text-shadow: 0 0 8px currentColor;
}
</style>
