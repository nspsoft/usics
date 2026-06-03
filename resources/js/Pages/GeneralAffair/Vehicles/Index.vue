<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    PlusIcon, 
    TruckIcon, 
    MagnifyingGlassIcon,
    CalendarDaysIcon,
    UserIcon,
    MapPinIcon,
    ChatBubbleBottomCenterTextIcon,
    CheckCircleIcon
} from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';
import moment from 'moment';

const props = defineProps({
    bookings: Object,
    vehicles: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');

watch([search, status], debounce(() => {
    router.get(
        route('ga.vehicle-bookings.index'),
        { search: search.value, status: status.value },
        { preserveState: true, preserveScroll: true, replace: true }
    );
}, 300));
</script>

<template>
    <Head title="Manajemen Kendaraan (GA)" />

    <AppLayout title="Manajemen Kendaraan">
        <div class="space-y-6">
            
            <!-- Section 1: Vehicles Status Catalog -->
            <div class="glass-card rounded-3xl p-6 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-black text-slate-900 dark:text-white flex items-center gap-2">
                            <TruckIcon class="h-5 w-5 text-cyan-600" />
                            Katalog & Status Kendaraan
                        </h3>
                        <p class="text-sm text-slate-500">Daftar armada kendaraan operasional perusahaan saat ini</p>
                    </div>
                    <div>
                        <a 
                            href="/general-affair/fleet"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                        >
                            Kelola Armada
                        </a>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                    <div v-for="vehicle in vehicles" :key="vehicle.id" 
                        class="rounded-2xl border p-4 bg-slate-50/50 dark:bg-slate-900/30 transition hover:shadow-md"
                        :class="[
                            vehicle.status === 'in_use' ? 'border-amber-500/20 dark:border-amber-500/10' :
                            vehicle.status === 'maintenance' ? 'border-red-500/20 dark:border-red-500/10' :
                            'border-slate-200 dark:border-slate-800'
                        ]"
                    >
                        <div class="flex items-start justify-between">
                            <div>
                                <h4 class="font-bold text-slate-900 dark:text-white">{{ vehicle.brand }} - {{ vehicle.model || vehicle.vehicle_type }}</h4>
                                <p class="text-xs text-slate-500 font-semibold">{{ vehicle.license_plate }}</p>
                            </div>
                            <span class="inline-flex rounded px-1.5 py-0.5 text-2xs font-semibold uppercase"
                                :class="{
                                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/20 dark:text-emerald-400': vehicle.status === 'available' || !vehicle.status,
                                    'bg-amber-100 text-amber-800 dark:bg-amber-950/20 dark:text-amber-400': vehicle.status === 'in_use',
                                    'bg-red-100 text-red-800 dark:bg-red-950/20 dark:text-red-400': vehicle.status === 'maintenance',
                                }"
                            >
                                {{ vehicle.status === 'in_use' ? 'In Use' : vehicle.status === 'maintenance' ? 'Service' : 'Ready' }}
                            </span>
                        </div>
                        
                        <div class="mt-4 space-y-1.5 text-xs text-slate-700 dark:text-slate-300 border-t border-slate-200 dark:border-slate-800 pt-3">
                            <div class="flex items-center gap-1.5">
                                <UserIcon class="h-3.5 w-3.5 text-slate-400" />
                                <span>Driver: <strong class="text-slate-900 dark:text-white">{{ vehicle.driver_name || '-' }}</strong></span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <ChatBubbleBottomCenterTextIcon class="h-3.5 w-3.5 text-slate-400" />
                                <span class="truncate">{{ vehicle.notes || 'Operasional umum' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Bookings List -->
            <div class="glass-card rounded-3xl p-6 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-lg font-black text-slate-900 dark:text-white">Pengajuan Peminjaman</h3>
                        <p class="text-sm text-slate-500">Daftar permohonan penggunaan kendaraan oleh karyawan</p>
                    </div>
                    <Link 
                        :href="route('ga.vehicle-bookings.create')"
                        class="inline-flex items-center gap-2 rounded-xl bg-cyan-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-cyan-500 focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2"
                    >
                        <PlusIcon class="h-4 w-4" />
                        Ajukan Peminjaman
                    </Link>
                </div>

                <!-- Filters -->
                <div class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-center">
                    <div class="relative max-w-sm flex-1">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <MagnifyingGlassIcon class="h-5 w-5 text-slate-400" />
                        </div>
                        <input 
                            v-model="search" 
                            type="text" 
                            class="block w-full rounded-xl border-slate-300 pl-10 focus:border-cyan-500 focus:ring-cyan-500 sm:text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white" 
                            placeholder="Cari pelapor / tujuan / keperluan..." 
                        />
                    </div>
                    <select 
                        v-model="status"
                        class="block w-full rounded-xl border-slate-300 focus:border-cyan-500 focus:ring-cyan-500 sm:max-w-xs sm:text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                    >
                        <option value="">Semua Status</option>
                        <option value="pending">Pending (Menunggu Persetujuan)</option>
                        <option value="approved">Approved (Disetujui)</option>
                        <option value="active">Active (Sedang Jalan)</option>
                        <option value="completed">Completed (Selesai)</option>
                        <option value="rejected">Rejected (Ditolak)</option>
                    </select>
                </div>

                <!-- Table -->
                <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 shadow-sm dark:border-slate-800">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                        <thead class="bg-slate-50 dark:bg-slate-900/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Karyawan</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Tujuan & Keperluan</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Jadwal</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Kendaraan & Driver</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-900">
                            <tr v-for="booking in bookings.data" :key="booking.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <!-- User -->
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-bold text-slate-900 dark:text-white">
                                    {{ booking.user?.name }}
                                </td>

                                <!-- Destination & Purpose -->
                                <td class="px-4 py-4 text-sm text-slate-900 dark:text-white">
                                    <div class="font-semibold flex items-center gap-1">
                                        <MapPinIcon class="h-4 w-4 text-slate-400 flex-shrink-0" />
                                        <span>{{ booking.destination }}</span>
                                    </div>
                                    <div class="text-xs text-slate-500 max-w-xs truncate">{{ booking.purpose }}</div>
                                </td>

                                <!-- Schedule -->
                                <td class="px-4 py-4 text-xs text-slate-700 dark:text-slate-300">
                                    <div class="flex items-center gap-1">
                                        <CalendarDaysIcon class="h-3.5 w-3.5 text-slate-400" />
                                        <span>{{ moment(booking.start_time).format('DD MMM YYYY, HH:mm') }}</span>
                                    </div>
                                    <div class="text-slate-400 mt-0.5 pl-4.5">s/d {{ moment(booking.end_time).format('DD MMM YYYY, HH:mm') }}</div>
                                </td>

                                <!-- Vehicle & Driver -->
                                <td class="px-4 py-4 text-sm text-slate-700 dark:text-slate-300">
                                    <div v-if="booking.vehicle" class="font-medium flex items-center gap-1">
                                        <TruckIcon class="h-4 w-4 text-slate-400" />
                                        <span>{{ booking.vehicle.brand }} ({{ booking.vehicle.license_plate }})</span>
                                    </div>
                                    <div v-else class="text-slate-400">Belum ditentukan</div>
                                    
                                    <div v-if="booking.driver_name" class="text-xs text-slate-500 mt-1 flex items-center gap-1">
                                        <UserIcon class="h-3 w-3 text-slate-400" />
                                        <span>Driver: {{ booking.driver_name }}</span>
                                    </div>
                                </td>

                                <!-- Status Badge -->
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold"
                                        :class="{
                                            'bg-slate-100 text-slate-800': booking.status === 'pending',
                                            'bg-blue-100 text-blue-800': booking.status === 'approved',
                                            'bg-amber-100 text-amber-800': booking.status === 'active',
                                            'bg-emerald-100 text-emerald-800': booking.status === 'completed',
                                            'bg-red-100 text-red-800': booking.status === 'rejected',
                                        }"
                                    >
                                        {{ booking.status.toUpperCase() }}
                                    </span>
                                </td>

                                <!-- Action -->
                                <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <Link :href="route('ga.vehicle-bookings.show', booking.id)" class="text-cyan-600 hover:text-cyan-900 dark:text-cyan-400 dark:hover:text-cyan-300 font-bold">Detail</Link>
                                </td>
                            </tr>
                            <tr v-if="bookings.data.length === 0">
                                <td colspan="6" class="py-8 text-center text-sm text-slate-500">
                                    Belum ada data pengajuan peminjaman kendaraan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-4 flex items-center justify-between" v-if="bookings.links.length > 3">
                    <div class="flex flex-1 justify-between sm:hidden">
                        <Link :href="bookings.prev_page_url" class="relative inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Previous</Link>
                        <Link :href="bookings.next_page_url" class="relative ml-3 inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Next</Link>
                    </div>
                    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-slate-700 dark:text-slate-300">
                                Showing <span class="font-medium">{{ bookings.from || 0 }}</span> to <span class="font-medium">{{ bookings.to || 0 }}</span> of <span class="font-medium">{{ bookings.total }}</span> results
                            </p>
                        </div>
                        <div>
                            <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                                <Link v-for="(link, i) in bookings.links" :key="i" :href="link.url" v-html="link.label" 
                                    class="relative inline-flex items-center px-4 py-2 text-sm font-semibold focus:z-20 border"
                                    :class="[
                                        link.active ? 'z-10 bg-cyan-600 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cyan-600 border-cyan-600' : 'text-slate-900 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 dark:text-slate-300 dark:border-slate-700 dark:hover:bg-slate-800',
                                        !link.url ? 'opacity-50 cursor-not-allowed' : ''
                                    ]"
                                />
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
