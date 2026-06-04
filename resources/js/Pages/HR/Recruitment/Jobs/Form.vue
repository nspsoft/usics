<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { defineProps } from 'vue';

const props = defineProps({
    job: {
        type: Object,
        default: () => ({})
    },
    departments: Array
});

const isEdit = !!props.job.id;

const form = useForm({
    title: props.job.title || '',
    department_id: props.job.department_id || '',
    description: props.job.description || '',
    requirements: props.job.requirements || '',
    status: props.job.status || 'Open',
    closing_date: props.job.closing_date ? props.job.closing_date.split('T')[0] : '',
});

const submit = () => {
    if (isEdit) {
        form.put(route('hr.jobs.update', props.job.id));
    } else {
        form.post(route('hr.jobs.store'));
    }
};
</script>

<template>
    <Head :title="isEdit ? 'Edit Job Posting' : 'Create Job Posting'" />

    <AppLayout :title="isEdit ? 'Edit Job Posting' : 'Create Job Posting'">
        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="mb-6 flex justify-between items-center">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        {{ isEdit ? 'Edit Job Posting' : 'Create Job Posting' }}
                    </h2>
                    <Link :href="route('hr.jobs.index')" class="text-gray-600 hover:text-gray-900 transition">
                        &larr; Back to Jobs
                    </Link>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-gray-900">
                    <form @submit.prevent="submit" class="space-y-6">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel for="title" value="Job Title" />
                                <TextInput id="title" v-model="form.title" type="text" class="mt-1 block w-full" required />
                                <InputError :message="form.errors.title" class="mt-2" />
                            </div>
                            
                            <div>
                                <InputLabel for="department_id" value="Department" />
                                <select id="department_id" v-model="form.department_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="" disabled>Select Department</option>
                                    <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
                                </select>
                                <InputError :message="form.errors.department_id" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="description" value="Job Description" />
                            <textarea id="description" v-model="form.description" rows="4" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required></textarea>
                            <InputError :message="form.errors.description" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="requirements" value="Requirements (Keywords & Skills)" />
                            <p class="text-xs text-gray-500 mb-1">Used by AI Resume Parser to calculate match score. Separate skills with spaces or commas.</p>
                            <textarea id="requirements" v-model="form.requirements" rows="4" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required></textarea>
                            <InputError :message="form.errors.requirements" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel for="status" value="Status" />
                                <select id="status" v-model="form.status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="Open">Open</option>
                                    <option value="Closed">Closed</option>
                                </select>
                                <InputError :message="form.errors.status" class="mt-2" />
                            </div>
                            
                            <div>
                                <InputLabel for="closing_date" value="Closing Date (Optional)" />
                                <TextInput id="closing_date" v-model="form.closing_date" type="date" class="mt-1 block w-full" />
                                <InputError :message="form.errors.closing_date" class="mt-2" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-4 pt-4 border-t">
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                {{ isEdit ? 'Update Job' : 'Create Job' }}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
