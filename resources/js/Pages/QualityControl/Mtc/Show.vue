<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    ArrowLeftIcon,
    ArrowPathIcon,
    CheckBadgeIcon,
    XCircleIcon,
    DocumentArrowDownIcon,
    BookmarkSquareIcon,
    BeakerIcon,
    DocumentTextIcon,
    WrenchIcon
} from '@heroicons/vue/24/outline';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
    document: Object,
    suppliers: Array,
    products: Array,
    warehouses: Array,
    locations: Array,
});

// Staging Form states
const supplierId = ref(props.document.supplier_id || '');
const supplierName = ref(props.document.supplier_name || '');
const certificateNumber = ref(props.document.certificate_number || '');
const dateOfIssue = ref(props.document.date_of_issue ? props.document.date_of_issue.substring(0, 10) : '');
const orderNo = ref(props.document.order_no || '');
const poNo = ref(props.document.po_no || '');
const commodity = ref(props.document.commodity || '');
const specAndType = ref(props.document.spec_and_type || '');
const customer = ref(props.document.customer || '');
const notes = ref(props.document.notes || '');

// Verification options
const warehouseId = ref('');
const locationId = ref('');

// Filter locations by warehouse
const filteredLocations = computed(() => {
    if (!warehouseId.value) return [];
    return props.locations.filter(loc => loc.warehouse_id === Number(warehouseId.value));
});

// Staging Items states
const items = ref(props.document.items.map(item => ({
    id: item.id,
    product_id: item.product_id || '',
    product_no: item.product_no || '',
    heat_no: item.heat_no || '',
    size: item.size || '',
    quantity: item.quantity || 1,
    weight_kg: item.weight_kg ? Number(item.weight_kg) : 0,
    yp_mpa: item.yp_mpa || '',
    ts_mpa: item.ts_mpa || '',
    el_percent: item.el_percent || '',
    chemical_ladle: item.chemical_ladle || {},
    chemical_product: item.chemical_product || {},
})));

// Active Item Details Modal
const selectedItemIndex = ref(null);
const showDetailsModal = ref(false);

const openItemDetails = (index) => {
    selectedItemIndex.value = index;
    showDetailsModal.value = true;
};

// Auto mapping trigger: try to fuzzy match supplier
const autoMapSupplier = () => {
    if (!supplierName.value) return;
    const name = supplierName.value.toLowerCase();
    const match = props.suppliers.find(s => {
        const sName = s.name.toLowerCase();
        return sName.includes(name) || name.includes(sName);
    });
    if (match) {
        supplierId.value = match.id;
        Swal.fire({
            title: 'Supplier Ditemukan',
            text: `Berhasil memetakan "${supplierName.value}" ke supplier "${match.name}".`,
            icon: 'success',
            background: '#0f172a',
            color: '#f8fafc',
            timer: 1500,
            showConfirmButton: false,
        });
    } else {
        Swal.fire({
            title: 'Tidak Ditemukan',
            text: `Tidak ada supplier di database yang cocok dengan "${supplierName.value}". Harap pilih secara manual.`,
            icon: 'info',
            background: '#0f172a',
            color: '#f8fafc',
        });
    }
};

// Save draft
const isSaving = ref(false);
const saveDraft = () => {
    isSaving.value = true;
    axios.put(route('qc.mtc.update', props.document.id), {
        supplier_id: supplierId.value,
        supplier_name: supplierName.value,
        certificate_number: certificateNumber.value,
        date_of_issue: dateOfIssue.value,
        order_no: orderNo.value,
        po_no: poNo.value,
        commodity: commodity.value,
        spec_and_type: specAndType.value,
        customer: customer.value,
        notes: notes.value,
        items: items.value.reduce((acc, curr) => {
            acc[curr.id] = curr;
            return acc;
        }, {}),
    }).then(() => {
        isSaving.value = false;
        Swal.fire({
            title: 'Draft Disimpan!',
            text: 'Perubahan berhasil disimpan sebagai draft.',
            icon: 'success',
            background: '#0f172a',
            color: '#f8fafc',
            timer: 1500,
            showConfirmButton: false,
        });
    }).catch((error) => {
        isSaving.value = false;
        Swal.fire({
            title: 'Gagal Menyimpan',
            text: error.response?.data?.message || 'Terjadi kesalahan.',
            icon: 'error',
            background: '#0f172a',
            color: '#f8fafc',
        });
    });
};

// Verify MTC and Push to Inventory
const verifyDocument = () => {
    // Validate warehouse
    if (!warehouseId.value) {
        Swal.fire({
            title: 'Input Diperlukan',
            text: 'Harap tentukan Warehouse penerima coil.',
            icon: 'error',
            background: '#0f172a',
            color: '#f8fafc',
        });
        return;
    }

    // Validate that all items are mapped to products
    const unmappedItems = items.value.filter(item => !item.product_id);
    if (unmappedItems.length > 0) {
        Swal.fire({
            title: 'Pemetaan Produk Belum Lengkap',
            text: `Terdapat ${unmappedItems.length} coil yang belum dipetakan ke kode produk database USICS. Harap petakan terlebih dahulu.`,
            icon: 'error',
            background: '#0f172a',
            color: '#f8fafc',
        });
        return;
    }

    Swal.fire({
        title: 'Verifikasi & Masukkan Inventaris?',
        text: 'Tindakan ini akan membuat data coil baru di tabel inventory lots aktif berdasarkan data MTC ini.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#1e293b',
        confirmButtonText: 'Ya, Verifikasi!',
        cancelButtonText: 'Batal',
        background: '#0f172a',
        color: '#f8fafc',
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('qc.mtc.verify', props.document.id), {
                supplier_id: supplierId.value,
                warehouse_id: warehouseId.value,
                location_id: locationId.value,
                notes: notes.value,
                items: items.value,
            }, {
                onSuccess: () => {
                    Swal.fire({
                        title: 'Sukses!',
                        text: 'MTC Terverifikasi. Data coil telah masuk ke inventory lots.',
                        icon: 'success',
                        background: '#0f172a',
                        color: '#f8fafc',
                    });
                }
            });
        }
    });
};

// Reject Document
const rejectDocument = () => {
    Swal.fire({
        title: 'Tolak MTC Dokumen?',
        text: 'Masukkan alasan penolakan dokumen ini:',
        input: 'text',
        inputPlaceholder: 'Misal: Dokumen buram, nomor heat tidak cocok...',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#1e293b',
        confirmButtonText: 'Tolak Dokumen',
        cancelButtonText: 'Batal',
        background: '#0f172a',
        color: '#f8fafc',
        inputValidator: (value) => {
            if (!value) {
                return 'Alasan penolakan wajib diisi!';
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('qc.mtc.reject', props.document.id), {
                reason: result.value
            }, {
                onSuccess: () => {
                    Swal.fire({
                        title: 'Ditolak!',
                        text: 'Dokumen MTC berhasil ditolak.',
                        icon: 'success',
                        background: '#0f172a',
                        color: '#f8fafc',
                    });
                }
            });
        }
    });
};

// Trigger AI Re-extraction
const isReExtracting = ref(false);
const triggerReExtract = () => {
    Swal.fire({
        title: 'Ekstrak Ulang dengan AI?',
        text: 'Tindakan ini akan memproses ulang dokumen MTC menggunakan Gemini AI. Perubahan manual yang belum disimpan mungkin akan hilang.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#06b6d4',
        cancelButtonColor: '#1e293b',
        confirmButtonText: 'Ekstrak Ulang',
        cancelButtonText: 'Batal',
        background: '#0f172a',
        color: '#f8fafc',
    }).then((result) => {
        if (result.isConfirmed) {
            isReExtracting.value = true;
            router.post(route('qc.mtc.re-extract', props.document.id), {}, {
                onFinish: () => {
                    isReExtracting.value = false;
                    Swal.fire({
                        title: 'Selesai!',
                        text: 'Proses ekstraksi ulang selesai.',
                        icon: 'success',
                        background: '#0f172a',
                        color: '#f8fafc',
                    });
                }
            });
        }
    });
};
</script>

<template>
    <Head :title="'Verification MTC: ' + (document.certificate_number || document.file_name)" />

    <AppLayout title="Quality Control Dashboard" :render-header="false">
        <div class="h-screen flex flex-col bg-[#030310] text-slate-100 font-sans overflow-hidden">
            <!-- Navbar / Sub-Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800/80 bg-slate-950/60 backdrop-blur-md z-10">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('qc.mtc.index')"
                        class="p-2 bg-slate-900 border border-slate-800 hover:border-slate-700 text-slate-400 hover:text-slate-200 rounded-lg transition"
                    >
                        <ArrowLeftIcon class="w-5 h-5" />
                    </Link>
                    <div>
                        <h2 class="text-lg font-black tracking-wider text-slate-200 uppercase font-mono">
                            Verification Console
                        </h2>
                        <div class="flex items-center gap-2 text-xs text-slate-500 font-mono mt-0.5">
                            <span>Status:</span>
                            <span v-if="document.status === 'draft'" class="text-amber-400 font-bold uppercase">Draft</span>
                            <span v-else-if="document.status === 'verified'" class="text-emerald-400 font-bold uppercase">Verified</span>
                            <span v-else-if="document.status === 'rejected'" class="text-red-400 font-bold uppercase">Rejected</span>
                            <span class="text-slate-700">|</span>
                            <span>File: {{ document.file_name }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Re-Extract AI Button -->
                    <button
                        v-if="document.status === 'draft' && !isReExtracting"
                        @click="triggerReExtract"
                        class="flex items-center gap-1.5 px-4 py-2 border border-cyan-800/40 bg-cyan-950/20 text-cyan-400 hover:bg-cyan-900/40 rounded-lg text-xs font-bold uppercase font-mono transition"
                    >
                        <ArrowPathIcon class="w-4 h-4" />
                        AI Re-Extract
                    </button>
                    <span v-else-if="isReExtracting" class="flex items-center gap-1.5 px-4 py-2 border border-cyan-800/20 text-cyan-400 rounded-lg text-xs font-mono">
                        <ArrowPathIcon class="w-4 h-4 animate-spin" />
                        AI extracting...
                    </span>

                    <!-- Reject Button -->
                    <button
                        v-if="document.status === 'draft'"
                        @click="rejectDocument"
                        class="flex items-center gap-1.5 px-4 py-2 border border-red-800/40 bg-red-950/20 text-red-400 hover:bg-red-900/40 rounded-lg text-xs font-bold uppercase font-mono transition"
                    >
                        <XCircleIcon class="w-4 h-4" />
                        Reject
                    </button>

                    <!-- Save Draft Button -->
                    <button
                        v-if="document.status === 'draft'"
                        @click="saveDraft"
                        :disabled="isSaving"
                        class="flex items-center gap-1.5 px-4 py-2 border border-slate-700 bg-slate-900 text-slate-300 hover:bg-slate-800 rounded-lg text-xs font-bold uppercase font-mono transition"
                    >
                        <BookmarkSquareIcon class="w-4 h-4" />
                        {{ isSaving ? 'Saving...' : 'Save Draft' }}
                    </button>

                    <!-- Verify Button -->
                    <button
                        v-if="document.status === 'draft'"
                        @click="verifyDocument"
                        class="flex items-center gap-1.5 px-6 py-2.5 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-400 hover:to-emerald-500 text-black font-bold uppercase font-mono rounded-lg text-xs transition shadow-[0_0_15px_rgba(16,185,129,0.3)]"
                    >
                        <CheckBadgeIcon class="w-5 h-5" />
                        Verify & PUSH
                    </button>
                </div>
            </div>

            <!-- Split Panel Container -->
            <div class="flex-1 flex overflow-hidden">
                <!-- Left Pane: PDF Viewer -->
                <div class="w-1/2 border-r border-slate-800/80 bg-slate-950 flex flex-col">
                    <div class="flex-1 relative">
                        <!-- Private File Route Serve -->
                        <iframe
                            v-if="document.file_type === 'pdf'"
                            :src="route('qc.mtc.file', document.id)"
                            class="w-full h-full border-0"
                        ></iframe>
                        <!-- Fallback Image Viewer -->
                        <div v-else class="w-full h-full overflow-auto flex items-center justify-center p-4 bg-slate-950">
                            <img 
                                :src="route('qc.mtc.file', document.id)" 
                                class="max-w-full max-h-full object-contain rounded-lg border border-slate-800 shadow-2xl"
                                alt="MTC Scanned Image"
                            />
                        </div>
                    </div>
                </div>

                <!-- Right Pane: Edit/Verify Form -->
                <div class="w-1/2 overflow-y-auto p-6 space-y-6 bg-[#040413] scrollbar-thin">
                    
                    <!-- Warehouse & Location Receiving Area (Glow Border) -->
                    <div class="bg-gradient-to-r from-cyan-950/20 to-slate-950/40 border border-cyan-800/30 p-5 rounded-xl space-y-4 shadow-[0_0_15px_rgba(6,182,212,0.05)]">
                        <h3 class="text-sm font-bold tracking-wider text-cyan-400 uppercase font-mono flex items-center gap-2">
                            <WrenchIcon class="w-4 h-4" />
                            Gudang Penerima (Coil Receiving)
                        </h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider font-mono mb-1.5">Warehouse *</label>
                                <select
                                    v-model="warehouseId"
                                    :disabled="document.status !== 'draft'"
                                    class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition"
                                >
                                    <option value="">Pilih Warehouse...</option>
                                    <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider font-mono mb-1.5">Location (Bin/Area)</label>
                                <select
                                    v-model="locationId"
                                    :disabled="document.status !== 'draft' || !warehouseId"
                                    class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition"
                                >
                                    <option value="">Pilih Area...</option>
                                    <option v-for="loc in filteredLocations" :key="loc.id" :value="loc.id">{{ loc.name }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Document Header Card -->
                    <div class="bg-gradient-to-b from-slate-950 to-slate-950/80 border border-slate-800/80 p-5 rounded-xl space-y-4 shadow-lg">
                        <h3 class="text-sm font-bold tracking-wider text-slate-300 uppercase font-mono border-b border-slate-900 pb-2">
                            MTC Header Information
                        </h3>

                        <!-- Supplier Mapping Row -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider font-mono mb-1">Nama Mill / Supplier (AI)</label>
                                <div class="flex gap-2">
                                    <input
                                        type="text"
                                        v-model="supplierName"
                                        :disabled="document.status !== 'draft'"
                                        class="flex-1 bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-cyan-500 transition"
                                        placeholder="MTC Supplier Name"
                                    />
                                    <button
                                        v-if="document.status === 'draft'"
                                        @click="autoMapSupplier"
                                        type="button"
                                        class="px-3 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-xs font-mono rounded-lg transition"
                                        title="Auto-Map ke Database"
                                    >
                                        Map
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider font-mono mb-1">Pemetaan Supplier Database (USICS) *</label>
                                <select
                                    v-model="supplierId"
                                    :disabled="document.status !== 'draft'"
                                    class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-cyan-500 transition"
                                >
                                    <option value="">Pilih Supplier Database...</option>
                                    <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                                </select>
                            </div>
                        </div>

                        <!-- Secondary details -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider font-mono mb-1">MTC Cert Number</label>
                                <input
                                    type="text"
                                    v-model="certificateNumber"
                                    :disabled="document.status !== 'draft'"
                                    class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-cyan-500 transition"
                                />
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider font-mono mb-1">Tanggal Terbit (Date of Issue)</label>
                                <input
                                    type="date"
                                    v-model="dateOfIssue"
                                    :disabled="document.status !== 'draft'"
                                    class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-cyan-500 transition font-mono"
                                />
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider font-mono mb-1">Nomor PO (Purchase Order)</label>
                                <input
                                    type="text"
                                    v-model="poNo"
                                    :disabled="document.status !== 'draft'"
                                    class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-cyan-500 transition font-mono"
                                />
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider font-mono mb-1">Specification & Grade</label>
                                <input
                                    type="text"
                                    v-model="specAndType"
                                    :disabled="document.status !== 'draft'"
                                    class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-cyan-500 transition font-mono text-cyan-400"
                                />
                            </div>
                        </div>

                        <!-- Notes Area -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider font-mono mb-1">Catatan / Keterangan</label>
                            <textarea
                                v-model="notes"
                                :disabled="document.status !== 'draft'"
                                rows="2"
                                class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-cyan-500 transition"
                                placeholder="Masukkan catatan MTC jika diperlukan..."
                            ></textarea>
                        </div>
                    </div>

                    <!-- Document Items (Coils List) -->
                    <div class="bg-gradient-to-b from-slate-950 to-slate-950/80 border border-slate-800/80 p-5 rounded-xl space-y-4 shadow-lg">
                        <h3 class="text-sm font-bold tracking-wider text-slate-300 uppercase font-mono border-b border-slate-900 pb-2">
                            Coils / Items List ({{ items.length }} items)
                        </h3>

                        <!-- Items Scroll Area -->
                        <div class="space-y-4">
                            <div 
                                v-for="(item, idx) in items" 
                                :key="item.id" 
                                class="bg-slate-900/40 border border-slate-800 p-4 rounded-lg space-y-3 relative hover:border-slate-700/80 transition"
                            >
                                <div class="flex justify-between items-center">
                                    <span class="text-xs font-mono font-bold text-slate-500 bg-slate-850 px-2 py-0.5 rounded border border-slate-800">
                                        #{{ idx + 1 }}
                                    </span>

                                    <!-- Action: View Spec Details -->
                                    <button
                                        @click="openItemDetails(idx)"
                                        type="button"
                                        class="flex items-center gap-1 text-[11px] text-cyan-400 font-mono hover:text-cyan-300 font-bold uppercase transition"
                                    >
                                        <BeakerIcon class="w-4 h-4" />
                                        Chemical/Mechanical Specs
                                    </button>
                                </div>

                                <!-- Coil Identification -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider font-mono mb-1">Coil Number (Product No)</label>
                                        <input
                                            type="text"
                                            v-model="item.product_no"
                                            :disabled="document.status !== 'draft'"
                                            class="w-full bg-slate-950 border border-slate-850 rounded px-2 py-1 text-xs text-slate-200 font-mono focus:outline-none focus:border-cyan-500 transition"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider font-mono mb-1">Heat Number</label>
                                        <input
                                            type="text"
                                            v-model="item.heat_no"
                                            :disabled="document.status !== 'draft'"
                                            class="w-full bg-slate-950 border border-slate-850 rounded px-2 py-1 text-xs text-slate-200 font-mono focus:outline-none focus:border-cyan-500 transition"
                                        />
                                    </div>
                                </div>

                                <!-- Size & Weight -->
                                <div class="grid grid-cols-3 gap-3">
                                    <div class="col-span-2">
                                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider font-mono mb-1">Size String (e.g. TxWxC)</label>
                                        <input
                                            type="text"
                                            v-model="item.size"
                                            :disabled="document.status !== 'draft'"
                                            class="w-full bg-slate-950 border border-slate-850 rounded px-2 py-1 text-xs text-slate-200 font-mono focus:outline-none focus:border-cyan-500 transition"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider font-mono mb-1">Weight (Kg)</label>
                                        <input
                                            type="number"
                                            v-model="item.weight_kg"
                                            :disabled="document.status !== 'draft'"
                                            class="w-full bg-slate-950 border border-slate-850 rounded px-2 py-1 text-xs text-slate-200 font-mono focus:outline-none focus:border-cyan-500 transition"
                                        />
                                    </div>
                                </div>

                                <!-- Database Product Mapping Dropdown -->
                                <div class="bg-slate-950/40 p-2.5 rounded border border-slate-850/50">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider font-mono mb-1">Petakan ke Kode Produk Database (USICS) *</label>
                                    <select
                                        v-model="item.product_id"
                                        :disabled="document.status !== 'draft'"
                                        class="w-full bg-slate-950 border border-slate-850 rounded px-2 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-cyan-500 transition"
                                    >
                                        <option value="">Pilih Produk Database...</option>
                                        <option v-for="p in products" :key="p.id" :value="p.id">
                                            [{{ p.code }}] {{ p.name }} - Thk: {{ p.thickness }}mm, Wd: {{ p.width }}mm
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Details Modal (Specs & Analysis values) -->
            <div v-if="showDetailsModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
                <div class="w-full max-w-xl bg-slate-950 border border-slate-800 rounded-2xl overflow-hidden shadow-2xl">
                    <div class="px-6 py-4 border-b border-slate-800 bg-slate-900/60 flex justify-between items-center">
                        <h4 class="text-sm font-bold uppercase tracking-wider font-mono text-cyan-400">
                            Metallurgical Specifications #{{ selectedItemIndex + 1 }}
                        </h4>
                        <button 
                            @click="showDetailsModal = false"
                            class="text-slate-400 hover:text-slate-200 font-bold font-mono text-lg"
                        >
                            &times;
                        </button>
                    </div>

                    <div class="p-6 space-y-6 max-h-[70vh] overflow-y-auto scrollbar-thin">
                        <!-- Mechanical Properties -->
                        <div class="space-y-3">
                            <h5 class="text-xs font-bold text-slate-400 uppercase tracking-wider font-mono border-b border-slate-900 pb-1">Mechanical Test</h5>
                            <div class="grid grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 font-mono mb-1">Yield Point (YP - MPa)</label>
                                    <input 
                                        type="text" 
                                        v-model="items[selectedItemIndex].yp_mpa" 
                                        :disabled="document.status !== 'draft'"
                                        class="w-full bg-slate-900 border border-slate-800 rounded px-2 py-1 text-xs text-slate-200 font-mono" 
                                    />
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 font-mono mb-1">Tensile Strength (TS - MPa)</label>
                                    <input 
                                        type="text" 
                                        v-model="items[selectedItemIndex].ts_mpa" 
                                        :disabled="document.status !== 'draft'"
                                        class="w-full bg-slate-900 border border-slate-800 rounded px-2 py-1 text-xs text-slate-200 font-mono" 
                                    />
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 font-mono mb-1">Elongation (EL - %)</label>
                                    <input 
                                        type="text" 
                                        v-model="items[selectedItemIndex].el_percent" 
                                        :disabled="document.status !== 'draft'"
                                        class="w-full bg-slate-900 border border-slate-800 rounded px-2 py-1 text-xs text-slate-200 font-mono" 
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Chemical Composition (Ladle) -->
                        <div class="space-y-3">
                            <h5 class="text-xs font-bold text-slate-400 uppercase tracking-wider font-mono border-b border-slate-900 pb-1">Chemical Ladle Analysis (%)</h5>
                            <div class="grid grid-cols-4 gap-3">
                                <div v-for="el in ['C', 'Si', 'Mn', 'P', 'S', 'Al', 'Cr', 'Ni', 'B', 'Cu', 'Mo', 'CEQ']" :key="el">
                                    <label class="block text-[10px] font-bold text-slate-500 font-mono mb-1">{{ el }}</label>
                                    <input 
                                        type="number"
                                        step="0.0001"
                                        v-model="items[selectedItemIndex].chemical_ladle[el]"
                                        :disabled="document.status !== 'draft'"
                                        class="w-full bg-slate-900 border border-slate-800 rounded px-2 py-1 text-xs text-slate-200 font-mono"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-slate-800 bg-slate-950/60 flex justify-end">
                        <button
                            @click="showDetailsModal = false"
                            class="px-5 py-2 bg-slate-850 hover:bg-slate-800 border border-slate-700 text-xs font-bold uppercase font-mono rounded-lg text-slate-300 transition"
                        >
                            Tutup
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<style scoped>
.scrollbar-thin::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
.scrollbar-thin::-webkit-scrollbar-track {
    background: #030310;
}
.scrollbar-thin::-webkit-scrollbar-thumb {
    background: #1e293b;
    border-radius: 3px;
}
.scrollbar-thin::-webkit-scrollbar-thumb:hover {
    background: #06b6d4;
}
</style>
