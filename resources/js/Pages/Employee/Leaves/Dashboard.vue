<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    CalendarDaysIcon, 
    PlusIcon, 
    CheckCircleIcon, 
    ClockIcon, 
    XCircleIcon,
    ArrowRightIcon
} from '@heroicons/vue/24/outline';
import moment from 'moment';

const props = defineProps({
    balances: Array,
    leaves: Array,
    stats: Object,
});

const formatDate = (date) => {
    return moment(date).format('DD MMM YYYY');
};

const getStatusColor = (status) => {
    switch (status) {
        case 'approved': return 'text-green-600 bg-green-100 dark:bg-green-900/30 dark:text-green-400';
        case 'rejected': return 'text-red-600 bg-red-100 dark:bg-red-900/30 dark:text-red-400';
        default: return 'text-orange-600 bg-orange-100 dark:bg-orange-900/30 dark:text-orange-400';
    }
};

const getStatusIcon = (status) => {
    switch (status) {
        case 'approved': return CheckCircleIcon;
        case 'rejected': return XCircleIcon;
        default: return ClockIcon;
    }
};
</script>

<template>
    <AppLayout title="My Time-Off">
        <template #header>
            <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">
                My Time-Off
            </h2>
        </template>

        <!-- Mobile First Container -->
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 py-6 pb-24">
            
            <!-- Balances Section -->
            <div class="px-4 sm:px-0 mb-6">
                <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Leave Balances ({{ new Date().getFullYear() }})</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div v-for="balance in balances" :key="balance.id" class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="p-2 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg text-indigo-600 dark:text-indigo-400">
                                <CalendarDaysIcon class="w-5 h-5" />
                            </div>
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300 line-clamp-1">{{ balance.leave_type.name }}</span>
                        </div>
                        <div class="mt-3 flex items-end justify-between">
                            <div>
                                <span class="text-2xl font-bold text-slate-900 dark:text-white">{{ balance.total_days - balance.used_days }}</span>
                                <span class="text-xs text-slate-500 ml-1">Days left</span>
                            </div>
                            <div class="text-xs text-slate-400">
                                of {{ balance.total_days }}
                            </div>
                        </div>
                        <!-- Progress bar -->
                        <div class="w-full bg-slate-100 dark:bg-slate-700 rounded-full h-1.5 mt-3 overflow-hidden">
                            <div class="bg-indigo-500 h-1.5 rounded-full" :style="{ width: `${(balance.used_days / balance.total_days) * 100}%` }"></div>
                        </div>
                    </div>
                    
                    <div v-if="balances.length === 0" class="col-span-2 bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-sm border border-slate-100 dark:border-slate-700 text-center">
                        <p class="text-slate-500 text-sm">No leave balances found for this year.</p>
                    </div>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="px-4 sm:px-0 mb-8">
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-1 shadow-sm border border-slate-100 dark:border-slate-700 flex divide-x divide-slate-100 dark:divide-slate-700">
                    <div class="flex-1 p-3 text-center">
                        <div class="text-2xl font-semibold text-orange-500">{{ stats.pending }}</div>
                        <div class="text-xs text-slate-500 mt-1">Pending</div>
                    </div>
                    <div class="flex-1 p-3 text-center">
                        <div class="text-2xl font-semibold text-green-500">{{ stats.approved }}</div>
                        <div class="text-xs text-slate-500 mt-1">Approved</div>
                    </div>
                </div>
            </div>

            <!-- History Section -->
            <div class="px-4 sm:px-0">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Recent Requests</h3>
                </div>
                
                <div class="space-y-3">
                    <div v-for="leave in leaves" :key="leave.id" class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h4 class="font-medium text-slate-900 dark:text-white">{{ leave.leave_type.name }}</h4>
                                <p class="text-xs text-slate-500 mt-0.5">Applied on {{ formatDate(leave.created_at) }}</p>
                            </div>
                            <span :class="['px-2.5 py-1 text-[10px] font-medium rounded-full flex items-center gap-1 uppercase tracking-wider', getStatusColor(leave.status)]">
                                <component :is="getStatusIcon(leave.status)" class="w-3.5 h-3.5" />
                                {{ leave.status }}
                            </span>
                        </div>
                        
                        <div class="flex items-center gap-3 bg-slate-50 dark:bg-slate-700/50 rounded-xl p-3 mb-3">
                            <div class="flex-1">
                                <p class="text-[10px] text-slate-500 uppercase">From</p>
                                <p class="text-sm font-medium text-slate-800 dark:text-slate-200">{{ formatDate(leave.start_date) }}</p>
                            </div>
                            <ArrowRightIcon class="w-4 h-4 text-slate-400" />
                            <div class="flex-1 text-right">
                                <p class="text-[10px] text-slate-500 uppercase">To</p>
                                <p class="text-sm font-medium text-slate-800 dark:text-slate-200">{{ formatDate(leave.end_date) }}</p>
                            </div>
                        </div>

                        <div class="flex justify-between items-center text-sm">
                            <span class="text-slate-500">{{ leave.total_days }} day(s) requested</span>
                            <span v-if="leave.status === 'rejected'" class="text-xs text-red-500 font-medium truncate max-w-[150px]" :title="leave.rejection_reason">
                                {{ leave.rejection_reason }}
                            </span>
                        </div>
                    </div>

                    <div v-if="leaves.length === 0" class="text-center py-8">
                        <div class="w-16 h-16 bg-slate-50 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-3">
                            <CalendarDaysIcon class="w-8 h-8 text-slate-400" />
                        </div>
                        <p class="text-slate-500 text-sm">You haven't made any leave requests yet.</p>
                    </div>
                </div>
            </div>

            <!-- Floating Action Button (FAB) for Mobile PWA -->
            <div class="fixed bottom-6 right-6 lg:bottom-10 lg:right-10 z-50">
                <Link :href="route('my-timeoff.create')" class="flex items-center justify-center w-14 h-14 bg-indigo-600 text-white rounded-full shadow-lg hover:bg-indigo-700 hover:scale-105 active:scale-95 transition-all duration-200 focus:outline-none focus:ring-4 focus:ring-indigo-500 focus:ring-opacity-50">
                    <PlusIcon class="w-6 h-6" />
                </Link>
            </div>
            
        </div>
    </AppLayout>
</template>
