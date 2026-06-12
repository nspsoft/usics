<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatNumber, formatDate, formatTime } from '@/helpers';
import debounce from 'lodash/debounce';
import { MagnifyingGlassIcon, ArrowTopRightOnSquareIcon, PencilSquareIcon, PrinterIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    entries: Object,
    filters: Object,
    operators: Array,
});

const search = ref(props.filters?.search || '');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');
const shift = ref(props.filters?.shift || '');
const operatorEmployeeId = ref(props.filters?.operator_employee_id || '');

const canClear = computed(() => !!(search.value || dateFrom.value || dateTo.value || shift.value || operatorEmployeeId.value));

const applyFilters = debounce(() => {
    router.get(route('manufacturing.production-reports.index'), {
        search: search.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
        shift: shift.value || undefined,
        operator_employee_id: operatorEmployeeId.value || undefined,
    }, { preserveState: true, replace: true });
}, 300);

watch([search, dateFrom, dateTo, shift, operatorEmployeeId], applyFilters);

const clearFilters = () => {
    search.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    shift.value = '';
    operatorEmployeeId.value = '';
};

// Print Labels Logic
const showPrintLabelModal = ref(false);
const printLabelItems = ref([]);
const globalLotNumber = ref('');
const globalSpk = ref('');
const globalNote = ref('');
const selectedEntryId = ref(null);

const openPrintLabelModal = (entry) => {
    selectedEntryId.value = entry.id;

    const sizeParts = [];
    if (entry.work_order?.product?.length) sizeParts.push(entry.work_order.product.length);
    if (entry.work_order?.product?.width) sizeParts.push(entry.work_order.product.width);
    if (entry.work_order?.product?.height) sizeParts.push(entry.work_order.product.height);
    const dimensionUnit = entry.work_order?.product?.dimension_unit || 'mm';
    const sizeStr = sizeParts.length > 0 ? sizeParts.map(p => p + ' ' + dimensionUnit).join(' x ') : '';

    const defaultLot = entry.work_order?.sales_order?.customer_po_number || '';
    const defaultSpk = entry.work_order?.sales_order?.so_number || entry.work_order?.wo_number || '';
    const defaultNote = `Shift ${entry.shift || '-'} - ${entry.operator_employee?.full_name || ''}`;

    printLabelItems.value = [{
        key: entry.id + '_init',
        item_id: entry.id,
        product_name: entry.work_order?.product?.name || '',
        sku: entry.work_order?.product?.sku || '',
        qty_delivered: entry.qty_produced || 0,
        unit_name: 'Pcs',
        qty_per_label: entry.qty_produced || 0,
        label_count: 1,
        lot_number: defaultLot,
        spk: defaultSpk,
        note: defaultNote,
        size: sizeStr,
        specification: entry.work_order?.product?.description || '',
        selected: true
    }];

    globalLotNumber.value = defaultLot;
    globalSpk.value = defaultSpk;
    globalNote.value = defaultNote;
    showPrintLabelModal.value = true;
};

const duplicateLabelRow = (row) => {
    const newRow = {
        ...row,
        key: row.item_id + '_' + Math.random().toString(36).substr(2, 9),
        qty_per_label: 0,
        label_count: 1,
        selected: true
    };
    const index = printLabelItems.value.findIndex(r => r.key === row.key);
    printLabelItems.value.splice(index + 1, 0, newRow);
};

const canDeleteRow = (row) => {
    return printLabelItems.value.length > 1;
};

const deleteLabelRow = (row) => {
    const index = printLabelItems.value.findIndex(r => r.key === row.key);
    printLabelItems.value.splice(index, 1);
};

const autoSplitRow = (row) => {
    const qtyInput = prompt("Masukkan Qty per label (misal 100):");
    if (!qtyInput) return;
    const splitQty = parseInt(qtyInput);
    if (isNaN(splitQty) || splitQty <= 0) {
        alert("Qty per label harus berupa angka positif.");
        return;
    }

    const totalQty = row.qty_delivered;
    if (splitQty > totalQty) {
        alert("Qty per label tidak boleh lebih besar dari total Qty Produksi.");
        return;
    }

    const labelCount = Math.floor(totalQty / splitQty);
    const remainder = totalQty % splitQty;

    const newRows = [];
    if (labelCount > 0) {
        newRows.push({
            ...row,
            key: row.item_id + '_1',
            qty_per_label: splitQty,
            label_count: labelCount,
            selected: true
        });
    }
    if (remainder > 0) {
        newRows.push({
            ...row,
            key: row.item_id + '_rem',
            qty_per_label: remainder,
            label_count: 1,
            selected: true
        });
    }

    printLabelItems.value = newRows;
};

const applyGlobalValues = () => {
    printLabelItems.value.forEach(item => {
        if (item.selected) {
            if (globalLotNumber.value) item.lot_number = globalLotNumber.value;
            if (globalSpk.value) item.spk = globalSpk.value;
            if (globalNote.value) item.note = globalNote.value;
        }
    });
};

const submitPrintLabels = () => {
    const selectedItems = printLabelItems.value.filter(item => item.selected);
    if (selectedItems.length === 0) {
        alert('Silakan pilih minimal satu produk untuk dicetak.');
        return;
    }

    const formEl = document.createElement('form');
    formEl.method = 'POST';
    formEl.action = route('manufacturing.production-reports.print-labels', selectedEntryId.value);
    formEl.target = '_blank';

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
        const tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = '_token';
        tokenInput.value = csrfToken;
        formEl.appendChild(tokenInput);
    }

    const dataInput = document.createElement('input');
    dataInput.type = 'hidden';
    dataInput.name = 'label_data';
    dataInput.value = JSON.stringify(selectedItems);
    formEl.appendChild(dataInput);

    document.body.appendChild(formEl);
    formEl.submit();
    document.body.removeChild(formEl);
    showPrintLabelModal.value = false;
};

</script>

<template>
    <Head title="Laporan Produksi" />

    <AppLayout title="Laporan Produksi">
        <div class="max-w-full px-4 sm:px-6 lg:px-8 mx-auto">
            <div class="mb-6">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                    <div class="lg:col-span-5">
                        <div class="relative">
                            <MagnifyingGlassIcon class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500" />
                            <input
                                v-model="search"
                                type="search"
                                placeholder="Search WO / Product..."
                                class="w-full rounded-2xl border-0 bg-white dark:bg-slate-950 py-3 pl-12 pr-4 text-slate-900 dark:text-white placeholder:text-slate-500 shadow-lg focus:ring-2 focus:ring-cyan-500/50"
                            />
                        </div>
                    </div>

                    <div class="lg:col-span-2">
                        <input
                            v-model="dateFrom"
                            type="date"
                            class="w-full rounded-2xl border-0 bg-white dark:bg-slate-950 py-3 px-4 text-slate-900 dark:text-white shadow-lg focus:ring-2 focus:ring-cyan-500/50"
                        />
                    </div>

                    <div class="lg:col-span-2">
                        <input
                            v-model="dateTo"
                            type="date"
                            class="w-full rounded-2xl border-0 bg-white dark:bg-slate-950 py-3 px-4 text-slate-900 dark:text-white shadow-lg focus:ring-2 focus:ring-cyan-500/50"
                        />
                    </div>

                    <div class="lg:col-span-1">
                        <select
                            v-model="shift"
                            class="w-full rounded-2xl border-0 bg-white dark:bg-slate-950 py-3 px-4 text-slate-900 dark:text-white shadow-lg focus:ring-2 focus:ring-cyan-500/50"
                        >
                            <option value="">Shift</option>
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                        </select>
                    </div>

                    <div class="lg:col-span-2">
                        <select
                            v-model="operatorEmployeeId"
                            class="w-full rounded-2xl border-0 bg-white dark:bg-slate-950 py-3 px-4 text-slate-900 dark:text-white shadow-lg focus:ring-2 focus:ring-cyan-500/50"
                        >
                            <option value="">Operator</option>
                            <option v-for="op in operators" :key="op.id" :value="op.id">
                                {{ op.nik ? `${op.nik} - ${op.full_name}` : op.full_name }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="mt-3 flex items-center justify-between">
                    <div class="text-xs text-slate-500 dark:text-slate-400">
                        Total: <span class="font-semibold text-slate-900 dark:text-white">{{ entries.total }}</span>
                    </div>
                    <button
                        v-if="canClear"
                        type="button"
                        class="rounded-xl bg-slate-50 dark:bg-slate-800 px-4 py-2 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-700 transition-colors"
                        @click="clearFilters"
                    >
                        Clear
                    </button>
                </div>
            </div>

            <div class="rounded-2xl glass-card overflow-hidden">
                <div class="overflow-x-auto overflow-y-auto max-h-[650px]">
                    <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700">
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tanggal</th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">WO</th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Product</th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Shift</th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Operator</th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Jam</th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">OK</th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Reject</th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Down (m)</th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Catatan</th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-24">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="e in entries.data" :key="e.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-600 dark:text-slate-300">{{ formatDate(e.production_date) }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-slate-900 dark:text-white">{{ e.work_order?.wo_number }}</div>
                                    <div class="text-[10px] text-slate-500 font-mono">{{ e.work_order?.product?.sku }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm font-semibold text-slate-900 dark:text-white line-clamp-1">{{ e.work_order?.product?.name }}</div>
                                </td>
                                <td class="px-4 py-3 text-center text-sm font-mono text-slate-600 dark:text-slate-300">{{ e.shift || '-' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                                    {{ e.operator_employee?.full_name || '-' }}
                                </td>
                                <td class="px-4 py-3 text-center text-sm font-mono text-slate-600 dark:text-slate-300">
                                    {{ formatTime(e.start_time) }} - {{ formatTime(e.end_time) }}
                                </td>
                                <td class="px-4 py-3 text-right text-sm font-mono font-bold text-emerald-400">{{ formatNumber(e.qty_produced) }}</td>
                                <td class="px-4 py-3 text-right text-sm font-mono font-bold text-red-400">{{ formatNumber(e.qty_rejected) }}</td>
                                <td class="px-4 py-3 text-right text-sm font-mono text-slate-600 dark:text-slate-300">{{ e.downtime_minutes ?? 0 }}</td>
                                <td class="px-4 py-3 text-xs text-slate-500 max-w-[240px] truncate" :title="e.notes || ''">
                                    {{ e.notes || '-' }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center justify-end gap-2">
                                        <button
                                            type="button"
                                            @click="openPrintLabelModal(e)"
                                            class="inline-flex items-center justify-center h-9 w-9 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-300 hover:text-blue-400 hover:bg-blue-500/10 transition-colors"
                                            title="Print Product Labels"
                                        >
                                            <PrinterIcon class="h-5 w-5" />
                                        </button>
                                        <Link
                                            :href="route('manufacturing.production-reports.edit', e.id)"
                                            class="inline-flex items-center justify-center h-9 w-9 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-300 hover:text-amber-400 hover:bg-amber-500/10 transition-colors"
                                            title="Edit Jam/Catatan"
                                        >
                                            <PencilSquareIcon class="h-5 w-5" />
                                        </Link>
                                        <Link
                                            :href="route('manufacturing.work-orders.show', e.work_order_id)"
                                            class="inline-flex items-center justify-center h-9 w-9 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-300 hover:text-cyan-400 hover:bg-cyan-500/10 transition-colors"
                                            title="View WO"
                                        >
                                            <ArrowTopRightOnSquareIcon class="h-5 w-5" />
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="entries.data.length === 0">
                                <td colspan="11" class="px-4 py-12 text-center text-slate-500 italic">Tidak ada laporan produksi.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="entries.last_page > 1" class="border-t border-slate-200 dark:border-slate-800 px-6 py-4 flex items-center justify-between">
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Showing {{ entries.from }} to {{ entries.to }} of {{ entries.total }} laporan
                    </p>
                    <div class="flex items-center gap-2">
                        <Link
                            v-for="link in entries.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            class="px-3 py-1.5 rounded-lg text-sm transition-colors"
                            :class="link.active
                                ? 'bg-cyan-600 text-white'
                                : link.url
                                    ? 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50'
                                    : 'text-slate-300 dark:text-slate-600 cursor-not-allowed'"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>

    <!-- Print Product Labels Modal -->
    <Teleport to="body">
        <div v-if="showPrintLabelModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4 overflow-y-auto">
            <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-4xl mx-auto overflow-hidden my-8">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <PrinterIcon class="h-6 w-6 text-blue-500" />
                        <h3 class="text-base font-bold text-slate-900 dark:text-white uppercase tracking-widest">Print Production Output Labels</h3>
                    </div>
                    <button @click="showPrintLabelModal = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <XMarkIcon class="h-5 w-5" />
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-6 max-h-[calc(100vh-200px)] overflow-y-auto">
                    <!-- Quick Fill Section -->
                    <div class="bg-slate-50 dark:bg-slate-800/30 rounded-2xl p-4 border border-slate-100 dark:border-slate-800">
                        <h4 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-3">Quick Fill (Set values for all label rows)</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Global SPK</label>
                                <input 
                                    v-model="globalSpk" 
                                    type="text" 
                                    placeholder="Enter SPK Number..." 
                                    class="w-full text-xs rounded-xl bg-white dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-blue-500 focus:border-blue-500"
                                />
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Global Lot Number</label>
                                <input 
                                    v-model="globalLotNumber" 
                                    type="text" 
                                    placeholder="Enter Lot Number..." 
                                    class="w-full text-xs rounded-xl bg-white dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-blue-500 focus:border-blue-500"
                                />
                            </div>
                            <div class="flex items-end gap-2">
                                <div class="flex-1">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Global Note</label>
                                    <input 
                                        v-model="globalNote" 
                                        type="text" 
                                        placeholder="Enter Note..." 
                                        class="w-full text-xs rounded-xl bg-white dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-blue-500 focus:border-blue-500"
                                    />
                                </div>
                                <button 
                                    type="button" 
                                    @click="applyGlobalValues" 
                                    class="h-[38px] px-4 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold transition-colors shadow-lg shadow-blue-500/10 whitespace-nowrap"
                                >
                                    Apply
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Items List -->
                    <div class="space-y-4">
                        <h4 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Label Configuration</h4>
                        <div class="border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead class="bg-slate-50 dark:bg-slate-800/40 text-[10px] font-bold text-slate-500 uppercase border-b border-slate-200 dark:border-slate-800">
                                    <tr>
                                        <th class="p-4 text-center w-12 border-r border-slate-200 dark:border-slate-800">Select</th>
                                        <th class="p-4 w-48 border-r border-slate-200 dark:border-slate-800">Product</th>
                                        <th class="p-4 w-28 border-r border-slate-200 dark:border-slate-800">Qty / Label</th>
                                        <th class="p-4 w-24 border-r border-slate-200 dark:border-slate-800">Labels</th>
                                        <th class="p-4 w-32 border-r border-slate-200 dark:border-slate-800">SPK</th>
                                        <th class="p-4 w-32 border-r border-slate-200 dark:border-slate-800">Lot Number</th>
                                        <th class="p-4 w-32 border-r border-slate-200 dark:border-slate-800">Size</th>
                                        <th class="p-4 w-32 border-r border-slate-200 dark:border-slate-800">Specification</th>
                                        <th class="p-4 w-32 border-r border-slate-200 dark:border-slate-800">Note</th>
                                        <th class="p-4 w-28 text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                                    <tr v-for="item in printLabelItems" :key="item.key" class="hover:bg-slate-50 dark:hover:bg-slate-800/20" :class="{'opacity-50': !item.selected}">
                                        <td class="p-4 text-center border-r border-slate-200 dark:border-slate-800">
                                            <input 
                                                type="checkbox" 
                                                v-model="item.selected" 
                                                class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                                            />
                                        </td>
                                        <td class="p-4 border-r border-slate-200 dark:border-slate-800">
                                            <div class="font-bold text-slate-900 dark:text-white">{{ item.product_name }}</div>
                                            <div class="text-[10px] text-slate-500 font-mono mt-0.5">{{ item.sku }}</div>
                                            <div class="text-[10px] text-slate-400 mt-1">Output Qty: {{ formatNumber(item.qty_delivered) }} {{ item.unit_name }}</div>
                                        </td>
                                        <td class="p-4 border-r border-slate-200 dark:border-slate-800">
                                            <input 
                                                v-model.number="item.qty_per_label" 
                                                type="number" 
                                                :disabled="!item.selected"
                                                class="w-full text-xs rounded-xl bg-white dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white focus:ring-blue-500 focus:border-blue-500 font-mono"
                                            />
                                        </td>
                                        <td class="p-4 border-r border-slate-200 dark:border-slate-800">
                                            <input 
                                                v-model.number="item.label_count" 
                                                type="number" 
                                                min="1"
                                                :disabled="!item.selected"
                                                class="w-full text-xs rounded-xl bg-white dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white focus:ring-blue-500 focus:border-blue-500 font-mono"
                                            />
                                        </td>
                                        <td class="p-4 border-r border-slate-200 dark:border-slate-800">
                                            <input 
                                                v-model="item.spk" 
                                                type="text" 
                                                :disabled="!item.selected"
                                                class="w-full text-xs rounded-xl bg-white dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white focus:ring-blue-500 focus:border-blue-500"
                                            />
                                        </td>
                                        <td class="p-4 border-r border-slate-200 dark:border-slate-800">
                                            <input 
                                                v-model="item.lot_number" 
                                                type="text" 
                                                :disabled="!item.selected"
                                                class="w-full text-xs rounded-xl bg-white dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white focus:ring-blue-500 focus:border-blue-500"
                                            />
                                        </td>
                                        <td class="p-4 border-r border-slate-200 dark:border-slate-800">
                                            <input 
                                                v-model="item.size" 
                                                type="text" 
                                                :disabled="!item.selected"
                                                class="w-full text-xs rounded-xl bg-white dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white focus:ring-blue-500 focus:border-blue-500"
                                            />
                                        </td>
                                        <td class="p-4 border-r border-slate-200 dark:border-slate-800">
                                            <input 
                                                v-model="item.specification" 
                                                type="text" 
                                                :disabled="!item.selected"
                                                class="w-full text-xs rounded-xl bg-white dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white focus:ring-blue-500 focus:border-blue-500"
                                            />
                                        </td>
                                        <td class="p-4 border-r border-slate-200 dark:border-slate-800">
                                            <input 
                                                v-model="item.note" 
                                                type="text" 
                                                :disabled="!item.selected"
                                                class="w-full text-xs rounded-xl bg-white dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white focus:ring-blue-500 focus:border-blue-500"
                                            />
                                        </td>
                                        <td class="p-4 text-center flex items-center gap-1.5 justify-center">
                                            <button 
                                                type="button" 
                                                @click="autoSplitRow(item)" 
                                                :disabled="!item.selected"
                                                class="p-1 rounded bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/40 transition-colors disabled:opacity-40"
                                                title="Bagi Quantitas Otomatis"
                                            >
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" /></svg>
                                            </button>
                                            <button 
                                                type="button" 
                                                @click="duplicateLabelRow(item)" 
                                                :disabled="!item.selected"
                                                class="p-1 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors disabled:opacity-40"
                                                title="Tambah Baris Konfigurasi"
                                            >
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                            </button>
                                            <button 
                                                v-if="canDeleteRow(item)"
                                                type="button" 
                                                @click="deleteLabelRow(item)" 
                                                class="p-1 rounded bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 hover:bg-rose-100 dark:hover:bg-rose-900/40 transition-colors"
                                                title="Hapus Baris"
                                            >
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-3">
                    <button 
                        @click="showPrintLabelModal = false" 
                        class="px-4 py-2 text-sm font-medium text-slate-500 hover:text-slate-700 transition-colors"
                    >
                        Batal
                    </button>
                    <button 
                        @click="submitPrintLabels" 
                        class="px-6 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-sm font-bold transition-all shadow-lg shadow-blue-500/20"
                    >
                        Print Labels
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
