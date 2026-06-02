<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { 
    CubeIcon, 
    ArrowRightIcon, 
    ClockIcon,
    MagnifyingGlassIcon,
    ListBulletIcon
} from '@heroicons/vue/24/outline';
import { ref, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatNumber } from '@/helpers';
import debounce from 'lodash/debounce';

const props = defineProps({
    workOrders: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');

const applyFilters = debounce(() => {
    router.get(route('manufacturing.production-entry.index'), {
        search: search.value || undefined,
    }, { preserveState: true, replace: true });
}, 300);

watch(search, applyFilters);

const formatShortDate = (value) => {
    if (!value) return '-';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return '-';
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

</script>

<template>
    <Head title="Input Produksi" />
    
    <AppLayout title="Input Produksi">
        <div class="w-full">
            <!-- PWA Style Header for Mobile -->
            <div class="mb-6 lg:mb-8">
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="relative flex-1">
                        <input 
                            v-model="search"
                            type="text" 
                            placeholder="Search Work Order / Product..." 
                            class="w-full rounded-2xl border-0 bg-white dark:bg-slate-950 py-4 pl-12 pr-4 text-slate-900 dark:text-white placeholder:text-slate-500 shadow-lg focus:ring-2 focus:ring-cyan-500/50"
                        />
                        <MagnifyingGlassIcon class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500" />
                    </div>
                    <Link
                        :href="route('manufacturing.production-reports.index')"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-50 dark:bg-slate-800/50 px-5 py-4 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                    >
                        <ListBulletIcon class="h-5 w-5" />
                        Laporan
                    </Link>
                </div>
            </div>

            <div v-if="workOrders.data.length === 0" class="text-center py-12">
                <div class="h-24 w-24 mx-auto rounded-full glass-card flex items-center justify-center mb-4">
                    <CubeIcon class="h-10 w-10 text-slate-500" />
                </div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">No Active Jobs</h3>
                <p class="text-slate-500">All work orders are completed or none are started.</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:hidden">
                <Link 
                    v-for="wo in workOrders.data" 
                    :key="wo.id"
                    :href="route('manufacturing.work-orders.record-production-form', wo.id)"
                    class="block glass-card rounded-3xl p-5 hover:border-cyan-500/50 hover:shadow-lg hover:shadow-cyan-500/10 transition-all active:scale-[0.98]"
                >
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <div class="text-xs font-bold text-cyan-400 mb-1 border border-cyan-500/30 bg-cyan-500/10 px-2 py-0.5 rounded-full w-fit">
                                {{ wo.wo_number }}
                            </div>
                            <h3 class="font-bold text-slate-900 dark:text-white text-lg leading-tight line-clamp-2">
                                {{ wo.product_name }}
                            </h3>
                            <p class="text-xs text-slate-500 mt-1 font-mono">{{ wo.product_sku }}</p>
                        </div>
                        <div class="h-10 w-10 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-500 dark:text-slate-400 group-hover:text-cyan-400 group-hover:bg-cyan-500/20 transition-colors">
                            <ArrowRightIcon class="h-5 w-5" />
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mb-4">
                        <div class="flex justify-between text-xs mb-2">
                            <span class="text-slate-500 dark:text-slate-400">Progress</span>
                            <span class="text-slate-900 dark:text-white font-mono font-bold">{{ wo.percent.toFixed(0) }}%</span>
                        </div>
                        <div class="h-2 bg-slate-50 dark:bg-slate-800 rounded-full overflow-hidden">
                            <div 
                                class="h-full bg-gradient-to-r from-cyan-500 to-blue-500 rounded-full"
                                :style="{ width: `${wo.percent}%` }"
                            ></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 pt-4 border-t border-slate-200 dark:border-slate-800">
                        <div>
                            <div class="text-[10px] text-slate-500 uppercase font-bold mb-1">Produced</div>
                            <div class="text-emerald-400 font-mono font-bold">{{ formatNumber(wo.qty_produced) }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-[10px] text-slate-500 uppercase font-bold mb-1">Remaining</div>
                            <div class="text-amber-400 font-mono font-bold">{{ formatNumber(wo.remaining) }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-[10px] text-slate-500 uppercase font-bold mb-1">Target</div>
                            <div class="text-slate-600 dark:text-slate-300 font-mono font-bold">{{ formatNumber(wo.qty_planned) }}</div>
                        </div>
                    </div>

                    <div class="mt-3 flex items-center justify-between text-[10px] text-slate-500 font-mono">
                        <div class="inline-flex items-center gap-1">
                            <ClockIcon class="h-4 w-4" />
                            <span>Start: {{ formatShortDate(wo.planned_start) }}</span>
                        </div>
                        <div>Finish: {{ formatShortDate(wo.planned_end) }}</div>
                    </div>
                </Link>
            </div>

            <div class="hidden lg:block rounded-2xl glass-card overflow-hidden">
                <div class="overflow-x-auto overflow-y-auto max-h-[650px]">
                    <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700">
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">WO</th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Product</th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Schedule Start</th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Schedule Finish</th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Produced</th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Target</th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Remaining</th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Progress</th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-16">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="wo in workOrders.data" :key="wo.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-xs font-bold text-cyan-400 mb-1 border border-cyan-500/30 bg-cyan-500/10 px-2 py-0.5 rounded-full w-fit">
                                        {{ wo.wo_number }}
                                    </div>
                                    <div class="text-[10px] text-slate-500 font-mono">{{ wo.product_sku }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm font-semibold text-slate-900 dark:text-white line-clamp-1">{{ wo.product_name }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-center text-sm text-slate-600 dark:text-slate-300 font-mono">
                                    {{ formatShortDate(wo.planned_start) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-center text-sm text-slate-600 dark:text-slate-300 font-mono">
                                    {{ formatShortDate(wo.planned_end) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right">
                                    <span class="text-emerald-400 font-mono font-bold">{{ formatNumber(wo.qty_produced) }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right">
                                    <span class="text-slate-600 dark:text-slate-300 font-mono font-bold">{{ formatNumber(wo.qty_planned) }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right">
                                    <span class="text-amber-400 font-mono font-bold">{{ formatNumber(wo.remaining) }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right">
                                    <span class="text-slate-900 dark:text-white font-mono font-bold">{{ wo.percent.toFixed(0) }}%</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right">
                                    <Link
                                        :href="route('manufacturing.work-orders.record-production-form', wo.id)"
                                        class="inline-flex items-center justify-center h-9 w-9 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-300 hover:text-cyan-400 hover:bg-cyan-500/10 transition-colors"
                                        title="Input Produksi"
                                    >
                                        <ArrowRightIcon class="h-5 w-5" />
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="workOrders.data.length === 0">
                                <td colspan="9" class="px-4 py-12 text-center text-slate-500 italic">No Active Jobs</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="workOrders.last_page > 1" class="mt-8 flex items-center justify-between">
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Showing {{ workOrders.from }} to {{ workOrders.to }} of {{ workOrders.total }} work orders
                </p>
                <div class="flex items-center gap-2">
                    <Link
                        v-for="link in workOrders.links"
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
    </AppLayout>
</template>
