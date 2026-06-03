<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import { 
    ArrowLeftIcon, 
    PrinterIcon,
    MapPinIcon,
    CalendarIcon
} from '@heroicons/vue/24/outline';
import { formatDate } from '@/helpers';

defineProps({
    delivery: Object,
});


</script>

<template>
    <PortalLayout :title="`DN #${delivery.delivery_note_number}`">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="flex items-center gap-4 mb-6">
                <Link href="/portal/deliveries" class="p-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-slate-700 dark:hover:text-white transition-colors">
                    <ArrowLeftIcon class="h-5 w-5" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Delivery Note #{{ delivery.delivery_note_number }}</h1>
                    <p class="text-sm text-slate-500">
                        Ref PO: <span class="text-indigo-600 font-bold">{{ delivery.purchase_order?.po_number }}</span>
                    </p>
                </div>
                <div class="ml-auto flex gap-3">
                    <a 
                        :href="route('portal.deliveries.print', delivery.id)"
                        target="_blank"
                        class="px-4 py-2 rounded-lg bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-600 font-bold flex items-center gap-2 cursor-pointer"
                    >
                        <PrinterIcon class="w-5 h-5" />
                        Print
                    </a>
                    <span class="px-3 py-1.5 rounded-full text-sm font-bold capitalize flex items-center"
                        :class="{
                            'bg-orange-100 text-orange-700': delivery.status === 'dispatched',
                            'bg-blue-100 text-blue-700': delivery.status === 'received',
                            'bg-emerald-100 text-emerald-700': delivery.status === 'completed',
                        }">
                        {{ delivery.status }}
                    </span>
                </div>
            </div>

            <!-- Info Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                    <h3 class="font-bold text-slate-500 text-xs uppercase mb-4">Delivery Details</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Date</span>
                            <span class="font-bold text-slate-900 dark:text-white">{{ formatDate(delivery.receipt_date) }}</span>
                        </div>
                         <div class="flex justify-between">
                            <span class="text-slate-500">GRN System ID</span>
                            <span class="font-mono text-slate-900 dark:text-white">{{ delivery.grn_number }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                    <h3 class="font-bold text-slate-500 text-xs uppercase mb-4">Destination</h3>
                    <div class="flex items-start gap-3">
                        <MapPinIcon class="w-5 h-5 text-slate-400 mt-0.5" />
                        <div>
                            <p class="font-bold text-slate-900 dark:text-white">{{ delivery.warehouse?.name }}</p>
                            <p class="text-sm text-slate-500">{{ delivery.warehouse?.address || 'No address details' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes -->
             <div v-if="delivery.notes" class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
                <h3 class="font-bold text-slate-800 dark:text-white mb-2">Driver / Delivery Notes</h3>
                <p class="text-slate-600 dark:text-slate-400 whitespace-pre-line">{{ delivery.notes }}</p>
            </div>

            <!-- Items Table -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="p-6 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-700/50">
                    <h2 class="font-bold text-slate-800 dark:text-white">Items Dispatched</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600 dark:text-slate-400">
                        <thead class="bg-slate-50 dark:bg-slate-700/50 uppercase text-xs font-bold text-slate-500">
                            <tr>
                                <th class="px-6 py-4">Product</th>
                                <th class="px-6 py-4 text-center">Qty Dispatched</th>
                                <th v-if="['received', 'completed'].includes(delivery.status)" class="px-6 py-4 text-center">Qty Received</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            <tr v-for="item in delivery.items" :key="item.id">
                                <td class="px-6 py-4">
                                    <p class="font-bold text-slate-900 dark:text-white">{{ item.product?.name || item.product_name || '-' }}</p>
                                    <p class="text-xs text-slate-500">SKU: {{ item.product?.sku || '-' }}</p>
                                </td>
                                <td class="px-6 py-4 text-center font-bold text-lg text-indigo-600">
                                    {{ Number(item.qty_ordered).toLocaleString('id-ID') }}
                                </td>
                                <td v-if="['received', 'completed'].includes(delivery.status)" class="px-6 py-4 text-center font-bold text-lg">
                                    <span 
                                        :class="{
                                            'text-emerald-600': Number(item.qty_received) === Number(item.qty_ordered),
                                            'text-red-500': Number(item.qty_received) < Number(item.qty_ordered),
                                            'text-orange-500': Number(item.qty_received) > Number(item.qty_ordered)
                                        }"
                                    >
                                        {{ Number(item.qty_received).toLocaleString('id-ID') }}
                                    </span>
                                    <p v-if="Number(item.qty_received) < Number(item.qty_ordered)" class="text-[10px] text-red-500 font-normal mt-1">
                                        Shortfall: {{ (Number(item.qty_ordered) - Number(item.qty_received)).toLocaleString('id-ID') }}
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </PortalLayout>
</template>
