<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    TruckIcon,
    CheckCircleIcon,
    ArrowLongRightIcon,
    ArrowLeftIcon,
    PaperAirplaneIcon,
    InboxArrowDownIcon,
    ClockIcon,
    XCircleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    transfer: Object,
});

const getStatusBadge = (status) => {
    const badges = {
        draft: 'bg-slate-500/20 text-slate-400 border-slate-500/30',
        in_transit: 'bg-amber-500/20 text-amber-400 border-amber-500/30',
        received: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
        cancelled: 'bg-red-500/20 text-red-400 border-red-500/30',
    };
    return badges[status] || 'bg-slate-500/20 text-slate-400 border-slate-500/30';
};

const getStatusLabel = (status) => {
    const labels = { draft: 'DRAFT', in_transit: 'IN TRANSIT', received: 'RECEIVED', cancelled: 'CANCELLED' };
    return labels[status] || status?.toUpperCase();
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const shipTransfer = () => {
    if (confirm('Kirim barang? Stok akan dikurangi dari gudang asal.')) {
        router.post(`/inventory/transfers/${props.transfer.id}/ship`);
    }
};

const receiveTransfer = () => {
    if (confirm('Terima barang? Stok akan ditambahkan ke gudang tujuan.')) {
        router.post(`/inventory/transfers/${props.transfer.id}/receive`);
    }
};

const timelineSteps = [
    { key: 'draft', label: 'Draft Created', icon: ClockIcon },
    { key: 'in_transit', label: 'Shipped', icon: TruckIcon },
    { key: 'received', label: 'Received', icon: CheckCircleIcon },
];

const getTimelineStatus = (stepKey) => {
    const order = ['draft', 'in_transit', 'received'];
    const currentIdx = order.indexOf(props.transfer.status);
    const stepIdx = order.indexOf(stepKey);
    if (props.transfer.status === 'cancelled') return 'cancelled';
    if (stepIdx < currentIdx) return 'completed';
    if (stepIdx === currentIdx) return 'current';
    return 'pending';
};
</script>

<template>
    <Head :title="`Transfer ${transfer.transfer_number}`" />

    <AppLayout :title="`Transfer ${transfer.transfer_number}`">
        <div class="max-w-5xl mx-auto space-y-6">
            <!-- Back Button -->
            <Link href="/inventory/transfers" class="inline-flex items-center gap-2 text-sm text-slate-400 hover:text-white transition-colors">
                <ArrowLeftIcon class="h-4 w-4" />
                Back to Transfers
            </Link>

            <!-- Header -->
            <div class="rounded-2xl glass-card p-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                    <div>
                        <div class="flex items-center gap-3 mb-1">
                            <TruckIcon class="h-6 w-6 text-blue-400" />
                            <h2 class="text-xl font-bold text-slate-900 dark:text-white">{{ transfer.transfer_number }}</h2>
                            <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-bold" :class="getStatusBadge(transfer.status)">
                                {{ getStatusLabel(transfer.status) }}
                            </span>
                        </div>
                        <p class="text-sm text-slate-500">Created by {{ transfer.created_by_user?.name || transfer.created_by }} on {{ formatDate(transfer.created_at) }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <button v-if="transfer.status === 'draft'" @click="shipTransfer"
                            class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-amber-600 to-amber-500 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-amber-500/25 hover:from-amber-500 hover:to-amber-400 transition-all">
                            <PaperAirplaneIcon class="h-5 w-5" />
                            Ship / Kirim
                        </button>
                        <button v-if="transfer.status === 'in_transit'" @click="receiveTransfer"
                            class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-emerald-500 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/25 hover:from-emerald-500 hover:to-emerald-400 transition-all">
                            <InboxArrowDownIcon class="h-5 w-5" />
                            Receive / Terima
                        </button>
                    </div>
                </div>

                <!-- Timeline -->
                <div class="flex items-center justify-between px-8 mb-8">
                    <div v-for="(step, idx) in timelineSteps" :key="step.key" class="flex items-center" :class="idx < timelineSteps.length - 1 ? 'flex-1' : ''">
                        <div class="flex flex-col items-center">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center border-2 transition-all"
                                :class="{
                                    'bg-emerald-500/20 border-emerald-500 text-emerald-400': getTimelineStatus(step.key) === 'completed',
                                    'bg-blue-500/20 border-blue-500 text-blue-400 ring-4 ring-blue-500/20': getTimelineStatus(step.key) === 'current',
                                    'bg-slate-800 border-slate-700 text-slate-500': getTimelineStatus(step.key) === 'pending',
                                    'bg-red-500/20 border-red-500 text-red-400': getTimelineStatus(step.key) === 'cancelled',
                                }">
                                <component :is="step.icon" class="h-5 w-5" />
                            </div>
                            <span class="text-xs font-semibold mt-2" :class="{
                                'text-emerald-400': getTimelineStatus(step.key) === 'completed',
                                'text-blue-400': getTimelineStatus(step.key) === 'current',
                                'text-slate-500': getTimelineStatus(step.key) === 'pending',
                            }">{{ step.label }}</span>
                        </div>
                        <div v-if="idx < timelineSteps.length - 1" class="flex-1 h-0.5 mx-3 mt-[-1.5rem]"
                            :class="{
                                'bg-emerald-500': getTimelineStatus(step.key) === 'completed',
                                'bg-blue-500/30': getTimelineStatus(step.key) === 'current',
                                'bg-slate-700': getTimelineStatus(step.key) === 'pending',
                            }">
                        </div>
                    </div>
                </div>

                <!-- Warehouse Direction -->
                <div class="flex items-center justify-center gap-6 py-4 px-6 rounded-xl bg-slate-50 dark:bg-slate-800/30 border border-slate-200 dark:border-slate-700/50">
                    <div class="text-center">
                        <p class="text-xs text-slate-500 uppercase font-semibold mb-1">Source</p>
                        <p class="text-lg font-bold text-red-400">{{ transfer.source_warehouse?.name }}</p>
                    </div>
                    <ArrowLongRightIcon class="h-8 w-8 text-slate-500 flex-shrink-0" />
                    <div class="text-center">
                        <p class="text-xs text-slate-500 uppercase font-semibold mb-1">Destination</p>
                        <p class="text-lg font-bold text-emerald-400">{{ transfer.destination_warehouse?.name }}</p>
                    </div>
                </div>

                <!-- Info Grid -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
                    <div>
                        <p class="text-xs text-slate-500 uppercase font-semibold">Transfer Date</p>
                        <p class="text-sm text-slate-900 dark:text-white font-medium">{{ formatDate(transfer.transfer_date) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 uppercase font-semibold">Shipped At</p>
                        <p class="text-sm text-slate-900 dark:text-white font-medium">{{ transfer.shipped_at ? formatDate(transfer.shipped_at) : '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 uppercase font-semibold">Received At</p>
                        <p class="text-sm text-slate-900 dark:text-white font-medium">{{ transfer.received_at ? formatDate(transfer.received_at) : '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 uppercase font-semibold">Received By</p>
                        <p class="text-sm text-slate-900 dark:text-white font-medium">{{ transfer.received_by_user?.name || '-' }}</p>
                    </div>
                </div>
                <div v-if="transfer.notes" class="mt-4 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/30 border border-slate-200 dark:border-slate-700/50">
                    <p class="text-xs text-slate-500 uppercase font-semibold mb-1">Notes</p>
                    <p class="text-sm text-slate-900 dark:text-white">{{ transfer.notes }}</p>
                </div>
            </div>

            <!-- Items Table -->
            <div class="rounded-2xl glass-card overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Transfer Items</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-800/50">
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">#</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Product</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Requested</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Sent</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Received</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Unit</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            <tr v-for="(item, idx) in transfer.items" :key="item.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                <td class="px-6 py-3 text-sm text-slate-500">{{ idx + 1 }}</td>
                                <td class="px-6 py-3">
                                    <div class="text-sm font-medium text-slate-900 dark:text-white">{{ item.product?.name }}</div>
                                    <div class="text-xs text-slate-500 font-mono">{{ item.product?.sku }}</div>
                                </td>
                                <td class="px-6 py-3 text-center text-sm font-mono text-slate-900 dark:text-white font-bold">
                                    {{ Number(item.qty_requested).toLocaleString() }}
                                </td>
                                <td class="px-6 py-3 text-center text-sm font-mono" :class="item.qty_sent > 0 ? 'text-amber-400 font-bold' : 'text-slate-500'">
                                    {{ Number(item.qty_sent).toLocaleString() }}
                                </td>
                                <td class="px-6 py-3 text-center text-sm font-mono" :class="item.qty_received > 0 ? 'text-emerald-400 font-bold' : 'text-slate-500'">
                                    {{ Number(item.qty_received).toLocaleString() }}
                                </td>
                                <td class="px-6 py-3 text-center text-sm text-slate-500 font-mono">
                                    {{ item.product?.unit?.symbol || item.product?.unit?.name || '-' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
