<script setup>
import { ref, watch } from 'vue';
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
    InboxArrowDownIcon
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
    router.get(route('qc.mtc.index'));
};

// Delete Document
const deleteDoc = (doc) => {
    Swal.fire({
        title: 'Hapus Dokumen MTC?',
        text: `Anda akan menghapus sertifikat ${doc.certificate_number || doc.file_name}. Tindakan ini tidak bisa dibatalkan!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#1e293b',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
        background: '#0f172a',
        color: '#f8fafc',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('qc.mtc.destroy', doc.id), {
                onSuccess: () => {
                    Swal.fire({
                        title: 'Terhapus!',
                        text: 'Dokumen MTC berhasil dihapus.',
                        icon: 'success',
                        background: '#0f172a',
                        color: '#f8fafc',
                    });
                }
            });
        }
    });
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

const formatDateTime = (dateTimeStr) => {
    if (!dateTimeStr) return '-';
    const date = new Date(dateTimeStr);
    return date.toLocaleDateString('id-ID', { 
        day: '2-digit', 
        month: 'short', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <Head title="Mill Test Certificate Documents" />

    <AppLayout title="Quality Control Dashboard" :render-header="false">
        <div class="p-6 space-y-6 bg-[#030310] min-h-screen text-slate-100 font-sans">
            <!-- Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b border-cyan-500/10 pb-6">
                <div>
                    <h1 class="text-3xl font-extrabold tracking-wider text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-emerald-400 uppercase font-mono">
                        Mill Test Certificate
                    </h1>
                    <p class="text-slate-400 text-sm mt-1 flex items-center gap-2">
                        AI-Powered Document Extraction & Verification Console
                        <span class="text-slate-600">|</span>
                        <button @click="showHelpModal = true" class="text-cyan-400 hover:text-cyan-300 hover:underline transition font-mono font-bold">
                            Cara Kerja AI Extractor
                        </button>
                    </p>
                </div>
                <Link
                    :href="route('qc.mtc.create')"
                    class="flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-cyan-500 to-cyan-600 hover:from-cyan-400 hover:to-cyan-500 text-black font-bold uppercase font-mono rounded-lg transition duration-300 shadow-[0_0_15px_rgba(6,182,212,0.4)] group"
                >
                    <SparklesIcon class="w-5 h-5 group-hover:animate-pulse" />
                    Upload MTC
                </Link>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total -->
                <div class="bg-gradient-to-b from-slate-900/80 to-slate-950/80 border border-slate-800/80 p-5 rounded-xl flex items-center justify-between shadow-[0_4px_20px_rgba(0,0,0,0.3)] backdrop-blur-md">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 font-mono">Total MTC</span>
                        <div class="text-3xl font-black text-slate-100 font-mono">{{ stats.total }}</div>
                    </div>
                    <div class="w-12 h-12 bg-slate-800/50 rounded-lg flex items-center justify-center border border-slate-700/50">
                        <DocumentTextIcon class="w-6 h-6 text-slate-400" />
                    </div>
                </div>

                <!-- Draft -->
                <div class="bg-gradient-to-b from-slate-900/80 to-slate-950/80 border border-slate-800/80 p-5 rounded-xl flex items-center justify-between shadow-[0_4px_20px_rgba(0,0,0,0.3)] backdrop-blur-md">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-amber-500/80 font-mono">Draft / Review</span>
                        <div class="text-3xl font-black text-amber-400 font-mono">{{ stats.draft }}</div>
                    </div>
                    <div class="w-12 h-12 bg-amber-950/30 rounded-lg flex items-center justify-center border border-amber-900/30 shadow-[0_0_10px_rgba(245,158,11,0.1)]">
                        <ArrowPathIcon class="w-6 h-6 text-amber-400 animate-spin-slow" />
                    </div>
                </div>

                <!-- Verified -->
                <div class="bg-gradient-to-b from-slate-900/80 to-slate-950/80 border border-slate-800/80 p-5 rounded-xl flex items-center justify-between shadow-[0_4px_20px_rgba(0,0,0,0.3)] backdrop-blur-md">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-500/80 font-mono">Verified</span>
                        <div class="text-3xl font-black text-emerald-400 font-mono">{{ stats.verified }}</div>
                    </div>
                    <div class="w-12 h-12 bg-emerald-950/30 rounded-lg flex items-center justify-center border border-emerald-900/30 shadow-[0_0_10px_rgba(16,185,129,0.1)]">
                        <CheckCircleIcon class="w-6 h-6 text-emerald-400" />
                    </div>
                </div>

                <!-- Rejected -->
                <div class="bg-gradient-to-b from-slate-900/80 to-slate-950/80 border border-slate-800/80 p-5 rounded-xl flex items-center justify-between shadow-[0_4px_20px_rgba(0,0,0,0.3)] backdrop-blur-md">
                    <div class="space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-red-500/80 font-mono">Rejected</span>
                        <div class="text-3xl font-black text-red-400 font-mono">{{ stats.rejected }}</div>
                    </div>
                    <div class="w-12 h-12 bg-red-950/30 rounded-lg flex items-center justify-center border border-red-900/30 shadow-[0_0_10px_rgba(239,68,68,0.1)]">
                        <XCircleIcon class="w-6 h-6 text-red-400" />
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-gradient-to-r from-slate-950 to-slate-900 border border-slate-800/80 p-4 rounded-xl shadow-md flex flex-wrap gap-4 items-center">
                <!-- Search Input -->
                <div class="flex-1 min-w-[240px] relative">
                    <MagnifyingGlassIcon class="w-5 h-5 text-slate-500 absolute left-3 top-1/2 -translate-y-1/2" />
                    <input
                        type="text"
                        v-model="search"
                        placeholder="Search Certificate No, Supplier, PO..."
                        class="w-full bg-slate-900 border border-slate-800 rounded-lg pl-10 pr-4 py-2 text-sm text-slate-200 focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 placeholder-slate-600 transition"
                    />
                </div>

                <!-- Status Filter -->
                <div class="w-[180px]">
                    <select
                        v-model="status"
                        class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition"
                    >
                        <option value="">All Status</option>
                        <option value="draft">Draft</option>
                        <option value="verified">Verified</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>

                <!-- Date Range -->
                <div class="flex items-center gap-2">
                    <input
                        type="date"
                        v-model="dateFrom"
                        class="bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition font-mono"
                    />
                    <span class="text-slate-600 text-xs">to</span>
                    <input
                        type="date"
                        v-model="dateTo"
                        class="bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition font-mono"
                    />
                </div>

                <!-- Reset Filter Button -->
                <button
                    @click="clearFilters"
                    class="px-4 py-2 border border-slate-800 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-slate-800/30 text-sm flex items-center gap-2 transition"
                >
                    <ArrowPathIcon class="w-4 h-4" />
                    Reset
                </button>
            </div>

            <!-- Documents Table -->
            <div class="bg-gradient-to-b from-slate-950 to-slate-950/90 border border-slate-800/80 rounded-xl overflow-hidden shadow-2xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-900/60 border-b border-slate-800 text-slate-400 text-xs font-bold uppercase tracking-wider font-mono">
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
                        <tbody class="divide-y divide-slate-800 text-sm text-slate-300">
                            <tr v-if="documents.data.length === 0">
                                <td colspan="8" class="text-center py-10 text-slate-500">
                                    No documents found.
                                </td>
                            </tr>
                            <tr v-for="doc in documents.data" :key="doc.id" class="hover:bg-slate-900/30 transition duration-150">
                                <!-- Cert info -->
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-200 font-mono">{{ doc.certificate_number || 'UNREADABLE / DRAFT' }}</div>
                                    <div class="text-xs text-slate-500 font-mono mt-0.5" v-if="doc.date_of_issue">
                                        {{ formatDate(doc.date_of_issue) }}
                                    </div>
                                </td>

                                <!-- Supplier name -->
                                <td class="px-6 py-4 max-w-[200px] truncate">
                                    <div class="font-bold text-slate-200">{{ doc.supplier ? doc.supplier.name : doc.supplier_name || 'Manual Upload' }}</div>
                                    <div class="text-xs text-slate-500" v-if="doc.supplier_name && doc.supplier">
                                        extracted: {{ doc.supplier_name }}
                                    </div>
                                </td>

                                <!-- Spec & Type -->
                                <td class="px-6 py-4">
                                    <span class="inline-block bg-slate-900 border border-slate-800 px-2 py-1 rounded text-xs text-cyan-400 font-mono">
                                        {{ doc.spec_and_type || '-' }}
                                    </span>
                                </td>

                                <!-- Coil items count -->
                                <td class="px-6 py-4 text-center font-bold font-mono text-cyan-500">
                                    {{ doc.items_count }}
                                </td>

                                <!-- PO Number -->
                                <td class="px-6 py-4 font-mono text-xs text-slate-400">
                                    {{ doc.po_no || '-' }}
                                </td>

                                <!-- Status Badge -->
                                <td class="px-6 py-4">
                                    <span v-if="doc.status === 'draft'" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-500/10 border border-amber-500/30 text-amber-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                                        Draft / Review
                                    </span>
                                    <span v-else-if="doc.status === 'verified'" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 border border-emerald-500/30 text-emerald-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                        Verified
                                    </span>
                                    <span v-else-if="doc.status === 'rejected'" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-red-500/10 border border-red-500/30 text-red-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>
                                        Rejected
                                    </span>
                                    <span v-else class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-500/10 border border-slate-500/30 text-slate-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        {{ doc.status }}
                                    </span>
                                </td>

                                <!-- Uploaded at & by -->
                                <td class="px-6 py-4">
                                    <div class="text-xs text-slate-400 font-mono">{{ formatDateTime(doc.created_at) }}</div>
                                    <div class="text-[10px] text-slate-500 mt-0.5">by {{ doc.creator ? doc.creator.name : 'System' }}</div>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 text-right space-x-1.5">
                                    <Link
                                        :href="route('qc.mtc.show', doc.id)"
                                        class="inline-flex items-center justify-center p-2 rounded-lg bg-cyan-950/40 border border-cyan-800/40 hover:bg-cyan-900/60 text-cyan-400 transition"
                                        title="View & Verify"
                                    >
                                        <InboxArrowDownIcon class="w-4 h-4" />
                                    </Link>
                                    <button
                                        @click="deleteDoc(doc)"
                                        class="inline-flex items-center justify-center p-2 rounded-lg bg-red-950/40 border border-red-800/40 hover:bg-red-900/60 text-red-400 transition"
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
                <div class="bg-slate-950/50 px-6 py-4 border-t border-slate-800 flex justify-between items-center text-xs text-slate-500 font-mono">
                    <div>
                        Showing {{ documents.from || 0 }} to {{ documents.to || 0 }} of {{ documents.total }} certificates
                    </div>
                    <div class="flex items-center gap-1.5">
                        <Link
                            v-if="documents.prev_page_url"
                            :href="documents.prev_page_url"
                            class="px-3 py-1.5 border border-slate-800 hover:bg-slate-900 hover:text-slate-300 rounded transition"
                        >
                            Previous
                        </Link>
                        <span v-else class="px-3 py-1.5 border border-slate-900 text-slate-800 cursor-not-allowed rounded">
                            Previous
                        </span>

                        <span class="px-3 py-1.5 bg-slate-900 text-cyan-400 border border-cyan-500/20 rounded">
                            {{ documents.current_page }}
                        </span>

                        <Link
                            v-if="documents.next_page_url"
                            :href="documents.next_page_url"
                            class="px-3 py-1.5 border border-slate-800 hover:bg-slate-900 hover:text-slate-300 rounded transition"
                        >
                            Next
                        </Link>
                        <span v-else class="px-3 py-1.5 border border-slate-900 text-slate-800 cursor-not-allowed rounded">
                            Next
                        </span>
                    </div>
                </div>
            </div>
            <!-- Help Modal: Cara Kerja AI Extractor -->
            <div v-if="showHelpModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
                <div class="w-full max-w-2xl bg-slate-950 border border-slate-800 rounded-2xl overflow-hidden shadow-2xl relative">
                    <!-- Glowing border effect -->
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-cyan-500 to-emerald-400"></div>

                    <div class="px-6 py-5 border-b border-slate-900 bg-slate-900/30 flex justify-between items-center">
                        <h3 class="text-base font-black tracking-wider text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-emerald-400 uppercase font-mono">
                            Cara Kerja MTC AI Extractor
                        </h3>
                        <button @click="showHelpModal = false" class="text-slate-400 hover:text-slate-200 font-bold font-mono text-xl">
                            &times;
                        </button>
                    </div>

                    <div class="p-6 space-y-6 max-h-[70vh] overflow-y-auto text-sm text-slate-300 leading-relaxed font-sans scrollbar-thin">
                        <div class="space-y-4">
                            <!-- Step 1 -->
                            <div class="flex gap-4">
                                <div class="w-8 h-8 rounded-lg bg-cyan-950/50 border border-cyan-800/50 flex items-center justify-center font-mono font-bold text-cyan-400 shrink-0">
                                    1
                                </div>
                                <div class="space-y-1">
                                    <h4 class="font-bold text-slate-200">Upload Berkas Secara Privat</h4>
                                    <p class="text-xs text-slate-400">MTC berupa PDF atau Gambar hasil scan (JPG/PNG) diunggah dan disimpan di folder privat server (`storage/app/private/mtc_documents`) demi keamanan kerahasiaan data.</p>
                                </div>
                            </div>

                            <!-- Step 2 -->
                            <div class="flex gap-4">
                                <div class="w-8 h-8 rounded-lg bg-cyan-950/50 border border-cyan-800/50 flex items-center justify-center font-mono font-bold text-cyan-400 shrink-0">
                                    2
                                </div>
                                <div class="space-y-1">
                                    <h4 class="font-bold text-slate-200">Konversi & Pengiriman ke Gemini AI</h4>
                                    <p class="text-xs text-slate-400">Sistem mengonversi file menjadi data Base64 dan mengirimkannya ke model AI Vision (`gemini-2.5-flash`) menggunakan API key dari menu <strong>AI Configuration</strong> Anda.</p>
                                </div>
                            </div>

                            <!-- Step 3 -->
                            <div class="flex gap-4">
                                <div class="w-8 h-8 rounded-lg bg-cyan-950/50 border border-cyan-800/50 flex items-center justify-center font-mono font-bold text-cyan-400 shrink-0">
                                    3
                                </div>
                                <div class="space-y-1">
                                    <h4 class="font-bold text-slate-200">Ekstraksi Teks Berpola JSON Schema</h4>
                                    <p class="text-xs text-slate-400">Model AI mengekstrak data dari tabel visual MTC ke struktur JSON yang kaku: mencakup data nomor sertifikat, mill supplier, serta detail nomor coil, nomor heat, ukuran, berat, dan komposisi kimia logam.</p>
                                </div>
                            </div>

                            <!-- Step 4 -->
                            <div class="flex gap-4">
                                <div class="w-8 h-8 rounded-lg bg-cyan-950/50 border border-cyan-800/50 flex items-center justify-center font-mono font-bold text-cyan-400 shrink-0">
                                    4
                                </div>
                                <div class="space-y-1">
                                    <h4 class="font-bold text-slate-200">Pencocokan Otomatis (Auto-Mapping)</h4>
                                    <p class="text-xs text-slate-400">Backend mem-parsing tebal & lebar dari string ukuran (misal: 6.02x1045) dan mencocokkannya ke tabel master <strong>products</strong> untuk pemetaan kode produk database secara otomatis.</p>
                                </div>
                            </div>

                            <!-- Step 5 -->
                            <div class="flex gap-4">
                                <div class="w-8 h-8 rounded-lg bg-emerald-950/50 border border-emerald-800/50 flex items-center justify-center font-mono font-bold text-emerald-400 shrink-0">
                                    5
                                </div>
                                <div class="space-y-1">
                                    <h4 class="font-bold text-emerald-400">Verifikasi & PUSH ke Inventaris Aktif</h4>
                                    <p class="text-xs text-slate-400">Data masuk ke Staging Area (Draft). Operator meninjau detailnya, memetakan gudang tujuan, lalu menekan <strong>Verify & PUSH</strong> untuk membuat coil lot baru di tabel stok aktif (`inventory_lots`).</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-slate-900 bg-slate-900/30 flex justify-end">
                        <button @click="showHelpModal = false" class="px-6 py-2 bg-slate-900 hover:bg-slate-800 border border-slate-800 text-xs font-bold uppercase font-mono rounded-lg transition text-slate-300">
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
