<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatNumber, formatCurrency } from '@/helpers';
import { 
    ArrowLeftIcon,
    PencilSquareIcon,
    UserGroupIcon,
    MapPinIcon,
    PhoneIcon,
    EnvelopeIcon,
    IdentificationIcon,
    CreditCardIcon,
    BanknotesIcon,
    CalendarIcon,
    ClockIcon,
    DocumentTextIcon,
    ShoppingBagIcon,
    UserIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    customer: Object,
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    });
};


const getTypeBadge = (type) => {
    const badges = {
        regular: 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30',
        vip: 'bg-amber-500/20 text-amber-400 border-amber-500/30 shadow-lg shadow-amber-500/10',
        wholesale: 'bg-purple-500/20 text-purple-400 border-purple-500/30',
    };
    return badges[type] || 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30';
};

const getStatusBadge = (status) => {
    const badges = {
        draft: 'bg-slate-500/20 text-slate-500 dark:text-slate-400',
        confirmed: 'bg-blue-500/20 text-blue-400',
        processing: 'bg-amber-500/20 text-amber-400',
        shipped: 'bg-indigo-500/20 text-indigo-400',
        delivered: 'bg-emerald-500/20 text-emerald-400',
        cancelled: 'bg-red-500/20 text-red-400',
    };
    return badges[status] || 'bg-slate-500/20 text-slate-500 dark:text-slate-400';
};
</script>

<template>
    <Head :title="customer.name" />

    <AppLayout :title="customer.name">
        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <Link :href="route('sales.customers.index')" class="p-2 rounded-xl glass-card text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white transition-colors">
                        <ArrowLeftIcon class="h-5 w-5" />
                    </Link>
                    
                    <!-- Profile Photo -->
                    <div class="h-16 w-16 rounded-3xl overflow-hidden border-2 border-white dark:border-slate-800 shadow-xl">
                        <img v-if="customer.profile_photo_url" :src="customer.profile_photo_url" class="h-full w-full object-cover" />
                        <div v-else class="h-full w-full bg-gradient-to-br from-slate-200 to-slate-300 dark:from-slate-700 dark:to-slate-800 flex items-center justify-center text-slate-400 dark:text-slate-500">
                            <UserIcon class="h-8 w-8" />
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center gap-3">
                            <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">{{ customer.name }}</h2>
                            <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-bold uppercase tracking-wider" :class="getTypeBadge(customer.customer_type)">
                                {{ customer.customer_type }}
                            </span>
                        </div>
                        <p class="text-slate-500 font-medium tracking-wide">{{ customer.code }}</p>
                    </div>
                </div>
                <Link :href="route('sales.customers.edit', customer.id)" 
                    class="inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl glass-card text-slate-600 dark:text-slate-300 font-bold hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 hover:text-slate-900 dark:text-white transition-all">
                    <PencilSquareIcon class="h-5 w-5" />
                    Edit Profile
                </Link>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Contact Info Card -->
                <div class="lg:col-span-1 space-y-6">
                    <div class="rounded-3xl glass-card overflow-hidden shadow-xl">
                        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/20">
                            <h3 class="flex items-center gap-2 text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-[0.2em]">Contact Details</h3>
                        </div>
                        <div class="p-6 space-y-6">
                            <div class="flex items-start gap-4 group">
                                <div class="p-3 rounded-2xl bg-blue-500/10 border border-blue-500/20 group-hover:bg-blue-500/20 transition-colors">
                                    <PhoneIcon class="h-5 w-5 text-blue-400" />
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Phone Number</p>
                                    <p class="text-sm text-slate-900 dark:text-white font-semibold">{{ customer.phone || 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-4 group">
                                <div class="p-3 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 group-hover:bg-emerald-500/20 transition-colors">
                                    <EnvelopeIcon class="h-5 w-5 text-emerald-400" />
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Email Address</p>
                                    <p class="text-sm text-slate-900 dark:text-white font-semibold truncate">{{ customer.email || 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-4 group">
                                <div class="p-3 rounded-2xl bg-violet-500/10 border border-violet-500/20 group-hover:bg-violet-500/20 transition-colors">
                                    <UserGroupIcon class="h-5 w-5 text-violet-400" />
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Contact Person</p>
                                    <p class="text-sm text-slate-900 dark:text-white font-semibold">{{ customer.contact_person || 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-4 group pt-2">
                                <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 group-hover:bg-slate-700 transition-colors">
                                    <MapPinIcon class="h-5 w-5 text-slate-500 dark:text-slate-400" />
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Office / Shipping Address</p>
                                    <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">{{ customer.full_address }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Summary Card -->
                    <div class="rounded-3xl glass-card overflow-hidden shadow-xl">
                        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/20">
                            <h3 class="flex items-center gap-2 text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-[0.2em]">Financial Info</h3>
                        </div>
                        <div class="p-6 grid grid-cols-2 gap-4">
                            <div class="p-4 rounded-3xl glass-card/50">
                                <IdentificationIcon class="h-5 w-5 text-amber-500 mb-2" />
                                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-tighter mb-1">NPWP / Tax ID</p>
                                <p class="text-xs text-slate-200 font-bold truncate">{{ customer.tax_id || 'Not Set' }}</p>
                            </div>
                            <div class="p-4 rounded-3xl glass-card/50">
                                <BanknotesIcon class="h-5 w-5 text-indigo-500 mb-2" />
                                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-tighter mb-1">Terms</p>
                                <p class="text-xs text-slate-200 font-bold">{{ customer.payment_terms }}</p>
                            </div>
                            <div class="p-4 rounded-3xl glass-card/50">
                                <ClockIcon class="h-5 w-5 text-cyan-500 mb-2" />
                                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-tighter mb-1">Due Days</p>
                                <p class="text-xs text-slate-200 font-bold">{{ customer.payment_days }} Days</p>
                            </div>
                            <div class="p-4 rounded-3xl glass-card/50">
                                <StarIcon class="h-5 w-5 text-yellow-500 mb-2" />
                                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-tighter mb-1">Joined Since</p>
                                <p class="text-xs text-slate-200 font-bold">{{ formatDate(customer.created_at) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sales Orders History -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="rounded-3xl glass-card overflow-hidden shadow-xl min-h-[400px]">
                        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/20 flex items-center justify-between">
                            <h3 class="flex items-center gap-2 text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-[0.2em]">Recent Sales Orders</h3>
                            <Link :href="route('sales.orders.index', { customer: customer.id })" class="text-[10px] font-bold text-blue-400 hover:text-blue-300 transition-colors uppercase tracking-widest">
                                View All
                            </Link>
                        </div>
                        
                        <div v-if="customer.sales_orders.length > 0" class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="bg-white dark:bg-slate-950 text-slate-500 text-[10px] font-black uppercase tracking-widest border-b border-slate-200 dark:border-slate-800">
                                    <tr>
                                        <th class="px-6 py-4">SO Number</th>
                                        <th class="px-6 py-4">Date</th>
                                        <th class="px-6 py-4">Status</th>
                                        <th class="px-6 py-4 text-right">Total</th>
                                        <th class="px-6 py-4"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 bg-white dark:bg-slate-950/40">
                                    <tr v-for="order in customer.sales_orders" :key="order.id" class="group hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="h-8 w-8 rounded-lg bg-slate-50 dark:bg-slate-800 flex items-center justify-center border border-slate-200 dark:border-slate-700 group-hover:border-blue-500/30 transition-colors">
                                                    <DocumentTextIcon class="h-4 w-4 text-slate-500 dark:text-slate-400 group-hover:text-blue-400" />
                                                </div>
                                                <span class="text-sm font-bold text-slate-900 dark:text-white tracking-wide">{{ order.so_number }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-xs font-medium text-slate-500 dark:text-slate-400">{{ formatDate(order.order_date) }}</td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border border-transparent transition-all" :class="getStatusBadge(order.status)">
                                                {{ order.status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm font-bold text-right text-slate-900 dark:text-white tracking-widest">{{ formatCurrency(order.total) }}</td>
                                        <td class="px-6 py-4 text-right">
                                            <Link :href="route('sales.orders.show', order.id)" class="p-2 rounded-lg text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 hover:text-slate-900 dark:text-white transition-all inline-block">
                                                <ArrowLeftIcon class="h-4 w-4 rotate-180" />
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <div v-else class="flex flex-col items-center justify-center p-12 text-center h-full">
                            <ShoppingBagIcon class="h-12 w-12 text-slate-700 mb-4" />
                            <p class="text-sm font-bold text-slate-500 uppercase tracking-widest">No orders found for this customer.</p>
                            <Link :href="route('sales.orders.create', { customer_id: customer.id })" 
                                class="mt-4 px-6 py-2 rounded-xl bg-blue-600/10 border border-blue-500/20 text-blue-400 text-xs font-black uppercase tracking-widest hover:bg-blue-600/20 transition-all">
                                Create First Order
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>



