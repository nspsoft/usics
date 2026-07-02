<script setup>
import { Head } from '@inertiajs/vue3';
import { onMounted } from 'vue';

const props = defineProps({
    groupedScenarios: Object,
    stats: Object,
});

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getStatusLabel = (status) => {
    switch (status) {
        case 'passed': return 'LULUS';
        case 'failed': return 'GAGAL';
        default: return 'BELUM DIUJI';
    }
};

const getStatusClass = (status) => {
    switch (status) {
        case 'passed': return 'text-emerald-700 font-bold';
        case 'failed': return 'text-red-700 font-bold';
        default: return 'text-slate-500';
    }
};

import { ref, nextTick } from 'vue';

const printOrientation = ref('landscape');

const printReport = async (orientation) => {
    printOrientation.value = orientation;
    await nextTick();
    window.print();
};
</script>

<template>
    <Head title="Laporan Dokumentasi UAT - USICS ERP" />
    
    <!-- Dynamic Print Styles -->
    <component :is="'style'">
        @media print {
            @page {
                margin: 5mm; 
                size: {{ printOrientation }};
            }
        }
    </component>

    <div class="min-h-screen bg-white p-8 md:p-16 print:p-0 text-slate-900">
        <!-- Header Laporan -->
        <div class="flex justify-between items-start border-b-4 border-slate-900 pb-8 mb-8 print:pb-2 print:mb-4 print:border-b-2">
            <div>
                <h1 class="text-4xl font-black text-slate-900 tracking-tight mb-2 print:text-xl print:mb-0">LAPORAN UAT</h1>
                <p class="text-slate-600 font-mono italic print:text-xs">User Acceptance Testing Documentation</p>
                <p class="text-slate-900 font-mono text-xs uppercase tracking-widest mt-1 font-black print:text-[10px]">USICS ERP SYSTEM v1.0</p>
            </div>
            <div class="text-right">
                <div class="text-3xl font-black text-slate-900 tracking-tighter print:text-lg">PT JIDOKA</div>
                <p class="text-slate-800 text-sm font-black uppercase tracking-widest print:text-[10px]">Dokumentasi Pengujian Sistem</p>
                <p class="text-slate-600 text-xs mt-1 italic font-bold underline print:text-[10px]">{{ new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}</p>
            </div>
        </div>

        <!-- Ringkasan Statistik -->
        <!-- Ringkasan Statistik -->
        <!-- Ringkasan Statistik -->
        <!-- Ringkasan Statistik -->
        <!-- Ringkasan Statistik -->
        <div class="hidden print-flex stats-grid-compact justify-between border-b border-slate-900 mb-2 pb-1 text-[8px] font-black uppercase tracking-wider text-slate-900">
            <div>Total: {{ stats.total }}</div>
            <div>Lulus: {{ stats.passed }}</div>
            <div>Gagal: {{ stats.failed }}</div>
            <div>Progress: {{ stats.progress }}%</div>
        </div>

        <div class="grid grid-cols-4 gap-4 mb-12 stats-grid-large">
            <div class="p-6 border-4 border-slate-900 rounded-2xl bg-slate-50 shadow-sm">
                <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Total Skenario</div>
                <div class="text-4xl font-black text-slate-900 tracking-tighter">{{ stats.total }}</div>
            </div>
            <div class="p-6 border-4 border-emerald-600 rounded-2xl bg-emerald-50 shadow-sm">
                <div class="text-[10px] font-black text-emerald-600 uppercase tracking-widest mb-1">Lulus (Passed)</div>
                <div class="text-4xl font-black text-emerald-700 tracking-tighter">{{ stats.passed }}</div>
            </div>
            <div class="p-6 border-4 border-red-600 rounded-2xl bg-red-50 shadow-sm">
                <div class="text-[10px] font-black text-red-600 uppercase tracking-widest mb-1">Gagal (Failed)</div>
                <div class="text-4xl font-black text-red-700 tracking-tighter">{{ stats.failed }}</div>
            </div>
            <div class="p-6 border-4 border-blue-600 rounded-2xl bg-blue-50 shadow-sm">
                <div class="text-[10px] font-black text-blue-600 uppercase tracking-widest mb-1">Progress</div>
                <div class="text-4xl font-black text-blue-700 tracking-tighter">{{ stats.progress }}%</div>
            </div>
        </div>

        <!-- Konten Per Modul -->
        <div v-for="(scenarios, moduleName) in groupedScenarios" :key="moduleName" class="mb-12 break-after-page print:mb-0">
            <div class="flex items-center justify-between border-b-8 border-slate-900 pb-2 mb-6 print:border-b-2 print:mb-1 print:pb-0">
                <h2 class="text-3xl font-black text-slate-900 uppercase tracking-tighter print:text-sm">
                    MODUL: {{ moduleName }}
                </h2>
                <span class="text-xs font-black text-white bg-slate-900 px-3 py-1 rounded uppercase tracking-widest print:text-[8px] print:px-1 print:py-0">{{ scenarios.length }} Skenario</span>
            </div>

            <table class="w-full text-left border-collapse border-4 border-slate-900 print:border">
                <thead>
                    <tr class="bg-slate-900 text-white uppercase text-[10px] tracking-widest font-black print:text-[6px]">
                        <th class="p-4 border border-slate-700 text-center print:p-0.5">Kode</th>
                        <th class="p-4 border border-slate-700 print:p-0.5">Fitur / Judul</th>
                        <th class="p-4 border border-slate-700 print:p-0.5">Kriteria Penerimaan</th>
                        <th class="p-4 border border-slate-700 w-28 text-center print:p-0.5 print:w-16">Status</th>
                        <th class="p-4 border border-slate-700 print:p-0.5">Keterangan / Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="scenario in scenarios" :key="scenario.id" class="text-sm font-medium print:text-[8px] print:leading-tight">
                        <td class="p-4 border border-slate-900 font-black text-slate-900 text-xs leading-none text-center bg-slate-50 print:p-0.5 print:text-[6px] print:border-slate-400">{{ scenario.code }}</td>
                        <td class="p-4 border border-slate-900 print:p-0.5 print:border-slate-400">
                            <div class="font-black text-slate-900 uppercase tracking-tight mb-1 print:text-[7px]">{{ scenario.feature }}</div>
                            <div class="text-slate-600 text-xs font-bold leading-tight uppercase print:text-[7px]">{{ scenario.title }}</div>
                        </td>
                        <td class="p-4 border border-slate-900 text-slate-900 text-xs leading-relaxed font-bold print:p-0.5 print:text-[7px] print:border-slate-400">
                            {{ scenario.acceptance_criteria }}
                        </td>
                        <td class="p-4 border border-slate-900 text-center text-xs print:p-0.5 print:border-slate-400" :class="getStatusClass(scenario.status)">
                            <span class="font-black border-2 px-2 py-1 rounded inline-block print:px-1 print:py-0 print:border" :class="scenario.status === 'passed' ? 'border-emerald-600' : (scenario.status === 'failed' ? 'border-red-600' : 'border-slate-300')">
                                {{ getStatusLabel(scenario.status) }}
                            </span>
                        </td>
                        <td class="p-4 border border-slate-900 text-xs text-slate-900 font-bold bg-slate-50/50 print:p-0.5 print:text-[7px] print:border-slate-400">
                            <div v-if="scenario.notes" class="mb-1 italic underline decoration-slate-300 print:mb-0">{{ scenario.notes }}</div>
                            <div v-if="scenario.tested_at" class="text-[10px] text-slate-500 uppercase tracking-tighter bg-white inline-block px-1 border border-slate-200 print:text-[6px] print:px-0.5">
                                Diuji: {{ formatDate(scenario.tested_at) }}
                            </div>
                            <div v-if="scenario.tester" class="text-[10px] text-slate-500 uppercase tracking-tighter mt-1 print:mt-0 print:text-[6px]">
                                Oleh: <span class="font-black text-slate-800">{{ scenario.tester.name }}</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>



        <!-- Tombol Aksi (Hidden when printing) -->
        <div class="fixed bottom-8 right-8 flex gap-4 print:hidden">
            <button 
                @click="window.history.back()"
                class="px-8 py-4 bg-slate-200 text-slate-900 rounded-2xl font-black hover:bg-slate-300 transition-all flex items-center gap-2 shadow-xl border-b-4 border-slate-400"
            >
                Kembali
            </button>
            <button 
                @click="printReport('portrait')"
                class="px-6 py-4 bg-slate-800 text-white rounded-2xl font-black hover:bg-black transition-all shadow-2xl flex items-center gap-2 border-b-4 border-black group"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
                Portrait
            </button>
            <button 
                @click="printReport('landscape')"
                class="px-8 py-4 bg-slate-900 text-white rounded-2xl font-black hover:bg-black transition-all shadow-2xl flex items-center gap-2 border-b-4 border-black group"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                </svg>
                Landscape
            </button>
        </div>
    </div>
</template>

<style>
@media print {
    /* @page size controlled dynamically via component style above */
    
    body {
        background-color: white !important;
        -webkit-print-color-adjust: exact;
    }
    .min-h-screen {
        min-height: auto !important;
        padding: 0 !important;
    }

    /* Hide large elements */
    .stats-grid-large,
    .print-hidden {
        display: none !important;
    }

    /* Show print-only elements */
    .print-flex {
        display: flex !important;
    }

    /* Header Compact */
    h1 { font-size: 14pt !important; margin-bottom: 0 !important; }
    .text-3xl { font-size: 12pt !important; }
    
    /* Layout Logic */
    .break-after-page:not(:last-child) {
        page-break-after: always;
    }
    .break-after-page:last-child {
        page-break-after: auto;
    }
    
    /* Table Compactness */
    table {
        page-break-inside: auto;
        border-width: 1px !important;
    }
    th, td {
        padding: 4px 6px !important;
        border-width: 1px !important;
    }
    tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }
    
    /* Font Sizes - Proportional */
    h1 { font-size: 16pt !important; margin-bottom: 0.5rem !important; }
    .text-3xl { font-size: 12pt !important; }
    th { font-size: 8pt !important; }
    td { font-size: 9pt !important; }

    /* Spacing - Proportional */
    .mb-12 { margin-bottom: 1.5rem !important; }
    .mb-8 { margin-bottom: 0.75rem !important; }
    .mt-32 { margin-top: 3rem !important; }
    
    /* Footer specific */
    .print\:mt-4 { margin-top: 2rem !important; }
    .print\:gap-12 { gap: 3rem !important; }
}
</style>
