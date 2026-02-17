<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ArrowLeftIcon,
    PencilSquareIcon,
    CubeIcon,
} from '@heroicons/vue/24/outline';
import { formatNumber, formatCurrency } from '@/helpers';
import ProductPartnerManager from './Partials/ProductPartnerManager.vue';
import { ref } from 'vue';

const activeTab = ref('details');

const props = defineProps({
    product: Object,
    customers: Array,
    suppliers: Array,
});


const getProductTypeLabel = (type) => {
    const labels = {
        raw_material: 'Raw Material',
        wip: 'Work in Progress (WIP)',
        finished_good: 'Finished Good',
        spare_part: 'Spare Part',
    };
    return labels[type] || type;
};

const getItemTypeLabel = (type) => {
    const labels = {
        product: 'Product',
        service: 'Service',
        consumable: 'Consumable',
    };
    return labels[type] || type;
};
</script>

<template>
    <Head title="Product Details" />
    
    <AppLayout title="Product Details">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <Link
                        href="/inventory/products"
                        class="p-2 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:bg-slate-700 transition-colors"
                    >
                        <ArrowLeftIcon class="h-5 w-5" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ product.name }}</h1>
                        <p class="text-slate-500 dark:text-slate-400">{{ product.sku }}</p>
                    </div>
                </div>
                <Link
                    :href="`/inventory/products/${product.id}/edit`"
                    class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white dark:text-white hover:bg-blue-500 transition-colors"
                >
                    <PencilSquareIcon class="h-5 w-5" />
                    Edit Product
                </Link>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Tabs -->
                    <div class="flex items-center space-x-1 border-b border-slate-200 dark:border-slate-800 overflow-x-auto">
                        <button
                            @click="activeTab = 'details'"
                            :class="[
                                activeTab === 'details'
                                    ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                                    : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300',
                                'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors'
                            ]"
                        >
                            Details
                        </button>
                        <button
                            @click="activeTab = 'stock'"
                            :class="[
                                activeTab === 'stock'
                                    ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                                    : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300',
                                'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors'
                            ]"
                        >
                            Stock Levels
                        </button>
                         <button
                            @click="activeTab = 'aliases'"
                            :class="[
                                activeTab === 'aliases'
                                    ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                                    : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300',
                                'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors'
                            ]"
                        >
                            Partner Aliases
                        </button>
                    </div>

                    <!-- Details Tab -->
                    <div v-show="activeTab === 'details'" class="space-y-6">
                        <!-- Basic Information -->
                        <div class="rounded-2xl glass-card overflow-hidden">
                            <div class="border-b border-slate-200 dark:border-slate-800 px-6 py-4">
                                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Basic Information</h2>
                            </div>
                            <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-slate-500 mb-1">SKU</label>
                                    <p class="text-slate-900 dark:text-white font-mono">{{ product.sku }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-500 mb-1">Barcode</label>
                                    <p class="text-slate-900 dark:text-white font-mono">{{ product.barcode || '-' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-500 mb-1">Category</label>
                                    <p class="text-slate-900 dark:text-white">{{ product.category?.name || '-' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-500 mb-1">Unit</label>
                                    <p class="text-slate-900 dark:text-white">{{ product.unit?.name }} ({{ product.unit?.symbol }})</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-500 mb-1">Item Type</label>
                                    <p class="text-slate-900 dark:text-white">{{ getItemTypeLabel(product.type) }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-500 mb-1">Product Type</label>
                                    <span class="inline-flex items-center rounded-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-2.5 py-0.5 text-sm font-medium text-slate-600 dark:text-slate-300">
                                        {{ getProductTypeLabel(product.product_type) }}
                                    </span>
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-slate-500 mb-1">Description</label>
                                    <p class="text-slate-600 dark:text-slate-300 text-sm leading-relaxed">{{ product.description || '-' }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Pricing & Inventory Settings -->
                        <div class="rounded-2xl glass-card overflow-hidden">
                            <div class="border-b border-slate-200 dark:border-slate-800 px-6 py-4">
                                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Pricing & Inventory</h2>
                            </div>
                            <div class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-slate-500 mb-1">Cost Price</label>
                                    <p class="text-slate-900 dark:text-white font-mono">{{ formatCurrency(product.cost_price) }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-500 mb-1">Selling Price</label>
                                    <p class="text-slate-900 dark:text-white font-mono">{{ formatCurrency(product.selling_price) }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-500 mb-1">Margin</label>
                                    <p class="text-emerald-400 font-mono" v-if="product.selling_price > 0">
                                        {{ ((product.selling_price - product.cost_price) / product.selling_price * 100).toFixed(1) }}%
                                    </p>
                                    <p class="text-slate-500" v-else>-</p>
                                </div>
                                <div class="border-t border-slate-200 dark:border-slate-800 sm:col-span-2 lg:col-span-3 my-2"></div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-500 mb-1">Min. Stock</label>
                                    <p class="text-slate-900 dark:text-white">{{ formatNumber(product.min_stock) }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-500 mb-1">Reorder Point</label>
                                    <p class="text-slate-900 dark:text-white">{{ formatNumber(product.reorder_point) }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-500 mb-1">Reorder Qty</label>
                                    <p class="text-slate-900 dark:text-white">{{ formatNumber(product.reorder_qty) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stock Tab -->
                    <div v-show="activeTab === 'stock'" class="rounded-2xl glass-card overflow-hidden">
                        <div class="border-b border-slate-200 dark:border-slate-800 px-6 py-4 flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Stock Levels</h2>
                            <div class="text-sm">
                                <span class="text-slate-500 dark:text-slate-400">Total: </span>
                                <span class="text-slate-900 dark:text-white font-bold ml-1">{{ formatNumber(product.stocks?.reduce((acc, stock) => acc + Number(stock.qty_on_hand), 0) || 0) }}</span>
                                <span class="text-slate-500 ml-1">{{ product.unit?.symbol }}</span>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                                <thead>
                                    <tr class="bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50">
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Warehouse</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Location</th>
                                        <th class="px-6 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">On Hand</th>
                                        <th class="px-6 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Allocated</th>
                                        <th class="px-6 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Available</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <tr v-for="stock in product.stocks" :key="stock.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 dark:text-white">
                                            {{ stock.warehouse?.name || 'Unknown' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-300">
                                            {{ stock.location?.name || '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 dark:text-white text-right font-medium">
                                            {{ formatNumber(stock.qty_on_hand) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-300 text-right">
                                            {{ formatNumber(stock.qty_allocated) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-emerald-400 text-right font-bold">
                                            {{ formatNumber(stock.qty_on_hand - stock.qty_allocated) }}
                                        </td>
                                    </tr>
                                    <tr v-if="!product.stocks?.length">
                                        <td colspan="5" class="px-6 py-4 text-center text-sm text-slate-500">
                                            No stock records found.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Partner Aliases Tab -->
                    <div v-show="activeTab === 'aliases'">
                        <ProductPartnerManager 
                            :product="product"
                            :customers="customers"
                            :suppliers="suppliers"
                        />
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Status Card -->
                    <div class="rounded-2xl glass-card overflow-hidden">
                        <div class="border-b border-slate-200 dark:border-slate-800 px-6 py-4">
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Status</h2>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 dark:text-slate-400">Status</span>
                                <span 
                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="product.is_active ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400'"
                                >
                                    {{ product.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            <div class="pt-4 border-t border-slate-200 dark:border-slate-800 space-y-3">
                                <div class="flex items-center gap-3">
                                    <div class="h-2 w-2 rounded-full" :class="product.is_purchased ? 'bg-blue-500' : 'bg-slate-700'"></div>
                                    <span :class="product.is_purchased ? 'text-slate-900 dark:text-white' : 'text-slate-500'">Can be Purchased</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="h-2 w-2 rounded-full" :class="product.is_sold ? 'bg-blue-500' : 'bg-slate-700'"></div>
                                    <span :class="product.is_sold ? 'text-slate-900 dark:text-white' : 'text-slate-500'">Can be Sold</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="h-2 w-2 rounded-full" :class="product.is_manufactured ? 'bg-blue-500' : 'bg-slate-700'"></div>
                                    <span :class="product.is_manufactured ? 'text-slate-900 dark:text-white' : 'text-slate-500'">Can be Manufactured</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tracking Info -->
                    <div class="rounded-2xl glass-card overflow-hidden">
                        <div class="border-b border-slate-200 dark:border-slate-800 px-6 py-4">
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Tracking</h2>
                        </div>
                        <div class="p-6 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 dark:text-slate-400">Serial Tracking</span>
                                <span :class="product.track_serial ? 'text-blue-400' : 'text-slate-600'">
                                    {{ product.track_serial ? 'Enabled' : 'Disabled' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 dark:text-slate-400">Batch Tracking</span>
                                <span :class="product.track_batch ? 'text-blue-400' : 'text-slate-600'">
                                    {{ product.track_batch ? 'Enabled' : 'Disabled' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>



