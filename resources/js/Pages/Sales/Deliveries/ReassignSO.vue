<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ArrowLeftIcon, CheckCircleIcon } from '@heroicons/vue/24/outline';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    deliveryOrder: Object,
    salesOrders: Array,
});

const salesOrderOptions = computed(() =>
    (props.salesOrders || []).map(so => ({
        id: so.id,
        label: `${so.so_number} ${so.customer_po_number ? '(PO: ' + so.customer_po_number + ')' : '(Tanpa PO)'} - ${so.customer?.name || '-'}`,
        ...so,
    }))
);

const form = useForm({
    sales_order_id: props.deliveryOrder.sales_order_id || null,
});

const submit = () => {
    form.post(route('sales.deliveries.reassign-so.update', props.deliveryOrder.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="`Pindahkan SO - ${deliveryOrder.do_number}`" />

    <AppLayout title="Delivery Orders">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center justify-between mb-8">
                <Link 
                    :href="route('sales.deliveries.show', deliveryOrder.id)" 
                    class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors"
                >
                    <ArrowLeftIcon class="h-4 w-4" /> Kembali ke Surat Jalan
                </Link>
            </div>

            <div class="rounded-2xl glass-card border border-slate-200 dark:border-slate-800 p-8 space-y-8 shadow-xl bg-white/50 dark:bg-slate-900/50 backdrop-blur-md">
                <div>
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white">Pindahkan ke Sales Order (SO) Lain</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">
                        Surat Jalan <span class="font-mono font-bold text-blue-500">{{ deliveryOrder.do_number }}</span> sudah terproses. Koreksi dilakukan dengan memindahkan referensi SO dan menyesuaikan kuantitas terkirim (<span class="font-mono text-amber-500">qty_delivered</span>) pada SO target tanpa mempengaruhi stok fisik gudang.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 pt-4 border-t border-slate-100 dark:border-slate-800/60">
                    <div class="space-y-3 bg-slate-50/50 dark:bg-slate-800/30 p-6 rounded-2xl border border-slate-100 dark:border-slate-800/30">
                        <div class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Sales Order Saat Ini</div>
                        <div class="text-lg font-mono font-bold text-slate-800 dark:text-slate-200">
                            {{ deliveryOrder.sales_order?.so_number || '-' }}
                        </div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 space-y-1">
                            <p><strong class="font-semibold text-slate-600 dark:text-slate-400">Customer:</strong> {{ deliveryOrder.customer?.name || '-' }}</p>
                            <p><strong class="font-semibold text-slate-600 dark:text-slate-400">Gudang:</strong> {{ deliveryOrder.warehouse?.name || '-' }}</p>
                        </div>
                    </div>

                    <div class="space-y-3 flex flex-col justify-between">
                        <div class="space-y-2">
                            <label class="block text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Pilih Sales Order Target</label>
                            <SearchableSelect
                                v-model="form.sales_order_id"
                                :options="salesOrderOptions"
                                placeholder="Cari No SO resmi..."
                                class="w-full"
                            />
                            <div v-if="form.errors.sales_order_id" class="text-xs text-red-500 font-medium">
                                {{ form.errors.sales_order_id }}
                            </div>
                        </div>
                        <div class="text-xs text-slate-400 dark:text-slate-500 leading-normal italic">
                            * Hanya menampilkan SO yang cocok (customer & warehouse sama, barang produk sesuai, dan sisa kuantitas di SO target mencukupi).
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-100 dark:border-slate-800/60">
                    <Link
                        :href="route('sales.deliveries.show', deliveryOrder.id)"
                        class="inline-flex items-center justify-center rounded-xl bg-slate-50 dark:bg-slate-800 px-5 py-2.5 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors border border-slate-200 dark:border-slate-700"
                    >
                        Batal
                    </Link>
                    <button
                        type="button"
                        @click="submit"
                        :disabled="form.processing || !form.sales_order_id"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-emerald-500 text-white px-6 py-2.5 text-sm font-bold shadow-lg shadow-emerald-500/20 hover:from-emerald-500 hover:to-emerald-400 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                    >
                        <CheckCircleIcon class="h-4.5 w-4.5" />
                        Pindahkan SO
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
