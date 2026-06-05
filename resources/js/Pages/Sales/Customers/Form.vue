<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    ArrowLeftIcon,
    DevicePhoneMobileIcon,
    EnvelopeIcon,
    UserIcon,
    MapPinIcon,
    IdentificationIcon,
    CreditCardIcon,
    BanknotesIcon,
    StarIcon,
    TrashIcon,
    QuestionMarkCircleIcon,
    MapIcon,
    PlusIcon,
    ArrowPathIcon
} from '@heroicons/vue/24/outline';
import MapPicker from '@/Components/MapPicker.vue';
import { ref } from 'vue';

const props = defineProps({
    customer: Object,
});

const isEditing = !!props.customer;

const form = useForm({
    code: props.customer?.code ?? '',
    name: props.customer?.name ?? '',
    contact_person: props.customer?.contact_person ?? '',
    email: props.customer?.email ?? '',
    phone: props.customer?.phone ?? '',
    address: props.customer?.address ?? '',
    latitude: props.customer?.latitude ?? '',
    longitude: props.customer?.longitude ?? '',
    city: props.customer?.city ?? '',
    state: props.customer?.state ?? '',
    postal_code: props.customer?.postal_code ?? '',
    country: props.customer?.country ?? 'ID',
    tax_id: props.customer?.tax_id ?? '',
    customer_type: props.customer?.customer_type ?? 'regular',
    payment_terms: props.customer?.payment_terms ?? 'net_30',
    payment_days: props.customer?.payment_days ?? 30,
    is_active: props.customer?.is_active ?? true,
    contacts: props.customer?.contacts ?? [],
    photo: null,
    _method: isEditing ? 'put' : 'post',
});

const photoPreview = ref(props.customer?.profile_photo_url || null);
const photoInput = ref(null);

const selectPhoto = () => {
    photoInput.value.click();
};

const updatePhotoPreview = () => {
    const photo = photoInput.value.files[0];
    if (!photo) return;
    
    form.photo = photo;

    const reader = new FileReader();
    reader.onload = (e) => {
        photoPreview.value = e.target.result;
    };
    reader.readAsDataURL(photo);
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
    if (location.latitude) form.latitude = location.latitude;
    if (location.longitude) form.longitude = location.longitude;
    if (location.city) form.city = location.city;
    if (location.state) form.state = location.state;
    if (location.postal_code) form.postal_code = location.postal_code;
};

const submit = () => {
    // We use post to handle multipart/form-data even for updates (legacy Inertia/Laravel behavior)
    // although newer Inertia supports patch/put with files, using _method spoofing is safer.
    if (isEditing) {
        form.post(route('sales.customers.update', props.customer.id), {
            forceFormData: true,
        });
    } else {
        form.post(route('sales.customers.store'));
    }
};
</script>

<template>
    <Head :title="isEditing ? 'Edit Customer' : 'Add New Customer'" />

    <AppLayout :title="isEditing ? 'Edit Customer' : 'Add New Customer'">
        <div class="max-w-4xl mx-auto">
            <div class="flex items-center gap-4 mb-8">
                <Link :href="route('sales.customers.index')" class="p-2 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white transition-colors">
                    <ArrowLeftIcon class="h-5 w-5" />
                </Link>
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">{{ isEditing ? 'Edit Customer' : 'Add New Customer' }}</h2>
                    <p class="text-slate-500 text-sm">Fill in the information below to {{ isEditing ? 'update' : 'create' }} a customer.</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Profile Photo -->
                <div class="rounded-3xl glass-card shadow-xl">
                    <div class="p-6 flex flex-col items-center gap-4">
                        <input type="file" class="hidden" ref="photoInput" @change="updatePhotoPreview">
                        <div class="relative group cursor-pointer" @click="selectPhoto">
                            <div class="h-28 w-28 rounded-3xl overflow-hidden border-2 border-dashed border-slate-300 dark:border-slate-700 group-hover:border-blue-500 transition-all flex items-center justify-center bg-slate-50 dark:bg-slate-800/50 shadow-inner">
                                <img v-if="photoPreview" :src="photoPreview" class="h-full w-full object-cover" />
                                <UserIcon v-else class="h-10 w-10 text-slate-400 group-hover:text-blue-400 transition-colors" />
                            </div>
                            <div class="absolute -bottom-2 -right-2 p-2 rounded-xl bg-blue-600 text-white shadow-lg shadow-blue-500/30 group-hover:scale-110 transition-transform">
                                <ArrowPathIcon class="h-4 w-4" />
                            </div>
                        </div>
                        <div class="text-center">
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Customer Profile Photo</p>
                            <p class="text-[10px] text-slate-400 mt-1">PNG, JPG up to 10MB</p>
                        </div>
                    </div>
                </div>

                <!-- Basic Info -->
                <div class="rounded-3xl glass-card shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/20">
                        <h3 class="flex items-center gap-2 text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            <UserIcon class="h-4 w-4 text-blue-400" />
                            Basic Information
                        </h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 text-sm font-medium text-slate-500 dark:text-slate-400 ml-1">
                                Customer Code
                                <div class="group relative cursor-help">
                                    <QuestionMarkCircleIcon class="h-4 w-4 text-slate-500 hover:text-blue-400 transition-colors" />
                                    <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-64 p-3 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-xl opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50">
                                        <p class="text-xs text-slate-900 dark:text-white">
                                            <span class="font-bold block mb-1">Kode Pelanggan</span>
                                            Kode unik identifikasi.
                                            <br><br>
                                            <span class="text-slate-500 dark:text-slate-400">Contoh: CUST-001, PT-ABC</span>
                                        </p>
                                        <div class="absolute bottom-0 left-1/2 -translate-x-1/2 translate-y-1/2 rotate-45 w-2 h-2 bg-slate-50 dark:bg-slate-800 border-r border-b border-slate-200 dark:border-slate-700"></div>
                                    </div>
                                </div>
                            </label>
                            <div class="relative group">
                                <IdentificationIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500 group-focus-within:text-blue-500 transition-colors" />
                                <input v-model="form.code" type="text" placeholder="CUST-0001" 
                                    class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl py-2.5 pl-10 pr-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 transition-all shadow-inner"
                                    :class="{ 'opacity-50 cursor-not-allowed': isEditing }"
                                    :disabled="isEditing" required />
                            </div>
                            <p v-if="form.errors.code" class="text-xs text-red-400 mt-1 ml-1">{{ form.errors.code }}</p>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 ml-1">Customer Name</label>
                            <div class="relative group">
                                <UserIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500 group-focus-within:text-blue-500 transition-colors" />
                                <input v-model="form.name" type="text" placeholder="PT. Customer Sejahtera" 
                                    class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl py-2.5 pl-10 pr-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 transition-all shadow-inner" required />
                            </div>
                            <p v-if="form.errors.name" class="text-xs text-red-400 mt-1 ml-1">{{ form.errors.name }}</p>
                        </div>
                    </div>
                </div>

                <!-- Contact & Address -->
                <div class="rounded-3xl glass-card shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/20">
                        <h3 class="flex items-center gap-2 text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            <DevicePhoneMobileIcon class="h-4 w-4 text-emerald-400" />
                            Contact & Address
                        </h3>
                    </div>
                    <div class="p-6 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 ml-1">Contact Person</label>
                                <div class="relative group">
                                    <UserIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500 group-focus-within:text-emerald-500 transition-colors" />
                                    <input v-model="form.contact_person" type="text" placeholder="John Doe" 
                                        class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl py-2.5 pl-10 pr-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/50 transition-all shadow-inner" />
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 ml-1">Phone</label>
                                <div class="relative group">
                                    <DevicePhoneMobileIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500 group-focus-within:text-emerald-500 transition-colors" />
                                    <input v-model="form.phone" type="text" placeholder="+62..." 
                                        class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl py-2.5 pl-10 pr-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/50 transition-all shadow-inner" />
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 ml-1">Email</label>
                                <div class="relative group">
                                    <EnvelopeIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500 group-focus-within:text-emerald-500 transition-colors" />
                                    <input v-model="form.email" type="email" placeholder="customer@example.com" 
                                        class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl py-2.5 pl-10 pr-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/50 transition-all shadow-inner" />
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 ml-1">City</label>
                                <div class="relative group">
                                    <MapPinIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500 group-focus-within:text-emerald-500 transition-colors" />
                                    <input v-model="form.city" type="text" placeholder="Jakarta" 
                                        class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl py-2.5 pl-10 pr-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/50 transition-all shadow-inner" />
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 ml-1">State / Province</label>
                                <div class="relative group">
                                    <MapPinIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500 group-focus-within:text-emerald-500 transition-colors" />
                                    <input v-model="form.state" type="text" placeholder="DKI Jakarta" 
                                        class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl py-2.5 pl-10 pr-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/50 transition-all shadow-inner" />
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 ml-1">Postal Code</label>
                                <div class="relative group">
                                    <MapPinIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500 group-focus-within:text-emerald-500 transition-colors" />
                                    <input v-model="form.postal_code" type="text" placeholder="12345" 
                                        class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl py-2.5 pl-10 pr-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/50 transition-all shadow-inner" />
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 ml-1">Address</label>
                            <div class="relative group">
                                <MapPinIcon class="absolute left-3 top-3 h-5 w-5 text-slate-500 group-focus-within:text-emerald-500 transition-colors" />
                                <textarea v-model="form.address" rows="3" placeholder="Full address..." 
                                    class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl py-2.5 pl-10 pr-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/50 transition-all shadow-inner"></textarea>
                                <button 
                                    type="button" 
                                    @click="openMap"
                                    class="absolute right-2 bottom-2 p-1.5 rounded-lg bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-600 hover:text-slate-900 dark:text-white transition-colors flex items-center gap-1 text-xs font-bold shadow-lg"
                                >
                                    <MapIcon class="h-4 w-4 text-emerald-400" />
                                    Pick from Map
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 ml-1">Latitude</label>
                                <input v-model="form.latitude" type="text" placeholder="-6.2088" 
                                    class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl py-2.5 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/50 transition-all shadow-inner" />
                            </div>
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 ml-1">Longitude</label>
                                <input v-model="form.longitude" type="text" placeholder="106.8456" 
                                    class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl py-2.5 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/50 transition-all shadow-inner" />
                            </div>
                        </div>
                    </div>
                </div>

                <MapPicker 
                    :show="showMap" 
                    @close="showMap = false" 
                    @confirm="onMapConfirm"
                />

                <!-- Additional Contacts -->
                <div class="rounded-3xl glass-card shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/20 flex items-center justify-between">
                        <h3 class="flex items-center gap-2 text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            <UserIcon class="h-4 w-4 text-purple-400" />
                            Additional Contacts
                        </h3>
                        <button type="button" @click="addContact" class="text-xs font-bold text-blue-400 hover:text-blue-300 flex items-center gap-1">
                            <PlusIcon class="h-4 w-4" />
                            Add Contact
                        </button>
                    </div>
                    <div class="p-6 space-y-4">
                        <div v-if="form.contacts.length === 0" class="text-center py-8 text-slate-500 italic">
                            No additional contacts added.
                        </div>
                        <div v-for="(contact, index) in form.contacts" :key="index" class="relative group bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 p-4 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-slate-600 transition-colors">
                            <button type="button" @click="removeContact(index)" class="absolute top-2 right-2 text-slate-500 hover:text-red-400 transition-colors">
                                <TrashIcon class="h-5 w-5" />
                            </button>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <label class="text-xs font-medium text-slate-500 dark:text-slate-400">Name</label>
                                    <input v-model="contact.name" type="text" placeholder="Contact Name" 
                                        class="w-full bg-white dark:bg-slate-950 border-slate-200 dark:border-slate-700 rounded-lg py-2 px-3 text-sm text-slate-900 dark:text-white focus:ring-1 focus:ring-purple-500" required />
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-medium text-slate-500 dark:text-slate-400">Position</label>
                                    <input v-model="contact.position" type="text" placeholder="Manager" 
                                        class="w-full bg-white dark:bg-slate-950 border-slate-200 dark:border-slate-700 rounded-lg py-2 px-3 text-sm text-slate-900 dark:text-white focus:ring-1 focus:ring-purple-500" />
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-medium text-slate-500 dark:text-slate-400">Phone</label>
                                    <input v-model="contact.phone" type="text" placeholder="+62..." 
                                        class="w-full bg-white dark:bg-slate-950 border-slate-200 dark:border-slate-700 rounded-lg py-2 px-3 text-sm text-slate-900 dark:text-white focus:ring-1 focus:ring-purple-500" />
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-medium text-slate-500 dark:text-slate-400">Email</label>
                                    <input v-model="contact.email" type="email" placeholder="email@example.com" 
                                        class="w-full bg-white dark:bg-slate-950 border-slate-200 dark:border-slate-700 rounded-lg py-2 px-3 text-sm text-slate-900 dark:text-white focus:ring-1 focus:ring-purple-500" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Financial & Tax -->
                <div class="rounded-3xl glass-card shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/20">
                        <h3 class="flex items-center gap-2 text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            <CreditCardIcon class="h-4 w-4 text-amber-400" />
                            Financial & Tax Information
                        </h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2 md:col-span-2">
                            <label class="flex items-center gap-2 text-sm font-medium text-slate-500 dark:text-slate-400 ml-1">
                                Customer Type
                                <div class="group relative cursor-help">
                                    <QuestionMarkCircleIcon class="h-4 w-4 text-slate-500 hover:text-blue-400 transition-colors" />
                                    <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-64 p-3 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-xl opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50">
                                        <p class="text-xs text-slate-900 dark:text-white">
                                            <span class="font-bold block mb-1">Tipe Pelanggan</span>
                                            Kategori pelanggan untuk level harga.
                                            <br><br>
                                            <span class="text-slate-500 dark:text-slate-400">VIP: Prioritas.<br>Wholesale: Grosir.</span>
                                        </p>
                                        <div class="absolute bottom-0 left-1/2 -translate-x-1/2 translate-y-1/2 rotate-45 w-2 h-2 bg-slate-50 dark:bg-slate-800 border-r border-b border-slate-200 dark:border-slate-700"></div>
                                    </div>
                                </div>
                            </label>
                            <div class="grid grid-cols-3 gap-3">
                                <label v-for="type in ['regular', 'vip', 'wholesale']" :key="type" 
                                    class="flex items-center justify-center gap-2 rounded-xl py-3 px-4 border text-sm font-bold cursor-pointer transition-all uppercase tracking-wider"
                                    :class="form.customer_type === type 
                                        ? 'bg-amber-500/20 border-amber-500 text-amber-400 shadow-lg shadow-amber-500/10' 
                                        : 'bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-500 hover:border-slate-500'">
                                    <input type="radio" :value="type" v-model="form.customer_type" class="hidden" />
                                    {{ type }}
                                </label>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="flex items-center gap-2 text-sm font-medium text-slate-500 dark:text-slate-400 ml-1">
                                Tax ID (NPWP)
                                <div class="group relative cursor-help">
                                    <QuestionMarkCircleIcon class="h-4 w-4 text-slate-500 hover:text-blue-400 transition-colors" />
                                    <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-64 p-3 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-xl opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50">
                                        <p class="text-xs text-slate-900 dark:text-white">
                                            <span class="font-bold block mb-1">Nomor Pokok Wajib Pajak</span>
                                            Format: 15/16 digit angka.
                                        </p>
                                        <div class="absolute bottom-0 left-1/2 -translate-x-1/2 translate-y-1/2 rotate-45 w-2 h-2 bg-slate-50 dark:bg-slate-800 border-r border-b border-slate-200 dark:border-slate-700"></div>
                                    </div>
                                </div>
                            </label>
                            <div class="relative group">
                                <IdentificationIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500 group-focus-within:text-amber-500 transition-colors" />
                                <input v-model="form.tax_id" type="text" placeholder="00.000..." 
                                    class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl py-2.5 pl-10 pr-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500/50 transition-all shadow-inner" />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="flex items-center gap-2 text-sm font-medium text-slate-500 dark:text-slate-400 ml-1">
                                Payment Term
                                <div class="group relative cursor-help">
                                    <QuestionMarkCircleIcon class="h-4 w-4 text-slate-500 hover:text-blue-400 transition-colors" />
                                    <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-64 p-3 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-xl opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50">
                                        <p class="text-xs text-slate-900 dark:text-white">
                                            <span class="font-bold block mb-1">Syarat Pembayaran</span>
                                            Jangka waktu pembayaran.
                                            <br><br>
                                            <span class="text-slate-500 dark:text-slate-400">COD: Bayar di tempat.<br>NET 30: Tempo 30 hari.</span>
                                        </p>
                                        <div class="absolute bottom-0 left-1/2 -translate-x-1/2 translate-y-1/2 rotate-45 w-2 h-2 bg-slate-50 dark:bg-slate-800 border-r border-b border-slate-200 dark:border-slate-700"></div>
                                    </div>
                                </div>
                            </label>
                            <div class="relative group">
                                <BanknotesIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500 group-focus-within:text-amber-500 transition-colors" />
                                <select v-model="form.payment_terms" 
                                    class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl py-2.5 pl-10 pr-10 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500/50 transition-all appearance-none cursor-pointer">
                                    <option value="cod">COD (Cash on Delivery)</option>
                                    <option value="net_15">NET 15</option>
                                    <option value="net_30">NET 30</option>
                                    <option value="net_60">NET 60</option>
                                </select>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="flex items-center gap-2 text-sm font-medium text-slate-500 dark:text-slate-400 ml-1">
                                Credit Limit / Payment Days
                                <div class="group relative cursor-help">
                                    <QuestionMarkCircleIcon class="h-4 w-4 text-slate-500 hover:text-blue-400 transition-colors" />
                                    <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-64 p-3 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-xl opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50">
                                        <p class="text-xs text-slate-900 dark:text-white">
                                            <span class="font-bold block mb-1">Hari Pembayaran</span>
                                            Batas waktu pembayaran (Top).
                                            <br><br>
                                            <span class="text-slate-500 dark:text-slate-400">Default: 30 Hari.</span>
                                        </p>
                                        <div class="absolute bottom-0 left-1/2 -translate-x-1/2 translate-y-1/2 rotate-45 w-2 h-2 bg-slate-50 dark:bg-slate-800 border-r border-b border-slate-200 dark:border-slate-700"></div>
                                    </div>
                                </div>
                            </label>
                            <div class="relative group">
                                <CreditCardIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500 group-focus-within:text-amber-500 transition-colors" />
                                <input v-model="form.payment_days" type="number" placeholder="30" 
                                    class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl py-2.5 pl-10 pr-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500/50 transition-all shadow-inner" />
                            </div>
                        </div>

                        <div class="flex items-center gap-3 md:pt-8 ml-1">
                            <button type="button" 
                                @click="form.is_active = !form.is_active"
                                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                :class="form.is_active ? 'bg-emerald-500' : 'bg-slate-700'">
                                <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                    :class="form.is_active ? 'translate-x-5' : 'translate-x-0'"></span>
                            </button>
                            <span class="text-sm font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wide cursor-help" title="Uncheck untuk menonaktifkan customer">Customer Active</span>
                            <p class="text-xs text-slate-500 ml-2">(Uncheck to Disable)</p>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex items-center justify-end gap-4 p-6 rounded-3xl glass-card shadow-xl">
                    <Link :href="route('sales.customers.index')" class="text-sm font-bold text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white transition-colors">
                        Cancel
                    </Link>
                    <button type="submit" :disabled="form.processing"
                        class="px-8 py-3 rounded-2xl bg-gradient-to-r from-blue-600 to-blue-500 text-white dark:text-white text-sm font-bold shadow-lg shadow-blue-500/25 hover:from-blue-500 hover:to-blue-400 disabled:opacity-50 transition-all flex items-center gap-2">
                        <span v-if="form.processing" class="h-4 w-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                        {{ isEditing ? 'Update Customer' : 'Save Customer' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>



