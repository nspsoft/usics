<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ArrowLeftIcon, CheckCircleIcon } from '@heroicons/vue/24/outline';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    receipt: Object,
    purchaseOrders: Array,
});

const purchaseOrderOptions = computed(() =>
    (props.purchaseOrders || []).map(po => ({
        id: po.id,
        label: `${po.po_number} - ${po.supplier?.name || '-'}`,
        ...po,
    }))
);

const form = useForm({
    purchase_order_id: props.receipt.purchase_order_id || null,
});

const submit = () => {
    form.post(route('purchasing.receipts.reassign-po.update', props.receipt.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="`Change Linked PO - ${receipt.grn_number}`" />

    <AppLayout title="Goods Receipts">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-6">
                <Link :href="route('purchasing.receipts.show', receipt.id)" class="inline-flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white">
                    <ArrowLeftIcon class="h-4 w-4" /> Back to Receipt
                </Link>
            </div>

            <div class="glass-card rounded-2xl p-6 space-y-6">
                <div>
                    <div class="text-lg font-semibold text-slate-900 dark:text-white">Change Linked PO</div>
                    <div class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        GRN <span class="font-mono">{{ receipt.grn_number }}</span> sudah completed, jadi koreksi dilakukan dengan memindahkan referensi PO dan menggeser qty_received pada PO (tanpa mengubah stok fisik).
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <div class="text-xs font-bold text-slate-500 uppercase tracking-widest">Current PO</div>
                        <div class="text-sm font-mono text-slate-900 dark:text-white">
                            {{ receipt.purchase_order?.po_number || '-' }}
                        </div>
                        <div class="text-xs text-slate-500 dark:text-slate-400">
                            Supplier: {{ receipt.supplier?.name || '-' }} · Warehouse: {{ receipt.warehouse?.name || '-' }}
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-slate-500 dark:text-slate-400">Target PO</label>
                        <SearchableSelect
                            v-model="form.purchase_order_id"
                            :options="purchaseOrderOptions"
                            placeholder="Search PO..."
                        />
                        <div v-if="form.errors.purchase_order_id" class="text-xs text-red-500">
                            {{ form.errors.purchase_order_id }}
                        </div>
                        <div class="text-xs text-slate-500 dark:text-slate-400">
                            Hanya menampilkan PO yang cocok (supplier & warehouse sama, item produk ada, dan remaining qty cukup).
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <Link
                        :href="route('purchasing.receipts.show', receipt.id)"
                        class="inline-flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-800 px-4 py-2 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-700 hover:text-slate-900 dark:text-white transition-colors"
                    >
                        Cancel
                    </Link>
                    <button
                        type="button"
                        @click="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-slate-900 dark:text-white hover:bg-emerald-500 shadow-lg shadow-emerald-900/20 disabled:opacity-60"
                    >
                        <CheckCircleIcon class="h-4 w-4" /> Update Linked PO
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
