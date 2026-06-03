<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ArrowLeftIcon,
    PencilSquareIcon,
    PrinterIcon,
    DocumentTextIcon,
    FolderOpenIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    request: Object,
});

const getStatusBadge = (status) => {
    if (!status) return 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30';
    const badges = {
        draft: 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30',
        pending: 'bg-amber-500/20 text-amber-550 border-amber-500/30',
        approved: 'bg-emerald-500/20 text-emerald-500 border-emerald-500/30',
        rejected: 'bg-red-500/20 text-red-500 border-red-500/30',
    };
    return badges[status] || 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30';
};

const formatDate = (date) => {
    if (!date) return '-';
    try {
        return new Date(date).toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'long',
            year: 'numeric'
        });
    } catch (e) {
        return date;
    }
};
</script>

<template>
    <Head :title="`Detail PR - ${request?.pr_number || ''}`" />
    
    <AppLayout title="Pengajuan Pembelian GA">
        <div v-if="request" class="max-w-7xl mx-auto">
            <!-- Header Actions -->
            <div class="flex items-center justify-between mb-6">
                <Link href="/general-affair/requests" class="inline-flex items-center gap-2 text-sm font-bold text-slate-500 dark:text-slate-400 hover:text-cyan-600 transition-colors">
                    <ArrowLeftIcon class="h-4 w-4" /> Kembali ke Daftar
                </Link>

                <div class="flex items-center gap-3">
                    <a 
                        :href="route('purchasing.requests.print', request.id)" 
                        target="_blank"
                        class="inline-flex items-center gap-2 rounded-xl bg-slate-100 dark:bg-slate-800 px-4 py-2.5 text-sm font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all active:scale-95 shadow-sm"
                    >
                        <PrinterIcon class="h-4 w-4" />
                        Cetak PR
                    </a>

                    <Link
                        v-if="request.status === 'draft'"
                        :href="`/general-affair/requests/${request.id}/edit`"
                        class="inline-flex items-center gap-2 rounded-xl bg-cyan-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-cyan-500 shadow-lg shadow-cyan-500/20 transition-all active:scale-95"
                    >
                        <PencilSquareIcon class="h-4 w-4" />
                        Ubah Draf
                    </Link>
                </div>
            </div>

            <!-- Details Card -->
            <div class="space-y-6">
                <!-- Info Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Main details -->
                    <div class="lg:col-span-2 glass-card rounded-2xl p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                        <div class="flex items-start gap-4 mb-6">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-cyan-500/10 text-cyan-600 dark:text-cyan-400">
                                <DocumentTextIcon class="h-6 w-6" />
                            </div>
                            <div>
                                <h1 class="text-xl font-bold text-slate-900 dark:text-white">{{ request.pr_number }}</h1>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-xs text-slate-500">Dibuat oleh {{ request.created_by?.name || request.requester || 'User' }} pada {{ formatDate(request.created_at) }}</span>
                                    <span 
                                        class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[10px] font-black tracking-wider uppercase"
                                        :class="getStatusBadge(request.status)"
                                    >
                                        {{ request.status || 'N/A' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 pt-6 border-t border-slate-100 dark:border-slate-800">
                            <div>
                                <div class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1">Tanggal Pengajuan</div>
                                <div class="text-sm font-bold text-slate-900 dark:text-white">{{ formatDate(request.request_date) }}</div>
                            </div>
                            <div>
                                <div class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1">Departemen</div>
                                <div class="text-sm font-bold text-slate-900 dark:text-white">{{ request.department || '-' }}</div>
                            </div>
                            <div>
                                <div class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1">Pemohon (Requester)</div>
                                <div class="text-sm font-bold text-slate-900 dark:text-white">{{ request.requester || '-' }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Notes/Justification -->
                    <div class="glass-card rounded-2xl p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex flex-col">
                        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-3 pb-2 border-b border-slate-100 dark:border-slate-800">Justifikasi / Catatan Pengadaan</h3>
                        <p class="text-sm text-slate-600 dark:text-slate-300 whitespace-pre-line flex-grow font-medium leading-relaxed">
                            {{ request.notes || 'Tidak ada catatan/justifikasi pengadaan.' }}
                        </p>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="glass-card rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                    <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                        <h3 class="font-bold text-slate-900 dark:text-white text-sm uppercase tracking-wider text-slate-400">Daftar Barang / Material</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-slate-800/50">
                                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Nama Barang / Produk</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Spesifikasi / Deskripsi</th>
                                    <th class="px-6 py-3.5 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Jumlah (Qty)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr v-for="item in request.items" :key="item.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div>
                                                <div class="text-sm font-bold text-slate-900 dark:text-white">{{ item.product?.name || 'Produk Tidak Dikenal' }}</div>
                                                <div class="text-xs text-slate-500 dark:text-slate-400 font-mono mt-0.5">{{ item.product?.sku || item.product?.code || '-' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-300 font-medium">
                                        {{ item.description || '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-slate-900 dark:text-white">
                                        {{ parseFloat(item.qty || 0) }} <span class="text-xs text-slate-500 font-medium ml-1">{{ item.product?.unit?.symbol || '' }}</span>
                                    </td>
                                </tr>
                                <tr v-if="!request.items || request.items.length === 0">
                                    <td colspan="3" class="px-6 py-12 text-center text-slate-500 italic">
                                        <FolderOpenIcon class="h-8 w-8 mx-auto text-slate-350 dark:text-slate-700 mb-2" />
                                        Tidak ada item pengajuan dalam Purchase Request ini.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div v-else class="text-slate-900 dark:text-white text-center py-20 font-bold">
            Memuat data pengajuan...
        </div>
    </AppLayout>
</template>
