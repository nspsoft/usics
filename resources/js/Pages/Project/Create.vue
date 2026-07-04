<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    BriefcaseIcon,
    ChevronLeftIcon,
    CalendarIcon,
    UserIcon,
    DocumentTextIcon,
    CommandLineIcon,
    SunIcon,
    MoonIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    users: Array
});

const form = useForm({
    name: '',
    description: '',
    start_date: '',
    end_date: '',
    manager_id: '',
    status: 'draft',
});

const submit = () => {
    form.post(route('projects.store'));
};

// --- Theme Reactive Sync ---
const isDark = ref(true);
const toggleTheme = () => {
    isDark.value = !isDark.value;
    if (isDark.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
};

let observer;
onMounted(() => {
    isDark.value = document.documentElement.classList.contains('dark');
    observer = new MutationObserver(() => {
        isDark.value = document.documentElement.classList.contains('dark');
    });
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
});
onUnmounted(() => {
    if (observer) observer.disconnect();
});
</script>

<template>
    <Head title="Initiate Project" />

    <AppLayout title="Initiate Project" :render-header="false">
        <div class="min-h-screen bg-slate-50 dark:bg-[#050510] relative overflow-hidden font-mono text-slate-800 dark:text-cyan-50 selection:bg-cyan-500/30 transition-colors duration-300">
            <!-- Dynamic Background -->
            <div class="fixed inset-0 z-0 pointer-events-none">
                <div class="absolute inset-0 bg-gradient-to-b from-cyan-500/5 to-slate-100 dark:from-cyan-950/20 dark:to-[#050510]"></div>
                <div class="perspective-grid absolute inset-0 opacity-[0.15] dark:opacity-20"></div>
            </div>

            <div class="relative z-10 p-6 max-w-4xl mx-auto space-y-8">
                <!-- Header -->
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-white/10 pb-4 backdrop-blur-sm">
                    <div class="flex items-center gap-4">
                        <Link :href="route('projects.index')" class="p-2 bg-white/70 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-lg text-slate-500 dark:text-slate-400 hover:text-cyan-600 dark:hover:text-cyan-400 hover:border-slate-350 dark:hover:border-cyan-500/30 transition-all">
                            <ChevronLeftIcon class="h-6 w-6" />
                        </Link>
                        <div>
                            <p class="text-[10px] text-cyan-650 dark:text-cyan-500/70 tracking-[0.2em] uppercase font-bold">SYSTEM_INITIALIZATION</p>
                            <h1 class="text-3xl font-black text-slate-900 dark:text-white dark:glow-text tracking-widest uppercase">
                                INITIATE PROJECT
                            </h1>
                        </div>
                    </div>

                    
                </div>

                <!-- Form Panel -->
                <div class="hud-panel p-8 bg-white/70 dark:bg-[#0a0a16]/80 backdrop-blur-2xl border border-slate-200 dark:border-white/10 rounded-2xl relative overflow-hidden shadow-sm dark:shadow-2xl">
                    <!-- Scanline effect -->
                    <div class="absolute inset-0 pointer-events-none bg-scanline opacity-[0.03]"></div>

                    <form @submit.prevent="submit" class="space-y-8 relative z-10">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Left Column -->
                            <div class="space-y-6">
                                <div class="space-y-2">
                                    <label class="flex items-center gap-2 text-[10px] text-cyan-600 dark:text-cyan-500/70 tracking-widest font-black uppercase">
                                        <CommandLineIcon class="h-3 w-3" /> PROJECT_IDENTIFIER
                                    </label>
                                    <input 
                                        v-model="form.name"
                                        type="text" 
                                        required
                                        placeholder="ENTER PROJECT NAME..."
                                        class="w-full bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-lg px-4 py-3 text-slate-800 dark:text-cyan-50 placeholder:text-slate-400 dark:placeholder:text-slate-700 focus:border-cyan-500/50 focus:ring-0 transition-all font-bold"
                                    />
                                    <div v-if="form.errors.name" class="text-rose-500 text-[10px] uppercase font-bold">{{ form.errors.name }}</div>
                                </div>

                                <div class="space-y-2">
                                    <label class="flex items-center gap-2 text-[10px] text-cyan-600 dark:text-cyan-500/70 tracking-widest font-black uppercase">
                                        <DocumentTextIcon class="h-3 w-3" /> OPERATIONAL_SCOPE
                                    </label>
                                    <textarea 
                                        v-model="form.description"
                                        rows="4"
                                        placeholder="DEFINE PROJECT PARAMETERS..."
                                        class="w-full bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-lg px-4 py-3 text-slate-800 dark:text-cyan-50 placeholder:text-slate-400 dark:placeholder:text-slate-700 focus:border-cyan-500/50 focus:ring-0 transition-all font-bold resize-none"
                                    ></textarea>
                                </div>
                            </div>

                            <!-- Right Column -->
                            <div class="space-y-6">
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <label class="flex items-center gap-2 text-[10px] text-cyan-600 dark:text-cyan-500/70 tracking-widest font-black uppercase">
                                            <CalendarIcon class="h-3 w-3" /> T_START
                                        </label>
                                        <input 
                                            v-model="form.start_date"
                                            type="date" 
                                            class="w-full bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-lg px-4 py-3 text-slate-800 dark:text-cyan-50 focus:border-cyan-500/50 focus:ring-0 transition-all font-bold"
                                        />
                                    </div>
                                    <div class="space-y-2">
                                        <label class="flex items-center gap-2 text-[10px] text-cyan-600 dark:text-cyan-500/70 tracking-widest font-black uppercase">
                                            <CalendarIcon class="h-3 w-3" /> T_END
                                        </label>
                                        <input 
                                            v-model="form.end_date"
                                            type="date" 
                                            class="w-full bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-lg px-4 py-3 text-slate-800 dark:text-cyan-50 focus:border-cyan-500/50 focus:ring-0 transition-all font-bold"
                                        />
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="flex items-center gap-2 text-[10px] text-cyan-600 dark:text-cyan-500/70 tracking-widest font-black uppercase">
                                        <UserIcon class="h-3 w-3" /> MISSION_COMMANDER
                                    </label>
                                    <select 
                                        v-model="form.manager_id"
                                        required
                                        class="w-full bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-lg px-4 py-3 text-slate-800 dark:text-cyan-50 focus:border-cyan-500/50 focus:ring-0 transition-all font-bold appearance-none bg-select-icon bg-white dark:bg-[#0a0a16]"
                                    >
                                        <option value="" disabled class="text-slate-800 dark:text-cyan-50 bg-white dark:bg-[#0a0a16]">SELECT COMMANDER...</option>
                                        <option v-for="user in users" :key="user.id" :value="user.id" class="text-slate-800 dark:text-cyan-50 bg-white dark:bg-[#0a0a16]">
                                            {{ user.name }}
                                        </option>
                                    </select>
                                    <div v-if="form.errors.manager_id" class="text-rose-500 text-[10px] uppercase font-bold">{{ form.errors.manager_id }}</div>
                                </div>

                                <div class="space-y-2">
                                    <label class="flex items-center gap-2 text-[10px] text-cyan-600 dark:text-cyan-500/70 tracking-widest font-black uppercase">
                                        <CommandLineIcon class="h-3 w-3" /> SYSTEM_STATUS
                                    </label>
                                    <div class="grid grid-cols-3 gap-2">
                                        <button 
                                            v-for="status in ['draft', 'active', 'on_hold']" 
                                            :key="status"
                                            type="button"
                                            @click="form.status = status"
                                            :class="[
                                                form.status === status ? 'bg-cyan-500/25 border-cyan-550 text-cyan-700 dark:text-cyan-400' : 'bg-white dark:bg-white/5 border-slate-200 dark:border-white/10 text-slate-400 dark:text-slate-500 hover:bg-slate-50 dark:hover:bg-white/10',
                                                'px-3 py-2 text-[9px] font-black uppercase tracking-widest border rounded transition-all truncate'
                                            ]"
                                        >
                                            {{ status.replace('_', ' ') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="pt-8 border-t border-slate-200 dark:border-white/5 flex items-center justify-end gap-6">
                            <Link :href="route('projects.index')" class="text-xs text-slate-400 dark:text-slate-500 hover:text-slate-700 dark:hover:text-white transition-colors uppercase tracking-[0.2em] font-bold">ABORT_SEQUENCE</Link>
                            <button 
                                type="submit" 
                                :disabled="form.processing"
                                class="px-10 py-4 bg-cyan-500/10 dark:bg-cyan-600/20 border border-cyan-500/30 dark:border-cyan-500/50 rounded-xl text-cyan-650 dark:text-cyan-400 hover:bg-cyan-500/20 dark:hover:bg-cyan-500/30 hover:border-cyan-550 transition-all font-bold tracking-[0.3em] uppercase shadow-sm dark:shadow-[0_0_20px_rgba(34,211,238,0.2)]"
                            >
                                <span v-if="form.processing" class="animate-pulse">PROCESSING...</span>
                                <span v-else>INITIALIZE_TRANSITION</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Space+Mono:ital,wght@0,400;0,700;1,400&display=swap');

.font-mono {
    font-family: 'Space Mono', monospace;
}

.perspective-grid {
    background-image: 
        linear-gradient(to right, rgba(34, 211, 238, 0.1) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(34, 211, 238, 0.1) 1px, transparent 1px);
    background-size: 40px 40px;
    transform: perspective(500px) rotateX(60deg) translateY(-100px) scale(2);
    animation: grid-move 20s linear infinite;
    transform-origin: top;
}

@keyframes grid-move {
    0% { background-position: 0 0; }
    100% { background-position: 0 40px; }
}

.bg-scanline {
    background: repeating-linear-gradient(
        0deg,
        transparent,
        transparent 1px,
        rgba(34, 211, 238, 0.1) 1px,
        rgba(34, 211, 238, 0.1) 2px
    );
}

.glow-text {
    text-shadow: 0 0 10px currentColor;
}

.bg-select-icon {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%2322d3ee' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 1rem center;
    background-repeat: no-repeat;
    background-size: 1.5em 1.5em;
}
</style>
