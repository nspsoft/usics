<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatNumber, formatDate } from '@/helpers';
import { 
    TruckIcon, 
    PlusIcon, 
    MagnifyingGlassIcon,
    PencilSquareIcon,
    TrashIcon,
    ChevronUpDownIcon,
    FunnelIcon,
    TagIcon,
    EyeIcon,
    UserIcon,
} from '@heroicons/vue/24/outline';
import { CheckBadgeIcon, ShieldCheckIcon } from '@heroicons/vue/20/solid';

const props = defineProps({
    vehicles: Object,
    filters: Object,
    vehicleStatuses: Array
});

const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');

const showModal = ref(false);
const editingVehicle = ref(null);

const form = useForm({
    license_plate: '',
    traccar_device_id: null,
    vehicle_type: '',
    brand: '',
    driver_name: '',
    status: 'available',
    notes: '',
    is_active: true,
    stnk_number: '',
    stnk_expiry: '',
    kir_number: '',
    kir_expiry: '',
    driver_photo: null,
    vehicle_photo: null,
    model: '',
    year: null,
});

const openCreateModal = () => {
    editingVehicle.value = null;
    form.reset();
    loadTraccarDevices();
    showModal.value = true;
};

const openEditModal = (vehicle) => {
    editingVehicle.value = vehicle;
    form.clearErrors();
    form.license_plate = vehicle.license_plate;
    form.traccar_device_id = vehicle.traccar_device_id;
    form.vehicle_type = vehicle.vehicle_type;
    form.brand = vehicle.brand;
    form.driver_name = vehicle.driver_name;
    form.status = vehicle.status;
    form.notes = vehicle.notes;
    form.is_active = vehicle.is_active ? true : false;
    form.stnk_number = vehicle.stnk_number;
    form.stnk_expiry = vehicle.stnk_expiry;
    form.kir_number = vehicle.kir_number;
    form.kir_expiry = vehicle.kir_expiry;
    form.model = vehicle.model;
    form.year = vehicle.year;
    form.driver_photo = null;
    form.vehicle_photo = null;
    loadTraccarDevices();
    showModal.value = true;
};

const traccarDevices = ref([]);
const traccarLoading = ref(false);
const traccarError = ref(null);

const loadTraccarDevices = async () => {
    traccarError.value = null;
    traccarLoading.value = true;

    try {
        const response = await axios.get(route('logistics.traccar.devices'));
        traccarDevices.value = response.data?.data || [];
    } catch (error) {
        traccarDevices.value = [];
        traccarError.value = error.response?.data?.message || error.message;
    } finally {
        traccarLoading.value = false;
    }
};

const submit = () => {
    if (editingVehicle.value) {
        form.transform((data) => ({
            ...data,
            _method: 'put',
        })).post(route('ga.fleet.update', editingVehicle.value.id), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('ga.fleet.store'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const deleteVehicle = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus kendaraan ini dari armada GA?')) {
        form.delete(route('ga.fleet.destroy', id));
    }
};

const getStatusColor = (status) => {
    switch (status) {
        case 'available': return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400';
        case 'busy': return 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400';
        case 'in_use': return 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400';
        case 'maintenance': return 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400';
        default: return 'bg-slate-100 text-slate-700 dark:bg-slate-500/10 dark:text-slate-400';
    }
};

const isExpired = (date) => {
    if (!date) return false;
    return new Date(date) < new Date();
};

const isNearExpiry = (date) => {
    if (!date) return false;
    const expiry = new Date(date);
    const today = new Date();
    const diffTime = expiry - today;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays > 0 && diffDays <= 30; // 30 days warning
};
</script>

<template>
    <Head title="Armada Operasional (GA)" />

    <AppLayout title="Armada Operasional GA">
        <div class="sm:flex sm:items-center sm:justify-between mb-8">
            <div>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400 font-medium uppercase tracking-widest">Master Data Kendaraan Operasional</p>
            </div>
            <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none flex items-center gap-3">
                <button
                    @click="openCreateModal"
                    class="group relative inline-flex items-center gap-2 rounded-xl bg-cyan-600 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-cyan-500/20 transition-all hover:bg-cyan-500 hover:shadow-cyan-500/40 active:scale-95"
                >
                    <PlusIcon class="h-5 w-5" />
                    Tambah Kendaraan
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="md:col-span-2 relative group">
                <MagnifyingGlassIcon class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400 group-focus-within:text-cyan-500 transition-colors" />
                <input
                    v-model="search"
                    type="text"
                    placeholder="Cari No Polisi, Driver, atau Tipe..."
                    class="w-full bg-white dark:bg-slate-900 border-0 ring-1 ring-slate-200 dark:ring-slate-800 rounded-xl pl-12 pr-4 py-3 text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-cyan-500 transition-all shadow-sm"
                />
            </div>
            <div class="relative group">
                <FunnelIcon class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400 group-focus-within:text-cyan-500 transition-colors" />
                <select
                    v-model="statusFilter"
                    class="w-full bg-white dark:bg-slate-900 border-0 ring-1 ring-slate-200 dark:ring-slate-800 rounded-xl pl-12 pr-4 py-3 text-slate-900 dark:text-white focus:ring-2 focus:ring-cyan-500 transition-all shadow-sm appearance-none"
                >
                    <option value="">Semua Status</option>
                    <option v-for="s in vehicleStatuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
            </div>
        </div>

        <!-- Vehicles Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div 
                v-for="vehicle in vehicles.data" 
                :key="vehicle.id"
                class="group relative bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden transition-all hover:shadow-xl hover:border-cyan-500/50"
            >
                <!-- Card Header (Large Vehicle Photo) -->
                <div class="relative h-48 bg-slate-100 dark:bg-slate-800 flex items-center justify-center overflow-hidden">
                    <img 
                        v-if="vehicle.vehicle_photo_url" 
                        :src="vehicle.vehicle_photo_url" 
                        class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" 
                    />
                    <div v-else class="flex flex-col items-center gap-2">
                        <TruckIcon class="h-16 w-16 text-slate-300 dark:text-slate-700" />
                        <span class="text-xs text-slate-400 uppercase font-black tracking-widest">{{ vehicle.vehicle_type || 'Operasional' }}</span>
                    </div>
                    
                    <!-- Status Badge Floating -->
                    <div class="absolute top-3 right-3">
                        <span 
                            class="px-3 py-1 text-[9px] font-black rounded-full uppercase tracking-[0.15em] leading-none flex items-center gap-1.5 shadow-2xl backdrop-blur-xl border border-white/30"
                            :class="getStatusColor(vehicle.status)"
                        >
                            {{ vehicle.status }}
                        </span>
                    </div>

                    <!-- Driver Photo -->
                    <div class="absolute bottom-3 right-3 group/driver">
                        <div class="w-16 h-16 rounded-2xl overflow-hidden ring-4 ring-white dark:ring-slate-900 shadow-2xl bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700">
                            <img 
                                v-if="vehicle.driver_photo_url" 
                                :src="vehicle.driver_photo_url" 
                                class="w-full h-full object-cover" 
                            />
                            <div v-else class="w-full h-full bg-slate-50 dark:bg-slate-900 flex items-center justify-center">
                                <UserIcon class="w-6 h-6 text-slate-300" />
                            </div>
                        </div>
                        <div class="absolute -bottom-1 -right-1 w-6 h-6 bg-emerald-500 rounded-full border-2 border-white dark:border-slate-900 flex items-center justify-center shadow-lg" v-if="vehicle.driver_name">
                            <CheckBadgeIcon class="w-3.5 h-3.5 text-white" />
                        </div>
                    </div>
                </div>

                <!-- Compact Info Section -->
                <div class="p-4 pt-4">
                    <div class="mb-3">
                        <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tighter leading-none">{{ vehicle.license_plate }}</h3>
                        <p class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] mt-1">{{ vehicle.brand }} • {{ vehicle.model || vehicle.vehicle_type }}</p>
                    </div>

                    <!-- Tech Tags (Very Compact) -->
                    <div class="flex flex-wrap gap-1.5 mb-4">
                        <div 
                            class="flex items-center gap-1 px-2 py-0.5 rounded text-[9px] font-black border uppercase"
                            :class="isExpired(vehicle.stnk_expiry) ? 'bg-red-50 border-red-100 text-red-600' : (isNearExpiry(vehicle.stnk_expiry) ? 'bg-amber-50 border-amber-100 text-amber-600' : 'bg-slate-50 border-slate-100 text-slate-500')"
                        >
                            STNK: {{ formatDate(vehicle.stnk_expiry) }}
                        </div>
                        <div v-if="vehicle.kir_expiry" 
                            class="flex items-center gap-1 px-2 py-0.5 rounded text-[9px] font-black border uppercase"
                            :class="isExpired(vehicle.kir_expiry) ? 'bg-red-50 border-red-100 text-red-600' : (isNearExpiry(vehicle.kir_expiry) ? 'bg-amber-50 border-amber-100 text-amber-600' : 'bg-slate-50 border-slate-100 text-slate-500')"
                        >
                            KIR: {{ formatDate(vehicle.kir_expiry) }}
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 border-t border-slate-50 dark:border-slate-800/50 pt-3">
                        <div class="flex flex-col">
                            <span class="text-[8px] font-black uppercase tracking-widest text-slate-400">Driver Utama</span>
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300 truncate">{{ vehicle.driver_name || 'No Assign' }}</span>
                        </div>
                        <div class="flex flex-col text-right">
                            <span class="text-[8px] font-black uppercase tracking-widest text-slate-400">Tahun</span>
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ vehicle.year || '-' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Card Actions -->
                <div class="flex items-center border-t border-slate-100 dark:border-slate-800 divide-x divide-slate-100 dark:divide-slate-800">
                    <Link 
                        :href="route('ga.fleet.show', vehicle.id)"
                        class="flex-1 py-3 text-sm font-black text-cyan-600 dark:text-cyan-400 hover:bg-cyan-50 dark:hover:bg-cyan-500/10 transition-all flex items-center justify-center gap-2"
                    >
                        <EyeIcon class="w-4 h-4" />
                        Detail
                    </Link>
                    <button 
                        @click="openEditModal(vehicle)"
                        class="flex-1 py-3 text-sm font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 hover:text-cyan-500 transition-all flex items-center justify-center gap-2"
                    >
                        <PencilSquareIcon class="w-4 h-4" />
                        Edit
                    </button>
                    <button 
                        @click="deleteVehicle(vehicle.id)"
                        class="flex-1 py-3 text-sm font-bold text-slate-600 dark:text-slate-400 hover:bg-red-50 dark:hover:bg-red-500/10 hover:text-red-500 transition-all flex items-center justify-center gap-2"
                    >
                        <TrashIcon class="w-4 h-4" />
                        Hapus
                    </button>
                </div>
            </div>
        </div>

        <!-- Zero State -->
        <div v-if="vehicles.data.length === 0" class="mt-12 text-center p-12 bg-white dark:bg-slate-900 rounded-3xl border border-dashed border-slate-300 dark:border-slate-700">
            <TruckIcon class="mx-auto h-12 w-12 text-slate-400" />
            <h3 class="mt-2 text-sm font-semibold text-slate-900 dark:text-white">Tidak ada kendaraan operasional</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Mulai dengan menambahkan kendaraan operasional baru (mobil/motor).</p>
        </div>

        <!-- Create/Edit Modal -->
        <div v-if="showModal" class="fixed inset-0 z-[60] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div @click="closeModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity" />
                
                <div class="relative w-full max-w-2xl rounded-3xl bg-white dark:bg-slate-900 shadow-2xl overflow-hidden">
                    <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/50 flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-bold text-slate-900 dark:text-white">{{ editingVehicle ? 'Edit Kendaraan Operasional' : 'Tambah Kendaraan Operasional Baru' }}</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 uppercase tracking-widest font-bold">Informasi Detail Armada GA</p>
                        </div>
                        <button @click="closeModal" class="text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors">
                            <PlusIcon class="h-6 w-6 rotate-45" />
                        </button>
                    </div>

                    <form @submit.prevent="submit" class="p-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">No Polisi (License Plate)</label>
                                <input v-model="form.license_plate" type="text" class="w-full bg-slate-50 dark:bg-slate-800 border-0 ring-1 ring-slate-200 dark:ring-slate-700 rounded-xl px-4 py-3 shadow-inner focus:ring-2 focus:ring-cyan-500 transition-all font-bold uppercase" placeholder="B 1234 ABC" required />
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">Traccar Device (GPS Tracking)</label>
                                <select v-model="form.traccar_device_id" class="w-full bg-slate-50 dark:bg-slate-800 border-0 ring-1 ring-slate-200 dark:ring-slate-700 rounded-xl px-4 py-3 shadow-inner focus:ring-2 focus:ring-cyan-500 transition-all">
                                    <option :value="null">- Tidak di-link -</option>
                                    <option v-for="d in traccarDevices" :key="d.id" :value="d.id">
                                        {{ d.name }}{{ d.uniqueId ? ` (${d.uniqueId})` : '' }}
                                    </option>
                                </select>
                                <div v-if="traccarLoading" class="text-[11px] font-bold text-slate-500">Loading devices...</div>
                                <div v-else-if="traccarError" class="text-[11px] font-bold text-red-600">{{ traccarError }}</div>
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">Tipe Kendaraan</label>
                                <select v-model="form.vehicle_type" class="w-full bg-slate-50 dark:bg-slate-800 border-0 ring-1 ring-slate-200 dark:ring-slate-700 rounded-xl px-4 py-3 shadow-inner focus:ring-2 focus:ring-cyan-500 transition-all" required>
                                    <option value="">Pilih Tipe</option>
                                    <option value="Mobil Penumpang">Mobil Penumpang</option>
                                    <option value="Sepeda Motor">Sepeda Motor</option>
                                    <option value="Mobil Pick-up/Logistik">Mobil Pick-up/Logistik</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">Brand / Merk</label>
                                <input v-model="form.brand" type="text" class="w-full bg-slate-50 dark:bg-slate-800 border-0 ring-1 ring-slate-200 dark:ring-slate-700 rounded-xl px-4 py-3 shadow-inner focus:ring-2 focus:ring-cyan-500 transition-all font-medium" placeholder="Toyota / Honda" required />
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">Model / Seri</label>
                                <input v-model="form.model" type="text" class="w-full bg-slate-50 dark:bg-slate-800 border-0 ring-1 ring-slate-200 dark:ring-slate-700 rounded-xl px-4 py-3 shadow-inner focus:ring-2 focus:ring-cyan-500 transition-all font-medium" placeholder="Avanza / Vario" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">Tahun Pembuatan</label>
                                <input v-model="form.year" type="number" class="w-full bg-slate-50 dark:bg-slate-800 border-0 ring-1 ring-slate-200 dark:ring-slate-700 rounded-xl px-4 py-3 shadow-inner focus:ring-2 focus:ring-cyan-500 transition-all font-medium" placeholder="2022" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">Nama Driver Standard</label>
                                <input v-model="form.driver_name" type="text" class="w-full bg-slate-50 dark:bg-slate-800 border-0 ring-1 ring-slate-200 dark:ring-slate-700 rounded-xl px-4 py-3 shadow-inner focus:ring-2 focus:ring-cyan-500 transition-all font-medium" placeholder="Driver Tetap (Kosongkan jika umum)" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">Status Awal</label>
                                <select v-model="form.status" class="w-full bg-slate-50 dark:bg-slate-800 border-0 ring-1 ring-slate-200 dark:ring-slate-700 rounded-xl px-4 py-3 shadow-inner focus:ring-2 focus:ring-cyan-500 transition-all">
                                    <option v-for="s in vehicleStatuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                                </select>
                            </div>

                            <!-- Legal Details -->
                            <div class="md:col-span-2 mt-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-cyan-500 mb-4">Legalitas & Identitas Kendaraan</p>
                            </div>
                            
                            <div class="space-y-2">
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">Nomor STNK</label>
                                <input v-model="form.stnk_number" type="text" class="w-full bg-slate-50 dark:bg-slate-800 border-0 ring-1 ring-slate-200 dark:ring-slate-700 rounded-xl px-4 py-3 shadow-inner focus:ring-2 focus:ring-cyan-500 transition-all font-medium" placeholder="No. Registrasi STNK" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">STNK Berlaku S/D</label>
                                <input v-model="form.stnk_expiry" type="date" class="w-full bg-slate-50 dark:bg-slate-800 border-0 ring-1 ring-slate-200 dark:ring-slate-700 rounded-xl px-4 py-3 shadow-inner focus:ring-2 focus:ring-cyan-500 transition-all font-medium" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">Nomor KIR (Optional)</label>
                                <input v-model="form.kir_number" type="text" class="w-full bg-slate-50 dark:bg-slate-800 border-0 ring-1 ring-slate-200 dark:ring-slate-700 rounded-xl px-4 py-3 shadow-inner focus:ring-2 focus:ring-cyan-500 transition-all font-medium" placeholder="No. Uji KIR jika ada" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">KIR Berlaku S/D</label>
                                <input v-model="form.kir_expiry" type="date" class="w-full bg-slate-50 dark:bg-slate-800 border-0 ring-1 ring-slate-200 dark:ring-slate-700 rounded-xl px-4 py-3 shadow-inner focus:ring-2 focus:ring-cyan-500 transition-all font-medium" />
                            </div>

                            <!-- Photo Uploads -->
                            <div class="md:col-span-2 mt-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-cyan-500 mb-4">Upload Dokumentasi (Foto)</p>
                            </div>

                            <div class="space-y-2">
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">Foto Kendaraan</label>
                                <input type="file" @input="form.vehicle_photo = $event.target.files[0]" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-cyan-50 file:text-cyan-700 hover:file:bg-cyan-100 dark:file:bg-slate-800 dark:file:text-slate-300" />
                                <progress v-if="form.progress" :value="form.progress.percentage" max="100" class="w-full h-1 rounded overflow-hidden">
                                    {{ form.progress.percentage }}%
                                </progress>
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">Foto Supir Utama</label>
                                <input type="file" @input="form.driver_photo = $event.target.files[0]" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-cyan-50 file:text-cyan-700 hover:file:bg-cyan-100 dark:file:bg-slate-800 dark:file:text-slate-300" />
                            </div>
                        </div>

                        <div class="mt-8 flex items-center justify-end gap-3">
                            <button 
                                type="button" 
                                @click="closeModal"
                                class="px-6 py-2.5 text-sm font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-all"
                            >
                                Batal
                            </button>
                            <button 
                                type="submit"
                                :disabled="form.processing"
                                class="px-10 py-2.5 text-sm font-black text-white bg-cyan-600 hover:bg-cyan-500 rounded-xl shadow-lg shadow-cyan-500/20 active:scale-95 transition-all disabled:opacity-50"
                            >
                                {{ editingVehicle ? 'Simpan Perubahan' : 'Tambah Kendaraan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.glass-card {
    background: rgba(255, 255, 255, 0.02);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.05);
}
</style>
