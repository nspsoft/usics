<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatNumber, formatDate } from '@/helpers';
import { 
    PlusIcon, 
    TruckIcon, 
    PencilSquareIcon, 
    TrashIcon, 
    EyeIcon,
    FunnelIcon,
    MagnifyingGlassIcon,
    CheckBadgeIcon,
    ArrowUpTrayIcon,
    TagIcon,
    CheckCircleIcon
} from '@heroicons/vue/24/outline';
import axios from 'axios';

const props = defineProps({
    vehicles: Object,
    filters: Object,
});

const vehicleStatuses = [
    { value: 'available', label: 'Tersedia (Available)' },
    { value: 'busy', label: 'Sedang Jalan (Busy)' },
    { value: 'maintenance', label: 'Perbaikan (Maintenance)' },
];

const showModal = ref(false);
const showImportModal = ref(false);
const editingVehicle = ref(null);
const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');

// Keep track of search and filter changes
watch([search, statusFilter], ([newSearch, newStatus]) => {
    router.get(route('logistics.fleet.index'), {
        search: newSearch || undefined,
        status: newStatus || undefined
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
});

const form = useForm({
    license_plate: '',
    rfid_card: '',
    traccar_device_id: null,
    vehicle_type: '',
    brand: '',
    driver_name: '',
    capacity_weight: 0,
    capacity_volume: 0,
    status: 'available',
    stnk_number: '',
    stnk_expiry: '',
    kir_number: '',
    kir_expiry: '',
    driver_photo: null,
    vehicle_photo: null,
});

const importForm = useForm({
    file: null,
});

const openImportModal = () => {
    importForm.reset();
    showImportModal.value = true;
};

const closeImportModal = () => {
    showImportModal.value = false;
};

const submitImport = () => {
    if (!importForm.file) {
        alert('Pilih file Excel terlebih dahulu!');
        return;
    }
    importForm.post(route('logistics.fleet.import'), {
        preserveScroll: true,
        onSuccess: () => closeImportModal(),
    });
};

const openCreateModal = () => {
    editingVehicle.value = null;
    form.reset();
    loadTraccarDevices();
    showModal.value = true;
};

const openEditModal = (vehicle) => {
    editingVehicle.value = vehicle;
    form.license_plate = vehicle.license_plate;
    form.rfid_card = vehicle.rfid_card;
    form.traccar_device_id = vehicle.traccar_device_id;
    form.vehicle_type = vehicle.vehicle_type;
    form.brand = vehicle.brand;
    form.driver_name = vehicle.driver_name;
    form.capacity_weight = vehicle.capacity_weight;
    form.capacity_volume = vehicle.capacity_volume;
    form.status = vehicle.status;
    form.stnk_number = vehicle.stnk_number;
    form.stnk_expiry = vehicle.stnk_expiry;
    form.kir_number = vehicle.kir_number;
    form.kir_expiry = vehicle.kir_expiry;
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
        })).post(route('logistics.fleet.update', editingVehicle.value.id), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('logistics.fleet.store'), {
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
    if (confirm('Apakah Anda yakin ingin menghapus armada ini dari master data?')) {
        form.delete(route('logistics.fleet.destroy', id));
    }
};

const getStatusColor = (status) => {
    const isLight = isLightMode.value;
    switch (status) {
        case 'available': return isLight ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-emerald-500/10 border-transparent text-emerald-400';
        case 'busy': return isLight ? 'bg-blue-50 border-blue-200 text-blue-700' : 'bg-blue-500/10 border-transparent text-blue-400';
        case 'maintenance': return isLight ? 'bg-amber-50 border-amber-205 text-amber-700' : 'bg-amber-500/10 border-transparent text-amber-400';
        default: return isLight ? 'bg-slate-50 border-slate-200 text-slate-700' : 'bg-slate-500/10 border-transparent text-slate-400';
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
    return diffDays > 0 && diffDays <= 30;
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
    <Head title="Vehicle Fleet" />

    <AppLayout title="Vehicle Fleet" :render-header="false">
        <div class="min-h-screen bg-slate-50 dark:bg-[#050510] relative overflow-hidden font-mono text-slate-800 dark:text-cyan-50 selection:bg-indigo-500/30 transition-colors duration-300">
            
            <!-- Dynamic Background -->
            <div class="fixed inset-0 z-0 pointer-events-none">
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/5 to-slate-100 dark:from-indigo-955/20 dark:to-[#050510]"></div>
                 <div class="perspective-grid absolute inset-0 opacity-[0.05] dark:opacity-10"></div>
            </div>

            <div class="relative z-10 p-6 space-y-6 max-w-7xl mx-auto">
                
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-slate-200 dark:border-white/10 pb-4">
                    <div>
                         <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-0.5 text-[10px] bg-indigo-500/10 border border-indigo-500/20 rounded text-indigo-705 dark:text-indigo-400 tracking-[0.2em] uppercase font-bold">
                                Fleet Master
                            </span>
                            <span class="px-2 py-0.5 text-[10px] bg-slate-200 dark:bg-white/5 border border-slate-300 dark:border-white/10 rounded text-slate-500 dark:text-slate-400 tracking-[0.2em] uppercase">LOGISTICS.FLEET.V1</span>
                        </div>
                        <h1 class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 via-slate-800 to-indigo-700 dark:from-cyan-400 dark:via-white dark:to-cyan-400 tracking-widest uppercase dark:glow-text">
                            VEHICLE FLEET
                        </h1>
                    </div>
                    
                    <div class="flex flex-wrap items-center gap-3">
                        <a href="/logistics/fleet/template" class="px-3 py-2 text-xs font-bold text-slate-700 hover:text-blue-600 dark:text-slate-300 bg-white hover:bg-slate-100 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:border-transparent rounded-lg transition-colors cursor-pointer shadow-sm">
                            Template Excel
                        </a>
                        <a href="/logistics/fleet/export" class="px-3 py-2 text-xs font-bold text-slate-700 hover:text-emerald-600 dark:text-slate-300 bg-white hover:bg-slate-100 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:border-transparent rounded-lg transition-colors cursor-pointer shadow-sm">
                            Export Data
                        </a>
                        <button
                            @click="openImportModal"
                            class="px-3 py-2 text-xs font-bold text-slate-700 hover:text-blue-600 dark:text-slate-300 bg-white hover:bg-slate-100 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:border-transparent rounded-lg transition-colors cursor-pointer shadow-sm"
                        >
                            Import Data
                        </button>
                        <div class="h-6 w-px bg-slate-200 dark:bg-slate-800 hidden sm:block"></div>
                        <button
                            @click="openCreateModal"
                            class="group relative inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-blue-500 active:scale-95 transition-all border-0 cursor-pointer"
                        >
                            <PlusIcon class="h-4 w-4" />
                            Tambah Kendaraan
                        </button>
                    </div>
                </div>

                <p class="text-xs text-slate-500 dark:text-slate-450 leading-relaxed max-w-2xl">Master data informasi kendaraan operational dan driver USICS.</p>

                <!-- Filters -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-2 relative group">
                        <MagnifyingGlassIcon class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400 group-focus-within:text-blue-500 transition-colors" />
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Cari No Polisi, Driver, atau Tipe..."
                            class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl pl-12 pr-4 py-3 text-sm text-slate-900 dark:text-white placeholder:text-slate-400 focus:border-blue-500 outline-none transition-all shadow-sm"
                        />
                    </div>
                    <div class="relative group">
                        <FunnelIcon class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400 group-focus-within:text-blue-500 transition-colors" />
                        <select
                            v-model="statusFilter"
                            class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl pl-12 pr-4 py-3 text-sm text-slate-900 dark:text-white focus:border-blue-500 outline-none transition-all shadow-sm"
                        >
                            <option value="">Semua Status</option>
                            <option v-for="s in vehicleStatuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                        </select>
                    </div>
                </div>

                <!-- Vehicles Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div 
                        v-for="vehicle in vehicles.data" 
                        :key="vehicle.id"
                        class="group relative bg-white/70 dark:bg-[#0a0a16]/60 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden transition-all hover:shadow-md hover:border-blue-500/50 flex flex-col justify-between"
                    >
                        <!-- Card Header (Large Vehicle Photo) -->
                        <div class="relative h-48 bg-slate-100 dark:bg-slate-800 flex items-center justify-center overflow-hidden">
                            <img 
                                v-if="vehicle.vehicle_photo_url" 
                                :src="vehicle.vehicle_photo_url" 
                                class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" 
                            />
                            <TruckIcon v-else class="h-20 w-20 text-slate-300 dark:text-slate-700" />
                            
                            <!-- Status Badge Floating -->
                            <div class="absolute top-3 right-3">
                                <span 
                                    class="px-3 py-1 text-[9px] font-black rounded-full uppercase tracking-[0.15em] leading-none flex items-center gap-1.5 shadow-sm backdrop-blur-md border"
                                    :class="getStatusColor(vehicle.status)"
                                >
                                    {{ vehicle.status }}
                                </span>
                            </div>

                            <!-- Driver Photo -->
                            <div class="absolute bottom-3 right-3 group/driver">
                                <div class="w-16 h-16 rounded-2xl overflow-hidden ring-4 ring-white dark:ring-slate-900 shadow-lg bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700">
                                    <img 
                                        v-if="vehicle.driver_photo_url" 
                                        :src="vehicle.driver_photo_url" 
                                        class="w-full h-full object-cover" 
                                    />
                                    <div v-else class="w-full h-full bg-slate-50 dark:bg-slate-900 flex items-center justify-center">
                                        <PlusIcon class="w-6 h-6 text-slate-300" />
                                    </div>
                                </div>
                                <div class="absolute -bottom-1 -right-1 w-6 h-6 bg-emerald-500 rounded-full border-2 border-white dark:border-slate-900 flex items-center justify-center shadow-md" v-if="vehicle.driver_name">
                                    <CheckBadgeIcon class="w-3.5 h-3.5 text-white" />
                                </div>
                            </div>
                        </div>

                        <!-- Compact Info Section -->
                        <div class="p-4 pt-4">
                            <div class="mb-3">
                                <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tighter leading-none font-mono">{{ vehicle.license_plate }}</h3>
                                <p class="text-[9px] font-bold text-slate-500 dark:text-slate-450 uppercase tracking-[0.2em] mt-1">{{ vehicle.brand }} • {{ vehicle.vehicle_type }}</p>
                            </div>

                            <!-- Tech Tags -->
                            <div class="flex flex-wrap gap-1.5 mb-4">
                                <div 
                                    class="flex items-center gap-1 px-2 py-0.5 rounded text-[9px] font-black border uppercase font-mono"
                                    :class="isExpired(vehicle.stnk_expiry) 
                                        ? 'bg-red-50 border-red-200 text-red-600 dark:bg-red-900/10 dark:border-transparent dark:text-red-400' 
                                        : (isNearExpiry(vehicle.stnk_expiry) 
                                            ? 'bg-amber-50 border-amber-200 text-amber-705 dark:bg-amber-900/10 dark:border-transparent dark:text-amber-400' 
                                            : 'bg-slate-50 border-slate-200 text-slate-550 dark:bg-slate-800 dark:border-transparent dark:text-slate-400')"
                                >
                                    STNK: {{ formatDate(vehicle.stnk_expiry) }}
                                </div>
                                <div 
                                    class="flex items-center gap-1 px-2 py-0.5 rounded text-[9px] font-black border uppercase font-mono"
                                    :class="isExpired(vehicle.kir_expiry) 
                                        ? 'bg-red-50 border-red-200 text-red-600 dark:bg-red-900/10 dark:border-transparent dark:text-red-400' 
                                        : (isNearExpiry(vehicle.kir_expiry) 
                                            ? 'bg-amber-50 border-amber-200 text-amber-705 dark:bg-amber-900/10 dark:border-transparent dark:text-amber-400' 
                                            : 'bg-slate-50 border-slate-200 text-slate-550 dark:bg-slate-800 dark:border-transparent dark:text-slate-400')"
                                >
                                    KIR: {{ formatDate(vehicle.kir_expiry) }}
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2 border-t border-slate-100 dark:border-slate-800/50 pt-3">
                                <div class="flex flex-col">
                                    <span class="text-[8px] font-black uppercase tracking-widest text-slate-400">Driver</span>
                                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300 truncate">{{ vehicle.driver_name || 'No Assign' }}</span>
                                </div>
                                <div class="flex flex-col text-right">
                                    <span class="text-[8px] font-black uppercase tracking-widest text-slate-400">Capacity</span>
                                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300 font-mono">{{ formatNumber(vehicle.capacity_weight/1000) }} Ton / {{ formatNumber(vehicle.capacity_volume) }} m³</span>
                                </div>
                            </div>
                        </div>

                        <!-- Card Actions -->
                        <div class="flex items-center border-t border-slate-100 dark:border-slate-800 divide-x divide-slate-100 dark:divide-slate-800">
                            <Link 
                                :href="route('logistics.fleet.show', vehicle.id)"
                                class="flex-1 py-3 text-xs font-black text-blue-650 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-500/10 transition-all flex items-center justify-center gap-2 cursor-pointer border-0 bg-transparent"
                            >
                                <EyeIcon class="w-4 h-4" /> Detail
                            </Link>
                            <button 
                                @click="openEditModal(vehicle)"
                                class="flex-1 py-3 text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 hover:text-blue-650 dark:hover:text-blue-400 transition-all flex items-center justify-center gap-2 cursor-pointer border-0 bg-transparent"
                            >
                                <PencilSquareIcon class="w-4 h-4" /> Edit
                            </button>
                            <button 
                                @click="deleteVehicle(vehicle.id)"
                                class="flex-1 py-3 text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-red-50 dark:hover:bg-red-500/10 hover:text-red-500 dark:hover:text-red-400 transition-all flex items-center justify-center gap-2 cursor-pointer border-0 bg-transparent"
                            >
                                <TrashIcon class="w-4 h-4" /> Hapus
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Zero State -->
                <div v-if="vehicles.data.length === 0" class="mt-12 text-center p-12 bg-white/70 dark:bg-slate-900 rounded-xl border border-dashed border-slate-300 dark:border-slate-800">
                    <TruckIcon class="mx-auto h-12 w-12 text-slate-350 dark:text-slate-650 mb-3" />
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Tidak ada kendaraan</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Mulai dengan menambahkan kendaraan baru ke armada Bapak.</p>
                </div>

                <!-- Import Modal -->
                <div v-if="showImportModal" class="fixed inset-0 z-[60] overflow-y-auto font-mono">
                    <div class="flex min-h-full items-center justify-center p-4">
                        <div @click="closeImportModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity" />
                        
                        <div class="relative w-full max-w-lg rounded-2xl bg-white dark:bg-slate-900 shadow-2xl overflow-hidden border border-slate-200 dark:border-slate-800">
                            <div class="px-8 py-6 border-b border-slate-105 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 flex items-center justify-between">
                                <div>
                                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">Import Data Armada</h3>
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1 uppercase tracking-widest font-bold">Upload Excel File</p>
                                </div>
                                <button @click="closeImportModal" class="text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors bg-transparent border-0 cursor-pointer">
                                    <PlusIcon class="h-6 w-6 rotate-45" />
                                </button>
                            </div>

                            <form @submit.prevent="submitImport" class="p-8">
                                <div class="space-y-4">
                                    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-xl border border-blue-150 dark:border-blue-500/10 flex items-start gap-3 text-xs">
                                        <div class="p-2 bg-blue-100 dark:bg-blue-800 rounded-lg text-blue-600 dark:text-blue-400">
                                            <TruckIcon class="h-6 w-6" />
                                        </div>
                                        <div>
                                            <p class="font-bold text-blue-900 dark:text-blue-100">Petunjuk Import</p>
                                            <p class="text-[11px] text-blue-700 dark:text-blue-300 mt-1 leading-relaxed">
                                                Pastikan format file Excel sesuai dengan template yang disediakan. 
                                                <a href="/logistics/fleet/template" class="underline font-bold hover:text-blue-500">Download Template Disini</a>.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">File Excel (.xlsx)</label>
                                        <input type="file" @input="importForm.file = $event.target.files[0]" accept=".xlsx, .xls" class="w-full text-xs text-slate-550 file:mr-4 file:py-2.5 file:px-6 file:rounded-xl file:border-0 file:text-[10px] file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-slate-800 dark:file:text-slate-300 border border-slate-200 dark:border-slate-700 rounded-xl p-2" required />
                                    </div>
                                </div>

                                <div class="mt-8 flex items-center justify-end gap-3">
                                    <button 
                                        type="button" 
                                        @click="closeImportModal"
                                        class="px-6 py-2.5 text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-all cursor-pointer border-0 bg-transparent"
                                    >
                                        Batal
                                    </button>
                                    <button 
                                        type="submit"
                                        :disabled="importForm.processing"
                                        class="px-10 py-2.5 text-xs font-black text-white bg-blue-600 hover:bg-blue-500 rounded-xl shadow-sm active:scale-95 transition-all disabled:opacity-50 border-0 cursor-pointer"
                                    >
                                        {{ importForm.processing ? 'Mengupload...' : 'Import Data' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Create/Edit Modal -->
                <div v-if="showModal" class="fixed inset-0 z-[60] overflow-y-auto font-mono">
                    <div class="flex min-h-full items-center justify-center p-4">
                        <div @click="closeModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity" />
                        
                        <div class="relative w-full max-w-2xl rounded-2xl bg-white dark:bg-slate-900 shadow-2xl overflow-hidden border border-slate-200 dark:border-slate-800">
                            <div class="px-8 py-6 border-b border-slate-105 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 flex items-center justify-between">
                                <div>
                                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">{{ editingVehicle ? 'Edit Kendaraan' : 'Tambah Kendaraan Baru' }}</h3>
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1 uppercase tracking-widest font-bold">Informasi Detail Armada</p>
                                </div>
                                <button @click="closeModal" class="text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors bg-transparent border-0 cursor-pointer">
                                    <PlusIcon class="h-6 w-6 rotate-45" />
                                </button>
                            </div>

                            <form @submit.prevent="submit" class="p-8">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-xs">
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">No Polisi (License Plate)</label>
                                        <input v-model="form.license_plate" type="text" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 outline-none transition-all font-bold uppercase" placeholder="B 1234 ABC" required />
                                        <div v-if="form.errors.license_plate" class="text-[11px] font-bold text-red-650">{{ form.errors.license_plate }}</div>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">Nomor Kartu RFID</label>
                                        <input v-model="form.rfid_card" type="text" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 outline-none transition-all font-mono" placeholder="Contoh: 1029384756" />
                                        <div v-if="form.errors.rfid_card" class="text-[11px] font-bold text-red-655">{{ form.errors.rfid_card }}</div>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">Traccar Device</label>
                                        <select v-model="form.traccar_device_id" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                                            <option :value="null">- Tidak di-link -</option>
                                            <option v-for="d in traccarDevices" :key="d.id" :value="d.id">
                                                {{ d.name }}{{ d.uniqueId ? ` (${d.uniqueId})` : '' }}
                                            </option>
                                        </select>
                                        <div v-if="traccarLoading" class="text-[10px] font-bold text-slate-500 font-mono">Loading devices...</div>
                                        <div v-else-if="traccarError" class="text-[10px] font-bold text-red-600 font-mono">{{ traccarError }}</div>
                                        <div v-if="form.errors.traccar_device_id" class="text-[10px] font-bold text-red-650 font-mono">{{ form.errors.traccar_device_id }}</div>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">Tipe Kendaraan</label>
                                        <select v-model="form.vehicle_type" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                                            <option value="">Pilih Tipe</option>
                                            <option value="Truck">Truck</option>
                                            <option value="Van">Van</option>
                                            <option value="Box">Box</option>
                                            <option value="Motorcycle">Motorcycle</option>
                                        </select>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">Brand / Merk</label>
                                        <input v-model="form.brand" type="text" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 outline-none transition-all font-medium" placeholder="Mitsubishi Fuso" />
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">Driver Name</label>
                                        <input v-model="form.driver_name" type="text" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 outline-none transition-all font-medium" placeholder="Nama Supir" />
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">Capacity Weight (Kg)</label>
                                        <input v-model="form.capacity_weight" type="number" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 outline-none transition-all font-medium" />
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">Status Awal</label>
                                        <select v-model="form.status" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                                            <option v-for="s in vehicleStatuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                                        </select>
                                    </div>

                                    <!-- Legal Details -->
                                    <div class="md:col-span-2 mt-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-blue-550 dark:text-blue-400">Legalitas & Identitas Kendaraan</p>
                                    </div>
                                    
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">Nomor STNK</label>
                                        <input v-model="form.stnk_number" type="text" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 outline-none transition-all font-medium" placeholder="No. Registrasi STNK" />
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">STNK Berlaku S/D</label>
                                        <input v-model="form.stnk_expiry" type="date" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 outline-none transition-all font-medium" />
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">Nomor KIR</label>
                                        <input v-model="form.kir_number" type="text" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 outline-none transition-all font-medium" placeholder="No. Uji KIR" />
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">KIR Berlaku S/D</label>
                                        <input v-model="form.kir_expiry" type="date" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 outline-none transition-all font-medium" />
                                    </div>

                                    <!-- Photo Uploads -->
                                    <div class="md:col-span-2 mt-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-blue-550 dark:text-blue-400">Upload Dokumentasi (Foto)</p>
                                    </div>

                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">Foto Kendaraan</label>
                                        <input type="file" @input="form.vehicle_photo = $event.target.files[0]" class="w-full text-[10px] text-slate-550 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-slate-800 dark:file:text-slate-300" />
                                        <progress v-if="form.progress" :value="form.progress.percentage" max="100" class="w-full h-1 rounded overflow-hidden">
                                            {{ form.progress.percentage }}%
                                        </progress>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">Foto Supir</label>
                                        <input type="file" @input="form.driver_photo = $event.target.files[0]" class="w-full text-[10px] text-slate-550 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-slate-800 dark:file:text-slate-300" />
                                    </div>
                                </div>

                                <div class="mt-8 flex items-center justify-end gap-3">
                                    <button 
                                        type="button" 
                                        @click="closeModal"
                                        class="px-6 py-2.5 text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-all cursor-pointer border-0 bg-transparent"
                                    >
                                        Batal
                                    </button>
                                    <button 
                                        type="submit"
                                        :disabled="form.processing"
                                        class="px-10 py-2.5 text-xs font-black text-white bg-blue-600 hover:bg-blue-500 rounded-xl shadow-sm active:scale-95 transition-all disabled:opacity-50 border-0 cursor-pointer"
                                    >
                                        {{ editingVehicle ? 'Simpan Perubahan' : 'Tambah Kendaraan' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Operations Guide Separator -->
                <div class="mt-8 relative hidden md:block">
                    <div class="absolute inset-0 flex items-center" aria-hidden="true">
                        <div class="w-full border-t border-slate-200 dark:border-slate-800"></div>
                    </div>
                    <div class="relative flex justify-center">
                        <span class="bg-[#F8FAFC] dark:bg-[#050510] px-4 text-[10px] font-black tracking-[0.2em] text-slate-400 dark:text-slate-500 uppercase">
                            Fleet Operations Guide
                        </span>
                    </div>
                </div>

                <!-- Guide Cards -->
                <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4 hidden md:grid mb-8">
                    <div class="bg-white/70 dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm hover:border-slate-350 dark:hover:border-slate-700 transition-colors">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2.5 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400">
                                <TruckIcon class="h-5 w-5" />
                            </div>
                            <h4 class="font-bold text-slate-850 dark:text-slate-205 text-sm">Capacity Analytics</h4>
                        </div>
                        <p class="text-xs text-slate-550 dark:text-slate-400 leading-relaxed">
                            Establish accurate <strong>Weight (Kg)</strong> and <strong>Volume (Cbm)</strong> bounds to prevent over-tonnage assignments during dispatch planning.
                        </p>
                    </div>

                    <div class="bg-white/70 dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm hover:border-slate-350 dark:hover:border-slate-700 transition-colors">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2.5 rounded-xl bg-orange-500/10 text-orange-605 dark:text-orange-400">
                                <TagIcon class="h-5 w-5" />
                            </div>
                            <h4 class="font-bold text-slate-850 dark:text-slate-205 text-sm">Compliance Alerts</h4>
                        </div>
                        <p class="text-xs text-slate-550 dark:text-slate-400 leading-relaxed">
                            Yellow or Red tags surrounding STNK / KIR dates indicate <strong>impending expiry</strong>. Renewing paperwork ensures seamless on-road compliance.
                        </p>
                    </div>

                    <div class="bg-white/70 dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm hover:border-slate-350 dark:hover:border-slate-700 transition-colors">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-500">
                                <CheckBadgeIcon class="h-5 w-5" />
                            </div>
                            <h4 class="font-bold text-slate-850 dark:text-slate-205 text-sm">Driver Pairing</h4>
                        </div>
                        <p class="text-xs text-slate-550 dark:text-slate-400 leading-relaxed">
                            Attach standard drivers directly to assets. This pre-fills <strong>Driver Manifests</strong> later, avoiding redundant data entry at operational touchpoints.
                        </p>
                    </div>
                    
                    <div class="bg-white/70 dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm hover:border-slate-350 dark:hover:border-slate-700 transition-colors">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2.5 rounded-xl bg-cyan-500/10 text-cyan-600 dark:text-cyan-400">
                                <FunnelIcon class="h-5 w-5" />
                            </div>
                            <h4 class="font-bold text-slate-850 dark:text-slate-205 text-sm">State Overrides</h4>
                        </div>
                        <p class="text-xs text-slate-550 dark:text-slate-400 leading-relaxed">
                            If an asset encounters defects, manually force the status to <strong>Maintenance</strong> to actively block its appearance inside the Logistics Planning wizard.
                        </p>
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
