<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    PlusIcon, 
    PencilSquareIcon, 
    TrashIcon, 
    PhotoIcon 
} from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';

const props = defineProps({
    locations: Array
});

const isModalOpen = ref(false);
const editingLocation = ref(null);

const form = useForm({
    name: '',
    description: '',
    map_background: null,
});

const openModal = (loc = null) => {
    editingLocation.value = loc;
    if (loc) {
        form.name = loc.name;
        form.description = loc.description || '';
        form.map_background = null;
    } else {
        form.reset();
    }
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
};

const handleFileChange = (e) => {
    form.map_background = e.target.files[0];
};

const saveLocation = () => {
    if (editingLocation.value) {
        // Inertia doesn't support PUT with FormData, so we use POST with _method=PUT
        form.transform((data) => ({
            ...data,
            _method: 'PUT',
        })).post(route('ga.locations.update', editingLocation.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('ga.locations.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteLocation = (loc) => {
    Swal.fire({
        title: 'Hapus Denah/Lokasi?',
        text: `Data ${loc.name} akan dihapus secara permanen.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('ga.locations.destroy', loc.id));
        }
    });
};
</script>

<template>
    <Head title="Denah & Lokasi (GA)" />

    <AppLayout title="Denah & Lokasi">
        <div class="glass-card rounded-3xl p-6 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white">Denah & Lokasi</h2>
                    <p class="text-sm text-slate-500">Kelola daftar ruangan/lokasi dan denahnya</p>
                </div>
                <button 
                    @click="openModal()" 
                    class="inline-flex items-center gap-2 rounded-xl bg-cyan-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-cyan-500 focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2"
                >
                    <PlusIcon class="h-4 w-4" />
                    Tambah Lokasi
                </button>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <div 
                    v-for="loc in locations" 
                    :key="loc.id"
                    class="group relative flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="relative h-48 w-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                        <img v-if="loc.map_background_url" :src="loc.map_background_url" class="absolute inset-0 h-full w-full object-cover opacity-80" />
                        <PhotoIcon v-else class="h-12 w-12 text-slate-300" />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                        <div class="absolute bottom-3 left-4 right-4 flex items-end justify-between text-white">
                            <div>
                                <h3 class="font-bold text-lg">{{ loc.name }}</h3>
                                <p class="text-xs text-slate-200 line-clamp-1">{{ loc.description || 'Tidak ada deskripsi' }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between border-t border-slate-100 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-900/50">
                        <span class="text-xs font-medium text-slate-500">ID: {{ loc.id }}</span>
                        <div class="flex gap-2">
                            <button @click="openModal(loc)" class="p-1.5 text-slate-400 hover:text-cyan-600">
                                <PencilSquareIcon class="h-5 w-5" />
                            </button>
                            <button @click="deleteLocation(loc)" class="p-1.5 text-slate-400 hover:text-red-500">
                                <TrashIcon class="h-5 w-5" />
                            </button>
                        </div>
                    </div>
                </div>
                
                <div v-if="locations.length === 0" class="col-span-full rounded-2xl border-2 border-dashed border-slate-200 p-12 text-center dark:border-slate-800">
                    <PhotoIcon class="mx-auto h-10 w-10 text-slate-300" />
                    <h3 class="mt-2 text-sm font-semibold text-slate-900 dark:text-white">Tidak ada denah</h3>
                    <p class="mt-1 text-sm text-slate-500">Mulai dengan menambahkan denah/lokasi baru.</p>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-3xl bg-white p-6 shadow-xl dark:bg-slate-900">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                    {{ editingLocation ? 'Edit Lokasi' : 'Tambah Lokasi Baru' }}
                </h3>
                <form @submit.prevent="saveLocation" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Nama Lokasi</label>
                        <input v-model="form.name" type="text" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Deskripsi</label>
                        <textarea v-model="form.description" rows="2" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Gambar Denah (Opsional)</label>
                        <input type="file" @change="handleFileChange" accept="image/*" class="mt-1 block w-full text-sm text-slate-500 file:mr-4 file:rounded-xl file:border-0 file:bg-cyan-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-cyan-700 hover:file:bg-cyan-100" />
                        <p class="mt-1 text-xs text-slate-500">Format: JPG, PNG. Maks 5MB.</p>
                    </div>
                    
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="closeModal" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">Batal</button>
                        <button type="submit" :disabled="form.processing" class="rounded-xl bg-cyan-600 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-500 disabled:opacity-50">
                            {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
