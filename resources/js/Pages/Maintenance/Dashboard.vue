<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import QrcodeVue from 'qrcode.vue';
import { 
    WrenchScrewdriverIcon,
    ExclamationTriangleIcon,
    CalendarDaysIcon,
    CogIcon,
    QrCodeIcon,
    CpuChipIcon,
    PrinterIcon,
    XMarkIcon,
    BanknotesIcon,
    PresentationChartBarIcon,
    ClockIcon,
    ArrowPathIcon,
    SparklesIcon,
    CheckCircleIcon,
    SunIcon,
    MoonIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    stats: Object,
    machines: Array,
    recent_breakdowns: Array,
    upcoming_schedules: Array
});

const activeTab = ref(new URLSearchParams(window.location.search).get('tab') || 'telemetry');
const search = ref('');
const statusFilter = ref('all');

const filteredMachines = computed(() => {
    return props.machines.filter(machine => {
        const matchesSearch = machine.name.toLowerCase().includes(search.value.toLowerCase()) || 
                              machine.code.toLowerCase().includes(search.value.toLowerCase());
        const matchesStatus = statusFilter.value === 'all' || machine.status === statusFilter.value;
        return matchesSearch && matchesStatus;
    });
});

// QR Code printing modal
const showQrModal = ref(false);
const selectedMachineForQr = ref(null);

const openQrModal = (machine) => {
    selectedMachineForQr.value = machine;
    showQrModal.value = true;
};

const closeQrModal = () => {
    showQrModal.value = false;
    selectedMachineForQr.value = null;
};

const printQrCode = () => {
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
            <head>
                <title>Print QR Code - ${selectedMachineForQr.value.name}</title>
                <style>
                    body {
                        font-family: monospace;
                        text-align: center;
                        padding: 40px;
                        background: white;
                        color: black;
                    }
                    .qr-container {
                        border: 3px double black;
                        padding: 30px;
                        display: inline-block;
                        border-radius: 10px;
                    }
                    .machine-title {
                        font-size: 24px;
                        font-weight: bold;
                        margin-bottom: 5px;
                        text-transform: uppercase;
                    }
                    .machine-code {
                        font-size: 16px;
                        color: #555;
                        margin-bottom: 20px;
                        letter-spacing: 2px;
                    }
                    .instructions {
                        margin-top: 20px;
                        font-size: 12px;
                        color: #333;
                    }
                    canvas, img {
                        display: block;
                        margin: 0 auto;
                    }
                </style>
            </head>
            <body>
                <div class="qr-container">
                    <div class="machine-title">${selectedMachineForQr.value.name}</div>
                    <div class="machine-code">CODE: ${selectedMachineForQr.value.code}</div>
                    <div style="display: flex; justify-content: center; margin: 20px 0;">
                        ${document.querySelector('#qr-print-area canvas').outerHTML}
                    </div>
                    <div class="instructions">SCAN UNTUK MELAPORKAN KERUSAKAN (BREAKDOWN)</div>
                </div>
                <script>
                    window.onload = function() {
                        window.print();
                        setTimeout(function() { window.close(); }, 500);
                    };
                <\/script>
            </body>
        </html>
    `);
    printWindow.document.close();
};

// Health color categories
const healthColorClass = (score) => {
    if (score >= 80) return 'text-emerald-600 dark:text-emerald-400';
    if (score >= 50) return 'text-amber-600 dark:text-amber-400';
    return 'text-rose-600 dark:text-rose-400';
};

const healthColorProgressClass = (score) => {
    if (score >= 80) return 'stroke-emerald-500';
    if (score >= 50) return 'stroke-amber-500';
    return 'stroke-rose-500';
};

// Circumference of radial gauge (radius = 26)
const strokeDasharray = 2 * Math.PI * 26; // ~163.36
const strokeDashoffset = (score) => strokeDasharray * (1 - score / 100);

// Status Badge styles
const getStatusBadgeClass = (status) => {
    return {
        'active': 'bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/30 text-emerald-700 dark:text-emerald-400',
        'breakdown': 'bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/30 text-rose-700 dark:text-rose-400 animate-pulse font-black shadow-[0_0_10px_rgba(244,63,94,0.1)] dark:shadow-[0_0_10px_rgba(244,63,94,0.2)]',
        'maintenance': 'bg-sky-50 dark:bg-sky-500/10 border border-sky-200 dark:border-sky-500/30 text-sky-700 dark:text-sky-400',
        'inactive': 'bg-slate-100 dark:bg-slate-500/10 border border-slate-200 dark:border-slate-500/30 text-slate-650 dark:text-slate-400'
    }[status];
};

const getStatusText = (status) => {
    return {
        'active': 'NORMAL / RUNNING',
        'breakdown': 'BREAKDOWN / DOWN',
        'maintenance': 'UNDER REPAIR',
        'inactive': 'INACTIVE'
    }[status];
};

const formatRupiah = (value) => {
    return 'Rp ' + numberFormat(value, 0, ',', '.');
};

const numberFormat = (value, decimals = 2) => {
    const num = parseFloat(value);
    return isNaN(num) ? '0' : num.toLocaleString('id-ID', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
};

const getPredictionColor = (machine) => {
    if (machine.predicted_failure === '-') return 'text-slate-400 dark:text-slate-600';
    if (!machine.predicted_failure_raw) return 'text-slate-600 dark:text-slate-300';
    
    const predictedDate = new Date(machine.predicted_failure_raw);
    const today = new Date();
    const diffTime = predictedDate - today;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays < 0) return 'text-rose-600 dark:text-rose-500 animate-pulse font-bold';
    if (diffDays <= 7) return 'text-amber-600 dark:text-amber-500 font-bold';
    return 'text-cyan-600 dark:text-cyan-400';
};

const getRecentLogStatusClass = (status) => {
    return {
        'open': 'bg-rose-50 dark:bg-rose-500/10 border-rose-200 dark:border-rose-500/20 text-rose-700 dark:text-rose-400',
        'in_progress': 'bg-amber-50 dark:bg-amber-500/10 border-amber-200 dark:border-amber-500/20 text-amber-700 dark:text-amber-400',
        'resolved': 'bg-emerald-50 dark:bg-emerald-500/10 border-emerald-200 dark:border-emerald-500/20 text-emerald-700 dark:text-emerald-400',
        'cancelled': 'bg-slate-100 dark:bg-slate-500/10 border-slate-200 dark:border-slate-500/20 text-slate-600 dark:text-slate-400'
    }[status.toLowerCase()] || 'text-slate-500 dark:text-slate-400 border-slate-200 dark:border-white/10';
};

const getTcoRatioPercentage = (tco) => {
    const maintenanceCost = tco.spareparts_cost + tco.labor_cost;
    if (tco.purchase_price <= 0) return 0;
    return (maintenanceCost / tco.purchase_price) * 100;
};

const getTcoRatioColor = (tco) => {
    const pct = getTcoRatioPercentage(tco);
    if (pct >= 50) return 'bg-rose-500 shadow-[0_0_10px_rgba(244,63,94,0.5)]';
    if (pct >= 25) return 'bg-amber-500';
    return 'bg-cyan-500';
};

// AI Predictive Advisor state
import axios from 'axios';
const aiLoading = ref(false);
const aiResult = ref(null);
const aiError = ref(null);
const selectedPartsForPr = ref([]);
const prLoading = ref(false);

const runAiDiagnostics = async () => {
    aiLoading.value = true;
    aiError.value = null;
    aiResult.value = null;
    selectedPartsForPr.value = [];
    
    try {
        const response = await axios.post(route('maintenance.predictive.advisor'));
        if (response.data?.success) {
            aiResult.value = response.data.data;
            // Pre-select all recommendations
            selectedPartsForPr.value = (response.data.data?.sparepart_recommendations || [])
                .map(item => item.part_number);
        } else {
            aiError.value = response.data?.message || 'Gagal memproses diagnosis AI.';
        }
    } catch (e) {
        aiError.value = e.response?.data?.message || e.message;
    } finally {
        aiLoading.value = false;
    }
};

const toggleSelectPart = (partNumber) => {
    if (selectedPartsForPr.value.includes(partNumber)) {
        selectedPartsForPr.value = selectedPartsForPr.value.filter(pn => pn !== partNumber);
    } else {
        selectedPartsForPr.value.push(partNumber);
    }
};

const createPrFromAi = async () => {
    if (selectedPartsForPr.value.length === 0) {
        alert('Pilih minimal satu suku cadang untuk diajukan PR!');
        return;
    }
    
    prLoading.value = true;
    
    const itemsToSubmit = (aiResult.value?.sparepart_recommendations || [])
        .filter(item => selectedPartsForPr.value.includes(item.part_number))
        .map(item => ({
            part_number: item.part_number,
            recommended_qty: item.recommended_qty,
            justification: item.justification
        }));
        
    try {
        await axios.post(route('maintenance.predictive.create-pr'), {
            items: itemsToSubmit
        });
        
        alert('Draft Purchase Request berhasil dibuat! Silakan periksa modul Purchasing.');
        aiResult.value = null;
        activeTab.value = 'telemetry';
        
        router.reload({
            only: ['stats', 'machines']
        });
    } catch (e) {
        alert('Gagal membuat PR: ' + (e.response?.data?.message || e.message));
    } finally {
        prLoading.value = false;
    }
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
    <Head title="Maintenance Dashboard" />

    <AppLayout title="Maintenance Analytics" :render-header="false">
        <div class="min-h-screen bg-slate-50 dark:bg-[#050510] relative overflow-hidden font-mono text-slate-800 dark:text-cyan-50 selection:bg-rose-500/30 transition-colors duration-300">
            
            <!-- Matrix Style Cyber Grid -->
            <div class="fixed inset-0 z-0 pointer-events-none">
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/5 to-slate-100 dark:from-indigo-955/20 dark:to-[#050510]"></div>
                <div class="perspective-grid absolute inset-0 opacity-[0.05] dark:opacity-10"></div>
            </div>

            <div class="relative z-10 p-6 space-y-6 max-w-7xl mx-auto">
                
                <!-- Page Title Header -->
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-slate-200 dark:border-white/10 pb-4">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-0.5 text-[10px] bg-cyan-500/10 border border-cyan-500/20 rounded text-cyan-705 dark:text-cyan-400 tracking-[0.2em] uppercase animate-pulse">
                                SYSTEM HEALTH ACTIVE
                            </span>
                            <span class="px-2 py-0.5 text-[10px] bg-slate-200 dark:bg-white/5 border border-slate-300 dark:border-white/10 rounded text-slate-500 dark:text-slate-400 tracking-[0.2em] uppercase">MNT.PREDICT.V3</span>
                        </div>
                        <h1 class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-cyan-605 via-slate-800 to-indigo-650 dark:from-cyan-400 dark:via-white dark:to-indigo-400 tracking-widest uppercase dark:glow-text">
                            MAINTENANCE ANALYTICS
                        </h1>
                    </div>
                    
                    <div class="flex items-center gap-6">
                        
                    </div>
                </div>

                <!-- Navigation Tabs -->
                <div class="flex gap-2 border-b border-slate-200 dark:border-white/10 pb-1 flex-wrap">
                    <button 
                        @click="activeTab = 'telemetry'"
                        class="px-4 py-2 text-xs font-black uppercase tracking-wider transition-all border-b-2 cursor-pointer"
                        :class="activeTab === 'telemetry' ? 'border-cyan-500 text-cyan-600 dark:text-cyan-400 dark:glow-text' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                    >
                        [01] Live Telemetry
                    </button>
                    <button 
                        @click="activeTab = 'tco'"
                        class="px-4 py-2 text-xs font-black uppercase tracking-wider transition-all border-b-2 cursor-pointer"
                        :class="activeTab === 'tco' ? 'border-cyan-500 text-cyan-600 dark:text-cyan-400 dark:glow-text' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                    >
                        [02] Analisis Biaya (TCO)
                    </button>
                    <button 
                        @click="activeTab = 'predictive'"
                        class="px-4 py-2 text-xs font-black uppercase tracking-wider transition-all border-b-2 cursor-pointer flex items-center gap-1.5"
                        :class="activeTab === 'predictive' ? 'border-cyan-500 text-cyan-600 dark:text-cyan-400 dark:glow-text' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                    >
                        <span>[03] AI Predictive Advisor</span>
                        <span class="px-1 text-[8px] bg-indigo-500/10 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-500/20 rounded font-black tracking-normal uppercase animate-pulse">Smart</span>
                    </button>
                </div>

                <!-- Tab 1: Live Telemetry -->
                <div v-if="activeTab === 'telemetry'" class="space-y-6 animate-fade-in">
                    <!-- KPI stats panel -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- KPI 1: Rerata Kesehatan -->
                        <div class="hud-panel bg-white/70 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl p-4 flex items-center justify-between border-l-4 border-l-emerald-500 shadow-sm dark:shadow-[0_0_20px_rgba(16,185,129,0.05)]">
                            <div>
                                <p class="text-[10px] text-slate-500 uppercase tracking-widest font-bold mb-1">Rata-rata Kesehatan</p>
                                <h3 class="text-2xl font-black text-emerald-600 dark:text-emerald-400 dark:glow-text">{{ stats.average_health }}%</h3>
                                <p class="text-[9px] text-slate-500 dark:text-slate-400 mt-1">Skor agregat semua mesin</p>
                            </div>
                            <div class="p-3 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 rounded-lg">
                                <CpuChipIcon class="h-6 w-6 text-emerald-605 dark:text-emerald-400" />
                            </div>
                        </div>

                        <!-- KPI 2: Active Breakdown -->
                        <div class="hud-panel bg-white/70 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl p-4 flex items-center justify-between border-l-4 border-l-rose-500 shadow-sm dark:shadow-[0_0_20px_rgba(244,63,94,0.05)]">
                            <div>
                                <p class="text-[10px] text-slate-500 uppercase tracking-widest font-bold mb-1">Breakdown Aktif</p>
                                <h3 class="text-2xl font-black text-rose-600 dark:text-rose-400 dark:glow-text animate-pulse">{{ stats.active_breakdowns }} UNIT</h3>
                                <p class="text-[9px] text-slate-500 dark:text-slate-400 mt-1">Butuh respon segera</p>
                            </div>
                            <div class="p-3 bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/20 rounded-lg">
                                <ExclamationTriangleIcon class="h-6 w-6 text-rose-600 dark:text-rose-400 animate-bounce" />
                            </div>
                        </div>

                        <!-- KPI 3: PM Overdue -->
                        <div class="hud-panel bg-white/70 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl p-4 flex items-center justify-between border-l-4 border-l-amber-500 shadow-sm dark:shadow-[0_0_20px_rgba(245,158,11,0.05)]">
                            <div>
                                <p class="text-[10px] text-slate-500 uppercase tracking-widest font-bold mb-1">PM Overdue</p>
                                <h3 class="text-2xl font-black text-amber-600 dark:text-amber-400 dark:glow-text">{{ stats.overdue_pms }} TUGAS</h3>
                                <p class="text-[9px] text-slate-500 dark:text-slate-400 mt-1">Jadwal perawatan tertunda</p>
                            </div>
                            <div class="p-3 bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 rounded-lg">
                                <CalendarDaysIcon class="h-6 w-6 text-amber-600 dark:text-amber-400" />
                            </div>
                        </div>

                        <!-- KPI 4: Critical Parts -->
                        <div class="hud-panel bg-white/70 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl p-4 flex items-center justify-between border-l-4 border-l-sky-500 shadow-sm dark:shadow-[0_0_20px_rgba(14,165,233,0.05)]">
                            <div>
                                <p class="text-[10px] text-slate-500 uppercase tracking-widest font-bold mb-1">Spareparts Kritis</p>
                                <h3 class="text-2xl font-black text-sky-600 dark:text-sky-400 dark:glow-text">{{ stats.critical_spareparts }} ITEM</h3>
                                <p class="text-[9px] text-slate-505 dark:text-slate-400 mt-1">Stok di bawah batas minimal</p>
                            </div>
                            <div class="p-3 bg-sky-50 dark:bg-sky-500/10 border border-sky-200 dark:border-sky-500/20 rounded-lg">
                                <CogIcon class="h-6 w-6 text-sky-600 dark:text-sky-400" />
                            </div>
                        </div>
                    </div>

                    <!-- Search and Filters -->
                    <div class="flex flex-col md:flex-row gap-4 items-center justify-between bg-white/75 dark:bg-slate-900/40 p-4 rounded-xl border border-slate-200 dark:border-white/5 shadow-sm dark:shadow-none">
                        <div class="w-full md:max-w-md relative">
                            <input 
                                v-model="search"
                                type="text" 
                                placeholder="Cari mesin berdasarkan nama atau kode..." 
                                class="w-full bg-white dark:bg-black/40 border border-slate-200 dark:border-white/10 rounded-lg px-4 py-2 text-sm text-slate-800 dark:text-cyan-200 placeholder-slate-400 dark:placeholder-slate-500 outline-none focus:border-cyan-500 focus:shadow-sm dark:focus:shadow-[0_0_15px_rgba(6,182,212,0.2)] transition-all font-mono"
                            />
                        </div>
                        <div class="flex gap-2 w-full md:w-auto">
                            <span class="text-xs text-slate-500 dark:text-slate-400 self-center hidden md:inline">Filter:</span>
                            <select 
                                v-model="statusFilter"
                                class="bg-white dark:bg-[#0f172a] border border-slate-200 dark:border-white/10 rounded-lg px-3 py-2 text-xs text-slate-800 dark:text-cyan-200 outline-none focus:border-cyan-500 flex-1 md:flex-none"
                            >
                                <option value="all" class="bg-white dark:bg-[#0f172a] text-slate-800 dark:text-cyan-200">Semua Status</option>
                                <option value="active" class="bg-white dark:bg-[#0f172a] text-slate-800 dark:text-cyan-200">Normal / Running</option>
                                <option value="breakdown" class="bg-white dark:bg-[#0f172a] text-slate-800 dark:text-cyan-200">Breakdown</option>
                                <option value="maintenance" class="bg-white dark:bg-[#0f172a] text-slate-800 dark:text-cyan-200">Under Repair</option>
                                <option value="inactive" class="bg-white dark:bg-[#0f172a] text-slate-800 dark:text-cyan-200">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <!-- Machines Grid -->
                    <div class="space-y-4">
                        <h3 class="text-xs font-bold text-slate-700 dark:text-cyan-400 uppercase tracking-widest flex items-center gap-2">
                            <CpuChipIcon class="h-4 w-4" /> Telemetry Status Mesin ({{ filteredMachines.length }} Unit)
                        </h3>
                        
                        <div v-if="filteredMachines.length === 0" class="hud-panel bg-white/75 dark:bg-[#0a0a16]/80 border border-slate-200 dark:border-white/10 p-8 text-center text-slate-500 text-sm">
                            Tidak ada mesin yang cocok dengan kriteria filter.
                        </div>

                        <div v-else class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div 
                                v-for="machine in filteredMachines" 
                                :key="machine.id"
                                class="hud-panel bg-white/75 dark:bg-[#0a0a16]/80 border border-slate-200 dark:border-white/10 rounded-xl p-5 relative overflow-hidden group hover:border-cyan-500/30 transition-all duration-300 shadow-sm dark:shadow-[0_0_20px_rgba(0,0,0,0.5)]"
                            >
                                <!-- Scan Glow Border on Hover -->
                                <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-cyan-455 to-transparent scale-x-0 group-hover:scale-x-100 transition-transform duration-500"></div>

                                <!-- Header -->
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <h4 class="text-lg font-black text-slate-800 dark:text-white group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors uppercase tracking-wider">{{ machine.name }}</h4>
                                        <span class="text-[10px] text-slate-450 dark:text-slate-500 tracking-wider font-mono">ID: {{ machine.code }}</span>
                                    </div>
                                    <span class="px-2 py-0.5 rounded text-[8px] font-bold tracking-widest border" :class="getStatusBadgeClass(machine.status)">
                                        {{ getStatusText(machine.status) }}
                                    </span>
                                </div>

                                <!-- Telemetry Data -->
                                <div class="grid grid-cols-12 gap-4 items-center py-2 border-y border-slate-200 dark:border-white/5 my-4">
                                    <!-- Health score radial gauge -->
                                    <div class="col-span-4 flex flex-col items-center justify-center relative">
                                        <div class="relative w-16 h-16 flex items-center justify-center">
                                            <svg class="w-16 h-16 transform -rotate-90">
                                                <circle cx="32" cy="32" r="26" stroke="currentColor" class="text-slate-100 dark:text-[#0f172a]" stroke-width="4.5" fill="transparent" />
                                                <circle 
                                                    cx="32" 
                                                    cy="32" 
                                                    r="26" 
                                                    stroke="currentColor" 
                                                    stroke-width="4.5" 
                                                    :stroke-dasharray="strokeDasharray"
                                                    :stroke-dashoffset="strokeDashoffset(machine.health_score)"
                                                    :class="healthColorProgressClass(machine.health_score)"
                                                    fill="transparent"
                                                    stroke-linecap="round" 
                                                />
                                            </svg>
                                            <span class="absolute text-xs font-bold text-slate-800 dark:text-white tracking-tighter" :class="healthColorClass(machine.health_score)">
                                                {{ machine.health_score }}%
                                            </span>
                                        </div>
                                        <span class="text-[9px] text-slate-500 mt-1 uppercase font-bold tracking-wider">Health</span>
                                    </div>

                                    <!-- Details list -->
                                    <div class="col-span-8 space-y-2 text-xs">
                                        <div class="flex justify-between">
                                            <span class="text-slate-500">Jam Kerja:</span>
                                            <span class="text-slate-700 dark:text-slate-300 font-bold font-mono">{{ numberFormat(machine.runtime_hours, 1) }} jam</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-slate-500">MTBF:</span>
                                            <span class="text-slate-700 dark:text-slate-300 font-bold" :class="machine.mtbf.includes('tidak') ? 'text-slate-400 dark:text-slate-600' : 'text-cyan-600 dark:text-cyan-400'">{{ machine.mtbf }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-slate-500">Kegagalan Prediktif:</span>
                                            <span class="font-bold" :class="getPredictionColor(machine)">{{ machine.predicted_failure }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Quick Action Buttons -->
                                <div class="flex items-center justify-between pt-2">
                                    <Link 
                                        href="/maintenance/breakdown"
                                        class="text-[10px] text-slate-500 hover:text-cyan-600 dark:text-slate-400 dark:hover:text-cyan-400 flex items-center gap-1 transition-colors font-bold"
                                    >
                                        <WrenchScrewdriverIcon class="h-3 w-3" /> Log Perawatan
                                    </Link>
                                    <button 
                                        @click="openQrModal(machine)"
                                        class="px-2 py-1 bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 text-slate-700 dark:text-white border border-slate-200 dark:border-white/10 rounded flex items-center gap-1.5 text-[10px] uppercase tracking-wider transition-colors font-bold shadow-sm dark:shadow-none"
                                    >
                                        <QrCodeIcon class="h-3.5 w-3.5" /> CETAK QR
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Urgent activity checklist and breakdowns log -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 pt-6">
                        <!-- Urgent Breakdown Incidents -->
                        <div class="hud-panel bg-white/75 dark:bg-[#0a0a16]/80 border border-slate-200 dark:border-white/10 rounded-xl p-5 space-y-4 shadow-sm dark:shadow-none">
                            <h3 class="text-sm font-bold text-rose-600 dark:text-rose-400 uppercase tracking-widest flex items-center gap-2">
                                <ExclamationTriangleIcon class="h-4 w-4 animate-pulse" /> Insiden Kerusakan Terbaru (Breakdown)
                            </h3>
                            <div v-if="recent_breakdowns.length === 0" class="text-slate-550 text-xs py-4 text-center">
                                Tidak ada insiden kerusakan terekam baru-baru ini.
                            </div>
                            <div v-else class="space-y-3">
                                <div 
                                    v-for="log in recent_breakdowns" 
                                    :key="log.id"
                                    class="bg-slate-50 dark:bg-black/30 border border-slate-200 dark:border-white/5 rounded p-3 flex flex-col md:flex-row justify-between items-start md:items-center gap-2 hover:bg-slate-100 dark:hover:bg-white/5 transition-colors shadow-sm dark:shadow-none"
                                >
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-800 dark:text-white text-xs uppercase">{{ log.machine_name }} ({{ log.machine_code }})</span>
                                            <span class="px-1.5 py-0.5 rounded text-[8px] border font-bold" :class="getRecentLogStatusClass(log.status)">
                                                {{ log.status.toUpperCase() }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-550 dark:text-slate-400 line-clamp-1 italic">"{{ log.description }}"</p>
                                    </div>
                                    <div class="text-left md:text-right text-[10px] text-slate-500 font-mono">
                                        <span>{{ log.started_at }}</span>
                                        <span class="hidden md:block text-slate-550 dark:text-slate-600">Teknisi: {{ log.technician }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Upcoming Schedules Checklist -->
                        <div class="hud-panel bg-white/75 dark:bg-[#0a0a16]/80 border border-slate-200 dark:border-white/10 rounded-xl p-5 space-y-4 shadow-sm dark:shadow-none">
                            <h3 class="text-sm font-bold text-cyan-600 dark:text-cyan-400 uppercase tracking-widest flex items-center gap-2">
                                <CalendarDaysIcon class="h-4 w-4" /> Checklist Preventive Maintenance (PM)
                            </h3>
                            <div v-if="upcoming_schedules.length === 0" class="text-slate-550 text-xs py-4 text-center">
                                Tidak ada jadwal PM aktif berikutnya.
                            </div>
                            <div v-else class="space-y-3">
                                <div 
                                    v-for="sched in upcoming_schedules" 
                                    :key="sched.id"
                                    class="bg-slate-50 dark:bg-black/30 border border-slate-200 dark:border-white/5 rounded p-3 flex justify-between items-center hover:bg-slate-100 dark:hover:bg-white/5 transition-colors shadow-sm dark:shadow-none"
                                >
                                    <div>
                                        <h4 class="font-bold text-slate-800 dark:text-white text-xs uppercase">{{ sched.machine_name }}</h4>
                                        <p class="text-[10px] text-slate-500 dark:text-slate-400 font-mono">{{ sched.task_name }}</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="px-2 py-0.5 rounded text-[9px] font-bold border" :class="sched.is_overdue ? 'bg-rose-50 dark:bg-rose-500/10 border-rose-200 dark:border-rose-500/20 text-rose-700 dark:text-rose-400' : 'bg-cyan-50 dark:bg-cyan-500/10 border-cyan-200 dark:border-cyan-500/20 text-cyan-705 dark:text-cyan-400'">
                                            {{ sched.next_due_date }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 2: Total Cost of Ownership (TCO) -->
                <div v-if="activeTab === 'tco'" class="space-y-6 animate-fade-in">
                    <!-- TCO KPI Cards -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- KPI 1: Total Investasi -->
                        <div class="hud-panel bg-white/70 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl p-4 flex items-center justify-between border-l-4 border-l-cyan-500 shadow-sm">
                            <div>
                                <p class="text-[10px] text-slate-500 uppercase tracking-widest font-bold mb-1">Investasi Pembelian Mesin</p>
                                <h3 class="text-lg font-black text-cyan-600 dark:text-cyan-400 dark:glow-text">{{ formatRupiah(stats.total_purchase_price) }}</h3>
                                <p class="text-[9px] text-slate-500 dark:text-slate-400 mt-1">Nilai perolehan awal mesin</p>
                            </div>
                            <div class="p-3 bg-cyan-50 dark:bg-cyan-500/10 border border-cyan-200 dark:border-cyan-500/20 rounded-lg">
                                <BanknotesIcon class="h-6 w-6 text-cyan-605 dark:text-cyan-400" />
                            </div>
                        </div>

                        <!-- KPI 2: Total Biaya Spareparts -->
                        <div class="hud-panel bg-white/70 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl p-4 flex items-center justify-between border-l-4 border-l-amber-500 shadow-sm">
                            <div>
                                <p class="text-[10px] text-slate-500 uppercase tracking-widest font-bold mb-1">Total Biaya Suku Cadang</p>
                                <h3 class="text-lg font-black text-amber-600 dark:text-amber-400 dark:glow-text">{{ formatRupiah(stats.total_spareparts_cost) }}</h3>
                                <p class="text-[9px] text-slate-500 dark:text-slate-400 mt-1">Konsumsi spareparts dari gudang</p>
                            </div>
                            <div class="p-3 bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 rounded-lg">
                                <CogIcon class="h-6 w-6 text-amber-605 dark:text-amber-400" />
                            </div>
                        </div>

                        <!-- KPI 3: Total Biaya Tenaga Kerja -->
                        <div class="hud-panel bg-white/70 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl p-4 flex items-center justify-between border-l-4 border-l-sky-500 shadow-sm">
                            <div>
                                <p class="text-[10px] text-slate-500 uppercase tracking-widest font-bold mb-1">Biaya Tenaga Kerja (Labor)</p>
                                <h3 class="text-lg font-black text-sky-600 dark:text-sky-400 dark:glow-text">{{ formatRupiah(stats.total_labor_cost) }}</h3>
                                <p class="text-[9px] text-slate-500 dark:text-slate-400 mt-1">Durasi perbaikan × Rp 50.000/jam</p>
                            </div>
                            <div class="p-3 bg-sky-50 dark:bg-sky-500/10 border border-sky-200 dark:border-sky-500/20 rounded-lg">
                                <ClockIcon class="h-6 w-6 text-sky-605 dark:text-sky-400" />
                            </div>
                        </div>

                        <!-- KPI 4: Total TCO Seluruhnya -->
                        <div class="hud-panel bg-white/70 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl p-4 flex items-center justify-between border-l-4 border-l-emerald-500 shadow-sm">
                            <div>
                                <p class="text-[10px] text-slate-500 uppercase tracking-widest font-bold mb-1">Akumulasi TCO Aset</p>
                                <h3 class="text-lg font-black text-emerald-600 dark:text-emerald-400 dark:glow-text">{{ formatRupiah(stats.total_tco_all) }}</h3>
                                <p class="text-[9px] text-slate-505 dark:text-slate-400 mt-1">Investasi + Total Perawatan</p>
                            </div>
                            <div class="p-3 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 rounded-lg">
                                <PresentationChartBarIcon class="h-6 w-6 text-emerald-605 dark:text-emerald-400" />
                            </div>
                        </div>
                    </div>

                    <!-- TCO Detailed Table -->
                    <div class="hud-panel bg-white/75 dark:bg-[#0a0a16]/80 border border-slate-200 dark:border-white/10 rounded-xl p-5 space-y-4 shadow-sm dark:shadow-none">
                        <h3 class="text-xs font-bold text-slate-700 dark:text-cyan-400 uppercase tracking-widest flex items-center gap-2">
                            <PresentationChartBarIcon class="h-4 w-4" /> Tabel Rincian Total Cost of Ownership (TCO) Mesin
                        </h3>

                        <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-white/10">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-slate-100 dark:bg-white/5 text-[10px] uppercase tracking-wider text-slate-500 dark:text-slate-400 font-bold">
                                    <tr>
                                        <th class="p-4">Mesin</th>
                                        <th class="p-4">Kode</th>
                                        <th class="p-4 text-right">Harga Beli</th>
                                        <th class="p-4 text-right">Biaya Spareparts</th>
                                        <th class="p-4 text-right">Biaya Labor</th>
                                        <th class="p-4 text-right">Total TCO</th>
                                        <th class="p-4 text-center">Rasio Biaya Perawatan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-white/5 bg-white/30 dark:bg-slate-900/50 text-slate-700 dark:text-slate-300">
                                    <tr v-for="machine in machines" :key="machine.id" class="hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                                        <td class="p-4 font-bold text-slate-800 dark:text-white uppercase">{{ machine.name }}</td>
                                        <td class="p-4 font-mono text-xs text-slate-500 dark:text-slate-400">{{ machine.code }}</td>
                                        <td class="p-4 text-right text-slate-700 dark:text-slate-300 font-mono">{{ formatRupiah(machine.tco.purchase_price) }}</td>
                                        <td class="p-4 text-right text-amber-600 dark:text-amber-400 font-mono">{{ formatRupiah(machine.tco.spareparts_cost) }}</td>
                                        <td class="p-4 text-right text-sky-600 dark:text-sky-400 font-mono">{{ formatRupiah(machine.tco.labor_cost) }}</td>
                                        <td class="p-4 text-right text-emerald-600 dark:text-emerald-400 font-mono font-bold">{{ formatRupiah(machine.tco.total_tco) }}</td>
                                        <td class="p-4">
                                            <div class="flex items-center gap-2 justify-center">
                                                <div class="w-24 bg-slate-200 dark:bg-white/5 rounded-full h-2 overflow-hidden border border-slate-300 dark:border-white/10">
                                                    <div 
                                                        class="h-full rounded-full transition-all"
                                                        :class="getTcoRatioColor(machine.tco)"
                                                        :style="{ width: Math.min(100, getTcoRatioPercentage(machine.tco)) + '%' }"
                                                    ></div>
                                                </div>
                                                <span class="text-[10px] font-mono font-bold text-slate-500 dark:text-slate-400">
                                                    {{ numberFormat(getTcoRatioPercentage(machine.tco), 1) }}%
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tab 3: AI Predictive Advisor -->
                <div v-if="activeTab === 'predictive'" class="space-y-6 animate-fade-in">
                    
                    <!-- Run Advisor Panel -->
                    <div v-if="!aiResult && !aiLoading" class="hud-panel bg-white/70 dark:bg-[#0a0a16]/80 p-8 text-center space-y-6 max-w-xl mx-auto my-12 border-2 border-dashed border-slate-300 dark:border-cyan-500/20 rounded-xl shadow-sm dark:shadow-none">
                        <div class="flex justify-center">
                            <div class="p-4 bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-200 dark:border-indigo-500/30 rounded-full animate-pulse text-indigo-650 dark:text-indigo-400">
                                <CpuChipIcon class="h-12 w-12" />
                            </div>
                        </div>
                        <div class="space-y-2">
                            <h3 class="text-lg font-black text-slate-800 dark:text-white uppercase tracking-widest font-mono">AI Predictive Maintenance Advisor</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed font-sans">
                                Jalankan analisis prediktif berbasis kecerdasan buatan untuk mengidentifikasi risiko kerusakan mesin pipa baja, mendiagnosis anomali performa, dan merumuskan daftar pengadaan suku cadang kritis secara otomatis.
                            </p>
                        </div>
                        
                        <div v-if="aiError" class="p-3 bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/20 rounded-xl text-xs text-rose-700 dark:text-rose-400 font-bold">
                            {{ aiError }}
                        </div>
                        
                        <button
                            type="button"
                            @click="runAiDiagnostics"
                            :disabled="aiLoading"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 px-6 py-3.5 text-xs font-black uppercase tracking-widest text-white shadow-lg shadow-indigo-500/20 transition-all active:scale-95 disabled:opacity-50 cursor-pointer"
                        >
                            <ArrowPathIcon v-if="aiLoading" class="h-4 w-4 animate-spin" />
                            <SparklesIcon v-else class="h-4 w-4" />
                            <span>{{ aiLoading ? 'Menganalisis Data Telemetri & MTBF...' : 'Mulai AI Diagnostik Pabrik' }}</span>
                        </button>
                    </div>

                    <!-- Loading State Custom HUD -->
                    <div v-if="aiLoading" class="hud-panel bg-white/70 dark:bg-[#0a0a16]/80 rounded-xl p-8 max-w-md mx-auto my-12 text-center space-y-4 border border-slate-200 dark:border-white/10">
                        <div class="flex justify-center">
                            <div class="relative w-16 h-16">
                                <div class="absolute inset-0 rounded-full border-4 border-cyan-500/20 border-t-cyan-500 dark:border-t-cyan-400 animate-spin"></div>
                                <div class="absolute inset-2 rounded-full border-4 border-indigo-500/20 border-b-indigo-500 dark:border-b-indigo-400 animate-spin-reverse"></div>
                            </div>
                        </div>
                        <div class="space-y-1 font-mono text-xs">
                            <div class="text-cyan-600 dark:text-cyan-400 font-black animate-pulse uppercase">[SYSTEM_SCANNING_TELEMETRY]</div>
                            <div class="text-[9px] text-slate-500 dark:text-slate-400">Membaca data jam jalan operasional mesin...</div>
                            <div class="text-[9px] text-slate-500 dark:text-slate-400">Mengkorelasi parameter kerusakan log terakhir...</div>
                            <div class="text-[9px] text-slate-500 dark:text-slate-400">Memeriksa level minimum stok suku cadang...</div>
                            <div class="text-indigo-600 dark:text-indigo-400 font-bold">Menghubungi Gemini AI Engine...</div>
                        </div>
                    </div>

                    <!-- Result Panel -->
                    <div v-if="aiResult && !aiLoading" class="grid grid-cols-1 xl:grid-cols-3 gap-6 animate-fade-in-up">
                        <!-- Left & Center: Diagnoses & General Insights -->
                        <div class="xl:col-span-2 space-y-6">
                            
                            <!-- General Insights -->
                            <div class="hud-panel bg-white/70 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl p-5 border-l-4 border-l-cyan-500 space-y-2 shadow-sm">
                                <h4 class="text-xs font-black uppercase tracking-widest text-cyan-600 dark:text-cyan-400 flex items-center gap-1.5">
                                    <SparklesIcon class="h-4 w-4 animate-pulse" /> Rangkuman Rekomendasi & Insights
                                </h4>
                                <p class="text-xs text-slate-700 dark:text-slate-300 leading-relaxed font-mono">
                                    {{ aiResult.general_insights }}
                                </p>
                            </div>

                            <!-- Diagnosed Machine Health Cards -->
                            <div class="space-y-4">
                                <h3 class="text-xs font-bold text-slate-700 dark:text-cyan-400 uppercase tracking-widest flex items-center gap-2">
                                    <ExclamationTriangleIcon class="h-4 w-4 text-rose-500" /> Hasil Diagnosis Mesin Kritis & Berisiko
                                </h3>
                                
                                <div v-if="!aiResult.critical_machines || aiResult.critical_machines.length === 0" class="hud-panel bg-white/75 dark:bg-[#0a0a16]/80 border border-slate-200 dark:border-white/10 rounded-xl p-6 text-center text-xs text-slate-500">
                                    Tidak ada mesin dengan kondisi kritis yang memerlukan tindakan segera.
                                </div>
                                
                                <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div v-for="m in aiResult.critical_machines" :key="m.machine_code" 
                                        class="hud-panel bg-white/75 dark:bg-[#0a0a16]/80 rounded-xl p-5 border border-slate-200 dark:border-white/5 space-y-3 relative overflow-hidden shadow-sm dark:shadow-none">
                                        <div class="absolute top-0 right-0 px-3 py-1 text-[9px] font-black uppercase rounded-bl border-l border-b border-slate-200 dark:border-white/5 bg-rose-50 dark:bg-rose-500/10 text-rose-700 dark:text-rose-400">
                                            Skor: {{ m.health_score }}%
                                        </div>
                                        
                                        <div>
                                            <h4 class="text-sm font-black text-slate-800 dark:text-white uppercase tracking-wider">{{ m.machine_name }}</h4>
                                            <span class="text-[9px] text-slate-500 dark:text-slate-550 font-mono">CODE: {{ m.machine_code }}</span>
                                        </div>
                                        
                                        <div class="text-xs space-y-2 pt-2 border-t border-slate-100 dark:border-white/5 text-slate-700 dark:text-slate-300 font-mono leading-relaxed">
                                            <div>
                                                <span class="text-rose-600 dark:text-rose-400 font-bold block uppercase text-[9px] tracking-wider mb-0.5">Diagnosis Kerusakan:</span>
                                                {{ m.diagnosis }}
                                            </div>
                                            <div>
                                                <span class="text-cyan-600 dark:text-cyan-400 font-bold block uppercase text-[9px] tracking-wider mb-0.5">Rekomendasi Tindakan:</span>
                                                {{ m.recommended_actions }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Spareparts Purchase Recommendation (PR) -->
                        <div class="hud-panel bg-white/75 dark:bg-[#0a0a16]/80 border border-slate-200 dark:border-white/10 rounded-xl p-5 space-y-4 shadow-sm dark:shadow-none">
                            <div class="border-b border-slate-200 dark:border-white/10 pb-3 flex items-center justify-between">
                                <h4 class="text-xs font-black uppercase tracking-widest text-cyan-600 dark:text-cyan-400 flex items-center gap-1.5">
                                    <CogIcon class="h-4 w-4" /> Pengadaan Suku Cadang AI
                                </h4>
                                <span class="text-[10px] text-slate-500 dark:text-slate-400 uppercase font-black font-mono">
                                    {{ selectedPartsForPr.length }} Terpilih
                                </span>
                            </div>
                            
                            <p class="text-[10px] text-slate-500 dark:text-slate-400 leading-normal font-sans">
                                Centang suku cadang di bawah untuk diajukan otomatis sebagai draf *Purchase Request* (PR) konsolidasian di modul Purchasing.
                            </p>

                            <!-- Parts Recommendations List -->
                            <div class="space-y-3 max-h-[360px] overflow-y-auto pr-1 divide-y divide-slate-100 dark:divide-white/5">
                                <div v-if="!aiResult.sparepart_recommendations || aiResult.sparepart_recommendations.length === 0" class="p-4 text-center text-xs text-slate-500 italic">
                                    Tidak ada rekomendasi suku cadang baru.
                                </div>
                                <div v-for="sp in aiResult.sparepart_recommendations" :key="sp.part_number" class="pt-3 first:pt-0 space-y-2">
                                    <div class="flex items-start gap-2.5">
                                        <input
                                            type="checkbox"
                                            :value="sp.part_number"
                                            :checked="selectedPartsForPr.includes(sp.part_number)"
                                            @change="toggleSelectPart(sp.part_number)"
                                            class="mt-0.5 rounded border-slate-300 dark:border-white/10 bg-white dark:bg-black/40 text-cyan-600 focus:ring-cyan-500 focus:ring-offset-0 focus:outline-none cursor-pointer"
                                        />
                                        <div class="text-xs flex-1">
                                            <div class="font-black text-slate-800 dark:text-white uppercase">{{ sp.name }}</div>
                                            <div class="text-[9px] text-slate-500 dark:text-slate-550 font-mono">PN: {{ sp.part_number }}</div>
                                        </div>
                                        <span class="text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded border"
                                            :class="sp.priority === 'High' ? 'text-rose-700 border-rose-200 bg-rose-50 dark:text-rose-400 dark:border-rose-500/20 dark:bg-rose-500/10' : 'text-amber-700 border-amber-250 bg-amber-50 dark:text-amber-400 dark:border-amber-500/20 dark:bg-amber-500/10'">
                                            {{ sp.recommended_qty }} PCS
                                        </span>
                                    </div>
                                    <div class="text-[10px] text-slate-500 dark:text-slate-400 pl-6 leading-relaxed font-mono">
                                        {{ sp.justification }}
                                    </div>
                                </div>
                            </div>

                            <!-- PR Generate Button -->
                            <div class="pt-3 border-t border-slate-200 dark:border-white/10">
                                <button
                                    type="button"
                                    @click="createPrFromAi"
                                    :disabled="prLoading || selectedPartsForPr.length === 0"
                                    class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-650 hover:bg-emerald-600 dark:bg-emerald-600 dark:hover:bg-emerald-500 px-4 py-3.5 text-xs font-black uppercase tracking-widest text-white shadow-lg shadow-emerald-500/10 dark:shadow-emerald-500/20 transition-all active:scale-95 disabled:opacity-50 cursor-pointer border-0"
                                >
                                    <CheckCircleIcon v-if="!prLoading" class="h-4 w-4" />
                                    <ArrowPathIcon v-else class="h-4 w-4 animate-spin" />
                                    <span>{{ prLoading ? 'Membuat Draft PR...' : 'Generate Purchase Request' }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Print QR Modal -->
            <div v-if="showQrModal && selectedMachineForQr" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
                <div class="bg-white dark:bg-[#0f172a] border border-slate-250 dark:border-cyan-500/30 p-6 rounded-xl w-full max-w-sm shadow-2xl dark:shadow-[0_0_50px_rgba(6,182,212,0.2)]">
                    <div class="flex justify-between items-center mb-4 pb-2 border-b border-slate-105 dark:border-white/10">
                        <h3 class="text-sm font-bold text-cyan-600 dark:text-cyan-400 uppercase tracking-wider flex items-center gap-2">
                            <QrCodeIcon class="h-5 w-5" /> Cetak QR Code Kerusakan
                        </h3>
                        <button @click="closeQrModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-white transition-colors">
                            <XMarkIcon class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="bg-white p-4 rounded-xl max-w-[240px] mx-auto mb-6 text-black border border-slate-200 shadow-sm">
                        <div id="qr-print-area" class="text-center font-mono">
                            <div class="text-sm font-black uppercase tracking-wider truncate mb-1 text-slate-800">{{ selectedMachineForQr.name }}</div>
                            <div class="text-[10px] text-slate-500 tracking-widest mb-3">CODE: {{ selectedMachineForQr.code }}</div>
                            
                            <div class="flex justify-center">
                                <qrcode-vue 
                                    :value="selectedMachineForQr.qr_url" 
                                    :size="160" 
                                    level="H" 
                                    render-as="canvas" 
                                />
                            </div>
                            
                            <div class="text-[8px] text-slate-500 font-sans tracking-wide mt-3 leading-tight uppercase font-bold">
                                SCAN CODE UNTUK MELAPORKAN KERUSAKAN MESIN SECARA MANDIRI VIA SMARTPHONE
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button 
                            @click="closeQrModal" 
                            class="flex-1 py-2 rounded-lg bg-slate-50 hover:bg-slate-100 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white text-xs font-bold uppercase transition-colors"
                        >
                            Batal
                        </button>
                        <button 
                            @click="printQrCode"
                            class="flex-1 py-2 rounded-lg bg-gradient-to-r from-cyan-650 to-cyan-555 hover:from-cyan-600 hover:to-cyan-500 dark:from-cyan-600 dark:to-cyan-550 dark:hover:from-cyan-500 dark:hover:to-cyan-400 text-white flex items-center justify-center gap-2 text-xs font-bold uppercase shadow-md dark:shadow-[0_0_15px_rgba(6,182,212,0.3)] transition-all"
                        >
                            <PrinterIcon class="h-4 w-4" /> Cetak (PDF)
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<style scoped>
.hud-panel {
    backdrop-filter: blur(12px);
}
.perspective-grid {
    background-image: 
        linear-gradient(to right, rgba(6, 182, 212, 0.05) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(6, 182, 212, 0.05) 1px, transparent 1px);
    background-size: 40px 40px;
    transform: perspective(500px) rotateX(60deg) translateY(-100px) scale(2);
    animation: grid-move 25s linear infinite;
    transform-origin: top;
}
@keyframes grid-move {
    0% { background-position: 0 0; }
    100% { background-position: 0 40px; }
}
.dark .glow-text {
    text-shadow: 0 0 10px rgba(6, 182, 212, 0.3);
}
.animate-fade-in {
    animation: fadeIn 0.4s ease-out forwards;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
