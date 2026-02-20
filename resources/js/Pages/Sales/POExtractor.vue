<script setup>
import { ref, computed, watch } from 'vue';
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
    EyeIcon,
    ArrowsPointingOutIcon,
    ArrowLeftIcon,
    PlusIcon,
    TrashIcon,
    PencilIcon,
    ArrowDownTrayIcon,
    UserPlusIcon,
    UserCircleIcon,
} from '@heroicons/vue/24/outline';
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    customers: Array,
    units: Array,
    categories: Array
});

// Register Product Modal State
const showRegisterModal = ref(false);
const registerForm = ref({
    name: '',
    sku: '',
    unit_id: '',
    category_id: '',
    selling_price: 0,
    type: 'product',
    product_type: 'finished_good',
    itemIndex: null
});
const isRegistering = ref(false);

const openRegisterModal = (item, index) => {
    registerForm.value = {
        name: item.description,
        sku: '',
        unit_id: '',
        category_id: '',
        selling_price: item.po_price || 0,
        type: 'product',
        product_type: 'finished_good',
        itemIndex: index
    };
    
    // Try to auto-select unit based on name or code
    if (item.unit && props.units) {
        const search = item.unit.trim().toLowerCase();
        const matchingUnit = props.units.find(u => 
            u.name.toLowerCase() === search || 
            (u.code && u.code.toLowerCase() === search)
        );
        if (matchingUnit) {
            registerForm.value.unit_id = matchingUnit.id;
        }
    }

    // Try to auto-generate SKU from first word of description
    if (item.description) {
        const firstWord = item.description.trim().split(/\s+/)[0];
        // Basic heuristic: if it looks like a code (alphanumeric, >2 chars)
        if (firstWord && firstWord.length >= 3) {
            registerForm.value.sku = firstWord;
        }
    }
    
    showRegisterModal.value = true;
};

const closeRegisterModal = () => {
    showRegisterModal.value = false;
    registerForm.value.itemIndex = null;
};

const registerProduct = async () => {
    if (!registerForm.value.name || !registerForm.value.unit_id) {
        alert('Please fill Name and Unit');
        return;
    }
    
    isRegistering.value = true;
    try {
        const response = await axios.post('/sales/po-extractor/store-product', registerForm.value);
        if (response.data.success) {
            const newProduct = response.data.product;
            
            // Update the item row
            if (registerForm.value.itemIndex !== null) {
                const item = editableData.value.items[registerForm.value.itemIndex];
                item.matched_product_id = newProduct.id;
                item.matched_product_name = newProduct.name;
                item.matched_sku = newProduct.sku;
                item.db_price = newProduct.selling_price;
                item.current_stock = newProduct.current_stock;
            }
            
            closeRegisterModal();
        }
    } catch (err) {
        alert('Failed to register product: ' + (err.response?.data?.message || err.message));
    } finally {
        isRegistering.value = false;
    }
};

const isBulkRegistering = ref(false);

// === Customer Matching & Registration ===
const showCustomerModal = ref(false);
const isRegisteringCustomer = ref(false);
const customerForm = ref({
    name: '',
    code: '',
    contact_person: '',
    phone: '',
    email: '',
    address: '',
});

const customerMatched = computed(() => {
    return editableData.value.matched_customer_id != null;
});

const openCustomerModal = () => {
    customerForm.value = {
        name: editableData.value.customer_name || '',
        code: '',
        contact_person: '',
        phone: '',
        email: '',
        address: '',
    };
    showCustomerModal.value = true;
};

const registerCustomer = async () => {
    if (!customerForm.value.name) {
        alert('Nama customer wajib diisi');
        return;
    }
    isRegisteringCustomer.value = true;
    try {
        const response = await axios.post('/sales/po-extractor/store-customer', customerForm.value);
        if (response.data.success) {
            const newCust = response.data.customer;
            editableData.value.matched_customer_id = newCust.id;
            editableData.value.matched_customer_name = newCust.name;
            editableData.value.customer_name = newCust.name;
            showCustomerModal.value = false;
            alert('Customer "' + newCust.name + '" berhasil didaftarkan!');
        }
    } catch (err) {
        alert('Gagal mendaftarkan customer: ' + (err.response?.data?.message || err.message));
    } finally {
        isRegisteringCustomer.value = false;
    }
};

// === Bulk Register Preview Modal ===
const showBulkModal = ref(false);
const bulkItems = ref([]);
const localUnits = ref([...(props.units || [])]);

const findUnitId = (unitText) => {
    if (!unitText) return null;
    const search = unitText.trim().toLowerCase();
    const match = localUnits.value.find(u => 
        u.name.toLowerCase() === search || 
        (u.code && u.code.toLowerCase() === search)
    );
    return match ? match.id : null;
};

const openBulkModal = () => {
    const noMatchItems = editableData.value.items
        .map((item, idx) => ({ ...item, originalIndex: idx }))
        .filter(item => !item.matched_product_id || item.match_status === 'NO_MATCH');
    
    if (noMatchItems.length === 0) {
        alert('Tidak ada item yang belum terdaftar.');
        return;
    }

    bulkItems.value = noMatchItems.map(item => ({
        description: item.description,
        sku: item.proposed_sku || '',
        extracted_unit: item.unit || '',
        unit_id: findUnitId(item.unit) || '',
        selling_price: item.unit_price || 0,
        originalIndex: item.originalIndex,
        selected: true,
    }));
    showBulkModal.value = true;
};

const bulkSelectedCount = computed(() => bulkItems.value.filter(i => i.selected).length);

const bulkHasUnitIssues = computed(() => bulkItems.value.some(i => i.selected && !i.unit_id));

// === Inline Unit Registration ===
const showUnitModal = ref(false);
const unitModalTargetIndex = ref(null);
const newUnitName = ref('');
const newUnitCode = ref('');
const isStoringUnit = ref(false);

const openUnitModal = (index) => {
    const item = bulkItems.value[index];
    unitModalTargetIndex.value = index;
    newUnitName.value = item.extracted_unit || '';
    newUnitCode.value = (item.extracted_unit || '').toUpperCase().replace(/[^A-Z0-9]/g, '').substring(0, 10);
    showUnitModal.value = true;
};

const storeNewUnit = async () => {
    if (!newUnitName.value || !newUnitCode.value) return;
    isStoringUnit.value = true;
    try {
        const res = await axios.post(route('sales.po-extractor.store-unit'), {
            name: newUnitName.value,
            code: newUnitCode.value.toUpperCase(),
        });
        if (res.data.success) {
            // Add to local units list
            localUnits.value.push(res.data.unit);
            // Auto-select for the target item
            if (unitModalTargetIndex.value !== null) {
                bulkItems.value[unitModalTargetIndex.value].unit_id = res.data.unit.id;
            }
            // Also auto-assign to other items with same extracted_unit
            const targetUnit = bulkItems.value[unitModalTargetIndex.value]?.extracted_unit;
            if (targetUnit) {
                bulkItems.value.forEach(item => {
                    if (!item.unit_id && item.extracted_unit && item.extracted_unit.toLowerCase() === targetUnit.toLowerCase()) {
                        item.unit_id = res.data.unit.id;
                    }
                });
            }
            showUnitModal.value = false;
        }
    } catch (e) {
        const msg = e.response?.data?.errors?.code?.[0] || e.response?.data?.message || 'Gagal mendaftarkan satuan.';
        alert(msg);
    } finally {
        isStoringUnit.value = false;
    }
};

const registerBulkProducts = async () => {
    const selectedItems = bulkItems.value.filter(i => i.selected);
    if (selectedItems.length === 0) {
        alert('Pilih minimal satu item.');
        return;
    }
    const missingUnit = selectedItems.filter(i => !i.unit_id);
    if (missingUnit.length > 0) {
        alert(`${missingUnit.length} item belum memiliki satuan yang valid. Silakan pilih satuan terlebih dahulu.`);
        return;
    }

    isBulkRegistering.value = true;
    try {
        const itemsToRegister = selectedItems.map(item => ({
            description: item.description,
            sku: item.sku || '',
            unit_id: item.unit_id,
            selling_price: item.selling_price || 0
        }));

        const response = await axios.post(route('sales.po-extractor.store-product-bulk'), {
            items: itemsToRegister
        });

        if (response.data.success) {
            const createdProducts = response.data.products;
            
            selectedItems.forEach((bulkItem, idx) => {
                if (createdProducts[idx]) {
                    const newProd = createdProducts[idx];
                    const origItem = editableData.value.items[bulkItem.originalIndex];
                    if (origItem) {
                        origItem.matched_product_id = newProd.id;
                        origItem.matched_product_name = newProd.name;
                        origItem.matched_sku = newProd.sku;
                        origItem.db_price = newProd.selling_price;
                        origItem.current_stock = newProd.current_stock;
                        origItem.match_status = 'MATCHED';
                    }
                }
            });
            showBulkModal.value = false;
            alert(response.data.message);
        }
    } catch (err) {
        console.error(err);
        alert('Gagal mendaftarkan produk: ' + (err.response?.data?.message || err.message));
    } finally {
        isBulkRegistering.value = false;
    }
};

const file = ref(null);
const fileInput = ref(null);
const filePreviewUrl = ref(null);
const isUploading = ref(false);
const isAnalyzing = ref(false);
const error = ref(null);
const extractionResult = ref(null);
const fulfillmentAnalysis = ref(null);
const currentStep = ref(1); // 1: Upload, 2: Processing, 3: Validation, 4: Fulfillment Analysis

// Editable data for Step 3 (Review)
const editableData = ref({
    po_number: '',
    po_date: '',
    customer_name: '',
    matched_customer_id: null,
    matched_customer_name: '',
    items: []
});

// Watch extraction result and populate editable data
watch(extractionResult, (newVal) => {
    if (newVal) {
        editableData.value = {
            po_number: newVal.po_number || '',
            po_date: newVal.po_date || '',
            customer_name: newVal.customer_name || '',
            matched_customer_id: newVal.matched_customer_id || null,
            matched_customer_name: newVal.matched_customer_name || '',
            items: (newVal.items || []).map(item => ({
                material_number: item.material_number || null,
                description: item.description || '',
                qty: item.qty || 0,
                unit: item.unit || 'Pcs',
                unit_price: item.unit_price || 0,
                po_price: item.po_price || item.unit_price || 0,
                db_price: item.db_price || 0,
                current_stock: item.current_stock || 0,
                price_mismatch: item.price_mismatch || false,
                matched_product_id: item.matched_product_id || null,
                matched_product_name: item.matched_product_name || '',
                matched_sku: item.matched_sku || '',
                proposed_sku: item.proposed_sku || '',
                match_status: item.match_status || (item.matched_product_id ? 'MATCHED' : 'NO_MATCH')
            }))
        };
    }
}, { deep: true });

// Add new item row
const addItemRow = () => {
    editableData.value.items.push({
        description: '',
        qty: 1,
        unit: 'Pcs',
        unit_price: 0,
        matched_product_id: null,
        matched_product_name: '',
        matched_sku: '',
        match_status: 'NO_MATCH'
    });
};

// Remove item row
const removeItemRow = (index) => {
    editableData.value.items.splice(index, 1);
};

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

const reset = () => {
    revokePreviewUrl();
    file.value = null;
    error.value = null;
    extractionResult.value = null;
    fulfillmentAnalysis.value = null;
    currentStep.value = 1;
};

const uploadPO = async () => {
    if (!file.value) return;

    isUploading.value = true;
    currentStep.value = 2;
    error.value = null;

    const formData = new FormData();
    formData.append('file', file.value);

    try {
        const response = await axios.post('/sales/orders/ai-extract', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        if (response.data.success) {
            extractionResult.value = response.data.data;
            currentStep.value = 3;
        } else {
            error.value = response.data.message || 'Failed to extract data from PO.';
            currentStep.value = 1;
        }
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to extract data from PO using AI. Please check your API configuration.';
        currentStep.value = 1;
    } finally {
        isUploading.value = false;
    }
};

const analyzeFulfillment = async () => {
    if (!editableData.value?.items || editableData.value.items.length === 0) return;

    isAnalyzing.value = true;
    error.value = null;

    try {
        // Map items to format expected by backend
        const mappedItems = editableData.value.items.map(item => ({
            product_id: item.matched_product_id || null,
            product_name: item.description || item.matched_product_name || 'Unknown',
            qty: item.qty || 0,
            unit_price: item.unit_price || 0
        }));

        const response = await axios.post('/sales/orders/analyze-fulfillment', {
            items: mappedItems
        });

        if (response.data.success) {
            fulfillmentAnalysis.value = {
                items: response.data.items,
                summary: response.data.summary
            };
            currentStep.value = 4;
        } else {
            error.value = response.data.message || 'Failed to analyze fulfillment.';
        }
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to analyze fulfillment.';
    } finally {
        isAnalyzing.value = false;
    }
};

const createDraftSO = () => {
    if (!editableData.value || editableData.value.items.length === 0) return;

    // Merge editableData with original extraction result for complete data
    const dataToSend = {
        ...extractionResult.value,
        po_number: editableData.value.po_number,
        po_date: editableData.value.po_date,
        customer_name: editableData.value.customer_name,
        items: editableData.value.items
    };

    router.post('/sales/orders/create-from-ai', {
        data: dataToSend
    });
};

const getMatchStatusClass = (status) => {
    if (status === 'MATCHED') return 'text-emerald-500 bg-emerald-500/10';
    if (status === 'PARTIAL') return 'text-amber-500 bg-amber-500/10';
    return 'text-red-500 bg-red-500/10';
};

const getPriorityBg = (priority) => {
    if (priority === 'ok') return 'bg-emerald-500/10 border-emerald-500/20';
    if (priority === 'warning') return 'bg-amber-500/10 border-amber-500/20';
    return 'bg-red-500/10 border-red-500/20';
};

const getPriorityIcon = (priority) => {
    if (priority === 'ok') return CheckCircleIcon;
    if (priority === 'warning') return ExclamationTriangleIcon;
    return ExclamationTriangleIcon;
};

const getPriorityIconClass = (priority) => {
    if (priority === 'ok') return 'text-emerald-500';
    if (priority === 'warning') return 'text-amber-500';
    return 'text-red-500';
};

const steps = [
    { id: 1, name: 'Upload', description: 'Upload PO document' },
    { id: 2, name: 'Processing', description: 'AI analyzing' },
    { id: 3, name: 'Review', description: 'Verify extracted data' },
    { id: 4, name: 'Fulfillment', description: 'Stock analysis' },
];

const exportToExcel = async () => {
    if (!editableData.value || editableData.value.items.length === 0) return;

    try {
        const response = await axios.post('/sales/po-extractor/export', editableData.value, {
            responseType: 'blob',
        });
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `PO_Extraction_${editableData.value.po_number || 'Draft'}.xlsx`);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
    } catch (err) {
        console.error(err);
        if (err.response && err.response.data instanceof Blob) {
            const text = await err.response.data.text();
            try {
                const json = JSON.parse(text);
                alert('Export Failed: ' + (json.message || JSON.stringify(json)));
            } catch (e) {
                alert('Export Failed: ' + text.substring(0, 100));
            }
        } else {
            alert('Failed to export Excel.');
        }
    }
};
</script>

<template>
    <AppLayout title="AI PO Extractor">
        <div class="max-w-full mx-auto px-4">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center gap-4 mb-4">
                    <a 
                        href="/sales/orders" 
                        class="p-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-slate-700 dark:hover:text-white transition-colors"
                    >
                        <ArrowLeftIcon class="h-5 w-5" />
                    </a>
                    <div class="flex items-center gap-3">
                        <div class="p-3 rounded-2xl bg-gradient-to-br from-amber-500/20 to-orange-500/20 text-amber-500">
                            <SparklesIcon class="h-8 w-8" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">AI Purchase Order Extractor</h1>
                            <p class="text-sm text-slate-500">Powered by Gemini AI - Extract PO data automatically</p>
                        </div>
                    </div>
                </div>

                <!-- Progress Stepper -->
                <nav aria-label="Progress" class="mt-6">
                    <ol class="flex items-center">
                        <li v-for="(step, stepIdx) in steps" :key="step.id" :class="[stepIdx !== steps.length - 1 ? 'flex-1' : '', 'relative']">
                            <div class="flex items-center">
                                <!-- Step circle -->
                                <div 
                                    :class="[
                                        'relative z-10 flex h-10 w-10 items-center justify-center rounded-full border-2 transition-all duration-300',
                                        currentStep > step.id 
                                            ? 'border-emerald-500 bg-emerald-500 text-white' 
                                            : currentStep === step.id 
                                                ? 'border-blue-500 bg-blue-500 text-white' 
                                                : 'border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-500'
                                    ]"
                                >
                                    <CheckCircleIcon v-if="currentStep > step.id" class="h-5 w-5" />
                                    <span v-else class="text-sm font-bold">{{ step.id }}</span>
                                </div>
                                <!-- Step info -->
                                <div class="ml-3 hidden sm:block">
                                    <p :class="['text-sm font-bold', currentStep >= step.id ? 'text-slate-900 dark:text-white' : 'text-slate-400']">
                                        {{ step.name }}
                                    </p>
                                    <p class="text-xs text-slate-500">{{ step.description }}</p>
                                </div>
                            </div>
                            <!-- Connector line -->
                            <div 
                                v-if="stepIdx !== steps.length - 1" 
                                :class="[
                                    'absolute top-5 left-10 -ml-px h-0.5 w-full transition-colors duration-300',
                                    currentStep > step.id ? 'bg-emerald-500' : 'bg-slate-200 dark:bg-slate-700'
                                ]"
                            />
                        </li>
                    </ol>
                </nav>
            </div>

            <!-- Main Content -->
            <div :class="['grid gap-6', currentStep >= 3 && filePreviewUrl ? 'lg:grid-cols-2' : 'lg:grid-cols-1 max-w-2xl mx-auto']">
                <!-- Left: Document Preview (only shown in step 3+) -->
                <div v-if="currentStep >= 3 && filePreviewUrl" class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 shadow-xl">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <EyeIcon class="h-5 w-5 text-slate-500" />
                            <span class="text-sm font-bold text-slate-700 dark:text-slate-300">Original Document</span>
                        </div>
                        <a 
                            :href="filePreviewUrl" 
                            target="_blank"
                            class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                            title="Open in new tab"
                        >
                            <ArrowsPointingOutIcon class="h-5 w-5" />
                        </a>
                    </div>
                    <div class="rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden bg-slate-100 dark:bg-slate-800">
                        <!-- PDF Preview -->
                        <iframe 
                            v-if="fileType === 'pdf'"
                            :src="filePreviewUrl"
                            class="w-full h-[600px]"
                            title="PO Document Preview"
                        ></iframe>
                        <!-- Image Preview -->
                        <img 
                            v-else-if="fileType === 'image'"
                            :src="filePreviewUrl"
                            class="w-full h-[600px] object-contain"
                            alt="PO Document Preview"
                        />
                        <!-- Unknown type fallback -->
                        <div v-else class="w-full h-[600px] flex items-center justify-center text-slate-400">
                            <div class="text-center">
                                <DocumentIcon class="h-16 w-16 mx-auto mb-3" />
                                <p class="text-sm">Preview not available for this file type</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Main Content -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 shadow-xl">
                    
                    <!-- Step 1: Upload -->
                    <div v-if="currentStep === 1" class="flex flex-col items-center py-8">
                        <div 
                            @click="triggerFileInput"
                            @dragover.prevent
                            @drop.prevent="handleFileSelect"
                            class="w-full h-64 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-3xl flex flex-col items-center justify-center gap-4 cursor-pointer hover:border-blue-500/50 hover:bg-blue-500/5 transition-all group"
                        >
                            <div class="p-5 rounded-full bg-slate-50 dark:bg-slate-800 group-hover:bg-blue-500/10 transition-colors">
                                <CloudArrowUpIcon class="h-12 w-12 text-slate-400 group-hover:text-blue-500 transition-colors" />
                            </div>
                            <div class="text-center">
                                <p class="text-lg font-semibold text-slate-700 dark:text-slate-200">
                                    {{ file ? file.name : 'Click to upload or drag & drop' }}
                                </p>
                                <p class="text-sm text-slate-500 mt-1">PDF or Image (Max 5MB)</p>
                            </div>
                            <input 
                                ref="fileInput"
                                type="file" 
                                class="hidden" 
                                accept="application/pdf,image/png,image/jpeg,image/jpg,image/gif,image/webp"
                                @change="handleFileSelect"
                            />
                        </div>

                        <div v-if="error" class="mt-6 w-full p-4 rounded-xl bg-red-500/10 border border-red-500/20 flex items-center gap-3 text-red-500">
                            <ExclamationTriangleIcon class="h-5 w-5 shrink-0" />
                            <span class="text-sm font-medium">{{ error }}</span>
                        </div>

                        <button 
                            @click="uploadPO"
                            :disabled="!file || isUploading"
                            class="mt-8 w-full py-4 px-6 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold shadow-lg shadow-blue-500/25 dark:shadow-blue-500/10 hover:shadow-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-3 text-lg"
                        >
                            <SparklesIcon class="h-6 w-6" />
                            Start AI Extraction
                        </button>
                    </div>

                    <!-- Step 2: Processing -->
                    <div v-if="currentStep === 2" class="flex flex-col items-center py-16">
                        <div class="relative">
                            <div class="w-28 h-28 rounded-full border-4 border-slate-100 dark:border-slate-800 border-t-blue-500 animate-spin"></div>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <SparklesIcon class="h-10 w-10 text-amber-500 animate-pulse" />
                            </div>
                        </div>
                        <h4 class="mt-10 text-xl font-bold text-slate-900 dark:text-white">AI is reading your document...</h4>
                        <p class="mt-3 text-sm text-slate-500 text-center max-w-sm">
                            Gemini is identifying products, quantities, and customer details. This may take a moment.
                        </p>
                        
                        <!-- Fake progress steps -->
                        <div class="mt-12 w-full max-w-xs space-y-4">
                            <div class="flex items-center gap-3">
                                <CheckCircleIcon class="h-6 w-6 text-emerald-500" />
                                <span class="text-sm font-semibold text-slate-400">File Uploaded</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <ArrowPathIcon class="h-6 w-6 text-blue-500 animate-spin" />
                                <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">Processing with Multimodal Vision</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="h-6 w-6 rounded-full border-2 border-slate-200 dark:border-slate-700"></div>
                                <span class="text-sm font-semibold text-slate-400">Matching with Product Catalog</span>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Success & Editable Review -->
                    <div v-if="currentStep === 3" class="space-y-5">
                        <div class="bg-emerald-500/10 border border-emerald-500/20 p-4 rounded-2xl flex items-center gap-3 text-emerald-500">
                            <CheckCircleIcon class="h-6 w-6" />
                            <div class="text-sm font-bold">Extraction Successful! AI found {{ extractionResult?.items?.length || 0 }} items. You can edit the data below.</div>
                        </div>

                        <!-- Editable Header Section -->
                        <div class="grid grid-cols-3 gap-4">
                            <div class="space-y-2">
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest flex items-center gap-1">
                                    <PencilIcon class="h-3 w-3" />
                                    Customer PO Number
                                </label>
                                <input 
                                    v-model="editableData.po_number"
                                    type="text"
                                    class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-sm font-medium focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                                    placeholder="Enter PO Number..."
                                />
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest flex items-center gap-1">
                                    <PencilIcon class="h-3 w-3" />
                                    Customer Name
                                </label>
                                <div class="relative">
                                    <input 
                                        v-model="editableData.customer_name"
                                        type="text"
                                        :class="[
                                            'w-full px-4 py-2.5 rounded-xl border text-sm font-medium focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all',
                                            customerMatched 
                                                ? 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-300 dark:border-emerald-700 text-slate-900 dark:text-white' 
                                                : 'bg-amber-50 dark:bg-amber-900/20 border-amber-300 dark:border-amber-700 text-slate-900 dark:text-white'
                                        ]"
                                        placeholder="Enter Customer Name..."
                                    />
                                    <!-- Match indicator -->
                                    <div class="absolute right-2 top-1/2 -translate-y-1/2 flex items-center gap-1">
                                        <span v-if="customerMatched" class="flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-600 text-[10px] font-bold">
                                            <CheckCircleIcon class="h-3 w-3" /> Terdaftar
                                        </span>
                                        <button 
                                            v-else
                                            @click="openCustomerModal"
                                            class="flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-600 hover:bg-amber-500/30 text-[10px] font-bold transition-colors cursor-pointer"
                                        >
                                            <UserPlusIcon class="h-3 w-3" /> Daftarkan
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest flex items-center gap-1">
                                    <PencilIcon class="h-3 w-3" />
                                    PO Date
                                </label>
                                <input 
                                    v-model="editableData.po_date"
                                    type="date"
                                    class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-sm font-medium focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                                />
                            </div>
                        </div>

                        <!-- Editable Items Table -->
                        <div class="rounded-2xl border border-slate-200 dark:border-slate-700 flex flex-col" style="max-height: 400px;">
                            <!-- Fixed Header -->
                            <table class="min-w-full">
                                <thead class="bg-slate-100 dark:bg-slate-800">
                                    <tr>
                                        <th class="px-2 py-3 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider w-[4%]">No</th>
                                        <th class="px-2 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider w-[20%]">Description</th>
                                        <th class="px-2 py-3 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider w-[12%]">Qty</th>
                                        <th class="px-2 py-3 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider w-[8%]">Stock</th>
                                        <th class="px-2 py-3 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider w-[6%]">Unit</th>
                                        <th class="px-2 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider w-[12%]">PO Price</th>
                                        <th class="px-2 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider w-[18%]">Price Comparison</th>
                                        <th class="px-2 py-3 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider w-[8%]">Act</th>
                                    </tr>
                                </thead>
                            </table>
                            <!-- Scrollable Body -->
                            <div class="overflow-y-auto flex-1">
                                <table class="min-w-full">
                                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                        <tr v-for="(item, index) in editableData.items" :key="index" class="bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                            <!-- No Column -->
                                            <td class="px-2 py-2 text-center w-[4%]">
                                                <span class="text-sm font-bold text-slate-400">{{ index + 1 }}</span>
                                            </td>
                                            <!-- Description -->
                                            <td class="px-2 py-2 w-[20%]">
                                                <input 
                                                    v-model="item.description"
                                                    type="text"
                                                    class="w-full px-2 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                                                    placeholder="Product..."
                                                />
                                            </td>
                                            <!-- Qty -->
                                            <td class="px-2 py-2 w-[12%]">
                                                <input 
                                                    v-model.number="item.qty"
                                                    type="number"
                                                    min="1"
                                                    class="w-full px-2 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-center"
                                                />
                                            </td>
                                            <!-- Stock Column -->
                                            <td class="px-2 py-2 text-center w-[8%]">
                                                <div v-if="item.current_stock > 0">
                                                    <span 
                                                        :class="[
                                                            'px-2 py-1 rounded-lg text-xs font-bold',
                                                            item.current_stock >= item.qty 
                                                                ? 'bg-emerald-500/10 text-emerald-600' 
                                                                : 'bg-red-500/10 text-red-600'
                                                        ]"
                                                    >
                                                        {{ Number(item.current_stock).toLocaleString('id-ID') }}
                                                    </span>
                                                </div>
                                                <span v-else class="text-xs text-slate-400">-</span>
                                            </td>
                                            <!-- Unit -->
                                            <td class="px-2 py-2 w-[6%]">
                                                <input 
                                                    v-model="item.unit"
                                                    type="text"
                                                    class="w-full px-1 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-center"
                                                    placeholder="Pcs"
                                                />
                                            </td>
                                            <!-- PO Price -->
                                            <td class="px-2 py-2 w-[12%]">
                                                <input 
                                                    v-model.number="item.unit_price"
                                                    type="number"
                                                    min="0"
                                                    class="w-full px-2 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                                                />
                                            </td>
                                            <!-- Price Comparison Column -->
                                            <td class="px-2 py-2 w-[18%]">
                                                <div v-if="item.db_price > 0" class="space-y-1">
                                                    <div class="flex items-center gap-2 text-xs">
                                                        <span class="text-slate-400 w-12">PO:</span>
                                                        <span class="font-bold text-blue-500">Rp {{ Number(item.po_price || 0).toLocaleString('id-ID') }}</span>
                                                    </div>
                                                    <div class="flex items-center gap-2 text-xs">
                                                        <span class="text-slate-400 w-12">Master:</span>
                                                        <span class="font-bold text-emerald-500">Rp {{ Number(item.db_price || 0).toLocaleString('id-ID') }}</span>
                                                    </div>
                                                    <!-- Price Difference Indicator -->
                                                    <div v-if="item.po_price && item.db_price && Math.abs(item.po_price - item.db_price) > 0.01" class="mt-1">
                                                        <span 
                                                            :class="[
                                                                'px-2 py-0.5 rounded-full text-[10px] font-bold',
                                                                item.po_price > item.db_price ? 'bg-emerald-500/10 text-emerald-600' : 'bg-red-500/10 text-red-600'
                                                            ]"
                                                        >
                                                            {{ item.po_price > item.db_price ? '↑' : '↓' }} 
                                                            Rp {{ Math.abs(item.po_price - item.db_price).toLocaleString('id-ID') }}
                                                            ({{ ((Math.abs(item.po_price - item.db_price) / item.db_price) * 100).toFixed(1) }}%)
                                                        </span>
                                                    </div>
                                                    <div v-else-if="item.po_price && item.db_price">
                                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-600">✓ Match</span>
                                                    </div>
                                                </div>
                                                <span v-else class="text-xs text-slate-400 italic">No match</span>
                                            </td>
                                            <!-- Actions -->
                                            <td class="px-2 py-2 text-center w-[8%]">
                                                <div class="flex items-center justify-center gap-1">
                                                    <button 
                                                        v-if="!item.matched_product_id || item.match_status === 'NO_MATCH'"
                                                        @click="openRegisterModal(item, index)"
                                                        class="p-1.5 rounded-lg bg-blue-500/10 text-blue-500 hover:bg-blue-500/20 transition-colors"
                                                        title="Register new product"
                                                    >
                                                        <PlusIcon class="h-4 w-4" />
                                                    </button>
                                                    
                                                    <button 
                                                        @click="removeItemRow(index)"
                                                        class="p-1.5 rounded-lg bg-red-500/10 text-red-500 hover:bg-red-500/20 transition-colors"
                                                        title="Remove item"
                                                    >
                                                        <TrashIcon class="h-4 w-4" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <!-- Empty state -->
                                        <tr v-if="editableData.items.length === 0" class="bg-white dark:bg-slate-900">
                                            <td colspan="8" class="px-4 py-8 text-center text-slate-400">
                                                No items. Click "Add Item" to add a new row.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <!-- Add Item Button -->
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
                            ✏️ <strong>Edit Mode:</strong> Anda dapat mengedit semua data di atas sebelum Generate Draft SO. 
                            Perubahan yang anda buat akan langsung digunakan untuk membuat Sales Order.
                        </div>

                        <div class="flex gap-4 pt-4">
                            <button 
                                @click="reset"
                                class="py-3 px-6 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold hover:bg-slate-200 transition-all"
                            >
                                Try Again
                            </button>
                            <button 
                                v-if="editableData.items.some(i => !i.matched_product_id || i.match_status === 'NO_MATCH')"
                                @click="openBulkModal"
                                class="py-3 px-6 rounded-2xl bg-purple-600 text-white font-bold shadow-lg shadow-purple-500/25 hover:bg-purple-500 transition-all flex items-center justify-center gap-2"
                            >
                                <SparklesIcon class="h-5 w-5" />
                                Register All No Match
                            </button>
                            <button 
                                @click="exportToExcel"
                                :disabled="editableData.items.length === 0"
                                class="py-3 px-6 rounded-2xl bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 font-bold hover:bg-emerald-200 dark:hover:bg-emerald-900/50 transition-all flex items-center justify-center gap-2"
                            >
                                <ArrowDownTrayIcon class="h-5 w-5" />
                                Export Excel
                            </button>
                            <button 
                                @click="analyzeFulfillment"
                                :disabled="isAnalyzing || editableData.items.length === 0"
                                class="flex-1 py-3 px-6 rounded-2xl bg-amber-500 text-white font-bold shadow-lg shadow-amber-500/25 hover:bg-amber-400 transition-all flex items-center justify-center gap-2 disabled:opacity-50"
                            >
                                <ChartBarSquareIcon v-if="!isAnalyzing" class="h-5 w-5" />
                                <ArrowPathIcon v-else class="h-5 w-5 animate-spin" />
                                {{ isAnalyzing ? 'Analyzing...' : 'Analyze Fulfillment' }}
                            </button>
                            <button 
                                @click="createDraftSO"
                                :disabled="editableData.items.length === 0"
                                class="flex-1 py-3 px-6 rounded-2xl bg-blue-600 text-white font-bold shadow-lg shadow-blue-500/25 hover:bg-blue-500 transition-all flex items-center justify-center gap-2 disabled:opacity-50"
                            >
                                <DocumentIcon class="h-5 w-5" />
                                Generate Draft SO
                            </button>
                        </div>
                    </div>

                    <!-- Step 4: Fulfillment Analysis -->
                    <div v-if="currentStep === 4" class="space-y-5">
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
                        <div class="space-y-4 max-h-96 overflow-y-auto pr-2">
                            <div 
                                v-for="(item, index) in fulfillmentAnalysis?.items" 
                                :key="index"
                                :class="['p-4 rounded-2xl border', getPriorityBg(item.priority)]"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <component :is="getPriorityIcon(item.priority)" :class="['h-5 w-5', getPriorityIconClass(item.priority)]" />
                                            <span class="font-bold text-slate-900 dark:text-white">{{ item.product_name }}</span>
                                            <span v-if="item.product_sku" class="text-xs text-slate-400">({{ item.product_sku }})</span>
                                        </div>
                                        <div class="mt-2 grid grid-cols-3 gap-4 text-xs">
                                            <div>
                                                <span class="text-slate-400">Ordered:</span>
                                                <span class="ml-1 font-bold text-slate-700 dark:text-slate-200">{{ item.required_qty }}</span>
                                            </div>
                                            <div>
                                                <span class="text-slate-400">Current Stock:</span>
                                                <span class="ml-1 font-bold" :class="item.current_stock >= item.required_qty ? 'text-emerald-500' : 'text-red-500'">
                                                    {{ item.current_stock }}
                                                </span>
                                            </div>
                                            <div>
                                                <span class="text-slate-400">Shortage:</span>
                                                <span class="ml-1 font-bold" :class="item.gap < 0 ? 'text-red-500' : 'text-emerald-500'">
                                                    {{ item.gap < 0 ? Math.abs(item.gap) : 0 }}
                                                </span>
                                            </div>
                                        </div>
                                        <div v-if="item.recommendation" class="mt-3 p-2 rounded-lg bg-white/50 dark:bg-slate-900/50 text-xs">
                                            <span 
                                                :class="[
                                                    'font-medium',
                                                    item.recommendation.type === 'success' ? 'text-emerald-600' : '',
                                                    item.recommendation.type === 'purchase' ? 'text-blue-600' : '',
                                                    item.recommendation.type === 'manufacture' ? 'text-purple-600' : '',
                                                    item.recommendation.type === 'warning' ? 'text-amber-600' : ''
                                                ]"
                                            >
                                                💡 {{ item.recommendation.message }}
                                            </span>
                                        </div>
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
                    </div>
                </div>
            </div>

            <!-- Footer Tips -->
            <div class="mt-8 flex items-center justify-between">
                <a 
                    href="/guide/ai-po-extractor.html" 
                    target="_blank"
                    class="inline-flex items-center gap-1.5 text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 font-semibold transition-colors"
                >
                    <QuestionMarkCircleIcon class="h-4 w-4" />
                    User Guide
                </a>
                <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                    Tip: Best results come from high-resolution images or clear PDF documents.
                </p>
            </div>
        </div>
        <!-- Fast Product Register Modal -->
        <div v-if="showRegisterModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center bg-slate-50 dark:bg-slate-800/50">
                    <h3 class="font-bold text-slate-900 dark:text-white">Register New Product</h3>
                    <button @click="closeRegisterModal" class="text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                        <XMarkIcon class="h-5 w-5" />
                    </button>
                </div>
                
                <div class="p-6 space-y-4">
                    <!-- Name -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Product Name</label>
                        <input 
                            v-model="registerForm.name"
                            type="text"
                            class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm placeholder-slate-400 dark:placeholder-slate-500 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none"
                            placeholder="Product Name"
                        />
                    </div>
                    
                    <!-- SKU (Optional) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">SKU (Optional)</label>
                        <input 
                            v-model="registerForm.sku"
                            type="text"
                            class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm placeholder-slate-400 dark:placeholder-slate-500 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none"
                            placeholder="Leave empty to auto-generate"
                        />
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <!-- Unit -->
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Unit</label>
                            <select 
                                v-model="registerForm.unit_id"
                                class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm placeholder-slate-400 dark:placeholder-slate-500 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none"
                            >
                                <option value="" disabled>Select Unit</option>
                                <option v-for="unit in props.units" :key="unit.id" :value="unit.id">
                                    {{ unit.name }}
                                </option>
                            </select>
                        </div>
                        
                        <!-- Product Type -->
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Type</label>
                            <select 
                                v-model="registerForm.product_type"
                                class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm placeholder-slate-400 dark:placeholder-slate-500 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none"
                            >
                                <option value="finished_good">Finished Good</option>
                                <option value="raw_material">Raw Material</option>
                                <option value="spare_part">Spare Part</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Selling Price -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Selling Price</label>
                        <input 
                            v-model.number="registerForm.selling_price"
                            type="number"
                            class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm placeholder-slate-400 dark:placeholder-slate-500 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none"
                        />
                    </div>
                </div>
                
                <div class="p-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-3">
                    <button 
                        @click="closeRegisterModal"
                        class="px-4 py-2 rounded-lg text-slate-600 hover:bg-slate-200 dark:text-slate-300 dark:hover:bg-slate-700 font-bold text-sm transition-colors"
                    >
                        Cancel
                    </button>
                    <button 
                        @click="registerProduct"
                        :disabled="isRegistering"
                        class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm transition-colors flex items-center gap-2"
                    >
                        <span v-if="isRegistering">Saving...</span>
                        <span v-else>Save Product</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Customer Register Modal -->
        <div v-if="showCustomerModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20">
                    <div class="flex items-center gap-2">
                        <UserPlusIcon class="h-5 w-5 text-amber-600" />
                        <h3 class="font-bold text-slate-900 dark:text-white">Daftarkan Customer Baru</h3>
                    </div>
                    <button @click="showCustomerModal = false" class="text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                        <XMarkIcon class="h-5 w-5" />
                    </button>
                </div>
                
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nama Customer *</label>
                        <input 
                            v-model="customerForm.name"
                            type="text"
                            class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none"
                            placeholder="PT. Example Indonesia"
                        />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Kode</label>
                            <input 
                                v-model="customerForm.code"
                                type="text"
                                class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none"
                                placeholder="CUST-001"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Contact Person</label>
                            <input 
                                v-model="customerForm.contact_person"
                                type="text"
                                class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none"
                            />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Telepon</label>
                            <input 
                                v-model="customerForm.phone"
                                type="text"
                                class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Email</label>
                            <input 
                                v-model="customerForm.email"
                                type="email"
                                class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none"
                            />
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Alamat</label>
                        <textarea 
                            v-model="customerForm.address"
                            rows="2"
                            class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none resize-none"
                        ></textarea>
                    </div>
                </div>
                
                <div class="p-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-3">
                    <button 
                        @click="showCustomerModal = false"
                        class="px-4 py-2 rounded-lg text-slate-600 hover:bg-slate-200 dark:text-slate-300 dark:hover:bg-slate-700 font-bold text-sm transition-colors"
                    >
                        Batal
                    </button>
                    <button 
                        @click="registerCustomer"
                        :disabled="isRegisteringCustomer"
                        class="px-4 py-2 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-bold text-sm transition-colors flex items-center gap-2"
                    >
                        <ArrowPathIcon v-if="isRegisteringCustomer" class="h-4 w-4 animate-spin" />
                        {{ isRegisteringCustomer ? 'Menyimpan...' : 'Daftarkan Customer' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Bulk Register Preview Modal -->
        <div v-if="showBulkModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-xl w-full max-w-3xl overflow-hidden">
                <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20">
                    <div>
                        <h3 class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <SparklesIcon class="h-5 w-5 text-purple-600" />
                            Register Produk Baru (Bulk)
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">Periksa dan sesuaikan data sebelum mendaftarkan</p>
                    </div>
                    <button @click="showBulkModal = false" class="text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                        <XMarkIcon class="h-5 w-5" />
                    </button>
                </div>
                
                <div class="overflow-auto max-h-[60vh]">
                    <table class="min-w-full">
                        <thead class="bg-slate-100 dark:bg-slate-800 sticky top-0">
                            <tr>
                                <th class="px-3 py-3 text-center w-10">
                                    <input 
                                        type="checkbox" 
                                        :checked="bulkItems.every(i => i.selected)"
                                        @change="bulkItems.forEach(i => i.selected = $event.target.checked)"
                                        class="rounded border-slate-300 text-purple-600 focus:ring-purple-500"
                                    />
                                </th>
                                <th class="px-3 py-3 text-left text-[10px] font-bold text-slate-500 uppercase">Nama Produk</th>
                                <th class="px-3 py-3 text-left text-[10px] font-bold text-slate-500 uppercase w-32">SKU</th>
                                <th class="px-3 py-3 text-center text-[10px] font-bold text-slate-500 uppercase w-24">Satuan PO</th>
                                <th class="px-3 py-3 text-center text-[10px] font-bold text-slate-500 uppercase w-40">Satuan Master</th>
                                <th class="px-3 py-3 text-right text-[10px] font-bold text-slate-500 uppercase w-32">Harga Jual</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            <tr v-for="(item, index) in bulkItems" :key="index" :class="[item.selected ? 'bg-white dark:bg-slate-900' : 'bg-slate-50 dark:bg-slate-800/50 opacity-60']">
                                <td class="px-3 py-3 text-center">
                                    <input 
                                        v-model="item.selected"
                                        type="checkbox" 
                                        class="rounded border-slate-300 text-purple-600 focus:ring-purple-500"
                                    />
                                </td>
                                <td class="px-3 py-3">
                                    <input 
                                        v-model="item.description"
                                        type="text"
                                        class="w-full px-2 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 outline-none"
                                    />
                                </td>
                                <td class="px-3 py-3">
                                    <input 
                                        v-model="item.sku"
                                        type="text"
                                        class="w-full px-2 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-mono text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 outline-none"
                                        placeholder="Auto"
                                    />
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span class="px-2 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-xs font-medium text-slate-600 dark:text-slate-400">
                                        {{ item.extracted_unit || '-' }}
                                    </span>
                                </td>
                                <td class="px-3 py-3">
                                    <div class="flex items-center gap-1">
                                        <select 
                                            v-model="item.unit_id"
                                            :class="[
                                                'flex-1 px-2 py-1.5 rounded-lg border text-sm focus:ring-2 focus:ring-purple-500 outline-none',
                                                item.unit_id 
                                                    ? 'bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white' 
                                                    : 'bg-red-50 dark:bg-red-900/20 border-red-300 dark:border-red-700 text-red-600'
                                            ]"
                                        >
                                            <option value="" disabled>-- Pilih Satuan --</option>
                                            <option v-for="unit in localUnits" :key="unit.id" :value="unit.id">
                                                {{ unit.name }} {{ unit.code ? '(' + unit.code + ')' : '' }}
                                            </option>
                                        </select>
                                        <button 
                                            v-if="!item.unit_id"
                                            type="button" 
                                            @click="openUnitModal(index)"
                                            class="shrink-0 px-2 py-1.5 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white text-[10px] font-bold transition-colors whitespace-nowrap"
                                            title="Tambah satuan baru"
                                        >
                                            + Baru
                                        </button>
                                    </div>
                                </td>
                                <td class="px-3 py-3">
                                    <input 
                                        v-model.number="item.selling_price"
                                        type="number"
                                        min="0"
                                        class="w-full px-2 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 outline-none text-right"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Warning for missing units -->
                <div v-if="bulkHasUnitIssues" class="mx-4 mt-3 p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-sm text-red-600 flex items-center gap-2">
                    <ExclamationTriangleIcon class="h-5 w-5 shrink-0" />
                    Beberapa item belum memiliki satuan yang valid (ditandai merah). Pilih satuan yang sesuai atau klik <strong>"+ Baru"</strong> untuk mendaftarkan satuan baru.
                </div>

                <!-- Unit Registration Mini Modal -->
                <div v-if="showUnitModal" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50" @click.self="showUnitModal = false">
                    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-sm p-6 space-y-4">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">➕ Tambah Satuan Baru</h3>
                        <div>
                            <label class="block text-sm font-medium text-slate-500 mb-1">Nama Satuan</label>
                            <input v-model="newUnitName" type="text" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 outline-none" placeholder="Contoh: Roll" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-500 mb-1">Kode Satuan</label>
                            <input v-model="newUnitCode" type="text" maxlength="10" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm font-mono uppercase text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 outline-none" placeholder="Contoh: ROL" />
                        </div>
                        <div class="flex gap-3 justify-end pt-2">
                            <button @click="showUnitModal = false" class="px-4 py-2 rounded-lg text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800 font-medium text-sm transition-colors">Batal</button>
                            <button @click="storeNewUnit" :disabled="isStoringUnit || !newUnitName || !newUnitCode" class="px-5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                {{ isStoringUnit ? 'Menyimpan...' : 'Simpan Satuan' }}
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="p-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <span class="text-xs text-slate-500 font-medium">{{ bulkSelectedCount }} dari {{ bulkItems.length }} item dipilih</span>
                    <div class="flex gap-3">
                        <button 
                            @click="showBulkModal = false"
                            class="px-4 py-2 rounded-lg text-slate-600 hover:bg-slate-200 dark:text-slate-300 dark:hover:bg-slate-700 font-bold text-sm transition-colors"
                        >
                            Batal
                        </button>
                        <button 
                            @click="registerBulkProducts"
                            :disabled="isBulkRegistering || bulkSelectedCount === 0 || bulkHasUnitIssues"
                            class="px-5 py-2 rounded-lg bg-purple-600 hover:bg-purple-700 text-white font-bold text-sm transition-colors flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <ArrowPathIcon v-if="isBulkRegistering" class="h-4 w-4 animate-spin" />
                            {{ isBulkRegistering ? 'Mendaftarkan...' : `Daftarkan ${bulkSelectedCount} Produk` }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
