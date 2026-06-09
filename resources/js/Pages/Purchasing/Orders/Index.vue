<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    PlusIcon,
    MagnifyingGlassIcon,
    PencilSquareIcon,
    TrashIcon,
    EyeIcon,
    FunnelIcon,
    DocumentTextIcon,
    CheckCircleIcon,
    XCircleIcon,
    PaperAirplaneIcon,
    InformationCircleIcon,
    BanknotesIcon,
    TruckIcon,
    CheckBadgeIcon,
    ShoppingCartIcon,
    ClockIcon,
    ArrowDownTrayIcon,
    PrinterIcon,
    ChevronUpIcon,
    ChevronDownIcon,
    ArrowUpTrayIcon,
} from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';
import Pagination from '@/Components/Pagination.vue';
import { formatNumber, formatCurrency } from '@/helpers';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    purchaseOrders: Object,
    stats: Object,
    suppliers: Array,
    users: Array,
    filters: Object,
    statuses: Array,
});

const search = ref(props.filters?.search || '');
const selectedStatus = ref(props.filters?.status || '');
const selectedSupplier = ref(props.filters?.supplier || '');
const selectedCreatedBy = ref(props.filters?.created_by || '');
const sortField = ref(props.filters?.sort || 'created_at');
const sortDirection = ref(props.filters?.direction || 'desc');
const showFilters = ref(false);

const selectedIds = ref([]);

const canSelect = (po) => po?.status === 'approved';

const eligibleIdsOnPage = computed(() => {
    if (!props.purchaseOrders?.data) return [];
    return props.purchaseOrders.data.filter(canSelect).map((po) => po.id);
});

const allEligibleSelected = computed(() => {
    const ids = eligibleIdsOnPage.value;
    if (!ids.length) return false;
    return ids.every((id) => selectedIds.value.includes(id));
});

const showImportModal = ref(false);
const importForm = useForm({
    file: null,
    with_data: false,
    include_all: false,
    overwrite: false,
});

const openImportModal = () => {
    showImportModal.value = true;
};

const closeImportModal = () => {
    showImportModal.value = false;
    importForm.reset();
};

const handleFileChange = (e) => {
    importForm.file = e.target.files[0];
};

const submitImport = () => {
    importForm.post(route('purchasing.orders.import'), {
        preserveScroll: true,
        onSuccess: () => {
            closeImportModal();
        },
    });
};

const exportOrders = () => {
    window.location.href = route('purchasing.orders.export');
};

const sort = (field) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }
    applyFilters();
};

const applyFilters = debounce(() => {
    router.get('/purchasing/orders', {
        search: search.value || undefined,
        status: selectedStatus.value || undefined,
        supplier: selectedSupplier.value || undefined,
        created_by: selectedCreatedBy.value || undefined,
        sort: sortField.value,
        direction: sortDirection.value,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, selectedStatus, selectedSupplier, selectedCreatedBy], () => {
    applyFilters();
});

const clearFilters = () => {
    search.value = '';
    selectedStatus.value = '';
    selectedSupplier.value = '';
    selectedCreatedBy.value = '';
};

const deletePO = (po) => {
    if (confirm(`Are you sure you want to delete "${po.po_number}"?`)) {
        router.delete(`/purchasing/orders/${po.id}`);
    }
};

const toggleSelectAllEligible = () => {
    const ids = eligibleIdsOnPage.value;
    if (!ids.length) return;

    if (allEligibleSelected.value) {
        selectedIds.value = selectedIds.value.filter((id) => !ids.includes(id));
        return;
    }

    const merged = new Set([...selectedIds.value, ...ids]);
    selectedIds.value = Array.from(merged);
};

const bulkMarkOrdered = () => {
    if (!selectedIds.value.length) return;
    if (confirm(`Mark ${selectedIds.value.length} purchase orders as ordered? (Only approved POs will be processed)`)) {
        router.post(route('purchasing.orders.bulk-mark-ordered'), { ids: selectedIds.value }, {
            preserveScroll: true,
            onSuccess: () => {
                selectedIds.value = [];
            },
        });
    }
};

const getStatusBadge = (status) => {
    if (!status) return 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30';
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

const getStatusLabel = (status) => {
    if (!status) return '-';
    const labels = {
        draft: 'Draft',
        submitted: 'Submitted',
        approved: 'Approved',
        ordered: 'Ordered',
        partial: 'Partial',
        received: 'Received',
        cancelled: 'Cancelled',
    };
    return labels[status] || status;
};


const formatDate = (date) => {
    if (!date) return '-';
    try {
        return new Date(date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
    } catch (e) {
        return date;
    }
};
</script>

<template>
    <Head title="Purchase Orders" />
    
    <AppLayout title="Purchase Orders">
        <template v-if="purchaseOrders">
            <!-- Header Actions -->
            <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 mb-6">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="relative w-full sm:w-64">
                        <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500" />
                        <input
                            v-model="search"
                            type="search"
                            placeholder="Search PO number..."
                            class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 py-2 pl-10 pr-4 text-sm text-slate-900 dark:text-white placeholder:text-slate-500 focus:bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-blue-500/50 transition-all"
                        />
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <button 
                            @click="showFilters = !showFilters"
                            class="flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 px-3 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors border border-transparent"
                            :class="{ 'ring-2 ring-blue-500/50 border-blue-500/50': showFilters }"
                        >
                            <FunnelIcon class="h-5 w-5" />
                            <span class="hidden md:inline">Filters</span>
                        </button>

                        <button 
                            @click="exportOrders"
                            class="hidden md:flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 px-3 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors"
                            title="Export to Excel"
                        >
                            <ArrowDownTrayIcon class="h-5 w-5" />
                            <span class="hidden md:inline">Export</span>
                        </button>

                        <button 
                            @click="openImportModal"
                            class="hidden md:flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 px-3 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors"
                            title="Import from Excel"
                        >
                            <ArrowUpTrayIcon class="h-5 w-5" />
                            <span class="hidden md:inline">Import</span>
                        </button>
                    </div>

                    <!-- Mini Statistics Row (Refined) -->
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
                                <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Received</span>
                                <span class="text-sm font-black text-slate-900 dark:text-white">{{ formatNumber(stats.total_received || 0) }}</span>
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
                
                <Link
                    href="/purchasing/orders/create"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 hover:from-blue-500 hover:to-blue-400 transition-all shrink-0"
                >
                    <PlusIcon class="h-5 w-5" />
                    Create PO
                </Link>
            </div>

            <!-- Filters Panel -->
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <div v-if="showFilters" class="mb-6 rounded-2xl glass-card p-4">
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
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
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Supplier</label>
                            <select
                                v-model="selectedSupplier"
                                class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                            >
                                <option value="">All Suppliers</option>
                                <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                                    {{ supplier.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Input By</label>
                            <select
                                v-model="selectedCreatedBy"
                                class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                            >
                                <option value="">All Users</option>
                                <option v-for="user in users" :key="user.id" :value="user.id">
                                    {{ user.name }}
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

            <!-- Purchase Orders Table -->
            <div class="rounded-2xl glass-card overflow-hidden">
                <div v-if="selectedIds.length" class="px-4 py-3 border-b border-slate-200 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-900/30 flex items-center justify-between gap-3">
                    <div class="text-sm text-slate-600 dark:text-slate-300">
                        Selected: <span class="font-bold text-slate-900 dark:text-white">{{ selectedIds.length }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            @click="bulkMarkOrdered"
                            class="inline-flex items-center gap-2 rounded-xl bg-purple-600 px-3 py-2 text-sm font-semibold text-white hover:bg-purple-500 transition-colors"
                        >
                            <TruckIcon class="h-4 w-4" />
                            Mark Ordered
                        </button>
                        <button
                            @click="selectedIds = []"
                            class="inline-flex items-center gap-2 rounded-xl bg-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-300 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 transition-colors"
                        >
                            Clear
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto overflow-y-auto max-h-[600px]">
                    <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700">
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-3 py-2 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-10">
                                    <input
                                        type="checkbox"
                                        :checked="allEligibleSelected"
                                        @change="toggleSelectAllEligible"
                                        class="rounded border-slate-300 dark:border-slate-700 text-blue-600 shadow-sm focus:ring-blue-500"
                                        :disabled="eligibleIdsOnPage.length === 0"
                                    >
                                </th>
                                <th @click="sort('po_number')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors">
                                    <div class="flex items-center gap-1">
                                        PO Number
                                        <span v-if="sortField === 'po_number'" class="text-blue-600 dark:text-blue-400">
                                            <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                            <ChevronDownIcon v-else class="h-3 w-3" />
                                        </span>
                                    </div>
                                </th>
                                <th @click="sort('supplier_name')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors">
                                    <div class="flex items-center gap-1">
                                        Supplier
                                        <span v-if="sortField === 'supplier_name'" class="text-blue-600 dark:text-blue-400">
                                            <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                            <ChevronDownIcon v-else class="h-3 w-3" />
                                        </span>
                                    </div>
                                </th>
                                <th @click="sort('order_date')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors">
                                    <div class="flex items-center gap-1">
                                        Order Date
                                        <span v-if="sortField === 'order_date'" class="text-blue-600 dark:text-blue-400">
                                            <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                            <ChevronDownIcon v-else class="h-3 w-3" />
                                        </span>
                                    </div>
                                </th>
                                <th @click="sort('created_by_name')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors">
                                    <div class="flex items-center gap-1">
                                        Input By
                                        <span v-if="sortField === 'created_by_name'" class="text-blue-600 dark:text-blue-400">
                                            <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                            <ChevronDownIcon v-else class="h-3 w-3" />
                                        </span>
                                    </div>
                                </th>
                                <th @click="sort('items_count')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors">
                                    <div class="flex items-center justify-center gap-1">
                                        Items
                                        <span v-if="sortField === 'items_count'" class="text-blue-600 dark:text-blue-400">
                                            <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                            <ChevronDownIcon v-else class="h-3 w-3" />
                                        </span>
                                    </div>
                                </th>
                                <th @click="sort('total_qty')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors">
                                    <div class="flex items-center justify-center gap-1">
                                        Qty
                                        <span v-if="sortField === 'total_qty'" class="text-blue-600 dark:text-blue-400">
                                            <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                            <ChevronDownIcon v-else class="h-3 w-3" />
                                        </span>
                                    </div>
                                </th>
                                <th @click="sort('total_received')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors">
                                    <div class="flex items-center justify-center gap-1">
                                        Recv
                                        <span v-if="sortField === 'total_received'" class="text-blue-600 dark:text-blue-400">
                                            <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                            <ChevronDownIcon v-else class="h-3 w-3" />
                                        </span>
                                    </div>
                                </th>
                                <th @click="sort('total_returned')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors">
                                    <div class="flex items-center justify-center gap-1">
                                        Return
                                        <span v-if="sortField === 'total_returned'" class="text-blue-600 dark:text-blue-400">
                                            <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                            <ChevronDownIcon v-else class="h-3 w-3" />
                                        </span>
                                    </div>
                                </th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Balance</th>
                                <th @click="sort('total')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors">
                                    <div class="flex items-center justify-end gap-1">
                                        Total
                                        <span v-if="sortField === 'total'" class="text-blue-600 dark:text-blue-400">
                                            <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                            <ChevronDownIcon v-else class="h-3 w-3" />
                                        </span>
                                    </div>
                                </th>
                                <th @click="sort('status')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors">
                                    <div class="flex items-center justify-center gap-1">
                                        Status
                                        <span v-if="sortField === 'status'" class="text-blue-600 dark:text-blue-400">
                                            <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                            <ChevronDownIcon v-else class="h-3 w-3" />
                                        </span>
                                    </div>
                                </th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr 
                                v-for="po in purchaseOrders.data" 
                                :key="po.id"
                                class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors"
                            >
                                <td class="px-3 py-2 whitespace-nowrap">
                                    <input
                                        v-if="canSelect(po)"
                                        type="checkbox"
                                        :value="po.id"
                                        v-model="selectedIds"
                                        class="rounded border-slate-300 dark:border-slate-700 text-blue-600 shadow-sm focus:ring-blue-500"
                                    >
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 dark:bg-slate-800 font-mono text-xs text-slate-500">
                                            PO
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-slate-900 dark:text-white">{{ po.po_number }}</div>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <div class="text-xs text-slate-500">{{ po.warehouse?.name }}</div>
                                                <span
                                                    v-if="po.is_subcontract"
                                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase bg-amber-500/20 text-amber-400 ring-1 ring-inset ring-amber-500/30"
                                                >
                                                    Subcontract
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap">
                                    <div class="text-sm text-slate-900 dark:text-white font-medium">{{ po.supplier?.name }}</div>
                                    <div class="text-xs text-slate-500 font-mono">{{ po.supplier?.code }}</div>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap">
                                    <span class="text-sm text-slate-600 dark:text-slate-300">{{ formatDate(po.order_date) }}</span>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap">
                                    <div class="text-sm text-slate-900 dark:text-white font-medium">
                                        {{ po.created_by?.name || po.createdBy?.name || '-' }}
                                    </div>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-center text-sm text-slate-500 dark:text-slate-400">
                                    {{ po.items_count }}
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-center text-sm font-medium text-slate-900 dark:text-white">
                                    {{ formatNumber(po.total_qty || 0) }}
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-center text-sm text-slate-500 dark:text-slate-400">
                                    {{ formatNumber(po.total_received || 0) }}
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-center text-sm text-red-400/80">
                                    {{ formatNumber(po.total_returned || 0) }}
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-center">
                                    <span 
                                        class="text-sm font-bold"
                                        :class="(parseFloat(po.total_qty || 0) - (parseFloat(po.total_received || 0) - parseFloat(po.total_returned || 0))) > 0 ? 'text-amber-400' : 'text-emerald-500'"
                                    >
                                        {{ formatNumber(parseFloat(po.total_qty || 0) - (parseFloat(po.total_received || 0) - parseFloat(po.total_returned || 0))) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-right">
                                    <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ formatCurrency(po.total) }}</span>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-center text-sm">
                                    <span 
                                        class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold capitalize"
                                        :class="getStatusBadge(po.status)"
                                    >
                                        {{ getStatusLabel(po.status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link
                                            :href="`/purchasing/orders/${po.id}`"
                                            class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors"
                                            title="View"
                                        >
                                            <EyeIcon class="h-4 w-4" />
                                        </Link>
                                        <Link
                                            v-if="po.status === 'draft'"
                                            :href="`/purchasing/orders/${po.id}/edit`"
                                            class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-blue-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors"
                                            title="Edit"
                                        >
                                            <PencilSquareIcon class="h-4 w-4" />
                                        </Link>
                                        <button
                                            v-if="po.status === 'draft'"
                                            @click="deletePO(po)"
                                            class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-red-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors"
                                            title="Delete"
                                        >
                                            <TrashIcon class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="purchaseOrders.data && purchaseOrders.data.length === 0">
                                <td colspan="13" class="px-4 py-12 text-center text-slate-500 italic">No purchase orders found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div v-if="purchaseOrders.last_page > 1" class="border-t border-slate-200 dark:border-slate-800 px-6 py-4">
                    <Pagination :links="purchaseOrders.links" />
                </div>
            </div>

            <!-- Feature Guide -->
            <div class="mt-12">
                <div class="flex items-center gap-2 mb-4 px-1">
                    <div class="h-px flex-1 bg-slate-50 dark:bg-slate-800"></div>
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Procurement Guide</span>
                    <div class="h-px flex-1 bg-slate-50 dark:bg-slate-800"></div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2.5 rounded-xl bg-blue-500/10 text-blue-400">
                                <ShoppingCartIcon class="h-5 w-5" />
                            </div>
                            <h4 class="font-bold text-slate-200 text-sm">Purchase Orders</h4>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                            Official <strong>PO Documents</strong> sent to suppliers.
                        </p>
                    </div>

                    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2.5 rounded-xl bg-orange-500/10 text-orange-400">
                                <ClockIcon class="h-5 w-5" />
                            </div>
                            <h4 class="font-bold text-slate-200 text-sm">Lead Time</h4>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                            Track <strong>Expected Arrival</strong> dates.
                        </p>
                    </div>

                    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-400">
                                <ArrowDownTrayIcon class="h-5 w-5" />
                            </div>
                            <h4 class="font-bold text-slate-200 text-sm">Fast Receiving</h4>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                            Generate <strong>Goods Receipts (GRN)</strong> directly.
                        </p>
                    </div>
                    
                    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2.5 rounded-xl bg-purple-500/10 text-purple-400">
                                <PrinterIcon class="h-5 w-5" />
                            </div>
                            <h4 class="font-bold text-slate-200 text-sm">PO Printout</h4>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                            Generate pixel-perfect <strong>PDF Purchase Orders</strong>.
                        </p>
                    </div>
                </div>
            </div>
        </template>
        <div v-else class="text-slate-900 dark:text-white text-center py-20">
            Loading order data...
        </div>

        <!-- Import Modal -->
        <Modal :show="showImportModal" @close="closeImportModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-slate-900 dark:text-white mb-4">
                    Import Purchase Orders
                </h2>
                
                <div class="mb-4">
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-2">
                        Upload an Excel file (.xlsx, .xls) to import Purchase Orders. Rows with the same Supplier + Warehouse + Date will be grouped into one PO.
                    </p>
                    
                    <div class="mb-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input 
                                type="checkbox" 
                                v-model="importForm.with_data"
                                class="rounded border-slate-300 dark:border-slate-700 text-blue-600 shadow-sm focus:ring-blue-500"
                            >
                            <span class="text-sm text-slate-700 dark:text-slate-300 font-medium">Include Existing Draft POs in Template</span>
                        </label>
                        <p class="text-xs text-slate-500 mt-1 ml-6">
                            Exports your active 'draft' status POs into the template so you can easily modify their quantities or add items.
                        </p>
                    </div>

                    <div class="mb-6" v-if="importForm.with_data">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input 
                                type="checkbox" 
                                v-model="importForm.include_all"
                                class="rounded border-slate-300 dark:border-slate-700 text-amber-600 shadow-sm focus:ring-amber-500"
                            >
                            <span class="text-sm text-slate-700 dark:text-slate-300 font-medium">Include ALL POs (All Statuses)</span>
                        </label>
                        <p class="text-xs text-slate-500 mt-1 ml-6">
                            Jika dicentang, template akan berisi semua item PO (draft + non-draft). Untuk import overwrite, sistem tetap hanya boleh overwrite PO status draft.
                        </p>
                    </div>

                    <div class="mb-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input 
                                type="checkbox" 
                                v-model="importForm.overwrite"
                                class="rounded border-slate-300 dark:border-slate-700 text-red-600 shadow-sm focus:ring-red-500"
                            >
                            <span class="text-sm text-slate-700 dark:text-slate-300 font-medium">Overwrite Existing PO Data</span>
                        </label>
                        <p class="text-xs text-slate-500 mt-1 ml-6">
                            If checked, uploading rows with an existing 'PO Number' will <strong class="text-red-500">replace</strong> all items in that draft PO. 
                        </p>
                    </div>

                    <a :href="route('purchasing.orders.template') + (importForm.with_data ? ('?with_data=1' + (importForm.include_all ? '&all=1' : '')) : '')" class="inline-flex items-center gap-1 text-sm text-blue-600 hover:text-blue-500 mb-4 font-medium">
                        <ArrowDownTrayIcon class="h-4 w-4" />
                        Download Template {{ importForm.with_data ? 'with Data' : '' }}
                    </a>

                    <p class="text-xs text-slate-400 dark:text-slate-500 mb-4">
                        Required columns: Order Date, Supplier Code, Warehouse Name, Product Code, Quantity.
                    </p>

                    <input 
                        type="file" 
                        @change="handleFileChange"
                        accept=".xlsx, .xls, .csv"
                        class="block w-full text-sm text-slate-500
                            file:mr-4 file:py-2 file:px-4
                            file:rounded-full file:border-0
                            file:text-sm file:font-semibold
                            file:bg-blue-50 file:text-blue-700
                            hover:file:bg-blue-100 dark:file:bg-slate-800 dark:file:text-blue-400"
                    />
                    <div v-if="importForm.errors.file" class="text-red-500 text-xs mt-1">{{ importForm.errors.file }}</div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="closeImportModal">
                        Cancel
                    </SecondaryButton>
                    <PrimaryButton 
                        @click="submitImport" 
                        :disabled="importForm.processing || !importForm.file"
                        :class="{ 'opacity-25': importForm.processing }"
                    >
                        <ArrowUpTrayIcon class="h-4 w-4 mr-2" />
                        Import
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>

