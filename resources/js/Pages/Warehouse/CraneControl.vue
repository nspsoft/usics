<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { 
  Cpu, 
  MapPin, 
  Layers, 
  Anchor, 
  Compass, 
  Terminal, 
  Volume2, 
  VolumeX, 
  RefreshCw, 
  ArrowRight,
  TrendingUp,
  AlertTriangle,
  HelpCircle
} from 'lucide-vue-next';

const props = defineProps({
  warehouses: {
    type: Array,
    default: () => []
  },
  lots: {
    type: Array,
    default: () => []
  }
});

// Sound States
const isMuted = ref(false);

// Synthesize Crane Mechanical hum + Clank + Beep
const playCraneSound = (phase = 'move') => {
  if (isMuted.value || typeof window === 'undefined') return;
  try {
    const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    
    if (phase === 'move') {
      // 1. Crane hum: Low-pitched rumble with frequency modulation
      const osc = audioCtx.createOscillator();
      const gainNode = audioCtx.createGain();
      osc.type = 'sawtooth';
      osc.frequency.setValueAtTime(60, audioCtx.currentTime); // Low engine hum
      
      // Filter for warm bassy feel
      const filter = audioCtx.createBiquadFilter();
      filter.type = 'lowpass';
      filter.frequency.setValueAtTime(150, audioCtx.currentTime);
      
      osc.connect(filter);
      filter.connect(gainNode);
      gainNode.connect(audioCtx.destination);
      
      gainNode.gain.setValueAtTime(0.01, audioCtx.currentTime);
      gainNode.gain.linearRampToValueAtTime(0.15, audioCtx.currentTime + 0.2);
      gainNode.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 1.2);
      
      osc.start();
      osc.stop(audioCtx.currentTime + 1.2);
    } else if (phase === 'drop') {
      // 2. Metal Clank: Decay noise + ringing frequency
      const osc = audioCtx.createOscillator();
      const gainNode = audioCtx.createGain();
      osc.type = 'triangle';
      osc.frequency.setValueAtTime(180, audioCtx.currentTime); 
      
      gainNode.gain.setValueAtTime(0.2, audioCtx.currentTime);
      gainNode.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.6);
      
      osc.connect(gainNode);
      gainNode.connect(audioCtx.destination);
      osc.start();
      osc.stop(audioCtx.currentTime + 0.6);
      
      // Ringing high metallic frequency
      const oscRing = audioCtx.createOscillator();
      const gainRing = audioCtx.createGain();
      oscRing.type = 'sine';
      oscRing.frequency.setValueAtTime(1200, audioCtx.currentTime);
      gainRing.gain.setValueAtTime(0.05, audioCtx.currentTime);
      gainRing.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.3);
      
      oscRing.connect(gainRing);
      gainRing.connect(audioCtx.destination);
      oscRing.start();
      oscRing.stop(audioCtx.currentTime + 0.3);
      
      // Followed by RFID Success Beep
      setTimeout(() => {
        const beepOsc = audioCtx.createOscillator();
        const beepGain = audioCtx.createGain();
        beepOsc.type = 'sine';
        beepOsc.frequency.setValueAtTime(1000, audioCtx.currentTime);
        beepGain.gain.setValueAtTime(0.1, audioCtx.currentTime);
        beepGain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.15);
        
        beepOsc.connect(beepGain);
        beepGain.connect(audioCtx.destination);
        beepOsc.start();
        beepOsc.stop(audioCtx.currentTime + 0.15);
      }, 400);
    }
  } catch (err) {
    console.error('Audio Synthesis Error:', err);
  }
};

// Simulation States
const selectedWarehouseId = ref('');
const hookedLotId = ref('');
const processing = ref(false);
const showHelpModal = ref(false);
const logs = ref([
  { time: new Date().toLocaleTimeString(), text: 'SYSTEM: Crane RFID positioning telemetry initialized.' },
  { time: new Date().toLocaleTimeString(), text: 'SYSTEM: Waiting for pilot input or Lot docking.' }
]);

// Initialize selected warehouse
onMounted(() => {
  if (props.warehouses.length > 0) {
    selectedWarehouseId.value = props.warehouses[0].id;
  }
  
  // Seed initial mock lots if table is empty
  if (props.lots.length === 0) {
    logs.value.push({
      time: new Date().toLocaleTimeString(),
      text: 'WARNING: No inventory lots found. Seed dummy lot data first.'
    });
  }
});

const selectedWarehouse = computed(() => {
  return props.warehouses.find(w => w.id === parseInt(selectedWarehouseId.value));
});

// Group locations of selected warehouse into rows of 4 columns for grid representation
const gridLocations = computed(() => {
  if (!selectedWarehouse.value || !selectedWarehouse.value.locations) return [];
  return selectedWarehouse.value.locations;
});

const activeLots = computed(() => {
  return props.lots;
});

const addLog = (text) => {
  logs.value.unshift({
    time: new Date().toLocaleTimeString(),
    text
  });
};

const selectLot = (lotId) => {
  hookedLotId.value = lotId;
  const lot = props.lots.find(l => l.id === lotId);
  playCraneSound('move');
  addLog(`CRANE: Connected hoist hook to Coil #${lot.coil_number} (${lot.product?.name}). Hook locked.`);
};

const dropLot = (location) => {
  if (!hookedLotId.value) return;
  const lot = props.lots.find(l => l.id === hookedLotId.value);
  
  processing.value = true;
  addLog(`CRANE: Moving hoist crane to location ${location.name}. Telematics active...`);
  playCraneSound('move');

  setTimeout(() => {
    router.post(route('warehouse.crane.move'), {
      inventory_lot_id: hookedLotId.value,
      location_id: location.id
    }, {
      preserveScroll: true,
      onSuccess: () => {
        playCraneSound('drop');
        addLog(`RFID SCANNER: Successfully scanned Coil Tag RFID-COIL-${lot.coil_number} at location ${location.name}`);
        addLog(`AUTO-PUTAWAY: Completed. Stock movement recorded in DB.`);
        hookedLotId.value = '';
      },
      onError: () => {
        addLog(`CRANE ERROR: Transfer rejected by warehouse gate validation.`);
      },
      onFinish: () => {
        processing.value = false;
      }
    });
  }, 1000);
};

// Check if a specific location contains any lot
const getLotInLocation = (locationId) => {
  return props.lots.find(l => l.location_id === locationId);
};
</script>

<template>
  <Head title="Overhead Crane RFID Auto-Putaway cockpit" />

  <div class="min-h-screen bg-slate-950 text-slate-100 font-sans antialiased overflow-x-hidden relative">
    <!-- Grid overlay background -->
    <div class="absolute inset-0 bg-[linear-gradient(to_right,#0f172a_1px,transparent_1px),linear-gradient(to_bottom,#0f172a_1px,transparent_1px)] bg-[size:3rem_3rem] pointer-events-none opacity-40"></div>
    <div class="absolute bottom-0 left-0 w-[50%] h-[50%] bg-indigo-900/10 rounded-full blur-[100px] pointer-events-none"></div>

    <!-- HUD Header -->
    <header class="border-b border-indigo-500/20 bg-slate-900/60 backdrop-blur-md sticky top-0 z-10 px-6 py-4 shadow-lg">
      <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-lg border border-indigo-500 bg-indigo-950/50 flex items-center justify-center shadow-[0_0_15px_rgba(99,102,241,0.4)] animate-pulse">
            <Compass class="w-6 h-6 text-indigo-400" />
          </div>
          <div>
            <h1 class="text-xl font-bold tracking-wider text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 via-purple-300 to-indigo-200">
              CRANE RFID AUTO-PUTAWAY
            </h1>
            <p class="text-xs text-indigo-500/60 uppercase tracking-widest font-mono">Real-time Location-Level Overhead Crane Telematics</p>
          </div>
        </div>

        <div class="flex items-center gap-4">
          <button 
            @click="showHelpModal = true"
            class="flex items-center gap-2 text-xs font-mono font-medium border border-indigo-500/30 bg-indigo-950/20 text-indigo-400 hover:bg-indigo-900/30 transition-all px-3 py-1.5 rounded-lg"
          >
            <HelpCircle class="w-4 h-4" />
            <span>PANDUAN SIMULASI</span>
          </button>

          <button 
            @click="isMuted = !isMuted"
            class="flex items-center gap-2 text-xs font-mono font-medium transition-all px-3 py-1.5 rounded-lg border"
            :class="isMuted 
              ? 'border-red-500/40 bg-red-950/20 text-red-400' 
              : 'border-indigo-500/30 bg-indigo-950/20 text-indigo-400'"
          >
            <component :is="isMuted ? VolumeX : Volume2" class="w-4 h-4" />
            <span>{{ isMuted ? 'SOUND OFF' : 'SOUND ON' }}</span>
          </button>
          
          <a
            :href="route('warehouse.loading.index')"
            class="text-xs font-mono border border-slate-700 hover:border-indigo-500/50 text-slate-300 px-4 py-1.5 rounded-lg transition-all"
          >
            KEMBALI KE ANTRIAN
          </a>
        </div>
      </div>
    </header>

    <main class="max-w-7xl mx-auto p-4 md:p-6 lg:p-8 space-y-6">
      
      <!-- Top selectors and HUD Metrics -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Selector -->
        <div class="bg-slate-900/60 border border-slate-800 p-4 rounded-xl flex items-center gap-4">
          <Layers class="w-8 h-8 text-indigo-400 shrink-0" />
          <div class="flex-1">
            <span class="text-[10px] font-mono text-slate-400 uppercase">PILIH AREA GUDANG (WAREHOUSE)</span>
            <select 
              v-model="selectedWarehouseId"
              class="w-full bg-slate-950 text-slate-200 border border-slate-800 rounded-lg px-2.5 py-1.5 mt-1 text-xs focus:outline-none focus:border-indigo-500 font-mono"
            >
              <option 
                v-for="wh in warehouses" 
                :key="wh.id" 
                :value="wh.id"
              >
                {{ wh.name }} ({{ wh.code }})
              </option>
            </select>
          </div>
        </div>

        <!-- Metric 1: Telemetry status -->
        <div class="bg-slate-900/60 border border-slate-800 p-4 rounded-xl flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-ping"></div>
            <div>
              <span class="text-[10px] font-mono text-slate-400 uppercase block">CRANE RFID TELEMETRY</span>
              <span class="text-xs font-mono text-emerald-400 font-bold">ONLINE & SYNCED</span>
            </div>
          </div>
          <Cpu class="w-6 h-6 text-emerald-400/80" />
        </div>

        <!-- Metric 2: Crane Status -->
        <div class="bg-slate-900/60 border border-slate-800 p-4 rounded-xl flex items-center justify-between"
          :class="hookedLotId ? 'border-amber-500/30 bg-amber-950/10' : 'border-slate-800'"
        >
          <div class="flex items-center gap-3">
            <Anchor class="w-5 h-5" :class="hookedLotId ? 'text-amber-400 animate-bounce' : 'text-slate-500'" />
            <div>
              <span class="text-[10px] font-mono text-slate-400 uppercase block">CRANE HOIST LOCK STATE</span>
              <span class="text-xs font-mono font-bold" :class="hookedLotId ? 'text-amber-400' : 'text-slate-500'">
                {{ hookedLotId ? 'COIL ENGAGED' : 'HOOK VACANT' }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Layout Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- LEFT: Available Lots Docking Bay (col 4) -->
        <div class="lg:col-span-4 bg-slate-900/60 border border-slate-800 rounded-2xl p-5 backdrop-blur-md flex flex-col min-h-[400px]">
          <div class="border-b border-slate-800 pb-3 mb-4">
            <h2 class="text-xs font-bold font-mono tracking-wider text-indigo-400 uppercase flex items-center gap-2">
              <Layers class="w-4 h-4" />
              COIL RECEIVING / WIP BAY (LOT DOCK)
            </h2>
            <p class="text-[10px] text-slate-500 font-mono mt-1">Daftar coil hasil produksi/masuk yang belum diposisikan di rak.</p>
          </div>

          <div class="flex-1 overflow-y-auto space-y-3 max-h-[450px] pr-2 custom-scrollbar">
            <div v-if="activeLots.length === 0" class="h-full flex flex-col items-center justify-center text-center text-slate-600 font-mono p-6 border border-dashed border-slate-800 rounded-xl">
              <AlertTriangle class="w-8 h-8 text-slate-700 mb-2" />
              <span>DOCK IS EMPTY</span>
              <span class="text-[9px] text-slate-700 mt-1">No active available coils found. Run database seeder first.</span>
            </div>

            <!-- Lot Cards -->
            <div 
              v-for="lot in activeLots"
              :key="lot.id"
              class="border rounded-xl p-3 cursor-pointer transition-all hover:bg-slate-950/60"
              :class="[
                lot.location_id ? 'border-slate-800/40 bg-slate-950/20 opacity-60' : 'border-slate-800 bg-slate-950/40',
                hookedLotId === lot.id ? 'ring-2 ring-indigo-500 border-transparent bg-indigo-950/20' : ''
              ]"
              @click="selectLot(lot.id)"
            >
              <div class="flex items-center justify-between mb-1.5">
                <span class="text-[10px] font-bold font-mono text-indigo-400">#{{ lot.coil_number }}</span>
                <span class="text-[9px] font-mono px-1.5 py-0.5 rounded"
                  :class="lot.location_id ? 'bg-indigo-950/30 text-indigo-300' : 'bg-amber-950/30 text-amber-300'"
                >
                  {{ lot.location_id ? 'IN RACK: ' + lot.location?.name : 'WIP BAY' }}
                </span>
              </div>
              
              <h4 class="text-xs font-semibold text-slate-200">{{ lot.product?.name }}</h4>
              
              <div class="grid grid-cols-3 gap-2 mt-2 pt-2 border-t border-slate-900/60 text-[9px] font-mono text-slate-500">
                <div>
                  <span class="block text-slate-600">WEIGHT</span>
                  <span class="font-bold text-slate-400">{{ Number(lot.weight).toLocaleString() }} Kg</span>
                </div>
                <div>
                  <span class="block text-slate-600">THICKNESS</span>
                  <span class="font-bold text-slate-400">{{ lot.thickness }} mm</span>
                </div>
                <div>
                  <span class="block text-slate-600">WIDTH</span>
                  <span class="font-bold text-slate-400">{{ lot.width }} mm</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT: Interactive 2D Storage Rack Layout (col 8) -->
        <div class="lg:col-span-8 bg-slate-900/60 border border-slate-800 rounded-2xl p-6 backdrop-blur-md flex flex-col">
          <div class="border-b border-slate-800 pb-3 mb-4 flex items-center justify-between">
            <div>
              <h2 class="text-xs font-bold font-mono tracking-wider text-cyan-400 uppercase flex items-center gap-2">
                <MapPin class="w-4 h-4" />
                2D COIL STORAGE BAY MAP (GRID LAYOUT)
              </h2>
              <p class="text-[10px] text-slate-500 font-mono mt-1">Rencana area tata letak sel gudang aktif. Pilih coil di sebelah kiri lalu klik slot kosong.</p>
            </div>
            
            <div class="text-[10px] font-mono text-slate-400 uppercase bg-slate-950 px-2.5 py-1.5 rounded-lg border border-slate-800 flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-cyan-500 animate-pulse"></span>
              <span>GRID CELL STORAGE ACTIVE</span>
            </div>
          </div>

          <!-- Locations visual grid -->
          <div class="flex-1 min-h-[350px] p-4 bg-slate-950/60 border border-slate-900 rounded-xl relative">
            <div v-if="gridLocations.length === 0" class="h-full flex items-center justify-center text-slate-600 font-mono text-xs">
              TIDAK ADA LOKASI AKTIF TERDAFTAR DI GUDANG INI
            </div>

            <!-- visual grid cells container -->
            <div 
              v-else 
              class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-4"
            >
              <div 
                v-for="loc in gridLocations"
                :key="loc.id"
                class="border border-slate-800 bg-slate-900/30 rounded-xl p-3.5 flex flex-col justify-between items-center text-center relative group overflow-hidden min-h-[110px] transition-all"
                :class="[
                  hookedLotId && !getLotInLocation(loc.id) ? 'hover:border-cyan-500 hover:bg-cyan-950/10 cursor-pointer scale-[1.02]' : '',
                  getLotInLocation(loc.id) ? 'border-indigo-500/50 bg-indigo-950/10' : ''
                ]"
                @click="() => {
                  if (hookedLotId && !getLotInLocation(loc.id)) {
                    dropLot(loc);
                  }
                }"
              >
                <!-- Location Label code -->
                <span class="text-[10px] font-mono font-bold tracking-wider text-slate-500 block mb-1 uppercase">{{ loc.name }}</span>

                <!-- Grid Visual State -->
                <div class="flex-1 flex items-center justify-center">
                  <!-- Mapped steel coil graphic -->
                  <div 
                    v-if="getLotInLocation(loc.id)" 
                    class="flex flex-col items-center gap-1 animate-fadeIn"
                  >
                    <!-- Metal Coil custom icon -->
                    <div class="w-10 h-10 rounded-full border border-indigo-400 bg-indigo-950 flex items-center justify-center shadow-[0_0_10px_rgba(99,102,241,0.4)] relative">
                      <div class="w-4 h-4 rounded-full border border-indigo-400/50 flex items-center justify-center">
                        <div class="w-1.5 h-1.5 rounded-full bg-indigo-400"></div>
                      </div>
                      <span class="absolute -top-1 -right-1 flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
                      </span>
                    </div>
                    <span class="text-[9px] font-mono font-bold text-slate-400 truncate max-w-[85px]">
                      {{ getLotInLocation(loc.id).coil_number }}
                    </span>
                  </div>

                  <!-- Vacant/Empty Slot layout -->
                  <div v-else class="flex flex-col items-center justify-center">
                    <span class="text-[8px] font-mono text-slate-700 block uppercase">VACANT</span>
                    <span class="text-[8px] font-mono text-slate-700 block">Cap: {{ loc.capacity || 0 }}</span>
                  </div>
                </div>

                <!-- Hover button overlays for hooked Lot state -->
                <div 
                  v-if="hookedLotId && !getLotInLocation(loc.id)"
                  class="absolute inset-0 bg-cyan-950/90 flex flex-col items-center justify-center text-center gap-1.5 opacity-0 group-hover:opacity-100 transition-all duration-200"
                >
                  <ArrowRight class="w-5 h-5 text-cyan-400 animate-pulse" />
                  <span class="text-[9px] font-mono font-bold text-cyan-300">DROP COIL HERE</span>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- Live Terminal Telematics Console logs (scrolling panel) -->
      <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5 backdrop-blur-md shadow-xl flex flex-col">
        <div class="border-b border-slate-800 pb-3 mb-4 flex items-center justify-between">
          <h3 class="text-xs font-bold font-mono tracking-wider text-emerald-400 uppercase flex items-center gap-2">
            <Terminal class="w-4 h-4" />
            CRANE RFID & TELEMETRY STREAM LOGS
          </h3>
          <span class="text-[9px] font-mono text-slate-500 animate-pulse">LISTENING ON COM4...</span>
        </div>

        <div class="min-h-[120px] max-h-[180px] overflow-y-auto custom-scrollbar bg-slate-950/80 rounded-xl p-4 space-y-2 text-xs font-mono">
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

    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-900 bg-slate-950 py-6 text-center text-xs text-slate-500 font-mono">
      <span>&copy; {{ new Date().getFullYear() }} USICS CRANE AUTONOMOUS AUTO-PUTAWAY SYSTEM.</span>
    </footer>

    <!-- Modal Panduan Simulasi -->
    <div v-if="showHelpModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm" @click="showHelpModal = false"></div>
      
      <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-2xl p-6 shadow-2xl relative z-10 animate-fadeIn text-slate-200">
        <div class="flex items-center justify-between border-b border-slate-800 pb-4 mb-4">
          <div class="flex items-center gap-2">
            <Compass class="w-5 h-5 text-indigo-400 animate-pulse" />
            <h3 class="text-sm font-bold font-mono tracking-wider text-transparent bg-clip-text bg-gradient-to-r from-indigo-450 via-indigo-300 to-indigo-200">
              PANDUAN SIMULASI CRANE RFID
            </h3>
          </div>
          <button @click="showHelpModal = false" class="text-slate-500 hover:text-slate-200 text-xs font-mono p-1 transition-colors">
            [TUTUP]
          </button>
        </div>

        <div class="space-y-4 text-xs leading-relaxed font-sans overflow-y-auto max-h-[360px] pr-2 custom-scrollbar">
          <p class="text-slate-400">
            Modul ini mensimulasikan pemindahan **Raw Material Coil** di dalam gudang secara otomatis (*Auto-Putaway*) menggunakan sensor deteksi RFID pada overhead crane.
          </p>

          <div class="bg-indigo-950/30 border border-indigo-500/20 rounded-xl p-4 space-y-3">
            <h4 class="font-bold text-indigo-400 font-mono uppercase text-[10px] tracking-wider">Langkah-Langkah Simulasi:</h4>
            <ol class="list-decimal list-inside space-y-2.5 text-slate-300">
              <li>
                <strong class="text-white">Kaitkan/Lock Crane ke Coil:</strong> Pilih salah satu Coil di panel kiri **"DOCKING BAY / LOTS"** yang berstatus <span class="text-emerald-400 font-bold font-mono text-[9px] px-1.5 py-0.5 rounded bg-emerald-950/50">AVAILABLE</span>, kemudian klik tombol <strong class="text-indigo-400">"HOOK COIL"</strong>.
              </li>
              <li>
                <strong class="text-white">Dengar Efek Suara:</strong> Derek akan mengunci (<span class="text-amber-400 font-bold font-mono text-[9px] px-1.5 py-0.5 rounded bg-amber-950/50">COIL ENGAGED</span>) dan mengeluarkan suara dengung motor mekanis (*Hum Sound*) di browser Anda.
              </li>
              <li>
                <strong class="text-white">Pindahkan ke Slot Rak 2D:</strong> Arahkan kursor Anda ke area pemetaan rak 2D di sebelah kanan, temukan slot yang bertanda <span class="text-slate-700 font-mono text-[9px]">VACANT</span>, kemudian layangkan kursor dan klik tombol <strong class="text-cyan-400">"DROP COIL HERE"</strong>.
              </li>
              <li>
                <strong class="text-white">Deteksi Sensor RFID & Auto-Putaway:</strong> Derek akan bergerak, disusul suara benturan logam (*Metal Clank*) dan bunyi bip sukses (*RFID Beep*). Lokasi fisik Coil (`location_id`) di database langsung ter-update secara real-time.
              </li>
            </ol>
          </div>

          <div class="border-t border-slate-800 pt-3.5 space-y-2">
            <h4 class="font-bold text-slate-400 font-mono uppercase text-[10px] tracking-wider">Hasil Sinkronisasi Database:</h4>
            <ul class="list-disc list-inside space-y-2 text-slate-400 text-[11px]">
              <li>
                <strong class="text-slate-300">Log Mutasi Stok:</strong> Mengurangi stok di lokasi lama (`transfer_out`) dan menambah stok di lokasi baru (`transfer_in`) secara otomatis.
              </li>
              <li>
                <strong class="text-slate-300">Reader Telemetry:</strong> Log scan direkam pada RFID Scan Logs oleh reader <code class="bg-slate-950 px-1.5 py-0.5 rounded font-mono text-emerald-400 text-[10px]">READER-CRANE-01</code>.
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style>
/* Custom animations for simulation UI */
@keyframes fadeIn {
  from { opacity: 0; transform: scale(0.95); }
  to { opacity: 1; transform: scale(1); }
}
.animate-fadeIn {
  animation: fadeIn 0.25s ease-out forwards;
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
  background: rgba(99, 102, 241, 0.2);
  border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(99, 102, 241, 0.4);
}
</style>
