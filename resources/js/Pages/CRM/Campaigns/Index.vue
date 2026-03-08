<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
    MegaphoneIcon, 
    MagnifyingGlassIcon,
    TrashIcon,
    PencilSquareIcon,
    CalendarIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    campaigns: Array,
    title: String
});

const form = useForm({
    name: '',
    type: 'email',
    status: 'planned',
    start_date: '',
    end_date: '',
    budget: 0
});

const showCreateModal = ref(false);
const editingCmp = ref(null);

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);
};

const submit = () => {
    if (editingCmp.value) {
        form.put(route('crm.campaigns.update', editingCmp.value.id), {
            onSuccess: () => {
                showCreateModal.value = false;
                editingCmp.value = null;
                form.reset();
            }
        });
    } else {
        form.post(route('crm.campaigns.store'), {
            onSuccess: () => {
                showCreateModal.value = false;
                form.reset();
            }
        });
    }
};

const editCmp = (cmp) => {
    editingCmp.value = cmp;
    form.name = cmp.name;
    form.type = cmp.type;
    form.status = cmp.status;
    form.start_date = cmp.start_date;
    form.end_date = cmp.end_date;
    form.budget = cmp.budget;
    showCreateModal.value = true;
};

const deleteCmp = (cmp) => {
    if (confirm('Are you sure you want to delete this campaign?')) {
        useForm({}).delete(route('crm.campaigns.destroy', cmp.id));
    }
};

const getStatusColor = (status) => {
    const colors = {
        'planned': 'bg-blue-500/10 text-blue-400 border-blue-500/20',
        'active': 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
        'completed': 'bg-purple-500/10 text-purple-400 border-purple-500/20'
    };
    return colors[status] || 'bg-slate-500/10 text-slate-400 border-slate-500/20';
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
                        <MegaphoneIcon class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                        MARKETING CAMPAIGNS
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Manage promotional activities and budget allocation.</p>
                </div>
                <button @click="showCreateModal = true; editingCmp = null; form.reset()" 
                    class="px-4 py-2 bg-purple-600 dark:bg-purple-500 hover:bg-purple-500 dark:hover:bg-purple-400 text-white font-bold rounded-lg transition-all flex items-center gap-2 shadow-lg shadow-purple-500/30">
                    <MegaphoneIcon class="h-5 w-5" />
                    NEW CAMPAIGN
                </button>
            </div>

            <!-- Main Content -->
            <div class="grid grid-cols-1 gap-6">
                <!-- Data Grid Mode for Campaigns -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="cmp in campaigns" :key="cmp.id" class="glass-card p-6 border border-slate-200 dark:border-purple-500/10 hover:border-purple-500/30 transition-all group">
                        <div class="flex justify-between items-start mb-4">
                            <span :class="['px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border', getStatusColor(cmp.status)]">
                                {{ cmp.status }}
                            </span>
                            <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button @click="editCmp(cmp)" class="text-slate-400 hover:text-purple-600 dark:hover:text-purple-400"><PencilSquareIcon class="h-4 w-4" /></button>
                                <button @click="deleteCmp(cmp)" class="text-slate-400 hover:text-rose-600 dark:hover:text-rose-400"><TrashIcon class="h-4 w-4" /></button>
                            </div>
                        </div>
                        
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-1">{{ cmp.name }}</h3>
                        <p class="text-xs text-purple-600 dark:text-purple-400 uppercase tracking-widest font-bold mb-4">{{ cmp.type }}</p>

                        <div class="space-y-3 border-t border-slate-100 dark:border-white/5 pt-4">
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500 flex items-center gap-2">
                                    <CalendarIcon class="h-4 w-4" /> Start
                                </span>
                                <span class="text-slate-700 dark:text-slate-300">{{ cmp.start_date || 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500 flex items-center gap-2">
                                    <CalendarIcon class="h-4 w-4" /> End
                                </span>
                                <span class="text-slate-700 dark:text-slate-300">{{ cmp.end_date || 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between text-sm pt-2">
                                <span class="text-slate-500">Budget</span>
                                <span class="text-purple-600 dark:text-purple-400 font-mono font-bold">{{ formatCurrency(cmp.budget) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Add New Card -->
                    <button @click="showCreateModal = true; editingCmp = null; form.reset()" 
                        class="glass-card p-6 flex flex-col items-center justify-center border-dashed border-slate-300 dark:border-slate-700 hover:border-purple-500/50 hover:bg-purple-50 dark:hover:bg-purple-500/5 transition-all text-slate-500 hover:text-purple-600 dark:hover:text-purple-400 h-full min-h-[250px] gap-4">
                        <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-white/5 flex items-center justify-center">
                            <MegaphoneIcon class="h-6 w-6" />
                        </div>
                        <span class="font-bold uppercase tracking-widest text-xs">Launch New Campaign</span>
                    </button>
                </div>
            </div>

            <!-- Campaign Operations Guide -->
            <div class="mt-8 relative hidden md:block">
                <div class="absolute inset-0 flex items-center" aria-hidden="true">
                    <div class="w-full border-t border-slate-200/60 dark:border-white/10"></div>
                </div>
                <div class="relative flex justify-center">
                    <span class="bg-[#F8FAFC] dark:bg-[#0F172A] px-4 text-[10px] font-black tracking-[0.2em] text-slate-400 dark:text-slate-500 uppercase">
                        Campaign Operations Guide
                    </span>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4 hidden md:grid mb-8">
                <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-purple-500/10 text-purple-500">
                            <MegaphoneIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Campaign Launch</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Initiate marketing efforts by creating campaigns spanning from <strong>Email</strong> blasts to <strong>Social Media</strong> push and <strong>Events</strong>.
                    </p>
                </div>

                <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-blue-500/10 text-blue-500">
                            <CalendarIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Timeline Management</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Define strict <strong>Start</strong> and <strong>End Dates</strong> to restrict your marketing cycles and accurately review periodic reach performances.
                    </p>
                </div>

                <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                            </svg>
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Budget Allocation</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Set a <strong>Budget</strong> ceiling per campaign. By cross-referencing this against leads generated, you can compute Cost-Per-Acquisition metrics.
                    </p>
                </div>
                
                <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-orange-500/10 text-orange-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Lifecycle Tracking</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Transition statuses manually from <strong>Planned</strong> to <strong>Active</strong> upon execution, and formally set to <strong>Completed</strong> upon closure.
                    </p>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="showCreateModal = false"></div>
            <div class="relative w-full max-w-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-2xl p-6 shadow-2xl">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-6">{{ editingCmp ? 'Edit Campaign' : 'Launch Campaign' }}</h3>
                
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Campaign Name</label>
                        <input v-model="form.name" type="text" class="w-full bg-slate-50 dark:bg-black/20 border border-slate-200 dark:border-white/10 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:border-purple-500 outline-none transition-colors" required>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Type</label>
                            <select v-model="form.type" class="w-full bg-slate-50 dark:bg-black/20 border border-slate-200 dark:border-white/10 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:border-purple-500 outline-none transition-colors">
                                <option value="email">Email Marketing</option>
                                <option value="social">Social Media</option>
                                <option value="event">Event / Webinar</option>
                                <option value="ads">Paid Ads</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Status</label>
                            <select v-model="form.status" class="w-full bg-slate-50 dark:bg-black/20 border border-slate-200 dark:border-white/10 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:border-purple-500 outline-none transition-colors">
                                <option value="planned">Planned</option>
                                <option value="active">Active</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Start Date</label>
                            <input v-model="form.start_date" type="date" class="w-full bg-slate-50 dark:bg-black/20 border border-slate-200 dark:border-white/10 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:border-purple-500 outline-none transition-colors">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">End Date</label>
                            <input v-model="form.end_date" type="date" class="w-full bg-slate-50 dark:bg-black/20 border border-slate-200 dark:border-white/10 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:border-purple-500 outline-none transition-colors">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Budget Allocation</label>
                        <input v-model="form.budget" type="number" class="w-full bg-slate-50 dark:bg-black/20 border border-slate-200 dark:border-white/10 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:border-purple-500 outline-none transition-colors">
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" @click="showCreateModal = false" class="px-4 py-2 text-slate-500 hover:text-slate-800 dark:text-slate-400 hover:text-white transition-colors">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-purple-600 dark:bg-purple-500 hover:bg-purple-500 dark:hover:bg-purple-400 text-white font-bold rounded-lg transition-colors">
                            {{ editingCmp ? 'Update Campaign' : 'Launch Campaign' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
