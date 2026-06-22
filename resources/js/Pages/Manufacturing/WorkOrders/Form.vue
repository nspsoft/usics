<script setup>
import { computed, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    ArrowLeftIcon, 
    CogIcon,
    CubeIcon,
} from '@heroicons/vue/24/outline';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    workOrder: Object,
    woNumber: String,
    boms: Array,
    warehouses: Array,
    defaultMaterialWarehouseId: [Number, String, null],
    suppliers: Array,
});

const isEditing = computed(() => !!props.workOrder);

const getFinishedGoodsWarehouseId = () => {
    if (props.workOrder?.warehouse_id) return props.workOrder.warehouse_id;
    const found = props.warehouses?.find(w => 
        w.name && w.name.toLowerCase().replace(/[^a-z0-9]/g, '') === 'finishedgoods'
    );
    return found ? found.id : '';
};

const form = useForm({
    wo_number: props.workOrder?.wo_number || props.woNumber || '',
    bom_id: props.workOrder?.bom_id || '',
    warehouse_id: getFinishedGoodsWarehouseId(),
    material_warehouse_id: props.workOrder?.material_warehouse_id || props.defaultMaterialWarehouseId || '',
    qty_planned: props.workOrder?.qty_planned || 1,
    planned_start: props.workOrder?.planned_start?.split('T')[0] || new Date().toISOString().split('T')[0],
    planned_end: props.workOrder?.planned_end?.split('T')[0] || '',
    priority: props.workOrder?.priority || 'normal',
    notes: props.workOrder?.notes || '',
    production_type: props.workOrder?.production_type || 'internal',
    supplier_id: props.workOrder?.supplier_id || '',
});

const selectedBom = computed(() => {
    if (!form.bom_id) return null;
    return props.boms.find(b => b.id === parseInt(form.bom_id));
});

const bomOptions = computed(() =>
    props.boms.map(b => ({
        id: b.id,
        label: `${b.name} (${b.product?.name || '-'})`,
    }))
);

const priorities = [
    { value: 'low', label: 'Low', color: 'text-slate-500 dark:text-slate-400' },
    { value: 'normal', label: 'Normal', color: 'text-blue-400' },
    { value: 'high', label: 'High', color: 'text-amber-400' },
    { value: 'urgent', label: 'Urgent', color: 'text-red-400' },
];

const submit = () => {
    if (isEditing.value) {
        form.put(route('manufacturing.work-orders.update', props.workOrder.id));
    } else {
        form.post(route('manufacturing.work-orders.store'));
    }
};

// Set default planned_end to 7 days after planned_start
watch(() => form.planned_start, (newVal) => {
    if (newVal && !form.planned_end) {
        const start = new Date(newVal);
        start.setDate(start.getDate() + 7);
        form.planned_end = start.toISOString().split('T')[0];
    }
});
</script>

<template>
    <Head :title="isEditing ? 'Edit Work Order' : 'Create Work Order'" />
    
    <AppLayout :title="isEditing ? 'Edit Work Order' : 'Create Work Order'">
        <div class="max-w-full px-4 sm:px-6 lg:px-8 mx-auto">
            <!-- Header -->
            <div class="flex items-center gap-4 mb-8">
                <Link 
                    :href="route('manufacturing.work-orders.index')" 
                    class="p-2.5 rounded-xl glass-card text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:border-slate-200 dark:border-slate-700 transition-all"
                >
                    <ArrowLeftIcon class="h-5 w-5" />
                </Link>
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
                        {{ isEditing ? 'Edit Work Order' : 'New Work Order' }}
                    </h2>
                    <p class="text-xs text-slate-500 mt-1 uppercase tracking-widest font-bold">
                        {{ form.wo_number }}
                    </p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-8">
                <!-- BOM Selection -->
                <div class="glass-card rounded-3xl p-6 shadow-sm relative z-50">
                    <h3 class="text-sm font-bold text-slate-500 uppercase tracking-widest mb-6 border-b border-slate-200 dark:border-slate-800 pb-4 flex items-center gap-2">
                        <CubeIcon class="h-4 w-4" />
                        Product & BOM
                    </h3>
                    
                    <div class="space-y-6">
                        <div class="relative z-50">
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Bill of Materials *</label>
                            <SearchableSelect
                                v-if="!isEditing"
                                v-model="form.bom_id"
                                :options="bomOptions"
                                placeholder="Select BOM..."
                            />
                            <select
                                v-else
                                v-model="form.bom_id"
                                class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-3 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                disabled
                                required
                            >
                                <option value="">Select BOM...</option>
                                <option v-for="bom in boms" :key="bom.id" :value="bom.id">
                                    {{ bom.name }} ({{ bom.product?.name }})
                                </option>
                            </select>
                            <div v-if="form.errors.bom_id" class="text-red-400 text-xs mt-1">{{ form.errors.bom_id }}</div>
                        </div>

                        <!-- Selected BOM Info -->
                        <div v-if="selectedBom" class="bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 rounded-2xl p-4 border border-slate-200 dark:border-slate-700">
                            <div class="flex items-center gap-4">
                                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-cyan-600/20 to-blue-600/20 border border-cyan-500/30">
                                    <CubeIcon class="h-6 w-6 text-cyan-400" />
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-slate-900 dark:text-white">{{ selectedBom.product?.name }}</div>
                                    <div class="text-xs text-slate-500 font-mono">{{ selectedBom.product?.sku }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Production Details -->
                <div class="glass-card rounded-3xl p-6 shadow-sm relative z-0">
                    <h3 class="text-sm font-bold text-slate-500 uppercase tracking-widest mb-6 border-b border-slate-200 dark:border-slate-800 pb-4 flex items-center gap-2">
                        <CogIcon class="h-4 w-4" />
                        Production Details
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6 pb-6 border-b border-slate-200 dark:border-slate-800">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Production Type *</label>
                            <div class="flex gap-4">
                                <label class="flex-1 cursor-pointer">
                                    <input type="radio" v-model="form.production_type" value="internal" class="sr-only peer">
                                    <div class="py-3 px-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 peer-checked:border-blue-500 peer-checked:bg-blue-500/10 peer-checked:text-blue-400 text-center font-bold text-sm transition-all">
                                        Internal
                                    </div>
                                </label>
                                <label class="flex-1 cursor-pointer">
                                    <input type="radio" v-model="form.production_type" value="subcontract" class="sr-only peer">
                                    <div class="py-3 px-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 peer-checked:border-amber-500 peer-checked:bg-amber-500/10 peer-checked:text-amber-400 text-center font-bold text-sm transition-all">
                                        Subcontract
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div v-if="form.production_type === 'subcontract'">
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Subcontractor (Vendor) *</label>
                            <select 
                                v-model="form.supplier_id"
                                class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-3 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500/50"
                                required
                            >
                                <option value="">Select Supplier...</option>
                                <option v-for="s in suppliers" :key="s.id" :value="s.id">
                                    {{ s.name }}
                                </option>
                            </select>
                            <div v-if="form.errors.supplier_id" class="text-red-400 text-xs mt-1">{{ form.errors.supplier_id }}</div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">WO Number</label>
                            <input 
                                v-model="form.wo_number"
                                type="text"
                                class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-3 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 font-mono"
                                readonly
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Planned Quantity *</label>
                            <input 
                                v-model="form.qty_planned"
                                type="number"
                                step="0.0001"
                                min="0.0001"
                                class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-3 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 font-mono"
                                required
                            />
                            <div v-if="form.errors.qty_planned" class="text-red-400 text-xs mt-1">{{ form.errors.qty_planned }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Output Warehouse *</label>
                            <select 
                                v-model="form.warehouse_id"
                                class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-3 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                required
                            >
                                <option value="">Select Warehouse...</option>
                                <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">
                                    {{ wh.name }}
                                </option>
                            </select>
                            <div v-if="form.errors.warehouse_id" class="text-red-400 text-xs mt-1">{{ form.errors.warehouse_id }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Material Warehouse (Raw Material) *</label>
                            <select 
                                v-model="form.material_warehouse_id"
                                class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-3 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                required
                            >
                                <option value="">Select Warehouse...</option>
                                <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">
                                    {{ wh.name }}
                                </option>
                            </select>
                            <div v-if="form.errors.material_warehouse_id" class="text-red-400 text-xs mt-1">{{ form.errors.material_warehouse_id }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Priority *</label>
                            <select 
                                v-model="form.priority"
                                class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-3 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                required
                            >
                                <option v-for="p in priorities" :key="p.value" :value="p.value">
                                    {{ p.label }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Planned Start *</label>
                            <input
                                v-model="form.planned_start"
                                type="date"
                                class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-3 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                required
                            />
                            <div v-if="form.errors.planned_start" class="text-red-400 text-xs mt-1">{{ form.errors.planned_start }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Planned End *</label>
                            <input
                                v-model="form.planned_end"
                                type="date"
                                class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-3 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                required
                            />
                            <div v-if="form.errors.planned_end" class="text-red-400 text-xs mt-1">{{ form.errors.planned_end }}</div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Notes</label>
                        <textarea 
                            v-model="form.notes"
                            rows="3"
                            placeholder="Optional production notes..."
                            class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-3 px-4 text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50 resize-none"
                        ></textarea>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex justify-end gap-4">
                    <Link 
                        :href="route('manufacturing.work-orders.index')"
                        class="px-6 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-semibold hover:bg-slate-700 transition-all"
                    >
                        Cancel
                    </Link>
                    <button 
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-3 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 text-white dark:text-white font-semibold shadow-lg shadow-blue-500/25 hover:from-blue-500 hover:to-blue-400 transition-all disabled:opacity-50"
                    >
                        {{ form.processing ? 'Saving...' : (isEditing ? 'Update Work Order' : 'Create Work Order') }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>



