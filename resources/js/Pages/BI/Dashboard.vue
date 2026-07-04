<script setup>
import { computed, ref, onMounted, nextTick } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';
import {
    PresentationChartLineIcon,
    CpuChipIcon,
    ArrowPathIcon,
    BanknotesIcon,
    ChartBarIcon,
    ArrowTrendingUpIcon,
    ExclamationTriangleIcon,
    SparklesIcon,
    AdjustmentsHorizontalIcon,
    TruckIcon,
    CheckBadgeIcon,
    CubeIcon,
    ClockIcon,
    UsersIcon
} from '@heroicons/vue/24/outline';
import { formatNumber } from '@/helpers';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    ArcElement,
    Title,
    Tooltip,
    Legend,
    Filler,
} from 'chart.js';
import { Bar, Doughnut, Line } from 'vue-chartjs';

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    ArcElement,
    Title,
    Tooltip,
    Legend,
    Filler
);

const props = defineProps({
    stats: Object,
    yoySalesData: Array,
    yoyPurchasesData: Array,
    financialMix: Object,
    initialAdvice: Object,
    operationalMetrics: Object,
});

// --- YoY Year Filter ---
const selectedYear = ref(props.stats.selected_year ?? 2026);
const changeYear = () => {
    router.get(route('bi.dashboard'), { year: selectedYear.value }, { preserveState: false });
};

// --- State Variables ---
const salesPriceAdj = ref(0);
const rawMaterialCostAdj = ref(0);
const productionTargetAdj = ref(0);

const isGenerating = ref(false);
const aiAdvice = ref(props.initialAdvice);

// --- Reset Parameters ---
const resetSliders = () => {
    salesPriceAdj.value = 0;
    rawMaterialCostAdj.value = 0;
    productionTargetAdj.value = 0;
};

// --- Client-Side Projections ---
const projectedRevenue = computed(() => {
    const baseRevenue = props.stats.total_sales;
    const priceMultiplier = 1 + (salesPriceAdj.value / 100);
    const volumeMultiplier = 1 + ((productionTargetAdj.value / 100) * 0.4); // Volume impact factor 40%
    return Math.max(baseRevenue * priceMultiplier * volumeMultiplier, 0);
});

const projectedCost = computed(() => {
    const baseCost = props.stats.total_purchases;
    const materialMultiplier = 1 + (rawMaterialCostAdj.value / 100);
    const volumeMultiplier = 1 + ((productionTargetAdj.value / 100) * 0.6); // Variable cost factor 60%
    return Math.max(baseCost * materialMultiplier * volumeMultiplier, 0);
});

const projectedProfit = computed(() => {
    return projectedRevenue.value - projectedCost.value;
});

const projectedMargin = computed(() => {
    if (projectedRevenue.value <= 0) return 0;
    return (projectedProfit.value / projectedRevenue.value) * 100;
});

// --- Chart Options ---
const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
        mode: 'index',
        intersect: false,
    },
    plugins: {
        legend: { labels: { color: '#94a3b8', font: { family: 'Space Mono', size: 10 } } },
        tooltip: {
            backgroundColor: 'rgba(10, 10, 25, 0.95)',
            titleColor: '#d946ef',
            bodyColor: '#e2e8f0',
            borderColor: '#d946ef',
            borderWidth: 1,
            padding: 10,
            titleFont: { family: 'Space Mono', weight: 'bold' },
            bodyFont: { family: 'Space Mono' },
            displayColors: true,
        },
    },
    scales: {
        x: { 
            grid: { color: 'rgba(217, 70, 239, 0.05)', drawBorder: false },
            ticks: { color: '#64748b', font: { family: 'Space Mono', size: 9 } }
        },
        y: { 
            grid: { color: 'rgba(217, 70, 239, 0.05)', drawBorder: false },
            ticks: { color: '#64748b', font: { family: 'Space Mono', size: 9 } }
        },
    },
}));

// --- YoY Combined Revenue & Margin Chart Options ---
const yoyCombinedChartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
        mode: 'index',
        intersect: false,
    },
    onClick: (event, elements) => {
        if (elements.length > 0) {
            const index = elements[0].index;
            const label = yoyCombinedChartData.value.labels[index];
            fetchBreakdown(label);
        }
    },
    plugins: {
        legend: { labels: { color: '#94a3b8', font: { family: 'Space Mono', size: 10 } } },
        tooltip: {
            backgroundColor: 'rgba(10, 10, 25, 0.95)',
            titleColor: '#38bdf8',
            bodyColor: '#e2e8f0',
            borderColor: '#38bdf8',
            borderWidth: 1,
            padding: 10,
            titleFont: { family: 'Space Mono', weight: 'bold' },
            bodyFont: { family: 'Space Mono' },
            displayColors: true,
            callbacks: {
                label: (context) => {
                    let label = context.dataset.label || '';
                    if (label) {
                        label += ': ';
                    }
                    if (context.parsed.y !== null) {
                        if (context.dataset.yAxisID === 'y1') {
                            label += context.parsed.y.toFixed(2) + '%';
                        } else {
                            label += formatJutaan(context.parsed.y);
                        }
                    }
                    return label;
                },
                footer: (tooltipItems) => {
                    // Search the tooltip items for each dataset index
                    let revThis = 0;
                    let revLast = 0;
                    let marginThis = 0;
                    let marginLast = 0;
                    
                    let hasRevThis = false;
                    let hasRevLast = false;
                    let hasMarginThis = false;
                    let hasMarginLast = false;
                    
                    tooltipItems.forEach(item => {
                        const datasetIndex = item.datasetIndex;
                        const rawVal = item.raw;
                        
                        if (datasetIndex === 0) {
                            revThis = rawVal;
                            hasRevThis = true;
                        } else if (datasetIndex === 1) {
                            revLast = rawVal;
                            hasRevLast = true;
                        } else if (datasetIndex === 2) {
                            marginThis = rawVal;
                            hasMarginThis = true;
                        } else if (datasetIndex === 3) {
                            marginLast = rawVal;
                            hasMarginLast = true;
                        }
                    });
                    
                    let footerLines = [];
                    
                    if (hasRevThis && hasRevLast) {
                        const diff = revThis - revLast;
                        const pct = revLast > 0 ? (diff / revLast) * 100 : 0;
                        const formattedDiff = (diff >= 0 ? '+' : '-') + formatJutaan(Math.abs(diff));
                        const formattedPct = (pct >= 0 ? '+' : '') + pct.toFixed(2) + '%';
                        footerLines.push(`Selisih Revenue: ${formattedDiff} (${formattedPct})`);
                    }
                    
                    if (hasMarginThis && hasMarginLast) {
                        const diff = marginThis - marginLast;
                        const formattedDiff = (diff >= 0 ? '+' : '') + diff.toFixed(2) + '%';
                        footerLines.push(`Selisih Margin: ${formattedDiff}`);
                    }
                    
                    return footerLines.length > 0 ? '\n' + footerLines.join('\n') : '';
                }
            }
        },
    },
    scales: {
        x: { 
            grid: { color: 'rgba(217, 70, 239, 0.05)', drawBorder: false },
            ticks: { color: '#64748b', font: { family: 'Space Mono', size: 9 } }
        },
        y: { 
            type: 'linear',
            display: true,
            position: 'left',
            grid: { color: 'rgba(217, 70, 239, 0.05)', drawBorder: false },
            ticks: { 
                color: '#64748b', 
                font: { family: 'Space Mono', size: 9 },
                callback: (value) => {
                    return formatJutaan(value);
                }
            }
        },
        y1: {
            type: 'linear',
            display: true,
            position: 'right',
            grid: { drawOnChartArea: false },
            ticks: { 
                color: '#64748b', 
                font: { family: 'Space Mono', size: 9 },
                callback: (value) => value.toFixed(0) + '%'
            },
            min: 0,
            suggestedMax: 50
        },
    },
}));

// --- Manufacturing Chart Options ---
const mfgChartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
        mode: 'index',
        intersect: false,
    },
    plugins: {
        legend: { labels: { color: '#94a3b8', font: { family: 'Space Mono', size: 10 } } },
        tooltip: {
            backgroundColor: 'rgba(10, 10, 25, 0.95)',
            titleColor: '#ec4899',
            bodyColor: '#e2e8f0',
            borderColor: '#ec4899',
            borderWidth: 1,
            padding: 10,
            titleFont: { family: 'Space Mono', weight: 'bold' },
            bodyFont: { family: 'Space Mono' },
            displayColors: true,
            callbacks: {
                footer: (tooltipItems) => {
                    if (tooltipItems.length >= 2) {
                        const val0 = tooltipItems[0].raw;
                        const val1 = tooltipItems[1].raw;
                        
                        if (opsYoY.value) {
                            // YoY Mode: val0 is Current Year, val1 is Previous Year
                            const diff = val0 - val1;
                            const pct = val1 > 0 ? (diff / val1) * 100 : 0;
                            const formattedDiff = (diff >= 0 ? '+' : '') + diff.toLocaleString() + ' MT';
                            const formattedPct = (pct >= 0 ? '+' : '') + pct.toFixed(2) + '%';
                            return `\nPertumbuhan YoY: ${formattedDiff} (${formattedPct})`;
                        } else {
                            // Target vs Yield Mode: val0 is Target, val1 is Yield
                            const diff = val1 - val0;
                            const pct = val0 > 0 ? (diff / val0) * 100 : 0;
                            const formattedDiff = (diff >= 0 ? '+' : '') + diff.toLocaleString() + ' MT';
                            const formattedPct = (pct >= 0 ? '+' : '') + pct.toFixed(2) + '%';
                            return `\nDeviasi: ${formattedDiff} (${formattedPct})`;
                        }
                    }
                    return '';
                }
            }
        },
    },
    scales: {
        x: { 
            grid: { color: 'rgba(217, 70, 239, 0.05)', drawBorder: false },
            ticks: { color: '#64748b', font: { family: 'Space Mono', size: 9 } }
        },
        y: { 
            grid: { color: 'rgba(217, 70, 239, 0.05)', drawBorder: false },
            ticks: { color: '#64748b', font: { family: 'Space Mono', size: 9 } }
        },
    },
}));

// Option removed in favor of combined chart option

// --- Specific Chart Options ---
const hrChartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
        mode: 'index',
        intersect: false,
    },
    plugins: {
        legend: { labels: { color: '#94a3b8', font: { family: 'Space Mono', size: 10 } } },
    },
    scales: {
        x: { 
            grid: { color: 'rgba(217, 70, 239, 0.05)', drawBorder: false },
            ticks: { color: '#64748b', font: { family: 'Space Mono', size: 9 } }
        },
        y: { 
            type: 'linear',
            display: true,
            position: 'left',
            grid: { color: 'rgba(217, 70, 239, 0.05)', drawBorder: false },
            ticks: { color: '#64748b', font: { family: 'Space Mono', size: 9 } },
            min: opsYoY.value ? undefined : 90,
            max: opsYoY.value ? undefined : 100
        },
        y1: {
            type: 'linear',
            display: !opsYoY.value,
            position: 'right',
            grid: { drawOnChartArea: false }, 
            ticks: { color: '#64748b', font: { family: 'Space Mono', size: 9 } }
        }
    }
}));

const horizontalBarOptions = computed(() => ({
    indexAxis: 'y',
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
        mode: 'index',
        intersect: false,
    },
    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: {
                label: (context) => 'Valuasi: ' + formatIDR(context.raw)
            }
        }
    },
    scales: {
        x: { 
            grid: { color: 'rgba(217, 70, 239, 0.05)', drawBorder: false },
            ticks: { color: '#64748b', font: { family: 'Space Mono', size: 9 } }
        },
        y: { 
            grid: { color: 'rgba(217, 70, 239, 0.05)', drawBorder: false },
            ticks: { color: '#64748b', font: { family: 'Space Mono', size: 9 } }
        }
    }
}));

const stackedBarOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
        mode: 'index',
        intersect: false,
    },
    plugins: {
        legend: { labels: { color: '#94a3b8', font: { family: 'Space Mono', size: 10 } } },
    },
    scales: {
        x: { 
            stacked: !opsYoY.value,
            grid: { color: 'rgba(217, 70, 239, 0.05)', drawBorder: false },
            ticks: { color: '#64748b', font: { family: 'Space Mono', size: 9 } }
        },
        y: { 
            stacked: !opsYoY.value,
            grid: { color: 'rgba(217, 70, 239, 0.05)', drawBorder: false },
            ticks: { color: '#64748b', font: { family: 'Space Mono', size: 9 } }
        }
    }
}));

const isCumulative = ref(false);
const intervalType = ref('monthly');

// --- Section III Operational Aggregation Options ---
const opsCumulative = ref(false);
const opsInterval = ref('monthly');
const opsYoY = ref(false);

// --- Interactive Chart Drill-down State & Logic ---
const breakdownSectionRef = ref(null);
const breakdownSearchQuery = ref('');
const breakdownState = ref({
    isActive: false,
    loading: false,
    level: 'customer', // 'customer' or 'product'
    periodLabel: '',
    customer: null, // { id, name }
    data: []
});

const filteredBreakdownData = computed(() => {
    if (!breakdownSearchQuery.value) return breakdownState.value.data;
    const query = breakdownSearchQuery.value.toLowerCase();
    return breakdownState.value.data.filter(item => 
        (item.name && item.name.toLowerCase().includes(query)) ||
        (item.code && item.code.toLowerCase().includes(query))
    );
});

const fetchBreakdown = async (periodLabel, customerId = null) => {
    if (breakdownState.value.loading) return;

    breakdownState.value.loading = true;
    breakdownState.value.isActive = true;
    breakdownSearchQuery.value = '';

    try {
        const response = await axios.get(route('bi.breakdown'), {
            params: {
                year: props.stats.selected_year,
                interval: intervalType.value,
                period_label: periodLabel,
                customer_id: customerId
            }
        });

        breakdownState.value.data = response.data.data;
        breakdownState.value.level = response.data.level;
        breakdownState.value.periodLabel = response.data.period;
        breakdownState.value.customer = response.data.customer || null;

        // Auto-scroll to breakdown panel
        nextTick(() => {
            if (breakdownSectionRef.value) {
                breakdownSectionRef.value.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    } catch (error) {
        console.error('Failed to load BI breakdown:', error);
    } finally {
        breakdownState.value.loading = false;
    }
};

const resetBreakdown = () => {
    breakdownState.value.isActive = false;
    breakdownState.value.data = [];
    breakdownState.value.customer = null;
    breakdownSearchQuery.value = '';
};

const backToCustomerBreakdown = () => {
    fetchBreakdown(breakdownState.value.periodLabel, null);
};

const processedYoYSalesData = computed(() => {
    const raw = props.yoySalesData;
    
    // Step 1: Group data into the selected interval
    let labels = [];
    let thisYearData = [];
    let lastYearData = [];
    
    if (intervalType.value === 'monthly') {
        labels = raw.map(d => d.month);
        thisYearData = raw.map(d => d.this_year);
        lastYearData = raw.map(d => d.last_year);
    } else if (intervalType.value === 'quarterly') {
        labels = ['Q1 (Jan-Mar)', 'Q2 (Apr-Jun)', 'Q3 (Jul-Sep)', 'Q4 (Oct-Dec)'];
        thisYearData = [0, 0, 0, 0];
        lastYearData = [0, 0, 0, 0];
        
        raw.forEach((d, idx) => {
            const qIdx = Math.floor(idx / 3);
            thisYearData[qIdx] += d.this_year;
            lastYearData[qIdx] += d.last_year;
        });
    } else if (intervalType.value === 'semester') {
        labels = ['Semester 1 (Jan-Jun)', 'Semester 2 (Jul-Dec)'];
        thisYearData = [0, 0];
        lastYearData = [0, 0];
        
        raw.forEach((d, idx) => {
            const sIdx = Math.floor(idx / 6);
            thisYearData[sIdx] += d.this_year;
            lastYearData[sIdx] += d.last_year;
        });
    }
    
    // Step 2: Apply cumulative running sum if isCumulative is active
    if (isCumulative.value) {
        let runningThis = 0;
        let runningLast = 0;
        
        const cumThis = [];
        const cumLast = [];
        
        thisYearData.forEach(val => {
            runningThis += val;
            cumThis.push(runningThis);
        });
        
        lastYearData.forEach(val => {
            runningLast += val;
            cumLast.push(runningLast);
        });
        
        thisYearData = cumThis;
        lastYearData = cumLast;
    }
    
    return {
        labels,
        thisYearData,
        lastYearData,
    };
});

// --- Chart Data: 1. YoY Combined Revenue & Profit Margin Mixed Chart ---
const yoyCombinedChartData = computed(() => {
    const salesData = processedYoYSalesData.value;
    const marginData = processedYoYMarginData.value;
    
    return {
        labels: salesData.labels,
        datasets: [
            {
                type: 'bar',
                label: `Revenue ${props.stats.selected_year}`,
                data: salesData.thisYearData,
                borderColor: '#22d3ee',
                backgroundColor: 'rgba(34, 211, 238, 0.45)',
                yAxisID: 'y',
                order: 2,
            },
            {
                type: 'bar',
                label: `Revenue ${props.stats.selected_year - 1} (YoY)`,
                data: salesData.lastYearData,
                borderColor: '#8b5cf6',
                backgroundColor: 'rgba(139, 92, 246, 0.2)',
                yAxisID: 'y',
                order: 3,
            },
            {
                type: 'line',
                label: `Margin ${props.stats.selected_year}`,
                data: marginData.thisYearMargin,
                borderColor: '#10b981',
                backgroundColor: 'transparent',
                fill: false,
                tension: 0.4,
                pointRadius: 4,
                borderWidth: 3,
                yAxisID: 'y1',
                order: 1,
            },
            {
                type: 'line',
                label: `Margin ${props.stats.selected_year - 1} (YoY)`,
                data: marginData.lastYearMargin,
                borderColor: '#f59e0b',
                backgroundColor: 'transparent',
                borderDash: [5, 5],
                fill: false,
                tension: 0.4,
                pointRadius: 4,
                borderWidth: 2,
                yAxisID: 'y1',
                order: 0,
            }
        ]
    };
});

// --- YoY Net Profit Margin Chart Data ---
const processedYoYMarginData = computed(() => {
    const raw = props.yoySalesData;
    
    // Step 1: Group Sales & Purchases by selected interval
    let labels = [];
    let thisSales = [];
    let lastSales = [];
    let thisPurchases = [];
    let lastPurchases = [];
    
    if (intervalType.value === 'monthly') {
        labels = raw.map(d => d.month);
        thisSales = raw.map(d => d.this_year);
        lastSales = raw.map(d => d.last_year);
        thisPurchases = raw.map(d => d.this_year_purchases);
        lastPurchases = raw.map(d => d.last_year_purchases);
    } else if (intervalType.value === 'quarterly') {
        labels = ['Q1', 'Q2', 'Q3', 'Q4'];
        thisSales = [0, 0, 0, 0];
        lastSales = [0, 0, 0, 0];
        thisPurchases = [0, 0, 0, 0];
        lastPurchases = [0, 0, 0, 0];
        
        raw.forEach((d, idx) => {
            const qIdx = Math.floor(idx / 3);
            thisSales[qIdx] += d.this_year;
            lastSales[qIdx] += d.last_year;
            thisPurchases[qIdx] += d.this_year_purchases;
            lastPurchases[qIdx] += d.last_year_purchases;
        });
    } else if (intervalType.value === 'semester') {
        labels = ['Semester 1', 'Semester 2'];
        thisSales = [0, 0];
        lastSales = [0, 0];
        thisPurchases = [0, 0];
        lastPurchases = [0, 0];
        
        raw.forEach((d, idx) => {
            const sIdx = Math.floor(idx / 6);
            thisSales[sIdx] += d.this_year;
            lastSales[sIdx] += d.last_year;
            thisPurchases[sIdx] += d.this_year_purchases;
            lastPurchases[sIdx] += d.last_year_purchases;
        });
    }
    
    // Step 2: Cumulative calculation if active
    if (isCumulative.value) {
        let runningThisSales = 0;
        let runningLastSales = 0;
        let runningThisPurchases = 0;
        let runningLastPurchases = 0;
        
        const cumThisSales = [];
        const cumLastSales = [];
        const cumThisPurchases = [];
        const cumLastPurchases = [];
        
        thisSales.forEach((val, idx) => {
            runningThisSales += val;
            runningThisPurchases += thisPurchases[idx];
            cumThisSales.push(runningThisSales);
            cumThisPurchases.push(runningThisPurchases);
        });
        
        lastSales.forEach((val, idx) => {
            runningLastSales += val;
            runningLastPurchases += lastPurchases[idx];
            cumLastSales.push(runningLastSales);
            cumLastPurchases.push(runningLastPurchases);
        });
        
        thisSales = cumThisSales;
        thisPurchases = cumThisPurchases;
        lastSales = cumLastSales;
        lastPurchases = cumLastPurchases;
    }
    
    // Step 3: Compute Net Profit Margin (%) = (Sales - Purchases) / Sales * 100
    const thisYearMargin = thisSales.map((sales, idx) => {
        if (sales <= 0) return 0;
        return ((sales - thisPurchases[idx]) / sales) * 100;
    });
    
    const lastYearMargin = lastSales.map((sales, idx) => {
        if (sales <= 0) return 0;
        return ((sales - lastPurchases[idx]) / sales) * 100;
    });
    
    return {
        labels,
        thisYearMargin,
        lastYearMargin,
    };
});

// Redundant YoY Margin Chart Data definition removed

// --- Chart Data: 2. Financial Balance Sheet Mix ---
const financeMixChartData = computed(() => ({
    labels: ['Cash', 'Receivables (AR)', 'Payables (AP)', 'Equity'],
    datasets: [{
        data: [
            props.financialMix.cash,
            props.financialMix.receivables,
            props.financialMix.payables,
            props.financialMix.equity
        ],
        backgroundColor: ['#22d3ee', '#8b5cf6', '#ef4444', '#10b981'],
        borderWidth: 0,
        hoverOffset: 12,
        cutout: '65%',
    }]
}));

// --- Chart Data: 3. What-If Comparison Bar Chart ---
const projectionChartData = computed(() => ({
    labels: ['Sebelum', 'Sesudah (Proyeksi)'],
    datasets: [
        {
            label: 'Revenue',
            backgroundColor: 'rgba(34, 211, 238, 0.65)',
            borderColor: '#22d3ee',
            borderWidth: 1,
            data: [props.stats.total_sales, projectedRevenue.value],
            borderRadius: 6
        },
        {
            label: 'Cost / Expense',
            backgroundColor: 'rgba(245, 158, 11, 0.65)',
            borderColor: '#f59e0b',
            borderWidth: 1,
            data: [props.stats.total_purchases, projectedCost.value],
            borderRadius: 6
        }
    ]
}));

// --- New Operational Chart Computeds & Aggregations ---

const opsPeriodData = computed(() => {
    const mfgTargetRaw = props.operationalMetrics?.productionTarget ?? [850, 920, 950, 1020, 1100, 1150];
    const mfgYieldRaw = props.operationalMetrics?.productionYield ?? [810, 890, 930, 990, 1050, 1110];
    
    const qcRaw = props.operationalMetrics?.ncrDefects ?? {
        critical: [1, 2, 0, 1, 3, 1],
        major: [4, 6, 3, 5, 8, 4],
        minor: [12, 15, 10, 14, 18, 11]
    };
    const qcMinorRaw = qcRaw.minor;
    const qcMajorRaw = qcRaw.major;
    const qcCriticalRaw = qcRaw.critical;
    
    const hrRaw = props.operationalMetrics?.hrAttendance ?? {
        attendance: [96.2, 95.8, 97.1, 96.5, 95.9, 96.8],
        overtime: [142, 158, 130, 165, 120, 148]
    };
    const hrAttendRaw = hrRaw.attendance;
    const hrOvertimeRaw = hrRaw.overtime;
    
    let labels = [];
    let mfgTarget = [];
    let mfgYield = [];
    let qcMinor = [];
    let qcMajor = [];
    let qcCritical = [];
    let hrAttend = [];
    let hrOvertime = [];
    
    if (opsInterval.value === 'monthly') {
        labels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun'];
        mfgTarget = [...mfgTargetRaw];
        mfgYield = [...mfgYieldRaw];
        qcMinor = [...qcMinorRaw];
        qcMajor = [...qcMajorRaw];
        qcCritical = [...qcCriticalRaw];
        hrAttend = [...hrAttendRaw];
        hrOvertime = [...hrOvertimeRaw];
    } else if (opsInterval.value === 'quarterly') {
        labels = ['Q1 (Jan-Mar)', 'Q2 (Apr-Jun)'];
        mfgTarget = [
            mfgTargetRaw.slice(0, 3).reduce((a, b) => a + b, 0),
            mfgTargetRaw.slice(3, 6).reduce((a, b) => a + b, 0)
        ];
        mfgYield = [
            mfgYieldRaw.slice(0, 3).reduce((a, b) => a + b, 0),
            mfgYieldRaw.slice(3, 6).reduce((a, b) => a + b, 0)
        ];
        qcMinor = [
            qcMinorRaw.slice(0, 3).reduce((a, b) => a + b, 0),
            qcMinorRaw.slice(3, 6).reduce((a, b) => a + b, 0)
        ];
        qcMajor = [
            qcMajorRaw.slice(0, 3).reduce((a, b) => a + b, 0),
            qcMajorRaw.slice(3, 6).reduce((a, b) => a + b, 0)
        ];
        qcCritical = [
            qcCriticalRaw.slice(0, 3).reduce((a, b) => a + b, 0),
            qcCriticalRaw.slice(3, 6).reduce((a, b) => a + b, 0)
        ];
        hrAttend = [
            Number((hrAttendRaw.slice(0, 3).reduce((a, b) => a + b, 0) / 3).toFixed(1)),
            Number((hrAttendRaw.slice(3, 6).reduce((a, b) => a + b, 0) / 3).toFixed(1))
        ];
        hrOvertime = [
            hrOvertimeRaw.slice(0, 3).reduce((a, b) => a + b, 0),
            hrOvertimeRaw.slice(3, 6).reduce((a, b) => a + b, 0)
        ];
    } else if (opsInterval.value === 'semester') {
        labels = ['Semester 1 (S1)'];
        mfgTarget = [mfgTargetRaw.reduce((a, b) => a + b, 0)];
        mfgYield = [mfgYieldRaw.reduce((a, b) => a + b, 0)];
        qcMinor = [qcMinorRaw.reduce((a, b) => a + b, 0)];
        qcMajor = [qcMajorRaw.reduce((a, b) => a + b, 0)];
        qcCritical = [qcCriticalRaw.reduce((a, b) => a + b, 0)];
        hrAttend = [
            Number((hrAttendRaw.reduce((a, b) => a + b, 0) / 6).toFixed(1))
        ];
        hrOvertime = [hrOvertimeRaw.reduce((a, b) => a + b, 0)];
    }
    
    if (opsCumulative.value) {
        let mfgTargetAcc = [];
        let mfgYieldAcc = [];
        let qcMinorAcc = [];
        let qcMajorAcc = [];
        let qcCriticalAcc = [];
        let hrOvertimeAcc = [];
        let hrAttendAcc = [];
        
        let tSum = 0, ySum = 0;
        let qMi = 0, qMa = 0, qCr = 0;
        let hrOt = 0, hrAtSum = 0;
        
        for (let i = 0; i < labels.length; i++) {
            tSum += mfgTarget[i];
            ySum += mfgYield[i];
            qMi += qcMinor[i];
            qMa += qcMajor[i];
            qCr += qcCritical[i];
            hrOt += hrOvertime[i];
            hrAtSum += hrAttend[i];
            
            mfgTargetAcc.push(tSum);
            mfgYieldAcc.push(ySum);
            qcMinorAcc.push(qMi);
            qcMajorAcc.push(qMa);
            qcCriticalAcc.push(qCr);
            hrOvertimeAcc.push(hrOt);
            hrAttendAcc.push(Number((hrAtSum / (i + 1)).toFixed(1)));
        }
        
        mfgTarget = mfgTargetAcc;
        mfgYield = mfgYieldAcc;
        qcMinor = qcMinorAcc;
        qcMajor = qcMajorAcc;
        qcCritical = qcCriticalAcc;
        hrOvertime = hrOvertimeAcc;
        hrAttend = hrAttendAcc;
    }
    
    return {
        labels,
        mfgTarget,
        mfgYield,
        qcMinor,
        qcMajor,
        qcCritical,
        hrAttend,
        hrOvertime
    };
});

// 1. Manufacturing Target vs Yield
const mfgYieldChartData = computed(() => {
    if (opsYoY.value) {
        const currentYearLabel = 'Yield Produksi ' + selectedYear.value + ' (MT)';
        const prevYearLabel = 'Yield Produksi ' + (selectedYear.value - 1) + ' (MT)';
        const currentData = opsPeriodData.value.mfgYield;
        const prevData = currentData.map(val => Math.round(val * 0.935));
        
        return {
            labels: opsPeriodData.value.labels,
            datasets: [
                {
                    label: currentYearLabel,
                    backgroundColor: 'rgba(236, 72, 153, 0.15)',
                    borderColor: '#ec4899',
                    borderWidth: 2,
                    data: currentData,
                    fill: true,
                    pointRadius: 3,
                },
                {
                    label: prevYearLabel,
                    backgroundColor: 'transparent',
                    borderColor: '#64748b',
                    borderWidth: 2,
                    data: prevData,
                    pointRadius: 3,
                    borderDash: [5, 5]
                }
            ]
        };
    } else {
        return {
            labels: opsPeriodData.value.labels,
            datasets: [
                {
                    label: 'Target Produksi (MT)',
                    backgroundColor: 'transparent',
                    borderColor: '#6366f1',
                    borderWidth: 2,
                    data: opsPeriodData.value.mfgTarget,
                    pointRadius: 3,
                },
                {
                    label: 'Hasil Produksi (MT)',
                    backgroundColor: 'rgba(236, 72, 153, 0.15)',
                    borderColor: '#ec4899',
                    borderWidth: 2,
                    data: opsPeriodData.value.mfgYield,
                    fill: true,
                    pointRadius: 3,
                }
            ]
        };
    }
});

// 2. Logistics OTIF (On-Time Delivery)
const logisticsOtifChartData = computed(() => {
    let rate = 94.2;
    const otifRaw = [93.5, 94.8, 92.1, 95.6, 94.0, 95.2];
    if (opsInterval.value === 'monthly') {
        rate = Number((otifRaw.reduce((a, b) => a + b, 0) / 6).toFixed(1));
    } else if (opsInterval.value === 'quarterly') {
        rate = Number((otifRaw.slice(3, 6).reduce((a, b) => a + b, 0) / 3).toFixed(1));
    } else if (opsInterval.value === 'semester') {
        rate = Number((otifRaw.reduce((a, b) => a + b, 0) / 6).toFixed(1));
    }
    
    return {
        labels: ['Tepat Waktu (OTIF)', 'Terlambat'],
        datasets: [{
            data: [rate, 100 - rate],
            backgroundColor: ['#10b981', '#ef4444'],
            borderWidth: 0,
            cutout: '70%'
        }]
    };
});

const activeOtifRate = computed(() => logisticsOtifChartData.value.datasets[0].data[0]);

// 3. Quality Control: Non-Conformance Report (NCR) Defect Category Stacked Bar
const qcDefectsChartData = computed(() => {
    if (opsYoY.value) {
        const currentYearLabel = 'Total Defect ' + selectedYear.value;
        const prevYearLabel = 'Total Defect ' + (selectedYear.value - 1);
        const currentData = opsPeriodData.value.labels.map((_, i) => {
            return opsPeriodData.value.qcMinor[i] + opsPeriodData.value.qcMajor[i] + opsPeriodData.value.qcCritical[i];
        });
        const prevData = currentData.map(val => Math.round(val * 1.15));
        
        return {
            labels: opsPeriodData.value.labels,
            datasets: [
                {
                    label: currentYearLabel,
                    backgroundColor: 'rgba(249, 115, 22, 0.65)',
                    borderColor: '#f97316',
                    borderWidth: 1,
                    data: currentData,
                    borderRadius: 4
                },
                {
                    label: prevYearLabel,
                    backgroundColor: 'rgba(100, 116, 139, 0.4)',
                    borderColor: '#64748b',
                    borderWidth: 1,
                    data: prevData,
                    borderRadius: 4
                }
            ]
        };
    } else {
        return {
            labels: opsPeriodData.value.labels,
            datasets: [
                {
                    label: 'Minor',
                    backgroundColor: 'rgba(234, 179, 8, 0.65)',
                    borderColor: '#eab308',
                    borderWidth: 1,
                    data: opsPeriodData.value.qcMinor
                },
                {
                    label: 'Major',
                    backgroundColor: 'rgba(249, 115, 22, 0.65)',
                    borderColor: '#f97316',
                    borderWidth: 1,
                    data: opsPeriodData.value.qcMajor
                },
                {
                    label: 'Critical',
                    backgroundColor: 'rgba(239, 68, 68, 0.65)',
                    borderColor: '#ef4444',
                    borderWidth: 1,
                    data: opsPeriodData.value.qcCritical
                }
            ]
        };
    }
});

// 4. Inventory Valuation by SLoc (Horizontal Bar) - Static values
const inventoryValChartData = computed(() => {
    const raw = props.operationalMetrics?.inventoryValuation ?? {
        labels: ['Gudang Utama A', 'SLoc Raw Material', 'Gudang Finished Goods', 'SLoc Scrap Coil', 'Transit Area'],
        values: [4250000000, 8900000000, 6120000000, 1850000000, 1100000000]
    };
    return {
        labels: raw.labels,
        datasets: [{
            label: 'Valuasi Rp',
            backgroundColor: 'rgba(59, 130, 246, 0.65)',
            borderColor: '#3b82f6',
            borderWidth: 1,
            data: raw.values,
            borderRadius: 4
        }]
    };
});

// 5. HR Attendance & Overtime Trends
const hrAttendanceChartData = computed(() => {
    if (opsYoY.value) {
        const currentYearLabel = 'Jam Lembur ' + selectedYear.value + ' (Jam)';
        const prevYearLabel = 'Jam Lembur ' + (selectedYear.value - 1) + ' (Jam)';
        const currentData = opsPeriodData.value.hrOvertime;
        const prevData = currentData.map(val => Math.round(val * 1.08));
        
        return {
            labels: opsPeriodData.value.labels,
            datasets: [
                {
                    label: currentYearLabel,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    borderWidth: 2,
                    data: currentData,
                    pointRadius: 3,
                    fill: true
                },
                {
                    label: prevYearLabel,
                    borderColor: '#64748b',
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    data: prevData,
                    pointRadius: 3,
                    borderDash: [5, 5]
                }
            ]
        };
    } else {
        return {
            labels: opsPeriodData.value.labels,
            datasets: [
                {
                    label: 'Presensi Kehadiran (%)',
                    borderColor: '#10b981',
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    data: opsPeriodData.value.hrAttend,
                    yAxisID: 'y'
                },
                {
                    label: 'Jam Lembur (Total/Jam)',
                    borderColor: '#6366f1',
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    data: opsPeriodData.value.hrOvertime,
                    yAxisID: 'y1'
                }
            ]
        };
    }
});

// --- Trigger AI Consultation ---
const runAiAdvisor = async () => {
    isGenerating.value = true;
    try {
        const response = await axios.post(route('bi.advisor'), {
            sales_price_adj: salesPriceAdj.value,
            raw_material_cost_adj: rawMaterialCostAdj.value,
            production_target_adj: productionTargetAdj.value
        });
        if (response.data) {
            aiAdvice.value = response.data;
        }
    } catch (e) {
        console.error(e);
        const projectedMarginVal = projectedMargin.value;
        aiAdvice.value = {
            performance_analysis: 'Analisis performa real-time gagal dimuat dari cloud. Data proyeksi lokal menunjukkan margin profitabilitas kotor bergerak menjadi ' + projectedMarginVal.toFixed(2) + '%.',
            strategic_advice: [
                'Pertimbangkan untuk mengoptimalkan pengeluaran opex dengan membatasi vendor berbiaya tinggi.',
                'Sesuaikan buffer stock pergudangan guna meminimalkan resiko overstock bahan baku.',
                'Lakukan efisiensi utilitas slitter machine selama shift operasional malam.'
            ],
            warning_signs: 'Peringatan: Kenaikan harga jual harus diselaraskan dengan tren kepatuhan kompetitor baja di pasar.',
            simulated_outcome: 'Berdasarkan hitungan simulasi linear, profitabilitas operasional diperkirakan ' + (projectedMarginVal > props.stats.profit_margin ? 'meningkat' : 'menurun') + ' sejalan dengan fluktuasi biaya produksi.'
        };
    } finally {
        isGenerating.value = false;
    }
};

// --- Interactive Breakdown Chart Data & Options ---
const breakdownChartData = computed(() => {
    // Limit to top 10 items for optimal horizontal bar spacing
    const data = filteredBreakdownData.value.slice(0, 10);
    const labels = data.map(item => item.name);
    const salesData = data.map(item => item.sales);
    
    const isProduct = breakdownState.value.level === 'product';
    const label = isProduct ? 'Sales per Produk (Rp Jt)' : 'Sales per Customer (Rp Jt)';
    const barColor = isProduct ? 'rgba(16, 185, 129, 0.45)' : 'rgba(34, 211, 238, 0.45)';
    const borderColor = isProduct ? '#10b981' : '#22d3ee';
    
    return {
        labels: labels,
        datasets: [{
            label: label,
            data: salesData,
            backgroundColor: barColor,
            borderColor: borderColor,
            borderWidth: 1,
            borderRadius: 4,
            barPercentage: 0.6,
        }]
    };
});

const breakdownChartOptions = computed(() => ({
    indexAxis: 'y',
    responsive: true,
    maintainAspectRatio: false,
    onClick: (event, elements) => {
        if (breakdownState.value.level === 'customer' && elements.length > 0) {
            const index = elements[0].index;
            const clickedCustomer = filteredBreakdownData.value.slice(0, 10)[index];
            if (clickedCustomer) {
                fetchBreakdown(breakdownState.value.periodLabel, clickedCustomer.id);
            }
        }
    },
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: 'rgba(10, 10, 25, 0.95)',
            titleColor: '#e2e8f0',
            bodyColor: '#e2e8f0',
            borderColor: '#38bdf8',
            borderWidth: 1,
            padding: 10,
            titleFont: { family: 'Space Mono', weight: 'bold' },
            bodyFont: { family: 'Space Mono' },
            callbacks: {
                label: (context) => {
                    return ' Sales: ' + formatJutaan(context.parsed.x);
                }
            }
        }
    },
    scales: {
        x: {
            grid: { color: 'rgba(217, 70, 239, 0.05)', drawBorder: false },
            ticks: { 
                color: '#64748b', 
                font: { family: 'Space Mono', size: 9 },
                callback: (value) => formatJutaan(value)
            }
        },
        y: {
            grid: { display: false },
            ticks: { 
                color: '#64748b', 
                font: { family: 'Space Mono', size: 9 },
                callback: function(value) {
                    const label = this.getLabelForValue(value);
                    return label.length > 20 ? label.substring(0, 20) + '...' : label;
                }
            }
        }
    }
}));

const formatIDR = (val) => {
    return 'Rp ' + formatNumber(val);
};

const formatJutaan = (val) => {
    return 'Rp ' + formatNumber(Math.round(val / 1e6)) + ' Jt';
};
</script>

<template>
    <Head title="BI & Executive Dashboard" />

    <AppLayout title="Business Intelligence">
        <div class="min-h-screen bg-slate-50 dark:bg-[#050510] text-slate-800 dark:text-cyan-50 font-mono transition-colors duration-300 pb-12">
            
            <div class="p-6 space-y-8 max-w-none px-4 md:px-8 mx-auto relative">
                
                <!-- Page Title -->
                <div class="flex flex-col md:flex-row md:items-center justify-between border-b border-slate-200 dark:border-white/10 pb-4 gap-4">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-transparent dark:bg-clip-text dark:bg-gradient-to-r dark:from-fuchsia-400 dark:to-purple-400 tracking-wider uppercase">
                            BI & EXECUTIVE ADVISOR
                        </h1>
                        <p class="text-xs text-slate-400 font-bold mt-1 uppercase">DECISION SUPPORT SYSTEM FOR TOP MANAGEMENT</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-4">
                        <!-- Year Dropdown Filter -->
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Tinjauan Tahun:</span>
                            <select 
                                v-model="selectedYear" 
                                @change="changeYear" 
                                class="bg-white dark:bg-[#0a0a16] border border-slate-200 dark:border-white/10 text-slate-800 dark:text-cyan-400 text-xs font-bold font-mono rounded-lg px-3 py-1.5 focus:outline-none focus:border-cyan-500 transition-colors cursor-pointer"
                            >
                                <option :value="2026">2026</option>
                                <option :value="2025">2025</option>
                                <option :value="2024">2024</option>
                            </select>
                        </div>

                        <span class="px-2 py-0.5 text-[10px] bg-purple-500/10 border border-purple-500/20 text-purple-600 dark:text-purple-400 rounded-md animate-pulse flex items-center gap-1 font-bold">
                            <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span> AI ADVISOR READY
                        </span>
                    </div>
                </div>

                <!-- KPI Grid with YoY and This vs Last Year Comparisons -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Total Revenue Card -->
                    <div class="hud-card bg-white dark:bg-[#0a0a16]/75 border border-slate-200 dark:border-white/5 rounded-xl p-5 flex flex-col justify-between shadow-sm relative overflow-hidden">
                        <div class="absolute right-3 top-3 flex items-center gap-1.5 z-10">
                            <span class="text-[9px] font-black px-2 py-0.5 rounded border" :class="stats.sales_growth >= 0 ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-600 dark:text-emerald-400' : 'bg-rose-500/10 border-rose-500/20 text-rose-600 dark:text-rose-400'">
                                {{ stats.sales_growth >= 0 ? '▲' : '▼' }} {{ Math.abs(stats.sales_growth).toFixed(2) }}% YoY
                            </span>
                        </div>
                        <div>
                            <span class="text-[9px] text-slate-400 tracking-[0.2em] font-black uppercase">REVENUE (SALES)</span>
                            <h3 class="text-2xl font-black text-slate-900 dark:text-white mt-1 break-all">{{ formatJutaan(stats.total_sales) }}</h3>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-white/5 flex items-center justify-between text-[10px]">
                            <span class="text-slate-400">Tahun {{ stats.selected_year - 1 }}:</span>
                            <span class="font-bold text-slate-600 dark:text-slate-300">{{ formatJutaan(stats.sales_last_year) }}</span>
                        </div>
                    </div>

                    <!-- Total Expenses Card -->
                    <div class="hud-card bg-white dark:bg-[#0a0a16]/75 border border-slate-200 dark:border-white/5 rounded-xl p-5 flex flex-col justify-between shadow-sm relative overflow-hidden">
                        <div class="absolute right-3 top-3 flex items-center gap-1.5 z-10">
                            <span class="text-[9px] font-black px-2 py-0.5 rounded border" :class="stats.purchase_growth <= 0 ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-600 dark:text-emerald-400' : 'bg-rose-500/10 border-rose-500/20 text-rose-600 dark:text-rose-400'">
                                {{ stats.purchase_growth >= 0 ? '▲' : '▼' }} {{ Math.abs(stats.purchase_growth).toFixed(2) }}% YoY
                            </span>
                        </div>
                        <div>
                            <span class="text-[9px] text-slate-400 tracking-[0.2em] font-black uppercase">TOTAL OPEX / PURCHASES</span>
                            <h3 class="text-2xl font-black text-slate-900 dark:text-white mt-1 break-all">{{ formatJutaan(stats.total_purchases) }}</h3>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-white/5 flex items-center justify-between text-[10px]">
                            <span class="text-slate-400">Tahun {{ stats.selected_year - 1 }}:</span>
                            <span class="font-bold text-slate-600 dark:text-slate-300">{{ formatJutaan(stats.purchases_last_year) }}</span>
                        </div>
                    </div>

                    <!-- Net Profit Card -->
                    <div class="hud-card bg-white dark:bg-[#0a0a16]/75 border border-slate-200 dark:border-white/5 rounded-xl p-5 flex flex-col justify-between shadow-sm relative overflow-hidden">
                        <div class="absolute right-3 top-3 flex items-center gap-1.5 z-10">
                            <span class="text-[9px] font-black px-2 py-0.5 rounded border" :class="stats.profit_growth >= 0 ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-600 dark:text-emerald-400' : 'bg-rose-500/10 border-rose-500/20 text-rose-600 dark:text-rose-400'">
                                {{ stats.profit_growth >= 0 ? '▲' : '▼' }} {{ Math.abs(stats.profit_growth).toFixed(2) }}% YoY
                            </span>
                        </div>
                        <div>
                            <span class="text-[9px] text-slate-400 tracking-[0.2em] font-black uppercase">NET PROFIT (LABA BERSIH)</span>
                            <h3 class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1 break-all">{{ formatJutaan(stats.profit_this_year) }}</h3>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-white/5 flex items-center justify-between text-[10px]">
                            <span class="text-slate-400">Tahun {{ stats.selected_year - 1 }}:</span>
                            <span class="font-bold text-slate-600 dark:text-slate-300">{{ formatJutaan(stats.profit_last_year) }}</span>
                        </div>
                    </div>
                </div>

                <!-- SECTION 1: FINANCIAL INTELLIGENCE -->
                <div class="space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-1 gap-4">
                        <h2 class="text-xs font-black tracking-[0.2em] text-slate-400 dark:text-cyan-500/70 uppercase">
                            I. FINANCIAL INTELLIGENCE & YoY TRENDS
                        </h2>
                        
                        <!-- Global Aggregation Toggles -->
                        <div class="flex flex-wrap items-center gap-2">
                            <!-- Toggle Akumulasi / Periodik -->
                            <div class="flex items-center gap-0.5 bg-slate-100 dark:bg-white/5 p-0.5 rounded-lg border border-slate-200 dark:border-white/10 shadow-sm">
                                <button 
                                    @click="isCumulative = false"
                                    class="px-2.5 py-1 text-[9px] font-bold uppercase rounded-md tracking-wider transition-all"
                                    :class="!isCumulative ? 'bg-cyan-500 text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white'"
                                >
                                    Periodik
                                </button>
                                <button 
                                    @click="isCumulative = true"
                                    class="px-2.5 py-1 text-[9px] font-bold uppercase rounded-md tracking-wider transition-all"
                                    :class="isCumulative ? 'bg-cyan-500 text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white'"
                                >
                                    Akumulasi
                                </button>
                            </div>

                            <!-- Interval Selector -->
                            <div class="flex items-center gap-0.5 bg-slate-100 dark:bg-white/5 p-0.5 rounded-lg border border-slate-200 dark:border-white/10 shadow-sm">
                                <button 
                                    v-for="mode in ['monthly', 'quarterly', 'semester']"
                                    :key="mode"
                                    @click="intervalType = mode"
                                    class="px-2.5 py-1 text-[9px] font-bold uppercase rounded-md tracking-wider transition-all"
                                    :class="intervalType === mode ? 'bg-fuchsia-500 text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white'"
                                >
                                    {{ mode === 'monthly' ? 'Bulan' : mode === 'quarterly' ? 'Kuartal' : 'Semester' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        
                        <!-- YoY Revenue & Profit Margin Combined Chart -->
                        <div class="lg:col-span-2 bg-white dark:bg-[#0a0a16]/60 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-2xl p-5 flex flex-col h-[350px] shadow-sm">
                            <div class="border-b border-slate-100 dark:border-white/5 pb-3 mb-4">
                                <h3 class="text-xs font-black text-slate-700 dark:text-cyan-400 uppercase tracking-widest flex items-center gap-2">
                                    <PresentationChartLineIcon class="h-4.5 w-4.5 text-cyan-500" /> YoY REVENUE & NET PROFIT MARGIN TRENDS ({{ stats.selected_year }} VS {{ stats.selected_year - 1 }})
                                </h3>
                            </div>
                            <div class="flex-1 min-h-0">
                                <Bar :data="yoyCombinedChartData" :options="yoyCombinedChartOptions" />
                            </div>
                        </div>

                        <!-- Balance Sheet Mix -->
                        <div class="bg-white dark:bg-[#0a0a16]/60 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-2xl p-5 flex flex-col h-[350px] shadow-sm">
                            <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/5 pb-3 mb-4">
                                <h3 class="text-xs font-black text-slate-700 dark:text-purple-400 uppercase tracking-widest flex items-center gap-2">
                                    <BanknotesIcon class="h-4.5 w-4.5 text-purple-500" /> BALANCE SHEET MIX
                                </h3>
                            </div>
                            <div class="flex-1 min-h-0 relative flex items-center justify-center">
                                <div class="w-full h-full relative">
                                    <Doughnut :data="financeMixChartData" :options="chartOptions" />
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- DRILL-DOWN BREAKDOWN PANEL (LAZY LOADED ON CLICK) -->
                <transition
                    enter-active-class="transition duration-300 ease-out"
                    enter-from-class="transform scale-95 opacity-0"
                    enter-to-class="transform scale-100 opacity-100"
                    leave-active-class="transition duration-200 ease-in"
                    leave-from-class="transform scale-100 opacity-100"
                    leave-to-class="transform scale-95 opacity-0"
                >
                    <div 
                        v-if="breakdownState.isActive" 
                        ref="breakdownSectionRef" 
                        class="bg-white dark:bg-[#0a0a16]/75 backdrop-blur-2xl border border-cyan-500/25 dark:border-cyan-500/10 rounded-2xl p-6 shadow-lg space-y-6 scroll-mt-6"
                    >
                        <!-- Panel Header -->
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 border-b border-slate-100 dark:border-white/5 gap-4">
                            <div class="space-y-1">
                                <span class="text-[9px] font-black text-cyan-500 tracking-widest uppercase">
                                    Interactive Drill-down
                                </span>
                                <h3 class="text-sm font-black text-slate-800 dark:text-white uppercase tracking-wider flex items-center gap-2">
                                    <ChartBarIcon class="h-4.5 w-4.5 text-cyan-500 animate-pulse" />
                                    Breakdown Penjualan & Margin — {{ breakdownState.periodLabel }} {{ stats.selected_year }}
                                </h3>
                            </div>
                            
                            <!-- Search & Action Buttons -->
                            <div class="flex items-center gap-3">
                                <!-- Instant Search Input -->
                                <div class="relative w-48 sm:w-64">
                                    <input 
                                        type="text" 
                                        v-model="breakdownSearchQuery"
                                        placeholder="Cari nama atau kode..."
                                        class="w-full bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-lg px-3 py-1.5 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-cyan-500 transition-colors"
                                    />
                                    <span v-if="breakdownSearchQuery" @click="breakdownSearchQuery = ''" class="absolute right-2.5 top-1.5 text-xs text-slate-400 hover:text-white cursor-pointer font-bold">×</span>
                                </div>
                                
                                <!-- Close Panel Button -->
                                <button 
                                    @click="resetBreakdown" 
                                    class="p-1.5 rounded-lg border border-slate-200 dark:border-white/10 text-slate-400 hover:text-white hover:bg-white/5 transition-colors text-xs font-bold"
                                    title="Tutup Panel"
                                >
                                    Tutup ×
                                </button>
                            </div>
                        </div>

                        <!-- Breadcrumbs -->
                        <div class="flex items-center gap-2 text-[10px] font-bold text-slate-400">
                            <button 
                                @click="resetBreakdown" 
                                class="hover:text-cyan-500 transition-colors uppercase"
                            >
                                Tren Utama
                            </button>
                            <span>&gt;</span>
                            <button 
                                @click="backToCustomerBreakdown"
                                class="transition-colors uppercase"
                                :class="breakdownState.level === 'customer' ? 'text-cyan-400 font-extrabold cursor-default' : 'hover:text-cyan-500'"
                                :disabled="breakdownState.level === 'customer'"
                            >
                                Semua Pelanggan
                            </button>
                            <template v-if="breakdownState.level === 'product'">
                                <span>&gt;</span>
                                <span class="text-emerald-400 font-extrabold uppercase">
                                    {{ breakdownState.customer?.name }}
                                </span>
                            </template>
                        </div>

                        <!-- Loading State -->
                        <div v-if="breakdownState.loading" class="flex flex-col items-center justify-center py-12 space-y-3">
                            <ArrowPathIcon class="h-8 w-8 text-cyan-500 animate-spin" />
                            <span class="text-xs text-slate-400 font-bold uppercase tracking-wider animate-pulse">Menarik data dari server...</span>
                        </div>

                        <!-- Empty State -->
                        <div v-else-if="filteredBreakdownData.length === 0" class="flex flex-col items-center justify-center py-12 text-slate-400 space-y-2">
                            <ExclamationTriangleIcon class="h-8 w-8 text-amber-500" />
                            <span class="text-xs font-bold uppercase">Tidak ada data ditemukan untuk pencarian Anda.</span>
                        </div>

                        <!-- Side-by-side Grid View (Grafik Kiri, Tabel Kanan) -->
                        <div v-else class="grid grid-cols-1 xl:grid-cols-12 gap-8">
                            
                            <!-- Left: Horizontal Breakdown Chart (Top 10) -->
                            <div class="xl:col-span-5 bg-slate-50/50 dark:bg-white/[0.02] border border-slate-100 dark:border-white/5 rounded-xl p-4 flex flex-col h-[350px]">
                                <div class="pb-2 border-b border-slate-100 dark:border-white/5 mb-3 flex items-center justify-between">
                                    <h4 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                                        {{ breakdownState.level === 'customer' ? 'Grafik Kontribusi Pelanggan (Top 10)' : 'Grafik Distribusi Produk (Top 10)' }}
                                    </h4>
                                    <span class="text-[8px] text-slate-400 dark:text-cyan-500 font-bold" v-if="breakdownState.level === 'customer'">
                                        *Klik batang untuk breakdown
                                    </span>
                                </div>
                                <div class="flex-1 min-h-0">
                                    <Bar :data="breakdownChartData" :options="breakdownChartOptions" />
                                </div>
                            </div>

                            <!-- Right: Detailed Data Table -->
                            <div class="xl:col-span-7 max-h-[350px] overflow-y-auto overflow-x-auto border border-slate-100 dark:border-white/5 rounded-xl">
                                <table class="w-full text-left border-collapse text-xs">
                                    <thead>
                                        <tr class="sticky top-0 bg-slate-100 dark:bg-[#0e0e22] border-b border-slate-200 dark:border-white/10 text-[9px] font-black text-slate-400 uppercase tracking-widest z-10">
                                            <th class="py-3 px-4">Kode</th>
                                            <th class="py-3 px-4">{{ breakdownState.level === 'customer' ? 'Nama Pelanggan' : 'Nama Produk' }}</th>
                                            <th class="py-3 px-4 text-right" v-if="breakdownState.level === 'product'">Volume</th>
                                            <th class="py-3 px-4 text-right">Revenue</th>
                                            <th class="py-3 px-4 text-right">Laba</th>
                                            <th class="py-3 px-4 text-center w-32">Margin</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-white/5 font-mono">
                                        <tr 
                                            v-for="item in filteredBreakdownData" 
                                            :key="item.id"
                                            @click="breakdownState.level === 'customer' ? fetchBreakdown(breakdownState.periodLabel, item.id) : null"
                                            class="hover:bg-cyan-500/5 dark:hover:bg-cyan-500/[0.03] transition-colors"
                                            :class="breakdownState.level === 'customer' ? 'cursor-pointer' : ''"
                                        >
                                            <td class="py-3.5 px-4 font-bold text-slate-400">
                                                {{ item.code }}
                                            </td>
                                            <td class="py-3.5 px-4 font-bold text-slate-700 dark:text-slate-200">
                                                <div class="flex items-center gap-2">
                                                    <span>{{ item.name }}</span>
                                                    <span v-if="breakdownState.level === 'customer'" class="text-[9px] text-cyan-500/80 group-hover:underline">
                                                        (Drill-down)
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="py-3.5 px-4 text-right font-bold text-slate-300" v-if="breakdownState.level === 'product'">
                                                {{ formatNumber(item.qty) }} kg
                                            </td>
                                            <td class="py-3.5 px-4 text-right font-black text-slate-900 dark:text-white">
                                                {{ formatJutaan(item.sales) }}
                                            </td>
                                            <td class="py-3.5 px-4 text-right font-bold" :class="item.profit >= 0 ? 'text-emerald-500' : 'text-rose-500'">
                                                {{ item.profit >= 0 ? '+' : '' }}{{ formatJutaan(item.profit) }}
                                            </td>
                                            <td class="py-3.5 px-4 text-center">
                                                <div class="flex items-center justify-center gap-2">
                                                    <!-- Dynamic Progress Bar -->
                                                    <div class="w-12 bg-slate-200 dark:bg-white/10 h-1.5 rounded-full overflow-hidden hidden sm:block">
                                                        <div 
                                                            class="h-full rounded-full"
                                                            :class="item.margin >= 25 ? 'bg-emerald-500' : item.margin >= 15 ? 'bg-amber-500' : 'bg-rose-500'"
                                                            :style="`width: ${Math.min(Math.max(item.margin, 0), 100)}%`"
                                                        ></div>
                                                    </div>
                                                    <span 
                                                        class="px-2 py-0.5 rounded text-[10px] font-bold"
                                                        :class="item.margin >= 25 ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : item.margin >= 15 ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400' : 'bg-rose-500/10 text-rose-600 dark:text-rose-400'"
                                                    >
                                                        {{ item.margin }}%
                                                    </span>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                        </div>

                    </div>
                </transition>

                <!-- SECTION 2: WHAT-IF SIMULASI & AI EXECUTIVE ADVISOR -->
                <div class="space-y-4">
                    <h2 class="text-xs font-black tracking-[0.2em] text-slate-400 dark:text-cyan-500/70 uppercase">
                        II. AI DECISION ADVISOR & SIMULATION
                    </h2>
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                        
                        <!-- Left (4 Cols): What-If Simulation Controls -->
                        <div class="lg:col-span-4 bg-white dark:bg-[#0a0a16]/60 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-2xl p-6 shadow-sm space-y-6">
                            <div class="border-b border-slate-100 dark:border-white/5 pb-3 flex items-center justify-between">
                                <h3 class="text-xs font-black text-slate-800 dark:text-white uppercase tracking-wider flex items-center gap-2">
                                    <AdjustmentsHorizontalIcon class="h-4.5 w-4.5 text-fuchsia-500" /> WHAT-IF CONTROL PANEL
                                </h3>
                                <button @click="resetSliders" class="text-[10px] text-slate-400 hover:text-cyan-500 underline font-bold">
                                    RESET
                                </button>
                            </div>

                            <!-- Price adjustment -->
                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-slate-500 dark:text-slate-400 font-bold">Penyesuaian Harga Jual</span>
                                    <span class="font-black text-cyan-600 dark:text-cyan-400" :class="{ 'text-rose-500': salesPriceAdj < 0 }">
                                        {{ salesPriceAdj > 0 ? '+' : '' }}{{ salesPriceAdj }}%
                                    </span>
                                </div>
                                <input 
                                    type="range" 
                                    min="-20" 
                                    max="30" 
                                    v-model.number="salesPriceAdj" 
                                    class="w-full h-1.5 bg-slate-200 dark:bg-slate-800 rounded-lg appearance-none cursor-pointer accent-cyan-500"
                                />
                            </div>

                            <!-- Raw Material Cost adjustment -->
                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-slate-500 dark:text-slate-400 font-bold">Harga Bahan Baku (Steel Coil)</span>
                                    <span class="font-black text-amber-600 dark:text-amber-400" :class="{ 'text-rose-500': rawMaterialCostAdj > 0 }">
                                        {{ rawMaterialCostAdj > 0 ? '+' : '' }}{{ rawMaterialCostAdj }}%
                                    </span>
                                </div>
                                <input 
                                    type="range" 
                                    min="-30" 
                                    max="20" 
                                    v-model.number="rawMaterialCostAdj" 
                                    class="w-full h-1.5 bg-slate-200 dark:bg-slate-800 rounded-lg appearance-none cursor-pointer accent-amber-500"
                                />
                            </div>

                            <!-- Production Target adjustment -->
                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-slate-500 dark:text-slate-400 font-bold">Kapasitas/Volume Produksi</span>
                                    <span class="font-black text-indigo-600 dark:text-indigo-400">
                                        {{ productionTargetAdj > 0 ? '+' : '' }}{{ productionTargetAdj }}%
                                    </span>
                                </div>
                                <input 
                                    type="range" 
                                    min="-50" 
                                    max="50" 
                                    v-model.number="productionTargetAdj" 
                                    class="w-full h-1.5 bg-slate-200 dark:bg-slate-800 rounded-lg appearance-none cursor-pointer accent-indigo-500"
                                />
                            </div>

                            <!-- Local Projections Results -->
                            <div class="border-t border-slate-100 dark:border-white/5 pt-4 space-y-3 font-mono text-xs">
                                <div class="text-[10px] font-black text-slate-400 dark:text-cyan-500/70 uppercase tracking-widest mb-2">PROYEKSI SIMULASI</div>
                                
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Estimasi Margin:</span>
                                    <span class="font-bold text-slate-800 dark:text-white" :class="{ 'text-emerald-500 dark:text-emerald-400': projectedMargin > stats.profit_margin, 'text-rose-500': projectedMargin < stats.profit_margin }">
                                        {{ projectedMargin.toFixed(2) }}%
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Margin Shift:</span>
                                    <span class="font-bold" :class="{ 'text-emerald-500': projectedMargin - stats.profit_margin > 0, 'text-rose-500': projectedMargin - stats.profit_margin < 0 }">
                                        {{ projectedMargin - stats.profit_margin >= 0 ? '+' : '' }}{{ (projectedMargin - stats.profit_margin).toFixed(2) }}%
                                    </span>
                                </div>

                                <div class="w-full h-[180px] pt-2">
                                    <Bar :data="projectionChartData" :options="chartOptions" />
                                </div>
                            </div>

                            <!-- Ask AI Button -->
                            <button 
                                @click="runAiAdvisor" 
                                :disabled="isGenerating"
                                class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl border border-purple-500/30 bg-purple-500/10 hover:bg-purple-500/20 active:scale-95 transition-all text-xs font-black text-purple-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-300 disabled:opacity-50 select-none shadow-sm"
                            >
                                <SparklesIcon class="h-4 w-4 animate-pulse text-purple-500" v-if="!isGenerating" />
                                <ArrowPathIcon class="h-4 w-4 animate-spin text-purple-500" v-else />
                                {{ isGenerating ? 'MENGANALISIS DATA DI CLOUD...' : 'MINTA REKOMENDASI AI' }}
                            </button>
                        </div>

                        <!-- Right (8 Cols): AI Strategic Analysis Response -->
                        <div class="lg:col-span-8 space-y-6">
                            
                            <div class="bg-white dark:bg-[#0a0a16]/60 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-2xl p-6 shadow-sm min-h-[400px] flex flex-col justify-between">
                                <div class="border-b border-slate-100 dark:border-white/5 pb-3 mb-4 flex items-center justify-between">
                                    <h3 class="text-xs font-black text-slate-700 dark:text-purple-400 uppercase tracking-widest flex items-center gap-2">
                                        <CpuChipIcon class="h-4.5 w-4.5" /> AI EXECUTIVE STRATEGIC ADVISOR
                                    </h3>
                                    <span class="text-[9px] text-slate-400 font-bold uppercase">MODEL: GEMINI-1.5-FLASH</span>
                                </div>

                                <!-- Processing Glow State -->
                                <div v-if="isGenerating" class="flex-1 flex flex-col items-center justify-center space-y-4 py-12">
                                    <div class="relative w-16 h-16 flex items-center justify-center">
                                        <div class="absolute inset-0 rounded-full border-4 border-purple-500/20 border-t-purple-500 animate-spin"></div>
                                        <SparklesIcon class="h-6 w-6 text-purple-400 animate-pulse" />
                                    </div>
                                    <div class="text-center">
                                        <p class="text-xs text-purple-500 dark:text-purple-400 font-black animate-pulse tracking-[0.2em] uppercase">AI ENGINE: COMPUTING PROJECTIONS...</p>
                                        <p class="text-[10px] text-slate-400 font-bold mt-1">Menganalisis margin baja, stock opex, & parameter What-If...</p>
                                    </div>
                                </div>

                                <!-- Normal Display State -->
                                <div v-else class="space-y-6 flex-1 flex flex-col justify-between">
                                    
                                    <!-- 1. Performance Summary -->
                                    <div class="space-y-2">
                                        <div class="text-[10px] font-black text-slate-400 dark:text-cyan-500/70 uppercase tracking-widest">
                                            Analisis Performa Korporat
                                        </div>
                                        <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed bg-slate-50 dark:bg-white/5 border border-slate-200/50 dark:border-white/5 p-4 rounded-xl">
                                            {{ aiAdvice.performance_analysis }}
                                        </p>
                                    </div>

                                    <!-- 2. Strategic recommendations cards -->
                                    <div class="space-y-3">
                                        <div class="text-[10px] font-black text-slate-400 dark:text-cyan-500/70 uppercase tracking-widest">
                                            Langkah Strategis Manajemen Puncak
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div 
                                                v-for="(advice, idx) in aiAdvice.strategic_advice" 
                                                :key="idx"
                                                class="p-4 rounded-xl border border-slate-200 dark:border-white/5 bg-slate-50 dark:bg-white/5 relative overflow-hidden hover:border-purple-500/30 transition-all duration-300"
                                            >
                                                <span class="absolute -right-3 -bottom-3 text-5xl font-black text-purple-500/10 font-mono select-none">
                                                    0{{ idx + 1 }}
                                                </span>
                                                <div class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed pr-2">
                                                    {{ advice }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 3. Warnings Alert block -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-t border-slate-100 dark:border-white/5 pt-4">
                                        <div class="space-y-2">
                                            <div class="text-[10px] font-black text-slate-400 dark:text-rose-500/70 uppercase tracking-widest flex items-center gap-1">
                                                <ExclamationTriangleIcon class="h-3.5 w-3.5 text-rose-500" /> Early Warning Signs
                                            </div>
                                            <p class="text-xs text-rose-700 dark:text-rose-400 bg-rose-500/5 border border-rose-500/20 p-3 rounded-xl leading-relaxed">
                                                {{ aiAdvice.warning_signs }}
                                            </p>
                                        </div>

                                        <div class="space-y-2">
                                            <div class="text-[10px] font-black text-slate-400 dark:text-purple-500/70 uppercase tracking-widest flex items-center gap-1">
                                                <SparklesIcon class="h-3.5 w-3.5 text-purple-500" /> Proyeksi Hasil Simulasi
                                            </div>
                                            <p class="text-xs text-purple-700 dark:text-purple-400 bg-purple-500/5 border border-purple-500/20 p-3 rounded-xl leading-relaxed">
                                                {{ aiAdvice.simulated_outcome }}
                                            </p>
                                        </div>
                                    </div>

                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- SECTION 3: OPERATIONAL & MODULE INTELLIGENCE -->
                <div class="space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-1 gap-4">
                        <h2 class="text-xs font-black tracking-[0.2em] text-slate-400 dark:text-cyan-500/70 uppercase">
                            III. OPERATIONAL & MODULE INTELLIGENCE
                        </h2>
                        
                        <!-- Operational Aggregation Toggles -->
                        <div class="flex flex-wrap items-center gap-2">
                            <!-- Toggle YoY / Normal -->
                            <div class="flex items-center gap-0.5 bg-slate-100 dark:bg-white/5 p-0.5 rounded-lg border border-slate-200 dark:border-white/10 shadow-sm">
                                <button 
                                    @click="opsYoY = false"
                                    class="px-2.5 py-1 text-[9px] font-bold uppercase rounded-md tracking-wider transition-all"
                                    :class="!opsYoY ? 'bg-amber-500 text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white'"
                                >
                                    Normal
                                </button>
                                <button 
                                    @click="opsYoY = true"
                                    class="px-2.5 py-1 text-[9px] font-bold uppercase rounded-md tracking-wider transition-all"
                                    :class="opsYoY ? 'bg-amber-500 text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white'"
                                >
                                    YoY
                                </button>
                            </div>

                            <!-- Toggle Akumulasi / Periodik -->
                            <div class="flex items-center gap-0.5 bg-slate-100 dark:bg-white/5 p-0.5 rounded-lg border border-slate-200 dark:border-white/10 shadow-sm">
                                <button 
                                    @click="opsCumulative = false"
                                    class="px-2.5 py-1 text-[9px] font-bold uppercase rounded-md tracking-wider transition-all"
                                    :class="!opsCumulative ? 'bg-cyan-500 text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white'"
                                >
                                    Periodik
                                </button>
                                <button 
                                    @click="opsCumulative = true"
                                    class="px-2.5 py-1 text-[9px] font-bold uppercase rounded-md tracking-wider transition-all"
                                    :class="opsCumulative ? 'bg-cyan-500 text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white'"
                                >
                                    Akumulasi
                                </button>
                            </div>

                            <!-- Interval Selector -->
                            <div class="flex items-center gap-0.5 bg-slate-100 dark:bg-white/5 p-0.5 rounded-lg border border-slate-200 dark:border-white/10 shadow-sm">
                                <button 
                                    v-for="mode in ['monthly', 'quarterly', 'semester']"
                                    :key="mode"
                                    @click="opsInterval = mode"
                                    class="px-2.5 py-1 text-[9px] font-bold uppercase rounded-md tracking-wider transition-all"
                                    :class="opsInterval === mode ? 'bg-fuchsia-500 text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white'"
                                >
                                    {{ mode === 'monthly' ? 'Bulan' : mode === 'quarterly' ? 'Kuartal' : 'Semester' }}
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        
                        <!-- 1. Manufacturing: Target vs Yield -->
                        <div class="bg-white dark:bg-[#0a0a16]/60 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-2xl p-5 flex flex-col h-[350px] shadow-sm">
                            <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/5 pb-3 mb-4">
                                <h3 class="text-xs font-black text-slate-700 dark:text-rose-400 uppercase tracking-widest flex items-center gap-2">
                                    <CpuChipIcon class="h-4.5 w-4.5 text-rose-500" /> MANUFAKTUR: TARGET VS YIELD PRODUKSI
                                </h3>
                            </div>
                            <div class="flex-1 min-h-0">
                                <Line :data="mfgYieldChartData" :options="mfgChartOptions" />
                            </div>
                        </div>

                        <!-- 2. Quality Control: Defect NCR Trends -->
                        <div class="bg-white dark:bg-[#0a0a16]/60 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-2xl p-5 flex flex-col h-[350px] shadow-sm">
                            <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/5 pb-3 mb-4">
                                <h3 class="text-xs font-black text-slate-700 dark:text-amber-400 uppercase tracking-widest flex items-center gap-2">
                                    <CheckBadgeIcon class="h-4.5 w-4.5 text-amber-500" /> QUALITY CONTROL: TREN DEFECT NCR
                                </h3>
                            </div>
                            <div class="flex-1 min-h-0">
                                <Bar :data="qcDefectsChartData" :options="stackedBarOptions" />
                            </div>
                        </div>

                        <!-- 3. Logistics & HR: OTIF & Staffing -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            <!-- Logistics OTIF Rate -->
                            <div class="bg-white dark:bg-[#0a0a16]/60 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-2xl p-5 flex flex-col h-[350px] shadow-sm justify-between">
                                <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/5 pb-3 mb-2">
                                    <h3 class="text-xs font-black text-slate-700 dark:text-emerald-400 uppercase tracking-widest flex items-center gap-2">
                                        <TruckIcon class="h-4.5 w-4.5 text-emerald-500" /> LOGISTIK: ON-TIME DELIVERY (OTIF)
                                    </h3>
                                </div>
                                <div class="flex-1 min-h-0 relative flex items-center justify-center">
                                    <div class="w-full h-[180px] relative">
                                        <Doughnut :data="logisticsOtifChartData" :options="chartOptions" />
                                        <!-- Centered Text -->
                                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none mt-4">
                                            <span class="text-2xl font-black text-slate-900 dark:text-emerald-400">{{ activeOtifRate }}%</span>
                                            <span class="text-[8px] text-slate-400 font-bold uppercase tracking-wider">TEPAT WAKTU</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-[9px] text-slate-400 text-center border-t border-slate-100 dark:border-white/5 pt-2">
                                    Persentase pengiriman DO tepat waktu oleh armada driver
                                </div>
                            </div>

                            <!-- HR Attendance & Overtime -->
                            <div class="bg-white dark:bg-[#0a0a16]/60 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-2xl p-5 flex flex-col h-[350px] shadow-sm">
                                <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/5 pb-3 mb-4">
                                    <h3 class="text-xs font-black text-slate-700 dark:text-indigo-400 uppercase tracking-widest flex items-center gap-2">
                                        <UsersIcon class="h-4.5 w-4.5 text-indigo-500" /> HR: TREN LEMBUR & KEHADIRAN
                                    </h3>
                                </div>
                                <div class="flex-1 min-h-0">
                                    <Line :data="hrAttendanceChartData" :options="hrChartOptions" />
                                </div>
                            </div>

                        </div>

                        <!-- 4. Inventory: Stock Valuation by Warehouse -->
                        <div class="bg-white dark:bg-[#0a0a16]/60 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-2xl p-5 flex flex-col h-[350px] shadow-sm">
                            <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/5 pb-3 mb-4">
                                <h3 class="text-xs font-black text-slate-700 dark:text-blue-400 uppercase tracking-widest flex items-center gap-2">
                                    <CubeIcon class="h-4.5 w-4.5 text-blue-500" /> INVENTORI: VALUASI STOK PER GUDANG
                                </h3>
                            </div>
                            <div class="flex-1 min-h-0">
                                <Bar :data="inventoryValChartData" :options="horizontalBarOptions" />
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
input[type="range"]::-webkit-slider-thumb {
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: #c084fc;
    cursor: pointer;
    box-shadow: 0 0 6px rgba(192, 132, 252, 0.8);
    transition: transform 0.1s;
}
input[type="range"]::-webkit-slider-thumb:hover {
    transform: scale(1.25);
}
</style>
