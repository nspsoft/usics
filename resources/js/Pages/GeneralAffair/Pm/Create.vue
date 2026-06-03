<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';
import moment from 'moment';

const props = defineProps({
    assets: Array,
    users: Array,
});

const form = useForm({
    ga_asset_id: '',
    task_name: '',
    description: '',
    interval_days: 30, // Default to 30 days
    next_due_date: moment().add(30, 'days').format('YYYY-MM-DD'),
    assignee_id: '',
    status: 'active',
});

const submit = () => {
    form.post(route('ga.pm-schedules.store'));
};
</script>

<template>
    <Head title="Tambah Jadwal PM (GA)" />

    <AppLayout title="Tambah Jadwal PM">
        <div class="mx-auto max-w-2xl">
            <div class="mb-6 flex items-center gap-4">
                <Link :href="route('ga.pm-schedules.index')" class="rounded-full p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800">
                    <ArrowLeftIcon class="h-5 w-5" />
                </Link>
                <h2 class="text-xl font-black text-slate-900 dark:text-white">Tambah Jadwal Perawatan Baru</h2>
            </div>

            <div class="glass-card rounded-3xl p-6 shadow-sm">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Pilih Aset Fisik <span class="text-red-500">*</span></label>
                        <select v-model="form.ga_asset_id" required class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                            <option value="">-- Pilih Aset --</option>
                            <option v-for="asset in assets" :key="asset.id" :value="asset.id">{{ asset.name }} ({{ asset.asset_code }})</option>
                        </select>
                        <div v-if="form.errors.ga_asset_id" class="mt-1 text-xs text-red-500">{{ form.errors.ga_asset_id }}</div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Nama Tugas / Servis <span class="text-red-500">*</span></label>
                        <input v-model="form.task_name" type="text" required class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white" placeholder="Contoh: Cuci AC Berkala / Ganti Oli Genset" />
                        <div v-if="form.errors.task_name" class="mt-1 text-xs text-red-500">{{ form.errors.task_name }}</div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Deskripsi / Prosedur Servis</label>
                        <textarea v-model="form.description" rows="3" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white" placeholder="Tuliskan detail pekerjaan atau instruksi servis berkala..."></textarea>
                        <div v-if="form.errors.description" class="mt-1 text-xs text-red-500">{{ form.errors.description }}</div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Interval Pengulangan (Hari) <span class="text-red-500">*</span></label>
                            <input v-model="form.interval_days" type="number" min="1" required class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white" />
                            <div v-if="form.errors.interval_days" class="mt-1 text-xs text-red-500">{{ form.errors.interval_days }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Tanggal Jatuh Tempo Pertama <span class="text-red-500">*</span></label>
                            <input v-model="form.next_due_date" type="date" required class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white" />
                            <div v-if="form.errors.next_due_date" class="mt-1 text-xs text-red-500">{{ form.errors.next_due_date }}</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Penanggung Jawab (PIC)</label>
                            <select v-model="form.assignee_id" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                                <option value="">-- Pilih User --</option>
                                <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
                            </select>
                            <div v-if="form.errors.assignee_id" class="mt-1 text-xs text-red-500">{{ form.errors.assignee_id }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Status</label>
                            <select v-model="form.status" required class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                                <option value="active">Active (Jadwal Jalan)</option>
                                <option value="paused">Paused (Ditangguhkan)</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-slate-200 pt-6 dark:border-slate-800">
                        <Link :href="route('ga.pm-schedules.index')" class="rounded-xl border border-slate-300 px-6 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">Batal</Link>
                        <button type="submit" :disabled="form.processing" class="rounded-xl bg-cyan-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-cyan-500 disabled:opacity-50">
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Jadwal' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
