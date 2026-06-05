<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    PlusIcon,
    MagnifyingGlassIcon,
    EyeIcon,
    TruckIcon,
    FunnelIcon,
    InformationCircleIcon,
    CubeIcon,
    CheckCircleIcon,
    ClipboardDocumentCheckIcon,
    ArchiveBoxArrowDownIcon,
    ShieldCheckIcon,
    QrCodeIcon,
    ChevronUpIcon,
    ChevronDownIcon,
    ArrowDownTrayIcon,
    ArrowUpTrayIcon,
    TrashIcon,
} from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    receipts: Object,
    suppliers: Array,
    filters: Object,
    statuses: Array,
});

const search = ref(props.filters?.search || '');
const selectedStatus = ref(props.filters?.status || '');
const selectedSupplier = ref(props.filters?.supplier || '');
const dateFrom = ref(props.filters?.date_range?.[0] || '');
const dateTo = ref(props.filters?.date_range?.[1] || '');
const sortField = ref(props.filters?.sort || 'created_at');
const sortDirection = ref(props.filters?.direction || 'desc');
const showFilters = ref(false);

const showImportModal = ref(false);
const importForm = useForm({
    file: null,
    with_data: false,
    overwrite: false,
});

const openImportModal = () => {
    showImportModal.value = true;
};

const closeImportModal = () => {
    showImportModal.value = false;
    importForm.reset();
};

const submitImport = () => {
    importForm.post(route('purchasing.receipts.import'), {
        preserveScroll: true,
        onSuccess: () => closeImportModal(),
    });
};

const handleFileChange = (e) => {
    importForm.file = e.target.files[0];
};

const exportReceipts = () => {
    window.location.href = route('purchasing.receipts.export');
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
    router.get('/purchasing/receipts', {
        search: search.value || undefined,
        status: selectedStatus.value || undefined,
        supplier: selectedSupplier.value || undefined,
        date_range: (dateFrom.value && dateTo.value) ? [dateFrom.value, dateTo.value] : undefined,
        sort: sortField.value,
        direction: sortDirection.value,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, selectedStatus, selectedSupplier, dateFrom, dateTo], applyFilters);

const clearFilters = () => {
    selectedStatus.value = '';
    selectedSupplier.value = '';
    dateFrom.value = '';
    dateTo.value = '';
};

const getStatusBadge = (status) => {
    if (!status) return 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30';
    const badges = {
        draft: 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30',
        received: 'bg-blue-500/20 text-blue-400 border-blue-500/30',
        inspected: 'bg-amber-500/20 text-amber-400 border-amber-500/30',
        completed: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
    };
    return badges[status] || 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30';
};

const formatDate = (date) => {
    if (!date) return '-';
    try {
        return new Date(date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
    } catch (e) {
        return date;
    }
};
const deleteReceipt = (id) => {
    if (confirm('Are you sure you want to delete this draft receipt?')) {
        router.delete(route('purchasing.receipts.destroy', id));
    }
};
</script>

<template>
    <Head title="Goods Receipts" />
    
    <AppLayout title="Goods Receipts">
        <template v-if="receipts">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div class="flex items-center gap-3">
                    <div class="relative flex-1 sm:w-80">
                        <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500" />
                        <input
                            v-model="search"
                            type="search"
                            placeholder="Search GRN number..."
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
                </div>
                
                <div class="flex gap-2">
                    <button 
                        @click="exportReceipts"
                        class="hidden md:inline-flex items-center gap-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-4 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition-all"
                        title="Export to Excel"
                    >
                        <ArrowDownTrayIcon class="h-5 w-5" />
                        <span class="hidden md:inline">Export</span>
                    </button>

                    <button 
                        @click="openImportModal"
                        class="hidden md:inline-flex items-center gap-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-4 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition-all"
                        title="Import from Excel"
                    >
                        <ArrowUpTrayIcon class="h-5 w-5" />
                        <span class="hidden md:inline">Import</span>
                    </button>

                    <Link
                        :href="route('purchasing.receipts.scan')"
                        class="inline-flex items-center gap-2 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-4 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all"
                    >
                        <QrCodeIcon class="h-5 w-5" />
                        Scan Inbound
                    </Link>
                    <Link
                        href="/purchasing/receipts/create"
                        class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 hover:from-blue-500 hover:to-blue-400 transition-all"
                    >
                        <PlusIcon class="h-5 w-5" />
                        New Receipt
                    </Link>
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
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Status</label>
                            <select v-model="selectedStatus" class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50">
                                <option value="">All Status</option>
                                <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
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
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Date From</label>
                            <input
                                v-model="dateFrom"
                                type="date"
                                class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Date To</label>
                            <input
                                v-model="dateTo"
                                type="date"
                                class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                            />
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

            <div class="rounded-2xl glass-card overflow-hidden">
                <div class="overflow-x-auto overflow-y-auto max-h-[600px]">
                    <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700">
                                <th @click="sort('grn_number')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors">
                                    <div class="flex items-center gap-1">
                                        Receipt Number
                                        <span v-if="sortField === 'grn_number'" class="text-blue-600 dark:text-blue-400">
                                            <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                            <ChevronDownIcon v-else class="h-3 w-3" />
                                        </span>
                                    </div>
                                </th>
                                <th @click="sort('po_number')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors">
                                    <div class="flex items-center gap-1">
                                        PO Reference
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
                                <th @click="sort('warehouse_name')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors">
                                    <div class="flex items-center gap-1">
                                        Warehouse
                                        <span v-if="sortField === 'warehouse_name'" class="text-blue-600 dark:text-blue-400">
                                            <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                            <ChevronDownIcon v-else class="h-3 w-3" />
                                        </span>
                                    </div>
                                </th>
                                <th @click="sort('receipt_date')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors">
                                    <div class="flex items-center gap-1">
                                        Date
                                        <span v-if="sortField === 'receipt_date'" class="text-blue-600 dark:text-blue-400">
                                            <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                            <ChevronDownIcon v-else class="h-3 w-3" />
                                        </span>
                                    </div>
                                </th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Items</th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Qty</th>
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
                            <tr v-for="receipt in receipts.data" :key="receipt.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors">
                                <td class="px-4 py-2 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 dark:bg-slate-800">
                                            <TruckIcon class="h-5 w-5 text-indigo-400" />
                                        </div>
                                        <div class="text-sm font-medium text-slate-900 dark:text-white">{{ receipt.grn_number }}</div>
                                    </div>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap">
                                    <Link :href="`/purchasing/orders/${receipt.purchase_order_id}`" class="text-sm text-blue-400 hover:underline">
                                        {{ receipt.purchase_order?.po_number || 'N/A' }}
                                    </Link>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-slate-900 dark:text-white">
                                    {{ receipt.supplier?.name || 'N/A' }}
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-slate-600 dark:text-slate-300">
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 w-2 rounded-full bg-blue-500"></div>
                                        {{ receipt.warehouse?.name || 'N/A' }}
                                    </div>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-slate-600 dark:text-slate-300">
                                    {{ formatDate(receipt.receipt_date) }}
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-center text-sm text-slate-600 dark:text-slate-300">
                                    {{ receipt.items_count }}
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-center text-sm font-bold text-slate-900 dark:text-white">
                                    {{ parseFloat(receipt.items_sum_qty_received || 0).toLocaleString() }}
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium" :class="getStatusBadge(receipt.status)">{{ receipt.status?.toUpperCase() }}</span>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link :href="`/purchasing/receipts/${receipt.id}`" class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors">
                                            <EyeIcon class="h-4 w-4" />
                                        </Link>
                                        <button 
                                            v-if="receipt.status === 'draft'"
                                            @click="deleteReceipt(receipt.id)"
                                            class="p-2 rounded-lg text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                                            title="Delete Draft"
                                        >
                                            <TrashIcon class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="receipts.data && receipts.data.length === 0">
                                <td colspan="9" class="px-4 py-12 text-center text-slate-500 italic">No goods receipts found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div v-if="receipts.last_page > 1" class="border-t border-slate-200 dark:border-slate-800 px-6 py-4">
                    <Pagination :links="receipts.links" />
                </div>
            </div>

            <!-- Feature Guide -->
            <div class="mt-12">
                <div class="flex items-center gap-2 mb-4 px-1">
                    <div class="h-px flex-1 bg-slate-50 dark:bg-slate-800"></div>
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Inbound Logistics Guide</span>
                    <div class="h-px flex-1 bg-slate-50 dark:bg-slate-800"></div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2.5 rounded-xl bg-indigo-500/10 text-indigo-400">
                                <TruckIcon class="h-5 w-5" />
                            </div>
                            <h4 class="font-bold text-slate-200 text-sm">Goods Receiving</h4>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                            Record <strong>Incoming Items</strong> against issued POs.
                        </p>
                    </div>

                    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-400">
                                <CubeIcon class="h-5 w-5" />
                            </div>
                            <h4 class="font-bold text-slate-200 text-sm">Stock Arrival</h4>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                            Inventory levels are <strong>Automatically Updated</strong>.
                        </p>
                    </div>

                    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2.5 rounded-xl bg-amber-500/10 text-amber-400">
                                <ArchiveBoxArrowDownIcon class="h-5 w-5" />
                            </div>
                            <h4 class="font-bold text-slate-200 text-sm">Partial Receipts</h4>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                            Support for <strong>Split Shipments</strong>.
                        </p>
                    </div>
                    
                    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2.5 rounded-xl bg-purple-500/10 text-purple-400">
                                <ShieldCheckIcon class="h-5 w-5" />
                            </div>
                            <h4 class="font-bold text-slate-200 text-sm">Quality Control</h4>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                            Use receipts to log <strong>Initial Inspections</strong>.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Import Modal -->
            <Modal :show="showImportModal" @close="closeImportModal">
                <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl">
                    <h2 class="text-lg font-medium text-slate-900 dark:text-white mb-4">
                        Import Goods Receipts
                    </h2>
                    
                    <div class="mb-4">
                        <p class="text-sm text-slate-500 dark:text-slate-400 mb-2">
                            Upload an Excel file (.xlsx, .xls) to import Goods Receipts. Baris yang tidak memiliki GRN Number akan digrup berdasar Supplier + Warehouse + Delivery Note ke GRN baru.
                        </p>
                        
                        <div class="mb-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input 
                                    type="checkbox" 
                                    v-model="importForm.with_data"
                                    class="rounded border-slate-300 dark:border-slate-700 text-blue-600 shadow-sm focus:ring-blue-500"
                                >
                                <span class="text-sm text-slate-700 dark:text-slate-300 font-medium">Include Existing Draft GRNs in Template</span>
                            </label>
                            <p class="text-xs text-slate-500 mt-1 ml-6">
                                Exports active 'draft' GRNs into the template so you can easily modify their quantities or add items.
                            </p>
                        </div>

                        <div class="mb-6">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input 
                                    type="checkbox" 
                                    v-model="importForm.overwrite"
                                    class="rounded border-slate-300 dark:border-slate-700 text-red-600 shadow-sm focus:ring-red-500"
                                >
                                <span class="text-sm text-slate-700 dark:text-slate-300 font-medium">Overwrite Existing GRN Data</span>
                            </label>
                            <p class="text-xs text-slate-500 mt-1 ml-6">
                                If checked, uploading rows with an existing 'GRN Number' will <strong class="text-red-500">replace</strong> all items in that draft document. 
                            </p>
                        </div>

                        <a :href="route('purchasing.receipts.template') + (importForm.with_data ? '?with_data=1' : '')" class="inline-flex items-center gap-1 text-sm text-blue-600 hover:text-blue-500 mb-4 font-medium">
                            <ArrowDownTrayIcon class="h-4 w-4" />
                            Download Template {{ importForm.with_data ? 'with Data' : '' }}
                        </a>

                        <p class="text-xs text-slate-400 dark:text-slate-500 mb-4">
                            Required: Receipt Date, Supplier Code, Warehouse Name, Delivery Note, Product Code, Quantity Received.
                        </p>
                        
                        <div class="flex items-center justify-center w-full">
                            <label for="dropzone-file" class="flex flex-col items-center justify-center w-full h-32 border-2 border-slate-300 border-dashed rounded-lg cursor-pointer bg-slate-50 dark:hover:bg-slate-800 dark:bg-slate-900 hover:bg-slate-100 dark:border-slate-700 dark:hover:border-slate-500 transition-colors">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <ArrowUpTrayIcon class="w-8 h-8 mb-2 text-slate-500 dark:text-slate-400" />
                                    <p class="mb-2 text-sm text-slate-500 dark:text-slate-400">
                                        <span class="font-semibold">Click to upload</span>
                                    </p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400" v-if="importForm.file">
                                        {{ importForm.file.name }}
                                    </p>
                                </div>
                                <input id="dropzone-file" type="file" class="hidden" @change="handleFileChange" accept=".xlsx, .xls, .csv" />
                            </label>
                        </div>
                        <div v-if="importForm.errors.file" class="text-red-500 text-xs mt-1">{{ importForm.errors.file }}</div>
                    </div>

                    <div class="flex items-center justify-end gap-3 mt-6">
                        <SecondaryButton @click="closeImportModal"> Cancel </SecondaryButton>
                        <PrimaryButton 
                            @click="submitImport" 
                            :class="{ 'opacity-25': importForm.processing }" 
                            :disabled="!importForm.file || importForm.processing"
                        >
                            Import Data
                        </PrimaryButton>
                    </div>
                </div>
            </Modal>

        </template>
        <div v-else class="text-slate-900 dark:text-white text-center py-20">
            Loading receipt data...
        </div>
    </AppLayout>
</template>



