<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
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
    deliveryOrders: Object,
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
    <Head title="Logistics Dispatch" />

    <AppLayout title="Dispatch Panel" :render-header="false">
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
                                Live Gate Control
                            </span>
                            <span class="px-2 py-0.5 text-[10px] bg-slate-200 dark:bg-white/5 border border-slate-300 dark:border-white/10 rounded text-slate-500 dark:text-slate-400 tracking-[0.2em] uppercase">LOGISTICS.DISP.V1</span>
                        </div>
                        <h1 class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 via-slate-800 to-indigo-700 dark:from-cyan-400 dark:via-white dark:to-cyan-400 tracking-widest uppercase dark:glow-text">
                            DISPATCH PANEL
                        </h1>
                    </div>
                    
                    <div class="flex items-center gap-2 bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-400 px-3 py-1.5 rounded-xl text-xs font-bold border border-indigo-200 dark:border-indigo-500/20 shadow-sm dark:shadow-none">
                        <TruckIcon class="h-4 w-4 text-indigo-500" />
                        {{ deliveryOrders.total }} Siap Kirim
                    </div>
                </div>

                <p class="text-xs text-slate-500 dark:text-slate-450 leading-relaxed max-w-2xl">DO siap kirim (status Packed). Klik BERANGKATKAN untuk mulai pengiriman.</p>

                <!-- Search -->
                <div class="bg-white/70 dark:bg-[#0a0a16]/60 border border-slate-200 dark:border-slate-800 rounded-xl p-4 flex gap-3 items-center shadow-sm dark:shadow-none">
                    <div class="relative flex-1">
                        <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-450" />
                        <input
                            v-model="search"
                            @keyup.enter="applyFilters"
                            type="text"
                            placeholder="Cari DO / Customer..."
                            class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl pl-10 pr-4 py-2.5 text-sm text-slate-800 dark:text-white focus:border-blue-500 outline-none transition-all font-medium"
                        />
                    </div>
                    <button @click="applyFilters" class="px-4 py-2.5 rounded-xl bg-indigo-650 hover:bg-indigo-600 dark:bg-indigo-600 dark:hover:bg-indigo-500 text-white text-sm font-bold border-0 cursor-pointer shadow-sm">
                        Cari
                    </button>
                </div>

                <!-- Empty State -->
                <div v-if="deliveryOrders.data.length === 0" class="text-center py-16 bg-white/70 dark:bg-[#0a0a16]/60 border border-slate-205 dark:border-slate-800 rounded-xl shadow-sm dark:shadow-none">
                    <TruckIcon class="h-12 w-12 text-slate-300 dark:text-slate-650 mx-auto mb-4" />
                    <p class="text-slate-500 text-sm">Tidak ada DO berstatus Packed yang siap dikirim.</p>
                    <p class="text-xs text-slate-400 mt-1">DO akan muncul setelah proses loading selesai di Loading Queue.</p>
                </div>

                <!-- Dispatch Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-3">
                    <div
                        v-for="order in deliveryOrders.data"
                        :key="order.id"
                        class="bg-white/70 dark:bg-[#0a0a16]/60 border border-slate-200 dark:border-slate-800 hover:border-indigo-400 dark:hover:border-indigo-700 rounded-xl p-4 transition-all hover:shadow-sm flex flex-col justify-between"
                    >
                        <div>
                            <div class="flex items-start justify-between mb-3 border-b border-slate-100 dark:border-white/5 pb-2">
                                <div>
                                    <Link :href="route('sales.deliveries.show', order.id)" class="text-sm font-bold text-slate-905 dark:text-white hover:text-blue-500 transition-colors font-mono">
                                        {{ order.do_number }}
                                    </Link>
                                    <div class="text-[10px] text-slate-450 font-mono mt-0.5">{{ order.sales_order?.so_number }}</div>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-indigo-50 dark:bg-indigo-550/10 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-500/25">Packed</span>
                            </div>

                            <div class="space-y-2 mb-4">
                                <div class="text-xs text-slate-800 dark:text-slate-200 font-bold truncate" :title="order.customer?.name">{{ order.customer?.name }}</div>
                                <div class="flex items-center gap-4 text-[10px] text-slate-500 font-bold">
                                    <span>🗓 {{ formatDate(order.delivery_date) }}</span>
                                    <span>📦 {{ order.items?.length || 0 }} items</span>
                                </div>
                                <div class="flex items-center gap-2 text-[10px] mt-2 p-2 rounded-lg border" 
                                    :class="order.vehicle_number 
                                        ? 'bg-emerald-50 dark:bg-emerald-900/10 text-emerald-700 dark:text-emerald-450 border-emerald-250 dark:border-emerald-500/20' 
                                        : 'bg-rose-50 dark:bg-rose-900/10 text-rose-700 dark:text-rose-455 border-rose-200 dark:border-rose-500/20'"
                                >
                                    <TruckIcon class="h-3.5 w-3.5" />
                                    <span v-if="order.vehicle_number" class="font-bold">{{ order.vehicle_number }}</span>
                                    <span v-else class="font-bold">Armada belum ditetapkan!</span>
                                </div>
                                <div v-if="order.driver_name" class="flex items-center gap-2 text-[10px] text-slate-500 font-mono font-bold">
                                    <UserIcon class="h-3.5 w-3.5 text-slate-400" />
                                    {{ order.driver_name }}
                                </div>
                                <div v-if="order.shipping_address || order.sales_order?.shipping_address" class="flex items-start gap-2 text-[10px] text-slate-500">
                                    <MapPinIcon class="h-3.5 w-3.5 text-slate-450 shrink-0 mt-0.5" />
                                    <span class="line-clamp-2" :title="order.shipping_address || order.sales_order?.shipping_address">{{ order.shipping_address || order.sales_order?.shipping_address }}</span>
                                </div>
                            </div>
                        </div>

                        <button
                            @click="dispatchOrder(order)"
                            :disabled="processingId === order.id || !order.vehicle_number"
                            class="w-full py-3 rounded-xl text-white text-sm font-black uppercase tracking-wide transition-all border-0 shadow-sm cursor-pointer"
                            :class="order.vehicle_number 
                                ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-400 hover:scale-[1.02] active:scale-95 shadow-sm' 
                                : 'bg-slate-205 dark:bg-slate-800 text-slate-400 dark:text-slate-500 cursor-not-allowed'"
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

                <!-- Pagination Support -->
                <div v-if="deliveryOrders.last_page > 1" class="border border-slate-200 dark:border-slate-850 px-6 py-4 flex items-center justify-between mt-6 bg-white/70 dark:bg-[#0a0a16]/60 rounded-xl shadow-sm dark:shadow-none">
                    <p class="text-xs text-slate-500 dark:text-slate-400 font-mono">
                        Showing {{ deliveryOrders.from }} to {{ deliveryOrders.to }} of {{ deliveryOrders.total }} orders
                    </p>
                    <div class="flex items-center gap-2">
                        <Link
                            v-for="link in deliveryOrders.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            class="px-3 py-1.5 rounded-lg text-xs transition-colors font-mono font-bold"
                            :class="link.active 
                                ? 'bg-blue-600 text-white' 
                                : link.url 
                                    ? 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-850 dark:text-white' 
                                    : 'text-slate-350 dark:text-slate-650 cursor-not-allowed'"
                            v-html="link.label"
                        />
                    </div>
                </div>

                <!-- Operations Guide Separator -->
                <div class="mt-8 relative hidden md:block">
                    <div class="absolute inset-0 flex items-center" aria-hidden="true">
                        <div class="w-full border-t border-slate-200 dark:border-slate-800"></div>
                    </div>
                    <div class="relative flex justify-center">
                        <span class="bg-[#F8FAFC] dark:bg-[#050510] px-4 text-[10px] font-black tracking-[0.2em] text-slate-400 dark:text-slate-500 uppercase">
                            Dispatch Operations Guide
                        </span>
                    </div>
                </div>

                <!-- Guide Cards -->
                <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4 hidden md:grid mb-8">
                    <div class="bg-white/70 dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm hover:border-slate-350 dark:hover:border-slate-700 transition-colors">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2.5 rounded-xl bg-indigo-500/10 text-indigo-700 dark:text-indigo-400">
                                <TruckIcon class="h-5 w-5" />
                            </div>
                            <h4 class="font-bold text-slate-850 dark:text-slate-205 text-sm">Gate Authorization</h4>
                        </div>
                        <p class="text-xs text-slate-550 dark:text-slate-400 leading-relaxed">
                            Serve as the final logistical checkpoint. Press <strong>BERANGKATKAN</strong> to certify the truck has left the facility premises carrying the manifested goods.
                        </p>
                    </div>

                    <div class="bg-white/70 dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm hover:border-slate-350 dark:hover:border-slate-700 transition-colors">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2.5 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-500">
                                <CheckCircleIcon class="h-5 w-5" />
                            </div>
                            <h4 class="font-bold text-slate-850 dark:text-slate-205 text-sm">Dependency Notice</h4>
                        </div>
                        <p class="text-xs text-slate-550 dark:text-slate-400 leading-relaxed">
                            Orders only appear here once Warehouse teams mark them <strong>Packed</strong> and Logistic Planners have assigned a valid Fleet identity.
                        </p>
                    </div>

                    <div class="bg-white/70 dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm hover:border-slate-350 dark:hover:border-slate-700 transition-colors">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2.5 rounded-xl bg-rose-500/10 text-rose-705 dark:text-rose-400">
                                <MapPinIcon class="h-5 w-5" />
                            </div>
                            <h4 class="font-bold text-slate-850 dark:text-slate-205 text-sm">Blind Drops</h4>
                        </div>
                        <p class="text-xs text-slate-550 dark:text-slate-400 leading-relaxed">
                            Briefly review the overlaid <strong>Shipping Address</strong> block to ensure drivers have explicit drop-off locales before authorizing gate departure.
                        </p>
                    </div>
                    
                    <div class="bg-white/70 dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm hover:border-slate-350 dark:hover:border-slate-700 transition-colors">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-450">
                                <ArrowPathIcon class="h-5 w-5" />
                            </div>
                            <h4 class="font-bold text-slate-850 dark:text-slate-205 text-sm">Status Propagation</h4>
                        </div>
                        <p class="text-xs text-slate-550 dark:text-slate-400 leading-relaxed">
                            Dispatching toggles the system state to <strong>Shipped</strong>, reflecting actively across Sales portals and notifying client endpoints.
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
