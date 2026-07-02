<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import {
  Shield,
  Truck,
  User,
  Package,
  Scan,
  Clock,
  CheckCircle,
  XCircle,
  AlertTriangle,
  ArrowLeft,
  Phone,
  FileText,
  RefreshCw,
  Volume2,
  VolumeX,
  ChevronDown,
  Printer,
  Radio,
  ShieldCheck,
  ShieldAlert,
  ShieldX,
  Wifi,
  LogIn,
  LogOut,
  Sparkles
} from 'lucide-vue-next';

const props = defineProps({
  deliveryOrders: { type: Array, default: () => [] },
  scans: { type: Array, default: () => [] }
});

const page = usePage();

// State
const selectedDoId = ref('');
const scanAction = ref('smart');
const rfidInputText = ref('');
const processing = ref(false);
const isMuted = ref(false);
const lastSync = ref(new Date().toLocaleTimeString());
let refreshTimer = null;

// Scanned vehicle data (populated after RFID tap)
const scannedData = ref(null);
const showCompliancePanel = ref(false);

// RFID keyboard buffer for USB reader
const rfidBuffer = ref('');
let rfidTimeout = null;

// Computed
const selectedDo = computed(() => {
  if (scannedData.value?.delivery_order) return scannedData.value.delivery_order;
  return props.deliveryOrders.find(d => d.id == selectedDoId.value) || null;
});

const vehiclePhoto = computed(() => scannedData.value?.vehicle_photo || selectedDo.value?.vehicle?.vehicle_photo_url || null);
const driverPhoto = computed(() => scannedData.value?.driver_photo || selectedDo.value?.vehicle?.driver_photo_url || null);
const compliance = computed(() => scannedData.value?.compliance || null);

const queueOrders = computed(() =>
  props.deliveryOrders.filter(d => ['draft', 'picking'].includes(d.status))
);

const activeLoadCount = computed(() =>
  props.deliveryOrders.filter(d => d.status === 'picking').length
);

// Sound
const playSound = (type = 'success') => {
  if (isMuted.value || typeof window === 'undefined') return;
  try {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.connect(gain);
    gain.connect(ctx.destination);
    if (type === 'success') {
      osc.type = 'sine';
      osc.frequency.setValueAtTime(1200, ctx.currentTime);
      osc.frequency.setValueAtTime(1600, ctx.currentTime + 0.08);
      gain.gain.setValueAtTime(0.12, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.2);
      osc.start(); osc.stop(ctx.currentTime + 0.2);
    } else if (type === 'error') {
      osc.type = 'sawtooth';
      osc.frequency.setValueAtTime(150, ctx.currentTime);
      gain.gain.setValueAtTime(0.15, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.5);
      osc.start(); osc.stop(ctx.currentTime + 0.5);
    } else {
      osc.type = 'triangle';
      osc.frequency.setValueAtTime(600, ctx.currentTime);
      gain.gain.setValueAtTime(0.1, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.15);
      osc.start(); osc.stop(ctx.currentTime + 0.15);
    }
  } catch (e) { /* silent */ }
};

// Text to Speech
const speakMessage = (text) => {
  if (isMuted.value || typeof window === 'undefined' || !window.speechSynthesis) return;
  try {
    window.speechSynthesis.cancel(); // stop current speech
    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = 'id-ID';
    utterance.rate = 0.95; // slightly slower for clearer output
    utterance.pitch = 1.0;
    
    // Attempt to load Indonesian voice
    const voices = window.speechSynthesis.getVoices();
    const idVoice = voices.find(v => v.lang.includes('id') || v.lang.includes('ID'));
    if (idVoice) {
      utterance.voice = idVoice;
    }
    window.speechSynthesis.speak(utterance);
  } catch (e) { /* silent */ }
};

// Trigger scan
const triggerScan = () => {
  if (!selectedDoId.value) {
    playSound('error');
    return;
  }
  processing.value = true;
  router.post(route('warehouse.security-gate.scan'), {
    delivery_order_id: selectedDoId.value,
    action: scanAction.value,
  }, {
    preserveScroll: true,
    onSuccess: (p) => {
      const flash = p.props.flash || {};
      const data = flash.scannedData;
      if (data) {
        scannedData.value = data;
        showCompliancePanel.value = true;
      }
      if (flash.error) {
        playSound('error');
        speakMessage('Gagal memproses. Akses ditolak.');
      } else if (flash.warning) {
        playSound('warning');
        speakMessage('Perhatian. Dokumen kendaraan kedaluwarsa.');
      } else {
        playSound('success');
        // Speak voice greeting based on the action determined by the server
        const actionDone = data?.scan_action;
        if (actionDone === 'entry') {
          speakMessage('Silakan masuk');
        } else if (actionDone === 'exit') {
          speakMessage('Selamat jalan');
        }
      }
    },
    onError: () => {
      playSound('error');
      speakMessage('Gagal memproses.');
    },
    onFinish: () => processing.value = false,
  });
};

// Manual DO select
const selectDO = (doItem) => {
  selectedDoId.value = doItem.id;
  scannedData.value = null;
  showCompliancePanel.value = false;
  // Auto-suggest scan action
  if (doItem.status === 'draft') scanAction.value = 'entry';
  else if (doItem.status === 'packed') scanAction.value = 'exit';
};

// RFID USB Reader keyboard listener
const handleKeyDown = (e) => {
  // Allow Enter inside input if it's the scan input
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

const processRfidTag = (tag) => {
  // Tag format: RFID-TRUCK-B1234ABC or just B1234ABC or raw card UID
  const cleanedInput = tag.replace('RFID-TRUCK-', '').replace(/[\s-]/g, '').toUpperCase();
  
  // Find DO by matching vehicle plate or registered rfid_card (remove spaces & hyphens for comparison)
  const match = props.deliveryOrders.find(d => {
    const doPlate = (d.vehicle_number || d.vehicle?.license_plate || '').replace(/[\s-]/g, '').toUpperCase();
    const doRfid = (d.vehicle?.rfid_card || '').replace(/[\s-]/g, '').toUpperCase();
    return doPlate === cleanedInput || (doRfid && doRfid === cleanedInput);
  });

  if (match) {
    selectDO(match);
    // Automatically trigger the scan API call after selecting it
    setTimeout(() => triggerScan(), 100);
  } else {
    playSound('error');
    // Set a dummy flash error for feedback
    scannedData.value = {
      scan_status: 'error',
      scan_message: `❌ Tag/Plat "${tag}" tidak ditemukan dalam antrean aktif.`,
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

// Compliance helpers
const complianceStatusClass = (status) => {
  switch (status) {
    case 'active': return 'text-emerald-400 bg-emerald-950/40 border-emerald-500/30';
    case 'near_expiry': return 'text-amber-400 bg-amber-950/40 border-amber-500/30';
    case 'expired': return 'text-red-400 bg-red-950/40 border-red-500/30';
    default: return 'text-slate-400 bg-slate-800/40 border-slate-700';
  }
};
const complianceLabel = (status) => {
  switch (status) {
    case 'active': return 'AKTIF';
    case 'near_expiry': return 'SEGERA EXPIRED';
    case 'expired': return 'EXPIRED';
    default: return 'TIDAK ADA DATA';
  }
};

const statusColor = (status) => {
  switch (status) {
    case 'draft': return 'bg-slate-700 text-slate-300';
    case 'picking': return 'bg-amber-500/20 text-amber-400 border border-amber-500/30';
    case 'packed': return 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30';
    case 'shipped': return 'bg-blue-500/20 text-blue-400 border border-blue-500/30';
    default: return 'bg-slate-700 text-slate-300';
  }
};

const statusLabel = (s) => {
  switch (s) {
    case 'draft': return 'MENUNGGU';
    case 'picking': return 'LOADING';
    case 'packed': return 'SIAP KIRIM';
    case 'shipped': return 'DALAM PERJALANAN';
    default: return s?.toUpperCase();
  }
};

// Auto-refresh
const startPolling = () => {
  refreshTimer = setInterval(() => {
    router.reload({
      only: ['deliveryOrders', 'scans'],
      onSuccess: () => { lastSync.value = new Date().toLocaleTimeString(); }
    });
  }, 10000);
};

// Check flash on page load
watch(() => page.props.flash, (flash) => {
  if (flash?.scannedData) {
    scannedData.value = flash.scannedData;
    showCompliancePanel.value = true;
  }
}, { deep: true, immediate: true });

onMounted(() => {
  window.addEventListener('keydown', handleKeyDown);
  startPolling();
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyDown);
  if (refreshTimer) clearInterval(refreshTimer);
  clearTimeout(rfidTimeout);
});
</script>

<template>
  <Head title="Security Gate — RFID Dashboard Pos Satpam" />

  <div class="min-h-screen bg-slate-950 text-slate-100 font-sans antialiased overflow-x-hidden relative">
    <!-- Background grid -->
    <div class="absolute inset-0 bg-[linear-gradient(to_right,#0a1628_1px,transparent_1px),linear-gradient(to_bottom,#0a1628_1px,transparent_1px)] bg-[size:3rem_3rem] pointer-events-none opacity-30"></div>
    <div class="absolute top-[-15%] left-[-5%] w-[45%] h-[45%] bg-emerald-900/10 rounded-full blur-[120px] pointer-events-none"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-blue-950/15 rounded-full blur-[100px] pointer-events-none"></div>

    <!-- Header -->
    <header class="border-b border-emerald-500/20 bg-slate-900/60 backdrop-blur-md sticky top-0 z-30 px-4 py-3 shadow-[0_4px_30px_rgba(0,0,0,0.5)]">
      <div class="max-w-[1600px] mx-auto flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <a :href="route('warehouse.loading.index')" class="text-xs font-mono border border-slate-700 hover:border-emerald-500/50 text-slate-300 px-3 py-1.5 rounded-lg transition-all flex items-center gap-1.5">
            <ArrowLeft class="w-3.5 h-3.5" /> Kembali
          </a>
          <div class="h-6 w-px bg-slate-700"></div>
          <div class="flex items-center gap-2">
            <div class="w-9 h-9 rounded-lg border border-emerald-400 bg-emerald-950/50 flex items-center justify-center shadow-[0_0_15px_rgba(52,211,153,0.3)]">
              <Shield class="w-5 h-5 text-emerald-400 animate-pulse" />
            </div>
            <div>
              <h1 class="text-lg font-bold tracking-wider text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 via-cyan-300 to-emerald-200">
                SECURITY GATE DASHBOARD
              </h1>
              <p class="text-[10px] text-emerald-500/60 uppercase tracking-widest font-mono">Pos Satpam — RFID Entry & Exit Control</p>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <!-- Sound toggle -->
          <button @click="isMuted = !isMuted"
            class="flex items-center gap-1.5 text-xs font-mono px-3 py-1.5 rounded-lg border transition-all"
            :class="isMuted ? 'border-red-500/40 bg-red-950/20 text-red-400' : 'border-emerald-500/30 bg-emerald-950/20 text-emerald-400'">
            <component :is="isMuted ? VolumeX : Volume2" class="w-3.5 h-3.5" />
            {{ isMuted ? 'MUTE' : 'SOUND' }}
          </button>
          <!-- Sync -->
          <div class="text-right hidden md:block">
            <span class="text-[9px] font-mono text-slate-500 block">LIVE SYNC</span>
            <span class="text-[10px] font-mono text-emerald-400 flex items-center gap-1">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span>
              {{ lastSync }}
            </span>
          </div>
        </div>
      </div>
    </header>

    <main class="max-w-[1600px] mx-auto p-4 md:p-6 space-y-5 relative z-10">

      <!-- ==================== TOP 3-COLUMN GRID ==================== -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

        <!-- ======= LEFT: INFORMASI ARMADA & DRIVER (col 4) ======= -->
        <div class="lg:col-span-4 bg-slate-900/70 border border-slate-800 rounded-2xl backdrop-blur-md overflow-hidden shadow-xl flex flex-col">
          <div class="px-4 py-3 bg-slate-900 border-b border-slate-800 flex items-center justify-between">
            <h3 class="text-xs font-bold tracking-wider font-mono text-emerald-400 uppercase flex items-center gap-1.5">
              <Truck class="w-3.5 h-3.5" /> Informasi Armada & Driver
            </h3>
            <span v-if="selectedDo" class="text-[9px] font-mono px-2 py-0.5 rounded-full" :class="statusColor(selectedDo.status)">
              {{ statusLabel(selectedDo.status) }}
            </span>
          </div>

          <div class="p-4 flex-1 flex flex-col">
            <!-- Vehicle Photo -->
            <div class="relative rounded-xl overflow-hidden bg-slate-950 border border-slate-800 mb-4" style="height: 220px;">
              <img v-if="vehiclePhoto" :src="vehiclePhoto" class="w-full h-full object-cover" alt="Vehicle" />
              <div v-else class="w-full h-full flex items-center justify-center">
                <Truck class="w-16 h-16 text-slate-700" />
              </div>
              <!-- Plate overlay -->
              <div v-if="selectedDo" class="absolute bottom-3 left-3 px-3 py-1.5 bg-slate-900/90 backdrop-blur-sm border border-emerald-500/40 rounded-lg shadow-lg">
                <span class="text-sm font-mono font-black text-emerald-300 tracking-wider">
                  {{ selectedDo.vehicle_number || selectedDo.vehicle?.license_plate || 'N/A' }}
                </span>
              </div>
              <!-- Type badge -->
              <div v-if="selectedDo" class="absolute top-3 right-3 px-2 py-1 bg-slate-900/80 backdrop-blur-sm border border-slate-700 rounded text-[9px] font-mono text-slate-400 uppercase">
                {{ selectedDo.vehicle?.vehicle_type || 'Truk' }}
              </div>
            </div>

            <!-- Driver Info -->
            <div v-if="selectedDo" class="flex items-center gap-3 bg-slate-950/60 border border-slate-800 rounded-xl p-3">
              <div class="w-12 h-12 rounded-full border-2 border-emerald-500/40 overflow-hidden bg-slate-800 shrink-0 shadow-[0_0_10px_rgba(52,211,153,0.2)]">
                <img v-if="driverPhoto" :src="driverPhoto" class="w-full h-full object-cover" alt="Driver" />
                <div v-else class="w-full h-full flex items-center justify-center">
                  <User class="w-6 h-6 text-slate-600" />
                </div>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-[9px] font-mono text-slate-500 uppercase tracking-wider">Driver / Pengemudi</p>
                <p class="text-sm font-bold text-white truncate">{{ selectedDo.driver_name || 'Tidak diketahui' }}</p>
                <p v-if="selectedDo.vehicle?.driver_name" class="text-[10px] text-slate-400">
                  <Phone class="w-3 h-3 inline text-slate-500" /> {{ selectedDo.driver_name }}
                </p>
              </div>
              <!-- WA quick call -->
              <a v-if="selectedDo.driver_name" href="#" class="w-8 h-8 rounded-full bg-emerald-600/20 border border-emerald-500/30 flex items-center justify-center hover:bg-emerald-600/40 transition-all">
                <Phone class="w-4 h-4 text-emerald-400" />
              </a>
            </div>

            <!-- Empty state -->
            <div v-else class="flex-1 flex flex-col items-center justify-center text-center py-8">
              <Truck class="w-12 h-12 text-slate-700 mb-3" />
              <p class="text-sm text-slate-500">Pilih armada atau tap kartu RFID</p>
              <p class="text-[10px] text-slate-600 mt-1">Data kendaraan akan tampil di sini</p>
            </div>

            <!-- Compliance summary -->
            <div v-if="compliance && showCompliancePanel" class="mt-4 space-y-2">
              <h4 class="text-[10px] font-mono text-slate-500 uppercase tracking-wider flex items-center gap-1">
                <FileText class="w-3 h-3" /> Status Dokumen Kendaraan
              </h4>
              <!-- STNK -->
              <div class="flex items-center justify-between px-3 py-2 rounded-lg border" :class="complianceStatusClass(compliance.stnk?.status)">
                <div>
                  <span class="text-[9px] font-mono uppercase tracking-wider opacity-70">STNK</span>
                  <p class="text-xs font-bold">{{ compliance.stnk?.number || 'N/A' }}</p>
                </div>
                <div class="text-right">
                  <span class="text-[9px] font-mono block">{{ compliance.stnk?.expiry ? new Date(compliance.stnk.expiry).toLocaleDateString('id-ID') : '-' }}</span>
                  <span class="text-[9px] font-bold">{{ complianceLabel(compliance.stnk?.status) }}</span>
                </div>
                <component :is="compliance.stnk?.status === 'active' ? ShieldCheck : compliance.stnk?.status === 'expired' ? ShieldX : ShieldAlert"
                  class="w-5 h-5 shrink-0 ml-2" />
              </div>
              <!-- KIR -->
              <div class="flex items-center justify-between px-3 py-2 rounded-lg border" :class="complianceStatusClass(compliance.kir?.status)">
                <div>
                  <span class="text-[9px] font-mono uppercase tracking-wider opacity-70">KIR</span>
                  <p class="text-xs font-bold">{{ compliance.kir?.number || 'N/A' }}</p>
                </div>
                <div class="text-right">
                  <span class="text-[9px] font-mono block">{{ compliance.kir?.expiry ? new Date(compliance.kir.expiry).toLocaleDateString('id-ID') : '-' }}</span>
                  <span class="text-[9px] font-bold">{{ complianceLabel(compliance.kir?.status) }}</span>
                </div>
                <component :is="compliance.kir?.status === 'active' ? ShieldCheck : compliance.kir?.status === 'expired' ? ShieldX : ShieldAlert"
                  class="w-5 h-5 shrink-0 ml-2" />
              </div>
              <!-- Alert if any expired -->
              <div v-if="compliance.stnk?.status === 'expired' || compliance.kir?.status === 'expired'"
                class="bg-red-950/40 border border-red-500/30 rounded-lg p-3 text-center">
                <XCircle class="w-5 h-5 text-red-400 mx-auto mb-1" />
                <p class="text-xs font-bold text-red-400">⚠️ DOKUMEN EXPIRED</p>
                <p class="text-[10px] text-red-400/70">Kendaraan ini memiliki dokumen yang sudah kadaluarsa. Mohon segera diperbarui.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- ======= CENTER: DAFTAR MUATAN CARGO (col 4) ======= -->
        <div class="lg:col-span-4 bg-slate-900/70 border border-slate-800 rounded-2xl backdrop-blur-md overflow-hidden shadow-xl flex flex-col">
          <div class="px-4 py-3 bg-slate-900 border-b border-slate-800 flex items-center justify-between">
            <h3 class="text-xs font-bold tracking-wider font-mono text-cyan-400 uppercase flex items-center gap-1.5">
              <Package class="w-3.5 h-3.5" /> Daftar Muatan Cargo
            </h3>
            <button v-if="selectedDo" class="text-[9px] font-mono border border-cyan-500/30 text-cyan-400 px-2.5 py-1 rounded-lg hover:bg-cyan-950/30 transition-all flex items-center gap-1">
              <Printer class="w-3 h-3" /> Print DO
            </button>
          </div>

          <div class="p-4 flex-1 flex flex-col">
            <template v-if="selectedDo">
              <!-- Customer -->
              <div class="bg-slate-950/60 border border-slate-800 rounded-xl p-3 mb-3 space-y-1">
                <span class="text-[9px] font-mono text-slate-500 uppercase tracking-wider">Customer / Vendor</span>
                <p class="text-sm font-bold text-white">{{ selectedDo.customer?.name || 'N/A' }}</p>
                <span class="text-[9px] font-mono text-slate-500 uppercase tracking-wider mt-2 block">Tujuan Pengiriman</span>
                <p class="text-xs text-slate-300">{{ selectedDo.shipping_address || 'Belum ditentukan' }}</p>
              </div>

              <!-- Items -->
              <div class="space-y-2 flex-1">
                <span class="text-[9px] font-mono text-slate-500 uppercase tracking-wider">Item Muatan</span>
                <div v-for="item in selectedDo.items" :key="item.id"
                  class="flex items-center justify-between bg-slate-950/40 border border-slate-800/60 rounded-lg px-3 py-2.5 hover:border-cyan-500/20 transition-colors">
                  <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold text-slate-200 truncate">{{ item.product?.name || 'Produk' }}</p>
                    <p class="text-[10px] text-slate-500 font-mono">{{ item.product?.sku || '' }}</p>
                  </div>
                  <div class="flex items-center gap-2">
                    <span class="text-xs font-mono font-bold px-2 py-0.5 rounded-md"
                      :class="(item.qty_loaded || 0) >= item.qty_ordered
                        ? 'text-emerald-400 bg-emerald-950/40 border border-emerald-500/20'
                        : 'text-cyan-400 bg-cyan-950/40 border border-cyan-500/20'">
                      {{ item.qty_loaded || 0 }} / {{ item.qty_ordered }} {{ item.unit?.code || 'pcs' }}
                    </span>
                  </div>
                </div>

                <div v-if="!selectedDo.items || selectedDo.items.length === 0"
                  class="text-center py-6 text-slate-600 text-xs font-mono">
                  TIDAK ADA ITEM MUATAN
                </div>
              </div>

              <!-- DO number footer -->
              <div class="mt-4 pt-3 border-t border-slate-800 flex items-center justify-between text-[10px] font-mono text-slate-500">
                <span>DO: <span class="text-slate-300 font-bold">{{ selectedDo.do_number }}</span></span>
                <span>{{ selectedDo.delivery_date ? new Date(selectedDo.delivery_date).toLocaleDateString('id-ID') : '-' }}</span>
              </div>
            </template>

            <!-- Empty -->
            <div v-else class="flex-1 flex flex-col items-center justify-center text-center py-8">
              <Package class="w-12 h-12 text-slate-700 mb-3" />
              <p class="text-sm text-slate-500">Belum ada muatan terpilih</p>
              <p class="text-[10px] text-slate-600 mt-1">Daftar item akan tampil setelah armada terdeteksi</p>
            </div>
          </div>
        </div>

        <!-- ======= RIGHT: TAP SENSOR RFID (col 4) ======= -->
        <div class="lg:col-span-4 bg-slate-900/70 border border-slate-800 rounded-2xl backdrop-blur-md overflow-hidden shadow-xl flex flex-col">
          <div class="px-4 py-3 bg-slate-900 border-b border-slate-800 flex items-center justify-between">
            <h3 class="text-xs font-bold tracking-wider font-mono text-blue-400 uppercase flex items-center gap-1.5">
              <Wifi class="w-3.5 h-3.5" /> TAP Sensor RFID
            </h3>
            <!-- Mode toggle -->
            <div class="flex border border-slate-700 rounded-lg overflow-hidden">
              <button @click="scanAction = 'smart'"
                class="text-[9px] font-mono font-bold px-2.5 py-1 transition-all flex items-center gap-1"
                :class="scanAction === 'smart' ? 'bg-gradient-to-r from-cyan-600 to-blue-600 text-white shadow-lg' : 'bg-slate-800 text-slate-400 hover:text-white'">
                <Sparkles class="w-3 h-3 text-cyan-300 animate-pulse" /> AUTO
              </button>
              <button @click="scanAction = 'entry'"
                class="text-[9px] font-mono font-bold px-2.5 py-1 transition-all flex items-center gap-1"
                :class="scanAction === 'entry' ? 'bg-emerald-600 text-white' : 'bg-slate-800 text-slate-400 hover:text-white'">
                <LogIn class="w-3 h-3" /> MASUK
              </button>
              <button @click="scanAction = 'exit'"
                class="text-[9px] font-mono font-bold px-2.5 py-1 transition-all flex items-center gap-1"
                :class="scanAction === 'exit' ? 'bg-blue-600 text-white' : 'bg-slate-800 text-slate-400 hover:text-white'">
                <LogOut class="w-3 h-3" /> KELUAR
              </button>
            </div>
          </div>

          <div class="p-4 flex-1 flex flex-col items-center justify-center">
            <!-- RFID Tap Animation -->
            <div class="relative mb-6">
              <div class="w-28 h-28 rounded-full border-2 flex items-center justify-center transition-all duration-500"
                :class="scannedData
                  ? (scannedData.scan_status === 'success' ? 'border-emerald-400 bg-emerald-950/30 shadow-[0_0_30px_rgba(52,211,153,0.3)]'
                    : scannedData.scan_status === 'error' ? 'border-red-400 bg-red-950/30 shadow-[0_0_30px_rgba(239,68,68,0.3)]'
                    : 'border-amber-400 bg-amber-950/30 shadow-[0_0_30px_rgba(245,158,11,0.3)]')
                  : 'border-blue-400/50 bg-blue-950/20 shadow-[0_0_20px_rgba(59,130,246,0.15)]'">
                <component :is="scannedData
                  ? (scannedData.scan_status === 'success' ? CheckCircle : scannedData.scan_status === 'error' ? XCircle : AlertTriangle)
                  : Scan"
                  class="w-12 h-12 transition-all"
                  :class="scannedData
                    ? (scannedData.scan_status === 'success' ? 'text-emerald-400' : scannedData.scan_status === 'error' ? 'text-red-400' : 'text-amber-400')
                    : 'text-blue-400 animate-pulse'" />
              </div>
              <!-- Ping ring -->
              <div v-if="!scannedData" class="absolute inset-0 w-28 h-28 rounded-full border-2 border-blue-400/30 animate-ping"></div>
            </div>

            <!-- Status text -->
            <div v-if="scannedData" class="text-center mb-4">
              <p class="text-sm font-bold" :class="scannedData.scan_status === 'success' ? 'text-emerald-400' : scannedData.scan_status === 'error' ? 'text-red-400' : 'text-amber-400'">
                {{ scannedData.scan_status === 'success' ? '✅ Scan Berhasil' : scannedData.scan_status === 'error' ? '❌ Scan Gagal' : '⚠️ Peringatan' }}
              </p>
              <p class="text-[10px] text-slate-400 mt-1 max-w-[250px]">{{ scannedData.scan_message }}</p>
              <p class="text-[9px] text-slate-600 font-mono mt-2">{{ scannedData.scan_time }}</p>
            </div>
            <div v-else class="text-center mb-4">
              <p class="text-base font-bold text-white">Silakan Tap Kartu RFID Truk</p>
              <p class="text-xs text-slate-400 mt-1">
                Dekatkan kartu ke USB Reader untuk {{ scanAction === 'entry' ? 'Masuk' : (scanAction === 'exit' ? 'Keluar' : 'Proses Otomatis') }}
              </p>
            </div>

            <!-- Manual select & trigger -->
            <div class="w-full space-y-3 mt-auto">
              <!-- Direct Scan Input Box -->
              <div>
                <label class="text-[9px] font-mono text-slate-500 uppercase tracking-wider mb-1 block">Simulasi Scan (Ketik Plat / Tag RFID & Tekan Enter)</label>
                <div class="relative">
                  <input
                    id="rfid-manual-input"
                    v-model="rfidInputText"
                    type="text"
                    placeholder="Contoh: B 9123 SFX atau RFID-TRUCK-..."
                    class="w-full bg-slate-950 border border-slate-700 rounded-lg pl-8 pr-3 py-2 text-xs text-white focus:ring-blue-500/50 focus:border-blue-500/50 font-mono placeholder:text-slate-600"
                  />
                  <Scan class="absolute left-2.5 top-2.5 w-3.5 h-3.5 text-slate-500" />
                </div>
              </div>

              <div>
                <label class="text-[9px] font-mono text-slate-500 uppercase tracking-wider mb-1 block">Pilih DO Manual (Alternatif)</label>
                <select v-model="selectedDoId"
                  @change="() => { scannedData = null; showCompliancePanel = false; }"
                  class="w-full bg-slate-950 border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:ring-blue-500/50 focus:border-blue-500/50 font-mono">
                  <option value="">-- Pilih Delivery Order --</option>
                  <option v-for="d in deliveryOrders" :key="d.id" :value="d.id">
                    [{{ d.status?.toUpperCase() }}] {{ d.vehicle_number || d.vehicle?.license_plate || 'N/A' }} — {{ d.do_number }} ({{ d.driver_name || 'No Driver' }})
                  </option>
                </select>
              </div>

              <button @click="triggerScan" :disabled="!selectedDoId || processing"
                class="w-full py-3 rounded-xl text-sm font-bold font-mono tracking-wider transition-all shadow-lg flex items-center justify-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed"
                :class="scanAction === 'entry'
                  ? 'bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-500 hover:to-emerald-400 text-white shadow-emerald-500/20'
                  : (scanAction === 'exit'
                    ? 'bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-500 hover:to-blue-400 text-white shadow-blue-500/20'
                    : 'bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white shadow-blue-500/20')">
                <component :is="processing ? RefreshCw : (scanAction === 'entry' ? LogIn : (scanAction === 'exit' ? LogOut : Sparkles))"
                  class="w-4 h-4" :class="processing ? 'animate-spin' : ''" />
                {{ processing ? 'MEMPROSES...' : (scanAction === 'entry' ? '⚡ SIMULASI TAP MASUK' : (scanAction === 'exit' ? '⚡ SIMULASI TAP KELUAR' : '⚡ SIMULASI TAP OTOMATIS')) }}
              </button>
            </div>

            <!-- Footer note -->
            <div class="mt-4 pt-3 border-t border-slate-800/60 text-center w-full">
              <p class="text-[9px] text-slate-600 font-mono flex items-center justify-center gap-1.5">
                <Radio class="w-3 h-3 text-blue-500 animate-pulse" />
                LAYAR INI DILOCK PADA MOUSE FOCUS — Cukup dekatkan kartu RFID Anda ke USB Reader Anda.
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- ==================== BOTTOM: ANTREAN KENDARAAN ==================== -->
      <div class="bg-slate-900/70 border border-slate-800 rounded-2xl backdrop-blur-md overflow-hidden shadow-xl">
        <div class="px-4 py-3 bg-slate-900 border-b border-slate-800 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <h3 class="text-xs font-bold tracking-wider font-mono text-indigo-400 uppercase flex items-center gap-1.5">
              <Clock class="w-3.5 h-3.5" /> Antrean Kendaraan
            </h3>
            <span class="text-[9px] font-mono text-slate-500">— Jadwal kendaraan berikutnya yang dikonfirmasi untuk muat/bongkar barang</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="px-2 py-0.5 bg-indigo-950/60 border border-indigo-800 text-indigo-400 text-[9px] font-mono rounded-full">
              {{ queueOrders.length }} Kendaraan
            </span>
            <span class="px-2 py-0.5 bg-amber-950/60 border border-amber-800 text-amber-400 text-[9px] font-mono rounded-full">
              {{ activeLoadCount }} Loading Aktif
            </span>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-xs">
            <thead>
              <tr class="border-b border-slate-800/60 bg-slate-950/40">
                <th class="py-3 px-4 text-left font-mono text-[9px] text-slate-500 uppercase tracking-wider">Waktu Jadwal</th>
                <th class="py-3 px-4 text-left font-mono text-[9px] text-slate-500 uppercase tracking-wider">Kendaraan</th>
                <th class="py-3 px-4 text-left font-mono text-[9px] text-slate-500 uppercase tracking-wider">Nama Driver</th>
                <th class="py-3 px-4 text-left font-mono text-[9px] text-slate-500 uppercase tracking-wider">Customer</th>
                <th class="py-3 px-4 text-left font-mono text-[9px] text-slate-500 uppercase tracking-wider">Gudang</th>
                <th class="py-3 px-4 text-left font-mono text-[9px] text-slate-500 uppercase tracking-wider">No. DO</th>
                <th class="py-3 px-4 text-center font-mono text-[9px] text-slate-500 uppercase tracking-wider">Status Antrean</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="order in queueOrders" :key="order.id"
                class="border-b border-slate-800/30 hover:bg-slate-800/30 cursor-pointer transition-colors"
                :class="selectedDoId == order.id ? 'bg-emerald-950/20 border-l-2 border-l-emerald-500' : ''"
                @click="selectDO(order)">
                <td class="py-3 px-4 font-mono text-slate-300">
                  {{ order.delivery_date ? new Date(order.delivery_date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short' }) : '-' }}
                </td>
                <td class="py-3 px-4">
                  <span class="px-2 py-0.5 bg-slate-800 border border-slate-700 rounded text-[10px] font-mono font-bold text-white">
                    {{ order.vehicle_number || order.vehicle?.license_plate || 'N/A' }}
                  </span>
                </td>
                <td class="py-3 px-4 font-semibold text-slate-200">{{ order.driver_name || '-' }}</td>
                <td class="py-3 px-4 text-slate-400 truncate max-w-[200px]">{{ order.customer?.name || '-' }}</td>
                <td class="py-3 px-4 text-slate-500 font-mono text-[10px]">{{ order.warehouse?.name || '-' }}</td>
                <td class="py-3 px-4 font-mono text-slate-300">{{ order.do_number }}</td>
                <td class="py-3 px-4 text-center">
                  <span class="px-2.5 py-0.5 rounded-full text-[9px] font-mono font-bold uppercase" :class="statusColor(order.status)">
                    {{ statusLabel(order.status) }}
                  </span>
                </td>
              </tr>
              <tr v-if="queueOrders.length === 0">
                <td colspan="7" class="py-8 text-center text-slate-600 text-xs font-mono">
                  TIDAK ADA KENDARAAN DALAM ANTREAN SAAT INI
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ==================== RFID SCAN LOG ==================== -->
      <div class="bg-slate-900/70 border border-slate-800 rounded-2xl backdrop-blur-md overflow-hidden shadow-xl">
        <div class="px-4 py-3 bg-slate-900 border-b border-slate-800 flex items-center justify-between">
          <h3 class="text-xs font-bold tracking-wider font-mono text-slate-400 uppercase flex items-center gap-1.5">
            <FileText class="w-3.5 h-3.5" /> Riwayat Scan RFID Terakhir
          </h3>
          <span class="text-[9px] font-mono text-slate-600">{{ scans.length }} entri</span>
        </div>

        <div class="max-h-[250px] overflow-y-auto">
          <div v-for="scan in scans" :key="scan.id"
            class="flex items-center gap-3 px-4 py-2.5 border-b border-slate-800/30 hover:bg-slate-800/20 transition-colors text-xs">
            <div class="w-1.5 h-1.5 rounded-full shrink-0"
              :class="scan.status === 'success' ? 'bg-emerald-400' : scan.status === 'error' ? 'bg-red-400' : 'bg-amber-400'"></div>
            <span class="font-mono text-[9px] text-slate-500 w-16 shrink-0">
              {{ new Date(scan.created_at).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'}) }}
            </span>
            <span class="font-mono text-[9px] text-slate-500 w-20 shrink-0 uppercase">{{ scan.reader_id }}</span>
            <span class="font-mono text-[10px] text-slate-400 w-32 shrink-0 truncate">{{ scan.tag_id }}</span>
            <span class="text-slate-300 flex-1 truncate">{{ scan.message }}</span>
          </div>
          <div v-if="scans.length === 0" class="py-6 text-center text-slate-600 text-xs font-mono">
            BELUM ADA RIWAYAT SCAN
          </div>
        </div>
      </div>

    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-800 py-3 px-6 text-center text-[9px] font-mono text-slate-600 mt-4">
      <span>&copy; {{ new Date().getFullYear() }} USICS SECURITY GATE RFID CONTROL MODULE. DEVICE ACTIVE.</span>
    </footer>
  </div>
</template>

<style scoped>
@keyframes scanner {
  0% { top: 0; }
  50% { top: 100%; }
  100% { top: 0; }
}
.animate-scanner {
  animation: scanner 4s ease-in-out infinite;
}
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #334155;
  border-radius: 4px;
}
</style>
