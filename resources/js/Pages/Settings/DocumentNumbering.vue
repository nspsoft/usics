<script setup>
import { ref, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    HashtagIcon, 
    PencilSquareIcon,
    ArrowPathIcon,
    InformationCircleIcon,
    PlusIcon
} from '@heroicons/vue/24/outline';
import _ from 'lodash';

const props = defineProps({
    numberings: Object, // Grouped by module name
});

const isEditing = ref(false);
const editingId = ref(null);
const preview = ref('');
const showModal = ref(false);

const form = useForm({
    module: '',
    code: '',
    name: '',
    prefix: '',
    format: '{PREFIX}/{Y}/{m}/{NUMBER}',
    padding: 4,
    reset_period: 'monthly',
    current_number: 0,
    separator: '/',
});

const createNew = () => {
    isEditing.value = false;
    editingId.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
    updatePreview();
};

const edit = (numbering) => {
    isEditing.value = true;
    editingId.value = numbering.id;
    form.clearErrors();
    form.module = numbering.module;
    form.code = numbering.code;
    form.name = numbering.name;
    form.prefix = numbering.prefix;
    form.format = numbering.format;
    form.padding = numbering.padding;
    form.reset_period = numbering.reset_period;
    form.current_number = numbering.current_number;
    form.separator = numbering.separator || '/';
    showModal.value = true;
    updatePreview();
};

const updatePreview = _.debounce(async () => {
    const now = new Date();
    let p = form.format
        .replace('{PREFIX}', form.prefix || '[PREFIX]')
        .replace('{Y}', now.getFullYear())
        .replace('{y}', String(now.getFullYear()).slice(-2))
        .replace('{m}', String(now.getMonth() + 1).padStart(2, '0'))
        .replace('{d}', String(now.getDate()).padStart(2, '0'))
        .replace('{NUMBER}', '0'.repeat(Math.max(0, (form.padding || 4) - 1)) + '1');
    
    preview.value = p;
}, 300);

watch(() => [form.prefix, form.format, form.padding], updatePreview);

const submit = () => {
    if (isEditing.value) {
        form.put(route('settings.numbering.update', editingId.value), {
            onSuccess: () => {
                showModal.value = false;
            },
        });
    } else {
        form.post(route('settings.numbering.store'), {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    }
};

const resetPeriods = [
    { value: 'never', label: 'Never Reset (Continuous)' },
    { value: 'daily', label: 'Daily (Reset every day)' },
    { value: 'monthly', label: 'Monthly (Reset every month)' },
    { value: 'yearly', label: 'Yearly (Reset every year)' },
];

const availableTags = ['{PREFIX}', '{Y}', '{m}', '{d}', '{NUMBER}'];

const insertTag = (tag) => {
    const el = document.getElementById('formatInput');
    if (el) {
        const start = el.selectionStart;
        const end = el.selectionEnd;
        const text = form.format;
        form.format = text.substring(0, start) + tag + text.substring(end);
        setTimeout(() => {
            el.focus();
            el.selectionStart = el.selectionEnd = start + tag.length;
        }, 0);
    } else {
        form.format += tag;
    }
};
</script>

<template>
    <Head title="Document Numbering" />
    
    <AppLayout title="Document Numbering">
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-xl shadow-lg">
                        <HashtagIcon class="h-6 w-6 text-white" />
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white">Document Numbering</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Manage number formats for all documents</p>
                    </div>
                </div>
                <button 
                    @click="createNew"
                    class="flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-lg transition-all transform hover:scale-105 active:scale-95"
                >
                    <PlusIcon class="h-5 w-5" />
                    Register New Document
                </button>
            </div>
        </template>

        <div class="p-6 space-y-8">
            <div v-for="(items, module) in numberings" :key="module">
                <h3 class="text-lg font-bold text-slate-800 dark:text-gray-200 mb-4 px-1 flex items-center gap-2">
                    <span class="w-1.5 h-6 bg-blue-500 rounded-full"></span>
                    {{ module }}
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div 
                        v-for="item in items" 
                        :key="item.id" 
                        class="glass-card p-5 rounded-2xl relative group hover:ring-2 hover:ring-blue-500/20 transition-all border border-slate-200 dark:border-slate-800"
                    >
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h4 class="font-bold text-slate-900 dark:text-white">{{ item.name }}</h4>
                                <p class="text-xs text-slate-500 font-mono">{{ item.code }}</p>
                            </div>
                            <span class="px-2 py-1 rounded-lg text-[10px] uppercase tracking-wider font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                {{ item.reset_period }}
                            </span>
                        </div>

                        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-3 mb-4 border border-slate-200 dark:border-slate-700">
                             <p class="text-[10px] text-slate-500 mb-1 uppercase tracking-widest font-bold">Format Pattern</p>
                             <p class="font-mono text-sm font-semibold text-blue-600 dark:text-blue-400 break-all">
                                 {{ item.format }}
                             </p>
                        </div>
                        
                        <div class="flex items-center justify-between mt-4">
                             <div class="text-xs text-slate-500">
                                Next #: <span class="font-bold text-slate-900 dark:text-white">{{ item.current_number + 1 }}</span>
                             </div>
                             <button 
                                @click="edit(item)"
                                class="p-2 bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-400 dark:hover:bg-blue-900/40 rounded-lg transition-colors border border-blue-100 dark:border-blue-900/50"
                            >
                                <PencilSquareIcon class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit/Create Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm" @click="showModal = false"></div>
                <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl p-6 max-w-md w-full border border-slate-200 dark:border-slate-800">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <PlusIcon v-if="!isEditing" class="h-5 w-5 text-blue-500" />
                            <PencilSquareIcon v-else class="h-5 w-5 text-blue-500" />
                            {{ isEditing ? 'Edit Document Format' : 'Register New Document' }}
                        </h3>
                        <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                            <span class="sr-only">Close</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="submit" class="space-y-4">
                        <!-- Module & Code (Visible always for context, fields only for new) -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Module Name</label>
                                <input 
                                    v-model="form.module" 
                                    type="text" 
                                    class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:ring-blue-500 text-sm"
                                    placeholder="e.g. Sales"
                                    :disabled="isEditing"
                                >
                                <p v-if="form.errors.module" class="mt-1 text-xs text-red-500">{{ form.errors.module }}</p>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">System Code</label>
                                <input 
                                    v-model="form.code" 
                                    type="text" 
                                    class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:ring-blue-500 text-sm font-mono"
                                    placeholder="e.g. sales_order"
                                    :disabled="isEditing"
                                >
                                <p v-if="form.errors.code" class="mt-1 text-xs text-red-500">{{ form.errors.code }}</p>
                            </div>
                        </div>

                        <!-- Display Name -->
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Display Name</label>
                            <input 
                                v-model="form.name" 
                                type="text" 
                                class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:ring-blue-500 text-sm"
                                placeholder="e.g. Sales Order"
                            >
                            <p v-if="form.errors.name" class="mt-1 text-xs text-red-500">{{ form.errors.name }}</p>
                        </div>

                        <!-- Prefix & Padding -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Prefix</label>
                                <input 
                                    v-model="form.prefix" 
                                    type="text" 
                                    class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:ring-blue-500 text-sm"
                                    placeholder="e.g. SO"
                                >
                                <p v-if="form.errors.prefix" class="mt-1 text-xs text-red-500">{{ form.errors.prefix }}</p>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Padding (Digits)</label>
                                <input 
                                    v-model="form.padding" 
                                    type="number" 
                                    min="2" max="10"
                                    class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:ring-blue-500 text-sm"
                                >
                            </div>
                        </div>

                        <!-- Reset Period & Next Number -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Reset Counter</label>
                                <select 
                                    v-model="form.reset_period"
                                    class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:ring-blue-500 text-sm"
                                >
                                    <option v-for="opt in resetPeriods" :key="opt.value" :value="opt.value">
                                        {{ opt.label }}
                                    </option>
                                </select>
                            </div>
                            <div v-if="isEditing">
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Current Counter</label>
                                <input 
                                    v-model.number="form.current_number" 
                                    type="number" 
                                    min="0"
                                    class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:ring-blue-500 text-sm"
                                >
                                <p class="mt-1 text-[10px] text-amber-500">Next # will be: <span class="font-bold">{{ (form.current_number || 0) + 1 }}</span></p>
                            </div>
                        </div>

                        <!-- Format Builder -->
                        <div>
                             <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Number Format</label>
                             <div class="relative">
                                 <input 
                                    id="formatInput"
                                    v-model="form.format" 
                                    type="text" 
                                    class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:ring-blue-500 font-mono text-sm pl-3 pr-10"
                                >
                             </div>
                             <div class="mt-2 flex flex-wrap gap-2">
                                 <button 
                                    type="button" 
                                    v-for="tag in availableTags" 
                                    :key="tag"
                                    @click="insertTag(tag)"
                                    class="px-2 py-1 text-[10px] bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 shadow-sm transition-all"
                                 >
                                     {{ tag }}
                                 </button>
                             </div>
                        </div>

                        <!-- Preview Box -->
                        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-100 dark:border-blue-900/50">
                            <p class="text-[10px] text-blue-600 dark:text-blue-300 uppercase tracking-widest font-bold mb-1">Visual Preview</p>
                            <p class="text-lg font-mono font-bold text-blue-700 dark:text-blue-200 break-all">
                                {{ preview }}
                            </p>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                            <button 
                                type="button" 
                                @click="showModal = false"
                                class="px-4 py-2 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors text-sm font-medium"
                            >
                                Cancel
                            </button>
                            <button 
                                type="submit" 
                                :disabled="form.processing"
                                class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-lg transition-all flex items-center gap-2 text-sm font-bold disabled:opacity-50"
                            >
                                <ArrowPathIcon v-if="form.processing" class="h-4 w-4 animate-spin" />
                                {{ isEditing ? 'Save Changes' : 'Register Document' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
