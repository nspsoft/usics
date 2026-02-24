<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatNumber, formatCurrency } from '@/helpers';
import {
    PlusIcon,
    MagnifyingGlassIcon,
    PencilSquareIcon,
    TrashIcon,
    EyeIcon,
    CubeTransparentIcon,
    ClipboardDocumentListIcon,
    ArrowPathRoundedSquareIcon,
    CalculatorIcon,
    ArrowDownTrayIcon,
    ArrowUpTrayIcon,
} from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    boms: Object,
    filters: Object,
    statuses: Array,
});

const search = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || '');

const applyFilters = debounce(() => {
    router.get('/manufacturing/boms', {
        search: search.value || undefined,
        status: selectedStatus.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, selectedStatus], applyFilters);

const deleteBom = (bom) => {
    if (confirm(`Are you sure you want to delete "${bom.name}"?`)) {
        router.delete(`/manufacturing/boms/${bom.id}`);
    }
};

const getStatusBadge = (status) => {
    const badges = {
        draft: 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30',
        active: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
        archived: 'bg-amber-500/20 text-amber-400 border-amber-500/30',
    };
    return badges[status] || 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30';
};

const showImportModal = ref(false);
const importForm = useForm({
    file: null,
});

const openImportModal = () => {
    showImportModal.value = true;
};

const closeImportModal = () => {
    showImportModal.value = false;
    importForm.reset();
};

const handleFileChange = (e) => {
    importForm.file = e.target.files[0];
};

const submitImport = () => {
    importForm.post(route('manufacturing.boms.import'), {
        preserveScroll: true,
        onSuccess: () => closeImportModal(),
    });
};

const exportBoms = () => {
    window.location.href = route('manufacturing.boms.export');
};

</script>

<template>
    <Head title="Bill of Materials" />
    
    <AppLayout title="Bill of Materials">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div class="flex items-center gap-3">
                <div class="relative flex-1 sm:w-80">
                    <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500" />
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search BOMs..."
                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 py-2.5 pl-10 pr-4 text-sm text-slate-900 dark:text-white placeholder:text-slate-500 focus:bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-blue-500/50 transition-all"
                    />
                </div>
                <select
                    v-model="selectedStatus"
                    class="rounded-xl border-0 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                >
                    <option value="">All Status</option>
                    <option v-for="status in statuses" :key="status.value" :value="status.value">
                        {{ status.label }}
                    </option>
                </select>
            </div>
            
            <div class="flex gap-2">
                <button 
                    @click="exportBoms"
                    class="inline-flex items-center gap-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-4 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition-all"
                    title="Export to Excel"
                >
                    <ArrowDownTrayIcon class="h-5 w-5" />
                    <span class="hidden md:inline">Export</span>
                </button>

                <button 
                    @click="openImportModal"
                    class="inline-flex items-center gap-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-4 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition-all"
                    title="Import from Excel"
                >
                    <ArrowUpTrayIcon class="h-5 w-5" />
                    <span class="hidden md:inline">Import</span>
                </button>

                <Link
                    :href="route('manufacturing.boms.create')"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 hover:from-blue-500 hover:to-blue-400 transition-all"
                >
                    <PlusIcon class="h-5 w-5" />
                    Create BOM
                </Link>
            </div>
        </div>

        <div class="rounded-2xl glass-card overflow-hidden">
            <div class="overflow-x-auto overflow-y-auto max-h-[600px]">
                <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700">
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">BOM Detail</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Finished Product</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Components</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Yield Qty</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr v-for="bom in boms.data" :key="bom.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors">
                            <td class="px-4 py-2 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-orange-600/20 to-red-600/20 border border-orange-500/30">
                                        <ClipboardDocumentListIcon class="h-5 w-5 text-orange-400" />
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-slate-900 dark:text-white">{{ bom.name }}</div>
                                        <div class="text-[10px] text-slate-500 font-mono">VER: {{ bom.version }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <div class="text-sm text-slate-900 dark:text-white font-medium">{{ bom.product?.name }}</div>
                                <div class="text-[10px] text-slate-500 font-mono">{{ bom.product?.sku }}</div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-center text-sm text-slate-600 dark:text-slate-300">
                                {{ bom.components_count }} items
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-center text-sm text-slate-900 dark:text-white font-medium">
                                {{ formatNumber(bom.qty) }} <span class="text-[10px] text-slate-500 font-normal ml-0.5">{{ bom.unit?.symbol || 'pcs' }}</span>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-center">
                                <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium capitalize" :class="getStatusBadge(bom.status)">{{ bom.status }}</span>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <Link :href="route('manufacturing.boms.show', bom.id)" class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors">
                                        <EyeIcon class="h-4 w-4" />
                                    </Link>
                                    <Link :href="route('manufacturing.boms.edit', bom.id)" class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-blue-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors">
                                        <PencilSquareIcon class="h-4 w-4" />
                                    </Link>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="boms.data.length === 0">
                            <td colspan="6" class="px-4 py-12 text-center text-slate-500 italic">No definitions found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div v-if="boms.last_page > 1" class="border-t border-slate-200 dark:border-slate-800 px-6 py-4 flex items-center justify-between">
                <Pagination :links="boms.links" />
            </div>
        </div>

        <!-- Feature Guide -->
        <div class="mt-12">
            <div class="flex items-center gap-2 mb-4 px-1">
                <div class="h-px flex-1 bg-slate-50 dark:bg-slate-800"></div>
                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Recipe Management Guide</span>
                <div class="h-px flex-1 bg-slate-50 dark:bg-slate-800"></div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-orange-500/10 text-orange-400">
                            <ClipboardDocumentListIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Product Recipes</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        BOMs define the <strong>Exact Ingredients</strong> and quantities required to produce a finished good. Ensure yields are accurate for precise cost calculation.
                    </p>
                </div>

                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-blue-500/10 text-blue-400">
                            <ArrowPathRoundedSquareIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Version Control</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Maintain <strong>Multiple Iterations</strong> of a recipe. Archive old versions while rolling out improved production processes to track historical performance.
                    </p>
                </div>

                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-400">
                            <CalculatorIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Cost Analysis</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Linked component prices drive the <strong>Standard Cost</strong> of your products. Always check for price updates in "Purchasing" after major market shifts.
                    </p>
                </div>
                
                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-purple-500/10 text-purple-400">
                            <CubeTransparentIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Material Planning</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Validated BOMs are essential for <strong>MRP (Material Requirements Planning)</strong>. They ensure you never run out of raw materials during production runs.
                    </p>
                </div>
            </div>
        </div>

        <Modal :show="showImportModal" @close="closeImportModal">
            <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl">
                <h2 class="text-lg font-medium text-slate-900 dark:text-white mb-4">
                    Import Bill of Materials
                </h2>
                
                <div class="mb-4">
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-2">
                        Upload an Excel file (.xlsx, .xls) to import BOM definitions. Rows with the same BOM Code and Version will be grouped into one BOM.
                    </p>
                    
                    <a :href="route('manufacturing.boms.template')" class="inline-flex items-center gap-1 text-sm text-blue-600 hover:text-blue-500 mb-4 font-medium">
                        <ArrowDownTrayIcon class="h-4 w-4" />
                        Download Template
                    </a>

                    <p class="text-xs text-slate-400 dark:text-slate-500 mb-4">
                        Required: BOM Code, Product Code (finished good), Component Code, Component Qty.
                    </p>
                    
                    <div class="flex items-center justify-center w-full">
                        <label for="bom-import-file" class="flex flex-col items-center justify-center w-full h-32 border-2 border-slate-300 border-dashed rounded-lg cursor-pointer bg-slate-50 dark:hover:bg-slate-800 dark:bg-slate-900 hover:bg-slate-100 dark:border-slate-700 dark:hover:border-slate-500 transition-colors">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <ArrowUpTrayIcon class="w-8 h-8 mb-2 text-slate-500 dark:text-slate-400" />
                                <p class="mb-2 text-sm text-slate-500 dark:text-slate-400">
                                    <span class="font-semibold">Click to upload</span>
                                </p>
                                <p class="text-xs text-slate-500 dark:text-slate-400" v-if="importForm.file">
                                    {{ importForm.file.name }}
                                </p>
                            </div>
                            <input id="bom-import-file" type="file" class="hidden" @change="handleFileChange" accept=".xlsx, .xls, .csv" />
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
    </AppLayout>
</template>

