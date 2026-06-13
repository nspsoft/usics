<script setup>
import { ref, watch, computed, onUnmounted } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';
import { 
    CloudArrowUpIcon, 
    DocumentTextIcon, 
    ArrowPathIcon,
    CheckCircleIcon, 
    ExclamationTriangleIcon,
    MagnifyingGlassIcon,
    TrashIcon,
    ArrowDownTrayIcon,
    PhotoIcon,
    SparklesIcon,
    EyeIcon,
    ArrowsPointingOutIcon,
    XMarkIcon,
    PencilIcon,
    PlusIcon,
    ChevronLeftIcon,
    CalendarIcon,
    BuildingStorefrontIcon,
    HashtagIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    suppliers: Array,
});

const file = ref(null);
const fileInput = ref(null);
const filePreviewUrl = ref(null);
const isUploading = ref(false);
const isAnalyzing = ref(false);
const currentStep = ref(1); // 1: Upload, 2: Processing, 3: Review, 4: Completion
const error = ref(null);

// Stepper Configuration
const steps = [
    { id: 1, name: 'Upload', description: 'Upload DN document' },
    { id: 2, name: 'Processing', description: 'AI analyzing' },
    { id: 3, name: 'Review', description: 'Verify & Match' },
    { id: 4, name: 'Completion', description: 'Goods Receipt' }
];

// Data structure for review
const editableData = ref({
    supplier_id: '',
    supplier_name: '',
    po_number: '',
    po_id: '',
    dn_number: '',
    date: new Date().toISOString().split('T')[0],
    items: [],
});

const matchedPO = ref(null);

// Cleanup preview URL
onUnmounted(() => {
    if (filePreviewUrl.value) URL.revokeObjectURL(filePreviewUrl.value);
});

// Computed for file type detection
const fileType = computed(() => {
    if (!file.value) return null;
    const type = file.value.type;
    if (type === 'application/pdf') return 'pdf';
    if (type.startsWith('image/')) return 'image';
    return 'unknown';
});

const handleFileSelect = (event) => {
    const selected = event.target.files?.[0] || event.dataTransfer?.files?.[0];
    if (selected) processFile(selected);
};

const handleDrop = (event) => {
    const dropped = event.dataTransfer?.files?.[0];
    if (dropped) processFile(dropped);
};

const processFile = (fileObj) => {
    if (!['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'].includes(fileObj.type)) {
        error.value = 'Invalid file format. Please upload Image or PDF.';
        return;
    }
    if (fileObj.size > 5 * 1024 * 1024) {
        error.value = 'File too large (Max 5MB).';
        return;
    }

    if (filePreviewUrl.value) URL.revokeObjectURL(filePreviewUrl.value);
    
    file.value = fileObj;
    filePreviewUrl.value = URL.createObjectURL(fileObj);
    error.value = null;
};

const removeFile = () => {
    file.value = null;
    if (fileInput.value) fileInput.value.value = null;
    if (filePreviewUrl.value) {
        URL.revokeObjectURL(filePreviewUrl.value);
        filePreviewUrl.value = null;
    }
};

const reset = () => {
    removeFile();
    error.value = null;
    currentStep.value = 1;
    editableData.value = {
        supplier_id: '',
        supplier_name: '',
        po_number: '',
        po_id: '',
        dn_number: '',
        date: new Date().toISOString().split('T')[0],
        items: [],
    };
    matchedPO.value = null;
};

const analyzeFile = async () => {
    if (!file.value) return;

    currentStep.value = 2;
    isAnalyzing.value = true;
    error.value = null;

    const formData = new FormData();
    formData.append('file', file.value);

    try {
        const response = await axios.post(route('purchasing.dn-extractor.extract', undefined, false), formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        
        const extracted = response.data.extracted_data;
        const po = response.data.matched_po;

        // Auto-fill Header
        editableData.value.dn_number = extracted.dn_number || '';
        editableData.value.date = extracted.date || new Date().toISOString().split('T')[0];
        editableData.value.po_number = extracted.po_number || (po ? po.po_number : '');
        editableData.value.supplier_name = extracted.supplier_name || '';

        // Match Supplier
        if (extracted.supplier_name) {
             const supplier = props.suppliers.find(s => 
                s.name.toLowerCase().includes(extracted.supplier_name.toLowerCase()) || 
                extracted.supplier_name.toLowerCase().includes(s.name.toLowerCase())
            );
            if (supplier) editableData.value.supplier_id = supplier.id;
        }

        // Match Items
        editableData.value.items = (extracted.items || []).map(item => ({
            description: item.description,
            scanned_qty: item.qty || 0,
            scanned_unit: item.unit || '',
            purchase_order_item_id: '',
            qty_received: item.qty || 0, // Default to scanned qty
            notes: item.remarks || '',
            match_status: 'unmatched', // unmatched, matched, partial
        }));

        if (po) {
            matchedPO.value = po;
            editableData.value.po_id = po.id;
            // Run matching logic
            autoMatchItems(editableData.value.items, po.items);
        }

        currentStep.value = 3;
    } catch (err) {
        console.error(err);
        error.value = err.response?.data?.message || 'Failed to analyze document.';
        currentStep.value = 1;
    } finally {
        isAnalyzing.value = false;
    }
};

const autoMatchItems = (scannedItems, poItems) => {
    scannedItems.forEach(item => {
        const desc = item.description.toLowerCase();
        // Priority: Exact match -> Contains -> Fuzzy
        const match = poItems.find(poItem => {
            const poDesc = (poItem.product?.name || poItem.description || '').toLowerCase();
            return poDesc.includes(desc) || desc.includes(poDesc);
        });

        if (match) {
            item.purchase_order_item_id = match.id;
            item.match_status = 'matched';
            if (item.qty_received > match.qty) {
                item.match_status = 'warning';
            }
        }
    });
};

const addItemRow = () => {
    editableData.value.items.push({
        description: '',
        scanned_qty: 0,
        scanned_unit: 'Pcs',
        purchase_order_item_id: '',
        qty_received: 0,
        notes: '',
        match_status: 'unmatched'
    });
};

const removeItemRow = (idx) => {
    editableData.value.items.splice(idx, 1);
};

const submitInfo = computed(() => {
    const validItems = editableData.value.items.filter(i => i.purchase_order_item_id && i.qty_received > 0);
    return {
        count: validItems.length,
        ready: !!editableData.value.po_id && !!editableData.value.dn_number && validItems.length > 0
    };
});

const createGR = async () => {
    if (!submitInfo.value.ready) return;
    
    if (!confirm('Create Goods Receipt for these items?')) return;

    try {
        const payload = {
            purchase_order_id: editableData.value.po_id,
            dn_number: editableData.value.dn_number,
            date: editableData.value.date,
            items: editableData.value.items
                .filter(i => i.purchase_order_item_id)
                .map(i => ({
                    purchase_order_item_id: i.purchase_order_item_id,
                    qty_received: i.qty_received,
                    notes: i.notes
                }))
        };

        const response = await axios.post(route('purchasing.dn-extractor.store-gr', undefined, false), payload);
        
        if (response.data.redirect_url) {
            currentStep.value = 4; // Complete
            setTimeout(() => {
                window.location.href = response.data.redirect_url;
            }, 1000);
        }
    } catch (err) {
        alert(err.response?.data?.message || 'Failed to create GR');
    }
};

const triggerFileInput = () => {
    if (fileInput.value) fileInput.value.click();
}
</script>

<template>
    <Head title="AI Delivery Note Extractor" />

    <AppLayout title="AI Delivery Note Extractor">
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 pb-2">
                <div class="flex items-center gap-4">
                    <button 
                        v-if="currentStep > 1"
                        @click="reset"
                        class="p-2.5 rounded-2xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-500 transition-colors"
                        title="Start Over"
                    >
                        <ChevronLeftIcon class="h-6 w-6" />
                    </button>
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 shadow-lg shadow-blue-500/20">
                            <SparklesIcon class="h-5 w-5 text-white" />
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-900 dark:text-white leading-tight">
                                AI Delivery Note Extractor
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Powered by Gemini AI - AI Powered Recognition
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Stepper -->
                <nav aria-label="Progress">
                    <ol role="list" class="flex items-center">
                        <li v-for="(s, stepIdx) in steps" :key="s.name" :class="['relative', stepIdx !== steps.length - 1 ? 'pr-8 sm:pr-20' : '']">
                            <div class="group flex items-center">
                                <!-- Step circle -->
                                <div 
                                    :class="[
                                        'relative z-10 flex h-10 w-10 items-center justify-center rounded-full border-2 transition-all duration-300',
                                        currentStep > s.id 
                                            ? 'border-emerald-500 bg-emerald-500 text-white' 
                                            : currentStep === s.id 
                                                ? 'border-blue-500 bg-blue-500 text-white' 
                                                : 'border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-500'
                                    ]"
                                >
                                    <CheckCircleIcon v-if="currentStep > s.id" class="h-5 w-5" />
                                    <span v-else class="text-sm font-bold">{{ s.id }}</span>
                                </div>
                                <!-- Step info -->
                                <div class="ml-3 hidden sm:block font-sans">
                                    <p :class="['text-sm font-bold', currentStep >= s.id ? 'text-slate-900 dark:text-white' : 'text-slate-400']">
                                        {{ s.name }}
                                    </p>
                                    <p class="text-xs text-slate-500">{{ s.description }}</p>
                                </div>
                            </div>
                            <!-- Connector line -->
                            <div 
                                v-if="stepIdx !== steps.length - 1" 
                                :class="[
                                    'absolute top-5 left-10 -ml-px h-0.5 w-full transition-colors duration-300',
                                    currentStep > s.id ? 'bg-emerald-500' : 'bg-slate-200 dark:bg-slate-700'
                                ]"
                            />
                        </li>
                    </ol>
                </nav>
            </div>

            <!-- Main Content Area -->
            <div :class="['grid gap-6', currentStep >= 3 && filePreviewUrl ? 'lg:grid-cols-2' : 'lg:grid-cols-1 max-w-4xl mx-auto']">
                
                <div v-if="currentStep >= 3 && filePreviewUrl" class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 shadow-xl h-fit sticky top-6 transition-all duration-500 animate-in fade-in slide-in-from-left-4">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <EyeIcon class="h-5 w-5 text-slate-500" />
                            <span class="text-sm font-bold text-slate-700 dark:text-slate-300">Original Document</span>
                        </div>
                        <a :href="filePreviewUrl" target="_blank" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" title="Open in new window">
                            <ArrowsPointingOutIcon class="h-5 w-5" />
                        </a>
                    </div>
                    <div class="rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden bg-slate-100 dark:bg-slate-800 shadow-inner">
                         <iframe 
                            v-if="fileType === 'pdf'"
                            :src="filePreviewUrl"
                            class="w-full h-[650px]"
                            title="DN Document Preview"
                        ></iframe>
                        <img 
                            v-else
                            :src="filePreviewUrl"
                            class="w-full h-[650px] object-contain"
                            alt="DN Document Preview"
                        />
                    </div>
                </div>

                <!-- Right: Action Area -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 shadow-xl overflow-hidden">
                    
                    <!-- Step 1: Upload Logic -->
                    <div v-if="currentStep === 1" class="flex flex-col items-center py-8">
                        <div 
                            @click="triggerFileInput"
                            @dragover.prevent
                            @drop.prevent="handleFileSelect"
                            class="w-full h-72 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-3xl flex flex-col items-center justify-center gap-4 cursor-pointer hover:border-blue-500/50 hover:bg-blue-500/5 transition-all group relative overflow-hidden"
                        >
                            <div class="p-5 rounded-full bg-slate-50 dark:bg-slate-800 group-hover:bg-blue-500/10 transition-colors">
                                <CloudArrowUpIcon class="h-12 w-12 text-slate-400 group-hover:text-blue-500 transition-colors" />
                            </div>
                            <div class="text-center">
                                <p class="text-lg font-bold text-slate-700 dark:text-slate-200">
                                    {{ file ? file.name : 'Click to upload or drag & drop' }}
                                </p>
                                <p class="text-sm text-slate-500 mt-1">PDF or Image (Max 5MB)</p>
                            </div>
                            <input 
                                ref="fileInput" 
                                type="file" 
                                class="hidden" 
                                accept="application/pdf,image/png,image/jpeg,image/jpg" 
                                @change="handleFileSelect" 
                            />
                        </div>

                        <div v-if="error" class="mt-6 w-full p-4 rounded-xl bg-red-500/10 border border-red-500/20 flex items-center gap-3 text-red-500 text-sm font-medium">
                            <ExclamationTriangleIcon class="h-5 w-5 shrink-0" />
                            <span>{{ error }}</span>
                        </div>

                        <button 
                            @click="analyzeFile"
                            :disabled="!file || isUploading"
                            class="mt-8 w-full py-4 px-6 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold shadow-lg shadow-blue-500/25 dark:shadow-blue-500/10 hover:shadow-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-3 text-lg"
                        >
                            <SparklesIcon class="h-6 w-6" />
                            Start AI Extraction
                        </button>
                        
                        <div class="mt-6 text-center text-xs text-slate-400 italic">
                            Tip: Best results come from high-resolution images or clear PDF documents.
                        </div>
                    </div>

                    <!-- Step 2: Processing Animation -->
                    <div v-if="currentStep === 2" class="flex flex-col items-center py-16">
                        <div class="relative w-28 h-28 mb-10">
                            <div class="absolute inset-0 border-4 border-slate-100 dark:border-slate-800 rounded-full"></div>
                            <div class="absolute inset-0 border-4 border-blue-500 rounded-full border-t-transparent animate-spin"></div>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <SparklesIcon class="h-10 w-10 text-amber-500 animate-pulse" />
                            </div>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white">AI is reading your document...</h3>
                        <p class="mt-3 text-sm text-slate-500 text-center max-w-sm px-6">
                            Gemini is identifying supplier details, PO numbers, and matching line items.
                        </p>
                        
                        <div class="mt-12 w-full max-w-xs space-y-4">
                             <div class="flex items-center gap-3">
                                <CheckCircleIcon class="h-6 w-6 text-emerald-500" />
                                <span class="text-sm font-semibold text-slate-400">File Uploaded</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <ArrowPathIcon class="h-6 w-6 text-blue-500 animate-spin" />
                                <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">Processing with Vision AI</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="h-6 w-6 rounded-full border-2 border-slate-200 dark:border-slate-700"></div>
                                <span class="text-sm font-semibold text-slate-400">Finalizing Data</span>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Review & Edit -->
                    <div v-if="currentStep === 3" class="space-y-6 animate-in fade-in duration-500">
                        
                        <!-- Success Banner -->
                        <div class="bg-emerald-500/10 border border-emerald-500/20 p-4 rounded-2xl flex items-center gap-3 text-emerald-500">
                            <CheckCircleIcon class="h-6 w-6" />
                            <div class="text-sm font-bold">Extraction Successful! AI found {{ editableData.items.length }} items. You can edit the data below.</div>
                        </div>

                        <!-- Info Header Grid (Refined to 3 columns) -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="space-y-2">
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest flex items-center gap-1 ml-1">
                                    <PencilIcon class="h-3 w-3" />
                                    Supplier
                                </label>
                                <select v-model="editableData.supplier_id" class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-sm font-medium focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                                    <option value="">Select Supplier...</option>
                                    <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest flex items-center gap-1 ml-1">
                                    <PencilIcon class="h-3 w-3" />
                                    DN Number
                                </label>
                                <input v-model="editableData.dn_number" type="text" class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-sm font-medium focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all" placeholder="Enter DN Number...">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest flex items-center gap-1 ml-1">
                                    <PencilIcon class="h-3 w-3" />
                                    Receipt Date
                                </label>
                                <input v-model="editableData.date" type="date" class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-sm font-medium focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                            </div>
                        </div>

                        <!-- Reference PO Box (Centered) -->
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest flex items-center gap-1.5 ml-1">
                                <DocumentTextIcon class="h-3 w-3" />
                                Reference Purchase Order (PO)
                            </label>
                            <div v-if="matchedPO" class="flex items-center justify-between px-3 py-2 rounded-xl bg-blue-500/5 border border-blue-500/20 group">
                                <div class="flex items-center gap-3">
                                    <CheckCircleIcon class="h-4 w-4 text-blue-500" />
                                    <span class="text-xs font-bold text-blue-500">{{ matchedPO.po_number }}</span>
                                    <span class="text-[10px] text-slate-400">Matched PO</span>
                                </div>
                                <button @click="matchedPO = null; editableData.po_id = ''" class="p-1 text-slate-300 hover:text-red-500 transition-colors">
                                    <XMarkIcon class="h-3 w-3" />
                                </button>
                            </div>
                            <div v-else class="px-3 py-2 rounded-xl border border-dashed border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex items-center gap-2">
                                <ExclamationTriangleIcon class="h-3 w-3 text-amber-500" />
                                <p class="text-[10px] font-bold text-slate-500">No PO Matched Automatically</p>
                            </div>
                        </div>

                        <!-- Items Table (Fixed Header Pattern) -->
                        <div class="rounded-2xl border border-slate-200 dark:border-slate-700 flex flex-col relative overflow-hidden bg-white dark:bg-slate-900 shadow-lg">
                            <!-- Header -->
                            <div class="bg-slate-100 dark:bg-slate-800">
                                <table class="min-w-full table-fixed">
                                    <thead>
                                        <tr>
                                            <th class="px-2 py-3 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider w-[6%]">No</th>
                                            <th class="px-2 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider w-[34%]">Description</th>
                                            <th class="px-2 py-3 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider w-[10%]">Qty</th>
                                            <th class="px-2 py-3 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider w-[34%]">Match PO Item</th>
                                            <th class="px-2 py-3 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider w-[10%]">Confirm</th>
                                            <th class="px-2 py-3 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider w-[6%]">Act</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                            
                            <!-- Body (Scrollable) -->
                            <div class="overflow-y-auto max-h-[400px] flex-1">
                                <table class="min-w-full table-fixed">
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                        <tr v-for="(item, idx) in editableData.items" :key="idx" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                            <!-- No -->
                                            <td class="px-2 py-3 text-center w-[6%]">
                                                <span class="text-xs font-bold text-slate-300">{{ idx + 1 }}</span>
                                            </td>
                                            <!-- Description -->
                                            <td class="px-2 py-3 w-[34%]">
                                                <input v-model="item.description" type="text" class="w-full px-2 py-1.5 rounded-lg bg-transparent border-0 focus:ring-0 text-xs font-medium text-slate-700 dark:text-slate-300">
                                            </td>
                                            <!-- Scanned Qty -->
                                            <td class="px-2 py-3 text-center w-[10%]">
                                                <span class="text-xs font-mono font-bold text-slate-400">{{ item.scanned_qty }}</span>
                                            </td>
                                            <!-- PO Item Selection -->
                                            <td class="px-2 py-3 w-[34%]">
                                                <select v-model="item.purchase_order_item_id" class="w-full px-2 py-1.5 rounded-lg bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-slate-700 text-[10px] font-bold text-slate-600 dark:text-slate-400 focus:ring-2 focus:ring-blue-500/20 outline-none appearance-none">
                                                    <option value="">-- Manual Match --</option>
                                                    <template v-if="matchedPO">
                                                        <option v-for="poItem in matchedPO.items" :key="poItem.id" :value="poItem.id">
                                                            {{ poItem.product?.name || poItem.description }} (Ord: {{ poItem.qty }})
                                                        </option>
                                                    </template>
                                                </select>
                                                <!-- Match Status Indicator -->
                                                <div v-if="item.purchase_order_item_id" class="mt-1 flex items-center gap-1 ml-1">
                                                    <span class="text-[9px] font-bold text-emerald-500 uppercase tracking-tight flex items-center gap-0.5">
                                                        <CheckCircleIcon class="h-2.5 w-2.5" />
                                                        Ready
                                                    </span>
                                                </div>
                                            </td>
                                            <!-- Receive Qty -->
                                            <td class="px-2 py-3 text-center w-[10%]">
                                                <input v-model.number="item.qty_received" type="number" class="w-full px-1 py-1.5 rounded-lg bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-900 dark:text-white text-center focus:ring-2 focus:ring-emerald-500/20 outline-none">
                                            </td>
                                            <!-- Action -->
                                            <td class="px-2 py-3 text-center w-[6%]">
                                                <button @click="removeItemRow(idx)" class="p-1.5 text-slate-300 hover:text-red-500 transition-colors">
                                                    <TrashIcon class="h-4 w-4" />
                                                </button>
                                            </td>
                                        </tr>
                                        <!-- Empty State -->
                                        <tr v-if="editableData.items.length === 0">
                                            <td colspan="6" class="px-6 py-12 text-center">
                                                <div class="flex flex-col items-center">
                                                    <DocumentTextIcon class="h-10 w-10 text-slate-200 mb-2" />
                                                    <p class="text-sm font-medium text-slate-400">No items detected.</p>
                                                    <button @click="addItemRow" class="mt-2 text-xs text-blue-500 hover:underline">Add row manually</button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Add Row Footer -->
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700">
                                <button 
                                    @click="addItemRow"
                                    class="w-full py-2.5 rounded-xl border-2 border-dashed border-slate-300 dark:border-slate-600 text-slate-500 hover:border-blue-500 hover:text-blue-500 transition-all flex items-center justify-center gap-2 text-sm font-medium"
                                >
                                    <PlusIcon class="h-4 w-4" />
                                    Add Item
                                </button>
                            </div>
                        </div>

                        <!-- Info Note -->
                        <div class="p-4 rounded-xl bg-blue-500/10 border border-blue-500/20 text-sm text-blue-600 dark:text-blue-400">
                            <strong>Edit Mode:</strong> Anda dapat mengedit semua data di atas sebelum membuat Goods Receipt. Perubahan yang Anda buat akan langsung digunakan untuk pembuatan dokumen penerimaan.
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-wrap items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                            <button 
                                @click="reset"
                                class="px-6 py-3 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold hover:bg-slate-200 transition-all font-sans"
                            >
                                Try Again
                            </button>
                            
                            <button 
                                @click="createGR"
                                :disabled="!submitInfo.ready"
                                class="px-8 py-3 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold shadow-lg shadow-blue-500/25 dark:shadow-blue-500/10 hover:shadow-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-3"
                            >
                                <SparklesIcon class="h-5 w-5" />
                                Create Goods Receipt
                            </button>
                        </div>
                    </div>

                    <!-- Step 4: Completion -->
                     <div v-if="currentStep === 4" class="flex flex-col items-center justify-center min-h-[50vh] text-center p-8">
                        <div class="w-24 h-24 bg-emerald-500/20 rounded-full flex items-center justify-center mb-8 relative">
                            <CheckCircleIcon class="w-14 h-14 text-emerald-500" />
                            <div class="absolute inset-0 bg-emerald-500 rounded-full animate-ping opacity-20"></div>
                        </div>
                        <h2 class="text-3xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-emerald-400 to-teal-500 mb-2">GR Successfully Created!</h2>
                        <p class="text-slate-500 max-w-sm font-medium">Redirecting you to the Goods Receipt details page. Great work!</p>
                        
                        <div class="mt-12 flex justify-center gap-2">
                            <div v-for="i in 3" :key="i" class="h-1.5 w-1.5 bg-emerald-500/50 rounded-full animate-bounce" :style="`animation-delay: ${i*200}ms`"></div>
                        </div>
                    </div>

                </div>
            </div>
            
            <!-- Footer Info Hint -->
            <div v-if="currentStep === 1" class="max-w-4xl mx-auto flex items-center justify-between px-4 opacity-50">
                 <div class="flex items-center gap-2 text-xs text-slate-400 font-bold uppercase tracking-tighter">
                    <PhotoIcon class="h-4 w-4" />
                    IMAGE RECOGNITION ACTIVE
                 </div>
                 <div class="flex items-center gap-2 text-xs text-slate-400 font-bold uppercase tracking-tighter">
                    GEMINI 1.5 PRO READY
                    <div class="h-1.5 w-1.5 bg-blue-500 rounded-full animate-pulse"></div>
                 </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.bg-grid-slate-100 {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32' width='32' height='32' fill='none' stroke='rgb(241 245 249 / 0.1)'%3E%3Cpath d='M0 .5H31.5V32'/%3E%3C/svg%3E");
}
.dark .bg-grid-slate-700 {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/xml' viewBox='0 0 32 32' width='32' height='32' fill='none' stroke='rgb(51 65 85 / 0.1)'%3E%3Cpath d='M0 .5H31.5V32'/%3E%3C/svg%3E");
}

/* Custom scrollbar for items list */
::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
::-webkit-scrollbar-track {
    background: transparent;
}
::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}
.dark ::-webkit-scrollbar-thumb {
    background: #334155;
}
::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>
