<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import {
    LifebuoyIcon,
    PlusIcon,
    MagnifyingGlassIcon,
    FunnelIcon,
    BugAntIcon,
    WrenchIcon,
    SparklesIcon,
    ClockIcon,
    CheckCircleIcon,
    ChatBubbleLeftRightIcon,
    UserIcon,
    ArrowPathIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    tickets: Object,
    stats: Object,
    users: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');
const category = ref(props.filters.category || '');
const priority = ref(props.filters.priority || '');
const status = ref(props.filters.status || '');
const myTickets = ref(props.filters.my_tickets === '1' || props.filters.my_tickets === true);

const applyFilters = () => {
    router.get(
        route('helpdesk.index'),
        {
            search: search.value,
            category: category.value,
            priority: priority.value,
            status: status.value,
            my_tickets: myTickets.value ? 1 : null,
        },
        { preserveState: true, replace: true }
    );
};

const resetFilters = () => {
    search.value = '';
    category.value = '';
    priority.value = '';
    status.value = '';
    myTickets.value = false;
    applyFilters();
};

const getCategoryBadge = (cat) => {
    switch (cat) {
        case 'bug':
            return { label: 'Bug / Error', bg: 'bg-rose-500/10 text-rose-400 ring-rose-500/20', icon: BugAntIcon };
        case 'revision':
            return { label: 'Revisi Fitur', bg: 'bg-amber-500/10 text-amber-400 ring-amber-500/20', icon: WrenchIcon };
        case 'feature_request':
            return { label: 'Request Fitur', bg: 'bg-purple-500/10 text-purple-400 ring-purple-500/20', icon: SparklesIcon };
        default:
            return { label: cat, bg: 'bg-slate-500/10 text-slate-400 ring-slate-500/20', icon: LifebuoyIcon };
    }
};

const getPriorityBadge = (prio) => {
    switch (prio) {
        case 'critical':
            return { label: 'Critical', bg: 'bg-red-600 text-white font-bold animate-pulse' };
        case 'high':
            return { label: 'High', bg: 'bg-orange-500/20 text-orange-400 ring-orange-500/30 font-semibold' };
        case 'medium':
            return { label: 'Medium', bg: 'bg-blue-500/20 text-blue-400 ring-blue-500/30' };
        case 'low':
            return { label: 'Low', bg: 'bg-slate-500/20 text-slate-400 ring-slate-500/30' };
        default:
            return { label: prio, bg: 'bg-slate-500/20 text-slate-400' };
    }
};

const getStatusBadge = (st) => {
    switch (st) {
        case 'open':
            return { label: 'Open', bg: 'bg-blue-500/10 text-blue-400 ring-blue-500/20' };
        case 'in_progress':
            return { label: 'In Progress', bg: 'bg-amber-500/10 text-amber-400 ring-amber-500/20' };
        case 'pending_user':
            return { label: 'Pending User', bg: 'bg-purple-500/10 text-purple-400 ring-purple-500/20' };
        case 'resolved':
            return { label: 'Resolved', bg: 'bg-emerald-500/10 text-emerald-400 ring-emerald-500/20' };
        case 'closed':
            return { label: 'Closed', bg: 'bg-slate-500/10 text-slate-400 ring-slate-500/20' };
        default:
            return { label: st, bg: 'bg-slate-500/10 text-slate-400' };
    }
};
</script>

<template>
    <Head title="Helpdesk & Support" />

    <AppLayout title="Helpdesk & Support">
        <div class="space-y-6">
            <!-- Header & Action -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-3">
                        <LifebuoyIcon class="h-8 w-8 text-blue-500" />
                        Helpdesk & System Support
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Pusat pelaporan bug, pengajuan revisi fitur, dan usulan pengembangan sistem ERP.
                    </p>
                </div>
                <Link
                    :href="route('helpdesk.create')"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-500 transition-all shadow-lg shadow-blue-500/25 active:scale-95"
                >
                    <PlusIcon class="h-5 w-5" />
                    Buat Tiket Baru
                </Link>
            </div>

            <!-- Stats Overview Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="rounded-2xl glass-card p-4 border border-slate-200/50 dark:border-slate-800/50">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Tiket</span>
                        <div class="p-2 rounded-xl bg-blue-500/10 text-blue-400">
                            <LifebuoyIcon class="h-5 w-5" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white mt-2">{{ stats.total }}</p>
                </div>
                <div class="rounded-2xl glass-card p-4 border border-slate-200/50 dark:border-slate-800/50">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tiket Baru (Open)</span>
                        <div class="p-2 rounded-xl bg-amber-500/10 text-amber-400">
                            <ClockIcon class="h-5 w-5" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-amber-500 mt-2">{{ stats.open }}</p>
                </div>
                <div class="rounded-2xl glass-card p-4 border border-slate-200/50 dark:border-slate-800/50">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Sedang Diproses</span>
                        <div class="p-2 rounded-xl bg-purple-500/10 text-purple-400">
                            <ArrowPathIcon class="h-5 w-5" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-purple-400 mt-2">{{ stats.in_progress }}</p>
                </div>
                <div class="rounded-2xl glass-card p-4 border border-slate-200/50 dark:border-slate-800/50">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Selesai (Resolved)</span>
                        <div class="p-2 rounded-xl bg-emerald-500/10 text-emerald-400">
                            <CheckCircleIcon class="h-5 w-5" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-emerald-500 mt-2">{{ stats.resolved }}</p>
                </div>
            </div>

            <!-- Filter & Search Bar -->
            <div class="rounded-2xl glass-card p-4 space-y-4">
                <div class="flex flex-col lg:flex-row gap-3">
                    <div class="relative flex-1">
                        <MagnifyingGlassIcon class="absolute left-3.5 top-3 h-5 w-5 text-slate-400" />
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Cari nomor tiket, judul, atau deskripsi..."
                            class="w-full rounded-xl border-0 bg-slate-100 dark:bg-slate-800/60 pl-11 pr-4 py-2.5 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 placeholder-slate-400"
                            @keyup.enter="applyFilters"
                        />
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <select
                            v-model="category"
                            class="rounded-xl border-0 bg-slate-100 dark:bg-slate-800/60 py-2.5 px-3 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                            @change="applyFilters"
                        >
                            <option value="">Semua Kategori</option>
                            <option value="bug">🐛 Bug / Error</option>
                            <option value="revision">✏️ Revisi Fitur</option>
                            <option value="feature_request">🚀 Request Fitur</option>
                        </select>
                        <select
                            v-model="priority"
                            class="rounded-xl border-0 bg-slate-100 dark:bg-slate-800/60 py-2.5 px-3 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                            @change="applyFilters"
                        >
                            <option value="">Semua Prioritas</option>
                            <option value="critical">🔴 Critical</option>
                            <option value="high">🟠 High</option>
                            <option value="medium">🔵 Medium</option>
                            <option value="low">⚪ Low</option>
                        </select>
                        <select
                            v-model="status"
                            class="rounded-xl border-0 bg-slate-100 dark:bg-slate-800/60 py-2.5 px-3 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                            @change="applyFilters"
                        >
                            <option value="">Semua Status</option>
                            <option value="open">Open</option>
                            <option value="in_progress">In Progress</option>
                            <option value="pending_user">Pending User</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                        </select>
                        <label class="inline-flex items-center gap-2 cursor-pointer px-3 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800/60 text-sm text-slate-700 dark:text-slate-300">
                            <input
                                v-model="myTickets"
                                type="checkbox"
                                class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                @change="applyFilters"
                            />
                            <span>Tiket Saya</span>
                        </label>
                        <button
                            type="button"
                            @click="resetFilters"
                            class="p-2.5 rounded-xl bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-slate-700 transition-colors"
                            title="Reset Filter"
                        >
                            <ArrowPathIcon class="h-5 w-5" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tickets List Table -->
            <div class="rounded-2xl glass-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-500 dark:text-slate-400">
                        <thead class="bg-slate-100/50 dark:bg-slate-900/50 text-slate-700 dark:text-slate-300 font-semibold uppercase tracking-wider text-xs border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="px-6 py-4">Tiket</th>
                                <th class="px-6 py-4">Judul & Halaman</th>
                                <th class="px-6 py-4">Kategori & Prioritas</th>
                                <th class="px-6 py-4">Pelapor</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr
                                v-for="ticket in tickets.data"
                                :key="ticket.id"
                                class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-colors"
                            >
                                <td class="px-6 py-4 font-mono font-bold text-blue-500 dark:text-blue-400 whitespace-nowrap">
                                    {{ ticket.ticket_number }}
                                </td>
                                <td class="px-6 py-4">
                                    <Link :href="route('helpdesk.show', ticket.id)" class="font-semibold text-slate-900 dark:text-white hover:text-blue-500 dark:hover:text-blue-400 transition-colors line-clamp-1">
                                        {{ ticket.title }}
                                    </Link>
                                    <span v-if="ticket.url" class="text-xs text-slate-400 dark:text-slate-500 block truncate max-w-xs mt-0.5" :title="ticket.url">
                                        🔗 {{ ticket.url }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap space-x-2">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-medium ring-1 ring-inset"
                                        :class="getCategoryBadge(ticket.category).bg"
                                    >
                                        <component :is="getCategoryBadge(ticket.category).icon" class="h-3.5 w-3.5" />
                                        {{ getCategoryBadge(ticket.category).label }}
                                    </span>
                                    <span
                                        class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] ring-1 ring-inset uppercase"
                                        :class="getPriorityBadge(ticket.priority).bg"
                                    >
                                        {{ getPriorityBadge(ticket.priority).label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="h-7 w-7 rounded-full bg-blue-500/20 text-blue-400 flex items-center justify-center font-bold text-xs">
                                            {{ ticket.user?.name ? ticket.user.name.charAt(0).toUpperCase() : 'U' }}
                                        </div>
                                        <div>
                                            <p class="text-xs font-medium text-slate-900 dark:text-white">{{ ticket.user?.name || 'Unknown' }}</p>
                                            <p class="text-[10px] text-slate-400">{{ new Date(ticket.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-medium ring-1 ring-inset uppercase tracking-wider"
                                        :class="getStatusBadge(ticket.status).bg"
                                    >
                                        {{ getStatusBadge(ticket.status).label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <Link
                                        :href="route('helpdesk.show', ticket.id)"
                                        class="inline-flex items-center gap-1 text-xs font-semibold text-blue-500 hover:text-blue-400 p-2 rounded-lg hover:bg-blue-500/10 transition-colors"
                                    >
                                        <ChatBubbleLeftRightIcon class="h-4 w-4" />
                                        Detail
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="tickets.data.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                                    <LifebuoyIcon class="h-12 w-12 mx-auto mb-3 opacity-30" />
                                    <p class="text-base font-medium">Tidak ada tiket helpdesk yang ditemukan.</p>
                                    <p class="text-xs text-slate-500 mt-1">Gunakan tombol "Buat Tiket Baru" untuk menyampaikan kendala atau saran.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                    <Pagination :links="tickets.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
