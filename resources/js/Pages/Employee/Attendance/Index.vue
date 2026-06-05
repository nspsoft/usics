<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ClockIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    attendances: Object
});

const formatTime = (dateTime) => {
    if (!dateTime) return '-';
    try {
        const date = new Date(dateTime);
        if (isNaN(date.getTime())) return dateTime;
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return `${hours}:${minutes}`;
    } catch (e) {
        return dateTime;
    }
};
</script>

<template>
    <Head title="My Attendance" />

    <AppLayout title="My Attendance">
        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-4 lg:px-6">
                <div class="mb-4 flex justify-between items-center">
                    <div>
                        <h2 class="font-semibold text-lg text-gray-800 dark:text-slate-100 leading-tight">My Attendance History</h2>
                        <p class="text-xs text-gray-500 dark:text-slate-400">Log of your daily attendance and check-in/out times.</p>
                    </div>
                    <Link :href="route('employee.attendance.clock')" class="bg-indigo-600 text-white px-3.5 py-1.5 rounded-lg hover:bg-indigo-700 transition flex items-center shadow-sm text-sm">
                        <ClockIcon class="w-4 h-4 mr-1" /> Smart Clock
                    </Link>
                </div>
                
                <div class="bg-white dark:bg-slate-900 overflow-hidden shadow-sm sm:rounded-lg border border-gray-150 dark:border-slate-800">
                    <div class="p-4 bg-white dark:bg-slate-900 border-b border-gray-200 dark:border-slate-800">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800">
                                <thead class="bg-gray-50 dark:bg-slate-800/50">
                                    <tr>
                                        <th scope="col" class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Date</th>
                                        <th scope="col" class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Clock In</th>
                                        <th scope="col" class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Clock Out</th>
                                        <th scope="col" class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-800">
                                    <tr v-for="att in attendances.data" :key="att.id" class="hover:bg-gray-50 dark:hover:bg-slate-800/40 transition">
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-slate-100 font-medium">
                                            {{ new Date(att.date).toLocaleDateString('id-ID', { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' }) }}
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-slate-400">
                                            <span v-if="att.clock_in" class="text-green-600 dark:text-green-400 font-medium">{{ formatTime(att.clock_in) }}</span>
                                            <span v-else>-</span>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-slate-400">
                                            <span v-if="att.clock_out" class="text-blue-600 dark:text-blue-400 font-medium">{{ formatTime(att.clock_out) }}</span>
                                            <span v-else>-</span>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap">
                                            <span :class="[
                                                'px-2 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full',
                                                att.status === 'present' ? 'bg-green-100 text-green-800 dark:bg-green-950/40 dark:text-green-400' :
                                                (att.status === 'late' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-950/40 dark:text-yellow-400' : 'bg-red-100 text-red-800 dark:bg-red-950/40 dark:text-red-400')
                                            ]">
                                                {{ att.status }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr v-if="attendances.data.length === 0">
                                        <td colspan="4" class="px-4 py-4 text-center text-sm text-gray-500 dark:text-slate-400">No attendance records found.</td>
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
