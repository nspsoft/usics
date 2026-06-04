<script setup>
import { ref, reactive, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    ClipboardDocumentCheckIcon,
    PlusIcon,
    TrashIcon,
    ChevronDownIcon,
    ChevronUpIcon,
    CheckCircleIcon,
    XCircleIcon,
    PencilIcon,
    UserIcon,
    ShieldCheckIcon,
    QuestionMarkCircleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    workflows: Array,
    documentTypes: Object,
    conditionOperators: Object,
    users: Array,
    roles: Array,
});

const showForm = ref(false);
const editingWorkflow = ref(null);
const saving = ref(false);

const defaultForm = () => ({
    name: '',
    document_type: 'PurchaseOrder',
    description: '',
    is_active: true,
    condition_field: 'total',
    condition_operator: '>',
    condition_value: null,
    priority: 0,
    is_auto_approve: false,
    steps: [{ approver_type: 'role', approver_id: null, can_skip: false, timeout_days: null }],
});

const form = reactive(defaultForm());

const conditionFields = {
    PurchaseOrder: [
        { value: 'total', label: 'Total Amount' },
        { value: 'subtotal', label: 'Subtotal' },
    ],
    PurchaseRequest: [
        { value: 'total', label: 'Total Amount' },
    ],
    SalesOrder: [
        { value: 'total', label: 'Total Amount' },
        { value: 'subtotal', label: 'Subtotal' },
    ],
    SalesQuotation: [
        { value: 'total', label: 'Total Amount' },
    ],
};

const availableConditionFields = computed(() => {
    return conditionFields[form.document_type] || [];
});

const openForm = (workflow = null) => {
    if (workflow) {
        editingWorkflow.value = workflow;
        Object.assign(form, {
            name: workflow.name,
            document_type: workflow.document_type,
            description: workflow.description || '',
            is_active: workflow.is_active,
            condition_field: workflow.condition_field || 'total',
            condition_operator: workflow.condition_operator || '>',
            condition_value: workflow.condition_value,
            priority: workflow.priority,
            is_auto_approve: !!workflow.is_auto_approve,
            steps: workflow.steps.map(s => ({
                approver_type: s.approver_type,
                approver_id: s.approver_id,
                can_skip: s.can_skip,
                timeout_days: s.timeout_days,
            })),
        });
    } else {
        editingWorkflow.value = null;
        Object.assign(form, defaultForm());
    }
    showForm.value = true;
};

const closeForm = () => {
    showForm.value = false;
    editingWorkflow.value = null;
    Object.assign(form, defaultForm());
};

const addStep = () => {
    form.steps.push({ approver_type: 'role', approver_id: null, can_skip: false, timeout_days: null });
};

const removeStep = (index) => {
    if (form.steps.length > 1) {
        form.steps.splice(index, 1);
    }
};

const moveStep = (index, direction) => {
    const newIndex = index + direction;
    if (newIndex >= 0 && newIndex < form.steps.length) {
        const temp = form.steps[index];
        form.steps[index] = form.steps[newIndex];
        form.steps[newIndex] = temp;
    }
};

const saveWorkflow = () => {
    saving.value = true;
    
    const url = editingWorkflow.value 
        ? route('settings.workflow.update', editingWorkflow.value.id)
        : route('settings.workflow.store');
    
    const method = editingWorkflow.value ? 'put' : 'post';
    
    router[method](url, form, {
        onSuccess: () => closeForm(),
        onFinish: () => saving.value = false,
    });
};

const deleteWorkflow = (workflow) => {
    if (confirm('Are you sure you want to delete this workflow?')) {
        router.delete(route('settings.workflow.destroy', workflow.id));
    }
};

const toggleWorkflow = (workflow) => {
    router.post(route('settings.workflow.toggle', workflow.id));
};

const getApproverName = (step) => {
    if (step.approver_type === 'user') {
        const user = props.users.find(u => u.id === step.approver_id);
        return user ? user.name : 'Unknown User';
    } else {
        const role = props.roles.find(r => r.id === step.approver_id);
        return role ? role.name : 'Unknown Role';
    }
};

const formatCurrency = (value) => {
    if (!value) return '-';
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value);
};
</script>

<template>
    <Head title="Workflow Approval" />
    
    <AppLayout title="Workflow Approval">
        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-gradient-to-br from-violet-600 to-purple-600 rounded-xl shadow-lg">
                        <ClipboardDocumentCheckIcon class="h-6 w-6 text-white" />
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white">Workflow Approval</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Configure multi-level approval chains for documents</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a 
                        href="/guide/workflow-approval.html" 
                        target="_blank"
                        class="flex items-center gap-2 px-4 py-2 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 font-semibold rounded-xl transition-all"
                    >
                        <QuestionMarkCircleIcon class="h-5 w-5" />
                        Panduan
                    </a>
                    <button 
                        @click="openForm()"
                        class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-xl shadow-lg transition-all"
                    >
                        <PlusIcon class="h-5 w-5" />
                        Add Workflow
                    </button>
                </div>
            </div>

            <!-- Workflow List -->
            <div class="space-y-4">
                <div v-for="workflow in workflows" :key="workflow.id" class="glass-card rounded-2xl overflow-hidden">
                    <div class="p-4 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div :class="[
                                'w-3 h-3 rounded-full',
                                workflow.is_active ? 'bg-green-500' : 'bg-slate-400'
                            ]"></div>
                            <div>
                                <h3 class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    {{ workflow.name }}
                                    <span v-if="workflow.is_auto_approve" class="px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded text-xs uppercase tracking-wider">Auto Approve</span>
                                </h3>
                                <p class="text-sm text-slate-500">
                                    {{ documentTypes[workflow.document_type] }}
                                    <span v-if="workflow.condition_field && workflow.condition_value">
                                         — {{ workflow.condition_field }} {{ workflow.condition_operator }} {{ formatCurrency(workflow.condition_value) }}
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button 
                                @click="toggleWorkflow(workflow)"
                                :class="[
                                    'px-3 py-1.5 rounded-lg text-sm font-medium transition-colors',
                                    workflow.is_active 
                                        ? 'bg-green-100 text-green-700 hover:bg-green-200' 
                                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                                ]"
                            >
                                {{ workflow.is_active ? 'Active' : 'Inactive' }}
                            </button>
                            <button 
                                @click="openForm(workflow)"
                                class="p-2 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                            >
                                <PencilIcon class="h-5 w-5" />
                            </button>
                            <button 
                                @click="deleteWorkflow(workflow)"
                                class="p-2 text-slate-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                            >
                                <TrashIcon class="h-5 w-5" />
                            </button>
                        </div>
                    </div>
                    
                    <!-- Steps Preview -->
                    <div class="px-4 pb-4">
                        <div class="flex items-center gap-2 flex-wrap">
                            <template v-for="(step, index) in workflow.steps" :key="index">
                                <div class="flex items-center gap-2 px-3 py-1.5 bg-slate-100 dark:bg-slate-700 rounded-lg">
                                    <component :is="step.approver_type === 'user' ? UserIcon : ShieldCheckIcon" class="h-4 w-4 text-slate-500" />
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                        {{ getApproverName(step) }}
                                    </span>
                                </div>
                                <span v-if="index < workflow.steps.length - 1" class="text-slate-400">→</span>
                            </template>
                        </div>
                    </div>
                </div>

                <div v-if="workflows.length === 0" class="glass-card rounded-2xl p-12 text-center">
                    <ClipboardDocumentCheckIcon class="h-12 w-12 text-slate-400 mx-auto mb-4" />
                    <h3 class="font-semibold text-slate-600 dark:text-slate-400">No workflows configured</h3>
                    <p class="text-sm text-slate-500 mt-1">Create your first workflow to enable document approvals</p>
                </div>
            </div>

            <!-- Form Modal -->
            <div v-if="showForm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                    <div class="p-6 border-b border-slate-200 dark:border-slate-700">
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white">
                            {{ editingWorkflow ? 'Edit Workflow' : 'New Workflow' }}
                        </h3>
                    </div>
                    
                    <div class="p-6 space-y-6">
                        <!-- Basic Info -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Workflow Name</label>
                                <input v-model="form.name" type="text" class="w-full px-4 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500" placeholder="e.g. Large PO Approval" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Document Type</label>
                                <select v-model="form.document_type" class="w-full px-4 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                    <option v-for="(label, value) in documentTypes" :key="value" :value="value">{{ label }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Priority</label>
                                <input v-model.number="form.priority" type="number" class="w-full px-4 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500" placeholder="0" />
                            </div>
                        </div>

                        <!-- Condition -->
                        <div class="p-4 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">Condition (Optional)</label>
                            <div class="flex items-center gap-2">
                                <select v-model="form.condition_field" class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                    <option v-for="field in availableConditionFields" :key="field.value" :value="field.value">{{ field.label }}</option>
                                </select>
                                <select v-model="form.condition_operator" class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                    <option v-for="(label, value) in conditionOperators" :key="value" :value="value">{{ value }} ({{ label }})</option>
                                </select>
                                <input v-model.number="form.condition_value" type="number" class="flex-1 px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white" placeholder="Value (e.g. 50000000)" />
                            </div>
                            <p class="text-xs text-slate-500 mt-2">Leave value empty to apply this workflow to all documents of this type</p>
                        </div>

                        <!-- Auto Approve Toggle -->
                        <div class="flex items-center justify-between p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl border border-indigo-100 dark:border-indigo-800">
                            <div>
                                <h4 class="text-sm font-semibold text-indigo-900 dark:text-indigo-300">Auto Approve</h4>
                                <p class="text-xs text-indigo-700/70 dark:text-indigo-400 mt-1">If enabled, documents matching this condition will be automatically approved by the system without requiring manual steps.</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="form.is_auto_approve" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>

                        <!-- Approval Steps -->
                        <div v-if="!form.is_auto_approve">
                            <div class="flex items-center justify-between mb-3">
                                <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Approval Steps</label>
                                <button @click="addStep" type="button" class="text-sm text-blue-600 hover:text-blue-700 font-medium flex items-center gap-1">
                                    <PlusIcon class="h-4 w-4" /> Add Step
                                </button>
                            </div>
                            <div class="space-y-3">
                                <div v-for="(step, index) in form.steps" :key="index" class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                                    <div class="flex flex-col gap-1">
                                        <button @click="moveStep(index, -1)" :disabled="index === 0" class="p-1 text-slate-400 hover:text-slate-600 disabled:opacity-30">
                                            <ChevronUpIcon class="h-4 w-4" />
                                        </button>
                                        <button @click="moveStep(index, 1)" :disabled="index === form.steps.length - 1" class="p-1 text-slate-400 hover:text-slate-600 disabled:opacity-30">
                                            <ChevronDownIcon class="h-4 w-4" />
                                        </button>
                                    </div>
                                    <div class="w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center font-bold text-sm">
                                        {{ index + 1 }}
                                    </div>
                                    <select v-model="step.approver_type" class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                        <option value="user">User</option>
                                        <option value="role">Role</option>
                                    </select>
                                    <select v-model="step.approver_id" class="flex-1 px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                        <option value="">Select {{ step.approver_type === 'user' ? 'User' : 'Role' }}</option>
                                        <template v-if="step.approver_type === 'user'">
                                            <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
                                        </template>
                                        <template v-else>
                                            <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
                                        </template>
                                    </select>
                                    <button @click="removeStep(index)" :disabled="form.steps.length <= 1" class="p-2 text-slate-400 hover:text-red-500 disabled:opacity-30">
                                        <TrashIcon class="h-5 w-5" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-3">
                        <button @click="closeForm" class="px-4 py-2 text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                            Cancel
                        </button>
                        <button 
                            @click="saveWorkflow" 
                            :disabled="saving || !form.name || form.steps.some(s => !s.approver_id)"
                            class="px-6 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-xl shadow-lg transition-all disabled:opacity-50"
                        >
                            {{ saving ? 'Saving...' : (editingWorkflow ? 'Update' : 'Create') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
