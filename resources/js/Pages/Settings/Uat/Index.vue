<script setup>
import { ref, computed, nextTick } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    CheckCircleIcon,
    XCircleIcon,
    ExclamationCircleIcon,
    ClipboardDocumentCheckIcon,
    ChevronDownIcon,
    ChevronUpIcon,
    PencilSquareIcon,
    ArrowDownTrayIcon,
} from '@heroicons/vue/24/outline';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    groupedScenarios: Object,
    stats: Object,
});

const openModule = ref('Sales'); // Default open module

const toggleModule = (module) => {
    openModule.value = openModule.value === module ? null : module;
};

const getStatusColor = (status) => {
    switch (status) {
        case 'passed': return 'text-emerald-500 bg-emerald-50 dark:bg-emerald-500/10 border-emerald-200 dark:border-emerald-500/20';
        case 'failed': return 'text-red-500 bg-red-50 dark:bg-red-500/10 border-red-200 dark:border-red-500/20';
        default: return 'text-slate-500 bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700';
    }
};

const getStatusIcon = (status) => {
    switch (status) {
        case 'passed': return CheckCircleIcon;
        case 'failed': return XCircleIcon;
        default: return ExclamationCircleIcon;
    }
};

const editingNote = ref(null);
const noteInput = ref(null);
const noteForm = useForm({
    notes: '',
    status: '',
});

const startEdit = (scenario, forceStatus = null) => {
    editingNote.value = scenario.id;
    noteForm.notes = scenario.notes || '';
    noteForm.status = forceStatus || scenario.status;
    
    // Focus textarea
    nextTick(() => {
        if(noteInput.value) {
            // If it's an array (v-for ref), get the last one or specific one? 
            // Simplified: user clicks one at a time. 
            // With v-for refs in Vue 3, it might be an array.
            // Using ID to find element might be safer or just rely on autofocus
            // Let's rely on standard ref binding in v-for which returns array
        }
    });
};

const saveScenario = (scenario, newStatus = null) => {
    if (newStatus === 'failed') {
        // If clicking "Fail", open edit mode instead of immediate save
        startEdit(scenario, 'failed');
        return;
    }

    if (newStatus) {
        noteForm.status = newStatus;
    }
    
    noteForm.put(route('settings.uat.update', scenario.id), {
        preserveScroll: true,
        onSuccess: () => {
            editingNote.value = null;
        }
    });
};

const cancelEdit = () => {
    editingNote.value = null;
    noteForm.reset();
};

const progressColor = computed(() => {
    if (props.stats.progress >= 100) return 'bg-emerald-500';
    if (props.stats.progress >= 50) return 'bg-blue-500';
    return 'bg-amber-500';
});
</script>

<template>
    <Head title="Pengujian Sistem (UAT)" />

    <AppLayout title="Pengujian Sistem (UAT)">
        <div class="max-w-7xl mx-auto">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Pengujian Sistem (UAT)</h1>
                    <p class="text-sm text-slate-500">Pantau dan verifikasi kesiapan fitur sistem USICS ERP.</p>
                </div>
                <div class="flex items-center gap-3">
                    <Link 
                        :href="route('settings.uat.export')" 
                        class="px-5 py-2.5 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-xl font-bold text-sm shadow-lg shadow-slate-900/20 dark:shadow-white/10 hover:scale-105 transition-all flex items-center gap-2"
                    >
                        <ArrowDownTrayIcon class="h-4 w-4" />
                        Export Laporan
                    </Link>
                </div>
            </div>

            <!-- Header Stats -->
            <div class="mb-8 grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="glass-card p-6 rounded-2xl flex flex-col items-center justify-center text-center">
                    <div class="text-3xl font-bold text-slate-900 dark:text-white mb-1">{{ stats.progress }}%</div>
                    <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2 mb-2 overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-500" :class="progressColor" :style="{ width: `${stats.progress}%` }"></div>
                    </div>
                    <div class="text-xs text-slate-500 uppercase font-bold tracking-wider">Progres Total</div>
                </div>
                
                <div class="glass-card p-6 rounded-2xl flex items-center justify-between border-l-4 border-emerald-500">
                    <div>
                        <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ stats.passed }}</div>
                        <div class="text-xs text-slate-500 uppercase font-bold tracking-wider">Lulus (Passed)</div>
                    </div>
                    <CheckCircleIcon class="h-8 w-8 text-emerald-500/20" />
                </div>

                <div class="glass-card p-6 rounded-2xl flex items-center justify-between border-l-4 border-red-500">
                    <div>
                        <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ stats.failed }}</div>
                        <div class="text-xs text-slate-500 uppercase font-bold tracking-wider">Gagal (Failed)</div>
                    </div>
                    <XCircleIcon class="h-8 w-8 text-red-500/20" />
                </div>

                <div class="glass-card p-6 rounded-2xl flex items-center justify-between border-l-4 border-slate-400">
                    <div>
                        <div class="text-2xl font-bold text-slate-600 dark:text-slate-400">{{ stats.pending }}</div>
                        <div class="text-xs text-slate-500 uppercase font-bold tracking-wider">Belum Diuji</div>
                    </div>
                    <ExclamationCircleIcon class="h-8 w-8 text-slate-500/20" />
                </div>
            </div>

            <!-- Scenarios List -->
            <div class="space-y-4">
                <div v-for="(scenarios, module) in groupedScenarios" :key="module" class="glass-card rounded-2xl overflow-hidden">
                    <!-- Module Header -->
                    <button 
                        @click="toggleModule(module)"
                        class="w-full flex items-center justify-between p-5 bg-slate-50/50 dark:bg-slate-800/50 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                    >
                        <div class="flex items-center gap-3">
                            <ClipboardDocumentCheckIcon class="h-6 w-6 text-blue-500" />
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Modul {{ module }}</h3>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300">
                                {{ scenarios.length }} Skenario
                            </span>
                        </div>
                        <div class="flex items-center gap-3">
                            <a 
                                :href="route('settings.uat.export', { module: module })" 
                                target="_blank"
                                class="p-1 px-3 rounded-lg bg-slate-200 text-slate-600 hover:bg-slate-300 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600 text-xs font-bold transition-colors flex items-center gap-1"
                                @click.stop
                            >
                                <component :is="ArrowDownTrayIcon" class="h-3 w-3" />
                                Export
                            </a>
                            <component :is="openModule === module ? ChevronUpIcon : ChevronDownIcon" class="h-5 w-5 text-slate-400" />
                        </div>
                    </button>

                    <!-- Scenarios Body -->
                    <div v-show="openModule === module" class="border-t border-slate-100 dark:border-slate-800">
                        <div v-for="scenario in scenarios" :key="scenario.id" class="p-5 border-b last:border-0 border-slate-100 dark:border-slate-800 hover:bg-slate-50/30 dark:hover:bg-slate-800/30 transition-colors">
                            <div class="flex flex-col md:flex-row gap-4">
                                <!-- Status Indicator -->
                                <div class="shrink-0 pt-1">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center border-2 transition-colors" :class="getStatusColor(scenario.status).replace('text-', 'border-').replace('bg-', 'text-')">
                                        <component :is="getStatusIcon(scenario.status)" class="h-5 w-5" />
                                    </div>
                                </div>

                                <!-- Content -->
                                <div class="flex-1">
                                    <div class="flex items-start justify-between gap-4 mb-2">
                                        <div>
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="text-xs font-mono font-bold text-slate-400">{{ scenario.code }}</span>
                                                <span class="text-xs px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-500">{{ scenario.feature }}</span>
                                            </div>
                                            <h4 class="text-base font-bold text-slate-900 dark:text-white">{{ scenario.title }}</h4>
                                        </div>
                                        
                                        <!-- Actions -->
                                        <div class="flex items-center gap-2 shrink-0">
                                            <button 
                                                v-if="scenario.status !== 'passed'"
                                                @click="saveScenario(scenario, 'passed')"
                                                class="p-2 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 dark:bg-emerald-500/10 dark:text-emerald-400 dark:hover:bg-emerald-500/20 transition-colors"
                                                title="Tandai Lulus (Passed)"
                                            >
                                                <CheckCircleIcon class="h-5 w-5" />
                                            </button>
                                            <button 
                                                v-if="scenario.status !== 'failed'"
                                                @click="saveScenario(scenario, 'failed')"
                                                class="p-2 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-500/10 dark:text-red-400 dark:hover:bg-red-500/20 transition-colors"
                                                title="Tandai Gagal (Failed) & Isi Keterangan"
                                            >
                                                <XCircleIcon class="h-5 w-5" />
                                            </button>
                                            <button 
                                                @click="startEdit(scenario)"
                                                class="p-2 rounded-lg bg-slate-50 text-slate-600 hover:bg-slate-100 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700 transition-colors"
                                                title="Edit Keterangan"
                                            >
                                                <PencilSquareIcon class="h-5 w-5" />
                                            </button>
                                        </div>
                                    </div>

                                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-3">{{ scenario.description }}</p>
                                    
                                    <div class="bg-slate-50 dark:bg-slate-900 rounded-lg p-3 text-sm border border-slate-100 dark:border-slate-800">
                                        <span class="font-bold text-slate-700 dark:text-slate-300 block mb-1">Kriteria Penerimaan (Acceptance Criteria):</span>
                                        <span class="text-slate-600 dark:text-slate-400">{{ scenario.acceptance_criteria }}</span>
                                    </div>

                                    <!-- Notes & Tester Info -->
                                    <div v-if="scenario.notes || scenario.tested_by" class="mt-3 flex items-start gap-2 text-xs text-slate-500">
                                        <div v-if="scenario.notes" class="flex-1 bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 p-2 rounded border border-amber-100 dark:border-amber-500/20">
                                            <strong>Keterangan:</strong> {{ scenario.notes }}
                                        </div>
                                        <div v-if="scenario.tested_by" class="shrink-0 flex flex-col items-end">
                                            <span>Diuji oleh: <strong>{{ scenario.tester?.name || 'Unknown' }}</strong></span>
                                            <span>{{ new Date(scenario.tested_at).toLocaleString('id-ID') }}</span>
                                        </div>
                                    </div>

                                    <!-- Edit Form -->
                                    <div v-if="editingNote === scenario.id" class="mt-4 p-4 bg-white dark:bg-slate-950 rounded-xl border border-slate-200 dark:border-slate-700 shadow-lg animate-in fade-in slide-in-from-top-2">
                                        <div class="mb-3">
                                            <label class="block text-xs font-bold text-slate-500 mb-1">Status Pengujian</label>
                                            <div class="flex gap-2">
                                                <label class="flex items-center gap-2 cursor-pointer">
                                                    <input type="radio" v-model="noteForm.status" value="passed" class="text-emerald-500 focus:ring-emerald-500">
                                                    <span class="text-sm text-slate-700 dark:text-slate-300">Lulus</span>
                                                </label>
                                                <label class="flex items-center gap-2 cursor-pointer">
                                                    <input type="radio" v-model="noteForm.status" value="failed" class="text-red-500 focus:ring-red-500">
                                                    <span class="text-sm text-slate-700 dark:text-slate-300">Gagal</span>
                                                </label>
                                                <label class="flex items-center gap-2 cursor-pointer">
                                                    <input type="radio" v-model="noteForm.status" value="pending" class="text-slate-500 focus:ring-slate-500">
                                                    <span class="text-sm text-slate-700 dark:text-slate-300">Belum Diuji</span>
                                                </label>
                                            </div>
                                        </div>
                                        <label class="block text-xs font-bold text-slate-500 mb-1">
                                            Keterangan / Catatan <span v-if="noteForm.status === 'failed'" class="text-red-500">* (Wajib diisi jika Gagal)</span>
                                        </label>
                                        <textarea 
                                            ref="noteInput"
                                            v-model="noteForm.notes"
                                            class="w-full rounded-lg border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-sm focus:ring-blue-500 mb-3"
                                            rows="3"
                                            placeholder="Tuliskan hasil pengujian atau alasan kegagalan..."
                                        ></textarea>
                                        <div class="flex justify-end gap-2">
                                            <button 
                                                @click="cancelEdit"
                                                class="px-3 py-1.5 rounded-lg text-sm text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                                            >
                                                Batal
                                            </button>
                                            <button 
                                                @click="saveScenario(scenario)"
                                                class="px-3 py-1.5 rounded-lg text-sm bg-blue-600 text-white hover:bg-blue-500 transition-colors font-medium shadow-lg shadow-blue-500/20"
                                            >
                                                Simpan Hasil
                                            </button>
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
