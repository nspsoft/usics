<script setup>
import { ref, computed } from 'vue';
import { 
    XMarkIcon, 
    CloudArrowUpIcon, 
    SparklesIcon,
    DocumentIcon,
    ArrowPathIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    QuestionMarkCircleIcon,
    ChartBarSquareIcon,
    CubeIcon,
    ShoppingCartIcon,
    WrenchScrewdriverIcon,
    ArrowRightIcon,
    EyeIcon,
    ArrowsPointingOutIcon,
} from '@heroicons/vue/24/outline';
import axios from 'axios';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    show: Boolean,
    customers: Array
});

const emit = defineEmits(['close']);

const file = ref(null);
const fileInput = ref(null);
const filePreviewUrl = ref(null);
const isUploading = ref(false);
const isAnalyzing = ref(false);
const error = ref(null);
const extractionResult = ref(null);
const fulfillmentAnalysis = ref(null);
const currentStep = ref(1); // 1: Upload, 2: Processing, 3: Validation, 4: Fulfillment Analysis

// Computed for file type detection
const fileType = computed(() => {
    if (!file.value) return null;
    const type = file.value.type;
    if (type === 'application/pdf') return 'pdf';
    if (type.startsWith('image/')) return 'image';
    return 'unknown';
});

// Create preview URL for the uploaded file
const createPreviewUrl = () => {
    if (file.value) {
        // Revoke previous URL if exists
        if (filePreviewUrl.value) {
            URL.revokeObjectURL(filePreviewUrl.value);
        }
        filePreviewUrl.value = URL.createObjectURL(file.value);
    }
};

// Revoke preview URL to prevent memory leaks
const revokePreviewUrl = () => {
    if (filePreviewUrl.value) {
        URL.revokeObjectURL(filePreviewUrl.value);
        filePreviewUrl.value = null;
    }
};

const handleFileSelect = (e) => {
    const selectedFile = e.target.files?.[0] || e.dataTransfer?.files?.[0];
    if (selectedFile) {
        file.value = selectedFile;
        error.value = null;
        createPreviewUrl();
    }
};

const triggerFileInput = () => {
    fileInput.value.click();
};

const uploadPO = async () => {
    if (!file.value) return;

    isUploading.value = true;
    error.value = null;
    currentStep.value = 2;

    const formData = new FormData();
    formData.append('file', file.value);

    try {
        const response = await axios.post('/sales/orders/ai-extract', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });

        if (response.data.success) {
            extractionResult.value = response.data.data;
            currentStep.value = 3;
        }
    } catch (err) {
        currentStep.value = 1;
        error.value = err.response?.data?.message || 'Failed to process PO. Please try again.';
    } finally {
        isUploading.value = false;
    }
};

const createDraftSO = () => {
    // Navigate to create SO with pre-filled data via query params or session
    // For simplicity, we'll use session flash or just pass data via router
    router.post('/sales/orders/create-from-ai', {
        data: extractionResult.value
    });
};

const reset = () => {
    revokePreviewUrl();
    file.value = null;
    error.value = null;
    extractionResult.value = null;
    fulfillmentAnalysis.value = null;
    currentStep.value = 1;
};

const analyzeFulfillment = async () => {
    if (!extractionResult.value?.items) return;
    
    isAnalyzing.value = true;
    error.value = null;
    
    try {
        // Prepare items for analysis
        const itemsForAnalysis = extractionResult.value.items.map(item => ({
            product_id: item.matched_product?.id || null,
            product_name: item.description,
            qty: item.qty
        }));
        
        const response = await axios.post('/sales/orders/analyze-fulfillment', {
            items: itemsForAnalysis
        });
        
        if (response.data.success) {
            fulfillmentAnalysis.value = response.data;
            currentStep.value = 4;
        }
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to analyze fulfillment. Please try again.';
    } finally {
        isAnalyzing.value = false;
    }
};

const getPriorityColor = (priority) => {
    switch (priority) {
        case 'green': return 'bg-emerald-500';
        case 'yellow': return 'bg-amber-500';
        case 'red': return 'bg-red-500';
        default: return 'bg-slate-500';
    }
};

const getPriorityBg = (priority) => {
    switch (priority) {
        case 'green': return 'bg-emerald-500/10 border-emerald-500/20';
        case 'yellow': return 'bg-amber-500/10 border-amber-500/20';
        case 'red': return 'bg-red-500/10 border-red-500/20';
        default: return 'bg-slate-500/10 border-slate-500/20';
    }
};

const close = () => {
    reset();
    emit('close');
};
</script>

<template>
    <Transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="close"></div>

            <!-- Modal Content -->
            <div class="flex min-h-full items-center justify-center p-4">
                <div :class="['relative w-full bg-white dark:bg-slate-900 rounded-3xl shadow-2xl overflow-hidden border border-slate-200 dark:border-slate-800 transition-all duration-300', (currentStep === 3 || currentStep === 4) && filePreviewUrl ? 'max-w-6xl' : 'max-w-2xl']">
                    
                    <!-- Header -->
                    <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/50">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 rounded-2xl bg-amber-500/10 text-amber-500">
                                <SparklesIcon class="h-6 w-6" />
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white">AI Purchase Order Extractor</h3>
                                <p class="text-xs text-slate-500 mt-0.5">Powered by Gemini AI</p>
                            </div>
                        </div>
                        <button @click="close" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            <XMarkIcon class="h-6 w-6" />
                        </button>
                    </div>

                    <!-- Body -->
                    <div :class="['p-6', (currentStep === 3 || currentStep === 4) && filePreviewUrl ? 'flex gap-6' : '']">
                        <!-- Document Preview Panel (Left side, only for Steps 3-4) -->
                        <div v-if="(currentStep === 3 || currentStep === 4) && filePreviewUrl" class="w-1/2 flex flex-col">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <EyeIcon class="h-4 w-4 text-slate-500" />
                                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Original Document</span>
                                </div>
                                <a 
                                    :href="filePreviewUrl" 
                                    target="_blank"
                                    class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                                    title="Open in new tab"
                                >
                                    <ArrowsPointingOutIcon class="h-4 w-4" />
                                </a>
                            </div>
                            <div class="flex-1 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden bg-slate-100 dark:bg-slate-800">
                                <!-- PDF Preview -->
                                <iframe 
                                    v-if="fileType === 'pdf'"
                                    :src="filePreviewUrl"
                                    class="w-full h-full min-h-[500px]"
                                    title="PO Document Preview"
                                ></iframe>
                                <!-- Image Preview -->
                                <img 
                                    v-else-if="fileType === 'image'"
                                    :src="filePreviewUrl"
                                    class="w-full h-full object-contain min-h-[500px]"
                                    alt="PO Document Preview"
                                />
                                <!-- Unknown type fallback -->
                                <div v-else class="w-full h-full min-h-[500px] flex items-center justify-center text-slate-400">
                                    <div class="text-center">
                                        <DocumentIcon class="h-12 w-12 mx-auto mb-2" />
                                        <p class="text-sm">Preview not available</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Main Content (Right side when preview visible, full width otherwise) -->
                        <div :class="[(currentStep === 3 || currentStep === 4) && filePreviewUrl ? 'w-1/2' : 'w-full']">
                            <!-- Step 1: Upload -->
                            <div v-if="currentStep === 1" class="flex flex-col items-center py-4">
                                <div 
                                    @click="triggerFileInput"
                                    @dragover.prevent
                                    @drop.prevent="handleFileSelect"
                                    class="w-full h-48 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-3xl flex flex-col items-center justify-center gap-4 cursor-pointer hover:border-blue-500/50 hover:bg-blue-500/5 transition-all group"
                                >
                                    <div class="p-4 rounded-full bg-slate-50 dark:bg-slate-800 group-hover:bg-blue-500/10 transition-colors">
                                        <CloudArrowUpIcon class="h-10 w-10 text-slate-400 group-hover:text-blue-500 transition-colors" />
                                    </div>
                                    <div class="text-center">
                                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                                            {{ file ? file.name : 'Click to upload or drag & drop' }}
                                        </p>
                                        <p class="text-xs text-slate-500 mt-1">PDF or Image (Max 5MB)</p>
                                    </div>
                                    <input 
                                        ref="fileInput"
                                        type="file" 
                                        class="hidden" 
                                        accept=".pdf,image/*"
                                        @change="handleFileSelect"
                                    />
                                </div>

                                <div v-if="error" class="mt-4 w-full p-4 rounded-xl bg-red-500/10 border border-red-500/20 flex items-center gap-3 text-red-500">
                                    <ExclamationTriangleIcon class="h-5 w-5 shrink-0" />
                                    <span class="text-sm font-medium">{{ error }}</span>
                                </div>

                                <button 
                                    @click="uploadPO"
                                    :disabled="!file || isUploading"
                                    class="mt-8 w-full py-3 px-6 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold shadow-lg shadow-blue-500/25 dark:shadow-blue-500/10 hover:shadow-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                                >
                                    <SparklesIcon class="h-5 w-5" />
                                    Start AI Extraction
                                </button>
                            </div>

                            <!-- Step 2: Processing -->
                            <div v-if="currentStep === 2" class="flex flex-col items-center py-12">
                                <div class="relative">
                                    <div class="w-24 h-24 rounded-full border-4 border-slate-100 dark:border-slate-800 border-t-blue-500 animate-spin"></div>
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <SparklesIcon class="h-8 w-8 text-amber-500 animate-pulse" />
                                    </div>
                                </div>
                                <h4 class="mt-8 text-lg font-bold text-slate-900 dark:text-white">AI is reading your document...</h4>
                                <p class="mt-2 text-sm text-slate-500 text-center max-w-xs">
                                    Gemini is identifying products, quantities, and customer details. Please wait a moment.
                                </p>
                                
                                <!-- Fake progress steps -->
                                <div class="mt-10 w-full max-w-xs space-y-3">
                                    <div class="flex items-center gap-3">
                                        <CheckCircleIcon class="h-5 w-5 text-emerald-500" />
                                        <span class="text-xs font-semibold text-slate-400">File Uploaded</span>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <ArrowPathIcon class="h-5 w-5 text-blue-500 animate-spin" />
                                        <span class="text-xs font-semibold text-slate-700 dark:text-slate-200">Processing Multimodal Vision</span>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <div class="h-5 w-5 rounded-full border-2 border-slate-200 dark:border-slate-800"></div>
                                        <span class="text-xs font-semibold text-slate-400">Synchronizing with USICS Catalog</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 3: Success & Preview -->
                            <div v-if="currentStep === 3" class="space-y-4">
                                <div class="bg-emerald-500/10 border border-emerald-500/20 p-3 rounded-2xl flex items-center gap-3 text-emerald-500">
                                    <CheckCircleIcon class="h-5 w-5" />
                                    <div class="text-sm font-bold">Extraction Successful! AI found {{ extractionResult.items?.length }} items.</div>
                                </div>

                            <!-- Preview Section -->
                            <div class="grid grid-cols-2 gap-4">
                                <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Customer PO Number</span>
                                    <div class="mt-1 font-bold text-slate-900 dark:text-white">{{ extractionResult.po_number || 'Not found' }}</div>
                                </div>
                                <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Customer Name</span>
                                    <div class="mt-1 font-bold text-slate-900 dark:text-white">
                                        {{ extractionResult.matched_customer_name || extractionResult.customer_name || 'Not found' }}
                                        <span v-if="extractionResult.matched_customer_id" class="ml-2 text-[10px] bg-emerald-500 text-white px-1.5 py-0.5 rounded-md">MATCHED</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Items Table -->
                            <div class="border border-slate-100 dark:border-slate-800 rounded-2xl overflow-hidden overflow-x-auto">
                                <table class="w-full text-left text-sm">
                                    <thead class="bg-slate-50 dark:bg-slate-800/80">
                                        <tr>
                                            <th class="px-3 py-3 font-bold text-slate-500 uppercase text-[10px]">Description</th>
                                            <th class="px-3 py-3 font-bold text-slate-500 uppercase text-[10px]">Qty</th>
                                            <th class="px-3 py-3 font-bold text-slate-500 uppercase text-[10px]">USICS Match</th>
                                            <th class="px-3 py-3 font-bold text-slate-500 uppercase text-[10px]">Price Comparison</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                        <tr v-for="(item, idx) in extractionResult.items" :key="idx" class="dark:text-slate-300">
                                            <td class="px-3 py-3 text-xs max-w-[150px]">{{ item.description }}</td>
                                            <td class="px-3 py-3 font-bold capitalize whitespace-nowrap">{{ item.qty }} {{ item.unit }}</td>
                                            <td class="px-3 py-3">
                                                <div v-if="item.matched_product_id" class="flex flex-col">
                                                    <span class="text-[10px] font-bold text-emerald-500">{{ item.matched_sku }}</span>
                                                    <span class="text-[10px] text-slate-500 truncate max-w-[120px]">{{ item.matched_product_name }}</span>
                                                </div>
                                                <div v-else class="text-[10px] font-bold text-red-500">NO MATCH</div>
                                            </td>
                                            <td class="px-3 py-3">
                                                <div v-if="item.matched_product_id" class="space-y-1">
                                                    <!-- Price from PO -->
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-[9px] text-slate-400 w-8">PO:</span>
                                                        <span class="text-[11px] font-semibold" :class="item.price_mismatch ? 'text-amber-500' : 'text-slate-600 dark:text-slate-300'">
                                                            {{ item.po_price ? 'Rp ' + Number(item.po_price).toLocaleString('id-ID') : '-' }}
                                                        </span>
                                                    </div>
                                                    <!-- Price from DB -->
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-[9px] text-slate-400 w-8">DB:</span>
                                                        <span class="text-[11px] font-bold text-emerald-600">
                                                            Rp {{ Number(item.db_price || 0).toLocaleString('id-ID') }}
                                                        </span>
                                                    </div>
                                                    <!-- Mismatch Warning -->
                                                    <div v-if="item.price_mismatch" class="flex items-center gap-1 mt-1">
                                                        <span class="text-[9px] bg-amber-500/20 text-amber-600 px-1.5 py-0.5 rounded font-bold">⚠ SELISIH</span>
                                                    </div>
                                                </div>
                                                <div v-else class="text-[10px] text-slate-400">-</div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Price Note -->
                            <div class="mt-3 p-3 rounded-xl bg-blue-500/10 border border-blue-500/20 text-xs text-blue-600 dark:text-blue-400">
                                💡 <strong>Catatan:</strong> Harga yang digunakan adalah <strong>Selling Price dari database</strong>. 
                                Jika ada selisih dengan harga di PO, Anda dapat mengedit langsung di form Sales Order setelah klik "Generate Draft SO".
                            </div>

                            <div class="flex gap-4 pt-4">
                                <button 
                                    @click="currentStep = 1"
                                    class="py-3 px-6 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold hover:bg-slate-200 transition-all"
                                >
                                    Try Again
                                </button>
                                <button 
                                    @click="analyzeFulfillment"
                                    :disabled="isAnalyzing"
                                    class="flex-1 py-3 px-6 rounded-2xl bg-amber-500 text-white font-bold shadow-lg shadow-amber-500/25 hover:bg-amber-400 transition-all flex items-center justify-center gap-2 disabled:opacity-50"
                                >
                                    <ChartBarSquareIcon v-if="!isAnalyzing" class="h-5 w-5" />
                                    <ArrowPathIcon v-else class="h-5 w-5 animate-spin" />
                                    {{ isAnalyzing ? 'Analyzing...' : 'Analyze Fulfillment' }}
                                </button>
                                <button 
                                    @click="createDraftSO"
                                    class="flex-1 py-3 px-6 rounded-2xl bg-blue-600 text-white font-bold shadow-lg shadow-blue-500/25 hover:bg-blue-500 transition-all flex items-center justify-center gap-2"
                                >
                                    <DocumentIcon class="h-5 w-5" />
                                    Generate Draft SO
                                </button>
                            </div>
                        </div>

                            <!-- Step 4: Fulfillment Analysis -->
                            <div v-if="currentStep === 4" class="space-y-4">
                            <!-- Header -->
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Fulfillment Analysis</h3>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Stock check & recommendations</p>
                                </div>
                                <div class="flex gap-2">
                                    <span v-if="fulfillmentAnalysis?.summary?.items_ok > 0" class="px-2 py-1 text-xs font-bold bg-emerald-500/20 text-emerald-600 rounded-full">
                                        {{ fulfillmentAnalysis.summary.items_ok }} OK
                                    </span>
                                    <span v-if="fulfillmentAnalysis?.summary?.items_warning > 0" class="px-2 py-1 text-xs font-bold bg-amber-500/20 text-amber-600 rounded-full">
                                        {{ fulfillmentAnalysis.summary.items_warning }} Warning
                                    </span>
                                    <span v-if="fulfillmentAnalysis?.summary?.items_critical > 0" class="px-2 py-1 text-xs font-bold bg-red-500/20 text-red-600 rounded-full">
                                        {{ fulfillmentAnalysis.summary.items_critical }} Critical
                                    </span>
                                </div>
                            </div>

                            <!-- Items Analysis -->
                            <div class="space-y-4 max-h-80 overflow-y-auto pr-2">
                                <div 
                                    v-for="(item, index) in fulfillmentAnalysis?.items" 
                                    :key="index"
                                    :class="['p-4 rounded-2xl border', getPriorityBg(item.priority)]"
                                >
                                    <!-- Item Header -->
                                    <div class="flex items-start justify-between mb-3">
                                        <div>
                                            <h4 class="font-bold text-slate-900 dark:text-white text-sm">{{ item.product_name }}</h4>
                                            <p v-if="item.product_sku" class="text-xs text-slate-500 font-mono">{{ item.product_sku }}</p>
                                        </div>
                                        <div :class="['w-3 h-3 rounded-full', getPriorityColor(item.priority)]"></div>
                                    </div>

                                    <!-- Stock Details -->
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-3">
                                        <div class="text-center p-2 rounded-xl bg-white/50 dark:bg-slate-900/50">
                                            <p class="text-lg font-bold text-slate-900 dark:text-white">{{ item.required_qty }}</p>
                                            <p class="text-[10px] text-slate-500 uppercase tracking-wider">Required</p>
                                        </div>
                                        <div class="text-center p-2 rounded-xl bg-white/50 dark:bg-slate-900/50">
                                            <p class="text-lg font-bold text-blue-600">{{ item.available_stock || 0 }}</p>
                                            <p class="text-[10px] text-slate-500 uppercase tracking-wider">Available</p>
                                        </div>
                                        <div class="text-center p-2 rounded-xl bg-white/50 dark:bg-slate-900/50">
                                            <p class="text-lg font-bold text-cyan-600">{{ item.incoming_po || 0 }}</p>
                                            <p class="text-[10px] text-slate-500 uppercase tracking-wider">Incoming PO</p>
                                        </div>
                                        <div class="text-center p-2 rounded-xl bg-white/50 dark:bg-slate-900/50">
                                            <p class="text-lg font-bold text-purple-600">{{ item.in_production || 0 }}</p>
                                            <p class="text-[10px] text-slate-500 uppercase tracking-wider">In Production</p>
                                        </div>
                                    </div>

                                    <!-- Gap & Recommendation -->
                                    <div class="flex items-center justify-between p-3 rounded-xl bg-white/30 dark:bg-slate-900/30">
                                        <div class="flex items-center gap-2">
                                            <span :class="['px-2 py-1 rounded-lg text-xs font-bold', item.gap >= 0 ? 'bg-emerald-500/20 text-emerald-600' : 'bg-red-500/20 text-red-600']">
                                                Gap: {{ item.gap >= 0 ? '+' : '' }}{{ item.gap }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <template v-if="item.recommendation?.action === 'fulfill_from_stock'">
                                                <span class="text-xs text-emerald-600 font-semibold flex items-center gap-1">
                                                    <CheckCircleIcon class="h-4 w-4" />
                                                    Stock OK
                                                </span>
                                            </template>
                                            <template v-else-if="item.recommendation?.action === 'create_work_order'">
                                                <span class="text-xs bg-purple-500/20 text-purple-600 px-2 py-1 rounded-lg font-bold flex items-center gap-1">
                                                    <WrenchScrewdriverIcon class="h-4 w-4" />
                                                    {{ item.recommendation.message }}
                                                </span>
                                            </template>
                                            <template v-else-if="item.recommendation?.action === 'create_purchase_order'">
                                                <span class="text-xs bg-blue-500/20 text-blue-600 px-2 py-1 rounded-lg font-bold flex items-center gap-1">
                                                    <ShoppingCartIcon class="h-4 w-4" />
                                                    {{ item.recommendation.message }}
                                                </span>
                                            </template>
                                            <template v-else>
                                                <span class="text-xs text-slate-500 italic">{{ item.recommendation?.message || 'Match product first' }}</span>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex gap-4 pt-4">
                                <button 
                                    @click="currentStep = 3"
                                    class="py-3 px-6 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold hover:bg-slate-200 transition-all"
                                >
                                    Back
                                </button>
                                <button 
                                    @click="createDraftSO"
                                    class="flex-1 py-3 px-6 rounded-2xl bg-blue-600 text-white font-bold shadow-lg shadow-blue-500/25 hover:bg-blue-500 transition-all flex items-center justify-center gap-2"
                                >
                                    <DocumentIcon class="h-5 w-5" />
                                    Proceed to Generate SO
                                </button>
                            </div>
                        </div>  <!-- End Step 4 -->
                    </div>  <!-- End main content wrapper -->
                </div>  <!-- End body -->

                    <!-- Footer -->
                    <div class="p-6 bg-slate-50 dark:bg-slate-800/30 border-t border-slate-100 dark:border-slate-800">
                        <div class="flex items-center justify-between">
                            <a 
                                href="/guide/ai-po-extractor.html" 
                                target="_blank"
                                class="inline-flex items-center gap-1.5 text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 font-semibold transition-colors"
                            >
                                <QuestionMarkCircleIcon class="h-4 w-4" />
                                User Guide
                            </a>
                            <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">
                                Tip: Best results come from high-resolution images or clear PDF documents.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.glass-card {
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.05);
}
</style>
