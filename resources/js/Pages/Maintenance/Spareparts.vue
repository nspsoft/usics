<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, onMounted, onUnmounted } from 'vue';
import { 
    CubeIcon, 
    MagnifyingGlassIcon,
    PlusIcon,
    PencilIcon,
    ExclamationCircleIcon,
    BanknotesIcon,
    ClockIcon,
    ArrowTrendingUpIcon,
    SunIcon,
    MoonIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    spareparts: Array,
    stats: Object
});

const showModal = ref(false);
const isEdit = ref(false);
const search = ref('');

const form = useForm({
    id: null,
    name: '',
    part_number: '',
    location: '',
    stock: 0,
    min_stock: 5,
    unit_cost: 0,
});

const editAdjustment = ref({
    adjustment: 0
});

const openCreate = () => {
    isEdit.value = false;
    form.reset();
    showModal.value = true;
};

const openEdit = (part) => {
    isEdit.value = true;
    form.id = part.id;
    form.name = part.name;
    form.part_number = part.part_number;
    form.location = part.location;
    form.stock = part.stock;
    form.min_stock = part.min_stock;
    form.unit_cost = part.cost_raw;
    editAdjustment.value.adjustment = 0;
    showModal.value = true;
};

const submit = () => {
    if (isEdit.value) {
        if (editAdjustment.value.adjustment !== 0) {
            form.stock += editAdjustment.value.adjustment;
        }
        form.put(route('maintenance.spareparts.update', form.id), {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            }
        });
    } else {
        form.post(route('maintenance.spareparts.store'), {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            }
        });
    }
};

const submitAutoPr = (partId) => {
    if (confirm('Jalankan Auto-PR untuk memulihkan stok suku cadang ini ke tingkat optimal?')) {
        router.post(route('maintenance.spareparts.auto-pr', partId));
    }
};

const selectedPartIds = ref([]);

const selectAllCritical = () => {
    const criticalIds = props.spareparts
        .filter(part => part.stock <= part.min_stock)
        .map(part => part.id);
    selectedPartIds.value = criticalIds;
};

const selectAllParts = () => {
    selectedPartIds.value = filteredParts().map(part => part.id);
};

const submitBulkAutoPr = () => {
    if (confirm(`Jalankan Auto-PR Konsolidasian untuk ${selectedPartIds.value.length} item terpilih?`)) {
        router.post(route('maintenance.spareparts.bulk-auto-pr'), {
            ids: selectedPartIds.value
        }, {
            onSuccess: () => {
                selectedPartIds.value = [];
            }
        });
    }
};

const filteredParts = () => {
    return props.spareparts.filter(part => {
        return part.name.toLowerCase().includes(search.value.toLowerCase()) || 
               part.part_number.toLowerCase().includes(search.value.toLowerCase()) ||
               part.location.toLowerCase().includes(search.value.toLowerCase());
    });
};

const formatRupiah = (value) => {
    return 'Rp ' + Number(value).toLocaleString('id-ID');
};

// --- Theme Reactive Sync ---
const isLightMode = ref(false);
const toggleTheme = () => {
    isLightMode.value = !isLightMode.value;
    if (isLightMode.value) {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    } else {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    }
};

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
    <Head title="Spareparts Inventory" />

    <AppLayout title="Spareparts Inventory" :render-header="false">
        <div class="min-h-screen bg-slate-50 dark:bg-[#050510] relative overflow-hidden font-mono text-slate-800 dark:text-cyan-50 selection:bg-amber-500/30 transition-colors duration-300">
            
            <!-- Dynamic Background -->
            <div class="fixed inset-0 z-0 pointer-events-none">
                <div class="absolute inset-0 bg-gradient-to-br from-amber-500/5 to-slate-100 dark:from-amber-955/20 dark:to-[#050510]"></div>
                 <div class="perspective-grid absolute inset-0 opacity-[0.05] dark:opacity-10"></div>
            </div>

            <div class="relative z-10 p-6 space-y-6 max-w-7xl mx-auto">
                
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-slate-200 dark:border-white/10 pb-4">
                    <div>
                         <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-0.5 text-[10px] bg-amber-500/10 border border-amber-500/20 rounded text-amber-700 dark:text-amber-400 tracking-[0.2em] uppercase">
                                Inventory Control & Forecast
                            </span>
                            <span class="px-2 py-0.5 text-[10px] bg-slate-200 dark:bg-white/5 border border-slate-300 dark:border-white/10 rounded text-slate-500 dark:text-slate-400 tracking-[0.2em] uppercase">MAINT.PARTS.V2</span>
                        </div>
                        <h1 class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-amber-600 via-slate-800 to-orange-600 dark:from-amber-400 dark:via-white dark:to-orange-400 tracking-widest uppercase dark:glow-text">
                            SPAREPARTS INV.
                        </h1>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="relative group">
                            <MagnifyingGlassIcon class="absolute left-3 top-2.5 h-4 w-4 text-slate-500 group-focus-within:text-amber-500" />
                            <input v-model="search" type="text" placeholder="Cari nama atau part number..." class="bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-lg pl-9 pr-4 py-2 text-sm text-slate-800 dark:text-white focus:border-amber-500 outline-none w-64 transition-all">
                        </div>

                        

                        <button 
                            @click="openCreate"
                            class="px-4 py-2 bg-gradient-to-r from-amber-600 to-amber-500 hover:from-amber-500 hover:to-amber-400 text-white font-bold rounded-lg shadow-sm dark:shadow-[0_0_15px_rgba(245,158,11,0.3)] transition-all flex items-center gap-2 text-sm cursor-pointer border-0"
                        >
                            <PlusIcon class="h-4 w-4" /> ADD PART
                        </button>
                    </div>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-3 gap-4">
                    <div class="hud-panel p-4 bg-white/70 dark:bg-[#0a0a16]/60 border border-slate-200 dark:border-white/5 rounded-xl shadow-sm dark:shadow-none">
                        <p class="text-[10px] text-slate-500 dark:text-slate-400 uppercase tracking-widest font-bold">Total Types</p>
                        <h3 class="text-2xl font-black text-slate-900 dark:text-white">{{ stats.total_items }}</h3>
                    </div>
                    <div class="hud-panel p-4 border-amber-250 dark:border-amber-500/30 bg-amber-50/50 dark:bg-amber-500/5 rounded-xl border shadow-sm dark:shadow-none">
                        <p class="text-[10px] text-amber-700 dark:text-amber-450 uppercase tracking-widest font-bold">Kritis / Habis</p>
                         <h3 class="text-2xl font-black text-amber-600 dark:text-amber-500 animate-pulse">{{ stats.low_stock }}</h3>
                    </div>
                    <div class="hud-panel p-4 bg-white/70 dark:bg-[#0a0a16]/60 border border-slate-200 dark:border-white/5 rounded-xl shadow-sm dark:shadow-none">
                        <p class="text-[10px] text-slate-500 dark:text-slate-400 uppercase tracking-widest font-bold">Total Value</p>
                        <h3 class="text-2xl font-black text-slate-900 dark:text-white">{{ stats.stock_value }}</h3>
                    </div>
                </div>

                <!-- Selection Toolbar -->
                <div class="flex flex-col sm:flex-row gap-4 items-center justify-between bg-slate-100 dark:bg-slate-900/40 p-4 rounded-xl border border-slate-200 dark:border-white/5 text-xs shadow-sm dark:shadow-none">
                    <div class="flex flex-wrap gap-2">
                        <button 
                            @click="selectAllCritical"
                            class="px-3 py-1.5 bg-rose-50 dark:bg-rose-500/10 hover:bg-rose-100 dark:hover:bg-rose-500/20 border border-rose-200 dark:border-rose-500/30 text-rose-700 dark:text-rose-455 rounded uppercase font-black tracking-wider transition-all cursor-pointer"
                        >
                            Pilih Semua Kritis / Habis
                        </button>
                        <button 
                            @click="selectAllParts"
                            class="px-3 py-1.5 bg-slate-50 dark:bg-white/5 hover:bg-slate-100 dark:hover:bg-white/10 border border-slate-200 dark:border-white/10 text-slate-650 dark:text-slate-300 rounded uppercase font-black tracking-wider transition-all cursor-pointer"
                        >
                            Pilih Semua ({{ filteredParts().length }} Item)
                        </button>
                        <button 
                            v-if="selectedPartIds.length > 0"
                            @click="selectedPartIds = []"
                            class="px-3 py-1.5 bg-slate-100 dark:bg-slate-500/10 hover:bg-slate-200 dark:hover:bg-slate-500/20 border border-slate-200 dark:border-slate-500/30 text-slate-600 dark:text-slate-400 rounded uppercase font-black tracking-wider transition-all cursor-pointer"
                        >
                            Clear Selection
                        </button>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="text-slate-500 dark:text-slate-400">
                            Terpilih: <strong class="text-amber-600 dark:text-amber-400 font-bold font-mono">{{ selectedPartIds.length }}</strong> / {{ props.spareparts.length }} item
                        </span>
                        <button 
                            v-if="selectedPartIds.length > 0"
                            @click="submitBulkAutoPr"
                            class="px-4 py-2 bg-gradient-to-r from-cyan-600 to-cyan-500 hover:from-cyan-500 hover:to-cyan-400 text-white font-bold rounded-lg shadow-sm dark:shadow-[0_0_15px_rgba(6,182,212,0.3)] transition-all flex items-center gap-2 cursor-pointer uppercase tracking-wider border-0"
                        >
                            <BanknotesIcon class="h-4 w-4" /> GENERATE AUTO-PR KONSOLIDASI ({{ selectedPartIds.length }} ITEM)
                        </button>
                    </div>
                </div>

                <!-- Inventory Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="part in filteredParts()" :key="part.id" 
                        class="hud-panel p-4 relative group hover:border-amber-500/50 transition-all flex flex-col justify-between bg-white/75 dark:bg-[#0a0a16]/80 border border-slate-200 dark:border-white/10 rounded-xl shadow-sm dark:shadow-[0_0_20px_rgba(0,0,0,0.5)]"
                    >
                        <div>
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex items-center gap-3">
                                    <input 
                                        type="checkbox" 
                                        :value="part.id" 
                                        v-model="selectedPartIds"
                                        class="rounded border-slate-300 dark:border-white/10 bg-white dark:bg-black/40 text-amber-500 focus:ring-amber-550 w-4 h-4 cursor-pointer"
                                    />
                                    <div class="w-10 h-10 rounded bg-slate-100 dark:bg-white/5 flex items-center justify-center text-slate-500 dark:text-slate-400 group-hover:text-amber-500 transition-colors border border-slate-200 dark:border-transparent">
                                        <CubeIcon class="h-6 w-6" />
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-slate-800 dark:text-white group-hover:text-amber-600 dark:group-hover:text-amber-300 transition-colors uppercase truncate max-w-[150px]">{{ part.name }}</h4>
                                        <p class="text-xs text-slate-500 font-mono">{{ part.part_number }}</p>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end gap-1">
                                    <span class="text-[10px] px-2 py-0.5 rounded border uppercase tracking-wider text-center" 
                                        :class="isLightMode
                                            ? {
                                                'bg-emerald-50 border-emerald-200 text-emerald-700': part.stock > part.min_stock,
                                                'bg-amber-50 border-amber-250 text-amber-700': part.stock <= part.min_stock && part.stock > 0,
                                                'bg-rose-50 border-rose-200 text-rose-700': part.stock === 0
                                              }[part.stock > part.min_stock ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : (part.stock > 0 ? 'bg-amber-50 border-amber-250 text-amber-700' : 'bg-rose-50 border-rose-200 text-rose-700')] || part.status_color.replace('500/10', '50').replace('500/20', '200')
                                            : part.status_color">
                                        {{ part.status }}
                                    </span>
                                    <span v-if="part.has_pending_pr" class="text-[9px] px-2 py-0.5 rounded border border-cyan-500/30 bg-cyan-50 dark:bg-cyan-500/10 text-cyan-705 dark:text-cyan-400 uppercase tracking-wider animate-pulse font-bold">
                                        🔗 PR {{ part.pending_pr_number }}
                                    </span>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-x-4 gap-y-3 text-xs mb-4 border-y border-slate-100 dark:border-white/5 py-3">
                                <div>
                                    <p class="text-[9px] text-slate-500 uppercase tracking-wider mb-0.5">Lokasi</p>
                                    <p class="text-slate-700 dark:text-slate-300 font-mono truncate">{{ part.location }}</p>
                                </div>
                                 <div class="text-right">
                                    <p class="text-[9px] text-slate-500 uppercase tracking-wider mb-0.5">Biaya Satuan</p>
                                    <p class="text-slate-700 dark:text-slate-300 font-mono font-bold">{{ formatRupiah(part.unit_cost) }}</p>
                                </div>
                                <div>
                                    <p class="text-[9px] text-slate-500 uppercase tracking-wider mb-0.5">Konsumsi 30D</p>
                                    <p class="text-slate-700 dark:text-slate-300 font-mono">{{ part.consumption_30d }} unit</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-[9px] text-slate-500 uppercase tracking-wider mb-0.5">Estimasi Habis</p>
                                    <p class="text-xs font-mono font-bold" 
                                        :class="part.days_to_depletion !== null && part.days_to_depletion <= 30 
                                            ? 'text-rose-600 dark:text-rose-400' 
                                            : 'text-emerald-600 dark:text-emerald-400'"
                                    >
                                        {{ part.depletion_text }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Stock Status Progress bar -->
                        <div class="space-y-3 pt-1">
                            <div class="flex justify-between items-center text-xs">
                                <span class="text-slate-500">Stok saat ini:</span>
                                <span class="font-bold font-mono" 
                                    :class="part.stock <= part.min_stock 
                                        ? 'text-rose-600 dark:text-rose-450 animate-pulse' 
                                        : 'text-slate-800 dark:text-white'"
                                >
                                    {{ part.stock }} <span class="text-slate-400 font-normal text-[10px]">/ min {{ part.min_stock }}</span>
                                </span>
                            </div>
                            
                            <!-- Level Bar -->
                            <div class="w-full bg-slate-200 dark:bg-slate-950 rounded-full h-2 overflow-hidden border border-slate-300 dark:border-white/5">
                                <div 
                                    class="h-full rounded-full transition-all"
                                    :class="part.stock <= part.min_stock ? 'bg-rose-500' : 'bg-emerald-500'"
                                    :style="{ width: Math.min(100, Math.max(5, (part.stock / Math.max(1, part.min_stock * 2)) * 100)) + '%' }"
                                ></div>
                            </div>

                            <!-- Single Actions -->
                            <div class="flex justify-between items-center pt-2 border-t border-slate-100 dark:border-white/5 text-[10px] uppercase font-bold">
                                <button 
                                    @click="openEdit(part)"
                                    class="text-slate-500 hover:text-amber-600 dark:text-slate-400 dark:hover:text-amber-450 flex items-center gap-1 transition-colors cursor-pointer bg-transparent border-0"
                                >
                                    <PencilIcon class="h-3.5 w-3.5" /> Edit Aset
                                </button>
                                
                                <button 
                                    v-if="part.stock <= part.min_stock && !part.has_pending_pr"
                                    @click="submitAutoPr(part.id)"
                                    class="px-2.5 py-1 bg-amber-500/10 dark:bg-amber-500/20 text-amber-700 dark:text-amber-400 border border-amber-250 dark:border-amber-500/30 rounded-lg hover:bg-amber-500 hover:text-white dark:hover:text-black transition-all flex items-center gap-1 cursor-pointer font-bold shadow-sm dark:shadow-none"
                                >
                                    <BanknotesIcon class="h-3.5 w-3.5" /> Auto-PR
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

             <!-- Create / Edit Modal -->
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
                <div class="bg-white dark:bg-[#0f172a] border border-slate-250 dark:border-white/10 p-6 rounded-xl w-full max-w-lg shadow-2xl text-slate-800 dark:text-slate-200">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 uppercase tracking-wider">
                        {{ isEdit ? 'Edit Sparepart' : 'Tambah Sparepart Baru' }}
                    </h3>
                    
                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <label class="block text-xs text-slate-505 dark:text-slate-400 mb-1">Nama Suku Cadang</label>
                            <input v-model="form.name" type="text" class="w-full bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded p-2 text-slate-850 dark:text-white outline-none focus:border-amber-550 font-mono" required>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs text-slate-505 dark:text-slate-400 mb-1">Part Number</label>
                                <input v-model="form.part_number" type="text" class="w-full bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded p-2 text-slate-850 dark:text-white outline-none focus:border-amber-550 font-mono" required>
                            </div>
                            <div>
                                <label class="block text-xs text-slate-505 dark:text-slate-400 mb-1">Lokasi Rak</label>
                                <input v-model="form.location" type="text" class="w-full bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded p-2 text-slate-850 dark:text-white outline-none focus:border-amber-550 font-mono" placeholder="e.g. Shelf A-3" required>
                            </div>
                        </div>

                        <!-- Edit Adjustment Option -->
                        <div v-if="isEdit" class="p-3 bg-amber-50 dark:bg-amber-500/5 border border-amber-200 dark:border-amber-500/10 rounded-xl space-y-3">
                            <h4 class="text-xs font-bold text-amber-700 dark:text-amber-400 uppercase tracking-wider font-mono">Penyesuaian Stok (Koreksi)</h4>
                            <div class="flex items-center gap-4">
                                <div class="text-xs text-slate-600 dark:text-slate-400">
                                    Stok saat ini: <strong class="text-slate-800 dark:text-white font-mono">{{ form.stock }}</strong>
                                </div>
                                <div class="flex-1 flex gap-2">
                                    <input 
                                        v-model.number="editAdjustment.adjustment" 
                                        type="number" 
                                        class="w-full bg-white dark:bg-black/40 border border-slate-200 dark:border-white/10 rounded p-1.5 text-center text-slate-850 dark:text-white outline-none focus:border-amber-500 font-mono text-xs"
                                        placeholder="Gunakan +/- (cth: -2, +5)"
                                    />
                                </div>
                            </div>
                            <p class="text-[9px] text-slate-450 dark:text-slate-500 italic leading-snug">Gunakan kolom di atas untuk mengoreksi stok saat ini tanpa menimpa stok secara manual.</p>
                        </div>

                        <div class="grid grid-cols-3 gap-4">
                            <div v-if="!isEdit">
                                <label class="block text-xs text-slate-505 dark:text-slate-400 mb-1">Stok Awal</label>
                                <input v-model="form.stock" type="number" class="w-full bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded p-2 text-slate-850 dark:text-white outline-none focus:border-amber-550 font-mono">
                            </div>
                            <div>
                                <label class="block text-xs text-slate-505 dark:text-slate-400 mb-1">Stok Minimal</label>
                                <input v-model="form.min_stock" type="number" class="w-full bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded p-2 text-slate-850 dark:text-white outline-none focus:border-amber-550 font-mono">
                            </div>
                            <div :class="isEdit ? 'col-span-2' : ''">
                                <label class="block text-xs text-slate-505 dark:text-slate-400 mb-1">Biaya Satuan (Rp)</label>
                                <input v-model="form.unit_cost" type="number" class="w-full bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded p-2 text-slate-850 dark:text-white outline-none focus:border-amber-550 font-mono">
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-4">
                            <button type="button" @click="showModal = false" class="px-4 py-2 text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white bg-slate-50 dark:bg-transparent rounded-lg border border-slate-200 dark:border-transparent cursor-pointer">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-amber-555 text-white rounded-lg hover:bg-amber-500 border-0 cursor-pointer shadow-sm">
                                {{ isEdit ? 'Update Sparepart' : 'Tambah Sparepart' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<style scoped>
.hud-panel {
    backdrop-filter: blur(12px);
}

.perspective-grid {
    background-image: 
        linear-gradient(to right, rgba(245, 158, 11, 0.05) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(245, 158, 11, 0.05) 1px, transparent 1px);
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
    text-shadow: 0 0 10px rgba(245, 158, 11, 0.3);
}
</style>
