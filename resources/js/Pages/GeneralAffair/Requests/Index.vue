<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    PlusIcon,
    EyeIcon,
    PencilSquareIcon,
    TrashIcon,
    DocumentPlusIcon,
    MagnifyingGlassIcon,
} from '@heroicons/vue/24/outline';
import Pagination from '@/Components/Pagination.vue';
import debounce from 'lodash/debounce';
import { ref } from 'vue';

const props = defineProps({
    requests: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');

const applyFilters = debounce(() => {
    router.get('/general-affair/requests', {
        search: search.value,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

const getStatusBadge = (status) => {
    if (!status) return 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30';
    const badges = {
        draft: 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30',
        pending: 'bg-amber-500/20 text-amber-500 border-amber-500/30',
        approved: 'bg-emerald-500/20 text-emerald-500 border-emerald-500/30',
        rejected: 'bg-red-500/20 text-red-500 border-red-500/30',
    };
    return badges[status] || 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30';
};

const deleteRequest = (id) => {
    if (!id) return;
    if (confirm('Apakah Anda yakin ingin menghapus draf pengajuan PR ini?')) {
        router.delete(`/general-affair/requests/${id}`);
    }
};

const formatDate = (date) => {
    if (!date) return '-';
    try {
        return new Date(date).toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        });
    } catch (e) {
        return date;
    }
};
</script>

<template>
    <Head title="Pengajuan Pembelian (PR)" />
    
    <AppLayout title="Pengajuan Pembelian GA">
        <div class="sm:flex sm:items-center sm:justify-between mb-6">
            <div>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400 font-medium uppercase tracking-widest">Pengajuan Purchase Request (PR) - Departemen HRGA</p>
            </div>
            <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none flex items-center gap-3">
                <div class="relative group">
                    <MagnifyingGlassIcon class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400 group-focus-within:text-cyan-500 transition-colors" />
                    <input 
                        v-model="search" 
                        @input="applyFilters"
                        type="text" 
                        placeholder="Cari PR..." 
                        class="w-full bg-white dark:bg-slate-900 border-0 ring-1 ring-slate-200 dark:ring-slate-800 rounded-xl pl-12 pr-4 py-2.5 text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-cyan-500 transition-all shadow-sm"
                    >
                </div>

                <Link
                    href="/general-affair/requests/create"
                    class="inline-flex items-center gap-2 rounded-xl bg-cyan-600 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-cyan-500/20 hover:bg-cyan-500 hover:shadow-cyan-500/40 active:scale-95 transition-all"
                >
                    <PlusIcon class="h-5 w-5" />
                    Buat Pengajuan (PR)
                </Link>
            </div>
        </div>

        <div class="rounded-2xl glass-card overflow-hidden border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/50">
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">No. PR</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tanggal</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pemohon</th>
                            <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Item</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Catatan</th>
                            <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3.5 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr v-for="request in requests.data" :key="request.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-cyan-500/10 font-mono text-[10px] font-black text-cyan-600 dark:text-cyan-400">
                                        PR
                                    </div>
                                    <Link :href="`/general-affair/requests/${request.id}`" class="text-sm font-bold text-slate-900 dark:text-white hover:text-cyan-500">
                                        {{ request.pr_number }}
                                    </Link>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-300">
                                {{ formatDate(request.request_date) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700 dark:text-slate-300 font-medium">
                                {{ request.requester || '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-bold text-slate-900 dark:text-white">
                                {{ request.items_count || 0 }}
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-500 dark:text-slate-400 max-w-xs truncate">
                                {{ request.notes || '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[10px] font-black tracking-wider uppercase" :class="getStatusBadge(request.status)">
                                    {{ request.status || 'N/A' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <Link :href="`/general-affair/requests/${request.id}`" class="p-2 rounded-xl text-slate-500 dark:text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/10 transition-colors" title="Lihat Detail">
                                        <EyeIcon class="h-4 w-4" />
                                    </Link>
                                    <Link v-if="request.status === 'draft'" :href="`/general-affair/requests/${request.id}/edit`" class="p-2 rounded-xl text-slate-500 dark:text-slate-400 hover:text-blue-500 hover:bg-blue-500/10 transition-colors" title="Ubah Draf">
                                        <PencilSquareIcon class="h-4 w-4" />
                                    </Link>
                                    <button v-if="request.status === 'draft'" @click="deleteRequest(request.id)" class="p-2 text-slate-500 dark:text-slate-400 hover:text-red-500 hover:bg-red-500/10 rounded-xl transition-colors" title="Hapus Draf">
                                        <TrashIcon class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="requests.data && requests.data.length === 0">
                            <td colspan="7" class="px-6 py-16 text-center text-slate-500 italic">
                                <DocumentPlusIcon class="h-10 w-10 mx-auto text-slate-300 dark:text-slate-700 mb-2" />
                                Tidak ada pengajuan PR dari General Affair.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div v-if="requests.last_page > 1" class="border-t border-slate-100 dark:border-slate-800 px-6 py-4">
                <Pagination :links="requests.links" />
            </div>
        </div>
    </AppLayout>
</template>
