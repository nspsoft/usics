<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed, watch } from 'vue';
import {
    ArrowUpOnSquareIcon,
    CheckCircleIcon,
    ArrowPathIcon,
    ExclamationCircleIcon,
    DocumentTextIcon,
    BanknotesIcon,
    CalendarIcon,
    ArrowDownTrayIcon,
    UserIcon,
    SparklesIcon,
} from '@heroicons/vue/24/outline';
import { formatCurrency, formatDate } from '@/helpers';

const props = defineProps({
    unreconciledSalesTransactions: Array,
    unreconciledPurchaseTransactions: Array,
    reconciledTransactions: Object, // Paginated
    pendingInvoices: Array,
    pendingPurchaseInvoices: Array,
    paymentMethods: Object,
    purchasePaymentMethods: Object,
});

const activeTab = ref('sales'); // 'sales' or 'purchase'
const selectedTransaction = ref(null);
const selectedInvoice = ref(null);
const invoiceSearch = ref('');
const fileInput = ref(null);

const importForm = useForm({
    file: null,
    bank_name: 'BCA',
});

const matchForm = useForm({
    bank_transaction_id: null,
    sales_invoice_id: null,
    purchase_invoice_id: null,
    payment_date: '',
    payment_method: 'Transfer',
    reference: '',
    notes: '',
});

// Reset selection on tab switch
watch(activeTab, (newTab) => {
    selectedTransaction.value = null;
    selectedInvoice.value = null;
    invoiceSearch.value = '';
    matchForm.payment_method = newTab === 'sales' ? 'Transfer' : 'transfer';
});

// Auto-match when transaction is selected
const selectTransaction = (tr) => {
    selectedTransaction.value = tr;
    selectedInvoice.value = null;
    
    // 1. Auto-fill match form defaults
    matchForm.payment_date = tr.transaction_date.split('T')[0];
    matchForm.reference = tr.reference_number || '';
    matchForm.notes = `Rekonsiliasi otomatis mutasi bank ${tr.bank_name} tanggal ${formatDate(tr.transaction_date)}: ${tr.description}`;

    const isSales = activeTab.value === 'sales';
    const pendingList = isSales ? props.pendingInvoices : props.pendingPurchaseInvoices;

    // 2. Try to find an exact amount match
    const amountMatch = pendingList.find((inv) => {
        const balance = isSales ? inv.balance : (inv.total_amount - inv.paid_amount);
        return Math.round(balance) === Math.round(tr.amount);
    });
    
    if (amountMatch) {
        selectedInvoice.value = amountMatch;
        return;
    }

    // 3. Try to scan description for invoice number matches
    for (const inv of pendingList) {
        const invNumOnly = inv.invoice_number.split('/')[0];
        if (invNumOnly && tr.description.toLowerCase().includes(invNumOnly.toLowerCase())) {
            selectedInvoice.value = inv;
            return;
        }
    }
};

const handleFileChange = (e) => {
    importForm.file = e.target.files[0];
};

const triggerFileInput = () => {
    fileInput.value.click();
};

const submitImport = () => {
    if (!importForm.file) return;
    importForm.post(route('finance.reconciliation.import'), {
        preserveScroll: true,
        onSuccess: () => {
            importForm.reset('file');
            if (fileInput.value) fileInput.value.value = '';
        },
    });
};

const submitMatch = () => {
    if (!selectedTransaction.value || !selectedInvoice.value) return;
    
    matchForm.bank_transaction_id = selectedTransaction.value.id;
    const isSales = activeTab.value === 'sales';
    
    if (isSales) {
        matchForm.sales_invoice_id = selectedInvoice.value.id;
        matchForm.purchase_invoice_id = null;
        matchForm.post(route('finance.reconciliation.match'), {
            preserveScroll: true,
            onSuccess: () => {
                selectedTransaction.value = null;
                selectedInvoice.value = null;
                matchForm.reset();
            },
        });
    } else {
        matchForm.purchase_invoice_id = selectedInvoice.value.id;
        matchForm.sales_invoice_id = null;
        matchForm.post(route('finance.reconciliation.match-purchase'), {
            preserveScroll: true,
            onSuccess: () => {
                selectedTransaction.value = null;
                selectedInvoice.value = null;
                matchForm.reset();
            },
        });
    }
};

// Filter pending invoices based on search query
const filteredInvoices = computed(() => {
    const query = invoiceSearch.value.toLowerCase().trim();
    const isSales = activeTab.value === 'sales';
    const pendingList = isSales ? props.pendingInvoices : props.pendingPurchaseInvoices;
    
    if (!query) return pendingList;
    
    return pendingList.filter((inv) => {
        const invoiceNum = inv.invoice_number.toLowerCase();
        const partnerName = isSales 
            ? (inv.customer?.name || '').toLowerCase() 
            : (inv.supplier?.name || '').toLowerCase();
        const totalStr = (isSales ? inv.total : inv.total_amount).toString();
        const balanceStr = (isSales ? inv.balance : (inv.total_amount - inv.paid_amount)).toString();
        
        return (
            invoiceNum.includes(query) ||
            partnerName.includes(query) ||
            totalStr.includes(query) ||
            balanceStr.includes(query)
        );
    });
});

// Helper to determine if an invoice is a recommended match for the selected transaction
const isRecommendedInvoice = (inv) => {
    if (!selectedTransaction.value) return false;
    
    const isSales = activeTab.value === 'sales';
    const balance = isSales ? inv.balance : (inv.total_amount - inv.paid_amount);
    
    // Check if balance matches amount
    const isAmountMatch = Math.round(balance) === Math.round(selectedTransaction.value.amount);
    
    // Check if description includes part of the invoice number
    const invNumOnly = inv.invoice_number.split('/')[0];
    const isDescMatch = invNumOnly && selectedTransaction.value.description.toLowerCase().includes(invNumOnly.toLowerCase());
    
    return isAmountMatch || isDescMatch;
};

// Helper to get match reason label
const getMatchReason = (inv) => {
    if (!selectedTransaction.value) return '';
    
    const isSales = activeTab.value === 'sales';
    const balance = isSales ? inv.balance : (inv.total_amount - inv.paid_amount);
    
    const isAmountMatch = Math.round(balance) === Math.round(selectedTransaction.value.amount);
    const invNumOnly = inv.invoice_number.split('/')[0];
    const isDescMatch = invNumOnly && selectedTransaction.value.description.toLowerCase().includes(invNumOnly.toLowerCase());
    
    if (isAmountMatch && isDescMatch) return 'Nominal & Invoice Cocok';
    if (isAmountMatch) return 'Nominal Cocok';
    if (isDescMatch) return 'Invoice Cocok';
    return 'Rekomendasi';
};
</script>

<template>
    <Head title="Rekonsiliasi Bank" />

    <AppLayout title="Bank Statement Reconciliation">
        <div class="max-w-full px-4 sm:px-6 lg:px-8 mx-auto">
            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Rekonsiliasi Rekening Koran</h1>
                <p class="text-slate-500 text-sm mt-1">Import file mutasi bank (Excel/CSV) dan cocokkan langsung dengan Piutang Pelanggan atau Hutang Supplier.</p>
            </div>

            <!-- Import Panel -->
            <div class="rounded-3xl glass-card p-6 shadow-xl border border-slate-200 dark:border-slate-800 mb-8 bg-slate-50 dark:bg-slate-900/40">
                <h2 class="text-sm font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-4">Upload Mutasi Bank</h2>
                <form @submit.prevent="submitImport" class="flex flex-col md:flex-row items-end gap-4">
                    <div class="w-full md:w-1/4">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Format Bank</label>
                        <select 
                            v-model="importForm.bank_name"
                            class="w-full rounded-xl bg-white dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white py-2.5 px-4 focus:ring-2 focus:ring-blue-500/50 cursor-pointer"
                        >
                            <option value="BCA">BCA (KlikBCA Bisnis)</option>
                            <option value="Mandiri">Mandiri MCM (Excel)</option>
                            <option value="Generic">Generic (Date, Desc, Kredit, Ref)</option>
                        </select>
                    </div>

                    <div class="w-full md:flex-1">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Pilih File (Excel / CSV)</label>
                        <div class="relative flex items-center">
                            <input 
                                type="file" 
                                ref="fileInput" 
                                @change="handleFileChange" 
                                accept=".xlsx,.xls,.csv" 
                                class="hidden" 
                            />
                            <button 
                                type="button" 
                                @click="triggerFileInput"
                                class="w-full md:w-auto flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl border border-dashed border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 hover:bg-slate-50 dark:hover:bg-slate-900 text-slate-600 dark:text-slate-300 font-medium transition-colors"
                            >
                                <ArrowUpOnSquareIcon class="h-5 w-5 text-blue-400" />
                                <span>{{ importForm.file ? importForm.file.name : 'Pilih Berkas Mutasi...' }}</span>
                            </button>
                        </div>
                    </div>

                    <div class="w-full md:w-auto">
                        <button 
                            type="submit" 
                            :disabled="!importForm.file || importForm.processing"
                            class="w-full md:w-auto flex items-center justify-center gap-2 rounded-xl bg-blue-600 hover:bg-blue-500 px-8 py-2.5 text-sm font-bold text-white transition-all shadow-lg shadow-blue-500/25 disabled:opacity-50"
                        >
                            <ArrowPathIcon v-if="importForm.processing" class="h-4 w-4 animate-spin" />
                            <span>{{ importForm.processing ? 'MEMPROSES...' : 'IMPORT MUTASI' }}</span>
                        </button>
                    </div>
                </form>

                <details class="mt-4 border-t border-slate-200 dark:border-slate-800 pt-4 group" open>
                    <summary class="text-xs font-bold text-slate-500 hover:text-slate-850 dark:hover:text-slate-350 uppercase tracking-wider flex items-center gap-1.5 cursor-pointer select-none">
                        <SparklesIcon class="h-4 w-4 text-blue-400 group-open:rotate-90 transition-transform" />
                        <span>Panduan Format & Template File Rekening Koran (Klik untuk Sembunyikan)</span>
                    </summary>
                    <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-6 text-xs leading-relaxed text-slate-600 dark:text-slate-400">
                        <div class="p-4 rounded-2xl bg-white/40 dark:bg-slate-950/40 border border-slate-200/50 dark:border-slate-800/50 flex flex-col justify-between">
                            <div>
                                <h4 class="font-bold text-slate-900 dark:text-white mb-2 flex items-center gap-1.5">
                                    <span class="h-2 w-2 rounded-full bg-blue-500 animate-pulse"></span>
                                    BCA (KlikBCA Bisnis)
                                </h4>
                                <p class="mb-2">Gunakan berkas ekspor mutasi rekening (.csv / .xlsx) bawaan BCA secara langsung tanpa perubahan.</p>
                                <ul class="list-disc pl-4 space-y-1 text-[11px] text-slate-500 dark:text-slate-400 mb-4">
                                    <li>Kolom A: Tanggal (DD/MM)</li>
                                    <li>Kolom B: Keterangan Mutasi</li>
                                    <li>Kolom C: Kode Cabang</li>
                                    <li>Kolom D: Nominal Transfer</li>
                                    <li>Kolom E: DB/CR (CR = Uang Masuk)</li>
                                </ul>
                            </div>
                            <a 
                                :href="route('finance.reconciliation.template', 'BCA')"
                                class="w-full text-center flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-xs font-bold bg-blue-500/10 text-blue-500 hover:bg-blue-600 hover:text-white border border-blue-500/20 hover:border-transparent transition-all shadow-md cursor-pointer"
                            >
                                <ArrowDownTrayIcon class="h-4 w-4" />
                                <span>Unduh Template BCA</span>
                            </a>
                        </div>

                        <div class="p-4 rounded-2xl bg-white/40 dark:bg-slate-950/40 border border-slate-200/50 dark:border-slate-800/50 flex flex-col justify-between">
                            <div>
                                <h4 class="font-bold text-slate-900 dark:text-white mb-2 flex items-center gap-1.5">
                                    <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                    Mandiri MCM
                                </h4>
                                <p class="mb-2">Gunakan langsung hasil ekspor dari Mandiri Cash Management MCM (.xlsx / .xls).</p>
                                <ul class="list-disc pl-4 space-y-1 text-[11px] text-slate-500 dark:text-slate-400 mb-4">
                                    <li>Kolom A: Posting Date</li>
                                    <li>Kolom C: Description</li>
                                    <li>Kolom D: Reference No</li>
                                    <li>Kolom E: Debit (Uang Keluar)</li>
                                    <li>Kolom F: Credit (Uang Masuk)</li>
                                </ul>
                            </div>
                            <a 
                                :href="route('finance.reconciliation.template', 'Mandiri')"
                                class="w-full text-center flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-xs font-bold bg-emerald-500/10 text-emerald-500 hover:bg-emerald-600 hover:text-white border border-emerald-500/20 hover:border-transparent transition-all shadow-md cursor-pointer"
                            >
                                <ArrowDownTrayIcon class="h-4 w-4" />
                                <span>Unduh Template Mandiri</span>
                            </a>
                        </div>

                        <div class="p-4 rounded-2xl bg-white/40 dark:bg-slate-950/40 border border-slate-200/50 dark:border-slate-800/50 flex flex-col justify-between">
                            <div>
                                <h4 class="font-bold text-slate-900 dark:text-white mb-2 flex items-center gap-1.5">
                                    <span class="h-2 w-2 rounded-full bg-purple-500 animate-pulse"></span>
                                    Generic Format (Kustom)
                                </h4>
                                <p class="mb-2">Jika bank lain, buat file Excel/CSV dengan baris pertama berisi header <strong>"Date"</strong> atau <strong>"Tanggal"</strong>.</p>
                                <ul class="list-disc pl-4 space-y-1 text-[11px] text-slate-500 dark:text-slate-400 mb-4">
                                    <li>Kolom A: Tanggal (YYYY-MM-DD)</li>
                                    <li>Kolom B: Deskripsi Transaksi</li>
                                    <li>Kolom C: Nominal Uang Masuk</li>
                                    <li>Kolom D: Nomor Referensi (Opsional)</li>
                                    <li>Kolom E: DB / CR (Opsional, Default CR)</li>
                                </ul>
                            </div>
                            <a 
                                :href="route('finance.reconciliation.template', 'Generic')"
                                class="w-full text-center flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-xs font-bold bg-purple-500/10 text-purple-500 hover:bg-purple-600 hover:text-white border border-purple-500/20 hover:border-transparent transition-all shadow-md cursor-pointer"
                            >
                                <ArrowDownTrayIcon class="h-4 w-4" />
                                <span>Unduh Template Generic</span>
                            </a>
                        </div>
                    </div>
                </details>
            </div>

            <!-- Tabs Navigation -->
            <div class="flex gap-2 mb-6 border-b border-slate-200 dark:border-slate-800">
                <button 
                    @click="activeTab = 'sales'"
                    type="button"
                    class="pb-3 px-4 font-bold text-sm tracking-wide border-b-2 transition-all flex items-center gap-2"
                    :class="activeTab === 'sales' 
                        ? 'border-blue-500 text-blue-600 dark:text-blue-400' 
                        : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                >
                    <BanknotesIcon class="h-4 w-4" />
                    <span>Piutang Pelanggan (Mutasi Masuk)</span>
                    <span class="ml-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-500/10 text-blue-500 border border-blue-500/20">
                        {{ unreconciledSalesTransactions.length }}
                    </span>
                </button>
                <button 
                    @click="activeTab = 'purchase'"
                    type="button"
                    class="pb-3 px-4 font-bold text-sm tracking-wide border-b-2 transition-all flex items-center gap-2"
                    :class="activeTab === 'purchase' 
                        ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400' 
                        : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                >
                    <DocumentTextIcon class="h-4 w-4" />
                    <span>Hutang Supplier (Mutasi Keluar)</span>
                    <span class="ml-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-500 border border-emerald-500/20">
                        {{ unreconciledPurchaseTransactions.length }}
                    </span>
                </button>
            </div>

            <!-- Workspace Layout -->
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">
                
                <!-- Left: Unreconciled Bank Transactions -->
                <div class="xl:col-span-5 rounded-2xl glass-card shadow-lg overflow-hidden border border-slate-200 dark:border-slate-800">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/10 flex justify-between items-center">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            {{ activeTab === 'sales' ? 'Mutasi Masuk (Kredit)' : 'Mutasi Keluar (Debit)' }}
                        </h3>
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold" :class="activeTab === 'sales' ? 'bg-blue-500/10 text-blue-400 border border-blue-500/20' : 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20'">
                            {{ (activeTab === 'sales' ? unreconciledSalesTransactions.length : unreconciledPurchaseTransactions.length) }} Transaksi
                        </span>
                    </div>

                    <div class="divide-y divide-slate-100 dark:divide-slate-800 max-h-[600px] overflow-y-auto">
                        <div v-if="(activeTab === 'sales' ? unreconciledSalesTransactions.length : unreconciledPurchaseTransactions.length) === 0" class="p-8 text-center text-slate-500 italic">
                            {{ activeTab === 'sales' ? 'Tidak ada data mutasi masuk. Silakan import mutasi bank terlebih dahulu.' : 'Tidak ada data mutasi keluar. Silakan import mutasi bank terlebih dahulu.' }}
                        </div>

                        <div 
                            v-for="tr in (activeTab === 'sales' ? unreconciledSalesTransactions : unreconciledPurchaseTransactions)" 
                            :key="tr.id"
                            @click="selectTransaction(tr)"
                            class="p-4 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-all cursor-pointer flex justify-between gap-4"
                            :class="selectedTransaction?.id === tr.id ? (activeTab === 'sales' ? 'bg-blue-500/10 dark:bg-blue-500/10 border-l-4 border-blue-500' : 'bg-purple-500/10 dark:bg-purple-500/10 border-l-4 border-purple-500') : ''"
                        >
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-slate-400 font-mono">{{ formatDate(tr.transaction_date) }}</span>
                                    <span class="px-1.5 py-0.2 rounded text-[10px] font-bold uppercase border bg-slate-100 dark:bg-slate-800 text-slate-500 border-slate-200 dark:border-slate-700">
                                        {{ tr.bank_name }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-600 dark:text-slate-300 mt-1 line-clamp-2 leading-relaxed">{{ tr.description }}</p>
                                <span v-if="tr.reference_number" class="text-[9px] font-mono text-slate-500 block mt-1">Ref: {{ tr.reference_number }}</span>
                            </div>
                            <div class="text-right flex flex-col justify-between items-end">
                                <span class="text-sm font-bold font-mono" :class="activeTab === 'sales' ? 'text-emerald-400' : 'text-red-400'">
                                    {{ activeTab === 'sales' ? '+' : '-' }}{{ formatCurrency(tr.amount) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Selection & Matching Console -->
                <div class="xl:col-span-7 space-y-6">
                    <div class="rounded-2xl glass-card shadow-lg border border-slate-200 dark:border-slate-800 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/10">
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">Rekonsiliasi Console</h3>
                        </div>

                        <!-- Not Selected State -->
                        <div v-if="!selectedTransaction" class="p-8 text-center text-slate-500 flex flex-col items-center justify-center min-h-[300px]">
                            <ExclamationCircleIcon class="h-10 w-10 text-slate-400 mb-2" />
                            <p class="text-sm font-medium">Pilih salah satu mutasi {{ activeTab === 'sales' ? 'masuk' : 'keluar' }} di kolom kiri untuk memulai pencocokan.</p>
                        </div>

                        <!-- Selected State Console -->
                        <div v-else class="p-6 space-y-6">
                            
                            <!-- Chosen Mutasi Summary -->
                            <div class="rounded-xl border p-4 flex justify-between gap-4" :class="activeTab === 'sales' ? 'border-blue-500/30 bg-blue-500/5' : 'border-purple-500/30 bg-purple-500/5'">
                                <div>
                                    <span class="text-[10px] font-bold uppercase tracking-widest" :class="activeTab === 'sales' ? 'text-blue-400' : 'text-purple-400'">Mutasi Terpilih</span>
                                    <p class="text-xs text-slate-900 dark:text-white font-medium mt-1 leading-relaxed">{{ selectedTransaction.description }}</p>
                                    <div class="flex items-center gap-3 mt-2 text-[10px] text-slate-500">
                                        <span>Tanggal: <strong class="font-bold">{{ formatDate(selectedTransaction.transaction_date) }}</strong></span>
                                        <span>Bank: <strong class="font-bold">{{ selectedTransaction.bank_name }}</strong></span>
                                    </div>
                                </div>
                                <div class="text-right flex flex-col justify-center">
                                    <span class="text-xs text-slate-400 font-bold uppercase">Jumlah Mutasi</span>
                                    <span class="text-lg font-black font-mono" :class="activeTab === 'sales' ? 'text-emerald-400' : 'text-red-400'">
                                        {{ activeTab === 'sales' ? '+' : '-' }}{{ formatCurrency(selectedTransaction.amount) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Invoice Search and Selector -->
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">
                                        Pilih {{ activeTab === 'sales' ? 'Sales' : 'Purchase' }} Invoice {{ activeTab === 'sales' ? 'Pembayar' : 'Terbayar' }} *
                                    </label>
                                    <input 
                                        v-model="invoiceSearch" 
                                        type="text" 
                                        :placeholder="`Cari No Invoice / ${activeTab === 'sales' ? 'Pelanggan' : 'Supplier'}...`" 
                                        class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 py-1.5 px-3 text-xs w-64 text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-1 focus:ring-blue-500"
                                    />
                                </div>

                                <div class="border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden max-h-[220px] overflow-y-auto">
                                    <table class="w-full text-left text-xs">
                                        <thead class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900 shadow-sm border-b border-slate-200 dark:border-slate-800">
                                            <tr class="bg-slate-50 dark:bg-slate-800/40 text-slate-500 font-bold uppercase">
                                                <th class="px-4 py-2.5">Invoice #</th>
                                                <th class="px-4 py-2.5">{{ activeTab === 'sales' ? 'Pelanggan' : 'Supplier' }}</th>
                                                <th class="px-4 py-2.5 text-right">{{ activeTab === 'sales' ? 'Balance' : 'Amount Due' }}</th>
                                                <th class="px-4 py-2.5 text-center">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                            <tr v-if="filteredInvoices.length === 0" class="text-center text-slate-500 italic">
                                                <td colspan="4" class="py-6">Tidak ada invoice pending yang cocok.</td>
                                            </tr>
                                            <tr 
                                                v-for="inv in filteredInvoices" 
                                                :key="inv.id"
                                                @click="selectedInvoice = inv"
                                                class="hover:bg-slate-50 dark:hover:bg-slate-800/50 cursor-pointer transition-all"
                                                :class="{ 
                                                    'bg-blue-500/10 dark:bg-blue-500/10': selectedInvoice?.id === inv.id && activeTab === 'sales',
                                                    'bg-emerald-500/10 dark:bg-emerald-500/10': selectedInvoice?.id === inv.id && activeTab === 'purchase',
                                                    'bg-emerald-500/5 dark:bg-emerald-500/5': isRecommendedInvoice(inv) && selectedInvoice?.id !== inv.id 
                                                }"
                                            >
                                                <td class="px-4 py-3 font-medium">
                                                    <div class="flex items-center gap-2">
                                                        <span class="font-mono text-slate-900 dark:text-white">{{ inv.invoice_number }}</span>
                                                        <span 
                                                            v-if="isRecommendedInvoice(inv)"
                                                            class="flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 uppercase"
                                                        >
                                                            <SparklesIcon class="h-3 w-3 animate-pulse text-emerald-400" />
                                                            {{ getMatchReason(inv) }}
                                                        </span>
                                                    </div>
                                                    <span class="text-[9px] text-slate-500 font-mono block mt-0.5">
                                                        PO: {{ activeTab === 'sales' ? (inv.sales_order?.customer_po_number || '-') : (inv.purchase_order?.po_number || '-') }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                                                    {{ activeTab === 'sales' ? (inv.customer?.name || '-') : (inv.supplier?.name || '-') }}
                                                </td>
                                                <td class="px-4 py-3 text-right font-bold font-mono text-slate-900 dark:text-white">
                                                    {{ formatCurrency(activeTab === 'sales' ? inv.balance : (inv.total_amount - inv.paid_amount)) }}
                                                </td>
                                                <td class="px-4 py-3 text-center uppercase text-[10px] font-bold">
                                                    <span :class="inv.status === 'partial' ? 'text-amber-400' : (activeTab === 'sales' ? 'text-blue-400' : 'text-emerald-400')">
                                                        {{ inv.status }}
                                                    </span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Match Details Form -->
                            <div v-if="selectedInvoice" class="grid grid-cols-1 sm:grid-cols-3 gap-4 border-t border-slate-200 dark:border-slate-800 pt-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Tanggal Pembayaran *</label>
                                    <input 
                                        v-model="matchForm.payment_date"
                                        type="date"
                                        class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 py-2.5 px-4 text-xs text-slate-900 dark:text-white focus:ring-1 focus:ring-blue-500"
                                        required
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Metode Pembayaran *</label>
                                    <select 
                                        v-model="matchForm.payment_method"
                                        class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 py-2.5 px-4 text-xs text-slate-900 dark:text-white focus:ring-1 focus:ring-blue-500 cursor-pointer"
                                        required
                                    >
                                        <option v-for="(label, value) in (activeTab === 'sales' ? paymentMethods : purchasePaymentMethods)" :key="value" :value="value">{{ label }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">No. Referensi / Ref *</label>
                                    <input 
                                        v-model="matchForm.reference"
                                        type="text"
                                        placeholder="TRF-..."
                                        class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 py-2.5 px-4 text-xs text-slate-900 dark:text-white focus:ring-1 focus:ring-blue-500"
                                        required
                                    />
                                </div>
                            </div>

                            <!-- Match Form Submissions -->
                            <div v-if="selectedInvoice" class="flex gap-4 border-t border-slate-200 dark:border-slate-800 pt-4">
                                <button 
                                    type="button" 
                                    @click="selectedInvoice = null"
                                    class="flex-1 py-3 text-sm font-semibold rounded-xl border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
                                >
                                    Batal Cocokkan
                                </button>
                                <button 
                                    type="button" 
                                    @click="submitMatch"
                                    :disabled="matchForm.processing"
                                    class="flex-1 py-3 text-sm font-bold rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white transition-all shadow-lg shadow-emerald-500/25 flex items-center justify-center gap-2"
                                >
                                    <CheckCircleIcon v-if="!matchForm.processing" class="h-5 w-5" />
                                    <ArrowPathIcon v-else class="h-4 w-4 animate-spin" />
                                    <span>{{ matchForm.processing ? 'MEMPROSES...' : 'COCOKKAN & BAYAR' }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- History of Reconciled Transactions -->
            <div class="rounded-2xl glass-card shadow-lg overflow-hidden border border-slate-200 dark:border-slate-800 mt-8">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/10 flex justify-between items-center">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">Riwayat Rekonsiliasi</h3>
                </div>

                <div class="overflow-x-auto relative">
                    <table class="w-full text-left text-sm">
                        <thead class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900 shadow-sm border-b border-slate-200 dark:border-slate-800">
                            <tr class="bg-slate-50 dark:bg-slate-800/30 text-slate-500 font-bold uppercase text-xs">
                                <th class="px-6 py-4">Tanggal Mutasi</th>
                                <th class="px-6 py-4">Deskripsi Mutasi</th>
                                <th class="px-6 py-4 text-right">Nominal</th>
                                <th class="px-6 py-4">Matched Invoice</th>
                                <th class="px-6 py-4">Pelanggan / Supplier</th>
                                <th class="px-6 py-4">Reconciled At</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-if="reconciledTransactions.data.length === 0" class="text-center text-slate-500 italic">
                                <td colspan="6" class="py-8">Belum ada mutasi yang direkonsiliasi.</td>
                            </tr>
                            <tr v-for="tr in reconciledTransactions.data" :key="tr.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="px-6 py-4 font-mono text-xs text-slate-500">{{ formatDate(tr.transaction_date) }}</td>
                                <td class="px-6 py-4 text-xs">
                                    <div class="text-slate-900 dark:text-white font-medium max-w-md truncate" :title="tr.description">{{ tr.description }}</div>
                                    <span class="inline-flex items-center rounded-md bg-slate-100 dark:bg-slate-800 px-2 py-0.5 text-[10px] font-medium text-slate-600 dark:text-slate-400 mt-1">
                                        {{ tr.bank_name }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right font-bold font-mono" :class="tr.type === 'DB' ? 'text-red-400' : 'text-emerald-400'">
                                    {{ tr.type === 'DB' ? '-' : '+' }}{{ formatCurrency(tr.amount) }}
                                </td>
                                <td class="px-6 py-4 font-mono text-xs">
                                    <template v-if="tr.sales_payment">
                                        <span class="text-blue-400 font-semibold">Sales: {{ tr.sales_payment?.sales_invoice?.invoice_number || 'INV-Deleted' }}</span>
                                        <div class="text-[10px] text-slate-500 mt-0.5">Payment Ref: {{ tr.sales_payment.payment_number }}</div>
                                    </template>
                                    <template v-else-if="tr.purchase_payment">
                                        <span class="text-emerald-400 font-semibold">Purchase: {{ tr.purchase_payment?.invoice?.invoice_number || 'PINV-Deleted' }}</span>
                                        <div class="text-[10px] text-slate-500 mt-0.5">Payment Ref: {{ tr.purchase_payment.payment_number }}</div>
                                    </template>
                                    <template v-else>
                                        <span class="text-slate-450">-</span>
                                    </template>
                                </td>
                                <td class="px-6 py-4 text-slate-600 dark:text-slate-300 text-xs">
                                    <span v-if="tr.sales_payment">{{ tr.sales_payment?.sales_invoice?.customer?.name || '-' }}</span>
                                    <span v-else-if="tr.purchase_payment">{{ tr.purchase_payment?.invoice?.supplier?.name || '-' }}</span>
                                    <span v-else>-</span>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-500">
                                    {{ tr.reconciled_at ? formatDate(tr.reconciled_at) : '-' }}
                                    <div class="text-[10px] text-slate-500 mt-0.5">by {{ tr.createdBy?.name || tr.created_by?.name || 'System' }}</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="reconciledTransactions.links.length > 3" class="px-6 py-4 bg-slate-50 dark:bg-slate-900/50 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center">
                    <div class="text-xs text-slate-500">
                        Showing {{ reconciledTransactions.from }} to {{ reconciledTransactions.to }} of {{ reconciledTransactions.total }} records
                    </div>
                    <div class="flex gap-1">
                        <template v-for="(link, key) in reconciledTransactions.links" :key="key">
                            <span 
                                v-if="link.url === null" 
                                class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-800 text-xs text-slate-400 cursor-not-allowed" 
                                v-html="link.label"
                            ></span>
                            <Link 
                                v-else 
                                :href="link.url" 
                                class="px-3 py-1.5 rounded-lg border text-xs transition-all"
                                :class="link.active 
                                    ? 'bg-blue-600 border-blue-600 text-white font-bold' 
                                    : 'border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800'"
                                v-html="link.label"
                                preserve-scroll
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
