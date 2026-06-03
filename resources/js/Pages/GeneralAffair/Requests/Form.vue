<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ArrowLeftIcon,
    TrashIcon,
    PlusIcon,
} from '@heroicons/vue/24/outline';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    products: Array,
    user: Object,
    request: Object, // Optional, for edit mode
});

const productOptions = computed(() => 
    props.products.map(p => ({
        id: p.id,
        label: `[${p.code || p.sku || '-'}] ${p.name}`,
        ...p
    }))
);

const isEdit = computed(() => !!props.request);

const form = useForm({
    request_date: props.request?.request_date || new Date().toISOString().split('T')[0],
    requester: props.request?.requester || props.user?.name || '',
    notes: props.request?.notes || '',
    items: props.request?.items?.map(item => ({
        product_id: item.product_id,
        qty: parseFloat(item.qty),
        description: item.description,
    })) || [{
        product_id: '',
        qty: 1,
        description: '',
    }],
});

const addItem = () => {
    form.items.push({
        product_id: '',
        qty: 1,
        description: '',
    });
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const onProductChange = (index, product) => {
    if (product) {
        form.items[index].product_id = product.id;
        form.items[index].description = product.description || product.notes || `SKU: ${product.sku}`;
    } else {
        form.items[index].product_id = '';
        form.items[index].description = '';
    }
};

const submit = () => {
    if (isEdit.value) {
        form.put(route('ga.requests.update', props.request.id));
    } else {
        form.post(route('ga.requests.store'));
    }
};
</script>

<template>
    <Head :title="isEdit ? 'Ubah Pengajuan PR' : 'Buat Pengajuan PR Baru'" />
    
    <AppLayout title="Pengajuan Pembelian GA">
        <div class="max-w-full mx-auto">
            <Link href="/general-affair/requests" class="inline-flex items-center gap-2 mb-4 text-sm font-bold text-slate-500 dark:text-slate-400 hover:text-cyan-600 transition-colors">
                <ArrowLeftIcon class="h-4 w-4" /> Kembali ke Daftar
            </Link>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
                    <!-- Request Info -->
                    <div class="xl:col-span-4 glass-card rounded-2xl p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-4">
                        <h3 class="text-sm font-black uppercase tracking-wider text-slate-400 pb-2 border-b border-slate-100 dark:border-slate-800">Informasi Pengajuan</h3>
                        
                        <div v-if="isEdit">
                            <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1">No. PR</label>
                            <input type="text" :value="request.pr_number" class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-950/50 py-2.5 text-slate-500 dark:text-slate-400 cursor-not-allowed font-bold" disabled />
                        </div>

                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1">Tanggal</label>
                            <input type="date" v-model="form.request_date" class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-cyan-500/50 font-bold" required />
                        </div>

                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1">Departemen</label>
                            <input type="text" value="HRGA" class="w-full rounded-xl border-0 bg-slate-100 dark:bg-slate-950 py-2.5 text-slate-500 dark:text-slate-400 cursor-not-allowed font-bold" disabled />
                        </div>

                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1">Pemohon (Requester)</label>
                            <input type="text" v-model="form.requester" class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-cyan-500/50 font-bold" required />
                        </div>
                    </div>

                    <!-- Items -->
                    <div class="xl:col-span-8 glass-card rounded-2xl p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 relative z-20">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800 mb-4">
                            <h3 class="text-sm font-black uppercase tracking-wider text-slate-400">Daftar Barang / Material</h3>
                            <button type="button" @click="addItem" class="text-xs font-bold text-cyan-600 dark:text-cyan-400 hover:text-cyan-500 flex items-center gap-1">
                                <PlusIcon class="h-4 w-4" /> Tambah Baris
                            </button>
                        </div>

                        <div class="space-y-3 pr-2 relative">
                            <!-- Header Row -->
                            <div class="grid grid-cols-12 gap-3 px-3 py-2 mb-2 hidden sm:grid sticky top-0 z-10 bg-white dark:bg-slate-900 border-b border-slate-100 dark:border-slate-800">
                                <div class="col-span-12 sm:col-span-5">
                                    <span class="text-[10px] font-bold text-slate-500 uppercase">Produk / Nama Barang</span>
                                </div>
                                <div class="col-span-4 sm:col-span-2 text-right pr-2">
                                    <span class="text-[10px] font-bold text-slate-500 uppercase">Jumlah (Qty)</span>
                                </div>
                                <div class="col-span-4 sm:col-span-4 pl-2">
                                    <span class="text-[10px] font-bold text-slate-500 uppercase">Spesifikasi / Catatan</span>
                                </div>
                                <div class="col-span-4 sm:col-span-1"></div>
                            </div>

                            <div v-for="(item, index) in form.items" :key="index" :style="{ zIndex: 100 - index }" class="relative grid grid-cols-12 gap-3 items-end bg-slate-50 dark:bg-slate-800/30 p-3 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800/50 transition-colors">
                                <div class="col-span-12 sm:col-span-5">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1 sm:hidden">Produk</label>
                                    <SearchableSelect 
                                        v-model="item.product_id"
                                        :options="productOptions"
                                        @change="(p) => onProductChange(index, p)"
                                        placeholder="Cari produk..."
                                    />
                                </div>
                                <div class="col-span-4 sm:col-span-2">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1 sm:hidden">Qty</label>
                                    <input type="number" v-model="item.qty" min="1" step="any" class="w-full rounded-lg border-0 bg-slate-50 dark:bg-slate-800 py-2.5 text-xs text-slate-900 dark:text-white focus:ring-1 focus:ring-cyan-500 text-right font-bold" required />
                                </div>
                                <div class="col-span-4 sm:col-span-4">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1 sm:hidden">Spesifikasi</label>
                                    <input type="text" v-model="item.description" class="w-full rounded-lg border-0 bg-slate-50 dark:bg-slate-800 py-2.5 text-xs text-slate-900 dark:text-white focus:ring-1 focus:ring-cyan-500" placeholder="Detail spesifikasi..." />
                                </div>
                                <div class="col-span-4 sm:col-span-1 flex justify-end">
                                    <button type="button" @click="removeItem(index)" class="p-2 text-slate-500 hover:text-red-500 rounded-lg hover:bg-red-500/10 transition-colors">
                                        <TrashIcon class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="glass-card rounded-2xl p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 relative z-0">
                    <label class="block text-xs font-black uppercase tracking-wider text-slate-400 mb-2">Justifikasi / Catatan Pengadaan</label>
                    <textarea v-model="form.notes" rows="3" class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-cyan-500/50" placeholder="Alasan mengapa barang-barang ini dibutuhkan..."></textarea>
                </div>

                <div class="flex justify-end gap-3">
                    <Link href="/general-affair/requests" class="px-6 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 text-sm font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-700 transition-all">Batal</Link>
                    <button type="submit" class="px-10 py-2.5 rounded-xl bg-cyan-600 text-sm font-black text-white hover:bg-cyan-500 shadow-lg shadow-cyan-500/20 active:scale-95 transition-all" :disabled="form.processing">
                        {{ form.processing ? 'Menyimpan...' : (isEdit ? 'Simpan Perubahan' : 'Kirim Pengajuan (PR)') }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
