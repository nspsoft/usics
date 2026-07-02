<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { 
  Cpu, 
  Map, 
  Terminal, 
  Volume2, 
  VolumeX, 
  RefreshCw, 
  AlertTriangle,
  CheckCircle,
  Sparkles,
  Barcode,
  Search,
  Check
} from 'lucide-vue-next';

const props = defineProps({
  opname: {
    type: Object,
    default: () => ({})
  },
  products: {
    type: Array,
    default: () => []
  }
});

// Sound States
const isMuted = ref(false);

// Web Audio API Sound Generator
const playScanSound = (type = 'sweep') => {
  if (isMuted.value || typeof window === 'undefined') return;
  try {
    const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    
    if (type === 'sweep') {
      // Sweep sound: ascending frequency chirp
      const osc = audioCtx.createOscillator();
      const gainNode = audioCtx.createGain();
      osc.type = 'sine';
      osc.frequency.setValueAtTime(300, audioCtx.currentTime);
      osc.frequency.exponentialRampToValueAtTime(1500, audioCtx.currentTime + 0.4);
      
      gainNode.gain.setValueAtTime(0.08, audioCtx.currentTime);
      gainNode.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.4);
      
      osc.connect(gainNode);
      gainNode.connect(audioCtx.destination);
      osc.start();
      osc.stop(audioCtx.currentTime + 0.4);
    } else if (type === 'beep') {
      // Beep: short clean high sine beep
      const osc = audioCtx.createOscillator();
      const gainNode = audioCtx.createGain();
      osc.type = 'sine';
      osc.frequency.setValueAtTime(1000, audioCtx.currentTime);
      
      gainNode.gain.setValueAtTime(0.1, audioCtx.currentTime);
      gainNode.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.15);
      
      osc.connect(gainNode);
      gainNode.connect(audioCtx.destination);
      osc.start();
      osc.stop(audioCtx.currentTime + 0.15);
    } else if (type === 'error') {
      // Buzzer: low sawtooth buzz
      const osc = audioCtx.createOscillator();
      const gainNode = audioCtx.createGain();
      osc.type = 'sawtooth';
      osc.frequency.setValueAtTime(130, audioCtx.currentTime);
      
      gainNode.gain.setValueAtTime(0.15, audioCtx.currentTime);
      gainNode.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.45);
      
      osc.connect(gainNode);
      gainNode.connect(audioCtx.destination);
      osc.start();
      osc.stop(audioCtx.currentTime + 0.45);
    }
  } catch (err) {
    console.error('Audio Synthesis Error:', err);
  }
};

// Scan Simulation States
const selectedProductId = ref('');
const physicalQty = ref(1);
const scanNotes = ref('');
const processing = ref(false);
const logs = ref([
  { time: new Date().toLocaleTimeString(), text: 'SYSTEM: Stock Opname blind count HUD initialized.' },
  { time: new Date().toLocaleTimeString(), text: 'SYSTEM: UHF RFID scanner connected on COM7.' }
]);

const addLog = (text) => {
  logs.value.unshift({
    time: new Date().toLocaleTimeString(),
    text
  });
};

// Map items into product hashmap for fast checking
const opnameItemsMap = computed(() => {
  const map = {};
  if (props.opname.items) {
    props.opname.items.forEach(item => {
      map[item.product_id] = item;
    });
  }
  return map;
});

// Determine location statuses based on scanned items in this opname
const locationStatuses = computed(() => {
  const statuses = {};
  if (!props.opname.warehouse || !props.opname.warehouse.locations) return {};
  
  // For simulation, let's map location IDs to statuses. 
  // If no items have been scanned yet, they are pending (grey).
  // If scanned and difference is 0, they are matched (green).
  // If scanned and difference is not 0, they have variance (yellow).
  props.opname.warehouse.locations.forEach((loc, index) => {
    // Determine status based on index for simulation visual variance
    const items = props.opname.items || [];
    if (items.length === 0) {
      statuses[loc.id] = 'pending';
    } else {
      // Simulate location mapping based on scanned items
      const hasDiscrepancy = items.some(item => item.qty_difference !== 0);
      if (index % 3 === 0 && hasDiscrepancy) {
        statuses[loc.id] = 'discrepancy';
      } else if (index % 5 === 0 && items.length > 1) {
        statuses[loc.id] = 'misplaced';
      } else {
        statuses[loc.id] = 'matched';
      }
    }
  });
  
  return statuses;
});

const aiRecommendations = computed(() => {
  const items = props.opname.items || [];
  const varianceItems = items.filter(item => item.qty_difference !== 0);
  
  if (varianceItems.length === 0) {
    return ["Semua item klop! Tidak ada selisih stok terdeteksi saat ini."];
  }
  
  const recs = [];
  varianceItems.forEach(item => {
    const diff = item.qty_difference;
    if (diff < 0) {
      recs.push(`Coil SKU ${item.product?.sku} kurang ${Math.abs(diff)} Pcs di Rak utama. Periksa Rak B2, kemungkinan barang salah diposisikan oleh Crane.`);
    } else {
      recs.push(`Terdeteksi kelebihan ${diff} Pcs pada SKU ${item.product?.sku}. Periksa dokumen Goods Receipt (GRN) terakhir, kemungkinan ada pencatatan yang ganda.`);
    }
  });
  
  return recs;
});

const submitScan = () => {
  if (!selectedProductId.value) {
    playScanSound('error');
    alert('Silakan pilih produk terlebih dahulu.');
    return;
  }
  
  processing.value = true;
  const product = props.products.find(p => p.id === parseInt(selectedProductId.value));
  addLog(`SCANNER: Pemindaian barcode tag RFID-OPNAME-${product.sku}...`);

  router.post(route('inventory.opname.scan-item', props.opname.id), {
    product_id: selectedProductId.value,
    qty_physic: physicalQty.value,
    notes: scanNotes.value
  }, {
    preserveScroll: true,
    onSuccess: () => {
      playScanSound('beep');
      const item = opnameItemsMap.value[selectedProductId.value];
      const diff = physicalQty.value - (item ? item.qty_system : 0);
      addLog(`SYSTEM: Berhasil memproses SKU ${product.sku}. Kuantitas Fisik: ${physicalQty.value}. Selisih: ${diff} Pcs.`);
      selectedProductId.value = '';
      physicalQty.value = 1;
      scanNotes.value = '';
    },
    onError: () => {
      playScanSound('error');
      addLog(`SCANNER ERROR: Gagal mengirimkan data pemindaian.`);
    },
    onFinish: () => {
      processing.value = false;
    }
  });
};

// Simulate sweep RFID mass audit
const triggerRfIdSweep = () => {
  processing.value = true;
  playScanSound('sweep');
  addLog(`RFID SWEEPER: Memulai sapuan gelombang UHF massal di Gudang...`);
  
  // Pick random products from the list and simulate scan
  const itemsToScan = props.products.slice(0, 3);
  let scannedCount = 0;
  
  const processNext = (idx) => {
    if (idx >= itemsToScan.length) {
      addLog(`RFID SWEEPER: Sapuan selesai. Terdeteksi ${scannedCount} tipe produk.`);
      processing.value = false;
      return;
    }
    
    const product = itemsToScan[idx];
    const simulatedPhysic = Math.floor(Math.random() * 5) + 3; // random count
    
    router.post(route('inventory.opname.scan-item', props.opname.id), {
      product_id: product.id,
      qty_physic: simulatedPhysic,
      notes: 'Pemindaian UHF RFID Massal'
    }, {
      preserveScroll: true,
      onSuccess: () => {
        playScanSound('beep');
        scannedCount++;
        processNext(idx + 1);
      },
      onError: () => {
        processNext(idx + 1);
      }
    });
  };
  
  setTimeout(() => {
    processNext(0);
  }, 600);
};

const getLocColorClass = (status) => {
  switch (status) {
    case 'matched': return 'border-emerald-500/50 bg-emerald-950/20 text-emerald-400';
    case 'discrepancy': return 'border-amber-500/50 bg-amber-950/20 text-amber-400';
    case 'misplaced': return 'border-red-500/50 bg-red-950/20 text-red-400';
    default: return 'border-slate-800 bg-slate-900/30 text-slate-500';
  }
};
</script>

<template>
  <Head title="Stock Opname HUD & AI Discrepancy Advisor" />

  <div class="min-h-screen bg-slate-950 text-slate-100 font-sans antialiased overflow-x-hidden relative">
    <!-- Grid overlay background -->
    <div class="absolute inset-0 bg-[linear-gradient(to_right,#0f172a_1px,transparent_1px),linear-gradient(to_bottom,#0f172a_1px,transparent_1px)] bg-[size:3.5rem_3.5rem] pointer-events-none opacity-40"></div>
    <div class="absolute top-0 right-0 w-[50%] h-[50%] bg-emerald-900/10 rounded-full blur-[100px] pointer-events-none"></div>

    <!-- HUD Header -->
    <header class="border-b border-emerald-500/25 bg-slate-900/60 backdrop-blur-md sticky top-0 z-10 px-6 py-4 shadow-lg">
      <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-lg border border-emerald-500 bg-emerald-950/50 flex items-center justify-center shadow-[0_0_15px_rgba(16,185,129,0.4)] animate-pulse">
            <Barcode class="w-6 h-6 text-emerald-400" />
          </div>
          <div>
            <h1 class="text-xl font-bold tracking-wider text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 via-teal-300 to-emerald-200">
              STOCK OPNAME HUD CONTROL PANEL
            </h1>
            <p class="text-xs text-emerald-500/60 uppercase tracking-widest font-mono">UAT Stock Audit & AI Discrepancy Verification</p>
          </div>
        </div>

        <div class="flex items-center gap-4">
          <button 
            @click="isMuted = !isMuted"
            class="flex items-center gap-2 text-xs font-mono font-medium transition-all px-3 py-1.5 rounded-lg border"
            :class="isMuted 
              ? 'border-red-500/40 bg-red-950/20 text-red-400' 
              : 'border-emerald-500/30 bg-emerald-950/20 text-emerald-400'"
          >
            <component :is="isMuted ? VolumeX : Volume2" class="w-4 h-4" />
            <span>{{ isMuted ? 'SOUND OFF' : 'SOUND ON' }}</span>
          </button>
          
          <a
            :href="route('opname.index')"
            class="text-xs font-mono border border-slate-700 hover:border-emerald-500/50 text-slate-300 px-4 py-1.5 rounded-lg transition-all"
          >
            KEMBALI KE DAFTAR
          </a>
        </div>
      </div>
    </header>

    <main class="max-w-7xl mx-auto p-4 md:p-6 lg:p-8 space-y-6">
      
      <!-- Opname Info Metadata Panel -->
      <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 backdrop-blur-md shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 flex-1">
          <div>
            <span class="text-[10px] font-mono text-slate-500 block uppercase">DOKUMEN OPNAME</span>
            <span class="text-sm font-mono font-bold text-slate-200">{{ opname.opname_number }}</span>
          </div>
          <div>
            <span class="text-[10px] font-mono text-slate-500 block uppercase">GUDANG / WAREHOUSE</span>
            <span class="text-sm font-sans font-semibold text-slate-200">{{ opname.warehouse?.name }}</span>
          </div>
          <div>
            <span class="text-[10px] font-mono text-slate-500 block uppercase">TANGGAL AUDIT</span>
            <span class="text-sm font-mono text-slate-200">{{ opname.opname_date }}</span>
          </div>
          <div>
            <span class="text-[10px] font-mono text-slate-500 block uppercase">STATUS PROSES</span>
            <span class="text-xs font-mono font-bold px-2 py-0.5 rounded uppercase inline-block border text-emerald-400 bg-emerald-950/20 border-emerald-500/30">
              {{ opname.status }}
            </span>
          </div>
        </div>
        
        <button
          @click="triggerRfIdSweep"
          :disabled="processing"
          class="bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-xs uppercase tracking-wider py-3 px-5 rounded-xl flex items-center gap-2 transition-all shadow-lg shadow-emerald-500/10 active:scale-95 disabled:opacity-40"
        >
          <Cpu class="w-4 h-4 animate-spin" v-if="processing" />
          <RefreshCw class="w-4 h-4" v-else />
          <span>⚡ SAPU RFID MASSAL</span>
        </button>
      </div>

      <!-- Main Columns Grid Layout -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- LEFT: Blind Count Scanner & AI Panel (col 5) -->
        <div class="lg:col-span-5 flex flex-col gap-6">
          
          <!-- 1. Blind Count Simulator Form -->
          <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 backdrop-blur-md shadow-xl flex flex-col justify-between">
            <div>
              <div class="border-b border-slate-800 pb-3 mb-4">
                <h3 class="text-xs font-bold font-mono tracking-wider text-emerald-400 uppercase flex items-center gap-2">
                  <Barcode class="w-4 h-4" />
                  BLIND COUNT SCANNER SIMULATOR
                </h3>
                <p class="text-[10px] text-slate-500 font-mono mt-1">Audit buta: Kuantitas stok buku sengaja disembunyikan untuk menjaga objektivitas audit.</p>
              </div>

              <div class="space-y-4">
                <!-- Product Select -->
                <div>
                  <label class="block text-xs font-mono text-slate-400 uppercase mb-1.5">PILIH PRODUK (SCAN BARCODE)</label>
                  <select 
                    v-model="selectedProductId"
                    class="w-full bg-slate-950 text-slate-100 border border-slate-800 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:border-emerald-500 font-mono"
                  >
                    <option value="">-- PILIH SKU / PRODUK --</option>
                    <option 
                      v-for="p in products" 
                      :key="p.id" 
                      :value="p.id"
                    >
                      [{{ p.sku }}] {{ p.name }}
                    </option>
                  </select>
                </div>

                <!-- Physic count input -->
                <div>
                  <label class="block text-xs font-mono text-slate-400 uppercase mb-1.5">JUMLAH DIHITUNG FISIK (QTY PHYSIC)</label>
                  <div class="flex items-center gap-3">
                    <button 
                      @click="() => { if (physicalQty > 0) physicalQty-- }"
                      class="w-10 h-10 rounded-lg bg-slate-950 border border-slate-800 flex items-center justify-center hover:bg-slate-900 active:scale-95 font-bold text-lg"
                    >-</button>
                    <input 
                      type="number" 
                      v-model.number="physicalQty"
                      min="0"
                      class="flex-1 bg-slate-950 text-center text-slate-100 border border-slate-800 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-emerald-500 font-mono font-bold"
                    />
                    <button 
                      @click="() => physicalQty++"
                      class="w-10 h-10 rounded-lg bg-slate-950 border border-slate-800 flex items-center justify-center hover:bg-slate-900 active:scale-95 font-bold text-lg"
                    >+</button>
                  </div>
                </div>

                <!-- Notes -->
                <div>
                  <label class="block text-xs font-mono text-slate-400 uppercase mb-1.5">CATATAN FISIK (OPTIONAL)</label>
                  <input 
                    type="text" 
                    v-model="scanNotes"
                    placeholder="misal: Barang berdebu / Penyok sedikit"
                    class="w-full bg-slate-950 text-slate-100 border border-slate-800 rounded-xl px-3 py-2.5 text-xs focus:outline-none focus:border-emerald-500"
                  />
                </div>
              </div>
            </div>

            <button
              @click="submitScan"
              :disabled="processing || !selectedProductId"
              class="w-full mt-6 py-3.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-xs font-bold uppercase tracking-wider transition-all disabled:opacity-40 disabled:cursor-not-allowed shadow-lg active:scale-95 flex items-center justify-center gap-2"
            >
              <Cpu class="w-4 h-4 animate-ping" v-slot="processing" />
              <span>{{ processing ? 'AUDITING...' : '⚡ KIRIM PEMINDAIAN AUDIT' }}</span>
            </button>
          </div>

          <!-- 2. AI Advisor Recommendations Panel -->
          <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5 backdrop-blur-md shadow-xl flex flex-col">
            <div class="border-b border-slate-800 pb-3 mb-3 flex items-center justify-between">
              <h3 class="text-xs font-bold font-mono tracking-wider text-teal-400 uppercase flex items-center gap-2">
                <Sparkles class="w-4 h-4" />
                AI DISCREPANCY ADVISOR
              </h3>
            </div>

            <div class="space-y-3 min-h-[100px]">
              <div 
                v-for="(rec, i) in aiRecommendations" 
                :key="i"
                class="bg-teal-950/20 border border-teal-500/20 p-3 rounded-xl flex items-start gap-2.5 text-xs font-sans leading-relaxed text-teal-300 animate-fadeIn"
              >
                <Sparkles class="w-4 h-4 text-teal-400 shrink-0 mt-0.5" />
                <p>{{ rec }}</p>
              </div>
            </div>
          </div>

        </div>

        <!-- RIGHT: Interactive 2D Warehouse Opname Progress Map (col 7) -->
        <div class="lg:col-span-7 flex flex-col gap-6">
          
          <!-- 2D Storage Rack Layout -->
          <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 backdrop-blur-md shadow-xl flex flex-col justify-between">
            <div class="border-b border-slate-800 pb-3 mb-4 flex items-center justify-between">
              <h3 class="text-xs font-bold font-mono tracking-wider text-cyan-400 uppercase flex items-center gap-2">
                <Map class="w-4 h-4" />
                GUDANG AUDIT PROGRESS MAP (2D GRID)
              </h3>
            </div>

            <!-- Visual grid status representation -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 py-3">
              <div 
                v-for="loc in opname.warehouse?.locations"
                :key="loc.id"
                class="border rounded-xl p-3.5 flex flex-col items-center justify-between text-center min-h-[90px] transition-all"
                :class="getLocColorClass(locationStatuses[loc.id])"
              >
                <span class="text-[9px] font-mono tracking-widest font-bold uppercase block">{{ loc.name }}</span>
                
                <div class="my-2">
                  <CheckCircle class="w-7 h-7 text-emerald-400 animate-pulse" v-if="locationStatuses[loc.id] === 'matched'" />
                  <AlertTriangle class="w-7 h-7 text-amber-400 animate-bounce" v-else-if="locationStatuses[loc.id] === 'discrepancy'" />
                  <AlertTriangle class="w-7 h-7 text-red-500 animate-pulse" v-else-if="locationStatuses[loc.id] === 'misplaced'" />
                  <span class="w-3 h-3 rounded-full bg-slate-800 block" v-else></span>
                </div>

                <span class="text-[8px] font-mono uppercase block" :class="locationStatuses[loc.id] === 'pending' ? 'text-slate-600' : ''">
                  {{ locationStatuses[loc.id] === 'pending' ? 'PENDING' : locationStatuses[loc.id].toUpperCase() }}
                </span>
              </div>
            </div>

            <!-- Legend indicators -->
            <div class="mt-4 pt-4 border-t border-slate-800 flex flex-wrap gap-4 text-[10px] font-mono justify-center">
              <div class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded bg-slate-900 border border-slate-800"></span>
                <span class="text-slate-500">BELUM DIHITUNG (PENDING)</span>
              </div>
              <div class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded bg-emerald-950/20 border border-emerald-500/50"></span>
                <span class="text-emerald-400">KLOP (MATCHED)</span>
              </div>
              <div class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded bg-amber-950/20 border border-amber-500/50"></span>
                <span class="text-amber-400">ADA SELISIH (VARIANCE)</span>
              </div>
            </div>
          </div>

          <!-- Audit scan logs terminal -->
          <div class="flex-1 bg-slate-900/60 border border-slate-800 rounded-2xl p-6 backdrop-blur-md shadow-xl flex flex-col">
            <div class="border-b border-slate-800 pb-3 mb-4 flex items-center justify-between">
              <h3 class="text-xs font-bold font-mono tracking-wider text-emerald-400 uppercase flex items-center gap-2">
                <Terminal class="w-4 h-4" />
                AUDIT PEMINDAIAN LOG TICKER
              </h3>
            </div>

            <div class="flex-1 min-h-[150px] max-h-[220px] overflow-y-auto custom-scrollbar bg-slate-950/80 rounded-xl p-4 space-y-2 text-xs font-mono">
              <div 
                v-for="(log, i) in logs" 
                :key="i"
                class="flex items-start gap-3"
              >
                <span class="text-slate-600 shrink-0">{{ log.time }}</span>
                <span class="text-emerald-400/90 leading-relaxed font-sans">{{ log.text }}</span>
              </div>
            </div>
          </div>

        </div>

      </div>

    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-900 bg-slate-950 py-6 text-center text-xs text-slate-500 font-mono">
      <span>&copy; {{ new Date().getFullYear() }} USICS AUDIT SCANNER INTERFACE. DETECTING ACTIVE.</span>
    </footer>
  </div>
</template>

<style>
/* Custom animations for simulation UI */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(3px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fadeIn {
  animation: fadeIn 0.2s ease-out forwards;
}

/* Custom Scrollbar */
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: rgba(15, 23, 42, 0.4);
  border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(16, 185, 129, 0.2);
  border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(16, 185, 129, 0.4);
}
</style>
