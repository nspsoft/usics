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
    SunIcon,
    MoonIcon
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

// --- Theme Reactive Sync ---
const isDark = ref(true);
const toggleTheme = () => {
    isDark.value = !isDark.value;
    if (isDark.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
};

let observer;
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
    
    isDark.value = document.documentElement.classList.contains('dark');
    observer = new MutationObserver(() => {
        isDark.value = document.documentElement.classList.contains('dark');
    });
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
    
    // Initial fetch
    fetchCalculations();
});

onUnmounted(() => {
    clearInterval(timer);
    if (observer) observer.disconnect();
});

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

        <div class="min-h-screen bg-slate-50 dark:bg-[#050510] text-slate-800 dark:text-white font-mono relative overflow-hidden transition-colors duration-300">
            <!-- Background Grid -->
            <div class="fixed inset-0 pointer-events-none z-0">
                <div class="absolute inset-0 bg-gradient-to-b from-purple-500/5 to-slate-100 dark:from-cyan-950/20 dark:to-[#050510]"></div>
                <div class="absolute inset-0 perspective-grid opacity-[0.15] dark:opacity-30"></div>
                <div class="absolute top-0 right-0 w-[600px] h-[600px] bg-purple-500/5 dark:bg-purple-500/10 rounded-full blur-[200px] animate-float"></div>
                <div class="absolute bottom-0 left-0 w-[500px] h-[500px] bg-amber-500/5 dark:bg-amber-500/10 rounded-full blur-[200px] animate-float-delayed"></div>
            </div>

            <div class="relative z-10 p-4 lg:p-6 max-w-[1600px] mx-auto space-y-6">
                <!-- Header -->
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-white/10 pb-4">
                    <div class="flex items-center gap-4">
                        <h1 class="text-2xl font-black tracking-wider text-purple-600 dark:text-purple-400 uppercase flex items-center gap-3">
                            <CalendarDaysIcon class="h-7 w-7" />
                            MAPU - Import Purchase Planner
                        </h1>
                        <span class="text-xs text-slate-550 dark:text-slate-500 tracking-[0.3em] uppercase mt-1 hidden sm:inline-block">/ Siklus Perencanaan Pembelian Coil Impor (3-Bulan Jeda)</span>
                    </div>
                    
                    <div class="flex items-center gap-6">
                        <!-- Theme Toggle Button -->
                        <button 
                            @click="toggleTheme"
                            class="p-2.5 rounded-xl border border-slate-200 dark:border-white/10 bg-white/70 dark:bg-white/5 hover:bg-slate-50 dark:hover:bg-white/10 text-slate-700 dark:text-cyan-400 transition-all hover:scale-105 shadow-sm dark:shadow-none"
                            :title="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
                        >
                            <SunIcon v-if="isDark" class="h-5 w-5 text-amber-500" />
                            <MoonIcon v-else class="h-5 w-5 text-indigo-600" />
                        </button>

                        <div class="text-right">
                            <p class="text-3xl font-black text-slate-900 dark:text-white dark:glow-text leading-none">{{ time }}</p>
                            <p class="text-[10px] text-slate-500 uppercase tracking-widest mt-1">Lead Time: 3 Bulan (90 Hari)</p>
                        </div>
                    </div>
                </div>

                <!-- Control Panel (Month Pickers) -->
                <div class="hud-panel bg-white/70 dark:bg-white/5 backdrop-blur-xl border border-slate-200 dark:border-white/10 rounded-xl p-4 flex flex-wrap gap-4 items-end shadow-sm dark:shadow-none">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-[10px] text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Bulan Perencanaan (Rilis PO)</label>
                        <input 
                            type="month" 
                            v-model="planningMonth"
                            class="w-full bg-white dark:bg-[#0a0a16] border border-slate-200 dark:border-white/10 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-white focus:outline-none focus:border-purple-500 focus:ring-0"
                        />
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-[10px] text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Bulan Target Kedatangan</label>
                        <input 
                            type="month" 
                            v-model="arrivalMonth"
                            class="w-full bg-white dark:bg-[#0a0a16] border border-slate-200 dark:border-white/10 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-white focus:outline-none focus:border-purple-500 focus:ring-0"
                        />
                    </div>
                    <div>
                        <button 
                            @click="fetchCalculations"
                            :disabled="isLoading"
                            class="px-5 py-2.5 bg-purple-600 hover:bg-purple-700 disabled:bg-purple-800 text-white rounded-lg text-xs font-bold uppercase tracking-wider flex items-center gap-2 transition-all shadow-sm"
                        >
                            <ArrowPathIcon class="h-4 w-4" :class="isLoading ? 'animate-spin' : ''" />
                            Hitung MAPU
                        </button>
                    </div>
                </div>

                <!-- Status Alerts -->
                <div v-if="errorMsg" class="bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 p-4 rounded-xl text-xs flex items-center gap-3">
                    <ExclamationTriangleIcon class="h-5 w-5 text-rose-550 flex-shrink-0" />
                    <span>{{ errorMsg }}</span>
                </div>
                <div v-if="successMsg" class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-750 dark:text-emerald-400 p-4 rounded-xl text-xs flex items-center gap-3">
                    <CheckCircleIcon class="h-5 w-5 text-emerald-555 flex-shrink-0" />
                    <span>{{ successMsg }}</span>
                </div>

                <!-- KPI / Stats Summary -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4" v-if="results.length > 0">
                    <div class="hud-card bg-white/70 dark:bg-white/5 backdrop-blur-xl border border-slate-200 dark:border-white/10 rounded-xl p-4 shadow-sm dark:shadow-none">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-[10px] text-slate-550 dark:text-slate-500 tracking-[0.15em] uppercase">Total Material Coil</span>
                        </div>
                        <p class="text-2xl font-black text-slate-800 dark:text-white">{{ results.length }} <span class="text-xs font-normal text-slate-400 dark:text-slate-500">Item</span></p>
                    </div>
                    <div class="hud-card bg-white/70 dark:bg-white/5 backdrop-blur-xl border border-slate-200 dark:border-white/10 rounded-xl p-4 shadow-sm dark:shadow-none">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-[10px] text-slate-550 dark:text-slate-500 tracking-[0.15em] uppercase">Masa Tunggu Pengapalan</span>
                        </div>
                        <p class="text-lg font-mono font-bold text-cyan-600 dark:text-cyan-400 tracking-wider">{{ waitPeriod }}</p>
                    </div>
                    <div class="hud-card bg-white/70 dark:bg-white/5 backdrop-blur-xl border border-slate-200 dark:border-white/10 rounded-xl p-4 shadow-sm dark:shadow-none">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-[10px] text-slate-550 dark:text-slate-500 tracking-[0.15em] uppercase">Rekomendasi Rilis PO</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <p class="text-2xl font-black text-amber-600 dark:text-amber-400">
                                {{ results.filter(r => r.net_requirement > 0).length }} <span class="text-xs font-normal text-slate-400 dark:text-slate-500">Coil</span>
                            </p>
                            <button
                                v-if="selectedCount > 0"
                                @click="openPrModal"
                                class="px-3 py-1 bg-amber-500/10 dark:bg-amber-500/20 text-amber-700 dark:text-amber-400 border border-amber-250 dark:border-amber-500/30 rounded-lg text-[10px] font-bold uppercase tracking-wider hover:bg-amber-500 hover:text-white transition-all flex items-center gap-1 shadow-sm"
                            >
                                <DocumentPlusIcon class="h-3.5 w-3.5" />
                                Buat PR ({{ selectedCount }})
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Results Table -->
                <div class="hud-panel bg-white/75 dark:bg-[#0a0a16]/80 border border-slate-200 dark:border-white/10 rounded-xl overflow-hidden shadow-sm dark:shadow-[0_0_20px_rgba(0,0,0,0.5)]" v-if="results.length > 0">
                    <div class="panel-header p-4 border-b border-slate-200 dark:border-white/5 bg-purple-500/5 flex justify-between items-center">
                        <h3 class="text-xs font-bold text-purple-600 dark:text-purple-400 tracking-widest uppercase">Formulasi Simulasi Kebutuhan Coil</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse min-w-[1200px]">
                            <thead>
                                <tr class="text-[10px] text-slate-500 font-bold uppercase tracking-wider border-b border-slate-200 dark:border-white/10 bg-slate-50 dark:bg-[#0a0a16]">
                                    <th class="p-3 w-10 text-center">
                                        <input 
                                            type="checkbox" 
                                            :checked="isAllSelected" 
                                            @change="toggleSelectAll" 
                                            class="rounded border-slate-350 dark:border-white/10 bg-transparent text-purple-600 dark:text-purple-500 focus:ring-purple-550 focus:ring-offset-slate-50 dark:focus:ring-offset-[#050510]"
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
                            <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                                <tr 
                                    v-for="item in results" 
                                    :key="item.product_id"
                                    class="hover:bg-slate-50 dark:hover:bg-white/5 transition-colors group"
                                    :class="item.net_requirement > 0 ? 'bg-amber-500/[0.02] dark:bg-amber-500/[0.02]' : ''"
                                >
                                    <td class="p-3 text-center">
                                        <input 
                                            type="checkbox" 
                                            :checked="isItemSelected(item.product_id)"
                                            @change="toggleSelectItem(item)"
                                            class="rounded border-slate-350 dark:border-white/10 bg-transparent text-purple-600 dark:text-purple-500 focus:ring-purple-550 focus:ring-offset-slate-50 dark:focus:ring-offset-[#050510]"
                                        />
                                    </td>
                                    <td class="p-3 border-l-2 border-transparent group-hover:border-purple-500 transition-colors">
                                        <div class="text-xs font-bold text-slate-800 dark:text-white">{{ item.name }}</div>
                                        <div class="text-[10px] text-slate-500 font-mono">{{ item.sku }}</div>
                                    </td>
                                    <td class="p-3 text-xs font-mono text-right text-purple-600 dark:text-purple-300">{{ formatNumber(item.gross_demand) }} <span class="text-[9px] text-slate-500 dark:text-slate-600">{{ item.unit }}</span></td>
                                    <td class="p-3 text-xs font-mono text-right text-slate-500 dark:text-slate-400">{{ formatNumber(item.current_stock) }} <span class="text-[9px] text-slate-500 dark:text-slate-600">{{ item.unit }}</span></td>
                                    <td class="p-3 text-xs font-mono text-right text-cyan-600 dark:text-cyan-400/80">+{{ formatNumber(item.outstanding_po) }}</td>
                                    <td class="p-3 text-xs font-mono text-right text-rose-600 dark:text-rose-400/80">-{{ formatNumber(item.projected_consumption) }}</td>
                                    <td class="p-3 text-xs font-mono text-right" :class="item.projected_ending_stock < 0 ? 'text-rose-600 dark:text-rose-500 font-bold' : 'text-slate-500 dark:text-slate-400'">
                                        {{ formatNumber(item.projected_ending_stock) }}
                                    </td>
                                    <td class="p-3 text-xs font-mono text-right text-slate-500">{{ formatNumber(item.safety_stock) }}</td>
                                    <td class="p-3 text-xs font-mono font-bold text-right" :class="item.net_requirement > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-400 dark:text-slate-500'">
                                        {{ formatNumber(item.net_requirement) }} <span class="text-[9px] text-slate-500 dark:text-slate-600">{{ item.unit }}</span>
                                    </td>
                                    <td class="p-3 text-center">
                                        <span 
                                            class="px-2 py-0.5 rounded-full text-[9px] font-bold border"
                                            :class="item.net_requirement > 0 ? 'bg-amber-50 dark:bg-amber-500/20 text-amber-705 dark:text-amber-400 border-amber-200 dark:border-amber-500/30' : 'bg-slate-100 dark:bg-slate-500/20 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-500/20'"
                                        >
                                            {{ item.recommendation }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-else-if="!isLoading" class="hud-panel bg-white/75 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl p-12 text-center shadow-sm dark:shadow-none">
                    <CalendarDaysIcon class="h-12 w-12 text-slate-400 dark:text-slate-600 mx-auto mb-3" />
                    <p class="text-sm text-slate-500 dark:text-slate-400 font-bold uppercase tracking-widest">Silakan Hitung MAPU</p>
                    <p class="text-xs text-slate-500 mt-1">Pilih bulan target kedatangan di panel kontrol di atas.</p>
                </div>
            </div>

            <!-- Create Purchase Request Modal -->
            <div v-if="showPrModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" @click="showPrModal = false"></div>
                <div class="hud-panel bg-white dark:bg-[#0a0a1a] border border-slate-200 dark:border-white/10 rounded-2xl w-full max-w-2xl overflow-hidden relative z-10 shadow-2xl">
                    <div class="p-4 border-b border-slate-100 dark:border-white/5 bg-purple-500/5 flex justify-between items-center">
                        <h3 class="text-sm font-bold text-purple-600 dark:text-purple-400 uppercase tracking-widest flex items-center gap-2">
                            <DocumentPlusIcon class="h-5 w-5" />
                            Buat Draft Purchase Request
                        </h3>
                        <button @click="showPrModal = false" class="p-1 rounded-lg text-slate-500 hover:text-slate-700 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-white/5 transition-all">
                            <XMarkIcon class="h-5 w-5" />
                        </button>
                    </div>
                    <form @submit.prevent="submitPurchaseRequest" class="p-6 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Tanggal Permintaan</label>
                                <input 
                                    type="date" 
                                    v-model="prForm.request_date"
                                    required
                                    class="w-full bg-white dark:bg-[#050510] border border-slate-200 dark:border-white/10 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-white focus:outline-none focus:border-purple-500 focus:ring-0"
                                />
                            </div>
                            <div>
                                <label class="block text-[10px] text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Pemohon (Requester)</label>
                                <select 
                                    v-model="prForm.requester"
                                    required
                                    class="w-full bg-white dark:bg-[#050510] border border-slate-200 dark:border-white/10 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-white focus:outline-none focus:border-purple-500 focus:ring-0 bg-white dark:bg-[#050510]"
                                >
                                    <option value="" disabled class="text-slate-800 dark:text-white">Pilih Pemohon</option>
                                    <option v-for="u in users" :key="u.id" :value="u.name" class="text-slate-850 dark:text-white">{{ u.name }}</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Departemen</label>
                            <select 
                                v-model="prForm.department"
                                required
                                class="w-full bg-white dark:bg-[#050510] border border-slate-200 dark:border-white/10 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-white focus:outline-none focus:border-purple-500 focus:ring-0 bg-white dark:bg-[#050510]"
                            >
                                <option value="" disabled class="text-slate-805 dark:text-white">Pilih Departemen</option>
                                <option v-for="d in departments" :key="d.id" :value="d.name" class="text-slate-855 dark:text-white">{{ d.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10px] text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Catatan (Notes)</label>
                            <textarea 
                                v-model="prForm.notes"
                                rows="3"
                                class="w-full bg-white dark:bg-[#050510] border border-slate-200 dark:border-white/10 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-white focus:outline-none focus:border-purple-500 focus:ring-0 resize-none"
                            ></textarea>
                        </div>

                        <div class="border-t border-slate-200 dark:border-white/5 pt-4">
                            <h4 class="text-[10px] text-slate-500 uppercase tracking-widest mb-3">Item yang di-request ({{ selectedCount }}):</h4>
                            <div class="max-h-[200px] overflow-y-auto space-y-2 pr-2">
                                <div 
                                    v-for="item in Object.values(selectedItems)" 
                                    :key="item.product_id"
                                    class="flex justify-between items-center p-2 bg-slate-50 dark:bg-[#050510]/50 rounded-lg border border-slate-200 dark:border-white/5 shadow-sm dark:shadow-none"
                                >
                                    <div>
                                        <div class="text-xs font-bold text-slate-800 dark:text-white">{{ item.name }}</div>
                                        <div class="text-[9px] text-slate-500 dark:text-slate-600 font-mono">{{ item.sku }}</div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <input 
                                            type="number" 
                                            v-model.number="item.qty"
                                            step="0.0001"
                                            required
                                            class="w-24 bg-white dark:bg-[#0a0a1a] border border-slate-300 dark:border-white/10 rounded px-2 py-1 text-xs text-right text-amber-600 dark:text-amber-400 font-bold focus:outline-none focus:border-purple-500"
                                        />
                                        <span class="text-[10px] text-slate-500 font-mono">{{ item.unit }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 dark:border-white/5">
                            <button 
                                type="button" 
                                @click="showPrModal = false"
                                class="px-4 py-2 border border-slate-350 dark:border-white/10 rounded-lg text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider hover:bg-slate-50 dark:hover:bg-white/5 transition-all"
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

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&display=swap');

.font-mono { font-family: 'Space Mono', monospace; }

.perspective-grid {
    background-image:
        linear-gradient(to right, rgba(168, 85, 247, 0.1) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(168, 85, 247, 0.1) 1px, transparent 1px);
    background-size: 40px 40px;
    transform: perspective(500px) rotateX(60deg) translateY(-100px) scale(2);
    animation: grid-move 20s linear infinite;
    transform-origin: top;
}

@keyframes grid-move { 0% { background-position: 0 0; } 100% { background-position: 0 40px; } }
@keyframes float { 0%, 100% { transform: translate(0, 0); } 50% { transform: translate(-20px, 20px); } }
@keyframes float-delayed { 0%, 100% { transform: translate(0, 0); } 50% { transform: translate(20px, -20px); } }

.animate-float { animation: float 15s ease-in-out infinite; }
.animate-float-delayed { animation: float-delayed 18s ease-in-out infinite; }

.hud-card { transition: all 0.3s ease; }
.hud-card:hover { transform: translateY(-5px); filter: drop-shadow(0 0 10px rgba(168, 85, 247, 0.2)); }

.hud-panel {
    backdrop-filter: blur(20px);
    border-radius: 12px;
}

.glow-text { text-shadow: 0 0 10px currentColor; }
</style>
