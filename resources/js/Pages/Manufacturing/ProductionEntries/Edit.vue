<script setup>
import { ref, watch } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ArrowLeftIcon, CheckCircleIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    entry: Object,
});

const page = usePage();
const flashMessage = ref(null);
const flashType = ref('success');

watch(() => page.props.flash, (flash) => {
    if (flash?.success) {
        flashMessage.value = flash.success;
        flashType.value = 'success';
        setTimeout(() => flashMessage.value = null, 8000);
    } else if (flash?.error) {
        flashMessage.value = flash.error;
        flashType.value = 'error';
        setTimeout(() => flashMessage.value = null, 8000);
    }
}, { deep: true, immediate: true });

const form = useForm({
    start_time: props.entry.start_time ? String(props.entry.start_time).slice(0, 5) : '',
    end_time: props.entry.end_time ? String(props.entry.end_time).slice(0, 5) : '',
    downtime_minutes: props.entry.downtime_minutes ?? 0,
    notes: props.entry.notes ?? '',
});

const submit = () => {
    form.put(route('manufacturing.production-reports.update', props.entry.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Edit Laporan Produksi" />

    <AppLayout title="Edit Laporan Produksi">
        <div class="max-w-3xl px-4 sm:px-6 lg:px-8 mx-auto">
            <div class="mb-4 flex items-center justify-between">
                <Link
                    :href="route('manufacturing.production-reports.index')"
                    class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:text-cyan-500"
                >
                    <ArrowLeftIcon class="h-4 w-4" />
                    Kembali
                </Link>
            </div>

            <div
                v-if="flashMessage"
                class="mb-4 rounded-2xl border px-4 py-3 text-sm flex items-start gap-3"
                :class="flashType === 'success'
                    ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-200'
                    : 'border-red-500/30 bg-red-500/10 text-red-200'"
            >
                <CheckCircleIcon v-if="flashType === 'success'" class="h-5 w-5 mt-0.5" />
                <ExclamationTriangleIcon v-else class="h-5 w-5 mt-0.5" />
                <div>{{ flashMessage }}</div>
            </div>

            <div class="glass-card rounded-2xl p-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <div class="text-xs text-slate-500 dark:text-slate-400">WO</div>
                        <div class="font-semibold text-slate-900 dark:text-white">{{ entry.work_order?.wo_number }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-500 dark:text-slate-400">Product</div>
                        <div class="font-semibold text-slate-900 dark:text-white">{{ entry.work_order?.product?.name }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-500 dark:text-slate-400">Tanggal</div>
                        <div class="font-semibold text-slate-900 dark:text-white">{{ entry.production_date }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-500 dark:text-slate-400">Shift / Operator</div>
                        <div class="font-semibold text-slate-900 dark:text-white">
                            {{ entry.shift }} / {{ entry.operator_employee?.full_name || '-' }}
                        </div>
                    </div>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2">Mulai</label>
                            <input
                                v-model="form.start_time"
                                type="time"
                                class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-3 px-4 text-slate-900 dark:text-white text-center font-mono focus:ring-2 focus:ring-cyan-500/50"
                            />
                            <div v-if="form.errors.start_time" class="text-red-400 text-[11px] mt-1 font-semibold">{{ form.errors.start_time }}</div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2">Selesai</label>
                            <input
                                v-model="form.end_time"
                                type="time"
                                class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-3 px-4 text-slate-900 dark:text-white text-center font-mono focus:ring-2 focus:ring-cyan-500/50"
                            />
                            <div v-if="form.errors.end_time" class="text-red-400 text-[11px] mt-1 font-semibold">{{ form.errors.end_time }}</div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2">Downtime (menit)</label>
                            <input
                                v-model="form.downtime_minutes"
                                type="number"
                                min="0"
                                class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-3 px-4 text-slate-900 dark:text-white text-center font-mono focus:ring-2 focus:ring-cyan-500/50"
                            />
                            <div v-if="form.errors.downtime_minutes" class="text-red-400 text-[11px] mt-1 font-semibold">{{ form.errors.downtime_minutes }}</div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2">Catatan</label>
                        <textarea
                            v-model="form.notes"
                            rows="4"
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-3 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-cyan-500/50"
                        />
                        <div v-if="form.errors.notes" class="text-red-400 text-[11px] mt-1 font-semibold">{{ form.errors.notes }}</div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <Link
                            :href="route('manufacturing.production-reports.index')"
                            class="rounded-xl px-4 py-2 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/50 transition-colors"
                        >
                            Batal
                        </Link>
                        <button
                            type="submit"
                            class="rounded-xl bg-cyan-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-cyan-500 transition-colors disabled:opacity-60"
                            :disabled="form.processing"
                        >
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

