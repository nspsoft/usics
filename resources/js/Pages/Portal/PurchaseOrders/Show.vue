<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import { 
    ArrowLeftIcon,
    CheckCircleIcon,
    XCircleIcon,
    TruckIcon,
    BanknotesIcon
} from '@heroicons/vue/24/outline';
import { formatDate } from '@/helpers';

const props = defineProps({
    order: Object,
});

const showRejectModal = ref(false);
const rejectReason = ref('');

const acknowledge = () => {
    if (confirm('Are you sure you want to acknowledge this order? This confirms you can fulfill the items and delivery date.')) {
        router.post(route('portal.purchase-orders.acknowledge', props.order.id));
    }
};

const reject = () => {
    if (!rejectReason.value) {
        alert('Please provide a reason for rejection.');
        return;
    }
    
    router.post(route('portal.purchase-orders.reject', props.order.id), {
        reason: rejectReason.value
    }, {
        onSuccess: () => showRejectModal.value = false
    });
};
</script>

<template>
    <PortalLayout :title="`PO #${order.po_number}`">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="flex items-center gap-4 mb-6">
                <Link href="/portal/purchase-orders" class="p-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-slate-700 dark:hover:text-white transition-colors">
                    <ArrowLeftIcon class="h-5 w-5" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Order #{{ order.po_number }}</h1>
                    <p class="text-sm text-slate-500">
                        Date: {{ formatDate(order.order_date) }}
                    </p>
                </div>
                <div class="ml-auto">
                    <span class="px-3 py-1.5 rounded-full text-sm font-bold capitalize"
                        :class="{
                             'bg-purple-100 text-purple-700': order.status === 'ordered' || order.status === 'approved',
                             'bg-teal-100 text-teal-700': order.status === 'acknowledged',
                             'bg-rose-100 text-rose-700': order.status === 'rejected',
                        }">
                        {{ order.status }}
                    </span>
                </div>
            </div>

            <!-- Main Content -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left: Order Details & Items -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Order Items -->
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <div class="p-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-700/50">
                            <h2 class="font-bold text-slate-800 dark:text-white">Order Items</h2>
                        </div>
                        <div class="p-4">
                            <div v-for="item in order.items" :key="item.id" class="flex items-start gap-4 py-4 border-b border-slate-100 dark:border-slate-700/50 last:border-0">
                                <div class="h-12 w-12 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center shrink-0">
                                    <span class="text-xs font-bold text-slate-500">{{ item.id }}</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-bold text-slate-900 dark:text-white">{{ item.product?.name || item.description }}</p>
                                    <p class="text-sm text-slate-500">SKU: {{ item.product?.sku || '-' }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-slate-900 dark:text-white">{{ Number(item.qty).toLocaleString('id-ID') }} {{ item.unit?.code || 'Pcs' }}</p>
                                    <p class="text-sm text-slate-500">x Rp {{ Number(item.unit_price).toLocaleString('id-ID') }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-4 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-700/50 flex justification-between items-center text-right justify-end gap-3">
                            <span class="text-sm text-slate-500">Total Amount:</span>
                            <span class="text-lg font-bold text-indigo-600">Rp {{ Number(order.total).toLocaleString('id-ID') }}</span>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div v-if="order.notes" class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
                        <h3 class="font-bold text-slate-800 dark:text-white mb-2">Notes</h3>
                        <p class="text-sm text-slate-600 dark:text-slate-400 whitespace-pre-line">{{ order.notes }}</p>
                    </div>
                </div>

                <!-- Right: Actions -->
                <div class="space-y-6">
                    <!-- Delivery Info -->
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
                         <h3 class="font-bold text-slate-800 dark:text-white mb-4">Delivery Information</h3>
                         <div class="space-y-3 text-sm">
                            <div>
                                <span class="block text-slate-500 text-xs">Ship To:</span>
                                <span class="font-medium text-slate-900 dark:text-white">{{ order.warehouse?.name }}</span>
                                <p class="text-slate-500 text-xs mt-1">{{ order.warehouse?.address || 'See warehouse details' }}</p>
                            </div>
                         </div>
                    </div>

                    <!-- Actions Card -->
                    <!-- Approved/Ordered: Acknowledge or Reject -->
                    <div v-if="['ordered', 'approved'].includes(order.status)" class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
                        <h3 class="font-bold text-slate-800 dark:text-white mb-4">Required Action</h3>
                        <div class="space-y-3">
                            <button 
                                @click="acknowledge"
                                class="w-full flex items-center justify-center gap-2 py-2.5 rounded-xl bg-emerald-600 text-white font-bold hover:bg-emerald-700 transition-colors shadow-lg shadow-emerald-500/20"
                            >
                                <CheckCircleIcon class="h-5 w-5" />
                                Acknowledge PO
                            </button>
                            <button 
                                @click="showRejectModal = true"
                                class="w-full flex items-center justify-center gap-2 py-2.5 rounded-xl bg-red-50 text-red-600 font-bold hover:bg-red-100 transition-colors border border-red-200"
                            >
                                <XCircleIcon class="h-5 w-5" />
                                Reject PO
                            </button>
                        </div>
                        <p class="text-xs text-slate-500 mt-4 text-center">
                            By acknowledging, you agree to the price and delivery timeline.
                        </p>
                    </div>

                    <!-- Acknowledged/Partial: Create Delivery -->
                    <div v-else-if="['acknowledged', 'partial'].includes(order.status)" class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
                        <h3 class="font-bold text-slate-800 dark:text-white mb-4">Fulfillment</h3>
                         <div class="bg-blue-50 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200 p-3 rounded-lg text-xs mb-4">
                            You can send items partially. Just adjust the quantity in the next step.
                        </div>
                         <Link 
                            :href="route('portal.deliveries.create', order.id)"
                            class="w-full flex items-center justify-center gap-2 py-2.5 rounded-xl bg-indigo-600 text-white font-bold hover:bg-indigo-700 transition-colors shadow-lg shadow-indigo-500/20"
                         >
                            <TruckIcon class="h-5 w-5" />
                            Create Delivery Note
                         </Link>

                         <Link 
                            :href="route('portal.invoices.create', { po_id: order.id })"
                            class="w-full flex items-center justify-center gap-2 py-2.5 rounded-xl bg-white border-2 border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-bold hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors mt-3"
                         >
                            <BanknotesIcon class="h-5 w-5" />
                            Create Invoice
                         </Link>
                    </div>
                    
                    <!-- Received/Completed/Cancelled -->
                    <div v-else class="bg-slate-50 dark:bg-slate-700/50 border border-slate-100 dark:border-slate-600 rounded-2xl p-6 text-center">
                         <CheckCircleIcon v-if="order.status === 'received' || order.status === 'completed'" class="h-10 w-10 text-emerald-500 mx-auto mb-3" />
                         <XCircleIcon v-else class="h-10 w-10 text-slate-400 mx-auto mb-3" />
                         
                         <p class="font-bold text-slate-900 dark:text-white mb-1">Order {{ order.status }}</p>
                         <p class="text-sm text-slate-500">No further action required.</p>

                          <!-- Allow invoice creation even if received, in case they forgot -->
                          <div v-if="['received', 'completed'].includes(order.status)" class="mt-4">
                             <Link 
                                :href="route('portal.invoices.create', { po_id: order.id })"
                                class="inline-flex items-center gap-2 text-sm font-bold text-indigo-600 hover:text-indigo-800"
                             >
                                <BanknotesIcon class="h-4 w-4" />
                                Create Invoice
                             </Link>
                          </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reject Modal -->
        <div v-if="showRejectModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="showRejectModal = false"></div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl max-w-md w-full relative z-10 p-6">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Reject Purchase Order</h3>
                <p class="text-sm text-slate-500 mb-4">Please specify why you cannot fulfill this order.</p>
                <textarea 
                    v-model="rejectReason"
                    class="w-full h-32 p-3 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 focus:ring-2 focus:ring-red-500 outline-none text-sm"
                    placeholder="E.g., Item out of stock, price discrepancy..."
                ></textarea>
                <div class="flex justify-end gap-3 mt-4">
                    <button 
                        @click="showRejectModal = false"
                        class="px-4 py-2 rounded-lg text-slate-500 hover:bg-slate-100 transition-colors font-medium"
                    >
                        Cancel
                    </button>
                    <button 
                        @click="reject"
                        class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700 transition-colors font-bold shadow-lg shadow-red-500/20"
                    >
                        Confirm Rejection
                    </button>
                </div>
            </div>
        </div>
    </PortalLayout>
</template>
