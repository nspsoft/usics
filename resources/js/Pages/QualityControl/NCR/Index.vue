<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import { formatDate } from '@/helpers';

defineProps({
    ncrs: Object,
});
</script>

<template>
    <AppLayout title="Non-Conformance Reports">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Non-Conformance Reports (NCR)
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <div class="mb-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Manage detected defects and determine disposition (Rework, Scrap, Return).
                        </p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Source</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Defect</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Disposition</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="ncr in ncrs.data" :key="ncr.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ formatDate(ncr.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ ncr.inspection?.reference_type?.split('\\').pop() }} #{{ ncr.inspection?.reference_id }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ ncr.defect_description }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" 
                                            :class="{
                                                'bg-red-100 text-red-800': ncr.status === 'open',
                                                'bg-green-100 text-green-800': ncr.status === 'closed'
                                            }">
                                            {{ ncr.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 capitalize">
                                        {{ ncr.disposition?.replace('_', ' ') || '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <Link :href="route('qc.ncr.show', ncr.id)" class="text-indigo-600 hover:text-indigo-900">
                                            Manage
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="ncrs.data.length === 0">
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                        No Non-Conformance Reports found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
