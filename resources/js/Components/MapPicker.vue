<script setup>
import { onMounted, ref, onBeforeUnmount, watch, nextTick } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { XMarkIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline'; // Updated import

// Prop to control visibility
const props = defineProps({
    show: Boolean,
    initialAddress: {
        type: String,
        default: ''
    }
});

watch(() => props.show, (val) => {
    if (val) {
        nextTick(() => {
            // Include a small delay for animation/transition if needed, or just nextTick
            setTimeout(() => {
                initMap();
                if (props.initialAddress) {
                    searchQuery.value = props.initialAddress;
                    searchLocation(true);
                }
            }, 100);
        });
    } else {
        if (map.value) {
            map.value.remove();
            map.value = null;
        }
    }
});

const emit = defineEmits(['close', 'confirm']);

const mapContainer = ref(null);
const map = ref(null);
const marker = ref(null);
const searchQuery = ref('');
const searchResults = ref([]);
const isSearching = ref(false);

// Default User Location (will try to get real location)
const center = ref([-6.2088, 106.8456]); // Jakarta
const address = ref('');
const addressDetails = ref({});

const initMap = () => {
    if (!mapContainer.value) return;

    // Fix Leaflet Icon Issue in Webpack/Vite
    delete L.Icon.Default.prototype._getIconUrl;
    L.Icon.Default.mergeOptions({
        iconRetinaUrl: new URL('leaflet/dist/images/marker-icon-2x.png', import.meta.url).href,
        iconUrl: new URL('leaflet/dist/images/marker-icon.png', import.meta.url).href,
        shadowUrl: new URL('leaflet/dist/images/marker-shadow.png', import.meta.url).href,
    });

    map.value = L.map(mapContainer.value).setView(center.value, 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map.value);

    // Add Marker
    marker.value = L.marker(center.value, { draggable: true }).addTo(map.value);

    // Event: Marker Drag End
    marker.value.on('dragend', async (event) => {
        const position = marker.value.getLatLng();
        await reverseGeocode(position.lat, position.lng);
    });

    // Event: Map Click
    map.value.on('click', async (e) => {
        marker.value.setLatLng(e.latlng);
        await reverseGeocode(e.latlng.lat, e.latlng.lng);
    });
    
    // Try to get user location ONLY IF no initial address
    if (!props.initialAddress && navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(position => {
            const { latitude, longitude } = position.coords;
            const latLng = [latitude, longitude];
            map.value.setView(latLng, 15);
            marker.value.setLatLng(latLng);
            reverseGeocode(latitude, longitude);
        });
    }
};

const reverseGeocode = async (lat, lng) => {
    try {
        const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`);
        const data = await response.json();
        
        address.value = data.display_name;
        addressDetails.value = {
            address: data.display_name,
            latitude: lat,
            longitude: lng,
            city: data.address.city || data.address.town || data.address.village || '',
            state: data.address.state || '',
            postal_code: data.address.postcode || '',
            country: data.address.country || '', // Just in case
            full_details: data.address
        };
    } catch (error) {
        console.error('Geocoding error:', error);
        address.value = 'Failed to fetch address';
    }
};

const searchLocation = async (autoSelect = false) => {
    if (!searchQuery.value) return;
    
    isSearching.value = true;
    
    // Helper function for fetching
    const performSearch = async (query) => {
        try {
            const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`);
            return await response.json();
        } catch (e) {
            console.error("Fetch error for query:", query, e);
            return [];
        }
    };

    try {
        // 1. Try exact search
        let data = await performSearch(searchQuery.value);
        
        // 2. Fallback: If no results & auto-selecting, try simplifying the address (take last 2 parts usually city/state)
        if (autoSelect && data.length === 0 && searchQuery.value.includes(',')) {
            const parts = searchQuery.value.split(',').filter(p => p.trim().length > 0);
            
            // Try last part (usually zip/country) + second last (city/state)
            if (parts.length >= 2) {
                const simpleQuery = parts.slice(-2).join(', ');
                console.log("Retrying with simple query:", simpleQuery);
                data = await performSearch(simpleQuery);
            } 
            // If still failing or only 1 part valuable, try just the last part
            if (data.length === 0 && parts.length > 0) {
                 const lastPart = parts[parts.length - 1];
                 console.log("Retrying with last part:", lastPart);
                 data = await performSearch(lastPart);
            }
        }

        searchResults.value = data;

        // Auto select first result if requested
        if (autoSelect && data.length > 0) {
            selectResult(data[0]);
        }
    } catch (error) {
        console.error('Search error:', error);
    } finally {
        isSearching.value = false;
    }
};

const selectResult = (result) => {
    const lat = parseFloat(result.lat);
    const lon = parseFloat(result.lon);
    const latLng = [lat, lon];
    
    map.value.setView(latLng, 16);
    marker.value.setLatLng(latLng);
    reverseGeocode(lat, lon);
    
    searchResults.value = [];
    // Keep search query to what user typed or initial for context, don't clear it
};

const confirmSelection = () => {
    emit('confirm', addressDetails.value);
    emit('close');
};

onMounted(() => {
    // initMap handled by watcher
});

onBeforeUnmount(() => {
    if (map.value) {
        map.value.remove();
    }
});
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[60] overflow-y-auto px-4 py-6 sm:px-0 flex items-center justify-center">
        <!-- Backdrop -->
        <div class="fixed inset-0 transform transition-all" @click="$emit('close')">
            <div class="absolute inset-0 bg-slate-900/75 backdrop-blur-sm"></div>
        </div>

        <!-- Modal Content -->
        <div class="relative bg-slate-900 rounded-2xl border border-slate-700 shadow-2xl w-full max-w-4xl overflow-hidden transform transition-all h-[90vh] flex flex-col">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-slate-700 flex items-center justify-between bg-slate-800/50">
                <h3 class="text-lg font-bold text-white">Pick Location from Map</h3>
                <button @click="$emit('close')" class="text-slate-400 hover:text-white transition-colors">
                    <XMarkIcon class="h-6 w-6" />
                </button>
            </div>

            <!-- Toolbar -->
            <div class="p-4 bg-slate-800/30 flex gap-4 border-b border-slate-700 z-[1000] relative">
                <div class="relative flex-1">
                    <input 
                        v-model="searchQuery" 
                        @keyup.enter="searchLocation"
                        type="text" 
                        placeholder="Search for a location (e.g. Jakarta, Monas)..." 
                        class="w-full bg-slate-900 border-slate-700 rounded-xl py-2.5 pl-4 pr-10 text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50"
                    />
                    <button 
                        @click="searchLocation"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-blue-400 p-1"
                    >
                        <MagnifyingGlassIcon class="h-5 w-5" />
                    </button>

                    <!-- Search Results -->
                    <div v-if="searchResults.length > 0" class="absolute top-full left-0 right-0 mt-2 bg-slate-800 border border-slate-700 rounded-xl shadow-xl overflow-hidden max-h-60 overflow-y-auto z-[2000]">
                        <button 
                            v-for="result in searchResults" 
                            :key="result.place_id"
                            @click="selectResult(result)"
                            class="w-full text-left px-4 py-3 text-sm text-slate-300 hover:bg-slate-700 hover:text-white border-b border-slate-700 last:border-0 transition-colors"
                        >
                            {{ result.display_name }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Map Container -->
            <div class="flex-1 relative bg-slate-800">
                <div ref="mapContainer" class="absolute inset-0 z-0"></div>
                
                <!-- Selected Address Overlay -->
                <div class="absolute bottom-6 left-6 right-6 bg-slate-900/90 backdrop-blur border border-slate-700 p-4 rounded-xl z-[500] shadow-lg">
                    <p class="text-xs text-slate-400 mb-1 font-bold uppercase tracking-wider">Selected Address</p>
                    <p class="text-sm text-white font-medium break-words leading-relaxed">
                        {{ address || 'Drag marker or click map to select location...' }}
                    </p>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-slate-700 bg-slate-800/50 flex justify-end gap-3 z-[1000] relative">
                <button 
                    @click="$emit('close')"
                    class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 hover:text-white hover:bg-slate-700 font-medium transition-colors border border-slate-700"
                >
                    Cancel
                </button>
                <button 
                    @click="confirmSelection"
                    :disabled="!address"
                    class="px-6 py-2 rounded-xl bg-blue-600 text-white hover:bg-blue-500 font-bold shadow-lg shadow-blue-500/20 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                >
                    Use This Location
                </button>
            </div>
        </div>
    </div>
</template>

<style>
/* Leaflet dark mode overrides if needed, or just let it be standard tiles */
.leaflet-container {
    background: #1e293b;
}
</style>
