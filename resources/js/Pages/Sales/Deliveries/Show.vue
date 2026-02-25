<script setup>
import { ref, computed, watch, onBeforeUnmount } from 'vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Html5Qrcode } from 'html5-qrcode';
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
    ExclamationTriangleIcon,
    InformationCircleIcon,
    XMarkIcon,
    FunnelIcon,
    QrCodeIcon,
    CameraIcon,
} from '@heroicons/vue/24/outline';
import { formatNumber, formatCurrency } from '@/helpers';

// --- ROLE-BASED ACCESS CONTROL ---
const page = usePage();
const userRoles = computed(() => page.props.auth?.roles || []);

const hasRole = (...roles) => {
    if (userRoles.value.includes('Super Admin')) return true;
    return roles.some(r => userRoles.value.includes(r));
};


// Permission map per action
const canSave = computed(() => hasRole('Sales', 'Sales Manager'));
const canLoading = computed(() => hasRole('Warehouse Manager'));
const canShip = computed(() => hasRole('Warehouse Manager'));
const canVerify = computed(() => hasRole('Sales Manager', 'Director'));
const canReset = computed(() => hasRole()); // Super Admin only (handled by hasRole)
const canInvoice = computed(() => hasRole('Sales', 'Sales Manager'));

// Smart action permission based on current status
const canDoNextAction = computed(() => {
    const status = props.deliveryOrder.status;
    switch (status) {
        case 'draft': return canLoading.value;
        case 'picking': return canLoading.value;
        case 'packed': return canShip.value;
        case 'shipped': return canShip.value;
        case 'delivered': return canVerify.value;
        default: return false;
    }
});

// --- PRINT READINESS CHECK ---
const showPrintWarning = ref(false);

const printReadiness = computed(() => {
    const checks = [];
    const o = props.deliveryOrder;
    checks.push({ label: 'Items terisi', ok: o.items && o.items.length > 0 });
    checks.push({ label: 'Armada (No Truk) terisi', ok: !!o.vehicle_number });
    checks.push({ label: 'Sopir (Driver) terisi', ok: !!o.driver_name });
    checks.push({ label: 'Tanggal kirim terisi', ok: !!o.delivery_date });
    return checks;
});

const isReadyToPrint = computed(() => printReadiness.value.every(c => c.ok));

const handlePrint = () => {
    if (!isReadyToPrint.value) {
        showPrintWarning.value = true;
    } else {
        window.open(route('sales.deliveries.print', props.deliveryOrder.id), '_blank');
    }
};

const forcePrint = () => {
    showPrintWarning.value = false;
    window.open(route('sales.deliveries.print', props.deliveryOrder.id), '_blank');
};

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

const showAddItemModal = ref(false);
const addItemLoading = ref(false);
const addItemOptions = ref([]);
const addItemSearch = ref('');

const addItemMode = computed(() => {
    const so = props.deliveryOrder.sales_order;
    if (!so) return 'direct';
    return so.status === 'waiting_po' ? 'direct' : 'so';
});

const addItemForm = useForm({
    sales_order_item_id: '',
    product_id: '',
    qty_delivered: '',
    notes: ''
});

const openAddItemModal = async () => {
    addItemForm.reset();
    addItemForm.clearErrors();
    addItemSearch.value = '';
    addItemOptions.value = [];
    showAddItemModal.value = true;
    await fetchAddItemOptions();
};

const fetchAddItemOptions = async () => {
    addItemLoading.value = true;
    try {
        const params = new URLSearchParams();
        if (addItemMode.value === 'direct' && addItemSearch.value.trim() !== '') {
            params.set('q', addItemSearch.value.trim());
        }
        const url = route('sales.deliveries.add-item-options', props.deliveryOrder.id) + (params.toString() ? `?${params.toString()}` : '');
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        addItemOptions.value = data.items || [];
    } finally {
        addItemLoading.value = false;
    }
};

const onAddItemSelect = () => {
    if (addItemMode.value !== 'so') return;
    const selected = addItemOptions.value.find(o => String(o.sales_order_item_id) === String(addItemForm.sales_order_item_id));
    if (selected) {
        addItemForm.qty_delivered = selected.remaining;
    }
};

const submitAddItem = () => {
    addItemForm.post(route('sales.deliveries.store-item', props.deliveryOrder.id), {
        preserveScroll: true,
        onSuccess: () => {
            showAddItemModal.value = false;
            addItemForm.reset();
        }
    });
};

const showScanModal = ref(false);
const scanError = ref('');
const scanning = ref(false);
const scanResult = ref(null);
let html5QrCode = null;

const parseScanPayload = (text) => {
    const raw = String(text || '').trim();
    if (!raw) return null;

    try {
        const obj = JSON.parse(raw);
        const sku = obj.sku || obj.SKU;
        const qty = obj.qty || obj.QTY || obj.quantity || obj.QUANTITY;
        if (sku) {
            return { sku: String(sku).trim().toUpperCase(), qty: qty !== undefined ? Number(qty) : 1 };
        }
    } catch (e) {}

    const pairs = raw.split('|').map(p => p.trim()).filter(Boolean);
    const map = {};
    for (const p of pairs) {
        const [k, ...rest] = p.split(':');
        if (!k || rest.length === 0) continue;
        map[k.trim().toUpperCase()] = rest.join(':').trim();
    }

    const skuFromPair = map.SKU || map.PRODUCT || map.ITEM;
    const qtyFromPair = map.QTY || map.QUANTITY;
    if (skuFromPair) {
        return {
            sku: String(skuFromPair).trim().toUpperCase(),
            qty: qtyFromPair !== undefined ? Number(qtyFromPair) : 1,
        };
    }

    const skuMatch = raw.match(/SKU\s*[:=]\s*([A-Z0-9._-]+)/i);
    const qtyMatch = raw.match(/QTY\s*[:=]\s*([0-9]+(?:\.[0-9]+)?)/i);
    if (skuMatch) {
        return { sku: skuMatch[1].trim().toUpperCase(), qty: qtyMatch ? Number(qtyMatch[1]) : 1 };
    }

    const token = raw.match(/[A-Z0-9._-]{3,}/i);
    if (token) {
        return { sku: token[0].trim().toUpperCase(), qty: 1 };
    }

    return null;
};

const openScanModal = async () => {
    scanError.value = '';
    scanResult.value = null;
    showScanModal.value = true;
};

const startScan = async () => {
    scanError.value = '';
    scanResult.value = null;
    scanning.value = true;

    try {
        html5QrCode = new Html5Qrcode('do-qr-reader');
        await html5QrCode.start(
            { facingMode: 'environment' },
            { fps: 10, qrbox: { width: 260, height: 260 } },
            async (decodedText) => {
                await stopScan();
                const payload = parseScanPayload(decodedText);
                if (!payload) {
                    scanError.value = 'QR tidak terbaca (format tidak dikenali).';
                    return;
                }

                const sku = payload.sku;
                const addQty = Number(payload.qty) > 0 ? Number(payload.qty) : 1;
                const idx = props.deliveryOrder.items.findIndex(i => String(i.product?.sku || '').toUpperCase() === sku);
                if (idx < 0) {
                    scanError.value = `SKU ${sku} tidak ada di DO ini.`;
                    return;
                }

                const max = Number(getRemainingBeforeThis(props.deliveryOrder.items[idx])) || 0;
                const current = Number(form.items[idx].qty_delivered) || 0;
                const next = Math.min(current + addQty, max);
                form.items[idx].qty_delivered = next;

                scanResult.value = { sku, qty: addQty, applied: next - current, max };
            },
            () => {}
        );
    } catch (err) {
        scanError.value = 'Gagal mengakses kamera: ' + (err?.message || String(err));
        scanning.value = false;
    }
};

const stopScan = async () => {
    if (html5QrCode && html5QrCode.isScanning) {
        await html5QrCode.stop();
    }
    scanning.value = false;
};

watch(showScanModal, async (open) => {
    if (!open) {
        scanError.value = '';
        scanResult.value = null;
        await stopScan();
    }
});

onBeforeUnmount(async () => {
    await stopScan();
});

// --- CONFIRMATION MESSAGES PER STATUS ---
const statusConfirmMessages = {
    picking: '📦 START LOADING\n\nItems will start being picked and loaded.\nPlease ensure items and quantities are correct before continuing.\n\nContinue?',
    packed: '✅ SELESAI LOADING\n\nBarang sudah selesai dimuat dan siap dikirim.\nPastikan semua item sudah masuk ke truk.\n\nLanjutkan?',
    shipped: '🚛 DEPARTURE\n\nTruck will depart to the destination.\nPlease ensure vehicle and driver are correct.\n\nPlate No: ' + (props.deliveryOrder.vehicle_number || '-') + '\nDriver: ' + (props.deliveryOrder.driver_name || '-') + '\n\nContinue?',
    delivered: '📍 ARRIVED AT DESTINATION\n\nItems have arrived at the customer\'s location.\nStatus will change to "Delivered".\n\nContinue?',
    completed: '🏁 VERIFICATION COMPLETE\n\nDelivery will be marked as completed.\nStock will be reduced and SO will be updated.\n\nContinue?',
    draft: '⚠️ REVISE (RESET)\n\nStatus will be reverted to Draft.\nStock that was reduced will be returned.\n\nAre you sure you want to reset?',
};

const updateStatus = (status) => {
    const msg = statusConfirmMessages[status] || `Update status delivery ini menjadi '${status.toUpperCase()}'?`;
    if (confirm(msg)) {
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
    { label: 'CREATE DO', status: 'draft', icon: DocumentPlusIcon, desc: 'Sales', pic: 'Sales' },
    { label: 'LOADING', status: ['picking', 'packed'], icon: CubeIcon, desc: 'Gudang', pic: 'Warehouse' },
    { label: 'SHIPPING', status: 'shipped', icon: TruckIcon, desc: 'Logistik', pic: 'Logistics' },
    { label: 'ARRIVED', status: 'delivered', icon: MapPinIconSolid, desc: 'Driver', pic: 'Driver' },
    { label: 'VERIFIED', status: 'completed', icon: CheckBadgeIcon, desc: 'Admin', pic: 'Admin' },
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
                label: 'FINISH LOADING',
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
    if (!canDoNextAction.value) {
        alert('Anda tidak memiliki akses untuk melakukan aksi ini. Hubungi Admin.');
        return;
    }
    
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
                    <!-- 1. Print SJ with readiness check -->
                    <button 
                        type="button"
                        @click="handlePrint"
                        class="relative flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-800 px-4 py-2.5 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors border border-slate-200 dark:border-slate-700"
                    >
                        <PrinterIcon class="h-4 w-4" />
                        PRINT SJ
                        <span v-if="!isReadyToPrint" class="absolute -top-1 -right-1 w-3 h-3 bg-amber-500 rounded-full animate-pulse" title="Data belum lengkap"></span>
                    </button>
                    

                    <!-- 2. Save Changes (Only in Draft + Authorized Role) -->
                    <button 
                        v-if="deliveryOrder.status === 'draft' && canSave"
                        @click="updateDelivery"
                        :disabled="!form.isDirty || form.processing"
                        class="flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition-all shadow-lg disabled:opacity-30 disabled:grayscale disabled:cursor-not-allowed"
                        :class="form.isDirty ? 'bg-blue-600 text-white hover:bg-blue-500 shadow-blue-500/20' : 'bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-600 border border-slate-200 dark:border-slate-700'"
                    >
                        Save Changes
                    </button>
                    
                    <!-- 3. SMART ACTION BUTTON (Pipeline Progression + Role Check) -->
                    <button 
                        v-if="nextAction && canDoNextAction"
                        @click="handleSmartAction"
                        :disabled="form.isDirty || form.processing"
                        class="flex items-center gap-2 rounded-xl px-6 py-2.5 text-sm font-black uppercase tracking-wide text-white shadow-lg transition-all hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100"
                        :class="nextAction.color"
                    >
                        <component :is="nextAction.icon" class="h-5 w-5" />
                        {{ nextAction.label }}
                    </button>
                    <!-- Show disabled hint if no permission -->
                    <div v-else-if="nextAction && !canDoNextAction" class="flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs font-bold text-slate-400 bg-slate-100 dark:bg-slate-800 border border-dashed border-slate-300 dark:border-slate-700 cursor-not-allowed" :title="'Aksi ini hanya untuk PIC ' + steps[currentStepIndex]?.pic">
                        <component :is="nextAction.icon" class="h-4 w-4" />
                        {{ nextAction.label }} ({{ steps[currentStepIndex]?.pic }})
                    </div>

                    <!-- Revert/Revise Button (Super Admin Only) -->
                    <button 
                        v-if="['delivered', 'completed'].includes(deliveryOrder.status) && canReset"
                        @click="updateStatus('draft')"
                        class="flex items-center gap-2 rounded-xl bg-red-50 dark:bg-red-900/20 px-4 py-2.5 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/40 transition-colors border border-red-200 dark:border-red-900/50"
                        title="Kembalikan status ke Draft untuk revisi (Stok akan dikembalikan)"
                    >
                        <ArrowPathIcon class="h-4 w-4" />
                        REVISE (RESET)
                    </button>

                    <!-- 4. Create Invoice (Post-Delivery + Role Check) -->
                    <Link 
                        v-if="deliveryOrder.status === 'completed' && canInvoice"
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
                            <span class="text-[8px] font-bold mt-0.5" :class="index <= currentStepIndex ? 'text-slate-400' : 'text-slate-300 dark:text-slate-700'">{{ step.desc }}</span>
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
                            <div class="flex items-center gap-3">
                                <button
                                    v-if="deliveryOrder.status === 'draft'"
                                    type="button"
                                    @click="openAddItemModal"
                                    class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-slate-900 text-white text-xs font-bold hover:bg-slate-700 transition-colors"
                                >
                                    <DocumentPlusIcon class="h-4 w-4" />
                                    Add Product
                                </button>
                                <button
                                    v-if="['draft','picking'].includes(deliveryOrder.status) && (canLoading || canSave)"
                                    type="button"
                                    @click="openScanModal"
                                    class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-blue-600 text-white text-xs font-bold hover:bg-blue-500 transition-colors"
                                >
                                    <QrCodeIcon class="h-4 w-4" />
                                    Scan Loading
                                </button>
                                <div class="text-[10px] font-bold text-slate-500 uppercase">Warehouse: {{ deliveryOrder.warehouse?.name }}</div>
                            </div>
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

    <!-- Print SJ Warning Modal -->
    <Teleport to="body">
        <div v-if="showPrintWarning" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
                <div class="px-6 py-4 bg-amber-50 dark:bg-amber-900/20 border-b border-amber-200 dark:border-amber-800/30 flex items-center gap-3">
                    <ExclamationTriangleIcon class="h-6 w-6 text-amber-500" />
                    <h3 class="text-sm font-bold text-amber-700 dark:text-amber-300 uppercase tracking-widest">Data Belum Lengkap</h3>
                    <button @click="showPrintWarning = false" class="ml-auto text-slate-400 hover:text-slate-600">
                        <XMarkIcon class="h-5 w-5" />
                    </button>
                </div>
                <div class="p-6 space-y-3">
                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">Surat Jalan akan dicetak dengan data berikut:</p>
                    <div v-for="check in printReadiness" :key="check.label" class="flex items-center gap-3">
                        <span v-if="check.ok" class="text-emerald-500 text-lg">✅</span>
                        <span v-else class="text-red-500 text-lg">❌</span>
                        <span class="text-sm" :class="check.ok ? 'text-slate-700 dark:text-slate-300' : 'text-red-600 dark:text-red-400 font-bold'">{{ check.label }}</span>
                    </div>
                </div>
                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-3">
                    <button @click="showPrintWarning = false" class="px-4 py-2 text-sm font-medium text-slate-500 hover:text-slate-700 transition-colors">Batal</button>
                    <button @click="forcePrint" class="px-4 py-2 rounded-xl bg-amber-500 text-white text-sm font-bold hover:bg-amber-400 transition-colors shadow-lg shadow-amber-500/20">Tetap Print</button>
                </div>
            </div>
        </div>
    </Teleport>

    <Teleport to="body">
        <div v-if="showAddItemModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-xl mx-4 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center gap-3">
                    <DocumentPlusIcon class="h-6 w-6 text-slate-500" />
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-widest">Add Product</h3>
                    <button @click="showAddItemModal = false" class="ml-auto text-slate-400 hover:text-slate-600">
                        <XMarkIcon class="h-5 w-5" />
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <div v-if="addItemMode === 'direct'" class="flex items-center gap-3">
                        <input
                            v-model="addItemSearch"
                            type="text"
                            placeholder="Cari product (nama / SKU)..."
                            class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                            @keyup.enter="fetchAddItemOptions"
                        />
                        <button
                            type="button"
                            @click="fetchAddItemOptions"
                            class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-bold hover:bg-slate-700 transition-colors"
                            :disabled="addItemLoading"
                        >
                            Cari
                        </button>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-tighter mb-1.5">
                            {{ addItemMode === 'so' ? 'Pilih Item SO (Open)' : 'Pilih Product' }}
                        </label>
                        <select
                            v-if="addItemMode === 'so'"
                            v-model="addItemForm.sales_order_item_id"
                            @change="onAddItemSelect"
                            class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                        >
                            <option value="">-- pilih --</option>
                            <option v-for="opt in addItemOptions" :key="opt.sales_order_item_id" :value="opt.sales_order_item_id">
                                {{ opt.sku }} - {{ opt.name }} (Sisa: {{ formatNumber(opt.remaining) }} {{ opt.unit_code || '' }})
                            </option>
                        </select>

                        <select
                            v-else
                            v-model="addItemForm.product_id"
                            class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                        >
                            <option value="">-- pilih --</option>
                            <option v-for="opt in addItemOptions" :key="opt.product_id" :value="opt.product_id">
                                {{ opt.sku }} - {{ opt.name }} ({{ opt.unit_code || '' }})
                            </option>
                        </select>
                        <div v-if="addItemLoading" class="text-xs text-slate-500 mt-2">Memuat...</div>
                        <div v-else-if="addItemOptions.length === 0" class="text-xs text-slate-500 mt-2">Tidak ada data.</div>
                        <div v-if="addItemForm.errors.sales_order_item_id" class="text-xs text-red-500 mt-2">{{ addItemForm.errors.sales_order_item_id }}</div>
                        <div v-if="addItemForm.errors.product_id" class="text-xs text-red-500 mt-2">{{ addItemForm.errors.product_id }}</div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-1">
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-tighter mb-1.5">Qty</label>
                            <input
                                v-model="addItemForm.qty_delivered"
                                type="number"
                                step="any"
                                min="0"
                                class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 text-sm font-bold text-center focus:ring-2 focus:ring-blue-500/50"
                            />
                            <div v-if="addItemForm.errors.qty_delivered" class="text-xs text-red-500 mt-2">{{ addItemForm.errors.qty_delivered }}</div>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-tighter mb-1.5">Remarks</label>
                            <input
                                v-model="addItemForm.notes"
                                type="text"
                                placeholder="catatan..."
                                class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                            />
                            <div v-if="addItemForm.errors.notes" class="text-xs text-red-500 mt-2">{{ addItemForm.errors.notes }}</div>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-3">
                    <button @click="showAddItemModal = false" class="px-4 py-2 text-sm font-medium text-slate-500 hover:text-slate-700 transition-colors">Batal</button>
                    <button
                        type="button"
                        @click="submitAddItem"
                        class="px-4 py-2 rounded-xl bg-blue-600 text-white text-sm font-bold hover:bg-blue-500 transition-colors disabled:opacity-50"
                        :disabled="addItemForm.processing || addItemLoading"
                    >
                        Tambah
                    </button>
                </div>
            </div>
        </div>
    </Teleport>

    <Teleport to="body">
        <div v-if="showScanModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-xl mx-4 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center gap-3">
                    <QrCodeIcon class="h-6 w-6 text-slate-500" />
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-widest">Scan Loading</h3>
                    <button @click="showScanModal = false" class="ml-auto text-slate-400 hover:text-slate-600">
                        <XMarkIcon class="h-5 w-5" />
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <div
                        id="do-qr-reader"
                        class="rounded-2xl overflow-hidden border-2 border-dashed border-blue-300 dark:border-blue-700 bg-slate-100 dark:bg-slate-800 min-h-[280px]"
                        :class="scanning ? 'border-solid border-blue-500' : ''"
                    ></div>

                    <div class="flex items-center gap-3">
                        <button
                            v-if="!scanning"
                            type="button"
                            @click="startScan"
                            class="w-full py-3 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-sm font-black uppercase tracking-wide shadow-lg shadow-blue-500/30 hover:from-blue-500 hover:to-indigo-500 active:scale-95 transition-all flex items-center justify-center gap-2"
                        >
                            <CameraIcon class="h-5 w-5" />
                            BUKA KAMERA
                        </button>
                        <button
                            v-else
                            type="button"
                            @click="stopScan"
                            class="w-full py-3 rounded-2xl bg-gradient-to-r from-red-600 to-red-500 text-white text-sm font-bold shadow-lg shadow-red-500/30 hover:from-red-500 hover:to-red-400 active:scale-95 transition-all flex items-center justify-center gap-2"
                        >
                            <XMarkIcon class="h-5 w-5" />
                            TUTUP KAMERA
                        </button>
                    </div>

                    <div v-if="scanError" class="bg-red-50 dark:bg-red-900/20 rounded-2xl p-4 border border-red-200 dark:border-red-800/30">
                        <div class="text-sm font-bold text-red-600 dark:text-red-400">{{ scanError }}</div>
                    </div>

                    <div v-if="scanResult" class="bg-emerald-50 dark:bg-emerald-900/20 rounded-2xl p-4 border border-emerald-200 dark:border-emerald-800/30">
                        <div class="text-sm font-bold text-emerald-700 dark:text-emerald-300">
                            {{ scanResult.sku }} +{{ formatNumber(scanResult.applied) }} (max {{ formatNumber(scanResult.max) }})
                        </div>
                        <div class="text-xs text-slate-600 dark:text-slate-400 mt-1">
                            Jika ingin menyimpan, tekan Save Changes.
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-3">
                    <button @click="showScanModal = false" class="px-4 py-2 text-sm font-medium text-slate-500 hover:text-slate-700 transition-colors">Tutup</button>
                </div>
            </div>
        </div>
    </Teleport>

</template>
