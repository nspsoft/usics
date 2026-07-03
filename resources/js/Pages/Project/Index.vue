<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    BriefcaseIcon,
    PlusIcon,
    CalendarIcon,
    UserCircleIcon,
    ChartBarIcon,
    ClockIcon,
    ChevronRightIcon,
    SunIcon,
    MoonIcon
} from '@heroicons/vue/24/outline';
import { formatDate } from '@/helpers';

const props = defineProps({
    projects: Array
});

const getStatusColor = (status) => {
    const s = status.toLowerCase();
    if (['completed', 'active'].includes(s)) return 'text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-500/10 border-emerald-200 dark:border-emerald-500/20';
    if (['in_progress', 'active'].includes(s)) return 'text-cyan-600 dark:text-cyan-400 bg-cyan-50 dark:bg-cyan-500/10 border-cyan-200 dark:border-cyan-500/20';
    if (['draft', 'on_hold'].includes(s)) return 'text-slate-600 dark:text-slate-400 bg-slate-50 dark:bg-slate-500/10 border-slate-200 dark:border-slate-500/20';
    return 'text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-500/10 border-rose-200 dark:border-rose-500/20';
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
    <Head title="Project Matrix" />

    <AppLayout title="Project Management" :render-header="false">
        <div class="min-h-screen bg-slate-50 dark:bg-[#050510] relative overflow-hidden font-mono text-slate-800 dark:text-cyan-50 selection:bg-cyan-500/30 transition-colors duration-300">
            <!-- Dynamic Background -->
            <div class="fixed inset-0 z-0 pointer-events-none">
                <div class="absolute inset-0 bg-gradient-to-b from-cyan-500/5 to-slate-100 dark:from-cyan-950/20 dark:to-[#050510]"></div>
                <div class="perspective-grid absolute inset-0 opacity-[0.15] dark:opacity-20"></div>
            </div>

            <div class="relative z-10 p-6 space-y-8">
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-slate-200 dark:border-white/10 pb-4 backdrop-blur-sm">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-0.5 text-[10px] bg-slate-200/50 dark:bg-white/5 border border-slate-300 dark:border-white/10 rounded text-slate-500 dark:text-slate-400 tracking-[0.2em]">PM.CORE.SYNC</span>
                            <span class="flex items-center gap-1.5 px-2 py-0.5 text-[10px] bg-cyan-500/10 border border-cyan-500/20 rounded text-cyan-600 dark:text-cyan-400 tracking-wider">
                                <span class="w-1.5 h-1.5 rounded-full bg-cyan-500 dark:bg-cyan-400"></span> GLOBAL PROJECT MATRIX
                            </span>
                        </div>
                        <h1 class="text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-cyan-600 via-slate-900 to-indigo-600 dark:from-cyan-400 dark:via-white dark:to-indigo-400 tracking-widest uppercase dark:glow-text">
                            PROJECT MATRIX
                        </h1>
                    </div>
                    
                    <div class="flex items-center gap-4">
                        <!-- Theme Toggle Button -->
                        <button 
                            @click="toggleTheme"
                            class="p-2.5 rounded-xl border border-slate-200 dark:border-white/10 bg-white/70 dark:bg-white/5 hover:bg-slate-50 dark:hover:bg-white/10 text-slate-700 dark:text-cyan-400 transition-all hover:scale-105 shadow-sm dark:shadow-none"
                            :title="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
                        >
                            <SunIcon v-if="isDark" class="h-5 w-5 text-amber-500" />
                            <MoonIcon v-else class="h-5 w-5 text-indigo-600" />
                        </button>

                        <Link :href="route('projects.create')" class="hud-btn flex items-center gap-2 px-6 py-2.5 bg-cyan-500/10 border border-cyan-500/30 rounded-lg text-cyan-600 dark:text-cyan-400 hover:bg-cyan-500/20 transition-all group">
                            <PlusIcon class="h-5 w-5 group-hover:rotate-90 transition-transform duration-300" />
                            <span class="font-bold tracking-widest text-sm">INITIATE NEW PROJECT</span>
                        </Link>
                    </div>
                </div>

                <!-- Projects Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="project in projects" :key="project.id" class="hud-card group">
                        <Link :href="route('projects.show', project.id)" class="block hud-content h-full relative z-10 bg-white/70 dark:bg-[#0a0a16]/60 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-xl overflow-hidden p-6 hover:border-cyan-500/30 transition-all shadow-sm dark:shadow-none">
                            <div class="flex justify-between items-start mb-4">
                                <div class="p-2.5 bg-cyan-500/10 border border-cyan-500/20 rounded-lg text-cyan-650 dark:text-cyan-400 group-hover:scale-110 transition-transform">
                                    <BriefcaseIcon class="h-6 w-6" />
                                </div>
                                <span class="px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-widest border" :class="getStatusColor(project.status)">
                                    {{ project.status }}
                                </span>
                            </div>

                            <h3 class="text-xl font-black text-slate-900 dark:text-white dark:glow-text mb-2 truncate group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors">{{ project.name }}</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2 mb-6 h-8">{{ project.description || 'No description provided.' }}</p>

                            <div class="space-y-4">
                                <!-- Progress -->
                                <div class="space-y-1.5">
                                    <div class="flex justify-between text-[10px] font-mono">
                                        <span class="text-slate-400 dark:text-slate-500 uppercase tracking-tighter">COMPLETION_PHASE</span>
                                        <span class="text-cyan-600 dark:text-cyan-400">{{ Math.round(project.progress || 0) }}%</span>
                                    </div>
                                    <div class="h-1.5 bg-slate-100 dark:bg-slate-900 rounded-full border border-slate-200 dark:border-white/5 overflow-hidden">
                                        <div class="h-full bg-gradient-to-r from-cyan-600 to-cyan-400 shadow-[0_0_10px_rgba(34,211,238,0.3)] transition-all duration-1000" :style="{ width: `${project.progress || 0}%` }"></div>
                                    </div>
                                </div>

                                <!-- Meta Info -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="flex items-center gap-2 text-slate-400 dark:text-slate-500">
                                        <CalendarIcon class="h-3.5 w-3.5" />
                                        <span class="text-[10px] uppercase truncate text-slate-500 dark:text-slate-450">{{ formatDate(project.end_date) }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-slate-400 dark:text-slate-500">
                                        <UserCircleIcon class="h-3.5 w-3.5" />
                                        <span class="text-[10px] uppercase truncate text-slate-500 dark:text-slate-450">{{ project.manager?.name || 'Unassigned' }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer Link HUD Style -->
                            <div class="mt-6 pt-4 border-t border-slate-100 dark:border-white/5 flex items-center justify-between group/footer">
                                <span class="text-[10px] text-slate-400 dark:text-slate-500 tracking-widest uppercase font-bold">ACCESS DATA NODE</span>
                                <ChevronRightIcon class="h-4 w-4 text-cyan-600 dark:text-cyan-500 group-hover/footer:translate-x-1 transition-transform" />
                            </div>
                        </Link>
                    </div>

                    <!-- Empty State -->
                    <div v-if="projects.length === 0" class="col-span-full py-20 text-center">
                        <div class="inline-block p-6 bg-white/50 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-2xl mb-4 group hover:border-cyan-500/30 transition-all">
                            <BriefcaseIcon class="h-16 w-16 text-slate-400 dark:text-slate-600 group-hover:text-cyan-500 transition-colors" />
                        </div>
                        <h3 class="text-xl font-black text-slate-900 dark:text-white dark:glow-text uppercase tracking-widest mb-2">NO ACTIVE PROJECTS FOUND</h3>
                        <p class="text-slate-550 dark:text-slate-400 text-xs mb-8">Initiate a new matrix to begin operational tracking.</p>
                        <Link :href="route('projects.create')" class="px-8 py-3 bg-cyan-500/10 border border-cyan-500/30 rounded-lg text-cyan-650 dark:text-cyan-400 hover:bg-cyan-500/20 transition-all font-bold tracking-widest text-sm">
                            INITIALIZE SYSTEM
                        </Link>
                    </div>
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

.hud-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.hud-card:hover {
    transform: translateY(-5px) scale(1.02);
    filter: drop-shadow(0 0 15px rgba(34, 211, 238, 0.3));
}

.glow-text {
    text-shadow: 0 0 10px currentColor;
}
</style>
