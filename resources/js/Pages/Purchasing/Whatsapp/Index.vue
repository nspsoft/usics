<script setup>
import { Head, useForm, router, usePage, Link } from '@inertiajs/vue3';
import { ref, computed, onMounted, nextTick } from 'vue';
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
    InformationCircleIcon,
    ArrowTopRightOnSquareIcon,
    TrashIcon,
    PaperClipIcon,
    XMarkIcon,
    DocumentIcon,
    ArrowDownTrayIcon,
    TagIcon,
    PlusIcon,
    PlusCircleIcon,
    PhoneIcon,
    DocumentDuplicateIcon,
    ChevronDownIcon,
} from '@heroicons/vue/24/outline';
import axios from 'axios';

const props = defineProps({
    contacts: Array,
    totalUnread: { type: Number, default: 0 },
    templates: { type: Array, default: () => [] },
    labelPresets: { type: Array, default: () => [] },
    suppliers: { type: Array, default: () => [] },
});

const search = ref('');
const activeContact = ref(null);
const messages = ref([]);
const isLoadingMessages = ref(false);
const chatContainer = ref(null);
const showTemplateDropdown = ref(false);
const templateSearch = ref('');
const showLabelDropdown = ref(false);
const filterLabel = ref('');
const showNewChatModal = ref(false);
const selectedSupplierIdForNewChat = ref('');

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
    
    if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = (e) => {
            filePreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    } else {
        filePreview.value = 'document';
    }
};

const clearFile = () => {
    form.file = null;
    filePreview.value = null;
    if (fileInput.value) fileInput.value.value = '';
};

// Filter contacts by search and label
const filteredContacts = computed(() => {
    let results = props.contacts || [];
    
    if (search.value) {
        results = results.filter(c => 
            c.phone.includes(search.value) || 
            (c.supplier?.name || '').toLowerCase().includes(search.value.toLowerCase()) ||
            (c.supplier?.contact_person || '').toLowerCase().includes(search.value.toLowerCase())
        );
    }
    
    if (filterLabel.value) {
        results = results.filter(c => 
            c.labels && c.labels.some(l => l.label === filterLabel.value)
        );
    }
    
    return results;
});

// Template filtering
const filteredTemplates = computed(() => {
    if (!templateSearch.value) return props.templates;
    return props.templates.filter(t =>
        t.name.toLowerCase().includes(templateSearch.value.toLowerCase()) ||
        t.body.toLowerCase().includes(templateSearch.value.toLowerCase())
    );
});

// All unique labels across contacts
const allUsedLabels = computed(() => {
    const set = new Set();
    (props.contacts || []).forEach(c => {
        (c.labels || []).forEach(l => set.add(l.label));
    });
    return Array.from(set);
});

// Select contact & load history
const selectContact = async (contact) => {
    activeContact.value = contact;
    form.phone = contact.phone;
    fetchMessages(contact.phone);
    showLabelDropdown.value = false;
};

const fetchMessages = async (phone) => {
    isLoadingMessages.value = true;
    try {
        const response = await axios.get(route('purchasing.whatsapp.history', phone));
        messages.value = response.data;
        
        // Reset unread count for this contact locally
        if (activeContact.value) {
            activeContact.value.unread_count = 0;
        }
        
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
    form.post(route('purchasing.whatsapp.send'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            messages.value.push({
                direction: 'outgoing',
                message: form.message || (form.file ? `[File: ${form.file.name}]` : ''),
                created_at: new Date().toISOString(),
                intent: 'manual_reply',
                metadata: form.file ? {
                    type: form.file.type.startsWith('image/') ? 'image' : 'document',
                    name: form.file.name,
                    url: filePreview.value !== 'document' ? filePreview.value : null,
                    size: form.file.size
                } : null
            });
            form.message = '';
            clearFile();
            scrollToBottom();
            
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

// Template actions
const selectTemplate = (template) => {
    form.message = template.body;
    showTemplateDropdown.value = false;
    templateSearch.value = '';
};

const toggleTemplateDropdown = () => {
    showTemplateDropdown.value = !showTemplateDropdown.value;
    if (showTemplateDropdown.value) {
        templateSearch.value = '';
    }
};

// Label actions
const addLabel = async (preset) => {
    if (!activeContact.value) return;
    
    try {
        await axios.post(route('purchasing.whatsapp.labels.store'), {
            phone: activeContact.value.phone,
            label: preset.label,
            color: preset.color,
        });
        
        // Add locally
        if (!activeContact.value.labels) activeContact.value.labels = [];
        if (!activeContact.value.labels.find(l => l.label === preset.label)) {
            activeContact.value.labels.push({ label: preset.label, color: preset.color, id: Date.now() });
        }
        showLabelDropdown.value = false;
    } catch (err) {
        console.error('Failed to add label', err);
    }
};

const removeLabel = async (label) => {
    if (!label.id) return;
    
    try {
        await axios.delete(route('purchasing.whatsapp.labels.destroy', label.id));
        
        // Remove locally
        if (activeContact.value?.labels) {
            activeContact.value.labels = activeContact.value.labels.filter(l => l.id !== label.id);
        }
    } catch (err) {
        console.error('Failed to remove label', err);
    }
};

// Label color mapping
const labelColors = {
    red: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 border-red-200 dark:border-red-800',
    orange: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400 border-orange-200 dark:border-orange-800',
    yellow: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 border-yellow-200 dark:border-yellow-800',
    rose: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400 border-rose-200 dark:border-rose-800',
    purple: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400 border-purple-200 dark:border-purple-800',
    blue: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 border-blue-200 dark:border-blue-800',
    slate: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400 border-slate-200 dark:border-slate-700',
    emerald: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800',
};

const getLabelClass = (color) => labelColors[color] || labelColors.slate;

// Auto refresh history every 10s if active
let pollingInterval;
onMounted(() => {
    pollingInterval = setInterval(() => {
        if (activeContact.value) {
            axios.get(route('purchasing.whatsapp.history', activeContact.value.phone))
                .then(res => {
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
    
    if (confirm('Apakah Anda yakin ingin menghapus riwayat chat ini? Tindakan ini tidak dapat dibatalkan.')) {
        router.delete(route('purchasing.whatsapp.destroy', activeContact.value.phone), {
            onSuccess: () => {
                messages.value = [];
            }
        });
    }
};

// Close dropdowns on click outside
const closeDropdowns = () => {
    showTemplateDropdown.value = false;
    showLabelDropdown.value = false;
};

// New Chat from Supplier Picker
const startNewChat = () => {
    if (!selectedSupplierIdForNewChat.value) return;
    
    const supplier = props.suppliers.find(s => s.id === selectedSupplierIdForNewChat.value);
    if (!supplier || !supplier.phone) return;

    let phone = supplier.phone.replace(/[\s\-\+]/g, '');
    if (phone.startsWith('0')) phone = '62' + phone.substring(1);
    
    const tempContact = {
        phone: phone,
        supplier: supplier,
        last_message: '',
        last_activity: new Date().toISOString(),
        last_intent: null,
        unread_count: 0,
        labels: [],
    };
    
    activeContact.value = tempContact;
    form.phone = phone;
    messages.value = [];
    showNewChatModal.value = false;
    selectedSupplierIdForNewChat.value = '';
};

</script>

<template>
    <Head title="Purchasing WhatsApp Center" />

    <AppLayout title="Purchasing WhatsApp Center">
        <div class="h-[calc(100vh-12rem)] w-full flex gap-6 overflow-hidden" @click.self="closeDropdowns">
            
            <!-- Chat List (Left) -->
            <div class="w-1/3 flex flex-col gap-4">
                <!-- Search + New Chat Button -->
                <div class="flex gap-2">
                    <div class="relative flex-1">
                        <input 
                            v-model="search"
                            type="text" 
                            placeholder="Cari chat supplier..." 
                            class="w-full bg-white dark:bg-slate-900/50 border border-slate-300 dark:border-slate-700 rounded-xl py-3 pl-10 pr-4 text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:ring-2 focus:ring-purple-500/50 font-sans"
                        />
                        <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400 dark:text-slate-500" />
                    </div>
                    <button 
                        @click="showNewChatModal = true"
                        class="flex-shrink-0 px-4 py-3 bg-purple-600 hover:bg-purple-500 text-white rounded-xl transition-all shadow-lg shadow-purple-500/20 hover:shadow-purple-500/40 flex items-center gap-2 font-bold text-sm"
                        title="Chat Baru"
                    >
                        <PlusCircleIcon class="h-5 w-5" />
                        <span class="hidden 2xl:inline">Chat Baru</span>
                    </button>
                </div>

                <!-- Label Filter -->
                <div v-if="allUsedLabels.length" class="flex items-center gap-2 flex-wrap">
                    <button 
                        @click="filterLabel = ''"
                        class="text-[10px] px-2 py-1 rounded-lg border font-bold transition-all"
                        :class="filterLabel === '' ? 'bg-purple-600 text-white border-purple-600' : 'bg-white dark:bg-slate-900 text-slate-500 border-slate-300 dark:border-slate-700 hover:border-purple-400'"
                    >All</button>
                    <button 
                        v-for="label in allUsedLabels" :key="label"
                        @click="filterLabel = label"
                        class="text-[10px] px-2 py-1 rounded-lg border font-bold transition-all"
                        :class="filterLabel === label ? 'bg-purple-600 text-white border-purple-600' : 'bg-white dark:bg-slate-900 text-slate-500 border-slate-300 dark:border-slate-700 hover:border-purple-400'"
                    >{{ label }}</button>
                </div>

                <!-- Guide Link -->
                <a href="/whatsapp_guide.html?module=purchasing" target="_blank" class="flex items-center justify-between px-4 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 rounded-xl text-white shadow-lg shadow-purple-500/20 hover:shadow-purple-500/40 transition-all group">
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

                <!-- Information Link -->
                <a href="/whatsapp_guide.html?module=purchasing" target="_blank" class="flex items-center justify-between px-4 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl text-white shadow-lg shadow-indigo-500/20 hover:shadow-indigo-500/40 transition-all group duration-300 hover:scale-[1.02]">
                    <div class="flex items-center gap-3">
                        <div class="p-1.5 bg-white/20 rounded-lg backdrop-blur-sm group-hover:rotate-12 transition-transform duration-300">
                            <InformationCircleIcon class="h-5 w-5 text-white" />
                        </div>
                        <div class="text-left">
                            <div class="text-xs font-bold opacity-90">Bisa Apa Saja?</div>
                            <div class="text-sm font-bold">Kemampuan WhatsApp Bot</div>
                        </div>
                    </div>
                    <ArrowTopRightOnSquareIcon class="h-5 w-5 opacity-70 group-hover:opacity-100 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-all" />
                </a>

                <!-- Contact List -->
                <div class="flex-1 overflow-y-auto pr-2 space-y-2 custom-scrollbar">
                    <button 
                        v-for="contact in filteredContacts" 
                        :key="contact.phone"
                        @click="selectContact(contact)"
                        class="w-full text-left p-4 rounded-2xl border transition-all duration-200 group relative overflow-hidden"
                        :class="activeContact?.phone === contact.phone 
                            ? 'bg-purple-50 dark:bg-purple-500/10 border-purple-500 dark:border-purple-500/50 shadow-lg dark:shadow-[0_0_20px_rgba(147,51,234,0.15)]' 
                            : 'bg-white dark:bg-slate-900/40 border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-800/40'"
                    >
                        <div v-if="activeContact?.phone === contact.phone" class="absolute inset-0 bg-gradient-to-r from-purple-500/10 to-transparent opacity-50"></div>

                        <div class="relative flex items-center gap-3 mb-1">
                            <!-- Avatar -->
                            <div class="flex-shrink-0 relative">
                                <div class="h-10 w-10 rounded-full bg-gradient-to-br from-purple-200 to-purple-300 dark:from-purple-700 dark:to-purple-800 flex items-center justify-center text-purple-700 dark:text-purple-300 font-bold text-xs shadow-inner">
                                    {{ (contact.supplier?.name || contact.phone).charAt(0) }}
                                </div>
                                <span v-if="contact.unread_count > 0" class="absolute -top-1 -right-1 inline-flex items-center justify-center w-5 h-5 text-[10px] font-bold text-white bg-red-500 rounded-full shadow-lg shadow-red-500/30 ring-2 ring-white dark:ring-slate-900">
                                    {{ contact.unread_count > 9 ? '9+' : contact.unread_count }}
                                </span>
                            </div>
                            
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-start">
                                    <h3 class="font-bold text-slate-800 dark:text-slate-200 group-hover:text-slate-900 dark:group-hover:text-white transition-colors truncate">
                                        {{ contact.supplier?.name || contact.phone }}
                                    </h3>
                                    <span class="text-[10px] text-slate-500 font-mono mt-1 whitespace-nowrap ml-2">
                                        {{ formatDate(contact.last_activity) }}
                                    </span>
                                </div>
                                <div v-if="contact.supplier?.contact_person" class="text-[10px] font-bold text-purple-600 dark:text-purple-400 uppercase tracking-widest truncate">
                                    {{ contact.supplier.contact_person }}
                                </div>
                            </div>
                        </div>
                        
                        <p class="text-sm text-slate-600 dark:text-slate-400 line-clamp-1 group-hover:text-purple-700 dark:group-hover:text-purple-200/70 transition-colors">
                            {{ contact.last_message }}
                        </p>

                        <div class="mt-2 flex items-center gap-2 flex-wrap">
                            <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                {{ contact.last_intent || 'Unknown' }}
                            </span>
                            <span 
                                v-for="label in (contact.labels || [])" :key="label.id"
                                class="px-2 py-0.5 rounded text-[10px] font-bold border"
                                :class="getLabelClass(label.color)"
                            >
                                {{ label.label }}
                            </span>
                        </div>
                    </button>
                    
                    <div v-if="filteredContacts.length === 0" class="text-center py-10 text-slate-500">
                        Tidak ada chat supplier ditemukan
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
                                <div class="h-10 w-10 rounded-full bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center text-white font-bold shadow-lg shadow-purple-500/20">
                                    {{ (activeContact.supplier?.name || activeContact.phone).charAt(0) }}
                                </div>
                                <div>
                                    <h2 class="font-bold text-slate-900 dark:text-white text-lg">
                                        {{ activeContact.supplier?.name || activeContact.phone }}
                                    </h2>
                                    <p class="text-xs text-purple-600 dark:text-purple-400 font-mono flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        WhatsApp connected (Purchasing)
                                    </p>
                                </div>
                            </div>
                            <button @click="selectContact(activeContact)" class="p-2 hover:bg-slate-200 dark:hover:bg-slate-800 rounded-full text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-white transition-colors">
                                <ArrowPathIcon class="h-5 w-5" :class="{ 'animate-spin': isLoadingMessages }" />
                            </button>
                        </div>

                        <!-- Messages -->
                        <div ref="chatContainer" class="flex-1 overflow-y-auto p-6 space-y-6 custom-scrollbar bg-slate-50 dark:bg-transparent">
                            <div v-for="msg in messages" :key="msg.id" class="flex flex-col" :class="msg.direction === 'outgoing' ? 'items-end' : 'items-start'">
                                <div 
                                    class="max-w-[75%] p-4 rounded-2xl relative group transition-all duration-300 hover:scale-[1.01]"
                                    :class="msg.direction === 'outgoing' 
                                        ? 'bg-gradient-to-br from-purple-600 to-indigo-600 text-white rounded-tr-sm shadow-[0_5px_15px_rgba(147,51,234,0.2)]' 
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
                                        <span class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700 text-[10px] px-2 py-0.5 rounded-full text-purple-600 dark:text-purple-400 shadow-xl">
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

                                <!-- Template Dropdown -->
                                <div v-if="showTemplateDropdown" class="absolute bottom-full left-0 right-0 mb-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-2xl z-20 max-h-72 overflow-hidden" @click.stop>
                                    <div class="p-3 border-b border-slate-200 dark:border-slate-700">
                                        <input 
                                            v-model="templateSearch"
                                            type="text" 
                                            placeholder="Cari template..." 
                                            class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg py-2 px-3 text-sm text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-purple-500/50"
                                        />
                                    </div>
                                    <div class="overflow-y-auto max-h-52 custom-scrollbar">
                                        <button 
                                            v-for="tpl in filteredTemplates" :key="tpl.id"
                                            type="button"
                                            @click="selectTemplate(tpl)"
                                            class="w-full text-left p-3 hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-colors border-b border-slate-100 dark:border-slate-800 last:border-0"
                                        >
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="text-sm font-bold text-slate-900 dark:text-white">{{ tpl.name }}</span>
                                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-500 border border-slate-200 dark:border-slate-700">{{ tpl.category }}</span>
                                            </div>
                                            <p class="text-xs text-slate-500 line-clamp-2">{{ tpl.body }}</p>
                                        </button>
                                        <div v-if="filteredTemplates.length === 0" class="p-4 text-center text-sm text-slate-400">
                                            Tidak ada template ditemukan
                                        </div>
                                    </div>
                                </div>

                                <input type="file" ref="fileInput" class="hidden" @change="handleFileSelect" />
                                
                                <button 
                                    type="button" 
                                    @click="triggerFileSelect"
                                    class="absolute left-3 top-1/2 -translate-y-1/2 p-2 text-slate-400 hover:text-purple-500 transition-colors"
                                    :disabled="form.processing"
                                >
                                    <PaperClipIcon class="h-5 w-5" />
                                </button>

                                <!-- Template Button -->
                                <button 
                                    type="button" 
                                    @click.stop="toggleTemplateDropdown"
                                    class="absolute left-12 top-1/2 -translate-y-1/2 p-2 text-slate-400 hover:text-purple-500 transition-colors"
                                    :class="{ 'text-purple-500': showTemplateDropdown }"
                                    :disabled="form.processing"
                                    title="Quick Templates"
                                >
                                    <DocumentDuplicateIcon class="h-5 w-5" />
                                </button>

                                <input 
                                    v-model="form.message"
                                    type="text" 
                                    placeholder="Tulis pesan manual untuk supplier..." 
                                    class="w-full bg-white dark:bg-slate-955 border border-slate-200 dark:border-0 rounded-xl py-4 pl-[5.5rem] pr-14 text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-600 focus:ring-2 focus:ring-purple-500/50 shadow-inner"
                                    :disabled="form.processing"
                                    @focus="showTemplateDropdown = false"
                                />
                                <button 
                                    type="submit" 
                                    :disabled="form.processing || (!form.message.trim() && !form.file)"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 p-2 bg-purple-600 hover:bg-purple-500 text-white rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-purple-500/20"
                                >
                                    <PaperAirplaneIcon class="h-5 w-5" />
                                </button>
                            </form>
                            <p class="text-[10px] text-slate-500 mt-2 text-center">
                                Tip: Klik <DocumentDuplicateIcon class="h-3 w-3 inline" /> untuk template jawaban cepat. Bot AI tetap merespon pesan otomatis setelah ini.
                            </p>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="h-full flex flex-col items-center justify-center text-slate-500 p-8 text-center">
                        <div class="h-40 w-40 rounded-full bg-slate-100 dark:bg-slate-800/50 flex items-center justify-center mb-6 border border-slate-200 dark:border-slate-700 shadow-2xl animate-pulse">
                            <ChatBubbleLeftRightIcon class="h-20 w-20 text-slate-400 dark:text-slate-600" />
                        </div>
                        <h3 class="text-xl font-bold text-slate-800 dark:text-white mb-2">Pilih Percakapan</h3>
                        <p class="max-w-xs mx-auto text-slate-500">Pilih salah satu kontak supplier di panel kiri untuk melihat riwayat pesan dan membalas manual.</p>
                    </div>
                </div>

                <!-- Info Panel (Right) -->
                <div v-if="activeContact" class="w-80 hidden xl:flex flex-col gap-4">
                    <!-- Supplier Card -->
                    <div class="bg-white dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 backdrop-blur-sm shadow-lg dark:shadow-none">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="h-16 w-16 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-purple-600 dark:text-purple-400">
                                <UserCircleIcon class="h-10 w-10" />
                            </div>
                            <div>
                                <div class="text-[10px] uppercase tracking-wider text-slate-500 font-bold mb-1">Supplier</div>
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white leading-tight">
                                    {{ activeContact.supplier?.name || 'Belum Terdaftar' }}
                                </h3>
                                <div v-if="activeContact.supplier?.contact_person" class="text-xs font-bold text-purple-600 dark:text-purple-400 mt-1 uppercase tracking-widest">
                                    {{ activeContact.supplier.contact_person }}
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <span class="text-xs text-slate-500 block mb-1">Nomor WhatsApp</span>
                                <span class="text-sm font-mono text-purple-700 dark:text-purple-300 bg-purple-50 dark:bg-purple-950/30 px-2 py-1 rounded border border-purple-200 dark:border-purple-900/50">
                                    {{ activeContact.phone }}
                                </span>
                            </div>
                            
                            <div v-if="activeContact.last_intent">
                                <span class="text-xs text-slate-500 block mb-1">Intent Terakhir</span>
                                <span class="text-xs font-bold text-slate-800 dark:text-white bg-slate-100 dark:bg-slate-800 px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 inline-block">
                                    ✨ {{ activeContact.last_intent }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Labels Section -->
                    <div class="bg-white dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 backdrop-blur-sm shadow-lg dark:shadow-none relative z-10">
                        <h4 class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-3 flex items-center gap-2">
                            <TagIcon class="h-4 w-4 text-purple-500" />
                            Labels
                        </h4>

                        <!-- Current Labels -->
                        <div class="flex flex-wrap gap-2 mb-3">
                            <span 
                                v-for="label in (activeContact.labels || [])" :key="label.id"
                                class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[11px] font-bold border transition-all"
                                :class="getLabelClass(label.color)"
                            >
                                {{ label.label }}
                                <button @click="removeLabel(label)" class="ml-0.5 opacity-60 hover:opacity-100 transition-opacity">
                                    <XMarkIcon class="h-3 w-3" />
                                </button>
                            </span>
                            <span v-if="!activeContact.labels?.length" class="text-xs text-slate-400 italic">Belum ada label</span>
                        </div>

                        <!-- Add Label -->
                        <div class="relative">
                            <button 
                                @click.stop="showLabelDropdown = !showLabelDropdown"
                                class="w-full flex items-center justify-center gap-1 px-3 py-2 rounded-xl border border-dashed border-slate-300 dark:border-slate-700 text-xs text-slate-500 hover:text-purple-500 hover:border-purple-400 transition-all"
                            >
                                <PlusIcon class="h-3.5 w-3.5" />
                                Tambah Label
                            </button>
                            
                            <!-- Label Preset Dropdown -->
                            <div v-if="showLabelDropdown" class="absolute top-full left-0 right-0 mt-2 z-20 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl shadow-2xl overflow-hidden" @click.stop>
                                <button 
                                    v-for="preset in labelPresets" :key="preset.label"
                                    @click="addLabel(preset)"
                                    class="w-full text-left px-3 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors flex items-center gap-2"
                                >
                                    <span class="w-2 h-2 rounded-full" :class="`bg-${preset.color}-500`"></span>
                                    <span class="text-slate-700 dark:text-slate-300">{{ preset.label }}</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-gradient-to-br from-purple-50 dark:from-purple-900/20 to-indigo-50 dark:to-indigo-900/20 border border-purple-200 dark:border-purple-500/20 rounded-3xl p-6 backdrop-blur-sm">
                        <h4 class="text-sm font-bold text-purple-700 dark:text-purple-300 mb-4 flex items-center gap-2">
                             Quick Actions
                        </h4>
                        <div class="space-y-2">
                            <a :href="route('purchasing.orders.create')" class="w-full text-left px-4 py-3 rounded-xl bg-white dark:bg-slate-900/50 hover:bg-purple-100 dark:hover:bg-purple-500/20 border border-slate-200 dark:border-slate-700 hover:border-purple-300 dark:hover:border-purple-500/50 text-sm text-slate-700 dark:text-slate-300 transition-all flex items-center justify-between group">
                                Buat Purchase Order (PO)
                                <ArrowTopRightOnSquareIcon class="h-4 w-4 opacity-0 group-hover:opacity-100 transition-opacity" />
                            </a>
                            <a :href="route('purchasing.requests.create')" class="w-full text-left px-4 py-3 rounded-xl bg-white dark:bg-slate-900/50 hover:bg-purple-100 dark:hover:bg-purple-500/20 border border-slate-200 dark:border-slate-700 hover:border-purple-300 dark:hover:border-purple-500/50 text-sm text-slate-700 dark:text-slate-300 transition-all flex items-center justify-between group">
                                Buat Purchase Request (PR)
                                <ArrowTopRightOnSquareIcon class="h-4 w-4 opacity-0 group-hover:opacity-100 transition-opacity" />
                            </a>
                            <button @click="confirmDeleteHistory" class="w-full text-left px-4 py-3 rounded-xl bg-white dark:bg-slate-900/50 hover:bg-red-50 dark:hover:bg-red-900/20 border border-slate-200 dark:border-slate-700 hover:border-red-200 dark:hover:border-red-800 text-sm text-slate-700 dark:text-slate-300 hover:text-red-600 dark:hover:text-red-400 transition-all flex items-center justify-between group">
                                Hapus Riwayat Chat
                                <TrashIcon class="h-4 w-4 opacity-0 group-hover:opacity-100 transition-opacity" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- New Chat Modal -->
        <Teleport to="body">
            <div v-if="showNewChatModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showNewChatModal = false">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
                <div class="relative bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-md overflow-hidden">
                    <!-- Header -->
                    <div class="p-6 pb-0">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <PhoneIcon class="h-5 w-5 text-purple-500" />
                                Chat Baru (Supplier)
                            </h3>
                            <button @click="showNewChatModal = false" class="p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                                <XMarkIcon class="h-5 w-5 text-slate-500" />
                            </button>
                        </div>
                        <p class="text-sm text-slate-500">Pilih supplier yang terdaftar untuk memulai percakapan WhatsApp baru.</p>
                    </div>

                    <!-- Body -->
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Pilih Supplier</label>
                            <select 
                                v-model="selectedSupplierIdForNewChat"
                                class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl py-3 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500/50 focus:border-purple-500 outline-none text-sm"
                            >
                                <option value="" disabled>-- Pilih Supplier --</option>
                                <option 
                                    v-for="sup in suppliers" 
                                    :key="sup.id" 
                                    :value="sup.id"
                                >
                                    {{ sup.name }} ({{ sup.phone }})
                                </option>
                            </select>
                        </div>
                        
                        <div class="flex gap-3 justify-end pt-2">
                            <button 
                                type="button" 
                                @click="showNewChatModal = false"
                                class="px-4 py-2 text-xs font-bold uppercase tracking-widest text-slate-500 hover:text-slate-800 dark:hover:text-white transition-colors"
                            >
                                Batal
                            </button>
                            <button 
                                type="button"
                                @click="startNewChat"
                                :disabled="!selectedSupplierIdForNewChat"
                                class="px-6 py-2 bg-purple-600 hover:bg-purple-500 disabled:bg-slate-300 text-white text-xs font-bold uppercase tracking-widest rounded-xl transition-all shadow-md shadow-purple-500/10"
                            >
                                Buka Chat
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<style scoped>
/* Custom Scrollbar Styles */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(147, 51, 234, 0.2);
    border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(147, 51, 234, 0.4);
}
</style>
