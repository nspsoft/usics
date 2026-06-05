<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';
import { 
    CalendarIcon, 
    CheckCircleIcon, 
    ClockIcon, 
    PlusIcon, 
    WrenchScrewdriverIcon,
    ExclamationTriangleIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    schedules: Array,
    stats: Object,
    machines: Array
});

const showCreateModal = ref(false);
const form = useForm({
    machine_id: '',
    task_name: '',
    description: '',
    frequency_days: 30,
});

const submit = () => {
    form.post(route('maintenance.schedule.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
            form.reset();
        }
    });
};

const completeTask = (id) => {
    if (confirm('Mark this maintenance task as completed?')) {
        useForm({}).post(route('maintenance.schedule.complete', id));
    }
};
</script>

<template>
    <Head title="Preventive Maintenance" />

    <AppLayout title="Maintenance Schedule" :render-header="false">
        <div class="min-h-screen bg-[#050510] relative overflow-hidden font-mono text-cyan-50 selection:bg-cyan-500/30">
            
            <!-- Dynamic Background -->
            <div class="fixed inset-0 z-0 pointer-events-none">
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-950/20 to-[#050510]"></div>
                <!-- CSS Grid -->
                 <div class="perspective-grid absolute inset-0 opacity-10"></div>
            </div>

            <div class="relative z-10 p-6 space-y-6 max-w-7xl mx-auto">
                
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-white/10 pb-4">
                    <div>
                         <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-0.5 text-[10px] bg-emerald-500/10 border border-emerald-500/20 rounded text-emerald-400 tracking-[0.2em] uppercase">
                                System Healthy
                            </span>
                            <span class="px-2 py-0.5 text-[10px] bg-white/5 border border-white/10 rounded text-slate-400 tracking-[0.2em] uppercase">MAINT.SCH.V1</span>
                        </div>
                        <h1 class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 via-white to-cyan-400 tracking-widest uppercase glow-text">
                            PREVENTIVE SCHEDULE
                        </h1>
                    </div>
                    <button 
                        @click="showCreateModal = true"
                        class="px-4 py-2 bg-gradient-to-r from-cyan-600 to-cyan-500 hover:from-cyan-500 hover:to-cyan-400 text-white font-bold rounded-lg shadow-[0_0_15px_rgba(34,211,238,0.3)] transition-all flex items-center gap-2 text-sm"
                    >
                        <PlusIcon class="h-4 w-4" /> NEW SCHEDULE
                    </button>
                </div>

                <!-- KPI Grid -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="hud-panel p-4 flex items-center justify-between">
                        <div>
                            <p class="text-[10px] text-slate-400 uppercase tracking-widest mb-1">TOTAL TASKS</p>
                            <h3 class="text-2xl font-black text-white">{{ stats.total_schedules }}</h3>
                        </div>
                        <CalendarIcon class="h-8 w-8 text-slate-600" />
                    </div>
                    <div class="hud-panel p-4 flex items-center justify-between border-rose-500/30 bg-rose-500/5">
                        <div>
                            <p class="text-[10px] text-rose-400 uppercase tracking-widest mb-1">OVERDUE</p>
                            <h3 class="text-2xl font-black text-rose-500 animate-pulse">{{ stats.overdue }}</h3>
                        </div>
                        <ExclamationTriangleIcon class="h-8 w-8 text-rose-500" />
                    </div>
                    <div class="hud-panel p-4 flex items-center justify-between border-amber-500/30 bg-amber-500/5">
                        <div>
                            <p class="text-[10px] text-amber-400 uppercase tracking-widest mb-1">DUE SOON (7 DAYS)</p>
                            <h3 class="text-2xl font-black text-amber-500">{{ stats.upcoming }}</h3>
                        </div>
                        <ClockIcon class="h-8 w-8 text-amber-500" />
                    </div>
                    <div class="hud-panel p-4 flex items-center justify-between border-emerald-500/30 bg-emerald-500/5">
                        <div>
                            <p class="text-[10px] text-emerald-400 uppercase tracking-widest mb-1">IN COMPLIANCE</p>
                            <h3 class="text-2xl font-black text-emerald-500">{{ stats.healthy }}</h3>
                        </div>
                        <CheckCircleIcon class="h-8 w-8 text-emerald-500" />
                    </div>
                </div>

                <!-- Schedule List -->
                <div class="space-y-4">
                    <h3 class="text-sm font-bold text-slate-400 uppercase tracking-widest flex items-center gap-2">
                        <WrenchScrewdriverIcon class="h-4 w-4" /> Maintenance Pipeline
                    </h3>
                    
                    <div class="grid gap-4">
                        <div v-for="task in schedules" :key="task.id" 
                            class="hud-panel p-4 flex flex-col md:flex-row md:items-center justify-between gap-4 group hover:border-cyan-500/30 transition-all"
                        >
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center font-black text-lg text-slate-500 group-hover:text-cyan-400 transition-colors">
                                    {{ task.machine_code.split('-')[1] || 'M' }}
                                </div>
                                <div>
                                    <h4 class="font-bold text-white group-hover:text-cyan-300 transition-colors">{{ task.task }}</h4>
                                    <p class="text-xs text-slate-400 mb-1">{{ task.machine }}</p>
                                    <p class="text-[10px] text-slate-500">{{ task.description }}</p>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-8 text-right min-w-[300px] justify-end">
                                <div>
                                    <p class="text-[10px] text-slate-500 uppercase tracking-wider">Last Done</p>
                                    <p class="text-xs font-mono text-slate-300">{{ task.last_performed }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-slate-500 uppercase tracking-wider">Next Due</p>
                                    <p class="text-xs font-mono font-bold" :class="task.days_due < 0 ? 'text-rose-400' : (task.days_due < 7 ? 'text-amber-400' : 'text-emerald-400')">
                                        {{ task.next_due }}
                                    </p>
                                </div>
                                <button 
                                    @click="completeTask(task.id)"
                                    class="p-2 rounded-lg bg-white/5 border border-white/10 hover:bg-emerald-500/20 hover:border-emerald-500/50 hover:text-emerald-400 transition-all text-slate-500"
                                    title="Mark Completed"
                                >
                                    <CheckCircleIcon class="h-5 w-5" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

             <!-- Modal -->
            <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
                <div class="bg-[#0f172a] border border-white/10 p-6 rounded-xl w-full max-w-lg shadow-2xl">
                    <h3 class="text-lg font-bold text-white mb-4 uppercase tracking-wider">Create Schedule</h3>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Machine</label>
                            <select v-model="form.machine_id" class="w-full bg-[#0f172a] border border-white/10 rounded p-2 text-white outline-none focus:border-cyan-500">
                                <option v-for="m in machines" :key="m.id" :value="m.id" class="bg-[#0f172a] text-white">{{ m.name }} ({{ m.code }})</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Task Name</label>
                            <input v-model="form.task_name" type="text" class="w-full bg-white/5 border border-white/10 rounded p-2 text-white outline-none focus:border-cyan-500" placeholder="e.g. Monthly Oiling">
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Description</label>
                            <textarea v-model="form.description" class="w-full bg-white/5 border border-white/10 rounded p-2 text-white outline-none focus:border-cyan-500" rows="3"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Frequency (Days)</label>
                            <input v-model="form.frequency_days" type="number" class="w-full bg-white/5 border border-white/10 rounded p-2 text-white outline-none focus:border-cyan-500">
                        </div>
                        <div class="flex justify-end gap-2 pt-4">
                            <button type="button" @click="showCreateModal = false" class="px-4 py-2 text-slate-400 hover:text-white">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-cyan-600 text-white rounded hover:bg-cyan-500">Create Schedule</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<style scoped>
.hud-panel {
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
}

.perspective-grid {
    background-image: 
        linear-gradient(to right, rgba(16, 185, 129, 0.05) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(16, 185, 129, 0.05) 1px, transparent 1px);
    background-size: 40px 40px;
    transform: perspective(500px) rotateX(60deg) translateY(-100px) scale(2);
    animation: grid-move 20s linear infinite;
    transform-origin: top;
}

@keyframes grid-move {
    0% { background-position: 0 0; }
    100% { background-position: 0 40px; }
}

.glow-text {
    text-shadow: 0 0 10px rgba(16, 185, 129, 0.3);
}
</style>
