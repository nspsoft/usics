<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import TextArea from '@/Components/TextArea.vue'; // Assuming TextArea component exists, else use textarea HTML
import { formatDate } from '@/helpers';

const props = defineProps({
    ncr: Object,
});

const form = useForm({
    disposition: props.ncr.disposition || '',
    action_plan: props.ncr.action_plan || '',
    approved_by: props.ncr.approved_by || 1, // Defaulting to 1 for demo, ideally auth user
});

const submitDisposition = () => {
    if (confirm('Confirm final disposition? This will close the NCR.')) {
        form.put(route('qc.ncr.update', props.ncr.id));
    }
};
</script>

<template>
    <AppLayout title="NCR Details">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                NCR #{{ ncr.id }} - {{ ncr.status.toUpperCase() }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Defect Details</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm text-gray-500">Source</p>
                            <p class="text-base font-semibold">{{ ncr.inspection?.reference_type?.split('\\').pop() }} #{{ ncr.inspection?.reference_id }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Date Detected</p>
                            <p class="text-base font-semibold">{{ formatDate(ncr.created_at) }}</p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-sm text-gray-500">Defect Description</p>
                            <div class="p-4 bg-red-50 text-red-800 rounded-md border border-red-200 mt-1">
                                {{ ncr.defect_description }}
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="ncr.status === 'open'" class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Disposition & Action Plan</h3>
                    
                    <form @submit.prevent="submitDisposition">
                        <div class="grid grid-cols-1 gap-6">
                            <div>
                                <InputLabel value="Disposition Decision" />
                                <select v-model="form.disposition" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm" required>
                                    <option value="" disabled>Select Action</option>
                                    <option value="rework">Rework (Perbaiki)</option>
                                    <option value="scrap">Scrap (Buang)</option>
                                    <option value="return_to_vendor">Return to Vendor (Retur)</option>
                                    <option value="use_as_is">Use As Is (Terima dengan Catatan)</option>
                                </select>
                            </div>

                            <div>
                                <InputLabel value="Action Plan / Remarks" />
                                <textarea v-model="form.action_plan" rows="4" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm" required></textarea>
                            </div>
                            
                            <!-- Hidden Inspector ID for now -->
                             <input type="hidden" v-model="form.approved_by">

                            <div class="flex justify-end">
                                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                    Close NCR
                                </PrimaryButton>
                            </div>
                        </div>
                    </form>
                </div>

                <div v-else class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Resolution</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm text-gray-500">Disposition</p>
                            <p class="text-base font-semibold capitalize">{{ ncr.disposition.replace('_', ' ') }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Closed By</p>
                            <p class="text-base font-semibold">{{ ncr.approver?.name || 'Unknown' }}</p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-sm text-gray-500">Action Taken</p>
                            <p class="text-base">{{ ncr.action_plan }}</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
