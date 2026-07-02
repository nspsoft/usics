<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import QrcodeVue from 'qrcode.vue';
import { 
    ArrowLeftIcon, 
    PrinterIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    invoice: Object,
});

const print = () => {
    window.print(); 
};
</script>

<template>
    <PortalLayout :title="`Invoice #${invoice.invoice_number}`">
        <Head title="Invoice Print View" />
        
        <div class="min-h-screen bg-slate-100 dark:bg-slate-900 print:bg-white p-4 md:p-8 print:p-0 font-sans text-slate-900">
            
            <!-- Print Toolbar -->
            <div class="max-w-[210mm] mx-auto mb-6 flex justify-between items-center print:hidden">
                <Link href="/portal/invoices" class="px-4 py-2 rounded-lg text-slate-600 hover:bg-slate-200 transition-colors font-bold flex items-center gap-2">
                    <ArrowLeftIcon class="w-5 h-5" /> Back
                </Link>
                <div class="flex gap-3">
                     <span class="px-3 py-1.5 rounded-full text-sm font-bold capitalize flex items-center border"
                        :class="{
                            'bg-red-100 text-red-700 border-red-200': invoice.status === 'unpaid',
                            'bg-amber-100 text-amber-700 border-amber-200': invoice.status === 'partial',
                            'bg-emerald-100 text-emerald-700 border-emerald-200': invoice.status === 'paid',
                            'bg-slate-100 text-slate-700 border-slate-200': invoice.status === 'cancelled',
                        }">
                        {{ invoice.status }}
                    </span>
                    <button @click="print" class="px-6 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 font-bold shadow-lg shadow-indigo-500/30 flex items-center gap-2">
                        <PrinterIcon class="w-5 h-5" />
                        Print Invoice
                    </button>
                </div>
            </div>

            <!-- Paper Container -->
            <div class="max-w-[210mm] min-h-[297mm] mx-auto bg-white shadow-2xl print:shadow-none p-[15mm] relative overflow-hidden text-sm">
                
                <!-- Header: Supplier Branding (Invoice Issuer) -->
                <header class="flex justify-between items-start border-b-2 border-indigo-900 pb-6 mb-8 block">
                        <!-- Supplier Logo & Name -->
                        <div class="flex items-center gap-4">
                            <img 
                                :src="invoice.supplier?.logo ? '/storage/' + invoice.supplier.logo : '/images/usics.png'" 
                                class="h-16 w-16 object-contain rounded-lg bg-white border border-slate-200"
                                alt="Supplier Logo"
                                @error="$event.target.src = '/images/usics.png'" 
                            />
                            <div>
                                <h1 class="text-2xl font-black text-indigo-950 uppercase tracking-tighter">{{ invoice.supplier?.name || 'Supplier' }}</h1>
                                <p class="text-xs text-slate-500 max-w-[250px] leading-relaxed">
                                   {{ invoice.supplier?.address || '-' }}
                                </p>
                                <p class="text-xs text-slate-500" v-if="invoice.supplier?.phone">Tel: {{ invoice.supplier.phone }}</p>
                            </div>
                        </div>
                    <div class="text-right">
                        <h2 class="text-3xl font-black text-indigo-900 uppercase tracking-widest leading-none mb-2">INVOICE</h2>
                        <p class="text-lg font-bold text-slate-900">#{{ invoice.invoice_number }}</p>
                        <p class="text-sm font-medium text-slate-600">Ref PO: {{ invoice.purchase_order?.po_number }}</p>
                    </div>
                </header>

                <!-- Metadata Cards -->
                <div class="flex gap-8 mb-10">
                    <!-- Invoice Dates -->
                    <div class="flex-1 border-2 border-indigo-900 p-4 rounded-lg bg-white box-border">
                        <p class="text-[10px] uppercase font-black text-indigo-400 mb-3 tracking-wider">Invoice Information</p>
                        <div class="space-y-2">
                            <div class="flex justify-between text-xs">
                                <span class="text-slate-500 font-bold">Invoice Date:</span>
                                <span class="font-bold text-slate-900">{{ new Date(invoice.invoice_date).toLocaleDateString('id-ID', { dateStyle: 'long' }) }}</span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-slate-500 font-bold">Due Date:</span>
                                <span class="font-bold text-red-600">{{ new Date(invoice.due_date).toLocaleDateString('id-ID', { dateStyle: 'long' }) }}</span>
                            </div>
                            <div class="flex justify-between text-xs pt-2 border-t border-slate-100">
                                <span class="text-slate-500 font-bold">Status:</span>
                                <span class="font-bold uppercase" :class="{
                                    'text-red-600': invoice.status === 'unpaid',
                                    'text-amber-600': invoice.status === 'partial',
                                    'text-emerald-600': invoice.status === 'paid',
                                }">{{ invoice.status }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- QR Code -->
                    <div class="w-40 flex flex-col items-center justify-center">
                        <qrcode-vue :value="invoice.invoice_number" :size="100" level="H" render-as="svg" foreground="#312e81" />
                        <p class="text-xs font-mono mt-2 text-indigo-900 font-bold">SCAN VERIFY</p>
                    </div>

                    <!-- Bill To (Company) -->
                    <div class="flex-1 border-2 border-indigo-900 p-4 rounded-lg bg-slate-50 print:bg-white box-border">
                        <p class="text-[10px] uppercase font-black text-indigo-400 mb-2 tracking-wider">Bill To</p>
                        <p class="font-bold text-base text-slate-900">{{ invoice.company?.legal_name || invoice.company?.name }}</p>
                        <p class="text-xs text-slate-700 mt-1 whitespace-pre-line font-medium">{{ invoice.company?.address || '-' }}</p>
                        <p class="text-xs text-slate-700 mt-1 font-medium" v-if="invoice.company?.phone">Tel: {{ invoice.company.phone }}</p>
                        <p class="text-xs text-slate-700 mt-1 font-medium" v-if="invoice.company?.tax_id">NPWP: {{ invoice.company.tax_id }}</p>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="mb-4 border-2 border-indigo-900 rounded-lg overflow-hidden">
                    <table class="w-full text-left">
                        <thead class="bg-indigo-900 text-white text-xs uppercase font-bold print:bg-indigo-900 print:text-white">
                            <tr>
                                <th class="py-3 pl-4 w-12 text-indigo-200">No</th>
                                <th class="py-3">Description / Item</th>
                                <th class="py-3 text-center w-24">Qty</th>
                                <th class="py-3 text-right w-32">Unit Price</th>
                                <th class="py-3 text-right w-32 pr-4">Line Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y-2 divide-slate-100 text-sm">
                            <tr v-for="(item, index) in invoice.items" :key="item.id">
                                <td class="py-3 pl-4 font-bold text-indigo-900/50">{{ index + 1 }}</td>
                                <td class="py-3 pr-4">
                                    <p class="font-bold text-slate-900">{{ item.description }}</p>
                                </td>
                                <td class="py-3 text-center font-bold text-slate-700">{{ Number(item.qty).toLocaleString('id-ID') }}</td>
                                <td class="py-3 text-right font-mono text-slate-600">Rp {{ Number(item.unit_price).toLocaleString('id-ID') }}</td>
                                <td class="py-3 text-right font-bold text-indigo-900 pr-4">Rp {{ Number(item.subtotal).toLocaleString('id-ID') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Totals -->
                <div class="flex justify-end mb-12">
                     <div class="w-1/2 border-b-2 border-slate-100 pb-4">
                        <div class="flex justify-between py-2 text-sm text-slate-600 font-medium border-b border-dashed border-slate-200">
                            <span>Subtotal</span>
                            <span>Rp {{ Number(invoice.subtotal).toLocaleString('id-ID') }}</span>
                        </div>
                        <div class="flex justify-between py-2 text-sm text-slate-600 font-medium border-b border-dashed border-slate-200">
                            <span>Tax Amount</span>
                            <span>Rp {{ Number(invoice.tax_amount).toLocaleString('id-ID') }}</span>
                        </div>
                        <div class="flex justify-between py-3 text-lg font-black text-indigo-900">
                            <span class="uppercase">Total Amount</span>
                            <span>Rp {{ Number(invoice.total_amount).toLocaleString('id-ID') }}</span>
                        </div>
                     </div>
                </div>

                <!-- Notes -->
                <div class="border-2 border-slate-200 rounded-lg p-4 mb-4 bg-slate-50 print:bg-white" v-if="invoice.notes">
                    <p class="text-[10px] uppercase font-black text-slate-400 mb-1">Payment Instructions / Notes</p>
                    <p class="text-sm text-slate-900 font-medium whitespace-pre-line leading-relaxed">{{ invoice.notes }}</p>
                </div>

                 <!-- Signatures -->
                <div class="grid grid-cols-2 gap-8 text-center text-xs mt-20">
                    <div>
                        <p class="mb-20 font-bold uppercase text-slate-900 tracking-wider">Authorized Signature (Supplier)</p>
                        <div class="border-t-2 border-slate-900 w-48 mx-auto pt-2 font-bold text-slate-900 text-sm">
                             {{ invoice.supplier?.pic_name || '....................' }}
                        </div>
                    </div>
                    <div>
                         <p class="mb-20 font-bold uppercase text-slate-900 tracking-wider">Finance Approval</p>
                        <div class="border-t-2 border-slate-900 w-48 mx-auto pt-2 font-bold text-slate-900">
                            ( .......................... )
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </PortalLayout>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap');
.font-sans {
    font-family: 'Inter', sans-serif;
}
</style>
