<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { 
    ChevronLeftIcon,
    CalendarIcon,
    UserIcon,
    TagIcon,
    ChatBubbleBottomCenterTextIcon,
    SparklesIcon,
    DocumentChartBarIcon,
    TrashIcon,
    ArrowDownTrayIcon,
    PaperClipIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    email: Object,
});

const stripHtml = (html) => {
    if (!html) return '';
    return String(html)
        .replace(/<script[\s\S]*?>[\s\S]*?<\/script>/gi, ' ')
        .replace(/<style[\s\S]*?>[\s\S]*?<\/style>/gi, ' ')
        .replace(/<[^>]+>/g, ' ')
        .replace(/&nbsp;/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
};

const emailBody = () => {
    return props.email.body_text || stripHtml(props.email.body_html) || '';
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleString('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getSentimentColor = (sentiment) => {
    const colors = {
        positive: 'text-green-600 bg-green-50',
        frustrated: 'text-red-600 bg-red-50',
        urgent: 'text-orange-600 bg-orange-50',
        neutral: 'text-gray-600 bg-gray-50',
    };
    return colors[sentiment] || 'text-gray-600 bg-gray-50';
};

const deleteEmail = () => {
    if (confirm('Are you sure you want to delete this email?')) {
        router.delete(route('sales.emails.destroy', props.email.id));
    }
};

const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text);
    alert('Copy to clipboard!');
};
</script>

<template>
    <Head :title="email.subject || 'Email Detail'" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <Link :href="route('sales.emails.index')" class="p-2 hover:bg-gray-100 rounded-full transition-colors">
                        <ChevronLeftIcon class="w-5 h-5 text-gray-600" />
                    </Link>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Email Detail
                    </h2>
                </div>
                <div class="flex gap-2">
                    <button @click="deleteEmail" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete Email">
                        <TrashIcon class="w-5 h-5" />
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 flex flex-col lg:flex-row gap-6">
                
                <!-- Main Content (Left) -->
                <div class="flex-1 space-y-6">
                    <!-- Email Content Card -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                        <div class="p-8">
                            <h1 class="text-2xl font-bold text-gray-900 mb-6 border-b pb-4">
                                {{ email.subject || '(No Subject)' }}
                            </h1>

                            <div class="flex flex-wrap gap-6 mb-8 text-sm text-gray-600">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold">
                                        {{ (email.from_name || email.from_address).charAt(0).toUpperCase() }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-900">{{ email.from_name || 'Sender' }}</p>
                                        <p class="text-xs">{{ email.from_address }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <CalendarIcon class="w-4 h-4 text-gray-400" />
                                    <span>{{ formatDate(email.email_date) }}</span>
                                </div>
                            </div>

                            <div class="whitespace-pre-wrap text-gray-800 border-t pt-6 font-sans">
                                {{ emailBody() }}
                            </div>

                            <!-- Attachments Area -->
                            <div v-if="email.attachments.length" class="mt-12 pt-8 border-t">
                                <h3 class="text-sm font-bold text-gray-900 mb-4 flex items-center gap-2">
                                    <DocumentChartBarIcon class="w-5 h-5 text-indigo-500" />
                                    Attachments ({{ email.attachments.length }})
                                </h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                                    <div v-for="att in email.attachments" :key="att.id" 
                                         class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-100 group hover:border-indigo-200 transition-colors"
                                    >
                                        <div class="flex items-center gap-3 truncate">
                                            <div class="w-10 h-10 bg-white rounded flex items-center justify-center border group-hover:bg-indigo-50">
                                                <PaperClipIcon class="w-5 h-5 text-gray-400" />
                                            </div>
                                            <div class="truncate">
                                                <p class="font-medium text-gray-900 truncate">{{ att.file_name }}</p>
                                                <p class="text-gray-500">{{ (att.size / 1024).toFixed(1) }} KB</p>
                                            </div>
                                        </div>
                                        <a :href="'/storage/' + att.file_path" download class="p-2 text-indigo-600 hover:bg-white rounded-full transition-shadow">
                                            <ArrowDownTrayIcon class="w-4 h-4" />
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- AI Insights Panel (Right) -->
                <div class="w-full lg:w-80 space-y-6">
                    <!-- Intelligence Card -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-indigo-100 ring-1 ring-indigo-50">
                        <div class="p-6">
                            <h3 class="text-xs font-bold text-indigo-600 uppercase tracking-widest flex items-center gap-2 mb-6">
                                <SparklesIcon class="w-4 h-4" />
                                AI Insights
                            </h3>

                            <div class="space-y-6">
                                <!-- Intent -->
                                <div>
                                    <p class="text-[10px] font-semibold text-gray-400 uppercase mb-2">Primary Intent</p>
                                    <div class="inline-flex items-center px-3 py-1 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-100 text-xs font-bold capitalize">
                                        {{ email.intent?.replace('_', ' ') || 'Analyzing...' }}
                                    </div>
                                </div>

                                <!-- Sentiment & Urgency -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-[10px] font-semibold text-gray-400 uppercase mb-2">Sentiment</p>
                                        <span class="text-xs font-medium px-2 py-1 rounded capitalize" :class="getSentimentColor(email.sentiment)">
                                            {{ email.sentiment }}
                                        </span>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-semibold text-gray-400 uppercase mb-2">Urgency</p>
                                        <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden mt-2">
                                            <div class="h-full rounded-full transition-all duration-1000" 
                                                 :class="email.urgency_score > 0.7 ? 'bg-red-500' : 'bg-indigo-500'"
                                                 :style="{ width: (email.urgency_score * 100) + '%' }">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Summary -->
                                <div>
                                    <p class="text-[10px] font-semibold text-gray-400 uppercase mb-2">AI Summary</p>
                                    <p class="text-sm text-gray-700 leading-relaxed italic">
                                        "{{ email.ai_metadata?.summary || 'No summary generated.' }}"
                                    </p>
                                </div>

                                <!-- Smart Reply -->
                                <div class="pt-6 border-t border-gray-100">
                                    <p class="text-[10px] font-semibold text-indigo-400 uppercase mb-3 flex items-center gap-1">
                                        <ChatBubbleBottomCenterTextIcon class="w-3 h-3" />
                                        Suggested Reply (Draft)
                                    </p>
                                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-100 relative group">
                                        <p class="text-xs text-gray-600 leading-relaxed min-h-[100px]">
                                            {{ email.ai_metadata?.suggest_reply || 'Generating draft...' }}
                                        </p>
                                        <button 
                                            @click="copyToClipboard(email.ai_metadata?.suggest_reply)"
                                            class="absolute top-2 right-2 p-1.5 bg-white shadow-sm border rounded-md text-indigo-600 opacity-0 group-hover:opacity-100 transition-opacity"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" />
                                                <path d="M6 3a2 2 0 00-2 2v11a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2 3 3 0 01-3 3H9a3 3 0 01-3-3z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Action Button if PO -->
                                <div v-if="email.intent === 'purchase_order' || email.ai_metadata?.extracted_po" class="pt-4">
                                    <button class="w-full flex items-center justify-center gap-2 bg-indigo-600 text-white text-xs font-bold py-3 rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
                                        <DocumentChartBarIcon class="w-4 h-4" />
                                        PROSES SEBAGAI SO
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Profile Card -->
                    <div v-if="email.customer" class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                        <div class="p-6">
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest flex items-center gap-2 mb-4">
                                <UserIcon class="w-4 h-4" />
                                Customer Info
                            </h3>
                            <Link :href="route('sales.customers.show', email.customer_id)" class="block group">
                                <p class="font-bold text-gray-900 group-hover:text-indigo-600">{{ email.customer.name }}</p>
                                <p class="text-xs text-gray-500 mt-1">Lihat Profil Customer &rarr;</p>
                            </Link>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
