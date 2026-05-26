<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ArrowLeftIcon,
    TrashIcon,
    PlusIcon,
} from '@heroicons/vue/24/outline';
import { formatNumber, formatCurrency } from '@/helpers';

const props = defineProps({
    purchaseOrder: Object,
    purchaseOrders: Array,
    suppliers: Array,
    warehouses: Array,
    products: Array,
    prefill: Object,
});

import axios from 'axios';

const form = useForm({
    purchase_order_id: props.prefill?.purchase_order_id ?? props.purchaseOrder?.id ?? null,
    supplier_id: props.prefill?.supplier_id ?? props.purchaseOrder?.supplier_id ?? '',
    warehouse_id: props.prefill?.warehouse_id ?? props.purchaseOrder?.warehouse_id ?? '',
    return_date: new Date().toISOString().split('T')[0],
    reason: props.prefill?.reason ?? '',
    items: props.prefill?.items ?? (props.purchaseOrder?.items?.map(item => ({
        product_id: item.product_id,
        name: item.product?.name || 'Unknown',
        sku: item.product?.sku || '-',
        qty: item.qty,
        unit_price: item.unit_price,
    })) ?? []),
});

const addItem = () => {
    form.items.push({
        product_id: '',
        name: '',
        sku: '',
        qty: 1,
        unit_price: 0,
    });
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const onProductChange = (index, product) => {
    form.items[index].product_id = product.id;
    form.items[index].name = product.name;
    form.items[index].sku = product.sku;
};

const onPOChange = async (poId) => {
    if (!poId) return;

    try {
        const response = await axios.get(route('purchasing.purchase-returns.po-items', poId));
        const data = response.data;

        form.supplier_id = data.supplier_id;
        form.warehouse_id = data.warehouse_id;

        if (data.items.length === 0) {
            alert('No items available to return. Only items that have been received via Goods Receipt can be returned.');
        }

        form.items = data.items.map(item => ({
            product_id: item.product_id,
            name: item.name,
            sku: item.sku,
            qty: parseFloat(item.returnable_qty),
            unit_price: parseFloat(item.unit_price),
        }));
    } catch (error) {
        console.error('Error fetching PO Items:', error);
    }
};

const totalAmount = computed(() => {
    return form.items.reduce((sum, item) => sum + (item.qty * item.unit_price), 0);
});

const submit = () => {
    form.post('/purchasing/returns');
};

</script>

<template>
    <Head title="Create Purchase Return" />
    
    <AppLayout title="Purchase Returns">
        <div class="max-w-full px-4 sm:px-6 lg:px-8 mx-auto">
            <Link href="/purchasing/returns" class="inline-flex items-center gap-2 mb-4 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white">
                <ArrowLeftIcon class="h-4 w-4" /> Back to List
            </Link>

            <form @submit.prevent="submit" class="space-y-6">
                <div v-if="prefill?.goods_receipt_id" class="glass-card rounded-2xl p-4">
                    <div class="text-sm font-semibold text-slate-900 dark:text-white">
                        Prefill dari Goods Receipt: <span class="font-mono">{{ prefill.grn_number }}</span>
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Draft Purchase Return ini dibuat untuk koreksi receipt yang sudah completed. Silakan sesuaikan qty yang benar-benar ingin diretur.
                    </div>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
                    <div class="xl:col-span-4 glass-card rounded-2xl p-6 space-y-4">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Return Info</h3>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">Purchase Order (Optional)</label>
                            <select v-model="form.purchase_order_id" @change="onPOChange(form.purchase_order_id)" class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50">
                                <option :value="null">Select PO (Optional)</option>
                                <option v-for="po in purchaseOrders" :key="po.id" :value="po.id">{{ po.po_number }} - {{ po.supplier?.name }}</option>
                            </select>
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
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">Date</label>
                            <input type="date" v-model="form.return_date" class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50" required />
                        </div>
                    </div>

                    <div class="xl:col-span-8 glass-card rounded-2xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Items to Return</h3>
                            <button type="button" @click="addItem" class="text-sm font-medium text-blue-400 hover:text-blue-300 flex items-center gap-1">
                                <PlusIcon class="h-4 w-4" /> Add Item
                            </button>
                        </div>

                        <div class="space-y-3 pr-2 relative">
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
                                    <span class="text-[10px] font-bold text-slate-500 uppercase">Total</span>
                                </div>
                                <div class="col-span-4 sm:col-span-1"></div>
                            </div>

                            <div v-for="(item, index) in form.items" :key="index" :style="{ zIndex: 100 - index }" class="relative grid grid-cols-12 gap-3 items-end bg-slate-50 dark:bg-slate-800/30 p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-900 dark:bg-slate-800/50 transition-colors">
                                <div class="col-span-12 sm:col-span-4">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1 sm:hidden">Product</label>
                                    <select 
                                        :value="item.product_id" 
                                        @change="onProductChange(index, products.find(p => p.id == $event.target.value))"
                                        class="w-full rounded-lg border-0 bg-slate-50 dark:bg-slate-800 py-2.5 text-xs text-slate-900 dark:text-white focus:ring-1 focus:ring-blue-500"
                                        required
                                    >
                                        <option value="">Select Product</option>
                                        <option v-for="p in products" :key="p.id" :value="p.id">[{{ p.sku }}] {{ p.name }}</option>
                                    </select>
                                </div>
                                <div class="col-span-4 sm:col-span-2">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1 sm:hidden">Qty</label>
                                    <input type="number" v-model="item.qty" min="1" step="any" class="w-full rounded-lg border-0 bg-slate-50 dark:bg-slate-800 py-2.5 text-xs text-slate-900 dark:text-white focus:ring-1 focus:ring-blue-500 text-right" required />
                                </div>
                                <div class="col-span-4 sm:col-span-3">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1 sm:hidden">Price</label>
                                    <input type="number" v-model="item.unit_price" step="any" class="w-full rounded-lg border-0 bg-slate-50 dark:bg-slate-800 py-2.5 text-xs text-slate-900 dark:text-white focus:ring-1 focus:ring-blue-500 text-right" required />
                                </div>
                                <div class="col-span-4 sm:col-span-2 text-right py-2.5">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1 sm:hidden">Total</label>
                                    <span class="text-xs font-mono text-slate-600 dark:text-slate-300">{{ formatCurrency(item.qty * item.unit_price) }}</span>
                                </div>
                                <div class="col-span-4 sm:col-span-1 flex justify-end">
                                    <button type="button" @click="removeItem(index)" class="p-2 text-slate-500 hover:text-red-400 rounded-lg hover:bg-slate-700/50 transition-colors">
                                        <TrashIcon class="h-4 w-4" />
                                    </button>
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
                    <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Reason for Return</label>
                    <textarea v-model="form.reason" rows="3" class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50" placeholder="e.g. Defective items, wrong shipment..."></textarea>
                </div>

                <div class="flex justify-end gap-3">
                    <Link href="/purchasing/returns" class="px-6 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-700">Cancel</Link>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-600 text-sm font-semibold text-white dark:text-white hover:bg-blue-500 shadow-lg shadow-blue-900/20" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Save Return' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>



