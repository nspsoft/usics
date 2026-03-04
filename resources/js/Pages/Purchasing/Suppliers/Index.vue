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
    BuildingOffice2Icon,
    PhoneIcon,
    EnvelopeIcon,
    ArrowDownIcon,
    BarsArrowUpIcon,
    BarsArrowDownIcon,
} from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';
import MapPicker from '@/Components/MapPicker.vue';

const props = defineProps({
    suppliers: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const sortField = ref(props.filters.sort || 'name');
const sortDirection = ref(props.filters.direction || 'asc');
const showImportModal = ref(false);
const importType = ref('supplier'); // 'supplier' or 'contact'
const includeData = ref(false);
const importFile = ref(null);
const overwriteData = ref(false);
const importing = ref(false);

const applyFilters = debounce(() => {
    router.get('/purchasing/suppliers', {
        search: search.value || undefined,
        sort: sortField.value,
        direction: sortDirection.value,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

const setSort = (field, direction) => {
    sortField.value = field;
    sortDirection.value = direction;
    applyFilters();
};

watch(search, () => {
    applyFilters();
});

const deleteSupplier = (supplier) => {
    if (confirm(`Are you sure you want to delete "${supplier.name}"?`)) {
        router.delete(`/purchasing/suppliers/${supplier.id}`);
    }
};

const handleImport = () => {
    if (!importFile.value) return;
    
    importing.value = true;
    router.post('/purchasing/suppliers-import', {
        file: importFile.value,
        overwrite: overwriteData.value
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
    router.post('/purchasing/suppliers-contacts-import', {
        file: importFile.value,
        overwrite: overwriteData.value
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

const showMap = ref(false);
const mapInitialAddress = ref('');

const openMap = (address, city) => {
    if (!address) return;
    mapInitialAddress.value = `${address}, ${city || ''}`;
    showMap.value = true;
};
</script>

<template>
    <Head title="Suppliers" />
    
    <AppLayout title="Suppliers">
        <!-- Header Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div class="relative flex-1 sm:max-w-md">
                <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500" />
                <input
                    v-model="search"
                    type="search"
                    placeholder="Search suppliers..."
                    class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 py-2.5 pl-10 pr-4 text-sm text-slate-900 dark:text-white placeholder:text-slate-500 focus:bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-blue-500/50 transition-all"
                />
            </div>
            
            <div class="flex items-center gap-3">
                <div class="flex gap-2">
                    <!-- Sort Dropdown -->
                    <div class="relative group">
                        <button class="inline-flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 px-4 py-2.5 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors">
                            <BarsArrowUpIcon v-if="sortDirection === 'asc'" class="h-5 w-5" />
                            <BarsArrowDownIcon v-else class="h-5 w-5" />
                            Sort
                        </button>
                        <div class="absolute right-0 pt-2 w-48 hidden group-hover:block z-20">
                            <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700 rounded-xl shadow-xl overflow-hidden py-1">
                                <button @click="setSort('name', 'asc')" class="block w-full text-left px-4 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-800" :class="sortField === 'name' && sortDirection === 'asc' ? 'text-blue-600 bg-blue-50 dark:text-blue-400 dark:bg-blue-900/20' : 'text-slate-600 dark:text-slate-300'">Name (A-Z)</button>
                                <button @click="setSort('name', 'desc')" class="block w-full text-left px-4 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-800" :class="sortField === 'name' && sortDirection === 'desc' ? 'text-blue-600 bg-blue-50 dark:text-blue-400 dark:bg-blue-900/20' : 'text-slate-600 dark:text-slate-300'">Name (Z-A)</button>
                                <div class="h-px bg-slate-100 dark:bg-slate-800 my-1"></div>
                                <button @click="setSort('code', 'asc')" class="block w-full text-left px-4 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-800" :class="sortField === 'code' && sortDirection === 'asc' ? 'text-blue-600 bg-blue-50 dark:text-blue-400 dark:bg-blue-900/20' : 'text-slate-600 dark:text-slate-300'">Code (A-Z)</button>
                                <button @click="setSort('code', 'desc')" class="block w-full text-left px-4 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-800" :class="sortField === 'code' && sortDirection === 'desc' ? 'text-blue-600 bg-blue-50 dark:text-blue-400 dark:bg-blue-900/20' : 'text-slate-600 dark:text-slate-300'">Code (Z-A)</button>
                                <div class="h-px bg-slate-100 dark:bg-slate-800 my-1"></div>
                                <button @click="setSort('is_active', 'desc')" class="block w-full text-left px-4 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-800" :class="sortField === 'is_active' && sortDirection === 'desc' ? 'text-blue-600 bg-blue-50 dark:text-blue-400 dark:bg-blue-900/20' : 'text-slate-600 dark:text-slate-300'">Active First</button>
                                <button @click="setSort('is_active', 'asc')" class="block w-full text-left px-4 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-800" :class="sortField === 'is_active' && sortDirection === 'asc' ? 'text-blue-600 bg-blue-50 dark:text-blue-400 dark:bg-blue-900/20' : 'text-slate-600 dark:text-slate-300'">Inactive First</button>
                            </div>
                        </div>
                    </div>

                    <div class="relative group">
                        <button class="inline-flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 px-4 py-2.5 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors">
                            <ArrowDownTrayIcon class="h-5 w-5" />
                            Export
                        </button>
                        <div class="absolute right-0 pt-2 w-48 hidden group-hover:block z-20">
                            <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700 rounded-xl shadow-xl overflow-hidden">
                                <a href="/purchasing/suppliers-export" class="block px-4 py-3 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 hover:text-slate-900 dark:text-white">
                                    Export Suppliers
                                </a>
                                <a href="/purchasing/suppliers-contacts-export" class="block px-4 py-3 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 hover:text-slate-900 dark:text-white">
                                    Export Contacts
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="relative group">
                        <button class="inline-flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 px-4 py-2.5 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors">
                            <ArrowUpTrayIcon class="h-5 w-5" />
                            Import
                        </button>
                        <div class="absolute right-0 pt-2 w-48 hidden group-hover:block z-20">
                            <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700 rounded-xl shadow-xl overflow-hidden">
                                <button @click="showImportModal = true; importType = 'supplier'" class="block w-full text-left px-4 py-3 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 hover:text-slate-900 dark:text-white">
                                    Import Suppliers
                                </button>
                                <button @click="showImportModal = true; importType = 'contact'" class="block w-full text-left px-4 py-3 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 hover:text-slate-900 dark:text-white">
                                    Import Contacts
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <Link
                    href="/purchasing/suppliers/create"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 hover:from-blue-500 hover:to-blue-400 transition-all"
                >
                    <PlusIcon class="h-5 w-5" />
                    Add Supplier
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
                            Import {{ importType === 'supplier' ? 'Suppliers' : 'Contacts' }}
                        </h3>
                        <button @click="showImportModal = false" class="text-slate-500 hover:text-slate-900 dark:text-white transition-colors">
                            <XMarkIcon class="h-6 w-6" />
                        </button>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Excel/CSV File</label>
                        <div class="flex flex-col items-center justify-center border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-xl p-8 hover:border-emerald-500/50 transition-colors relative">
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
                                    :href="importType === 'supplier' 
                                        ? (includeData ? '/purchasing/suppliers-template?with_data=1' : '/purchasing/suppliers-template') 
                                        : (includeData ? '/purchasing/suppliers-contacts-template?with_data=1' : '/purchasing/suppliers-contacts-template')"
                                    target="_blank"
                                    class="text-xs text-blue-400 hover:text-blue-300 underline"
                                >
                                    Download Template
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Import Options -->
                    <div class="bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-100 dark:border-indigo-800 rounded-xl p-4 mb-6 relative overflow-hidden">
                        <div class="absolute right-0 top-0 w-24 h-24 bg-indigo-500/10 rounded-full blur-xl -mr-10 -mt-10"></div>
                        
                        <h4 class="text-sm font-bold text-indigo-900 dark:text-indigo-300 mb-3 flex items-center gap-2">
                            <DocumentCheckIcon class="h-4 w-4" />
                            Import Options
                        </h4>
                        
                        <div class="space-y-4">
                            <!-- Include Data Check -->
                            <label class="flex items-start gap-3 cursor-pointer group">
                                <div class="pt-0.5">
                                    <input 
                                        type="checkbox" 
                                        v-model="includeData"
                                        class="w-4 h-4 rounded border-indigo-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 transition-all dark:bg-slate-800 dark:border-slate-600"
                                    >
                                </div>
                                <div class="flex-1">
                                    <span class="block text-sm font-medium text-slate-700 dark:text-slate-200 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">Include Existing {{ importType === 'supplier' ? 'Suppliers' : 'Contacts' }} in Template</span>
                                    <span class="block text-xs text-slate-500 dark:text-slate-400 mt-0.5">Download template terisi data {{ importType === 'supplier' ? 'Profil Supplier' : 'Kontak PIC' }} dari database untuk mass-update/overwrite.</span>
                                </div>
                            </label>
                            
                            <!-- Overwrite Data Check -->
                            <label class="flex items-start gap-3 cursor-pointer group">
                                <div class="pt-0.5">
                                    <input 
                                        type="checkbox" 
                                        v-model="overwriteData"
                                        class="w-4 h-4 rounded border-amber-300 text-amber-600 shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200 focus:ring-opacity-50 transition-all dark:bg-slate-800 dark:border-slate-600"
                                    >
                                </div>
                                <div class="flex-1">
                                    <span class="block text-sm font-medium text-slate-700 dark:text-slate-200 group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors">Overwrite Existing {{ importType === 'supplier' ? 'Profiles' : 'Contacts' }} Data</span>
                                    <span class="block text-xs text-slate-500 dark:text-slate-400 mt-0.5 mb-2">Jika dicentang, import akan merevisi data yang sudah ada di database jika {{ importType === 'supplier' ? 'Kode (Code)' : 'Internal ID / Nama' }} match.</span>
                                    
                                    <!-- Warning Banner -->
                                    <div v-show="overwriteData" class="mt-2 text-xs p-2 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800/50 flex items-start gap-2">
                                        <ShieldCheckIcon class="h-4 w-4 shrink-0 mt-0.5" />
                                        <span><strong>Peringatan!</strong> Setuju untuk mereplace spesifikasi {{ importType === 'supplier' ? 'perusahaan dan term payout supplier' : 'informasi contact detail PIC' }} secara menyeluruh.</span>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button 
                            @click="importType === 'supplier' ? handleImport() : handleImportContacts()"
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

        <!-- Suppliers Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div 
                v-for="supplier in suppliers.data" 
                :key="supplier.id"
                class="rounded-2xl glass-card overflow-hidden hover:border-slate-200 dark:border-slate-700 transition-all"
            >
                <div class="p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-600/20 to-teal-600/20 border border-emerald-500/30">
                                <BuildingOffice2Icon class="h-6 w-6 text-emerald-400" />
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ supplier.name }}</h3>
                                <p class="text-sm text-slate-500">{{ supplier.code }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2 mb-4">
                        <div v-if="supplier.contact_person" class="text-sm text-slate-500 dark:text-slate-400">
                            <span class="font-medium">Contact:</span> {{ supplier.contact_person }}
                        </div>
                        <div v-if="supplier.phone" class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                            <PhoneIcon class="h-4 w-4" />
                            {{ supplier.phone }}
                        </div>
                        <div v-if="supplier.email" class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                            <EnvelopeIcon class="h-4 w-4" />
                            {{ supplier.email }}
                        </div>
                        <div class="text-sm text-slate-500">
                            Payment: {{ supplier.payment_terms }} ({{ supplier.payment_days }} days)
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-4 border-t border-slate-200 dark:border-slate-800">
                        <span 
                            class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"
                            :class="supplier.is_active 
                                ? 'bg-emerald-500/20 text-emerald-400' 
                                : 'bg-slate-500/20 text-slate-500 dark:text-slate-400'"
                        >
                            {{ supplier.is_active ? 'Active' : 'Inactive' }}
                        </span>
                        <div class="flex items-center gap-1">
                            <button
                                v-if="supplier.address"
                                @click="openMap(supplier.address, supplier.city)"
                                class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-emerald-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors"
                                title="View on Map"
                            >
                                <MapPinIcon class="h-4 w-4" />
                            </button>
                            <Link
                                :href="`/purchasing/suppliers/${supplier.id}`"
                                class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors"
                            >
                                <EyeIcon class="h-4 w-4" />
                            </Link>
                            <Link
                                :href="`/purchasing/suppliers/${supplier.id}/edit`"
                                class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-blue-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors"
                            >
                                <PencilSquareIcon class="h-4 w-4" />
                            </Link>
                            <button
                                @click="deleteSupplier(supplier)"
                                class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-red-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors"
                            >
                                <TrashIcon class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div 
                v-if="suppliers.data.length === 0"
                class="col-span-full rounded-2xl glass-card p-12 text-center"
            >
                <BuildingOffice2Icon class="mx-auto h-12 w-12 text-slate-600" />
                <h3 class="mt-2 text-sm font-medium text-slate-600 dark:text-slate-300">No suppliers found</h3>
                <p class="mt-1 text-sm text-slate-500">Get started by adding a new supplier.</p>
                <div class="mt-4">
                    <Link
                        href="/purchasing/suppliers/create"
                        class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 transition-colors"
                    >
                        <PlusIcon class="h-4 w-4" />
                        Add Supplier
                    </Link>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="suppliers.last_page > 1" class="mt-6 flex items-center justify-center gap-2">
            <Link
                v-for="link in suppliers.links"
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

        <!-- Feature Guide -->
        <div class="mt-12">
            <div class="flex items-center gap-2 mb-4 px-1">
                <div class="h-px flex-1 bg-slate-50 dark:bg-slate-800"></div>
                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Supplier Management Guide</span>
                <div class="h-px flex-1 bg-slate-50 dark:bg-slate-800"></div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-blue-500/10 text-blue-400">
                            <BuildingOffice2Icon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Centralized Registry</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Maintain a complete database of your vendor partners, including <strong>Tax IDs (NPWP)</strong> and primary office locations.
                    </p>
                </div>

                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-400">
                            <UserGroupIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Multiple Contacts</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Store multiple <strong>Contact Persons</strong> per supplier to ensure your procurement team always reaches the right person for POs or Billing.
                    </p>
                </div>

                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-amber-500/10 text-amber-400">
                            <ShieldCheckIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Payment Terms</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Define <strong>Credit Terms</strong> (e.g., NET 30) for each supplier. These terms will auto-populate when creating new Purchase Orders.
                    </p>
                </div>
                
                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-purple-500/10 text-purple-400">
                            <ArrowUpTrayIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Mass Import</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Use our <strong>Smart Import</strong> to bring in hundreds of suppliers or contacts at once. Always download the latest template first.
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>



