<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    SparklesIcon, 
    MagnifyingGlassIcon,
    ArrowPathIcon,
    DocumentArrowDownIcon,
    TrashIcon,
    CheckCircleIcon,
    XCircleIcon,
    DocumentTextIcon,
    PresentationChartBarIcon,
    InboxArrowDownIcon,
    SunIcon,
    MoonIcon
} from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';
import Swal from 'sweetalert2';

const props = defineProps({
    documents: Object,
    stats: Object,
    filters: Object,
});

const showHelpModal = ref(false);

// Search & Filter state
const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');

// Trigger filtering
const applyFilters = () => {
    router.get(route('qc.mtc.index'), {
        search: search.value,
        status: status.value,
        date_from: dateFrom.value,
        date_to: dateTo.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

// Debounce search
watch(search, debounce(() => {
    applyFilters();
}, 400));

watch([status, dateFrom, dateTo], () => {
    applyFilters();
});

const clearFilters = () => {
    search.value = '';
    status.value = '';
    dateFrom.value = '';
    dateTo.value = '';
};

const deleteDoc = (doc) => {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: `Dokumen MTC ${doc.certificate_number || ''} akan dihapus beserta seluruh data coil hasil ekstraksinya!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
        background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
        color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#0f172a'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('qc.mtc.destroy', doc.id), {
                onSuccess: () => {
                    Swal.fire({
                        title: 'Terhapus!',
                        text: 'Dokumen MTC berhasil dihapus.',
                        icon: 'success',
                        background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
                        color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#0f172a'
                    });
                }
            });
        }
    });
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const formatDateTime = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// --- Theme Reactive Sync ---
const isLightMode = ref(false);
const toggleTheme = () => {
    isLightMode.value = !isLightMode.value;
    if (isLightMode.value) {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    } else {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    }
};

let observer;
onMounted(() => {
    isLightMode.value = !document.documentElement.classList.contains('dark');
    observer = new MutationObserver(() => {
        isLightMode.value = !document.documentElement.classList.contains('dark');
    });
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
});

onUnmounted(() => {
    if (observer) observer.disconnect();
});
</script>

<template>
    <Head title="Mill Test Certificate Documents" />

    <AppLayout title="Quality Control Dashboard" :render-header="false">
        <div class="p-6 space-y-6 bg-slate-50 dark:bg-[#030310] min-h-screen text-slate-800 dark:text-slate-100 font-sans transition-colors duration-300 relative">
            
            <!-- Matrix Style Cyber Background -->
            <div class="fixed inset-0 z-0 pointer-events-none">
                <div class="absolute inset-0 bg-gradient-to-br from-cyan-500/5 to-slate-100 dark:from-cyan-955/20 dark:to-[#030310]"></div>
            </div>

            <div class="relative z-10 space-y-6 max-w-7xl mx-auto">
                <!-- Header -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b border-slate-200 dark:border-cyan-500/10 pb-6">
                    <div>
                        <h1 class="text-3xl font-extrabold tracking-wider text-transparent bg-clip-text bg-gradient-to-r from-cyan-605 via-slate-800 to-emerald-650 dark:from-cyan-400 dark:to-emerald-400 uppercase font-mono">
                            Mill Test Certificate
                        </h1>
                        <p class="text-slate-500 dark:text-slate-400 text-sm mt-1 flex items-center gap-2">
                            AI-Powered Document Extraction & Verification Console
                            <span class="text-slate-350 dark:text-slate-600">|</span>
                            <button @click="showHelpModal = true" class="text-cyan-600 dark:text-cyan-400 hover:text-cyan-505 dark:hover:text-cyan-300 hover:underline transition font-mono font-bold bg-transparent border-0 cursor-pointer">
                                Cara Kerja AI Extractor
                            </button>
                        </p>
                    </div>
                    
                    <div class="flex items-center gap-4">
                        

                        <Link
                            :href="route('qc.mtc.create')"
                            class="flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-cyan-600 to-cyan-555 hover:from-cyan-500 hover:to-cyan-400 dark:from-cyan-500 dark:to-cyan-600 dark:hover:from-cyan-400 dark:hover:to-cyan-500 text-white dark:text-black font-bold uppercase font-mono rounded-lg transition duration-300 shadow-sm dark:shadow-[0_0_15px_rgba(6,182,212,0.4)] group border-0"
                        >
                            <SparklesIcon class="w-5 h-5 group-hover:animate-pulse" />
                            Upload MTC
                        </Link>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Total -->
                    <div class="bg-white/70 dark:bg-gradient-to-b dark:from-slate-900/80 dark:to-slate-955/80 border border-slate-200 dark:border-slate-800/80 p-5 rounded-xl flex items-center justify-between shadow-sm dark:shadow-[0_4px_20px_rgba(0,0,0,0.3)] backdrop-blur-md">
                        <div class="space-y-1">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-500 font-mono">Total MTC</span>
                            <div class="text-3xl font-black text-slate-900 dark:text-slate-100 font-mono">{{ stats.total }}</div>
                        </div>
                        <div class="w-12 h-12 bg-slate-100 dark:bg-slate-800/50 rounded-lg flex items-center justify-center border border-slate-200 dark:border-slate-700/50">
                            <DocumentTextIcon class="w-6 h-6 text-slate-500 dark:text-slate-400" />
                        </div>
                    </div>

                    <!-- Draft -->
                    <div class="bg-white/70 dark:bg-gradient-to-b dark:from-slate-900/80 dark:to-slate-955/80 border border-slate-200 dark:border-slate-800/80 p-5 rounded-xl flex items-center justify-between shadow-sm dark:shadow-[0_4px_20px_rgba(0,0,0,0.3)] backdrop-blur-md border-l-4 border-l-amber-400">
                        <div class="space-y-1">
                            <span class="text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-500/80 font-mono">Draft / Review</span>
                            <div class="text-3xl font-black text-amber-600 dark:text-amber-400 font-mono">{{ stats.draft }}</div>
                        </div>
                        <div class="w-12 h-12 bg-amber-50 dark:bg-amber-955/30 rounded-lg flex items-center justify-center border border-amber-200 dark:border-amber-900/30 shadow-none dark:shadow-[0_0_10px_rgba(245,158,11,0.1)]">
                            <ArrowPathIcon class="w-6 h-6 text-amber-600 dark:text-amber-400 animate-spin-slow" />
                        </div>
                    </div>

                    <!-- Verified -->
                    <div class="bg-white/70 dark:bg-gradient-to-b dark:from-slate-900/80 dark:to-slate-955/80 border border-slate-200 dark:border-slate-800/80 p-5 rounded-xl flex items-center justify-between shadow-sm dark:shadow-[0_4px_20px_rgba(0,0,0,0.3)] backdrop-blur-md border-l-4 border-l-emerald-500">
                        <div class="space-y-1">
                            <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-500/80 font-mono">Verified</span>
                            <div class="text-3xl font-black text-emerald-600 dark:text-emerald-400 font-mono">{{ stats.verified }}</div>
                        </div>
                        <div class="w-12 h-12 bg-emerald-50 dark:bg-emerald-955/30 rounded-lg flex items-center justify-center border border-emerald-200 dark:border-emerald-900/30 shadow-none dark:shadow-[0_0_10px_rgba(16,185,129,0.1)]">
                            <CheckCircleIcon class="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
                        </div>
                    </div>

                    <!-- Rejected -->
                    <div class="bg-white/70 dark:bg-gradient-to-b dark:from-slate-900/80 dark:to-slate-955/80 border border-slate-200 dark:border-slate-800/80 p-5 rounded-xl flex items-center justify-between shadow-sm dark:shadow-[0_4px_20px_rgba(0,0,0,0.3)] backdrop-blur-md border-l-4 border-l-red-500">
                        <div class="space-y-1">
                            <span class="text-xs font-bold uppercase tracking-wider text-red-700 dark:text-red-500/80 font-mono">Rejected</span>
                            <div class="text-3xl font-black text-red-650 dark:text-red-400 font-mono">{{ stats.rejected }}</div>
                        </div>
                        <div class="w-12 h-12 bg-red-50 dark:bg-red-955/30 rounded-lg flex items-center justify-center border border-red-200 dark:border-red-900/30 shadow-none dark:shadow-[0_0_10px_rgba(239,68,68,0.1)]">
                            <XCircleIcon class="w-6 h-6 text-red-600 dark:text-red-400" />
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white/75 dark:bg-gradient-to-r dark:from-slate-950 dark:to-slate-900 border border-slate-200 dark:border-slate-800/80 p-4 rounded-xl shadow-sm dark:shadow-md flex flex-wrap gap-4 items-center">
                    <!-- Search Input -->
                    <div class="flex-1 min-w-[240px] relative">
                        <MagnifyingGlassIcon class="w-5 h-5 text-slate-500 absolute left-3 top-1/2 -translate-y-1/2" />
                        <input
                            type="text"
                            v-model="search"
                            placeholder="Search Certificate No, Supplier, PO..."
                            class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg pl-10 pr-4 py-2 text-sm text-slate-800 dark:text-slate-200 focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 placeholder-slate-400 dark:placeholder-slate-600 transition"
                        />
                    </div>

                    <!-- Status Filter -->
                    <div class="w-[180px]">
                        <select
                            v-model="status"
                            class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-200 focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition"
                        >
                            <option value="" class="bg-white dark:bg-slate-900">All Status</option>
                            <option value="draft" class="bg-white dark:bg-slate-900">Draft</option>
                            <option value="verified" class="bg-white dark:bg-slate-900">Verified</option>
                            <option value="rejected" class="bg-white dark:bg-slate-900">Rejected</option>
                        </select>
                    </div>

                    <!-- Date Range -->
                    <div class="flex items-center gap-2">
                        <input
                            type="date"
                            v-model="dateFrom"
                            class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-850 dark:text-slate-200 focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition font-mono"
                        />
                        <span class="text-slate-400 dark:text-slate-650 text-xs">to</span>
                        <input
                            type="date"
                            v-model="dateTo"
                            class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-850 dark:text-slate-200 focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition font-mono"
                        />
                    </div>

                    <!-- Reset Filter Button -->
                    <button
                        @click="clearFilters"
                        class="px-4 py-2 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/30 text-sm flex items-center gap-2 transition cursor-pointer bg-white dark:bg-transparent"
                    >
                        <ArrowPathIcon class="w-4 h-4" />
                        Reset
                    </button>
                </div>

                <!-- Documents Table -->
                <div class="bg-white/75 dark:bg-gradient-to-b dark:from-slate-950 dark:to-slate-955/90 border border-slate-200 dark:border-slate-800/80 rounded-xl overflow-hidden shadow-sm dark:shadow-2xl">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-100 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 text-xs font-bold uppercase tracking-wider font-mono">
                                    <th class="px-6 py-4">Certificate</th>
                                    <th class="px-6 py-4">Supplier</th>
                                    <th class="px-6 py-4">Spec & Type</th>
                                    <th class="px-6 py-4 text-center">Items</th>
                                    <th class="px-6 py-4">PO No.</th>
                                    <th class="px-6 py-4">Status</th>
                                    <th class="px-6 py-4">Uploaded</th>
                                    <th class="px-6 py-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-sm text-slate-700 dark:text-slate-300">
                                <tr v-if="documents.data.length === 0">
                                    <td colspan="8" class="text-center py-10 text-slate-500">
                                        No documents found.
                                    </td>
                                </tr>
                                <tr v-for="doc in documents.data" :key="doc.id" class="hover:bg-slate-50 dark:hover:bg-slate-900/30 transition duration-150">
                                    <!-- Cert info -->
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-slate-900 dark:text-slate-200 font-mono">{{ doc.certificate_number || 'UNREADABLE / DRAFT' }}</div>
                                        <div class="text-xs text-slate-400 dark:text-slate-500 font-mono mt-0.5" v-if="doc.date_of_issue">
                                            {{ formatDate(doc.date_of_issue) }}
                                        </div>
                                    </td>

                                    <!-- Supplier name -->
                                    <td class="px-6 py-4 max-w-[200px] truncate">
                                        <div class="font-bold text-slate-850 dark:text-slate-200">{{ doc.supplier ? doc.supplier.name : doc.supplier_name || 'Manual Upload' }}</div>
                                        <div class="text-xs text-slate-450 dark:text-slate-500" v-if="doc.supplier_name && doc.supplier">
                                            extracted: {{ doc.supplier_name }}
                                        </div>
                                    </td>

                                    <!-- Spec & Type -->
                                    <td class="px-6 py-4">
                                        <span class="inline-block bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 px-2 py-1 rounded text-xs text-cyan-705 dark:text-cyan-400 font-mono font-bold">
                                            {{ doc.spec_and_type || '-' }}
                                        </span>
                                    </td>

                                    <!-- Coil items count -->
                                    <td class="px-6 py-4 text-center font-bold font-mono text-cyan-600 dark:text-cyan-500">
                                        {{ doc.items_count }}
                                    </td>

                                    <!-- PO Number -->
                                    <td class="px-6 py-4 font-mono text-xs text-slate-500 dark:text-slate-400">
                                        {{ doc.po_no || '-' }}
                                    </td>

                                    <!-- Status Badge -->
                                    <td class="px-6 py-4">
                                        <span v-if="doc.status === 'draft'" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 text-amber-700 dark:text-amber-400">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 dark:bg-amber-400 animate-pulse"></span>
                                            Draft / Review
                                        </span>
                                        <span v-else-if="doc.status === 'verified'" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/30 text-emerald-700 dark:text-emerald-400">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 dark:bg-emerald-400"></span>
                                            Verified
                                        </span>
                                        <span v-else-if="doc.status === 'rejected'" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 text-red-700 dark:text-red-400">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-500 dark:bg-red-400"></span>
                                            Rejected
                                        </span>
                                        <span v-else class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 dark:bg-slate-500/10 border border-slate-200 dark:border-slate-500/30 text-slate-650 dark:text-slate-400">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-500 dark:bg-slate-400"></span>
                                            {{ doc.status }}
                                        </span>
                                    </td>

                                    <!-- Uploaded at & by -->
                                    <td class="px-6 py-4">
                                        <div class="text-xs text-slate-500 dark:text-slate-400 font-mono">{{ formatDateTime(doc.created_at) }}</div>
                                        <div class="text-[10px] text-slate-450 dark:text-slate-500 mt-0.5">by {{ doc.creator ? doc.creator.name : 'System' }}</div>
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-6 py-4 text-right space-x-1.5">
                                        <Link
                                            :href="route('qc.mtc.show', doc.id)"
                                            class="inline-flex items-center justify-center p-2 rounded-lg bg-slate-100 hover:bg-slate-200 border border-slate-200 dark:bg-cyan-955/40 dark:border-cyan-800/40 dark:hover:bg-cyan-900/60 text-cyan-600 dark:text-cyan-400 transition"
                                            title="View & Verify"
                                        >
                                            <InboxArrowDownIcon class="w-4 h-4" />
                                        </Link>
                                        <button
                                            @click="deleteDoc(doc)"
                                            class="inline-flex items-center justify-center p-2 rounded-lg bg-red-50 hover:bg-red-100 border border-red-200 dark:bg-red-955/40 dark:border-red-800/40 dark:hover:bg-red-900/60 text-red-600 dark:text-red-400 transition cursor-pointer"
                                            title="Delete"
                                        >
                                            <TrashIcon class="w-4 h-4" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="bg-slate-100 dark:bg-slate-950/50 px-6 py-4 border-t border-slate-200 dark:border-slate-800 flex justify-between items-center text-xs text-slate-500 dark:text-slate-500 font-mono">
                        <div>
                            Showing {{ documents.from || 0 }} to {{ documents.to || 0 }} of {{ documents.total }} certificates
                        </div>
                        <div class="flex items-center gap-1.5">
                            <Link
                                v-if="documents.prev_page_url"
                                :href="documents.prev_page_url"
                                class="px-3 py-1.5 border border-slate-200 dark:border-slate-800 hover:bg-slate-200 dark:hover:bg-slate-900 text-slate-600 dark:text-slate-300 rounded transition"
                            >
                                Previous
                            </Link>
                            <span v-else class="px-3 py-1.5 border border-slate-200 dark:border-slate-900 text-slate-400 dark:text-slate-800 cursor-not-allowed rounded">
                                Previous
                            </span>

                            <span class="px-3 py-1.5 bg-slate-200 dark:bg-slate-900 text-cyan-705 dark:text-cyan-400 border border-slate-300 dark:border-cyan-500/20 rounded font-bold">
                                {{ documents.current_page }}
                            </span>

                            <Link
                                v-if="documents.next_page_url"
                                :href="documents.next_page_url"
                                class="px-3 py-1.5 border border-slate-200 dark:border-slate-800 hover:bg-slate-200 dark:hover:bg-slate-900 text-slate-600 dark:text-slate-300 rounded transition"
                            >
                                Next
                            </Link>
                            <span v-else class="px-3 py-1.5 border border-slate-200 dark:border-slate-900 text-slate-400 dark:text-slate-800 cursor-not-allowed rounded">
                                Next
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Help Modal: Cara Kerja AI Extractor -->
            <div v-if="showHelpModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
                <div class="w-full max-w-2xl bg-white dark:bg-slate-950 border border-slate-250 dark:border-slate-800 rounded-2xl overflow-hidden shadow-2xl relative text-slate-800 dark:text-slate-300">
                    <!-- Glowing border effect -->
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-cyan-500 to-emerald-400"></div>

                    <div class="px-6 py-5 border-b border-slate-150 dark:border-slate-900 bg-slate-50 dark:bg-slate-900/30 flex justify-between items-center">
                        <h3 class="text-base font-black tracking-wider text-transparent bg-clip-text bg-gradient-to-r from-cyan-605 to-emerald-600 dark:from-cyan-400 dark:to-emerald-400 uppercase font-mono">
                            Cara Kerja MTC AI Extractor
                        </h3>
                        <button @click="showHelpModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 font-bold font-mono text-xl bg-transparent border-0 cursor-pointer">
                            &times;
                        </button>
                    </div>

                    <div class="p-6 space-y-6 max-h-[70vh] overflow-y-auto text-sm text-slate-700 dark:text-slate-300 leading-relaxed font-sans scrollbar-thin">
                        <div class="space-y-4">
                            <!-- Step 1 -->
                            <div class="flex gap-4">
                                <div class="w-8 h-8 rounded-lg bg-cyan-50 dark:bg-cyan-955/50 border border-cyan-200 dark:border-cyan-800/50 flex items-center justify-center font-mono font-bold text-cyan-600 dark:text-cyan-400 shrink-0 shadow-sm dark:shadow-none">
                                    1
                                </div>
                                <div class="space-y-1">
                                    <h4 class="font-bold text-slate-800 dark:text-slate-200">Upload Berkas Secara Privat</h4>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">MTC berupa PDF atau Gambar hasil scan (JPG/PNG) diunggah dan disimpan di folder privat server (`storage/app/private/mtc_documents`) demi keamanan kerahasiaan data.</p>
                                </div>
                            </div>

                            <!-- Step 2 -->
                            <div class="flex gap-4">
                                <div class="w-8 h-8 rounded-lg bg-cyan-50 dark:bg-cyan-955/50 border border-cyan-200 dark:border-cyan-800/50 flex items-center justify-center font-mono font-bold text-cyan-600 dark:text-cyan-400 shrink-0 shadow-sm dark:shadow-none">
                                    2
                                </div>
                                <div class="space-y-1">
                                    <h4 class="font-bold text-slate-800 dark:text-slate-200">Konversi & Pengiriman ke Gemini AI</h4>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Sistem mengonversi file menjadi data Base64 dan mengirimkannya ke model AI Vision (`gemini-2.5-flash`) menggunakan API key dari menu <strong>AI Configuration</strong> Anda.</p>
                                </div>
                            </div>

                            <!-- Step 3 -->
                            <div class="flex gap-4">
                                <div class="w-8 h-8 rounded-lg bg-cyan-50 dark:bg-cyan-955/50 border border-cyan-200 dark:border-cyan-800/50 flex items-center justify-center font-mono font-bold text-cyan-600 dark:text-cyan-400 shrink-0 shadow-sm dark:shadow-none">
                                    3
                                </div>
                                <div class="space-y-1">
                                    <h4 class="font-bold text-slate-800 dark:text-slate-200">Ekstraksi Teks Berpola JSON Schema</h4>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Model AI mengekstrak data dari tabel visual MTC ke struktur JSON yang kaku: mencakup data nomor sertifikat, mill supplier, serta detail nomor coil, nomor heat, ukuran, berat, dan komposisi kimia logam.</p>
                                </div>
                            </div>

                            <!-- Step 4 -->
                            <div class="flex gap-4">
                                <div class="w-8 h-8 rounded-lg bg-cyan-50 dark:bg-cyan-955/50 border border-cyan-200 dark:border-cyan-800/50 flex items-center justify-center font-mono font-bold text-cyan-600 dark:text-cyan-400 shrink-0 shadow-sm dark:shadow-none">
                                    4
                                </div>
                                <div class="space-y-1">
                                    <h4 class="font-bold text-slate-800 dark:text-slate-200">Pencocokan Otomatis (Auto-Mapping)</h4>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Backend mem-parsing tebal & lebar dari string ukuran (misal: 6.02x1045) dan mencocokkannya ke tabel master <strong>products</strong> untuk pemetaan kode produk database secara otomatis.</p>
                                </div>
                            </div>

                            <!-- Step 5 -->
                            <div class="flex gap-4">
                                <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-955/50 border border-emerald-200 dark:border-emerald-800/50 flex items-center justify-center font-mono font-bold text-emerald-600 dark:text-emerald-400 shrink-0 shadow-sm dark:shadow-none">
                                    5
                                </div>
                                <div class="space-y-1">
                                    <h4 class="font-bold text-emerald-700 dark:text-emerald-450">Verifikasi & PUSH ke Inventaris Aktif</h4>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Data masuk ke Staging Area (Draft). Operator meninjau detailnya, memetakan gudang tujuan, lalu menekan <strong>Verify & PUSH</strong> untuk membuat coil lot baru di tabel stok aktif (`inventory_lots`).</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-slate-150 dark:border-slate-900 bg-slate-50 dark:bg-slate-900/30 flex justify-end">
                        <button @click="showHelpModal = false" class="px-6 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-900 dark:hover:bg-slate-800 border border-slate-250 dark:border-slate-800 text-xs font-bold uppercase font-mono rounded-lg transition text-slate-700 dark:text-slate-300 cursor-pointer">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.animate-spin-slow {
    animation: spin 8s linear infinite;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}
</style>
