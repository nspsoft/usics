<script setup>
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatNumber, formatCurrency, formatNumberFixed } from '@/helpers';
import { 
    ArrowLeftIcon, 
    PencilSquareIcon,
    TrashIcon,
    CubeIcon,
    ClipboardDocumentListIcon,
    CheckCircleIcon,
    ArchiveBoxIcon,
    Bars3Icon,
    CalculatorIcon,
    ListBulletIcon,
    ClockIcon,
    BanknotesIcon,
    CpuChipIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    bom: Object,
});

const activateBom = () => {
    if (confirm('Are you sure you want to activate this BOM?')) {
        router.post(route('manufacturing.boms.activate', props.bom.id));
    }
};

const archiveBom = () => {
    if (confirm('Are you sure you want to archive this BOM? It will no longer be selectable for new WOs.')) {
        router.post(route('manufacturing.boms.archive', props.bom.id));
    }
};

const deleteBom = () => {
    if (confirm(`Are you sure you want to delete this BOM? This action cannot be undone.`)) {
        router.delete(route('manufacturing.boms.destroy', props.bom.id));
    }
};

const getStatusBadge = (status) => {
    const badges = {
        draft: 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30',
        active: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30 font-bold',
        archived: 'bg-amber-500/20 text-amber-400 border-amber-500/30 font-bold',
    };
    return badges[status] || 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30';
};


const materialCost = computed(() => {
    return props.bom.components?.reduce((acc, comp) => {
        return acc + (parseFloat(comp.qty) * parseFloat(comp.product?.cost_price || 0));
    }, 0);
});

const laborCost = computed(() => {
    return props.bom.operations?.reduce((acc, op) => acc + parseFloat(op.labor_cost || 0), 0);
});

const machineCost = computed(() => {
    return props.bom.operations?.reduce((acc, op) => acc + parseFloat(op.machine_cost || 0), 0);
});

const totalCost = computed(() => {
    return materialCost.value + laborCost.value + machineCost.value;
});

const costPerUnit = computed(() => {
    return props.bom.qty > 0 ? totalCost.value / props.bom.qty : 0;
});

const formatShortDate = (value) => {
    if (!value) return '-';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return '-';
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};
</script>

<template>
    <Head :title="`BOM: ${bom.code}`" />
    
    <AppLayout title="Bill of Materials">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center gap-4">
                    <Link 
                        :href="route('manufacturing.boms.index')" 
                        class="p-2.5 rounded-xl glass-card text-slate-500 dark:text-slate-400 font-bold hover:text-slate-900 dark:text-white hover:border-slate-200 dark:border-slate-700 transition-all shadow-sm"
                    >
                        <ArrowLeftIcon class="h-5 w-5" />
                    </Link>
                    <div>
                        <div class="flex items-center gap-3">
                            <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">{{ bom.name }}</h2>
                            <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider" :class="getStatusBadge(bom.status)">
                                {{ bom.status }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1 uppercase tracking-widest font-bold font-mono">CODE: {{ bom.code }} / VERSION: {{ bom.version }}</p>
                        <p class="text-[10px] text-slate-500 font-mono mt-1">CREATED: {{ formatShortDate(bom.created_at) }} | LAST REVISION: {{ formatShortDate(bom.updated_at) }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button 
                        v-if="bom.status === 'draft'"
                        @click="activateBom"
                        class="flex items-center gap-2 rounded-xl bg-emerald-600/10 border border-emerald-500/20 px-4 py-2.5 text-sm font-bold text-emerald-400 hover:bg-emerald-600/20 transition-all font-mono shadow-sm"
                    >
                        <CheckCircleIcon class="h-5 w-5" />
                        ACTIVATE_BOM
                    </button>
                    <button 
                        v-if="bom.status === 'active'"
                        @click="archiveBom"
                        class="flex items-center gap-2 rounded-xl bg-amber-600/10 border border-amber-500/20 px-4 py-2.5 text-sm font-bold text-amber-400 hover:bg-amber-600/20 transition-all font-mono shadow-sm"
                    >
                        <ArchiveBoxIcon class="h-5 w-5" />
                        ARCHIVE_BOM
                    </button>
                    <Link 
                        :href="route('manufacturing.boms.edit', bom.id)"
                        class="flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-4 py-2.5 text-sm font-bold text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:text-white hover:bg-slate-700 transition-all shadow-sm"
                    >
                        <PencilSquareIcon class="h-5 w-5" />
                        Edit Definition
                    </Link>
                    <button 
                        v-if="bom.status === 'draft'"
                        @click="deleteBom"
                        class="p-2.5 rounded-xl bg-red-600/10 border border-red-500/20 text-red-500 hover:bg-red-600/20 transition-all shadow-sm"
                        title="Delete BOM"
                    >
                        <TrashIcon class="h-5 w-5" />
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column: Summary, Components, and Routing -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Finished Product Summary -->
                    <div class="glass-card rounded-3xl p-6 shadow-sm relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-8 opacity-5">
                            <CubeIcon class="h-32 w-32 text-slate-500 dark:text-slate-400" />
                        </div>
                        <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-6 flex items-center gap-2">
                             PRODUCT_OUTPUT_DEFINITION
                        </h3>
                        <div class="relative flex flex-col sm:flex-row gap-8">
                            <div class="flex-1">
                                <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Target Product</div>
                                <div class="text-xl font-bold text-slate-900 dark:text-white">{{ bom.product?.name }}</div>
                                <div class="text-xs text-slate-500 font-mono mt-0.5">{{ bom.product?.sku }}</div>
                            </div>
                            <div class="w-px bg-slate-50 dark:bg-slate-800 hidden sm:block"></div>
                            <div class="flex gap-12">
                                <div>
                                    <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Standard Yield</div>
                                    <div class="text-xl font-bold text-slate-900 dark:text-white font-mono">{{ formatNumber(bom.qty) }}</div>
                                    <div class="text-xs text-slate-500 uppercase font-bold">{{ bom.unit?.name || 'pcs' }}</div>
                                </div>
                                <div>
                                    <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Lead Time</div>
                                    <div class="text-xl font-bold text-slate-900 dark:text-white font-mono">{{ bom.lead_time_days }}</div>
                                    <div class="text-xs text-slate-500 uppercase font-bold">Days</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Components Table -->
                    <div class="glass-card rounded-3xl shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                            <h3 class="text-sm font-bold text-slate-500 uppercase tracking-widest flex items-center gap-2 font-mono">
                                <Bars3Icon class="h-4 w-4" />
                                MATERIAL_REQUIREMENTS_LIST
                            </h3>
                            <span class="text-[10px] font-mono font-bold text-slate-500 bg-slate-50 dark:bg-slate-800 px-3 py-1 rounded-lg">
                                {{ bom.components?.length || 0 }} ITEMS
                            </span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                                <thead>
                                    <tr class="bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50">
                                        <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-500 uppercase tracking-widest">Component</th>
                                        <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-widest" width="120">Required Qty</th>
                                        <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-widest" width="100">Scrap Rate</th>
                                        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-widest">Est. Mat. Cost</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <tr v-for="comp in bom.components" :key="comp.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-bold text-slate-900 dark:text-white">{{ comp.product?.name }}</div>
                                            <div class="text-[10px] text-slate-500 font-mono mt-0.5">{{ comp.product?.sku }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <div class="text-sm font-bold text-slate-200 font-mono">{{ formatNumberFixed(comp.qty, 2) }}</div>
                                            <div class="text-[10px] text-slate-500 uppercase font-bold">{{ comp.unit?.symbol || comp.product?.unit?.symbol || 'pcs' }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span 
                                                class="px-2 py-0.5 rounded-lg text-[10px] font-bold font-mono"
                                                :class="comp.scrap_rate > 0 ? 'bg-emerald-500/10 text-emerald-400' : 'bg-slate-50 dark:bg-slate-800 text-slate-500'"
                                            >
                                                {{ formatNumber(comp.scrap_rate) }}%
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="text-sm font-bold text-slate-600 dark:text-slate-300 font-mono">
                                                Rp {{ formatNumber(parseFloat(comp.qty) * parseFloat(comp.product?.cost_price || 0)) }}
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="!bom.components?.length">
                                        <td colspan="4" class="px-6 py-12 text-center text-slate-600 italic border-t border-slate-200 dark:border-slate-800">No components defined in this recipe.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Multiple Outputs Table (If Any) -->
                    <div v-if="bom.outputs?.length > 0" class="glass-card rounded-3xl shadow-sm overflow-hidden mb-8">
                        <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                            <h3 class="text-sm font-bold text-slate-500 uppercase tracking-widest flex items-center gap-2 font-mono">
                                <CubeIcon class="h-4 w-4" />
                                MULTIPLE_OUTPUTS_LIST
                            </h3>
                            <span class="text-[10px] font-mono font-bold text-slate-500 bg-slate-50 dark:bg-slate-800 px-3 py-1 rounded-lg">
                                {{ bom.outputs?.length }} OUTPUTS
                            </span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                                <thead>
                                    <tr class="bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50">
                                        <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-500 uppercase tracking-widest">Output Product</th>
                                        <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-widest" width="120">Qty Ratio</th>
                                        <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-widest" width="100">Unit</th>
                                        <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-500 uppercase tracking-widest">Notes</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <tr v-for="out in bom.outputs" :key="out.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-bold text-slate-900 dark:text-white">{{ out.product?.name }}</div>
                                            <div class="text-[10px] text-slate-500 font-mono mt-0.5">{{ out.product?.sku }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <div class="text-sm font-bold text-slate-200 font-mono">{{ formatNumberFixed(out.qty_ratio, 2) }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <div class="text-[10px] text-slate-500 uppercase font-bold">{{ out.unit?.symbol || out.product?.unit?.symbol || 'pcs' }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-left">
                                            <div class="text-xs text-slate-500">{{ out.notes || '-' }}</div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Production Routing Section -->
                    <div class="glass-card rounded-3xl shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                            <h3 class="text-sm font-bold text-slate-500 uppercase tracking-widest flex items-center gap-2 font-mono">
                                <ListBulletIcon class="h-4 w-4" />
                                PRODUCTION_ROUTING_STEPS
                            </h3>
                            <span class="text-[10px] font-mono font-bold text-slate-500 bg-slate-50 dark:bg-slate-800 px-3 py-1 rounded-lg">
                                {{ bom.operations?.length || 0 }} OPERATIONS
                            </span>
                        </div>
                        <div class="p-6 space-y-4">
                            <div v-for="(op, index) in bom.operations" :key="op.id" class="relative pl-12 pb-6 border-l-2 border-slate-200 dark:border-slate-800 last:pb-0">
                                <!-- Step Number -->
                                <div class="absolute left-0 top-0 -translate-x-1/2 h-8 w-8 rounded-full bg-white dark:bg-slate-950 border-2 border-slate-200 dark:border-slate-800 flex items-center justify-center text-xs font-black text-slate-500">
                                    {{ index + 1 }}
                                </div>
                                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                    <div>
                                        <h4 class="text-sm font-bold text-slate-900 dark:text-white">{{ op.name }}</h4>
                                        <p v-if="op.description" class="text-xs text-slate-500 mt-1 italic">{{ op.description }}</p>
                                        <div class="flex items-center gap-4 mt-3">
                                            <div class="flex items-center gap-1.5 px-2 py-1 rounded bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 text-[10px] font-bold text-slate-500 dark:text-slate-400">
                                                <ClockIcon class="h-3 w-3" />
                                                {{ op.processing_time_mins }} MINS
                                            </div>
                                            <div class="flex items-center gap-1.5 px-2 py-1 rounded bg-emerald-500/5 text-[10px] font-bold text-emerald-500/70 border border-emerald-500/10">
                                                <BanknotesIcon class="h-3 w-3" />
                                                LABOR: Rp {{ formatNumber(op.labor_cost) }}
                                            </div>
                                            <div class="flex items-center gap-1.5 px-2 py-1 rounded bg-blue-500/5 text-[10px] font-bold text-blue-500/70 border border-blue-500/10">
                                                <CpuChipIcon class="h-3 w-3" />
                                                MACHINE: Rp {{ formatNumber(op.machine_cost) }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Step Subtotal</div>
                                        <div class="text-sm font-bold font-mono text-slate-900 dark:text-white">Rp {{ formatNumber(parseFloat(op.labor_cost) + parseFloat(op.machine_cost)) }}</div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="!bom.operations?.length" class="py-6 text-center text-slate-600 italic">
                                No production steps defined.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Analytics & Cost -->
                <div class="space-y-8">
                    <div class="glass-card rounded-3xl p-6 shadow-sm">
                        <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-6 flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-4">
                            <CalculatorIcon class="h-4 w-4" />
                            TOTAL_COST_ANALYSIS
                        </h3>
                        
                        <div class="space-y-4">
                            <!-- Breakdown -->
                            <div class="space-y-3">
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-slate-500 font-bold uppercase">Material Cost</span>
                                    <span class="text-slate-900 dark:text-white font-mono">Rp {{ formatNumber(materialCost) }}</span>
                                </div>
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-slate-500 font-bold uppercase">Labor Cost</span>
                                    <span class="text-emerald-400 font-mono">Rp {{ formatNumber(laborCost) }}</span>
                                </div>
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-slate-500 font-bold uppercase">Machine Cost</span>
                                    <span class="text-blue-400 font-mono">Rp {{ formatNumber(machineCost) }}</span>
                                </div>
                                <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex justify-between items-center">
                                    <div class="text-[10px] text-slate-500 dark:text-slate-400 uppercase font-bold">Total Process Cost</div>
                                    <div class="text-lg font-bold text-slate-900 dark:text-white font-mono tracking-tight">
                                        Rp {{ formatNumber(totalCost) }}
                                    </div>
                                </div>
                            </div>
                            
                            <div class="p-4 rounded-2xl bg-indigo-500/5 border border-indigo-500/10 mt-4">
                                <div class="text-[10px] text-indigo-400 uppercase font-bold mb-1">Standard Cost / {{ bom.unit?.symbol || 'Unit' }}</div>
                                <div class="text-xl font-bold text-indigo-300 font-mono">
                                    Rp {{ formatNumber(costPerUnit) }}
                                </div>
                            </div>

                            <div class="pt-4 mt-6 border-t border-slate-200 dark:border-slate-800 space-y-4">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-slate-500 font-bold uppercase tracking-wider">Created Date</span>
                                    <span class="text-slate-600 dark:text-slate-300">{{ new Date(bom.created_at).toLocaleDateString('id-ID', {day: 'numeric', month: 'short', year: 'numeric'}) }}</span>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-slate-500 font-bold uppercase tracking-wider">Last Revision</span>
                                    <span class="text-slate-600 dark:text-slate-300">{{ bom.updated_at ? new Date(bom.updated_at).toLocaleDateString('id-ID', {day: 'numeric', month: 'short', year: 'numeric'}) : '-' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="bom.description" class="glass-card rounded-3xl p-6 shadow-sm">
                        <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-4 border-b border-slate-200 dark:border-slate-800 pb-4 font-mono">Process Instructions</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed italic">
                            "{{ bom.description }}"
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script>
// Needed for computed properties that were moved to <script setup>
</script>



