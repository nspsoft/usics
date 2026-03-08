<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
    CurrencyDollarIcon, 
    MagnifyingGlassIcon,
    FunnelIcon,
    TrashIcon,
    PencilSquareIcon,
    ChartBarIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    opportunities: Array,
    leads: Array,
    title: String
});

const form = useForm({
    name: '',
    lead_id: '',
    amount: 0,
    stage: 'prospecting',
    close_date: '',
    probability: 10
});

const showCreateModal = ref(false);
const editingOpp = ref(null);

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);
};

const submit = () => {
    if (editingOpp.value) {
        form.put(route('crm.opportunities.update', editingOpp.value.id), {
            onSuccess: () => {
                showCreateModal.value = false;
                editingOpp.value = null;
                form.reset();
            }
        });
    } else {
        form.post(route('crm.opportunities.store'), {
            onSuccess: () => {
                showCreateModal.value = false;
                form.reset();
            }
        });
    }
};

const editOpp = (opp) => {
    editingOpp.value = opp;
    form.name = opp.name;
    form.lead_id = props.leads.find(l => l.name === opp.lead)?.id || ''; // Ideally match by ID from backend if available
    form.amount = opp.amount;
    form.stage = opp.stage;
    form.close_date = opp.close_date;
    form.probability = opp.probability;
    showCreateModal.value = true;
};

const deleteOpp = (opp) => {
    if (confirm('Are you sure you want to delete this opportunity?')) {
        useForm({}).delete(route('crm.opportunities.destroy', opp.id));
    }
};

const getStageColor = (stage) => {
    const colors = {
        'prospecting': 'bg-blue-500/10 text-blue-400 border-blue-500/20',
        'negotiation': 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
        'closed_won': 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
        'closed_lost': 'bg-slate-500/10 text-slate-400 border-slate-500/20'
    };
    return colors[stage] || 'bg-slate-500/10 text-slate-400 border-slate-500/20';
};
</script>

<template>
    <Head :title="title" />

    <AppLayout :title="title">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <CurrencyDollarIcon class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                        OPPORTUNITY PIPELINE
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Track deals, negotiations, and projected revenue.</p>
                </div>
                <button @click="showCreateModal = true; editingOpp = null; form.reset()" 
                    class="px-4 py-2 bg-emerald-600 dark:bg-emerald-500 hover:bg-emerald-500 dark:hover:bg-emerald-400 text-white dark:text-slate-900 font-bold rounded-lg transition-all flex items-center gap-2 shadow-lg shadow-emerald-500/30">
                    <CurrencyDollarIcon class="h-5 w-5" />
                    NEW OPPORTUNITY
                </button>
            </div>

            <!-- Main Content -->
            <div class="grid grid-cols-1 gap-6">
                <div class="glass-card p-6">
                    <!-- Filters -->
                    <div class="flex items-center gap-4 mb-6">
                        <div class="relative flex-1 max-w-md">
                            <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400 dark:text-slate-500" />
                            <input type="text" placeholder="Search opportunities..." 
                                class="w-full bg-slate-50 dark:bg-black/20 border border-slate-200 dark:border-white/10 rounded-lg pl-10 pr-4 py-2 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:border-emerald-500/50 focus:ring-1 focus:ring-emerald-500/50 transition-all outline-none">
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto overflow-y-auto max-h-[600px]">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="text-xs text-slate-500 font-bold uppercase tracking-wider border-b border-slate-200 dark:border-white/10">
                                    <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3">Opportunity Name</th>
                                    <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3">Lead / Customer</th>
                                    <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3">Stage</th>
                                    <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3">Probability</th>
                                    <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-right">Value</th>
                                    <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                                <tr v-for="opp in opportunities" :key="opp.id" class="group hover:bg-slate-50 dark:hover:bg-white/5 transition-all">
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-slate-900 dark:text-white">{{ opp.name }}</div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400">Close: {{ opp.close_date || 'TBD' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                                        {{ opp.lead }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span :class="['px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wider border', getStageColor(opp.stage)]">
                                            {{ opp.stage.replace('_', ' ') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1 h-1.5 w-24 bg-slate-200 dark:bg-slate-800 rounded-full overflow-hidden">
                                                <div class="h-full bg-emerald-500" :style="{ width: `${opp.probability}%` }"></div>
                                            </div>
                                            <span class="text-xs text-slate-500 dark:text-slate-400 font-mono">{{ opp.probability }}%</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                        {{ formatCurrency(opp.amount) }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-all">
                                            <button @click="editOpp(opp)" class="p-1.5 text-slate-400 hover:text-cyan-600 dark:hover:text-cyan-400 hover:bg-cyan-50 dark:hover:bg-cyan-500/10 rounded transition-all">
                                                <PencilSquareIcon class="h-4 w-4" />
                                            </button>
                                            <button @click="deleteOpp(opp)" class="p-1.5 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10 rounded transition-all">
                                                <TrashIcon class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="opportunities.length === 0">
                                    <td colspan="6" class="px-4 py-12 text-center text-slate-500 uppercase tracking-widest text-sm">
                                        No opportunities found
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Opportunity Operations Guide -->
            <div class="mt-8 relative hidden md:block">
                <div class="absolute inset-0 flex items-center" aria-hidden="true">
                    <div class="w-full border-t border-slate-200/60 dark:border-white/10"></div>
                </div>
                <div class="relative flex justify-center">
                    <span class="bg-[#F8FAFC] dark:bg-[#0F172A] px-4 text-[10px] font-black tracking-[0.2em] text-slate-400 dark:text-slate-500 uppercase">
                        Opportunity Operations Guide
                    </span>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4 hidden md:grid mb-8">
                <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-500">
                            <CurrencyDollarIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Pipeline Valuation</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Assign an estimated monetary <strong>Value</strong> to every opportunity to forecast potential revenue and prioritize high-worth deals effectively.
                    </p>
                </div>

                <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-blue-500/10 text-blue-500">
                            <ChartBarIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Stage Progression</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Update stages systematically from <strong>Prospecting</strong> through <strong>Negotiation</strong>, reflecting the current engagement maturity with the client.
                    </p>
                </div>

                <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-indigo-500/10 text-indigo-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="20" x2="12" y2="10"></line><line x1="18" y1="20" x2="18" y2="4"></line><line x1="6" y1="20" x2="6" y2="16"></line>
                            </svg>
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Probability Index</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Adjust the <strong>Probability (%)</strong> percentage based on client interest levels. This weighting refines realistic sales forecasting metrics.
                    </p>
                </div>
                
                <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-amber-500/10 text-amber-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                            </svg>
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Win/Loss Analysis</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Conclude deals accurately as either <strong>Closed Won</strong> to proceed to ordering, or <strong>Closed Lost</strong> to close the cycle loop.
                    </p>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="showCreateModal = false"></div>
            <div class="relative w-full max-w-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-2xl p-6 shadow-2xl">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-6">{{ editingOpp ? 'Edit Opportunity' : 'New Opportunity' }}</h3>
                
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Opportunity Name</label>
                        <input v-model="form.name" type="text" class="w-full bg-slate-50 dark:bg-black/20 border border-slate-200 dark:border-white/10 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:border-emerald-500 outline-none transition-colors" required>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Related Lead</label>
                        <select v-model="form.lead_id" class="w-full bg-slate-50 dark:bg-black/20 border border-slate-200 dark:border-white/10 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:border-emerald-500 outline-none transition-colors">
                            <option value="">Select Lead...</option>
                            <option v-for="lead in leads" :key="lead.id" :value="lead.id">{{ lead.name }}</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Estimated Value</label>
                            <input v-model="form.amount" type="number" class="w-full bg-slate-50 dark:bg-black/20 border border-slate-200 dark:border-white/10 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:border-emerald-500 outline-none transition-colors">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Stage</label>
                            <select v-model="form.stage" class="w-full bg-slate-50 dark:bg-black/20 border border-slate-200 dark:border-white/10 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:border-emerald-500 outline-none transition-colors">
                                <option value="prospecting">Prospecting</option>
                                <option value="negotiation">Negotiation</option>
                                <option value="closed_won">Closed Won</option>
                                <option value="closed_lost">Closed Lost</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Probability (%)</label>
                            <input v-model="form.probability" type="number" min="0" max="100" class="w-full bg-slate-50 dark:bg-black/20 border border-slate-200 dark:border-white/10 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:border-emerald-500 outline-none transition-colors">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Expected Close</label>
                            <input v-model="form.close_date" type="date" class="w-full bg-slate-50 dark:bg-black/20 border border-slate-200 dark:border-white/10 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:border-emerald-500 outline-none transition-colors">
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" @click="showCreateModal = false" class="px-4 py-2 text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white transition-colors">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 dark:bg-emerald-500 hover:bg-emerald-500 dark:hover:bg-emerald-400 text-white dark:text-slate-900 font-bold rounded-lg transition-colors">
                            {{ editingOpp ? 'Update Opportunity' : 'Create Opportunity' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
