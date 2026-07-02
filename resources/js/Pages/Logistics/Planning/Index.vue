<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatNumber, formatDate } from '@/helpers';
import { 
    TruckIcon, 
    ClipboardDocumentListIcon, 
    MagnifyingGlassIcon, 
    UserIcon, 
    MapPinIcon, 
    ChevronRightIcon, 
    CheckCircleIcon, 
    XCircleIcon,
    ChevronDownIcon,
    ChevronUpIcon,
    Square3Stack3DIcon,
    SparklesIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    deliveryOrders: Array,
    vehicles: Array,
    filters: Object
});

const selectedOrders = ref([]);
const expandedOrders = ref([]);
const search = ref(props.filters.search || '');

const toggleExpand = (id) => {
    if (expandedOrders.value.includes(id)) {
        expandedOrders.value = expandedOrders.value.filter(oid => oid !== id);
    } else {
        expandedOrders.value.push(id);
    }
};

const form = useForm({
    delivery_order_ids: [],
    vehicle_id: '',
    driver_name: '',
    travel_allowance: 0,
    travel_allowance_notes: ''
});

const toggleSelectAll = (e) => {
    if (e.target.checked) {
        selectedOrders.value = props.deliveryOrders.map(o => o.id);
    } else {
        selectedOrders.value = [];
    }
};

const assignVehicle = () => {
    if (selectedOrders.value.length === 0) {
        alert('Pilih minimal satu Delivery Order!');
        return;
    }
    if (!form.vehicle_id) {
        alert('Pilih kendaraan!');
        return;
    }

    form.delivery_order_ids = selectedOrders.value;
    
    // Auto-fill driver from vehicle if empty
    const selectedVehicle = props.vehicles.find(v => v.id === form.vehicle_id);
    if (!form.driver_name && selectedVehicle) {
        form.driver_name = selectedVehicle.driver_name;
    }

    form.post(route('logistics.planning.assign'), {
        onSuccess: () => {
            selectedOrders.value = [];
            form.reset();
        }
    });
};

const getStatusColor = (status) => {
    switch (status) {
        case 'draft': return 'bg-slate-100 text-slate-700 dark:bg-slate-500/10 dark:text-slate-400';
        case 'picking': return 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400';
        case 'packed': return 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400';
        default: return 'bg-slate-100 text-slate-700 dark:bg-slate-500/10 dark:text-slate-400';
    }
};
</script>

<template>
    <Head title="Delivery Planning" />

    <AppLayout title="Delivery Planning">
        <div class="flex items-center justify-between mb-8 gap-4 flex-wrap">
            <div>
                <h2 class="text-xl font-bold text-slate-900 dark:text-white leading-none">Delivery Planning</h2>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 font-medium uppercase tracking-widest leading-none">Perencanaan Pengiriman Barang</p>
            </div>
            <Link
                href="/logistics/planning/optimize"
                class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-indigo-500/20 transition-all hover:bg-indigo-500 active:scale-95"
            >
                <SparklesIcon class="h-4 w-4 text-indigo-200 animate-pulse" />
                <span>AI VRP Optimizer</span>
            </Link>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left: Delivery Orders List -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Toolbar -->
                <div class="flex flex-wrap items-center justify-between gap-4 bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <div class="relative group min-w-[300px]">
                        <MagnifyingGlassIcon class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400 group-focus-within:text-blue-500 transition-colors" />
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Cari No DO atau Customer..."
                            class="w-full bg-slate-50 dark:bg-slate-800 border-0 ring-1 ring-slate-200 dark:ring-slate-700 rounded-xl pl-12 pr-4 py-2.5 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all font-medium"
                        />
                    </div>
                    <div class="flex items-center gap-2">
                         <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">{{ selectedOrders.length }} Terpilih</span>
                    </div>
                </div>

                <!-- List -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm transition-all">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                        <thead class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm bg-slate-50/50 dark:bg-slate-800/50">
                            <tr>
                                <th scope="col" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4 text-left">
                                    <input 
                                        type="checkbox" 
                                        class="h-4 w-4 rounded border-slate-300 dark:border-slate-700 text-blue-600 focus:ring-blue-500 dark:bg-slate-900" 
                                        @change="toggleSelectAll"
                                    />
                                </th>
                                <th scope="col" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4 text-left text-xs font-black text-slate-500 uppercase tracking-wider w-10"></th>
                                <th scope="col" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4 text-left text-xs font-black text-slate-500 uppercase tracking-wider">DO Info</th>
                                <th scope="col" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4 text-left text-xs font-black text-slate-500 uppercase tracking-wider">Customer & Alamat</th>
                                <th scope="col" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4 text-left text-xs font-black text-slate-500 uppercase tracking-wider">Armada (DO)</th>
                                <th scope="col" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-6 py-4 text-left text-xs font-black text-slate-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <template v-for="order in deliveryOrders" :key="order.id">
                                <tr 
                                    class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors cursor-pointer group"
                                    :class="{ 'bg-blue-50/30 dark:bg-blue-500/5': expandedOrders.includes(order.id) }"
                                    @click="selectedOrders.includes(order.id) ? selectedOrders.splice(selectedOrders.indexOf(order.id),1) : selectedOrders.push(order.id)"
                                >
                                    <td class="px-6 py-4">
                                        <input 
                                            v-model="selectedOrders" 
                                            :value="order.id" 
                                            type="checkbox" 
                                            class="h-4 w-4 rounded border-slate-300 dark:border-slate-700 text-blue-600 focus:ring-blue-500 dark:bg-slate-900" 
                                            @click.stop
                                        />
                                    </td>
                                    <td class="px-3 py-4 text-center">
                                        <button 
                                            @click.stop="toggleExpand(order.id)"
                                            class="p-1 rounded-lg hover:bg-white dark:hover:bg-slate-700 shadow-sm transition-all"
                                        >
                                            <ChevronDownIcon v-if="!expandedOrders.includes(order.id)" class="h-4 w-4 text-slate-400" />
                                            <ChevronUpIcon v-else class="h-4 w-4 text-blue-500" />
                                        </button>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-bold text-slate-900 dark:text-white group-hover:text-blue-500 transition-colors">{{ order.do_number }}</span>
                                            <span class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-0.5">{{ formatDate(order.delivery_date) }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col max-w-xs">
                                            <span class="text-sm font-semibold text-slate-900 dark:text-slate-200">{{ order.customer?.name }}</span>
                                            <span class="text-xs text-slate-500 dark:text-slate-400 truncate mt-0.5 flex items-center gap-1">
                                                <MapPinIcon class="h-3 w-3 shrink-0" />
                                                {{ order.shipping_address || 'No Address' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div v-if="order.vehicle || order.vehicle_number" class="flex flex-col">
                                            <span class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-wider">
                                                {{ order.vehicle ? order.vehicle.license_plate : order.vehicle_number }}
                                            </span>
                                            <span class="text-[10px] text-slate-500 truncate" v-if="order.driver_name">
                                                {{ order.driver_name }}
                                            </span>
                                        </div>
                                        <span v-else class="text-xs text-slate-400 italic">Belum ditentukan</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span 
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-[0.1em] border"
                                            :class="getStatusColor(order.status)"
                                        >
                                            {{ order.status }}
                                        </span>
                                    </td>
                                </tr>
                                <!-- Expandable Row for Items -->
                                <tr v-if="expandedOrders.includes(order.id)">
                                    <td colspan="5" class="px-12 py-4 bg-slate-50/50 dark:bg-slate-800/20">
                                        <div class="space-y-3">
                                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-blue-500 mb-2 flex items-center gap-2">
                                                <Square3Stack3DIcon class="h-3.5 w-3.5" />
                                                Detail Item Terpilih
                                            </p>
                                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                                <div v-for="item in order.items" :key="item.id" class="bg-white dark:bg-slate-900 p-3 rounded-xl border border-slate-100 dark:border-slate-800 shadow-sm flex items-center gap-3">
                                                    <div class="bg-slate-50 dark:bg-slate-800 p-2 rounded-lg">
                                                        <ClipboardDocumentListIcon class="h-4 w-4 text-slate-400" />
                                                    </div>
                                                    <div class="flex-1 min-w-0">
                                                        <p class="text-xs font-black text-slate-900 dark:text-white truncate">{{ item.product?.name }}</p>
                                                        <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">{{ item.product?.sku }}</p>
                                                    </div>
                                                    <div class="text-right">
                                                        <p class="text-xs font-black text-blue-600 dark:text-blue-400">{{ formatNumber(item.qty_delivered) }} Unit</p>
                                                        <p class="text-[9px] text-slate-400 font-bold">{{ formatNumber(item.weight || item.product?.weight || 0) }} Kg</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <p v-if="!order.items || order.items.length === 0" class="text-xs text-slate-400 italic">Tidak ada item dalam pengiriman ini.</p>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr v-if="deliveryOrders.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <ClipboardDocumentListIcon class="mx-auto h-12 w-12 text-slate-300" />
                                    <p class="mt-2 text-sm text-slate-500 font-medium">Tidak ada pengiriman yang perlu direncanakan.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Right: Assignment Board -->
            <div class="space-y-6">
                <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-8 shadow-xl relative overflow-hidden group">
                    <!-- Decor -->
                    <div class="absolute -top-12 -right-12 w-24 h-24 bg-blue-500/10 rounded-full blur-3xl group-hover:bg-blue-500/20 transition-all duration-500"></div>
                    
                    <div class="relative z-10">
                        <div class="flex items-center gap-4 mb-8">
                            <div class="h-12 w-12 rounded-2xl bg-blue-500/10 flex items-center justify-center text-blue-500 shadow-lg shadow-blue-500/5">
                                <TruckIcon class="h-6 w-6" />
                            </div>
                            <div>
                                <h3 class="font-black text-slate-900 dark:text-white tracking-tight">Assignment</h3>
                                <p class="text-[10px] text-slate-400 uppercase tracking-widest font-black leading-none mt-1">Atur Pengiriman</p>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <!-- Vehicle Selection -->
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Pilih Kendaraan</label>
                                <select 
                                    v-model="form.vehicle_id"
                                    class="w-full bg-slate-50 dark:bg-slate-800 border-0 ring-1 ring-slate-200 dark:ring-slate-700 rounded-2xl px-5 py-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all shadow-inner font-bold"
                                >
                                    <option value="">-- Pilih Armada --</option>
                                    <option v-for="v in vehicles" :key="v.id" :value="v.id">
                                        {{ v.license_plate }} - {{ v.vehicle_type }}
                                    </option>
                                </select>
                            </div>

                            <!-- Driver Info (Optional override) -->
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Driver (Opsional)</label>
                                <div class="relative">
                                    <UserIcon class="absolute left-5 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" />
                                    <input 
                                        v-model="form.driver_name" 
                                        type="text" 
                                        placeholder="Kosongkan jika sesuai master"
                                        class="w-full bg-slate-50 dark:bg-slate-800 border-0 ring-1 ring-slate-200 dark:ring-slate-700 rounded-2xl pl-14 pr-5 py-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all shadow-inner font-medium"
                                    />
                                </div>
                            </div>

                            <!-- Travel Allowance (Uang Jalan) -->
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Uang Jalan (Opsional)</label>
                                <div class="relative">
                                    <span class="absolute left-5 top-1/2 -translate-y-1/2 text-sm font-bold text-slate-400">Rp</span>
                                    <input 
                                        v-model="form.travel_allowance" 
                                        type="number" 
                                        placeholder="0"
                                        class="w-full bg-slate-50 dark:bg-slate-800 border-0 ring-1 ring-slate-200 dark:ring-slate-700 rounded-2xl pl-14 pr-5 py-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all shadow-inner font-bold"
                                    />
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Catatan Uang Jalan / Perjalanan</label>
                                <textarea 
                                    v-model="form.travel_allowance_notes" 
                                    placeholder="Petunjuk rute, pengisian solar, dll."
                                    rows="2"
                                    class="w-full bg-slate-50 dark:bg-slate-800 border-0 ring-1 ring-slate-200 dark:ring-slate-700 rounded-2xl px-5 py-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all shadow-inner font-medium text-xs"
                                ></textarea>
                            </div>

                            <!-- Summary Selection -->
                            <div class="bg-slate-50 dark:bg-slate-800/50 rounded-2xl p-5 border border-slate-100 dark:border-slate-800">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-xs font-bold text-slate-500">Total Orders</span>
                                    <span class="text-lg font-black text-slate-900 dark:text-white">{{ selectedOrders.length }}</span>
                                </div>
                                <div class="w-full bg-slate-200 dark:bg-slate-700 h-1 rounded-full overflow-hidden mt-2">
                                    <div 
                                        class="bg-blue-500 h-full transition-all duration-500" 
                                        :style="{ width: selectedOrders.length > 0 ? '100%' : '0%' }"
                                    ></div>
                                </div>
                            </div>

                            <!-- Submit -->
                            <button 
                                @click="assignVehicle"
                                :disabled="form.processing || selectedOrders.length === 0"
                                class="w-full group relative flex items-center justify-center gap-3 bg-slate-900 dark:bg-blue-600 px-8 py-5 rounded-2xl text-white font-black uppercase tracking-widest text-sm shadow-xl shadow-blue-500/20 hover:shadow-blue-500/40 active:scale-95 transition-all disabled:opacity-30"
                            >
                                <span class="relative z-10 flex items-center gap-3">
                                    Terapkan Rencana
                                    <ChevronRightIcon class="h-5 w-5 group-hover:translate-x-1 transition-transform" />
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Info Box -->
                <div class="p-6 bg-emerald-50 dark:bg-emerald-500/5 border border-emerald-100 dark:border-emerald-500/10 rounded-2xl flex gap-4">
                    <CheckCircleIcon class="h-6 w-6 text-emerald-500 shrink-0" />
                    <div>
                        <h4 class="text-xs font-black text-emerald-700 dark:text-emerald-400 uppercase tracking-widest mb-1">Pro Tip</h4>
                        <p class="text-xs text-emerald-600 dark:text-emerald-500 leading-relaxed font-medium">Bapak bisa memilih banyak DO sekaligus untuk satu truk. Status DO akan otomatis berubah menjadi "Packed" setelah Bapak mendaftarkan pengirimannya.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delivery Planning Operations Guide -->
        <div class="mt-8 relative hidden md:block">
            <div class="absolute inset-0 flex items-center" aria-hidden="true">
                <div class="w-full border-t border-slate-200/60 dark:border-slate-700/60"></div>
            </div>
            <div class="relative flex justify-center">
                <span class="bg-[#F8FAFC] dark:bg-[#0F172A] px-4 text-[10px] font-black tracking-[0.2em] text-slate-400 dark:text-slate-500 uppercase">
                    Planning Operations Guide
                </span>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4 hidden md:grid mb-8">
            <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                <div class="flex items-center gap-3 mb-3">
                    <div class="p-2.5 rounded-xl bg-blue-500/10 text-blue-400">
                        <TruckIcon class="h-5 w-5" />
                    </div>
                    <h4 class="font-bold text-slate-200 text-sm">Fleet Assignment</h4>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    Map verified outbound Delivery Orders to specific <strong>Vehicles</strong> and <strong>Drivers</strong>. This forms the manifest for the day’s outgoing transportation.
                </p>
            </div>

            <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                <div class="flex items-center gap-3 mb-3">
                    <div class="p-2.5 rounded-xl bg-purple-500/10 text-purple-400">
                        <Square3Stack3DIcon class="h-5 w-5" />
                    </div>
                    <h4 class="font-bold text-slate-200 text-sm">Batch Routing</h4>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    Select <strong>Multiple DOs</strong> directed towards identical geographical zones to maximize payload capacity per truck and reduce overhead routing costs.
                </p>
            </div>

            <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                <div class="flex items-center gap-3 mb-3">
                    <div class="p-2.5 rounded-xl bg-amber-500/10 text-amber-500">
                        <MagnifyingGlassIcon class="h-5 w-5" />
                    </div>
                    <h4 class="font-bold text-slate-200 text-sm">Load Inspection</h4>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    Expand any DO row using the <strong>Chevron toggle</strong> to inspect payload metric aggregations like Total Weight and Volume prior to dispatch assignment.
                </p>
            </div>
            
            <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                <div class="flex items-center gap-3 mb-3">
                    <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-400">
                        <CheckCircleIcon class="h-5 w-5" />
                    </div>
                    <h4 class="font-bold text-slate-200 text-sm">Manifest Locking</h4>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    After confirming the assignment, associated DO status elevates to <strong>Packed</strong>, readying it for final authorization inside the Dispatch Panel.
                </p>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
select {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 1.25rem center;
    background-repeat: no-repeat;
    background-size: 1.5em 1.5em;
    padding-right: 2.5rem;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
</style>
