<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';
import {
    ArrowLeftIcon,
    BriefcaseIcon,
    CalendarIcon,
    ClockIcon,
    FunnelIcon,
    ListBulletIcon,
    UserIcon,
    VideoCameraIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    actionItems: Array,
    users: Array,
    filters: Object,
});

const activeTab = ref('kanban'); // 'kanban' | 'gantt'
const filterPic = ref(props.filters?.pic_id || '');

const handleFilterChange = () => {
    router.get(route('meeting-command.action-items.board'), {
        pic_id: filterPic.value
    }, {
        preserveState: true,
        preserveScroll: true
    });
};

// Kanban columns logic
const todoItems = computed(() => props.actionItems.filter(item => item.status === 'pending'));
const progressItems = computed(() => props.actionItems.filter(item => item.status === 'in_progress'));
const doneItems = computed(() => props.actionItems.filter(item => item.status === 'completed'));

const updateStatus = async (item, newStatus) => {
    try {
        const response = await axios.put(route('meeting-command.action-items.status-update', item.id), {
            status: newStatus
        });
        if (response.data.success) {
            // Reload page to refresh props
            router.reload({ preserveScroll: true });
        }
    } catch (err) {
        console.error('Gagal memperbarui status tugas:', err);
        alert('Gagal memperbarui status tugas.');
    }
};

const getOverdueStatus = (dueDate) => {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const due = new Date(dueDate);
    due.setHours(0, 0, 0, 0);
    return due < today;
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

// Gantt timeline calculations
const earliestDate = computed(() => {
    if (!props.actionItems.length) return new Date();
    const dates = props.actionItems.map(item => new Date(item.meeting?.meeting_date || item.created_at));
    return new Date(Math.min(...dates));
});

const latestDate = computed(() => {
    if (!props.actionItems.length) {
        const d = new Date();
        d.setDate(d.getDate() + 14);
        return d;
    }
    const dates = props.actionItems.map(item => new Date(item.due_date));
    return new Date(Math.max(...dates));
});

const timelineDays = computed(() => {
    const days = [];
    const start = new Date(earliestDate.value);
    // 2 days padding before
    start.setDate(start.getDate() - 2);
    
    const end = new Date(latestDate.value);
    // 2 days padding after
    end.setDate(end.getDate() + 2);

    // Limit to 45 days to avoid layout overflow
    let count = 0;
    while (start <= end && count < 45) {
        days.push(new Date(start));
        start.setDate(start.getDate() + 1);
        count++;
    }
    return days;
});

const getGridColumnRange = (item) => {
    const start = new Date(item.meeting?.meeting_date || item.created_at);
    const end = new Date(item.due_date);
    
    start.setHours(0,0,0,0);
    end.setHours(0,0,0,0);

    let startIndex = timelineDays.value.findIndex(d => d.toDateString() === start.toDateString()) + 1;
    let endIndex = timelineDays.value.findIndex(d => d.toDateString() === end.toDateString()) + 2;

    if (startIndex === 0) startIndex = 1;
    if (endIndex === 1) endIndex = timelineDays.value.length + 1;

    return `${startIndex} / ${endIndex}`;
};

const getStatusColorClass = (status) => {
    if (status === 'completed') return 'bg-emerald-500/20 border-emerald-500/30 text-emerald-450';
    if (status === 'in_progress') return 'bg-amber-500/20 border-amber-500/30 text-amber-450';
    return 'bg-purple-500/20 border-purple-500/30 text-purple-400';
};
</script>

<template>
    <Head title="Action Items Board" />
    
    <AppLayout title="Meeting Command Hub" :render-header="false">
        <div class="min-h-screen bg-[#030108] p-6 font-mono text-slate-50 relative overflow-hidden">
            <!-- Glow background -->
            <div class="fixed inset-0 z-0 pointer-events-none">
                <div class="absolute inset-0 bg-gradient-to-b from-purple-950/10 to-[#030108]"></div>
                <div class="absolute top-[-10%] left-[20%] w-[500px] h-[500px] bg-purple-500/5 blur-[120px] rounded-full"></div>
            </div>

            <div class="relative z-10 max-w-7xl mx-auto space-y-6">
                <!-- Header navigation -->
                <div class="flex items-center justify-between border-b border-purple-500/20 pb-4">
                    <div>
                        <Link href="/meeting-command" class="inline-flex items-center gap-2 text-xs font-bold text-purple-400 hover:text-purple-300 transition-colors uppercase mb-1">
                            <ArrowLeftIcon class="h-4 w-4" /> KEMBALI KE DASHBOARD
                        </Link>
                        <h1 class="text-2xl font-black tracking-widest text-white glow-text-purple uppercase">
                            ACTION ITEMS BOARD
                        </h1>
                        <p class="text-[10px] text-slate-500 tracking-wider uppercase">Papan pantau dan pelacakan seluruh tugas hasil rapat</p>
                    </div>

                    <!-- Filter Area -->
                    <div class="flex items-center gap-2 bg-[#0c0517] border border-purple-500/15 rounded-xl px-4 py-2.5">
                        <FunnelIcon class="h-4 w-4 text-purple-400" />
                        <label class="text-[10px] font-bold text-slate-400 uppercase">PIC:</label>
                        <select 
                            v-model="filterPic" 
                            @change="handleFilterChange"
                            class="bg-transparent border-0 ring-0 focus:ring-0 text-xs text-white py-0 pl-1 pr-6 font-mono cursor-pointer"
                        >
                            <option value="">Semua PIC</option>
                            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>
                    </div>
                </div>

                <!-- Tab selectors -->
                <div class="flex gap-2">
                    <button 
                        @click="activeTab = 'kanban'" 
                        class="px-5 py-2.5 rounded-xl border text-xs font-black uppercase tracking-wider transition-all flex items-center gap-2"
                        :class="activeTab === 'kanban' 
                            ? 'bg-purple-950/40 text-purple-400 border-purple-500/30 glow-text-purple shadow-lg shadow-purple-500/5' 
                            : 'bg-[#0c0517]/80 text-slate-500 border-purple-500/10 hover:text-slate-300'"
                    >
                        <ListBulletIcon class="h-4 w-4" /> PAPAN KANBAN
                    </button>
                    <button 
                        @click="activeTab = 'gantt'" 
                        class="px-5 py-2.5 rounded-xl border text-xs font-black uppercase tracking-wider transition-all flex items-center gap-2"
                        :class="activeTab === 'gantt' 
                            ? 'bg-purple-950/40 text-purple-400 border-purple-500/30 glow-text-purple shadow-lg shadow-purple-500/5' 
                            : 'bg-[#0c0517]/80 text-slate-500 border-purple-500/10 hover:text-slate-300'"
                    >
                        <BriefcaseIcon class="h-4 w-4" /> DIAGRAM GANTT
                    </button>
                </div>

                <!-- TABS CONTENT: KANBAN -->
                <div v-if="activeTab === 'kanban'" class="grid grid-cols-1 md:grid-cols-3 gap-6 items-start">
                    <!-- Column 1: TODO -->
                    <div class="bg-[#0c0517]/80 border border-purple-500/15 rounded-2xl p-5 shadow-xl flex flex-col space-y-4">
                        <div class="flex justify-between items-center border-b border-slate-500/10 pb-2">
                            <h3 class="text-xs font-black uppercase tracking-widest text-slate-350 flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full bg-slate-500"></span> TODO (PENDING)
                            </h3>
                            <span class="px-2 py-0.5 rounded-md bg-slate-500/10 text-[10px] font-bold text-slate-400 border border-slate-500/15">
                                {{ todoItems.length }}
                            </span>
                        </div>

                        <div class="space-y-4 max-h-[70vh] overflow-y-auto pr-1">
                            <div v-for="item in todoItems" :key="item.id" class="bg-[#030108]/90 border border-purple-500/5 hover:border-purple-500/15 p-4 rounded-xl space-y-3 transition-colors relative">
                                <div class="space-y-1">
                                    <p class="text-xs font-bold text-slate-200 leading-relaxed">{{ item.description }}</p>
                                    <Link :href="`/meeting-command/${item.meeting?.id}`" class="text-[9px] font-black text-purple-400 hover:underline uppercase block tracking-wider">
                                        Rapat: {{ item.meeting?.title }}
                                    </Link>
                                </div>
                                <div class="flex justify-between items-center border-t border-purple-500/5 pt-2 text-[9px] text-slate-500 font-bold">
                                    <span class="flex items-center gap-1"><UserIcon class="h-3 w-3" /> {{ item.pic?.name }}</span>
                                    <span class="flex items-center gap-1" :class="{ 'text-rose-500': getOverdueStatus(item.due_date) }">
                                        <CalendarIcon class="h-3 w-3" /> {{ formatDate(item.due_date) }}
                                        <span v-if="getOverdueStatus(item.due_date)" class="text-[7px] border border-rose-500/30 px-1 rounded bg-rose-500/10 animate-pulse">LATE</span>
                                    </span>
                                </div>
                                <div class="pt-2 border-t border-purple-500/5 flex justify-end">
                                    <select 
                                        @change="(e) => updateStatus(item, e.target.value)"
                                        class="bg-[#0c0517] border border-purple-500/20 rounded-md px-2 py-1 text-[9px] text-slate-400 font-mono focus:ring-1 focus:ring-purple-500 cursor-pointer"
                                    >
                                        <option value="pending" selected>Pindahkan ke...</option>
                                        <option value="in_progress">WIP (In Progress)</option>
                                        <option value="completed">Done (Selesai)</option>
                                    </select>
                                </div>
                            </div>
                            <div v-if="todoItems.length === 0" class="text-center py-12 text-[10px] text-slate-600 italic">Tidak ada tugas pending.</div>
                        </div>
                    </div>

                    <!-- Column 2: IN PROGRESS -->
                    <div class="bg-[#0c0517]/80 border border-purple-500/15 rounded-2xl p-5 shadow-xl flex flex-col space-y-4">
                        <div class="flex justify-between items-center border-b border-amber-500/15 pb-2">
                            <h3 class="text-xs font-black uppercase tracking-widest text-amber-450 flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full bg-amber-500"></span> WIP (PROGRESS)
                            </h3>
                            <span class="px-2 py-0.5 rounded-md bg-amber-500/10 text-[10px] font-bold text-amber-400 border border-amber-500/15">
                                {{ progressItems.length }}
                            </span>
                        </div>

                        <div class="space-y-4 max-h-[70vh] overflow-y-auto pr-1">
                            <div v-for="item in progressItems" :key="item.id" class="bg-[#030108]/90 border border-purple-500/5 hover:border-purple-500/15 p-4 rounded-xl space-y-3 transition-colors">
                                <div class="space-y-1">
                                    <p class="text-xs font-bold text-slate-200 leading-relaxed">{{ item.description }}</p>
                                    <Link :href="`/meeting-command/${item.meeting?.id}`" class="text-[9px] font-black text-purple-400 hover:underline uppercase block tracking-wider">
                                        Rapat: {{ item.meeting?.title }}
                                    </Link>
                                </div>
                                <div class="flex justify-between items-center border-t border-purple-500/5 pt-2 text-[9px] text-slate-500 font-bold">
                                    <span class="flex items-center gap-1"><UserIcon class="h-3 w-3" /> {{ item.pic?.name }}</span>
                                    <span class="flex items-center gap-1" :class="{ 'text-rose-500': getOverdueStatus(item.due_date) }">
                                        <CalendarIcon class="h-3 w-3" /> {{ formatDate(item.due_date) }}
                                        <span v-if="getOverdueStatus(item.due_date)" class="text-[7px] border border-rose-500/30 px-1 rounded bg-rose-500/10 animate-pulse">LATE</span>
                                    </span>
                                </div>
                                <div class="pt-2 border-t border-purple-500/5 flex justify-end">
                                    <select 
                                        @change="(e) => updateStatus(item, e.target.value)"
                                        class="bg-[#0c0517] border border-purple-500/20 rounded-md px-2 py-1 text-[9px] text-slate-400 font-mono focus:ring-1 focus:ring-purple-500 cursor-pointer"
                                    >
                                        <option value="in_progress" selected>Pindahkan ke...</option>
                                        <option value="pending">Todo (Pending)</option>
                                        <option value="completed">Done (Selesai)</option>
                                    </select>
                                </div>
                            </div>
                            <div v-if="progressItems.length === 0" class="text-center py-12 text-[10px] text-slate-600 italic">Tidak ada tugas dikerjakan.</div>
                        </div>
                    </div>

                    <!-- Column 3: DONE -->
                    <div class="bg-[#0c0517]/80 border border-purple-500/15 rounded-2xl p-5 shadow-xl flex flex-col space-y-4">
                        <div class="flex justify-between items-center border-b border-emerald-500/15 pb-2">
                            <h3 class="text-xs font-black uppercase tracking-widest text-emerald-450 flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full bg-emerald-500"></span> DONE (COMPLETED)
                            </h3>
                            <span class="px-2 py-0.5 rounded-md bg-emerald-500/10 text-[10px] font-bold text-emerald-400 border border-emerald-500/15">
                                {{ doneItems.length }}
                            </span>
                        </div>

                        <div class="space-y-4 max-h-[70vh] overflow-y-auto pr-1">
                            <div v-for="item in doneItems" :key="item.id" class="bg-[#030108]/90 border border-purple-500/5 hover:border-purple-500/15 p-4 rounded-xl space-y-3 transition-colors opacity-75">
                                <div class="space-y-1">
                                    <p class="text-xs font-bold text-slate-400 line-through leading-relaxed">{{ item.description }}</p>
                                    <Link :href="`/meeting-command/${item.meeting?.id}`" class="text-[9px] font-black text-purple-400/70 hover:underline uppercase block tracking-wider">
                                        Rapat: {{ item.meeting?.title }}
                                    </Link>
                                </div>
                                <div class="flex justify-between items-center border-t border-purple-500/5 pt-2 text-[9px] text-slate-550 font-bold">
                                    <span class="flex items-center gap-1"><UserIcon class="h-3 w-3" /> {{ item.pic?.name }}</span>
                                    <span class="flex items-center gap-1"><CalendarIcon class="h-3 w-3" /> {{ formatDate(item.due_date) }}</span>
                                </div>
                                <div class="pt-2 border-t border-purple-500/5 flex justify-end">
                                    <select 
                                        @change="(e) => updateStatus(item, e.target.value)"
                                        class="bg-[#0c0517] border border-purple-500/20 rounded-md px-2 py-1 text-[9px] text-slate-405 font-mono focus:ring-1 focus:ring-purple-500 cursor-pointer"
                                    >
                                        <option value="completed" selected>Pindahkan ke...</option>
                                        <option value="pending">Todo (Pending)</option>
                                        <option value="in_progress">WIP (In Progress)</option>
                                    </select>
                                </div>
                            </div>
                            <div v-if="doneItems.length === 0" class="text-center py-12 text-[10px] text-slate-600 italic">Belum ada tugas selesai.</div>
                        </div>
                    </div>
                </div>

                <!-- TABS CONTENT: GANTT CHART -->
                <div v-if="activeTab === 'gantt'" class="bg-[#0c0517]/85 border border-purple-500/15 rounded-2xl p-6 shadow-xl space-y-4 overflow-x-auto">
                    <div class="border-b border-purple-500/10 pb-2 mb-2 flex justify-between items-center">
                        <h3 class="text-xs font-black uppercase tracking-widest text-purple-400 flex items-center gap-2">
                            <ClockIcon class="h-4 w-4" /> TIMELINE & DURASI ACTION ITEMS (GANTT CHART)
                        </h3>
                        <div class="flex gap-4 text-[9px] font-bold text-slate-400 uppercase tracking-widest">
                            <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 bg-purple-500 rounded"></span> Todo</span>
                            <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 bg-amber-500 rounded"></span> In Progress</span>
                            <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 bg-emerald-500 rounded"></span> Completed</span>
                        </div>
                    </div>

                    <div v-if="actionItems.length === 0" class="text-center py-20 text-slate-500 italic text-xs">
                        Tidak ada data tugas yang dapat divisualisasikan pada Gantt Chart.
                    </div>

                    <div v-else class="min-w-[900px] space-y-1">
                        <!-- Timeline Header Grid -->
                        <div class="grid items-center" :style="{ gridTemplateColumns: `250px repeat(${timelineDays.length}, 1fr)` }">
                            <div class="text-[10px] font-black text-slate-500 uppercase tracking-wider pr-4">Deskripsi Tugas</div>
                            <div 
                                v-for="d in timelineDays" 
                                :key="d.toISOString()" 
                                class="text-center border-l border-purple-500/5 pb-2 text-[8px] font-bold"
                                :class="d.toDateString() === new Date().toDateString() ? 'text-cyan-400 glow-text-purple border-cyan-500/40 bg-cyan-500/5 rounded-t-lg' : 'text-slate-500'"
                            >
                                <div>{{ d.getDate() }}</div>
                                <div class="text-[6px] opacity-75 mt-0.5">{{ d.toLocaleDateString('id-ID', { weekday: 'narrow' }) }}</div>
                            </div>
                        </div>

                        <!-- Timeline Rows Grid -->
                        <div class="divide-y divide-purple-500/5">
                            <div 
                                v-for="item in actionItems" 
                                :key="item.id" 
                                class="grid items-center py-2 border-l border-r border-purple-500/5"
                                :style="{ gridTemplateColumns: `250px repeat(${timelineDays.length}, 1fr)` }"
                            >
                                <!-- Task Name & Info -->
                                <div class="pr-4 space-y-0.5 max-w-[240px]">
                                    <div class="text-[10px] font-bold text-slate-200 truncate hover:text-purple-300 cursor-help" :title="item.description">
                                        {{ item.description }}
                                    </div>
                                    <div class="flex items-center gap-1.5 text-[7px] text-slate-500 font-bold uppercase tracking-wider">
                                        <span class="truncate max-w-[100px] text-purple-400">PIC: {{ item.pic?.name }}</span>
                                        <span>•</span>
                                        <span>Deadline: {{ formatDate(item.due_date) }}</span>
                                    </div>
                                </div>

                                <!-- Timeline Spanning Area -->
                                <div class="col-span-full grid" :style="{ gridTemplateColumns: `repeat(${timelineDays.length}, 1fr)` }">
                                    <div 
                                        class="h-5 rounded-lg border text-[8px] font-black uppercase flex items-center px-2 tracking-widest text-slate-100 shadow-md relative group truncate"
                                        :style="{ gridColumn: getGridColumnRange(item) }"
                                        :class="getStatusColorClass(item.status)"
                                    >
                                        <span class="truncate">{{ item.pic?.name?.split(' ')[0] }} : {{ item.status }}</span>
                                        
                                        <!-- Cyber tooltip on hover -->
                                        <div class="hidden group-hover:block absolute bottom-6 left-0 bg-[#0c0517] border border-purple-500/30 text-[8px] p-2 rounded shadow-2xl z-50 text-white min-w-[200px] leading-relaxed whitespace-normal font-mono normal-case">
                                            <p class="font-bold text-purple-400 uppercase tracking-widest mb-1">Detail Tugas</p>
                                            <p class="text-slate-200 mb-1 font-bold">{{ item.description }}</p>
                                            <p class="text-slate-400">Mulai (Meeting): {{ formatDate(item.meeting?.meeting_date) }}</p>
                                            <p class="text-slate-405">Jatuh Tempo: {{ formatDate(item.due_date) }}</p>
                                            <p class="text-slate-400 uppercase">Status: <span class="font-bold">{{ item.status }}</span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.glow-text-purple {
    text-shadow: 0 0 10px rgba(168, 85, 247, 0.5);
}
.text-emerald-455 {
    color: #34d399;
}
.text-amber-455 {
    color: #fbbf24;
}
</style>
