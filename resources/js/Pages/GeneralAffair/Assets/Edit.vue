<script setup>
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ArrowLeftIcon, PhotoIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    asset: Object,
    locations: Array,
    users: Array,
});

const form = useForm({
    _method: 'PUT',
    asset_code: props.asset.asset_code,
    name: props.asset.name,
    category: props.asset.category || '',
    purchase_date: props.asset.purchase_date || '',
    price: props.asset.price || '',
    condition: props.asset.condition || 'Baik',
    location: props.asset.location || '',
    ga_location_id: props.asset.ga_location_id || '',
    pos_x: props.asset.pos_x || '',
    pos_y: props.asset.pos_y || '',
    user_id: props.asset.user_id || '',
    status: props.asset.status || 'active',
    image: null,
});

const handleFileChange = (e) => {
    form.image = e.target.files[0];
};

const selectedLocation = computed(() => {
    return props.locations.find(l => l.id === form.ga_location_id);
});

const handleMapClick = (e) => {
    if (!selectedLocation.value || !selectedLocation.value.map_background_url) return;
    const rect = e.target.getBoundingClientRect();
    const x = ((e.clientX - rect.left) / rect.width) * 100;
    const y = ((e.clientY - rect.top) / rect.height) * 100;
    form.pos_x = x.toFixed(2);
    form.pos_y = y.toFixed(2);
};

const submit = () => {
    form.post(route('ga.assets.update', props.asset.id));
};
</script>

<template>
    <Head title="Edit Aset (GA)" />

    <AppLayout title="Edit Aset">
        <div class="mx-auto max-w-4xl">
            <div class="mb-6 flex items-center gap-4">
                <Link :href="route('ga.assets.index')" class="rounded-full p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800">
                    <ArrowLeftIcon class="h-5 w-5" />
                </Link>
                <h2 class="text-xl font-black text-slate-900 dark:text-white">Edit Aset: {{ asset.name }}</h2>
            </div>

            <div class="glass-card rounded-3xl p-6 shadow-sm">
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <!-- Left Column -->
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Kode Aset <span class="text-red-500">*</span></label>
                                <input v-model="form.asset_code" type="text" required class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white" />
                                <div v-if="form.errors.asset_code" class="mt-1 text-xs text-red-500">{{ form.errors.asset_code }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Nama Aset <span class="text-red-500">*</span></label>
                                <input v-model="form.name" type="text" required class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white" />
                                <div v-if="form.errors.name" class="mt-1 text-xs text-red-500">{{ form.errors.name }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Kategori</label>
                                <select v-model="form.category" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                                    <option value="">-- Pilih --</option>
                                    <option value="Elektronik">Elektronik</option>
                                    <option value="Furnitur">Furnitur</option>
                                    <option value="Kendaraan">Kendaraan</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Tgl Beli</label>
                                    <input v-model="form.purchase_date" type="date" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Harga (Rp)</label>
                                    <input v-model="form.price" type="number" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white" />
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Kondisi</label>
                                <select v-model="form.condition" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                                    <option value="Baik">Baik</option>
                                    <option value="Rusak Ringan">Rusak Ringan</option>
                                    <option value="Rusak Berat">Rusak Berat</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Denah Lokasi (Mapping)</label>
                                <select v-model="form.ga_location_id" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                                    <option value="">-- Pilih Denah --</option>
                                    <option v-for="loc in locations" :key="loc.id" :value="loc.id">{{ loc.name }}</option>
                                </select>
                            </div>

                            <div v-if="selectedLocation && selectedLocation.map_background_url" class="mt-4">
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Klik pada peta untuk menetapkan posisi aset</label>
                                <div class="relative w-full overflow-hidden rounded-xl border border-slate-300 bg-slate-100 dark:border-slate-700" style="padding-top: 56.25%;">
                                    <img :src="selectedLocation.map_background_url" @click="handleMapClick" class="absolute inset-0 h-full w-full object-cover opacity-80 cursor-crosshair" />
                                    
                                    <div 
                                        v-if="form.pos_x && form.pos_y"
                                        class="absolute h-6 w-6 -ml-3 -mt-6 text-red-500 pointer-events-none drop-shadow-md"
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
                                    <button type="button" @click="form.pos_x = ''; form.pos_y = ''" class="text-red-500 hover:underline">Hapus Pin</button>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Detail Lokasi (Teks)</label>
                                <input v-model="form.location" type="text" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Penanggung Jawab (PIC)</label>
                                <select v-model="form.user_id" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                                    <option value="">-- Pilih --</option>
                                    <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Status</label>
                                <select v-model="form.status" required class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                                    <option value="active">Active</option>
                                    <option value="maintenance">Maintenance</option>
                                    <option value="disposed">Disposed (Dibuang/Dijual)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex gap-6 border-t border-slate-200 pt-6 dark:border-slate-800">
                        <div class="w-32 h-32 flex-shrink-0 overflow-hidden rounded-xl border border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800">
                            <img v-if="asset.image_url" :src="asset.image_url" class="h-full w-full object-cover" />
                            <div v-else class="flex h-full w-full items-center justify-center text-slate-400">
                                <PhotoIcon class="h-10 w-10" />
                            </div>
                        </div>
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Ubah Foto Aset</label>
                            <input type="file" @change="handleFileChange" accept="image/*" class="mt-2 block w-full text-sm text-slate-500 file:mr-4 file:rounded-xl file:border-0 file:bg-cyan-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-cyan-700 hover:file:bg-cyan-100" />
                            <p class="mt-1 text-xs text-slate-500">Biarkan kosong jika tidak ingin mengubah foto.</p>
                            <div v-if="form.errors.image" class="mt-1 text-xs text-red-500">{{ form.errors.image }}</div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-slate-200 pt-6 dark:border-slate-800">
                        <Link :href="route('ga.assets.index')" class="rounded-xl border border-slate-300 px-6 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">Batal</Link>
                        <button type="submit" :disabled="form.processing" class="rounded-xl bg-cyan-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-cyan-500 disabled:opacity-50">
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
