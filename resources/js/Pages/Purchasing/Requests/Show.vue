<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ArrowLeftIcon,
    PencilSquareIcon,
    PrinterIcon,
    DocumentTextIcon,
    CheckIcon,
    XMarkIcon,
    ArrowRightCircleIcon,
    DocumentDuplicateIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    request: Object,
});

const approve = () => {
    if (!props.request?.id) return;
    if (confirm('Are you sure you want to approve this request?')) {
        router.post(`/purchasing/requests/${props.request.id}/approve`);
    }
};

const reject = () => {
    if (!props.request?.id) return;
    if (confirm('Are you sure you want to reject this request?')) {
        router.post(`/purchasing/requests/${props.request.id}/reject`);
    }
};

const duplicatePR = () => {
    if (!props.request?.id) return;
    if (confirm('Are you sure you want to duplicate this Purchase Request? A new draft will be created.')) {
        router.post(`/purchasing/requests/${props.request.id}/duplicate`, {}, {
            preserveScroll: true
        });
    }
};

const getStatusBadge = (status) => {
    if (!status) return 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30';
    const badges = {
        draft: 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30',
        submitted: 'bg-amber-500/20 text-amber-400 border-amber-500/30',
        approved: 'bg-blue-500/20 text-blue-400 border-blue-500/30',
        processed: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
        rejected: 'bg-red-500/20 text-red-400 border-red-500/30',
    };
    return badges[status] || 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30';
};

const formatDate = (date) => {
    if (!date) return '-';
    try {
        return new Date(date).toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'long',
            year: 'numeric'
        });
    } catch (e) {
        return date;
    }
};
</script>

<template>
    <Head :title="`PR ${request?.pr_number || 'Details'}`" />
    
    <AppLayout title="Purchase Requests">
        <div v-if="request" class="max-w-7xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <Link href="/purchasing/requests" class="inline-flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white">
                    <ArrowLeftIcon class="h-4 w-4" /> Back to List
                </Link>

                <div class="flex items-center gap-3">
                    <a 
                        :href="route('purchasing.requests.print', request.id)" 
                        target="_blank"
                        class="hidden sm:inline-flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-800 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-700 hover:text-slate-900 dark:text-white transition-colors"
                    >
                        <PrinterIcon class="h-4 w-4" />
                        Print
                    </a>

                    <button
                        @click="duplicatePR"
                        class="hidden sm:inline-flex items-center gap-2 rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 px-4 py-2 text-sm font-semibold text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/40 transition-colors"
                    >
                        <DocumentDuplicateIcon class="h-4 w-4" />
                        Duplicate
                    </button>

                    <template v-if="request.status === 'draft'">
                         <button 
                            @click="approve"
                            class="inline-flex items-center gap-2 rounded-xl bg-green-600/10 border border-green-600/20 px-4 py-2 text-sm font-semibold text-green-400 hover:bg-green-600/20 transition-colors"
                        >
                            <CheckIcon class="h-4 w-4" />
                            Approve
                        </button>
                         <button 
                            @click="reject"
                            class="inline-flex items-center gap-2 rounded-xl bg-red-600/10 border border-red-600/20 px-4 py-2 text-sm font-semibold text-red-400 hover:bg-red-600/20 transition-colors"
                        >
                            <XMarkIcon class="h-4 w-4" />
                            Reject
                        </button>
                        <Link
                            :href="`/purchasing/requests/${request.id}/edit`"
                            class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 transition-colors shadow-lg shadow-blue-500/25"
                        >
                            <PencilSquareIcon class="h-4 w-4" />
                            Edit Request
                        </Link>
                    </template>

                    <Link
                        v-if="request.status === 'approved'"
                        :href="`/purchasing/orders/create?from_pr=${request.id}`"
                        class="inline-flex items-center gap-2 rounded-xl bg-purple-600 px-4 py-2 text-sm font-semibold text-slate-900 dark:text-white hover:bg-purple-500 transition-colors shadow-lg shadow-purple-500/25"
                    >
                        <ArrowRightCircleIcon class="h-4 w-4" />
                        Convert to PO
                    </Link>
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
                                <h1 class="text-xl font-bold text-slate-900 dark:text-white">{{ request.pr_number }}</h1>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-sm text-slate-500">Created by {{ request.created_by?.name || request.requester || 'Unknown' }} on {{ formatDate(request.created_at) }}</span>
                                    <span 
                                        class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium"
                                        :class="getStatusBadge(request.status)"
                                    >
                                        {{ request.status?.toUpperCase() }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-6 pt-6 border-t border-slate-200 dark:border-slate-800">
                            <div>
                                <div class="text-xs text-slate-500 uppercase tracking-wider mb-1">Request Date</div>
                                <div class="text-sm font-medium text-slate-900 dark:text-white">{{ formatDate(request.request_date) }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-slate-500 uppercase tracking-wider mb-1">Department</div>
                                <div class="text-sm font-medium text-slate-900 dark:text-white">{{ request.department || '-' }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-slate-500 uppercase tracking-wider mb-1">Requester</div>
                                <div class="text-sm font-medium text-slate-900 dark:text-white">{{ request.requester || '-' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="glass-card rounded-2xl p-6">
                        <h3 class="text-sm font-medium text-slate-500 dark:text-slate-400 mb-3">Notes</h3>
                        <p class="text-sm text-slate-600 dark:text-slate-300 whitespace-pre-line">{{ request.notes || 'No notes provided.' }}</p>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="glass-card rounded-2xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800">
                        <h3 class="font-semibold text-slate-900 dark:text-white">Requested Items</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50">
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Product / Material</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Description / Specs</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Qty</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr v-for="item in request.items" :key="item.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div>
                                                <div class="text-sm font-medium text-slate-900 dark:text-white">{{ item.product?.name || 'Unknown Product' }}</div>
                                                <div class="text-xs text-slate-500 font-mono">{{ item.product?.sku || item.product?.code || '-' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-slate-600 dark:text-slate-300 max-w-md truncate">{{ item.description || '-' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <div class="text-sm font-medium text-slate-900 dark:text-white">{{ parseFloat(item.qty || 0) }}</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div v-else class="text-slate-900 dark:text-white text-center py-20">
            Loading request data...
        </div>
    </AppLayout>
</template>



