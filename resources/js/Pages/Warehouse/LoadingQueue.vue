<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, usePage, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    CubeIcon,
    TruckIcon,
    MagnifyingGlassIcon,
    CheckCircleIcon,
    ClockIcon,
    ArrowPathIcon,
    FunnelIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    deliveryOrders: Array,
    warehouses: Array,
    filters: Object,
});

const page = usePage();
const search = ref(props.filters?.search || '');
const warehouseFilter = ref(props.filters?.warehouse_id || '');
const processingId = ref(null);
const selectedOrder = ref(null);
const showManageItemsModal = ref(false);
const revisionForm = useForm({
    item_id: null,
    qty: 0,
    reason: '',
});

const openManageItems = (order) => {
    selectedOrder.value = order;
    showManageItemsModal.value = true;
};

const updateItemQty = (item) => {
    if (processingId.value) return;
    
    const newQty = prompt(`Revisi Qty untuk ${item.product.name}\nQty Order: ${item.qty_ordered}\nQty Baru:`, item.qty_delivered);
    
    if (newQty === null) return;
    if (isNaN(newQty) || newQty < 0) {
        alert('Quantity tidak valid.');
        return;
    }
    if (parseFloat(newQty) > item.qty_ordered) {
        alert('Quantity tidak boleh melebihi Qty Order.');
        return;
    }

    const reason = prompt('Alasan Revisi:');
    
    revisionForm.item_id = item.id;
    revisionForm.qty = parseFloat(newQty);
    revisionForm.reason = reason || '';
    
    processingId.value = selectedOrder.value.id;
    
    revisionForm.put(route('warehouse.loading.update-item-qty', selectedOrder.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            // Update local state to reflect change without full reload if possible, 
            // but Inertia will reload the props anyway.
            const updatedOrder = page.props.deliveryOrders.find(o => o.id === selectedOrder.value.id);
            if (updatedOrder) selectedOrder.value = updatedOrder;
        },
        onFinish: () => processingId.value = null,
    });
};

const applyFilters = () => {
    router.get(route('warehouse.loading.index'), {
        search: search.value || undefined,
        warehouse_id: warehouseFilter.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const draftOrders = computed(() => props.deliveryOrders.filter(o => o.status === 'draft'));
const pickingOrders = computed(() => props.deliveryOrders.filter(o => o.status === 'picking'));

const startLoading = (order) => {
    if (!confirm('📦 START LOADING\n\nItems for DO ' + order.do_number + ' will start being picked and loaded.\n\nContinue?')) return;
    processingId.value = order.id;
    router.patch(route('warehouse.loading.update-status', order.id), {
        status: 'picking'
    }, {
        preserveScroll: true,
        onFinish: () => processingId.value = null,
    });
};

const finishLoading = (order) => {
    if (!confirm('✅ FINISH LOADING\n\nAll items for DO ' + order.do_number + ' have been loaded into the truck.\nPlease ensure all items are checked.\n\nContinue?')) return;
    processingId.value = order.id;
    router.patch(route('warehouse.loading.update-status', order.id), {
        status: 'packed'
    }, {
        preserveScroll: true,
        onFinish: () => processingId.value = null,
    });
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

const toggleItemLoaded = (item) => {
    if (processingId.value) return;
    
    router.patch(route('warehouse.loading.toggle-item-loaded', selectedOrder.value.id), {
        item_id: item.id,
        is_loaded: !item.is_loaded
    }, {
        preserveScroll: true,
        onSuccess: () => {
            const updatedOrder = page.props.deliveryOrders.find(o => o.id === selectedOrder.value.id);
            if (updatedOrder) selectedOrder.value = updatedOrder;
        },
    });
};

const calculateProgress = (order) => {
    if (!order.items || order.items.length === 0) return 0;
    const loadedCount = order.items.filter(item => item.is_loaded).length;
    return Math.round((loadedCount / order.items.length) * 100);
};

const getLoadedCount = (order) => {
    if (!order.items) return 0;
    return order.items.filter(item => item.is_loaded).length;
};
</script>

<template>
    <Head title="Warehouse Loading Queue" />

    <AppLayout title="Loading Queue">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">📦 Loading Queue</h1>
                    <p class="text-sm text-slate-500 mt-1">Antrian barang yang siap dimuat ke truk. Klik tombol untuk update status.</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2 bg-amber-500/10 text-amber-600 px-3 py-1.5 rounded-xl text-xs font-bold border border-amber-500/20">
                        <ClockIcon class="h-4 w-4" />
                        {{ draftOrders.length }} Antri
                    </div>
                    <div class="flex items-center gap-2 bg-blue-500/10 text-blue-600 px-3 py-1.5 rounded-xl text-xs font-bold border border-blue-500/20">
                        <CubeIcon class="h-4 w-4" />
                        {{ pickingOrders.length }} Loading
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="glass-card rounded-2xl p-4 mb-6 flex flex-col sm:flex-row gap-3 items-center border border-slate-200 dark:border-slate-800">
                <div class="relative flex-1 w-full">
                    <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                    <input
                        v-model="search"
                        @keyup.enter="applyFilters"
                        type="text"
                        placeholder="Cari DO / Customer..."
                        class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 pl-10 py-2.5 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                    />
                </div>
                <select
                    v-model="warehouseFilter"
                    @change="applyFilters"
                    class="rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                >
                    <option value="">Semua Warehouse</option>
                    <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
                </select>
                <button @click="applyFilters" class="px-4 py-2.5 rounded-xl bg-blue-600 text-white text-sm font-bold hover:bg-blue-500 transition-colors">
                    <FunnelIcon class="h-4 w-4" />
                </button>
            </div>

            <!-- Queue Sections -->
            <div class="space-y-8">
                <!-- Draft: Waiting to Start Load -->
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-3 h-3 rounded-full bg-amber-500 animate-pulse"></div>
                        <h2 class="text-sm font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest">Menunggu Loading ({{ draftOrders.length }})</h2>
                    </div>
                    <div v-if="draftOrders.length === 0" class="text-center py-12 text-slate-400 text-sm glass-card rounded-2xl border border-slate-200 dark:border-slate-800">
                        Tidak ada DO yang menunggu loading.
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        <div
                            v-for="order in draftOrders"
                            :key="order.id"
                            class="glass-card rounded-2xl p-5 border border-slate-200 dark:border-slate-800 hover:border-amber-300 dark:hover:border-amber-700 transition-all hover:shadow-lg group"
                        >
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <Link :href="route('sales.deliveries.show', order.id)" class="text-sm font-bold text-slate-900 dark:text-white hover:text-blue-500 transition-colors">
                                        {{ order.do_number }}
                                    </Link>
                                    <div class="text-xs text-slate-500 mt-0.5">{{ order.sales_order?.so_number }}</div>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-amber-500/10 text-amber-600 border border-amber-500/20">Draft</span>
                            </div>
                            <div class="space-y-2 mb-4">
                                <div class="text-xs text-slate-700 dark:text-slate-300 font-bold">{{ order.customer?.name }}</div>
                                <div class="flex items-center gap-4 text-[10px] text-slate-500">
                                    <span>🗓 {{ formatDate(order.delivery_date) }}</span>
                                    <span>📦 {{ order.items?.length || 0 }} items</span>
                                </div>
                                <div v-if="order.vehicle_number" class="text-[10px] text-slate-500">
                                    🚛 {{ order.vehicle_number }} <span v-if="order.driver_name">• {{ order.driver_name }}</span>
                                </div>
                                <div v-if="order.warehouse" class="text-[10px] text-slate-500">🏭 {{ order.warehouse.name }}</div>
                            </div>
                            <div class="flex flex-col gap-2">
                                <button
                                    @click="startLoading(order)"
                                    :disabled="processingId === order.id"
                                    class="w-full py-3 rounded-xl bg-gradient-to-r from-amber-600 to-amber-500 text-white text-sm font-black uppercase tracking-wide shadow-lg shadow-amber-500/30 hover:from-amber-500 hover:to-amber-400 hover:scale-[1.02] active:scale-95 transition-all disabled:opacity-50"
                                >
                                    <span v-if="processingId === order.id" class="flex items-center justify-center gap-2">
                                        <ArrowPathIcon class="h-4 w-4 animate-spin" /> Processing...
                                    </span>
                                    <span v-else class="flex items-center justify-center gap-2">
                                        <CubeIcon class="h-4 w-4" /> START LOADING
                                    </span>
                                </button>
                                <button
                                    @click="openManageItems(order)"
                                    class="w-full py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs font-bold uppercase tracking-wider hover:bg-slate-200 dark:hover:bg-slate-700 transition-all border border-slate-200 dark:border-slate-700"
                                >
                                    MANAGE ITEMS / SHORT SHIPMENT
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Picking: Currently Being Loaded -->
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-3 h-3 rounded-full bg-blue-500 animate-pulse"></div>
                        <h2 class="text-sm font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest">Sedang Loading ({{ pickingOrders.length }})</h2>
                    </div>
                    <div v-if="pickingOrders.length === 0" class="text-center py-12 text-slate-400 text-sm glass-card rounded-2xl border border-slate-200 dark:border-slate-800">
                        Tidak ada DO yang sedang diloading.
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        <div
                            v-for="order in pickingOrders"
                            :key="order.id"
                            class="glass-card rounded-2xl p-5 border-2 border-blue-300 dark:border-blue-700 hover:shadow-lg transition-all bg-blue-50/30 dark:bg-blue-900/10"
                        >
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <Link :href="route('sales.deliveries.show', order.id)" class="text-sm font-bold text-slate-900 dark:text-white hover:text-blue-500 transition-colors">
                                        {{ order.do_number }}
                                    </Link>
                                    <div class="text-xs text-slate-500 mt-0.5">{{ order.sales_order?.so_number }}</div>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-blue-500/10 text-blue-600 border border-blue-500/20 animate-pulse">Picking</span>
                            </div>
                            <div class="space-y-2 mb-4">
                                <div class="text-xs text-slate-700 dark:text-slate-300 font-bold">{{ order.customer?.name }}</div>
                                <div class="flex items-center gap-4 text-[10px] text-slate-500">
                                    <span>🗓 {{ formatDate(order.delivery_date) }}</span>
                                    <span>📦 {{ order.items?.length || 0 }} items</span>
                                </div>
                                <div v-if="order.vehicle_number" class="text-[10px] text-slate-500">
                                    🚛 {{ order.vehicle_number }} <span v-if="order.driver_name">• {{ order.driver_name }}</span>
                                </div>
                                <div v-if="order.warehouse" class="text-[10px] text-slate-500">🏭 {{ order.warehouse.name }}</div>

                                <!-- Progress Bar -->
                                <div v-if="order.status === 'picking'" class="mt-4 pt-4 border-t border-blue-200/50 dark:border-blue-800/50">
                                    <div class="flex items-center justify-between mb-1.5">
                                        <span class="text-[10px] font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider">Loading Progress</span>
                                        <span class="text-[10px] font-black text-blue-700 dark:text-blue-300">{{ calculateProgress(order) }}%</span>
                                    </div>
                                    <div class="w-full h-2 bg-slate-200 dark:bg-slate-800 rounded-full overflow-hidden border border-blue-200/30 dark:border-blue-800/30">
                                        <div 
                                            class="h-full bg-gradient-to-r from-blue-600 to-blue-400 transition-all duration-500 ease-out shadow-[0_0_8px_rgba(59,130,246,0.5)]"
                                            :style="{ width: calculateProgress(order) + '%' }"
                                        ></div>
                                    </div>
                                    <div class="flex justify-between mt-1 text-[9px] font-medium text-slate-500">
                                        <span>{{ getLoadedCount(order) }} of {{ order.items?.length || 0 }} items loaded</span>
                                        <span v-if="calculateProgress(order) === 100" class="text-green-500 flex items-center gap-0.5 animate-bounce">
                                            <CheckCircleIcon class="h-3 w-3" /> READY
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col gap-2">
                                <button
                                    @click="finishLoading(order)"
                                    :disabled="processingId === order.id"
                                    class="w-full py-3 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 text-white text-sm font-black uppercase tracking-wide shadow-lg shadow-blue-500/30 hover:from-blue-500 hover:to-blue-400 hover:scale-[1.02] active:scale-95 transition-all disabled:opacity-50"
                                >
                                    <span v-if="processingId === order.id && !showManageItemsModal" class="flex items-center justify-center gap-2">
                                        <ArrowPathIcon class="h-4 w-4 animate-spin" /> Processing...
                                    </span>
                                    <span v-else class="flex items-center justify-center gap-2">
                                        <CheckCircleIcon class="h-4 w-4" /> FINISH LOADING
                                    </span>
                                </button>
                                <button
                                    @click="openManageItems(order)"
                                    class="w-full py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs font-bold uppercase tracking-wider hover:bg-slate-200 dark:hover:bg-slate-700 transition-all border border-slate-200 dark:border-slate-700"
                                >
                                    MANAGE ITEMS / SHORT SHIPMENT
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Manage Items Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showManageItemsModal" class="fixed inset-0 z-50 flex items-center justify-center">
                    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showManageItemsModal = false"></div>
                    <div class="relative w-full max-w-4xl mx-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-800/50">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ selectedOrder?.do_number }}</h3>
                                <p class="text-xs text-slate-500">{{ selectedOrder?.customer?.name }}</p>
                            </div>
                            <button @click="showManageItemsModal = false" class="p-2 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                                <ArrowPathIcon class="h-5 w-5 text-slate-500" v-if="revisionForm.processing" />
                                <span v-else class="text-2xl leading-none">&times;</span>
                            </button>
                        </div>
                        <div class="p-6 max-h-[70vh] overflow-y-auto">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left">
                                    <thead>
                                        <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800">
                                            <th class="py-3 px-2">Item</th>
                                            <th class="py-3 px-2 text-center">Status</th>
                                            <th class="py-3 px-2 text-center">Order</th>
                                            <th class="py-3 px-2 text-center">Dikirim (Muat)</th>
                                            <th class="py-3 px-2 text-center">Satuan</th>
                                            <th class="py-3 px-2 text-right">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                        <tr v-for="item in selectedOrder?.items" :key="item.id" class="text-sm">
                                            <td class="py-4 px-2">
                                                <div class="font-bold text-slate-900 dark:text-white">{{ item.product?.name }}</div>
                                                <div class="text-[10px] text-slate-500">{{ item.product?.sku }}</div>
                                                <div v-if="item.notes" class="text-[10px] italic text-amber-500 mt-1">{{ item.notes }}</div>
                                            </td>
                                            <td class="py-4 px-2 text-center">
                                                <button 
                                                    @click="toggleItemLoaded(item)"
                                                    :disabled="selectedOrder.status !== 'picking' || processingId"
                                                    class="group relative flex items-center justify-center mx-auto"
                                                >
                                                    <div 
                                                        class="w-8 h-8 rounded-xl border-2 transition-all flex items-center justify-center"
                                                        :class="item.is_loaded 
                                                            ? 'bg-blue-600 border-blue-600 shadow-lg shadow-blue-500/30 text-white' 
                                                            : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-transparent group-hover:border-blue-500'"
                                                    >
                                                        <CheckCircleIcon class="h-5 w-5" :class="item.is_loaded ? 'opacity-100 scale-100' : 'opacity-0 scale-50'" />
                                                    </div>
                                                </button>
                                            </td>
                                            <td class="py-4 px-2 text-center font-mono">{{ item.qty_ordered }}</td>
                                            <td class="py-4 px-2 text-center font-mono font-bold" :class="item.qty_delivered < item.qty_ordered ? 'text-red-500 underline' : 'text-blue-500'">
                                                {{ item.qty_delivered }}
                                            </td>
                                            <td class="py-4 px-2 text-center text-xs text-slate-500">{{ item.unit?.name }}</td>
                                            <td class="py-4 px-2 text-right">
                                                <button 
                                                    @click="updateItemQty(item)"
                                                    :disabled="revisionForm.processing"
                                                    class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-[10px] font-bold hover:bg-blue-500 hover:text-white transition-all disabled:opacity-50"
                                                >
                                                    REVISE QTY
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-800 flex justify-between items-center">
                            <div class="text-[10px] text-slate-500 max-w-sm">
                                💡 Mengurangi jumlah di sini akan mengembalikan sisa barang ke saldo Sales Order secara otomatis.
                            </div>
                            <button @click="showManageItemsModal = false" class="px-6 py-2 rounded-xl bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-sm font-bold shadow-lg">
                                CLOSE
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Loading Queue Operations Guide -->
        <div class="mt-8 relative hidden md:block">
            <div class="absolute inset-0 flex items-center" aria-hidden="true">
                <div class="w-full border-t border-slate-200/60 dark:border-slate-700/60"></div>
            </div>
            <div class="relative flex justify-center">
                <span class="bg-[#F8FAFC] dark:bg-[#0F172A] px-4 text-[10px] font-black tracking-[0.2em] text-slate-400 dark:text-slate-500 uppercase">
                    Loading Queue Operations Guide
                </span>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4 hidden md:grid mb-8">
            <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                <div class="flex items-center gap-3 mb-3">
                    <div class="p-2.5 rounded-xl bg-amber-500/10 text-amber-500">
                        <ClockIcon class="h-5 w-5" />
                    </div>
                    <h4 class="font-bold text-slate-200 text-sm">Staging Process</h4>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    Verify physical items on the warehouse floor before marking them as <strong>Picking</strong>. This initializes the loading sequence protocol into the fleet.
                </p>
            </div>

            <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                <div class="flex items-center gap-3 mb-3">
                    <div class="p-2.5 rounded-xl bg-blue-500/10 text-blue-400">
                        <CheckCircleIcon class="h-5 w-5" />
                    </div>
                    <h4 class="font-bold text-slate-200 text-sm">Line-Item Check</h4>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    Once picking commences, click <strong>Manage Items</strong> to perform a granular barcode scan or manual checklist of goods actively bound for shipment.
                </p>
            </div>

            <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                <div class="flex items-center gap-3 mb-3">
                    <div class="p-2.5 rounded-xl bg-rose-500/10 text-rose-400">
                        <CubeIcon class="h-5 w-5" />
                    </div>
                    <h4 class="font-bold text-slate-200 text-sm">Short Shipments</h4>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    If items are damaged or unavailable during loading, use the <strong>Revise Qty</strong> function. Deficits will automatically revert to the origin Sales Order backlog.
                </p>
            </div>
            
            <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                <div class="flex items-center gap-3 mb-3">
                    <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-400">
                        <TruckIcon class="h-5 w-5" />
                    </div>
                    <h4 class="font-bold text-slate-200 text-sm">Handover Seal</h4>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    Progressing an order to <strong>Packed</strong> finalizes the warehouse's responsibility. It moves the authorization gate over to the Dispatch Panel.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
