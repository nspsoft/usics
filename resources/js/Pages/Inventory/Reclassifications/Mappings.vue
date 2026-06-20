<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import {
    PlusIcon,
    PencilSquareIcon,
    TrashIcon,
    XMarkIcon,
    ArrowUpTrayIcon,
    ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    mappings: Array,
    mappingProducts: Array,
    productLookupUrl: String,
});

const productOptions = computed(() => {
    return (props.mappingProducts || []).map((product) => ({
        id: product.id,
        label: product.label ?? `[${product.sku || '-'}] ${product.name || '-'}`,
        ...product,
    }));
});

const newForm = ref({
    source_product_id: '',
    target_product_id: '',
    is_default: true,
    is_active: true,
    notes: '',
});

const editingId = ref(null);
const editForm = ref({
    source_product_id: '',
    target_product_id: '',
    is_default: false,
    is_active: true,
    notes: '',
});

const startEdit = (mapping) => {
    editingId.value = mapping.id;
    editForm.value = {
        source_product_id: mapping.source_product_id,
        target_product_id: mapping.target_product_id,
        is_default: !!mapping.is_default,
        is_active: !!mapping.is_active,
        notes: mapping.notes || '',
    };
};

const cancelEdit = () => {
    editingId.value = null;
};

const createMapping = () => {
    router.post(route('inventory.reclass-mappings.store'), newForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            newForm.value = { source_product_id: '', target_product_id: '', is_default: true, is_active: true, notes: '' };
        },
    });
};

const updateMapping = (id) => {
    router.put(route('inventory.reclass-mappings.update', id), editForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            editingId.value = null;
        },
    });
};

const deleteMapping = (id) => {
    if (!confirm('Hapus mapping ini?')) return;
    router.delete(route('inventory.reclass-mappings.destroy', id), { preserveScroll: true });
};

const showImportModal = ref(false);
const importFile = ref(null);
const importing = ref(false);
const overwriteExisting = ref(false);

const onFileChange = (e) => {
    importFile.value = e.target.files[0];
};

const handleImport = () => {
    if (!importFile.value) return;
    
    importing.value = true;
    router.post(route('inventory.reclass-mappings.import'), {
        file: importFile.value,
        overwrite: overwriteExisting.value,
    }, {
        onSuccess: () => {
            showImportModal.value = false;
            importFile.value = null;
            importing.value = false;
            overwriteExisting.value = false;
        },
        onError: () => {
            importing.value = false;
        },
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="Reclass Mapping" />

    <AppLayout title="Reclass Mapping">
        <div class="max-w-7xl mx-auto">
            <div class="rounded-2xl glass-card p-6 mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Tambah Mapping</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Saat source dipilih di Reclass Stock, target akan otomatis mengikuti mapping.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <button @click="showImportModal = true" class="inline-flex items-center gap-2 rounded-xl bg-slate-100 dark:bg-slate-900/50 px-4 py-2 text-sm font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700 transition-colors">
                            <ArrowUpTrayIcon class="h-4 w-4" />
                            Import Excel
                        </button>
                        <button @click="createMapping" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 transition-colors">
                            <PlusIcon class="h-4 w-4" />
                            Add
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Source Product</label>
                        <SearchableSelect v-model="newForm.source_product_id" :options="productOptions" :fetchUrl="productLookupUrl" placeholder="Pilih source product" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Target Product</label>
                        <SearchableSelect v-model="newForm.target_product_id" :options="productOptions" :fetchUrl="productLookupUrl" placeholder="Pilih target product" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Notes</label>
                        <input v-model="newForm.notes" type="text" class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50" />
                        <div class="mt-2 flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                            <input id="is_active" v-model="newForm.is_active" type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                            <label for="is_active">Active</label>
                        </div>
                        <div class="mt-2 flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                            <input id="is_default" v-model="newForm.is_default" type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                            <label for="is_default">Default Target</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl glass-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-900/50">
                                <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Source</th>
                                <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Target</th>
                                <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Default</th>
                                <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Status</th>
                                <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Notes</th>
                                <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="mapping in mappings" :key="mapping.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <template v-if="editingId === mapping.id">
                                    <td class="px-6 py-4">
                                        <SearchableSelect v-model="editForm.source_product_id" :options="productOptions" :fetchUrl="productLookupUrl" placeholder="Source product" />
                                    </td>
                                    <td class="px-6 py-4">
                                        <SearchableSelect v-model="editForm.target_product_id" :options="productOptions" :fetchUrl="productLookupUrl" placeholder="Target product" />
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                                            <input v-model="editForm.is_default" type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                                            <span>Default</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                                            <input v-model="editForm.is_active" type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                                            <span>{{ editForm.is_active ? 'Active' : 'Inactive' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <input v-model="editForm.notes" type="text" class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50" />
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="inline-flex items-center gap-2">
                                            <button @click="updateMapping(mapping.id)" class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-500">Save</button>
                                            <button @click="cancelEdit" class="rounded-lg p-2 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800">
                                                <XMarkIcon class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </td>
                                </template>
                                <template v-else>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-slate-900 dark:text-white">{{ mapping.source_product?.name || mapping.sourceProduct?.name }}</div>
                                        <div class="text-xs text-slate-500">{{ mapping.source_product?.sku || mapping.sourceProduct?.sku }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-slate-900 dark:text-white">{{ mapping.target_product?.name || mapping.targetProduct?.name }}</div>
                                        <div class="text-xs text-slate-500">{{ mapping.target_product?.sku || mapping.targetProduct?.sku }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span v-if="mapping.is_default" class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold bg-blue-500/20 text-blue-500 border-blue-500/30">Default</span>
                                        <span v-else class="text-xs text-slate-400">-</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold" :class="mapping.is_active ? 'bg-emerald-500/20 text-emerald-500 border-emerald-500/30' : 'bg-slate-500/20 text-slate-500 border-slate-500/30'">
                                            {{ mapping.is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-300">{{ mapping.notes || '-' }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="inline-flex items-center gap-2">
                                            <button @click="startEdit(mapping)" class="rounded-lg p-2 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800">
                                                <PencilSquareIcon class="h-4 w-4" />
                                            </button>
                                            <button @click="deleteMapping(mapping.id)" class="rounded-lg p-2 text-slate-500 hover:text-red-500 hover:bg-red-500/10">
                                                <TrashIcon class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </td>
                                </template>
                            </tr>
                            <tr v-if="mappings.length === 0">
                                <td colspan="6" class="px-6 py-10 text-center text-slate-500 italic">Belum ada mapping.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Import Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showImportModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-white dark:bg-slate-950/80 backdrop-blur-sm">
                    <div class="glass-card rounded-2xl w-full max-w-md p-6 shadow-2xl">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Import Reclass Mappings</h3>
                            <button @click="showImportModal = false" class="text-slate-500 hover:text-slate-900 dark:text-white transition-colors">
                                <XMarkIcon class="h-6 w-6" />
                            </button>
                        </div>
                        
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Excel/CSV File</label>
                            <div class="flex flex-col items-center justify-center border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-xl p-8 hover:border-blue-500/50 transition-colors relative">
                                <input 
                                    type="file" 
                                    @change="onFileChange"
                                    class="absolute inset-0 opacity-0 cursor-pointer"
                                    accept=".xlsx,.xls,.csv"
                                />
                                <ArrowUpTrayIcon class="h-10 w-10 text-slate-600 mb-2" />
                                <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">
                                    {{ importFile ? importFile.name : 'Click or drag file to upload' }}
                                </p>
                                <p class="text-xs text-slate-600 mt-1">Maximum size: 2MB</p>
                                <div class="mt-4 z-10 relative flex flex-col items-center gap-2">
                                    <a 
                                        :href="route('inventory.reclass-mappings.template')"
                                        class="text-xs text-blue-400 hover:text-blue-300 underline"
                                    >
                                        Download Empty Template
                                    </a>
                                    <a 
                                        :href="route('inventory.reclass-mappings.template', { with_data: 1 })"
                                        class="text-xs text-emerald-400 hover:text-emerald-300 underline font-medium"
                                    >
                                        Download Template with Existing Data
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="mb-6">
                            <label class="flex items-start gap-3 p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors">
                                <div class="flex items-center h-5">
                                    <input 
                                        v-model="overwriteExisting" 
                                        type="checkbox" 
                                        class="w-4 h-4 rounded border-slate-600 bg-slate-700 text-blue-600 focus:ring-blue-500/50 focus:ring-offset-0"
                                    />
                                </div>
                                <div class="flex-1">
                                    <span class="block text-sm font-medium text-slate-600 dark:text-slate-300">Overwrite Existing Mappings</span>
                                    <p class="text-xs text-slate-500 mt-1">
                                        If checked, mappings with matching source SKU and target SKU combination will be updated with data from the file.
                                    </p>
                                    <div v-if="overwriteExisting" class="mt-2 flex items-start gap-2 text-xs text-amber-400 bg-amber-500/10 p-2 rounded-lg border border-amber-500/20">
                                        <ExclamationTriangleIcon class="h-4 w-4 shrink-0" />
                                        <span>Warning: This will replace existing mapping details.</span>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <div class="flex items-center gap-3">
                            <button 
                                @click="handleImport"
                                :disabled="!importFile || importing"
                                class="flex-1 rounded-xl bg-blue-600 py-2.5 text-sm font-semibold text-white hover:bg-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                            >
                                {{ importing ? 'Importing...' : 'Start Import' }}
                            </button>
                            <button 
                                @click="showImportModal = false"
                                class="flex-1 rounded-xl bg-slate-50 dark:bg-slate-800 py-2.5 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-700 transition-all"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>
