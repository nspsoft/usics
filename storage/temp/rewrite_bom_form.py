import re

with open('c:\\laragon\\www\\USICS\\resources\\js\\Pages\\Manufacturing\\Boms\\Form.vue', 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Add slit_count to outputs initialization
content = content.replace(
    'product_id: \'\', qty: 1, unit_id: \'\', scrap_rate: 0 }],\n    outputs: props.bom?.outputs?.map(o => ({\n        id: o.id,\n        product_id: o.product_id,\n        qty_ratio: parseFloat(o.qty_ratio),',
    'product_id: \'\', qty: 1, unit_id: \'\', scrap_rate: 0 }],\n    outputs: props.bom?.outputs?.map(o => ({\n        id: o.id,\n        product_id: o.product_id,\n        qty_ratio: parseFloat(o.qty_ratio),\n        slit_count: o.slit_count || 1,'
)

# Also initialization in fallback
content = content.replace(
    'qty_ratio: parseFloat(o.qty_ratio),\n        unit_id: o.unit_id,\n        notes: o.notes,\n    })) || [],',
    'qty_ratio: parseFloat(o.qty_ratio),\n        slit_count: o.slit_count || 1,\n        unit_id: o.unit_id,\n        notes: o.notes,\n    })) || [],'
)

# Add slit_count to addOutput
content = content.replace(
    'const addOutput = () => {\n    form.outputs.push({\n        product_id: \'\',\n        qty_ratio: 1,\n        unit_id: \'\',',
    'const addOutput = () => {\n    form.outputs.push({\n        product_id: \'\',\n        qty_ratio: 1,\n        slit_count: 1,\n        unit_id: \'\','
)

# 2. Add Computed Property for Waste
waste_computed = """

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
"""

# Insert computed after imports and composables
content = content.replace(
    'const form = useForm({',
    waste_computed + '\nconst form = useForm({'
)

# 3. Add UI elements for Slit Count and Waste indicator
old_output_row = """                                <div v-for="(out, index) in form.outputs" :key="index" class="group/out relative glass-card rounded-2xl p-5 hover:border-slate-200 dark:border-slate-700 transition-all">
                                    <div class="flex flex-col md:flex-row gap-6">
                                        <!-- Index -->
                                        <div class="flex-shrink-0">
                                            <div class="h-8 w-8 rounded-full bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold text-xs">
                                                #{{ index + 1 }}
                                            </div>
                                        </div>
                                        
                                        <!-- Fields -->
                                        <div class="flex-grow grid grid-cols-1 md:grid-cols-12 gap-5">
                                            <div class="md:col-span-5">
                                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2">Output Product <span class="text-red-500">*</span></label>
                                                <SearchableSelect
                                                    v-model="out.product_id"
                                                    :options="materials"
                                                    label-key="name"
                                                    value-key="id"
                                                    placeholder="Select product..."
                                                    @update:model-value="syncOutputUnitFromProduct(index)"
                                                    :error="form.errors[`outputs.${index}.product_id`]"
                                                />
                                            </div>
                                            
                                            <div class="md:col-span-3">
                                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2">Est. Qty Output <span class="text-red-500">*</span></label>
                                                <input 
                                                    v-model="out.qty_ratio"
                                                    type="number"
                                                    step="0.0001"
                                                    min="0.0001"
                                                    class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2.5 px-3 text-sm focus:ring-2 focus:ring-emerald-500/50"
                                                    placeholder="e.g. 10"
                                                    required
                                                />
                                            </div>"""

new_output_row = """                                <div v-for="(out, index) in form.outputs" :key="index" class="group/out relative glass-card rounded-2xl p-5 hover:border-slate-200 dark:border-slate-700 transition-all">
                                    <div class="flex flex-col md:flex-row gap-6">
                                        <!-- Index -->
                                        <div class="flex-shrink-0">
                                            <div class="h-8 w-8 rounded-full bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold text-xs">
                                                #{{ index + 1 }}
                                            </div>
                                        </div>
                                        
                                        <!-- Fields -->
                                        <div class="flex-grow grid grid-cols-1 md:grid-cols-12 gap-5">
                                            <div class="md:col-span-5">
                                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2">Output Product <span class="text-red-500">*</span></label>
                                                <SearchableSelect
                                                    v-model="out.product_id"
                                                    :options="materials"
                                                    label-key="name"
                                                    value-key="id"
                                                    placeholder="Select product..."
                                                    @update:model-value="syncOutputUnitFromProduct(index)"
                                                    :error="form.errors[`outputs.${index}.product_id`]"
                                                />
                                            </div>
                                            
                                            <div class="md:col-span-2">
                                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2" title="Jumlah Pisau / Potongan per output">Slit Count</label>
                                                <input 
                                                    v-model="out.slit_count"
                                                    type="number"
                                                    step="1"
                                                    min="1"
                                                    class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2.5 px-3 text-sm focus:ring-2 focus:ring-emerald-500/50"
                                                    required
                                                />
                                            </div>
                                            
                                            <div class="md:col-span-2">
                                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2">Est. Qty Output <span class="text-red-500">*</span></label>
                                                <input 
                                                    v-model="out.qty_ratio"
                                                    type="number"
                                                    step="0.0001"
                                                    min="0.0001"
                                                    class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2.5 px-3 text-sm focus:ring-2 focus:ring-emerald-500/50"
                                                    placeholder="e.g. 10"
                                                    required
                                                />
                                            </div>"""

content = content.replace(old_output_row, new_output_row)


waste_html = """
                            <div v-if="wasteCalculation" class="p-6 bg-slate-50 dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="text-xs font-bold uppercase tracking-wider" :class="wasteCalculation.isInvalid ? 'text-red-500' : 'text-emerald-500'">
                                        Slitting Waste Indicator
                                    </h4>
                                    <span class="text-xs font-mono font-bold" :class="wasteCalculation.isInvalid ? 'text-red-500' : 'text-emerald-500'">
                                        {{ wasteCalculation.waste }} mm ({{ wasteCalculation.percentage }}%)
                                    </span>
                                </div>
                                <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2.5 mb-2 overflow-hidden flex">
                                    <div class="h-2.5" :class="wasteCalculation.isInvalid ? 'bg-red-500' : 'bg-emerald-500'" :style="`width: ${Math.min(100, Math.max(0, 100 - wasteCalculation.percentage))}%`"></div>
                                </div>
                                <div class="flex justify-between text-[10px] text-slate-500 font-bold">
                                    <span>Mother Coil: {{ wasteCalculation.motherWidth }} mm</span>
                                    <span>Used Width: {{ wasteCalculation.totalOutputWidth }} mm</span>
                                </div>
                                <div v-if="wasteCalculation.isInvalid" class="mt-2 text-xs font-bold text-red-500">
                                    Total waste (scrap) melebihi batas 1% atau negatif. Silakan perbaiki kombinasi potong.
                                </div>
                            </div>
"""

content = content.replace(
    '<!-- Action Buttons -->',
    waste_html + '\n                            <!-- Action Buttons -->'
)

# Disable submit button if waste is invalid
content = content.replace(
    ':disabled="form.processing"',
    ':disabled="form.processing || (wasteCalculation && wasteCalculation.isInvalid)"'
)

with open('c:\\laragon\\www\\USICS\\resources\\js\\Pages\\Manufacturing\\Boms\\Form.vue', 'w', encoding='utf-8') as f:
    f.write(content)
