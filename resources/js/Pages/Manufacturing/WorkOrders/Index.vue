<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import {
    PlusIcon,
    MagnifyingGlassIcon,
    EyeIcon,
    PlayIcon,
    CheckCircleIcon,
    CogIcon,
    FunnelIcon,
    PencilIcon,
    ArrowUpTrayIcon,
    ArrowDownTrayIcon,
} from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';
import { formatNumber, formatCurrency } from '@/helpers';

const props = defineProps({
    workOrders: Object,
    filters: Object,
    statuses: Array,
    priorities: Array,
    productionTypes: Array,
});

const search = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || '');
const selectedPriority = ref(props.filters.priority || '');
const selectedType = ref(props.filters.production_type || '');
const showFilters = ref(false);
const showImportModal = ref(false);

const importForm = useForm({
    file: null,
});

const applyFilters = debounce(() => {
    router.get('/manufacturing/work-orders', {
        search: search.value || undefined,
        status: selectedStatus.value || undefined,
        priority: selectedPriority.value || undefined,
        production_type: selectedType.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, selectedStatus, selectedPriority, selectedType], applyFilters);

const clearFilters = () => {
    search.value = '';
    selectedStatus.value = '';
    selectedPriority.value = '';
    selectedType.value = '';
};

const openImport = () => {
    importForm.reset();
    importForm.clearErrors();
    showImportModal.value = true;
};

const closeImport = () => {
    showImportModal.value = false;
    importForm.reset();
    importForm.clearErrors();
};

const submitImport = () => {
    importForm.post(route('manufacturing.work-orders.import'), {
        forceFormData: true,
        onSuccess: () => closeImport(),
    });
};

const getStatusBadge = (status) => {
    const badges = {
        draft: 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30',
        confirmed: 'bg-blue-500/20 text-blue-400 border-blue-500/30',
        in_progress: 'bg-amber-500/20 text-amber-400 border-amber-500/30',
        completed: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
        cancelled: 'bg-red-500/20 text-red-400 border-red-500/30',
    };
    return badges[status] || 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30';
};

const getStatusLabel = (status) => {
    const labels = {
        draft: 'Draft',
        confirmed: 'Confirmed',
        in_progress: 'In Progress',
        completed: 'Completed',
        cancelled: 'Cancelled',
    };
    return labels[status] || status;
};

const getPriorityBadge = (priority) => {
    const badges = {
        low: 'bg-slate-500/20 text-slate-500 dark:text-slate-400',
        normal: 'bg-blue-500/20 text-blue-400',
        high: 'bg-amber-500/20 text-amber-400',
        urgent: 'bg-red-500/20 text-red-400',
    };
    return badges[priority] || 'bg-slate-500/20 text-slate-500 dark:text-slate-400';
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

</script>

<template>
    <Head title="Work Orders" />
    
    <AppLayout title="Work Orders">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div class="flex items-center gap-3">
                <div class="relative flex-1 sm:w-80">
                    <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500" />
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search work orders..."
                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 py-2.5 pl-10 pr-4 text-sm text-slate-900 dark:text-white placeholder:text-slate-500 focus:bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-blue-500/50 transition-all"
                    />
                </div>
                <button 
                    @click="showFilters = !showFilters"
                    class="flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 px-4 py-2.5 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors"
                    :class="{ 'ring-2 ring-blue-500/50': showFilters }"
                >
                    <FunnelIcon class="h-5 w-5" />
                    Filters
                </button>
            </div>
            
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    @click="openImport"
                    class="inline-flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-800/50 px-4 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                >
                    <ArrowUpTrayIcon class="h-5 w-5" />
                    Import
                </button>

                <Link
                    href="/manufacturing/work-orders/create"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 hover:from-blue-500 hover:to-blue-400 transition-all"
                >
                    <PlusIcon class="h-5 w-5" />
                    Create WO
                </Link>
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
                        <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Status</label>
                        <select
                            v-model="selectedStatus"
                            class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                        >
                            <option value="">All Status</option>
                            <option v-for="status in statuses" :key="status.value" :value="status.value">
                                {{ status.label }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Priority</label>
                        <select
                            v-model="selectedPriority"
                            class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                        >
                            <option value="">All Priority</option>
                            <option v-for="p in priorities" :key="p.value" :value="p.value">
                                {{ p.label }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Production Type</label>
                        <select
                            v-model="selectedType"
                            class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                        >
                            <option value="">All Types</option>
                            <option v-for="type in productionTypes" :key="type.value" :value="type.value">
                                {{ type.label }}
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
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">WO Number</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Product</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Output Qty</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Progress</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Schedule</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Priority</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Type</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr v-for="wo in workOrders.data" :key="wo.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors">
                            <td class="px-4 py-2 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-cyan-600/20 to-blue-600/20 border border-cyan-500/30">
                                        <CogIcon class="h-5 w-5 text-cyan-400" />
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-slate-900 dark:text-white">{{ wo.wo_number }}</div>
                                        <div class="text-[10px] text-slate-500 uppercase">{{ wo.bom?.name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <div class="text-sm text-slate-900 dark:text-white font-medium">{{ wo.product?.name }}</div>
                                <div class="text-[10px] text-slate-500 font-mono">{{ wo.product?.sku }}</div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-center">
                                <span class="text-sm text-slate-900 dark:text-white font-medium">{{ formatNumber(wo.qty_produced) }}</span>
                                <span class="text-[10px] text-slate-500 ml-1">/ {{ formatNumber(wo.qty_planned) }}</span>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <div class="w-24 mx-auto">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 bg-slate-50 dark:bg-slate-800 rounded-full h-1.5 overflow-hidden">
                                            <div 
                                                class="bg-gradient-to-r from-cyan-500 to-blue-600 h-full transition-all duration-500"
                                                :style="{ width: `${wo.progress_percent}%` }"
                                            ></div>
                                        </div>
                                        <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 w-8">{{ Math.round(wo.progress_percent) }}%</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <div class="text-sm text-slate-600 dark:text-slate-300">{{ formatDate(wo.planned_start) }}</div>
                                <div class="text-[10px] text-slate-500 uppercase">thru {{ formatDate(wo.planned_end) }}</div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-center">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase ring-1 ring-inset" :class="getPriorityBadge(wo.priority)">
                                    {{ wo.priority }}
                                </span>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-center">
                                <span 
                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase ring-1 ring-inset"
                                    :class="wo.production_type === 'subcontract' ? 'bg-amber-500/20 text-amber-400 ring-amber-500/30' : 'bg-blue-500/20 text-blue-400 ring-blue-500/30'"
                                >
                                    {{ wo.production_type }}
                                </span>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-center">
                                <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium" :class="getStatusBadge(wo.status)">
                                    {{ getStatusLabel(wo.status) }}
                                </span>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-right">
                                <Link :href="route('manufacturing.work-orders.show', wo.id)" class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors" title="View Detail">
                                    <EyeIcon class="h-4 w-4" />
                                </Link>
                                <Link 
                                    v-if="wo.status === 'draft'"
                                    :href="route('manufacturing.work-orders.edit', wo.id)" 
                                    class="p-2 rounded-lg text-blue-400 hover:text-blue-300 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors"
                                    title="Edit Work Order"
                                >
                                    <PencilIcon class="h-4 w-4" />
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="workOrders.data.length === 0">
                            <td colspan="9" class="px-4 py-12 text-center text-slate-500 italic">No active production runs found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="workOrders.last_page > 1" class="border-t border-slate-200 dark:border-slate-800 px-6 py-4 flex items-center justify-between">
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Showing {{ workOrders.from }} to {{ workOrders.to }} of {{ workOrders.total }} orders
                </p>
                <div class="flex items-center gap-2">
                    <Link
                        v-for="link in workOrders.links"
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
                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Production execution Guide</span>
                <div class="h-px flex-1 bg-slate-50 dark:bg-slate-800"></div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-cyan-500/10 text-cyan-400">
                            <CogIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Execution Control</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Transition Work Orders from <strong>Draft</strong> to <strong>Started</strong> to signal the floor team. The system will automatically allocate components based on the BOM.
                    </p>
                </div>

                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-orange-500/10 text-orange-400">
                            <PlayIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">In-Progress Tracking</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Monitor <strong>Real-time Progress</strong> as production units are confirmed. High visibility avoids bottlenecks and helps management adjust schedules dynamically.
                    </p>
                </div>

                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-400">
                            <CheckCircleIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Output Validation</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Completing a work order <strong>Increases Inventory</strong> for the finished good and <strong>Decreases Inventory</strong> for raw materials according to the BOM.
                    </p>
                </div>
                
                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-purple-500/10 text-purple-400">
                            <FunnelIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Priority Dispatch</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Categorize orders by <strong>Priority (Urgent to Low)</strong>. This helps the production manager sequence jobs to meet critical customer deadlines.
                    </p>
                </div>
            </div>
        </div>

        <Modal :show="showImportModal" @close="closeImport">
            <div class="px-6 py-4">
                <div class="text-lg font-semibold text-slate-900 dark:text-white">Import Work Orders</div>
                <div class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    WO akan dibuat otomatis dengan status Confirmed, berdasarkan BOM Name.
                </div>

                <div class="mt-4 flex flex-col gap-3">
                    <a
                        :href="route('manufacturing.work-orders.template')"
                        class="inline-flex items-center gap-2 text-sm font-semibold text-blue-600 hover:text-blue-500"
                    >
                        <ArrowDownTrayIcon class="h-4 w-4" />
                        Download Template
                    </a>

                    <div>
                        <input
                            type="file"
                            accept=".xlsx,.xls"
                            class="block w-full text-sm text-slate-700 dark:text-slate-200 file:mr-4 file:rounded-lg file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200 dark:file:bg-slate-700 dark:file:text-slate-200 dark:hover:file:bg-slate-600"
                            @change="(e) => (importForm.file = e.target.files?.[0] || null)"
                        />
                        <div v-if="importForm.errors.file" class="mt-2 text-sm text-red-600">
                            {{ importForm.errors.file }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 bg-slate-50 dark:bg-slate-900 px-6 py-4">
                <button
                    type="button"
                    class="rounded-lg bg-white dark:bg-slate-800 px-4 py-2 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                    @click="closeImport"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 disabled:opacity-60 transition-colors"
                    :disabled="importForm.processing || !importForm.file"
                    @click="submitImport"
                >
                    Import
                </button>
            </div>
        </Modal>
    </AppLayout>
</template>



