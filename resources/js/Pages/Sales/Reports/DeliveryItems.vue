<script setup>
import { ref, watch, computed } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    MagnifyingGlassIcon, 
    FunnelIcon, 
    ArrowDownTrayIcon,
    ChevronUpIcon,
    ChevronDownIcon,
    EyeIcon,
    CheckCircleIcon
} from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';
import { formatNumber } from '@/helpers';

const props = defineProps({
    items: Object,
    customers: Array,
    filters: Object,
    statuses: Array
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const customer = ref(props.filters.customer || '');
const sort = ref(props.filters.sort || 'delivery_orders.delivery_date');
const direction = ref(props.filters.direction || 'desc');
const showFilters = ref(false);

const updateFilters = debounce(() => {
    router.get(route('sales.deliveries.items'), {
        search: search.value || undefined,
        status: status.value || undefined,
        customer: customer.value || undefined,
        sort: sort.value,
        direction: direction.value,
    }, {
        preserveState: true,
        replace: true
    });
}, 300);

const sortBy = (column) => {
    if (sort.value === column) {
        direction.value = direction.value === 'asc' ? 'desc' : 'asc';
    } else {
        sort.value = column;
        direction.value = 'asc';
    }
    updateFilters();
};

watch([search, status, customer], updateFilters);

const clearFilters = () => {
    search.value = '';
    status.value = '';
    customer.value = '';
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

const getStatusClass = (status) => {
    const classes = {
        draft: 'bg-slate-500/10 text-slate-500 border-slate-500/20',
        picking: 'bg-blue-500/10 text-blue-500 border-blue-500/20',
        packed: 'bg-indigo-500/10 text-indigo-500 border-indigo-500/20',
        shipped: 'bg-purple-500/10 text-purple-500 border-purple-500/20',
        delivered: 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20',
        cancelled: 'bg-red-500/10 text-red-500 border-red-500/20'
    };
    return classes[status] || 'bg-slate-500/10 text-slate-500 border-slate-500/20';
};
</script>

<template>
    <Head title="DO Items Report" />
    <AppLayout title="DO Items Report">
        <!-- Header Actions -->
        <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 mb-6">
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative w-full sm:w-64">
                    <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500" />
                    <input 
                        v-model="search"
                        type="search" 
                        placeholder="Search DO, PO, Product..." 
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

            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <a 
                    :href="route('sales.deliveries.items.export', filters)"
                    class="inline-flex items-center gap-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-4 py-2 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-700 transition-all group"
                >
                    <ArrowDownTrayIcon class="h-5 w-5 text-emerald-500 group-hover:scale-110 transition-transform" />
                    Export Excel
                </a>
            </div>
        </div>

        <!-- Expanded Filters -->
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
                        <select v-model="status" class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50">
                            <option value="">All Status</option>
                            <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Customer</label>
                        <select v-model="customer" class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50">
                            <option value="">All Customers</option>
                            <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
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

        <!-- Table -->
        <div class="rounded-2xl glass-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-800/50">
                        <tr>
                            <th @click="sortBy('delivery_orders.do_number')" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider cursor-pointer hover:text-slate-700">
                                <div class="flex items-center gap-1">
                                    DO Number
                                    <template v-if="sort === 'delivery_orders.do_number'">
                                        <ChevronUpIcon v-if="direction === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </template>
                                </div>
                            </th>
                            <th @click="sortBy('delivery_orders.delivery_date')" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider cursor-pointer hover:text-slate-700">
                                <div class="flex items-center gap-1">
                                    Date
                                    <template v-if="sort === 'delivery_orders.delivery_date'">
                                        <ChevronUpIcon v-if="direction === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </template>
                                </div>
                            </th>
                            <th @click="sortBy('customer_name')" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider cursor-pointer hover:text-slate-700">
                                <div class="flex items-center gap-1">
                                    Customer
                                    <template v-if="sort === 'customer_name'">
                                        <ChevronUpIcon v-if="direction === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </template>
                                </div>
                            </th>
                            <th @click="sortBy('product_name')" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider cursor-pointer hover:text-slate-700">
                                <div class="flex items-center gap-1">
                                    Product
                                    <template v-if="sort === 'product_name'">
                                        <ChevronUpIcon v-if="direction === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </template>
                                </div>
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">Qty DO</th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">Qty Actual</th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">Delay / Balance</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Notes / Problem</th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Loaded</th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900/50">
                        <tr v-for="item in items.data" :key="item.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <Link :href="route('sales.deliveries.show', item.delivery_order_id)" class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline">
                                    {{ item.delivery_order?.do_number }}
                                </Link>
                                <div class="text-xs text-slate-500">{{ item.delivery_order?.customer_po_number || '-' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-300">
                                {{ formatDate(item.delivery_order?.delivery_date) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-300">
                                <div class="font-medium text-slate-900 dark:text-white">{{ item.delivery_order?.customer?.name }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-slate-900 dark:text-white">{{ item.product?.name }}</div>
                                <div class="text-xs text-slate-500 font-mono">{{ item.product?.sku }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium text-slate-500">
                                {{ formatNumber(item.qty_ordered) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-bold text-slate-900 dark:text-white">
                                {{ formatNumber(item.qty_delivered) }} {{ item.unit?.code }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-bold" :class="(item.qty_delivered - item.qty_ordered) < 0 ? 'text-rose-500' : 'text-slate-500'">
                                {{ formatNumber(item.qty_delivered - item.qty_ordered) }}
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-500">
                                <div class="max-w-xs truncate" :title="item.notes">{{ item.notes || '-' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <CheckCircleIcon 
                                    v-if="item.is_loaded" 
                                    class="h-5 w-5 text-emerald-500 mx-auto" 
                                />
                                <span v-else class="text-slate-300 dark:text-slate-700">—</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="inline-flex px-2 py-1 text-xs font-bold rounded-full border" :class="getStatusClass(item.delivery_order?.status)">
                                    {{ item.delivery_order?.status.toUpperCase() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                <Link :href="route('sales.deliveries.show', item.delivery_order_id)" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                    <EyeIcon class="w-5 h-5" />
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="items.data.length === 0">
                            <td colspan="8" class="px-6 py-12 text-center text-slate-500">
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
    </AppLayout>
</template>
