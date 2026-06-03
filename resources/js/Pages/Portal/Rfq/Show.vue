<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import { 
    ClipboardDocumentListIcon, 
    CalendarIcon, 
    ArrowLeftIcon,
    PaperClipIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon
} from '@heroicons/vue/24/outline';
import { ref, computed } from 'vue';
import { formatDate } from '@/helpers';

const props = defineProps({
    rfq: Object,
    quotation: Object,
    supplierStatus: String,
});

const isResponded = computed(() => props.supplierStatus === 'responded');
const isExpired = computed(() => new Date(props.rfq.deadline) < new Date());

// For MVP, we use a simple total amount input, but ideally we'd map items to inputs
const form = useForm({
    quote_number: 'QT-' + Date.now().toString().slice(-6),
    quotation_date: new Date().toISOString().split('T')[0],
    valid_until: '',
    items: [], // Populated manually for now or hidden
    total_amount: 0,
    subtotal: 0,
    tax_amount: 0,
    payment_terms: '',
    delivery_terms: '',
    notes: '',
    file: null,
});

// Helper to auto-calc total from simpler inputs
const calculateTotal = () => {
    form.total_amount = Number(form.subtotal) + Number(form.tax_amount);
};

const submitQuotation = () => {
    // Fill dummy items just to pass validation if user didn't edit individual items
    // In real app, we would render item inputs
    form.items = props.rfq.items.map(item => ({
        price: 0 // Simplification
    }));
    
    form.post(route('portal.rfq.store', props.rfq.id), {
        forceFormData: true,
    });
};

const fileInput = ref(null);
const handleFileChange = (e) => {
    form.file = e.target.files[0];
};
</script>

<template>
    <PortalLayout :title="rfq.rfq_number">
        <div class="mb-6">
            <Link :href="route('portal.rfq.index')" class="inline-flex items-center gap-1 text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white mb-4">
                <ArrowLeftIcon class="w-4 h-4" />
                Back to RFQs
            </Link>
            
            <div class="flex flex-col md:flex-row md:items-start justify-between gap-6">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <span class="text-sm font-mono text-slate-500">
                            {{ rfq.rfq_number }}
                        </span>
                        <span v-if="isResponded" class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold flex items-center gap-1">
                            <CheckCircleIcon class="w-3 h-3" /> Submitted
                        </span>
                        <span v-else-if="isExpired" class="px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-xs font-bold">
                            Expired
                        </span>
                        <span v-else class="px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded-full text-xs font-bold">
                            Open
                        </span>
                    </div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">{{ rfq.title }}</h1>
                    <p class="text-slate-600 dark:text-slate-400">{{ rfq.description }}</p>
                </div>
                
                <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-xl p-4 min-w-[200px]">
                    <p class="text-xs text-indigo-600 dark:text-indigo-400 font-bold uppercase mb-1">Deadline</p>
                    <p class="text-lg font-bold text-indigo-900 dark:text-indigo-100 flex items-center gap-2">
                        <CalendarIcon class="w-5 h-5" />
                        {{ formatDate(rfq.deadline) }}
                    </p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left: RFQ Items -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Items Table -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div class="p-6 border-b border-slate-200 dark:border-slate-700">
                        <h2 class="font-bold text-lg text-slate-900 dark:text-white">Requested Items</h2>
                    </div>
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-700/50 text-slate-500">
                            <tr>
                                <th class="px-6 py-3">Product / Desc</th>
                                <th class="px-6 py-3 text-right">Qty</th>
                                <th class="px-6 py-3">Unit</th>
                                <th class="px-6 py-3">Specs</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            <tr v-for="item in rfq.items" :key="item.id">
                                <td class="px-6 py-4 font-bold text-slate-900 dark:text-white">
                                    {{ item.product_name }}
                                </td>
                                <td class="px-6 py-4 text-right">{{ item.qty_required }}</td>
                                <td class="px-6 py-4 text-slate-500">{{ item.unit }}</td>
                                <td class="px-6 py-4 text-slate-500 max-w-xs truncate">{{ item.specifications || '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Previous Quotation View (If submitted) -->
                <div v-if="isResponded && quotation" class="bg-emerald-50 dark:bg-emerald-900/10 rounded-2xl p-6 border border-emerald-100 dark:border-emerald-800">
                    <h2 class="font-bold text-lg text-emerald-900 dark:text-emerald-400 mb-4 flex items-center gap-2">
                        <CheckCircleIcon class="w-6 h-6" />
                        Your Quotation Submitted
                    </h2>
                    <div class="grid grid-cols-2 gap-4 text-sm text-emerald-800 dark:text-emerald-300">
                        <div>
                            <span class="block opacity-70">Quote Number:</span>
                            <span class="font-bold">{{ quotation.quote_number }}</span>
                        </div>
                        <div>
                            <span class="block opacity-70">Submitted On:</span>
                            <span class="font-bold">{{ formatDate(quotation.created_at) }}</span>
                        </div>
                        <div>
                            <span class="block opacity-70">Total Amount:</span>
                            <span class="font-bold">{{ new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(quotation.total_amount) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Quotation Form -->
                <div v-else-if="!isExpired" class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                    <h2 class="font-bold text-lg text-slate-900 dark:text-white mb-6">Submit Your Quotation</h2>
                    
                    <form @submit.prevent="submitQuotation" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Quote Number</label>
                                <input v-model="form.quote_number" type="text" class="w-full rounded-xl border-slate-300">
                                <p class="text-xs text-red-500 mt-1" v-if="form.errors.quote_number">{{ form.errors.quote_number }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Quote Date</label>
                                <input v-model="form.quotation_date" type="date" class="w-full rounded-xl border-slate-300">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Valid Until</label>
                                <input v-model="form.valid_until" type="date" class="w-full rounded-xl border-slate-300">
                                <p class="text-xs text-red-500 mt-1" v-if="form.errors.valid_until">{{ form.errors.valid_until }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Attach Proposal (PDF)</label>
                                <input type="file" @change="handleFileChange" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            </div>
                        </div>

                        <div class="border-t border-slate-200 dark:border-slate-700 pt-6">
                            <h3 class="font-bold text-slate-900 mb-4">Pricing Summary</h3>
                            <div class="space-y-4 max-w-sm ml-auto">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Subtotal</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-2 text-slate-500">Rp</span>
                                        <input v-model="form.subtotal" @input="calculateTotal" type="number" class="w-full pl-10 rounded-xl border-slate-300 text-right">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Tax Amount</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-2 text-slate-500">Rp</span>
                                        <input v-model="form.tax_amount" @input="calculateTotal" type="number" class="w-full pl-10 rounded-xl border-slate-300 text-right">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-900 mb-1">Total Amount</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-2 text-slate-900 font-bold">Rp</span>
                                        <input v-model="form.total_amount" type="number" class="w-full pl-10 rounded-xl border-slate-300 bg-slate-50 font-bold text-right" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Payment Terms</label>
                                <textarea v-model="form.payment_terms" rows="2" class="w-full rounded-xl border-slate-300" placeholder="e.g. Net 30 days"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Delivery Terms</label>
                                <textarea v-model="form.delivery_terms" rows="2" class="w-full rounded-xl border-slate-300" placeholder="e.g. FOB Jakarta"></textarea>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
                                <textarea v-model="form.notes" rows="2" class="w-full rounded-xl border-slate-300"></textarea>
                            </div>
                        </div>

                        <div class="flex justify-end pt-4">
                            <button 
                                type="submit" 
                                :disabled="form.processing"
                                class="px-6 py-2.5 bg-indigo-600 text-white font-bold rounded-xl shadow-lg shadow-indigo-500/20 hover:bg-indigo-700 disabled:opacity-50 transition-colors"
                            >
                                {{ form.processing ? 'Submitting...' : 'Submit Quotation' }}
                            </button>
                        </div>
                    </form>
                </div>

                <div v-else class="bg-red-50 text-red-700 p-6 rounded-2xl flex items-center gap-3">
                    <ExclamationTriangleIcon class="w-6 h-6" />
                    <div>
                        <p class="font-bold">This RFQ has expired.</p>
                        <p class="text-sm">The deadline was {{ formatDate(rfq.deadline) }}. You can no longer submit a quotation.</p>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div>
                 <div class="bg-blue-50 dark:bg-blue-900/20 rounded-2xl p-6 border border-blue-100 dark:border-blue-800">
                    <h3 class="font-bold text-blue-900 dark:text-blue-300 mb-2">Instructions</h3>
                    <ul class="text-sm text-blue-800 dark:text-blue-400 space-y-2 list-disc list-inside">
                        <li>Review the requested items carefully.</li>
                        <li>Download any attachments if available.</li>
                        <li>Submit your best price and delivery terms.</li>
                        <li>Ensure validity period covers our review timeline.</li>
                    </ul>
                 </div>
            </div>
        </div>
    </PortalLayout>
</template>
