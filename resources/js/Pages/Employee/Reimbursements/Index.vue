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
        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-4 lg:px-6">
                <div class="mb-4 flex justify-between items-center">
                    <h2 class="font-semibold text-lg text-gray-800 dark:text-slate-100 leading-tight">My Reimbursements</h2>
                    <Link :href="route('employee.reimbursements.create')" class="bg-blue-600 text-white px-3.5 py-1.5 rounded-md hover:bg-blue-700 transition text-sm">
                        + New Reimbursement
                    </Link>
                </div>
                <div class="bg-white dark:bg-slate-900 overflow-hidden shadow-sm sm:rounded-lg border border-gray-150 dark:border-slate-800">
                    <div class="p-4 text-gray-900 dark:text-slate-100 overflow-x-auto">
                        <table class="w-full text-xs text-left text-gray-500 dark:text-slate-400">
                            <thead class="text-xs text-gray-700 dark:text-slate-300 uppercase bg-gray-50 dark:bg-slate-800/50">
                                <tr>
                                    <th class="px-4 py-2.5">Number</th>
                                    <th class="px-4 py-2.5">Date</th>
                                    <th class="px-4 py-2.5">Type</th>
                                    <th class="px-4 py-2.5">Amount</th>
                                    <th class="px-4 py-2.5">Status</th>
                                    <th class="px-4 py-2.5">Approval</th>
                                    <th class="px-4 py-2.5 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in reimbursements" :key="item.id" class="bg-white dark:bg-slate-900 border-b border-gray-150 dark:border-slate-800 hover:bg-gray-50 dark:hover:bg-slate-800/40 transition">
                                    <td class="px-4 py-2.5 font-medium text-gray-900 dark:text-slate-100">{{ item.reimbursement_number }}</td>
                                    <td class="px-4 py-2.5">{{ formatDate(item.date) }}</td>
                                    <td class="px-4 py-2.5">{{ item.type }}</td>
                                    <td class="px-4 py-2.5">{{ formatCurrency(item.amount) }}</td>
                                    <td class="px-4 py-2.5">
                                        <span class="px-2 py-0.5 text-xs rounded-full font-semibold" 
                                              :class="{
                                                  'bg-yellow-100 text-yellow-800 dark:bg-yellow-950/40 dark:text-yellow-400': item.status === 'submitted',
                                                  'bg-green-100 text-green-800 dark:bg-green-950/40 dark:text-green-400': item.status === 'paid',
                                                  'bg-red-100 text-red-800 dark:bg-red-950/40 dark:text-red-400': item.status === 'rejected',
                                                  'bg-blue-100 text-blue-800 dark:bg-blue-950/40 dark:text-blue-400': item.status === 'approved'
                                              }">
                                            {{ item.status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <span class="px-2 py-0.5 text-xs rounded-full font-semibold" 
                                              :class="{
                                                  'bg-yellow-100 text-yellow-800 dark:bg-yellow-950/40 dark:text-yellow-400': item.approval_status === 'pending',
                                                  'bg-green-100 text-green-800 dark:bg-green-950/40 dark:text-green-400': item.approval_status === 'approved',
                                                  'bg-red-100 text-red-800 dark:bg-red-950/40 dark:text-red-400': item.approval_status === 'rejected'
                                              }">
                                            {{ item.approval_status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5 text-right">
                                        <Link :href="route('employee.reimbursements.show', item.id)" class="text-blue-600 dark:text-blue-400 hover:underline">
                                            View
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="reimbursements.length === 0">
                                    <td colspan="7" class="px-4 py-4 text-center text-gray-500 dark:text-slate-400">
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
