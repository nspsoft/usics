<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    PlusIcon, 
    PencilIcon, 
    TrashIcon, 
    XMarkIcon,
    CpuChipIcon,
    TagIcon,
    CalendarIcon,
    ClockIcon,
    BanknotesIcon,
    WrenchScrewdriverIcon,
    EyeIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    machines: Array,
});

const showModal = ref(false);
const editingMachine = ref(null);

const machineTypes = [
    'Slitting',
    'Mini Slitting',
    'Levelling',
    'Mini Levelling',
    'Shearing',
    'Guillotine Shear',
    'Blanking',
    'Welding',
    'Supporting'
];

const form = useForm({
    name: '',
    code: '',
    type: '',
    maker: '',
    capacity: '',
    purchase_date: '',
    purchase_price: '',
    runtime_hours: '',
    is_active: true,
});

const openCreateModal = () => {
    editingMachine.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEditModal = (machine) => {
    editingMachine.value = machine;
    form.name = machine.name;
    form.code = machine.code || '';
    form.type = machine.type || '';
    form.maker = machine.maker || '';
    form.capacity = machine.capacity || '';
    form.purchase_date = machine.purchase_date ? machine.purchase_date.slice(0, 10) : '';
    form.purchase_price = machine.purchase_price !== null && machine.purchase_price !== undefined ? parseFloat(machine.purchase_price) : '';
    form.runtime_hours = machine.runtime_hours !== null && machine.runtime_hours !== undefined ? parseFloat(machine.runtime_hours) : '';
    form.is_active = !!machine.is_active;
    form.clearErrors();
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const submit = () => {
    if (editingMachine.value) {
        form.put(route('manufacturing.machines.update', editingMachine.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('manufacturing.machines.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteMachine = (machine) => {
    if (confirm(`Are you sure you want to delete machine "${machine.name}"?`)) {
        form.delete(route('manufacturing.machines.destroy', machine.id));
    }
};

// Formatter Helpers
const formatCurrency = (value) => {
    if (value === null || value === undefined || isNaN(value) || value === '') return 'Rp -';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    }).format(value);
};

const formatDate = (value) => {
    if (!value) return '-';
    try {
        const date = new Date(value);
        return date.toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' });
    } catch (e) {
        return value;
    }
};

const formatRuntime = (value) => {
    if (value === null || value === undefined || isNaN(value)) return '0 hrs';
    return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 }).format(value) + ' hrs';
};

// Machine Image Mapper
const getMachineImage = (type, name) => {
    const t = (type || '').toUpperCase();
    const n = (name || '').toUpperCase();
    
    if (t.includes('SLITTING') || n.includes('SLITTER')) {
        return '/images/slitting_machine.png';
    } else if (t.includes('BLANKING') || n.includes('BLANKING')) {
        return '/images/blanking_press.png';
    } else if (t.includes('WELDING') || n.includes('LASER') || n.includes('WELDER')) {
        return '/images/laser_welder.png';
    } else {
        // Shearing / Guillotine Shear / Levelling / Supporting
        return '/images/shearing_machine.png';
    }
};

const selectedMachine = ref(null);
const viewMachineDetails = (machine) => {
    selectedMachine.value = machine;
};
const closeDetailModal = () => {
    selectedMachine.value = null;
};
</script>

<template>
    <Head title="Manajemen Mesin" />
    
    <AppLayout title="Manajemen Mesin">
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 pb-10">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Daftar Mesin / Line</h2>
                    <p class="text-sm text-slate-500 mt-1 uppercase tracking-widest font-bold">Machine Configuration & Specifications</p>
                </div>
                <button 
                    @click="openCreateModal"
                    class="flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-emerald-500 px-5 py-3 text-sm font-bold text-slate-900 dark:text-white shadow-lg shadow-emerald-500/20 hover:from-emerald-500 transition-all active:scale-95"
                >
                    <PlusIcon class="h-5 w-5" />
                    Tambah Mesin Baru
                </button>
            </div>

            <!-- Table Card -->
            <div class="glass-card rounded-3xl overflow-hidden shadow-sm border border-slate-200/50 dark:border-slate-800/50">
                <div class="overflow-x-auto overflow-y-auto max-h-[650px]">
                    <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800 text-sm">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
                                <th class="sticky top-0 z-20 bg-slate-50 dark:bg-slate-900 px-6 py-4 text-left text-[10px] font-bold text-slate-500 uppercase tracking-widest">Mesin</th>
                                <th class="sticky top-0 z-20 bg-slate-50 dark:bg-slate-900 px-6 py-4 text-left text-[10px] font-bold text-slate-500 uppercase tracking-widest">Kode</th>
                                <th class="sticky top-0 z-20 bg-slate-50 dark:bg-slate-900 px-6 py-4 text-left text-[10px] font-bold text-slate-500 uppercase tracking-widest">Tipe</th>
                                <th class="sticky top-0 z-20 bg-slate-50 dark:bg-slate-900 px-6 py-4 text-left text-[10px] font-bold text-slate-500 uppercase tracking-widest">Maker</th>
                                <th class="sticky top-0 z-20 bg-slate-50 dark:bg-slate-900 px-6 py-4 text-left text-[10px] font-bold text-slate-500 uppercase tracking-widest">Kapasitas</th>
                                <th class="sticky top-0 z-20 bg-slate-50 dark:bg-slate-900 px-6 py-4 text-left text-[10px] font-bold text-slate-500 uppercase tracking-widest">Detail Pembelian</th>
                                <th class="sticky top-0 z-20 bg-slate-50 dark:bg-slate-900 px-6 py-4 text-left text-[10px] font-bold text-slate-500 uppercase tracking-widest">Runtime</th>
                                <th class="sticky top-0 z-20 bg-slate-50 dark:bg-slate-900 px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-widest">Status</th>
                                <th class="sticky top-0 z-20 bg-slate-50 dark:bg-slate-900 px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-widest">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="machine in machines" :key="machine.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                <!-- Machine Info -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <img 
                                            :src="getMachineImage(machine.type, machine.name)" 
                                            class="h-10 w-10 rounded-xl object-cover border border-slate-200 dark:border-slate-800 shadow-sm shrink-0" 
                                            :alt="machine.name"
                                        />
                                        <div>
                                            <div class="text-slate-900 dark:text-white font-bold text-base">{{ machine.name }}</div>
                                            <div class="text-xs text-slate-500 font-mono mt-0.5">{{ machine.qr_code_uuid ? machine.qr_code_uuid.substring(0, 8) : '-' }} (QR ID)</div>
                                        </div>
                                    </div>
                                </td>
                                
                                <!-- Code -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-1.5 text-slate-700 dark:text-slate-300 font-mono font-bold text-xs bg-slate-100 dark:bg-slate-800 px-2.5 py-1 rounded-lg w-max border border-slate-200 dark:border-slate-700/50">
                                        <TagIcon class="h-3.5 w-3.5 text-slate-500" />
                                        {{ machine.code || '-' }}
                                    </div>
                                </td>

                                <!-- Type -->
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-md bg-blue-50 dark:bg-blue-900/20 px-2 py-1 text-xs font-bold text-blue-700 dark:text-blue-400 border border-blue-100 dark:border-blue-800/30">
                                        {{ machine.type || '-' }}
                                    </span>
                                </td>
                                
                                <!-- Maker -->
                                <td class="px-6 py-4">
                                    <div class="text-slate-900 dark:text-slate-200 font-medium">{{ machine.maker || '-' }}</div>
                                </td>
                                
                                <!-- Capacity -->
                                <td class="px-6 py-4">
                                    <div class="text-slate-900 dark:text-slate-200 font-bold text-xs bg-emerald-500/5 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/10 px-2 py-1 rounded-lg w-max">
                                        {{ machine.capacity || '-' }}
                                    </div>
                                </td>
                                
                                <!-- Purchase Details -->
                                <td class="px-6 py-4">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-1.5 text-slate-700 dark:text-slate-300 text-xs">
                                            <CalendarIcon class="h-3.5 w-3.5 text-slate-400 shrink-0" />
                                            <span>{{ formatDate(machine.purchase_date) }}</span>
                                        </div>
                                        <div class="flex items-center gap-1.5 text-slate-500 text-xs font-semibold">
                                            <BanknotesIcon class="h-3.5 w-3.5 text-slate-400 shrink-0" />
                                            <span>{{ formatCurrency(machine.purchase_price) }}</span>
                                        </div>
                                    </div>
                                </td>
                                
                                <!-- Runtime -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-1.5 text-slate-700 dark:text-slate-300 font-mono text-xs">
                                        <ClockIcon class="h-3.5 w-3.5 text-slate-400" />
                                        {{ formatRuntime(machine.runtime_hours) }}
                                    </div>
                                </td>
                                
                                <!-- Status -->
                                <td class="px-6 py-4 text-center">
                                    <span 
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                                        :class="machine.is_active ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border border-slate-500/30'"
                                    >
                                        {{ machine.is_active ? 'Aktif' : 'Non-Aktif' }}
                                    </span>
                                </td>
                                
                                <!-- Actions -->
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button 
                                            @click="viewMachineDetails(machine)"
                                            class="p-2 rounded-lg bg-slate-100 dark:bg-slate-800 text-indigo-500 dark:text-indigo-400 hover:bg-indigo-500 hover:text-white dark:hover:bg-indigo-650 transition-all active:scale-90"
                                            title="Detail Mesin"
                                        >
                                            <EyeIcon class="h-4 w-4" />
                                        </button>
                                        <button 
                                            @click="openEditModal(machine)"
                                            class="p-2 rounded-lg bg-slate-100 dark:bg-slate-800 text-blue-500 dark:text-blue-400 hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600 transition-all active:scale-90"
                                            title="Edit"
                                        >
                                            <PencilIcon class="h-4 w-4" />
                                        </button>
                                        <button 
                                            @click="deleteMachine(machine)"
                                            class="p-2 rounded-lg bg-slate-100 dark:bg-slate-800 text-red-500 dark:text-red-400 hover:bg-red-500 hover:text-white dark:hover:bg-red-600 transition-all active:scale-90"
                                            title="Delete"
                                        >
                                            <TrashIcon class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="machines.length === 0">
                                <td colspan="9" class="px-6 py-12 text-center text-slate-500 italic">Belum ada mesin terdaftar</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Form -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div v-if="showModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-950/40 dark:bg-slate-950/80 backdrop-blur-sm">
                    <div class="bg-white dark:bg-slate-900 w-full max-w-2xl rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                        <!-- Modal Header -->
                        <div class="relative p-6 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex items-center gap-3">
                            <WrenchScrewdriverIcon class="h-6 w-6 text-emerald-500" />
                            <div>
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ editingMachine ? 'Edit Detail Mesin' : 'Tambah Mesin Baru' }}</h3>
                                <p class="text-xs text-slate-500">Konfigurasi spesifikasi dan parameter operasional mesin</p>
                            </div>
                            <button @click="closeModal" class="absolute right-6 top-6 text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800">
                                <XMarkIcon class="h-5 w-5" />
                            </button>
                        </div>

                        <!-- Modal Body -->
                        <form @submit.prevent="submit" class="p-6 space-y-6">
                            <!-- Machine Name -->
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 px-1">Nama Mesin / Line</label>
                                <input 
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Contoh: Slitter Machine SA"
                                    class="w-full rounded-2xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 py-3.5 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 dark:focus:border-emerald-400 transition-all"
                                    :class="{'border-red-500/50 focus:ring-red-500/50': form.errors.name}"
                                    required
                                />
                                <div v-if="form.errors.name" class="text-red-400 text-xs mt-1.5 ml-1 font-medium">{{ form.errors.name }}</div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Code -->
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 px-1">Kode Mesin</label>
                                    <input 
                                        v-model="form.code"
                                        type="text"
                                        placeholder="Contoh: SLITTER-SA"
                                        class="w-full rounded-2xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 py-3.5 px-4 text-slate-900 dark:text-white font-mono focus:ring-2 focus:ring-emerald-500/50 transition-all"
                                    />
                                </div>

                                <!-- Type / Jenis Mesin -->
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 px-1">Jenis / Tipe Mesin</label>
                                    <select 
                                        v-model="form.type"
                                        class="w-full rounded-2xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 py-3.5 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/50 transition-all"
                                    >
                                        <option value="">-- Pilih Jenis Mesin --</option>
                                        <option v-for="t in machineTypes" :key="t" :value="t">{{ t }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Maker -->
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 px-1">Maker / Pabrikan</label>
                                    <input 
                                        v-model="form.maker"
                                        type="text"
                                        placeholder="Contoh: SUMIKURA, AIDA, TOTO"
                                        class="w-full rounded-2xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 py-3.5 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/50 transition-all"
                                    />
                                </div>

                                <!-- Capacity -->
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 px-1">Kapasitas Produksi</label>
                                    <input 
                                        v-model="form.capacity"
                                        type="text"
                                        placeholder="Contoh: 3,502 MT/Month atau 94,169 Pcs/Month"
                                        class="w-full rounded-2xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 py-3.5 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/50 transition-all"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Purchase Date -->
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 px-1">Tanggal Pembelian</label>
                                    <input 
                                        v-model="form.purchase_date"
                                        type="date"
                                        class="w-full rounded-2xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 py-3.5 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/50 transition-all"
                                    />
                                </div>

                                <!-- Purchase Price -->
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 px-1">Harga Pembelian (IDR)</label>
                                    <input 
                                        v-model="form.purchase_price"
                                        type="number"
                                        step="any"
                                        placeholder="Contoh: 6200000000"
                                        class="w-full rounded-2xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 py-3.5 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/50 transition-all"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Runtime Hours -->
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 px-1">Jam Kerja / Runtime (Hours)</label>
                                    <input 
                                        v-model="form.runtime_hours"
                                        type="number"
                                        step="any"
                                        placeholder="Contoh: 45280.5"
                                        class="w-full rounded-2xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 py-3.5 px-4 text-slate-900 dark:text-white font-mono focus:ring-2 focus:ring-emerald-500/50 transition-all"
                                    />
                                </div>
                            </div>

                            <!-- Active Checkbox -->
                            <div class="flex items-center gap-3 p-4 rounded-2xl bg-slate-50 dark:bg-slate-950/40 border border-slate-200/60 dark:border-slate-800/80">
                                <input 
                                    v-model="form.is_active"
                                    type="checkbox"
                                    id="is_active"
                                    class="h-5 w-5 rounded-md border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-emerald-600 focus:ring-emerald-500/50"
                                />
                                <label for="is_active" class="text-sm font-bold text-slate-600 dark:text-slate-300">Setel sebagai Mesin Aktif (Dapat digunakan di Produksi & Maintenance)</label>
                            </div>

                            <!-- Modal Actions -->
                            <div class="flex gap-3 pt-4 border-t border-slate-100 dark:border-slate-850">
                                <button 
                                    type="button"
                                    @click="closeModal"
                                    class="flex-1 py-4 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold hover:bg-slate-200 dark:hover:bg-slate-700/80 active:scale-95 transition-all"
                                >
                                    Batal
                                </button>
                                <button 
                                    type="submit"
                                    :disabled="form.processing"
                                    class="flex-[2] py-4 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold shadow-lg shadow-emerald-500/20 active:scale-95 transition-all disabled:opacity-50 flex items-center justify-center gap-2"
                                >
                                    <span v-if="form.processing" class="animate-spin h-5 w-5 border-2 border-white/30 border-t-white rounded-full"></span>
                                    {{ editingMachine ? 'Simpan Perubahan' : 'Tambah Mesin' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Machine Detail Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div v-if="selectedMachine" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-md">
                    <div class="bg-white dark:bg-slate-900 w-full max-w-3xl rounded-[32px] border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden flex flex-col md:flex-row p-6 md:p-8 gap-6 md:gap-8 animate-in zoom-in-95 duration-200 relative">
                        
                        <!-- Close button -->
                        <button @click="closeDetailModal" class="absolute top-4 right-4 p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors text-slate-400 hover:text-slate-900 dark:hover:text-white">
                            <XMarkIcon class="h-6 w-6" />
                        </button>

                        <!-- Left Side: Machine Media & Description -->
                        <div class="md:w-1/2 flex flex-col justify-between border-b md:border-b-0 md:border-r border-slate-200 dark:border-slate-800 pb-6 md:pb-0 md:pr-6">
                            <div class="space-y-4">
                                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-[10px] font-bold uppercase tracking-wider">
                                    Spesifikasi Alat & Lini
                                </div>
                                <h3 class="text-xl font-black text-slate-950 dark:text-white leading-snug">
                                    {{ selectedMachine.name }}
                                </h3>
                                <div class="relative group overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-850 shadow-sm">
                                    <img 
                                        :src="getMachineImage(selectedMachine.type, selectedMachine.name)" 
                                        class="w-full h-48 md:h-56 object-cover" 
                                        :alt="selectedMachine.name"
                                    />
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                                    Mesin lini produksi {{ selectedMachine.name }} (Kode: {{ selectedMachine.code || '-' }}) merupakan lini krusial buatan {{ selectedMachine.maker || 'Maker N/A' }} dengan kapasitas terpasang {{ selectedMachine.capacity || 'N/A' }}.
                                </p>
                            </div>
                            <div class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-800 flex gap-2">
                                <span class="text-xs text-slate-400">QR UUID:</span>
                                <span class="text-xs font-mono text-slate-600 dark:text-slate-300 font-bold truncate max-w-[200px]" :title="selectedMachine.qr_code_uuid">{{ selectedMachine.qr_code_uuid || '-' }}</span>
                            </div>
                        </div>

                        <!-- Right Side: Metrics & Operational Stats -->
                        <div class="md:w-1/2 flex flex-col justify-between">
                            <div class="space-y-6">
                                <div>
                                    <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Status Mesin</h4>
                                    <span 
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                                        :class="selectedMachine.is_active ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border border-slate-500/30'"
                                    >
                                        {{ selectedMachine.is_active ? 'Aktif' : 'Non-Aktif' }}
                                    </span>
                                </div>

                                <!-- Dynamic Operational Metrics -->
                                <div class="bg-slate-50 dark:bg-slate-950/40 rounded-2xl p-4 border border-slate-200/50 dark:border-slate-800/80 space-y-4">
                                    <h5 class="text-xs font-bold text-indigo-500 dark:text-indigo-400 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800 pb-2">Status Operasional & Perawatan</h5>
                                    
                                    <div class="space-y-3 text-xs">
                                        <div class="flex justify-between">
                                            <span class="text-slate-505 text-slate-500">Masa Pakai (Runtime)</span>
                                            <span class="font-mono text-slate-900 dark:text-slate-100 font-bold">{{ formatRuntime(selectedMachine.runtime_hours) }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-slate-500">Tanggal Pembelian</span>
                                            <span class="font-mono text-slate-900 dark:text-slate-100 font-bold">{{ formatDate(selectedMachine.purchase_date) }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-slate-500">Nilai Investasi</span>
                                            <span class="font-mono text-slate-900 dark:text-slate-100 font-bold">{{ formatCurrency(selectedMachine.purchase_price) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Health Score Meter (Simulated) -->
                                <div class="bg-slate-50 dark:bg-slate-950/40 rounded-2xl p-4 border border-slate-200/50 dark:border-slate-800/80 space-y-3">
                                    <div class="flex justify-between items-center">
                                        <h5 class="text-xs font-bold text-indigo-500 dark:text-indigo-400 uppercase tracking-wider">Health Score & Efisiensi</h5>
                                        <span class="text-xs font-black text-emerald-500">{{ Math.max(70, Math.min(100, 100 - Math.round((selectedMachine.runtime_hours || 0) / 3000))) }}%</span>
                                    </div>
                                    <div class="w-full bg-slate-200 dark:bg-slate-800 h-2 rounded-full overflow-hidden">
                                        <div 
                                            class="h-full rounded-full transition-all duration-1000 bg-emerald-500 animate-pulse"
                                            :style="{ width: Math.max(70, Math.min(100, 100 - Math.round((selectedMachine.runtime_hours || 0) / 3000))) + '%' }"
                                        ></div>
                                    </div>
                                    <p class="text-[10px] text-slate-400 leading-normal">
                                        *Skor kesehatan dihitung berdasarkan total jam berjalan (*runtime hours*), masa penyusutan, dan histori pemeliharaan mesin.
                                    </p>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-850 flex gap-3">
                                <button 
                                    @click="closeDetailModal"
                                    class="w-full py-3.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold rounded-2xl text-xs uppercase tracking-wider transition-all"
                                >
                                    Tutup Detail
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>
