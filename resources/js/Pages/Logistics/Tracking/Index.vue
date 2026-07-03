<script setup>
import { computed, onBeforeUnmount, onMounted, ref, shallowRef } from 'vue';
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

onMounted(() => {
    initMap();
    fetchTracking();
});

onBeforeUnmount(() => {
    clearMarkers();
    if (map.value) {
        map.value.remove();
        map.value = null;
    }
});
</script>

<template>
    <Head title="Fleet Tracking" />

    <AppLayout title="Fleet Tracking">
        <div class="p-6 space-y-6">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-gradient-to-br from-emerald-600 to-teal-600 rounded-xl shadow-lg">
                        <MapPinIcon class="h-6 w-6 text-white" />
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white">Fleet Tracking</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Posisi terakhir kendaraan yang sudah di-link ke Traccar</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Link
                        href="/settings/traccar"
                        class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-sm font-bold text-slate-700 dark:text-slate-200 transition-colors"
                    >
                        Settings Traccar
                    </Link>
                    <button
                        type="button"
                        @click="fetchTracking"
                        :disabled="loading"
                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-bold text-white shadow-lg shadow-emerald-500/20 transition-all hover:bg-emerald-500 active:scale-95 disabled:opacity-60"
                    >
                        <ArrowPathIcon class="h-5 w-5" />
                        <span>{{ loading ? 'Refreshing...' : 'Refresh' }}</span>
                    </button>
                </div>
            </div>

            <div v-if="error" class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-bold text-red-800 dark:border-red-700/30 dark:bg-red-500/10 dark:text-red-200">
                {{ error }}
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 glass-card rounded-2xl overflow-hidden">
                    <div class="p-3 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                        <div class="text-xs font-black uppercase tracking-[0.2em] text-slate-500">Map</div>
                        <div class="text-xs font-bold text-slate-500" v-if="lastUpdatedAt">
                            Updated: {{ formatDateTime(lastUpdatedAt) }}
                        </div>
                    </div>
                    <div ref="mapEl" class="h-[520px] w-full"></div>
                </div>

                <div class="glass-card rounded-2xl overflow-hidden">
                    <div class="p-3 border-b border-slate-200 dark:border-slate-700">
                        <div class="text-xs font-black uppercase tracking-[0.2em] text-slate-500">Linked Vehicles</div>
                    </div>

                    <div class="divide-y divide-slate-200 dark:divide-slate-700 max-h-[520px] overflow-auto">
                        <div v-if="vehiclesWithTracking.length === 0" class="p-4 text-sm text-slate-500">
                            Belum ada kendaraan yang di-link ke device Traccar.
                        </div>

                        <div v-for="v in vehiclesWithTracking" :key="v.id" class="p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-sm font-black text-slate-900 dark:text-white">{{ v.license_plate }}</div>
                                    <div class="text-xs font-bold text-slate-500">{{ v.driver_name || '-' }}</div>
                                </div>
                                <div class="text-[10px] font-black uppercase tracking-widest px-2 py-1 rounded-full"
                                    :class="v.position ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-500/10 dark:text-slate-300'">
                                    {{ v.position ? 'Online' : 'No Position' }}
                                </div>
                            </div>

                            <div class="mt-2 text-xs text-slate-600 dark:text-slate-300 space-y-1">
                                <div v-if="v.device" class="font-medium">
                                    Device: {{ v.device.name }}{{ v.device.uniqueId ? ` (${v.device.uniqueId})` : '' }}
                                </div>
                                <div v-if="v.position">
                                    Lat/Lng: {{ v.position.latitude }}, {{ v.position.longitude }}
                                </div>
                                <div v-if="v.position && v.position.speed !== undefined">
                                    Speed: {{ v.position.speed }} kn
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
