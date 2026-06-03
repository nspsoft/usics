<script setup>
import { computed, ref, onMounted } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import {
    CalendarDaysIcon,
    ClockIcon,
    MapPinIcon,
    UserIcon,
    CheckCircleIcon,
    UserPlusIcon,
    SparklesIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    meeting: Object,
    auth_user: Object,
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const isGuestMode = ref(!props.auth_user);
const successCheckedIn = ref(false);

const form = useForm({
    is_guest: !props.auth_user,
    guest_name: '',
});

const submitCheckIn = () => {
    form.post(route('meeting-command.check-in.submit', props.meeting.id), {
        onSuccess: () => {
            successCheckedIn.value = true;
        }
    });
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    });
};

const formatTime = (timeStr) => {
    if (!timeStr) return '-';
    return timeStr.substring(0, 5);
};
</script>

<template>
    <Head :title="`Check-In: ${meeting.title}`" />
    
    <div class="min-h-screen bg-[#030108] p-4 flex flex-col justify-center items-center font-mono text-slate-50 relative overflow-hidden">
        <!-- Glow backgrounds -->
        <div class="absolute inset-0 z-0 pointer-events-none">
            <div class="absolute inset-0 bg-gradient-to-b from-purple-950/10 to-[#030108]"></div>
            <div class="absolute top-[20%] left-[30%] w-[400px] h-[400px] bg-purple-500/5 blur-[120px] rounded-full"></div>
        </div>

        <div class="relative z-10 w-full max-w-md bg-[#0c0517]/90 border border-purple-500/20 rounded-3xl p-6 shadow-2xl shadow-purple-550/10 space-y-6">
            <!-- Title -->
            <div class="text-center border-b border-purple-500/10 pb-4">
                <h1 class="text-lg font-black tracking-widest text-white glow-text-purple uppercase flex items-center justify-center gap-1.5">
                    <SparklesIcon class="h-5 w-5 text-purple-400 animate-pulse" />
                    MEETING CHECK-IN
                </h1>
                <p class="text-[8px] text-slate-500 tracking-widest uppercase mt-1">Konfirmasi Kehadiran Rapat Mandiri</p>
            </div>

            <!-- Success Screen -->
            <div v-if="successCheckedIn || flashSuccess" class="text-center py-6 space-y-4 animate-fadeIn">
                <CheckCircleIcon class="h-16 w-16 text-emerald-400 mx-auto animate-bounce" />
                <div class="space-y-1">
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider">Check-In Berhasil!</h3>
                    <p class="text-[10px] text-slate-400 leading-relaxed">
                        Kehadiran Anda di rapat <span class="text-purple-400 font-bold">"{{ meeting.title }}"</span> telah dicatat secara resmi di dalam sistem.
                    </p>
                </div>
                <div class="pt-4 border-t border-purple-500/5 text-[9px] text-slate-500">
                    Anda dapat menutup jendela browser ini sekarang.
                </div>
            </div>

            <!-- Form Screen -->
            <div v-else class="space-y-5">
                <!-- Meeting details -->
                <div class="bg-[#030108]/60 border border-purple-500/5 rounded-2xl p-4 space-y-3">
                    <h2 class="text-xs font-black text-slate-100 uppercase tracking-wide border-b border-purple-500/5 pb-1">{{ meeting.title }}</h2>
                    <div class="space-y-1.5 text-[9px] text-slate-400">
                        <div class="flex items-center gap-2">
                            <CalendarDaysIcon class="h-3.5 w-3.5 text-purple-400 shrink-0" />
                            <span>{{ formatDate(meeting.meeting_date) }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <ClockIcon class="h-3.5 w-3.5 text-purple-400 shrink-0" />
                            <span>{{ formatTime(meeting.start_time) }} - {{ formatTime(meeting.end_time) }} WIB</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <MapPinIcon class="h-3.5 w-3.5 text-purple-400 shrink-0" />
                            <span class="truncate">{{ meeting.location }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <UserIcon class="h-3.5 w-3.5 text-purple-400 shrink-0" />
                            <span>Pimpinan Rapat: {{ meeting.chairperson?.name }}</span>
                        </div>
                    </div>
                </div>

                <!-- Tabs (only if logged in) -->
                <div v-if="auth_user" class="grid grid-cols-2 gap-2 border-b border-purple-500/10 pb-2">
                    <button 
                        type="button"
                        @click="isGuestMode = false; form.is_guest = false"
                        class="py-2 text-center text-[9px] font-black uppercase tracking-wider rounded-lg border transition-all"
                        :class="!isGuestMode 
                            ? 'bg-purple-950/40 text-purple-400 border-purple-500/30 glow-text-purple' 
                            : 'bg-transparent text-slate-500 border-transparent hover:text-slate-300'"
                    >
                        Karyawan (JIDOKA)
                    </button>
                    <button 
                        type="button"
                        @click="isGuestMode = true; form.is_guest = true"
                        class="py-2 text-center text-[9px] font-black uppercase tracking-wider rounded-lg border transition-all"
                        :class="isGuestMode 
                            ? 'bg-purple-950/40 text-purple-400 border-purple-500/30 glow-text-purple' 
                            : 'bg-transparent text-slate-500 border-transparent hover:text-slate-300'"
                    >
                        Tamu Eksternal
                    </button>
                </div>

                <form @submit.prevent="submitCheckIn" class="space-y-4">
                    <!-- Mode: Employee -->
                    <div v-if="!isGuestMode" class="space-y-3">
                        <div class="p-4 bg-[#030108]/40 border border-purple-500/10 rounded-2xl text-center space-y-2">
                            <UserIcon class="h-8 w-8 text-purple-400 mx-auto" />
                            <div class="text-[10px] text-slate-350 leading-relaxed">
                                Anda terdeteksi masuk sebagai:<br/>
                                <strong class="text-white text-xs block mt-1">{{ auth_user.name }}</strong>
                                <span class="text-slate-500">({{ auth_user.email }})</span>
                            </div>
                        </div>

                        <button 
                            type="submit" 
                            class="w-full py-3 rounded-xl bg-purple-600 hover:bg-purple-500 text-xs font-bold text-white shadow-lg shadow-purple-500/20 transition-all active:scale-95 flex items-center justify-center gap-2"
                            :disabled="form.processing"
                        >
                            <span v-if="form.processing" class="inline-block w-4.5 h-4.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                            <span>{{ form.processing ? 'Mencatat...' : 'Konfirmasi Check-In Karyawan' }}</span>
                        </button>
                    </div>

                    <!-- Mode: Guest -->
                    <div v-else class="space-y-4">
                        <div class="space-y-2">
                            <label class="block text-[8px] font-bold text-slate-400 uppercase tracking-widest">Nama Lengkap Tamu</label>
                            <div class="relative rounded-xl shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <UserPlusIcon class="h-4 w-4 text-purple-450" />
                                </div>
                                <input 
                                    type="text" 
                                    v-model="form.guest_name" 
                                    class="w-full bg-[#030108] border-0 ring-1 ring-purple-500/25 rounded-xl pl-9 pr-4 py-3 text-xs text-white placeholder-slate-600 focus:ring-2 focus:ring-purple-500 font-mono"
                                    placeholder="Masukkan nama lengkap Anda..."
                                    required
                                    :disabled="form.processing"
                                />
                            </div>
                        </div>

                        <!-- Info unauthenticated warning -->
                        <div v-if="!auth_user" class="text-[8px] text-slate-500 leading-relaxed bg-[#030108]/20 p-2.5 rounded-lg border border-purple-500/5">
                            *Jika Anda karyawan internal PT SPINDO / JIDOKA, Anda disarankan untuk melakukan login terlebih dahulu agar absensi tercatat langsung di bawah akun Anda.
                        </div>

                        <button 
                            type="submit" 
                            class="w-full py-3 rounded-xl bg-purple-650 hover:bg-purple-600 text-xs font-bold text-white shadow-lg shadow-purple-500/20 transition-all active:scale-95 flex items-center justify-center gap-2 disabled:opacity-50 disabled:pointer-events-none"
                            :disabled="form.processing || !form.guest_name.trim()"
                        >
                            <span v-if="form.processing" class="inline-block w-4.5 h-4.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                            <span>{{ form.processing ? 'Mencatat...' : 'Konfirmasi Check-In Tamu' }}</span>
                        </button>
                    </div>
                </form>

                <!-- Local Error Alert -->
                <div v-if="form.errors.guest_name || flashError" class="p-3 bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-xl text-[9px] font-bold text-center animate-pulse">
                    {{ form.errors.guest_name || flashError }}
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.glow-text-purple {
    text-shadow: 0 0 10px rgba(168, 85, 247, 0.5);
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fadeIn {
    animation: fadeIn 0.4s ease-out forwards;
}
</style>
