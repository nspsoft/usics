<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ArrowLeftIcon,
    CubeIcon,
    PhotoIcon,
    QuestionMarkCircleIcon,
} from '@heroicons/vue/24/outline';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    product: Object,
    categories: Array,
    units: Array,
    warehouses: Array,
    customers: Array,
    suppliers: Array,
});

const customerOptions = computed(() => props.customers?.map(c => ({ id: c.id, label: c.name })) || []);
const supplierOptions = computed(() => props.suppliers?.map(s => ({ id: s.id, label: s.name })) || []);

const isEditing = computed(() => !!props.product);

const form = useForm({
    sku: props.product?.sku || '',
    name: props.product?.name || '',
    description: props.product?.description || '',
    barcode: props.product?.barcode || '',
    category_id: props.product?.category_id || '',
    customer_id: props.product?.customer_id || '',
    supplier_id: props.product?.supplier_id || '',
    type: props.product?.type || 'product',
    product_type: props.product?.product_type || 'finished_good',
    unit_id: props.product?.unit_id || '',
    cost_price: props.product?.cost_price || 0,
    selling_price: props.product?.selling_price || 0,
    min_stock: props.product?.min_stock || 0,
    max_stock: props.product?.max_stock || 0,
    reorder_point: props.product?.reorder_point || 0,
    reorder_qty: props.product?.reorder_qty || 0,
    is_manufactured: props.product?.is_manufactured || false,
    is_purchased: props.product?.is_purchased || true,
    is_sold: props.product?.is_sold || true,
    track_serial: props.product?.track_serial || false,
    track_batch: props.product?.track_batch || false,
    is_active: props.product?.is_active ?? true,
    initial_stocks: props.product?.stocks || [],
});

const submit = () => {
    if (isEditing.value) {
        form.put(`/inventory/products/${props.product.id}`);
    } else {
        form.post('/inventory/products');
    }
};

const productTypes = [
    { value: 'raw_material', label: 'Raw Material' },
    { value: 'wip', label: 'Work in Progress (WIP)' },
    { value: 'finished_good', label: 'Finished Good' },
    { value: 'spare_part', label: 'Spare Part' },
];

const itemTypes = [
    { value: 'product', label: 'Product' },
    { value: 'service', label: 'Service' },
    { value: 'consumable', label: 'Consumable' },
];

const addInitialStock = () => {
    form.initial_stocks.push({
        warehouse_id: '',
        qty: 0,
    });
};

const removeInitialStock = (index) => {
    form.initial_stocks.splice(index, 1);
};

// Auto Calculate Selling Price
const autoCalculate = ref(false);
const profitMargin = ref(0);

import { watch } from 'vue';

watch([() => form.cost_price, profitMargin, autoCalculate], ([newCost, newMargin, isAuto]) => {
    if (isAuto && newCost > 0) {
        const marginValue = (newCost * newMargin) / 100;
        form.selling_price = Math.round(newCost + marginValue);
    }
});
</script>

<template>
    <Head :title="isEditing ? 'Edit Product' : 'Create Product'" />
    
    <AppLayout :title="isEditing ? 'Edit Product' : 'Create Product'">
        <form @submit.prevent="submit" class="space-y-6">
            <!-- Back button -->
            <div class="flex items-center gap-4">
                <Link
                    href="/inventory/products"
                    class="flex items-center gap-2 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white transition-colors"
                >
                    <ArrowLeftIcon class="h-5 w-5" />
                    Back to Products
                </Link>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
                <!-- Main Content -->
                <div class="xl:col-span-8 space-y-6">
                    <!-- Basic Information -->
                    <div class="rounded-2xl glass-card">
                        <div class="border-b border-slate-200 dark:border-slate-800 px-6 py-4">
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Basic Information</h2>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="flex items-center gap-2 text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">
                                        SKU *
                                        <div class="group relative cursor-help">
                                            <QuestionMarkCircleIcon class="h-4 w-4 text-slate-500 hover:text-blue-400 transition-colors" />
                                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-64 p-3 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-xl opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50">
                                                <p class="text-xs text-slate-900 dark:text-white">
                                                    <span class="font-bold block mb-1">Stock Keeping Unit</span>
                                                    Kode unik untuk identifikasi produk. Biasanya kombinasi huruf dan angka.
                                                    <br><br>
                                                    <span class="text-slate-500 dark:text-slate-400">Contoh: EL-Laptop-001</span>
                                                </p>
                                                <div class="absolute bottom-0 left-1/2 -translate-x-1/2 translate-y-1/2 rotate-45 w-2 h-2 bg-slate-50 dark:bg-slate-800 border-r border-b border-slate-200 dark:border-slate-700"></div>
                                            </div>
                                        </div>
                                    </label>
                                    <input
                                        v-model="form.sku"
                                        type="text"
                                        required
                                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50"
                                        placeholder="e.g., PRD-001"
                                    />
                                    <p v-if="form.errors.sku" class="mt-1 text-sm text-red-400">{{ form.errors.sku }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Barcode</label>
                                    <input
                                        v-model="form.barcode"
                                        type="text"
                                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50"
                                        placeholder="Scan or enter barcode"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Name *</label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    required
                                    class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50"
                                    placeholder="Product name"
                                />
                                <p v-if="form.errors.name" class="mt-1 text-sm text-red-400">{{ form.errors.name }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Description</label>
                                <textarea
                                    v-model="form.description"
                                    rows="3"
                                    class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50"
                                    placeholder="Product description..."
                                />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label class="flex items-center gap-2 text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">
                                        Category
                                        <div class="group relative cursor-help">
                                            <QuestionMarkCircleIcon class="h-4 w-4 text-slate-500 hover:text-blue-400 transition-colors" />
                                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-64 p-3 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-xl opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50">
                                                <p class="text-xs text-slate-900 dark:text-white">
                                                    <span class="font-bold block mb-1">Kategori Produk</span>
                                                    Pengelompokan logis produk untuk pelaporan.
                                                    <br><br>
                                                    <span class="text-slate-500 dark:text-slate-400">Contoh: Elektronik, Bahan Kimia, Suku Cadang Mesin A, dll.</span>
                                                </p>
                                                <div class="absolute bottom-0 left-1/2 -translate-x-1/2 translate-y-1/2 rotate-45 w-2 h-2 bg-slate-50 dark:bg-slate-800 border-r border-b border-slate-200 dark:border-slate-700"></div>
                                            </div>
                                        </div>
                                    </label>
                                    <select
                                        v-model="form.category_id"
                                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                    >
                                        <option value="">Select category</option>
                                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                            {{ cat.name }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label class="flex items-center gap-2 text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">
                                        Item Type *
                                        <div class="group relative cursor-help">
                                            <QuestionMarkCircleIcon class="h-4 w-4 text-slate-500 hover:text-blue-400 transition-colors" />
                                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-64 p-3 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-xl opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50">
                                                <p class="text-xs text-slate-900 dark:text-white">
                                                    <span class="font-bold block mb-1">Jenis Item</span>
                                                    • <b>Product</b>: Barang fisik yang disimpan.<br>
                                                    • <b>Service</b>: Jasa (tidak ada stok).<br>
                                                    • <b>Consumable</b>: Barang habis pakai (ATK, dll).
                                                </p>
                                                <div class="absolute bottom-0 left-1/2 -translate-x-1/2 translate-y-1/2 rotate-45 w-2 h-2 bg-slate-50 dark:bg-slate-800 border-r border-b border-slate-200 dark:border-slate-700"></div>
                                            </div>
                                        </div>
                                    </label>
                                    <select
                                        v-model="form.type"
                                        required
                                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                    >
                                        <option v-for="type in itemTypes" :key="type.value" :value="type.value">
                                            {{ type.label }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label class="flex items-center gap-2 text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">
                                        Product Type *
                                        <div class="group relative cursor-help">
                                            <QuestionMarkCircleIcon class="h-4 w-4 text-slate-500 hover:text-blue-400 transition-colors" />
                                            <div class="absolute bottom-full right-0 mb-2 w-64 p-3 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-xl opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50">
                                                <p class="text-xs text-slate-900 dark:text-white">
                                                    <span class="font-bold block mb-1">Tipe Produk</span>
                                                    • <b>Raw Material</b>: Bahan baku produksi.<br>
                                                    • <b>WIP</b>: Barang setengah jadi.<br>
                                                    • <b>Finished Good</b>: Barang jadi siap jual.<br>
                                                    • <b>Spare Part</b>: Suku cadang mesin/alat.
                                                </p>
                                                <div class="absolute bottom-0 right-4 translate-y-1/2 rotate-45 w-2 h-2 bg-slate-50 dark:bg-slate-800 border-r border-b border-slate-200 dark:border-slate-700"></div>
                                            </div>
                                        </div>
                                    </label>
                                    <select
                                        v-model="form.product_type"
                                        required
                                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                    >
                                        <option v-for="type in productTypes" :key="type.value" :value="type.value">
                                            {{ type.label }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Partner Information -->
                    <div class="rounded-2xl glass-card">
                        <div class="border-b border-slate-200 dark:border-slate-800 px-6 py-4">
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Partner Information</h2>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="flex items-center gap-2 text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">
                                        Exclusive Customer
                                        <div class="group relative cursor-help">
                                            <QuestionMarkCircleIcon class="h-4 w-4 text-slate-500 hover:text-blue-400 transition-colors" />
                                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-64 p-3 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-xl opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50">
                                                <p class="text-xs text-slate-900 dark:text-white">
                                                    <span class="font-bold block mb-1">Pelanggan Eksklusif</span>
                                                    Jika diisi, produk ini hanya akan muncul untuk pelanggan yang dipilih.
                                                </p>
                                                <div class="absolute bottom-0 left-1/2 -translate-x-1/2 translate-y-1/2 rotate-45 w-2 h-2 bg-slate-50 dark:bg-slate-800 border-r border-b border-slate-200 dark:border-slate-700"></div>
                                            </div>
                                        </div>
                                    </label>
                                    <SearchableSelect
                                        v-model="form.customer_id"
                                        :options="customerOptions"
                                        placeholder="Select customer (Optional)"
                                        class="block w-full"
                                    />
                                </div>
                                <div>
                                    <label class="flex items-center gap-2 text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">
                                        Preferred Supplier
                                        <div class="group relative cursor-help">
                                            <QuestionMarkCircleIcon class="h-4 w-4 text-slate-500 hover:text-blue-400 transition-colors" />
                                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-64 p-3 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-xl opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50">
                                                <p class="text-xs text-slate-900 dark:text-white">
                                                    <span class="font-bold block mb-1">Pemasok Utama</span>
                                                    Pemasok default untuk produk ini saat membuat Purchase Order.
                                                </p>
                                                <div class="absolute bottom-0 left-1/2 -translate-x-1/2 translate-y-1/2 rotate-45 w-2 h-2 bg-slate-50 dark:bg-slate-800 border-r border-b border-slate-200 dark:border-slate-700"></div>
                                            </div>
                                        </div>
                                    </label>
                                    <SearchableSelect
                                        v-model="form.supplier_id"
                                        :options="supplierOptions"
                                        placeholder="Select supplier (Optional)"
                                        class="block w-full"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pricing & Stock -->
                    <div class="rounded-2xl glass-card">
                        <div class="border-b border-slate-200 dark:border-slate-800 px-6 py-4">
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Pricing & Stock</h2>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Unit of Measure</label>
                                    <select
                                        v-model="form.unit_id"
                                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                    >
                                        <option value="">Select unit</option>
                                        <option v-for="unit in units" :key="unit.id" :value="unit.id">
                                            {{ unit.name }} ({{ unit.symbol }})
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Cost Price (IDR)</label>
                                    <input
                                        v-model.number="form.cost_price"
                                        type="number"
                                        step="1"
                                        min="0"
                                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50"
                                    />
                                </div>
                                <div>
                                    <label class="flex items-center gap-2 text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">
                                        Margin (%)
                                        <input 
                                            v-model="autoCalculate" 
                                            type="checkbox" 
                                            class="rounded border-slate-600 bg-slate-50 dark:bg-slate-800 text-blue-500 focus:ring-blue-500/50"
                                            title="Auto Calculate Selling Price"
                                        >
                                    </label>
                                    <input
                                        v-model.number="profitMargin"
                                        type="number"
                                        step="0.1"
                                        min="0"
                                        :disabled="!autoCalculate"
                                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50 disabled:opacity-50 disabled:cursor-not-allowed"
                                        placeholder="0"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Selling Price (IDR)</label>
                                    <input
                                        v-model.number="form.selling_price"
                                        type="number"
                                        step="1"
                                        min="0"
                                        :readonly="autoCalculate"
                                        :class="{'opacity-75 cursor-not-allowed': autoCalculate}"
                                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50"
                                    />
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                                <div>
                                    <label class="flex items-center gap-2 text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">
                                        Min Stock
                                        <div class="group relative cursor-help">
                                            <QuestionMarkCircleIcon class="h-4 w-4 text-slate-500 hover:text-blue-400 transition-colors" />
                                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-64 p-3 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-xl opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50">
                                                <p class="text-xs text-slate-900 dark:text-white">
                                                    <span class="font-bold block mb-1">Stok Minimum</span>
                                                    Batas absolut stok terendah yang diizinkan di gudang sebelum dianggap kritis.
                                                </p>
                                                <div class="absolute bottom-0 left-1/2 -translate-x-1/2 translate-y-1/2 rotate-45 w-2 h-2 bg-slate-50 dark:bg-slate-800 border-r border-b border-slate-200 dark:border-slate-700"></div>
                                            </div>
                                        </div>
                                    </label>
                                    <input
                                        v-model.number="form.min_stock"
                                        type="number"
                                        step="0.1"
                                        min="0"
                                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50"
                                    />
                                </div>
                                <div>
                                    <label class="flex items-center gap-2 text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">
                                        Max Stock
                                        <div class="group relative cursor-help">
                                            <QuestionMarkCircleIcon class="h-4 w-4 text-slate-500 hover:text-blue-400 transition-colors" />
                                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-64 p-3 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-xl opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50">
                                                <p class="text-xs text-slate-900 dark:text-white">
                                                    <span class="font-bold block mb-1">Stok Maksimum</span>
                                                    Kapasitas maksimal penyimpanan untuk item ini di gudang.
                                                    <br><br>
                                                    <span class="text-slate-500 dark:text-slate-400">Berguna untuk mencegah overstock yang memakan tempat.</span>
                                                </p>
                                                <div class="absolute bottom-0 left-1/2 -translate-x-1/2 translate-y-1/2 rotate-45 w-2 h-2 bg-slate-50 dark:bg-slate-800 border-r border-b border-slate-200 dark:border-slate-700"></div>
                                            </div>
                                        </div>
                                    </label>
                                    <input
                                        v-model.number="form.max_stock"
                                        type="number"
                                        step="0.1"
                                        min="0"
                                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50"
                                    />
                                </div>
                                <div>
                                    <label class="flex items-center gap-2 text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">
                                        Reorder Point
                                        <div class="group relative cursor-help">
                                            <QuestionMarkCircleIcon class="h-4 w-4 text-slate-500 hover:text-blue-400 transition-colors" />
                                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-64 p-3 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-xl opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50">
                                                <p class="text-xs text-slate-900 dark:text-white">
                                                    <span class="font-bold block mb-1">Titik Pemesanan Kembali</span>
                                                    Batas stok minimal untuk mulai memesan barang.
                                                    <br><br>
                                                    <span class="text-slate-500 dark:text-slate-400">Rumus: (Avg Penjualan Harian x Lead Time) + Safety Stock</span>
                                                </p>
                                                <div class="absolute bottom-0 left-1/2 -translate-x-1/2 translate-y-1/2 rotate-45 w-2 h-2 bg-slate-50 dark:bg-slate-800 border-r border-b border-slate-200 dark:border-slate-700"></div>
                                            </div>
                                        </div>
                                    </label>
                                    <input
                                        v-model.number="form.reorder_point"
                                        type="number"
                                        step="0.1"
                                        min="0"
                                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50"
                                    />
                                </div>
                                <div>
                                    <label class="flex items-center gap-2 text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">
                                        Reorder Qty
                                        <div class="group relative cursor-help">
                                            <QuestionMarkCircleIcon class="h-4 w-4 text-slate-500 hover:text-blue-400 transition-colors" />
                                            <div class="absolute bottom-full right-0 mb-2 w-64 p-3 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-xl opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50">
                                                <p class="text-xs text-slate-900 dark:text-white">
                                                    <span class="font-bold block mb-1">Jumlah Pemesanan</span>
                                                    Jumlah barang yang harus dipesan ketika stok menyentuh Reorder Point.
                                                    <br><br>
                                                    <span class="text-slate-500 dark:text-slate-400">Biasanya disesuaikan dengan MOQ supplier atau kebutuhan untuk periode tertentu (misal: stok 1 bulan).</span>
                                                </p>
                                                <div class="absolute bottom-0 right-4 translate-y-1/2 rotate-45 w-2 h-2 bg-slate-50 dark:bg-slate-800 border-r border-b border-slate-200 dark:border-slate-700"></div>
                                            </div>
                                        </div>
                                    </label>
                                    <input
                                        v-model.number="form.reorder_qty"
                                        type="number"
                                        step="0.1"
                                        min="0"
                                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Initial Stock (only for new products) -->
                    <div v-if="!isEditing" class="rounded-2xl glass-card">
                        <div class="border-b border-slate-200 dark:border-slate-800 px-6 py-4 flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Initial Stock</h2>
                            <button
                                type="button"
                                @click="addInitialStock"
                                class="text-sm text-blue-400 hover:text-blue-300"
                            >
                                + Add Warehouse
                            </button>
                        </div>
                        <div class="p-6">
                            <div v-if="form.initial_stocks.length === 0" class="text-center py-8 text-slate-500">
                                No initial stock. Click "Add Warehouse" to add initial quantities.
                            </div>
                            <div v-else class="space-y-4">
                                <div 
                                    v-for="(stock, index) in form.initial_stocks" 
                                    :key="index"
                                    class="flex items-center gap-4"
                                >
                                    <select
                                        v-model="stock.warehouse_id"
                                        class="flex-1 rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                    >
                                        <option value="">Select warehouse</option>
                                        <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">
                                            {{ wh.name }}
                                        </option>
                                    </select>
                                    <input
                                        v-model.number="stock.qty"
                                        type="number"
                                        min="0"
                                        placeholder="Qty"
                                        class="w-32 rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                    />
                                    <button
                                        type="button"
                                        @click="removeInitialStock(index)"
                                        class="p-2 text-red-400 hover:text-red-300"
                                    >
                                        ×
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="xl:col-span-4 space-y-6">
                    <!-- Status -->
                    <div class="rounded-2xl glass-card">
                        <div class="border-b border-slate-200 dark:border-slate-800 px-6 py-4">
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Status</h2>
                        </div>
                        <div class="p-6 space-y-4">
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input 
                                    v-model="form.is_active" 
                                    type="checkbox"
                                    class="rounded border-slate-600 bg-slate-50 dark:bg-slate-800 text-blue-500 focus:ring-blue-500/50"
                                />
                                <div class="flex-1">
                                    <span class="text-sm text-slate-600 dark:text-slate-300">Active</span>
                                    <p class="text-xs text-slate-500 mt-0.5">Uncheck untuk menonaktifkan produk (Hidden).</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Product Flags -->
                    <div class="rounded-2xl glass-card">
                        <div class="border-b border-slate-200 dark:border-slate-800 px-6 py-4">
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Product Flags</h2>
                        </div>
                        <div class="p-6 space-y-4">
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input 
                                    v-model="form.is_purchased" 
                                    type="checkbox"
                                    class="rounded border-slate-600 bg-slate-50 dark:bg-slate-800 text-blue-500 focus:ring-blue-500/50"
                                />
                                <div class="flex-1">
                                    <span class="text-sm text-slate-600 dark:text-slate-300">Can be Purchased</span>
                                    <p class="text-xs text-slate-500 mt-0.5">Centang jika barang ini dibeli dari Supplier (Bahan Baku / Barang Dagangan).</p>
                                </div>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input 
                                    v-model="form.is_sold" 
                                    type="checkbox"
                                    class="rounded border-slate-600 bg-slate-50 dark:bg-slate-800 text-blue-500 focus:ring-blue-500/50"
                                />
                                <div class="flex-1">
                                    <span class="text-sm text-slate-600 dark:text-slate-300">Can be Sold</span>
                                    <p class="text-xs text-slate-500 mt-0.5">Centang jika barang ini dijual ke Customer (Barang Jadi).</p>
                                </div>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input 
                                    v-model="form.is_manufactured" 
                                    type="checkbox"
                                    class="rounded border-slate-600 bg-slate-50 dark:bg-slate-800 text-blue-500 focus:ring-blue-500/50"
                                />
                                <div class="flex-1">
                                    <span class="text-sm text-slate-600 dark:text-slate-300">Can be Manufactured</span>
                                    <p class="text-xs text-slate-500 mt-0.5">Centang jika barang ini diproduksi sendiri (punya BOM).</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Tracking -->
                    <div class="rounded-2xl glass-card">
                        <div class="border-b border-slate-200 dark:border-slate-800 px-6 py-4">
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Tracking</h2>
                        </div>
                        <div class="p-6 space-y-4">
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input 
                                    v-model="form.track_serial" 
                                    type="checkbox"
                                    class="rounded border-slate-600 bg-slate-50 dark:bg-slate-800 text-blue-500 focus:ring-blue-500/50"
                                />
                                <div class="flex-1">
                                    <span class="text-sm text-slate-600 dark:text-slate-300">Track by Serial Number</span>
                                    <p class="text-xs text-slate-500 mt-0.5">Stok dicatat per unit unik (e.g. Elektronik, Mesin).</p>
                                </div>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input 
                                    v-model="form.track_batch" 
                                    type="checkbox"
                                    class="rounded border-slate-600 bg-slate-50 dark:bg-slate-800 text-blue-500 focus:ring-blue-500/50"
                                />
                                <div class="flex-1">
                                    <span class="text-sm text-slate-600 dark:text-slate-300">Track by Batch/Lot</span>
                                    <p class="text-xs text-slate-500 mt-0.5">Stok dicatat per batch produksi & expired date (e.g. Obat, Makanan).</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-col gap-3">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 hover:from-blue-500 hover:to-blue-400 disabled:opacity-50 transition-all"
                        >
                            {{ form.processing ? 'Saving...' : (isEditing ? 'Update Product' : 'Create Product') }}
                        </button>
                        <Link
                            href="/inventory/products"
                            class="w-full text-center rounded-xl bg-slate-50 dark:bg-slate-800 px-4 py-3 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-700 transition-colors"
                        >
                            Cancel
                        </Link>
                    </div>
                </div>
            </div>
        </form>
    </AppLayout>
</template>



