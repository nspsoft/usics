<script setup>
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, nextTick, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    PaperAirplaneIcon, 
    MagnifyingGlassIcon,
    ChatBubbleLeftRightIcon,
    UserCircleIcon,
    CheckCircleIcon,
    ClockIcon,
    ArrowPathIcon,
    BookOpenIcon,
    ArrowTopRightOnSquareIcon,
    TrashIcon,
    PaperClipIcon,
    XMarkIcon,
    DocumentIcon,
    ArrowDownTrayIcon
} from '@heroicons/vue/24/outline';
import axios from 'axios';

const props = defineProps({
    contacts: Array,
});

const search = ref('');
const activeContact = ref(null);
const messages = ref([]);
const isLoadingMessages = ref(false);
const chatContainer = ref(null);

const form = useForm({
    phone: '',
    message: '',
    file: null,
    _token: usePage().props.csrf_token,
});

const fileInput = ref(null);
const filePreview = ref(null);

const triggerFileSelect = () => {
    fileInput.value.click();
};

const handleFileSelect = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    form.file = file;
    
    // Create preview if it's an image
    if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = (e) => {
            filePreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    } else {
        filePreview.value = 'document'; // Placeholder for non-image files
    }
};

const clearFile = () => {
    form.file = null;
    filePreview.value = null;
    if (fileInput.value) fileInput.value.value = '';
};

// Filter contacts
const filteredContacts = computed(() => {
    if (!search.value) return props.contacts;
    return props.contacts.filter(c => 
        c.phone.includes(search.value) || 
        (c.customer?.name || '').toLowerCase().includes(search.value.toLowerCase()) ||
        (c.customer?.contact_person || '').toLowerCase().includes(search.value.toLowerCase())
    );
});

// Select contact & load history
const selectContact = async (contact) => {
    activeContact.value = contact;
    form.phone = contact.phone;
    fetchMessages(contact.phone);
};

const fetchMessages = async (phone) => {
    isLoadingMessages.value = true;
    try {
        const response = await axios.get(route('sales.whatsapp.history', phone));
        messages.value = response.data;
        scrollToBottom();
    } catch (error) {
        console.error('Failed to load history', error);
    } finally {
        isLoadingMessages.value = false;
    }
};

const sendMessage = () => {
    if (!activeContact.value) return;
    if (!form.message.trim() && !form.file) return;
    
    form.phone = activeContact.value.phone;
    form.post(route('sales.whatsapp.send'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            // Optimistic update
            messages.value.push({
                direction: 'outgoing',
                message: form.message || (form.file ? `[File: ${form.file.name}]` : ''),
                created_at: new Date().toISOString(),
                intent: 'manual_reply',
                metadata: form.file ? {
                    type: form.file.type.startsWith('image/') ? 'image' : 'document',
                    name: form.file.name,
                    url: filePreview.value !== 'document' ? filePreview.value : null, // Temp preview URL
                    size: form.file.size
                } : null
            });
            form.message = '';
            clearFile();
            scrollToBottom();
            
            // Re-fetch to get actual server URLs
            setTimeout(() => {
                fetchMessages(activeContact.value.phone);
            }, 1000);
        }
    });
};

const scrollToBottom = async () => {
    await nextTick();
    if (chatContainer.value) {
        chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
    }
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        day: '2-digit',
        month: 'short'
    }).format(date);
};

// Auto refresh history every 10s if active
let pollingInterval;
onMounted(() => {
    pollingInterval = setInterval(() => {
        if (activeContact.value) {
            axios.get(route('sales.whatsapp.history', activeContact.value.phone))
                .then(res => {
                    // Simple check if new messages
                    if (res.data.length > messages.value.length) {
                        messages.value = res.data;
                        scrollToBottom();
                    }
                });
        }
    }, 10000);
});

// Delete history
const confirmDeleteHistory = () => {
    if (!activeContact.value) return;
    
    if (confirm('Are you sure you want to delete this chat history? This action cannot be undone.')) {
        router.delete(route('sales.whatsapp.destroy', activeContact.value.phone), {
            onSuccess: () => {
                messages.value = [];
                // Optional: You might want to remove the contact from the list or clear the active contact
                // activeContact.value = null; // Uncomment if you want to deselect
            }
        });
    }
};

</script>

<template>
    <Head title="WhatsApp Center" />

    <AppLayout title="WhatsApp Center">
        <div class="h-[calc(100vh-12rem)] w-full flex gap-6 overflow-hidden">
            
            <!-- Chat List (Left) -->
            <div class="w-1/3 flex flex-col gap-4">
                <!-- Search -->
                <div class="relative">
                    <input 
                        v-model="search"
                        type="text" 
                        placeholder="Search chats..." 
                        class="w-full bg-white dark:bg-slate-900/50 border border-slate-300 dark:border-slate-700 rounded-xl py-3 pl-10 pr-4 text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:ring-2 focus:ring-cyan-500/50 font-sans"
                    />
                    <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400 dark:text-slate-500" />
                </div>

                <!-- Guide Link -->
                 <a href="/whatsapp_guide.html" target="_blank" class="flex items-center justify-between px-4 py-3 bg-gradient-to-r from-cyan-600 to-blue-600 rounded-xl text-white shadow-lg shadow-cyan-500/20 hover:shadow-cyan-500/40 transition-all group">
                    <div class="flex items-center gap-3">
                        <div class="p-1.5 bg-white/20 rounded-lg backdrop-blur-sm">
                            <BookOpenIcon class="h-5 w-5 text-white" />
                        </div>
                        <div class="text-left">
                            <div class="text-xs font-bold opacity-90">Butuh Bantuan?</div>
                            <div class="text-sm font-bold">Buka Panduan & Demo</div>
                        </div>
                    </div>
                    <ArrowTopRightOnSquareIcon class="h-5 w-5 opacity-70 group-hover:opacity-100 transition-opacity" />
                </a>

                <!-- Contact List -->
                <div class="flex-1 overflow-y-auto pr-2 space-y-2 custom-scrollbar">
                    <button 
                        v-for="contact in filteredContacts" 
                        :key="contact.phone"
                        @click="selectContact(contact)"
                        class="w-full text-left p-4 rounded-2xl border transition-all duration-200 group relative overflow-hidden"
                        :class="activeContact?.phone === contact.phone 
                            ? 'bg-cyan-50 dark:bg-cyan-500/10 border-cyan-500 dark:border-cyan-500/50 shadow-lg dark:shadow-[0_0_20px_rgba(6,182,212,0.15)]' 
                            : 'bg-white dark:bg-slate-900/40 border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-800/40'"
                    >
                        <!-- Glow Effect -->
                        <div v-if="activeContact?.phone === contact.phone" class="absolute inset-0 bg-gradient-to-r from-cyan-500/10 to-transparent opacity-50"></div>

                        <div class="relative flex items-center gap-3 mb-1">
                            <!-- Avatar -->
                            <div class="flex-shrink-0">
                                <img v-if="contact.customer?.profile_photo_url" :src="contact.customer.profile_photo_url" class="h-10 w-10 rounded-full object-cover border border-slate-200 dark:border-slate-700 shadow-sm" />
                                <div v-else class="h-10 w-10 rounded-full bg-gradient-to-br from-slate-200 to-slate-300 dark:from-slate-700 dark:to-slate-800 flex items-center justify-center text-slate-500 dark:text-slate-400 font-bold text-xs shadow-inner">
                                    {{ (contact.customer?.name || contact.phone).charAt(0) }}
                                </div>
                            </div>
                            
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-start">
                                    <h3 class="font-bold text-slate-800 dark:text-slate-200 group-hover:text-slate-900 dark:group-hover:text-white transition-colors truncate">
                                        {{ contact.customer?.name || contact.phone }}
                                    </h3>
                                    <span class="text-[10px] text-slate-500 font-mono mt-1 whitespace-nowrap ml-2">
                                        {{ formatDate(contact.last_activity) }}
                                    </span>
                                </div>
                                <div v-if="contact.customer?.contact_person" class="text-[10px] font-bold text-cyan-600 dark:text-cyan-400 uppercase tracking-widest truncate">
                                    {{ contact.customer.contact_person }}
                                </div>
                            </div>
                        </div>
                        
                        <p class="text-sm text-slate-600 dark:text-slate-400 line-clamp-1 group-hover:text-cyan-700 dark:group-hover:text-cyan-200/70 transition-colors">
                            {{ contact.last_message }}
                        </p>

                        <div class="mt-2 flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                {{ contact.last_intent || 'Unknown' }}
                            </span>
                        </div>
                    </button>
                    
                    <div v-if="filteredContacts.length === 0" class="text-center py-10 text-slate-500">
                        No chats found
                    </div>
                </div>
            </div>

            <!-- Chat Area (Center & Right) -->
            <div class="flex-1 flex gap-6">
                <!-- Main Chat Window -->
                <div class="flex-1 bg-white dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800 rounded-3xl flex flex-col overflow-hidden relative backdrop-blur-sm shadow-lg dark:shadow-none">
                    
                    <div v-if="activeContact" class="flex flex-col h-full">
                        <!-- Header -->
                        <div class="p-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/80 flex justify-between items-center backdrop-blur-md z-10">
                            <div class="flex items-center gap-3">
                                <img v-if="activeContact.customer?.profile_photo_url" :src="activeContact.customer.profile_photo_url" class="h-10 w-10 rounded-full object-cover border-2 border-white dark:border-slate-800 shadow-lg" />
                                <div v-else class="h-10 w-10 rounded-full bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center text-white font-bold shadow-lg shadow-cyan-500/20">
                                    {{ (activeContact.customer?.name || activeContact.phone).charAt(0) }}
                                </div>
                                <div>
                                    <h2 class="font-bold text-slate-900 dark:text-white text-lg">
                                        {{ activeContact.customer?.name || activeContact.phone }}
                                    </h2>
                                    <p class="text-xs text-cyan-600 dark:text-cyan-400 font-mono flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        WhatsApp Connected
                                    </p>
                                </div>
                            </div>
                            <button @click="selectContact(activeContact)" class="p-2 hover:bg-slate-200 dark:hover:bg-slate-800 rounded-full text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-white transition-colors">
                                <ArrowPathIcon class="h-5 w-5" :class="{ 'animate-spin': isLoadingMessages }" />
                            </button>
                        </div>

                        <!-- Messages -->
                        <div ref="chatContainer" class="flex-1 overflow-y-auto p-6 space-y-6 custom-scrollbar bg-slate-50 dark:bg-transparent bg-dots-pattern-light dark:bg-dots-pattern">
                            <div v-for="msg in messages" :key="msg.id" class="flex flex-col" :class="msg.direction === 'outgoing' ? 'items-end' : 'items-start'">
                                <div 
                                    class="max-w-[70%] p-4 rounded-2xl relative group transition-all duration-300 hover:scale-[1.01]"
                                    :class="msg.direction === 'outgoing' 
                                        ? 'bg-gradient-to-br from-cyan-600 to-blue-600 text-white rounded-tr-sm shadow-[0_5px_15px_rgba(8,145,178,0.2)]' 
                                        : 'bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 rounded-tl-sm shadow-lg'"
                                >
                                    <!-- Attachment Rendering -->
                                    <div v-if="msg.metadata?.url" class="mb-2">
                                        <!-- Image -->
                                        <div v-if="msg.metadata.type === 'image'" class="rounded-lg overflow-hidden border border-white/20 dark:border-slate-700 shadow-sm cursor-pointer" @click="window.open(msg.metadata.url, '_blank')">
                                            <img :src="msg.metadata.url" class="max-w-full h-auto object-cover hover:scale-105 transition-transform duration-500" />
                                        </div>
                                        <!-- Document -->
                                        <div v-else class="flex items-center gap-3 p-3 bg-white/10 dark:bg-slate-900/50 rounded-xl border border-white/20 dark:border-slate-700">
                                            <div class="p-2 rounded-lg bg-white/20 dark:bg-slate-800">
                                                <DocumentIcon class="h-6 w-6" />
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-xs font-bold truncate">{{ msg.metadata.name || 'document' }}</p>
                                                <p class="text-[10px] opacity-70">{{ (msg.metadata.size / 1024).toFixed(1) }} KB</p>
                                            </div>
                                            <a :href="msg.metadata.url" target="_blank" class="p-2 hover:bg-white/10 rounded-lg transition-colors">
                                                <ArrowDownTrayIcon class="h-4 w-4" />
                                            </a>
                                        </div>
                                    </div>

                                    <p v-if="msg.message && !msg.message.startsWith('[File:')" class="whitespace-pre-wrap leading-relaxed">{{ msg.message }}</p>
                                    
                                    <div class="mt-2 flex items-center justify-end gap-2 opacity-70">
                                        <span class="text-[10px] font-mono">{{ formatDate(msg.created_at) }}</span>
                                        <CheckCircleIcon v-if="msg.direction === 'outgoing'" class="h-3 w-3" />
                                    </div>

                                    <!-- Intent Badge for Incoming -->
                                    <div v-if="msg.direction === 'incoming' && msg.intent" class="absolute -top-2 -right-2">
                                        <span class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700 text-[10px] px-2 py-0.5 rounded-full text-cyan-600 dark:text-cyan-400 shadow-xl">
                                            {{ msg.intent }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Input -->
                        <div class="p-4 bg-slate-50 dark:bg-slate-900/80 border-t border-slate-200 dark:border-slate-800 backdrop-blur-md">
                            <form @submit.prevent="sendMessage" class="relative">
                                <!-- File Preview Area -->
                                <div v-if="form.file" class="absolute bottom-full left-0 mb-4 p-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-2xl flex items-center gap-3 animate-in fade-in slide-in-from-bottom-2 duration-300">
                                    <div class="h-12 w-12 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                                        <img v-if="filePreview !== 'document'" :src="filePreview" class="h-full w-full object-cover" />
                                        <DocumentIcon v-else class="h-6 w-6 text-slate-400" />
                                    </div>
                                    <div class="flex-1 min-w-0 pr-8">
                                        <p class="text-xs font-bold text-slate-900 dark:text-white truncate max-w-[150px]">{{ form.file.name }}</p>
                                        <p class="text-[10px] text-slate-500">{{ (form.file.size / 1024).toFixed(1) }} KB</p>
                                    </div>
                                    <button type="button" @click="clearFile" class="absolute top-2 right-2 p-1 hover:bg-red-50 dark:hover:bg-red-900/40 rounded-lg text-slate-400 hover:text-red-500 transition-colors">
                                        <XMarkIcon class="h-4 w-4" />
                                    </button>
                                </div>

                                <input type="file" ref="fileInput" class="hidden" @change="handleFileSelect" />
                                
                                <button 
                                    type="button" 
                                    @click="triggerFileSelect"
                                    class="absolute left-3 top-1/2 -translate-y-1/2 p-2 text-slate-400 hover:text-cyan-500 transition-colors"
                                    :disabled="form.processing"
                                >
                                    <PaperClipIcon class="h-5 w-5" />
                                </button>

                                <input 
                                    v-model="form.message"
                                    type="text" 
                                    placeholder="Type a message to reply manually..." 
                                    class="w-full bg-white dark:bg-slate-950 border border-slate-200 dark:border-0 rounded-xl py-4 pl-12 pr-14 text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-600 focus:ring-2 focus:ring-cyan-500/50 shadow-inner"
                                    :disabled="form.processing"
                                />
                                <button 
                                    type="submit" 
                                    :disabled="form.processing || (!form.message.trim() && !form.file)"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 p-2 bg-cyan-500 hover:bg-cyan-400 text-white rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-cyan-500/20"
                                >
                                    <PaperAirplaneIcon class="h-5 w-5" />
                                </button>
                            </form>
                            <p class="text-[10px] text-slate-500 mt-2 text-center">
                                Tip: Sending a manual message will not stop the bot from replying to future messages.
                            </p>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="h-full flex flex-col items-center justify-center text-slate-500 p-8 text-center">
                        <div class="h-40 w-40 rounded-full bg-slate-100 dark:bg-slate-800/50 flex items-center justify-center mb-6 border border-slate-200 dark:border-slate-700 shadow-2xl animate-pulse-slow">
                            <ChatBubbleLeftRightIcon class="h-20 w-20 text-slate-400 dark:text-slate-600" />
                        </div>
                        <h3 class="text-xl font-bold text-slate-800 dark:text-white mb-2">Select a Conversation</h3>
                        <p class="max-w-xs mx-auto text-slate-500">Click on a contact from the left panel to view history and chat manually.</p>
                    </div>
                </div>

                <!-- Info Panel (Right) - Only visible when chat active -->
                <div v-if="activeContact" class="w-80 hidden xl:flex flex-col gap-4">
                    <!-- Customer Card -->
                    <div class="bg-white dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 backdrop-blur-sm shadow-lg dark:shadow-none">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="h-16 w-16 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-cyan-600 dark:text-cyan-400">
                                <UserCircleIcon class="h-10 w-10" />
                            </div>
                            <div>
                                <div class="text-[10px] uppercase tracking-wider text-slate-500 font-bold mb-1">Customer</div>
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white leading-tight">
                                    {{ activeContact.customer?.name || 'Unregistered' }}
                                </h3>
                                <div v-if="activeContact.customer?.contact_person" class="text-xs font-bold text-cyan-600 dark:text-cyan-400 mt-1 uppercase tracking-widest">
                                    {{ activeContact.customer.contact_person }}
                                </div>
                            </div>
                        </div>
                        
                        <div v-if="activeContact.customer?.profile_photo_url" class="mb-6 rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-800 shadow-inner">
                            <img :src="activeContact.customer.profile_photo_url" class="w-full aspect-square object-cover" />
                        </div>

                        <div class="space-y-4">
                            <div>
                                <span class="text-xs text-slate-500 block mb-1">Phone Number</span>
                                <span class="text-sm font-mono text-cyan-700 dark:text-cyan-300 bg-cyan-50 dark:bg-cyan-950/30 px-2 py-1 rounded border border-cyan-200 dark:border-cyan-900/50">
                                    {{ activeContact.phone }}
                                </span>
                            </div>
                            
                            <div v-if="activeContact.last_intent">
                                <span class="text-xs text-slate-500 block mb-1">Last Detected Intent</span>
                                <span class="text-xs font-bold text-slate-800 dark:text-white bg-slate-100 dark:bg-slate-800 px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 inline-block">
                                    ✨ {{ activeContact.last_intent }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions (Placeholder) -->
                    <div class="bg-gradient-to-br from-indigo-50 dark:from-indigo-900/20 to-purple-50 dark:to-purple-900/20 border border-indigo-200 dark:border-indigo-500/20 rounded-3xl p-6 backdrop-blur-sm">
                        <h4 class="text-sm font-bold text-indigo-700 dark:text-indigo-300 mb-4 flex items-center gap-2">
                             Quick Actions
                        </h4>
                        <div class="space-y-2">
                            <button class="w-full text-left px-4 py-3 rounded-xl bg-white dark:bg-slate-900/50 hover:bg-indigo-100 dark:hover:bg-indigo-500/20 border border-slate-200 dark:border-slate-700 hover:border-indigo-300 dark:hover:border-indigo-500/50 text-sm text-slate-700 dark:text-slate-300 transition-all flex items-center justify-between group">
                                Create Sales Order
                                <ArrowPathIcon class="h-4 w-4 opacity-0 group-hover:opacity-100 transition-opacity" />
                            </button>
                            <button class="w-full text-left px-4 py-3 rounded-xl bg-white dark:bg-slate-900/50 hover:bg-indigo-100 dark:hover:bg-indigo-500/20 border border-slate-200 dark:border-slate-700 hover:border-indigo-300 dark:hover:border-indigo-500/50 text-sm text-slate-700 dark:text-slate-300 transition-all flex items-center justify-between group">
                                View Profile
                                <ArrowPathIcon class="h-4 w-4 opacity-0 group-hover:opacity-100 transition-opacity" />
                            </button>
                            <button @click="confirmDeleteHistory" class="w-full text-left px-4 py-3 rounded-xl bg-white dark:bg-slate-900/50 hover:bg-red-50 dark:hover:bg-red-900/20 border border-slate-200 dark:border-slate-700 hover:border-red-200 dark:hover:border-red-800 text-sm text-slate-700 dark:text-slate-300 hover:text-red-600 dark:hover:text-red-400 transition-all flex items-center justify-between group">
                                Clear Chat History
                                <TrashIcon class="h-4 w-4 opacity-0 group-hover:opacity-100 transition-opacity" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #94a3b8;
    border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #64748b;
}

:global(.dark) .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #475569;
}
:global(.dark) .custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #64748b;
}

.bg-dots-pattern {
    background-image: radial-gradient(#334155 1px, transparent 1px);
    background-size: 20px 20px;
}
.bg-dots-pattern-light {
    background-image: radial-gradient(#cbd5e1 1px, transparent 1px);
    background-size: 20px 20px;
}
.dark .bg-dots-pattern-light {
    background-image: none;
}
.animate-pulse-slow {
    animation: pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
