<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';
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
    ClockIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    stats: Object,
    machines: Array,
    recent_breakdowns: Array,
    upcoming_schedules: Array
});

const activeTab = ref('telemetry');
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
    if (score >= 80) return 'text-emerald-500';
    if (score >= 50) return 'text-amber-500';
    return 'text-rose-500';
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
        'active': 'bg-emerald-500/10 border border-emerald-500/30 text-emerald-400',
        'breakdown': 'bg-rose-500/10 border border-rose-500/30 text-rose-400 animate-pulse font-black shadow-[0_0_10px_rgba(244,63,94,0.2)]',
        'maintenance': 'bg-sky-500/10 border border-sky-500/30 text-sky-400',
        'inactive': 'bg-slate-500/10 border border-slate-500/30 text-slate-400'
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
    if (machine.predicted_failure === '-') return 'text-slate-600';
    if (!machine.predicted_failure_raw) return 'text-slate-300';
    
    const predictedDate = new Date(machine.predicted_failure_raw);
    const today = new Date();
    const diffTime = predictedDate - today;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays < 0) return 'text-rose-500 animate-pulse font-bold';
    if (diffDays <= 7) return 'text-amber-500 font-bold';
    return 'text-cyan-400';
};

const getRecentLogStatusClass = (status) => {
    return {
        'open': 'bg-rose-500/10 border-rose-500/20 text-rose-400',
        'in_progress': 'bg-amber-500/10 border-amber-500/20 text-amber-400',
        'resolved': 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400',
        'cancelled': 'bg-slate-500/10 border-slate-500/20 text-slate-400'
    }[status.toLowerCase()] || 'text-slate-400 border-white/10';
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
</script>

<template>
    <Head title="Maintenance Dashboard" />

    <AppLayout title="Maintenance Analytics" :render-header="false">
        <div class="min-h-screen bg-[#050510] relative overflow-hidden font-mono text-cyan-50 selection:bg-rose-500/30">
            
            <!-- Matrix Style Cyber Grid -->
            <div class="fixed inset-0 z-0 pointer-events-none">
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-950/20 to-[#050510]"></div>
                <div class="perspective-grid absolute inset-0 opacity-10"></div>
            </div>

            <div class="relative z-10 p-6 space-y-6 max-w-7xl mx-auto">
                
                <!-- Page Title Header -->
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-white/10 pb-4">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-0.5 text-[10px] bg-cyan-500/10 border border-cyan-500/20 rounded text-cyan-400 tracking-[0.2em] uppercase animate-pulse">
                                SYSTEM HEALTH ACTIVE
                            </span>
                            <span class="px-2 py-0.5 text-[10px] bg-white/5 border border-white/10 rounded text-slate-400 tracking-[0.2em] uppercase">MNT.PREDICT.V3</span>
                        </div>
                        <h1 class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 via-white to-indigo-400 tracking-widest uppercase glow-text">
                            MAINTENANCE ANALYTICS
                        </h1>
                    </div>
                </div>

                <!-- Navigation Tabs -->
                <div class="flex gap-2 border-b border-white/10 pb-1">
                    <button 
                        @click="activeTab = 'telemetry'"
                        class="px-4 py-2 text-xs font-black uppercase tracking-wider transition-all border-b-2 cursor-pointer"
                        :class="activeTab === 'telemetry' ? 'border-cyan-400 text-cyan-400 glow-text' : 'border-transparent text-slate-500 hover:text-slate-300'"
                    >
                        [01] Live Telemetry
                    </button>
                    <button 
                        @click="activeTab = 'tco'"
                        class="px-4 py-2 text-xs font-black uppercase tracking-wider transition-all border-b-2 cursor-pointer"
                        :class="activeTab === 'tco' ? 'border-cyan-400 text-cyan-400 glow-text' : 'border-transparent text-slate-500 hover:text-slate-300'"
                    >
                        [02] Analisis Biaya (TCO)
                    </button>
                </div>

                <!-- Tab 1: Live Telemetry -->
                <div v-if="activeTab === 'telemetry'" class="space-y-6 animate-fade-in">
                    <!-- KPI stats panel -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- KPI 1: Rerata Kesehatan -->
                        <div class="hud-panel p-4 flex items-center justify-between border-l-4 border-l-emerald-500 shadow-[0_0_20px_rgba(16,185,129,0.05)]">
                            <div>
                                <p class="text-[10px] text-slate-500 uppercase tracking-widest font-bold mb-1">Rata-rata Kesehatan</p>
                                <h3 class="text-2xl font-black text-emerald-400 glow-text">{{ stats.average_health }}%</h3>
                                <p class="text-[9px] text-slate-400 mt-1">Skor agregat semua mesin</p>
                            </div>
                            <div class="p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-lg">
                                <CpuChipIcon class="h-6 w-6 text-emerald-400" />
                            </div>
                        </div>

                        <!-- KPI 2: Active Breakdown -->
                        <div class="hud-panel p-4 flex items-center justify-between border-l-4 border-l-rose-500 shadow-[0_0_20px_rgba(244,63,94,0.05)]">
                            <div>
                                <p class="text-[10px] text-slate-500 uppercase tracking-widest font-bold mb-1">Breakdown Aktif</p>
                                <h3 class="text-2xl font-black text-rose-400 glow-text animate-pulse">{{ stats.active_breakdowns }} UNIT</h3>
                                <p class="text-[9px] text-slate-400 mt-1">Butuh respon segera</p>
                            </div>
                            <div class="p-3 bg-rose-500/10 border border-rose-500/20 rounded-lg">
                                <ExclamationTriangleIcon class="h-6 w-6 text-rose-400 animate-bounce" />
                            </div>
                        </div>

                        <!-- KPI 3: PM Overdue -->
                        <div class="hud-panel p-4 flex items-center justify-between border-l-4 border-l-amber-500 shadow-[0_0_20px_rgba(245,158,11,0.05)]">
                            <div>
                                <p class="text-[10px] text-slate-500 uppercase tracking-widest font-bold mb-1">PM Overdue</p>
                                <h3 class="text-2xl font-black text-amber-400 glow-text">{{ stats.overdue_pms }} TUGAS</h3>
                                <p class="text-[9px] text-slate-400 mt-1">Jadwal perawatan tertunda</p>
                            </div>
                            <div class="p-3 bg-amber-500/10 border border-amber-500/20 rounded-lg">
                                <CalendarDaysIcon class="h-6 w-6 text-amber-400" />
                            </div>
                        </div>

                        <!-- KPI 4: Critical Parts -->
                        <div class="hud-panel p-4 flex items-center justify-between border-l-4 border-l-sky-500 shadow-[0_0_20px_rgba(14,165,233,0.05)]">
                            <div>
                                <p class="text-[10px] text-slate-500 uppercase tracking-widest font-bold mb-1">Spareparts Kritis</p>
                                <h3 class="text-2xl font-black text-sky-400 glow-text">{{ stats.critical_spareparts }} ITEM</h3>
                                <p class="text-[9px] text-slate-400 mt-1">Stok di bawah batas minimal</p>
                            </div>
                            <div class="p-3 bg-sky-500/10 border border-sky-500/20 rounded-lg">
                                <CogIcon class="h-6 w-6 text-sky-400" />
                            </div>
                        </div>
                    </div>

                    <!-- Search and Filters -->
                    <div class="flex flex-col md:flex-row gap-4 items-center justify-between bg-slate-900/40 p-4 rounded-xl border border-white/5">
                        <div class="w-full md:max-w-md relative">
                            <input 
                                v-model="search"
                                type="text" 
                                placeholder="Cari mesin berdasarkan nama atau kode..." 
                                class="w-full bg-black/40 border border-white/10 rounded-lg px-4 py-2 text-sm text-cyan-200 placeholder-slate-500 outline-none focus:border-cyan-500 focus:shadow-[0_0_15px_rgba(6,182,212,0.2)] transition-all font-mono"
                            />
                        </div>
                        <div class="flex gap-2 w-full md:w-auto">
                            <span class="text-xs text-slate-400 self-center hidden md:inline">Filter:</span>
                            <select 
                                v-model="statusFilter"
                                class="bg-[#0f172a] border border-white/10 rounded-lg px-3 py-2 text-xs text-cyan-200 outline-none focus:border-cyan-500 flex-1 md:flex-none"
                            >
                                <option value="all" class="bg-[#0f172a] text-cyan-200">Semua Status</option>
                                <option value="active" class="bg-[#0f172a] text-cyan-200">Normal / Running</option>
                                <option value="breakdown" class="bg-[#0f172a] text-cyan-200">Breakdown</option>
                                <option value="maintenance" class="bg-[#0f172a] text-cyan-200">Under Repair</option>
                                <option value="inactive" class="bg-[#0f172a] text-cyan-200">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <!-- Machines Grid -->
                    <div class="space-y-4">
                        <h3 class="text-xs font-bold text-cyan-400 uppercase tracking-widest flex items-center gap-2">
                            <CpuChipIcon class="h-4 w-4" /> Telemetry Status Mesin ({{ filteredMachines.length }} Unit)
                        </h3>
                        
                        <div v-if="filteredMachines.length === 0" class="hud-panel p-8 text-center text-slate-500 text-sm">
                            Tidak ada mesin yang cocok dengan kriteria filter.
                        </div>

                        <div v-else class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div 
                                v-for="machine in filteredMachines" 
                                :key="machine.id"
                                class="hud-panel p-5 relative overflow-hidden group hover:border-cyan-500/30 transition-all duration-300"
                            >
                                <!-- Scan Glow Border on Hover -->
                                <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-cyan-400 to-transparent scale-x-0 group-hover:scale-x-100 transition-transform duration-500"></div>

                                <!-- Header -->
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <h4 class="text-lg font-black text-white group-hover:text-cyan-400 transition-colors uppercase tracking-wider">{{ machine.name }}</h4>
                                        <span class="text-[10px] text-slate-500 tracking-wider">ID: {{ machine.code }}</span>
                                    </div>
                                    <span class="px-2 py-0.5 rounded text-[8px] font-bold tracking-widest border" :class="getStatusBadgeClass(machine.status)">
                                        {{ getStatusText(machine.status) }}
                                    </span>
                                </div>

                                <!-- Telemetry Data -->
                                <div class="grid grid-cols-12 gap-4 items-center py-2 border-y border-white/5 my-4">
                                    <!-- Health score radial gauge -->
                                    <div class="col-span-4 flex flex-col items-center justify-center relative">
                                        <div class="relative w-16 h-16 flex items-center justify-center">
                                            <svg class="w-16 h-16 transform -rotate-90">
                                                <circle cx="32" cy="32" r="26" stroke="#0f172a" stroke-width="4.5" fill="transparent" />
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
                                            <span class="absolute text-xs font-bold text-white tracking-tighter" :class="healthColorClass(machine.health_score)">
                                                {{ machine.health_score }}%
                                            </span>
                                        </div>
                                        <span class="text-[9px] text-slate-500 mt-1 uppercase font-bold tracking-wider">Health</span>
                                    </div>

                                    <!-- Details list -->
                                    <div class="col-span-8 space-y-2 text-xs">
                                        <div class="flex justify-between">
                                            <span class="text-slate-500">Jam Kerja:</span>
                                            <span class="text-slate-300 font-bold font-mono">{{ numberFormat(machine.runtime_hours, 1) }} jam</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-slate-500">MTBF:</span>
                                            <span class="text-slate-300 font-bold" :class="machine.mtbf.includes('tidak') ? 'text-slate-600' : 'text-cyan-400'">{{ machine.mtbf }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-slate-500">Kegagalan Prediktif:</span>
                                            <span class="text-slate-300 font-bold" :class="getPredictionColor(machine)">{{ machine.predicted_failure }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Quick Action Buttons -->
                                <div class="flex items-center justify-between pt-2">
                                    <Link 
                                        href="/maintenance/breakdown"
                                        class="text-[10px] text-slate-400 hover:text-cyan-400 flex items-center gap-1 transition-colors"
                                    >
                                        <WrenchScrewdriverIcon class="h-3 w-3" /> Log Perawatan
                                    </Link>
                                    <button 
                                        @click="openQrModal(machine)"
                                        class="px-2 py-1 bg-white/5 hover:bg-white/10 text-white border border-white/10 rounded flex items-center gap-1.5 text-[10px] uppercase tracking-wider transition-colors"
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
                        <div class="hud-panel p-5 space-y-4">
                            <h3 class="text-sm font-bold text-rose-400 uppercase tracking-widest flex items-center gap-2">
                                <ExclamationTriangleIcon class="h-4 w-4" /> Insiden Kerusakan Terbaru (Breakdown)
                            </h3>
                            <div v-if="recent_breakdowns.length === 0" class="text-slate-500 text-xs py-4 text-center">
                                Tidak ada insiden kerusakan terekam baru-baru ini.
                            </div>
                            <div v-else class="space-y-3">
                                <div 
                                    v-for="log in recent_breakdowns" 
                                    :key="log.id"
                                    class="bg-black/30 border border-white/5 rounded p-3 flex flex-col md:flex-row justify-between items-start md:items-center gap-2 hover:bg-white/5 transition-colors"
                                >
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-white text-xs uppercase">{{ log.machine_name }} ({{ log.machine_code }})</span>
                                            <span class="px-1.5 py-0.5 rounded text-[8px] border" :class="getRecentLogStatusClass(log.status)">
                                                {{ log.status.toUpperCase() }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-400 line-clamp-1 italic">"{{ log.description }}"</p>
                                    </div>
                                    <div class="text-left md:text-right text-[10px] text-slate-500 font-mono">
                                        <span>{{ log.started_at }}</span>
                                        <span class="hidden md:block text-slate-600">Teknisi: {{ log.technician }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Upcoming Schedules Checklist -->
                        <div class="hud-panel p-5 space-y-4">
                            <h3 class="text-sm font-bold text-cyan-400 uppercase tracking-widest flex items-center gap-2">
                                <CalendarDaysIcon class="h-4 w-4" /> Checklist Preventive Maintenance (PM)
                            </h3>
                            <div v-if="upcoming_schedules.length === 0" class="text-slate-500 text-xs py-4 text-center">
                                Tidak ada jadwal PM aktif berikutnya.
                            </div>
                            <div v-else class="space-y-3">
                                <div 
                                    v-for="sched in upcoming_schedules" 
                                    :key="sched.id"
                                    class="bg-black/30 border border-white/5 rounded p-3 flex justify-between items-center hover:bg-white/5 transition-colors"
                                >
                                    <div>
                                        <h4 class="font-bold text-white text-xs uppercase">{{ sched.machine_name }}</h4>
                                        <p class="text-[10px] text-slate-400">{{ sched.task_name }}</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="px-2 py-0.5 rounded text-[9px] font-bold border" :class="sched.is_overdue ? 'bg-rose-500/10 border-rose-500/20 text-rose-400' : 'bg-cyan-500/10 border-cyan-500/20 text-cyan-400'">
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
                        <div class="hud-panel p-4 flex items-center justify-between border-l-4 border-l-cyan-500">
                            <div>
                                <p class="text-[10px] text-slate-500 uppercase tracking-widest font-bold mb-1">Investasi Pembelian Mesin</p>
                                <h3 class="text-lg font-black text-cyan-400 glow-text">{{ formatRupiah(stats.total_purchase_price) }}</h3>
                                <p class="text-[9px] text-slate-400 mt-1">Nilai perolehan awal mesin</p>
                            </div>
                            <div class="p-3 bg-cyan-500/10 border border-cyan-500/20 rounded-lg">
                                <BanknotesIcon class="h-6 w-6 text-cyan-400" />
                            </div>
                        </div>

                        <!-- KPI 2: Total Biaya Spareparts -->
                        <div class="hud-panel p-4 flex items-center justify-between border-l-4 border-l-amber-500">
                            <div>
                                <p class="text-[10px] text-slate-500 uppercase tracking-widest font-bold mb-1">Total Biaya Suku Cadang</p>
                                <h3 class="text-lg font-black text-amber-400 glow-text">{{ formatRupiah(stats.total_spareparts_cost) }}</h3>
                                <p class="text-[9px] text-slate-400 mt-1">Konsumsi spareparts dari gudang</p>
                            </div>
                            <div class="p-3 bg-amber-500/10 border border-amber-500/20 rounded-lg">
                                <CogIcon class="h-6 w-6 text-amber-400" />
                            </div>
                        </div>

                        <!-- KPI 3: Total Biaya Tenaga Kerja -->
                        <div class="hud-panel p-4 flex items-center justify-between border-l-4 border-l-sky-500">
                            <div>
                                <p class="text-[10px] text-slate-500 uppercase tracking-widest font-bold mb-1">Biaya Tenaga Kerja (Labor)</p>
                                <h3 class="text-lg font-black text-sky-400 glow-text">{{ formatRupiah(stats.total_labor_cost) }}</h3>
                                <p class="text-[9px] text-slate-400 mt-1">Durasi perbaikan × Rp 50.000/jam</p>
                            </div>
                            <div class="p-3 bg-sky-500/10 border border-sky-500/20 rounded-lg">
                                <ClockIcon class="h-6 w-6 text-sky-400" />
                            </div>
                        </div>

                        <!-- KPI 4: Total TCO Seluruhnya -->
                        <div class="hud-panel p-4 flex items-center justify-between border-l-4 border-l-emerald-500">
                            <div>
                                <p class="text-[10px] text-slate-500 uppercase tracking-widest font-bold mb-1">Akumulasi TCO Aset</p>
                                <h3 class="text-lg font-black text-emerald-400 glow-text">{{ formatRupiah(stats.total_tco_all) }}</h3>
                                <p class="text-[9px] text-slate-400 mt-1">Investasi + Total Perawatan</p>
                            </div>
                            <div class="p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-lg">
                                <PresentationChartBarIcon class="h-6 w-6 text-emerald-400" />
                            </div>
                        </div>
                    </div>

                    <!-- TCO Detailed Table -->
                    <div class="hud-panel p-5 space-y-4">
                        <h3 class="text-xs font-bold text-cyan-400 uppercase tracking-widest flex items-center gap-2">
                            <PresentationChartBarIcon class="h-4 w-4" /> Tabel Rincian Total Cost of Ownership (TCO) Mesin
                        </h3>

                        <div class="overflow-x-auto rounded-xl border border-white/10">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-white/5 text-[10px] uppercase tracking-wider text-slate-400 font-bold">
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
                                <tbody class="divide-y divide-white/5 bg-slate-900/50">
                                    <tr v-for="machine in machines" :key="machine.id" class="hover:bg-white/5 transition-colors">
                                        <td class="p-4 font-bold text-white uppercase">{{ machine.name }}</td>
                                        <td class="p-4 font-mono text-xs text-slate-400">{{ machine.code }}</td>
                                        <td class="p-4 text-right text-slate-300 font-mono">{{ formatRupiah(machine.tco.purchase_price) }}</td>
                                        <td class="p-4 text-right text-amber-400 font-mono">{{ formatRupiah(machine.tco.spareparts_cost) }}</td>
                                        <td class="p-4 text-right text-sky-400 font-mono">{{ formatRupiah(machine.tco.labor_cost) }}</td>
                                        <td class="p-4 text-right text-emerald-400 font-mono font-bold">{{ formatRupiah(machine.tco.total_tco) }}</td>
                                        <td class="p-4">
                                            <div class="flex items-center gap-2 justify-center">
                                                <div class="w-24 bg-white/5 rounded-full h-2 overflow-hidden border border-white/10">
                                                    <div 
                                                        class="h-full rounded-full transition-all"
                                                        :class="getTcoRatioColor(machine.tco)"
                                                        :style="{ width: Math.min(100, getTcoRatioPercentage(machine.tco)) + '%' }"
                                                    ></div>
                                                </div>
                                                <span class="text-[10px] font-mono font-bold text-slate-400">
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

            </div>

            <!-- Print QR Modal -->
            <div v-if="showQrModal && selectedMachineForQr" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
                <div class="bg-[#0f172a] border border-cyan-500/30 p-6 rounded-xl w-full max-w-sm shadow-[0_0_50px_rgba(6,182,212,0.2)]">
                    <div class="flex justify-between items-center mb-4 pb-2 border-b border-white/10">
                        <h3 class="text-sm font-bold text-cyan-400 uppercase tracking-wider flex items-center gap-2">
                            <QrCodeIcon class="h-5 w-5" /> Cetak QR Code Kerusakan
                        </h3>
                        <button @click="closeQrModal" class="text-slate-400 hover:text-white transition-colors">
                            <XMarkIcon class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="bg-white p-4 rounded-xl max-w-[240px] mx-auto mb-6 text-black border border-slate-200">
                        <div id="qr-print-area" class="text-center font-mono">
                            <div class="text-sm font-black uppercase tracking-wider truncate mb-1">{{ selectedMachineForQr.name }}</div>
                            <div class="text-[10px] text-slate-500 tracking-widest mb-3">CODE: {{ selectedMachineForQr.code }}</div>
                            
                            <div class="flex justify-center">
                                <qrcode-vue 
                                    :value="selectedMachineForQr.qr_url" 
                                    :size="160" 
                                    level="H" 
                                    render-as="canvas" 
                                />
                            </div>
                            
                            <div class="text-[8px] text-slate-600 font-sans tracking-wide mt-3 leading-tight">
                                SCAN CODE UNTUK MELAPORKAN KERUSAKAN MESIN SECARA MANDIRI VIA SMARTPHONE
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button 
                            @click="closeQrModal" 
                            class="flex-1 py-2 rounded-lg bg-white/5 border border-white/10 text-slate-400 hover:text-white hover:bg-white/10 text-xs font-bold uppercase transition-colors"
                        >
                            Batal
                        </button>
                        <button 
                            @click="printQrCode"
                            class="flex-1 py-2 rounded-lg bg-gradient-to-r from-cyan-600 to-cyan-500 hover:from-cyan-500 hover:to-cyan-400 text-white flex items-center justify-center gap-2 text-xs font-bold uppercase shadow-[0_0_15px_rgba(6,182,212,0.3)] transition-all"
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
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
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
.glow-text {
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


