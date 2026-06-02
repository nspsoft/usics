<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    ArrowLeftIcon, 
    CogIcon,
    PlayIcon,
    CheckCircleIcon,
    XCircleIcon,
    ClipboardDocumentCheckIcon,
    PlusIcon,
    CubeIcon,
    CalendarIcon,
    ClockIcon,
    ArrowPathIcon,
    UserIcon,
    BeakerIcon,
    XMarkIcon,
    PencilIcon,
} from '@heroicons/vue/24/outline';
import { formatNumber, formatNumberFixed } from '@/helpers';

const props = defineProps({
    workOrder: Object,
    subcontractGrReceipts: Array,
});

const confirmAction = (action) => {
    router.post(route(`manufacturing.work-orders.${action}`, props.workOrder.id), {}, {
        preserveScroll: true,
    });
};



const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

const formatDateTime = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleString('id-ID');
};


const getStatusBadge = (status) => {
    const badges = {
        draft: 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30',
        confirmed: 'bg-blue-500/20 text-blue-400 border-blue-500/30',
        in_progress: 'bg-amber-500/20 text-amber-400 border-amber-500/30',
        completed: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
        cancelled: 'bg-red-500/20 text-red-400 border-red-500/30',
    };
    return badges[status] || 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30';
};

const getStatusLabel = (status) => {
    const labels = {
        draft: 'Draft',
        confirmed: 'Confirmed',
        in_progress: 'In Progress',
        completed: 'Completed',
        cancelled: 'Cancelled',
    };
    return labels[status] || status;
};

const getPriorityBadge = (priority) => {
    const badges = {
        low: 'bg-slate-500/20 text-slate-500 dark:text-slate-400',
        normal: 'bg-blue-500/20 text-blue-400',
        high: 'bg-amber-500/20 text-amber-400',
        urgent: 'bg-red-500/20 text-red-400',
    };
    return badges[priority] || 'bg-slate-500/20 text-slate-500 dark:text-slate-400';
};

const progressPercent = computed(() => {
    const planned = parseFloat(props.workOrder.qty_planned) || 0;
    const produced = parseFloat(props.workOrder.qty_produced) || 0;
    if (planned <= 0) return 0;
    return Math.min(100, (produced / planned) * 100);
});

const remainingQty = computed(() => {
    const planned = parseFloat(props.workOrder.qty_planned) || 0;
    const produced = parseFloat(props.workOrder.qty_produced) || 0;
    return Math.max(0, planned - produced);
});

const canConfirm = computed(() => props.workOrder.status === 'draft');
const canStart = computed(() => props.workOrder.status === 'confirmed');
const canRecordProduction = computed(() => props.workOrder.status === 'in_progress' && props.workOrder.production_type !== 'subcontract');
const canComplete = computed(() => props.workOrder.status === 'in_progress' && parseFloat(props.workOrder.qty_produced) > 0 && props.workOrder.production_type !== 'subcontract');
const canCancel = computed(() => !['completed', 'cancelled'].includes(props.workOrder.status));
const canReopen = computed(() => props.workOrder.status === 'cancelled');
</script>

<template>
    <Head :title="`Work Order ${workOrder.wo_number}`" />
    
    <AppLayout title="Work Orders">
        <div class="max-w-full px-4 sm:px-6 lg:px-8 mx-auto">
            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center gap-4">
                    <Link 
                        :href="route('manufacturing.work-orders.index')" 
                        class="p-2.5 rounded-xl glass-card text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:border-slate-200 dark:border-slate-700 transition-all"
                    >
                        <ArrowLeftIcon class="h-5 w-5" />
                    </Link>
                    <div>
                        <div class="flex items-center gap-3">
                            <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">{{ workOrder.wo_number }}</h2>
                            <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider" :class="getStatusBadge(workOrder.status)">
                                {{ getStatusLabel(workOrder.status) }}
                            </span>
                            <span 
                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider border"
                                :class="workOrder.production_type === 'subcontract' ? 'bg-amber-500/20 text-amber-400 border-amber-500/30' : 'bg-blue-500/20 text-blue-400 border-blue-500/30'"
                            >
                                {{ workOrder.production_type }}
                            </span>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase" :class="getPriorityBadge(workOrder.priority)">
                                {{ workOrder.priority }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1 uppercase tracking-widest font-bold">Production Work Order</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <a 
                        :href="route('manufacturing.work-orders.print', workOrder.id)" 
                        target="_blank"
                        class="flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-800 px-4 py-2.5 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:text-white hover:bg-slate-700 transition-all border border-slate-200 dark:border-slate-700"
                    >
                        <PrinterIcon class="h-5 w-5" />
                        Print WO
                    </a>
                    <a 
                        v-if="workOrder.production_type === 'subcontract' && workOrder.subcontract_orders?.length > 0"
                        :href="route('manufacturing.subcontract-orders.print', workOrder.subcontract_orders[0].id)" 
                        target="_blank"
                        class="flex items-center gap-2 rounded-xl bg-white dark:bg-slate-950 px-4 py-2.5 text-sm font-semibold text-red-500 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-all border border-red-500/30"
                    >
                        <PrinterIcon class="h-5 w-5" />
                        Official SCO
                    </a>
                    <Link 
                        v-if="canConfirm"
                        :href="route('manufacturing.work-orders.edit', workOrder.id)"
                        class="flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-800 px-4 py-2.5 text-sm font-semibold text-blue-400 hover:text-blue-300 hover:bg-slate-700 transition-all border border-slate-200 dark:border-slate-700"
                    >
                        <PencilIcon class="h-5 w-5" />
                        Edit WO
                    </Link>
                    <button 
                        v-if="canConfirm"
                        @click="confirmAction('confirm')"
                        class="flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white dark:text-white hover:bg-blue-500 transition-all"
                    >
                        <ClipboardDocumentCheckIcon class="h-5 w-5" />
                        Confirm
                    </button>
                    <button 
                        v-if="canStart"
                        @click="confirmAction('revert-to-draft')"
                        class="flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-800 px-4 py-2.5 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-700 transition-all border border-slate-200 dark:border-slate-700"
                        title="Revert to Draft for revision"
                    >
                        <ArrowPathIcon class="h-5 w-5" />
                        Revert to Draft
                    </button>
                    <button 
                        v-if="canStart"
                        @click="confirmAction('start')"
                        class="flex items-center gap-2 rounded-xl bg-gradient-to-r from-amber-600 to-amber-500 px-4 py-2.5 text-sm font-semibold text-slate-900 dark:text-white shadow-lg shadow-amber-500/25 hover:from-amber-500 hover:to-amber-400 transition-all"
                    >
                        <PlayIcon class="h-5 w-5" />
                        Start Production
                    </button>
                    <Link 
                        v-if="canRecordProduction"
                        :href="route('manufacturing.work-orders.record-production-form', workOrder.id)"
                        class="flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-emerald-500 px-4 py-2.5 text-sm font-semibold text-slate-900 dark:text-white shadow-lg shadow-emerald-500/25 hover:from-emerald-500 hover:to-emerald-400 transition-all"
                    >
                        <PlusIcon class="h-5 w-5" />
                        Record Output
                    </Link>
                    <button 
                        v-if="canComplete"
                        @click="confirmAction('complete')"
                        class="flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-slate-900 dark:text-white hover:bg-emerald-500 transition-all"
                    >
                        <CheckCircleIcon class="h-5 w-5" />
                        Complete
                    </button>
                    <button 
                        v-if="canCancel"
                        @click="confirmAction('cancel')"
                        class="flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-800 px-4 py-2.5 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-700 transition-all"
                    >
                        <XCircleIcon class="h-5 w-5" />
                        Cancel
                    </button>
                    <button 
                        v-if="canReopen"
                        @click="confirmAction('reopen')"
                        class="flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-800 px-4 py-2.5 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-700 transition-all border border-slate-200 dark:border-slate-700"
                    >
                        <ArrowPathIcon class="h-5 w-5" />
                        Reopen to Draft
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">
                <div class="xl:col-span-8 space-y-8">
                    <!-- Progress Card -->
                    <div class="glass-card rounded-3xl p-6 shadow-sm">
                        <h3 class="text-sm font-bold text-slate-500 uppercase tracking-widest mb-6 border-b border-slate-200 dark:border-slate-800 pb-4 flex items-center gap-2">
                            <CogIcon class="h-4 w-4" />
                            Production Progress
                        </h3>
                        <div class="grid grid-cols-4 gap-6 mb-6">
                            <div>
                                <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Planned</div>
                                <div class="text-2xl font-bold text-slate-900 dark:text-white font-mono">{{ formatNumber(workOrder.qty_planned) }}</div>
                            </div>
                            <div>
                                <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Produced</div>
                                <div class="text-2xl font-bold text-emerald-400 font-mono">{{ formatNumber(workOrder.qty_produced) }}</div>
                            </div>
                            <div>
                                <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Rejected</div>
                                <div class="text-2xl font-bold text-red-400 font-mono">{{ formatNumber(workOrder.qty_rejected) }}</div>
                            </div>
                            <div>
                                <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Remaining</div>
                                <div class="text-2xl font-bold text-amber-400 font-mono">{{ formatNumber(remainingQty) }}</div>
                            </div>
                        </div>
                        <!-- Progress Bar -->
                        <div class="relative">
                            <div class="h-4 bg-slate-50 dark:bg-slate-800 rounded-full overflow-hidden">
                                <div 
                                    class="h-full bg-gradient-to-r from-cyan-500 to-blue-600 rounded-full transition-all duration-500"
                                    :style="{ width: `${progressPercent}%` }"
                                ></div>
                            </div>
                            <div class="flex justify-between mt-2 text-[10px] text-slate-500 font-bold">
                                <span>0%</span>
                                <span>{{ progressPercent.toFixed(1) }}% Complete</span>
                                <span>100%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Product Info -->
                    <div class="glass-card rounded-3xl p-6 shadow-sm">
                        <h3 class="text-sm font-bold text-slate-500 uppercase tracking-widest mb-6 border-b border-slate-200 dark:border-slate-800 pb-4 flex items-center gap-2">
                            <CubeIcon class="h-4 w-4" />
                            Product Information
                        </h3>
                        <div class="flex items-center gap-4">
                            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-600/20 to-blue-600/20 border border-cyan-500/30">
                                <CubeIcon class="h-8 w-8 text-cyan-400" />
                            </div>
                            <div>
                                <div class="text-lg font-bold text-slate-900 dark:text-white">{{ workOrder.product?.name }}</div>
                                <div class="text-xs text-slate-500 font-mono mt-1">{{ workOrder.product?.sku }}</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">BOM: {{ workOrder.bom?.name }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Components -->
                    <div class="glass-card rounded-3xl shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-slate-200 dark:border-slate-800">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2 font-mono">
                                <div class="h-6 w-1 bg-cyan-500 rounded-full"></div>
                                COMPONENTS
                            </h3>
                        </div>
                        <div class="overflow-x-auto max-h-[300px] overflow-y-auto custom-scrollbar relative">
                            <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800 text-sm">
                                <thead class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900 shadow-sm">
                                    <tr class="bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 text-left">
                                        <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Material</th>
                                        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-widest">Required Qty</th>
                                        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-widest">Consumed</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <tr v-for="comp in workOrder.components" :key="comp.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="text-slate-900 dark:text-white font-medium">{{ comp.product?.name }}</div>
                                            <div class="text-[10px] text-slate-500 font-mono mt-0.5">{{ comp.product?.sku }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-right text-slate-600 dark:text-slate-300 font-mono">{{ formatNumberFixed(comp.qty_required, 2) }}</td>
                                        <td class="px-6 py-4 text-right text-emerald-400 font-mono">{{ formatNumberFixed(comp.qty_consumed || 0, 2) }}</td>
                                    </tr>
                                    <tr v-if="!workOrder.components || workOrder.components.length === 0">
                                        <td colspan="3" class="px-6 py-8 text-center text-slate-500 italic">No components defined</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Production Entries -->
                    <div v-if="workOrder.production_entries && workOrder.production_entries.length > 0" class="glass-card rounded-3xl shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-slate-200 dark:border-slate-800">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2 font-mono">
                                <div class="h-6 w-1 bg-emerald-500 rounded-full"></div>
                                PRODUCTION_ENTRIES
                            </h3>
                        </div>
                        <div class="overflow-x-auto max-h-[400px] overflow-y-auto custom-scrollbar relative">
                            <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800 text-sm">
                                <thead class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900 shadow-sm">
                                    <tr class="bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 text-left">
                                        <th class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Date / Shift</th>
                                        <th class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Operator</th>
                                        <th class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Entry By</th>
                                        <th class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Time</th>
                                        <th class="px-4 py-3 text-right text-[10px] font-bold text-slate-500 uppercase tracking-widest">Good</th>
                                        <th class="px-4 py-3 text-right text-[10px] font-bold text-slate-500 uppercase tracking-widest">Reject</th>
                                        <th class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Notes</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <tr v-for="entry in workOrder.production_entries" :key="entry.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors">
                                        <td class="px-4 py-3">
                                            <div class="text-slate-900 dark:text-white">{{ formatDate(entry.production_date) }}</div>
                                            <div class="text-[10px] text-slate-500">Shift {{ entry.shift || '-' }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-slate-600 dark:text-slate-300 text-xs">
                                            {{ entry.operator_employee?.full_name || entry.produced_by?.name || '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-slate-600 dark:text-slate-300 text-xs">
                                            {{ entry.entry_user?.name || '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-slate-500 dark:text-slate-400 text-xs font-mono">
                                            {{ entry.start_time || '-' }} - {{ entry.end_time || '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-right text-emerald-400 font-mono font-bold">+{{ formatNumber(entry.qty_produced) }}</td>
                                        <td class="px-4 py-3 text-right text-red-400 font-mono">{{ formatNumber(entry.qty_rejected) }}</td>
                                        <td class="px-4 py-3 text-slate-500 dark:text-slate-400 text-xs max-w-[150px] truncate" :title="entry.notes">{{ entry.notes || '-' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="xl:col-span-4 space-y-8">
                    <!-- Schedule Info -->
                    <div class="glass-card rounded-3xl p-6 shadow-sm">
                        <h3 class="text-sm font-bold text-slate-500 uppercase tracking-widest mb-6 border-b border-slate-200 dark:border-slate-800 pb-4">Schedule</h3>
                        <div class="space-y-4">
                            <div class="flex items-center gap-3">
                                <div class="h-8 w-8 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-500 dark:text-slate-400">
                                    <CalendarIcon class="h-4 w-4" />
                                </div>
                                <div>
                                    <div class="text-[10px] text-slate-500 uppercase font-bold">Planned Start</div>
                                    <div class="text-xs text-slate-900 dark:text-white">{{ formatDate(workOrder.planned_start) }}</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="h-8 w-8 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-500 dark:text-slate-400">
                                    <CalendarIcon class="h-4 w-4" />
                                </div>
                                <div>
                                    <div class="text-[10px] text-slate-500 uppercase font-bold">Planned End</div>
                                    <div class="text-xs text-slate-900 dark:text-white">{{ formatDate(workOrder.planned_end) }}</div>
                                </div>
                            </div>
                            <div v-if="workOrder.actual_start" class="flex items-center gap-3">
                                <div class="h-8 w-8 rounded-full bg-emerald-500/20 flex items-center justify-center text-emerald-400">
                                    <ClockIcon class="h-4 w-4" />
                                </div>
                                <div>
                                    <div class="text-[10px] text-slate-500 uppercase font-bold">Actual Start</div>
                                    <div class="text-xs text-emerald-400">{{ formatDateTime(workOrder.actual_start) }}</div>
                                </div>
                            </div>
                            <div v-if="workOrder.actual_end" class="flex items-center gap-3">
                                <div class="h-8 w-8 rounded-full bg-emerald-500/20 flex items-center justify-center text-emerald-400">
                                    <ClockIcon class="h-4 w-4" />
                                </div>
                                <div>
                                    <div class="text-[10px] text-slate-500 uppercase font-bold">Actual End</div>
                                    <div class="text-xs text-emerald-400">{{ formatDateTime(workOrder.actual_end) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Warehouse -->
                    <div class="glass-card rounded-3xl p-6 shadow-sm">
                        <h3 class="text-sm font-bold text-slate-500 uppercase tracking-widest mb-6 border-b border-slate-200 dark:border-slate-800 pb-4">Output Warehouse</h3>
                        <div class="text-sm font-bold text-slate-900 dark:text-white">{{ workOrder.warehouse?.name }}</div>
                        <div class="text-xs text-slate-500 mt-1">{{ workOrder.warehouse?.code }}</div>
                    </div>

                    <!-- Subcontractor Info -->
                    <div v-if="workOrder.production_type === 'subcontract'" class="glass-card rounded-3xl p-6 shadow-sm border-l-4 border-l-amber-500">
                        <h3 class="text-sm font-bold text-amber-500 uppercase tracking-widest mb-6 border-b border-slate-200 dark:border-slate-800 pb-4 flex items-center gap-2">
                             <UserIcon class="h-4 w-4" />
                             Subcontractor
                        </h3>
                        <div class="text-sm font-bold text-slate-900 dark:text-white">{{ workOrder.supplier?.name || 'Unknown Vendor' }}</div>
                        <p class="text-[10px] text-slate-500 mt-1 uppercase tracking-widest font-bold">External Production Partner</p>
                    </div>

                    <div v-if="subcontractGrReceipts && subcontractGrReceipts.length > 0" class="glass-card rounded-3xl overflow-hidden shadow-sm">
                        <div class="p-6 border-b border-slate-200 dark:border-slate-800">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2 font-mono">
                                <div class="h-6 w-1 bg-blue-500 rounded-full"></div>
                                GR_RECEIPTS
                            </h3>
                        </div>
                        <div class="overflow-x-auto max-h-[240px] overflow-y-auto custom-scrollbar relative">
                            <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800 text-sm">
                                <thead class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900 shadow-sm">
                                    <tr class="bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 text-left">
                                        <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">GRN</th>
                                        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-widest">Qty</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <tr v-for="gr in subcontractGrReceipts" :key="gr.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors">
                                        <td class="px-6 py-4">
                                            <Link :href="route('purchasing.receipts.show', gr.id)" class="text-slate-900 dark:text-white font-medium hover:text-blue-500">
                                                {{ gr.grn_number }}
                                            </Link>
                                            <div class="text-[10px] text-slate-500 font-mono mt-0.5">{{ formatDate(gr.receipt_date) }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-right text-emerald-400 font-mono font-bold">{{ formatNumber(gr.qty) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div v-if="workOrder.notes" class="glass-card rounded-3xl p-6 shadow-sm">
                        <h3 class="text-sm font-bold text-slate-500 uppercase tracking-widest mb-4 border-b border-slate-200 dark:border-slate-800 pb-4">Notes</h3>
                        <div class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 p-4 rounded-xl border border-slate-200 dark:border-slate-800 italic">
                            {{ workOrder.notes }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>



