<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import { 
    ArchiveBoxIcon, 
    CalendarIcon, 
    MapPinIcon,
    DocumentTextIcon,
    ArrowLeftIcon,
    ExclamationCircleIcon
} from '@heroicons/vue/24/outline';
import { formatDate } from '@/helpers';

defineProps({
    returnDetails: Object,
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(amount);
};
</script>

<template>
    <PortalLayout :title="`Return #${returnDetails.number}`">
        <div class="mb-6">
            <Link :href="route('portal.returns.index')" class="inline-flex items-center gap-1 text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white mb-4">
                <ArrowLeftIcon class="w-4 h-4" />
                Back to Returns
            </Link>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-3">
                    Return #{{ returnDetails.number }}
                    <span class="px-3 py-1 bg-slate-100 text-slate-600 rounded-full text-sm font-bold capitalize">
                        {{ returnDetails.status }}
                    </span>
                </h1>
                <div class="text-sm text-slate-500">
                    Created on {{ formatDate(returnDetails.created_at) }}
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Reason & Details -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 shadow-sm">
                    <h2 class="font-bold text-lg text-slate-900 dark:text-white mb-4">Details</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm text-slate-500 mb-1">Reason</p>
                            <p class="font-medium text-slate-900 dark:text-white">{{ returnDetails.reason }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500 mb-1">Return Date</p>
                            <p class="font-medium text-slate-900 dark:text-white flex items-center gap-2">
                                <CalendarIcon class="w-4 h-4" />
                                {{ formatDate(returnDetails.return_date) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500 mb-1">PO Reference</p>
                            <Link v-if="returnDetails.purchase_order" :href="route('portal.purchase-orders.show', returnDetails.purchase_order_id)" class="font-medium text-indigo-600 hover:underline flex items-center gap-2">
                                <DocumentTextIcon class="w-4 h-4" />
                                {{ returnDetails.purchase_order.po_number }}
                            </Link>
                            <span v-else>-</span>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500 mb-1">Warehouse</p>
                            <p class="font-medium text-slate-900 dark:text-white flex items-center gap-2">
                                <MapPinIcon class="w-4 h-4" />
                                {{ returnDetails.warehouse?.name || '-' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Items -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div class="p-6 border-b border-slate-200 dark:border-slate-700">
                        <h2 class="font-bold text-lg text-slate-900 dark:text-white">Returned Items</h2>
                    </div>
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-700/50 text-slate-500">
                            <tr>
                                <th class="px-6 py-3">Product</th>
                                <th class="px-6 py-3 text-right">Qty</th>
                                <th class="px-6 py-3 text-right">Price</th>
                                <th class="px-6 py-3 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            <tr v-for="item in returnDetails.items" :key="item.id">
                                <td class="px-6 py-4">
                                    <p class="font-bold text-slate-900 dark:text-white">{{ item.product?.name || 'Unknown Product' }}</p>
                                    <p class="text-xs text-slate-500">{{ item.product?.code }}</p>
                                </td>
                                <td class="px-6 py-4 text-right">{{ item.qty }}</td>
                                <td class="px-6 py-4 text-right">{{ formatCurrency(item.price) }}</td>
                                <td class="px-6 py-4 text-right font-bold">{{ formatCurrency(item.total_price) }}</td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-slate-50 dark:bg-slate-700/50">
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-right font-bold text-slate-900 dark:text-white">Total Amount</td>
                                <td class="px-6 py-4 text-right font-bold text-indigo-600">{{ formatCurrency(returnDetails.total_amount) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Sidebar Info -->
            <div class="space-y-6">
                <div class="bg-amber-50 rounded-2xl p-6 border border-amber-100">
                    <h3 class="font-bold text-amber-900 mb-2 flex items-center gap-2">
                        <ExclamationCircleIcon class="w-5 h-5 text-amber-600" />
                        Important Note
                    </h3>
                    <p class="text-sm text-amber-800">
                        This return request requires your attention. Please arrange for the collection of these items or provide a credit note as per the agreement.
                    </p>
                </div>
            </div>
        </div>
    </PortalLayout>
</template>
