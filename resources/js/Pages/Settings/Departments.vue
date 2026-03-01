<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    BuildingOffice2Icon, 
    PlusIcon, 
    PencilSquareIcon, 
    TrashIcon, 
    MagnifyingGlassIcon,
    ExclamationTriangleIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';

const props = defineProps({
    departments: Object,
    filters: Object,
});

const searchQuery = ref(props.filters?.search || '');
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteConfirm = ref(false);
const deptToEdit = ref(null);
const deptToDelete = ref(null);

const doSearch = () => {
    router.get(route('settings.departments'), { search: searchQuery.value }, { preserveState: true, replace: true });
};

const form = useForm({
    code: '',
    name: '',
    description: '',
    is_active: true,
});

const openCreateModal = () => {
    form.clearErrors();
    form.reset();
    form.is_active = true;
    showCreateModal.value = true;
};

const createDept = () => {
    form.post(route('settings.departments.store'), {
        onSuccess: () => { showCreateModal.value = false; },
    });
};

const openEditModal = (dept) => {
    deptToEdit.value = dept;
    form.clearErrors();
    form.code = dept.code || '';
    form.name = dept.name;
    form.description = dept.description || '';
    form.is_active = dept.is_active;
    showEditModal.value = true;
};

const updateDept = () => {
    form.put(route('settings.departments.update', deptToEdit.value.id), {
        onSuccess: () => { showEditModal.value = false; },
    });
};

const confirmDelete = (dept) => {
    deptToDelete.value = dept;
    showDeleteConfirm.value = true;
};

const deleteDept = () => {
    router.delete(route('settings.departments.destroy', deptToDelete.value.id), {
        onSuccess: () => { showDeleteConfirm.value = false; },
    });
};
</script>

<template>
    <Head title="Department Management" />
    
    <AppLayout title="Department Management">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-400">
                        <BuildingOffice2Icon class="h-7 w-7" />
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white">Departments</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Manage company departments for Purchase Requests & HR</p>
                    </div>
                </div>
                <button 
                    @click="openCreateModal"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-900/20 hover:bg-blue-500 transition-all duration-200"
                >
                    <PlusIcon class="h-5 w-5" />
                    Add Department
                </button>
            </div>

            <!-- List -->
            <div class="rounded-2xl glass-card overflow-hidden shadow-xl">
                <!-- Search -->
                <div class="p-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50">
                    <div class="relative max-w-sm w-full">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <MagnifyingGlassIcon class="h-5 w-5 text-slate-500" />
                        </div>
                        <input 
                            v-model="searchQuery"
                            @keyup.enter="doSearch"
                            type="text" 
                            placeholder="Search departments..." 
                            class="block w-full pl-10 pr-3 py-2 glass-card rounded-xl text-sm text-slate-900 dark:text-white placeholder-slate-500 focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 transition-all"
                        />
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-900/50">
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Code</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider hidden md:table-cell">Description</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50">
                            <tr v-for="dept in departments.data" :key="dept.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors group">
                                <td class="px-6 py-4">
                                    <span class="text-xs font-mono font-bold text-blue-400 bg-blue-500/10 px-2 py-1 rounded-lg">{{ dept.code || '-' }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-semibold text-slate-900 dark:text-white">{{ dept.name }}</div>
                                </td>
                                <td class="px-6 py-4 hidden md:table-cell">
                                    <div class="text-sm text-slate-500 dark:text-slate-400 max-w-xs truncate">{{ dept.description || '-' }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span v-if="dept.is_active" class="inline-flex items-center px-2.5 py-1 rounded-lg bg-emerald-500/10 text-emerald-400 text-xs font-bold border border-emerald-500/20">Active</span>
                                    <span v-else class="inline-flex items-center px-2.5 py-1 rounded-lg bg-red-500/10 text-red-400 text-xs font-bold border border-red-500/20">Inactive</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button 
                                            type="button"
                                            @click="openEditModal(dept)"
                                            class="p-2 text-slate-500 dark:text-slate-400 hover:text-blue-400 hover:bg-blue-400/10 rounded-xl transition-all border border-transparent hover:border-blue-500/30"
                                            title="Edit"
                                        >
                                            <PencilSquareIcon class="h-5 w-5" />
                                        </button>
                                        <button 
                                            type="button"
                                            @click="confirmDelete(dept)"
                                            class="p-2 text-slate-500 dark:text-slate-400 hover:text-red-400 hover:bg-red-400/10 rounded-xl transition-all border border-transparent hover:border-red-500/30"
                                            title="Delete"
                                        >
                                            <TrashIcon class="h-5 w-5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="departments.data.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center text-slate-500 italic">
                                    No departments found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <TransitionRoot as="template" :show="showCreateModal">
            <Dialog as="div" class="relative z-[99]" @close="showCreateModal = false">
                <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                    <div class="fixed inset-0 bg-slate-950/50 dark:bg-slate-950/80 backdrop-blur-sm transition-opacity" />
                </TransitionChild>
                <div class="fixed inset-0 z-10 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                        <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                            <DialogPanel class="relative transform overflow-hidden rounded-2xl glass-card text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                                <form @submit.prevent="createDept">
                                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center">
                                        <DialogTitle as="h3" class="text-lg font-bold text-slate-900 dark:text-white">Add New Department</DialogTitle>
                                        <button @click="showCreateModal = false" type="button" class="text-slate-500 hover:text-slate-900 dark:text-white transition-colors">
                                            <XMarkIcon class="h-6 w-6" />
                                        </button>
                                    </div>
                                    <div class="p-6 space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1.5">Code</label>
                                            <input v-model="form.code" type="text" class="block w-full px-4 py-2.5 glass-card rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 transition-all" placeholder="e.g. PRD, LOG, IT" />
                                            <p v-if="form.errors.code" class="mt-1.5 text-xs text-red-500">{{ form.errors.code }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1.5">Name <span class="text-red-400">*</span></label>
                                            <input v-model="form.name" type="text" class="block w-full px-4 py-2.5 glass-card rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 transition-all" placeholder="Department name" />
                                            <p v-if="form.errors.name" class="mt-1.5 text-xs text-red-500">{{ form.errors.name }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1.5">Description</label>
                                            <textarea v-model="form.description" rows="2" class="block w-full px-4 py-2.5 glass-card rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 transition-all" placeholder="Optional description"></textarea>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <input v-model="form.is_active" type="checkbox" id="create_is_active" class="rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500" />
                                            <label for="create_is_active" class="text-sm text-slate-500 dark:text-slate-400">Active</label>
                                        </div>
                                    </div>
                                    <div class="px-6 py-4 bg-slate-50 dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-3">
                                        <button @click="showCreateModal = false" type="button" class="px-4 py-2 text-sm font-medium text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white transition-colors">Cancel</button>
                                        <button 
                                            type="submit" 
                                            :disabled="form.processing"
                                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-2 text-sm font-semibold text-white shadow-lg shadow-blue-900/20 hover:bg-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                                        >
                                            Save Department
                                        </button>
                                    </div>
                                </form>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>

        <!-- Edit Modal -->
        <TransitionRoot as="template" :show="showEditModal">
            <Dialog as="div" class="relative z-[99]" @close="showEditModal = false">
                <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                    <div class="fixed inset-0 bg-slate-950/50 dark:bg-slate-950/80 backdrop-blur-sm transition-opacity" />
                </TransitionChild>
                <div class="fixed inset-0 z-10 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                        <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                            <DialogPanel class="relative transform overflow-hidden rounded-2xl glass-card text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                                <form @submit.prevent="updateDept">
                                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center">
                                        <DialogTitle as="h3" class="text-lg font-bold text-slate-900 dark:text-white">Edit Department</DialogTitle>
                                        <button @click="showEditModal = false" type="button" class="text-slate-500 hover:text-slate-900 dark:text-white transition-colors">
                                            <XMarkIcon class="h-6 w-6" />
                                        </button>
                                    </div>
                                    <div class="p-6 space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1.5">Code</label>
                                            <input v-model="form.code" type="text" class="block w-full px-4 py-2.5 glass-card rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 transition-all" />
                                            <p v-if="form.errors.code" class="mt-1.5 text-xs text-red-500">{{ form.errors.code }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1.5">Name <span class="text-red-400">*</span></label>
                                            <input v-model="form.name" type="text" class="block w-full px-4 py-2.5 glass-card rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 transition-all" />
                                            <p v-if="form.errors.name" class="mt-1.5 text-xs text-red-500">{{ form.errors.name }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1.5">Description</label>
                                            <textarea v-model="form.description" rows="2" class="block w-full px-4 py-2.5 glass-card rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 transition-all"></textarea>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <input v-model="form.is_active" type="checkbox" id="edit_is_active" class="rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500" />
                                            <label for="edit_is_active" class="text-sm text-slate-500 dark:text-slate-400">Active</label>
                                        </div>
                                    </div>
                                    <div class="px-6 py-4 bg-slate-50 dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-3">
                                        <button @click="showEditModal = false" type="button" class="px-4 py-2 text-sm font-medium text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white transition-colors">Cancel</button>
                                        <button 
                                            type="submit" 
                                            :disabled="form.processing"
                                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-2 text-sm font-semibold text-white shadow-lg shadow-blue-900/20 hover:bg-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                                        >
                                            Update Department
                                        </button>
                                    </div>
                                </form>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>

        <!-- Delete Confirmation -->
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
                                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-500/10 text-red-500 mb-6">
                                        <ExclamationTriangleIcon class="h-10 w-10" />
                                    </div>
                                    <div class="text-center">
                                        <DialogTitle as="h3" class="text-xl font-bold text-slate-900 dark:text-white mb-2">Delete Department</DialogTitle>
                                        <p class="text-sm text-slate-500 dark:text-slate-400">
                                            Are you sure you want to delete <span class="text-slate-900 dark:text-white font-bold">{{ deptToDelete?.name }}</span>? This action cannot be undone.
                                        </p>
                                    </div>
                                    <div class="mt-8 flex gap-3">
                                        <button 
                                            @click="showDeleteConfirm = false"
                                            class="flex-1 px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-medium text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-all"
                                        >
                                            No, Keep it
                                        </button>
                                        <button 
                                            @click="deleteDept"
                                            class="flex-1 px-4 py-2.5 rounded-xl bg-red-600 text-sm font-semibold text-white shadow-lg shadow-red-900/20 hover:bg-red-500 transition-all"
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
