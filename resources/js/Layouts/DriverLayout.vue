<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    TruckIcon,
    QrCodeIcon,
    UserCircleIcon,
    ArrowRightOnRectangleIcon,
    Bars3Icon,
    SunIcon,
    MoonIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    title: String,
});

const page = usePage();
const userName = computed(() => page.props.auth?.user?.name || 'Driver');
const isDarkMode = ref(false);

const toggleTheme = () => {
    isDarkMode.value = !isDarkMode.value;
    if (isDarkMode.value) {
        document.documentElement.classList.add('dark');
        localStorage.theme = 'dark';
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.theme = 'light';
    }
};

const logout = () => {
    router.post('/logout');
};

const navItems = [
    { name: 'Pengiriman', href: '/driver/dashboard', icon: TruckIcon },
    { name: 'Scan QR', href: '/driver/scan', icon: QrCodeIcon },
];

const isActive = (href) => {
    return window.location.pathname === href;
};

onMounted(() => {
    if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        isDarkMode.value = true;
        document.documentElement.classList.add('dark');
    }
});
</script>

<template>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors pb-20">
        <Head :title="'Driver - ' + (title || 'Dashboard')" />

        <!-- Top Bar -->
        <header class="sticky top-0 z-40 bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl border-b border-slate-200 dark:border-slate-800">
            <div class="flex items-center justify-between px-4 py-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-700 flex items-center justify-center shadow-lg shadow-blue-500/30">
                        <TruckIcon class="h-5 w-5 text-white" />
                    </div>
                    <div>
                        <div class="text-xs font-black uppercase tracking-widest text-blue-600">Driver Mode</div>
                        <div class="text-[10px] text-slate-500 font-bold">{{ userName }}</div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="toggleTheme" class="p-2 rounded-xl text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <MoonIcon v-if="!isDarkMode" class="h-5 w-5" />
                        <SunIcon v-else class="h-5 w-5" />
                    </button>
                    <button @click="logout" class="p-2 rounded-xl text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                        <ArrowRightOnRectangleIcon class="h-5 w-5" />
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="px-4 py-4">
            <slot />
        </main>

        <!-- Bottom Navigation -->
        <nav class="fixed bottom-0 left-0 right-0 z-50 bg-white/90 dark:bg-slate-900/90 backdrop-blur-xl border-t border-slate-200 dark:border-slate-800 safe-area-bottom">
            <div class="flex items-center justify-around py-2">
                <Link
                    v-for="item in navItems"
                    :key="item.name"
                    :href="item.href"
                    class="flex flex-col items-center gap-1 px-6 py-2 rounded-xl transition-all"
                    :class="isActive(item.href) 
                        ? 'text-blue-600 bg-blue-50 dark:bg-blue-900/20 scale-105' 
                        : 'text-slate-400 hover:text-slate-600'"
                >
                    <component :is="item.icon" class="h-6 w-6" />
                    <span class="text-[10px] font-bold uppercase tracking-widest">{{ item.name }}</span>
                </Link>
            </div>
        </nav>
    </div>
</template>

<style scoped>
.safe-area-bottom {
    padding-bottom: env(safe-area-inset-bottom, 0px);
}
</style>
