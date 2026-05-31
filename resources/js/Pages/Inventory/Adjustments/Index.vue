<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import {
    PlusIcon,
    MagnifyingGlassIcon,
    EyeIcon,
    CheckCircleIcon,
    XCircleIcon,
    TrashIcon,
    InformationCircleIcon,
    ArrowPathRoundedSquareIcon,
    ShieldCheckIcon,
    ScaleIcon,
    ArrowsRightLeftIcon,
    NoSymbolIcon,
    DocumentArrowUpIcon,
    ArrowDownTrayIcon,
    ChevronUpIcon,
    ChevronDownIcon,
} from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';

const props = defineProps({
    adjustments: Object,
    warehouses: Array,
    filters: Object,
    statuses: Array,
});

const search = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || '');
const selectedWarehouse = ref(props.filters.warehouse_id || '');
const sortField = ref(props.filters.sort || 'created_at');
const sortDirection = ref(props.filters.direction || 'desc');

const showImportModal = ref(false);
const importForm = useForm({
    file: null,
    warehouse_id: props.filters.warehouse_id || '',
    adjustment_date: new Date().toISOString().slice(0, 10),
    reason: 'STO Correction',
    notes: '',
});

const openImportModal = () => {
    importForm.warehouse_id = selectedWarehouse.value || importForm.warehouse_id || '';
    showImportModal.value = true;
};

const closeImportModal = () => {
    showImportModal.value = false;
    importForm.reset();
};

const handleFileChange = (e) => {
    importForm.file = e.target.files[0];
};

const downloadTemplate = () => {
    window.location.href = route('inventory.adjustments.template');
};

const submitImport = () => {
    importForm.post(route('inventory.adjustments.import'), {
        preserveScroll: true,
        onSuccess: () => {
            closeImportModal();
        },
    });
};

const applyFilters = debounce(() => {
    router.get('/inventory/adjustments', {
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
        draft: 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30',
        completed: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
        cancelled: 'bg-red-500/20 text-red-400 border-red-500/30',
    };
    return badges[status] || 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30';
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

const deleteAdjustment = (adj) => {
    if (confirm('Are you sure you want to delete this draft adjustment?')) {
        router.delete(`/inventory/adjustments/${adj.id}`);
    }
};
</script>

<template>
    <Head title="Stock Adjustments" />
    
    <AppLayout title="Stock Adjustments">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div class="flex items-center gap-3">
                <div class="relative flex-1 sm:w-80">
                    <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500" />
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search adjustment number..."
                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 py-2.5 pl-10 pr-4 text-sm text-slate-900 dark:text-white placeholder:text-slate-500 focus:bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-blue-500/50 transition-all"
                    />
                </div>
                <select
                    v-model="selectedWarehouse"
                    class="rounded-xl border-0 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                >
                    <option value="">All Warehouses</option>
                    <option v-for="w in warehouses" :key="w.id" :value="w.id">
                        {{ w.name }}
                    </option>
                </select>
                <select
                    v-model="selectedStatus"
                    class="rounded-xl border-0 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                >
                    <option value="">All Status</option>
                    <option v-for="s in statuses" :key="s.value" :value="s.value">
                        {{ s.label }}
                    </option>
                </select>
            </div>
            
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    @click="openImportModal"
                    class="inline-flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 px-4 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                >
                    <DocumentArrowUpIcon class="h-5 w-5" />
                    Import
                </button>
                <Link
                    href="/inventory/adjustments/create"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 hover:from-blue-500 hover:to-blue-400 transition-all"
                >
                    <PlusIcon class="h-5 w-5" />
                    New Adjustment
                </Link>
            </div>
        </div>

        <div class="rounded-2xl glass-card overflow-hidden">
            <div class="overflow-x-auto overflow-y-auto max-h-[600px]">
                <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700">
                            <th 
                                @click="sort('adjustment_number')"
                                class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors group"
                            >
                                <div class="flex items-center gap-2">
                                    Number
                                    <span v-if="sortField === 'adjustment_number'" class="text-blue-500">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                    <span v-else class="text-slate-300 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <ChevronUpIcon class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th 
                                @click="sort('adjustment_date')"
                                class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors group"
                            >
                                <div class="flex items-center gap-2">
                                    Date
                                    <span v-if="sortField === 'adjustment_date'" class="text-blue-500">
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
                                class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors group"
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
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Reason</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Items</th>
                            <th 
                                @click="sort('status')"
                                class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors group"
                            >
                                <div class="flex items-center justify-center gap-2">
                                    Status
                                    <span v-if="sortField === 'status'" class="text-blue-500">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                    <span v-else class="text-slate-300 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <ChevronUpIcon class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr v-for="adj in adjustments.data" :key="adj.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors">
                            <td class="px-4 py-2 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 dark:bg-slate-800 font-mono text-xs text-slate-500">
                                        ADJ
                                    </div>
                                    <div class="text-sm font-medium text-slate-900 dark:text-white">{{ adj.number }}</div>
                                </div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm text-slate-900 dark:text-white">
                                {{ adj.warehouse?.name }}
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm text-slate-600 dark:text-slate-300">
                                {{ formatDate(adj.adjustment_date) }}
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-center text-sm text-slate-600 dark:text-slate-300">
                                {{ adj.items_count }}
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-center">
                                <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium capitalize" :class="getStatusBadge(adj.status)">{{ adj.status?.toUpperCase() }}</span>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-right">
                                <Link :href="route('inventory.adjustments.show', adj.id)" class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors">
                                    <EyeIcon class="h-4 w-4" />
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="adjustments.data.length === 0">
                            <td colspan="6" class="px-4 py-12 text-center text-slate-500 italic">No stock adjustments found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div v-if="adjustments.last_page > 1" class="border-t border-slate-200 dark:border-slate-800 px-6 py-4 flex items-center justify-between">
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Showing {{ adjustments.from }} to {{ adjustments.to }} of {{ adjustments.total }} adjustments
                </p>
                <div class="flex items-center gap-2">
                    <Link
                        v-for="link in adjustments.links"
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
                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Inventory Correction Guide</span>
                <div class="h-px flex-1 bg-slate-50 dark:bg-slate-800"></div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-orange-500/10 text-orange-400">
                            <ArrowsRightLeftIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Stock Correction</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Use adjustments to fix <strong>Minor Discrepancies</strong> found during daily operations. For periodic full counts, use the <strong>Stock Opname</strong> feature instead.
                    </p>
                </div>

                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-red-500/10 text-red-400">
                            <NoSymbolIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Damaged Goods</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Quickly <strong>Write-off</strong> items that are damaged, expired, or otherwise unsellable. Adjustments provide a clear trail for inventory loss accounting.
                    </p>
                </div>

                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-blue-500/10 text-blue-400">
                            <DocumentArrowUpIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Reason Codes</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Always provide a <strong>Reason for Adjustment</strong>. This helps management identify recurring issues in warehouse handling or data entry accuracy.
                    </p>
                </div>
                
                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-purple-500/10 text-purple-400">
                            <ShieldCheckIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Audit Preparedness</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Adjustments automatically post <strong>Journal Entries</strong> to the general ledger, ensuring your financial inventory value matches physical reality.
                    </p>
                </div>
            </div>
        </div>

        <Modal :show="showImportModal" @close="closeImportModal">
            <div class="p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Import Stock Adjustment</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            Gunakan file per gudang. Sistem akan membuat draft Adjustment dan menghitung selisih otomatis.
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="downloadTemplate"
                        class="inline-flex items-center gap-2 rounded-lg bg-slate-50 dark:bg-slate-800 px-3 py-2 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                    >
                        <ArrowDownTrayIcon class="h-4 w-4" />
                        Template
                    </button>
                </div>

                <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-1">Warehouse</label>
                        <select
                            v-model="importForm.warehouse_id"
                            class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                        >
                            <option value="">Pilih Warehouse</option>
                            <option v-for="w in warehouses" :key="w.id" :value="w.id">
                                {{ w.name }}
                            </option>
                        </select>
                        <div v-if="importForm.errors.warehouse_id" class="mt-1 text-xs text-red-400">{{ importForm.errors.warehouse_id }}</div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-1">Adjustment Date</label>
                        <input
                            v-model="importForm.adjustment_date"
                            type="date"
                            class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                        />
                        <div v-if="importForm.errors.adjustment_date" class="mt-1 text-xs text-red-400">{{ importForm.errors.adjustment_date }}</div>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-1">Reason</label>
                        <input
                            v-model="importForm.reason"
                            type="text"
                            class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                        />
                        <div v-if="importForm.errors.reason" class="mt-1 text-xs text-red-400">{{ importForm.errors.reason }}</div>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-1">Notes (optional)</label>
                        <textarea
                            v-model="importForm.notes"
                            rows="2"
                            class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                        />
                        <div v-if="importForm.errors.notes" class="mt-1 text-xs text-red-400">{{ importForm.errors.notes }}</div>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-1">File</label>
                        <input
                            type="file"
                            accept=".xlsx,.xls,.csv"
                            @change="handleFileChange"
                            class="block w-full text-sm text-slate-600 dark:text-slate-300 file:mr-4 file:rounded-lg file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200 dark:file:bg-slate-800 dark:file:text-slate-200 dark:hover:file:bg-slate-700"
                        />
                        <div v-if="importForm.errors.file" class="mt-1 text-xs text-red-400">{{ importForm.errors.file }}</div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 bg-slate-50 dark:bg-slate-900 px-6 py-4">
                <SecondaryButton @click="closeImportModal">Cancel</SecondaryButton>
                <PrimaryButton :disabled="importForm.processing" @click="submitImport">Import</PrimaryButton>
            </div>
        </Modal>
    </AppLayout>
</template>



