<script setup>
import { computed, ref, onMounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    ArrowLeftIcon, 
    PlusIcon, 
    TrashIcon, 
    CubeIcon, 
    DocumentTextIcon,
    Bars3Icon,
    ExclamationTriangleIcon,
    SparklesIcon,
    XMarkIcon,
    ArrowRightIcon,
    ClockIcon,
    BanknotesIcon,
    CpuChipIcon,
    ListBulletIcon,
    ArrowPathRoundedSquareIcon,
    CheckCircleIcon
} from '@heroicons/vue/24/outline';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    bom: Object,
    products: Array,
    materials: Array,
    units: Array,
});

const isEditing = computed(() => !!props.bom);

const productOptions = computed(() => 
    props.products.map(p => ({
        id: p.id,
        label: `${p.name} (${p.sku})`
    }))
);

const materialOptions = computed(() => 
    props.materials.map(m => ({
        id: m.id,
        label: `${m.name} (${m.sku})`
    }))
);

const getUnitIdForMaterial = (materialId) => {
    const material = props.materials.find(m => m.id == materialId);
    return material?.unit_id ?? '';
};

// Tutorial State
const showTutorial = ref(false);
const currentStep = ref(0);
const tutorialSteps = [
    {
        title: 'Step 1: Identitas BOM',
        content: 'Isi kode BOM (otomatis disarankan) dan tentukan Nama BOM agar mudah dicari. Jangan lupa pilih **Output Product** atau hasil jadi dari proses ini.',
        target: 'header-section',
        animation: 'header'
    },
    {
        title: 'Step 2: Daftar Material',
        content: 'Klik tombol **"Add Item"** untuk menambah baris material. Pilih material pendukung, masukkan jumlahnya (qty), dan estimasikan % scrap (waste) jika ada.',
        target: 'components-section',
        animation: 'components'
    },
    {
        title: 'Step 3: Rute Produksi (Routing)',
        content: 'Di bagian ini, Bapak bisa mencatat langkah-langkah kerja produksi. Masukkan estimasi waktu, biaya tenaga kerja, dan biaya mesin untuk setiap langkah.',
        target: 'operations-section',
        animation: 'operations'
    },
    {
        title: 'Step 4: Simpan Definisi',
        content: 'Setelah semua material dan rute terisi, klik **"Save BOM Definition"**. BOM akan tersimpan sebagai Draft.',
        target: 'submit-section',
        animation: 'save'
    }
];

const nextStep = () => {
    if (currentStep.value < tutorialSteps.length - 1) {
        currentStep.value++;
    } else {
        showTutorial.value = false;
        currentStep.value = 0;
    }
};

const closeTutorial = () => {
    showTutorial.value = false;
    currentStep.value = 0;
};



const wasteCalculation = computed(() => {
    if (!form.product_id) return null;
    const motherCoil = props.materials.find(m => m.id === form.product_id);
    if (!motherCoil || !motherCoil.width || motherCoil.width <= 0) return null;

    let totalWidth = 0;
    form.outputs.forEach(out => {
        if (out.product_id) {
            const outProduct = props.materials.find(m => m.id === out.product_id);
            if (outProduct && outProduct.width && outProduct.width > 0) {
                totalWidth += (outProduct.width * (parseInt(out.slit_count) || 1));
            }
        }
    });
    
    if (totalWidth === 0) return null;

    const waste = motherCoil.width - totalWidth;
    const percentage = (waste / motherCoil.width) * 100;
    
    return {
        motherWidth: motherCoil.width,
        totalOutputWidth: totalWidth,
        waste: waste,
        percentage: percentage.toFixed(2),
        isInvalid: percentage > 1.0 || waste < 0
    };
});

const form = useForm({
    code: props.bom?.code || '',
    name: props.bom?.name || '',
    product_id: props.bom?.product_id || '',
    qty: props.bom?.qty || 1,
    unit_id: props.bom?.unit_id || '',
    version: props.bom?.version || '1.0',
    description: props.bom?.description || '',
    lead_time_days: props.bom?.lead_time_days || 0,
    components: props.bom?.components?.map(c => ({
        id: c.id,
        product_id: c.product_id,
        qty: parseFloat(c.qty),
        unit_id: c.unit_id,
        scrap_rate: parseFloat(c.scrap_rate),
    })) || [{ product_id: '', qty: 1, unit_id: '', scrap_rate: 0 }],
    outputs: props.bom?.outputs?.map(o => ({
        id: o.id,
        product_id: o.product_id,
        qty_ratio: parseFloat(o.qty_ratio),
        slit_count: o.slit_count || 1,
        unit_id: o.unit_id,
        notes: o.notes,
    })) || [],
    operations: props.bom?.operations?.map(o => ({
        id: o.id,
        name: o.name,
        setup_time_mins: o.setup_time_mins,
        processing_time_mins: o.processing_time_mins,
        labor_cost: parseFloat(o.labor_cost),
        machine_cost: parseFloat(o.machine_cost),
        description: o.description,
    })) || [],
});

const addOperation = () => {
    form.operations.push({
        name: '',
        setup_time_mins: 0,
        processing_time_mins: 0,
        labor_cost: 0,
        machine_cost: 0,
        description: '',
    });
};

const removeOperation = (index) => {
    form.operations.splice(index, 1);
};

const addOutput = () => {
    form.outputs.push({
        product_id: '',
        qty_ratio: 1,
        slit_count: 1,
        unit_id: '',
        notes: '',
    });
};

const removeOutput = (index) => {
    form.outputs.splice(index, 1);
};

const syncOutputUnitFromProduct = (index) => {
    const out = form.outputs[index];
    if (!out) return;

    if (!out.product_id) {
        out.unit_id = '';
        return;
    }

    const product = props.products.find(p => p.id == out.product_id) || props.materials.find(m => m.id == out.product_id);
    out.unit_id = product?.unit_id || '';
};

const addComponent = () => {
    form.components.push({
        product_id: '',
        qty: 1,
        unit_id: '',
        scrap_rate: 0,
    });
};

const syncComponentUnitFromMaterial = (index) => {
    const comp = form.components[index];
    if (!comp) return;

    if (!comp.product_id) {
        comp.unit_id = '';
        return;
    }

    const unitId = getUnitIdForMaterial(comp.product_id);
    comp.unit_id = unitId || '';
};

const removeComponent = (index) => {
    if (form.components.length > 1) {
        form.components.splice(index, 1);
    }
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('manufacturing.boms.update', props.bom.id));
    } else {
        form.post(route('manufacturing.boms.store'));
    }
};

onMounted(() => {
    if (!isEditing.value && !form.code) {
        form.code = 'BOM-' + Math.random().toString(36).substring(2, 8).toUpperCase();
    }

    form.components.forEach((_, index) => {
        if (form.components[index]?.product_id && !form.components[index]?.unit_id) {
            syncComponentUnitFromMaterial(index);
        }
    });

    form.outputs.forEach((_, index) => {
        if (form.outputs[index]?.product_id && !form.outputs[index]?.unit_id) {
            syncOutputUnitFromProduct(index);
        }
    });
});
</script>

<template>
    <Head :title="isEditing ? 'Edit BOM' : 'Create BOM'" />
    
    <AppLayout :title="isEditing ? 'Edit BOM' : 'Create BOM'">
        <div class="max-w-full px-4 sm:px-6 lg:px-8 mx-auto">
            <!-- Header -->
            <div class="flex items-center gap-4 mb-8">
                <Link 
                    :href="route('manufacturing.boms.index')" 
                    class="p-2.5 rounded-xl glass-card text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:border-slate-200 dark:border-slate-700 transition-all"
                >
                    <ArrowLeftIcon class="h-5 w-5" />
                </Link>
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
                        {{ isEditing ? 'Edit Bill of Materials' : 'New Bill of Materials' }}
                    </h2>
                    <p class="text-xs text-slate-500 mt-1 uppercase tracking-widest font-bold">
                        Definition for Manufacturing Process
                    </p>
                </div>

                <div class="ml-auto">
                    <button 
                        @click="showTutorial = true"
                        type="button"
                        class="flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-400 hover:bg-blue-500/20 transition-all font-bold text-sm shadow-lg shadow-blue-500/5 animate-pulse"
                    >
                        <SparklesIcon class="h-5 w-5" />
                        Cara Buat BOM?
                    </button>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-8">
                <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">
                    <!-- Left Column: BOM Header Info -->
                    <div class="xl:col-span-4 space-y-8" id="header-section">
                        <div class="glass-card rounded-3xl p-6 shadow-sm relative overflow-hidden" :class="{'ring-2 ring-blue-500 shadow-2xl shadow-blue-500/20 z-10': showTutorial && tutorialSteps[currentStep].target === 'header-section'}">
                            <h3 class="text-sm font-bold text-slate-500 uppercase tracking-widest mb-6 border-b border-slate-200 dark:border-slate-800 pb-4 flex items-center gap-2">
                                <CubeIcon class="h-4 w-4" />
                                Header Info
                            </h3>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">BOM Code *</label>
                                    <input 
                                        v-model="form.code"
                                        type="text"
                                        class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-3 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 font-mono"
                                        placeholder="e.g. BOM-FG-001"
                                        required
                                    />
                                    <div v-if="form.errors.code" class="text-red-400 text-xs mt-1">{{ form.errors.code }}</div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">BOM Name *</label>
                                    <input 
                                        v-model="form.name"
                                        type="text"
                                        class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-3 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                        placeholder="e.g. BOM for Assembly A"
                                        required
                                    />
                                    <div v-if="form.errors.name" class="text-red-400 text-xs mt-1">{{ form.errors.name }}</div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Output Product *</label>
                                    <SearchableSelect 
                                        v-model="form.product_id"
                                        :options="productOptions"
                                        placeholder="Search product..."
                                        :disabled="isEditing"
                                    />
                                    <div v-if="form.errors.product_id" class="text-red-400 text-xs mt-1">{{ form.errors.product_id }}</div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Yield Qty *</label>
                                        <input 
                                            v-model="form.qty"
                                            type="number"
                                            step="0.01"
                                            class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-3 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 font-mono text-center"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Unit</label>
                                        <select 
                                            v-model="form.unit_id"
                                            class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-3 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                        >
                                            <option value="">Select Unit...</option>
                                            <option v-for="u in units" :key="u.id" :value="u.id">
                                                {{ u.name }}
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Version</label>
                                    <input 
                                        v-model="form.version"
                                        type="text"
                                        class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-3 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="glass-card rounded-3xl p-6 shadow-sm">
                            <h3 class="text-sm font-bold text-slate-500 uppercase tracking-widest mb-4 border-b border-slate-200 dark:border-slate-800 pb-4 flex items-center gap-2">
                                <DocumentTextIcon class="h-4 w-4" />
                                Notes & Description
                            </h3>
                            <textarea 
                                v-model="form.description"
                                rows="4"
                                placeholder="Additional details or recipe instructions..."
                                class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-3 px-4 text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50 resize-none"
                            ></textarea>
                        </div>
                    </div>

                    <!-- Right Column: Components List -->
                    <div class="xl:col-span-8 space-y-8" id="components-section">
                        <div class="glass-card rounded-3xl shadow-sm !overflow-visible relative z-30" :class="{'ring-2 ring-blue-500 shadow-2xl shadow-blue-500/20 z-10': showTutorial && tutorialSteps[currentStep].target === 'components-section'}">
                            <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                                <h3 class="text-sm font-bold text-slate-500 uppercase tracking-widest flex items-center gap-2">
                                    <Bars3Icon class="h-4 w-4" />
                                    BOM_COMPONENTS_LIST
                                </h3>
                                <button 
                                    type="button"
                                    @click="addComponent"
                                    class="inline-flex items-center gap-2 rounded-xl bg-blue-600/10 px-3 py-1.5 text-xs font-bold text-blue-400 hover:bg-blue-600/20 transition-all border border-blue-500/20"
                                >
                                    <PlusIcon class="h-4 w-4" />
                                    Add Item
                                </button>
                            </div>

                            <div class="custom-scrollbar relative !overflow-visible">
                                <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                                    <thead class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900 shadow-sm">
                                        <tr class="bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50">
                                            <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-500 uppercase tracking-widest">Material / Component</th>
                                            <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-widest" width="120">Qty</th>
                                            <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-widest" width="120">Unit</th>
                                            <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-widest" width="100">Scrap %</th>
                                            <th class="px-6 py-4 text-right" width="60"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                        <tr v-for="(comp, index) in form.components" :key="index" class="relative hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors" :style="{ zIndex: 100 - index }">
                                            <td class="px-6 py-3">
                                                <SearchableSelect 
                                                    v-model="comp.product_id"
                                                    :options="materialOptions"
                                                    placeholder="Search material..."
                                                    @change="() => syncComponentUnitFromMaterial(index)"
                                                />
                                            </td>
                                            <td class="px-6 py-3">
                                                <input 
                                                    v-model="comp.qty"
                                                    type="number"
                                                    step="0.01"
                                                    class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 py-2.5 px-3 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 font-mono text-center"
                                                    required
                                                />
                                            </td>
                                            <td class="px-6 py-3 text-center">
                                                <select 
                                                    v-model="comp.unit_id"
                                                    class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 py-2.5 px-3 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 transition-all font-bold"
                                                >
                                                    <option v-for="u in units" :key="u.id" :value="u.id">
                                                        {{ u.symbol }}
                                                    </option>
                                                </select>
                                            </td>
                                            <td class="px-6 py-3">
                                                <input 
                                                    v-model="comp.scrap_rate"
                                                    type="number"
                                                    step="0.01"
                                                    class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 py-2.5 px-3 text-sm text-emerald-400 focus:ring-2 focus:ring-emerald-500/50 font-mono text-center bg-emerald-500/5"
                                                />
                                            </td>
                                            <td class="px-6 py-3 text-right">
                                                <button 
                                                    type="button"
                                                    @click="removeComponent(index)"
                                                    class="p-2 rounded-lg text-slate-500 hover:text-red-400 hover:bg-red-500/10 transition-all"
                                                >
                                                    <TrashIcon class="h-4 w-4" />
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div v-if="form.errors.components" class="p-6 bg-red-500/5 text-red-400 text-xs border-t border-red-500/20">
                                <div class="flex items-center gap-2">
                                    <ExclamationTriangleIcon class="h-4 w-4" />
                                    {{ form.errors.components }}
                                </div>
                            </div>
                        </div>

                        <!-- Multiple Outputs Section -->
                        <div class="glass-card rounded-3xl shadow-sm overflow-hidden relative z-10" id="outputs-section">
                            <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-white dark:bg-slate-950/50">
                                <h3 class="text-sm font-bold text-slate-500 uppercase tracking-widest flex items-center gap-2">
                                    <CubeIcon class="h-4 w-4" />
                                    MULTIPLE_OUTPUTS_&_BYPRODUCTS
                                </h3>
                                <button 
                                    type="button"
                                    @click="addOutput"
                                    class="inline-flex items-center gap-2 rounded-xl bg-indigo-600/10 px-3 py-1.5 text-xs font-bold text-indigo-400 hover:bg-indigo-600/20 transition-all border border-indigo-500/20"
                                >
                                    <PlusIcon class="h-4 w-4" />
                                    Add Output
                                </button>
                            </div>

                            <div class="p-6 space-y-4">
                                <div v-for="(out, index) in form.outputs" :key="index" class="group/out relative glass-card rounded-2xl p-5 hover:border-slate-200 dark:border-slate-700 transition-all">
                                    <div class="flex flex-col md:flex-row gap-6">
                                        <!-- Index -->
                                        <div class="flex-shrink-0">
                                            <div class="h-10 w-10 rounded-xl glass-card flex items-center justify-center text-xs font-black text-slate-500 group-hover/out:text-indigo-400 group-hover/out:border-indigo-500/30 transition-all">
                                                {{ index + 1 }}
                                            </div>
                                        </div>

                                        <!-- Core Fields -->
                                        <div class="flex-grow grid grid-cols-1 md:grid-cols-12 gap-4">
                                            <!-- Product -->
                                            <div class="md:col-span-5">
                                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Output Product</label>
                                                <SearchableSelect
                                                    v-model="out.product_id"
                                                    :options="[...products, ...materials]"
                                                    labelKey="name"
                                                    valueKey="id"
                                                    placeholder="Select Product/By-Product"
                                                    @update:modelValue="syncOutputUnitFromProduct(index)"
                                                    class="w-full"
                                                />
                                            </div>

                                            <!-- Qty Ratio -->
                                            <div class="md:col-span-2">
                                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Qty Ratio</label>
                                                <input 
                                                    v-model.number="out.qty_ratio"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    class="w-full rounded-xl border-0 bg-white dark:bg-slate-950 py-2 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50"
                                                    placeholder="1.00"
                                                />
                                            </div>

                                            <!-- Unit -->
                                            <div class="md:col-span-2">
                                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Unit</label>
                                                <select 
                                                    v-model="out.unit_id"
                                                    class="w-full rounded-xl border-0 bg-white dark:bg-slate-950 py-2 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50"
                                                >
                                                    <option value="" disabled>Unit</option>
                                                    <option v-for="unit in units" :key="unit.id" :value="unit.id">
                                                        {{ unit.symbol }} - {{ unit.name }}
                                                    </option>
                                                </select>
                                            </div>

                                            <!-- Notes -->
                                            <div class="md:col-span-3">
                                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Notes</label>
                                                <input 
                                                    v-model="out.notes"
                                                    type="text"
                                                    class="w-full rounded-xl border-0 bg-white dark:bg-slate-950 py-2 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50"
                                                    placeholder="e.g. Baby Coil / Scrap"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Delete Button -->
                                    <button 
                                        type="button"
                                        @click="removeOutput(index)"
                                        class="absolute -top-3 -right-3 h-8 w-8 rounded-full bg-red-500 text-white shadow-lg flex items-center justify-center hover:bg-red-600 transition-all hover:scale-110 opacity-0 group-hover/out:opacity-100"
                                    >
                                        <TrashIcon class="h-4 w-4" />
                                    </button>
                                </div>
                                
                                <div v-if="!form.outputs.length" class="text-center py-8">
                                    <div class="inline-flex items-center justify-center h-12 w-12 rounded-full bg-slate-100 dark:bg-slate-800 mb-3">
                                        <CubeIcon class="h-6 w-6 text-slate-400" />
                                    </div>
                                    <p class="text-sm font-bold text-slate-500">No extra outputs defined.</p>
                                    <p class="text-xs text-slate-400 mt-1">If this process only has one main product, leave this empty.</p>
                                </div>
                            </div>

                            <div v-if="form.errors.outputs" class="p-6 bg-red-500/5 text-red-400 text-xs border-t border-red-500/20">
                                <div class="flex items-center gap-2">
                                    <ExclamationTriangleIcon class="h-4 w-4" />
                                    {{ form.errors.outputs }}
                                </div>
                            </div>
                        </div>

                        <!-- Routing Section -->
                        <div class="glass-card rounded-3xl shadow-sm overflow-hidden relative z-10" id="operations-section" :class="{'ring-2 ring-blue-500 shadow-2xl shadow-blue-500/20 z-10': showTutorial && tutorialSteps[currentStep].target === 'operations-section'}">
                            <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-white dark:bg-slate-950/50">
                                <h3 class="text-sm font-bold text-slate-500 uppercase tracking-widest flex items-center gap-2">
                                    <ListBulletIcon class="h-4 w-4" />
                                    PRODUCTION_ROUTING_&_STEPS
                                </h3>
                                <button 
                                    type="button"
                                    @click="addOperation"
                                    class="inline-flex items-center gap-2 rounded-xl bg-indigo-600/10 px-3 py-1.5 text-xs font-bold text-indigo-400 hover:bg-indigo-600/20 transition-all border border-indigo-500/20"
                                >
                                    <PlusIcon class="h-4 w-4" />
                                    Add Operation
                                </button>
                            </div>

                            <div class="p-6 space-y-4">
                                <div v-for="(op, index) in form.operations" :key="index" class="group/op relative glass-card rounded-2xl p-5 hover:border-slate-200 dark:border-slate-700 transition-all">
                                    <div class="flex flex-col md:flex-row gap-6">
                                        <!-- Step Number -->
                                        <div class="flex-shrink-0">
                                            <div class="h-10 w-10 rounded-xl glass-card flex items-center justify-center text-xs font-black text-slate-500 group-hover/op:text-indigo-400 group-hover/op:border-indigo-500/30 transition-all">
                                                {{ index + 1 }}
                                            </div>
                                        </div>

                                        <!-- Core Step Info -->
                                        <div class="flex-grow grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div class="md:col-span-2">
                                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Step Name / Station</label>
                                                <input 
                                                    v-model="op.name"
                                                    type="text"
                                                    class="w-full rounded-xl border-0 bg-white dark:bg-slate-950 py-2 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50"
                                                    placeholder="e.g. CNC Cutting Machine #1"
                                                />
                                            </div>

                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 flex items-center gap-1">
                                                    <ClockIcon class="h-3 w-3" />
                                                    Process Time (Mins)
                                                </label>
                                                <input 
                                                    v-model="op.processing_time_mins"
                                                    type="number"
                                                    class="w-full rounded-xl border-0 bg-white dark:bg-slate-950 py-2 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50 font-mono"
                                                    placeholder="Mins"
                                                />
                                            </div>

                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 flex items-center gap-1">
                                                    <BanknotesIcon class="h-3 w-3" />
                                                    Labor Cost (Rp)
                                                </label>
                                                <input 
                                                    v-model="op.labor_cost"
                                                    type="number"
                                                    class="w-full rounded-xl border-0 bg-white dark:bg-slate-950 py-2 px-4 text-sm text-emerald-400 focus:ring-2 focus:ring-emerald-500/50 font-mono bg-emerald-500/5"
                                                />
                                            </div>
                                        </div>

                                        <!-- Machine Cost -->
                                        <div class="w-full md:w-32">
                                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 flex items-center gap-1">
                                                <CpuChipIcon class="h-3 w-3" />
                                                Machine (Rp)
                                            </label>
                                            <input 
                                                v-model="op.machine_cost"
                                                type="number"
                                                class="w-full rounded-xl border-0 bg-white dark:bg-slate-950 py-2 px-4 text-sm text-blue-400 focus:ring-2 focus:ring-blue-500/50 font-mono bg-blue-500/5"
                                            />
                                        </div>

                                        <!-- Delete Button -->
                                        <div class="flex items-end">
                                            <button 
                                                type="button"
                                                @click="removeOperation(index)"
                                                class="p-2.5 rounded-xl text-slate-600 hover:text-red-400 hover:bg-red-500/10 transition-all border border-transparent hover:border-red-500/20"
                                            >
                                                <TrashIcon class="h-5 w-5" />
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="!form.operations.length" class="py-12 text-center border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-3xl">
                                    <ListBulletIcon class="h-12 w-12 text-slate-800 mx-auto mb-4" />
                                    <p class="text-sm text-slate-500 italic">No production steps defined yet. Click "Add Operation" to start.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Section -->
                        <div class="flex justify-end gap-4 mt-8" id="submit-section">
                            <Link 
                                :href="route('manufacturing.boms.index')"
                                class="px-6 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-semibold hover:bg-slate-700 transition-all"
                            >
                                Cancel
                            </Link>
                            <button 
                                type="submit"
                                :disabled="form.processing || (wasteCalculation && wasteCalculation.isInvalid)"
                                class="px-8 py-3 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 text-white dark:text-white font-semibold shadow-lg shadow-blue-500/25 hover:from-blue-500 hover:to-blue-400 transition-all disabled:opacity-50 flex items-center gap-2"
                                :class="{'ring-4 ring-white': showTutorial && tutorialSteps[currentStep].target === 'submit-section'}"
                            >
                                <ArrowPathRoundedSquareIcon v-if="form.processing" class="h-5 w-5 animate-spin" />
                                {{ form.processing ? 'Saving...' : (isEditing ? 'Update BOM Definition' : 'Save BOM Definition') }}
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Tutorial Overlay -->
        <div v-if="showTutorial" class="fixed inset-0 z-[100] flex items-center justify-center bg-white dark:bg-slate-950/60 backdrop-blur-sm p-4">
            <div class="glass-card w-full max-w-lg rounded-[2.5rem] shadow-2xl overflow-hidden animate-in zoom-in duration-300">
                <div class="p-8">
                    <div class="flex items-center justify-between mb-8">
                        <div class="flex items-center gap-3">
                            <div class="p-2 rounded-xl bg-blue-500 text-slate-900 dark:text-white shadow-lg shadow-blue-500/30">
                                <SparklesIcon class="h-6 w-6" />
                            </div>
                            <h4 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Interactive Guide</h4>
                        </div>
                        <button @click="closeTutorial" class="p-2 text-slate-500 hover:text-slate-900 dark:text-white transition-colors">
                            <XMarkIcon class="h-6 w-6" />
                        </button>
                    </div>

                    <!-- Step Image Placeholder (Animated Icon) -->
                    <div class="aspect-video mb-8 rounded-3xl bg-slate-50 dark:bg-slate-800 flex items-center justify-center relative overflow-hidden group">
                        <div class="absolute inset-0 bg-gradient-to-br from-blue-600/5 to-transparent"></div>
                        
                        <!-- Step 1 Animation -->
                        <div v-if="currentStep === 0" class="text-center animate-in slide-in-from-bottom duration-500">
                             <div class="relative inline-block mb-4">
                                <div class="h-20 w-32 bg-slate-700 rounded-xl border border-slate-600 animate-pulse"></div>
                                <div class="absolute -top-3 -right-3 h-8 w-8 bg-blue-500 rounded-full border-4 border-slate-200 dark:border-slate-800 flex items-center justify-center text-slate-900 dark:text-white text-[10px] font-bold italic">1</div>
                             </div>
                             <p class="text-xs font-bold text-slate-500 font-mono uppercase tracking-widest">Setup identity...</p>
                        </div>

                        <!-- Step 2 Animation -->
                        <div v-if="currentStep === 1" class="flex flex-col items-center animate-in slide-in-from-right duration-500">
                             <div class="flex gap-2 mb-4">
                                <div class="h-12 w-32 bg-slate-700 rounded-lg border border-slate-600"></div>
                                <div class="h-12 w-12 bg-blue-500 rounded-lg animate-bounce flex items-center justify-center">
                                    <PlusIcon class="h-6 w-6 text-slate-900 dark:text-white" />
                                </div>
                             </div>
                             <p class="text-xs font-bold text-slate-500 font-mono uppercase tracking-widest">Append components...</p>
                        </div>

                        <!-- Step 3 Animation (Operations) -->
                        <div v-if="currentStep === 2" class="flex flex-col items-center animate-in slide-in-from-left duration-500">
                             <div class="flex flex-col gap-2 mb-4">
                                <div class="h-4 w-40 bg-slate-700 rounded-full"></div>
                                <div class="h-4 w-32 bg-indigo-500/40 rounded-full animate-pulse"></div>
                                <div class="h-4 w-36 bg-slate-700 rounded-full"></div>
                             </div>
                             <p class="text-xs font-bold text-slate-500 font-mono uppercase tracking-widest">Define production route...</p>
                        </div>

                        <!-- Step 4 Animation -->
                        <div v-if="currentStep === 3" class="text-center animate-in zoom-in duration-500">
                             <div class="h-12 w-48 bg-gradient-to-r from-blue-600 to-blue-500 rounded-xl shadow-lg shadow-blue-500/40 flex items-center justify-center mb-4">
                                <CheckCircleIcon class="h-6 w-6 text-slate-900 dark:text-white" />
                             </div>
                             <p class="text-xs font-bold text-slate-500 font-mono uppercase tracking-widest">Finalize & save...</p>
                        </div>
                    </div>

                    <div class="mb-8">
                        <h5 class="text-lg font-bold text-slate-900 dark:text-white mb-2">{{ tutorialSteps[currentStep].title }}</h5>
                        <p class="text-slate-500 dark:text-slate-400 text-sm leading-relaxed">{{ tutorialSteps[currentStep].content }}</p>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex gap-1">
                            <div v-for="i in tutorialSteps.length" :key="i" 
                                class="h-1.5 rounded-full transition-all duration-300" 
                                :class="i-1 === currentStep ? 'w-8 bg-blue-500' : 'w-2 bg-slate-700'"
                            ></div>
                        </div>
                        <button 
                            @click="nextStep"
                            class="flex items-center gap-2 rounded-2xl bg-white px-6 py-3 text-sm font-bold text-slate-900 hover:bg-slate-100 transition-all shadow-xl shadow-white/5"
                        >
                            {{ currentStep < tutorialSteps.length - 1 ? 'Next' : 'Mulai Sekarang' }}
                            <ArrowRightIcon v-if="currentStep < tutorialSteps.length - 1" class="h-4 w-4" />
                            <CheckCircleIcon v-else class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Confetti-like Sparkles Background -->
            <div class="absolute inset-0 pointer-events-none overflow-hidden opacity-20">
                <div v-for="n in 10" :key="n" class="absolute h-1 w-1 bg-white rounded-full animate-ping" :style="`top: ${Math.random()*100}%; left: ${Math.random()*100}%; animation-delay: ${Math.random()*5}s`"></div>
            </div>
        </div>
    </AppLayout>
</template>



