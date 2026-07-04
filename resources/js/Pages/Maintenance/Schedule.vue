<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, onMounted, onUnmounted } from 'vue';
import { 
    CalendarIcon, 
    CheckCircleIcon, 
    ClockIcon, 
    PlusIcon, 
    WrenchScrewdriverIcon,
    ExclamationTriangleIcon,
    SunIcon,
    MoonIcon
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

// --- Theme Reactive Sync ---
const isLightMode = ref(false);
const toggleTheme = () => {
    isLightMode.value = !isLightMode.value;
    if (isLightMode.value) {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    } else {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    }
};

let observer;
onMounted(() => {
    isLightMode.value = !document.documentElement.classList.contains('dark');
    observer = new MutationObserver(() => {
        isLightMode.value = !document.documentElement.classList.contains('dark');
    });
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
});

onUnmounted(() => {
    if (observer) observer.disconnect();
});
</script>

<template>
    <Head title="Preventive Maintenance" />

    <AppLayout title="Maintenance Schedule" :render-header="false">
        <div class="min-h-screen bg-slate-50 dark:bg-[#050510] relative overflow-hidden font-mono text-slate-800 dark:text-cyan-50 selection:bg-cyan-500/30 transition-colors duration-300">
            
            <!-- Dynamic Background -->
            <div class="fixed inset-0 z-0 pointer-events-none">
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/5 to-slate-100 dark:from-indigo-955/20 dark:to-[#050510]"></div>
                 <div class="perspective-grid absolute inset-0 opacity-[0.05] dark:opacity-10"></div>
            </div>

            <div class="relative z-10 p-6 space-y-6 max-w-7xl mx-auto">
                
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-slate-200 dark:border-white/10 pb-4">
                    <div>
                         <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-0.5 text-[10px] bg-emerald-500/10 border border-emerald-500/20 rounded text-emerald-700 dark:text-emerald-400 tracking-[0.2em] uppercase">
                                System Healthy
                            </span>
                            <span class="px-2 py-0.5 text-[10px] bg-slate-200 dark:bg-white/5 border border-slate-300 dark:border-white/10 rounded text-slate-500 dark:text-slate-400 tracking-[0.2em] uppercase">MAINT.SCH.V1</span>
                        </div>
                        <h1 class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-cyan-605 via-slate-800 to-cyan-650 dark:from-cyan-400 dark:via-white dark:to-cyan-400 tracking-widest uppercase dark:glow-text">
                            PREVENTIVE SCHEDULE
                        </h1>
                    </div>
                    <div class="flex items-center gap-4">
                        

                        <button 
                            @click="showCreateModal = true"
                            class="px-4 py-2 bg-gradient-to-r from-cyan-600 to-cyan-500 hover:from-cyan-500 hover:to-cyan-400 text-white font-bold rounded-lg shadow-sm dark:shadow-[0_0_15px_rgba(34,211,238,0.3)] transition-all flex items-center gap-2 text-sm border-0 cursor-pointer"
                        >
                            <PlusIcon class="h-4 w-4" /> NEW SCHEDULE
                        </button>
                    </div>
                </div>

                <!-- KPI Grid -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="hud-panel p-4 flex items-center justify-between bg-white/70 dark:bg-[#0a0a16]/60 border border-slate-200 dark:border-white/5 rounded-xl shadow-sm dark:shadow-none">
                        <div>
                            <p class="text-[10px] text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1">TOTAL TASKS</p>
                            <h3 class="text-2xl font-black text-slate-900 dark:text-white">{{ stats.total_schedules }}</h3>
                        </div>
                        <CalendarIcon class="h-8 w-8 text-slate-400 dark:text-slate-650" />
                    </div>
                    <div class="hud-panel p-4 flex items-center justify-between border-rose-200 dark:border-rose-500/30 bg-rose-50/50 dark:bg-rose-500/5 rounded-xl border shadow-sm dark:shadow-none">
                        <div>
                            <p class="text-[10px] text-rose-700 dark:text-rose-450 uppercase tracking-widest mb-1">OVERDUE</p>
                            <h3 class="text-2xl font-black text-rose-600 dark:text-rose-400 animate-pulse">{{ stats.overdue }}</h3>
                        </div>
                        <ExclamationTriangleIcon class="h-8 w-8 text-rose-500" />
                    </div>
                    <div class="hud-panel p-4 flex items-center justify-between border-amber-250 dark:border-amber-500/30 bg-amber-50/50 dark:bg-amber-500/5 rounded-xl border shadow-sm dark:shadow-none">
                        <div>
                            <p class="text-[10px] text-amber-700 dark:text-amber-450 uppercase tracking-widest mb-1">DUE SOON (7 DAYS)</p>
                            <h3 class="text-2xl font-black text-amber-600 dark:text-amber-500">{{ stats.upcoming }}</h3>
                        </div>
                        <ClockIcon class="h-8 w-8 text-amber-500" />
                    </div>
                    <div class="hud-panel p-4 flex items-center justify-between border-emerald-200 dark:border-emerald-500/30 bg-emerald-50/50 dark:bg-emerald-500/5 rounded-xl border shadow-sm dark:shadow-none">
                        <div>
                            <p class="text-[10px] text-emerald-700 dark:text-emerald-450 uppercase tracking-widest mb-1">IN COMPLIANCE</p>
                            <h3 class="text-2xl font-black text-emerald-600 dark:text-emerald-500">{{ stats.healthy }}</h3>
                        </div>
                        <CheckCircleIcon class="h-8 w-8 text-emerald-500" />
                    </div>
                </div>

                <!-- Schedule List -->
                <div class="space-y-4">
                    <h3 class="text-sm font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest flex items-center gap-2">
                        <WrenchScrewdriverIcon class="h-4 w-4" /> Maintenance Pipeline
                    </h3>
                    
                    <div class="grid gap-4">
                        <div v-for="task in schedules" :key="task.id" 
                            class="hud-panel p-4 flex flex-col md:flex-row md:items-center justify-between gap-4 group hover:border-cyan-500/30 transition-all bg-white/70 dark:bg-[#0a0a16]/60 border border-slate-200 dark:border-white/5 rounded-xl shadow-sm dark:shadow-none"
                        >
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 rounded-lg bg-slate-100 dark:bg-white/5 border border-slate-200 dark:border-white/10 flex items-center justify-center font-black text-lg text-slate-500 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors">
                                    {{ task.machine_code.split('-')[1] || 'M' }}
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-800 dark:text-white group-hover:text-cyan-600 dark:group-hover:text-cyan-300 transition-colors">{{ task.task }}</h4>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">{{ task.machine }}</p>
                                    <p class="text-[10px] text-slate-550 dark:text-slate-500">{{ task.description }}</p>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-8 text-right min-w-[300px] justify-end">
                                <div>
                                    <p class="text-[10px] text-slate-500 uppercase tracking-wider">Last Done</p>
                                    <p class="text-xs font-mono text-slate-700 dark:text-slate-300">{{ task.last_performed }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-slate-500 uppercase tracking-wider">Next Due</p>
                                    <p class="text-xs font-mono font-bold" :class="task.days_due < 0 ? 'text-rose-600 dark:text-rose-400' : (task.days_due < 7 ? 'text-amber-600 dark:text-amber-405' : 'text-emerald-600 dark:text-emerald-450')">
                                        {{ task.next_due }}
                                    </p>
                                </div>
                                <button 
                                    @click="completeTask(task.id)"
                                    class="p-2 rounded-lg bg-slate-50 border border-slate-200 dark:bg-white/5 dark:border-white/10 hover:bg-emerald-500/10 hover:border-emerald-500/30 hover:text-emerald-600 dark:hover:bg-emerald-500/20 dark:hover:border-emerald-500/50 dark:hover:text-emerald-400 transition-all text-slate-400 dark:text-slate-500 cursor-pointer"
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
                <div class="bg-white dark:bg-[#0f172a] border border-slate-250 dark:border-white/10 p-6 rounded-xl w-full max-w-lg shadow-2xl text-slate-800 dark:text-slate-200">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 uppercase tracking-wider">Create Schedule</h3>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Machine</label>
                            <select v-model="form.machine_id" class="w-full bg-white dark:bg-[#0f172a] border border-slate-200 dark:border-white/10 rounded p-2 text-slate-800 dark:text-white outline-none focus:border-cyan-500">
                                <option v-for="m in machines" :key="m.id" :value="m.id" class="bg-white dark:bg-[#0f172a] text-slate-805 dark:text-white">{{ m.name }} ({{ m.code }})</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-slate-505 dark:text-slate-400 mb-1">Task Name</label>
                            <input v-model="form.task_name" type="text" class="w-full bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded p-2 text-slate-850 dark:text-white outline-none focus:border-cyan-500 font-mono" placeholder="e.g. Monthly Oiling">
                        </div>
                        <div>
                            <label class="block text-xs text-slate-505 dark:text-slate-400 mb-1">Description</label>
                            <textarea v-model="form.description" class="w-full bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded p-2 text-slate-850 dark:text-white outline-none focus:border-cyan-500 font-mono" rows="3"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs text-slate-505 dark:text-slate-400 mb-1">Frequency (Days)</label>
                            <input v-model="form.frequency_days" type="number" class="w-full bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded p-2 text-slate-850 dark:text-white outline-none focus:border-cyan-500 font-mono">
                        </div>
                        <div class="flex justify-end gap-2 pt-4">
                            <button type="button" @click="showCreateModal = false" class="px-4 py-2 text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white bg-slate-50 dark:bg-transparent rounded-lg border border-slate-200 dark:border-transparent cursor-pointer">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-500 border-0 cursor-pointer shadow-sm">Create Schedule</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<style scoped>
.hud-panel {
    backdrop-filter: blur(12px);
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

.dark .glow-text {
    text-shadow: 0 0 10px rgba(16, 185, 129, 0.3);
}
</style>
