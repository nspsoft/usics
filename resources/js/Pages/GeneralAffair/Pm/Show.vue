<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    ArrowLeftIcon, 
    PencilSquareIcon,
    CalendarDaysIcon,
    UserCircleIcon,
    CubeIcon,
    MapPinIcon,
    CurrencyDollarIcon,
    ClockIcon,
    WrenchIcon
} from '@heroicons/vue/24/outline';
import moment from 'moment';

const props = defineProps({
    schedule: Object,
});

const totalMaintenanceCost = computed(() => {
    return props.schedule.pm_logs.reduce((sum, log) => sum + Number(log.cost || 0), 0);
});
</script>

<template>
    <Head :title="`Detail Jadwal PM: ${schedule.task_name}`" />

    <AppLayout title="Detail Jadwal PM">
        <div class="mx-auto max-w-5xl">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="route('ga.pm-schedules.index')" class="rounded-full p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800">
                        <ArrowLeftIcon class="h-5 w-5" />
                    </Link>
                    <div>
                        <h2 class="text-xl font-black text-slate-900 dark:text-white">{{ schedule.task_name }}</h2>
                        <p class="text-sm text-slate-500">Jadwal PM Aset: {{ schedule.ga_asset?.name }} ({{ schedule.ga_asset?.asset_code }})</p>
                    </div>
                </div>
                <Link :href="route('ga.pm-schedules.edit', schedule.id)" class="inline-flex items-center gap-2 rounded-xl bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                    <PencilSquareIcon class="h-4 w-4" />
                    Edit Jadwal
                </Link>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                
                <!-- Left Column: Details -->
                <div class="lg:col-span-1 space-y-6">
                    
                    <!-- PM Details Card -->
                    <div class="glass-card rounded-3xl p-6 shadow-sm">
                        <h3 class="mb-4 text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                            <ClockIcon class="h-5 w-5 text-cyan-600" />
                            Detail Jadwal PM
                        </h3>

                        <dl class="space-y-4 text-sm">
                            <div>
                                <dt class="text-xs text-slate-500 uppercase font-semibold">Status Jadwal</dt>
                                <dd class="mt-1">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold"
                                        :class="{
                                            'bg-emerald-100 text-emerald-800': schedule.status === 'active',
                                            'bg-slate-100 text-slate-800': schedule.status === 'paused',
                                        }"
                                    >
                                        {{ schedule.status.toUpperCase() }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500 uppercase font-semibold">Interval Pengulangan</dt>
                                <dd class="mt-0.5 font-medium text-slate-900 dark:text-white">Tiap {{ schedule.interval_days }} Hari</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500 uppercase font-semibold">Tanggal Jatuh Tempo Berikutnya</dt>
                                <dd class="mt-0.5 font-medium text-slate-900 dark:text-white">{{ moment(schedule.next_due_date).format('DD MMM YYYY') }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500 uppercase font-semibold">Terakhir Kali Servis</dt>
                                <dd class="mt-0.5 font-medium text-slate-900 dark:text-white">
                                    {{ schedule.last_performed_at ? moment(schedule.last_performed_at).format('DD MMM YYYY') : 'Belum Pernah' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500 uppercase font-semibold">Penanggung Jawab (PIC)</dt>
                                <dd class="mt-0.5 font-medium text-slate-900 dark:text-white flex items-center gap-1.5">
                                    <UserCircleIcon class="h-4 w-4 text-slate-400" />
                                    {{ schedule.assignee?.name || 'Belum ditunjuk' }}
                                </dd>
                            </div>
                            <div v-if="schedule.description">
                                <dt class="text-xs text-slate-500 uppercase font-semibold">Deskripsi / Prosedur</dt>
                                <dd class="mt-1 text-slate-700 dark:text-slate-300 bg-slate-50 dark:bg-slate-900/50 p-3 rounded-xl whitespace-pre-wrap">{{ schedule.description }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Linked Asset Info Card -->
                    <div class="glass-card rounded-3xl p-6 shadow-sm">
                        <h3 class="mb-4 text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                            <CubeIcon class="h-5 w-5 text-cyan-600" />
                            Aset Terkait
                        </h3>

                        <dl class="space-y-3 text-sm">
                            <div>
                                <dt class="text-xs text-slate-500">Nama Aset</dt>
                                <dd class="font-bold text-slate-900 dark:text-white">{{ schedule.ga_asset?.name }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500">Kode Aset</dt>
                                <dd class="font-medium text-slate-900 dark:text-white">{{ schedule.ga_asset?.asset_code }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500">Kondisi Aset Saat Ini</dt>
                                <dd class="font-medium text-slate-900 dark:text-white">{{ schedule.ga_asset?.condition }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500">Denah Lokasi Aset</dt>
                                <dd class="font-medium text-slate-900 dark:text-white flex items-center gap-1">
                                    <MapPinIcon class="h-4 w-4 text-slate-400" />
                                    {{ schedule.ga_asset?.ga_location?.name || 'Tidak ada denah' }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Cost Summary Card -->
                    <div class="glass-card rounded-3xl p-6 shadow-sm bg-gradient-to-br from-cyan-500/10 via-transparent to-transparent border-cyan-500/15">
                        <h3 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Biaya Pemeliharaan</h3>
                        <div class="mt-2 flex items-baseline gap-2">
                            <span class="text-2xl font-black text-slate-900 dark:text-white">
                                Rp {{ totalMaintenanceCost.toLocaleString('id-ID') }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">Akumulasi biaya pengerjaan servis berkala.</p>
                    </div>

                </div>

                <!-- Right Column: Logs -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="glass-card rounded-3xl p-6 shadow-sm">
                        <h3 class="mb-4 text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                            <WrenchIcon class="h-5 w-5 text-cyan-600" />
                            Riwayat Realisasi Servis
                        </h3>

                        <!-- Logs Table -->
                        <div class="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                                <thead class="bg-slate-50 dark:bg-slate-900/50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Tanggal</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Teknisi / Perwakilan</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Catatan Pengerjaan</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Biaya</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-900 text-sm">
                                    <tr v-for="log in schedule.pm_logs" :key="log.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                        <td class="px-4 py-3 whitespace-nowrap text-slate-700 dark:text-slate-300">
                                            {{ moment(log.performed_at).format('DD MMM YYYY') }}
                                        </td>
                                        <td class="px-4 py-3 text-slate-900 dark:text-white">
                                            <div>{{ log.technician_name || 'Teknisi Internal' }}</div>
                                            <div class="text-xs text-slate-500">Oleh: {{ log.performed_by?.name }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-slate-700 dark:text-slate-300">
                                            {{ log.notes || '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap font-medium text-slate-900 dark:text-white">
                                            Rp {{ log.cost ? Number(log.cost).toLocaleString('id-ID') : '0' }}
                                        </td>
                                    </tr>
                                    <tr v-if="schedule.pm_logs.length === 0">
                                        <td colspan="4" class="py-8 text-center text-slate-500">
                                            Belum ada catatan realisasi servis berkala.
                                        </td>
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
