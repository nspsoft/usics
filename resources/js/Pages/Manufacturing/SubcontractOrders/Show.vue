<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatNumber, formatCurrency } from '@/helpers';
import { 
    ArrowLeftIcon, 
    TruckIcon,
    CubeIcon,
    UserIcon,
    ClipboardDocumentCheckIcon,
    ArrowPathIcon,
    CheckCircleIcon,
    BanknotesIcon,
    ClockIcon,
    DocumentTextIcon,
    ArrowUpTrayIcon,
    ArrowDownTrayIcon,
    PrinterIcon,
    XMarkIcon,
    ArrowUturnLeftIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    order: Object,
    stockMovements: Array,
    subcontractWarehouse: Object,
    subcontractStocks: Array,
});

const showDispatchModal = ref(false);
const dispatchForm = useForm({
    items: props.order.work_order?.components.map(c => ({
        id: c.id,
        name: c.product?.name,
        sku: c.product?.sku,
        qty_required: parseFloat(c.qty_required),
        qty_consumed: parseFloat(c.qty_consumed),
        qty: Math.max(0, parseFloat(c.qty_required) - parseFloat(c.qty_consumed)),
    })) || [],
});

const showReturnModal = ref(false);
const returnForm = useForm({
    items: [],
});

const openReturnModal = () => {
    // Calculate theoretical balances for return
    returnForm.items = props.order.work_order?.components.map(c => {
        const usagePerUnit = (parseFloat(c.qty_required) / parseFloat(props.order.work_order?.qty_planned || 1));
        const theoreticalUsed = usagePerUnit * parseFloat(props.order.work_order?.qty_produced || 0);
        const estimatedBalance = Math.max(0, parseFloat(c.qty_consumed) - theoreticalUsed);
        
        return {
            id: c.id,
            name: c.product?.name,
            sku: c.product?.sku,
            qty_consumed: parseFloat(c.qty_consumed),
            theoretical_used: theoreticalUsed,
            estimated_balance: estimatedBalance,
            qty: 0,
        };
    }) || [];
    showReturnModal.value = true;
};

const submitReturn = () => {
    returnForm.post(route('manufacturing.subcontract-orders.return-materials', props.order.id), {
        onSuccess: () => {
            showReturnModal.value = false;
        },
    });
};

const openDispatchModal = () => {
    // Refresh items in case props changed
    dispatchForm.items = props.order.work_order?.components.map(c => ({
        id: c.id,
        name: c.product?.name,
        sku: c.product?.sku,
        qty_required: parseFloat(c.qty_required),
        qty_consumed: parseFloat(c.qty_consumed),
        qty: Math.max(0, parseFloat(c.qty_required) - parseFloat(c.qty_consumed)),
    }));
    showDispatchModal.value = true;
};

const submitDispatch = () => {
    dispatchForm.post(route('manufacturing.subcontract-orders.dispatch', props.order.id), {
        onSuccess: () => {
            showDispatchModal.value = false;
        },
    });
};

const showReceiveModal = ref(false);
const receiveForm = useForm({
    qty_received: props.order.work_order?.qty_planned - props.order.work_order?.qty_produced,
    sj_number: '',
    notes: '',
});

const submitReceive = () => {
    receiveForm.post(route('manufacturing.subcontract-orders.receive', props.order.id), {
        onSuccess: () => {
            showReceiveModal.value = false;
            receiveForm.reset();
        },
    });
};

const getStatusBadge = (status) => {
    const badges = {
        draft: 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30',
        sent: 'bg-blue-500/20 text-blue-400 border-blue-500/30 font-bold',
        received: 'bg-amber-500/20 text-amber-400 border-amber-500/30 font-bold',
        completed: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30 font-bold',
        cancelled: 'bg-red-500/20 text-red-400 border-red-500/30 font-bold',
    };
    return badges[status] || 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30';
};


const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

const getMovementLabel = (mv) => {
    if (mv.type === 'transfer') {
        return mv.qty > 0 ? 'MAT_RECV (By Subcont)' : 'MAT_SENT (To Subcont)';
    }
    if (mv.type === 'production_in') return 'FG_IN (Receipt)';
    if (mv.type === 'production_out') return 'MAT_USE (Backflush)';
    
    return mv.qty > 0 ? 'IN' : 'OUT';
};

const getMovementColor = (mv) => {
    if (mv.type === 'transfer') {
        return 'bg-blue-500/10 text-blue-400';
    }
    return mv.qty > 0 ? 'bg-emerald-500/10 text-emerald-500' : 'bg-red-500/10 text-red-500';
};

const generatePo = () => {
    if (confirm('Create a Purchase Order Draft for this subcontract service?')) {
        router.post(route('manufacturing.subcontract-orders.generate-po', props.order.id));
    }
};

const canDispatch = computed(() => !['completed', 'cancelled'].includes(props.order.status));
const canReceive = computed(() => ['sent', 'received'].includes(props.order.status));
</script>

<template>
    <Head :title="`Subcontract Order ${order.order_number}`" />
    
    <AppLayout title="Subcontract Orders">
        <div class="max-w-full px-4 sm:px-6 lg:px-8 mx-auto">
            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center gap-4">
                    <Link 
                        :href="route('manufacturing.subcontract-orders.index')" 
                        class="p-2.5 rounded-xl glass-card text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:border-slate-200 dark:border-slate-700 transition-all"
                    >
                        <ArrowLeftIcon class="h-5 w-5" />
                    </Link>
                    <div>
                        <div class="flex items-center gap-3">
                            <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">{{ order.order_number }}</h2>
                            <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider" :class="getStatusBadge(order.status)">
                                {{ order.status }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1 uppercase tracking-widest font-bold">Subcontract Tracking Detail</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <a 
                        :href="route('manufacturing.subcontract-orders.print', order.id)"
                        target="_blank"
                        class="flex items-center gap-2 rounded-xl bg-white dark:bg-slate-950 px-4 py-2.5 text-sm font-semibold text-red-500 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-all border border-red-500/30"
                    >
                        <PrinterIcon class="h-5 w-5" />
                        Print Order
                    </a>
                    <button 
                        v-if="!order.purchase_order_id && order.status !== 'cancelled'"
                        @click="generatePo"
                        class="flex items-center gap-2 rounded-xl bg-slate-900 dark:bg-white px-4 py-2.5 text-sm font-semibold text-white dark:text-slate-900 hover:opacity-90 transition-all shadow-lg"
                    >
                        <BanknotesIcon class="h-5 w-5" />
                        Generate Service PO
                    </button>
                    <Link
                        v-if="order.purchase_order_id"
                        :href="route('purchasing.orders.show', order.purchase_order_id)"
                        class="flex items-center gap-2 rounded-xl bg-blue-500/10 px-4 py-2.5 text-sm font-semibold text-blue-500 border border-blue-500/20 hover:bg-blue-500 hover:text-white transition-all"
                    >
                        <BanknotesIcon class="h-5 w-5" />
                        View PO: {{ order.purchase_order?.po_number }}
                    </Link>
                    <a 
                        v-if="['sent', 'received', 'completed'].includes(order.status)"
                        :href="route('manufacturing.subcontract-orders.print-delivery-note', order.id)"
                        target="_blank"
                        class="flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-800 px-4 py-2.5 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-700 transition-all border border-slate-200 dark:border-slate-700"
                    >
                        <PrinterIcon class="h-5 w-5" />
                        Print Surat Jalan
                    </a>
                    <button 
                        v-if="canDispatch"
                        @click="openDispatchModal"
                        class="flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white dark:text-white hover:bg-blue-500 transition-all shadow-lg shadow-blue-500/20"
                    >
                        <ArrowUpTrayIcon class="h-5 w-5" />
                        Dispatch Materials (OUT)
                    </button>
                    <button 
                        v-if="canReceive"
                        @click="showReceiveModal = true"
                        class="flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-slate-900 dark:text-white hover:bg-emerald-500 transition-all shadow-lg shadow-emerald-500/20"
                    >
                        <ArrowDownTrayIcon class="h-5 w-5" />
                        Receive Product (IN)
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">
                <!-- Left Column -->
                <div class="xl:col-span-8 space-y-8">
                    <!-- Basic Info -->
                    <div class="glass-card rounded-3xl p-6 shadow-sm overflow-hidden relative">
                         <div class="absolute top-0 right-0 p-8 opacity-5">
                            <TruckIcon class="h-32 w-32 text-slate-500 dark:text-slate-400" />
                         </div>
                         <div class="relative grid grid-cols-1 sm:grid-cols-2 gap-8">
                            <div class="flex gap-4">
                                <div class="h-12 w-12 rounded-2xl bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-blue-400">
                                    <UserIcon class="h-6 w-6" />
                                </div>
                                <div>
                                    <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Subcontractor</div>
                                    <div class="text-lg font-bold text-slate-900 dark:text-white">{{ order.supplier?.name }}</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ order.supplier?.phone || 'No phone' }}</div>
                                </div>
                            </div>
                            <div class="flex gap-4">
                                <div class="h-12 w-12 rounded-2xl bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-amber-400">
                                    <CubeIcon class="h-6 w-6" />
                                </div>
                                <div>
                                    <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Finished Good</div>
                                    <div class="text-lg font-bold text-slate-900 dark:text-white">{{ order.work_order?.product?.name }}</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400 font-mono">{{ order.work_order?.product?.sku }}</div>
                                </div>
                            </div>
                         </div>
                    </div>

                    <!-- Components Section -->
                    <div class="glass-card rounded-3xl overflow-hidden shadow-sm">
                        <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2 font-mono">
                                <div class="h-6 w-1 bg-blue-500 rounded-full"></div>
                                MATERIALS_DISPATCH_PLAN
                            </h3>
                            <span class="text-[10px] items-center gap-1.5 text-slate-500 font-mono flex uppercase font-bold px-3 py-1 bg-slate-50 dark:bg-slate-800 rounded-lg">
                                 WO Ref: {{ order.work_order?.wo_number }}
                            </span>
                        </div>
                        <div class="overflow-x-auto max-h-[300px] overflow-y-auto custom-scrollbar relative">
                            <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800 text-sm">
                                <thead class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900 shadow-sm">
                                    <tr class="bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 text-left">
                                        <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Material</th>
                                        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-widest">Required</th>
                                        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-widest">Dispatched</th>
                                        <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-widest">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <tr v-for="comp in order.work_order?.components" :key="comp.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="text-slate-900 dark:text-white font-medium">{{ comp.product?.name }}</div>
                                            <div class="text-[10px] text-slate-500 font-mono mt-0.5">{{ comp.product?.sku }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-right text-slate-600 dark:text-slate-300 font-mono">{{ formatNumber(comp.qty_required) }}</td>
                                        <td class="px-6 py-4 text-right text-emerald-400 font-mono">{{ formatNumber(comp.qty_consumed) }}</td>
                                        <td class="px-6 py-4 text-center">
                                            <span 
                                                class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase"
                                                :class="comp.qty_consumed >= comp.qty_required ? 'bg-emerald-500/10 text-emerald-500' : 'bg-slate-50 dark:bg-slate-800 text-slate-500'"
                                            >
                                                {{ comp.qty_consumed >= comp.qty_required ? 'Sent' : 'Pending' }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Subcontract Stock On Hand -->
                    <div class="glass-card rounded-3xl overflow-hidden shadow-sm">
                        <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2 font-mono">
                                <div class="h-6 w-1 bg-emerald-500 rounded-full"></div>
                                SUBCONT_STOCK_ON_HAND
                            </h3>
                            <span class="text-[10px] items-center gap-1.5 text-slate-500 font-mono flex uppercase font-bold px-3 py-1 bg-slate-50 dark:bg-slate-800 rounded-lg">
                                 {{ subcontractWarehouse?.name || 'No Subcontract WH' }}
                            </span>
                        </div>
                        <div class="overflow-x-auto max-h-[300px] overflow-y-auto custom-scrollbar relative">
                            <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800 text-sm">
                                <thead class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900 shadow-sm">
                                    <tr class="bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 text-left">
                                        <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Material</th>
                                        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-widest">Net Sent</th>
                                        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-widest">On Hand</th>
                                        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-widest">Used</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <tr v-for="row in (subcontractStocks || [])" :key="row.product_id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="text-slate-900 dark:text-white font-medium">{{ row.product?.name }}</div>
                                            <div class="text-[10px] text-slate-500 font-mono mt-0.5">{{ row.product?.sku }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-right text-slate-600 dark:text-slate-300 font-mono">{{ formatNumber(row.qty_sent) }}</td>
                                        <td class="px-6 py-4 text-right text-emerald-400 font-mono font-bold">{{ formatNumber(row.qty_on_hand) }}</td>
                                        <td class="px-6 py-4 text-right text-amber-400 font-mono font-bold">{{ formatNumber(row.qty_sent - row.qty_on_hand) }}</td>
                                    </tr>
                                    <tr v-if="(subcontractStocks || []).length === 0">
                                        <td colspan="4" class="px-6 py-12 text-center text-slate-600 italic">Subcontract Warehouse belum diset atau belum ada pergerakan stok.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Movement Histroy -->
                    <div class="glass-card rounded-3xl overflow-hidden shadow-sm">
                        <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2 font-mono">
                                <div class="h-6 w-1 bg-amber-500 rounded-full"></div>
                                STOCK_MOVEMENT_LOGS
                            </h3>
                        </div>
                        <div class="overflow-x-auto max-h-[400px] overflow-y-auto custom-scrollbar relative">
                            <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800 text-sm">
                                <thead class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900 shadow-sm">
                                    <tr class="bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 text-left">
                                        <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Date / Ref</th>
                                        <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Ref / SJ</th>
                                        <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Item</th>
                                        <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-widest">Type</th>
                                        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-widest">Qty</th>
                                        <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-widest">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <tr v-for="mv in stockMovements" :key="mv.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="text-slate-900 dark:text-white">{{ formatDate(mv.created_at) }}</div>
                                            <div class="text-[10px] text-slate-500 font-mono mt-0.5">MOV-{{ mv.id }}</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-xs font-bold text-slate-600 dark:text-slate-300">{{ mv.external_reference || '-' }}</div>
                                            <div class="text-[10px] text-slate-500 uppercase font-bold">Subcont SJ</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-slate-600 dark:text-slate-300 font-medium text-xs">{{ mv.product?.name }}</div>
                                            <div class="text-[10px] text-slate-500 font-mono">{{ mv.product?.sku }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span 
                                                class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase"
                                                :class="getMovementColor(mv)"
                                            >
                                                {{ getMovementLabel(mv) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right font-mono font-bold" :class="mv.qty > 0 ? 'text-emerald-400' : 'text-red-400'">
                                            {{ mv.qty > 0 ? '+' : '' }}{{ formatNumber(mv.qty) }}
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <a 
                                                v-if="mv.qty > 0 && mv.type === 'production_in'"
                                                :href="route('manufacturing.subcontract-orders.print-grn', mv.id)"
                                                target="_blank"
                                                class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-500 hover:text-emerald-400 uppercase tracking-tighter"
                                            >
                                                <PrinterIcon class="h-3 w-3" />
                                                Print GRN
                                            </a>
                                            <a 
                                                v-if="mv.qty < 0 && mv.type === 'transfer'"
                                                :href="route('manufacturing.subcontract-orders.print-delivery-note', order.id)"
                                                target="_blank"
                                                class="inline-flex items-center gap-1 text-[10px] font-bold text-blue-500 hover:text-blue-400 uppercase tracking-tighter"
                                            >
                                                <PrinterIcon class="h-3 w-3" />
                                                Print SJ
                                            </a>
                                        </td>
                                    </tr>
                                    <tr v-if="stockMovements.length === 0">
                                        <td colspan="4" class="px-6 py-12 text-center text-slate-600 italic">No movements recorded for this order.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Material Reconciliation -->
                    <div class="glass-card rounded-3xl overflow-hidden shadow-sm">
                        <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2 font-mono">
                                <div class="h-6 w-1 bg-purple-500 rounded-full"></div>
                                MATERIAL_RECONCILIATION
                            </h3>
                            <button 
                                v-if="canReceive"
                                @click="openReturnModal"
                                class="text-[10px] flex items-center gap-1.5 font-bold uppercase px-3 py-1.5 bg-purple-500/10 text-purple-400 hover:bg-purple-500 hover:text-slate-900 dark:text-white rounded-lg transition-all border border-purple-500/20"
                            >
                                <ArrowUturnLeftIcon class="h-3.5 w-3.5" />
                                Return Material
                            </button>
                        </div>
                        <div class="overflow-x-auto max-h-[300px] overflow-y-auto custom-scrollbar relative">
                            <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800 text-sm">
                                <thead class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900 shadow-sm">
                                    <tr class="bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 text-left">
                                        <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Material</th>
                                        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-widest">Total Sent</th>
                                        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-widest">Theo. Usage</th>
                                        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-widest">Data Est. Bal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <tr v-for="comp in order.work_order?.components" :key="comp.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="text-slate-900 dark:text-white font-medium">{{ comp.product?.name }}</div>
                                            <div class="text-[10px] text-slate-500 font-mono mt-0.5">{{ comp.product?.sku }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-right text-emerald-400 font-mono">{{ formatNumber(comp.qty_consumed) }}</td>
                                        <td class="px-6 py-4 text-right text-slate-600 dark:text-slate-300 font-mono">
                                            {{ formatNumber((parseFloat(comp.qty_required) / parseFloat(order.work_order?.qty_planned || 1)) * parseFloat(order.work_order?.qty_produced || 0)) }}
                                        </td>
                                        <td class="px-6 py-4 text-right text-purple-400 font-bold font-mono">
                                            {{ formatNumber(Math.max(0, parseFloat(comp.qty_consumed) - ((parseFloat(comp.qty_required) / parseFloat(order.work_order?.qty_planned || 1)) * parseFloat(order.work_order?.qty_produced || 0)))) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="xl:col-span-4 space-y-8">
                    <!-- Progress Card -->
                    <div class="glass-card rounded-3xl p-6 shadow-sm">
                        <h3 class="text-sm font-bold text-slate-500 uppercase tracking-widest mb-6 border-b border-slate-200 dark:border-slate-800 pb-4">Job Progress</h3>
                        <div class="flex items-end justify-between mb-4">
                            <div>
                                <div class="text-3xl font-bold text-slate-900 dark:text-white font-mono">{{ formatNumber(order.work_order?.qty_produced) }}</div>
                                <div class="text-[10px] text-slate-500 uppercase font-bold mt-1">Total Received</div>
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-bold text-slate-500 dark:text-slate-400 font-mono">/ {{ formatNumber(order.work_order?.qty_planned) }}</div>
                                <div class="text-[10px] text-slate-500 uppercase font-bold mt-1">Target</div>
                            </div>
                        </div>
                        <div class="h-2 bg-slate-50 dark:bg-slate-800 rounded-full overflow-hidden">
                            <div 
                                class="h-full bg-gradient-to-r from-emerald-500 to-emerald-400 transition-all duration-500"
                                :style="{ width: `${(order.work_order?.qty_produced / order.work_order?.qty_planned) * 100}%` }"
                            ></div>
                        </div>
                    </div>

                    <!-- Warehouse & Notes -->
                    <div class="glass-card rounded-3xl p-6 shadow-sm">
                        <h3 class="text-sm font-bold text-slate-500 uppercase tracking-widest mb-6 border-b border-slate-200 dark:border-slate-800 pb-4">Warehouse Details</h3>
                        <div class="space-y-4">
                            <div class="flex items-center gap-3">
                                <BanknotesIcon class="h-5 w-5 text-slate-500 dark:text-slate-400" />
                                <div>
                                    <div class="text-[10px] text-slate-500 uppercase font-bold">Main Inventory</div>
                                    <div class="text-xs text-slate-900 dark:text-white">{{ order.work_order?.warehouse?.name }}</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <ClipboardDocumentCheckIcon class="h-5 w-5 text-slate-500 dark:text-slate-400" />
                                <div>
                                    <div class="text-[10px] text-slate-500 uppercase font-bold">Material Warehouse</div>
                                    <div class="text-xs text-slate-900 dark:text-white">{{ order.work_order?.material_warehouse?.name || '-' }}</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <TruckIcon class="h-5 w-5 text-slate-500 dark:text-slate-400" />
                                <div>
                                    <div class="text-[10px] text-slate-500 uppercase font-bold">Subcontract Warehouse</div>
                                    <div class="text-xs text-slate-900 dark:text-white">{{ subcontractWarehouse?.name || '-' }}</div>
                                </div>
                            </div>
                         </div>
                    </div>

                    <div v-if="order.notes" class="glass-card rounded-3xl p-6 shadow-sm">
                        <h3 class="text-sm font-bold text-slate-500 uppercase tracking-widest mb-4 border-b border-slate-200 dark:border-slate-800 pb-4">Order Notes</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 italic">"{{ order.notes }}"</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dispatch Modal -->
        <div v-if="showDispatchModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-white dark:bg-slate-950/80 backdrop-blur-sm">
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

        <!-- Receive Modal -->
        <div v-if="showReceiveModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-white dark:bg-slate-950/80 backdrop-blur-sm">
            <div class="glass-card rounded-3xl w-full max-w-4xl p-6 shadow-2xl">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-3">
                        <ArrowDownTrayIcon class="h-6 w-6 text-emerald-400" />
                        Receive Products
                    </h3>
                    <button @click="showReceiveModal = false" class="p-2 text-slate-500 hover:text-slate-900 dark:text-white">
                        <ArrowPathIcon class="h-5 w-5" />
                    </button>
                </div>
                
                <form @submit.prevent="submitReceive" class="space-y-6">
                    <div class="bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                        <div class="grid grid-cols-12 gap-4 p-4 bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">
                            <div class="col-span-4">Item Details</div>
                            <div class="col-span-2 text-right">Target</div>
                            <div class="col-span-2 text-right">In (Recv)</div>
                            <div class="col-span-2 text-right">Remaining</div>
                            <div class="col-span-2 text-right">Receive Now</div>
                        </div>
                        <div class="p-4 grid grid-cols-12 gap-4 items-center">
                            <div class="col-span-4">
                                <div class="font-bold text-slate-900 dark:text-white text-sm">{{ order.work_order?.product?.name }}</div>
                                <div class="text-[10px] text-slate-500 font-mono">{{ order.work_order?.product?.sku }}</div>
                            </div>
                            <div class="col-span-2 text-right text-slate-500 dark:text-slate-400 font-mono">
                                {{ formatNumber(order.work_order?.qty_planned) }}
                            </div>
                            <div class="col-span-2 text-right text-emerald-500 font-mono">
                                {{ formatNumber(order.work_order?.qty_produced) }}
                            </div>
                            <div class="col-span-2 text-right text-amber-500 font-mono">
                                {{ formatNumber(Math.max(0, order.work_order?.qty_planned - order.work_order?.qty_produced)) }}
                            </div>
                            <div class="col-span-2">
                                <input 
                                    v-model="receiveForm.qty_received"
                                    type="number"
                                    step="0.01"
                                    class="w-full bg-white dark:bg-slate-950 border-slate-200 dark:border-slate-700 rounded-xl py-2 px-3 text-emerald-400 font-mono font-bold text-right focus:ring-emerald-500"
                                    required
                                />
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Nomor SJ Subcont</label>
                        <input 
                            v-model="receiveForm.sj_number"
                            type="text"
                            class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl py-3 px-4 text-slate-900 dark:text-white text-sm focus:ring-blue-500"
                            placeholder="e.g. SJ/2026/001"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Notes</label>
                        <textarea 
                            v-model="receiveForm.notes"
                            class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl py-3 px-4 text-slate-900 dark:text-white text-sm focus:ring-blue-500"
                            rows="3"
                            placeholder="Add receipt notes..."
                        ></textarea>
                    </div>

                    <div class="pt-4 flex gap-3">
                        <button 
                            type="button"
                            @click="showReceiveModal = false"
                            class="flex-1 py-3 font-bold text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-800 rounded-xl hover:bg-slate-700"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit"
                            :disabled="receiveForm.processing"
                            class="flex-1 py-3 font-bold text-slate-900 dark:text-white bg-emerald-600 rounded-xl hover:bg-emerald-500 shadow-lg shadow-emerald-500/20 disabled:opacity-50"
                        >
                            Confirm Receipt
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Return Modal -->
        <div v-if="showReturnModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-white dark:bg-slate-950/80 backdrop-blur-sm">
            <div class="glass-card rounded-3xl w-full max-w-3xl p-6 shadow-2xl">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-3">
                        <ArrowUturnLeftIcon class="h-6 w-6 text-purple-400" />
                        Return Unused Material
                    </h3>
                    <button @click="showReturnModal = false" class="p-2 text-slate-500 hover:text-slate-900 dark:text-white">
                        <XMarkIcon class="h-6 w-6" />
                    </button>
                </div>

                <div class="bg-purple-500/5 border border-purple-500/10 rounded-2xl p-4 mb-6">
                    <p class="text-xs text-purple-400 font-medium">
                        Enter the quantity of material to return from the subcontractor to the main inventory.
                        This will reduce the "Consumed/Sent" quantity and increase your stock.
                    </p>
                </div>
                
                <form @submit.prevent="submitReturn" class="space-y-6">
                    <div class="max-h-[40vh] overflow-y-auto space-y-4 pr-2">
                        <div v-for="item in returnForm.items" :key="item.id" class="p-4 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 rounded-2xl border border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center gap-4">
                            <div class="flex-1">
                                <div class="text-sm font-bold text-slate-900 dark:text-white">{{ item.name }}</div>
                                <div class="text-[10px] text-slate-500 font-mono">{{ item.sku }}</div>
                                <div class="mt-1 flex gap-3 text-[10px] font-bold uppercase tracking-wider">
                                    <span class="text-slate-500">Sent: {{ formatNumber(item.qty_consumed) }}</span>
                                    <span class="text-emerald-500">Theo. Used: {{ formatNumber(item.theoretical_used) }}</span>
                                    <span class="text-purple-500">Est. Bal: {{ formatNumber(item.estimated_balance) }}</span>
                                </div>
                            </div>
                            <div class="w-32">
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Qty to Return</label>
                                <input 
                                    v-model="item.qty"
                                    type="number"
                                    step="0.01"
                                    class="w-full bg-white dark:bg-slate-950 border-slate-200 dark:border-slate-700 rounded-xl py-2 px-3 text-purple-400 font-mono font-bold focus:ring-purple-500"
                                    placeholder="0"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 flex gap-3">
                        <button 
                            type="button"
                            @click="showReturnModal = false"
                            class="flex-1 py-3 font-bold text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-800 rounded-xl hover:bg-slate-700"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit"
                            :disabled="returnForm.processing"
                            class="flex-1 py-3 font-bold text-slate-900 dark:text-white bg-purple-600 rounded-xl hover:bg-purple-500 shadow-lg shadow-purple-500/20 disabled:opacity-50"
                        >
                            Confirm Return
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>



