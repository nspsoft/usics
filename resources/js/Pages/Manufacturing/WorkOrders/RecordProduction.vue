<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { 
    ArrowLeftIcon, 
    XMarkIcon,
    CubeIcon,
    Bars3CenterLeftIcon,
    ClockIcon,
    ExclamationTriangleIcon,
    PlusIcon,
    TrashIcon,
    DocumentDuplicateIcon,
} from '@heroicons/vue/24/outline';
import DmyDateInput from '@/Components/DmyDateInput.vue';

const props = defineProps({
    workOrder: Object,
    shiftOptions: Object,
    machineOptions: Object,
    defectCategories: Object,
    operators: Array,
    defaultOperatorEmployeeId: [Number, String],
    availableLots: Array,
});

const page = usePage();
const toast = ref(null);
const lastFlash = ref(null);

const showToast = (type, message) => {
    toast.value = { type, message };
    setTimeout(() => {
        if (toast.value?.message === message) {
            toast.value = null;
        }
    }, 4000);
};

watch(() => page.props.flash, (flash) => {
    const key = JSON.stringify(flash || {});
    if (key === lastFlash.value) return;
    lastFlash.value = key;

    if (flash?.success) showToast('success', flash.success);
    if (flash?.error) showToast('error', flash.error);
}, { deep: true });

const generateRequestId = () => {
    try {
        if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
            return crypto.randomUUID();
        }
    } catch (e) {}
    const rnd = () => Math.floor((1 + Math.random()) * 0x10000).toString(16).substring(1);
    return `${rnd()}${rnd()}-${rnd()}-${rnd()}-${rnd()}-${rnd()}${rnd()}${rnd()}`;
};

const form = useForm({
    client_request_id: generateRequestId(),
    production_date: new Date().toISOString().split('T')[0],
    shift: '1',
    operator_employee_id: props.defaultOperatorEmployeeId || '',
    start_time: '',
    end_time: '',
    machine_line: '',
    mother_coil_lot_id: '',
    baby_coils: [],
    qty_good: 0,
    qty_rejected: 0,
    defect_category: '',
    downtime_minutes: 0,
    notes: '',
});

const generateBabyCoils = () => {
    if (!form.mother_coil_lot_id) {
        showToast('error', 'Pilih Mother Coil terlebih dahulu.');
        return;
    }
    
    let motherCoil = props.availableLots?.find(l => l.id == form.mother_coil_lot_id);
    let baseThickness = motherCoil ? parseFloat(motherCoil.thickness) : 0;
    let baseMotherCoilNo = motherCoil ? motherCoil.coil_number : props.workOrder.wo_number;
    
    let targets = [];
    if (props.workOrder.outputs && props.workOrder.outputs.length > 0) {
        targets = props.workOrder.outputs;
    } else {
        targets = [{ product: props.workOrder.product, product_id: props.workOrder.product_id }];
    }
    
    // Clear existing
    form.baby_coils = [];
    
    targets.forEach((target, index) => {
        form.baby_coils.push({
            product_id: target.product_id,
            product_name: target.product?.name || 'Unknown',
            coil_number: `${baseMotherCoilNo}-${(form.baby_coils.length + 1).toString().padStart(2, '0')}`,
            thickness: baseThickness,
            width: target.product?.width || (motherCoil ? parseFloat(motherCoil.width) : 0),
            length: target.product?.length || 0,
            weight: 0,
        });
    });
};

const addBabyCoil = () => {
    let motherCoil = props.availableLots?.find(l => l.id == form.mother_coil_lot_id);
    let baseThickness = motherCoil ? parseFloat(motherCoil.thickness) : 0;
    let baseMotherCoilNo = motherCoil ? motherCoil.coil_number : props.workOrder.wo_number;
    let target = props.workOrder.outputs && props.workOrder.outputs.length > 0 ? props.workOrder.outputs[0] : { product: props.workOrder.product, product_id: props.workOrder.product_id };
    
    form.baby_coils.push({
        product_id: target.product_id,
        product_name: target.product?.name || 'Unknown',
        coil_number: `${baseMotherCoilNo}-${(form.baby_coils.length + 1).toString().padStart(2, '0')}`,
        thickness: baseThickness,
        width: target.product?.width || 0,
        length: target.product?.length || 0,
        weight: 0,
    });
};

const removeBabyCoil = (index) => {
    form.baby_coils.splice(index, 1);
};

const duplicateBabyCoil = (index) => {
    const coil = { ...form.baby_coils[index] };
    let motherCoil = props.availableLots?.find(l => l.id == form.mother_coil_lot_id);
    let baseMotherCoilNo = motherCoil ? motherCoil.coil_number : props.workOrder.wo_number;
    coil.coil_number = `${baseMotherCoilNo}-${(form.baby_coils.length + 1).toString().padStart(2, '0')}`;
    form.baby_coils.splice(index + 1, 0, coil);
};

// Auto update qty_good based on weight
watch(() => form.baby_coils, (newVal) => {
    let totalWeight = 0;
    newVal.forEach(c => {
        totalWeight += parseFloat(c.weight) || 0;
    });
    form.qty_good = totalWeight;
}, { deep: true });


const remainingQty = computed(() => {
    const planned = parseFloat(props.workOrder.qty_planned) || 0;
    const produced = parseFloat(props.workOrder.qty_produced) || 0;
    return Math.max(0, planned - produced);
});

const isOverProduction = computed(() => {
    return parseFloat(form.qty_good) > remainingQty.value;
});

const submit = () => {
    form.post(route('manufacturing.work-orders.record-production', props.workOrder.id), {
        onSuccess: () => {
            form.client_request_id = generateRequestId();
            showToast('success', 'Input produksi berhasil disimpan.');
            // Redirect will be handled by controller, but we can also force navigate back
            // The controller currently does `return back()`, so this form submission will reload the page
            // We should ideally update controller to redirect to show page on success
            // OR we rely on Inertia behavior. 
            // For PWA flow, let's assume we want to go back to WO detail on success.
            // But 'back' from here is the WO detail page anyway if we navigated here.
        },
        onError: () => {
            showToast('error', 'Gagal menyimpan input produksi. Periksa kembali data yang diisi.');
        }
    });
};
</script>

<template>
    <Head title="Production Report" />
    
    <div class="min-h-screen bg-white dark:bg-slate-950 text-slate-900 dark:text-white pb-20">
        <!-- PWA Header -->
        <div class="sticky top-0 z-30 bg-white dark:bg-slate-950/80 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <Link 
                    :href="route('manufacturing.work-orders.show', workOrder.id)" 
                    class="p-2 -ml-2 rounded-full text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white active:bg-slate-50 dark:bg-slate-800"
                >
                    <ArrowLeftIcon class="h-6 w-6" />
                </Link>
                <div>
                    <h1 class="font-bold text-lg leading-tight">Laporan Produksi</h1>
                    <p class="text-[10px] items-center gap-1.5 text-slate-500 dark:text-slate-400 font-mono flex">
                        <span>{{ workOrder.wo_number }}</span>
                        <span class="h-1 w-1 rounded-full bg-slate-600"></span>
                        <span class="text-blue-400 font-bold uppercase">{{ remainingQty }} Left</span>
                    </p>
                </div>
            </div>
            
            <div class="h-10 w-10 flex items-center justify-center rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700/50">
                 <CubeIcon class="h-5 w-5 text-emerald-400" />
            </div>
        </div>

        <div class="p-4 max-w-lg mx-auto">
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Identity Section -->
                <div class="glass-card rounded-2xl p-4 space-y-4">
                     <div class="flex items-center gap-2 text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">
                        <Bars3CenterLeftIcon class="h-4 w-4" />
                        Identitas
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2">Tanggal & Shift</label>
                        <div class="grid grid-cols-2 gap-3">
                            <DmyDateInput
                                v-model="form.production_date"
                                class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-3 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/50"
                                required
                            />
                            <select 
                                v-model="form.shift"
                                class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-3 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/50"
                                required
                            >
                                <option v-for="(label, value) in shiftOptions" :key="value" :value="value">{{ label }}</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2">Operator</label>
                        <select 
                            v-model="form.operator_employee_id"
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-3 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/50"
                            required
                        >
                            <option value="">Pilih Operator...</option>
                            <option v-for="op in operators" :key="op.id" :value="op.id">{{ op.nik ? `${op.nik} - ${op.full_name}` : op.full_name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2">Mesin / Line</label>
                        <select 
                            v-model="form.machine_line"
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-3 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/50"
                        >
                            <option value="">- Pilih Mesin / Line -</option>
                            <option v-for="(label, value) in machineOptions" :key="value" :value="value">{{ label }}</option>
                        </select>
                    </div>
                </div>

                <!-- Material Section -->
                <div class="glass-card rounded-2xl p-4 space-y-4">
                    <div class="flex items-center gap-2 text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">
                        <CubeIcon class="h-4 w-4" />
                        Bahan Baku (Mother Coil)
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2">Pilih Mother Coil <span class="text-emerald-500">*</span></label>
                        <select 
                            v-model="form.mother_coil_lot_id"
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-3 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/50"
                            required
                        >
                            <option value="">- Scan / Pilih Mother Coil -</option>
                            <option v-for="lot in availableLots" :key="lot.id" :value="lot.id">
                                {{ lot.coil_number }} - {{ lot.weight }} kg (Thick: {{ lot.thickness }}mm)
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Time & Output -->
                <div class="glass-card rounded-2xl p-4 space-y-4">
                    <div class="flex items-center gap-2 text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">
                        <ClockIcon class="h-4 w-4" />
                        Waktu & Hasil
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2">Mulai</label>
                            <input 
                                v-model="form.start_time"
                                type="time"
                                class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-3 px-4 text-slate-900 dark:text-white text-center font-mono focus:ring-2 focus:ring-emerald-500/50"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2">Selesai</label>
                            <input 
                                v-model="form.end_time"
                                type="time"
                                class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-3 px-4 text-slate-900 dark:text-white text-center font-mono focus:ring-2 focus:ring-emerald-500/50"
                            />
                        </div>
                    </div>

                    <div class="pt-2 border-t border-slate-200 dark:border-slate-800"></div>

                    <div v-if="isOverProduction" class="bg-amber-500/10 border border-amber-500/30 rounded-xl p-3 flex gap-3 animate-pulse">
                        <ExclamationTriangleIcon class="h-5 w-5 text-amber-500 shrink-0" />
                        <div class="text-[10px] text-amber-200">
                            <strong>Over Produksi!</strong> Jumlah yang anda masukkan ({{ form.qty_good }}) melebihi sisa rencana ({{ remainingQty }}). Pastikan data sudah benar.
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2">Total Berat OK (kg)</label>
                            <input 
                                v-model="form.qty_good"
                                type="number"
                                readonly
                                class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-emerald-50 dark:bg-emerald-900/20 py-4 px-4 text-emerald-500 text-2xl font-bold font-mono text-center focus:ring-0"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2">Total Reject (kg)</label>
                            <input 
                                v-model="form.qty_rejected"
                                type="number"
                                step="0.0001"
                                min="0"
                                class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-4 px-4 text-red-400 text-2xl font-bold font-mono text-center focus:ring-2 focus:ring-red-500/50"
                            />
                        </div>
                    </div>
                    
                    <div class="pt-4 border-t border-slate-200 dark:border-slate-800"></div>
                    
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-xs font-bold text-slate-500 dark:text-slate-400">Daftar Potongan (Baby Coils)</div>
                        <button type="button" @click="generateBabyCoils" class="text-xs px-3 py-1.5 bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300 rounded-lg font-bold hover:bg-emerald-200 transition-colors">
                            Generate Default
                        </button>
                    </div>
                    
                    <div class="space-y-3">
                        <div v-for="(coil, index) in form.baby_coils" :key="index" class="p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 relative group">
                            <div class="absolute right-2 top-2 flex gap-1">
                                <button type="button" @click="duplicateBabyCoil(index)" class="p-1.5 text-slate-400 hover:text-blue-500 rounded-md hover:bg-slate-100 dark:hover:bg-slate-800" title="Duplicate">
                                    <DocumentDuplicateIcon class="h-4 w-4" />
                                </button>
                                <button type="button" @click="removeBabyCoil(index)" class="p-1.5 text-slate-400 hover:text-red-500 rounded-md hover:bg-slate-100 dark:hover:bg-slate-800" title="Remove">
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </div>
                            
                            <div class="grid grid-cols-12 gap-3 mb-3 pr-16">
                                <div class="col-span-12 sm:col-span-6">
                                    <label class="block text-[10px] font-bold text-slate-400 mb-1">Coil No.</label>
                                    <input v-model="coil.coil_number" type="text" class="w-full text-sm rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-1.5 px-2 focus:ring-1 focus:ring-emerald-500/50" required />
                                </div>
                                <div class="col-span-12 sm:col-span-6">
                                    <label class="block text-[10px] font-bold text-slate-400 mb-1">Produk</label>
                                    <select v-model="coil.product_id" class="w-full text-sm rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-1.5 px-2 focus:ring-1 focus:ring-emerald-500/50" required>
                                        <option v-for="output in (workOrder.outputs?.length ? workOrder.outputs : [{product_id: workOrder.product_id, product: workOrder.product}])" :key="output.product_id" :value="output.product_id">
                                            {{ output.product?.name }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-4 gap-2">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 mb-1 text-center">Tebal</label>
                                    <input v-model="coil.thickness" type="number" step="0.01" class="w-full text-sm rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800/50 py-1.5 px-2 text-center" readonly />
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 mb-1 text-center">Lebar</label>
                                    <input v-model="coil.width" type="number" step="0.01" class="w-full text-sm rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-1.5 px-2 text-center focus:ring-1 focus:ring-emerald-500/50" />
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 mb-1 text-center">Panjang</label>
                                    <input v-model="coil.length" type="number" step="0.01" class="w-full text-sm rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-1.5 px-2 text-center focus:ring-1 focus:ring-emerald-500/50" />
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-emerald-500 mb-1 text-center">Berat (kg)</label>
                                    <input v-model="coil.weight" type="number" step="0.01" class="w-full text-sm font-bold rounded-lg border border-emerald-200 dark:border-emerald-700/50 bg-emerald-50 dark:bg-emerald-900/20 py-1.5 px-2 text-center focus:ring-1 focus:ring-emerald-500/50" required />
                                </div>
                            </div>
                        </div>
                        
                        <button type="button" @click="addBabyCoil" class="w-full py-2 border-2 border-dashed border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 rounded-xl flex items-center justify-center gap-2 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors font-bold text-sm">
                            <PlusIcon class="h-4 w-4" /> Tambah Potongan
                        </button>
                    </div>
                </div>

                <!-- Quality & Notes -->
                <div class="glass-card rounded-2xl p-4 space-y-4">
                    <div class="flex items-center gap-2 text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">
                        <ExclamationTriangleIcon class="h-4 w-4" />
                        Quality & Notes
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="col-span-2 sm:col-span-1">
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2">Kategori Cacat</label>
                            <select 
                                v-model="form.defect_category"
                                class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-3 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/50"
                            >
                                <option value="">- Tidak Ada -</option>
                                <option v-for="(label, value) in defectCategories" :key="value" :value="value">{{ label }}</option>
                            </select>
                        </div>
                        <div class="col-span-2 sm:col-span-1">
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2">Downtime (menit)</label>
                            <input 
                                v-model="form.downtime_minutes"
                                type="number"
                                min="0"
                                class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-3 px-4 text-slate-900 dark:text-white font-mono focus:ring-2 focus:ring-emerald-500/50"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2">Catatan</label>
                        <textarea 
                            v-model="form.notes"
                            rows="2"
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-3 px-4 text-slate-900 dark:text-white resize-none focus:ring-2 focus:ring-emerald-500/50"
                            placeholder="Keterangan tambahan..."
                        ></textarea>
                    </div>
                </div>

                <!-- Submit Button PWA Style (Fixed Bottom) -->
                <div class="fixed bottom-0 left-0 right-0 p-4 bg-white dark:bg-slate-950/90 backdrop-blur border-t border-slate-200 dark:border-slate-800 z-40">
                    <div class="max-w-lg mx-auto flex gap-3">
                        <Link 
                            :href="route('manufacturing.work-orders.show', workOrder.id)"
                            class="px-4 py-3.5 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold active:bg-slate-700 active:scale-95 transition-all"
                        >
                            <XMarkIcon class="h-6 w-6" />
                        </Link>
                        <button 
                            type="submit"
                            :disabled="form.processing"
                            class="flex-1 py-3.5 rounded-xl bg-gradient-to-r from-emerald-600 to-emerald-500 text-slate-900 dark:text-white font-bold text-lg shadow-lg shadow-emerald-500/25 active:scale-95 transition-all disabled:opacity-50 disabled:scale-100 flex items-center justify-center gap-2"
                        >
                            <span v-if="form.processing" class="animate-spin h-5 w-5 border-2 border-white/30 border-t-white rounded-full"></span>
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Laporan' }}
                        </button>
                    </div>
                </div>
                
                <!-- Spacer for fixed bottom bar -->
                <div class="h-20"></div>
            </form>
        </div>

        <div v-if="toast" class="fixed left-1/2 -translate-x-1/2 bottom-24 z-50 w-[calc(100%-2rem)] max-w-lg">
            <div
                class="rounded-2xl px-4 py-3 shadow-2xl border backdrop-blur-md"
                :class="toast.type === 'success'
                    ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-200'
                    : 'bg-red-500/10 border-red-500/20 text-red-200'"
            >
                <div class="text-sm font-semibold">{{ toast.message }}</div>
            </div>
        </div>
    </div>
</template>



