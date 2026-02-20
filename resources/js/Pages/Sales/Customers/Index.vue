<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    PlusIcon,
    MagnifyingGlassIcon,
    PencilSquareIcon,
    TrashIcon,
    EyeIcon,
    UserGroupIcon,
    PhoneIcon,
    EnvelopeIcon,
    StarIcon,
    ArrowDownTrayIcon,
    ArrowUpTrayIcon,
    XMarkIcon,
    MapPinIcon,
    UserIcon,
    CreditCardIcon,
    ShieldCheckIcon,
} from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';
import MapPicker from '@/Components/MapPicker.vue';

const props = defineProps({
    customers: Object,
    filters: Object,
    customerTypes: Array,
});

const search = ref(props.filters.search || '');
const selectedType = ref(props.filters.type || '');
const showImportModal = ref(false);
const importType = ref('customer'); // 'customer' or 'contact'
const importFile = ref(null);
const importing = ref(false);

const applyFilters = debounce(() => {
    router.get('/sales/customers', {
        search: search.value || undefined,
        type: selectedType.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, selectedType], applyFilters);

const deleteCustomer = (customer) => {
    if (confirm(`Are you sure you want to delete "${customer.name}"?`)) {
        router.delete(`/sales/customers/${customer.id}`);
    }
};

const handleImport = () => {
    if (!importFile.value) return;
    
    importing.value = true;
    router.post('/sales/customers-import', {
        file: importFile.value
    }, {
        onSuccess: () => {
            showImportModal.value = false;
            importFile.value = null;
            importing.value = false;
        },
        onError: () => {
            importing.value = false;
        },
        forceFormData: true,
    });
};

const handleImportContacts = () => {
    if (!importFile.value) return;
    
    importing.value = true;
    router.post('/sales/customers-contacts-import', {
        file: importFile.value
    }, {
        onSuccess: () => {
            showImportModal.value = false;
            importFile.value = null;
            importing.value = false;
        },
        onError: () => {
            importing.value = false;
        },
        forceFormData: true,
    });
};

const onFileChange = (e) => {
    importFile.value = e.target.files[0];
};

const getTypeBadge = (type) => {
    const badges = {
        regular: 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30',
        vip: 'bg-amber-500/20 text-amber-400 border-amber-500/30',
        wholesale: 'bg-purple-500/20 text-purple-400 border-purple-500/30',
    };
    return badges[type] || 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30';
};

const showMap = ref(false);
const mapInitialAddress = ref('');

const openMap = (address, city) => {
    if (!address) return;
    mapInitialAddress.value = `${address}, ${city || ''}`;
    showMap.value = true;
};
</script>

<template>
    <Head title="Customers" />
    
    <AppLayout title="Customers">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div class="flex items-center gap-3">
                <div class="relative flex-1 sm:w-80">
                    <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500" />
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search customers..."
                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800/50 py-2.5 pl-10 pr-4 text-sm text-slate-900 dark:text-white placeholder:text-slate-500 focus:bg-slate-50 dark:focus:bg-slate-800 focus:ring-2 focus:ring-blue-500/50 transition-all"
                    />
                </div>
                <select
                    v-model="selectedType"
                    class="rounded-xl border-0 bg-slate-50 dark:bg-slate-800/50 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                >
                    <option value="">All Types</option>
                    <option v-for="type in customerTypes" :key="type.value" :value="type.value">
                        {{ type.label }}
                    </option>
                </select>
            </div>
            
            <div class="flex items-center gap-3">
                <div class="relative group">
                    <button class="inline-flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-800/50 px-4 py-2.5 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <ArrowDownTrayIcon class="h-5 w-5" />
                        Export
                    </button>
                    <div class="absolute right-0 pt-2 w-48 hidden group-hover:block z-20">
                        <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700 rounded-xl shadow-xl overflow-hidden">
                            <a href="/sales/customers-export" class="block px-4 py-3 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 hover:text-slate-900 dark:text-white">
                                Export Customers
                            </a>
                            <a href="/sales/customers-contacts-export" class="block px-4 py-3 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 hover:text-slate-900 dark:text-white">
                                Export Contacts
                            </a>
                        </div>
                    </div>
                </div>

                <div class="relative group">
                    <button class="inline-flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-800/50 px-4 py-2.5 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <ArrowUpTrayIcon class="h-5 w-5" />
                        Import
                    </button>
                    <div class="absolute right-0 pt-2 w-48 hidden group-hover:block z-20">
                        <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700 rounded-xl shadow-xl overflow-hidden">
                            <button @click="showImportModal = true; importType = 'customer'" class="block w-full text-left px-4 py-3 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 hover:text-slate-900 dark:text-white">
                                Import Customers
                            </button>
                            <button @click="showImportModal = true; importType = 'contact'" class="block w-full text-left px-4 py-3 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 hover:text-slate-900 dark:text-white">
                                Import Contacts
                            </button>
                        </div>
                    </div>
                </div>
                <Link
                    :href="route('sales.customers.create')"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 hover:from-blue-500 hover:to-blue-400 transition-all"
                >
                    <PlusIcon class="h-5 w-5" />
                    Add Customer
                </Link>
            </div>
        </div>

        <!-- Import Modal -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showImportModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-white dark:bg-slate-950/80 backdrop-blur-sm">
                <div class="glass-card rounded-2xl w-full max-w-md p-6 shadow-2xl">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                            Import {{ importType === 'customer' ? 'Customers' : 'Contacts' }}
                        </h3>
                        <button @click="showImportModal = false" class="text-slate-500 hover:text-slate-900 dark:text-white transition-colors">
                            <XMarkIcon class="h-6 w-6" />
                        </button>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Excel/CSV File</label>
                        <div class="flex flex-col items-center justify-center border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-xl p-8 hover:border-violet-500/50 transition-colors relative">
                            <input 
                                type="file" 
                                @change="onFileChange"
                                class="absolute inset-0 opacity-0 cursor-pointer"
                                accept=".xlsx,.xls,.csv"
                            />
                            <ArrowUpTrayIcon class="h-10 w-10 text-slate-600 mb-2" />
                            <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">
                                {{ importFile ? importFile.name : 'Click or drag file to upload' }}
                            </p>
                            <p class="text-xs text-slate-600 mt-1">Maximum size: 2MB</p>
                            <div class="mt-4 z-10 relative">
                                <a 
                                    :href="importType === 'customer' ? '/sales/customers-template' : '/sales/customers-contacts-template'"
                                    class="text-xs text-blue-400 hover:text-blue-300 underline"
                                >
                                    Download Template
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button 
                            @click="importType === 'customer' ? handleImport() : handleImportContacts()"
                            :disabled="!importFile || importing"
                            class="flex-1 rounded-xl bg-blue-600 py-2.5 text-sm font-semibold text-white hover:bg-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                        >
                            {{ importing ? 'Importing...' : 'Start Import' }}
                        </button>
                        <button 
                            @click="showImportModal = false"
                            class="flex-1 rounded-xl bg-slate-50 dark:bg-slate-800 py-2.5 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-700 transition-all"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div 
                v-for="customer in customers.data" 
                :key="customer.id"
                class="glass-card rounded-2xl overflow-hidden"
            >
                <div class="p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-violet-600/20 to-purple-600/20 border border-violet-500/30">
                                <UserGroupIcon class="h-6 w-6 text-violet-400" />
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ customer.name }}</h3>
                                <p class="text-sm font-medium text-slate-700 dark:text-slate-400">{{ customer.code }}</p>
                            </div>
                        </div>
                        <span 
                            class="inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-medium"
                            :class="getTypeBadge(customer.customer_type)"
                        >
                            <StarIcon v-if="customer.customer_type === 'vip'" class="h-3 w-3" />
                            {{ customer.customer_type.toUpperCase() }}
                        </span>
                    </div>

                    <div class="space-y-2 mb-4">
                        <div v-if="customer.contact_person" class="text-sm text-slate-700 dark:text-slate-400">
                            <span class="font-medium">Contact:</span> {{ customer.contact_person }}
                        </div>
                        <div v-if="customer.phone" class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-400">
                            <PhoneIcon class="h-4 w-4 text-slate-500 dark:text-slate-500" />
                            {{ customer.phone }}
                        </div>
                        <div v-if="customer.email" class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-400">
                            <EnvelopeIcon class="h-4 w-4 text-slate-500 dark:text-slate-500" />
                            {{ customer.email }}
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-4 border-t border-slate-200 dark:border-slate-800">
                        <span 
                            class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"
                            :class="customer.is_active 
                                ? 'bg-emerald-500/20 text-emerald-400' 
                                : 'bg-slate-500/20 text-slate-500 dark:text-slate-400'"
                        >
                            {{ customer.is_active ? 'Active' : 'Inactive' }}
                        </span>
                        <div class="flex items-center gap-1">
                            <button
                                v-if="customer.address"
                                @click="openMap(customer.address, customer.city)"
                                class="p-2 rounded-lg text-slate-600 dark:text-slate-400 hover:text-emerald-500 dark:hover:text-emerald-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors"
                                title="View on Map"
                            >
                                <MapPinIcon class="h-4 w-4" />
                            </button>
                            <Link
                                :href="`/sales/customers/${customer.id}`"
                                class="p-2 rounded-lg text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors"
                            >
                                <EyeIcon class="h-4 w-4" />
                            </Link>
                            <Link
                                :href="`/sales/customers/${customer.id}/edit`"
                                class="p-2 rounded-lg text-slate-600 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors"
                            >
                                <PencilSquareIcon class="h-4 w-4" />
                            </Link>
                            <button
                                @click="deleteCustomer(customer)"
                                class="p-2 rounded-lg text-slate-600 dark:text-slate-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors"
                            >
                                <TrashIcon class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div 
                v-if="customers.data.length === 0"
                class="glass-card rounded-2xl p-12 text-center"
            >
                <UserGroupIcon class="mx-auto h-12 w-12 text-slate-600" />
                <h3 class="mt-2 text-sm font-medium text-slate-600 dark:text-slate-300">No customers found</h3>
                <p class="mt-1 text-sm text-slate-500">Get started by adding a new customer.</p>
                <div class="mt-4">
                    <Link
                        href="/sales/customers/create"
                        class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 transition-colors"
                    >
                        <PlusIcon class="h-4 w-4" />
                        Add Customer
                    </Link>
                </div>
            </div>
        </div>

        <div v-if="customers.last_page > 1" class="mt-6 flex items-center justify-center gap-2">
            <Link
                v-for="link in customers.links"
                :key="link.label"
                :href="link.url || '#'"
                class="px-3 py-1.5 rounded-lg text-sm transition-colors"
                :class="link.active 
                    ? 'bg-blue-600 text-slate-900 dark:text-white' 
                    : link.url 
                        ? 'text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 hover:text-slate-900 dark:text-white' 
                        : 'text-white cursor-not-allowed'"
                v-html="link.label"
            />
        </div>

        <MapPicker 
            :show="showMap" 
            :initial-address="mapInitialAddress"
            @close="showMap = false" 
        />

        <!-- Panduan Pelanggan & CRM -->
        <div class="mt-12">
            <div class="flex items-center gap-2 mb-4 px-1">
                <div class="h-px flex-1 bg-slate-50 dark:bg-slate-800"></div>
                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Panduan Pelanggan & CRM</span>
                <div class="h-px flex-1 bg-slate-50 dark:bg-slate-800"></div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="glass-card rounded-2xl p-5">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-violet-500/10 text-violet-400">
                            <UserGroupIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-900 dark:text-slate-200 text-sm">Segmentasi</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Tandai pelanggan sebagai <strong>VIP</strong>, <strong>Grosir</strong>, atau <strong>Reguler</strong>. Gunakan ini untuk menerapkan kebijakan harga khusus atau layanan prioritas.
                    </p>
                </div>

                <div class="glass-card rounded-2xl p-5">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-blue-500/10 text-blue-400">
                            <UserIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-900 dark:text-slate-200 text-sm">Hubungan Kunci</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Kelola banyak <strong>Stakeholders</strong> untuk setiap perusahaan. Sempurna untuk melacak manajer pembelian, tim keuangan, dan kontak gudang.
                    </p>
                </div>

                <div class="glass-card rounded-2xl p-5">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-amber-500/10 text-amber-400">
                            <CreditCardIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-900 dark:text-slate-200 text-sm">Limit Kredit</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Atur <strong>Syarat Pembayaran</strong> dan pantau limit untuk mencegah kelebihan kredit. Penting untuk menjaga arus kas sehat dan mengurangi risiko.
                    </p>
                </div>
                
                <div class="glass-card rounded-2xl p-5">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-400">
                            <ShieldCheckIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-900 dark:text-slate-200 text-sm">Log Aktivitas</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Setiap modifikasi atau impor <strong>diaudit</strong>. Pelanggan tidak aktif dapat diarsipkan untuk menjaga CRM Anda tetap bersih tanpa kehilangan data historis.
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>


