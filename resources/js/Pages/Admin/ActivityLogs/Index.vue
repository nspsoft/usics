<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    MagnifyingGlassIcon,
    EyeIcon,
    Bars3Icon,
    ArrowDownTrayIcon,
    TrashIcon,
    ChartBarIcon,
} from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';
import { formatNumber } from '@/helpers';
import Swal from 'sweetalert2';

const props = defineProps({
    logs: Object,
    filters: Object,
    subjectTypes: Array,
});

const search = ref(props.filters.search || '');
const subjectType = ref(props.filters.subject_type || '');
const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');

const applyFilters = debounce(() => {
    router.get('/admin/activity-logs', {
        search: search.value || undefined,
        subject_type: subjectType.value || undefined,
        start_date: startDate.value || undefined,
        end_date: endDate.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, subjectType, startDate, endDate], applyFilters);

const handleExport = () => {
    const params = new URLSearchParams({
        start_date: startDate.value,
        end_date: endDate.value,
    }).toString();
    window.location.href = `/admin/activity-logs/export?${params}`;
};

const handleReset = () => {
    if (!startDate.value || !endDate.value) {
        Swal.fire('Oops!', 'Please select both start and end dates to reset logs.', 'warning');
        return;
    }

    Swal.fire({
        title: 'Are you sure?',
        text: `This will permanently delete activity logs from ${startDate.value} to ${endDate.value}.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post('/admin/activity-logs/reset', {
                start_date: startDate.value,
                end_date: endDate.value,
            }, {
                onSuccess: () => {
                    Swal.fire('Deleted!', 'Activity logs have been reset.', 'success');
                }
            });
        }
    });
};

const getEventBadge = (event) => {
    const badges = {
        created: 'bg-green-500/20 text-green-400 border-green-500/30',
        updated: 'bg-blue-500/20 text-blue-400 border-blue-500/30',
        deleted: 'bg-red-500/20 text-red-400 border-red-500/30',
    };
    return badges[event] || 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30';
};

const formatValue = (value) => {
    if (value === null || value === undefined) return '-';
    
    // Handle ISO date strings (e.g., 2026-01-26T07:44:18.000000Z)
    if (typeof value === 'string' && /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/.test(value)) {
        try {
            const date = new Date(value);
            return date.toLocaleString('id-ID', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            }).replace(/\//g, '-').replace(',', '');
        } catch (e) {
            return value;
        }
    }

    // Handle numbers with too many decimals
    if (typeof value === 'number' || (!isNaN(value) && !isNaN(parseFloat(value)))) {
        return formatNumber(value);
    }

    return value;
};

const formatChanges = (log) => {
    if (!log.properties) return null;
    
    const props = log.properties;
    let changes = [];

    // Handle standard Spatie Activity Log format (attributes and old)
    if (props.attributes && props.old) {
        Object.keys(props.attributes).forEach(key => {
            if (key !== 'updated_at' && props.old[key] != props.attributes[key]) {
                changes.push({
                    key: key,
                    old: formatValue(props.old[key]),
                    new: formatValue(props.attributes[key])
                });
            }
        });
    } 
    // Handle custom logs (like the one I just added for SO Item Qty)
    else if (props.old_qty !== undefined && props.new_qty !== undefined) {
        changes.push({
            key: 'qty',
            old: formatValue(props.old_qty),
            new: formatValue(props.new_qty)
        });
    }

    return changes;
};
</script>

<template>
    <Head title="Activity Logs" />
    
    <AppLayout title="Activity Logs" :render-header="false">
        <div class="mb-8">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Activity Logs</h1>
                    <p class="text-sm text-slate-500 mt-1">Monitor and manage system activity audit trails.</p>
                </div>
                
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('admin.activity-logs.dashboard')"
                        class="flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl shadow-lg shadow-blue-500/20 transition-all active:scale-95"
                    >
                        <ChartBarIcon class="h-4 w-4" />
                        <span>Dashboard Analytics</span>
                    </Link>
                    <button
                        @click="handleExport"
                        class="flex items-center justify-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl shadow-lg shadow-emerald-500/20 transition-all active:scale-95"
                    >
                        <ArrowDownTrayIcon class="h-4 w-4" />
                        <span>Download Excel</span>
                    </button>
                    <button
                        @click="handleReset"
                        class="flex items-center justify-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl shadow-lg shadow-red-500/20 transition-all active:scale-95"
                    >
                        <TrashIcon class="h-4 w-4" />
                        <span>Reset Logs</span>
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 bg-slate-50/50 dark:bg-slate-800/30 p-4 rounded-2xl border border-slate-100 dark:border-slate-700">
                <div class="relative">
                    <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-500" />
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search description or user..."
                        class="block w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 py-2 pl-9 pr-4 text-sm text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50 transition-all"
                    />
                </div>

                <select
                    v-model="subjectType"
                    class="block w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 py-2 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 transition-all"
                >
                    <option value="">All Models</option>
                    <option v-for="type in subjectTypes" :key="type.value" :value="type.value">
                        {{ type.value }}
                    </option>
                </select>

                <div class="flex items-center gap-2">
                    <input
                        v-model="startDate"
                        type="date"
                        class="block w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 py-2 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 transition-all"
                    />
                </div>

                <div class="flex items-center gap-2">
                    <input
                        v-model="endDate"
                        type="date"
                        class="block w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 py-2 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 transition-all"
                    />
                </div>
            </div>
        </div>

        <div class="rounded-2xl glass-card overflow-hidden">
            <div class="overflow-x-auto overflow-y-auto max-h-[600px]">
                <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700">
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Date</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">User</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Subject</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Event</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Description</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Changes</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr 
                            v-for="log in logs.data" 
                            :key="log.id"
                            class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors"
                        >
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-slate-600 dark:text-slate-300">{{ log.created_at }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-slate-900 dark:text-white">{{ log.causer_name }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-slate-600 dark:text-slate-300">
                                    {{ log.subject_type }}
                                    <template v-if="log.subject_id">#{{ log.subject_id }}</template>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span 
                                    class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium capitalize"
                                    :class="getEventBadge(log.event)"
                                >
                                    {{ log.event }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs text-slate-500 dark:text-slate-400 leading-none">{{ log.description }}</span>
                                <div v-if="log.properties?.reason" class="mt-1 text-[10px] text-amber-500 font-medium italic">
                                    "{{ log.properties.reason }}"
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-1">
                                    <div v-for="change in formatChanges(log)" :key="change.key" class="flex items-center gap-1.5 text-[10px]">
                                        <span class="text-slate-500 font-mono">{{ change.key }}:</span>
                                        <span class="text-red-400 line-through">{{ change.old ?? '-' }}</span>
                                        <span class="text-slate-600">→</span>
                                        <span class="text-emerald-400 font-bold">{{ change.new ?? '-' }}</span>
                                    </div>
                                    <span v-if="formatChanges(log)?.length === 0" class="text-[10px] text-slate-600 italic">-</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <Link
                                    :href="`/admin/activity-logs/${log.id}`"
                                    class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors inline-block"
                                >
                                    <EyeIcon class="h-4 w-4" />
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="logs.data.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center">
                                <Bars3Icon class="mx-auto h-12 w-12 text-slate-600" />
                                <h3 class="mt-2 text-sm font-medium text-slate-600 dark:text-slate-300">No activity logs found</h3>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div v-if="logs.last_page > 1" class="border-t border-slate-200 dark:border-slate-800 px-6 py-4 flex items-center justify-between">
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Showing {{ logs.from }} to {{ logs.to }} of {{ logs.total }}
                </p>
                <div class="flex items-center gap-2">
                    <Link
                        v-for="link in logs.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        class="px-3 py-1.5 rounded-lg text-sm transition-colors"
                        :class="link.active 
                            ? 'bg-blue-600 text-slate-900 dark:text-white' 
                            : link.url 
                                ? 'text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 hover:text-slate-900 dark:text-white' 
                                : 'text-white cursor-not-allowed'"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>



