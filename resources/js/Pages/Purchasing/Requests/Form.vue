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
    departments: Array,
    users: Array,
    user: Object,
    request: Object, // Optional, for edit mode
    prefill: Object, // Optional, for initial data
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
    department: props.request?.department || '',
    requester: props.request?.requester || props.user?.name || '',
    notes: props.request?.notes || '',
    items: props.request?.items?.map(item => ({
        product_id: item.product_id,
        qty: parseFloat(item.qty),
        description: item.description,
    })) || props.prefill?.items || [{
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
        form.put(route('purchasing.requests.update', props.request.id));
    } else {
        form.post(route('purchasing.requests.store'));
    }
};
</script>

<template>
    <Head :title="isEdit ? 'Edit Purchase Request' : 'Create Purchase Request'" />
    
    <AppLayout title="Purchase Requests">
        <div class="max-w-full px-4 sm:px-6 lg:px-8 mx-auto">
            <Link href="/purchasing/requests" class="inline-flex items-center gap-2 mb-4 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white">
                <ArrowLeftIcon class="h-4 w-4" /> Back to List
            </Link>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
                    <!-- Request Info -->
                    <div class="xl:col-span-4 glass-card rounded-2xl p-6 space-y-4">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Request Info</h3>
                        
                        <div v-if="isEdit">
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">PR Number</label>
                            <input type="text" :value="request.pr_number" class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 py-2.5 text-slate-500 dark:text-slate-400 cursor-not-allowed" disabled />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">Date</label>
                            <input type="date" v-model="form.request_date" class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50" required />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">Department</label>
                            <select v-model="form.department" class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50" required>
                                <option value="">Select Department</option>
                                <option v-for="dept in departments" :key="dept.id" :value="dept.name">{{ dept.name }}</option>
                            </select>
                        </div>

                         <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">Requester</label>
                            <select v-model="form.requester" class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50" required>
                                <option value="">Select Requester</option>
                                <option v-for="u in users" :key="u.id" :value="u.name">{{ u.name }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Items -->
                    <div class="xl:col-span-8 glass-card rounded-2xl p-6 relative z-20">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Items Requested</h3>
                            <button type="button" @click="addItem" class="text-sm font-medium text-blue-400 hover:text-blue-300 flex items-center gap-1">
                                <PlusIcon class="h-4 w-4" /> Add Item
                            </button>
                        </div>

                        <div class="space-y-3 pr-2 relative">
                            <!-- Header Row -->
                            <div class="grid grid-cols-12 gap-3 px-3 py-2 mb-2 hidden sm:grid sticky top-0 z-10 bg-white dark:bg-slate-900 border-b border-slate-100 dark:border-slate-800">
                                <div class="col-span-12 sm:col-span-5">
                                    <span class="text-[10px] font-bold text-slate-500 uppercase">Product / Material</span>
                                </div>
                                <div class="col-span-4 sm:col-span-2 text-right pr-2">
                                    <span class="text-[10px] font-bold text-slate-500 uppercase">Qty</span>
                                </div>
                                <div class="col-span-4 sm:col-span-4 pl-2">
                                    <span class="text-[10px] font-bold text-slate-500 uppercase">Description / Specs</span>
                                </div>
                                <div class="col-span-4 sm:col-span-1"></div>
                            </div>

                            <div v-for="(item, index) in form.items" :key="index" :style="{ zIndex: 100 - index }" class="relative grid grid-cols-12 gap-3 items-end bg-slate-50 dark:bg-slate-800/30 p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-900 dark:bg-slate-800/50 transition-colors">
                                <div class="col-span-12 sm:col-span-5">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1 sm:hidden">Product</label>
                                    <SearchableSelect 
                                        v-model="item.product_id"
                                        :options="productOptions"
                                        @change="(p) => onProductChange(index, p)"
                                        placeholder="Search Product..."
                                    />
                                </div>
                                <div class="col-span-4 sm:col-span-2">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1 sm:hidden">Qty</label>
                                    <input type="number" v-model="item.qty" min="1" step="any" class="w-full rounded-lg border-0 bg-slate-50 dark:bg-slate-800 py-2.5 text-xs text-slate-900 dark:text-white focus:ring-1 focus:ring-blue-500 text-right" required />
                                </div>
                                <div class="col-span-4 sm:col-span-4">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1 sm:hidden">Specs</label>
                                    <input type="text" v-model="item.description" class="w-full rounded-lg border-0 bg-slate-50 dark:bg-slate-800 py-2.5 text-xs text-slate-900 dark:text-white focus:ring-1 focus:ring-blue-500" placeholder="Details..." />
                                </div>
                                <div class="col-span-4 sm:col-span-1 flex justify-end">
                                    <button type="button" @click="removeItem(index)" class="p-2 text-slate-500 hover:text-red-400 rounded-lg hover:bg-slate-700/50 transition-colors">
                                        <TrashIcon class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="glass-card rounded-2xl p-6 relative z-0">
                    <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Notes / Justification</label>
                    <textarea v-model="form.notes" rows="3" class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50" placeholder="Why is this purchase needed?"></textarea>
                </div>

                <div class="flex justify-end gap-3">
                    <Link href="/purchasing/requests" class="px-6 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-700">Cancel</Link>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-600 text-sm font-semibold text-white dark:text-white hover:bg-blue-500 shadow-lg shadow-blue-900/20" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : (isEdit ? 'Update Request' : 'Submit Request') }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>



