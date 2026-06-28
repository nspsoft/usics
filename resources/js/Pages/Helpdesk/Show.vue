<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ArrowLeftIcon,
    LifebuoyIcon,
    PaperClipIcon,
    BugAntIcon,
    WrenchIcon,
    SparklesIcon,
    ChatBubbleLeftRightIcon,
    UserIcon,
    CheckCircleIcon,
    ClockIcon,
    ArrowPathIcon,
    PaperAirplaneIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    ticket: Object,
    users: Array,
});

const replyForm = useForm({
    message: '',
    is_internal: false,
    attachment: null,
});

const statusForm = useForm({
    status: props.ticket.status,
    assigned_to: props.ticket.assigned_to || '',
});

const handleFileChange = (e) => {
    replyForm.attachment = e.target.files[0];
};

const submitReply = () => {
    replyForm.post(route('helpdesk.reply', props.ticket.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            replyForm.reset('message', 'attachment');
        },
    });
};

const submitStatusUpdate = () => {
    statusForm.put(route('helpdesk.status', props.ticket.id), {
        preserveScroll: true,
    });
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
            return { label: cat, bg: 'bg-slate-500/10 text-slate-400', icon: LifebuoyIcon };
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
    <Head :title="`Tiket ${ticket.ticket_number}`" />

    <AppLayout title="Detail Tiket Helpdesk">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('helpdesk.index')"
                        class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition-colors"
                    >
                        <ArrowLeftIcon class="h-5 w-5" />
                    </Link>
                    <div>
                        <div class="flex items-center gap-3">
                            <span class="font-mono text-lg font-bold text-blue-500">{{ ticket.ticket_number }}</span>
                            <span
                                class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-semibold uppercase tracking-wider"
                                :class="getStatusBadge(ticket.status).bg"
                            >
                                {{ getStatusBadge(ticket.status).label }}
                            </span>
                        </div>
                        <h1 class="text-xl font-bold text-slate-900 dark:text-white mt-1">{{ ticket.title }}</h1>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Main: Discussion & Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Ticket Description Card -->
                    <div class="rounded-2xl glass-card p-6 space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-4">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-full bg-blue-500/20 text-blue-400 flex items-center justify-center font-bold text-base">
                                    {{ ticket.user?.name ? ticket.user.name.charAt(0).toUpperCase() : 'U' }}
                                </div>
                                <div>
                                    <p class="font-bold text-slate-900 dark:text-white">{{ ticket.user?.name }}</p>
                                    <p class="text-xs text-slate-400">Dibuat pada {{ new Date(ticket.created_at).toLocaleString('id-ID') }}</p>
                                </div>
                            </div>
                            <span
                                class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-medium ring-1 ring-inset"
                                :class="getCategoryBadge(ticket.category).bg"
                            >
                                <component :is="getCategoryBadge(ticket.category).icon" class="h-4 w-4" />
                                {{ getCategoryBadge(ticket.category).label }}
                            </span>
                        </div>

                        <div class="prose dark:prose-invert max-w-none text-slate-800 dark:text-slate-200 text-sm whitespace-pre-wrap leading-relaxed">
                            {{ ticket.description }}
                        </div>

                        <!-- Attachment link if any -->
                        <div v-if="ticket.attachment_path" class="pt-4 border-t border-slate-200 dark:border-slate-800">
                            <a
                                :href="`/storage/${ticket.attachment_path}`"
                                target="_blank"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-500/10 text-blue-400 hover:bg-blue-500/20 text-xs font-semibold transition-colors"
                            >
                                <PaperClipIcon class="h-4 w-4" />
                                lihat Lampiran Tiket
                            </a>
                        </div>
                    </div>

                    <!-- Replies Thread -->
                    <div class="space-y-4">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <ChatBubbleLeftRightIcon class="h-5 w-5 text-blue-500" />
                            Diskusi & Balasan ({{ ticket.replies.length }})
                        </h3>

                        <div
                            v-for="reply in ticket.replies"
                            :key="reply.id"
                            class="rounded-2xl p-5 border transition-all space-y-3"
                            :class="reply.is_internal ? 'bg-amber-500/5 border-amber-500/30' : 'glass-card border-slate-200/50 dark:border-slate-800/50'"
                        >
                            <div class="flex items-center justify-between border-b border-slate-200/40 dark:border-slate-800/40 pb-2.5">
                                <div class="flex items-center gap-2.5">
                                    <div class="h-8 w-8 rounded-full bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 flex items-center justify-center font-bold text-xs">
                                        {{ reply.user?.name ? reply.user.name.charAt(0).toUpperCase() : 'U' }}
                                    </div>
                                    <div>
                                        <span class="font-bold text-xs text-slate-900 dark:text-white">{{ reply.user?.name }}</span>
                                        <span v-if="reply.is_internal" class="ml-2 px-2 py-0.5 rounded text-[10px] bg-amber-500/20 text-amber-400 font-bold uppercase">Catatan Internal</span>
                                    </div>
                                </div>
                                <span class="text-[11px] text-slate-400">{{ new Date(reply.created_at).toLocaleString('id-ID') }}</span>
                            </div>
                            <p class="text-sm text-slate-800 dark:text-slate-200 whitespace-pre-wrap leading-relaxed">{{ reply.message }}</p>
                            <div v-if="reply.attachment_path" class="pt-2">
                                <a :href="`/storage/${reply.attachment_path}`" target="_blank" class="inline-flex items-center gap-1.5 text-xs text-blue-400 hover:underline">
                                    <PaperClipIcon class="h-3.5 w-3.5" /> Lihat Lampiran Balasan
                                </a>
                            </div>
                        </div>

                        <!-- Add Reply Form -->
                        <form @submit.prevent="submitReply" class="rounded-2xl glass-card p-5 space-y-4">
                            <h4 class="text-sm font-bold text-slate-900 dark:text-white">Tulis Balasan / Tanggapan</h4>
                            <textarea
                                v-model="replyForm.message"
                                rows="4"
                                placeholder="Tuliskan pesan balasan Anda..."
                                class="w-full rounded-xl border-0 bg-slate-100 dark:bg-slate-800/80 p-3 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                required
                            ></textarea>

                            <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                                <div class="flex items-center gap-3">
                                    <label class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-xs text-slate-700 dark:text-slate-300">
                                        <PaperClipIcon class="h-4 w-4 text-blue-500" />
                                        <span>Attachment</span>
                                        <input type="file" @change="handleFileChange" class="sr-only" />
                                    </label>
                                    <span v-if="replyForm.attachment" class="text-xs text-blue-400 truncate max-w-xs">📄 {{ replyForm.attachment.name }}</span>
                                </div>
                                <button
                                    type="submit"
                                    :disabled="replyForm.processing"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-sm font-semibold text-white transition-all shadow-lg shadow-blue-500/25 disabled:opacity-50"
                                >
                                    <PaperAirplaneIcon class="h-4 w-4" />
                                    {{ replyForm.processing ? 'Mengirim...' : 'Kirim Balasan' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right Sidebar: Controls & Info -->
                <div class="space-y-6">
                    <!-- Update Status & Assignment Card (for Admin/Dev) -->
                    <div class="rounded-2xl glass-card p-6 space-y-4 border border-blue-500/20">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider border-b border-slate-200 dark:border-slate-800 pb-3">
                            Kontrol Tiket (Admin/Dev)
                        </h3>
                        <form @submit.prevent="submitStatusUpdate" class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-2">Ubah Status Tiket</label>
                                <select
                                    v-model="statusForm.status"
                                    class="w-full rounded-xl border-0 bg-slate-100 dark:bg-slate-800 py-2.5 px-3 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                >
                                    <option value="open">Open</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="pending_user">Pending User</option>
                                    <option value="resolved">Resolved</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-2">Assign ke Programmer/IT</label>
                                <select
                                    v-model="statusForm.assigned_to"
                                    class="w-full rounded-xl border-0 bg-slate-100 dark:bg-slate-800 py-2.5 px-3 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                >
                                    <option value="">-- Belum Di-assign --</option>
                                    <option v-for="u in users" :key="u.id" :value="u.id">
                                        {{ u.name }}
                                    </option>
                                </select>
                            </div>
                            <button
                                type="submit"
                                :disabled="statusForm.processing"
                                class="w-full py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 dark:bg-slate-700 dark:hover:bg-slate-600 text-xs font-bold text-white transition-colors"
                            >
                                Update Kontrol Tiket
                            </button>
                        </form>
                    </div>

                    <!-- Ticket Metadata Sidebar Card -->
                    <div class="rounded-2xl glass-card p-6 space-y-4 text-xs text-slate-600 dark:text-slate-400">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider border-b border-slate-200 dark:border-slate-800 pb-3">
                            Informasi Tiket
                        </h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-slate-400">Nomor Tiket</span>
                                <span class="font-mono font-bold text-blue-400">{{ ticket.ticket_number }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400">Tingkat Urgensi</span>
                                <span class="font-semibold uppercase" :class="getPriorityBadge(ticket.priority).bg + ' px-1.5 py-0.5 rounded text-[10px]'">
                                    {{ ticket.priority }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400">Penanggung Jawab</span>
                                <span class="font-medium text-slate-900 dark:text-white">{{ ticket.assigned_to ? ticket.assigned_to.name : 'Belum Ditentukan' }}</span>
                            </div>
                            <div v-if="ticket.url" class="pt-2 border-t border-slate-200 dark:border-slate-800">
                                <span class="text-slate-400 block mb-1">Halaman Terkait:</span>
                                <a :href="ticket.url" target="_blank" class="text-blue-400 hover:underline break-all block text-[11px]" :title="ticket.url">
                                    {{ ticket.url }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
