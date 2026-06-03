<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { formatDate as formatDateDmy } from '@/helpers';
import {
    HomeIcon,
    ShoppingCartIcon,
    ArrowRightOnRectangleIcon,
    Bars3Icon,
    XMarkIcon,
    TruckIcon,
    BanknotesIcon,
    CalendarIcon,
    ChartBarIcon,
    FolderIcon,
    BellIcon,
    SunIcon,
    MoonIcon,
    ArchiveBoxIcon,
    ClipboardDocumentListIcon,
    QuestionMarkCircleIcon,
    ChevronLeftIcon,
    ChevronRightIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    title: String,
});

const sidebarOpen = ref(false);
const notificationDropdownOpen = ref(false);
const isSidebarCollapsed = ref(false); // Default false, will sync on mounted

const navigation = [
    { name: 'Dashboard', href: '/portal/dashboard', icon: HomeIcon },
    { name: 'Purchase Orders', href: '/portal/purchase-orders', icon: ShoppingCartIcon },
    { name: 'Deliveries', href: '/portal/deliveries', icon: TruckIcon },
    { name: 'Invoices', href: '/portal/invoices', icon: BanknotesIcon },
    { name: 'Schedule', href: '/portal/schedule', icon: CalendarIcon },
    { name: 'Analytics', href: '/portal/analytics', icon: ChartBarIcon },
    { name: 'Documents', href: '/portal/documents', icon: FolderIcon },
    { name: 'Returns', href: '/portal/returns', icon: ArchiveBoxIcon },
    { name: 'RFQ', href: '/portal/rfq', icon: ClipboardDocumentListIcon },
    { name: 'User Guide', href: '/portal/help', icon: QuestionMarkCircleIcon },
];

const logout = () => {
    router.post(route('logout'));
};

const toggleSidebar = () => {
    isSidebarCollapsed.value = !isSidebarCollapsed.value;
    localStorage.setItem('sidebarCollapsed', isSidebarCollapsed.value);
};

// Get notifications from shared props
const notifications = computed(() => {
    return window.$page?.props?.auth?.recentNotifications || [];
});

const unreadCount = computed(() => {
    return window.$page?.props?.auth?.unreadNotificationsCount || 0;
});

const recentNotifications = computed(() => {
    return notifications.value.slice(0, 5);
});

const markAsRead = (id) => {
    router.post(route('portal.notifications.read', id), {}, { preserveScroll: true });
    notificationDropdownOpen.value = false;
};

const formatDate = (date) => {
    const d = new Date(date);
    const now = new Date();
    const diff = now - d;
    
    if (diff < 60000) return 'Just now';
    if (diff < 3600000) return Math.floor(diff / 60000) + 'm ago';
    if (diff < 86400000) return Math.floor(diff / 3600000) + 'h ago';
    return formatDateDmy(d);
};

const isDarkMode = ref(false);

const toggleTheme = () => {
    isDarkMode.value = !isDarkMode.value;
    if (isDarkMode.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
};

onMounted(() => {
    // Theme Sync
    if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        isDarkMode.value = true;
        document.documentElement.classList.add('dark');
    } else {
        isDarkMode.value = false;
        document.documentElement.classList.remove('dark');
    }

    // Sidebar State Sync
    if (localStorage.getItem('sidebarCollapsed') === 'true') {
        isSidebarCollapsed.value = true;
    }
});
</script>

<template>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors duration-300">
        <Head :title="title" />

        <!-- Mobile Sidebar -->
        <div v-if="sidebarOpen" class="fixed inset-0 z-50 lg:hidden">
            <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm" @click="sidebarOpen = false"></div>
            <div class="fixed inset-y-0 left-0 w-64 bg-white dark:bg-slate-800 shadow-xl p-4">
                <div class="flex items-center justify-between mb-8">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-slate-900 border border-white/10 flex items-center justify-center shadow-[0_0_15px_rgba(6,182,212,0.3)] shrink-0 overflow-hidden">
                            <img :src="$page.props.company?.logo || '/images/jicos.png'" alt="Logo" class="w-full h-full object-cover" />
                        </div>
                        <div class="flex flex-col">
                            <span class="text-2xl font-black italic tracking-tighter text-slate-900 dark:text-white">JICOS PORTAL</span>
                            <span class="text-[8px] font-bold text-indigo-500 uppercase tracking-[0.2em] -mt-1 font-mono">Supplier Gateway</span>
                        </div>
                    </div>
                    <button @click="sidebarOpen = false" class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                        <XMarkIcon class="h-6 w-6 text-slate-500" />
                    </button>
                </div>
                <nav class="space-y-2">
                    <Link
                        v-for="item in navigation"
                        :key="item.name"
                        :href="item.href"
                        class="flex items-center gap-3 px-4 py-3 rounded-xl transition-colors"
                        :class="$page.url.startsWith(item.href) ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/20 dark:text-indigo-400 font-semibold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700'"
                    >
                        <component :is="item.icon" class="h-5 w-5" />
                        {{ item.name }}
                    </Link>
                </nav>
            </div>
        </div>

        <!-- Desktop Sidebar -->
        <div 
            class="hidden lg:fixed lg:inset-y-0 lg:flex lg:flex-col bg-white dark:bg-slate-800 border-r border-slate-200 dark:border-slate-700 transition-all duration-300 z-30"
            :class="isSidebarCollapsed ? 'lg:w-20' : 'lg:w-64'"
        >
            <div class="flex items-center justify-center border-b border-slate-200 dark:border-slate-700 bg-slate-50/30 dark:bg-slate-900/10 transition-all duration-300"
                :class="isSidebarCollapsed ? 'py-4 h-[73px]' : 'py-8 h-[120px]'"
            >
                <div v-if="isSidebarCollapsed" class="w-10 h-10 rounded-xl bg-slate-900 border border-white/10 flex items-center justify-center shadow-[0_0_15px_rgba(6,182,212,0.3)] shrink-0 overflow-hidden cursor-pointer" @click="toggleSidebar">
                     <img :src="$page.props.company?.logo || '/images/jicos.png'" alt="Logo" class="w-full h-full object-cover" />
                </div>
                <div v-else class="flex flex-col items-center gap-3 fade-in">
                    <div class="w-16 h-16 rounded-2xl bg-slate-900 border border-white/10 flex items-center justify-center shadow-[0_0_20px_rgba(6,182,212,0.4)] transition-all duration-500 hover:shadow-[0_0_30px_rgba(6,182,212,0.7)] hover:border-cyan-500/50 overflow-hidden group">
                        <img :src="$page.props.company?.logo || '/images/jicos.png'" alt="Logo" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" />
                    </div>
                    <div class="text-center px-4">
                        <span class="text-2xl font-black italic tracking-[0.05em] text-transparent bg-clip-text bg-gradient-to-r from-slate-900 to-slate-600 dark:from-white dark:to-cyan-400 drop-shadow-[0_0_8px_rgba(6,182,212,0.3)] leading-none block">JICOS PORTAL</span>
                        <div class="h-1 w-12 bg-indigo-500 mx-auto mt-2 rounded-full shadow-[0_0_8px_#6366f1]"></div>
                    </div>
                </div>
            </div>

            <nav class="flex-1 p-3 space-y-2 overflow-y-auto overflow-x-hidden no-scrollbar">
                <Link
                    v-for="item in navigation"
                    :key="item.name"
                    :href="item.href"
                    class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all duration-200 group relative"
                    :class="[
                        $page.url.startsWith(item.href) ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/20 dark:text-indigo-400 font-semibold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700',
                        isSidebarCollapsed ? 'justify-center' : ''
                    ]"
                >
                    <component :is="item.icon" class="h-6 w-6 shrink-0" />
                    <span v-if="!isSidebarCollapsed" class="whitespace-nowrap transition-opacity duration-300">{{ item.name }}</span>
                    
                    <!-- Tooltip -->
                    <div v-if="isSidebarCollapsed" class="absolute left-full ml-3 px-3 py-1.5 bg-slate-800 text-white text-xs font-bold rounded-lg opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50 shadow-xl border border-slate-700 transition-opacity translate-x-1 group-hover:translate-x-0">
                        {{ item.name }}
                    </div>
                </Link>
            </nav>

            <div class="p-3 border-t border-slate-200 dark:border-slate-700 space-y-2">
                 <!-- Collapse Toggle -->
                 <button 
                    @click="toggleSidebar" 
                    class="hidden lg:flex w-full items-center gap-3 px-3 py-2 rounded-xl text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors group relative"
                    :class="isSidebarCollapsed ? 'justify-center' : ''"
                    title="Toggle Sidebar"
                >
                    <ChevronRightIcon v-if="isSidebarCollapsed" class="h-5 w-5" />
                    <ChevronLeftIcon v-else class="h-5 w-5" />
                    <span v-if="!isSidebarCollapsed" class="text-sm">Collapse</span>
                </button>

                <button
                    @click="logout"
                    class="flex w-full items-center gap-3 px-3 py-3 rounded-xl text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors group relative"
                    :class="isSidebarCollapsed ? 'justify-center' : ''"
                >
                    <ArrowRightOnRectangleIcon class="h-5 w-5 shrink-0" />
                    <span v-if="!isSidebarCollapsed" class="whitespace-nowrap">Logout</span>
                     <!-- Tooltip -->
                     <div v-if="isSidebarCollapsed" class="absolute left-full ml-3 px-3 py-1.5 bg-red-900 text-white text-xs font-bold rounded-lg opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50 shadow-xl border border-red-800">
                        Logout
                    </div>
                </button>
            </div>
        </div>

        <!-- Main Content -->
        <div class="transition-all duration-300 min-h-screen flex flex-col" :class="isSidebarCollapsed ? 'lg:pl-20' : 'lg:pl-64'">
            <!-- Header -->
            <header class="sticky top-0 z-40 bg-white/80 dark:bg-slate-900/80 backdrop-blur border-b border-slate-200 dark:border-slate-700 h-16 flex items-center justify-between px-4 sm:px-6 lg:px-8">
                <button @click="sidebarOpen = true" class="lg:hidden p-2 text-slate-500">
                    <Bars3Icon class="h-6 w-6" />
                </button>
                <div class="flex-1"></div>
                <div class="flex items-center gap-4">
                    <!-- Theme Toggle -->
                    <button 
                        @click="toggleTheme"
                        class="p-2 text-slate-500 hover:text-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors"
                        :title="isDarkMode ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
                    >
                        <SunIcon v-if="isDarkMode" class="h-5 w-5" />
                        <MoonIcon v-else class="h-5 w-5" />
                    </button>

                    <!-- Notification Bell -->
                    <div class="relative">
                        <button 
                            @click="notificationDropdownOpen = !notificationDropdownOpen"
                            class="relative p-2 text-slate-500 hover:text-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors"
                        >
                            <BellIcon class="h-5 w-5" />
                            <span 
                                v-if="unreadCount > 0"
                                class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center"
                            >
                                {{ unreadCount > 9 ? '9+' : unreadCount }}
                            </span>
                        </button>

                        <!-- Dropdown -->
                        <div 
                            v-if="notificationDropdownOpen"
                            class="absolute right-0 mt-2 w-80 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden z-50"
                        >
                            <div class="p-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between bg-slate-50 dark:bg-slate-700/50">
                                <h3 class="font-bold text-slate-900 dark:text-white">Notifications</h3>
                                <span v-if="unreadCount > 0" class="text-xs text-indigo-600 font-semibold">{{ unreadCount }} new</span>
                            </div>
                            <div class="max-h-80 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700">
                                <div 
                                    v-for="notif in recentNotifications" 
                                    :key="notif.id"
                                    class="p-3 hover:bg-slate-50 dark:hover:bg-slate-700/30 cursor-pointer"
                                    :class="{ 'bg-indigo-50/50 dark:bg-indigo-900/10': !notif.read_at }"
                                    @click="markAsRead(notif.id)"
                                >
                                    <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ notif.data?.title }}</p>
                                    <p class="text-xs text-slate-500 truncate">{{ notif.data?.message }}</p>
                                    <p class="text-[10px] text-slate-400 mt-1">{{ formatDate(notif.created_at) }}</p>
                                </div>
                                <div v-if="recentNotifications.length === 0" class="p-6 text-center text-slate-400 text-sm">
                                    No notifications
                                </div>
                            </div>
                            <Link 
                                href="/portal/notifications" 
                                class="block p-3 text-center text-sm font-semibold text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 border-t border-slate-200 dark:border-slate-700"
                                @click="notificationDropdownOpen = false"
                            >
                                View all notifications
                            </Link>
                        </div>
                    </div>

                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $page.props.auth.user.name }}</p>
                        <p class="text-xs text-slate-500">{{ $page.props.auth.user.email }}</p>
                    </div>
                    <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold border border-indigo-200">
                        {{ $page.props.auth.user.name.charAt(0) }}
                    </div>
                </div>
            </header>

            <!-- Click outside to close dropdown -->
            <div v-if="notificationDropdownOpen" class="fixed inset-0 z-30" @click="notificationDropdownOpen = false"></div>

            <!-- Page Content -->
            <main class="py-10 flex-1">
                <div class="px-4 sm:px-6 lg:px-8">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.fade-in {
    animation: fadeIn 0.3s ease-in;
}
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
</style>
