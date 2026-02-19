<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    opname: Object,
    opnameNumber: String,
    warehouses: Array,
});

const form = useForm({
    opname_number: props.opnameNumber,
    warehouse_id: '',
    opname_date: new Date().toISOString().split('T')[0],
    notes: '',
});

const submit = () => {
    form.post('/inventory/opname');
};
</script>

<template>
    <Head title="New Stock Opname" />

    <AppLayout title="New Stock Opname">
        <div class="max-w-full px-4 sm:px-6 lg:px-8 mx-auto">
            <Link
                href="/inventory/opname"
                class="inline-flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white mb-6"
            >
                <ArrowLeftIcon class="h-4 w-4" />
                Back to List
            </Link>

            <form @submit.prevent="submit" class="rounded-2xl glass-card p-6 space-y-6">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Start New Session</h2>
                    <p class="text-sm text-slate-500">Create a new stock taking session for a warehouse.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Opname Number</label>
                    <input
                        v-model="form.opname_number"
                        type="text"
                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                        readonly
                    />
                    <p v-if="form.errors.opname_number" class="mt-1 text-xs text-red-500">{{ form.errors.opname_number }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Warehouse</label>
                    <select
                        v-model="form.warehouse_id"
                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                    >
                        <option value="">Select Warehouse</option>
                        <option v-for="w in warehouses" :key="w.id" :value="w.id">
                            {{ w.name }}
                        </option>
                    </select>
                    <p v-if="form.errors.warehouse_id" class="mt-1 text-xs text-red-500">{{ form.errors.warehouse_id }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Session Date</label>
                    <input
                        v-model="form.opname_date"
                        type="date"
                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                    />
                    <p v-if="form.errors.opname_date" class="mt-1 text-xs text-red-500">{{ form.errors.opname_date }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Notes</label>
                    <textarea
                        v-model="form.notes"
                        rows="3"
                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                        placeholder="e.g. Annual Stock Taking, Aisle 1-5"
                    ></textarea>
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <Link
                        href="/inventory/opname"
                        class="rounded-xl bg-slate-50 dark:bg-slate-800 px-6 py-2.5 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-700 transition-colors"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        class="rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 hover:bg-blue-500 transition-colors"
                        :disabled="form.processing"
                    >
                        Create Session
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>



