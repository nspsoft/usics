<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import ManualSectionContent from './Partials/ManualSectionContent.vue';
import InventoryFlowGuide from './Partials/InventoryFlowGuide.vue';
import { nextTick, onMounted, ref } from 'vue';
import {
    AlertTriangleIcon,
    BarChart3Icon,
    CheckCircle2Icon,
    ChevronDownIcon,
    ClipboardListIcon,
    InfoIcon,
    LayoutDashboardIcon,
    ListIcon,
    MapIcon,
    PackageIcon,
    PrinterIcon,
    RotateCcwIcon,
    ZapIcon,
} from 'lucide-vue-next';

const activeTab = ref('flow');
const activeStep = ref(1);
const simulationRunning = ref(false);
const expandedSections = ref(new Set());

const manualSections = [
    { id: 'command-center', num: '1', title: 'Command Center (Dashboard)', icon: LayoutDashboardIcon, desc: 'Ringkasan kondisi stok & aktivitas gudang.' },
    { id: 'categories', num: '2', title: 'Categories', icon: ListIcon, desc: 'Master kategori untuk klasifikasi produk.' },
    { id: 'products', num: '3', title: 'Products', icon: PackageIcon, desc: 'Master produk (RM/FG/Tools) termasuk harga/cost.' },
    { id: 'units', num: '4', title: 'Unit Management', icon: ClipboardListIcon, desc: 'Kelola satuan yang dipakai produk.' },
    { id: 'current-stock', num: '5', title: 'Current Stock', icon: BarChart3Icon, desc: 'Monitoring stok on-hand per gudang/lokasi.' },
    { id: 'warehouses', num: '6', title: 'Warehouses', icon: MapIcon, desc: 'Master gudang dan pengaturan layout/map.' },
    { id: 'warehouse-areas', num: '7', title: 'Warehouse Areas', icon: MapIcon, desc: 'Area/lokasi gudang untuk penempatan stok & opname.' },
    { id: 'stock-movements', num: '8', title: 'Stock Movements', icon: RotateCcwIcon, desc: 'Riwayat mutasi stok (audit trail).' },
    { id: 'stock-transfers', num: '9', title: 'Stock Transfers', icon: RotateCcwIcon, desc: 'Transfer stok antar gudang / lokasi.' },
    { id: 'stock-adjustments', num: '10', title: 'Stock Adjustments', icon: AlertTriangleIcon, desc: 'Koreksi stok karena selisih fisik/kerusakan/penyesuaian.' },
    { id: 'stock-opname', num: '11', title: 'Stock Opname', icon: CheckCircle2Icon, desc: 'Stock take (hitung fisik) dan penyesuaian sistem.' },
    { id: 'inventory-aging', num: '12', title: 'Inventory Aging', icon: BarChart3Icon, desc: 'Laporan umur persediaan untuk evaluasi slow moving.' },
];

const toggleSection = (id) => {
    if (expandedSections.value.has(id)) expandedSections.value.delete(id);
    else expandedSections.value.add(id);
};

const expandAll = () => {
    manualSections.forEach((s) => expandedSections.value.add(s.id));
};

const collapseAll = () => {
    expandedSections.value.clear();
};

const scrollToSection = (id) => {
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
    setTimeout(() => window.print(), 300);
};

const stepsData = {
    1: { title: 'Setup Master Data', desc: 'Lengkapi data Unit, Categories, Products, Warehouses, dan Warehouse Areas terlebih dahulu.', stats: ['Master Data', 'Wajib di awal'], icon: ListIcon },
    2: { title: 'Stok Masuk (Inbound)', desc: 'Stok bertambah dari transaksi Purchasing / Production / Return yang menghasilkan movement masuk.', stats: ['GR/Production', 'Auto Movement'], icon: PackageIcon },
    3: { title: 'Perpindahan Stok', desc: 'Gunakan Stock Transfers untuk pindah gudang/lokasi dengan proses ship & receive.', stats: ['Ship/Receive', 'Audit Trail'], icon: RotateCcwIcon },
    4: { title: 'Penyesuaian Stok', desc: 'Gunakan Stock Adjustments untuk koreksi selisih karena rusak, hilang, atau koreksi admin.', stats: ['Approval', 'Complete Posting'], icon: AlertTriangleIcon },
    5: { title: 'Stock Opname', desc: 'Hitung fisik (full count) lalu sistem menyesuaikan stok berdasarkan hasil hitung.', stats: ['Full Count', 'Adjustment'], icon: CheckCircle2Icon },
    6: { title: 'Monitoring & Report', desc: 'Pantau Current Stock, Stock Movements, dan Inventory Aging untuk kontrol persediaan.', stats: ['Monitoring', 'Audit'], icon: BarChart3Icon },
};

const totalSteps = Object.keys(stepsData).length;
const getProgressWidth = () => ((activeStep.value - 1) / (totalSteps - 1)) * 100 + '%';

const activateToStep = (index) => {
    activeStep.value = index;
};

const animateProcess = async () => {
    if (simulationRunning.value) return;
    simulationRunning.value = true;
    for (let i = totalSteps; i >= 1; i--) activeStep.value = i;
    await new Promise((r) => setTimeout(r, 400));
    for (let i = 1; i <= totalSteps; i++) {
        activeStep.value = i;
        await new Promise((r) => setTimeout(r, 1400));
    }
    simulationRunning.value = false;
};

onMounted(() => {
    activeStep.value = 1;
});
</script>

<template>
    <AppLayout title="Information">
        <template #header>
            <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">Inventory Information Center</h2>
        </template>

        <div class="py-12 px-4 sm:px-6 lg:px-8 bg-slate-50 dark:bg-slate-950 min-h-[calc(100vh-64px)] overflow-hidden relative">
            <div class="absolute top-[-10%] right-[-10%] w-[50%] h-[50%] bg-emerald-600/5 rounded-full blur-[120px] pointer-events-none"></div>
            <div class="absolute bottom-[-10%] left-[-10%] w-[40%] h-[40%] bg-cyan-600/5 rounded-full blur-[100px] pointer-events-none"></div>

            <div class="max-w-7xl mx-auto relative z-10">
                <div class="flex justify-center mb-12">
                    <div class="inline-flex p-1 bg-slate-200 dark:bg-slate-900 rounded-2xl border border-slate-300 dark:border-slate-800 shadow-sm">
                        <button
                            @click="activeTab = 'flow'"
                            :class="activeTab === 'flow' ? 'bg-white dark:bg-slate-800 text-emerald-600 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                            class="px-6 py-2.5 rounded-xl text-sm font-bold uppercase tracking-wider transition-all duration-300"
                        >
                            Flow Process
                        </button>
                        <button
                            @click="activeTab = 'sop'"
                            :class="activeTab === 'sop' ? 'bg-white dark:bg-slate-800 text-emerald-600 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                            class="px-6 py-2.5 rounded-xl text-sm font-bold uppercase tracking-wider transition-all duration-300"
                        >
                            SOP / Flowchart
                        </button>
                        <button
                            @click="activeTab = 'manual'"
                            :class="activeTab === 'manual' ? 'bg-white dark:bg-slate-800 text-emerald-600 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                            class="px-6 py-2.5 rounded-xl text-sm font-bold uppercase tracking-wider transition-all duration-300"
                        >
                            User Manual
                        </button>
                    </div>
                    <button @click="printManual" class="ml-4 p-2.5 rounded-xl bg-slate-200 dark:bg-slate-800 text-slate-500 hover:text-emerald-600 hover:bg-white dark:hover:text-emerald-400 transition-all shadow-sm print:hidden" title="Print Guide">
                        <PrinterIcon class="w-5 h-5" />
                    </button>
                </div>

                <div v-if="activeTab === 'flow'" class="space-y-16 animate-in fade-in duration-700 print:hidden">
                    <div class="text-center">
                        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-bold uppercase tracking-widest mb-4">
                            <ZapIcon class="w-4 h-4" />
                            Visualization
                        </div>
                        <h1 class="text-4xl md:text-5xl font-extrabold mb-4 tracking-tight text-slate-900 dark:text-white">Inventory <span class="text-emerald-500">Flow</span></h1>
                        <p class="text-slate-500 dark:text-slate-400 max-w-2xl mx-auto text-lg">Ringkasan alur penggunaan modul inventory untuk operasional gudang harian.</p>
                    </div>

                    <div class="relative py-10">
                        <div class="hidden lg:block absolute h-[4px] w-[calc(100%-100px)] top-[60px] left-[50px] bg-slate-200 dark:bg-slate-800">
                            <div class="absolute h-full bg-gradient-to-r from-emerald-500 to-cyan-500 transition-all duration-700 shadow-[0_0_15px_rgba(16,185,129,0.35)]" :style="{ width: getProgressWidth() }">
                                <div class="absolute right-[-6px] top-1/2 -translate-y-1/2 w-4 h-4 bg-emerald-500 rounded-full shadow-[0_0_20px_rgba(16,185,129,0.45)] animate-pulse"></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-8 items-start relative z-10">
                            <div
                                v-for="(step, index) in stepsData"
                                :key="index"
                                @click="activateToStep(parseInt(index))"
                                class="cursor-pointer transition-all duration-500"
                                :class="parseInt(index) <= activeStep ? 'opacity-100 scale-100' : 'opacity-30 grayscale scale-95'"
                            >
                                <div
                                    class="p-6 rounded-[28px] text-center border transition-all duration-500"
                                    :class="parseInt(index) <= activeStep ? 'bg-white dark:bg-slate-900 border-emerald-500/30 shadow-xl dark:shadow-emerald-500/10' : 'bg-white/50 dark:bg-white/5 border-slate-200 dark:border-slate-800'"
                                >
                                    <div class="w-16 h-16 flex items-center justify-center mx-auto mb-4 rounded-2xl transition-all duration-500" :class="parseInt(index) <= activeStep ? 'bg-gradient-to-br from-emerald-500 to-cyan-500 text-white shadow-lg' : 'bg-slate-100 dark:bg-slate-800 text-slate-400'">
                                        <component :is="step.icon" class="w-8 h-8" />
                                    </div>
                                    <h3 class="font-bold text-lg mb-2 text-slate-800 dark:text-slate-200">{{ step.title }}</h3>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">{{ step.desc }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-center mt-12">
                            <button @click="animateProcess" :disabled="simulationRunning" class="px-8 py-4 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-2xl shadow-xl shadow-emerald-600/25 transition-all transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed group">
                                <span v-if="!simulationRunning" class="flex items-center gap-2">
                                    Simulasi Alur Proses
                                    <ZapIcon class="w-4 h-4 group-hover:animate-pulse" />
                                </span>
                                <span v-else class="flex items-center gap-2">
                                    Mensimulasikan...
                                    <span class="animate-spin inline-block w-4 h-4 border-2 border-white/30 border-t-white rounded-full"></span>
                                </span>
                            </button>
                        </div>
                    </div>

                    <div class="p-8 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[32px] max-w-4xl mx-auto shadow-2xl transition-all duration-700">
                        <div class="flex flex-col md:flex-row items-center md:items-start gap-8">
                            <div class="p-6 bg-emerald-500 rounded-[28px] shadow-lg shadow-emerald-500/25 shrink-0">
                                <component :is="stepsData[activeStep].icon" class="w-10 h-10 text-white" />
                            </div>
                            <div class="text-center md:text-left">
                                <h2 class="text-3xl font-bold mb-4 text-slate-900 dark:text-white">{{ stepsData[activeStep].title }}</h2>
                                <p class="text-slate-500 dark:text-slate-400 leading-relaxed text-lg mb-8">{{ stepsData[activeStep].desc }}</p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div v-for="(stat, sIdx) in stepsData[activeStep].stats" :key="sIdx" class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/50 flex items-center gap-3">
                                        <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]"></div>
                                        <span class="text-sm font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest">{{ stat }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else-if="activeTab === 'sop'" class="animate-in fade-in duration-700 print:block">
                    <InventoryFlowGuide />
                </div>

                <div v-else-if="activeTab === 'manual'" class="max-w-5xl mx-auto space-y-8 print:w-full print:max-w-none">
                    <div class="text-center mb-8 animate-in fade-in duration-700">
                        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-cyan-500/10 border border-cyan-500/20 text-cyan-400 text-xs font-bold uppercase tracking-widest mb-4 print:hidden">
                            <InfoIcon class="w-4 h-4" />
                            Comprehensive Guide
                        </div>
                        <h1 class="text-4xl font-bold mb-4 text-slate-900 dark:text-white print:text-black">Panduan Penggunaan Modul Inventory</h1>
                        <p class="text-slate-500 dark:text-slate-400 print:text-slate-600">Klik section untuk membuka detail langkah penggunaan tiap menu.</p>
                    </div>

                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm sticky top-4 z-20 animate-in slide-in-from-top-3 duration-500 print:hidden">
                        <div class="flex flex-wrap items-center gap-2">
                            <button @click="expandAll" class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 rounded-lg hover:bg-emerald-100 transition-colors">Buka Semua</button>
                            <button @click="collapseAll" class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider bg-slate-100 dark:bg-slate-800 text-slate-500 rounded-lg hover:bg-slate-200 transition-colors">Tutup Semua</button>
                            <div class="w-px h-5 bg-slate-200 dark:bg-slate-700 mx-1"></div>
                            <button
                                v-for="s in manualSections"
                                :key="s.id"
                                @click="scrollToSection(s.id)"
                                class="px-2.5 py-1.5 text-[10px] font-bold rounded-lg transition-all hover:scale-105"
                                :class="expandedSections.has(s.id) ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700' : 'bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-slate-600'"
                            >
                                {{ s.num }}
                            </button>
                        </div>
                    </div>

                    <div class="space-y-4 print:space-y-8">
                        <div v-for="(section, idx) in manualSections" :key="section.id" :id="'manual-' + section.id" class="animate-in slide-in-from-bottom-3 duration-500" :style="{ animationDelay: idx * 40 + 'ms' }">
                            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[20px] overflow-hidden transition-all duration-300 hover:shadow-lg print:border-none print:shadow-none print:break-inside-avoid" :class="expandedSections.has(section.id) ? 'shadow-xl ring-1 ring-emerald-500/20' : ''">
                                <button @click="toggleSection(section.id)" class="w-full p-5 flex items-center gap-4 text-left group transition-colors print:hidden" :class="expandedSections.has(section.id) ? 'bg-slate-50/50 dark:bg-slate-800/30' : 'hover:bg-slate-50 dark:hover:bg-slate-800/20'">
                                    <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 transition-all duration-300" :class="expandedSections.has(section.id) ? 'bg-emerald-500 text-white shadow-lg' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 group-hover:bg-emerald-500 group-hover:text-white'">
                                        <component :is="section.icon" class="w-6 h-6" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ section.num }}. {{ section.title }}</h3>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ section.desc }}</p>
                                    </div>
                                    <ChevronDownIcon class="w-5 h-5 text-slate-400 shrink-0 transition-transform duration-300" :class="expandedSections.has(section.id) ? 'rotate-180' : ''" />
                                </button>

                                <div class="hidden print:flex items-center gap-3 mb-4 border-b border-black pb-2">
                                    <h3 class="text-xl font-bold text-black">{{ section.num }}. {{ section.title }}</h3>
                                </div>

                                <Transition name="accordion">
                                    <div v-if="expandedSections.has(section.id)" class="px-5 pb-5 pt-0 border-t border-slate-100 dark:border-slate-800 print:border-none print:p-0">
                                        <div class="pt-4">
                                            <ManualSectionContent :sectionId="section.id" />
                                        </div>
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
.accordion-enter-active {
    transition: all 0.3s ease-out;
    max-height: 2000px;
    overflow: hidden;
}
.accordion-leave-active {
    transition: all 0.2s ease-in;
    max-height: 2000px;
    overflow: hidden;
}
.accordion-enter-from,
.accordion-leave-to {
    opacity: 0;
    max-height: 0;
}

@media print {
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

