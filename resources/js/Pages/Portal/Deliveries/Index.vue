<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import { ChevronRightIcon, PlusIcon } from '@heroicons/vue/24/outline';
import { formatDate } from '@/helpers';

const props = defineProps({
    deliveries: Object,
});
</script>

<template>
    <PortalLayout title="Deliveries">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Deliveries</h1>
                <p class="text-slate-500">Manage your delivery notes (Surat Jalan).</p>
            </div>
        </div>

        <!-- Deliveries Table -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="overflow-x-auto overflow-y-auto max-h-[600px]">
                <table class="w-full text-left text-sm text-slate-600 dark:text-slate-400">
                    <thead class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm bg-slate-50 dark:bg-slate-700/50 uppercase text-xs font-bold text-slate-500">
                        <tr>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4">Delivery Note #</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4">PO Reference</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4">Date</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4">Destination</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4">Items</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4">Status</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        <tr v-for="delivery in deliveries.data" :key="delivery.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                            <td class="px-6 py-4 font-medium text-slate-900 dark:text-white">{{ delivery.delivery_note_number }}</td>
                            <td class="px-6 py-4 text-indigo-600">{{ delivery.purchase_order?.po_number }}</td>
                            <td class="px-6 py-4">{{ formatDate(delivery.receipt_date) }}</td>
                            <td class="px-6 py-4">{{ delivery.warehouse?.name || '-' }}</td>
                            <td class="px-6 py-4">{{ delivery.items_count || 0 }} Items</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded-full text-xs font-bold capitalize"
                                    :class="{
                                        'bg-orange-100 text-orange-700': delivery.status === 'dispatched',
                                        'bg-blue-100 text-blue-700': delivery.status === 'received',
                                        'bg-emerald-100 text-emerald-700': delivery.status === 'completed',
                                    }">
                                    {{ delivery.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <Link :href="route('portal.deliveries.show', delivery.id)" class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-900 font-semibold">
                                    Details <ChevronRightIcon class="h-4 w-4" />
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="deliveries.data.length === 0">
                            <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                                No deliveries found. Create one from an Acknowledged PO.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="deliveries.links && deliveries.links.length > 3" class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex items-center justify-center">
                <div class="flex gap-1">
                    <Link
                        v-for="(link, key) in deliveries.links"
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
