<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { DevicePhoneMobileIcon, CheckIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    roles: Array,
    config: Object,
    navCatalog: Array,
    quickCatalog: Array,
});

const defaultNav = ['home', 'menu', 'quick', 'activity', 'profile'];
const defaultQuick = ['pr', 'po', 'grn', 'pi', 'so', 'movement'];

const roleOptions = computed(() => ['default', ...(props.roles ?? [])]);
const selectedRole = ref('default');

const enabled = ref(!!props.config?.enabled);
const navByRole = ref(JSON.parse(JSON.stringify(props.config?.nav_by_role ?? {})));
const quickByRole = ref(JSON.parse(JSON.stringify(props.config?.quick_by_role ?? {})));

const currentNav = ref([...defaultNav]);
const currentQuick = ref([...defaultQuick]);

const isOverridden = computed(() => {
    const r = selectedRole.value;
    return Object.prototype.hasOwnProperty.call(navByRole.value, r) || Object.prototype.hasOwnProperty.call(quickByRole.value, r);
});

const loadRole = (roleName) => {
    currentNav.value = [...(navByRole.value?.[roleName] ?? defaultNav)];
    currentQuick.value = [...(quickByRole.value?.[roleName] ?? defaultQuick)];
    while (currentQuick.value.length < 6) currentQuick.value.push('');
    currentQuick.value = currentQuick.value.slice(0, 6);
};

watch(selectedRole, (r) => loadRole(r), { immediate: true });

const navOptionsForIndex = (idx) => {
    const used = new Set(currentNav.value.filter((k, j) => j !== idx));
    return (props.navCatalog ?? []).filter((x) => !used.has(x.key));
};

const quickOptionsForIndex = (idx) => {
    const used = new Set(currentQuick.value.filter((k, j) => j !== idx && k));
    return (props.quickCatalog ?? []).filter((x) => !used.has(x.key));
};

const save = () => {
    navByRole.value[selectedRole.value] = [...currentNav.value];
    quickByRole.value[selectedRole.value] = currentQuick.value.filter((x) => !!x);

    router.put(
        route('settings.mobile-navbar.update'),
        {
            enabled: enabled.value,
            nav_by_role: navByRole.value,
            quick_by_role: quickByRole.value,
        },
        {
            preserveScroll: true,
        }
    );
};

const resetRole = () => {
    const role = selectedRole.value;
    if (role === 'default') {
        currentNav.value = [...defaultNav];
        currentQuick.value = [...defaultQuick];
        return;
    }

    delete navByRole.value[role];
    delete quickByRole.value[role];
    loadRole(role);
};
</script>

<template>
    <Head title="Mobile Navbar" />

    <AppLayout title="Mobile Navbar">
        <div class="max-w-5xl mx-auto space-y-6">
            <div class="glass-card rounded-2xl p-6">
                <div class="flex items-start gap-4">
                    <div class="p-3 rounded-2xl bg-cyan-500/10 text-cyan-300">
                        <DevicePhoneMobileIcon class="h-7 w-7" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-xl font-black text-slate-900 dark:text-white">Setting Navbar Bawah (Mobile)</div>
                        <div class="text-sm text-slate-500 dark:text-slate-400">
                            Atur 5 menu navbar bawah dan quick actions berdasarkan role.
                        </div>
                    </div>
                </div>

                <div class="mt-5 flex items-center justify-between gap-4 rounded-2xl border border-slate-200 dark:border-slate-700 bg-white/60 dark:bg-slate-950/50 p-4">
                    <div>
                        <div class="font-bold text-slate-900 dark:text-white">Aktifkan Role-based Navbar</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400">Jika nonaktif, sistem akan selalu pakai layout default.</div>
                    </div>
                    <button
                        type="button"
                        @click="enabled = !enabled"
                        :class="[
                            'relative inline-flex h-7 w-12 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2',
                            enabled ? 'bg-cyan-600' : 'bg-slate-300 dark:bg-slate-600',
                        ]"
                    >
                        <span
                            :class="[
                                'inline-block h-5 w-5 transform rounded-full bg-white shadow-lg transition-transform',
                                enabled ? 'translate-x-6' : 'translate-x-1',
                            ]"
                        />
                    </button>
                </div>
            </div>

            <div class="glass-card rounded-2xl p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-200">Role</label>
                        <select v-model="selectedRole" class="mt-1 w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-900">
                            <option v-for="r in roleOptions" :key="r" :value="r">{{ r }}</option>
                        </select>
                        <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            Status: <span class="font-bold" :class="isOverridden ? 'text-cyan-400' : 'text-slate-500'">{{ isOverridden ? 'override' : 'mengikuti default' }}</span>
                        </div>
                    </div>
                    <div class="flex gap-2 justify-end">
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 dark:border-slate-700 px-4 py-2 text-sm font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition"
                            @click="resetRole"
                        >
                            <TrashIcon class="h-4 w-4" />
                            Reset
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-xl bg-cyan-600 hover:bg-cyan-500 px-4 py-2 text-sm font-black text-white transition"
                            @click="save"
                        >
                            <CheckIcon class="h-4 w-4" />
                            Simpan
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="glass-card rounded-2xl p-6">
                    <div class="text-sm font-black tracking-widest uppercase text-slate-700 dark:text-slate-300">Navbar (5 Menu)</div>
                    <div class="mt-3 space-y-3">
                        <div v-for="idx in 5" :key="idx" class="grid grid-cols-12 gap-3 items-center">
                            <div class="col-span-3 text-xs font-bold text-slate-500 dark:text-slate-400">Slot {{ idx }}</div>
                            <div class="col-span-9">
                                <select
                                    v-model="currentNav[idx - 1]"
                                    class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-900"
                                >
                                    <option v-for="opt in navOptionsForIndex(idx - 1)" :key="opt.key" :value="opt.key">
                                        {{ opt.name }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="glass-card rounded-2xl p-6">
                    <div class="text-sm font-black tracking-widest uppercase text-slate-700 dark:text-slate-300">Quick Actions (maks 6)</div>
                    <div class="mt-3 space-y-3">
                        <div v-for="idx in 6" :key="idx" class="grid grid-cols-12 gap-3 items-center">
                            <div class="col-span-3 text-xs font-bold text-slate-500 dark:text-slate-400">Slot {{ idx }}</div>
                            <div class="col-span-9">
                                <select
                                    v-model="currentQuick[idx - 1]"
                                    class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-900"
                                >
                                    <option value="">(Kosong)</option>
                                    <option v-for="opt in quickOptionsForIndex(idx - 1)" :key="opt.key" :value="opt.key">
                                        {{ opt.name }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-slate-500 dark:text-slate-400">
                        Menu dan quick actions tetap akan di-filter otomatis oleh permission user (kalau tidak punya akses, item tidak ditampilkan).
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
