<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    PlusIcon, 
    PencilSquareIcon, 
    TrashIcon,
    MagnifyingGlassIcon,
    ClipboardDocumentListIcon,
    MapPinIcon,
    CubeIcon,
    ClockIcon,
    UserIcon
} from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';
import debounce from 'lodash/debounce';

const props = defineProps({
    tickets: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const category = ref(props.filters.category || '');
const priority = ref(props.filters.priority || '');
const status = ref(props.filters.status || '');

watch([search, category, priority, status], debounce(() => {
    router.get(
        route('ga.tickets.index'),
        { 
            search: search.value, 
            category: category.value,
            priority: priority.value,
            status: status.value
        },
        { preserveState: true, preserveScroll: true, replace: true }
    );
}, 300));

const deleteTicket = (ticket) => {
    Swal.fire({
        title: 'Hapus Tiket?',
        text: `Tiket ${ticket.ticket_code} akan dihapus permanen.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('ga.tickets.destroy', ticket.id));
        }
    });
};
</script>

<template>
    <Head title="Tiket Layanan (GA)" />

    <AppLayout title="Tiket Layanan">
        <div class="glass-card rounded-3xl p-6 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white">Tiket Layanan GA</h2>
                    <p class="text-sm text-slate-500">Kelola aduan kerusakan, kebersihan, dan fasilitas umum</p>
                </div>
                <div class="flex gap-2">
                    <Link 
                        :href="route('ga.tickets.create')"
                        class="inline-flex items-center gap-2 rounded-xl bg-cyan-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-cyan-500 focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2"
                    >
                        <PlusIcon class="h-4 w-4" />
                        Buat Tiket Baru
                    </Link>
                </div>
            </div>

            <!-- Filters -->
            <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4">
                <!-- Search -->
                <div class="relative flex-1">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <MagnifyingGlassIcon class="h-5 w-5 text-slate-400" />
                    </div>
                    <input 
                        v-model="search" 
                        type="text" 
                        class="block w-full rounded-xl border-slate-300 pl-10 focus:border-cyan-500 focus:ring-cyan-500 sm:text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white" 
                        placeholder="Cari kode / judul / deskripsi..." 
                    />
                </div>

                <!-- Category -->
                <select 
                    v-model="category"
                    class="block w-full rounded-xl border-slate-300 focus:border-cyan-500 focus:ring-cyan-500 sm:text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                >
                    <option value="">Semua Kategori</option>
                    <option value="facility">Fasilitas (Building)</option>
                    <option value="cleaning">Kebersihan</option>
                    <option value="it_support">Dukungan IT</option>
                    <option value="security">Keamanan</option>
                    <option value="other">Lainnya</option>
                </select>

                <!-- Priority -->
                <select 
                    v-model="priority"
                    class="block w-full rounded-xl border-slate-300 focus:border-cyan-500 focus:ring-cyan-500 sm:text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                >
                    <option value="">Semua Prioritas</option>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="critical">Critical</option>
                </select>

                <!-- Status -->
                <select 
                    v-model="status"
                    class="block w-full rounded-xl border-slate-300 focus:border-cyan-500 focus:ring-cyan-500 sm:text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                >
                    <option value="">Semua Status</option>
                    <option value="open">Open (Baru)</option>
                    <option value="in_progress">In Progress</option>
                    <option value="resolved">Resolved</option>
                    <option value="closed">Closed</option>
                </select>
            </div>

            <!-- Table -->
            <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 shadow-sm dark:border-slate-800">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                    <thead class="bg-slate-50 dark:bg-slate-900/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Tiket</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Kategori & Prioritas</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Lokasi / Aset</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Pelapor / Teknisi</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-900">
                        <tr v-for="ticket in tickets.data" :key="ticket.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <!-- Ticket Info -->
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 flex-shrink-0 overflow-hidden rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                                        <img v-if="ticket.image_url" :src="ticket.image_url" class="h-full w-full object-cover" />
                                        <ClipboardDocumentListIcon v-else class="h-6 w-6 text-slate-400" />
                                    </div>
                                    <div class="max-w-xs truncate">
                                        <div class="font-bold text-slate-900 dark:text-white">{{ ticket.title }}</div>
                                        <div class="text-xs font-semibold text-slate-500">{{ ticket.ticket_code }}</div>
                                    </div>
                                </div>
                            </td>

                            <!-- Category & Priority -->
                            <td class="whitespace-nowrap px-4 py-4 text-sm">
                                <div class="text-slate-700 dark:text-slate-300 capitalize font-medium">{{ ticket.category }}</div>
                                <div class="mt-1">
                                    <span class="inline-flex rounded px-1.5 py-0.5 text-2xs font-semibold uppercase"
                                        :class="{
                                            'bg-slate-100 text-slate-800': ticket.priority === 'low',
                                            'bg-blue-100 text-blue-800': ticket.priority === 'medium',
                                            'bg-amber-100 text-amber-800': ticket.priority === 'high',
                                            'bg-red-100 text-red-800': ticket.priority === 'critical',
                                        }"
                                    >
                                        {{ ticket.priority }}
                                    </span>
                                </div>
                            </td>

                            <!-- Location / Asset -->
                            <td class="px-4 py-4 text-sm text-slate-700 dark:text-slate-300">
                                <div v-if="ticket.ga_location" class="flex items-center gap-1 font-medium">
                                    <MapPinIcon class="h-4 w-4 text-slate-400 flex-shrink-0" />
                                    <span>{{ ticket.ga_location.name }}</span>
                                </div>
                                <div v-if="ticket.ga_asset" class="flex items-center gap-1 text-xs text-slate-500 mt-1">
                                    <CubeIcon class="h-3 w-3 text-slate-400 flex-shrink-0" />
                                    <span>Aset: {{ ticket.ga_asset.name }}</span>
                                </div>
                                <div v-if="!ticket.ga_location && !ticket.ga_asset" class="text-slate-400">-</div>
                            </td>

                            <!-- Reporter / Technician -->
                            <td class="px-4 py-4 text-sm text-slate-700 dark:text-slate-300">
                                <div class="flex items-center gap-1">
                                    <UserIcon class="h-3.5 w-3.5 text-slate-400" />
                                    <span>Pelapor: {{ ticket.reporter?.name || 'Sistem' }}</span>
                                </div>
                                <div class="flex items-center gap-1 text-xs text-slate-500 mt-1">
                                    <ClockIcon class="h-3.5 w-3.5 text-slate-400" />
                                    <span>Teknisi: {{ ticket.assignee?.name || 'Belum ditunjuk' }}</span>
                                </div>
                            </td>

                            <!-- Status -->
                            <td class="whitespace-nowrap px-4 py-4">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold"
                                    :class="{
                                        'bg-blue-100 text-blue-800': ticket.status === 'open',
                                        'bg-amber-100 text-amber-800': ticket.status === 'in_progress',
                                        'bg-emerald-100 text-emerald-800': ticket.status === 'resolved',
                                        'bg-slate-100 text-slate-800': ticket.status === 'closed',
                                    }"
                                >
                                    {{ ticket.status.toUpperCase() }}
                                </span>
                            </td>

                            <!-- Actions -->
                            <td class="whitespace-nowrap px-4 py-4 text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    <Link :href="route('ga.tickets.show', ticket.id)" class="text-cyan-600 hover:text-cyan-900 dark:text-cyan-400 dark:hover:text-cyan-300 font-bold">Detail</Link>
                                    <Link :href="route('ga.tickets.edit', ticket.id)" class="text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-300"><PencilSquareIcon class="h-5 w-5"/></Link>
                                    <button @click="deleteTicket(ticket)" class="text-red-500 hover:text-red-700"><TrashIcon class="h-5 w-5"/></button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="tickets.data.length === 0">
                            <td colspan="6" class="py-8 text-center text-sm text-slate-500">
                                Belum ada data tiket layanan.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4 flex items-center justify-between" v-if="tickets.links.length > 3">
                <div class="flex flex-1 justify-between sm:hidden">
                    <Link :href="tickets.prev_page_url" class="relative inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Previous</Link>
                    <Link :href="tickets.next_page_url" class="relative ml-3 inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Next</Link>
                </div>
                <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-slate-700 dark:text-slate-300">
                            Showing <span class="font-medium">{{ tickets.from || 0 }}</span> to <span class="font-medium">{{ tickets.to || 0 }}</span> of <span class="font-medium">{{ tickets.total }}</span> results
                        </p>
                    </div>
                    <div>
                        <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                            <Link v-for="(link, i) in tickets.links" :key="i" :href="link.url" v-html="link.label" 
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
