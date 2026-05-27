<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ArrowLeftIcon, PrinterIcon, CheckCircleIcon, TruckIcon, DocumentTextIcon, PencilSquareIcon, TrashIcon, ArrowUturnLeftIcon, LinkIcon } from '@heroicons/vue/24/outline';
import { formatNumber, formatCurrency } from '@/helpers';

const props = defineProps({
    receipt: Object,
});

const canBeInvoiced = computed(() => {
    if (!props.receipt || props.receipt.status !== 'completed') return false;
    // Check if any item hasn't been fully invoiced
    return props.receipt.items?.some(item => parseFloat(item.qty_invoiced || 0) < parseFloat(item.qty_received));
});

const completeReceipt = () => {
    if (confirm('Are you sure you want to complete this receipt? This will update your stock levels.')) {
        router.post(`/purchasing/receipts/${props.receipt.id}/complete`);
    }
};


const formatDate = (date) => {
    return new Date(date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

const getStatusBadge = (status) => {
    const badges = {
        draft: 'bg-slate-500/20 text-slate-500 dark:text-slate-400',
        dispatched: 'bg-purple-500/20 text-purple-400',
        received: 'bg-blue-500/20 text-blue-400',
        inspected: 'bg-amber-500/20 text-amber-400',
        completed: 'bg-emerald-500/20 text-emerald-400',
    };
    return badges[status] || 'bg-slate-500/20 text-slate-500 dark:text-slate-400';
};
const deleteReceipt = () => {
    if (confirm('Are you sure you want to delete this draft receipt?')) {
        router.delete(route('purchasing.receipts.destroy', props.receipt.id));
    }
};
</script>

<template>
    <Head :title="`GRN ${receipt.grn_number}`" />
    
    <AppLayout title="Goods Receipts">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <Link href="/purchasing/receipts" class="inline-flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white">
                    <ArrowLeftIcon class="h-4 w-4" /> Back to List
                </Link>
                <div class="flex items-center gap-3">
                    <a 
                        :href="route('purchasing.receipts.print', receipt.id)" 
                        target="_blank"
                        class="inline-flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-800 px-4 py-2 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-700 hover:text-slate-900 dark:text-white transition-colors"
                    >
                        <PrinterIcon class="h-4 w-4" /> Print
                    </a>
                    <Link
                        v-if="receipt.status !== 'completed'"
                        :href="route('purchasing.receipts.edit', receipt.id)"
                        class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 transition-colors shadow-lg shadow-blue-500/25"
                    >
                        <PencilSquareIcon class="h-4 w-4" />
                        Edit
                    </Link>
                    <button 
                        v-if="receipt.status !== 'completed'"
                        @click="completeReceipt"
                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-slate-900 dark:text-white hover:bg-emerald-500 shadow-lg shadow-emerald-900/20"
                    >
                        <CheckCircleIcon class="h-4 w-4" /> Complete & Update Stock
                    </button>
                    <button 
                        v-if="receipt.status === 'draft'"
                        @click="deleteReceipt"
                        class="inline-flex items-center gap-2 rounded-xl bg-red-50 dark:bg-red-900/20 px-4 py-2 text-sm font-semibold text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/40 transition-colors"
                    >
                        <TrashIcon class="h-4 w-4" /> Delete Draft
                    </button>
                    <Link
                        v-if="canBeInvoiced"
                        :href="`/purchasing/invoices/create?goods_receipt_id=${receipt.id}`"
                        class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 shadow-lg shadow-blue-900/20"
                    >
                        <DocumentTextIcon class="h-4 w-4" /> Create Invoice
                    </Link>
                    <Link
                        v-if="receipt.status === 'completed'"
                        :href="route('purchasing.purchase-returns.create', { goods_receipt_id: receipt.id })"
                        class="inline-flex items-center gap-2 rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-500 shadow-lg shadow-amber-900/20"
                    >
                        <ArrowUturnLeftIcon class="h-4 w-4" /> Create Return (Correction)
                    </Link>
                    <Link
                        v-if="receipt.status === 'completed'"
                        :href="route('purchasing.receipts.reassign-po', receipt.id)"
                        class="inline-flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-800 px-4 py-2 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-700 hover:text-slate-900 dark:text-white transition-colors"
                    >
                        <LinkIcon class="h-4 w-4" /> Change Linked PO
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="space-y-6">
                    <div class="glass-card rounded-2xl p-6">
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4">Receipt Info</h3>
                        <div class="space-y-4">
                            <div>
                                <p class="text-[10px] text-slate-500 uppercase font-bold">GRN Number</p>
                                <p class="text-sm font-mono text-slate-900 dark:text-white">{{ receipt.grn_number }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-slate-500 uppercase font-bold">Status</p>
                                <span class="inline-flex mt-1 rounded-full px-2 py-0.5 text-xs font-medium" :class="getStatusBadge(receipt.status)">
                                    {{ receipt.status?.toUpperCase() }}
                                </span>
                            </div>
                            <div>
                                <p class="text-[10px] text-slate-500 uppercase font-bold">Receipt Date</p>
                                <p class="text-sm text-slate-900 dark:text-white">{{ formatDate(receipt.receipt_date) }}</p>
                            </div>
                            <div v-if="receipt.delivery_note_number">
                                <p class="text-[10px] text-slate-500 uppercase font-bold">No SJ Supplier</p>
                                <p class="text-sm font-mono text-slate-900 dark:text-white">{{ receipt.delivery_note_number }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-slate-500 uppercase font-bold">Warehouse</p>
                                <p class="text-sm text-slate-900 dark:text-white">{{ receipt.warehouse?.name }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="glass-card rounded-2xl p-6">
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4">Supplier</h3>
                        <p class="text-sm font-bold text-slate-900 dark:text-white">{{ receipt.supplier?.name }}</p>
                    </div>

                    <div v-if="receipt.purchase_order_id" class="glass-card rounded-2xl p-6">
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4">Linked PO</h3>
                        <Link :href="`/purchasing/orders/${receipt.purchase_order_id}`" class="text-sm font-bold text-blue-400 hover:underline">
                            {{ receipt.purchase_order?.po_number }}
                        </Link>
                    </div>
                </div>

                <div class="md:col-span-2">
                    <div class="glass-card rounded-2xl overflow-hidden">
                        <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 text-left">
                                    <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Product</th>
                                    <th class="px-6 py-3 text-right text-[10px] font-bold text-slate-500 uppercase tracking-widest">Qty Received</th>
                                    <th class="px-6 py-3 text-right text-[10px] font-bold text-slate-500 uppercase tracking-widest">Unit Cost</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr v-for="item in receipt.items" :key="item.id">
                                    <td class="px-6 py-4 text-sm">
                                        <div class="text-slate-900 dark:text-white font-medium">{{ item.product?.name }}</div>
                                        <div class="text-xs text-slate-500 font-mono">{{ item.product?.sku }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-right text-slate-600 dark:text-slate-300">{{ formatNumber(item.qty_received) }}</td>
                                    <td class="px-6 py-4 text-sm text-right text-slate-600 dark:text-slate-300">{{ formatCurrency(item.unit_cost) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>



