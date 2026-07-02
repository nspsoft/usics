<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, onBeforeUnmount, ref } from 'vue';

const props = defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});

const canvasRef = ref(null);
let animationFrameId;

// Canvas Animation Logic
const initCanvas = () => {
    const canvas = canvasRef.value;
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    let width = window.innerWidth;
    let height = window.innerHeight;
    canvas.width = width;
    canvas.height = height;

    // Particles
    const particleCount = Math.min(Math.floor(width * height / 10000), 100); // Responsive count
    const particles = [];
    const connectionDistance = 150;
    const mouseParams = { x: null, y: null, radius: 200 };

    class Particle {
        constructor() {
            this.x = Math.random() * width;
            this.y = Math.random() * height;
            this.vx = (Math.random() - 0.5) * 1; // Velocity X
            this.vy = (Math.random() - 0.5) * 1; // Velocity Y
            this.size = Math.random() * 2 + 1;
            this.color = Math.random() > 0.5 ? '#06b6d4' : '#a855f7'; // Cyan or Purple
        }

        update() {
            this.x += this.vx;
            this.y += this.vy;

            // Bounce off edges
            if (this.x < 0 || this.x > width) this.vx *= -1;
            if (this.y < 0 || this.y > height) this.vy *= -1;

            // Mouse interaction
            if (mouseParams.x != null) {
                let dx = mouseParams.x - this.x;
                let dy = mouseParams.y - this.y;
                let distance = Math.sqrt(dx * dx + dy * dy);
                if (distance < mouseParams.radius) {
                    const forceDirectionX = dx / distance;
                    const forceDirectionY = dy / distance;
                    const force = (mouseParams.radius - distance) / mouseParams.radius;
                    const directionX = forceDirectionX * force * this.size;
                    const directionY = forceDirectionY * force * this.size;
                    
                    // Repulse slightly
                    this.x -= directionX * 2; 
                    this.y -= directionY * 2;
                }
            }
        }

        draw() {
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
            ctx.fillStyle = this.color;
            ctx.fill();
        }
    }

    // Init Particles
    for (let i = 0; i < particleCount; i++) {
        particles.push(new Particle());
    }

    const animate = () => {
        ctx.clearRect(0, 0, width, height);
        
        // Update and draw particles
        particles.forEach(p => {
            p.update();
            p.draw();
        });

        // Draw connections
        for (let a = 0; a < particles.length; a++) {
            for (let b = a; b < particles.length; b++) {
                let dx = particles[a].x - particles[b].x;
                let dy = particles[a].y - particles[b].y;
                let distance = Math.sqrt(dx * dx + dy * dy);

                if (distance < connectionDistance) {
                    let opacityValue = 1 - (distance / connectionDistance);
                    // Dynamic color based on particle color for nicer effect, or just bright cyan
                    // Let's use a mix or just bright cyan/white for high visibility
                    ctx.strokeStyle = `rgba(34, 211, 238, ${opacityValue * 0.8})`; // Cyan-400, higher opacity
                    ctx.lineWidth = 1.5; // Thicker lines
                    ctx.beginPath();
                    ctx.moveTo(particles[a].x, particles[a].y);
                    ctx.lineTo(particles[b].x, particles[b].y);
                    ctx.stroke();
                }
            }
        }

        animationFrameId = requestAnimationFrame(animate);
    };

    animate();

    // Resize Handler
    const handleResize = () => {
        width = window.innerWidth;
        height = window.innerHeight;
        canvas.width = width;
        canvas.height = height;
    };

    // Mouse Handler
    const handleMouseMove = (e) => {
        mouseParams.x = e.x;
        mouseParams.y = e.y;
    };
    
    const handleMouseLeave = () => {
        mouseParams.x = null;
        mouseParams.y = null;
    };

    window.addEventListener('resize', handleResize);
    window.addEventListener('mousemove', handleMouseMove);
    window.addEventListener('mouseout', handleMouseLeave);
    
    // Cleanup listeners on unmount (scoped to this setup function call, but we need to remove on unmount)
    // Actually better to define cleanup function
    return () => {
        window.removeEventListener('resize', handleResize);
        window.removeEventListener('mousemove', handleMouseMove);
        window.removeEventListener('mouseout', handleMouseLeave);
        cancelAnimationFrame(animationFrameId);
    };
};

const visitFeatures = (e) => {
    // Force standard navigation
    window.location.href = '/features';
};

let cleanup;

onMounted(() => {
    cleanup = initCanvas();
});

onBeforeUnmount(() => {
    if (cleanup) cleanup();
});

</script>

<template>
    <Head title="Welcome to the Future" />

    <div class="relative min-h-screen bg-slate-950 overflow-hidden font-sans selection:bg-cyan-500 selection:text-white">
        <!-- Canvas Background -->
        <canvas ref="canvasRef" class="absolute inset-0 z-0 opacity-90"></canvas>
        
        <!-- Gradient Overlay -->
        <div class="absolute inset-0 bg-gradient-to-br from-slate-950/90 via-slate-900/80 to-slate-900/90 z-0 pointer-events-none"></div>

        <!-- Content -->
        <div class="relative z-10 flex min-h-screen flex-col items-center justify-center px-6 py-12">
            
            <!-- Logo Section -->
            <div class="mb-12 animate-fade-in-down">
                <!-- Dynamic Logo with Zoom Effect -->
                <div class="group relative flex items-center justify-center p-6 transition-transform duration-500 hover:scale-125 cursor-pointer">
                    <div class="absolute inset-0 bg-cyan-500/20 rounded-full blur-xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                    <img 
                        :src="$page.props.company?.logo || '/images/usics.png'" 
                        alt="Company Logo" 
                        class="h-32 w-auto object-contain drop-shadow-[0_0_20px_rgba(6,182,212,0.6)] relative z-10" 
                    />
                </div>
            </div>

            <!-- Headlines -->
            <div class="text-center max-w-4xl mx-auto space-y-6 animate-fade-in-up">
                <p class="text-xs font-bold tracking-[0.3em] text-cyan-500 uppercase">
                    Advanced Enterprise Resource Planning
                </p>
                <h1 class="text-5xl md:text-7xl font-black tracking-tight text-white mb-4 drop-shadow-2xl">
                    <span class="bg-clip-text text-transparent bg-gradient-to-r from-cyan-400 via-blue-500 to-purple-600">SMART</span>
                    <span class="block mt-2">NETWORK PORTAL</span>
                </h1>
                <p class="text-lg md:text-xl text-slate-400 max-w-2xl mx-auto leading-relaxed">
                    Integrated AI-driven intelligence for maximum operational efficiency. Secure, scalable, and future-ready.
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="mt-12 flex flex-col sm:flex-row items-center gap-6 animate-fade-in-up delay-200">
                <Link 
                    :href="route('login')" 
                    class="group relative px-8 py-4 bg-cyan-600 hover:bg-cyan-500 text-white font-bold rounded-xl transition-all duration-300 shadow-[0_0_20px_rgba(8,145,178,0.5)] hover:shadow-[0_0_40px_rgba(8,145,178,0.7)] overflow-hidden"
                >
                    <div class="absolute inset-0 w-full h-full bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:animate-shimmer"></div>
                    <span class="relative flex items-center gap-2">
                        ENTER PORTAL
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 group-hover:translate-x-1 transition-transform">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                    </span>
                </Link>
                
                <a 
                    href="/features"
                    @click="visitFeatures"
                    class="relative z-50 cursor-pointer px-8 py-4 bg-slate-800/80 hover:bg-slate-700 text-slate-300 hover:text-white font-semibold rounded-xl border border-slate-700 hover:border-slate-600 transition-all backdrop-blur-md shadow-lg"
                >
                    Explore Features
                </a>
            </div>

            <!-- Footer Stats or Info -->
            <div class="mt-24 grid grid-cols-2 md:grid-cols-4 gap-8 md:gap-16 text-center border-t border-slate-800/50 pt-12 animate-fade-in-up delay-300">
                <div>
                    <h4 class="text-3xl font-bold text-white mb-1">99.9%</h4>
                    <p class="text-xs text-slate-500 uppercase tracking-wider">Uptime</p>
                </div>
                <div>
                    <h4 class="text-3xl font-bold text-white mb-1">AI</h4>
                    <p class="text-xs text-slate-500 uppercase tracking-wider">Powered</p>
                </div>
                <div>
                    <h4 class="text-3xl font-bold text-white mb-1">256-bit</h4>
                    <p class="text-xs text-slate-500 uppercase tracking-wider">Encryption</p>
                </div>
                <div>
                    <h4 class="text-3xl font-bold text-white mb-1">IoT</h4>
                    <p class="text-xs text-slate-500 uppercase tracking-wider">Ready</p>
                </div>
            </div>
        </div>
        
        <!-- Decorative Glows -->
        <div class="absolute top-0 left-0 w-[500px] h-[500px] bg-purple-600/20 rounded-full blur-[120px] -translate-x-1/2 -translate-y-1/2 pointer-events-none"></div>
        <div class="absolute bottom-0 right-0 w-[500px] h-[500px] bg-cyan-600/20 rounded-full blur-[120px] translate-x-1/2 translate-y-1/2 pointer-events-none"></div>
    </div>
</template>

<style scoped>
@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}
.animate-shimmer {
    animation: shimmer 1.5s infinite;
}
.animate-fade-in-down {
    animation: fadeInDown 0.8s ease-out forwards;
}
.animate-fade-in-up {
    animation: fadeInUp 0.8s ease-out forwards;
    opacity: 0;
}
.delay-200 { animation-delay: 0.2s; }
.delay-300 { animation-delay: 0.3s; }

@keyframes fadeInDown {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
