<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { formatDate } from '@/helpers';
import debounce from 'lodash/debounce';

const props = defineProps({
    leaves: Object,
    filters: Object,
});

const statusFilter = ref(props.filters.status || '');

// Status Filtering
watch(statusFilter, debounce(function (value) {
    router.get(
        route('hr.leaves.index'),
        { status: value },
        { preserveState: true, replace: true }
    );
}, 300));

// Modals State
const showApproveModal = ref(false);
const showRejectModal = ref(false);
const showImageModal = ref(false);
const selectedLeave = ref(null);

// Forms
const rejectForm = useForm({
    rejection_reason: '',
});

const approveForm = useForm({});

// Modal Controllers
const confirmApprove = (leave) => {
    selectedLeave.value = leave;
    showApproveModal.value = true;
};

const confirmReject = (leave) => {
    selectedLeave.value = leave;
    rejectForm.reset();
    showRejectModal.value = true;
};

const viewImage = (leave) => {
    selectedLeave.value = leave;
    showImageModal.value = true;
};

// Actions
const submitApprove = () => {
    approveForm.post(route('hr.leaves.approve', selectedLeave.value.id), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

const submitReject = () => {
    rejectForm.post(route('hr.leaves.reject', selectedLeave.value.id), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

const closeModal = () => {
    showApproveModal.value = false;
    showRejectModal.value = false;
    showImageModal.value = false;
    setTimeout(() => {
        selectedLeave.value = null;
    }, 300);
};
</script>

<template>
    <AppLayout title="Leave Requests">
        <template #header>
            <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">
                Leave Management
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Filters Component -->
                <div class="mb-6 flex space-x-4">
                    <select v-model="statusFilter" class="border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 dark:text-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>

                <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-2xl border border-slate-100 dark:border-slate-700">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                            <thead class="bg-slate-50 dark:bg-slate-900/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Employee</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Leave Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Dates</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 uppercase tracking-wider">Duration</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-800">
                                <tr v-for="leave in leaves.data" :key="leave.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div>
                                                <div class="text-sm font-medium text-slate-900 dark:text-white">
                                                    {{ leave.employee.first_name }} {{ leave.employee.last_name }}
                                                </div>
                                                <div class="text-[11px] text-slate-500">
                                                    {{ leave.employee.department?.name }} | {{ leave.employee.position?.title }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-slate-900 dark:text-white font-medium">{{ leave.leave_type.name }}</div>
                                        <div v-if="leave.attachment_path" class="text-xs text-indigo-600 dark:text-indigo-400 mt-1 cursor-pointer hover:underline" @click="viewImage(leave)">
                                            View Attachment
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-slate-900 dark:text-slate-300">
                                            {{ formatDate(leave.start_date) }} - {{ formatDate(leave.end_date) }}
                                        </div>
                                        <div class="text-[11px] text-slate-500 mt-1 truncate max-w-[200px]" :title="leave.reason">
                                            {{ leave.reason }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-slate-900 dark:text-slate-300">
                                        <span class="inline-flex items-center justify-center bg-slate-100 dark:bg-slate-700 rounded-xl px-3 py-1 font-bold">
                                            {{ leave.total_days }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span v-if="leave.status === 'pending'" class="px-2.5 py-1 text-[11px] font-medium rounded-full text-orange-600 bg-orange-100 dark:bg-orange-900/30 dark:text-orange-400 uppercase tracking-wider">
                                            Pending
                                        </span>
                                        <span v-else-if="leave.status === 'approved'" class="px-2.5 py-1 text-[11px] font-medium rounded-full text-green-600 bg-green-100 dark:bg-green-900/30 dark:text-green-400 uppercase tracking-wider">
                                            Approved
                                        </span>
                                        <span v-else class="px-2.5 py-1 text-[11px] font-medium rounded-full text-red-600 bg-red-100 dark:bg-red-900/30 dark:text-red-400 uppercase tracking-wider">
                                            Rejected
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div v-if="leave.status === 'pending'" class="flex items-center justify-end space-x-2">
                                            <button @click="confirmApprove(leave)" class="text-green-600 bg-green-50 hover:bg-green-100 dark:bg-green-900/20 dark:hover:bg-green-900/40 px-3 py-1.5 rounded-lg transition-colors">
                                                Approve
                                            </button>
                                            <button @click="confirmReject(leave)" class="text-red-600 bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/40 px-3 py-1.5 rounded-lg transition-colors">
                                                Reject
                                            </button>
                                        </div>
                                        <div v-else class="text-xs text-slate-400">
                                            Handled
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="leaves.data.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                        No leave requests found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <Pagination :links="leaves.links" class="mt-6" />

                <!-- Approve Modal -->
                <Modal :show="showApproveModal" @close="closeModal" maxWidth="sm">
                    <div class="p-6">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">
                            Approve Leave Request
                        </h2>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                            Are you sure you want to approve this {{ selectedLeave?.total_days }}-day(s) leave for {{ selectedLeave?.employee?.first_name }}? The balance will be automatically deducted.
                        </p>
                        <div class="mt-6 flex justify-end space-x-3">
                            <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
                            <PrimaryButton @click="submitApprove" :class="{ 'opacity-25': approveForm.processing }" :disabled="approveForm.processing">
                                Confirm Approval
                            </PrimaryButton>
                        </div>
                    </div>
                </Modal>

                <!-- Reject Modal -->
                <Modal :show="showRejectModal" @close="closeModal" maxWidth="md">
                    <div class="p-6">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white mb-4">
                            Reject Leave Request
                        </h2>
                        <div class="space-y-4">
                            <div>
                                <label class="block font-medium text-sm text-slate-700 dark:text-slate-300">Reason for rejection <span class="text-red-500">*</span></label>
                                <textarea v-model="rejectForm.rejection_reason" class="mt-1 border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full text-sm" rows="3" required></textarea>
                                <p v-if="rejectForm.errors.rejection_reason" class="text-sm text-red-600 mt-2">{{ rejectForm.errors.rejection_reason }}</p>
                            </div>
                        </div>
                        <div class="mt-6 flex justify-end space-x-3">
                            <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
                            <DangerButton @click="submitReject" :class="{ 'opacity-25': rejectForm.processing }" :disabled="rejectForm.processing">
                                Reject Request
                            </DangerButton>
                        </div>
                    </div>
                </Modal>

                <!-- Image / Attachment Modal -->
                <Modal :show="showImageModal" @close="closeModal" maxWidth="2xl">
                    <div class="p-4 bg-slate-900 flex justify-between items-center rounded-t-xl">
                        <h3 class="text-white text-sm font-medium">Attachment Proof</h3>
                        <button @click="closeModal" class="text-slate-400 hover:text-white">&times;</button>
                    </div>
                    <div class="p-4 bg-slate-900 rounded-b-xl flex justify-center">
                        <img v-if="selectedLeave?.attachment_path?.match(/\.(jpeg|jpg|png|gif)$/i)" :src="`/storage/${selectedLeave?.attachment_path}`" class="max-w-full max-h-[70vh] object-contain rounded-lg" />
                        <div v-else class="text-center py-12 text-white">
                            <p class="mb-4">File is not an image.</p>
                            <a :href="`/storage/${selectedLeave?.attachment_path}`" target="_blank" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-500">Download File</a>
                        </div>
                    </div>
                </Modal>

            </div>
        </div>
    </AppLayout>
</template>
