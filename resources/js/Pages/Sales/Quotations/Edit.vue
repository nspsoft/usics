<script setup>
import { computed, onMounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { ArrowLeftIcon, TrashIcon, PlusIcon } from '@heroicons/vue/24/outline';
import { formatNumber, formatCurrency } from '@/helpers';

const props = defineProps({
    quotation: Object,
    customers: Array,
    products: Array,
});

const form = useForm({
    customer_id: '',
    quotation_date: '',
    valid_until: '',
    notes: '',
    items: [],
});

onMounted(() => {
    // Populate form with existing data
    form.customer_id = props.quotation.customer_id;
    form.quotation_date = props.quotation.quotation_date.split('T')[0];
    form.valid_until = props.quotation.valid_until.split('T')[0];
    form.notes = props.quotation.notes;
    
    // Map items or default to empty row if none (The core fix for the user)
    if (props.quotation.items && props.quotation.items.length > 0) {
        form.items = props.quotation.items.map(item => ({
            product_id: item.product_id,
            qty: parseFloat(item.qty),
            unit_price: parseFloat(item.unit_price),
        }));
    } else {
        form.items = [{ product_id: '', qty: 1, unit_price: 0 }];
    }
});

const productOptions = computed(() => 
    props.products
        ? props.products
            .filter(p => p && !p.name.startsWith('SO-')) // Defensive filter against SO pollution
            .map(p => ({
                id: p.id,
                label: `[${p.sku || '#' + p.id}] ${p.name}`
            }))
        : []
);

const addItem = () => {
    form.items.push({ product_id: '', qty: 1, unit_price: 0 });
};

const onProductChange = (item, index) => {
    if (!props.products) return;
    const product = props.products.find(p => p.id === item.product_id);
    if (product) {
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

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const totalAmount = computed(() => {
    return form.items.reduce((sum, item) => sum + (item.qty * item.unit_price), 0);
});

const submit = () => {
    form.put(`/sales/quotations/${props.quotation.id}`);
};

</script>

<template>
    <Head :title="`Edit Quotation ${quotation.number}`" />
    
    <AppLayout title="Quotations">
        <div class="max-w-full px-4 sm:px-6 lg:px-8 mx-auto">
            <Link :href="`/sales/quotations/${quotation.id}`" class="inline-flex items-center gap-2 mb-4 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white">
                <ArrowLeftIcon class="h-4 w-4" /> Back to Detail
            </Link>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
                    <div class="xl:col-span-4 glass-card rounded-2xl p-6 space-y-4">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Edit Quotation Info</h3>
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">Quotation Number</label>
                            <input type="text" :value="quotation.number" class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 py-2.5 text-slate-500 dark:text-slate-400 cursor-not-allowed" disabled />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">Customer</label>
                            <select v-model="form.customer_id" class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50" required>
                                <option value="">Select Customer</option>
                                <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">Quotation Date</label>
                            <input type="date" v-model="form.quotation_date" class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50" required />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">Valid Until</label>
                            <input type="date" v-model="form.valid_until" class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50" required />
                        </div>
                    </div>

                    <div class="xl:col-span-8 glass-card rounded-2xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Items</h3>
                            <button type="button" @click="addItem" class="text-sm font-medium text-blue-400 hover:text-blue-300 flex items-center gap-1">
                                <PlusIcon class="h-4 w-4" /> Add Item
                            </button>
                        </div>

                        <div class="space-y-2 relative pr-2">
                             <!-- Header Labels -->
                             <div class="hidden sm:grid grid-cols-12 gap-3 px-3 py-2 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 rounded-lg sticky top-0 z-10">
                                  <div class="col-span-5 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Product</div>
                                  <div class="col-span-2 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-center">Qty</div>
                                  <div class="col-span-2 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-right">Unit Price</div>
                                  <div class="col-span-2 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-right">Subtotal</div>
                                  <div class="col-span-1"></div>
                             </div>

                            <div v-for="(item, index) in form.items" :key="index" :style="{ zIndex: 100 - index }" class="relative grid grid-cols-12 gap-3 items-center bg-slate-50 dark:bg-slate-800/10 p-2 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors">
                                <div class="col-span-12 sm:col-span-5">
                                    <label class="sm:hidden block text-[10px] font-bold text-slate-500 uppercase mb-1">Product</label>
                                    <SearchableSelect
                                        v-model="item.product_id"
                                        :options="productOptions"
                                        placeholder="Search Product..."
                                        @change="onProductChange(item, index)"
                                    />
                                </div>
                                <div class="col-span-4 sm:col-span-2">
                                    <label class="sm:hidden block text-[10px] font-bold text-slate-500 uppercase mb-1 text-center">Qty</label>
                                    <input type="number" v-model="item.qty" min="0.0001" step="any" class="w-full rounded-lg border-0 bg-slate-50 dark:bg-slate-800 py-2.5 text-xs text-slate-900 dark:text-white focus:ring-1 focus:ring-blue-500 text-center" required />
                                </div>
                                <div class="col-span-4 sm:col-span-2">
                                    <label class="sm:hidden block text-[10px] font-bold text-slate-500 uppercase mb-1 text-right">Price</label>
                                    <input type="number" v-model="item.unit_price" step="any" class="w-full rounded-lg border-0 bg-slate-50 dark:bg-slate-800 py-2.5 text-xs text-slate-900 dark:text-white focus:ring-1 focus:ring-blue-500 text-right" required />
                                    <div v-if="getPriceDeviation(item)" class="mt-1 flex items-center gap-1">
                                        <span class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-bold" :class="[getPriceDeviation(item).color, getPriceDeviation(item).bg]">
                                            {{ getPriceDeviation(item).label }}
                                        </span>
                                        <span v-if="getPriceDeviation(item).pct !== 0" class="text-[10px] text-slate-500 truncate" :title="'Standard: Rp ' + getPriceDeviation(item).standard">
                                            Std: {{ formatCurrency(getPriceDeviation(item).standard) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-span-4 sm:col-span-2">
                                    <label class="sm:hidden block text-[10px] font-bold text-slate-500 uppercase mb-1 text-right">Subtotal</label>
                                    <div class="w-full text-xs text-slate-900 dark:text-white font-bold text-right truncate">
                                        {{ formatCurrency(item.qty * item.unit_price) }}
                                    </div>
                                </div>
                                <div class="col-span-12 sm:col-span-1 flex justify-end">
                                    <button type="button" @click="removeItem(index)" class="p-2 text-slate-600 hover:text-red-400" v-if="form.items.length > 1">
                                        <TrashIcon class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-800 flex justify-end">
                            <div class="text-right">
                                <p class="text-sm text-slate-500 dark:text-slate-400">Total</p>
                                <p class="text-xl font-bold text-slate-900 dark:text-white">{{ formatCurrency(totalAmount) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="glass-card rounded-2xl p-6">
                    <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Notes</label>
                    <textarea v-model="form.notes" rows="2" class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50" placeholder="Optional notes..."></textarea>
                </div>

                <div class="flex justify-end gap-3">
                    <Link :href="`/sales/quotations/${quotation.id}`" class="px-6 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-700">Cancel</Link>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-600 text-sm font-semibold text-white dark:text-white hover:bg-blue-500 shadow-lg shadow-blue-900/20" :disabled="form.processing">
                        {{ form.processing ? 'Saving Changes...' : 'Save Changes' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
