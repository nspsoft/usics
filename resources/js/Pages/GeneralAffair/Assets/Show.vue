<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    ArrowLeftIcon, 
    PencilSquareIcon,
    MapPinIcon,
    UserCircleIcon,
    CalendarDaysIcon,
    CurrencyDollarIcon,
    CubeIcon,
    TagIcon,
    PhotoIcon
} from '@heroicons/vue/24/outline';
import moment from 'moment';

const props = defineProps({
    asset: Object,
});
</script>

<template>
    <Head :title="`Detail Aset: ${asset.name}`" />

    <AppLayout title="Detail Aset">
        <div class="mx-auto max-w-5xl">
            <div class="mb-6 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="route('ga.assets.index')" class="rounded-full p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800">
                        <ArrowLeftIcon class="h-5 w-5" />
                    </Link>
                    <div>
                        <h2 class="text-xl font-black text-slate-900 dark:text-white">{{ asset.name }}</h2>
                        <p class="text-sm text-slate-500">{{ asset.asset_code }}</p>
                    </div>
                </div>
                <Link :href="route('ga.assets.edit', asset.id)" class="inline-flex items-center gap-2 rounded-xl bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                    <PencilSquareIcon class="h-4 w-4" />
                    Edit Aset
                </Link>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Info Kolom Kiri -->
                <div class="lg:col-span-1 space-y-6">
                    <div class="glass-card overflow-hidden rounded-3xl shadow-sm">
                        <div class="aspect-w-4 aspect-h-3 relative w-full bg-slate-100 dark:bg-slate-800">
                            <img v-if="asset.image_url" :src="asset.image_url" class="absolute inset-0 h-full w-full object-cover" />
                            <div v-else class="flex h-full w-full items-center justify-center text-slate-400">
                                <PhotoIcon class="h-16 w-16" />
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="flex items-center justify-between">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                    :class="{
                                        'bg-emerald-100 text-emerald-800': asset.status === 'active',
                                        'bg-amber-100 text-amber-800': asset.status === 'maintenance',
                                        'bg-slate-100 text-slate-800': asset.status === 'disposed',
                                    }"
                                >
                                    {{ asset.status.toUpperCase() }}
                                </span>
                                <span class="text-sm font-medium text-slate-500">{{ asset.condition }}</span>
                            </div>
                            
                            <dl class="mt-6 space-y-4 text-sm">
                                <div class="flex items-center gap-3">
                                    <TagIcon class="h-5 w-5 text-slate-400" />
                                    <dt class="w-24 text-slate-500">Kategori</dt>
                                    <dd class="font-medium text-slate-900 dark:text-white">{{ asset.category || '-' }}</dd>
                                </div>
                                <div class="flex items-center gap-3">
                                    <UserCircleIcon class="h-5 w-5 text-slate-400" />
                                    <dt class="w-24 text-slate-500">PIC</dt>
                                    <dd class="font-medium text-slate-900 dark:text-white">{{ asset.pic?.name || '-' }}</dd>
                                </div>
                                <div class="flex items-center gap-3">
                                    <CalendarDaysIcon class="h-5 w-5 text-slate-400" />
                                    <dt class="w-24 text-slate-500">Tgl Beli</dt>
                                    <dd class="font-medium text-slate-900 dark:text-white">{{ asset.purchase_date ? moment(asset.purchase_date).format('DD MMM YYYY') : '-' }}</dd>
                                </div>
                                <div class="flex items-center gap-3">
                                    <CurrencyDollarIcon class="h-5 w-5 text-slate-400" />
                                    <dt class="w-24 text-slate-500">Harga</dt>
                                    <dd class="font-medium text-slate-900 dark:text-white">Rp {{ asset.price ? Number(asset.price).toLocaleString('id-ID') : '-' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>

                <!-- Info Kolom Kanan (Map & Logs) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Mapping Location -->
                    <div class="glass-card rounded-3xl p-6 shadow-sm">
                        <h3 class="mb-4 text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <MapPinIcon class="h-5 w-5 text-cyan-600" />
                            Lokasi & Denah
                        </h3>
                        <p class="text-sm text-slate-700 dark:text-slate-300 mb-4">
                            <strong>Deskripsi Lokasi:</strong> {{ asset.location || '-' }}
                            <br>
                            <strong>Denah:</strong> {{ asset.ga_location?.name || 'Tidak ada denah terkait' }}
                        </p>
                        
                        <div v-if="asset.ga_location?.map_background_url" class="relative w-full overflow-hidden rounded-xl border border-slate-200 bg-slate-100 dark:border-slate-800" style="padding-top: 56.25%;">
                            <img :src="asset.ga_location.map_background_url" class="absolute inset-0 h-full w-full object-cover opacity-80" />
                            
                            <!-- Marker (Hanya ilustrasi posisi jika pos_x pos_y di set) -->
                            <div 
                                v-if="asset.pos_x && asset.pos_y"
                                class="absolute h-6 w-6 -ml-3 -mt-6 text-red-500 drop-shadow-md"
                                :style="{ left: `${asset.pos_x}%`, top: `${asset.pos_y}%` }"
                                title="Posisi Aset"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path fill-rule="evenodd" d="M11.54 22.351l.07.04.028.016a.76.76 0 00.723 0l.028-.015.071-.041a16.975 16.975 0 001.144-.742 19.58 19.58 0 002.683-2.282c1.944-1.99 3.963-4.98 3.963-8.827a8.25 8.25 0 00-16.5 0c0 3.846 2.02 6.837 3.963 8.827a19.58 19.58 0 002.682 2.282 16.975 16.975 0 001.145.742zM12 13.5a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                        <div v-else class="rounded-xl border-2 border-dashed border-slate-200 p-8 text-center dark:border-slate-800">
                            <p class="text-sm text-slate-500">Belum ada pemetaan pada denah.</p>
                        </div>
                    </div>

                    <!-- Logs/History -->
                    <div class="glass-card rounded-3xl p-6 shadow-sm">
                        <h3 class="mb-4 text-lg font-bold text-slate-900 dark:text-white">Riwayat / Log Aset</h3>
                        <div class="flow-root">
                            <ul role="list" class="-mb-8">
                                <li v-for="(log, logIdx) in asset.logs" :key="log.id">
                                    <div class="relative pb-8">
                                        <span v-if="logIdx !== asset.logs.length - 1" class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-slate-200 dark:bg-slate-700" aria-hidden="true"></span>
                                        <div class="relative flex space-x-3">
                                            <div>
                                                <span class="h-8 w-8 rounded-full bg-slate-100 flex items-center justify-center ring-8 ring-white dark:bg-slate-800 dark:ring-slate-900">
                                                    <CubeIcon class="h-4 w-4 text-slate-500" />
                                                </span>
                                            </div>
                                            <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                                <div>
                                                    <p class="text-sm text-slate-500">
                                                        <span class="font-medium text-slate-900 dark:text-white">{{ log.user?.name || 'System' }}</span>
                                                        melakukan tindakan <span class="font-semibold">{{ log.action }}</span>.
                                                    </p>
                                                    <p class="mt-1 text-sm text-slate-700 dark:text-slate-300">{{ log.notes }}</p>
                                                </div>
                                                <div class="whitespace-nowrap text-right text-xs text-slate-500">
                                                    {{ moment(log.created_at).format('DD MMM YYYY, HH:mm') }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li v-if="asset.logs.length === 0">
                                    <p class="text-sm text-slate-500">Belum ada riwayat aktivitas.</p>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
