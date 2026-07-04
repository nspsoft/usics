<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, onMounted, onUnmounted } from 'vue';
import { 
    ExclamationTriangleIcon,
    WrenchScrewdriverIcon,
    ClockIcon,
    CheckCircleIcon,
    PlusIcon,
    SunIcon,
    MoonIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    logs: Array,
    machines: Array,
    spareparts: Array
});

const showReportModal = ref(false);
const showResolveModal = ref(false);
const selectedLog = ref(null);

const reportForm = useForm({
    machine_id: '',
    description: '',
    started_at: new Date().toISOString().slice(0, 16),
});

const resolveForm = useForm({
    status: 'resolved',
    technician_name: '',
    spareparts: [] // {id, qty}
});

const newPart = ref({ id: '', qty: 1 });

const addPartToResolve = () => {
    if (newPart.value.id && newPart.value.qty > 0) {
        resolveForm.spareparts.push({ ...newPart.value });
        newPart.value = { id: '', qty: 1 };
    }
};

const submitReport = () => {
    reportForm.post(route('maintenance.breakdown.store'), {
        onSuccess: () => {
            showReportModal.value = false;
            reportForm.reset();
        }
    });
};

const openResolve = (log) => {
    selectedLog.value = log;
    resolveForm.technician_name = log.technician === '-' ? '' : log.technician;
    showResolveModal.value = true;
};

const submitResolve = () => {
    resolveForm.put(route('maintenance.breakdown.update', selectedLog.value.id), {
        onSuccess: () => {
            showResolveModal.value = false;
            resolveForm.reset();
        }
    });
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
    <Head title="Machine Breakdowns" />

    <AppLayout title="Breakdown Logs" :render-header="false">
        <div class="min-h-screen bg-slate-50 dark:bg-[#050510] relative overflow-hidden font-mono text-slate-800 dark:text-cyan-50 selection:bg-rose-500/30 transition-colors duration-300">
            
            <!-- Dynamic Background -->
            <div class="fixed inset-0 z-0 pointer-events-none">
                <div class="absolute inset-0 bg-gradient-to-br from-rose-500/5 to-slate-100 dark:from-rose-955/20 dark:to-[#050510]"></div>
                 <div class="perspective-grid absolute inset-0 opacity-[0.05] dark:opacity-10"></div>
            </div>

            <div class="relative z-10 p-6 space-y-6 max-w-7xl mx-auto">
                
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-slate-200 dark:border-white/10 pb-4">
                    <div>
                         <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-0.5 text-[10px] bg-rose-500/10 border border-rose-500/20 rounded text-rose-700 dark:text-rose-400 tracking-[0.2em] uppercase animate-pulse">
                                Live Monitoring
                            </span>
                            <span class="px-2 py-0.5 text-[10px] bg-slate-200 dark:bg-white/5 border border-slate-350 dark:border-white/10 rounded text-slate-500 dark:text-slate-400 tracking-[0.2em] uppercase">MAINT.LOGS.V1</span>
                        </div>
                        <h1 class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-rose-600 via-slate-800 to-rose-700 dark:from-rose-500 dark:via-white dark:to-amber-400 tracking-widest uppercase dark:glow-text">
                            BREAKDOWN LOGS
                        </h1>
                    </div>
                    <div class="flex items-center gap-4">
                        <!-- Theme Toggle Button -->
                        <button 
                            @click="toggleTheme"
                            class="p-2.5 rounded-xl border border-slate-200 dark:border-white/10 bg-white/70 dark:bg-white/5 hover:bg-slate-50 dark:hover:bg-white/10 text-slate-700 dark:text-cyan-400 transition-all hover:scale-105 shadow-sm dark:shadow-none cursor-pointer"
                            :title="isLightMode ? 'Switch to Dark Mode' : 'Switch to Light Mode'"
                        >
                            <SunIcon v-if="!isLightMode" class="h-5 w-5 text-amber-500" />
                            <MoonIcon v-else class="h-5 w-5 text-indigo-600" />
                        </button>

                        <button 
                            @click="showReportModal = true"
                            class="px-4 py-2 bg-gradient-to-r from-rose-650 to-rose-555 hover:from-rose-600 hover:to-rose-500 dark:from-rose-600 dark:to-rose-500 dark:hover:from-rose-500 dark:hover:to-rose-400 text-white font-bold rounded-lg shadow-sm dark:shadow-[0_0_15px_rgba(244,63,94,0.3)] transition-all flex items-center gap-2 text-sm animate-pulse cursor-pointer border-0"
                        >
                            <ExclamationTriangleIcon class="h-4 w-4" /> REPORT BREAKDOWN
                        </button>
                    </div>
                </div>

                <!-- Active Issues (Pinned at Top) -->
                <div v-if="logs.some(l => l.status === 'Open' || l.status === 'In_progress')" class="space-y-4">
                    <h3 class="text-sm font-bold text-rose-600 dark:text-rose-400 uppercase tracking-widest flex items-center gap-2">
                        <ExclamationTriangleIcon class="h-4 w-4" /> Active Incidents
                    </h3>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div v-for="log in logs.filter(l => l.status === 'Open' || l.status === 'In_progress')" :key="log.id" 
                            class="hud-panel p-5 border-l-4 border-l-rose-550 bg-rose-500/[0.04] dark:bg-rose-500/5 relative overflow-hidden group border border-slate-200 dark:border-rose-500/25 rounded-xl shadow-sm dark:shadow-none"
                        >
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h4 class="text-xl font-bold text-slate-900 dark:text-white">{{ log.machine }}</h4>
                                    <p class="text-xs text-rose-600 dark:text-rose-400 font-bold uppercase tracking-wider">{{ log.status }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-[10px] text-slate-500 uppercase">Started</p>
                                    <p class="text-xs text-slate-700 dark:text-slate-300 font-mono">{{ log.started_at }}</p>
                                </div>
                            </div>
                            <p class="text-sm text-slate-800 dark:text-slate-300 mb-6 font-bold bg-white/70 dark:bg-black/20 p-2 rounded border border-slate-200 dark:border-white/5 shadow-sm dark:shadow-none">
                                "{{ log.description }}"
                            </p>
                            
                            <div class="flex items-center justify-between mt-auto">
                                <div class="flex items-center gap-2 text-xs text-amber-700 dark:text-amber-500 font-bold">
                                    <ClockIcon class="h-4 w-4" />
                                    <span>Duration: {{ log.duration }}</span>
                                </div>
                                <button 
                                    @click="openResolve(log)"
                                    class="px-3 py-1.5 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400 border border-emerald-250 dark:border-emerald-500/50 rounded-lg text-xs uppercase tracking-wider transition-colors cursor-pointer font-bold"
                                >
                                    Update Status
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Historical Logs -->
                <div class="space-y-4 pt-6 md:col-span-2">
                     <h3 class="text-sm font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest flex items-center gap-2">
                        <WrenchScrewdriverIcon class="h-4 w-4" /> Repair History
                    </h3>
                    <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-white/10 shadow-sm dark:shadow-[0_0_20px_rgba(0,0,0,0.5)]">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-100 dark:bg-white/5 text-[10px] uppercase tracking-wider text-slate-500 dark:text-slate-400 font-bold">
                                <tr>
                                    <th class="p-4">Date</th>
                                    <th class="p-4">Machine</th>
                                    <th class="p-4">Issue</th>
                                    <th class="p-4">Technician</th>
                                    <th class="p-4">Parts Used</th>
                                    <th class="p-4 text-center">Duration</th>
                                    <th class="p-4 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-white/5 bg-white/30 dark:bg-slate-900/50 text-slate-700 dark:text-slate-300">
                                <tr v-for="log in logs.filter(l => l.status === 'Resolved' || l.status === 'Cancelled')" :key="log.id" class="hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                                    <td class="p-4 font-mono text-slate-500 dark:text-slate-400">{{ log.started_at }}</td>
                                    <td class="p-4 font-bold text-slate-800 dark:text-white uppercase">{{ log.machine_code }}</td>
                                    <td class="p-4 text-slate-700 dark:text-slate-300">{{ log.description }}</td>
                                    <td class="p-4 text-slate-600 dark:text-slate-400">{{ log.technician }}</td>
                                    <td class="p-4 text-xs text-slate-500">{{ log.parts_used || '-' }}</td>
                                    <td class="p-4 text-center font-mono text-cyan-600 dark:text-cyan-400 font-bold">{{ log.duration }}</td>
                                    <td class="p-4 text-center">
                                        <span class="px-2 py-1 rounded text-[10px] font-bold border" 
                                            :class="isLightMode 
                                                ? {
                                                    'bg-emerald-50 border-emerald-200 text-emerald-700': log.status.toLowerCase() === 'resolved',
                                                    'bg-slate-100 border-slate-200 text-slate-600': log.status.toLowerCase() === 'cancelled'
                                                  }[log.status.toLowerCase()] || log.status_color
                                                : log.status_color">
                                            {{ log.status }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Report Breakdown Modal -->
            <div v-if="showReportModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
                <div class="bg-white dark:bg-[#0f172a] border border-slate-250 dark:border-white/10 p-6 rounded-xl w-full max-w-lg shadow-2xl text-slate-800 dark:text-slate-200 font-mono">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 uppercase tracking-wider flex items-center gap-2">
                        <ExclamationTriangleIcon class="h-5 w-5 text-rose-500 animate-pulse" /> Report Machine Breakdown
                    </h3>
                    <form @submit.prevent="submitReport" class="space-y-4">
                        <div>
                            <label class="block text-xs text-slate-505 dark:text-slate-400 mb-1">Machine</label>
                            <select v-model="reportForm.machine_id" class="w-full bg-white dark:bg-[#0f172a] border border-slate-200 dark:border-white/10 rounded p-2 text-slate-800 dark:text-white outline-none focus:border-rose-500 font-mono" required>
                                <option v-for="m in machines" :key="m.id" :value="m.id" class="bg-white dark:bg-[#0f172a] text-slate-800 dark:text-white">{{ m.name }} ({{ m.code }})</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-slate-505 dark:text-slate-400 mb-1">Incident Description</label>
                            <textarea v-model="reportForm.description" class="w-full bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded p-2 text-slate-850 dark:text-white outline-none focus:border-rose-500 font-mono" rows="3" placeholder="Describe the symptoms, error codes, or physical damage..." required></textarea>
                        </div>
                        <div>
                            <label class="block text-xs text-slate-505 dark:text-slate-400 mb-1">Occurrence Time</label>
                            <input v-model="reportForm.started_at" type="datetime-local" class="w-full bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded p-2 text-slate-850 dark:text-white outline-none focus:border-rose-500 font-mono" required>
                        </div>
                        <div class="flex justify-end gap-2 pt-4">
                            <button type="button" @click="showReportModal = false" class="px-4 py-2 text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white bg-slate-50 dark:bg-transparent rounded-lg border border-slate-200 dark:border-transparent cursor-pointer">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-rose-600 text-white rounded-lg hover:bg-rose-550 border-0 cursor-pointer shadow-sm">Submit Incident</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Resolve Breakdown Modal -->
            <div v-if="showResolveModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
                <div class="bg-white dark:bg-[#0f172a] border border-slate-250 dark:border-white/10 p-6 rounded-xl w-full max-w-lg shadow-2xl text-slate-800 dark:text-slate-200 font-mono">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 uppercase tracking-wider flex items-center gap-2">
                        <WrenchScrewdriverIcon class="h-5 w-5 text-emerald-505" /> Update Incident Status
                    </h3>
                    <form @submit.prevent="submitResolve" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs text-slate-505 dark:text-slate-400 mb-1">New Status</label>
                                <select v-model="resolveForm.status" class="w-full bg-white dark:bg-[#0f172a] border border-slate-200 dark:border-white/10 rounded p-2 text-slate-800 dark:text-white outline-none focus:border-cyan-500 font-mono">
                                    <option value="resolved" class="bg-white dark:bg-[#0f172a]">Resolved (Fixed)</option>
                                    <option value="in_progress" class="bg-white dark:bg-[#0f172a]">In Progress (Working)</option>
                                    <option value="cancelled" class="bg-white dark:bg-[#0f172a]">Cancelled (False Alarm)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-slate-505 dark:text-slate-400 mb-1">Technician Name</label>
                                <input v-model="resolveForm.technician_name" type="text" class="w-full bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded p-2 text-slate-850 dark:text-white outline-none focus:border-cyan-500 font-mono" required>
                            </div>
                        </div>

                        <!-- Spareparts Selection (Only if resolved) -->
                        <div v-if="resolveForm.status === 'resolved'" class="border border-slate-200 dark:border-white/5 p-4 rounded-xl space-y-3 bg-slate-50 dark:bg-black/20">
                            <h4 class="text-xs font-bold text-slate-700 dark:text-cyan-400 uppercase tracking-wider">Spareparts Consumed</h4>
                            
                            <!-- Selected parts list -->
                            <div v-if="resolveForm.spareparts.length > 0" class="space-y-2">
                                <div v-for="(p, i) in resolveForm.spareparts" :key="i" class="flex justify-between items-center text-xs bg-white dark:bg-white/5 p-2 rounded border border-slate-100 dark:border-white/5 shadow-sm dark:shadow-none">
                                    <span class="text-slate-700 dark:text-slate-300 font-bold uppercase">{{ spareparts.find(sp => sp.id === parseInt(p.id))?.name }}</span>
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono bg-slate-100 dark:bg-white/5 px-2 py-0.5 rounded text-slate-805 dark:text-white">{{ p.qty }} pcs</span>
                                        <button type="button" @click="resolveForm.spareparts.splice(i, 1)" class="text-rose-600 hover:text-rose-500 text-xs font-bold bg-transparent border-0 cursor-pointer">Hapus</button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Add part selector -->
                            <div class="flex gap-2">
                                <select v-model="newPart.id" class="flex-1 bg-white dark:bg-[#0f172a] border border-slate-200 dark:border-white/10 rounded p-1.5 text-xs text-slate-805 dark:text-white outline-none font-mono">
                                    <option value="" disabled class="bg-white dark:bg-[#0f172a]">Pilih Sparepart...</option>
                                    <option v-for="sp in spareparts" :key="sp.id" :value="sp.id" class="bg-white dark:bg-[#0f172a]">{{ sp.name }} (Stok: {{ sp.stock }})</option>
                                </select>
                                <input v-model.number="newPart.qty" type="number" min="1" class="w-16 bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded p-1.5 text-center text-slate-850 dark:text-white outline-none font-mono text-xs" />
                                <button type="button" @click="addPartToResolve" class="px-3 py-1 bg-cyan-600 text-white rounded text-xs hover:bg-cyan-500 border-0 cursor-pointer font-bold">Tambah</button>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-4">
                            <button type="button" @click="showResolveModal = false" class="px-4 py-2 text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white bg-slate-50 dark:bg-transparent rounded-lg border border-slate-200 dark:border-transparent cursor-pointer">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-555 border-0 cursor-pointer shadow-sm">Save Changes</button>
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
        linear-gradient(to right, rgba(244, 63, 94, 0.05) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(244, 63, 94, 0.05) 1px, transparent 1px);
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
    text-shadow: 0 0 10px rgba(244, 63, 94, 0.3);
}
</style>
