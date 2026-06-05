<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';
import { 
    ExclamationTriangleIcon,
    WrenchScrewdriverIcon,
    ClockIcon,
    CheckCircleIcon,
    PlusIcon
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
</script>

<template>
    <Head title="Machine Breakdowns" />

    <AppLayout title="Breakdown Logs" :render-header="false">
        <div class="min-h-screen bg-[#050510] relative overflow-hidden font-mono text-cyan-50 selection:bg-rose-500/30">
            
            <!-- Dynamic Background -->
            <div class="fixed inset-0 z-0 pointer-events-none">
                <div class="absolute inset-0 bg-gradient-to-br from-rose-950/20 to-[#050510]"></div>
                 <div class="perspective-grid absolute inset-0 opacity-10"></div>
            </div>

            <div class="relative z-10 p-6 space-y-6 max-w-7xl mx-auto">
                
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-white/10 pb-4">
                    <div>
                         <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-0.5 text-[10px] bg-rose-500/10 border border-rose-500/20 rounded text-rose-400 tracking-[0.2em] uppercase animate-pulse">
                                Live Monitoring
                            </span>
                            <span class="px-2 py-0.5 text-[10px] bg-white/5 border border-white/10 rounded text-slate-400 tracking-[0.2em] uppercase">MAINT.LOGS.V1</span>
                        </div>
                        <h1 class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-rose-400 via-white to-amber-400 tracking-widest uppercase glow-text">
                            BREAKDOWN LOGS
                        </h1>
                    </div>
                    <button 
                        @click="showReportModal = true"
                        class="px-4 py-2 bg-gradient-to-r from-rose-600 to-rose-500 hover:from-rose-500 hover:to-rose-400 text-white font-bold rounded-lg shadow-[0_0_15px_rgba(244,63,94,0.3)] transition-all flex items-center gap-2 text-sm animate-pulse"
                    >
                        <ExclamationTriangleIcon class="h-4 w-4" /> REPORT BREAKDOWN
                    </button>
                </div>

                <!-- Active Issues (Pinned at Top) -->
                <div v-if="logs.some(l => l.status === 'Open' || l.status === 'In_progress')" class="space-y-4">
                    <h3 class="text-sm font-bold text-rose-400 uppercase tracking-widest flex items-center gap-2">
                        <ExclamationTriangleIcon class="h-4 w-4" /> Active Incidents
                    </h3>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div v-for="log in logs.filter(l => l.status === 'Open' || l.status === 'In_progress')" :key="log.id" 
                            class="hud-panel p-5 border-l-4 border-l-rose-500 bg-rose-500/5 relative overflow-hidden group"
                        >
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h4 class="text-xl font-bold text-white">{{ log.machine }}</h4>
                                    <p class="text-xs text-rose-400 font-bold uppercase tracking-wider">{{ log.status }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-[10px] text-slate-500 uppercase">Started</p>
                                    <p class="text-xs text-slate-300 font-mono">{{ log.started_at }}</p>
                                </div>
                            </div>
                            <p class="text-sm text-slate-300 mb-6 font-bold bg-black/20 p-2 rounded border border-white/5">
                                "{{ log.description }}"
                            </p>
                            
                            <div class="flex items-center justify-between mt-auto">
                                <div class="flex items-center gap-2 text-xs text-amber-500">
                                    <ClockIcon class="h-4 w-4" />
                                    <span>Duration: {{ log.duration }}</span>
                                </div>
                                <button 
                                    @click="openResolve(log)"
                                    class="px-3 py-1.5 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/50 rounded text-xs uppercase tracking-wider transition-colors"
                                >
                                    Update Status
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Historical Logs -->
                <div class="space-y-4 pt-6 md:col-span-2">
                     <h3 class="text-sm font-bold text-slate-400 uppercase tracking-widest flex items-center gap-2">
                        <WrenchScrewdriverIcon class="h-4 w-4" /> Repair History
                    </h3>
                    <div class="overflow-x-auto rounded-xl border border-white/10">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-white/5 text-[10px] uppercase tracking-wider text-slate-400 font-bold">
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
                            <tbody class="divide-y divide-white/5 bg-slate-900/50">
                                <tr v-for="log in logs.filter(l => l.status === 'Resolved' || l.status === 'Cancelled')" :key="log.id" class="hover:bg-white/5 transition-colors">
                                    <td class="p-4 font-mono text-slate-400">{{ log.started_at }}</td>
                                    <td class="p-4 font-bold text-white">{{ log.machine_code }}</td>
                                    <td class="p-4 text-slate-300">{{ log.description }}</td>
                                    <td class="p-4 text-slate-400">{{ log.technician }}</td>
                                    <td class="p-4 text-xs text-slate-500">{{ log.parts_used || '-' }}</td>
                                    <td class="p-4 text-center font-mono text-cyan-400">{{ log.duration }}</td>
                                    <td class="p-4 text-center">
                                        <span class="px-2 py-1 rounded text-[10px] font-bold border" :class="log.status_color">
                                            {{ log.status }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Report Modal -->
            <div v-if="showReportModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
                 <div class="bg-[#0f172a] border border-rose-500/30 p-6 rounded-xl w-full max-w-lg shadow-[0_0_50px_rgba(225,29,72,0.2)]">
                    <h3 class="text-lg font-bold text-rose-400 mb-4 uppercase tracking-wider flex items-center gap-2">
                        <ExclamationTriangleIcon class="h-5 w-5" /> Report Issue
                    </h3>
                    <form @submit.prevent="submitReport" class="space-y-4">
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Machine</label>
                            <select v-model="reportForm.machine_id" class="w-full bg-[#0f172a] border border-white/10 rounded p-2 text-white outline-none focus:border-rose-500">
                                <option v-for="m in machines" :key="m.id" :value="m.id" class="bg-[#0f172a] text-white">{{ m.name }} ({{ m.code }})</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Problem Description</label>
                            <textarea v-model="reportForm.description" class="w-full bg-white/5 border border-white/10 rounded p-2 text-white outline-none focus:border-rose-500" rows="3" placeholder="Describe the sound, error code, or behavior..."></textarea>
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Time Detected</label>
                            <input v-model="reportForm.started_at" type="datetime-local" class="w-full bg-white/5 border border-white/10 rounded p-2 text-white outline-none focus:border-rose-500">
                        </div>
                        <div class="flex justify-end gap-2 pt-4">
                            <button type="button" @click="showReportModal = false" class="px-4 py-2 text-slate-400 hover:text-white">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-rose-600 text-white rounded hover:bg-rose-500 font-bold">SUBMIT REPORT</button>
                        </div>
                    </form>
                </div>
            </div>

             <!-- Resolve Modal -->
            <div v-if="showResolveModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
                 <div class="bg-[#0f172a] border border-emerald-500/30 p-6 rounded-xl w-full max-w-lg shadow-2xl">
                    <h3 class="text-lg font-bold text-emerald-400 mb-4 uppercase tracking-wider">Update Status</h3>
                    <form @submit.prevent="submitResolve" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs text-slate-400 mb-1">Status</label>
                                <select v-model="resolveForm.status" class="w-full bg-[#0f172a] border border-white/10 rounded p-2 text-white outline-none focus:border-emerald-500">
                                    <option value="in_progress" class="bg-[#0f172a] text-white">In Progress</option>
                                    <option value="resolved" class="bg-[#0f172a] text-white">Resolved (Close Ticket)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-slate-400 mb-1">Technician</label>
                                <input v-model="resolveForm.technician_name" type="text" class="w-full bg-white/5 border border-white/10 rounded p-2 text-white outline-none focus:border-emerald-500" placeholder="Name">
                            </div>
                        </div>

                        <div class="border-t border-white/10 pt-4">
                            <label class="block text-xs text-slate-400 mb-2">Record Spareparts Used</label>
                            <div class="flex gap-2 mb-2">
                                <select v-model="newPart.id" class="flex-1 bg-[#0f172a] border border-white/10 rounded p-2 text-white text-xs outline-none">
                                    <option value="" disabled class="bg-[#0f172a] text-slate-400">Select Part</option>
                                    <option v-for="part in spareparts" :key="part.id" :value="part.id" class="bg-[#0f172a] text-white">{{ part.part_number }} - {{ part.name }} (Stock: {{ part.stock }})</option>
                                </select>
                                <input v-model="newPart.qty" type="number" min="1" class="w-16 bg-white/5 border border-white/10 rounded p-2 text-white text-xs" placeholder="Qty">
                                <button type="button" @click="addPartToResolve" class="px-3 bg-white/10 rounded hover:bg-white/20 text-white">+</button>
                            </div>
                            <!-- List added parts -->
                            <div class="space-y-1">
                                <div v-for="(p, i) in resolveForm.spareparts" :key="i" class="flex justify-between text-xs text-slate-300 bg-white/5 p-1 px-2 rounded">
                                    <span>Part ID: {{ p.id }} (Qty: {{ p.qty }})</span>
                                    <button type="button" @click="resolveForm.spareparts.splice(i, 1)" class="text-rose-400">x</button>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-4">
                            <button type="button" @click="showResolveModal = false" class="px-4 py-2 text-slate-400 hover:text-white">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded hover:bg-emerald-500 font-bold">UPDATE TICKET</button>
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
.glow-text {
    text-shadow: 0 0 10px rgba(244, 63, 94, 0.3);
}
</style>
