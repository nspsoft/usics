<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router, usePage, useForm } from '@inertiajs/vue3';
import { 
    InboxIcon, 
    ArrowPathIcon, 
    CheckCircleIcon, 
    ExclamationCircleIcon,
    TrashIcon,
    PaperClipIcon,
    MagnifyingGlassIcon,
    FunnelIcon,
    ArchiveBoxIcon,
    ClockIcon,
    StarIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    PaperAirplaneIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline';
import { 
    StarIcon as StarIconSolid 
} from '@heroicons/vue/24/solid';
import { ref, computed, onMounted, watch } from 'vue';
import moment from 'moment';

const props = defineProps({
    emails: Object,
    filters: Object,
});

const page = usePage();
const isSyncing = ref(false);
const showComposeModal = ref(false);
const composeForm = useForm({
    to: '',
    subject: '',
    body: '',
    attachments: []
});
const selectedEmail = ref(null);
const searchQuery = ref(props.filters.search || '');
const activeFilter = ref('inbox'); // 'inbox', 'sent', 'done', 'trash', 'po', 'urgent'

// Initialize activeFilter based on props
if (props.filters.status) {
    activeFilter.value = props.filters.status === 'read' ? 'done' : props.filters.status;
} else if (props.filters.intent) {
    if (props.filters.intent === 'purchase_order') activeFilter.value = 'po';
    // 'urgent' would be handled by urgency score, not intent strictly
}

// Formatting Functions
const formatDate = (dateString) => {
    const date = moment(dateString);
    if (date.isSame(moment(), 'day')) {
        return date.format('HH:mm');
    } else if (date.isSame(moment().subtract(1, 'days'), 'day')) {
        return 'Yesterday';
    } else {
        return date.format('D MMM');
    }
};

const formatFullDate = (dateString) => {
    return moment(dateString).format('dddd, D MMMM YYYY • HH:mm');
};

const getInitials = (name) => {
    return (name || '?').charAt(0).toUpperCase();
};

const getIntentColor = (intent) => {
    const colors = {
        purchase_order: 'bg-emerald-100/90 text-emerald-700 border-emerald-200 ring-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20',
        request_quotation: 'bg-blue-100/90 text-blue-700 border-blue-200 ring-blue-500/30 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20',
        complaint: 'bg-rose-100/90 text-rose-700 border-rose-200 ring-rose-500/30 dark:bg-rose-500/10 dark:text-rose-400 dark:border-rose-500/20',
        order_status: 'bg-violet-100/90 text-violet-700 border-violet-200 ring-violet-500/30 dark:bg-violet-500/10 dark:text-violet-400 dark:border-violet-500/20',
        payment_info: 'bg-amber-100/90 text-amber-700 border-amber-200 ring-amber-500/30 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20',
        general_inquiry: 'bg-slate-100/90 text-slate-700 border-slate-200 ring-slate-500/30 dark:bg-slate-700/50 dark:text-slate-200 dark:border-slate-600/30',
    };
    return colors[intent] || 'bg-gray-50 text-gray-600 border-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700';
};

const getIntentLabel = (intent) => {
    return (intent || 'General').replace(/_/g, ' ');
};

// Actions
const syncEmails = () => {
    isSyncing.value = true;
    router.visit(route('sales.emails.sync'), {
        method: 'get',
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            isSyncing.value = false;
            // Refresh list if needed
            router.reload({ only: ['emails'] });
        }
    });
};

const selectEmail = (email) => {
    selectedEmail.value = email;
    if (email.status === 'unread') {
        // Optimistic update
        email.status = 'read';
        // In a real app, you'd send a request to mark as read here
        // router.post(route('sales.emails.read', email.id), {}, { preserveScroll: true });
    }
};

const deleteEmail = (email) => {
    if (!confirm('Are you sure you want to delete this email?')) return;
    router.delete(route('sales.emails.destroy', email.id), {
        onSuccess: () => {
            selectedEmail.value = null;
        }
    });
};

const performSearch = (filter = null) => {
    if (filter) activeFilter.value = filter;
    
    router.visit(route('sales.emails.index'), {
        data: { 
            search: searchQuery.value,
            intent: ['po', 'urgent'].includes(activeFilter.value) ? (activeFilter.value === 'po' ? 'purchase_order' : null) : null,
            status: ['sent', 'trash'].includes(activeFilter.value) ? activeFilter.value : (activeFilter.value === 'done' ? 'read' : null)
        },
        preserveState: true, 
        preserveScroll: true,
        replace: true 
    });
};

// Auto-select first email on load if desktop
onMounted(() => {
    if (window.innerWidth >= 1024 && props.emails.data.length > 0) {
        // selectedEmail.value = props.emails.data[0];
    }
});

const openCompose = () => {
    showComposeModal.value = true;
};

const closeCompose = () => {
    showComposeModal.value = false;
    composeForm.reset();
};

const sendEmail = () => {
    composeForm.post(route('sales.emails.store'), {
        onSuccess: () => {
            closeCompose();
            // Optional: Show success notification or refresh list
        }
    });
};

const handleAttachmentUpload = (e) => {
    const files = Array.from(e.target.files);
    composeForm.attachments = [...composeForm.attachments, ...files];
};

const removeAttachment = (index) => {
    composeForm.attachments.splice(index, 1);
};

</script>

<template>
    <Head title="AI Inbox" />

    <AppLayout title="AI Inbox" :showHeading="false">
        <div class="flex h-[calc(100vh-65px)] bg-white dark:bg-gray-900 overflow-hidden">
            
            <!-- LEFT SIDEBAR (Folder/Nav) -->
            <div class="w-64 flex-shrink-0 bg-slate-50 dark:bg-gray-900 border-r border-slate-200 dark:border-gray-800 flex flex-col hidden md:flex">
                <div class="p-4 space-y-3">
                    <button 
                        @click="openCompose" 
                        class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-slate-900 dark:bg-white hover:bg-slate-800 dark:hover:bg-slate-200 text-white dark:text-slate-900 rounded-xl font-bold shadow-lg shadow-slate-900/20 transition-all active:scale-95"
                    >
                        <PaperAirplaneIcon class="w-5 h-5 -rotate-45 mt-0.5" />
                        <span>Compose</span>
                    </button>

                    <button 
                        @click="syncEmails" 
                        :disabled="isSyncing"
                        class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold shadow-lg shadow-indigo-500/30 transition-all active:scale-95 disabled:opacity-70 disabled:active:scale-100"
                    >
                        <ArrowPathIcon :class="{'animate-spin': isSyncing}" class="w-5 h-5" />
                        <span>{{ isSyncing ? 'Syncing...' : 'Sync Inbox' }}</span>
                    </button>
                </div>

                <nav class="flex-1 overflow-y-auto px-2 space-y-1">
                    <div class="px-3 py-2 text-xs font-bold text-slate-400 uppercase tracking-wider">Folders</div>
                    
                    <button @click="performSearch('inbox')" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg font-medium group transition-colors text-left" :class="activeFilter === 'inbox' ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100'">
                        <InboxIcon class="w-5 h-5" :class="activeFilter === 'inbox' ? 'text-indigo-600' : 'text-slate-400'" />
                        <span class="flex-1">Inbox</span>
                        <span v-if="emails.total > 0 && activeFilter === 'inbox'" class="text-xs font-bold bg-white text-indigo-600 px-2 py-0.5 rounded-full shadow-sm">{{ emails.total }}</span>
                    </button>
                    
                    <button @click="performSearch('sent')" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg font-medium group transition-colors text-left" :class="activeFilter === 'sent' ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100'">
                        <PaperAirplaneIcon class="w-5 h-5 -rotate-45" :class="activeFilter === 'sent' ? 'text-indigo-600' : 'text-slate-400'" />
                        <span>Sent</span>
                    </button>

                    <button @click="performSearch('done')" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg font-medium group transition-colors text-left" :class="activeFilter === 'done' ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100'">
                        <CheckCircleIcon class="w-5 h-5" :class="activeFilter === 'done' ? 'text-indigo-600' : 'text-slate-400'" />
                        <span>Done</span>
                    </button>
                    <button @click="performSearch('trash')" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg font-medium group transition-colors text-left" :class="activeFilter === 'trash' ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100'">
                        <TrashIcon class="w-5 h-5" :class="activeFilter === 'trash' ? 'text-indigo-600' : 'text-slate-400'" />
                        <span>Trash</span>
                    </button>

                    <div class="mt-6 px-3 py-2 text-xs font-bold text-slate-400 uppercase tracking-wider">Smart Filters</div>
                    
                    <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-600 hover:bg-slate-100 font-medium transition-colors">
                        <ExclamationCircleIcon class="w-5 h-5 text-rose-500" />
                        <span>High Urgency</span>
                        <span class="ml-auto w-2 h-2 rounded-full bg-rose-500"></span>
                    </a>
                    <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-600 hover:bg-slate-100 font-medium transition-colors">
                        <ArchiveBoxIcon class="w-5 h-5 text-emerald-500" />
                        <span>Purchase Orders</span>
                    </a>
                </nav>

                <div class="p-4 border-t border-slate-200">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                        <span class="text-xs font-medium text-slate-500">IMAP Connected</span>
                    </div>
                    <div class="text-[10px] text-slate-400 mt-1 truncate">
                        {{ $page.props.auth.user.email }}
                    </div>
                </div>
            </div>

            <!-- MIDDLE PANE (Email List) -->
            <div class="w-full md:w-96 flex-shrink-0 border-r border-slate-200 dark:border-gray-800 flex flex-col bg-white dark:bg-gray-900 z-10">
                <!-- Search Header -->
                <div class="p-4 border-b border-slate-100 dark:border-gray-800">
                    <div class="relative">
                        <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 dark:text-slate-500" />
                        <input 
                            v-model="searchQuery"
                            @keydown.enter="performSearch"
                            type="text" 
                            placeholder="Search emails..." 
                            class="w-full pl-9 pr-4 py-2 bg-slate-50 dark:bg-gray-800 border-none rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 text-slate-700 dark:text-slate-200 placeholder:text-slate-400 dark:placeholder:text-slate-500"
                        >
                    </div>
                </div>

                <!-- List Content -->
                <div class="flex-1 overflow-y-auto">
                    <div v-if="emails.data.length === 0" class="p-8 text-center text-slate-500">
                        <InboxIcon class="w-12 h-12 mx-auto text-slate-300 mb-3" />
                        <p class="text-sm">No emails found.</p>
                    </div>

                    <div 
                        v-for="email in emails.data" 
                        :key="email.id"
                        @click="selectEmail(email)"
                        class="p-4 border-b border-slate-50 dark:border-gray-800 cursor-pointer transition-all hover:bg-slate-50 dark:hover:bg-gray-800 relative group"
                        :class="{
                            'bg-indigo-50/40 dark:bg-indigo-900/20 hover:bg-indigo-50/60 dark:hover:bg-indigo-900/30': selectedEmail?.id === email.id,
                            'bg-white dark:bg-gray-900': selectedEmail?.id !== email.id
                        }"
                    >
                        <!-- Unread Indicator Line -->
                        <div v-if="email.status === 'unread'" class="absolute left-0 top-0 bottom-0 w-1 bg-indigo-500"></div>

                        <div class="flex justify-between items-start mb-1">
                            <div class="flex items-center gap-2 overflow-hidden">
                                <span 
                                    class="font-semibold text-sm truncate"
                                    :class="email.status === 'unread' ? 'text-slate-900' : 'text-slate-600'"
                                >
                                    {{ email.from_name || email.from_address }}
                                </span>
                            </div>
                            <span class="text-[10px] text-slate-400 flex-shrink-0 whitespace-nowrap ml-2">
                                {{ formatDate(email.email_date) }}
                            </span>
                        </div>

                        <div class="mb-1">
                            <h4 
                                class="text-xs truncate mb-1"
                                :class="email.status === 'unread' ? 'font-bold text-slate-800' : 'font-medium text-slate-500'"
                            >
                                {{ email.subject || '(No Subject)' }}
                            </h4>
                            <p class="text-[11px] text-slate-400 line-clamp-2 leading-relaxed">
                                {{ email.ai_metadata?.summary || email.body_text || '...' }}
                            </p>
                        </div>

                        <!-- Tags / Badges -->
                        <div class="flex items-center gap-2 mt-2">
                            <span 
                                v-if="email.intent" 
                                class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide border shadow-sm"
                                :class="getIntentColor(email.intent)"
                            >
                                {{ getIntentLabel(email.intent) }}
                            </span>
                            
                            <PaperClipIcon v-if="email.attachments.length" class="w-3 h-3 text-slate-400" />
                            
                            <span 
                                v-if="email.customer" 
                                class="text-[10px] text-green-600 font-semibold flex items-center gap-1 bg-green-50 px-1.5 py-0.5 rounded ml-auto"
                            >
                                <CheckCircleIcon class="w-3 h-3" />
                                Linked
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT PANE (Reading View) -->
            <div class="flex-1 bg-white dark:bg-gray-900 flex flex-col h-full overflow-hidden relative">
                <div v-if="selectedEmail" class="flex flex-col h-full">
                    <!-- Email Header -->
                    <div class="p-6 border-b border-slate-100 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 backdrop-blur-md sticky top-0 z-20">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h1 class="text-xl font-bold text-slate-900 dark:text-white leading-snug mb-2">
                                    {{ selectedEmail.subject }}
                                </h1>
                                <div class="flex items-center gap-2">
                                    <span 
                                        class="px-2.5 py-1 rounded-md text-xs font-bold border shadow-sm uppercase tracking-wide"
                                        :class="getIntentColor(selectedEmail.intent)"
                                    >
                                        {{ getIntentLabel(selectedEmail.intent) }}
                                    </span>
                                    <span v-if="selectedEmail.urgency_score > 0.7" class="px-2.5 py-1 rounded-md text-xs font-bold bg-rose-100 text-rose-700 border border-rose-200">
                                        High Urgency
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button class="p-2 text-slate-400 hover:text-indigo-600 transition-colors rounded-full hover:bg-slate-100">
                                    <StarIcon class="w-5 h-5" />
                                </button>
                                <button @click="deleteEmail(selectedEmail)" class="p-2 text-slate-400 hover:text-rose-600 transition-colors rounded-full hover:bg-rose-50">
                                    <TrashIcon class="w-5 h-5" />
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 text-white flex items-center justify-center font-bold text-lg shadow-md">
                                    {{ getInitials(selectedEmail.from_name || selectedEmail.from_address) }}
                                </div>
                                <div>
                                    <div class="font-bold text-slate-900 text-sm">
                                        {{ selectedEmail.from_name || selectedEmail.from_address }}
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        {{ selectedEmail.from_address }}
                                    </div>
                                </div>
                            </div>
                            <div class="text-xs text-slate-400 text-right">
                                <div>{{ formatFullDate(selectedEmail.email_date) }}</div>
                                <div v-if="selectedEmail.to_address" class="mt-0.5">To: {{ selectedEmail.to_address }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- AI Insight Card -->
                    <div class="px-6 pt-6 pb-2" v-if="selectedEmail.ai_metadata">
                        <div class="bg-gradient-to-br from-indigo-50 to-blue-50 dark:from-slate-800 dark:to-slate-800 border border-indigo-100 dark:border-slate-700 rounded-xl p-4 relative overflow-hidden">
                            <div class="absolute top-0 right-0 p-3 opacity-10">
                                <SparklesIcon class="w-24 h-24 text-indigo-600" />
                            </div>
                            <div class="relative z-10">
                                <h3 class="text-xs font-black text-indigo-400 uppercase tracking-widest mb-2 flex items-center gap-2">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                    AI Insight
                                </h3>
                                <p class="text-sm text-slate-700 leading-relaxed font-medium">
                                    {{ selectedEmail.ai_metadata.summary || 'AI has analyzed this email but provided no summary.' }}
                                </p>
                                <div class="mt-3 flex gap-4 text-xs">
                                    <div class="flex flex-col">
                                        <span class="text-slate-400 uppercase tracking-wider text-[10px] font-bold">Sentiment</span>
                                        <span class="font-bold capitalize" :class="{
                                            'text-green-600': selectedEmail.sentiment === 'positive',
                                            'text-rose-600': selectedEmail.sentiment === 'negative',
                                            'text-slate-600': selectedEmail.sentiment === 'neutral'
                                        }">{{ selectedEmail.sentiment }}</span>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-slate-400 uppercase tracking-wider text-[10px] font-bold">Action Required</span>
                                        <span class="font-bold text-slate-700">{{ selectedEmail.urgency_score > 0.5 ? 'Yes' : 'No' }}</span>
                                    </div>
                                    
                                    <!-- Extracted PO Data -->
                                    <div v-if="selectedEmail.ai_metadata.extracted_po" class="flex flex-col">
                                        <span class="text-slate-400 uppercase tracking-wider text-[10px] font-bold">PO Extracted</span>
                                        <Link :href="route('sales.po-extractor')" class="font-bold text-indigo-600 hover:underline">
                                            View Data &rarr;
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Email Body -->
                    <div class="flex-1 overflow-y-auto p-6">
                        <div 
                            class="prose prose-sm dark:prose-invert max-w-none text-slate-800 dark:text-slate-300 prose-headings:font-bold prose-a:text-indigo-600 prose-img:rounded-lg"
                            v-html="selectedEmail.body_html || selectedEmail.body_text.replace(/\n/g, '<br>')"
                        ></div>

                        <!-- Attachments -->
                        <div v-if="selectedEmail.attachments.length > 0" class="mt-8 pt-6 border-t border-slate-100">
                            <h4 class="text-sm font-bold text-slate-900 mb-3 flex items-center gap-2">
                                <PaperClipIcon class="w-4 h-4 text-slate-400" />
                                Attachments ({{ selectedEmail.attachments.length }})
                            </h4>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                <a 
                                    v-for="att in selectedEmail.attachments" 
                                    :key="att.id"
                                    :href="`/storage/${att.file_path}`" 
                                    target="_blank"
                                    class="group p-3 border border-slate-200 rounded-xl hover:border-indigo-300 hover:bg-indigo-50 transition-all flex items-center gap-3"
                                >
                                    <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center text-slate-500 font-bold text-xs uppercase group-hover:bg-white">
                                        {{ att.file_name.split('.').pop() }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-xs font-bold text-slate-700 truncate group-hover:text-indigo-700">{{ att.file_name }}</div>
                                        <div class="text-[10px] text-slate-400">{{ (att.size / 1024).toFixed(1) }} KB</div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State (No Selection) -->
                <div v-else class="flex-1 flex flex-col items-center justify-center text-slate-300 dark:text-slate-600 bg-slate-50/50 dark:bg-gray-900/50">
                    <InboxIcon class="w-24 h-24 stroke-1 opacity-20" />
                    <p class="mt-4 text-sm font-medium text-slate-400">Select an email to read</p>
                </div>
            </div>

        </div>
    <!-- Compose Modal -->
    <div v-if="showComposeModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
        <div class="bg-white dark:bg-slate-900 w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/50">
                <h3 class="font-bold text-lg text-slate-800 dark:text-white">New Message</h3>
                <button @click="closeCompose" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                    <XMarkIcon class="w-6 h-6" />
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6 overflow-y-auto space-y-4">
                <div>
                    <input 
                        v-model="composeForm.to"
                        type="email" 
                        placeholder="To: recipient@example.com" 
                        class="w-full px-0 py-2 border-b border-slate-200 dark:border-slate-700 bg-transparent focus:ring-0 focus:border-indigo-500 font-medium text-slate-900 dark:text-white placeholder:text-slate-400"
                    />
                    <div v-if="composeForm.errors.to" class="text-xs text-rose-500 mt-1">{{ composeForm.errors.to }}</div>
                </div>

                <div>
                    <input 
                        v-model="composeForm.subject"
                        type="text" 
                        placeholder="Subject" 
                        class="w-full px-0 py-2 border-b border-slate-200 dark:border-slate-700 bg-transparent focus:ring-0 focus:border-indigo-500 font-bold text-lg text-slate-900 dark:text-white placeholder:text-slate-400"
                    />
                    <div v-if="composeForm.errors.subject" class="text-xs text-rose-500 mt-1">{{ composeForm.errors.subject }}</div>
                </div>

                <div>
                    <textarea 
                        v-model="composeForm.body"
                        rows="8" 
                        placeholder="Write your message here..." 
                        class="w-full px-0 py-2 border-none bg-transparent focus:ring-0 resize-none text-slate-700 dark:text-slate-300 placeholder:text-slate-400 leading-relaxed"
                    ></textarea>
                    <div v-if="composeForm.errors.body" class="text-xs text-rose-500 mt-1">{{ composeForm.errors.body }}</div>
                </div>

                <!-- Attachments List -->
                <div v-if="composeForm.attachments.length > 0" class="flex flex-wrap gap-2">
                    <div v-for="(file, index) in composeForm.attachments" :key="index" class="flex items-center gap-2 px-3 py-1.5 bg-slate-100 dark:bg-slate-800 rounded-lg text-xs font-medium text-slate-700 dark:text-slate-300">
                        <span class="truncate max-w-[150px]">{{ file.name }}</span>
                        <button @click="removeAttachment(index)" class="text-slate-400 hover:text-rose-500">
                            <XMarkIcon class="w-3 h-3" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/50 flex justify-between items-center">
                <div class="relative">
                    <input 
                        type="file" 
                        multiple 
                        @change="handleAttachmentUpload" 
                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                    />
                    <button type="button" class="text-slate-500 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400 transition-colors">
                        <PaperClipIcon class="w-6 h-6" />
                    </button>
                </div>

                <div class="flex items-center gap-3">
                    <button @click="closeCompose" type="button" class="px-4 py-2 text-sm font-bold text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-white transition-colors">
                        Discard
                    </button>
                    <button 
                        @click="sendEmail" 
                        :disabled="composeForm.processing"
                        class="flex items-center gap-2 px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-bold shadow-lg shadow-indigo-500/30 transition-all active:scale-95 disabled:opacity-70"
                    >
                        <span v-if="composeForm.processing">Sending...</span>
                        <span v-else>Send Message</span>
                        <PaperAirplaneIcon v-if="!composeForm.processing" class="w-4 h-4 -rotate-45 mt-0.5" />
                    </button>
                </div>
            </div>
        </div>
    </div>
    </AppLayout>
</template>

<style scoped>
/* Custom Scrollbar for sleek look */
::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
::-webkit-scrollbar-track {
    background: transparent;
}
::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 3px;
}
::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1;
}

/* Dark Mode Scrollbar */
:is(.dark) ::-webkit-scrollbar-thumb {
    background: #475569;
}
:is(.dark) ::-webkit-scrollbar-thumb:hover {
    background: #64748b;
}
</style>


