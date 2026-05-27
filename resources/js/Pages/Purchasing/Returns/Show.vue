<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ArrowLeftIcon,
    PrinterIcon,
    CheckCircleIcon,
    DocumentTextIcon,
    InformationCircleIcon,
} from '@heroicons/vue/24/outline';
import { formatNumber, formatCurrency } from '@/helpers';

const props = defineProps({
    purchaseReturn: Object,
});

const confirmReturn = () => {
    if (!props.purchaseReturn?.id) return;
    if (confirm('Are you sure you want to confirm this return? This will update your stock levels.')) {
        router.post(route('purchasing.purchase-returns.confirm', props.purchaseReturn.id));
    }
};


const formatDate = (date) => {
    if (!date) return '-';
    try {
        return new Date(date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
    } catch (e) {
        return date;
    }
};
</script>

<template>
    <Head :title="`Return ${purchaseReturn?.number || 'Details'}`" />
    
    <AppLayout title="Purchase Returns">
        <div v-if="purchaseReturn" class="max-w-7xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <Link href="/purchasing/returns" class="inline-flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white">
                    <ArrowLeftIcon class="h-4 w-4" /> Back to List
                </Link>
                <div class="flex items-center gap-3">
                    <button @click="window?.print()" class="inline-flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-800 px-4 py-2 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-700">
                        <PrinterIcon class="h-4 w-4" /> Print
                    </button>
                    <button 
                        v-if="purchaseReturn.status === 'draft'"
                        @click="confirmReturn"
                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-slate-900 dark:text-white hover:bg-emerald-500 shadow-lg shadow-emerald-900/20"
                    >
                        <CheckCircleIcon class="h-4 w-4" /> Confirm & Update Stock
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Info Column -->
                <div class="space-y-6">
                    <div class="glass-card rounded-2xl p-6">
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4">Header Information</h3>
                        <div class="space-y-4">
                            <div>
                                <p class="text-[10px] text-slate-500 uppercase font-bold">Return Number</p>
                                <p class="text-sm font-mono text-slate-900 dark:text-white">{{ purchaseReturn.number || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-slate-500 uppercase font-bold">Status</p>
                                <span class="inline-flex mt-1 rounded-full px-2 py-0.5 text-xs font-medium" 
                                    :class="purchaseReturn.status === 'confirmed' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-slate-500/20 text-slate-500 dark:text-slate-400'">
                                    {{ purchaseReturn.status ? purchaseReturn.status.toUpperCase() : 'DRAFT' }}
                                </span>
                            </div>
                            <div>
                                <p class="text-[10px] text-slate-500 uppercase font-bold">Date</p>
                                <p class="text-sm text-slate-900 dark:text-white">{{ formatDate(purchaseReturn.return_date) }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-slate-500 uppercase font-bold">Warehouse</p>
                                <p class="text-sm text-slate-900 dark:text-white">{{ purchaseReturn.warehouse?.name || 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="glass-card rounded-2xl p-6">
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4">Supplier</h3>
                        <p class="text-sm font-bold text-slate-900 dark:text-white">{{ purchaseReturn.supplier?.name || 'Unknown' }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ purchaseReturn.supplier?.code || '-' }}</p>
                    </div>

                    <div v-if="purchaseReturn.reason" class="glass-card rounded-2xl p-6">
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4">Reason</h3>
                        <p class="text-sm text-slate-600 dark:text-slate-300 italic">{{ purchaseReturn.reason }}</p>
                    </div>
                </div>

                <!-- Items Column -->
                <div class="md:col-span-2 space-y-6">
                    <div class="glass-card rounded-2xl overflow-hidden">
                        <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 text-left">
                                    <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Product</th>
                                    <th class="px-6 py-3 text-right text-[10px] font-bold text-slate-500 uppercase tracking-widest">Qty</th>
                                    <th class="px-6 py-3 text-right text-[10px] font-bold text-slate-500 uppercase tracking-widest">Price</th>
                                    <th class="px-6 py-3 text-right text-[10px] font-bold text-slate-500 uppercase tracking-widest">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr v-for="item in purchaseReturn.items" :key="item.id">
                                    <td class="px-6 py-4 text-sm">
                                        <div class="text-slate-900 dark:text-white font-medium">{{ item.product?.name || 'Unknown' }}</div>
                                        <div class="text-xs text-slate-500 font-mono">{{ item.product?.sku || '-' }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-right text-slate-600 dark:text-slate-300">{{ formatNumber(item.qty || 0) }}</td>
                                    <td class="px-6 py-4 text-sm text-right text-slate-600 dark:text-slate-300">{{ formatCurrency(item.unit_price) }}</td>
                                    <td class="px-6 py-4 text-sm text-right text-slate-900 dark:text-white font-medium">{{ formatCurrency(item.total_price) }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="bg-slate-50 dark:bg-slate-800/20">
                                    <td colspan="3" class="px-6 py-4 text-sm font-bold text-right text-slate-500 dark:text-slate-400 uppercase">Grand Total</td>
                                    <td class="px-6 py-4 text-lg font-bold text-right text-slate-900 dark:text-white">{{ formatCurrency(purchaseReturn.total_amount) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="flex items-center gap-4 bg-white dark:bg-slate-950/50 border border-slate-200 dark:border-slate-800 rounded-2xl p-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-500/10 text-blue-400">
                            <DocumentTextIcon class="h-6 w-6" v-if="purchaseReturn.purchase_order_id" />
                            <InformationCircleIcon class="h-6 w-6" v-else />
                        </div>
                        <div v-if="purchaseReturn.purchase_order_id">
                            <p class="text-xs text-slate-500">Linked to Order</p>
                            <Link :href="`/purchasing/orders/${purchaseReturn.purchase_order_id}`" class="text-sm font-bold text-blue-400 hover:underline">
                                {{ purchaseReturn.purchase_order?.po_number || 'View Order' }}
                            </Link>
                        </div>
                        <p v-else class="text-sm text-slate-500 italic">No linked purchase order.</p>
                    </div>
                </div>
            </div>
        </div>
        <div v-else class="text-slate-900 dark:text-white text-center py-20">
            Loading return data...
        </div>
    </AppLayout>
</template>



