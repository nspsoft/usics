<script setup>
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ArrowLeftIcon,
    PencilSquareIcon,
    PrinterIcon,
    DocumentTextIcon,
    CheckIcon,
    PaperAirplaneIcon,
    XMarkIcon,
    TruckIcon,
} from '@heroicons/vue/24/outline';
import { formatNumber, formatCurrency } from '@/helpers';

const props = defineProps({
    purchaseOrder: Object,
});

const editingItemId = ref(null);
const adjustmentForm = useForm({
    qty: 0,
    reason: ''
});

const startEditing = (item) => {
    editingItemId.value = item.id;
    adjustmentForm.qty = parseFloat(item.qty) || 0;
    adjustmentForm.reason = '';
};

const cancelEditing = () => {
    editingItemId.value = null;
};

const submitAdjustment = (itemId) => {
    adjustmentForm.put(route('purchasing.orders.update-item-qty', itemId), {
        preserveScroll: true,
        onSuccess: () => {
            editingItemId.value = null;
        }
    });
};

const submitPO = () => {
    if (confirm('Are you sure you want to submit this order for approval?')) {
        router.post(route('purchasing.orders.submit', props.purchaseOrder.id));
    }
};

const approvePO = () => {
    if (confirm('Are you sure you want to approve this order?')) {
        router.post(route('purchasing.orders.approve', props.purchaseOrder.id));
    }
};

const markOrdered = () => {
    if (confirm('Are you sure you want to mark this as ordered?')) {
        router.post(route('purchasing.orders.mark-ordered', props.purchaseOrder.id));
    }
};

const cancelPO = () => {
    if (confirm('Are you sure you want to cancel this order?')) {
        router.post(route('purchasing.orders.cancel', props.purchaseOrder.id));
    }
};

const getStatusBadge = (status) => {
    const badges = {
        draft: 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30',
        submitted: 'bg-amber-500/20 text-amber-400 border-amber-500/30',
        approved: 'bg-blue-500/20 text-blue-400 border-blue-500/30',
        ordered: 'bg-purple-500/20 text-purple-400 border-purple-500/30',
        partial: 'bg-orange-500/20 text-orange-400 border-orange-500/30',
        received: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
        cancelled: 'bg-red-500/20 text-red-400 border-red-500/30',
    };
    return badges[status] || 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30';
};



const hoverText = ref('');

const setHoverText = (text) => {
    hoverText.value = text;
};

const clearHoverText = () => {
    hoverText.value = '';
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
};
</script>

<template>
    <Head :title="`PO ${purchaseOrder.po_number}`" />
    
    <AppLayout title="Purchase Orders">
        <div class="max-w-full px-4 sm:px-6 lg:px-8 mx-auto">
            <div class="flex items-center justify-between mb-6">
                <Link href="/purchasing/orders" class="inline-flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white">
                    <ArrowLeftIcon class="h-4 w-4" /> Back to List
                </Link>

                <div class="flex items-center gap-3">
                    <a :href="`/purchasing/orders/${purchaseOrder.id}/print`" target="_blank" class="inline-flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-800 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-700 hover:text-slate-900 dark:text-white transition-colors">
                        <PrinterIcon class="h-4 w-4" />
                        Print
                    </a>

                    <!-- Workflow Buttons -->
                    <template v-if="purchaseOrder.status === 'draft'">
                         <button 
                            @click="submitPO"
                            @mouseenter="setHoverText('Submit for Approval')"
                            @mouseleave="clearHoverText"
                            class="inline-flex items-center gap-2 rounded-xl bg-amber-600/10 border border-amber-600/20 px-4 py-2 text-sm font-semibold text-amber-400 hover:bg-amber-600/20 transition-colors"
                        >
                            <PaperAirplaneIcon class="h-4 w-4" />
                            Submit
                        </button>
                        <Link
                            :href="`/purchasing/orders/${purchaseOrder.id}/edit`"
                            @mouseenter="setHoverText('Edit Purchase Order')"
                            @mouseleave="clearHoverText"
                            class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 transition-colors shadow-lg shadow-blue-500/25"
                        >
                            <PencilSquareIcon class="h-4 w-4" />
                            Edit
                        </Link>
                    </template>

                     <template v-if="purchaseOrder.status === 'submitted'">
                         <button 
                            @click="approvePO"
                            @mouseenter="setHoverText('Approve Order')"
                            @mouseleave="clearHoverText"
                            class="inline-flex items-center gap-2 rounded-xl bg-green-600/10 border border-green-600/20 px-4 py-2 text-sm font-semibold text-green-400 hover:bg-green-600/20 transition-colors"
                        >
                            <CheckIcon class="h-4 w-4" />
                            Approve
                        </button>
                         <button 
                            @click="cancelPO"
                            @mouseenter="setHoverText('Reject/Cancel Order')"
                            @mouseleave="clearHoverText"
                            class="inline-flex items-center gap-2 rounded-xl bg-red-600/10 border border-red-600/20 px-4 py-2 text-sm font-semibold text-red-400 hover:bg-red-600/20 transition-colors"
                        >
                            <XMarkIcon class="h-4 w-4" />
                            Reject
                        </button>
                    </template>

                    <template v-if="purchaseOrder.status === 'approved'">
                         <button 
                            @click="markOrdered"
                            @mouseenter="setHoverText('Mark as Sent to Supplier')"
                            @mouseleave="clearHoverText"
                            class="inline-flex items-center gap-2 rounded-xl bg-purple-600/10 border border-purple-600/20 px-4 py-2 text-sm font-semibold text-purple-400 hover:bg-purple-600/20 transition-colors"
                        >
                            <TruckIcon class="h-4 w-4" />
                            Mark Ordered
                        </button>
                    </template>
                </div>
            </div>

            <div class="space-y-6">
                <!-- Header Info -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-2 glass-card rounded-2xl p-6">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-500/10">
                                <DocumentTextIcon class="h-6 w-6 text-blue-400" />
                            </div>
                            <div>
                                <h1 class="text-xl font-bold text-slate-900 dark:text-white">{{ purchaseOrder.po_number }}</h1>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-sm text-slate-500">Created by {{ purchaseOrder.created_by?.name }} on {{ formatDate(purchaseOrder.created_at) }}</span>
                                    <span 
                                        class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium"
                                        :class="getStatusBadge(purchaseOrder.status)"
                                    >
                                        {{ purchaseOrder.status }}
                                    </span>
                                    <span v-if="hoverText" class="text-sm font-medium text-blue-400 animate-pulse">
                                        {{ hoverText }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-6 pt-6 border-t border-slate-200 dark:border-slate-800">
                            <div>
                                <div class="text-xs text-slate-500 uppercase tracking-wider mb-1">Supplier</div>
                                <div class="text-sm font-medium text-slate-900 dark:text-white">{{ purchaseOrder.supplier?.name }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-slate-500 uppercase tracking-wider mb-1">Order Date</div>
                                <div class="text-sm font-medium text-slate-900 dark:text-white">{{ formatDate(purchaseOrder.order_date) }}</div>
                            </div>
                             <div>
                                <div class="text-xs text-slate-500 uppercase tracking-wider mb-1">Expected</div>
                                <div class="text-sm font-medium text-slate-900 dark:text-white">{{ formatDate(purchaseOrder.expected_date) }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-slate-500 uppercase tracking-wider mb-1">Total Amount</div>
                                <div class="text-sm font-bold text-slate-900 dark:text-white">{{ formatCurrency(purchaseOrder.total) }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="glass-card rounded-2xl p-6">
                        <h3 class="text-sm font-medium text-slate-500 dark:text-slate-400 mb-3">Warehouse & Notes</h3>
                        <div class="mb-4">
                            <div class="text-xs text-slate-500 uppercase tracking-wider mb-1">Destination</div>
                            <div class="text-sm font-medium text-slate-900 dark:text-white">{{ purchaseOrder.warehouse?.name }}</div>
                        </div>
                        <div class="mb-4">
                            <div class="text-xs text-slate-500 uppercase tracking-wider mb-1">Notes</div>
                            <p class="text-sm text-slate-600 dark:text-slate-300 whitespace-pre-line">{{ purchaseOrder.notes || 'No notes provided.' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="glass-card rounded-2xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800">
                        <h3 class="font-semibold text-slate-900 dark:text-white">Order Items</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50">
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Product</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Qty</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Received</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Returned</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Balance</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Unit Price</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Disc %</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr v-for="item in purchaseOrder.items" :key="item.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div>
                                                <div class="text-sm font-medium text-slate-900 dark:text-white">{{ item.product?.name }}</div>
                                                <div class="text-xs text-slate-500">{{ item.product?.code || item.product?.sku }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right min-w-[200px]">
                                        <div v-if="editingItemId === item.id" class="flex flex-col gap-2 items-end">
                                            <div class="flex items-center gap-2">
                                                <input 
                                                    v-model="adjustmentForm.qty"
                                                    type="number"
                                                    step="any"
                                                    class="w-20 rounded-lg border-0 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 py-1 font-bold text-center"
                                                    @keyup.enter="submitAdjustment(item.id)"
                                                />
                                                <div class="flex items-center gap-1">
                                                    <button 
                                                        @click="submitAdjustment(item.id)"
                                                        class="p-1.5 rounded-lg bg-emerald-500/10 text-emerald-500 hover:bg-emerald-500/20 transition-colors"
                                                        title="Save"
                                                    >
                                                        <CheckIcon class="h-4 w-4" />
                                                    </button>
                                                    <button 
                                                        @click="cancelEditing"
                                                        class="p-1.5 rounded-lg bg-red-500/10 text-red-500 hover:bg-red-500/20 transition-colors"
                                                        title="Cancel"
                                                    >
                                                        <XMarkIcon class="h-4 w-4" />
                                                    </button>
                                                </div>
                                            </div>
                                            <input 
                                                v-model="adjustmentForm.reason"
                                                type="text"
                                                placeholder="Alasan koreksi..."
                                                class="w-full rounded-lg border-0 bg-slate-50 dark:bg-slate-800 text-[10px] text-slate-500 dark:text-slate-400 focus:ring-1 focus:ring-blue-500/50 py-1 italic text-right"
                                                required
                                            />
                                            <div v-if="adjustmentForm.errors.qty" class="text-[9px] text-red-500 font-bold uppercase">{{ adjustmentForm.errors.qty }}</div>
                                        </div>
                                        <div v-else class="flex items-center justify-end gap-2 group">
                                            <span class="text-sm font-medium text-slate-900 dark:text-white">{{ formatNumber(item.qty) }} {{ item.unit?.name }}</span>
                                            <button 
                                                @click="startEditing(item)"
                                                class="p-1 rounded-md text-blue-500 bg-blue-500/5 hover:bg-blue-500/10 transition-all shadow-sm"
                                                title="Koreksi Qty"
                                            >
                                                <PencilSquareIcon class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <span class="text-sm text-slate-600 dark:text-slate-300">{{ formatNumber(item.qty_received || 0) }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <span class="text-sm text-red-400">{{ formatNumber(item.qty_returned || 0) }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <span 
                                            class="text-sm font-bold"
                                            :class="(item.qty - (item.qty_received - (item.qty_returned || 0))) > 0 ? 'text-amber-400' : 'text-emerald-500'"
                                        >
                                            {{ formatNumber(item.qty - ((item.qty_received || 0) - (item.qty_returned || 0))) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <div class="text-sm text-slate-600 dark:text-slate-300">{{ formatCurrency(item.unit_price) }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <div class="text-sm text-slate-600 dark:text-slate-300">{{ formatNumber(item.discount_percent) }}%</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <div class="text-sm font-medium text-slate-900 dark:text-white">{{ formatCurrency((item.qty * item.unit_price) * (1 - item.discount_percent/100)) }}</div>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50">
                                <tr>
                                    <td colspan="7" class="px-6 py-4 text-right text-sm font-medium text-slate-500 dark:text-slate-400">Subtotal</td>
                                    <td class="px-6 py-4 text-right text-sm font-medium text-slate-900 dark:text-white">
                                        {{ formatCurrency(purchaseOrder.total * 100 / (100 + parseFloat(purchaseOrder.tax_percent || 11))) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="7" class="px-6 py-4 text-right text-sm font-medium text-slate-500 dark:text-slate-400">VAT ({{ purchaseOrder.tax_percent }}%)</td>
                                    <td class="px-6 py-4 text-right text-sm font-medium text-slate-900 dark:text-white">
                                         {{ formatCurrency(purchaseOrder.total - (purchaseOrder.total * 100 / (100 + parseFloat(purchaseOrder.tax_percent || 11)))) }}
                                    </td>
                                </tr>
                                 <tr>
                                    <td colspan="7" class="px-6 py-4 text-right text-base font-bold text-slate-900 dark:text-white">Grand Total</td>
                                    <td class="px-6 py-4 text-right text-base font-bold text-blue-400">
                                        {{ formatCurrency(purchaseOrder.total) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>



