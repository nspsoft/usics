<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    PlusIcon,
    EyeIcon,
    PencilSquareIcon,
    TrashIcon,
    CalendarDaysIcon,
    ClockIcon,
    MapPinIcon,
    UserIcon,
    MagnifyingGlassIcon,
    VideoCameraIcon,
    CheckCircleIcon,
    QueueListIcon,
    UserGroupIcon,
} from '@heroicons/vue/24/outline';
import Pagination from '@/Components/Pagination.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    meetings: Object,
    filters: Object,
    kpis: Object,
});

const search = ref(props.filters.search || '');
const type = ref(props.filters.type || '');
const status = ref(props.filters.status || '');

const applyFilters = debounce(() => {
    router.get('/meeting-command', {
        search: search.value,
        type: type.value,
        status: status.value,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

const time = ref('');
const updateTime = () => {
    const now = new Date();
    time.value = now.toLocaleTimeString('id-ID', { hour12: false, hour: '2-digit', minute: '2-digit', second: '2-digit' });
};
let clockTimer;
onMounted(() => {
    updateTime();
    clockTimer = setInterval(updateTime, 1000);
});
onUnmounted(() => clearInterval(clockTimer));

const getStatusBadge = (st) => {
    if (!st) return 'text-slate-400 border-slate-500/30 bg-slate-500/5';
    const clean = st.toLowerCase();
    if (clean === 'locked') return 'text-rose-400 border-rose-400/30 bg-rose-500/5';
    if (clean === 'published') return 'text-emerald-400 border-emerald-400/30 bg-emerald-500/5';
    return 'text-slate-400 border-slate-400/30 bg-slate-500/5';
};

const getTypeBadge = (tp) => {
    if (!tp) return 'text-slate-400 border-slate-500/30 bg-slate-500/5';
    const clean = tp.toLowerCase();
    if (clean === 'project') return 'text-purple-400 border-purple-400/30 bg-purple-500/5';
    if (clean === 'external') return 'text-sky-400 border-sky-400/30 bg-sky-500/5';
    return 'text-teal-400 border-teal-400/30 bg-teal-500/5';
};

const formatDate = (date) => {
    if (!date) return '-';
    try {
        return new Date(date).toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        });
    } catch (e) {
        return date;
    }
};

const formatTime = (timeStr) => {
    if (!timeStr) return '-';
    return timeStr.substring(0, 5);
};

const deleteMeeting = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus dokumen notulen rapat ini?')) {
        router.delete(`/meeting-command/${id}`);
    }
};
</script>

<template>
    <Head title="Meeting Command Hub" />
    
    <AppLayout title="Meeting Command Hub" :render-header="false">
        <div class="min-h-screen bg-[#030108] relative overflow-hidden font-mono text-slate-50 selection:bg-purple-500/30">
            <!-- Neon Ambient Grid Background -->
            <div class="fixed inset-0 z-0 pointer-events-none">
                <div class="absolute inset-0 bg-gradient-to-b from-purple-950/15 to-[#030108]"></div>
                <div class="perspective-grid absolute inset-0 opacity-15"></div>
                <div class="absolute top-[-10%] right-[10%] w-[600px] h-[600px] bg-purple-600/10 blur-[150px] rounded-full animate-float"></div>
                <div class="absolute bottom-[-10%] left-[10%] w-[500px] h-[500px] bg-fuchsia-600/5 blur-[120px] rounded-full animate-float" style="animation-delay: -4s;"></div>
                <div class="stars"></div>
            </div>

            <div class="relative z-10 p-6 space-y-6">
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-purple-500/20 pb-4 backdrop-blur-sm">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-0.5 text-[9px] bg-purple-950/50 border border-purple-500/30 rounded text-purple-400 tracking-[0.25em]">MOM.COMMAND.SYS</span>
                            <span class="flex items-center gap-1.5 px-2 py-0.5 text-[9px] bg-purple-500/10 border border-purple-500/20 rounded text-purple-400 font-bold tracking-wider animate-pulse">
                                <span class="w-1.5 h-1.5 rounded-full bg-purple-400"></span> MINUTES OF MEETING HUB ONLINE
                            </span>
                        </div>
                        <h1 class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-purple-400 via-white to-fuchsia-400 tracking-widest uppercase glow-text">
                            MEETING COMMAND HUB
                        </h1>
                    </div>
                    
                    <div class="flex items-center gap-6">
                        <div class="text-right hidden md:block">
                            <p class="text-[9px] text-purple-500/70 tracking-[0.2em] mb-1">LOCAL TIME</p>
                            <p class="text-xl font-bold font-mono text-white glow-text">{{ time }}</p>
                        </div>
                    </div>
                </div>

                <!-- KPI Box Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="hud-card group">
                        <div class="hud-content p-6 bg-[#0c0517]/85 border border-purple-500/15 rounded-2xl overflow-hidden flex flex-col justify-between relative shadow-xl shadow-black/50">
                            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                                <VideoCameraIcon class="h-12 w-12 text-purple-400" />
                            </div>
                            <div>
                                <p class="text-[10px] text-slate-400 tracking-[0.2em] uppercase font-bold mb-1">TOTAL MEETINGS</p>
                                <h3 class="text-3xl font-black text-white glow-text tracking-tight mt-1">
                                    {{ kpis?.total_meetings || 0 }} <span class="text-xs text-slate-500 font-normal">SESSIONS</span>
                                </h3>
                            </div>
                            <p class="text-[9px] text-slate-500 mt-4 uppercase">Recorded minutes of meeting logs</p>
                        </div>
                    </div>

                    <div class="hud-card group">
                        <div class="hud-content p-6 bg-[#0c0517]/85 border border-purple-500/15 rounded-2xl overflow-hidden flex flex-col justify-between relative shadow-xl shadow-black/50">
                            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                                <QueueListIcon class="h-12 w-12 text-fuchsia-400" />
                            </div>
                            <div>
                                <p class="text-[10px] text-slate-400 tracking-[0.2em] uppercase font-bold mb-1">PENDING ACTIONS</p>
                                <h3 class="text-3xl font-black text-white glow-text tracking-tight mt-1" :class="kpis?.pending_actions > 0 ? 'text-fuchsia-400 animate-pulse' : 'text-white'">
                                    {{ kpis?.pending_actions || 0 }} <span class="text-xs text-slate-500 font-normal">TASKS</span>
                                </h3>
                            </div>
                            <p class="text-[9px] text-slate-500 mt-4 uppercase">Awaiting completion from meeting decisions</p>
                        </div>
                    </div>

                    <div class="hud-card group">
                        <div class="hud-content p-6 bg-[#0c0517]/85 border border-purple-500/15 rounded-2xl overflow-hidden flex flex-col justify-between relative shadow-xl shadow-black/50">
                            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                                <UserGroupIcon class="h-12 w-12 text-teal-400" />
                            </div>
                            <div>
                                <p class="text-[10px] text-slate-400 tracking-[0.2em] uppercase font-bold mb-1">ATTENDANCE RATE</p>
                                <h3 class="text-3xl font-black text-white glow-text tracking-tight mt-1">
                                    {{ kpis?.attendance_rate || 0 }}% <span class="text-xs text-slate-500 font-normal">AVERAGE</span>
                                </h3>
                            </div>
                            <p class="text-[9px] text-slate-500 mt-4 uppercase">Overall attendees present status</p>
                        </div>
                    </div>
                </div>

                <!-- Filters & Search -->
                <div class="flex flex-col lg:flex-row gap-4 justify-between items-center bg-[#0c0517]/70 border border-purple-500/10 p-4 rounded-2xl">
                    <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
                        <div class="relative group w-full sm:w-80">
                            <MagnifyingGlassIcon class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400 group-focus-within:text-purple-500 transition-colors" />
                            <input 
                                v-model="search" 
                                @input="applyFilters"
                                type="text" 
                                placeholder="Cari Rapat atau Notulen..." 
                                class="w-full bg-[#030108] border-0 ring-1 ring-purple-500/20 rounded-xl pl-12 pr-4 py-2.5 text-xs text-slate-100 placeholder:text-slate-500 focus:ring-2 focus:ring-purple-500 transition-all font-mono"
                            >
                        </div>

                        <select 
                            v-model="type" 
                            @change="applyFilters"
                            class="bg-[#030108] border-0 ring-1 ring-purple-500/20 rounded-xl px-4 py-2.5 text-xs text-slate-300 focus:ring-2 focus:ring-purple-500 font-mono"
                        >
                            <option value="">Semua Tipe</option>
                            <option value="internal">Internal</option>
                            <option value="external">External</option>
                            <option value="project">Project</option>
                        </select>

                        <select 
                            v-model="status" 
                            @change="applyFilters"
                            class="bg-[#030108] border-0 ring-1 ring-purple-500/20 rounded-xl px-4 py-2.5 text-xs text-slate-300 focus:ring-2 focus:ring-purple-500 font-mono"
                        >
                            <option value="">Semua Status</option>
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                            <option value="locked">Locked (Arsip)</option>
                        </select>
                    </div>

                    <div class="flex gap-3 w-full lg:w-auto">
                        <Link
                            href="/meeting-command/action-items/board"
                            class="w-full lg:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-[#0c0517] hover:bg-[#160c29] border border-purple-500/20 px-5 py-2.5 text-xs font-bold text-purple-400 transition-all active:scale-95 shadow-sm"
                        >
                            <QueueListIcon class="h-4 w-4 text-purple-455" />
                            Papan Tugas (Kanban/Gantt)
                        </Link>

                        <Link
                            href="/meeting-command/create"
                            class="w-full lg:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-purple-600 hover:bg-purple-500 px-5 py-2.5 text-xs font-bold text-white shadow-lg shadow-purple-500/20 active:scale-95 transition-all"
                        >
                            <PlusIcon class="h-4 w-4" />
                            Buat Rapat Baru
                        </Link>
                    </div>
                </div>

                <!-- Meetings Table -->
                <div class="hud-panel overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-purple-500/10 text-xs">
                            <thead>
                                <tr class="bg-purple-950/20 text-slate-500 font-bold uppercase tracking-wider border-b border-purple-500/15">
                                    <th class="px-6 py-4 text-left">Agenda Rapat</th>
                                    <th class="px-6 py-4 text-left">Waktu & Tempat</th>
                                    <th class="px-6 py-4 text-center">Tipe</th>
                                    <th class="px-6 py-4 text-left">Pimpinan & Notulis</th>
                                    <th class="px-6 py-4 text-center">Statistik</th>
                                    <th class="px-6 py-4 text-center">Status</th>
                                    <th class="px-6 py-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-purple-500/5">
                                <tr v-for="meeting in meetings.data" :key="meeting.id" class="hover:bg-purple-500/[0.01] transition-colors group">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-500/10 text-purple-400 font-bold">
                                                M
                                            </div>
                                            <Link :href="`/meeting-command/${meeting.id}`" class="text-sm font-bold text-slate-100 hover:text-purple-400 transition-colors">
                                                {{ meeting.title }}
                                            </Link>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-slate-300">
                                        <div class="flex items-center gap-1.5">
                                            <CalendarDaysIcon class="h-3.5 w-3.5 text-slate-500" />
                                            <span>{{ formatDate(meeting.meeting_date) }}</span>
                                        </div>
                                        <div class="flex items-center gap-1.5 mt-1 text-[10px] text-slate-500">
                                            <ClockIcon class="h-3 w-3" />
                                            <span>{{ formatTime(meeting.start_time) }} - {{ formatTime(meeting.end_time) }}</span>
                                            <span class="mx-1">•</span>
                                            <MapPinIcon class="h-3 w-3" />
                                            <span class="truncate max-w-[120px]">{{ meeting.location }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[9px] font-black uppercase tracking-wider" :class="getTypeBadge(meeting.type)">
                                            {{ meeting.type }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-slate-300 font-medium">
                                        <div class="flex items-center gap-1.5">
                                            <UserIcon class="h-3.5 w-3.5 text-purple-400" />
                                            <span>PIC: {{ meeting.chairperson?.name }}</span>
                                        </div>
                                        <div class="text-[10px] text-slate-500 mt-1 pl-5">
                                            Notulis: {{ meeting.secretary?.name }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <div class="text-slate-200 font-bold">{{ meeting.attendees_count || 0 }} Peserta</div>
                                        <div class="text-[10px] text-slate-500 mt-0.5">{{ meeting.action_items_count || 0 }} Action Items</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[9px] font-black uppercase tracking-wider" :class="getStatusBadge(meeting.status)">
                                            {{ meeting.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <Link :href="`/meeting-command/${meeting.id}`" class="p-2 text-slate-400 hover:text-purple-400 hover:bg-purple-500/10 rounded-xl transition-colors" title="Buka Rincian">
                                                <EyeIcon class="h-4 w-4" />
                                            </Link>
                                            <Link v-if="meeting.status !== 'locked'" :href="`/meeting-command/${meeting.id}/edit`" class="p-2 text-slate-400 hover:text-blue-400 hover:bg-blue-500/10 rounded-xl transition-colors" title="Ubah Rapat">
                                                <PencilSquareIcon class="h-4 w-4" />
                                            </Link>
                                            <button v-if="meeting.status !== 'locked'" @click="deleteMeeting(meeting.id)" class="p-2 text-slate-400 hover:text-rose-500 hover:bg-rose-500/10 rounded-xl transition-colors" title="Hapus Rapat">
                                                <TrashIcon class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="meetings.data && meetings.data.length === 0">
                                    <td colspan="7" class="px-6 py-16 text-center text-slate-500 italic">
                                        Tidak ada rapat yang tercatat atau cocok dengan pencarian.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="meetings.last_page > 1" class="border-t border-purple-500/10 px-6 py-4 bg-purple-950/5">
                        <Pagination :links="meetings.links" />
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

/* Background Animated Grid */
.perspective-grid {
    background-image: 
        linear-gradient(to right, rgba(168, 85, 247, 0.08) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(168, 85, 247, 0.08) 1px, transparent 1px);
    background-size: 50px 50px;
    transform: perspective(600px) rotateX(60deg) translateY(-100px) scale(2.2);
    animation: grid-move 40s linear infinite;
    transform-origin: top;
}

@keyframes grid-move {
    0% { background-position: 0 0; }
    100% { background-position: 0 50px; }
}

@keyframes float {
    0%, 100% { transform: translate(0, 0); }
    50% { transform: translate(15px, -15px); }
}

.hud-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.hud-card:hover {
    transform: translateY(-4px);
    filter: drop-shadow(0 0 20px rgba(168, 85, 247, 0.15));
}

.hud-panel {
    background: rgba(12, 5, 23, 0.85);
    backdrop-filter: blur(25px);
    border: 1px solid rgba(168, 85, 247, 0.15);
    border-radius: 16px;
    box-shadow: 0 0 40px rgba(0, 0, 0, 0.7);
}

.glow-text {
    text-shadow: 0 0 8px currentColor;
}
</style>
