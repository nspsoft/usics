<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    PlusIcon,
    MagnifyingGlassIcon,
    PencilSquareIcon,
    TrashIcon,
    EyeIcon,
    BuildingStorefrontIcon,
    MapPinIcon,
    ShieldCheckIcon,
    ChevronUpIcon,
    ChevronDownIcon,
} from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';

const props = defineProps({
    warehouses: Object,
    filters: Object,
    warehouseTypes: Array,
});

const search = ref(props.filters.search || '');
const selectedType = ref(props.filters.type || '');
const sortField = ref(props.filters.sort || 'name');
const sortDirection = ref(props.filters.direction || 'asc');

const applyFilters = debounce(() => {
    router.get('/inventory/warehouses', {
        search: search.value || undefined,
        type: selectedType.value || undefined,
        sort: sortField.value,
        direction: sortDirection.value,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, selectedType], applyFilters);

const sort = (field) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }
    applyFilters();
};

const deleteWarehouse = (warehouse) => {
    if (confirm(`Are you sure you want to delete "${warehouse.name}"?`)) {
        router.delete(`/inventory/warehouses/${warehouse.id}`);
    }
};

const getTypeBadge = (type) => {
    const badges = {
        warehouse: 'bg-blue-500/20 text-blue-400 border-blue-500/30',
        production: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
        transit: 'bg-amber-500/20 text-amber-400 border-amber-500/30',
        scrap: 'bg-red-500/20 text-red-400 border-red-500/30',
        subcontract: 'bg-purple-500/20 text-purple-400 border-purple-500/30',
    };
    return badges[type] || 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30';
};

const getTypeLabel = (type) => {
    const labels = {
        warehouse: 'Warehouse',
        production: 'Production',
        transit: 'Transit',
        scrap: 'Scrap',
        subcontract: 'Subcontract Only',
    };
    return labels[type] || type;
};
</script>

<template>
    <Head title="Warehouses" />
    
    <AppLayout title="Warehouses">
        <!-- Header Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div class="flex items-center gap-3">
                <div class="relative flex-1 sm:w-80">
                    <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500" />
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search warehouses..."
                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 py-2.5 pl-10 pr-4 text-sm text-slate-900 dark:text-white placeholder:text-slate-500 focus:bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-blue-500/50 transition-all"
                    />
                </div>
                <select
                    v-model="selectedType"
                    class="rounded-xl border-0 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                >
                    <option value="">All Types</option>
                    <option v-for="type in warehouseTypes" :key="type.value" :value="type.value">
                        {{ type.label }}
                    </option>
                </select>
            </div>
            
            <Link
                href="/inventory/warehouses/create"
                class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 hover:from-blue-500 hover:to-blue-400 transition-all"
            >
                <PlusIcon class="h-5 w-5" />
                Add Warehouse
            </Link>
        </div>

        <!-- Warehouses Table -->
        <div class="rounded-2xl glass-card overflow-hidden mb-8">
            <div class="overflow-x-auto overflow-y-auto max-h-[600px]">
                <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700">
                            <th 
                                @click="sort('name')"
                                class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group"
                            >
                                <div class="flex items-center gap-2">
                                    Warehouse Name
                                    <span v-if="sortField === 'name'" class="text-blue-500">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                    <span v-else class="text-slate-300 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <ChevronUpIcon class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th 
                                @click="sort('code')"
                                class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group"
                            >
                                <div class="flex items-center gap-2">
                                    Code
                                    <span v-if="sortField === 'code'" class="text-blue-500">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                    <span v-else class="text-slate-300 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <ChevronUpIcon class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th 
                                @click="sort('type')"
                                class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group"
                            >
                                <div class="flex items-center gap-2">
                                    Type
                                    <span v-if="sortField === 'type'" class="text-blue-500">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                    <span v-else class="text-slate-300 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <ChevronUpIcon class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th 
                                @click="sort('location')"
                                class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group"
                            >
                                <div class="flex items-center gap-2">
                                    Location
                                    <span v-if="sortField === 'location'" class="text-blue-500">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                    <span v-else class="text-slate-300 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <ChevronUpIcon class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th 
                                @click="sort('is_active')"
                                class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group"
                            >
                                <div class="flex items-center justify-center gap-2">
                                    Status
                                    <span v-if="sortField === 'is_active'" class="text-blue-500">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                    <span v-else class="text-slate-300 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <ChevronUpIcon class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr v-for="warehouse in warehouses.data" :key="warehouse.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors">
                            <td class="px-4 py-2 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 dark:bg-slate-800">
                                        <BuildingStorefrontIcon class="h-5 w-5 text-indigo-400" />
                                    </div>
                                    <div class="text-sm font-medium text-slate-900 dark:text-white">{{ warehouse.name }}</div>
                                </div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400 font-mono">
                                {{ warehouse.code }}
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm">
                                <span 
                                    class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase"
                                    :class="getTypeBadge(warehouse.type)"
                                >
                                    {{ getTypeLabel(warehouse.type) }}
                                </span>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">
                                {{ warehouse.location || '-' }}
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-center">
                                <span 
                                    class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium"
                                    :class="warehouse.is_active ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-red-500/10 text-red-400 border-red-500/20'"
                                >
                                    {{ warehouse.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <Link :href="`/inventory/warehouses/${warehouse.id}/map`" class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-emerald-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors" title="View Map">
                                        <MapPinIcon class="h-4 w-4" />
                                    </Link>
                                    <Link :href="`/inventory/warehouses/${warehouse.id}/edit`" class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-blue-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors">
                                        <PencilSquareIcon class="h-4 w-4" />
                                    </Link>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="warehouses.data.length === 0">
                            <td colspan="6" class="px-4 py-12 text-center text-slate-500 italic">No warehouses found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div v-if="warehouses.last_page > 1" class="border-t border-slate-200 dark:border-slate-800 px-6 py-4 flex items-center justify-between">
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Showing {{ warehouses.from }} to {{ warehouses.to }} of {{ warehouses.total }} warehouses
                </p>
                <div class="flex items-center gap-2">
                    <Link
                        v-for="link in warehouses.links"
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
                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Facility Guide</span>
                <div class="h-px flex-1 bg-slate-50 dark:bg-slate-800"></div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-blue-500/10 text-blue-400">
                            <BuildingStorefrontIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Multi-Warehouse</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Track inventory across <strong>Multiple Locations</strong>. Define distinct warehouses for Raw Materials, Finished Goods, or virtual locations for returns.
                    </p>
                </div>

                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-orange-500/10 text-orange-400">
                            <MapPinIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Location Mapping</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Maintain <strong>Geographic Context</strong> for your stock. Including location data helps delivery drivers and warehouse staff find items quickly.
                    </p>
                </div>

                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-400">
                            <PlusIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Type Segmentation</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Distinguish between <strong>Main Hubs</strong> and satellite stores. Controlling warehouse types ensures that stock movements follow authorized routes.
                    </p>
                </div>
                
                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-purple-500/10 text-purple-400">
                            <ShieldCheckIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Access Control</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Confirm <strong>Warehouse Visibility</strong>. Active warehouses are available for all stock operations, while inactive ones are archived for history.
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>



