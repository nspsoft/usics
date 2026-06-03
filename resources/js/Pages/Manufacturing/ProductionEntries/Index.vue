<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatNumber, formatDate, formatTime } from '@/helpers';
import debounce from 'lodash/debounce';
import { MagnifyingGlassIcon, ArrowTopRightOnSquareIcon, PencilSquareIcon } from '@heroicons/vue/24/outline';

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
</template>
