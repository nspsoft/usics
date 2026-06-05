<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { PlusIcon, BriefcaseIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    jobs: Array
});
</script>

<template>
    <Head title="Job Postings" />

    <AppLayout title="Job Postings">
        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-4 lg:px-6">
                <div class="mb-4 flex justify-between items-center">
                    <h2 class="font-semibold text-lg text-gray-800 dark:text-slate-100 leading-tight">Job Postings</h2>
                    <Link :href="route('hr.jobs.create')" class="bg-indigo-600 text-white px-3.5 py-1.5 rounded-lg hover:bg-indigo-700 transition flex items-center shadow-sm text-sm">
                        <PlusIcon class="w-4 h-4 mr-1" /> Create Job
                    </Link>
                </div>
                <div class="bg-white dark:bg-slate-900 overflow-hidden shadow-sm sm:rounded-lg border border-gray-150 dark:border-slate-800">
                    <div class="p-4 bg-white dark:bg-slate-900 border-b border-gray-200 dark:border-slate-800">
                        <div v-if="jobs.length === 0" class="text-center py-8 text-gray-500 dark:text-slate-400 text-sm">
                            <BriefcaseIcon class="w-10 h-10 mx-auto mb-2 text-gray-300 dark:text-slate-700" />
                            <p>No job postings available.</p>
                        </div>
                        
                        <div class="overflow-x-auto" v-else>
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800">
                                <thead class="bg-gray-50 dark:bg-slate-800/50">
                                    <tr>
                                        <th scope="col" class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Title</th>
                                        <th scope="col" class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Department</th>
                                        <th scope="col" class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                                        <th scope="col" class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Closing Date</th>
                                        <th scope="col" class="px-4 py-2.5 text-right text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-800">
                                    <tr v-for="job in jobs" :key="job.id" class="hover:bg-gray-50 dark:hover:bg-slate-800/40 transition">
                                        <td class="px-4 py-2.5 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-slate-100">
                                            {{ job.title }}
                                        </td>
                                        <td class="px-4 py-2.5 whitespace-nowrap text-sm text-gray-500 dark:text-slate-400">
                                            {{ job.department?.name || '-' }}
                                        </td>
                                        <td class="px-4 py-2.5 whitespace-nowrap">
                                            <span :class="[
                                                'px-2 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full',
                                                job.status === 'Open' ? 'bg-green-100 text-green-800 dark:bg-green-950/40 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-950/40 dark:text-red-400'
                                            ]">
                                                {{ job.status }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2.5 whitespace-nowrap text-sm text-gray-500 dark:text-slate-400">
                                            {{ job.closing_date ? new Date(job.closing_date).toLocaleDateString() : 'No Limit' }}
                                        </td>
                                        <td class="px-4 py-2.5 whitespace-nowrap text-right text-sm font-medium">
                                            <Link :href="route('hr.jobs.edit', job.id)" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 mr-3">Edit</Link>
                                            <Link :href="route('careers.show', job.id)" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300" target="_blank">View</Link>
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
