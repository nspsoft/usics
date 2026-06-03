<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    ArrowLeftIcon,
    CalendarDaysIcon,
    UserIcon,
    TruckIcon,
    MapPinIcon,
    ChatBubbleBottomCenterTextIcon,
    CurrencyDollarIcon,
    CheckCircleIcon,
    XCircleIcon,
    ScaleIcon,
    PhotoIcon
} from '@heroicons/vue/24/outline';
import moment from 'moment';

const props = defineProps({
    booking: Object,
    vehicles: Array,
});

// Admin Approval Form
const approvalForm = useForm({
    vehicle_id: '',
    driver_name: '',
    approval_notes: '',
});

// Autopopulate driver name when vehicle is selected
watch(() => approvalForm.vehicle_id, (newVehId) => {
    if (newVehId) {
        const vehicle = props.vehicles.find(v => v.id === newVehId);
        if (vehicle) {
            approvalForm.driver_name = vehicle.driver_name || '';
        }
    }
});

const submitApproval = () => {
    approvalForm.post(route('vehicle-bookings.approve', props.booking.id));
};

const rejectForm = useForm({
    approval_notes: '',
});

const submitRejection = () => {
    if (!rejectForm.approval_notes) {
        alert('Harap isi alasan penolakan.');
        return;
    }
    rejectForm.post(route('vehicle-bookings.reject', props.booking.id));
};

// Start Trip Form
const startForm = useForm({
    odometer_start: '',
});

const submitStart = () => {
    startForm.post(route('vehicle-bookings.start', props.booking.id));
};

// Complete Trip Form (requires multipart POST due to image upload)
const completeForm = useForm({
    _method: 'POST',
    odometer_end: '',
    fuel_liters: '',
    fuel_cost: '',
    toll_cost: '',
    image: null,
    notes: '',
});

const handleFileChange = (e) => {
    completeForm.image = e.target.files[0];
};

const submitComplete = () => {
    completeForm.post(route('vehicle-bookings.complete', props.booking.id));
};

// Calculations for completed state
const distanceTraveled = computed(() => {
    if (!props.booking.trip || !props.booking.trip.odometer_end) return 0;
    return props.booking.trip.odometer_end - props.booking.trip.odometer_start;
});

const totalOperationalCost = computed(() => {
    if (!props.booking.trip) return 0;
    return Number(props.booking.trip.fuel_cost || 0) + Number(props.booking.trip.toll_cost || 0);
});
</script>

<template>
    <Head :title="`Detail Peminjaman Kendaraan`" />

    <AppLayout title="Detail Peminjaman">
        <div class="mx-auto max-w-5xl">
            <!-- Header -->
            <div class="mb-6 flex items-center gap-4">
                <Link :href="route('ga.vehicle-bookings.index')" class="rounded-full p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800">
                    <ArrowLeftIcon class="h-5 w-5" />
                </Link>
                <div>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white">Detail Pengajuan Kendaraan</h2>
                    <p class="text-sm text-slate-500">Status: <span class="font-bold uppercase">{{ booking.status }}</span></p>
                </div>
            </div>

            <!-- Main Layout Grid -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                
                <!-- Left Column: Details -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- Booking Info Card -->
                    <div class="glass-card rounded-3xl p-6 shadow-sm">
                        <h3 class="mb-4 text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                            <CalendarDaysIcon class="h-5 w-5 text-cyan-600" />
                            Detail Pengajuan Peminjaman
                        </h3>

                        <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2 text-sm">
                            <div>
                                <dt class="text-xs text-slate-500 uppercase font-semibold">Nama Karyawan</dt>
                                <dd class="mt-0.5 font-bold text-slate-900 dark:text-white">{{ booking.user?.name }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500 uppercase font-semibold">Tujuan Perjalanan</dt>
                                <dd class="mt-0.5 font-bold text-slate-900 dark:text-white flex items-center gap-1">
                                    <MapPinIcon class="h-4 w-4 text-slate-400" />
                                    {{ booking.destination }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500 uppercase font-semibold">Tanggal & Jam Mulai</dt>
                                <dd class="mt-0.5 font-medium text-slate-900 dark:text-white">
                                    {{ moment(booking.start_time).format('DD MMM YYYY, HH:mm') }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500 uppercase font-semibold">Estimasi Selesai</dt>
                                <dd class="mt-0.5 font-medium text-slate-900 dark:text-white">
                                    {{ moment(booking.end_time).format('DD MMM YYYY, HH:mm') }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500 uppercase font-semibold">Jumlah Penumpang</dt>
                                <dd class="mt-0.5 font-medium text-slate-900 dark:text-white">{{ booking.passengers_count }} Orang</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-xs text-slate-500 uppercase font-semibold">Keperluan / Keterangan</dt>
                                <dd class="mt-1 text-slate-700 dark:text-slate-300 bg-slate-50 dark:bg-slate-900/50 p-3 rounded-xl whitespace-pre-wrap">{{ booking.purpose }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Assigned Vehicle Details -->
                    <div v-if="booking.vehicle" class="glass-card rounded-3xl p-6 shadow-sm">
                        <h3 class="mb-4 text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                            <TruckIcon class="h-5 w-5 text-cyan-600" />
                            Armada Kendaraan & Driver
                        </h3>

                        <div class="flex flex-col sm:flex-row gap-6">
                            <!-- Vehicle Photo/Avatar -->
                            <div class="w-24 h-24 flex-shrink-0 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center border dark:border-slate-850">
                                <TruckIcon class="h-10 w-10 text-slate-400" />
                            </div>
                            <!-- Vehicle Details -->
                            <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <div class="text-xs text-slate-500">Merek & Model</div>
                                    <div class="font-bold text-slate-900 dark:text-white">{{ booking.vehicle.brand }} ({{ booking.vehicle.model || booking.vehicle.vehicle_type }})</div>
                                </div>
                                <div>
                                    <div class="text-xs text-slate-500">Plat Nomor</div>
                                    <div class="font-bold text-slate-900 dark:text-white">{{ booking.vehicle.license_plate }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-slate-500">Nama Pengemudi (Driver)</div>
                                    <div class="font-bold text-slate-900 dark:text-white">{{ booking.driver_name || 'Tanpa Driver (Self Drive)' }}</div>
                                </div>
                                <div v-if="booking.approval_notes">
                                    <div class="text-xs text-slate-500">Catatan Admin GA</div>
                                    <div class="text-slate-700 dark:text-slate-300 font-medium">{{ booking.approval_notes }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Trip Logs Details & Operational cost breakdown (Completed state) -->
                    <div v-if="booking.status === 'completed' && booking.trip" class="glass-card rounded-3xl p-6 shadow-sm border border-emerald-500/10 bg-gradient-to-tr from-emerald-500/5 via-transparent to-transparent">
                        <h3 class="mb-4 text-base font-black text-slate-950 dark:text-white flex items-center gap-2">
                            <CheckCircleIcon class="h-5 w-5 text-emerald-600" />
                            Realisasi Perjalanan & Biaya Operasional
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 text-sm mb-6 border-b border-slate-200 dark:border-slate-800 pb-6">
                            <div>
                                <div class="text-xs text-slate-500">Jarak Tempuh (Odometer)</div>
                                <div class="font-black text-lg text-slate-900 dark:text-white">{{ distanceTraveled }} KM</div>
                                <div class="text-xs text-slate-400">Start: {{ booking.trip.odometer_start }} | End: {{ booking.trip.odometer_end }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-slate-500">Penggunaan Bensin</div>
                                <div class="font-black text-lg text-slate-900 dark:text-white">
                                    Rp {{ Number(booking.trip.fuel_cost || 0).toLocaleString('id-ID') }}
                                </div>
                                <div class="text-xs text-slate-400" v-if="booking.trip.fuel_liters">{{ booking.trip.fuel_liters }} Liter</div>
                            </div>
                            <div>
                                <div class="text-xs text-slate-500">Biaya Tol / Parkir</div>
                                <div class="font-black text-lg text-slate-900 dark:text-white">
                                    Rp {{ Number(booking.trip.toll_cost || 0).toLocaleString('id-ID') }}
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <!-- Receipt image -->
                            <div>
                                <span class="block text-xs font-semibold text-slate-500 uppercase mb-2">Foto Struk / Bukti Pembayaran</span>
                                <div v-if="booking.trip.receipt_url" class="relative w-full rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-800 bg-slate-100" style="padding-top: 56.25%;">
                                    <img :src="booking.trip.receipt_url" class="absolute inset-0 h-full w-full object-cover" />
                                    <a :href="booking.trip.receipt_url" target="_blank" class="absolute bottom-2 right-2 rounded-lg bg-black/60 px-3 py-1.5 text-xs font-bold text-white hover:bg-black/85">Buka Gambar ↗</a>
                                </div>
                                <div v-else class="rounded-xl border border-dashed p-6 text-center text-xs text-slate-400 dark:border-slate-800">
                                    <PhotoIcon class="h-10 w-10 mx-auto text-slate-300 mb-1" />
                                    Tidak ada foto bukti pengeluaran
                                </div>
                            </div>
                            <!-- Logs notes -->
                            <div>
                                <span class="block text-xs font-semibold text-slate-500 uppercase mb-2">Catatan Perjalanan</span>
                                <p class="text-sm text-slate-700 dark:text-slate-300 bg-slate-50 dark:bg-slate-900/50 p-4 rounded-2xl whitespace-pre-wrap h-32 overflow-y-auto">
                                    {{ booking.trip.notes || 'Tidak ada catatan tambahan.' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Flow Controls / States Panels -->
                <div class="lg:col-span-1 space-y-6">
                    
                    <!-- 1. PENDING State: Admin approvals -->
                    <div v-if="booking.status === 'pending'" class="glass-card rounded-3xl p-6 shadow-sm">
                        <h3 class="mb-4 text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                            <ScaleIcon class="h-5 w-5 text-cyan-600" />
                            Persetujuan Admin GA
                        </h3>

                        <!-- Approval Form -->
                        <form @submit.prevent="submitApproval" class="space-y-4 mb-6">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Pilih Kendaraan Operasional <span class="text-red-500">*</span></label>
                                <select v-model="approvalForm.vehicle_id" required class="mt-1 block w-full rounded-xl border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                                    <option value="">-- Pilih Armada --</option>
                                    <option v-for="vehicle in vehicles" :key="vehicle.id" :value="vehicle.id" :disabled="vehicle.status === 'in_use' || vehicle.status === 'maintenance'">
                                        {{ vehicle.brand }} - {{ vehicle.license_plate }} ({{ vehicle.status === 'in_use' ? 'Dipakai' : vehicle.status === 'maintenance' ? 'Servis' : 'Ready' }})
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Nama Pengemudi (Driver)</label>
                                <input v-model="approvalForm.driver_name" type="text" class="mt-1 block w-full rounded-xl border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white" placeholder="Kosongkan jika Tanpa Driver" />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Catatan Persetujuan</label>
                                <textarea v-model="approvalForm.approval_notes" rows="2" class="mt-1 block w-full rounded-xl border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white" placeholder="Contoh: Bensin disiapkan penuh."></textarea>
                            </div>

                            <button type="submit" :disabled="approvalForm.processing" class="w-full inline-flex justify-center items-center gap-2 rounded-xl bg-cyan-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-cyan-500 disabled:opacity-50">
                                Setujui Pengajuan
                            </button>
                        </form>

                        <!-- Rejection Form -->
                        <div class="border-t border-slate-200 pt-4 dark:border-slate-800">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Tolak Pengajuan</label>
                            <textarea v-model="rejectForm.approval_notes" rows="2" required class="block w-full rounded-xl border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white" placeholder="Alasan penolakan pengajuan..."></textarea>
                            
                            <button @click="submitRejection" :disabled="rejectForm.processing" class="mt-2 w-full inline-flex justify-center items-center gap-2 rounded-xl bg-red-50/50 px-4 py-2 text-sm font-bold text-red-600 hover:bg-red-100 disabled:opacity-50">
                                Tolak Pengajuan
                            </button>
                        </div>
                    </div>

                    <!-- 2. APPROVED State: Waiting to start trip -->
                    <div v-if="booking.status === 'approved'" class="glass-card rounded-3xl p-6 shadow-sm border border-cyan-500/25 bg-cyan-500/5">
                        <h3 class="mb-4 text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                            <TruckIcon class="h-5 w-5 text-cyan-600" />
                            Mulai Perjalanan
                        </h3>
                        <p class="text-xs text-slate-500 mb-4">Input kilometer awal odometer pada dasbor fisik kendaraan sebelum memulai perjalanan operasional.</p>

                        <form @submit.prevent="submitStart" class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Odometer Awal (KM) <span class="text-red-500">*</span></label>
                                <input v-model="startForm.odometer_start" type="number" required class="mt-1 block w-full rounded-xl border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white" placeholder="Contoh: 12050" />
                            </div>

                            <button type="submit" :disabled="startForm.processing" class="w-full inline-flex justify-center items-center gap-2 rounded-xl bg-cyan-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-cyan-500 disabled:opacity-50">
                                Mulai Perjalanan
                            </button>
                        </form>
                    </div>

                    <!-- 3. ACTIVE State: Trip ongoing, record logs to close trip -->
                    <div v-if="booking.status === 'active' && booking.trip" class="glass-card rounded-3xl p-6 shadow-sm border border-emerald-500/25 bg-emerald-500/5">
                        <h3 class="mb-4 text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                            <CheckCircleIcon class="h-5 w-5 text-emerald-600" />
                            Tutup Perjalanan
                        </h3>
                        <p class="text-xs text-slate-500 mb-4">Catat kilometer odometer akhir, pengeluaran bensin, biaya tol, serta foto struk pengeluaran untuk menutup peminjaman.</p>

                        <form @submit.prevent="submitComplete" class="space-y-4">
                            <div class="text-xs font-bold text-slate-500">Odometer Mulai: {{ booking.trip.odometer_start }} KM</div>
                            
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Odometer Akhir (KM) <span class="text-red-500">*</span></label>
                                <input v-model="completeForm.odometer_end" type="number" required class="mt-1 block w-full rounded-xl border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white" :placeholder="`Harus lebih dari ${booking.trip.odometer_start}`" />
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Bensin (Liter)</label>
                                    <input v-model="completeForm.fuel_liters" type="number" step="0.01" class="mt-1 block w-full rounded-xl border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white" />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Biaya Bensin (Rp)</label>
                                    <input v-model="completeForm.fuel_cost" type="number" class="mt-1 block w-full rounded-xl border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white" />
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Biaya Tol / Parkir (Rp)</label>
                                <input v-model="completeForm.toll_cost" type="number" class="mt-1 block w-full rounded-xl border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white" />
                            </div>

                            <!-- Photo Upload (toll/fuel receipt) -->
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Upload Foto Struk / Kuitansi</label>
                                <input type="file" @change="handleFileChange" accept="image/*" class="mt-1.5 block w-full text-xs text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-cyan-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-cyan-700 hover:file:bg-cyan-100" />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Catatan Perjalanan</label>
                                <textarea v-model="completeForm.notes" rows="2" class="mt-1 block w-full rounded-xl border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white" placeholder="Tuliskan jika ada kendala di jalan..."></textarea>
                            </div>

                            <button type="submit" :disabled="completeForm.processing" class="w-full inline-flex justify-center items-center gap-2 rounded-xl bg-emerald-650 bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-emerald-500 disabled:opacity-50">
                                Selesaikan Perjalanan
                            </button>
                        </form>
                    </div>

                    <!-- 4. REJECTED / CANCELLED / COMPLETED States: Status Information -->
                    <div v-if="['completed', 'rejected', 'cancelled'].includes(booking.status)" class="glass-card rounded-3xl p-6 shadow-sm text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-50 dark:bg-slate-800">
                            <CheckCircleIcon v-if="booking.status === 'completed'" class="h-6 w-6 text-emerald-600" />
                            <XCircleIcon v-else class="h-6 w-6 text-red-500" />
                        </div>
                        <h3 class="mt-3 text-sm font-bold text-slate-900 dark:text-white">Peminjaman Selesai</h3>
                        <p class="mt-1 text-xs text-slate-500">
                            Pengajuan ini berstatus <span class="font-bold uppercase">{{ booking.status }}</span>. Tidak ada tindakan lebih lanjut yang diperlukan.
                        </p>
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>
