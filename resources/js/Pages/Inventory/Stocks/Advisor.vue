<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ArrowLeftIcon,
    ArrowPathIcon,
    SparklesIcon,
    ShoppingCartIcon,
    ArrowsRightLeftIcon,
    WrenchIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    PrinterIcon,
} from '@heroicons/vue/24/outline';
import { formatCurrency, formatNumber } from '@/helpers';

const props = defineProps({
    categories: Array,
    warehouses: Array,
    suppliers: Array,
});

const selectedCategory = ref('');
const recommendations = ref(null);
const isLoading = ref(false);
const errorMsg = ref('');
const successMsg = ref('');

const isPoCreating = ref({});
const isWoCreating = ref({});
const isReclassCreating = ref({});

// Selection states for checklists
const selectedReclasses = ref([]);
const selectedPos = ref({}); // key: supplier_id, value: array of item indices
const selectedWos = ref([]);

// Filtered computed properties to remove qty <= 0 items
const filteredReclassifications = computed(() => {
    if (!recommendations.value?.reclassifications) return [];
    return recommendations.value.reclassifications.filter(rc => rc.qty > 0);
});

const filteredWorkOrders = computed(() => {
    if (!recommendations.value?.work_orders) return [];
    return recommendations.value.work_orders.filter(wo => wo.qty > 0);
});

const groupedPos = computed(() => {
    if (!recommendations.value?.purchase_orders) return {};
    const groups = {};
    recommendations.value.purchase_orders.forEach(item => {
        if (item.qty <= 0) return; // filter out qty <= 0
        const sId = item.supplier_id || 'unknown';
        if (!groups[sId]) {
            groups[sId] = {
                supplier_name: item.supplier_name || 'Tanpa Supplier Utama',
                items: []
            };
        }
        groups[sId].items.push(item);
    });
    return groups;
});

const initSelections = () => {
    selectedReclasses.value = filteredReclassifications.value.map((_, idx) => idx);
    
    selectedWos.value = filteredWorkOrders.value.map((_, idx) => idx);

    selectedPos.value = {};
    Object.keys(groupedPos.value).forEach(sId => {
        selectedPos.value[sId] = groupedPos.value[sId].items.map((_, idx) => idx);
    });
};

const runAnalysis = async () => {
    isLoading.value = true;
    errorMsg.value = '';
    recommendations.value = null;
    successMsg.value = '';

    try {
        const response = await fetch(route('inventory.intelligence.analyze'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                category_id: selectedCategory.value || null
            })
        });

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.error || 'Terjadi kesalahan sistem.');
        }

        recommendations.value = await response.json();
        initSelections();
    } catch (e) {
        errorMsg.value = e.message;
    } finally {
        isLoading.value = false;
    }
};

const handleCreatePo = async (supplierId, items) => {
    isPoCreating.value[supplierId] = true;
    errorMsg.value = '';
    successMsg.value = '';

    try {
        const response = await fetch(route('inventory.intelligence.create-po'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                supplier_id: supplierId,
                items: items
            })
        });

        const data = await response.json();
        if (!response.ok) throw new Error(data.error || 'Gagal membuat draft PO.');

        successMsg.value = data.success;
        if (data.redirect_url) {
            window.location.href = data.redirect_url;
        }
    } catch (e) {
        errorMsg.value = e.message;
    } finally {
        isPoCreating.value[supplierId] = false;
    }
};

const handleCreatePoSelected = async (supplierId) => {
    const selectedIndices = selectedPos.value[supplierId] || [];
    if (selectedIndices.length === 0) {
        errorMsg.value = 'Silakan pilih setidaknya satu item PO.';
        return;
    }
    const allItems = groupedPos.value[supplierId].items;
    const itemsToSend = selectedIndices.map(idx => ({
        product_id: allItems[idx].product_id,
        qty: allItems[idx].qty
    }));
    await handleCreatePo(supplierId, itemsToSend);
};

const handleCreateWo = async (productKey, items) => {
    isWoCreating.value[productKey] = true;
    errorMsg.value = '';
    successMsg.value = '';

    try {
        const response = await fetch(route('inventory.intelligence.create-wo'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                items: items
            })
        });

        const data = await response.json();
        if (!response.ok) throw new Error(data.error || 'Gagal membuat draft WO.');

        successMsg.value = data.success;
        if (data.redirect_url) {
            window.location.href = data.redirect_url;
        }
    } catch (e) {
        errorMsg.value = e.message;
    } finally {
        isWoCreating.value[productKey] = false;
    }
};

const handleCreateWoSelected = async () => {
    if (selectedWos.value.length === 0) {
        errorMsg.value = 'Silakan pilih setidaknya satu item Work Order.';
        return;
    }
    const itemsToSend = selectedWos.value.map(idx => {
        const wo = filteredWorkOrders.value[idx];
        return {
            product_id: wo.product_id,
            qty_planned: wo.qty,
            production_type: wo.production_type || 'internal',
            supplier_id: wo.supplier_id || null,
        };
    });
    await handleCreateWo('bulk', itemsToSend);
};

const handleCreateReclass = async (itemsKey, items) => {
    isReclassCreating.value[itemsKey] = true;
    errorMsg.value = '';
    successMsg.value = '';

    try {
        const response = await fetch(route('inventory.intelligence.create-reclass'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                items: items
            })
        });

        const data = await response.json();
        if (!response.ok) throw new Error(data.error || 'Gagal membuat draft Reclass.');

        successMsg.value = data.success;
        if (data.redirect_url) {
            window.location.href = data.redirect_url;
        }
    } catch (e) {
        errorMsg.value = e.message;
    } finally {
        isReclassCreating.value[itemsKey] = false;
    }
};

const handleCreateReclassSelected = async () => {
    if (selectedReclasses.value.length === 0) {
        errorMsg.value = 'Silakan pilih setidaknya satu item Reclass.';
        return;
    }
    const itemsToSend = selectedReclasses.value.map(idx => {
        const rc = filteredReclassifications.value[idx];
        return {
            source_product_id: rc.source_product_id,
            target_product_id: rc.target_product_id,
            qty: rc.qty
        };
    });
    await handleCreateReclass('bulk', itemsToSend);
};

const formatMd = (text) => {
    if (!text) return '';
    let html = text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;");
    // Bold
    html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    // Bullet points
    html = html.replace(/^\s*-\s+(.*?)$/gm, '<li class="ml-4 list-disc mb-1">$1</li>');
    // Headers
    html = html.replace(/^\s*###\s+(.*?)$/gm, '<h5 class="text-sm font-bold mt-4 mb-2 text-slate-800 dark:text-slate-200">$1</h5>');
    html = html.replace(/^\s*####\s+(.*?)$/gm, '<h6 class="text-xs font-bold mt-3 mb-1 text-slate-700 dark:text-slate-300">$1</h6>');
    // Newlines
    html = html.replace(/\n/g, '<br/>');
    return html;
};

// Checkbox helper functions
const isAllReclassSelected = computed(() => {
    return filteredReclassifications.value.length > 0 && selectedReclasses.value.length === filteredReclassifications.value.length;
});

const isAllWoSelected = computed(() => {
    return filteredWorkOrders.value.length > 0 && selectedWos.value.length === filteredWorkOrders.value.length;
});

const isAllPoSelected = (sId) => {
    const items = groupedPos.value[sId]?.items || [];
    const selected = selectedPos.value[sId] || [];
    return items.length > 0 && selected.length === items.length;
};

const toggleReclassSelection = (idx) => {
    const sIdx = selectedReclasses.value.indexOf(idx);
    if (sIdx > -1) {
        selectedReclasses.value.splice(sIdx, 1);
    } else {
        selectedReclasses.value.push(idx);
    }
};

const toggleWoSelection = (idx) => {
    const sIdx = selectedWos.value.indexOf(idx);
    if (sIdx > -1) {
        selectedWos.value.splice(sIdx, 1);
    } else {
        selectedWos.value.push(idx);
    }
};

const togglePoSelection = (sId, idx) => {
    if (!selectedPos.value[sId]) {
        selectedPos.value[sId] = [];
    }
    const sIdx = selectedPos.value[sId].indexOf(idx);
    if (sIdx > -1) {
        selectedPos.value[sId].splice(sIdx, 1);
    } else {
        selectedPos.value[sId].push(idx);
    }
};

const toggleSelectAllReclass = () => {
    if (isAllReclassSelected.value) {
        selectedReclasses.value = [];
    } else {
        selectedReclasses.value = filteredReclassifications.value.map((_, idx) => idx);
    }
};

const toggleSelectAllWo = () => {
    if (isAllWoSelected.value) {
        selectedWos.value = [];
    } else {
        selectedWos.value = filteredWorkOrders.value.map((_, idx) => idx);
    }
};

const toggleSelectAllPo = (sId) => {
    if (isAllPoSelected(sId)) {
        selectedPos.value[sId] = [];
    } else {
        selectedPos.value[sId] = groupedPos.value[sId].items.map((_, idx) => idx);
    }
};

const selectedCategoryName = computed(() => {
    if (!selectedCategory.value) return 'Semua Kategori';
    const cat = props.categories.find(c => c.id == selectedCategory.value);
    return cat ? cat.name : '';
});

const printReport = () => {
    window.print();
};

</script>

<template>
    <Head title="AI Stock & Procurement Advisor" />

    <AppLayout title="Inventory Intelligence">
        <div class="max-w-full px-4 sm:px-6 lg:px-8 mx-auto">
            
            <!-- Header & Back Button -->
            <div class="flex justify-between items-center mb-6">
                <div class="flex items-center gap-3">
                    <Link href="/inventory/stocks" class="p-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-500 hover:text-slate-900 dark:hover:text-white transition-all shadow-sm print:hidden">
                        <ArrowLeftIcon class="h-4 w-4" />
                    </Link>
                    <div>
                        <h2 class="font-bold text-xl text-slate-900 dark:text-white flex items-center gap-2">
                            <SparklesIcon class="h-5 w-5 text-indigo-500 animate-pulse print:hidden" />
                            AI Stock & Procurement Advisor
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Analisis stok kritis terotomatisasi berdasarkan Sales Order aktif.
                            <a href="/docs/ai-advisor.html" target="_blank" class="ml-2 text-indigo-500 hover:underline font-semibold print:hidden">Lihat Dokumentasi & Diagram Alur ➔</a>
                        </p>
                        <p v-if="recommendations" class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-2 flex-wrap">
                            <span>Kategori: <strong class="text-slate-800 dark:text-slate-200">{{ selectedCategoryName }}</strong></span>
                            <span class="text-slate-300 dark:text-slate-700">|</span>
                            <span>Tanggal Analisis: <strong class="text-slate-800 dark:text-slate-200">{{ new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}</strong></span>
                        </p>
                    </div>
                </div>

                <!-- Filter & Trigger Actions -->
                <div class="flex items-center gap-3 print:hidden">
                    <div class="w-48">
                        <select v-model="selectedCategory" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs text-slate-700 dark:text-slate-300 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Semua Kategori</option>
                            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>
                    <button @click="runAnalysis" :disabled="isLoading" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white shadow-md disabled:opacity-50 transition-all">
                        <ArrowPathIcon class="h-3.5 w-3.5" :class="{ 'animate-spin': isLoading }" />
                        Mulai Analisis
                    </button>
                    <button v-if="recommendations" @click="printReport" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white shadow-md hover:shadow-teal-900/20 active:scale-[0.98] transition-all">
                        <PrinterIcon class="h-3.5 w-3.5" />
                        Cetak
                    </button>
                </div>
            </div>

            <!-- Banner Messages -->
            <div v-if="errorMsg" class="mb-4 p-3 bg-red-100 dark:bg-red-950/40 text-red-800 dark:text-red-400 rounded-xl text-xs flex items-center gap-2 border border-red-200 dark:border-red-900 print:hidden">
                <ExclamationTriangleIcon class="h-4 w-4" />
                {{ errorMsg }}
            </div>
            <div v-if="successMsg" class="mb-4 p-3 bg-green-100 dark:bg-green-950/40 text-green-800 dark:text-green-400 rounded-xl text-xs flex items-center gap-2 border border-green-200 dark:border-green-900 print:hidden">
                <CheckCircleIcon class="h-4 w-4" />
                {{ successMsg }}
            </div>

            <!-- Loading State -->
            <div v-if="isLoading" class="flex flex-col items-center justify-center py-20 bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
                <div class="relative flex items-center justify-center mb-4">
                    <div class="absolute h-12 w-12 rounded-full border-4 border-indigo-500/20 animate-ping"></div>
                    <div class="h-10 w-10 rounded-full border-4 border-t-indigo-600 border-indigo-200 animate-spin"></div>
                </div>
                <h4 class="font-bold text-slate-800 dark:text-slate-200 text-sm animate-pulse">Menjalankan Analisis Cerdas...</h4>
                <p class="text-xs text-slate-500 mt-1 max-w-sm text-center">Menyaring pesanan, mengevaluasi stock ekuivalen (Reclass), memproses ledakan BOM, dan merumuskan saran pengadaan.</p>
            </div>

            <!-- Analysis Content -->
            <div v-else-if="recommendations" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                
                <!-- Recommendations Section -->
                <div class="lg:col-span-8 space-y-6">
                    
                    <!-- 1. Reclass Recommendations -->
                    <div v-if="filteredReclassifications.length > 0" class="bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
                        <div class="flex justify-between items-center mb-4 flex-wrap gap-2">
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <div class="p-1.5 rounded-lg bg-teal-500/10 text-teal-400">
                                    <ArrowsRightLeftIcon class="h-4.5 w-4.5" />
                                </div>
                                Rekomendasi Stock Reclassification
                            </h3>
                            <div class="flex items-center gap-3 print:hidden">
                                <label class="inline-flex items-center text-xs text-slate-500 cursor-pointer">
                                    <input type="checkbox" :checked="isAllReclassSelected" @change="toggleSelectAllReclass" class="rounded border-slate-300 text-teal-600 focus:ring-teal-500 mr-1.5 h-3.5 w-3.5 dark:border-slate-800 dark:bg-slate-950" />
                                    Pilih Semua
                                </label>
                                <button 
                                    @click="handleCreateReclassSelected"
                                    :disabled="selectedReclasses.length === 0 || isReclassCreating['bulk']"
                                    class="px-3 py-1.5 text-[11px] font-semibold rounded-lg bg-teal-600 hover:bg-teal-700 text-white disabled:opacity-50 transition-all flex items-center gap-1 shadow-sm"
                                >
                                    <ArrowPathIcon v-if="isReclassCreating['bulk']" class="h-3 w-3 animate-spin" />
                                    Buat Reclass Terpilih ({{ selectedReclasses.length }})
                                </button>
                            </div>
                        </div>
                        
                        <div class="space-y-4">
                            <div v-for="(rc, idx) in filteredReclassifications" :key="idx" class="p-4 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                                <div class="flex items-start">
                                    <input type="checkbox" :checked="selectedReclasses.includes(idx)" @change="toggleReclassSelection(idx)" class="mt-1 rounded border-slate-300 text-teal-600 focus:ring-teal-500 mr-3 h-4 w-4 dark:border-slate-800 dark:bg-slate-950 print:hidden" />
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="text-xs font-bold text-slate-500 uppercase">Gunakan</span>
                                            <span class="text-xs font-semibold px-2 py-0.5 rounded bg-teal-500/10 text-teal-400">{{ rc.source_sku }}</span>
                                            <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">{{ rc.source_name }}</span>
                                        </div>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="text-xs font-bold text-slate-500 uppercase">Untuk</span>
                                            <span class="text-xs font-semibold px-2 py-0.5 rounded bg-indigo-500/10 text-indigo-400">{{ rc.target_sku }}</span>
                                            <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">{{ rc.target_name }}</span>
                                        </div>
                                        <p class="text-[10px] text-slate-500 mt-1 italic">{{ rc.reason }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 w-full md:w-auto justify-between md:justify-end">
                                    <div class="text-right">
                                        <span class="text-[10px] text-slate-500 block">Kuantitas</span>
                                        <span class="font-bold text-sm text-teal-400">{{ formatNumber(rc.qty) }}</span>
                                    </div>
                                    <button 
                                        @click="handleCreateReclass(idx, [{ source_product_id: rc.source_product_id, target_product_id: rc.target_product_id, qty: rc.qty }])"
                                        :disabled="isReclassCreating[idx]"
                                        class="px-3.5 py-2 text-xs font-semibold rounded-lg bg-teal-600 hover:bg-teal-700 text-white disabled:opacity-50 transition-all flex items-center gap-1.5 shadow-sm print:hidden"
                                    >
                                        <ArrowPathIcon v-if="isReclassCreating[idx]" class="h-3 w-3 animate-spin" />
                                        Buat Reclass Draft
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
 
                    <!-- 2. Purchase Order Recommendations (Grouped by Supplier) -->
                    <div v-if="Object.keys(groupedPos).length > 0" class="bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                            <div class="p-1.5 rounded-lg bg-amber-500/10 text-amber-400">
                                <ShoppingCartIcon class="h-4.5 w-4.5" />
                            </div>
                            Rekomendasi Pembuatan Purchase Order
                        </h3>
                        
                        <div class="space-y-6">
                            <div v-for="(group, sId) in groupedPos" :key="sId" class="border border-slate-100 dark:border-slate-800 rounded-xl overflow-hidden">
                                <div class="bg-slate-50 dark:bg-slate-950 px-4 py-3 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center flex-wrap gap-2">
                                    <div class="flex items-center">
                                        <input type="checkbox" :checked="isAllPoSelected(sId)" @change="toggleSelectAllPo(sId)" class="rounded border-slate-300 text-amber-600 focus:ring-amber-500 mr-2 h-4 w-4 dark:border-slate-800 dark:bg-slate-950 print:hidden" />
                                        <h4 class="font-bold text-xs text-slate-700 dark:text-slate-300 uppercase tracking-wider">{{ group.supplier_name }}</h4>
                                    </div>
                                    <button 
                                        v-if="sId !== 'unknown'"
                                        @click="handleCreatePoSelected(sId)"
                                        :disabled="isPoCreating[sId] || !selectedPos[sId] || selectedPos[sId].length === 0"
                                        class="px-3 py-1.5 text-[11px] font-semibold rounded-lg bg-amber-600 hover:bg-amber-700 text-white disabled:opacity-50 transition-all flex items-center gap-1 shadow-sm print:hidden"
                                    >
                                        <ArrowPathIcon v-if="isPoCreating[sId]" class="h-3 w-3 animate-spin" />
                                        Buat PO Terpilih ({{ selectedPos[sId]?.length || 0 }})
                                    </button>
                                </div>
                                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <div v-for="(poItem, pIdx) in group.items" :key="pIdx" class="p-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                                        <div class="flex items-start">
                                            <input type="checkbox" :checked="selectedPos[sId]?.includes(pIdx)" @change="togglePoSelection(sId, pIdx)" class="mt-1 rounded border-slate-300 text-amber-600 focus:ring-amber-500 mr-3 h-4 w-4 dark:border-slate-800 dark:bg-slate-950 print:hidden" />
                                            <div class="space-y-0.5">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs font-semibold px-2 py-0.5 rounded bg-amber-500/10 text-amber-400">{{ poItem.sku }}</span>
                                                    <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">{{ poItem.name }}</span>
                                                </div>
                                                <p class="text-[10px] text-slate-500 italic">{{ poItem.reason }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-4 w-full sm:w-auto justify-between sm:justify-end">
                                            <div class="text-right">
                                                <span class="text-[10px] text-slate-500 block">Kuantitas</span>
                                                <span class="font-bold text-xs text-slate-800 dark:text-slate-200">{{ formatNumber(poItem.qty) }}</span>
                                            </div>
                                            <button 
                                                v-if="sId !== 'unknown'"
                                                @click="handleCreatePo(sId, [{ product_id: poItem.product_id, qty: poItem.qty }])"
                                                :disabled="isPoCreating[sId]"
                                                class="px-2.5 py-1.5 text-[10px] font-semibold rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-750 transition-all shadow-sm print:hidden"
                                            >
                                                Buat Draft PO
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
 
                    <!-- 3. Work Order Recommendations -->
                    <div v-if="filteredWorkOrders.length > 0" class="bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
                        <div class="flex justify-between items-center mb-4 flex-wrap gap-2">
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <div class="p-1.5 rounded-lg bg-blue-500/10 text-blue-400">
                                    <WrenchIcon class="h-4.5 w-4.5" />
                                </div>
                                Rekomendasi Perencanaan Produksi (Work Order)
                            </h3>
                            <div class="flex items-center gap-3 print:hidden">
                                <label class="inline-flex items-center text-xs text-slate-500 cursor-pointer">
                                    <input type="checkbox" :checked="isAllWoSelected" @change="toggleSelectAllWo" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 mr-1.5 h-3.5 w-3.5 dark:border-slate-800 dark:bg-slate-950" />
                                    Pilih Semua
                                </label>
                                <button 
                                    @click="handleCreateWoSelected"
                                    :disabled="selectedWos.length === 0 || isWoCreating['bulk']"
                                    class="px-3 py-1.5 text-[11px] font-semibold rounded-lg bg-blue-600 hover:bg-blue-700 text-white disabled:opacity-50 transition-all flex items-center gap-1 shadow-sm"
                                >
                                    <ArrowPathIcon v-if="isWoCreating['bulk']" class="h-3 w-3 animate-spin" />
                                    Buat WO Terpilih ({{ selectedWos.length }})
                                </button>
                            </div>
                        </div>
                        
                        <div class="space-y-4">
                            <div v-for="(wo, wIdx) in filteredWorkOrders" :key="wIdx" class="p-4 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                                <div class="flex items-start">
                                    <input type="checkbox" :checked="selectedWos.includes(wIdx)" @change="toggleWoSelection(wIdx)" class="mt-1 rounded border-slate-300 text-blue-600 focus:ring-blue-500 mr-3 h-4 w-4 dark:border-slate-800 dark:bg-slate-950 print:hidden" />
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="text-xs font-semibold px-2 py-0.5 rounded bg-blue-500/10 text-blue-400">{{ wo.sku }}</span>
                                            <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">{{ wo.name }}</span>
                                            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded" :class="wo.production_type === 'subcontract' ? 'bg-purple-500/10 text-purple-400' : 'bg-blue-500/10 text-blue-400'">
                                                {{ wo.production_type === 'subcontract' ? 'Subkontrak' : 'Internal' }}
                                            </span>
                                            <span v-if="wo.supplier_name" class="text-[10px] text-slate-500">➔ {{ wo.supplier_name }}</span>
                                        </div>
                                        <p class="text-[10px] text-slate-500 italic">{{ wo.reason }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 w-full md:w-auto justify-between md:justify-end">
                                    <div class="text-right">
                                        <span class="text-[10px] text-slate-500 block">Rencana Qty</span>
                                        <span class="font-bold text-sm text-blue-400">{{ formatNumber(wo.qty) }}</span>
                                    </div>
                                    <button 
                                        @click="handleCreateWo(wIdx, [{ product_id: wo.product_id, qty_planned: wo.qty, production_type: wo.production_type || 'internal', supplier_id: wo.supplier_id || null }])"
                                        :disabled="isWoCreating[wIdx]"
                                        class="px-3.5 py-2 text-xs font-semibold rounded-lg bg-blue-600 hover:bg-blue-700 text-white disabled:opacity-50 transition-all flex items-center gap-1.5 shadow-sm print:hidden"
                                    >
                                        <ArrowPathIcon v-if="isWoCreating[wIdx]" class="h-3 w-3 animate-spin" />
                                        Buat WO Draft
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- AI Commentary Box -->
                <div class="lg:col-span-4 space-y-6">
                    <div class="bg-indigo-950/20 dark:bg-slate-900 border border-indigo-500/30 dark:border-slate-800 rounded-2xl p-5 shadow-sm relative overflow-hidden">
                        <!-- Glowing effect -->
                        <div class="absolute top-0 right-0 h-40 w-40 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
                        
                        <h3 class="text-sm font-bold text-indigo-700 dark:text-indigo-400 mb-4 flex items-center gap-2">
                            <SparklesIcon class="h-4.5 w-4.5 animate-bounce" />
                            Analisis Narasi AI
                        </h3>
                        
                        <div 
                            class="text-xs text-slate-700 dark:text-slate-400 leading-relaxed font-normal"
                            v-html="formatMd(recommendations.analysis_summary)"
                        ></div>
                    </div>
                </div>

            </div>

            <!-- Placeholder State when not yet analyzed -->
            <div v-else class="flex flex-col items-center justify-center py-20 bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800 rounded-2xl p-8 shadow-sm relative overflow-hidden print:hidden">
                <!-- Glowing background circles -->
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-indigo-500/5 dark:bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
                
                <div class="relative z-10 flex flex-col items-center max-w-lg text-center">
                    <div class="p-4 rounded-2xl bg-indigo-50 dark:bg-indigo-950/30 text-indigo-600 dark:text-indigo-400 mb-6 shadow-inner">
                        <SparklesIcon class="h-10 w-10 animate-pulse" />
                    </div>
                    
                    <h3 class="font-extrabold text-slate-800 dark:text-slate-200 text-lg mb-2">
                        AI Stock & Procurement Advisor
                    </h3>
                    
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-8">
                        Sistem kecerdasan inventaris hibrida siap menganalisis data pesanan penjualan aktif (Sales Order), ketersediaan stok saat ini, stok ekuivalen (Reclass), ledakan tagihan bahan baku (BOM), dan menyarankan tindakan pengadaan/manufaktur secara otomatis.
                    </p>
                    
                    <button 
                        @click="runAnalysis" 
                        class="inline-flex items-center gap-2 px-6 py-3 text-sm font-semibold rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white shadow-lg shadow-indigo-900/25 hover:shadow-indigo-900/40 transition-all hover:scale-[1.02] active:scale-[0.98]"
                    >
                        <SparklesIcon class="h-4.5 w-4.5" />
                        Mulai Analisis Cerdas
                    </button>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<style scoped>
@media print {
  /* Page margins */
  @page {
    margin: 0.8cm;
  }

  /* Print layout optimizations */
  .grid-cols-1 {
    display: flex !important;
    flex-direction: column !important;
    gap: 0.5rem !important;
  }
  
  .lg\:col-span-8 {
    order: 2 !important; /* Recommendations second */
    width: 100% !important;
  }
  
  .lg\:col-span-4 {
    order: 1 !important; /* AI Commentary first */
    width: 100% !important;
    margin-bottom: 0.75rem !important;
  }

  /* Compress layouts, margins and paddings */
  .mb-6 {
    margin-bottom: 0.5rem !important;
  }
  
  .space-y-6 > * + * {
    margin-top: 0.5rem !important;
  }

  .space-y-4 > * + * {
    margin-top: 0.35rem !important;
  }

  .p-5 {
    padding: 0.6rem 0.75rem !important;
  }

  .p-4 {
    padding: 0.4rem 0.6rem !important;
  }

  .px-4 {
    padding-left: 0.4rem !important;
    padding-right: 0.4rem !important;
  }

  .py-3 {
    padding-top: 0.25rem !important;
    padding-bottom: 0.25rem !important;
  }

  .gap-6 {
    gap: 0.5rem !important;
  }

  /* Scale font-sizes down for compact printing */
  .text-xl {
    font-size: 1.15rem !important;
  }
  .text-lg {
    font-size: 1rem !important;
  }
  .text-sm {
    font-size: 0.75rem !important;
  }
  .text-xs {
    font-size: 0.7rem !important;
  }
  .text-\[11px\] {
    font-size: 0.68rem !important;
  }
  .text-\[10px\] {
    font-size: 0.62rem !important;
  }
  .font-bold {
    font-weight: 700 !important;
  }

  /* Force light theme elements for cleaner printing */
  .bg-white, .dark\:bg-slate-900, .bg-slate-50, .dark\:bg-slate-950 {
    background-color: #ffffff !important;
    color: #0f172a !important;
    border-color: #cbd5e1 !important; /* border-slate-300 */
    box-shadow: none !important;
  }

  .text-slate-900, .dark\:text-white, .text-slate-800, .dark\:text-slate-200, .text-slate-700, .dark\:text-slate-300 {
    color: #0f172a !important;
  }

  .text-slate-500, .dark\:text-slate-400 {
    color: #475569 !important;
  }

  /* Prevent page breaks inside individual item rows instead of the entire card container */
  .space-y-4 > div, 
  .divide-y > div,
  .border.rounded-xl {
    page-break-inside: avoid !important;
  }
  
  /* Allow cards containing lists to break across pages */
  .bg-white.rounded-2xl {
    page-break-inside: auto !important;
  }
}
</style>
