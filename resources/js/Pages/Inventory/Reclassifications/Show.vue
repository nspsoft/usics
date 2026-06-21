<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ArrowLeftIcon,
    CheckCircleIcon,
    PencilSquareIcon,
    TrashIcon,
} from '@heroicons/vue/24/outline';
import { formatCurrency, formatDate, formatNumber } from '@/helpers';

const props = defineProps({
    reclassification: Object,
});

const totalCost = computed(() => {
    if (props.reclassification.total_value !== undefined && parseFloat(props.reclassification.total_value) > 0) {
        return parseFloat(props.reclassification.total_value);
    }
    return props.reclassification.items?.reduce((sum, item) => {
        const cost = parseFloat(item.cost_per_unit || 0);
        return sum + (parseFloat(item.qty) * cost);
    }, 0) || 0;
});

const totalSell = computed(() => {
    if (props.reclassification.total_sell_value !== undefined && parseFloat(props.reclassification.total_sell_value) > 0) {
        return parseFloat(props.reclassification.total_sell_value);
    }
    return props.reclassification.items?.reduce((sum, item) => {
        const sell = parseFloat(item.selling_price_per_unit || item.target_product?.selling_price || item.targetProduct?.selling_price || 0);
        return sum + (parseFloat(item.qty) * sell);
    }, 0) || 0;
});

const totalProfit = computed(() => totalSell.value - totalCost.value);

const totalProfitPct = computed(() => {
    if (totalCost.value <= 0) return 0;
    return (totalProfit.value / totalCost.value) * 100;
});

const getStatusBadge = (status) => {
    const badges = {
        draft: 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30',
        posted: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
        cancelled: 'bg-red-500/20 text-red-400 border-red-500/30',
    };
    return badges[status] || badges.draft;
};

const getProfitPercentage = (item) => {
    if (item.profit_percentage !== undefined && parseFloat(item.profit_percentage) !== 0) {
        return parseFloat(item.profit_percentage);
    }
    const cost = parseFloat(item.cost_per_unit || 0);
    if (cost <= 0) return 0;
    const sell = parseFloat(item.selling_price_per_unit || item.target_product?.selling_price || item.targetProduct?.selling_price || 0);
    return ((sell - cost) / cost) * 100;
};

const getProfitBadgeClass = (pct) => {
    if (pct < 10) {
        return 'inline-flex items-center gap-1 rounded bg-amber-500/10 text-amber-500 border border-amber-500/20 text-[10px] font-bold px-1.5 py-0.5 mt-1';
    }
    if (pct > 50) {
        return 'inline-flex items-center gap-1 rounded bg-indigo-500/10 text-indigo-500 border border-indigo-500/20 text-[10px] font-bold px-1.5 py-0.5 mt-1';
    }
    return '';
};

const getProfitBadgeLabel = (pct) => {
    if (pct < 10) return 'Margin Rendah (<10%)';
    if (pct > 50) return 'Margin Tinggi (>50%)';
    return '';
};

const postDocument = () => {
    if (!confirm('Post reclass stock ini? Sistem akan membuat Stock Movement OUT dan IN untuk semua line.')) return;
    router.post(route('inventory.reclassifications.post', props.reclassification.id));
};

const deleteDraft = () => {
    if (!confirm('Hapus draft reclass stock ini?')) return;
    router.delete(route('inventory.reclassifications.destroy', props.reclassification.id));
};

const deleteItem = (itemId) => {
    if (!confirm('Hapus item ini dari reclass?')) return;
    router.delete(route('inventory.reclassifications.items.destroy', itemId));
};
</script>

<template>
    <Head :title="`Reclass ${reclassification.reclass_number}`" />

    <AppLayout :title="`Reclass ${reclassification.reclass_number}`">
        <div class="max-w-6xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <Link :href="route('inventory.reclassifications.index')" class="inline-flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white">
                    <ArrowLeftIcon class="h-4 w-4" />
                    Back to Reclass Stock
                </Link>
                <div class="flex items-center gap-3">
                    <Link
                        v-if="reclassification.status === 'draft'"
                        :href="route('inventory.reclassifications.edit', reclassification.id)"
                        class="inline-flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-800 px-4 py-2 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700"
                    >
                        <PencilSquareIcon class="h-4 w-4" />
                        Edit
                    </Link>
                    <button
                        v-if="reclassification.status === 'draft'"
                        @click="deleteDraft"
                        class="inline-flex items-center gap-2 rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-2 text-sm font-semibold text-red-500 hover:bg-red-500/20"
                    >
                        <TrashIcon class="h-4 w-4" />
                        Delete
                    </button>
                    <button
                        v-if="reclassification.status === 'draft'"
                        @click="postDocument"
                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500"
                    >
                        <CheckCircleIcon class="h-4 w-4" />
                        Post Reclass
                    </button>
                </div>
            </div>

            <div class="rounded-2xl glass-card overflow-hidden mb-6">
                <div class="p-6 border-b border-slate-200 dark:border-slate-800">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Document Details</h2>
                        <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold capitalize" :class="getStatusBadge(reclassification.status)">
                            {{ reclassification.status }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 lg:grid-cols-5 gap-6">
                        <div>
                            <p class="text-xs text-slate-500 mb-1">Reclass Number</p>
                            <p class="text-sm font-medium text-slate-900 dark:text-white">{{ reclassification.reclass_number }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 mb-1">Date</p>
                            <p class="text-sm font-medium text-slate-900 dark:text-white">{{ formatDate(reclassification.reclass_date) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 mb-1">Warehouse</p>
                            <p class="text-sm font-medium text-slate-900 dark:text-white">{{ reclassification.warehouse?.name }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 mb-1">Created By</p>
                            <p class="text-sm font-medium text-slate-900 dark:text-white">{{ reclassification.created_by?.name || reclassification.createdBy?.name || '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 mb-1">Reason</p>
                            <p class="text-sm font-medium text-slate-900 dark:text-white">{{ reclassification.reason }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 mb-1">Posted By</p>
                            <p class="text-sm font-medium text-slate-900 dark:text-white">{{ reclassification.posted_by?.name || reclassification.postedBy?.name || '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 mb-1">Jumlah Item</p>
                            <p class="text-sm font-medium text-slate-900 dark:text-white">{{ reclassification.items?.length || 0 }} item</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 mb-1">Total Qty</p>
                            <p class="text-sm font-medium text-slate-900 dark:text-white">{{ formatNumber(reclassification.total_qty || 0) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 mb-1">Total Cost Value</p>
                            <p class="text-sm font-medium text-slate-900 dark:text-white">{{ formatCurrency(totalCost) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 mb-1">Total Sell Value</p>
                            <p class="text-sm font-medium text-slate-900 dark:text-white">{{ formatCurrency(totalSell) }}</p>
                        </div>
                        <div class="col-span-2 lg:col-span-1">
                            <p class="text-xs text-slate-500 mb-1">Estimated Profit</p>
                            <p class="text-sm font-bold" :class="totalProfit >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500'">
                                {{ formatCurrency(totalProfit) }} ({{ formatNumber(totalProfitPct) }}%)
                            </p>
                        </div>
                    </div>

                    <div v-if="reclassification.notes" class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-800">
                        <p class="text-xs text-slate-500 mb-1">Notes</p>
                        <p class="text-sm text-slate-600 dark:text-slate-300">{{ reclassification.notes }}</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-900/50">
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Source Product</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Target Product</th>
                                <th class="px-4 py-4 text-right text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Qty</th>
                                <th class="px-4 py-4 text-left text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Unit</th>
                                <th class="px-4 py-4 text-right text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Cost/Unit</th>
                                <th class="px-4 py-4 text-right text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Sell/Unit</th>
                                <th class="px-4 py-4 text-right text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Profit</th>
                                <th class="px-4 py-4 text-right text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Total Cost</th>
                                <th v-if="reclassification.status === 'draft'" class="px-4 py-4 text-center text-xs font-semibold uppercase text-slate-500 dark:text-slate-400 w-16">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="item in reclassification.items" :key="item.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-slate-900 dark:text-white">{{ item.source_product?.name || item.sourceProduct?.name }}</div>
                                    <div class="text-xs text-slate-500">{{ item.source_product?.sku || item.sourceProduct?.sku }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-slate-900 dark:text-white">{{ item.target_product?.name || item.targetProduct?.name }}</div>
                                    <div class="text-xs text-slate-500">{{ item.target_product?.sku || item.targetProduct?.sku }}</div>
                                </td>
                                <td class="px-4 py-4 text-right text-sm font-medium text-slate-900 dark:text-white">{{ formatNumber(item.qty) }}</td>
                                <td class="px-4 py-4 text-sm text-slate-600 dark:text-slate-300">{{ item.unit?.symbol || item.unit?.name || item.target_product?.unit?.symbol || item.targetProduct?.unit?.symbol || item.source_product?.unit?.symbol || item.sourceProduct?.unit?.symbol || '-' }}</td>
                                <td class="px-4 py-4 text-right text-sm text-slate-600 dark:text-slate-300">{{ formatCurrency(item.cost_per_unit || 0) }}</td>
                                <td class="px-4 py-4 text-right text-sm text-slate-600 dark:text-slate-300">
                                    {{ formatCurrency(item.selling_price_per_unit || item.target_product?.selling_price || item.targetProduct?.selling_price || 0) }}
                                </td>
                                <td class="px-4 py-4 text-right text-sm font-semibold" :class="(parseFloat(item.selling_price_per_unit || item.target_product?.selling_price || item.targetProduct?.selling_price || 0) - parseFloat(item.cost_per_unit || 0)) >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500'">
                                    <div class="text-sm font-bold">
                                        {{ formatCurrency(item.profit_nominal !== undefined && parseFloat(item.profit_nominal) !== 0 ? item.profit_nominal : (parseFloat(item.selling_price_per_unit || item.target_product?.selling_price || item.targetProduct?.selling_price || 0) - parseFloat(item.cost_per_unit || 0)) * item.qty) }}
                                    </div>
                                    <div class="text-xs font-normal text-slate-500 dark:text-slate-400">
                                        {{ formatNumber(getProfitPercentage(item)) }}%
                                    </div>
                                    <div v-if="getProfitBadgeLabel(getProfitPercentage(item))" :class="getProfitBadgeClass(getProfitPercentage(item))">
                                        {{ getProfitBadgeLabel(getProfitPercentage(item)) }}
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-right text-sm font-medium text-slate-900 dark:text-white">{{ formatCurrency(item.total_cost || 0) }}</td>
                                <td v-if="reclassification.status === 'draft'" class="px-4 py-4 text-center">
                                    <button
                                        @click="deleteItem(item.id)"
                                        class="inline-flex rounded-lg p-1.5 text-slate-400 hover:text-red-500 hover:bg-red-500/10 transition-colors"
                                    >
                                        <TrashIcon class="h-4 w-4" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
