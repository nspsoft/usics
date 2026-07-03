<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TimelineChart from './Partials/TimelineChart.vue';
import {
    BriefcaseIcon,
    ChevronLeftIcon,
    PlusIcon,
    CalendarIcon,
    UserCircleIcon,
    CheckCircleIcon,
    ArrowPathIcon,
    UsersIcon,
    ListBulletIcon,
    ChartPieIcon,
    PencilSquareIcon,
    TrashIcon,
    XMarkIcon,
    PaperClipIcon,
    ArrowDownTrayIcon,
    PrinterIcon,
    BookOpenIcon,
    SunIcon,
    MoonIcon
} from '@heroicons/vue/24/outline';
import { formatDate, formatDateTime } from '@/helpers';

const props = defineProps({
    project: Object,
    users: Array
});

const activeTab = ref('tasks');
const showTaskModal = ref(false);
const showMemberModal = ref(false);
const editingTask = ref(null);

const taskForm = useForm({
    name: '',
    description: '',
    start_date_plan: '',
    end_date_plan: '',
    priority: 'medium',
    status: 'draft',
    progress: 0,
    members: []
});

const memberForm = useForm({
    user_id: '',
    role: 'Member'
});

const openCreateTaskModal = () => {
    editingTask.value = null;
    taskForm.reset();
    // Default dates to project window if empty
    taskForm.start_date_plan = props.project.start_date; 
    taskForm.end_date_plan = props.project.end_date;
    showTaskModal.value = true;
};

const openEditTaskModal = (task) => {
    editingTask.value = task;
    taskForm.name = task.name;
    taskForm.description = task.description;
    taskForm.start_date_plan = task.start_date_plan;
    taskForm.end_date_plan = task.end_date_plan;
    taskForm.priority = task.priority;
    taskForm.status = task.status;
    taskForm.progress = task.progress;
    taskForm.members = task.members.map(m => m.id);
    showTaskModal.value = true;
};

const submitTask = () => {
    if (editingTask.value) {
        taskForm.put(route('projects.tasks.update', editingTask.value.id), {
            onSuccess: () => showTaskModal.value = false
        });
    } else {
        taskForm.post(route('projects.tasks.store', props.project.id), {
            onSuccess: () => showTaskModal.value = false
        });
    }
};

const deleteTask = (task) => {
    if (confirm('Are you sure you want to delete this task?')) {
        router.delete(route('projects.tasks.destroy', task.id));
    }
};

const submitMember = () => {
    memberForm.post(route('projects.members.store', props.project.id), {
        onSuccess: () => {
            showMemberModal.value = false;
            memberForm.reset();
        }
    });
};

const removeMember = (user) => {
    if (confirm(`Remove ${user.name} from the project?`)) {
        router.delete(route('projects.members.destroy', [props.project.id, user.id]));
    }
};

const getStatusColor = (status) => {
    const s = status.toLowerCase();
    if (['completed', 'active'].includes(s)) return 'text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-500/10 border-emerald-200 dark:border-emerald-500/20';
    if (['in_progress', 'active'].includes(s)) return 'text-cyan-600 dark:text-cyan-400 bg-cyan-50 dark:bg-cyan-500/10 border-cyan-200 dark:border-cyan-500/20';
    return 'text-slate-600 dark:text-slate-400 bg-slate-50 dark:bg-slate-500/10 border-slate-200 dark:border-slate-500/20';
};

const fileForm = useForm({
    file: null
});

const handleFileUpload = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    
    fileForm.file = file;
    fileForm.post(route('projects.tasks.attachments.store', editingTask.value.id), {
        onSuccess: () => {
            fileForm.reset();
            // Refresh editing task data
            if (editingTask.value) {
                const updatedTask = props.project.tasks.find(t => t.id === editingTask.value.id);
                if (updatedTask) editingTask.value = updatedTask;
            }
        },
        preserveScroll: true
    });
};

const deleteAttachment = (attachment) => {
    if (confirm('Delete this attachment?')) {
        router.delete(route('projects.tasks.attachments.destroy', attachment.id), {
             onSuccess: () => {
                // Refresh editing task data
                if (editingTask.value) {
                    const updatedTask = props.project.tasks.find(t => t.id === editingTask.value.id);
                    if (updatedTask) editingTask.value = updatedTask;
                }
            },
            preserveScroll: true
        });
    }
};

const getMemberProgress = (memberId) => {
    if (!props.project.tasks || props.project.tasks.length === 0) return 0;
    
    // Find all tasks assigned to this member
    const memberTasks = props.project.tasks.filter(task => 
        task.members && task.members.some(m => m.id === memberId)
    );
    
    if (memberTasks.length === 0) return 0;
    
    // Calculate average progress
    const totalProgress = memberTasks.reduce((sum, task) => sum + (parseFloat(task.progress) || 0), 0);
    return Math.round(totalProgress / memberTasks.length);
};

const getMemberTaskCount = (memberId) => {
    if (!props.project.tasks) return 0;
    return props.project.tasks.filter(task => 
        task.members && task.members.some(m => m.id === memberId)
    ).length;
};

const handlePrint = () => {
    window.print();
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
    <Head :title="project.name" />

    <AppLayout :title="project.name" :render-header="false">
        <div class="min-h-screen bg-slate-50 dark:bg-[#050510] relative overflow-hidden font-mono text-slate-800 dark:text-cyan-50 print:hidden transition-colors duration-300">
            <!-- Dynamic Background -->
            <div class="fixed inset-0 z-0 pointer-events-none print:hidden">
                <div class="absolute inset-0 bg-gradient-to-b from-cyan-500/5 to-slate-100 dark:from-cyan-950/20 dark:to-[#050510]"></div>
                <div class="perspective-grid absolute inset-0 opacity-[0.15] dark:opacity-20"></div>
            </div>

            <div class="relative z-10 p-6 space-y-8 print:hidden">
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-slate-200 dark:border-white/10 pb-4 backdrop-blur-sm">
                    <div class="flex items-center gap-4">
                        <Link :href="route('projects.index')" class="p-2 bg-white/70 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-lg text-slate-500 dark:text-slate-400 hover:text-cyan-600 dark:hover:text-cyan-400 transition-all print:hidden">
                            <ChevronLeftIcon class="h-6 w-6" />
                        </Link>
                        <!-- Theme Toggle Button -->
                        <button 
                            @click="toggleTheme"
                            class="p-2.5 rounded-xl border border-slate-200 dark:border-white/10 bg-white/70 dark:bg-white/5 hover:bg-slate-50 dark:hover:bg-white/10 text-slate-700 dark:text-cyan-400 transition-all hover:scale-105 shadow-sm dark:shadow-none"
                            :title="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
                        >
                            <SunIcon v-if="isDark" class="h-5 w-5 text-amber-500" />
                            <MoonIcon v-else class="h-5 w-5 text-indigo-600" />
                        </button>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="px-2 py-0.5 text-[8px] bg-cyan-500/10 border border-cyan-500/20 rounded text-cyan-600 dark:text-cyan-400 uppercase tracking-widest">PROJECT_ID: {{ project.id }}</span>
                                <span class="px-2 py-0.5 text-[8px] rounded border uppercase tracking-widest" :class="getStatusColor(project.status)">{{ project.status }}</span>
                            </div>
                            <h1 class="text-3xl font-black text-slate-900 dark:text-white dark:glow-text tracking-widest uppercase">
                                {{ project.name }}
                            </h1>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 print:hidden">
                        <button 
                            @click="handlePrint"
                            class="flex items-center gap-2 px-6 py-2.5 bg-white/70 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl text-xs font-bold text-cyan-600 dark:text-cyan-400 hover:bg-slate-50 dark:hover:bg-white/10 transition-all uppercase tracking-widest shadow-sm dark:shadow-none"
                        >
                            <PrinterIcon class="h-5 w-5" />
                            Print Blueprint
                        </button>
                    </div>
                </div>

                <!-- Stats Overview -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="hud-panel p-4 bg-white/75 dark:bg-white/5 border border-slate-200 dark:border-white/5 rounded-xl shadow-sm dark:shadow-none">
                        <p class="text-[10px] text-slate-500 uppercase tracking-widest mb-1">COMPLETION_PHASE</p>
                        <div class="flex items-end gap-2">
                            <span class="text-2xl font-black text-slate-900 dark:text-white dark:glow-text">{{ Math.round(project.progress || 0) }}%</span>
                            <div class="flex-1 mb-2 h-1 bg-slate-100 dark:bg-slate-900 rounded-full overflow-hidden">
                                <div class="h-full bg-cyan-500" :style="{ width: `${project.progress}%` }"></div>
                            </div>
                        </div>
                    </div>
                    <div class="hud-panel p-4 bg-white/75 dark:bg-white/5 border border-slate-200 dark:border-white/5 rounded-xl shadow-sm dark:shadow-none">
                        <p class="text-[10px] text-slate-500 uppercase tracking-widest mb-1">TASK_COUNT</p>
                        <span class="text-2xl font-black text-slate-900 dark:text-white dark:glow-text">{{ project.tasks?.length || 0 }}</span>
                    </div>
                    <div class="hud-panel p-4 bg-white/75 dark:bg-white/5 border border-slate-200 dark:border-white/5 rounded-xl shadow-sm dark:shadow-none">
                        <p class="text-[10px] text-slate-500 uppercase tracking-widest mb-1">CREW_COMPLEMENT</p>
                        <span class="text-2xl font-black text-slate-900 dark:text-white dark:glow-text">{{ project.members?.length || 0 }}</span>
                    </div>
                    <div class="hud-panel p-4 bg-white/75 dark:bg-white/5 border border-slate-200 dark:border-white/5 rounded-xl shadow-sm dark:shadow-none">
                        <p class="text-[10px] text-slate-500 uppercase tracking-widest mb-1">TARGET_WINDOW</p>
                        <span class="text-lg font-black text-cyan-600 dark:text-cyan-400 truncate">{{ formatDate(project.end_date) }}</span>
                    </div>
                </div>

                <!-- Main Content Tabs -->
                <div class="hud-panel bg-white/75 dark:bg-[#0a0a16]/60 backdrop-blur-xl border border-slate-200 dark:border-white/10 rounded-2xl overflow-hidden shadow-sm dark:shadow-none min-h-[500px]">
                    <div class="flex border-b border-slate-100 dark:border-white/5">
                        <button v-for="tab in ['tasks', 'timeline', 'team']" :key="tab" @click="activeTab = tab"
                            :class="[activeTab === tab ? 'text-cyan-600 dark:text-cyan-400 border-b-2 border-cyan-500 bg-slate-50/50 dark:bg-white/5' : 'text-slate-500', 'px-8 py-4 text-xs font-black uppercase tracking-[0.2em] transition-all hover:bg-slate-100/50 dark:hover:bg-white/5']"
                        >
                            {{ tab }}
                        </button>
                    </div>

                    <div class="p-8">
                        <!-- Tasks View -->
                        <div v-if="activeTab === 'tasks'" class="space-y-8">
                            <div class="flex justify-between items-center">
                                <h3 class="flex items-center gap-2 text-sm font-bold text-cyan-600 dark:text-cyan-300 tracking-widest uppercase">
                                    <ListBulletIcon class="h-4 w-4" /> TASK_MANIFEST
                                </h3>
                                <button @click="openCreateTaskModal" class="hud-btn flex items-center gap-2 px-4 py-1.5 bg-cyan-500/10 border border-cyan-500/20 rounded text-[10px] text-cyan-600 dark:text-cyan-400 hover:bg-cyan-500/20 transition-all font-bold tracking-widest">
                                    <PlusIcon class="h-3.5 w-3.5" /> ADD_TASK
                                </button>
                            </div>

                            <div class="grid grid-cols-1 gap-4">
                                <div v-for="task in project.tasks" :key="task.id" @click="openEditTaskModal(task)" class="p-5 bg-white/80 dark:bg-white/5 border border-slate-200 dark:border-white/5 rounded-xl hover:border-cyan-500/30 transition-all group cursor-pointer shadow-sm dark:shadow-none">
                                    <div class="flex justify-between items-start mb-4">
                                        <div>
                                            <div class="flex items-center gap-3 mb-1">
                                                <h4 class="text-sm font-black text-slate-900 dark:text-white uppercase">{{ task.name }}</h4>
                                                <span class="text-[8px] px-1.5 py-0.5 rounded text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-white/10 uppercase">{{ task.status }}</span>
                                            </div>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2">{{ task.description }}</p>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <button @click.stop="openEditTaskModal(task)" class="p-1.5 hover:bg-slate-100 dark:hover:bg-white/5 rounded text-slate-500 hover:text-cyan-600 dark:hover:text-cyan-400 transition-colors">
                                                <PencilSquareIcon class="h-4 w-4" />
                                            </button>
                                            <button @click.stop="deleteTask(task)" class="p-1.5 hover:bg-slate-100 dark:hover:bg-white/5 rounded text-slate-500 hover:text-rose-500 dark:hover:text-rose-400 transition-colors">
                                                <TrashIcon class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center gap-6 text-[10px] text-slate-500 dark:text-slate-400 font-mono mb-3">
                                        <span class="flex items-center gap-1.5">
                                            <CalendarIcon class="h-3 w-3" /> {{ formatDate(task.start_date_plan) }} > {{ formatDate(task.end_date_plan) }}
                                        </span>
                                        <span class="flex items-center gap-1.5 uppercase" :class="{'text-rose-600 dark:text-rose-400': task.priority === 'urgent', 'text-cyan-600 dark:text-cyan-400': task.priority === 'high'}">
                                            <CheckCircleIcon class="h-3 w-3" /> {{ task.priority }}
                                        </span>
                                    </div>

                                    <!-- Assigned Members -->
                                    <div class="flex items-center gap-2 mb-4">
                                         <div class="flex items-center -space-x-2">
                                            <div v-for="member in task.members" :key="member.id" 
                                                class="w-6 h-6 rounded-full bg-indigo-500/20 border border-indigo-500/50 flex items-center justify-center text-[8px] font-bold text-indigo-600 dark:text-indigo-300 ring-2 ring-white dark:ring-[#0a0a16] relative group/member" 
                                                :title="member.name">
                                                {{ member.name.substring(0, 2).toUpperCase() }}
                                            </div>
                                        </div>
                                        <span v-if="!task.members || task.members.length === 0" class="text-[10px] text-slate-400 dark:text-slate-600 italic flex items-center gap-1">
                                            <UserCircleIcon class="h-3 w-3" /> UNASSIGNED
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <div class="h-1.5 flex-1 bg-slate-100 dark:bg-slate-900 rounded-full overflow-hidden">
                                            <div class="h-full transition-all duration-700 bg-cyan-500 shadow-[0_0_10px_rgba(34,211,238,0.3)]" :style="{ width: `${task.progress}%` }"></div>
                                        </div>
                                        <span class="text-[10px] font-black w-8 text-right" :class="task.progress == 100 ? 'text-emerald-600 dark:text-emerald-400' : 'text-cyan-600 dark:text-cyan-400'">{{ Math.round(task.progress) }}%</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Timeline View -->
                        <div v-if="activeTab === 'timeline'">
                            <TimelineChart :tasks="project.tasks" :projectStart="project.start_date" :projectEnd="project.end_date" />
                        </div>

                        <!-- Team View -->
                        <div v-if="activeTab === 'team'" class="space-y-6">
                            <div class="flex justify-between items-center">
                                <h3 class="flex items-center gap-2 text-sm font-bold text-indigo-600 dark:text-indigo-300 tracking-widest uppercase">
                                    <UsersIcon class="h-4 w-4" /> UNIT_CREW
                                </h3>
                                <button @click="showMemberModal = true" class="hud-btn flex items-center gap-2 px-4 py-1.5 bg-indigo-500/10 border border-indigo-500/20 rounded text-[10px] text-indigo-600 dark:text-indigo-400 hover:bg-indigo-500/20 transition-all font-bold tracking-widest">
                                    <PlusIcon class="h-3.5 w-3.5" /> RECRUIT_UNIT
                                </button>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div v-for="member in project.members" :key="member.id" class="p-4 bg-white/80 dark:bg-white/5 border border-slate-200 dark:border-white/5 rounded-xl flex items-center justify-between group shadow-sm dark:shadow-none">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-lg bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-650 dark:text-indigo-400">
                                            <UserCircleIcon class="h-8 w-8" />
                                        </div>
                                        <div>
                                            <p class="text-xs font-black text-slate-900 dark:text-white uppercase">{{ member.name }}</p>
                                            <p class="text-[10px] text-slate-500 uppercase tracking-widest">{{ member.pivot?.role || 'Member' }}</p>
                                            
                                            <!-- Member Progress -->
                                            <div class="mt-2 w-32">
                                                <div class="flex justify-between text-[8px] mb-1 font-mono">
                                                    <span class="text-slate-400 dark:text-slate-500">{{ getMemberTaskCount(member.id) }} TASKS</span>
                                                    <span class="text-cyan-600 dark:text-cyan-400 font-bold">{{ getMemberProgress(member.id) }}%</span>
                                                </div>
                                                <div class="h-1 bg-slate-100 dark:bg-slate-900 rounded-full overflow-hidden">
                                                    <div class="h-full bg-cyan-500 transition-all duration-1000" :style="{ width: `${getMemberProgress(member.id)}%` }"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <button @click="removeMember(member)" class="p-2 opacity-0 group-hover:opacity-100 text-slate-400 hover:text-rose-500 dark:hover:text-rose-400 transition-all">
                                        <XMarkIcon class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modals -->
            
            <!-- Task Modal -->
            <div v-if="showTaskModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
                <div class="w-full max-w-2xl bg-white dark:bg-[#0a0a16] border border-slate-200 dark:border-white/10 rounded-2xl overflow-hidden shadow-2xl relative">
                    <div class="p-6 border-b border-slate-100 dark:border-white/5 flex justify-between items-center bg-slate-50 dark:bg-white/5">
                        <h3 class="text-sm font-black text-cyan-600 dark:text-cyan-400 uppercase tracking-widest">{{ editingTask ? 'UPDATE_PROTOCOL' : 'INITIATE_TASK' }}</h3>
                        <button @click="showTaskModal = false" class="text-slate-400 dark:text-slate-500 hover:text-slate-800 dark:hover:text-white"><XMarkIcon class="h-6 w-6" /></button>
                    </div>
                    <form @submit.prevent="submitTask" class="p-6 space-y-6">
                        <div class="space-y-4">
                            <div>
                                <label class="text-[10px] text-slate-500 uppercase font-bold tracking-wider">TASK_IDENTIFIER</label>
                                <input v-model="taskForm.name" type="text" required class="w-full bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-lg px-4 py-2 text-slate-800 dark:text-cyan-50 focus:border-cyan-500/50 transition-all font-bold mt-1" />
                            </div>
                            <div>
                                <label class="text-[10px] text-slate-500 uppercase font-bold tracking-wider">PARAMETERS</label>
                                <textarea v-model="taskForm.description" rows="3" class="w-full bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-lg px-4 py-2 text-slate-800 dark:text-cyan-50 focus:border-cyan-500/50 transition-all font-bold mt-1 resize-none"></textarea>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-[10px] text-slate-500 uppercase font-bold tracking-wider">T_START_PLAN</label>
                                    <input v-model="taskForm.start_date_plan" type="date" class="w-full bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-lg px-4 py-2 text-slate-800 dark:text-cyan-50 focus:border-cyan-500/50 transition-all font-bold mt-1" />
                                </div>
                                <div>
                                    <label class="text-[10px] text-slate-500 uppercase font-bold tracking-wider">T_END_PLAN</label>
                                    <input v-model="taskForm.end_date_plan" type="date" class="w-full bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-lg px-4 py-2 text-slate-800 dark:text-cyan-50 focus:border-cyan-500/50 transition-all font-bold mt-1" />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-[10px] text-slate-500 uppercase font-bold tracking-wider">PRIORITY_LEVEL</label>
                                    <select v-model="taskForm.priority" class="w-full bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-lg px-4 py-2 text-slate-800 dark:text-cyan-50 focus:border-cyan-500/50 transition-all font-bold mt-1 bg-white dark:bg-[#0a0a16]">
                                        <option value="low" class="text-slate-800 dark:text-cyan-50">LOW</option>
                                        <option value="medium" class="text-slate-800 dark:text-cyan-50">MEDIUM</option>
                                        <option value="high" class="text-slate-800 dark:text-cyan-50">HIGH</option>
                                        <option value="urgent" class="text-slate-800 dark:text-cyan-50">URGENT</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-[10px] text-slate-500 uppercase font-bold tracking-wider">STATUS</label>
                                    <select v-model="taskForm.status" class="w-full bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-lg px-4 py-2 text-slate-800 dark:text-cyan-50 focus:border-cyan-500/50 transition-all font-bold mt-1 bg-white dark:bg-[#0a0a16]">
                                        <option value="todo" class="text-slate-800 dark:text-cyan-50">TODO</option>
                                        <option value="in_progress" class="text-slate-800 dark:text-cyan-50">IN PROGRESS</option>
                                        <option value="completed" class="text-slate-800 dark:text-cyan-50">COMPLETED</option>
                                        <option value="on_hold" class="text-slate-800 dark:text-cyan-50">ON HOLD</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div>
                                <label class="text-[10px] text-slate-500 uppercase font-bold tracking-wider">ASSIGN_OPERATIVES</label>
                                <div class="grid grid-cols-2 gap-2 mt-1 max-h-32 overflow-y-auto">
                                    <label v-for="member in project.members" :key="member.id" class="flex items-center gap-2 p-2 bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded cursor-pointer hover:bg-slate-50 dark:hover:bg-white/10">
                                        <input type="checkbox" :value="member.id" v-model="taskForm.members" class="rounded border-slate-300 dark:border-white/20 bg-white dark:bg-slate-900 text-cyan-500 focus:ring-0" />
                                        <span class="text-xs font-bold text-slate-800 dark:text-white uppercase">{{ member.name }}</span>
                                    </label>
                                </div>
                            </div>

                            <div v-if="editingTask">
                                <label class="text-[10px] text-slate-500 uppercase font-bold tracking-wider">COMPLETION_PERCENTAGE</label>
                                <div class="flex items-center gap-4 mt-1">
                                    <input v-model="taskForm.progress" type="range" min="0" max="100" class="flex-1 accent-cyan-550" />
                                    <span class="text-sm font-black text-cyan-600 dark:text-cyan-400 w-12 text-right">{{ taskForm.progress }}%</span>
                                </div>
                            </div>
                            
                            <!-- Attachments Section -->
                            <div v-if="editingTask" class="pt-4 border-t border-slate-200 dark:border-white/5">
                                <label class="text-[10px] text-slate-500 uppercase font-bold tracking-wider mb-2 block flex justify-between items-center">
                                    <span>DATA_LINKS (ATTACHMENTS)</span>
                                    <span v-if="fileForm.processing" class="text-cyan-600 dark:text-cyan-400 text-[10px] animate-pulse">UPLOADING...</span>
                                </label>
                                
                                <div class="space-y-2 mb-3">
                                    <div v-for="file in editingTask.attachments" :key="file.id" class="flex items-center justify-between p-2 bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded hover:bg-slate-50 dark:hover:bg-white/10 transition-colors group">
                                        <div class="flex items-center gap-2 overflow-hidden">
                                            <PaperClipIcon class="h-4 w-4 text-slate-400 flex-shrink-0" />
                                            <div class="flex flex-col min-w-0">
                                                <a :href="'/storage/' + file.file_path" target="_blank" class="text-xs font-bold text-cyan-600 dark:text-cyan-400 truncate hover:underline hover:text-cyan-500">
                                                    {{ file.file_name }}
                                                </a>
                                                <span class="text-[8px] text-slate-500 uppercase">
                                                    {{ (file.file_size / 1024).toFixed(1) }}KB • BY {{ file.uploader?.name }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <a :href="'/storage/' + file.file_path" download class="p-1 hover:text-cyan-650 dark:hover:text-cyan-400 transition-colors text-slate-500">
                                                <ArrowDownTrayIcon class="h-3.5 w-3.5" />
                                            </a>
                                            <button type="button" @click="deleteAttachment(file)" class="p-1 hover:text-rose-500 dark:hover:text-rose-400 transition-colors text-slate-500">
                                                <XMarkIcon class="h-3.5 w-3.5" />
                                            </button>
                                        </div>
                                    </div>
                                    <div v-if="!editingTask.attachments?.length" class="text-[10px] text-slate-450 dark:text-slate-600 italic">NO_DATA_LINKED</div>
                                </div>

                                <div class="relative">
                                    <label class="flex items-center justify-center gap-2 w-full p-2 border-2 border-dashed border-slate-200 dark:border-white/10 rounded-lg hover:border-cyan-500/30 hover:bg-cyan-500/5 transition-all cursor-pointer text-slate-400 hover:text-cyan-500">
                                        <PaperClipIcon class="h-4 w-4" />
                                        <span class="text-xs font-bold uppercase tracking-wider">UPLOAD_DATA</span>
                                        <input type="file" class="hidden" @change="handleFileUpload" />
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="pt-4 border-t border-slate-200 dark:border-white/5 flex justify-end gap-4">
                            <button type="button" @click="showTaskModal = false" class="px-4 py-2 text-slate-500 hover:text-slate-800 dark:hover:text-white uppercase text-xs font-bold tracking-wider">CANCEL</button>
                            <button type="submit" class="px-6 py-2 bg-cyan-500/10 dark:bg-cyan-600/20 border border-cyan-500/30 dark:border-cyan-500/50 rounded-lg text-cyan-650 dark:text-cyan-400 hover:bg-cyan-500/20 dark:hover:bg-cyan-500/30 transition-all uppercase text-xs font-bold tracking-widest shadow-sm dark:shadow-[0_0_10px_rgba(34,211,238,0.2)]">EXECUTE</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Member Modal -->
            <div v-if="showMemberModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
                <div class="w-full max-w-md bg-white dark:bg-[#0a0a16] border border-slate-200 dark:border-white/10 rounded-2xl overflow-hidden shadow-2xl relative">
                    <div class="p-6 border-b border-slate-100 dark:border-white/5 flex justify-between items-center bg-slate-50 dark:bg-white/5">
                        <h3 class="text-sm font-black text-indigo-650 dark:text-indigo-400 uppercase tracking-widest">RECRUIT_UNIT</h3>
                        <button @click="showMemberModal = false" class="text-slate-400 dark:text-slate-500 hover:text-slate-800 dark:hover:text-white"><XMarkIcon class="h-6 w-6" /></button>
                    </div>
                    <form @submit.prevent="submitMember" class="p-6 space-y-6">
                         <div class="space-y-4">
                            <div>
                                <label class="text-[10px] text-slate-500 uppercase font-bold tracking-wider">OPERATIVE</label>
                                <select v-model="memberForm.user_id" required class="w-full bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-lg px-4 py-2 text-slate-800 dark:text-cyan-50 focus:border-cyan-500/50 transition-all font-bold mt-1 bg-white dark:bg-[#0a0a16]">
                                    <option value="" disabled class="text-slate-850 dark:text-cyan-50">SELECT PERSONNEL...</option>
                                    <option v-for="user in users" :key="user.id" :value="user.id" class="text-slate-850 dark:text-cyan-50">{{ user.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-[10px] text-slate-500 uppercase font-bold tracking-wider">DESIGNATION</label>
                                <input v-model="memberForm.role" type="text" placeholder="e.g. Lead Developer" class="w-full bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-lg px-4 py-2 text-slate-800 dark:text-cyan-50 focus:border-cyan-500/50 transition-all font-bold mt-1" />
                            </div>
                        </div>
                        <div class="pt-4 border-t border-slate-200 dark:border-white/5 flex justify-end gap-4">
                            <button type="button" @click="showMemberModal = false" class="px-4 py-2 text-slate-500 hover:text-slate-800 dark:hover:text-white uppercase text-xs font-bold tracking-wider">CANCEL</button>
                            <button type="submit" class="px-6 py-2 bg-indigo-500/10 dark:bg-indigo-600/20 border border-indigo-500/30 dark:border-indigo-500/50 rounded-lg text-indigo-650 dark:text-indigo-400 hover:bg-indigo-500/20 dark:hover:bg-indigo-500/30 transition-all uppercase text-xs font-bold tracking-widest shadow-sm dark:shadow-[0_0_10px_rgba(99,102,241,0.2)]">ASSIGN</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Official Print Layout (Hidden on screen) -->
        <div class="hidden print:block bg-white text-black p-10 font-sans min-h-screen">
            <div class="border-b-4 border-black pb-6 mb-8 flex justify-between items-end">
                <div class="flex items-center gap-6">
                    <img :src="$page.props.company?.logo || '/images/usics.png'" class="h-16 w-16 object-contain" />
                    <div>
                        <h1 class="text-3xl font-black uppercase tracking-tighter mb-1">Project Implementation Blueprint</h1>
                        <p class="text-xs font-bold uppercase tracking-widest text-slate-600">{{ $page.props.company?.name || 'USICS CORE ERP' }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-600">ID: USICS-PRJ-{{ project.id.toString().padStart(4, '0') }}</p>
                    <p class="text-[8px] uppercase font-bold tracking-widest text-slate-500">Official Strategic Document • {{ formatDateTime(new Date()) }}</p>
                </div>
            </div>

            <!-- Project Details Grid -->
            <div class="grid grid-cols-2 gap-8 mb-10">
                <div class="space-y-4">
                    <div class="border-l-4 border-black pl-4">
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Project Name</p>
                        <p class="text-lg font-black uppercase">{{ project.name }}</p>
                    </div>
                    <div class="border-l-4 border-black pl-4">
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Objective</p>
                        <p class="text-sm font-medium">{{ project.description }}</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-slate-100 p-4 rounded-lg">
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Mission Commander</p>
                        <p class="text-sm font-black">{{ project.manager?.name || 'ADMINISTRATOR' }}</p>
                    </div>
                    <div class="bg-slate-100 p-4 rounded-lg">
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Operational Status</p>
                        <p class="text-sm font-black uppercase">{{ project.status }}</p>
                    </div>
                    <div class="bg-slate-100 p-4 rounded-lg">
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Initial Deployment</p>
                        <p class="text-sm font-black">{{ formatDate(project.start_date) }}</p>
                    </div>
                    <div class="bg-slate-100 p-4 rounded-lg">
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Final Milestone</p>
                        <p class="text-sm font-black">{{ formatDate(project.end_date) }}</p>
                    </div>
                </div>
            </div>

            <!-- Task manifest -->
            <div class="mb-10">
                <h3 class="text-sm font-black uppercase tracking-[0.2em] border-b-2 border-black pb-2 mb-4">Task Manifest & Activity Plan</h3>
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-black">
                            <th class="py-3 text-[10px] font-black uppercase">Activity / Milestone</th>
                            <th class="py-3 text-[10px] font-black uppercase">Plan Window</th>
                            <th class="py-3 text-[10px] font-black uppercase">Progress</th>
                            <th class="py-3 text-[10px] font-black uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <template v-for="task in project.tasks" :key="task.id">
                            <!-- Phase Header (if no parent) -->
                            <tr v-if="!task.parent_id" class="bg-slate-50 font-bold">
                                <td class="py-3 text-sm uppercase">{{ task.name }}</td>
                                <td class="py-3 text-[10px]">{{ formatDate(task.start_date_plan) }} - {{ formatDate(task.end_date_plan) }}</td>
                                <td class="py-3 text-xs font-black">{{ Math.round(task.progress) }}%</td>
                                <td class="py-3 text-[10px] uppercase font-bold text-slate-600">{{ task.status }}</td>
                            </tr>
                            <!-- Sub-tasks -->
                            <tr v-for="sub in project.tasks.filter(t => t.parent_id === task.id)" :key="sub.id">
                                <td class="py-3 pl-8 text-xs">• {{ sub.name }}</td>
                                <td class="py-3 text-[10px]">{{ formatDate(sub.start_date_plan) }} - {{ formatDate(sub.end_date_plan) }}</td>
                                <td class="py-3 text-[10px] font-bold">{{ Math.round(sub.progress) }}%</td>
                                <td class="py-3 text-[10px] uppercase text-slate-500">{{ sub.status }}</td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Timeline Visualization -->
            <div class="mb-10 page-break-before">
                <h3 class="text-sm font-black uppercase tracking-[0.2em] border-b-2 border-black pb-2 mb-6">Visual Timeline Manifest</h3>
                <div class="relative pt-10 border border-slate-200 rounded-xl p-6">
                    <!-- Timeline Header -->
                    <div class="absolute top-0 left-0 right-0 h-10 border-b border-slate-200 grid grid-cols-5 text-[8px] font-bold items-center px-4 bg-slate-50">
                        <div class="text-center border-r border-slate-100 uppercase">Preparation</div>
                        <div class="text-center border-r border-slate-100 uppercase">Blueprinting</div>
                        <div class="text-center border-r border-slate-100 uppercase">Realization</div>
                        <div class="text-center border-r border-slate-100 uppercase">Final Prep</div>
                        <div class="text-center uppercase">Go-Live</div>
                    </div>
                    
                    <div class="space-y-4 pt-4">
                        <div v-for="task in project.tasks.filter(t => !t.parent_id)" :key="'print_'+task.id" class="flex items-center gap-4">
                            <div class="w-48 text-[10px] font-bold uppercase truncate">{{ task.name }}</div>
                            <div class="flex-1 h-8 relative bg-slate-100 rounded border border-slate-200 overflow-hidden">
                                <!-- Progress Bar -->
                                <div class="absolute inset-y-0 left-0 bg-slate-800" :style="{ width: `${task.progress}%` }"></div>
                                <div class="absolute inset-0 flex items-center justify-center text-[8px] font-black mix-blend-difference text-white">
                                    {{ Math.round(task.progress) }}% COMPLETE
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <p class="text-[8px] italic text-slate-500 mt-4 text-center">*Bars represent development intensity and progress status relative to the 01 March milestone.</p>
            </div>

            <!-- Signature block -->
            <div class="mt-20 flex justify-end gap-20">
                <div class="text-center w-48">
                    <div class="h-px bg-black mb-2"></div>
                    <p class="text-[10px] font-bold uppercase tracking-widest">Authorized By</p>
                    <p class="text-[8px] text-slate-500 mt-1 uppercase">SPINDO Strategic Planning</p>
                </div>
                <div class="text-center w-48">
                    <div class="h-px bg-black mb-2"></div>
                    <p class="text-[10px] font-bold uppercase tracking-widest">Project Manager</p>
                    <p class="text-[8px] text-slate-500 mt-1 uppercase">{{ project.manager?.name || 'Mission Commander' }}</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
@media print {
    @page {
        size: landscape;
        margin: 1cm !important;
    }
    html, body {
        background: white !important;
        background-color: white !important;
        color: black !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    /* Absolute suppression of screen-only elements */
    .print\:hidden, nav, aside, header, footer, button, .hud-btn, .fixed, .absolute.inset-0, .perspective-grid {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        height: 0 !important;
        overflow: hidden !important;
    }
    .print\:block {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    /* Reset layout for print */
    main {
        padding: 0 !important;
        margin: 0 !important;
        width: 100% !important;
    }
    .min-h-screen {
        min-height: auto !important;
        background: transparent !important;
    }
    /* Branding and Typography */
    .text-black { color: black !important; }
    .bg-white { background-color: white !important; }
    .bg-slate-50 { background-color: #f8fafc !important; }
    .bg-slate-100 { background-color: #f1f5f9 !important; }
    .page-break-before { page-break-before: always; }
    
    /* Force background colors */
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
}
</style>

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

.hud-panel {
    /* box-shadow styling left empty or handled dynamically, standard shadow-sm is set in template */
}

.glow-text {
    text-shadow: 0 0 10px currentColor;
}
</style>
