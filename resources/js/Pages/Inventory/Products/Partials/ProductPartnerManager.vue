<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { TrashIcon, PlusIcon, UserGroupIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    product: Object,
    customers: Array,
    suppliers: Array,
});

const form = useForm({
    partner_type: 'App\\Models\\Sales\\Customer',
    partner_id: '',
    alias_sku: '',
    alias_name: '',
});

const submit = () => {
    form.post(route('inventory.products.partners.store', props.product.id), {
        onSuccess: () => {
            form.reset('partner_id', 'alias_sku', 'alias_name');
        },
    });
};

const deleteAlias = (id) => {
    if (confirm('Are you sure you want to remove this alias?')) {
        router.delete(route('inventory.products.partners.destroy', id), {
            preserveScroll: true,
        });
    }
};

const getPartnerName = (partner) => {
    if (!partner.partner) return 'Unknown Partner (Deleted)';
    return partner.partner.name;
};
</script>

<template>
    <div class="rounded-2xl glass-card overflow-hidden">
        <div class="border-b border-slate-200 dark:border-slate-800 px-6 py-4 flex items-center gap-3">
             <div class="p-2 rounded-lg bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400">
                <UserGroupIcon class="w-5 h-5" />
            </div>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Partner Aliases</h2>
        </div>
        
        <div class="p-6">
            <p class="text-sm text-slate-500 mb-6">
                Map this product to specific Customer or Supplier terminologies. 
                Documents (SO/PO) printed for these partners will use the Alias Name/SKU instead of the internal one.
            </p>

            <!-- Add New Alias Form -->
            <form @submit.prevent="submit" class="mb-8 bg-slate-50 dark:bg-slate-800/50 rounded-xl p-4 border border-slate-100 dark:border-slate-700">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                    <div class="md:col-span-2">
                         <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Type</label>
                         <select v-model="form.partner_type" class="form-select w-full rounded-lg border-slate-200 dark:border-slate-700 text-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-slate-800 dark:text-white">
                            <option value="App\Models\Sales\Customer">Customer</option>
                            <option value="App\Models\Purchasing\Supplier">Supplier</option>
                         </select>
                    </div>
                    <div class="md:col-span-3">
                         <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Partner</label>
                         <select v-model="form.partner_id" class="form-select w-full rounded-lg border-slate-200 dark:border-slate-700 text-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-slate-800 dark:text-white">
                            <option value="" disabled>Select Partner</option>
                            <template v-if="form.partner_type === 'App\\Models\\Sales\\Customer'">
                                <option v-for="customer in customers" :key="customer.id" :value="customer.id">{{ customer.name }}</option>
                            </template>
                             <template v-else>
                                <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">{{ supplier.name }}</option>
                            </template>
                         </select>
                         <p v-if="form.errors.partner_id" class="text-xs text-red-500 mt-1">{{ form.errors.partner_id }}</p>
                    </div>
                     <div class="md:col-span-3">
                         <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Partner SKU</label>
                         <input v-model="form.alias_sku" type="text" class="form-input w-full rounded-lg border-slate-200 dark:border-slate-700 text-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-slate-800 dark:text-white" placeholder="Optional" />
                    </div>
                     <div class="md:col-span-3">
                         <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Partner Product Name</label>
                         <input v-model="form.alias_name" type="text" class="form-input w-full rounded-lg border-slate-200 dark:border-slate-700 text-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-slate-800 dark:text-white" placeholder="Optional" />
                    </div>
                    <div class="md:col-span-1">
                        <button type="submit" :disabled="form.processing" class="w-full h-[38px] flex items-center justify-center rounded-lg bg-indigo-600 text-white hover:bg-indigo-500 transition-colors disabled:opacity-50">
                            <PlusIcon class="w-5 h-5" />
                        </button>
                    </div>
                </div>
                 <p v-if="form.errors.alias_name" class="text-xs text-red-500 mt-2">{{ form.errors.alias_name }}</p>
            </form>
            
            <!-- List -->
             <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Partner</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Alias SKU</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Alias Name</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                        <tr v-for="partner in product.partners" :key="partner.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <span 
                                        class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset"
                                        :class="partner.partner_type.includes('Customer') ? 'bg-blue-50 text-blue-700 ring-blue-600/20' : 'bg-amber-50 text-amber-700 ring-amber-600/20'"
                                    >
                                        {{ partner.partner_type.includes('Customer') ? 'Cust' : 'Supp' }}
                                    </span>
                                    <span class="text-sm font-medium text-slate-900 dark:text-white">{{ getPartnerName(partner) }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm font-mono text-slate-600 dark:text-slate-400">{{ partner.alias_sku || '-' }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-400">{{ partner.alias_name || '-' }}</td>
                            <td class="px-4 py-3 text-right">
                                <button @click="deleteAlias(partner.id)" class="text-slate-400 hover:text-red-500 transition-colors">
                                    <TrashIcon class="w-4 h-4" />
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!product.partners?.length">
                            <td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500 italic">
                                No aliases defined. This product will use its internal name for all partners.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
