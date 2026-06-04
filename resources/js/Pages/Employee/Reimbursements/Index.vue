<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { defineProps } from 'vue';

const props = defineProps({
    reimbursements: Array
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value);
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('id-ID');
};
</script>

<template>
    <Head title="My Reimbursements" />

    <AppLayout title="My Reimbursements">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="mb-6 flex justify-between items-center">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Reimbursements</h2>
                    <Link :href="route('employee.reimbursements.create')" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition">
                        + New Reimbursement
                    </Link>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3">Number</th>
                                    <th class="px-6 py-3">Date</th>
                                    <th class="px-6 py-3">Type</th>
                                    <th class="px-6 py-3">Amount</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3">Approval</th>
                                    <th class="px-6 py-3 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in reimbursements" :key="item.id" class="bg-white border-b hover:bg-gray-50">
                                    <td class="px-6 py-4 font-medium text-gray-900">{{ item.reimbursement_number }}</td>
                                    <td class="px-6 py-4">{{ formatDate(item.date) }}</td>
                                    <td class="px-6 py-4">{{ item.type }}</td>
                                    <td class="px-6 py-4">{{ formatCurrency(item.amount) }}</td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs rounded-full" 
                                              :class="{
                                                  'bg-yellow-100 text-yellow-800': item.status === 'submitted',
                                                  'bg-green-100 text-green-800': item.status === 'paid',
                                                  'bg-red-100 text-red-800': item.status === 'rejected',
                                                  'bg-blue-100 text-blue-800': item.status === 'approved'
                                              }">
                                            {{ item.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs rounded-full" 
                                              :class="{
                                                  'bg-yellow-100 text-yellow-800': item.approval_status === 'pending',
                                                  'bg-green-100 text-green-800': item.approval_status === 'approved',
                                                  'bg-red-100 text-red-800': item.approval_status === 'rejected'
                                              }">
                                            {{ item.approval_status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <Link :href="route('employee.reimbursements.show', item.id)" class="text-blue-600 hover:underline">
                                            View
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="reimbursements.length === 0">
                                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                        No reimbursements found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
