<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    TruckIcon,
    MagnifyingGlassIcon,
    CheckCircleIcon,
    ArrowPathIcon,
    MapPinIcon,
    UserIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    deliveryOrders: Array,
    vehicles: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const processingId = ref(null);

const applyFilters = () => {
    router.get(route('logistics.dispatch.index'), {
        search: search.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const dispatchOrder = (order) => {
    if (!order.vehicle_number) {
        alert('⚠️ Armada belum ditetapkan!\n\nAssign kendaraan via Delivery Planning terlebih dahulu.');
        return;
    }
    const msg = '🚛 BERANGKATKAN PENGIRIMAN\n\n'
        + 'DO: ' + order.do_number + '\n'
        + 'Customer: ' + (order.customer?.name || '-') + '\n'
        + 'Truk: ' + (order.vehicle_number || '-') + '\n'
        + 'Sopir: ' + (order.driver_name || '-') + '\n\n'
        + 'Status akan berubah menjadi SHIPPED.\nLanjutkan?';
    if (!confirm(msg)) return;

    processingId.value = order.id;
    router.patch(route('logistics.dispatch.ship', order.id), {}, {
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
    <Head title="Logistics Dispatch" />

    <AppLayout title="Dispatch Panel">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">🚛 Dispatch Panel</h1>
                    <p class="text-sm text-slate-500 mt-1">DO siap kirim (status Packed). Klik BERANGKATKAN untuk mulai pengiriman.</p>
                </div>
                <div class="flex items-center gap-2 bg-indigo-500/10 text-indigo-600 px-3 py-1.5 rounded-xl text-xs font-bold border border-indigo-500/20">
                    <TruckIcon class="h-4 w-4" />
                    {{ deliveryOrders.length }} Siap Kirim
                </div>
            </div>

            <!-- Search -->
            <div class="glass-card rounded-2xl p-4 mb-6 flex gap-3 items-center border border-slate-200 dark:border-slate-800">
                <div class="relative flex-1">
                    <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                    <input
                        v-model="search"
                        @keyup.enter="applyFilters"
                        type="text"
                        placeholder="Cari DO / Customer..."
                        class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 pl-10 py-2.5 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                    />
                </div>
                <button @click="applyFilters" class="px-4 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-bold hover:bg-indigo-500 transition-colors">
                    Cari
                </button>
            </div>

            <!-- Empty State -->
            <div v-if="deliveryOrders.length === 0" class="text-center py-16 glass-card rounded-2xl border border-slate-200 dark:border-slate-800">
                <TruckIcon class="h-12 w-12 text-slate-300 mx-auto mb-4" />
                <p class="text-slate-500 text-sm">Tidak ada DO berstatus Packed yang siap dikirim.</p>
                <p class="text-xs text-slate-400 mt-1">DO akan muncul setelah proses loading selesai di Loading Queue.</p>
            </div>

            <!-- Dispatch Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                <div
                    v-for="order in deliveryOrders"
                    :key="order.id"
                    class="glass-card rounded-2xl p-5 border border-slate-200 dark:border-slate-800 hover:border-indigo-300 dark:hover:border-indigo-700 transition-all hover:shadow-lg"
                >
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <Link :href="route('sales.deliveries.show', order.id)" class="text-sm font-bold text-slate-900 dark:text-white hover:text-blue-500 transition-colors">
                                {{ order.do_number }}
                            </Link>
                            <div class="text-xs text-slate-500 mt-0.5">{{ order.sales_order?.so_number }}</div>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-indigo-500/10 text-indigo-600 border border-indigo-500/20">Packed</span>
                    </div>

                    <div class="space-y-2 mb-4">
                        <div class="text-xs text-slate-700 dark:text-slate-300 font-bold">{{ order.customer?.name }}</div>
                        <div class="flex items-center gap-4 text-[10px] text-slate-500">
                            <span>🗓 {{ formatDate(order.delivery_date) }}</span>
                            <span>📦 {{ order.items?.length || 0 }} items</span>
                        </div>
                        <div class="flex items-center gap-2 text-[10px] mt-2 p-2 rounded-lg" :class="order.vehicle_number ? 'bg-emerald-50 dark:bg-emerald-900/10 text-emerald-700 dark:text-emerald-400' : 'bg-red-50 dark:bg-red-900/10 text-red-600 dark:text-red-400'">
                            <TruckIcon class="h-3.5 w-3.5" />
                            <span v-if="order.vehicle_number" class="font-bold">{{ order.vehicle_number }}</span>
                            <span v-else class="font-bold">Armada belum ditetapkan!</span>
                        </div>
                        <div v-if="order.driver_name" class="flex items-center gap-2 text-[10px] text-slate-500">
                            <UserIcon class="h-3.5 w-3.5" />
                            {{ order.driver_name }}
                        </div>
                        <div v-if="order.shipping_address || order.sales_order?.shipping_address" class="flex items-start gap-2 text-[10px] text-slate-500">
                            <MapPinIcon class="h-3.5 w-3.5 shrink-0 mt-0.5" />
                            <span class="line-clamp-2">{{ order.shipping_address || order.sales_order?.shipping_address }}</span>
                        </div>
                    </div>

                    <button
                        @click="dispatchOrder(order)"
                        :disabled="processingId === order.id"
                        class="w-full py-3 rounded-xl text-white text-sm font-black uppercase tracking-wide shadow-lg hover:scale-[1.02] active:scale-95 transition-all disabled:opacity-50"
                        :class="order.vehicle_number ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-400 shadow-indigo-500/30' : 'bg-gradient-to-r from-slate-500 to-slate-400 shadow-slate-500/20 cursor-not-allowed'"
                    >
                        <span v-if="processingId === order.id" class="flex items-center justify-center gap-2">
                            <ArrowPathIcon class="h-4 w-4 animate-spin" /> Processing...
                        </span>
                        <span v-else class="flex items-center justify-center gap-2">
                            <TruckIcon class="h-4 w-4" /> BERANGKATKAN
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
