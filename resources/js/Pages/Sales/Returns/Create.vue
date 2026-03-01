<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ArrowLeftIcon,
    TrashIcon,
    PlusIcon,
    CheckCircleIcon,
} from '@heroicons/vue/24/outline';
import { formatNumber, formatCurrency } from '@/helpers';
import axios from 'axios';

import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    salesOrder: Object,
    salesInvoice: Object,
    salesOrders: Array,
    customers: Array,
    warehouses: Array,
    products: Array,
});

const soOptions = computed(() => props.salesOrders.map(so => ({
    id: so.id,
    label: `${so.so_number} - ${so.customer?.name}`
})));

const customerOptions = computed(() => props.customers.map(c => ({
    id: c.id,
    label: c.name
})));

const form = useForm({
    sales_invoice_id: props.salesInvoice?.id || null,
    sales_order_id: props.salesOrder?.id || props.salesInvoice?.sales_order_id || null,
    customer_id: props.salesOrder?.customer_id || props.salesInvoice?.sales_order?.customer_id || '',
    warehouse_id: props.salesOrder?.warehouse_id || '',
    return_date: new Date().toISOString().split('T')[0],
    reason: '',
    items: props.salesInvoice 
        ? props.salesInvoice.items.map(item => ({
            product_id: item.product_id,
            name: item.product.name,
            sku: item.product.sku,
            qty: parseFloat(item.qty),
            unit_price: parseFloat(item.unit_price),
        }))
        : (props.salesOrder?.items.map(item => ({
            product_id: item.product_id,
            name: item.product.name,
            sku: item.product.sku,
            qty: parseFloat(item.qty),
            unit_price: parseFloat(item.unit_price),
        })) || []),
});

const isLoading = ref(false);

const onSOChange = async (soId) => {
    if (!soId) return;

    isLoading.value = true;
    try {
        const response = await axios.get(route('sales.returns.so-items', soId));
        const data = response.data;

        form.customer_id = data.customer_id;
        form.warehouse_id = data.warehouse_id;

        if (data.items.length === 0) {
            alert('No items available to return for this Sales Order. Only items that have been officially DELIVERED can be returned.');
        }

        form.items = data.items.map(item => ({
            product_id: item.product_id,
            name: item.name,
            sku: item.sku,
            qty: parseFloat(item.returnable_qty),
            unit_price: parseFloat(item.unit_price),
        }));
    } catch (error) {
        console.error('Error fetching SO Items:', error);
        alert('Failed to load Sales Order items. Please check if the SO is correct and has been delivered.');
    } finally {
        isLoading.value = false;
    }
};

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

const totalAmount = computed(() => {
    return form.items.reduce((sum, item) => sum + (item.qty * item.unit_price), 0);
});

const submit = () => {
    form.post(route('sales.returns.store'));
};


// If coming from another page with sales_order_id, trigger the change logic
onMounted(() => {
    if (form.sales_order_id) {
        // We don't necessarily want to override if it was pre-populated by props, 
        // but the current controller only passes basic props.
        // If items are empty, load them.
        if (form.items.length === 0) {
            onSOChange(form.sales_order_id);
        }
    }
});
</script>

<template>
    <Head title="Create Sales Return" />
    
    <AppLayout title="Sales Returns">
        <div class="max-w-full px-4 sm:px-6 lg:px-8 mx-auto">
            <Link :href="route('sales.returns.index')" class="inline-flex items-center gap-2 mb-4 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white">
                <ArrowLeftIcon class="h-4 w-4" /> Back to List
            </Link>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
                    <!-- Column 1: Info -->
                    <div class="xl:col-span-4 glass-card rounded-2xl p-6 space-y-4">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Return Info</h3>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">Sales Order (Optional)</label>
                            <SearchableSelect 
                                v-model="form.sales_order_id" 
                                :options="soOptions" 
                                placeholder="Search SO number..."
                                @change="onSOChange(form.sales_order_id)"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">Customer</label>
                            <SearchableSelect 
                                v-model="form.customer_id" 
                                :options="customerOptions" 
                                placeholder="Search Customer..."
                                required
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
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">Date</label>
                            <input type="date" v-model="form.return_date" class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50" required />
                        </div>
                    </div>

                    <!-- Column 2 & 3: Items -->
                    <div class="xl:col-span-8 glass-card rounded-2xl p-6 flex flex-col">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Items Returned</h3>
                            <button type="button" @click="addItem" class="text-sm font-medium text-blue-400 hover:text-blue-300 flex items-center gap-1">
                                <PlusIcon class="h-4 w-4" /> Add Item
                            </button>
                        </div>

                        <div class="flex-1 space-y-3 overflow-y-auto max-h-[600px] pr-2 custom-scrollbar relative">
                            <!-- Header Row -->
                            <div class="grid grid-cols-12 gap-3 px-3 py-2 mb-2 hidden sm:grid sticky top-0 z-10 bg-white dark:bg-slate-900 border-b border-slate-100 dark:border-slate-800">
                                <div class="col-span-12 sm:col-span-4">
                                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Product</span>
                                </div>
                                <div class="col-span-4 sm:col-span-2 text-right pr-2">
                                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Qty</span>
                                </div>
                                <div class="col-span-4 sm:col-span-3 text-right pr-2">
                                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Price</span>
                                </div>
                                <div class="col-span-4 sm:col-span-2 text-right pr-2">
                                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Total</span>
                                </div>
                                <div class="col-span-1"></div>
                            </div>

                            <div v-for="(item, index) in form.items" :key="index" :style="{ zIndex: 100 - index }" class="relative grid grid-cols-12 gap-3 items-center bg-slate-50 dark:bg-slate-800/20 p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/40 transition-all border border-transparent hover:border-slate-200 dark:border-slate-700/50 shadow-sm">
                                <div class="col-span-12 sm:col-span-4">
                                    <select 
                                        :value="item.product_id" 
                                        @change="onProductChange(index, products.find(p => p.id == $event.target.value))"
                                        class="w-full rounded-lg border-0 bg-slate-50 dark:bg-slate-800 py-2 text-xs text-slate-900 dark:text-white focus:ring-1 focus:ring-blue-500"
                                        required
                                    >
                                        <option value="">Select Product</option>
                                        <option v-for="p in products" :key="p.id" :value="p.id">[{{ p.sku }}] {{ p.name }}</option>
                                    </select>
                                </div>
                                <div class="col-span-4 sm:col-span-2">
                                    <input type="number" v-model="item.qty" min="0.0001" step="any" class="w-full rounded-lg border-0 bg-slate-50 dark:bg-slate-800 py-2 text-xs text-slate-900 dark:text-white focus:ring-1 focus:ring-blue-500 text-right font-mono" required />
                                </div>
                                <div class="col-span-4 sm:col-span-3">
                                    <input type="number" v-model="item.unit_price" step="any" class="w-full rounded-lg border-0 bg-slate-50 dark:bg-slate-800 py-2 text-xs text-slate-900 dark:text-white focus:ring-1 focus:ring-blue-500 text-right font-mono" required />
                                </div>
                                <div class="col-span-4 sm:col-span-2 text-right">
                                    <span class="text-xs font-mono font-bold text-slate-600 dark:text-slate-300">{{ formatCurrency(item.qty * item.unit_price) }}</span>
                                </div>
                                <div class="col-span-1 flex justify-end">
                                    <button type="button" @click="removeItem(index)" class="p-1.5 text-slate-500 hover:text-red-400 rounded-lg hover:bg-slate-700/50 transition-colors">
                                        <TrashIcon class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>

                            <div v-if="isLoading" class="py-12 text-center">
                                <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-500 border-t-transparent"></div>
                                <p class="mt-2 text-sm text-slate-500 font-medium">Fetching items from Sales Order...</p>
                            </div>

                            <div v-else-if="form.items.length === 0" class="py-12 text-center border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl">
                                <p class="text-sm text-slate-500 italic">No items added yet. Search SO or manually add items.</p>
                            </div>
                        </div>

                        <!-- Grand Total Area -->
                        <div class="mt-6 pt-6 border-t border-slate-200 dark:border-slate-800">
                            <div class="flex justify-between items-center bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 rounded-2xl p-4 border border-slate-200 dark:border-slate-700/50">
                                <span class="text-slate-500 dark:text-slate-400 font-bold uppercase tracking-widest text-xs">Grand Total</span>
                                <span class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">{{ formatCurrency(totalAmount) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Column 4: Reason -->
                <div class="glass-card rounded-2xl p-6">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-3">Reason for Return</label>
                    <textarea v-model="form.reason" rows="3" class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 text-sm py-3" placeholder="e.g. Defective items, customer changed mind..."></textarea>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3 pt-4">
                    <Link :href="route('sales.returns.index')" class="px-8 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-sm font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-700 transition-all border border-slate-200 dark:border-slate-700">Cancel</Link>
                    <button type="submit" class="px-8 py-3 rounded-xl bg-blue-600 text-sm font-bold text-white dark:text-white hover:bg-blue-500 shadow-xl shadow-blue-900/30 transition-all active:scale-95" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Save Return' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #1e293b;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #334155;
}
</style>



