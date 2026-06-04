<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    employee: Object,
    objectives: Array,
    currentPeriod: String
});
</script>

<template>
    <Head :title="'Performance: ' + employee.full_name" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Performance Details: {{ employee.full_name }}
                </h2>
                <Link :href="route('hr.performance.index')" class="text-gray-600 hover:text-gray-900 transition">
                    &larr; Back to Performance
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6 p-6">
                    <div class="flex items-center space-x-4">
                        <div class="h-16 w-16 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-2xl">
                            {{ employee.full_name ? employee.full_name.split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase() : '' }}
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">{{ employee.full_name }}</h3>
                            <p class="text-gray-500">{{ employee.department?.name || 'No Dept' }} • {{ employee.position?.title || 'No Position' }}</p>
                        </div>
                    </div>
                </div>

                <div v-if="objectives.length === 0" class="bg-white rounded-lg shadow-sm p-12 text-center border border-gray-200">
                    <p class="text-gray-500">This employee has no objectives set for {{ currentPeriod }}.</p>
                </div>

                <div v-else class="space-y-6">
                    <div v-for="obj in objectives" :key="obj.id" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        
                        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                            <div>
                                <h4 class="font-bold text-lg text-gray-900">{{ obj.title }}</h4>
                                <p class="text-xs text-gray-500">Weight: {{ obj.weight }}%</p>
                            </div>
                            <div class="text-right">
                                <span class="text-2xl font-black text-indigo-600">{{ obj.score }}%</span>
                                <p class="text-xs text-gray-500 font-bold uppercase tracking-wide">Completion</p>
                            </div>
                        </div>

                        <div class="p-6">
                            <h5 class="font-semibold text-gray-700 mb-4">Key Results</h5>

                            <div v-if="obj.key_results.length === 0" class="text-sm text-gray-400 italic">
                                No key results.
                            </div>

                            <div v-else class="space-y-4">
                                <div v-for="kr in obj.key_results" :key="kr.id" class="p-4 bg-gray-50 rounded-lg border border-gray-100">
                                    <p class="font-medium text-gray-800 text-sm mb-1">{{ kr.title }}</p>
                                    
                                    <div class="w-full bg-gray-200 rounded-full h-2.5 mb-1 max-w-md">
                                        <div class="bg-indigo-600 h-2.5 rounded-full" :style="'width: ' + Math.min(100, (kr.current_value / kr.target_value) * 100) + '%'"></div>
                                    </div>
                                    <p class="text-xs text-gray-500">{{ kr.current_value }} / {{ kr.target_value }} ({{ Math.round((kr.current_value / kr.target_value) * 100) }}%)</p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
