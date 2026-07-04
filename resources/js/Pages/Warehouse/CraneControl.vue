<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
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
  HelpCircle,
  Sun,
  Moon,
  Info
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
const showSyncInfoModal = ref(false);

// --- Theme Sync ---
const isLightMode = ref(false);
const toggleTheme = () => {
  isLightMode.value = !isLightMode.value;
  if (isLightMode.value) {
    document.documentElement.classList.remove('dark');
    localStorage.setItem('theme', 'light');
  } else {
    document.documentElement.classList.add('dark');
    localStorage.setItem('theme', 'dark');
  }
};

let observer;
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

  // Sync theme
  isLightMode.value = !document.documentElement.classList.contains('dark');
  observer = new MutationObserver(() => {
    isLightMode.value = !document.documentElement.classList.contains('dark');
  });
  observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
});

onUnmounted(() => {
  if (observer) observer.disconnect();
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

  <div class="min-h-screen font-sans antialiased overflow-x-hidden relative transition-colors duration-300"
    :class="isLightMode ? 'bg-slate-50 text-slate-800' : 'bg-slate-950 text-slate-100'">
    <!-- Grid overlay background -->
    <div v-if="!isLightMode" class="absolute inset-0 bg-[linear-gradient(to_right,#0f172a_1px,transparent_1px),linear-gradient(to_bottom,#0f172a_1px,transparent_1px)] bg-[size:3rem_3rem] pointer-events-none opacity-40"></div>
    <div v-else class="absolute inset-0 bg-[linear-gradient(to_right,#e2e8f0_1px,transparent_1px),linear-gradient(to_bottom,#e2e8f0_1px,transparent_1px)] bg-[size:3rem_3rem] pointer-events-none opacity-60"></div>
    <div v-if="!isLightMode" class="absolute bottom-0 left-0 w-[50%] h-[50%] bg-indigo-900/10 rounded-full blur-[100px] pointer-events-none"></div>
    <div v-else class="absolute bottom-0 left-0 w-[50%] h-[50%] bg-indigo-100/30 rounded-full blur-[100px] pointer-events-none"></div>

    <!-- HUD Header -->
    <header class="border-b sticky top-0 z-10 px-6 py-4 shadow-lg backdrop-blur-md transition-colors"
      :class="isLightMode ? 'border-slate-200 bg-white/80' : 'border-indigo-500/20 bg-slate-900/60'">
      <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-lg flex items-center justify-center transition-all"
            :class="isLightMode ? 'border border-indigo-250 bg-indigo-50 shadow-sm' : 'border border-indigo-500 bg-indigo-950/50 shadow-[0_0_15px_rgba(99,102,241,0.4)] animate-pulse'">
            <Compass class="w-6 h-6" :class="isLightMode ? 'text-indigo-650' : 'text-indigo-400'" />
          </div>
          <div>
            <h1 class="text-xl font-bold tracking-wider transition-colors"
              :class="isLightMode ? 'text-slate-800' : 'text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 via-purple-300 to-indigo-200'">
              CRANE RFID AUTO-PUTAWAY
            </h1>
            <p class="text-xs uppercase tracking-widest font-mono transition-colors"
              :class="isLightMode ? 'text-slate-400' : 'text-indigo-500/60'">Real-time Location-Level Overhead Crane Telematics</p>
          </div>
        </div>

        <div class="flex items-center gap-4">
          <!-- RFID Sync Info Button (New!) -->
          <button 
            @click="showSyncInfoModal = true"
            class="flex items-center gap-2 text-xs font-mono font-medium transition-all px-3 py-1.5 rounded-lg border"
            :class="isLightMode 
              ? 'border-emerald-250 bg-emerald-50 text-emerald-700 hover:bg-emerald-100/70 shadow-sm' 
              : 'border-emerald-500/30 bg-emerald-950/20 text-emerald-400 hover:bg-emerald-900/30'"
          >
            <Info class="w-4 h-4" />
            <span>SIMULASI RFID 2D</span>
          </button>

          <button 
            @click="showHelpModal = true"
            class="flex items-center gap-2 text-xs font-mono font-medium border transition-all px-3 py-1.5 rounded-lg"
            :class="isLightMode 
              ? 'border-indigo-250 bg-indigo-50 text-indigo-700 hover:bg-indigo-100/70 shadow-sm' 
              : 'border-indigo-500/30 bg-indigo-950/20 text-indigo-400 hover:bg-indigo-900/30'"
          >
            <HelpCircle class="w-4 h-4" />
            <span>PANDUAN SIMULASI</span>
          </button>

          <button 
            @click="isMuted = !isMuted"
            class="flex items-center gap-2 text-xs font-mono font-medium transition-all px-3 py-1.5 rounded-lg border"
            :class="isMuted 
              ? 'border-red-500/40 bg-red-950/20 text-red-400' 
              : (isLightMode 
                  ? 'border-indigo-200 bg-white text-indigo-700 hover:bg-slate-50 shadow-sm' 
                  : 'border-indigo-500/30 bg-indigo-950/20 text-indigo-400')"
          >
            <component :is="isMuted ? VolumeX : Volume2" class="w-4 h-4" />
            <span>{{ isMuted ? 'SOUND OFF' : 'SOUND ON' }}</span>
          </button>

          <button 
            @click="toggleTheme"
            class="flex items-center gap-2 text-xs font-mono font-medium transition-all px-3 py-1.5 rounded-lg border"
            :class="isLightMode 
              ? 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 shadow-sm' 
              : 'border-purple-500/20 bg-[#0c0517] text-purple-400 hover:bg-[#160c29]'"
          >
            <Sun v-if="!isLightMode" class="w-4 h-4 text-amber-500" />
            <Moon v-else class="w-4 h-4 text-indigo-500" />
            <span>{{ isLightMode ? 'MODE GELAP' : 'MODE TERANG' }}</span>
          </button>
          
          <a
            :href="route('warehouse.loading.index')"
            class="text-xs font-mono border px-4 py-1.5 rounded-lg transition-all"
            :class="isLightMode 
              ? 'border-slate-205 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-350 shadow-sm' 
              : 'border-slate-700 hover:border-indigo-400 text-slate-300'"
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
        <div class="p-4 rounded-xl flex items-center gap-4 border transition-colors"
          :class="isLightMode ? 'bg-white border-slate-200 text-slate-800' : 'bg-slate-900/60 border-slate-800 text-white'">
          <Layers class="w-8 h-8 text-indigo-400 shrink-0" />
          <div class="flex-1">
            <span class="text-[10px] font-mono uppercase" :class="isLightMode ? 'text-slate-500' : 'text-slate-400'">PILIH AREA GUDANG (WAREHOUSE)</span>
            <select 
              v-model="selectedWarehouseId"
              class="w-full rounded-lg px-2.5 py-1.5 mt-1 text-xs focus:outline-none focus:border-indigo-500 font-mono transition-colors"
              :class="isLightMode ? 'bg-slate-100 border-slate-200 text-slate-850' : 'bg-slate-950 text-slate-200 border-slate-800'"
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
        <div class="p-4 rounded-xl flex items-center justify-between border transition-colors"
          :class="isLightMode ? 'bg-white border-slate-200 text-slate-800' : 'bg-slate-900/60 border-slate-800 text-white'">
          <div class="flex items-center gap-3">
            <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-ping"></div>
            <div>
              <span class="text-[10px] font-mono uppercase block" :class="isLightMode ? 'text-slate-500' : 'text-slate-400'">CRANE RFID TELEMETRY</span>
              <span class="text-xs font-mono text-emerald-650 font-bold">ONLINE & SYNCED</span>
            </div>
          </div>
          <Cpu class="w-6 h-6 text-emerald-555" />
        </div>

        <!-- Metric 2: Crane Status -->
        <div class="p-4 rounded-xl flex items-center justify-between border transition-colors"
          :class="hookedLotId 
            ? (isLightMode ? 'border-amber-400 bg-amber-50 text-slate-850' : 'border-amber-500/30 bg-amber-950/10 text-white') 
            : (isLightMode ? 'bg-white border-slate-200 text-slate-800' : 'bg-slate-900/60 border-slate-800 text-white')"
        >
          <div class="flex items-center gap-3">
            <Anchor class="w-5 h-5" :class="hookedLotId ? 'text-amber-500 animate-bounce' : 'text-slate-400 dark:text-slate-500'" />
            <div>
              <span class="text-[10px] font-mono uppercase block" :class="isLightMode ? 'text-slate-500' : 'text-slate-400'">CRANE HOIST LOCK STATE</span>
              <span class="text-xs font-mono font-bold" :class="hookedLotId ? 'text-amber-600 dark:text-amber-400' : 'text-slate-400 dark:text-slate-500'">
                {{ hookedLotId ? 'COIL ENGAGED' : 'HOOK VACANT' }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Layout Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- LEFT: Available Lots Docking Bay (col 4) -->
        <div class="lg:col-span-4 border rounded-2xl p-5 backdrop-blur-md flex flex-col min-h-[400px] transition-colors duration-300"
          :class="isLightMode ? 'bg-white border-slate-200 text-slate-800' : 'bg-slate-900/60 border-slate-800 text-slate-100'">
          <div class="border-b pb-3 mb-4" :class="isLightMode ? 'border-slate-200' : 'border-slate-800'">
            <h2 class="text-xs font-bold font-mono tracking-wider uppercase flex items-center gap-2"
              :class="isLightMode ? 'text-indigo-650' : 'text-indigo-400'">
              <Layers class="w-4 h-4" />
              COIL RECEIVING / WIP BAY (LOT DOCK)
            </h2>
            <p class="text-[10px] font-mono mt-1" :class="isLightMode ? 'text-slate-500' : 'text-slate-500'">Daftar coil hasil produksi/masuk yang belum diposisikan di rak.</p>
          </div>

          <div class="flex-1 overflow-y-auto space-y-3 max-h-[450px] pr-2 custom-scrollbar">
            <div v-if="activeLots.length === 0" class="h-full flex flex-col items-center justify-center text-center font-mono p-6 border border-dashed rounded-xl"
              :class="isLightMode ? 'border-slate-300 text-slate-500' : 'border-slate-800 text-slate-600'">
              <AlertTriangle class="w-8 h-8 mb-2" :class="isLightMode ? 'text-slate-400' : 'text-slate-700'" />
              <span>DOCK IS EMPTY</span>
              <span class="text-[9px] mt-1" :class="isLightMode ? 'text-slate-400' : 'text-slate-700'">No active available coils found. Run database seeder first.</span>
            </div>

            <!-- Lot Cards -->
            <div 
              v-for="lot in activeLots"
              :key="lot.id"
              class="border rounded-xl p-3 cursor-pointer transition-all shadow-sm dark:shadow-none"
              :class="[
                lot.location_id ? (isLightMode ? 'border-slate-200 bg-slate-50 opacity-60' : 'border-slate-800 bg-slate-950/20 opacity-60') : (isLightMode ? 'border-slate-200 bg-white hover:bg-slate-50' : 'border-slate-800 bg-slate-950/40 hover:bg-slate-950/60'),
                hookedLotId === lot.id ? (isLightMode ? 'ring-2 ring-indigo-500 border-transparent bg-indigo-50' : 'ring-2 ring-indigo-500 border-transparent bg-indigo-950/20') : ''
              ]"
              @click="selectLot(lot.id)"
            >
              <div class="flex items-center justify-between mb-1.5">
                <span class="text-[10px] font-bold font-mono" :class="isLightMode ? 'text-indigo-650' : 'text-indigo-400'">#{{ lot.coil_number }}</span>
                <span class="text-[9px] font-mono px-1.5 py-0.5 rounded"
                  :class="lot.location_id 
                    ? (isLightMode ? 'bg-indigo-100 text-indigo-800' : 'bg-indigo-950/30 text-indigo-300') 
                    : (isLightMode ? 'bg-amber-100 text-amber-800' : 'bg-amber-950/30 text-amber-300')"
                >
                  {{ lot.location_id ? 'IN RACK: ' + lot.location?.name : 'WIP BAY' }}
                </span>
              </div>
              
              <h4 class="text-xs font-semibold" :class="isLightMode ? 'text-slate-800' : 'text-slate-200'">{{ lot.product?.name }}</h4>
              
              <div class="grid grid-cols-3 gap-2 mt-2 pt-2 border-t text-[9px] font-mono"
                :class="isLightMode ? 'border-slate-100 text-slate-500' : 'border-slate-900/60 text-slate-500'">
                <div>
                  <span class="block" :class="isLightMode ? 'text-slate-400' : 'text-slate-600'">WEIGHT</span>
                  <span class="font-bold" :class="isLightMode ? 'text-slate-700' : 'text-slate-400'">{{ Number(lot.weight).toLocaleString() }} Kg</span>
                </div>
                <div>
                  <span class="block" :class="isLightMode ? 'text-slate-400' : 'text-slate-600'">THICKNESS</span>
                  <span class="font-bold" :class="isLightMode ? 'text-slate-700' : 'text-slate-400'">{{ lot.thickness }} mm</span>
                </div>
                <div>
                  <span class="block" :class="isLightMode ? 'text-slate-400' : 'text-slate-600'">WIDTH</span>
                  <span class="font-bold" :class="isLightMode ? 'text-slate-700' : 'text-slate-400'">{{ lot.width }} mm</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT: Interactive 2D Storage Rack Layout (col 8) -->
        <div class="lg:col-span-8 border rounded-2xl p-6 backdrop-blur-md flex flex-col transition-colors duration-300"
          :class="isLightMode ? 'bg-white border-slate-200' : 'bg-slate-900/60 border-slate-800'">
          <div class="border-b pb-3 mb-4 flex items-center justify-between"
            :class="isLightMode ? 'border-slate-200' : 'border-slate-800'">
            <div>
              <h2 class="text-xs font-bold font-mono tracking-wider uppercase flex items-center gap-2"
                :class="isLightMode ? 'text-cyan-650' : 'text-cyan-400'">
                <MapPin class="w-4 h-4" />
                2D COIL STORAGE BAY MAP (GRID LAYOUT)
              </h2>
              <p class="text-[10px] font-mono mt-1" :class="isLightMode ? 'text-slate-500' : 'text-slate-550'">Rencana area tata letak sel gudang aktif. Pilih coil di sebelah kiri lalu klik slot kosong.</p>
            </div>
            
            <div class="text-[10px] font-mono uppercase px-2.5 py-1.5 rounded-lg border flex items-center gap-1.5"
              :class="isLightMode ? 'bg-slate-50 border-slate-200 text-slate-600' : 'bg-slate-955 border-slate-800 text-slate-400'">
              <span class="w-2 h-2 rounded-full bg-cyan-500 animate-pulse"></span>
              <span>GRID CELL STORAGE ACTIVE</span>
            </div>
          </div>

          <!-- Locations visual grid -->
          <div class="flex-1 min-h-[350px] p-4 border rounded-xl relative"
            :class="isLightMode ? 'bg-slate-50 border-slate-200' : 'bg-slate-950/60 border-slate-900'">
            <div v-if="gridLocations.length === 0" class="h-full flex items-center justify-center text-slate-550 font-mono text-xs">
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
                class="border rounded-xl p-3.5 flex flex-col justify-between items-center text-center relative group overflow-hidden min-h-[110px] transition-all"
                :class="[
                  hookedLotId && !getLotInLocation(loc.id) ? (isLightMode ? 'border-slate-250 bg-white hover:border-cyan-500 hover:bg-cyan-50 cursor-pointer scale-[1.02]' : 'border-slate-800 bg-slate-900/30 hover:border-cyan-500 hover:bg-cyan-950/10 cursor-pointer scale-[1.02]') : (isLightMode ? 'border-slate-200 bg-white' : 'border-slate-800 bg-slate-900/30'),
                  getLotInLocation(loc.id) ? (isLightMode ? 'border-indigo-400 bg-indigo-50/50' : 'border-indigo-500/50 bg-indigo-950/10') : ''
                ]"
                @click="() => {
                  if (hookedLotId && !getLotInLocation(loc.id)) {
                    dropLot(loc);
                  }
                }"
              >
                <!-- Location Label code -->
                <span class="text-[10px] font-mono font-bold tracking-wider block mb-1 uppercase"
                  :class="isLightMode ? 'text-slate-500' : 'text-slate-500'">{{ loc.name }}</span>

                <!-- Grid Visual State -->
                <div class="flex-1 flex items-center justify-center">
                  <!-- Mapped steel coil graphic -->
                  <div 
                    v-if="getLotInLocation(loc.id)" 
                    class="flex flex-col items-center gap-1 animate-fadeIn"
                  >
                    <!-- Metal Coil custom icon -->
                    <div class="w-10 h-10 rounded-full border flex items-center justify-center relative shadow-md dark:shadow-[0_0_10px_rgba(99,102,241,0.4)]"
                      :class="isLightMode ? 'border-indigo-400 bg-indigo-50' : 'border-indigo-400 bg-indigo-950'">
                      <div class="w-4 h-4 rounded-full border flex items-center justify-center"
                        :class="isLightMode ? 'border-indigo-400/50' : 'border-indigo-400/50'">
                        <div class="w-1.5 h-1.5 rounded-full bg-indigo-500 dark:bg-indigo-400"></div>
                      </div>
                      <span class="absolute -top-1 -right-1 flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
                      </span>
                    </div>
                    <span class="text-[9px] font-mono font-bold truncate max-w-[85px]"
                      :class="isLightMode ? 'text-slate-650' : 'text-slate-400'">
                      {{ getLotInLocation(loc.id).coil_number }}
                    </span>
                  </div>

                  <!-- Vacant/Empty Slot layout -->
                  <div v-else class="flex flex-col items-center justify-center">
                    <span class="text-[8px] font-mono block uppercase" :class="isLightMode ? 'text-slate-400' : 'text-slate-700'">VACANT</span>
                    <span class="text-[8px] font-mono block" :class="isLightMode ? 'text-slate-400' : 'text-slate-700'">Cap: {{ loc.capacity || 0 }}</span>
                  </div>
                </div>

                <!-- Hover button overlays for hooked Lot state -->
                <div 
                  v-if="hookedLotId && !getLotInLocation(loc.id)"
                  class="absolute inset-0 flex flex-col items-center justify-center text-center gap-1.5 opacity-0 group-hover:opacity-100 transition-all duration-200"
                  :class="isLightMode ? 'bg-cyan-50/95' : 'bg-cyan-950/90'"
                >
                  <ArrowRight class="w-5 h-5 text-cyan-500 animate-pulse" />
                  <span class="text-[9px] font-mono font-bold text-cyan-600">DROP COIL HERE</span>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- Live Terminal Telematics Console logs (scrolling panel) -->
      <div class="border rounded-2xl p-5 backdrop-blur-md shadow-xl flex flex-col transition-colors duration-300"
        :class="isLightMode ? 'bg-white border-slate-200' : 'bg-slate-900/60 border-slate-800'">
        <div class="border-b pb-3 mb-4 flex items-center justify-between"
          :class="isLightMode ? 'border-slate-200' : 'border-slate-800'">
          <h3 class="text-xs font-bold font-mono tracking-wider uppercase flex items-center gap-2"
            :class="isLightMode ? 'text-emerald-700' : 'text-emerald-400'">
            <Terminal class="w-4 h-4" />
            CRANE RFID & TELEMETRY STREAM LOGS
          </h3>
          <span class="text-[9px] font-mono text-slate-400 dark:text-slate-500 animate-pulse">LISTENING ON COM4...</span>
        </div>

        <div class="min-h-[120px] max-h-[180px] overflow-y-auto custom-scrollbar rounded-xl p-4 space-y-2 text-xs font-mono"
          :class="isLightMode ? 'bg-slate-50 border border-slate-200 text-slate-800' : 'bg-slate-950/80'">
          <div 
            v-for="(log, i) in logs" 
            :key="i"
            class="flex items-start gap-3"
          >
            <span class="text-slate-500 shrink-0">{{ log.time }}</span>
            <span class="leading-relaxed font-sans" :class="isLightMode ? 'text-slate-700' : 'text-emerald-400/90'">{{ log.text }}</span>
          </div>
        </div>
      </div>

    </main>

    <!-- Footer -->
    <footer class="border-t py-6 text-center text-xs font-mono transition-colors"
      :class="isLightMode ? 'border-slate-200 bg-white text-slate-500' : 'border-slate-900 bg-slate-950 text-slate-500'">
      <span>&copy; {{ new Date().getFullYear() }} USICS CRANE AUTONOMOUS AUTO-PUTAWAY SYSTEM.</span>
    </footer>

    <!-- Modal Panduan Simulasi -->
    <div v-if="showHelpModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm" @click="showHelpModal = false"></div>
      
      <div class="border rounded-2xl w-full max-w-2xl p-6 shadow-2xl relative z-10 animate-fadeIn transition-colors duration-300"
        :class="isLightMode ? 'bg-white border-slate-250 text-slate-800' : 'bg-slate-900 border-slate-800 text-slate-200'">
        <div class="flex items-center justify-between border-b pb-4 mb-4" :class="isLightMode ? 'border-slate-200' : 'border-slate-800'">
          <div class="flex items-center gap-2">
            <Compass class="w-5 h-5 text-indigo-500 animate-pulse" />
            <h3 class="text-sm font-bold font-mono tracking-wider" :class="isLightMode ? 'text-slate-800' : 'text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 via-indigo-300 to-indigo-200'">
              PANDUAN SIMULASI CRANE RFID
            </h3>
          </div>
          <button @click="showHelpModal = false" class="text-slate-500 hover:text-slate-700 dark:hover:text-slate-200 text-xs font-mono p-1 transition-colors">
            [TUTUP]
          </button>
        </div>

        <div class="space-y-4 text-xs leading-relaxed font-sans overflow-y-auto max-h-[360px] pr-2 custom-scrollbar">
          <p :class="isLightMode ? 'text-slate-600' : 'text-slate-400'">
            Modul ini mensimulasikan pemindahan **Raw Material Coil** di dalam gudang secara otomatis (*Auto-Putaway*) menggunakan sensor deteksi RFID pada overhead crane.
          </p>

          <div class="border rounded-xl p-4 space-y-3"
            :class="isLightMode ? 'bg-indigo-50 border-indigo-200' : 'bg-indigo-950/30 border-indigo-500/20'">
            <h4 class="font-bold font-mono uppercase text-[10px] tracking-wider" :class="isLightMode ? 'text-indigo-700' : 'text-indigo-450'">Langkah-Langkah Simulasi:</h4>
            <ol class="list-decimal list-inside space-y-2.5" :class="isLightMode ? 'text-slate-650' : 'text-slate-300'">
              <li>
                <strong :class="isLightMode ? 'text-slate-900' : 'text-white'">Kaitkan/Lock Crane ke Coil:</strong> Pilih salah satu Coil di panel kiri **"DOCKING BAY / LOTS"** yang berstatus <span class="text-emerald-700 font-bold font-mono text-[9px] px-1.5 py-0.5 rounded bg-emerald-100">AVAILABLE</span>, kemudian klik tombol <strong :class="isLightMode ? 'text-indigo-700' : 'text-indigo-400'">"HOOK COIL"</strong>.
              </li>
              <li>
                <strong :class="isLightMode ? 'text-slate-900' : 'text-white'">Dengar Efek Suara:</strong> Derek akan mengunci (<span class="text-amber-700 font-bold font-mono text-[9px] px-1.5 py-0.5 rounded bg-amber-100">COIL ENGAGED</span>) dan mengeluarkan suara dengung motor mekanis (*Hum Sound*) di browser Anda.
              </li>
              <li>
                <strong :class="isLightMode ? 'text-slate-900' : 'text-white'">Pindahkan ke Slot Rak 2D:</strong> Arahkan kursor Anda ke area pemetaan rak 2D di sebelah kanan, temukan slot yang bertanda <span class="text-slate-500 font-mono text-[9px]">VACANT</span>, kemudian layangkan kursor dan klik tombol <strong :class="isLightMode ? 'text-cyan-705' : 'text-cyan-400'">"DROP COIL HERE"</strong>.
              </li>
              <li>
                <strong :class="isLightMode ? 'text-slate-900' : 'text-white'">Deteksi Sensor RFID & Auto-Putaway:</strong> Derek akan bergerak, disusul suara benturan logam (*Metal Clank*) dan bunyi bip sukses (*RFID Beep*). Lokasi fisik Coil (`location_id`) di database langsung ter-update secara real-time.
              </li>
            </ol>
          </div>

          <div class="border-t pt-3.5 space-y-2" :class="isLightMode ? 'border-slate-200' : 'border-slate-800'">
            <h4 class="font-bold font-mono uppercase text-[10px] tracking-wider" :class="isLightMode ? 'text-slate-700' : 'text-slate-400'">Hasil Sinkronisasi Database:</h4>
            <ul class="list-disc list-inside space-y-2 text-slate-500">
              <li>
                <strong :class="isLightMode ? 'text-slate-800' : 'text-slate-300'">Log Mutasi Stok:</strong> Mengurangi stok di lokasi lama (`transfer_out`) dan menambah stok di lokasi baru (`transfer_in`) secara otomatis.
              </li>
              <li>
                <strong :class="isLightMode ? 'text-slate-800' : 'text-slate-300'">Reader Telemetry:</strong> Log scan direkam pada RFID Scan Logs oleh reader <code class="px-1.5 py-0.5 rounded font-mono text-[10px]" :class="isLightMode ? 'bg-slate-100 text-emerald-800' : 'bg-slate-950 text-emerald-400'">READER-CRANE-01</code>.
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Informasi Sinkronisasi Real-time RFID 2D (New!) -->
    <div v-if="showSyncInfoModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="fixed inset-0 bg-slate-955/80 backdrop-blur-sm" @click="showSyncInfoModal = false"></div>
      
      <div class="border rounded-2xl w-full max-w-2xl p-6 shadow-2xl relative z-10 animate-fadeIn transition-colors duration-300"
        :class="isLightMode ? 'bg-white border-slate-250 text-slate-800' : 'bg-slate-900 border-slate-800 text-slate-200'">
        <div class="flex items-center justify-between border-b pb-4 mb-4" :class="isLightMode ? 'border-slate-200' : 'border-slate-800'">
          <div class="flex items-center gap-2">
            <Info class="w-5 h-5 text-emerald-500 animate-bounce" />
            <h3 class="text-sm font-bold font-mono tracking-wider" :class="isLightMode ? 'text-slate-800' : 'text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 via-emerald-300 to-emerald-250'">
              SINKRONISASI REAL-TIME CRANE RFID
            </h3>
          </div>
          <button @click="showSyncInfoModal = false" class="text-slate-500 hover:text-slate-700 dark:hover:text-slate-205 text-xs font-mono p-1 transition-colors">
            [TUTUP]
          </button>
        </div>

        <div class="space-y-4 text-xs leading-relaxed font-sans overflow-y-auto max-h-[360px] pr-2 custom-scrollbar">
          <p class="text-slate-500 font-mono">
            Bagaimana sistem melakukan sinkronisasi real-time antara derek fisik crane (RFID scanner) dan peta visual 2D?
          </p>

          <div class="border rounded-xl p-4 space-y-3"
            :class="isLightMode ? 'bg-slate-50 border-slate-200' : 'bg-slate-950/40 border-slate-800'">
            <h4 class="font-bold font-mono uppercase text-[10px] tracking-wider text-emerald-600">Mekanisme Arsitektur Telemetri:</h4>
            <ul class="list-disc list-inside space-y-2 text-slate-500">
              <li>
                <strong class="text-slate-700">RFID Tag & Antena Reader:</strong> Setiap coil material dipasangkan tag RFID pasif. Antena RFID terpasang pada hook hoist crane dan setiap slot grid rak 2D.
              </li>
              <li>
                <strong class="text-slate-700">Event-driven State Sync:</strong> Saat hook crane menurunkan coil di slot grid, Reader RFID mendeteksi sinyal tag pada koordinat XYZ slot tersebut dan mengirim data telemetri via port serial COM4.
              </li>
              <li>
                <strong class="text-slate-700">Controller & WebSocket Broadcast:</strong> Web backend (Laravel) menerima payload event, memvalidasi aturan penataan gudang (*putaway rules*), dan memperbarui koordinat `location_id` coil di database. Perubahan dibroadcast via WebSocket ke seluruh client visual 2D secara real-time.
              </li>
            </ul>
          </div>

          <div class="border rounded-xl p-4 space-y-3"
            :class="isLightMode ? 'bg-indigo-50/50 border-indigo-200' : 'bg-indigo-950/30 border-indigo-500/20'">
            <h4 class="font-bold font-mono uppercase text-[10px] tracking-wider text-indigo-650">Langkah Simulasi Sinkronisasi Real-Time:</h4>
            <ol class="list-decimal list-inside space-y-2 text-slate-500">
              <li>Buka halaman cockpit Crane Control di dua browser/layar laptop yang berbeda secara bersamaan.</li>
              <li>Di Layar A, lakukan kait coil (**Hook Coil**) lalu klik slot grid rak kosong (**Drop Coil Here**).</li>
              <li>Perhatikan Layar B secara langsung (tanpa reload/refresh). Layar B otomatis memperbarui peta 2D secara instan (Coil muncul di slot baru dan WIP bay berkurang), disertai dengan telemetry stream logs yang sinkron di kedua layar.</li>
            </ol>
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
