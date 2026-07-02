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
import { formatNumber } from '@/helpers';

const props = defineProps({
    data: Array,
    products: Array,
    filters: Object,
    date: String,
});

const startDate = ref(props.filters.start_date);
const endDate = ref(props.filters.end_date);
const selectedProduct = ref(props.filters.product_id || '');

const applyFilters = debounce(() => {
    router.get('/inventory/reports/production-summary', {
        start_date: startDate.value,
        end_date: endDate.value,
        product_id: selectedProduct.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([startDate, endDate, selectedProduct], applyFilters);


const totalPlanned = computed(() => {
    return props.data.reduce((sum, item) => sum + Number(item.planned_qty), 0);
});

const totalActual = computed(() => {
    return props.data.reduce((sum, item) => sum + Number(item.actual_qty || 0), 0);
});

const completionRate = computed(() => {
    if (totalPlanned.value === 0) return 0;
    return ((totalActual.value / totalPlanned.value) * 100).toFixed(1);
});

const statusCounts = computed(() => {
    return {
        draft: props.data.filter(wo => wo.status === 'draft').length,
        confirmed: props.data.filter(wo => wo.status === 'confirmed').length,
        in_progress: props.data.filter(wo => wo.status === 'in_progress').length,
        completed: props.data.filter(wo => wo.status === 'completed').length,
        cancelled: props.data.filter(wo => wo.status === 'cancelled').length,
    };
});
</script>

<template>
    <Head title="Production Summary Report" />
    
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
                        v-model="selectedProduct"
                        class="rounded-xl border-0 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 py-2 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                    >
                        <option value="">All Products</option>
                        <option v-for="p in props.products" :key="p.id" :value="p.id">
                            {{ p.name }}
                        </option>
                    </select>
                    <a
                        :href="`/inventory/reports/export/production?start_date=${startDate}&end_date=${endDate}&product_id=${selectedProduct}`"
                        class="inline-flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 px-4 py-2 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors"
                    >
                        <ArrowDownTrayIcon class="h-4 w-4" />
                        Export Excel
                    </a>
                    <button
                        onclick="window.print()"
                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-slate-900 dark:text-white hover:bg-emerald-500 transition-colors shadow-lg shadow-emerald-900/20"
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
                            <h1 class="text-3xl font-black uppercase tracking-tighter text-emerald-800 dark:text-emerald-400">Production Summary</h1>
                            <p class="text-sm text-slate-500 mt-1">Planned Start: {{ filters.start_date }} to {{ filters.end_date }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 italic">Generated on {{ date }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xl font-bold text-slate-900 dark:text-white">USICS</p>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Manufacturing Report</p>
                            <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">Product: {{ products.find(p => p.id == selectedProduct)?.name || 'All Products' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Stats Summary -->
                <div class="grid grid-cols-4 gap-6 mb-8">
                    <div class="border border-slate-200 dark:border-slate-800 p-4 rounded-lg bg-slate-50 dark:bg-slate-800/50">
                        <p class="text-xs uppercase font-bold text-slate-500 dark:text-slate-400 mb-1">Total Work Orders</p>
                        <p class="text-2xl font-black text-slate-900 dark:text-white">{{ data.length }}</p>
                    </div>
                    <div class="border border-slate-200 dark:border-slate-800 p-4 rounded-lg bg-slate-50 dark:bg-slate-800/50">
                        <p class="text-xs uppercase font-bold text-slate-500 dark:text-slate-400 mb-1">Planned Qty</p>
                        <p class="text-2xl font-black text-slate-900 dark:text-white">{{ formatNumber(totalPlanned) }}</p>
                    </div>
                    <div class="border border-slate-200 dark:border-slate-800 p-4 rounded-lg bg-slate-50 dark:bg-slate-800/50">
                        <p class="text-xs uppercase font-bold text-slate-500 dark:text-slate-400 mb-1">Actual Output</p>
                        <p class="text-2xl font-black text-slate-900 dark:text-white">{{ formatNumber(totalActual) }}</p>
                    </div>
                    <div class="border border-emerald-200 dark:border-emerald-800/50 p-4 rounded-lg bg-emerald-50 dark:bg-emerald-900/20">
                        <p class="text-xs uppercase font-bold text-emerald-600 dark:text-emerald-400 mb-1">Completion Rate</p>
                        <p class="text-2xl font-black text-emerald-700 dark:text-emerald-400">{{ completionRate }}%</p>
                    </div>
                </div>

                <!-- Status Breakdown -->
                <div class="mb-6 p-4 bg-slate-100 dark:bg-slate-800 rounded-lg flex gap-6 text-sm text-slate-900 dark:text-white">
                    <div><span class="font-bold text-slate-500 dark:text-slate-400">Draft:</span> {{ statusCounts.draft }}</div>
                    <div><span class="font-bold text-slate-500 dark:text-slate-400">Confirmed:</span> {{ statusCounts.confirmed }}</div>
                    <div><span class="font-bold text-amber-700 dark:text-amber-400">In Progress:</span> {{ statusCounts.in_progress }}</div>
                    <div><span class="font-bold text-emerald-700 dark:text-emerald-400">Completed:</span> {{ statusCounts.completed }}</div>
                    <div><span class="font-bold text-red-700 dark:text-red-400">Cancelled:</span> {{ statusCounts.cancelled }}</div>
                </div>

                <!-- Content Table -->
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b-2 border-slate-200 dark:border-slate-800 text-left">
                            <th class="py-3 px-2 font-bold uppercase text-xs">WO Number</th>
                            <th class="py-3 px-2 font-bold uppercase text-xs">Product</th>
                            <th class="py-3 px-2 font-bold uppercase text-xs">Planned Start</th>
                            <th class="py-3 px-2 font-bold uppercase text-xs">Status</th>
                            <th class="py-3 px-2 text-right font-bold uppercase text-xs">Planned Qty</th>
                            <th class="py-3 px-2 text-right font-bold uppercase text-xs">Actual Qty</th>
                            <th class="py-3 px-2 text-right font-bold uppercase text-xs">Yield %</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr v-for="wo in data" :key="wo.id" class="break-inside-avoid hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                            <td class="py-3 px-2 font-mono text-xs">{{ wo.number }}</td>
                            <td class="py-3 px-2 font-medium">{{ wo.product?.name }}</td>
                            <td class="py-3 px-2">{{ wo.planned_start }}</td>
                            <td class="py-3 px-2">
                                <span class="capitalize px-2 py-0.5 rounded text-[10px] font-bold border" 
                                    :class="{
                                        'bg-emerald-50 text-emerald-700 border-emerald-200': wo.status === 'completed',
                                        'bg-amber-50 text-amber-700 border-amber-200': wo.status === 'in_progress',
                                        'bg-blue-50 text-blue-700 border-blue-200': wo.status === 'confirmed',
                                        'bg-slate-50 text-slate-700 border-slate-200': wo.status === 'draft',
                                        'bg-red-50 text-red-700 border-red-200': wo.status === 'cancelled'
                                    }">
                                    {{ wo.status?.replace('_', ' ') }}
                                </span>
                            </td>
                            <td class="py-3 px-2 text-right">{{ formatNumber(wo.planned_qty) }}</td>
                            <td class="py-3 px-2 text-right font-bold">{{ formatNumber(wo.actual_qty || 0) }}</td>
                            <td class="py-3 px-2 text-right">
                                <span :class="wo.planned_qty > 0 && (wo.actual_qty / wo.planned_qty) >= 1 ? 'text-emerald-600' : 'text-slate-500'">
                                    {{ wo.planned_qty > 0 ? ((wo.actual_qty || 0) / wo.planned_qty * 100).toFixed(0) : 0 }}%
                                </span>
                            </td>
                        </tr>
                        <tr v-if="data.length === 0">
                            <td colspan="7" class="py-12 text-center text-slate-500 dark:text-slate-400 italic">No work orders found for the selected period.</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Footer -->
                <div class="mt-12 pt-4 border-t border-slate-200 dark:border-slate-800 flex justify-between text-[10px] text-slate-500 dark:text-slate-400 uppercase font-bold tracking-widest">
                    <p>Manufacturing Report - ERP USICS</p>
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
    .bg-white, .dark\:bg-slate-900, .bg-slate-50, .dark\:bg-slate-800\/50, .bg-slate-100, .dark\:bg-slate-800, .bg-emerald-50, .dark\:bg-emerald-900\/20 {
        background-color: white !important;
        color: black !important;
        box-shadow: none !important;
    }
    .text-slate-900, .dark\:text-white, .text-slate-500, .dark\:text-slate-400, .text-emerald-800, .dark\:text-emerald-400 {
        color: black !important;
    }
}
</style>


