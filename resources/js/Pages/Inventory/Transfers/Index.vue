<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    PlusIcon,
    MagnifyingGlassIcon,
    EyeIcon,
    TrashIcon,
    TruckIcon,
    ArrowsRightLeftIcon,
    ChevronUpIcon,
    ChevronDownIcon,
    ArrowLongRightIcon,
} from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';

const props = defineProps({
    transfers: Object,
    warehouses: Array,
    filters: Object,
    statuses: Array,
});

const search = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || '');
const selectedWarehouse = ref(props.filters.warehouse_id || '');
const sortField = ref(props.filters.sort || 'created_at');
const sortDirection = ref(props.filters.direction || 'desc');

const applyFilters = debounce(() => {
    router.get('/inventory/transfers', {
        search: search.value || undefined,
        status: selectedStatus.value || undefined,
        warehouse_id: selectedWarehouse.value || undefined,
        sort: sortField.value,
        direction: sortDirection.value,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, selectedStatus, selectedWarehouse], applyFilters);

const sort = (field) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }
    applyFilters();
};

const getStatusBadge = (status) => {
    const badges = {
        draft: 'bg-slate-500/20 text-slate-400 border-slate-500/30',
        in_transit: 'bg-amber-500/20 text-amber-400 border-amber-500/30',
        received: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
        cancelled: 'bg-red-500/20 text-red-400 border-red-500/30',
    };
    return badges[status] || 'bg-slate-500/20 text-slate-400 border-slate-500/30';
};

const getStatusLabel = (status) => {
    const labels = { draft: 'DRAFT', in_transit: 'IN TRANSIT', received: 'RECEIVED', cancelled: 'CANCELLED' };
    return labels[status] || status?.toUpperCase();
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

const deleteTransfer = (t) => {
    if (confirm('Yakin ingin menghapus transfer draft ini?')) {
        router.delete(`/inventory/transfers/${t.id}`);
    }
};
</script>

<template>
    <Head title="Stock Transfers" />

    <AppLayout title="Stock Transfers">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div class="flex items-center gap-3">
                <div class="relative flex-1 sm:w-80">
                    <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500" />
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search transfer number..."
                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800/50 py-2.5 pl-10 pr-4 text-sm text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50 transition-all"
                    />
                </div>
                <select
                    v-model="selectedWarehouse"
                    class="rounded-xl border-0 bg-slate-50 dark:bg-slate-800/50 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                >
                    <option value="">All Warehouses</option>
                    <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                </select>
                <select
                    v-model="selectedStatus"
                    class="rounded-xl border-0 bg-slate-50 dark:bg-slate-800/50 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                >
                    <option value="">All Status</option>
                    <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
            </div>

            <Link
                href="/inventory/transfers/create"
                class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 hover:from-blue-500 hover:to-blue-400 transition-all"
            >
                <PlusIcon class="h-5 w-5" />
                New Transfer
            </Link>
        </div>

        <div class="rounded-2xl glass-card overflow-hidden">
            <div class="overflow-x-auto overflow-y-auto max-h-[600px]">
                <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700">
                            <th @click="sort('transfer_number')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors group">
                                <div class="flex items-center gap-2">
                                    Transfer #
                                    <span v-if="sortField === 'transfer_number'" class="text-blue-500">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th @click="sort('transfer_date')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors group">
                                <div class="flex items-center gap-2">
                                    Date
                                    <span v-if="sortField === 'transfer_date'" class="text-blue-500">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Source → Destination</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Items</th>
                            <th @click="sort('status')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors group">
                                <div class="flex items-center justify-center gap-2">
                                    Status
                                    <span v-if="sortField === 'status'" class="text-blue-500">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr v-for="t in transfers.data" :key="t.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors">
                            <td class="px-6 py-3 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-500/10 text-blue-400">
                                        <TruckIcon class="h-5 w-5" />
                                    </div>
                                    <div class="text-sm font-medium text-slate-900 dark:text-white">{{ t.transfer_number }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-slate-600 dark:text-slate-300">
                                {{ formatDate(t.transfer_date) }}
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                <div class="flex items-center gap-2 text-sm">
                                    <span class="text-slate-900 dark:text-white font-medium">{{ t.source_warehouse?.name }}</span>
                                    <ArrowLongRightIcon class="h-4 w-4 text-slate-400" />
                                    <span class="text-slate-900 dark:text-white font-medium">{{ t.destination_warehouse?.name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-center text-sm text-slate-600 dark:text-slate-300">
                                {{ t.items_count }}
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-center">
                                <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium" :class="getStatusBadge(t.status)">
                                    {{ getStatusLabel(t.status) }}
                                </span>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <Link :href="`/inventory/transfers/${t.id}`" class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors">
                                        <EyeIcon class="h-4 w-4" />
                                    </Link>
                                    <button v-if="t.status === 'draft'" @click="deleteTransfer(t)" class="p-2 rounded-lg text-slate-400 hover:text-red-400 hover:bg-red-500/10 transition-colors">
                                        <TrashIcon class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="transfers.data.length === 0">
                            <td colspan="6" class="px-4 py-12 text-center text-slate-500 italic">No stock transfers found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="transfers.last_page > 1" class="border-t border-slate-200 dark:border-slate-800 px-6 py-4 flex items-center justify-between">
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Showing {{ transfers.from }} to {{ transfers.to }} of {{ transfers.total }} transfers
                </p>
                <div class="flex items-center gap-2">
                    <Link
                        v-for="link in transfers.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        class="px-3 py-1.5 rounded-lg text-sm transition-colors"
                        :class="link.active
                            ? 'bg-blue-600 text-white'
                            : link.url
                                ? 'text-slate-400 hover:bg-slate-800 hover:text-white'
                                : 'text-white/30 cursor-not-allowed'"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>

        <!-- Transfers Operations Guide -->
        <div class="mt-8 relative hidden md:block">
            <div class="absolute inset-0 flex items-center" aria-hidden="true">
                <div class="w-full border-t border-slate-200/60 dark:border-slate-700/60"></div>
            </div>
            <div class="relative flex justify-center">
                <span class="bg-[#F8FAFC] dark:bg-[#0F172A] px-4 text-[10px] font-black tracking-[0.2em] text-slate-400 dark:text-slate-500 uppercase">
                    Transfers Operations Guide
                </span>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4 hidden md:grid mb-8">
            <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                <div class="flex items-center gap-3 mb-3">
                    <div class="p-2.5 rounded-xl bg-blue-500/10 text-blue-400">
                        <TruckIcon class="h-5 w-5" />
                    </div>
                    <h4 class="font-bold text-slate-200 text-sm">Fleet Dispatch</h4>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    Track the lifecycle of internal goods moving between warehouses. A transfer labeled <strong>In Transit</strong> signifies stock actively on delivery.
                </p>
            </div>

            <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                <div class="flex items-center gap-3 mb-3">
                    <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                    </div>
                    <h4 class="font-bold text-slate-200 text-sm">Receipt Handshake</h4>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    Destination inventory is only updated when the transfer status changes to <strong>Received</strong>. Discrepancies must be audited immediately.
                </p>
            </div>

            <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                <div class="flex items-center gap-3 mb-3">
                    <div class="p-2.5 rounded-xl bg-amber-500/10 text-amber-400">
                        <ArrowsRightLeftIcon class="h-5 w-5" />
                    </div>
                    <h4 class="font-bold text-slate-200 text-sm">Drafting Strategy</h4>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    Use <strong>Draft</strong> transfers to pre-plan monthly store restocks without prematurely deducting available inventory from the source location.
                </p>
            </div>
            
            <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                <div class="flex items-center gap-3 mb-3">
                    <div class="p-2.5 rounded-xl bg-cyan-500/10 text-cyan-400">
                        <EyeIcon class="h-5 w-5" />
                    </div>
                    <h4 class="font-bold text-slate-200 text-sm">Deep Inspection</h4>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    Click the <strong>Eye Icon</strong> to visualize exact SKU units being shipped. Once In Transit, contents of the transfer are locked.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
