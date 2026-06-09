<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import debounce from 'lodash/debounce';
import {
    PlusIcon,
    MagnifyingGlassIcon,
    FunnelIcon,
    EyeIcon,
} from '@heroicons/vue/24/outline';
import { formatNumber, formatCurrency, formatDate } from '@/helpers';

const props = defineProps({
    reclassifications: Object,
    warehouses: Array,
    filters: Object,
    statuses: Array,
});

const search = ref(props.filters?.search || '');
const selectedStatus = ref(props.filters?.status || '');
const selectedWarehouse = ref(props.filters?.warehouse_id || '');
const showFilters = ref(false);

const applyFilters = debounce(() => {
    router.get(route('inventory.reclassifications.index'), {
        search: search.value || undefined,
        status: selectedStatus.value || undefined,
        warehouse_id: selectedWarehouse.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, selectedStatus, selectedWarehouse], applyFilters);

const clearFilters = () => {
    search.value = '';
    selectedStatus.value = '';
    selectedWarehouse.value = '';
};

const getStatusBadge = (status) => {
    const badges = {
        draft: 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30',
        posted: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
        cancelled: 'bg-red-500/20 text-red-400 border-red-500/30',
    };
    return badges[status] || badges.draft;
};
</script>

<template>
    <Head title="Stock Reclassifications" />

    <AppLayout title="Stock Reclassifications">
        <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 mb-6">
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative w-full sm:w-72">
                    <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500" />
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search reclass number or reason..."
                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-900/50 py-2 pl-10 pr-4 text-sm text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50"
                    />
                </div>
                <button
                    @click="showFilters = !showFilters"
                    class="flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-800/50 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800"
                    :class="{ 'ring-2 ring-blue-500/50': showFilters }"
                >
                    <FunnelIcon class="h-5 w-5" />
                    Filters
                </button>
            </div>

            <Link
                :href="route('inventory.reclassifications.create')"
                class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 hover:from-blue-500 hover:to-blue-400 transition-all"
            >
                <PlusIcon class="h-5 w-5" />
                New Reclass
            </Link>
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
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Status</label>
                        <select v-model="selectedStatus" class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50">
                            <option value="">All Status</option>
                            <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Warehouse</label>
                        <select v-model="selectedWarehouse" class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50">
                            <option value="">All Warehouses</option>
                            <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">{{ warehouse.name }}</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button @click="clearFilters" class="w-full rounded-xl bg-slate-50 dark:bg-slate-800 px-4 py-2.5 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700">
                            Clear Filters
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <div class="rounded-2xl glass-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-900/50">
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Number</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Date</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Warehouse</th>
                            <th class="px-4 py-4 text-right text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Lines</th>
                            <th class="px-4 py-4 text-right text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Qty</th>
                            <th class="px-4 py-4 text-right text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Value</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Created By</th>
                            <th class="px-6 py-4 text-center text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Status</th>
                            <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr v-for="reclass in reclassifications.data" :key="reclass.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <td class="px-6 py-4">
                                <div class="text-sm font-semibold text-slate-900 dark:text-white">{{ reclass.reclass_number }}</div>
                                <div class="text-xs text-slate-500">{{ reclass.reason }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-300">{{ formatDate(reclass.reclass_date) }}</td>
                            <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-300">{{ reclass.warehouse?.name }}</td>
                            <td class="px-4 py-4 text-right text-sm text-slate-600 dark:text-slate-300">{{ reclass.items_count }}</td>
                            <td class="px-4 py-4 text-right text-sm font-medium text-slate-900 dark:text-white">{{ formatNumber(reclass.total_qty || 0) }}</td>
                            <td class="px-4 py-4 text-right text-sm font-medium text-slate-900 dark:text-white">{{ formatCurrency(reclass.total_value || 0) }}</td>
                            <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-300">{{ reclass.created_by?.name || reclass.createdBy?.name || '-' }}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold capitalize" :class="getStatusBadge(reclass.status)">
                                    {{ reclass.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <Link :href="route('inventory.reclassifications.show', reclass.id)" class="inline-flex rounded-lg p-2 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800">
                                    <EyeIcon class="h-4 w-4" />
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="reclassifications.data.length === 0">
                            <td colspan="9" class="px-6 py-12 text-center text-slate-500 italic">No reclass documents found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="reclassifications.last_page > 1" class="border-t border-slate-200 dark:border-slate-800 px-6 py-4">
                <Pagination :links="reclassifications.links" />
            </div>
        </div>
    </AppLayout>
</template>
