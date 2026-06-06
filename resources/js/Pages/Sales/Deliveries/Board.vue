<script setup>
import { computed, ref } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import { 
    ClockIcon, 
    TruckIcon, 
    CheckCircleIcon, 
    DocumentCheckIcon,
    MapPinIcon,
    EyeIcon,
    DocumentPlusIcon,
    CubeIcon,
    CheckBadgeIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    orders: Object // All orders passed from parent
});

// Columns definition
const columns = [
    { 
        id: 'draft', 
        title: 'CREATE DO', 
        bg: 'bg-slate-50 dark:bg-slate-800/50',
        icon: DocumentPlusIcon,
        color: 'text-slate-500'
    },
    { 
        id: 'processing', 
        title: 'LOADING', 
        bg: 'bg-amber-50 dark:bg-amber-900/10',
        icon: CubeIcon,
        color: 'text-amber-500',
        statuses: ['picking', 'packed']
    },
    { 
        id: 'shipped', 
        title: 'SHIPPING', 
        bg: 'bg-blue-50 dark:bg-blue-900/10',
        icon: TruckIcon,
        color: 'text-blue-500'
    },
    { 
        id: 'delivered', 
        title: 'ARRIVED', 
        bg: 'bg-teal-50 dark:bg-teal-900/10',
        icon: MapPinIcon,
        color: 'text-teal-500'
    },
    { 
        id: 'completed', 
        title: 'VERIFIED', 
        bg: 'bg-emerald-50 dark:bg-emerald-900/10',
        icon: CheckBadgeIcon,
        color: 'text-emerald-500'
    }
];

// Helper to filter orders by column
const getOrdersByColumn = (colId, colStatuses) => {
    if (!props.orders || !props.orders.data) return [];
    if (colStatuses) {
        return props.orders.data.filter(o => colStatuses.includes(o.status));
    }
    return props.orders.data.filter(o => o.status === colId);
};

const formatDate = (date) => {
    if (!date) return '-';
    try {
        if (typeof date === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(date)) {
            const [_, monthNum, day] = date.split('-');
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];
            return `${day} ${months[parseInt(monthNum, 10) - 1]}`;
        }
        const d = new Date(date);
        if (isNaN(d.getTime())) return date;
        const day = String(d.getDate()).padStart(2, '0');
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];
        const month = months[d.getMonth()];
        return `${day} ${month}`;
    } catch (e) {
        return date;
    }
};

const getStatusDescription = (status) => {
    const descriptions = {
        draft: 'Draft surat jalan, belum diproses.',
        picking: 'Barang sedang diambil di gudang.',
        packed: 'Barang siap dimuat (load).',
        shipped: 'Barang dalam perjalanan.',
        delivered: 'Barang sampai di tujuan (Laporan Driver).',
        completed: 'Selesai (Verifikasi Admin/POD).',
        cancelled: 'Pengiriman dibatalkan.',
    };
    return descriptions[status] || '';
};

</script>

<template>
    <div class="flex h-full gap-4 overflow-x-auto pb-4 items-stretch">
        <div 
            v-for="col in columns" 
            :key="col.id"
            class="flex-shrink-0 w-80 flex flex-col rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm transition-colors duration-300"
        >
            <!-- Header -->
            <div :class="`p-4 border-b border-slate-100 dark:border-slate-800 ${col.bg} rounded-t-2xl flex items-center justify-between`">
                <div class="flex items-center gap-2">
                    <component :is="col.icon" :class="`w-5 h-5 ${col.color}`" />
                    <h3 class="font-bold text-sm text-slate-700 dark:text-slate-200">{{ col.title }}</h3>
                </div>
                <span class="text-xs font-black bg-white dark:bg-slate-800 px-2 py-0.5 rounded-full text-slate-500 shadow-sm">
                    {{ getOrdersByColumn(col.id, col.statuses).length }}
                </span>
            </div>

            <!-- Cards Container -->
            <div class="p-3 flex-1 overflow-y-auto min-h-[150px] space-y-3 bg-slate-50/50 dark:bg-black/20">
                <div 
                    v-for="order in getOrdersByColumn(col.id, col.statuses)" 
                    :key="order.id"
                    class="bg-white dark:bg-slate-800 p-4 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 hover:shadow-md transition-all group relative"
                >
                    <div class="flex justify-between items-start mb-2">
                        <span class="text-[10px] font-black uppercase text-slate-400 tracking-wider">{{ order.do_number }}</span>
                        <div class="flex items-center gap-1">
                             <span class="text-[9px] font-bold px-1.5 py-0.5 rounded border cursor-help" 
                                :class="order.status === 'completed' ? 'bg-emerald-50 border-emerald-100 text-emerald-600' : 'bg-slate-50 border-slate-100 text-slate-500'"
                                :title="getStatusDescription(order.status)"
                            >
                                {{ order.status }}
                            </span>
                            <Link :href="route('sales.deliveries.show', order.id)" class="p-1 rounded bg-slate-50 text-slate-400 hover:text-blue-500">
                                <EyeIcon class="w-3 h-3" />
                            </Link>
                        </div>
                    </div>

                    <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-1 line-clamp-1">{{ order.customer?.name || order.shipping_name }}</h4>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 mb-3 flex items-center gap-1 truncate">
                        <MapPinIcon class="w-3 h-3" /> {{ order.shipping_address || 'No Address' }}
                    </p>

                    <div class="flex items-center justify-between mt-2 pt-2 border-t border-slate-50 dark:border-slate-700/50">
                        <div class="flex flex-col">
                             <div class="flex items-center gap-1.5">
                                <TruckIcon class="w-3 h-3 text-slate-400" />
                                <span class="text-[10px] font-bold text-slate-600 dark:text-slate-300 truncate">
                                    {{ order.vehicle ? order.vehicle.license_plate : (order.vehicle_number || '-') }}
                                </span>
                            </div>
                            <div class="text-[9px] text-slate-400 pl-4.5 truncate">
                                {{ order.driver_name || '-' }}
                            </div>
                        </div>
                        <div class="text-[9px] text-slate-400 font-medium">
                            {{ formatDate(order.delivery_date) }}
                        </div>
                    </div>
                </div>
                
                <!-- Empty State per column -->
                <div v-if="getOrdersByColumn(col.id, col.statuses).length === 0" class="h-full flex items-center justify-center py-8 text-center pointer-events-none">
                    <span class="text-xs text-slate-400 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-lg px-4 py-2 w-full">Kosong</span>
                </div>
            </div>
        </div>
    </div>
</template>
