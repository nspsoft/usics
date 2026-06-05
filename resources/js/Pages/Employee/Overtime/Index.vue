<script setup>
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { ClockIcon, CalendarIcon, CheckCircleIcon, ExclamationTriangleIcon, PlusIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    overtimes: Array
});

const showRequestModal = ref(false);

const form = useForm({
    type: 'pre_planned',
    date: '',
    start_time: '17:00',
    end_time: '19:00',
    reason: ''
});

const submit = () => {
    form.post(route('employee.overtime.store'), {
        onSuccess: () => {
            showRequestModal.value = false;
            form.reset();
        }
    });
};

const openRequestModal = () => {
    form.reset();
    showRequestModal.value = true;
};

// Summary stats
const stats = computed(() => {
    const pending = props.overtimes.filter(o => o.status === 'pending').length;
    const approvedList = props.overtimes.filter(o => o.status === 'approved');
    const totalApprovedMinutes = approvedList.reduce((acc, curr) => acc + curr.approved_minutes, 0);
    const approvedHours = (totalApprovedMinutes / 60).toFixed(1);

    return {
        total: props.overtimes.length,
        pending,
        approvedHours
    };
});

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
    <Head title="Pengajuan Lembur (Overtime)" />

    <AppLayout title="My Overtime">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-bold text-2xl text-slate-800 dark:text-slate-200 leading-tight">Pengajuan Lembur</h2>
                    <p class="text-xs text-slate-500 mt-1">Kelola dan pantau waktu kerja lembur Anda.</p>
                </div>
                <button @click="openRequestModal" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white shadow-lg hover:bg-indigo-500 transition-all">
                    <PlusIcon class="w-4 h-4" /> Ajukan Lembur
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-100 dark:border-slate-700 shadow-sm flex items-center space-x-4">
                        <div class="p-3 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-xl">
                            <ClockIcon class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Total Jam Disetujui</p>
                            <h3 class="text-2xl font-black text-slate-800 dark:text-white mt-1">{{ stats.approvedHours }} Jam</h3>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-100 dark:border-slate-700 shadow-sm flex items-center space-x-4">
                        <div class="p-3 bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded-xl">
                            <CalendarIcon class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Menunggu Persetujuan</p>
                            <h3 class="text-2xl font-black text-slate-800 dark:text-white mt-1">{{ stats.pending }} Pengajuan</h3>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-100 dark:border-slate-700 shadow-sm flex items-center space-x-4">
                        <div class="p-3 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-xl">
                            <CheckCircleIcon class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Total Pengajuan</p>
                            <h3 class="text-2xl font-black text-slate-800 dark:text-white mt-1">{{ stats.total }} Pengajuan</h3>
                        </div>
                    </div>
                </div>

                <!-- Overtime Table/List -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-700">
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white">Riwayat Pengajuan Lembur</h3>
                        <p class="text-xs text-slate-400 mt-1">Daftar semua pengajuan rencana lembur maupun klaim lembur Anda.</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-700">
                            <thead class="bg-slate-50/50 dark:bg-slate-900/50">
                                <tr>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Tanggal</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Tipe</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Jam Rencana</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Durasi Pengajuan</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Disetujui HR</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Status</th>
                                    <th class="px-6 py-3.5 class-right text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Tugas/Keterangan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700 bg-white dark:bg-slate-800">
                                <tr v-for="ot in overtimes" :key="ot.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-750/50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900 dark:text-white">
                                        {{ formatDate(ot.date) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-xs">
                                        <span v-if="ot.type === 'pre_planned'" class="px-2 py-1 rounded-full font-bold bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                                            Rencana Awal
                                        </span>
                                        <span v-else class="px-2 py-1 rounded-full font-bold bg-purple-50 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                                            Klaim Akhir
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400 font-mono">
                                        {{ ot.start_time }} - {{ ot.end_time }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700 dark:text-slate-300 font-medium">
                                        {{ formatMinutes(ot.requested_minutes) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700 dark:text-slate-300 font-bold">
                                        <span v-if="ot.status === 'approved'">{{ formatMinutes(ot.approved_minutes) }}</span>
                                        <span v-else-if="ot.status === 'rejected'" class="text-red-500">-</span>
                                        <span v-else class="text-slate-400 italic">Menunggu</span>
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
                                    <td class="px-6 py-4 text-sm text-slate-500 dark:text-slate-400 max-w-xs truncate" :title="ot.reason">
                                        <div>{{ ot.reason }}</div>
                                        <div v-if="ot.rejection_reason" class="text-xs text-red-500 mt-1 italic">
                                            Alasan ditolak: {{ ot.rejection_reason }}
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="overtimes.length === 0">
                                    <td colspan="7" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
                                        Belum ada data pengajuan lembur.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <!-- Request Overtime Modal -->
        <Modal :show="showRequestModal" @close="showRequestModal = false" maxWidth="lg">
            <form @submit.prevent="submit" class="p-6">
                <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-2">Formulir Pengajuan Lembur</h3>
                <p class="text-xs text-slate-400 mb-6">Silakan isi detail rencana lembur atau klaim lembur Anda.</p>

                <div class="space-y-5">
                    <!-- Tipe Pengajuan -->
                    <div>
                        <label class="block text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-2">Tipe Lembur</label>
                        <div class="grid grid-cols-2 gap-4">
                            <label class="flex items-center justify-between p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-850/50 transition">
                                <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Rencana Awal (Opsi A)</span>
                                <input type="radio" v-model="form.type" value="pre_planned" class="text-indigo-600 focus:ring-indigo-500 border-slate-300" />
                            </label>
                            <label class="flex items-center justify-between p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-850/50 transition">
                                <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Klaim Akhir (Opsi B)</span>
                                <input type="radio" v-model="form.type" value="post_claim" class="text-indigo-600 focus:ring-indigo-500 border-slate-300" />
                            </label>
                        </div>
                    </div>

                    <!-- Banner Info Tipe -->
                    <div class="p-3.5 rounded-xl border flex items-start space-x-3" 
                         :class="form.type === 'pre_planned' ? 'bg-blue-50/50 border-blue-150 text-blue-800 dark:bg-blue-900/10 dark:border-blue-900/30 dark:text-blue-400' : 'bg-purple-50/50 border-purple-150 text-purple-800 dark:bg-purple-900/10 dark:border-purple-900/30 dark:text-purple-400'">
                        <ExclamationTriangleIcon class="w-5 h-5 shrink-0 mt-0.5" />
                        <div class="text-xs leading-relaxed">
                            <span v-if="form.type === 'pre_planned'">
                                <strong>Rencana Awal:</strong> Ajukan sebelum lembur dikerjakan. Setelah Anda melakukan Clock Out fisik nanti, sistem akan otomatis merekonsiliasi (mengambil nilai terkecil) jam rencana dengan jam kerja aktual Anda.
                            </span>
                            <span v-else>
                                <strong>Klaim Akhir (Unpredictable):</strong> Ajukan setelah selesai lembur. Sistem akan memvalidasi pengajuan ini langsung terhadap jam Clock Out fisik Anda pada tanggal yang Anda klaim.
                            </span>
                        </div>
                    </div>

                    <!-- Tanggal -->
                    <div>
                        <label class="block text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Tanggal Lembur</label>
                        <input type="date" v-model="form.date" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl py-3 px-4 text-sm font-bold text-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition shadow-sm" />
                        <p v-if="form.errors.date" class="text-xs text-red-500 mt-1">{{ form.errors.date }}</p>
                    </div>

                    <!-- Waktu Mulai & Selesai -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Jam Mulai</label>
                            <input type="time" v-model="form.start_time" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl py-3 px-4 text-sm font-mono font-bold text-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition shadow-sm" />
                            <p v-if="form.errors.start_time" class="text-xs text-red-500 mt-1">{{ form.errors.start_time }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Jam Selesai</label>
                            <input type="time" v-model="form.end_time" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl py-3 px-4 text-sm font-mono font-bold text-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition shadow-sm" />
                            <p v-if="form.errors.end_time" class="text-xs text-red-500 mt-1">{{ form.errors.end_time }}</p>
                        </div>
                    </div>

                    <!-- Alasan / Keterangan Tugas -->
                    <div>
                        <label class="block text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Deskripsi Tugas / Alasan</label>
                        <textarea v-model="form.reason" required placeholder="Tuliskan pekerjaan yang akan/telah Anda kerjakan selama lembur..." rows="3" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl py-3 px-4 text-sm font-bold text-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition shadow-sm"></textarea>
                        <p v-if="form.errors.reason" class="text-xs text-red-500 mt-1">{{ form.errors.reason }}</p>
                    </div>
                </div>

                <!-- Footer / Actions -->
                <div class="mt-8 flex justify-end space-x-3">
                    <SecondaryButton @click="showRequestModal = false">Batal</SecondaryButton>
                    <PrimaryButton type="submit" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Kirim Pengajuan
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

    </AppLayout>
</template>
