<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    coa: Object,
});

const printCoa = (id) => {
    window.open(route('qc.coa.print', id), '_blank');
};
</script>

<template>
    <AppLayout title="View COA">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Certificate of Analysis: {{ coa.coa_number }}
                </h2>
                <button @click="printCoa(coa.id)" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                    Print / Download PDF
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-8">
                    
                    <div class="border-b border-gray-200 pb-4 mb-4">
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Certificate of Analysis</h3>
                        <p class="text-gray-500">Issued Date: {{ coa.issued_date }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-8 mb-8">
                        <div>
                            <h4 class="font-bold text-gray-700 dark:text-gray-300">Customer</h4>
                            <p class="text-lg">{{ coa.customer?.name }}</p>
                            <p class="text-gray-500">{{ coa.customer?.address }}</p>
                        </div>
                        <div class="text-right">
                            <h4 class="font-bold text-gray-700 dark:text-gray-300">Reference</h4>
                            <p>Sales Order: {{ coa.sales_order?.so_number }}</p>
                            <p>Customer PO: {{ coa.sales_order?.customer_po_number }}</p>
                            <p>Batch Number: {{ coa.batch_number }}</p>
                        </div>
                    </div>

                    <div class="mt-8">
                        <h4 class="font-bold text-gray-700 dark:text-gray-300 mb-4">Product Details & Test Results</h4>
                        <table class="min-w-full divide-y divide-gray-200 border">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="item in coa.sales_order.items" :key="item.id">
                                    <td class="px-6 py-4">{{ item.product?.name }}</td>
                                    <td class="px-6 py-4">{{ item.product?.code }}</td>
                                    <td class="px-6 py-4">{{ item.qty }} {{ item.unit }}</td>
                                    <td class="px-6 py-4 text-green-600 font-bold">CONFORMS</td>
                                </tr>
                            </tbody>
                        </table>
                        <p class="mt-4 text-sm text-gray-500 italic">
                            * Detailed test results (Chemical/Mechanical attributes) will be populated from QC records linked to the specific batches dispatched.
                        </p>
                    </div>

                    <div class="mt-12 text-center border-t pt-8">
                        <p class="font-bold">Authorized Signature</p>
                        <div class="h-16"></div>
                        <p>Quality Control Manager</p>
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>
