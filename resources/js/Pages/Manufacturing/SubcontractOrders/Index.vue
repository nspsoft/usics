<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatNumber, formatCurrency } from '@/helpers';
import { 
    TruckIcon, 
    UserIcon, 
    CubeIcon, 
    ClipboardDocumentListIcon,
    EyeIcon,
    MagnifyingGlassIcon,
    FunnelIcon,
    ArrowPathIcon,
    ArrowUpTrayIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';

const props = defineProps({
    orders: Object,
    filters: Object,
    statuses: Array,
    syncCancelledCount: Number,
});

const search = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || '');
const showFilters = ref(false);
const syncingCancelled = ref(false);

const applyFilters = debounce(() => {
    router.get('/manufacturing/subcontract-orders', {
        search: search.value || undefined,
        status: selectedStatus.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, selectedStatus], applyFilters);

const syncCancelled = () => {
    const count = Number(props.syncCancelledCount || 0);
    if (count <= 0 || syncingCancelled.value) return;
    if (!confirm(`Sync ${count} Subcontract Order dari WO yang sudah cancelled?`)) return;

    syncingCancelled.value = true;
    router.post(route('manufacturing.subcontract-orders.sync-cancelled'), {}, {
        preserveScroll: true,
        onFinish: () => {
            syncingCancelled.value = false;
        },
        onSuccess: () => {
            router.reload({ preserveScroll: true });
        },
    });
};

const clearFilters = () => {
    search.value = '';
    selectedStatus.value = '';
};

const getStatusBadge = (status) => {
    const badges = {
        draft: 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30',
        sent: 'bg-blue-500/20 text-blue-400 border-blue-500/30',
        received: 'bg-amber-500/20 text-amber-400 border-amber-500/30',
        completed: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
        cancelled: 'bg-red-500/20 text-red-400 border-red-500/30',
    };
    return badges[status] || 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30';
};


const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

const getDispatchQty = (order) => {
    const wo = order.work_order;
    if (!wo) return 0;
    if (!wo.components || wo.components.length === 0) {
        return ['sent', 'received', 'completed'].includes(order.status) ? parseFloat(wo.qty_planned || 0) : 0;
    }
    
    let minRatio = 1.0;
    let hasComponents = false;
    
    wo.components.forEach(comp => {
        const required = parseFloat(comp.qty_required || 0);
        const consumed = parseFloat(comp.qty_consumed || 0);
        if (required > 0) {
            hasComponents = true;
            const ratio = consumed / required;
            if (ratio < minRatio) {
                minRatio = ratio;
            }
        }
    });
    
    if (!hasComponents) return 0;
    return parseFloat(wo.qty_planned || 0) * minRatio;
};

const showDispatchModal = ref(false);
const selectedOrderForDispatch = ref(null);
const dispatchForm = useForm({
    items: [],
});

const openDispatchModal = (order) => {
    selectedOrderForDispatch.value = order;
    dispatchForm.items = order.work_order?.components.map(c => ({
        id: c.id,
        name: c.product?.name,
        sku: c.product?.sku,
        qty_required: parseFloat(c.qty_required),
        qty_consumed: parseFloat(c.qty_consumed),
        qty: Math.max(0, parseFloat(c.qty_required) - parseFloat(c.qty_consumed)),
    })) || [];
    showDispatchModal.value = true;
};

const submitDispatch = () => {
    if (!selectedOrderForDispatch.value) return;
    dispatchForm.post(route('manufacturing.subcontract-orders.dispatch', selectedOrderForDispatch.value.id), {
        onSuccess: () => {
            showDispatchModal.value = false;
            selectedOrderForDispatch.value = null;
        },
    });
};

const canDispatch = (order) => {
    return !['completed', 'cancelled'].includes(order.status);
};
</script>

<template>
    <Head title="Subcontract Orders" />
    
    <AppLayout title="Subcontract Orders">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div class="flex items-center gap-3">
                <div class="relative flex-1 sm:w-80">
                    <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500" />
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search orders or WO..."
                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 py-2.5 pl-10 pr-4 text-sm text-slate-900 dark:text-white placeholder:text-slate-500 focus:bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-blue-500/50 transition-all"
                    />
                </div>
                <button 
                    @click="showFilters = !showFilters"
                    class="flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 px-4 py-2.5 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors"
                    :class="{ 'ring-2 ring-blue-500/50': showFilters }"
                >
                    <FunnelIcon class="h-5 w-5" />
                    Filters
                </button>

                <button
                    type="button"
                    @click="syncCancelled"
                    :disabled="syncingCancelled || Number(syncCancelledCount || 0) <= 0"
                    class="flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 px-4 py-2.5 text-sm font-medium text-slate-600 dark:text-slate-300 transition-colors disabled:opacity-50 disabled:cursor-not-allowed hover:bg-slate-50 dark:hover:bg-slate-800/50"
                >
                    <ArrowPathIcon class="h-5 w-5" />
                    Sync Cancelled ({{ syncCancelledCount || 0 }})
                </button>
            </div>
            
            <div>
                 <p class="text-[10px] text-slate-500 uppercase tracking-[0.2em] font-black text-right">External Production Tracking</p>
                 <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight text-right">Subcontract Orders</h2>
            </div>
        </div>

        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-2"
        >
            <div v-if="showFilters" class="mb-6 rounded-2xl glass-card p-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Status</label>
                        <select
                            v-model="selectedStatus"
                            class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                        >
                            <option value="">All Status</option>
                            <option v-for="status in statuses" :key="status.value" :value="status.value">
                                {{ status.label }}
                            </option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button 
                            @click="clearFilters"
                            class="w-full rounded-xl bg-slate-50 dark:bg-slate-800 px-4 py-2.5 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-700 transition-colors"
                        >
                            Clear Filters
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Orders Table List -->
        <div class="rounded-2xl glass-card overflow-hidden">
            <div class="overflow-x-auto overflow-y-auto max-h-[600px]">
                <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700">
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Order & Supplier</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">WO Ref & PO Number</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Product</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-4 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Qty Order</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-4 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Qty Dispatch</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-4 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Blm Dispatch</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-4 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Recv</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-4 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Balance</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Created At</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                         <tr v-if="orders.data.length === 0">
                            <td colspan="11" class="px-6 py-20 text-center">
                                <div class="mx-auto h-20 w-20 rounded-full bg-white dark:bg-slate-950 flex items-center justify-center border border-slate-200 dark:border-slate-800 mb-4">
                                    <TruckIcon class="h-10 w-10 text-slate-700" />
                                </div>
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">No subcontract orders yet</h3>
                                <p class="text-slate-500 max-w-sm mx-auto">Create a Work Order with "Subcontract" type to start tracking external production.</p>
                            </td>
                        </tr>
                        <tr v-for="order in orders.data" :key="order.id" class="group hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors">
                            <td class="px-6 py-3 whitespace-nowrap font-medium text-slate-900 dark:text-white">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-amber-600/20 to-amber-500/20 border border-amber-500/30">
                                        <TruckIcon class="h-5 w-5 text-amber-500" />
                                    </div>
                                    <div>
                                        <Link
                                            :href="route('manufacturing.subcontract-orders.show', order.id)"
                                            class="text-sm font-bold hover:underline block"
                                        >
                                            {{ order.order_number }}
                                        </Link>
                                        <div class="flex items-center gap-1 text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                            <UserIcon class="h-3.5 w-3.5 text-slate-400" />
                                            <span>{{ order.supplier?.name || '-' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                <div class="flex flex-col gap-1">
                                    <Link :href="route('manufacturing.work-orders.show', order.work_order_id)" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:text-blue-500 transition-colors font-mono text-xs w-fit">
                                        <ClipboardDocumentListIcon class="h-3.5 w-3.5" />
                                        {{ order.work_order?.wo_number }}
                                    </Link>
                                    <div class="flex items-center gap-1 text-xs text-slate-500 font-mono pl-1 mt-0.5">
                                        <span class="text-[10px] text-slate-400 font-semibold uppercase">PO:</span>
                                        <Link v-if="order.purchase_order" :href="route('purchasing.orders.show', order.purchase_order.id)" class="font-bold text-blue-600 dark:text-blue-400 hover:underline">
                                            {{ order.purchase_order.po_number }}
                                        </Link>
                                        <span v-else class="text-slate-400">-</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <CubeIcon class="h-4 w-4 text-slate-500" />
                                    <span class="text-sm text-slate-600 dark:text-slate-300">{{ order.work_order?.product?.name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-center">
                                <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider" :class="getStatusBadge(order.status)">
                                    {{ order.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right text-slate-600 dark:text-slate-300 font-mono font-bold">{{ formatNumber(order.work_order?.qty_planned) }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-right text-blue-500 font-mono font-bold">{{ formatNumber(getDispatchQty(order)) }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-right text-amber-500 font-mono font-bold">
                                {{ formatNumber(Math.max(0, parseFloat(order.work_order?.qty_planned || 0) - getDispatchQty(order))) }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right text-emerald-400 font-mono font-bold">{{ formatNumber(order.work_order?.qty_produced) }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-right text-amber-500 font-mono font-bold">
                                {{ formatNumber(Math.max(0, parseFloat(order.work_order?.qty_planned || 0) - parseFloat(order.work_order?.qty_produced || 0))) }}
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-right">
                                <span class="font-mono text-xs text-slate-500 dark:text-slate-400">{{ formatDate(order.created_at) }}</span>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button 
                                        v-if="canDispatch(order)"
                                        @click="openDispatchModal(order)"
                                        class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 hover:bg-blue-500 px-3 py-2 text-xs font-bold text-white shadow-lg shadow-blue-500/20 transition-all"
                                    >
                                        <ArrowUpTrayIcon class="h-3.5 w-3.5" />
                                        Dispatch
                                    </button>
                                    <button 
                                        v-else
                                        disabled
                                        class="inline-flex items-center gap-1.5 rounded-xl bg-slate-100 dark:bg-slate-800/40 px-3 py-2 text-xs font-bold text-slate-400 dark:text-slate-500 cursor-not-allowed border border-slate-200 dark:border-slate-800"
                                    >
                                        <ArrowUpTrayIcon class="h-3.5 w-3.5" />
                                        Dispatch
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Support -->
            <div v-if="orders.last_page > 1" class="border-t border-slate-200 dark:border-slate-800 px-6 py-4 flex items-center justify-between">
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Showing {{ orders.from }} to {{ orders.to }} of {{ orders.total }} orders
                </p>
                <div class="flex items-center gap-2">
                    <Link
                        v-for="link in orders.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        class="px-3 py-1.5 rounded-lg text-sm transition-colors"
                        :class="link.active 
                            ? 'bg-blue-600 text-slate-900 dark:text-white' 
                            : link.url 
                                ? 'text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 hover:text-slate-900 dark:text-white' 
                                : 'text-white cursor-not-allowed'"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>

        <!-- Subcontract Orders Operations Guide -->
        <div class="mt-8 relative hidden md:block">
            <div class="absolute inset-0 flex items-center" aria-hidden="true">
                <div class="w-full border-t border-slate-200/60 dark:border-slate-700/60"></div>
            </div>
            <div class="relative flex justify-center">
                <span class="bg-[#F8FAFC] dark:bg-[#0F172A] px-4 text-[10px] font-black tracking-[0.2em] text-slate-400 dark:text-slate-500 uppercase">
                    Subcontract Operations Guide
                </span>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4 hidden md:grid mb-8">
            <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                <div class="flex items-center gap-3 mb-3">
                    <div class="p-2.5 rounded-xl bg-amber-500/10 text-amber-500">
                        <TruckIcon class="h-5 w-5" />
                    </div>
                    <h4 class="font-bold text-slate-200 text-sm">External Tracking</h4>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    Monitor production timelines shifted to external vendors. These orders correlate directly with internal <strong>Work Orders</strong> mapped as Subcontracts.
                </p>
            </div>

            <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                <div class="flex items-center gap-3 mb-3">
                    <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-400">
                        <CubeIcon class="h-5 w-5" />
                    </div>
                    <h4 class="font-bold text-slate-200 text-sm">Yield Management</h4>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    Compare the initial <strong>Qty Order</strong> against what the vendor actually <strong>Recv</strong> (Delivered). The <strong>Balance</strong> tells you pending outputs.
                </p>
            </div>

            <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                <div class="flex items-center gap-3 mb-3">
                    <div class="p-2.5 rounded-xl bg-blue-500/10 text-blue-400">
                        <ClipboardDocumentListIcon class="h-5 w-5" />
                    </div>
                    <h4 class="font-bold text-slate-200 text-sm">Lifecycle States</h4>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    Orders traverse from <strong>Draft</strong> &rarr; <strong>Sent</strong> &rarr; <strong>Received</strong> &rarr; <strong>Completed</strong> as physical goods are audited and paid.
                </p>
            </div>
            
            <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                <div class="flex items-center gap-3 mb-3">
                    <div class="p-2.5 rounded-xl bg-cyan-500/10 text-cyan-400">
                        <EyeIcon class="h-5 w-5" />
                    </div>
                    <h4 class="font-bold text-slate-200 text-sm">Deep Audit</h4>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    Click <strong>Order Number</strong> to review raw material consumption sent to the vendor and cross-check incoming financial invoices from them.
                </p>
            </div>
        </div>

        <!-- Dispatch Modal -->
        <div v-if="showDispatchModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-sm">
            <div class="glass-card rounded-3xl w-full max-w-2xl p-6 shadow-2xl">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-3">
                        <ArrowUpTrayIcon class="h-6 w-6 text-blue-400" />
                        Dispatch Materials
                    </h3>
                    <button @click="showDispatchModal = false" class="p-2 text-slate-500 hover:text-slate-900 dark:text-white">
                        <XMarkIcon class="h-6 w-6" />
                    </button>
                </div>

                <div class="bg-blue-500/5 border border-blue-500/10 rounded-2xl p-4 mb-6">
                    <p class="text-xs text-blue-400 font-medium">
                        Enter the quantity of each material you are currently sending to the subcontractor. 
                        Stock will be reduced for the specified quantities.
                    </p>
                </div>
                
                <form @submit.prevent="submitDispatch" class="space-y-6">
                    <div class="max-h-[40vh] overflow-y-auto space-y-4 pr-2">
                        <div v-for="item in dispatchForm.items" :key="item.id" class="p-4 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 rounded-2xl border border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center gap-4">
                            <div class="flex-1">
                                <div class="text-sm font-bold text-slate-900 dark:text-white">{{ item.name }}</div>
                                <div class="text-[10px] text-slate-500 font-mono">{{ item.sku }}</div>
                                <div class="mt-1 flex gap-3 text-[10px] font-bold uppercase tracking-wider">
                                    <span class="text-slate-500">Required: {{ formatNumber(item.qty_required) }}</span>
                                    <span class="text-emerald-500">Sent: {{ formatNumber(item.qty_consumed) }}</span>
                                </div>
                            </div>
                            <div class="w-32">
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Qty to send</label>
                                <input 
                                    v-model="item.qty"
                                    type="number"
                                    step="0.01"
                                    class="w-full bg-white dark:bg-slate-950 border-slate-200 dark:border-slate-700 rounded-xl py-2 px-3 text-blue-400 font-mono font-bold focus:ring-blue-500"
                                    placeholder="0"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 flex gap-3">
                        <button 
                            type="button"
                            @click="showDispatchModal = false"
                            class="flex-1 py-3 font-bold text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-800 rounded-xl hover:bg-slate-700"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit"
                            :disabled="dispatchForm.processing"
                            class="flex-1 py-3 font-bold text-slate-900 dark:text-white bg-blue-600 rounded-xl hover:bg-blue-500 shadow-lg shadow-blue-500/20 disabled:opacity-50"
                        >
                            Confirm Dispatch
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>



