<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, watch } from 'vue';
import { 
    MagnifyingGlassIcon, 
    CalendarIcon,
    ChevronDownIcon,
    ChevronUpIcon,
    DocumentTextIcon
} from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';
import { formatDate } from '@/helpers';

const props = defineProps({
    journals: Object,
    filters: Object
});

const search = ref(props.filters.search || '');
const expandedRows = ref(new Set());

const toggleRow = (id) => {
    if (expandedRows.value.has(id)) {
        expandedRows.value.delete(id);
    } else {
        expandedRows.value.add(id);
    }
};

const handleSearch = debounce(() => {
    router.get(route('finance.ledger'), { search: search.value }, { preserveState: true, replace: true });
}, 300);

const formatCurrency = (value) => new Intl.NumberFormat('id-ID', { 
    style: 'currency', currency: 'IDR', maximumFractionDigits: 0 
}).format(value);

</script>

<template>
    <Head title="General Ledger" />

    <AppLayout title="General Ledger" :render-header="false">
        <div class="min-h-screen bg-white dark:bg-[#050510] relative font-mono text-slate-900 dark:text-cyan-50 transition-colors duration-300">
             <!-- Background -->
            <div class="fixed inset-0 z-0 pointer-events-none">
                <div class="absolute inset-0 bg-gradient-to-br from-slate-50 via-white to-slate-50 dark:from-indigo-950/20 dark:to-[#050510]"></div>
                <div class="perspective-grid absolute inset-0 opacity-[0.03] dark:opacity-10"></div>
            </div>

            <div class="relative z-10 p-6 space-y-6">
                 <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 dark:border-white/10 pb-4 transition-colors">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="px-2 py-0.5 text-[10px] bg-indigo-500/10 border border-indigo-500/20 rounded text-indigo-600 dark:text-indigo-400 tracking-wider">FIN.GL.VIEWER</span>
                        </div>
                        <h1 class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-cyan-600 dark:from-indigo-400 dark:to-cyan-400 tracking-widest uppercase glow-text">
                            GENERAL LEDGER
                        </h1>
                    </div>
                    
                    <!-- Search -->
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <MagnifyingGlassIcon class="h-5 w-5 text-slate-400 dark:text-indigo-400 group-focus-within:text-indigo-600 dark:group-focus-within:text-cyan-400 transition-colors" />
                        </div>
                        <input 
                            v-model="search"
                            @input="handleSearch"
                            type="text" 
                            class="block w-full pl-10 pr-3 py-2 border border-slate-200 dark:border-white/10 rounded-lg leading-5 bg-slate-100 dark:bg-white/5 text-slate-900 dark:text-indigo-100 placeholder-slate-400 dark:placeholder-indigo-400/50 focus:outline-none focus:bg-white dark:focus:bg-white/10 focus:border-indigo-500/50 dark:focus:border-cyan-500/50 focus:ring-1 focus:ring-indigo-500/50 dark:focus:ring-cyan-500/50 sm:text-sm transition-all shadow-lg shadow-indigo-500/5 dark:shadow-indigo-500/10" 
                            placeholder="Search Reference / Desc..."
                        >
                    </div>
                </div>

                <!-- Table Container -->
                <div class="bg-white/80 dark:bg-gray-900/50 backdrop-blur-xl border border-slate-200 dark:border-white/10 rounded-xl overflow-hidden shadow-2xl relative transition-all">
                    <!-- Neon Line Top -->
                    <div class="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-indigo-600 dark:via-indigo-500 to-transparent opacity-30 dark:opacity-50"></div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-slate-100 dark:border-white/5 bg-slate-50 dark:bg-white/5 text-[10px] uppercase tracking-widest text-slate-500 dark:text-indigo-300">
                                    <th class="px-6 py-4 font-bold">Date</th>
                                    <th class="px-6 py-4 font-bold">Reference</th>
                                    <th class="px-6 py-4 font-bold">Description</th>
                                    <th class="px-6 py-4 font-bold text-center">Status</th>
                                    <th class="px-6 py-4 font-bold text-right">Details</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-white/5 text-sm">
                                <template v-for="journal in journals.data" :key="journal.id">
                                    <!-- Main Row -->
                                    <tr 
                                        class="hover:bg-slate-50 dark:hover:bg-white/5 transition-colors cursor-pointer group"
                                        @click="toggleRow(journal.id)"
                                    >
                                        <td class="px-6 py-4 font-mono text-slate-500 dark:text-slate-400 group-hover:text-slate-900 dark:group-hover:text-white transition-colors">
                                            {{ formatDate(journal.date) }}
                                        </td>
                                        <td class="px-6 py-4 font-bold text-indigo-600 dark:text-cyan-400 group-hover:text-indigo-800 dark:group-hover:text-cyan-300 transition-colors">
                                            {{ journal.reference }}
                                        </td>
                                        <td class="px-6 py-4 text-slate-600 dark:text-slate-300">
                                            {{ journal.description }}
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="px-2 py-1 text-[10px] uppercase tracking-wider rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                                {{ journal.status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <button class="text-indigo-400 hover:text-white transition-colors">
                                                <ChevronUpIcon v-if="expandedRows.has(journal.id)" class="h-4 w-4" />
                                                <ChevronDownIcon v-else class="h-4 w-4" />
                                            </button>
                                        </td>
                                    </tr>
                                    
                                    <!-- Expanded Details -->
                                    <tr v-if="expandedRows.has(journal.id)" class="bg-indigo-50/50 dark:bg-indigo-950/20">
                                        <td colspan="5" class="p-4">
                                            <div class="bg-white dark:bg-black/40 rounded-lg border border-slate-200 dark:border-white/5 overflow-hidden shadow-inner">
                                                <table class="w-full text-left text-xs border-collapse">
                                                    <thead>
                                                        <tr class="text-slate-500 dark:text-slate-500 border-b border-slate-100 dark:border-white/5 bg-slate-50/50 dark:bg-black/20">
                                                            <th class="px-4 py-2 font-bold">Account Code</th>
                                                            <th class="px-4 py-2 font-bold">Account Name</th>
                                                            <th class="px-4 py-2 text-right text-emerald-600 dark:text-emerald-500 font-bold">Debit</th>
                                                            <th class="px-4 py-2 text-right text-rose-600 dark:text-rose-500 font-bold">Credit</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                                                        <tr v-for="item in journal.items" :key="item.id" class="hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                                                            <td class="px-4 py-2 font-bold text-slate-600 dark:text-slate-400">{{ item.coa.code }}</td>
                                                            <td class="px-4 py-2 text-slate-500 dark:text-slate-300">{{ item.coa.name }}</td>
                                                            <td class="px-4 py-2 text-right font-mono text-emerald-600 dark:text-emerald-400">
                                                                {{ item.debit > 0 ? formatCurrency(item.debit) : '-' }}
                                                            </td>
                                                            <td class="px-4 py-2 text-right font-mono text-rose-600 dark:text-rose-400">
                                                                {{ item.credit > 0 ? formatCurrency(item.credit) : '-' }}
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination (Simple) -->
                    <div class="p-4 border-t border-slate-200 dark:border-white/10 flex justify-between items-center text-xs text-slate-500 transition-colors">
                        <span>Showing {{ journals.from }} to {{ journals.to }} of {{ journals.total }} entries</span>
                        <div class="flex gap-2">
                             <component 
                                :is="link.url ? 'Link' : 'span'"
                                v-for="link in journals.links" 
                                :key="link.label"
                                :href="link.url"
                                class="px-3 py-1 rounded border border-slate-200 dark:border-white/10 transition-all"
                                :class="{ 
                                    'bg-indigo-600 dark:bg-cyan-500/20 text-white dark:text-cyan-400 border-indigo-600 dark:border-cyan-500/50': link.active, 
                                    'hover:bg-slate-50 dark:hover:bg-white/5 cursor-pointer bg-white dark:bg-transparent': link.url && !link.active, 
                                    'opacity-50 cursor-not-allowed': !link.url 
                                }"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.glow-text {
    text-shadow: 0 0 10px rgba(79, 70, 229, 0.2);
}

.dark .glow-text {
    text-shadow: 0 0 10px rgba(34, 211, 238, 0.5);
}

.perspective-grid {
    background-image: 
        linear-gradient(to right, rgba(99, 102, 241, 0.1) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(99, 102, 241, 0.1) 1px, transparent 1px);
    background-size: 40px 40px;
    transform: perspective(500px) rotateX(60deg) translateY(-100px) scale(2);
    animation: grid-move 20s linear infinite;
    transform-origin: top;
}

@keyframes grid-move {
    0% { background-position: 0 0; }
    100% { background-position: 0 40px; }
}
</style>
