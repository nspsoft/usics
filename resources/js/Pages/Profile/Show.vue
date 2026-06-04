<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    UserCircleIcon,
    KeyIcon,
    CheckCircleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    user: Object,
});

const photoPreview = ref(null);
const photoInput = ref(null);
const signaturePreview = ref(null);
const signatureInput = ref(null);

// Signature Pad state
const signatureMode = ref('draw'); // 'draw' or 'upload'
const signatureCanvas = ref(null);
const isDrawing = ref(false);
const hasDrawn = ref(false);
const penColor = ref('#1e293b');
const penSize = ref(2.5);
const strokes = ref([]);      // Array of strokes, each stroke is array of points
const currentStroke = ref([]); // Current stroke being drawn
let canvasCtx = null;

const profileForm = useForm({
    _method: 'PUT',
    name: props.user?.name || '',
    email: props.user?.email || '',
    photo: null,
    signature: null,
    signature_data: null,
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

// === Signature Pad Functions ===
const initCanvas = () => {
    const canvas = signatureCanvas.value;
    if (!canvas) return;
    canvasCtx = canvas.getContext('2d');
    // Set canvas internal resolution
    const rect = canvas.getBoundingClientRect();
    canvas.width = rect.width * 2;
    canvas.height = rect.height * 2;
    canvasCtx.scale(2, 2);
    canvasCtx.lineCap = 'round';
    canvasCtx.lineJoin = 'round';
    clearCanvas();
};

const getPos = (e) => {
    const canvas = signatureCanvas.value;
    const rect = canvas.getBoundingClientRect();
    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
    const clientY = e.touches ? e.touches[0].clientY : e.clientY;
    return {
        x: clientX - rect.left,
        y: clientY - rect.top,
    };
};

const startDraw = (e) => {
    e.preventDefault();
    isDrawing.value = true;
    hasDrawn.value = true;
    const pos = getPos(e);
    currentStroke.value = [{ ...pos, color: penColor.value, size: penSize.value }];
    canvasCtx.beginPath();
    canvasCtx.moveTo(pos.x, pos.y);
    canvasCtx.strokeStyle = penColor.value;
    canvasCtx.lineWidth = penSize.value;
};

const draw = (e) => {
    if (!isDrawing.value) return;
    e.preventDefault();
    const pos = getPos(e);
    currentStroke.value.push({ ...pos, color: penColor.value, size: penSize.value });
    canvasCtx.lineTo(pos.x, pos.y);
    canvasCtx.stroke();
};

const endDraw = () => {
    if (!isDrawing.value) return;
    isDrawing.value = false;
    if (currentStroke.value.length > 0) {
        strokes.value.push([...currentStroke.value]);
        currentStroke.value = [];
    }
};

const redrawCanvas = () => {
    const canvas = signatureCanvas.value;
    if (!canvas || !canvasCtx) return;
    const rect = canvas.getBoundingClientRect();
    canvasCtx.clearRect(0, 0, rect.width, rect.height);

    for (const stroke of strokes.value) {
        if (stroke.length < 2) continue;
        canvasCtx.beginPath();
        canvasCtx.moveTo(stroke[0].x, stroke[0].y);
        canvasCtx.strokeStyle = stroke[0].color;
        canvasCtx.lineWidth = stroke[0].size;
        for (let i = 1; i < stroke.length; i++) {
            canvasCtx.lineTo(stroke[i].x, stroke[i].y);
        }
        canvasCtx.stroke();
    }
};

const undoStroke = () => {
    if (strokes.value.length === 0) return;
    strokes.value.pop();
    redrawCanvas();
    if (strokes.value.length === 0) hasDrawn.value = false;
};

const clearCanvas = () => {
    if (!signatureCanvas.value || !canvasCtx) return;
    const rect = signatureCanvas.value.getBoundingClientRect();
    canvasCtx.clearRect(0, 0, rect.width, rect.height);
    strokes.value = [];
    currentStroke.value = [];
    hasDrawn.value = false;
};

const getCanvasDataURL = () => {
    if (!signatureCanvas.value || !hasDrawn.value) return null;
    // Create a trimmed version
    const canvas = signatureCanvas.value;
    const ctx = canvas.getContext('2d');
    const w = canvas.width;
    const h = canvas.height;
    const imgData = ctx.getImageData(0, 0, w, h);
    const pixels = imgData.data;
    let minX = w, minY = h, maxX = 0, maxY = 0;
    for (let y = 0; y < h; y++) {
        for (let x = 0; x < w; x++) {
            const a = pixels[(y * w + x) * 4 + 3];
            if (a > 0) {
                if (x < minX) minX = x;
                if (x > maxX) maxX = x;
                if (y < minY) minY = y;
                if (y > maxY) maxY = y;
            }
        }
    }
    if (maxX <= minX || maxY <= minY) return null;
    const pad = 20;
    minX = Math.max(0, minX - pad);
    minY = Math.max(0, minY - pad);
    maxX = Math.min(w - 1, maxX + pad);
    maxY = Math.min(h - 1, maxY + pad);
    const cw = maxX - minX + 1;
    const ch = maxY - minY + 1;
    const trimmed = document.createElement('canvas');
    trimmed.width = cw;
    trimmed.height = ch;
    const tCtx = trimmed.getContext('2d');
    tCtx.putImageData(ctx.getImageData(minX, minY, cw, ch), 0, 0);
    return trimmed.toDataURL('image/png');
};

watch(signatureMode, async (newMode) => {
    if (newMode === 'draw') {
        await nextTick();
        initCanvas();
    }
});

onMounted(() => {
    if (signatureMode.value === 'draw') {
        nextTick(() => initCanvas());
    }
});

// === Profile Functions ===
const updateProfile = () => {
    if (photoInput.value) {
        profileForm.photo = photoInput.value.files[0];
    }

    // Handle signature based on mode
    if (signatureMode.value === 'upload' && signatureInput.value) {
        profileForm.signature = signatureInput.value.files[0];
        profileForm.signature_data = null;
    } else if (signatureMode.value === 'draw' && hasDrawn.value) {
        profileForm.signature = null;
        profileForm.signature_data = getCanvasDataURL();
    }

    profileForm.post('/profile', {
        errorBag: 'updateProfileInformation',
        preserveScroll: true,
        onSuccess: () => {
            clearPhotoFileInput();
            clearSignatureFileInput();
            clearCanvas();
        },
    });
};

const selectNewPhoto = () => {
    photoInput.value.click();
};

const updatePhotoPreview = () => {
    const photo = photoInput.value.files[0];

    if (! photo) return;

    const reader = new FileReader();

    reader.onload = (e) => {
        photoPreview.value = e.target.result;
    };

    reader.readAsDataURL(photo);
};

const clearPhotoFileInput = () => {
    if (photoInput.value?.value) {
        photoInput.value.value = null;
    }
};

const selectNewSignature = () => {
    signatureInput.value.click();
};

const updateSignaturePreview = () => {
    const signature = signatureInput.value.files[0];

    if (! signature) return;

    const reader = new FileReader();

    reader.onload = (e) => {
        signaturePreview.value = e.target.result;
    };

    reader.readAsDataURL(signature);
};

const clearSignatureFileInput = () => {
    if (signatureInput.value?.value) {
        signatureInput.value.value = null;
    }
};

const updatePassword = () => {
    passwordForm.put('/profile/password', {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
        },
    });
};
</script>

<template>
    <Head title="Your Profile" />
    
    <AppLayout title="Your Profile">
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Profile Information -->
            <div class="glass-card rounded-2xl p-6">
                <div class="flex items-center gap-4 mb-6">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-slate-900 dark:text-white text-2xl font-bold">
                        {{ user?.name?.charAt(0)?.toUpperCase() || 'U' }}
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                            <UserCircleIcon class="h-5 w-5 text-slate-500 dark:text-slate-400" />
                            Profile Information
                        </h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Update your account's profile information and email address.</p>
                    </div>
                </div>

                <form @submit.prevent="updateProfile" class="space-y-4">
                    <!-- Photo & Signature Section -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pb-4">
                        <!-- Profile Photo -->
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Profile Photo</label>
                            <input
                                ref="photoInput"
                                type="file"
                                class="hidden"
                                @change="updatePhotoPreview"
                            >

                            <div v-show="!photoPreview">
                                <div 
                                    class="relative inline-block group cursor-pointer"
                                    @click.prevent="selectNewPhoto"
                                >
                                    <img
                                        v-if="user.profile_photo_path"
                                        :src="'/storage/' + user.profile_photo_path"
                                        :alt="user.name"
                                        class="rounded-full h-20 w-20 object-cover border-2 border-transparent group-hover:border-blue-500 transition-colors"
                                    >
                                    <div
                                        v-else
                                        class="flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 text-slate-900 dark:text-white text-2xl font-bold border-2 border-transparent group-hover:border-blue-500 transition-colors"
                                    >
                                        {{ user.name.charAt(0).toUpperCase() }}
                                    </div>
                                    
                                    <!-- Hover Overlay -->
                                    <div class="absolute inset-0 flex items-center justify-center rounded-full bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <!-- New Profile Photo Preview -->
                            <div v-show="photoPreview">
                                <div 
                                    class="relative inline-block group cursor-pointer"
                                    @click.prevent="selectNewPhoto"
                                >
                                    <span
                                        class="block rounded-full w-20 h-20 bg-cover bg-no-repeat bg-center border-2 border-transparent group-hover:border-blue-500 transition-colors"
                                        :style="'background-image: url(\'' + photoPreview + '\');'"
                                    />
                                    <!-- Hover Overlay -->
                                    <div class="absolute inset-0 flex items-center justify-center rounded-full bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2-2H5a2 2 0 01-2-2V9z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <p v-if="profileForm.errors.photo" class="text-red-400 text-xs mt-1">{{ profileForm.errors.photo }}</p>
                        </div>

                        <!-- Signature -->
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Tanda Tangan Digital (Digital Signature)</label>
                            
                            <!-- Existing Signature Preview -->
                            <div v-if="user.signature_path && !hasDrawn && !signaturePreview" class="mb-3">
                                <div class="border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-800/40 p-2" style="width: 220px; height: 80px;">
                                    <img :src="'/storage/' + user.signature_path" alt="Tanda tangan saat ini" class="h-full w-full object-contain dark:invert">
                                </div>
                                <p class="text-[10px] text-slate-400 mt-1">Tanda tangan saat ini</p>
                            </div>

                            <!-- Mode Toggle Tabs -->
                            <div class="flex gap-1 p-1 bg-slate-100 dark:bg-slate-800 rounded-xl mb-3" style="width: fit-content;">
                                <button
                                    type="button"
                                    @click="signatureMode = 'draw'"
                                    :class="[
                                        'px-3 py-1.5 rounded-lg text-xs font-semibold transition-all duration-200',
                                        signatureMode === 'draw'
                                            ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-sm'
                                            : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'
                                    ]"
                                >
                                    <span class="flex items-center gap-1.5">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                        Gambar
                                    </span>
                                </button>
                                <button
                                    type="button"
                                    @click="signatureMode = 'upload'"
                                    :class="[
                                        'px-3 py-1.5 rounded-lg text-xs font-semibold transition-all duration-200',
                                        signatureMode === 'upload'
                                            ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-sm'
                                            : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'
                                    ]"
                                >
                                    <span class="flex items-center gap-1.5">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                        </svg>
                                        Unggah
                                    </span>
                                </button>
                            </div>

                            <!-- DRAW MODE -->
                            <div v-show="signatureMode === 'draw'">
                                <!-- Canvas -->
                                <div class="relative border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-900 overflow-hidden" style="width: 340px; height: 140px;">
                                    <canvas
                                        ref="signatureCanvas"
                                        class="w-full h-full cursor-crosshair"
                                        style="touch-action: none;"
                                        @mousedown="startDraw"
                                        @mousemove="draw"
                                        @mouseup="endDraw"
                                        @mouseleave="endDraw"
                                        @touchstart="startDraw"
                                        @touchmove="draw"
                                        @touchend="endDraw"
                                    />
                                    <!-- Guideline -->
                                    <div class="absolute bottom-8 left-4 right-4 border-b border-dashed border-slate-200 dark:border-slate-700 pointer-events-none"></div>
                                    <p v-if="!hasDrawn" class="absolute bottom-2 left-0 right-0 text-center text-[10px] text-slate-300 dark:text-slate-600 pointer-events-none select-none">Gambar tanda tangan Anda di sini</p>
                                </div>
                                <!-- Canvas Controls -->
                                <div class="flex items-center gap-2 mt-2">
                                    <!-- Pen Colors -->
                                    <div class="flex items-center gap-1">
                                        <button type="button" v-for="c in ['#1e293b', '#1d4ed8', '#dc2626']" :key="c"
                                            @click="penColor = c"
                                            :class="['w-5 h-5 rounded-full border-2 transition-transform', penColor === c ? 'scale-125 border-blue-400 ring-2 ring-blue-200' : 'border-slate-300 hover:scale-110']"
                                            :style="{ backgroundColor: c }"
                                        />
                                    </div>
                                    <div class="w-px h-4 bg-slate-200 dark:bg-slate-700"></div>
                                    <!-- Pen Size -->
                                    <div class="flex items-center gap-1">
                                        <button type="button" v-for="s in [1.5, 2.5, 4]" :key="s"
                                            @click="penSize = s"
                                            :class="['rounded-full bg-slate-700 dark:bg-slate-300 transition-transform', penSize === s ? 'ring-2 ring-blue-400' : 'hover:scale-110']"
                                            :style="{ width: (s * 3 + 4) + 'px', height: (s * 3 + 4) + 'px' }"
                                        />
                                    </div>
                                    <div class="flex-1"></div>
                                    <!-- Undo -->
                                    <button type="button" @click="undoStroke" :disabled="strokes.length === 0"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/30 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                                        title="Undo"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a4 4 0 014 4v0a4 4 0 01-4 4H3m0-8l4-4m-4 4l4 4" />
                                        </svg>
                                    </button>
                                    <!-- Clear -->
                                    <button type="button" @click="clearCanvas" :disabled="!hasDrawn"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                                        title="Hapus Semua"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- UPLOAD MODE -->
                            <div v-show="signatureMode === 'upload'">
                                <input
                                    ref="signatureInput"
                                    type="file"
                                    class="hidden"
                                    accept="image/*"
                                    @change="updateSignaturePreview"
                                >

                                <div v-show="!signaturePreview">
                                    <div 
                                        class="relative inline-block group cursor-pointer border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-800/40 p-2 hover:border-blue-500 transition-colors"
                                        @click.prevent="selectNewSignature"
                                        style="width: 220px; height: 80px;"
                                    >
                                        <div class="flex flex-col h-full w-full items-center justify-center text-slate-400 text-xs">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mb-1 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                            </svg>
                                            Klik untuk unggah file
                                        </div>
                                        <div class="absolute inset-0 flex items-center justify-center rounded-xl bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <span class="text-white text-xs font-bold">Pilih File</span>
                                        </div>
                                    </div>
                                </div>

                                <div v-show="signaturePreview">
                                    <div 
                                        class="relative inline-block group cursor-pointer border border-slate-300 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-800/40 p-2 hover:border-blue-500 transition-colors"
                                        @click.prevent="selectNewSignature"
                                        style="width: 220px; height: 80px;"
                                    >
                                        <img
                                            :src="signaturePreview"
                                            alt="Signature Preview"
                                            class="h-full w-full object-contain dark:invert"
                                        >
                                        <div class="absolute inset-0 flex items-center justify-center rounded-xl bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <span class="text-white text-xs font-bold">Ubah File</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <p v-if="profileForm.errors.signature" class="text-red-400 text-xs mt-1">{{ profileForm.errors.signature }}</p>
                            <p v-if="profileForm.errors.signature_data" class="text-red-400 text-xs mt-1">{{ profileForm.errors.signature_data }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">Name</label>
                            <input 
                                v-model="profileForm.name" 
                                type="text" 
                                class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                required
                            />
                            <p v-if="profileForm.errors.name" class="text-red-400 text-xs mt-1">{{ profileForm.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">Email</label>
                            <input 
                                v-model="profileForm.email" 
                                type="email" 
                                class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                required
                            />
                            <p v-if="profileForm.errors.email" class="text-red-400 text-xs mt-1">{{ profileForm.errors.email }}</p>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button 
                            type="submit" 
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-600 text-sm font-semibold text-white dark:text-white hover:bg-blue-500 transition-colors"
                            :disabled="profileForm.processing"
                        >
                            <CheckCircleIcon class="h-4 w-4" />
                            {{ profileForm.processing ? 'Saving...' : 'Save Changes' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Update Password -->
            <div class="glass-card rounded-2xl p-6">
                <div class="flex items-center gap-4 mb-6">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-50 dark:bg-slate-800 text-amber-400">
                        <KeyIcon class="h-8 w-8" />
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                            <KeyIcon class="h-5 w-5 text-slate-500 dark:text-slate-400" />
                            Update Password
                        </h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Ensure your account is using a long, random password to stay secure.</p>
                    </div>
                </div>

                <form @submit.prevent="updatePassword" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">Current Password</label>
                        <input 
                            v-model="passwordForm.current_password" 
                            type="password" 
                            class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                            required
                        />
                        <p v-if="passwordForm.errors.current_password" class="text-red-400 text-xs mt-1">{{ passwordForm.errors.current_password }}</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">New Password</label>
                            <input 
                                v-model="passwordForm.password" 
                                type="password" 
                                class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                required
                            />
                            <p v-if="passwordForm.errors.password" class="text-red-400 text-xs mt-1">{{ passwordForm.errors.password }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">Confirm Password</label>
                            <input 
                                v-model="passwordForm.password_confirmation" 
                                type="password" 
                                class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                required
                            />
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button 
                            type="submit" 
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-amber-600 text-sm font-semibold text-slate-900 dark:text-white hover:bg-amber-500 transition-colors"
                            :disabled="passwordForm.processing"
                        >
                            <KeyIcon class="h-4 w-4" />
                            {{ passwordForm.processing ? 'Updating...' : 'Update Password' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>



