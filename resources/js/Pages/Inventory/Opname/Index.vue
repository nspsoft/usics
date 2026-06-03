<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import { formatCurrency, formatDate } from '@/helpers';
import {
    PlusIcon,
    MagnifyingGlassIcon,
    EyeIcon,
    ClipboardDocumentCheckIcon,
    TrashIcon,
    InformationCircleIcon,
    ClockIcon,
    CheckBadgeIcon,
    ExclamationCircleIcon,
    ChevronUpIcon,
    ChevronDownIcon,
    ArrowDownTrayIcon,
    DocumentArrowUpIcon,
    PrinterIcon,
} from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';

const props = defineProps({
    opnames: Object,
    warehouses: Array,
    filters: Object,
    statuses: Array,
    valuationSummary: Object,
});

const search = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || '');
const selectedWarehouse = ref(props.filters.warehouse_id || '');
const sortField = ref(props.filters.sort || 'created_at');
const sortDirection = ref(props.filters.direction || 'desc');

const showExportModal = ref(false);
const exportDateFrom = ref('');
const exportDateTo = ref('');
const exportStatus = ref('');
const exportWarehouse = ref('');

const showImportModal = ref(false);
const importForm = useForm({
    file: null,
    overwrite_existing: true,
});

const handleFileChange = (e) => {
    importForm.file = e.target.files[0];
};

const openImportModal = () => {
    showImportModal.value = true;
};

const closeImportModal = () => {
    showImportModal.value = false;
    importForm.reset();
};

const submitImport = () => {
    importForm.post(route('inventory.opname.import'), {
        preserveScroll: true,
        onSuccess: () => {
            closeImportModal();
        },
    });
};

const page = usePage();
const importErrors = computed(() => page.props.flash?.import_errors || []);
const showImportErrors = ref(true);

watch(importErrors, (v) => {
    if (Array.isArray(v) && v.length) {
        showImportErrors.value = true;
    }
});

const applyFilters = debounce(() => {
    router.get('/inventory/opname', {
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

const openExport = () => {
    exportStatus.value = selectedStatus.value || '';
    exportWarehouse.value = selectedWarehouse.value || '';
    showExportModal.value = true;
};

const closeExport = () => {
    showExportModal.value = false;
};

const exportToExcel = () => {
    const dateFrom = exportDateFrom.value || '';
    const dateTo = exportDateTo.value || dateFrom || '';
    const params = {
        date_from: dateFrom || undefined,
        date_to: dateTo || undefined,
        status: exportStatus.value || undefined,
        warehouse_id: exportWarehouse.value || undefined,
    };

    window.location.href = route('inventory.opname.export', params);
    closeExport();
};

const exportSession = (opnameId) => {
    window.location.href = route('inventory.opname.export', { opname_ids: [opnameId] });
};

const printSession = (opnameId) => {
    window.open(route('inventory.opname.print', opnameId), '_blank');
};

const printByFilter = () => {
    const dateFrom = exportDateFrom.value || '';
    const dateTo = exportDateTo.value || dateFrom || '';
    const params = {
        date_from: dateFrom || undefined,
        date_to: dateTo || undefined,
        status: exportStatus.value || undefined,
        warehouse_id: exportWarehouse.value || undefined,
    };

    window.open(route('inventory.opname.print-batch', params), '_blank');
    closeExport();
};

const printSummaryByDate = () => {
    const dateFrom = exportDateFrom.value || '';
    const dateTo = exportDateTo.value || dateFrom || '';
    if (!dateFrom) {
        alert('Pilih Date From terlebih dahulu.');
        return;
    }
    if (dateTo && dateTo !== dateFrom) {
        alert('Print Summary hanya untuk 1 tanggal. Samakan Date From dan Date To.');
        return;
    }

    const params = {
        date: dateFrom,
        status: exportStatus.value || undefined,
        warehouse_id: exportWarehouse.value || undefined,
    };

    window.open(route('inventory.opname.print-summary', params), '_blank');
    closeExport();
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

const getStatusBadge = (status) => {
    const badges = {
        draft: 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30',
        in_progress: 'bg-blue-500/20 text-blue-400 border-blue-500/30',
        completed: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
        cancelled: 'bg-red-500/20 text-red-400 border-red-500/30',
    };
    return badges[status] || 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30';
};

const deleteOpname = (opname) => {
    if (confirm('Are you sure you want to delete this session?')) {
        router.delete(`/inventory/opname/${opname.id}`);
    }
};
</script>

<template>
    <Head title="Stock Opname" />
    
    <AppLayout title="Stock Opname">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div class="flex items-center gap-3">
                <div class="relative flex-1 sm:w-80">
                    <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500" />
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search..."
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
                    <option value="">All Statuses</option>
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
                <button
                    type="button"
                    @click="openExport"
                    class="inline-flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 px-4 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                >
                    <ArrowDownTrayIcon class="h-5 w-5" />
                    Export
                </button>
                <Link
                    href="/inventory/opname/create"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 hover:from-blue-500 hover:to-blue-400 transition-all"
                >
                    <PlusIcon class="h-5 w-5" />
                    New Session
                </Link>
            </div>
        </div>

        <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="rounded-2xl border border-slate-200 dark:border-slate-700/50 bg-white dark:bg-slate-900/50 px-5 py-4">
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Sistem (Rp)</div>
                <div class="mt-1 text-lg font-bold text-slate-900 dark:text-white font-mono">
                    {{ formatCurrency(valuationSummary?.system_value ?? 0) }}
                </div>
            </div>
            <div class="rounded-2xl border border-slate-200 dark:border-slate-700/50 bg-white dark:bg-slate-900/50 px-5 py-4">
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Fisik (Rp)</div>
                <div class="mt-1 text-lg font-bold text-slate-900 dark:text-white font-mono">
                    {{ formatCurrency(valuationSummary?.physical_value ?? 0) }}
                </div>
            </div>
            <div class="rounded-2xl border border-slate-200 dark:border-slate-700/50 bg-white dark:bg-slate-900/50 px-5 py-4">
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Variance (Rp)</div>
                <div class="mt-1 text-lg font-bold text-slate-900 dark:text-white font-mono">
                    {{ formatCurrency(valuationSummary?.variance_value ?? 0) }}
                </div>
            </div>
        </div>

        <div v-if="showImportErrors && importErrors.length" class="mb-6 rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-3">
            <div class="flex items-start justify-between gap-3">
                <div class="text-sm text-amber-200">
                    <div class="font-semibold">Import: ada baris yang gagal diproses</div>
                    <ul class="mt-2 list-disc pl-5 space-y-1">
                        <li v-for="(err, i) in importErrors.slice(0, 10)" :key="i">{{ err }}</li>
                    </ul>
                    <div v-if="importErrors.length > 10" class="mt-2 text-xs text-amber-300">
                        Menampilkan 10 dari {{ importErrors.length }} error.
                    </div>
                </div>
                <button
                    type="button"
                    class="text-xs font-semibold text-amber-200 hover:text-amber-100"
                    @click="showImportErrors = false"
                >
                    Tutup
                </button>
            </div>
        </div>

        <div class="rounded-2xl glass-card overflow-hidden">
            <div class="overflow-x-auto overflow-y-auto max-h-[600px]">
                <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700">
                            <th 
                                @click="sort('opname_number')"
                                class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors group"
                            >
                                <div class="flex items-center gap-2">
                                    Number
                                    <span v-if="sortField === 'opname_number'" class="text-blue-500">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                    <span v-else class="text-slate-300 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <ChevronUpIcon class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th 
                                @click="sort('opname_date')"
                                class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors group"
                            >
                                <div class="flex items-center gap-2">
                                    Date
                                    <span v-if="sortField === 'opname_date'" class="text-blue-500">
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
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Items</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">System (Rp)</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Physical (Rp)</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Variance (Rp)</th>
                            <th 
                                @click="sort('status')"
                                class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors group"
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
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr 
                            v-for="opname in opnames.data" 
                            :key="opname.id"
                            class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors"
                        >
                            <td class="px-4 py-2 whitespace-nowrap">
                                <div class="text-sm font-medium text-slate-900 dark:text-white">{{ opname.opname_number }}</div>
                                <div class="text-xs text-slate-500">{{ opname.created_by?.name }}</div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <span class="text-sm text-slate-600 dark:text-slate-300">{{ formatDate(opname.opname_date) }}</span>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <span class="text-sm text-slate-600 dark:text-slate-300">{{ opname.warehouse?.name }}</span>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-center">
                                <span class="text-sm text-slate-600 dark:text-slate-300">{{ opname.items_count }}</span>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-right text-sm text-slate-600 dark:text-slate-300 font-mono">
                                {{ formatCurrency(opname.system_value ?? 0) }}
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-right text-sm text-slate-600 dark:text-slate-300 font-mono">
                                {{ formatCurrency(opname.physical_value ?? 0) }}
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-right text-sm text-slate-600 dark:text-slate-300 font-mono">
                                {{ formatCurrency(opname.variance_value ?? 0) }}
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-center">
                                <span 
                                    class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium capitalize"
                                    :class="getStatusBadge(opname.status)"
                                >
                                    {{ opname.status }}
                                </span>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <Link
                                        :href="`/inventory/opname/${opname.id}`"
                                        class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors"
                                    >
                                        <EyeIcon class="h-4 w-4" />
                                    </Link>
                                    <button
                                        type="button"
                                        @click="exportSession(opname.id)"
                                        class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-emerald-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors"
                                    >
                                        <ArrowDownTrayIcon class="h-4 w-4" />
                                    </button>
                                    <button
                                        type="button"
                                        @click="printSession(opname.id)"
                                        class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors"
                                    >
                                        <PrinterIcon class="h-4 w-4" />
                                    </button>
                                    <button
                                        v-if="opname.status !== 'completed'"
                                        @click="deleteOpname(opname)"
                                        class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-red-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors"
                                    >
                                        <TrashIcon class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="opnames.data.length === 0">
                            <td colspan="9" class="px-6 py-12 text-center">
                                <ClipboardDocumentCheckIcon class="mx-auto h-12 w-12 text-slate-600" />
                                <h3 class="mt-2 text-sm font-medium text-slate-600 dark:text-slate-300">No sessions found</h3>
                                <p class="mt-1 text-sm text-slate-500">Create a session to start stock taking.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div v-if="opnames.last_page > 1" class="border-t border-slate-200 dark:border-slate-800 px-6 py-4 flex items-center justify-between">
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Showing {{ opnames.from }} to {{ opnames.to }} of {{ opnames.total }}
                </p>
                <div class="flex items-center gap-2">
                    <Link
                        v-for="link in opnames.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        class="px-3 py-1.5 rounded-lg text-sm transition-colors"
                        :class="link.active 
                            ? 'bg-blue-600 text-slate-900 dark:text-white' 
                            : link.url 
                                ? 'text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 hover:text-slate-900 dark:text-white' 
                                : 'text-slate-600 cursor-not-allowed opacity-50'"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>

        <!-- Panduan Stock Opname -->
        <div class="mt-12">
            <div class="flex items-center gap-2 mb-4 px-1">
                <div class="h-px flex-1 bg-slate-50 dark:bg-slate-800"></div>
                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Panduan Stock Opname</span>
                <div class="h-px flex-1 bg-slate-50 dark:bg-slate-800"></div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-blue-500/10 text-blue-400">
                            <ClockIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Buat Sesi Baru</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Klik <strong>New Session</strong>, pilih gudang dan tanggal. Sistem akan membuat sesi opname baru untuk memverifikasi stok fisik.
                    </p>
                </div>

                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-400">
                            <CheckBadgeIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Hitung & Auto-Simpan</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Gunakan tombol <strong>+/−</strong> atau ketik angka langsung. Setiap perubahan <strong>otomatis tersimpan</strong>. Bisa diakses lewat HP atau tablet.
                    </p>
                </div>

                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-amber-500/10 text-amber-400">
                            <InformationCircleIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Selisih Otomatis</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Sistem menghitung selisih antara stok sistem dan stok fisik secara otomatis. Gunakan filter <strong>Changed/Pending</strong> untuk fokus pada item tertentu.
                    </p>
                </div>
                
                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-red-500/10 text-red-400">
                            <ExclamationCircleIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Tidak Bisa Dibatalkan</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Setelah sesi ditandai <strong>Complete</strong>, penyesuaian stok langsung diposting dan <strong>tidak bisa diedit atau dihapus</strong>. Pastikan semua sudah benar.
                    </p>
                </div>
            </div>
        </div>

        <Modal :show="showExportModal" @close="closeExport">
            <div class="px-6 py-4">
                <div class="text-lg font-semibold text-slate-900 dark:text-white">Export Stock Opname</div>
                <div class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Export detail item Stock Opname ke Excel untuk backup.
                </div>

                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-1">Date From</label>
                        <input
                            v-model="exportDateFrom"
                            type="date"
                            class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-1">Date To</label>
                        <input
                            v-model="exportDateTo"
                            type="date"
                            class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-1">Warehouse</label>
                        <select
                            v-model="exportWarehouse"
                            class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                        >
                            <option value="">All Warehouses</option>
                            <option v-for="w in warehouses" :key="w.id" :value="w.id">
                                {{ w.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-1">Status</label>
                        <select
                            v-model="exportStatus"
                            class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                        >
                            <option value="">All Statuses</option>
                            <option v-for="s in statuses" :key="s.value" :value="s.value">
                                {{ s.label }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 bg-slate-50 dark:bg-slate-900 px-6 py-4">
                <button
                    type="button"
                    class="rounded-lg bg-white dark:bg-slate-800 px-4 py-2 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                    @click="closeExport"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 transition-colors"
                    @click="exportToExcel"
                >
                    Export
                </button>
                <button
                    type="button"
                    class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 transition-colors"
                    @click="printByFilter"
                >
                    Print
                </button>
                <button
                    type="button"
                    class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500 transition-colors"
                    @click="printSummaryByDate"
                >
                    Print Summary
                </button>
            </div>
        </Modal>

        <Modal :show="showImportModal" @close="closeImportModal">
            <div class="px-6 py-4">
                <div class="text-lg font-semibold text-slate-900 dark:text-white">Import Stock Opname</div>
                <div class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Import dari file hasil export Stock Opname (untuk restore histori).
                </div>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-1">File Excel</label>
                        <input
                            type="file"
                            accept=".xlsx,.xls,.csv"
                            class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                            @change="handleFileChange"
                        />
                        <div v-if="importForm.errors.file" class="mt-1 text-xs text-red-400">{{ importForm.errors.file }}</div>
                    </div>

                    <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                        <input
                            v-model="importForm.overwrite_existing"
                            type="checkbox"
                            class="rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500"
                        />
                        Overwrite session jika Opname Number sudah ada
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 bg-slate-50 dark:bg-slate-900 px-6 py-4">
                <button
                    type="button"
                    class="rounded-lg bg-white dark:bg-slate-800 px-4 py-2 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                    @click="closeImportModal"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 transition-colors disabled:opacity-50"
                    :disabled="importForm.processing"
                    @click="submitImport"
                >
                    Import
                </button>
            </div>
        </Modal>
    </AppLayout>
</template>



