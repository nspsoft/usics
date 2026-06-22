<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    PlusIcon, 
    MagnifyingGlassIcon, 
    ChevronRightIcon, 
    UserIcon,
    IdentificationIcon,
    EnvelopeIcon,
    PhoneIcon,
    MapPinIcon,
    BriefcaseIcon,
    BuildingOfficeIcon,
    CalendarDaysIcon,
    CurrencyDollarIcon,
    XMarkIcon,
    EllipsisVerticalIcon,
    ArrowDownTrayIcon,
    ArrowUpTrayIcon,
    DocumentArrowDownIcon
} from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';
import { formatNumber, formatCurrency } from '@/helpers';
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';

const props = defineProps({
    employees: Object,
    departments: Array,
    positions: Array,
    filters: Object,
});

const search = ref(props.filters.search);
const department_id = ref(props.filters.department_id);
const status = ref(props.filters.status);

watch([search, department_id, status], debounce(() => {
    router.get(route('hr.employees.index'), { 
        search: search.value, 
        department_id: department_id.value, 
        status: status.value 
    }, { preserveState: true, replace: true });
}, 300));

const showModal = ref(false);
const showImportModal = ref(false);
const editingEmployee = ref(null);

const importForm = useForm({
    file: null,
    overwrite: false,
});

const form = useForm({
    nik: '',
    full_name: '',
    email: '',
    phone: '',
    address: '',
    department_id: '',
    position_id: '',
    joining_date: new Date().toISOString().split('T')[0],
    employment_status: 'probation',
    basic_salary: 0,
    profile_picture: null,
});

const openModal = (employee = null) => {
    editingEmployee.value = employee;
    if (employee) {
        form.nik = employee.nik;
        form.full_name = employee.full_name;
        form.email = employee.email;
        form.phone = employee.phone;
        form.address = employee.address;
        form.department_id = employee.department_id;
        form.position_id = employee.position_id;
        form.joining_date = employee.joining_date;
        form.employment_status = employee.employment_status;
        form.basic_salary = employee.basic_salary;
        form.profile_picture = null;
    } else {
        form.reset();
        form.joining_date = new Date().toISOString().split('T')[0];
    }
    showModal.value = true;
};

const submitForm = () => {
    if (editingEmployee.value) {
        form.transform((data) => ({
            ...data,
            _method: 'put',
        })).post(route('hr.employees.update', editingEmployee.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            }
        });
    } else {
        form.post(route('hr.employees.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            }
        });
    }
};

const exportData = () => {
    const params = {
        department_id: department_id.value,
        status: status.value,
    };
    window.location.href = route('hr.employees.export', params);
};

const downloadTemplate = () => {
    window.location.href = route('hr.employees.template');
};

const handleImport = () => {
    importForm.post(route('hr.employees.import'), {
        onSuccess: () => {
            showImportModal.value = false;
            importForm.reset();
        },
    });
};

const getStatusBadge = (status) => {
    const badges = {
        permanent: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
        contract: 'bg-blue-500/10 text-blue-400 border-blue-500/20',
        probation: 'bg-amber-500/10 text-amber-400 border-amber-500/20',
        internship: 'bg-slate-500/10 text-slate-500 dark:text-slate-400 border-slate-500/20',
    };
    return badges[status] || 'bg-slate-500/10 text-slate-500 dark:text-slate-400 border-slate-500/20';
};
</script>

<template>
    <Head title="Employee Directory" />
    
    <AppLayout title="HR: Employee Directory">
        <div class="max-w-full px-4 sm:px-6 lg:px-8 mx-auto">
            <!-- Header & Actions -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Personnel Directory</h2>
                    <p class="text-sm text-slate-500 mt-1 uppercase tracking-widest font-bold font-mono">Employee Lifecycle & Structure</p>
                </div>
                
                <div class="flex items-center gap-3">
                    <button 
                        @click="exportData"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-white dark:bg-slate-900 px-5 py-3.5 text-sm font-bold text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-800 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-800 transition-all"
                    >
                        <ArrowDownTrayIcon class="h-5 w-5" />
                        Export
                    </button>

                    <button 
                        @click="showImportModal = true"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-white dark:bg-slate-900 px-5 py-3.5 text-sm font-bold text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-800 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-800 transition-all"
                    >
                        <ArrowUpTrayIcon class="h-5 w-5" />
                        Import
                    </button>

                    <button 
                        @click="openModal()"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-6 py-3.5 text-sm font-bold text-white shadow-xl shadow-indigo-900/20 hover:bg-indigo-500 transition-all hover:-translate-y-0.5"
                    >
                        <PlusIcon class="h-5 w-5" />
                        Onboard New Employee
                    </button>
                </div>
            </div>

            <!-- Search & Filters -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8 bg-white dark:bg-slate-950/50 p-4 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-lg">
                <div class="md:col-span-2 relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <MagnifyingGlassIcon class="h-5 w-5 text-slate-500" />
                    </div>
                    <input 
                        v-model="search"
                        type="text"
                        placeholder="Search by Name, NIK, or Email..."
                        class="block w-full rounded-2xl border-0 bg-white dark:bg-slate-950 py-3.5 pl-11 pr-4 text-slate-900 dark:text-white placeholder:text-slate-600 focus:ring-2 focus:ring-indigo-500/50 transition-all"
                    />
                </div>
                
                <select 
                    v-model="department_id"
                    class="block w-full rounded-2xl border-0 bg-white dark:bg-slate-950 py-3.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50 transition-all"
                >
                    <option value="">All Departments</option>
                    <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
                </select>

                <select 
                    v-model="status"
                    class="block w-full rounded-2xl border-0 bg-white dark:bg-slate-950 py-3.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50 transition-all"
                >
                    <option value="">All Statuses</option>
                    <option value="permanent">Permanent</option>
                    <option value="contract">Contract</option>
                    <option value="probation">Probation</option>
                    <option value="internship">Internship</option>
                </select>
            </div>

            <!-- Employee Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div 
                    v-for="employee in employees.data" 
                    :key="employee.id"
                    class="group relative glass-card rounded-[2rem] p-6 hover:border-indigo-500/30 transition-all shadow-sm hover:shadow-indigo-500/5"
                >
                    <div class="absolute top-4 right-4">
                        <button class="p-2 text-slate-600 hover:text-slate-900 dark:text-white transition-colors">
                            <EllipsisVerticalIcon class="h-6 w-6" />
                        </button>
                    </div>

                    <div class="flex items-center gap-5 mb-6">
                        <div v-if="employee.profile_picture" class="w-16 h-16 rounded-2xl overflow-hidden shadow-lg shadow-indigo-500/20 border border-slate-200 dark:border-slate-700">
                            <img :src="`/storage/${employee.profile_picture}`" alt="Avatar" class="w-full h-full object-cover" />
                        </div>
                        <div v-else class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-500 to-blue-600 flex items-center justify-center text-2xl font-black text-slate-900 dark:text-white shadow-lg shadow-indigo-500/20">
                            {{ employee.full_name.charAt(0).toUpperCase() }}
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white group-hover:text-indigo-400 transition-colors">{{ employee.full_name }}</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-mono font-bold tracking-widest mt-0.5">NIK: {{ employee.nik }}</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-center gap-3 text-slate-500 dark:text-slate-400">
                            <div class="p-2 rounded-lg bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50">
                                <BuildingOfficeIcon class="h-4 w-4" />
                            </div>
                            <span class="text-xs font-medium">{{ employee.department?.name }}</span>
                        </div>
                        <div class="flex items-center gap-3 text-slate-500 dark:text-slate-400">
                            <div class="p-2 rounded-lg bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50">
                                <BriefcaseIcon class="h-4 w-4" />
                            </div>
                            <span class="text-xs font-medium">{{ employee.position?.name }}</span>
                        </div>
                    </div>

                    <div class="mt-6 pt-6 border-t border-slate-200 dark:border-slate-800/50 flex items-center justify-between">
                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider border" :class="getStatusBadge(employee.employment_status)">
                            {{ employee.employment_status }}
                        </span>
                        
                        <div class="flex items-center gap-4">
                            <Link 
                                :href="route('hr.employees.face.show', employee.id)"
                                class="text-xs font-bold text-emerald-500 hover:text-emerald-400 dark:text-emerald-400 dark:hover:text-emerald-300 transition-colors flex items-center gap-1.5"
                            >
                                <IdentificationIcon class="h-4 w-4 shrink-0" />
                                Register Face
                            </Link>

                            <button 
                                @click="openModal(employee)"
                                class="text-xs font-bold text-indigo-400 hover:text-indigo-300 transition-colors flex items-center gap-1 group/btn"
                            >
                                View Profile
                                <ChevronRightIcon class="h-3 w-3 group-hover/btn:translate-x-1 transition-transform" />
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="!employees.data.length" class="md:col-span-2 lg:col-span-3 py-24 text-center glass-card border-dashed rounded-[3rem]">
                    <UserIcon class="h-16 w-16 text-slate-800 mx-auto mb-4" />
                    <h4 class="text-lg font-bold text-slate-500 dark:text-slate-400">No employees record found</h4>
                    <p class="text-sm text-slate-500 max-w-xs mx-auto mt-2 italic">Try adjusting your filters or hire new personnel to build your team.</p>
                </div>
            </div>

            <!-- Pagination -->
            <div class="mt-8 flex justify-center">
                <nav class="flex gap-1">
                    <Link
                        v-for="(link, i) in employees.links"
                        :key="i"
                        :href="link.url || '#'"
                        class="px-4 py-2 rounded-xl text-sm font-bold transition-all"
                        :class="[
                            link.active ? 'bg-indigo-600 text-slate-900 dark:text-white shadow-lg shadow-indigo-500/20' : 'text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 hover:text-slate-900 dark:text-white',
                            !link.url ? 'opacity-50 cursor-not-allowed' : ''
                        ]"
                        v-html="link.label"
                    />
                </nav>
            </div>
        </div>

        <!-- Import Modal -->
        <TransitionRoot as="template" :show="showImportModal">
            <Dialog as="div" class="relative z-[100]" @close="showImportModal = false">
                <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                    <div class="fixed inset-0 bg-white dark:bg-slate-950/80 backdrop-blur-sm transition-opacity" />
                </TransitionChild>

                <div class="fixed inset-0 z-10 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                        <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                            <DialogPanel class="relative transform overflow-hidden rounded-[2rem] glass-card text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md">
                                <form @submit.prevent="handleImport">
                                    <div class="px-8 py-6 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center bg-white dark:bg-slate-950/50">
                                        <DialogTitle as="h3" class="text-xl font-bold text-slate-900 dark:text-white">
                                            Import Employees
                                        </DialogTitle>
                                        <button @click="showImportModal = false" type="button" class="text-slate-500 hover:text-slate-900 dark:text-white transition-colors">
                                            <XMarkIcon class="h-7 w-7" />
                                        </button>
                                    </div>

                                    <div class="p-8 space-y-6">
                                        <div class="p-4 rounded-2xl bg-indigo-500/5 border border-indigo-500/10 space-y-3">
                                            <h4 class="text-xs font-bold text-indigo-400 uppercase tracking-widest flex items-center gap-2">
                                                <DocumentArrowDownIcon class="h-4 w-4" />
                                                Instructions
                                            </h4>
                                            <p class="text-xs text-slate-500 leading-relaxed italic">
                                                Please use our standard template to ensure data compatibility. You can fill the Department and Position by their names.
                                            </p>
                                            <button 
                                                type="button"
                                                @click="downloadTemplate"
                                                class="text-xs font-bold text-indigo-500 hover:text-indigo-400 underline underline-offset-4"
                                            >
                                                Download Excel Template
                                            </button>
                                        </div>

                                        <div class="space-y-4">
                                            <div class="space-y-2">
                                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Select Excel File</label>
                                                <input 
                                                    type="file" 
                                                    @input="importForm.file = $event.target.files[0]"
                                                    class="block w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:bg-indigo-600 file:text-white hover:file:bg-indigo-500 transition-all cursor-pointer" 
                                                    accept=".xlsx, .xls, .csv"
                                                />
                                                <p v-if="importForm.errors.file" class="text-[10px] text-red-500 italic">{{ importForm.errors.file }}</p>
                                            </div>

                                            <div class="flex items-center gap-3 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                                                <input 
                                                    v-model="importForm.overwrite"
                                                    type="checkbox" 
                                                    id="overwrite"
                                                    class="h-5 w-5 rounded-lg border-slate-300 text-indigo-600 focus:ring-indigo-500 transition-all"
                                                />
                                                <label for="overwrite" class="text-xs font-medium text-slate-700 dark:text-slate-300 cursor-pointer">
                                                    Overwrite existing data (Match by NIK)
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="px-8 py-6 bg-white dark:bg-slate-950 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-4">
                                        <button @click="showImportModal = false" type="button" class="px-6 py-2.5 text-sm font-medium text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white transition-colors">Cancel</button>
                                        <button 
                                            type="submit" 
                                            :disabled="importForm.processing || !importForm.file"
                                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-8 py-2.5 text-sm font-bold text-white shadow-lg shadow-indigo-900/20 hover:bg-indigo-500 disabled:opacity-50 transition-all"
                                        >
                                            Start Import
                                        </button>
                                    </div>
                                </form>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>

        <!-- Hiring/Edit Modal -->
        <TransitionRoot as="template" :show="showModal">
            <Dialog as="div" class="relative z-[100]" @close="showModal = false">
                <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                    <div class="fixed inset-0 bg-white dark:bg-slate-950/80 backdrop-blur-sm transition-opacity" />
                </TransitionChild>

                <div class="fixed inset-0 z-10 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                        <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                            <DialogPanel class="relative transform overflow-hidden rounded-[2rem] glass-card text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-2xl">
                                <form @submit.prevent="submitForm">
                                    <div class="px-8 py-6 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center bg-white dark:bg-slate-950/50">
                                        <DialogTitle as="h3" class="text-xl font-bold text-slate-900 dark:text-white">
                                            {{ editingEmployee ? 'Edit Personnel Data' : 'New Employee Onboarding' }}
                                        </DialogTitle>
                                        <button @click="showModal = false" type="button" class="text-slate-500 hover:text-slate-900 dark:text-white transition-colors">
                                            <XMarkIcon class="h-7 w-7" />
                                        </button>
                                    </div>

                                    <div class="p-8 space-y-6 max-h-[70vh] overflow-y-auto custom-scrollbar">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div class="space-y-2">
                                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">NIK (Employee ID)</label>
                                                <input v-model="form.nik" type="text" class="block w-full rounded-xl border-0 bg-white dark:bg-slate-950 py-3 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50 transition-all font-mono" />
                                                <p v-if="form.errors.nik" class="text-[10px] text-red-500 italic">{{ form.errors.nik }}</p>
                                            </div>
                                            <div class="space-y-2">
                                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Full Name</label>
                                                <input v-model="form.full_name" type="text" class="block w-full rounded-xl border-0 bg-white dark:bg-slate-950 py-3 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50 transition-all" />
                                            </div>
                                            
                                            <div class="space-y-2">
                                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Email Address</label>
                                                <input v-model="form.email" type="email" class="block w-full rounded-xl border-0 bg-white dark:bg-slate-950 py-3 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50 transition-all" />
                                            </div>
                                            <div class="space-y-2">
                                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Phone Number</label>
                                                <input v-model="form.phone" type="text" class="block w-full rounded-xl border-0 bg-white dark:bg-slate-950 py-3 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50 transition-all" />
                                            </div>

                                            <div class="space-y-2">
                                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Department</label>
                                                <select v-model="form.department_id" class="block w-full rounded-xl border-0 bg-white dark:bg-slate-950 py-3 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50 transition-all">
                                                    <option value="">Select Department</option>
                                                    <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
                                                </select>
                                            </div>
                                            <div class="space-y-2">
                                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Position</label>
                                                <select v-model="form.position_id" class="block w-full rounded-xl border-0 bg-white dark:bg-slate-950 py-3 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50 transition-all">
                                                    <option value="">Select Position</option>
                                                    <option v-for="pos in positions" :key="pos.id" :value="pos.id">{{ pos.name }}</option>
                                                </select>
                                            </div>

                                            <div class="space-y-2">
                                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Joining Date</label>
                                                <input v-model="form.joining_date" type="date" class="block w-full rounded-xl border-0 bg-white dark:bg-slate-950 py-3 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50 transition-all" />
                                            </div>
                                            <div class="space-y-2">
                                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Employment Status</label>
                                                <select v-model="form.employment_status" class="block w-full rounded-xl border-0 bg-white dark:bg-slate-950 py-3 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50 transition-all">
                                                    <option value="permanent">Permanent</option>
                                                    <option value="contract">Contract</option>
                                                    <option value="probation">Probation</option>
                                                    <option value="internship">Internship</option>
                                                </select>
                                            </div>

                                            <div class="space-y-2 md:col-span-2">
                                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Basic Salary (Monthly)</label>
                                                <div class="relative">
                                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                        <span class="text-xs text-slate-500 font-bold">Rp</span>
                                                    </div>
                                                    <input v-model="form.basic_salary" type="number" class="block w-full rounded-xl border-0 bg-white dark:bg-slate-950 py-3 pl-12 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50 transition-all font-mono font-bold" />
                                                </div>
                                            </div>

                                            <div class="space-y-2 md:col-span-2">
                                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Address</label>
                                                <textarea v-model="form.address" rows="3" class="block w-full rounded-xl border-0 bg-white dark:bg-slate-950 py-3 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50 transition-all"></textarea>
                                            </div>

                                            <div class="space-y-2 md:col-span-2">
                                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Profile Picture (Optional)</label>
                                                <input 
                                                    type="file" 
                                                    @input="form.profile_picture = $event.target.files[0]"
                                                    class="block w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:bg-indigo-600 file:text-white hover:file:bg-indigo-500 transition-all cursor-pointer" 
                                                    accept="image/*"
                                                />
                                                <p v-if="form.errors.profile_picture" class="text-[10px] text-red-500 italic">{{ form.errors.profile_picture }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="px-8 py-6 bg-white dark:bg-slate-950 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-4">
                                        <button @click="showModal = false" type="button" class="px-6 py-2.5 text-sm font-medium text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white transition-colors">Cancel</button>
                                        <button 
                                            type="submit" 
                                            :disabled="form.processing"
                                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-8 py-2.5 text-sm font-bold text-slate-900 dark:text-white shadow-lg shadow-indigo-900/20 hover:bg-indigo-500 disabled:opacity-50 transition-all"
                                        >
                                            {{ editingEmployee ? 'Save Changes' : 'Hire Employee' }}
                                        </button>
                                    </div>
                                </form>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>
    </AppLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #1e293b;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #334155;
}
</style>



