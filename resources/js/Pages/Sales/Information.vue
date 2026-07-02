<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import ManualSectionContent from './Partials/ManualSectionContent.vue';
import SalesFlowGuide from './Partials/SalesFlowGuide.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted, nextTick } from 'vue';
import { 
    ZapIcon, 
    FileInputIcon, 
    ShoppingCartIcon, 
    TruckIcon, 
    CheckCircle2Icon, 
    ReceiptIcon, 
    LockIcon,
    InfoIcon,
    LayoutDashboardIcon,
    UsersIcon,
    SendIcon,
    FileTextIcon,
    PackageIcon,
    RotateCcwIcon,
    BotIcon,
    ChevronDownIcon,
    CalendarIcon,
    BarChart3Icon,
    SearchIcon,
    ArrowRightIcon,
    AlertTriangleIcon,
    LightbulbIcon,
    SparklesIcon,
    ListIcon,
    PrinterIcon,
    MapIcon,
} from 'lucide-vue-next';

const props = defineProps({
    chartData: Object,
    chartPercentages: Object,
    queryParams: Object,
});

const activeTab = ref('flow');
const activeStep = ref(1);
const simulationRunning = ref(false);
const expandedSections = ref(new Set());
const activeNavItem = ref(null);

const toggleSection = (id) => {
    if (expandedSections.value.has(id)) {
        expandedSections.value.delete(id);
    } else {
        expandedSections.value.add(id);
    }
};

const expandAll = () => {
    manualSections.forEach(s => expandedSections.value.add(s.id));
};

const collapseAll = () => {
    expandedSections.value.clear();
};

const scrollToSection = (id) => {
    activeNavItem.value = id;
    const el = document.getElementById('manual-' + id);
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    if (!expandedSections.value.has(id)) toggleSection(id);
};

const printManual = async () => {
    if (activeTab.value === 'sop') {
         window.print();
         return;
    }
    activeTab.value = 'manual';
    expandAll();
    await nextTick();
    setTimeout(() => {
        window.print();
    }, 300); 
};

const manualSections = [
    { id: 'sales-hub', num: '1', title: 'Sales Hub (Dashboard)', icon: LayoutDashboardIcon, color: 'blue', desc: 'Dashboard utama untuk memantau KPI penjualan secara real-time.' },
    { id: 'planning-dashboard', num: '2', title: 'Planning > Dashboard', icon: BarChart3Icon, color: 'cyan', desc: 'Ringkasan visual dari seluruh perencanaan penjualan.' },
    { id: 'planning-forecast', num: '3', title: 'Planning > Forecast', icon: SparklesIcon, color: 'violet', desc: 'Mengelola prediksi demand (permintaan) pelanggan.' },
    { id: 'planning-schedule', num: '4', title: 'Planning > Schedule', icon: CalendarIcon, color: 'sky', desc: 'Melihat jadwal pengiriman yang sudah direncanakan.' },
    { id: 'customers', num: '5', title: 'Customers (Master Data)', icon: UsersIcon, color: 'indigo', desc: 'Tempat mengelola seluruh data pelanggan.' },
    { id: 'quotations', num: '6', title: 'Quotations (Penawaran)', icon: SendIcon, color: 'purple', desc: 'Membuat penawaran harga resmi ke calon pelanggan.' },
    { id: 'sales-orders', num: '7', title: 'Sales Orders (SO)', icon: FileTextIcon, color: 'amber', desc: 'Dokumen pesanan penjualan resmi — dasar pengiriman & penagihan.' },
    { id: 'delivery-orders', num: '8', title: 'Delivery Orders (DO)', icon: PackageIcon, color: 'emerald', desc: 'Surat Jalan pengiriman barang ke pelanggan.' },
    { id: 'sales-invoices', num: '9', title: 'Sales Invoices (Faktur)', icon: ReceiptIcon, color: 'teal', desc: 'Faktur penjualan / tagihan resmi ke pelanggan.' },
    { id: 'sales-returns', num: '10', title: 'Sales Returns (Retur)', icon: RotateCcwIcon, color: 'red', desc: 'Mencatat pengembalian barang dari pelanggan.' },
    { id: 'po-tracking', num: '11', title: 'PO Tracking', icon: SearchIcon, color: 'orange', desc: 'Melacak status seluruh PO customer dari awal hingga selesai.' },
    { id: 'ai-extractor', num: '12', title: 'AI PO Extractor', icon: BotIcon, color: 'slate', desc: 'Fitur otomatisasi input order dari dokumen PO Customer.' },
    { id: 'whatsapp-orchestrator', num: '13', title: 'Smart AI WhatsApp Orchestrator', icon: BotIcon, color: 'emerald', desc: 'Unggah PO, konfirmasi SO via WA, otomatis audit stok, WIP, BOM, & PR/WO.' },
    { id: 'pricing-intelligence', num: '14', title: 'AI Pricing Intelligence', icon: SparklesIcon, color: 'violet', desc: 'Simulasi margin dinamis berbasis LME, kurs, scrap, & saran harga AI.' },
];

const stepsData = {
    1: { title: "PO Customer Diterima", desc: "Admin input data manual atau via AI PO Extractor untuk data otomatis.", stats: ["99% Akurasi AI", "Manual/Auto"], icon: FileInputIcon },
    2: { title: "Pembuatan Sales Order", desc: "Konversi PO menjadi SO. Booking stok di gudang agar tidak terjual ke orang lain.", stats: ["Booking Stok", "Cek Margin"], icon: ShoppingCartIcon },
    3: { title: "Pengiriman (Delivery)", desc: "Picking & Packing di gudang. Penerbitan Surat Jalan untuk driver.", stats: ["Tracking Driver", "Stok Berkurang"], icon: TruckIcon },
    4: { title: "Penyelesaian DO (POD)", desc: "Customer tanda tangan Surat Jalan. Admin verifikasi bukti fisik di sistem.", stats: ["POD Verified", "Siap Billing"], icon: CheckCircle2Icon },
    5: { title: "Penagihan (Invoice)", desc: "Faktur terbit sesuai pengiriman. Bisa cetak Massal/Batch per bulan.", stats: ["Faktur Pajak", "Batch Printing"], icon: ReceiptIcon },
    6: { title: "Diterima / Closed", desc: "Pembayaran lunas. Status invoice berubah menjadi 'Paid'. Transaksi Selesai.", stats: ["Cash In", "Closed Deal"], icon: LockIcon },
};

const activateToStep = (index) => { activeStep.value = index; };

const animateProcess = async () => {
    if (simulationRunning.value) return;
    simulationRunning.value = true;
    for (let i = 6; i >= 1; i--) { activeStep.value = i; } // Reset
    await new Promise(r => setTimeout(r, 500));
    simulationRunning.value = true;
    for (let i = 1; i <= 6; i++) { activeStep.value = i; await new Promise(r => setTimeout(r, 2000)); }
    simulationRunning.value = false;
};

onMounted(() => { 
    activeStep.value = 1; 
    
    // Check if query params or hash points to manual
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    if (tabParam) {
        activeTab.value = tabParam;
    }
    
    if (window.location.hash) {
        const targetId = window.location.hash.replace('#manual-', '');
        if (targetId) {
            activeTab.value = 'manual';
            setTimeout(() => {
                scrollToSection(targetId);
            }, 300);
        }
    }
});

const getProgressWidth = () => ((activeStep.value - 1) / 5) * 100 + '%';

</script>

<template>
    <AppLayout title="Information">
        <template #header>
            <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">Sales Information Center</h2>
        </template>
        <div class="py-12 px-4 sm:px-6 lg:px-8 bg-slate-50 dark:bg-slate-950 min-h-[calc(100vh-64px)] overflow-hidden relative">
            <div class="absolute top-[-10%] right-[-10%] w-[50%] h-[50%] bg-indigo-600/5 rounded-full blur-[120px] pointer-events-none"></div>
            <div class="absolute bottom-[-10%] left-[-10%] w-[40%] h-[40%] bg-purple-600/5 rounded-full blur-[100px] pointer-events-none"></div>
            <div class="max-w-7xl mx-auto relative z-10">
                <div class="flex justify-center mb-12">
                    <div class="inline-flex p-1 bg-slate-200 dark:bg-slate-900 rounded-2xl border border-slate-300 dark:border-slate-800 shadow-sm">
                        <button @click="activeTab = 'flow'" :class="activeTab === 'flow' ? 'bg-white dark:bg-slate-800 text-indigo-600 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'" class="px-6 py-2.5 rounded-xl text-sm font-bold uppercase tracking-wider transition-all duration-300">Interactive Flow</button>
                        <button @click="activeTab = 'sop'" :class="activeTab === 'sop' ? 'bg-white dark:bg-slate-800 text-indigo-600 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'" class="px-6 py-2.5 rounded-xl text-sm font-bold uppercase tracking-wider transition-all duration-300">SOP / Flowchart</button>
                        <button @click="activeTab = 'manual'" :class="activeTab === 'manual' ? 'bg-white dark:bg-slate-800 text-indigo-600 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'" class="px-6 py-2.5 rounded-xl text-sm font-bold uppercase tracking-wider transition-all duration-300">User Manual</button>
                    </div>
                    <button @click="printManual" class="ml-4 p-2.5 rounded-xl bg-slate-200 dark:bg-slate-800 text-slate-500 hover:text-indigo-600 hover:bg-white dark:hover:text-indigo-400 transition-all shadow-sm print:hidden" title="Print Guide">
                        <PrinterIcon class="w-5 h-5" />
                    </button>
                </div>

                <div v-if="activeTab === 'flow'" class="space-y-16 animate-in fade-in duration-700 print:hidden">
                    <div class="text-center">
                        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-xs font-bold uppercase tracking-widest mb-4"><ZapIcon class="w-4 h-4" /> Visualization</div>
                        <h1 class="text-4xl md:text-5xl font-extrabold mb-4 tracking-tight text-slate-900 dark:text-white">Sales Journey <span class="text-indigo-500">Flow</span></h1>
                        <p class="text-slate-500 dark:text-slate-400 max-w-2xl mx-auto text-lg">Pantau alur data dari penerimaan PO hingga pelunasan Invoice secara visual.</p>
                    </div>
                    <div class="relative py-10">
                        <div class="hidden lg:block absolute h-[4px] w-[calc(100%-100px)] top-[60px] left-[50px] bg-slate-200 dark:bg-slate-800">
                            <div class="absolute h-full bg-gradient-to-r from-indigo-500 to-purple-500 transition-all duration-1000 shadow-[0_0_15px_rgba(99,102,241,0.5)]" :style="{ width: getProgressWidth() }">
                                <div class="absolute right-[-6px] top-1/2 -translate-y-1/2 w-4 h-4 bg-indigo-500 rounded-full shadow-[0_0_20px_#6366f1] animate-pulse"></div>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-8 items-start relative z-10">
                            <div v-for="(step, index) in stepsData" :key="index" @click="activateToStep(parseInt(index))" class="cursor-pointer transition-all duration-500" :class="parseInt(index) <= activeStep ? 'opacity-100 scale-100' : 'opacity-30 grayscale scale-95'">
                                <div class="p-6 rounded-[28px] text-center border transition-all duration-500" :class="parseInt(index) <= activeStep ? 'bg-white dark:bg-slate-900 border-indigo-500/30 shadow-xl dark:shadow-indigo-500/10' : 'bg-white/50 dark:bg-white/5 border-slate-200 dark:border-slate-800'">
                                    <div class="w-16 h-16 flex items-center justify-center mx-auto mb-4 rounded-2xl transition-all duration-500" :class="parseInt(index) <= activeStep ? 'bg-gradient-to-br from-indigo-500 to-purple-500 text-white shadow-lg' : 'bg-slate-100 dark:bg-slate-800 text-slate-400'">
                                        <component :is="step.icon" class="w-8 h-8" />
                                    </div>
                                    <h3 class="font-bold text-lg mb-2 text-slate-800 dark:text-slate-200">{{ step.title }}</h3>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">{{ step.desc }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-center mt-12">
                            <button @click="animateProcess" :disabled="simulationRunning" class="px-8 py-4 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-2xl shadow-xl shadow-indigo-600/30 transition-all transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed group">
                                <span v-if="!simulationRunning" class="flex items-center gap-2">Simulasi Alur Proses <ZapIcon class="w-4 h-4 group-hover:animate-pulse" /></span>
                                <span v-else class="flex items-center gap-2">Mensiimulasikan... <span class="animate-spin inline-block w-4 h-4 border-2 border-white/30 border-t-white rounded-full"></span></span>
                            </button>
                        </div>
                    </div>
                    <div class="p-8 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[32px] max-w-4xl mx-auto shadow-2xl transition-all duration-700">
                        <div class="flex flex-col md:flex-row items-center md:items-start gap-8">
                            <div class="p-6 bg-indigo-500 rounded-[28px] shadow-lg shadow-indigo-500/30 shrink-0"><component :is="stepsData[activeStep].icon" class="w-10 h-10 text-white" /></div>
                            <div class="text-center md:text-left">
                                <h2 class="text-3xl font-bold mb-4 text-slate-900 dark:text-white">{{ stepsData[activeStep].title }}</h2>
                                <p class="text-slate-500 dark:text-slate-400 leading-relaxed text-lg mb-8">{{ stepsData[activeStep].desc }}</p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div v-for="(stat, sIdx) in stepsData[activeStep].stats" :key="sIdx" class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/50 flex items-center gap-3">
                                        <div class="w-2.5 h-2.5 rounded-full bg-indigo-500 shadow-[0_0_8px_#6366f1]"></div>
                                        <span class="text-sm font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest">{{ stat }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else-if="activeTab === 'sop'" class="animate-in fade-in duration-700 print:block">
                    <SalesFlowGuide />
                </div>

                <div v-else-if="activeTab === 'manual'" class="max-w-5xl mx-auto space-y-8 print:w-full print:max-w-none">
                    <div class="text-center mb-8 animate-in fade-in duration-700">
                        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-bold uppercase tracking-widest mb-4 print:hidden"><InfoIcon class="w-4 h-4" /> Comprehensive Guide</div>
                        <h1 class="text-4xl font-bold mb-4 text-slate-900 dark:text-white print:text-black">Panduan Pengunaan Modul Sales</h1>
                        <p class="text-slate-500 dark:text-slate-400 print:text-slate-600">Langkah demi langkah operasional setiap menu aplikasi. Klik section untuk membuka detail.</p>
                    </div>
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm sticky top-4 z-20 animate-in slide-in-from-top-3 duration-500 print:hidden">
                        <div class="flex flex-wrap items-center gap-2">
                            <button @click="expandAll" class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 rounded-lg hover:bg-emerald-100 transition-colors">Buka Semua</button>
                            <button @click="collapseAll" class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider bg-slate-100 dark:bg-slate-800 text-slate-500 rounded-lg hover:bg-slate-200 transition-colors">Tutup Semua</button>
                            <div class="w-px h-5 bg-slate-200 dark:bg-slate-700 mx-1"></div>
                            <button v-for="s in manualSections" :key="s.id" @click="scrollToSection(s.id)" class="px-2.5 py-1.5 text-[10px] font-bold rounded-lg transition-all hover:scale-105" :class="expandedSections.has(s.id) ? 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600' : 'bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-slate-600'">{{ s.num }}</button>
                        </div>
                    </div>
                    <div class="space-y-4 print:space-y-8">
                        <div v-for="(section, idx) in manualSections" :key="section.id" :id="'manual-' + section.id" class="animate-in slide-in-from-bottom-3 duration-500" :style="{ animationDelay: idx * 50 + 'ms' }">
                            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[20px] overflow-hidden transition-all duration-300 hover:shadow-lg print:border-none print:shadow-none print:break-inside-avoid" :class="expandedSections.has(section.id) ? 'shadow-xl ring-1 ring-indigo-500/20' : ''">
                                <button @click="toggleSection(section.id)" class="w-full p-5 flex items-center gap-4 text-left group transition-colors print:hidden" :class="expandedSections.has(section.id) ? 'bg-slate-50/50 dark:bg-slate-800/30' : 'hover:bg-slate-50 dark:hover:bg-slate-800/20'">
                                    <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 transition-all duration-300 manual-icon-box" :class="[expandedSections.has(section.id) ? 'manual-icon-active bg-indigo-500 text-white shadow-lg' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 group-hover:bg-indigo-500 group-hover:text-white']">
                                        <component :is="section.icon" class="w-6 h-6" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ section.num }}. {{ section.title }}</h3>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ section.desc }}</p>
                                    </div>
                                    <ChevronDownIcon class="w-5 h-5 text-slate-400 shrink-0 transition-transform duration-300" :class="expandedSections.has(section.id) ? 'rotate-180' : ''" />
                                </button>
                                <!-- Print Header for Section -->
                                <div class="hidden print:flex items-center gap-3 mb-4 border-b border-black pb-2">
                                    <h3 class="text-xl font-bold text-black">{{ section.num }}. {{ section.title }}</h3>
                                </div>
                                <Transition name="accordion">
                                    <div v-if="expandedSections.has(section.id)" class="px-5 pb-5 pt-0 border-t border-slate-100 dark:border-slate-800 print:border-none print:p-0">
                                        <div class="pt-4"><ManualSectionContent :sectionId="section.id" /></div>
                                    </div>
                                </Transition>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(99,102,241,0.2); border-radius: 3px; }
.manual-icon-active { box-shadow: 0 8px 25px rgba(99,102,241,0.3); }
.accordion-enter-active { transition: all 0.3s ease-out; max-height: 2000px; overflow: hidden; }
.accordion-leave-active { transition: all 0.2s ease-in; max-height: 2000px; overflow: hidden; }
.accordion-enter-from, .accordion-leave-to { opacity: 0; max-height: 0; }

@media print {
    :deep(nav), :deep(header), :deep(.bg-slate-50) {
        background: white !important; 
    }
    :deep(.text-slate-900), :deep(.text-white) {
        color: black !important;
    }
    :deep(.text-slate-500), :deep(.text-slate-400) {
        color: #333 !important;
    }
    .print\:hidden {
        display: none !important;
    }
    .print\:flex {
        display: flex !important;
    }
    .print\:block {
        display: block !important;
    }
}
</style>
