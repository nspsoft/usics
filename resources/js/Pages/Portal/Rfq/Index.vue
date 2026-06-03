<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import { 
    ClipboardDocumentListIcon,
    CalendarIcon, 
    ArrowRightIcon,
    CheckCircleIcon,
    ClockIcon,
    XCircleIcon
} from '@heroicons/vue/24/outline';
import { ref, watch } from 'vue';
import { formatDate } from '@/helpers';

const props = defineProps({
    rfqs: Object,
    filters: Object,
});

const filter = ref(props.filters.filter || 'all');

const filterOptions = [
    { label: 'All', value: 'all' },
    { label: 'Open', value: 'open' },
    { label: 'Closed', value: 'closed' },
    { label: 'Awarded', value: 'awarded' },
];

const applyFilter = (val) => {
    router.get(route('portal.rfq.index'), { filter: val }, { preserveState: true });
};

const getStatusColor = (status) => {
    const colors = {
        open: 'bg-indigo-100 text-indigo-600',
        closed: 'bg-slate-100 text-slate-600',
        awarded: 'bg-emerald-100 text-emerald-600',
        cancelled: 'bg-red-100 text-red-600',
    };
    return colors[status] || 'bg-slate-100 text-slate-600';
};
</script>

<template>
    <PortalLayout title="Request for Quotation">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-3">
                    <ClipboardDocumentListIcon class="w-7 h-7 text-indigo-500" />
                    Request for Quotation
                </h1>
                <p class="text-slate-500">View and respond to RFQs from the company.</p>
            </div>
            
            <!-- Filters -->
            <div class="flex gap-2">
                <button 
                    v-for="opt in filterOptions" 
                    :key="opt.value"
                    @click="applyFilter(opt.value)"
                    class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors"
                    :class="filter === opt.value
                        ? 'bg-indigo-600 text-white' 
                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300'"
                >
                    {{ opt.label }}
                </button>
            </div>
        </div>

        <div class="space-y-4">
            <div 
                v-for="rfq in rfqs.data" 
                :key="rfq.id"
                class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-md transition-shadow group"
            >
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <span :class="['px-2.5 py-0.5 rounded-full text-xs font-bold uppercase', getStatusColor(rfq.status)]">
                                {{ rfq.status }}
                            </span>
                            <span class="text-xs text-slate-500 font-mono">{{ rfq.rfq_number }}</span>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white group-hover:text-indigo-600 transition-colors">
                            {{ rfq.title }}
                        </h3>
                        <p class="text-slate-500 text-sm mt-1 line-clamp-2">{{ rfq.description }}</p>
                    </div>

                    <div class="flex items-center gap-6 md:border-l md:pl-6 border-slate-200 dark:border-slate-700">
                        <div class="text-right">
                            <p class="text-xs text-slate-400 mb-1">Deadline</p>
                            <p class="text-sm font-semibold text-slate-700 dark:text-slate-300 flex items-center justify-end gap-1">
                                <CalendarIcon class="w-4 h-4" />
                                {{ formatDate(rfq.deadline) }}
                            </p>
                        </div>
                        <Link 
                            :href="route('portal.rfq.show', rfq.id)"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 rounded-xl font-semibold text-sm transition-colors"
                        >
                            View Details
                            <ArrowRightIcon class="w-4 h-4" />
                        </Link>
                    </div>
                </div>
                
                <!-- Supplier Status Indicator (if needed) -->
                <div v-if="rfq.target_suppliers && rfq.target_suppliers[0]" class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700 flex items-center gap-2 text-xs">
                    <span v-if="rfq.target_suppliers[0].status === 'responded'" class="text-emerald-600 flex items-center gap-1 font-bold">
                        <CheckCircleIcon class="w-4 h-4" /> Responded
                    </span>
                    <span v-else-if="rfq.target_suppliers[0].status === 'viewed'" class="text-amber-600 flex items-center gap-1 font-bold">
                        <ClockIcon class="w-4 h-4" /> Viewed
                    </span>
                    <span v-else class="text-slate-400 flex items-center gap-1">
                        Pending Response
                    </span>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="rfqs.data.length === 0" class="py-16 text-center bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700">
                <ClipboardDocumentListIcon class="w-16 h-16 text-slate-300 mx-auto mb-4" />
                <p class="text-slate-500">No RFQs found.</p>
            </div>
            
            <!-- Pagination -->
            <div v-if="rfqs.links.length > 3" class="pt-4 flex justify-center">
                 <div class="flex gap-1">
                    <Link
                        v-for="(link, key) in rfqs.links"
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
