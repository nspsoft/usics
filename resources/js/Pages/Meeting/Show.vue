<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import QrcodeVue from 'qrcode.vue';
import {
    ArrowLeftIcon,
    PencilSquareIcon,
    PrinterIcon,
    DocumentTextIcon,
    UserIcon,
    CalendarDaysIcon,
    ClockIcon,
    MapPinIcon,
    CheckIcon,
    ListBulletIcon,
    UserGroupIcon,
    ShieldCheckIcon,
    QrCodeIcon,
    PaperAirplaneIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    meeting: Object,
});

const isSending = ref(false);
const sendNotifications = () => {
    isSending.value = true;
    router.post(route('meeting-command.dispatch-notifications', props.meeting.id), {}, {
        preserveScroll: true,
        onFinish: () => {
            isSending.value = false;
        }
    });
};

const getStatusBadge = (status) => {
    if (!status) return 'text-slate-400 border-slate-500/30 bg-slate-500/5';
    const clean = status.toLowerCase();
    if (clean === 'locked') return 'text-rose-400 border-rose-400/30 bg-rose-500/5';
    if (clean === 'published') return 'text-emerald-400 border-emerald-400/30 bg-emerald-500/5';
    return 'text-slate-400 border-slate-400/30 bg-slate-500/5';
};

const getTypeBadge = (type) => {
    if (!type) return 'text-slate-400 border-slate-500/30 bg-slate-500/5';
    const clean = type.toLowerCase();
    if (clean === 'project') return 'text-purple-400 border-purple-400/30 bg-purple-500/5';
    if (clean === 'external') return 'text-sky-400 border-sky-400/30 bg-sky-500/5';
    return 'text-teal-400 border-teal-400/30 bg-teal-500/5';
};

const getAttendeeStatusClass = (status) => {
    if (!status) return 'text-slate-500 bg-slate-500/10';
    const clean = status.toLowerCase();
    if (clean === 'present') return 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20';
    if (clean === 'excused') return 'text-amber-400 bg-amber-500/10 border-amber-500/20';
    return 'text-rose-400 bg-rose-500/10 border-rose-500/20';
};

const getAttendeeStatusLabel = (status) => {
    if (!status) return '-';
    const clean = status.toLowerCase();
    if (clean === 'present') return 'Hadir';
    if (clean === 'excused') return 'Izin';
    return 'Absen';
};

const formatDate = (date) => {
    if (!date) return '-';
    try {
        return new Date(date).toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'long',
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

const toggleActionItem = (itemId) => {
    router.post(`/meeting-command/action-items/${itemId}/toggle`, {}, {
        preserveScroll: true
    });
};

const printMinutes = () => {
    window.print();
};

// QR Absensi Modal State
const showQrModal = ref(false);
const checkInUrl = computed(() => {
    return `${window.location.origin}/meeting-command/${props.meeting.id}/check-in`;
});

// Chairperson authorization check
const page = usePage();
const isChairperson = computed(() => {
    const userId = page.props.auth?.user?.id || page.props.user?.id;
    return userId === props.meeting.chairperson_id;
});

// Signature pad states and functions
const showSignModal = ref(false);
const canvasRef = ref(null);
let isDrawing = false;
let ctx = null;

const openSignatureModal = () => {
    showSignModal.value = true;
    setTimeout(() => {
        initCanvas();
    }, 100);
};

const initCanvas = () => {
    const canvas = canvasRef.value;
    if (!canvas) return;
    ctx = canvas.getContext('2d');
    ctx.strokeStyle = '#000000'; // Black stroke
    ctx.lineWidth = 3;
    ctx.lineCap = 'round';
    
    // Fill background with white
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
};

const handleStartDrawing = (e) => {
    isDrawing = true;
    draw(e);
};

const handleStopDrawing = () => {
    isDrawing = false;
    if (ctx) ctx.beginPath();
};

const draw = (e) => {
    if (!isDrawing || !ctx) return;
    const canvas = canvasRef.value;
    const rect = canvas.getBoundingClientRect();
    
    const clientX = e.clientX || e.touches?.[0]?.clientX;
    const clientY = e.clientY || e.touches?.[0]?.clientY;
    
    if (clientX === undefined || clientY === undefined) return;
    
    const x = clientX - rect.left;
    const y = clientY - rect.top;
    
    ctx.lineTo(x, y);
    ctx.stroke();
    ctx.beginPath();
    ctx.moveTo(x, y);
};

const clearCanvas = () => {
    const canvas = canvasRef.value;
    if (!canvas || !ctx) return;
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
};

const submitSignature = () => {
    const canvas = canvasRef.value;
    if (!canvas) return;
    
    const signatureImage = canvas.toDataURL('image/png');
    
    router.post(route('meeting-command.approve', props.meeting.id), {
        signature_image: signatureImage
    }, {
        onSuccess: () => {
            showSignModal.value = false;
        }
    });
};
</script>

<template>
    <Head :title="`Notulen: ${meeting?.title || ''}`" />
    
    <AppLayout title="Meeting Command Hub" :render-header="false">
        <div v-if="meeting" class="min-h-screen bg-[#030108] p-6 font-mono text-slate-50 relative overflow-hidden print:bg-white print:text-black print:p-0 print:min-h-0">
            <!-- Background effects (hidden on print) -->
            <div class="fixed inset-0 z-0 pointer-events-none print:hidden">
                <div class="absolute inset-0 bg-gradient-to-b from-purple-950/10 to-[#030108]"></div>
                <div class="absolute top-[-10%] right-[20%] w-[500px] h-[500px] bg-purple-500/5 blur-[120px] rounded-full"></div>
            </div>

            <!-- Screen View (Hidden on Print) -->
            <div class="relative z-10 max-w-7xl mx-auto space-y-6 print:hidden">
                <!-- Header (hidden on print) -->
                <div class="flex items-center justify-between mb-4 print:hidden">
                    <Link href="/meeting-command" class="inline-flex items-center gap-2 text-xs font-bold text-purple-400 hover:text-purple-300 transition-colors">
                        <ArrowLeftIcon class="h-4 w-4" /> KEMBALI KE DASHBOARD
                    </Link>

                    <div class="flex items-center gap-3">
                        <button 
                            @click="showQrModal = true" 
                            class="inline-flex items-center gap-2 rounded-xl bg-[#0c0517] hover:bg-[#160c29] border border-purple-500/20 px-4 py-2.5 text-xs font-bold text-purple-400 transition-all active:scale-95 shadow-sm"
                        >
                            <QrCodeIcon class="h-4 w-4 text-purple-355" />
                            QR Absensi
                        </button>

                        <button 
                            @click="printMinutes" 
                            class="inline-flex items-center gap-2 rounded-xl bg-[#0c0517] hover:bg-[#160c29] border border-purple-500/20 px-4 py-2.5 text-xs font-bold text-slate-350 transition-all active:scale-95 shadow-sm"
                        >
                            <PrinterIcon class="h-4 w-4" />
                            Cetak Notulen
                        </button>

                        <button 
                            v-if="meeting.status !== 'draft'"
                            @click="sendNotifications" 
                            :disabled="isSending"
                            class="inline-flex items-center gap-2 rounded-xl bg-[#0c0517] hover:bg-[#160c29] border border-purple-500/20 px-4 py-2.5 text-xs font-bold text-indigo-400 transition-all active:scale-95 shadow-sm disabled:opacity-50"
                        >
                            <PaperAirplaneIcon class="h-4 w-4" :class="{ 'animate-pulse': isSending }" />
                            {{ isSending ? 'Mengirim...' : 'Kirim Notifikasi' }}
                        </button>

                        <button 
                            v-if="meeting.status !== 'locked' && isChairperson"
                            @click="openSignatureModal" 
                            class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-fuchsia-600 to-pink-650 hover:from-fuchsia-500 hover:to-pink-550 px-5 py-2.5 text-xs font-black text-white shadow-lg shadow-fuchsia-500/20 transition-all active:scale-95 border border-fuchsia-400/25"
                        >
                            <ShieldCheckIcon class="h-4 w-4 text-fuchsia-200 animate-pulse" />
                            Tandatangani Rapat
                        </button>

                        <Link
                            v-if="meeting.status !== 'locked'"
                            :href="`/meeting-command/${meeting.id}/edit`"
                            class="inline-flex items-center gap-2 rounded-xl bg-purple-600 hover:bg-purple-500 px-5 py-2.5 text-xs font-bold text-white shadow-lg shadow-purple-500/20 transition-all active:scale-95"
                        >
                            <PencilSquareIcon class="h-4 w-4" />
                            Ubah Notulen
                        </Link>
                    </div>
                </div>

                <!-- Main Layout -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    <!-- Left Section: Details & Presence -->
                    <div class="lg:col-span-4 space-y-6">
                        <!-- Details -->
                        <div class="bg-[#0c0517]/85 border border-purple-500/15 rounded-2xl p-6 space-y-4 shadow-xl print:bg-white print:border-slate-300 print:text-black print:shadow-none">
                            <h3 class="text-xs font-black uppercase tracking-widest text-purple-400 border-b border-purple-500/10 pb-2 flex items-center gap-2 print:text-black print:border-slate-300">
                                <DocumentTextIcon class="h-4 w-4 text-purple-400 print:text-black" /> Ringkasan Rapat
                            </h3>

                            <dl class="space-y-3 text-xs">
                                <div class="flex justify-between py-1 border-b border-purple-500/5 print:border-slate-100">
                                    <dt class="text-slate-400 font-bold print:text-slate-600">Tanggal Rapat</dt>
                                    <dd class="font-bold text-white print:text-black flex items-center gap-1.5">
                                        <CalendarDaysIcon class="h-3.5 w-3.5 text-slate-500 print:hidden" />
                                        {{ formatDate(meeting.meeting_date) }}
                                    </dd>
                                </div>
                                <div class="flex justify-between py-1 border-b border-purple-500/5 print:border-slate-100">
                                    <dt class="text-slate-400 font-bold print:text-slate-600">Waktu</dt>
                                    <dd class="font-bold text-white print:text-black flex items-center gap-1.5">
                                        <ClockIcon class="h-3.5 w-3.5 text-slate-500 print:hidden" />
                                        {{ formatTime(meeting.start_time) }} - {{ formatTime(meeting.end_time) }}
                                    </dd>
                                </div>
                                <div class="flex justify-between py-1 border-b border-purple-500/5 print:border-slate-100">
                                    <dt class="text-slate-400 font-bold print:text-slate-600">Lokasi / Ruang</dt>
                                    <dd class="font-bold text-white print:text-black flex items-center gap-1.5 max-w-[180px] truncate">
                                        <MapPinIcon class="h-3.5 w-3.5 text-slate-500 print:hidden" />
                                        {{ meeting.location }}
                                    </dd>
                                </div>
                                <div class="flex justify-between py-1 border-b border-purple-500/5 print:border-slate-100">
                                    <dt class="text-slate-400 font-bold print:text-slate-600">Tipe</dt>
                                    <dd class="print:text-black">
                                        <span class="border rounded px-2 py-0.5 font-bold uppercase tracking-wider text-[9px]" :class="getTypeBadge(meeting.type)">
                                            {{ meeting.type }}
                                        </span>
                                    </dd>
                                </div>
                                <div class="flex justify-between py-1 border-b border-purple-500/5 print:border-slate-100">
                                    <dt class="text-slate-400 font-bold print:text-slate-600">Pimpinan (PIC)</dt>
                                    <dd class="font-bold text-white print:text-black flex items-center gap-1">
                                        <UserIcon class="h-3.5 w-3.5 text-purple-400 print:hidden" />
                                        {{ meeting.chairperson?.name }}
                                    </dd>
                                </div>
                                <div class="flex justify-between py-1 border-b border-purple-500/5 print:border-slate-100">
                                    <dt class="text-slate-400 font-bold print:text-slate-600">Notulis</dt>
                                    <dd class="font-bold text-white print:text-black flex items-center gap-1">
                                        <UserIcon class="h-3.5 w-3.5 text-slate-500 print:hidden" />
                                        {{ meeting.secretary?.name }}
                                    </dd>
                                </div>
                                <div class="flex justify-between py-1 print:border-slate-100">
                                    <dt class="text-slate-400 font-bold print:text-slate-600">Status</dt>
                                    <dd class="print:text-black">
                                        <span class="border rounded px-2 py-0.5 font-bold uppercase tracking-wider text-[9px]" :class="getStatusBadge(meeting.status)">
                                            {{ meeting.status }}
                                        </span>
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Attendees Presence -->
                        <div class="bg-[#0c0517]/85 border border-purple-500/15 rounded-2xl p-6 space-y-4 shadow-xl print:bg-white print:border-slate-300 print:text-black print:shadow-none">
                            <h3 class="text-xs font-black uppercase tracking-widest text-purple-400 border-b border-purple-500/10 pb-2 flex items-center gap-2 print:text-black print:border-slate-300">
                                <UserGroupIcon class="h-4 w-4 text-purple-400 print:text-black" /> Kehadiran Peserta
                            </h3>

                            <div class="space-y-2 max-h-[350px] overflow-y-auto pr-1">
                                <div v-for="att in meeting.attendees" :key="att.id" class="flex justify-between items-center bg-[#030108]/60 p-2.5 rounded-xl border border-purple-500/5 print:bg-slate-50 print:border-slate-200">
                                    <div>
                                        <div class="text-xs font-bold text-slate-200 print:text-black">
                                            {{ att.user ? att.user.name : att.guest_name }}
                                        </div>
                                        <div class="text-[9px] text-slate-500 mt-0.5">
                                            {{ att.user ? 'Karyawan Internal' : 'Tamu Eksternal' }}
                                        </div>
                                    </div>
                                    <span class="border px-2 py-0.5 text-[9px] font-bold rounded" :class="getAttendeeStatusClass(att.status)">
                                        {{ getAttendeeStatusLabel(att.status) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Section: Discussion Notes -->
                    <div class="lg:col-span-8 space-y-6">
                        <!-- Notes -->
                        <div class="bg-[#0c0517]/85 border border-purple-500/15 rounded-2xl p-6 shadow-xl flex flex-col min-h-[460px] print:bg-white print:border-slate-300 print:text-black print:shadow-none">
                            <h3 class="text-xs font-black uppercase tracking-widest text-purple-400 border-b border-purple-500/10 pb-2 mb-4 print:text-black print:border-slate-300">
                                HASIL PEMBAHASAN RAPAT (DISCUSSION NOTES)
                            </h3>
                            
                            <p class="text-xs text-slate-200 print:text-black whitespace-pre-line leading-relaxed flex-grow font-mono font-medium">
                                {{ meeting.discussion_notes || 'Belum ada catatan rapat / notulensi lengkap.' }}
                            </p>

                            <!-- Digital Signature & Cryptographic Stamp (when locked) -->
                            <div v-if="meeting.status === 'locked'" class="mt-6 pt-6 border-t border-purple-500/10 flex flex-col md:flex-row justify-between items-center gap-6 print:border-slate-200">
                                <div class="text-center md:text-left space-y-1.5">
                                    <label class="block text-[8px] font-bold text-slate-500 uppercase tracking-widest">Tanda Tangan Pimpinan</label>
                                    <div class="bg-white border border-purple-500/10 rounded-xl p-2 h-20 w-44 flex items-center justify-center relative overflow-hidden print:bg-white print:border-slate-300">
                                        <img :src="meeting.chairperson_signature" alt="Signature" class="h-full object-contain" />
                                    </div>
                                    <div class="text-[9px] font-bold text-white print:text-black">{{ meeting.chairperson?.name }}</div>
                                    <div class="text-[7px] text-slate-500">Disetujui pada: {{ formatDate(meeting.approved_at) }}</div>
                                </div>

                                <div class="border-2 border-dashed border-fuchsia-500/30 bg-fuchsia-500/5 rounded-2xl p-4 flex items-center gap-3 relative max-w-sm rotate-1 print:border-slate-400 print:bg-slate-50">
                                    <span class="absolute -top-2 left-4 px-2 py-0.5 bg-[#0c0517] text-[7px] font-black text-fuchsia-400 uppercase tracking-widest border border-fuchsia-500/20 print:bg-white print:text-slate-700 print:border-slate-300">
                                        SECURITY VERIFICATION
                                    </span>
                                    <ShieldCheckIcon class="h-8 w-8 text-fuchsia-500 shrink-0 print:text-slate-700" />
                                    <div class="space-y-1">
                                        <div class="text-[10px] font-black text-fuchsia-400 tracking-wider uppercase print:text-slate-800">
                                            VERIFIED & LOCKED
                                        </div>
                                        <div class="text-[7px] text-slate-500 leading-normal font-mono uppercase">
                                            ID: SEC-MOM-{{ meeting.id }}<br/>
                                            HASH: {{ meeting.signature_hash ? meeting.signature_hash.substring(0, 32) + '...' : '-' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-purple-500/10 pt-4 mt-6 flex justify-between text-[9px] text-slate-500 print:border-slate-200 print:text-slate-600">
                                <span>Dicatat oleh: {{ meeting.secretary?.name }}</span>
                                <span>Dokumen Terkunci: {{ meeting.status === 'locked' ? 'YA' : 'TIDAK' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Section: Action Items Checklist -->
                    <div class="lg:col-span-12 bg-[#0c0517]/85 border border-purple-500/15 rounded-2xl p-6 shadow-xl print:bg-white print:border-slate-300 print:text-black print:shadow-none">
                        <h3 class="text-xs font-black uppercase tracking-widest text-purple-400 border-b border-purple-500/10 pb-2 mb-4 flex items-center gap-2 print:text-black print:border-slate-300">
                            <ListBulletIcon class="h-4 w-4 text-purple-400 print:text-black" /> Penugasan Hasil Rapat (Action Items Checklist)
                        </h3>

                        <div class="space-y-2">
                            <div v-for="item in meeting.action_items" :key="item.id" class="flex flex-col sm:flex-row justify-between items-start sm:items-center bg-[#030108]/60 p-4 rounded-xl border border-purple-500/5 hover:border-purple-500/10 transition-colors print:bg-slate-50 print:border-slate-200">
                                <div class="flex items-start gap-3 w-full sm:w-2/3">
                                    <button 
                                        @click="toggleActionItem(item.id)" 
                                        class="h-5 w-5 rounded border flex items-center justify-center shrink-0 mt-0.5 transition-all print:hidden"
                                        :class="item.status === 'completed' 
                                            ? 'bg-emerald-500 border-emerald-500 text-white' 
                                            : 'border-purple-500/30 hover:border-purple-500 hover:bg-purple-500/10 bg-[#0c0517]'"
                                    >
                                        <CheckIcon v-if="item.status === 'completed'" class="h-3.5 w-3.5 font-black" />
                                    </button>

                                    <!-- Print-only indicator -->
                                    <div class="hidden print:block h-4 w-4 border border-black flex items-center justify-center shrink-0 mr-2 mt-0.5">
                                        <span v-if="item.status === 'completed'" class="text-xs font-bold">X</span>
                                    </div>

                                    <div>
                                        <p class="text-xs font-bold text-slate-100 print:text-black" :class="{ 'line-through text-slate-500': item.status === 'completed' }">
                                            {{ item.description }}
                                        </p>
                                        <p class="text-[9px] text-slate-500 mt-1 uppercase flex items-center gap-1.5">
                                            <span>PIC: <span class="text-purple-400 print:text-black font-bold">{{ item.pic?.name }}</span></span>
                                            <span>•</span>
                                            <span>Deadline: <span class="text-slate-400 print:text-black font-bold">{{ formatDate(item.due_date) }}</span></span>
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-2 sm:mt-0">
                                    <span 
                                        class="px-2.5 py-0.5 border text-[9px] font-black rounded uppercase tracking-wider"
                                        :class="item.status === 'completed' 
                                            ? 'text-emerald-400 border-emerald-400/20' 
                                            : (item.status === 'in_progress' 
                                                ? 'text-amber-400 border-amber-400/20' 
                                                : 'text-slate-400 border-slate-500/20')"
                                    >
                                        {{ item.status }}
                                    </span>
                                </div>
                            </div>

                            <div v-if="!meeting.action_items || meeting.action_items.length === 0" class="text-center py-8 text-slate-500 italic text-xs">
                                Tidak ada penugasan hasil rapat dalam notulen ini.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Professional Print-Only Layout -->
            <div class="hidden print:block text-black bg-white font-sans p-2 text-xs">
                <!-- Header -->
                <div class="border-b border-black pb-4 mb-4">
                    <table class="w-full border-collapse">
                        <tr>
                            <td class="w-[60%] align-top text-left">
                                <div class="flex items-start gap-4">
                                    <img src="/images/jri-official-logo.png" alt="logo" class="h-14 object-contain inline-block mr-3" />
                                    <div class="inline-block align-top">
                                        <div class="text-[#E21E26] font-extrabold italic text-2xl leading-none">jidoka</div>
                                        <div class="text-[#003680] font-black text-xs mt-0.5">PT. JIDOKA RESULT INDONESIA</div>
                                        <div class="text-[9px] text-slate-800 leading-tight mt-1">
                                            Kawasan Industri JABABEKA I, Jl. Jababeka II Blok C No. 19 L<br>
                                            Cikarang Utara, Bekasi 17530 Jawa Barat<br>
                                            Telp : +62 21 89383915
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="w-[40%] align-top text-right">
                                <div class="inline-block text-left">
                                    <div class="text-lg font-black text-emerald-800 italic leading-none">MINUTES OF MEETING</div>
                                    <div class="text-[8px] font-bold text-slate-500 mt-1 mb-2">(BERITA ACARA & NOTULEN RAPAT)</div>
                                    <table class="text-[9px] leading-tight w-full mt-1">
                                        <tr>
                                            <td class="font-bold pr-2 py-0.5">No. Dokumen</td>
                                            <td class="px-1 py-0.5">:</td>
                                            <td class="font-bold py-0.5">MOM-{{ meeting.id }}-{{ new Date(meeting.meeting_date).getFullYear() }}</td>
                                        </tr>
                                        <tr>
                                            <td class="font-bold pr-2 py-0.5">Tanggal Rapat</td>
                                            <td class="px-1 py-0.5">:</td>
                                            <td class="py-0.5">{{ formatDate(meeting.meeting_date) }}</td>
                                        </tr>
                                        <tr>
                                            <td class="font-bold pr-2 py-0.5">Status</td>
                                            <td class="px-1 py-0.5">:</td>
                                            <td class="font-bold py-0.5 uppercase">{{ meeting.status }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Meeting Subject / Title -->
                <div class="text-center my-4">
                    <h1 class="text-base font-extrabold uppercase border border-black bg-slate-100/50 py-2 tracking-wide text-black">
                        {{ meeting.title }}
                    </h1>
                </div>

                <!-- Summary & Attendance side-by-side -->
                <table class="w-full border-collapse mt-4">
                    <tr>
                        <td class="w-[50%] align-top pr-4">
                            <div class="font-bold uppercase tracking-wider text-[9px] bg-slate-100 border border-black px-2 py-1 mb-2">
                                Ringkasan Rapat
                            </div>
                            <table class="w-full text-xs leading-normal">
                                <tr class="border-b border-slate-200">
                                    <td class="py-1.5 font-bold text-slate-600 w-[35%]">Tanggal Rapat</td>
                                    <td class="py-1.5 text-right font-semibold">{{ formatDate(meeting.meeting_date) }}</td>
                                </tr>
                                <tr class="border-b border-slate-200">
                                    <td class="py-1.5 font-bold text-slate-600">Waktu</td>
                                    <td class="py-1.5 text-right font-semibold">{{ formatTime(meeting.start_time) }} - {{ formatTime(meeting.end_time) }}</td>
                                </tr>
                                <tr class="border-b border-slate-200">
                                    <td class="py-1.5 font-bold text-slate-600">Lokasi / Ruang</td>
                                    <td class="py-1.5 text-right font-semibold">{{ meeting.location }}</td>
                                </tr>
                                <tr class="border-b border-slate-200">
                                    <td class="py-1.5 font-bold text-slate-600">Tipe</td>
                                    <td class="py-1.5 text-right font-semibold uppercase">{{ meeting.type }}</td>
                                </tr>
                                <tr class="border-b border-slate-200">
                                    <td class="py-1.5 font-bold text-slate-600">Pimpinan (PIC)</td>
                                    <td class="py-1.5 text-right font-semibold">{{ meeting.chairperson?.name }}</td>
                                </tr>
                                <tr class="border-b border-slate-200">
                                    <td class="py-1.5 font-bold text-slate-600">Notulis</td>
                                    <td class="py-1.5 text-right font-semibold">{{ meeting.secretary?.name }}</td>
                                </tr>
                            </table>
                        </td>
                        <td class="w-[50%] align-top pl-4 border-l border-slate-300">
                            <div class="font-bold uppercase tracking-wider text-[9px] bg-slate-100 border border-black px-2 py-1 mb-2">
                                Kehadiran Peserta
                            </div>
                            <table class="w-full text-xs leading-normal">
                                <thead>
                                    <tr class="border-b border-slate-300 text-slate-600">
                                        <th class="py-1.5 text-left font-bold">Nama Peserta</th>
                                        <th class="py-1.5 text-left font-bold">Tipe</th>
                                        <th class="py-1.5 text-right font-bold">Kehadiran</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="att in meeting.attendees" :key="att.id" class="border-b border-slate-200">
                                        <td class="py-1.5 font-semibold text-slate-800">{{ att.user ? att.user.name : att.guest_name }}</td>
                                        <td class="py-1.5 text-slate-500">{{ att.user ? 'Internal' : 'Eksternal' }}</td>
                                        <td class="py-1.5 text-right font-bold" :class="att.status === 'present' ? 'text-emerald-700' : (att.status === 'excused' ? 'text-amber-700' : 'text-rose-700')">
                                            {{ getAttendeeStatusLabel(att.status) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </table>

                <!-- Discussion Notes -->
                <div class="mt-6">
                    <div class="font-bold uppercase tracking-wider text-[9px] bg-slate-100 border border-black px-2 py-1 mb-2">
                        Hasil Pembahasan Rapat (Discussion Notes)
                    </div>
                    <div class="border border-black p-3 bg-white text-xs whitespace-pre-line leading-relaxed min-h-[120px]">
                        {{ meeting.discussion_notes || 'Belum ada catatan rapat / notulensi lengkap.' }}
                    </div>
                </div>

                <!-- Action Items Checklist -->
                <div class="mt-6">
                    <div class="font-bold uppercase tracking-wider text-[9px] bg-slate-100 border border-black px-2 py-1 mb-2">
                        Penugasan Hasil Rapat (Action Items Checklist)
                    </div>
                    <table class="w-full text-xs border-collapse border border-black">
                        <thead>
                            <tr class="bg-slate-50">
                                <th class="border border-black px-2 py-1 text-center w-[5%]">No</th>
                                <th class="border border-black px-2 py-1 text-left w-[55%]">Deskripsi Penugasan</th>
                                <th class="border border-black px-2 py-1 text-left w-[20%]">Penanggung Jawab (PIC)</th>
                                <th class="border border-black px-2 py-1 text-center w-[12%]">Target Selesai</th>
                                <th class="border border-black px-2 py-1 text-center w-[8%]">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, idx) in meeting.action_items" :key="item.id">
                                <td class="border border-black px-2 py-1 text-center">{{ idx + 1 }}</td>
                                <td class="border border-black px-2 py-1">{{ item.description }}</td>
                                <td class="border border-black px-2 py-1 font-semibold">{{ item.pic?.name || '-' }}</td>
                                <td class="border border-black px-2 py-1 text-center">{{ formatDate(item.due_date) }}</td>
                                <td class="border border-black px-2 py-1 text-center font-bold uppercase text-[9px]">{{ item.status }}</td>
                            </tr>
                            <tr v-if="!meeting.action_items || meeting.action_items.length === 0">
                                <td colspan="5" class="border border-black px-2 py-4 text-center text-slate-500 italic">
                                    Tidak ada penugasan hasil rapat dalam notulen ini.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Signatures & Verification -->
                <table class="w-full mt-6 border-collapse">
                    <tr>
                        <!-- Left: Chairperson -->
                        <td class="w-[33%] text-center align-top">
                            <div class="text-xs font-semibold text-slate-700 mb-1">Pimpinan Rapat (Chairperson),</div>
                            <div class="border border-black h-20 w-44 mx-auto my-2 flex items-center justify-center relative overflow-hidden bg-white">
                                <img v-if="meeting.status === 'locked' && meeting.chairperson_signature" :src="meeting.chairperson_signature" alt="Signature" class="h-full object-contain" />
                                <div v-else class="text-slate-350 italic text-[10px] pt-10">Belum Ditandatangani</div>
                            </div>
                            <div class="text-xs font-bold">{{ meeting.chairperson?.name }}</div>
                            <div v-if="meeting.status === 'locked' && meeting.approved_at" class="text-[9px] text-slate-500">Tgl: {{ formatDate(meeting.approved_at) }}</div>
                        </td>
                        
                        <!-- Center: Security Seal / Verification QR -->
                        <td class="w-[34%] text-center align-top px-4">
                            <div v-if="meeting.status === 'locked'" class="border border-dashed border-slate-400 bg-slate-50 p-2 text-left w-full rounded-md mt-2">
                                <div class="flex items-center gap-2">
                                    <ShieldCheckIcon class="h-6 w-6 text-emerald-700 shrink-0" />
                                    <div>
                                        <div class="text-[9px] font-black text-emerald-800 tracking-wider uppercase">
                                            VERIFIED & LOCKED
                                        </div>
                                        <div class="text-[7px] text-slate-600 font-mono leading-tight uppercase mt-0.5">
                                            ID: SEC-MOM-{{ meeting.id }}<br/>
                                            HASH: {{ meeting.signature_hash ? meeting.signature_hash.substring(0, 16) + '...' : '-' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="border border-dashed border-slate-300 p-4 rounded-md inline-block text-slate-450 italic text-[10px] mt-2">
                                Draft Notulen / Belum Dikunci
                            </div>
                        </td>

                        <!-- Right: Secretary -->
                        <td class="w-[33%] text-center align-top">
                            <div class="text-xs font-semibold text-slate-700 mb-1">Notulis (Secretary),</div>
                            <div class="border border-black h-20 w-44 mx-auto my-2 flex items-center justify-center relative overflow-hidden bg-white">
                                <div class="w-full border-b border-dashed border-slate-400 mt-12 mx-4"></div>
                            </div>
                            <div class="text-xs font-bold">{{ meeting.secretary?.name }}</div>
                            <div class="text-[9px] text-slate-500">Dicatat oleh Notulis</div>
                        </td>
                    </tr>
                </table>

                <!-- Footer info -->
                <div class="mt-8 pt-2 border-t border-slate-300 text-center text-[8px] text-slate-500">
                    ERP JIDOKA SYSTEM | Doc ID: MOM-{{ meeting.id }} | Status: {{ meeting.status.toUpperCase() }}
                </div>
            </div>
        </div>
        <div v-else class="text-slate-900 dark:text-white text-center py-20 font-bold">
            Memuat data notulen rapat...
        </div>

        <!-- QR Absensi Modal Overlay -->
        <div v-if="showQrModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-sm">
            <div class="relative w-full max-w-sm bg-[#0c0517] border border-purple-500/30 rounded-2xl p-6 shadow-2xl shadow-purple-500/20 text-center space-y-4">
                <div class="flex justify-between items-center border-b border-purple-500/20 pb-3">
                    <h3 class="text-xs font-black tracking-widest text-white flex items-center gap-2">
                        <QrCodeIcon class="h-4 w-4 text-purple-400" />
                        ABSENSI QR CODE
                    </h3>
                    <button @click="showQrModal = false" class="text-slate-400 hover:text-white transition-colors text-xs font-bold">[ TUTUP ]</button>
                </div>

                <div class="text-[10px] text-slate-400 leading-relaxed bg-[#030108] p-3 rounded-lg border border-purple-500/10">
                    Arahkan kamera smartphone Anda ke QR Code di bawah untuk masuk ke halaman check-in kehadiran rapat mandiri.
                </div>

                <!-- QR Display Container -->
                <div class="bg-white p-4 rounded-2xl inline-block border border-purple-500/20 shadow-lg shadow-purple-500/10 mx-auto">
                    <QrcodeVue :value="checkInUrl" :size="200" level="H" />
                </div>

                <!-- URL link for copy -->
                <div class="space-y-1">
                    <label class="block text-[8px] font-bold text-slate-500 uppercase tracking-widest">Link Check-In</label>
                    <div class="bg-[#030108] p-2 rounded-lg border border-purple-500/10 text-[9px] text-purple-350 select-all break-all select-text font-bold">
                        {{ checkInUrl }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Signature Pad Modal Overlay -->
        <div v-if="showSignModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-sm">
            <div class="relative w-full max-w-md bg-[#0c0517] border border-purple-500/30 rounded-2xl p-6 shadow-2xl shadow-purple-500/20 text-center space-y-4">
                <div class="flex justify-between items-center border-b border-purple-500/20 pb-3">
                    <h3 class="text-xs font-black tracking-widest text-white flex items-center gap-2">
                        <ShieldCheckIcon class="h-5 w-5 text-purple-400 animate-pulse" />
                        TANDA TANGAN DIGITAL
                    </h3>
                    <button @click="showSignModal = false" class="text-slate-450 hover:text-white transition-colors text-xs font-bold">[ TUTUP ]</button>
                </div>

                <div class="text-[10px] text-slate-400 leading-relaxed bg-[#030108] p-3 rounded-lg border border-purple-500/10">
                    Gunakan mouse Anda atau layar sentuh untuk menggambar tanda tangan Anda pada bidang putih di bawah. Rapat akan dikunci secara permanen setelah ditandatangani.
                </div>

                <!-- Canvas signature pad -->
                <div class="border border-purple-500/20 rounded-xl overflow-hidden bg-white h-48 relative">
                    <canvas 
                        ref="canvasRef" 
                        width="380" 
                        height="190" 
                        class="cursor-crosshair w-full h-full"
                        @mousedown="handleStartDrawing"
                        @mousemove="draw"
                        @mouseup="handleStopDrawing"
                        @mouseleave="handleStopDrawing"
                        @touchstart.prevent="handleStartDrawing"
                        @touchmove.prevent="draw"
                        @touchend.prevent="handleStopDrawing"
                    ></canvas>
                </div>

                <div class="flex justify-between gap-3 pt-2">
                    <button 
                        type="button" 
                        @click="clearCanvas" 
                        class="px-5 py-2.5 rounded-xl bg-[#030108] border border-purple-500/10 text-xs font-bold text-slate-400 hover:bg-[#160c29] hover:text-white transition-all"
                    >
                        Bersihkan
                    </button>
                    <div class="flex gap-2">
                        <button 
                            type="button" 
                            @click="showSignModal = false" 
                            class="px-5 py-2.5 rounded-xl bg-[#030108] border border-purple-500/10 text-xs font-bold text-slate-400 hover:bg-[#160c29] hover:text-white transition-all"
                        >
                            Batal
                        </button>
                        <button 
                            type="button" 
                            @click="submitSignature" 
                            class="px-6 py-2.5 rounded-xl bg-purple-650 hover:bg-purple-600 text-xs font-bold text-white shadow-lg shadow-purple-500/20 active:scale-95 transition-all"
                        >
                            Tandatangani & Kunci
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
@media print {
    @page {
        size: A4 portrait;
        margin: 0.4cm 0.4cm;
    }
}
</style>

<style scoped>
.glow-text-purple {
    text-shadow: 0 0 10px rgba(168, 85, 247, 0.5);
}

@media print {
    .print\:bg-white {
        background-color: white !important;
    }
    .print\:text-black {
        color: black !important;
    }
    .print\:border-slate-300 {
        border-color: #cbd5e1 !important;
    }
}
</style>
