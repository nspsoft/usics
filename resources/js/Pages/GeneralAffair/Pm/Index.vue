<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    PlusIcon, 
    PencilSquareIcon, 
    TrashIcon,
    MagnifyingGlassIcon,
    CalendarDaysIcon,
    UserIcon,
    WrenchScrewdriverIcon,
    CheckCircleIcon,
    CubeIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';
import debounce from 'lodash/debounce';
import moment from 'moment';

const props = defineProps({
    schedules: Object,
    filters: Object,
    users: Array,
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');

watch([search, status], debounce(() => {
    router.get(
        route('ga.pm-schedules.index'),
        { search: search.value, status: status.value },
        { preserveState: true, preserveScroll: true, replace: true }
    );
}, 300));

const deleteSchedule = (schedule) => {
    Swal.fire({
        title: 'Hapus Jadwal PM?',
        text: `Jadwal PM untuk ${schedule.task_name} akan dihapus permanen.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('ga.pm-schedules.destroy', schedule.id));
        }
    });
};

// State for Record Service Modal
const showModal = ref(false);
const activeSchedule = ref(null);

const logForm = useForm({
    performed_at: moment().format('YYYY-MM-DD'),
    technician_name: '',
    notes: '',
    cost: '',
});

const openRecordModal = (schedule) => {
    activeSchedule.value = schedule;
    logForm.reset();
    logForm.performed_at = moment().format('YYYY-MM-DD');
    showModal.value = true;
};

const closeRecordModal = () => {
    showModal.value = false;
    activeSchedule.value = null;
};

const submitLog = () => {
    if (!activeSchedule.value) return;
    logForm.post(route('ga.pm-schedules.logs.store', activeSchedule.value.id), {
        onSuccess: () => {
            closeRecordModal();
            Swal.fire('Berhasil!', 'Servis berkala berhasil dicatat.', 'success');
        }
    });
};

const getPmStatusLabel = (schedule) => {
    if (schedule.status === 'paused') return 'paused';
    const nextDue = moment(schedule.next_due_date);
    const today = moment().startOf('day');
    if (nextDue.isBefore(today)) return 'overdue';
    if (nextDue.isBefore(today.clone().add(7, 'days'))) return 'due_soon';
    return 'active';
};
</script>

<template>
    <Head title="Preventive Maintenance (GA)" />

    <AppLayout title="Preventive Maintenance">
        <div class="glass-card rounded-3xl p-6 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white">Preventive Maintenance Aset</h2>
                    <p class="text-sm text-slate-500">Jadwal perawatan rutin aset dan fasilitas kantor secara berkala</p>
                </div>
                <div class="flex gap-2">
                    <Link 
                        :href="route('ga.pm-schedules.create')"
                        class="inline-flex items-center gap-2 rounded-xl bg-cyan-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-cyan-500 focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2"
                    >
                        <PlusIcon class="h-4 w-4" />
                        Tambah Jadwal PM
                    </Link>
                </div>
            </div>

            <!-- Filters -->
            <div class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-center">
                <div class="relative max-w-sm flex-1">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <MagnifyingGlassIcon class="h-5 w-5 text-slate-400" />
                    </div>
                    <input 
                        v-model="search" 
                        type="text" 
                        class="block w-full rounded-xl border-slate-300 pl-10 focus:border-cyan-500 focus:ring-cyan-500 sm:text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white" 
                        placeholder="Cari tugas / kode / nama aset..." 
                    />
                </div>
                <select 
                    v-model="status"
                    class="block w-full rounded-xl border-slate-300 focus:border-cyan-500 focus:ring-cyan-500 sm:max-w-xs sm:text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                >
                    <option value="">Semua Status</option>
                    <option value="active">Active (Normal)</option>
                    <option value="overdue">Overdue (Terlewat)</option>
                    <option value="paused">Paused (Ditangguhkan)</option>
                </select>
            </div>

            <!-- Table -->
            <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 shadow-sm dark:border-slate-800">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                    <thead class="bg-slate-50 dark:bg-slate-900/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Aset</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Tugas Perawatan</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Interval</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Tgl Terakhir Servis</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Jatuh Tempo Berikutnya</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-900">
                        <tr v-for="schedule in schedules.data" :key="schedule.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <!-- Asset -->
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 flex-shrink-0 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                                        <CubeIcon class="h-5 w-5 text-slate-400" />
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900 dark:text-white">{{ schedule.ga_asset?.name }}</div>
                                        <div class="text-xs text-slate-500">{{ schedule.ga_asset?.asset_code }}</div>
                                    </div>
                                </div>
                            </td>

                            <!-- Task -->
                            <td class="px-4 py-4 text-sm text-slate-900 dark:text-white">
                                <div class="font-semibold">{{ schedule.task_name }}</div>
                                <div class="text-xs text-slate-500 max-w-xs truncate">{{ schedule.description || '-' }}</div>
                            </td>

                            <!-- Interval -->
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-700 dark:text-slate-300">
                                Tiap {{ schedule.interval_days }} Hari
                            </td>

                            <!-- Last performed -->
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-700 dark:text-slate-300">
                                {{ schedule.last_performed_at ? moment(schedule.last_performed_at).format('DD MMM YYYY') : 'Belum pernah' }}
                            </td>

                            <!-- Next due date -->
                            <td class="px-4 py-4 whitespace-nowrap text-sm">
                                <div class="font-semibold" 
                                    :class="{
                                        'text-red-600 dark:text-red-400': getPmStatusLabel(schedule) === 'overdue',
                                        'text-amber-600 dark:text-amber-400': getPmStatusLabel(schedule) === 'due_soon',
                                        'text-slate-900 dark:text-white': getPmStatusLabel(schedule) === 'active' || getPmStatusLabel(schedule) === 'paused'
                                    }"
                                >
                                    {{ moment(schedule.next_due_date).format('DD MMM YYYY') }}
                                </div>
                                <div class="text-2xs text-slate-500">
                                    PIC: {{ schedule.assignee?.name || '-' }}
                                </div>
                            </td>

                            <!-- Status Badge -->
                            <td class="px-4 py-4 whitespace-nowrap text-sm">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold"
                                    :class="{
                                        'bg-emerald-100 text-emerald-800': getPmStatusLabel(schedule) === 'active',
                                        'bg-amber-100 text-amber-800': getPmStatusLabel(schedule) === 'due_soon',
                                        'bg-red-100 text-red-800': getPmStatusLabel(schedule) === 'overdue',
                                        'bg-slate-100 text-slate-800': getPmStatusLabel(schedule) === 'paused',
                                    }"
                                >
                                    {{ getPmStatusLabel(schedule).toUpperCase().replace('_', ' ') }}
                                </span>
                            </td>

                            <!-- Action -->
                            <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    <button 
                                        v-if="schedule.status === 'active'"
                                        @click="openRecordModal(schedule)" 
                                        class="inline-flex items-center gap-1 rounded bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/20 dark:text-emerald-400"
                                        title="Catat Pengerjaan Servis"
                                    >
                                        <WrenchScrewdriverIcon class="h-3.5 w-3.5" />
                                        Servis
                                    </button>
                                    <Link :href="route('ga.pm-schedules.show', schedule.id)" class="text-cyan-600 hover:text-cyan-900 dark:text-cyan-400 dark:hover:text-cyan-300">Detail</Link>
                                    <Link :href="route('ga.pm-schedules.edit', schedule.id)" class="text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-300"><PencilSquareIcon class="h-5 w-5"/></Link>
                                    <button @click="deleteSchedule(schedule)" class="text-red-500 hover:text-red-700"><TrashIcon class="h-5 w-5"/></button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="schedules.data.length === 0">
                            <td colspan="7" class="py-8 text-center text-sm text-slate-500">
                                Belum ada data jadwal preventive maintenance.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4 flex items-center justify-between" v-if="schedules.links.length > 3">
                <div class="flex flex-1 justify-between sm:hidden">
                    <Link :href="schedules.prev_page_url" class="relative inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Previous</Link>
                    <Link :href="schedules.next_page_url" class="relative ml-3 inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Next</Link>
                </div>
                <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-slate-700 dark:text-slate-300">
                            Showing <span class="font-medium">{{ schedules.from || 0 }}</span> to <span class="font-medium">{{ schedules.to || 0 }}</span> of <span class="font-medium">{{ schedules.total }}</span> results
                        </p>
                    </div>
                    <div>
                        <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                            <Link v-for="(link, i) in schedules.links" :key="i" :href="link.url" v-html="link.label" 
                                class="relative inline-flex items-center px-4 py-2 text-sm font-semibold focus:z-20 border"
                                :class="[
                                    link.active ? 'z-10 bg-cyan-600 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cyan-600 border-cyan-600' : 'text-slate-900 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 dark:text-slate-300 dark:border-slate-700 dark:hover:bg-slate-800',
                                    !link.url ? 'opacity-50 cursor-not-allowed' : ''
                                ]"
                            />
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Record Service Modal Popup -->
        <div v-if="showModal" class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-slate-500/75 dark:bg-slate-950/80 transition-opacity"></div>
            
            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div class="relative transform overflow-hidden rounded-3xl bg-white dark:bg-slate-900 p-6 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-slate-200 dark:border-slate-800">
                        
                        <!-- Modal Close Button -->
                        <div class="absolute right-4 top-4">
                            <button @click="closeRecordModal" class="rounded-full p-1.5 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">
                                <XMarkIcon class="h-5 w-5" />
                            </button>
                        </div>

                        <!-- Modal Header -->
                        <div class="mb-4">
                            <h3 class="text-lg font-black text-slate-900 dark:text-white" id="modal-title">
                                Catat Realisasi Servis Berkala
                            </h3>
                            <p class="text-sm text-slate-500">
                                Jadwal: {{ activeSchedule?.task_name }} - {{ activeSchedule?.ga_asset?.name }}
                            </p>
                        </div>

                        <!-- Modal Content Form -->
                        <form @submit.prevent="submitLog" class="space-y-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Tanggal Pelaksanaan <span class="text-red-500">*</span></label>
                                <input v-model="logForm.performed_at" type="date" required class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white" />
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Nama Teknisi / Vendor Servis</label>
                                <input v-model="logForm.technician_name" type="text" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white" placeholder="Contoh: CV Bintang Aircon / John Doe" />
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Biaya Servis (Rp)</label>
                                <input v-model="logForm.cost" type="number" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white" placeholder="Contoh: 150000" />
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Catatan Servis / Perbaikan</label>
                                <textarea v-model="logForm.notes" rows="3" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white" placeholder="Sebutkan tindakan servis yang dikerjakan..."></textarea>
                            </div>

                            <!-- Actions -->
                            <div class="mt-6 flex justify-end gap-3 border-t border-slate-200 pt-4 dark:border-slate-800">
                                <button type="button" @click="closeRecordModal" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">Batal</button>
                                <button type="submit" :disabled="logForm.processing" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-emerald-500 disabled:opacity-50">
                                    <CheckCircleIcon class="h-4 w-4" />
                                    Simpan & Reschedule
                                </button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
