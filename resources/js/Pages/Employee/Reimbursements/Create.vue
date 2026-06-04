<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const form = useForm({
    date: new Date().toISOString().split('T')[0],
    type: 'Travel',
    amount: '',
    description: '',
    receipt: null,
});

const submit = () => {
    form.post(route('employee.reimbursements.store'), {
        preserveScroll: true,
    });
};

const handleFileChange = (e) => {
    form.receipt = e.target.files[0];
};
</script>

<template>
    <Head title="Submit Reimbursement" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Submit Reimbursement</h2>
                <Link :href="route('employee.reimbursements.index')" class="text-gray-600 hover:text-gray-900 transition">
                    &larr; Back to List
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        
                        <div>
                            <InputLabel for="date" value="Transaction Date" />
                            <TextInput
                                id="date"
                                type="date"
                                class="mt-1 block w-full"
                                v-model="form.date"
                                required
                            />
                            <InputError class="mt-2" :message="form.errors.date" />
                        </div>

                        <div>
                            <InputLabel for="type" value="Expense Type" />
                            <select
                                id="type"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                v-model="form.type"
                                required
                            >
                                <option value="Travel">Travel & Transportation</option>
                                <option value="Medical">Medical / Health</option>
                                <option value="Meals">Meals & Entertainment</option>
                                <option value="Supplies">Office Supplies</option>
                                <option value="Other">Other</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.type" />
                        </div>

                        <div>
                            <InputLabel for="amount" value="Amount (IDR)" />
                            <TextInput
                                id="amount"
                                type="number"
                                min="0"
                                class="mt-1 block w-full"
                                v-model="form.amount"
                                required
                                placeholder="e.g. 150000"
                            />
                            <InputError class="mt-2" :message="form.errors.amount" />
                        </div>

                        <div>
                            <InputLabel for="description" value="Description / Purpose" />
                            <textarea
                                id="description"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                v-model="form.description"
                                rows="3"
                                required
                                placeholder="Describe the expense..."
                            ></textarea>
                            <InputError class="mt-2" :message="form.errors.description" />
                        </div>

                        <div>
                            <InputLabel for="receipt" value="Receipt/Invoice Upload (Image or PDF)" />
                            <input
                                id="receipt"
                                type="file"
                                @change="handleFileChange"
                                class="mt-1 block w-full border border-gray-300 p-2 rounded-md"
                                accept=".jpg,.jpeg,.png,.pdf"
                                required
                            />
                            <InputError class="mt-2" :message="form.errors.receipt" />
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <PrimaryButton class="ml-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Submit Request
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
