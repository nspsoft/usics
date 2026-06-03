<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatNumber, formatDate } from '@/helpers';
import { 
    TruckIcon,
    UserIcon,
    DocumentTextIcon,
    CalendarIcon,
    ChevronLeftIcon,
    ClockIcon,
    CreditCardIcon,
    MapPinIcon,
    CheckCircleIcon
} from '@heroicons/vue/24/outline';
import moment from 'moment';

const props = defineProps({
    vehicle: Object,
    stats: Object
});

const getStatusColor = (status) => {
    switch (status) {
        case 'available': return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400';
        case 'busy': return 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400';
        case 'in_use': return 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400';
        case 'maintenance': return 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400';
        default: return 'bg-slate-100 text-slate-700 dark:bg-slate-500/10 dark:text-slate-400';
    }
};

const getBookingStatusColor = (status) => {
    switch (status) {
        case 'completed': return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400';
        case 'active': return 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400';
        case 'approved': return 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400';
        case 'pending': return 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400';
        case 'rejected': return 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400';
        default: return 'bg-slate-100 text-slate-700';
    }
};
</script>

<template>
    <Head title="Detail Kendaraan Operasional" />

    <AppLayout title="Detail Kendaraan Operasional">
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('ga.fleet.index')" class="p-2 bg-white dark:bg-slate-800 rounded-xl shadow-sm hover:shadow-md transition-all">
                    <ChevronLeftIcon class="h-5 w-5 text-slate-600 dark:text-slate-400" />
                </Link>
                <div>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">Detail Kendaraan GA</h2>
                    <p class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">{{ vehicle.license_plate }}</p>
                </div>
            </div>
        </template>

        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Vehicle Info Cards -->
                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                        <!-- Photo -->
                        <div class="relative h-56 bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                            <img 
                                v-if="vehicle.vehicle_photo_url" 
                                :src="vehicle.vehicle_photo_url" 
                                class="w-full h-full object-cover" 
                            />
                            <TruckIcon v-else class="h-24 w-24 text-slate-300 dark:text-slate-700" />
                            
                            <div class="absolute top-4 right-4">
                                <span 
                                    class="px-4 py-1.5 text-[10px] font-black rounded-full uppercase tracking-widest shadow-xl backdrop-blur-md border border-white/20"
                                    :class="getStatusColor(vehicle.status)"
                                >
                                    {{ vehicle.status }}
                                </span>
                            </div>
                        </div>

                        <!-- Data details -->
                        <div class="p-8">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h3 class="text-3xl font-black text-slate-900 dark:text-white tracking-tighter leading-none mb-1">{{ vehicle.license_plate }}</h3>
                                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-[0.2em]">{{ vehicle.brand }} • {{ vehicle.model || vehicle.vehicle_type }}</p>
                                </div>
                                <div class="bg-cyan-50 dark:bg-cyan-500/10 p-3 rounded-2xl">
                                    <TruckIcon class="h-8 w-8 text-cyan-600 dark:text-cyan-400" />
                                </div>
                            </div>

                            <div class="mt-8 space-y-4">
                                <!-- Driver -->
                                <div class="flex items-center gap-4 bg-slate-50 dark:bg-slate-800/50 p-4 rounded-2xl border border-slate-100 dark:border-slate-800">
                                    <div class="w-14 h-14 rounded-xl overflow-hidden shadow-md ring-2 ring-white dark:ring-slate-700 shrink-0">
                                        <img 
                                            v-if="vehicle.driver_photo_url" 
                                            :src="vehicle.driver_photo_url" 
                                            class="w-full h-full object-cover" 
                                        />
                                        <div v-else class="w-full h-full bg-slate-200 flex items-center justify-center">
                                            <UserIcon class="w-6 h-6 text-slate-400" />
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-[9px] font-black uppercase tracking-widest text-slate-400">Driver Utama</p>
                                        <p class="text-lg font-black text-slate-900 dark:text-white tracking-tight">{{ vehicle.driver_name || 'Tidak ada driver tetap' }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800">
                                        <p class="text-[9px] font-black uppercase tracking-widest text-slate-400">Tahun Pembuatan</p>
                                        <p class="text-md font-black text-slate-900 dark:text-white tracking-tight">{{ vehicle.year || '-' }}</p>
                                    </div>
                                    <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800 text-right">
                                        <p class="text-[9px] font-black uppercase tracking-widest text-slate-400">Bahan Bakar</p>
                                        <p class="text-md font-black text-slate-900 dark:text-white tracking-tight">{{ vehicle.fuel_type || '-' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Legal details -->
                    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-8 shadow-sm">
                        <h4 class="text-xs font-black uppercase tracking-widest text-slate-900 dark:text-white mb-6">Legalitas & Dokumen</h4>
                        <div class="space-y-6">
                            <div class="flex items-center gap-4">
                                <div class="bg-amber-50 dark:bg-amber-500/10 p-3 rounded-xl">
                                    <DocumentTextIcon class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                                </div>
                                <div class="flex-1">
                                    <p class="text-[10px] uppercase font-black tracking-widest text-slate-400">Nomor STNK</p>
                                    <p class="text-sm font-bold text-slate-900 dark:text-white">{{ vehicle.stnk_number || '-' }}</p>
                                    <p class="text-[10px] mt-1" :class="new Date(vehicle.stnk_expiry) < new Date() ? 'text-red-500 font-bold' : 'text-slate-500'">
                                        Berlaku s/d: {{ formatDate(vehicle.stnk_expiry) }}
                                    </p>
                                </div>
                            </div>

                            <div v-if="vehicle.kir_number" class="flex items-center gap-4 border-t border-slate-50 dark:border-slate-800 pt-6">
                                <div class="bg-cyan-50 dark:bg-cyan-500/10 p-3 rounded-xl">
                                    <CheckCircleIcon class="w-5 h-5 text-cyan-600 dark:text-cyan-400" />
                                </div>
                                <div class="flex-1">
                                    <p class="text-[10px] uppercase font-black tracking-widest text-slate-400">Nomor KIR</p>
                                    <p class="text-sm font-bold text-slate-900 dark:text-white">{{ vehicle.kir_number }}</p>
                                    <p class="text-[10px] mt-1" :class="new Date(vehicle.kir_expiry) < new Date() ? 'text-red-500 font-bold' : 'text-slate-500'">
                                        Berlaku s/d: {{ formatDate(vehicle.kir_expiry) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Booking History -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Stats Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
                            <div class="flex items-center justify-between mb-2">
                                <div class="bg-blue-50 dark:bg-blue-500/10 p-2 rounded-lg">
                                    <CalendarIcon class="w-5 h-5 text-blue-600" />
                                </div>
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Total Bookings</span>
                            </div>
                            <p class="text-3xl font-black text-slate-900 dark:text-white tracking-tighter">{{ stats.total_bookings }}</p>
                        </div>

                        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
                            <div class="flex items-center justify-between mb-2">
                                <div class="bg-emerald-50 dark:bg-emerald-500/10 p-2 rounded-lg">
                                    <CheckCircleIcon class="w-5 h-5 text-emerald-600" />
                                </div>
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Completed Trips</span>
                            </div>
                            <p class="text-3xl font-black text-slate-900 dark:text-white tracking-tighter">{{ stats.completed_trips }}</p>
                        </div>

                        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
                            <div class="flex items-center justify-between mb-2">
                                <div class="bg-indigo-50 dark:bg-indigo-500/10 p-2 rounded-lg">
                                    <ClockIcon class="w-5 h-5 text-indigo-600" />
                                </div>
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Pending Approval</span>
                            </div>
                            <p class="text-3xl font-black text-slate-900 dark:text-white tracking-tighter">{{ stats.pending_bookings }}</p>
                        </div>
                    </div>

                    <!-- History Table -->
                    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                        <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                            <h4 class="text-xs font-black uppercase tracking-widest text-slate-900 dark:text-white">Riwayat Perjalanan & Peminjaman</h4>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left bg-slate-50 dark:bg-slate-800/50">
                                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-500">Peminjam</th>
                                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-500">Tujuan & Keperluan</th>
                                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-500">Tanggal</th>
                                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-500">Status</th>
                                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-500">Detail Biaya</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <tr v-for="booking in vehicle.ga_bookings" :key="booking.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors group">
                                        <td class="px-6 py-4">
                                            <p class="text-sm font-bold text-slate-900 dark:text-white">{{ booking.user?.name }}</p>
                                            <p class="text-[9px] text-slate-400">Pax: {{ booking.passengers_count }}</p>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-col max-w-xs">
                                                <span class="text-xs font-bold text-slate-700 dark:text-slate-300 truncate">{{ booking.purpose }}</span>
                                                <span class="text-[10px] text-slate-500 flex items-center gap-1 mt-0.5">
                                                    <MapPinIcon class="w-3 h-3 text-red-500 shrink-0" />
                                                    <span class="truncate">{{ booking.destination }}</span>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400 font-medium">
                                                <CalendarIcon class="w-3.5 h-3.5" />
                                                <span>{{ moment(booking.start_time).format('DD MMM YYYY') }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2.5 py-1 text-[9px] font-black rounded-full uppercase tracking-widest" :class="getBookingStatusColor(booking.status)">
                                                {{ booking.status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div v-if="booking.trip" class="text-xs space-y-0.5 text-slate-600 dark:text-slate-400">
                                                <p>Odo: <strong class="text-slate-800 dark:text-slate-200">{{ booking.trip.odometer_start }} - {{ booking.trip.odometer_end || '?' }}</strong></p>
                                                <p v-if="booking.trip.fuel_cost || booking.trip.toll_cost">
                                                    Cost: <span class="text-emerald-600 font-semibold">Rp {{ formatNumber((booking.trip.fuel_cost || 0) + (booking.trip.toll_cost || 0)) }}</span>
                                                </p>
                                            </div>
                                            <span v-else class="text-xs text-slate-400">-</span>
                                        </td>
                                    </tr>
                                    <tr v-if="!vehicle.ga_bookings || vehicle.ga_bookings.length === 0">
                                        <td colspan="5" class="px-6 py-12 text-center">
                                            <div class="flex flex-col items-center gap-3">
                                                <CalendarIcon class="h-12 w-12 text-slate-200 dark:text-slate-700" />
                                                <p class="text-sm font-bold text-slate-400">Belum ada riwayat peminjaman untuk kendaraan ini.</p>
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
    </AppLayout>
</template>
