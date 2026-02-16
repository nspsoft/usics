<script setup>
import { computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ArrowLeftIcon,
    TruckIcon,
    PrinterIcon,
    CheckCircleIcon,
    CalendarIcon,
    UserIcon,
    MapPinIcon,
    DocumentTextIcon,
    TrashIcon,
    DocumentPlusIcon,
    CubeIcon,
    MapPinIcon as MapPinIconSolid,
    CheckBadgeIcon,
    ArrowPathIcon,
} from '@heroicons/vue/24/outline';
import { formatNumber, formatCurrency } from '@/helpers';

const props = defineProps({
    deliveryOrder: Object,
    vehicles: Array
});

const form = useForm({
    vehicle_id: props.deliveryOrder.vehicle_id || '',
    vehicle_number: props.deliveryOrder.vehicle_number || '',
    driver_name: props.deliveryOrder.driver_name || '',
    delivery_date: props.deliveryOrder.delivery_date ? new Date(props.deliveryOrder.delivery_date).toISOString().split('T')[0] : new Date().toISOString().split('T')[0],
    items: props.deliveryOrder.items.map(item => ({
        id: item.id,
        qty_delivered: parseFloat(item.qty_delivered),
        notes: item.notes || ''
    }))
});

const onVehicleChange = () => {
    const selected = props.vehicles.find(v => v.id === form.vehicle_id);
    if (selected) {
        form.vehicle_number = selected.license_plate;
        form.driver_name = selected.driver_name || '';
    } else {
        form.vehicle_number = '';
        form.driver_name = '';
    }
};

const updateDelivery = () => {
    form.put(route('sales.deliveries.update-items', props.deliveryOrder.id), {
        preserveScroll: true,
        onSuccess: () => alert('Delivery updated successfully.')
    });
};

const completeDelivery = () => {
    if (confirm('Are you sure you want to complete this delivery? This will reduce stock and update the Sales Order.')) {
        form.post(route('sales.deliveries.complete', props.deliveryOrder.id));
    }
};

const removeItem = (id) => {
    if (confirm('Yakin ingin menghapus item ini dari pengiriman sekarang? Item ini akan tetap tersedia untuk pengiriman berikutnya.')) {
        router.delete(route('sales.deliveries.destroy-item', id), {
            preserveScroll: true
        });
    }
};

const updateStatus = (status) => {
    if (confirm(`Update status delivery ini menjadi '${status.toUpperCase()}'?`)) {
        router.patch(route('sales.deliveries.update-status', props.deliveryOrder.id), {
            status: status
        }, {
            preserveScroll: true,
            onError: (errors) => alert('Gagal update status: ' + JSON.stringify(errors)),
            onSuccess: () => { /* Inertia auto reload */ }
        });
    }
};

const getRemainingBeforeThis = (item) => {
    const soQty = parseFloat(item.sales_order_item?.qty || 0);
    const totalDeliveredSoItem = parseFloat(item.sales_order_item?.qty_delivered || 0);
    const totalReturnedSoItem = parseFloat(item.sales_order_item?.qty_returned || 0);
    const totalReserved = parseFloat(item.sales_order_item?.reserved_qty || 0);
    
    // Effective Delivered = Total Delivered - Total Returned
    const effectiveDelivered = totalDeliveredSoItem - totalReturnedSoItem;

    if (['delivered', 'completed'].includes(props.deliveryOrder.status)) {
        // If already delivered, totalDelivered includes THIS item.
        // Reserved doesn't include delivered items.
        return soQty - (effectiveDelivered - parseFloat(item.qty_delivered));
    }
    
    // If draft/active: 
    // totalReserved includes THIS item's current qty in DB.
    // We want sisa EXCLUDING this DO.
    const reservedByOthers = totalReserved - parseFloat(item.qty_delivered);
    
    return soQty - effectiveDelivered - reservedByOthers;
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', { 
        day: '2-digit', 
        month: 'long', 
        year: 'numeric' 
    });
};


const getStatusBadge = (status) => {
    const badges = {
        draft: 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30',
        picking: 'bg-amber-500/20 text-amber-500 border-amber-500/30',
        packed: 'bg-blue-500/20 text-blue-500 border-blue-500/30',
        shipped: 'bg-purple-500/20 text-purple-400 border-purple-500/30',
        delivered: 'bg-teal-500/20 text-teal-400 border-teal-500/30',
        completed: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
        cancelled: 'bg-red-500/20 text-red-400 border-red-500/30',
    };
    return badges[status] || 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30';
};

const steps = [
    { label: 'CREATE DO', status: 'draft', icon: DocumentPlusIcon },
    { label: 'LOADING', status: ['picking', 'packed'], icon: CubeIcon },
    { label: 'SHIPPING', status: 'shipped', icon: TruckIcon },
    { label: 'ARRIVED', status: 'delivered', icon: MapPinIconSolid },
    { label: 'VERIFIED', status: 'completed', icon: CheckBadgeIcon },
];

    const currentStepIndex = computed(() => {
    const status = props.deliveryOrder.status;
    if (status === 'draft') return 0;
    if (['picking', 'packed'].includes(status)) return 1;
    if (status === 'shipped') return 2;
    if (status === 'delivered') return 3;
    if (status === 'completed') return 4;
    return -1;
});

// --- SMART ACTION BUTTON LOGIC ---
const nextAction = computed(() => {
    const status = props.deliveryOrder.status;
    switch (status) {
        case 'draft':
            return {
                label: 'START LOADING',
                target: 'picking',
                icon: CubeIcon,
                color: 'bg-gradient-to-r from-amber-600 to-amber-500 hover:from-amber-500 hover:to-amber-400 shadow-amber-500/30'
            };
        case 'picking':
            return {
                label: 'FINISH PACKING',
                target: 'packed',
                icon: CubeIcon,
                color: 'bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-500 hover:to-blue-400 shadow-blue-500/30'
            };
        case 'packed':
            return {
                label: 'SHIP ORDER',
                target: 'shipped',
                icon: TruckIcon,
                color: 'bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-400 shadow-indigo-500/30'
            };
        case 'shipped':
            return {
                label: 'ARRIVED AT LOCATION',
                target: 'delivered',
                icon: MapPinIconSolid,
                color: 'bg-gradient-to-r from-teal-600 to-teal-500 hover:from-teal-500 hover:to-teal-400 shadow-teal-500/30'
            };
        case 'delivered':
            return {
                label: 'CONFIRM & VERIFY',
                target: 'completed',
                icon: CheckCircleIcon,
                color: 'bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-500 hover:to-emerald-400 shadow-emerald-500/30'
            };
        default:
            return null;
    }
});

const handleSmartAction = () => {
    if (!nextAction.value) return;

    // Special handler for completion (uses different endpoint in original code, 
    // but updateStatus handles 'completed' logic too via controller, 
    // but let's stick to the main method for completion if needed or unify it)
    
    if (nextAction.value.target === 'completed') {
        completeDelivery();
    } else {
        updateStatus(nextAction.value.target);
    }
};
</script>

<template>
    <Head :title="`Delivery Order ${deliveryOrder.do_number}`" />
    
    <AppLayout title="Delivery Order Details">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div class="flex items-center gap-4">
                    <Link :href="route('sales.deliveries.index')" class="p-2 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white transition-colors">
                        <ArrowLeftIcon class="h-5 w-5" />
                    </Link>
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ deliveryOrder.do_number }}</h1>
                            <span :class="['px-2.5 py-0.5 rounded-full text-xs font-medium border uppercase', getStatusBadge(deliveryOrder.status)]">
                                {{ deliveryOrder.status }}
                            </span>
                        </div>
                        <p class="text-slate-500 text-sm mt-1">
                            Reference: 
                            <Link :href="route('sales.orders.show', deliveryOrder.sales_order_id)" class="text-blue-400 hover:underline font-medium">
                                {{ deliveryOrder.sales_order?.so_number }}
                            </Link>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <!-- 1. Print SJ (Informations/Documentation) -->
                    <a 
                        :href="route('sales.deliveries.print', deliveryOrder.id)" 
                        target="_blank"
                        class="flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-800 px-4 py-2.5 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors border border-slate-200 dark:border-slate-700"
                    >
                        <PrinterIcon class="h-4 w-4" />
                        Print SJ
                    </a>

                    <!-- 2. Save Changes (Only in Draft) -->
                    <button 
                        v-if="deliveryOrder.status === 'draft'"
                        @click="updateDelivery"
                        :disabled="!form.isDirty || form.processing"
                        class="flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition-all shadow-lg disabled:opacity-30 disabled:grayscale disabled:cursor-not-allowed"
                        :class="form.isDirty ? 'bg-blue-600 text-white hover:bg-blue-500 shadow-blue-500/20' : 'bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-600 border border-slate-200 dark:border-slate-700'"
                    >
                        Save Changes
                    </button>
                    
                    <!-- 3. SMART ACTION BUTTON (Pipeline Progression) -->
                    <button 
                        v-if="nextAction"
                        @click="handleSmartAction"
                        :disabled="form.isDirty || form.processing"
                        class="flex items-center gap-2 rounded-xl px-6 py-2.5 text-sm font-black uppercase tracking-wide text-white shadow-lg transition-all hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100"
                        :class="nextAction.color"
                    >
                        <component :is="nextAction.icon" class="h-5 w-5" />
                        {{ nextAction.label }}
                    </button>

                    <!-- NEW: Revert/Revise Button (Draft Reset) -->
                    <button 
                        v-if="['delivered', 'completed'].includes(deliveryOrder.status)"
                        @click="updateStatus('draft')"
                        class="flex items-center gap-2 rounded-xl bg-red-50 dark:bg-red-900/20 px-4 py-2.5 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/40 transition-colors border border-red-200 dark:border-red-900/50"
                        title="Kembalikan status ke Draft untuk revisi (Stok akan dikembalikan)"
                    >
                        <ArrowPathIcon class="h-4 w-4" />
                        Revisi (Reset)
                    </button>

                    <!-- 4. Create Invoice (Post-Delivery Process) -->
                    <Link 
                        v-if="deliveryOrder.status === 'completed'"
                        :href="route('sales.deliveries.create-invoice', deliveryOrder.id)"
                        method="post"
                        as="button"
                        class="flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-indigo-500 transition-colors shadow-lg shadow-indigo-500/20"
                    >
                        <DocumentTextIcon class="h-4 w-4" />
                        Create Invoice (This SJ)
                    </Link>
                </div>
            </div>

            <!-- Progress Pipeline -->
            <div class="mb-8 px-4 md:px-8">
                <div class="relative flex items-center justify-between">
                    <!-- Progress Line Background -->
                    <div class="absolute left-0 top-1/2 -mt-px w-full h-0.5 bg-slate-100 dark:bg-slate-800"></div>
                    
                    <!-- Active Progress Line -->
                    <div 
                        class="absolute left-0 top-1/2 -mt-px h-0.5 bg-blue-500 transition-all duration-500"
                        :style="{ width: `${(currentStepIndex / (steps.length - 1)) * 100}%` }"
                    ></div>

                    <!-- Steps -->
                    <div 
                        v-for="(step, index) in steps" 
                        :key="index"
                        class="relative flex flex-col items-center group"
                    >
                        <!-- Pulse effect for active step -->
                        <div 
                            v-if="index === currentStepIndex"
                            class="absolute inset-0 w-10 h-10 m-auto rounded-full bg-blue-400 animate-ping opacity-25 z-0"
                        ></div>

                        <div 
                            class="w-10 h-10 rounded-full flex items-center justify-center border-4 transition-all duration-500 z-10 shadow-sm"
                            :class="[
                                index < currentStepIndex 
                                    ? 'bg-emerald-500 border-white dark:border-slate-900 text-white scale-100' 
                                    : index === currentStepIndex
                                        ? 'bg-blue-500 border-white dark:border-slate-900 text-white scale-125 shadow-lg shadow-blue-500/50 animate-pulse'
                                        : 'bg-white dark:bg-slate-900 border-slate-100 dark:border-slate-800 text-slate-300 dark:text-slate-700 scale-90 opacity-60'
                            ]"
                        >
                            <component :is="step.icon" class="w-5 h-5 transition-transform duration-500" :class="{ 'rotate-12': index === currentStepIndex }" />
                        </div>
                        
                        <div class="absolute -bottom-8 flex flex-col items-center whitespace-nowrap">
                            <span 
                                class="text-[10px] font-black uppercase tracking-widest transition-all duration-500"
                                :class="[
                                    index < currentStepIndex ? 'text-emerald-500' :
                                    index === currentStepIndex ? 'text-blue-500 scale-110' : 
                                    'text-slate-400 dark:text-slate-600'
                                ]"
                            >
                                {{ step.label }}
                            </span>
                            <!-- Show real status below label if in multi-status step -->
                            <span 
                                v-if="index === 1 && ['picking', 'packed'].includes(deliveryOrder.status)"
                                class="text-[8px] font-bold text-blue-400 italic"
                            >
                                ({{ deliveryOrder.status }})
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mt-12">
                <!-- Info Block -->
                <div class="lg:col-span-4 space-y-6">
                    <div class="rounded-2xl glass-card p-6 shadow-sm">
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-6">Delivery Information</h3>
                        
                        <div class="space-y-5">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-tighter mb-1.5">No Truk (Vehicle)</label>
                                <select 
                                    v-if="deliveryOrder.status === 'draft'"
                                    v-model="form.vehicle_id"
                                    @change="onVehicleChange"
                                    class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                >
                                    <option value="">-- Pilih Armada --</option>
                                    <option v-for="v in vehicles" :key="v.id" :value="v.id">
                                        {{ v.license_plate }} - {{ v.vehicle_type }}
                                    </option>
                                    <option value="manual">-- Input Manual --</option>
                                </select>
                                <div v-else class="text-sm font-medium text-slate-900 dark:text-white bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800">
                                    {{ deliveryOrder.vehicle_number || '-' }}
                                </div>
                            </div>

                            <div v-if="form.vehicle_id === 'manual'">
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-tighter mb-1.5">Input No Truk Manual</label>
                                <input 
                                    v-model="form.vehicle_number"
                                    type="text"
                                    placeholder="e.g. B 1234 ABC"
                                    class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                />
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-tighter mb-1.5">Sopir (Driver)</label>
                                <input 
                                    v-if="deliveryOrder.status === 'draft'"
                                    v-model="form.driver_name"
                                    type="text"
                                    placeholder="e.g. Ahmad Sudarto"
                                    class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                />
                                <div v-else class="text-sm font-medium text-slate-900 dark:text-white bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800">
                                    {{ deliveryOrder.driver_name || '-' }}
                                </div>
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-tighter mb-1.5">Tanggal Kirim</label>
                                <input 
                                    v-if="deliveryOrder.status === 'draft'"
                                    v-model="form.delivery_date"
                                    type="date"
                                    class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                />
                                <div v-else class="text-sm font-medium text-slate-900 dark:text-white bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800">
                                    {{ formatDate(deliveryOrder.delivery_date) }}
                                </div>
                            </div>

                            <div class="pt-4 border-t border-slate-200 dark:border-slate-800/50">
                                <div class="text-[10px] font-bold text-slate-500 uppercase tracking-tighter mb-1">Customer</div>
                                <div class="text-sm text-slate-900 dark:text-white font-bold">{{ deliveryOrder.sales_order?.customer?.name }}</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">{{ deliveryOrder.shipping_address || deliveryOrder.sales_order?.shipping_address }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Items Block -->
                <div class="lg:col-span-8 space-y-6">
                    <div class="rounded-2xl glass-card overflow-hidden shadow-sm">
                        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/20 flex items-center justify-between">
                            <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-widest">Items to Deliver</h3>
                            <div class="text-[10px] font-bold text-slate-500 uppercase">Warehouse: {{ deliveryOrder.warehouse?.name }}</div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="bg-slate-50 dark:bg-slate-800/30">
                                        <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-tighter">Product</th>
                                        <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase text-center tracking-tighter">SO Qty</th>
                                        <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase text-center tracking-tighter">Sisa SO</th>
                                        <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase text-center tracking-tighter">Qty Stock</th>
                                        <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase text-center tracking-tighter">Send Qty</th>
                                        <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-tighter">UOM</th>
                                        <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-tighter">Remarks</th>
                                        <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase text-right tracking-tighter"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <tr v-for="(item, index) in deliveryOrder.items" :key="item.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/10 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-bold text-slate-900 dark:text-white">{{ item.product?.name }}</div>
                                            <div class="text-[10px] text-slate-500 font-mono tracking-tight">{{ item.product?.sku }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-center text-sm text-slate-500 dark:text-slate-400 font-medium">
                                            {{ formatNumber(item.sales_order_item?.qty || item.qty_ordered) }}
                                        </td>
                                        <td class="px-6 py-4 text-center text-sm text-amber-500 font-bold bg-amber-500/5">
                                            {{ formatNumber(getRemainingBeforeThis(item)) }}
                                        </td>
                                        <td class="px-6 py-4 text-center text-sm text-emerald-500 font-bold bg-emerald-500/5">
                                            {{ formatNumber(item.current_stock || 0) }}
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <template v-if="deliveryOrder.status === 'draft'">
                                                <div class="flex flex-col items-center gap-1">
                                                    <input 
                                                        v-model="form.items[index].qty_delivered"
                                                        type="number"
                                                        step="any"
                                                        min="0"
                                                        :max="getRemainingBeforeThis(item)"
                                                        class="w-24 rounded-xl border-0 bg-slate-50 dark:bg-slate-800 text-sm font-bold text-center focus:ring-2 focus:ring-blue-500/50"
                                                        :class="{ 
                                                            'text-blue-400': form.items[index].qty_delivered <= getRemainingBeforeThis(item),
                                                            'text-red-500 ring-2 ring-red-500/50 bg-red-500/10': form.items[index].qty_delivered > getRemainingBeforeThis(item)
                                                        }"
                                                    />
                                                    <div v-if="form.items[index].qty_delivered > getRemainingBeforeThis(item)" class="text-[9px] font-bold text-red-500 uppercase tracking-tight animate-pulse">
                                                        Melebihi Sisa!
                                                    </div>
                                                    <div v-else class="text-[9px] text-slate-500 uppercase font-medium">
                                                        Max: {{ getRemainingBeforeThis(item) }}
                                                    </div>
                                                </div>
                                            </template>
                                            <span v-else class="text-sm font-bold text-blue-400 bg-blue-400/10 px-3 py-1.5 rounded-xl border border-blue-400/20">
                                                {{ formatNumber(item.qty_delivered) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-xs font-bold text-slate-500">
                                            {{ item.unit?.code || 'PCS' }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <input 
                                                v-if="deliveryOrder.status === 'draft'"
                                                v-model="form.items[index].notes"
                                                type="text"
                                                placeholder="catatan..."
                                                class="w-full rounded-lg border-0 bg-slate-50 dark:bg-slate-800 text-[10px] text-slate-900 dark:text-white focus:ring-1 focus:ring-blue-500/50 py-1.5"
                                            />
                                            <div v-else class="text-[10px] text-slate-500 dark:text-slate-400 italic">
                                                {{ item.notes || '-' }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <button 
                                                v-if="deliveryOrder.status === 'draft'" 
                                                @click="removeItem(item.id)" 
                                                class="text-slate-500 hover:text-red-500 transition-colors p-2 rounded-xl hover:bg-red-500/10 group" 
                                                title="Remove Item"
                                            >
                                                <TrashIcon class="h-4 w-4 group-hover:scale-110 transition-transform" />
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Notes Block -->
                    <div class="rounded-2xl glass-card p-6 shadow-sm">
                        <div class="flex items-center gap-3 mb-4">
                            <DocumentTextIcon class="h-5 w-5 text-slate-500" />
                            <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-widest">Internal Notes</h3>
                        </div>
                        <p v-if="deliveryOrder.notes" class="text-sm text-slate-500 dark:text-slate-400 italic bg-slate-50 dark:bg-slate-800/30 p-4 rounded-xl border border-slate-200 dark:border-slate-800/50">
                            "{{ deliveryOrder.notes }}"
                        </p>
                        <p v-else class="text-xs text-slate-600 italic">No notes added to this delivery order.</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>



