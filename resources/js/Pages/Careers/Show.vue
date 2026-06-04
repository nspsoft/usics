<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { defineProps } from 'vue';

const props = defineProps({
    job: Object
});

const form = useForm({
    name: '',
    email: '',
    phone: '',
    resume: null,
});

const submit = () => {
    form.post(route('careers.apply', props.job.id), {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head :title="job.title" />

    <div class="min-h-screen bg-gray-50 text-gray-900 pb-12">
        <!-- Navigation -->
        <nav class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <Link :href="route('careers.index')" class="font-bold text-2xl text-indigo-600">NSP Careers</Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Content -->
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 mt-10">
            
            <Link :href="route('careers.index')" class="text-indigo-600 hover:text-indigo-800 font-medium mb-6 inline-block">
                &larr; Back to Open Positions
            </Link>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Job Details -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white shadow-sm rounded-xl border border-gray-200 p-8">
                        <h1 class="text-3xl font-extrabold text-gray-900 mb-2">{{ job.title }}</h1>
                        <p class="text-lg text-indigo-600 font-medium mb-6">{{ job.department?.name || 'General' }}</p>
                        
                        <div class="prose max-w-none text-gray-600 mb-8">
                            <h3 class="text-xl font-bold text-gray-900 mb-3 border-b pb-2">Description</h3>
                            <p class="whitespace-pre-line">{{ job.description }}</p>
                        </div>
                        
                        <div class="prose max-w-none text-gray-600">
                            <h3 class="text-xl font-bold text-gray-900 mb-3 border-b pb-2">Requirements</h3>
                            <p class="whitespace-pre-line">{{ job.requirements }}</p>
                        </div>
                    </div>
                </div>

                <!-- Application Form -->
                <div class="lg:col-span-1">
                    <div class="bg-white shadow-sm rounded-xl border border-gray-200 p-6 sticky top-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-4 border-b pb-2">Apply for this position</h3>
                        
                        <form @submit.prevent="submit" class="space-y-4">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
                                <input type="text" id="name" v-model="form.name" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                                <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                            </div>
                            
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                                <input type="email" id="email" v-model="form.email" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                                <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
                            </div>
                            
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                                <input type="text" id="phone" v-model="form.phone" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                                <p v-if="form.errors.phone" class="mt-1 text-xs text-red-600">{{ form.errors.phone }}</p>
                            </div>
                            
                            <div>
                                <label for="resume" class="block text-sm font-medium text-gray-700">Resume / CV (PDF only, max 5MB)</label>
                                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md relative" :class="{'border-indigo-500 bg-indigo-50': form.resume}">
                                    <div class="space-y-1 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <div class="flex text-sm text-gray-600 justify-center">
                                            <label for="resume" class="relative cursor-pointer rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                                <span>{{ form.resume ? form.resume.name : 'Upload a file' }}</span>
                                                <input id="resume" name="resume" type="file" accept=".pdf" class="sr-only" @input="form.resume = $event.target.files[0]" required />
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <p v-if="form.errors.resume" class="mt-1 text-xs text-red-600">{{ form.errors.resume }}</p>
                            </div>
                            
                            <div class="pt-4">
                                <button type="submit" :disabled="form.processing" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50">
                                    {{ form.processing ? 'Submitting...' : 'Submit Application' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>
