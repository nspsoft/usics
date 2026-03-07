<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    PrinterIcon,
    ArrowLeftIcon,
    ArrowDownTrayIcon,
    ClockIcon,
    ExclamationCircleIcon,
    ChartBarIcon,
    ArchiveBoxIcon,
} from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';
import { formatNumber } from '@/helpers';

const props = defineProps({
    data: Object, // Changed to Object since it's a paginator now
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
        <div class="w-full">
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
            <div class="bg-white dark:bg-slate-900 text-slate-900 dark:text-white p-4 sm:p-6 min-h-[50vh] rounded-2xl shadow-xl print:m-0 print:rounded-none print:shadow-none print:w-full print:p-0 flex flex-col">
                
                <!-- Official Print Header (Quotation Style) -->
                <div class="hidden print:flex justify-between items-start mb-6 pb-4 border-b-2 border-slate-300">
                    <!-- Left: Logo & Company -->
                    <div class="flex gap-4">
                        <img src="/images/jri-official-logo.png" alt="JIDOKA Logo" class="h-16 object-contain">
                        <div>
                            <div class="text-[#E21E26] font-black italic text-4xl leading-none tracking-tighter lowercase">jidoka</div>
                            <div class="text-[#003680] font-black text-[15px] leading-tight mt-0.5">PT. JIDOKA RESULT INDONESIA</div>
                            <div class="text-[11px] text-black mt-2 leading-relaxed">
                                Kawasan Industri JABABEKA I, Jl. Jababeka II Blok C No. 19 L<br>
                                Cikarang Utara, Bekasi 17530 Jawa Barat<br>
                                Telp : +62 21 89383915
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right: Document Title & Info -->
                    <div class="text-right flex flex-col items-end">
                        <div class="text-[#000080] font-black italic text-3xl uppercase tracking-wider mb-4">INVENTORY AGING</div>
                        
                        <table class="text-xs text-black text-left">
                            <tbody>
                                <tr>
                                    <td class="font-bold pr-4 py-0.5 uppercase tracking-wider">Date</td>
                                    <td class="pr-2">:</td>
                                    <td>{{ date }}</td>
                                </tr>
                                <tr>
                                    <td class="font-bold pr-4 py-0.5 uppercase tracking-wider">Category</td>
                                    <td class="pr-2">:</td>
                                    <td>{{ selectedCategory || 'All Categories' }}</td>
                                </tr>
                                <tr>
                                    <td class="font-bold pr-4 py-0.5 uppercase tracking-wider">Status</td>
                                    <td class="pr-2">:</td>
                                    <td class="font-bold text-[#E21E26] uppercase tracking-wider">{{ getStatusLabel(selectedStatus || 'all') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Web Header (Visible on Screen) -->
                <div class="border-b border-slate-200 dark:border-slate-800 pb-6 mb-6 print:hidden">
                    <div class="flex items-start justify-between">
                        <div>
                            <h1 class="text-2xl font-bold uppercase tracking-wide text-slate-900 dark:text-white">Inventory Aging Report</h1>
                            <p class="text-sm text-slate-500 mt-1 dark:text-slate-400">Generated on {{ date }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">ERP Manufacturing</p>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Category: {{ selectedCategory || 'All' }}</p>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Status: {{ getStatusLabel(selectedStatus || 'all') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="overflow-x-auto overflow-y-auto max-h-[600px] border border-slate-200 dark:border-slate-800 rounded-xl rounded-b-none border-b-0 print:border-none print:overflow-visible">
                    <table class="w-full text-sm min-w-max">
                        <thead class="bg-slate-50 dark:bg-slate-800/80 sticky top-0 z-20">
                            <tr class="border-b-2 border-slate-200 dark:border-slate-800">
                                <th class="py-3 px-4 text-left font-bold text-slate-500 tracking-wider uppercase text-xs">SKU</th>
                                <th class="py-3 px-4 text-left font-bold text-slate-500 tracking-wider uppercase text-xs">Product Name</th>
                                <th class="py-3 px-4 text-left font-bold text-slate-500 tracking-wider uppercase text-xs">Category</th>
                                <th class="py-3 px-4 text-right font-bold text-slate-500 tracking-wider uppercase text-xs">Stock Qty</th>
                                <th class="py-3 px-4 text-center font-bold text-slate-500 tracking-wider uppercase text-xs">Last Out Date</th>
                                <th class="py-3 px-4 text-center font-bold text-slate-500 tracking-wider uppercase text-xs">Days Inactive</th>
                                <th class="py-3 px-4 text-right font-bold text-slate-500 tracking-wider uppercase text-xs">Classification</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="item in data.data" :key="item.id" class="break-inside-avoid hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="py-3 px-4 font-mono text-xs text-slate-500">{{ item.sku }}</td>
                                <td class="py-3 px-4 font-bold text-slate-900 dark:text-white">{{ item.name }}</td>
                                <td class="py-3 px-4 text-slate-500 text-xs">{{ item.category }}</td>
                                <td class="py-3 px-4 text-right font-mono text-slate-900 dark:text-white">
                                    {{ formatNumber(item.qty) }} <span class="text-[10px] text-slate-400">{{ item.unit }}</span>
                                </td>
                                <td class="py-3 px-4 text-center font-mono text-xs text-slate-500">{{ item.last_out_date }}</td>
                                <td class="py-3 px-4 text-center">
                                    <span class="font-mono font-bold" :class="item.days_inactive > 90 ? 'text-rose-500' : 'text-slate-900 dark:text-white'">
                                        {{ formatNumber(item.days_inactive) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <span 
                                        class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider border"
                                        :class="getStatusBadge(item.classification)"
                                    >
                                        {{ item.classification }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="data.data.length === 0">
                                <td colspan="7" class="py-12 px-4 text-center text-slate-500 italic">No inventory records found for the selected filters.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div v-if="data.last_page > 1" class="border border-t-0 border-slate-200 dark:border-slate-800 rounded-b-xl px-6 py-4 flex flex-col sm:flex-row items-center justify-between gap-4 print:hidden">
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Showing {{ data.from }} to {{ data.to }} of {{ data.total }}
                    </p>
                    <div class="flex items-center gap-2 overflow-x-auto pb-2 sm:pb-0 scrollbar-hide w-full sm:w-auto">
                        <Link
                            v-for="(link, index) in data.links"
                            :key="index"
                            :href="link.url || '#'"
                            class="px-3 py-1.5 rounded-lg text-sm transition-colors whitespace-nowrap shrink-0"
                            :class="link.active 
                                ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' 
                                : link.url 
                                    ? 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' 
                                    : 'text-slate-400 dark:text-slate-600 cursor-not-allowed opacity-50'"
                            v-html="link.label"
                        />
                    </div>
                </div>

                <!-- Footer -->
                <div class="mt-auto pt-8 flex justify-between text-xs text-slate-500 dark:text-slate-400 print:mt-12 uppercase tracking-wider font-bold">
                    <p>Printed from ERP System</p>
                    <p>Total Items: {{ data.total }}</p>
                </div>
            </div>
        </div>

        <!-- Panduan Inventory Aging -->
        <div class="mt-12 print:hidden w-full">
            <div class="flex items-center gap-2 mb-4 px-1">
                <div class="h-px flex-1 bg-slate-200 dark:bg-slate-800"></div>
                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Panduan Laporan Aging</span>
                <div class="h-px flex-1 bg-slate-200 dark:bg-slate-800"></div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-400">
                            <ChartBarIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-900 dark:text-slate-200 text-sm">Fast Moving</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Produk dengan tingkat perputaran tinggi (di bawah 30 hari). Barang jenis ini laku keras dan perlu dijaga ketersediaannya.
                    </p>
                </div>

                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-amber-500/10 text-amber-500">
                            <ClockIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-900 dark:text-slate-200 text-sm">Slow Moving</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Produk yang sudah lama tidak bergerak (30-90 Hari). Barang ini masih sesekali laku, tetapi memakan ruang kapasitas gudang.
                    </p>
                </div>

                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-rose-500/10 text-rose-500">
                            <ExclamationCircleIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-900 dark:text-slate-200 text-sm">Dead Stock</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Stok mati tanpa ada pergerakan lebih dari 90 Hari. Direkomendasikan evaluasi untuk promo diskon atau cuci gudang (clearance).
                    </p>
                </div>
                
                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-blue-500/10 text-blue-500">
                            <ArchiveBoxIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-900 dark:text-slate-200 text-sm">Manajemen Inventaris</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Laporan ini merupakan parameter bagi Tim untuk menekan Holding Cost (biaya simpan) dari barang yang menumpuk di gudang terlalu lama.
                    </p>
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
