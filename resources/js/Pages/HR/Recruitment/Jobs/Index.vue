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
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="mb-6 flex justify-between items-center">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Job Postings</h2>
                    <Link :href="route('hr.jobs.create')" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition flex items-center shadow-sm">
                        <PlusIcon class="w-5 h-5 mr-1" /> Create Job
                    </Link>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <div v-if="jobs.length === 0" class="text-center py-12 text-gray-500">
                            <BriefcaseIcon class="w-12 h-12 mx-auto mb-3 text-gray-300" />
                            <p>No job postings available.</p>
                        </div>
                        
                        <div class="overflow-x-auto" v-else>
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Closing Date</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="job in jobs" :key="job.id" class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ job.title }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ job.department?.name || '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="[
                                                'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                                                job.status === 'Open' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                            ]">
                                                {{ job.status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ job.closing_date ? new Date(job.closing_date).toLocaleDateString() : 'No Limit' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <Link :href="route('hr.jobs.edit', job.id)" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</Link>
                                            <Link :href="route('careers.show', job.id)" class="text-blue-600 hover:text-blue-900" target="_blank">View</Link>
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
