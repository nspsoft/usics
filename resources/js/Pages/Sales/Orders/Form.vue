<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { formatNumber, formatCurrency } from '@/helpers';
import {
    ArrowLeftIcon,
    PlusIcon,
    TrashIcon,
    CalculatorIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    salesOrder: Object,
    soNumber: String,
    customers: Array,
    warehouses: Array,
    products: Array,
    units: Array,
    aiData: Object,
    preSelectedCustomerId: [Number, String],
});

const form = useForm({
    so_number: props.salesOrder?.so_number || props.soNumber,
    customer_po_number: props.salesOrder?.customer_po_number || props.aiData?.po_number || '',
    customer_id: props.salesOrder?.customer_id || props.aiData?.matched_customer_id || props.preSelectedCustomerId || '',
    warehouse_id: props.salesOrder?.warehouse_id || props.warehouses?.[0]?.id || '',
    order_date: props.salesOrder?.order_date 
        ? new Date(props.salesOrder.order_date).toISOString().split('T')[0] 
        : (props.aiData?.po_date || new Date().toISOString().split('T')[0]),
    delivery_date: props.salesOrder?.delivery_date 
        ? new Date(props.salesOrder.delivery_date).toISOString().split('T')[0] 
        : (props.aiData?.delivery_date || ''),
    discount_percent: props.salesOrder?.discount_percent || 0,
    tax_percent: props.salesOrder?.tax_percent || 11,
    notes: props.salesOrder?.notes || (props.aiData ? 'Extracted via AI Gemini' : ''),
    shipping_name: props.salesOrder?.shipping_name || '',
    shipping_address: props.salesOrder?.shipping_address || '',
    items: props.salesOrder?.items?.map(item => ({
        id: item.id,
        product_id: item.product_id,
        qty: parseFloat(item.qty),
        qty_delivered: parseFloat(item.qty_delivered || 0),
        unit_id: item.unit_id,
        unit_price: parseFloat(item.unit_price),
        discount_percent: parseFloat(item.discount_percent),
    })) || props.aiData?.items?.map(item => ({
        product_id: item.matched_product_id || '',
        qty: parseFloat(item.qty || 1),
        qty_delivered: 0,
        unit_id: props.products?.find(p => p.id === item.matched_product_id)?.unit_id || '',
        unit_price: parseFloat(item.unit_price || 0),
        discount_percent: 0,
        description: item.description // Temporary for UI reference if needed
    })) || [
        { product_id: '', qty: 1, qty_delivered: 0, unit_id: '', unit_price: 0, discount_percent: 0 }
    ],
});

const syncWithCustomer = ref(!props.salesOrder);

watch(() => form.customer_id, (newId) => {
    if (syncWithCustomer.value && newId) {
        const customer = props.customers.find(c => c.id === newId);
        if (customer) {
            form.shipping_name = customer.name;
            form.shipping_address = customer.full_address || '';
        }
    }
});

watch(syncWithCustomer, (val) => {
    if (val && form.customer_id) {
        const customer = props.customers.find(c => c.id === form.customer_id);
        if (customer) {
            form.shipping_name = customer.name;
            form.shipping_address = customer.full_address || '';
        }
    }
});

// Computed for Items Processing
const productOptions = computed(() => 
    props.products
        ? props.products
            .filter(p => p && !p.name.startsWith('SO-'))
            .map(p => ({
                id: p.id,
                label: `[${p.sku || '#' + p.id}] ${p.name}`
            }))
        : []
);

const enrichedItems = computed(() => {
    const products = props.products || [];
    return form.items.map(item => {
        const product = products.find(p => p.id === item.product_id);
        const gross = (item.qty || 0) * (item.unit_price || 0);
        const discountAmount = gross * ((item.discount_percent || 0) / 100);
        const subtotal = gross - discountAmount;
        return { ...item, product, subtotal };
    });
});

const subtotal = computed(() => enrichedItems.value.reduce((sum, item) => sum + item.subtotal, 0));
const discountAmount = computed(() => subtotal.value * ((form.discount_percent || 0) / 100));
const afterDiscount = computed(() => subtotal.value - discountAmount.value);
const taxAmount = computed(() => afterDiscount.value * ((form.tax_percent || 0) / 100));
const total = computed(() => afterDiscount.value + taxAmount.value);

const addItem = () => {
    form.items.push({ product_id: '', qty: 1, qty_delivered: 0, unit_id: '', unit_price: 0, discount_percent: 0 });
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

// Fill price and unit when product changes
const onProductChange = (item, index) => {
    if (!props.products) return;
    const product = props.products.find(p => p.id === item.product_id);
    if (product) {
        form.items[index].unit_id = product.unit?.id || product.sales_unit_id; 
        form.items[index].unit_price = parseFloat(product.selling_price || product.price || 0);
    }
};

const getPriceDeviation = (item) => {
    if (!item.product_id || !props.products) return null;
    const product = props.products.find(p => p.id === item.product_id);
    if (!product) return null;
    const standardPrice = parseFloat(product.selling_price || product.price || 0);
    if (standardPrice === 0) return null;
    const currentPrice = parseFloat(item.unit_price || 0);
    if (currentPrice === standardPrice) return { pct: 0, standard: standardPrice, color: 'text-emerald-500', bg: 'bg-emerald-500/10', label: 'Standard price' };
    const pct = ((currentPrice - standardPrice) / standardPrice) * 100;
    const absPct = Math.abs(pct);
    if (absPct <= 5) return { pct, standard: standardPrice, color: 'text-amber-500', bg: 'bg-amber-500/10', label: `${pct > 0 ? '+' : ''}${pct.toFixed(1)}%` };
    return { pct, standard: standardPrice, color: 'text-red-500', bg: 'bg-red-500/10', label: `${pct > 0 ? '+' : ''}${pct.toFixed(1)}%` };
};

const duplicatePoWarning = ref('');

const submit = async () => {
    // Check duplicate PO number before submit (only for new SO)
    if (!props.salesOrder && form.customer_po_number) {
        try {
            const res = await axios.get(route('sales.orders.check-po'), {
                params: { po_number: form.customer_po_number }
            });
            if (res.data.exists) {
                duplicatePoWarning.value = `PO Number "${form.customer_po_number}" sudah terdaftar di ${res.data.so_number}. Tidak bisa membuat SO duplikat.`;
                return;
            }
        } catch (e) {
            // If check fails, allow submission
        }
    }
    duplicatePoWarning.value = '';
    
    if (props.salesOrder) {
        form.put(route('sales.orders.update', props.salesOrder.id));
    } else {
        form.post(route('sales.orders.store'));
    }
};


</script>

<template>
    <Head :title="salesOrder ? `Edit SO ${salesOrder.so_number}` : 'New Sales Order'" />

    <AppLayout :title="salesOrder ? 'Edit Sales Order' : 'Create Sales Order'">
        <div class="max-w-full px-4 sm:px-6 lg:px-8 mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link
                    :href="route('sales.orders.index')"
                    class="p-2 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white transition-colors"
                >
                    <ArrowLeftIcon class="h-5 w-5" />
                </Link>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
                    {{ salesOrder ? `Edit ${salesOrder.so_number}` : 'New Sales Order' }}
                </h1>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Main Info -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    <div class="lg:col-span-9 space-y-6">
                        <div class="rounded-2xl glass-card p-6 space-y-4">
                            <h3 class="text-sm font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-4">Order Details</h3>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">SO Number</label>
                                    <input
                                        v-model="form.so_number"
                                        type="text"
                                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                        required
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Customer</label>
                                    <select
                                        v-model="form.customer_id"
                                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                        required
                                    >
                                        <option value="" disabled>Select Customer</option>
                                        <option v-for="c in customers" :key="c.id" :value="c.id">
                                            {{ c.name }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Warehouse</label>
                                    <select
                                        v-model="form.warehouse_id"
                                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                        required
                                    >
                                        <option value="" disabled>Select Warehouse</option>
                                        <option v-for="w in warehouses" :key="w.id" :value="w.id">
                                            {{ w.name }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Order Date</label>
                                    <input
                                        v-model="form.order_date"
                                        type="date"
                                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                        required
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Delivery Date</label>
                                    <input
                                        v-model="form.delivery_date"
                                        type="date"
                                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Items -->
                        <div class="rounded-2xl glass-card overflow-hidden">
                            <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center">
                                <h3 class="text-sm font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Order Items</h3>
                                <button type="button" @click="addItem" class="text-sm text-blue-400 hover:text-blue-300 font-medium flex items-center gap-1">
                                    <PlusIcon class="h-4 w-4" /> Add Item
                                </button>
                            </div>
                            <div class="overflow-x-auto max-h-[600px] overflow-y-auto relative">
                                <table class="w-full text-left text-sm text-slate-500 dark:text-slate-400">
                                    <thead class="bg-white dark:bg-slate-950 text-slate-200 sticky top-0 z-10">
                                        <tr>
                                            <th class="px-4 py-3 min-w-[350px]">Product</th>
                                            <th class="px-4 py-3 w-24">Qty</th>
                                            <th class="px-4 py-3 w-32">Unit</th>
                                            <th class="px-4 py-3 w-32">Price</th>
                                            <th class="px-4 py-3 w-24">Disc %</th>
                                            <th class="px-4 py-3 w-32 text-right">Subtotal</th>
                                            <th class="px-4 py-3 w-10"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                        <tr v-for="(item, index) in form.items" :key="index">
                                            <td class="px-4 py-2">
                                                <SearchableSelect
                                                    v-model="item.product_id"
                                                    :options="productOptions"
                                                    placeholder="Search Product..."
                                                    @change="onProductChange(item, index)"
                                                />
                                            </td>
                                            <td class="px-4 py-2">
                                                <input
                                                    v-model="item.qty"
                                                    type="number"
                                                    step="0.01"
                                                    class="block w-full rounded-lg border-0 bg-slate-50 dark:bg-slate-800 py-1.5 px-2 text-sm text-slate-900 dark:text-white focus:ring-1 focus:ring-blue-500"
                                                    required
                                                />
                                            </td>
                                            <td class="px-4 py-2">
                                                <select
                                                    v-model="item.unit_id"
                                                    class="block w-full rounded-lg border-0 bg-slate-50 dark:bg-slate-800 py-1.5 px-2 text-sm text-slate-900 dark:text-white focus:ring-1 focus:ring-blue-500"
                                                >
                                                    <option value="" disabled>Unit</option>
                                                    <option v-for="u in units" :key="u.id" :value="u.id">
                                                        {{ u.code }}
                                                    </option>
                                                </select>
                                            </td>
                                            <td class="px-4 py-2">
                                                <input
                                                    v-model="item.unit_price"
                                                    type="number"
                                                    step="0.01"
                                                    class="block w-full rounded-lg border-0 bg-slate-50 dark:bg-slate-800 py-1.5 px-2 text-sm text-slate-900 dark:text-white focus:ring-1 focus:ring-blue-500 text-right"
                                                    required
                                                />
                                                <div v-if="getPriceDeviation(item)" class="mt-1 flex items-center gap-1">
                                                    <span class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-bold" :class="[getPriceDeviation(item).color, getPriceDeviation(item).bg]">
                                                        {{ getPriceDeviation(item).label }}
                                                    </span>
                                                    <span v-if="getPriceDeviation(item).pct !== 0" class="text-[10px] text-slate-500 truncate" :title="'Standard: Rp ' + formatNumber(getPriceDeviation(item).standard)">
                                                        Std: {{ formatCurrency(getPriceDeviation(item).standard) }}
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-2">
                                                <input
                                                    v-model="item.discount_percent"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    max="100"
                                                    class="block w-full rounded-lg border-0 bg-slate-50 dark:bg-slate-800 py-1.5 px-2 text-sm text-slate-900 dark:text-white focus:ring-1 focus:ring-blue-500 text-center"
                                                />
                                            </td>
                                            <td class="px-4 py-2 text-right font-medium text-slate-900 dark:text-white">
                                                {{ formatCurrency(enrichedItems[index].subtotal) }}
                                            </td>
                                            <td class="px-4 py-2 text-center">
                                                <button 
                                                    type="button" 
                                                    @click="removeItem(index)" 
                                                    class="transition-colors"
                                                    :class="item.qty_delivered > 0 ? 'text-slate-300 cursor-not-allowed' : 'text-slate-500 hover:text-red-400'"
                                                    :disabled="item.qty_delivered > 0"
                                                    :title="item.qty_delivered > 0 ? 'Cannot delete delivered item' : 'Remove item'"
                                                >
                                                    <TrashIcon class="h-4 w-4" />
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="flex justify-between items-start">
                            <div class="w-1/2 space-y-4">
                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-slate-500 dark:text-slate-400">SO Number</label>
                                    <input v-model="form.so_number" type="text" class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 sm:text-sm" readonly />
                                </div>
                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-slate-500 dark:text-slate-400">PO Number (Customer)</label>
                                    <input v-model="form.customer_po_number" type="text" class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 sm:text-sm" :class="{ 'ring-2 ring-red-500': duplicatePoWarning }" placeholder="PO-XXXXX" />
                                    <p v-if="duplicatePoWarning" class="mt-1 text-xs text-red-500 font-medium">⚠️ {{ duplicatePoWarning }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Notes</label>
                                    <textarea
                                        v-model="form.notes"
                                        rows="3"
                                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 sm:text-sm"
                                        placeholder="Additional notes..."
                                    ></textarea>
                                </div>
                            </div>
                            <div class="w-1/3 space-y-3 bg-white dark:bg-slate-950 p-4 rounded-xl border border-slate-200 dark:border-slate-800">
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-500 dark:text-slate-400">Subtotal</span>
                                    <span class="text-slate-900 dark:text-white font-medium">{{ formatCurrency(subtotal) }}</span>
                                </div>
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-slate-500 dark:text-slate-400">Discount (%)</span>
                                    <input
                                        v-model="form.discount_percent"
                                        type="number"
                                        class="w-16 rounded-lg border-0 bg-slate-50 dark:bg-slate-800 py-0.5 px-2 text-right text-xs text-slate-900 dark:text-white focus:ring-1 focus:ring-blue-500"
                                    />
                                </div>
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-slate-500 dark:text-slate-400">VAT (%)</span>
                                    <input
                                        v-model="form.tax_percent"
                                        type="number"
                                        class="w-16 rounded-lg border-0 bg-slate-50 dark:bg-slate-800 py-0.5 px-2 text-right text-xs text-slate-900 dark:text-white focus:ring-1 focus:ring-blue-500"
                                    />
                                </div>
                                <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex justify-between text-base font-bold">
                                    <span class="text-slate-900 dark:text-white">Total</span>
                                    <span class="text-emerald-400">{{ formatCurrency(total) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Sidebar -->
                    <div class="lg:col-span-3 space-y-6">
                        <!-- Sold-to Preview -->
                        <div v-if="form.customer_id" class="rounded-2xl bg-white dark:bg-slate-950/50 border border-slate-200 dark:border-slate-800 p-6 space-y-4">
                             <h3 class="text-xs font-black text-slate-500 uppercase tracking-widest">Sold-to Party (Buyer)</h3>
                             <div class="space-y-1">
                                <div class="text-sm font-bold text-slate-900 dark:text-white">{{ customers.find(c => c.id === form.customer_id)?.name }}</div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">{{ customers.find(c => c.id === form.customer_id)?.full_address }}</p>
                             </div>
                        </div>

                        <!-- Ship-to Info -->
                        <div class="rounded-2xl glass-card p-6 space-y-4">
                             <div class="flex items-center justify-between mb-2">
                                <h3 class="text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest">Ship-to Party (Delivery)</h3>
                                <label class="flex items-center gap-2 cursor-pointer group">
                                    <div class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out"
                                        :class="syncWithCustomer ? 'bg-emerald-600' : 'bg-slate-700'"
                                        @click="syncWithCustomer = !syncWithCustomer">
                                        <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow transition duration-200 ease-in-out"
                                            :class="syncWithCustomer ? 'translate-x-4' : 'translate-x-0'"></span>
                                    </div>
                                    <span class="text-[10px] font-black uppercase tracking-tighter text-slate-500 group-hover:text-slate-600 dark:text-slate-300 transition-colors">Same as Buyer</span>
                                </label>
                             </div>
                             
                             <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Receiver Name</label>
                                    <input
                                        v-model="form.shipping_name"
                                        type="text"
                                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 sm:text-sm"
                                        :class="{ 'opacity-50 cursor-not-allowed': syncWithCustomer }"
                                        :disabled="syncWithCustomer"
                                        placeholder="Receiver name..."
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Shipping Address</label>
                                    <textarea
                                        v-model="form.shipping_address"
                                        rows="4"
                                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 sm:text-sm"
                                        :class="{ 'opacity-50 cursor-not-allowed': syncWithCustomer }"
                                        :disabled="syncWithCustomer"
                                        placeholder="Detailed shipping address..."
                                    ></textarea>
                                </div>
                             </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white dark:text-white shadow-lg shadow-blue-500/25 hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-slate-900 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span v-if="form.processing">Saving...</span>
                                <span v-else>Save Sales Order</span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
