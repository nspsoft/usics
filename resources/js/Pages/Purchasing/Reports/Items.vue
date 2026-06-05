<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    MagnifyingGlassIcon,
    FunnelIcon,
    ArrowDownTrayIcon,
    ChevronUpIcon,
    ChevronDownIcon,
    EyeIcon,
} from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';
import { formatNumber, formatCurrency } from '@/helpers';

const props = defineProps({
    items: Object,
    suppliers: Array,
    filters: Object,
    statuses: Array,
});

const search = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || '');
const selectedSupplier = ref(props.filters.supplier || '');
const dateFrom = ref(props.filters.date_range?.[0] || '');
const dateTo = ref(props.filters.date_range?.[1] || '');
const sortField = ref(props.filters.sort || 'purchase_orders.order_date');
const sortDirection = ref(props.filters.direction || 'desc');
const showFilters = ref(false);
const showDetailModal = ref(false);
const selectedItem = ref(null);

const openDetails = (item) => {
    selectedItem.value = item;
    showDetailModal.value = true;
};

const applyFilters = debounce(() => {
    router.get('/purchasing/orders/items', {
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

const sort = (field) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }
    applyFilters();
};

watch([search, selectedStatus, selectedSupplier, dateFrom, dateTo], applyFilters);

const clearFilters = () => {
    search.value = '';
    selectedStatus.value = '';
    selectedSupplier.value = '';
    dateFrom.value = '';
    dateTo.value = '';
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

const getStatusBadge = (status) => {
    const badges = {
        draft: 'bg-slate-500/10 text-slate-500 border-slate-500/20',
        submitted: 'bg-indigo-500/10 text-indigo-500 border-indigo-500/20',
        approved: 'bg-teal-500/10 text-teal-500 border-teal-500/20',
        confirmed: 'bg-blue-500/10 text-blue-500 border-blue-500/20',
        ordered: 'bg-amber-500/10 text-amber-500 border-amber-500/20',
        partial: 'bg-orange-500/10 text-orange-500 border-orange-500/20',
        received: 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20',
        completed: 'bg-green-500/10 text-green-500 border-green-500/20',
        cancelled: 'bg-red-500/10 text-red-500 border-red-500/20',
    };
    return badges[status] || 'bg-slate-500/10 text-slate-500 border-slate-500/20';
};

const exportUrl = computed(() => {
    const params = new URLSearchParams();

    if (search.value) params.set('search', search.value);
    if (selectedStatus.value) params.set('status', selectedStatus.value);
    if (selectedSupplier.value) params.set('supplier', selectedSupplier.value);
    if (dateFrom.value && dateTo.value) {
        params.set('date_range[0]', dateFrom.value);
        params.set('date_range[1]', dateTo.value);
    }

    if (sortField.value) params.set('sort', sortField.value);
    if (sortDirection.value) params.set('direction', sortDirection.value);

    const qs = params.toString();
    return qs ? `/purchasing/orders/items/export?${qs}` : '/purchasing/orders/items/export';
});
</script>

<template>
    <Head title="PO Items Report" />

    <AppLayout title="PO Items Report">
        <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 mb-6">
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative w-full sm:w-64">
                    <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500" />
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search Product, PO, Supplier..."
                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-900/50 py-2 pl-10 pr-4 text-sm text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50 transition-all"
                    />
                </div>
                <button 
                    @click="showFilters = !showFilters"
                    class="flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-900/50 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors border border-transparent"
                    :class="{ 'ring-2 ring-blue-500/50 border-blue-500/50': showFilters }"
                >
                    <FunnelIcon class="h-5 w-5" />
                    Filters
                </button>
            </div>
            
            <div class="flex items-center gap-2 shrink-0">
                <a
                    :href="exportUrl"
                    class="hidden md:inline-flex items-center gap-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-4 py-2 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-700 transition-all group"
                >
                    <ArrowDownTrayIcon class="h-5 w-5 text-emerald-500 group-hover:scale-110 transition-transform" />
                    Export Excel
                </a>
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
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead>
                        <tr>
                            <th @click="sort('purchase_orders.po_number')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-6 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors">
                                <div class="flex items-center gap-1">
                                    PO Number
                                    <span v-if="sortField === 'purchase_orders.po_number'">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th @click="sort('purchase_orders.order_date')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-6 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors">
                                <div class="flex items-center gap-1">
                                    Date
                                    <span v-if="sortField === 'purchase_orders.order_date'">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th @click="sort('supplier_name')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-6 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors">
                                <div class="flex items-center gap-1">
                                    Supplier
                                    <span v-if="sortField === 'supplier_name'">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th @click="sort('product_name')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-6 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors">
                                <div class="flex items-center gap-1">
                                    Product
                                    <span v-if="sortField === 'product_name'">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-6 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">Ordered</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-6 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">Received</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-6 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">Returned</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-6 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">Balance</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-6 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">Status</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-6 py-3 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900/50">
                        <tr v-for="item in items.data" :key="item.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <Link :href="`/purchasing/orders/${item.purchase_order_id}`" class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline">
                                    {{ item.purchase_order?.po_number }}
                                </Link>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-300">
                                {{ formatDate(item.purchase_order?.order_date) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-300">
                                <div class="font-medium text-slate-900 dark:text-white">{{ item.purchase_order?.supplier?.name }}</div>
                                <div class="text-xs text-slate-500">{{ item.purchase_order?.supplier?.code }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-slate-900 dark:text-white">{{ item.product?.name }}</div>
                                <div class="text-xs text-slate-500 font-mono">{{ item.product?.code || item.product?.sku }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-bold text-slate-900 dark:text-white">
                                {{ formatNumber(item.qty) }} {{ item.unit?.name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium text-emerald-600">
                                {{ formatNumber(item.qty_received) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium text-rose-600">
                                {{ formatNumber(item.qty_returned) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-bold text-amber-600">
                                {{ formatNumber(item.remaining_qty) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span 
                                    class="inline-flex px-2 py-1 text-xs font-bold rounded-full border"
                                    :class="getStatusBadge(item.purchase_order?.status)"
                                >
                                    {{ item.purchase_order?.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                <button 
                                    @click="openDetails(item)"
                                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                    title="View Details"
                                >
                                    <EyeIcon class="w-5 h-5" />
                                </button>
                            </td>
                        </tr>
                        <tr v-if="items.data.length === 0">
                            <td colspan="10" class="px-6 py-12 text-center text-slate-500">
                                No items found matching your criteria.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="border-t border-slate-200 dark:border-slate-700 px-6 py-4">
                <Pagination :links="items.links" />
            </div>
        </div>

        <!-- PO Items Operations Guide -->
        <div class="mt-8 relative hidden md:block">
            <div class="absolute inset-0 flex items-center" aria-hidden="true">
                <div class="w-full border-t border-slate-200/60 dark:border-slate-700/60"></div>
            </div>
            <div class="relative flex justify-center">
                <span class="bg-[#F8FAFC] dark:bg-[#0F172A] px-4 text-[10px] font-black tracking-[0.2em] text-slate-400 dark:text-slate-500 uppercase">
                    PO Items Operations Guide
                </span>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4 hidden md:grid mb-8">
            <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                <div class="flex items-center gap-3 mb-3">
                    <div class="p-2.5 rounded-xl bg-blue-500/10 text-blue-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="16" y1="4" x2="16" y2="20"></line><line x1="8" y1="4" x2="8" y2="20"></line><line x1="3" y1="4" x2="21" y2="4"></line><line x1="3" y1="20" x2="21" y2="20"></line>
                        </svg>
                    </div>
                    <h4 class="font-bold text-slate-200 text-sm">Fulfillment Status</h4>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    Instantly monitor whether an item’s <strong>Ordered</strong> quantity matches the <strong>Received</strong> count. The <strong>Balance</strong> indicator highlights outstanding stocks.
                </p>
            </div>

            <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                <div class="flex items-center gap-3 mb-3">
                    <div class="p-2.5 rounded-xl bg-rose-500/10 text-rose-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                    </div>
                    <h4 class="font-bold text-slate-200 text-sm">Return Tracking</h4>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    Spot discrepancies by observing the <strong>Returned</strong> column to see if any portions of an ordered item were rejected back to the supplier.
                </p>
            </div>

            <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                <div class="flex items-center gap-3 mb-3">
                    <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-400">
                        <EyeIcon class="h-5 w-5" />
                    </div>
                    <h4 class="font-bold text-slate-200 text-sm">Detailed Overview</h4>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    Click the <strong>Eye Icon</strong> under Actions to view deep unit pricing, total subtotal valuation, and the target warehouse for that specific SKU.
                </p>
            </div>
            
            <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                <div class="flex items-center gap-3 mb-3">
                    <div class="p-2.5 rounded-xl bg-indigo-500/10 text-indigo-400">
                        <FunnelIcon class="h-5 w-5" />
                    </div>
                    <h4 class="font-bold text-slate-200 text-sm">Targeted Filtering</h4>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    Use the robust <strong>Filters</strong> to narrow down records by specific statuses (e.g. partial, received) or specific supplier delivery dates.
                </p>
            </div>
        </div>

        <!-- Details Modal -->
        <Modal :show="showDetailModal" @close="showDetailModal = false" maxWidth="2xl">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                        Item Details
                    </h3>
                    <button @click="showDetailModal = false" class="text-slate-400 hover:text-slate-500">
                        <span class="sr-only">Close</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div v-if="selectedItem" class="space-y-6">
                    <!-- Item Info -->
                    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-4">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <div class="text-slate-500">Product</div>
                                <div class="font-medium text-slate-900 dark:text-white">{{ selectedItem.product?.name }}</div>
                                <div class="text-xs text-slate-500">{{ selectedItem.product?.code || selectedItem.product?.sku }}</div>
                            </div>
                            <div>
                                <div class="text-slate-500">PO Ref</div>
                                <div class="font-medium text-blue-600">{{ selectedItem.purchase_order?.po_number }}</div>
                            </div>
                            <div>
                                <div class="text-slate-500">Supplier</div>
                                <div class="font-medium text-slate-900 dark:text-white">{{ selectedItem.purchase_order?.supplier?.name }}</div>
                            </div>
                            <div>
                                <div class="text-slate-500">Warehouse</div>
                                <div class="font-medium text-slate-900 dark:text-white">{{ selectedItem.purchase_order?.warehouse?.name }}</div>
                            </div>
                            <div>
                                <div class="text-slate-500">Ordered Qty</div>
                                <div class="font-bold text-slate-900 dark:text-white">{{ formatNumber(selectedItem.qty) }} {{ selectedItem.unit?.name }}</div>
                            </div>
                            <div>
                                <div class="text-slate-500">Received</div>
                                <div class="font-bold text-emerald-600">{{ formatNumber(selectedItem.qty_received) }} {{ selectedItem.unit?.name }}</div>
                            </div>
                            <div>
                                <div class="text-slate-500">Returned</div>
                                <div class="font-bold text-rose-600">{{ formatNumber(selectedItem.qty_returned) }} {{ selectedItem.unit?.name }}</div>
                            </div>
                            <div>
                                <div class="text-slate-500">Outstanding Balance</div>
                                <div class="font-bold text-amber-600">{{ formatNumber(selectedItem.remaining_qty) }} {{ selectedItem.unit?.name }}</div>
                            </div>
                            <div>
                                <div class="text-slate-500">Unit Price</div>
                                <div class="font-bold text-slate-900 dark:text-white">{{ formatCurrency(selectedItem.unit_price) }}</div>
                            </div>
                            <div>
                                <div class="text-slate-500">Subtotal</div>
                                <div class="font-bold text-slate-900 dark:text-white">{{ formatCurrency(selectedItem.subtotal) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-6 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 flex justify-end">
                <button 
                    @click="showDetailModal = false"
                    class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-xl text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700"
                >
                    Close
                </button>
            </div>
        </Modal>
    </AppLayout>
</template>
