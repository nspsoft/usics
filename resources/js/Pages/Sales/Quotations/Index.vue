<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    PlusIcon,
    MagnifyingGlassIcon,
    EyeIcon,
    DocumentDuplicateIcon,
    FunnelIcon,
    CheckCircleIcon,
    ClockIcon,
    ClipboardDocumentCheckIcon,
    ShieldCheckIcon,
    DocumentPlusIcon,
    PencilSquareIcon,
    ChevronUpIcon,
    ChevronDownIcon,
    ArrowUpTrayIcon,
    ArrowDownTrayIcon,
    ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';
import { formatNumber, formatCurrency } from '@/helpers';
import debounce from 'lodash/debounce';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    quotations: Object,
    filters: Object,
    statuses: Array,
});

const search = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || '');
const sortField = ref(props.filters.sort || 'created_at');
const sortDirection = ref(props.filters.direction || 'desc');
const showFilters = ref(false);

const applyFilters = debounce(() => {
    router.get('/sales/quotations', {
        search: search.value || undefined,
        status: selectedStatus.value || undefined,
        sort: sortField.value,
        direction: sortDirection.value,
    }, { preserveState: true, replace: true });
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
        draft: 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-9000/20 dark:text-slate-500 dark:text-slate-400 dark:border-slate-500/30',
        sent: 'bg-blue-50 text-blue-600 border-blue-100 dark:bg-blue-500/20 dark:text-blue-400 dark:border-blue-500/30',
        accepted: 'bg-emerald-50 text-emerald-600 border-emerald-100 dark:bg-emerald-500/20 dark:text-emerald-400 dark:border-emerald-500/30',
        rejected: 'bg-red-50 text-red-600 border-red-100 dark:bg-red-500/20 dark:text-red-400 dark:border-red-500/30',
        expired: 'bg-amber-50 text-amber-600 border-amber-100 dark:bg-amber-500/20 dark:text-amber-400 dark:border-amber-500/30',
    };
    return badges[status] || 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-9000/20 dark:text-slate-500 dark:text-slate-400 dark:border-slate-500/30';
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

const deleteQuotation = (q) => {
    if (confirm(`Are you sure you want to delete quotation "${q.number}"?`)) {
        router.delete(`/sales/quotations/${q.id}`);
    }
};

const showImportModal = ref(false);
const importForm = useForm({ 
    file: null,
    overwrite: false,
});
const includeData = ref(false);
const fileInput = ref(null);

const handleFileChange = (e) => {
    importForm.file = e.target.files[0];
};

const submitImport = () => {
    importForm.post('/sales/quotations-import', {
        onSuccess: () => {
            showImportModal.value = false;
            importForm.reset();
            if (fileInput.value) fileInput.value.value = '';
        },
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="Quotations" />
    
    <AppLayout title="Quotations">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div class="flex items-center gap-3">
                <div class="relative flex-1 sm:w-80">
                    <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500 dark:text-slate-400" />
                    <input v-model="search" type="search" placeholder="Search quotation..." class="block w-full rounded-xl border-0 bg-white dark:bg-slate-800/50 py-2.5 pl-10 pr-4 text-sm text-slate-900 dark:text-white placeholder:text-slate-500 dark:placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50 shadow-sm dark:shadow-none ring-1 ring-slate-200 dark:ring-0 transition-all" />
                </div>
                <button @click="showFilters = !showFilters" class="flex items-center gap-2 rounded-xl bg-white dark:bg-slate-800/50 px-4 py-2.5 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-0 transition-colors shadow-sm dark:shadow-none">
                    <FunnelIcon class="h-5 w-5" /> Filters
                </button>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a
                    href="/sales/quotations-export"
                    class="inline-flex items-center gap-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-4 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-700 transition-all group"
                >
                    <ArrowDownTrayIcon class="h-5 w-5 text-emerald-500 group-hover:scale-110 transition-transform" />
                    Export
                </a>
                <button
                    @click="showImportModal = true"
                    class="inline-flex items-center gap-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-4 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-700 transition-all group"
                >
                    <ArrowUpTrayIcon class="h-5 w-5 text-blue-500 group-hover:scale-110 transition-transform" />
                    Import
                </button>
                <Link href="/sales/quotations/create" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 hover:from-blue-500 hover:to-blue-400 transition-all">
                    <PlusIcon class="h-5 w-5" /> New Quotation
                </Link>
            </div>
        </div>

        <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-2">
            <div v-if="showFilters" class="mb-6 rounded-2xl glass-card p-4 shadow-sm">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-500 dark:text-slate-500 dark:text-slate-400 mb-2">Status</label>
                        <select v-model="selectedStatus" class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 ring-1 ring-slate-200 dark:ring-0">
                            <option value="">All Status</option>
                            <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </Transition>

        <div class="rounded-2xl glass-card overflow-hidden shadow-sm">
            <div class="overflow-x-auto overflow-y-auto max-h-[600px]">
                <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-100 dark:divide-slate-800">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700">
                            <th @click="sort('number')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group">
                                <div class="flex items-center gap-1">
                                    Number
                                    <span v-if="sortField === 'number'" class="text-blue-600 dark:text-blue-400">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th @click="sort('customer_name')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group">
                                <div class="flex items-center gap-1">
                                    Customer
                                    <span v-if="sortField === 'customer_name'" class="text-blue-600 dark:text-blue-400">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th @click="sort('quotation_date')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group">
                                <div class="flex items-center gap-1">
                                    Date
                                    <span v-if="sortField === 'quotation_date'" class="text-blue-600 dark:text-blue-400">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th @click="sort('items_count')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group">
                                <div class="flex items-center justify-center gap-1">
                                    Items
                                    <span v-if="sortField === 'items_count'" class="text-blue-600 dark:text-blue-400">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th @click="sort('valid_until')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group">
                                <div class="flex items-center gap-1">
                                    Valid Until
                                    <span v-if="sortField === 'valid_until'" class="text-blue-600 dark:text-blue-400">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th @click="sort('total')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-right text-xs font-bold text-slate-500 dark:text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group">
                                <div class="flex items-center justify-end gap-1">
                                    Total
                                    <span v-if="sortField === 'total'" class="text-blue-600 dark:text-blue-400">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th @click="sort('status')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group">
                                <div class="flex items-center justify-center gap-1">
                                    Status
                                    <span v-if="sortField === 'status'" class="text-blue-600 dark:text-blue-400">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-right text-xs font-bold text-slate-500 dark:text-slate-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-100 dark:divide-slate-800">
                        <tr v-for="q in quotations.data" :key="q.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors">
                            <td class="px-4 py-2 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-900 dark:bg-slate-800">
                                        <DocumentDuplicateIcon class="h-5 w-5 text-indigo-500 dark:text-indigo-400" />
                                    </div>
                                    <div class="text-sm font-medium text-slate-900 dark:text-white">{{ q.number }}</div>
                                </div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm text-slate-700 dark:text-white">{{ q.customer?.name }}</td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm text-slate-500 dark:text-slate-600 dark:text-slate-300">{{ formatDate(q.quotation_date) }}</td>
                            <td class="px-4 py-2 whitespace-nowrap text-center text-sm text-slate-600 dark:text-slate-300">
                                {{ q.items_count }}
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm text-slate-500 dark:text-slate-600 dark:text-slate-300">{{ formatDate(q.valid_until) }}</td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm text-right text-slate-900 dark:text-white font-medium">{{ formatCurrency(q.total) }}</td>
                            <td class="px-4 py-2 whitespace-nowrap text-center">
                                <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium" :class="getStatusBadge(q.status)">{{ q.status?.toUpperCase() }}</span>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <Link :href="`/sales/quotations/${q.id}`" class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:bg-slate-100 dark:hover:bg-slate-800/50 transition-colors" title="View Details">
                                        <EyeIcon class="h-4 w-4" />
                                    </Link>
                                    <Link v-if="!['accepted', 'converted'].includes(q.status)" :href="`/sales/quotations/${q.id}/edit`" class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-blue-500 hover:bg-slate-100 dark:hover:bg-slate-800/50 transition-colors" title="Edit Quotation">
                                        <PencilSquareIcon class="h-4 w-4" />
                                    </Link>
                                    <button v-if="q.status === 'draft'" @click="deleteQuotation(q)" class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-red-500 hover:bg-slate-100 dark:hover:bg-slate-800/50 transition-colors" title="Delete Quotation">
                                        <TrashIcon class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="quotations.data.length === 0">
                            <td colspan="7" class="px-4 py-12 text-center">
                                <DocumentDuplicateIcon class="mx-auto h-12 w-12 text-slate-600 dark:text-slate-300 dark:text-slate-600" />
                                <h3 class="mt-2 text-sm font-medium text-slate-600 dark:text-slate-600 dark:text-slate-300">No quotations found</h3>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400 dark:text-slate-500">Create a new quotation for your customers.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div v-if="quotations.last_page > 1" class="border-t border-slate-100 dark:border-slate-200 dark:border-slate-800 px-6 py-4 flex items-center justify-between">
                <p class="text-sm text-slate-500 dark:text-slate-500 dark:text-slate-400">
                    Showing {{ quotations.from }} to {{ quotations.to }} of {{ quotations.total }} quotations
                </p>
                <div class="flex items-center gap-2">
                    <Link
                        v-for="link in quotations.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        class="px-3 py-1.5 rounded-lg text-sm transition-colors"
                        :class="link.active 
                            ? 'bg-blue-600 text-slate-900 dark:text-white' 
                            : link.url 
                                ? 'text-slate-500 dark:text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 hover:text-slate-900 dark:hover:text-slate-900 dark:text-white' 
                                : 'text-slate-600 dark:text-slate-300 dark:text-white cursor-not-allowed'"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>

        <!-- Feature Guide -->
        <div class="mt-12">
            <div class="flex items-center gap-2 mb-8 px-1">
                <div class="h-px flex-1 bg-slate-200 dark:bg-slate-900 dark:bg-slate-800"></div>
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 dark:text-slate-500 uppercase tracking-widest">Quotations & CRM Guide</span>
                <div class="h-px flex-1 bg-slate-200 dark:bg-slate-900 dark:bg-slate-800"></div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-300 dark:hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                            <ClockIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-900 dark:text-slate-200 text-sm">Validity Tracking</h4>
                    </div>
                    <p class="text-xs text-slate-600 dark:text-slate-500 dark:text-slate-400 leading-relaxed">
                        Keep track of <strong>Valid Until</strong> dates. Expired quotations are automatically flagged, helping you follow up with customers promptly.
                    </p>
                </div>

                <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-300 dark:hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                            <CheckCircleIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-900 dark:text-slate-200 text-sm">Status Workflow</h4>
                    </div>
                    <p class="text-xs text-slate-600 dark:text-slate-500 dark:text-slate-400 leading-relaxed">
                        Manage the lifecycle from <strong>Draft</strong> to <strong>Sent</strong> and <strong>Accepted</strong>. Use this to monitor your sales pipeline.
                    </p>
                </div>

                <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-300 dark:hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400">
                            <ClipboardDocumentCheckIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-900 dark:text-slate-200 text-sm">One-Click Conversion</h4>
                    </div>
                    <p class="text-xs text-slate-600 dark:text-slate-500 dark:text-slate-400 leading-relaxed">
                        Once a quotation is <strong>Accepted</strong>, you can quickly convert it into a Sales Order without re-entering any data.
                    </p>
                </div>
                
                <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-300 dark:hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-purple-50 dark:bg-purple-500/10 text-purple-600 dark:text-purple-400">
                            <ShieldCheckIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-900 dark:text-slate-200 text-sm">Internal Notes</h4>
                    </div>
                    <p class="text-xs text-slate-600 dark:text-slate-500 dark:text-slate-400 leading-relaxed">
                        Use the notes field for <strong>Internal Comments</strong> or specific customer requirements that don't need to appear on the final printout.
                    </p>
                </div>
            </div>
        </div>

        <!-- Import Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showImportModal" class="fixed inset-0 z-50 flex items-center justify-center">
                    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showImportModal = false"></div>
                    <div class="relative w-full max-w-lg mx-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-2xl">
                        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Import Quotations</h3>
                            <button @click="showImportModal = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                        <form @submit.prevent="submitImport" class="p-6">
                            <div class="mb-4">
                                <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                                    Upload an Excel file (.xlsx, .xls, .csv) to import Quotations. Rows with the same Customer Code + Quotation Date will be grouped into one Quotation.
                                </p>
                                
                                <div class="flex items-center gap-4 mb-4">
                                    <a :href="`/sales/quotations-template?with_data=${includeData ? 1 : 0}`" class="inline-flex items-center gap-2 text-sm font-medium text-blue-500 hover:text-blue-400 transition-colors">
                                        <ArrowDownTrayIcon class="h-4 w-4" /> Download Template
                                    </a>
                                    
                                    <div class="flex items-center gap-2">
                                        <input
                                            id="include_data"
                                            type="checkbox"
                                            v-model="includeData"
                                            class="h-3.5 w-3.5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded dark:bg-slate-700 dark:border-slate-600"
                                        />
                                        <label for="include_data" class="text-xs text-slate-500 dark:text-slate-400 cursor-pointer select-none">
                                            Include Existing Data
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Select File</label>
                                <input ref="fileInput" type="file" @change="handleFileChange" accept=".xlsx,.xls,.csv" class="block w-full text-sm text-slate-500 dark:text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-blue-500/10 file:text-blue-500 hover:file:bg-blue-500/20 transition-all cursor-pointer" />
                                <p v-if="importForm.errors.file" class="mt-1 text-sm text-red-500">{{ importForm.errors.file }}</p>
                            </div>

                            <div class="mb-6">
                                <label class="flex items-start gap-3 p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                    <div class="flex items-center h-5">
                                        <input 
                                            v-model="importForm.overwrite" 
                                            type="checkbox" 
                                            class="w-4 h-4 rounded border-slate-600 bg-slate-700 text-blue-600 focus:ring-blue-500/50 focus:ring-offset-0"
                                        />
                                    </div>
                                    <div class="flex-1">
                                        <span class="block text-sm font-medium text-slate-600 dark:text-slate-300">Overwrite Existing Data</span>
                                        <p class="text-xs text-slate-500 mt-1">
                                            If checked, existing Draft or Sent quotations matching the Customer + Date will be overwritten (items replaced).
                                        </p>
                                        <div v-if="importForm.overwrite" class="mt-2 flex items-start gap-2 text-xs text-amber-500 bg-amber-500/10 p-2 rounded-lg border border-amber-500/20">
                                            <ExclamationTriangleIcon class="h-4 w-4 shrink-0" />
                                            <span>Warning: This will delete and replace all items in matching existing quotations.</span>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <div class="flex items-center justify-end gap-3">
                                <button type="button" @click="showImportModal = false" class="px-4 py-2 rounded-xl text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">Cancel</button>
                                <button type="submit" :disabled="!importForm.file || importForm.processing" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 hover:from-blue-500 hover:to-blue-400 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                    <ArrowUpTrayIcon class="h-4 w-4" />
                                    {{ importForm.processing ? 'Importing...' : 'Import' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>
