<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';
import {
    ArrowLeftIcon,
    MagnifyingGlassIcon,
    ArrowPathIcon,
    CheckCircleIcon,
    PrinterIcon,
    TrashIcon,
    MinusIcon,
    PlusIcon,
    CheckIcon,
    XMarkIcon,
    FunnelIcon,
} from '@heroicons/vue/24/outline';
import { formatNumber } from '@/helpers';

const props = defineProps({
    opname: Object,
});

const search = ref('');
const filterMode = ref('all'); // all | changed | unchanged
const savingItemId = ref(null);
const savedItemId = ref(null);
const errorItemId = ref(null);

// Local state for items
const items = ref(
    (props.opname.items || []).map(item => ({
        id: item.id,
        product: item.product,
        qty_system: Number(item.qty_system),
        qty_physic: Number(item.qty_physic),
        qty_difference: Number(item.qty_difference),
        original_physic: Number(item.qty_physic),
    }))
);

// Progress
const progress = computed(() => {
    const total = items.value.length;
    const counted = items.value.filter(i => i.qty_physic !== i.qty_system).length;
    return {
        total,
        counted,
        percent: total > 0 ? Math.round((counted / total) * 100 * 10) / 10 : 0,
    };
});

// Filtered items
const filteredItems = computed(() => {
    let list = items.value;

    if (search.value) {
        const q = search.value.toLowerCase();
        list = list.filter(i =>
            i.product.name.toLowerCase().includes(q) ||
            (i.product.sku && i.product.sku.toLowerCase().includes(q))
        );
    }

    if (filterMode.value === 'changed') {
        list = list.filter(i => i.qty_difference !== 0);
    } else if (filterMode.value === 'unchanged') {
        list = list.filter(i => i.qty_difference === 0);
    }

    return list;
});

// Auto-save single item
const autoSave = async (item) => {
    if (props.opname.status === 'completed') return;

    item.qty_difference = item.qty_physic - item.qty_system;

    // Don't save if unchanged from server
    if (item.qty_physic === item.original_physic) return;

    savingItemId.value = item.id;
    savedItemId.value = null;
    errorItemId.value = null;

    try {
        await axios.put(`/inventory/opname/${props.opname.id}/item`, {
            item_id: item.id,
            qty_physic: item.qty_physic,
        });
        item.original_physic = item.qty_physic;
        savedItemId.value = item.id;
        setTimeout(() => { if (savedItemId.value === item.id) savedItemId.value = null; }, 1500);
    } catch (e) {
        errorItemId.value = item.id;
        setTimeout(() => { if (errorItemId.value === item.id) errorItemId.value = null; }, 3000);
    } finally {
        savingItemId.value = null;
    }
};

// Stepper buttons
const increment = (item) => {
    item.qty_physic = Number(item.qty_physic) + 1;
    item.qty_difference = item.qty_physic - item.qty_system;
    autoSave(item);
};

const decrement = (item) => {
    if (item.qty_physic <= 0) return;
    item.qty_physic = Number(item.qty_physic) - 1;
    item.qty_difference = item.qty_physic - item.qty_system;
    autoSave(item);
};

// Manual input with debounced save
let saveTimers = {};
const onInput = (item) => {
    item.qty_physic = Math.max(0, Number(item.qty_physic) || 0);
    item.qty_difference = item.qty_physic - item.qty_system;

    clearTimeout(saveTimers[item.id]);
    saveTimers[item.id] = setTimeout(() => autoSave(item), 800);
};

// Reset to system qty
const resetItem = (item) => {
    item.qty_physic = item.qty_system;
    item.qty_difference = 0;
    autoSave(item);
};

// Batch actions
const populateItems = () => {
    if (confirm('This will load ALL active products in this warehouse. Continue?')) {
        router.post(`/inventory/opname/${props.opname.id}/populate`);
    }
};

const completeOpname = () => {
    if (confirm('Complete this session? Stock adjustments will be posted immediately. This action is IRREVERSIBLE.')) {
        router.post(`/inventory/opname/${props.opname.id}/complete`);
    }
};

const deleteOpname = () => {
    if (confirm('Are you sure you want to delete this session?')) {
        router.delete(`/inventory/opname/${props.opname.id}`);
    }
};

// Helpers
const getStatusBadge = (status) => ({
    draft: 'bg-slate-500/20 text-slate-400 border-slate-500/30',
    in_progress: 'bg-blue-500/20 text-blue-400 border-blue-500/30',
    completed: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
    cancelled: 'bg-red-500/20 text-red-400 border-red-500/30',
}[status] || 'bg-slate-500/20 text-slate-400');

const formatDate = (d) => new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });

const getDiffColor = (diff) => {
    if (diff > 0) return 'text-emerald-400';
    if (diff < 0) return 'text-red-400';
    return 'text-slate-500';
};

const getDiffBg = (diff) => {
    if (diff > 0) return 'bg-emerald-500/10 border-emerald-500/20';
    if (diff < 0) return 'bg-red-500/10 border-red-500/20';
    return 'bg-slate-800/30 border-slate-700/30';
};
</script>

<template>
    <Head :title="`Opname ${opname.opname_number}`" />

    <AppLayout :title="`Stock Opname ${opname.opname_number}`">
        <div class="flex flex-col gap-4 sm:gap-6 pb-24 sm:pb-6">

            <!-- Top Bar -->
            <div class="flex items-center justify-between">
                <Link
                    href="/inventory/opname"
                    class="inline-flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white"
                >
                    <ArrowLeftIcon class="h-4 w-4" />
                    <span class="hidden sm:inline">Back to List</span>
                </Link>
                <div class="flex items-center gap-2">
                    <button
                        v-if="opname.status !== 'completed'"
                        @click="deleteOpname"
                        class="p-2 sm:px-3 sm:py-2 rounded-xl border border-red-500/30 bg-red-500/10 text-red-400 hover:bg-red-500/20 transition-colors"
                    >
                        <TrashIcon class="h-4 w-4" />
                    </button>
                    <button
                        v-if="opname.status !== 'completed' && items.length > 0"
                        @click="completeOpname"
                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-3 py-2 sm:px-4 text-sm font-semibold text-white shadow-lg shadow-emerald-500/25 hover:bg-emerald-500 transition-colors"
                    >
                        <CheckCircleIcon class="h-4 w-4" />
                        <span class="hidden sm:inline">Complete & Post</span>
                        <span class="sm:hidden">Complete</span>
                    </button>
                    <button
                        v-if="opname.status === 'completed'"
                        class="inline-flex items-center gap-2 rounded-xl bg-slate-800 px-3 py-2 text-sm text-slate-300 hover:bg-slate-700"
                        onclick="window.print()"
                    >
                        <PrinterIcon class="h-4 w-4" />
                        Print
                    </button>
                </div>
            </div>

            <!-- Session Info Card -->
            <div class="rounded-2xl glass-card p-4 sm:p-6">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ opname.opname_number }}</h2>
                    <span
                        class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium capitalize"
                        :class="getStatusBadge(opname.status)"
                    >{{ opname.status?.replace('_', ' ') }}</span>
                </div>
                <div class="grid grid-cols-3 gap-4 text-sm">
                    <div>
                        <p class="text-xs text-slate-500">Warehouse</p>
                        <p class="font-medium text-slate-900 dark:text-white truncate">{{ opname.warehouse?.name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Date</p>
                        <p class="font-medium text-slate-900 dark:text-white">{{ formatDate(opname.opname_date) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Created By</p>
                        <p class="font-medium text-slate-900 dark:text-white truncate">{{ opname.created_by_user?.name ?? '—' }}</p>
                    </div>
                </div>
            </div>

            <!-- Progress Bar -->
            <div v-if="items.length > 0" class="rounded-2xl glass-card p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Counting Progress</span>
                    <span class="text-sm font-bold" :class="progress.percent >= 100 ? 'text-emerald-400' : 'text-blue-400'">
                        {{ progress.counted }} / {{ progress.total }} ({{ progress.percent }}%)
                    </span>
                </div>
                <div class="w-full bg-slate-800 rounded-full h-3 overflow-hidden">
                    <div
                        class="h-full rounded-full transition-all duration-500 ease-out"
                        :class="progress.percent >= 100 ? 'bg-emerald-500' : 'bg-blue-500'"
                        :style="{ width: progress.percent + '%' }"
                    ></div>
                </div>
            </div>

            <!-- Search & Filter -->
            <div v-if="items.length > 0" class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500" />
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search product or SKU..."
                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800/50 py-3 pl-10 pr-4 text-sm text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50"
                    />
                </div>
                <div class="flex rounded-xl overflow-hidden border border-slate-200 dark:border-slate-700">
                    <button
                        v-for="f in [{v:'all',l:'All'},{v:'changed',l:'Changed'},{v:'unchanged',l:'Pending'}]"
                        :key="f.v"
                        @click="filterMode = f.v"
                        class="flex-1 px-4 py-2.5 text-xs font-semibold uppercase tracking-wider transition-colors"
                        :class="filterMode === f.v
                            ? 'bg-blue-600 text-white'
                            : 'bg-slate-50 dark:bg-slate-800/50 text-slate-500 hover:text-slate-300'"
                    >{{ f.l }}</button>
                </div>
            </div>

            <!-- Items Count -->
            <p v-if="items.length > 0" class="text-xs text-slate-500 px-1">
                Showing {{ filteredItems.length }} of {{ items.length }} items
            </p>

            <!-- Product Cards (Mobile-First) -->
            <div v-if="items.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <div
                    v-for="item in filteredItems"
                    :key="item.id"
                    class="rounded-xl border transition-all duration-200"
                    :class="[
                        getDiffBg(item.qty_difference),
                        savingItemId === item.id ? 'ring-2 ring-blue-500/50' : '',
                        savedItemId === item.id ? 'ring-2 ring-emerald-500/50' : '',
                        errorItemId === item.id ? 'ring-2 ring-red-500/50' : '',
                    ]"
                >
                    <div class="p-4">
                        <!-- Product Name + SKU -->
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex-1 min-w-0 mr-2">
                                <p class="text-sm font-semibold text-slate-900 dark:text-white leading-tight truncate">{{ item.product.name }}</p>
                                <p class="text-xs text-slate-500 mt-0.5 font-mono">{{ item.product.sku }}</p>
                            </div>
                            <!-- Auto-save indicator -->
                            <div class="flex-shrink-0 w-5 h-5 flex items-center justify-center">
                                <ArrowPathIcon v-if="savingItemId === item.id" class="h-4 w-4 text-blue-400 animate-spin" />
                                <CheckIcon v-else-if="savedItemId === item.id" class="h-4 w-4 text-emerald-400" />
                                <XMarkIcon v-else-if="errorItemId === item.id" class="h-4 w-4 text-red-400" />
                            </div>
                        </div>

                        <!-- System Qty -->
                        <div class="flex items-center justify-between text-sm mb-3">
                            <span class="text-slate-500">System Qty</span>
                            <span class="font-mono font-semibold text-slate-400">{{ formatNumber(item.qty_system) }}</span>
                        </div>

                        <!-- Stepper Input -->
                        <div v-if="opname.status !== 'completed'" class="flex items-center gap-2 mb-3">
                            <button
                                @click="decrement(item)"
                                class="flex-shrink-0 w-12 h-12 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400 hover:bg-red-500/20 transition-colors flex items-center justify-center active:scale-95"
                                :disabled="item.qty_physic <= 0"
                            >
                                <MinusIcon class="h-5 w-5" />
                            </button>
                            <input
                                v-model.number="item.qty_physic"
                                type="number"
                                min="0"
                                step="1"
                                @change="onInput(item)"
                                class="flex-1 h-12 text-center text-lg font-bold rounded-xl border-0 bg-slate-50 dark:bg-slate-900/80 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                            />
                            <button
                                @click="increment(item)"
                                class="flex-shrink-0 w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/20 transition-colors flex items-center justify-center active:scale-95"
                            >
                                <PlusIcon class="h-5 w-5" />
                            </button>
                        </div>
                        <div v-else class="text-center mb-3">
                            <span class="text-lg font-bold text-white">{{ formatNumber(item.qty_physic) }}</span>
                        </div>

                        <!-- Difference & Reset -->
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-xs text-slate-500 mr-1">Diff:</span>
                                <span class="text-sm font-bold font-mono" :class="getDiffColor(item.qty_difference)">
                                    {{ item.qty_difference > 0 ? '+' : '' }}{{ formatNumber(item.qty_difference) }}
                                </span>
                            </div>
                            <button
                                v-if="opname.status !== 'completed' && item.qty_difference !== 0"
                                @click="resetItem(item)"
                                class="text-xs text-slate-500 hover:text-slate-300 underline"
                            >Reset</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="rounded-2xl glass-card p-8 sm:p-12 text-center">
                <ArrowPathIcon class="mx-auto h-12 w-12 text-slate-600 mb-3" />
                <p class="text-slate-400 mb-1">No items in this session.</p>
                <p class="text-xs text-slate-500 mb-4">Load products to start counting.</p>
                <button
                    v-if="opname.status !== 'completed'"
                    @click="populateItems"
                    class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white hover:bg-blue-500 transition-colors"
                >
                    <ArrowPathIcon class="h-4 w-4" />
                    Load All Products
                </button>
            </div>
        </div>

        <!-- Mobile Fixed Bottom Bar -->
        <div
            v-if="items.length > 0 && opname.status !== 'completed'"
            class="fixed bottom-0 left-0 right-0 sm:hidden bg-slate-950/95 backdrop-blur-lg border-t border-slate-800 p-3 z-50 safe-area-bottom"
        >
            <div class="flex items-center justify-between gap-3">
                <div class="flex-1">
                    <div class="text-xs text-slate-500">Progress</div>
                    <div class="text-sm font-bold" :class="progress.percent >= 100 ? 'text-emerald-400' : 'text-blue-400'">
                        {{ progress.counted }}/{{ progress.total }}
                    </div>
                </div>
                <div class="flex-1">
                    <div class="w-full bg-slate-800 rounded-full h-2 overflow-hidden">
                        <div
                            class="h-full rounded-full transition-all"
                            :class="progress.percent >= 100 ? 'bg-emerald-500' : 'bg-blue-500'"
                            :style="{ width: progress.percent + '%' }"
                        ></div>
                    </div>
                </div>
                <button
                    @click="completeOpname"
                    class="flex-shrink-0 inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white"
                >
                    <CheckCircleIcon class="h-4 w-4" />
                    Complete
                </button>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.safe-area-bottom {
    padding-bottom: max(0.75rem, env(safe-area-inset-bottom));
}
</style>
