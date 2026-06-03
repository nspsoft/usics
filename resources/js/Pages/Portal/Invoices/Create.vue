<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import { 
    ArrowLeftIcon, 
    BanknotesIcon, 
    DocumentTextIcon,
    CheckCircleIcon
} from '@heroicons/vue/24/outline';
import { formatDate } from '@/helpers';

const props = defineProps({
    order: Object,
    deliveries: Array,
});

const selectedDeliveryIds = ref([]);

const toggleDelivery = (id) => {
    if (selectedDeliveryIds.value.includes(id)) {
        selectedDeliveryIds.value = selectedDeliveryIds.value.filter(dId => dId !== id);
    } else {
        selectedDeliveryIds.value.push(id);
    }
};

const billableItems = computed(() => {
    let items = [];
    if (props.deliveries) {
        props.deliveries.forEach(delivery => {
            if (selectedDeliveryIds.value.includes(delivery.id)) {
                delivery.items.forEach(item => {
                    items.push({
                        grn_number: delivery.grn_number,
                        delivery_note: delivery.delivery_note_number,
                        receipt_date: delivery.receipt_date,
                        product_name: item.product_name,
                        qty: item.qty,
                        unit_price: item.unit_price,
                        subtotal: item.subtotal
                    });
                });
            }
        });
    }
    return items;
});

const subtotal = computed(() => {
    return billableItems.value.reduce((sum, item) => sum + item.subtotal, 0);
});

const taxAmount = computed(() => {
    return subtotal.value * (props.order.tax_percent / 100);
});

const totalAmount = computed(() => {
    return subtotal.value + taxAmount.value;
});

const form = useForm({
    purchase_order_id: props.order.id,
    invoice_number: '',
    invoice_date: new Date().toISOString().split('T')[0],
    due_date: new Date(new Date().setDate(new Date().getDate() + 30)).toISOString().split('T')[0],
    notes: '',
    selected_gr_ids: [],
});

const submit = () => {
    form.selected_gr_ids = selectedDeliveryIds.value;
    
    if (confirm('Are you sure you want to submit this invoice?')) {
        form.post(route('portal.invoices.store'));
    }
};
</script>

<template>
    <PortalLayout title="Create Invoice">
        <div class="max-w-5xl mx-auto">
            <!-- Header -->
             <div class="flex items-center gap-4 mb-6">
                <Link :href="route('portal.purchase-orders.show', order.id)" class="p-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-slate-700 dark:hover:text-white transition-colors">
                    <ArrowLeftIcon class="h-5 w-5" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Create Invoice</h1>
                    <p class="text-sm text-slate-500">
                        For PO #{{ order.po_number }} • {{ order.warehouse?.name }}
                    </p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Invoice Details -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                    <h2 class="font-bold text-slate-800 dark:text-white mb-4 flex items-center gap-2">
                        <DocumentTextIcon class="w-5 h-5 text-indigo-500" />
                        Invoice Details
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                Invoice Number <span class="text-red-500">*</span>
                            </label>
                            <input 
                                v-model="form.invoice_number"
                                type="text" 
                                required
                                class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none"
                                placeholder="e.g., INV-2026/001"
                            />
                            <p v-if="form.errors.invoice_number" class="text-red-500 text-xs mt-1">{{ form.errors.invoice_number }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                Invoice Date <span class="text-red-500">*</span>
                            </label>
                            <input 
                                v-model="form.invoice_date"
                                type="date" 
                                required
                                class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                Due Date <span class="text-red-500">*</span>
                            </label>
                            <input 
                                v-model="form.due_date"
                                type="date" 
                                required
                                class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none"
                            />
                        </div>
                        
                        <div class="md:col-span-3">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                Notes / Payment Instructions
                            </label>
                            <textarea 
                                v-model="form.notes"
                                rows="2"
                                class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none"
                                placeholder="e.g., Bank details..."
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- Step 1: Select Deliveries -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div class="p-6 border-b border-slate-200 dark:border-slate-700">
                        <h2 class="font-bold text-slate-800 dark:text-white flex items-center gap-2">
                            <CheckCircleIcon class="w-5 h-5 text-indigo-500" />
                            Select Deliveries to Invoice
                        </h2>
                        <p class="text-sm text-slate-500 mt-1">Check the delivery notes you want to include in this invoice.</p>
                    </div>
                    
                    <div class="divide-y divide-slate-200 dark:divide-slate-700">
                        <div 
                            v-for="delivery in deliveries" 
                            :key="delivery.id"
                            @click="toggleDelivery(delivery.id)"
                            class="p-4 flex items-center justify-between cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors"
                            :class="{'bg-indigo-50 dark:bg-indigo-900/20': selectedDeliveryIds.includes(delivery.id)}"
                        >
                            <div class="flex items-center gap-4">
                                <div 
                                    class="w-5 h-5 rounded border flex items-center justify-center transition-colors"
                                    :class="selectedDeliveryIds.includes(delivery.id) ? 'bg-indigo-600 border-indigo-600' : 'border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800'"
                                >
                                    <svg v-if="selectedDeliveryIds.includes(delivery.id)" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5 text-white">
                                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-bold text-slate-900 dark:text-white">{{ delivery.delivery_note_number }}</p>
                                    <p class="text-xs text-slate-500">
                                        {{ formatDate(delivery.receipt_date) }} • GRN: {{ delivery.grn_number }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-slate-900 dark:text-white">Rp {{ Number(delivery.total_value).toLocaleString('id-ID') }}</p>
                                <p class="text-xs text-slate-500">{{ delivery.items_count }} Items</p>
                            </div>
                        </div>
                         <div v-if="deliveries.length === 0" class="p-8 text-center text-slate-500">
                            No uninvoiced deliveries found for this PO.
                        </div>
                    </div>
                </div>

                <!-- Step 2: Billable Items Preview -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden" v-if="selectedDeliveryIds.length > 0">
                    <div class="p-6 border-b border-slate-200 dark:border-slate-700">
                        <h2 class="font-bold text-slate-800 dark:text-white flex items-center gap-2">
                            <BanknotesIcon class="w-5 h-5 text-indigo-500" />
                            Invoice Items Preview
                        </h2>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-600 dark:text-slate-400">
                            <thead class="bg-slate-50 dark:bg-slate-700/50 uppercase text-xs font-bold text-slate-500">
                                <tr>
                                    <th class="px-6 py-4">Product / Delivery Ref</th>
                                    <th class="px-6 py-4 text-center">Qty</th>
                                    <th class="px-6 py-4 text-right">Unit Price</th>
                                    <th class="px-6 py-4 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                <tr v-for="(item, index) in billableItems" :key="index">
                                    <td class="px-6 py-4">
                                        <p class="font-bold text-slate-900 dark:text-white">{{ item.product_name }}</p>
                                        <p class="text-xs text-slate-500">
                                            DN: {{ item.delivery_note }} • {{ formatDate(item.receipt_date) }}
                                        </p>
                                    </td>
                                    <td class="px-6 py-4 text-center font-bold text-slate-900 dark:text-white">{{ Number(item.qty).toLocaleString('id-ID') }}</td>
                                    <td class="px-6 py-4 text-right font-mono">
                                        Rp {{ Number(item.unit_price).toLocaleString('id-ID') }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-bold text-slate-900 dark:text-white">
                                        Rp {{ Number(item.subtotal).toLocaleString('id-ID') }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-slate-50 dark:bg-slate-800/50">
                                <tr>
                                    <td colspan="3" class="px-6 py-3 text-right font-bold text-slate-500">Subtotal</td>
                                    <td class="px-6 py-3 text-right font-bold text-slate-900 dark:text-white">Rp {{ Number(subtotal).toLocaleString('id-ID') }}</td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="px-6 py-3 text-right font-bold text-slate-500">Tax ({{ order.tax_percent }}%)</td>
                                    <td class="px-6 py-3 text-right font-bold text-slate-900 dark:text-white">Rp {{ Number(taxAmount).toLocaleString('id-ID') }}</td>
                                </tr>
                                <tr class="bg-indigo-50 dark:bg-indigo-900/20">
                                    <td colspan="3" class="px-6 py-4 text-right font-bold text-indigo-900 dark:text-indigo-100 uppercase">Grand Total</td>
                                    <td class="px-6 py-4 text-right font-bold text-indigo-600 dark:text-indigo-300 text-lg">Rp {{ Number(totalAmount).toLocaleString('id-ID') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end">
                    <button 
                        type="submit"
                        :disabled="form.processing || selectedDeliveryIds.length === 0"
                        class="px-6 py-3 rounded-xl bg-indigo-600 text-white font-bold hover:bg-indigo-700 transition-colors shadow-lg shadow-indigo-500/30 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <DocumentTextIcon class="w-5 h-5" />
                        Submit Invoice
                    </button>
                </div>
            </form>
        </div>
    </PortalLayout>
</template>
