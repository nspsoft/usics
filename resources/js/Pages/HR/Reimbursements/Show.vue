<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ApprovalChain from '@/Components/ApprovalChain.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { defineProps } from 'vue';

const props = defineProps({
    reimbursement: Object
});

const form = useForm({});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value);
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('id-ID');
};

const markAsPaid = () => {
    if (confirm('Are you sure you want to mark this reimbursement as Paid?')) {
        form.post(route('hr.reimbursements.pay', props.reimbursement.id), {
            preserveScroll: true
        });
    }
};
</script>

<template>
    <Head title="Reimbursement Detail" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Manage Reimbursement #{{ reimbursement.reimbursement_number }}
                </h2>
                <Link :href="route('hr.reimbursements.index')" class="text-gray-600 hover:text-gray-900 transition">
                    &larr; Back to List
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 flex flex-col md:flex-row gap-6">
                <!-- Details -->
                <div class="w-full md:w-2/3">
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Reimbursement Information</h3>
                            
                            <PrimaryButton v-if="reimbursement.approval_status === 'approved' && reimbursement.status !== 'paid'" 
                                         @click="markAsPaid" 
                                         class="bg-green-600 hover:bg-green-700">
                                Mark as Paid
                            </PrimaryButton>
                        </div>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-500">Employee</p>
                                <p class="font-medium">{{ reimbursement.employee?.full_name }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Date</p>
                                <p class="font-medium">{{ formatDate(reimbursement.date) }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Expense Type</p>
                                <p class="font-medium">{{ reimbursement.type }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Amount</p>
                                <p class="font-medium text-blue-600 text-lg">{{ formatCurrency(reimbursement.amount) }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Status</p>
                                <p class="font-medium uppercase" :class="{'text-green-600': reimbursement.status === 'paid'}">{{ reimbursement.status }}</p>
                            </div>
                            <div class="col-span-2">
                                <p class="text-sm text-gray-500">Description</p>
                                <p class="font-medium whitespace-pre-wrap">{{ reimbursement.description }}</p>
                            </div>
                            
                            <div class="col-span-2 mt-4" v-if="reimbursement.receipt_path">
                                <p class="text-sm text-gray-500 mb-2">Receipt Document</p>
                                <a :href="'/storage/' + reimbursement.receipt_path" target="_blank" class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200">
                                    View Receipt
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Approval Workflow Sidebar -->
                <div class="w-full md:w-1/3 space-y-6">
                    <ApprovalChain 
                        :approval-request="reimbursement.approval_request"
                        :document-id="reimbursement.id"
                        document-type="App\Models\HR\Reimbursement"
                        document-url="/hr/reimbursements/"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
