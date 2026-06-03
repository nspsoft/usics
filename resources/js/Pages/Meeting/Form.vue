<script setup>
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';
import {
    ArrowLeftIcon,
    TrashIcon,
    PlusIcon,
    UserIcon,
    UserGroupIcon,
    ClipboardDocumentListIcon,
    SparklesIcon,
    MicrophoneIcon,
    ArrowUpTrayIcon,
    DocumentTextIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    users: Array,
    meeting: Object, // Optional, for edit mode
});

const isEdit = computed(() => !!props.meeting);

const form = useForm({
    title: props.meeting?.title || '',
    meeting_date: props.meeting?.meeting_date || new Date().toISOString().split('T')[0],
    start_time: props.meeting?.start_time ? props.meeting.start_time.substring(0, 5) : '09:00',
    end_time: props.meeting?.end_time ? props.meeting.end_time.substring(0, 5) : '10:00',
    location: props.meeting?.location || '',
    type: props.meeting?.type || 'internal',
    chairperson_id: props.meeting?.chairperson_id || '',
    secretary_id: props.meeting?.secretary_id || '',
    discussion_notes: props.meeting?.discussion_notes || '',
    status: props.meeting?.status || 'draft',
    
    // Attendees
    attendees: props.meeting?.attendees?.map(att => ({
        user_id: att.user_id || '',
        guest_name: att.guest_name || '',
        status: att.status || 'present',
        is_guest: !att.user_id,
    })) || [
        { user_id: '', guest_name: '', status: 'present', is_guest: false }
    ],

    // Action Items
    action_items: props.meeting?.action_items?.map(item => ({
        id: item.id,
        description: item.description,
        pic_id: item.pic_id,
        due_date: item.due_date,
        status: item.status,
    })) || [],
});

// AI Assistant state
const showAiModal = ref(false);
const activeTab = ref('text'); // 'text' | 'speech' | 'file'
const rawNotes = ref('');
const isProcessingAi = ref(false);
const aiError = ref('');

const processWithAi = async () => {
    if (!rawNotes.value.trim()) return;
    
    isProcessingAi.value = true;
    aiError.value = '';
    
    try {
        const response = await axios.post(route('meeting-command.ai-process'), {
            raw_notes: rawNotes.value
        });
        
        if (response.data) {
            const data = response.data;
            if (data.title) form.title = data.title;
            if (data.discussion_notes) form.discussion_notes = data.discussion_notes;
            
            if (data.action_items && Array.isArray(data.action_items)) {
                form.action_items = data.action_items.map(item => ({
                    description: item.description || '',
                    pic_id: item.pic_id ? String(item.pic_id) : '',
                    due_date: item.due_date || new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
                    status: 'pending'
                }));
            }
            
            showAiModal.value = false;
            rawNotes.value = '';
        }
    } catch (err) {
        console.error('Error processing with AI:', err);
        aiError.value = err.response?.data?.error || err.response?.data?.message || 'Gagal memproses notulensi dengan AI. Pastikan layanan AI aktif dan cobalah beberapa saat lagi.';
    } finally {
        isProcessingAi.value = false;
    }
};

// Speech Recognition (Web Speech API)
const isRecording = ref(false);
let recognition = null;

const startSpeechRecognition = () => {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRecognition) {
        aiError.value = 'Browser Anda tidak mendukung Speech Recognition. Silakan gunakan Google Chrome atau Microsoft Edge.';
        return;
    }

    try {
        recognition = new SpeechRecognition();
        recognition.continuous = true;
        recognition.interimResults = true;
        recognition.lang = 'id-ID'; // Indonesian

        recognition.onstart = () => {
            isRecording.value = true;
            aiError.value = '';
        };

        recognition.onresult = (event) => {
            let finalTranscript = '';
            for (let i = event.resultIndex; i < event.results.length; ++i) {
                if (event.results[i].isFinal) {
                    finalTranscript += event.results[i][0].transcript + ' ';
                }
            }
            if (finalTranscript) {
                rawNotes.value += finalTranscript;
            }
        };

        recognition.onerror = (event) => {
            console.error('Speech recognition error:', event.error);
            if (event.error !== 'no-speech') {
                aiError.value = `Perekaman suara terganggu: ${event.error}`;
                isRecording.value = false;
            }
        };

        recognition.onend = () => {
            isRecording.value = false;
        };

        recognition.start();
    } catch (e) {
        console.error('Speech recognition start failed:', e);
        aiError.value = 'Gagal mengakses mikrofon atau memulai perekaman.';
        isRecording.value = false;
    }
};

const stopSpeechRecognition = () => {
    if (recognition) {
        recognition.stop();
    }
};

// Audio File Upload
const audioFile = ref(null);
const audioFileName = ref('');

const onFileChange = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    // Validate size (15MB)
    if (file.size > 15 * 1024 * 1024) {
        aiError.value = 'Ukuran berkas audio terlalu besar. Maksimal 15MB.';
        audioFile.value = null;
        audioFileName.value = '';
        return;
    }

    audioFile.value = file;
    audioFileName.value = file.name;
    aiError.value = '';
};

const processAudioWithAi = async () => {
    if (!audioFile.value) return;

    isProcessingAi.value = true;
    aiError.value = '';

    const formData = new FormData();
    formData.append('audio_file', audioFile.value);

    try {
        const response = await axios.post(route('meeting-command.ai-process-audio'), formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });

        if (response.data) {
            const data = response.data;
            if (data.title) form.title = data.title;
            if (data.discussion_notes) form.discussion_notes = data.discussion_notes;

            if (data.action_items && Array.isArray(data.action_items)) {
                form.action_items = data.action_items.map(item => ({
                    description: item.description || '',
                    pic_id: item.pic_id ? String(item.pic_id) : '',
                    due_date: item.due_date || new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
                    status: 'pending'
                }));
            }

            showAiModal.value = false;
            audioFile.value = null;
            audioFileName.value = '';
        }
    } catch (err) {
        console.error('Error processing audio with AI:', err);
        aiError.value = err.response?.data?.error || err.response?.data?.message || 'Gagal memproses berkas audio dengan AI. Pastikan berkas valid dan kunci API terkonfigurasi dengan benar.';
    } finally {
        isProcessingAi.value = false;
    }
};

const closeAiModal = () => {
    stopSpeechRecognition();
    showAiModal.value = false;
    audioFile.value = null;
    audioFileName.value = '';
    aiError.value = '';
};

const addAttendee = (isGuest = false) => {
    form.attendees.push({
        user_id: '',
        guest_name: '',
        status: 'present',
        is_guest: isGuest,
    });
};

const removeAttendee = (index) => {
    form.attendees.splice(index, 1);
};

const addActionItem = () => {
    form.action_items.push({
        description: '',
        pic_id: '',
        due_date: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0], // 7 days later
        status: 'pending',
    });
};

const removeActionItem = (index) => {
    form.action_items.splice(index, 1);
};

const submit = () => {
    // Basic formatting cleanups before sending
    const cleanedAttendees = form.attendees.map(att => {
        if (att.is_guest) {
            return { user_id: null, guest_name: att.guest_name, status: att.status };
        } else {
            return { user_id: att.user_id, guest_name: null, status: att.status };
        }
    });

    const dataToSend = {
        ...form,
        attendees: cleanedAttendees,
    };

    if (isEdit.value) {
        form.transform(() => dataToSend).put(route('meeting-command.update', props.meeting.id));
    } else {
        form.transform(() => dataToSend).post(route('meeting-command.store'));
    }
};
</script>

<template>
    <Head :title="isEdit ? 'Ubah Rapat' : 'Buat Rapat Baru'" />
    
    <AppLayout title="Meeting Command Hub" :render-header="false">
        <div class="min-h-screen bg-[#030108] p-6 font-mono text-slate-50 relative overflow-hidden">
            <!-- Background glow lines -->
            <div class="fixed inset-0 z-0 pointer-events-none">
                <div class="absolute inset-0 bg-gradient-to-b from-purple-950/10 to-[#030108]"></div>
                <div class="absolute top-[-10%] left-[30%] w-[500px] h-[500px] bg-purple-500/5 blur-[120px] rounded-full"></div>
            </div>

            <div class="relative z-10 max-w-7xl mx-auto space-y-6">
                <!-- Back Link -->
                <Link href="/meeting-command" class="inline-flex items-center gap-2 text-xs font-bold text-purple-400 hover:text-purple-300 transition-colors">
                    <ArrowLeftIcon class="h-4 w-4" /> KEMBALI KE DASHBOARD
                </Link>

                <!-- Page title & AI Assistant Button -->
                <div class="border-b border-purple-500/20 pb-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h1 class="text-2xl font-black tracking-widest text-white glow-text-purple uppercase">
                            {{ isEdit ? 'EDIT MEETING MINUTES' : 'RECORD NEW MEETING' }}
                        </h1>
                        <p class="text-[10px] text-slate-500 tracking-wider uppercase mt-1">Sistem pencatatan notulensi & penugasan PIC terpusat</p>
                    </div>
                    <button 
                        type="button" 
                        @click="showAiModal = true"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-fuchsia-600 to-purple-650 hover:from-fuchsia-500 hover:to-purple-550 text-xs font-black tracking-wider text-white shadow-lg shadow-purple-500/25 active:scale-95 transition-all border border-fuchsia-400/30"
                    >
                        <SparklesIcon class="h-4 w-4 text-fuchsia-205 animate-pulse" />
                        AI ASSISTANT NOTULEN
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                        <!-- Left Panel: General Info -->
                        <div class="lg:col-span-5 bg-[#0c0517]/85 border border-purple-500/15 rounded-2xl p-6 space-y-4 shadow-xl">
                            <h3 class="text-xs font-black uppercase tracking-widest text-purple-400 border-b border-purple-500/10 pb-2 flex items-center gap-2">
                                <UserIcon class="h-4 w-4" /> Informasi Rapat
                            </h3>

                            <div>
                                <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Agenda / Judul Rapat</label>
                                <input type="text" v-model="form.title" class="w-full bg-[#030108] border-0 ring-1 ring-purple-500/20 rounded-xl px-4 py-2.5 text-xs text-white focus:ring-2 focus:ring-purple-500" placeholder="Contoh: Rapat Koordinasi ATK Bulanan..." required />
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Tipe Rapat</label>
                                    <select v-model="form.type" class="w-full bg-[#030108] border-0 ring-1 ring-purple-500/20 rounded-xl px-4 py-2.5 text-xs text-white focus:ring-2 focus:ring-purple-500">
                                        <option value="internal">Internal</option>
                                        <option value="external">Eksternal</option>
                                        <option value="project">Proyek</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Tanggal</label>
                                    <input type="date" v-model="form.meeting_date" class="w-full bg-[#030108] border-0 ring-1 ring-purple-500/20 rounded-xl px-4 py-2.5 text-xs text-white focus:ring-2 focus:ring-purple-500" required />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Jam Mulai</label>
                                    <input type="time" v-model="form.start_time" class="w-full bg-[#030108] border-0 ring-1 ring-purple-500/20 rounded-xl px-4 py-2.5 text-xs text-white focus:ring-2 focus:ring-purple-500" required />
                                </div>
                                <div>
                                    <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Jam Selesai</label>
                                    <input type="time" v-model="form.end_time" class="w-full bg-[#030108] border-0 ring-1 ring-purple-500/20 rounded-xl px-4 py-2.5 text-xs text-white focus:ring-2 focus:ring-purple-500" required />
                                </div>
                            </div>

                            <div>
                                <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Tempat / Ruangan / Link</label>
                                <input type="text" v-model="form.location" class="w-full bg-[#030108] border-0 ring-1 ring-purple-500/20 rounded-xl px-4 py-2.5 text-xs text-white focus:ring-2 focus:ring-purple-500" placeholder="Ruang Rapat Utama / Zoom Link..." required />
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Pimpinan Rapat (PIC)</label>
                                    <select v-model="form.chairperson_id" class="w-full bg-[#030108] border-0 ring-1 ring-purple-500/20 rounded-xl px-4 py-2.5 text-xs text-white focus:ring-2 focus:ring-purple-500" required>
                                        <option value="">Pilih PIC</option>
                                        <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Notulis (Secretary)</label>
                                    <select v-model="form.secretary_id" class="w-full bg-[#030108] border-0 ring-1 ring-purple-500/20 rounded-xl px-4 py-2.5 text-xs text-white focus:ring-2 focus:ring-purple-500" required>
                                        <option value="">Pilih Notulis</option>
                                        <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Status Publikasi</label>
                                <select v-model="form.status" class="w-full bg-[#030108] border-0 ring-1 ring-purple-500/20 rounded-xl px-4 py-2.5 text-xs text-white focus:ring-2 focus:ring-purple-500">
                                    <option value="draft">Draft (Dapat diedit bebas)</option>
                                    <option value="published">Published (Dibagikan ke PIC)</option>
                                    <option value="locked">Locked (Arsip, tidak dapat diedit)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Right Panel: Attendees -->
                        <div class="lg:col-span-7 bg-[#0c0517]/85 border border-purple-500/15 rounded-2xl p-6 space-y-4 shadow-xl flex flex-col">
                            <div class="flex justify-between items-center border-b border-purple-500/10 pb-2">
                                <h3 class="text-xs font-black uppercase tracking-widest text-purple-400 flex items-center gap-2">
                                    <UserGroupIcon class="h-4 w-4" /> Peserta Rapat (Attendees)
                                </h3>
                                <div class="flex gap-2">
                                    <button type="button" @click="addAttendee(false)" class="text-[10px] font-bold text-purple-400 hover:text-purple-300 flex items-center gap-1">
                                        <PlusIcon class="h-3.5 w-3.5" /> + Anggota
                                    </button>
                                    <button type="button" @click="addAttendee(true)" class="text-[10px] font-bold text-sky-400 hover:text-sky-300 flex items-center gap-1">
                                        <PlusIcon class="h-3.5 w-3.5" /> + Tamu Luar
                                    </button>
                                </div>
                            </div>

                            <div class="space-y-3 flex-1 overflow-y-auto max-h-[360px] pr-2">
                                <div v-for="(att, idx) in form.attendees" :key="idx" class="grid grid-cols-12 gap-3 items-center bg-[#030108]/60 p-3 rounded-xl border border-purple-500/5 hover:border-purple-500/10 transition-colors">
                                    <div class="col-span-7">
                                        <label class="block text-[8px] font-bold text-slate-500 uppercase mb-1">
                                            {{ att.is_guest ? 'Nama Tamu Eksternal' : 'Karyawan Internal' }}
                                        </label>

                                        <select v-if="!att.is_guest" v-model="att.user_id" class="w-full bg-[#0c0517] border-0 ring-1 ring-purple-500/20 rounded-lg px-3 py-2 text-xs text-white focus:ring-1 focus:ring-purple-500" required>
                                            <option value="">Pilih Anggota</option>
                                            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }} ({{ u.email }})</option>
                                        </select>

                                        <input v-else type="text" v-model="att.guest_name" class="w-full bg-[#0c0517] border-0 ring-1 ring-sky-500/20 rounded-lg px-3 py-2 text-xs text-white focus:ring-1 focus:ring-sky-500" placeholder="Ketik nama tamu eksternal..." required />
                                    </div>

                                    <div class="col-span-4">
                                        <label class="block text-[8px] font-bold text-slate-500 uppercase mb-1">Kehadiran</label>
                                        <select v-model="att.status" class="w-full bg-[#0c0517] border-0 ring-1 ring-purple-500/20 rounded-lg px-3 py-2 text-xs text-white focus:ring-1 focus:ring-purple-500">
                                            <option value="present">Hadir</option>
                                            <option value="excused">Izin (Excused)</option>
                                            <option value="absent">Absen (Absent)</option>
                                        </select>
                                    </div>

                                    <div class="col-span-1 flex justify-end pt-4">
                                        <button type="button" @click="removeAttendee(idx)" class="p-2 text-slate-500 hover:text-rose-500 rounded-lg hover:bg-rose-500/10 transition-all">
                                            <TrashIcon class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>
                                <div v-if="form.attendees.length === 0" class="text-center py-10 text-slate-500 italic text-xs">
                                    Belum ada peserta rapat yang didaftarkan.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Discussion Notes Area -->
                    <div class="bg-[#0c0517]/85 border border-purple-500/15 rounded-2xl p-6 space-y-4 shadow-xl">
                        <h3 class="text-xs font-black uppercase tracking-widest text-purple-400 border-b border-purple-500/10 pb-2">
                            Pembahasan & Diskusi Rapat (Discussion Notes)
                        </h3>
                        <div>
                            <textarea v-model="form.discussion_notes" rows="6" class="w-full bg-[#030108] border-0 ring-1 ring-purple-500/20 rounded-xl p-4 text-xs text-white focus:ring-2 focus:ring-purple-500 font-mono" placeholder="Ketik notulensi lengkap di sini... Anda bisa memformat poin-poin menggunakan enter."></textarea>
                        </div>
                    </div>

                    <!-- Bottom Section: Action Items -->
                    <div class="bg-[#0c0517]/85 border border-purple-500/15 rounded-2xl p-6 space-y-4 shadow-xl">
                        <div class="flex justify-between items-center border-b border-purple-500/10 pb-2">
                            <h3 class="text-xs font-black uppercase tracking-widest text-purple-400 flex items-center gap-2">
                                <ClipboardDocumentListIcon class="h-4 w-4" /> Penugasan Hasil Rapat (Action Items)
                            </h3>
                            <button type="button" @click="addActionItem" class="text-xs font-bold text-purple-400 hover:text-purple-300 flex items-center gap-1">
                                <PlusIcon class="h-4 w-4" /> Tambah Task
                            </button>
                        </div>

                        <div class="space-y-3">
                            <div v-for="(item, idx) in form.action_items" :key="idx" class="grid grid-cols-12 gap-3 items-end bg-[#030108]/60 p-4 rounded-xl border border-purple-500/5 hover:border-purple-500/10 transition-colors">
                                <div class="col-span-12 sm:col-span-5">
                                    <label class="block text-[8px] font-bold text-slate-500 uppercase mb-1">Deskripsi Tugas / Keputusan</label>
                                    <input type="text" v-model="item.description" class="w-full bg-[#0c0517] border-0 ring-1 ring-purple-500/20 rounded-lg px-3 py-2 text-xs text-white focus:ring-1 focus:ring-purple-500" placeholder="Ketik tindak lanjut tugas..." required />
                                </div>

                                <div class="col-span-6 sm:col-span-3">
                                    <label class="block text-[8px] font-bold text-slate-500 uppercase mb-1">Penanggung Jawab (PIC)</label>
                                    <select v-model="item.pic_id" class="w-full bg-[#0c0517] border-0 ring-1 ring-purple-500/20 rounded-lg px-3 py-2 text-xs text-white focus:ring-1 focus:ring-purple-500" required>
                                        <option value="">Pilih PIC</option>
                                        <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                                    </select>
                                </div>

                                <div class="col-span-4 sm:col-span-2">
                                    <label class="block text-[8px] font-bold text-slate-500 uppercase mb-1">Deadline</label>
                                    <input type="date" v-model="item.due_date" class="w-full bg-[#0c0517] border-0 ring-1 ring-purple-500/20 rounded-lg px-3 py-2 text-xs text-white focus:ring-1 focus:ring-purple-500" required />
                                </div>

                                <div class="col-span-2 sm:col-span-1" v-if="isEdit">
                                    <label class="block text-[8px] font-bold text-slate-500 uppercase mb-1">Status</label>
                                    <select v-model="item.status" class="w-full bg-[#0c0517] border-0 ring-1 ring-purple-500/20 rounded-lg px-2 py-2 text-xs text-white focus:ring-1 focus:ring-purple-500">
                                        <option value="pending">Pending</option>
                                        <option value="in_progress">Progress</option>
                                        <option value="completed">Done</option>
                                    </select>
                                </div>

                                <div class="col-span-2 sm:col-span-1 flex justify-end">
                                    <button type="button" @click="removeActionItem(idx)" class="p-2 text-slate-500 hover:text-rose-500 rounded-lg hover:bg-rose-500/10 transition-all">
                                        <TrashIcon class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>

                            <div v-if="form.action_items.length === 0" class="text-center py-8 text-slate-500 italic text-xs">
                                Tidak ada penugasan hasil rapat / Action Items yang diinisiasi.
                            </div>
                        </div>
                    </div>

                    <!-- Submission buttons -->
                    <div class="flex justify-end gap-3 pb-8">
                        <Link href="/meeting-command" class="px-6 py-2.5 rounded-xl bg-[#0c0517] text-xs font-bold text-slate-400 hover:bg-[#160c29] hover:text-white transition-all border border-purple-500/10">Batal</Link>
                        <button type="submit" class="px-8 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-xs font-bold text-white shadow-lg shadow-purple-500/20 active:scale-95 transition-all" :disabled="form.processing">
                            {{ form.processing ? 'Menyimpan...' : (isEdit ? 'Simpan Perubahan' : 'Publish Rapat & Kirim Notulensi') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- AI Assistant Modal Overlay -->
        <div v-if="showAiModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-sm">
            <div class="relative w-full max-w-2xl bg-[#0c0517] border border-purple-500/30 rounded-2xl p-6 shadow-2xl shadow-purple-500/20 max-h-[95vh] flex flex-col">
                <div class="relative z-10 flex flex-col h-full space-y-4">
                    <!-- Modal Header -->
                    <div class="flex justify-between items-center border-b border-purple-500/20 pb-3">
                        <h3 class="text-xs font-black tracking-widest text-white flex items-center gap-2">
                            <SparklesIcon class="h-5 w-5 text-purple-400 animate-pulse" />
                            AI MEETING NOTULEN ASSISTANT
                        </h3>
                        <button 
                            type="button" 
                            @click="closeAiModal" 
                            class="text-slate-400 hover:text-white transition-colors text-xs font-bold"
                            :disabled="isProcessingAi"
                        >
                            [ BATAL ]
                        </button>
                    </div>

                    <!-- Cyberpunk Tabs -->
                    <div class="grid grid-cols-3 gap-2 border-b border-purple-500/10 pb-3">
                        <button 
                            type="button"
                            @click="activeTab = 'text'"
                            class="px-3 py-2 text-center text-[10px] font-black uppercase tracking-wider rounded-lg border transition-all"
                            :class="activeTab === 'text' 
                                ? 'bg-purple-950/40 text-purple-400 border-purple-500/30 glow-text-purple' 
                                : 'bg-[#030108] text-slate-500 border-purple-500/5 hover:text-slate-350'"
                            :disabled="isRecording || isProcessingAi"
                        >
                            <span class="flex items-center justify-center gap-1.5">
                                <DocumentTextIcon class="h-3.5 w-3.5" />
                                Teks Mentah
                            </span>
                        </button>
                        <button 
                            type="button"
                            @click="activeTab = 'speech'"
                            class="px-3 py-2 text-center text-[10px] font-black uppercase tracking-wider rounded-lg border transition-all"
                            :class="activeTab === 'speech' 
                                ? 'bg-purple-950/40 text-purple-400 border-purple-500/30 glow-text-purple' 
                                : 'bg-[#030108] text-slate-500 border-purple-500/5 hover:text-slate-350'"
                            :disabled="isProcessingAi"
                        >
                            <span class="flex items-center justify-center gap-1.5">
                                <MicrophoneIcon class="h-3.5 w-3.5" />
                                Rekam Suara
                            </span>
                        </button>
                        <button 
                            type="button"
                            @click="activeTab = 'file'"
                            class="px-3 py-2 text-center text-[10px] font-black uppercase tracking-wider rounded-lg border transition-all"
                            :class="activeTab === 'file' 
                                ? 'bg-purple-950/40 text-purple-400 border-purple-500/30 glow-text-purple' 
                                : 'bg-[#030108] text-slate-500 border-purple-500/5 hover:text-slate-350'"
                            :disabled="isRecording || isProcessingAi"
                        >
                            <span class="flex items-center justify-center gap-1.5">
                                <ArrowUpTrayIcon class="h-3.5 w-3.5" />
                                Berkas Audio
                            </span>
                        </button>
                    </div>

                    <!-- TAB Content: TEXT PASTE -->
                    <div v-if="activeTab === 'text'" class="flex-grow flex flex-col space-y-4">
                        <div class="text-[10px] text-slate-400 leading-relaxed bg-[#030108] p-3 rounded-xl border border-purple-500/10">
                            <p class="font-bold text-purple-350 mb-1">💡 SALIN TEKS NOTULEN RAPAT:</p>
                            Tempelkan teks kasar, poin rapat, atau transkrip di bawah. AI akan memformat menjadi judul, notulen terperinci, dan mendeteksi penugasan PIC otomatis.
                        </div>

                        <div class="flex-grow min-h-[200px] flex flex-col">
                            <textarea 
                                v-model="rawNotes" 
                                class="w-full flex-grow bg-[#030108] border-0 ring-1 ring-purple-500/20 rounded-xl p-4 text-xs text-white focus:ring-2 focus:ring-purple-500 font-mono resize-none min-h-[180px]" 
                                placeholder="Tempelkan hasil transkrip rapat atau catatan di sini..."
                                :disabled="isProcessingAi"
                            ></textarea>
                        </div>

                        <!-- Error Alert -->
                        <div v-if="aiError" class="p-3 bg-rose-500/10 border border-rose-500/20 text-rose-450 rounded-xl text-[11px] font-bold font-mono">
                            {{ aiError }}
                        </div>

                        <div class="flex justify-end gap-3 pt-2">
                            <button 
                                type="button" 
                                @click="closeAiModal" 
                                class="px-5 py-2.5 rounded-xl bg-[#030108] border border-purple-500/10 text-xs font-bold text-slate-400 hover:bg-[#160c29] hover:text-white transition-all"
                                :disabled="isProcessingAi"
                            >
                                Batal
                            </button>
                            <button 
                                type="button" 
                                @click="processWithAi" 
                                class="px-6 py-2.5 rounded-xl bg-purple-650 hover:bg-purple-600 text-xs font-bold text-white shadow-lg shadow-purple-500/20 active:scale-95 transition-all flex items-center gap-2 disabled:opacity-50 disabled:pointer-events-none"
                                :disabled="isProcessingAi || !rawNotes.trim()"
                            >
                                <span v-if="isProcessingAi" class="inline-block w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                <span>{{ isProcessingAi ? 'Menganalisis Rapat...' : 'Proses dengan AI' }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- TAB Content: MICROPHONE RECORDING -->
                    <div v-if="activeTab === 'speech'" class="flex-grow flex flex-col space-y-4">
                        <div class="text-[10px] text-slate-400 leading-relaxed bg-[#030108] p-3 rounded-xl border border-purple-500/10">
                            <p class="font-bold text-purple-350 mb-1">🎤 DIKTASI SUARA REAL-TIME (SPEECH-TO-TEXT):</p>
                            Bicaralah langsung melalui mikrofon komputer Anda. Sistem akan mentranskripsikan ucapan Anda secara real-time ke kolom teks di bawah. Setelah selesai, klik proses untuk merumuskan berita acara MoM.
                        </div>

                        <!-- Live Visualizer / Control Area -->
                        <div class="bg-[#030108] p-6 rounded-xl border border-purple-500/10 flex flex-col items-center justify-center space-y-4 min-h-[140px] relative overflow-hidden">
                            <!-- Cyber Soundwave Animation -->
                            <div v-if="isRecording" class="flex gap-1 justify-center items-center h-8 mb-2">
                                <div class="w-1 bg-purple-500 rounded-full animate-wave h-4 duration-300"></div>
                                <div class="w-1 bg-fuchsia-500 rounded-full animate-wave h-8 duration-200"></div>
                                <div class="w-1 bg-purple-400 rounded-full animate-wave h-6 duration-150"></div>
                                <div class="w-1 bg-fuchsia-400 rounded-full animate-wave h-3 duration-400"></div>
                                <div class="w-1 bg-purple-500 rounded-full animate-wave h-7 duration-250"></div>
                                <div class="w-1 bg-[#a855f7] rounded-full animate-wave h-5 duration-350"></div>
                            </div>
                            
                            <div v-else class="text-[10px] text-slate-500 tracking-wider font-bold">
                                MIKROFON SIAP DIGUNAKAN
                            </div>

                            <button 
                                type="button"
                                @click="isRecording ? stopSpeechRecognition() : startSpeechRecognition()"
                                class="h-16 w-16 rounded-full flex items-center justify-center border shadow-lg transition-all active:scale-95 duration-300"
                                :class="isRecording 
                                    ? 'bg-rose-600 hover:bg-rose-500 border-rose-400/40 text-white animate-pulse' 
                                    : 'bg-purple-950/60 hover:bg-purple-900 border-purple-500/30 text-purple-400'"
                            >
                                <span v-if="isRecording" class="h-6 w-6 rounded-sm bg-white shrink-0"></span>
                                <MicrophoneIcon v-else class="h-7 w-7" />
                            </button>

                            <div class="text-[9px] font-bold text-center uppercase tracking-widest text-slate-400">
                                {{ isRecording ? 'Sedang merekam... Klik untuk berhenti' : 'Klik tombol di atas untuk mulai merekam' }}
                            </div>
                        </div>

                        <!-- Live Text Box -->
                        <div class="flex-grow min-h-[140px] flex flex-col">
                            <label class="block text-[8px] font-bold text-slate-500 uppercase mb-1">Hasil Transkrip Ucapan</label>
                            <textarea 
                                v-model="rawNotes" 
                                class="w-full flex-grow bg-[#030108] border-0 ring-1 ring-purple-500/20 rounded-xl p-4 text-xs text-white focus:ring-2 focus:ring-purple-500 font-mono resize-none min-h-[120px]" 
                                placeholder="Ucapan rapat Anda akan muncul otomatis di sini..."
                                :disabled="isProcessingAi"
                            ></textarea>
                        </div>

                        <!-- Error Alert -->
                        <div v-if="aiError" class="p-3 bg-rose-500/10 border border-rose-500/20 text-rose-450 rounded-xl text-[11px] font-bold font-mono">
                            {{ aiError }}
                        </div>

                        <div class="flex justify-end gap-3 pt-2">
                            <button 
                                type="button" 
                                @click="closeAiModal" 
                                class="px-5 py-2.5 rounded-xl bg-[#030108] border border-purple-500/10 text-xs font-bold text-slate-400 hover:bg-[#160c29] hover:text-white transition-all"
                                :disabled="isProcessingAi"
                            >
                                Batal
                            </button>
                            <button 
                                type="button" 
                                @click="processWithAi" 
                                class="px-6 py-2.5 rounded-xl bg-purple-650 hover:bg-purple-600 text-xs font-bold text-white shadow-lg shadow-purple-500/20 active:scale-95 transition-all flex items-center gap-2 disabled:opacity-50 disabled:pointer-events-none"
                                :disabled="isProcessingAi || isRecording || !rawNotes.trim()"
                            >
                                <span v-if="isProcessingAi" class="inline-block w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                <span>{{ isProcessingAi ? 'Menganalisis Suara...' : 'Proses Notulensi Rapat' }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- TAB Content: AUDIO FILE UPLOAD -->
                    <div v-if="activeTab === 'file'" class="flex-grow flex flex-col space-y-4">
                        <div class="text-[10px] text-slate-400 leading-relaxed bg-[#030108] p-3 rounded-xl border border-purple-500/10">
                            <p class="font-bold text-purple-350 mb-1">📂 PROSES BERKAS AUDIO RAPAT:</p>
                            Unggah rekaman suara rapat Anda (Maksimal 15MB. Format yang didukung: `.mp3`, `.wav`, `.m4a`). Model multimodal AI akan langsung 'mendengarkan' file audio tersebut, merangkumnya, serta mengidentifikasi tugas PIC.
                        </div>

                        <!-- Drag and Drop Dropzone -->
                        <div class="flex-grow min-h-[180px] bg-[#030108] border-2 border-dashed border-purple-500/20 rounded-xl hover:border-purple-500/40 transition-colors flex flex-col items-center justify-center p-6 relative">
                            <input 
                                type="file" 
                                id="audio-upload-input" 
                                accept="audio/mp3,audio/mpeg,audio/wav,audio/m4a,audio/x-m4a,audio/ogg,video/mp4" 
                                class="absolute inset-0 opacity-0 cursor-pointer"
                                @change="onFileChange"
                                :disabled="isProcessingAi"
                            />
                            
                            <div class="text-center space-y-3 pointer-events-none">
                                <ArrowUpTrayIcon class="h-10 w-10 text-purple-400 mx-auto" />
                                <div class="text-xs font-bold text-slate-350">
                                    {{ audioFileName ? `Berkas Terpilih: ${audioFileName}` : 'Seret file suara ke sini atau klik untuk mencari' }}
                                </div>
                                <div class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">
                                    Format: MP3, WAV, M4A | Maksimal: 15MB
                                </div>
                            </div>
                        </div>

                        <!-- Error Alert -->
                        <div v-if="aiError" class="p-3 bg-rose-500/10 border border-rose-500/20 text-rose-450 rounded-xl text-[11px] font-bold font-mono">
                            {{ aiError }}
                        </div>

                        <div class="flex justify-end gap-3 pt-2">
                            <button 
                                type="button" 
                                @click="closeAiModal" 
                                class="px-5 py-2.5 rounded-xl bg-[#030108] border border-purple-500/10 text-xs font-bold text-slate-400 hover:bg-[#160c29] hover:text-white transition-all"
                                :disabled="isProcessingAi"
                            >
                                Batal
                            </button>
                            <button 
                                type="button" 
                                @click="processAudioWithAi" 
                                class="px-6 py-2.5 rounded-xl bg-purple-650 hover:bg-purple-600 text-xs font-bold text-white shadow-lg shadow-purple-500/20 active:scale-95 transition-all flex items-center gap-2 disabled:opacity-50 disabled:pointer-events-none"
                                :disabled="isProcessingAi || !audioFile"
                            >
                                <span v-if="isProcessingAi" class="inline-block w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                <span>{{ isProcessingAi ? 'AI Sedang Mendengarkan Berkas...' : 'Proses Berkas Audio dengan AI' }}</span>
                            </button>
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
@keyframes wave {
  0%, 100% {
    transform: scaleY(0.4);
  }
  50% {
    transform: scaleY(1.3);
  }
}
.animate-wave {
  animation: wave 1.2s ease-in-out infinite;
  transform-origin: bottom;
}
.animate-wave:nth-child(2) { animation-delay: 0.15s; }
.animate-wave:nth-child(3) { animation-delay: 0.3s; }
.animate-wave:nth-child(4) { animation-delay: 0.45s; }
.animate-wave:nth-child(5) { animation-delay: 0.6s; }
.animate-wave:nth-child(6) { animation-delay: 0.75s; }
</style>
