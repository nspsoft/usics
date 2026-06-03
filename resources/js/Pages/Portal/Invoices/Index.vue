<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import { ChevronRightIcon } from '@heroicons/vue/24/outline';
import { formatDate } from '@/helpers';

defineProps({
    invoices: Object,
});
</script>

<template>
    <PortalLayout title="Invoices">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Invoices</h1>
                <p class="text-slate-500">Manage your invoices and track payments.</p>
            </div>
        </div>

        <!-- Invoices Table -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="overflow-x-auto overflow-y-auto max-h-[600px]">
                <table class="w-full text-left text-sm text-slate-600 dark:text-slate-400">
                    <thead class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm bg-slate-50 dark:bg-slate-700/50 uppercase text-xs font-bold text-slate-500">
                        <tr>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4">Invoice #</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4">PO Reference</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4">Date</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4">Due Date</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4">Total Amount</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4">Status</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        <tr v-for="invoice in invoices.data" :key="invoice.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                            <td class="px-6 py-4 font-medium text-slate-900 dark:text-white">{{ invoice.invoice_number }}</td>
                            <td class="px-6 py-4 text-indigo-600">{{ invoice.purchase_order?.po_number }}</td>
                            <td class="px-6 py-4">{{ formatDate(invoice.invoice_date) }}</td>
                            <td class="px-6 py-4">{{ formatDate(invoice.due_date) }}</td>
                            <td class="px-6 py-4 font-bold">Rp {{ Number(invoice.total_amount).toLocaleString('id-ID') }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded-full text-xs font-bold capitalize"
                                    :class="{
                                        'bg-red-100 text-red-700': invoice.status === 'unpaid',
                                        'bg-amber-100 text-amber-700': invoice.status === 'partial',
                                        'bg-emerald-100 text-emerald-700': invoice.status === 'paid',
                                        'bg-slate-100 text-slate-700': invoice.status === 'cancelled',
                                    }">
                                    {{ invoice.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <Link :href="route('portal.invoices.show', invoice.id)" class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-900 font-semibold">
                                    Details <ChevronRightIcon class="h-4 w-4" />
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="invoices.data.length === 0">
                            <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                                No invoices found. Create one from an Acknowledged PO.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

             <!-- Pagination -->
            <div v-if="invoices.links && invoices.links.length > 3" class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex items-center justify-center">
                <div class="flex gap-1">
                    <Link
                        v-for="(link, key) in invoices.links"
                        :key="key"
                        :href="link.url || '#'"
                        v-html="link.label"
                        class="px-3 py-1 rounded-lg text-xs font-medium transition-colors"
                        :class="[
                            link.active 
                                ? 'bg-indigo-600 text-white' 
                                : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600',
                            !link.url ? 'opacity-50 cursor-not-allowed' : ''
                        ]"
                    />
                </div>
            </div>
        </div>
    </PortalLayout>
</template>
