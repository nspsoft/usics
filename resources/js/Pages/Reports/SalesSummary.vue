<script setup>
import { ref, watch, computed } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    PrinterIcon,
    ArrowLeftIcon,
    ArrowDownTrayIcon,
} from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';
import { formatNumber, formatCurrency } from '@/helpers';

const props = defineProps({
    data: Array,
    customers: Array,
    filters: Object,
    date: String,
});

const startDate = ref(props.filters.start_date);
const endDate = ref(props.filters.end_date);
const selectedCustomer = ref(props.filters.customer_id || '');

const applyFilters = debounce(() => {
    router.get('/inventory/reports/sales-summary', {
        start_date: startDate.value,
        end_date: endDate.value,
        customer_id: selectedCustomer.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([startDate, endDate, selectedCustomer], applyFilters);


const totalSales = computed(() => {
    return props.data.reduce((sum, item) => sum + Number(item.total), 0);
});

const totalTax = computed(() => {
    return props.data.reduce((sum, item) => sum + Number(item.tax), 0);
});

const totalGrand = computed(() => {
    return totalSales.value + totalTax.value;
});
</script>

<template>
    <Head title="Sales Summary Report" />
    
    <AppLayout title="Reports">
        <div class="max-w-7xl mx-auto">
            <!-- No Print Elements -->
            <div class="print:hidden mb-6 flex items-center justify-between">
                <Link href="/inventory/reports" class="inline-flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white">
                    <ArrowLeftIcon class="h-4 w-4" />
                    Back to Reports
                </Link>
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 rounded-xl px-4 py-1.5">
                        <input type="date" v-model="startDate" class="bg-transparent border-0 text-sm text-slate-900 dark:text-white focus:ring-0 p-0 w-32" />
                        <span class="text-slate-500">-</span>
                        <input type="date" v-model="endDate" class="bg-transparent border-0 text-sm text-slate-900 dark:text-white focus:ring-0 p-0 w-32" />
                    </div>
                    <select
                        v-model="selectedCustomer"
                        class="rounded-xl border-0 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 py-2 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                    >
                        <option value="">All Customers</option>
                        <option v-for="c in props.customers" :key="c.id" :value="c.id">
                            {{ c.name }}
                        </option>
                    </select>
                    <a
                        :href="`/inventory/reports/export/sales?start_date=${startDate}&end_date=${endDate}&customer_id=${selectedCustomer}`"
                        class="inline-flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 px-4 py-2 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors"
                    >
                        <ArrowDownTrayIcon class="h-4 w-4" />
                        Export Excel
                    </a>
                    <button
                        onclick="window.print()"
                        class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 transition-colors shadow-lg shadow-blue-900/20"
                    >
                        <PrinterIcon class="h-4 w-4" />
                        Print PDF
                    </button>
                </div>
            </div>

            <!-- Report Sheet -->
            <div class="bg-white dark:bg-slate-900 text-slate-900 dark:text-white p-8 min-h-screen rounded-sm shadow-2xl print:shadow-none print:w-full">
                <!-- Header -->
                <div class="border-b-2 border-slate-900 dark:border-slate-800 pb-6 mb-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <h1 class="text-3xl font-black uppercase tracking-tighter text-slate-900 dark:text-white">Sales Summary</h1>
                            <p class="text-sm text-slate-500 mt-1">Period: {{ filters.start_date }} to {{ filters.end_date }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 italic">Generated on {{ date }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xl font-bold text-slate-900 dark:text-white">USICS</p>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Sales Transactions Report</p>
                            <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">Customer: {{ customers.find(c => c.id == selectedCustomer)?.name || 'All Customers' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Stats Summary Row -->
                <div class="grid grid-cols-3 gap-8 mb-8">
                    <div class="border border-slate-200 dark:border-slate-800 p-4 rounded-lg bg-slate-50 dark:bg-slate-800/50">
                        <p class="text-xs uppercase font-bold text-slate-500 mb-1">Total Orders</p>
                        <p class="text-2xl font-black text-slate-900 dark:text-white">{{ data.length }}</p>
                    </div>
                    <div class="border border-slate-200 dark:border-slate-800 p-4 rounded-lg bg-slate-50 dark:bg-slate-800/50">
                        <p class="text-xs uppercase font-bold text-slate-500 mb-1">Subtotal Amount</p>
                        <p class="text-2xl font-black text-slate-900 dark:text-white">{{ formatCurrency(totalSales) }}</p>
                    </div>
                    <div class="border border-slate-200 dark:border-slate-800 p-4 rounded-lg bg-slate-50 dark:bg-slate-800/50">
                        <p class="text-xs uppercase font-bold text-slate-500 mb-1">Grand Total (Inc. Tax)</p>
                        <p class="text-2xl font-black text-blue-700">{{ formatCurrency(totalGrand) }}</p>
                    </div>
                </div>

                <!-- Content Table -->
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b-2 border-slate-200 dark:border-slate-800 text-left">
                            <th class="py-3 px-2 font-bold uppercase text-xs">Date</th>
                            <th class="py-3 px-2 font-bold uppercase text-xs">SO Number</th>
                            <th class="py-3 px-2 font-bold uppercase text-xs">Customer</th>
                            <th class="py-3 px-2 font-bold uppercase text-xs">Status</th>
                            <th class="py-3 px-2 text-right font-bold uppercase text-xs">Amount</th>
                            <th class="py-3 px-2 text-right font-bold uppercase text-xs">Tax</th>
                            <th class="py-3 px-2 text-right font-bold uppercase text-xs">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr v-for="order in data" :key="order.id" class="break-inside-avoid hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                            <td class="py-3 px-2 font-medium">{{ order.order_date }}</td>
                            <td class="py-3 px-2 font-mono text-xs">{{ order.number }}</td>
                            <td class="py-3 px-2">{{ order.customer?.name }}</td>
                            <td class="py-3 px-2">
                                <span class="capitalize px-2 py-0.5 rounded text-[10px] font-bold border" 
                                    :class="{
                                        'bg-blue-50 text-blue-700 border-blue-200': order.status === 'confirmed',
                                        'bg-slate-50 text-slate-700 border-slate-200': order.status === 'draft'
                                    }">
                                    {{ order.status }}
                                </span>
                            </td>
                            <td class="py-3 px-2 text-right">{{ formatCurrency(order.total) }}</td>
                            <td class="py-3 px-2 text-right">{{ formatCurrency(order.tax) }}</td>
                            <td class="py-3 px-2 text-right font-bold">{{ formatCurrency(Number(order.total) + Number(order.tax)) }}</td>
                        </tr>
                        <tr v-if="data.length === 0">
                            <td colspan="7" class="py-12 text-center text-slate-500 dark:text-slate-400 italic">No sales transactions found for the selected period.</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Footer -->
                <div class="mt-12 pt-4 border-t border-slate-200 dark:border-slate-800 flex justify-between text-[10px] text-slate-500 dark:text-slate-400 uppercase font-bold tracking-widest">
                    <p>Internal Document - ERP USICS</p>
                    <p>Page 1 OF 1</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
@media print {
    :deep(nav), :deep(.print\:hidden) {
        display: none !important;
    }
    .bg-white, .dark\:bg-slate-900, .bg-slate-50, .dark\:bg-slate-800\/50 {
        background-color: white !important;
        color: black !important;
        box-shadow: none !important;
    }
    .text-slate-900, .dark\:text-white, .text-slate-500, .dark\:text-slate-400 {
        color: black !important;
    }
}
</style>


