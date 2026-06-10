<script setup>
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ArrowLeftIcon,
    PrinterIcon,
    PencilSquareIcon,
    TruckIcon,
    CheckCircleIcon,
    DocumentTextIcon,
    CheckIcon,
    XMarkIcon,
    CurrencyDollarIcon,
    ArrowPathIcon,
    MagnifyingGlassIcon,
} from '@heroicons/vue/24/outline';
import { formatNumber, formatCurrency } from '@/helpers';

const props = defineProps({
    salesOrder: Object,
    products: Array,
});

// ─── canEditPrice ───
const canEditPrice = computed(() => {
    if (!props.salesOrder || props.salesOrder.status === 'cancelled') return false;

    const invoices = props.salesOrder.invoices ?? [];
    return !invoices.some((inv) => {
        const paidAmount = Number(inv?.paid_amount ?? 0);
        return inv?.status !== 'draft' || paidAmount > 0;
    });
});

// ─── Qty Adjustment ───
const editingItemId = ref(null);
const adjustmentForm = useForm({
    qty: 0,
    reason: ''
});

const startEditing = (item) => {
    editingItemId.value = item.id;
    adjustmentForm.qty = parseFloat(item.qty) || 0;
    adjustmentForm.reason = '';
};

const cancelEditing = () => {
    editingItemId.value = null;
};

const submitAdjustment = (itemId) => {
    adjustmentForm.put(route('sales.orders.update-item-qty', itemId), {
        preserveScroll: true,
        onSuccess: () => {
            editingItemId.value = null;
        }
    });
};

// ─── Price Editing ───
const editingPriceItemId = ref(null);
const priceForm = useForm({
    unit_price: 0,
    reason: ''
});

const startPriceEditing = (item) => {
    editingPriceItemId.value = item.id;
    priceForm.unit_price = parseFloat(item.unit_price) || 0;
    priceForm.reason = '';
};

const cancelPriceEditing = () => {
    editingPriceItemId.value = null;
};

const submitPriceRevision = (itemId) => {
    priceForm.put(route('sales.orders.update-item-price', itemId), {
        preserveScroll: true,
        onSuccess: () => {
            editingPriceItemId.value = null;
        }
    });
};

// ─── Replace Product ───
// Kondisi: item belum pernah dikirim (qty_delivered=0), belum di-reserve di DO aktif, belum diinvoice
const canReplaceProduct = (item) => {
    if (!props.salesOrder || props.salesOrder.status === 'cancelled') return false;
    return (
        parseFloat(item.qty_delivered ?? 0) === 0 &&
        parseFloat(item.qty_invoiced ?? 0) === 0 &&
        parseFloat(item.reserved_qty ?? 0) === 0
    );
};

const replacingProductItemId = ref(null);
const productSearch = ref('');
const replaceForm = useForm({
    new_product_id: null,
    new_unit_price: null,
    new_unit_id: null,
    reason: '',
});

const filteredProducts = computed(() => {
    if (!productSearch.value) return props.products ?? [];
    const q = productSearch.value.toLowerCase();
    return (props.products ?? []).filter(
        (p) => p.name.toLowerCase().includes(q) || p.sku.toLowerCase().includes(q)
    );
});

const startReplaceProduct = (item) => {
    replacingProductItemId.value = item.id;
    productSearch.value = '';
    replaceForm.reset();
    replaceForm.new_unit_price = parseFloat(item.unit_price) || null;
    replaceForm.new_unit_id = item.unit_id || null;
};

const cancelReplaceProduct = () => {
    replacingProductItemId.value = null;
    productSearch.value = '';
};

const submitReplaceProduct = (itemId) => {
    if (!replaceForm.new_product_id) return;
    replaceForm.put(route('sales.orders.replace-item-product', itemId), {
        preserveScroll: true,
        onSuccess: () => {
            replacingProductItemId.value = null;
            productSearch.value = '';
        },
    });
};

// ─── Status Badge ───
const getStatusClass = (status) => {
    const classes = {
        draft: 'bg-slate-500/10 text-slate-500 dark:text-slate-400 ring-slate-500/20',
        confirmed: 'bg-blue-500/10 text-blue-400 ring-blue-500/20',
        processing: 'bg-amber-500/10 text-amber-400 ring-amber-500/20',
        shipped: 'bg-purple-500/10 text-purple-400 ring-purple-500/20',
        delivered: 'bg-emerald-500/10 text-emerald-400 ring-emerald-500/20',
        cancelled: 'bg-red-500/10 text-red-400 ring-red-500/20',
    };
    return classes[status] || 'bg-slate-500/10 text-slate-500 dark:text-slate-400 ring-slate-500/20';
};
</script>

<template>
    <Head :title="`SO ${salesOrder.so_number}`" />

    <AppLayout title="Sales Order Details">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('sales.orders.index')"
                        class="p-2 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white transition-colors"
                    >
                        <ArrowLeftIcon class="h-5 w-5" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ salesOrder.so_number }}</h1>
                        <div class="flex items-center gap-3 mt-1 text-sm">
                            <span
                                class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset uppercase tracking-wider"
                                :class="getStatusClass(salesOrder.status)"
                            >
                                {{ salesOrder.status }}
                            </span>
                            <span class="text-slate-500">
                                Created on {{ new Date(salesOrder.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Invoice Button -->
                    <Link
                        v-if="['confirmed', 'processing', 'shipped', 'delivered'].includes(salesOrder.status) && salesOrder.payment_status !== 'paid'"
                        :href="route('sales.orders.create-invoice', salesOrder.id)"
                        method="post"
                        as="button"
                        class="flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-800 px-4 py-2.5 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-700 transition-colors"
                    >
                        <DocumentTextIcon class="h-4 w-4 text-indigo-400" />
                        Create Invoice (All Delivered)
                    </Link>

                    <!-- Delivery Button -->
                    <Link
                        v-if="['confirmed', 'processing', 'partial'].includes(salesOrder.status)"
                        :href="route('sales.deliveries.create', { sales_order_id: salesOrder.id })"
                        class="flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-medium text-slate-900 dark:text-white hover:bg-emerald-500 transition-colors shadow-lg shadow-emerald-500/20"
                    >
                        <TruckIcon class="h-4 w-4" />
                        Delivery
                    </Link>

                    <a
                        :href="route('sales.orders.print', salesOrder.id)"
                        target="_blank"
                        class="flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-800 px-4 py-2.5 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-700 transition-colors"
                    >
                        <PrinterIcon class="h-4 w-4" />
                        Print
                    </a>
                    <Link
                        v-if="salesOrder.status === 'draft'"
                        :href="route('sales.orders.confirm', salesOrder.id)"
                        method="post"
                        as="button"
                        class="flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-medium text-slate-900 dark:text-white hover:bg-emerald-500 transition-colors shadow-lg shadow-emerald-500/20"
                    >
                        <CheckCircleIcon class="h-4 w-4" />
                        Confirm Order
                    </Link>
                    <Link
                        v-if="salesOrder.status === 'draft'"
                        :href="route('sales.orders.edit', salesOrder.id)"
                        class="flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-medium text-white dark:text-white hover:bg-blue-500 transition-colors shadow-lg shadow-blue-500/20"
                    >
                        <PencilSquareIcon class="h-4 w-4" />
                        Edit Order
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Items Table -->
                    <div class="rounded-2xl glass-card overflow-hidden">
                        <div class="p-6 border-b border-slate-200 dark:border-slate-800">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Order Items</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm text-slate-500 dark:text-slate-400">
                                <thead class="bg-white dark:bg-slate-950 text-slate-200 font-medium">
                                    <tr>
                                        <th class="px-4 py-3 min-w-[300px] lg:min-w-[380px]">Product</th>
                                        <th class="px-4 py-3 text-right min-w-[110px]">Price</th>
                                        <th class="px-4 py-3 text-center min-w-[120px]">Ordered</th>
                                        <th class="px-4 py-3 text-center min-w-[80px]">Delivered</th>
                                        <th class="px-4 py-3 text-center min-w-[80px]">Reserved</th>
                                        <th class="px-4 py-3 text-center min-w-[80px]">Invoiced</th>
                                        <th class="px-4 py-3 text-center min-w-[80px]">Returned</th>
                                        <th class="px-4 py-3 text-center min-w-[90px] cursor-help" title="Rumus: Ordered - Delivered - Reserved">Remaining ⓘ</th>
                                        <th class="px-4 py-3 text-right min-w-[110px]">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <tr v-for="item in salesOrder.items" :key="item.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-900 dark:bg-slate-800/50">

                                        <!-- ── Product Column ── -->
                                        <td class="px-4 py-3 min-w-[300px] lg:min-w-[380px]">
                                            <!-- Replace Product Mode -->
                                            <div v-if="replacingProductItemId === item.id" class="space-y-2 min-w-[260px]">
                                                <!-- Search input -->
                                                <div class="relative">
                                                    <MagnifyingGlassIcon class="absolute left-2 top-2 h-4 w-4 text-slate-400 pointer-events-none" />
                                                    <input
                                                        v-model="productSearch"
                                                        type="text"
                                                        placeholder="Cari produk (nama/SKU)..."
                                                        class="w-full pl-7 rounded-lg border-0 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-orange-500/50 py-1.5"
                                                        autofocus
                                                    />
                                                </div>
                                                <!-- Dropdown product list -->
                                                <div class="max-h-40 overflow-y-auto rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                                                    <div
                                                        v-for="prod in filteredProducts.slice(0, 30)"
                                                        :key="prod.id"
                                                        @click="replaceForm.new_product_id = prod.id; replaceForm.new_unit_price = prod.selling_price; replaceForm.new_unit_id = prod.unit_id; productSearch = prod.name"
                                                        class="px-3 py-2 cursor-pointer hover:bg-orange-50 dark:hover:bg-orange-500/10 transition-colors"
                                                        :class="replaceForm.new_product_id === prod.id ? 'bg-orange-50 dark:bg-orange-500/10 text-orange-600 dark:text-orange-400 font-semibold' : 'text-slate-700 dark:text-slate-300'"
                                                    >
                                                        <div class="font-medium">{{ prod.name }}</div>
                                                        <div class="text-slate-400 font-mono">{{ prod.sku }}</div>
                                                    </div>
                                                    <div v-if="filteredProducts.length === 0" class="px-3 py-2 text-slate-400 italic">Tidak ada produk ditemukan</div>
                                                </div>
                                                <!-- Alasan -->
                                                <input
                                                    v-model="replaceForm.reason"
                                                    type="text"
                                                    placeholder="Alasan penggantian produk... (wajib)"
                                                    class="w-full rounded-lg border-0 bg-slate-50 dark:bg-slate-800 text-[10px] text-slate-500 dark:text-slate-400 focus:ring-1 focus:ring-orange-500/50 py-1 italic"
                                                    required
                                                />
                                                <div v-if="replaceForm.errors.reason" class="text-[9px] text-red-500 font-bold uppercase">{{ replaceForm.errors.reason }}</div>
                                                <div v-if="replaceForm.errors.new_product_id" class="text-[9px] text-red-500 font-bold uppercase">{{ replaceForm.errors.new_product_id }}</div>
                                                <!-- Action buttons -->
                                                <div class="flex items-center gap-2">
                                                    <button
                                                        @click="submitReplaceProduct(item.id)"
                                                        :disabled="!replaceForm.new_product_id || !replaceForm.reason || replaceForm.processing"
                                                        class="flex-1 flex items-center justify-center gap-1 py-1.5 rounded-lg bg-orange-500 text-white text-xs font-medium hover:bg-orange-600 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                                                    >
                                                        <CheckIcon class="h-3.5 w-3.5" />
                                                        Simpan
                                                    </button>
                                                    <button
                                                        @click="cancelReplaceProduct"
                                                        class="p-1.5 rounded-lg bg-red-500/10 text-red-500 hover:bg-red-500/20 transition-colors"
                                                    >
                                                        <XMarkIcon class="h-4 w-4" />
                                                    </button>
                                                </div>
                                            </div>
                                            <!-- Normal Display Mode -->
                                            <div v-else class="group">
                                                <div class="font-medium text-slate-900 dark:text-white">{{ item.product?.name || 'Unknown Item' }}</div>
                                                <div class="text-xs text-slate-500 font-mono">{{ item.product?.sku || '#' + item.product_id }}</div>
                                                <!-- Tombol Ganti Produk — hanya muncul jika belum ada delivery untuk item ini -->
                                                <button
                                                    v-if="canReplaceProduct(item)"
                                                    @click="startReplaceProduct(item)"
                                                    class="mt-1 flex items-center gap-1 text-[10px] font-semibold text-orange-500 bg-orange-500/10 hover:bg-orange-500/20 rounded-md px-2 py-0.5 transition-all opacity-0 group-hover:opacity-100"
                                                    title="Ganti Produk (item belum ada Delivery)"
                                                >
                                                    <ArrowPathIcon class="h-3 w-3" />
                                                    Ganti Produk
                                                </button>
                                            </div>
                                        </td>

                                        <!-- ── Price Column ── -->
                                        <td class="px-4 py-3 text-right min-w-[110px]">
                                            <div v-if="editingPriceItemId === item.id" class="flex flex-col gap-2">
                                                <div class="flex items-center gap-2 justify-end">
                                                    <input
                                                        v-model="priceForm.unit_price"
                                                        type="number"
                                                        step="any"
                                                        class="w-24 rounded-lg border-0 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 py-1 font-bold text-right"
                                                        @keyup.enter="submitPriceRevision(item.id)"
                                                    />
                                                    <div class="flex items-center gap-1">
                                                        <button
                                                            @click="submitPriceRevision(item.id)"
                                                            class="p-1.5 rounded-lg bg-emerald-500/10 text-emerald-500 hover:bg-emerald-500/20 transition-colors"
                                                            title="Save"
                                                        >
                                                            <CheckIcon class="h-4 w-4" />
                                                        </button>
                                                        <button
                                                            @click="cancelPriceEditing"
                                                            class="p-1.5 rounded-lg bg-red-500/10 text-red-500 hover:bg-red-500/20 transition-colors"
                                                            title="Cancel"
                                                        >
                                                            <XMarkIcon class="h-4 w-4" />
                                                        </button>
                                                    </div>
                                                </div>
                                                <input
                                                    v-model="priceForm.reason"
                                                    type="text"
                                                    placeholder="Alasan revisi harga..."
                                                    class="w-full rounded-lg border-0 bg-slate-50 dark:bg-slate-800 text-[10px] text-slate-500 dark:text-slate-400 focus:ring-1 focus:ring-blue-500/50 py-1 italic"
                                                />
                                                <div v-if="priceForm.errors.unit_price" class="text-[9px] text-red-500 font-bold uppercase">{{ priceForm.errors.unit_price }}</div>
                                            </div>
                                            <div v-else class="flex items-center justify-end gap-2 group">
                                                <span>{{ formatCurrency(item.unit_price) }}</span>
                                                <button
                                                    v-if="canEditPrice"
                                                    @click="startPriceEditing(item)"
                                                    class="p-1 rounded-md text-blue-500 bg-blue-500/5 hover:bg-blue-500/10 transition-all opacity-0 group-hover:opacity-100"
                                                    title="Revisi Harga"
                                                >
                                                    <PencilSquareIcon class="h-4 w-4" />
                                                </button>
                                            </div>
                                        </td>

                                        <!-- ── Ordered (Qty) Column ── -->
                                        <td class="px-4 py-3 text-center font-bold text-slate-900 dark:text-white min-w-[120px]">
                                            <div v-if="editingItemId === item.id" class="flex flex-col gap-2">
                                                <div class="flex items-center gap-2 justify-center">
                                                    <input
                                                        v-model="adjustmentForm.qty"
                                                        type="number"
                                                        step="any"
                                                        class="w-16 rounded-lg border-0 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 py-1 font-bold text-center"
                                                        @keyup.enter="submitAdjustment(item.id)"
                                                    />
                                                    <div class="flex items-center gap-1">
                                                        <button
                                                            @click="submitAdjustment(item.id)"
                                                            class="p-1.5 rounded-lg bg-emerald-500/10 text-emerald-500 hover:bg-emerald-500/20 transition-colors"
                                                            title="Save"
                                                        >
                                                            <CheckIcon class="h-4 w-4" />
                                                        </button>
                                                        <button
                                                            @click="cancelEditing"
                                                            class="p-1.5 rounded-lg bg-red-500/10 text-red-500 hover:bg-red-500/20 transition-colors"
                                                            title="Cancel"
                                                        >
                                                            <XMarkIcon class="h-4 w-4" />
                                                        </button>
                                                    </div>
                                                </div>
                                                <input
                                                    v-model="adjustmentForm.reason"
                                                    type="text"
                                                    placeholder="Alasan koreksi..."
                                                    class="w-full rounded-lg border-0 bg-slate-50 dark:bg-slate-800 text-[10px] text-slate-500 dark:text-slate-400 focus:ring-1 focus:ring-blue-500/50 py-1 italic"
                                                    required
                                                />
                                                <div v-if="adjustmentForm.errors.qty" class="text-[9px] text-red-500 font-bold uppercase">{{ adjustmentForm.errors.qty }}</div>
                                            </div>
                                            <div v-else class="flex items-center justify-center gap-2 group">
                                                <span>{{ formatNumber(item.qty) }} {{ item.unit?.name || 'Unit' }}</span>
                                                <button
                                                    @click="startEditing(item)"
                                                    class="p-1 rounded-md text-blue-500 bg-blue-500/5 hover:bg-blue-500/10 transition-all"
                                                    title="Koreksi Qty"
                                                >
                                                    <PencilSquareIcon class="h-4 w-4" />
                                                </button>
                                            </div>
                                        </td>

                                        <td class="px-4 py-3 text-center text-emerald-400 font-medium min-w-[80px]">
                                            {{ formatNumber(item.qty_delivered) }}
                                        </td>
                                        <td class="px-4 py-3 text-center text-amber-500 font-medium min-w-[80px]">
                                            {{ formatNumber(item.reserved_qty || 0) }}
                                        </td>
                                        <td class="px-4 py-3 text-center text-indigo-400 font-medium min-w-[80px]">
                                            {{ formatNumber(item.qty_invoiced || 0) }}
                                        </td>
                                        <td class="px-4 py-3 text-center text-red-400 font-medium min-w-[80px]">
                                            {{ formatNumber(item.qty_returned || 0) }}
                                        </td>
                                        <td class="px-4 py-3 text-center min-w-[90px]">
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                                :class="item.remaining_qty > 0 ? 'bg-amber-500/10 text-amber-500' : 'bg-slate-50 dark:bg-slate-800 text-slate-500'"
                                            >
                                                {{ formatNumber(item.remaining_qty) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right font-medium text-slate-900 dark:text-white min-w-[110px]">
                                            {{ formatCurrency(item.subtotal) }}
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot class="bg-white dark:bg-slate-950 font-medium">
                                    <tr>
                                        <td colspan="8" class="px-4 py-3 text-right text-slate-500 dark:text-slate-400">
                                            Subtotal
                                        </td>
                                        <td class="px-4 py-3 text-right text-slate-900 dark:text-white">
                                            {{ formatCurrency(salesOrder.subtotal) }}
                                        </td>
                                    </tr>
                                    <tr v-if="salesOrder.tax_amount > 0">
                                        <td colspan="8" class="px-4 py-3 text-right text-slate-500 dark:text-slate-400">
                                            VAT ({{ salesOrder.tax_percent }}%)
                                        </td>
                                        <td class="px-4 py-3 text-right text-slate-900 dark:text-white">
                                            {{ formatCurrency(salesOrder.tax_amount) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="8" class="px-4 py-3 text-right text-slate-900 dark:text-white text-lg font-bold">
                                            Grand Total
                                        </td>
                                        <td class="px-4 py-3 text-right text-slate-900 dark:text-white text-lg font-bold">
                                            {{ formatCurrency(salesOrder.total) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Info -->
                <div class="space-y-6">
                    <!-- Addresses Info -->
                    <div class="rounded-2xl glass-card p-6 space-y-6">
                        <!-- Sold-to Party -->
                        <div>
                            <h3 class="text-xs font-black text-slate-500 uppercase tracking-[0.2em] mb-4">Sold-to Party (Buyer)</h3>
                            <div class="space-y-2">
                                <div class="text-slate-900 dark:text-white font-bold text-base">{{ salesOrder.customer.name }}</div>
                                <div class="flex items-start gap-2 text-sm text-slate-500 dark:text-slate-400">
                                    <p class="leading-relaxed">{{ salesOrder.customer.full_address }}</p>
                                </div>
                                <div class="flex items-center gap-2 text-sm text-slate-500 pt-1">
                                    <span class="font-medium">{{ salesOrder.customer.phone }}</span>
                                    <span class="h-1 w-1 rounded-full bg-slate-700"></span>
                                    <span>{{ salesOrder.customer.email }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Ship-to Party -->
                        <div class="border-t border-slate-200 dark:border-slate-800 pt-6">
                            <h3 class="text-xs font-black text-slate-500 uppercase tracking-[0.2em] mb-4">Ship-to Party (Delivery)</h3>
                            <div class="space-y-2">
                                <div class="text-emerald-400 font-bold text-base">{{ salesOrder.shipping_name || salesOrder.customer.name }}</div>
                                <div class="flex items-start gap-2 text-sm text-slate-600 dark:text-slate-300">
                                    <p class="leading-relaxed">{{ salesOrder.shipping_address || salesOrder.customer.full_address }}</p>
                                </div>
                                <div v-if="!salesOrder.shipping_name && !salesOrder.shipping_address" class="text-[10px] font-bold text-slate-600 uppercase italic pt-1">
                                    Same as Sold-to Party
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Info -->
                    <div class="rounded-2xl glass-card p-6">
                        <h3 class="text-sm font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-4">Order Details</h3>
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-xs text-slate-500">Warehouse</dt>
                                <dd class="text-sm text-slate-900 dark:text-white font-medium">{{ salesOrder.warehouse.name }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500">Order Date</dt>
                                <dd class="text-sm text-slate-900 dark:text-white">{{ new Date(salesOrder.order_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500">Expected Delivery</dt>
                                <dd class="text-sm text-slate-900 dark:text-white">{{ new Date(salesOrder.delivery_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}</dd>
                            </div>
                        </dl>
                    </div>


                    <!-- Related Deliveries -->
                    <div v-if="salesOrder.delivery_orders && salesOrder.delivery_orders.length > 0" class="rounded-2xl glass-card p-6">
                        <h3 class="text-xs font-black text-slate-500 uppercase tracking-[0.2em] mb-4">Related Deliveries</h3>
                        <div class="space-y-3">
                            <div v-for="delivery in salesOrder.delivery_orders" :key="delivery.id" class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700">
                                <div class="flex flex-col">
                                    <Link :href="route('sales.deliveries.show', delivery.id)" class="text-sm font-bold text-emerald-600 dark:text-emerald-400 hover:underline">
                                        {{ delivery.do_number }}
                                    </Link>
                                    <span class="text-[10px] text-slate-500">{{ new Date(delivery.delivery_date).toLocaleDateString('id-ID') }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium uppercase tracking-wider"
                                          :class="{
                                              'bg-slate-100 text-slate-800': delivery.status === 'draft',
                                              'bg-amber-100 text-amber-800': delivery.status === 'picking',
                                              'bg-blue-100 text-blue-800': delivery.status === 'packed' || delivery.status === 'shipped',
                                              'bg-teal-100 text-teal-800': delivery.status === 'delivered',
                                              'bg-emerald-100 text-emerald-800': delivery.status === 'completed',
                                              'bg-red-100 text-red-800': delivery.status === 'cancelled'
                                          }">
                                        {{ delivery.status }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Related Invoices -->
                    <div v-if="salesOrder.invoices && salesOrder.invoices.length > 0" class="rounded-2xl glass-card p-6">
                        <h3 class="text-xs font-black text-slate-500 uppercase tracking-[0.2em] mb-4">Related Invoices</h3>
                        <div class="space-y-3">
                            <div v-for="invoice in salesOrder.invoices" :key="invoice.id" class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700">
                                <div class="flex flex-col">
                                    <Link :href="route('sales.invoices.show', invoice.id)" class="text-sm font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                                        {{ invoice.invoice_number }}
                                    </Link>
                                    <span class="text-[10px] text-slate-500">{{ new Date(invoice.invoice_date).toLocaleDateString('id-ID') }}</span>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-medium text-slate-900 dark:text-white">{{ formatCurrency(invoice.total) }}</div>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium"
                                          :class="{
                                              'bg-slate-100 text-slate-800': invoice.status === 'draft',
                                              'bg-blue-100 text-blue-800': invoice.status === 'issued' || invoice.status === 'sent',
                                              'bg-emerald-100 text-emerald-800': invoice.status === 'paid',
                                              'bg-amber-100 text-amber-800': invoice.status === 'partial'
                                          }">
                                        {{ invoice.status }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
