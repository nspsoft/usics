<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { PlusIcon, CheckCircleIcon } from '@heroicons/vue/24/outline';
import { ref } from 'vue';

const props = defineProps({
    objectives: Array,
    currentPeriod: String,
    employee: Object
});

const showObjModal = ref(false);
const showKrModal = ref(null);

const objForm = useForm({
    title: '',
    period: props.currentPeriod,
    weight: 100
});

const krForm = useForm({
    title: '',
    target_value: 100
});

const progressForm = useForm({
    current_value: 0
});

const submitObj = () => {
    objForm.post(route('employee.performance.storeObjective'), {
        onSuccess: () => {
            objForm.reset();
            showObjModal.value = false;
        }
    });
};

const submitKr = (objectiveId) => {
    krForm.post(route('employee.performance.storeKeyResult', objectiveId), {
        onSuccess: () => {
            krForm.reset();
            showKrModal.value = null;
        }
    });
};

const updateKrProgress = (kr) => {
    progressForm.current_value = kr.current_value;
    progressForm.put(route('employee.performance.updateKeyResult', kr.id), {
        preserveScroll: true
    });
};
</script>

<template>
    <Head title="My Performance (OKR)" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Performance (OKR)</h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="mb-6 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Period: {{ currentPeriod }}</h3>
                        <p class="text-sm text-gray-500">Manage your Objectives and Key Results</p>
                    </div>
                    <button @click="showObjModal = true" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition flex items-center shadow-sm">
                        <PlusIcon class="w-5 h-5 mr-1" /> New Objective
                    </button>
                </div>

                <div v-if="objectives.length === 0" class="bg-white rounded-lg shadow-sm p-12 text-center border border-gray-200">
                    <p class="text-gray-500 mb-4">You haven't set any objectives for this period yet.</p>
                    <button @click="showObjModal = true" class="text-indigo-600 font-medium hover:underline">Create your first Objective</button>
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
                            <div class="flex justify-between items-center mb-4">
                                <h5 class="font-semibold text-gray-700">Key Results</h5>
                                <button @click="showKrModal = obj.id" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center">
                                    <PlusIcon class="w-4 h-4 mr-1" /> Add Key Result
                                </button>
                            </div>

                            <div v-if="obj.key_results.length === 0" class="text-sm text-gray-400 italic">
                                No key results added yet.
                            </div>

                            <div v-else class="space-y-4">
                                <div v-for="kr in obj.key_results" :key="kr.id" class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-100">
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-800 text-sm mb-1">{{ kr.title }}</p>
                                        
                                        <!-- Progress Bar -->
                                        <div class="w-full bg-gray-200 rounded-full h-2.5 mb-1 mr-4 max-w-md">
                                            <div class="bg-indigo-600 h-2.5 rounded-full" :style="'width: ' + Math.min(100, (kr.current_value / kr.target_value) * 100) + '%'"></div>
                                        </div>
                                        <p class="text-xs text-gray-500">{{ kr.current_value }} / {{ kr.target_value }} ({{ Math.round((kr.current_value / kr.target_value) * 100) }}%)</p>
                                    </div>
                                    <div class="ml-4 flex items-center space-x-2">
                                        <input type="number" v-model="kr.current_value" class="w-20 text-sm border-gray-300 rounded-md py-1" />
                                        <button @click="updateKrProgress(kr)" class="bg-green-100 text-green-700 px-3 py-1 rounded-md text-xs font-bold hover:bg-green-200 transition">Update</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- Add Objective Modal -->
        <div v-if="showObjModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="showObjModal = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form @submit.prevent="submitObj">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">New Objective</h3>
                            
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">Title</label>
                                <input type="text" v-model="objForm.title" required class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                            </div>
                            
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">Weight (%)</label>
                                <input type="number" v-model="objForm.weight" required min="1" max="100" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">Save</button>
                            <button type="button" @click="showObjModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Add KR Modal -->
        <div v-if="showKrModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="showKrModal = null"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form @submit.prevent="submitKr(showKrModal)">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">New Key Result</h3>
                            
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">Metric Title (e.g., Increase sales to X)</label>
                                <input type="text" v-model="krForm.title" required class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                            </div>
                            
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">Target Value</label>
                                <input type="number" v-model="krForm.target_value" step="0.01" required class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">Save</button>
                            <button type="button" @click="showKrModal = null" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </AppLayout>
</template>
