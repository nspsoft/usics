<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    PlusIcon,
    TrashIcon,
    ArrowLongRightIcon,
    MagnifyingGlassIcon,
} from '@heroicons/vue/24/outline';
import axios from 'axios';

const props = defineProps({
    transfer: Object,
    transferNumber: String,
    warehouses: Array,
    products: Array,
});

const form = useForm({
    transfer_number: props.transferNumber || '',
    source_warehouse_id: '',
    destination_warehouse_id: '',
    transfer_date: new Date().toISOString().slice(0, 10),
    notes: '',
    items: [],
});

const productSearch = ref('');

const filteredProducts = computed(() => {
    if (!productSearch.value) return props.products.slice(0, 50);
    const q = productSearch.value.toLowerCase();
    return props.products.filter(p =>
        p.name.toLowerCase().includes(q) || p.sku?.toLowerCase().includes(q)
    ).slice(0, 50);
});

const destinationWarehouses = computed(() => {
    return props.warehouses.filter(w => w.id != form.source_warehouse_id);
});

const addItem = (product) => {
    if (form.items.find(i => i.product_id === product.id)) return;
    form.items.push({
        product_id: product.id,
        product_name: product.name,
        product_sku: product.sku,
        unit_symbol: product.unit?.symbol || product.unit?.name || '-',
        qty_requested: 1,
        stock_available: null,
    });
    productSearch.value = '';
    // Fetch current stock
    if (form.source_warehouse_id) {
        fetchStock(form.items.length - 1);
    }
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const fetchStock = async (index) => {
    const item = form.items[index];
    if (!form.source_warehouse_id || !item) return;
    try {
        const res = await axios.get('/inventory/stock-check', {
            params: { product_id: item.product_id, warehouse_id: form.source_warehouse_id }
        });
        form.items[index].stock_available = res.data.qty;
    } catch {
        form.items[index].stock_available = null;
    }
};

const onSourceChange = () => {
    form.items.forEach((_, i) => fetchStock(i));
};

const submit = () => {
    form.post('/inventory/transfers', {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Create Stock Transfer" />

    <AppLayout title="Create Stock Transfer">
        <form @submit.prevent="submit" class="max-w-5xl mx-auto space-y-6">
            <!-- Header Card -->
            <div class="rounded-2xl glass-card p-6">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Transfer Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase mb-1">Transfer Number</label>
                        <input v-model="form.transfer_number" type="text" readonly
                            class="w-full rounded-xl border-0 bg-slate-100 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white font-mono" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase mb-1">Transfer Date</label>
                        <input v-model="form.transfer_date" type="date"
                            class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800/50 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50" />
                        <p v-if="form.errors.transfer_date" class="text-xs text-red-400 mt-1">{{ form.errors.transfer_date }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase mb-1">Source Warehouse</label>
                        <select v-model="form.source_warehouse_id" @change="onSourceChange"
                            class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800/50 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50">
                            <option value="">-- Select Source --</option>
                            <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                        </select>
                        <p v-if="form.errors.source_warehouse_id" class="text-xs text-red-400 mt-1">{{ form.errors.source_warehouse_id }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase mb-1">Destination Warehouse</label>
                        <select v-model="form.destination_warehouse_id"
                            class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800/50 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50">
                            <option value="">-- Select Destination --</option>
                            <option v-for="w in destinationWarehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                        </select>
                        <p v-if="form.errors.destination_warehouse_id" class="text-xs text-red-400 mt-1">{{ form.errors.destination_warehouse_id }}</p>
                    </div>
                </div>
                <div class="mt-4">
                    <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase mb-1">Notes (Optional)</label>
                    <textarea v-model="form.notes" rows="2"
                        class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800/50 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 resize-none"
                        placeholder="Reason for transfer..."></textarea>
                </div>
            </div>

            <!-- Warehouse Direction Visual -->
            <div v-if="form.source_warehouse_id && form.destination_warehouse_id" class="flex items-center justify-center gap-4 py-2">
                <div class="px-4 py-2 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm font-bold">
                    {{ warehouses.find(w => w.id == form.source_warehouse_id)?.name }}
                </div>
                <ArrowLongRightIcon class="h-8 w-8 text-slate-500" />
                <div class="px-4 py-2 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm font-bold">
                    {{ warehouses.find(w => w.id == form.destination_warehouse_id)?.name }}
                </div>
            </div>

            <!-- Items Card -->
            <div class="rounded-2xl glass-card p-6">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Transfer Items</h3>
                <p v-if="form.errors.items" class="text-xs text-red-400 mb-2">{{ form.errors.items }}</p>

                <!-- Product Search -->
                <div class="relative mb-4">
                    <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500" />
                    <input
                        v-model="productSearch"
                        type="search"
                        placeholder="Search product by name or SKU..."
                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800/50 py-2.5 pl-10 pr-4 text-sm text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50"
                    />
                    <div v-if="productSearch" class="absolute z-30 w-full mt-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl shadow-xl max-h-60 overflow-y-auto">
                        <button
                            v-for="p in filteredProducts"
                            :key="p.id"
                            @click.prevent="addItem(p)"
                            class="w-full text-left px-4 py-2.5 hover:bg-slate-50 dark:hover:bg-slate-800 text-sm transition-colors flex items-center justify-between"
                        >
                            <span>
                                <span class="font-mono text-xs text-slate-500">{{ p.sku }}</span>
                                <span class="ml-2 text-slate-900 dark:text-white">{{ p.name }}</span>
                            </span>
                            <PlusIcon class="h-4 w-4 text-blue-500" />
                        </button>
                        <div v-if="filteredProducts.length === 0" class="px-4 py-3 text-sm text-slate-500 italic text-center">No products found.</div>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-800/50">
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">#</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Product</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Available</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Qty to Transfer</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Unit</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            <tr v-for="(item, idx) in form.items" :key="idx" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="px-4 py-3 text-sm text-slate-500">{{ idx + 1 }}</td>
                                <td class="px-4 py-3">
                                    <div class="text-sm font-medium text-slate-900 dark:text-white">{{ item.product_name }}</div>
                                    <div class="text-xs text-slate-500 font-mono">{{ item.product_sku }}</div>
                                </td>
                                <td class="px-4 py-3 text-center text-sm">
                                    <span v-if="item.stock_available !== null" :class="item.stock_available > 0 ? 'text-emerald-400' : 'text-red-400'" class="font-mono font-bold">
                                        {{ Number(item.stock_available).toLocaleString() }}
                                    </span>
                                    <span v-else class="text-slate-500 text-xs">-</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <input v-model.number="item.qty_requested" type="number" min="0.0001" step="any"
                                        class="w-28 mx-auto rounded-lg border-0 bg-slate-50 dark:bg-slate-800 py-1.5 px-3 text-sm text-center text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50" />
                                </td>
                                <td class="px-4 py-3 text-center text-sm text-slate-500 font-mono">{{ item.unit_symbol }}</td>
                                <td class="px-4 py-3 text-center">
                                    <button @click.prevent="removeItem(idx)" class="p-1.5 rounded-lg text-slate-400 hover:text-red-400 hover:bg-red-500/10 transition-colors">
                                        <TrashIcon class="h-4 w-4" />
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="form.items.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-slate-500 text-sm italic">
                                    Search and add products above to begin transfer.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3">
                <Link href="/inventory/transfers" class="rounded-xl px-6 py-2.5 text-sm font-semibold text-slate-400 hover:text-white transition-colors">
                    Cancel
                </Link>
                <button
                    type="submit"
                    :disabled="form.processing || form.items.length === 0"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 hover:from-blue-500 hover:to-blue-400 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Save as Draft
                </button>
            </div>
        </form>
    </AppLayout>
</template>
