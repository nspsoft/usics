<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import debounce from 'lodash/debounce';
import {
    PlusIcon,
    MagnifyingGlassIcon,
    FunnelIcon,
    EyeIcon,
    SparklesIcon,
    XMarkIcon,
    TrashIcon,
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

const deleteReclass = (id) => {
    if (!confirm('Hapus draft reclass stock ini?')) return;
    router.delete(route('inventory.reclassifications.destroy', id));
};

const getStatusBadge = (status) => {
    const badges = {
        draft: 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30',
        posted: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
        cancelled: 'bg-red-500/20 text-red-400 border-red-500/30',
    };
    return badges[status] || badges.draft;
};

// Modal State
const showAutoModal = ref(false);

const toLocalDateString = () => {
    const d = new Date();
    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
};

const autoForm = useForm({
    warehouse_id: '',
    target_warehouse_id: '',
    reclass_date: toLocalDateString(),
    reason: 'Otomatis reclass berdasarkan mapping & stok',
    notes: '',
});

watch(() => autoForm.warehouse_id, (newVal, oldVal) => {
    if (!autoForm.target_warehouse_id || autoForm.target_warehouse_id === oldVal) {
        autoForm.target_warehouse_id = newVal;
    }
});

const openAutoModal = () => {
    const rawMaterialWh = props.warehouses?.find(w => w.name.toLowerCase().replace(/[^a-z]/g, '') === 'rawmaterial');
    const finishedGoodsWh = props.warehouses?.find(w => w.name.toLowerCase().replace(/[^a-z]/g, '') === 'finishedgoods');

    autoForm.warehouse_id = rawMaterialWh ? rawMaterialWh.id : '';
    autoForm.target_warehouse_id = finishedGoodsWh ? finishedGoodsWh.id : '';
    autoForm.reclass_date = toLocalDateString();
    autoForm.reason = 'Otomatis reclass berdasarkan mapping & stok';
    autoForm.notes = '';
    autoForm.clearErrors();
    showAutoModal.value = true;
};

const submitAutoGenerate = () => {
    autoForm.post(route('inventory.reclassifications.auto-generate'), {
        onSuccess: () => {
            showAutoModal.value = false;
        },
    });
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

            <div class="flex items-center gap-3">
                <button
                    @click="openAutoModal"
                    class="inline-flex items-center gap-2 rounded-xl border border-blue-500/30 bg-blue-500/10 px-4 py-2 text-sm font-semibold text-blue-600 dark:text-blue-400 hover:bg-blue-500/20 transition-all"
                >
                    <SparklesIcon class="h-5 w-5" />
                    Auto Reclass Stock
                </button>
                <Link
                    :href="route('inventory.reclassifications.create')"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 hover:from-blue-500 hover:to-blue-400 transition-all"
                >
                    <PlusIcon class="h-5 w-5" />
                    New Reclass
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
                            <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-300">
                                <div v-if="reclass.target_warehouse || (reclass.targetWarehouse && reclass.target_warehouse_id !== reclass.warehouse_id)" class="flex items-center gap-1.5 flex-wrap">
                                    <span>{{ reclass.warehouse?.name }}</span>
                                    <span class="text-blue-500 font-semibold">➔</span>
                                    <span class="font-semibold text-slate-900 dark:text-white">{{ reclass.target_warehouse?.name || reclass.targetWarehouse?.name }}</span>
                                </div>
                                <div v-else>
                                    {{ reclass.warehouse?.name }}
                                </div>
                            </td>
                            <td class="px-4 py-4 text-right text-sm text-slate-600 dark:text-slate-300">{{ reclass.items_count }}</td>
                            <td class="px-4 py-4 text-right text-sm font-medium text-slate-900 dark:text-white">{{ formatNumber(reclass.total_qty || 0) }}</td>
                            <td class="px-4 py-4 text-right text-sm font-medium text-slate-900 dark:text-white">{{ formatCurrency(reclass.total_value || 0) }}</td>
                            <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-300">{{ reclass.created_by?.name || reclass.createdBy?.name || '-' }}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold capitalize" :class="getStatusBadge(reclass.status)">
                                    {{ reclass.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right flex items-center justify-end gap-1">
                                <Link :href="route('inventory.reclassifications.show', reclass.id)" class="inline-flex rounded-lg p-2 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800">
                                    <EyeIcon class="h-4 w-4" />
                                </Link>
                                <button
                                    v-if="reclass.status === 'draft'"
                                    @click="deleteReclass(reclass.id)"
                                    class="inline-flex rounded-lg p-2 text-slate-500 hover:text-red-500 hover:bg-red-500/10"
                                >
                                    <TrashIcon class="h-4 w-4" />
                                </button>
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

        <!-- Auto Generate Reclass Modal -->
        <div v-if="showAutoModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div class="relative w-full max-w-lg rounded-2xl bg-white dark:bg-slate-900 shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden transform transition-all">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 px-6 py-4">
                    <div class="flex items-center gap-2">
                        <SparklesIcon class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white">Auto Reclass Stock</h3>
                    </div>
                    <button @click="showAutoModal = false" class="rounded-lg p-1.5 text-slate-500 hover:text-slate-950 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800">
                        <XMarkIcon class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitAutoGenerate" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Gudang Asal (Source Warehouse)</label>
                        <select v-model="autoForm.warehouse_id" required class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50">
                            <option value="">Pilih Gudang Asal</option>
                            <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">{{ warehouse.name }}</option>
                        </select>
                        <p v-if="autoForm.errors.warehouse_id" class="mt-1 text-xs text-red-500">{{ autoForm.errors.warehouse_id }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Gudang Tujuan (Target Warehouse)</label>
                        <select v-model="autoForm.target_warehouse_id" class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50">
                            <option value="">Pilih Gudang Tujuan (Default: Sama)</option>
                            <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">{{ warehouse.name }}</option>
                        </select>
                        <p v-if="autoForm.errors.target_warehouse_id" class="mt-1 text-xs text-red-500">{{ autoForm.errors.target_warehouse_id }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Tanggal</label>
                        <input v-model="autoForm.reclass_date" type="date" required class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50" />
                        <p v-if="autoForm.errors.reclass_date" class="mt-1 text-xs text-red-500">{{ autoForm.errors.reclass_date }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Alasan</label>
                        <input v-model="autoForm.reason" type="text" required class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50" placeholder="Contoh: Reclass stok rutin" />
                        <p v-if="autoForm.errors.reason" class="mt-1 text-xs text-red-500">{{ autoForm.errors.reason }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Catatan (Opsional)</label>
                        <textarea v-model="autoForm.notes" rows="2" class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"></textarea>
                        <p v-if="autoForm.errors.notes" class="mt-1 text-xs text-red-500">{{ autoForm.errors.notes }}</p>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="showAutoModal = false" class="rounded-xl bg-slate-50 dark:bg-slate-800 px-5 py-2 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700">
                            Batal
                        </button>
                        <button type="submit" :disabled="autoForm.processing" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 hover:bg-blue-500 disabled:opacity-50">
                            <span v-if="autoForm.processing" class="animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full"></span>
                            Generate Draft
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
