<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import { 
    CalendarDaysIcon, 
    PlusIcon, 
    CheckCircleIcon, 
    ClockIcon, 
    XCircleIcon,
    ArrowRightIcon,
    ExclamationTriangleIcon
} from '@heroicons/vue/24/outline';
import moment from 'moment';

const props = defineProps({
    balances: Array,
    leaves: Array,
    attendanceRequests: Array,
    stats: Object,
});

const formatDate = (date) => {
    return moment(date).format('DD MMM YYYY');
};

const formatTime = (time) => {
    if (!time) return '--:--';
    return moment(time, ['HH:mm:ss', 'HH:mm']).format('HH:mm');
};

const getAttendanceTypeLabel = (type) => {
    const map = {
        late_arrival: 'Datang Terlambat',
        early_dismissal: 'Pulang Lebih Awal',
        forgot_clock_in: 'Lupa Absen',
    };
    return map[type] || type;
};

const getAttendanceStatusColor = (status) => {
    switch (status) {
        case 'approved': return 'text-green-600 bg-green-100 dark:bg-green-900/30 dark:text-green-400';
        case 'rejected': return 'text-red-600 bg-red-100 dark:bg-red-900/30 dark:text-red-400';
        default: return 'text-orange-600 bg-orange-100 dark:bg-orange-900/30 dark:text-orange-400';
    }
};

const getStatusColor = (status) => {
    switch (status) {
        case 'approved': return 'text-green-600 bg-green-100 dark:bg-green-900/30 dark:text-green-400';
        case 'rejected': return 'text-red-600 bg-red-100 dark:bg-red-900/30 dark:text-red-400';
        default: return 'text-orange-600 bg-orange-100 dark:bg-orange-900/30 dark:text-orange-400';
    }
};

const getStatusIcon = (status) => {
    switch (status) {
        case 'approved': return CheckCircleIcon;
        case 'rejected': return XCircleIcon;
        default: return ClockIcon;
    }
};

const showAttendanceModal = ref(false);
const attendanceForm = useForm({
    type: 'late_arrival',
    request_date: '',
    request_time: '',
    reason: '',
    attachment: null,
});

const submitAttendance = () => {
    attendanceForm.post(route('my-timeoff.attendance-request.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showAttendanceModal.value = false;
            attendanceForm.reset();
        },
    });
};
</script>

<template>
    <AppLayout title="My Time-Off">
        <template #header>
            <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">
                My Time-Off
            </h2>
        </template>

        <!-- Mobile First Container -->
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 py-6 pb-24">
            
            <!-- Balances Section -->
            <div class="px-4 sm:px-0 mb-6">
                <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Leave Balances ({{ new Date().getFullYear() }})</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div v-for="balance in balances" :key="balance.id" class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="p-2 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg text-indigo-600 dark:text-indigo-400">
                                <CalendarDaysIcon class="w-5 h-5" />
                            </div>
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300 line-clamp-1">{{ balance.leave_type.name }}</span>
                        </div>
                        <div class="mt-3 flex items-end justify-between">
                            <div>
                                <span class="text-2xl font-bold text-slate-900 dark:text-white">{{ balance.total_days - balance.used_days }}</span>
                                <span class="text-xs text-slate-500 ml-1">Days left</span>
                            </div>
                            <div class="text-xs text-slate-400">
                                of {{ balance.total_days }}
                            </div>
                        </div>
                        <!-- Progress bar -->
                        <div class="w-full bg-slate-100 dark:bg-slate-700 rounded-full h-1.5 mt-3 overflow-hidden">
                            <div class="bg-indigo-500 h-1.5 rounded-full" :style="{ width: `${(balance.used_days / balance.total_days) * 100}%` }"></div>
                        </div>
                    </div>
                    
                    <div v-if="balances.length === 0" class="col-span-2 bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-sm border border-slate-100 dark:border-slate-700 text-center">
                        <p class="text-slate-500 text-sm">No leave balances found for this year.</p>
                    </div>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="px-4 sm:px-0 mb-8">
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-1 shadow-sm border border-slate-100 dark:border-slate-700 flex divide-x divide-slate-100 dark:divide-slate-700">
                    <div class="flex-1 p-3 text-center">
                        <div class="text-2xl font-semibold text-orange-500">{{ stats.pending }}</div>
                        <div class="text-xs text-slate-500 mt-1">Pending</div>
                    </div>
                    <div class="flex-1 p-3 text-center">
                        <div class="text-2xl font-semibold text-green-500">{{ stats.approved }}</div>
                        <div class="text-xs text-slate-500 mt-1">Approved</div>
                    </div>
                </div>
            </div>

            <!-- History Section -->
            <div class="px-4 sm:px-0">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Recent Requests</h3>
                </div>
                
                <div class="space-y-3 mb-6">
                    <div v-for="req in (attendanceRequests || []).slice(0, 5)" :key="`att-${req.id}`" class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="font-medium text-slate-900 dark:text-white">{{ getAttendanceTypeLabel(req.type) }}</h4>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    {{ formatDate(req.request_date) }} • {{ formatTime(req.request_time) }}
                                </p>
                            </div>
                            <span :class="['px-2.5 py-1 text-[10px] font-medium rounded-full uppercase tracking-wider', getAttendanceStatusColor(req.status)]">
                                {{ req.status }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-3 line-clamp-2">{{ req.reason }}</p>
                        <p v-if="req.status === 'rejected' && req.rejection_reason" class="text-xs text-red-500 mt-2">
                            {{ req.rejection_reason }}
                        </p>
                    </div>
                </div>

                <div class="space-y-3">
                    <div v-for="leave in leaves" :key="leave.id" class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h4 class="font-medium text-slate-900 dark:text-white">{{ leave.leave_type.name }}</h4>
                                <p class="text-xs text-slate-500 mt-0.5">Applied on {{ formatDate(leave.created_at) }}</p>
                            </div>
                            <span :class="['px-2.5 py-1 text-[10px] font-medium rounded-full flex items-center gap-1 uppercase tracking-wider', getStatusColor(leave.status)]">
                                <component :is="getStatusIcon(leave.status)" class="w-3.5 h-3.5" />
                                {{ leave.status }}
                            </span>
                        </div>
                        
                        <div class="flex items-center gap-3 bg-slate-50 dark:bg-slate-700/50 rounded-xl p-3 mb-3">
                            <div class="flex-1">
                                <p class="text-[10px] text-slate-500 uppercase">From</p>
                                <p class="text-sm font-medium text-slate-800 dark:text-slate-200">{{ formatDate(leave.start_date) }}</p>
                            </div>
                            <ArrowRightIcon class="w-4 h-4 text-slate-400" />
                            <div class="flex-1 text-right">
                                <p class="text-[10px] text-slate-500 uppercase">To</p>
                                <p class="text-sm font-medium text-slate-800 dark:text-slate-200">{{ formatDate(leave.end_date) }}</p>
                            </div>
                        </div>

                        <div class="flex justify-between items-center text-sm">
                            <span class="text-slate-500">{{ leave.total_days }} day(s) requested</span>
                            <span v-if="leave.status === 'rejected'" class="text-xs text-red-500 font-medium truncate max-w-[150px]" :title="leave.rejection_reason">
                                {{ leave.rejection_reason }}
                            </span>
                        </div>
                    </div>

                    <div v-if="leaves.length === 0" class="text-center py-8">
                        <div class="w-16 h-16 bg-slate-50 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-3">
                            <CalendarDaysIcon class="w-8 h-8 text-slate-400" />
                        </div>
                        <p class="text-slate-500 text-sm">You haven't made any leave requests yet.</p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons for Mobile PWA -->
            <div class="fixed bottom-6 right-6 lg:bottom-10 lg:right-10 z-50 flex flex-col items-end gap-3">
                <button @click="showAttendanceModal = true" class="flex items-center justify-center w-12 h-12 bg-orange-500 text-white rounded-full shadow-lg hover:bg-orange-600 hover:scale-105 active:scale-95 transition-all duration-200 focus:outline-none" title="Izin Jam">
                    <ClockIcon class="w-6 h-6" />
                </button>
                <Link :href="route('my-timeoff.create')" class="flex items-center justify-center w-14 h-14 bg-indigo-600 text-white rounded-full shadow-lg hover:bg-indigo-700 hover:scale-105 active:scale-95 transition-all duration-200 focus:outline-none" title="Cuti Baru">
                    <PlusIcon class="w-6 h-6" />
                </Link>
            </div>
            
        </div>

        <!-- Attendance Exception Request Modal -->
        <Modal :show="showAttendanceModal" @close="showAttendanceModal = false" maxWidth="md">
            <div class="p-6">
                <h3 class="text-lg font-medium text-slate-900 dark:text-slate-100 flex items-center gap-2 mb-4">
                    <ExclamationTriangleIcon class="w-5 h-5 text-orange-500" /> Pengajuan Izin Jam
                </h3>

                <form @submit.prevent="submitAttendance" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Tipe Izin</label>
                        <select v-model="attendanceForm.type" class="mt-1 block w-full rounded-md border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="late_arrival">Datang Terlambat</option>
                            <option value="early_dismissal">Pulang Lebih Awal</option>
                            <option value="forgot_clock_in">Lupa Absen</option>
                        </select>
                        <InputError :message="attendanceForm.errors.type" class="mt-1" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Tanggal</label>
                            <input type="date" v-model="attendanceForm.request_date" required class="mt-1 block w-full rounded-md border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <InputError :message="attendanceForm.errors.request_date" class="mt-1" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Jam</label>
                            <input type="time" v-model="attendanceForm.request_time" required class="mt-1 block w-full rounded-md border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <InputError :message="attendanceForm.errors.request_time" class="mt-1" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Alasan Keterlambatan / Pulang Cepat</label>
                        <textarea v-model="attendanceForm.reason" rows="3" required class="mt-1 block w-full rounded-md border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                        <InputError :message="attendanceForm.errors.reason" class="mt-1" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Lampiran Bukti (Opsional)</label>
                        <input type="file" @input="attendanceForm.attachment = $event.target.files[0]" class="mt-1 block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        <InputError :message="attendanceForm.errors.attachment" class="mt-1" />
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="showAttendanceModal = false" class="px-4 py-2 border border-slate-300 rounded-md shadow-sm text-sm font-medium text-slate-700 bg-white hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Batal
                        </button>
                        <button type="submit" :disabled="attendanceForm.processing" class="inline-flex justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Kirim Pengajuan
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

    </AppLayout>
</template>
