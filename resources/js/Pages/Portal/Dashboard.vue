<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import { formatDate } from '@/helpers';
import { 
    ShoppingCartIcon, 
    TruckIcon, 
    BanknotesIcon,
    ArrowRightIcon,
    ClockIcon,
    ChartBarIcon,
    SparklesIcon,
    CpuChipIcon,
    BoltIcon
} from '@heroicons/vue/24/outline';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
} from 'chart.js';
import { Line } from 'vue-chartjs';

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
);

const props = defineProps({
    metrics: Object,
    recent_pos: Array,
    chart_data: Array,
});

// Chart Configuration
const chartData = {
  labels: props.chart_data.map(d => {
    const [year, month] = d.month.split('-');
    return new Date(year, month - 1).toLocaleString('default', { month: 'short' });
  }),
  datasets: [
    {
      label: 'Order Volume',
      backgroundColor: (ctx) => {
        const canvas = ctx.chart.ctx;
        const gradient = canvas.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(34, 211, 238, 0.2)'); // Cyan
        gradient.addColorStop(1, 'rgba(34, 211, 238, 0.0)');
        return gradient;
      },
      borderColor: '#22d3ee',
      borderWidth: 2,
      pointBackgroundColor: '#06b6d4',
      pointBorderColor: '#fff',
      pointHoverBackgroundColor: '#fff',
      pointHoverBorderColor: '#22d3ee',
      data: props.chart_data.map(d => d.total),
      fill: true,
      tension: 0.4
    }
  ]
};

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
        backgroundColor: 'rgba(15, 23, 42, 0.9)',
        titleColor: '#e2e8f0',
        bodyColor: '#e2e8f0',
        borderColor: 'rgba(56, 189, 248, 0.3)',
        borderWidth: 1,
        padding: 10,
        cornerRadius: 8,
        displayColors: false,
        callbacks: {
            label: function(context) {
                let label = context.dataset.label || '';
                if (label) { label += ': '; }
                if (context.parsed.y !== null) {
                    label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(context.parsed.y);
                }
                return label;
            }
        }
    }
  },
  scales: {
    x: {
        grid: { display: false },
        ticks: { color: '#94a3b8' }
    },
    y: {
        grid: {
            borderDash: [5, 5],
            color: 'rgba(148, 163, 184, 0.1)'
        },
        ticks: {
            color: '#94a3b8',
            callback: function(value) {
                if (value >= 1000000) return (value/1000000).toFixed(1) + 'M';
                if (value >= 1000) return (value/1000).toFixed(0) + 'k';
                return value;
            }
        }
    }
  }
};
</script>

<template>
    <PortalLayout title="Vendor Dashboard">
        <!-- Dashboard Container with Tech Background -->
        <div class="relative min-h-screen">
            <!-- Background Elements -->
            <div class="absolute inset-0 pointer-events-none overflow-hidden -z-10">
                <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-indigo-500/10 rounded-full blur-[100px] animate-pulse"></div>
                <div class="absolute bottom-0 left-0 w-[500px] h-[500px] bg-cyan-500/10 rounded-full blur-[100px] animate-pulse" style="animation-delay: 2s;"></div>
            </div>

            <!-- Hero Section -->
            <div class="mb-10 animate-fade-in-up">
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-3 py-1 rounded-full bg-cyan-500/10 border border-cyan-500/20 text-cyan-400 text-xs font-bold uppercase tracking-widest flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-cyan-400 animate-ping"></span>
                                Live System
                            </span>
                            <span class="text-xs font-mono text-slate-500 dark:text-slate-400">USICS-AI-V2.0</span>
                        </div>
                        <h1 class="text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-slate-900 to-slate-600 dark:from-white dark:to-slate-400 mb-2 tracking-tight">
                            Good {{ new Date().getHours() < 12 ? 'Morning' : new Date().getHours() < 18 ? 'Afternoon' : 'Evening' }}, {{ $page.props.auth.user.name }}! 🚀
                        </h1>
                        <p class="text-slate-500 dark:text-slate-400 font-medium">Your digital command center is ready.</p>
                    </div>
                </div>
            </div>

            <!-- Main Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 mb-8">
                
                <!-- Left Column: Metrics (8 cols) -->
                <div class="lg:col-span-8 flex flex-col gap-6">
                    <!-- Cards Row -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- New Orders Card (Featured) -->
                        <div class="md:col-span-1 relative group md:hover:scale-105 transition-all duration-300">
                             <div class="absolute -inset-0.5 bg-gradient-to-r from-cyan-500 to-blue-600 rounded-2xl blur opacity-30 group-hover:opacity-100 transition duration-500"></div>
                             <div class="relative h-full bg-slate-900 rounded-2xl p-6 border border-white/10 overflow-hidden flex flex-col justify-between">
                                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                                    <ShoppingCartIcon class="w-24 h-24 text-white transform rotate-12" />
                                </div>
                                
                                <div>
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="p-2.5 rounded-xl bg-gradient-to-br from-cyan-500/20 to-blue-500/20 border border-cyan-500/30">
                                            <ShoppingCartIcon class="w-6 h-6 text-cyan-400" />
                                        </div>
                                        <div class="bg-cyan-500/20 text-cyan-300 text-[10px] font-bold px-2 py-1 rounded-full border border-cyan-500/20">NEW</div>
                                    </div>
                                    <h3 class="text-slate-400 text-sm font-medium uppercase tracking-wider mb-1">New Orders</h3>
                                    <div class="text-4xl font-black text-white tracking-tight mb-1">{{ metrics.pending_pos }}</div>
                                    <p class="text-cyan-400/80 text-xs flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-cyan-400"></span>
                                        Awaiting Action
                                    </p>
                                </div>

                                <Link href="/portal/purchase-orders" class="mt-6 flex items-center justify-between group/link w-full py-2 px-3 rounded-lg bg-white/5 hover:bg-white/10 border border-white/5 transition-colors">
                                    <span class="text-xs font-bold text-white">Process Now</span>
                                    <ArrowRightIcon class="w-4 h-4 text-white group-hover/link:translate-x-1 transition-transform" />
                                </Link>
                             </div>
                        </div>

                        <!-- On the Way Card -->
                        <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 shadow-lg relative overflow-hidden group hover:border-orange-500/30 transition-colors">
                            <div class="absolute top-0 right-0 w-20 h-20 bg-orange-500/5 rounded-full -mr-10 -mt-10 group-hover:bg-orange-500/10 transition-colors"></div>
                            <div class="flex items-center gap-3 mb-4">
                                <div class="p-2.5 rounded-xl bg-orange-500/10 text-orange-500">
                                    <TruckIcon class="w-6 h-6" />
                                </div>
                                <span class="font-bold text-slate-700 dark:text-slate-300 text-sm">On Delivery</span>
                            </div>
                            <div class="text-3xl font-black text-slate-900 dark:text-white mb-1">{{ metrics.active_deliveries }}</div>
                            <p class="text-slate-400 text-xs">Active shipments</p>
                             <div class="mt-4 w-full bg-slate-100 dark:bg-slate-700 h-1.5 rounded-full overflow-hidden">
                                <div class="h-full bg-orange-500 w-2/3 rounded-full animate-pulse"></div>
                            </div>
                        </div>

                        <!-- Unpaid Invoice Card -->
                        <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 shadow-lg relative overflow-hidden group hover:border-emerald-500/30 transition-colors">
                            <div class="absolute top-0 right-0 w-20 h-20 bg-emerald-500/5 rounded-full -mr-10 -mt-10 group-hover:bg-emerald-500/10 transition-colors"></div>
                            <div class="flex items-center gap-3 mb-4">
                                <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-500">
                                    <BanknotesIcon class="w-6 h-6" />
                                </div>
                                <span class="font-bold text-slate-700 dark:text-slate-300 text-sm">Invoices</span>
                            </div>
                             <div class="text-xl font-black text-slate-900 dark:text-white mb-1 truncate" :title="metrics.unpaid_invoices_amount">
                                {{ Number(metrics.unpaid_invoices_amount).toLocaleString('id-ID', { notation: "compact", maximumFractionDigits: 1 }) }}
                            </div>
                            <p class="text-slate-400 text-xs">Pending payment</p>
                            <Link href="/portal/invoices" class="mt-4 block text-xs font-bold text-emerald-600 hover:text-emerald-500 uppercase tracking-wide">
                                View Details &rarr;
                            </Link>
                        </div>
                    </div>

                    <!-- Chart Section -->
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700 p-6 flex-1 relative overflow-hidden">
                        <!-- AI Decorative Header -->
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h3 class="font-bold text-slate-900 dark:text-white text-lg flex items-center gap-2">
                                    Order Analytics
                                    <span class="px-2 py-0.5 rounded text-[10px] bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 font-mono">AI-POWERED</span>
                                </h3>
                                <p class="text-slate-500 text-xs">Real-time volume tracking</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                                    <SparklesIcon class="w-4 h-4 text-amber-400" />
                                </button>
                                <select class="text-xs bg-slate-100 dark:bg-slate-700 border-none rounded-lg px-3 py-1.5 text-slate-600 dark:text-slate-300 font-bold outline-none cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                                    <option>Last 6 Months</option>
                                    <option>This Year</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="h-[300px] w-full relative z-10">
                            <Line :data="chartData" :options="chartOptions" />
                        </div>
                    </div>
                </div>

                <!-- Right Column: Recent Activity & Actions (4 cols) -->
                <div class="lg:col-span-4 flex flex-col gap-6">
                    <!-- Quick Actions -->
                    <div class="bg-gradient-to-br from-indigo-900 to-slate-900 rounded-2xl shadow-xl border border-indigo-500/20 p-6 text-white relative overflow-hidden">
                        <div class="absolute inset-0 bg-[url('/images/grid.svg')] opacity-10"></div>
                        <h3 class="font-bold mb-4 flex items-center gap-2 relative z-10">
                            <BoltIcon class="w-5 h-5 text-yellow-400" />
                            Quick Actions
                        </h3>
                        <div class="space-y-3 relative z-10">
                            <Link :href="route('portal.purchase-orders.index')" class="flex items-center p-3 rounded-xl bg-white/5 hover:bg-indigo-500/20 border border-white/5 hover:border-indigo-500/50 transition-all group backdrop-blur-sm cursor-pointer">
                                <div class="p-2 rounded-lg bg-indigo-500/20 text-indigo-300 mr-3 group-hover:bg-indigo-500 group-hover:text-white transition-colors">
                                    <ShoppingCartIcon class="w-5 h-5" />
                                </div>
                                <div>
                                    <div class="font-bold text-sm text-indigo-100">Review Orders</div>
                                    <div class="text-[10px] text-indigo-300/60">Check pending requests</div>
                                </div>
                                <ArrowRightIcon class="w-4 h-4 text-indigo-400 ml-auto opacity-0 group-hover:opacity-100 transition-opacity -translate-x-2 group-hover:translate-x-0" />
                            </Link>

                             <Link :href="route('portal.deliveries.index')" class="flex items-center p-3 rounded-xl bg-white/5 hover:bg-orange-500/20 border border-white/5 hover:border-orange-500/50 transition-all group backdrop-blur-sm cursor-pointer">
                                <div class="p-2 rounded-lg bg-orange-500/20 text-orange-300 mr-3 group-hover:bg-orange-500 group-hover:text-white transition-colors">
                                    <TruckIcon class="w-5 h-5" />
                                </div>
                                <div>
                                    <div class="font-bold text-sm text-orange-100">Track Shipments</div>
                                    <div class="text-[10px] text-orange-300/60">Monitor delivery status</div>
                                </div>
                                 <ArrowRightIcon class="w-4 h-4 text-orange-400 ml-auto opacity-0 group-hover:opacity-100 transition-opacity -translate-x-2 group-hover:translate-x-0" />
                            </Link>
                        </div>
                    </div>

                    <!-- Recent Orders List -->
                     <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700 flex flex-col flex-1 overflow-hidden">
                        <div class="p-5 border-b border-slate-100 dark:border-slate-700/50 flex justify-between items-center bg-slate-50/50 dark:bg-slate-800/80 backdrop-blur-sm">
                            <h3 class="font-bold text-slate-900 dark:text-white flex items-center gap-2 text-sm">
                                <ClockIcon class="w-4 h-4 text-slate-400" />
                                Recent Activity
                            </h3>
                            <Link href="/portal/purchase-orders" class="text-[10px] font-black text-indigo-500 hover:text-indigo-600 uppercase tracking-wider bg-indigo-50 dark:bg-indigo-900/30 px-2 py-1 rounded-md transition-colors">View All</Link>
                        </div>
                        <div class="flex-1 overflow-auto max-h-[400px]">
                            <div class="divide-y divide-slate-100 dark:divide-slate-700/50">
                                <div v-for="po in recent_pos" :key="po.id" class="p-4 hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors group cursor-default">
                                    <div class="flex justify-between items-start mb-1">
                                        <span class="font-bold text-slate-800 dark:text-slate-200 text-sm group-hover:text-indigo-400 transition-colors">{{ po.po_number }}</span>
                                        <span class="text-[10px] font-mono text-slate-400">{{ formatDate(po.order_date) }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                       <div class="flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full" 
                                                :class="{
                                                    'bg-purple-500 animate-pulse': po.status === 'ordered',
                                                    'bg-teal-500': po.status === 'acknowledged',
                                                    'bg-rose-500': po.status === 'rejected',
                                                    'bg-green-500': po.status === 'approved',
                                                }"
                                            ></span>
                                           <span class="text-xs font-medium capitalize"
                                                :class="{
                                                    'text-purple-600 dark:text-purple-400': po.status === 'ordered',
                                                    'text-teal-600 dark:text-teal-400': po.status === 'acknowledged',
                                                    'text-rose-600 dark:text-rose-400': po.status === 'rejected',
                                                    'text-green-600 dark:text-green-400': po.status === 'approved',
                                                }">
                                                {{ po.status }}
                                           </span>
                                       </div>
                                       <span class="text-[10px] text-slate-400 opacity-0 group-hover:opacity-100 transition-opacity">Details &rarr;</span>
                                    </div>
                                </div>
                                <div v-if="recent_pos.length === 0" class="p-8 text-center text-slate-400 text-xs">
                                    No recent orders found.
                                </div>
                            </div>
                        </div>
                     </div>
                </div>
            </div>
        </div>
    </PortalLayout>
</template>

<style scoped>
.animate-fade-in-up {
    animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
