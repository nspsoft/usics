<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import { ref } from 'vue';
import { formatDate as formatDateDmy } from '@/helpers';
import { 
    BellIcon, 
    CheckIcon,
    ShoppingCartIcon,
    TruckIcon,
    BanknotesIcon,
    DocumentIcon,
    ArrowRightIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    notifications: Object,
    unread_count: Number,
    filter: String,
});

const getIcon = (type) => {
    switch (type) {
        case 'new_po': return ShoppingCartIcon;
        case 'delivery_received': return TruckIcon;
        case 'invoice_paid': return BanknotesIcon;
        default: return DocumentIcon;
    }
};

const getColorClasses = (color, isRead) => {
    if (isRead) return 'bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400';
    
    const colors = {
        indigo: 'bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400',
        emerald: 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400',
        green: 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400',
        orange: 'bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400',
    };
    return colors[color] || colors.indigo;
};

const markAsRead = (id) => {
    router.post(route('portal.notifications.read', id), {}, { preserveScroll: true });
};

const markAllAsRead = () => {
    router.post(route('portal.notifications.mark-all-read'), {}, { preserveScroll: true });
};

const formatDate = (date) => {
    const d = new Date(date);
    const now = new Date();
    const diff = now - d;
    
    if (diff < 60000) return 'Just now';
    if (diff < 3600000) return Math.floor(diff / 60000) + 'm ago';
    if (diff < 86400000) return Math.floor(diff / 3600000) + 'h ago';
    if (diff < 604800000) return Math.floor(diff / 86400000) + 'd ago';
    return formatDateDmy(d);
};
</script>

<template>
    <PortalLayout title="Notifications">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-3">
                    <BellIcon class="w-7 h-7 text-indigo-500" />
                    Notifications
                </h1>
                <p class="text-slate-500">Stay updated with your latest activities.</p>
            </div>
            <div class="flex items-center gap-3">
                <button 
                    v-if="unread_count > 0"
                    @click="markAllAsRead"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-xl transition-colors"
                >
                    <CheckIcon class="w-4 h-4" />
                    Mark all as read
                </button>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="flex gap-2 mb-6">
            <Link 
                :href="route('portal.notifications.index')"
                class="px-4 py-2 rounded-xl text-sm font-semibold transition-colors"
                :class="filter === 'all' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300'"
            >
                All
            </Link>
            <Link 
                :href="route('portal.notifications.index', { filter: 'unread' })"
                class="px-4 py-2 rounded-xl text-sm font-semibold transition-colors flex items-center gap-2"
                :class="filter === 'unread' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300'"
            >
                Unread
                <span v-if="unread_count > 0" class="px-1.5 py-0.5 rounded-full text-xs bg-red-500 text-white">{{ unread_count }}</span>
            </Link>
        </div>

        <!-- Notifications List -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="divide-y divide-slate-100 dark:divide-slate-700">
                <div 
                    v-for="notification in notifications.data" 
                    :key="notification.id"
                    class="p-4 hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors"
                    :class="{ 'bg-indigo-50/50 dark:bg-indigo-900/10': !notification.read_at }"
                >
                    <div class="flex items-start gap-4">
                        <!-- Icon -->
                        <div 
                            class="p-2.5 rounded-xl shrink-0"
                            :class="getColorClasses(notification.data.color, notification.read_at)"
                        >
                            <component :is="getIcon(notification.data.type)" class="w-5 h-5" />
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="font-bold text-slate-900 dark:text-white" :class="{ 'font-extrabold': !notification.read_at }">
                                        {{ notification.data.title }}
                                    </h3>
                                    <p class="text-sm text-slate-600 dark:text-slate-400 mt-0.5">
                                        {{ notification.data.message }}
                                    </p>
                                </div>
                                <span class="text-xs text-slate-400 shrink-0">{{ formatDate(notification.created_at) }}</span>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-3 mt-3">
                                <Link 
                                    v-if="notification.data.action_url"
                                    :href="notification.data.action_url"
                                    class="inline-flex items-center gap-1 text-sm font-semibold text-indigo-600 hover:text-indigo-800"
                                >
                                    {{ notification.data.action_text || 'View' }}
                                    <ArrowRightIcon class="w-3 h-3" />
                                </Link>
                                <button 
                                    v-if="!notification.read_at"
                                    @click="markAsRead(notification.id)"
                                    class="text-sm text-slate-500 hover:text-slate-700"
                                >
                                    Mark as read
                                </button>
                            </div>
                        </div>

                        <!-- Unread indicator -->
                        <div v-if="!notification.read_at" class="w-2 h-2 rounded-full bg-indigo-500 shrink-0 mt-2"></div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="notifications.data.length === 0" class="py-16 text-center">
                    <BellIcon class="w-16 h-16 text-slate-300 mx-auto mb-4" />
                    <p class="text-slate-500">No notifications yet.</p>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="notifications.links && notifications.links.length > 3" class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex items-center justify-center">
                <div class="flex gap-1">
                    <Link
                        v-for="(link, key) in notifications.links"
                        :key="key"
                        :href="link.url || '#'"
                        v-html="link.label"
                        class="px-3 py-1 rounded-lg text-xs font-medium transition-colors"
                        :class="[
                            link.active 
                                ? 'bg-indigo-600 text-white' 
                                : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600',
                            !link.url ? 'opacity-50 cursor-not-allowed' : ''
                        ]"
                    />
                </div>
            </div>
        </div>
    </PortalLayout>
</template>
