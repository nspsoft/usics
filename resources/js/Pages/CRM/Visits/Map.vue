<script setup>
import { ref, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { 
    MapPinIcon, 
    ArrowLeftIcon,
    MagnifyingGlassIcon,
    FunnelIcon,
    InformationCircleIcon,
    UserIcon,
    CalendarDaysIcon,
    ClockIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    customers: Array,
    leads: Array,
    visits: Array,
    salesList: Array,
    canViewAll: Boolean,
    filters: Object,
    title: String
});

const mapContainer = ref(null);
const map = ref(null);
const markersGroup = ref(null);
const linesGroup = ref(null);
const searchQuery = ref('');
const selectedSalesId = ref(props.filters.sales_id || '');

// Sidebar selected info
const selectedItem = ref(null);

// Custom map icon factory using inline SVG/CSS (avoids broken Vite default assets)
const createCustomIcon = (type) => {
    let pinColor = '#3b82f6'; // blue (customer)
    let ringHtml = '';
    
    if (type === 'customer') {
        pinColor = '#3b82f6'; // Blue
    } else if (type === 'lead') {
        pinColor = '#f59e0b'; // Amber/Orange
    } else if (type === 'checked_in') {
        pinColor = '#10b981'; // Emerald Green
        ringHtml = `<div class="absolute w-8 h-8 rounded-full bg-emerald-500/30 animate-ping"></div>`;
    } else if (type === 'completed') {
        pinColor = '#6366f1'; // Indigo
    }

    return L.divIcon({
        className: 'custom-leaflet-marker',
        html: `
            <div class="relative flex items-center justify-center w-8 h-8">
                ${ringHtml}
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="${pinColor}" class="w-8 h-8 drop-shadow-md z-10">
                    <path fill-rule="evenodd" d="M11.54 22.351l.07.04.028.016a.76.76 0 00.723 0l.028-.015.071-.041a16.975 16.975 0 001.157-1.071c.883-.913 1.685-1.89 2.32-2.91C18.89 15.741 20 13.092 20 10.08c0-5.673-4.507-10.27-10.017-10.08C4.556.18 0 4.793 0 10.08c0 3.012 1.11 5.661 2.382 7.284a17.11 17.11 0 003.477 3.981 16.975 16.975 0 001.157 1.071l.07.04.028.016zM12 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                </svg>
            </div>
        `,
        iconSize: [32, 32],
        iconAnchor: [16, 32],
        popupAnchor: [0, -32]
    });
};

const initMap = () => {
    if (!mapContainer.value) return;

    // Default coordinate: Jakarta
    const defaultCenter = [-6.2088, 106.8456];
    map.value = L.map(mapContainer.value, {
        zoomControl: false // Move zoom control to bottom right
    }).setView(defaultCenter, 11);

    // OpenStreetMap dark-ish standard tile style
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map.value);

    L.control.zoom({
        position: 'bottomright'
    }).addTo(map.value);

    markersGroup.value = L.layerGroup().addTo(map.value);
    linesGroup.value = L.layerGroup().addTo(map.value);

    renderMarkers();
};

const renderMarkers = () => {
    if (!map.value || !markersGroup.value || !linesGroup.value) return;

    markersGroup.value.clearLayers();
    linesGroup.value.clearLayers();

    const bounds = [];

    // 1. Plot Customers
    props.customers.forEach(customer => {
        if (!customer.latitude || !customer.longitude) return;
        
        // Skip based on search query
        if (searchQuery.value && !customer.name.toLowerCase().includes(searchQuery.value.toLowerCase())) {
            return;
        }

        const latLng = [customer.latitude, customer.longitude];
        bounds.push(latLng);

        const marker = L.marker(latLng, {
            icon: createCustomIcon('customer')
        });

        const popupContent = `
            <div class="p-3 text-slate-100 bg-slate-900 rounded-lg min-w-[200px]">
                <h4 class="font-bold text-sm text-white border-b border-slate-700 pb-1 mb-2">${customer.name}</h4>
                <p class="text-xs text-slate-400 mb-1">📍 ${customer.address}</p>
                <p class="text-xs text-slate-400">📞 ${customer.phone || '-'}</p>
            </div>
        `;

        marker.bindPopup(popupContent, {
            className: 'dark-theme-popup'
        });

        marker.on('click', () => {
            selectedItem.value = {
                type: 'customer',
                data: customer
            };
        });

        markersGroup.value.addLayer(marker);
    });

    // 2. Plot Leads
    props.leads.forEach(lead => {
        if (!lead.latitude || !lead.longitude) return;

        // Skip based on search query
        if (searchQuery.value && !lead.name.toLowerCase().includes(searchQuery.value.toLowerCase())) {
            return;
        }

        const latLng = [lead.latitude, lead.longitude];
        bounds.push(latLng);

        const marker = L.marker(latLng, {
            icon: createCustomIcon('lead')
        });

        const popupContent = `
            <div class="p-3 text-slate-100 bg-slate-900 rounded-lg min-w-[200px]">
                <h4 class="font-bold text-sm text-amber-400 border-b border-slate-700 pb-1 mb-2">${lead.name}</h4>
                <p class="text-xs text-slate-400 mb-1">📋 Calon Pelanggan</p>
                <p class="text-xs text-slate-300">${lead.address}</p>
            </div>
        `;

        marker.bindPopup(popupContent, {
            className: 'dark-theme-popup'
        });

        marker.on('click', () => {
            selectedItem.value = {
                type: 'lead',
                data: lead
            };
        });

        markersGroup.value.addLayer(marker);
    });

    // 3. Plot Check-in Logs
    props.visits.forEach(visit => {
        if (!visit.check_in_lat || !visit.check_in_lng) return;

        const latLng = [visit.check_in_lat, visit.check_in_lng];
        bounds.push(latLng);

        // Marker for check-in
        const checkInMarker = L.marker(latLng, {
            icon: createCustomIcon(visit.status)
        });

        const popupContent = `
            <div class="p-3 text-slate-100 bg-slate-900 rounded-lg min-w-[220px]">
                <h4 class="font-bold text-sm text-emerald-400 border-b border-slate-700 pb-1 mb-2">Check-In: ${visit.sales_name}</h4>
                <p class="text-xs text-slate-300 font-bold mb-1">🏢 Kunjungan: ${visit.client_name}</p>
                <p class="text-[11px] text-slate-400 mb-2">🎯 Tujuan: ${visit.purpose}</p>
                <p class="text-[10px] text-slate-500 mb-1">🕒 Jam Masuk: ${visit.check_in_at}</p>
                ${visit.check_out_at ? `<p class="text-[10px] text-slate-500 mb-2">🕒 Jam Keluar: ${visit.check_out_at}</p>` : ''}
                ${visit.summary ? `<div class="bg-slate-950 p-2 rounded text-[11px] text-slate-300 border border-slate-800"><b>Laporan:</b> "${visit.summary}"</div>` : '<p class="text-[11px] text-amber-500 italic">Sedang berlangsung...</p>'}
            </div>
        `;

        checkInMarker.bindPopup(popupContent, {
            className: 'dark-theme-popup'
        });

        checkInMarker.on('click', () => {
            selectedItem.value = {
                type: 'visit',
                data: visit
            };
        });

        markersGroup.value.addLayer(checkInMarker);

        // Drawing accurate dashed lines between check-in marker and official customer coordinates
        let officialLatLng = null;
        
        // Find corresponding customer or lead coordinates
        const matchedCust = props.customers.find(c => c.name === visit.client_name);
        if (matchedCust && matchedCust.latitude) {
            officialLatLng = [matchedCust.latitude, matchedCust.longitude];
        } else {
            const matchedLead = props.leads.find(l => l.name.startsWith(visit.client_name) || visit.client_name.startsWith(l.name));
            if (matchedLead && matchedLead.latitude) {
                officialLatLng = [matchedLead.latitude, matchedLead.longitude];
            }
        }

        if (officialLatLng) {
            // Draw a dashed connecting line (shows deviation of check-in position from actual HQ)
            const polyline = L.polyline([latLng, officialLatLng], {
                color: visit.status === 'checked_in' ? '#f59e0b' : '#10b981',
                weight: 2,
                dashArray: '5, 10',
                opacity: 0.7
            });
            linesGroup.value.addLayer(polyline);
        }
    });

    // Fit map bounds to show all pins if bounds exist
    if (bounds.length > 0) {
        map.value.fitBounds(bounds, { padding: [50, 50] });
    }
};

const handleSalesFilter = () => {
    router.get(route('crm.visits.map'), {
        sales_id: selectedSalesId.value
    }, {
        preserveState: true,
        onSuccess: () => {
            nextTick(() => {
                renderMarkers();
            });
        }
    });
};

watch(() => searchQuery.value, () => {
    renderMarkers();
});

onMounted(() => {
    initMap();
});

onBeforeUnmount(() => {
    if (map.value) {
        map.value.remove();
    }
});

const flyToLocation = (lat, lng) => {
    if (map.value && lat && lng) {
        map.value.flyTo([lat, lng], 15, {
            animate: true,
            duration: 1.5
        });
    }
};
</script>

<template>
    <Head :title="title" />

    <AppLayout>
        <div class="h-[calc(100vh-64px)] flex flex-col md:flex-row bg-slate-950 text-slate-100 overflow-hidden">
            <!-- Sidebar panel -->
            <div class="w-full md:w-96 bg-slate-900 border-r border-slate-800 flex flex-col h-1/3 md:h-full z-10 shadow-2xl">
                <!-- Header -->
                <div class="p-4 border-b border-slate-800 bg-slate-950/40 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <Link 
                            :href="route('crm.visits.index')"
                            class="p-2 bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-white rounded-xl border border-slate-800 transition-colors"
                        >
                            <ArrowLeftIcon class="h-4 w-4" />
                        </Link>
                        <h2 class="font-bold text-white tracking-wide">Peta Kunjungan Sales</h2>
                    </div>
                </div>

                <!-- Filters -->
                <div class="p-4 border-b border-slate-800 space-y-3">
                    <!-- Search -->
                    <div class="relative">
                        <input 
                            v-model="searchQuery" 
                            type="text" 
                            placeholder="Cari Customer / Lead..."
                            class="w-full bg-slate-950 border-slate-800 rounded-xl py-2 pl-9 pr-4 text-xs text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50"
                        />
                        <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-500" />
                    </div>

                    <!-- Salesperson dropdown -->
                    <div v-if="canViewAll">
                        <select 
                            v-model="selectedSalesId" 
                            @change="handleSalesFilter"
                            class="w-full bg-slate-950 border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:ring-blue-500/50"
                        >
                            <option value="">Semua Salesperson</option>
                            <option v-for="sales in salesList" :key="sales.id" :value="sales.id">
                                {{ sales.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Info detail sidebar -->
                <div class="flex-1 overflow-y-auto p-4 space-y-4">
                    <!-- Welcome or Selected Panel -->
                    <div v-if="!selectedItem" class="text-center py-12 px-6 bg-slate-950/20 border border-slate-800/40 rounded-2xl">
                        <InformationCircleIcon class="h-10 w-10 text-slate-600 mx-auto mb-3" />
                        <h4 class="font-bold text-white text-sm">Informasi Kunjungan</h4>
                        <p class="text-xs text-slate-400 mt-1">Klik penanda pin di peta atau cari pelanggan di atas untuk melihat ringkasan detail.</p>
                    </div>

                    <!-- Customer Detail Panel -->
                    <div v-else-if="selectedItem.type === 'customer'" class="space-y-4">
                        <div class="bg-blue-950/20 border border-blue-500/20 rounded-2xl p-4">
                            <span class="px-2 py-0.5 rounded bg-blue-500/10 border border-blue-500/30 text-[10px] text-blue-400 font-bold uppercase tracking-wider mb-2 inline-block">Customer</span>
                            <h3 class="font-bold text-white text-lg mb-1">{{ selectedItem.data.name }}</h3>
                            <p class="text-xs text-slate-400 mb-3">📍 {{ selectedItem.data.address }}</p>
                            <p class="text-xs text-slate-300">📞 Phone: {{ selectedItem.data.phone || '-' }}</p>

                            <button 
                                @click="flyToLocation(selectedItem.data.latitude, selectedItem.data.longitude)"
                                class="mt-4 w-full py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-bold transition-all shadow-md flex items-center justify-center gap-1.5"
                            >
                                <MapPinIcon class="h-4 w-4" />
                                Fokus ke Lokasi
                            </button>
                        </div>
                    </div>

                    <!-- Lead Detail Panel -->
                    <div v-else-if="selectedItem.type === 'lead'" class="space-y-4">
                        <div class="bg-amber-950/20 border border-amber-500/20 rounded-2xl p-4">
                            <span class="px-2 py-0.5 rounded bg-amber-500/10 border border-amber-500/30 text-[10px] text-amber-400 font-bold uppercase tracking-wider mb-2 inline-block">Lead / Calon</span>
                            <h3 class="font-bold text-white text-lg mb-1">{{ selectedItem.data.name }}</h3>
                            <p class="text-xs text-slate-300 leading-relaxed">📄 Detail: {{ selectedItem.data.address }}</p>

                            <button 
                                @click="flyToLocation(selectedItem.data.latitude, selectedItem.data.longitude)"
                                class="mt-4 w-full py-2 bg-amber-600 hover:bg-amber-500 text-white rounded-xl text-xs font-bold transition-all shadow-md flex items-center justify-center gap-1.5"
                            >
                                <MapPinIcon class="h-4 w-4" />
                                Fokus ke Lokasi
                            </button>
                        </div>
                    </div>

                    <!-- Visit Log Detail Panel -->
                    <div v-else-if="selectedItem.type === 'visit'" class="space-y-4">
                        <div class="bg-emerald-950/20 border border-emerald-500/20 rounded-2xl p-4 space-y-3">
                            <div class="flex items-center justify-between">
                                <span :class="['px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider', selectedItem.data.status === 'checked_in' ? 'bg-amber-950/40 text-amber-400 border border-amber-500/20 animate-pulse' : 'bg-emerald-950/40 text-emerald-400 border border-emerald-500/20']">
                                    {{ selectedItem.data.status === 'checked_in' ? 'Sedang Kunjungan' : 'Kunjungan Selesai' }}
                                </span>
                            </div>
                            
                            <div>
                                <h4 class="text-xs text-slate-400 font-bold uppercase tracking-wider">Salesperson</h4>
                                <p class="text-sm text-white font-bold flex items-center gap-1 mt-0.5">
                                    <UserIcon class="h-4 w-4 text-slate-400" />
                                    {{ selectedItem.data.sales_name }}
                                </p>
                            </div>

                            <div>
                                <h4 class="text-xs text-slate-400 font-bold uppercase tracking-wider">Perusahaan</h4>
                                <p class="text-sm text-slate-200 font-bold">{{ selectedItem.data.client_name }}</p>
                            </div>

                            <div>
                                <h4 class="text-xs text-slate-400 font-bold uppercase tracking-wider">Tujuan Pertemuan</h4>
                                <p class="text-xs text-slate-300 mt-0.5 font-medium">{{ selectedItem.data.purpose }}</p>
                            </div>

                            <div class="border-t border-slate-800/60 pt-3 space-y-2 text-[11px] text-slate-400">
                                <p class="flex items-center gap-1">
                                    <ClockIcon class="h-3.5 w-3.5 text-emerald-400" />
                                    Masuk: {{ selectedItem.data.check_in_at }}
                                </p>
                                <p v-if="selectedItem.data.check_out_at" class="flex items-center gap-1">
                                    <ClockIcon class="h-3.5 w-3.5 text-blue-400" />
                                    Keluar: {{ selectedItem.data.check_out_at }}
                                </p>
                            </div>

                            <div v-if="selectedItem.data.summary" class="bg-slate-950/60 border border-slate-850 p-2.5 rounded-xl text-xs mt-2">
                                <span class="font-bold text-slate-400 block mb-1">Hasil Meeting:</span>
                                <p class="text-slate-300 italic">"{{ selectedItem.data.summary }}"</p>
                            </div>

                            <button 
                                @click="flyToLocation(selectedItem.data.check_in_lat, selectedItem.data.check_in_lng)"
                                class="mt-4 w-full py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-bold transition-all border border-slate-700 flex items-center justify-center gap-1.5"
                            >
                                <MapPinIcon class="h-4 w-4 text-emerald-400" />
                                Fokus ke Check-In GPS
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Map View Area -->
            <div class="flex-1 relative h-2/3 md:h-full">
                <!-- Legend overlay -->
                <div class="absolute top-4 left-4 z-[500] bg-slate-900/90 backdrop-blur border border-slate-800 p-3.5 rounded-2xl shadow-xl space-y-2 text-xs">
                    <h5 class="font-bold text-white border-b border-slate-800 pb-1 mb-1.5 uppercase tracking-wider text-[10px]">Legenda Peta</h5>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-[#3b82f6]"></span>
                        <span>Pelanggan (Customer)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-[#f59e0b]"></span>
                        <span>Calon Pelanggan (Lead)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-[#10b981] animate-pulse"></span>
                        <span>Kunjungan Aktif (Checked In)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-[#6366f1]"></span>
                        <span>Kunjungan Selesai</span>
                    </div>
                    <div class="text-[9px] text-slate-500 border-t border-slate-800 pt-1.5 mt-1">
                        * Garis putus-putus menunjukkan deviasi GPS check-in.
                    </div>
                </div>

                <div ref="mapContainer" class="w-full h-full bg-slate-950"></div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
/* Style override for Dark Mode Leaflet popups */
.dark-theme-popup .leaflet-popup-content-wrapper {
    background-color: #0f172a !important; /* slate-900 */
    border: 1px solid #1e293b !important; /* slate-800 */
    color: #cbd5e1 !important; /* slate-300 */
    border-radius: 12px !important;
    padding: 0 !important;
    overflow: hidden;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.5), 0 4px 6px -2px rgba(0, 0, 0, 0.5) !important;
}

.dark-theme-popup .leaflet-popup-content {
    margin: 0 !important;
}

.dark-theme-popup .leaflet-popup-tip {
    background-color: #0f172a !important;
    border: 1px solid #1e293b !important;
}

/* Make Leaflet tiles slightly darker and modern using CSS filters if needed */
.leaflet-tile-container {
    filter: invert(90%) hue-rotate(180deg) brightness(95%) contrast(90%);
}
</style>
