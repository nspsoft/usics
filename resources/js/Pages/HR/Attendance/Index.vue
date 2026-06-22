<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    ClockIcon, 
    CalendarIcon, 
    MagnifyingGlassIcon,
    ArrowRightOnRectangleIcon,
    ArrowLeftOnRectangleIcon,
    MapPinIcon,
    UserCircleIcon,
    FunnelIcon,
    CheckCircleIcon,
    ExclamationCircleIcon,
    ArrowUpTrayIcon,
    DocumentArrowDownIcon,
    XMarkIcon,
    PencilSquareIcon,
    TrashIcon
} from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';

const props = defineProps({
    attendances: Object,
    attendanceRequests: Object,
    departments: Array,
    filters: Object,
});

const activeTab = ref('logs'); // 'logs' or 'requests'

const page = usePage();
const search = ref(props.filters.search);
const date = ref(props.filters.date || new Date().toISOString().split('T')[0]);
const status = ref(props.filters.status);
const showImportModal = ref(false);

const importForm = useForm({
    file: null,
});

watch([search, date, status], debounce(() => {
    router.get(route('hr.attendance.index'), { 
        search: search.value, 
        date: date.value, 
        status: status.value 
    }, { preserveState: true, replace: true });
}, 300));

// Find if current login user has an employee record
const currentEmployee = computed(() => {
    // This assumes we have a way to find current user's employee ID
    // For now, we'll let HR record for anyone or build a simple mock
    return page.props.auth?.employee_id || null;
});

const clockInForm = useForm({
    employee_id: '',
    lat: '',
    lng: '',
});

const performClockIn = (employeeId) => {
    if (confirm('Verify Clock-In at current time?')) {
        clockInForm.employee_id = employeeId;
        // Mocking location for now
        clockInForm.lat = '-6.2088';
        clockInForm.lng = '106.8456';
        
        clockInForm.post(route('hr.attendance.clock-in'), {
            onSuccess: () => clockInForm.reset(),
        });
    }
};

const performClockOut = (attendanceId) => {
    if (confirm('Confirm Clock-Out?')) {
        router.post(route('hr.attendance.clock-out', attendanceId));
    }
};

const handleImport = () => {
    importForm.post(route('hr.attendance.import'), {
        onSuccess: () => {
            showImportModal.value = false;
            importForm.reset();
        },
    });
};

const downloadTemplate = () => {
    window.location.href = route('hr.attendance.template');
};

const formatTime = (dateTime) => {
    if (!dateTime) return '--:--';
    return new Date(dateTime).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
};

const getStatusColor = (status) => {
    const colors = {
        present: 'text-emerald-700 dark:text-emerald-400 bg-emerald-500/10 border-emerald-500/20',
        late: 'text-amber-700 dark:text-amber-400 bg-amber-500/10 border-amber-500/20',
        absent: 'text-red-700 dark:text-red-400 bg-red-500/10 border-red-500/20',
        leave: 'text-indigo-700 dark:text-indigo-400 bg-indigo-500/10 border-indigo-500/20',
    };
    return colors[status] || 'text-slate-700 dark:text-slate-400 bg-slate-500/10 border-slate-500/20';
};

const rejectRequestForm = useForm({
    rejection_reason: ''
});

const approveRequest = (requestId) => {
    if (confirm('Setujui kompensasi izin absensi ini?')) {
        router.post(route('attendance-requests.approve', requestId), {}, { preserveScroll: true });
    }
};

const rejectRequest = (requestId) => {
    const reason = prompt('Masukkan alasan penolakan:');
    if (reason) {
        rejectRequestForm.rejection_reason = reason;
        rejectRequestForm.post(route('attendance-requests.reject', requestId), { preserveScroll: true });
    }
};

// Edit & Delete feature implementation
const can = (permission) => {
    if (!page.props.auth) return false;
    if (page.props.auth.roles?.includes('Super Admin')) return true;
    return page.props.auth.permissions?.includes(permission);
};

const showEditModal = ref(false);
const editingLog = ref(null);

const editForm = useForm({
    date: '',
    clock_in: '',
    clock_out: '',
    status: '',
    note: '',
});

const getTimeString = (dateTime) => {
    if (!dateTime) return '';
    const match = dateTime.match(/(?:T|\s)(\d{2}:\d{2})/);
    if (match) return match[1];
    
    const d = new Date(dateTime);
    if (isNaN(d.getTime())) return '';
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    return `${hours}:${minutes}`;
};

const openEditModal = (log) => {
    editingLog.value = log;
    editForm.date = log.date;
    editForm.clock_in = getTimeString(log.clock_in);
    editForm.clock_out = getTimeString(log.clock_out);
    editForm.status = log.status;
    editForm.note = log.note || '';
    showEditModal.value = true;
};

const submitEditForm = () => {
    editForm.put(route('hr.attendance.update', editingLog.value.id), {
        onSuccess: () => {
            showEditModal.value = false;
            editingLog.value = null;
            editForm.reset();
        },
    });
};

const deleteAttendance = (logId) => {
    if (confirm('Apakah Anda yakin ingin menghapus data absensi ini?')) {
        router.delete(route('hr.attendance.destroy', logId), {
            preserveScroll: true
        });
    }
};
</script>

<template>
    <Head title="Attendance Logs" />
    
    <AppLayout title="HR: Attendance Logs">
        <div class="max-w-full px-4 sm:px-6 lg:px-8 mx-auto">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Time & Attendance</h2>
                    <p class="text-sm text-slate-500 mt-1 uppercase tracking-widest font-bold font-mono">Daily Presence Tracking</p>
                </div>
                
                <div class="flex items-center gap-3">
                    <button 
                        @click="showImportModal = true"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-white dark:bg-slate-900 px-5 py-2.5 text-sm font-bold text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-800 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-800 transition-all hover:-translate-y-0.5"
                    >
                        <ArrowUpTrayIcon class="h-5 w-5" />
                        Import Fingerprint
                    </button>
                    <div class="glass-card rounded-2xl px-4 py-2.5 flex items-center gap-3 shadow-sm border border-white/10">
                        <div class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></div>
                        <span class="text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-widest font-mono">Live Monitoring</span>
                    </div>
                </div>
            </div>

            <!-- Stats & Quick Actions -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <!-- Self Clocking Panel (Demo) -->
                <div class="lg:col-span-2 bg-indigo-50 dark:bg-slate-900/50 dark:bg-gradient-to-br dark:from-indigo-600/20 dark:to-purple-600/10 border border-indigo-100 dark:border-indigo-500/20 rounded-[2.5rem] p-8 relative overflow-hidden group transition-colors">
                    <div class="absolute top-0 right-0 p-8 opacity-5 group-hover:opacity-10 transition-opacity">
                        <ClockIcon class="h-32 w-32 text-indigo-400 dark:text-indigo-400" />
                    </div>
                    
                    <div class="relative z-10">
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Daily Timekeeping</h3>
                        <p class="text-sm text-indigo-700/70 dark:text-indigo-200/70 max-w-md leading-relaxed mb-8">Record your daily presence. Ensure point of entry and exit are captured accurately for payroll processing.</p>
                        
                        <!-- Manual Entry for Demo (In real app, employee_id would be auto-detected) -->
                        <div class="flex flex-col sm:flex-row items-end gap-4 max-w-xl">
                            <div class="flex-1 w-full space-y-2">
                                <label class="text-[10px] font-bold text-indigo-600 dark:text-indigo-300 uppercase tracking-widest ml-1">Select Employee (Self-Service Mock)</label>
                                <select v-model="clockInForm.employee_id" class="w-full bg-white dark:bg-slate-950/50 border border-indigo-200 dark:border-indigo-500/30 rounded-2xl py-3 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50 transition-all">
                                    <option value="">Choose profile...</option>
                                    <!-- In production this list would be limited or auto-selected -->
                                    <option value="1">Ahmad Subkont (Sample)</option>
                                </select>
                            </div>
                            <button 
                                @click="performClockIn(clockInForm.employee_id)"
                                :disabled="!clockInForm.employee_id || clockInForm.processing"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-8 py-3.5 text-sm font-bold text-white shadow-xl shadow-indigo-600/20 dark:shadow-indigo-900/40 hover:bg-indigo-500 disabled:opacity-50 transition-all hover:-translate-y-1"
                            >
                                <ArrowRightOnRectangleIcon class="h-5 w-5" />
                                Record Clock-In
                            </button>
                        </div>
                    </div>
                </div>

                <div class="glass-card rounded-[2.5rem] p-8">
                    <div class="flex items-center justify-between mb-6">
                        <h4 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-widest">Today's Summary</h4>
                        <CalendarIcon class="h-5 w-5 text-slate-500" />
                    </div>
                    
                    <div class="space-y-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                    <CheckCircleIcon class="h-5 w-5" />
                                </div>
                                <span class="text-sm font-bold text-slate-700 dark:text-slate-300">Present</span>
                            </div>
                            <span class="text-xl font-bold text-slate-900 dark:text-white font-mono">--</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400">
                                    <ExclamationCircleIcon class="h-5 w-5" />
                                </div>
                                <span class="text-sm font-bold text-slate-700 dark:text-slate-300">Late</span>
                            </div>
                            <span class="text-xl font-bold text-slate-900 dark:text-white font-mono">--</span>
                        </div>
                        <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex justify-between items-center">
                            <span class="text-xs text-slate-500 font-bold uppercase tracking-wider">Total Headcount</span>
                            <span class="text-sm font-bold text-slate-500 dark:text-slate-400">--</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Management Table -->
            <div class="glass-card rounded-[2.5rem] overflow-hidden shadow-xl">
                <div class="p-8 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950/50 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="flex items-center gap-6">
                        <div class="flex items-center gap-3">
                            <FunnelIcon class="h-5 w-5 text-indigo-400" />
                            <span class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-widest">Management</span>
                        </div>
                        <div class="flex bg-slate-100 dark:bg-slate-900 rounded-xl p-1">
                            <button @click="activeTab = 'logs'" :class="['px-4 py-2 rounded-lg text-xs font-bold transition-all', activeTab === 'logs' ? 'bg-white dark:bg-slate-800 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300']">Daily Logs</button>
                            <button @click="activeTab = 'requests'" :class="['px-4 py-2 rounded-lg text-xs font-bold transition-all', activeTab === 'requests' ? 'bg-white dark:bg-slate-800 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300']">Exception Requests</button>
                        </div>
                    </div>

                    <div v-show="activeTab === 'logs'" class="flex flex-wrap items-center gap-4">
                        <div class="relative w-full md:w-64">
                            <MagnifyingGlassIcon class="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-500" />
                            <input v-model="search" type="text" placeholder="Search employee..." class="w-full bg-white dark:bg-slate-900 border-0 rounded-xl py-2.5 pl-10 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50" />
                        </div>
                        <input v-model="date" type="date" class="bg-white dark:bg-slate-900 border-0 rounded-xl py-2.5 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50" />
                        <select v-model="status" class="bg-white dark:bg-slate-900 border-0 rounded-xl py-2.5 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50">
                            <option value="">All Status</option>
                            <option value="present">Present</option>
                            <option value="late">Late</option>
                            <option value="absent">Absent</option>
                            <option value="leave">On Leave</option>
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto overflow-y-auto max-h-[600px]">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-950/30">
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-8 py-4 text-[10px] font-bold text-slate-900 dark:text-slate-400 uppercase tracking-widest border-b border-slate-200 dark:border-slate-800">Employee</th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-8 py-4 text-[10px] font-bold text-slate-900 dark:text-slate-400 uppercase tracking-widest border-b border-slate-200 dark:border-slate-800">Department</th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-8 py-4 text-[10px] font-bold text-slate-900 dark:text-slate-400 uppercase tracking-widest border-b border-slate-200 dark:border-slate-800">Clock In</th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-8 py-4 text-[10px] font-bold text-slate-900 dark:text-slate-400 uppercase tracking-widest border-b border-slate-200 dark:border-slate-800">Clock Out</th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-8 py-4 text-[10px] font-bold text-slate-900 dark:text-slate-400 uppercase tracking-widest border-b border-slate-200 dark:border-slate-800">Status</th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-8 py-4 text-[10px] font-bold text-slate-900 dark:text-slate-400 uppercase tracking-widest border-b border-slate-200 dark:border-slate-800 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="log in attendances.data" :key="log.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors group">
                                <td class="px-8 py-5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-xs font-bold text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                            {{ log.employee.full_name.charAt(0) }}
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold text-slate-900 dark:text-white">{{ log.employee.full_name }}</div>
                                            <div class="text-[10px] text-slate-500 font-mono font-bold">{{ log.employee.nik }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-5">
                                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ log.employee.department?.name }}</span>
                                </td>
                                <td class="px-8 py-5">
                                    <div class="flex items-center gap-2">
                                        <ClockIcon class="h-4 w-4 text-emerald-500/50" />
                                        <span class="text-sm font-mono font-bold text-emerald-400">{{ formatTime(log.clock_in) }}</span>
                                    </div>
                                </td>
                                <td class="px-8 py-5">
                                    <div class="flex items-center gap-2" :class="log.clock_out ? '' : 'opacity-30'">
                                        <ClockIcon class="h-4 w-4 text-blue-500/50" />
                                        <span class="text-sm font-mono font-bold text-blue-400">{{ formatTime(log.clock_out) }}</span>
                                    </div>
                                </td>
                                <td class="px-8 py-5">
                                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider border shadow-sm" :class="getStatusColor(log.status)">
                                        {{ log.status }}
                                    </span>
                                </td>
                                <td class="px-8 py-5 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button 
                                            v-if="!log.clock_out"
                                            @click="performClockOut(log.id)"
                                            class="p-2 text-slate-500 dark:text-slate-400 hover:text-blue-400 bg-slate-50 dark:bg-slate-800 hover:bg-blue-400/10 rounded-xl transition-all border border-slate-200 dark:border-slate-700 hover:border-blue-500/30"
                                            title="Force Clock Out"
                                        >
                                            <ArrowLeftOnRectangleIcon class="h-5 w-5" />
                                        </button>
                                        <button 
                                            v-if="can('hr_payroll.attendance.edit')"
                                            @click="openEditModal(log)"
                                            class="p-2 text-slate-500 dark:text-slate-400 hover:text-indigo-400 bg-slate-50 dark:bg-slate-800 hover:bg-indigo-400/10 rounded-xl transition-all border border-slate-200 dark:border-slate-700 hover:border-indigo-500/30"
                                            title="Edit Attendance"
                                        >
                                            <PencilSquareIcon class="h-5 w-5" />
                                        </button>
                                        <button 
                                            v-if="can('hr_payroll.attendance.delete')"
                                            @click="deleteAttendance(log.id)"
                                            class="p-2 text-slate-500 dark:text-slate-400 hover:text-red-400 bg-slate-50 dark:bg-slate-800 hover:bg-red-400/10 rounded-xl transition-all border border-slate-200 dark:border-slate-700 hover:border-red-500/30"
                                            title="Delete Attendance"
                                        >
                                            <TrashIcon class="h-5 w-5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!attendances.data.length">
                                <td colspan="6" class="px-8 py-16 text-center">
                                    <div class="text-slate-500 text-sm italic">No attendance records found for selected criteria.</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Exception Requests Table -->
                <div v-show="activeTab === 'requests'" class="overflow-x-auto overflow-y-auto max-h-[600px]">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-950/30">
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-8 py-4 text-[10px] font-bold text-slate-900 dark:text-slate-400 uppercase tracking-widest border-b border-slate-200 dark:border-slate-800">Employee</th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-8 py-4 text-[10px] font-bold text-slate-900 dark:text-slate-400 uppercase tracking-widest border-b border-slate-200 dark:border-slate-800">Date & Type</th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-8 py-4 text-[10px] font-bold text-slate-900 dark:text-slate-400 uppercase tracking-widest border-b border-slate-200 dark:border-slate-800">Reason</th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-8 py-4 text-[10px] font-bold text-slate-900 dark:text-slate-400 uppercase tracking-widest border-b border-slate-200 dark:border-slate-800 text-center">Status</th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-8 py-4 text-[10px] font-bold text-slate-900 dark:text-slate-400 uppercase tracking-widest border-b border-slate-200 dark:border-slate-800 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="req in attendanceRequests.data" :key="req.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors">
                                <td class="px-8 py-5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-orange-50 dark:bg-orange-900/20 flex items-center justify-center text-orange-500 border border-orange-200 dark:border-orange-800/30">
                                            <ExclamationCircleIcon class="w-5 h-5" />
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold text-slate-900 dark:text-white">{{ req.employee?.full_name }}</div>
                                            <div class="text-[10px] text-slate-500 font-mono font-bold">{{ req.employee?.department?.name }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-5">
                                    <div class="text-sm font-bold text-slate-800 dark:text-slate-200">{{ req.request_date }} ({{ req.request_time.substring(0,5) }})</div>
                                    <div class="text-[10px] uppercase tracking-wider text-slate-500 mt-0.5">{{ req.type.replace('_', ' ') }}</div>
                                </td>
                                <td class="px-8 py-5">
                                    <div class="text-xs text-slate-600 dark:text-slate-400 max-w-xs truncate" :title="req.reason">{{ req.reason }}</div>
                                    <a v-if="req.attachment_path" :href="`/storage/${req.attachment_path}`" target="_blank" class="text-[10px] text-indigo-500 hover:underline mt-1 inline-block">View Attachment</a>
                                </td>
                                <td class="px-8 py-5 text-center">
                                    <span :class="['px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider', 
                                        req.status === 'approved' ? 'text-green-700 bg-green-100 dark:bg-green-900/30 dark:text-green-400' : 
                                        req.status === 'rejected' ? 'text-red-700 bg-red-100 dark:bg-red-900/30 dark:text-red-400' : 
                                        'text-orange-700 bg-orange-100 dark:bg-orange-900/30 dark:text-orange-400']">
                                        {{ req.status }}
                                    </span>
                                </td>
                                <td class="px-8 py-5 text-right space-x-2">
                                    <template v-if="req.status === 'pending'">
                                        <button @click="approveRequest(req.id)" class="px-3 py-1.5 text-xs font-medium text-white bg-green-600 hover:bg-green-500 rounded-lg transition-colors">Approve</button>
                                        <button @click="rejectRequest(req.id)" class="px-3 py-1.5 text-xs font-medium text-white bg-red-600 hover:bg-red-500 rounded-lg transition-colors">Reject</button>
                                    </template>
                                </td>
                            </tr>
                            <tr v-if="!attendanceRequests.data.length">
                                <td colspan="5" class="px-8 py-16 text-center">
                                    <div class="text-slate-500 text-sm italic">No exception requests found.</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Footer / Pagination for Logs -->
                <div v-show="activeTab === 'logs'" class="px-8 py-6 bg-white dark:bg-slate-950/20 border-t border-slate-200 dark:border-slate-800 flex justify-center">
                    <nav class="flex gap-1">
                        <Link
                            v-for="(link, i) in attendances.links"
                            :key="i"
                            :href="link.url || '#'"
                            class="px-4 py-2 rounded-xl text-sm font-bold transition-all"
                            :class="[
                                link.active ? 'bg-indigo-600 text-slate-900 dark:text-white shadow-lg shadow-indigo-500/20' : 'text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 hover:text-slate-900 dark:text-white',
                                !link.url ? 'opacity-50 cursor-not-allowed' : ''
                            ]"
                            v-html="link.label"
                        />
                    </nav>
                </div>
                <!-- Pagination for Requests -->
                <div v-show="activeTab === 'requests'" class="px-8 py-6 bg-white dark:bg-slate-950/20 border-t border-slate-200 dark:border-slate-800 flex justify-center">
                    <nav class="flex gap-1">
                        <Link
                            v-for="(link, i) in attendanceRequests.links"
                            :key="i"
                            :href="link.url || '#'"
                            class="px-4 py-2 rounded-xl text-sm font-bold transition-all"
                            :class="[
                                link.active ? 'bg-indigo-600 text-slate-900 dark:text-white shadow-lg shadow-indigo-500/20' : 'text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 hover:text-slate-900 dark:text-white',
                                !link.url ? 'opacity-50 cursor-not-allowed' : ''
                            ]"
                            v-html="link.label"
                        />
                    </nav>
                </div>
            </div>
        </div>
        <!-- Import Modal -->
        <TransitionRoot as="template" :show="showImportModal">
            <Dialog as="div" class="relative z-[100]" @close="showImportModal = false">
                <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                    <div class="fixed inset-0 bg-white dark:bg-slate-950/80 backdrop-blur-sm transition-opacity" />
                </TransitionChild>

                <div class="fixed inset-0 z-10 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                        <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                            <DialogPanel class="relative transform overflow-hidden rounded-[2rem] glass-card text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md">
                                <form @submit.prevent="handleImport">
                                    <div class="px-8 py-6 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center bg-white dark:bg-slate-950/50">
                                        <DialogTitle as="h3" class="text-xl font-bold text-slate-900 dark:text-white">
                                            Import Fingerprint Data
                                        </DialogTitle>
                                        <button @click="showImportModal = false" type="button" class="text-slate-500 hover:text-slate-900 dark:text-white transition-colors">
                                            <XMarkIcon class="h-7 w-7" />
                                        </button>
                                    </div>

                                    <div class="p-8 space-y-6">
                                        <div class="p-4 rounded-2xl bg-indigo-500/5 border border-indigo-500/10 space-y-3">
                                            <h4 class="text-xs font-bold text-indigo-400 uppercase tracking-widest flex items-center gap-2">
                                                <DocumentArrowDownIcon class="h-4 w-4" />
                                                Instructions
                                            </h4>
                                            <p class="text-xs text-slate-500 leading-relaxed italic">
                                                Please list NIK, Date, Clock In, and Clock Out in your Excel file. The system will automatically detect late status (> 08:30).
                                            </p>
                                            <button 
                                                type="button"
                                                @click="downloadTemplate"
                                                class="text-xs font-bold text-indigo-500 hover:text-indigo-400 underline underline-offset-4"
                                            >
                                                Download Fingerprint Template
                                            </button>
                                        </div>

                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Select Attendance File</label>
                                            <input 
                                                type="file" 
                                                @input="importForm.file = $event.target.files[0]"
                                                class="block w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:bg-indigo-600 file:text-white hover:file:bg-indigo-500 transition-all cursor-pointer" 
                                                accept=".xlsx, .xls, .csv"
                                            />
                                            <p v-if="importForm.errors.file" class="text-[10px] text-red-500 italic">{{ importForm.errors.file }}</p>
                                        </div>
                                    </div>

                                    <div class="px-8 py-6 bg-white dark:bg-slate-950 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-4">
                                        <button @click="showImportModal = false" type="button" class="px-6 py-2.5 text-sm font-medium text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white transition-colors">Cancel</button>
                                        <button 
                                            type="submit" 
                                            :disabled="importForm.processing || !importForm.file"
                                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-8 py-2.5 text-sm font-bold text-white shadow-lg shadow-indigo-900/20 hover:bg-indigo-500 disabled:opacity-50 transition-all"
                                        >
                                            Start Import
                                        </button>
                                    </div>
                                </form>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>

        <!-- Edit Attendance Modal -->
        <TransitionRoot as="template" :show="showEditModal">
            <Dialog as="div" class="relative z-[100]" @close="showEditModal = false">
                <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                    <div class="fixed inset-0 bg-white dark:bg-slate-950/80 backdrop-blur-sm transition-opacity" />
                </TransitionChild>

                <div class="fixed inset-0 z-10 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                        <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                            <DialogPanel class="relative transform overflow-hidden rounded-[2rem] glass-card text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md">
                                <form @submit.prevent="submitEditForm">
                                    <div class="px-8 py-6 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center bg-white dark:bg-slate-950/50">
                                        <DialogTitle as="h3" class="text-xl font-bold text-slate-900 dark:text-white">
                                            Edit Attendance Log
                                        </DialogTitle>
                                        <button @click="showEditModal = false" type="button" class="text-slate-500 hover:text-slate-900 dark:text-white transition-colors">
                                            <XMarkIcon class="h-7 w-7" />
                                        </button>
                                    </div>

                                    <div class="p-8 space-y-4">
                                        <div v-if="editingLog" class="p-4 rounded-2xl bg-indigo-500/5 border border-indigo-500/10 space-y-1">
                                            <div class="text-[10px] text-slate-500 uppercase tracking-widest font-bold">Employee</div>
                                            <div class="text-sm font-bold text-slate-900 dark:text-white">{{ editingLog.employee?.full_name }}</div>
                                            <div class="text-xs text-slate-500 font-mono">{{ editingLog.employee?.nik }}</div>
                                        </div>

                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Date</label>
                                            <input 
                                                type="date" 
                                                v-model="editForm.date"
                                                class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50" 
                                                required
                                            />
                                            <p v-if="editForm.errors.date" class="text-[10px] text-red-500 italic">{{ editForm.errors.date }}</p>
                                        </div>

                                        <div class="grid grid-cols-2 gap-4">
                                            <div class="space-y-2">
                                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Clock In</label>
                                                <input 
                                                    type="time" 
                                                    v-model="editForm.clock_in"
                                                    class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50" 
                                                />
                                                <p v-if="editForm.errors.clock_in" class="text-[10px] text-red-500 italic">{{ editForm.errors.clock_in }}</p>
                                            </div>
                                            <div class="space-y-2">
                                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Clock Out</label>
                                                <input 
                                                    type="time" 
                                                    v-model="editForm.clock_out"
                                                    class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50" 
                                                />
                                                <p v-if="editForm.errors.clock_out" class="text-[10px] text-red-500 italic">{{ editForm.errors.clock_out }}</p>
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Status</label>
                                            <select 
                                                v-model="editForm.status"
                                                class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50"
                                                required
                                            >
                                                <option value="present">Present</option>
                                                <option value="late">Late</option>
                                                <option value="absent">Absent</option>
                                                <option value="leave">On Leave</option>
                                                <option value="sick">Sick</option>
                                                <option value="overtime">Overtime</option>
                                            </select>
                                            <p v-if="editForm.errors.status" class="text-[10px] text-red-500 italic">{{ editForm.errors.status }}</p>
                                        </div>

                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Note / Keterangan</label>
                                            <textarea 
                                                v-model="editForm.note"
                                                rows="3"
                                                class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50"
                                                placeholder="Keterangan perubahan..."
                                            ></textarea>
                                            <p v-if="editForm.errors.note" class="text-[10px] text-red-500 italic">{{ editForm.errors.note }}</p>
                                        </div>
                                    </div>

                                    <div class="px-8 py-6 bg-white dark:bg-slate-950 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-4">
                                        <button @click="showEditModal = false" type="button" class="px-6 py-2.5 text-sm font-medium text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white transition-colors">Cancel</button>
                                        <button 
                                            type="submit" 
                                            :disabled="editForm.processing"
                                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-8 py-2.5 text-sm font-bold text-white shadow-lg shadow-indigo-900/20 hover:bg-indigo-500 disabled:opacity-50 transition-all"
                                        >
                                            Save Changes
                                        </button>
                                    </div>
                                </form>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>
    </AppLayout>
</template>

<style scoped>
/* Optional: Style the scrollbar for the table if needed */
</style>



