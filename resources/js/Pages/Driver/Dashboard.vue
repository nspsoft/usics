<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import DriverLayout from '@/Layouts/DriverLayout.vue';
import {
    TruckIcon,
    MapPinIcon,
    CubeIcon,
    CalendarIcon,
    UserIcon,
    QrCodeIcon,
    CheckCircleIcon,
    ClockIcon,
    ShieldCheckIcon,
    DocumentTextIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    deliveryOrders: Array,
    vehicle: Object,
    tripStats: Object,
});

const page = usePage();
const flash = computed(() => page.props.flash || {});

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};

const doStatusStyle = (status) => {
    const map = {
        draft: 'bg-slate-500/10 text-slate-500 border-slate-500/20',
        picking: 'bg-amber-500/10 text-amber-600 border-amber-500/20',
        packed: 'bg-cyan-500/10 text-cyan-600 border-cyan-500/20',
        shipped: 'bg-purple-500/10 text-purple-600 border-purple-500/20 animate-pulse',
        delivered: 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20',
        completed: 'bg-green-500/10 text-green-600 border-green-500/20',
    };
    return map[status] || 'bg-slate-500/10 text-slate-500 border-slate-500/20';
};

const statusColor = computed(() => {
    if (!props.vehicle) return '';
    const s = props.vehicle.status?.toLowerCase();
    if (s === 'available') return 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20';
    if (s === 'on_trip') return 'bg-blue-500/10 text-blue-500 border-blue-500/20';
    if (s === 'maintenance') return 'bg-amber-500/10 text-amber-500 border-amber-500/20';
    return 'bg-slate-500/10 text-slate-500 border-slate-500/20';
});

const groupedShipments = computed(() => {
    const groups = {};
    props.deliveryOrders.forEach(order => {
        const key = order.shipment_number || 'no_shipment';
        if (!groups[key]) {
            groups[key] = {
                shipment_number: order.shipment_number || null,
                orders: []
            };
        }
        groups[key].orders.push(order);
    });
    return Object.values(groups);
});
</script>

<template>
    <DriverLayout title="Driver Dashboard">
        <!-- Flash message -->
        <div v-if="flash.success" class="mb-4 p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 dark:text-emerald-400 text-sm font-bold flex items-center gap-2">
            ✅ {{ flash.success }}
        </div>
        <div v-if="flash.error" class="mb-4 p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-700 dark:text-red-400 text-sm font-bold flex items-center gap-2">
            ❌ {{ flash.error }}
        </div>

        <!-- ===================== VEHICLE DETAILS ===================== -->
        <div v-if="vehicle" class="mb-5">
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
                <!-- Vehicle Header -->
                <div class="relative bg-gradient-to-br from-blue-600 to-indigo-700 p-4">
                    <div class="flex items-center gap-3">
                        <!-- Vehicle Photo / Icon -->
                        <div class="w-16 h-16 rounded-2xl bg-white/10 backdrop-blur-sm flex items-center justify-center shrink-0 overflow-hidden border border-white/20">
                            <img v-if="vehicle.vehicle_photo_url" :src="vehicle.vehicle_photo_url" class="w-full h-full object-cover" />
                            <TruckIcon v-else class="h-8 w-8 text-white/80" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-xl font-black text-white tracking-wide">{{ vehicle.license_plate }}</div>
                            <div class="text-xs text-blue-200 mt-0.5">{{ vehicle.brand }} {{ vehicle.model ? '• ' + vehicle.model : '' }}</div>
                            <div v-if="vehicle.vehicle_type" class="text-[10px] text-blue-300 uppercase tracking-wider mt-0.5">{{ vehicle.vehicle_type }}</div>
                        </div>
                    </div>
                    <!-- Status Badge -->
                    <span :class="[statusColor]" class="absolute top-3 right-3 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase border">
                        {{ vehicle.status || '-' }}
                    </span>
                </div>

                <!-- Driver Info -->
                <div class="p-3 border-b border-slate-100 dark:border-slate-700">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center overflow-hidden shrink-0">
                            <img v-if="vehicle.driver_photo_url" :src="vehicle.driver_photo_url" class="w-full h-full object-cover" />
                            <UserIcon v-else class="h-5 w-5 text-slate-400" />
                        </div>
                        <div>
                            <div class="text-[10px] text-slate-400 uppercase tracking-wider font-bold">Main Driver</div>
                            <div class="text-sm font-bold text-slate-900 dark:text-white">{{ vehicle.driver_name || '-' }}</div>
                        </div>
                    </div>
                </div>

                <!-- Capacity + Stats Row -->
                <div class="grid grid-cols-3 divide-x divide-slate-100 dark:divide-slate-700">
                    <div class="p-3 text-center">
                        <div class="text-lg font-black text-slate-900 dark:text-white">{{ tripStats?.total || 0 }}</div>
                        <div class="text-[9px] text-slate-400 uppercase tracking-wider font-bold">Total Trip</div>
                    </div>
                    <div class="p-3 text-center">
                        <div class="text-lg font-black text-emerald-500">{{ tripStats?.delivered || 0 }}</div>
                        <div class="text-[9px] text-slate-400 uppercase tracking-wider font-bold">Selesai</div>
                    </div>
                    <div class="p-3 text-center">
                        <div class="text-lg font-black text-blue-500">{{ tripStats?.in_progress || 0 }}</div>
                        <div class="text-[9px] text-slate-400 uppercase tracking-wider font-bold">Dalam Perjalanan</div>
                    </div>
                </div>

                <!-- Capacity Info -->
                <div class="grid grid-cols-2 gap-2 p-3 border-t border-slate-100 dark:border-slate-700">
                    <div v-if="vehicle.capacity_weight" class="flex items-center gap-2 bg-slate-50 dark:bg-slate-900/50 rounded-xl p-2.5">
                        <CubeIcon class="h-4 w-4 text-blue-500 shrink-0" />
                        <div>
                            <div class="text-[9px] text-slate-400 uppercase tracking-wider font-bold">Muat Berat</div>
                            <div class="text-sm font-black text-slate-900 dark:text-white">{{ vehicle.capacity_weight }} Ton</div>
                        </div>
                    </div>
                    <div v-if="vehicle.capacity_volume" class="flex items-center gap-2 bg-slate-50 dark:bg-slate-900/50 rounded-xl p-2.5">
                        <CubeIcon class="h-4 w-4 text-indigo-500 shrink-0" />
                        <div>
                            <div class="text-[9px] text-slate-400 uppercase tracking-wider font-bold">Muat Volume</div>
                            <div class="text-sm font-black text-slate-900 dark:text-white">{{ vehicle.capacity_volume }} m³</div>
                        </div>
                    </div>
                </div>

                <!-- Legal Documents -->
                <div v-if="vehicle.stnk_number || vehicle.kir_number" class="p-3 border-t border-slate-100 dark:border-slate-700 space-y-2">
                    <div class="text-[10px] text-slate-400 uppercase tracking-wider font-bold">Dokumen Kendaraan</div>
                    <div v-if="vehicle.stnk_number" class="flex items-center gap-2.5 bg-slate-50 dark:bg-slate-900/50 rounded-xl p-2.5">
                        <div class="w-8 h-8 rounded-lg bg-blue-500/10 flex items-center justify-center shrink-0">
                            <DocumentTextIcon class="h-4 w-4 text-blue-500" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-[9px] text-slate-400 uppercase font-bold">STNK</div>
                            <div class="text-xs font-bold text-slate-900 dark:text-white">{{ vehicle.stnk_number }}</div>
                        </div>
                        <div v-if="vehicle.stnk_expiry" class="text-right shrink-0">
                            <div class="text-[9px] text-slate-400">Exp</div>
                            <div class="text-[10px] font-bold text-slate-600 dark:text-slate-300">{{ formatDate(vehicle.stnk_expiry) }}</div>
                        </div>
                    </div>
                    <div v-if="vehicle.kir_number" class="flex items-center gap-2.5 bg-slate-50 dark:bg-slate-900/50 rounded-xl p-2.5">
                        <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center shrink-0">
                            <ShieldCheckIcon class="h-4 w-4 text-emerald-500" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-[9px] text-slate-400 uppercase font-bold">KIR</div>
                            <div class="text-xs font-bold text-slate-900 dark:text-white">{{ vehicle.kir_number }}</div>
                        </div>
                        <div v-if="vehicle.kir_expiry" class="text-right shrink-0">
                            <div class="text-[9px] text-slate-400">Exp</div>
                            <div class="text-[10px] font-bold text-slate-600 dark:text-slate-300">{{ formatDate(vehicle.kir_expiry) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===================== ACTIVE DELIVERIES ===================== -->
        <div class="mb-3">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white">Pengiriman Aktif</h2>
            <p class="text-[10px] text-slate-400">DO yang sedang dalam perjalanan</p>
        </div>

        <!-- Empty State -->
        <div v-if="deliveryOrders.length === 0" class="text-center py-12">
            <div class="w-16 h-16 mx-auto bg-slate-100 dark:bg-slate-800 rounded-2xl flex items-center justify-center mb-3">
                <TruckIcon class="h-8 w-8 text-slate-300" />
            </div>
            <p class="text-slate-500 font-bold text-sm">Tidak ada pengiriman aktif</p>
            <p class="text-[10px] text-slate-400 mt-1">DO akan muncul saat diberangkatkan oleh Logistik</p>
            <Link href="/driver/scan" class="inline-flex items-center gap-2 mt-5 px-5 py-2.5 rounded-2xl bg-blue-600 text-white text-sm font-bold hover:bg-blue-500 shadow-lg shadow-blue-500/30 transition-all">
                <QrCodeIcon class="h-5 w-5" />
                Scan QR Surat Jalan
            </Link>
        </div>

        <!-- DO Cards Grouped by Shipment -->
        <div class="space-y-6">
            <div 
                v-for="shipment in groupedShipments" 
                :key="shipment.shipment_number || 'single'" 
                class="space-y-3"
            >
                <!-- Shipment Header -->
                <div v-if="shipment.shipment_number" class="flex items-center justify-between bg-blue-500/10 border border-blue-500/20 px-4 py-3 rounded-2xl">
                    <div class="flex items-center gap-2">
                        <TruckIcon class="h-4 w-4 text-blue-500" />
                        <span class="text-xs font-black uppercase tracking-wider text-blue-600 dark:text-blue-400">
                            Shipment: {{ shipment.shipment_number }}
                        </span>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-500 bg-slate-100 dark:bg-slate-700 px-2 py-0.5 rounded-lg border border-slate-200 dark:border-slate-650">
                        {{ shipment.orders.length }} Stop
                    </span>
                </div>

                <div class="space-y-3">
                    <div
                        v-for="order in shipment.orders"
                        :key="order.id"
                        class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-md transition-all"
                    >
                        <div class="flex items-start justify-between mb-3">
                            <div>
                                <div class="text-sm font-black text-slate-900 dark:text-white">{{ order.do_number }}</div>
                                <div class="text-[10px] text-slate-400 mt-0.5">{{ order.sales_order?.so_number }}</div>
                            </div>
                            <span :class="doStatusStyle(order.status)" class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase border">
                                {{ order.status }}
                            </span>
                        </div>

                        <div class="space-y-2 mb-4">
                            <div class="flex items-center gap-2 text-xs text-slate-700 dark:text-slate-300">
                                <UserIcon class="h-3.5 w-3.5 text-slate-400 shrink-0" />
                                <span class="font-bold truncate">{{ order.customer?.name }}</span>
                            </div>
                            <div v-if="order.shipping_address" class="flex items-start gap-2 text-xs text-slate-500">
                                <MapPinIcon class="h-3.5 w-3.5 text-slate-400 shrink-0 mt-0.5" />
                                <span class="line-clamp-2">{{ order.shipping_address }}</span>
                            </div>
                            <div class="flex items-center gap-4 text-[10px] text-slate-400">
                                <span class="flex items-center gap-1">
                                    <CalendarIcon class="h-3 w-3" />
                                    {{ formatDate(order.delivery_date) }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <CubeIcon class="h-3 w-3" />
                                    {{ order.items?.length || 0 }} items
                                </span>
                                <span v-if="order.vehicle_number" class="flex items-center gap-1">
                                    <TruckIcon class="h-3 w-3" />
                                    {{ order.vehicle_number }}
                                </span>
                            </div>
                        </div>

                        <Link
                            v-if="order.status === 'shipped'"
                            :href="'/driver/scan'"
                            class="w-full flex items-center justify-center gap-2 py-3 rounded-xl bg-gradient-to-r from-emerald-600 to-emerald-500 text-white text-sm font-bold shadow-lg shadow-emerald-500/30 hover:from-emerald-500 hover:to-emerald-400 active:scale-95 transition-all"
                        >
                            <QrCodeIcon class="h-4 w-4" />
                            SCAN QR KONFIRMASI SAMPAI
                        </Link>
                        <div v-else class="w-full text-center py-2.5 rounded-xl bg-slate-100 dark:bg-slate-700/50 text-slate-400 text-xs font-bold">
                            ⏳ Menunggu status SHIPPED untuk konfirmasi
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DriverLayout>
</template>
