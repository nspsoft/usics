<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    PlusIcon, 
    PencilSquareIcon, 
    TrashIcon,
    MagnifyingGlassIcon,
    CubeIcon,
    MapPinIcon
} from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';
import debounce from 'lodash/debounce';

const props = defineProps({
    assets: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const category = ref(props.filters.category || '');

watch([search, category], debounce(() => {
    router.get(
        route('ga.assets.index'),
        { search: search.value, category: category.value },
        { preserveState: true, preserveScroll: true, replace: true }
    );
}, 300));

const deleteAsset = (asset) => {
    Swal.fire({
        title: 'Hapus Aset?',
        text: `Data aset ${asset.name} akan dihapus permanen.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('ga.assets.destroy', asset.id));
        }
    });
};
</script>

<template>
    <Head title="Manajemen Aset (GA)" />

    <AppLayout title="Manajemen Aset">
        <div class="glass-card rounded-3xl p-6 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white">Manajemen Aset</h2>
                    <p class="text-sm text-slate-500">Daftar semua aset fisik perusahaan</p>
                </div>
                <div class="flex gap-2">
                    <Link 
                        :href="route('ga.assets.create')"
                        class="inline-flex items-center gap-2 rounded-xl bg-cyan-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-cyan-500 focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2"
                    >
                        <PlusIcon class="h-4 w-4" />
                        Tambah Aset
                    </Link>
                </div>
            </div>

            <!-- Filters -->
            <div class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-center">
                <div class="relative max-w-sm flex-1">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <MagnifyingGlassIcon class="h-5 w-5 text-slate-400" />
                    </div>
                    <input 
                        v-model="search" 
                        type="text" 
                        class="block w-full rounded-xl border-slate-300 pl-10 focus:border-cyan-500 focus:ring-cyan-500 sm:text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white" 
                        placeholder="Cari kode / nama aset..." 
                    />
                </div>
                <select 
                    v-model="category"
                    class="block w-full rounded-xl border-slate-300 focus:border-cyan-500 focus:ring-cyan-500 sm:max-w-xs sm:text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                >
                    <option value="">Semua Kategori</option>
                    <option value="Elektronik">Elektronik</option>
                    <option value="Furnitur">Furnitur</option>
                    <option value="Kendaraan">Kendaraan</option>
                    <option value="Lainnya">Lainnya</option>
                </select>
            </div>

            <!-- Table -->
            <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 shadow-sm dark:border-slate-800">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                    <thead class="bg-slate-50 dark:bg-slate-900/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Aset</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Kategori</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Lokasi / Penanggung Jawab</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-900">
                        <tr v-for="asset in assets.data" :key="asset.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <td class="whitespace-nowrap px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 flex-shrink-0 overflow-hidden rounded-lg bg-slate-100 dark:bg-slate-800">
                                        <img v-if="asset.image_url" :src="asset.image_url" class="h-full w-full object-cover" />
                                        <CubeIcon v-else class="m-2 h-6 w-6 text-slate-400" />
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900 dark:text-white">{{ asset.name }}</div>
                                        <div class="text-xs text-slate-500">{{ asset.asset_code }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4 text-sm text-slate-700 dark:text-slate-300">
                                {{ asset.category || '-' }}
                            </td>
                            <td class="px-4 py-4 text-sm text-slate-700 dark:text-slate-300">
                                <div class="flex items-center gap-1 font-medium">
                                    <MapPinIcon class="h-4 w-4 text-slate-400" />
                                    {{ asset.ga_location?.name || asset.location || '-' }}
                                </div>
                                <div class="text-xs text-slate-500 mt-0.5">PIC: {{ asset.pic?.name || '-' }}</div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold"
                                    :class="{
                                        'bg-emerald-100 text-emerald-800': asset.status === 'active',
                                        'bg-amber-100 text-amber-800': asset.status === 'maintenance',
                                        'bg-slate-100 text-slate-800': asset.status === 'disposed',
                                    }"
                                >
                                    {{ asset.status.toUpperCase() }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4 text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    <Link :href="route('ga.assets.show', asset.id)" class="text-cyan-600 hover:text-cyan-900 dark:text-cyan-400 dark:hover:text-cyan-300">Detail</Link>
                                    <Link :href="route('ga.assets.edit', asset.id)" class="text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-300"><PencilSquareIcon class="h-5 w-5"/></Link>
                                    <button @click="deleteAsset(asset)" class="text-red-500 hover:text-red-700"><TrashIcon class="h-5 w-5"/></button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="assets.data.length === 0">
                            <td colspan="5" class="py-8 text-center text-sm text-slate-500">
                                Belum ada data aset.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4 flex items-center justify-between" v-if="assets.links.length > 3">
                <div class="flex flex-1 justify-between sm:hidden">
                    <Link :href="assets.prev_page_url" class="relative inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Previous</Link>
                    <Link :href="assets.next_page_url" class="relative ml-3 inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Next</Link>
                </div>
                <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-slate-700 dark:text-slate-300">
                            Showing <span class="font-medium">{{ assets.from || 0 }}</span> to <span class="font-medium">{{ assets.to || 0 }}</span> of <span class="font-medium">{{ assets.total }}</span> results
                        </p>
                    </div>
                    <div>
                        <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                            <Link v-for="(link, i) in assets.links" :key="i" :href="link.url" v-html="link.label" 
                                class="relative inline-flex items-center px-4 py-2 text-sm font-semibold focus:z-20 border"
                                :class="[
                                    link.active ? 'z-10 bg-cyan-600 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cyan-600 border-cyan-600' : 'text-slate-900 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 dark:text-slate-300 dark:border-slate-700 dark:hover:bg-slate-800',
                                    !link.url ? 'opacity-50 cursor-not-allowed' : ''
                                ]"
                            />
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
