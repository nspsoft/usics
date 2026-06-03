<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    locations: Array,
    assets: Array,
    users: Array,
});

const form = useForm({
    title: '',
    description: '',
    category: 'facility',
    priority: 'medium',
    ga_location_id: '',
    ga_asset_id: '',
    image: null,
    pos_x: '',
    pos_y: '',
});

// Reactively filter assets based on selected location
const filteredAssets = computed(() => {
    if (!form.ga_location_id) return [];
    return props.assets.filter(a => a.ga_location_id === form.ga_location_id);
});

// Reset asset and position if location changes
watch(() => form.ga_location_id, () => {
    form.ga_asset_id = '';
    form.pos_x = '';
    form.pos_y = '';
});

// Get current selected location
const selectedLocation = computed(() => {
    return props.locations.find(l => l.id === form.ga_location_id);
});

// Get current selected asset
const selectedAsset = computed(() => {
    return props.assets.find(a => a.id === form.ga_asset_id);
});

// Automate pin position from selected asset or enable manual clicking
watch(() => form.ga_asset_id, (newAssetId) => {
    if (newAssetId) {
        const asset = props.assets.find(a => a.id === newAssetId);
        if (asset && asset.pos_x !== null && asset.pos_y !== null) {
            form.pos_x = asset.pos_x;
            form.pos_y = asset.pos_y;
        } else {
            form.pos_x = '';
            form.pos_y = '';
        }
    } else {
        form.pos_x = '';
        form.pos_y = '';
    }
});

const handleFileChange = (e) => {
    form.image = e.target.files[0];
};

const handleMapClick = (e) => {
    // If an asset is selected, the pin location is locked to the asset's coordinate
    if (form.ga_asset_id) return;
    if (!selectedLocation.value || !selectedLocation.value.map_background_url) return;
    
    const rect = e.target.getBoundingClientRect();
    const x = ((e.clientX - rect.left) / rect.width) * 100;
    const y = ((e.clientY - rect.top) / rect.height) * 100;
    form.pos_x = x.toFixed(2);
    form.pos_y = y.toFixed(2);
};

const submit = () => {
    form.post(route('ga.tickets.store'));
};
</script>

<template>
    <Head title="Buat Tiket Baru (GA)" />

    <AppLayout title="Buat Tiket">
        <div class="mx-auto max-w-4xl">
            <div class="mb-6 flex items-center gap-4">
                <Link :href="route('ga.tickets.index')" class="rounded-full p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800">
                    <ArrowLeftIcon class="h-5 w-5" />
                </Link>
                <h2 class="text-xl font-black text-slate-900 dark:text-white">Buat Tiket Layanan Baru</h2>
            </div>

            <div class="glass-card rounded-3xl p-6 shadow-sm">
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <!-- Left Column: Basic Details -->
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Judul Laporan / Kerusakan <span class="text-red-500">*</span></label>
                                <input v-model="form.title" type="text" required class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white" placeholder="Contoh: AC Bocor Air / Lampu Padam" />
                                <div v-if="form.errors.title" class="mt-1 text-xs text-red-500">{{ form.errors.title }}</div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Deskripsi Detail Masalah <span class="text-red-500">*</span></label>
                                <textarea v-model="form.description" rows="4" required class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white" placeholder="Jelaskan secara rinci kondisi kerusakan dan tindakan yang dibutuhkan..."></textarea>
                                <div v-if="form.errors.description" class="mt-1 text-xs text-red-500">{{ form.errors.description }}</div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Kategori <span class="text-red-500">*</span></label>
                                    <select v-model="form.category" required class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                                        <option value="facility">Fasilitas (Building)</option>
                                        <option value="cleaning">Kebersihan</option>
                                        <option value="it_support">Dukungan IT</option>
                                        <option value="security">Keamanan</option>
                                        <option value="other">Lainnya</option>
                                    </select>
                                    <div v-if="form.errors.category" class="mt-1 text-xs text-red-500">{{ form.errors.category }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Prioritas <span class="text-red-500">*</span></label>
                                    <select v-model="form.priority" required class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                                        <option value="low">Low</option>
                                        <option value="medium">Medium</option>
                                        <option value="high">High</option>
                                        <option value="critical">Critical</option>
                                    </select>
                                    <div v-if="form.errors.priority" class="mt-1 text-xs text-red-500">{{ form.errors.priority }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Location & Mapping -->
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Denah Lokasi Kejadian</label>
                                <select v-model="form.ga_location_id" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                                    <option value="">-- Pilih Denah --</option>
                                    <option v-for="loc in locations" :key="loc.id" :value="loc.id">{{ loc.name }}</option>
                                </select>
                                <div v-if="form.errors.ga_location_id" class="mt-1 text-xs text-red-500">{{ form.errors.ga_location_id }}</div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Tautkan ke Aset Spesifik (Opsional)</label>
                                <select v-model="form.ga_asset_id" :disabled="!form.ga_location_id" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 disabled:bg-slate-100 disabled:cursor-not-allowed dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:disabled:bg-slate-900">
                                    <option value="">-- Non-Aset / Lainnya --</option>
                                    <option v-for="asset in filteredAssets" :key="asset.id" :value="asset.id">{{ asset.name }} ({{ asset.asset_code }})</option>
                                </select>
                                <p v-if="!form.ga_location_id" class="text-xs text-amber-500 mt-1">Pilih lokasi terlebih dahulu untuk memfilter aset.</p>
                                <div v-if="form.errors.ga_asset_id" class="mt-1 text-xs text-red-500">{{ form.errors.ga_asset_id }}</div>
                            </div>

                            <!-- Interactive Floor Plan Map -->
                            <div v-if="selectedLocation && selectedLocation.map_background_url" class="mt-4">
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    <span v-if="form.ga_asset_id">Lokasi terdeteksi dari posisi Aset (Terkunci)</span>
                                    <span v-else>Klik pada peta untuk menandai titik kerusakan</span>
                                </label>
                                <div class="relative w-full overflow-hidden rounded-xl border border-slate-300 bg-slate-100 dark:border-slate-700" style="padding-top: 56.25%;">
                                    <img 
                                        :src="selectedLocation.map_background_url" 
                                        @click="handleMapClick" 
                                        class="absolute inset-0 h-full w-full object-cover opacity-80"
                                        :class="form.ga_asset_id ? 'cursor-default' : 'cursor-crosshair'"
                                    />
                                    
                                    <!-- Pin Marker -->
                                    <div 
                                        v-if="form.pos_x && form.pos_y"
                                        class="absolute h-6 w-6 -ml-3 -mt-6 pointer-events-none drop-shadow-md"
                                        :class="form.ga_asset_id ? 'text-cyan-500' : 'text-red-500'"
                                        :style="{ left: `${form.pos_x}%`, top: `${form.pos_y}%` }"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                            <path fill-rule="evenodd" d="M11.54 22.351l.07.04.028.016a.76.76 0 00.723 0l.028-.015.071-.041a16.975 16.975 0 001.144-.742 19.58 19.58 0 002.683-2.282c1.944-1.99 3.963-4.98 3.963-8.827a8.25 8.25 0 00-16.5 0c0 3.846 2.02 6.837 3.963 8.827a19.58 19.58 0 002.682 2.282 16.975 16.975 0 001.145.742zM12 13.5a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="mt-2 text-xs text-slate-500 flex justify-between">
                                    <span>X: {{ form.pos_x || '-' }}%</span>
                                    <span>Y: {{ form.pos_y || '-' }}%</span>
                                    <button 
                                        v-if="!form.ga_asset_id && form.pos_x" 
                                        type="button" 
                                        @click="form.pos_x = ''; form.pos_y = ''" 
                                        class="text-red-500 hover:underline"
                                    >
                                        Hapus Pin
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- File Upload -->
                    <div class="border-t border-slate-200 pt-6 dark:border-slate-800">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Foto Bukti Kerusakan / Kondisi Fisik</label>
                        <input type="file" @change="handleFileChange" accept="image/*" class="mt-2 block w-full text-sm text-slate-500 file:mr-4 file:rounded-xl file:border-0 file:bg-cyan-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-cyan-700 hover:file:bg-cyan-100" />
                        <div v-if="form.errors.image" class="mt-1 text-xs text-red-500">{{ form.errors.image }}</div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 border-t border-slate-200 pt-6 dark:border-slate-800">
                        <Link :href="route('ga.tickets.index')" class="rounded-xl border border-slate-300 px-6 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">Batal</Link>
                        <button type="submit" :disabled="form.processing" class="rounded-xl bg-cyan-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-cyan-500 disabled:opacity-50">
                            {{ form.processing ? 'Mengirim...' : 'Kirim Tiket' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
