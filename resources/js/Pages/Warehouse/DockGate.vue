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
  Printer,
  Radio,
  Wifi,
  Loader2,
  CheckSquare,
  Square
} from 'lucide-vue-next';

const props = defineProps({
  deliveryOrders: { type: Array, default: () => [] },
  scans: { type: Array, default: () => [] }
});

const page = usePage();

// State
const selectedBay = ref('Bay 1 Slitting');
const selectedDoId = ref('');
const rfidInputText = ref('');
const processing = ref(false);
const isMuted = ref(false);
const lastSync = ref(new Date().toLocaleTimeString());
let refreshTimer = null;

// Scanned vehicle/loading data
const scannedData = ref(null);

// RFID keyboard buffer for USB reader
const rfidBuffer = ref('');
let rfidTimeout = null;

// Loading bays option list
const bays = [
  'Bay 1 Slitting',
  'Bay 2 Slitting',
  'Bay 3 Pipe',
  'Bay 4 Packing'
];

// Computed
const filteredOrders = computed(() => {
  // Return orders that are called to this bay or currently loading in this bay
  return props.deliveryOrders.filter(d => 
    d.loading_bay === selectedBay.value && 
    ['draft', 'picking', 'packed'].includes(d.status)
  );
});

const selectedDo = computed(() => {
  if (scannedData.value?.delivery_order) return scannedData.value.delivery_order;
  return props.deliveryOrders.find(d => d.id == selectedDoId.value) || null;
});

const progressPercent = computed(() => {
  if (!selectedDo.value || !selectedDo.value.items || selectedDo.value.items.length === 0) return 0;
  const loadedCount = selectedDo.value.items.filter(item => item.is_loaded).length;
  return Math.round((loadedCount / selectedDo.value.items.length) * 100);
});

const allItemsLoaded = computed(() => {
  if (!selectedDo.value || !selectedDo.value.items || selectedDo.value.items.length === 0) return false;
  return selectedDo.value.items.every(item => item.is_loaded);
});

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
    window.speechSynthesis.cancel();
    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = 'id-ID';
    utterance.rate = 0.95;
    utterance.pitch = 1.0;
    
    const voices = window.speechSynthesis.getVoices();
    const idVoice = voices.find(v => v.lang.includes('id') || v.lang.includes('ID'));
    if (idVoice) utterance.voice = idVoice;
    
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
  router.post(route('warehouse.dock-gate.scan'), {
    delivery_order_id: selectedDoId.value,
    loading_bay: selectedBay.value,
  }, {
    preserveScroll: true,
    onSuccess: (p) => {
      const flash = p.props.flash || {};
      const data = flash.dockScannedData;
      if (data) {
        scannedData.value = data;
      }
      if (flash.warning) {
        playSound('warning');
      } else {
        playSound('success');
        if (data?.delivery_order?.status === 'picking') {
          speakMessage('Pemuatan dimulai. Silakan muat barang.');
        }
      }
    },
    onError: () => playSound('error'),
    onFinish: () => processing.value = false,
  });
};

// Toggle loaded status for DO items
const toggleItemLoaded = (item) => {
  if (processing.value || !selectedDo.value) return;
  
  router.patch(route('warehouse.loading.toggle-item-loaded', selectedDo.value.id), {
    item_id: item.id,
    is_loaded: !item.is_loaded
  }, {
    preserveScroll: true,
    onSuccess: (p) => {
      playSound('success');
      // Update locally
      const updated = props.deliveryOrders.find(o => o.id === selectedDo.value.id);
      if (updated) {
        if (scannedData.value) {
          scannedData.value.delivery_order = updated;
        }
      }
    }
  });
};

// Complete loading process (status picking -> packed)
const finishLoading = () => {
  if (!selectedDo.value) return;
  
  processing.value = true;
  router.patch(route('warehouse.loading.update-status', selectedDo.value.id), {
    status: 'packed'
  }, {
    preserveScroll: true,
    onSuccess: () => {
      playSound('success');
      speakMessage('Pemuatan selesai. Silakan menuju jembatan timbang.');
      scannedData.value = null;
      selectedDoId.value = '';
    },
    onFinish: () => processing.value = false
  });
};

// Select DO manually
const selectDO = (doItem) => {
  selectedDoId.value = doItem.id;
  scannedData.value = null;
};

// RFID Keyboard emulation listener
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

const processRfidTag = (tag) => {
  const cleanedInput = tag.replace('RFID-TRUCK-', '').replace(/[\s-]/g, '').toUpperCase();
  
  // Look up DO in active orders list
  const match = props.deliveryOrders.find(d => {
    const doPlate = (d.vehicle_number || d.vehicle?.license_plate || '').replace(/[\s-]/g, '').toUpperCase();
    const doRfid = (d.vehicle?.rfid_card || '').replace(/[\s-]/g, '').toUpperCase();
    return doPlate === cleanedInput || (doRfid && doRfid === cleanedInput);
  });

  if (match) {
    selectDO(match);
    setTimeout(() => triggerScan(), 100);
  } else {
    playSound('error');
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

// Status helpers
const statusColor = (status) => {
  switch (status) {
    case 'draft': return 'bg-slate-700 text-slate-300';
    case 'picking': return 'bg-amber-500/20 text-amber-400 border border-amber-500/30 animate-pulse';
    case 'packed': return 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30';
    default: return 'bg-slate-700 text-slate-300';
  }
};

const statusLabel = (s) => {
  switch (s) {
    case 'draft': return 'DIPANGGIL';
    case 'picking': return 'LOADING';
    case 'packed': return 'SELESAI';
    default: return s?.toUpperCase();
  }
};

// Autopool data
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
  if (flash?.dockScannedData) {
    scannedData.value = flash.dockScannedData;
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
  <Head title="Loading Dock — RFID Terminal Pemuatan" />

  <div class="min-h-screen bg-slate-950 text-slate-100 font-sans antialiased overflow-x-hidden relative">
    <!-- Grid -->
    <div class="absolute inset-0 bg-[linear-gradient(to_right,#0a1628_1px,transparent_1px),linear-gradient(to_bottom,#0a1628_1px,transparent_1px)] bg-[size:3rem_3rem] pointer-events-none opacity-30"></div>
    
    <!-- Header -->
    <header class="border-b border-blue-500/20 bg-slate-900/60 backdrop-blur-md sticky top-0 z-30 px-4 py-3 shadow-lg">
      <div class="max-w-[1600px] mx-auto flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <a :href="route('warehouse.loading.index')" class="text-xs font-mono border border-slate-700 hover:border-blue-500/50 text-slate-300 px-3 py-1.5 rounded-lg transition-all flex items-center gap-1.5">
            <ArrowLeft class="w-3.5 h-3.5" /> Kembali
          </a>
          <div class="h-6 w-px bg-slate-700"></div>
          <div class="flex items-center gap-2">
            <div class="w-9 h-9 rounded-lg border border-blue-400 bg-blue-950/50 flex items-center justify-center shadow-lg">
              <Package class="w-5 h-5 text-blue-400 animate-pulse" />
            </div>
            <div>
              <h1 class="text-lg font-bold tracking-wider text-transparent bg-clip-text bg-gradient-to-r from-blue-400 via-cyan-300 to-blue-200">
                LOADING DOCK RFID TERMINAL
              </h1>
              <p class="text-[10px] text-blue-500/60 uppercase tracking-widest font-mono">Terminal Pemuatan Barang Aktif</p>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-4">
          <!-- Dock selector -->
          <div class="flex items-center gap-2 bg-slate-950 border border-slate-800 rounded-xl px-3 py-1.5">
            <span class="text-[10px] font-mono text-slate-500 uppercase tracking-wider">Lokasi Bay:</span>
            <select v-model="selectedBay" @change="() => { selectedDoId = ''; scannedData = null; }"
              class="bg-transparent border-0 text-xs text-white font-bold p-0 pr-8 focus:ring-0 cursor-pointer">
              <option v-for="b in bays" :key="b" :value="b">{{ b }}</option>
            </select>
          </div>

          <!-- Sound toggle -->
          <button @click="isMuted = !isMuted"
            class="flex items-center gap-1.5 text-xs font-mono px-3 py-1.5 rounded-lg border transition-all"
            :class="isMuted ? 'border-red-500/40 bg-red-950/20 text-red-400' : 'border-blue-500/30 bg-blue-950/20 text-blue-400'">
            <component :is="isMuted ? VolumeX : Volume2" class="w-3.5 h-3.5" />
            {{ isMuted ? 'MUTE' : 'SOUND' }}
          </button>
          
          <div class="text-right hidden md:block">
            <span class="text-[9px] font-mono text-slate-500 block">LIVE SYNC</span>
            <span class="text-[10px] font-mono text-blue-400 flex items-center gap-1">
              <span class="w-1.5 h-1.5 rounded-full bg-blue-400 animate-ping"></span>
              {{ lastSync }}
            </span>
          </div>
        </div>
      </div>
    </header>

    <main class="max-w-[1600px] mx-auto p-4 md:p-6 space-y-5 relative z-10">

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
        
        <!-- ======= LEFT: TRUK AKTIF (col 4) ======= -->
        <div class="lg:col-span-4 bg-slate-900/70 border border-slate-800 rounded-2xl backdrop-blur-md overflow-hidden shadow-xl flex flex-col h-[480px]">
          <div class="px-4 py-3 bg-slate-900 border-b border-slate-800 flex items-center justify-between">
            <h3 class="text-xs font-bold tracking-wider font-mono text-blue-400 uppercase flex items-center gap-1.5">
              <Truck class="w-3.5 h-3.5" /> Truk Pemuatan Aktif
            </h3>
            <span v-if="selectedDo" class="text-[9px] font-mono px-2 py-0.5 rounded-full" :class="statusColor(selectedDo.status)">
              {{ statusLabel(selectedDo.status) }}
            </span>
          </div>

          <div class="p-4 flex-1 flex flex-col">
            <template v-if="selectedDo">
              <!-- Vehicle Details -->
              <div class="relative rounded-xl overflow-hidden bg-slate-950 border border-slate-800 mb-3 h-40">
                <img v-if="selectedDo.vehicle?.vehicle_photo_url" :src="selectedDo.vehicle.vehicle_photo_url" class="w-full h-full object-cover" alt="Vehicle" />
                <div v-else class="w-full h-full flex items-center justify-center">
                  <Truck class="w-12 h-12 text-slate-700" />
                </div>
                <div class="absolute bottom-3 left-3 px-3 py-1.5 bg-slate-900/90 border border-blue-500/40 rounded-lg shadow-lg">
                  <span class="text-sm font-mono font-black text-blue-300 tracking-wider">
                    {{ selectedDo.vehicle_number || selectedDo.vehicle?.license_plate || 'N/A' }}
                  </span>
                </div>
              </div>

              <!-- Driver details -->
              <div class="flex items-center gap-3 bg-slate-950/60 border border-slate-800 rounded-xl p-2.5 mb-3">
                <div class="w-9 h-9 rounded-full border border-blue-500/40 overflow-hidden bg-slate-800 shrink-0">
                  <img v-if="selectedDo.vehicle?.driver_photo_url" :src="selectedDo.vehicle.driver_photo_url" class="w-full h-full object-cover" alt="Driver" />
                  <div v-else class="w-full h-full flex items-center justify-center">
                    <User class="w-4 h-4 text-slate-600" />
                  </div>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-[9px] font-mono text-slate-500 uppercase tracking-wider">Driver / Supir</p>
                  <p class="text-xs font-bold text-white truncate">{{ selectedDo.driver_name || 'Tidak diketahui' }}</p>
                </div>
              </div>

              <!-- Progress bar -->
              <div class="space-y-1.5 mt-auto">
                <div class="flex items-center justify-between text-xs font-mono">
                  <span class="text-slate-400">PROGRESS LOADING:</span>
                  <span class="font-bold text-blue-400">{{ progressPercent }}%</span>
                </div>
                <div class="w-full bg-slate-950 rounded-full h-3 border border-slate-800 overflow-hidden">
                  <div class="bg-gradient-to-r from-blue-500 to-cyan-400 h-full rounded-full transition-all duration-300 shadow-[0_0_10px_rgba(59,130,246,0.5)]"
                    :style="`width: ${progressPercent}%`"></div>
                </div>
              </div>
            </template>
            <div v-else class="flex-1 flex flex-col items-center justify-center text-center py-8">
              <Truck class="w-12 h-12 text-slate-700 mb-3" />
              <p class="text-sm text-slate-500">Bay Kosong</p>
              <p class="text-[10px] text-slate-600 mt-1">Tap kartu RFID truk untuk memanggil antrean ke bay ini</p>
            </div>
          </div>
        </div>

        <!-- ======= CENTER: CARGO CHECKLIST (col 5) ======= -->
        <div class="lg:col-span-5 bg-slate-900/70 border border-slate-800 rounded-2xl backdrop-blur-md overflow-hidden shadow-xl flex flex-col h-[480px]">
          <div class="px-4 py-3 bg-slate-900 border-b border-slate-800 flex items-center justify-between">
            <h3 class="text-xs font-bold tracking-wider font-mono text-cyan-400 uppercase flex items-center gap-1.5">
              <Package class="w-3.5 h-3.5" /> Request Loading / DO Checklist
            </h3>
            <span v-if="selectedDo" class="text-[9px] font-mono text-slate-500">
              DO: {{ selectedDo.do_number }}
            </span>
          </div>

          <div class="p-4 flex-1 flex flex-col min-h-0">
            <template v-if="selectedDo">
              <!-- Customer destination -->
              <div class="bg-slate-950/50 border border-slate-800/80 rounded-xl p-2.5 mb-3 text-xs space-y-0.5 shrink-0">
                <p class="font-bold text-white"><span class="text-[9px] font-mono text-slate-500 uppercase tracking-wider">Cust:</span> {{ selectedDo.customer?.name || 'N/A' }}</p>
                <p class="text-slate-300 truncate"><span class="text-[9px] font-mono text-slate-500 uppercase tracking-wider">Kirim:</span> {{ selectedDo.shipping_address || '-' }}</p>
              </div>

              <!-- Item table -->
              <div class="space-y-1.5 flex-1 overflow-y-auto pr-1">
                <div v-for="item in selectedDo.items" :key="item.id"
                  @click="toggleItemLoaded(item)"
                  class="flex items-center justify-between bg-slate-950/40 border rounded-xl p-2.5 hover:border-cyan-500/30 transition-all cursor-pointer select-none border-slate-800"
                  :class="item.is_loaded ? 'border-emerald-500/30 bg-emerald-950/5' : ''">
                  <div class="flex items-center gap-2 min-w-0">
                    <component :is="item.is_loaded ? CheckSquare : Square" 
                      class="w-3.5 h-3.5 shrink-0" 
                      :class="item.is_loaded ? 'text-emerald-400' : 'text-slate-500'" />
                    <div class="min-w-0">
                      <p class="text-xs font-semibold truncate" :class="item.is_loaded ? 'text-slate-400 line-through' : 'text-slate-200'">
                        {{ item.product?.name || 'Produk' }}
                      </p>
                      <p class="text-[8px] text-slate-500 font-mono">{{ item.product?.sku || '' }}</p>
                    </div>
                  </div>
                  <div class="text-right ml-2 shrink-0">
                    <span class="text-xs font-mono font-bold"
                      :class="item.is_loaded ? 'text-emerald-400' : 'text-cyan-400'">
                      {{ item.qty_ordered }} {{ item.unit?.code || 'pcs' }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Finish loading action -->
              <div class="mt-3 pt-2.5 border-t border-slate-800 flex items-center justify-between shrink-0">
                <div class="text-[10px] font-mono text-slate-500">
                  {{ selectedDo.items?.filter(i => i.is_loaded).length || 0 }} / {{ selectedDo.items?.length || 0 }} Item
                </div>
                <button 
                  v-if="selectedDo.status === 'picking'"
                  @click="finishLoading"
                  :disabled="!allItemsLoaded || processing"
                  class="px-4 py-2 rounded-xl text-xs font-black font-mono tracking-wider shadow-lg flex items-center gap-1 transition-all active:scale-95 disabled:opacity-40 disabled:cursor-not-allowed"
                  :class="allItemsLoaded
                    ? 'bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-500 hover:to-emerald-400 text-white shadow-emerald-500/20 border border-emerald-400/20'
                    : 'bg-slate-800 border border-slate-700 text-slate-500'">
                  <CheckCircle class="w-3.5 h-3.5" />
                  SELESAI
                </button>
              </div>
            </template>
            <div v-else class="flex-1 flex flex-col items-center justify-center text-center py-8">
              <Package class="w-12 h-12 text-slate-700 mb-3" />
              <p class="text-sm text-slate-500">Checklist kargo kosong</p>
              <p class="text-[10px] text-slate-600 mt-1">Daftar item loading akan tampil setelah truk di-tap</p>
            </div>
          </div>
        </div>

        <!-- ======= RIGHT: SENSOR RFID & BAY CONTROLLER (col 3) ======= -->
        <div class="lg:col-span-3 bg-slate-900/70 border border-slate-800 rounded-2xl backdrop-blur-md overflow-hidden shadow-xl flex flex-col h-[480px]">
          <div class="px-4 py-3 bg-slate-900 border-b border-slate-800">
            <h3 class="text-xs font-bold tracking-wider font-mono text-blue-400 uppercase flex items-center gap-1.5">
              <Wifi class="w-3.5 h-3.5" /> Sensor RFID Dock
            </h3>
          </div>

          <div class="p-4 flex-1 flex flex-col items-center justify-center">
            <div class="relative mb-6">
              <div class="w-24 h-24 rounded-full border-2 flex items-center justify-center transition-all duration-500"
                :class="scannedData
                  ? (scannedData.scan_status === 'success' ? 'border-emerald-400 bg-emerald-950/30 shadow-[0_0_30px_rgba(52,211,153,0.3)]'
                    : 'border-amber-400 bg-amber-950/30 shadow-[0_0_30px_rgba(245,158,11,0.3)]')
                  : 'border-blue-400/50 bg-blue-950/20 shadow-[0_0_20px_rgba(59,130,246,0.15)]'">
                <Scan class="w-10 h-10 transition-all text-blue-400" :class="!scannedData ? 'animate-pulse' : ''" />
              </div>
              <div v-if="!scannedData" class="absolute inset-0 w-24 h-24 rounded-full border-2 border-blue-400/30 animate-ping"></div>
            </div>

            <!-- Status message -->
            <div v-if="scannedData" class="text-center mb-4">
              <p class="text-xs font-bold" :class="scannedData.scan_status === 'success' ? 'text-emerald-400' : 'text-amber-400'">
                {{ scannedData.scan_status === 'success' ? '✅ Scan Berhasil' : '⚠️ Scan Gagal' }}
              </p>
              <p class="text-[10px] text-slate-400 mt-1 max-w-[220px]">{{ scannedData.scan_message }}</p>
            </div>
            <div v-else class="text-center mb-4">
              <p class="text-sm font-bold text-white">Tap Kartu RFID Pemuatan</p>
              <p class="text-[10px] text-slate-500 mt-1 font-mono">Terminal aktif untuk {{ selectedBay }}</p>
            </div>

            <!-- Manual trigger -->
            <div class="w-full space-y-3 mt-auto">
              <div>
                <label class="text-[9px] font-mono text-slate-500 uppercase tracking-wider mb-1 block">Simulasi Scan (Ketik Plat / Tag RFID & Tekan Enter)</label>
                <div class="relative">
                  <input
                    id="rfid-manual-input"
                    v-model="rfidInputText"
                    type="text"
                    placeholder="Contoh: B 3604 XJ atau RFID-TRUCK-..."
                    class="w-full bg-slate-950 border border-slate-700 rounded-lg pl-8 pr-3 py-2 text-xs text-white focus:ring-blue-500/50 focus:border-blue-500/50 font-mono placeholder:text-slate-600"
                  />
                  <Scan class="absolute left-2.5 top-2.5 w-3.5 h-3.5 text-slate-500" />
                </div>
              </div>

              <div>
                <label class="text-[9px] font-mono text-slate-500 uppercase tracking-wider mb-1 block">Pilih DO Manual</label>
                <select v-model="selectedDoId"
                  @change="() => { scannedData = null; }"
                  class="w-full bg-slate-950 border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:ring-blue-500/50 focus:border-blue-500/50 font-mono">
                  <option value="">-- Pilih Delivery Order --</option>
                  <option v-for="d in filteredOrders" :key="d.id" :value="d.id">
                    [{{ d.status?.toUpperCase() }}] {{ d.vehicle_number || d.vehicle?.license_plate || 'N/A' }} — {{ d.do_number }}
                  </option>
                </select>
              </div>

              <button @click="triggerScan" :disabled="!selectedDoId || processing"
                class="w-full py-2.5 rounded-xl text-xs font-bold font-mono tracking-wider transition-all shadow-lg flex items-center justify-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-500 hover:to-blue-400 text-white shadow-blue-500/20 border border-blue-400/20">
                <component :is="processing ? Loader2 : Scan" class="w-3.5 h-3.5" :class="processing ? 'animate-spin' : ''" />
                {{ processing ? 'MEMPROSES...' : '⚡ SIMULASI TAP DOCK' }}
              </button>
            </div>
          </div>
        </div>

      </div>

      <!-- ======= DOCK VECHILE QUEUE ======= -->
      <div class="bg-slate-900/70 border border-slate-800 rounded-2xl backdrop-blur-md overflow-hidden shadow-xl">
        <div class="px-4 py-3 bg-slate-900 border-b border-slate-800 flex items-center justify-between">
          <h3 class="text-xs font-bold tracking-wider font-mono text-indigo-400 uppercase flex items-center gap-1.5">
            <Clock class="w-3.5 h-3.5" /> Antrean Kendaraan di {{ selectedBay }}
          </h3>
          <span class="text-[9px] font-mono text-slate-500">{{ filteredOrders.length }} Truk Terjadwal</span>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-xs">
            <thead>
              <tr class="border-b border-slate-800/60 bg-slate-950/40">
                <th class="py-3 px-4 text-left font-mono text-[9px] text-slate-500 uppercase tracking-wider">Waktu Jadwal</th>
                <th class="py-3 px-4 text-left font-mono text-[9px] text-slate-500 uppercase tracking-wider">Kendaraan</th>
                <th class="py-3 px-4 text-left font-mono text-[9px] text-slate-500 uppercase tracking-wider">Nama Driver</th>
                <th class="py-3 px-4 text-left font-mono text-[9px] text-slate-500 uppercase tracking-wider">Customer</th>
                <th class="py-3 px-4 text-left font-mono text-[9px] text-slate-500 uppercase tracking-wider">No. DO</th>
                <th class="py-3 px-4 text-center font-mono text-[9px] text-slate-500 uppercase tracking-wider">Status Antrean</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="order in filteredOrders" :key="order.id"
                @click="selectDO(order)"
                class="border-b border-slate-800/30 hover:bg-slate-800/30 cursor-pointer transition-colors"
                :class="selectedDoId == order.id ? 'bg-blue-950/20 border-l-2 border-l-blue-500' : ''">
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
                <td class="py-3 px-4 font-mono text-slate-300">{{ order.do_number }}</td>
                <td class="py-3 px-4 text-center">
                  <span class="px-2.5 py-0.5 rounded-full text-[9px] font-mono font-bold uppercase" :class="statusColor(order.status)">
                    {{ statusLabel(order.status) }}
                  </span>
                </td>
              </tr>
              <tr v-if="filteredOrders.length === 0">
                <td colspan="6" class="py-8 text-center text-slate-600 text-xs font-mono">
                  TIDAK ADA KENDARAAN DI BAY INI
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </main>

    <footer class="border-t border-slate-800 py-3 px-6 text-center text-[9px] font-mono text-slate-600 mt-4">
      <span>&copy; {{ new Date().getFullYear() }} USICS LOADING DOCK TERMINAL MODULE. ALL BAY ACTIVE.</span>
    </footer>
  </div>
</template>
