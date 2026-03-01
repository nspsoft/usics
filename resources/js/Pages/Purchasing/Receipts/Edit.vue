<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ArrowLeftIcon, TrashIcon, PlusIcon } from '@heroicons/vue/24/outline';
import { formatNumber, formatCurrency } from '@/helpers';
import axios from 'axios';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    receipt: Object,
    purchaseOrders: Array,
    suppliers: Array,
    warehouses: Array,
    products: Array,
});

const productOptions = computed(() => 
    props.products.map(p => ({
        id: p.id,
        label: `[${p.code || p.sku || '-'}] ${p.name}`,
        ...p
    }))
);

const form = useForm({
    purchase_order_id: props.receipt.purchase_order_id || null,
    supplier_id: props.receipt.supplier_id || '',
    warehouse_id: props.receipt.warehouse_id || '',
    receipt_date: props.receipt.receipt_date ? String(props.receipt.receipt_date).split('T')[0].split(' ')[0] : new Date().toISOString().split('T')[0],
    delivery_note_number: props.receipt.delivery_note_number || '',
    notes: props.receipt.notes || '',
    items: props.receipt.items?.map(item => ({
        id: item.id,
        po_item_id: item.purchase_order_item_id,
        product_id: item.product_id,
        name: item.product?.name,
        qty_received: parseFloat(item.qty_received),
        remark: item.notes || '',
        qty_ordered: parseFloat(item.qty_ordered || 0),
        remaining_qty: parseFloat(item.qty_received) // Simple fallback to avoid frontend error, true validation is in backend
    })) || [],
});

const addItem = () => {
    form.items.push({ product_id: '', name: '', qty_received: 1, remark: '' });
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const onProductChange = (index, product) => {
    form.items[index].product_id = product.id;
    form.items[index].name = product.name;
};

// Disable changing PO on Edit, but keep method for layout consistency in case we want to re-enable it later
const onPOChange = async (poId) => {
    // Disabled behavior
};

const submit = () => {
    form.put(route('purchasing.receipts.update', props.receipt.id));
};

</script>

<template>
    <Head :title="`Edit Goods Receipt ${receipt.grn_number}`" />
    
    <AppLayout title="Goods Receipts">
        <div class="max-w-full px-4 sm:px-6 lg:px-8 mx-auto">
            <Link :href="route('purchasing.receipts.show', receipt.id)" class="inline-flex items-center gap-2 mb-4 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white">
                <ArrowLeftIcon class="h-4 w-4" /> Back to Receipt
            </Link>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
                    <div class="xl:col-span-4 glass-card rounded-2xl p-6 space-y-4">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Receipt Info</h3>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">Purchase Order</label>
                            <select v-model="form.purchase_order_id" disabled class="w-full rounded-xl border-0 bg-slate-100 dark:bg-slate-800 text-slate-500 cursor-not-allowed">
                                <option :value="null">None</option>
                                <option v-for="po in purchaseOrders" :key="po.id" :value="po.id">{{ po.po_number }} - {{ po.supplier?.name }}</option>
                            </select>
                            <span class="text-xs text-slate-400">PO reference cannot be changed.</span>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">Supplier</label>
                            <select v-model="form.supplier_id" disabled class="w-full rounded-xl border-0 bg-slate-100 dark:bg-slate-800 text-slate-500 cursor-not-allowed" required>
                                <option value="">Select Supplier</option>
                                <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">Warehouse</label>
                            <select v-model="form.warehouse_id" disabled class="w-full rounded-xl border-0 bg-slate-100 dark:bg-slate-800 text-slate-500 cursor-not-allowed" required>
                                <option value="">Select Warehouse</option>
                                <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">Receipt Date</label>
                            <input type="date" v-model="form.receipt_date" class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50" required />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">No SJ Supplier</label>
                            <input
                                type="text"
                                v-model="form.delivery_note_number"
                                class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                placeholder="Contoh: SJ/02/2026/123"
                            />
                        </div>
                    </div>

                    <div class="xl:col-span-8 glass-card rounded-2xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Items Received</h3>
                            <button type="button" @click="addItem" class="text-sm font-medium text-blue-400 hover:text-blue-300 flex items-center gap-1">
                                <PlusIcon class="h-4 w-4" /> Add Item
                            </button>
                        </div>

                        <div class="space-y-3 pr-2 relative">
                            <!-- Header Row -->
                            <div class="grid grid-cols-12 gap-3 px-3 py-2 mb-2 hidden sm:grid sticky top-0 z-10 bg-white dark:bg-slate-900 border-b border-slate-100 dark:border-slate-800">
                                <div class="col-span-12 sm:col-span-5">
                                    <span class="text-[10px] font-bold text-slate-500 uppercase">Product</span>
                                </div>
                                <div class="col-span-4 sm:col-span-2 text-right pr-2">
                                    <span class="text-[10px] font-bold text-slate-500 uppercase">Qty Validated</span>
                                </div>
                                <div class="col-span-4 sm:col-span-3 pl-2">
                                    <span class="text-[10px] font-bold text-slate-500 uppercase">Remark</span>
                                </div>
                                <div class="col-span-4 sm:col-span-2"></div>
                            </div>

                            <div v-for="(item, index) in form.items" :key="index" :style="{ zIndex: 100 - index }" class="relative grid grid-cols-12 gap-3 items-end bg-slate-50 dark:bg-slate-800/30 p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-900 dark:bg-slate-800/50 transition-colors">
                                <div class="col-span-12 sm:col-span-5">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1 sm:hidden">Product</label>
                                    <SearchableSelect 
                                        v-if="!item.po_item_id"
                                        v-model="item.product_id"
                                        :options="productOptions"
                                        @change="(p) => onProductChange(index, p)"
                                        placeholder="Search Product..."
                                    />
                                    <div v-else class="py-2.5 px-3 bg-slate-100 dark:bg-slate-800 rounded-lg text-xs text-slate-600 dark:text-slate-400 whitespace-nowrap overflow-hidden text-ellipsis">
                                        {{ item.name }}
                                    </div>
                                </div>
                                <div class="col-span-4 sm:col-span-2">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1 sm:hidden">Qty</label>
                                    <input 
                                        type="number" 
                                        v-model="item.qty_received" 
                                        min="0.0001" 
                                        step="any" 
                                        class="w-full rounded-lg border-0 bg-slate-50 dark:bg-slate-800 py-2.5 text-xs text-slate-900 dark:text-white focus:ring-1 focus:ring-blue-500 text-right transition-colors"
                                        required 
                                    />
                                </div>
                                <div class="col-span-4 sm:col-span-3">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1 sm:hidden">Remark</label>
                                    <input type="text" v-model="item.remark" class="w-full rounded-lg border-0 bg-slate-50 dark:bg-slate-800 py-2.5 text-xs text-slate-900 dark:text-white focus:ring-1 focus:ring-blue-500" placeholder="Remark..." />
                                </div>
                                <div class="col-span-4 sm:col-span-2 flex justify-end">
                                    <button type="button" @click="removeItem(index)" class="p-2 text-slate-500 hover:text-red-400 rounded-lg hover:bg-slate-700/50 transition-colors">
                                        <TrashIcon class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="glass-card rounded-2xl p-6">
                    <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Notes</label>
                    <textarea v-model="form.notes" rows="2" class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50" placeholder="Optional notes..."></textarea>
                </div>

                <div class="flex justify-end gap-3">
                    <Link :href="route('purchasing.receipts.show', receipt.id)" class="px-6 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-700">Cancel</Link>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-600 text-sm font-semibold text-white dark:text-white hover:bg-blue-500 shadow-lg shadow-blue-900/20" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Update Receipt' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
