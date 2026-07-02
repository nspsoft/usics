<script setup>
import { Head } from '@inertiajs/vue3';
import QrcodeVue from 'qrcode.vue';

const props = defineProps({
    delivery: Object,
    company: Object, // The buying company (Jidoka)
    supplier: Object, // The vendor
    verification_url: String, // Verification URL
});

const print = () => {
    window.print();
};
</script>

<template>
    <Head title="Print Delivery Note" />
    
    <div class="min-h-screen bg-slate-100 dark:bg-slate-900 print:bg-white p-4 md:p-8 print:p-0 font-sans text-slate-900">
        
        <!-- Print Toolbar -->
        <div class="max-w-[210mm] mx-auto mb-6 flex justify-between items-center print:hidden">
            <button @click="$inertia.visit(route('portal.deliveries.show', delivery.id))" class="px-4 py-2 rounded-lg text-slate-600 hover:bg-slate-200 transition-colors font-bold">
                &larr; Back
            </button>
            <button @click="print" class="px-6 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 font-bold shadow-lg shadow-indigo-500/30 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
                </svg>
                Print Document
            </button>
        </div>

        <!-- Paper Container -->
        <div class="max-w-[210mm] min-h-[297mm] mx-auto bg-white shadow-2xl print:shadow-none p-[15mm] relative overflow-hidden text-sm">
            
            <!-- Watermark (Optional) -->
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 pointer-events-none opacity-[0.03]">
                <img src="/logo.png" class="w-[500px]" alt="" />
            </div>

            <!-- Header: Supplier Branding (DN Issuer) -->
            <header class="flex justify-between items-start border-b-2 border-indigo-900 pb-6 mb-8 block">
                    <!-- Supplier Logo & Name -->
                    <div class="flex items-center gap-4">
                        <img 
                            :src="supplier?.logo ? '/storage/' + supplier.logo : '/images/usics.png'" 
                            class="h-16 w-16 object-contain rounded-lg bg-white border border-slate-200"
                            alt="Supplier Logo"
                            @error="$event.target.src = '/images/usics.png'" 
                        />
                        <div>
                            <h1 class="text-2xl font-black text-indigo-950 uppercase tracking-tighter">{{ supplier?.name || 'Supplier' }}</h1>
                            <p class="text-xs text-slate-500 max-w-[250px] leading-relaxed">
                               {{ supplier?.address || '-' }}
                            </p>
                            <p class="text-xs text-slate-500" v-if="supplier?.phone">Tel: {{ supplier.phone }}</p>
                        </div>
                    </div>
                <div class="text-right">
                    <h2 class="text-3xl font-black text-indigo-900 uppercase tracking-widest leading-none mb-2">Delivery Note</h2>
                    <p class="text-lg font-bold text-slate-900">{{ delivery.delivery_note_number }}</p>
                    <p class="text-sm font-medium text-slate-600">Ref: {{ delivery.purchase_order?.po_number }}</p>
                </div>
            </header>

            <!-- Metadata Cards -->
            <div class="flex gap-8 mb-10">
                <!-- Delivery Info -->
                <div class="flex-1 border-2 border-indigo-900 p-4 rounded-lg bg-white box-border">
                    <p class="text-[10px] uppercase font-black text-indigo-400 mb-3 tracking-wider">Delivery Information</p>
                    <div class="space-y-2">
                        <div class="flex justify-between text-xs">
                            <span class="text-slate-500 font-bold">GRN Number:</span>
                            <span class="font-bold text-slate-900">{{ delivery.grn_number }}</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-slate-500 font-bold">Delivery Date:</span>
                            <span class="font-bold text-slate-900">{{ new Date(delivery.receipt_date).toLocaleDateString('id-ID', { dateStyle: 'long' }) }}</span>
                        </div>
                        <div class="flex justify-between text-xs" v-if="delivery.driver_name">
                            <span class="text-slate-500 font-bold">Driver:</span>
                            <span class="font-bold text-slate-900">{{ delivery.driver_name }}</span>
                        </div>
                    </div>
                </div>

                <!-- QR Code & Info -->
                <div class="w-40 flex flex-col items-center justify-center">
                    <qrcode-vue :value="verification_url || delivery.grn_number" :size="100" level="H" render-as="svg" foreground="#312e81" />
                    <p class="text-xs font-mono mt-2 text-indigo-900 font-bold">SCAN VERIFY</p>
                </div>

                <!-- Ship To (Company/Warehouse) -->
                <div class="flex-1 border-2 border-indigo-900 p-4 rounded-lg bg-slate-50 print:bg-white box-border">
                    <p class="text-[10px] uppercase font-black text-indigo-400 mb-2 tracking-wider">Ship To</p>
                    <p class="font-bold text-base text-slate-900">{{ company?.legal_name || company?.name }}</p>
                    <p class="text-xs text-slate-700 mt-1 font-medium">{{ delivery.warehouse?.name }}</p>
                    <p class="text-xs text-slate-700 mt-1 whitespace-pre-line font-medium">{{ delivery.warehouse?.address }}</p>
                </div>
            </div>

            <!-- Items Table -->
            <div class="mb-8 border-2 border-indigo-900 rounded-lg overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-indigo-900 text-white text-xs uppercase font-bold print:bg-indigo-900 print:text-white">
                        <tr>
                            <th class="py-3 pl-4 w-12 text-indigo-200">No</th>
                            <th class="py-3">Product Description</th>
                            <th class="py-3 text-center">SKU</th>
                            <th class="py-3 text-center w-24">Qty</th>
                            <th class="py-3 text-center w-24">Unit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y-2 divide-slate-100 text-sm">
                        <tr v-for="(item, index) in delivery.items" :key="item.id">
                            <td class="py-3 pl-4 font-bold text-indigo-900/50">{{ index + 1 }}</td>
                            <td class="py-3 pr-4">
                                <p class="font-bold text-slate-900">{{ item.product?.name || item.product_name }}</p>
                                <p class="text-slate-600 text-xs mt-0.5 font-medium" v-if="item.notes">{{ item.notes }}</p>
                            </td>
                            <td class="py-3 text-center font-mono text-slate-700 font-bold text-xs">{{ item.product?.sku || '-' }}</td>
                            <td class="py-3 text-center font-black text-slate-900 text-lg">{{ Number(item.qty_ordered).toLocaleString('id-ID') }}</td>
                            <td class="py-3 text-center font-bold text-indigo-400 text-xs">{{ item.product?.unit?.code || 'Pcs' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Notes -->
            <div class="border-2 border-slate-200 rounded-lg p-4 mb-12 bg-slate-50 print:bg-white" v-if="delivery.notes">
                <p class="text-[10px] uppercase font-black text-slate-400 mb-1">Driver / Delivery Notes</p>
                <p class="text-sm text-slate-900 font-medium italic">{{ delivery.notes }}</p>
            </div>

            <!-- Signatures -->
            <div class="grid grid-cols-3 gap-8 text-center text-xs mt-auto">
                <div>
                    <p class="mb-20 font-bold uppercase text-slate-900 tracking-wider">Prepared By (Supplier)</p>
                    <div class="border-t-2 border-slate-900 w-40 mx-auto pt-2 font-bold text-slate-900 text-sm">
                        {{ supplier?.pic_name || '....................' }}
                    </div>
                </div>
                <div>
                     <p class="mb-20 font-bold uppercase text-slate-900 tracking-wider">Driver / Expeditor</p>
                    <div class="border-t-2 border-slate-900 w-40 mx-auto pt-2 font-bold text-slate-900">
                        {{ delivery.driver_name || '( .......................... )' }}
                        <div v-if="delivery.truck_number" class="text-xs font-medium mt-1">
                            {{ delivery.truck_number }}
                        </div>
                    </div>
                </div>
                <div>
                     <p class="mb-20 font-bold uppercase text-slate-900 tracking-wider">Received By (Gudang)</p>
                    <div class="border-t-2 border-slate-900 w-40 mx-auto pt-2 font-bold text-slate-900">
                        ( .......................... )
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap');
.font-sans {
    font-family: 'Inter', sans-serif;
}
</style>
