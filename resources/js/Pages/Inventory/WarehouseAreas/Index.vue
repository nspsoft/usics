<script setup>
import { ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';
import {
    MagnifyingGlassIcon,
    PlusIcon,
    PencilSquareIcon,
    TrashIcon,
    MapPinIcon,
    CheckCircleIcon,
    XCircleIcon,
    XMarkIcon,
    ChevronUpIcon,
    ChevronDownIcon,
} from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';

const props = defineProps({
    areas: Object,
    warehouses: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');
const selectedWarehouse = ref(props.filters.warehouse_id || '');
const sortField = ref(props.filters.sort || 'name');
const sortDirection = ref(props.filters.direction || 'asc');

const showModal = ref(false);
const editingArea = ref(null);

const form = useForm({
    warehouse_id: '',
    name: '',
    is_active: true,
});

const applyFilters = debounce(() => {
    router.get('/inventory/warehouse-areas', {
        search: search.value || undefined,
        warehouse_id: selectedWarehouse.value || undefined,
        sort: sortField.value,
        direction: sortDirection.value,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, selectedWarehouse], applyFilters);

const sort = (field) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }
    applyFilters();
};

const openModal = (area = null) => {
    editingArea.value = area;
    if (area) {
        form.warehouse_id = area.warehouse_id;
        form.name = area.name;
        form.is_active = Boolean(area.is_active);
    } else {
        form.reset();
        form.warehouse_id = selectedWarehouse.value || '';
        form.is_active = true;
    }
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
    editingArea.value = null;
    form.clearErrors();
};

const submit = () => {
    if (editingArea.value) {
        form.put(`/inventory/warehouse-areas/${editingArea.value.id}`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/inventory/warehouse-areas', {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteArea = (area) => {
    if (confirm(`Are you sure you want to delete area "${area.name}"?`)) {
        router.delete(`/inventory/warehouse-areas/${area.id}`);
    }
};
</script>

<template>
    <Head title="Warehouse Areas" />

    <AppLayout title="Warehouse Areas">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div class="flex items-center gap-3 w-full">
                <div class="relative flex-1 sm:w-80">
                    <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500" />
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search area..."
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
            </div>

            <button
                type="button"
                @click="openModal()"
                class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 hover:from-blue-500 hover:to-blue-400 transition-all"
            >
                <PlusIcon class="h-5 w-5" />
                New Area
            </button>
        </div>

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
                                    Area
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
                                @click="sort('warehouse_name')"
                                class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors group"
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
                        <tr v-for="area in areas.data" :key="area.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 rounded-lg bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400">
                                        <MapPinIcon class="h-5 w-5" />
                                    </div>
                                    <span class="font-medium text-slate-900 dark:text-white">{{ area.name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-300">
                                {{ area.warehouse?.name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span v-if="area.is_active" class="inline-flex items-center gap-1 text-emerald-500 text-sm font-semibold">
                                    <CheckCircleIcon class="h-4 w-4" /> Active
                                </span>
                                <span v-else class="inline-flex items-center gap-1 text-red-400 text-sm font-semibold">
                                    <XCircleIcon class="h-4 w-4" /> Inactive
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        type="button"
                                        @click="openModal(area)"
                                        class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-blue-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors"
                                    >
                                        <PencilSquareIcon class="h-4 w-4" />
                                    </button>
                                    <button
                                        type="button"
                                        @click="deleteArea(area)"
                                        class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-red-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors"
                                    >
                                        <TrashIcon class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="areas.data.length === 0">
                            <td colspan="4" class="px-6 py-12 text-center text-sm text-slate-500">
                                No areas found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">
            <Pagination :links="areas.links" />
        </div>

        <Modal :show="showModal" @close="closeModal">
            <div class="px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="text-lg font-semibold text-slate-900 dark:text-white">
                        {{ editingArea ? 'Edit Area' : 'New Area' }}
                    </div>
                    <button class="text-slate-500 hover:text-slate-300" @click="closeModal">
                        <XMarkIcon class="h-5 w-5" />
                    </button>
                </div>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-1">Warehouse</label>
                        <select
                            v-model="form.warehouse_id"
                            class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                        >
                            <option value="">Select warehouse</option>
                            <option v-for="w in warehouses" :key="w.id" :value="w.id">
                                {{ w.name }}
                            </option>
                        </select>
                        <div v-if="form.errors.warehouse_id" class="mt-1 text-xs text-red-400">{{ form.errors.warehouse_id }}</div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-1">Area Name</label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                            placeholder="e.g. FG, RM, Rack A1"
                        />
                        <div v-if="form.errors.name" class="mt-1 text-xs text-red-400">{{ form.errors.name }}</div>
                    </div>

                    <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500"
                        />
                        Active
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 bg-slate-50 dark:bg-slate-900 px-6 py-4">
                <button
                    type="button"
                    class="rounded-lg bg-white dark:bg-slate-800 px-4 py-2 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                    @click="closeModal"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 transition-colors disabled:opacity-50"
                    :disabled="form.processing"
                    @click="submit"
                >
                    Save
                </button>
            </div>
        </Modal>
    </AppLayout>
</template>

