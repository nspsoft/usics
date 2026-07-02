import re

with open('c:\\laragon\\www\\USICS\\resources\\js\\Pages\\Manufacturing\\WorkOrders\\RecordProduction.vue', 'r', encoding='utf-8') as f:
    content = f.read()

# Add props
content = content.replace(
    'defaultOperatorEmployeeId: [Number, String],',
    'defaultOperatorEmployeeId: [Number, String],\n    availableLots: Array,'
)

# Replace imports
content = content.replace(
    "ExclamationTriangleIcon,\n} from '@heroicons/vue/24/outline';",
    "ExclamationTriangleIcon,\n    PlusIcon,\n    TrashIcon,\n    DocumentDuplicateIcon,\n} from '@heroicons/vue/24/outline';"
)

# Replace form definition
new_form = """const form = useForm({
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
"""
content = content.replace(
    "const form = useForm({\n    client_request_id: generateRequestId(),\n    production_date: new Date().toISOString().split('T')[0],\n    shift: '1',\n    operator_employee_id: props.defaultOperatorEmployeeId || '',\n    start_time: '',\n    end_time: '',\n    machine_line: '',\n    qty_good: 0,\n    qty_rejected: 0,\n    defect_category: '',\n    downtime_minutes: 0,\n    notes: '',\n});",
    new_form
)

# HTML changes - Add Material Section
material_html = """
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
"""

# Replace Identity section closing div with the new material section appended
content = content.replace(
    '</select>\n                    </div>\n                </div>\n\n                <!-- Time & Output -->',
    '</select>\n                    </div>\n                </div>\n' + material_html + '\n                <!-- Time & Output -->'
)

# Output Section logic
output_html = """
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
"""

old_output_html = """
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2">Qty OK <span class="text-emerald-500">*</span></label>
                            <input 
                                v-model="form.qty_good"
                                type="number"
                                step="0.0001"
                                min="0"
                                class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-4 px-4 text-emerald-400 text-2xl font-bold font-mono text-center focus:ring-2 focus:ring-emerald-500/50"
                                required
                            />
                            <div v-if="form.errors.qty_good" class="text-red-400 text-[10px] mt-1 font-bold">{{ form.errors.qty_good }}</div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2">Qty Reject</label>
                            <input 
                                v-model="form.qty_rejected"
                                type="number"
                                step="0.0001"
                                min="0"
                                class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-4 px-4 text-red-400 text-2xl font-bold font-mono text-center focus:ring-2 focus:ring-red-500/50"
                            />
                        </div>
                    </div>
"""

content = content.replace(old_output_html.strip(), output_html.strip())

with open('c:\\laragon\\www\\USICS\\resources\\js\\Pages\\Manufacturing\\WorkOrders\\RecordProduction.vue', 'w', encoding='utf-8') as f:
    f.write(content)
