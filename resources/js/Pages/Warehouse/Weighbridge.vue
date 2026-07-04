<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Head, router, usePage, Link } from '@inertiajs/vue3';
import {
  Scale,
  Truck,
  User,
  Scan,
  Clock,
  CheckCircle,
  XCircle,
  AlertTriangle,
  ArrowLeft,
  Volume2,
  VolumeX,
  Radio,
  LogIn,
  LogOut,
  Sparkles,
  RefreshCw,
  History,
  FileText,
  Sun,
  Moon
} from 'lucide-vue-next';

const props = defineProps({
  deliveryOrders: {
    type: Array,
    default: () => []
  },
  scans: {
    type: Array,
    default: () => []
  }
});

const page = usePage();

// State
const selectedDoId = ref('');
const scanAction = ref('smart');
const simulatedWeight = ref(8200); // Live scale weight in Kg
const rfidInputText = ref('');
const processing = ref(false);
const isMuted = ref(false);
const lastSync = ref(new Date().toLocaleTimeString());
let refreshTimer = null;

// Scanned vehicle/scale data
const scannedData = ref(null);

// RFID keyboard buffer for USB reader
const rfidBuffer = ref('');
let rfidTimeout = null;

// Computed: Find the selected DO details
const selectedDo = computed(() => {
  if (scannedData.value?.delivery_order) {
    return scannedData.value.delivery_order;
  }
  return props.deliveryOrders.find(d => d.id === selectedDoId.value) || null;
});

// Parse Tare & Gross weights from DO notes
const parsedWeights = computed(() => {
  if (!selectedDo.value) return { tare: null, gross: null, net: null };
  const notes = selectedDo.value.notes || '';
  
  let tare = null;
  let gross = null;

  // Extract Tare
  const tareMatch = notes.match(/Timbang Masuk \(Tare\):\s*([\d,]+)\s*Kg/i);
  if (tareMatch) {
    tare = parseInt(tareMatch[1].replace(/,/g, ''), 10);
  }

  // Extract Gross
  const grossMatch = notes.match(/Timbang Keluar \(Gross\):\s*([\d,]+)\s*Kg/i);
  if (grossMatch) {
    gross = parseInt(grossMatch[1].replace(/,/g, ''), 10);
  }

  // Calculate Net
  const net = (tare !== null && gross !== null) ? Math.max(0, gross - tare) : null;

  return { tare, gross, net };
});

// Auto-suggest action based on selected DO status
watch(selectedDoId, (newId) => {
  if (!newId) return;
  const match = props.deliveryOrders.find(d => d.id === newId);
  if (match) {
    scannedData.value = null;
    if (match.status === 'draft') {
      scanAction.value = 'smart'; // Server defaults to Tare (in)
      simulatedWeight.value = 8200; // tare default
    } else {
      scanAction.value = 'smart'; // Server defaults to Gross (out)
      simulatedWeight.value = 15400; // gross default
    }
  }
});

// Indonesian Text-to-Speech Voice Synthesis
const speakMessage = (text) => {
  if (isMuted.value || typeof window === 'undefined' || !window.speechSynthesis) return;
  try {
    window.speechSynthesis.cancel();
    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = 'id-ID';
    utterance.rate = 0.95;
    utterance.pitch = 1.0;
    
    const voices = window.speechSynthesis.getVoices();
    const idVoice = voices.find(v => v.lang.includes('id') || v.lang.includes('ID'));
    if (idVoice) utterance.voice = idVoice;
    
    window.speechSynthesis.speak(utterance);
  } catch (e) {
    console.error('Speech synthesis error:', e);
  }
};

// Play audio buzzer feedback
const playSound = (type) => {
  if (isMuted.value) return;
  try {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    
    osc.connect(gain);
    gain.connect(ctx.destination);
    
    if (type === 'success') {
      osc.frequency.setValueAtTime(880, ctx.currentTime); // A5
      gain.gain.setValueAtTime(0.1, ctx.currentTime);
      osc.start();
      osc.stop(ctx.currentTime + 0.15);
    } else if (type === 'warning') {
      osc.frequency.setValueAtTime(440, ctx.currentTime); // A4
      gain.gain.setValueAtTime(0.15, ctx.currentTime);
      osc.start();
      osc.stop(ctx.currentTime + 0.3);
    } else { // error
      osc.frequency.setValueAtTime(220, ctx.currentTime); // A3
      gain.gain.setValueAtTime(0.2, ctx.currentTime);
      osc.start();
      osc.stop(ctx.currentTime + 0.4);
    }
  } catch (e) {}
};

// Check flash notifications on load/update
watch(() => page.props.flash, (flash) => {
  if (flash?.dockScannedData) {
    scannedData.value = flash.dockScannedData;
  }
}, { deep: true, immediate: true });

// Trigger the weighbridge RFID scan API call
const triggerScan = () => {
  if (processing.value || !selectedDoId.value) return;
  
  processing.value = true;
  router.post(route('warehouse.weighbridge.scan'), {
    delivery_order_id: selectedDoId.value,
    action: scanAction.value,
    weight: simulatedWeight.value,
  }, {
    preserveScroll: true,
    onSuccess: (p) => {
      const flash = p.props.flash || {};
      const data = flash.dockScannedData;
      if (data) {
        scannedData.value = data;
      }
      
      if (flash.error) {
        playSound('error');
        speakMessage('Penimbangan gagal. Periksa status armada.');
      } else if (flash.warning) {
        playSound('warning');
        speakMessage('Gagal memproses. Timbangan mendeteksi ketidaksesuaian.');
      } else {
        playSound('success');
        const act = data?.scan_action;
        const wtVal = data?.weight_value || simulatedWeight.value;
        
        if (act === 'in') {
          speakMessage(`Penimbangan kosong selesai. Berat kosong ${wtVal} kilogram. Silakan menuju dermaga pemuatan.`);
        } else {
          // Gross weighing
          const netWt = parsedWeights.value.net || (wtVal - (parsedWeights.value.tare || 8200));
          speakMessage(`Penimbangan muatan selesai. Berat kotor ${wtVal} kilogram. Berat bersih ${netWt} kilogram. Silakan menuju gerbang keluar.`);
        }
      }
    },
    onError: () => {
      playSound('error');
      speakMessage('Gagal memproses penimbangan.');
    },
    onFinish: () => processing.value = false,
  });
};

// Process scanned RFID tag
const processRfidTag = (tag) => {
  const cleanedInput = tag.replace('RFID-TRUCK-', '').replace(/[\s-]/g, '').toUpperCase();
  
  // Find DO matching license plate or RFID card number
  const match = props.deliveryOrders.find(d => {
    const doPlate = (d.vehicle_number || d.vehicle?.license_plate || '').replace(/[\s-]/g, '').toUpperCase();
    const doRfid = (d.vehicle?.rfid_card || '').replace(/[\s-]/g, '').toUpperCase();
    return doPlate === cleanedInput || (doRfid && doRfid === cleanedInput);
  });

  if (match) {
    selectedDoId.value = match.id;
    // Set appropriate simulated weight if auto-detecting
    if (match.status === 'draft') {
      simulatedWeight.value = 8200; // tare default
    } else {
      simulatedWeight.value = 15400; // gross default
    }
    // Automatically trigger scan after short timeout to let Vue state bind
    setTimeout(() => triggerScan(), 150);
  } else {
    playSound('error');
    speakMessage('Gagal memproses. Kartu tidak terdaftar.');
    scannedData.value = {
      scan_status: 'error',
      scan_message: `❌ Tag/Plat "${tag}" tidak ditemukan dalam antrean timbang aktif.`,
      scan_time: new Date().toLocaleTimeString()
    };
  }
};

const handleManualScan = () => {
  if (rfidInputText.value.trim().length > 0) {
    processRfidTag(rfidInputText.value.trim());
    rfidInputText.value = '';
  }
};

// USB Reader Keyboard emulation listener
const handleKeyDown = (e) => {
  if (e.target.id === 'rfid-manual-input' && e.key === 'Enter') {
    handleManualScan();
    return;
  }
  if (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT' || e.target.tagName === 'TEXTAREA') return;

  if (e.key === 'Enter') {
    if (rfidBuffer.value.length > 3) {
      processRfidTag(rfidBuffer.value.trim());
    }
    rfidBuffer.value = '';
    return;
  }

  if (e.key.length === 1) {
    rfidBuffer.value += e.key;
    clearTimeout(rfidTimeout);
    rfidTimeout = setTimeout(() => { rfidBuffer.value = ''; }, 300);
  }
};

// Auto refresh logs
onMounted(() => {
  window.addEventListener('keydown', handleKeyDown);
  // Auto-reload data every 10 seconds to keep scale logs & DO list updated
  refreshTimer = setInterval(() => {
    router.reload({
      only: ['deliveryOrders', 'scans'],
      onSuccess: () => {
        lastSync.value = new Date().toLocaleTimeString();
      }
    });
  }, 10000);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyDown);
  clearInterval(refreshTimer);
});

// Helper formatting functions
const formatWeight = (val) => {
  if (val === null || val === undefined) return '-';
  return Number(val).toLocaleString('id-ID') + ' Kg';
};

const statusLabel = (status) => {
  switch (status) {
    case 'draft': return 'Timbang Masuk (Tare)';
    case 'picking': return 'Proses Loading';
    case 'packed': return 'Timbang Keluar (Gross)';
    default: return status?.toUpperCase() || '';
  }
};

const statusColor = (status) => {
  const isLight = isLightMode.value;
  switch (status) {
    case 'draft': return isLight ? 'bg-cyan-50 border border-cyan-200 text-cyan-700' : 'bg-cyan-950/80 text-cyan-400 border border-cyan-800/60';
    case 'picking': return isLight ? 'bg-amber-50 border border-amber-200 text-amber-705 font-bold' : 'bg-amber-955/80 text-amber-400 border border-amber-800/60';
    case 'packed': return isLight ? 'bg-emerald-50 border border-emerald-200 text-emerald-700' : 'bg-emerald-950/80 text-emerald-400 border border-emerald-800/60';
    default: return isLight ? 'bg-slate-100 border border-slate-200 text-slate-700' : 'bg-slate-900 text-slate-400';
  }
};

// --- Theme Toggle & Reactive Sync ---
const isLightMode = ref(false);
let observer;

const toggleTheme = () => {
  if (document.documentElement.classList.contains('dark')) {
    document.documentElement.classList.remove('dark');
    localStorage.setItem('theme', 'light');
    isLightMode.value = true;
  } else {
    document.documentElement.classList.add('dark');
    localStorage.setItem('theme', 'dark');
    isLightMode.value = false;
  }
};

onMounted(() => {
  // Restore theme from localStorage if available
  const savedTheme = localStorage.getItem('theme');
  if (savedTheme === 'dark') {
    document.documentElement.classList.add('dark');
  } else if (savedTheme === 'light') {
    document.documentElement.classList.remove('dark');
  }

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
  <Head title="Jembatan Timbang RFID Terminal" />

  <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 flex flex-col font-sans select-none relative overflow-hidden transition-colors duration-300">
    <!-- Grid BG effect -->
    <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,rgba(248,250,252,0.8),rgba(241,245,249,1))] dark:bg-[radial-gradient(ellipse_at_center,rgba(15,23,42,0.8),rgba(3,7,18,1))] z-0"></div>
    <div class="absolute inset-0 bg-[linear-gradient(rgba(148,163,184,0.05)_1px,transparent_1px),linear-gradient(90deg,rgba(148,163,184,0.05)_1px,transparent_1px)] dark:bg-[linear-gradient(rgba(18,24,38,0.05)_1px,transparent_1px),linear-gradient(90deg,rgba(18,24,38,0.05)_1px,transparent_1px)] bg-[size:32px_32px] z-0"></div>

    <!-- MAIN HEADER -->
    <header class="relative z-10 px-6 py-4 bg-white/60 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-800/60 backdrop-blur-md flex items-center justify-between shadow-sm dark:shadow-lg">
      <div class="flex items-center gap-3">
        <Link href="/warehouse/security-gate" class="px-3 py-1.5 bg-white hover:bg-slate-100 border border-slate-300 text-slate-700 dark:bg-slate-800/80 dark:hover:bg-slate-700/80 dark:border-slate-700/60 dark:text-slate-300 text-xs font-semibold rounded-lg flex items-center gap-1.5 transition-colors cursor-pointer">
          <ArrowLeft class="w-3.5 h-3.5" /> Kembali
        </Link>
        <div class="h-6 w-px bg-slate-200 dark:bg-slate-800"></div>
        <div class="flex items-center gap-2">
          <div class="p-2 bg-gradient-to-br from-emerald-500/20 to-teal-500/20 rounded-xl border border-emerald-500/30">
            <Scale class="w-5 h-5 text-emerald-600 dark:text-emerald-400 animate-pulse" />
          </div>
          <div>
            <h1 class="text-sm font-black tracking-widest font-mono text-slate-850 dark:text-white uppercase leading-none mb-1">JEMBATAN TIMBANG RFID TERMINAL</h1>
            <p class="text-[10px] text-slate-500 dark:text-slate-400 font-mono leading-none">Terminal Penimbangan Armada Masuk & Keluar Otomatis</p>
          </div>
        </div>
      </div>

      <div class="flex items-center gap-4">
        <!-- Theme Toggle -->
        <button @click="toggleTheme"
          class="p-2 rounded-xl border transition-all duration-300 flex items-center gap-1.5 text-xs font-semibold cursor-pointer bg-white dark:bg-transparent border-slate-300 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/30">
          <component :is="isLightMode ? Moon : Sun" class="w-4 h-4" />
          <span class="font-mono text-[10px]">{{ isLightMode ? 'DARK' : 'LIGHT' }}</span>
        </button>

        <!-- Audio Mute button -->
        <button @click="isMuted = !isMuted"
          class="p-2 rounded-xl border transition-all duration-300 flex items-center gap-1.5 text-xs font-semibold cursor-pointer bg-white dark:bg-transparent"
          :class="isMuted
            ? 'bg-red-50 hover:bg-red-100 border-red-200 text-red-700 dark:bg-red-955/40 dark:border-red-900/50 dark:text-red-400 dark:hover:bg-red-900/30'
            : 'bg-emerald-50 hover:bg-emerald-100 border-emerald-200 text-emerald-705 dark:bg-emerald-955/40 dark:border-emerald-900/50 dark:text-emerald-400 dark:hover:bg-emerald-900/30'">
          <component :is="isMuted ? VolumeX : Volume2" class="w-4 h-4" />
          <span class="font-mono text-[10px]">{{ isMuted ? 'MUTE' : 'SOUND' }}</span>
        </button>

        <div class="text-right font-mono">
          <div class="text-xs font-bold text-slate-850 dark:text-white flex items-center gap-1.5 justify-end">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
            LIVE SYNC
          </div>
          <div class="text-[10px] text-slate-500">Sync: {{ lastSync }}</div>
        </div>
      </div>
    </header>

    <!-- CONTENT BODY -->
    <main class="relative z-10 flex-1 p-6 grid grid-cols-1 lg:grid-cols-12 gap-6 overflow-hidden">
      
      <!-- ======= LEFT COLUMN: TRUK & STATUS (col-span-4) ======= -->
      <section class="lg:col-span-4 bg-white/90 dark:bg-slate-900/70 border border-slate-205 dark:border-slate-800/80 rounded-2xl backdrop-blur-md overflow-hidden shadow-sm dark:shadow-xl flex flex-col h-[520px]">
        <div class="px-4 py-3 bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
          <h3 class="text-xs font-bold tracking-wider font-mono text-slate-805 dark:text-blue-400 uppercase flex items-center gap-1.5">
            <Truck class="w-3.5 h-3.5" /> Truk di Timbangan
          </h3>
          <span v-if="selectedDo" class="text-[9px] font-mono px-2 py-0.5 rounded-full border" :class="statusColor(selectedDo.status)">
            {{ statusLabel(selectedDo.status) }}
          </span>
        </div>

        <div class="p-4 flex-1 flex flex-col justify-between">
          <template v-if="selectedDo">
            <div class="space-y-4">
              <!-- Vehicle Info Card -->
              <div class="flex items-center gap-3 bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 rounded-xl p-3">
                <div class="w-12 h-12 rounded-xl border border-slate-250 dark:border-blue-500/30 overflow-hidden bg-slate-100 dark:bg-slate-900 flex items-center justify-center shrink-0">
                  <img v-if="selectedDo.vehicle?.vehicle_photo_url" :src="selectedDo.vehicle.vehicle_photo_url" class="w-full h-full object-cover" />
                  <Truck v-else class="w-6 h-6 text-slate-400 dark:text-slate-600" />
                </div>
                <div>
                  <p class="text-[9px] font-mono text-slate-400 dark:text-slate-500 uppercase">Pelat Nomor / Armada</p>
                  <p class="text-sm font-black font-mono text-slate-905 dark:text-blue-300 tracking-wider leading-tight">
                    {{ selectedDo.vehicle_number || selectedDo.vehicle?.license_plate || 'TANPA PLAT' }}
                  </p>
                  <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 leading-tight mt-0.5">{{ selectedDo.driver_name || 'Driver belum diset' }}</p>
                </div>
              </div>

              <!-- DO Details -->
              <div class="space-y-2.5">
                <div class="bg-slate-50 dark:bg-slate-955/30 border border-slate-200 dark:border-slate-800/80 rounded-xl p-3 space-y-1">
                  <span class="text-[9px] font-mono text-slate-450 dark:text-slate-500 uppercase tracking-wider block">Customer</span>
                  <p class="text-xs font-bold text-slate-800 dark:text-white truncate">{{ selectedDo.customer?.name || 'N/A' }}</p>
                  <span class="text-[9px] font-mono text-slate-450 dark:text-slate-500 uppercase tracking-wider block mt-1">Nomor DO</span>
                  <p class="text-xs font-mono text-emerald-650 dark:text-emerald-400 font-bold">{{ selectedDo.do_number }}</p>
                </div>
              </div>
            </div>

            <!-- Weighing details calculations -->
            <div class="bg-slate-100/60 dark:bg-slate-950/80 border border-slate-205 dark:border-slate-800 rounded-2xl p-3.5 space-y-2.5 shadow-inner">
              <div class="flex justify-between items-center text-xs font-mono">
                <span class="text-slate-500 dark:text-slate-400">Berat Masuk (Tare):</span>
                <span class="text-cyan-705 dark:text-cyan-400 font-bold">{{ formatWeight(parsedWeights.tare) }}</span>
              </div>
              <div class="flex justify-between items-center text-xs font-mono border-t border-slate-200 dark:border-slate-800/60 pt-2.5">
                <span class="text-slate-500 dark:text-slate-400">Berat Keluar (Gross):</span>
                <span class="text-emerald-705 dark:text-emerald-400 font-bold">{{ formatWeight(parsedWeights.gross) }}</span>
              </div>
              <div class="flex justify-between items-center text-xs font-mono border-t border-slate-350 dark:border-slate-700/60 pt-2.5">
                <span class="text-slate-700 dark:text-slate-300 font-bold">Berat Bersih (Netto):</span>
                <span class="text-base font-extrabold text-lime-650 dark:text-lime-400">{{ formatWeight(parsedWeights.net) }}</span>
              </div>
            </div>
          </template>

          <div v-else class="flex-1 flex flex-col items-center justify-center text-center py-8">
            <div class="p-4 bg-slate-100 dark:bg-slate-950/40 rounded-full border border-slate-200 dark:border-slate-800 mb-3">
              <Scale class="w-12 h-12 text-slate-400 dark:text-slate-700" />
            </div>
            <p class="text-sm font-semibold text-slate-550 dark:text-slate-400">Timbangan Kosong</p>
            <p class="text-[10px] text-slate-400 dark:text-slate-650 mt-1 max-w-[200px]">Tap kartu RFID kendaraan untuk memulai penimbangan berat</p>
          </div>
        </div>
      </section>

      <!-- ======= CENTER COLUMN: LED DIGITAL INDICATOR (col-span-5) ======= -->
      <section class="lg:col-span-5 flex flex-col gap-5 h-[520px]">
        <!-- Big LED Scale display -->
        <div class="bg-white/90 dark:bg-slate-900/70 border border-slate-205 dark:border-slate-800/80 rounded-2xl p-6 backdrop-blur-md flex flex-col justify-between flex-1 shadow-sm dark:shadow-2xl relative overflow-hidden">
          <div class="absolute -right-16 -top-16 w-32 h-32 bg-emerald-500/5 rounded-full blur-3xl"></div>
          
          <div class="flex justify-between items-center">
            <span class="text-[10px] font-mono text-slate-500 tracking-widest uppercase flex items-center gap-1.5">
              <Radio class="w-3.5 h-3.5 text-emerald-500 animate-pulse" /> Live Scale Weight
            </span>
            <span class="text-[9px] font-mono text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/80 border border-emerald-200 dark:border-emerald-900/60 rounded px-2 py-0.5 font-bold">
              INDICATOR OK
            </span>
          </div>

          <!-- Glowing Green LED numbers -->
          <div class="my-6 py-6 bg-slate-100 dark:bg-slate-950/90 rounded-2xl border border-slate-250 dark:border-slate-800 shadow-inner flex flex-col items-center justify-center relative">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(16,185,129,0.04),transparent_70%)]"></div>
            
            <div class="text-[60px] font-black tracking-widest font-mono text-emerald-600 dark:text-emerald-400 leading-none select-all filter drop-shadow-[0_0_12px_rgba(52,211,153,0.4)]">
              {{ Number(simulatedWeight).toLocaleString('id-ID') }}
            </div>
            <div class="text-xs font-mono font-bold tracking-widest text-emerald-700/70 dark:text-emerald-500/70 mt-2">KILOGRAM (KG)</div>
          </div>

          <!-- Weight Slider simulator controller -->
          <div class="space-y-4">
            <div class="flex items-center justify-between text-xs font-mono text-slate-500 dark:text-slate-400">
              <span>Simulasi Beban Timbangan:</span>
              <span class="text-slate-800 dark:text-white font-bold">{{ Number(simulatedWeight).toLocaleString('id-ID') }} Kg</span>
            </div>
            
            <input
              type="range"
              min="3000"
              max="40000"
              step="50"
              v-model="simulatedWeight"
              class="w-full h-1.5 bg-slate-205 dark:bg-slate-800 rounded-lg appearance-none cursor-pointer accent-emerald-500"
            />

            <!-- Preset simulator weights -->
            <div class="grid grid-cols-2 gap-3 pt-2">
              <button
                @click="simulatedWeight = 8200"
                class="py-2 bg-slate-50 hover:bg-slate-100 dark:bg-slate-950/60 dark:hover:bg-slate-900 border border-slate-250 dark:border-slate-800/80 text-[10px] font-mono text-cyan-700 dark:text-cyan-400 rounded-lg transition-all cursor-pointer">
                Tared / Kosong (8.200 Kg)
              </button>
              <button
                @click="simulatedWeight = 15400"
                class="py-2 bg-slate-50 hover:bg-slate-100 dark:bg-slate-955/60 dark:hover:bg-slate-900 border border-slate-250 dark:border-slate-800/80 text-[10px] font-mono text-emerald-700 dark:text-emerald-400 rounded-lg transition-all cursor-pointer">
                Gross / Muatan (15.400 Kg)
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- ======= RIGHT COLUMN: TERMINAL CONTROL (col-span-3) ======= -->
      <section class="lg:col-span-3 bg-white/90 dark:bg-slate-900/70 border border-slate-205 dark:border-slate-800/80 rounded-2xl backdrop-blur-md overflow-hidden shadow-sm dark:shadow-xl flex flex-col h-[520px]">
        <div class="px-4 py-3 bg-slate-55 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
          <h3 class="text-xs font-bold tracking-wider font-mono text-slate-805 dark:text-blue-400 uppercase flex items-center gap-1.5">
            <Scan class="w-3.5 h-3.5" /> Sensor Timbangan
          </h3>
          <!-- Action Mode Toggle -->
          <div class="flex border border-slate-250 dark:border-slate-700 rounded-lg overflow-hidden">
            <button @click="scanAction = 'smart'"
              class="text-[8px] font-mono font-bold px-2 py-1 transition-all flex items-center gap-0.5 border-0 cursor-pointer"
              :class="scanAction === 'smart' ? 'bg-gradient-to-r from-cyan-600 to-blue-600 text-white shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:text-white'">
              <Sparkles class="w-2.5 h-2.5 animate-pulse text-cyan-300" /> AUTO
            </button>
            <button @click="scanAction = 'in'"
              class="text-[8px] font-mono font-bold px-2 py-1 transition-all flex items-center gap-0.5 border-0 cursor-pointer"
              :class="scanAction === 'in' ? 'bg-cyan-600 text-white shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:text-white'">
              <LogIn class="w-2.5 h-2.5" /> TARE
            </button>
            <button @click="scanAction = 'out'"
              class="text-[8px] font-mono font-bold px-2 py-1 transition-all flex items-center gap-0.5 border-0 cursor-pointer"
              :class="scanAction === 'out' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:text-white'">
              <LogOut class="w-2.5 h-2.5" /> GROSS
            </button>
          </div>
        </div>

        <div class="p-4 flex-1 flex flex-col items-center justify-center">
          <!-- RFID tap animation -->
          <div class="relative mb-5">
            <div class="w-24 h-24 rounded-full border-2 flex items-center justify-center transition-all duration-500"
              :class="scannedData
                ? (scannedData.scan_status === 'success' ? 'border-emerald-400 bg-emerald-50 dark:bg-emerald-950/20 shadow-sm'
                  : scannedData.scan_status === 'error' ? 'border-red-400 bg-red-50 dark:bg-red-950/20 shadow-sm'
                  : 'border-amber-400 bg-amber-50 dark:bg-amber-950/20 shadow-sm')
                : 'border-blue-300 dark:border-blue-400/50 bg-blue-50 dark:bg-blue-955/15 shadow-sm'">
              <component :is="scannedData
                ? (scannedData.scan_status === 'success' ? CheckCircle
                  : scannedData.scan_status === 'error' ? XCircle : AlertTriangle)
                : Scale"
                class="w-10 h-10 transition-colors duration-500"
                :class="scannedData
                  ? (scannedData.scan_status === 'success' ? 'text-emerald-605 dark:text-emerald-400'
                    : scannedData.scan_status === 'error' ? 'text-red-550 dark:text-red-400' : 'text-amber-600 dark:text-amber-400')
                  : 'text-blue-550 dark:text-blue-400'" />
            </div>
            <div v-if="!scannedData" class="absolute inset-0 w-24 h-24 rounded-full border-2 border-blue-400/20 animate-ping"></div>
          </div>

          <!-- Status logs -->
          <div v-if="scannedData" class="text-center mb-4 min-h-[60px] text-xs">
            <p class="font-bold font-mono" :class="scannedData.scan_status === 'success' ? 'text-emerald-700 dark:text-emerald-400' : scannedData.scan_status === 'error' ? 'text-red-650 dark:text-red-400' : 'text-amber-605 dark:text-amber-400'">
              {{ scannedData.scan_status === 'success' ? '✅ Berhasil Ditimbang' : scannedData.scan_status === 'error' ? '❌ Gagal Menimbang' : '⚠️ Peringatan' }}
            </p>
            <p class="text-[9px] text-slate-500 dark:text-slate-400 mt-1 max-w-[210px] mx-auto leading-relaxed">{{ scannedData.scan_message }}</p>
            <p class="text-[8px] text-slate-450 dark:text-slate-605 font-mono mt-1.5">{{ scannedData.scan_time }}</p>
          </div>
          <div v-else class="text-center mb-4 min-h-[60px]">
            <p class="text-xs font-bold text-slate-805 dark:text-white font-mono">SILAKAN TAP RFID KENDARAAN</p>
            <p class="text-[9px] text-slate-500 dark:text-slate-500 mt-1 leading-relaxed">Dekatkan kartu RFID ke scanner di samping timbangan</p>
          </div>

          <!-- Input simulation controls -->
          <div class="w-full space-y-3 mt-auto">
            <div class="text-xs">
              <label class="text-[8px] font-mono text-slate-400 dark:text-slate-505 uppercase tracking-wider mb-1 block">Simulasi Sensor Tap RFID (Ketik Plat & Enter)</label>
              <div class="relative">
                <input
                  id="rfid-manual-input"
                  v-model="rfidInputText"
                  type="text"
                  placeholder="Contoh: B 3604 XJ atau RFID-..."
                  class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-lg pl-8 pr-3 py-2 text-xs text-slate-800 dark:text-white focus:ring-emerald-500/40 focus:border-emerald-500/40 font-mono placeholder:text-slate-400 dark:placeholder:text-slate-700 outline-none"
                />
                <Scan class="absolute left-2.5 top-2.5 w-3.5 h-3.5 text-slate-400 dark:text-slate-505" />
              </div>
            </div>

            <div class="text-xs">
              <label class="text-[8px] font-mono text-slate-400 dark:text-slate-505 uppercase tracking-wider mb-1 block">Pilih DO Manual</label>
              <select v-model="selectedDoId"
                @change="() => scannedData = null"
                class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-lg px-2.5 py-2 text-xs text-slate-850 dark:text-white focus:ring-emerald-500/40 focus:border-emerald-500/40 font-mono outline-none">
                <option value="" class="bg-white dark:bg-slate-950 text-slate-800 dark:text-white">-- Pilih Antrean Timbang --</option>
                <option v-for="d in deliveryOrders" :key="d.id" :value="d.id" class="bg-white dark:bg-slate-955 text-slate-850 dark:text-white">
                  [{{ d.status?.toUpperCase() }}] {{ d.vehicle_number || d.vehicle?.license_plate || 'N/A' }}
                </option>
              </select>
            </div>

            <button @click="triggerScan" :disabled="!selectedDoId || processing"
              class="w-full py-2.5 rounded-xl text-xs font-bold font-mono tracking-wider transition-all disabled:opacity-40 disabled:cursor-not-allowed shadow-sm flex items-center justify-center gap-1.5 border-0 cursor-pointer text-white"
              :class="scanAction === 'in'
                ? 'bg-gradient-to-r from-cyan-600 to-cyan-555'
                : (scanAction === 'out'
                  ? 'bg-gradient-to-r from-emerald-600 to-emerald-555'
                  : 'bg-gradient-to-r from-cyan-600 to-blue-600')">
              <component :is="processing ? RefreshCw : (scanAction === 'in' ? LogIn : (scanAction === 'out' ? LogOut : Sparkles))"
                class="w-3.5 h-3.5" :class="processing ? 'animate-spin' : ''" />
              {{ processing ? 'MEMPROSES...' : (scanAction === 'in' ? '⚡ SIMULASI TIMBANG MASUK' : (scanAction === 'out' ? '⚡ SIMULASI TIMBANG KELUAR' : '⚡ SIMULASI TIMBANG AUTO')) }}
            </button>
          </div>
        </div>
      </section>

    </main>

    <!-- ======= BOTTOM TABLE: WEIGHT LOG HISTORY ======= -->
    <section class="relative z-10 p-6 pt-0">
      <div class="bg-white/90 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800/80 rounded-2xl overflow-hidden shadow-sm dark:shadow-xl">
        <div class="px-4 py-3 bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 flex items-center gap-2">
          <History class="w-4 h-4 text-emerald-650 dark:text-emerald-400" />
          <h3 class="text-xs font-bold tracking-wider font-mono text-slate-805 dark:text-white uppercase">Riwayat Aktivitas Timbangan</h3>
        </div>
        
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs font-mono">
            <thead class="bg-slate-100/50 dark:bg-slate-950/40 text-slate-500 border-b border-slate-200 dark:border-slate-800 uppercase text-[9px] tracking-wider">
              <tr>
                <th class="px-4 py-2.5">Waktu Scan</th>
                <th class="px-4 py-2.5">Tag RFID</th>
                <th class="px-4 py-2.5">Armada / Kendaraan</th>
                <th class="px-4 py-2.5">Driver</th>
                <th class="px-4 py-2.5">No. DO</th>
                <th class="px-4 py-2.5">Timbangan (Kg)</th>
                <th class="px-4 py-2.5">Tipe Sensor</th>
                <th class="px-4 py-2.5">Status Timbang</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-slate-700 dark:text-slate-300">
              <tr v-for="log in scans" :key="log.id" class="hover:bg-slate-50 dark:hover:bg-slate-900/30 transition-colors">
                <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ new Date(log.created_at).toLocaleString('id-ID') }}</td>
                <td class="px-4 py-3 text-slate-400 dark:text-slate-500">{{ log.tag_id }}</td>
                <td class="px-4 py-3 font-semibold text-slate-800 dark:text-slate-200">
                  {{ log.delivery_order?.vehicle_number || log.delivery_order?.vehicle?.license_plate || 'TANPA PLAT' }}
                </td>
                <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ log.delivery_order?.driver_name || '-' }}</td>
                <td class="px-4 py-3 text-cyan-705 dark:text-cyan-400 font-bold">{{ log.delivery_order?.do_number || '-' }}</td>
                <td class="px-4 py-3 font-bold" :class="log.reader_id === 'weighbridge_in' ? 'text-cyan-705 dark:text-cyan-400' : 'text-emerald-705 dark:text-emerald-400'">
                  {{ formatWeight(log.simulated_weight) }}
                </td>
                <td class="px-4 py-3">
                  <span class="px-2 py-0.5 rounded text-[8px] font-bold border" :class="log.reader_id === 'weighbridge_in' ? 'bg-cyan-50 text-cyan-700 border-cyan-200 dark:bg-cyan-950 dark:text-cyan-400 dark:border-cyan-900' : 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950 dark:text-emerald-400 dark:border-emerald-900'">
                    {{ log.reader_id === 'weighbridge_in' ? 'SCALE-IN (Tare)' : 'SCALE-OUT (Gross)' }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <span class="px-2 py-0.5 rounded text-[8px] font-bold border"
                    :class="log.status === 'success' ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950 dark:text-emerald-400' : log.status === 'warning' ? 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-955 dark:text-amber-400' : 'bg-red-50 text-red-700 border-red-200 dark:bg-red-955 dark:text-red-400'">
                    {{ log.status?.toUpperCase() }}
                  </span>
                </td>
              </tr>
              <tr v-if="scans.length === 0">
                <td colspan="8" class="text-center py-8 text-slate-450 dark:text-slate-600">BELUM ADA RIWAYAT PENIMBANGAN</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
/* Glowing styles */
input[type="range"]::-webkit-slider-thumb {
  box-shadow: 0 0 10px rgba(52, 211, 153, 0.5);
}
</style>
