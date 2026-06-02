<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ArrowLeftIcon, PlusIcon, TrashIcon, QuestionMarkCircleIcon, MapIcon } from '@heroicons/vue/24/outline';
import { ref } from 'vue';
import MapPicker from '@/Components/MapPicker.vue';

const props = defineProps({
    supplier: Object,
    subcontWarehouses: Array,
});

const isEditing = computed(() => !!props.supplier);

const form = useForm({
    code: props.supplier?.code || '',
    name: props.supplier?.name || '',
    contact_person: props.supplier?.contact_person || '',
    address: props.supplier?.address || '',
    city: props.supplier?.city || '',
    phone: props.supplier?.phone || '',
    fax: props.supplier?.fax || '',
    email: props.supplier?.email || '',
    tax_id: props.supplier?.tax_id || '',
    npwp: props.supplier?.npwp || '',
    payment_terms: props.supplier?.payment_terms || 'NET30',
    payment_days: props.supplier?.payment_days || 30,
    is_active: props.supplier?.is_active ?? true,
    subcontract_warehouse_id: props.supplier?.subcontract_warehouse_id || null,
    contacts: props.supplier?.contacts || [],
});

const submit = () => {
    if (isEditing.value) {
        form.put(`/purchasing/suppliers/${props.supplier.id}`);
    } else {
        form.post('/purchasing/suppliers');
    }
};

const paymentTerms = [
    { value: 'COD', label: 'Cash on Delivery', days: 0 },
    { value: 'NET7', label: 'Net 7 Days', days: 7 },
    { value: 'NET14', label: 'Net 14 Days', days: 14 },
    { value: 'NET30', label: 'Net 30 Days', days: 30 },
    { value: 'NET45', label: 'Net 45 Days', days: 45 },
    { value: 'NET60', label: 'Net 60 Days', days: 60 },
    { value: 'NET90', label: 'Net 90 Days', days: 90 },
];

const onPaymentTermChange = () => {
    const term = paymentTerms.find(t => t.value === form.payment_terms);
    if (term) {
        form.payment_days = term.days;
    }
};

const addContact = () => {
    form.contacts.push({
        name: '',
        phone: '',
        email: '',
        position: '',
    });
};

const removeContact = (index) => {
    form.contacts.splice(index, 1);
};

const showMap = ref(false);

const openMap = () => {
    showMap.value = true;
};

const onMapConfirm = (location) => {
    form.address = location.address;
    if (location.city) form.city = location.city;
    // Supplier form doesn't seem to have state/postal_code fields readily available in the shared file content?
    // Checking lines 17-23... address, city, phone, fax, email, tax_id, npwp.
    // So only address and city can be auto-filled.
};
</script>

<template>
    <Head :title="isEditing ? 'Edit Supplier' : 'Create Supplier'" />
    
    <AppLayout :title="isEditing ? 'Edit Supplier' : 'Create Supplier'">
        <form @submit.prevent="submit" class="space-y-6">
            <div class="flex items-center gap-4">
                <Link
                    href="/purchasing/suppliers"
                    class="flex items-center gap-2 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white transition-colors"
                >
                    <ArrowLeftIcon class="h-5 w-5" />
                    Back to Suppliers
                </Link>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    <!-- Basic Information -->
                    <div class="rounded-2xl glass-card">
                        <div class="border-b border-slate-200 dark:border-slate-800 px-6 py-4">
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Basic Information</h2>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="flex items-center gap-2 text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">
                                        Supplier Code *
                                        <div class="group relative cursor-help">
                                            <QuestionMarkCircleIcon class="h-4 w-4 text-slate-500 hover:text-blue-400 transition-colors" />
                                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-64 p-3 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-xl opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50">
                                                <p class="text-xs text-slate-900 dark:text-white">
                                                    <span class="font-bold block mb-1">Kode Supplier</span>
                                                    Kode unik untuk identifikasi supplier.
                                                    <br><br>
                                                    <span class="text-slate-500 dark:text-slate-400">Contoh: SUP-001, PT-ABC</span>
                                                </p>
                                                <div class="absolute bottom-0 left-1/2 -translate-x-1/2 translate-y-1/2 rotate-45 w-2 h-2 bg-slate-50 dark:bg-slate-800 border-r border-b border-slate-200 dark:border-slate-700"></div>
                                            </div>
                                        </div>
                                    </label>
                                    <input
                                        v-model="form.code"
                                        type="text"
                                        required
                                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50"
                                        placeholder="e.g., SUP-001"
                                    />
                                    <p v-if="form.errors.code" class="mt-1 text-sm text-red-400">{{ form.errors.code }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Contact Person</label>
                                    <input
                                        v-model="form.contact_person"
                                        type="text"
                                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50"
                                        placeholder="Contact name"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Supplier Name *</label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    required
                                    class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50"
                                    placeholder="Company name"
                                />
                                <p v-if="form.errors.name" class="mt-1 text-sm text-red-400">{{ form.errors.name }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Address</label>
                                <div class="relative group">
                                    <textarea
                                        v-model="form.address"
                                        rows="2"
                                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50"
                                        placeholder="Street address"
                                    />
                                    <button 
                                        type="button" 
                                        @click="openMap"
                                        class="absolute right-2 bottom-2 p-1.5 rounded-lg bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-600 hover:text-slate-900 dark:text-white transition-colors flex items-center gap-1 text-xs font-bold shadow-lg"
                                    >
                                        <MapIcon class="h-4 w-4 text-blue-400" />
                                        Pick from Map
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">City</label>
                                    <input
                                        v-model="form.city"
                                        type="text"
                                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50"
                                        placeholder="City"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Phone</label>
                                    <input
                                        v-model="form.phone"
                                        type="text"
                                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50"
                                        placeholder="Phone number"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Fax</label>
                                    <input
                                        v-model="form.fax"
                                        type="text"
                                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50"
                                        placeholder="Fax number"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Email</label>
                                    <input
                                        v-model="form.email"
                                        type="email"
                                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50"
                                        placeholder="supplier@company.com"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="flex items-center gap-2 text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">
                                        Tax ID
                                        <div class="group relative cursor-help">
                                            <QuestionMarkCircleIcon class="h-4 w-4 text-slate-500 hover:text-blue-400 transition-colors" />
                                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-64 p-3 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-xl opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50">
                                                <p class="text-xs text-slate-900 dark:text-white">
                                                    <span class="font-bold block mb-1">Nomor Identitas Pajak</span>
                                                    Nomor registrasi perpajakan perusahaan.
                                                </p>
                                                <div class="absolute bottom-0 left-1/2 -translate-x-1/2 translate-y-1/2 rotate-45 w-2 h-2 bg-slate-50 dark:bg-slate-800 border-r border-b border-slate-200 dark:border-slate-700"></div>
                                            </div>
                                        </div>
                                    </label>
                                    <input
                                        v-model="form.tax_id"
                                        type="text"
                                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50"
                                        placeholder="Tax ID"
                                    />
                                </div>
                                <div>
                                    <label class="flex items-center gap-2 text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">
                                        NPWP
                                        <div class="group relative cursor-help">
                                            <QuestionMarkCircleIcon class="h-4 w-4 text-slate-500 hover:text-blue-400 transition-colors" />
                                            <div class="absolute bottom-full right-0 mb-2 w-64 p-3 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-xl opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50">
                                                <p class="text-xs text-slate-900 dark:text-white">
                                                    <span class="font-bold block mb-1">Nomor Pokok Wajib Pajak</span>
                                                    Format: 15/16 digit angka.
                                                    <br><br>
                                                    <span class="text-slate-500 dark:text-slate-400">Contoh: 01.234.567.8-901.000</span>
                                                </p>
                                                <div class="absolute bottom-0 right-4 translate-y-1/2 rotate-45 w-2 h-2 bg-slate-50 dark:bg-slate-800 border-r border-b border-slate-200 dark:border-slate-700"></div>
                                            </div>
                                        </div>
                                    </label>
                                    <input
                                        v-model="form.npwp"
                                        type="text"
                                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50"
                                        placeholder="00.000.000.0-000.000"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Persons -->
                    <div class="rounded-2xl glass-card">
                        <div class="border-b border-slate-200 dark:border-slate-800 px-6 py-4 flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Contact Persons (PIC)</h2>
                            <button
                                type="button"
                                @click="addContact"
                                class="text-sm text-blue-400 hover:text-blue-300 transition-colors font-medium flex items-center gap-1"
                            >
                                <plus-icon class="h-4 w-4" /> Add Consumer
                            </button>
                        </div>
                        <div class="p-6 space-y-4">
                            <div v-if="form.contacts.length === 0" class="text-center text-slate-500 py-4 text-sm">
                                No contact persons added yet.
                            </div>
                            <div v-for="(contact, index) in form.contacts" :key="index" class="p-4 rounded-xl bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 space-y-3 relative group">
                                <button
                                    type="button"
                                    @click="removeContact(index)"
                                    class="absolute top-2 right-2 p-1 text-slate-500 hover:text-red-400 transition-colors opacity-0 group-hover:opacity-100"
                                    title="Remove Contact"
                                >
                                    <trash-icon class="h-4 w-4" />
                                </button>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <input
                                            v-model="contact.name"
                                            type="text"
                                            required
                                            class="block w-full rounded-lg border-0 bg-white dark:bg-slate-950 py-2 px-3 text-slate-900 dark:text-white text-sm placeholder:text-slate-600 focus:ring-1 focus:ring-blue-500/50"
                                            placeholder="PIC Name *"
                                        />
                                    </div>
                                    <div>
                                        <input
                                            v-model="contact.position"
                                            type="text"
                                            class="block w-full rounded-lg border-0 bg-white dark:bg-slate-950 py-2 px-3 text-slate-900 dark:text-white text-sm placeholder:text-slate-600 focus:ring-1 focus:ring-blue-500/50"
                                            placeholder="Position"
                                        />
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <input
                                            v-model="contact.phone"
                                            type="text"
                                            class="block w-full rounded-lg border-0 bg-white dark:bg-slate-950 py-2 px-3 text-slate-900 dark:text-white text-sm placeholder:text-slate-600 focus:ring-1 focus:ring-blue-500/50"
                                            placeholder="Mobile / Phone"
                                        />
                                    </div>
                                    <div>
                                        <input
                                            v-model="contact.email"
                                            type="email"
                                            class="block w-full rounded-lg border-0 bg-white dark:bg-slate-950 py-2 px-3 text-slate-900 dark:text-white text-sm placeholder:text-slate-600 focus:ring-1 focus:ring-blue-500/50"
                                            placeholder="Email Address"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Terms -->
                    <div class="rounded-2xl glass-card">
                        <div class="border-b border-slate-200 dark:border-slate-800 px-6 py-4">
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Payment Terms</h2>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="flex items-center gap-2 text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">
                                        Payment Terms *
                                        <div class="group relative cursor-help">
                                            <QuestionMarkCircleIcon class="h-4 w-4 text-slate-500 hover:text-blue-400 transition-colors" />
                                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-64 p-3 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-xl opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50">
                                                <p class="text-xs text-slate-900 dark:text-white">
                                                    <span class="font-bold block mb-1">Syarat Pembayaran</span>
                                                    Jangka waktu pembayaran tagihan.
                                                    <br><br>
                                                    <span class="text-slate-500 dark:text-slate-400">COD: Bayar saat terima barang.<br>NET 30: Bayar dalam 30 hari.</span>
                                                </p>
                                                <div class="absolute bottom-0 left-1/2 -translate-x-1/2 translate-y-1/2 rotate-45 w-2 h-2 bg-slate-50 dark:bg-slate-800 border-r border-b border-slate-200 dark:border-slate-700"></div>
                                            </div>
                                        </div>
                                    </label>
                                    <select
                                        v-model="form.payment_terms"
                                        required
                                        @change="onPaymentTermChange"
                                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                    >
                                        <option v-for="term in paymentTerms" :key="term.value" :value="term.value">
                                            {{ term.label }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Payment Days</label>
                                    <input
                                        v-model.number="form.payment_days"
                                        type="number"
                                        min="0"
                                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">

                    <!-- Subcontracting Config -->
                    <div class="rounded-2xl glass-card">
                        <div class="border-b border-slate-200 dark:border-slate-800 px-6 py-4">
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Subcontracting</h2>
                        </div>
                        <div class="p-6">
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Virtual Warehouse</label>
                            <select
                                v-model="form.subcontract_warehouse_id"
                                class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                            >
                                <option :value="null">-- No Virtual Warehouse --</option>
                                <option v-for="wh in subcontWarehouses" :key="wh.id" :value="wh.id">
                                    {{ wh.code }} - {{ wh.name }}
                                </option>
                            </select>
                            <p class="text-xs text-slate-500 mt-2">
                                Link this supplier to a Virtual Warehouse to enable automated <strong>Stock Transfer</strong> and <strong>Backflush</strong> features.
                            </p>
                        </div>
                    </div>
                    
                    <div class="rounded-2xl glass-card">
                        <div class="border-b border-slate-200 dark:border-slate-800 px-6 py-4">
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Status</h2>
                        </div>
                        <div class="p-6">
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input 
                                    v-model="form.is_active" 
                                    type="checkbox"
                                    class="rounded border-slate-600 bg-slate-50 dark:bg-slate-800 text-blue-500 focus:ring-blue-500/50"
                                />
                                <div class="flex-1">
                                    <span class="text-sm text-slate-600 dark:text-slate-300">Active</span>
                                    <p class="text-xs text-slate-500 mt-0.5">Uncheck untuk menonaktifkan supplier (Hidden).</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 hover:from-blue-500 hover:to-blue-400 disabled:opacity-50 transition-all"
                        >
                            {{ form.processing ? 'Saving...' : (isEditing ? 'Update Supplier' : 'Create Supplier') }}
                        </button>
                        <Link
                            href="/purchasing/suppliers"
                            class="w-full text-center rounded-xl bg-slate-50 dark:bg-slate-800 px-4 py-3 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-700 transition-colors"
                        >
                            Cancel
                        </Link>
                    </div>
                </div>
            </div>
        </form>

        <MapPicker 
            :show="showMap" 
            @close="showMap = false" 
            @confirm="onMapConfirm"
        />
    </AppLayout>
</template>



