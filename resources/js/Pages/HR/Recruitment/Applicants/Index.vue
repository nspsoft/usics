<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { UserIcon, DocumentTextIcon, CheckCircleIcon, XCircleIcon } from '@heroicons/vue/24/outline';
import { defineProps, computed } from 'vue';

const props = defineProps({
    applicants: Array
});

const statuses = ['Applied', 'Interview', 'Hired', 'Rejected'];

const applicantsByStatus = computed(() => {
    const grouped = {};
    statuses.forEach(s => grouped[s] = []);
    props.applicants.forEach(app => {
        if (grouped[app.status]) {
            grouped[app.status].push(app);
        }
    });
    return grouped;
});

const form = useForm({
    status: ''
});

const changeStatus = (applicant, newStatus) => {
    form.status = newStatus;
    form.put(route('hr.applicants.status', applicant.id), {
        preserveScroll: true
    });
};
</script>

<template>
    <Head title="Applicant Tracking" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Applicant Tracking System (ATS)</h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="flex flex-col md:flex-row gap-6 overflow-x-auto pb-6">
                    
                    <div v-for="status in statuses" :key="status" class="flex-1 min-w-[300px] bg-gray-50 rounded-lg p-4">
                        <h3 class="font-bold text-gray-700 mb-4 flex items-center justify-between border-b pb-2">
                            <span>{{ status }}</span>
                            <span class="bg-gray-200 text-gray-600 text-xs px-2 py-1 rounded-full">{{ applicantsByStatus[status].length }}</span>
                        </h3>
                        
                        <div class="space-y-3">
                            <div v-for="app in applicantsByStatus[status]" :key="app.id" class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                                <div class="flex justify-between items-start mb-2">
                                    <h4 class="font-semibold text-sm text-gray-900">{{ app.name }}</h4>
                                    <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none" 
                                          :class="app.match_score > 75 ? 'text-green-800 bg-green-100 rounded-full' : (app.match_score > 40 ? 'text-yellow-800 bg-yellow-100 rounded-full' : 'text-red-800 bg-red-100 rounded-full')">
                                        {{ app.match_score }}% Match
                                    </span>
                                </div>
                                
                                <p class="text-xs text-indigo-600 font-medium mb-1">{{ app.job_posting?.title }}</p>
                                <p class="text-xs text-gray-500 mb-3">{{ app.email }} • {{ app.phone }}</p>
                                
                                <div class="mb-3">
                                    <p class="text-[10px] uppercase text-gray-400 font-bold tracking-wider mb-1">Parsed Skills (AI)</p>
                                    <div class="flex flex-wrap gap-1">
                                        <span v-for="skill in (app.parsed_skills ? app.parsed_skills.split(', ') : [])" :key="skill" class="text-[10px] bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded border border-gray-200">
                                            {{ skill }}
                                        </span>
                                        <span v-if="!app.parsed_skills" class="text-[10px] text-gray-400">No skills parsed.</span>
                                    </div>
                                </div>
                                
                                <div class="flex justify-between items-center pt-3 border-t">
                                    <a v-if="app.resume_path" :href="'/storage/' + app.resume_path" target="_blank" class="text-xs text-blue-600 hover:underline flex items-center">
                                        <DocumentTextIcon class="w-4 h-4 mr-1" /> View CV
                                    </a>
                                    <span v-else class="text-xs text-gray-400">No CV</span>
                                    
                                    <div class="flex space-x-1">
                                        <select @change="changeStatus(app, $event.target.value)" class="text-xs border-gray-300 rounded-md py-1 pl-2 pr-6">
                                            <option value="" disabled selected>Move to...</option>
                                            <option v-for="s in statuses" :key="s" :value="s" :disabled="s === app.status">{{ s }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div v-if="applicantsByStatus[status].length === 0" class="text-center py-6 text-gray-400 text-sm border-2 border-dashed border-gray-200 rounded-lg">
                                No applicants
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </AppLayout>
</template>
