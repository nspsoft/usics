<script setup>
import { onMounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ArrowLeftIcon,
    LifebuoyIcon,
    PaperClipIcon,
    BugAntIcon,
    WrenchIcon,
    SparklesIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    referralUrl: String,
});

const form = useForm({
    title: '',
    category: 'bug',
    priority: 'medium',
    description: '',
    url: props.referralUrl || '',
    attachment: null,
});

onMounted(() => {
    if (!form.url && typeof window !== 'undefined') {
        form.url = document.referrer || window.location.href;
    }
});

const handleFileChange = (e) => {
    form.attachment = e.target.files[0];
};

const submit = () => {
    form.post(route('helpdesk.store'), {
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="Buat Tiket Helpdesk" />

    <AppLayout title="Buat Tiket Helpdesk">
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link
                    :href="route('helpdesk.index')"
                    class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition-colors"
                >
                    <ArrowLeftIcon class="h-5 w-5" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <LifebuoyIcon class="h-7 w-7 text-blue-500" />
                        Buat Tiket Helpdesk Baru
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Sampaikan bug, kendala sistem, revisi, atau ide fitur baru kepada tim pengembang.
                    </p>
                </div>
            </div>

            <!-- Form Card -->
            <form @submit.prevent="submit" class="rounded-2xl glass-card p-6 sm:p-8 space-y-6">
                <!-- Category Selection Cards -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3">
                        Pilih Jenis Tiket <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <label
                            class="relative flex flex-col p-4 rounded-xl border-2 cursor-pointer transition-all"
                            :class="form.category === 'bug' ? 'border-rose-500 bg-rose-500/5 dark:bg-rose-500/10' : 'border-slate-200 dark:border-slate-800 hover:border-slate-300'"
                        >
                            <input type="radio" v-model="form.category" value="bug" class="sr-only" />
                            <div class="flex items-center justify-between mb-2">
                                <span class="p-2 rounded-lg bg-rose-500/10 text-rose-500">
                                    <BugAntIcon class="h-6 w-6" />
                                </span>
                                <span v-if="form.category === 'bug'" class="h-2.5 w-2.5 rounded-full bg-rose-500"></span>
                            </div>
                            <span class="font-bold text-slate-900 dark:text-white text-base">🐛 Bug / Error</span>
                            <span class="text-xs text-slate-500 dark:text-slate-400 mt-1">Sistem error, tombol tidak merespon, atau hasil kalkulasi salah.</span>
                        </label>

                        <label
                            class="relative flex flex-col p-4 rounded-xl border-2 cursor-pointer transition-all"
                            :class="form.category === 'revision' ? 'border-amber-500 bg-amber-500/5 dark:bg-amber-500/10' : 'border-slate-200 dark:border-slate-800 hover:border-slate-300'"
                        >
                            <input type="radio" v-model="form.category" value="revision" class="sr-only" />
                            <div class="flex items-center justify-between mb-2">
                                <span class="p-2 rounded-lg bg-amber-500/10 text-amber-500">
                                    <WrenchIcon class="h-6 w-6" />
                                </span>
                                <span v-if="form.category === 'revision'" class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                            </div>
                            <span class="font-bold text-slate-900 dark:text-white text-base">✏️ Revisi Fitur</span>
                            <span class="text-xs text-slate-500 dark:text-slate-400 mt-1">Penyesuaian kecil alur, penambahan kolom, atau ubah tampilan.</span>
                        </label>

                        <label
                            class="relative flex flex-col p-4 rounded-xl border-2 cursor-pointer transition-all"
                            :class="form.category === 'feature_request' ? 'border-purple-500 bg-purple-500/5 dark:bg-purple-500/10' : 'border-slate-200 dark:border-slate-800 hover:border-slate-300'"
                        >
                            <input type="radio" v-model="form.category" value="feature_request" class="sr-only" />
                            <div class="flex items-center justify-between mb-2">
                                <span class="p-2 rounded-lg bg-purple-500/10 text-purple-500">
                                    <SparklesIcon class="h-6 w-6" />
                                </span>
                                <span v-if="form.category === 'feature_request'" class="h-2.5 w-2.5 rounded-full bg-purple-500"></span>
                            </div>
                            <span class="font-bold text-slate-900 dark:text-white text-base">🚀 Request Fitur</span>
                            <span class="text-xs text-slate-500 dark:text-slate-400 mt-1">Usulan modul baru atau otomasi proses bisnis baru.</span>
                        </label>
                    </div>
                </div>

                <!-- Title & Priority -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-3">
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                            Judul Singkat Tiket <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="form.title"
                            type="text"
                            placeholder="Contoh: Tombol simpan DO tidak merespon di browser Chrome"
                            class="w-full rounded-xl border-0 bg-slate-100 dark:bg-slate-800/80 py-3 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                            required
                        />
                        <p v-if="form.errors.title" class="text-xs text-rose-500 mt-1">{{ form.errors.title }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                            Tingkat Urgensi <span class="text-rose-500">*</span>
                        </label>
                        <select
                            v-model="form.priority"
                            class="w-full rounded-xl border-0 bg-slate-100 dark:bg-slate-800/80 py-3 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                        >
                            <option value="low">Low (Santai)</option>
                            <option value="medium">Medium (Normal)</option>
                            <option value="high">High (Penting)</option>
                            <option value="critical">Critical (Sistem Mati)</option>
                        </select>
                    </div>
                </div>

                <!-- Page URL (Auto-Captured) -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                        URL / Halaman Terkait (Auto-Captured)
                    </label>
                    <input
                        v-model="form.url"
                        type="text"
                        placeholder="https://erp.domain.com/sales/orders/..."
                        class="w-full rounded-xl border-0 bg-slate-100 dark:bg-slate-800/80 py-2.5 px-4 text-xs font-mono text-slate-600 dark:text-slate-400 focus:ring-2 focus:ring-blue-500/50"
                    />
                    <span class="text-[11px] text-slate-400 mt-1 block">URL ini sangat membantu tim developer melacak lokasi persis masalah.</span>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                        Deskripsi Kendala / Detail Request <span class="text-rose-500">*</span>
                    </label>
                    <textarea
                        v-model="form.description"
                        rows="5"
                        placeholder="Jelaskan kronologi munculnya error atau spesifikasi revisi yang Anda harapkan secara detail..."
                        class="w-full rounded-xl border-0 bg-slate-100 dark:bg-slate-800/80 p-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                        required
                    ></textarea>
                    <p v-if="form.errors.description" class="text-xs text-rose-500 mt-1">{{ form.errors.description }}</p>
                </div>

                <!-- Screenshot / Attachment File -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                        Lampiran Screenshot / Dokumen (Opsional)
                    </label>
                    <div class="flex items-center gap-4">
                        <label class="cursor-pointer inline-flex items-center gap-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 px-4 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-300 transition-colors">
                            <PaperClipIcon class="h-5 w-5 text-blue-500" />
                            <span>Pilih File Lampiran</span>
                            <input type="file" @change="handleFileChange" class="sr-only" accept="image/*,.pdf,.doc,.docx,.zip" />
                        </label>
                        <span v-if="form.attachment" class="text-xs font-medium text-blue-400 truncate max-w-xs">
                            📄 {{ form.attachment.name }}
                        </span>
                        <span v-else class="text-xs text-slate-400">Format: Gambar, PDF, Doc, Zip (Maks 10MB)</span>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-3">
                    <Link
                        :href="route('helpdesk.index')"
                        class="px-5 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-sm font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors"
                    >
                        Batal
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-sm font-semibold text-white transition-all shadow-lg shadow-blue-500/25 disabled:opacity-50"
                    >
                        {{ form.processing ? 'Mengirim...' : 'Kirim Tiket Helpdesk' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
