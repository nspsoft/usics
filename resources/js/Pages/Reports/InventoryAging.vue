<script setup>
import { ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
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
    categories: Array,
    filters: Object,
    date: String,
});

const selectedCategory = ref(props.filters.category || '');
const selectedStatus = ref(props.filters.status || '');

const applyFilters = debounce(() => {
    router.get('/inventory/reports/inventory-aging', {
        category: selectedCategory.value || undefined,
        status: selectedStatus.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([selectedCategory, selectedStatus], applyFilters);

const getStatusBadge = (status) => {
    switch (status) {
        case 'fast':
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-800/30 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800';
        case 'slow':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-800/30 dark:text-amber-400 border-amber-200 dark:border-amber-800';
        case 'dead':
            return 'bg-rose-100 text-rose-800 dark:bg-rose-800/30 dark:text-rose-400 border-rose-200 dark:border-rose-800';
        default:
            return 'bg-slate-100 text-slate-800 dark:bg-slate-800/30 dark:text-slate-400 border-slate-200 dark:border-slate-800';
    }
};

const getStatusLabel = (status) => {
    switch (status) {
        case 'fast': return 'Fast Moving (< 30 Days)';
        case 'slow': return 'Slow Moving (30-90 Days)';
        case 'dead': return 'Dead Stock (> 90 Days)';
        default: return status.toUpperCase();
    }
};

const exportExcel = () => {
    const params = new URLSearchParams();
    if (selectedCategory.value) params.append('category', selectedCategory.value);
    if (selectedStatus.value) params.append('status', selectedStatus.value);
    
    window.location.href = `/inventory/reports/export/inventory-aging?${params.toString()}`;
};
</script>

<template>
    <Head title="Inventory Aging Report" />
    
    <AppLayout title="Reports">
        <div class="max-w-7xl mx-auto">
            <!-- No Print Elements -->
            <div class="print:hidden mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <Link href="/inventory/dashboard" class="inline-flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white shrink-0">
                    <ArrowLeftIcon class="h-4 w-4" />
                    Back
                </Link>
                <div class="flex flex-wrap items-center gap-4">
                    <select
                        v-model="selectedCategory"
                        class="rounded-xl border-0 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 py-2 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                    >
                        <option value="">All Categories</option>
                        <option v-for="c in props.categories" :key="c" :value="c">
                            {{ c }}
                        </option>
                    </select>
                    <select
                        v-model="selectedStatus"
                        class="rounded-xl border-0 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 py-2 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                    >
                        <option value="">All Classifications</option>
                        <option value="fast">Fast Moving (< 30 Days)</option>
                        <option value="slow">Slow Moving (30 - 90 Days)</option>
                        <option value="dead">Dead Stock (> 90 Days)</option>
                    </select>
                    
                    <button
                        @click="exportExcel"
                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500 transition-colors shadow-lg shadow-emerald-500/20"
                    >
                        <ArrowDownTrayIcon class="h-4 w-4" />
                        Export Excel
                    </button>

                    <button
                        onclick="window.print()"
                        class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 transition-colors shadow-lg shadow-blue-500/20"
                    >
                        <PrinterIcon class="h-4 w-4" />
                        Print
                    </button>
                </div>
            </div>

            <!-- Report Sheet -->
            <div class="bg-white dark:bg-slate-900 text-slate-900 dark:text-white p-8 min-h-[50vh] rounded-2xl shadow-xl print:m-0 print:rounded-none print:shadow-none print:w-full print:p-0">
                
                <!-- Official Print Header -->
                <div class="flex justify-between items-start border-b-4 border-slate-900 dark:border-slate-700 pb-6 mb-6 print:border-b-2 print:pb-4 print:mb-4">
                    <div>
                        <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight mb-1 print:text-xl print:mb-0">INVENTORY AGING REPORT</h1>
                        <p class="text-slate-600 dark:text-slate-400 font-mono text-sm italic print:text-[10px]">Analisis Masa Simpan & Pergerakan Stok Barang</p>
                        <p class="text-slate-900 dark:text-slate-300 font-mono text-xs uppercase tracking-widest mt-2 font-black print:text-[8px] print:mt-1">ERP MANUFACTURING SYSTEM</p>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-black text-slate-900 dark:text-white tracking-tighter print:text-lg">PT. JIDOKA SYSTEM INDONESIA</div>
                        <p class="text-slate-800 dark:text-slate-300 text-xs font-black uppercase tracking-widest mt-1 print:text-[8px]">Department Gudang & Logistik</p>
                        <div class="mt-3 text-right text-xs text-slate-600 dark:text-slate-400 print:text-[9px] print:mt-2">
                            <p class="font-semibold"><span class="inline-block w-16 text-left">Tanggal</span>: {{ date }}</p>
                            <p class="font-semibold"><span class="inline-block w-16 text-left">Kategori</span>: {{ selectedCategory || 'Semua Kategori' }}</p>
                            <p class="font-semibold"><span class="inline-block w-16 text-left">Status</span>: {{ getStatusLabel(selectedStatus || 'semua') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b-2 border-slate-200 dark:border-slate-800">
                                <th class="py-3 text-left font-bold text-slate-500 tracking-wider uppercase text-xs">SKU</th>
                                <th class="py-3 text-left font-bold text-slate-500 tracking-wider uppercase text-xs">Product Name</th>
                                <th class="py-3 text-left font-bold text-slate-500 tracking-wider uppercase text-xs">Category</th>
                                <th class="py-3 text-right font-bold text-slate-500 tracking-wider uppercase text-xs">Stock Qty</th>
                                <th class="py-3 text-center font-bold text-slate-500 tracking-wider uppercase text-xs">Last Out Date</th>
                                <th class="py-3 text-center font-bold text-slate-500 tracking-wider uppercase text-xs">Days Inactive</th>
                                <th class="py-3 text-right font-bold text-slate-500 tracking-wider uppercase text-xs">Classification</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="item in data" :key="item.id" class="break-inside-avoid hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="py-3 font-mono text-xs text-slate-500">{{ item.sku }}</td>
                                <td class="py-3 font-bold text-slate-900 dark:text-white">{{ item.name }}</td>
                                <td class="py-3 text-slate-500 text-xs">{{ item.category }}</td>
                                <td class="py-3 text-right font-mono text-slate-900 dark:text-white">
                                    {{ formatNumber(item.qty) }} <span class="text-[10px] text-slate-400">{{ item.unit }}</span>
                                </td>
                                <td class="py-3 text-center font-mono text-xs text-slate-500">{{ item.last_out_date }}</td>
                                <td class="py-3 text-center">
                                    <span class="font-mono font-bold" :class="item.days_inactive > 90 ? 'text-rose-500' : 'text-slate-900 dark:text-white'">
                                        {{ formatNumber(item.days_inactive) }}
                                    </span>
                                </td>
                                <td class="py-3 text-right">
                                    <span 
                                        class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider border"
                                        :class="getStatusBadge(item.classification)"
                                    >
                                        {{ item.classification }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="data.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-500 italic">No inventory records found for the selected filters.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Footer -->
                <div class="mt-8 pt-4 border-t border-slate-200 dark:border-slate-800 flex justify-between text-xs text-slate-500 dark:text-slate-400 print:mt-12">
                    <p>Printed from ERP System</p>
                    <p>Total Items: {{ data.length }}</p>
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
    .bg-white, .dark\:bg-slate-900 {
        background-color: white !important;
        color: black !important;
        box-shadow: none !important;
    }
    .text-slate-900, .dark\:text-white, .text-slate-500, .dark\:text-slate-400 {
        color: black !important;
    }
    table {
        border-color: #e2e8f0 !important;
    }
    @page { margin: 1cm; }
}
</style>
