<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
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

// Sidebar detail
const selectedLocation = ref(null);
const sidebarOpen = ref(false);
const sidebarLoading = ref(false);
const sidebarStocks = ref([]);

// Heatmap color based on utilization
const getHeatColor = (percent) => {
    if (percent <= 0) return { bg: 'bg-slate-100 dark:bg-slate-800', border: 'border-slate-300 dark:border-slate-700', text: 'text-slate-500 dark:text-slate-400', glow: '' };
    if (percent < 40) return { bg: 'bg-emerald-100 dark:bg-emerald-950/60', border: 'border-emerald-400 dark:border-emerald-600', text: 'text-emerald-700 dark:text-emerald-400', glow: 'shadow-emerald-500/20' };
    if (percent < 70) return { bg: 'bg-amber-100 dark:bg-amber-950/60', border: 'border-amber-400 dark:border-amber-600', text: 'text-amber-700 dark:text-amber-400', glow: 'shadow-amber-500/20' };
    if (percent < 90) return { bg: 'bg-orange-100 dark:bg-orange-950/60', border: 'border-orange-400 dark:border-orange-600', text: 'text-orange-700 dark:text-orange-400', glow: 'shadow-orange-500/20' };
    return { bg: 'bg-rose-100 dark:bg-rose-950/60', border: 'border-rose-400 dark:border-rose-600', text: 'text-rose-700 dark:text-rose-400', glow: 'shadow-rose-500/20' };
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

const formatNumber = (num) => {
    return Number(num || 0).toLocaleString('id-ID');
};

const typeLabel = (type) => {
    const labels = { storage: 'Storage', receiving: 'Receiving', shipping: 'Shipping', production: 'Production' };
    return labels[type] || type;
};
</script>

<template>
    <Head :title="`Warehouse Map — ${warehouse.name}`" />

    <AppLayout title="Warehouse Map">
        <div class="max-w-[1600px] mx-auto">
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
                        <button @click="editMode = false" class="inline-flex items-center gap-2 rounded-xl bg-slate-200 dark:bg-slate-800 px-4 py-2 text-sm font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-slate-700 transition-colors">
                            <XMarkIcon class="h-4 w-4" /> Cancel
                        </button>
                        <button @click="saveLayout" :disabled="saving" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500 transition-colors shadow-lg shadow-emerald-500/20 disabled:opacity-50">
                            <CheckIcon class="h-4 w-4" /> {{ saving ? 'Saving...' : 'Save Layout' }}
                        </button>
                    </template>
                    <template v-else>
                        <button @click="editMode = true" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 transition-colors shadow-lg shadow-blue-500/20">
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
                        <!-- Legend -->
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">Floor Plan</h3>
                            <div class="flex items-center gap-4 text-[10px] font-bold uppercase tracking-wider">
                                <div class="flex items-center gap-1.5">
                                    <div class="w-3 h-3 rounded-sm bg-slate-200 dark:bg-slate-700 border border-slate-400"></div>
                                    <span class="text-slate-500">Empty</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <div class="w-3 h-3 rounded-sm bg-emerald-200 border border-emerald-400"></div>
                                    <span class="text-emerald-600">Low</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <div class="w-3 h-3 rounded-sm bg-amber-200 border border-amber-400"></div>
                                    <span class="text-amber-600">Medium</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <div class="w-3 h-3 rounded-sm bg-orange-200 border border-orange-400"></div>
                                    <span class="text-orange-600">High</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <div class="w-3 h-3 rounded-sm bg-rose-200 border border-rose-400"></div>
                                    <span class="text-rose-600">Full</span>
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
                            }"
                            @drop="onGridDrop"
                            @dragover="onDragOver"
                        >
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
                                :draggable="editMode"
                                @dragstart="(e) => onDragStart(loc, e)"
                                @click="inspectLocation(loc)"
                                class="absolute rounded-xl border-2 p-2 flex flex-col items-center justify-center cursor-pointer transition-all duration-300 hover:scale-[1.03] hover:z-10"
                                :class="[
                                    getHeatColor(loc.utilization_percent).bg,
                                    getHeatColor(loc.utilization_percent).border,
                                    getHeatColor(loc.utilization_percent).glow ? 'shadow-lg ' + getHeatColor(loc.utilization_percent).glow : '',
                                    editMode ? 'cursor-grab active:cursor-grabbing ring-2 ring-blue-400/50 ring-offset-1' : '',
                                    selectedLocation?.id === loc.id ? 'ring-2 ring-blue-500 ring-offset-2 dark:ring-offset-slate-950 scale-[1.03] z-20' : '',
                                ]"
                                :style="{
                                    left: `${((loc.pos_x ?? 0) / gridCols) * 100}%`,
                                    top: `${((loc.pos_y ?? 0) / gridRows) * 100}%`,
                                    width: `${((loc.width || 1) / gridCols) * 100}%`,
                                    height: `${((loc.height || 1) / gridRows) * 100}%`,
                                }"
                            >
                                <span class="text-[11px] font-black uppercase tracking-wider leading-none" :class="getHeatColor(loc.utilization_percent).text">
                                    {{ loc.code }}
                                </span>
                                <span class="text-[9px] font-medium text-slate-500 dark:text-slate-400 mt-0.5 truncate max-w-full">
                                    {{ loc.name }}
                                </span>
                                <!-- Utilization bar -->
                                <div class="w-full mt-1.5 h-1.5 rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden">
                                    <div
                                        class="h-full rounded-full transition-all duration-500"
                                        :class="[
                                            loc.utilization_percent < 40 ? 'bg-emerald-500' :
                                            loc.utilization_percent < 70 ? 'bg-amber-500' :
                                            loc.utilization_percent < 90 ? 'bg-orange-500' : 'bg-rose-500'
                                        ]"
                                        :style="{ width: `${loc.utilization_percent}%` }"
                                    ></div>
                                </div>
                                <span class="text-[8px] font-bold mt-0.5" :class="getHeatColor(loc.utilization_percent).text">
                                    {{ loc.utilization_percent }}%
                                </span>
                            </div>
                        </div>

                        <!-- Edit Mode Hint -->
                        <p v-if="editMode" class="mt-3 text-xs text-blue-500 dark:text-blue-400 font-semibold text-center animate-pulse">
                            🖱️ Drag the blocks to rearrange rack positions, then click "Save Layout"
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
                                    <h3 class="text-base font-black text-slate-900 dark:text-white uppercase tracking-wider">{{ selectedLocation.code }}</h3>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ selectedLocation.name }}</p>
                                </div>
                                <button @click="closeSidebar" class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                    <XMarkIcon class="h-5 w-5 text-slate-400" />
                                </button>
                            </div>

                            <!-- Location Info -->
                            <div class="p-5 border-b border-slate-200 dark:border-slate-800 space-y-3">
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-500 dark:text-slate-400 font-medium">Type</span>
                                    <span class="font-bold text-slate-900 dark:text-white uppercase text-xs tracking-wider bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded">{{ typeLabel(selectedLocation.type) }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-500 dark:text-slate-400 font-medium">Capacity</span>
                                    <span class="font-bold text-slate-900 dark:text-white">{{ formatNumber(selectedLocation.capacity) }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-500 dark:text-slate-400 font-medium">Current Stock</span>
                                    <span class="font-bold text-slate-900 dark:text-white">{{ formatNumber(selectedLocation.total_stock_qty) }}</span>
                                </div>
                                <div>
                                    <div class="flex justify-between text-xs mb-1">
                                        <span class="text-slate-500 dark:text-slate-400 font-medium">Utilization</span>
                                        <span class="font-black" :class="getHeatColor(selectedLocation.utilization_percent).text">{{ selectedLocation.utilization_percent }}%</span>
                                    </div>
                                    <div class="w-full h-2 rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden">
                                        <div
                                            class="h-full rounded-full transition-all duration-500"
                                            :class="[
                                                selectedLocation.utilization_percent < 40 ? 'bg-emerald-500' :
                                                selectedLocation.utilization_percent < 70 ? 'bg-amber-500' :
                                                selectedLocation.utilization_percent < 90 ? 'bg-orange-500' : 'bg-rose-500'
                                            ]"
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
        </div>
    </AppLayout>
</template>
