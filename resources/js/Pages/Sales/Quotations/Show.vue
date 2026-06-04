<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ArrowLeftIcon, PrinterIcon, PaperAirplaneIcon, CheckCircleIcon, DocumentTextIcon, PencilSquareIcon, XCircleIcon, ShieldCheckIcon
} from '@heroicons/vue/24/outline';
import { formatNumber, formatCurrency } from '@/helpers';
import { ref, computed } from 'vue';
import ApprovalChain from '@/Components/ApprovalChain.vue';

const props = defineProps({
    quotation: Object,
});

const localHasNoWorkflow = ref(false);

const submitForApproval = () => {
    localHasNoWorkflow.value = true;
    router.post(route('sales.quotations.submit-for-approval', props.quotation.id), {}, {
        preserveScroll: true,
        onSuccess: (page) => {
            if (page.props.flash?.warning) {
                localHasNoWorkflow.value = true;
            }
        }
    });
};

const sendQuotation = () => {
    router.post(`/sales/quotations/${props.quotation.id}/send`);
};

const acceptQuotation = () => {
    router.post(`/sales/quotations/${props.quotation.id}/accept`);
};

const rejectQuotation = () => {
    if (confirm('Are you sure you want to reject this quotation?')) {
        router.post(`/sales/quotations/${props.quotation.id}/reject`);
    }
};

const convertToSO = () => {
    if (confirm('Convert this quotation to a Sales Order?')) {
        router.post(`/sales/quotations/${props.quotation.id}/convert`);
    }
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

const getStatusBadge = (status) => {
    const badges = {
        draft: 'bg-slate-500/20 text-slate-500 dark:text-slate-400',
        sent: 'bg-blue-500/20 text-blue-400',
        accepted: 'bg-emerald-500/20 text-emerald-400',
        rejected: 'bg-red-500/20 text-red-400',
        expired: 'bg-amber-500/20 text-amber-400',
    };
    return badges[status] || 'bg-slate-500/20 text-slate-500 dark:text-slate-400';
};

const canSubmitForApproval = computed(() => props.quotation.status === 'draft' && !props.quotation.approvalRequest && !localHasNoWorkflow.value && props.quotation.approval_status !== 'approved');
const canSendDirectly = computed(() => props.quotation.status === 'draft' && (!props.quotation.approvalRequest && localHasNoWorkflow.value || props.quotation.approval_status === 'approved'));

</script>

<template>
    <Head :title="`Quotation ${quotation.number}`" />
    
    <AppLayout title="Quotations">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <Link href="/sales/quotations" class="inline-flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white">
                    <ArrowLeftIcon class="h-4 w-4" /> Back to List
                </Link>
                <div class="flex items-center gap-3">
                    <a :href="route('sales.quotations.print', quotation.id)" target="_blank" class="inline-flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-800 px-4 py-2 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-700 transition-colors shadow-lg shadow-slate-900/10">
                        <PrinterIcon class="h-4 w-4" /> Print
                    </a>
                    <Link :href="route('sales.quotations.edit', quotation.id)" v-if="!['accepted', 'converted'].includes(quotation.status)" class="inline-flex items-center gap-2 rounded-xl bg-amber-500/10 px-4 py-2 text-sm font-semibold text-amber-500 hover:bg-amber-500/20 border border-amber-500/20">
                        <PencilSquareIcon class="h-4 w-4" /> Edit
                    </Link>
                    
                    <button v-if="canSubmitForApproval" @click="submitForApproval" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 shadow-lg shadow-indigo-500/20">
                        <ShieldCheckIcon class="h-4 w-4" /> Submit for Approval
                    </button>
                    
                    <button v-if="canSendDirectly" @click="sendQuotation" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500">
                        <PaperAirplaneIcon class="h-4 w-4" /> Send directly to Customer
                    </button>
                    
                    <button v-if="quotation.status === 'sent'" @click="acceptQuotation" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-slate-900 dark:text-white hover:bg-emerald-500">
                        <CheckCircleIcon class="h-4 w-4" /> Accept
                    </button>
                    <button v-if="quotation.status === 'sent'" @click="rejectQuotation" class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-slate-900 dark:text-white hover:bg-red-500">
                        <XCircleIcon class="h-4 w-4" /> Reject
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="space-y-6">
                    <div class="glass-card rounded-2xl p-6">
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4">Quotation Info</h3>
                        <div class="space-y-4">
                            <div>
                                <p class="text-[10px] text-slate-500 uppercase font-bold">Number</p>
                                <p class="text-sm font-mono text-slate-900 dark:text-white">{{ quotation.number }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-slate-500 uppercase font-bold">Status</p>
                                <span class="inline-flex mt-1 rounded-full px-2 py-0.5 text-xs font-medium" :class="getStatusBadge(quotation.status)">{{ quotation.status?.toUpperCase() }}</span>
                            </div>
                            <div>
                                <p class="text-[10px] text-slate-500 uppercase font-bold">Date</p>
                                <p class="text-sm text-slate-900 dark:text-white">{{ formatDate(quotation.quotation_date) }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-slate-500 uppercase font-bold">Valid Until</p>
                                <p class="text-sm text-slate-900 dark:text-white">{{ formatDate(quotation.valid_until) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="glass-card rounded-2xl p-6">
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4">Customer</h3>
                        <p class="text-sm font-bold text-slate-900 dark:text-white">{{ quotation.customer?.name }}</p>
                    </div>

                    <div class="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl p-6">
                        <p class="text-xs text-blue-200 uppercase font-bold mb-1">Total Amount</p>
                        <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ formatCurrency(quotation.total) }}</p>
                    </div>
                </div>

                <div class="md:col-span-2">
                    <div class="glass-card rounded-2xl overflow-hidden">
                        <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 text-left">
                                    <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Product</th>
                                    <th class="px-6 py-3 text-right text-[10px] font-bold text-slate-500 uppercase tracking-widest">Qty</th>
                                    <th class="px-6 py-3 text-right text-[10px] font-bold text-slate-500 uppercase tracking-widest">Unit Price</th>
                                    <th class="px-6 py-3 text-right text-[10px] font-bold text-slate-500 uppercase tracking-widest">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr v-for="item in quotation.items" :key="item.id">
                                    <td class="px-6 py-4 text-sm">
                                        <div class="text-slate-900 dark:text-white font-medium">{{ item.product?.name }}</div>
                                        <div class="text-xs text-slate-500 font-mono">{{ item.product?.sku }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-right text-slate-600 dark:text-slate-300">{{ item.qty }}</td>
                                    <td class="px-6 py-4 text-sm text-right text-slate-600 dark:text-slate-300">{{ formatCurrency(item.unit_price) }}</td>
                                    <td class="px-6 py-4 text-sm text-right text-slate-900 dark:text-white font-medium">{{ formatCurrency(item.total_price) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-6" v-if="quotation.approvalRequest">
                        <ApprovalChain :request="quotation.approvalRequest" :approvalStatus="quotation.approval_status" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>



