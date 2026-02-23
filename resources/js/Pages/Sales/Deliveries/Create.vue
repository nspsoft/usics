<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ArrowLeftIcon,
    TrashIcon,
    PlusIcon,
    TruckIcon,
    CalendarIcon,
    MapPinIcon,
    UserIcon,
    InformationCircleIcon,
    ChevronDownIcon,
    ChevronUpIcon,
} from '@heroicons/vue/24/outline';
import { CheckCircleIcon } from '@heroicons/vue/24/solid';
import { formatNumber, formatCurrency } from '@/helpers';
import axios from 'axios';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    salesOrder: Object,
    salesOrders: Array,
    vehicles: Array,
    warehouses: Array,
    customers: Array,
    products: Array,
});

const soOptions = computed(() => [
    { id: '', label: '-- Tanpa Sold Order (Direct DO) --' },
    ...props.salesOrders.map(so => ({
        id: so.id,
        label: `${so.so_number} - ${so.customer?.name}`
    }))
]);

const customerOptions = computed(() => props.customers.map(c => ({
    id: c.id,
    label: `${c.code ? '[' + c.code + '] ' : ''}${c.name}`
})));

const productOptions = computed(() => props.products.map(p => ({
    id: p.id,
    label: `[${p.sku}] ${p.name}`,
    unit_id: p.unit_id,
    unit_name: p.unit?.name ?? 'Unit'
})));

const vehicleOptions = computed(() => [
    ...props.vehicles.map(v => ({
        id: v.id,
        label: `${v.license_plate} - ${v.driver_name || 'No Driver'}`
    })),
    { id: 'manual', label: 'Input Manual (No Truk Baru)' }
]);

const form = useForm({
    sales_order_id: props.salesOrder?.id || '',
    customer_id: '',
    warehouse_id: props.salesOrder?.warehouse_id || '',
    delivery_date: new Date().toISOString().split('T')[0],
    shipping_address: props.salesOrder?.shipping_address || '',
    vehicle_id: '',
    vehicle_number: '',
    driver_name: '',
    items: [],
});

const isLoading = ref(false);
const showGuide = ref(false);
const searchFilter = ref('');

const hasSalesOrder = computed(() => Boolean(form.sales_order_id));

const filteredItems = computed(() => {
    if (!searchFilter.value || !hasSalesOrder.value) return form.items;
    const q = searchFilter.value.toLowerCase();
    return form.items.filter(item => 
        (item.name && item.name.toLowerCase().includes(q)) ||
        (item.sku && item.sku.toLowerCase().includes(q))
    );
});

const onSOChange = async () => {
    form.items = []; // Clear items
    
    if (!form.sales_order_id) {
        // Direct DO Mode
        form.customer_id = '';
        form.shipping_address = '';
        return;
    }

    isLoading.value = true;
    try {
        const response = await axios.get(route('sales.deliveries.so-items', form.sales_order_id));
        const data = response.data;

        form.customer_id = data.customer_id;
        form.warehouse_id = data.warehouse_id;
        form.shipping_address = data.shipping_address;

        if (data.items.length === 0) {
            alert('Tidak ada item yang tersisa untuk dikirim pada Sales Order ini.');
        }

        form.items = data.items.map(item => ({
            sales_order_item_id: item.sales_order_item_id,
            product_id: item.product_id,
            name: item.name,
            sku: item.sku,
            qty_ordered: item.qty_ordered,
            remaining: item.remaining,
            qty_delivered: 0, // Default to 0 for partial delivery
            include: false,   // Checkbox for selection
            unit_id: item.unit_id,
            unit_name: item.unit_name,
            notes: '',
        }));
    } catch (error) {
        console.error('Error fetching SO Items:', error);
        alert('Gagal memuat item Sales Order.');
    } finally {
        isLoading.value = false;
    }
};

const onVehicleChange = () => {
    const selected = props.vehicles.find(v => v.id === form.vehicle_id);
    if (selected) {
        form.vehicle_number = selected.license_plate;
        form.driver_name = selected.driver_name || '';
    } else if (form.vehicle_id !== 'manual') {
        form.vehicle_number = '';
        form.driver_name = '';
    }
};

const addItem = () => {
    form.items.push({
        sales_order_item_id: null,
        product_id: '',
        name: '',
        sku: '',
        qty_ordered: 0,
        remaining: 0,
        qty_delivered: 1,
        unit_id: '',
        unit_name: '',
        notes: '',
    });
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

// === Partial Delivery Helpers ===
const includedCount = computed(() => form.items.filter(i => i.include && i.qty_delivered > 0).length);
const allSelected = computed(() => form.items.length > 0 && form.items.every(i => i.include));

const toggleSelectAll = () => {
    const newVal = !allSelected.value;
    form.items.forEach(item => {
        item.include = newVal;
        if (newVal && item.qty_delivered === 0) {
            item.qty_delivered = item.remaining;
        }
    });
};

const fillAllMax = () => {
    form.items.forEach(item => {
        item.include = true;
        item.qty_delivered = item.remaining;
    });
};

const clearAll = () => {
    form.items.forEach(item => {
        item.include = false;
        item.qty_delivered = 0;
    });
};

const onItemToggle = (item) => {
    if (item.include && item.qty_delivered === 0) {
        item.qty_delivered = item.remaining;
    }
    if (!item.include) {
        item.qty_delivered = 0;
    }
};

const onProductChange = (item, index) => {
    const product = props.products.find(p => p.id === item.product_id);
    if (product) {
        item.name = product.name;
        item.sku = product.sku;
        item.unit_id = product.unit_id;
        item.unit_name = product.unit?.name ?? 'Unit';
    }
};

const submit = () => {
    // Filter: only include checked items with qty > 0
    if (hasSalesOrder.value) {
        const shippableItems = form.items.filter(i => i.include && i.qty_delivered > 0);
        if (shippableItems.length === 0) {
            alert('Pilih minimal satu item dan isi qty kirim.');
            return;
        }
        // Create a copy with only the selected items
        const formData = form.transform(data => ({
            ...data,
            items: data.items.filter(i => i.include && i.qty_delivered > 0),
        }));
        formData.post(route('sales.deliveries.store'), {
            onSuccess: () => {},
            onError: (errors) => { console.error(errors); }
        });
        return;
    }
    
    if (!hasSalesOrder.value && form.items.length === 0) {
        alert('Tambahkan minimal satu item barang.');
        return;
    }

    form.post(route('sales.deliveries.store'), {
        onSuccess: () => {},
        onError: (errors) => { console.error(errors); }
    });
};

onMounted(() => {
    if (form.sales_order_id) {
        onSOChange();
    }
});
</script>

<template>
    <Head title="Create Delivery Order" />
    
    <AppLayout title="Deliveries">
        <div class="max-w-full px-4 sm:px-6 lg:px-8 mx-auto">
            <Link :href="route('sales.deliveries.index')" class="inline-flex items-center gap-2 mb-4 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white transition-colors">
                <ArrowLeftIcon class="h-4 w-4" /> Back to List
            </Link>

            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Create New Delivery</h2>
                    <p class="text-sm text-slate-500 mt-1">Siapkan Surat Jalan baru untuk pengiriman barang ke customer.</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
                    <!-- Left Column: Main Cargo Info -->
                    <div class="xl:col-span-4 space-y-6">
                        <!-- Reference Section -->
                        <div class="glass-card rounded-2xl p-6 border border-slate-200 dark:border-slate-800 shadow-sm">
                            <div class="flex items-center gap-2 mb-4">
                                <PlusIcon class="h-5 w-5 text-blue-500" />
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-widest">Reference & Date</h3>
                            </div>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-tighter mb-1.5">Sales Order (Opsional)</label>
                                    <SearchableSelect 
                                        v-model="form.sales_order_id" 
                                        :options="soOptions" 
                                        placeholder="Cari No SO... (Kosongkan utk Direct DO)"
                                        @change="onSOChange"
                                    />
                                </div>

                                <div v-if="!form.sales_order_id">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-tighter mb-1.5">Customer</label>
                                    <SearchableSelect 
                                        v-model="form.customer_id" 
                                        :options="customerOptions" 
                                        placeholder="Pilih Customer..."
                                        required
                                    />
                                </div>

                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-tighter mb-1.5">Warehouse Pengirim</label>
                                    <select v-model="form.warehouse_id" class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50" required>
                                        <option value="">Pilih Gudang</option>
                                        <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-tighter mb-1.5">Tanggal Kirim</label>
                                    <div class="relative">
                                        <CalendarIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                                        <input type="date" v-model="form.delivery_date" class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 pl-10 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 shadow-sm" required />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Fleet Section -->
                        <div class="glass-card rounded-2xl p-6 border border-slate-200 dark:border-slate-800 shadow-sm">
                            <div class="flex items-center gap-2 mb-4">
                                <TruckIcon class="h-5 w-5 text-emerald-500" />
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-widest">Fleet & Driver</h3>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-tighter mb-1.5">Pilih Armada</label>
                                    <SearchableSelect 
                                        v-model="form.vehicle_id" 
                                        :options="vehicleOptions" 
                                        placeholder="Pilih Truk..."
                                        @change="onVehicleChange"
                                    />
                                </div>

                                <div v-if="form.vehicle_id === 'manual'">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-tighter mb-1.5">Input No Truk Manual</label>
                                    <input 
                                        v-model="form.vehicle_number"
                                        type="text"
                                        placeholder="e.g. B 1234 ABC"
                                        class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 shadow-sm"
                                        required
                                    />
                                </div>

                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-tighter mb-1.5">Sopir (Driver)</label>
                                    <div class="relative">
                                        <UserIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                                        <input 
                                            v-model="form.driver_name"
                                            type="text"
                                            placeholder="Nama Sopir..."
                                            class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 pl-10 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 shadow-sm"
                                            required
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Address Section -->
                        <div class="glass-card rounded-2xl p-6 border border-slate-200 dark:border-slate-800 shadow-sm">
                            <div class="flex items-center gap-2 mb-4">
                                <MapPinIcon class="h-5 w-5 text-amber-500" />
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-widest">Shipping Address</h3>
                            </div>
                            <textarea 
                                v-model="form.shipping_address" 
                                rows="3" 
                                class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 shadow-sm"
                                placeholder="Alamat lengkap pengiriman..."
                            ></textarea>
                        </div>

                        <!-- Step-by-Step Guide -->
                        <div class="glass-card rounded-2xl border border-blue-200 dark:border-blue-800/50 shadow-sm overflow-hidden">
                            <button type="button" @click="showGuide = !showGuide" class="w-full px-6 py-4 flex items-center justify-between bg-blue-50/50 dark:bg-blue-900/10 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors">
                                <div class="flex items-center gap-2">
                                    <InformationCircleIcon class="h-5 w-5 text-blue-500" />
                                    <h3 class="text-sm font-bold text-blue-700 dark:text-blue-300 uppercase tracking-widest">Panduan</h3>
                                </div>
                                <ChevronDownIcon v-if="!showGuide" class="h-4 w-4 text-blue-400" />
                                <ChevronUpIcon v-else class="h-4 w-4 text-blue-400" />
                            </button>
                            <div v-show="showGuide" class="px-6 py-4 space-y-3 border-t border-blue-100 dark:border-blue-800/30 bg-white dark:bg-slate-900">
                                <div class="flex gap-3 items-start">
                                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-blue-500 text-white text-xs font-bold flex items-center justify-center">1</span>
                                    <div>
                                        <p class="text-sm font-bold text-slate-800 dark:text-slate-200">Pilih Sales Order</p>
                                        <p class="text-xs text-slate-500">Pilih SO yang item-nya akan dikirim, atau pilih "Direct DO" jika tanpa SO.</p>
                                    </div>
                                </div>
                                <div class="flex gap-3 items-start">
                                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-blue-500 text-white text-xs font-bold flex items-center justify-center">2</span>
                                    <div>
                                        <p class="text-sm font-bold text-slate-800 dark:text-slate-200">Centang Item & Isi Qty</p>
                                        <p class="text-xs text-slate-500">Centang item yang akan dikirim. Qty otomatis terisi sisa SO. Ubah jika pengiriman parsial.</p>
                                    </div>
                                </div>
                                <div class="flex gap-3 items-start">
                                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-blue-500 text-white text-xs font-bold flex items-center justify-center">3</span>
                                    <div>
                                        <p class="text-sm font-bold text-slate-800 dark:text-slate-200">Pilih Armada & Sopir</p>
                                        <p class="text-xs text-slate-500">Pilih truk dan isi nama sopir. Bisa juga diatur nanti via <strong>Delivery Planning</strong>.</p>
                                    </div>
                                </div>
                                <div class="flex gap-3 items-start">
                                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-blue-500 text-white text-xs font-bold flex items-center justify-center">4</span>
                                    <div>
                                        <p class="text-sm font-bold text-slate-800 dark:text-slate-200">Isi Alamat Pengiriman</p>
                                        <p class="text-xs text-slate-500">Alamat otomatis dari SO. Ubah jika tujuan berbeda.</p>
                                    </div>
                                </div>
                                <div class="flex gap-3 items-start">
                                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-emerald-500 text-white text-xs font-bold flex items-center justify-center">5</span>
                                    <div>
                                        <p class="text-sm font-bold text-slate-800 dark:text-slate-200">Simpan Surat Jalan</p>
                                        <p class="text-xs text-slate-500">Klik tombol <strong>"SIMPAN SURAT JALAN"</strong>. Status awal: Draft.</p>
                                    </div>
                                </div>
                                <div class="mt-3 pt-3 border-t border-blue-100 dark:border-blue-800/30">
                                    <div class="flex gap-2 items-start">
                                        <CheckCircleIcon class="h-4 w-4 text-emerald-500 flex-shrink-0 mt-0.5" />
                                        <p class="text-xs text-slate-500"><strong class="text-slate-700 dark:text-slate-300">Tips:</strong> Print Surat Jalan selalu mengacu data truk <strong>terakhir</strong>. Jika armada diubah via Delivery Planning, SJ otomatis ikut data terbaru.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Shipped Items -->
                    <div class="xl:col-span-8 space-y-6">
                        <div class="glass-card rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-800 shadow-sm min-h-[400px]">
                            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/30 flex justify-between items-center">
                                <div class="flex items-center gap-3">
                                    <h3 class="text-[10px] font-bold text-slate-900 dark:text-white uppercase tracking-widest">Items to Ship</h3>
                                    <span v-if="hasSalesOrder && form.items.length > 0" class="text-[10px] font-bold px-2 py-0.5 rounded-full" :class="includedCount > 0 ? 'bg-blue-500/10 text-blue-500' : 'bg-slate-200 text-slate-500'">
                                        {{ includedCount }} / {{ form.items.length }} item dipilih
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <template v-if="hasSalesOrder && form.items.length > 0">
                                        <button type="button" @click="fillAllMax" class="text-[10px] font-bold text-emerald-500 hover:text-emerald-400 px-2 py-1 rounded-lg hover:bg-emerald-500/10 transition-colors">
                                            ✅ Pilih Semua (Max)
                                        </button>
                                        <button type="button" @click="clearAll" class="text-[10px] font-bold text-slate-400 hover:text-red-400 px-2 py-1 rounded-lg hover:bg-red-500/10 transition-colors">
                                            ✖ Reset
                                        </button>
                                    </template>
                                    <button v-if="!hasSalesOrder" type="button" @click="addItem" class="text-xs font-bold text-blue-500 hover:text-blue-400 flex items-center gap-1">
                                        <PlusIcon class="h-3 w-3" /> ADD ITEM
                                    </button>
                                </div>
                            </div>

                            <!-- Search Filter -->
                            <div v-if="hasSalesOrder && form.items.length > 5" class="px-4 py-2 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                                <div class="relative">
                                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                                    <input 
                                        v-model="searchFilter"
                                        type="text"
                                        placeholder="Cari nama produk atau SKU..."
                                        class="w-full pl-9 pr-8 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 outline-none placeholder-slate-400"
                                    />
                                    <button v-if="searchFilter" type="button" @click="searchFilter = ''" class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors">
                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>
                            </div>

                            <div class="overflow-x-auto max-h-[600px] overflow-y-auto custom-scrollbar relative">
                                <table class="w-full text-left">
                                    <thead class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900 shadow-sm">
                                        <tr>
                                            <th v-if="hasSalesOrder" class="px-3 py-3 text-center w-10">
                                                <input 
                                                    type="checkbox" 
                                                    :checked="allSelected"
                                                    @change="toggleSelectAll"
                                                    class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                                    title="Pilih Semua"
                                                />
                                            </th>
                                            <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-tighter">Product</th>
                                            <th v-if="hasSalesOrder" class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase text-center tracking-tighter">Sisa SO</th>
                                            <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase text-center tracking-tighter">Qty Kirim</th>
                                            <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-tighter">UOM</th>
                                            <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-tighter">Notes</th>
                                            <th v-if="!hasSalesOrder" class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase text-right tracking-tighter"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50">
                                        <tr v-for="(item, index) in filteredItems" :key="item.sales_order_item_id || index" 
                                            class="transition-colors"
                                            :class="[
                                                hasSalesOrder 
                                                    ? (item.include ? 'bg-blue-50/50 dark:bg-blue-900/10 hover:bg-blue-50 dark:hover:bg-blue-900/20' : 'opacity-40 hover:opacity-60 hover:bg-slate-50 dark:hover:bg-slate-800/20') 
                                                    : 'hover:bg-slate-50 dark:hover:bg-slate-800/20'
                                            ]"
                                        >
                                            <td v-if="hasSalesOrder" class="px-3 py-4 text-center">
                                                <input 
                                                    v-model="item.include"
                                                    type="checkbox" 
                                                    class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                                    @change="onItemToggle(item)"
                                                />
                                            </td>
                                            <td class="px-6 py-4 min-w-[250px]">
                                                <div v-if="hasSalesOrder">
                                                    <div class="text-sm font-bold text-slate-900 dark:text-white">{{ item.name }}</div>
                                                    <div class="text-[10px] text-slate-500 font-mono">{{ item.sku }}</div>
                                                </div>
                                                <div v-else>
                                                    <SearchableSelect 
                                                        v-model="item.product_id" 
                                                        :options="productOptions" 
                                                        placeholder="Cari Produk..."
                                                        @change="onProductChange(item, index)"
                                                        class="w-full"
                                                    />
                                                </div>
                                            </td>
                                            <td v-if="hasSalesOrder" class="px-6 py-4 text-center">
                                                <span class="text-xs font-bold px-2.5 py-1 rounded-lg bg-blue-500/10 text-blue-500">
                                                    {{ formatNumber(item.remaining) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <input 
                                                    v-model="item.qty_delivered"
                                                    type="number"
                                                    step="any"
                                                    min="0"
                                                    :max="hasSalesOrder ? item.remaining : null"
                                                    class="w-24 rounded-lg border-0 bg-slate-50 dark:bg-slate-800 text-sm font-bold text-center focus:ring-1 focus:ring-blue-500"
                                                    :class="{ 
                                                        'text-red-500 ring-1 ring-red-500/50 bg-red-500/5': hasSalesOrder && item.qty_delivered > item.remaining,
                                                        'opacity-30': hasSalesOrder && !item.include 
                                                    }"
                                                    :disabled="hasSalesOrder && !item.include"
                                                    @focus="if (hasSalesOrder && !item.include) { item.include = true; item.qty_delivered = item.remaining; }"
                                                />
                                            </td>
                                            <td class="px-6 py-4 text-xs text-slate-500 font-medium whitespace-nowrap">
                                                {{ item.unit_name }}
                                            </td>
                                            <td class="px-6 py-4">
                                                <input 
                                                    v-model="item.notes"
                                                    type="text"
                                                    placeholder="..."
                                                    class="w-full rounded-lg border-0 bg-slate-50 dark:bg-slate-800 text-xs focus:ring-1 focus:ring-blue-500"
                                                />
                                            </td>
                                            <td v-if="!hasSalesOrder" class="px-6 py-4 text-right">
                                                <button type="button" @click="removeItem(index)" class="p-1.5 text-slate-400 hover:text-red-500 transition-colors">
                                                    <TrashIcon class="h-4 w-4" />
                                                </button>
                                            </td>
                                        </tr>
                                        <tr v-if="form.items.length === 0">
                                            <td :colspan="hasSalesOrder ? 7 : 6" class="px-6 py-12 text-center">
                                                <div v-if="isLoading" class="flex flex-col items-center gap-3">
                                                    <div class="h-8 w-8 animate-spin rounded-full border-4 border-blue-500 border-t-transparent"></div>
                                                    <span class="text-sm text-slate-500">Memuat item Sales Order...</span>
                                                </div>
                                                <div v-else class="flex flex-col items-center gap-2">
                                                    <TruckIcon class="h-10 w-10 text-slate-200 dark:text-slate-800" />
                                                    <span class="text-sm text-slate-500 italic">
                                                        {{ hasSalesOrder ? 'Tidak ada item.' : 'Klik + ADD ITEM untuk menambah barang.' }}
                                                    </span>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="flex items-center justify-between glass-card rounded-2xl p-6 border border-slate-200 dark:border-slate-800 shadow-lg">
                            <div class="text-xs text-slate-500 italic">
                                * Pastikan armada dan Qty kirim sudah sesuai sebelum menyimpan Draft.
                            </div>
                            <div class="flex items-center gap-3">
                                <Link :href="route('sales.deliveries.index')" class="rounded-xl px-6 py-2.5 text-sm font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all">
                                    Batal
                                </Link>
                                <button 
                                    type="submit"
                                    :disabled="form.processing || (hasSalesOrder ? includedCount === 0 : form.items.length === 0)"
                                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 px-8 py-2.5 text-sm font-bold text-white shadow-lg shadow-blue-500/20 hover:from-blue-500 hover:to-blue-400 transition-all disabled:opacity-50 active:scale-95"
                                >
                                    <TruckIcon class="h-5 w-5" />
                                    {{ form.processing ? 'Menyimpan...' : (hasSalesOrder ? `KIRIM ${includedCount} ITEM` : 'SIMPAN SURAT JALAN') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Chrome, Safari, Edge, Opera */
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

/* Firefox */
input[type=number] {
  -moz-appearance: textfield;
}

.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #1e293b;
    border-radius: 10px;
}
</style>
