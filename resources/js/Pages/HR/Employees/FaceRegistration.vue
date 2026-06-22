<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { ArrowLeftIcon, VideoCameraIcon, ShieldCheckIcon } from '@heroicons/vue/24/outline';
import * as faceapi from 'face-api.js';

const props = defineProps({
    employee: Object
});

const form = useForm({
    face_descriptor: '',
    face_photo: null
});

const videoRef = ref(null);
const canvasRef = ref(null);
const statusMessage = ref('Loading models...');
const isLoading = ref(true);
const stream = ref(null);
const faceData = ref(null);

const loadModels = async () => {
    try {
        await Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri('/models'),
            faceapi.nets.faceLandmark68Net.loadFromUri('/models'),
            faceapi.nets.faceRecognitionNet.loadFromUri('/models')
        ]);
        statusMessage.value = 'Models loaded. Please allow camera access.';
        startVideo();
    } catch (e) {
        statusMessage.value = 'Failed to load face detection models.';
        console.error(e);
        isLoading.value = false;
    }
};

const startVideo = () => {
    navigator.mediaDevices.getUserMedia({ video: true })
        .then(currentStream => {
            stream.value = currentStream;
            videoRef.value.srcObject = currentStream;
            statusMessage.value = 'Camera active. Look straight into the camera.';
            isLoading.value = false;
        })
        .catch(err => {
            console.error(err);
            statusMessage.value = 'Could not access the camera. Please check permissions.';
            isLoading.value = false;
        });
};

const captureFace = async () => {
    if (!videoRef.value) return;
    
    statusMessage.value = 'Scanning face...';
    isLoading.value = true;
    
    try {
        const detection = await faceapi.detectSingleFace(videoRef.value, new faceapi.TinyFaceDetectorOptions())
            .withFaceLandmarks()
            .withFaceDescriptor();
            
        if (detection) {
            faceData.value = Array.from(detection.descriptor);
            form.face_descriptor = JSON.stringify(faceData.value);
            
            // Capture image frame from video for profile picture
            try {
                const tempCanvas = document.createElement('canvas');
                tempCanvas.width = videoRef.value.videoWidth || 640;
                tempCanvas.height = videoRef.value.videoHeight || 480;
                const tempCtx = tempCanvas.getContext('2d');
                tempCtx.drawImage(videoRef.value, 0, 0, tempCanvas.width, tempCanvas.height);
                form.face_photo = tempCanvas.toDataURL('image/jpeg');
            } catch (err) {
                console.error('Failed to capture base64 photo frame:', err);
            }
            
            statusMessage.value = 'Face detected and captured successfully!';
            
            // Draw on canvas
            const displaySize = { width: videoRef.value.width, height: videoRef.value.height };
            faceapi.matchDimensions(canvasRef.value, displaySize);
            const resizedDetections = faceapi.resizeResults(detection, displaySize);
            const ctx = canvasRef.value.getContext('2d');
            ctx.clearRect(0, 0, canvasRef.value.width, canvasRef.value.height);
            faceapi.draw.drawDetections(canvasRef.value, resizedDetections);
            faceapi.draw.drawFaceLandmarks(canvasRef.value, resizedDetections);
        } else {
            statusMessage.value = 'No face detected. Please try again.';
            faceData.value = null;
            form.face_photo = null;
        }
    } catch (e) {
        statusMessage.value = 'Error scanning face.';
        console.error(e);
    }
    
    isLoading.value = false;
};

const stopVideo = () => {
    if (stream.value) {
        stream.value.getTracks().forEach(track => track.stop());
    }
};

const saveRegistration = () => {
    form.post(route('hr.employees.face.store', props.employee.id), {
        onSuccess: () => stopVideo()
    });
};

onMounted(() => {
    loadModels();
});

onUnmounted(() => {
    stopVideo();
});
</script>

<template>
    <Head title="Face Registration" />

    <AppLayout title="Face Registration">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <!-- Breadcrumbs / Top Actions -->
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl shadow-lg">
                        <VideoCameraIcon class="h-6 w-6 text-white" />
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white">Face Registration</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Scan and register face master data for {{ employee.full_name }}</p>
                    </div>
                </div>

                <Link 
                    :href="route('hr.employees.index')" 
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-sm font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all shadow-sm"
                >
                    <ArrowLeftIcon class="h-4 w-4" />
                    Back to Directory
                </Link>
            </div>

            <!-- Scanner Card -->
            <div class="glass-card rounded-[2rem] overflow-hidden p-6 md:p-8">
                <!-- Status Bar -->
                <div 
                    class="mb-6 p-4 rounded-2xl flex items-center gap-3 transition-colors border"
                    :class="faceData 
                        ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20' 
                        : 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20'"
                >
                    <div class="h-2 w-2 rounded-full animate-ping" :class="faceData ? 'bg-emerald-500' : 'bg-blue-500'"></div>
                    <span class="text-sm font-bold uppercase tracking-wider font-mono">{{ statusMessage }}</span>
                </div>

                <div class="flex flex-col lg:flex-row gap-8">
                    <!-- Camera View Area -->
                    <div class="relative w-full lg:w-2/3 rounded-3xl overflow-hidden bg-slate-950 border border-slate-200 dark:border-slate-800 shadow-2xl flex items-center justify-center aspect-[4/3] min-h-[360px] lg:min-h-[440px]">
                        <video ref="videoRef" width="640" height="480" autoplay muted class="absolute inset-0 w-full h-full object-cover"></video>
                        <canvas ref="canvasRef" width="640" height="480" class="absolute inset-0 w-full h-full pointer-events-none"></canvas>
                        
                        <!-- Overlay helper graphic -->
                        <div class="absolute inset-0 border-[4px] border-dashed border-indigo-500/30 rounded-3xl pointer-events-none m-8 flex items-center justify-center">
                            <div class="w-48 h-48 rounded-full border-2 border-dashed border-indigo-500/50"></div>
                        </div>
                    </div>
                    
                    <!-- Control Actions Panel -->
                    <div class="w-full lg:w-1/3 flex flex-col justify-between py-2 space-y-6">
                        <div class="space-y-4">
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">Registration Instructions</h3>
                            <ul class="text-xs text-slate-500 dark:text-slate-400 space-y-2 leading-relaxed list-disc pl-4">
                                <li>Ensure you are in a well-lit environment.</li>
                                <li>Remove glasses, hats, or masks if any.</li>
                                <li>Position your face inside the dashed circular frame.</li>
                                <li>Look straight into the camera and click <strong>Capture Face</strong>.</li>
                            </ul>
                        </div>

                        <div class="space-y-4">
                            <button 
                                @click="captureFace" 
                                :disabled="isLoading" 
                                class="w-full inline-flex items-center justify-center gap-2 rounded-2xl bg-indigo-600 hover:bg-indigo-500 px-6 py-4 text-base font-black text-white shadow-xl shadow-indigo-900/20 transition-all disabled:opacity-50"
                            >
                                <VideoCameraIcon class="h-5 w-5" />
                                Capture Face Scan
                            </button>
                            
                            <div v-if="faceData" class="space-y-3 pt-6 border-t border-slate-200 dark:border-slate-800/80">
                                <div class="flex items-center justify-center gap-1.5 text-xs font-bold text-emerald-500 dark:text-emerald-400">
                                    <ShieldCheckIcon class="h-4 w-4" />
                                    Face features captured successfully!
                                </div>
                                <button 
                                    @click="saveRegistration" 
                                    :disabled="form.processing" 
                                    class="w-full inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 hover:bg-emerald-500 px-6 py-3.5 text-base font-black text-white shadow-xl shadow-emerald-900/20 transition-all disabled:opacity-50"
                                >
                                    💾 Save Registration
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
