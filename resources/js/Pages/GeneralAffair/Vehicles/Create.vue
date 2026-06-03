<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';
import moment from 'moment';

const form = useForm({
    purpose: '',
    destination: '',
    start_time: moment().format('YYYY-MM-DDTHH:mm'),
    end_time: moment().add(4, 'hours').format('YYYY-MM-DDTHH:mm'),
    passengers_count: 1,
});

const submit = () => {
    form.post(route('ga.vehicle-bookings.store'));
};
</script>

<template>
    <Head title="Ajukan Peminjaman Kendaraan (GA)" />

    <AppLayout title="Ajukan Peminjaman">
        <div class="mx-auto max-w-2xl">
            <div class="mb-6 flex items-center gap-4">
                <Link :href="route('ga.vehicle-bookings.index')" class="rounded-full p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800">
                    <ArrowLeftIcon class="h-5 w-5" />
                </Link>
                <h2 class="text-xl font-black text-slate-900 dark:text-white">Form Pengajuan Kendaraan</h2>
            </div>

            <div class="glass-card rounded-3xl p-6 shadow-sm">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Tujuan Perjalanan <span class="text-red-500">*</span></label>
                        <input v-model="form.destination" type="text" required class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white" placeholder="Contoh: Kantor Cabang Bandung / Gudang Cikarang" />
                        <div v-if="form.errors.destination" class="mt-1 text-xs text-red-500">{{ form.errors.destination }}</div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Keperluan Penggunaan <span class="text-red-500">*</span></label>
                        <textarea v-model="form.purpose" rows="3" required class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white" placeholder="Jelaskan detail keperluan perjalan dinas..."></textarea>
                        <div v-if="form.errors.purpose" class="mt-1 text-xs text-red-500">{{ form.errors.purpose }}</div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Waktu Mulai <span class="text-red-500">*</span></label>
                            <input v-model="form.start_time" type="datetime-local" required class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white" />
                            <div v-if="form.errors.start_time" class="mt-1 text-xs text-red-500">{{ form.errors.start_time }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Estimasi Waktu Selesai <span class="text-red-500">*</span></label>
                            <input v-model="form.end_time" type="datetime-local" required class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white" />
                            <div v-if="form.errors.end_time" class="mt-1 text-xs text-red-500">{{ form.errors.end_time }}</div>
                        </div>
                    </div>

                    <div class="w-1/2">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Jumlah Penumpang <span class="text-red-500">*</span></label>
                        <input v-model="form.passengers_count" type="number" min="1" required class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white" />
                        <div v-if="form.errors.passengers_count" class="mt-1 text-xs text-red-500">{{ form.errors.passengers_count }}</div>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-slate-200 pt-6 dark:border-slate-800">
                        <Link :href="route('ga.vehicle-bookings.index')" class="rounded-xl border border-slate-300 px-6 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">Batal</Link>
                        <button type="submit" :disabled="form.processing" class="rounded-xl bg-cyan-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-cyan-500 disabled:opacity-50">
                            {{ form.processing ? 'Mengajukan...' : 'Ajukan Peminjaman' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
