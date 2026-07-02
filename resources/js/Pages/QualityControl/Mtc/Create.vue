<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    SparklesIcon, 
    ArrowUpTrayIcon,
    DocumentIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline';
import axios from 'axios';
import Swal from 'sweetalert2';

const isDragging = ref(false);
const isUploading = ref(false);
const uploadProgress = ref(0);
const statusMessage = ref('Menunggu file...');
const fileInput = ref(null);

const triggerFileInput = () => {
    fileInput.value.click();
};

const handleDragOver = (e) => {
    e.preventDefault();
    isDragging.value = true;
};

const handleDragLeave = () => {
    isDragging.value = false;
};

const handleDrop = (e) => {
    e.preventDefault();
    isDragging.value = false;
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        uploadFile(files[0]);
    }
};

const handleFileSelect = (e) => {
    const files = e.target.files;
    if (files.length > 0) {
        uploadFile(files[0]);
    }
};

// Clipboard / Ctrl+V Paste handler
const handlePaste = (e) => {
    const items = (e.clipboardData || e.originalEvent.clipboardData).items;
    for (let index in items) {
        const item = items[index];
        if (item.kind === 'file') {
            const blob = item.getAsFile();
            // Create a file object with a name
            const file = new File([blob], `mtc_paste_${Date.now()}.png`, { type: blob.type });
            uploadFile(file);
            break;
        }
    }
};

onMounted(() => {
    window.addEventListener('paste', handlePaste);
});

onUnmounted(() => {
    window.removeEventListener('paste', handlePaste);
});

// Upload file to Backend
const uploadFile = (file) => {
    // Validate file type
    const allowedMimeTypes = [
        'application/pdf',
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/webp',
        'image/tiff'
    ];
    
    if (!allowedMimeTypes.includes(file.type)) {
        Swal.fire({
            title: 'Tipe File Tidak Didukung',
            text: 'Harap upload file PDF atau Gambar (JPG, PNG, WebP, TIFF).',
            icon: 'error',
            background: '#0f172a',
            color: '#f8fafc',
        });
        return;
    }

    // Validate size (20MB)
    if (file.size > 20 * 1024 * 1024) {
        Swal.fire({
            title: 'File Terlalu Besar',
            text: 'Maksimum ukuran file adalah 20MB.',
            icon: 'error',
            background: '#0f172a',
            color: '#f8fafc',
        });
        return;
    }

    isUploading.value = true;
    uploadProgress.value = 5;
    statusMessage.value = 'Mengunggah file MTC...';

    const formData = new FormData();
    formData.append('file', file);

    // Call upload API
    axios.post(route('qc.mtc.upload'), formData, {
        headers: {
            'Content-Type': 'multipart/form-data'
        },
        onUploadProgress: (progressEvent) => {
            const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
            // Limit progress bar to 85% until extraction finishes
            uploadProgress.value = Math.min(Math.round(percentCompleted * 0.85), 85);
            if (percentCompleted >= 100) {
                statusMessage.value = 'Menganalisis MTC menggunakan Gemini AI...';
                // Trigger slow increment simulation up to 98%
                simulateProgress();
            }
        }
    }).then((response) => {
        uploadProgress.value = 100;
        statusMessage.value = 'Ekstraksi selesai!';
        
        Swal.fire({
            title: response.data.success ? 'Sukses!' : 'Peringatan',
            text: response.data.message,
            icon: response.data.success ? 'success' : 'warning',
            background: '#0f172a',
            color: '#f8fafc',
            timer: 2000,
            showConfirmButton: false
        }).then(() => {
            if (response.data.redirect) {
                router.visit(response.data.redirect);
            }
        });
    }).catch((error) => {
        isUploading.value = false;
        uploadProgress.value = 0;
        statusMessage.value = 'Gagal.';
        
        const errorMsg = error.response?.data?.message || 'Terjadi kesalahan saat mengupload.';
        Swal.fire({
            title: 'Gagal Mengekstrak',
            text: errorMsg,
            icon: 'error',
            background: '#0f172a',
            color: '#f8fafc',
        });
    });
};

// Simulation progress after 100% upload
let progressInterval;
const simulateProgress = () => {
    clearInterval(progressInterval);
    progressInterval = setInterval(() => {
        if (uploadProgress.value < 97) {
            uploadProgress.value += 1;
        } else {
            clearInterval(progressInterval);
        }
    }, 400);
};

onUnmounted(() => {
    clearInterval(progressInterval);
});
</script>

<template>
    <Head title="Upload Mill Test Certificate" />

    <AppLayout title="Quality Control Dashboard" :render-header="false">
        <div class="p-6 space-y-6 bg-[#030310] min-h-screen text-slate-100 font-sans flex flex-col justify-center items-center">
            
            <!-- Main Card Container -->
            <div class="w-full max-w-2xl bg-slate-950/80 border border-slate-800/80 p-8 rounded-2xl shadow-2xl backdrop-blur-md relative overflow-hidden">
                <!-- Glowing effect -->
                <div class="absolute top-0 right-0 w-64 h-64 bg-cyan-500/5 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -bottom-10 -left-10 w-64 h-64 bg-emerald-500/5 rounded-full blur-3xl pointer-events-none"></div>

                <!-- Back button -->
                <div class="mb-6">
                    <Link
                        :href="route('qc.mtc.index')"
                        class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-500 hover:text-slate-300 font-mono transition"
                    >
                        <ArrowLeftIcon class="w-4 h-4" />
                        Kembali Ke Dasbor
                    </Link>
                </div>

                <!-- Header Info -->
                <div class="text-center space-y-2 mb-8">
                    <div class="inline-flex items-center justify-center p-3 bg-cyan-950/30 border border-cyan-800/30 text-cyan-400 rounded-xl shadow-inner shadow-cyan-500/5">
                        <SparklesIcon class="w-8 h-8 animate-pulse" />
                    </div>
                    <h2 class="text-2xl font-black tracking-wider text-slate-200 uppercase font-mono mt-2">
                        Upload Mill Test Certificate
                    </h2>
                    <p class="text-slate-400 text-xs max-w-md mx-auto">
                        Seret berkas PDF atau gambar hasil scan ke area di bawah ini. Anda juga bisa langsung melakukan <span class="text-cyan-400 font-mono font-bold bg-cyan-950/30 px-1 py-0.5 rounded">Ctrl+V</span> (Paste) dari screenshot clipboard.
                    </p>
                </div>

                <!-- Dropzone/Drag & Drop Area -->
                <div
                    v-if="!isUploading"
                    @dragover="handleDragOver"
                    @dragleave="handleDragLeave"
                    @drop="handleDrop"
                    @click="triggerFileInput"
                    :class="[
                        'border-2 border-dashed rounded-xl p-12 flex flex-col items-center justify-center gap-4 cursor-pointer transition duration-300 min-h-[250px]',
                        isDragging 
                            ? 'border-cyan-400 bg-cyan-500/5 shadow-[0_0_20px_rgba(6,182,212,0.15)] scale-[1.01]' 
                            : 'border-slate-800 hover:border-slate-700 bg-slate-900/30 hover:bg-slate-900/50'
                    ]"
                >
                    <input
                        type="file"
                        ref="fileInput"
                        @change="handleFileSelect"
                        class="hidden"
                        accept=".pdf,.jpg,.jpeg,.png,.webp,.tiff"
                    />
                    
                    <div class="w-16 h-16 bg-slate-850 rounded-full flex items-center justify-center border border-slate-800 shadow-inner">
                        <ArrowUpTrayIcon class="w-7 h-7 text-slate-400" />
                    </div>

                    <div class="text-center space-y-1">
                        <div class="text-sm font-bold text-slate-300">Pilih berkas MTC Anda</div>
                        <div class="text-xs text-slate-500 font-mono">PDF, JPG, PNG, WEBP, atau TIFF (Maks. 20MB)</div>
                    </div>

                    <div class="mt-4 px-4 py-1.5 bg-slate-900/80 border border-slate-800 rounded-full text-[11px] text-slate-400 font-mono flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-cyan-400 animate-pulse"></span>
                        Tip: Mendukung Paste Clipboard Gambar langsung
                    </div>
                </div>

                <!-- Uploading progress overlay -->
                <div v-else class="space-y-6 py-10 flex flex-col items-center justify-center min-h-[250px]">
                    <div class="relative w-24 h-24 flex items-center justify-center">
                        <!-- Orbit animation loader -->
                        <div class="absolute inset-0 border-4 border-slate-900 rounded-full"></div>
                        <div class="absolute inset-0 border-4 border-t-cyan-400 rounded-full animate-spin"></div>
                        <DocumentIcon class="w-8 h-8 text-slate-400 animate-pulse" />
                    </div>

                    <div class="w-full max-w-sm space-y-2">
                        <div class="flex justify-between items-center text-xs font-mono text-slate-400">
                            <span>{{ statusMessage }}</span>
                            <span class="text-cyan-400 font-bold">{{ uploadProgress }}%</span>
                        </div>
                        <div class="w-full h-2 bg-slate-900 rounded-full overflow-hidden border border-slate-800">
                            <div 
                                class="h-full bg-gradient-to-r from-cyan-500 to-emerald-400 shadow-[0_0_10px_rgba(6,182,212,0.5)] transition-all duration-300"
                                :style="{ width: `${uploadProgress}%` }"
                            ></div>
                        </div>
                    </div>
                    
                    <p class="text-slate-500 text-[11px] text-center font-mono max-w-xs">
                        Gemini AI sedang membaca struktur tabel dan teks kecil pada dokumen MTC. Harap tunggu beberapa detik.
                    </p>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
