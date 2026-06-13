<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    MagnifyingGlassIcon,
    FunnelIcon,
    CubeIcon,
    QuestionMarkCircleIcon,
    ExclamationTriangleIcon,
    ShoppingCartIcon,
    ChevronUpIcon,
    ChevronDownIcon,
    ArrowDownTrayIcon,
} from '@heroicons/vue/24/outline';
import Pagination from '@/Components/Pagination.vue';
import debounce from 'lodash/debounce';
import { formatNumber, formatCurrency } from '@/helpers';

const props = defineProps({
    stocks: Object,
    warehouses: Array,
    categories: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');
const selectedWarehouse = ref(props.filters.warehouse_id || '');
const selectedCategory = ref(props.filters.category || '');
const selectedAction = ref(props.filters.action || '');
const sortField = ref(props.filters.sort || 'product_name');
const sortDirection = ref(props.filters.direction || 'asc');
const showFilters = ref(false);

const applyFilters = debounce(() => {
    router.get('/inventory/stocks', {
        search: search.value || undefined,
        warehouse_id: selectedWarehouse.value || undefined,
        category: selectedCategory.value || undefined,
        action: selectedAction.value || undefined,
        sort: sortField.value,
        direction: sortDirection.value,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, selectedWarehouse, selectedCategory, selectedAction], applyFilters);

const sort = (field) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }
    applyFilters();
};

const clearFilters = () => {
    search.value = '';
    selectedWarehouse.value = '';
    selectedCategory.value = '';
    selectedAction.value = '';
    sortField.value = 'product_name';
    sortDirection.value = 'asc';
    applyFilters();
};


const getProductTypeBadge = (type) => {
    const badges = {
        raw_material: 'bg-amber-500/10 text-amber-400 ring-amber-500/20',
        wip: 'bg-blue-500/10 text-blue-400 ring-blue-500/20',
        finished_good: 'bg-emerald-500/10 text-emerald-400 ring-emerald-500/20',
        spare_part: 'bg-purple-500/10 text-purple-400 ring-purple-500/20',
    };
    return badges[type] || 'bg-slate-500/10 text-slate-500 dark:text-slate-400 ring-slate-500/20';
};

const getProductTypeLabel = (type) => {
    const labels = {
        raw_material: 'Raw Material',
        wip: 'WIP',
        finished_good: 'Finished Good',
        spare_part: 'Spare Part',
    };
    return labels[type] || type;
};

const getStockStatus = (stock) => {
    const product = stock.product;
    if (!product) return { label: 'Error', class: 'bg-gray-500', textClass: 'text-gray-500', isSafe: true, reorderQty: 0 };

    const onHand = parseFloat(stock.qty_on_hand || 0);
    const reserved = parseFloat(stock.qty_reserved || 0);
    const onOrder = parseFloat(stock.on_order_qty || 0);
    const available = onHand - reserved;
    const projected = available + onOrder;
    
    const minStock = parseFloat(product.min_stock || 0);
    const maxStock = parseFloat(product.max_stock || 0);
    const reorderPoint = parseFloat(product.reorder_point || 0);
    const reorderQty = parseFloat(product.reorder_qty || 0);

    // Calculate Target Reorder Amount based on policy
    // Reorder Calculation should consider Incoming Stock (On Order) to prevent double ordering.
    
    const getReorderSuggestion = () => {
        let suggestion = 0;

        // Priority 1: Use Standard Reorder Qty if defined
        if (reorderQty > 0) {
            suggestion = reorderQty;
        } else if (maxStock > 0) {
            // Priority 2: Fill to Max
            suggestion = maxStock - projected;
        } else {
            // Priority 3: Fallback (2x Min Stock)
            suggestion = (minStock * 2) - projected;
        }

        // Final Safety Check: Never exceed Max Stock if Max Stock is defined
        if (maxStock > 0) {
            const spaceAvailable = maxStock - projected;
            // Use the smaller of suggestion or spaceAvailable, ensuring we don't go negative
            suggestion = Math.min(suggestion, spaceAvailable);
        }

        return Math.max(0, suggestion); // Ensure non-negative
    };

    // Critical: Available < 0 (Negative Stock) OR (Available <= Min Stock AND Min Stock > 0)
    if (available < 0 || (minStock > 0 && available <= minStock)) {
        const qtyToOrder = getReorderSuggestion();
        // If suggestion is 0 but we have negative stock, suggest at least enough to cover the deficit
        const finalQty = (available < 0 && qtyToOrder === 0) ? Math.abs(available) : qtyToOrder;
        
        return {
            label: `URGENT: Reorder ${formatNumber(finalQty)}`,
            class: 'bg-red-500/10 text-red-500 ring-red-500/20 hover:bg-red-500 hover:text-slate-900 dark:text-white cursor-pointer',
            textClass: 'text-red-500 font-bold',
            isCritical: true,
            reorderQty: finalQty
        };
    }

    // Warning: Available <= Reorder Point (Only if reorder point > 0 and we haven't hit URGENT yet)
    if (reorderPoint > 0 && available <= reorderPoint) {
        const qtyToOrder = getReorderSuggestion();
        return {
            label: `Reorder ${formatNumber(qtyToOrder)}`,
            class: 'bg-amber-500/10 text-amber-500 ring-amber-500/20 hover:bg-amber-500 hover:text-slate-900 dark:text-white cursor-pointer',
            textClass: 'text-amber-500 font-bold',
            isLow: true,
            reorderQty: qtyToOrder
        };
    }

    // Safe
    return {
        label: 'Stock OK',
        class: 'bg-emerald-500/10 text-emerald-500 ring-emerald-500/20',
        textClass: 'text-emerald-500 font-medium',
        isSafe: true,
        reorderQty: 0
    };
};

const selected = ref([]);

const toggleSelection = (stock) => {
    const index = selected.value.findIndex(item => item.id === stock.id);
    if (index === -1) {
        selected.value.push(stock);
    } else {
        selected.value.splice(index, 1);
    }
};

const toggleAll = () => {
    if (selected.value.length === props.stocks.data.length) {
        selected.value = [];
    } else {
        selected.value = [...props.stocks.data];
    }
};

const bulkReorder = () => {
    if (selected.value.length === 0) return;
    
    // Build Query String
    const params = new URLSearchParams();
    selected.value.forEach(stock => {
        const qty = getStockStatus(stock).reorderQty > 0 ? getStockStatus(stock).reorderQty : 1;
        params.append('products[]', stock.product.id);
        params.append('qtys[]', qty);
    });
    
    router.get(`/purchasing/requests/create?${params.toString()}`);
};

const exportUrl = computed(() => {
    return route('inventory.stocks.export', {
        search: search.value || undefined,
        warehouse_id: selectedWarehouse.value || undefined,
        category: selectedCategory.value || undefined,
        action: selectedAction.value || undefined,
        sort: sortField.value,
        direction: sortDirection.value,
    });
});

</script>

<template>
    <Head title="Current Stock" />

    <AppLayout title="Current Stock">
        <div class="space-y-6">
            <!-- Header & Actions -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3 flex-1">
                    <div class="relative max-w-sm w-full">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <MagnifyingGlassIcon class="h-5 w-5 text-slate-500 dark:text-slate-400" />
                        </div>
                        <input
                            v-model="search"
                            type="text"
                            class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 pl-10 pr-4 text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50"
                            placeholder="Search SKU or Product Name..."
                        />
                    </div>
                    <button 
                        @click="showFilters = !showFilters"
                        class="flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-800 px-4 py-2.5 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-700 transition-colors"
                        :class="{ 'ring-2 ring-blue-500/50': showFilters }"
                    >
                        <FunnelIcon class="h-5 w-5" />
                        Filters
                    </button>
                </div>
                
                <div class="flex items-center gap-3">
                    <a
                        :href="exportUrl"
                        target="_blank"
                        class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-500 shadow-lg shadow-blue-900/25 transition-all"
                    >
                        <ArrowDownTrayIcon class="h-5 w-5" />
                        Export Stock
                    </a>
                </div>
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
                <div v-if="showFilters" class="rounded-2xl glass-card p-4">
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Warehouse</label>
                            <select
                                v-model="selectedWarehouse"
                                class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                            >
                                <option value="">All Warehouses</option>
                                <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">
                                    {{ wh.name }} {{ (wh.type && wh.type !== 'warehouse') ? `(${(wh.type.charAt(0).toUpperCase() + wh.type.slice(1))})` : '' }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Category</label>
                            <select
                                v-model="selectedCategory"
                                class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                            >
                                <option value="">All Categories</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                    {{ cat.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Action</label>
                            <select
                                v-model="selectedAction"
                                class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                            >
                                <option value="">All</option>
                                <option value="urgent">Urgent</option>
                                <option value="reorder">Reorder</option>
                                <option value="ok">Stock OK</option>
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

            <!-- Stocks List -->
            <div class="rounded-2xl glass-card overflow-hidden">
                <div class="overflow-x-auto overflow-y-auto max-h-[600px]">
                    <table class="w-full text-left text-sm text-slate-500 dark:text-slate-400">
                        <thead class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm text-slate-900 dark:text-slate-200 font-medium border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm pl-6 pr-3 py-4 w-10">
                                    <input 
                                        type="checkbox" 
                                        :checked="selected.length === stocks.data.length && stocks.data.length > 0"
                                        @change="toggleAll"
                                        class="rounded border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-blue-600 focus:ring-blue-500/50"
                                    />
                                </th>
                                <th 
                                    @click="sort('product_name')"
                                    class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group"
                                >
                                    <div class="flex items-center gap-2">
                                        Product Info
                                        <span v-if="sortField === 'product_name'" class="text-blue-500">
                                            <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                            <ChevronDownIcon v-else class="h-3 w-3" />
                                        </span>
                                        <span v-else class="text-slate-500 dark:text-slate-600 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <ChevronUpIcon class="h-3 w-3" />
                                        </span>
                                    </div>
                                </th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3">Category</th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3">Type</th>
                                <th 
                                    @click="sort('warehouse_name')"
                                    class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group"
                                >
                                    <div class="flex items-center gap-2">
                                        Warehouse
                                        <span v-if="sortField === 'warehouse_name'" class="text-blue-500">
                                            <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                            <ChevronDownIcon v-else class="h-3 w-3" />
                                        </span>
                                        <span v-else class="text-slate-500 dark:text-slate-600 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <ChevronUpIcon class="h-3 w-3" />
                                        </span>
                                    </div>
                                </th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-right">Levels (Min / Reorder / Max)</th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        Action / Recommendation
                                        <div class="group relative cursor-help">
                                            <QuestionMarkCircleIcon class="h-4 w-4 text-slate-500 hover:text-blue-400 transition-colors" />
                                            <div class="absolute top-full right-0 mt-2 w-64 p-3 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-xl opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50 text-left">
                                                <p class="text-xs text-slate-900 dark:text-white">
                                                    <span class="font-bold block mb-1">Rekomendasi Tindakan</span>
                                                    Sistem memberikan saran berdasarkan level stok saat ini.
                                                    <br><br>
                                                    • <b>URGENT</b>: Stok di bawah batas minimun! Segera pesan.
                                                    <br>
                                                    • <b>Reorder</b>: Stok mencapai titik pesan ulang. Siapkan PO.
                                                    <br>
                                                    • <b>Stock OK</b>: Stok masih aman.
                                                </p>
                                                <!-- Arrow pointing up -->
                                                <div class="absolute top-0 right-4 -translate-y-1/2 rotate-45 w-2 h-2 bg-slate-50 dark:bg-slate-800 border-l border-t border-slate-200 dark:border-slate-700"></div>
                                            </div>
                                        </div>
                                    </div>
                                </th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-right text-blue-400">On Order</th>
                                <th 
                                    @click="sort('qty_on_hand')"
                                    class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-right cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group"
                                >
                                    <div class="flex items-center justify-end gap-2">
                                        Qty On Hand
                                        <span v-if="sortField === 'qty_on_hand'" class="text-blue-500">
                                            <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                            <ChevronDownIcon v-else class="h-3 w-3" />
                                        </span>
                                        <span v-else class="text-slate-500 dark:text-slate-600 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <ChevronUpIcon class="h-3 w-3" />
                                        </span>
                                    </div>
                                </th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-right">Reserved</th>
                                <th 
                                    @click="sort('available')"
                                    class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-right cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group"
                                >
                                    <div class="flex items-center justify-end gap-2">
                                        Available
                                        <span v-if="sortField === 'available'" class="text-blue-500">
                                            <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                            <ChevronDownIcon v-else class="h-3 w-3" />
                                        </span>
                                        <span v-else class="text-slate-500 dark:text-slate-600 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <ChevronUpIcon class="h-3 w-3" />
                                        </span>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-if="stocks.data.length === 0">
                                <td colspan="10" class="px-6 py-8 text-center text-slate-500">
                                    No stock records found.
                                </td>
                            </tr>
                            <template v-for="stock in stocks.data" :key="stock.id">
                                <tr 
                                    v-if="stock.product"
                                    class="transition-colors"
                                    :class="selected.find(s => s.id === stock.id) ? 'bg-blue-500/10 hover:bg-blue-500/20' : 'hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-900 dark:bg-slate-800/50'"
                                >
                                    <td class="pl-6 pr-3 py-4">
                                        <input 
                                            type="checkbox" 
                                            :checked="!!selected.find(s => s.id === stock.id)"
                                            @change="toggleSelection(stock)"
                                            class="rounded border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 text-blue-600 focus:ring-blue-500/50"
                                        />
                                    </td>
                                    <td class="px-4 py-2">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-50 dark:bg-slate-800">
                                                <CubeIcon class="h-5 w-5 text-slate-500 dark:text-slate-400" />
                                            </div>
                                            <div>
                                                <div class="font-medium text-slate-900 dark:text-white">{{ stock.product.name }}</div>
                                                <div class="text-xs text-slate-500 font-mono">{{ stock.product.sku }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2">
                                        <span class="inline-flex rounded-full bg-slate-50 dark:bg-slate-800 px-2 text-xs font-semibold leading-5 text-slate-600 dark:text-slate-300">
                                            {{ stock.product.category?.name || '-' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2">
                                        <span 
                                            class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset"
                                            :class="getProductTypeBadge(stock.product.product_type)"
                                        >
                                            {{ getProductTypeLabel(stock.product.product_type) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-slate-900 dark:text-white">
                                        {{ stock.warehouse?.name || 'Unknown Warehouse' }}
                                        <span v-if="stock.warehouse?.type && stock.warehouse?.type !== 'warehouse'" class="ml-1 text-xs text-slate-500 font-mono">
                                            ({{ stock.warehouse?.type }})
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-right text-xs text-slate-500 dark:text-slate-400 font-mono">
                                        {{ formatNumber(stock.product.min_stock || 0) }} / {{ formatNumber(stock.product.reorder_point || 0) }} / {{ formatNumber(stock.product.max_stock || 0) }}
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        <Link
                                            v-if="getStockStatus(stock).reorderQty > 0"
                                            :href="`/purchasing/requests/create?product_id=${stock.product.id}&qty=${getStockStatus(stock).reorderQty}`"
                                            class="inline-flex items-center rounded-md px-2 py-1 text-xs font-bold ring-1 ring-inset uppercase tracking-wider whitespace-nowrap"
                                            :class="getStockStatus(stock).class"
                                        >
                                            {{ getStockStatus(stock).label }}
                                        </Link>
                                        <span
                                            v-else
                                            class="inline-flex items-center rounded-md px-2 py-1 text-xs font-bold ring-1 ring-inset uppercase tracking-wider whitespace-nowrap"
                                            :class="getStockStatus(stock).class"
                                        >
                                            {{ getStockStatus(stock).label }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-right font-medium text-blue-400">
                                        {{ formatNumber(stock.on_order_qty) }}
                                    </td>
                                    <td class="px-4 py-2 text-right font-medium text-slate-900 dark:text-white transition-colors" :class="{ 'text-red-500 font-bold': getStockStatus(stock).isCritical, 'text-amber-500': getStockStatus(stock).isLow }">
                                        {{ formatNumber(stock.qty_on_hand) }}
                                    </td>
                                    <td class="px-4 py-2 text-right text-slate-500 dark:text-slate-400">
                                        {{ formatNumber(stock.qty_reserved) }}
                                    </td>
                                    <td 
                                        class="px-4 py-2 text-right font-bold transition-colors"
                                        :class="getStockStatus(stock).textClass"
                                    >
                                        {{ formatNumber(stock.qty_on_hand - stock.qty_reserved) }}
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="border-t border-slate-200 dark:border-slate-800 px-6 py-4">
                     <Pagination :links="stocks.links" />
                </div>
            </div>
        </div>

        <!-- Feature Guide -->
        <div class="mt-12">
            <div class="flex items-center gap-2 mb-4 px-1">
                <div class="h-px flex-1 bg-slate-50 dark:bg-slate-800"></div>
                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Inventory Intelligence Guide</span>
                <div class="h-px flex-1 bg-slate-50 dark:bg-slate-800"></div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-blue-500/10 text-blue-400">
                            <FunnelIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Stock Tracking</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Filter stocks by <strong>Warehouse</strong> or <strong>Category</strong> to locate items quickly. Monitor "On Order" vs "Available" for accurate planning.
                    </p>
                </div>

                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-amber-500/10 text-amber-400">
                            <ExclamationTriangleIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Level Indicators</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        <span class="text-red-400 font-bold">URGENT</span>: Below Min Stock.<br>
                        <span class="text-amber-500 font-bold">Reorder</span>: Hit Reorder Point.<br>
                        <span class="text-emerald-500 font-bold">OK</span>: Safe levels.
                    </p>
                </div>

                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-400">
                            <ShoppingCartIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Bulk Reorder</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Select multiple items using the checkboxes and click <strong>Create Request</strong> to generate a Purchase Request instantly.
                    </p>
                </div>
                
                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-purple-500/10 text-purple-400">
                            <CubeIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Availability</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        <strong>Available = On Hand - Reserved</strong>.<br>
                        Always check "Available" stock before committing to new Sales Orders to avoid shortages.
                    </p>
                </div>
            </div>
        </div>

        <!-- Floating Action Bar -->
        <Transition
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-full opacity-0 sm:translate-y-0 sm:translate-x-full"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0 translate-y-full"
        >
            <div v-if="selected.length > 0" class="fixed bottom-6 right-6 left-6 md:left-auto md:w-96 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-2xl shadow-black/50 p-4 z-50">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-500/20 text-blue-400 font-bold">
                            {{ selected.length }}
                        </div>
                        <div class="text-sm">
                            <p class="font-medium text-slate-900 dark:text-white">Items Selected</p>
                            <button @click="selected = []" class="text-xs text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:underline">Clear selection</button>
                        </div>
                    </div>
                    <button 
                        @click="bulkReorder" 
                        class="flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 shadow-lg shadow-blue-900/20"
                    >
                        Create Request
                    </button>
                </div>
            </div>
        </Transition>
    </AppLayout>
</template>



