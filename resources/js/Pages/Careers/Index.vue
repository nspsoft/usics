<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { defineProps } from 'vue';

const props = defineProps({
    jobs: Array
});
</script>

<template>
    <Head title="Careers" />

    <div class="min-h-screen bg-gray-50 text-gray-900">
        <!-- Navigation -->
        <nav class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 flex items-center">
                            <span class="font-bold text-2xl text-indigo-600">NSP Careers</span>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <Link href="/" class="text-gray-500 hover:text-gray-900">Back to ERP</Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero -->
        <div class="bg-indigo-700 text-white py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl lg:text-6xl mb-4">
                    Join Our Team
                </h1>
                <p class="mt-4 max-w-2xl text-xl text-indigo-200 mx-auto">
                    We are always looking for talented people to join us. Check out our open positions below.
                </p>
            </div>
        </div>

        <!-- Job List -->
        <div class="py-12">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div v-if="$page.props.flash?.success" class="mb-8 bg-green-50 border-l-4 border-green-400 p-4 rounded shadow-sm">
                    <p class="text-green-700 font-medium">{{ $page.props.flash.success }}</p>
                </div>

                <div v-if="jobs.length === 0" class="text-center py-12 bg-white rounded-xl shadow-sm border border-gray-100">
                    <p class="text-gray-500">There are no open positions at the moment. Please check back later.</p>
                </div>
                
                <div v-else class="space-y-4">
                    <div v-for="job in jobs" :key="job.id" class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-200 hover:shadow-md transition">
                        <div class="p-6">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="text-lg leading-6 font-bold text-gray-900">
                                        {{ job.title }}
                                    </h3>
                                    <p class="mt-1 max-w-2xl text-sm text-indigo-600 font-medium">
                                        {{ job.department?.name || 'General' }}
                                    </p>
                                </div>
                                <div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Open
                                    </span>
                                </div>
                            </div>
                            <div class="mt-4">
                                <p class="text-sm text-gray-500 line-clamp-2">
                                    {{ job.description }}
                                </p>
                            </div>
                            <div class="mt-6 flex justify-between items-center">
                                <span class="text-xs text-gray-400">
                                    Closing: {{ job.closing_date ? new Date(job.closing_date).toLocaleDateString() : 'No Deadline' }}
                                </span>
                                <Link :href="route('careers.show', job.id)" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
                                    View Details & Apply
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</template>
