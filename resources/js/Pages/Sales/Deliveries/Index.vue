<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    MagnifyingGlassIcon,
    FunnelIcon,
    TruckIcon,
    EyeIcon,
    PrinterIcon,
    PlusIcon,
    XMarkIcon,
    InboxStackIcon,
    DocumentCheckIcon,
    ShieldCheckIcon,
    TrashIcon,
    ListBulletIcon,
    Squares2X2Icon,
    InformationCircleIcon,
    ChevronDownIcon,
    UserIcon,
    SparklesIcon,
    ArrowPathIcon,
    ChevronUpIcon,
    ArrowDownTrayIcon,
    ArrowUpTrayIcon,
} from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';
import Board from './Board.vue';

const props = defineProps({
    deliveryOrders: Object,
    pendingSalesOrders: Array,
    filters: Object,
    statuses: Array,
});

const search = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || '');
const showFilters = ref(false);
const viewMode = ref('list'); // list or board (Trigger Update)
const sortField = ref(props.filters.sort || 'delivery_date');
const sortDirection = ref(props.filters.direction || 'desc');

const deleteDelivery = (id) => {
    if (confirm('Yakin ingin menghapus Draft Surat Jalan ini? Tindakan ini tidak dapat dibatalkan.')) {
        router.delete(route('sales.deliveries.destroy', id), {
            preserveScroll: true
        });
    }
};

const applyFilters = debounce(() => {
    router.get(route('sales.deliveries.index'), {
        search: search.value || undefined,
        status: selectedStatus.value || undefined,
        invoice_status: invoiceStatus.value || undefined,
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

watch([search, selectedStatus], applyFilters);

const getStatusBadge = (status) => {
    const badges = {
        draft: 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30',
        confirmed: 'bg-blue-500/20 text-blue-400 border-blue-500/30',
        picking: 'bg-amber-500/20 text-amber-400 border-amber-500/30',
        packed: 'bg-blue-600/20 text-blue-500 border-blue-500/30',
        shipped: 'bg-purple-500/20 text-purple-400 border-purple-500/30',
        delivered: 'bg-teal-500/20 text-teal-400 border-teal-500/30',
        completed: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
        cancelled: 'bg-red-500/20 text-red-400 border-red-500/30',
    };
    return badges[status] || 'bg-slate-500/10 text-slate-400 border-slate-500/20';
};

const getInvoiceStatusBadge = (status) => {
    const badges = {
        pending: 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30', // Default/Neutral
        partial: 'bg-amber-500/20 text-amber-400 border-amber-500/30', // Warning/Partial
        invoiced: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30', // Success/Completed
    };
    return badges[status] || 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30';
};

const getInvoiceStatusDescription = (status) => {
    const descriptions = {
        pending: 'Belum ada item yang dibuatkan invois.',
        partial: 'Baru sebagian item ditagih atau ada penyesuaian tonase.',
        invoiced: 'Seluruh barang sudah berhasil ditagih (Lunas Invois).',
    };
    return descriptions[status] || '';
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

const getStatusDescription = (status) => {
    const descriptions = {
        draft: 'Draft surat jalan, belum diproses.',
        picking: 'Barang sedang diambil di gudang.',
        packed: 'Barang siap dimuat (load).',
        shipped: 'Barang dalam perjalanan.',
        delivered: 'Barang sampai di tujuan (Laporan Driver).',
        completed: 'Selesai (Verifikasi Admin/POD).',
        cancelled: 'Pengiriman dibatalkan.',
    };
    return descriptions[status] || '';
};

// Selection Logic
const selectedIds = ref([]);
const isSelectAllAcrossPages = ref(false);
const invoiceStatus = ref(props.filters.invoice_status || '');

const isEligible = (doOrder) => {
    return ['completed', 'delivered', 'shipped'].includes(doOrder.status) && 
           doOrder.invoice_status !== 'invoiced';
};

const toggleAll = (e) => {
    isSelectAllAcrossPages.value = false;
    if (e.target.checked) {
        selectedIds.value = props.deliveryOrders.data
            .filter(d => isEligible(d))
            .map(d => d.id);
    } else {
        selectedIds.value = [];
    }
};

const selectAllAcrossPages = () => {
    isSelectAllAcrossPages.value = true;
};

// Preview Modal State
const isPreviewModalOpen = ref(false);
const isPreparingPreview = ref(false);
const previewData = ref(null);
const excludedSoIds = ref([]);

const toggleInvoiceSelection = (soId) => {
    const index = excludedSoIds.value.indexOf(soId);
    if (index > -1) {
        excludedSoIds.value.splice(index, 1);
    } else {
        excludedSoIds.value.push(soId);
    }
};

const finalInvoiceCount = computed(() => {
    if (!previewData.value) return 0;
    return previewData.value.invoices_count - excludedSoIds.value.length;
});

const createConsolidatedInvoice = async () => {
    if (selectedIds.value.length === 0 && !isSelectAllAcrossPages.value) return;

    isPreparingPreview.value = true;
    try {
        const response = await axios.post(route('sales.deliveries.bulk-invoice-preview'), {
            ids: selectedIds.value,
            select_all: isSelectAllAcrossPages.value,
            filters: {
                search: search.value,
                status: selectedStatus.value,
                invoice_status: invoiceStatus.value
            }
        });
        
        previewData.value = response.data;
        excludedSoIds.value = []; // Reset exclusions on new preview
        isPreviewModalOpen.value = true;
    } catch (error) {
        console.error('Preview error:', error);
        alert(error.response?.data?.error || 'Failed to prepare preview. Please try again.');
    } finally {
        isPreparingPreview.value = false;
    }
};

const confirmBulkInvoice = () => {
    router.post(route('sales.deliveries.bulk-invoice'), {
        ids: selectedIds.value,
        select_all: isSelectAllAcrossPages.value,
        filters: {
            search: search.value,
            status: selectedStatus.value,
            invoice_status: invoiceStatus.value
        },
        excluded_so_ids: excludedSoIds.value
    }, {
        onStart: () => {
            isPreviewModalOpen.value = false;
        },
        onSuccess: () => {
            selectedIds.value = [];
            isSelectAllAcrossPages.value = false;
        }
    });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
};

const invoiceStatuses = [
    { value: 'pending', label: 'Pending', description: 'Belum ditagih sama sekali.' },
    { value: 'partial', label: 'Partial', description: 'Ditagih sebagian (Item tertentu saja/Koreksi tonase).' },
    { value: 'invoiced', label: 'Fully Invoiced', description: 'Sudah ditagih lunas seluruh item.' },
];

watch([search, selectedStatus, invoiceStatus], () => {
    selectedIds.value = [];
    isSelectAllAcrossPages.value = false;
    applyFilters();
});

// Import modal state
const showImportModal = ref(false);
const importForm = useForm({ file: null });
const onFileChange = (e) => { importForm.file = e.target.files[0]; };
const submitImport = () => {
    importForm.post(route('sales.deliveries.import'), {
        onSuccess: () => { showImportModal.value = false; importForm.reset(); },
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="Delivery Orders" />
    
    <AppLayout title="Delivery Orders">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div class="flex items-center gap-3">
                <div class="relative flex-1 sm:w-80">
                    <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500" />
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search DO number..."
                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 py-2.5 pl-10 pr-4 text-sm text-slate-900 dark:text-white placeholder:text-slate-500 focus:bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-blue-500/50 transition-all"
                    />
                </div>
                <!-- View Toggle -->
                <div class="bg-slate-50 dark:bg-slate-900 p-1 rounded-xl flex items-center border border-slate-200 dark:border-slate-800">
                    <button 
                        @click="viewMode = 'list'"
                        class="p-2 rounded-lg transition-all"
                        :class="viewMode === 'list' ? 'bg-white dark:bg-slate-800 text-blue-600 shadow-sm' : 'text-slate-400 hover:text-slate-600'"
                    >
                        <ListBulletIcon class="w-5 h-5" />
                    </button>
                    <button 
                        @click="viewMode = 'board'"
                        class="p-2 rounded-lg transition-all"
                        :class="viewMode === 'board' ? 'bg-white dark:bg-slate-800 text-blue-600 shadow-sm' : 'text-slate-400 hover:text-slate-600'"
                    >
                        <Squares2X2Icon class="w-5 h-5" />
                    </button>
                </div>

                <button 
                    @click="showFilters = !showFilters"
                    class="flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 px-4 py-2.5 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors"
                    :class="{ 'ring-2 ring-blue-500/50': showFilters }"
                >
                    <FunnelIcon class="h-5 w-5" />
                </button>

                <!-- Bulk Action -->
                <button
                    v-if="selectedIds.length > 0 || isSelectAllAcrossPages"
                    @click="createConsolidatedInvoice"
                    :disabled="isPreparingPreview"
                    class="flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-indigo-500/25 hover:bg-indigo-500 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <ArrowPathIcon v-if="isPreparingPreview" class="h-5 w-5 animate-spin" />
                    <DocumentCheckIcon v-else class="h-5 w-5" />
                    {{ isPreparingPreview ? 'Preparing Preview...' : `Create Invoice (${isSelectAllAcrossPages ? deliveryOrders.total : selectedIds.length})` }}
                </button>
            </div>

            <div class="flex items-center gap-2">
                <a
                    :href="route('sales.deliveries.export')"
                    class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/25 hover:bg-emerald-500 transition-all"
                >
                    <ArrowDownTrayIcon class="h-5 w-5" />
                    Export
                </a>
                <button
                    @click="showImportModal = true"
                    class="inline-flex items-center gap-2 rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-amber-500/25 hover:bg-amber-500 transition-all"
                >
                    <ArrowUpTrayIcon class="h-5 w-5" />
                    Import
                </button>
                <Link
                    :href="route('sales.deliveries.create')"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 hover:from-blue-500 hover:to-blue-400 transition-all"
                >
                    <PlusIcon class="h-5 w-5" />
                    Create Delivery
                </Link>
            </div>
        </div>

        <!-- Filter Panel -->
        <div v-if="showFilters" class="mb-6 p-4 rounded-xl bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="relative group">
                <label class="text-xs font-bold text-slate-500 mb-1 block">Status</label>
                <div class="relative">
                    <select
                        v-model="selectedStatus"
                        class="w-full bg-white dark:bg-slate-900 border-0 ring-2 ring-slate-200 dark:ring-slate-800 rounded-xl px-4 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all shadow-sm appearance-none"
                    >
                        <option value="">All Statuses</option>
                        <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                    </select>
                    <ChevronDownIcon class="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 pointer-events-none" />
                </div>
            </div>
            <div class="relative group">
                <div class="flex items-center gap-1 mb-1">
                    <label class="text-xs font-bold text-slate-500 block">Invoicing Status</label>
                    <InformationCircleIcon 
                        class="h-3.5 w-3.5 text-slate-400 cursor-help" 
                        title="Status penagihan Surat Jalan. 'Partial' muncul jika ada item yang belum ditagih atau tonase dikoreksi."
                    />
                </div>
                <div class="relative">
                    <select
                        v-model="invoiceStatus"
                        class="w-full bg-white dark:bg-slate-900 border-0 ring-2 ring-slate-200 dark:ring-slate-800 rounded-xl px-4 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all shadow-sm appearance-none"
                    >
                        <option value="">All Invoicing</option>
                        <option 
                            v-for="s in invoiceStatuses" 
                            :key="s.value" 
                            :value="s.value"
                            :title="s.description"
                        >
                            {{ s.label }}
                        </option>
                    </select>
                    <ChevronDownIcon class="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 pointer-events-none" />
                </div>
            </div>
        </div>

        <!-- BOARD VIEW -->
        <div v-if="viewMode === 'board'" class="h-[calc(100vh-280px)] overflow-hidden">
             <!-- Warning if filtered -->
             <div v-if="selectedStatus" class="mb-4 bg-amber-50 text-amber-600 px-4 py-2 rounded-lg text-xs font-bold border border-amber-100 flex items-center gap-2">
                <FunnelIcon class="w-4 h-4" />
                Filter aktif: Board mungkin tidak menampilkan semua item.
             </div>
             <Board :orders="deliveryOrders" />
        </div>

        <!-- LIST VIEW -->
        <div v-else class="rounded-2xl glass-card overflow-hidden">
            <!-- Selection Banner -->
            <div 
                v-if="selectedIds.length > 0 && selectedIds.length === deliveryOrders.data.filter(d => isEligible(d)).length && deliveryOrders.total > deliveryOrders.data.length"
                class="bg-blue-600/10 dark:bg-blue-500/10 px-6 py-3 border-b border-blue-500/20 flex items-center justify-between"
            >
                <div class="text-sm text-blue-600 dark:text-blue-400 font-medium">
                    <span v-if="!isSelectAllAcrossPages">
                        All {{ selectedIds.length }} items on this page are selected.
                        <button @click="selectAllAcrossPages" class="ml-2 underline font-bold hover:text-blue-700 dark:hover:text-blue-300">
                            Select all {{ deliveryOrders.total }} deliveries matching this filter
                        </button>
                    </span>
                    <span v-else>
                        All {{ deliveryOrders.total }} deliveries matching this filter are selected.
                        <button @click="selectedIds = []; isSelectAllAcrossPages = false" class="ml-2 underline font-bold hover:text-blue-700 dark:hover:text-blue-300">
                            Clear selection
                        </button>
                    </span>
                </div>
            </div>

            <div class="overflow-x-auto overflow-y-auto max-h-[600px]">
                <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700">
                            <th @click="sort('do_number')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group">
                                <div class="flex items-center gap-2">
                                    <input 
                                        type="checkbox" 
                                        @change="toggleAll"
                                        :checked="isSelectAllAcrossPages || (selectedIds.length > 0 && selectedIds.length === deliveryOrders.data.filter(d => isEligible(d)).length)"
                                        @click.stop
                                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                                    >
                                    <div class="flex items-center gap-1">
                                        <span>Nomor SJ</span>
                                        <span v-if="sortField === 'do_number'" class="text-blue-600 dark:text-blue-400">
                                            <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                            <ChevronDownIcon v-else class="h-3 w-3" />
                                        </span>
                                    </div>
                                </div>
                            </th>
                            <th @click="sort('so_number')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group">
                                <div class="flex items-center gap-1">
                                    Reference (SO/PO)
                                    <span v-if="sortField === 'so_number'" class="text-blue-600 dark:text-blue-400">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                Vehicle
                            </th>
                            <th @click="sort('customer_name')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group">
                                <div class="flex items-center gap-1">
                                    Customer
                                    <span v-if="sortField === 'customer_name'" class="text-blue-600 dark:text-blue-400">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th @click="sort('delivery_date')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group">
                                <div class="flex items-center gap-1">
                                    Date
                                    <span v-if="sortField === 'delivery_date'" class="text-blue-600 dark:text-blue-400">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                <div class="flex items-center justify-center gap-1">
                                    Total Qty
                                </div>
                            </th>
                            <th @click="sort('items_count')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group">
                                <div class="flex items-center justify-center gap-1">
                                    Items
                                    <span v-if="sortField === 'items_count'" class="text-blue-600 dark:text-blue-400">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th @click="sort('status')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group">
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
                            v-for="doOrder in deliveryOrders.data" 
                            :key="doOrder.id"
                            class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors"
                        >
                            <td class="px-4 py-2 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <input 
                                        type="checkbox" 
                                        :value="doOrder.id" 
                                        v-model="selectedIds"
                                        :disabled="!isEligible(doOrder)"
                                        :title="!isEligible(doOrder) ? 'Only completed & uninvoiced DOs can be selected' : ''"
                                        @click.stop
                                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed"
                                    >
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 dark:bg-slate-800">
                                        <TruckIcon class="h-5 w-5 text-slate-500 dark:text-slate-400" />
                                    </div>
                                    <div>
                                        <Link :href="route('sales.deliveries.show', doOrder.id)" class="text-sm font-medium text-slate-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 hover:underline">
                                            {{ doOrder.do_number }}
                                        </Link>
                                        <div class="text-xs text-slate-500">{{ doOrder.warehouse?.name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm">
                                <Link :href="route('sales.orders.show', doOrder.sales_order_id)" class="font-medium text-blue-600 dark:text-blue-400 hover:underline">
                                    {{ doOrder.sales_order?.so_number }}
                                </Link>
                                <div v-if="doOrder.sales_order?.customer_po_number" class="text-xs text-slate-500">
                                    PO: {{ doOrder.sales_order?.customer_po_number }}
                                </div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm text-slate-600 dark:text-slate-300">
                                {{ doOrder.vehicle_number || '-' }}
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <div class="text-sm text-slate-900 dark:text-white font-medium">{{ doOrder.shipping_name || doOrder.sales_order?.customer?.name }}</div>
                                <div v-if="doOrder.shipping_name && doOrder.shipping_name !== doOrder.sales_order?.customer?.name" class="text-[10px] text-slate-500 uppercase tracking-tighter">Member: {{ doOrder.sales_order?.customer?.name }}</div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <span class="text-sm text-slate-600 dark:text-slate-300">{{ formatDate(doOrder.delivery_date) }}</span>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-center text-sm font-bold text-slate-700 dark:text-slate-300">
                                {{ doOrder.total_qty ? Number(doOrder.total_qty).toLocaleString('id-ID') : '-' }}
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-center text-sm text-slate-600 dark:text-slate-300">
                                {{ doOrder.items_count }}
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-center">
                                <div class="flex flex-col items-center gap-1">
                                    <span 
                                        class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium cursor-help"
                                        :class="getInvoiceStatusBadge(doOrder.invoice_status)"
                                        :title="getInvoiceStatusDescription(doOrder.invoice_status)"
                                    >
                                        {{ doOrder.invoice_status }}
                                    </span>
                                    <span 
                                        class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium cursor-help"
                                        :class="getStatusBadge(doOrder.status)"
                                        :title="getStatusDescription(doOrder.status)"
                                    >
                                        {{ doOrder.status }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <div class="relative group">
                                        <button class="flex items-center p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors">
                                            <PrinterIcon class="h-4 w-4" />
                                        </button>
                                        <div class="absolute right-0 top-full pt-1 w-40 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-[100]">
                                            <div class="rounded-xl bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 shadow-lg p-1">
                                                <a :href="route('sales.deliveries.print', doOrder.id) + '?format=a4'" target="_blank" class="block w-full text-left px-3 py-2 rounded-lg text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50 hover:text-blue-600 dark:hover:text-blue-400 font-medium transition-colors">
                                                    A4 (Standard)
                                                </a>
                                                <a :href="route('sales.deliveries.print', doOrder.id) + '?format=continuous'" target="_blank" class="block w-full text-left px-3 py-2 rounded-lg text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50 hover:text-blue-600 dark:hover:text-blue-400 font-medium transition-colors">
                                                    Continuous
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <Link :href="route('sales.deliveries.show', doOrder.id)" class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors">
                                        <EyeIcon class="h-4 w-4" />
                                    </Link>
                                    <button 
                                        v-if="doOrder.status === 'draft' || doOrder.items_count === 0"
                                        @click="deleteDelivery(doOrder.id)" 
                                        class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-red-500 hover:bg-red-500/10 transition-colors"
                                    >
                                        <TrashIcon class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="deliveryOrders.data.length === 0">
                            <td colspan="7" class="px-4 py-12 text-center">
                                <TruckIcon class="mx-auto h-12 w-12 text-slate-600" />
                                <h3 class="mt-2 text-sm font-medium text-slate-600 dark:text-slate-300">No delivery orders found</h3>
                                <p class="mt-1 text-sm text-slate-500">Deliveries are created from Sales Orders.</p>
                                <div class="mt-6">
                                    <Link :href="route('sales.orders.index')" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-medium text-white dark:text-white hover:bg-blue-500 transition-colors shadow-lg shadow-blue-500/20">
                                        Go to Sales Orders
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="deliveryOrders.last_page > 1" class="border-t border-slate-200 dark:border-slate-800 px-6 py-4 flex items-center justify-between">
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Showing {{ deliveryOrders.from }} to {{ deliveryOrders.to }} of {{ deliveryOrders.total }} deliveries
                </p>
                <div class="flex items-center gap-2">
                    <Link
                        v-for="link in deliveryOrders.links"
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

        <!-- Feature Guide -->
        <div class="mt-12">
            <div class="flex items-center gap-2 mb-4 px-1">
                <div class="h-px flex-1 bg-slate-50 dark:bg-slate-800"></div>
                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Logistics & Delivery Guide</span>
                <div class="h-px flex-1 bg-slate-50 dark:bg-slate-800"></div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-purple-500/10 text-purple-400">
                            <TruckIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Surat Jalan (DO)</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Every delivery generates a professional <strong>Surat Jalan</strong>. Use the Print icon to generate the document for the driver.
                    </p>
                </div>

                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-blue-500/10 text-blue-400">
                            <InboxStackIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Inventory Sync</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Stock is only deducted from the warehouse once the DO is marked as <strong>Delivered</strong>, ensuring real-time inventory accuracy.
                    </p>
                </div>

                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-400">
                            <DocumentCheckIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Direct Invoicing</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Once a delivery is complete, you can generate a <strong>Sales Invoice</strong> directly from the DO to ensure billing matches what was shipped.
                    </p>
                </div>
                
                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-amber-500/10 text-amber-400">
                            <ShieldCheckIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Proof of Delivery</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Maintain <strong>Accountability</strong> by tracking vehicle numbers and the personnel who prepared each shipment for delivery.
                    </p>
                </div>
            </div>
        </div>
        <!-- Batch Invoice Preview Modal -->
        <Transition
            enter-active-class="ease-out duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="isPreviewModalOpen" class="fixed inset-0 z-[100] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm transition-opacity" @click="isPreviewModalOpen = false"></div>

                    <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

                    <div class="relative inline-block transform overflow-hidden rounded-3xl bg-white dark:bg-slate-900 text-left align-bottom shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-4xl sm:align-middle border border-slate-200 dark:border-slate-800">
                        <div class="absolute top-0 right-0 pt-6 pr-6">
                            <button @click="isPreviewModalOpen = false" class="rounded-xl bg-slate-100 dark:bg-slate-800 p-2 text-slate-400 hover:text-slate-500 focus:outline-none transition-all">
                                <XMarkIcon class="h-5 w-5" />
                            </button>
                        </div>

                        <div class="p-8">
                            <div class="flex items-center gap-4 mb-8">
                                <div class="p-3 bg-indigo-500/10 rounded-2xl">
                                    <DocumentCheckIcon class="h-8 w-8 text-indigo-500" />
                                </div>
                                <div>
                                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Batch Invoice Preview</h3>
                                    <p class="text-slate-500 dark:text-slate-400">Review results before processing {{ previewData?.total_dos }} Delivery Orders</p>
                                </div>
                            </div>

                            <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-200 dark:border-slate-700">
                                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Total Invoices to Generate</p>
                                    <p class="text-2xl font-bold text-indigo-500">{{ finalInvoiceCount }} Invoices</p>
                                </div>
                                <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-200 dark:border-slate-700">
                                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Total Delivery Orders</p>
                                    <p class="text-2xl font-bold text-blue-500">{{ previewData?.total_dos }} DOs</p>
                                </div>
                            </div>

                            <div class="max-h-[400px] overflow-y-auto mb-8 pr-2 custom-scrollbar">
                                <div v-for="(inv, idx) in previewData?.preview" :key="idx" 
                                    class="mb-4 last:mb-0 p-5 rounded-2xl border transition-all shadow-sm flex items-start gap-4"
                                    :class="excludedSoIds.includes(inv.so_id) ? 'bg-slate-50 dark:bg-slate-800/30 border-slate-100 dark:border-slate-800 opacity-60' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 hover:border-indigo-500/30'"
                                >
                                    <div class="pt-1">
                                        <input 
                                            type="checkbox" 
                                            :checked="!excludedSoIds.includes(inv.so_id)"
                                            @change="toggleInvoiceSelection(inv.so_id)"
                                            class="h-5 w-5 rounded-lg border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                        />
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex flex-wrap justify-between items-start gap-4 mb-4">
                                            <div>
                                                <p class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                                    <UserIcon class="h-4 w-4 text-slate-400" />
                                                    {{ inv.customer_name }}
                                                </p>
                                                <p class="text-xs text-slate-500 mt-1">SO: {{ inv.so_number }} | {{ inv.do_count }} DOs ({{ inv.do_numbers }})</p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-sm font-bold text-indigo-500">{{ formatCurrency(inv.total_amount) }}</p>
                                                <p class="text-xs text-slate-500 mt-1">Total Qty: {{ inv.total_qty }}</p>
                                            </div>
                                        </div>
                                        
                                        <div class="space-y-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                                            <div v-for="(item, iidx) in inv.items" :key="iidx" class="flex justify-between text-xs">
                                                <span class="text-slate-600 dark:text-slate-400 font-medium">{{ item.product_name }}</span>
                                                <span class="font-bold text-slate-700 dark:text-slate-300">{{ item.qty }} {{ item.unit_name }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-100 dark:border-slate-800">
                                <button
                                    @click="isPreviewModalOpen = false"
                                    class="px-6 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all"
                                >
                                    Cancel
                                </button>
                                <button
                                    @click="confirmBulkInvoice"
                                    :disabled="finalInvoiceCount === 0"
                                    class="px-8 py-2.5 rounded-xl bg-indigo-600 text-sm font-bold text-white shadow-lg shadow-indigo-500/25 hover:bg-indigo-500 transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <DocumentCheckIcon class="h-5 w-5" />
                                    Confirm & Process ({{ finalInvoiceCount }})
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </AppLayout>

    <!-- Import Modal -->
    <Transition
        enter-active-class="ease-out duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100"
        leave-active-class="ease-in duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0"
    >
        <div v-if="showImportModal" class="fixed inset-0 z-[100] overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm" @click="showImportModal = false"></div>
                <div class="relative w-full max-w-md rounded-2xl bg-white dark:bg-slate-900 p-6 border border-slate-200 dark:border-slate-800 shadow-2xl">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Import Delivery Orders</h3>
                    <form @submit.prevent="submitImport" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Excel File (.xlsx)</label>
                            <input type="file" @change="onFileChange" accept=".xlsx,.xls,.csv" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-slate-800 dark:file:text-blue-400" required />
                        </div>
                        <div class="text-xs text-slate-500 dark:text-slate-400">
                            <a :href="route('sales.deliveries.template')" class="text-blue-400 hover:underline">Download template</a> for the correct format.
                        </div>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="showImportModal = false" class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-sm font-semibold text-slate-600 dark:text-slate-300">Cancel</button>
                            <button type="submit" :disabled="importForm.processing" class="px-4 py-2 rounded-xl bg-blue-600 text-sm font-semibold text-white hover:bg-blue-500 disabled:opacity-50">
                                {{ importForm.processing ? 'Importing...' : 'Import' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </Transition>
</template>



