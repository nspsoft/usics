<script setup>
import { ref, computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    CalendarIcon, 
    MapPinIcon, 
    ClockIcon, 
    PlusIcon, 
    CheckCircleIcon, 
    ArrowRightOnRectangleIcon, 
    ExclamationTriangleIcon,
    ChatBubbleLeftEllipsisIcon,
    UserIcon,
    TrashIcon,
    PencilIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    visits: Object,
    customers: Array,
    leads: Array,
    salesList: Array,
    canViewAll: Boolean,
    filters: Object,
    title: String
});

// State
const showScheduleModal = ref(false);
const showCheckOutModal = ref(false);
const selectedVisitForCheckOut = ref(null);
const isLocating = ref(false);
const locationError = ref('');

// Forms
const scheduleForm = useForm({
    client_type: 'customer', // customer or lead
    customer_id: '',
    lead_id: '',
    purpose: '',
    notes: '',
    planned_at: '',
});

const checkOutForm = useForm({
    latitude: '',
    longitude: '',
    address: '',
    summary: '',
});

// Filters form
const filterForm = ref({
    status: props.filters.status || '',
    sales_id: props.filters.sales_id || '',
});

const applyFilters = () => {
    router.get(route('crm.visits.index'), filterForm.value, {
        preserveState: true,
        replace: true
    });
};

const clearFilters = () => {
    filterForm.value.status = '';
    filterForm.value.sales_id = '';
    applyFilters();
};

const submitSchedule = () => {
    scheduleForm.post(route('crm.visits.store'), {
        onSuccess: () => {
            showScheduleModal.value = false;
            scheduleForm.reset();
        }
    });
};

// Geolocation helper
const getGPSCoordinates = () => {
    return new Promise((resolve, reject) => {
        if (!navigator.geolocation) {
            reject(new Error('Browser Anda tidak mendukung geolokasi GPS.'));
            return;
        }
        navigator.geolocation.getCurrentPosition(
            (position) => resolve(position.coords),
            (error) => {
                let msg = 'Gagal mengakses GPS.';
                if (error.code === error.PERMISSION_DENIED) {
                    msg = 'Izin lokasi GPS ditolak oleh pengguna.';
                }
                reject(new Error(msg));
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    });
};

// Reverse geocode from lat/lng using OSM Nominatim
const getAddressFromCoordinates = async (lat, lng) => {
    try {
        const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`);
        const data = await response.json();
        return data.display_name || `Koordinat: ${lat}, ${lng}`;
    } catch (e) {
        console.error('Reverse geocode error:', e);
        return `Koordinat: ${lat}, ${lng}`;
    }
};

// Check-in logic
const handleCheckIn = async (visit) => {
    if (confirm(`Apakah Anda yakin ingin Check-In untuk kunjungan ke ${visit.customer_name || visit.lead_name}?`)) {
        isLocating.value = true;
        locationError.value = '';
        try {
            const coords = await getGPSCoordinates();
            const address = await getAddressFromCoordinates(coords.latitude, coords.longitude);
            
            router.post(route('crm.visits.check-in', visit.id), {
                latitude: coords.latitude,
                longitude: coords.longitude,
                address: address
            }, {
                onFinish: () => {
                    isLocating.value = false;
                }
            });
        } catch (error) {
            isLocating.value = false;
            locationError.value = error.message;
            alert(error.message);
        }
    }
};

// Check-out modal trigger
const openCheckOutModal = (visit) => {
    selectedVisitForCheckOut.value = visit;
    checkOutForm.reset();
    checkOutForm.summary = '';
    showCheckOutModal.value = true;
};

// Submit Check-out
const submitCheckOut = async () => {
    isLocating.value = true;
    locationError.value = '';
    try {
        const coords = await getGPSCoordinates();
        const address = await getAddressFromCoordinates(coords.latitude, coords.longitude);
        
        checkOutForm.latitude = coords.latitude;
        checkOutForm.longitude = coords.longitude;
        checkOutForm.address = address;
        
        checkOutForm.post(route('crm.visits.check-out', selectedVisitForCheckOut.value.id), {
            onSuccess: () => {
                showCheckOutModal.value = false;
                selectedVisitForCheckOut.value = null;
            },
            onFinish: () => {
                isLocating.value = false;
            }
        });
    } catch (error) {
        isLocating.value = false;
        locationError.value = error.message;
        alert(error.message);
    }
};

const deleteVisit = (visit) => {
    if (confirm('Apakah Anda yakin ingin menghapus jadwal kunjungan ini?')) {
        router.delete(route('crm.visits.destroy', visit.id));
    }
};

// Helpers for badges
const getStatusBadgeClass = (status) => {
    switch (status) {
        case 'completed':
            return 'bg-emerald-950/40 text-emerald-400 border border-emerald-500/20';
        case 'checked_in':
            return 'bg-amber-950/40 text-amber-400 border border-amber-500/20 animate-pulse';
        case 'planned':
            return 'bg-blue-950/40 text-blue-400 border border-blue-500/20';
        case 'cancelled':
            return 'bg-slate-800 text-slate-400 border border-slate-700';
        default:
            return 'bg-slate-900 text-slate-300';
    }
};

const getStatusLabel = (status) => {
    switch (status) {
        case 'completed': return 'Selesai';
        case 'checked_in': return 'Sedang Kunjungan';
        case 'planned': return 'Terencana';
        case 'cancelled': return 'Dibatalkan';
        default: return status;
    }
};
</script>

<template>
    <Head :title="title" />

    <AppLayout>
        <div class="py-6 px-4 sm:px-6 lg:px-8 bg-slate-950 min-h-screen text-slate-100">
            <!-- Header Panel -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8 gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
                        <MapPinIcon class="h-8 w-8 text-blue-500" />
                        Sales Visits Tracking
                    </h1>
                    <p class="text-sm text-slate-400 mt-1">
                        Jadwalkan, lakukan check-in GPS real-time di lokasi pelanggan, dan buat laporan kunjungan sales Anda.
                    </p>
                </div>
                <div class="flex gap-3">
                    <Link 
                        :href="route('crm.visits.map')"
                        class="px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-700 text-sm font-bold text-slate-200 transition-all shadow-md flex items-center gap-2"
                    >
                        <MapPinIcon class="h-4 w-4 text-emerald-400" />
                        Lihat Peta Kunjungan
                    </Link>
                    <button 
                        @click="showScheduleModal = true"
                        class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-sm font-bold text-white transition-all shadow-lg shadow-blue-500/20 flex items-center gap-2"
                    >
                        <PlusIcon class="h-5 w-5" />
                        Jadwalkan Kunjungan
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-slate-900/60 backdrop-blur border border-slate-800 rounded-2xl p-4 mb-6 flex flex-wrap items-end gap-4">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Status</label>
                    <select 
                        v-model="filterForm.status" 
                        class="w-full bg-slate-950 border-slate-800 rounded-xl px-3 py-2 text-sm text-white focus:ring-blue-500/50"
                    >
                        <option value="">Semua Status</option>
                        <option value="planned">Terencana</option>
                        <option value="checked_in">Sedang Aktif (Checked In)</option>
                        <option value="completed">Selesai</option>
                        <option value="cancelled">Dibatalkan</option>
                    </select>
                </div>

                <div v-if="canViewAll" class="flex-1 min-w-[200px]">
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Salesperson</label>
                    <select 
                        v-model="filterForm.sales_id" 
                        class="w-full bg-slate-950 border-slate-800 rounded-xl px-3 py-2 text-sm text-white focus:ring-blue-500/50"
                    >
                        <option value="">Semua Sales</option>
                        <option v-for="sales in salesList" :key="sales.id" :value="sales.id">
                            {{ sales.name }}
                        </option>
                    </select>
                </div>

                <div class="flex gap-2">
                    <button 
                        @click="applyFilters"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-sm font-bold transition-all shadow-md"
                    >
                        Filter
                    </button>
                    <button 
                        @click="clearFilters"
                        class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-sm font-bold transition-all border border-slate-700"
                    >
                        Reset
                    </button>
                </div>
            </div>

            <!-- GPS Loading Overlay -->
            <div v-if="isLocating" class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/80 backdrop-blur-sm">
                <div class="bg-slate-900 border border-slate-800 p-8 rounded-2xl shadow-2xl flex flex-col items-center max-w-sm text-center">
                    <div class="w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mb-4"></div>
                    <h3 class="text-lg font-bold text-white mb-2">Mengakses GPS Perangkat</h3>
                    <p class="text-sm text-slate-400">Harap izinkan akses lokasi jika browser meminta. Kami sedang mengambil titik koordinat GPS Anda secara akurat...</p>
                </div>
            </div>

            <!-- Visits List -->
            <div v-if="visits.length === 0" class="bg-slate-900/40 border border-slate-800 rounded-2xl p-12 text-center">
                <CalendarIcon class="h-12 w-12 text-slate-600 mx-auto mb-4" />
                <h3 class="text-lg font-medium text-white mb-1">Belum ada kunjungan sales</h3>
                <p class="text-sm text-slate-400">Mulai dengan menjadwalkan kunjungan pertama ke customer atau lead Anda.</p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                <div 
                    v-for="visit in visits" 
                    :key="visit.id"
                    class="bg-slate-900 border border-slate-800/80 rounded-2xl shadow-lg hover:border-slate-700/60 transition-all flex flex-col overflow-hidden"
                >
                    <!-- Status Header -->
                    <div class="px-5 py-3.5 bg-slate-950/50 border-b border-slate-800/60 flex items-center justify-between">
                        <span :class="['px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider', getStatusBadgeClass(visit.status)]">
                            {{ getStatusLabel(visit.status) }}
                        </span>
                        
                        <div class="flex items-center gap-2 text-xs text-slate-500">
                            <ClockIcon class="h-4 w-4 text-slate-400" />
                            {{ visit.planned_at }}
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="p-5 flex-1 flex flex-col justify-between">
                        <div>
                            <!-- Client Info -->
                            <div class="mb-4">
                                <h3 class="text-lg font-bold text-white line-clamp-1 mb-1">
                                    {{ visit.customer_name || visit.lead_name }}
                                </h3>
                                <p class="text-xs font-bold text-blue-400 uppercase tracking-wider flex items-center gap-1.5">
                                    <span v-if="visit.customer_id" class="px-1.5 py-0.5 rounded bg-blue-500/10 border border-blue-500/20 text-[10px]">Customer</span>
                                    <span v-else class="px-1.5 py-0.5 rounded bg-orange-500/10 border border-orange-500/20 text-[10px]">Lead ({{ visit.lead_company || 'Lead' }})</span>
                                </p>
                            </div>

                            <!-- Purpose / Notes -->
                            <div class="space-y-3 text-sm mb-4">
                                <div class="bg-slate-950/40 border border-slate-800/40 rounded-xl p-3">
                                    <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Tujuan Kunjungan</span>
                                    <p class="text-slate-200 font-semibold leading-relaxed">{{ visit.purpose }}</p>
                                </div>

                                <div v-if="visit.notes" class="text-xs text-slate-400 bg-slate-950/20 p-2.5 rounded-lg border border-slate-800/20">
                                    <span class="font-bold text-slate-500 block mb-1">Catatan Internal:</span>
                                    {{ visit.notes }}
                                </div>
                            </div>

                            <!-- Check-in & Check-out Data -->
                            <div v-if="visit.check_in_at" class="border-t border-slate-800/60 pt-4 mt-4 space-y-3 text-xs">
                                <div>
                                    <span class="font-bold text-slate-400 flex items-center gap-1">
                                        <CheckCircleIcon class="h-3.5 w-3.5 text-emerald-400" />
                                        Check-In: {{ visit.check_in_at }}
                                    </span>
                                    <p class="text-[11px] text-slate-500 mt-1 line-clamp-2" :title="visit.check_in_address">
                                        📍 {{ visit.check_in_address }}
                                    </p>
                                </div>

                                <div v-if="visit.check_out_at">
                                    <span class="font-bold text-slate-400 flex items-center gap-1">
                                        <CheckCircleIcon class="h-3.5 w-3.5 text-blue-400" />
                                        Check-Out: {{ visit.check_out_at }}
                                    </span>
                                    <p class="text-[11px] text-slate-500 mt-1 line-clamp-2" :title="visit.check_out_address">
                                        📍 {{ visit.check_out_address }}
                                    </p>
                                </div>

                                <div v-if="visit.summary" class="bg-emerald-950/20 border border-emerald-500/10 p-2.5 rounded-xl mt-2">
                                    <span class="font-bold text-emerald-400 block mb-1">Laporan Hasil Kunjungan:</span>
                                    <p class="text-slate-300 leading-relaxed italic">"{{ visit.summary }}"</p>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Details / Actions -->
                        <div class="border-t border-slate-800/60 pt-4 mt-5 flex items-center justify-between">
                            <div class="flex items-center gap-1.5 text-xs text-slate-400">
                                <UserIcon class="h-4 w-4 text-slate-500" />
                                <span>Sales: <b>{{ visit.sales_name }}</b></span>
                            </div>

                            <div class="flex gap-2">
                                <!-- Check-in button -->
                                <button 
                                    v-if="visit.status === 'planned'"
                                    @click="handleCheckIn(visit)"
                                    class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold transition-all shadow-md shadow-emerald-500/10 flex items-center gap-1"
                                >
                                    <CheckCircleIcon class="h-4 w-4" />
                                    Check In GPS
                                </button>

                                <!-- Check-out button -->
                                <button 
                                    v-if="visit.status === 'checked_in'"
                                    @click="openCheckOutModal(visit)"
                                    class="px-4 py-1.5 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-xs font-bold transition-all shadow-md shadow-blue-500/10 flex items-center gap-1"
                                >
                                    <ArrowRightOnRectangleIcon class="h-4 w-4" />
                                    Check Out GPS
                                </button>

                                <!-- Delete button (for planned/cancelled) -->
                                <button 
                                    v-if="visit.status === 'planned' || visit.status === 'cancelled'"
                                    @click="deleteVisit(visit)"
                                    class="p-1.5 bg-slate-800 hover:bg-red-950/50 hover:text-red-400 text-slate-400 rounded-lg transition-all border border-slate-700 hover:border-red-500/20"
                                    title="Hapus Kunjungan"
                                >
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination (if applicable) -->
            <div v-if="visits.links && visits.links.length > 3" class="mt-6 flex justify-center">
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                    <Link 
                        v-for="(link, k) in visits.links" 
                        :key="k"
                        :href="link.url || '#'"
                        v-html="link.label"
                        :class="[
                            'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                            link.active ? 'bg-blue-600 border-blue-600 text-white z-10' : 'bg-slate-900 border-slate-800 text-slate-400 hover:bg-slate-800',
                            !link.url ? 'opacity-50 cursor-not-allowed' : ''
                        ]"
                    />
                </nav>
            </div>
        </div>

        <!-- MODAL: SCHEDULE NEW VISIT -->
        <div v-if="showScheduleModal" class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0 flex items-center justify-center">
            <div class="fixed inset-0 transform transition-all" @click="showScheduleModal = false">
                <div class="absolute inset-0 bg-slate-950/75 backdrop-blur-sm"></div>
            </div>

            <div class="relative bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden transform transition-all p-6 text-slate-100">
                <h3 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                    <CalendarIcon class="h-6 w-6 text-blue-400" />
                    Jadwalkan Kunjungan Baru
                </h3>

                <form @submit.prevent="submitSchedule" class="space-y-4">
                    <!-- Client Type Selection -->
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Tipe Target</label>
                        <div class="grid grid-cols-2 gap-3">
                            <button 
                                type="button"
                                @click="scheduleForm.client_type = 'customer'"
                                :class="['py-2 px-4 rounded-xl border text-sm font-bold transition-all', scheduleForm.client_type === 'customer' ? 'bg-blue-600 text-white border-blue-500' : 'bg-slate-950 border-slate-800 text-slate-400 hover:bg-slate-800']"
                            >
                                Customer (Pelanggan)
                            </button>
                            <button 
                                type="button"
                                @click="scheduleForm.client_type = 'lead'"
                                :class="['py-2 px-4 rounded-xl border text-sm font-bold transition-all', scheduleForm.client_type === 'lead' ? 'bg-blue-600 text-white border-blue-500' : 'bg-slate-950 border-slate-800 text-slate-400 hover:bg-slate-800']"
                            >
                                Lead (Calon Pelanggan)
                            </button>
                        </div>
                    </div>

                    <!-- Client Select -->
                    <div v-if="scheduleForm.client_type === 'customer'">
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Pilih Customer</label>
                        <select 
                            v-model="scheduleForm.customer_id"
                            class="w-full bg-slate-950 border-slate-800 rounded-xl px-3 py-2.5 text-sm text-white focus:ring-blue-500/50"
                            required
                        >
                            <option value="">-- Pilih Customer --</option>
                            <option v-for="c in customers" :key="c.id" :value="c.id">
                                {{ c.name }} ({{ c.city || 'No City' }})
                            </option>
                        </select>
                    </div>

                    <div v-else>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Pilih Lead</label>
                        <select 
                            v-model="scheduleForm.lead_id"
                            class="w-full bg-slate-950 border-slate-800 rounded-xl px-3 py-2.5 text-sm text-white focus:ring-blue-500/50"
                            required
                        >
                            <option value="">-- Pilih Lead --</option>
                            <option v-for="l in leads" :key="l.id" :value="l.id">
                                {{ l.name }} {{ l.company ? `(${l.company})` : '' }}
                            </option>
                        </select>
                    </div>

                    <!-- Purpose -->
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Tujuan Kunjungan</label>
                        <input 
                            v-model="scheduleForm.purpose" 
                            type="text" 
                            placeholder="Contoh: Demo Produk, Negosiasi Kontrak, dsb..." 
                            class="w-full bg-slate-950 border-slate-800 rounded-xl px-3 py-2.5 text-sm text-white focus:ring-blue-500/50"
                            required
                        />
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Catatan Internal / Agenda</label>
                        <textarea 
                            v-model="scheduleForm.notes" 
                            rows="3" 
                            placeholder="Tulis poin-poin bahasan atau detail rencana kunjungan..."
                            class="w-full bg-slate-950 border-slate-800 rounded-xl px-3 py-2.5 text-sm text-white focus:ring-blue-500/50"
                        ></textarea>
                    </div>

                    <!-- Date & Time -->
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Waktu Kunjungan</label>
                        <input 
                            v-model="scheduleForm.planned_at" 
                            type="datetime-local" 
                            class="w-full bg-slate-950 border-slate-800 rounded-xl px-3 py-2.5 text-sm text-white focus:ring-blue-500/50"
                            required
                        />
                    </div>

                    <!-- Footer actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-800">
                        <button 
                            type="button" 
                            @click="showScheduleModal = false"
                            class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 hover:text-white font-bold transition-all"
                        >
                            Batal
                        </button>
                        <button 
                            type="submit" 
                            :disabled="scheduleForm.processing"
                            class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold transition-all shadow-md shadow-blue-500/20 disabled:opacity-50"
                        >
                            Jadwalkan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: CHECK-OUT WITH VISIT REPORT -->
        <div v-if="showCheckOutModal" class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0 flex items-center justify-center">
            <div class="fixed inset-0 transform transition-all" @click="showCheckOutModal = false">
                <div class="absolute inset-0 bg-slate-950/75 backdrop-blur-sm"></div>
            </div>

            <div class="relative bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden transform transition-all p-6 text-slate-100">
                <h3 class="text-xl font-bold text-white mb-2 flex items-center gap-2">
                    <ArrowRightOnRectangleIcon class="h-6 w-6 text-blue-400" />
                    Check-Out Kunjungan Sales
                </h3>
                <p class="text-xs text-slate-400 mb-4">
                    Anda akan check-out secara GPS. Harap isi ringkasan laporan hasil pertemuan di bawah ini sebelum mengakhiri kunjungan.
                </p>

                <form @submit.prevent="submitCheckOut" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Laporan Pertemuan (Summary)</label>
                        <textarea 
                            v-model="checkOutForm.summary" 
                            rows="5" 
                            placeholder="Tulis hasil pertemuan, kesepakatan, kendala lapangan, atau tindak lanjut berikutnya secara lengkap..."
                            class="w-full bg-slate-950 border-slate-800 rounded-xl px-3 py-2.5 text-sm text-white focus:ring-blue-500/50"
                            required
                        ></textarea>
                        <p class="text-[10px] text-slate-500 mt-1">Minimal 5 karakter laporan.</p>
                    </div>

                    <!-- Footer actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-800">
                        <button 
                            type="button" 
                            @click="showCheckOutModal = false"
                            class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 hover:text-white font-bold transition-all"
                        >
                            Batal
                        </button>
                        <button 
                            type="submit" 
                            :disabled="checkOutForm.processing || checkOutForm.summary.length < 5"
                            class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold transition-all shadow-md shadow-blue-500/20 disabled:opacity-50"
                        >
                            Ambil GPS & Check-Out
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
