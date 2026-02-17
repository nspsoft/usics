<script setup>
import { ref } from 'vue';
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

const profileForm = useForm({
    _method: 'PUT',
    name: props.user?.name || '',
    email: props.user?.email || '',
    photo: null,
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updateProfile = () => {
    if (photoInput.value) {
        profileForm.photo = photoInput.value.files[0];
    }

    profileForm.post('/profile', {
        errorBag: 'updateProfileInformation',
        preserveScroll: true,
        onSuccess: () => {
            clearPhotoFileInput();
            // Success handled by flash message
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
                    <!-- Profile Photo -->
                    <div class="col-span-6 sm:col-span-4">
                        <!-- Profile Photo File Input -->
                        <input
                            ref="photoInput"
                            type="file"
                            class="hidden"
                            @change="updatePhotoPreview"
                        >

                        <div class="mt-2" v-show="!photoPreview">
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
                        <div class="mt-2" v-show="photoPreview">
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <p v-if="profileForm.errors.photo" class="text-red-400 text-xs mt-1">{{ profileForm.errors.photo }}</p>
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



