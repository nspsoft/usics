<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import * as faceapi from 'face-api.js';

const props = defineProps({
    employee: Object
});

const form = useForm({
    face_descriptor: ''
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

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Face Registration - {{ employee.full_name }}
                </h2>
                <Link :href="route('hr.employees.show', employee.id)" class="text-gray-600 hover:text-gray-900 transition">
                    &larr; Back to Employee
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    
                    <div class="mb-4 p-4 rounded-md" :class="faceData ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'">
                        {{ statusMessage }}
                    </div>

                    <div class="flex flex-col md:flex-row gap-6">
                        <!-- Camera Area -->
                        <div class="relative w-full md:w-2/3 flex justify-center bg-gray-900 rounded-lg overflow-hidden" style="min-height: 480px;">
                            <video ref="videoRef" width="640" height="480" autoplay muted class="absolute top-0 left-0 w-full h-full object-cover"></video>
                            <canvas ref="canvasRef" width="640" height="480" class="absolute top-0 left-0 w-full h-full pointer-events-none"></canvas>
                        </div>
                        
                        <!-- Controls -->
                        <div class="w-full md:w-1/3 flex flex-col justify-center space-y-4">
                            <PrimaryButton @click="captureFace" :disabled="isLoading" class="w-full justify-center py-4 text-lg bg-indigo-600 hover:bg-indigo-700">
                                Capture Face
                            </PrimaryButton>
                            
                            <div v-if="faceData" class="mt-8 border-t pt-6">
                                <p class="text-sm text-green-600 font-semibold mb-4 text-center">Face successfully scanned!</p>
                                <PrimaryButton @click="saveRegistration" :disabled="form.processing" class="w-full justify-center py-3 bg-green-600 hover:bg-green-700">
                                    Save Registration
                                </PrimaryButton>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </AppLayout>
</template>
