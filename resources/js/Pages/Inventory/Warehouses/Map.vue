<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import axios from 'axios';
import {
    ArrowLeftIcon,
    MapPinIcon,
    CubeIcon,
    ArrowsPointingOutIcon,
    CheckIcon,
    XMarkIcon,
    InformationCircleIcon,
    ChevronRightIcon,
    PhotoIcon,
    TrashIcon,
    PlusIcon,
    PencilSquareIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    warehouse: Object,
    stats: Object,
});

const gridCols = ref(props.warehouse.grid_cols || 12);
const gridRows = ref(props.warehouse.grid_rows || 8);
const locations = ref(props.warehouse.locations || []);
const editMode = ref(false);
const saving = ref(false);
const dragging = ref(null);
const dragOffset = ref({ x: 0, y: 0 });
const showHelp = ref(false);

// SLoc filter
const selectedSlocFilter = ref('');

// CRUD Location Modal
const showLocationModal = ref(false);
const editingLocation = ref(null);

const locationForm = useForm({
    warehouse_area_id: '',
    code: '',
    name: '',
    type: 'rack',
    capacity: 100,
    color: '#3b82f6',
    width: 2,
    height: 2,
});

// Sidebar detail
const selectedLocation = ref(null);
const sidebarOpen = ref(false);
const sidebarLoading = ref(false);
const sidebarStocks = ref([]);

// Heatmap color based on utilization (suitable for dark-themed glass cards)
const getHeatColor = (percent) => {
    if (percent <= 0) return { border: 'border-slate-800/80', text: 'text-slate-400', bar: 'bg-slate-700', glow: '' };
    if (percent < 40) return { border: 'border-emerald-500/40', text: 'text-emerald-400', bar: 'bg-emerald-500', glow: 'shadow-emerald-500/10' };
    if (percent < 70) return { border: 'border-amber-500/40', text: 'text-amber-400', bar: 'bg-amber-500', glow: 'shadow-amber-500/10' };
    if (percent < 90) return { border: 'border-orange-500/40', text: 'text-orange-400', bar: 'bg-orange-500', glow: 'shadow-orange-500/10' };
    return { border: 'border-rose-500/60', text: 'text-rose-400', bar: 'bg-rose-500', glow: 'shadow-rose-500/20' };
};

// Auto-assign positions if locations have no position
onMounted(() => {
    let needsAutoLayout = locations.value.some(l => l.pos_x === null || l.pos_y === null);
    if (needsAutoLayout) {
        let col = 0, row = 0;
        locations.value.forEach(loc => {
            if (loc.pos_x === null || loc.pos_y === null) {
                loc.pos_x = col;
                loc.pos_y = row;
                col += 2;
                if (col >= gridCols.value) {
                    col = 0;
                    row += 2;
                }
            }
        });
    }
});

// Click to inspect
const inspectLocation = async (loc) => {
    selectedLocation.value = loc;
    sidebarOpen.value = true;
    sidebarLoading.value = true;
    sidebarStocks.value = [];

    try {
        const res = await axios.get(`/inventory/locations/${loc.id}/detail`);
        sidebarStocks.value = res.data.stocks;
    } catch (e) {
        console.error(e);
    } finally {
        sidebarLoading.value = false;
    }
};

const closeSidebar = () => {
    sidebarOpen.value = false;
    selectedLocation.value = null;
};

// Drag & Drop (edit mode)
const onDragStart = (loc, event) => {
    if (!editMode.value) return;
    dragging.value = loc;
    event.dataTransfer.effectAllowed = 'move';
};

const onGridDrop = (event) => {
    if (!editMode.value || !dragging.value) return;
    const grid = event.currentTarget;
    const rect = grid.getBoundingClientRect();
    const cellW = rect.width / gridCols.value;
    const cellH = rect.height / gridRows.value;
    const x = Math.floor((event.clientX - rect.left) / cellW);
    const y = Math.floor((event.clientY - rect.top) / cellH);

    const loc = locations.value.find(l => l.id === dragging.value.id);
    if (loc) {
        loc.pos_x = Math.max(0, Math.min(x, gridCols.value - (loc.width || 1)));
        loc.pos_y = Math.max(0, Math.min(y, gridRows.value - (loc.height || 1)));
    }
    dragging.value = null;
};

const onDragOver = (e) => {
    if (editMode.value) e.preventDefault();
};

// Save layout
const saveLayout = () => {
    saving.value = true;
    router.post(`/inventory/warehouses/${props.warehouse.id}/update-layout`, {
        locations: locations.value.map(l => ({
            id: l.id,
            pos_x: l.pos_x ?? 0,
            pos_y: l.pos_y ?? 0,
            width: l.width || 1,
            height: l.height || 1,
        })),
        grid_cols: gridCols.value,
        grid_rows: gridRows.value,
    }, {
        preserveState: true,
        onFinish: () => {
            saving.value = false;
            editMode.value = false;
        },
    });
};

// Background Image Upload
const bgFileInput = ref(null);
const uploadingBg = ref(false);

const triggerBgUpload = () => {
    bgFileInput.value?.click();
};

const handleBgUpload = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    uploadingBg.value = true;
    router.post(`/inventory/warehouses/${props.warehouse.id}/map-background`, {
        image: file,
        _method: 'POST',
    }, {
        forceFormData: true,
        preserveState: true,
        onFinish: () => {
            uploadingBg.value = false;
            if (bgFileInput.value) bgFileInput.value.value = '';
        },
    });
};

const removeBackground = () => {
    if (confirm('Are you sure you want to remove the floor plan background?')) {
        router.delete(`/inventory/warehouses/${props.warehouse.id}/map-background`, {
            preserveState: true,
        });
    }
};

const openLocationModal = (loc = null) => {
    editingLocation.value = loc;
    if (loc) {
        locationForm.warehouse_area_id = loc.warehouse_area_id || '';
        locationForm.code = loc.code;
        locationForm.name = loc.name;
        locationForm.type = loc.type || 'rack';
        locationForm.capacity = loc.capacity || 100;
        locationForm.color = loc.color || '#3b82f6';
        locationForm.width = loc.width || 2;
        locationForm.height = loc.height || 2;
    } else {
        locationForm.reset();
        locationForm.color = '#3b82f6';
        locationForm.capacity = 100;
        locationForm.type = 'rack';
        locationForm.width = 2;
        locationForm.height = 2;
    }
    showLocationModal.value = true;
};

const closeLocationModal = () => {
    showLocationModal.value = false;
    locationForm.reset();
    editingLocation.value = null;
};

const submitLocation = () => {
    if (editingLocation.value) {
        locationForm.put(`/inventory/locations/${editingLocation.value.id}`, {
            preserveState: true,
            onSuccess: () => {
                const updatedLoc = locations.value.find(l => l.id === editingLocation.value.id);
                if (updatedLoc) {
                    updatedLoc.warehouse_area_id = locationForm.warehouse_area_id;
                    updatedLoc.code = locationForm.code;
                    updatedLoc.name = locationForm.name;
                    updatedLoc.type = locationForm.type;
                    updatedLoc.capacity = locationForm.capacity;
                    updatedLoc.color = locationForm.color;
                    updatedLoc.width = parseInt(locationForm.width || 2);
                    updatedLoc.height = parseInt(locationForm.height || 2);
                    
                    // Keep bounds in grid
                    updatedLoc.pos_x = Math.max(0, Math.min(updatedLoc.pos_x, gridCols.value - updatedLoc.width));
                    updatedLoc.pos_y = Math.max(0, Math.min(updatedLoc.pos_y, gridRows.value - updatedLoc.height));
                }
                closeLocationModal();
                router.reload({ only: ['warehouse'] });
            },
        });
    } else {
        locationForm.post(`/inventory/warehouses/${props.warehouse.id}/locations`, {
            preserveState: true,
            onSuccess: () => {
                closeLocationModal();
                router.reload({ only: ['warehouse'] });
            },
        });
    }
};

const deleteLocation = (loc) => {
    if (confirm(`Apakah Anda yakin ingin menghapus lokasi "${loc.code}"?`)) {
        router.delete(`/inventory/locations/${loc.id}`, {
            preserveState: true,
            onSuccess: () => {
                if (selectedLocation.value?.id === loc.id) {
                    closeSidebar();
                }
                router.reload({ only: ['warehouse'] });
            },
        });
    }
};

const getAreaColor = (code) => {
    const colors = {
        'SP01': '#3b82f6',
        'PKG1': '#10b981',
        'TOOL': '#8b5cf6',
        'RM01': '#3b82f6',
        'RM02': '#10b981',
        'RM03': '#8b5cf6',
        'WIP1': '#3b82f6',
        'WIP2': '#10b981',
        'WIP3': '#eab308',
        'WIP4': '#ec4899',
        'FG01': '#3b82f6',
        'FG02': '#eab308',
        'FG03': '#ec4899',
        'LDB1': '#f97316',
        'LDB2': '#f97316',
    };
    return colors[code] || '#94a3b8';
};

const formatNumber = (num) => {
    return Number(num || 0).toLocaleString('id-ID');
};

const typeLabel = (type) => {
    const labels = { 
        storage: 'Storage', 
        receiving: 'Receiving', 
        shipping: 'Shipping', 
        production: 'Production',
        rack: 'Rack / Bay',
        transit: 'Transit Area'
    };
    return labels[type] || type;
};
</script>

<template>
    <Head :title="`Warehouse Map — ${warehouse.name}`" />

    <AppLayout title="Warehouse Map">
        <div class="max-w-[1600px] mx-auto">
            <!-- Hidden File Input for Background Upload -->
            <input 
                type="file" 
                ref="bgFileInput" 
                class="hidden" 
                accept="image/*" 
                @change="handleBgUpload" 
            />

            <!-- Top Nav -->
            <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4 print:hidden">
                <div class="flex items-center gap-4">
                    <Link :href="`/inventory/warehouses`" class="inline-flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white shrink-0">
                        <ArrowLeftIcon class="h-4 w-4" />
                        Back to Warehouses
                    </Link>
                    <div class="hidden md:block h-5 w-px bg-slate-300 dark:bg-slate-700"></div>
                    <div>
                        <h2 class="text-lg font-black text-slate-900 dark:text-white tracking-tight">{{ warehouse.name }}</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ warehouse.code }} · {{ warehouse.type?.toUpperCase() }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <!-- Help Button -->
                    <button @click="showHelp = true" class="inline-flex items-center justify-center p-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 hover:text-blue-500 transition-colors" title="Cara Penggunaan">
                        <InformationCircleIcon class="h-5 w-5" />
                    </button>

                    <template v-if="editMode">
                        <button @click="openLocationModal()" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-500 transition-colors shadow-lg shadow-blue-500/20 mr-2 shrink-0">
                            <PlusIcon class="h-4 w-4" /> Add Location
                        </button>
                        <div class="flex items-center bg-slate-100 dark:bg-slate-800 rounded-xl p-1 mr-2">
                            <button @click="triggerBgUpload" :disabled="uploadingBg" class="inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-700 hover:shadow-sm transition-all disabled:opacity-50" title="Upload Blueprint Background">
                                <PhotoIcon class="h-4 w-4" :class="uploadingBg ? 'animate-pulse' : ''" /> {{ uploadingBg ? 'Uploading...' : 'Set Background' }}
                            </button>
                            <button v-if="warehouse.map_background_url" @click="removeBackground" class="inline-flex items-center justify-center p-1.5 rounded-lg text-rose-500 hover:bg-rose-100 dark:hover:bg-rose-500/20 transition-colors" title="Remove Background">
                                <TrashIcon class="h-4 w-4" />
                            </button>
                        </div>
                        <button @click="editMode = false" class="inline-flex items-center gap-2 rounded-xl bg-slate-200 dark:bg-slate-800 px-4 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-slate-700 transition-colors">
                            <XMarkIcon class="h-4 w-4" /> Cancel
                        </button>
                        <button @click="saveLayout" :disabled="saving" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-500 transition-colors shadow-lg shadow-emerald-500/20 disabled:opacity-50">
                            <CheckIcon class="h-4 w-4" /> {{ saving ? 'Saving...' : 'Save Layout' }}
                        </button>
                    </template>
                    <template v-else>
                        <!-- SLoc Filter Dropdown -->
                        <div class="flex items-center gap-2 mr-2">
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">SLoc:</label>
                            <select
                                v-model="selectedSlocFilter"
                                class="rounded-xl border-0 bg-slate-100 dark:bg-slate-800 py-2 px-4 text-xs font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                            >
                                <option value="">All SLocs</option>
                                <option v-for="area in warehouse.areas" :key="area.id" :value="area.id">
                                    [{{ area.code }}] {{ area.name }}
                                </option>
                            </select>
                        </div>
                        <button @click="editMode = true" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-500 transition-colors shadow-lg shadow-blue-500/20">
                            <ArrowsPointingOutIcon class="h-4 w-4" /> Edit Layout
                        </button>
                    </template>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
                    <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1">Total Locations</p>
                    <p class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">{{ stats.total_locations }}</p>
                </div>
                <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
                    <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1">Total Capacity</p>
                    <p class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">{{ formatNumber(stats.total_capacity) }}</p>
                </div>
                <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
                    <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1">Total Stock</p>
                    <p class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">{{ formatNumber(stats.total_stock) }}</p>
                </div>
                <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
                    <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1">Avg Utilization</p>
                    <p class="text-3xl font-black tracking-tight" :class="stats.avg_utilization > 80 ? 'text-rose-500' : stats.avg_utilization > 50 ? 'text-amber-500' : 'text-emerald-500'">{{ stats.avg_utilization }}%</p>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="flex gap-6">
                <!-- Map Canvas -->
                <div class="flex-1 min-w-0">
                    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 shadow-xl overflow-hidden">
                        <!-- Legends -->
                        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-4 pb-4 border-b border-slate-100 dark:border-slate-800">
                            <div>
                                <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">Floor Plan Layout</h3>
                            </div>
                            <div class="flex flex-wrap items-center gap-6 text-[10px] font-bold uppercase tracking-wider">
                                <!-- SLoc Legend -->
                                <div class="flex flex-wrap items-center gap-3 pr-4 border-r border-slate-200 dark:border-slate-800" v-if="warehouse.areas?.length > 0">
                                    <span class="text-slate-400">SLocs:</span>
                                    <div v-for="area in warehouse.areas" :key="area.id" class="flex items-center gap-1.5">
                                        <span class="w-2.5 h-2.5 rounded-full inline-block" :style="{ backgroundColor: getAreaColor(area.code) }"></span>
                                        <span class="text-slate-700 dark:text-slate-300 font-mono">{{ area.code }}</span>
                                    </div>
                                </div>

                                <!-- Heatmap Legend -->
                                <div class="flex items-center gap-3">
                                    <span class="text-slate-400">Utilization:</span>
                                    <div class="flex items-center gap-1.5">
                                        <div class="w-3 h-3 rounded-sm bg-slate-800 border border-slate-700"></div>
                                        <span class="text-slate-500">Empty</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <div class="w-3 h-3 rounded-sm bg-emerald-500/20 border border-emerald-500/40"></div>
                                        <span class="text-emerald-500">Low</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <div class="w-3 h-3 rounded-sm bg-amber-500/20 border border-amber-500/40"></div>
                                        <span class="text-amber-500 font-bold">Med</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <div class="w-3 h-3 rounded-sm bg-orange-500/20 border border-orange-500/40"></div>
                                        <span class="text-orange-500 font-bold">High</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <div class="w-3 h-3 rounded-sm bg-rose-500/20 border border-rose-500/60"></div>
                                        <span class="text-rose-500 font-bold">Full</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Grid -->
                        <div
                            class="relative border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-950/50 overflow-hidden"
                            :style="{
                                display: 'grid',
                                gridTemplateColumns: `repeat(${gridCols}, 1fr)`,
                                gridTemplateRows: `repeat(${gridRows}, 1fr)`,
                                aspectRatio: `${gridCols} / ${gridRows}`,
                                backgroundImage: warehouse.map_background_url ? `url('${warehouse.map_background_url}')` : 'none',
                                backgroundSize: '100% 100%',
                                backgroundPosition: 'center',
                                backgroundRepeat: 'no-repeat',
                            }"
                            @drop="onGridDrop"
                            @dragover="onDragOver"
                        >
                            <!-- Grid Background overlay (makes blueprint slightly dimmed) -->
                            <div v-if="warehouse.map_background_url" class="absolute inset-0 bg-white/40 dark:bg-slate-950/60 mix-blend-overlay pointer-events-none"></div>

                            <!-- Grid Background Lines -->
                            <template v-for="row in gridRows" :key="'row-' + row">
                                <template v-for="col in gridCols" :key="'cell-' + row + '-' + col">
                                    <div
                                        class="border-r border-b border-slate-200/50 dark:border-slate-800/50"
                                        :style="{ gridColumn: col, gridRow: row }"
                                    ></div>
                                </template>
                            </template>

                            <!-- Location Blocks -->
                            <div
                                v-for="loc in locations"
                                :key="loc.id"
                                :draggable="editMode ? 'true' : 'false'"
                                @dragstart="(e) => onDragStart(loc, e)"
                                @click="editMode ? openLocationModal(loc) : inspectLocation(loc)"
                                class="absolute rounded-xl border-2 p-3 flex flex-col items-center justify-center cursor-pointer transition-all duration-300 hover:scale-[1.03] hover:z-10 bg-slate-950/85 backdrop-blur-sm shadow-xl"
                                :class="[
                                    getHeatColor(loc.utilization_percent).border,
                                    getHeatColor(loc.utilization_percent).glow ? 'shadow-lg ' + getHeatColor(loc.utilization_percent).glow : '',
                                    editMode ? 'cursor-grab active:cursor-grabbing ring-2 ring-blue-500/50 ring-offset-1 border-blue-400 border-dashed' : '',
                                    selectedLocation?.id === loc.id ? 'ring-2 ring-blue-500 ring-offset-2 dark:ring-offset-slate-950 scale-[1.03] z-20' : '',
                                    selectedSlocFilter && loc.warehouse_area_id != selectedSlocFilter ? 'opacity-10 dark:opacity-20 scale-95 border-dashed pointer-events-none' : '',
                                ]"
                                :style="{
                                    left: `${((loc.pos_x ?? 0) / gridCols) * 100}%`,
                                    top: `${((loc.pos_y ?? 0) / gridRows) * 100}%`,
                                    width: `${((loc.width || 1) / gridCols) * 100}%`,
                                    height: `${((loc.height || 1) / gridRows) * 100}%`,
                                    borderColor: loc.color ? `${loc.color}aa` : undefined
                                }"
                            >
                                <!-- Sloc pill and color dot -->
                                <div class="flex items-center gap-1 mb-1" v-if="loc.warehouse_area">
                                    <span class="w-1.5 h-1.5 rounded-full inline-block shrink-0" :style="{ backgroundColor: getAreaColor(loc.warehouse_area.code) }"></span>
                                    <span class="text-[8px] px-1 py-0.2 rounded bg-slate-800 text-slate-300 font-bold font-mono">
                                        {{ loc.warehouse_area.code }}
                                    </span>
                                </div>

                                <span class="text-[11px] font-black uppercase tracking-wider leading-none text-white">
                                    {{ loc.code }}
                                </span>
                                <span class="text-[9px] font-medium text-slate-400 mt-1 truncate max-w-full">
                                    {{ loc.name }}
                                </span>
                                <!-- Utilization bar -->
                                <div class="w-full mt-2 h-1 rounded-full bg-slate-800 overflow-hidden">
                                    <div
                                        class="h-full rounded-full transition-all duration-500"
                                        :class="getHeatColor(loc.utilization_percent).bar"
                                        :style="{ width: `${loc.utilization_percent}%` }"
                                    ></div>
                                </div>
                                <span class="text-[8px] font-bold mt-1" :class="getHeatColor(loc.utilization_percent).text">
                                    {{ loc.utilization_percent }}%
                                </span>
                            </div>
                        </div>

                        <!-- Edit Mode Hint -->
                        <p v-if="editMode" class="mt-3 text-xs text-blue-500 dark:text-blue-400 font-semibold text-center animate-pulse">
                            🖱️ Drag block untuk mengatur posisi tata letak rak, klik rak untuk mengubah ukuran (lebar/tinggi) & detail, lalu klik "Save Layout"
                        </p>
                    </div>
                </div>

                <!-- Sidebar Inspector -->
                <Transition
                    enter-active-class="transition ease-out duration-300 transform"
                    enter-from-class="translate-x-full opacity-0"
                    enter-to-class="translate-x-0 opacity-100"
                    leave-active-class="transition ease-in duration-200 transform"
                    leave-from-class="translate-x-0 opacity-100"
                    leave-to-class="translate-x-full opacity-0"
                >
                    <div v-if="sidebarOpen && selectedLocation" class="w-96 shrink-0">
                        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xl overflow-hidden sticky top-24">
                            <!-- Sidebar Header -->
                            <div class="p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                                <div>
                                    <h3 class="text-base font-black text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                                        {{ selectedLocation.code }}
                                        <button @click="openLocationModal(selectedLocation)" class="p-1 rounded text-slate-400 hover:text-blue-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" title="Edit Detail">
                                            <PencilSquareIcon class="h-4 w-4" />
                                        </button>
                                    </h3>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ selectedLocation.name }}</p>
                                </div>
                                <button @click="closeSidebar" class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                    <XMarkIcon class="h-5 w-5 text-slate-400" />
                                </button>
                            </div>

                            <!-- Location Info -->
                            <div class="p-5 border-b border-slate-200 dark:border-slate-800 space-y-3">
                                <div class="flex justify-between items-start text-sm" v-if="selectedLocation.warehouse_area">
                                    <span class="text-slate-500 dark:text-slate-400 font-medium">SLoc</span>
                                    <span class="font-bold text-slate-900 dark:text-white font-mono bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded text-xs text-right max-w-[200px]">
                                        [{{ selectedLocation.warehouse_area.code }}] {{ selectedLocation.warehouse_area.name }}
                                    </span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-500 dark:text-slate-400 font-medium">Type</span>
                                    <span class="font-bold text-slate-900 dark:text-white uppercase text-xs tracking-wider bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded">{{ typeLabel(selectedLocation.type) }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-500 dark:text-slate-400 font-medium">Capacity</span>
                                    <span class="font-bold text-slate-900 dark:text-white">{{ formatNumber(selectedLocation.capacity) }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-700 dark:text-slate-400 font-medium">Current Stock</span>
                                    <span class="font-bold text-slate-900 dark:text-white">{{ formatNumber(selectedLocation.total_stock_qty) }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-500 dark:text-slate-400 font-medium">Grid Dimension</span>
                                    <span class="font-bold text-slate-900 dark:text-white font-mono text-xs">
                                        L: {{ selectedLocation.width || 1 }} x T: {{ selectedLocation.height || 1 }} (Grid Units)
                                    </span>
                                </div>
                                <div>
                                    <div class="flex justify-between text-xs mb-1">
                                        <span class="text-slate-500 dark:text-slate-400 font-medium">Utilization</span>
                                        <span class="font-black" :class="getHeatColor(selectedLocation.utilization_percent).text">{{ selectedLocation.utilization_percent }}%</span>
                                    </div>
                                    <div class="w-full h-2 rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden">
                                        <div
                                            class="h-full rounded-full transition-all duration-500"
                                            :class="getHeatColor(selectedLocation.utilization_percent).bar"
                                            :style="{ width: `${selectedLocation.utilization_percent}%` }"
                                        ></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Stock Items -->
                            <div class="p-5">
                                <h4 class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-3">Items in this Rack</h4>

                                <div v-if="sidebarLoading" class="flex items-center justify-center py-8">
                                    <div class="animate-spin rounded-full h-6 w-6 border-2 border-blue-500 border-t-transparent"></div>
                                </div>

                                <div v-else-if="sidebarStocks.length === 0" class="text-center py-8">
                                    <CubeIcon class="h-8 w-8 text-slate-300 dark:text-slate-600 mx-auto mb-2" />
                                    <p class="text-xs text-slate-400 italic">No items stored here</p>
                                </div>

                                <div v-else class="space-y-2 max-h-80 overflow-y-auto pr-1">
                                    <div
                                        v-for="stock in sidebarStocks"
                                        :key="stock.id"
                                        class="rounded-xl border border-slate-200 dark:border-slate-700 p-3 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors"
                                    >
                                        <div class="flex justify-between items-start">
                                            <div class="min-w-0 flex-1">
                                                <p class="text-sm font-bold text-slate-900 dark:text-white truncate">{{ stock.product_name }}</p>
                                                <p class="text-[10px] text-slate-500 dark:text-slate-400 font-mono mt-0.5">{{ stock.sku }}</p>
                                            </div>
                                            <div class="text-right shrink-0 ml-3">
                                                <p class="text-sm font-black text-slate-900 dark:text-white">{{ formatNumber(stock.qty_on_hand) }}</p>
                                                <p class="text-[10px] text-slate-400 font-bold">{{ stock.unit }}</p>
                                            </div>
                                        </div>
                                        <div class="flex gap-4 mt-2 text-[10px]">
                                            <div>
                                                <span class="text-slate-400">Reserved: </span>
                                                <span class="font-bold text-amber-500">{{ formatNumber(stock.qty_reserved) }}</span>
                                            </div>
                                            <div>
                                                <span class="text-slate-400">Available: </span>
                                                <span class="font-bold text-emerald-500">{{ formatNumber(stock.available) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>

            <!-- Help / Instruction Modal -->
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div v-if="showHelp" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 bg-slate-900/50 backdrop-blur-sm">
                    <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden border border-slate-200 dark:border-slate-800">
                        <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center bg-slate-50 dark:bg-slate-800/50">
                            <h3 class="text-lg font-black text-slate-900 dark:text-white flex items-center gap-2 tracking-tight">
                                <InformationCircleIcon class="h-6 w-6 text-blue-500" /> 
                                Panduan Penggunaan Warehouse Map
                            </h3>
                            <button @click="showHelp = false" class="p-2 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                                <XMarkIcon class="h-5 w-5 text-slate-500" />
                            </button>
                        </div>
                        <div class="p-6 text-sm text-slate-600 dark:text-slate-300 space-y-6">
                            
                            <div class="flex gap-4">
                                <div class="shrink-0 flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900/50 dark:text-blue-400 font-black">1</div>
                                <div>
                                    <h4 class="font-bold text-slate-900 dark:text-white text-base mb-1">Kenapa beberapa peta gudang terlihat sama / hanya ada 1 blok besar?</h4>
                                    <p>Peta ini menggambarkan <strong>Master Data Lokasi (Rak/Bin)</strong> di dalam gudang. Jika sebuah gudang baru dibuat dan belum dibagi-bagi menjadi beberapa rak penyimpanan spesifik, sistem hanya akan membuat 1 lokasi bawaan ("General"/GEN).</p>
                                    <p class="mt-2 text-xs text-slate-500 bg-slate-50 dark:bg-slate-800 p-2 rounded-lg">💡 <strong>Solusi:</strong> Tambahkan lebih banyak rak/lokasi untuk gudang ini di pengaturan Master Data terlebih dahulu.</p>
                                </div>
                            </div>

                            <div class="flex gap-4">
                                <div class="shrink-0 flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900/50 dark:text-blue-400 font-black">2</div>
                                <div>
                                    <h4 class="font-bold text-slate-900 dark:text-white text-base mb-1">Mengatur Tata Letak (Drag & Drop)</h4>
                                    <p>Untuk mengatur posisi letak rak agar sesuai dengan kondisi fisik gudang, klik tombol <strong><ArrowsPointingOutIcon class="h-4 w-4 inline" /> Edit Layout</strong> di pojok kanan atas. Setelah itu, geser (drag) kotak rak ke posisi yang di inginkan pada grid canvas lantai, lalu klik <strong><CheckIcon class="h-4 w-4 inline" /> Save Layout</strong>.</p>
                                </div>
                            </div>

                            <div class="flex gap-4">
                                <div class="shrink-0 flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900/50 dark:text-blue-400 font-black">3</div>
                                <div>
                                    <h4 class="font-bold text-slate-900 dark:text-white text-base mb-1">Melihat Daftar Barang di dalam Rak</h4>
                                    <p>Klik kotak mana saja pada peta untuk seketika memunculkan Panel Detail di sisi kanan layar. Panel tersebut menunjukkan persentase pemakaian kapasitas rak ("Utilization") beserta semua daftar spesifik barang yang saat ini tersimpan di posisi tersebut.</p>
                                </div>
                            </div>

                        </div>
                        <div class="p-4 bg-slate-50 dark:bg-slate-800/80 border-t border-slate-200 dark:border-slate-800 flex justify-end">
                            <button @click="showHelp = false" class="px-6 py-2 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-xl shadow-lg shadow-blue-500/20 transition">Saya Mengerti</button>
                        </div>
                    </div>
                </div>
            </Transition>

            <!-- Location CRUD Modal -->
            <Modal :show="showLocationModal" @close="closeLocationModal">
                <div class="p-6">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                        <h3 class="text-lg font-black text-slate-900 dark:text-white">
                            {{ editingLocation ? 'Edit Detail Lokasi' : 'Tambah Lokasi Baru' }}
                        </h3>
                        <button @click="closeLocationModal" class="p-1 rounded-lg text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">
                            <XMarkIcon class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="mt-4 space-y-4">
                        <!-- SLoc Dropdown -->
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Storage Location (SLoc)</label>
                            <select
                                v-model="locationForm.warehouse_area_id"
                                class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                            >
                                <option value="">Pilih SLoc...</option>
                                <option v-for="area in warehouse.areas" :key="area.id" :value="area.id">
                                    [{{ area.code }}] {{ area.name }}
                                </option>
                            </select>
                            <div v-if="locationForm.errors.warehouse_area_id" class="mt-1 text-xs text-red-400">{{ locationForm.errors.warehouse_area_id }}</div>
                        </div>

                        <!-- Code -->
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Kode Lokasi (RACK/BIN)</label>
                            <input
                                v-model="locationForm.code"
                                type="text"
                                required
                                class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 font-mono"
                                placeholder="e.g. FG01-A1, RM01-C3"
                            />
                            <div v-if="locationForm.errors.code" class="mt-1 text-xs text-red-400">{{ locationForm.errors.code }}</div>
                        </div>

                        <!-- Name -->
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Nama / Deskripsi Lokasi</label>
                            <input
                                v-model="locationForm.name"
                                type="text"
                                required
                                class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                placeholder="e.g. Row A1 - Slitted Coil Yard"
                            />
                            <div v-if="locationForm.errors.name" class="mt-1 text-xs text-red-400">{{ locationForm.errors.name }}</div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <!-- Type -->
                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Tipe Lokasi</label>
                                <select
                                    v-model="locationForm.type"
                                    required
                                    class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                >
                                    <option value="rack">Rack / Bay</option>
                                    <option value="storage">Storage</option>
                                    <option value="transit">Transit Area</option>
                                    <option value="receiving">Receiving</option>
                                    <option value="shipping">Shipping</option>
                                    <option value="production">Production</option>
                                </select>
                            </div>

                            <!-- Capacity -->
                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Kapasitas Maksimal</label>
                                <input
                                    v-model="locationForm.capacity"
                                    type="number"
                                    min="1"
                                    required
                                    class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                />
                                <div v-if="locationForm.errors.capacity" class="mt-1 text-xs text-red-400">{{ locationForm.errors.capacity }}</div>
                            </div>
                        </div>

                        <!-- Card Resizing Options (Lebar & Tinggi) -->
                        <div class="grid grid-cols-2 gap-4 p-3 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-200 dark:border-slate-800">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1.5">Lebar Lokasi (Grid)</label>
                                <div class="flex items-center gap-2">
                                    <input
                                        v-model="locationForm.width"
                                        type="number"
                                        min="1"
                                        max="24"
                                        required
                                        class="block w-full rounded-xl border-0 bg-white dark:bg-slate-900 py-2 px-3 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 text-center font-bold"
                                    />
                                    <span class="text-xs text-slate-400 font-bold">Kolom</span>
                                </div>
                                <div v-if="locationForm.errors.width" class="mt-1 text-xs text-red-400">{{ locationForm.errors.width }}</div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1.5">Tinggi Lokasi (Grid)</label>
                                <div class="flex items-center gap-2">
                                    <input
                                        v-model="locationForm.height"
                                        type="number"
                                        min="1"
                                        max="24"
                                        required
                                        class="block w-full rounded-xl border-0 bg-white dark:bg-slate-900 py-2 px-3 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 text-center font-bold"
                                    />
                                    <span class="text-xs text-slate-400 font-bold">Baris</span>
                                </div>
                                <div v-if="locationForm.errors.height" class="mt-1 text-xs text-red-400">{{ locationForm.errors.height }}</div>
                            </div>
                            <p class="col-span-2 text-[10px] text-slate-400 dark:text-slate-500 font-semibold italic mt-1 text-center">
                                * Semakin besar angkanya, semakin lebar/tinggi kotak lokasi digambarkan di peta.
                            </p>
                        </div>

                        <!-- Color -->
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Warna Identifikasi Visual</label>
                            <div class="flex items-center gap-3">
                                <input
                                    v-model="locationForm.color"
                                    type="color"
                                    class="w-12 h-10 border-0 rounded-xl bg-transparent cursor-pointer"
                                />
                                <input
                                    v-model="locationForm.color"
                                    type="text"
                                    class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 font-mono"
                                    placeholder="#3b82f6"
                                />
                            </div>
                            <div v-if="locationForm.errors.color" class="mt-1 text-xs text-red-400">{{ locationForm.errors.color }}</div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-between items-center bg-slate-50 dark:bg-slate-950 p-4 -mx-6 -mb-6 border-t border-slate-200 dark:border-slate-800 rounded-b-3xl">
                        <div>
                            <button
                                v-if="editingLocation"
                                type="button"
                                class="inline-flex items-center gap-1.5 px-3 py-2 border border-red-200 hover:bg-red-50 text-red-600 rounded-xl text-xs font-bold transition-all"
                                @click="deleteLocation(editingLocation)"
                            >
                                <TrashIcon class="h-4 w-4" /> Hapus Lokasi
                            </button>
                        </div>
                        <div class="flex gap-2">
                            <button
                                type="button"
                                class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold rounded-xl text-xs transition"
                                @click="closeLocationModal"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-xl text-xs shadow-lg shadow-blue-500/20 transition disabled:opacity-50"
                                :disabled="locationForm.processing"
                                @click="submitLocation"
                            >
                                {{ locationForm.processing ? 'Menyimpan...' : 'Simpan Detail' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Modal>
        </div>
    </AppLayout>
</template>
