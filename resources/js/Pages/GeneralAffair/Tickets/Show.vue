<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    ArrowLeftIcon, 
    PencilSquareIcon,
    MapPinIcon,
    UserCircleIcon,
    CalendarDaysIcon,
    ClipboardDocumentListIcon,
    TagIcon,
    PhotoIcon,
    ExclamationTriangleIcon,
    WrenchScrewdriverIcon,
    CheckCircleIcon,
    LockClosedIcon
} from '@heroicons/vue/24/outline';
import moment from 'moment';

const props = defineProps({
    ticket: Object,
    users: Array,
});

const form = useForm({
    status: props.ticket.status,
    assignee_id: props.ticket.assignee_id || '',
    resolution_notes: props.ticket.resolution_notes || '',
});

const updateTicket = () => {
    form.put(route('ga.tickets.update', props.ticket.id), {
        preserveScroll: true
    });
};
</script>

<template>
    <Head :title="`Detail Tiket: ${ticket.ticket_code}`" />

    <AppLayout title="Detail Tiket">
        <div class="mx-auto max-w-5xl">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="route('ga.tickets.index')" class="rounded-full p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800">
                        <ArrowLeftIcon class="h-5 w-5" />
                    </Link>
                    <div>
                        <h2 class="text-xl font-black text-slate-900 dark:text-white">{{ ticket.title }}</h2>
                        <p class="text-sm text-slate-500">{{ ticket.ticket_code }}</p>
                    </div>
                </div>
                <Link :href="route('ga.tickets.edit', ticket.id)" class="inline-flex items-center gap-2 rounded-xl bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                    <PencilSquareIcon class="h-4 w-4" />
                    Edit Detail
                </Link>
            </div>

            <!-- Main Grid -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                
                <!-- Left Column: Details & Action Controls -->
                <div class="lg:col-span-1 space-y-6">
                    
                    <!-- Evidence Image -->
                    <div class="glass-card overflow-hidden rounded-3xl shadow-sm">
                        <div class="aspect-w-16 aspect-h-9 relative w-full bg-slate-100 dark:bg-slate-800" style="padding-top: 56.25%;">
                            <img v-if="ticket.image_url" :src="ticket.image_url" class="absolute inset-0 h-full w-full object-cover" />
                            <div v-else class="absolute inset-0 flex h-full w-full flex-col items-center justify-center text-slate-400">
                                <PhotoIcon class="h-12 w-12" />
                                <span class="text-xs mt-1">Tidak ada foto bukti</span>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="flex items-center justify-between">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                    :class="{
                                        'bg-blue-100 text-blue-800': ticket.status === 'open',
                                        'bg-amber-100 text-amber-800': ticket.status === 'in_progress',
                                        'bg-emerald-100 text-emerald-800': ticket.status === 'resolved',
                                        'bg-slate-100 text-slate-800': ticket.status === 'closed',
                                    }"
                                >
                                    {{ ticket.status.toUpperCase() }}
                                </span>
                                <span class="inline-flex rounded px-1.5 py-0.5 text-xs font-semibold uppercase"
                                    :class="{
                                        'bg-slate-100 text-slate-800': ticket.priority === 'low',
                                        'bg-blue-100 text-blue-800': ticket.priority === 'medium',
                                        'bg-amber-100 text-amber-800': ticket.priority === 'high',
                                        'bg-red-100 text-red-800': ticket.priority === 'critical',
                                    }"
                                >
                                    {{ ticket.priority }} Priority
                                </span>
                            </div>
                            
                            <dl class="mt-6 space-y-4 text-sm">
                                <div class="flex items-start gap-3">
                                    <TagIcon class="h-5 w-5 text-slate-400 flex-shrink-0" />
                                    <div>
                                        <dt class="text-xs text-slate-500">Kategori</dt>
                                        <dd class="font-medium text-slate-900 dark:text-white capitalize">{{ ticket.category }}</dd>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <UserCircleIcon class="h-5 w-5 text-slate-400 flex-shrink-0" />
                                    <div>
                                        <dt class="text-xs text-slate-500">Pelapor</dt>
                                        <dd class="font-medium text-slate-900 dark:text-white">{{ ticket.reporter?.name || 'Sistem' }}</dd>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <CalendarDaysIcon class="h-5 w-5 text-slate-400 flex-shrink-0" />
                                    <div>
                                        <dt class="text-xs text-slate-500">Dilaporkan Pada</dt>
                                        <dd class="font-medium text-slate-900 dark:text-white">{{ moment(ticket.created_at).format('DD MMM YYYY, HH:mm') }}</dd>
                                    </div>
                                </div>
                            </dl>

                            <div class="mt-6 border-t border-slate-200 pt-4 dark:border-slate-800">
                                <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Deskripsi Masalah</h4>
                                <p class="text-sm text-slate-700 dark:text-slate-300 bg-slate-50 dark:bg-slate-900/50 p-3 rounded-xl whitespace-pre-wrap">{{ ticket.description }}</p>
                            </div>

                            <div v-if="ticket.status === 'resolved' && ticket.resolution_notes" class="mt-4 border-t border-slate-200 pt-4 dark:border-slate-800">
                                <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Catatan Resolusi (Perbaikan)</h4>
                                <p class="text-sm text-emerald-800 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/20 p-3 rounded-xl whitespace-pre-wrap">{{ ticket.resolution_notes }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Actions Form (GA Team controls) -->
                    <div class="glass-card rounded-3xl p-6 shadow-sm">
                        <h3 class="mb-4 text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                            <WrenchScrewdriverIcon class="h-5 w-5 text-cyan-600" />
                            Tindakan Penugasan & Status
                        </h3>
                        
                        <form @submit.prevent="updateTicket" class="space-y-4">
                            <!-- Assignee Selection -->
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Teknisi / PIC Penugasan</label>
                                <select v-model="form.assignee_id" class="mt-1 block w-full rounded-xl border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                                    <option value="">-- Belum Ditunjuk --</option>
                                    <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
                                </select>
                            </div>

                            <!-- Status Selection -->
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Status Tiket</label>
                                <select v-model="form.status" class="mt-1 block w-full rounded-xl border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                                    <option value="open">Open (Baru)</option>
                                    <option value="in_progress">In Progress (Sedang Dikerjakan)</option>
                                    <option value="resolved">Resolved (Selesai Diperbaiki)</option>
                                    <option value="closed">Closed (Ditutup Pelapor)</option>
                                </select>
                            </div>

                            <!-- Resolution Notes (Only visible if state is set to resolved) -->
                            <div v-if="form.status === 'resolved'">
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Catatan Resolusi / Perbaikan <span class="text-red-500">*</span></label>
                                <textarea v-model="form.resolution_notes" rows="3" required class="mt-1 block w-full rounded-xl border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white" placeholder="Jelaskan tindakan yang telah dilakukan untuk memperbaiki masalah ini..."></textarea>
                            </div>

                            <button type="submit" :disabled="form.processing" class="w-full inline-flex justify-center items-center gap-2 rounded-xl bg-cyan-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-cyan-500 focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2 disabled:opacity-50">
                                Update Tiket
                            </button>
                        </form>
                    </div>

                </div>

                <!-- Right Column: Map & Logs -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- Mapping Location -->
                    <div class="glass-card rounded-3xl p-6 shadow-sm">
                        <h3 class="mb-4 text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                            <MapPinIcon class="h-5 w-5 text-cyan-600" />
                            Lokasi Peta Denah
                        </h3>
                        <p class="text-sm text-slate-700 dark:text-slate-300 mb-4">
                            <strong>Denah Area:</strong> {{ ticket.ga_location?.name || 'Tidak ada denah terkait' }}
                            <br>
                            <span v-if="ticket.ga_asset">
                                <strong>Terkait Aset:</strong> <span class="font-semibold">{{ ticket.ga_asset.name }} ({{ ticket.ga_asset.asset_code }})</span>
                            </span>
                            <span v-else-if="ticket.pos_x && ticket.pos_y">
                                <strong>Kategori:</strong> Titik Koordinat Kerusakan Umum (Bukan Aset)
                            </span>
                        </p>
                        
                        <div v-if="ticket.ga_location?.map_background_url" class="relative w-full overflow-hidden rounded-xl border border-slate-200 bg-slate-100 dark:border-slate-800" style="padding-top: 56.25%;">
                            <img :src="ticket.ga_location.map_background_url" class="absolute inset-0 h-full w-full object-cover opacity-80" />
                            
                            <!-- Location Marker Pin -->
                            <div 
                                v-if="ticket.pos_x && ticket.pos_y"
                                class="absolute h-6 w-6 -ml-3 -mt-6 pointer-events-none drop-shadow-md"
                                :class="ticket.ga_asset ? 'text-cyan-500' : 'text-red-500'"
                                :style="{ left: `${ticket.pos_x}%`, top: `${ticket.pos_y}%` }"
                                :title="ticket.ga_asset ? `Aset: ${ticket.ga_asset.name}` : 'Titik Kerusakan'"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path fill-rule="evenodd" d="M11.54 22.351l.07.04.028.016a.76.76 0 00.723 0l.028-.015.071-.041a16.975 16.975 0 001.144-.742 19.58 19.58 0 002.683-2.282c1.944-1.99 3.963-4.98 3.963-8.827a8.25 8.25 0 00-16.5 0c0 3.846 2.02 6.837 3.963 8.827a19.58 19.58 0 002.682 2.282 16.975 16.975 0 001.145.742zM12 13.5a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                        <div v-else class="rounded-xl border-2 border-dashed border-slate-200 p-8 text-center dark:border-slate-800">
                            <p class="text-sm text-slate-500">Tidak ada pemetaan denah pada tiket ini.</p>
                        </div>
                    </div>

                    <!-- Logs/History Timeline -->
                    <div class="glass-card rounded-3xl p-6 shadow-sm">
                        <h3 class="mb-4 text-base font-black text-slate-900 dark:text-white">Timeline Riwayat Penanganan</h3>
                        <div class="flow-root">
                            <ul role="list" class="-mb-8">
                                <li v-for="(log, logIdx) in ticket.logs" :key="log.id">
                                    <div class="relative pb-8">
                                        <span v-if="logIdx !== ticket.logs.length - 1" class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-slate-200 dark:bg-slate-700" aria-hidden="true"></span>
                                        <div class="relative flex space-x-3">
                                            <div>
                                                <!-- Icon type based on action -->
                                                <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white dark:ring-slate-900"
                                                    :class="{
                                                        'bg-blue-50 text-blue-600 dark:bg-blue-950/20': log.action === 'created',
                                                        'bg-amber-50 text-amber-600 dark:bg-amber-950/20': log.action === 'assigned',
                                                        'bg-purple-50 text-purple-600 dark:bg-purple-950/20': log.action === 'status_changed' && ticket.status !== 'resolved',
                                                        'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/20': log.action === 'resolved' || (log.action === 'status_changed' && log.notes.includes('RESOLVED')),
                                                        'bg-slate-50 text-slate-600 dark:bg-slate-850/20': log.action === 'closed' || (log.action === 'status_changed' && log.notes.includes('CLOSED')),
                                                    }"
                                                >
                                                    <ExclamationTriangleIcon v-if="log.action === 'created'" class="h-4 w-4" />
                                                    <UserCircleIcon v-else-if="log.action === 'assigned'" class="h-4 w-4" />
                                                    <CheckCircleIcon v-else-if="log.action === 'resolved' || log.notes.includes('RESOLVED')" class="h-4 w-4" />
                                                    <LockClosedIcon v-else-if="log.action === 'closed' || log.notes.includes('CLOSED')" class="h-4 w-4" />
                                                    <ClipboardDocumentListIcon v-else class="h-4 w-4" />
                                                </span>
                                            </div>
                                            <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                                <div>
                                                    <p class="text-sm text-slate-500">
                                                        <span class="font-medium text-slate-900 dark:text-white">{{ log.user?.name || 'Sistem' }}</span>
                                                    </p>
                                                    <p class="mt-1 text-sm text-slate-700 dark:text-slate-300 font-medium">{{ log.notes }}</p>
                                                </div>
                                                <div class="whitespace-nowrap text-right text-xs text-slate-500">
                                                    {{ moment(log.created_at).format('DD MMM YYYY, HH:mm') }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li v-if="ticket.logs.length === 0">
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
