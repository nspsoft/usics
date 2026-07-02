<script setup>
import { computed, ref, watch } from 'vue';
import axios from 'axios';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { ArrowLeftIcon, TrashIcon, PlusIcon, SparklesIcon, XMarkIcon, ArrowPathIcon } from '@heroicons/vue/24/outline';
import { formatNumber, formatCurrency } from '@/helpers';

const props = defineProps({
    customers: Array,
    products: Array,
    quotationNumber: String,
});

const form = useForm({
    customer_id: '',
    quotation_date: new Date().toISOString().split('T')[0],
    valid_until: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
    notes: '',
    items: [{ product_id: '', qty: 1, unit_price: 0 }],
});

const currentQuotationNumber = ref(props.quotationNumber);

watch([() => form.customer_id, () => form.quotation_date], async ([newCust, newDate]) => {
    try {
        const response = await axios.get(route('sales.quotations.next-number'), {
            params: { 
                customer_id: newCust,
                quotation_date: newDate
            }
        });
        currentQuotationNumber.value = response.data.number;
    } catch (error) {
        console.error('Failed to fetch quotation number', error);
    }
});

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

const showAiModal = ref(false);
const aiModalLoading = ref(false);
const aiModalProduct = ref(null);
const aiModalResult = ref(null);
const aiModalIndex = ref(null);

const openAiSuggest = async (item, index) => {
    if (!item.product_id) return;
    const product = props.products.find(p => p.id === item.product_id);
    if (!product) return;
    
    aiModalProduct.value = product;
    aiModalIndex.value = index;
    aiModalResult.value = null;
    showAiModal.value = true;
    aiModalLoading.value = true;
    
    try {
        const response = await axios.post(route('sales.pricing-intelligence.analyze'), {
            products: [product],
            params: {
                lme_price: 580,
                exchange_rate: 16000,
                target_margin: 15,
                processing_fee: 350,
                scrap_recovery: 5
            }
        });
        if (response.data.success && response.data.data.pricing_suggestions) {
            aiModalResult.value = response.data.data.pricing_suggestions[0];
        }
    } catch (e) {
        console.error(e);
    } finally {
        aiModalLoading.value = false;
    }
};

const applyAiPrice = (price) => {
    if (aiModalIndex.value !== null) {
        form.items[aiModalIndex.value].unit_price = price;
    }
    showAiModal.value = false;
};

const submit = () => {
    form.post('/sales/quotations');
};

</script>

<template>
    <Head title="Create Quotation" />
    
    <AppLayout title="Quotations">
        <div class="max-w-full px-4 sm:px-6 lg:px-8 mx-auto">
            <Link href="/sales/quotations" class="inline-flex items-center gap-2 mb-4 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white">
                <ArrowLeftIcon class="h-4 w-4" /> Back to List
            </Link>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
                    <div class="xl:col-span-4 glass-card rounded-2xl p-6 space-y-4">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Quotation Info</h3>
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">Quotation Number</label>
                            <input type="text" :value="currentQuotationNumber" class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 py-2.5 text-slate-500 dark:text-slate-400 cursor-not-allowed" disabled />
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

                    <div class="xl:col-span-8 glass-card rounded-2xl p-6 !overflow-visible relative z-20">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Items</h3>
                            <button type="button" @click="addItem" class="text-sm font-medium text-blue-400 hover:text-blue-300 flex items-center gap-1">
                                <PlusIcon class="h-4 w-4" /> Add Item
                            </button>
                        </div>

                        <div class="space-y-2 relative pr-2 !overflow-visible">
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
                                    <div class="relative flex items-center">
                                        <input type="number" v-model="item.unit_price" step="any" class="w-full rounded-lg border-0 bg-slate-50 dark:bg-slate-800 py-2.5 pr-8 text-xs text-slate-900 dark:text-white focus:ring-1 focus:ring-blue-500 text-right font-semibold" required />
                                        <button type="button" @click="openAiSuggest(item, index)" :disabled="!item.product_id" class="absolute right-2 text-slate-400 hover:text-amber-400 disabled:opacity-30 transition-colors" title="AI Price Suggestion">
                                            <SparklesIcon class="h-4 w-4" />
                                        </button>
                                    </div>
                                    <div v-if="getPriceDeviation(item)" class="mt-1 flex items-center gap-1 justify-end">
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

                <div class="glass-card rounded-2xl p-6 relative z-0">
                    <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Notes</label>
                    <textarea v-model="form.notes" rows="2" class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50" placeholder="Optional notes..."></textarea>
                </div>

                <div class="flex justify-end gap-3">
                    <Link href="/sales/quotations" class="px-6 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-700">Cancel</Link>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-600 text-sm font-semibold text-white dark:text-white hover:bg-blue-500 shadow-lg shadow-blue-900/20" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Save Quotation' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- AI Pricing Suggestion Modal -->
        <div v-if="showAiModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4">
             <div class="glass-card rounded-2xl p-6 max-w-md w-full border border-white/10 shadow-2xl relative">
                  <button type="button" @click="showAiModal = false" class="absolute right-4 top-4 text-slate-400 hover:text-white">
                      <XMarkIcon class="h-5 w-5" />
                  </button>
                  <div class="flex items-center gap-3 text-amber-500 mb-4 border-b border-white/5 pb-3">
                      <SparklesIcon class="h-6 w-6 animate-pulse" />
                      <h4 class="text-sm font-bold uppercase tracking-wider">AI Price Suggestion</h4>
                  </div>
                  <p class="text-xs text-slate-400 mb-4">Analisis harga untuk: <span class="text-white font-bold">{{ aiModalProduct?.name }}</span></p>

                  <div v-if="aiModalLoading" class="flex flex-col items-center justify-center p-8 space-y-3">
                       <ArrowPathIcon class="h-8 w-8 animate-spin text-amber-500" />
                       <span class="text-xs text-slate-400">Menghitung HPP & Margin...</span>
                  </div>
                  <div v-else-if="aiModalResult" class="space-y-4">
                       <div class="grid grid-cols-2 gap-3 bg-slate-900/50 p-4 rounded-xl text-xs border border-white/5">
                            <div>
                                <span class="text-slate-400 block mb-0.5">Saran HPP Baru:</span>
                                <span class="text-blue-400 font-bold">{{ formatCurrency(aiModalResult.suggested_cost_price) }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block mb-0.5">Margin Target:</span>
                                <span class="text-purple-400 font-bold">{{ aiModalResult.margin_percentage }}%</span>
                            </div>
                       </div>
                       
                       <div class="space-y-2">
                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Opsi Harga Jual</span>
                            <div class="grid grid-cols-1 gap-2">
                                <button type="button" @click="applyAiPrice(aiModalResult.min_selling_price)" class="flex justify-between items-center p-3 rounded-xl bg-slate-800/40 hover:bg-slate-700/50 text-xs text-left border border-white/5 transition-colors">
                                     <div>
                                         <span class="font-semibold block">Harga Minimum</span>
                                         <span class="text-[10px] text-slate-400">Margin Terendah</span>
                                     </div>
                                     <span class="font-bold text-slate-200">{{ formatCurrency(aiModalResult.min_selling_price) }}</span>
                                </button>
                                <button type="button" @click="applyAiPrice(aiModalResult.recommended_selling_price)" class="flex justify-between items-center p-3 rounded-xl bg-emerald-500/10 hover:bg-emerald-500/20 text-xs text-left border border-emerald-500/20 transition-colors">
                                     <div>
                                         <span class="font-bold text-emerald-400 block">Rekomendasi AI</span>
                                         <span class="text-[10px] text-emerald-500/75">Margin Optimal</span>
                                     </div>
                                     <span class="font-black text-emerald-400">{{ formatCurrency(aiModalResult.recommended_selling_price) }}</span>
                                </button>
                                <button type="button" @click="applyAiPrice(aiModalResult.max_selling_price)" class="flex justify-between items-center p-3 rounded-xl bg-slate-800/40 hover:bg-slate-700/50 text-xs text-left border border-white/5 transition-colors">
                                     <div>
                                         <span class="font-semibold block">Harga Maksimum</span>
                                         <span class="text-[10px] text-slate-400">Margin Maksimal</span>
                                     </div>
                                     <span class="font-bold text-slate-200">{{ formatCurrency(aiModalResult.max_selling_price) }}</span>
                                </button>
                            </div>
                       </div>

                       <div class="bg-amber-500/5 p-3 rounded-xl border border-amber-500/10 text-[10px] text-amber-500 leading-normal">
                            <strong>Penjelasan AI:</strong> {{ aiModalResult.rationale }}
                       </div>
                  </div>
                  <div v-else class="text-center py-6 text-xs text-red-400">
                       Gagal mendapatkan saran harga. Silakan coba beberapa saat lagi.
                  </div>
             </div>
        </div>
    </AppLayout>
</template>
