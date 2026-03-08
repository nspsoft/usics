<script setup>
import { ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { 
    MagnifyingGlassIcon, 
    PlusIcon, 
    PencilSquareIcon, 
    TrashIcon, 
    ScaleIcon,
    CheckCircleIcon,
    XCircleIcon,
    XMarkIcon,
    ChevronUpIcon,
    ChevronDownIcon,
} from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';

const props = defineProps({
    units: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const sortField = ref(props.filters.sort || 'name');
const sortDirection = ref(props.filters.direction || 'asc');
const showModal = ref(false);
const editingUnit = ref(null);

const form = useForm({
    name: '',
    code: '',
    symbol: '',
    is_active: true,
});

const applyFilters = debounce(() => {
    router.get('/inventory/units', { 
        search: search.value,
        sort: sortField.value,
        direction: sortDirection.value
    }, { preserveState: true, replace: true });
}, 300);

watch(search, applyFilters);

const sort = (field) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }
    applyFilters();
};

const openModal = (unit = null) => {
    editingUnit.value = unit;
    if (unit) {
        form.name = unit.name;
        form.code = unit.code;
        form.symbol = unit.symbol;
        form.is_active = Boolean(unit.is_active);
    } else {
        form.reset();
        form.is_active = true;
    }
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
    editingUnit.value = null;
    form.clearErrors();
};

const submit = () => {
    if (editingUnit.value) {
        form.put(`/inventory/units/${editingUnit.value.id}`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/inventory/units', {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteUnit = (unit) => {
    if (confirm(`Are you sure you want to delete unit "${unit.name}"?`)) {
        router.delete(`/inventory/units/${unit.id}`);
    }
};
</script>

<template>
    <Head title="Units Management" />

    <AppLayout title="Units Management">
        <!-- Header & Toolbar -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div class="relative w-full sm:w-80">
                <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500" />
                <input 
                    v-model="search" 
                    type="search" 
                    placeholder="Search units..." 
                    class="block w-full rounded-xl border-0 bg-white dark:bg-slate-800/50 py-2.5 pl-10 pr-4 text-sm text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50 shadow-sm" 
                />
            </div>
            
            <button 
                @click="openModal()" 
                class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 hover:bg-blue-500 transition-all"
            >
                <PlusIcon class="h-5 w-5" />
                New Unit
            </button>
        </div>

        <!-- Table -->
        <div class="rounded-2xl glass-card overflow-hidden shadow-sm bg-white dark:bg-slate-900/50">
            <div class="overflow-x-auto overflow-y-auto max-h-[600px]">
                <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700">
                            <th 
                                @click="sort('name')"
                                class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors group"
                            >
                                <div class="flex items-center gap-2">
                                    Name
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
                                class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors group"
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
                                @click="sort('symbol')"
                                class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors group"
                            >
                                <div class="flex items-center gap-2">
                                    Symbol
                                    <span v-if="sortField === 'symbol'" class="text-blue-500">
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
                                class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4 text-center text-xs font-bold text-slate-500 uppercase tracking-wider cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors group"
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
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr v-for="unit in units.data" :key="unit.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 rounded-lg bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                                        <ScaleIcon class="h-5 w-5" />
                                    </div>
                                    <span class="font-medium text-slate-900 dark:text-white">{{ unit.name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-300 font-mono bg-slate-100 dark:bg-slate-800 rounded px-2 w-min">
                                {{ unit.code }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-300">
                                {{ unit.symbol || '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div v-if="unit.is_active" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                                    <CheckCircleIcon class="w-3.5 h-3.5" />
                                    Active
                                </div>
                                <div v-else class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                    <XCircleIcon class="w-3.5 h-3.5" />
                                    Inactive
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button 
                                        @click="openModal(unit)" 
                                        class="p-2 rounded-lg text-slate-400 hover:text-blue-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                                    >
                                        <PencilSquareIcon class="h-5 w-5" />
                                    </button>
                                    <button 
                                        @click="deleteUnit(unit)" 
                                        class="p-2 rounded-lg text-slate-400 hover:text-red-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                                    >
                                        <TrashIcon class="h-5 w-5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="units.data.length === 0">
                            <td colspan="5" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
                                No units found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="units.links.length > 3" class="border-t border-slate-100 dark:border-slate-800 p-4">
                <Pagination :links="units.links" />
            </div>
        </div>

        <!-- Units Operations Guide -->
        <div class="mt-8 relative hidden md:block">
            <div class="absolute inset-0 flex items-center" aria-hidden="true">
                <div class="w-full border-t border-slate-200/60 dark:border-slate-700/60"></div>
            </div>
            <div class="relative flex justify-center">
                <span class="bg-[#F8FAFC] dark:bg-[#0F172A] px-4 text-[10px] font-black tracking-[0.2em] text-slate-400 dark:text-slate-500 uppercase">
                    Units Operations Guide
                </span>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4 hidden md:grid mb-8">
            <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                <div class="flex items-center gap-3 mb-3">
                    <div class="p-2.5 rounded-xl bg-blue-500/10 text-blue-400">
                        <ScaleIcon class="h-5 w-5" />
                    </div>
                    <h4 class="font-bold text-slate-200 text-sm">Unit Standardization</h4>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    Ensure all product <strong>Base Units</strong> precisely match purchasing norms to prevent fractional errors during bulk procurement or internal transfers.
                </p>
            </div>

            <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                <div class="flex items-center gap-3 mb-3">
                    <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-400">
                        <CheckCircleIcon class="h-5 w-5" />
                    </div>
                    <h4 class="font-bold text-slate-200 text-sm">Active Lifecycle</h4>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    Toggle a unit to <strong>Inactive</strong> when phasing out obsolete packaging sizes, automatically hiding them from new Purchase Orders.
                </p>
            </div>

            <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                <div class="flex items-center gap-3 mb-3">
                    <div class="p-2.5 rounded-xl bg-purple-500/10 text-purple-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 22h14a2 2 0 0 0 2-2V7l-5-5H6a2 2 0 0 0-2 2v4"></path><path d="M14 2v4a2 2 0 0 0 2 2h4"></path><path d="m3 15 2 2 4-4"></path>
                        </svg>
                    </div>
                    <h4 class="font-bold text-slate-200 text-sm">Symbol Accuracy</h4>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    Maintain universally accepted <strong>Symbols</strong> (e.g., Ltr, Kg, Pcs) for clear rendering across printed invoices and delivery notes.
                </p>
            </div>
            
            <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                <div class="flex items-center gap-3 mb-3">
                    <div class="p-2.5 rounded-xl bg-rose-500/10 text-rose-400">
                        <TrashIcon class="h-5 w-5" />
                    </div>
                    <h4 class="font-bold text-slate-200 text-sm">Safe Deletion</h4>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    Deleting a unit is disabled if it is presently bound to existing products. Consider <strong>Deactivating</strong> it as a safer alternative.
                </p>
            </div>
        </div>

        <!-- Custom Modal Overlay -->
        <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="closeModal"></div>
                
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="relative inline-block align-bottom bg-white dark:bg-slate-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white dark:bg-slate-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex items-start justify-between">
                            <h3 class="text-lg leading-6 font-medium text-slate-900 dark:text-white" id="modal-title">
                                {{ editingUnit ? 'Edit Unit' : 'New Unit' }}
                            </h3>
                            <button @click="closeModal" class="text-slate-400 hover:text-slate-500">
                                <XMarkIcon class="h-6 w-6" />
                            </button>
                        </div>
                        
                        <form @submit.prevent="submit" class="mt-4 space-y-4">
                            <div>
                                <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Unit Name</label>
                                <input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white"
                                    placeholder="e.g. Box, Piece, Kilogram"
                                    required
                                />
                                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="code" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Code</label>
                                    <input
                                        id="code"
                                        v-model="form.code"
                                        type="text"
                                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white uppercase"
                                        placeholder="PCS"
                                        required
                                    />
                                    <p v-if="form.errors.code" class="mt-1 text-sm text-red-600">{{ form.errors.code }}</p>
                                </div>
                                <div>
                                    <label for="symbol" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Symbol</label>
                                    <input
                                        id="symbol"
                                        v-model="form.symbol"
                                        type="text"
                                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white"
                                        placeholder="kg"
                                    />
                                    <p v-if="form.errors.symbol" class="mt-1 text-sm text-red-600">{{ form.errors.symbol }}</p>
                                </div>
                            </div>

                            <div class="flex items-center">
                                <input
                                    id="is_active"
                                    v-model="form.is_active"
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                />
                                <label for="is_active" class="ml-2 block text-sm text-slate-700 dark:text-slate-300">
                                    Active Status
                                </label>
                            </div>

                            <div class="bg-gray-50 dark:bg-slate-700/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse -mx-6 -mb-6 mt-6">
                                <button 
                                    type="submit" 
                                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm"
                                    :disabled="form.processing"
                                >
                                    {{ editingUnit ? 'Update Unit' : 'Create Unit' }}
                                </button>
                                <button 
                                    type="button" 
                                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-slate-800 dark:text-slate-300 dark:border-slate-600 dark:hover:bg-slate-700" 
                                    @click="closeModal"
                                >
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
