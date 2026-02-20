<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
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
    if (!confirm('📦 MULAI LOADING\n\nBarang dari DO ' + order.do_number + ' akan mulai diambil dan dimuat.\n\nLanjutkan?')) return;
    processingId.value = order.id;
    router.patch(route('warehouse.loading.update-status', order.id), {
        status: 'picking'
    }, {
        preserveScroll: true,
        onFinish: () => processingId.value = null,
    });
};

const finishPacking = (order) => {
    if (!confirm('✅ SELESAI LOADING\n\nSemua item DO ' + order.do_number + ' sudah selesai dimuat ke truk.\nPastikan semua item sudah dicek.\n\nLanjutkan?')) return;
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
                            <button
                                @click="startLoading(order)"
                                :disabled="processingId === order.id"
                                class="w-full py-3 rounded-xl bg-gradient-to-r from-amber-600 to-amber-500 text-white text-sm font-black uppercase tracking-wide shadow-lg shadow-amber-500/30 hover:from-amber-500 hover:to-amber-400 hover:scale-[1.02] active:scale-95 transition-all disabled:opacity-50"
                            >
                                <span v-if="processingId === order.id" class="flex items-center justify-center gap-2">
                                    <ArrowPathIcon class="h-4 w-4 animate-spin" /> Processing...
                                </span>
                                <span v-else class="flex items-center justify-center gap-2">
                                    <CubeIcon class="h-4 w-4" /> MULAI LOADING
                                </span>
                            </button>
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
                            </div>
                            <button
                                @click="finishPacking(order)"
                                :disabled="processingId === order.id"
                                class="w-full py-3 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 text-white text-sm font-black uppercase tracking-wide shadow-lg shadow-blue-500/30 hover:from-blue-500 hover:to-blue-400 hover:scale-[1.02] active:scale-95 transition-all disabled:opacity-50"
                            >
                                <span v-if="processingId === order.id" class="flex items-center justify-center gap-2">
                                    <ArrowPathIcon class="h-4 w-4 animate-spin" /> Processing...
                                </span>
                                <span v-else class="flex items-center justify-center gap-2">
                                    <CheckCircleIcon class="h-4 w-4" /> SELESAI LOADING
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
