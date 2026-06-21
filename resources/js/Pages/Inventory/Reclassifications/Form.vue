<script setup>
import { computed, ref, watch, onMounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import axios from 'axios';
import {
    ArrowLeftIcon,
    PlusIcon,
    TrashIcon,
    SparklesIcon,
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

const findWhByName = (namePattern) => {
    return props.warehouses?.find(w => w.name.toLowerCase().replace(/[^a-z]/g, '') === namePattern)?.id || '';
};

const form = useForm({
    reclass_number: props.reclassNumber || props.reclassification?.reclass_number || '',
    warehouse_id: props.reclassification?.warehouse_id || findWhByName('rawmaterial'),
    target_warehouse_id: props.reclassification?.target_warehouse_id || findWhByName('finishedgoods'),
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

        if (item.target_product_id) {
            const tgtProduct = resolveProduct(item.target_product_id);
            if (tgtProduct) {
                item.unit_id = tgtProduct.unit_id || null;
            }
        }
        return;
    }

    item.target_locked = false;
    item.allowed_target_ids = [];
};

// cache for resolving product names and labels
const initialOptions = ref([]);

const initializeInitialOptions = () => {
    const list = [];
    const seen = new Set();
    
    // Add mappingTargetProducts
    (props.mappingTargetProducts || []).forEach(p => {
        if (!seen.has(Number(p.id))) {
            seen.add(Number(p.id));
            const symbol = p.unit?.symbol || p.unit?.name || '-';
            list.push({
                id: p.id,
                label: p.label || `${p.name} | SKU: ${p.sku || '-'} | Unit: ${symbol}`,
                sku: p.sku,
                name: p.name,
                unit_id: p.unit_id,
                cost_price: parseFloat(p.cost_price || 0),
                selling_price: parseFloat(p.selling_price || 0),
            });
        }
    });

    // Add source and target products from existing items
    (props.reclassification?.items || []).forEach((item) => {
        const src = item.sourceProduct || item.source_product;
        if (src && !seen.has(Number(src.id))) {
            seen.add(Number(src.id));
            const symbol = src.unit?.symbol ?? src.unit?.name ?? '-';
            list.push({
                id: src.id,
                label: `${src.name} | SKU: ${src.sku ?? '-'} | Unit: ${symbol}`,
                sku: src.sku,
                name: src.name,
                unit_id: src.unit_id,
                cost_price: parseFloat(src.cost_price || 0),
                selling_price: parseFloat(src.selling_price || 0),
            });
        }
        const tgt = item.targetProduct || item.target_product;
        if (tgt && !seen.has(Number(tgt.id))) {
            seen.add(Number(tgt.id));
            const symbol = tgt.unit?.symbol ?? tgt.unit?.name ?? '-';
            list.push({
                id: tgt.id,
                label: `${tgt.name} | SKU: ${tgt.sku ?? '-'} | Unit: ${symbol}`,
                sku: tgt.sku,
                name: tgt.name,
                unit_id: tgt.unit_id,
                cost_price: parseFloat(tgt.cost_price || 0),
                selling_price: parseFloat(tgt.selling_price || 0),
            });
        }
    });

    initialOptions.value = list;
};

initializeInitialOptions();

const resolveProduct = (productId) => initialOptions.value.find((item) => item.id == productId);

const targetOptionsForRow = (item) => {
    if (item.allowed_target_ids?.length) {
        const allowed = new Set(item.allowed_target_ids.map((id) => Number(id)));
        return initialOptions.value.filter((p) => allowed.has(Number(p.id)));
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

watch(() => form.warehouse_id, (newVal, oldVal) => {
    if (!isEdit.value && (!form.target_warehouse_id || form.target_warehouse_id === oldVal)) {
        form.target_warehouse_id = newVal;
    }
    form.items.forEach((_, index) => fetchSourceStock(index));
});

watch(mappingMap, () => {
    form.items.forEach((_, index) => applyMappingLock(index));
}, { immediate: true });

onMounted(() => {
    form.items.forEach((_, index) => fetchSourceStock(index));
});

const onSourceChange = (index, product) => {
    form.items[index].source_product_id = product?.id || '';
    form.items[index].unit_id = product?.unit_id || null;
    fetchSourceStock(index);
    applyMappingLock(index);
};

const onTargetChange = (index, product) => {
    form.items[index].target_product_id = product?.id || '';
    if (product?.unit_id) {
        form.items[index].unit_id = product.unit_id;
    }
};

const totalQty = computed(() => form.items.reduce((sum, item) => sum + Number(item.qty || 0), 0));

const getLineCostPrice = (item) => {
    return resolveProduct(item.source_product_id)?.cost_price || 0;
};
const getLineSellPrice = (item) => {
    return resolveProduct(item.target_product_id)?.selling_price || 0;
};
const getLineProfit = (item) => {
    return getLineSellPrice(item) - getLineCostPrice(item);
};
const getLineProfitPct = (item) => {
    const cost = getLineCostPrice(item);
    if (cost <= 0) return 0;
    return (getLineProfit(item) / cost) * 100;
};

const totalCost = computed(() => {
    return form.items.reduce((sum, item) => {
        if (!item.source_product_id) return sum;
        return sum + (Number(item.qty || 0) * getLineCostPrice(item));
    }, 0);
});

const totalSell = computed(() => {
    return form.items.reduce((sum, item) => {
        if (!item.target_product_id) return sum;
        return sum + (Number(item.qty || 0) * getLineSellPrice(item));
    }, 0);
});

const totalProfit = computed(() => totalSell.value - totalCost.value);

const totalProfitPct = computed(() => {
    if (totalCost.value <= 0) return 0;
    return (totalProfit.value / totalCost.value) * 100;
});

const isAutoFilling = ref(false);

const autoFillFromStockAndMapping = async () => {
    if (!form.warehouse_id) {
        alert('Silakan pilih Warehouse terlebih dahulu.');
        return;
    }

    if (form.items.length > 0 && form.items.some(item => item.source_product_id)) {
        if (!confirm('Peringatan: Fitur ini akan mengganti baris reclass saat ini dengan data otomatis berdasarkan mapping & stock. Lanjutkan?')) {
            return;
        }
    }

    isAutoFilling.value = true;
    try {
        const response = await axios.get(route('inventory.reclassifications.auto-fill'), {
            params: { warehouse_id: form.warehouse_id }
        });

        const items = response.data.items || [];
        if (items.length === 0) {
            alert('Tidak ada stok produk dengan mapping aktif di gudang ini.');
            return;
        }

        // Add auto-filled products to initialOptions so SearchableSelect can resolve them
        const seen = new Set(initialOptions.value.map(p => Number(p.id)));
        items.forEach(item => {
            if (item.source_product && !seen.has(Number(item.source_product.id))) {
                seen.add(Number(item.source_product.id));
                const p = item.source_product;
                const symbol = p.unit?.symbol || p.unit?.name || '-';
                initialOptions.value.push({
                    id: p.id,
                    label: `${p.name} | SKU: ${p.sku ?? '-'} | Unit: ${symbol}`,
                    sku: p.sku,
                    name: p.name,
                    unit_id: p.unit_id,
                    cost_price: parseFloat(p.cost_price || 0),
                    selling_price: parseFloat(p.selling_price || 0),
                });
            }
            if (item.target_product && !seen.has(Number(item.target_product.id))) {
                seen.add(Number(item.target_product.id));
                const p = item.target_product;
                const symbol = p.unit?.symbol || p.unit?.name || '-';
                initialOptions.value.push({
                    id: p.id,
                    label: `${p.name} | SKU: ${p.sku ?? '-'} | Unit: ${symbol}`,
                    sku: p.sku,
                    name: p.name,
                    unit_id: p.unit_id,
                    cost_price: parseFloat(p.cost_price || 0),
                    selling_price: parseFloat(p.selling_price || 0),
                });
            }
        });

        // Map items to form.items structure
        form.items = items.map(item => ({
            source_product_id: item.source_product_id,
            target_product_id: item.target_product_id,
            unit_id: item.unit_id,
            qty: parseFloat(item.qty),
            source_stock: parseFloat(item.source_stock),
            target_locked: false,
            allowed_target_ids: [],
            notes: item.notes || '',
        }));

        // Re-apply mapping locks for all imported items
        form.items.forEach((_, index) => {
            applyMappingLock(index);
        });

    } catch (error) {
        console.error(error);
        alert('Terjadi kesalahan saat memproses data otomatis.');
    } finally {
        isAutoFilling.value = false;
    }
};

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
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Gudang Asal (Source Warehouse)</label>
                            <select v-model="form.warehouse_id" class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50">
                                <option value="">Pilih Gudang Asal</option>
                                <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">{{ warehouse.name }}</option>
                            </select>
                            <p v-if="form.errors.warehouse_id" class="mt-1 text-xs text-red-500">{{ form.errors.warehouse_id }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Gudang Tujuan (Target Warehouse)</label>
                            <select v-model="form.target_warehouse_id" class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50">
                                <option value="">Pilih Gudang Tujuan (Default: Sama)</option>
                                <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">{{ warehouse.name }}</option>
                            </select>
                            <p v-if="form.errors.target_warehouse_id" class="mt-1 text-xs text-red-500">{{ form.errors.target_warehouse_id }}</p>
                        </div>
                        <div class="md:col-span-2 xl:col-span-4">
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
                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                @click="autoFillFromStockAndMapping"
                                :disabled="isAutoFilling || !form.warehouse_id"
                                class="inline-flex items-center gap-2 rounded-xl border border-blue-500/30 bg-blue-500/10 px-4 py-2 text-sm font-semibold text-blue-600 dark:text-blue-400 hover:bg-blue-500/25 disabled:opacity-50 transition-colors"
                            >
                                <SparklesIcon class="h-4 w-4" v-if="!isAutoFilling" />
                                <svg class="animate-spin h-4 w-4 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" v-else>
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Isi Otomatis
                            </button>
                            <button type="button" @click="addItem" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500">
                                <PlusIcon class="h-4 w-4" />
                                Add Row
                            </button>
                        </div>
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
                                        :options="initialOptions"
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

                            <div class="mt-3 pt-3 border-t border-slate-200 dark:border-slate-800/60 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                                <div class="flex flex-wrap items-center gap-3 text-slate-500 dark:text-slate-400">
                                    <span v-if="resolveProduct(item.source_product_id)">Source: {{ resolveProduct(item.source_product_id)?.name }}</span>
                                    <span v-if="resolveProduct(item.target_product_id)">Target: {{ resolveProduct(item.target_product_id)?.name }}</span>
                                    <span v-if="resolveProduct(item.target_product_id)?.unit?.symbol || resolveProduct(item.source_product_id)?.unit?.symbol">
                                        Unit: {{ resolveProduct(item.target_product_id)?.unit?.symbol || resolveProduct(item.source_product_id)?.unit?.symbol }}
                                    </span>
                                </div>
                                
                                <div v-if="item.source_product_id && item.target_product_id" class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-1 sm:mt-0">
                                    <span class="text-slate-500">Cost Price: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ formatCurrency(getLineCostPrice(item)) }}</span></span>
                                    <span class="text-slate-500">Sell Price: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ formatCurrency(getLineSellPrice(item)) }}</span></span>
                                    <span class="text-slate-500 flex items-center gap-2">
                                        Profit: 
                                        <span class="font-bold" :class="getLineProfit(item) >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500'">
                                            {{ formatCurrency(getLineProfit(item)) }} ({{ formatNumber(getLineProfitPct(item)) }}%)
                                        </span>
                                        <span v-if="getLineProfitPct(item) < 10" class="inline-flex items-center rounded bg-amber-500/10 text-amber-500 border border-amber-500/20 text-[10px] font-bold px-1.5 py-0.5">
                                            Margin Rendah (<10%)
                                        </span>
                                        <span v-else-if="getLineProfitPct(item) > 50" class="inline-flex items-center rounded bg-indigo-500/10 text-indigo-500 border border-indigo-500/20 text-[10px] font-bold px-1.5 py-0.5">
                                            Margin Tinggi (>50%)
                                        </span>
                                    </span>
                                </div>
                            </div>
                            <p v-if="form.errors[`items.${index}.source_product_id`]" class="mt-2 text-xs text-red-500">{{ form.errors[`items.${index}.source_product_id`] }}</p>
                            <p v-if="form.errors[`items.${index}.target_product_id`]" class="mt-1 text-xs text-red-500">{{ form.errors[`items.${index}.target_product_id`] }}</p>
                            <p v-if="form.errors[`items.${index}.qty`]" class="mt-1 text-xs text-red-500">{{ form.errors[`items.${index}.qty`] }}</p>
                        </div>
                    </div>

                    <div class="mt-4 rounded-xl bg-slate-100 dark:bg-slate-800/60 px-6 py-4 flex flex-col lg:flex-row lg:items-center justify-between gap-6 text-sm">
                        <div class="flex flex-wrap gap-x-8 gap-y-4">
                            <div class="flex flex-col gap-1">
                                <span class="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold tracking-wider">Jumlah Item</span>
                                <span class="text-lg font-bold text-slate-900 dark:text-white">
                                    {{ form.items.filter(item => item.source_product_id).length }} <span class="text-xs font-normal text-slate-500">item</span>
                                </span>
                            </div>
                            <div class="flex flex-col gap-1 border-l border-slate-200 dark:border-slate-700 pl-8">
                                <span class="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold tracking-wider">Total Qty</span>
                                <span class="text-lg font-bold text-slate-900 dark:text-white">{{ formatNumber(totalQty) }}</span>
                            </div>
                            <div class="flex flex-col gap-1 border-l border-slate-200 dark:border-slate-700 pl-8">
                                <span class="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold tracking-wider">Total Cost</span>
                                <span class="text-lg font-bold text-slate-900 dark:text-white">{{ formatCurrency(totalCost) }}</span>
                            </div>
                            <div class="flex flex-col gap-1 border-l border-slate-200 dark:border-slate-700 pl-8">
                                <span class="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold tracking-wider">Total Sell</span>
                                <span class="text-lg font-bold text-slate-900 dark:text-white">{{ formatCurrency(totalSell) }}</span>
                            </div>
                        </div>
                        
                        <div class="flex flex-col gap-1 border-t lg:border-t-0 lg:border-l border-slate-200 dark:border-slate-700 pt-4 lg:pt-0 lg:pl-8">
                            <span class="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold tracking-wider">Total Profit</span>
                            <span class="text-xl font-extrabold" :class="totalProfit >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500'">
                                {{ formatCurrency(totalProfit) }} <span class="text-xs font-semibold">({{ formatNumber(totalProfitPct) }}%)</span>
                            </span>
                        </div>
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
