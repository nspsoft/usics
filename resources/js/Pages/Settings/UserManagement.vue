<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    UserGroupIcon, 
    PlusIcon, 
    PencilSquareIcon, 
    TrashIcon, 
    MagnifyingGlassIcon,
    ExclamationTriangleIcon,
    ShieldCheckIcon,
    XMarkIcon,
    BuildingOfficeIcon
} from '@heroicons/vue/24/outline';
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    users: Object,
    roles: Array,
    suppliers: Array,
    filters: Object,
});

const searchQuery = ref(props.filters?.search || '');
const perPage = ref(props.filters?.per_page || 10);
const userType = ref(props.filters?.type || 'all');
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteConfirm = ref(false);
const userToEdit = ref(null);
const userToDelete = ref(null);

const applyFilters = debounce(() => {
    router.get(route('settings.users'), {
        search: searchQuery.value || undefined,
        per_page: perPage.value || undefined,
        type: userType.value !== 'all' ? userType.value : undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([searchQuery, perPage, userType], applyFilters);

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: '',
    supplier_id: '',
});

const openCreateModal = () => {
    form.clearErrors();
    form.reset();
    showCreateModal.value = true;
};

const createUser = () => {
    form.post(route('settings.users.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
        },
    });
};

const openEditModal = (user) => {
    userToEdit.value = user;
    form.clearErrors();
    form.name = user.name;
    form.email = user.email;
    form.password = '';
    form.password_confirmation = '';
    form.role = user.roles && user.roles.length > 0 ? user.roles[0].name : '';
    form.supplier_id = user.supplier_id || '';
    showEditModal.value = true;
};

const updateUser = () => {
    form.put(route('settings.users.update', userToEdit.value.id), {
        onSuccess: () => {
            showEditModal.value = false;
        },
    });
};

const confirmDelete = (user) => {
    userToDelete.value = user;
    showDeleteConfirm.value = true;
};

const deleteUser = () => {
    router.delete(route('settings.users.destroy', userToDelete.value.id), {
        onSuccess: () => {
            showDeleteConfirm.value = false;
        },
    });
};
</script>

<template>
    <Head title="User Management" />
    
    <AppLayout title="User Management">
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-500/10 text-blue-400">
                        <UserGroupIcon class="h-7 w-7" />
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white">System Users</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Manage employee accounts and system access</p>
                    </div>
                </div>
                <button 
                    @click="openCreateModal"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white dark:text-white shadow-lg shadow-blue-900/20 hover:bg-blue-500 transition-all duration-200"
                >
                    <PlusIcon class="h-5 w-5" />
                    Add New User
                </button>
            </div>

            <!-- List Section -->
            <div class="rounded-2xl glass-card overflow-hidden shadow-xl">
                <!-- Toolbar -->
                <div class="p-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div class="relative max-w-sm w-full">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <MagnifyingGlassIcon class="h-5 w-5 text-slate-500" />
                        </div>
                        <input 
                            v-model="searchQuery"
                            type="text" 
                            placeholder="Search users..." 
                            class="block w-full pl-10 pr-3 py-2 glass-card rounded-xl text-sm text-slate-900 dark:text-white placeholder-slate-500 focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 transition-all"
                        />
                    </div>
                    
                    <div class="flex items-center gap-4">
                        <!-- User Type Filter -->
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Type:</span>
                            <select 
                                v-model="userType" 
                                class="block rounded-xl border-0 bg-white dark:bg-slate-950 py-1.5 px-3 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 transition-all cursor-pointer"
                            >
                                <option value="all">All Users</option>
                                <option value="internal">Internal</option>
                                <option value="supplier">Supplier</option>
                            </select>
                        </div>

                        <!-- Page Size Selector -->
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Per Page:</span>
                            <select 
                                v-model="perPage" 
                                class="block rounded-xl border-0 bg-white dark:bg-slate-950 py-1.5 px-3 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 transition-all cursor-pointer"
                            >
                                <option :value="5">5</option>
                                <option :value="10">10</option>
                                <option :value="25">25</option>
                                <option :value="50">50</option>
                                <option :value="100">100</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-900/50">
                                <th class="px-6 py-2.5 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">User</th>
                                <th class="px-6 py-2.5 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-2.5 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Access Info</th>
                                <th class="px-6 py-2.5 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50">
                            <tr v-for="user in users.data" :key="user.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors group">
                                <td class="px-6 py-2">
                                    <div class="flex items-center gap-3">
                                        <div class="h-8 w-8 rounded-full bg-gradient-to-br from-blue-500/20 to-purple-500/20 flex items-center justify-center text-xs font-bold text-blue-400 border border-blue-500/20 shrink-0">
                                            {{ user.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div>
                                            <div class="text-sm font-semibold text-slate-900 dark:text-white">{{ user.name }}</div>
                                            <div v-if="user.employee" class="text-[10px] text-slate-500 dark:text-slate-400 font-mono font-bold tracking-wide mt-0.5">
                                                NIK: {{ user.employee.nik }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-2 text-sm text-slate-500 dark:text-slate-400">
                                    {{ user.email }}
                                </td>
                                <td class="px-6 py-2">
                                    <div class="flex flex-col gap-1">
                                        <div v-if="user.roles && user.roles.length > 0" class="flex items-center gap-2">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-indigo-500/10 text-indigo-400 text-[10px] font-bold border border-indigo-500/20">
                                                <ShieldCheckIcon class="h-3 w-3" />
                                                {{ user.roles[0].name }}
                                            </span>
                                        </div>
                                        <div v-else class="text-[10px] text-slate-600 italic">No role assigned</div>
                                        
                                        <div v-if="user.supplier" class="text-[10px] text-blue-500 font-semibold flex items-center gap-1">
                                            <BuildingOfficeIcon class="h-3 w-3 text-blue-500" />
                                            {{ user.supplier.name }} (Supplier)
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-2 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button 
                                            type="button"
                                            @click="openEditModal(user)"
                                            class="p-1.5 text-slate-500 dark:text-slate-400 hover:text-blue-400 hover:bg-blue-400/10 rounded-lg transition-all border border-transparent hover:border-blue-500/30"
                                            title="Edit User"
                                        >
                                            <PencilSquareIcon class="h-4.5 w-4.5" />
                                        </button>
                                        <button 
                                            type="button"
                                            @click="confirmDelete(user)"
                                            class="p-1.5 text-slate-500 dark:text-slate-400 hover:text-red-400 hover:bg-red-400/10 rounded-lg transition-all border border-transparent hover:border-red-500/30"
                                            title="Delete User"
                                        >
                                            <TrashIcon class="h-4.5 w-4.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!users.data || users.data.length === 0">
                                <td colspan="4" class="px-6 py-12 text-center text-slate-500 italic">
                                    No users found matching your search.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Showing {{ users.from || 0 }} to {{ users.to || 0 }} of {{ users.total || 0 }} users
                    </p>
                    <div class="flex items-center gap-2">
                        <Link
                            v-for="link in users.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            class="px-3 py-1.5 rounded-lg text-sm transition-colors"
                            :class="link.active 
                                ? 'bg-blue-600 text-white' 
                                : link.url 
                                    ? 'text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' 
                                    : 'text-slate-300 dark:text-slate-600 cursor-not-allowed'"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Create User Modal -->
        <TransitionRoot as="template" :show="showCreateModal">
            <Dialog as="div" class="relative z-[99]" @close="showCreateModal = false">
                <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                    <div class="fixed inset-0 bg-slate-950/50 dark:bg-slate-950/80 backdrop-blur-sm transition-opacity" />
                </TransitionChild>
                <div class="fixed inset-0 z-10 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                        <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                            <DialogPanel class="relative transform overflow-hidden rounded-2xl glass-card text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                                <form @submit.prevent="createUser" autocomplete="off">
                                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center">
                                        <DialogTitle as="h3" class="text-lg font-bold text-slate-900 dark:text-white">Add New User</DialogTitle>
                                        <button @click="showCreateModal = false" type="button" class="text-slate-500 hover:text-slate-900 dark:text-white transition-colors">
                                            <XMarkIcon class="h-6 w-6" />
                                        </button>
                                    </div>
                                    <div class="p-6 space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1.5">Full Name</label>
                                            <input v-model="form.name" type="text" autocomplete="off" class="block w-full px-4 py-2.5 glass-card rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 transition-all" placeholder="Enter full name" />
                                            <p v-if="form.errors.name" class="mt-1.5 text-xs text-red-500">{{ form.errors.name }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1.5">Email Address</label>
                                            <input v-model="form.email" type="email" autocomplete="off" class="block w-full px-4 py-2.5 glass-card rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 transition-all" placeholder="user@company.com" />
                                            <p v-if="form.errors.email" class="mt-1.5 text-xs text-red-500">{{ form.errors.email }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1.5">Link to Supplier (Optional)</label>
                                            <select v-model="form.supplier_id" class="block w-full px-4 py-2.5 glass-card rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 transition-all">
                                                <option value="" class="text-slate-400">Select Supplier</option>
                                                <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">{{ supplier.name }}</option>
                                            </select>
                                            <p class="mt-1 text-xs text-slate-400">If selected, this user will access the Vendor Portal.</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1.5">Assigned Role</label>
                                            <select v-model="form.role" class="block w-full px-4 py-2.5 glass-card rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 transition-all">
                                                <option value="" disabled>Select a role</option>
                                                <option v-for="role in roles" :key="role.id" :value="role.name">{{ role.name }}</option>
                                            </select>
                                            <p v-if="form.errors.role" class="mt-1.5 text-xs text-red-500">{{ form.errors.role }}</p>
                                        </div>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1.5">Password</label>
                                                <input v-model="form.password" type="password" autocomplete="new-password" class="block w-full px-4 py-2.5 glass-card rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 transition-all" placeholder="••••••••" />
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1.5">Confirm Password</label>
                                                <input v-model="form.password_confirmation" type="password" autocomplete="new-password" class="block w-full px-4 py-2.5 glass-card rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 transition-all" placeholder="••••••••" />
                                            </div>
                                            <p v-if="form.errors.password" class="col-span-2 mt-0.5 text-xs text-red-500">{{ form.errors.password }}</p>
                                        </div>
                                    </div>
                                    <div class="px-6 py-4 bg-slate-50 dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-3">
                                        <button @click="showCreateModal = false" type="button" class="px-4 py-2 text-sm font-medium text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white transition-colors">Cancel</button>
                                        <button 
                                            type="submit" 
                                            :disabled="form.processing"
                                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-2 text-sm font-semibold text-white dark:text-white shadow-lg shadow-blue-900/20 hover:bg-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                                        >
                                            Save User
                                        </button>
                                    </div>
                                </form>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>

        <!-- Edit User Modal -->
        <TransitionRoot as="template" :show="showEditModal">
            <Dialog as="div" class="relative z-[99]" @close="showEditModal = false">
                <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                    <div class="fixed inset-0 bg-slate-950/50 dark:bg-slate-950/80 backdrop-blur-sm transition-opacity" />
                </TransitionChild>
                <div class="fixed inset-0 z-10 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                        <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                            <DialogPanel class="relative transform overflow-hidden rounded-2xl glass-card text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                                <form @submit.prevent="updateUser">
                                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center">
                                        <DialogTitle as="h3" class="text-lg font-bold text-slate-900 dark:text-white">Edit User</DialogTitle>
                                        <button @click="showEditModal = false" type="button" class="text-slate-500 hover:text-slate-900 dark:text-white transition-colors">
                                            <XMarkIcon class="h-6 w-6" />
                                        </button>
                                    </div>
                                    <div class="p-6 space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1.5">Full Name</label>
                                            <input v-model="form.name" type="text" class="block w-full px-4 py-2.5 glass-card rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 transition-all" />
                                            <p v-if="form.errors.name" class="mt-1.5 text-xs text-red-500">{{ form.errors.name }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1.5">Email Address</label>
                                            <input v-model="form.email" type="email" class="block w-full px-4 py-2.5 glass-card rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 transition-all" />
                                            <p v-if="form.errors.email" class="mt-1.5 text-xs text-red-500">{{ form.errors.email }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1.5">Link to Supplier (Optional)</label>
                                            <select v-model="form.supplier_id" class="block w-full px-4 py-2.5 glass-card rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 transition-all">
                                                <option value="" class="text-slate-400">Select Supplier</option>
                                                <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">{{ supplier.name }}</option>
                                            </select>
                                            <p class="mt-1 text-xs text-slate-400">If selected, this user will access the Vendor Portal.</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1.5">Assigned Role</label>
                                            <select v-model="form.role" class="block w-full px-4 py-2.5 glass-card rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 transition-all">
                                                <option value="" disabled>Select a role</option>
                                                <option v-for="role in roles" :key="role.id" :value="role.name">{{ role.name }}</option>
                                            </select>
                                            <p v-if="form.errors.role" class="mt-1.5 text-xs text-red-500">{{ form.errors.role }}</p>
                                        </div>
                                        <div class="bg-blue-500/5 rounded-xl p-4 border border-blue-500/10">
                                            <p class="text-xs text-blue-400 mb-3">Leave password blank if you don't want to change it.</p>
                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1.5">New Password</label>
                                                    <input v-model="form.password" type="password" class="block w-full px-4 py-2.5 glass-card rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 transition-all" placeholder="••••••••" />
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1.5">Confirm Password</label>
                                                    <input v-model="form.password_confirmation" type="password" class="block w-full px-4 py-2.5 glass-card rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 transition-all" placeholder="••••••••" />
                                                </div>
                                                <p v-if="form.errors.password" class="col-span-2 mt-0.5 text-xs text-red-500">{{ form.errors.password }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="px-6 py-4 bg-slate-50 dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-3">
                                        <button @click="showEditModal = false" type="button" class="px-4 py-2 text-sm font-medium text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white transition-colors">Cancel</button>
                                        <button 
                                            type="submit" 
                                            :disabled="form.processing"
                                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-2 text-sm font-semibold text-white dark:text-white shadow-lg shadow-blue-900/20 hover:bg-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                                        >
                                            Update User
                                        </button>
                                    </div>
                                </form>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>

        <!-- Delete Confirmation Modal -->
        <TransitionRoot as="template" :show="showDeleteConfirm">
            <Dialog as="div" class="relative z-[99]" @close="showDeleteConfirm = false">
                <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                    <div class="fixed inset-0 bg-slate-950/50 dark:bg-slate-950/80 backdrop-blur-sm transition-opacity" />
                </TransitionChild>
                <div class="fixed inset-0 z-10 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                        <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                            <DialogPanel class="relative transform overflow-hidden rounded-2xl glass-card text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md">
                                <div class="p-6">
                                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-500/10 text-red-500 mb-6 font-bold">
                                        <ExclamationTriangleIcon class="h-10 w-10" />
                                    </div>
                                    <div class="text-center">
                                        <DialogTitle as="h3" class="text-xl font-bold text-slate-900 dark:text-white mb-2">Delete User</DialogTitle>
                                        <p class="text-sm text-slate-500 dark:text-slate-400">
                                            Are you sure you want to delete user <span class="text-slate-900 dark:text-white font-bold">{{ userToDelete?.name }}</span>? This action cannot be undone.
                                        </p>
                                    </div>
                                    <div class="mt-8 flex gap-3">
                                        <button 
                                            @click="showDeleteConfirm = false"
                                            class="flex-1 px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-medium text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-all"
                                        >
                                            No, Keep it
                                        </button>
                                        <button 
                                            @click="deleteUser"
                                            class="flex-1 px-4 py-2.5 rounded-xl bg-red-600 text-sm font-semibold text-slate-900 dark:text-white shadow-lg shadow-red-900/20 hover:bg-red-500 transition-all"
                                        >
                                            Yes, Delete
                                        </button>
                                    </div>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>
    </AppLayout>
</template>



