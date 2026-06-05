<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    PlusIcon,
    MagnifyingGlassIcon,
    PencilSquareIcon,
    TrashIcon,
    EyeIcon,
    FunnelIcon,
    DocumentTextIcon,
    CheckBadgeIcon,
    TruckIcon,
    CurrencyDollarIcon,
    ShieldCheckIcon,
    ChevronUpIcon,
    ChevronDownIcon,
    ArrowUpTrayIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
} from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';
import Pagination from '@/Components/Pagination.vue';
import { ShoppingCartIcon, ClockIcon, ArrowDownTrayIcon, SparklesIcon } from '@heroicons/vue/24/outline';
import { formatNumber, formatCurrency, formatDate } from '@/helpers';
import POImportModal from './POImportModal.vue';

const showImportModal = ref(false);
const importForm = useForm({ 
    file: null,
    overwrite: false,
});
const includeData = ref(false);
const fileInput = ref(null);

const handleFileChange = (e) => {
    importForm.file = e.target.files[0];
};

const submitImport = () => {
    importForm.post('/sales/orders-import', {
        onSuccess: () => {
            showImportModal.value = false;
            importForm.reset();
            if (fileInput.value) fileInput.value.value = '';
        },
        forceFormData: true,
    });
};

const props = defineProps({
    salesOrders: Object,
    stats: Object,
    customers: Array,
    filters: Object,
    statuses: Array,
});

const search = ref(props.filters.search || '');
const soNumberFilter = ref(props.filters.so_number || '');
const poNumber = ref(props.filters.po_number || '');
const selectedStatus = ref(props.filters.status || '');
const selectedCustomer = ref(props.filters.customer || '');
const sortField = ref(props.filters.sort || 'created_at');
const sortDirection = ref(props.filters.direction || 'desc');
const showFilters = ref(false);
const selectedIds = ref([]);
const bulkConfirming = ref(false);
const flashMessage = ref(null);
const flashType = ref('success');

const page = usePage();

watch(() => page.props.flash, (flash) => {
    if (flash?.success) {
        flashMessage.value = flash.success;
        flashType.value = 'success';
        setTimeout(() => flashMessage.value = null, 8000);
    } else if (flash?.error) {
        flashMessage.value = flash.error;
        flashType.value = 'error';
        setTimeout(() => flashMessage.value = null, 8000);
    }
}, { deep: true, immediate: true });

const draftOrders = computed(() => props.salesOrders.data.filter(so => so.status === 'draft'));
const allDraftsSelected = computed(() => draftOrders.value.length > 0 && draftOrders.value.every(so => selectedIds.value.includes(so.id)));

const toggleAll = () => {
    if (allDraftsSelected.value) {
        selectedIds.value = [];
    } else {
        selectedIds.value = draftOrders.value.map(so => so.id);
    }
};

const toggleOne = (id) => {
    const idx = selectedIds.value.indexOf(id);
    if (idx > -1) {
        selectedIds.value.splice(idx, 1);
    } else {
        selectedIds.value.push(id);
    }
};

const bulkConfirm = () => {
    if (selectedIds.value.length === 0) return;
    if (!confirm(`Apakah Anda yakin ingin meng-confirm ${selectedIds.value.length} Sales Order?`)) return;
    
    bulkConfirming.value = true;
    router.post('/sales/orders/bulk-confirm', {
        ids: selectedIds.value,
    }, {
        onSuccess: () => {
            selectedIds.value = [];
            bulkConfirming.value = false;
        },
        onError: () => {
            bulkConfirming.value = false;
        },
    });
};

const applyFilters = debounce(() => {
    router.get('/sales/orders', {
        search: search.value || undefined,
        so_number: soNumberFilter.value || undefined,
        po_number: poNumber.value || undefined,
        status: selectedStatus.value || undefined,
        customer: selectedCustomer.value || undefined,
        sort: sortField.value,
        direction: sortDirection.value,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

const sort = (field) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }
    applyFilters();
};

watch([search, soNumberFilter, poNumber, selectedStatus, selectedCustomer], applyFilters);

const clearFilters = () => {
    search.value = '';
    soNumberFilter.value = '';
    poNumber.value = '';
    selectedStatus.value = '';
    selectedCustomer.value = '';
};

const deleteSO = (so) => {
    if (confirm(`Are you sure you want to delete "${so.so_number}"?`)) {
        router.delete(`/sales/orders/${so.id}`);
    }
};

const getStatusBadge = (status) => {
    const badges = {
        draft: 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30',
        waiting_po: 'bg-orange-500/20 text-orange-500 dark:text-orange-400 border-orange-500/30',
        confirmed: 'bg-blue-500/20 text-blue-400 border-blue-500/30',
        processing: 'bg-amber-500/20 text-amber-400 border-amber-500/30',
        shipped: 'bg-purple-500/20 text-purple-400 border-purple-500/30',
        delivered: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
        cancelled: 'bg-red-500/20 text-red-400 border-red-500/30',
    };
    return badges[status] || 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30';
};

const getStatusLabel = (status) => {
    const labels = {
        draft: 'Draft',
        waiting_po: 'Waiting PO',
        confirmed: 'Confirmed',
        processing: 'Processing',
        shipped: 'Shipped',
        delivered: 'Delivered',
        cancelled: 'Cancelled',
    };
    return labels[status] || status;
};

const getStatusDescription = (status) => {
    const descriptions = {
        draft: 'Pesanan baru dibuat, belum dikonfirmasi.',
        waiting_po: 'Menunggu Purchase Order resmi dari customer.',
        confirmed: 'Pesanan disetujui, siap diproses.',
        processing: 'Sedang disiapkan di gudang.',
        shipped: 'Barang dalam pengiriman.',
        delivered: 'Pesanan sudah sampai.',
        cancelled: 'Pesanan dibatalkan.',
    };
    return descriptions[status] || '';
};


const calculatePercentage = (value, total) => {
    const v = parseFloat(value || 0);
    const t = parseFloat(total || 1);
    if (t <= 0) return 0;
    return Math.round((v / t) * 100);
};

const calculateWidth = (value, total) => {
    const pct = calculatePercentage(value, total);
    return Math.min(pct, 100) + '%';
};
</script>

<template>
    <Head title="Sales Orders" />
    
    <AppLayout title="Sales Orders">
        <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 mb-6">
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative w-full sm:w-64">
                    <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500" />
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search SO or PO number..."
                        class="block w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 py-2 pl-10 pr-4 text-sm text-slate-900 dark:text-white placeholder:text-slate-500 focus:bg-white dark:focus:bg-slate-800 focus:ring-2 focus:ring-blue-500/50 transition-all shadow-sm"
                    />
                </div>
                <button 
                    @click="showFilters = !showFilters"
                    class="flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors border border-transparent"
                    :class="{ 'ring-2 ring-blue-500/50 border-blue-500/50': showFilters }"
                >
                    <FunnelIcon class="h-5 w-5" />
                    Filters
                </button>

                <!-- Mini Statistics Row (Sales Orders) -->
                <div v-if="stats" class="flex flex-wrap items-center gap-2">
                    <div class="glass-card px-3 py-2 rounded-xl border-l-4 border-l-blue-500 flex items-center gap-3 shadow-sm">
                        <ShoppingCartIcon class="w-4 h-4 text-blue-500 shrink-0" />
                        <div class="flex items-baseline gap-2">
                            <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Ordered</span>
                            <span class="text-sm font-black text-slate-900 dark:text-white">{{ formatNumber(stats.total_qty || 0) }}</span>
                        </div>
                    </div>
                    <div class="glass-card px-3 py-2 rounded-xl border-l-4 border-l-emerald-500 flex items-center gap-3 shadow-sm">
                        <CheckBadgeIcon class="w-4 h-4 text-emerald-500 shrink-0" />
                        <div class="flex items-baseline gap-2">
                            <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Sent</span>
                            <span class="text-sm font-black text-slate-900 dark:text-white">{{ formatNumber(stats.total_delivered || 0) }}</span>
                        </div>
                    </div>
                    <div class="glass-card px-3 py-2 rounded-xl border-l-4 border-l-red-500 flex items-center gap-3 shadow-sm">
                        <ArrowDownTrayIcon class="w-4 h-4 text-red-500 rotate-180 shrink-0" />
                        <div class="flex items-baseline gap-2">
                            <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Return</span>
                            <span class="text-sm font-black text-slate-900 dark:text-white">{{ formatNumber(stats.total_returned || 0) }}</span>
                        </div>
                    </div>
                    <div class="glass-card px-3 py-2 rounded-xl border-l-4 border-l-amber-500 flex items-center gap-3 shadow-sm">
                        <ClockIcon class="w-4 h-4 text-amber-500 shrink-0" />
                        <div class="flex items-baseline gap-2">
                            <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Balance</span>
                            <span class="text-sm font-black text-slate-900 dark:text-white">{{ formatNumber(stats.total_balance || 0) }}</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center gap-2 shrink-0">
                <a
                    href="/sales/orders-export"
                    class="hidden md:inline-flex items-center gap-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-4 py-2 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-700 transition-all group"
                >
                    <ArrowDownTrayIcon class="h-5 w-5 text-emerald-500 group-hover:scale-110 transition-transform" />
                    Export
                </a>
                <button
                    @click="showImportModal = true"
                    class="hidden md:inline-flex items-center gap-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-4 py-2 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-700 transition-all group"
                >
                    <ArrowUpTrayIcon class="h-5 w-5 text-blue-500 group-hover:scale-110 transition-transform" />
                    Import
                </button>
                <Link
                    href="/sales/po-extractor"
                    class="hidden md:inline-flex items-center gap-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-4 py-2 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-700 transition-all group"
                >
                    <SparklesIcon class="h-5 w-5 text-amber-500 group-hover:scale-110 transition-transform" />
                    Import PO (AI)
                </Link>
                <Link
                    href="/sales/orders/create"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 hover:from-blue-500 hover:to-blue-400 transition-all"
                >
                    <PlusIcon class="h-5 w-5" />
                    Create SO
                </Link>
            </div>
        </div>

        <!-- AI Import now redirects to full page at /sales/po-extractor -->

        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-2"
        >
            <div v-if="showFilters" class="mb-6 rounded-2xl glass-card p-4">
                <div class="grid grid-cols-1 sm:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Status</label>
                        <select
                            v-model="selectedStatus"
                            class="block w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 focus:bg-white dark:focus:bg-slate-800"
                        >
                            <option value="">All Status</option>
                            <option v-for="status in statuses" :key="status.value" :value="status.value">
                                {{ status.label }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Customer</label>
                        <select
                            v-model="selectedCustomer"
                            class="block w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 focus:bg-white dark:focus:bg-slate-800"
                        >
                            <option value="">All Customers</option>
                            <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                                {{ customer.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">SO Number</label>
                        <input
                            v-model="soNumberFilter"
                            type="text"
                            placeholder="Search SO..."
                            class="block w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 py-2.5 px-4 text-sm text-slate-900 dark:text-white placeholder:text-slate-500 focus:bg-white dark:focus:bg-slate-800 focus:ring-2 focus:ring-blue-500/50 transition-all shadow-sm"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">PO Number</label>
                        <input
                            v-model="poNumber"
                            type="text"
                            placeholder="Search PO..."
                            class="block w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 py-2.5 px-4 text-sm text-slate-900 dark:text-white placeholder:text-slate-500 focus:bg-white dark:focus:bg-slate-800 focus:ring-2 focus:ring-blue-500/50 transition-all shadow-sm"
                        />
                    </div>
                    <div class="flex items-end">
                        <button 
                            @click="clearFilters"
                            class="w-full rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-4 py-2.5 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors"
                        >
                            Clear Filters
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Flash Message -->
        <Transition enter-active-class="transition ease-out duration-300" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition ease-in duration-200" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-2">
            <div v-if="flashMessage" class="mb-4 p-4 rounded-xl flex items-center justify-between gap-3 shadow-md" :class="flashType === 'success' ? 'bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 dark:text-emerald-400' : 'bg-red-500/10 border border-red-500/20 text-red-700 dark:text-red-400'">
                <div class="flex items-center gap-3">
                    <CheckCircleIcon v-if="flashType === 'success'" class="h-5 w-5 shrink-0" />
                    <ExclamationTriangleIcon v-else class="h-5 w-5 shrink-0" />
                    <span class="text-sm font-medium">{{ flashMessage }}</span>
                </div>
                <button @click="flashMessage = null" class="p-1 rounded-lg hover:bg-black/5 dark:hover:bg-white/5 transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
        </Transition>

        <!-- Bulk Action Bar -->
        <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-2">
            <div v-if="selectedIds.length > 0" class="mb-4 p-3 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-between gap-3">
                <span class="text-sm font-medium text-blue-700 dark:text-blue-400">
                    {{ selectedIds.length }} order dipilih
                </span>
                <div class="flex items-center gap-2">
                    <button @click="selectedIds = []" class="px-3 py-1.5 rounded-lg text-xs font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        Batal Pilih
                    </button>
                    <button 
                        @click="bulkConfirm" 
                        :disabled="bulkConfirming"
                        class="inline-flex items-center gap-2 px-4 py-1.5 rounded-lg bg-blue-600 text-white text-xs font-bold hover:bg-blue-500 disabled:opacity-50 transition-all shadow-sm"
                    >
                        <CheckBadgeIcon class="h-4 w-4" />
                        {{ bulkConfirming ? 'Confirming...' : 'Confirm All' }}
                    </button>
                </div>
            </div>
        </Transition>

        <div class="rounded-2xl glass-card overflow-hidden">
            <div class="overflow-x-auto overflow-y-auto max-h-[600px]">
                <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700">
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-3 py-3 text-center shadow-sm w-10">
                                <input type="checkbox" :checked="allDraftsSelected" @change="toggleAll" class="w-4 h-4 rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500/50 cursor-pointer" title="Select all draft orders" />
                            </th>
                            <th @click="sort('so_number')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group">
                                <div class="flex items-center gap-1">
                                    SO Number
                                    <span v-if="sortField === 'so_number'" class="text-blue-600 dark:text-blue-400">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th @click="sort('customer_name')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group">
                                <div class="flex items-center gap-1">
                                    Customer
                                    <span v-if="sortField === 'customer_name'" class="text-blue-600 dark:text-blue-400">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th @click="sort('customer_po_number')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group">
                                <div class="flex items-center gap-1">
                                    No PO
                                    <span v-if="sortField === 'customer_po_number'" class="text-blue-600 dark:text-blue-400">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th @click="sort('order_date')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group">
                                <div class="flex items-center gap-1">
                                    Order Date
                                    <span v-if="sortField === 'order_date'" class="text-blue-600 dark:text-blue-400">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th @click="sort('delivery_date')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group">
                                <div class="flex items-center gap-1">
                                    Delivery Date
                                    <span v-if="sortField === 'delivery_date'" class="text-blue-600 dark:text-blue-400">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th @click="sort('items_count')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group">
                                <div class="flex items-center justify-center gap-1">
                                    Items
                                    <span v-if="sortField === 'items_count'" class="text-blue-600 dark:text-blue-400">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th @click="sort('total_qty_ordered')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group">
                                <div class="flex items-center justify-center gap-1">
                                    Qty
                                    <span v-if="sortField === 'total_qty_ordered'" class="text-blue-600 dark:text-blue-400">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th @click="sort('total_qty_delivered')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group">
                                <div class="flex items-center justify-center gap-1">
                                    Delivery
                                    <span v-if="sortField === 'total_qty_delivered'" class="text-blue-600 dark:text-blue-400">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th @click="sort('total_qty_invoiced')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group">
                                <div class="flex items-center justify-center gap-1">
                                    Inv (Qty)
                                    <span v-if="sortField === 'total_qty_invoiced'" class="text-blue-600 dark:text-blue-400">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>

                            <th @click="sort('total_qty_returned')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group">
                                <div class="flex items-center justify-center gap-1">
                                    Return
                                    <span v-if="sortField === 'total_qty_returned'" class="text-blue-600 dark:text-blue-400">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">Balance</th>
                            <th @click="sort('total')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group">
                                <div class="flex items-center justify-end gap-1">
                                    Total
                                    <span v-if="sortField === 'total'" class="text-blue-600 dark:text-blue-400">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th @click="sort('total_amount_invoiced')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group">
                                <div class="flex items-center justify-center gap-1">
                                    Inv (Rp)
                                    <span v-if="sortField === 'total_amount_invoiced'" class="text-blue-600 dark:text-blue-400">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th @click="sort('percent_invoiced')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group">
                                <div class="flex items-center justify-center gap-1">
                                    % Inv
                                    <span v-if="sortField === 'percent_invoiced'" class="text-blue-600 dark:text-blue-400">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th @click="sort('status')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group">
                                <div class="flex items-center justify-center gap-1">
                                    Status
                                    <span v-if="sortField === 'status'" class="text-blue-600 dark:text-blue-400">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr 
                            v-for="so in salesOrders.data" 
                            :key="so.id"
                            class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors"
                        >
                            <td class="px-3 py-2 whitespace-nowrap text-center w-10">
                                <input 
                                    v-if="so.status === 'draft'"
                                    type="checkbox" 
                                    :checked="selectedIds.includes(so.id)" 
                                    @change="toggleOne(so.id)" 
                                    class="w-4 h-4 rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500/50 cursor-pointer" 
                                />
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 dark:bg-slate-800">
                                        <DocumentTextIcon class="h-5 w-5 text-slate-500 dark:text-slate-400" />
                                    </div>
                                    <div>
                                        <Link 
                                            :href="`/sales/orders/${so.id}`"
                                            class="text-sm font-medium text-slate-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 hover:underline transition-colors"
                                        >
                                            {{ so.so_number }}
                                        </Link>
                                        <div class="text-xs text-slate-500">{{ so.warehouse?.name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <div class="text-sm text-slate-900 dark:text-white">{{ so.customer?.name }}</div>
                                <div class="text-xs text-slate-500">{{ so.customer?.code }}</div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap max-w-[180px]">
                                <div class="truncate text-sm font-medium text-slate-600 dark:text-slate-300 font-mono" :title="so.customer_po_number">
                                    {{ so.customer_po_number || '-' }}
                                </div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <span class="text-sm text-slate-600 dark:text-slate-300">{{ formatDate(so.order_date) }}</span>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <span class="text-sm text-slate-600 dark:text-slate-300">{{ formatDate(so.delivery_date) }}</span>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-center text-sm text-slate-600 dark:text-slate-300">
                                {{ so.items_count }}
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-center text-sm font-medium text-slate-900 dark:text-white">
                                {{ formatNumber(so.total_qty_ordered || 0) }}
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-center">
                                <div class="flex flex-col items-center gap-1">
                                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                        {{ calculatePercentage(so.total_qty_delivered, so.total_qty_ordered) }}%
                                    </span>
                                    <div class="w-20 h-1.5 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                        <div class="h-full bg-emerald-500 rounded-full" :style="{ width: calculateWidth(so.total_qty_delivered, so.total_qty_ordered) }"></div>
                                    </div>
                                    <span class="text-[10px] text-slate-400">{{ formatNumber(so.total_qty_delivered || 0) }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-center">
                                <div class="flex flex-col items-center gap-1">
                                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                        {{ calculatePercentage(so.total_qty_invoiced, so.total_qty_ordered) }}%
                                    </span>
                                    <div class="w-20 h-1.5 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                        <div class="h-full bg-blue-500 rounded-full" :style="{ width: calculateWidth(so.total_qty_invoiced, so.total_qty_ordered) }"></div>
                                    </div>
                                    <span class="text-[10px] text-slate-400">{{ formatNumber(so.total_qty_invoiced || 0) }}</span>
                                </div>
                            </td>


                            <td class="px-4 py-2 whitespace-nowrap text-center text-sm text-red-400">
                                {{ formatNumber(so.total_qty_returned || 0) }}
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-center">
                                <span 
                                    class="text-sm font-bold"
                                    :class="(parseFloat(so.total_qty_ordered) - (parseFloat(so.total_qty_delivered) - parseFloat(so.total_qty_returned || 0))) > 0 ? 'text-amber-400' : 'text-emerald-500'"
                                >
                                    {{ formatNumber(parseFloat(so.total_qty_ordered || 0) - (parseFloat(so.total_qty_delivered || 0) - parseFloat(so.total_qty_returned || 0))) }}
                                </span>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-right">
                                <span class="text-sm font-medium text-slate-900 dark:text-white">{{ formatCurrency(so.total) }}</span>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-right text-xs font-mono">
                                {{ formatCurrency(so.total_amount_invoiced || 0) }}
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-center">
                                <div class="flex flex-col items-center gap-1">
                                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                        {{ calculatePercentage(so.total_amount_invoiced, so.total) }}%
                                    </span>
                                    <div class="w-20 h-1.5 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                        <div class="h-full bg-indigo-500 rounded-full" :style="{ width: calculateWidth(so.total_amount_invoiced, so.total) }"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-center">
                                <span 
                                    class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium cursor-help"
                                    :class="getStatusBadge(so.status)"
                                    :title="getStatusDescription(so.status)"
                                >
                                    {{ getStatusLabel(so.status) }}
                                </span>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <Link
                                        :href="`/sales/orders/${so.id}`"
                                        class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors"
                                    >
                                        <EyeIcon class="h-4 w-4" />
                                    </Link>
                                    <Link
                                        v-if="so.status === 'draft' || so.status === 'waiting_po'"
                                        :href="`/sales/orders/${so.id}/edit`"
                                        class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-blue-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors"
                                    >
                                        <PencilSquareIcon class="h-4 w-4" />
                                    </Link>
                                    <button
                                        v-if="so.status === 'draft'"
                                        @click="deleteSO(so)"
                                        class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-red-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors"
                                    >
                                        <TrashIcon class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="salesOrders.data.length === 0">
                            <td colspan="15" class="px-4 py-12 text-center">
                                <DocumentTextIcon class="mx-auto h-12 w-12 text-slate-600" />
                                <h3 class="mt-2 text-sm font-medium text-slate-600 dark:text-slate-300">No sales orders found</h3>
                                <p class="mt-1 text-sm text-slate-500">Create a new sales order to get started.</p>
                                <div class="mt-4">
                                    <Link
                                        href="/sales/orders/create"
                                        class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 transition-colors"
                                    >
                                        <PlusIcon class="h-4 w-4" />
                                        Create SO
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="salesOrders.last_page > 1" class="border-t border-slate-200 dark:border-slate-800 px-6 py-4 flex items-center justify-between">
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Showing {{ salesOrders.from }} to {{ salesOrders.to }} of {{ salesOrders.total }} orders
                </p>
                <div class="flex items-center gap-2">
                    <Link
                        v-for="link in salesOrders.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        class="px-3 py-1.5 rounded-lg text-sm transition-colors"
                        :class="link.active 
                            ? 'bg-blue-600 text-white' 
                            : link.url 
                                ? 'text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' 
                                : 'text-slate-300 dark:text-slate-600 cursor-not-allowed'"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>

        <!-- Feature Guide -->
        <div class="mt-12">
            <div class="flex items-center gap-2 mb-4 px-1">
                <div class="h-px flex-1 bg-slate-50 dark:bg-slate-800"></div>
                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Sales Operations Guide</span>
                <div class="h-px flex-1 bg-slate-50 dark:bg-slate-800"></div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-blue-500/10 text-blue-400">
                            <CheckBadgeIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Order Fulfillment</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Track <strong>Qty Balance</strong> to see what's pending. Orders move from Draft to Confirmed before they can be processed for shipping.
                    </p>
                </div>

                <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-purple-500/10 text-purple-400">
                            <TruckIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Delivery Linking</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Generate <strong>Delivery Orders (DO)</strong> directly from confirmed SOs. One SO can have multiple partial deliveries if needed.
                    </p>
                </div>

                <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-400">
                            <CurrencyDollarIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Invoicing Flow</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Create <strong>Proforma or Final Invoices</strong> based on ordered or delivered quantities to ensure accurate billing for your customers.
                    </p>
                </div>
                
                <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-amber-500/10 text-amber-400">
                            <ShieldCheckIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Return Tracking</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Monitor <strong>Returned Items</strong> in the list view. The system automatically recalculates the balance to prevent over-delivery.
                    </p>
                </div>
            </div>
        </div>
        <!-- Import Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showImportModal" class="fixed inset-0 z-50 flex items-center justify-center">
                    <!-- Backdrop -->
                    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showImportModal = false"></div>
                    
                    <!-- Modal Content -->
                    <div class="relative w-full max-w-lg mx-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-2xl">
                        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Import Sales Orders</h3>
                            <button @click="showImportModal = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                        
                        <form @submit.prevent="submitImport" class="p-6">
                            <div class="mb-4">
                                <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                                    Upload an Excel file (.xlsx, .xls, .csv) to import Sales Orders. Rows with the same Customer Code + PO + Order Date will be grouped into one SO.
                                </p>

                                <div class="flex items-center gap-4 mb-4">
                                    <a 
                                        :href="`/sales/orders-template?with_data=${includeData ? 1 : 0}`" 
                                        class="inline-flex items-center gap-2 text-sm font-medium text-blue-500 hover:text-blue-400 transition-colors"
                                    >
                                        <ArrowDownTrayIcon class="h-4 w-4" />
                                        Download Template
                                    </a>

                                    <div class="flex items-center gap-2">
                                        <input
                                            id="include_data"
                                            type="checkbox"
                                            v-model="includeData"
                                            class="h-3.5 w-3.5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded dark:bg-slate-700 dark:border-slate-600"
                                        />
                                        <label for="include_data" class="text-xs text-slate-500 dark:text-slate-400 cursor-pointer select-none">
                                            Include Existing Data
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Select File</label>
                                <input 
                                    ref="fileInput"
                                    type="file" 
                                    @change="handleFileChange"
                                    accept=".xlsx,.xls,.csv"
                                    class="block w-full text-sm text-slate-500 dark:text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-blue-500/10 file:text-blue-500 hover:file:bg-blue-500/20 transition-all cursor-pointer"
                                />
                                <p v-if="importForm.errors.file" class="mt-1 text-sm text-red-500">{{ importForm.errors.file }}</p>
                            </div>

                            <div class="mb-6">
                                <label class="flex items-start gap-3 p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                    <div class="flex items-center h-5">
                                        <input 
                                            v-model="importForm.overwrite" 
                                            type="checkbox" 
                                            class="w-4 h-4 rounded border-slate-600 bg-slate-700 text-blue-600 focus:ring-blue-500/50 focus:ring-offset-0"
                                        />
                                    </div>
                                    <div class="flex-1">
                                        <span class="block text-sm font-medium text-slate-600 dark:text-slate-300">Overwrite Existing Data</span>
                                        <p class="text-xs text-slate-500 mt-1">
                                            If checked, existing Draft or Waiting PO orders matching the Customer + PO + Date will be overwritten (items replaced).
                                        </p>
                                        <div v-if="importForm.overwrite" class="mt-2 flex items-start gap-2 text-xs text-amber-500 bg-amber-500/10 p-2 rounded-lg border border-amber-500/20">
                                            <ExclamationTriangleIcon class="h-4 w-4 shrink-0" />
                                            <span>Warning: This will delete and replace all items in matching existing sales orders.</span>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            
                            <div class="flex items-center justify-end gap-3">
                                <button 
                                    type="button" 
                                    @click="showImportModal = false"
                                    class="px-4 py-2 rounded-xl text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                                >
                                    Cancel
                                </button>
                                <button 
                                    type="submit" 
                                    :disabled="!importForm.file || importForm.processing"
                                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 hover:from-blue-500 hover:to-blue-400 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <ArrowUpTrayIcon class="h-4 w-4" />
                                    {{ importForm.processing ? 'Importing...' : 'Import' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>
