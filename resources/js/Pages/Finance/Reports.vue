<script setup>
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, watch } from 'vue';
import { 
    BanknotesIcon, 
    ArrowTrendingUpIcon, 
    ArrowTrendingDownIcon, 
    CalculatorIcon,
    CalendarIcon,
    ChevronDownIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    pnl: Object,
    filters: Object
});

const startDate = ref(props.filters?.start_date || '');
const endDate = ref(props.filters?.end_date || '');
const selectedPeriod = ref('custom');

const applyFilters = () => {
    router.get(route('finance.reports'), { 
        start_date: startDate.value,
        end_date: endDate.value
    }, { preserveState: true, replace: true });
};

watch([startDate, endDate], () => {
    applyFilters();
});

const applyPeriod = (period) => {
    selectedPeriod.value = period;
    const now = new Date();
    
    if (period === 'this_month') {
        const start = new Date(now.getFullYear(), now.getMonth(), 1);
        const end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
        startDate.value = formatDateForInput(start);
        endDate.value = formatDateForInput(end);
    } else if (period === 'last_month') {
        const start = new Date(now.getFullYear(), now.getMonth() - 1, 1);
        const end = new Date(now.getFullYear(), now.getMonth(), 0);
        startDate.value = formatDateForInput(start);
        endDate.value = formatDateForInput(end);
    } else if (period === 'this_year') {
        const start = new Date(now.getFullYear(), 0, 1);
        const end = new Date(now.getFullYear(), 11, 31);
        startDate.value = formatDateForInput(start);
        endDate.value = formatDateForInput(end);
    } else if (period === 'all') {
        startDate.value = '';
        endDate.value = '';
    }
};

const formatDateForInput = (date) => {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
};

const formatCurrency = (value) => new Intl.NumberFormat('id-ID', { 
    style: 'currency', currency: 'IDR', maximumFractionDigits: 0 
}).format(value);

</script>

<template>
    <Head title="Profit & Loss" />

    <AppLayout title="Profit & Loss" :render-header="false">
        <div class="min-h-screen bg-white dark:bg-[#050510] relative font-mono text-slate-900 dark:text-cyan-50 pb-20 transition-colors duration-300">
            <!-- Background -->
            <div class="fixed inset-0 z-0 pointer-events-none transition-opacity duration-500">
                <div class="absolute inset-0 bg-gradient-to-b from-emerald-50 via-white to-emerald-50 dark:from-emerald-950/20 dark:to-[#050510]"></div>
                <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-emerald-500/5 dark:bg-cyan-500/5 blur-[120px] rounded-full"></div>
            </div>

            <div class="relative z-10 p-6 space-y-6 max-w-5xl mx-auto">
                 <!-- Header -->
                <div class="text-center mb-12">
                    <span class="px-3 py-1 text-xs bg-emerald-500/10 dark:bg-cyan-500/10 border border-emerald-500/20 dark:border-cyan-500/20 rounded-full text-emerald-600 dark:text-cyan-400 tracking-widest uppercase mb-4 inline-block">Financial Statement</span>
                    <h1 class="text-4xl md:text-5xl font-black text-slate-900 dark:text-white glow-text tracking-tight mb-2 uppercase">
                        PROFIT & LOSS
                    </h1>
                    <p class="text-slate-500 dark:text-slate-400 text-sm tracking-wide mb-8">Statement of Financial Performance</p>

                    <!-- Filters Section -->
                    <div class="flex flex-wrap items-center justify-center gap-3 max-w-2xl mx-auto">
                        <!-- Period Dropdown -->
                        <div class="relative group min-w-[160px]">
                            <select 
                                v-model="selectedPeriod" 
                                @change="applyPeriod(selectedPeriod)"
                                class="block w-full pl-3 pr-8 py-2 border border-slate-250 dark:border-indigo-500/30 rounded-lg bg-slate-100 dark:bg-[#070718] text-slate-900 dark:text-indigo-300 focus:outline-none focus:bg-white dark:focus:bg-[#070718] focus:border-indigo-500/50 dark:focus:border-cyan-500/50 focus:ring-1 focus:ring-indigo-500/50 dark:focus:ring-cyan-500/50 sm:text-sm transition-all shadow-lg shadow-indigo-500/5 dark:shadow-indigo-500/20 appearance-none font-mono cursor-pointer"
                            >
                                <option value="custom">Custom Period</option>
                                <option value="this_month">This Month</option>
                                <option value="last_month">Last Month</option>
                                <option value="this_year">This Year</option>
                                <option value="all">All Time</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none text-slate-400">
                                <ChevronDownIcon class="h-4 w-4" />
                            </div>
                        </div>

                        <!-- Start Date -->
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <CalendarIcon class="h-4 w-4 text-slate-400 dark:text-indigo-400/70" />
                            </div>
                            <input 
                                v-model="startDate"
                                type="date" 
                                class="block w-full pl-10 pr-3 py-2 border border-slate-200 dark:border-indigo-500/30 rounded-lg bg-slate-100 dark:bg-[#070718] text-slate-900 dark:text-indigo-300 focus:outline-none focus:bg-white dark:focus:bg-[#070718] focus:border-indigo-500/50 dark:focus:border-cyan-500/50 focus:ring-1 focus:ring-indigo-500/50 dark:focus:ring-cyan-500/50 sm:text-sm transition-all shadow-lg shadow-indigo-500/5 dark:shadow-indigo-500/20 font-mono"
                            >
                        </div>

                        <!-- End Date -->
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <CalendarIcon class="h-4 w-4 text-slate-400 dark:text-indigo-400/70" />
                            </div>
                            <input 
                                v-model="endDate"
                                type="date" 
                                class="block w-full pl-10 pr-3 py-2 border border-slate-200 dark:border-indigo-500/30 rounded-lg bg-slate-100 dark:bg-[#070718] text-slate-900 dark:text-indigo-300 focus:outline-none focus:bg-white dark:focus:bg-[#070718] focus:border-indigo-500/50 dark:focus:border-cyan-500/50 focus:ring-1 focus:ring-indigo-500/50 dark:focus:ring-cyan-500/50 sm:text-sm transition-all shadow-lg shadow-indigo-500/5 dark:shadow-indigo-500/20 font-mono"
                            >
                        </div>
                    </div>
                </div>

                <!-- Main Statement Card -->
                <div class="bg-white/80 dark:bg-gray-900/60 backdrop-blur-xl border border-slate-200 dark:border-white/10 rounded-2xl overflow-hidden shadow-2xl transition-all">
                    <div class="p-8 space-y-8">
                        
                        <!-- Revenue Section -->
                        <div class="space-y-4">
                            <div class="flex items-center gap-3 border-b border-slate-100 dark:border-white/10 pb-2">
                                <ArrowTrendingUpIcon class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                                <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-widest">REVENUE</h2>
                            </div>
                            <div class="space-y-2 pl-4">
                                <div v-for="item in pnl.revenue" :key="item.code" class="flex justify-between items-center text-sm group">
                                    <span class="text-slate-500 dark:text-slate-400 group-hover:text-emerald-600 dark:group-hover:text-cyan-300 transition-colors">{{ item.name }}</span>
                                    <span class="font-bold text-emerald-600 dark:text-emerald-400 group-hover:text-emerald-500 dark:group-hover:text-emerald-300">{{ formatCurrency(item.total) }}</span>
                                </div>
                            </div>
                            <div class="flex justify-between items-center pt-2 border-t border-dashed border-emerald-200 dark:border-emerald-500/30 pl-4">
                                <span class="font-bold text-emerald-600 dark:text-emerald-500 uppercase text-xs tracking-wider">Total Revenue</span>
                                <span class="text-xl font-black text-emerald-600 dark:text-emerald-400 glow-emerald">{{ formatCurrency(pnl.total_revenue) }}</span>
                            </div>
                        </div>

                        <!-- COGS Section -->
                        <div class="space-y-4">
                             <div class="flex items-center gap-3 border-b border-slate-100 dark:border-white/10 pb-2">
                                <CalculatorIcon class="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
                                <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-widest">COST OF GOODS SOLD</h2>
                            </div>
                            <div class="space-y-2 pl-4">
                                <div v-for="item in pnl.cogs" :key="item.code" class="flex justify-between items-center text-sm group">
                                    <span class="text-slate-500 dark:text-slate-400 group-hover:text-indigo-600 dark:group-hover:text-cyan-300 transition-colors">{{ item.name }}</span>
                                    <span class="font-bold text-slate-600 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white">({{ formatCurrency(item.total) }})</span>
                                </div>
                            </div>
                            <div class="flex justify-between items-center pt-2 border-t border-dashed border-indigo-200 dark:border-indigo-500/30 pl-4">
                                <span class="font-bold text-indigo-600 dark:text-indigo-500 uppercase text-xs tracking-wider">Total COGS</span>
                                <span class="text-xl font-black text-slate-600 dark:text-slate-300">({{ formatCurrency(pnl.total_cogs) }})</span>
                            </div>
                        </div>

                        <!-- Gross Profit -->
                        <div class="bg-indigo-50 dark:bg-indigo-950/30 p-4 rounded-xl border border-indigo-100 dark:border-indigo-500/20 flex justify-between items-center transition-colors">
                            <span class="font-bold text-indigo-700 dark:text-indigo-300 uppercase tracking-widest text-sm">GROSS PROFIT</span>
                             <span class="text-2xl font-black text-slate-900 dark:text-white dark:glow-indigo">{{ formatCurrency(pnl.gross_profit) }}</span>
                        </div>

                        <!-- Expenses Section -->
                         <div class="space-y-4">
                             <div class="flex items-center gap-3 border-b border-slate-100 dark:border-white/10 pb-2">
                                <ArrowTrendingDownIcon class="h-6 w-6 text-rose-600 dark:text-rose-400" />
                                <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-widest">OPERATIONAL EXPENSES</h2>
                            </div>
                            <div class="space-y-2 pl-4">
                                <div v-for="item in pnl.expenses" :key="item.code" class="flex justify-between items-center text-sm group">
                                    <span class="text-slate-500 dark:text-slate-400 group-hover:text-rose-600 dark:group-hover:text-cyan-300 transition-colors">{{ item.name }}</span>
                                    <span class="font-bold text-rose-600 dark:text-rose-400 group-hover:text-rose-500 dark:group-hover:text-rose-300">({{ formatCurrency(item.total) }})</span>
                                </div>
                            </div>
                             <div class="flex justify-between items-center pt-2 border-t border-dashed border-rose-200 dark:border-rose-500/30 pl-4">
                                <span class="font-bold text-rose-600 dark:text-rose-500 uppercase text-xs tracking-wider">Total Expenses</span>
                                <span class="text-xl font-black text-rose-600 dark:text-rose-400 glow-rose">({{ formatCurrency(pnl.total_expenses) }})</span>
                            </div>
                        </div>

                         <!-- Net Profit -->
                        <div class="bg-gradient-to-r from-emerald-50 to-emerald-100/50 dark:from-emerald-900/40 dark:to-cyan-900/40 p-6 rounded-xl border border-emerald-200 dark:border-emerald-500/30 flex justify-between items-center relative overflow-hidden transition-all shadow-lg">
                            <div class="absolute inset-0 bg-emerald-500/[0.03] dark:bg-emerald-500/5 pulse-anim"></div>
                            <div>
                                <h3 class="font-black text-emerald-700 dark:text-emerald-400 uppercase tracking-widest text-lg relative z-10">NET PROFIT / LOSS</h3>
                                <p class="text-[10px] text-emerald-800/60 dark:text-emerald-600 font-bold tracking-[0.2em] relative z-10">BOTTOM LINE</p>
                            </div>
                             <span class="text-4xl font-black text-slate-900 dark:text-white glow-emerald relative z-10">{{ formatCurrency(pnl.net_profit) }}</span>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.glow-text { text-shadow: 0 0 10px rgba(79, 70, 229, 0.1); }
.dark .glow-text { text-shadow: 0 0 20px rgba(34, 211, 238, 0.5); }

.dark .glow-emerald { text-shadow: 0 0 15px rgba(16, 185, 129, 0.5); }
.dark .glow-indigo { text-shadow: 0 0 15px rgba(99, 102, 241, 0.5); }
.dark .glow-rose { text-shadow: 0 0 15px rgba(244, 63, 94, 0.5); }

@keyframes pulse {
    0%, 100% { opacity: 0.1; }
    50% { opacity: 0.3; }
}
.pulse-anim { animation: pulse 3s infinite; }
</style>
