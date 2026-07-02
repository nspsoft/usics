<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import { 
    EyeIcon, 
    EyeSlashIcon, 
    LockClosedIcon, 
    EnvelopeIcon, 
    ArrowRightIcon, 
    ArrowDownTrayIcon,
    UserIcon,
    ShieldCheckIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    startRegister: Boolean
});

// State Management
const isRegister = ref(props.startRegister || false);
const showPassword = ref(false);
const showRegisterPassword = ref(false);

// Login Form
const loginForm = useForm({
    email: '',
    password: '',
    remember: false,
});

// Register Form
const registerForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submitLogin = () => {
    loginForm.post('/login', {
        onFinish: () => loginForm.reset('password'),
    });
};

const submitRegister = () => {
    registerForm.post('/register', {
        onFinish: () => registerForm.reset('password', 'password_confirmation'),
    });
};

// PWA Install Prompt
const deferredPrompt = ref(null);
const canInstall = ref(false);

const handleBeforeInstallPrompt = (e) => {
    e.preventDefault();
    window.deferredPrompt = e;
    canInstall.value = true;
};

const installApp = async () => {
    if (!window.deferredPrompt) return;
    window.deferredPrompt.prompt();
    const { outcome } = await window.deferredPrompt.userChoice;
    if (outcome === 'accepted') {
        canInstall.value = false;
        window.deferredPrompt = null;
    }
};

onMounted(() => {
    // Sync Theme
    if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }

    if (window.deferredPrompt) {
        canInstall.value = true;
    }
    window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
});

onUnmounted(() => {
    window.removeEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
});
</script>

<template>
    <Head :title="isRegister ? 'Sign Up - USICS ERP' : 'Sign In - USICS ERP'" />
    
    <div class="min-h-screen relative overflow-hidden bg-slate-950 flex items-center justify-center p-4 selection:bg-cyan-500/30">
        <!-- Futuristic Background -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute inset-0 bg-gradient-to-b from-slate-950 via-slate-900 to-slate-950"></div>
            <div class="absolute inset-0" style="perspective: 800px;">
                <div class="grid-floor"></div>
            </div>
            <div class="data-streams">
                <div class="stream stream-1"></div>
                <div class="stream stream-2"></div>
                <div class="stream stream-3"></div>
                <div class="stream stream-4"></div>
                <div class="stream stream-5"></div>
                <div class="stream stream-6"></div>
            </div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[400px] h-[400px] bg-cyan-500/10 rounded-full blur-[120px] animate-pulse"></div>
            
            <div class="particle p1"></div><div class="particle p2"></div><div class="particle p3"></div><div class="particle p4"></div>
            <div class="particle p5"></div><div class="particle p6"></div><div class="particle p7"></div><div class="particle p8"></div>
        </div>

        <div class="relative z-10 w-full max-w-md perspective-2000">
            <!-- PWA Install Button -->
            <button 
                v-if="canInstall"
                @click="installApp"
                class="absolute -top-16 left-1/2 -translate-x-1/2 flex items-center gap-2 px-6 py-2.5 rounded-2xl bg-slate-900/80 border border-white/20 text-white hover:bg-slate-800 hover:border-cyan-500 transition-all backdrop-blur-xl group shadow-2xl shadow-cyan-500/20"
            >
                <div class="p-1.5 rounded-lg bg-cyan-500/20 text-cyan-400 group-hover:bg-cyan-500 group-hover:text-slate-900 transition-all">
                    <ArrowDownTrayIcon class="h-4 w-4" />
                </div>
                <span class="text-xs font-bold uppercase tracking-widest">Install USICS App</span>
            </button>

            <!-- Flip Container -->
            <div class="flip-card-inner transition-all duration-1000 cubic-bezier(0.4, 0, 0.2, 1)" :class="isRegister ? 'flipped' : ''">
                <!-- LOGIN CARD (Front) -->
                <div class="flip-card-front w-full">
                    <div class="text-center mb-10">
                        <div class="inline-flex items-center justify-center w-24 h-24 rounded-3xl bg-slate-950 border border-white/20 shadow-2xl shadow-blue-500/20 mb-6 relative group overflow-hidden">
                            <img :src="$page.props.company?.logo || '/images/usics.png'" alt="Logo" class="w-full h-full object-contain p-3 relative z-10" />
                            <div class="absolute -inset-1 rounded-3xl bg-gradient-to-br from-cyan-500/30 to-blue-500/30 blur-xl opacity-50 group-hover:opacity-100 transition-opacity"></div>
                        </div>
                        <h1 class="text-4xl font-black text-white mb-2 tracking-tight">
                            Welcome to <span class="bg-gradient-to-r from-cyan-400 to-blue-500 bg-clip-text text-transparent">USICS</span>
                        </h1>
                        <p class="text-slate-400 text-sm font-medium tracking-wide">UNITED STEEL INTELLIGENCE CONTROL SYSTEM</p>
                    </div>

                    <div class="glass-card rounded-[2.5rem] py-10 px-10 border border-white/20 relative overflow-hidden backdrop-blur-3xl bg-slate-950/90 shadow-2xl">
                        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-4/5 h-px bg-gradient-to-r from-transparent via-cyan-500/40 to-transparent"></div>
                        
                        <form @submit.prevent="submitLogin" class="space-y-6">
                            <div class="space-y-2">
                                <label class="text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] ml-1">Access Email</label>
                                <div class="relative group">
                                    <div class="absolute left-4 top-1/2 -translate-y-1/2 p-2 rounded-lg bg-white/5 border border-white/10 text-slate-400 group-focus-within:text-cyan-400 group-focus-within:border-cyan-500/50 transition-all z-20">
                                        <EnvelopeIcon class="h-5 w-5" />
                                    </div>
                                    <input v-model="loginForm.email" type="email" class="auth-input" placeholder="admin@usc-indonesia.co.id" required />
                                </div>
                                <p v-if="loginForm.errors.email" class="text-xs text-red-400 mt-1.5 ml-1">{{ loginForm.errors.email }}</p>
                            </div>

                            <div class="space-y-2">
                                <div class="flex justify-between items-center ml-1">
                                    <label class="text-[11px] font-black text-slate-400 uppercase tracking-[0.2em]">Secret Key</label>
                                    <a href="#" class="text-[11px] font-black text-cyan-400 hover:text-cyan-300 uppercase tracking-widest transition-colors">Forgot?</a>
                                </div>
                                <div class="relative group">
                                    <div class="absolute left-4 top-1/2 -translate-y-1/2 p-2 rounded-lg bg-white/5 border border-white/10 text-slate-400 group-focus-within:text-cyan-400 group-focus-within:border-cyan-500/50 transition-all z-20">
                                        <LockClosedIcon class="h-5 w-5" />
                                    </div>
                                    <input v-model="loginForm.password" :type="showPassword ? 'text' : 'password'" class="auth-input pr-14" placeholder="••••••••" required />
                                    <button type="button" @click="showPassword = !showPassword" class="absolute right-4 top-1/2 -translate-y-1/2 p-1.5 text-slate-500 hover:text-white transition-colors z-20">
                                        <EyeSlashIcon v-if="showPassword" class="h-5 w-5" />
                                        <EyeIcon v-else class="h-5 w-5" />
                                    </button>
                                </div>
                            </div>

                            <div class="flex items-center">
                                <label class="flex items-center gap-3 cursor-pointer select-none group">
                                    <div class="relative">
                                        <input v-model="loginForm.remember" type="checkbox" class="peer sr-only" />
                                        <div class="w-5 h-5 rounded-md border-2 border-white/10 bg-slate-900 peer-checked:bg-cyan-500 peer-checked:border-cyan-500 transition-all"></div>
                                        <div class="absolute inset-0 flex items-center justify-center opacity-0 peer-checked:opacity-100 transition-opacity">
                                            <svg class="w-3.5 h-3.5 text-slate-900 font-bold" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                    </div>
                                    <span class="text-[11px] font-black text-slate-400 group-hover:text-slate-300 uppercase tracking-widest transition-colors">Remember Session</span>
                                </label>
                            </div>

                            <button type="submit" :disabled="loginForm.processing" class="auth-btn group">
                                <div class="relative z-10 flex items-center justify-center gap-3">
                                    <span v-if="!loginForm.processing" class="tracking-[0.2em]">Sign In</span>
                                    <span v-else class="tracking-[0.1em]">Verifying...</span>
                                    <ArrowRightIcon class="h-5 w-5 group-hover:translate-x-1.5 transition-transform" />
                                </div>
                                <div class="absolute inset-0 bg-gradient-to-r from-cyan-600 to-blue-700 transition-transform group-hover:scale-105 duration-500"></div>
                            </button>
                        </form>

                        <div class="mt-6 text-center border-t border-white/5 pt-6">
                            <p class="text-[11px] font-black text-slate-500 uppercase tracking-widest">
                                Need an account? 
                                <button @click="isRegister = true" class="text-cyan-400 hover:text-white transition-all ml-2 underline underline-offset-4 decoration-2 decoration-cyan-400/30">Create Account</button>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- REGISTER CARD (Back) -->
                <div class="flip-card-back w-full">
                    <div class="text-center mb-10">
                        <div class="inline-flex items-center justify-center w-24 h-24 rounded-3xl bg-slate-950 border border-white/20 shadow-2xl shadow-emerald-500/20 mb-6 relative group overflow-hidden">
                            <ShieldCheckIcon class="h-12 w-12 text-emerald-400 relative z-10" />
                            <div class="absolute -inset-1 rounded-3xl bg-gradient-to-br from-emerald-500/30 to-teal-500/30 blur-xl opacity-50 group-hover:opacity-100 transition-opacity"></div>
                        </div>
                        <h1 class="text-4xl font-black text-white mb-2 tracking-tight">
                            Register User
                        </h1>
                        <p class="text-slate-400 text-sm font-medium tracking-wide">Access Request for Management System</p>
                    </div>

                    <div class="glass-card rounded-[2.5rem] py-10 px-10 border border-white/20 relative overflow-hidden backdrop-blur-3xl bg-slate-950/90 shadow-2xl">
                        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-4/5 h-px bg-gradient-to-r from-transparent via-emerald-500/40 to-transparent"></div>
                        
                        <form @submit.prevent="submitRegister" class="space-y-4">
                            <div class="space-y-2">
                                <label class="text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] ml-1">Full Name</label>
                                <div class="relative group">
                                    <div class="absolute left-4 top-1/2 -translate-y-1/2 p-2 rounded-lg bg-white/5 border border-white/10 text-slate-400 group-focus-within:text-emerald-400 group-focus-within:border-emerald-500/50 transition-all z-20">
                                        <UserIcon class="h-5 w-5" />
                                    </div>
                                    <input v-model="registerForm.name" type="text" class="auth-input" placeholder="Enter Full Name" required />
                                </div>
                                <p v-if="registerForm.errors.name" class="text-xs text-red-400 mt-1 ml-1">{{ registerForm.errors.name }}</p>
                            </div>

                            <div class="space-y-2">
                                <label class="text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] ml-1">Email Address</label>
                                <div class="relative group">
                                    <div class="absolute left-4 top-1/2 -translate-y-1/2 p-2 rounded-lg bg-white/5 border border-white/10 text-slate-400 group-focus-within:text-emerald-400 group-focus-within:border-emerald-500/50 transition-all z-20">
                                        <EnvelopeIcon class="h-5 w-5" />
                                    </div>
                                    <input v-model="registerForm.email" type="email" class="auth-input" placeholder="email@usc-indonesia.co.id" required />
                                </div>
                                <p v-if="registerForm.errors.email" class="text-xs text-red-400 mt-1 ml-1">{{ registerForm.errors.email }}</p>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <label class="text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] ml-1">Password</label>
                                    <div class="relative group">
                                        <div class="absolute left-4 top-1/2 -translate-y-1/2 p-2 rounded-lg bg-white/5 border border-white/10 text-slate-400 group-focus-within:text-emerald-400 group-focus-within:border-emerald-500/50 transition-all z-20 pointer-events-none">
                                            <LockClosedIcon class="h-5 w-5" />
                                        </div>
                                        <input v-model="registerForm.password" :type="showRegisterPassword ? 'text' : 'password'" class="auth-input pr-12 text-xs" placeholder="Secret" required />
                                        <button type="button" @click="showRegisterPassword = !showRegisterPassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 hover:text-white transition-colors z-20">
                                            <EyeSlashIcon v-if="showRegisterPassword" class="h-4 w-4" />
                                            <EyeIcon v-else class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] ml-1">Confirm</label>
                                    <input v-model="registerForm.password_confirmation" type="password" class="auth-input pl-6 text-xs" placeholder="Repeat" required />
                                </div>
                            </div>

                            <button type="submit" :disabled="registerForm.processing" class="auth-btn group !mt-6">
                                <div class="relative z-10 flex items-center justify-center gap-3">
                                    <span v-if="!registerForm.processing" class="tracking-[0.2em]">Create Account</span>
                                    <span v-else class="tracking-[0.1em]">Registering...</span>
                                    <ArrowRightIcon class="h-5 w-5 group-hover:translate-x-1.5 transition-transform" />
                                </div>
                                <div class="absolute inset-0 bg-gradient-to-r from-emerald-600 to-teal-700 transition-transform group-hover:scale-105 duration-500"></div>
                            </button>
                        </form>

                        <div class="mt-6 text-center border-t border-white/5 pt-6">
                            <button @click="isRegister = false" class="inline-flex items-center gap-2 text-[11px] font-black text-slate-500 uppercase tracking-widest hover:text-emerald-400 transition-all group">
                                <ArrowLeftIcon class="h-4 w-4 group-hover:-translate-x-1 transition-transform" />
                                Back to Login
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Global Footer -->
            <div class="text-center mt-12 space-y-3">
                <p class="text-[12px] font-black text-slate-500 uppercase tracking-[0.4em]">
                    Powered by <span class="bg-gradient-to-r from-cyan-400 to-blue-500 bg-clip-text text-transparent font-black">USICS ERP</span>
                </p>
                <p class="text-[12px] font-bold text-slate-600 uppercase tracking-widest leading-relaxed">
                    © 2026 PT. JIDOKA RESULT INDONESIA. <br/> 
                    <span class="opacity-50 text-[10px]">All rights reserved.</span>
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
@reference "../../../css/app.css";

.perspective-2000 { perspective: 2000px; }
.flip-card-inner {
    position: relative;
    width: 100%;
    transform-style: preserve-3d;
}
.flip-card-inner.flipped {
    transform: rotateY(180deg);
}
.flip-card-front, .flip-card-back {
    -webkit-backface-visibility: hidden;
    backface-visibility: hidden;
}
.flip-card-back {
    transform: rotateY(180deg);
    position: absolute;
    top: 0;
    left: 0;
}

.auth-input {
    @apply block w-full pl-16 pr-6 py-4 rounded-2xl border border-white/10 bg-slate-950/50 text-white text-sm placeholder:text-slate-600 focus:ring-2 focus:ring-cyan-500/50 focus:border-cyan-500 transition-all outline-none shadow-inner;
}

.auth-btn {
    @apply relative w-full py-5 rounded-2xl font-black text-xs uppercase text-white overflow-hidden shadow-2xl transition-all active:scale-[0.97] disabled:opacity-50 mt-4;
}

/* Background Effects */
.grid-floor {
    position: absolute;
    bottom: 0;
    left: 50%;
    width: 200%;
    height: 50%;
    transform: translateX(-50%) rotateX(75deg);
    transform-origin: center bottom;
    background-image: 
        linear-gradient(rgba(6, 182, 212, 0.2) 1px, transparent 1px),
        linear-gradient(90deg, rgba(6, 182, 212, 0.2) 1px, transparent 1px);
    background-size: 80px 80px;
    animation: gridScroll 3s linear infinite;
}

@keyframes gridScroll {
    0% { background-position: 0 0; }
    100% { background-position: 0 80px; }
}

.data-streams { position: absolute; inset: 0; overflow: hidden; }
.stream {
    position: absolute; width: 2px; height: 120px;
    background: linear-gradient(to bottom, transparent, #06b6d4, #3b82f6, transparent);
    animation: streamFall linear infinite;
    opacity: 0.6;
}
.stream-1 { left: 15%; animation-duration: 3s; }
.stream-2 { left: 35%; animation-duration: 4s; }
.stream-3 { left: 55%; animation-duration: 2.5s; }
.stream-4 { left: 70%; animation-duration: 3.5s; }
.stream-5 { left: 85%; animation-duration: 2s; }
.stream-6 { left: 5%; animation-duration: 5s; }

@keyframes streamFall {
    0% { transform: translateY(-100%) scale(0.5); opacity: 0; }
    10% { opacity: 0.6; }
    90% { opacity: 0.6; }
    100% { transform: translateY(100vh) scale(1.5); opacity: 0; }
}

.particle {
    position: absolute; width: 4px; height: 4px; background: #06b6d4; border-radius: 50%;
    box-shadow: 0 0 15px #06b6d4; animation: particleFloat 10s ease-in-out infinite;
}
.p1 { top: 20%; left: 10%; animation-delay: 0s; }
.p2 { top: 40%; right: 15%; animation-delay: 2s; background: #3b82f6; }
.p3 { top: 65%; left: 20%; animation-delay: 4s; }
.p4 { top: 80%; right: 25%; animation-delay: 6s; background: #818cf8; }
.p5 { top: 15%; right: 30%; animation-delay: 1s; }
.p6 { top: 55%; right: 5%; animation-delay: 3s; background: #22d3ee; }
.p7 { top: 85%; left: 40%; animation-delay: 5s; }
.p8 { top: 30%; left: 50%; animation-delay: 7s; background: #0ea5e9; }

@keyframes particleFloat {
    0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.4; }
    25% { transform: translate(30px, -40px) scale(1.4); opacity: 0.8; }
    50% { transform: translate(-20px, -70px) scale(0.7); opacity: 0.5; }
    75% { transform: translate(25px, -30px) scale(1.2); opacity: 0.9; }
}

.cubic-bezier {
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}
</style>
