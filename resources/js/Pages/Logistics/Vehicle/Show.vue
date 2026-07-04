<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatNumber, formatDate } from '@/helpers';
import {
    TruckIcon,
    ChevronLeftIcon,
    CalendarIcon,
    MapPinIcon,
    UserIcon,
    DocumentTextIcon,
    TrophyIcon,
    ClockIcon,
    CheckBadgeIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    vehicle: Object,
    stats: Object
});

const getStatusColor = (status) => {
    const isLight = isLightMode.value;
    switch (status) {
        case 'available': return isLight ? 'bg-emerald-50 border-emerald-250 text-emerald-700' : 'bg-emerald-500/10 border-transparent text-emerald-400';
        case 'busy': return isLight ? 'bg-blue-50 border-blue-200 text-blue-700' : 'bg-blue-500/10 border-transparent text-blue-400';
        case 'maintenance': return isLight ? 'bg-amber-50 border-amber-205 text-amber-700' : 'bg-amber-500/10 border-transparent text-amber-400';
        default: return isLight ? 'bg-slate-50 border-slate-200 text-slate-700' : 'bg-slate-500/10 border-transparent text-slate-400';
    }
};

const getDoStatusColor = (status) => {
    const isLight = isLightMode.value;
    switch (status) {
        case 'delivered': return isLight ? 'bg-emerald-50 border-emerald-200 text-emerald-755' : 'bg-emerald-500/10 text-emerald-400 border-transparent';
        case 'shipped': return isLight ? 'bg-blue-50 border-blue-200 text-blue-700' : 'bg-blue-500/10 text-blue-400 border-transparent';
        case 'packed': return isLight ? 'bg-indigo-50 border-indigo-200 text-indigo-700' : 'bg-indigo-500/10 text-indigo-400 border-transparent';
        case 'picking': return isLight ? 'bg-amber-50 border-amber-200 text-amber-700' : 'bg-amber-500/10 text-amber-450 border-transparent';
        case 'cancelled': return isLight ? 'bg-red-50 border-red-200 text-red-700' : 'bg-red-500/10 text-red-400 border-transparent';
        default: return isLight ? 'bg-slate-50 border-slate-200 text-slate-700' : 'bg-slate-500/10 text-slate-405 border-transparent';
    }
};

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
</script>

<template>
    <Head title="Vehicle Details" />

    <AppLayout title="Vehicle Details" :render-header="false">
        <div class="min-h-screen bg-slate-50 dark:bg-[#050510] relative overflow-hidden font-mono text-slate-800 dark:text-cyan-50 selection:bg-indigo-500/30 transition-colors duration-300">
            
            <!-- Dynamic Background -->
            <div class="fixed inset-0 z-0 pointer-events-none">
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/5 to-slate-100 dark:from-indigo-955/20 dark:to-[#050510]"></div>
                 <div class="perspective-grid absolute inset-0 opacity-[0.05] dark:opacity-10"></div>
            </div>

            <div class="relative z-10 p-6 space-y-6 max-w-7xl mx-auto">
                
                <!-- Header -->
                <div class="flex items-center gap-4 border-b border-slate-200 dark:border-white/10 pb-4">
                    <Link :href="route('logistics.fleet.index')" class="p-2 bg-white hover:bg-slate-100 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:border-transparent rounded-xl shadow-sm hover:shadow-md transition-all cursor-pointer text-slate-550 dark:text-slate-200">
                        <ChevronLeftIcon class="h-5 w-5" />
                    </Link>
                    <div>
                        <h2 class="text-xl font-black text-slate-900 dark:text-white tracking-tight leading-none mb-1.5">VEHICLE DETAILS</h2>
                        <p class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest leading-none font-mono">{{ vehicle.license_plate }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <!-- Left Column: Vehicle & Driver Info -->
                    <div class="lg:col-span-1 space-y-6">
                        <!-- Identity Card -->
                        <div class="bg-white/70 dark:bg-[#0a0a16]/60 rounded-3xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                            <div class="relative h-56 bg-slate-105 dark:bg-slate-800 flex items-center justify-center">
                                <img v-if="vehicle.vehicle_photo_url" :src="vehicle.vehicle_photo_url" class="w-full h-full object-cover" />
                                <TruckIcon v-else class="h-24 w-24 text-slate-300 dark:text-slate-700" />
                                
                                <div class="absolute top-4 right-4">
                                    <span class="px-4 py-1.5 text-[9px] font-black rounded-full uppercase tracking-widest shadow-sm backdrop-blur-md border" :class="getStatusColor(vehicle.status)">
                                        {{ vehicle.status }}
                                    </span>
                                </div>
                            </div>
                            <div class="p-8">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <h3 class="text-3xl font-black text-slate-900 dark:text-white tracking-tighter leading-none mb-1 font-mono">{{ vehicle.license_plate }}</h3>
                                        <p class="text-[11px] font-bold text-slate-500 dark:text-slate-450 uppercase tracking-[0.2em]">{{ vehicle.brand }} • {{ vehicle.vehicle_type }}</p>
                                    </div>
                                    <div class="bg-blue-50 dark:bg-blue-500/10 p-3 rounded-2xl border border-blue-100 dark:border-transparent">
                                        <TruckIcon class="h-8 w-8 text-blue-650 dark:text-blue-400" />
                                    </div>
                                </div>

                                <div class="mt-8 space-y-4">
                                    <div class="flex items-center gap-4 bg-slate-50 dark:bg-slate-800/50 p-4 rounded-2xl border border-slate-150 dark:border-slate-800">
                                        <div class="w-14 h-14 rounded-xl overflow-hidden shadow-sm ring-2 ring-white dark:ring-slate-700 shrink-0">
                                            <img v-if="vehicle.driver_photo_url" :src="vehicle.driver_photo_url" class="w-full h-full object-cover" />
                                            <div v-else class="w-full h-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center text-slate-400 dark:text-slate-500"><UserIcon class="w-6 h-6" /></div>
                                        </div>
                                        <div>
                                            <p class="text-[9px] font-black uppercase tracking-widest text-slate-400">Main Driver</p>
                                            <p class="text-lg font-black text-slate-900 dark:text-white tracking-tight">{{ vehicle.driver_name || 'Not assigned' }}</p>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-150 dark:border-slate-800">
                                            <p class="text-[9px] font-black uppercase tracking-widest text-slate-400">Weight Cap</p>
                                            <p class="text-md font-black text-slate-900 dark:text-white tracking-tight font-mono">{{ formatNumber(vehicle.capacity_weight/1000) }} Ton</p>
                                        </div>
                                        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-150 dark:border-slate-800 text-right">
                                            <p class="text-[9px] font-black uppercase tracking-widest text-slate-400">Volume Cap</p>
                                            <p class="text-md font-black text-slate-900 dark:text-white tracking-tight font-mono">{{ formatNumber(vehicle.capacity_volume) }} m³</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Legal Docs -->
                        <div class="bg-white/70 dark:bg-[#0a0a16]/60 rounded-3xl border border-slate-200 dark:border-slate-800 p-8 shadow-sm">
                            <h4 class="text-xs font-black uppercase tracking-widest text-slate-900 dark:text-white mb-6">Legal Documentation</h4>
                            <div class="space-y-6">
                                <div class="flex items-center gap-4">
                                    <div class="bg-amber-50 dark:bg-amber-500/10 p-3 rounded-xl border border-amber-100 dark:border-transparent">
                                        <DocumentTextIcon class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-[9px] uppercase font-black tracking-widest text-slate-400">STNK Registration</p>
                                        <p class="text-sm font-bold text-slate-900 dark:text-white font-mono">{{ vehicle.stnk_number || '-' }}</p>
                                        <p class="text-[10px] mt-1 font-mono font-bold" :class="isExpired(vehicle.stnk_expiry) ? 'text-red-500' : 'text-slate-500'">Expires: {{ formatDate(vehicle.stnk_expiry) }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4 border-t border-slate-100 dark:border-slate-800 pt-6">
                                    <div class="bg-blue-50 dark:bg-blue-500/10 p-3 rounded-xl border border-blue-100 dark:border-transparent">
                                        <CheckBadgeIcon class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-[9px] uppercase font-black tracking-widest text-slate-400">KIR Certificate</p>
                                        <p class="text-sm font-bold text-slate-900 dark:text-white font-mono">{{ vehicle.kir_number || '-' }}</p>
                                        <p class="text-[10px] mt-1 font-mono font-bold" :class="isExpired(vehicle.kir_expiry) ? 'text-red-500' : 'text-slate-500'">Expires: {{ formatDate(vehicle.kir_expiry) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Stats & History -->
                    <div class="lg:col-span-2 space-y-8">
                        <!-- Stats Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="bg-white/70 dark:bg-[#0a0a16]/60 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="bg-blue-50 dark:bg-blue-500/10 p-2 rounded-lg text-blue-600 dark:text-blue-400 border border-blue-100 dark:border-transparent">
                                        <TrophyIcon class="w-5 h-5" />
                                    </div>
                                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Total Trips</span>
                                </div>
                                <p class="text-3xl font-black text-slate-900 dark:text-white tracking-tighter font-mono">{{ stats.total_trips }}</p>
                            </div>
                            <div class="bg-white/70 dark:bg-[#0a0a16]/60 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="bg-emerald-50 dark:bg-emerald-500/10 p-2 rounded-lg text-emerald-600 dark:text-emerald-450 border border-emerald-100 dark:border-transparent">
                                        <CheckBadgeIcon class="w-5 h-5" />
                                    </div>
                                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Delivered</span>
                                </div>
                                <p class="text-3xl font-black text-slate-900 dark:text-white tracking-tighter font-mono">{{ stats.completed_trips }}</p>
                            </div>
                            <div class="bg-white/70 dark:bg-[#0a0a16]/60 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="bg-amber-50 dark:bg-amber-500/10 p-2 rounded-lg text-amber-600 dark:text-amber-500 border border-amber-100 dark:border-transparent">
                                        <ClockIcon class="w-5 h-5" />
                                    </div>
                                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">In Progress</span>
                                </div>
                                <p class="text-3xl font-black text-slate-900 dark:text-white tracking-tighter font-mono">{{ stats.pending_trips }}</p>
                            </div>
                        </div>

                        <!-- Activity Table -->
                        <div class="bg-white/70 dark:bg-[#0a0a16]/60 rounded-3xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                            <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                                <h4 class="text-xs font-black uppercase tracking-widest text-slate-900 dark:text-white">Activity History</h4>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead>
                                        <tr class="text-left bg-slate-55 dark:bg-slate-800/50">
                                            <th class="px-6 py-4 text-[9px] font-black uppercase tracking-widest text-slate-500">Ref Number</th>
                                            <th class="px-6 py-4 text-[9px] font-black uppercase tracking-widest text-slate-500">Customer</th>
                                            <th class="px-6 py-4 text-[9px] font-black uppercase tracking-widest text-slate-500">Date</th>
                                            <th class="px-6 py-4 text-[9px] font-black uppercase tracking-widest text-slate-500">Status</th>
                                            <th class="px-6 py-4 text-[9px] font-black uppercase tracking-widest text-slate-500">Items</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                                        <tr v-for="order in vehicle.delivery_orders" :key="order.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors group">
                                            <td class="px-6 py-4">
                                                <p class="text-sm font-bold text-blue-605 dark:text-blue-400 font-mono">{{ order.do_number }}</p>
                                            </td>
                                            <td class="px-6 py-4">
                                                <p class="text-sm font-bold text-slate-900 dark:text-white">{{ order.customer?.name || '-' }}</p>
                                                <p class="text-[10px] text-slate-500">{{ order.shipping_name }}</p>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400 font-medium font-mono">
                                                    <CalendarIcon class="w-3.5 h-3.5 text-slate-400" />
                                                    {{ formatDate(order.delivery_date) }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="px-2.5 py-1 text-[9px] font-black rounded-full uppercase tracking-widest border" :class="getDoStatusColor(order.status)">
                                                    {{ order.status }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 font-mono">
                                                    {{ order.items?.length || 0 }} SKU(s)
                                                </p>
                                            </td>
                                        </tr>
                                        <tr v-if="!vehicle.delivery_orders || vehicle.delivery_orders.length === 0">
                                            <td colspan="5" class="px-6 py-12 text-center">
                                                <div class="flex flex-col items-center gap-3">
                                                    <DocumentTextIcon class="h-12 w-12 text-slate-300 dark:text-slate-700" />
                                                    <p class="text-xs font-bold text-slate-500 dark:text-slate-400">No activity recorded for this vehicle.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.perspective-grid {
    background-image: 
        linear-gradient(to right, rgba(99, 102, 241, 0.05) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(99, 102, 241, 0.05) 1px, transparent 1px);
    background-size: 40px 40px;
    transform: perspective(500px) rotateX(60deg) translateY(-100px) scale(2);
    animation: grid-move 20s linear infinite;
    transform-origin: top;
}

@keyframes grid-move {
    0% { background-position: 0 0; }
    100% { background-position: 0 40px; }
}

.dark .glow-text {
    text-shadow: 0 0 10px rgba(99, 102, 241, 0.3);
}
</style>
