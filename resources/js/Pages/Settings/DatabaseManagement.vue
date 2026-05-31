<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ServerStackIcon,
    ArrowDownTrayIcon,
    ArrowUpTrayIcon,
    ArrowPathIcon,
    TrashIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    DocumentArrowDownIcon,
    CloudArrowUpIcon,
    ShieldExclamationIcon,
    CubeIcon,
    WrenchScrewdriverIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    modules: Array,
    backups: Array,
});

const activeTab = ref('backup');
const showConfirmModal = ref(false);
const confirmAction = ref(null);
const confirmTitle = ref('');
const confirmMessage = ref('');
const confirmType = ref('warning'); // warning, danger

// Forms
const backupForm = useForm({
    type: 'full',
    modules: [],
});

const restoreForm = useForm({
    filename: '',
    password: '',
});

const uploadRestoreForm = useForm({
    file: null,
    password: '',
});

const softResetForm = useForm({
    password: '',
    confirmation: '',
});

const hardResetForm = useForm({
    password: '',
    confirmation: '',
});

const moduleResetForm = useForm({
    module: '',
    mode: 'hard',
    password: '',
});

// Module labels
const moduleLabels = {
    sales: { name: 'Sales', icon: '📊', desc: 'Customers, Quotations, Sales Orders, DO, Invoices' },
    purchasing: { name: 'Purchasing', icon: '🛒', desc: 'Suppliers, PR, PO, GR, Purchase Invoices' },
    inventory: { name: 'Inventory', icon: '📦', desc: 'Products, Stocks, Warehouses, Movements' },
    manufacturing: { name: 'Manufacturing', icon: '🏭', desc: 'BOM, Work Orders, Production, Machines' },
    hr: { name: 'HR & Payroll', icon: '👥', desc: 'Employees, Attendance, Payrolls' },
    finance: { name: 'Finance', icon: '💰', desc: 'Accounts, Journals, Ledgers' },
    settings: { name: 'Settings', icon: '⚙️', desc: 'Users, Roles, Company Settings' },
    projects: { name: 'Projects', icon: '🚀', desc: 'Projects, Tasks, Members' },
    crm: { name: 'CRM', icon: '🤝', desc: 'Leads, Opportunities, Campaigns' },
    logistics: { name: 'Logistics', icon: '🚚', desc: 'Fleet, Delivery Schedules' },
    maintenance: { name: 'Maintenance', icon: '🔧', desc: 'Schedules, Logs, Spareparts' },
};

const softResettableModules = new Set(['sales', 'purchasing', 'inventory', 'manufacturing', 'hr', 'finance', 'logistics']);

// Computed
const selectedModuleCount = computed(() => backupForm.modules.length);

// Methods
const createBackup = () => {
    backupForm.post(route('settings.database.backup'), {
        preserveScroll: true,
        onSuccess: () => {
            backupForm.reset();
            router.reload({ only: ['backups'] });
        },
    });
};

const downloadBackup = (filename) => {
    window.location.href = route('settings.database.download', filename);
};

const deleteBackup = (filename) => {
    if (confirm('Are you sure you want to delete this backup?')) {
        router.delete(route('settings.database.delete', filename), {
            preserveScroll: true,
        });
    }
};

const selectBackupForRestore = (filename) => {
    restoreForm.filename = filename;
    activeTab.value = 'restore';
};

const restoreBackup = () => {
    confirmAction.value = () => {
        restoreForm.post(route('settings.database.restore'), {
            preserveScroll: true,
            onSuccess: () => {
                restoreForm.reset();
                showConfirmModal.value = false;
            },
        });
    };
    confirmTitle.value = 'Confirm Restore';
    confirmMessage.value = 'This will overwrite current data. A backup will be created before restore. Continue?';
    confirmType.value = 'warning';
    showConfirmModal.value = true;
};

const uploadAndRestore = () => {
    confirmAction.value = () => {
        uploadRestoreForm.post(route('settings.database.upload-restore'), {
            preserveScroll: true,
            onSuccess: () => {
                uploadRestoreForm.reset();
                showConfirmModal.value = false;
            },
        });
    };
    confirmTitle.value = 'Confirm Upload & Restore';
    confirmMessage.value = 'This will upload and restore from the selected file. Continue?';
    confirmType.value = 'warning';
    showConfirmModal.value = true;
};

const performSoftReset = () => {
    confirmAction.value = () => {
        softResetForm.post(route('settings.database.soft-reset'), {
            preserveScroll: true,
            onSuccess: () => {
                softResetForm.reset();
                showConfirmModal.value = false;
            },
        });
    };
    confirmTitle.value = '⚠️ Soft Reset';
    confirmMessage.value = 'This will DELETE all transaction data (Sales, Purchases, etc.) but KEEP master data. This action CANNOT be undone!';
    confirmType.value = 'danger';
    showConfirmModal.value = true;
};

const performHardReset = () => {
    confirmAction.value = () => {
        hardResetForm.post(route('settings.database.hard-reset'), {
            preserveScroll: true,
            onSuccess: () => {
                hardResetForm.reset();
                showConfirmModal.value = false;
            },
        });
    };
    confirmTitle.value = '🚨 HARD RESET';
    confirmMessage.value = 'This will COMPLETELY RESET the database to initial state. ALL DATA WILL BE LOST! A backup will be created before reset.';
    confirmType.value = 'danger';
    showConfirmModal.value = true;
};

const performModuleReset = () => {
    confirmAction.value = () => {
        moduleResetForm.post(route('settings.database.module-reset'), {
            preserveScroll: true,
            onSuccess: () => {
                moduleResetForm.reset();
                showConfirmModal.value = false;
            },
        });
    };
    const moduleName = moduleLabels[moduleResetForm.module]?.name || moduleResetForm.module;
    if (moduleResetForm.mode === 'soft') {
        confirmTitle.value = 'Module Soft Reset';
        confirmMessage.value = `This will DELETE transaction data in the "${moduleName}" module but KEEP master data. Continue?`;
        confirmType.value = 'danger';
    } else {
        confirmTitle.value = 'Module Reset';
        confirmMessage.value = `This will reset all data in the "${moduleName}" module (including master data). Continue?`;
        confirmType.value = 'warning';
    }
    showConfirmModal.value = true;
};

const handleFileUpload = (event) => {
    uploadRestoreForm.file = event.target.files[0];
};

const formatSize = (bytes) => {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
};

const formatDate = (dateStr) => {
    return new Date(dateStr).toLocaleString('id-ID');
};
</script>

<template>
    <Head title="Database Management" />

    <AppLayout title="Database Management">
        <template #header>
            <div class="flex items-center gap-3">
                <div class="p-2 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-xl shadow-lg">
                    <ServerStackIcon class="h-6 w-6 text-white" />
                </div>
                <div>
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white">Database Management</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Backup, Restore, and Reset Database</p>
                </div>
            </div>
        </template>

        <div class="p-6 space-y-6">
            <!-- Tab Navigation -->
            <div class="flex gap-2 border-b border-slate-200 dark:border-slate-700 pb-2">
                <button
                    v-for="tab in ['backup', 'restore', 'reset', 'maintenance']"
                    :key="tab"
                    @click="activeTab = tab"
                    class="px-4 py-2 rounded-lg font-medium text-sm transition-all"
                    :class="activeTab === tab 
                        ? 'bg-indigo-600 text-white shadow-lg' 
                        : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'"
                >
                    <span v-if="tab === 'backup'">📦 Backup</span>
                    <span v-else-if="tab === 'restore'">📥 Restore</span>
                    <span v-else-if="tab === 'reset'">🔄 Reset</span>
                    <span v-else>🔧 Maintenance</span>
                </button>
            </div>

            <!-- BACKUP TAB -->
            <div v-if="activeTab === 'backup'" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Create Backup Card -->
                <div class="glass-card p-6 rounded-2xl">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                        <ArrowDownTrayIcon class="h-5 w-5 text-indigo-500" />
                        Create Backup
                    </h3>

                    <form @submit.prevent="createBackup" class="space-y-4">
                        <!-- Backup Type -->
                        <div class="space-y-3">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                                Backup Type
                            </label>
                            <div class="space-y-2">
                                <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 dark:border-slate-700 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
                                    :class="backupForm.type === 'full' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : ''">
                                    <input type="radio" v-model="backupForm.type" value="full" class="rounded-full text-indigo-600">
                                    <div>
                                        <p class="font-medium text-slate-900 dark:text-white">Full Database</p>
                                        <p class="text-xs text-slate-500">Backup entire database (Recommended)</p>
                                    </div>
                                </label>
                                <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 dark:border-slate-700 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
                                    :class="backupForm.type === 'partial' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : ''">
                                    <input type="radio" v-model="backupForm.type" value="partial" class="rounded-full text-indigo-600">
                                    <div>
                                        <p class="font-medium text-slate-900 dark:text-white">Partial (Select Modules)</p>
                                        <p class="text-xs text-slate-500">Backup specific modules only</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Module Selection (for Partial) -->
                        <div v-if="backupForm.type === 'partial'" class="space-y-3">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                                Select Modules ({{ selectedModuleCount }} selected)
                            </label>
                            <div class="grid grid-cols-1 gap-2">
                                <label 
                                    v-for="module in modules" 
                                    :key="module"
                                    class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 dark:border-slate-700 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
                                    :class="backupForm.modules.includes(module) ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : ''"
                                >
                                    <input type="checkbox" v-model="backupForm.modules" :value="module" class="rounded text-indigo-600">
                                    <span class="text-xl">{{ moduleLabels[module]?.icon || '📁' }}</span>
                                    <div>
                                        <p class="font-medium text-slate-900 dark:text-white">{{ moduleLabels[module]?.name || module }}</p>
                                        <p class="text-xs text-slate-500">{{ moduleLabels[module]?.desc || '' }}</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <button
                            type="submit"
                            :disabled="backupForm.processing || (backupForm.type === 'partial' && selectedModuleCount === 0)"
                            class="w-full py-3 px-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="backupForm.processing" class="flex items-center justify-center gap-2">
                                <ArrowPathIcon class="h-5 w-5 animate-spin" />
                                Creating Backup...
                            </span>
                            <span v-else class="flex items-center justify-center gap-2">
                                <ArrowDownTrayIcon class="h-5 w-5" />
                                Create Backup
                            </span>
                        </button>
                    </form>
                </div>

                <!-- Available Backups Card -->
                <div class="glass-card p-6 rounded-2xl">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center justify-between">
                        <span class="flex items-center gap-2">
                            <DocumentArrowDownIcon class="h-5 w-5 text-emerald-500" />
                            Available Backups
                        </span>
                        <span class="text-sm font-normal text-slate-500">{{ backups.length }} files</span>
                    </h3>

                    <div v-if="backups.length === 0" class="text-center py-8 text-slate-500">
                        <CubeIcon class="h-12 w-12 mx-auto mb-2 opacity-50" />
                        <p>No backups available</p>
                    </div>

                    <div v-else class="space-y-2 max-h-96 overflow-y-auto">
                        <div 
                            v-for="backup in backups" 
                            :key="backup.filename"
                            class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                        >
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-slate-900 dark:text-white text-sm truncate">{{ backup.filename }}</p>
                                <p class="text-xs text-slate-500">{{ backup.size_human }} • {{ formatDate(backup.created_at) }}</p>
                            </div>
                            <div class="flex items-center gap-1 ml-2">
                                <button 
                                    @click="downloadBackup(backup.filename)"
                                    class="p-2 text-indigo-600 hover:bg-indigo-100 dark:hover:bg-indigo-900/30 rounded-lg transition-colors"
                                    title="Download"
                                >
                                    <ArrowDownTrayIcon class="h-4 w-4" />
                                </button>
                                <button 
                                    @click="selectBackupForRestore(backup.filename)"
                                    class="p-2 text-emerald-600 hover:bg-emerald-100 dark:hover:bg-emerald-900/30 rounded-lg transition-colors"
                                    title="Restore"
                                >
                                    <ArrowUpTrayIcon class="h-4 w-4" />
                                </button>
                                <button 
                                    @click="deleteBackup(backup.filename)"
                                    class="p-2 text-red-600 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                                    title="Delete"
                                >
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RESTORE TAB -->
            <div v-if="activeTab === 'restore'" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Restore from Server -->
                <div class="glass-card p-6 rounded-2xl">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                        <ArrowUpTrayIcon class="h-5 w-5 text-emerald-500" />
                        Restore from Server Backup
                    </h3>

                    <form @submit.prevent="restoreBackup" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Select Backup File
                            </label>
                            <select 
                                v-model="restoreForm.filename"
                                class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                            >
                                <option value="">-- Select a backup --</option>
                                <option v-for="backup in backups" :key="backup.filename" :value="backup.filename">
                                    {{ backup.filename }} ({{ backup.size_human }})
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Enter Your Password
                            </label>
                            <input 
                                type="password" 
                                v-model="restoreForm.password"
                                class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                placeholder="Enter your password to confirm"
                            >
                        </div>

                        <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800">
                            <div class="flex items-start gap-2">
                                <ExclamationTriangleIcon class="h-5 w-5 text-amber-600 shrink-0 mt-0.5" />
                                <p class="text-sm text-amber-800 dark:text-amber-200">
                                    Restore will <strong>overwrite</strong> current data. A backup will be created automatically before restore.
                                </p>
                            </div>
                        </div>

                        <button
                            type="submit"
                            :disabled="restoreForm.processing || !restoreForm.filename || !restoreForm.password"
                            class="w-full py-3 px-4 bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="restoreForm.processing" class="flex items-center justify-center gap-2">
                                <ArrowPathIcon class="h-5 w-5 animate-spin" />
                                Restoring...
                            </span>
                            <span v-else class="flex items-center justify-center gap-2">
                                <ArrowUpTrayIcon class="h-5 w-5" />
                                Restore Database
                            </span>
                        </button>
                    </form>
                </div>

                <!-- Upload & Restore -->
                <div class="glass-card p-6 rounded-2xl">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                        <CloudArrowUpIcon class="h-5 w-5 text-blue-500" />
                        Upload & Restore
                    </h3>

                    <form @submit.prevent="uploadAndRestore" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Upload Backup File (.sql or .sql.gz)
                            </label>
                            <input 
                                type="file" 
                                @change="handleFileUpload"
                                accept=".sql,.gz"
                                class="w-full rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-50 dark:file:bg-indigo-900/30 file:text-indigo-700 dark:file:text-indigo-300"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Enter Your Password
                            </label>
                            <input 
                                type="password" 
                                v-model="uploadRestoreForm.password"
                                class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                placeholder="Enter your password to confirm"
                            >
                        </div>

                        <button
                            type="submit"
                            :disabled="uploadRestoreForm.processing || !uploadRestoreForm.file || !uploadRestoreForm.password"
                            class="w-full py-3 px-4 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="uploadRestoreForm.processing" class="flex items-center justify-center gap-2">
                                <ArrowPathIcon class="h-5 w-5 animate-spin" />
                                Uploading & Restoring...
                            </span>
                            <span v-else class="flex items-center justify-center gap-2">
                                <CloudArrowUpIcon class="h-5 w-5" />
                                Upload & Restore
                            </span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- RESET TAB -->
            <div v-if="activeTab === 'reset'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Soft Reset -->
                <div class="glass-card p-6 rounded-2xl border-2 border-amber-200 dark:border-amber-800">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2 flex items-center gap-2">
                        <ArrowPathIcon class="h-5 w-5 text-amber-500" />
                        Soft Reset
                    </h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                        Clear all transaction data but keep master data (products, customers, suppliers, etc.)
                    </p>

                    <form @submit.prevent="performSoftReset" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Type "SOFT RESET" to confirm
                            </label>
                            <input 
                                type="text" 
                                v-model="softResetForm.confirmation"
                                class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                placeholder="SOFT RESET"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Password
                            </label>
                            <input 
                                type="password" 
                                v-model="softResetForm.password"
                                class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                            >
                        </div>

                        <button
                            type="submit"
                            :disabled="softResetForm.processing || softResetForm.confirmation !== 'SOFT RESET' || !softResetForm.password"
                            class="w-full py-3 px-4 bg-gradient-to-r from-amber-500 to-orange-500 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Soft Reset
                        </button>
                    </form>
                </div>

                <!-- Hard Reset -->
                <div class="glass-card p-6 rounded-2xl border-2 border-red-200 dark:border-red-800">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2 flex items-center gap-2">
                        <ShieldExclamationIcon class="h-5 w-5 text-red-500" />
                        Hard Reset
                    </h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                        <strong class="text-red-600">DANGER!</strong> Reset entire database to initial state. ALL data will be lost!
                    </p>

                    <form @submit.prevent="performHardReset" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Type "HARD RESET" to confirm
                            </label>
                            <input 
                                type="text" 
                                v-model="hardResetForm.confirmation"
                                class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                placeholder="HARD RESET"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Password
                            </label>
                            <input 
                                type="password" 
                                v-model="hardResetForm.password"
                                class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                            >
                        </div>

                        <button
                            type="submit"
                            :disabled="hardResetForm.processing || hardResetForm.confirmation !== 'HARD RESET' || !hardResetForm.password"
                            class="w-full py-3 px-4 bg-gradient-to-r from-red-600 to-rose-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            🚨 Hard Reset
                        </button>
                    </form>
                </div>

                <!-- Module Reset -->
                <div class="glass-card p-6 rounded-2xl border-2 border-purple-200 dark:border-purple-800">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2 flex items-center gap-2">
                        <CubeIcon class="h-5 w-5 text-purple-500" />
                        Module Reset
                    </h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                        Reset specific module only. Other modules will remain intact.
                    </p>

                    <form @submit.prevent="performModuleReset" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Select Module
                            </label>
                            <select 
                                v-model="moduleResetForm.module"
                                class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                            >
                                <option value="">-- Select module --</option>
                                <option v-for="module in modules" :key="module" :value="module">
                                    {{ moduleLabels[module]?.icon || '📁' }} {{ moduleLabels[module]?.name || module }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Reset Mode
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                <label class="flex items-center gap-2 p-3 rounded-xl border border-slate-200 dark:border-slate-700 cursor-pointer"
                                    :class="moduleResetForm.mode === 'soft' ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20' : ''">
                                    <input type="radio" value="soft" v-model="moduleResetForm.mode"
                                        :disabled="moduleResetForm.module && !softResettableModules.has(moduleResetForm.module)"
                                        class="text-purple-600">
                                    <div>
                                        <p class="text-sm font-medium text-slate-900 dark:text-white">Soft</p>
                                        <p class="text-xs text-slate-500">Keep master data</p>
                                    </div>
                                </label>
                                <label class="flex items-center gap-2 p-3 rounded-xl border border-slate-200 dark:border-slate-700 cursor-pointer"
                                    :class="moduleResetForm.mode === 'hard' ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20' : ''">
                                    <input type="radio" value="hard" v-model="moduleResetForm.mode" class="text-purple-600">
                                    <div>
                                        <p class="text-sm font-medium text-slate-900 dark:text-white">Hard</p>
                                        <p class="text-xs text-slate-500">Reset module entirely</p>
                                    </div>
                                </label>
                            </div>
                            <p v-if="moduleResetForm.module && moduleResetForm.mode === 'soft' && !softResettableModules.has(moduleResetForm.module)" class="text-xs text-amber-600 mt-2">
                                Soft mode is not available for this module.
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Password
                            </label>
                            <input 
                                type="password" 
                                v-model="moduleResetForm.password"
                                class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                            >
                        </div>

                        <button
                            type="submit"
                            :disabled="moduleResetForm.processing || !moduleResetForm.module || !moduleResetForm.password || (moduleResetForm.mode === 'soft' && !softResettableModules.has(moduleResetForm.module))"
                            class="w-full py-3 px-4 bg-gradient-to-r from-purple-600 to-violet-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Reset Module
                        </button>
                    </form>
                </div>
            </div>

            <!-- MAINTENANCE TAB -->
            <div v-if="activeTab === 'maintenance'" class="space-y-6">
                <!-- Tutorial Header -->
                <div class="glass-card p-6 rounded-2xl bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 border border-indigo-200 dark:border-indigo-800">
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
                        📚 Panduan System Maintenance
                    </h3>
                    <p class="text-slate-600 dark:text-slate-400 mb-4">
                        Halaman ini memungkinkan Anda menjalankan perintah maintenance langsung dari browser 
                        tanpa perlu akses SSH atau Terminal server. Berikut urutan yang disarankan setelah update sistem:
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                        <div class="flex items-center gap-2 p-3 bg-white dark:bg-slate-800 rounded-xl">
                            <span class="flex items-center justify-center w-8 h-8 bg-amber-100 dark:bg-amber-900/30 text-amber-600 rounded-full font-bold">1</span>
                            <span class="text-sm text-slate-700 dark:text-slate-300">Run Migrations</span>
                        </div>
                        <div class="flex items-center gap-2 p-3 bg-white dark:bg-slate-800 rounded-xl">
                            <span class="flex items-center justify-center w-8 h-8 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 rounded-full font-bold">2</span>
                            <span class="text-sm text-slate-700 dark:text-slate-300">Sync Permissions</span>
                        </div>
                        <div class="flex items-center gap-2 p-3 bg-white dark:bg-slate-800 rounded-xl">
                            <span class="flex items-center justify-center w-8 h-8 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 rounded-full font-bold">3</span>
                            <span class="text-sm text-slate-700 dark:text-slate-300">Sync Numbering</span>
                        </div>
                        <div class="flex items-center gap-2 p-3 bg-white dark:bg-slate-800 rounded-xl">
                            <span class="flex items-center justify-center w-8 h-8 bg-rose-100 dark:bg-rose-900/30 text-rose-600 rounded-full font-bold">4</span>
                            <span class="text-sm text-slate-700 dark:text-slate-300">Clear Cache</span>
                        </div>
                    </div>
                </div>

                <!-- Action Cards Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Run Migrations (FIRST) -->
                    <div class="glass-card p-6 rounded-2xl border-2 border-amber-200 dark:border-amber-800">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <span class="inline-flex items-center justify-center w-8 h-8 bg-amber-100 dark:bg-amber-900/30 text-amber-600 rounded-full font-bold text-sm mb-2">1</span>
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <WrenchScrewdriverIcon class="h-5 w-5 text-amber-500" />
                                    Run Database Migrations
                                </h3>
                            </div>
                        </div>
                        
                        <div class="space-y-3 mb-4">
                            <div class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-xl">
                                <p class="text-sm font-medium text-amber-800 dark:text-amber-200 mb-1">🎯 Fungsi:</p>
                                <p class="text-sm text-amber-700 dark:text-amber-300">
                                    Membuat tabel-tabel baru di database yang diperlukan oleh fitur terbaru.
                                </p>
                            </div>
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                                <p class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">📝 Kapan digunakan:</p>
                                <ul class="text-sm text-slate-600 dark:text-slate-400 list-disc list-inside space-y-1">
                                    <li>Setelah melakukan <code class="bg-slate-200 dark:bg-slate-700 px-1 rounded">git pull</code></li>
                                    <li>Jika muncul error "Table not found"</li>
                                    <li>Setelah ada update fitur baru</li>
                                </ul>
                            </div>
                            <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-xl">
                                <p class="text-sm font-medium text-green-700 dark:text-green-300">✅ Aman dijalankan berkali-kali (tidak akan duplikat)</p>
                            </div>
                        </div>

                        <form @submit.prevent="router.post(route('settings.database.run-migrations'), {}, { preserveScroll: true })">
                            <button
                                type="submit"
                                class="w-full py-3 px-4 bg-gradient-to-r from-amber-600 to-orange-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all flex items-center justify-center gap-2"
                            >
                                ⚙️ Run Migrations
                            </button>
                        </form>
                    </div>

                    <!-- Sync Permissions (SECOND) -->
                    <div class="glass-card p-6 rounded-2xl border-2 border-indigo-200 dark:border-indigo-800">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <span class="inline-flex items-center justify-center w-8 h-8 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 rounded-full font-bold text-sm mb-2">2</span>
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <WrenchScrewdriverIcon class="h-5 w-5 text-indigo-500" />
                                    Sync Roles & Permissions
                                </h3>
                            </div>
                        </div>

                        <div class="space-y-3 mb-4">
                            <div class="p-3 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl">
                                <p class="text-sm font-medium text-indigo-800 dark:text-indigo-200 mb-1">🎯 Fungsi:</p>
                                <p class="text-sm text-indigo-700 dark:text-indigo-300">
                                    Mendaftarkan role dan permission baru ke database agar muncul di menu "Roles & Permissions".
                                </p>
                            </div>
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                                <p class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">📝 Kapan digunakan:</p>
                                <ul class="text-sm text-slate-600 dark:text-slate-400 list-disc list-inside space-y-1">
                                    <li>Menu baru tidak muncul di daftar permission</li>
                                    <li>Role baru tidak terdaftar</li>
                                    <li>Setelah ada update modul baru</li>
                                </ul>
                            </div>
                            <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-xl">
                                <p class="text-sm font-medium text-green-700 dark:text-green-300">✅ Tidak menghapus permission yang sudah ada</p>
                            </div>
                        </div>

                        <form @submit.prevent="router.post(route('settings.database.sync-permissions'), {}, { preserveScroll: true })">
                            <button
                                type="submit"
                                class="w-full py-3 px-4 bg-gradient-to-r from-indigo-600 to-blue-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all flex items-center justify-center gap-2"
                            >
                                🛡️ Sync Permissions
                            </button>
                        </form>
                    </div>

                    <!-- Sync Document Numbering (THIRD) -->
                    <div class="glass-card p-6 rounded-2xl border-2 border-emerald-200 dark:border-emerald-800">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <span class="inline-flex items-center justify-center w-8 h-8 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 rounded-full font-bold text-sm mb-2">3</span>
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <WrenchScrewdriverIcon class="h-5 w-5 text-emerald-500" />
                                    Sync Document Numbering
                                </h3>
                            </div>
                        </div>

                        <div class="space-y-3 mb-4">
                            <div class="p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl">
                                <p class="text-sm font-medium text-emerald-800 dark:text-emerald-200 mb-1">🎯 Fungsi:</p>
                                <p class="text-sm text-emerald-700 dark:text-emerald-300">
                                    Menambahkan format penomoran dokumen default (SO, PO, Invoice, dll) untuk tipe dokumen yang belum ada.
                                </p>
                            </div>
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                                <p class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">📝 Kapan digunakan:</p>
                                <ul class="text-sm text-slate-600 dark:text-slate-400 list-disc list-inside space-y-1">
                                    <li>Menu Document Numbering kosong</li>
                                    <li>Ada tipe dokumen baru yang perlu format nomor</li>
                                    <li>Error saat generate nomor dokumen</li>
                                </ul>
                            </div>
                            <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-xl">
                                <p class="text-sm font-medium text-green-700 dark:text-green-300">✅ Tidak mengubah format yang sudah Anda kustomisasi</p>
                            </div>
                        </div>

                        <form @submit.prevent="router.post(route('settings.database.sync-numbering'), {}, { preserveScroll: true })">
                            <button
                                type="submit"
                                class="w-full py-3 px-4 bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all flex items-center justify-center gap-2"
                            >
                                🔢 Sync Numbering
                            </button>
                        </form>
                    </div>

                    <!-- Clear Cache (FOURTH) -->
                    <div class="glass-card p-6 rounded-2xl border-2 border-rose-200 dark:border-rose-800">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <span class="inline-flex items-center justify-center w-8 h-8 bg-rose-100 dark:bg-rose-900/30 text-rose-600 rounded-full font-bold text-sm mb-2">4</span>
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <WrenchScrewdriverIcon class="h-5 w-5 text-rose-500" />
                                    Clear System Cache
                                </h3>
                            </div>
                        </div>

                        <div class="space-y-3 mb-4">
                            <div class="p-3 bg-rose-50 dark:bg-rose-900/20 rounded-xl">
                                <p class="text-sm font-medium text-rose-800 dark:text-rose-200 mb-1">🎯 Fungsi:</p>
                                <p class="text-sm text-rose-700 dark:text-rose-300">
                                    Membersihkan semua cache sistem (route, config, view, permission) agar perubahan terbaru langsung terlihat.
                                </p>
                            </div>
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                                <p class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">📝 Kapan digunakan:</p>
                                <ul class="text-sm text-slate-600 dark:text-slate-400 list-disc list-inside space-y-1">
                                    <li>Menu tidak berubah setelah update</li>
                                    <li>Permission sudah di-sync tapi belum muncul</li>
                                    <li>Tampilan tidak sesuai dengan yang seharusnya</li>
                                    <li>Setelah mengubah konfigurasi sistem</li>
                                </ul>
                            </div>
                            <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-xl">
                                <p class="text-sm font-medium text-green-700 dark:text-green-300">✅ Tidak menghapus data apapun, hanya file cache sementara</p>
                            </div>
                        </div>

                        <form @submit.prevent="router.post(route('settings.database.clear-cache'), {}, { preserveScroll: true })">
                            <button
                                type="submit"
                                class="w-full py-3 px-4 bg-gradient-to-r from-rose-600 to-pink-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all flex items-center justify-center gap-2"
                            >
                                🗑️ Clear Cache
                            </button>
                        </form>
                    </div>
                </div>

                <!-- FAQ Section -->
                <div class="glass-card p-6 rounded-2xl">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">❓ Pertanyaan Umum (FAQ)</h3>
                    <div class="space-y-4">
                        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                            <p class="font-medium text-slate-900 dark:text-white mb-1">Q: Apakah aman menjalankan semua tombol ini?</p>
                            <p class="text-sm text-slate-600 dark:text-slate-400">
                                A: Ya, sangat aman! Semua aksi di halaman ini dirancang untuk tidak menghapus data penting. 
                                Mereka hanya menambahkan data baru atau membersihkan cache sementara.
                            </p>
                        </div>
                        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                            <p class="font-medium text-slate-900 dark:text-white mb-1">Q: Berapa lama prosesnya?</p>
                            <p class="text-sm text-slate-600 dark:text-slate-400">
                                A: Biasanya hanya beberapa detik (1-5 detik). Jika lebih lama, tunggu saja sampai muncul notifikasi sukses.
                            </p>
                        </div>
                        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                            <p class="font-medium text-slate-900 dark:text-white mb-1">Q: Kenapa ada error "500 Server Error"?</p>
                            <p class="text-sm text-slate-600 dark:text-slate-400">
                                A: Biasanya karena ada tabel yang belum dibuat. Jalankan "Run Migrations" terlebih dahulu, 
                                lalu coba lagi aksi yang error.
                            </p>
                        </div>
                        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                            <p class="font-medium text-slate-900 dark:text-white mb-1">Q: Apakah perlu menjalankan semua tombol setiap update?</p>
                            <p class="text-sm text-slate-600 dark:text-slate-400">
                                A: Tidak selalu. Jalankan sesuai kebutuhan. Jika tidak ada masalah setelah update, 
                                Anda tidak perlu menjalankan apapun.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirmation Modal -->
        <Teleport to="body">
            <div v-if="showConfirmModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm" @click="showConfirmModal = false"></div>
                <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl p-6 max-w-md w-full">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">{{ confirmTitle }}</h3>
                    <p class="text-slate-600 dark:text-slate-400 mb-6">{{ confirmMessage }}</p>
                    <div class="flex gap-3">
                        <button 
                            @click="showConfirmModal = false"
                            class="flex-1 py-2 px-4 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors"
                        >
                            Cancel
                        </button>
                        <button 
                            @click="confirmAction"
                            class="flex-1 py-2 px-4 rounded-xl text-white font-semibold transition-all"
                            :class="confirmType === 'danger' ? 'bg-red-600 hover:bg-red-700' : 'bg-amber-600 hover:bg-amber-700'"
                        >
                            Confirm
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
