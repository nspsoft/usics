<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import { 
    ArchiveBoxIcon, 
    MagnifyingGlassIcon,
    ArrowRightIcon,
    ExclamationCircleIcon
} from '@heroicons/vue/24/outline';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';
import { formatDate } from '@/helpers';

const props = defineProps({
    returns: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');

watch(search, debounce((value) => {
    router.get(route('portal.returns.index'), { search: value }, { preserveState: true, replace: true });
}, 300));

const getStatusColor = (status) => {
    const colors = {
        draft: 'bg-slate-100 text-slate-600',
        pending: 'bg-amber-100 text-amber-600',
        approved: 'bg-blue-100 text-blue-600',
        shipped: 'bg-purple-100 text-purple-600',
        received: 'bg-emerald-100 text-emerald-600',
        completed: 'bg-green-100 text-green-600',
        cancelled: 'bg-red-100 text-red-600',
    };
    return colors[status] || 'bg-slate-100 text-slate-600';
};
</script>

<template>
    <PortalLayout title="Returns & Claims">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-3">
                    <ArchiveBoxIcon class="w-7 h-7 text-indigo-500" />
                    Returns & Claims
                </h1>
                <p class="text-slate-500">Track returned items and claim status.</p>
            </div>
            <div class="relative">
                <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
                <input 
                    v-model="search"
                    type="text" 
                    placeholder="Search returns..." 
                    class="pl-10 pr-4 py-2 border border-slate-300 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                >
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="overflow-x-auto overflow-y-auto max-h-[600px]">
                <table class="w-full text-left text-sm">
                    <thead class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm bg-slate-50 dark:bg-slate-700/50 text-slate-500 font-medium">
                        <tr>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4">Return #</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4">PO Reference</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4">Date</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4">Reason</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4">Status</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        <tr v-for="ret in returns.data" :key="ret.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                            <td class="px-6 py-4 font-bold text-slate-900 dark:text-white">{{ ret.number }}</td>
                            <td class="px-6 py-4 text-indigo-600">{{ ret.purchase_order?.po_number || '-' }}</td>
                            <td class="px-6 py-4 text-slate-600 dark:text-slate-400">
                                {{ formatDate(ret.return_date) }}
                            </td>
                            <td class="px-6 py-4 text-slate-600 dark:text-slate-400 max-w-xs truncate">{{ ret.reason }}</td>
                            <td class="px-6 py-4">
                                <span :class="['px-2.5 py-1 rounded-full text-xs font-bold capitalize', getStatusColor(ret.status)]">
                                    {{ ret.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <Link 
                                    :href="route('portal.returns.show', ret.id)"
                                    class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800 font-semibold"
                                >
                                    View
                                    <ArrowRightIcon class="w-4 h-4" />
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="returns.data.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                <ArchiveBoxIcon class="w-12 h-12 mx-auto mb-3 text-slate-300" />
                                No returns found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div v-if="returns.links.length > 3" class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex justify-center">
                 <div class="flex gap-1">
                    <Link
                        v-for="(link, key) in returns.links"
                        :key="key"
                        :href="link.url || '#'"
                        v-html="link.label"
                        class="px-3 py-1 rounded-lg text-xs font-medium transition-colors"
                        :class="[
                            link.active 
                                ? 'bg-indigo-600 text-white' 
                                : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600',
                            !link.url ? 'opacity-50 cursor-not-allowed' : ''
                        ]"
                    />
                </div>
            </div>
        </div>
    </PortalLayout>
</template>
