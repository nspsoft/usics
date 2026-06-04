<script setup>
import { CheckCircleIcon, XCircleIcon } from '@heroicons/vue/24/outline';
import { formatNumber, formatCurrency } from '@/helpers';

const props = defineProps({
    request: Object,
    documentStatus: {
        type: String,
        default: 'draft'
    },
    approvalStatus: {
        type: String,
        default: 'pending'
    }
});

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', { 
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
};
</script>

<template>
    <div v-if="request" class="glass-card rounded-3xl shadow-sm overflow-hidden mt-6">
        <div class="p-6 border-b border-slate-200 dark:border-slate-800">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2 font-mono">
                <div class="h-6 w-1 bg-yellow-500 rounded-full"></div>
                APPROVAL_CHAIN
            </h3>
            <div class="text-[11px] text-slate-500 mt-1 uppercase tracking-widest font-bold">Workflow: {{ request.workflow?.name }}</div>
        </div>
        <div class="p-6">
            <div class="relative">
                <!-- Line connecting steps -->
                <div class="absolute left-6 top-10 bottom-10 w-0.5 bg-slate-200 dark:bg-slate-800"></div>
                
                <div class="space-y-8">
                    <div v-for="step in request.workflow?.steps" :key="step.id" class="relative pl-14">
                        <!-- Step Indicator -->
                        <div class="absolute left-0 top-1 w-12 h-12 rounded-full border-4 border-white dark:border-slate-900 flex items-center justify-center z-10"
                             :class="[
                                 request.current_step > step.step_order || approvalStatus === 'approved' 
                                    ? 'bg-green-500 text-white' 
                                    : (request.current_step === step.step_order && approvalStatus !== 'rejected'
                                        ? 'bg-yellow-500 text-white'
                                        : 'bg-slate-200 dark:bg-slate-700 text-slate-500')
                             ]"
                        >
                            <span v-if="request.current_step > step.step_order || approvalStatus === 'approved'"><CheckCircleIcon class="h-6 w-6"/></span>
                            <span v-else-if="request.current_step === step.step_order && approvalStatus === 'rejected'"><XCircleIcon class="h-6 w-6 text-red-500"/></span>
                            <span v-else class="font-bold font-mono">{{ step.step_order }}</span>
                        </div>
                        
                        <!-- Step Content -->
                        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-2xl p-4 border border-slate-200 dark:border-slate-800">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="text-sm font-bold text-slate-900 dark:text-white">{{ step.name }}</div>
                                    <div class="text-[10px] text-slate-500 mt-1 uppercase tracking-widest font-bold">
                                        Approver: {{ step.approver_type === 'role' ? `Role - ${step.role?.name}` : `User - ${step.user?.name}` }}
                                    </div>
                                </div>
                                <!-- History -->
                                <div v-if="request.histories?.find(h => h.step_order === step.step_order)" class="text-right">
                                    <div class="text-[10px] text-slate-500 uppercase tracking-widest font-bold">Action By</div>
                                    <div class="text-xs font-bold" :class="request.histories?.find(h => h.step_order === step.step_order).action === 'approved' ? 'text-green-500' : 'text-red-500'">
                                        {{ request.histories?.find(h => h.step_order === step.step_order).acted_by?.name }}
                                    </div>
                                    <div class="text-[10px] text-slate-500">{{ formatDate(request.histories?.find(h => h.step_order === step.step_order).created_at) }}</div>
                                </div>
                            </div>
                            
                            <!-- Notes -->
                            <div v-if="request.histories?.find(h => h.step_order === step.step_order)?.notes" class="mt-3 bg-white dark:bg-slate-900 rounded-xl p-3 text-xs text-slate-600 dark:text-slate-300 italic border border-slate-100 dark:border-slate-800">
                                "{{ request.histories?.find(h => h.step_order === step.step_order).notes }}"
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
