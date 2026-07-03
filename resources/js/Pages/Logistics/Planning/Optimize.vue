<script setup>
import { ref, computed, onMounted, onBeforeUnmount, shallowRef } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatNumber } from '@/helpers';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { 
    TruckIcon, 
    MapPinIcon, 
    ArrowPathIcon,
    SparklesIcon,
    ArrowLeftIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    CurrencyDollarIcon,
    ScaleIcon,
    QuestionMarkCircleIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline';
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';
import axios from 'axios';

const props = defineProps({
    deliveryOrders: Array,
    vehicles: Array,
    depot: Object
});

const selectedOrders = ref(props.deliveryOrders.map(o => o.id));
const selectedVehicles = ref(props.vehicles.map(v => v.id));

const loading = ref(false);
const applying = ref(false);
const error = ref(null);
const result = ref(null);
const showHelpModal = ref(false);

const mapEl = ref(null);
const map = shallowRef(null);
const markers = ref(new Map());
const polylines = ref([]);

const colors = ['#3B82F6', '#EF4444', '#10B981', '#8B5CF6', '#F59E0B', '#EC4899', '#06B6D4'];

const ordersMap = computed(() => {
    const map = new Map();
    for (const o of props.deliveryOrders) {
        map.set(o.id, o);
    }
    return map;
});

const vehiclesMap = computed(() => {
    const map = new Map();
    for (const v of props.vehicles) {
        map.set(v.id, v);
    }
    return map;
});

// Calculate total weight of selected DOs
const totalSelectedWeight = computed(() => {
    return props.deliveryOrders
        .filter(o => selectedOrders.value.includes(o.id))
        .reduce((sum, o) => sum + o.weight, 0);
});

// Calculate total capacity of selected vehicles
const totalSelectedCapacity = computed(() => {
    return props.vehicles
        .filter(v => selectedVehicles.value.includes(v.id))
        .reduce((sum, v) => sum + (v.capacity_weight || 1000), 0);
});

const initMap = () => {
    if (!mapEl.value || map.value) return;

    // Reset Leaflet icon assets
    delete L.Icon.Default.prototype._getIconUrl;
    L.Icon.Default.mergeOptions({
        iconRetinaUrl: new URL('leaflet/dist/images/marker-icon-2x.png', import.meta.url).href,
        iconUrl: new URL('leaflet/dist/images/marker-icon.png', import.meta.url).href,
        shadowUrl: new URL('leaflet/dist/images/marker-shadow.png', import.meta.url).href,
    });

    map.value = L.map(mapEl.value).setView([props.depot.latitude, props.depot.longitude], 11);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map.value);

    renderBaseMarkers();
};

const renderBaseMarkers = () => {
    if (!map.value) return;
    clearMarkers();
    clearRoutes();

    const pts = [];

    // Add Depot Marker
    const depotLatLng = [props.depot.latitude, props.depot.longitude];
    pts.push(depotLatLng);
    
    // Custom Depot Icon
    const depotIcon = L.divIcon({
        html: `<div class="h-6 w-6 rounded-full bg-slate-900 border-2 border-white flex items-center justify-center shadow-lg text-white font-black text-[10px]">D</div>`,
        className: '',
        iconSize: [24, 24],
        iconAnchor: [12, 12]
    });
    
    const depotMarker = L.marker(depotLatLng, { icon: depotIcon }).addTo(map.value);
    depotMarker.bindPopup(`<div class="font-bold">${props.depot.name}</div><div class="text-xs text-slate-500">${props.depot.latitude}, ${props.depot.longitude}</div>`);
    markers.value.set('depot', depotMarker);

    // Add DO Markers
    for (const doItem of props.deliveryOrders) {
        if (!selectedOrders.value.includes(doItem.id)) continue;
        if (!doItem.latitude || !doItem.longitude) continue;

        const latLng = [doItem.latitude, doItem.longitude];
        pts.push(latLng);

        // Standard Marker for pending DOs
        const doIcon = L.divIcon({
            html: `<div class="h-6 w-6 rounded-full bg-blue-600 border-2 border-white flex items-center justify-center shadow-lg text-white font-bold text-[9px]">📦</div>`,
            className: '',
            iconSize: [24, 24],
            iconAnchor: [12, 12]
        });

        const doMarker = L.marker(latLng, { icon: doIcon }).addTo(map.value);
        doMarker.bindPopup(`
            <div class="font-bold text-slate-900">${doItem.do_number}</div>
            <div class="text-xs font-bold text-slate-700">${doItem.customer_name}</div>
            <div class="text-[10px] text-slate-500 mt-1">${doItem.address}</div>
            <div class="text-[10px] font-black text-indigo-600 mt-1">Weight: ${formatNumber(doItem.weight)} kg</div>
        `);
        markers.value.set(doItem.id, doMarker);
    }

    if (pts.length > 0) {
        map.value.fitBounds(L.latLngBounds(pts), { padding: [50, 50] });
    }
};

const clearMarkers = () => {
    for (const m of markers.value.values()) {
        m.remove();
    }
    markers.value = new Map();
};

const clearRoutes = () => {
    for (const poly of polylines.value) {
        poly.remove();
    }
    polylines.value = [];
};

const runOptimization = async () => {
    if (selectedOrders.value.length === 0) {
        alert('Pilih minimal satu Delivery Order untuk dioptimalkan!');
        return;
    }
    if (selectedVehicles.value.length === 0) {
        alert('Pilih minimal satu kendaraan untuk mengangkut barang!');
        return;
    }

    loading.value = true;
    error.value = null;
    result.value = null;

    try {
        const response = await axios.post(route('logistics.planning.optimize.run'), {
            delivery_order_ids: selectedOrders.value,
            vehicle_ids: selectedVehicles.value,
        });

        if (response.data?.success) {
            result.value = response.data.data;
            drawOptimizedRoutes();
        } else {
            error.value = response.data?.message || 'Gagal memproses optimasi rute.';
        }
    } catch (e) {
        error.value = e.response?.data?.message || e.message;
    } finally {
        loading.value = false;
    }
};

const drawOptimizedRoutes = () => {
    if (!map.value || !result.value) return;
    clearMarkers();
    clearRoutes();

    const pts = [];
    const depotLatLng = [props.depot.latitude, props.depot.longitude];
    pts.push(depotLatLng);

    // Custom Depot Icon
    const depotIcon = L.divIcon({
        html: `<div class="h-8 w-8 rounded-full bg-slate-900 border-2 border-white flex items-center justify-center shadow-2xl text-white font-black text-xs">DEPOT</div>`,
        className: '',
        iconSize: [32, 32],
        iconAnchor: [16, 16]
    });
    const depotMarker = L.marker(depotLatLng, { icon: depotIcon }).addTo(map.value);
    markers.value.set('depot', depotMarker);

    // Loop through shipments and draw routes
    result.value.shipments.forEach((shipment, index) => {
        const routeColor = colors[index % colors.length];
        const routePts = [depotLatLng]; // Start at depot

        shipment.stops.forEach((stop) => {
            if (!stop.latitude || !stop.longitude) return;

            const stopLatLng = [stop.latitude, stop.longitude];
            routePts.push(stopLatLng);
            pts.push(stopLatLng);

            // Add stop marker with route-specific color and sequence badge
            const stopIcon = L.divIcon({
                html: `<div class="h-7 w-7 rounded-full flex items-center justify-center border-2 border-white shadow-lg text-white font-black text-xs" style="background-color: ${routeColor}">${stop.sequence}</div>`,
                className: '',
                iconSize: [28, 28],
                iconAnchor: [14, 14]
            });

            const stopMarker = L.marker(stopLatLng, { icon: stopIcon }).addTo(map.value);
            stopMarker.bindPopup(`
                <div class="font-bold text-slate-900">${stop.do_number} (Stop #${stop.sequence})</div>
                <div class="text-xs font-bold text-slate-700">${stop.customer_name}</div>
                <div class="text-[10px] text-slate-500 mt-1">${stop.address}</div>
                <div class="text-[10px] font-black text-indigo-600 mt-1">Vehicle: ${shipment.vehicle_plate}</div>
                <div class="text-[10px] font-black text-emerald-600">Weight: ${formatNumber(stop.weight)} kg</div>
            `);
            markers.value.set(stop.delivery_order_id, stopMarker);
        });

        routePts.push(depotLatLng); // End back at depot

        // Draw polyline route path
        const polyline = L.polyline(routePts, {
            color: routeColor,
            weight: 4,
            opacity: 0.8,
            dashArray: '5, 10'
        }).addTo(map.value);
        
        polyline.bindPopup(`<div class="font-bold text-slate-800">${shipment.route_name}</div><div class="text-xs text-slate-500">Estimasi Jarak: ${shipment.estimated_distance_km} km</div>`);
        polylines.value.push(polyline);
    });

    if (pts.length > 1) {
        map.value.fitBounds(L.latLngBounds(pts), { padding: [50, 50] });
    }
};

const applyRoutes = () => {
    if (!result.value || !result.value.shipments) return;

    applying.value = true;
    router.post(route('logistics.planning.optimize.apply'), {
        shipments: result.value.shipments
    }, {
        onFinish: () => {
            applying.value = false;
        }
    });
};

const toggleSelectOrder = (id) => {
    if (selectedOrders.value.includes(id)) {
        selectedOrders.value = selectedOrders.value.filter(oid => oid !== id);
    } else {
        selectedOrders.value.push(id);
    }
    renderBaseMarkers();
};

const toggleSelectVehicle = (id) => {
    if (selectedVehicles.value.includes(id)) {
        selectedVehicles.value = selectedVehicles.value.filter(vid => vid !== id);
    } else {
        selectedVehicles.value.push(id);
    }
};

onMounted(() => {
    initMap();
});

onBeforeUnmount(() => {
    clearMarkers();
    clearRoutes();
    if (map.value) {
        map.value.remove();
        map.value = null;
    }
});
</script>

<template>
    <Head title="AI VRP Route Optimization" />

    <AppLayout title="AI VRP Route Optimization">
        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Link
                        href="/logistics/planning"
                        class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-500 hover:text-slate-900 dark:hover:text-white transition-colors"
                    >
                        <ArrowLeftIcon class="h-5 w-5" />
                    </Link>
                    <div>
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <span>AI VRP Route Optimizer</span>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-500/10 text-indigo-400 font-black animate-pulse flex items-center gap-1">
                                <SparklesIcon class="h-3 w-3" />
                                Smart AI
                            </span>
                        </h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Rencanakan rute multi-stop paling hemat bahan bakar dan kapasitas berat truk secara otomatis</p>
                    </div>
                </div>
                <button 
                    @click="showHelpModal = true"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 text-sm font-semibold border border-indigo-500/20 transition-all hover:scale-105"
                >
                    <QuestionMarkCircleIcon class="h-5 w-5" />
                    <span>Panduan AI VRP</span>
                </button>
            </div>

            <!-- Error Banner -->
            <div v-if="error" class="rounded-2xl border border-red-200 bg-red-50 dark:border-red-700/30 dark:bg-red-500/10 p-4 flex gap-3 text-sm font-bold text-red-800 dark:text-red-200 animate-shake">
                <ExclamationTriangleIcon class="h-5 w-5 shrink-0 text-red-500" />
                <span>{{ error }}</span>
            </div>

            <!-- Main Layout Grid -->
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">
                <!-- Left Panel: Configurations & AI Results (xl:col-span-5) -->
                <div class="xl:col-span-5 space-y-6">
                    <!-- Setup Selection Panel (Visible when no result is generated) -->
                    <div v-if="!result" class="glass-card rounded-2xl p-6 border border-white/5 shadow-2xl space-y-6">
                        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                            <h4 class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">1. Konfigurasi Optimasi</h4>
                        </div>

                        <!-- Vehicles List -->
                        <div class="space-y-3">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1 flex items-center gap-2">
                                <TruckIcon class="h-3 w-3 text-indigo-400" />
                                Pilih Kendaraan Tersedia ({{ selectedVehicles.length }}/{{ vehicles.length }})
                            </label>
                            <div class="max-h-[160px] overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800 border border-slate-100 dark:border-slate-800 rounded-xl">
                                <div v-if="vehicles.length === 0" class="p-3 text-xs text-slate-500 italic">
                                    Tidak ada armada kendaraan yang tersedia (Available).
                                </div>
                                <div v-for="v in vehicles" :key="v.id" class="p-3 flex items-center justify-between gap-3 text-xs hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                    <label class="flex items-center gap-2.5 font-bold text-slate-800 dark:text-slate-200 cursor-pointer w-full">
                                        <input
                                            type="checkbox"
                                            :value="v.id"
                                            :checked="selectedVehicles.includes(v.id)"
                                            @change="toggleSelectVehicle(v.id)"
                                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                        />
                                        <span>{{ v.license_plate }} ({{ v.vehicle_type }} - {{ v.driver_name || 'No Driver' }})</span>
                                    </label>
                                    <span class="text-[10px] font-black text-slate-500 uppercase tracking-wider shrink-0 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded">
                                        {{ formatNumber(v.capacity_weight) }} kg
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Orders List -->
                        <div class="space-y-3">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1 flex items-center gap-2">
                                <MapPinIcon class="h-3 w-3 text-emerald-400" />
                                Pilih DO untuk Dikirim ({{ selectedOrders.length }}/{{ deliveryOrders.length }})
                            </label>
                            <div class="max-h-[220px] overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800 border border-slate-100 dark:border-slate-800 rounded-xl">
                                <div v-if="deliveryOrders.length === 0" class="p-3 text-xs text-slate-500 italic">
                                    Tidak ada Delivery Order tertunda.
                                </div>
                                <div v-for="o in deliveryOrders" :key="o.id" class="p-3 flex items-center justify-between gap-3 text-xs hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                    <label class="flex items-center gap-2.5 font-bold text-slate-800 dark:text-slate-200 cursor-pointer w-full">
                                        <input
                                            type="checkbox"
                                            :value="o.id"
                                            :checked="selectedOrders.includes(o.id)"
                                            @change="toggleSelectOrder(o.id)"
                                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                        />
                                        <div class="text-left">
                                            <div class="font-black text-slate-900 dark:text-white">{{ o.do_number }}</div>
                                            <div class="text-[10px] text-slate-500">{{ o.customer_name }}</div>
                                        </div>
                                    </label>
                                    <span class="text-[10px] font-black text-indigo-500 uppercase tracking-wider shrink-0 bg-indigo-500/5 px-2 py-0.5 rounded">
                                        {{ formatNumber(o.weight) }} kg
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Capacity Comparison Panel -->
                        <div class="p-4 rounded-2xl border border-slate-100 dark:border-slate-800 space-y-3 bg-slate-50/50 dark:bg-slate-800/10">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-slate-500">Total Kebutuhan Muatan:</span>
                                <span class="font-black text-slate-900 dark:text-white">{{ formatNumber(totalSelectedWeight) }} kg</span>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-slate-500">Total Kapasitas Truk:</span>
                                <span class="font-black text-slate-900 dark:text-white">{{ formatNumber(totalSelectedCapacity) }} kg</span>
                            </div>
                            <!-- Bar Chart -->
                            <div class="relative w-full h-2 bg-slate-200 dark:bg-slate-800 rounded-full overflow-hidden">
                                <div class="absolute h-full rounded-full transition-all duration-500" 
                                    :class="totalSelectedWeight > totalSelectedCapacity ? 'bg-red-500' : 'bg-indigo-500'"
                                    :style="{ width: Math.min((totalSelectedWeight / (totalSelectedCapacity || 1)) * 100, 100) + '%' }">
                                </div>
                            </div>
                            <div v-if="totalSelectedWeight > totalSelectedCapacity" class="text-[10px] font-bold text-red-500 flex items-center gap-1.5 mt-1">
                                <ExclamationTriangleIcon class="h-4 w-4 text-red-500 shrink-0" />
                                <span>Peringatan: Total muatan melebihi batas kapasitas maksimum truk yang dipilih!</span>
                            </div>
                        </div>

                        <!-- Action Button -->
                        <button
                            type="button"
                            @click="runOptimization"
                            :disabled="loading || selectedOrders.length === 0 || selectedVehicles.length === 0"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 px-6 py-4 text-sm font-black uppercase tracking-widest text-white shadow-2xl shadow-indigo-500/20 transition-all active:scale-95 disabled:opacity-50"
                        >
                            <ArrowPathIcon v-if="loading" class="h-5 w-5 animate-spin" />
                            <SparklesIcon v-else class="h-5 w-5" />
                            <span>{{ loading ? 'Sedang Mengoptimasi Rute...' : 'AI Selesaikan Rute VRP' }}</span>
                        </button>
                    </div>

                    <!-- AI Optimization Results Panel (Visible when result exists) -->
                    <div v-else class="glass-card rounded-2xl p-6 border border-white/5 shadow-2xl space-y-6 animate-fade-in-up">
                        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                            <h4 class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">2. Hasil Rekomendasi Rute VRP</h4>
                            <button 
                                type="button" 
                                @click="result = null; renderBaseMarkers();" 
                                class="text-xs font-black text-indigo-500 hover:underline uppercase tracking-wider"
                            >
                                Reset Optimasi
                            </button>
                        </div>

                        <!-- Proposed Shipments -->
                        <div class="space-y-4 max-h-[460px] overflow-y-auto pr-1">
                            <div v-for="(shp, index) in result.shipments" :key="index" 
                                class="rounded-2xl border border-slate-200 dark:border-slate-800 p-4 space-y-3 relative overflow-hidden">
                                <!-- Top Stripe Indicator -->
                                <div class="absolute top-0 left-0 right-0 h-1" :style="{ backgroundColor: colors[index % colors.length] }"></div>

                                <div class="flex justify-between items-start">
                                    <div>
                                        <div class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                                            <span class="h-2 w-2 rounded-full" :style="{ backgroundColor: colors[index % colors.length] }"></span>
                                            {{ shp.vehicle_plate }} ({{ shp.driver_name }})
                                        </div>
                                        <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mt-0.5">
                                            {{ shp.route_name }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Grid Statistics -->
                                <div class="grid grid-cols-3 gap-2 bg-slate-50 dark:bg-slate-800/30 p-2.5 rounded-xl border border-slate-100 dark:border-slate-800 text-[10px] font-bold text-slate-600 dark:text-slate-400">
                                    <div class="space-y-0.5">
                                        <div class="text-slate-400 uppercase tracking-wider flex items-center gap-1">
                                            <ScaleIcon class="h-3 w-3" />
                                            Muatan
                                        </div>
                                        <div class="text-slate-900 dark:text-white font-black">{{ formatNumber(shp.total_weight) }} kg</div>
                                    </div>
                                    <div class="space-y-0.5">
                                        <div class="text-slate-400 uppercase tracking-wider flex items-center gap-1">
                                            <MapPinIcon class="h-3 w-3" />
                                            Jarak
                                        </div>
                                        <div class="text-slate-900 dark:text-white font-black">{{ shp.estimated_distance_km }} km</div>
                                    </div>
                                    <div class="space-y-0.5">
                                        <div class="text-slate-400 uppercase tracking-wider flex items-center gap-1">
                                            <CurrencyDollarIcon class="h-3 w-3" />
                                            Uang Jalan
                                        </div>
                                        <div class="text-emerald-500 font-black">Rp {{ formatNumber(shp.suggested_allowance) }}</div>
                                    </div>
                                </div>

                                <!-- Stops Timeline -->
                                <div class="space-y-2 relative pl-4 border-l-2 border-slate-200 dark:border-slate-800 ml-2">
                                    <div v-for="stop in shp.stops" :key="stop.sequence" class="relative text-xs">
                                        <!-- Dot Indicator -->
                                        <div class="absolute -left-[21px] top-1 h-2.5 w-2.5 rounded-full border-2 border-white shadow bg-slate-400"
                                            :style="{ backgroundColor: colors[index % colors.length] }">
                                        </div>
                                        <div class="flex justify-between items-start gap-2">
                                            <div>
                                                <div class="font-black text-slate-800 dark:text-slate-200">
                                                    {{ stop.sequence }}. {{ stop.customer_name }}
                                                </div>
                                                <div class="text-[10px] text-slate-400">{{ stop.do_number }}</div>
                                            </div>
                                            <span class="text-[10px] font-black text-slate-500 uppercase tracking-wider shrink-0">
                                                {{ formatNumber(stop.weight) }} kg
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Unassigned Orders Warn Banner -->
                        <div v-if="result.unassigned_orders && result.unassigned_orders.length > 0" 
                            class="p-4 rounded-2xl bg-amber-500/5 border border-amber-500/10 text-xs font-bold text-amber-700 dark:text-amber-300 space-y-2">
                            <div class="flex items-center gap-1.5">
                                <ExclamationTriangleIcon class="h-4 w-4 text-amber-500 shrink-0" />
                                <span>Beberapa pesanan tidak terangkut (Kapasitas armada penuh):</span>
                            </div>
                            <ul class="list-disc pl-4 space-y-1 text-[10px]">
                                <li v-for="uo in result.unassigned_orders" :key="uo.delivery_order_id">
                                    {{ uo.do_number }}: {{ uo.reason }}
                                </li>
                            </ul>
                        </div>

                        <!-- Action Buttons -->
                        <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex gap-3">
                            <button
                                type="button"
                                @click="applyRoutes"
                                :disabled="applying"
                                class="w-full inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 hover:bg-emerald-500 px-6 py-4 text-xs font-black uppercase tracking-widest text-white shadow-xl shadow-emerald-500/20 transition-all active:scale-95 disabled:opacity-50"
                            >
                                <CheckCircleIcon v-if="!applying" class="h-5 w-5" />
                                <ArrowPathIcon v-else class="h-5 w-5 animate-spin" />
                                <span>{{ applying ? 'Menerapkan Rute...' : 'Terapkan Rute & Cetak Uang Jalan' }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Right Panel: Leaflet Map (xl:col-span-7) -->
                <div class="xl:col-span-7 glass-card rounded-2xl overflow-hidden relative shadow-2xl border border-white/5">
                    <div class="p-3 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                        <div class="text-xs font-black uppercase tracking-[0.2em] text-slate-500">Visualisasi Rute Pengiriman</div>
                        <div class="text-xs font-bold text-slate-500" v-if="depot">
                            Depot: {{ depot.latitude }}, {{ depot.longitude }}
                        </div>
                    </div>
                    <div ref="mapEl" class="h-[600px] w-full z-10"></div>
                </div>
            </div>
            <!-- AI VRP Guide Modal -->
            <TransitionRoot as="template" :show="showHelpModal">
                <Dialog as="div" class="relative z-[99]" @close="showHelpModal = false">
                    <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                        <div class="fixed inset-0 bg-slate-950/50 dark:bg-slate-950/80 backdrop-blur-sm transition-opacity" />
                    </TransitionChild>

                    <div class="fixed inset-0 z-10 overflow-y-auto">
                        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                            <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                                <DialogPanel class="relative transform overflow-hidden rounded-2xl glass-card text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-xl">
                                    <!-- Title/Header -->
                                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center bg-slate-50 dark:bg-slate-900/50">
                                        <DialogTitle as="h3" class="text-sm font-black uppercase tracking-wider text-slate-800 dark:text-white flex items-center gap-2">
                                            <QuestionMarkCircleIcon class="h-5 w-5 text-indigo-500" />
                                            <span>Cara Kerja & Panduan AI VRP</span>
                                        </DialogTitle>
                                        <button @click="showHelpModal = false" type="button" class="text-slate-500 hover:text-slate-900 dark:text-white transition-colors">
                                            <XMarkIcon class="h-6 w-6" />
                                        </button>
                                    </div>

                                    <!-- Content -->
                                    <div class="p-6 space-y-4 text-xs leading-relaxed text-slate-600 dark:text-slate-300">
                                        <div class="space-y-2">
                                            <h4 class="font-bold text-slate-950 dark:text-white text-xs">1. Apa itu AI VRP?</h4>
                                            <p>AI VRP (Vehicle Routing Problem) Optimizer adalah asisten logistik pintar yang otomatis menghitung rute pengiriman multi-tujuan (multi-stop) yang paling optimal. Sistem mencari rute terpendek untuk menghemat bahan bakar truk.</p>
                                        </div>

                                        <div class="space-y-2">
                                            <h4 class="font-bold text-slate-950 dark:text-white text-xs">2. Persyaratan Input Data</h4>
                                            <ul class="list-disc pl-4 space-y-1">
                                                <li><strong>Peta Koordinat</strong>: Pastikan alamat pelanggan pada Delivery Order (DO) yang dipilih memiliki koordinat Latitude & Longitude terdaftar pada Master Data Pelanggan.</li>
                                                <li><strong>Kapasitas Truk</strong>: Berat total muatan barang pada DO diusahakan tidak melebihi daya tampung maksimum truk terpilih. Sistem akan memberi peringatan merah jika terjadi overload.</li>
                                            </ul>
                                        </div>

                                        <div class="space-y-2">
                                            <h4 class="font-bold text-slate-950 dark:text-white text-xs">3. Langkah-Langkah Penggunaan</h4>
                                            <ol class="list-decimal pl-4 space-y-1.5">
                                                <li>Pilih satu atau lebih <strong>Kendaraan</strong> yang ingin dikerahkan pada panel kiri.</li>
                                                <li>Centang daftar <strong>Delivery Order (DO)</strong> yang ingin dijadwalkan hari ini.</li>
                                                <li>Klik tombol biru <strong>AI Selesaikan Rute VRP</strong> untuk mengirim data ke AI.</li>
                                                <li>Periksa garis rute dan urutan nomor stop (Sequence 1, 2, dst) yang digambar pada peta OpenStreetMap di sebelah kanan.</li>
                                                <li>Tinjau rincian biaya uang jalan rekomendasi di panel hasil optimasi. Jika sudah sesuai, klik <strong>Terapkan Rute & Cetak Uang Jalan</strong>.</li>
                                            </ol>
                                        </div>
                                    </div>

                                    <!-- Footer -->
                                    <div class="px-6 py-4 bg-slate-50 dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 flex justify-end">
                                        <button @click="showHelpModal = false" type="button" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold transition-all text-xs">Mengerti</button>
                                    </div>
                                </DialogPanel>
                            </TransitionChild>
                        </div>
                    </div>
                </Dialog>
            </TransitionRoot>
        </div>
    </AppLayout>
</template>

<style>
/* Leaflet popup customization to match dark themes */
.dark .leaflet-popup-content-wrapper,
.dark .leaflet-popup-tip {
    background: #0f172a !important;
    color: #f8fafc !important;
    border: 1px border #1e293b !important;
    box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.5) !important;
}
.leaflet-container {
    font-family: inherit !important;
}
</style>
