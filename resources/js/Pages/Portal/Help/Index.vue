<script setup>
import { Head } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import { 
    QuestionMarkCircleIcon, 
    ChevronRightIcon, 
    ChevronLeftIcon,
    CheckCircleIcon,
    HomeIcon,
    ShoppingCartIcon,
    TruckIcon,
    BanknotesIcon,
    SunIcon,
    MoonIcon,
    ArrowRightIcon,
    CloudArrowUpIcon,
    DocumentCheckIcon
} from '@heroicons/vue/24/outline';
import { ref, computed } from 'vue';

// --- Mock Data & Visual Helpers ---
const isDarkDemo = ref(false);
const toggleDemoTheme = () => isDarkDemo.value = !isDarkDemo.value;

const guides = [
    {
        id: 'getting-started',
        title: 'Getting Started',
        description: 'Master the basics of your USICS Portal dashboard.',
        icon: HomeIcon,
        steps: [
            {
                title: 'Your Command Center',
                text: 'The Sidebar is your main navigation tool. It stays on the left (or in the menu on mobile) giving you instant access to Purchase Orders, Deliveries, Invoices, and more. Use it to switch between different modules seamlessly.',
                visual: 'sidebar-demo'
            },
            {
                title: 'At a Glance',
                text: 'The Dashboard provides an immediate overview of your tasks. "New Orders" highlights POs waiting for your acknowledgment. "On the Way" tracks your active shipments. "Unpaid Invoices" shows pending payments.',
                visual: 'cards-demo'
            },
            {
                title: 'Personalize Your View',
                text: 'Prefer working at night? Use the Theme Toggle in the top right corner. Switching to Dark Mode reduces eye strain in low-light environments. Your preference is saved automatically.',
                visual: 'theme-demo'
            }
        ]
    },
    {
        id: 'purchase-orders',
        title: 'Managing Orders (PO)',
        description: 'How to view, accept, and process Purchase Orders.',
        icon: ShoppingCartIcon,
        steps: [
            {
                title: 'New Order Notification',
                text: 'When we send a PO, it appears in your "Purchase Orders" list with a "New" status. You will also see a notification bell alert and an email summary.',
                visual: 'notification-demo'
            },
            {
                title: 'Reviewing Details',
                text: 'Click on any PO number to view the full details: items, quantities, prices, and delivery dates. You can also download the official PDF version for your records.',
                visual: 'po-detail-demo'
            },
            {
                title: 'Acknowledgment is Key',
                text: 'You MUST "Acknowledge" a PO to confirm you can fulfill it. This changes the status to "Acknowledged" and allows you to proceed with creating a Delivery Note (ASN).',
                visual: 'acknowledge-demo'
            }
        ]
    },
    {
        id: 'deliveries',
        title: 'Shipping Goods (ASN)',
        description: 'Creating generic Delivery Notes for shipments.',
        icon: TruckIcon,
        steps: [
            {
                title: 'Initiate Shipment',
                text: 'Go to the "Deliveries" module. Click the "+ Create Delivery Note" button. This starts the Advance Shipping Notice (ASN) process.',
                visual: 'create-dn-demo'
            },
            {
                title: 'Select Purchase Order',
                text: 'Choose the PO you are fulfilling. You can verify available quantities immediately. If you are shipping partial quantities, simply adjust the number in the "Qty to Ship" column.',
                visual: 'select-po-demo'
            },
            {
                title: 'Driver & Logistics',
                text: 'Enter your physical Delivery Note/Surat Jalan number. Optionally, add Driver Name and Vehicle Number to help our security team expedite entry at the gate.',
                visual: 'logistics-demo'
            }
        ]
    },
    {
        id: 'invoicing',
        title: 'Invoicing & Listings',
        description: 'Tracking your invoices and payment status.',
        icon: BanknotesIcon,
        steps: [
            {
                title: 'Submission Status',
                text: 'Once we receive your goods, you can submit your invoice. The "Invoices" module tracks usage from "submitted" to "paid".',
                visual: 'invoice-list-demo'
            }
        ]
    }
];

const activeGuide = ref(guides[0]);
const currentStepIndex = ref(0);

const selectGuide = (guide) => {
    activeGuide.value = guide;
    currentStepIndex.value = 0;
};

const nextStep = () => {
    if (currentStepIndex.value < activeGuide.value.steps.length - 1) {
        currentStepIndex.value++;
    }
};

const prevStep = () => {
    if (currentStepIndex.value > 0) {
        currentStepIndex.value--;
    }
};

const currentStep = computed(() => activeGuide.value.steps[currentStepIndex.value]);
</script>

<template>
    <PortalLayout title="Help Center">
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 h-[calc(100vh-8rem)]">
                
                <!-- Guide Selector Sidebar -->
                <div class="lg:col-span-3 flex flex-col bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-700">
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <QuestionMarkCircleIcon class="w-6 h-6 text-indigo-500" />
                            Help Topics
                        </h2>
                    </div>
                    <div class="flex-1 overflow-y-auto p-2 space-y-1">
                        <button 
                            v-for="guide in guides" 
                            :key="guide.id"
                            @click="selectGuide(guide)"
                            class="w-full text-left px-4 py-4 rounded-xl text-sm transition-all duration-200 group relative overflow-hidden"
                            :class="activeGuide.id === guide.id 
                                ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300' 
                                : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700'"
                        >
                            <div class="flex items-start gap-3 relative z-10">
                                <component :is="guide.icon" class="w-5 h-5 flex-shrink-0 mt-0.5" 
                                    :class="activeGuide.id === guide.id ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400'" 
                                />
                                <div>
                                    <div class="font-bold">{{ guide.title }}</div>
                                    <div class="text-xs opacity-80 mt-1 leading-relaxed">{{ guide.description }}</div>
                                </div>
                            </div>
                            <div v-if="activeGuide.id === guide.id" class="absolute left-0 top-0 bottom-0 w-1 bg-indigo-500"></div>
                        </button>
                    </div>
                </div>

                <!-- Main Interactive Area -->
                <div class="lg:col-span-9 flex flex-col bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                    
                    <!-- Progress Bar -->
                    <div class="h-1 bg-slate-100 dark:bg-slate-700 w-full">
                        <div 
                            class="h-full bg-indigo-500 transition-all duration-500 ease-out"
                            :style="{ width: `${((currentStepIndex + 1) / activeGuide.steps.length) * 100}%` }"
                        ></div>
                    </div>

                    <!-- Header -->
                    <div class="p-8 pb-4">
                        <div class="flex items-center justify-between mb-2">
                            <h2 class="text-xs font-bold uppercase tracking-widest text-indigo-500">
                                {{ activeGuide.title }} &bull; Step {{ currentStepIndex + 1 }} of {{ activeGuide.steps.length }}
                            </h2>
                        </div>
                        <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-4">
                            {{ currentStep.title }}
                        </h1>
                        <p class="text-lg text-slate-600 dark:text-slate-300 leading-relaxed max-w-3xl">
                            {{ currentStep.text }}
                        </p>
                    </div>

                    <!-- Visual Interactive Stage -->
                    <div class="flex-1 bg-slate-50 dark:bg-slate-900/50 relative overflow-hidden flex items-center justify-center p-8">
                        <Transition
                            mode="out-in"
                            enter-active-class="transition duration-500 ease-out"
                            enter-from-class="opacity-0 scale-95 translate-y-4"
                            enter-to-class="opacity-100 scale-100 translate-y-0"
                            leave-active-class="transition duration-300 ease-in"
                            leave-from-class="opacity-100 scale-100 translate-y-0"
                            leave-to-class="opacity-0 scale-105 -translate-y-4"
                        >
                            <div :key="currentStep.visual" class="w-full max-w-2xl">
                                
                                <!-- VISUAL: Sidebar Demo -->
                                <div v-if="currentStep.visual === 'sidebar-demo'" class="bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 p-4 flex gap-4">
                                    <div class="w-16 bg-slate-100 dark:bg-slate-700 rounded-lg h-40 flex flex-col items-center py-4 gap-3">
                                        <div class="w-8 h-8 rounded-full bg-indigo-500 flex items-center justify-center text-white font-bold">J</div>
                                        <div class="w-8 h-8 rounded bg-slate-200 dark:bg-slate-600"></div>
                                        <div class="w-8 h-8 rounded bg-slate-200 dark:bg-slate-600"></div>
                                        <div class="w-8 h-8 rounded bg-indigo-100 dark:bg-indigo-900/50 border border-indigo-500"></div>
                                    </div>
                                    <div class="flex-1 space-y-3 py-2">
                                        <div class="h-4 bg-slate-100 dark:bg-slate-700 rounded w-3/4"></div>
                                        <div class="h-4 bg-slate-100 dark:bg-slate-700 rounded w-1/2"></div>
                                        <div class="h-20 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg border border-indigo-100 dark:border-indigo-800 p-3 flex items-center justify-center text-center text-indigo-800 dark:text-indigo-200 text-sm font-medium">
                                            The active module is highlighted.
                                        </div>
                                    </div>
                                </div>

                                <!-- VISUAL: Cards Demo -->
                                <div v-else-if="currentStep.visual === 'cards-demo'" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div class="bg-indigo-600 text-white p-4 rounded-xl shadow-lg transform hover:-translate-y-1 transition-transform">
                                        <div class="h-8 w-8 bg-white/20 rounded-lg mb-3"></div>
                                        <div class="text-2xl font-bold">3</div>
                                        <div class="text-xs opacity-80">New Orders</div>
                                    </div>
                                    <div class="bg-white dark:bg-slate-800 p-4 rounded-xl shadow border border-slate-200 dark:border-slate-700 opacity-60">
                                        <div class="h-8 w-8 bg-orange-100 text-orange-600 rounded-lg mb-3"></div>
                                        <div class="text-2xl font-bold text-slate-900 dark:text-white">12</div>
                                        <div class="text-xs text-slate-500">In Transit</div>
                                    </div>
                                    <div class="bg-white dark:bg-slate-800 p-4 rounded-xl shadow border border-slate-200 dark:border-slate-700 opacity-60">
                                        <div class="h-8 w-8 bg-emerald-100 text-emerald-600 rounded-lg mb-3"></div>
                                        <div class="text-2xl font-bold text-slate-900 dark:text-white">Paid</div>
                                        <div class="text-xs text-slate-500">Last 30 Days</div>
                                    </div>
                                </div>

                                <!-- VISUAL: Theme Demo -->
                                <div v-else-if="currentStep.visual === 'theme-demo'" class="flex flex-col items-center">
                                    <div 
                                        class="w-full max-w-sm rounded-2xl shadow-2xl transition-all duration-500 p-6 border"
                                        :class="isDarkDemo ? 'bg-slate-800 border-slate-700' : 'bg-white border-slate-200'"
                                    >
                                        <div class="flex justify-between items-center mb-6">
                                            <div class="h-4 w-24 rounded" :class="isDarkDemo ? 'bg-slate-600' : 'bg-slate-200'"></div>
                                            <button @click="toggleDemoTheme" class="p-2 rounded-full hover:bg-black/5 dark:hover:bg-white/5 transition-colors">
                                                <MoonIcon v-if="!isDarkDemo" class="w-6 h-6 text-slate-500" />
                                                <SunIcon v-else class="w-6 h-6 text-yellow-400" />
                                            </button>
                                        </div>
                                        <div class="space-y-3">
                                            <div class="h-3 w-full rounded" :class="isDarkDemo ? 'bg-slate-700' : 'bg-slate-100'"></div>
                                            <div class="h-3 w-4/5 rounded" :class="isDarkDemo ? 'bg-slate-700' : 'bg-slate-100'"></div>
                                        </div>
                                        <div class="mt-6 text-center text-sm font-bold" :class="isDarkDemo ? 'text-white' : 'text-slate-900'">
                                            Try clicking the icon!
                                        </div>
                                    </div>
                                </div>

                                <!-- VISUAL: PO Detail Demo -->
                                <div v-else-if="currentStep.visual === 'po-detail-demo'" class="bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                                     <div class="bg-slate-50 dark:bg-slate-900/50 p-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
                                         <div>
                                             <div class="text-xs text-slate-500 font-bold uppercase">Purchase Order</div>
                                             <div class="font-mono font-bold text-indigo-600">PO-2026-001</div>
                                         </div>
                                         <button class="bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 px-3 py-1.5 rounded-lg text-xs font-bold flex items-center gap-2 hover:bg-slate-50">
                                             <CloudArrowUpIcon class="w-4 h-4" /> PDF
                                         </button>
                                     </div>
                                     <div class="p-4 space-y-2">
                                         <div class="flex justify-between text-sm py-2 border-b border-slate-100 dark:border-slate-700">
                                             <span class="text-slate-500 dark:text-slate-400">Steel Pipe X-200</span>
                                             <span class="font-bold text-slate-900 dark:text-white">500 pcs</span>
                                         </div>
                                         <div class="flex justify-between text-sm py-2">
                                             <span class="text-slate-500 dark:text-slate-400">Total</span>
                                             <span class="font-bold text-slate-900 dark:text-white">$ 25,000</span>
                                         </div>
                                     </div>
                                </div>

                                <!-- VISUAL: Acknowledge Demo -->
                                <div v-else-if="currentStep.visual === 'acknowledge-demo'" class="flex flex-col items-center justify-center p-8 bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700">
                                    <div class="mb-4 text-center">
                                        <p class="text-slate-500 mb-4">Can you fulfill this order?</p>
                                        <button class="bg-emerald-500 text-white px-8 py-3 rounded-xl font-bold shadow-lg shadow-emerald-500/30 hover:scale-105 transition-transform flex items-center gap-2 mx-auto">
                                            <CheckCircleIcon class="w-5 h-5" />
                                            Acknowledge Order
                                        </button>
                                    </div>
                                    <div class="text-xs text-slate-400">Latest Delivery by: <span class="font-bold text-slate-600 dark:text-slate-300">Feb 28, 2026</span></div>
                                </div>

                                <!-- Default Placeholder -->
                                <div v-else class="p-12 bg-white/50 dark:bg-white/5 rounded-2xl border-2 border-dashed border-slate-300 dark:border-slate-600 flex items-center justify-center">
                                    <ArrowRightIcon class="w-12 h-12 text-slate-300 dark:text-slate-600" />
                                </div>

                            </div>
                        </Transition>
                    </div>

                    <!-- Footer Navigation -->
                    <div class="p-6 bg-white dark:bg-slate-800 border-t border-slate-100 dark:border-slate-700 flex justify-between items-center z-10">
                        <button
                            @click="prevStep"
                            :disabled="currentStepIndex === 0"
                            class="flex items-center gap-2 px-4 py-2 text-slate-500 hover:text-slate-800 dark:hover:text-white disabled:opacity-30 disabled:cursor-not-allowed font-semibold transition-colors"
                        >
                            <ChevronLeftIcon class="w-5 h-5" />
                            Previous
                        </button>

                        <button
                            v-if="currentStepIndex < activeGuide.steps.length - 1"
                            @click="nextStep"
                            class="group flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white rounded-xl shadow-lg shadow-indigo-500/20 hover:bg-indigo-700 hover:shadow-indigo-500/30 transition-all font-bold"
                        >
                            Next Step
                            <ChevronRightIcon class="w-5 h-5 group-hover:translate-x-1 transition-transform" />
                        </button>
                        <button
                            v-else
                            class="flex items-center gap-2 px-6 py-3 bg-emerald-500 text-white rounded-xl shadow-lg shadow-emerald-500/20 cursor-default font-bold"
                        >
                            <CheckCircleIcon class="w-5 h-5" />
                            Topic Completed
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </PortalLayout>
</template>
