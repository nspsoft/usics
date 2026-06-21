<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';
import {
    ArrowLeftIcon,
    PrinterIcon,
    DocumentTextIcon,
    CalendarIcon,
    UserIcon,
    CreditCardIcon,
    CheckCircleIcon,
    BanknotesIcon,
    ArrowPathIcon,
    PencilSquareIcon,
    ShieldCheckIcon,
    TrashIcon,
    XMarkIcon,
    ArrowDownTrayIcon,
} from '@heroicons/vue/24/outline';
import { formatNumber, formatCurrency } from '@/helpers';

const props = defineProps({
    invoice: Object,
    emeteraiConfigured: Boolean,
    emeteraiEnabled: Boolean,
    paymentMethods: Object,
});

const showPaymentModal = ref(false);
const showTaxModal = ref(false);

const taxForm = useForm({
    tax_amount: props.invoice.tax_amount,
});

const paymentForm = useForm({
    amount: Math.max(1, Math.round(props.invoice.balance)),
    payment_date: new Date().toISOString().split('T')[0],
    payment_method: 'Transfer',
    reference: '',
    bank_name: '',
    account_number: '',
    attachment: null,
    notes: '',
});

const formattedAmount = ref('');

const openPaymentModal = () => {
    paymentForm.amount = Math.max(1, Math.round(props.invoice.balance));
    formattedAmount.value = paymentForm.amount.toLocaleString('id-ID');
    paymentForm.payment_date = new Date().toISOString().split('T')[0];
    paymentForm.payment_method = 'Transfer';
    paymentForm.reference = '';
    paymentForm.bank_name = '';
    paymentForm.account_number = '';
    paymentForm.attachment = null;
    paymentForm.notes = '';
    showPaymentModal.value = true;
};

const onAmountInput = (e) => {
    const raw = e.target.value;
    const clean = raw.replace(/\D/g, '');
    formattedAmount.value = clean ? parseInt(clean, 10).toLocaleString('id-ID') : '';
    paymentForm.amount = clean ? parseInt(clean, 10) : 0;
};

const handleFileChange = (e) => {
    paymentForm.attachment = e.target.files[0];
};

const confirmInvoice = () => {
    if (confirm('Are you sure you want to confirm and issue this invoice?')) {
        useForm({}).post(route('sales.invoices.confirm', props.invoice.id));
    }
};

const emeteraiForm = useForm({});
const stampEmeterai = () => {
    if (!props.emeteraiConfigured) {
        alert('e-Meterai API belum dikonfigurasi. Silakan isi Client ID dan Secret Key di menu Settings > AI & Integration.');
        return;
    }
    if (confirm('Bubuhkan e-Meterai pada invoice ini? Biaya Rp 10.000 akan dipotong dari saldo e-Meterai Anda.')) {
        emeteraiForm.post(route('sales.invoices.stamp-emeterai', props.invoice.id));
    }
};

const submitTaxUpdate = () => {
    taxForm.post(route('sales.invoices.update-tax', props.invoice.id), {
        onSuccess: () => {
            showTaxModal.value = false;
        }
    });
};

const submitPayment = () => {
    paymentForm.post(route('sales.invoices.pay', props.invoice.id), {
        preserveScroll: true,
        onSuccess: () => {
            showPaymentModal.value = false;
            paymentForm.reset();
        }
    });
};

const deletePayment = (paymentId) => {
    if (confirm('Apakah Anda yakin ingin menghapus pembayaran ini?')) {
        router.delete(route('sales.invoices.payment.delete', [props.invoice.id, paymentId]), {
            preserveScroll: true,
        });
    }
};

const confirmRevise = () => {
    if (confirm('Are you sure you want to revise this invoice? This will revert the status to Draft and increment the revision number (REV-X).')) {
        router.post(route('sales.invoices.revise', props.invoice.id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                router.reload({ preserveScroll: true });
            },
            onError: () => {
                alert('Failed to revise invoice. Please refresh and try again.');
            }
        });
    }
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', { 
        day: '2-digit', 
        month: 'long', 
        year: 'numeric' 
    });
};

const getStatusBadge = (status) => {
    const badges = {
        draft: 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30',
        issued: 'bg-blue-500/20 text-blue-400 border-blue-500/30',
        partial: 'bg-amber-500/20 text-amber-400 border-amber-500/30',
        paid: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
        cancelled: 'bg-red-500/20 text-red-400 border-red-500/30',
    };
    return badges[status] || 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30';
};
</script>

<template>
    <Head :title="`Invoice ${invoice.invoice_number}`" />
    
    <AppLayout title="Invoice Details">
        <div class="max-w-full px-4 sm:px-6 lg:px-8 mx-auto">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div class="flex items-center gap-4">
                    <Link :href="route('sales.invoices.index')" class="p-2 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white transition-colors">
                        <ArrowLeftIcon class="h-5 w-5" />
                    </Link>
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ invoice.invoice_number }}</h1>
                            <span :class="['px-2.5 py-0.5 rounded-full text-xs font-medium border uppercase', getStatusBadge(invoice.status)]">
                                {{ invoice.status }}
                            </span>
                        </div>
                        <p class="text-slate-500 text-sm mt-1">
                            Ref: 
                            <Link :href="route('sales.orders.show', invoice.sales_order_id)" class="text-blue-400 hover:underline font-medium">
                                {{ invoice.sales_order?.so_number }}
                            </Link>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Link 
                        v-if="invoice.status !== 'draft'"
                        :href="route('sales.returns.create', { invoice_id: invoice.id })"
                        class="flex items-center gap-2 rounded-xl bg-rose-600 px-6 py-2.5 text-sm font-bold text-slate-900 dark:text-white hover:bg-rose-500 transition-colors shadow-lg shadow-rose-500/20"
                    >
                        <ArrowPathIcon class="h-4 w-4" />
                        CREATE RETURN
                    </Link>

                    <button 
                        v-if="invoice.status === 'draft'"
                        @click="confirmInvoice"
                        class="flex items-center gap-2 rounded-xl bg-emerald-600 px-6 py-2.5 text-sm font-bold text-slate-900 dark:text-white hover:bg-emerald-500 transition-colors shadow-lg shadow-emerald-500/20"
                    >
                        <CheckCircleIcon class="h-4 w-4" />
                        CONFIRM INVOICE
                    </button>

                    <button 
                        v-if="invoice.status !== 'draft' && invoice.balance > 0"
                        @click="openPaymentModal"
                        class="flex items-center gap-2 rounded-xl bg-indigo-600 px-6 py-2.5 text-sm font-bold text-slate-900 dark:text-white hover:bg-indigo-500 transition-colors shadow-lg shadow-indigo-500/20"
                    >
                        <BanknotesIcon class="h-4 w-4" />
                        RECORD PAYMENT
                    </button>

                    <a 
                        :href="route('sales.invoices.print', invoice.id)" 
                        target="_blank"
                        class="flex items-center gap-2 rounded-xl bg-slate-700 px-6 py-2.5 text-sm font-bold text-slate-900 dark:text-white hover:bg-slate-600 transition-colors shadow-lg"
                    >
                        <PrinterIcon class="h-4 w-4" />
                        PRINT STANDARD
                    </a>

                    <button 
                        v-if="invoice.status === 'issued'"
                        @click="confirmRevise"
                        class="flex items-center gap-2 rounded-xl bg-amber-600 px-6 py-2.5 text-sm font-bold text-slate-900 dark:text-white hover:bg-amber-500 transition-colors shadow-lg shadow-amber-500/20"
                    >
                        <PencilSquareIcon class="h-4 w-4" />
                        REVISE INVOICE
                    </button>

                    <!-- e-Meterai Stamp Button -->
                    <button 
                        v-if="emeteraiEnabled && invoice.status !== 'draft' && invoice.total >= 5000000 && !invoice.emeterai_serial"
                        @click="stampEmeterai"
                        :disabled="emeteraiForm.processing"
                        class="flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-700 to-indigo-700 px-6 py-2.5 text-sm font-bold text-white hover:from-blue-600 hover:to-indigo-600 transition-all shadow-lg shadow-indigo-500/20 disabled:opacity-50"
                    >
                        <ShieldCheckIcon class="h-4 w-4" />
                        {{ emeteraiForm.processing ? 'PROCESSING...' : 'STAMP e-METERAI' }}
                    </button>

                    <!-- e-Meterai Already Stamped Badge -->
                    <div 
                        v-if="invoice.emeterai_serial"
                        class="flex items-center gap-2 rounded-xl bg-emerald-500/10 border border-emerald-500/30 px-4 py-2.5 text-sm font-bold text-emerald-500"
                    >
                        <ShieldCheckIcon class="h-4 w-4" />
                        <div>
                            <div>✅ e-Meterai Applied</div>
                            <div class="text-[10px] font-mono opacity-70">SN: {{ invoice.emeterai_serial }}</div>
                        </div>
                    </div>

                    <!-- <a 
                        :href="route('sales.invoices.print-v2', invoice.id)" 
                        target="_blank"
                        class="flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-bold text-white dark:text-white hover:bg-blue-500 transition-colors shadow-lg shadow-blue-500/20"
                    >
                        <PrinterIcon class="h-4 w-4" />
                        PRINT PROFESSIONAL (QR)
                    </a> -->
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
                <!-- Left Column: Items -->
                <div class="xl:col-span-8 space-y-6">
                    <div class="rounded-2xl glass-card overflow-hidden shadow-sm">
                        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/20">
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">Invoice Items</h3>
                        </div>
                        <div class="overflow-x-auto relative">
                            <table class="w-full text-left">
                                <thead class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900 shadow-sm">
                                    <tr class="bg-slate-50 dark:bg-slate-800/30">
                                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Product</th>
                                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase text-center">Qty</th>
                                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase text-right">Price</th>
                                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase text-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <tr v-for="item in invoice.items" :key="item.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/10 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-slate-900 dark:text-white">{{ item.product?.name }}</div>
                                            <div class="text-[10px] text-slate-500 uppercase font-mono">{{ item.product?.sku }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-center text-sm text-slate-900 dark:text-white">
                                            {{ formatNumber(item.qty) }} {{ item.unit?.name || 'Unit' }}
                                        </td>
                                        <td class="px-6 py-4 text-right text-sm text-slate-500 dark:text-slate-400 font-mono">
                                            {{ formatCurrency(item.unit_price) }}
                                        </td>
                                        <td class="px-6 py-4 text-right text-sm text-slate-900 dark:text-white font-bold font-mono">
                                            {{ formatCurrency(item.subtotal) }}
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-slate-50 dark:bg-slate-800/20">
                                        <td colspan="3" class="px-6 py-4 text-right text-sm font-medium text-slate-500 dark:text-slate-400">Subtotal</td>
                                        <td class="px-6 py-4 text-right text-sm font-bold text-slate-900 dark:text-white font-mono">{{ formatCurrency(invoice.subtotal) }}</td>
                                    </tr>
                                    <tr class="bg-slate-50 dark:bg-slate-800/20 border-t border-slate-200 dark:border-slate-800">
                                        <td colspan="3" class="px-6 py-4 text-right text-sm font-medium text-slate-500 dark:text-slate-400">
                                            <div class="flex items-center justify-end gap-2">
                                                VAT 11%
                                                <button 
                                                    v-if="invoice.status === 'draft'"
                                                    @click="showTaxModal = true"
                                                    class="p-1 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-400 hover:text-blue-400 transition-colors"
                                                    title="Edit VAT Nominal"
                                                >
                                                    <PencilSquareIcon class="h-4 w-4" />
                                                </button>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-right text-sm font-bold text-slate-900 dark:text-white font-mono">{{ formatCurrency(invoice.tax_amount) }}</td>
                                    </tr>
                                    <tr class="bg-blue-600/10 border-t border-blue-500/30">
                                        <td colspan="3" class="px-6 py-4 text-right text-base font-bold text-blue-400 uppercase tracking-wider">Grand Total</td>
                                        <td class="px-6 py-4 text-right text-lg font-black text-blue-400 font-mono">{{ formatCurrency(invoice.total) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Payment History -->
                    <div v-if="invoice.payments && invoice.payments.length > 0" class="glass-card rounded-2xl shadow-sm overflow-hidden mt-6">
                        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/20">
                             <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">Payment History</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr class="bg-slate-50 dark:bg-slate-800/30 text-xs font-bold text-slate-500 uppercase">
                                        <th class="px-6 py-4">Payment #</th>
                                        <th class="px-6 py-4">Date</th>
                                        <th class="px-6 py-4">Method</th>
                                        <th class="px-6 py-4">Reference</th>
                                        <th class="px-6 py-4 text-right">Amount</th>
                                        <th class="px-6 py-4 text-center">Receipt</th>
                                        <th class="px-6 py-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <tr v-for="payment in invoice.payments" :key="payment.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/10 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="text-slate-900 dark:text-white font-medium font-mono">{{ payment.payment_number }}</div>
                                            <div class="text-[10px] text-slate-500 mt-0.5">by {{ payment.created_by?.name || 'System' }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-slate-600 dark:text-slate-300">{{ formatDate(payment.payment_date) }}</td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center rounded-lg bg-slate-100 dark:bg-slate-800 px-2 py-1 text-xs font-medium text-slate-600 dark:text-slate-300">
                                                {{ payment.method_label || payment.payment_method }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-slate-500 dark:text-slate-400 font-mono text-xs">
                                            {{ payment.reference || '-' }}
                                            <div v-if="payment.bank_name" class="text-[10px] text-slate-500">{{ payment.bank_name }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-right text-emerald-400 font-bold font-mono">{{ formatCurrency(payment.amount) }}</td>
                                        <td class="px-6 py-4 text-center">
                                            <a 
                                                v-if="payment.attachment" 
                                                :href="payment.attachment_url" 
                                                target="_blank"
                                                class="inline-flex items-center gap-1 text-blue-400 hover:text-blue-300 transition-colors"
                                            >
                                                <ArrowDownTrayIcon class="h-4 w-4" />
                                                <span class="text-xs">Download</span>
                                            </a>
                                            <span v-else class="text-slate-600 text-xs">-</span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <button 
                                                @click="deletePayment(payment.id)"
                                                class="p-2 rounded-lg text-red-400 hover:text-red-300 hover:bg-red-500/10 transition-all"
                                                title="Delete Payment"
                                            >
                                                <TrashIcon class="h-4 w-4" />
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Details -->
                <div class="xl:col-span-4 space-y-6">
                    <div class="rounded-2xl glass-card p-6 shadow-sm">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-4">Invoice Info</h3>
                        <div class="space-y-4">
                            <div class="flex items-start gap-3">
                                <UserIcon class="h-5 w-5 text-slate-500 mt-0.5" />
                                <div>
                                    <div class="text-[10px] text-slate-500 font-bold uppercase">Customer</div>
                                    <div class="text-sm text-slate-900 dark:text-white font-medium">{{ invoice.customer?.name }}</div>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <CalendarIcon class="h-5 w-5 text-slate-500 mt-0.5" />
                                <div>
                                    <div class="text-[10px] text-slate-500 font-bold uppercase">Invoice Date</div>
                                    <div class="text-sm text-slate-900 dark:text-white font-medium">{{ formatDate(invoice.invoice_date) }}</div>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <CalendarIcon class="h-5 w-5 text-slate-500 mt-0.5" />
                                <div>
                                    <div class="text-[10px] text-slate-500 font-bold uppercase">Due Date</div>
                                    <div class="text-sm text-orange-400 font-bold">{{ formatDate(invoice.due_date) }}</div>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 border-t border-slate-200 dark:border-slate-800 pt-4">
                                <CreditCardIcon class="h-5 w-5 text-slate-500 mt-0.5" />
                                <div>
                                    <div class="text-[10px] text-slate-500 font-bold uppercase">Payment Status</div>
                                    <div class="text-xs text-slate-600 dark:text-slate-300 mt-1">
                                        Paid: <span class="font-bold text-emerald-400">{{ formatCurrency(invoice.paid_amount) }}</span><br>
                                        Balance: <span class="font-bold text-rose-400">{{ formatCurrency(invoice.balance) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bank Info -->
                    <div class="rounded-2xl glass-card p-6 shadow-sm">
                        <h3 class="text-sm font-bold text-blue-400 uppercase tracking-wider mb-4">Bank Account Info</h3>
                        <div class="space-y-2 text-sm">
                            <div class="text-slate-600 dark:text-slate-300">Bank MANDIRI</div>
                            <div class="text-slate-600 dark:text-slate-300">KK Karawang Galuh Mas</div>
                            <div class="text-slate-900 dark:text-white font-bold font-mono">173-00-0777778-3</div>
                            <div class="text-blue-400 font-medium whitespace-nowrap overflow-hidden text-ellipsis">PT JIDOKA RESULT INDONESIA</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tax Edit Modal -->
        <div v-if="showTaxModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-white dark:bg-slate-950/80 backdrop-blur-sm">
            <div class="w-full max-w-sm rounded-2xl glass-card shadow-2xl overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-800/20">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Adjust VAT Nominal</h3>
                    <button @click="showTaxModal = false" class="text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor font-bold"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <form @submit.prevent="submitTaxUpdate" class="p-6 space-y-4">
                    <p class="text-xs text-slate-500">
                        Adjust VAT amount manually for rounding differences. This will update the Grand Total.
                    </p>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">VAT Amount (IDR)</label>
                        <input 
                            v-model="taxForm.tax_amount" 
                            type="number" 
                            step="1"
                            class="w-full rounded-xl bg-white dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white placeholder-slate-600 focus:ring-blue-500 focus:border-blue-500 font-mono text-lg"
                            required
                        />
                    </div>
                    <div class="pt-4 flex gap-3">
                        <button 
                            type="button" 
                            @click="showTaxModal = false"
                            class="flex-1 px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 text-sm font-bold text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors"
                        >
                            CANCEL
                        </button>
                        <button 
                            type="submit" 
                            :disabled="taxForm.processing"
                            class="flex-1 px-4 py-2.5 rounded-xl bg-blue-600 text-sm font-bold text-white dark:text-white hover:bg-blue-500 transition-colors disabled:opacity-50"
                        >
                            {{ taxForm.processing ? 'SAVING...' : 'UPDATE VAT' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Payment Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showPaymentModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" @click="showPaymentModal = false"></div>
                    
                    <div class="relative w-full max-w-lg bg-white dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden">
                        <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white">Record Payment</h3>
                            </div>
                            <button @click="showPaymentModal = false" class="p-2 rounded-xl text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-all">
                                <XMarkIcon class="h-5 w-5" />
                            </button>
                        </div>

                        <form @submit.prevent="submitPayment" class="p-6 space-y-5">
                            <div class="bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 rounded-xl p-4 border border-slate-200 dark:border-slate-800">
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">Amount Due</span>
                                    <span class="text-lg font-bold text-amber-400 font-mono">{{ formatCurrency(invoice.balance) }}</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Payment Amount (IDR) *</label>
                                    <input 
                                        v-model="formattedAmount" 
                                        @input="onAmountInput"
                                        type="text" 
                                        class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 py-3 px-4 text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50 font-mono text-lg"
                                        required
                                    />
                                    <div v-if="paymentForm.errors.amount" class="text-red-400 text-xs mt-1">{{ paymentForm.errors.amount }}</div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Payment Date *</label>
                                    <input 
                                        v-model="paymentForm.payment_date" 
                                        type="date" 
                                        class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 py-3 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                        required
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Payment Method *</label>
                                <select 
                                    v-model="paymentForm.payment_method" 
                                    class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 py-3 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                    required
                                >
                                    <option v-for="(label, value) in paymentMethods" :key="value" :value="value">{{ label }}</option>
                                </select>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Reference / No. Giro</label>
                                    <input 
                                        v-model="paymentForm.reference" 
                                        type="text" 
                                        placeholder="e.g. TRF-123456"
                                        class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 py-3 px-4 text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Bank Name</label>
                                    <input 
                                        v-model="paymentForm.bank_name" 
                                        type="text" 
                                        placeholder="e.g. BCA, Mandiri"
                                        class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 py-3 px-4 text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Account Number</label>
                                    <input 
                                        v-model="paymentForm.account_number" 
                                        type="text" 
                                        placeholder="e.g. 1234567890"
                                        class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 py-3 px-4 text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Bukti Pembayaran (Attachment)</label>
                                    <input 
                                        type="file" 
                                        @change="handleFileChange"
                                        accept=".jpg,.jpeg,.png,.pdf"
                                        class="w-full rounded-xl border border-dashed border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 py-3 px-4 text-slate-900 dark:text-white file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-500 cursor-pointer"
                                    />
                                    <p class="text-[10px] text-slate-500 mt-1">Max 5MB. Formats: JPG, PNG, PDF</p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Notes</label>
                                <textarea 
                                    v-model="paymentForm.notes" 
                                    rows="2"
                                    placeholder="Optional notes..."
                                    class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 py-3 px-4 text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50 resize-none"
                                ></textarea>
                            </div>

                            <div class="flex gap-3 pt-4">
                                <button 
                                    type="button"
                                    @click="showPaymentModal = false" 
                                    class="flex-1 rounded-xl border border-slate-200 dark:border-slate-800 py-3 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-all"
                                >
                                    Cancel
                                </button>
                                <button 
                                    type="submit"
                                    :disabled="paymentForm.processing"
                                    class="flex-1 rounded-xl bg-blue-600 py-3 text-sm font-semibold text-white dark:text-white hover:bg-blue-500 shadow-lg shadow-blue-500/25 transition-all disabled:opacity-50"
                                >
                                    {{ paymentForm.processing ? 'Processing...' : 'Record Payment' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>



