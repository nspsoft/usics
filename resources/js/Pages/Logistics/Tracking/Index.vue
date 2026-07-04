<script setup>
import { computed, onBeforeUnmount, onMounted, ref, shallowRef, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { ArrowPathIcon, MapPinIcon } from '@heroicons/vue/24/outline';
import { formatDateTime } from '@/helpers';

const props = defineProps({
    vehicles: Array,
    traccarConfigured: Boolean,
});

const mapEl = ref(null);
const map = shallowRef(null);
const markers = ref(new Map());

const loading = ref(false);
const error = ref(null);
const lastUpdatedAt = ref(null);

const devices = ref([]);
const positions = ref([]);

const vehiclesWithTracking = computed(() => {
    const byDeviceId = new Map();
    for (const p of positions.value || []) {
        if (p?.deviceId) byDeviceId.set(p.deviceId, p);
    }

    const byId = new Map();
    for (const d of devices.value || []) {
        if (d?.id) byId.set(d.id, d);
    }

    return (props.vehicles || []).map((v) => {
        const device = byId.get(v.traccar_device_id) || null;
        const position = byDeviceId.get(v.traccar_device_id) || null;
        return { ...v, device, position };
    });
});

const initMap = () => {
    if (!mapEl.value || map.value) return;

    delete L.Icon.Default.prototype._getIconUrl;
    L.Icon.Default.mergeOptions({
        iconRetinaUrl: new URL('leaflet/dist/images/marker-icon-2x.png', import.meta.url).href,
        iconUrl: new URL('leaflet/dist/images/marker-icon.png', import.meta.url).href,
        shadowUrl: new URL('leaflet/dist/images/marker-shadow.png', import.meta.url).href,
    });

    map.value = L.map(mapEl.value).setView([-6.2088, 106.8456], 11);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map.value);
};

const clearMarkers = () => {
    for (const m of markers.value.values()) {
        m.remove();
    }
    markers.value = new Map();
};

const renderMarkers = () => {
    if (!map.value) return;

    clearMarkers();

    const pts = [];
    for (const v of vehiclesWithTracking.value) {
        const p = v.position;
        if (!p || !p.latitude || !p.longitude) continue;

        const latLng = [p.latitude, p.longitude];
        pts.push(latLng);

        const marker = L.marker(latLng).addTo(map.value);
        marker.bindPopup(`<div style="font-weight:700">${v.license_plate}</div><div style="font-size:12px">${v.driver_name || ''}</div>`);
        markers.value.set(v.id, marker);
    }

    if (pts.length > 0) {
        map.value.fitBounds(L.latLngBounds(pts), { padding: [40, 40] });
    }
};

const fetchTracking = async () => {
    error.value = null;

    if (!props.traccarConfigured) {
        error.value = 'Konfigurasi Traccar belum lengkap. Silakan set di Settings → Traccar Tracking.';
        return;
    }

    loading.value = true;
    try {
        const [dRes, pRes] = await Promise.all([
            axios.get(route('logistics.traccar.devices')),
            axios.get(route('logistics.traccar.positions')),
        ]);

        devices.value = dRes.data?.data || [];
        positions.value = pRes.data?.data || [];
        lastUpdatedAt.value = new Date();
        renderMarkers();
    } catch (e) {
        error.value = e.response?.data?.message || e.message;
        devices.value = [];
        positions.value = [];
        renderMarkers();
    } finally {
        loading.value = false;
    }
};

// --- Theme Reactive Sync ---
const isLightMode = ref(false);
let observer;
onMounted(() => {
    isLightMode.value = !document.documentElement.classList.contains('dark');
    observer = new MutationObserver(() => {
        isLightMode.value = !document.documentElement.classList.contains('dark');
    });
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
    initMap();
    fetchTracking();
});

onBeforeUnmount(() => {
    clearMarkers();
    if (map.value) {
        map.value.remove();
        map.value = null;
    }
    if (observer) observer.disconnect();
});
</script>

<template>
    <Head title="Fleet Tracking" />

    <AppLayout title="Fleet Tracking" :render-header="false">
        <div class="min-h-screen bg-slate-50 dark:bg-[#050510] relative overflow-hidden font-mono text-slate-800 dark:text-cyan-50 selection:bg-indigo-500/30 transition-colors duration-300">
            
            <!-- Dynamic Background -->
            <div class="fixed inset-0 z-0 pointer-events-none">
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/5 to-slate-100 dark:from-indigo-955/20 dark:to-[#050510]"></div>
                 <div class="perspective-grid absolute inset-0 opacity-[0.05] dark:opacity-10"></div>
            </div>

            <div class="relative z-10 p-6 space-y-6 max-w-7xl mx-auto">
                
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-slate-200 dark:border-white/10 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-gradient-to-br from-emerald-600 to-teal-650 rounded-xl shadow-sm text-white">
                            <MapPinIcon class="h-6 w-6" />
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-slate-900 dark:text-white leading-none mb-1.5">Fleet Tracking</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 leading-none">Posisi terakhir kendaraan yang sudah di-link ke Traccar</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <Link
                            href="/settings/traccar"
                            class="px-4 py-2 rounded-xl bg-white hover:bg-slate-100 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:border-transparent text-xs font-bold text-slate-700 dark:text-slate-250 transition-colors shadow-sm dark:shadow-none"
                        >
                            Settings Traccar
                        </Link>
                        <button
                            type="button"
                            @click="fetchTracking"
                            :disabled="loading"
                            class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-emerald-555 active:scale-95 disabled:opacity-60 border-0 cursor-pointer"
                        >
                            <ArrowPathIcon class="h-4 w-4" :class="loading ? 'animate-spin' : ''" />
                            <span>{{ loading ? 'Refreshing...' : 'Refresh' }}</span>
                        </button>
                    </div>
                </div>

                <div v-if="error" class="rounded-2xl border border-red-200 bg-red-50 p-4 text-xs font-bold text-red-800 dark:border-red-700/30 dark:bg-red-500/10 dark:text-red-200 animate-shake">
                    {{ error }}
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Map Panel -->
                    <div class="lg:col-span-2 bg-white/70 dark:bg-[#0a0a16]/60 border border-slate-205 dark:border-white/5 rounded-xl overflow-hidden shadow-sm dark:shadow-none">
                        <div class="p-3 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/20 flex items-center justify-between">
                            <div class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">Live Map</div>
                            <div class="text-[10px] font-bold text-slate-500 dark:text-slate-400 font-mono" v-if="lastUpdatedAt">
                                Updated: {{ formatDateTime(lastUpdatedAt) }}
                            </div>
                        </div>
                        <div ref="mapEl" class="h-[520px] w-full" :class="isLightMode ? '' : 'dark:invert dark:hue-rotate-180 dark:brightness-95 dark:contrast-90'"></div>
                    </div>

                    <!-- Linked Vehicles List -->
                    <div class="bg-white/70 dark:bg-[#0a0a16]/60 border border-slate-205 dark:border-white/5 rounded-xl overflow-hidden shadow-sm dark:shadow-none">
                        <div class="p-3 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/20">
                            <div class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">Linked Vehicles</div>
                        </div>

                        <div class="divide-y divide-slate-100 dark:divide-slate-800 max-h-[520px] overflow-auto">
                            <div v-if="vehiclesWithTracking.length === 0" class="p-4 text-xs text-slate-500 italic">
                                Belum ada kendaraan yang di-link ke device Traccar.
                            </div>

                            <div v-for="v in vehiclesWithTracking" :key="v.id" class="p-4 hover:bg-slate-50/50 dark:hover:bg-white/[0.02] transition-colors">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="text-sm font-black text-slate-900 dark:text-white font-mono tracking-widest">{{ v.license_plate }}</div>
                                        <div class="text-xs font-bold text-slate-500 dark:text-slate-400">{{ v.driver_name || '-' }}</div>
                                    </div>
                                    <div class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded border"
                                        :class="v.position 
                                            ? 'bg-emerald-50 border-emerald-200 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300 dark:border-transparent' 
                                            : 'bg-slate-100 border-slate-200 text-slate-500 dark:bg-slate-500/10 dark:text-slate-350 dark:border-transparent'">
                                        {{ v.position ? 'Online' : 'No Position' }}
                                    </div>
                                </div>

                                <div class="mt-2 text-xs text-slate-650 dark:text-slate-300 space-y-1">
                                    <div v-if="v.device" class="font-medium">
                                        Device: {{ v.device.name }}{{ v.device.uniqueId ? ` (${v.device.uniqueId})` : '' }}
                                    </div>
                                    <div v-if="v.position" class="font-mono text-[10px]">
                                        Lat/Lng: {{ v.position.latitude.toFixed(6) }}, {{ v.position.longitude.toFixed(6) }}
                                    </div>
                                    <div v-if="v.position && v.position.speed !== undefined" class="font-mono text-[10px]">
                                        Speed: {{ (v.position.speed * 1.852).toFixed(1) }} km/h
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
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

.perspective-grid {
    background-image: 
        linear-gradient(to right, rgba(16, 185, 129, 0.05) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(16, 185, 129, 0.05) 1px, transparent 1px);
    background-size: 40px 40px;
    transform: perspective(500px) rotateX(60deg) translateY(-100px) scale(2);
    animation: grid-move 20s linear infinite;
    transform-origin: top;
}

@keyframes grid-move {
    0% { background-position: 0 0; }
    100% { background-position: 0 40px; }
}

.dark .glow-text {
    text-shadow: 0 0 10px rgba(16, 185, 129, 0.3);
}
</style>
