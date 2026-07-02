<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { 
  Tv, 
  Volume2, 
  VolumeX, 
  Truck, 
  Clock, 
  CheckCircle, 
  AlertCircle, 
  ListOrdered, 
  Radio, 
  Sparkles,
  ArrowRight,
  ShieldCheck
} from 'lucide-vue-next';

const props = defineProps({
  called: {
    type: Array,
    default: () => []
  },
  queued: {
    type: Array,
    default: () => []
  },
  completed: {
    type: Array,
    default: () => []
  }
});

// Audio & Voice States
const isMuted = ref(false);
const speechRate = ref(0.85);
const speechPitch = ref(1.0);
const lastSpokenId = ref(null);
const isAnnouncing = ref(false);
const currentAnnouncementText = ref('');
const voiceList = ref([]);
const selectedVoice = ref(null);

// Timer for auto refresh
let refreshTimer = null;
const lastSync = ref(new Date().toLocaleTimeString());

// Fetch available voices for Indonesian TTS
const loadVoices = () => {
  if (typeof window === 'undefined') return;
  const voices = window.speechSynthesis.getVoices();
  voiceList.value = voices;
  
  // Try to find an Indonesian voice
  const indonesianVoice = voices.find(voice => 
    voice.lang.includes('id') || voice.lang.includes('ID')
  );
  selectedVoice.value = indonesianVoice ? indonesianVoice.name : (voices[0]?.name || null);
};

// Play TTS Announcement
const speakAnnouncement = (text) => {
  if (isMuted.value || typeof window === 'undefined') return;
  
  // Cancel current speech
  window.speechSynthesis.cancel();
  
  currentAnnouncementText.value = text;
  isAnnouncing.value = true;
  
  const utterance = new SpeechSynthesisUtterance(text);
  utterance.rate = speechRate.value;
  utterance.pitch = speechPitch.value;
  
  if (selectedVoice.value) {
    const voice = voiceList.value.find(v => v.name === selectedVoice.value);
    if (voice) utterance.voice = voice;
  }
  
  // Set fallback lang
  utterance.lang = 'id-ID';
  
  utterance.onend = () => {
    isAnnouncing.value = false;
  };
  
  utterance.onerror = (e) => {
    console.error('Speech error:', e);
    isAnnouncing.value = false;
  };
  
  window.speechSynthesis.speak(utterance);
};

// Test speech
const triggerTestSpeech = () => {
  speakAnnouncement("Sistem panggilan suara aktif. Selamat datang di pusat kontrol logistik U S I C S.");
};

// Check for new calls and play sound
const checkNewCalls = () => {
  if (props.called.length === 0) return;
  
  const latestCall = props.called[0];
  const callId = latestCall.id;
  const lastSpoken = localStorage.getItem('usics_last_spoken_call_id');
  
  // If it's a new call we haven't spoken yet
  if (callId && callId.toString() !== lastSpoken) {
    localStorage.setItem('usics_last_spoken_call_id', callId.toString());
    lastSpokenId.value = callId;
    
    // Construct Indonesian announcement
    const driverName = latestCall.driver_name || 'Supir';
    const plate = latestCall.vehicle_number || (latestCall.vehicle?.license_plate || '');
    const formattedPlate = plate.split('').join(' '); // Space out letters for better spelling (e.g. B 1 2 3 4 X Y Z)
    const bay = latestCall.loading_bay || 'Bay Pemuatan';
    
    const speechText = `Panggilan untuk supir, Bapak ${driverName}, dengan nomor kendaraan, ${formattedPlate}. Silakan memasuki, ${bay}, untuk melakukan muat barang. Terima kasih.`;
    
    // Delay slightly to allow page load/transitions
    setTimeout(() => {
      speakAnnouncement(speechText);
    }, 1000);
  }
};

// Autopool data every 8 seconds
const startPolling = () => {
  refreshTimer = setInterval(() => {
    router.reload({
      only: ['called', 'queued', 'completed'],
      onSuccess: () => {
        lastSync.value = new Date().toLocaleTimeString();
      }
    });
  }, 8000);
};

watch(() => props.called, () => {
  checkNewCalls();
}, { deep: true });

onMounted(() => {
  if (typeof window !== 'undefined') {
    loadVoices();
    if (window.speechSynthesis.onvoiceschanged !== undefined) {
      window.speechSynthesis.onvoiceschanged = loadVoices;
    }
    
    // Check initially
    const latestCall = props.called[0];
    if (latestCall) {
      lastSpokenId.value = latestCall.id;
      // Write to localStorage on mount so we don't spam-speak old calls on page refreshes
      const lastSpoken = localStorage.getItem('usics_last_spoken_call_id');
      if (!lastSpoken) {
        localStorage.setItem('usics_last_spoken_call_id', latestCall.id.toString());
      }
    }
    
    startPolling();
  }
});

onUnmounted(() => {
  if (refreshTimer) clearInterval(refreshTimer);
  if (typeof window !== 'undefined') {
    window.speechSynthesis.cancel();
  }
});
</script>

<template>
  <Head title="Logistics Loading Queue Control HUD" />
  
  <div class="min-h-screen bg-slate-950 text-slate-100 font-sans antialiased overflow-x-hidden relative">
    <!-- Matrix Grid Overlay for Futuristic Theme -->
    <div class="absolute inset-0 bg-[linear-gradient(to_right,#0c1524_1px,transparent_1px),linear-gradient(to_bottom,#0c1524_1px,transparent_1px)] bg-[size:3rem_3rem] pointer-events-none opacity-40"></div>
    <!-- Radial Glow overlay -->
    <div class="absolute top-[-20%] left-[-10%] w-[60%] h-[60%] bg-cyan-900/10 rounded-full blur-[120px] pointer-events-none"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-[50%] h-[50%] bg-indigo-950/20 rounded-full blur-[100px] pointer-events-none"></div>

    <!-- Futuristic Header -->
    <header class="border-b border-cyan-500/20 bg-slate-900/60 backdrop-blur-md sticky top-0 z-10 px-6 py-4 shadow-[0_4px_30px_rgba(0,0,0,0.5)]">
      <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        
        <!-- Left Title and Logo -->
        <div class="flex items-center gap-3">
          <div class="relative flex items-center justify-center w-10 h-10 rounded-lg border border-cyan-400 bg-cyan-950/50 shadow-[0_0_15px_rgba(34,211,238,0.3)]">
            <Tv class="w-6 h-6 text-cyan-400 animate-pulse" />
            <div class="absolute inset-0 rounded-lg border border-cyan-400 animate-ping opacity-20"></div>
          </div>
          <div>
            <h1 class="text-xl font-bold tracking-wider text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 via-indigo-300 to-cyan-200">
              USICS LOGISTICS CONTROL HUB
            </h1>
            <p class="text-xs text-cyan-500/60 uppercase tracking-widest font-mono">Real-Time Loading Queue & Voice Director</p>
          </div>
        </div>

        <!-- Center sound control -->
        <div class="flex flex-wrap items-center gap-4 bg-slate-900/80 border border-slate-800 px-4 py-2 rounded-xl backdrop-blur-sm">
          <button 
            @click="isMuted = !isMuted"
            class="flex items-center gap-2 text-xs font-mono font-medium transition-all px-3 py-1 rounded-lg border"
            :class="isMuted 
              ? 'border-red-500/40 bg-red-950/20 text-red-400 hover:bg-red-950/40' 
              : 'border-cyan-500/30 bg-cyan-950/20 text-cyan-400 hover:bg-cyan-950/40'"
          >
            <component :is="isMuted ? VolumeX : Volume2" class="w-4 h-4" />
            <span>{{ isMuted ? 'MUTE ACTIVE' : 'VOICE ACTIVE' }}</span>
          </button>

          <!-- Voice Selector -->
          <div class="hidden lg:flex items-center gap-2">
            <span class="text-[10px] font-mono text-slate-400">VOICE:</span>
            <select 
              v-model="selectedVoice"
              class="bg-slate-950 text-cyan-400 text-xs border border-slate-800 rounded px-2 py-1 focus:outline-none focus:border-cyan-500 font-mono"
            >
              <option v-for="voice in voiceList" :key="voice.name" :value="voice.name">
                {{ voice.name }} ({{ voice.lang }})
              </option>
            </select>
          </div>

          <button 
            @click="triggerTestSpeech" 
            class="text-[10px] font-mono border border-slate-700 hover:border-cyan-500/50 hover:bg-cyan-950/20 text-slate-300 px-3 py-1 rounded-lg transition-all"
          >
            TEST VOICE
          </button>
        </div>

        <!-- Sync stats -->
        <div class="flex items-center gap-3 text-right">
          <div class="flex flex-col">
            <span class="text-[10px] font-mono text-slate-500">SYSTEM REFRESH</span>
            <span class="text-xs font-mono text-emerald-400 flex items-center justify-end gap-1.5">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span>
              SYNCED {{ lastSync }}
            </span>
          </div>
        </div>

      </div>
    </header>

    <main class="max-w-7xl mx-auto p-4 md:p-6 lg:p-8 space-y-6">
      
      <!-- GIANT HIGH-INTENSITY CURRENT CALL HUD BANNER -->
      <section v-if="called.length > 0" class="relative">
        <!-- Glowing Ambient Shadow behind the main banner -->
        <div class="absolute -inset-1 rounded-3xl bg-gradient-to-r from-cyan-500 via-indigo-500 to-cyan-500 opacity-20 blur-xl animate-pulse"></div>
        
        <div class="relative bg-slate-900/80 border-2 border-cyan-400/40 rounded-2xl overflow-hidden backdrop-blur-md shadow-[0_0_50px_rgba(6,182,212,0.15)]">
          <!-- Hologram Scanner bar line -->
          <div class="absolute left-0 right-0 h-[2px] bg-cyan-400/50 shadow-[0_0_10px_#22d3ee] animate-scanner z-0 pointer-events-none"></div>
          
          <div class="p-6 md:p-8 flex flex-col lg:flex-row items-center justify-between gap-6 relative z-10">
            <!-- Left Info Panel -->
            <div class="space-y-3 text-center lg:text-left flex-1">
              <div class="inline-flex items-center gap-2 px-3 py-1 bg-cyan-950/50 border border-cyan-500/40 rounded-full text-cyan-400 font-mono text-xs tracking-wider animate-pulse">
                <Radio class="w-3.5 h-3.5" />
                <span>PANGGILAN AKTIF / ACTIVE LOAD-CALL</span>
              </div>
              <h2 class="text-3xl md:text-5xl font-extrabold tracking-tight text-white flex flex-wrap items-center justify-center lg:justify-start gap-4">
                <span class="font-mono text-cyan-300 drop-shadow-[0_0_10px_rgba(34,211,238,0.4)]">
                  {{ called[0].vehicle_number || called[0].vehicle?.license_plate || 'N/A' }}
                </span>
                <span class="text-slate-400 font-light text-2xl md:text-4xl">|</span>
                <span class="text-white drop-shadow-sm uppercase">{{ called[0].driver_name }}</span>
              </h2>
              <p class="text-slate-400 text-sm md:text-base max-w-xl mx-auto lg:mx-0">
                Panggilan untuk supir pengiriman tujuan <strong class="text-indigo-300 font-semibold">{{ called[0].customer?.name || 'Customer' }}</strong>. Silakan segera arahkan armada Anda.
              </p>
            </div>

            <!-- Speech visualizer center -->
            <div v-if="isAnnouncing" class="flex items-center justify-center gap-1.5 px-6 py-4 bg-cyan-950/20 border border-cyan-500/10 rounded-2xl w-full lg:w-auto">
              <div class="w-1.5 h-8 bg-cyan-400 rounded animate-bar-1"></div>
              <div class="w-1.5 h-12 bg-cyan-400 rounded animate-bar-2"></div>
              <div class="w-1.5 h-16 bg-cyan-400 rounded animate-bar-3"></div>
              <div class="w-1.5 h-10 bg-cyan-400 rounded animate-bar-4"></div>
              <div class="w-1.5 h-14 bg-cyan-400 rounded animate-bar-5"></div>
              <span class="text-xs font-mono text-cyan-400 tracking-widest uppercase ml-4 animate-pulse">MEMUTAR SUARA...</span>
            </div>

            <!-- Big Bay Target Panel -->
            <div class="flex flex-col items-center justify-center px-8 py-6 bg-gradient-to-b from-cyan-950/50 to-slate-950 border-l lg:border-l border-cyan-400/20 min-w-[240px] text-center rounded-xl w-full lg:w-auto shadow-inner">
              <span class="text-xs font-mono text-cyan-500 tracking-wider mb-1">SILAKAN MENUJU / GO TO</span>
              <div class="text-4xl md:text-5xl font-black text-cyan-400 tracking-wider drop-shadow-[0_0_15px_rgba(34,211,238,0.5)] font-mono animate-pulse">
                {{ called[0].loading_bay || 'BAY 1' }}
              </div>
              <div class="flex items-center gap-1.5 mt-2 text-[10px] font-mono text-slate-400">
                <Clock class="w-3.5 h-3.5 text-indigo-400" />
                <span>DIPANGGIL {{ new Date(called[0].called_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }} WIB</span>
              </div>
            </div>

          </div>
        </div>
      </section>

      <!-- EMPTY STATE BANNER -->
      <section v-else class="bg-slate-900/40 border border-slate-800 rounded-2xl p-8 text-center flex flex-col items-center justify-center gap-3 backdrop-blur-md">
        <div class="w-12 h-12 rounded-full bg-slate-950 flex items-center justify-center border border-slate-800 text-slate-500 shadow-inner">
          <Radio class="w-6 h-6 animate-pulse" />
        </div>
        <h3 class="text-lg font-semibold text-slate-300">Belum Ada Panggilan Aktif</h3>
        <p class="text-sm text-slate-500 max-w-sm">Truk yang dipanggil oleh admin logistik akan muncul di layar utama dengan pengumuman suara otomatis.</p>
      </section>

      <!-- MAIN HUD DETAILS GRID -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- PANEL 1: LOADING IN PROGRESS (Status: picking) -->
        <div class="lg:col-span-2 flex flex-col bg-slate-900/60 border border-slate-800/80 rounded-2xl backdrop-blur-md overflow-hidden shadow-xl">
          <div class="p-4 bg-slate-900 border-b border-slate-800 flex items-center justify-between">
            <h3 class="text-sm font-semibold tracking-wider font-mono text-cyan-400 flex items-center gap-2">
              <Truck class="w-4 h-4 text-cyan-400" />
              SEDANG LOADING / LOADING IN PROGRESS
            </h3>
            <span class="px-2.5 py-0.5 bg-cyan-950/60 border border-cyan-800 text-cyan-400 text-xs font-mono rounded-full">
              {{ called.length }} TRUK
            </span>
          </div>

          <div class="p-4 flex-1 space-y-4 max-h-[500px] overflow-y-auto custom-scrollbar">
            <div v-if="called.length === 0" class="h-40 flex items-center justify-center text-slate-600 text-xs font-mono">
              TIDAK ADA AKTIVITAS LOADING AKTIF
            </div>
            
            <!-- Called item rows -->
            <div 
              v-for="(item, index) in called" 
              :key="item.id"
              class="group relative bg-slate-950/60 border border-slate-800 hover:border-cyan-500/30 p-4 rounded-xl transition-all duration-300 flex flex-col sm:flex-row sm:items-center justify-between gap-4 overflow-hidden"
            >
              <!-- Left strip color indicator -->
              <div class="absolute left-0 top-0 bottom-0 w-1 bg-cyan-500 shadow-[0_0_10px_#06b6d4]"></div>

              <div class="flex items-start gap-4">
                <div class="w-10 h-10 rounded-lg bg-cyan-950/30 border border-cyan-900 flex items-center justify-center text-cyan-400 shrink-0 group-hover:scale-105 transition-transform">
                  <span class="text-xs font-mono font-bold">#{{ index + 1 }}</span>
                </div>
                <div>
                  <div class="flex items-center gap-2">
                    <span class="font-mono text-lg font-bold text-slate-100">{{ item.vehicle_number || item.vehicle?.license_plate || 'N/A' }}</span>
                    <span class="text-xs text-slate-500 font-mono">({{ item.vehicle?.vehicle_type || 'Truk' }})</span>
                  </div>
                  <div class="text-sm font-medium text-slate-300 uppercase mt-0.5">{{ item.driver_name }}</div>
                  <div class="text-xs text-slate-500 mt-1 flex items-center gap-1.5">
                    <span>DO: <span class="font-mono text-slate-400">{{ item.do_number }}</span></span>
                    <span>•</span>
                    <span class="truncate max-w-[150px]" :title="item.customer?.name">{{ item.customer?.name || 'N/A' }}</span>
                  </div>
                </div>
              </div>

              <!-- Bay Info -->
              <div class="flex items-center gap-4 self-end sm:self-center">
                <div class="text-right">
                  <span class="text-[10px] font-mono text-slate-500 block">LOKASI MUAT</span>
                  <span class="font-mono text-base font-semibold text-cyan-300">{{ item.loading_bay || 'Bay' }}</span>
                </div>
                <div class="h-10 w-[1px] bg-slate-800"></div>
                <div class="text-right">
                  <span class="text-[10px] font-mono text-slate-500 block">DIPANGGIL</span>
                  <span class="font-mono text-xs text-indigo-300">{{ new Date(item.called_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}</span>
                </div>
              </div>
            </div>

          </div>
        </div>

        <!-- PANEL 2: UPCOMING QUEUE (Status: draft) -->
        <div class="flex flex-col bg-slate-900/60 border border-slate-800/80 rounded-2xl backdrop-blur-md overflow-hidden shadow-xl">
          <div class="p-4 bg-slate-900 border-b border-slate-800 flex items-center justify-between">
            <h3 class="text-sm font-semibold tracking-wider font-mono text-indigo-400 flex items-center gap-2">
              <ListOrdered class="w-4 h-4 text-indigo-400" />
              ANTRIAN BERIKUTNYA / UPCOMING QUEUE
            </h3>
            <span class="px-2.5 py-0.5 bg-indigo-950/60 border border-indigo-800 text-indigo-400 text-xs font-mono rounded-full">
              {{ queued.length }} TRUK
            </span>
          </div>

          <div class="p-4 flex-1 space-y-3 max-h-[500px] overflow-y-auto custom-scrollbar">
            <div v-if="queued.length === 0" class="h-40 flex items-center justify-center text-slate-600 text-xs font-mono">
              BELUM ADA ANTRIAN BARU
            </div>

            <!-- Queued item rows -->
            <div 
              v-for="(item, index) in queued" 
              :key="item.id"
              class="bg-slate-950/40 border border-slate-800/60 p-3 rounded-lg hover:bg-slate-950/80 transition-colors flex items-center justify-between gap-3"
            >
              <div class="flex items-center gap-3">
                <div class="w-7 h-7 rounded bg-slate-900 border border-slate-800 text-slate-400 text-xs font-mono flex items-center justify-center">
                  {{ index + 1 }}
                </div>
                <div>
                  <span class="font-mono text-sm font-bold text-slate-200 block">{{ item.vehicle_number || item.vehicle?.license_plate || 'N/A' }}</span>
                  <span class="text-xs text-slate-400 uppercase font-mono block truncate max-w-[140px]">{{ item.driver_name }}</span>
                </div>
              </div>

              <div class="text-right">
                <span class="text-[9px] font-mono text-slate-500 block">DO NUMBER</span>
                <span class="text-xs font-mono text-indigo-300 block">{{ item.do_number }}</span>
              </div>
            </div>

          </div>
        </div>

      </div>

      <!-- BOTTOM ROW: RECENTLY COMPLETED LOADS -->
      <section class="bg-slate-900/60 border border-slate-800/80 rounded-2xl backdrop-blur-md overflow-hidden shadow-xl">
        <div class="p-4 bg-slate-900 border-b border-slate-800 flex items-center justify-between">
          <h3 class="text-sm font-semibold tracking-wider font-mono text-emerald-400 flex items-center gap-2">
            <CheckCircle class="w-4 h-4 text-emerald-400" />
            SELESAI LOADING / RECENTLY COMPLETED
          </h3>
          <span class="text-[10px] font-mono text-slate-500">LIVE STATS</span>
        </div>

        <div class="p-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
          <div v-if="completed.length === 0" class="col-span-full py-8 text-center text-slate-600 text-xs font-mono">
            BELUM ADA TRUK YANG SELESAI LOADING HARI INI
          </div>

          <div 
            v-for="item in completed" 
            :key="item.id"
            class="bg-slate-950/60 border border-emerald-950 hover:border-emerald-800/30 p-3.5 rounded-xl flex flex-col justify-between gap-3 relative overflow-hidden"
          >
            <div class="absolute right-2 top-2">
              <ShieldCheck class="w-4 h-4 text-emerald-500/70" />
            </div>
            
            <div>
              <span class="text-[10px] font-mono text-slate-500 block">LICENSE PLATE</span>
              <span class="font-mono text-sm font-bold text-slate-200">{{ item.vehicle_number || item.vehicle?.license_plate || 'N/A' }}</span>
              <span class="text-xs text-slate-400 uppercase font-mono block truncate mt-0.5">{{ item.driver_name }}</span>
            </div>

            <div class="border-t border-slate-800 pt-2 flex items-center justify-between">
              <div>
                <span class="text-[8px] font-mono text-slate-500 block">LOADING BAY</span>
                <span class="text-xs font-mono text-emerald-400 font-semibold">{{ item.loading_bay || 'N/A' }}</span>
              </div>
              <div class="text-right">
                <span class="text-[8px] font-mono text-slate-500 block">DISPATCHED</span>
                <span class="text-xs font-mono text-slate-400">{{ new Date(item.updated_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}</span>
              </div>
            </div>
          </div>
        </div>
      </section>

    </main>

    <!-- Holographic Footer details -->
    <footer class="border-t border-slate-900 bg-slate-950 py-6 text-center text-xs text-slate-500 font-mono relative z-10">
      <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-4">
        <span>&copy; {{ new Date().getFullYear() }} USICS ERP LOGISTICS CONTROLLER. ALL SYSTEMS ONLINE.</span>
        <div class="flex items-center gap-4 text-[10px]">
          <span class="flex items-center gap-1"><span class="w-1.5 h-1.5 bg-cyan-400 rounded-full animate-ping"></span> GPS GATEWAY ONLINE</span>
          <span class="flex items-center gap-1"><span class="w-1.5 h-1.5 bg-emerald-400 rounded-full"></span> TTS SERVICE ONLINE</span>
        </div>
      </div>
    </footer>
  </div>
</template>

<style>
/* Futuristic HUD Custom CSS Animations */
@keyframes scanner {
  0% {
    top: 0%;
    opacity: 0.1;
  }
  50% {
    opacity: 0.8;
  }
  100% {
    top: 100%;
    opacity: 0.1;
  }
}

.animate-scanner {
  animation: scanner 5s linear infinite;
}

/* Audio Visualizer animations */
@keyframes voice-bar-1 {
  0%, 100% { height: 12px; }
  50% { height: 28px; }
}
@keyframes voice-bar-2 {
  0%, 100% { height: 16px; }
  50% { height: 42px; }
}
@keyframes voice-bar-3 {
  0%, 100% { height: 20px; }
  50% { height: 56px; }
}
@keyframes voice-bar-4 {
  0%, 100% { height: 10px; }
  50% { height: 35px; }
}
@keyframes voice-bar-5 {
  0%, 100% { height: 14px; }
  50% { height: 48px; }
}

.animate-bar-1 { animation: voice-bar-1 0.7s ease-in-out infinite alternate; }
.animate-bar-2 { animation: voice-bar-2 0.5s ease-in-out infinite alternate; }
.animate-bar-3 { animation: voice-bar-3 0.6s ease-in-out infinite alternate; }
.animate-bar-4 { animation: voice-bar-4 0.8s ease-in-out infinite alternate; }
.animate-bar-5 { animation: voice-bar-5 0.55s ease-in-out infinite alternate; }

/* Custom Scrollbar for dark theme */
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: rgba(15, 23, 42, 0.5);
  border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(6, 182, 212, 0.2);
  border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(6, 182, 212, 0.4);
}
</style>
