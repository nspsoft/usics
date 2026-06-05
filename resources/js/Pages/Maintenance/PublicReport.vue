<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { 
    ExclamationTriangleIcon,
    CheckCircleIcon,
    ChevronRightIcon,
    ArrowPathIcon,
    UserIcon,
    ShieldExclamationIcon,
    ChatBubbleBottomCenterTextIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    machine: Object
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);

const form = useForm({
    reporter_name: '',
    severity: 'medium',
    description: '',
});

const isSubmitting = ref(false);

const submitReport = () => {
    isSubmitting.value = true;
    form.post(route('maintenance.public.breakdown.store', props.machine.qr_code_uuid), {
        onFinish: () => {
            isSubmitting.value = false;
        },
        onSuccess: () => {
            form.reset('description');
        }
    });
};
</script>

<template>
    <Head :title="'Laporkan Kerusakan: ' + machine.name" />

    <div class="min-h-screen bg-[#050510] relative overflow-hidden font-mono text-cyan-50 flex items-center justify-center p-4 selection:bg-rose-500/30">
        
        <!-- Cyber grid background -->
        <div class="fixed inset-0 z-0 pointer-events-none">
            <div class="absolute inset-0 bg-gradient-to-br from-rose-950/20 to-[#050510]"></div>
            <div class="perspective-grid absolute inset-0 opacity-15"></div>
        </div>

        <div class="relative z-10 w-full max-w-md my-8">
            
            <!-- Logo & Title -->
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center p-3 bg-rose-500/10 border border-rose-500/20 rounded-full mb-3 shadow-[0_0_15px_rgba(244,63,94,0.2)] animate-pulse">
                    <ExclamationTriangleIcon class="h-8 w-8 text-rose-500" />
                </div>
                <h1 class="text-2xl font-black tracking-widest text-transparent bg-clip-text bg-gradient-to-r from-rose-400 via-white to-amber-400 glow-text uppercase">
                    BREAKDOWN REPORTING
                </h1>
                <p class="text-[10px] text-slate-500 mt-1 uppercase tracking-wider">Halaman Pelaporan Kerusakan Cepat</p>
            </div>

            <!-- Success Panel -->
            <div v-if="flashSuccess" class="hud-panel p-6 border-l-4 border-l-emerald-500 bg-emerald-500/5 space-y-6 text-center shadow-[0_0_30px_rgba(16,185,129,0.15)] animate-fade-in">
                <div class="inline-flex items-center justify-center p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-full mb-1">
                    <CheckCircleIcon class="h-10 w-10 text-emerald-400" />
                </div>
                <div>
                    <h3 class="text-lg font-black text-emerald-400 uppercase tracking-widest">Laporan Terkirim!</h3>
                    <p class="text-xs text-slate-300 mt-2 font-sans leading-relaxed">
                        {{ flashSuccess }}
                    </p>
                </div>

                <div class="bg-black/30 border border-white/5 rounded-lg p-4 text-left space-y-2 text-xs">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Nama Mesin:</span>
                        <span class="text-white font-bold">{{ machine.name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Kode Mesin:</span>
                        <span class="text-cyan-400 font-bold font-mono">{{ machine.code }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Waktu Lapor:</span>
                        <span class="text-slate-300 font-mono">Baru saja</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Status Tiket:</span>
                        <span class="px-1.5 py-0.5 rounded text-[8px] bg-rose-500/10 border border-rose-500/20 text-rose-400 font-bold tracking-wider">OPEN</span>
                    </div>
                </div>

                <p class="text-[9px] text-slate-500 uppercase tracking-wider animate-pulse">
                    Anda sekarang dapat menutup halaman ini atau kembali memindai.
                </p>
            </div>

            <!-- Form Panel -->
            <div v-else class="hud-panel p-6 space-y-6">
                <!-- Machine Details Card -->
                <div class="bg-black/40 border border-white/5 rounded-xl p-4 flex items-center justify-between">
                    <div>
                        <span class="text-[9px] text-slate-500 uppercase tracking-wider block">Mesin Teridentifikasi</span>
                        <h2 class="text-base font-black text-white uppercase tracking-wide truncate max-w-[200px]">{{ machine.name }}</h2>
                        <span class="text-[10px] text-cyan-400 font-mono">{{ machine.code }}</span>
                    </div>
                    <div class="text-right">
                        <span class="px-2 py-0.5 rounded text-[8px] bg-rose-500/15 border border-rose-500/30 text-rose-400 font-bold tracking-widest">
                            DOWN STATE
                        </span>
                    </div>
                </div>

                <!-- Form -->
                <form @submit.prevent="submitReport" class="space-y-4">
                    <!-- Reporter Name -->
                    <div>
                        <label class="block text-xs text-slate-400 mb-1 flex items-center gap-1">
                            <UserIcon class="h-3.5 w-3.5" /> NAMA OPERATOR / PELAPOR
                        </label>
                        <div class="relative">
                            <input 
                                v-model="form.reporter_name"
                                type="text" 
                                required
                                placeholder="Masukkan nama lengkap Anda..."
                                class="w-full bg-black/40 border border-white/10 rounded-lg px-3 py-2 text-sm text-cyan-200 outline-none focus:border-rose-500 focus:shadow-[0_0_15px_rgba(244,63,94,0.15)] transition-all font-mono"
                            />
                        </div>
                        <span v-if="form.errors.reporter_name" class="text-[10px] text-rose-400 mt-1 block">{{ form.errors.reporter_name }}</span>
                    </div>

                    <!-- Severity -->
                    <div>
                        <label class="block text-xs text-slate-400 mb-1 flex items-center gap-1">
                            <ShieldExclamationIcon class="h-3.5 w-3.5" /> TINGKAT KEPARAHAN
                        </label>
                        <div class="grid grid-cols-3 gap-2">
                            <button 
                                type="button"
                                @click="form.severity = 'low'"
                                class="py-1.5 rounded-lg border text-xs font-bold uppercase transition-all tracking-wider"
                                :class="form.severity === 'low' 
                                    ? 'bg-emerald-500/10 border-emerald-500 text-emerald-400 font-black shadow-[0_0_10px_rgba(16,185,129,0.1)]' 
                                    : 'bg-black/20 border-white/5 text-slate-500 hover:text-slate-300'"
                            >
                                Ringan
                            </button>
                            <button 
                                type="button"
                                @click="form.severity = 'medium'"
                                class="py-1.5 rounded-lg border text-xs font-bold uppercase transition-all tracking-wider"
                                :class="form.severity === 'medium' 
                                    ? 'bg-amber-500/10 border-amber-500 text-amber-400 font-black shadow-[0_0_10px_rgba(245,158,11,0.1)]' 
                                    : 'bg-black/20 border-white/5 text-slate-500 hover:text-slate-300'"
                            >
                                Sedang
                            </button>
                            <button 
                                type="button"
                                @click="form.severity = 'high'"
                                class="py-1.5 rounded-lg border text-xs font-bold uppercase transition-all tracking-wider"
                                :class="form.severity === 'high' 
                                    ? 'bg-rose-500/10 border-rose-500 text-rose-400 font-black shadow-[0_0_10px_rgba(244,63,94,0.15)] animate-pulse' 
                                    : 'bg-black/20 border-white/5 text-slate-500 hover:text-slate-300'"
                            >
                                Berat
                            </button>
                        </div>
                        <span v-if="form.errors.severity" class="text-[10px] text-rose-400 mt-1 block">{{ form.errors.severity }}</span>
                    </div>

                    <!-- Problem Description -->
                    <div>
                        <label class="block text-xs text-slate-400 mb-1 flex items-center gap-1">
                            <ChatBubbleBottomCenterTextIcon class="h-3.5 w-3.5" /> DETAIL KERUSAKAN
                        </label>
                        <textarea 
                            v-model="form.description"
                            required
                            rows="4"
                            placeholder="Deskripsikan bunyi abnormal, kebocoran, slip belt, kode error, atau kondisi fisik mesin..."
                            class="w-full bg-black/40 border border-white/10 rounded-lg px-3 py-2 text-sm text-cyan-200 outline-none focus:border-rose-500 focus:shadow-[0_0_15px_rgba(244,63,94,0.15)] transition-all font-mono"
                        ></textarea>
                        <span v-if="form.errors.description" class="text-[10px] text-rose-400 mt-1 block">{{ form.errors.description }}</span>
                    </div>

                    <!-- Submit Button -->
                    <button 
                        type="submit"
                        :disabled="isSubmitting"
                        class="w-full py-3 bg-gradient-to-r from-rose-600 to-rose-500 hover:from-rose-500 hover:to-rose-400 disabled:from-rose-800 disabled:to-rose-800 text-white font-bold rounded-lg shadow-[0_0_15px_rgba(244,63,94,0.3)] transition-all flex items-center justify-center gap-2 text-sm uppercase tracking-wider"
                    >
                        <ArrowPathIcon v-if="isSubmitting" class="h-4 w-4 animate-spin" />
                        <span v-if="isSubmitting">Mengirim Laporan...</span>
                        <span v-else class="flex items-center gap-2">
                            KIRIM LAPORAN KERUSAKAN <ChevronRightIcon class="h-4 w-4" />
                        </span>
                    </button>
                </form>
            </div>

            <!-- Footer terms -->
            <p class="text-center text-[9px] text-slate-600 mt-6 leading-tight uppercase font-sans">
                PT Manufacturing Indonesia | Laporan dikirimkan langsung ke dashboard antrean pemeliharaan teknik secara otomatis.
            </p>

        </div>
    </div>
</template>

<style scoped>
.hud-panel {
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 16px;
}
.perspective-grid {
    background-image: 
        linear-gradient(to right, rgba(244, 63, 94, 0.05) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(244, 63, 94, 0.05) 1px, transparent 1px);
    background-size: 45px 45px;
    transform: perspective(400px) rotateX(55deg) translateY(-80px) scale(2);
    animation: grid-move 20s linear infinite;
    transform-origin: top;
}
@keyframes grid-move {
    0% { background-position: 0 0; }
    100% { background-position: 0 45px; }
}
.glow-text {
    text-shadow: 0 0 10px rgba(244, 63, 94, 0.3);
}
.animate-fade-in {
    animation: fadeIn 0.4s ease-out forwards;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
