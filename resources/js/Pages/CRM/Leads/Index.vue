<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
    UserPlusIcon, 
    MagnifyingGlassIcon,
    FunnelIcon,
    TrashIcon,
    PencilSquareIcon,
    MapIcon,
    MapPinIcon
} from '@heroicons/vue/24/outline';
import MapPicker from '@/Components/MapPicker.vue';

const props = defineProps({
    leads: Array,
    title: String
});

const form = useForm({
    name: '',
    company: '',
    email: '',
    phone: '',
    address: '',
    status: 'new',
    source: '',
    latitude: '',
    longitude: ''
});

const showCreateModal = ref(false);
const editingLead = ref(null);
const showMap = ref(false);

const onMapConfirm = (location) => {
    form.address = location.address;
    if (location.latitude) form.latitude = location.latitude;
    if (location.longitude) form.longitude = location.longitude;
};

const submit = () => {
    if (editingLead.value) {
        form.put(route('crm.leads.update', editingLead.value.id), {
            onSuccess: () => {
                showCreateModal.value = false;
                editingLead.value = null;
                form.reset();
            }
        });
    } else {
        form.post(route('crm.leads.store'), {
            onSuccess: () => {
                showCreateModal.value = false;
                form.reset();
            }
        });
    }
};

const editLead = (lead) => {
    editingLead.value = lead;
    form.name = lead.name;
    form.company = lead.company;
    form.email = lead.email;
    form.phone = lead.phone;
    form.address = lead.address ?? '';
    form.status = lead.status;
    form.source = lead.source;
    form.latitude = lead.latitude ?? '';
    form.longitude = lead.longitude ?? '';
    showCreateModal.value = true;
};

const deleteLead = (lead) => {
    if (confirm('Are you sure you want to delete this lead?')) {
        useForm({}).delete(route('crm.leads.destroy', lead.id));
    }
};

const getStatusColor = (status) => {
    const colors = {
        'new': 'bg-blue-500/10 text-blue-400 border-blue-500/20',
        'contacted': 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
        'qualified': 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
        'lost': 'bg-rose-500/10 text-rose-400 border-rose-500/20'
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
                        <UserPlusIcon class="h-6 w-6 text-cyan-600 dark:text-cyan-400" />
                        LEADS DATABASE
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Manage and track potential customer acquisitions.</p>
                </div>
                <button @click="showCreateModal = true; editingLead = null; form.reset()" 
                    class="px-4 py-2 bg-cyan-600 dark:bg-cyan-500 hover:bg-cyan-500 dark:hover:bg-cyan-400 text-white dark:text-slate-900 font-bold rounded-lg transition-all flex items-center gap-2 shadow-lg shadow-cyan-500/30">
                    <UserPlusIcon class="h-5 w-5" />
                    NEW LEAD
                </button>
            </div>

            <!-- Main Content -->
            <div class="grid grid-cols-1 gap-6">
                <div class="glass-card p-6">
                    <!-- Filters -->
                    <div class="flex items-center gap-4 mb-6">
                        <div class="relative flex-1 max-w-md">
                            <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400 dark:text-slate-500" />
                            <input type="text" placeholder="Search leads..." 
                                class="w-full bg-slate-50 dark:bg-black/20 border border-slate-200 dark:border-white/10 rounded-lg pl-10 pr-4 py-2 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:border-cyan-500/50 focus:ring-1 focus:ring-cyan-500/50 transition-all outline-none">
                        </div>
                        <button class="p-2 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-white/10 rounded-lg hover:bg-slate-100 dark:hover:bg-white/5 transition-all">
                            <FunnelIcon class="h-5 w-5" />
                        </button>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto overflow-y-auto max-h-[600px]">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="text-xs text-slate-500 font-bold uppercase tracking-wider border-b border-slate-200 dark:border-white/10">
                                    <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3">Name / Company</th>
                                    <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3">Contact</th>
                                    <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3">Source</th>
                                    <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3">Status</th>
                                    <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                                <tr v-for="lead in leads" :key="lead.id" class="group hover:bg-slate-50 dark:hover:bg-white/5 transition-all">
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-slate-900 dark:text-white">{{ lead.name }}</div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400">{{ lead.company || 'No Company' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                                        <div>{{ lead.email }}</div>
                                        <div class="text-xs text-slate-400 dark:text-slate-500">{{ lead.phone }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-400">{{ lead.source || '-' }}</td>
                                    <td class="px-4 py-3">
                                        <span :class="['px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wider border', getStatusColor(lead.status)]">
                                            {{ lead.status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-all">
                                            <button @click="editLead(lead)" class="p-1.5 text-slate-400 hover:text-cyan-600 dark:hover:text-cyan-400 hover:bg-cyan-50 dark:hover:bg-cyan-500/10 rounded transition-all">
                                                <PencilSquareIcon class="h-4 w-4" />
                                            </button>
                                            <button @click="deleteLead(lead)" class="p-1.5 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10 rounded transition-all">
                                                <TrashIcon class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="leads.length === 0">
                                    <td colspan="5" class="px-4 py-12 text-center text-slate-500 uppercase tracking-widest text-sm">
                                        No leads found in database
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Leads Operations Guide -->
            <div class="mt-8 relative hidden md:block">
                <div class="absolute inset-0 flex items-center" aria-hidden="true">
                    <div class="w-full border-t border-slate-200/60 dark:border-white/10"></div>
                </div>
                <div class="relative flex justify-center">
                    <span class="bg-[#F8FAFC] dark:bg-[#0F172A] px-4 text-[10px] font-black tracking-[0.2em] text-slate-400 dark:text-slate-500 uppercase">
                        Leads Operations Guide
                    </span>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4 hidden md:grid mb-8">
                <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-cyan-500/10 text-cyan-500">
                            <UserPlusIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Prospecting</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Input new potential buyers into the system and initially mark their status as <strong>New</strong> to start the qualification funnel.
                    </p>
                </div>

                <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-yellow-500/10 text-yellow-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                            </svg>
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Lead Qualification</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Assess the viability of each lead. Update their status to <strong>Contacted</strong> once reached out, and <strong>Qualified</strong> when they meet purchasing criteria.
                    </p>
                </div>

                <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-purple-500/10 text-purple-400">
                            <FunnelIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Source Tracking</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Always record the <strong>Source</strong> of your leads (e.g., LinkedIn, Website) to evaluate the highest yielding acquisition channels.
                    </p>
                </div>
                
                <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-rose-500/10 text-rose-400">
                            <TrashIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Conversion Path</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        A lead marked as <strong>Lost</strong> signifies an ended pursuit. Focus on Qualified leads to seamlessly convert them into actual Sales Opportunities.
                    </p>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal (Simplified) -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="showCreateModal = false"></div>
            <div class="relative w-full max-w-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-2xl p-6 shadow-2xl">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-6">{{ editingLead ? 'Edit Lead' : 'Create New Lead' }}</h3>
                
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Full Name</label>
                        <input v-model="form.name" type="text" class="w-full bg-slate-50 dark:bg-black/20 border border-slate-200 dark:border-white/10 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:border-cyan-500 outline-none transition-colors" required>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Company</label>
                            <input v-model="form.company" type="text" class="w-full bg-slate-50 dark:bg-black/20 border border-slate-200 dark:border-white/10 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:border-cyan-500 outline-none transition-colors">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Status</label>
                            <select v-model="form.status" class="w-full bg-slate-50 dark:bg-black/20 border border-slate-200 dark:border-white/10 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:border-cyan-500 outline-none transition-colors">
                                <option value="new">New</option>
                                <option value="contacted">Contacted</option>
                                <option value="qualified">Qualified</option>
                                <option value="lost">Lost</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Email</label>
                            <input v-model="form.email" type="email" class="w-full bg-slate-50 dark:bg-black/20 border border-slate-200 dark:border-white/10 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:border-cyan-500 outline-none transition-colors">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Phone</label>
                            <input v-model="form.phone" type="text" class="w-full bg-slate-50 dark:bg-black/20 border border-slate-200 dark:border-white/10 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:border-cyan-500 outline-none transition-colors">
                        </div>
                    </div>

                    <div class="relative">
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Address</label>
                        <textarea v-model="form.address" rows="2" placeholder="Full address..." class="w-full bg-slate-50 dark:bg-black/20 border border-slate-200 dark:border-white/10 rounded-lg pl-4 pr-24 py-2 text-slate-900 dark:text-white focus:border-cyan-500 outline-none transition-colors"></textarea>
                        <button 
                            type="button" 
                            @click="showMap = true"
                            class="absolute right-2 bottom-3 px-2 py-1 rounded-md bg-slate-700 hover:bg-slate-600 text-slate-200 text-[10px] font-bold transition-all shadow flex items-center gap-1 z-10"
                        >
                            <MapIcon class="h-3 w-3 text-cyan-400" />
                            Pick Map
                        </button>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Source</label>
                        <input v-model="form.source" type="text" placeholder="e.g. LinkedIn, Website, Referral" class="w-full bg-slate-50 dark:bg-black/20 border border-slate-200 dark:border-white/10 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:border-cyan-500 outline-none transition-colors">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Latitude</label>
                            <input v-model="form.latitude" type="text" placeholder="-6.2088" class="w-full bg-slate-50 dark:bg-black/20 border border-slate-200 dark:border-white/10 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:border-cyan-500 outline-none transition-colors">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Longitude</label>
                            <input v-model="form.longitude" type="text" placeholder="106.8456" class="w-full bg-slate-50 dark:bg-black/20 border border-slate-200 dark:border-white/10 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:border-cyan-500 outline-none transition-colors">
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" @click="showCreateModal = false" class="px-4 py-2 text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white transition-colors">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-cyan-600 dark:bg-cyan-500 hover:bg-cyan-500 dark:hover:bg-cyan-400 text-white dark:text-slate-900 font-bold rounded-lg transition-colors">
                            {{ editingLead ? 'Update Lead' : 'Create Lead' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <MapPicker 
            :show="showMap" 
            @close="showMap = false" 
            @confirm="onMapConfirm"
        />
    </AppLayout>
</template>
