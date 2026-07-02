<script setup>
import { ref, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { 
  Radio, 
  Map, 
  Settings, 
  FileText, 
  Cpu, 
  TrendingUp, 
  Volume2, 
  VolumeX, 
  Terminal, 
  AlertTriangle,
  CheckCircle,
  Truck,
  ArrowRight,
  RefreshCw
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

// Sound States
const isMuted = ref(false);

// Synthesize Beep/Buzz using Web Audio API
const playSound = (type = 'success') => {
  if (isMuted.value || typeof window === 'undefined') return;
  try {
    const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    const osc = audioCtx.createOscillator();
    const gainNode = audioCtx.createGain();
    
    osc.connect(gainNode);
    gainNode.connect(audioCtx.destination);
    
    if (type === 'success') {
      // Success beep: High-pitched short sine wave
      osc.type = 'sine';
      osc.frequency.setValueAtTime(880, audioCtx.currentTime); // A5 note
      gainNode.gain.setValueAtTime(0.1, audioCtx.currentTime);
      gainNode.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.15);
      osc.start();
      osc.stop(audioCtx.currentTime + 0.15);
    } else if (type === 'error') {
      // Error buzz: Low-pitched sawtooth buzz
      osc.type = 'sawtooth';
      osc.frequency.setValueAtTime(120, audioCtx.currentTime); 
      gainNode.gain.setValueAtTime(0.15, audioCtx.currentTime);
      gainNode.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.45);
      osc.start();
      osc.stop(audioCtx.currentTime + 0.45);
    } else if (type === 'warning') {
      // Warning beep: Double medium-pitch beep
      osc.type = 'triangle';
      osc.frequency.setValueAtTime(440, audioCtx.currentTime); // A4 note
      gainNode.gain.setValueAtTime(0.1, audioCtx.currentTime);
      gainNode.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.2);
      osc.start();
      osc.stop(audioCtx.currentTime + 0.2);
    }
  } catch (err) {
    console.error('Failed to play audio:', err);
  }
};

// Simulation Selection States
const selectedDoId = ref('');
const selectedReader = ref('gate_entry');
const simulatedWeight = ref(8500);
const loadingBay = ref('Bay 2 Slitting');
const processing = ref(false);

const selectDO = (doItem) => {
  selectedDoId.value = doItem.id;
  // Pre-fill fields based on current status
  if (doItem.status === 'draft') {
    selectedReader.value = 'gate_entry';
    simulatedWeight.value = 8200; // default empty tare
  } else if (doItem.status === 'picking') {
    selectedReader.value = 'weighbridge_out';
    simulatedWeight.value = 16500; // default loaded gross
    if (doItem.loading_bay) {
      loadingBay.value = doItem.loading_bay;
    }
  } else if (doItem.status === 'packed') {
    selectedReader.value = 'gate_exit';
  }
};

const triggerSimulation = () => {
  if (!selectedDoId.value) {
    playSound('error');
    alert('Silakan pilih salah satu armada/DO untuk simulasi.');
    return;
  }

  processing.value = true;
  
  router.post(route('warehouse.rfid.simulate'), {
    delivery_order_id: selectedDoId.value,
    reader_id: selectedReader.value,
    weight: ['weighbridge_in', 'weighbridge_out'].includes(selectedReader.value) ? simulatedWeight.value : null,
    loading_bay: selectedReader.value === 'bay_loading' ? loadingBay.value : null
  }, {
    preserveScroll: true,
    onSuccess: (page) => {
      const flash = page.props.flash || {};
      if (flash.error) {
        playSound('error');
      } else if (flash.warning) {
        playSound('warning');
      } else {
        playSound('success');
      }
    },
    onError: () => {
      playSound('error');
    },
    onFinish: () => {
      processing.value = false;
    }
  });
};

const getStatusColorClass = (status) => {
  switch (status) {
    case 'success': return 'text-emerald-400 bg-emerald-950/30 border-emerald-500/30';
    case 'warning': return 'text-amber-400 bg-amber-950/30 border-amber-500/30';
    case 'error': return 'text-red-400 bg-red-950/30 border-red-500/30';
    default: return 'text-slate-400 bg-slate-900 border-slate-800';
  }
};
</script>

<template>
  <Head title="RFID Checkpoint Simulation Control HUD" />

  <div class="min-h-screen bg-slate-950 text-slate-100 font-sans antialiased overflow-x-hidden relative">
    <!-- Scanner scanline theme -->
    <div class="absolute inset-0 bg-[linear-gradient(to_right,#09101d_1px,transparent_1px),linear-gradient(to_bottom,#09101d_1px,transparent_1px)] bg-[size:4rem_4rem] pointer-events-none opacity-40"></div>
    <div class="absolute top-0 right-0 w-[50%] h-[50%] bg-blue-900/10 rounded-full blur-[100px] pointer-events-none"></div>

    <!-- HUD Header -->
    <header class="border-b border-blue-500/20 bg-slate-900/60 backdrop-blur-md sticky top-0 z-10 px-6 py-4 shadow-lg">
      <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-lg border border-blue-500 bg-blue-950/50 flex items-center justify-center shadow-[0_0_15px_rgba(59,130,246,0.4)] animate-pulse">
            <Cpu class="w-6 h-6 text-blue-400" />
          </div>
          <div>
            <h1 class="text-xl font-bold tracking-wider text-transparent bg-clip-text bg-gradient-to-r from-blue-400 via-cyan-300 to-blue-200">
              RFID CHECKPOINT SIMULATOR
            </h1>
            <p class="text-xs text-blue-500/60 uppercase tracking-widest font-mono">UAT Testing Room & Hardware Webhook Emulator</p>
          </div>
        </div>

        <div class="flex items-center gap-4">
          <button 
            @click="isMuted = !isMuted"
            class="flex items-center gap-2 text-xs font-mono font-medium transition-all px-3 py-1.5 rounded-lg border"
            :class="isMuted 
              ? 'border-red-500/40 bg-red-950/20 text-red-400' 
              : 'border-blue-500/30 bg-blue-950/20 text-blue-400'"
          >
            <component :is="isMuted ? VolumeX : Volume2" class="w-4 h-4" />
            <span>{{ isMuted ? 'SOUND OFF' : 'SOUND ON' }}</span>
          </button>
          
          <a
            :href="route('warehouse.loading.index')"
            class="text-xs font-mono border border-slate-700 hover:border-blue-500/50 text-slate-300 px-4 py-1.5 rounded-lg transition-all"
          >
            KEMBALI KE ANTRIAN
          </a>
        </div>
      </div>
    </header>

    <main class="max-w-7xl mx-auto p-4 md:p-6 lg:p-8 space-y-6">
      
      <!-- Interactive Grid Layout -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- LEFT PANEL: SIMULATION CONTROL CENTER (col 5) -->
        <div class="lg:col-span-5 flex flex-col bg-slate-900/60 border border-slate-800 rounded-2xl backdrop-blur-md overflow-hidden p-6 shadow-xl">
          <div class="border-b border-slate-800 pb-4 mb-4">
            <h2 class="text-sm font-semibold tracking-wider font-mono text-blue-400 uppercase flex items-center gap-2">
              <Settings class="w-4 h-4 text-blue-400" />
              SIMULATOR CONTROL DECK
            </h2>
          </div>

          <div class="space-y-4 flex-1">
            <!-- 1. Select Active Trial DO -->
            <div>
              <label class="block text-xs font-mono text-slate-400 uppercase mb-1.5">1. Pilih Armada / Delivery Order</label>
              <select 
                v-model="selectedDoId"
                @change="() => {
                  const item = deliveryOrders.find(o => o.id === parseInt(selectedDoId));
                  if (item) selectDO(item);
                }"
                class="w-full bg-slate-950 text-slate-100 border border-slate-800 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:border-blue-500 font-mono"
              >
                <option value="">-- PILIH TRUK / DO --</option>
                <option 
                  v-for="order in deliveryOrders" 
                  :key="order.id" 
                  :value="order.id"
                >
                  [{{ order.status.toUpperCase() }}] {{ order.vehicle_number || order.vehicle?.license_plate }} - {{ order.do_number }} ({{ order.driver_name }})
                </option>
              </select>
            </div>

            <!-- 2. Select Checkpoint Reader -->
            <div>
              <label class="block text-xs font-mono text-slate-400 uppercase mb-1.5">2. Pilih Lokasi Scanner RFID (Reader ID)</label>
              <div class="grid grid-cols-1 gap-2">
                <label 
                  v-for="reader in [
                    { id: 'gate_entry', label: 'READER-GATE-ENTRY (Masuk Satpam)', desc: 'Validasi DO & Truk Masuk' },
                    { id: 'weighbridge_in', label: 'READER-SCALE-IN (Jembatan Timbang)', desc: 'Penimbangan Truk Kosong (Tare)' },
                    { id: 'bay_loading', label: 'READER-BAY-LOAD (Dermaga Muat)', desc: 'Tempat Pemuatan Barang / Bay' },
                    { id: 'weighbridge_out', label: 'READER-SCALE-OUT (Jembatan Timbang)', desc: 'Penimbangan Muatan (Gross)' },
                    { id: 'gate_exit', label: 'READER-GATE-EXIT (Keluar Satpam)', desc: 'Pemeriksaan Akhir & Keluar' }
                  ]"
                  :key="reader.id"
                  class="flex items-start gap-3 p-2.5 rounded-xl border cursor-pointer transition-all hover:bg-slate-950/40"
                  :class="selectedReader === reader.id 
                    ? 'border-blue-500/60 bg-blue-950/20 text-blue-300' 
                    : 'border-slate-800 text-slate-400 bg-slate-950/10'"
                >
                  <input 
                    type="radio" 
                    v-model="selectedReader" 
                    :value="reader.id" 
                    class="mt-1 text-blue-600 focus:ring-blue-500/50" 
                  />
                  <div>
                    <span class="text-xs font-mono font-bold block">{{ reader.label }}</span>
                    <span class="text-[10px] text-slate-500 block">{{ reader.desc }}</span>
                  </div>
                </label>
              </div>
            </div>

            <!-- 3. Dynamic input: Weight (for Scales) -->
            <div 
              v-if="['weighbridge_in', 'weighbridge_out'].includes(selectedReader)"
              class="bg-slate-950/60 border border-slate-800 p-4 rounded-xl space-y-2 animate-fadeIn"
            >
              <div class="flex items-center justify-between text-xs font-mono">
                <span class="text-slate-400">INPUT TIMBANGAN (SIMULATOR)</span>
                <span class="text-blue-400 font-bold">{{ simulatedWeight.toLocaleString() }} Kg</span>
              </div>
              <input 
                type="range" 
                v-model.number="simulatedWeight"
                min="3000" 
                max="25000" 
                step="50"
                class="w-full h-2 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-blue-500"
              />
              <div class="flex justify-between text-[9px] font-mono text-slate-600">
                <span>3,000 Kg</span>
                <span>12,000 Kg</span>
                <span>25,000 Kg</span>
              </div>
            </div>

            <!-- 4. Dynamic input: Bay (for Bay Loading) -->
            <div 
              v-if="selectedReader === 'bay_loading'"
              class="bg-slate-950/60 border border-slate-800 p-4 rounded-xl space-y-2 animate-fadeIn"
            >
              <label class="block text-xs font-mono text-slate-400 uppercase">Pilih Area Bay / Pemuatan</label>
              <select 
                v-model="loadingBay"
                class="w-full bg-slate-900 text-slate-100 border border-slate-800 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:border-blue-500 font-mono"
              >
                <option value="Bay 1 Slitting">Bay 1 Slitting</option>
                <option value="Bay 2 Slitting">Bay 2 Slitting</option>
                <option value="Bay 3 Pipe">Bay 3 Pipe</option>
                <option value="Bay 4 Packing">Bay 4 Packing</option>
                <option value="Jembatan Timbang">Jembatan Timbang</option>
              </select>
            </div>

          </div>

          <!-- Trigger Button -->
          <div class="mt-6 pt-4 border-t border-slate-800">
            <button
              @click="triggerSimulation"
              :disabled="processing || !selectedDoId"
              class="w-full py-3.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white text-sm font-bold uppercase tracking-wider transition-all disabled:opacity-40 disabled:cursor-not-allowed shadow-lg shadow-blue-500/20 active:scale-95 flex items-center justify-center gap-2"
            >
              <Radio class="w-4 h-4 animate-ping" v-if="processing" />
              <Cpu class="w-4 h-4" v-else />
              <span>{{ processing ? 'EMULATING SCAN...' : '⚡ EMULATE RFID SCAN' }}</span>
            </button>
          </div>
        </div>

        <!-- RIGHT PANEL: PHYSICAL FLOW FLOWCHART & LIVE LOGS (col 7) -->
        <div class="lg:col-span-7 flex flex-col gap-6">

          <!-- 1. Physical Flow Blueprint HUD Card -->
          <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 backdrop-blur-md shadow-xl flex flex-col justify-between min-h-[220px]">
            <div class="border-b border-slate-800 pb-3 mb-4 flex items-center justify-between">
              <h3 class="text-xs font-semibold tracking-wider font-mono text-cyan-400 uppercase flex items-center gap-2">
                <Map class="w-4 h-4 text-cyan-400" />
                FACTORY RFID SCAN CHECKPOINTS BLUEPRINT
              </h3>
            </div>

            <!-- HUD Checkpoint Step Flow Diagram -->
            <div class="grid grid-cols-5 gap-2 text-center py-4 relative">
              
              <!-- Flow connection line background -->
              <div class="absolute top-[40%] left-[10%] right-[10%] h-[2px] bg-slate-800 z-0"></div>

              <!-- Gate Entry -->
              <div class="relative z-10 flex flex-col items-center gap-2">
                <div class="w-10 h-10 rounded-full flex items-center justify-center border font-mono text-xs"
                  :class="selectedReader === 'gate_entry' 
                    ? 'border-cyan-400 bg-cyan-950/60 text-cyan-400 shadow-[0_0_10px_rgba(34,211,238,0.4)] animate-pulse' 
                    : 'border-slate-800 bg-slate-900 text-slate-500'"
                >
                  01
                </div>
                <span class="text-[9px] font-mono tracking-wider font-bold block" :class="selectedReader === 'gate_entry' ? 'text-cyan-400' : 'text-slate-500'">GATE IN</span>
              </div>

              <!-- Scale 1 (Tare) -->
              <div class="relative z-10 flex flex-col items-center gap-2">
                <div class="w-10 h-10 rounded-full flex items-center justify-center border font-mono text-xs"
                  :class="selectedReader === 'weighbridge_in' 
                    ? 'border-cyan-400 bg-cyan-950/60 text-cyan-400 shadow-[0_0_10px_rgba(34,211,238,0.4)] animate-pulse' 
                    : 'border-slate-800 bg-slate-900 text-slate-500'"
                >
                  02
                </div>
                <span class="text-[9px] font-mono tracking-wider font-bold block" :class="selectedReader === 'weighbridge_in' ? 'text-cyan-400' : 'text-slate-500'">TARE</span>
              </div>

              <!-- Bay Loading -->
              <div class="relative z-10 flex flex-col items-center gap-2">
                <div class="w-10 h-10 rounded-full flex items-center justify-center border font-mono text-xs"
                  :class="selectedReader === 'bay_loading' 
                    ? 'border-cyan-400 bg-cyan-950/60 text-cyan-400 shadow-[0_0_10px_rgba(34,211,238,0.4)] animate-pulse' 
                    : 'border-slate-800 bg-slate-900 text-slate-500'"
                >
                  03
                </div>
                <span class="text-[9px] font-mono tracking-wider font-bold block" :class="selectedReader === 'bay_loading' ? 'text-cyan-400' : 'text-slate-500'">LOADING</span>
              </div>

              <!-- Scale 2 (Gross) -->
              <div class="relative z-10 flex flex-col items-center gap-2">
                <div class="w-10 h-10 rounded-full flex items-center justify-center border font-mono text-xs"
                  :class="selectedReader === 'weighbridge_out' 
                    ? 'border-cyan-400 bg-cyan-950/60 text-cyan-400 shadow-[0_0_10px_rgba(34,211,238,0.4)] animate-pulse' 
                    : 'border-slate-800 bg-slate-900 text-slate-500'"
                >
                  04
                </div>
                <span class="text-[9px] font-mono tracking-wider font-bold block" :class="selectedReader === 'weighbridge_out' ? 'text-cyan-400' : 'text-slate-500'">GROSS</span>
              </div>

              <!-- Gate Exit -->
              <div class="relative z-10 flex flex-col items-center gap-2">
                <div class="w-10 h-10 rounded-full flex items-center justify-center border font-mono text-xs"
                  :class="selectedReader === 'gate_exit' 
                    ? 'border-cyan-400 bg-cyan-950/60 text-cyan-400 shadow-[0_0_10px_rgba(34,211,238,0.4)] animate-pulse' 
                    : 'border-slate-800 bg-slate-900 text-slate-500'"
                >
                  05
                </div>
                <span class="text-[9px] font-mono tracking-wider font-bold block" :class="selectedReader === 'gate_exit' ? 'text-cyan-400' : 'text-slate-500'">GATE OUT</span>
              </div>

            </div>

            <!-- Hint instructions -->
            <div class="bg-slate-950/80 border border-slate-800 p-3.5 rounded-xl flex items-start gap-2.5 mt-4">
              <AlertTriangle class="w-4 h-4 text-cyan-400 shrink-0 mt-0.5" />
              <div class="text-[11px] text-slate-400 font-mono leading-relaxed">
                Supir truk menggunakan stiker kartu RFID RFID-TRUCK. Simulator ini memicu trigger event webhook secara virtual seolah-olah sensor fisik mendeteksi plat truk di lapangan. Anda dapat mencobanya berulang kali.
              </div>
            </div>
          </div>

          <!-- 2. Live RFID Scan History Console Log -->
          <div class="flex-1 bg-slate-900/60 border border-slate-800 rounded-2xl p-6 backdrop-blur-md shadow-xl flex flex-col">
            <div class="border-b border-slate-800 pb-3 mb-4 flex items-center justify-between">
              <h3 class="text-xs font-semibold tracking-wider font-mono text-emerald-400 uppercase flex items-center gap-2">
                <Terminal class="w-4 h-4 text-emerald-400" />
                LIVE HARDWARE SCAN LOGS (REAL-TIME AUDIT)
              </h3>
              <span class="text-[10px] font-mono text-slate-500 animate-pulse">LISTENING...</span>
            </div>

            <!-- Logs list -->
            <div class="flex-1 min-h-[220px] max-h-[300px] overflow-y-auto custom-scrollbar space-y-3 pr-2">
              <div v-if="scans.length === 0" class="h-full flex items-center justify-center text-slate-600 text-xs font-mono">
                NO SCAN DETECTED YET
              </div>

              <!-- Log Rows -->
              <div 
                v-for="log in scans" 
                :key="log.id"
                class="border-l-2 p-3 bg-slate-950/50 rounded-r-xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs font-mono"
                :class="log.status === 'success' 
                  ? 'border-emerald-500' 
                  : (log.status === 'warning' ? 'border-amber-500' : 'border-red-500')"
              >
                <div class="space-y-1">
                  <!-- Header -->
                  <div class="flex items-center gap-2">
                    <span class="text-[10px] text-slate-500">{{ new Date(log.created_at).toLocaleTimeString() }}</span>
                    <span class="font-bold px-2 py-0.5 rounded text-[9px] uppercase border"
                      :class="getStatusColorClass(log.status)"
                    >
                      {{ log.reader_id }}
                    </span>
                    <span class="text-slate-400">Tag: <span class="text-blue-400">{{ log.tag_id }}</span></span>
                  </div>
                  <!-- Message -->
                  <p class="text-slate-300 text-xs font-sans leading-relaxed">{{ log.message }}</p>
                </div>

                <div v-if="log.simulated_weight" class="text-right sm:self-center">
                  <span class="text-[8px] text-slate-500 block">WEIGHT VALUE</span>
                  <span class="text-xs text-blue-300 block font-bold">{{ log.simulated_weight.toLocaleString() }} Kg</span>
                </div>
              </div>
            </div>

          </div>

        </div>

      </div>

    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-900 bg-slate-950 py-6 text-center text-xs text-slate-500 font-mono">
      <span>&copy; {{ new Date().getFullYear() }} USICS RFID WEBHOOK INTEGRATOR MODULE. DEVICE ACTIVE.</span>
    </footer>
  </div>
</template>

<style>
/* Custom animations for simulation UI */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(5px); }
  to { opacity: 1; transform: translateY(0); }
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
  background: rgba(59, 130, 246, 0.2);
  border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(59, 130, 246, 0.4);
}
</style>
