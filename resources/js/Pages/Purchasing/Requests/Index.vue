<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    PlusIcon,
    DocumentTextIcon,
    EyeIcon,
    PencilSquareIcon,
    TrashIcon,
    InformationCircleIcon,
    CheckIcon,
    XMarkIcon,
    ArrowPathIcon,
    DocumentCheckIcon,
    ShoppingCartIcon,
    ClipboardDocumentListIcon,
    ChevronUpIcon,
    ChevronDownIcon,
    ArrowDownTrayIcon,
    ArrowUpTrayIcon,
} from '@heroicons/vue/24/outline';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import debounce from 'lodash/debounce';
import { ref } from 'vue';

const props = defineProps({
    requests: Object,
    filters: Object,
});

const showImportModal = ref(false);
const includeData = ref(false);
const importForm = useForm({
    file: null,
    overwrite: false,
});

const applyFilters = debounce(() => {
    router.get('/purchasing/requests', {
        search: props.filters.search,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

const getStatusBadge = (status) => {
    if (!status) return 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30';
    const badges = {
        draft: 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30',
        pending: 'bg-amber-500/20 text-amber-400 border-amber-500/30',
        approved: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
        rejected: 'bg-red-500/20 text-red-400 border-red-500/30',
    };
    return badges[status] || 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30';
};

const deleteRequest = (id) => {
    if (!id) return;
    if (confirm('Are you sure you want to delete this purchase request?')) {
        router.delete(`/purchasing/requests/${id}`);
    }
};

const formatDate = (date) => {
    if (!date) return '-';
    try {
        return new Date(date).toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        });
    } catch (e) {
        return date;
    }
};

const openImportModal = () => {
    showImportModal.value = true;
};

const closeImportModal = () => {
    showImportModal.value = false;
    importForm.reset();
};

const submitImport = () => {
    importForm.post(route('requests.import'), {
        preserveScroll: true,
        onSuccess: () => closeImportModal(),
    });
};

const handleFileChange = (e) => {
    importForm.file = e.target.files[0];
};

const exportRequests = () => {
    window.location.href = route('requests.export');
};
</script>

<template>
    <Head title="Purchase Requests" />
    
    <AppLayout title="Purchase Requests">
        <template v-if="requests">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div class="flex-1">
                     <p class="text-slate-500 dark:text-slate-400 italic text-sm">Manage purchase requests from departments</p>
                </div>
               
                <div class="flex items-center gap-2">
                    <div class="relative">
                        <input 
                            v-model="filters.search" 
                            @input="applyFilters"
                            type="text" 
                            placeholder="Search requests..." 
                            class="pl-4 pr-10 py-2.5 text-sm rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 focus:ring-blue-500 focus:border-blue-500 w-full md:w-64"
                        >
                    </div>

                    <button 
                        @click="exportRequests"
                        class="hidden md:inline-flex items-center gap-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-4 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition-all"
                        title="Export to Excel"
                    >
                        <ArrowDownTrayIcon class="h-5 w-5" />
                        <span class="hidden md:inline">Export</span>
                    </button>

                    <button 
                        @click="openImportModal"
                        class="hidden md:inline-flex items-center gap-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-4 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition-all"
                        title="Import from Excel"
                    >
                        <ArrowUpTrayIcon class="h-5 w-5" />
                        <span class="hidden md:inline">Import</span>
                    </button>

                    <Link
                        href="/purchasing/requests/create"
                        class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 hover:from-blue-500 hover:to-blue-400 transition-all"
                    >
                        <PlusIcon class="h-5 w-5" />
                        <span class="hidden md:inline">New Request</span>
                        <span class="md:hidden">New</span>
                    </Link>
                </div>
            </div>

            <div class="rounded-2xl glass-card overflow-hidden">
                <div class="overflow-x-auto overflow-y-auto max-h-[600px]">
                    <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700">
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                    <div class="flex items-center gap-1">
                                        PR Number
                                    </div>
                                </th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                    <div class="flex items-center gap-1">
                                        Date
                                    </div>
                                </th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                    <div class="flex items-center gap-1">
                                        Department
                                    </div>
                                </th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                    <div class="flex items-center gap-1">
                                        Requester
                                    </div>
                                </th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Items</th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                    <div class="flex items-center justify-center gap-1">
                                        Status
                                    </div>
                                </th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="request in requests.data" :key="request.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors">
                                <td class="px-4 py-2 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 dark:bg-slate-800 font-mono text-xs text-slate-500">
                                            PR
                                        </div>
                                        <Link :href="`/purchasing/requests/${request.id}`" class="text-sm font-medium text-slate-900 dark:text-white hover:text-blue-400">
                                            {{ request.pr_number }}
                                        </Link>
                                    </div>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-slate-600 dark:text-slate-300">
                                    {{ formatDate(request.request_date) }}
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-slate-600 dark:text-slate-300 font-medium">
                                    {{ request.department || '-' }}
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-slate-600 dark:text-slate-300">
                                    {{ request.requester || '-' }}
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-center text-sm text-slate-600 dark:text-slate-300">
                                    {{ request.items_count || 0 }}
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium" :class="getStatusBadge(request.status)">{{ request.status?.toUpperCase() || 'N/A' }}</span>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link :href="`/purchasing/requests/${request.id}`" class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors">
                                            <EyeIcon class="h-4 w-4" />
                                        </Link>
                                        <Link v-if="request.status === 'draft' || request.status === 'approved'" :href="`/purchasing/requests/${request.id}/edit`" class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-blue-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors">
                                            <PencilSquareIcon class="h-4 w-4" />
                                        </Link>
                                        <button @click="deleteRequest(request.id)" class="p-2 text-slate-500 dark:text-slate-400 hover:text-red-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 rounded-lg transition-colors">
                                            <TrashIcon class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="requests.data && requests.data.length === 0">
                                <td colspan="7" class="px-4 py-12 text-center text-slate-500 italic">No purchase requests found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div v-if="requests.last_page > 1" class="border-t border-slate-200 dark:border-slate-800 px-6 py-4">
                    <Pagination :links="requests.links" />
                </div>
            </div>

            <!-- Guide Section -->
            <div class="mt-12">
                <div class="flex items-center gap-2 mb-4 px-1">
                    <div class="h-px flex-1 bg-slate-50 dark:bg-slate-800"></div>
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Procurement Guide</span>
                    <div class="h-px flex-1 bg-slate-50 dark:bg-slate-800"></div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2.5 rounded-xl bg-blue-500/10 text-blue-400">
                                <PlusIcon class="h-5 w-5" />
                            </div>
                            <h4 class="font-bold text-slate-200 text-sm">Create Request</h4>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                            Department members can initiate a <strong>Purchase Request (PR)</strong>.
                        </p>
                    </div>

                    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2.5 rounded-xl bg-orange-500/10 text-orange-400">
                                <DocumentCheckIcon class="h-5 w-5" />
                            </div>
                            <h4 class="font-bold text-slate-200 text-sm">Approval Flow</h4>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                            PRs go through a <strong>Status Workflow</strong>.
                        </p>
                    </div>

                    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-400">
                                <ShoppingCartIcon class="h-5 w-5" />
                            </div>
                            <h4 class="font-bold text-slate-200 text-sm">Convert to PO</h4>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                            Save time by <strong>Linking approved PRs</strong>.
                        </p>
                    </div>
                    
                    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2.5 rounded-xl bg-purple-500/10 text-purple-400">
                                <ClipboardDocumentListIcon class="h-5 w-5" />
                            </div>
                            <h4 class="font-bold text-slate-200 text-sm">Tracking</h4>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                            Monitor the <strong>Quantity Requested</strong>.
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Import Modal -->
            <Modal :show="showImportModal" @close="closeImportModal">
                <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl">
                    <h2 class="text-lg font-medium text-slate-900 dark:text-white mb-4">
                        Import Purchase Requests
                    </h2>
                    
                    <div class="mb-4">
                        <p class="text-sm text-slate-500 dark:text-slate-400 mb-2">
                            Upload an Excel file (.xlsx, .xls) to import Purchase Requests.
                        </p>
                        
                        <div class="bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-100 dark:border-indigo-800 rounded-xl p-4 mb-4 relative overflow-hidden">
                            <div class="absolute right-0 top-0 w-24 h-24 bg-indigo-500/10 rounded-full blur-xl -mr-10 -mt-10"></div>
                            
                            <h4 class="text-sm font-bold text-indigo-900 dark:text-indigo-300 mb-3 flex items-center gap-2">
                                <DocumentCheckIcon class="h-4 w-4" />
                                Import Options
                            </h4>
                            
                            <div class="space-y-4">
                                <label class="flex items-start gap-3 cursor-pointer group">
                                    <div class="pt-0.5">
                                        <input 
                                            type="checkbox" 
                                            v-model="includeData"
                                            class="w-4 h-4 rounded border-indigo-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 transition-all dark:bg-slate-800 dark:border-slate-600"
                                        >
                                    </div>
                                    <div class="flex-1">
                                        <span class="block text-sm font-medium text-slate-700 dark:text-slate-200 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">Include Existing Draft PRs in Template</span>
                                        <span class="block text-xs text-slate-500 dark:text-slate-400 mt-0.5">Download template yang otomatis terisi seluruh Purchase Request berstatus Draft di database saat ini.</span>
                                    </div>
                                </label>
                                
                                <label class="flex items-start gap-3 cursor-pointer group">
                                    <div class="pt-0.5">
                                        <input 
                                            type="checkbox" 
                                            v-model="importForm.overwrite"
                                            class="w-4 h-4 rounded border-amber-300 text-amber-600 shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200 focus:ring-opacity-50 transition-all dark:bg-slate-800 dark:border-slate-600"
                                        >
                                    </div>
                                    <div class="flex-1">
                                        <span class="block text-sm font-medium text-slate-700 dark:text-slate-200 group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors">Overwrite Existing PR Data</span>
                                        <span class="block text-xs text-slate-500 dark:text-slate-400 mt-0.5 mb-2">Jika file Excel menyertakan PR Number yang valid, sistem akan menghapus seluruh item lama pada PR tersebut lalu menimpanya dengan daftar item baru dari baris file ini.</span>
                                        
                                        <!-- Warning Banner -->
                                        <div v-show="importForm.overwrite" class="mt-2 text-xs p-2 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800/50 flex items-start gap-2">
                                            <InformationCircleIcon class="h-4 w-4 shrink-0 mt-0.5" />
                                            <span>Hanya Purchase Request yang masih berstatus <strong>Draft</strong> yang dizinkan untuk ditimpa paksa (Overwrite).</span>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <a :href="includeData ? route('purchasing.requests.template', { with_data: 1 }) : route('purchasing.requests.template')" target="_blank" class="inline-flex items-center gap-1 text-sm text-blue-600 hover:text-blue-500 mb-4 font-medium">
                            <ArrowDownTrayIcon class="h-4 w-4" />
                            Download Template
                        </a>

                        <p class="text-xs text-slate-400 dark:text-slate-500 mb-4">
                            Required columns: Date, Department, Requester, Product Code, Quantity. Jika PR Number kosong, baris baru akan digrouping otomatis hari itu.
                        </p>
                        
                        <div class="flex items-center justify-center w-full">
                            <label for="dropzone-file" class="flex flex-col items-center justify-center w-full h-32 border-2 border-slate-300 border-dashed rounded-lg cursor-pointer bg-slate-50 dark:hover:bg-slate-800 dark:bg-slate-900 hover:bg-slate-100 dark:border-slate-700 dark:hover:border-slate-500 transition-colors">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <ArrowUpTrayIcon class="w-8 h-8 mb-2 text-slate-500 dark:text-slate-400" />
                                    <p class="mb-2 text-sm text-slate-500 dark:text-slate-400">
                                        <span class="font-semibold">Click to upload</span>
                                    </p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400" v-if="importForm.file">
                                        {{ importForm.file.name }}
                                    </p>
                                </div>
                                <input id="dropzone-file" type="file" class="hidden" @change="handleFileChange" accept=".xlsx, .xls, .csv" />
                            </label>
                        </div>
                        <div v-if="importForm.errors.file" class="text-red-500 text-xs mt-1">{{ importForm.errors.file }}</div>
                    </div>

                    <div class="flex items-center justify-end gap-3 mt-6">
                        <SecondaryButton @click="closeImportModal"> Cancel </SecondaryButton>
                        <PrimaryButton 
                            @click="submitImport" 
                            :class="{ 'opacity-25': importForm.processing }" 
                            :disabled="!importForm.file || importForm.processing"
                        >
                            Import Data
                        </PrimaryButton>
                    </div>
                </div>
            </Modal>

        </template>
        <div v-else class="text-slate-900 dark:text-white text-center py-20">
            Loading requests...
        </div>
    </AppLayout>
</template>



