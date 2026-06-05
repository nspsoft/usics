<script setup>
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { ClockIcon, UserIcon, CheckCircleIcon, XCircleIcon, ExclamationCircleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    overtimes: Array
});

const showRejectModal = ref(false);
const selectedOvertime = ref(null);

const rejectForm = useForm({
    rejection_reason: ''
});

const confirmReject = (ot) => {
    selectedOvertime.value = ot;
    rejectForm.rejection_reason = '';
    showRejectModal.value = true;
};

const submitReject = () => {
    rejectForm.post(route('hr.overtime.reject', selectedOvertime.value.id), {
        onSuccess: () => {
            showRejectModal.value = false;
            selectedOvertime.value = null;
        }
    });
};

const approve = (ot) => {
    if (confirm(`Setujui pengajuan lembur untuk ${ot.employee.full_name}? Sistem akan otomatis menghitung durasi lembur berdasarkan jam absensi pulang aktual.`)) {
        router.post(route('hr.overtime.approve', ot.id));
    }
};

const formatMinutes = (minutes) => {
    if (minutes >= 60) {
        const hrs = Math.floor(minutes / 60);
        const mins = minutes % 60;
        return mins > 0 ? `${hrs} jam ${mins} menit` : `${hrs} jam`;
    }
    return `${minutes} menit`;
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};
</script>

<template>
    <Head title="Verifikasi Lembur (HR)" />

    <AppLayout title="Overtime Management">
        <template #header>
            <div>
                <h2 class="font-bold text-2xl text-slate-800 dark:text-slate-200 leading-tight">Manajemen Cuti & Lembur</h2>
                <p class="text-xs text-slate-500 mt-1">Verifikasi, hitung ulang, dan setujui jam kerja lembur karyawan.</p>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

                <!-- Alert Information -->
                <div class="bg-indigo-50 border border-indigo-150 rounded-2xl p-4 flex items-start space-x-3 dark:bg-indigo-950/20 dark:border-indigo-900/30 text-indigo-800 dark:text-indigo-400">
                    <ExclamationCircleIcon class="w-5 h-5 shrink-0 mt-0.5" />
                    <div class="text-xs leading-relaxed">
                        <strong>Aturan Rekonsiliasi Kehadiran Otomatis:</strong> Saat Anda menyetujui (*Approve*) pengajuan lembur, sistem secara otomatis akan memvalidasi durasi pengajuan terhadap jam *Clock Out* fisik aktual karyawan hari itu. Menit lembur yang disetujui adalah nilai terkecil dari rencana/klaim karyawan dan jam kehadiran fisiknya di kantor.
                    </div>
                </div>

                <!-- Overtime Requests List -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-700">
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white">Daftar Pengajuan Lembur Karyawan</h3>
                        <p class="text-xs text-slate-400 mt-1">Tinjau rencana lembur awal atau klaim lembur dari karyawan.</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-700">
                            <thead class="bg-slate-50/50 dark:bg-slate-900/50">
                                <tr>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Karyawan</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Tanggal</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Tipe</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Waktu Rencana</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Absensi Fisik</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Lembur Disetujui</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Status</th>
                                    <th class="px-6 py-3.5 text-right text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700 bg-white dark:bg-slate-800">
                                <tr v-for="ot in overtimes" :key="ot.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-750/50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center space-x-3">
                                            <div class="h-10 w-10 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center rounded-xl font-bold">
                                                {{ ot.employee.full_name ? ot.employee.full_name.charAt(0).toUpperCase() : 'E' }}
                                            </div>
                                            <div>
                                                <div class="text-sm font-bold text-slate-900 dark:text-white">{{ ot.employee.full_name }}</div>
                                                <div class="text-xs text-slate-400 mt-0.5">{{ ot.employee.department?.name }} • {{ ot.employee.position?.title }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-800 dark:text-slate-200">
                                        {{ formatDate(ot.date) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-xs">
                                        <span v-if="ot.type === 'pre_planned'" class="px-2 py-0.5 rounded-full font-bold bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                                            Rencana Awal
                                        </span>
                                        <span v-else class="px-2 py-0.5 rounded-full font-bold bg-purple-50 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                                            Klaim Akhir
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-xs">
                                        <div class="font-mono text-slate-800 dark:text-slate-300 font-bold">{{ ot.start_time }} - {{ ot.end_time }}</div>
                                        <div class="text-slate-400 mt-0.5">Durasi: {{ formatMinutes(ot.requested_minutes) }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-xs">
                                        <div v-if="ot.attendance">
                                            <div class="font-mono text-slate-800 dark:text-slate-300 font-bold">
                                                Clock Out: {{ ot.attendance.clock_out || 'Belum Out' }}
                                            </div>
                                            <div class="text-slate-400 mt-0.5">
                                                In: {{ ot.attendance.clock_in || '-' }}
                                            </div>
                                        </div>
                                        <span v-else class="text-red-500 font-bold bg-red-50 dark:bg-red-950/20 px-2 py-0.5 rounded-full">
                                            Tidak Ada Absen
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 dark:text-white font-black">
                                        <span v-if="ot.status === 'approved'" class="text-indigo-600 dark:text-indigo-400">
                                            {{ formatMinutes(ot.approved_minutes) }}
                                        </span>
                                        <span v-else-if="ot.status === 'rejected'" class="text-slate-400 font-medium line-through">
                                            Ditolak
                                        </span>
                                        <span v-else class="text-slate-400 italic font-medium">Belum Diproses</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-xs">
                                        <span v-if="ot.status === 'pending'" class="px-2.5 py-1 text-[11px] font-bold rounded-full text-orange-600 bg-orange-100 dark:bg-orange-900/30 dark:text-orange-400 uppercase tracking-wider">
                                            Pending
                                        </span>
                                        <span v-else-if="ot.status === 'approved'" class="px-2.5 py-1 text-[11px] font-bold rounded-full text-emerald-600 bg-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-400 uppercase tracking-wider">
                                            Approved
                                        </span>
                                        <span v-else class="px-2.5 py-1 text-[11px] font-bold rounded-full text-red-600 bg-red-100 dark:bg-red-900/30 dark:text-red-400 uppercase tracking-wider">
                                            Rejected
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-xs font-bold">
                                        <div v-if="ot.status === 'pending'" class="flex justify-end space-x-2">
                                            <button @click="approve(ot)" class="text-emerald-600 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-900/20 dark:hover:bg-emerald-900/40 px-3 py-1.5 rounded-lg transition-colors">
                                                Approve
                                            </button>
                                            <button @click="confirmReject(ot)" class="text-red-600 bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/40 px-3 py-1.5 rounded-lg transition-colors">
                                                Reject
                                            </button>
                                        </div>
                                        <div v-else class="text-slate-400 italic">
                                            Diproses oleh {{ ot.approver?.name || 'HR' }}
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="overtimes.length === 0">
                                    <td colspan="8" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
                                        Belum ada data pengajuan lembur masuk.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <!-- Reject Modal -->
        <Modal :show="showRejectModal" @close="showRejectModal = false" maxWidth="md">
            <form @submit.prevent="submitReject" class="p-6">
                <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-2">Tolak Pengajuan Lembur</h3>
                <p class="text-xs text-slate-400 mb-6">Silakan isi alasan penolakan lembur untuk karyawan ini.</p>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Alasan Penolakan <span class="text-red-500">*</span></label>
                        <textarea v-model="rejectForm.rejection_reason" required placeholder="Tuliskan alasan penolakan secara mendetail..." rows="3" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl py-3 px-4 text-sm font-bold text-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition shadow-sm"></textarea>
                        <p v-if="rejectForm.errors.rejection_reason" class="text-xs text-red-500 mt-1">{{ rejectForm.errors.rejection_reason }}</p>
                    </div>
                </div>

                <!-- Footer / Actions -->
                <div class="mt-8 flex justify-end space-x-3">
                    <SecondaryButton @click="showRejectModal = false">Batal</SecondaryButton>
                    <DangerButton type="submit" :class="{ 'opacity-25': rejectForm.processing }" :disabled="rejectForm.processing">
                        Tolak Pengajuan
                    </DangerButton>
                </div>
            </form>
        </Modal>

    </AppLayout>
</template>
