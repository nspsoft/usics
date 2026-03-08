<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
import { ref, watch, computed } from 'vue';
import { 
    MagnifyingGlassIcon, 
    ArrowUpTrayIcon, 
    CalendarDaysIcon,
    XMarkIcon,
    ChevronUpIcon,
    ChevronDownIcon,
    UserIcon,
    SparklesIcon,
    ArrowPathIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    EyeIcon,
    ArrowsPointingOutIcon,
    TrashIcon,
    ArrowDownTrayIcon,
} from '@heroicons/vue/24/outline';
import { formatNumber } from '@/helpers';
import axios from 'axios';

const props = defineProps({
    schedules: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const date = ref(props.filters.date || '');
const sortField = ref(props.filters.sort || 'delivery_date');
const sortDirection = ref(props.filters.direction || 'asc');
const importModalOpen = ref(false);
const fileInput = ref(null);
const flashMessage = ref(null);
const flashType = ref('success');

const page = usePage();

// Watch for flash messages from Inertia
watch(() => page.props.flash, (flash) => {
    if (flash?.success) {
        flashMessage.value = flash.success;
        flashType.value = 'success';
        setTimeout(() => flashMessage.value = null, 8000);
    } else if (flash?.error) {
        flashMessage.value = flash.error;
        flashType.value = 'error';
        setTimeout(() => flashMessage.value = null, 8000);
    }
}, { deep: true, immediate: true });

const form = useForm({
    file: null,
    sales_name: '',
});

const sort = (field) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }
    handleSearch();
};

const handleSearch = () => {
    router.get(route('sales.planning.schedule.index'), {
        search: search.value, 
        date: date.value,
        sort: sortField.value,
        direction: sortDirection.value,
    }, { preserveState: true, replace: true });
};

watch([search, date], () => {
    handleSearch();
});

const openImportModal = () => {
    importModalOpen.value = true;
};

const closeImportModal = () => {
    importModalOpen.value = false;
    form.reset();
};

const submitImport = () => {
    if (form.file) {
        form.post(route('sales.planning.schedule.import'), {
            onSuccess: () => {
                closeImportModal();
            },
            onError: (errors) => {
                if (errors.file) {
                    alert(errors.file);
                }
            },
        });
    }
};

const isDelayed = (schedule) => {
    return new Date(schedule.delivery_date) < new Date().setHours(0,0,0,0);
};

const isUpcoming = (schedule) => {
    const today = new Date();
    const nextWeek = new Date();
    nextWeek.setDate(today.getDate() + 7);
    const deliveryDate = new Date(schedule.delivery_date);
    return deliveryDate >= today && deliveryDate <= nextWeek;
};

const formatMonth = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('id-ID', { year: 'numeric', month: 'long' });
};

const formatDateShort = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short' });
};

// ========== AI Matrix Extractor ==========
const aiModalOpen = ref(false);
const aiStep = ref(1);
const aiFile = ref(null);
const aiFilePreview = ref(null);
const isExtracting = ref(false);
const isSaving = ref(false);
const aiError = ref(null);
const extractedData = ref({ month_year: '', items: [] });

const openAiModal = () => {
    aiModalOpen.value = true;
    aiStep.value = 1;
    aiFile.value = null;
    aiFilePreview.value = null;
    aiError.value = null;
    extractedData.value = { month_year: '', items: [] };
};

const closeAiModal = () => {
    aiModalOpen.value = false;
    if (aiFilePreview.value) URL.revokeObjectURL(aiFilePreview.value);
};

const aiFileIsPdf = ref(false);

const handleAiFileSelect = (e) => {
    const file = e.target.files[0];
    if (file) {
        aiFile.value = file;
        aiFileIsPdf.value = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
        aiFilePreview.value = aiFileIsPdf.value ? null : URL.createObjectURL(file);
    }
};

const extractMatrix = async () => {
    if (!aiFile.value) return;
    isExtracting.value = true;
    aiError.value = null;
    const formData = new FormData();
    formData.append('file', aiFile.value);
    try {
        const response = await axios.post(route('sales.planning.schedule.extract-matrix'), formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
            maxRedirects: 0,
        });
        if (response.data.success) {
            extractedData.value = response.data.data;
            aiStep.value = 2;
        } else {
            aiError.value = response.data.message || 'Gagal mengekstrak data.';
        }
    } catch (err) {
        if (err.response?.status === 419) {
            aiError.value = 'Sesi telah kedaluwarsa. Silakan refresh halaman dan coba lagi.';
        } else if (err.response?.data?.message?.includes('GET method')) {
            aiError.value = 'Terjadi kesalahan redirect di server. Pastikan route:clear sudah dijalankan di production.';
        } else {
            aiError.value = err.response?.data?.message || 'Gagal mengekstrak data. Periksa koneksi dan konfigurasi AI.';
        }
    } finally {
        isExtracting.value = false;
    }
};

const removeItem = (index) => extractedData.value.items.splice(index, 1);

const downloadExcel = async () => {
    isSaving.value = true;
    try {
        const response = await axios.post(route('sales.planning.schedule.export-extraction'), {
            items: extractedData.value.items,
            month_year: extractedData.value.month_year || '',
        }, { responseType: 'blob' });

        // Trigger file download
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `ai_extraction_schedule_${Date.now()}.xlsx`);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
    } catch (err) {
        alert(err.response?.data?.message || 'Gagal mengunduh Excel.');
    } finally {
        isSaving.value = false;
    }
};
</script>

<template>
    <Head title="Delivery Schedule" />

    <AppLayout title="Delivery Schedule">
        <template #header>
            <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">
                Delivery Schedule
            </h2>
        </template>

        <div class="p-4 sm:p-6 lg:p-8">
            <!-- Flash Message -->
            <Transition enter-active-class="transition ease-out duration-300" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition ease-in duration-200" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-2">
                <div v-if="flashMessage" class="mb-4 p-4 rounded-xl flex items-center justify-between gap-3 shadow-md" :class="flashType === 'success' ? 'bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 dark:text-emerald-400' : 'bg-red-500/10 border border-red-500/20 text-red-700 dark:text-red-400'">
                    <div class="flex items-center gap-3">
                        <CheckCircleIcon v-if="flashType === 'success'" class="h-5 w-5 shrink-0" />
                        <ExclamationTriangleIcon v-else class="h-5 w-5 shrink-0" />
                        <span class="text-sm font-medium">{{ flashMessage }}</span>
                    </div>
                    <button @click="flashMessage = null" class="p-1 rounded-lg hover:bg-black/5 dark:hover:bg-white/5 transition-colors">
                        <XMarkIcon class="h-4 w-4" />
                    </button>
                </div>
            </Transition>
            <!-- Main Container -->
            <div class="glass-card rounded-2xl overflow-hidden p-6">
                <!-- Actions Row -->
                <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                    <div class="flex gap-4 w-full md:w-auto">
                        <div class="relative w-full md:w-64">
                            <input 
                                v-model="search"
                                type="text" 
                                placeholder="Search Customer / PO / Product..." 
                                class="w-full pl-10 pr-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:ring-blue-500 focus:border-blue-500"
                            >
                            <MagnifyingGlassIcon class="w-5 h-5 text-slate-400 absolute left-3 top-2.5" />
                        </div>
                        <input 
                            v-model="date"
                            type="date" 
                            class="rounded-lg border border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:ring-blue-500 focus:border-blue-500"
                        >
                    </div>
                    
                    <div class="flex gap-2">
                        <a 
                            :href="route('sales.planning.schedule.export', { search, date })"
                            class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                            </svg>
                            Export Excel
                        </a>
                        <button 
                            @click="openAiModal"
                            class="flex items-center gap-2 bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-700 hover:to-orange-700 text-white px-4 py-2 rounded-lg transition-all shadow-md hover:shadow-lg group"
                        >
                            <SparklesIcon class="w-5 h-5 group-hover:animate-pulse" />
                            AI Matrix Extractor
                        </button>
                        <button 
                            @click="openImportModal"
                            class="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg transition-colors"
                        >
                            <ArrowUpTrayIcon class="w-5 h-5" />
                            Import Excel
                        </button>
                        <Link 
                            :href="route('sales.planning.schedule.comparison')"
                            class="flex items-center gap-2 bg-slate-800 dark:bg-slate-700 hover:bg-slate-900 dark:hover:bg-slate-600 text-white px-4 py-2 rounded-lg transition-colors border border-slate-700 dark:border-slate-600"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-indigo-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
                            </svg>
                            View Comparison
                        </Link>
                    </div>
                </div>

                <!-- Table Wrapper -->
                <div class="rounded-2xl glass-card overflow-hidden">
                    <div class="overflow-x-auto overflow-y-auto max-h-[600px]">
                        <table class="w-full text-left text-sm text-slate-600 dark:text-slate-400">
                            <thead class="bg-slate-50 dark:bg-slate-700/50 text-xs uppercase font-semibold text-slate-500 dark:text-slate-300">
                                <tr class="border-b border-slate-200 dark:border-slate-700">
                                    <th @click="sort('delivery_date')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">
                                        <div class="flex items-center gap-1">
                                            Delivery Date
                                            <span v-if="sortField === 'delivery_date'" class="text-blue-600 dark:text-blue-400">
                                                <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                                <ChevronDownIcon v-else class="h-3 w-3" />
                                            </span>
                                        </div>
                                    </th>
                                    <th @click="sort('customer_name')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">
                                        <div class="flex items-center gap-1">
                                            Customer
                                            <span v-if="sortField === 'customer_name'" class="text-blue-600 dark:text-blue-400">
                                                <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                                <ChevronDownIcon v-else class="h-3 w-3" />
                                            </span>
                                        </div>
                                    </th>
                                    <th @click="sort('po_number')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">
                                        <div class="flex items-center gap-1">
                                            PO Number
                                            <span v-if="sortField === 'po_number'" class="text-blue-600 dark:text-blue-400">
                                                <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                                <ChevronDownIcon v-else class="h-3 w-3" />
                                            </span>
                                        </div>
                                    </th>
                                    <th @click="sort('product_name')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">
                                        <div class="flex items-center gap-1">
                                            Product
                                            <span v-if="sortField === 'product_name'" class="text-blue-600 dark:text-blue-400">
                                                <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                                <ChevronDownIcon v-else class="h-3 w-3" />
                                            </span>
                                        </div>
                                    </th>
                                    <th @click="sort('qty_scheduled')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 text-right cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">
                                        <div class="flex items-center justify-end gap-1">
                                            Qty Scheduled
                                            <span v-if="sortField === 'qty_scheduled'" class="text-blue-600 dark:text-blue-400">
                                                <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                                <ChevronDownIcon v-else class="h-3 w-3" />
                                            </span>
                                        </div>
                                    </th>
                                    <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">Sales Name</th>
                                    <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr v-for="schedule in schedules.data" :key="schedule.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors">
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400 uppercase font-medium">
                                        <div class="flex items-center gap-2">
                                            <CalendarDaysIcon class="w-4 h-4 text-slate-400" />
                                            {{ new Date(schedule.delivery_date).toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' }) }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-2">
                                        <div class="text-sm font-semibold text-slate-900 dark:text-white">{{ schedule.customer?.name }}</div>
                                        <div class="text-[10px] text-slate-500 font-mono tracking-tight">{{ schedule.customer?.code }}</div>
                                    </td>
                                    <td class="px-4 py-2 font-mono text-sm text-slate-900 dark:text-white">{{ schedule.po_number || '-' }}</td>
                                    <td class="px-4 py-2">
                                        <div class="text-sm font-medium text-slate-900 dark:text-white">{{ schedule.product?.name }}</div>
                                        <div class="text-[10px] text-slate-500 font-mono tracking-tight">{{ schedule.product?.sku }}</div>
                                    </td>
                                    <td class="px-4 py-2 text-right font-mono text-sm text-slate-900 dark:text-white">
                                        {{ formatNumber(schedule.qty_scheduled) }} <span class="text-[10px] text-slate-500">{{ schedule.product?.unit?.code }}</span>
                                    </td>
                                    <td class="px-4 py-2">
                                        <div class="text-sm font-medium text-slate-900 dark:text-white truncate max-w-[120px]" :title="schedule.sales_name">{{ schedule.sales_name || '-' }}</div>
                                        <div class="text-[9px] text-slate-500 uppercase flex items-center gap-1" v-if="schedule.created_by">
                                            <UserIcon class="w-2.5 h-2.5" />
                                            {{ schedule.created_by_user?.name || 'User' }} • {{ formatDateShort(schedule.created_at) }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        <span v-if="isDelayed(schedule)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-red-100 text-red-800 dark:bg-red-800/20 dark:text-red-400 border border-red-200 dark:border-red-800/50">
                                            Delayed
                                        </span>
                                        <span v-else-if="isUpcoming(schedule)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-100 text-amber-800 dark:bg-amber-800/20 dark:text-amber-400 border border-amber-200 dark:border-amber-800/50">
                                            Upcoming
                                        </span>
                                        <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-blue-100 text-blue-800 dark:bg-blue-800/20 dark:text-blue-400 border border-blue-200 dark:border-blue-800/50">
                                            Scheduled
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="schedules.data.length === 0">
                                    <td colspan="7" class="px-4 py-12 text-center text-slate-500 whitespace-nowrap">
                                        No schedule data found. Import Excel to get started.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination Wrapper -->
                <div class="mt-6">
                    <Pagination :links="schedules.links" />
                </div>
            </div>

            <!-- Delivery Operations Guide -->
            <div class="mt-8 relative hidden md:block">
                <div class="absolute inset-0 flex items-center" aria-hidden="true">
                    <div class="w-full border-t border-slate-200/60 dark:border-slate-700/60"></div>
                </div>
                <div class="relative flex justify-center">
                    <span class="bg-[#F8FAFC] dark:bg-[#0F172A] px-4 text-[10px] font-black tracking-[0.2em] text-slate-400 dark:text-slate-500 uppercase">
                        Delivery Operations Guide
                    </span>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4 hidden md:grid">
                <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-amber-500/10 text-amber-500">
                            <SparklesIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">AI Matrix Extractor</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Instantly digitize documents by uploading PDFs or images. The <strong>Gemini AI</strong> will automatically extract table data into structured schedule formats.
                    </p>
                </div>

                <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-indigo-500/10 text-indigo-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
                            </svg>
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Schedule Comparison</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Use the <strong>View Comparison</strong> tool to evaluate gaps between planned Delivery Schedules and real Actual Delivery Orders execution.
                    </p>
                </div>

                <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-400">
                            <CalendarDaysIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Status Tracking</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Keep track of priorities. Items are automatically tagged as <strong>Upcoming</strong> (next 7 days) or <strong>Delayed</strong> if past due.
                    </p>
                </div>
                
                <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-blue-500/10 text-blue-400">
                            <ArrowUpTrayIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Batch Operations</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Manage large quantities of schedule updates simply by using the <strong>Import</strong> and <strong>Export Excel</strong> capabilities simultaneously.
                    </p>
                </div>
            </div>
        </div>

        <!-- AI Matrix Extractor Modal -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div v-if="aiModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm overflow-hidden">
                <div :class="['bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 transition-all duration-500 overflow-hidden flex flex-col', aiStep === 1 ? 'max-w-xl w-full max-h-[90vh]' : 'max-w-[95vw] w-full h-[90vh]']">
                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="p-2 rounded-xl bg-amber-500/10 text-amber-600"><SparklesIcon class="h-6 w-6" /></div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white">AI Matrix Extractor</h3>
                                <p class="text-xs text-slate-500">Ekstrak jadwal dari gambar menggunakan Gemini AI</p>
                            </div>
                        </div>
                        <button @click="closeAiModal" class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 transition-colors"><XMarkIcon class="h-6 w-6" /></button>
                    </div>

                    <!-- Step 1: Upload -->
                    <div v-if="aiStep === 1" class="p-8 flex-1 overflow-y-auto">
                        <div @click="$refs.aiFileInput.click()" class="w-full h-80 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-3xl flex flex-col items-center justify-center gap-4 cursor-pointer hover:border-amber-500/50 hover:bg-amber-500/5 transition-all group relative overflow-hidden">
                            <input ref="aiFileInput" type="file" class="hidden" accept="image/*,.pdf" @change="handleAiFileSelect" />
                            <template v-if="!aiFile">
                                <div class="p-6 rounded-full bg-slate-50 dark:bg-slate-800 group-hover:bg-amber-500/10 transition-colors">
                                    <ArrowUpTrayIcon class="h-12 w-12 text-slate-400 group-hover:text-amber-500 transition-colors" />
                                </div>
                                <div class="text-center">
                                    <p class="text-lg font-semibold text-slate-700 dark:text-slate-200">Upload gambar/PDF jadwal delivery</p>
                                    <p class="text-sm text-slate-500 mt-1">PDF, PNG, JPG atau WebP (Max 5MB)</p>
                                </div>
                            </template>
                            <!-- PDF Preview -->
                            <template v-else-if="aiFileIsPdf">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="p-5 rounded-2xl bg-red-50 dark:bg-red-900/20 text-red-500 dark:text-red-400">
                                        <svg class="h-16 w-16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/>
                                        </svg>
                                    </div>
                                    <div class="text-center">
                                        <p class="text-sm font-bold text-slate-800 dark:text-white">{{ aiFile.name }}</p>
                                        <p class="text-xs text-slate-500 mt-1">{{ (aiFile.size / 1024).toFixed(0) }} KB • PDF Document</p>
                                    </div>
                                </div>
                            </template>
                            <!-- Image Preview -->
                            <img v-else :src="aiFilePreview" class="w-full h-full object-contain p-4" />
                            <div v-if="aiFile" class="absolute inset-x-0 bottom-0 p-4 bg-gradient-to-t from-black/50 to-transparent flex justify-center">
                                <span class="bg-white/90 dark:bg-slate-800/90 py-1.5 px-3 rounded-full text-xs font-bold shadow-sm backdrop-blur-sm">Ganti File</span>
                            </div>
                        </div>
                        <div v-if="aiError" class="mt-6 p-4 rounded-xl bg-red-500/10 border border-red-500/20 flex items-center gap-3 text-red-500">
                            <ExclamationTriangleIcon class="h-5 w-5 shrink-0" />
                            <span class="text-sm font-medium">{{ aiError }}</span>
                        </div>
                        <button @click="extractMatrix" :disabled="!aiFile || isExtracting" class="mt-8 w-full py-4 px-6 rounded-2xl bg-gradient-to-r from-amber-600 to-orange-600 text-white font-bold shadow-lg shadow-amber-500/25 hover:shadow-xl hover:-translate-y-0.5 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-3 text-lg">
                            <template v-if="isExtracting"><ArrowPathIcon class="h-6 w-6 animate-spin" /> AI sedang menganalisis...</template>
                            <template v-else><SparklesIcon class="h-6 w-6" /> Ekstrak Jadwal</template>
                        </button>
                    </div>

                    <!-- Step 2: Verification -->
                    <div v-if="aiStep === 2" class="flex-1 flex overflow-hidden">
                        <!-- Left: Image -->
                        <div class="w-1/3 border-r border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 p-6 overflow-hidden flex flex-col">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-sm font-bold text-slate-500 flex items-center gap-2"><EyeIcon class="h-4 w-4" /> REFERENSI</h4>
                            </div>
                            <div class="flex-1 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden bg-white dark:bg-slate-950 flex items-center justify-center">
                                <template v-if="aiFileIsPdf">
                                    <div class="flex flex-col items-center gap-3 p-6">
                                        <div class="p-4 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-500">
                                            <svg class="h-12 w-12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
                                            </svg>
                                        </div>
                                        <p class="text-xs font-bold text-slate-600 dark:text-slate-400 text-center">{{ aiFile?.name }}</p>
                                    </div>
                                </template>
                                <img v-else :src="aiFilePreview" class="w-full h-full object-contain" />
                            </div>
                            <div class="mt-4 p-4 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800 text-xs text-amber-700 dark:text-amber-400 italic">
                                💡 Bandingkan hasil ekstraksi di tabel dengan gambar asli sebelum menyimpan.
                            </div>
                        </div>
                        <!-- Right: Table -->
                        <div class="flex-1 flex flex-col bg-white dark:bg-slate-900 overflow-hidden">
                            <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between shrink-0">
                                <div class="flex items-center gap-4">
                                    <div class="px-3 py-1.5 rounded-lg bg-emerald-500/10 text-emerald-600 text-xs font-bold ring-1 ring-emerald-500/20">{{ extractedData.items.length }} Items</div>
                                    <span class="text-sm font-bold text-slate-700 dark:text-slate-300">Periode: <span class="text-blue-600">{{ extractedData.month_year }}</span></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button @click="aiStep = 1" class="px-4 py-2 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 font-bold text-sm">Ganti Gambar</button>
                                    <button @click="downloadExcel" :disabled="isSaving || extractedData.items.length === 0" class="px-6 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm shadow-lg shadow-emerald-500/25 disabled:opacity-50 flex items-center gap-2">
                                        <template v-if="isSaving"><ArrowPathIcon class="h-4 w-4 animate-spin" /> Mengunduh...</template>
                                        <template v-else><ArrowDownTrayIcon class="h-4 w-4" /> Download Excel</template>
                                    </button>
                                </div>
                            </div>
                            <div class="flex-1 overflow-auto bg-slate-50 dark:bg-slate-950/20 p-6">
                                <table class="w-full text-left border-separate border-spacing-0">
                                    <thead class="sticky top-0 z-20">
                                        <tr>
                                            <th class="px-4 py-3 border-b border-slate-200 dark:border-slate-800 text-[10px] font-bold text-slate-400 uppercase tracking-wider bg-slate-50 dark:bg-slate-900">Material Code</th>
                                            <th class="px-4 py-3 border-b border-slate-200 dark:border-slate-800 text-[10px] font-bold text-slate-400 uppercase tracking-wider bg-slate-50 dark:bg-slate-900">Customer</th>
                                            <th class="px-4 py-3 border-b border-slate-200 dark:border-slate-800 text-[10px] font-bold text-slate-400 uppercase tracking-wider bg-slate-50 dark:bg-slate-900">Tanggal</th>
                                            <th class="px-4 py-3 border-b border-slate-200 dark:border-slate-800 text-[10px] font-bold text-slate-400 uppercase tracking-wider bg-slate-50 dark:bg-slate-900 text-right">Qty</th>
                                            <th class="px-4 py-3 border-b border-slate-200 dark:border-slate-800 text-[10px] font-bold text-slate-400 uppercase tracking-wider bg-slate-50 dark:bg-slate-900 text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 bg-white dark:bg-slate-900">
                                        <tr v-for="(item, idx) in extractedData.items" :key="idx" class="group hover:bg-blue-50/30 dark:hover:bg-blue-900/10 transition-colors">
                                            <td class="px-4 py-3">
                                                <input v-model="item.product_code" class="bg-transparent border-none p-0 text-sm font-bold text-slate-900 dark:text-white focus:ring-0 w-full" />
                                                <div v-if="item.match_status === 'MATCHED'" class="flex items-center gap-1 mt-1">
                                                    <CheckCircleIcon class="h-3 w-3 text-emerald-500" />
                                                    <span class="text-[10px] text-emerald-500 font-bold">{{ item.product_name }}</span>
                                                </div>
                                                <div v-else class="flex items-center gap-1 mt-1">
                                                    <ExclamationTriangleIcon class="h-3 w-3 text-amber-500" />
                                                    <span class="text-[10px] text-amber-500 font-bold">Belum Terdaftar</span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <input v-model="item.supplier_name" class="bg-transparent border-none p-0 text-sm text-slate-600 dark:text-slate-400 focus:ring-0 w-full" />
                                                <span class="text-[10px] text-slate-400 font-mono">{{ item.customer_name || 'Manual' }}</span>
                                            </td>
                                            <td class="px-4 py-3"><input v-model="item.date" type="date" class="bg-transparent border-none p-0 text-sm font-mono text-slate-600 dark:text-slate-400 focus:ring-0" /></td>
                                            <td class="px-4 py-3 text-right"><input v-model.number="item.qty" type="number" class="bg-transparent border-none p-0 text-sm font-mono text-right font-bold text-slate-900 dark:text-white focus:ring-0 w-24" /></td>
                                            <td class="px-4 py-3 text-center">
                                                <button @click="removeItem(idx)" class="p-1.5 rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-950/30 transition-all opacity-0 group-hover:opacity-100"><TrashIcon class="h-4 w-4" /></button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Import Modal -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="importModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
                <div class="bg-white dark:bg-slate-800 rounded-2xl w-full max-w-md p-6 shadow-2xl border border-slate-200 dark:border-slate-700">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white">
                            Import Delivery Schedule
                        </h3>
                        <button @click="closeImportModal" class="text-slate-500 hover:text-slate-900 dark:text-white transition-colors">
                            <XMarkIcon class="h-6 w-6" />
                        </button>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Sales Name</label>
                        <input 
                            v-model="form.sales_name" 
                            type="text"
                            placeholder="Enter salesperson name"
                            class="w-full rounded-lg border border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:ring-blue-500 focus:border-blue-500"
                        >
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-3">Excel/CSV File</label>
                        <div class="flex flex-col items-center justify-center border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-xl p-8 hover:border-blue-500/50 transition-all relative group bg-slate-50/50 dark:bg-slate-900/50">
                            <input 
                                type="file" 
                                ref="fileInput"
                                @change="(e) => form.file = e.target.files[0]"
                                class="absolute inset-0 opacity-0 cursor-pointer z-20"
                                accept=".xlsx,.xls,.csv"
                            />
                            <div class="flex flex-col items-center transition-transform group-hover:scale-105 duration-300">
                                <div class="p-3 rounded-full bg-blue-50 dark:bg-blue-900/30 mb-3">
                                    <ArrowUpTrayIcon class="h-8 w-8 text-blue-600 dark:text-blue-400" />
                                </div>
                                <p class="text-sm text-slate-600 dark:text-slate-300 font-semibold mb-1">
                                    {{ form.file ? form.file.name : 'Click or drag file to upload' }}
                                </p>
                                <p class="text-xs text-slate-500">Maximum size: 2MB</p>
                            </div>
                            
                            <div class="mt-4 z-30 relative py-1 px-3 rounded-lg bg-blue-50 dark:bg-blue-900/40 border border-blue-100 dark:border-blue-800/50">
                                <a 
                                    :href="route('sales.planning.schedule.template')"
                                    class="flex items-center gap-1.5 text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                    Download Template
                                </a>
                            </div>
                        </div>
                        <div v-if="form.errors.file" class="text-red-500 text-xs mt-2 font-medium">{{ form.errors.file }}</div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button 
                            @click="submitImport"
                            :disabled="!form.file || form.processing"
                            class="flex-1 rounded-xl bg-blue-600 py-3 text-sm font-bold text-white hover:bg-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-lg shadow-blue-500/20"
                        >
                            {{ form.processing ? 'Importing...' : 'Start Import' }}
                        </button>
                        <button 
                            @click="closeImportModal"
                            class="flex-1 rounded-xl bg-slate-100 dark:bg-slate-700 py-3 text-sm font-bold text-slate-600 dark:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-600 transition-all font-mono uppercase tracking-wider"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </AppLayout>
</template>
