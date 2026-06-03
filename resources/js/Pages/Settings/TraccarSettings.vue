<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { MapPinIcon, SignalIcon, CheckCircleIcon, XCircleIcon, InformationCircleIcon, ChevronDownIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    settings: Object
});

const page = usePage();

const activeTab = ref('connection');
const activeGuideSection = ref('local');

const toggleGuideSection = (key) => {
    activeGuideSection.value = activeGuideSection.value === key ? null : key;
};

const form = useForm({
    traccar_base_url: props.settings?.traccar_base_url || '',
    traccar_username: props.settings?.traccar_username || '',
    traccar_password: '',
});

const submit = () => {
    form.post(route('settings.traccar.update'), {
        preserveScroll: true,
        onSuccess: () => {
            form.traccar_password = '';
        },
    });
};

const testLoading = ref(false);
const testResult = ref(null);

const canTest = computed(() => {
    return form.traccar_base_url.trim() !== '' && form.traccar_username.trim() !== '' && (props.settings?.has_password || form.traccar_password.trim() !== '');
});

const testConnection = async () => {
    testResult.value = null;

    if (!canTest.value) {
        testResult.value = { success: false, message: 'Lengkapi Base URL, Username, dan Password terlebih dahulu.' };
        return;
    }

    testLoading.value = true;
    try {
        const token = page.props.csrf_token;
        const response = await axios.post(route('settings.traccar.test'), {}, {
            headers: {
                'X-CSRF-TOKEN': token
            }
        });
        testResult.value = response.data;
    } catch (error) {
        testResult.value = {
            success: false,
            message: error.response?.data?.message || 'Terjadi error: ' + error.message,
        };
    } finally {
        testLoading.value = false;
    }
};
</script>

<template>
    <Head title="Traccar Tracking" />

    <AppLayout title="Traccar Tracking">
        <div class="p-6 space-y-6">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-gradient-to-br from-blue-600 to-cyan-600 rounded-xl shadow-lg">
                    <MapPinIcon class="h-6 w-6 text-white" />
                </div>
                <div>
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white">Traccar Tracking</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Konfigurasi koneksi Traccar (Basic Auth)</p>
                </div>
            </div>

            <div class="glass-card rounded-2xl overflow-hidden">
                <div class="p-3 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="activeTab = 'connection'"
                            :class="activeTab === 'connection'
                                ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/20'
                                : 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700'"
                            class="px-4 py-2 rounded-xl text-sm font-black transition-all active:scale-95"
                        >
                            Connection
                        </button>
                        <button
                            type="button"
                            @click="activeTab = 'guide'"
                            :class="activeTab === 'guide'
                                ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-500/20'
                                : 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700'"
                            class="px-4 py-2 rounded-xl text-sm font-black transition-all active:scale-95"
                        >
                            Panduan Setup
                        </button>
                    </div>

                    <div class="flex items-center gap-2">
                        <Link
                            href="/logistics/fleet"
                            class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-sm font-bold text-slate-700 dark:text-slate-200 transition-colors"
                        >
                            Vehicle Fleet
                        </Link>
                        <Link
                            href="/logistics/tracking"
                            class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-sm font-bold text-white shadow-lg shadow-emerald-500/20 transition-colors"
                        >
                            Fleet Tracking
                        </Link>
                    </div>
                </div>

                <div v-show="activeTab === 'connection'">
                <div class="p-4 border-b border-slate-200 dark:border-slate-700">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Connection Settings</h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Contoh Base URL: http://localhost:8082</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="testConnection"
                                :disabled="testLoading"
                                class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-sm font-bold text-white shadow-lg shadow-slate-900/20 transition-all hover:bg-slate-800 active:scale-95 disabled:opacity-60 dark:bg-slate-700 dark:hover:bg-slate-600"
                            >
                                <SignalIcon class="h-5 w-5" />
                                <span>{{ testLoading ? 'Testing...' : 'Test Connection' }}</span>
                            </button>
                            <button
                                type="button"
                                @click="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-bold text-white shadow-lg shadow-blue-500/20 transition-all hover:bg-blue-500 active:scale-95 disabled:opacity-60"
                            >
                                <span>{{ form.processing ? 'Saving...' : 'Save' }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="p-4 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-200 mb-1">Base URL</label>
                            <input
                                v-model="form.traccar_base_url"
                                type="text"
                                placeholder="http://localhost:8082"
                                class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                            />
                            <div v-if="form.errors.traccar_base_url" class="mt-1 text-sm text-red-600">{{ form.errors.traccar_base_url }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-200 mb-1">Username (Email)</label>
                            <input
                                v-model="form.traccar_username"
                                type="text"
                                placeholder="admin@company.com"
                                class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                            />
                            <div v-if="form.errors.traccar_username" class="mt-1 text-sm text-red-600">{{ form.errors.traccar_username }}</div>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between">
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-200 mb-1">Password</label>
                            <span
                                v-if="props.settings?.has_password && form.traccar_password === ''"
                                class="text-xs font-bold text-emerald-700 dark:text-emerald-400"
                            >
                                Password tersimpan
                            </span>
                        </div>
                        <input
                            v-model="form.traccar_password"
                            type="password"
                            placeholder="Isi untuk ganti password"
                            class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                        />
                        <div v-if="form.errors.traccar_password" class="mt-1 text-sm text-red-600">{{ form.errors.traccar_password }}</div>
                    </div>

                    <div v-if="testResult" class="rounded-xl border p-4" :class="testResult.success ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-700/30 dark:bg-emerald-500/10' : 'border-red-200 bg-red-50 dark:border-red-700/30 dark:bg-red-500/10'">
                        <div class="flex items-start gap-3">
                            <CheckCircleIcon v-if="testResult.success" class="h-6 w-6 text-emerald-600 dark:text-emerald-400 mt-0.5" />
                            <XCircleIcon v-else class="h-6 w-6 text-red-600 dark:text-red-400 mt-0.5" />
                            <div class="flex-1">
                                <div class="font-bold" :class="testResult.success ? 'text-emerald-800 dark:text-emerald-200' : 'text-red-800 dark:text-red-200'">
                                    {{ testResult.message }}
                                </div>
                                <div v-if="testResult.success && typeof testResult.devices !== 'undefined'" class="text-sm mt-1 text-slate-600 dark:text-slate-300">
                                    Devices terdeteksi: {{ testResult.devices }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-xs text-slate-500 dark:text-slate-400">
                        Traccar menggunakan Basic Auth (username + password). Pastikan user Traccar yang dipakai punya akses melihat Devices & Positions.
                    </div>
                </div>
                </div>
            </div>

            <div v-show="activeTab === 'guide'" class="glass-card rounded-2xl overflow-hidden">
                <div class="p-4 border-b border-slate-200 dark:border-slate-700">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-gradient-to-br from-emerald-600 to-teal-600 rounded-xl shadow-lg">
                            <InformationCircleIcon class="h-5 w-5 text-white" />
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Panduan Setup</h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Langkah konfigurasi Traccar (Local & VPS) yang mudah diikuti</p>
                        </div>
                    </div>
                </div>

                <div class="p-4 space-y-6 text-sm text-slate-700 dark:text-slate-200">
                    <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white/50 dark:bg-slate-900/30 p-4">
                        <div class="font-black text-slate-900 dark:text-white">Prinsip penting</div>
                        <div class="mt-2 space-y-1 text-slate-600 dark:text-slate-300">
                            <div>1) ERP memanggil Traccar dari backend Laravel (server-side), bukan dari browser.</div>
                            <div>2) Base URL harus bisa diakses dari mesin server ERP.</div>
                            <div>3) Karena ERP dan Traccar kamu berada di 1 VPS yang sama, Base URL paling aman adalah <span class="font-black">http://127.0.0.1:8082</span>.</div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white/50 dark:bg-slate-900/30 overflow-hidden">
                            <button
                                type="button"
                                @click="toggleGuideSection('local')"
                                class="w-full px-4 py-3 flex items-center justify-between gap-4 hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors"
                            >
                                <div class="text-left">
                                    <div class="text-xs font-black uppercase tracking-[0.2em] text-slate-500">Local (Laragon + Docker)</div>
                                    <div class="text-sm font-bold text-slate-900 dark:text-white mt-1">Setup Traccar di PC/Laptop</div>
                                </div>
                                <ChevronDownIcon class="h-5 w-5 text-slate-500 transition-transform" :class="activeGuideSection === 'local' ? 'rotate-180' : ''" />
                            </button>
                            <div v-show="activeGuideSection === 'local'" class="px-4 pb-4">
                                <div class="mt-3 space-y-3 text-slate-600 dark:text-slate-300">
                                    <div class="font-bold text-slate-900 dark:text-white">A. Jalankan Traccar via Docker</div>
                                    <div>1) Install Docker Desktop.</div>
                                    <div>2) Buat folder mis. <span class="font-black">C:\traccar</span>.</div>
                                    <div>3) Buat file <span class="font-black">docker-compose.yml</span>:</div>
                                    <pre class="mt-2 text-xs whitespace-pre overflow-auto rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-950 text-slate-100 p-4">services:
  db:
    image: postgres:16
    environment:
      POSTGRES_DB: traccar
      POSTGRES_USER: traccar
      POSTGRES_PASSWORD: traccar_password
    volumes:
      - traccar_db:/var/lib/postgresql/data
    restart: unless-stopped

  traccar:
    image: traccar/traccar:latest
    depends_on:
      - db
    ports:
      - "8082:8082"
      - "5000-5150:5000-5150"
    environment:
      DATABASE_DRIVER: postgres
      DATABASE_URL: jdbc:postgresql://db:5432/traccar
      DATABASE_USER: traccar
      DATABASE_PASSWORD: traccar_password
    volumes:
      - traccar_logs:/opt/traccar/logs
    restart: unless-stopped

volumes:
  traccar_db:
  traccar_logs:</pre>
                                    <div>4) Jalankan:</div>
                                    <pre class="text-xs whitespace-pre overflow-auto rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-950 text-slate-100 p-4">cd C:\traccar
docker compose up -d</pre>
                                    <div>5) Buka UI Traccar: <span class="font-black">http://localhost:8082</span></div>

                                    <div class="font-bold text-slate-900 dark:text-white mt-4">B. Buat User Integrasi + Device</div>
                                    <div>1) Buat user integrasi (contoh): <span class="font-black">erp-integration@local</span></div>
                                    <div>2) Tambah device di menu Devices.</div>
                                    <div>3) Pastikan user integrasi diberi akses/permission ke device.</div>

                                    <div class="font-bold text-slate-900 dark:text-white mt-4">C. Isi Settings di ERP</div>
                                    <div>Base URL: <span class="font-black">http://127.0.0.1:8082</span></div>
                                    <div>Username/Password: user Traccar integrasi</div>
                                    <div>Klik <span class="font-black">Test Connection</span> lalu <span class="font-black">Save</span>.</div>

                                    <div class="font-bold text-slate-900 dark:text-white mt-4">D. Link Vehicle ↔ Device</div>
                                    <div>Masuk <span class="font-black">Vehicle Fleet</span> → edit kendaraan → pilih Traccar Device.</div>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white/50 dark:bg-slate-900/30 overflow-hidden">
                            <button
                                type="button"
                                @click="toggleGuideSection('vps')"
                                class="w-full px-4 py-3 flex items-center justify-between gap-4 hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors"
                            >
                                <div class="text-left">
                                    <div class="text-xs font-black uppercase tracking-[0.2em] text-slate-500">Production (1 VPS Domainesia)</div>
                                    <div class="text-sm font-bold text-slate-900 dark:text-white mt-1">Setup Traccar di server</div>
                                </div>
                                <ChevronDownIcon class="h-5 w-5 text-slate-500 transition-transform" :class="activeGuideSection === 'vps' ? 'rotate-180' : ''" />
                            </button>
                            <div v-show="activeGuideSection === 'vps'" class="px-4 pb-4">
                                <div class="mt-3 space-y-3 text-slate-600 dark:text-slate-300">
                                    <div class="font-bold text-slate-900 dark:text-white">A. Jalankan Traccar di VPS (Docker)</div>
                                    <div>1) Buat folder <span class="font-black">/opt/traccar</span> dan taruh <span class="font-black">docker-compose.yml</span> (boleh sama seperti local).</div>
                                    <div>2) Jalankan:</div>
                                    <pre class="text-xs whitespace-pre overflow-auto rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-950 text-slate-100 p-4">cd /opt/traccar
docker compose up -d
docker ps</pre>

                                    <div class="font-bold text-slate-900 dark:text-white mt-4">B. Base URL yang dipakai ERP</div>
                                    <div>Karena ERP & Traccar satu VPS, isi Base URL di ERP dengan:</div>
                                    <div class="font-black text-slate-900 dark:text-white">http://127.0.0.1:8082</div>
                                    <div class="text-xs">Tidak tergantung domain Cloudflare ERP (<span class="font-black">erp.nsp.my.id</span>) dan minim masalah jaringan/firewall.</div>

                                    <div class="font-bold text-slate-900 dark:text-white mt-4">C. Port & Firewall (wajib kalau device kirim data dari luar)</div>
                                    <div>1) UI/API: 8082 (sebaiknya tidak dipublikkan, cukup internal).</div>
                                    <div>2) Port protocol tracking: default Traccar banyak port <span class="font-black">5000-5150</span> (buka sesuai kebutuhan device/app tracker).</div>

                                    <div class="font-bold text-slate-900 dark:text-white mt-4">D. (Opsional) Domain khusus Traccar UI</div>
                                    <div>Buat subdomain mis. <span class="font-black">traccar.nsp.my.id</span> lalu reverse proxy ke <span class="font-black">127.0.0.1:8082</span>.</div>
                                    <pre class="text-xs whitespace-pre overflow-auto rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-950 text-slate-100 p-4">server {
  server_name traccar.nsp.my.id;
  location / {
    proxy_pass http://127.0.0.1:8082;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
  }
}</pre>

                                    <div class="font-bold text-slate-900 dark:text-white mt-4">E. Validasi</div>
                                    <div>1) Settings → Traccar Tracking → Test Connection.</div>
                                    <div>2) Vehicle Fleet → link device.</div>
                                    <div>3) Logistics → Fleet Tracking → pastikan marker muncul.</div>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white/50 dark:bg-slate-900/30 overflow-hidden">
                            <button
                                type="button"
                                @click="toggleGuideSection('troubleshooting')"
                                class="w-full px-4 py-3 flex items-center justify-between gap-4 hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors"
                            >
                                <div class="text-left">
                                    <div class="text-xs font-black uppercase tracking-[0.2em] text-slate-500">Troubleshooting</div>
                                    <div class="text-sm font-bold text-slate-900 dark:text-white mt-1">Masalah umum & solusi cepat</div>
                                </div>
                                <ChevronDownIcon class="h-5 w-5 text-slate-500 transition-transform" :class="activeGuideSection === 'troubleshooting' ? 'rotate-180' : ''" />
                            </button>
                            <div v-show="activeGuideSection === 'troubleshooting'" class="px-4 pb-4">
                                <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3 text-slate-600 dark:text-slate-300">
                                    <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white/50 dark:bg-slate-950/30 p-3">
                                        <div class="font-bold text-slate-900 dark:text-white">Test Connection sukses tapi devices = 0</div>
                                        <div>Device belum dibuat, atau user integrasi belum diberi akses ke device.</div>
                                    </div>
                                    <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white/50 dark:bg-slate-950/30 p-3">
                                        <div class="font-bold text-slate-900 dark:text-white">401 Unauthorized</div>
                                        <div>Username/password salah, atau password berubah tapi ERP belum di-update.</div>
                                    </div>
                                    <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white/50 dark:bg-slate-950/30 p-3">
                                        <div class="font-bold text-slate-900 dark:text-white">Timeout / Connection refused</div>
                                        <div>Traccar belum running, port 8082 tidak listen, atau Base URL bukan alamat yang reachable dari server ERP.</div>
                                    </div>
                                    <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white/50 dark:bg-slate-950/30 p-3">
                                        <div class="font-bold text-slate-900 dark:text-white">Fleet Tracking kosong</div>
                                        <div>Kendaraan belum di-link ke device, atau device belum mengirim position ke Traccar.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
