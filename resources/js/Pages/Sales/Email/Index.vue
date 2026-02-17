<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { 
    InboxIcon, 
    ArrowPathIcon, 
    CheckCircleIcon, 
    ExclamationCircleIcon,
    TrashIcon,
    PaperClipIcon,
} from '@heroicons/vue/24/outline';
import { ref } from 'vue';

const props = defineProps({
    emails: Object,
    filters: Object,
});

const isSyncing = ref(false);

const syncEmails = () => {
    isSyncing.value = true;
    router.get(route('sales.emails.sync'), {}, {
        preserveScroll: true,
        onFinish: () => isSyncing.value = false
    });
};

const getIntentColor = (intent) => {
    const colors = {
        purchase_order: 'bg-green-100 text-green-700 border-green-200',
        request_quotation: 'bg-blue-100 text-blue-700 border-blue-200',
        complaint: 'bg-red-100 text-red-700 border-red-200',
        order_status: 'bg-purple-100 text-purple-700 border-purple-200',
        payment_info: 'bg-orange-100 text-orange-700 border-orange-200',
        general_inquiry: 'bg-gray-100 text-gray-700 border-gray-200',
    };
    return colors[intent] || 'bg-gray-50 text-gray-600 border-gray-100';
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <Head title="AI Email Inbox" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center gap-2">
                    <InboxIcon class="w-6 h-6 text-indigo-600" />
                    AI Email Inbox
                </h2>
                <button 
                    @click="syncEmails" 
                    :disabled="isSyncing"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
                >
                    <ArrowPathIcon :class="{'animate-spin': isSyncing}" class="w-4 h-4 mr-2" />
                    {{ isSyncing ? 'Syncing...' : 'Sync Inbox' }}
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-100">
                    <div class="p-6 text-gray-900 p-0">
                        <div class="divide-y divide-gray-100">
                            <!-- Email Item -->
                            <div v-for="email in emails.data" :key="email.id" 
                                 class="group hover:bg-indigo-50/30 transition-colors flex items-center p-4 cursor-pointer relative"
                                 :class="{'bg-blue-50/50 border-l-4 border-l-indigo-500': email.status === 'unread'}"
                                 @click="router.get(route('sales.emails.show', email.id))"
                            >
                                <!-- AI Urgency Indicator -->
                                <div v-if="email.urgency_score > 0.7" class="absolute left-0 top-0 bottom-0 w-1 bg-red-500 rounded-r shadow-[0_0_8px_rgba(239,68,68,0.5)]"></div>

                                <!-- Avatar / Sender -->
                                <div class="flex-shrink-0 mr-4">
                                    <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold">
                                        {{ (email.from_name || email.from_address).charAt(0).toUpperCase() }}
                                    </div>
                                </div>

                                <!-- Email Content Summary -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex justify-between items-center mb-1">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-gray-900 truncate max-w-[200px]" :class="{'text-indigo-900': email.status === 'unread'}">
                                                {{ email.from_name || email.from_address }}
                                            </span>
                                            <span v-if="email.customer" class="text-[10px] px-1.5 py-0.5 rounded bg-green-50 text-green-600 border border-green-100 uppercase tracking-tighter font-semibold">
                                                Linked
                                            </span>
                                        </div>
                                        <span class="text-xs text-gray-500">
                                            {{ formatDate(email.email_date) }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <h3 class="text-sm font-medium text-gray-900 truncate" :class="{'font-bold': email.status === 'unread'}">
                                            {{ email.subject || '(No Subject)' }}
                                        </h3>
                                        <div v-if="email.attachments.length" class="flex items-center text-gray-400 group-hover:text-indigo-400">
                                            <PaperClipIcon class="w-3 h-3" />
                                            <span class="text-[10px] ml-0.5">{{ email.attachments.length }}</span>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-500 truncate max-w-[600px]">
                                        {{ email.ai_metadata?.summary || email.body_text || '...' }}
                                    </p>
                                </div>

                                <!-- AI Status Badges -->
                                <div class="flex-shrink-0 ml-4 flex flex-col items-end gap-2">
                                    <span v-if="email.intent" 
                                          class="px-2 py-1 rounded-full text-[10px] font-bold border uppercase tracking-wider"
                                          :class="getIntentColor(email.intent)"
                                    >
                                        {{ email.intent.replace('_', ' ') }}
                                    </span>
                                    <div class="flex items-center gap-1">
                                        <CheckCircleIcon v-if="email.status === 'read'" class="w-4 h-4 text-gray-300" />
                                        <ExclamationCircleIcon v-else class="w-4 h-4 text-indigo-400 fill-indigo-50" />
                                    </div>
                                </div>
                            </div>

                            <!-- Empty State -->
                            <div v-if="emails.data.length === 0" class="py-20 text-center">
                                <InboxIcon class="mx-auto h-12 w-12 text-gray-300" />
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No emails found</h3>
                                <p class="mt-1 text-sm text-gray-500">Get started by syncing your inbox.</p>
                                <div class="mt-6">
                                    <button @click="syncEmails" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                                        <ArrowPathIcon class="-ml-1 mr-2 h-5 w-5" />
                                        Sync Now
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Pagination placeholder -->
                <div v-if="emails.links.length > 3" class="mt-6 flex justify-center">
                    <!-- Simple Laravel/Inertia Pagination list here -->
                </div>
            </div>
        </div>
    </AppLayout>
</template>
