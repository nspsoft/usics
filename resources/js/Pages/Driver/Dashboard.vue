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
} from '@heroicons/vue/24/outline';

const props = defineProps({
    deliveryOrders: Array,
});

const page = usePage();
const flash = computed(() => page.props.flash || {});

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
    });
};
</script>

<template>
    <DriverLayout title="Pengiriman Aktif">
        <!-- Flash message -->
        <div v-if="flash.success" class="mb-4 p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 dark:text-emerald-400 text-sm font-bold flex items-center gap-2">
            ✅ {{ flash.success }}
        </div>
        <div v-if="flash.error" class="mb-4 p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-700 dark:text-red-400 text-sm font-bold flex items-center gap-2">
            ❌ {{ flash.error }}
        </div>

        <!-- Header -->
        <div class="mb-4">
            <h1 class="text-lg font-bold text-slate-900 dark:text-white">Pengiriman Aktif</h1>
            <p class="text-xs text-slate-500">DO yang sedang dalam perjalanan</p>
        </div>

        <!-- Empty State -->
        <div v-if="deliveryOrders.length === 0" class="text-center py-16">
            <div class="w-20 h-20 mx-auto bg-slate-100 dark:bg-slate-800 rounded-3xl flex items-center justify-center mb-4">
                <TruckIcon class="h-10 w-10 text-slate-300" />
            </div>
            <p class="text-slate-500 font-bold text-sm">Tidak ada pengiriman aktif</p>
            <p class="text-xs text-slate-400 mt-1">DO akan muncul saat diberangkatkan oleh Logistik</p>
            <Link href="/driver/scan" class="inline-flex items-center gap-2 mt-6 px-6 py-3 rounded-2xl bg-blue-600 text-white text-sm font-bold hover:bg-blue-500 shadow-lg shadow-blue-500/30 transition-all">
                <QrCodeIcon class="h-5 w-5" />
                Scan QR Surat Jalan
            </Link>
        </div>

        <!-- DO Cards -->
        <div class="space-y-3">
            <div
                v-for="order in deliveryOrders"
                :key="order.id"
                class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-md transition-all"
            >
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <div class="text-sm font-black text-slate-900 dark:text-white">{{ order.do_number }}</div>
                        <div class="text-[10px] text-slate-400 mt-0.5">{{ order.sales_order?.so_number }}</div>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-purple-500/10 text-purple-600 border border-purple-500/20 animate-pulse">
                        Shipped
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
                    :href="'/driver/scan'"
                    class="w-full flex items-center justify-center gap-2 py-3 rounded-xl bg-gradient-to-r from-emerald-600 to-emerald-500 text-white text-sm font-bold shadow-lg shadow-emerald-500/30 hover:from-emerald-500 hover:to-emerald-400 active:scale-95 transition-all"
                >
                    <QrCodeIcon class="h-4 w-4" />
                    SCAN QR KONFIRMASI SAMPAI
                </Link>
            </div>
        </div>
    </DriverLayout>
</template>
