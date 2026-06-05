<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import * as faceapi from 'face-api.js';

const props = defineProps({
    employee: Object,
    appSettings: Object,
    todayAttendance: Object
});

const form = useForm({
    type: props.todayAttendance?.time_in ? 'out' : 'in', // Determine if Clocking IN or OUT
    photo: null,
    latitude: null,
    longitude: null,
    face_descriptor: ''
});

const videoRef = ref(null);
const canvasRef = ref(null);
const statusMessage = ref('Loading models...');
const isLoading = ref(true);
const stream = ref(null);
const locationStatus = ref('Fetching location...');
const distance = ref(null);
const inRadius = ref(false);

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
            statusMessage.value = 'Ready. Click "Clock In" when ready.';
            isLoading.value = false;
        })
        .catch(err => {
            console.error(err);
            statusMessage.value = 'Could not access the camera.';
            isLoading.value = false;
        });
};

// Calculate distance using Haversine formula
const getDistance = (lat1, lon1, lat2, lon2) => {
    const R = 6371e3; // metres
    const φ1 = lat1 * Math.PI/180; // φ, λ in radians
    const φ2 = lat2 * Math.PI/180;
    const Δφ = (lat2-lat1) * Math.PI/180;
    const Δλ = (lon2-lon1) * Math.PI/180;

    const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
            Math.cos(φ1) * Math.cos(φ2) *
            Math.sin(Δλ/2) * Math.sin(Δλ/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));

    return R * c; // in metres
};

const getLocation = () => {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                form.latitude = position.coords.latitude;
                form.longitude = position.coords.longitude;
                
                if (props.appSettings?.office_latitude && props.appSettings?.office_longitude) {
                    distance.value = getDistance(
                        form.latitude, 
                        form.longitude, 
                        parseFloat(props.appSettings.office_latitude), 
                        parseFloat(props.appSettings.office_longitude)
                    );
                    
                    const maxRadius = props.appSettings.max_radius_meters || 50;
                    inRadius.value = distance.value <= maxRadius;
                    
                    locationStatus.value = `You are ${Math.round(distance.value)}m away from the office. ` + 
                        (inRadius.value ? '(Within range)' : '(Out of range)');
                } else {
                    locationStatus.value = 'Location found. (Office location not configured in settings)';
                    inRadius.value = true; // allow if no office location
                }
            },
            (error) => {
                locationStatus.value = 'Error fetching location. Please enable location services.';
                console.error(error);
            }
        );
    } else {
        locationStatus.value = 'Geolocation is not supported by this browser.';
    }
};

const captureAndClock = async () => {
    if (!videoRef.value || !form.latitude) {
        alert('Please ensure camera and location are ready.');
        return;
    }
    
    statusMessage.value = 'Verifying face...';
    isLoading.value = true;
    
    try {
        const detection = await faceapi.detectSingleFace(videoRef.value, new faceapi.TinyFaceDetectorOptions())
            .withFaceLandmarks()
            .withFaceDescriptor();
            
        if (detection) {
            form.face_descriptor = JSON.stringify(Array.from(detection.descriptor));
            
            // Capture image as file
            const canvas = document.createElement('canvas');
            canvas.width = videoRef.value.videoWidth;
            canvas.height = videoRef.value.videoHeight;
            canvas.getContext('2d').drawImage(videoRef.value, 0, 0);
            
            canvas.toBlob((blob) => {
                const file = new File([blob], `clock_${form.type}_${new Date().getTime()}.jpg`, { type: 'image/jpeg' });
                form.photo = file;
                
                // Submit Form
                form.post(route('employee.attendance.clock.process'), {
                    onSuccess: () => stopVideo()
                });
            }, 'image/jpeg');

        } else {
            statusMessage.value = 'No face detected. Please try again.';
            isLoading.value = false;
        }
    } catch (e) {
        statusMessage.value = 'Error verifying face.';
        console.error(e);
        isLoading.value = false;
    }
};

const stopVideo = () => {
    if (stream.value) {
        stream.value.getTracks().forEach(track => track.stop());
    }
};

onMounted(() => {
    loadModels();
    getLocation();
});

onUnmounted(() => {
    stopVideo();
});
</script>

<template>
    <Head title="Clock In/Out" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-lg text-gray-800 dark:text-slate-100 leading-tight">
                    Smart Attendance
                </h2>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-xl mx-auto sm:px-4 lg:px-6">
                <div class="bg-white dark:bg-slate-900 overflow-hidden shadow-sm sm:rounded-lg p-4 text-center border border-gray-150 dark:border-slate-800">
                    
                    <h3 class="text-xl font-bold mb-1 dark:text-slate-100">{{ new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }) }}</h3>
                    <p class="text-xs text-gray-500 dark:text-slate-400 mb-4">{{ new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}</p>
 
                    <div class="mb-3 p-2 text-xs rounded-md font-semibold" :class="inRadius ? 'bg-green-100 text-green-800 dark:bg-green-950/40 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-950/40 dark:text-red-400'">
                        {{ locationStatus }}
                    </div>
                    
                    <div class="mb-3 p-2 text-xs rounded-md bg-blue-100 text-blue-800 dark:bg-blue-950/40 dark:text-blue-400 font-semibold">
                        {{ statusMessage }}
                    </div>
                    
                    <div class="flex justify-center mb-4">
                        <div class="relative bg-gray-900 rounded-lg overflow-hidden border-4" 
                             :class="inRadius ? 'border-green-500 dark:border-green-600' : 'border-red-500 dark:border-red-600'" 
                             style="width: 480px; height: 360px;">
                            <video ref="videoRef" width="480" height="360" autoplay muted class="absolute top-0 left-0 w-full h-full object-cover"></video>
                        </div>
                    </div>
                    
                    <div class="flex justify-center mt-3">
                        <PrimaryButton 
                            v-if="!todayAttendance?.time_in"
                            @click="captureAndClock" 
                            :disabled="isLoading || !inRadius || form.processing" 
                            class="py-2.5 px-8 text-base bg-indigo-600 hover:bg-indigo-700">
                            {{ form.processing ? 'Clocking In...' : 'Clock In' }}
                        </PrimaryButton>
                        
                        <PrimaryButton 
                            v-else-if="!todayAttendance?.time_out"
                            @click="captureAndClock" 
                            :disabled="isLoading || !inRadius || form.processing" 
                            class="py-2.5 px-8 text-base bg-orange-600 hover:bg-orange-700 focus:bg-orange-700 active:bg-orange-900">
                            {{ form.processing ? 'Clocking Out...' : 'Clock Out' }}
                        </PrimaryButton>
                        
                        <div v-else class="text-green-600 dark:text-green-400 font-bold text-lg">
                            You have completed your shift today! 🎉
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
