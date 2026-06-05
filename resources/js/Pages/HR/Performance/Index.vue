<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ChartBarIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    employees: Array,
    currentPeriod: String
});
</script>

<template>
    <Head title="Performance Monitoring" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-lg text-gray-800 dark:text-slate-100 leading-tight">Company Performance (OKR)</h2>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-4 lg:px-6">
                
                <div class="mb-4">
                    <h3 class="text-base font-bold text-gray-800 dark:text-slate-100">Period: {{ currentPeriod }}</h3>
                    <p class="text-xs text-gray-500 dark:text-slate-400">Monitor employee OKR progress across the company.</p>
                </div>

                <div class="bg-white dark:bg-slate-900 overflow-hidden shadow-sm sm:rounded-lg border border-gray-150 dark:border-slate-800">
                    <div class="p-4 bg-white dark:bg-slate-900 border-b border-gray-200 dark:border-slate-800">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800">
                                <thead class="bg-gray-50 dark:bg-slate-800/50">
                                    <tr>
                                        <th scope="col" class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Employee Name</th>
                                        <th scope="col" class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Department</th>
                                        <th scope="col" class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Total Objectives</th>
                                        <th scope="col" class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Avg OKR Score</th>
                                        <th scope="col" class="px-4 py-2.5 text-right text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-800">
                                    <tr v-for="emp in employees" :key="emp.id" class="hover:bg-gray-50 dark:hover:bg-slate-800/40 transition">
                                        <td class="px-4 py-2.5 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-slate-100">
                                            {{ emp.full_name }}
                                        </td>
                                        <td class="px-4 py-2.5 whitespace-nowrap text-sm text-gray-500 dark:text-slate-400">
                                            {{ emp.department?.name || '-' }}
                                        </td>
                                        <td class="px-4 py-2.5 whitespace-nowrap text-sm text-gray-500 dark:text-slate-400">
                                            {{ emp.objectives_count }}
                                        </td>
                                        <td class="px-4 py-2.5 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <span class="mr-2 text-xs font-bold" :class="emp.total_okr_score >= 80 ? 'text-green-600 dark:text-green-400' : (emp.total_okr_score >= 50 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400')">
                                                    {{ emp.total_okr_score }}%
                                                </span>
                                                <div class="w-20 bg-gray-200 dark:bg-slate-700 rounded-full h-1.5">
                                                    <div :class="emp.total_okr_score >= 80 ? 'bg-green-600 dark:bg-green-500' : (emp.total_okr_score >= 50 ? 'bg-yellow-600 dark:bg-yellow-500' : 'bg-red-600 dark:bg-red-500')" class="h-1.5 rounded-full" :style="'width: ' + Math.min(100, emp.total_okr_score) + '%'"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2.5 whitespace-nowrap text-right text-xs font-medium">
                                            <Link :href="route('hr.performance.show', emp.id)" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 inline-flex items-center">
                                                <ChartBarIcon class="w-3.5 h-3.5 mr-0.5" /> View Details
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
