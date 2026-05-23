<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ArrowLeftIcon, TrashIcon, PlusIcon } from '@heroicons/vue/24/outline';
import { formatNumber, formatCurrency } from '@/helpers';
import axios from 'axios';
import SearchableSelect from '@/Components/SearchableSelect.vue';
const props = defineProps({
    purchaseOrder: Object,
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

const supplierOptions = computed(() =>
    props.suppliers.map(s => ({
        id: s.id,
        label: s.name,
        ...s
    }))
);

const purchaseOrderOptions = computed(() =>
    props.purchaseOrders.map(po => ({
        id: po.id,
        label: `${po.po_number} - ${po.supplier?.name || '-'}`,
        ...po
    }))
);

const form = useForm({
    purchase_order_id: props.purchaseOrder?.id || null,
    supplier_id: props.purchaseOrder?.supplier_id || '',
    warehouse_id: props.purchaseOrder?.warehouse_id || '',
    receipt_date: new Date().toISOString().split('T')[0],
    delivery_note_number: '',
    notes: '',
    items: props.purchaseOrder?.items.map(item => ({
        product_id: item.product_id,
        name: item.product?.name,
        qty_received: item.qty - ((item.qty_received || 0) - (item.qty_returned || 0)),
        unit_cost: Math.round(parseFloat(item.unit_price || 0)),
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

const onPOChange = async (poId) => {
    if (!poId) return;
    
    try {
        const response = await axios.get(route('purchasing.receipts.po-items', poId));
        const data = response.data;

        form.supplier_id = data.supplier_id;
        form.warehouse_id = data.warehouse_id;
        
        form.items = data.items.map(item => ({
            po_item_id: item.po_item_id,
            product_id: item.product_id,
            name: item.name,
            qty_ordered: item.qty_ordered,
            remaining_qty: item.remaining_qty,
            qty_received: item.remaining_qty,
            remark: '',
        }));
    } catch (error) {
        console.error('Error fetching PO items:', error);
        alert('Failed to fetch PO items. Please try again.');
    }
};

const isWarningReceipt = (item) => {
    if (!item.qty_ordered) return false;
    const toleranceFactor = 1.1; // 10% tolerance
    const maxAllowed = Math.round(item.remaining_qty * toleranceFactor);
    return item.qty_received > (item.remaining_qty + 0.0001) && item.qty_received <= (maxAllowed + 0.0001);
};

const isOverReceipt = (item) => {
    if (!item.qty_ordered) return false;
    const toleranceFactor = 1.1; // 10% tolerance
    const maxAllowed = Math.round(item.remaining_qty * toleranceFactor);
    return item.qty_received > (maxAllowed + 0.0001);
};

const submit = () => {
    form.post('/purchasing/receipts');
};

</script>

<template>
    <Head title="Create Goods Receipt" />
    
    <AppLayout title="Goods Receipts">
        <div class="max-w-full px-4 sm:px-6 lg:px-8 mx-auto">
            <Link href="/purchasing/receipts" class="inline-flex items-center gap-2 mb-4 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white">
                <ArrowLeftIcon class="h-4 w-4" /> Back to List
            </Link>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
                    <div class="xl:col-span-4 glass-card rounded-2xl p-6 space-y-4">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Receipt Info</h3>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">Purchase Order (Optional)</label>
                            <SearchableSelect
                                v-model="form.purchase_order_id"
                                :options="purchaseOrderOptions"
                                placeholder="Search PO (Optional)..."
                                @change="(po) => onPOChange(po?.id)"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">Supplier</label>
                            <SearchableSelect
                                v-model="form.supplier_id"
                                :options="supplierOptions"
                                placeholder="Search Supplier..."
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">Warehouse</label>
                            <select v-model="form.warehouse_id" class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50" required>
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
                                    <span class="text-[10px] font-bold text-slate-500 uppercase">Qty</span>
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
                                        v-model="item.product_id"
                                        :options="productOptions"
                                        @change="(p) => onProductChange(index, p)"
                                        placeholder="Search Product..."
                                    />
                                </div>
                                <div class="col-span-4 sm:col-span-2">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1 sm:hidden">Qty</label>
                                    <input 
                                        type="number" 
                                        v-model="item.qty_received" 
                                        min="0.0001" 
                                        step="any" 
                                        class="w-full rounded-lg border-0 bg-slate-50 dark:bg-slate-800 py-2.5 text-xs text-slate-900 dark:text-white focus:ring-1 focus:ring-blue-500 text-right transition-colors"
                                        :class="{
                                            'ring-1 ring-red-500 bg-red-500/10': isOverReceipt(item),
                                            'ring-1 ring-amber-500 bg-amber-500/10': isWarningReceipt(item)
                                        }"
                                        required 
                                    />
                                    <div v-if="isOverReceipt(item)" class="text-[10px] text-red-500 mt-1 text-right">
                                        Max Allowed: {{ Math.round(item.remaining_qty * 1.1) }}
                                    </div>
                                    <div v-else-if="isWarningReceipt(item)" class="text-[10px] text-amber-500 mt-1 text-right">
                                        Over PO (Tolerance 10%)
                                    </div>
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
                    <Link href="/purchasing/receipts" class="px-6 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-700">Cancel</Link>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-600 text-sm font-semibold text-white dark:text-white hover:bg-blue-500 shadow-lg shadow-blue-900/20" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Save Receipt' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>



