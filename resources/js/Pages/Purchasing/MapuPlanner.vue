<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';
import {
    CalendarDaysIcon,
    ExclamationTriangleIcon,
    ShoppingCartIcon,
    ClockIcon,
    ArrowPathIcon,
    DocumentPlusIcon,
    CheckCircleIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import { formatNumber } from '@/helpers';

const props = defineProps({
    defaultPlanningMonth: String,
    defaultArrivalMonth: String,
    departments: Array,
    users: Array,
});

// --- State ---
const planningMonth = ref(props.defaultPlanningMonth);
const arrivalMonth = ref(props.defaultArrivalMonth);
const results = ref([]);
const waitPeriod = ref('');
const isLoading = ref(false);
const errorMsg = ref('');
const successMsg = ref('');

// PR Modal & Form state
const showPrModal = ref(false);
const selectedItems = ref({}); // Maps product_id -> quantity to order
const prForm = ref({
    request_date: new Date().toISOString().split('T')[0],
    department: '',
    requester: '',
    notes: 'Created via MAPU Import Planner',
});

// --- Clock ---
const time = ref('');
let timer;
onMounted(() => {
    const tick = () => { 
        time.value = new Date().toLocaleTimeString('en-US', { 
            hour12: false, 
            hour: '2-digit', 
            minute: '2-digit', 
            second: '2-digit' 
        }); 
    };
    tick(); 
    timer = setInterval(tick, 1000);
    
    // Initial fetch
    fetchCalculations();
});

onUnmounted(() => clearInterval(timer));

// --- Fetch Data ---
const fetchCalculations = async () => {
    isLoading.value = true;
    errorMsg.value = '';
    successMsg.value = '';
    try {
        const response = await axios.get(route('purchasing.mapu.calculate'), {
            params: {
                planning_month: planningMonth.value,
                arrival_month: arrivalMonth.value
            }
        });
        results.value = response.data.results;
        waitPeriod.value = response.data.wait_period;
        
        // Initialize selected items for ones that have net requirement > 0
        selectedItems.value = {};
        results.value.forEach(item => {
            if (item.net_requirement > 0) {
                selectedItems.value[item.product_id] = {
                    selected: true,
                    product_id: item.product_id,
                    name: item.name,
                    sku: item.sku,
                    qty: item.net_requirement,
                    unit: item.unit
                };
            }
        });
    } catch (err) {
        console.error(err);
        errorMsg.value = err.response?.data?.message || 'Gagal menghitung MAPU. Silakan periksa format bulan.';
    } finally {
        isLoading.value = false;
    }
};

// --- Checkbox Helpers ---
const toggleSelectAll = (event) => {
    const isChecked = event.target.checked;
    results.value.forEach(item => {
        if (isChecked) {
            selectedItems.value[item.product_id] = {
                selected: true,
                product_id: item.product_id,
                name: item.name,
                sku: item.sku,
                qty: item.net_requirement > 0 ? item.net_requirement : 1,
                unit: item.unit
            };
        } else {
            delete selectedItems.value[item.product_id];
        }
    });
};

const toggleSelectItem = (item) => {
    if (selectedItems.value[item.product_id]) {
        delete selectedItems.value[item.product_id];
    } else {
        selectedItems.value[item.product_id] = {
            selected: true,
            product_id: item.product_id,
            name: item.name,
            sku: item.sku,
            qty: item.net_requirement > 0 ? item.net_requirement : 1,
            unit: item.unit
        };
    }
};

const isItemSelected = (productId) => {
    return !!selectedItems.value[productId];
};

const selectedCount = computed(() => {
    return Object.keys(selectedItems.value).length;
});

const isAllSelected = computed(() => {
    return results.value.length > 0 && results.value.every(item => selectedItems.value[item.product_id]);
});

// --- Submit Purchase Request ---
const openPrModal = () => {
    if (selectedCount.value === 0) {
        alert('Silakan pilih minimal satu item untuk membuat Purchase Request.');
        return;
    }
    showPrModal.value = true;
};

const submitPurchaseRequest = async () => {
    if (!prForm.value.department || !prForm.value.requester) {
        alert('Departemen dan Pemohon wajib diisi.');
        return;
    }

    isLoading.value = true;
    errorMsg.value = '';
    
    // Map selected items to the backend array structure
    const items = Object.values(selectedItems.value).map(item => ({
        product_id: item.product_id,
        qty: item.qty
    }));

    try {
        const response = await axios.post(route('purchasing.mapu.create-pr'), {
            request_date: prForm.value.request_date,
            department: prForm.value.department,
            requester: prForm.value.requester,
            notes: prForm.value.notes,
            items: items
        });

        if (response.data.success) {
            showPrModal.value = false;
            successMsg.value = response.data.message;
            selectedItems.value = {};
            // Redirect to PR index
            setTimeout(() => {
                router.visit(response.data.redirect_url);
            }, 1500);
        } else {
            errorMsg.value = response.data.message;
        }
    } catch (err) {
        console.error(err);
        errorMsg.value = err.response?.data?.message || 'Gagal membuat Purchase Request.';
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <AppLayout :render-header="false">
        <Head title="MAPU Import Planner" />

        <div class="min-h-screen bg-[#050510] text-white font-mono relative overflow-hidden">
            <!-- Background Grid -->
            <div class="fixed inset-0 pointer-events-none z-0">
                <div class="absolute inset-0 perspective-grid opacity-30"></div>
                <div class="absolute top-0 right-0 w-[600px] h-[600px] bg-purple-500/10 rounded-full blur-[200px] animate-float"></div>
                <div class="absolute bottom-0 left-0 w-[500px] h-[500px] bg-amber-500/10 rounded-full blur-[200px] animate-float-delayed"></div>
            </div>

            <div class="relative z-10 p-4 lg:p-6 max-w-[1600px] mx-auto space-y-6">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-black tracking-wider text-purple-400 uppercase flex items-center gap-3">
                            <CalendarDaysIcon class="h-7 w-7" />
                            MAPU - Import Purchase Planner
                        </h1>
                        <p class="text-xs text-slate-500 tracking-[0.3em] uppercase mt-1">Siklus Perencanaan Pembelian Coil Impor (3-Bulan Jeda)</p>
                    </div>
                    <div class="text-right">
                        <p class="text-3xl font-black text-white/10 tracking-widest">{{ time }}</p>
                        <p class="text-[10px] text-slate-600 uppercase tracking-widest mt-1">Lead Time: 3 Bulan (90 Hari)</p>
                    </div>
                </div>

                <!-- Control Panel (Month Pickers) -->
                <div class="hud-panel bg-white/5 backdrop-blur-xl border border-white/10 rounded-xl p-4 flex flex-wrap gap-4 items-end">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-[10px] text-slate-400 uppercase tracking-wider mb-2">Bulan Perencanaan (Rilis PO)</label>
                        <input 
                            type="month" 
                            v-model="planningMonth"
                            class="w-full bg-[#0a0a16] border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-purple-500"
                        />
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-[10px] text-slate-400 uppercase tracking-wider mb-2">Bulan Target Kedatangan</label>
                        <input 
                            type="month" 
                            v-model="arrivalMonth"
                            class="w-full bg-[#0a0a16] border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-purple-500"
                        />
                    </div>
                    <div>
                        <button 
                            @click="fetchCalculations"
                            :disabled="isLoading"
                            class="px-5 py-2.5 bg-purple-600 hover:bg-purple-700 disabled:bg-purple-800 text-white rounded-lg text-xs font-bold uppercase tracking-wider flex items-center gap-2 transition-all"
                        >
                            <ArrowPathIcon class="h-4 w-4" :class="isLoading ? 'animate-spin' : ''" />
                            Hitung MAPU
                        </button>
                    </div>
                </div>

                <!-- Status Alerts -->
                <div v-if="errorMsg" class="bg-rose-500/10 border border-rose-500/20 text-rose-400 p-4 rounded-xl text-xs flex items-center gap-3">
                    <ExclamationTriangleIcon class="h-5 w-5 text-rose-500 flex-shrink-0" />
                    <span>{{ errorMsg }}</span>
                </div>
                <div v-if="successMsg" class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 p-4 rounded-xl text-xs flex items-center gap-3">
                    <CheckCircleIcon class="h-5 w-5 text-emerald-500 flex-shrink-0" />
                    <span>{{ successMsg }}</span>
                </div>

                <!-- KPI / Stats Summary -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4" v-if="results.length > 0">
                    <div class="hud-card bg-white/5 backdrop-blur-xl border border-white/10 rounded-xl p-4">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-[10px] text-slate-500 tracking-[0.15em] uppercase">Total Material Coil</span>
                        </div>
                        <p class="text-2xl font-black text-white">{{ results.length }} <span class="text-xs font-normal text-slate-500">Item</span></p>
                    </div>
                    <div class="hud-card bg-white/5 backdrop-blur-xl border border-white/10 rounded-xl p-4">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-[10px] text-slate-500 tracking-[0.15em] uppercase">Masa Tunggu Pengapalan</span>
                        </div>
                        <p class="text-lg font-mono font-bold text-cyan-400 tracking-wider">{{ waitPeriod }}</p>
                    </div>
                    <div class="hud-card bg-white/5 backdrop-blur-xl border border-white/10 rounded-xl p-4">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-[10px] text-slate-500 tracking-[0.15em] uppercase">Rekomendasi Rilis PO</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <p class="text-2xl font-black text-amber-400">
                                {{ results.filter(r => r.net_requirement > 0).length }} <span class="text-xs font-normal text-slate-500">Coil</span>
                            </p>
                            <button
                                v-if="selectedCount > 0"
                                @click="openPrModal"
                                class="px-3 py-1 bg-amber-500/20 text-amber-400 border border-amber-500/30 rounded-lg text-[10px] font-bold uppercase tracking-wider hover:bg-amber-500 hover:text-black transition-all flex items-center gap-1"
                            >
                                <DocumentPlusIcon class="h-3.5 w-3.5" />
                                Buat PR ({{ selectedCount }})
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Results Table -->
                <div class="hud-panel bg-white/5 backdrop-blur-xl border border-white/10 rounded-xl overflow-hidden" v-if="results.length > 0">
                    <div class="panel-header p-4 border-b border-white/5 bg-purple-500/5 flex justify-between items-center">
                        <h3 class="text-xs font-bold text-purple-400 tracking-widest uppercase">Formulasi Simulasi Kebutuhan Coil</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse min-w-[1200px]">
                            <thead>
                                <tr class="text-[10px] text-slate-500 font-bold uppercase tracking-wider border-b border-white/10 bg-[#0a0a16]">
                                    <th class="p-3 w-10 text-center">
                                        <input 
                                            type="checkbox" 
                                            :checked="isAllSelected" 
                                            @change="toggleSelectAll" 
                                            class="rounded border-white/10 bg-transparent text-purple-500 focus:ring-purple-500 focus:ring-offset-[#050510]"
                                        />
                                    </th>
                                    <th class="p-3">Material Coil</th>
                                    <th class="p-3 text-right">Gross Demand (T)</th>
                                    <th class="p-3 text-right">Stok Fisik (P)</th>
                                    <th class="p-3 text-right">Outstanding PO</th>
                                    <th class="p-3 text-right">Konsumsi Tunggu</th>
                                    <th class="p-3 text-right">Proyeksi Akhir (T-1)</th>
                                    <th class="p-3 text-right">Safety Stock</th>
                                    <th class="p-3 text-right">Net Req. (Beli)</th>
                                    <th class="p-3 text-center">Rekomendasi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <tr 
                                    v-for="item in results" 
                                    :key="item.product_id"
                                    class="hover:bg-white/5 transition-colors group"
                                    :class="item.net_requirement > 0 ? 'bg-amber-500/[0.02]' : ''"
                                >
                                    <td class="p-3 text-center">
                                        <input 
                                            type="checkbox" 
                                            :checked="isItemSelected(item.product_id)"
                                            @change="toggleSelectItem(item)"
                                            class="rounded border-white/10 bg-transparent text-purple-500 focus:ring-purple-500 focus:ring-offset-[#050510]"
                                        />
                                    </td>
                                    <td class="p-3 border-l-2 border-transparent group-hover:border-purple-500 transition-colors">
                                        <div class="text-xs font-bold text-white">{{ item.name }}</div>
                                        <div class="text-[10px] text-slate-500 font-mono">{{ item.sku }}</div>
                                    </td>
                                    <td class="p-3 text-xs font-mono text-right text-purple-300">{{ formatNumber(item.gross_demand) }} <span class="text-[9px] text-slate-600">{{ item.unit }}</span></td>
                                    <td class="p-3 text-xs font-mono text-right text-slate-400">{{ formatNumber(item.current_stock) }} <span class="text-[9px] text-slate-600">{{ item.unit }}</span></td>
                                    <td class="p-3 text-xs font-mono text-right text-cyan-400/80">+{{ formatNumber(item.outstanding_po) }}</td>
                                    <td class="p-3 text-xs font-mono text-right text-rose-400/80">-{{ formatNumber(item.projected_consumption) }}</td>
                                    <td class="p-3 text-xs font-mono text-right" :class="item.projected_ending_stock < 0 ? 'text-rose-500 font-bold' : 'text-slate-400'">
                                        {{ formatNumber(item.projected_ending_stock) }}
                                    </td>
                                    <td class="p-3 text-xs font-mono text-right text-slate-500">{{ formatNumber(item.safety_stock) }}</td>
                                    <td class="p-3 text-xs font-mono font-bold text-right" :class="item.net_requirement > 0 ? 'text-amber-400' : 'text-slate-500'">
                                        {{ formatNumber(item.net_requirement) }} <span class="text-[9px] text-slate-600">{{ item.unit }}</span>
                                    </td>
                                    <td class="p-3 text-center">
                                        <span 
                                            class="px-2 py-0.5 rounded-full text-[9px] font-bold border"
                                            :class="item.net_requirement > 0 ? 'bg-amber-500/20 text-amber-400 border-amber-500/30' : 'bg-slate-500/20 text-slate-400 border-slate-500/20'"
                                        >
                                            {{ item.recommendation }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-else-if="!isLoading" class="hud-panel bg-white/5 border border-white/10 rounded-xl p-12 text-center">
                    <CalendarDaysIcon class="h-12 w-12 text-slate-600 mx-auto mb-3" />
                    <p class="text-sm text-slate-400 font-bold uppercase tracking-widest">Silakan Hitung MAPU</p>
                    <p class="text-xs text-slate-600 mt-1">Pilih bulan target kedatangan di panel kontrol di atas.</p>
                </div>
            </div>

            <!-- Create Purchase Request Modal -->
            <div v-if="showPrModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" @click="showPrModal = false"></div>
                <div class="hud-panel bg-[#0a0a1a] border border-white/10 rounded-2xl w-full max-w-2xl overflow-hidden relative z-10">
                    <div class="p-4 border-b border-white/5 bg-purple-500/5 flex justify-between items-center">
                        <h3 class="text-sm font-bold text-purple-400 uppercase tracking-widest flex items-center gap-2">
                            <DocumentPlusIcon class="h-5 w-5" />
                            Buat Draft Purchase Request
                        </h3>
                        <button @click="showPrModal = false" class="p-1 rounded-lg text-slate-500 hover:text-white hover:bg-white/5 transition-all">
                            <XMarkIcon class="h-5 w-5" />
                        </button>
                    </div>
                    <form @submit.prevent="submitPurchaseRequest" class="p-6 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] text-slate-400 uppercase tracking-wider mb-2">Tanggal Permintaan</label>
                                <input 
                                    type="date" 
                                    v-model="prForm.request_date"
                                    required
                                    class="w-full bg-[#050510] border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-purple-500"
                                />
                            </div>
                            <div>
                                <label class="block text-[10px] text-slate-400 uppercase tracking-wider mb-2">Pemohon (Requester)</label>
                                <select 
                                    v-model="prForm.requester"
                                    required
                                    class="w-full bg-[#050510] border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-purple-500"
                                >
                                    <option value="" disabled>Pilih Pemohon</option>
                                    <option v-for="u in users" :key="u.id" :value="u.name">{{ u.name }}</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] text-slate-400 uppercase tracking-wider mb-2">Departemen</label>
                            <select 
                                v-model="prForm.department"
                                required
                                class="w-full bg-[#050510] border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-purple-500"
                            >
                                <option value="" disabled>Pilih Departemen</option>
                                <option v-for="d in departments" :key="d.id" :value="d.name">{{ d.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10px] text-slate-400 uppercase tracking-wider mb-2">Catatan (Notes)</label>
                            <textarea 
                                v-model="prForm.notes"
                                rows="3"
                                class="w-full bg-[#050510] border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-purple-500"
                            ></textarea>
                        </div>

                        <div class="border-t border-white/5 pt-4">
                            <h4 class="text-[10px] text-slate-500 uppercase tracking-widest mb-3">Item yang di-request ({{ selectedCount }}):</h4>
                            <div class="max-h-[200px] overflow-y-auto space-y-2 pr-2">
                                <div 
                                    v-for="item in Object.values(selectedItems)" 
                                    :key="item.product_id"
                                    class="flex justify-between items-center p-2 bg-[#050510]/50 rounded-lg border border-white/5"
                                >
                                    <div>
                                        <div class="text-xs font-bold text-white">{{ item.name }}</div>
                                        <div class="text-[9px] text-slate-600 font-mono">{{ item.sku }}</div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <input 
                                            type="number" 
                                            v-model.number="item.qty"
                                            step="0.0001"
                                            required
                                            class="w-24 bg-[#0a0a1a] border border-white/10 rounded px-2 py-1 text-xs text-right text-amber-400 font-bold focus:outline-none focus:border-purple-500"
                                        />
                                        <span class="text-[10px] text-slate-500 font-mono">{{ item.unit }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t border-white/5">
                            <button 
                                type="button" 
                                @click="showPrModal = false"
                                class="px-4 py-2 border border-white/10 rounded-lg text-xs font-bold text-slate-400 uppercase tracking-wider hover:bg-white/5 transition-all"
                            >
                                Batal
                            </button>
                            <button 
                                type="submit" 
                                :disabled="isLoading"
                                class="px-5 py-2 bg-purple-600 hover:bg-purple-700 disabled:bg-purple-800 text-white rounded-lg text-xs font-bold uppercase tracking-wider transition-all"
                            >
                                Kirim PR
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
