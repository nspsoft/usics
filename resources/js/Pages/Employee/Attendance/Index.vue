<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ClockIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    attendances: Object
});
</script>

<template>
    <Head title="My Attendance" />

    <AppLayout title="My Attendance">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="mb-6 flex justify-between items-center">
                    <div>
                        <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Attendance History</h2>
                        <p class="text-sm text-gray-500">Log of your daily attendance and check-in/out times.</p>
                    </div>
                    <Link :href="route('employee.attendance.clock')" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition flex items-center shadow-sm">
                        <ClockIcon class="w-5 h-5 mr-1" /> Smart Clock
                    </Link>
                </div>
                
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Clock In</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Clock Out</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="att in attendances.data" :key="att.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                            {{ new Date(att.date).toLocaleDateString('id-ID', { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' }) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <span v-if="att.clock_in" class="text-green-600 font-medium">{{ att.clock_in }}</span>
                                            <span v-else>-</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <span v-if="att.clock_out" class="text-blue-600 font-medium">{{ att.clock_out }}</span>
                                            <span v-else>-</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="[
                                                'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                                                att.status === 'present' ? 'bg-green-100 text-green-800' :
                                                (att.status === 'late' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800')
                                            ]">
                                                {{ att.status }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr v-if="attendances.data.length === 0">
                                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">No attendance records found.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
