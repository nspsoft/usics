<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ArrowLeftIcon,
    TrashIcon,
    PlusIcon,
} from '@heroicons/vue/24/outline';

import SearchableSelect from '@/Components/SearchableSelect.vue';
import { formatCurrency } from '@/helpers';

const props = defineProps({
    purchaseOrder: Object,
    poNumber: String,
    suppliers: Array,
    warehouses: Array,
    products: Array,
    units: Array,
});

const productOptions = computed(() => {
    if (!props.products || !Array.isArray(props.products)) return [];
    return props.products.map(p => ({
        id: p.id,
        label: `[${p.code || p.sku || '-'}] ${p.name}`,
        ...p
    }));
});

const isEdit = computed(() => !!props.purchaseOrder?.id);

const form = useForm({
    po_number: props.poNumber || props.purchaseOrder?.po_number,
    supplier_id: props.purchaseOrder?.supplier_id || '',
    warehouse_id: props.purchaseOrder?.warehouse_id || '',
    order_date: props.purchaseOrder?.order_date || new Date().toISOString().split('T')[0],
    expected_date: props.purchaseOrder?.expected_date || '',
    tax_percent: props.purchaseOrder?.tax_percent ?? 11,
    notes: props.purchaseOrder?.notes || '',
    items: props.purchaseOrder?.items?.map(item => ({
        id: item.id,
        product_id: item.product_id,
        qty: parseFloat(item.qty),
        unit_price: Math.round(parseFloat(item.unit_price || 0)),
        discount_percent: parseFloat(item.discount_percent || 0),
        unit_id: item.unit_id,
        description: item.description || '',
        notes: item.notes || '',
    })) || [{
        product_id: '',
        qty: 1,
        unit_price: 0,
        discount_percent: 0,
        unit_id: null,
        description: '',
        notes: '',
    }],
});

const refreshPoNumber = async () => {
    if (isEdit.value) return;
    if (!form.supplier_id || !form.order_date) return;

    const supplier = props.suppliers?.find(s => String(s.id) === String(form.supplier_id));
    if (!supplier?.code) return;

    try {
        const res = await fetch(`${route('numbering.preview', 'purchase_order')}?SUPP_CODE=${encodeURIComponent(supplier.code)}&date=${encodeURIComponent(form.order_date)}`, {
            headers: { 'Accept': 'application/json' },
        });
        if (!res.ok) return;
        const data = await res.json();
        if (data?.preview) {
            form.po_number = data.preview;
        }
    } catch (e) {
    }
};

watch(() => [form.supplier_id, form.order_date], refreshPoNumber, { immediate: true });

const addItem = () => {
    form.items.push({
        product_id: '',
        qty: 1,
        unit_price: 0,
        discount_percent: 0,
        unit_id: null,
        description: '',
        notes: '',
    });
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const onProductChange = (index, product) => {
    if (product) {
        form.items[index].product_id = product.id;
        form.items[index].unit_price = Math.round(parseFloat(product.cost_price || 0));
        form.items[index].unit_id = product.unit_id;
        if (!form.items[index].description) {
            form.items[index].description = product.description || product.name || '';
        }
    }
};

const totalAmount = computed(() => {
    return form.items.reduce((sum, item) => {
        const subtotal = (item.qty || 0) * (item.unit_price || 0);
        const discount = subtotal * ((item.discount_percent || 0) / 100);
        return sum + (subtotal - discount);
    }, 0);
});

const submit = () => {
    if (isEdit.value) {
        form.put(route('purchasing.orders.update', props.purchaseOrder.id));
    } else {
        form.post(route('purchasing.orders.store'));
    }
};

</script>

<template>
    <Head :title="isEdit ? 'Edit Purchase Order' : 'Create Purchase Order'" />
    
    <AppLayout title="Purchase Orders">
        <div class="max-w-full px-4 sm:px-6 lg:px-8 mx-auto">
            <Link href="/purchasing/orders" class="inline-flex items-center gap-2 mb-4 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white">
                <ArrowLeftIcon class="h-4 w-4" /> Back to List
            </Link>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
                    <!-- PO Info -->
                    <div class="xl:col-span-4 glass-card rounded-2xl p-6 space-y-4">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Order Info</h3>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">PO Number</label>
                            <input type="text" v-model="form.po_number" class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 py-2.5 text-slate-500 dark:text-slate-400 cursor-not-allowed" disabled />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">Supplier</label>
                            <select v-model="form.supplier_id" class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50" required>
                                <option value="">Select Supplier</option>
                                <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">Warehouse</label>
                            <select v-model="form.warehouse_id" class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50" required>
                                <option value="">Select Warehouse</option>
                                <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">Order Date</label>
                            <input type="date" v-model="form.order_date" class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50" required />
                        </div>
                        
                         <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">Expected Date</label>
                            <input type="date" v-model="form.expected_date" class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">VAT (%)</label>
                            <input type="number" v-model="form.tax_percent" min="0" max="100" step="any" class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50" />
                        </div>
                    </div>

                    <!-- Items -->
                    <div class="xl:col-span-8 glass-card rounded-2xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Items</h3>
                            <button type="button" @click="addItem" class="text-sm font-medium text-blue-400 hover:text-blue-300 flex items-center gap-1">
                                <PlusIcon class="h-4 w-4" /> Add Item
                            </button>
                        </div>

                        <div class="space-y-3 max-h-[600px] overflow-y-auto custom-scrollbar pr-2 relative">
                            <!-- Header Row -->
                            <div class="grid grid-cols-12 gap-3 px-3 py-2 mb-2 hidden sm:grid sticky top-0 z-10 bg-white dark:bg-slate-900 border-b border-slate-100 dark:border-slate-800">
                                <div class="col-span-12 sm:col-span-4">
                                    <span class="text-[10px] font-bold text-slate-500 uppercase">Product</span>
                                </div>
                                <div class="col-span-4 sm:col-span-2 text-right pr-2">
                                    <span class="text-[10px] font-bold text-slate-500 uppercase">Qty</span>
                                </div>
                                <div class="col-span-4 sm:col-span-3 text-right pr-2">
                                    <span class="text-[10px] font-bold text-slate-500 uppercase">Price</span>
                                </div>
                                <div class="col-span-4 sm:col-span-2 text-right pr-2">
                                    <span class="text-[10px] font-bold text-slate-500 uppercase">Disc %</span>
                                </div>
                                <div class="col-span-4 sm:col-span-1"></div>
                            </div>

                            <div v-for="(item, index) in form.items" :key="index" class="grid grid-cols-12 gap-3 items-end bg-slate-50 dark:bg-slate-800/30 p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-900 dark:bg-slate-800/50 transition-colors">
                                <div class="col-span-12 sm:col-span-4">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1 sm:hidden">Product</label>
                                    <SearchableSelect 
                                        v-model="item.product_id"
                                        :options="productOptions"
                                        @change="(p) => onProductChange(index, p)"
                                        placeholder="Search Product..."
                                    />
                                </div>
                                <div class="col-span-4 sm:col-span-2">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1 sm:hidden">Qty</label>
                                    <input type="number" v-model="item.qty" min="0.0001" step="any" class="w-full rounded-lg border-0 bg-slate-50 dark:bg-slate-800 py-2.5 text-xs text-slate-900 dark:text-white focus:ring-1 focus:ring-blue-500 text-right" required />
                                </div>
                                <div class="col-span-4 sm:col-span-3">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1 sm:hidden">Price</label>
                                    <input type="number" v-model="item.unit_price" @change="item.unit_price = Math.round(item.unit_price)" step="any" class="w-full rounded-lg border-0 bg-slate-50 dark:bg-slate-800 py-2.5 text-xs text-slate-900 dark:text-white focus:ring-1 focus:ring-blue-500 text-right" required />
                                </div>
                                <div class="col-span-4 sm:col-span-2">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1 sm:hidden">Disc %</label>
                                    <input type="number" v-model="item.discount_percent" min="0" max="100" step="any" class="w-full rounded-lg border-0 bg-slate-50 dark:bg-slate-800 py-2.5 text-xs text-slate-900 dark:text-white focus:ring-1 focus:ring-blue-500 text-right" />
                                </div>
                                <div class="col-span-4 sm:col-span-1 flex justify-end">
                                    <button type="button" @click="removeItem(index)" class="p-2 text-slate-500 hover:text-red-400 rounded-lg hover:bg-slate-700/50 transition-colors">
                                        <TrashIcon class="h-4 w-4" />
                                    </button>
                                </div>

                                <div class="col-span-12">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Remarks</label>
                                    <input type="text" v-model="item.notes" maxlength="255" class="w-full rounded-lg border-0 bg-slate-50 dark:bg-slate-800 py-2.5 text-xs text-slate-900 dark:text-white focus:ring-1 focus:ring-blue-500" placeholder="Remarks per item..." />
                                </div>
                            </div>
                        </div>

                         <div class="mt-6 pt-6 border-t border-slate-200 dark:border-slate-800">
                            <div class="flex justify-between items-center">
                                <span class="text-slate-500 dark:text-slate-400 font-medium">Grand Total</span>
                                <span class="text-xl font-bold text-slate-900 dark:text-white">{{ formatCurrency(totalAmount) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="glass-card rounded-2xl p-6">
                    <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Notes</label>
                    <textarea v-model="form.notes" rows="3" class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50" placeholder="Optional notes..."></textarea>
                </div>

                <div class="flex justify-end gap-3">
                    <Link href="/purchasing/orders" class="px-6 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-700">Cancel</Link>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-600 text-sm font-semibold text-white dark:text-white hover:bg-blue-500 shadow-lg shadow-blue-900/20" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Save Purchase Order' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
