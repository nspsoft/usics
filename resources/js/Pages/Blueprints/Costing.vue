<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';
import { 
    ChartPieIcon, 
    CalculatorIcon, 
    ArrowPathIcon,
    ArrowUpCircleIcon,
    BeakerIcon,
    CpuChipIcon,
    Square3Stack3DIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    title: String,
    mode: {
        type: String,
        default: 'production',
    },
});

// --- Real-time Clock ---
const time = ref('');
const updateTime = () => {
    const now = new Date();
    time.value = now.toLocaleTimeString('en-US', { hour12: false, hour: '2-digit', minute: '2-digit', second: '2-digit' });
};
let timer;
onMounted(() => {
    updateTime();
    timer = setInterval(updateTime, 1000);
});
onUnmounted(() => clearInterval(timer));

const costElements = computed(() => {
    switch (props.mode) {
        case 'overhead':
            return [
                { name: 'Machinery Depreciation', value: 'Rp 42.000.000', percentage: 49, color: '#f59e0b' },
                { name: 'Factory Power & Utilities', value: 'Rp 21.500.000', percentage: 25, color: '#ef4444' },
                { name: 'Machine Maintenance', value: 'Rp 13.000.000', percentage: 15, color: '#10b981' },
                { name: 'Indirect Supervision', value: 'Rp 8.500.000', percentage: 11, color: '#ec4899' },
            ];
        case 'profitability':
            return [
                { name: 'Gross Revenue', value: 'Rp 1.250.000.000', percentage: 100, color: '#10b981' },
                { name: 'Manufacturing Cost (COGS)', value: 'Rp 690.400.000', percentage: 55, color: '#ef4444' },
                { name: 'Operating Expenses (OPEX)', value: 'Rp 170.000.000', percentage: 14, color: '#f59e0b' },
                { name: 'Net Profit Margin (EBITDA)', value: 'Rp 389.600.000', percentage: 31, color: '#3b82f6' },
            ];
        default:
            return [
                { name: 'Raw Materials', value: 'Rp 450.000.000', percentage: 65, color: '#3b82f6' },
                { name: 'Direct Labor', value: 'Rp 120.000.000', percentage: 18, color: '#8b5cf6' },
                { name: 'Factory Overhead', value: 'Rp 85.000.000', percentage: 12, color: '#22d3ee' },
                { name: 'Other Costs', value: 'Rp 35.000.000', percentage: 5, color: '#64748b' },
            ];
    }
});

const pageConfig = computed(() => {
    switch (props.mode) {
        case 'overhead':
            return {
                heading: 'OVERHEAD ALLOCATION',
                bannerTitle: 'Overhead Allocation Blueprint',
                bannerDescription: 'Blueprinting phase: Implementing overhead allocation engine using cost drivers (machine hours, labor hours, energy). Allocations are posted to work orders and item-level costs.',
                breakdownTitle: 'Overhead Allocation Breakdown',
                totalLabel: 'Total Allocated Overhead',
                totalValue: 'Rp 85.000.000',
                panel1Title: 'Allocation Keys',
                panel1Desc: 'Post allocation based on current cost drivers: Machine Hours (55%), Direct Labor Hours (30%), Square Footage (15%).',
                panel1Button: 'Recalculate Cost Drivers',
                panel2Title: 'Factory Absorption',
                panel2Desc: 'Live tracking of overhead cost absorption across all active production lines and machines.',
                panel2BadgeLabel: 'FACTORY ABSORPTION RATE',
                panel2BadgeValue: '98.2%',
            };
        case 'profitability':
            return {
                heading: 'PROFITABILITY ANALYTICS',
                bannerTitle: 'Profitability Analytics Blueprint',
                bannerDescription: 'Blueprinting phase: Implementing profitability analytics per item/customer/order by combining selling price, actual HPP, and overhead allocation. Detecting margin leakage and pricing anomalies.',
                breakdownTitle: 'Cost & Margin Breakdown',
                totalLabel: 'Estimated Net Profit (EBIT)',
                totalValue: 'Rp 389.600.000',
                panel1Title: 'Margin Sensitivity',
                panel1Desc: 'Analyze profitability sensitivity based on raw material price fluctuations (steel coil +10%, scrap price -5%).',
                panel1Button: 'Run Sensitivity Analysis',
                panel2Title: 'Net Margin',
                panel2Desc: 'Live profitability detection based on current selling price and actual manufacturing HPP.',
                panel2BadgeLabel: 'NET MARGIN ESTIMATE',
                panel2BadgeValue: '+31.1%',
            };
        default:
            return {
                heading: 'PRODUCTION COSTING',
                bannerTitle: 'Production Costing Blueprint',
                bannerDescription: 'Blueprinting phase: Implementing real-time actual costing engine. This module calculates COGS (HPP) by aggregating Raw Material (FIFO/Weighted), Direct Labor, and allocated Overhead for every Work Order.',
                breakdownTitle: 'Production Cost Breakdown',
                totalLabel: 'Total Estimated COGS (HPP)',
                totalValue: 'Rp 690.400.000',
                panel1Title: 'Variance Analytics',
                panel1Desc: 'Comparing Standard Cost vs Actual Cost. Automatic detection of efficiency leaks and material variance.',
                panel1Button: 'RUN VARIANCE AUDIT',
                panel2Title: 'Margin Pulse',
                panel2Desc: 'Live profitability detection based on current selling price and actual manufacturing HPP.',
                panel2BadgeLabel: 'NET MARGIN ESTIMATE',
                panel2BadgeValue: '+22.4%',
            };
    }
});

const navigationTabs = computed(() => [
    { name: 'Financial Hub', href: '/finance/dashboard', active: false },
    { name: 'General Ledger', href: '/finance/ledger', active: false },
    { name: 'Profit & Loss', href: '/finance/reports', active: false },
    { name: 'AP & AR', href: '/finance/payment-monitoring', active: false },
    { name: 'Production Costing', href: '/costing/production', active: props.mode === 'production' },
    { name: 'Overhead Allocation', href: '/costing/overhead', active: props.mode === 'overhead' },
    { name: 'Profitability Analytics', href: '/costing/profitability', active: props.mode === 'profitability' },
]);

const isAnalyzing = ref(false);
const aiAnalysis = ref(null);
const showAiModal = ref(false);
const analysisError = ref('');

const formatAiResponse = (text) => {
    if (!text) return '';
    let isHtml = /<[a-z][\s\S]*>/i.test(text);
    if (isHtml) {
        return text;
    }
    
    // Convert markdown to HTML
    let html = text
        .replace(/\*\*(.*?)\*\*/g, '<strong class="text-white font-bold">$1</strong>')
        .replace(/\*(.*?)\*/g, '<em class="text-slate-300">$1</em>')
        .replace(/### (.*?)(?:\n|$)/g, '<h3 class="text-cyan-400 font-bold text-sm mt-4 mb-2 tracking-wide uppercase">$1</h3>')
        .replace(/## (.*?)(?:\n|$)/g, '<h2 class="text-cyan-300 font-black text-base mt-6 mb-3 border-b border-cyan-500/20 pb-1 tracking-widest uppercase">$1</h2>')
        .replace(/# (.*?)(?:\n|$)/g, '<h1 class="text-white font-black text-lg mt-8 mb-4 tracking-widest uppercase">$1</h1>')
        .replace(/^\s*-\s+(.*?)(?:\n|$)/gm, '<li class="ml-5 list-disc text-slate-300 my-1.5 leading-relaxed">$1</li>')
        .replace(/^\s*\*\s+(.*?)(?:\n|$)/gm, '<li class="ml-5 list-disc text-slate-300 my-1.5 leading-relaxed">$1</li>')
        .replace(/\n\n/g, '</p><p class="text-slate-400 leading-relaxed text-xs my-3">')
        .replace(/\n/g, '<br>');
    return '<p class="text-slate-400 leading-relaxed text-xs my-3">' + html + '</p>';
};

const runAiAudit = async () => {
    isAnalyzing.value = true;
    showAiModal.value = true;
    analysisError.value = '';
    aiAnalysis.value = null;
    
    try {
        const response = await axios.post(route('costing.ai-analyze'), {
            mode: props.mode,
            cost_elements: costElements.value,
            total_value: pageConfig.value.totalValue
        });
        
        if (response.data && response.data.success) {
            aiAnalysis.value = response.data;
        } else {
            analysisError.value = response.data?.message || 'Unknown error occurred during analysis.';
        }
    } catch (error) {
        console.error('AI Costing Audit Error:', error);
        analysisError.value = error.response?.data?.message || 'Gagal menghubungi server untuk melakukan analisis AI.';
    } finally {
        isAnalyzing.value = false;
    }
};

const printReport = () => {
    const printContent = document.querySelector('.ai-report-content')?.innerHTML;
    if (!printContent) return;
    
    const logoUrl = window.location.origin + '/images/jri-official-logo.png';
    const printWindow = window.open('', '_blank');
    printWindow.document.write('<html><head><title>AI Costing Audit Report - ' + props.title + '</title>' +
        '<style>' +
        'body { font-family: Arial, sans-serif; background: #fff; color: #000; padding: 40px; line-height: 1.5; }' +
        'h1, h2, h3 { border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-top: 25px; }' +
        'p, li { font-size: 10pt; }' +
        'table { width: 100%; border-collapse: collapse; margin: 20px 0; }' +
        'th, td { border: 1px solid #ccc; padding: 8px 12px; text-align: left; }' +
        'th { background: #f0f0f0; }' +
        '.header-table { width: 100%; border-collapse: collapse; border-bottom: 2pt solid #003680; padding-bottom: 15px; margin-bottom: 25px; }' +
        '.header-table td { vertical-align: top; border: none; padding: 0 0 15px 0; }' +
        '.company-logo-text { font-size: 24pt; font-weight: 900; font-style: italic; color: #E21E26; letter-spacing: -1px; margin: 0; line-height: 1; text-transform: lowercase; }' +
        '.company-full-name { font-size: 10pt; font-weight: 800; color: #003680; margin: -5px 0 3px 0; font-family: Arial, sans-serif; }' +
        '.company-address { font-size: 8pt; line-height: 1.3; color: #333; font-family: Arial, sans-serif; }' +
        '</style></head>' +
        '<body>' +
        '<table class="header-table"><tr>' +
        '<td width="15%"><img src="' + logoUrl + '" alt="logo" style="height: 60px;"></td>' +
        '<td width="85%" style="padding-left: 15px;">' +
        '<div class="company-logo-text">jidoka</div>' +
        '<div class="company-full-name">PT. JIDOKA RESULT INDONESIA</div>' +
        '<div class="company-address" style="margin-top: 5px;">' +
        'Kawasan Industri JABABEKA I<br>' +
        'Jl. Jababeka II Blok C No. 19 L<br>' +
        'Pasir Gombong, Cikarang Utara, Bekasi 17530 Jawa Barat<br>' +
        'Telp : +62 21 89383915, Fax. : +62 21 89383915<br>' +
        'e_mail : accounting@jidoka.co.id' +
        '</div>' +
        '</td>' +
        '</tr></table>' +
        '<h1>AI COSTING AUDIT REPORT: ' + props.title.toUpperCase() + '</h1>' +
        '<p><strong>Date:</strong> ' + new Date().toLocaleDateString() + ' &nbsp;|&nbsp; <strong>Mode:</strong> ' + props.mode.toUpperCase() + '</p>' +
        '<hr />' +
        printContent +
        '</body></html>'
    );
    printWindow.document.close();
    printWindow.print();
};
</script>

<template>
    <Head :title="title" />
    
    <AppLayout :title="title" :render-header="false">
        <div class="min-h-screen bg-[#050510] relative overflow-hidden font-mono text-cyan-50 selection:bg-cyan-500/30">
            
            <!-- Dynamic Background -->
            <div class="fixed inset-0 z-0 pointer-events-none">
                <div class="absolute inset-0 bg-gradient-to-b from-indigo-950/20 to-[#050510]"></div>
                <div class="perspective-grid absolute inset-0 opacity-20"></div>
                <div class="absolute top-[-10%] right-[20%] w-[600px] h-[600px] bg-indigo-600/10 blur-[150px] rounded-full animate-float"></div>
                <div class="stars"></div>
            </div>

            <div class="relative z-10 p-6 space-y-8">
                
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-white/10 pb-4 backdrop-blur-sm">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-0.5 text-[10px] bg-white/5 border border-white/10 rounded text-slate-400 tracking-[0.2em]">CST.MNG.x64</span>
                            <span class="flex items-center gap-1.5 px-2 py-0.5 text-[10px] bg-cyan-500/10 border border-cyan-500/20 rounded text-cyan-400 tracking-wider animate-pulse">
                                <span class="w-1.5 h-1.5 rounded-full bg-cyan-400"></span> ENGINE ONLINE
                            </span>
                        </div>
                        <h1 class="text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 via-white to-indigo-400 tracking-widest uppercase glow-text">
                            {{ pageConfig.heading }}
                        </h1>
                    </div>
                    
                    <div class="flex items-center gap-6">
                        <div class="text-right hidden md:block border-r border-white/10 pr-6">
                            <p class="text-[10px] text-cyan-500/70 tracking-[0.2em] mb-1">NETWORK TIME</p>
                            <p class="text-2xl font-bold font-mono text-white glow-text">{{ time }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <button class="px-4 py-2 bg-white/5 border border-white/10 text-slate-400 rounded-xl text-xs font-bold flex items-center gap-2 hover:bg-white/10 transition-all uppercase tracking-widest">
                                <ArrowPathIcon class="h-4 w-4" /> Recalculate
                            </button>
                        </div>
                    </div>
                </div>

                <!-- HUD Navigation Tabs -->
                <div class="flex items-center gap-2 mb-8 bg-white/5 p-1 rounded-xl border border-white/5 overflow-x-auto no-scrollbar relative z-10">
                    <Link 
                        v-for="tab in navigationTabs" 
                        :key="tab.name"
                        :href="tab.href"
                        class="px-6 py-2 rounded-lg text-xs font-bold tracking-widest transition-all duration-300 whitespace-nowrap"
                        :class="tab.active 
                            ? 'bg-cyan-500/20 text-cyan-400 border border-cyan-500/30' 
                            : 'text-slate-500 hover:text-slate-300 hover:bg-white/5'"
                    >
                        {{ tab.name }}
                    </Link>
                </div>

                <!-- Alert/Context Banner -->
                <div class="hud-panel p-6 overflow-hidden relative group">
                    <div class="relative z-10 flex flex-col md:flex-row items-center gap-6">
                        <div class="h-16 w-16 bg-cyan-500/10 border border-cyan-500/20 rounded-2xl flex items-center justify-center shrink-0">
                            <CpuChipIcon class="h-8 w-8 text-cyan-400" />
                        </div>
                        <div>
                            <h3 class="text-lg font-black tracking-tight uppercase text-white glow-text">{{ pageConfig.bannerTitle }}</h3>
                            <p class="text-slate-400 max-w-4xl text-xs leading-relaxed mt-1 italic">
                                {{ pageConfig.bannerDescription }}
                            </p>
                        </div>
                    </div>
                    <div class="absolute right-0 top-0 h-full w-1/3 bg-gradient-to-l from-cyan-500/5 to-transparent skew-x-12 translate-x-1/2"></div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
                    <!-- Left: Cost Breakdown -->
                    <div class="lg:col-span-2 hud-panel p-8">
                        <div class="flex items-center justify-between mb-8">
                            <div>
                                <h3 class="text-xl font-black text-white uppercase tracking-tighter glow-text">{{ pageConfig.breakdownTitle }}</h3>
                                <p class="text-[10px] text-cyan-500 font-bold uppercase tracking-[0.2em] mt-1 pulse-opacity">Status: Calculation Running</p>
                            </div>
                            <div class="p-3 bg-white/5 border border-white/10 rounded-2xl">
                                <ChartPieIcon class="h-6 w-6 text-cyan-400" />
                            </div>
                        </div>

                        <div class="space-y-8">
                            <div v-for="cost in costElements" :key="cost.name" class="group">
                                <div class="flex justify-between items-end mb-2">
                                    <div>
                                        <span class="text-sm font-black text-white group-hover:text-cyan-400 transition-colors uppercase tracking-tight">{{ cost.name }}</span>
                                        <div class="text-[10px] text-slate-500 font-mono tracking-tighter uppercase">
                                            {{ cost.percentage }}% {{ mode === 'profitability' ? 'OF REVENUE' : (mode === 'overhead' ? 'OF TOTAL OVERHEAD' : 'OF TOTAL COGS') }}
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm font-black text-white font-mono glow-text transition-all group-hover:scale-110">{{ cost.value }}</div>
                                    </div>
                                </div>
                                <div class="w-full bg-slate-900 h-2 rounded-full overflow-hidden border border-white/5 p-[1px]">
                                    <div class="h-full rounded-full transition-all duration-1000 delay-300 shadow-[0_0_10px_currentColor]" :style="{ width: `${cost.percentage}%`, backgroundColor: cost.color, color: cost.color }"></div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-12 pt-8 border-t border-white/10 flex items-center justify-between">
                            <div class="text-slate-500 text-[10px] font-bold uppercase tracking-[0.2em]">{{ pageConfig.totalLabel }}</div>
                            <div class="text-4xl font-black text-cyan-400 glow-text tracking-tighter">{{ pageConfig.totalValue }}</div>
                        </div>
                    </div>

                    <!-- Right: Analysis Controls -->
                    <div class="space-y-6">
                        <div class="hud-panel p-6 border-l-4 border-cyan-500 relative overflow-hidden group">
                            <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:opacity-10 transition-opacity">
                                <CalculatorIcon class="h-24 w-24 text-white" />
                            </div>
                            <div class="relative z-10 flex flex-col">
                                <div class="flex items-center gap-4 mb-4">
                                    <div class="p-3 bg-cyan-500/10 text-cyan-500 border border-cyan-500/20 rounded-2xl">
                                        <Square3Stack3DIcon class="h-6 w-6" />
                                    </div>
                                    <h4 class="font-black text-white uppercase tracking-tighter">{{ pageConfig.panel1Title }}</h4>
                                </div>
                                <p class="text-[10px] text-slate-400 leading-relaxed mb-6 uppercase tracking-wider">
                                    {{ pageConfig.panel1Desc }}
                                </p>
                                <button 
                                    @click="runAiAudit"
                                    :disabled="isAnalyzing"
                                    class="w-full py-4 bg-cyan-600 text-white rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] hover:scale-105 hover:bg-cyan-500 transition-all shadow-lg shadow-cyan-900/40 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                                >
                                    <template v-if="isAnalyzing">
                                        <ArrowPathIcon class="h-4 w-4 animate-spin text-white" />
                                        ANALYZING DATA...
                                    </template>
                                    <template v-else>
                                        {{ pageConfig.panel1Button }}
                                    </template>
                                </button>
                            </div>
                        </div>

                        <div class="hud-panel p-6 border-l-4 border-indigo-500 group relative overflow-hidden">
                             <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:opacity-10 transition-opacity">
                                <ArrowUpCircleIcon class="h-24 w-24 text-white" />
                            </div>
                            <div class="relative z-10">
                                <div class="flex items-center gap-4 mb-4">
                                    <div class="p-3 bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 rounded-2xl">
                                        <BeakerIcon class="h-6 w-6" />
                                    </div>
                                    <h4 class="font-black text-white uppercase tracking-tighter">{{ pageConfig.panel2Title }}</h4>
                                </div>
                                <p class="text-[10px] text-slate-400 leading-relaxed mb-6 uppercase tracking-wider">
                                    {{ pageConfig.panel2Desc }}
                                </p>
                                <div class="p-4 bg-[#0a0a20] rounded-2xl border text-center shadow-inner" :class="mode === 'overhead' ? 'border-cyan-500/20 shadow-cyan-500/10' : 'border-emerald-500/20 shadow-emerald-500/10'">
                                    <div class="text-[10px] font-bold uppercase mb-1 tracking-widest" :class="mode === 'overhead' ? 'text-cyan-500' : 'text-emerald-500'">{{ pageConfig.panel2BadgeLabel }}</div>
                                    <div class="text-3xl font-black glow-text" :class="mode === 'overhead' ? 'text-cyan-400' : 'text-emerald-400'">{{ pageConfig.panel2BadgeValue }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Technical Specs / Footer -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 pb-12">
                    <div v-for="i in 4" :key="i" class="p-4 bg-white/5 border border-white/10 rounded-xl">
                        <div class="text-[8px] text-slate-500 uppercase mb-1 tracking-[0.2em]">ENGINE.PARAM.0{{ i }}</div>
                        <div class="text-xs font-bold text-slate-300 uppercase tracking-widest">ENABLED</div>
                    </div>
                </div>
            </div>
            
            <!-- AI Analysis HUD Modal -->
            <div v-if="showAiModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md">
                <div class="relative w-full max-w-4xl max-h-[85vh] bg-[#070715] border border-cyan-500/30 rounded-2xl shadow-[0_0_50px_rgba(6,182,212,0.15)] flex flex-col overflow-hidden animate-fade-in font-mono">
                    
                    <!-- Scanline effect overlay inside modal -->
                    <div class="absolute inset-0 pointer-events-none bg-scanlines opacity-[0.03]"></div>
                    <div class="absolute top-0 left-0 w-full h-[2px] bg-cyan-500/40 shadow-[0_0_10px_#06b6d4] animate-scan pointer-events-none"></div>

                    <!-- Modal Header -->
                    <div class="flex items-center justify-between p-6 border-b border-cyan-500/20 bg-cyan-950/20 shrink-0">
                        <div class="flex items-center gap-3">
                            <span class="w-2.5 h-2.5 rounded-full bg-cyan-400 animate-ping"></span>
                            <h3 class="text-lg font-black tracking-widest text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-indigo-400 glow-text uppercase">
                                {{ mode === 'production' ? 'AI VARIANCE AUDIT SYSTEM' : (mode === 'overhead' ? 'AI OVERHEAD OPTIMIZER' : 'AI PROFITABILITY DIAGNOSTIC') }}
                            </h3>
                        </div>
                        <button 
                            @click="showAiModal = false" 
                            class="px-3 py-1.5 border border-red-500/30 hover:border-red-500 bg-red-950/20 hover:bg-red-500/20 text-red-400 hover:text-red-300 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all"
                        >
                            [ TERMINATE_SESSION ]
                        </button>
                    </div>

                    <!-- Modal Body / Content -->
                    <div class="p-6 md:p-8 overflow-y-auto grow custom-scrollbar space-y-6 relative">
                        
                        <!-- Scenario 1: Loading State -->
                        <div v-if="isAnalyzing" class="flex flex-col items-center justify-center py-20 space-y-6">
                            <div class="relative w-20 h-20">
                                <!-- Outer neon spinning wheel -->
                                <div class="absolute inset-0 border-4 border-cyan-500/10 border-t-cyan-400 rounded-full animate-spin shadow-[0_0_15px_rgba(6,182,212,0.3)]"></div>
                                <!-- Inner neon counter-spinning wheel -->
                                <div class="absolute inset-2 border-4 border-indigo-500/10 border-b-indigo-400 rounded-full animate-spin-reverse"></div>
                                <!-- Core flashing light -->
                                <div class="absolute inset-6 bg-cyan-500/20 rounded-full flex items-center justify-center animate-pulse">
                                    <CpuChipIcon class="h-6 w-6 text-cyan-400" />
                                </div>
                            </div>
                            <div class="text-center space-y-2 max-w-md">
                                <h4 class="text-sm font-black text-cyan-400 tracking-[0.25em] uppercase glow-text animate-pulse">Running Cognitive Diagnostic...</h4>
                                <div class="w-48 bg-slate-950 h-1 border border-cyan-500/20 rounded-full mx-auto p-[1px] overflow-hidden">
                                    <div class="h-full bg-cyan-400 rounded-full animate-loading-bar"></div>
                                </div>
                                <p class="text-[10px] text-slate-500 uppercase tracking-widest animate-pulse mt-2">
                                    AUDITING: {{ costElements.length }} Elements &bull; Total Value: {{ pageConfig.totalValue }}
                                </p>
                            </div>
                        </div>

                        <!-- Scenario 2: Error State -->
                        <div v-else-if="analysisError" class="p-6 bg-red-950/20 border border-red-500/30 rounded-2xl flex items-start gap-4">
                            <div class="p-2 bg-red-500/10 border border-red-500/20 rounded-xl text-red-400 shrink-0">
                                <span class="text-xl font-bold">⚠️</span>
                            </div>
                            <div class="space-y-1">
                                <h4 class="font-black text-red-400 text-sm tracking-wider uppercase">CORE SYSTEM DIAGNOSTIC ERROR</h4>
                                <p class="text-xs text-slate-400 leading-relaxed font-mono mt-1">{{ analysisError }}</p>
                                <button 
                                    @click="runAiAudit" 
                                    class="mt-4 px-4 py-2 bg-red-500/20 hover:bg-red-500/30 border border-red-500/40 text-red-300 hover:text-red-200 text-[10px] font-black uppercase tracking-widest rounded-xl transition-all"
                                >
                                    Retry Diagnosis
                                </button>
                            </div>
                        </div>

                        <!-- Scenario 3: AI Audit Result Display -->
                        <div v-else-if="aiAnalysis" class="space-y-6 animate-fade-in">
                            
                            <!-- Key Metrics Header -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                
                                <!-- Card 1: Diagnostic Efficiency Score -->
                                <div class="p-4 bg-[#0a0a20] border border-cyan-500/20 rounded-xl flex items-center justify-between">
                                    <div>
                                        <span class="text-[8px] text-slate-500 tracking-[0.2em] uppercase font-bold">EFFICIENCY SCORE</span>
                                        <div class="text-2xl font-black text-cyan-400 font-mono glow-text mt-1">
                                            {{ Math.round((aiAnalysis.score || 0.85) * 100) }}%
                                        </div>
                                    </div>
                                    <div class="h-10 w-10 rounded-lg bg-cyan-500/10 border border-cyan-500/20 flex items-center justify-center font-bold text-cyan-400">
                                        {{ (aiAnalysis.score || 0.85) >= 0.8 ? 'A+' : ((aiAnalysis.score || 0.85) >= 0.6 ? 'B' : 'C') }}
                                    </div>
                                </div>

                                <!-- Card 2: Anomaly / Leak Count -->
                                <div class="p-4 bg-[#0a0a20] border border-orange-500/20 rounded-xl flex items-center justify-between">
                                    <div>
                                        <span class="text-[8px] text-slate-500 tracking-[0.2em] uppercase font-bold">ANOMALIES DETECTED</span>
                                        <div class="text-2xl font-black font-mono mt-1" :class="(aiAnalysis.leaks_detected || 0) > 0 ? 'text-orange-400 glow-text' : 'text-emerald-400'">
                                            {{ aiAnalysis.leaks_detected ?? 0 }} LEAKS
                                        </div>
                                    </div>
                                    <div class="h-10 w-10 rounded-lg bg-orange-500/10 border border-orange-500/20 flex items-center justify-center font-bold" :class="(aiAnalysis.leaks_detected || 0) > 0 ? 'text-orange-400' : 'text-emerald-400'">
                                        {{ (aiAnalysis.leaks_detected || 0) > 0 ? '!' : '✓' }}
                                    </div>
                                </div>

                                <!-- Card 3: AI Driver & Mode Info -->
                                <div class="p-4 bg-[#0a0a20] border border-indigo-500/20 rounded-xl flex items-center justify-between">
                                    <div>
                                        <span class="text-[8px] text-slate-500 tracking-[0.2em] uppercase font-bold">ANALYSIS SCOPE</span>
                                        <div class="text-sm font-black text-indigo-400 font-mono mt-1 uppercase tracking-widest">
                                            {{ mode }} Mode
                                        </div>
                                    </div>
                                    <div class="h-10 w-10 rounded-lg bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-[10px] font-bold text-indigo-400">
                                        SEC.03
                                    </div>
                                </div>

                            </div>

                            <!-- Diagnostic Summary callout -->
                            <div class="p-5 bg-cyan-950/20 border border-cyan-500/20 rounded-xl">
                                <div class="text-[8px] text-cyan-400 tracking-[0.2em] font-bold uppercase mb-1">SYSTEM BRIEF RECOMMENDATION</div>
                                <p class="text-xs text-slate-300 italic font-medium leading-relaxed font-mono">
                                    "{{ aiAnalysis.recommendation || 'No short recommendation provided.' }}"
                                </p>
                            </div>

                            <!-- Detailed Analysis Report -->
                            <div class="border border-white/10 rounded-xl bg-slate-950/40 p-6 space-y-4">
                                <div class="flex items-center justify-between border-b border-white/5 pb-3">
                                    <span class="text-[10px] text-slate-500 tracking-[0.25em] uppercase font-bold font-mono">DETAILED DIAGNOSTIC LOG</span>
                                    <span class="px-2 py-0.5 text-[8px] bg-emerald-500/10 border border-emerald-500/20 rounded text-emerald-400 tracking-wider font-mono">SECURE TRANSMISSION</span>
                                </div>
                                <div class="ai-report-content font-sans overflow-x-auto text-slate-300" v-html="formatAiResponse(aiAnalysis.analysis)"></div>
                            </div>

                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-between p-6 border-t border-cyan-500/20 bg-cyan-950/20 shrink-0">
                        <div class="text-[9px] text-slate-500 uppercase tracking-widest font-mono">
                            AI DIAGNOSTICS &bull; VER 1.0.8 &bull; PT JICOS ERP
                        </div>
                        <div class="flex items-center gap-3">
                            <button 
                                @click="showAiModal = false" 
                                class="px-5 py-2.5 border border-white/10 hover:border-cyan-500/50 bg-white/5 hover:bg-cyan-500/10 text-slate-300 hover:text-cyan-400 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all font-mono"
                            >
                                Close Audit
                            </button>
                            <button 
                                v-if="aiAnalysis"
                                @click="printReport"
                                class="px-5 py-2.5 bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl text-[10px] font-black uppercase tracking-widest transition-all hover:scale-105 shadow-md shadow-cyan-900/40 flex items-center gap-1.5 font-mono"
                            >
                                📊 Print Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Space+Mono:ital,wght@0,400;0,700;1,400&display=swap');

.font-mono {
    font-family: 'Space Mono', monospace;
}

/* Background Effects */
.perspective-grid {
    background-image: 
        linear-gradient(to right, rgba(34, 211, 238, 0.1) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(34, 211, 238, 0.1) 1px, transparent 1px);
    background-size: 50px 50px;
    transform: perspective(600px) rotateX(60deg) translateY(-100px) scale(2);
    animation: grid-move 30s linear infinite;
    transform-origin: top;
}

@keyframes grid-move {
    0% { background-position: 0 0; }
    100% { background-position: 0 50px; }
}

@keyframes float {
    0%, 100% { transform: translate(0, 0); }
    50% { transform: translate(20px, 20px); }
}

/* HUD Styling */
.hud-panel {
    background: rgba(10, 10, 22, 0.8);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    box-shadow: 0 0 40px rgba(0, 0, 0, 0.8);
    transition: all 0.3s ease;
}
.hud-panel:hover {
    border-color: rgba(34, 211, 238, 0.3);
}

/* Text Effects */
.glow-text {
    text-shadow: 0 0 10px currentColor;
}

.pulse-opacity {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
}

/* Custom Scrollbar */
::-webkit-scrollbar {
    width: 6px;
}
::-webkit-scrollbar-track {
    background: #050510;
}
::-webkit-scrollbar-thumb {
    background: #1e293b;
    border-radius: 3px;
}
::-webkit-scrollbar-thumb:hover {
    background: #334155;
}

/* Modal Animations and custom styles */
@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.97); }
    to { opacity: 1; transform: scale(1); }
}
.animate-fade-in {
    animation: fadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes scan {
    0% { transform: translateY(-20px); opacity: 0; }
    10% { opacity: 1; }
    90% { opacity: 1; }
    100% { transform: translateY(85vh); opacity: 0; }
}
.animate-scan {
    animation: scan 8s linear infinite;
}

@keyframes spin-reverse {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(-360deg); }
}
.animate-spin-reverse {
    animation: spin-reverse 2s linear infinite;
}

@keyframes loadingBar {
    0% { transform: translateX(-100%); }
    50% { transform: translateX(0); }
    100% { transform: translateX(100%); }
}
.animate-loading-bar {
    animation: loadingBar 2s ease-in-out infinite;
}

.bg-scanlines {
    background: repeating-linear-gradient(
        0deg,
        rgba(0, 0, 0, 0.15),
        rgba(0, 0, 0, 0.15) 1px,
        transparent 1px,
        transparent 2px
    );
}

.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(10, 10, 22, 0.3);
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(34, 211, 238, 0.2);
    border-radius: 3px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(34, 211, 238, 0.5);
}

/* AI Report HTML Formatting */
.ai-report-content :deep(h1),
.ai-report-content :deep(h2),
.ai-report-content :deep(h3) {
    font-family: 'Space Mono', monospace;
    font-weight: 800;
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
    color: #22d3ee; /* cyan-400 */
    text-shadow: 0 0 8px rgba(34, 211, 238, 0.3);
}
.ai-report-content :deep(h1) {
    font-size: 1.25rem;
    border-bottom: 1px solid rgba(34, 211, 238, 0.2);
    padding-bottom: 0.5rem;
}
.ai-report-content :deep(h2) {
    font-size: 1.1rem;
}
.ai-report-content :deep(h3) {
    font-size: 0.95rem;
}
.ai-report-content :deep(p) {
    font-size: 0.75rem;
    line-height: 1.6;
    color: #cbd5e1; /* slate-300 */
    margin-top: 0.5rem;
    margin-bottom: 0.75rem;
}
.ai-report-content :deep(strong) {
    color: #ffffff;
    font-weight: 700;
}
.ai-report-content :deep(ul),
.ai-report-content :deep(ol) {
    margin-top: 0.5rem;
    margin-bottom: 0.75rem;
    padding-left: 1.25rem;
}
.ai-report-content :deep(li) {
    font-size: 0.75rem;
    line-height: 1.6;
    color: #cbd5e1;
    margin-top: 0.25rem;
    margin-bottom: 0.25rem;
    list-style-type: disc;
}
.ai-report-content :deep(table) {
    width: 100%;
    border-collapse: collapse;
    margin: 1rem 0;
    font-size: 0.7rem;
    background: rgba(10, 10, 22, 0.6);
    border: 1px solid rgba(255, 255, 255, 0.1);
}
.ai-report-content :deep(th),
.ai-report-content :deep(td) {
    padding: 0.5rem 0.75rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
    text-align: left;
}
.ai-report-content :deep(th) {
    background: rgba(34, 211, 238, 0.1);
    color: #22d3ee;
    font-weight: bold;
}
</style>
