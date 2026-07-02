<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed, onMounted, onUnmounted } from 'vue';
import { 
    MagnifyingGlassIcon, 
    ArrowLeftIcon,
    CalendarDaysIcon,
    PrinterIcon,
    ChartBarIcon,
    TableCellsIcon,
    ArrowLeftCircleIcon,
} from '@heroicons/vue/24/outline';
import { formatNumber } from '@/helpers';
import {
    Chart as ChartJS,
    CategoryScale, LinearScale, PointElement, LineElement, BarElement,
    Title, Tooltip, Legend, Filler,
} from 'chart.js';
import { Bar, Line } from 'vue-chartjs';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title, Tooltip, Legend, Filler);

const props = defineProps({
    dates: Array,
    matrix: Array,
    weeks: { type: Array, default: () => [] },
    filters: Object,
});

const search = ref(props.filters.search || '');
const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');
const mode = ref(props.filters.mode || 'daily');
const activeView = ref('chart'); // 'chart' or 'matrix'

// ─── Chart State ───
const chartLevel = ref('summary'); // summary, customer, item
const chartData = ref(null);
const chartLoading = ref(false);
const chartBreadcrumb = ref([{ label: 'All Customers', level: 'summary' }]);
const chartPeriod = ref('weekly');
const selectedCustomerId = ref(null);
const selectedProductId = ref(null);
const itemChartRef = ref(null);
let pulseAnimId = null;
let lastPulseTick = 0;

const loadChartData = async () => {
    chartLoading.value = true;
    try {
        const params = new URLSearchParams({
            start_date: startDate.value,
            end_date: endDate.value,
            search: search.value || '',
            level: chartLevel.value,
            period: chartPeriod.value,
        });
        if (selectedCustomerId.value) params.set('customer_id', selectedCustomerId.value);
        if (selectedProductId.value) params.set('product_id', selectedProductId.value);

        const res = await fetch(route('sales.planning.schedule.chart-data', undefined, false) + '?' + params.toString());
        chartData.value = await res.json();
    } catch (e) {
        console.error('Chart fetch error:', e);
    } finally {
        chartLoading.value = false;
    }
};

const drillDown = (item) => {
    if (chartLevel.value === 'summary') {
        chartLevel.value = 'customer';
        selectedCustomerId.value = item.id;
        chartBreadcrumb.value.push({ label: item.name, level: 'customer', id: item.id });
        loadChartData();
    } else if (chartLevel.value === 'customer') {
        chartLevel.value = 'item';
        selectedProductId.value = item.id;
        chartBreadcrumb.value.push({ label: item.name, level: 'item', id: item.id });
        loadChartData();
    }
};

const drillUp = (index) => {
    const target = chartBreadcrumb.value[index];
    chartBreadcrumb.value = chartBreadcrumb.value.slice(0, index + 1);
    chartLevel.value = target.level;
    if (target.level === 'summary') {
        selectedCustomerId.value = null;
        selectedProductId.value = null;
    } else if (target.level === 'customer') {
        selectedProductId.value = null;
    }
    loadChartData();
};

watch(chartPeriod, () => {
    if (chartLevel.value === 'item') loadChartData();
});

onMounted(() => { loadChartData(); });

// ─── Today Pulse Animation (item daily chart) ───
const todayItemIndex = computed(() => {
    if (!chartData.value || chartData.value.level !== 'item') return -1;
    const now = new Date();
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const data = chartData.value.data || [];

    if (chartPeriod.value === 'daily') {
        const day = now.getDate().toString().padStart(2, '0');
        const todayLabel = `${day}-${months[now.getMonth()]}`;
        return data.findIndex(t => t.label === todayLabel);
    }

    if (chartPeriod.value === 'weekly') {
        // Parse label format: "W2 (08-14 Jun)"
        const nowMs = now.getTime();
        const year = now.getFullYear();
        return data.findIndex(t => {
            const match = t.label.match(/\((\d{2})-(\d{2})\s+(\w{3})\)/);
            if (!match) return false;
            const monthIdx = months.indexOf(match[3]);
            if (monthIdx < 0) return false;
            const startDay = parseInt(match[1]);
            const endDay = parseInt(match[2]);
            let startMonthIdx = monthIdx;
            let startYear = year;
            if (startDay > endDay) {
                startMonthIdx = monthIdx - 1;
                if (startMonthIdx < 0) {
                    startMonthIdx = 11;
                    startYear = year - 1;
                }
            }
            const startMs = new Date(startYear, startMonthIdx, startDay).getTime();
            const endMs = new Date(year, monthIdx, endDay, 23, 59, 59).getTime();
            return nowMs >= startMs && nowMs <= endMs;
        });
    }

    if (chartPeriod.value === 'monthly') {
        const todayMonth = months[now.getMonth()];
        return data.findIndex(t => t.label.startsWith(todayMonth));
    }

    return -1;
});

const pulsingTodayPlugin = {
    id: 'pulsingToday',
    afterDraw(chart) {
        const todayIdx = chart.options.plugins?.pulsingToday?.todayIndex ?? -1;
        if (todayIdx < 0) return;
        // Cum. Actual is dataset index 3 (pulsing moves to actual line)
        const meta = chart.getDatasetMeta(3);
        if (!meta?.data?.[todayIdx]) return;
        const pt = meta.data[todayIdx];
        if (!pt || pt.x === undefined || pt.y === undefined) return;
        const { x, y } = pt;
        const ctx = chart.ctx;

        // Determine color based on balance (actual vs target)
        const targetVal = chart.data.datasets[2]?.data?.[todayIdx] ?? 0;
        const actualVal = chart.data.datasets[3]?.data?.[todayIdx] ?? 0;
        const balance = actualVal - targetVal;

        // Color scheme: minus=red, equal=green, over=blue
        let dotColor, ring1Color, ring2Color;
        if (balance < 0) {
            dotColor = '#ef4444';       // red
            ring1Color = [239, 68, 68];
            ring2Color = [252, 165, 165];
        } else if (balance === 0) {
            dotColor = '#10b981';       // green
            ring1Color = [16, 185, 129];
            ring2Color = [110, 231, 183];
        } else {
            dotColor = '#3b82f6';       // blue
            ring1Color = [59, 130, 246];
            ring2Color = [147, 197, 253];
        }

        const now = performance.now();
        const cycle = 2000;
        const t1 = (now % cycle) / cycle;
        const t2 = ((now + cycle * 0.5) % cycle) / cycle;
        ctx.save();
        // Outer pulsing ring 1
        ctx.beginPath();
        ctx.arc(x, y, 10 + t1 * 22, 0, Math.PI * 2);
        ctx.strokeStyle = `rgba(${ring1Color.join(',')}, ${Math.max(0, 0.85 * (1 - t1))})`;
        ctx.lineWidth = 2.5;
        ctx.stroke();
        // Outer pulsing ring 2 (half-cycle offset)
        ctx.beginPath();
        ctx.arc(x, y, 10 + t2 * 22, 0, Math.PI * 2);
        ctx.strokeStyle = `rgba(${ring2Color.join(',')}, ${Math.max(0, 0.6 * (1 - t2))})`;
        ctx.lineWidth = 1.5;
        ctx.stroke();
        // Central enlarged dot with white border
        ctx.beginPath();
        ctx.arc(x, y, 9, 0, Math.PI * 2);
        ctx.fillStyle = dotColor;
        ctx.fill();
        ctx.strokeStyle = 'white';
        ctx.lineWidth = 2.5;
        ctx.stroke();

        // ─── Info Card ───
        const fmt = (n) => Number(n ?? 0).toLocaleString('id-ID');
        const dateLabel = chart.data.labels?.[todayIdx] ?? 'Today';

        const padX = 12, padY = 9, lineH = 17;
        const cardW = 168, cardH = padY * 2 + lineH * 4 + 4;
        const r = 7;

        // Auto-position: right of dot unless too close to edge
        const area = chart.chartArea;
        let cx = x + 20;
        if (cx + cardW > area.right - 4) cx = x - cardW - 20;
        let cy = y - cardH / 2;
        if (cy < area.top + 4) cy = area.top + 4;
        if (cy + cardH > area.bottom - 4) cy = area.bottom - cardH - 4;

        // Card background with rounded corners
        ctx.beginPath();
        ctx.moveTo(cx + r, cy);
        ctx.lineTo(cx + cardW - r, cy);
        ctx.quadraticCurveTo(cx + cardW, cy, cx + cardW, cy + r);
        ctx.lineTo(cx + cardW, cy + cardH - r);
        ctx.quadraticCurveTo(cx + cardW, cy + cardH, cx + cardW - r, cy + cardH);
        ctx.lineTo(cx + r, cy + cardH);
        ctx.quadraticCurveTo(cx, cy + cardH, cx, cy + cardH - r);
        ctx.lineTo(cx, cy + r);
        ctx.quadraticCurveTo(cx, cy, cx + r, cy);
        ctx.closePath();
        ctx.fillStyle = 'rgba(10, 20, 40, 0.90)';
        ctx.fill();
        ctx.strokeStyle = 'rgba(59, 130, 246, 0.5)';
        ctx.lineWidth = 1;
        ctx.stroke();

        // Title: date label
        ctx.font = 'bold 10px system-ui,sans-serif';
        ctx.textBaseline = 'top';
        ctx.textAlign = 'left';
        ctx.fillStyle = '#60a5fa';
        ctx.fillText(`📍 ${dateLabel}`, cx + padX, cy + padY);

        // Separator line
        ctx.strokeStyle = 'rgba(59,130,246,0.25)';
        ctx.lineWidth = 0.8;
        ctx.beginPath();
        ctx.moveTo(cx + padX, cy + padY + lineH + 2);
        ctx.lineTo(cx + cardW - padX, cy + padY + lineH + 2);
        ctx.stroke();

        // Helper to draw row
        const drawRow = (label, value, labelColor, valueColor, rowY) => {
            ctx.font = '10px system-ui,sans-serif';
            ctx.textAlign = 'left';
            ctx.fillStyle = labelColor;
            ctx.fillText(label, cx + padX, rowY);
            ctx.font = 'bold 10px system-ui,sans-serif';
            ctx.textAlign = 'right';
            ctx.fillStyle = valueColor;
            ctx.fillText(value, cx + cardW - padX, rowY);
        };

        const row1Y = cy + padY + lineH + 8;
        const row2Y = row1Y + lineH;
        const row3Y = row2Y + lineH;

        drawRow('Cum. Target', fmt(targetVal), '#93c5fd', '#bfdbfe', row1Y);
        drawRow('Cum. Actual', fmt(actualVal), '#6ee7b7', '#a7f3d0', row2Y);

        const balLabel = balance >= 0 ? 'Over ▲' : 'Delay ▼';
        const balColor = balance >= 0 ? '#4ade80' : '#f87171';
        const balStr = (balance >= 0 ? '+' : '') + fmt(balance);
        drawRow(balLabel, balStr, balance >= 0 ? '#86efac' : '#fca5a5', balColor, row3Y);

        ctx.textAlign = 'left'; // reset
        ctx.restore();
    },
};

ChartJS.register(pulsingTodayPlugin);

const startPulseAnim = () => {
    stopPulseAnim();
    const tick = (ts) => {
        pulseAnimId = requestAnimationFrame(tick);
        if (ts - lastPulseTick < 50) return; // ~20fps
        lastPulseTick = ts;
        const chart = itemChartRef.value?.chart;
        if (chart && !chart._destroyed) chart.update('none');
    };
    pulseAnimId = requestAnimationFrame(tick);
};
const stopPulseAnim = () => {
    if (pulseAnimId !== null) { cancelAnimationFrame(pulseAnimId); pulseAnimId = null; }
};
watch(chartLevel, (level) => {
    if (level === 'item') startPulseAnim();
    else stopPulseAnim();
});
onUnmounted(() => stopPulseAnim());

// ─── Chart.js Configs ───
const summaryChartData = computed(() => {
    if (!chartData.value || chartData.value.level !== 'summary') return null;
    const d = chartData.value.data;
    
    // Hitung pencapaian (line) dengan batas maks 110%
    const achievements = d.map(c => {
        const sch = parseFloat(c.schedule) || 0;
        const del = parseFloat(c.delivery) || 0;
        if (sch > 0) {
            return Math.min(110, (del / sch) * 100);
        } else if (del > 0) {
            return 110;
        }
        return 0;
    });

    return {
        labels: d.map(c => c.code || 'N/A'),
        datasets: [
            { 
                type: 'bar',
                label: 'Schedule', 
                data: d.map(c => c.schedule), 
                backgroundColor: 'rgba(59,130,246,0.7)', 
                borderRadius: 4, 
                yAxisID: 'y',
                barPercentage: 0.9, 
                categoryPercentage: 0.85, 
                maxBarThickness: 45 
            },
            { 
                type: 'bar',
                label: 'Delivery', 
                data: d.map(c => c.delivery), 
                backgroundColor: 'rgba(16,185,129,0.8)', 
                borderRadius: 4, 
                yAxisID: 'y',
                barPercentage: 0.9, 
                categoryPercentage: 0.85, 
                maxBarThickness: 45 
            },
            {
                type: 'line',
                label: 'Achievement',
                data: achievements,
                borderColor: '#10b981',
                borderWidth: 2.5,
                pointRadius: 4,
                pointBackgroundColor: '#10b981',
                yAxisID: 'y1',
                fill: false,
                tension: 0.3
            }
        ],
    };
});

const chartHeight = computed(() => {
    return '380px';
});

const customerChartData = computed(() => {
    if (!chartData.value || chartData.value.level !== 'customer') return null;
    const d = chartData.value.data;

    // Hitung pencapaian per product, maks 110%
    const achievements = d.map(p => {
        const sch = parseFloat(p.schedule) || 0;
        const del = parseFloat(p.delivery) || 0;
        if (sch > 0) {
            return Math.min(110, (del / sch) * 100);
        } else if (del > 0) {
            return 110;
        }
        return 0;
    });

    return {
        labels: d.map(p => p.name),
        datasets: [
            {
                type: 'bar',
                label: 'Schedule',
                data: d.map(p => p.schedule),
                backgroundColor: 'rgba(59,130,246,0.7)',
                borderRadius: 4,
                yAxisID: 'y',
                barPercentage: 0.9,
                categoryPercentage: 0.85,
                maxBarThickness: 45
            },
            {
                type: 'bar',
                label: 'Delivery',
                data: d.map(p => p.delivery),
                backgroundColor: 'rgba(16,185,129,0.8)',
                borderRadius: 4,
                yAxisID: 'y',
                barPercentage: 0.9,
                categoryPercentage: 0.85,
                maxBarThickness: 45
            },
            {
                type: 'line',
                label: 'Achievement',
                data: achievements,
                borderColor: '#f59e0b',
                borderWidth: 2.5,
                pointRadius: 4,
                pointBackgroundColor: '#f59e0b',
                yAxisID: 'y1',
                fill: false,
                tension: 0.3,
            }
        ],
    };
});

const itemChartData = computed(() => {
    if (!chartData.value || chartData.value.level !== 'item') return null;
    const d = chartData.value.data;
    const todayIdx = todayItemIndex.value;
    return {
        labels: d.map(t => t.label),
        datasets: [
            { 
                type: 'bar', 
                label: 'Schedule', 
                data: d.map(t => t.schedule), 
                backgroundColor: 'rgba(148,163,184,0.4)', 
                borderRadius: 3, 
                order: 2,
                barPercentage: 0.9,
                categoryPercentage: 0.85,
                maxBarThickness: 45
            },
            { 
                type: 'bar', 
                label: 'Delivery', 
                data: d.map(t => t.delivery), 
                backgroundColor: 'rgba(16,185,129,0.7)', 
                borderRadius: 3, 
                order: 2,
                barPercentage: 0.9,
                categoryPercentage: 0.85,
                maxBarThickness: 45
            },
            {
                type: 'line', label: 'Cum. Target',
                data: d.map(t => t.cum_schedule),
                borderColor: '#3b82f6', borderDash: [6, 3], borderWidth: 2,
                pointRadius: 3,
                pointHoverRadius: 5,
                pointBackgroundColor: '#3b82f6',
                fill: false, tension: 0.3, order: 1
            },
            {
                type: 'line', label: 'Cum. Actual',
                data: d.map(t => t.cum_delivery),
                borderColor: '#10b981', borderWidth: 2.5,
                // Hide native point at today — plugin draws pulsing dot instead
                pointRadius: d.map((_, i) => (todayIdx >= 0 && i === todayIdx) ? 0 : 4),
                pointHoverRadius: d.map((_, i) => (todayIdx >= 0 && i === todayIdx) ? 12 : 6),
                pointBackgroundColor: '#10b981',
                fill: false, tension: 0.3, order: 1
            },
        ],
    };
});

const summaryChartOpts = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    onClick: (evt, elements) => {
        if (elements.length && chartData.value) {
            drillDown(chartData.value.data[elements[0].index]);
        }
    },
    plugins: {
        legend: {
            position: 'top',
            labels: { color: '#64748b', font: { size: 11, weight: 'bold' } }
        },
        tooltip: {
            padding: 10,
            callbacks: {
                title: (context) => {
                    const idx = context[0].dataIndex;
                    const item = chartData.value.data[idx];
                    return item ? `${item.name} [${item.code}]` : '';
                },
                label: (context) => {
                    let label = context.dataset.label || '';
                    if (label) {
                        label += ': ';
                    }
                    if (context.datasetIndex === 2) {
                        label += context.raw.toFixed(1) + '%';
                    } else {
                        label += formatNumber(context.raw);
                    }
                    return label;
                }
            }
        }
    },
    scales: {
        x: {
            grid: { display: false },
            ticks: { color: '#334155', font: { size: 10, weight: '600' } }
        },
        y: {
            type: 'linear',
            position: 'left',
            grid: { color: 'rgba(0,0,0,0.06)' },
            ticks: { color: '#64748b', font: { size: 10 } },
            title: { display: true, text: 'Qty (Schedule / Delivery)', color: '#64748b', font: { size: 10, weight: 'bold' } }
        },
        y1: {
            type: 'linear',
            position: 'right',
            grid: { drawOnChartArea: false },
            min: 0,
            max: 110,
            ticks: {
                color: '#10b981',
                font: { size: 10 },
                callback: (val) => val + '%'
            },
            title: { display: true, text: 'Achievement (%)', color: '#10b981', font: { size: 10, weight: 'bold' } }
        }
    }
}));

const horizontalBarOpts = computed(() => ({
    responsive: true, maintainAspectRatio: false, indexAxis: 'y',
    onClick: (evt, elements) => { if (elements.length && chartData.value) drillDown(chartData.value.data[elements[0].index]); },
    plugins: { legend: { position: 'top', labels: { color: '#64748b', font: { size: 11, weight: 'bold' } } }, tooltip: { padding: 10 } },
    scales: {
        x: { grid: { color: 'rgba(0,0,0,0.06)' }, ticks: { color: '#64748b', font: { size: 10 } } },
        y: { grid: { display: false }, ticks: { color: '#334155', font: { size: 11, weight: '600' } } },
    },
}));

const verticalBarOpts = computed(() => ({
    responsive: true, maintainAspectRatio: false,
    onClick: (evt, elements) => { if (elements.length && chartData.value) drillDown(chartData.value.data[elements[0].index]); },
    plugins: {
        legend: { position: 'top', labels: { color: '#64748b', font: { size: 11, weight: 'bold' } } },
        tooltip: {
            padding: 10,
            callbacks: {
                label: (context) => {
                    let label = context.dataset.label || '';
                    if (label) label += ': ';
                    if (context.dataset.yAxisID === 'y1') {
                        label += context.raw.toFixed(1) + '%';
                    } else {
                        label += formatNumber(context.raw);
                    }
                    return label;
                }
            }
        }
    },
    scales: {
        x: { grid: { display: false }, ticks: { color: '#334155', font: { size: 10, weight: '600' }, maxRotation: 45 } },
        y: {
            type: 'linear',
            position: 'left',
            grid: { color: 'rgba(0,0,0,0.06)' },
            ticks: { color: '#64748b', font: { size: 10 } },
            title: { display: true, text: 'Qty (Schedule / Delivery)', color: '#64748b', font: { size: 10, weight: 'bold' } }
        },
        y1: {
            type: 'linear',
            position: 'right',
            grid: { drawOnChartArea: false },
            min: 0,
            max: 110,
            ticks: {
                color: '#f59e0b',
                font: { size: 10 },
                callback: (val) => val + '%'
            },
            title: { display: true, text: 'Achievement (%)', color: '#f59e0b', font: { size: 10, weight: 'bold' } }
        }
    },
}));

const comboChartOpts = computed(() => ({
    responsive: true, maintainAspectRatio: false,
    plugins: {
        legend: { position: 'top', labels: { color: '#64748b', font: { size: 11, weight: 'bold' } } },
        tooltip: { padding: 10 },
        pulsingToday: { todayIndex: todayItemIndex.value },
    },
    scales: {
        x: { grid: { display: false }, ticks: { color: '#334155', font: { size: 10 }, maxRotation: 45 } },
        y: { grid: { color: 'rgba(0,0,0,0.06)' }, ticks: { color: '#64748b', font: { size: 10 } } },
    },
}));

const handleSearch = () => {
    router.get(route('sales.planning.schedule.comparison', undefined, false), {
        search: search.value, 
        start_date: startDate.value,
        end_date: endDate.value,
        mode: mode.value,
    }, { preserveState: true, replace: true });
};

watch([search, startDate, endDate], () => {
    handleSearch();
    loadChartData();
});

const toggleMode = (newMode) => {
    mode.value = newMode;
    handleSearch();
};

const formatDateShort = (dateStr) => {
    if (mode.value === 'weekly') {
        const week = props.weeks.find(w => w.key === dateStr);
        return week ? week.label : dateStr;
    }
    const d = new Date(dateStr);
    const day = d.getDate().toString().padStart(2, '0');
    const month = d.toLocaleString('en-US', { month: 'short' });
    return `${day}-${month}`;
};

const formatColumnHeader = (dateStr) => {
    if (mode.value === 'weekly') {
        return dateStr;
    }
    return formatDateShort(dateStr);
};

const getBalanceClass = (val) => {
    if (val < 0) return 'text-red-600 dark:text-red-400 font-bold';
    if (val > 0) return 'text-blue-600 dark:text-blue-400 font-bold';
    return 'text-slate-400';
};

const isToday = (dateStr) => {
    if (mode.value === 'weekly') return false;
    const today = new Date().toISOString().split('T')[0];
    return dateStr === today;
};

const accumulateBalance = ref(false);

const getBalanceValue = (product, date) => {
    if (!accumulateBalance.value) {
        return product.daily[date]?.bal ?? 0;
    }
    
    let totalSch = 0;
    let totalAct = 0;
    
    for (const d of props.dates) {
        totalSch += product.daily[d]?.sch ?? 0;
        totalAct += product.daily[d]?.act ?? 0;
        if (d === date) {
            break;
        }
    }
    
    return totalAct - totalSch;
};

const printOfficial = () => {
    const params = new URLSearchParams({
        start_date: startDate.value,
        end_date: endDate.value,
        search: search.value || '',
        mode: mode.value,
        accumulate: accumulateBalance.value ? '1' : '0',
    });
    window.open(route('sales.planning.schedule.print', undefined, false) + '?' + params.toString(), '_blank');
};
</script>

<template>
    <Head title="Monitoring Schedule vs Actual" />

    <AppLayout title="Monitoring Schedule vs Actual">
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <Link :href="route('sales.planning.schedule.index', undefined, false)" class="p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors no-print">
                        <ArrowLeftIcon class="w-5 h-5" />
                    </Link>
                    <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">
                        {{ mode === 'weekly' ? 'Weekly' : 'Daily' }} Delivery Monitoring Matrix
                    </h2>
                </div>
            </div>
        </template>

        <div class="p-4 sm:p-6 lg:p-8">
            <!-- Filter Bar (Hidden on Print) -->
            <div class="glass-card rounded-2xl p-6 mb-6 no-print">
                <div class="flex flex-col lg:flex-row justify-between items-center gap-4">
                    <div class="flex flex-wrap items-center gap-4 w-full lg:w-auto">
                        <div class="relative w-full sm:w-56">
                            <input 
                                v-model="search"
                                type="text" 
                                placeholder="Search Customer / Product..." 
                                class="w-full pl-10 pr-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:ring-blue-500 focus:border-blue-500 text-sm"
                            >
                            <MagnifyingGlassIcon class="w-5 h-5 text-slate-400 absolute left-3 top-2.5" />
                        </div>
                        <div class="flex items-center gap-2">
                            <input 
                                v-model="startDate"
                                type="date" 
                                class="rounded-lg border border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:ring-blue-500 focus:border-blue-500 py-2"
                            >
                            <span class="text-slate-400">to</span>
                            <input 
                                v-model="endDate"
                                type="date" 
                                class="rounded-lg border border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:ring-blue-500 focus:border-blue-500 py-2"
                            >
                        </div>
                        <!-- Daily/Weekly Toggle -->
                        <div class="flex items-center bg-slate-200 dark:bg-slate-700 rounded-lg p-0.5">
                            <button @click="toggleMode('daily')" 
                                :class="mode === 'daily' ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'"
                                class="px-4 py-1.5 rounded-md text-xs font-bold uppercase tracking-wider transition-all">
                                Daily
                            </button>
                            <button @click="toggleMode('weekly')" 
                                :class="mode === 'weekly' ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'"
                                class="px-4 py-1.5 rounded-md text-xs font-bold uppercase tracking-wider transition-all">
                                Weekly
                            </button>
                        </div>
                        <!-- Accumulate Balance Checkbox -->
                        <label class="flex items-center gap-2 cursor-pointer bg-slate-100 dark:bg-slate-850 border border-slate-200 dark:border-slate-700/60 px-3 py-1.5 rounded-lg text-xs font-bold text-slate-700 dark:text-slate-350 select-none hover:bg-slate-200 dark:hover:bg-slate-800 transition-colors">
                            <input 
                                v-model="accumulateBalance" 
                                type="checkbox" 
                                class="rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500 w-4 h-4"
                            >
                            Akumulasi Balance
                        </label>
                    </div>
                    
                    <div class="flex items-center gap-4">
                        <!-- View Toggle: Chart | Matrix -->
                        <div class="flex items-center bg-slate-200 dark:bg-slate-700 rounded-lg p-0.5">
                            <button @click="activeView = 'chart'" 
                                :class="activeView === 'chart' ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'"
                                class="px-3 py-1.5 rounded-md text-xs font-bold uppercase tracking-wider transition-all flex items-center gap-1">
                                <ChartBarIcon class="w-3.5 h-3.5" /> Chart
                            </button>
                            <button @click="activeView = 'matrix'" 
                                :class="activeView === 'matrix' ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'"
                                class="px-3 py-1.5 rounded-md text-xs font-bold uppercase tracking-wider transition-all flex items-center gap-1">
                                <TableCellsIcon class="w-3.5 h-3.5" /> Matrix
                            </button>
                        </div>
                        <div class="flex gap-4 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                            <div class="flex items-center gap-1"><div class="w-2 h-2 bg-red-500 rounded-full"></div> Minus Balance</div>
                            <div class="flex items-center gap-1"><div class="w-2 h-2 bg-blue-500 rounded-full"></div> Surplus</div>
                            <div class="flex items-center gap-1"><div class="w-2 h-2 bg-slate-200 dark:bg-slate-700 rounded-full"></div> On-time</div>
                        </div>
                        <button @click="printOfficial" class="flex items-center gap-2 bg-blue-900 hover:bg-blue-800 text-white px-5 py-2.5 rounded-lg text-sm font-bold transition-colors shadow-lg hover:shadow-xl">
                            <PrinterIcon class="w-4 h-4" />
                            Print Official
                        </button>
                    </div>
                </div>
            </div>

            <!-- ═══ CHART SECTION ═══ -->
            <div v-if="activeView === 'chart'" class="glass-card rounded-2xl p-6 mb-6 border border-slate-200 dark:border-slate-700/50 shadow-xl">
                <!-- Breadcrumb -->
                <div class="flex items-center gap-2 mb-4 text-sm">
                    <template v-for="(crumb, idx) in chartBreadcrumb" :key="idx">
                        <span v-if="idx > 0" class="text-slate-400">/</span>
                        <button 
                            @click="drillUp(idx)" 
                            :class="idx === chartBreadcrumb.length - 1 ? 'text-blue-600 dark:text-blue-400 font-bold' : 'text-slate-500 hover:text-blue-600 dark:hover:text-slate-300'"
                            class="transition-colors"
                        >
                            {{ crumb.label }}
                        </button>
                    </template>
                    <button v-if="chartBreadcrumb.length > 1" @click="drillUp(chartBreadcrumb.length - 2)" class="ml-2 p-1 rounded-full hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                        <ArrowLeftCircleIcon class="w-5 h-5 text-slate-400" />
                    </button>
                </div>

                <!-- KPI Cards -->
                <div v-if="chartData && chartData.kpi" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-xl p-4 text-center">
                        <p class="text-[10px] text-blue-500 dark:text-blue-400 font-bold uppercase tracking-wider">Total Schedule</p>
                        <p class="text-2xl font-black text-blue-700 dark:text-blue-300 mt-1">{{ formatNumber(chartData.kpi.total_schedule) }}</p>
                    </div>
                    <div class="bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-xl p-4 text-center">
                        <p class="text-[10px] text-emerald-500 dark:text-emerald-400 font-bold uppercase tracking-wider">Total Delivered</p>
                        <p class="text-2xl font-black text-emerald-700 dark:text-emerald-300 mt-1">{{ formatNumber(chartData.kpi.total_delivery) }}</p>
                    </div>
                    <div class="border rounded-xl p-4 text-center" :class="chartData.kpi.achievement >= 90 ? 'bg-green-50 dark:bg-green-900/30 border-green-200 dark:border-green-800' : chartData.kpi.achievement >= 70 ? 'bg-amber-50 dark:bg-amber-900/30 border-amber-200 dark:border-amber-800' : 'bg-red-50 dark:bg-red-900/30 border-red-200 dark:border-red-800'">
                        <p class="text-[10px] font-bold uppercase tracking-wider" :class="chartData.kpi.achievement >= 90 ? 'text-green-500' : chartData.kpi.achievement >= 70 ? 'text-amber-500' : 'text-red-500'">Achievement</p>
                        <p class="text-2xl font-black mt-1" :class="chartData.kpi.achievement >= 90 ? 'text-green-700 dark:text-green-300' : chartData.kpi.achievement >= 70 ? 'text-amber-700 dark:text-amber-300' : 'text-red-700 dark:text-red-300'">{{ chartData.kpi.achievement }}%</p>
                    </div>
                    <div class="border rounded-xl p-4 text-center" :class="chartData.kpi.shortfall < 0 ? 'bg-red-50 dark:bg-red-900/30 border-red-200 dark:border-red-800' : 'bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700'">
                        <p class="text-[10px] font-bold uppercase tracking-wider" :class="chartData.kpi.shortfall < 0 ? 'text-red-500' : 'text-slate-500'">Shortfall</p>
                        <p class="text-2xl font-black mt-1" :class="chartData.kpi.shortfall < 0 ? 'text-red-700 dark:text-red-300' : 'text-slate-700 dark:text-slate-300'">{{ formatNumber(chartData.kpi.shortfall) }}</p>
                    </div>
                </div>

                <!-- Period toggle for Level 3 -->
                <div v-if="chartLevel === 'item'" class="flex items-center gap-2 mb-4">
                    <span class="text-xs text-slate-500 font-bold uppercase">Period:</span>
                    <div class="flex items-center bg-slate-200 dark:bg-slate-700 rounded-lg p-0.5">
                        <button v-for="p in ['daily','weekly','monthly']" :key="p" @click="chartPeriod = p"
                            :class="chartPeriod === p ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'"
                            class="px-3 py-1 rounded-md text-xs font-bold uppercase tracking-wider transition-all capitalize">
                            {{ p }}
                        </button>
                    </div>
                </div>

                <!-- Chart Area -->
                <div class="relative" :style="{ height: chartHeight }">
                    <div v-if="chartLoading" class="absolute inset-0 flex items-center justify-center bg-white/60 dark:bg-slate-900/60 z-10 rounded-xl">
                        <div class="flex items-center gap-3 text-slate-500">
                            <svg class="animate-spin h-6 w-6" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            <span class="text-sm font-medium">Loading chart data...</span>
                        </div>
                    </div>

                    <!-- Level 1: Summary (vertical combo chart) -->
                    <Bar v-if="chartLevel === 'summary' && summaryChartData" :key="'summary'" :data="summaryChartData" :options="summaryChartOpts" />

                    <!-- Level 2: Customer Detail (vertical bar) -->
                    <Bar v-else-if="chartLevel === 'customer' && customerChartData" :key="'customer-' + selectedCustomerId" :data="customerChartData" :options="verticalBarOpts" />

                    <!-- Level 3: Item Timeline (combo) -->
                    <Bar v-else-if="chartLevel === 'item' && itemChartData" ref="itemChartRef" :key="'item-' + selectedProductId + '-' + chartPeriod" :data="itemChartData" :options="comboChartOpts" />

                    <div v-else-if="!chartLoading" class="flex items-center justify-center h-full text-slate-400">
                        <p class="text-sm">No chart data available for the selected filters.</p>
                    </div>
                </div>

                <p v-if="chartLevel !== 'item'" class="text-center text-[10px] text-slate-400 mt-3 italic">Click a bar to drill down into details</p>
            </div>

            <!-- Matrix Table -->
            <div v-show="activeView === 'matrix'" class="glass-card rounded-2xl overflow-hidden shadow-2xl border border-slate-200 dark:border-slate-700/50 matrix-container">
                <div class="overflow-x-auto overflow-y-auto max-h-[750px] relative">
                    <table class="w-full border-collapse text-[12px]">
                        <thead class="bg-slate-100 dark:bg-slate-800 sticky top-0 z-30 font-bold">
                            <tr>
                                <th class="p-3 border border-slate-300 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 text-left sticky left-0 z-40 min-w-[200px] shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]" rowspan="2">
                                    <span class="text-slate-900 dark:text-slate-100 font-black uppercase tracking-wider">Customer & PO</span>
                                </th>
                                <th class="p-3 border border-slate-300 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 text-left sticky left-[200px] z-40 min-w-[250px] shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]" rowspan="2">
                                    <span class="text-slate-900 dark:text-slate-100 font-black uppercase tracking-wider">Product Detail</span>
                                </th>
                                <th class="p-3 border border-slate-300 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 text-center sticky left-[450px] z-40 min-w-[85px] shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]" rowspan="2">
                                    <span class="text-slate-900 dark:text-slate-100 font-black uppercase tracking-wider">DATA</span>
                                </th>
                                <th v-for="date in dates" :key="date" 
                                    class="p-2 border border-slate-300 dark:border-slate-700 text-center font-black whitespace-nowrap"
                                    :class="[
                                        isToday(date) ? 'bg-indigo-100 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 ring-2 ring-inset ring-indigo-500/20' : 'text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800',
                                        mode === 'weekly' ? 'min-w-[100px]' : 'min-w-[65px]'
                                    ]"
                                >
                                    <div>{{ formatColumnHeader(date) }}</div>
                                    <div v-if="mode === 'weekly'" class="text-[9px] font-medium text-slate-500 dark:text-slate-400 mt-0.5">{{ formatDateShort(date) }}</div>
                                </th>
                                <th class="p-3 border border-slate-300 dark:border-slate-700 text-center bg-slate-200 dark:bg-slate-950 sticky right-0 z-30 min-w-[85px] shadow-[-2px_0_5px_-2px_rgba(0,0,0,0.1)]" rowspan="2">
                                    <span class="text-slate-900 dark:text-slate-100 font-black uppercase tracking-wider">TOTAL</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            <template v-for="(customer, cIdx) in matrix" :key="cIdx">
                                <template v-for="(product, pIdx) in customer.products" :key="pIdx">
                                    <!-- ROW 1: SCHEDULE -->
                                    <tr class="group hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors border-t-2 border-slate-200 dark:border-slate-700">
                                        <td v-if="pIdx === 0" 
                                            class="p-4 border border-slate-200 dark:border-slate-800 font-black text-indigo-700 dark:text-indigo-400 bg-white dark:bg-slate-950 sticky left-0 z-10 align-top shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]"
                                            :rowspan="customer.products.length * 3"
                                        >
                                            <div class="line-clamp-3 text-sm leading-tight">{{ customer.customer_name }}</div>
                                            <div class="text-[10px] font-mono text-slate-500 dark:text-slate-400 mt-2 uppercase tracking-tighter bg-slate-100 dark:bg-slate-800/50 px-2 py-0.5 rounded-full inline-block">{{ customer.customer_code }}</div>
                                        </td>
                                        <td class="p-4 border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 sticky left-[200px] z-10 align-top shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]" rowspan="3">
                                            <div class="font-black text-slate-900 dark:text-white leading-tight break-words">{{ product.product_name }}</div>
                                            <div class="flex justify-between items-center mt-2 group-hover:bg-slate-100 dark:group-hover:bg-slate-800 p-1 rounded transition-colors">
                                                <span class="text-[10px] font-mono text-slate-600 dark:text-slate-400 uppercase font-bold">{{ product.sku }}</span>
                                                <span class="text-[10px] font-black bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 px-1.5 rounded">{{ product.unit }}</span>
                                            </div>
                                            <div v-if="product.po_number" class="text-[10px] font-bold text-slate-500 dark:text-slate-400 mt-2 border-l-2 border-slate-200 dark:border-slate-700 pl-2">PO: {{ product.po_number }}</div>
                                        </td>
                                        <td class="p-2 border border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-900 sticky left-[450px] z-10 font-black text-slate-600 dark:text-slate-400 uppercase text-[10px] shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">
                                            SCHEDULE
                                        </td>
                                        <td v-for="date in dates" :key="date" class="p-2 border border-slate-200 dark:border-slate-800 text-right font-mono text-slate-700 dark:text-slate-300 font-medium">
                                            {{ product.daily[date]?.sch > 0 ? formatNumber(product.daily[date].sch) : '-' }}
                                        </td>
                                        <td class="p-2 border border-slate-200 dark:border-slate-800 text-right bg-slate-50 dark:bg-slate-900 font-mono font-black text-slate-900 dark:text-white sticky right-0 z-10 transition-colors shadow-[-2px_0_5px_-2px_rgba(0,0,0,0.1)]">
                                            {{ formatNumber(product.totals.sch) }}
                                        </td>
                                    </tr>
                                    <!-- ROW 2: DELIVERY -->
                                    <tr class="group hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                        <td class="p-2 border border-slate-200 dark:border-slate-800 bg-slate-100/30 dark:bg-slate-800/20 sticky left-[450px] z-10 font-black text-blue-600 dark:text-blue-400 uppercase text-[10px] shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">
                                            DELIVERY
                                        </td>
                                        <td v-for="date in dates" :key="date" class="p-2 border border-slate-200 dark:border-slate-800 text-right font-mono font-bold text-blue-700 dark:text-blue-300">
                                            {{ product.daily[date]?.act > 0 ? formatNumber(product.daily[date].act) : '-' }}
                                        </td>
                                        <td class="p-2 border border-slate-200 dark:border-slate-800 text-right bg-blue-50/40 dark:bg-blue-900/20 font-mono font-black text-blue-800 dark:text-blue-200 sticky right-0 z-10 transition-colors shadow-[-2px_0_5px_-2px_rgba(0,0,0,0.1)]">
                                            {{ formatNumber(product.totals.act) }}
                                        </td>
                                    </tr>
                                    <!-- ROW 3: BALANCE -->
                                    <tr class="group bg-slate-100/50 dark:bg-slate-800/40 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                        <td class="p-2 border border-slate-200 dark:border-slate-800 bg-slate-200/50 dark:bg-slate-700/50 sticky left-[450px] z-10 font-black text-slate-900 dark:text-white uppercase text-[10px] shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">
                                            BALANCE
                                        </td>
                                        <td v-for="date in dates" :key="date" 
                                            class="p-2 border border-slate-200 dark:border-slate-800 text-right font-mono"
                                            :class="getBalanceValue(product, date) < 0 ? 'text-red-700 dark:text-red-400 font-black bg-red-100/40 dark:bg-red-900/20' : getBalanceValue(product, date) > 0 ? 'text-blue-700 dark:text-blue-400 font-black bg-blue-50/30' : 'text-slate-400'"
                                        >
                                            {{ getBalanceValue(product, date) !== 0 ? formatNumber(getBalanceValue(product, date)) : '-' }}
                                        </td>
                                        <td class="p-2 border border-slate-200 dark:border-slate-800 text-right font-mono font-black sticky right-0 z-10 transition-colors shadow-[-2px_0_5px_-2px_rgba(0,0,0,0.1)]"
                                            :class="product.totals.bal < 0 ? 'bg-red-200 dark:bg-red-800/60 text-red-900 dark:text-red-100' : 'bg-slate-200 dark:bg-slate-900 text-slate-900 dark:text-slate-100 text-base shadow-inner'"
                                        >
                                            {{ formatNumber(product.totals.bal) }}
                                        </td>
                                    </tr>
                                </template>
                            </template>
                            <tr v-if="matrix.length === 0">
                                <td :colspan="dates.length + 4" class="p-20 text-center text-slate-500 italic">
                                    No data found for the selected period.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.glass-card {
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.dark .glass-card {
    background: rgba(15, 23, 42, 0.6);
    border-color: rgba(255, 255, 255, 0.05);
}

/* Custom scrollbar for matrix */
.matrix-container ::-webkit-scrollbar {
    width: 10px;
    height: 10px;
}
.matrix-container ::-webkit-scrollbar-track {
    background: transparent;
}
.matrix-container ::-webkit-scrollbar-thumb {
    background: rgba(148, 163, 184, 0.3);
    border-radius: 5px;
}
.matrix-container ::-webkit-scrollbar-thumb:hover {
    background: rgba(148, 163, 184, 0.5);
}

@media print {
    .no-print {
        display: none !important;
    }
    .p-4, .sm\:p-6, .lg\:p-8 {
        padding: 0 !important;
    }
    .glass-card {
        border: none !important;
        box-shadow: none !important;
        background: white !important;
    }
    .matrix-container {
        overflow: visible !important;
        max-height: none !important;
    }
    table {
        font-size: 8px !important;
    }
    .sticky {
        position: static !important;
    }
}
</style>
