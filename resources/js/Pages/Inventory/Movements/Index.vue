<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    MagnifyingGlassIcon,
    Bars3Icon,
    FunnelIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
    CubeIcon,
    InformationCircleIcon,
    ArrowPathIcon,
    ArrowsRightLeftIcon,
    ClockIcon,
    ChevronUpIcon,
    ChevronDownIcon,
    TrashIcon,
} from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';

const props = defineProps({
    movements: Object,
    warehouses: Array,
    filters: Object,
    types: Array,
});

const search = ref(props.filters.search || '');
const selectedType = ref(props.filters.type || '');
const selectedWarehouse = ref(props.filters.warehouse_id || '');
const sortField = ref(props.filters.sort || 'created_at');
const sortDirection = ref(props.filters.direction || 'desc');
const showFilters = ref(false);

const resetMovements = () => {
    const password = prompt("⚠️ DANGER ZONE ⚠️\n\nUntuk mereset SEMUA data stock movement dan mengenolkan inventory,\nmasukkan password Anda untuk konfirmasi:");
    
    if (password) {
        if (confirm("ANDA YAKIN? \n\nTindakan ini akan:\n1. MENGHAPUS semua riwayat keluar masuk barang\n2. MENGUBAH stok semua barang menjadi 0\n3. Data Master (Produk/Gudang) TETAP ADA\n\nLanjutkan?")) {
            router.delete('/inventory/movements/reset', {
                data: { password },
                onSuccess: () => alert('Reset berhasil. Semua stok kini 0.'),
                onError: (err) => alert('Gagal: ' + (err.password || err.error || 'Terjadi kesalahan'))
            });
        }
    }
};

const applyFilters = debounce(() => {
    router.get('/inventory/movements', {
        search: search.value || undefined,
        type: selectedType.value || undefined,
        warehouse_id: selectedWarehouse.value || undefined,
        sort: sortField.value,
        direction: sortDirection.value,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, selectedType, selectedWarehouse], applyFilters);

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
    selectedType.value = '';
    selectedWarehouse.value = '';
    sortField.value = 'created_at';
    sortDirection.value = 'desc';
    applyFilters();
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('id-ID', { 
        day: '2-digit', 
        month: 'short', 
        year: 'numeric',
        hour: '2-digit', 
        minute: '2-digit' 
    });
};

const getTypeLabel = (type) => {
    const found = props.types.find(t => t.value === type);
    return found ? found.label : type;
};

const getTypeColor = (type, qty) => {
    if (qty > 0) return 'text-emerald-400 bg-emerald-500/10';
    if (qty < 0) return 'text-red-400 bg-red-500/10';
    return 'text-slate-500 dark:text-slate-400 bg-slate-500/10';
};
</script>

<template>
    <Head title="Stock Movements" />
    
    <AppLayout title="Stock Movements">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div class="flex items-center gap-3">
                <div class="relative flex-1 sm:w-80">
                    <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500" />
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search product..."
                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 py-2.5 pl-10 pr-4 text-sm text-slate-900 dark:text-white placeholder:text-slate-500 focus:bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-blue-500/50 transition-all"
                    />
                </div>
                <!-- Reset Button -->
                <button 
                    @click="resetMovements"
                    class="hidden sm:inline-flex items-center gap-2 rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-2.5 text-sm font-semibold text-red-500 hover:bg-red-500/20 focus:outline-none focus:ring-2 focus:ring-red-500/50 transition-all"
                    title="Reset Stock Movements & Zero Inventory"
                >
                    <TrashIcon class="h-5 w-5" />
                    Reset Data
                </button>
                <button 
                    @click="showFilters = !showFilters"
                    class="flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 px-4 py-2.5 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors"
                    :class="{ 'ring-2 ring-blue-500/50': showFilters }"
                >
                    <FunnelIcon class="h-5 w-5" />
                    Filters
                </button>
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
                        <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Movement Type</label>
                        <select
                            v-model="selectedType"
                            class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                        >
                            <option value="">All Types</option>
                            <option v-for="type in types" :key="type.value" :value="type.value">
                                {{ type.label }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Warehouse</label>
                        <select
                            v-model="selectedWarehouse"
                            class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                        >
                            <option value="">All Warehouses</option>
                            <option v-for="w in warehouses" :key="w.id" :value="w.id">
                                {{ w.name }}
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

        <div class="rounded-2xl glass-card overflow-hidden">
            <div class="overflow-x-auto overflow-y-auto max-h-[600px]">
                <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700">
                            <th 
                                @click="sort('created_at')"
                                class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors group"
                            >
                                <div class="flex items-center gap-2">
                                    Date
                                    <span v-if="sortField === 'created_at'" class="text-blue-500">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                    <span v-else class="text-slate-300 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <ChevronUpIcon class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th 
                                @click="sort('product_name')"
                                class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors group"
                            >
                                <div class="flex items-center gap-2">
                                    Product
                                    <span v-if="sortField === 'product_name'" class="text-blue-500">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                    <span v-else class="text-slate-300 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <ChevronUpIcon class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th 
                                @click="sort('warehouse_name')"
                                class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors group"
                            >
                                <div class="flex items-center gap-2">
                                    Warehouse
                                    <span v-if="sortField === 'warehouse_name'" class="text-blue-500">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                    <span v-else class="text-slate-300 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <ChevronUpIcon class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Type</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Qty</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Balance</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Reference</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr 
                            v-for="movement in movements.data" 
                            :key="movement.id"
                            class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors"
                        >
                            <td class="px-4 py-2 whitespace-nowrap">
                                <span class="text-sm text-slate-600 dark:text-slate-300">{{ formatDate(movement.created_at) }}</span>
                                <div class="text-xs text-slate-500">{{ movement.created_by?.name }}</div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-50 dark:bg-slate-800">
                                        <CubeIcon class="h-5 w-5 text-slate-500 dark:text-slate-400" />
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-slate-900 dark:text-white">{{ movement.product?.name }}</div>
                                        <div class="text-xs text-slate-500 font-mono">{{ movement.product?.sku }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <span class="text-sm text-slate-600 dark:text-slate-300">{{ movement.warehouse?.name }}</span>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-center">
                                <span 
                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="getTypeColor(movement.type, movement.qty)"
                                >
                                    {{ getTypeLabel(movement.type) }}
                                </span>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <ArrowTrendingUpIcon v-if="movement.qty > 0" class="h-4 w-4 text-emerald-400" />
                                    <ArrowTrendingDownIcon v-else class="h-4 w-4 text-red-400" />
                                    <span 
                                        class="text-sm font-bold"
                                        :class="movement.qty > 0 ? 'text-emerald-400' : 'text-red-400'"
                                    >
                                        {{ movement.qty > 0 ? '+' : '' }}{{ Math.round(movement.qty * 100) / 100 }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-right">
                                <span class="text-sm text-slate-600 dark:text-slate-300">{{ Math.round(movement.balance_after * 100) / 100 }}</span>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <span class="text-sm text-slate-500 dark:text-slate-400">{{ movement.notes || '-' }}</span>
                            </td>
                        </tr>
                        <tr v-if="movements.data.length === 0">
                            <td colspan="7" class="px-6 py-12 text-center">
                                <Bars3Icon class="mx-auto h-12 w-12 text-slate-600" />
                                <h3 class="mt-2 text-sm font-medium text-slate-600 dark:text-slate-300">No movements found</h3>
                                <p class="mt-1 text-sm text-slate-500">Stock movements will appear here when inventory changes.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="movements.last_page > 1" class="border-t border-slate-200 dark:border-slate-800 px-6 py-4 flex items-center justify-between">
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Showing {{ movements.from }} to {{ movements.to }} of {{ movements.total }}
                </p>
                <div class="flex items-center gap-2">
                    <Link
                        v-for="link in movements.links"
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
                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Inventory Traceability Guide</span>
                <div class="h-px flex-1 bg-slate-50 dark:bg-slate-800"></div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-blue-500/10 text-blue-400">
                            <ClockIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Audit Trail</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Track every change to your inventory with a full history of <strong>In</strong> and <strong>Out</strong> movements. Know exactly who did what and when.
                    </p>
                </div>

                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-400">
                            <ArrowPathIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Movement Types</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Identify movements by their source: <strong>Adjustment</strong>, <strong>PO Receive</strong>, <strong>SO Delivery</strong>, or <strong>Transfer</strong>.
                    </p>
                </div>

                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-amber-500/10 text-amber-400">
                            <InformationCircleIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Balance History</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        The <strong>Balance</strong> column shows the stock level <em>immediately after</em> the movement, helping you trace stock level changes over time.
                    </p>
                </div>
                
                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-purple-500/10 text-purple-400">
                            <ArrowsRightLeftIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Multi-Warehouse</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Monitor stock flow across different <strong>Warehouses</strong>. Use the filter to see specific site activity accurately.
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>



