<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    InboxArrowDownIcon,
    PaperAirplaneIcon
} from '@heroicons/vue/24/outline';
import { computed, reactive } from 'vue';
import { formatDate } from '@/helpers';

const props = defineProps({
    filters: Object,
    ar: Object,
    ap: Object
});

const formatCurrency = (value) => new Intl.NumberFormat('id-ID', { 
    style: 'currency', currency: 'IDR', maximumFractionDigits: 0 
}).format(value);

const form = reactive({
    as_of: props.filters?.as_of ?? new Date().toISOString().slice(0, 10),
    start_date: props.filters?.start_date ?? '',
    end_date: props.filters?.end_date ?? '',
    search: props.filters?.search ?? '',
});

const queryParams = computed(() => {
    const params = {
        as_of: form.as_of,
    };
    if (form.start_date && form.end_date) {
        params.start_date = form.start_date;
        params.end_date = form.end_date;
    }
    if (form.search) params.search = form.search;
    return params;
});

const applyFilters = () => {
    router.get('/finance/payment-monitoring', queryParams.value, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="AP & AR Monitoring" />

    <AppLayout title="AP & AR Monitoring" :render-header="false">
        <div class="min-h-screen bg-slate-50 dark:bg-[#050510] relative font-mono text-slate-800 dark:text-cyan-50 transition-colors duration-300">
             <!-- Background -->
            <div class="fixed inset-0 z-0 pointer-events-none">
                <div class="absolute inset-x-0 top-0 h-[400px] bg-gradient-to-b from-cyan-100/10 to-transparent dark:from-cyan-900/10"></div>
            </div>

            <div class="relative z-10 p-6 space-y-6">
                 <!-- Header -->
                <div class="text-center mb-8">
                     <h1 class="text-3xl font-black text-slate-800 dark:text-white glow-text tracking-widest uppercase transition-colors">
                        DEBT & RECEIVABLES
                    </h1>
                </div>

                <div class="hud-panel p-4 border border-slate-200 dark:border-white/10 bg-white/80 dark:bg-white/5 rounded-2xl transition-colors">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
                        <div>
                            <div class="text-[10px] uppercase tracking-[0.2em] text-slate-500 mb-1">As Of Date</div>
                            <input v-model="form.as_of" type="date" class="w-full bg-slate-100 dark:bg-[#0a0a20] border border-slate-200 dark:border-white/10 rounded-xl px-3 py-2 text-xs text-slate-800 dark:text-white outline-none focus:ring-1 focus:ring-cyan-500/50" />
                        </div>
                        <div>
                            <div class="text-[10px] uppercase tracking-[0.2em] text-slate-500 mb-1">Start Date</div>
                            <input v-model="form.start_date" type="date" class="w-full bg-slate-100 dark:bg-[#0a0a20] border border-slate-200 dark:border-white/10 rounded-xl px-3 py-2 text-xs text-slate-800 dark:text-white outline-none focus:ring-1 focus:ring-cyan-500/50" />
                        </div>
                        <div>
                            <div class="text-[10px] uppercase tracking-[0.2em] text-slate-500 mb-1">End Date</div>
                            <input v-model="form.end_date" type="date" class="w-full bg-slate-100 dark:bg-[#0a0a20] border border-slate-200 dark:border-white/10 rounded-xl px-3 py-2 text-xs text-slate-800 dark:text-white outline-none focus:ring-1 focus:ring-cyan-500/50" />
                        </div>
                        <div>
                            <div class="text-[10px] uppercase tracking-[0.2em] text-slate-500 mb-1">Search</div>
                            <input v-model="form.search" type="text" placeholder="Reference / Description" class="w-full bg-slate-100 dark:bg-[#0a0a20] border border-slate-200 dark:border-white/10 rounded-xl px-3 py-2 text-xs text-slate-800 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-600 outline-none focus:ring-1 focus:ring-cyan-500/50" />
                        </div>
                        <div class="flex justify-end md:justify-start">
                            <button @click="applyFilters" class="w-full md:w-auto px-5 py-2 bg-cyan-600 hover:bg-cyan-500 text-white dark:bg-cyan-500/20 dark:text-cyan-300 border dark:border-cyan-500/30 rounded-xl text-xs font-bold tracking-widest uppercase dark:hover:bg-cyan-500/30 transition cursor-pointer border-none shadow-sm">
                                Apply
                            </button>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    
                    <!-- Receivables (AR) -->
                    <div class="space-y-4">
                        <div class="p-6 rounded-2xl bg-gradient-to-br from-cyan-50 to-white dark:from-cyan-950/50 dark:to-[#050510] border border-cyan-200 dark:border-cyan-500/30 relative overflow-hidden group shadow-sm transition-all duration-300">
                             <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                                <InboxArrowDownIcon class="h-24 w-24 text-cyan-400" />
                            </div>
                            <div class="relative z-10">
                                <span class="text-xs font-bold bg-cyan-100 text-cyan-700 dark:bg-cyan-500/10 dark:text-cyan-400 px-2 py-1 rounded border border-cyan-200 dark:border-cyan-500/20 tracking-wider">ACCOUNTS RECEIVABLE</span>
                                <h2 class="text-4xl font-black text-slate-800 dark:text-white glow-text mt-4 mb-2 transition-colors">{{ formatCurrency(ar.balance) }}</h2>
                                <p class="text-sm text-slate-500 dark:text-slate-400 transition-colors">Total Outstanding from Customers</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                            <div class="p-3 bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl shadow-sm transition-colors">
                                <div class="text-[10px] text-slate-500 uppercase tracking-[0.2em] mb-1">0-30</div>
                                <div class="text-sm font-black text-cyan-600 dark:text-cyan-300 transition-colors">{{ formatCurrency(ar.aging?.['0-30'] ?? 0) }}</div>
                            </div>
                            <div class="p-3 bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl shadow-sm transition-colors">
                                <div class="text-[10px] text-slate-500 uppercase tracking-[0.2em] mb-1">31-60</div>
                                <div class="text-sm font-black text-cyan-600 dark:text-cyan-300 transition-colors">{{ formatCurrency(ar.aging?.['31-60'] ?? 0) }}</div>
                            </div>
                            <div class="p-3 bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl shadow-sm transition-colors">
                                <div class="text-[10px] text-slate-500 uppercase tracking-[0.2em] mb-1">61-90</div>
                                <div class="text-sm font-black text-cyan-600 dark:text-cyan-300 transition-colors">{{ formatCurrency(ar.aging?.['61-90'] ?? 0) }}</div>
                            </div>
                            <div class="p-3 bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl shadow-sm transition-colors">
                                <div class="text-[10px] text-slate-500 uppercase tracking-[0.2em] mb-1">&gt; 90</div>
                                <div class="text-sm font-black text-cyan-600 dark:text-cyan-300 transition-colors">{{ formatCurrency(ar.aging?.['>90'] ?? 0) }}</div>
                            </div>
                            <div class="p-3 bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl shadow-sm transition-colors">
                                <div class="text-[10px] text-slate-500 uppercase tracking-[0.2em] mb-1">Total</div>
                                <div class="text-sm font-black text-slate-800 dark:text-white transition-colors">{{ formatCurrency(ar.aging?.total ?? ar.balance ?? 0) }}</div>
                            </div>
                        </div>

                        <!-- AR List -->
                        <div class="bg-white dark:bg-gray-900/50 border border-slate-200 dark:border-white/10 rounded-xl overflow-hidden shadow-sm transition-colors">
                            <div class="px-4 py-3 bg-slate-50 dark:bg-white/5 border-b border-slate-200 dark:border-white/5">
                                <h3 class="text-xs font-bold text-cyan-600 dark:text-cyan-400 uppercase tracking-widest">Open Items (Outstanding)</h3>
                            </div>
                            <div class="divide-y divide-slate-100 dark:divide-white/5">
                                <div v-if="(ar.open_items?.length ?? 0) === 0" class="p-4 text-xs text-slate-500">Tidak ada outstanding AR untuk filter ini.</div>
                                <div v-for="tx in ar.open_items" :key="tx.journal_id" class="p-4 flex justify-between items-center hover:bg-slate-50 dark:hover:bg-white/5 transition-colors gap-4">
                                    <div class="flex flex-col min-w-0">
                                        <Link :href="tx.ledger_url" class="font-bold text-slate-800 dark:text-white text-sm truncate hover:text-cyan-650 dark:hover:text-cyan-300 transition">{{ tx.reference }}</Link>
                                        <span class="text-xs text-slate-500 truncate">{{ formatDate(tx.date) }} • {{ tx.bucket }} • {{ tx.description }}</span>
                                    </div>
                                    <span class="font-bold text-cyan-600 dark:text-cyan-400 whitespace-nowrap">{{ formatCurrency(tx.balance) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payables (AP) -->
                    <div class="space-y-4">
                         <div class="p-6 rounded-2xl bg-gradient-to-br from-rose-50 to-white dark:from-rose-905/50 dark:to-[#050510] border border-rose-200 dark:border-rose-500/30 relative overflow-hidden group shadow-sm transition-all duration-300">
                             <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                                <PaperAirplaneIcon class="h-24 w-24 text-rose-400" />
                            </div>
                            <div class="relative z-10">
                                <span class="text-xs font-bold bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400 px-2 py-1 rounded border border-rose-200 dark:border-rose-500/20 tracking-wider">ACCOUNTS PAYABLE</span>
                                <h2 class="text-4xl font-black text-slate-800 dark:text-white glow-rose mt-4 mb-2 transition-colors">{{ formatCurrency(ap.balance) }}</h2>
                                <p class="text-sm text-slate-500 dark:text-slate-400 transition-colors">Total Outstanding to Suppliers</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                            <div class="p-3 bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl shadow-sm transition-colors">
                                <div class="text-[10px] text-slate-500 uppercase tracking-[0.2em] mb-1">0-30</div>
                                <div class="text-sm font-black text-rose-600 dark:text-rose-300 transition-colors">{{ formatCurrency(ap.aging?.['0-30'] ?? 0) }}</div>
                            </div>
                            <div class="p-3 bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl shadow-sm transition-colors">
                                <div class="text-[10px] text-slate-500 uppercase tracking-[0.2em] mb-1">31-60</div>
                                <div class="text-sm font-black text-rose-600 dark:text-rose-300 transition-colors">{{ formatCurrency(ap.aging?.['31-60'] ?? 0) }}</div>
                            </div>
                            <div class="p-3 bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl shadow-sm transition-colors">
                                <div class="text-[10px] text-slate-500 uppercase tracking-[0.2em] mb-1">61-90</div>
                                <div class="text-sm font-black text-rose-600 dark:text-rose-300 transition-colors">{{ formatCurrency(ap.aging?.['61-90'] ?? 0) }}</div>
                            </div>
                            <div class="p-3 bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl shadow-sm transition-colors">
                                <div class="text-[10px] text-slate-500 uppercase tracking-[0.2em] mb-1">&gt; 90</div>
                                <div class="text-sm font-black text-rose-600 dark:text-rose-300 transition-colors">{{ formatCurrency(ap.aging?.['>90'] ?? 0) }}</div>
                            </div>
                            <div class="p-3 bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl shadow-sm transition-colors">
                                <div class="text-[10px] text-slate-500 uppercase tracking-[0.2em] mb-1">Total</div>
                                <div class="text-sm font-black text-slate-800 dark:text-white transition-colors">{{ formatCurrency(ap.aging?.total ?? ap.balance ?? 0) }}</div>
                            </div>
                        </div>

                         <!-- AP List -->
                        <div class="bg-white dark:bg-gray-900/50 border border-slate-200 dark:border-white/10 rounded-xl overflow-hidden shadow-sm transition-colors">
                            <div class="px-4 py-3 bg-slate-50 dark:bg-white/5 border-b border-slate-200 dark:border-white/5">
                                <h3 class="text-xs font-bold text-rose-600 dark:text-rose-400 uppercase tracking-widest">Open Items (Outstanding)</h3>
                            </div>
                             <div class="divide-y divide-slate-100 dark:divide-white/5">
                                <div v-if="(ap.open_items?.length ?? 0) === 0" class="p-4 text-xs text-slate-500">Tidak ada outstanding AP untuk filter ini.</div>
                                <div v-for="tx in ap.open_items" :key="tx.journal_id" class="p-4 flex justify-between items-center hover:bg-slate-50 dark:hover:bg-white/5 transition-colors gap-4">
                                    <div class="flex flex-col min-w-0">
                                        <Link :href="tx.ledger_url" class="font-bold text-slate-800 dark:text-white text-sm truncate hover:text-rose-650 dark:hover:text-rose-300 transition">{{ tx.reference }}</Link>
                                        <span class="text-xs text-slate-500 truncate">{{ formatDate(tx.date) }} • {{ tx.bucket }} • {{ tx.description }}</span>
                                    </div>
                                    <span class="font-bold text-rose-600 dark:text-rose-400 whitespace-nowrap">{{ formatCurrency(tx.balance) }}</span>
                                </div>
                             </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.dark .glow-text { text-shadow: 0 0 15px rgba(34, 211, 238, 0.5); }
.dark .glow-rose { text-shadow: 0 0 15px rgba(244, 63, 94, 0.5); }
</style>
