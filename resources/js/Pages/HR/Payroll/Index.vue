<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    CurrencyDollarIcon,
    MagnifyingGlassIcon,
    ChevronRightIcon,
    DocumentChartBarIcon,
    ArrowPathIcon,
    BanknotesIcon,
    CpuChipIcon,
    CheckBadgeIcon,
    CalendarIcon,
    Cog6ToothIcon
} from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';
import { formatNumber, formatCurrency } from '@/helpers';

const props = defineProps({
    payrolls: Object,
    filters: Object,
});

const search = ref(props.filters.search);
const month = ref(props.filters.month);
const year = ref(props.filters.year);

const months = [
    { value: 1, name: 'January' }, { value: 2, name: 'February' }, { value: 3, name: 'March' },
    { value: 4, name: 'April' }, { value: 5, name: 'May' }, { value: 6, name: 'June' },
    { value: 7, name: 'July' }, { value: 8, name: 'August' }, { value: 9, name: 'September' },
    { value: 10, name: 'October' }, { value: 11, name: 'November' }, { value: 12, name: 'December' },
];

const years = Array.from({ length: 5 }, (_, i) => new Date().getFullYear() - i);

watch([search, month, year], debounce(() => {
    router.get(route('hr.payroll.index'), { 
        search: search.value, 
        month: month.value, 
        year: year.value 
    }, { preserveState: true, replace: true });
}, 300));

const genForm = useForm({
    month: month.value,
    year: year.value,
});

const generatePayroll = () => {
    if (confirm(`Generate Payroll for ${months.find(m => m.value == month.value).name} ${year.value}?`)) {
        genForm.month = month.value;
        genForm.year = year.value;
        genForm.post(route('hr.payroll.generate'));
    }
};


const getStatusStyle = (status) => {
    const styles = {
        draft: 'bg-slate-500/10 text-slate-500 dark:text-slate-400 border-slate-500/20',
        confirmed: 'bg-blue-500/10 text-blue-400 border-blue-500/20',
        paid: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
        cancelled: 'bg-red-500/10 text-red-400 border-red-500/20',
    };
    return styles[status] || 'bg-slate-500/10 text-slate-500 dark:text-slate-400 border-slate-500/20';
};
</script>

<template>
    <Head title="Payroll Management" />
    
    <AppLayout title="HR: Payroll Management">
        <div class="max-w-full px-4 sm:px-6 lg:px-8 mx-auto">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Payroll Administration</h2>
                    <p class="text-sm text-slate-500 mt-1 uppercase tracking-widest font-bold font-mono">Compensation & Benefits</p>
                </div>
                
                <div class="flex items-center gap-3">
                    <Link 
                        :href="route('hr.payroll.settings')"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 px-5 py-3.5 text-sm font-bold text-slate-700 dark:text-slate-300 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-800 transition-all hover:-translate-y-0.5"
                    >
                        <Cog6ToothIcon class="h-5 w-5 text-slate-500" />
                        Settings
                    </Link>
                    <button 
                        @click="generatePayroll"
                        :disabled="genForm.processing"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-6 py-3.5 text-sm font-bold text-white shadow-xl shadow-indigo-900/20 hover:bg-indigo-500 transition-all hover:-translate-y-0.5"
                    >
                        <ArrowPathIcon class="h-5 w-5" :class="{'animate-spin': genForm.processing}" />
                        Generate Monthly Payroll
                    </button>
                </div>
            </div>

            <!-- Dashboard / Filters -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
                <div class="lg:col-span-3 bg-white dark:bg-slate-950/50 p-6 rounded-[2.5rem] border border-slate-200 dark:border-slate-800 shadow-lg flex flex-wrap items-center gap-6">
                    <div class="flex items-center gap-4">
                        <CalendarIcon class="h-5 w-5 text-indigo-400" />
                        <select v-model="month" class="bg-white dark:bg-slate-950 border-0 rounded-xl py-2.5 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50 w-32">
                            <option v-for="m in months" :key="m.value" :value="m.value">{{ m.name }}</option>
                        </select>
                        <select v-model="year" class="bg-white dark:bg-slate-950 border-0 rounded-xl py-2.5 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50 w-24">
                            <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                        </select>
                    </div>

                    <div class="flex-1 min-w-[200px] relative">
                        <MagnifyingGlassIcon class="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-500" />
                        <input v-model="search" type="text" placeholder="Search employee..." class="w-full bg-white dark:bg-slate-950 border-0 rounded-xl py-2.5 pl-10 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50" />
                    </div>
                </div>

                <div class="bg-indigo-600 border border-indigo-500 rounded-[2.5rem] p-6 shadow-xl shadow-indigo-900/20 relative overflow-hidden group">
                    <div class="absolute top-0 right-0 p-4 opacity-10">
                        <BanknotesIcon class="h-16 w-16 text-slate-900 dark:text-white" />
                    </div>
                    <div class="text-[10px] font-bold text-indigo-200 uppercase tracking-widest mb-1">Total Net Disbursement</div>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white font-mono">--</div>
                </div>
            </div>

            <!-- Payroll List -->
            <div class="glass-card rounded-[2.5rem] overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-white dark:bg-slate-950/30">
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2.5 text-[10px] font-bold text-slate-600 uppercase tracking-widest">Employee</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2.5 text-[10px] font-bold text-slate-600 uppercase tracking-widest text-right">Basic Salary</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2.5 text-[10px] font-bold text-slate-600 uppercase tracking-widest text-right">Allowances</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2.5 text-[10px] font-bold text-slate-600 uppercase tracking-widest text-right">Net Pay</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2.5 text-[10px] font-bold text-slate-600 uppercase tracking-widest text-center">Status</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2.5 text-[10px] font-bold text-slate-600 uppercase tracking-widest text-right"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr v-for="payroll in payrolls.data" :key="payroll.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors group">
                            <td class="px-4 py-2">
                                <Link :href="route('hr.payroll.show', payroll.id)" class="flex items-center gap-2.5 group/link">
                                    <div class="w-8 h-8 rounded-lg bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-[10px] font-bold text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                        {{ payroll.employee.full_name.charAt(0) }}
                                    </div>
                                    <div>
                                        <div class="text-xs font-bold text-slate-900 dark:text-white group-hover/link:text-indigo-400 transition-colors">{{ payroll.employee.full_name }}</div>
                                        <div class="text-[10px] text-slate-500">{{ payroll.employee.position?.name }}</div>
                                    </div>
                                </Link>
                            </td>
                            <td class="px-4 py-2 text-right font-mono text-xs text-slate-600 dark:text-slate-300">
                                {{ formatCurrency(payroll.basic_salary) }}
                            </td>
                            <td class="px-4 py-2 text-right font-mono text-xs text-emerald-400">
                                + {{ formatCurrency(payroll.total_allowances) }}
                            </td>
                            <td class="px-4 py-2 text-right font-mono text-xs font-bold text-slate-900 dark:text-white">
                                {{ formatCurrency(payroll.net_salary) }}
                            </td>
                            <td class="px-4 py-2 text-center">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider border" :class="getStatusStyle(payroll.status)">
                                    {{ payroll.status }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-right">
                                <Link :href="route('hr.payroll.show', payroll.id)" class="p-1.5 text-slate-500 hover:text-slate-900 dark:text-white transition-colors inline-block">
                                    <ChevronRightIcon class="h-4 w-4" />
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="!payrolls.data.length">
                            <td colspan="6" class="px-4 py-10 text-center">
                                <div class="text-slate-500 text-xs italic">No payroll records found for this period. Click "Generate" to start.</div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="px-8 py-6 bg-white dark:bg-slate-950/20 border-t border-slate-200 dark:border-slate-800 flex justify-center">
                    <nav class="flex gap-1">
                        <Link
                            v-for="(link, i) in payrolls.links"
                            :key="i"
                            :href="link.url || '#'"
                            class="px-4 py-2 rounded-xl text-sm font-bold transition-all"
                            :class="[
                                link.active ? 'bg-indigo-600 text-slate-900 dark:text-white shadow-lg shadow-indigo-900/20' : 'text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 hover:text-slate-900 dark:text-white',
                                !link.url ? 'opacity-50 cursor-not-allowed' : ''
                            ]"
                            v-html="link.label"
                        />
                    </nav>
                </div>
            </div>
        </div>
    </AppLayout>
</template>



