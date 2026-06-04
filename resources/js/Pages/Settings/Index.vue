<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    Cog6ToothIcon,
    BuildingOffice2Icon,
    GlobeAltIcon,
    BellIcon,
    ShieldCheckIcon,
    SparklesIcon,
} from '@heroicons/vue/24/outline';

const settingsSections = [
    {
        title: 'Company Settings',
        description: 'Manage company information and branding',
        icon: BuildingOffice2Icon,
        href: route('settings.company'),
        color: 'text-blue-400',
        bg: 'bg-blue-500/10',
    },
    {
        title: 'Regional Settings',
        description: 'Configure timezone, language, and currency',
        icon: GlobeAltIcon,
        href: route('settings.regional'),
        color: 'text-emerald-400',
        bg: 'bg-emerald-500/10',
    },
    {
        title: 'Notification Preferences',
        description: 'Manage email and push notification settings',
        icon: BellIcon,
        href: route('settings.preferences'),
        color: 'text-amber-400',
        bg: 'bg-amber-500/10',
    },
    {
        title: 'Security & Access',
        description: 'Manage roles, permissions, and security settings',
        icon: ShieldCheckIcon,
        href: route('settings.roles'),
        color: 'text-red-400',
        bg: 'bg-red-500/10',
    },
    {
        title: 'AI Configuration',
        description: 'Configure Gemini AI engine and API keys',
        icon: SparklesIcon,
        href: route('settings.ai.index'),
        color: 'text-amber-400',
        bg: 'bg-amber-500/10',
    },
];
</script>

<template>
    <Head title="Settings" />
    
    <AppLayout title="Settings">
        <div class="max-w-full px-4 sm:px-6 lg:px-8 mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <Link 
                    v-for="section in settingsSections" 
                    :key="section.title"
                    :href="section.href"
                    class="group glass-card rounded-2xl p-6 hover:border-slate-200 dark:border-slate-700 transition-all hover:shadow-lg hover:shadow-slate-900/50"
                >
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl" :class="[section.bg, section.color]">
                            <component :is="section.icon" class="h-6 w-6" />
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white group-hover:text-blue-400 transition-colors">
                                {{ section.title }}
                            </h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">{{ section.description }}</p>
                        </div>
                    </div>
                </Link>
            </div>

            <!-- App Info -->
            <div class="mt-8 bg-white dark:bg-slate-950/50 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 text-center">
                <div class="flex items-center justify-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center">
                        <Cog6ToothIcon class="h-5 w-5 text-slate-900 dark:text-white" />
                    </div>
                    <span class="text-xl font-bold text-slate-900 dark:text-white">JICOS ERP</span>
                </div>
                <p class="text-sm text-slate-500">Version 1.0.0</p>
                <p class="text-xs text-slate-600 mt-2">© 2026 PT JIDOKA. All rights reserved.</p>
            </div>
        </div>
    </AppLayout>
</template>



