<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ArrowLeftIcon,
    TrashIcon,
    PlusIcon,
    MagnifyingGlassIcon,
    DocumentTextIcon,
    CalendarIcon,
    ReceiptPercentIcon,
    CalculatorIcon,
    CheckBadgeIcon,
    XCircleIcon,
} from '@heroicons/vue/24/outline';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { formatNumber, formatCurrency, formatDate } from '@/helpers';

const props = defineProps({
    suppliers: Array,
    unbilledReceipts: Array,
    preselectedSupplier: String,
    preselectedPO: String,
    preselectedGR: String,
    nextInvoiceNumber: String,
});

import { onMounted } from 'vue';

const form = useForm({
    supplier_id: props.preselectedSupplier || '',
    purchase_order_id: props.preselectedPO || null,
    invoice_number: props.nextInvoiceNumber || '',
    invoice_date: new Date().toISOString().split('T')[0],
    due_date: '',
    subtotal: 0,
    tax_percent: 11,
    tax_amount: 0,
    discount_total: 0,
    total_amount: 0,
    notes: '',
    items: [],
});

const supplierOptions = computed(() => 
    props.suppliers.map(s => ({
        id: s.id,
        label: `[${s.code}] ${s.name}`,
        ...s
    }))
);

const onSupplierChange = (supplierId) => {
    if (!supplierId) {
        form.items = [];
        return;
    }
    // Refresh to get unbilled receipts for this supplier
    router.get(route('purchasing.invoices.create'), { supplier_id: supplierId }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: (page) => {
            // Logic to handle if we want to auto-add items
        }
    });
};

const toggleReceiptItems = (receipt, checked) => {
    if (checked) {
        // Add items from this receipt
        receipt.items.forEach(item => {
            // Check if already added
            if (!form.items.some(i => i.goods_receipt_item_id === item.id)) {
                const qty = parseFloat(item.qty_received - (item.qty_invoiced || 0));
                
                // Only add if there is quantity to invoice
                if (qty > 0) {
                    const unitPrice = parseFloat(item.unit_cost);
                    
                    form.items.push({
                        goods_receipt_item_id: item.id,
                        product_id: item.product_id,
                        name: item.product?.name,
                        qty: qty,
                        unit_price: unitPrice,
                        discount_percent: 0,
                        discount_amount: 0,
                        subtotal: qty * unitPrice,
                    });
                }
            }
        });
        
        // Auto-set PO ID if not set
        if (!form.purchase_order_id && receipt.purchase_order_id) {
            form.purchase_order_id = receipt.purchase_order_id;
        }
    } else {
        // Remove items from this receipt
        form.items = form.items.filter(i => 
            !receipt.items.some(ri => ri.id === i.goods_receipt_item_id)
        );
    }
};

const calculateRow = (index) => {
    const item = form.items[index];
    const baseSubtotal = (item.qty || 0) * (item.unit_price || 0);
    item.discount_amount = baseSubtotal * ((item.discount_percent || 0) / 100);
    item.subtotal = baseSubtotal - item.discount_amount;
    calculateTotals();
};

const calculateTotals = () => {
    form.subtotal = form.items.reduce((sum, item) => sum + (item.subtotal || 0), 0);
    form.tax_amount = (form.subtotal - form.discount_total) * (form.tax_percent / 100);
    form.total_amount = (form.subtotal - form.discount_total) + form.tax_amount;
};

// Initial calc
watch(() => form.items, () => calculateTotals(), { deep: true });
watch(() => [form.tax_percent, form.discount_total], () => calculateTotals());

const submit = () => {
    form.post(route('purchasing.invoices.store'));
};

const isReceiptSelected = (receiptId) => {
    // A receipt is selected if any of its items are in the form.items
    const receipt = props.unbilledReceipts.find(r => r.id === receiptId);
    if (!receipt) return false;
    return receipt.items.some(ri => form.items.some(fi => fi.goods_receipt_item_id === ri.id));
};

onMounted(() => {
    if (props.preselectedGR && props.unbilledReceipts.length > 0) {
        const receipt = props.unbilledReceipts.find(r => r.id == props.preselectedGR);
        if (receipt) {
            toggleReceiptItems(receipt, true);
        }
    }
});

</script>

<template>
    <Head title="Create Purchase Invoice" />
    
    <AppLayout title="Create Purchase Invoice">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center gap-4 mb-8">
                <Link 
                    :href="route('purchasing.invoices.index')" 
                    class="p-2.5 rounded-xl glass-card text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:border-slate-200 dark:border-slate-700 transition-all"
                >
                    <ArrowLeftIcon class="h-5 w-5" />
                </Link>
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Record Supplier Invoice</h2>
                    <p class="text-sm text-slate-500 mt-1">Convert Received Goods into a Billing Statement</p>
                </div>
            </div>

            <!-- Flash Error Message -->
            <div v-if="$page.props.flash?.error || Object.keys(form.errors).length > 0" class="mb-8 rounded-2xl bg-red-500/10 border border-red-500/20 p-4 flex items-start gap-3">
                <XCircleIcon class="h-6 w-6 text-red-500 shrink-0" />
                <div>
                    <h4 class="font-bold text-red-500 text-sm">Action Failed</h4>
                    <p v-if="$page.props.flash?.error" class="text-xs text-red-400 mt-1">{{ $page.props.flash.error }}</p>
                    <p v-else class="text-xs text-red-400 mt-1">Please check the form for validation errors.</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                <!-- Main Form -->
                <div class="lg:col-span-8 space-y-8">
                    <!-- General Information -->
                    <div class="glass-card rounded-3xl p-6 shadow-sm overflow-hidden relative">
                        <div class="absolute top-0 right-0 p-8 opacity-5">
                            <DocumentTextIcon class="h-32 w-32 text-slate-900 dark:text-white" />
                        </div>
                        
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-2">
                            <div class="h-6 w-1 bg-blue-500 rounded-full"></div>
                            Invoice Details
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 relative z-10">
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Supplier</label>
                                <SearchableSelect 
                                    v-model="form.supplier_id"
                                    :options="supplierOptions"
                                    @change="onSupplierChange"
                                    placeholder="Select Supplier..."
                                />
                                <div v-if="form.errors.supplier_id" class="text-xs text-red-500 mt-1">{{ form.errors.supplier_id }}</div>
                            </div>

                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Invoice Number</label>
                                <div class="relative group">
                                    <DocumentTextIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-600 group-focus-within:text-blue-500 transition-colors" />
                                    <input 
                                        type="text" 
                                        v-model="form.invoice_number" 
                                        class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 pl-10 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 transition-all"
                                        placeholder="PINV-XXXXXXXX"
                                        required 
                                    />
                                </div>
                                <div v-if="form.errors.invoice_number" class="text-xs text-red-500 mt-1">{{ form.errors.invoice_number }}</div>
                            </div>

                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Invoice Date</label>
                                <div class="relative group">
                                    <CalendarIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-600 group-focus-within:text-blue-500 transition-colors" />
                                    <input 
                                        type="date" 
                                        v-model="form.invoice_date" 
                                        class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 pl-10 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 transition-all"
                                        required 
                                    />
                                </div>
                                <div v-if="form.errors.invoice_date" class="text-xs text-red-500 mt-1">{{ form.errors.invoice_date }}</div>
                            </div>

                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Due Date</label>
                                <div class="relative group">
                                    <CalendarIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-600 group-focus-within:text-blue-500 transition-colors" />
                                    <input 
                                        type="date" 
                                        v-model="form.due_date" 
                                        class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 pl-10 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 transition-all"
                                    />
                                </div>
                                <div v-if="form.errors.due_date" class="text-xs text-red-500 mt-1">{{ form.errors.due_date }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Receipt Selector -->
                    <div v-if="form.supplier_id" class="glass-card rounded-3xl p-6 shadow-sm overflow-hidden">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-6 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="h-6 w-1 bg-amber-500 rounded-full"></div>
                                Pending Receipts
                            </div>
                            <span class="text-[10px] bg-amber-500/10 text-amber-500 px-2 py-1 rounded-lg uppercase tracking-widest">Select to convert</span>
                        </h3>

                        <div v-if="unbilledReceipts.length === 0" class="text-center py-10 bg-slate-50 dark:bg-slate-800/30 rounded-2xl border border-dashed border-slate-200 dark:border-slate-700">
                            <DocumentTextIcon class="h-10 w-10 text-slate-600 mx-auto mb-3" />
                            <p class="text-slate-500 dark:text-slate-400 text-sm italic">No unconverted goods receipts found for this supplier.</p>
                        </div>

                        <div v-else class="space-y-3">
                            <div v-for="receipt in unbilledReceipts" :key="receipt.id" 
                                class="flex items-center gap-4 p-4 rounded-2xl border transition-all cursor-pointer"
                                :class="isReceiptSelected(receipt.id) ? 'bg-blue-500/10 border-blue-500/50 shadow-lg shadow-blue-500/5' : 'bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 border-transparent hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 hover:border-slate-200 dark:border-slate-700'"
                                @click="toggleReceiptItems(receipt, !isReceiptSelected(receipt.id))"
                            >
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl transition-colors"
                                    :class="isReceiptSelected(receipt.id) ? 'bg-blue-500 text-slate-900 dark:text-white' : 'bg-slate-700 text-slate-500 dark:text-slate-400'"
                                >
                                    <div v-if="isReceiptSelected(receipt.id)" class="text-slate-900 dark:text-white">
                                        <PlusIcon class="h-5 w-5 rotate-45" />
                                    </div>
                                    <div v-else>
                                        <PlusIcon class="h-5 w-5" />
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <div class="text-sm font-bold text-slate-900 dark:text-white">{{ receipt.grn_number }}</div>
                                            <div class="text-xs text-slate-500 mt-0.5" v-if="receipt.purchase_order">PO Ref: {{ receipt.purchase_order.po_number }}</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-xs font-bold text-slate-600 dark:text-slate-300">Items: {{ receipt.items.length }}</div>
                                            <div class="text-[10px] text-slate-500 uppercase mt-0.5">{{ formatDate(receipt.receipt_date) }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="glass-card rounded-3xl p-6 shadow-sm overflow-hidden min-h-[400px]">
                         <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-2">
                            <div class="h-6 w-1 bg-emerald-500 rounded-full"></div>
                            Invoice Items
                        </h3>
                        
                        <div class="overflow-x-auto -mx-6 px-6">
                            <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800 text-left">
                                <thead>
                                    <tr>
                                        <th class="pb-4 text-xs font-bold text-slate-500 uppercase tracking-widest pl-2">Product</th>
                                        <th class="pb-4 text-xs font-bold text-slate-500 uppercase tracking-widest text-right px-4">Qty</th>
                                        <th class="pb-4 text-xs font-bold text-slate-500 uppercase tracking-widest text-right px-4">Unit Cost</th>
                                        <th class="pb-4 text-xs font-bold text-slate-500 uppercase tracking-widest text-right px-4">Disc %</th>
                                        <th class="pb-4 text-xs font-bold text-slate-500 uppercase tracking-widest text-right pr-2">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <tr v-for="(item, index) in form.items" :key="index" class="group">
                                        <td class="py-4">
                                            <div class="text-sm font-medium text-slate-900 dark:text-white">{{ item.name }}</div>
                                            <div class="text-[10px] text-slate-500 font-mono">ID: {{ item.goods_receipt_item_id }}</div>
                                        </td>
                                        <td class="py-4 px-4 text-right">
                                            <input 
                                                type="number" 
                                                v-model="item.qty" 
                                                @input="calculateRow(index)"
                                                step="any"
                                                class="w-20 rounded-lg border-0 bg-slate-50 dark:bg-slate-800 py-1.5 text-xs text-slate-900 dark:text-white focus:ring-1 focus:ring-blue-500 text-right" 
                                                :class="{'ring-1 ring-red-500': form.errors[`items.${index}.qty`]}"
                                            />
                                            <div v-if="form.errors[`items.${index}.qty`]" class="text-[9px] text-red-500 mt-1">{{ form.errors[`items.${index}.qty`] }}</div>
                                        </td>
                                        <td class="py-4 px-4 text-right">
                                            <input 
                                                type="number" 
                                                v-model="item.unit_price" 
                                                @input="calculateRow(index)"
                                                @change="item.unit_price = Math.round(item.unit_price)"
                                                step="any"
                                                class="w-24 rounded-lg border-0 bg-slate-50 dark:bg-slate-800 py-1.5 text-xs text-slate-900 dark:text-white focus:ring-1 focus:ring-blue-500 text-right font-mono" 
                                                :class="{'ring-1 ring-red-500': form.errors[`items.${index}.unit_price`]}"
                                            />
                                            <div v-if="form.errors[`items.${index}.unit_price`]" class="text-[9px] text-red-500 mt-1">{{ form.errors[`items.${index}.unit_price`] }}</div>
                                        </td>
                                        <td class="py-4 px-4 text-right">
                                            <input 
                                                type="number" 
                                                v-model="item.discount_percent" 
                                                @input="calculateRow(index)"
                                                min="0" max="100"
                                                class="w-16 rounded-lg border-0 bg-slate-50 dark:bg-slate-800 py-1.5 text-xs text-slate-900 dark:text-white focus:ring-1 focus:ring-blue-500 text-right" 
                                            />
                                        </td>
                                        <td class="py-4 text-right text-sm font-bold text-slate-900 dark:text-white pr-2 font-mono">
                                            {{ formatNumber(item.subtotal) }}
                                        </td>
                                    </tr>
                                    <tr v-if="form.items.length === 0">
                                        <td colspan="5" class="py-12 text-center text-slate-600 italic">
                                            Select one or more receipts above to add items.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Summary Sidebar -->
                <div class="lg:col-span-4 space-y-8">
                    <div class="glass-card rounded-3xl p-6 shadow-lg sticky top-8">
                         <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-2">
                            <CalculatorIcon class="h-5 w-5 text-blue-400" />
                            Bill Summary
                        </h3>

                        <div class="space-y-4">
                            <div class="flex justify-between text-sm py-2 border-b border-slate-200 dark:border-slate-800">
                                <span class="text-slate-500 uppercase tracking-widest text-[10px] font-bold">Subtotal</span>
                                <span class="text-slate-900 dark:text-white font-mono">{{ formatCurrency(form.subtotal) }}</span>
                            </div>

                            <div class="flex justify-between items-center text-sm py-1">
                                <span class="text-slate-500 uppercase tracking-widest text-[10px] font-bold">Discount Total</span>
                                <input 
                                    type="number" 
                                    v-model="form.discount_total" 
                                    @change="form.discount_total = Math.round(form.discount_total)"
                                    class="w-32 rounded-lg border-0 bg-slate-50 dark:bg-slate-800 py-1.5 text-xs text-slate-900 dark:text-white focus:ring-1 focus:ring-blue-500 text-right font-mono" 
                                />
                            </div>

                            <div class="flex justify-between items-center text-sm py-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-slate-500 uppercase tracking-widest text-[10px] font-bold">Tax Rate</span>
                                    <div class="flex items-center gap-1 bg-slate-50 dark:bg-slate-800 rounded-lg px-2 py-1">
                                        <input 
                                            type="number" 
                                            v-model="form.tax_percent" 
                                            class="w-8 border-0 bg-transparent p-0 text-[10px] text-slate-900 dark:text-white focus:ring-0 text-center"
                                        />
                                        <span class="text-[10px] text-slate-500 font-bold">%</span>
                                    </div>
                                </div>
                                <span class="text-slate-900 dark:text-white font-mono">{{ formatCurrency(form.tax_amount) }}</span>
                            </div>

                            <div class="mt-4 pt-4 border-t-2 border-blue-500/20 flex justify-between items-center">
                                <span class="text-blue-400 font-bold text-sm uppercase tracking-tighter">Grand Total</span>
                                <span class="text-xl font-bold bg-gradient-to-r from-white to-slate-400 bg-clip-text text-transparent font-mono">
                                    {{ formatCurrency(form.total_amount) }}
                                </span>
                            </div>

                            <div class="mt-8 space-y-4">
                                <div class="space-y-2">
                                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Notes</label>
                                    <textarea 
                                        v-model="form.notes" 
                                        rows="3" 
                                        class="w-full rounded-2xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 text-sm text-slate-900 dark:text-white placeholder:text-slate-600 focus:ring-2 focus:ring-blue-500/50 transition-all"
                                        placeholder="Add internal notes or billing comments..."
                                    ></textarea>
                                </div>

                                <button 
                                    type="submit" 
                                    :disabled="form.processing || form.items.length === 0"
                                    class="w-full relative group overflow-hidden rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 p-4 text-center text-sm font-bold text-slate-900 dark:text-white shadow-xl shadow-blue-500/20 hover:from-blue-500 hover:to-indigo-500 transition-all disabled:opacity-50 disabled:grayscale"
                                >
                                    <div class="absolute inset-0 bg-white/20 translate-y-full group-hover:translate-y-0 transition-transform duration-300"></div>
                                    <div class="relative flex items-center justify-center gap-2">
                                        <CheckBadgeIcon v-if="!form.processing" class="h-5 w-5" />
                                        <div v-else class="h-5 w-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                                        {{ form.processing ? 'Recording Invoice...' : 'Finalize & Record' }}
                                    </div>
                                </button>
                                
                                <p v-if="form.items.length === 0" class="text-[10px] text-center text-amber-500/70 animate-pulse font-medium italic">
                                    Select receipts to enable submission
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>



