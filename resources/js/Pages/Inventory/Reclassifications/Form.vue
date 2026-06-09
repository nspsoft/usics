<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import axios from 'axios';
import {
    ArrowLeftIcon,
    PlusIcon,
    TrashIcon,
} from '@heroicons/vue/24/outline';
import { formatCurrency, formatNumber } from '@/helpers';

const props = defineProps({
    reclassification: Object,
    reclassNumber: String,
    warehouses: Array,
    productLookupUrl: String,
    mappingTargetProducts: Array,
    mappings: Array,
});

const isEdit = computed(() => !!props.reclassification?.id);

const toLocalDateString = (dateInput) => {
    const d = dateInput ? new Date(dateInput) : new Date();
    if (isNaN(d.getTime())) return '';
    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
};

const emptyItem = () => ({
    source_product_id: '',
    target_product_id: '',
    unit_id: null,
    qty: 1,
    source_stock: 0,
    target_locked: false,
    allowed_target_ids: [],
    notes: '',
});

const form = useForm({
    reclass_number: props.reclassNumber || props.reclassification?.reclass_number || '',
    warehouse_id: props.reclassification?.warehouse_id || '',
    reclass_date: props.reclassification?.reclass_date ? toLocalDateString(props.reclassification.reclass_date) : toLocalDateString(),
    reason: props.reclassification?.reason || '',
    notes: props.reclassification?.notes || '',
    items: props.reclassification?.items?.map((item) => ({
        id: item.id,
        source_product_id: item.source_product_id,
        target_product_id: item.target_product_id,
        unit_id: item.unit_id || item.unit?.id || item.source_product?.unit_id || item.sourceProduct?.unit_id || null,
        qty: parseFloat(item.qty),
        source_stock: 0,
        target_locked: false,
        allowed_target_ids: [],
        notes: item.notes || '',
    })) || [emptyItem()],
});

const mappingMap = computed(() => {
    const map = new Map();
    (props.mappings || []).forEach((row) => {
        const sourceId = Number(row.source_product_id);
        const targetId = Number(row.target_product_id);
        const current = map.get(sourceId) || { targets: [], defaultTargetId: null };
        if (!current.targets.includes(targetId)) current.targets.push(targetId);
        if (row.is_default) current.defaultTargetId = targetId;
        map.set(sourceId, current);
    });
    return map;
});

const applyMappingLock = (index) => {
    const item = form.items[index];
    const sourceId = Number(item?.source_product_id || 0);
    if (!sourceId) {
        item.target_locked = false;
        item.allowed_target_ids = [];
        return;
    }

    const mapping = mappingMap.value.get(sourceId);
    if (mapping?.targets?.length) {
        item.allowed_target_ids = mapping.targets;

        const preferred = mapping.defaultTargetId || mapping.targets[0];
        if (!item.target_product_id || !mapping.targets.includes(Number(item.target_product_id))) {
            item.target_product_id = preferred;
        }

        item.target_locked = mapping.targets.length === 1;
        return;
    }

    item.target_locked = false;
    item.allowed_target_ids = [];
};

const productOptions = computed(() => {
    return (props.mappingTargetProducts || []).map((product) => ({
        id: product.id,
        label: product.label || `${product.name} | SKU: ${product.sku || '-'} | Unit: -`,
        ...product,
    }));
});

const resolveProduct = (productId) => productOptions.value.find((item) => item.id == productId);

const targetOptionsForRow = (item) => {
    if (item.allowed_target_ids?.length) {
        const allowed = new Set(item.allowed_target_ids.map((id) => Number(id)));
        return productOptions.value.filter((p) => allowed.has(Number(p.id)));
    }
    return [];
};

const addItem = () => {
    form.items.push(emptyItem());
};

const removeItem = (index) => {
    if (form.items.length === 1) {
        form.items[0] = emptyItem();
        return;
    }
    form.items.splice(index, 1);
};

const fetchSourceStock = async (index) => {
    const item = form.items[index];
    if (!item?.source_product_id || !form.warehouse_id) {
        item.source_stock = 0;
        return;
    }

    try {
        const response = await axios.get(route('inventory.stock.check'), {
            params: {
                product_id: item.source_product_id,
                warehouse_id: form.warehouse_id,
            },
        });
        item.source_stock = Number(response.data.qty || 0);
    } catch (error) {
        item.source_stock = 0;
    }
};

watch(() => form.warehouse_id, () => {
    form.items.forEach((_, index) => fetchSourceStock(index));
});

watch(mappingMap, () => {
    form.items.forEach((_, index) => applyMappingLock(index));
}, { immediate: true });

const onSourceChange = (index, product) => {
    form.items[index].source_product_id = product?.id || '';
    form.items[index].unit_id = product?.unit_id || null;
    fetchSourceStock(index);
    applyMappingLock(index);
};

const onTargetChange = (index, product) => {
    form.items[index].target_product_id = product?.id || '';
};

const totalQty = computed(() => form.items.reduce((sum, item) => sum + Number(item.qty || 0), 0));

const submit = () => {
    if (isEdit.value) {
        form.put(route('inventory.reclassifications.update', props.reclassification.id));
        return;
    }

    form.post(route('inventory.reclassifications.store'));
};
</script>

<template>
    <Head :title="isEdit ? `Edit ${form.reclass_number}` : 'New Reclass Stock'" />

    <AppLayout :title="isEdit ? `Edit ${form.reclass_number}` : 'New Reclass Stock'">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <Link
                :href="route('inventory.reclassifications.index')"
                class="inline-flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white mb-6"
            >
                <ArrowLeftIcon class="h-4 w-4" />
                Back to Reclass Stock
            </Link>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="rounded-2xl glass-card p-6">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Document Header</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Reclass Number</label>
                            <input v-model="form.reclass_number" type="text" class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50" />
                            <p v-if="form.errors.reclass_number" class="mt-1 text-xs text-red-500">{{ form.errors.reclass_number }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Date</label>
                            <input v-model="form.reclass_date" type="date" class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50" />
                            <p v-if="form.errors.reclass_date" class="mt-1 text-xs text-red-500">{{ form.errors.reclass_date }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Warehouse</label>
                            <select v-model="form.warehouse_id" class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50">
                                <option value="">Select Warehouse</option>
                                <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">{{ warehouse.name }}</option>
                            </select>
                            <p v-if="form.errors.warehouse_id" class="mt-1 text-xs text-red-500">{{ form.errors.warehouse_id }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Reason</label>
                            <input v-model="form.reason" type="text" class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50" placeholder="Reclass supplier SKU" />
                            <p v-if="form.errors.reason" class="mt-1 text-xs text-red-500">{{ form.errors.reason }}</p>
                        </div>
                        <div class="md:col-span-2 xl:col-span-4">
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Notes</label>
                            <textarea v-model="form.notes" rows="2" class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"></textarea>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl glass-card p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Reclass Lines</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Pilih berdasarkan nama produk, SKU tetap tampil sebagai pembeda.</p>
                        </div>
                        <button type="button" @click="addItem" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500">
                            <PlusIcon class="h-4 w-4" />
                            Add Row
                        </button>
                    </div>

                    <div class="space-y-4">
                        <div
                            v-for="(item, index) in form.items"
                            :key="item.id || index"
                            class="rounded-xl border border-slate-200 dark:border-slate-700 p-4 bg-slate-50 dark:bg-slate-900/30"
                        >
                            <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
                                <div class="xl:col-span-4">
                                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Source Product</label>
                                    <SearchableSelect
                                        v-model="item.source_product_id"
                                        :options="[]"
                                        :fetchUrl="productLookupUrl"
                                        placeholder="Select source product"
                                        @change="onSourceChange(index, $event)"
                                    />
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        Stock: {{ formatNumber(item.source_stock || 0) }}
                                    </p>
                                </div>
                                <div class="xl:col-span-4">
                                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Target Product</label>
                                    <template v-if="item.target_locked">
                                        <div class="block w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 py-2.5 px-3 text-xs text-slate-900 dark:text-white">
                                            {{ resolveProduct(item.target_product_id)?.label || '-' }}
                                        </div>
                                        <p class="mt-1 text-xs text-emerald-600 dark:text-emerald-400">Auto-mapped dari Reclass Mapping</p>
                                    </template>
                                    <template v-else>
                                        <SearchableSelect
                                            v-model="item.target_product_id"
                                            :options="item.allowed_target_ids?.length ? targetOptionsForRow(item) : []"
                                            :fetchUrl="item.allowed_target_ids?.length ? null : productLookupUrl"
                                            placeholder="Select target product"
                                            @change="onTargetChange(index, $event)"
                                        />
                                        <p v-if="item.allowed_target_ids?.length" class="mt-1 text-xs text-emerald-600 dark:text-emerald-400">Target dibatasi sesuai mapping</p>
                                    </template>
                                </div>
                                <div class="xl:col-span-1">
                                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Qty</label>
                                    <input v-model="item.qty" type="number" min="0.0001" step="0.0001" class="block w-full rounded-lg border-0 bg-white dark:bg-slate-800 py-2.5 px-3 text-sm text-slate-900 dark:text-white focus:ring-1 focus:ring-blue-500" />
                                </div>
                                <div class="xl:col-span-2">
                                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Notes</label>
                                    <input v-model="item.notes" type="text" class="block w-full rounded-lg border-0 bg-white dark:bg-slate-800 py-2.5 px-3 text-sm text-slate-900 dark:text-white focus:ring-1 focus:ring-blue-500" placeholder="Optional" />
                                </div>
                                <div class="xl:col-span-1 flex items-end justify-end">
                                    <button type="button" @click="removeItem(index)" class="rounded-lg p-2 text-slate-500 hover:text-red-500 hover:bg-red-500/10">
                                        <TrashIcon class="h-5 w-5" />
                                    </button>
                                </div>
                            </div>

                            <div class="mt-3 flex flex-wrap items-center gap-3 text-xs text-slate-500 dark:text-slate-400">
                                <span v-if="resolveProduct(item.source_product_id)">Source: {{ resolveProduct(item.source_product_id)?.name }}</span>
                                <span v-if="resolveProduct(item.target_product_id)">Target: {{ resolveProduct(item.target_product_id)?.name }}</span>
                                <span v-if="resolveProduct(item.source_product_id)?.unit?.symbol">Unit: {{ resolveProduct(item.source_product_id)?.unit?.symbol }}</span>
                            </div>
                            <p v-if="form.errors[`items.${index}.source_product_id`]" class="mt-2 text-xs text-red-500">{{ form.errors[`items.${index}.source_product_id`] }}</p>
                            <p v-if="form.errors[`items.${index}.target_product_id`]" class="mt-1 text-xs text-red-500">{{ form.errors[`items.${index}.target_product_id`] }}</p>
                            <p v-if="form.errors[`items.${index}.qty`]" class="mt-1 text-xs text-red-500">{{ form.errors[`items.${index}.qty`] }}</p>
                        </div>
                    </div>

                    <div class="mt-4 rounded-xl bg-slate-100 dark:bg-slate-800/60 px-4 py-3 flex items-center justify-between text-sm">
                        <span class="text-slate-500 dark:text-slate-400">Total Qty</span>
                        <span class="font-semibold text-slate-900 dark:text-white">{{ formatNumber(totalQty) }}</span>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <Link :href="route('inventory.reclassifications.index')" class="rounded-xl bg-slate-50 dark:bg-slate-800 px-6 py-2.5 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700">
                        Cancel
                    </Link>
                    <button type="submit" :disabled="form.processing" class="rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 hover:bg-blue-500 disabled:opacity-50">
                        {{ isEdit ? 'Update Draft' : 'Save Draft' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
