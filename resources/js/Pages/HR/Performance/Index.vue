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
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Company Performance (OKR)</h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="mb-6">
                    <h3 class="text-lg font-bold text-gray-800">Period: {{ currentPeriod }}</h3>
                    <p class="text-sm text-gray-500">Monitor employee OKR progress across the company.</p>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee Name</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Objectives</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg OKR Score</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="emp in employees" :key="emp.id" class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ emp.full_name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ emp.department?.name || '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ emp.objectives_count }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <span class="mr-2 text-sm font-bold" :class="emp.total_okr_score >= 80 ? 'text-green-600' : (emp.total_okr_score >= 50 ? 'text-yellow-600' : 'text-red-600')">
                                                    {{ emp.total_okr_score }}%
                                                </span>
                                                <div class="w-24 bg-gray-200 rounded-full h-1.5">
                                                    <div :class="emp.total_okr_score >= 80 ? 'bg-green-600' : (emp.total_okr_score >= 50 ? 'bg-yellow-600' : 'bg-red-600')" class="h-1.5 rounded-full" :style="'width: ' + Math.min(100, emp.total_okr_score) + '%'"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <Link :href="route('hr.performance.show', emp.id)" class="text-indigo-600 hover:text-indigo-900 inline-flex items-center">
                                                <ChartBarIcon class="w-4 h-4 mr-1" /> View Details
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
