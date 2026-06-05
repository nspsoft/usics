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
                <h2 class="font-semibold text-lg text-gray-800 dark:text-slate-100 leading-tight">Applicant Tracking System (ATS)</h2>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-4 lg:px-6">
                
                <div class="flex flex-col md:flex-row gap-4 overflow-x-auto pb-4">
                    
                    <div v-for="status in statuses" :key="status" class="flex-1 min-w-[280px] bg-gray-50 dark:bg-slate-900 border border-gray-100 dark:border-slate-800 rounded-lg p-3">
                        <h3 class="font-bold text-gray-700 dark:text-slate-200 text-sm mb-3 flex items-center justify-between border-b border-gray-200 dark:border-slate-800 pb-1.5">
                            <span>{{ status }}</span>
                            <span class="bg-gray-200 dark:bg-slate-800 text-gray-600 dark:text-slate-400 text-[10px] px-1.5 py-0.5 rounded-full">{{ applicantsByStatus[status].length }}</span>
                        </h3>
                        
                        <div class="space-y-2.5">
                            <div v-for="app in applicantsByStatus[status]" :key="app.id" class="bg-white dark:bg-slate-950/40 p-3 rounded-lg shadow-sm border border-gray-200 dark:border-slate-800/80">
                                <div class="flex justify-between items-start mb-1.5">
                                    <h4 class="font-semibold text-xs text-gray-900 dark:text-slate-100">{{ app.name }}</h4>
                                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 text-[9px] font-bold leading-none" 
                                          :class="app.match_score > 75 ? 'text-green-800 bg-green-100 dark:text-green-400 dark:bg-green-950/40 rounded-full' : (app.match_score > 40 ? 'text-yellow-800 bg-yellow-100 dark:text-yellow-400 dark:bg-yellow-950/40 rounded-full' : 'text-red-800 bg-red-100 dark:text-red-400 dark:bg-red-950/40 rounded-full')">
                                        {{ app.match_score }}% Match
                                    </span>
                                </div>
                                
                                <p class="text-[11px] text-indigo-600 dark:text-indigo-400 font-medium mb-0.5">{{ app.job_posting?.title }}</p>
                                <p class="text-[10px] text-gray-500 dark:text-slate-400 mb-2">{{ app.email }} • {{ app.phone }}</p>
                                
                                <div class="mb-2">
                                    <p class="text-[9px] uppercase text-gray-400 dark:text-slate-500 font-bold tracking-wider mb-1">Parsed Skills (AI)</p>
                                    <div class="flex flex-wrap gap-1">
                                        <span v-for="skill in (app.parsed_skills ? app.parsed_skills.split(', ') : [])" :key="skill" class="text-[9px] bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-slate-350 px-1 py-0.5 rounded border border-gray-200 dark:border-slate-700">
                                            {{ skill }}
                                        </span>
                                        <span v-if="!app.parsed_skills" class="text-[9px] text-gray-400 dark:text-slate-500">No skills parsed.</span>
                                    </div>
                                </div>
                                
                                <div class="flex justify-between items-center pt-2 border-t border-gray-150 dark:border-slate-800/80">
                                    <a v-if="app.resume_path" :href="'/storage/' + app.resume_path" target="_blank" class="text-[11px] text-blue-600 dark:text-blue-400 hover:underline flex items-center">
                                        <DocumentTextIcon class="w-3.5 h-3.5 mr-0.5" /> View CV
                                    </a>
                                    <span v-else class="text-[11px] text-gray-400 dark:text-slate-500">No CV</span>
                                    
                                    <div class="flex space-x-1">
                                        <select @change="changeStatus(app, $event.target.value)" class="text-[10px] border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 rounded-md py-0.5 pl-1.5 pr-5">
                                            <option value="" disabled selected>Move to...</option>
                                            <option v-for="s in statuses" :key="s" :value="s" :disabled="s === app.status">{{ s }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div v-if="applicantsByStatus[status].length === 0" class="text-center py-4 text-gray-400 dark:text-slate-500 text-xs border-2 border-dashed border-gray-250 dark:border-slate-800/80 rounded-lg">
                                No applicants
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </AppLayout>
</template>
