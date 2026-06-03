<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import DriverLayout from '@/Layouts/DriverLayout.vue';
import { Html5Qrcode } from 'html5-qrcode';
import {
    QrCodeIcon,
    CheckCircleIcon,
    XCircleIcon,
    ArrowPathIcon,
    TruckIcon,
    UserIcon,
    MapPinIcon,
    CubeIcon,
    CameraIcon,
} from '@heroicons/vue/24/outline';

const page = usePage();
const scannerRef = ref(null);
let html5QrCode = null;

const scanning = ref(false);
const scannedDo = ref(null);
const scanError = ref('');
const confirming = ref(false);
const lookingUp = ref(false);

// New states for travel allowance and expenses
const requiresCosts = ref(false);
const travelAllowance = ref(0);
const odometerEnd = ref('');
const realFuelCost = ref(0);
const realTollCost = ref(0);
const realOtherCost = ref(0);
const receiptFile = ref(null);
const receiptPreviewUrl = ref(null);

const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        receiptFile.value = file;
        receiptPreviewUrl.value = URL.createObjectURL(file);
    }
};

const startScanner = async () => {
    scanError.value = '';
    scannedDo.value = null;
    scanning.value = true;

    try {
        html5QrCode = new Html5Qrcode('qr-reader');
        await html5QrCode.start(
            { facingMode: 'environment' },
            {
                fps: 10,
                qrbox: { width: 250, height: 250 },
            },
            onScanSuccess,
            () => {} // ignore scan failures
        );
    } catch (err) {
        scanError.value = 'Gagal mengakses kamera: ' + err.message;
        scanning.value = false;
    }
};

const stopScanner = async () => {
    if (html5QrCode && html5QrCode.isScanning) {
        await html5QrCode.stop();
    }
    scanning.value = false;
};

const onScanSuccess = async (decodedText) => {
    // Stop scanning immediately
    await stopScanner();
    lookingUp.value = true;
    scanError.value = '';

    try {
        // Extract UUID from URL (e.g. /v/do/{uuid}) or use raw text as UUID
        let uuid = decodedText;
        const urlMatch = decodedText.match(/\/v\/do\/([a-f0-9-]+)/i);
        if (urlMatch) {
            uuid = urlMatch[1];
        }
        // Also try matching just a UUID/numeric ID
        const idMatch = decodedText.match(/([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}|\d+)/i);
        if (idMatch) {
            uuid = idMatch[1];
        }

        const response = await fetch(route('driver.lookup'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': page.props.csrf_token || document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ uuid }),
        });

        const data = await response.json();

        if (!response.ok) {
            scanError.value = data.error || 'DO tidak ditemukan.';
        } else {
            scannedDo.value = data.deliveryOrder;
            requiresCosts.value = data.requires_costs || false;
            travelAllowance.value = data.travel_allowance || 0;
            
            // Reset input values
            odometerEnd.value = '';
            realFuelCost.value = 0;
            realTollCost.value = 0;
            realOtherCost.value = 0;
            receiptFile.value = null;
            receiptPreviewUrl.value = null;
        }
    } catch (err) {
        scanError.value = 'Error: ' + err.message;
    } finally {
        lookingUp.value = false;
    }
};

const confirmArrival = () => {
    if (!scannedDo.value) return;

    if (requiresCosts.value) {
        if (!odometerEnd.value || odometerEnd.value <= 0) {
            alert('Harap isi Odometer Akhir dengan benar.');
            return;
        }
        if (realFuelCost.value === '' || realFuelCost.value < 0) {
            alert('Harap isi Biaya Solar dengan benar.');
            return;
        }
        if (realTollCost.value === '' || realTollCost.value < 0) {
            alert('Harap isi Biaya Tol dengan benar.');
            return;
        }
        if (realOtherCost.value === '' || realOtherCost.value < 0) {
            alert('Harap isi Biaya Lainnya dengan benar.');
            return;
        }
    }

    if (!confirm('📍 KONFIRMASI SAMPAI\n\nDO: ' + scannedDo.value.do_number + '\nCustomer: ' + (scannedDo.value.customer?.name || '-') + '\n\nBarang sudah sampai di lokasi?\nStatus akan berubah menjadi DELIVERED.')) return;

    confirming.value = true;
    
    const formData = {};
    if (requiresCosts.value) {
        formData.odometer_end = odometerEnd.value;
        formData.real_fuel_cost = realFuelCost.value;
        formData.real_toll_cost = realTollCost.value;
        formData.real_other_cost = realOtherCost.value;
        if (receiptFile.value) {
            formData.image = receiptFile.value;
        }
    }

    router.post(route('confirm', scannedDo.value.id), {
        _method: 'PATCH',
        ...formData
    }, {
        onFinish: () => confirming.value = false,
        onSuccess: () => {
            scannedDo.value = null;
            requiresCosts.value = false;
            travelAllowance.value = 0;
            odometerEnd.value = '';
            realFuelCost.value = 0;
            realTollCost.value = 0;
            realOtherCost.value = 0;
            receiptFile.value = null;
            receiptPreviewUrl.value = null;
        },
    });
};

const resetScanner = () => {
    scannedDo.value = null;
    scanError.value = '';
    confirming.value = false;
    requiresCosts.value = false;
    travelAllowance.value = 0;
    odometerEnd.value = '';
    realFuelCost.value = 0;
    realTollCost.value = 0;
    realOtherCost.value = 0;
    receiptFile.value = null;
    receiptPreviewUrl.value = null;
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

onBeforeUnmount(async () => {
    await stopScanner();
});
</script>

<template>
    <DriverLayout title="Scan QR">
        <div class="max-w-md mx-auto">
            <!-- Header -->
            <div class="text-center mb-6">
                <div class="w-16 h-16 mx-auto bg-blue-600/10 rounded-3xl flex items-center justify-center mb-3">
                    <QrCodeIcon class="h-8 w-8 text-blue-600" />
                </div>
                <h1 class="text-lg font-bold text-slate-900 dark:text-white">Scan Surat Jalan</h1>
                <p class="text-xs text-slate-500 mt-1">Arahkan kamera ke QR code pada Surat Jalan</p>
            </div>

            <!-- Scanner Area -->
            <div v-if="!scannedDo && !scanError" class="space-y-4">
                <div 
                    id="qr-reader"
                    class="rounded-2xl overflow-hidden border-2 border-dashed border-blue-300 dark:border-blue-700 bg-slate-100 dark:bg-slate-800 min-h-[300px]"
                    :class="scanning ? 'border-solid border-blue-500' : ''"
                ></div>

                <button
                    v-if="!scanning"
                    @click="startScanner"
                    class="w-full py-4 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-sm font-black uppercase tracking-wide shadow-lg shadow-blue-500/30 hover:from-blue-500 hover:to-indigo-500 active:scale-95 transition-all flex items-center justify-center gap-2"
                >
                    <CameraIcon class="h-5 w-5" />
                    BUKA KAMERA
                </button>

                <button
                    v-else
                    @click="stopScanner"
                    class="w-full py-4 rounded-2xl bg-gradient-to-r from-red-600 to-red-500 text-white text-sm font-bold shadow-lg shadow-red-500/30 hover:from-red-500 hover:to-red-400 active:scale-95 transition-all flex items-center justify-center gap-2"
                >
                    <XCircleIcon class="h-5 w-5" />
                    TUTUP KAMERA
                </button>

                <div v-if="lookingUp" class="text-center py-6">
                    <ArrowPathIcon class="h-8 w-8 text-blue-500 animate-spin mx-auto mb-2" />
                    <p class="text-sm text-slate-500 font-bold">Mencari DO...</p>
                </div>
            </div>

            <!-- Scan Error -->
            <div v-if="scanError" class="space-y-4">
                <div class="bg-red-50 dark:bg-red-900/20 rounded-2xl p-6 text-center border border-red-200 dark:border-red-800/30">
                    <XCircleIcon class="h-10 w-10 text-red-400 mx-auto mb-3" />
                    <p class="text-sm font-bold text-red-600 dark:text-red-400">{{ scanError }}</p>
                </div>
                <button
                    @click="resetScanner"
                    class="w-full py-3 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-sm font-bold hover:bg-slate-200 transition-all flex items-center justify-center gap-2"
                >
                    <ArrowPathIcon class="h-4 w-4" />
                    Scan Ulang
                </button>
            </div>

            <!-- Scanned Result -->
            <div v-if="scannedDo" class="space-y-4">
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-emerald-300 dark:border-emerald-700 shadow-lg">
                    <div class="flex items-center gap-2 mb-4">
                        <CheckCircleIcon class="h-5 w-5 text-emerald-500" />
                        <span class="text-xs font-black uppercase tracking-widest text-emerald-600">QR Terdeteksi</span>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">DO Number</div>
                            <div class="text-lg font-black text-slate-900 dark:text-white">{{ scannedDo.do_number }}</div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Customer</div>
                                <div class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ scannedDo.customer?.name }}</div>
                            </div>
                            <div>
                                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Tanggal</div>
                                <div class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ formatDate(scannedDo.delivery_date) }}</div>
                            </div>
                        </div>

                        <div v-if="scannedDo.shipping_address">
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Alamat</div>
                            <div class="text-xs text-slate-600 dark:text-slate-400 mt-0.5">{{ scannedDo.shipping_address }}</div>
                        </div>

                        <div class="flex items-center gap-3 text-[10px] text-slate-500">
                            <span v-if="scannedDo.vehicle_number" class="flex items-center gap-1">
                                <TruckIcon class="h-3 w-3" /> {{ scannedDo.vehicle_number }}
                            </span>
                            <span class="flex items-center gap-1">
                                <CubeIcon class="h-3 w-3" /> {{ scannedDo.items?.length || 0 }} items
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Form Biaya Operasional Perjalanan -->
                <div v-if="requiresCosts" class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-amber-300 dark:border-amber-700 shadow-lg space-y-4">
                    <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-700">
                        <div class="w-8 h-8 rounded-lg bg-amber-500/10 flex items-center justify-center">
                            <TruckIcon class="h-5 w-5 text-amber-500" />
                        </div>
                        <div>
                            <span class="text-xs font-black uppercase tracking-widest text-amber-600 block">Laporan Biaya Perjalanan</span>
                            <span class="text-[10px] text-slate-400">Pemberhentian Terakhir (Uang Jalan: Rp {{ travelAllowance.toLocaleString('id-ID') }})</span>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1">Odometer Akhir (KM)</label>
                            <input 
                                type="number" 
                                v-model.number="odometerEnd" 
                                placeholder="Masukkan Odometer Akhir"
                                class="w-full px-4 py-3 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500 font-bold"
                                required
                            />
                        </div>

                        <div class="grid grid-cols-3 gap-2">
                            <div>
                                <label class="block text-[9px] font-black uppercase tracking-wider text-slate-400 mb-1">Solar (Rp)</label>
                                <input 
                                    type="number" 
                                    v-model.number="realFuelCost" 
                                    placeholder="0"
                                    class="w-full px-2 py-3 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500 font-bold"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-[9px] font-black uppercase tracking-wider text-slate-400 mb-1">Tol (Rp)</label>
                                <input 
                                    type="number" 
                                    v-model.number="realTollCost" 
                                    placeholder="0"
                                    class="w-full px-2 py-3 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500 font-bold"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-[9px] font-black uppercase tracking-wider text-slate-400 mb-1">Lainnya (Rp)</label>
                                <input 
                                    type="number" 
                                    v-model.number="realOtherCost" 
                                    placeholder="0"
                                    class="w-full px-2 py-3 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500 font-bold"
                                    required
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1">Unggah Struk / Bukti Pengeluaran</label>
                            <div class="flex items-center gap-3">
                                <label class="flex-1 flex flex-col items-center justify-center px-4 py-4 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-xl cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-900/50 transition-all">
                                    <CameraIcon class="h-6 w-6 text-slate-400 mb-1" />
                                    <span class="text-xs text-slate-500 font-bold">Ambil Foto Struk</span>
                                    <input type="file" accept="image/*" capture="environment" @change="handleFileChange" class="hidden" />
                                </label>
                                <div v-if="receiptPreviewUrl" class="w-16 h-16 rounded-xl border border-slate-200 dark:border-slate-750 overflow-hidden relative group shrink-0">
                                    <img :src="receiptPreviewUrl" class="w-full h-full object-cover" />
                                    <button 
                                        type="button" 
                                        @click="receiptFile = null; receiptPreviewUrl = null" 
                                        class="absolute inset-0 bg-red-500/85 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
                                    >
                                        <XCircleIcon class="h-5 w-5 text-white" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <button
                    @click="confirmArrival"
                    :disabled="confirming"
                    class="w-full py-4 rounded-2xl bg-gradient-to-r from-emerald-600 to-emerald-500 text-white text-base font-black uppercase tracking-wide shadow-lg shadow-emerald-500/30 hover:from-emerald-500 hover:to-emerald-400 active:scale-95 transition-all disabled:opacity-50 flex items-center justify-center gap-2"
                >
                    <ArrowPathIcon v-if="confirming" class="h-5 w-5 animate-spin" />
                    <MapPinIcon v-else class="h-5 w-5" />
                    {{ confirming ? 'Memproses...' : 'KONFIRMASI SAMPAI' }}
                </button>

                <button
                    @click="resetScanner"
                    class="w-full py-3 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-sm font-bold hover:bg-slate-200 transition-all flex items-center justify-center gap-2"
                >
                    <ArrowPathIcon class="h-4 w-4" />
                    Scan Ulang
                </button>
            </div>
        </div>
    </DriverLayout>
</template>
