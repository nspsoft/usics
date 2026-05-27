<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import {
    HomeIcon,
    CubeIcon,
    ShoppingCartIcon,
    CurrencyDollarIcon,
    WrenchScrewdriverIcon,
    Cog6ToothIcon,
    Bars3Icon,
    XMarkIcon,
    BellIcon,
    InboxIcon,
    UserCircleIcon,
    ChevronDownIcon,
    ArrowRightStartOnRectangleIcon,
    MagnifyingGlassIcon,
    ClipboardDocumentListIcon,
    Bars3BottomLeftIcon,
    ArrowDownOnSquareIcon,
    ArrowsPointingOutIcon,
    ArrowsPointingInIcon,
    PencilSquareIcon,
    CheckBadgeIcon,
    ShieldExclamationIcon,
    BanknotesIcon,
    UsersIcon,
    TruckIcon,
    UserPlusIcon,
    ChartBarIcon,
    GlobeAltIcon,
    SunIcon,
    MoonIcon,
    CpuChipIcon,
    BriefcaseIcon,
    BookOpenIcon,
    // New Icons for Submenus
    PresentationChartBarIcon,
    ChartBarSquareIcon,
    CalendarDaysIcon,
    UserGroupIcon,
    DocumentTextIcon,
    ClipboardDocumentCheckIcon,
    ArrowUturnLeftIcon,
    InformationCircleIcon,
    MapPinIcon,
    SparklesIcon,
    ChatBubbleLeftRightIcon,
    FunnelIcon,
    MegaphoneIcon,
    BuildingOfficeIcon,
    DocumentPlusIcon,
    ArchiveBoxArrowDownIcon,
    CalculatorIcon,
    TableCellsIcon,
    ClockIcon,
    WrenchIcon,
    ClipboardDocumentIcon,
    ListBulletIcon,
    IdentificationIcon,
    TableCellsIcon as SpreadsheetIcon,
    // Fix Missing Imports
    FlagIcon,
    TagIcon,
    ScaleIcon,
    BuildingStorefrontIcon,
    ArrowsRightLeftIcon,
    ShareIcon,
    ArrowPathIcon,
    PresentationChartLineIcon,
    PlusCircleIcon,
    MapIcon,
    CheckCircleIcon,
    ShieldCheckIcon,
    QueueListIcon,
    AdjustmentsHorizontalIcon,
    CircleStackIcon,
    TrophyIcon,
    CalendarIcon,
    CalendarDaysIcon as CalendarAltIcon,
} from '@heroicons/vue/24/outline';
import TechnoHeaderBg from '@/Components/TechnoHeaderBg.vue';

const props = defineProps({
    title: {
        type: String,
        default: 'Dashboard'
    },
    renderHeader: {
        type: Boolean,
        default: true
    }
});

const page = usePage();
const sidebarOpen = ref(false);
const collapsed = ref(false);
const userMenuOpen = ref(false);
const isFullscreen = ref(false);
const isInstalled = ref(false);
const isDark = ref(false);
const waUnreadCount = ref(0);
let waUnreadInterval = null;

// Flash Notifications Logic
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);
const showFlash = ref(false);

watch([flashSuccess, flashError], ([newSuccess, newError]) => {
    if (newSuccess || newError) {
        showFlash.value = true;
        setTimeout(() => {
            showFlash.value = false;
        }, 5000);
    }
});

// PWA Install Prompt
const deferredPrompt = ref(null);
const canInstall = ref(false);

const handleBeforeInstallPrompt = (e) => {
    e.preventDefault();
    deferredPrompt.value = e;
    canInstall.value = true;
};

const handleAppInstalled = () => {
    isInstalled.value = true;
    canInstall.value = false;
    deferredPrompt.value = null;
};

const installApp = async () => {
    if (!deferredPrompt.value) return;
    
    deferredPrompt.value.prompt();
    const { outcome } = await deferredPrompt.value.userChoice;
    
    if (outcome === 'accepted') {
        isInstalled.value = true;
        canInstall.value = false;
    }
    deferredPrompt.value = null;
};

onMounted(() => {
    if (window.matchMedia('(display-mode: standalone)').matches) {
        isInstalled.value = true;
    }
    window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
    window.addEventListener('appinstalled', handleAppInstalled);
});

onUnmounted(() => {
    window.removeEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
    window.removeEventListener('appinstalled', handleAppInstalled);
});

const hasPermission = (permission) => {
    if (!permission) return true;
    const auth = page.props.auth;
    if (!auth) return false;
    if (auth.roles?.includes('Super Admin')) return true;
    return auth.permissions?.includes(permission);
};

const mobileNavCatalog = {
    home: { name: 'Home', href: '/', icon: HomeIcon },
    menu: { name: 'Menu', action: 'openMenu', icon: Bars3Icon },
    sales: { name: 'Sales', href: '/sales/dashboard', icon: CurrencyDollarIcon, permission: 'sales_crm.view' },
    purchasing: { name: 'Purchasing', href: '/purchasing/dashboard', icon: ShoppingCartIcon, permission: 'purchasing.view' },
    inventory: { name: 'Inventory', href: '/inventory/dashboard', icon: CubeIcon, permission: 'inventory.view' },
    finance: { name: 'Finance', href: '/finance/dashboard', icon: BanknotesIcon, permission: 'finance.view' },
    profile: { name: 'Profile', href: '/profile', icon: UserCircleIcon },
};

const mobileNavByRole = {
    'Super Admin': ['home', 'sales', 'purchasing', 'inventory', 'finance'],
    Finance: ['home', 'finance', 'purchasing', 'sales', 'profile'],
    Purchasing: ['home', 'purchasing', 'inventory', 'finance', 'profile'],
    Sales: ['home', 'sales', 'purchasing', 'finance', 'profile'],
    Inventory: ['home', 'inventory', 'purchasing', 'finance', 'profile'],
    default: ['home', 'menu', 'purchasing', 'finance', 'profile'],
};

const mobileNavItems = computed(() => {
    const roles = page.props.auth?.roles ?? [];
    const matchedRole = roles.find((r) => Object.prototype.hasOwnProperty.call(mobileNavByRole, r));
    const keys = mobileNavByRole[matchedRole ?? 'default'] ?? mobileNavByRole.default;

    const result = [];
    for (const key of keys) {
        const item = mobileNavCatalog[key];
        if (!item) continue;
        if (item.permission && !hasPermission(item.permission)) continue;
        if (!result.some((x) => (x.href && item.href && x.href === item.href) || (x.action && item.action && x.action === item.action))) {
            result.push(item);
        }
        if (result.length === 5) break;
    }

    const fallbackKeys = mobileNavByRole.default;
    for (const key of fallbackKeys) {
        if (result.length === 5) break;
        const item = mobileNavCatalog[key];
        if (!item) continue;
        if (item.permission && !hasPermission(item.permission)) continue;
        if (!result.some((x) => (x.href && item.href && x.href === item.href) || (x.action && item.action && x.action === item.action))) {
            result.push(item);
        }
    }

    return result.slice(0, 5);
});

const isMobileNavActive = (href) => {
    if (!href) return false;
    const url = page.url ?? '';
    if (href === '/') return url === '/' || url === '';
    return url.startsWith(href);
};

const navigation = [
    { name: 'Dashboard', href: '/', icon: HomeIcon, current: true },
    { 
        name: 'Sales', 
        href: '#', 
        icon: CurrencyDollarIcon, 
        current: false,
        permission: 'sales_crm.view',
        children: [
            { name: 'Sales Hub', href: '/sales/dashboard', icon: PresentationChartBarIcon, permission: 'sales_crm.sales_hub.view' },
            { name: 'Planning', href: '/sales/planning/dashboard', icon: ChartBarSquareIcon, permission: 'sales_crm.planning.view' },
            { name: 'Forecast', href: '/sales/planning/forecast', icon: CalendarDaysIcon, permission: 'sales_crm.forecast.view' },
            { name: 'Schedule', href: '/sales/planning/schedule', icon: TruckIcon, permission: 'sales_crm.schedule.view' },
            { name: 'Customers', href: '/sales/customers', icon: UserGroupIcon, permission: 'sales_crm.customers.view' },
            { name: 'Quotations', href: '/sales/quotations', icon: DocumentTextIcon, permission: 'sales_crm.quotations.view' },
            { name: 'Sales Orders', href: '/sales/orders', icon: ShoppingCartIcon, permission: 'sales_crm.sales_orders.view' },
            { name: 'Delivery Orders', href: '/sales/deliveries', icon: ClipboardDocumentCheckIcon, permission: 'sales_crm.delivery_orders.view' },
            { name: 'Sales Invoices', href: '/sales/invoices', icon: BanknotesIcon, permission: 'sales_crm.invoices.view' },
            { name: 'Sales Returns', href: '/sales/returns', icon: ArrowUturnLeftIcon, permission: 'sales_crm.sales_returns.view' },
            { name: 'SO Items Report', href: '/sales/orders/items', icon: ClipboardDocumentListIcon, permission: 'sales_crm.items_report.view' },
            { name: 'DO Items Report', href: '/sales/deliveries/items', icon: QueueListIcon, permission: 'sales_crm.items_report.view' },
            { name: 'Information', href: '/sales/information', icon: InformationCircleIcon, permission: 'sales_crm.information.view' },
            { name: 'PO Tracking', href: '/sales/po-tracking', icon: MapPinIcon, permission: 'sales_crm.po_tracking.view' },
            { name: 'AI PO Extractor', href: '/sales/po-extractor', icon: SparklesIcon, permission: 'sales_crm.ai_po_extractor.view' },
        ]
    },
    { 
        name: 'CRM', 
        href: '#', 
        icon: UserPlusIcon, 
        current: false,
        permission: 'sales_crm.view',
        children: [
            { name: 'WhatsApp Center', href: '/sales/whatsapp', icon: ChatBubbleLeftRightIcon, permission: 'sales_crm.whatsapp_center.view', badgeKey: 'waUnread' },
            { name: 'AI Email Inbox', href: '/sales/emails', icon: InboxIcon, permission: 'sales_crm.ai_email_inbox.view' },
            { name: 'CRM Intelligence', href: '/crm/dashboard', icon: ChartBarIcon, permission: 'sales_crm.crm_intelligence.view' },
            { name: 'Leads Management', href: '/crm/leads', icon: FunnelIcon, permission: 'sales_crm.leads_management.view' },
            { name: 'Opportunity Tracking', href: '/crm/opportunities', icon: FlagIcon, permission: 'sales_crm.opportunity_tracking.view' },
            { name: 'Marketing Campaigns', href: '/crm/campaigns', icon: MegaphoneIcon, permission: 'sales_crm.marketing_campaigns.view' },
        ]
    },
    { 
        name: 'Purchasing', 
        href: '#', 
        icon: ShoppingCartIcon, 
        current: false,
        permission: 'purchasing.view',
        children: [
            { name: 'Procurement Ops', href: '/purchasing/dashboard', icon: PresentationChartBarIcon, permission: 'purchasing.view' },
            { name: 'Delivery Schedule', href: '/purchasing/delivery-schedule', icon: CalendarDaysIcon, permission: 'purchasing.view' },
            { name: 'Procurement Forecast', href: '/purchasing/procurement-forecast', icon: ChartBarSquareIcon, permission: 'purchasing.view' },
            { name: 'Supplier Scorecard', href: '/purchasing/supplier-scorecard', icon: TrophyIcon, permission: 'purchasing.view' },
            { name: 'Information', href: '/purchasing/information', icon: InformationCircleIcon, permission: 'purchasing.view' },
            { name: 'Suppliers', href: '/purchasing/suppliers', icon: BuildingOfficeIcon, permission: 'purchasing.suppliers.view' },
            { name: 'Purchase Requests', href: '/purchasing/requests', icon: DocumentPlusIcon, permission: 'purchasing.purchase_requests.view' },
            { name: 'Purchase Orders', href: '/purchasing/orders', icon: ShoppingCartIcon, permission: 'purchasing.purchase_orders.view' },
            { name: 'Goods Receipts', href: '/purchasing/receipts', icon: ArchiveBoxArrowDownIcon, permission: 'purchasing.goods_receipts.view' },
            { name: 'AI Gen. Receipt', href: '/purchasing/dn-extractor', icon: SparklesIcon, permission: 'purchasing.goods_receipts.view' },
            { name: 'Purchase Invoices', href: '/purchasing/invoices', icon: BanknotesIcon, permission: 'purchasing.purchase_invoices.view' },
            { name: 'Purchase Returns', href: '/purchasing/returns', icon: ArrowUturnLeftIcon, permission: 'purchasing.purchase_returns.view' },
            { name: 'PO Items Report', href: '/purchasing/orders/items', icon: ClipboardDocumentListIcon, permission: 'purchasing.view' },
            { name: 'GR Items Report', href: '/purchasing/receipts/items', icon: QueueListIcon, permission: 'purchasing.view' },
        ]
    },
    { 
        name: 'Inventory', 
        href: '#', 
        icon: CubeIcon, 
        current: false,
        permission: 'inventory.view',
        children: [
            { name: 'Command Center', href: '/inventory/dashboard', icon: PresentationChartBarIcon, permission: 'inventory.view' },
            { name: 'Categories', href: '/inventory/categories', icon: TagIcon, permission: 'inventory.categories.view' },
            { name: 'Products', href: '/inventory/products', icon: CubeIcon, permission: 'inventory.products.view' },
            { name: 'Unit Management', href: '/inventory/units', icon: ScaleIcon, permission: 'inventory.products.view' },
            { name: 'Current Stock', href: '/inventory/stocks', icon: ClipboardDocumentListIcon, permission: 'inventory.current_stock.view' },
            { name: 'Warehouses', href: '/inventory/warehouses', icon: BuildingStorefrontIcon, permission: 'inventory.warehouses.view' },
            { name: 'Stock Movements', href: '/inventory/movements', icon: ArrowsRightLeftIcon, permission: 'inventory.stock_movements.view' },
            { name: 'Stock Transfers', href: '/inventory/transfers', icon: ArrowsRightLeftIcon, permission: 'inventory.stock_movements.view' },
            { name: 'Stock Opname', href: '/inventory/opname', icon: ClipboardDocumentCheckIcon, permission: 'inventory.stock_opname.view' },
            { name: 'Inventory Aging', href: '/inventory/reports/inventory-aging', icon: ClockIcon, permission: 'inventory.view' },
        ]
    },
    { 
        name: 'Manufacturing', 
        href: '#', 
        icon: CpuChipIcon, 
        current: false,
        permission: 'manufacturing.view',
        children: [
            { name: 'Intelligence Hub', href: '/manufacturing/dashboard', icon: PresentationChartBarIcon, permission: 'manufacturing.view' },
            { name: 'Bill of Materials', href: '/manufacturing/boms', icon: ListBulletIcon, permission: 'manufacturing.bill_of_materials.view' },
            { name: 'Production Routing', href: '/manufacturing/routing', icon: ArrowsRightLeftIcon, permission: 'manufacturing.production_routing.view' },
            { name: 'Work Orders', href: '/manufacturing/work-orders', icon: ClipboardDocumentListIcon, permission: 'manufacturing.work_orders.view' },
            { name: 'Input Output', href: '/manufacturing/production-entry', icon: ArrowDownOnSquareIcon, permission: 'manufacturing.input_output.view' },
            { name: 'Shift Management', href: '/manufacturing/shifts', icon: ClockIcon, permission: 'manufacturing.shift_management.view' },
            { name: 'Machine Management', href: '/manufacturing/machines', icon: Cog6ToothIcon, permission: 'manufacturing.machine_management.view' },
            { name: 'Subcontract Orders', href: '/manufacturing/subcontract-orders', icon: ShareIcon, permission: 'manufacturing.subcontract_orders.view' },
        ]
    },
    { 
        name: 'Maintenance', 
        href: '#', 
        icon: WrenchScrewdriverIcon, 
        current: false,
        permission: 'maintenance.view',
        children: [
            { name: 'Preventive Schedule', href: '/maintenance/schedule', icon: CalendarDaysIcon, permission: 'maintenance.schedule.view' },
            { name: 'Breakdown Logs', href: '/maintenance/breakdown', icon: WrenchIcon, permission: 'maintenance.breakdown.view' },
            { name: 'Spareparts Inventory', href: '/maintenance/spareparts', icon: Cog6ToothIcon, permission: 'maintenance.spareparts.view' },
        ]
    },
    { 
        name: 'Quality Control', 
        href: '#', 
        icon: CheckBadgeIcon, 
        current: false,
        permission: 'qc.view',
        children: [
            { name: 'QC Dashboard', href: '/qc/dashboard', icon: PresentationChartBarIcon, permission: 'qc.view' },
            { name: 'Incoming Inspection', href: '/qc/incoming', icon: ArrowDownOnSquareIcon, permission: 'qc.incoming_inspection.view' },
            { name: 'In-Process QC', href: '/qc/in-process', icon: ArrowPathIcon, permission: 'qc.in-process_qc.view' },
            { name: 'Defect Management (NCR)', href: '/qc/ncr', icon: ShieldExclamationIcon, permission: 'qc.ncr.view' },
            { name: 'COA Generator', href: '/qc/coa/create', icon: DocumentTextIcon, permission: 'qc.coa.view' },
            { name: 'Master Data', href: '/qc/master-points', icon: TagIcon, permission: 'qc.master_data.view' },
        ]
    },
    { 
        name: 'Logistics', 
        href: '#', 
        icon: TruckIcon, 
        current: false,
        permission: 'logistics.view',
        children: [
            { name: 'Logistics Hub', href: '/logistics/dashboard', icon: PresentationChartBarIcon, permission: 'logistics.view' },
            { name: 'Loading Queue', href: '/warehouse/loading', icon: CubeIcon, permission: 'logistics.view' },
            { name: 'Delivery Planning', href: '/logistics/planning', icon: MapIcon, permission: 'logistics.delivery_planning.view' },
            { name: 'Dispatch', href: '/logistics/dispatch', icon: TruckIcon, permission: 'logistics.view' },
            { name: 'Vehicle Fleet', href: '/logistics/fleet', icon: TruckIcon, permission: 'logistics.vehicle_fleet.view' },
        ]
    },
    { 
        name: 'Finance', 
        href: '#', 
        icon: BanknotesIcon, 
        current: false,
        permission: 'finance.view',
        children: [
            { name: 'Financial Command', href: '/finance/dashboard', icon: PresentationChartBarIcon, permission: 'finance.general_ledger.view' },
            { name: 'General Ledger', href: '/finance/ledger', icon: BookOpenIcon, permission: 'finance.general_ledger.view' },
            { name: 'Profit & Loss', href: '/finance/reports', icon: ChartBarSquareIcon, permission: 'finance.profit_&_loss.view' },
            { name: 'AP & AR Monitoring', href: '/finance/payment-monitoring', icon: ArrowsRightLeftIcon, permission: 'finance.ap_&_ar_monitoring.view' },
            // Costing Modules merged here
            { name: 'Production Costing', href: '/costing/production', icon: CalculatorIcon, permission: 'finance.production_costing.view' },
            { name: 'Overhead Allocation', href: '/costing/overhead', icon: TableCellsIcon, permission: 'finance.overhead_allocation.view' },
            { name: 'Profitability Analytic', href: '/costing/profitability', icon: PresentationChartLineIcon, permission: 'finance.profitability_analytic.view' },
        ]
    },
    { 
        name: 'Human Resources', 
        href: '#', 
        icon: UsersIcon, 
        current: false,
        children: [
            { name: 'My Time-Off', href: '/my-timeoff', icon: CalendarIcon },
            { name: 'Employee Directory', href: '/hr/employees', icon: IdentificationIcon, permission: 'hr_payroll.employee_directory.view' },
            { name: 'Attendance', href: '/hr/attendance', icon: ClockIcon, permission: 'hr_payroll.attendance.view' },
            { name: 'Leave Management', href: '/hr/leaves', icon: CalendarAltIcon, permission: 'hr_payroll.attendance.view' },
            { name: 'Payroll', href: '/hr/payroll', icon: BanknotesIcon, permission: 'hr_payroll.payroll.view' },
        ]
    },
    { 
        name: 'Project Matrix', 
        href: '#', 
        icon: BriefcaseIcon, 
        current: false,
        children: [
            { name: 'Project Dashboard', href: '/projects', icon: PresentationChartBarIcon },
            { name: 'Initiate Project', href: '/projects/create', icon: PlusCircleIcon },
        ]
    },
    { 
        name: 'Documentation', 
        href: '#', 
        icon: BookOpenIcon, 
        current: false, 
        children: [
            { name: 'Blueprint Interactive', href: '/project/blueprint', icon: MapIcon },
            { name: 'System Testing (UAT)', href: '/settings/uat', icon: CheckCircleIcon, permission: 'settings.view', target: '_blank' },
        ]
    },
    { 
        name: 'Settings', 
        href: '#', 
        icon: Cog6ToothIcon, 
        current: false,
        permission: 'settings.view',
        children: [
            { name: 'User Management', href: '/settings/users', icon: UsersIcon, permission: 'settings.user_management.view' },
            { name: 'Roles & Permissions', href: '/settings/roles', icon: ShieldCheckIcon, permission: 'settings.roles_&_permissions.view' },
            { name: 'Company Profile', href: '/settings/company', icon: BuildingOfficeIcon, permission: 'settings.company_profile.view' },
            { name: 'Departments', href: '/settings/departments', icon: BuildingOfficeIcon, permission: 'settings.view' },
            { name: 'AI Configuration', href: '/settings/ai', icon: CpuChipIcon, permission: 'settings.company_profile.view' },
            { name: 'Document Numbering', href: '/settings/numbering', icon: QueueListIcon, permission: 'settings.document_numbering.view' },
            { name: 'Regional & Tax', href: '/settings/regional', icon: GlobeAltIcon, permission: 'settings.regional_&_tax.view' },
            { name: 'System Preferences', href: '/settings/preferences', icon: AdjustmentsHorizontalIcon, permission: 'settings.system_preferences.view' },
            { name: 'Workflow Approval', href: '/settings/workflow', icon: CheckBadgeIcon, permission: 'settings.workflow_approval.view' },
            { name: 'Database Management', href: '/settings/database', icon: CircleStackIcon, permission: 'settings.database_management.view' },
            { name: 'Activity Logs', href: '/admin/activity-logs', icon: ClipboardDocumentListIcon, permission: 'settings.activity_logs.view' },
            { name: 'WhatsApp Bot', href: '/settings/whatsapp', icon: ChatBubbleLeftRightIcon, permission: 'settings.company_profile.view' },
        ]
    },
];

const filteredNavigation = computed(() => {
    return navigation
        .filter(item => hasPermission(item.permission))
        .map(item => {
            if (item.children) {
                return {
                    ...item,
                    children: item.children.filter(child => hasPermission(child.permission))
                };
            }
            return item;
        })
        .filter(item => !item.children || item.children.length > 0);
});

const expandedMenus = ref({});

// Initialize expanded state based on current URL
const initExpandedMenus = () => {
    // Ensure we handle potential trailing slashes or query params loosely if needed
    // But startsWith is usually consistent for hierarchical paths
    const currentUrl = page.url; 
    
    navigation.forEach(item => {
        if (item.children) {
            // Check if any child matches the current URL
            const hasActiveChild = item.children.some(child => 
                child.href !== '#' && currentUrl.startsWith(child.href)
            );
            
            if (hasActiveChild) {
                expandedMenus.value[item.name] = true;
            }
        }
    });
};

initExpandedMenus();

const toggleMenu = (name) => {
    expandedMenus.value[name] = !expandedMenus.value[name];
};

const closeSidebar = () => {
    sidebarOpen.value = false;
};

const toggleDesktopSidebar = () => {
    collapsed.value = !collapsed.value;
    // Close expanded menus when collapsing
    if (collapsed.value) {
        expandedMenus.value = {};
    }
};

// Notifications logic
const notificationsOpen = ref(false);
const unreadCount = computed(() => page.props.auth?.unreadNotificationsCount || 0);
const recentNotifications = computed(() => page.props.auth?.recentNotifications || []);



// Clock Logic
const currentDate = ref('');

// Immediate initialization (outside onMounted)
const dateOptions = { 
    weekday: 'long', 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
};

try {
    currentDate.value = new Date().toLocaleDateString('id-ID', dateOptions);
} catch (e) {
    console.error('Date formatting error:', e);
    currentDate.value = new Date().toDateString();
}

onMounted(() => {
    // Initialize theme
    if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        isDark.value = true;
        document.documentElement.classList.add('dark');
    } else {
        isDark.value = false;
        document.documentElement.classList.remove('dark');
    }
    
    const checkInstall = () => {
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches;
        if (isStandalone || window.navigator.standalone === true) {
            isInstalled.value = true;
        }
    };
    
    checkInstall();

    // Poll WhatsApp unread count every 30 seconds
    const fetchWaUnread = () => {
        axios.get('/sales/whatsapp/unread-count').then(r => {
            waUnreadCount.value = r.data?.total || 0;
        }).catch(() => {});
    };
    fetchWaUnread();
    waUnreadInterval = setInterval(fetchWaUnread, 30000);

    // Listen to fullscreen change events
    document.addEventListener('fullscreenchange', () => {
        isFullscreen.value = !!document.fullscreenElement;
    });

    // Handle screen resize to close mobile sidebar on desktop
    const handleResize = () => {
        if (window.innerWidth >= 1024) { // lg breakpoint
            sidebarOpen.value = false;
        }
    };
    window.addEventListener('resize', handleResize);
    onUnmounted(() => window.removeEventListener('resize', handleResize));
});

const toggleNotifications = () => {
    notificationsOpen.value = !notificationsOpen.value;
    if (userMenuOpen.value) userMenuOpen.value = false;
};

const toggleFullscreen = () => {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().then(() => {
            isFullscreen.value = true;
        }).catch(() => {});
    } else {
        document.exitFullscreen().then(() => {
            isFullscreen.value = false;
        }).catch(() => {});
    }
};

const toggleTheme = () => {
    isDark.value = !isDark.value;
    if (isDark.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
};

// Badge value resolver
const getBadge = (child) => {
    if (child.badgeKey === 'waUnread') return waUnreadCount.value;
    return 0;
};

onUnmounted(() => {
    if (waUnreadInterval) clearInterval(waUnreadInterval);
});

</script>

<template>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 transition-colors duration-300">
        <!-- Mobile sidebar backdrop -->
        <div 
            v-if="sidebarOpen" 
            class="fixed inset-0 z-40 bg-slate-900/80 backdrop-blur-sm lg:hidden transition-opacity duration-300 print:hidden"
            @click="closeSidebar"
        />

        <!-- Mobile sidebar -->
        <Transition
            enter-active-class="transition ease-in-out duration-300 transform"
            enter-from-class="-translate-x-full"
            enter-to-class="translate-x-0"
            leave-active-class="transition ease-in-out duration-300 transform"
            leave-from-class="translate-x-0"
            leave-to-class="-translate-x-full"
        >
            <div 
                v-if="sidebarOpen"
                class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-950 border-r border-white/5 lg:hidden print:hidden"
            >
                <div class="flex h-16 shrink-0 items-center justify-between px-6 border-b border-white/5 bg-slate-950">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-900 border border-white/10 shadow-lg shadow-blue-500/20 overflow-hidden">
                            <img :src="$page.props.company?.logo || '/images/jicos.png'" alt="Logo" class="w-full h-full object-cover" />
                        </div>
                        <div class="flex flex-col">
                            <span class="text-xl font-black tracking-tighter text-white">{{ $page.props.company?.name || 'JICOS' }}</span>
                            <span class="text-[8px] font-bold text-slate-400 uppercase tracking-[0.2em] -mt-1">Manufacturing System</span>
                        </div>
                    </div>
                    <button @click="closeSidebar" class="text-slate-400 hover:text-white transition-colors">
                        <XMarkIcon class="w-6 h-6" />
                    </button>
                </div>
                <nav class="flex flex-1 flex-col px-4 py-4 overflow-y-auto bg-slate-950">
                    <ul class="space-y-1">
                        <li v-for="item in filteredNavigation" :key="item.name">
                            <template v-if="item.children">
                                <button
                                    @click="toggleMenu(item.name)"
                                    class="group flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200"
                                    :class="item.current 
                                        ? 'bg-slate-900 text-white shadow-lg shadow-blue-500/10' 
                                        : 'text-slate-400 hover:text-white hover:bg-slate-900'"
                                >
                                    <component :is="item.icon" class="h-5 w-5 shrink-0" />
                                    {{ item.name }}
                                    <ChevronDownIcon 
                                        class="ml-auto h-4 w-4 transition-transform duration-200"
                                        :class="expandedMenus[item.name] ? 'rotate-180' : ''"
                                    />
                                </button>
                                <Transition
                                    enter-active-class="transition ease-out duration-200"
                                    enter-from-class="opacity-0 -translate-y-1"
                                    enter-to-class="opacity-100 translate-y-0"
                                    leave-active-class="transition ease-in duration-150"
                                    leave-from-class="opacity-100 translate-y-0"
                                    leave-to-class="opacity-0 -translate-y-1"
                                >
                                    <ul v-if="expandedMenus[item.name]" class="mt-1 space-y-1 pl-4">
                                        <li v-for="child in item.children" :key="child.name">
                                            <component
                                                :is="child.target ? 'a' : Link"
                                                :href="child.href"
                                                :target="child.target"
                                                class="group flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-slate-400 hover:bg-slate-900 hover:text-white transition-colors"
                                            >
                                                <component :is="child.icon" v-if="child.icon" class="h-4 w-4 shrink-0 transition-colors group-hover:text-white" />
                                                <span :class="child.icon ? '' : 'pl-7'">{{ child.name }}</span>
                                                <span v-if="getBadge(child)" class="ml-auto inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold text-white bg-red-500 rounded-full min-w-[18px] shadow-lg shadow-red-500/30 animate-pulse">{{ getBadge(child) }}</span>
                                            </component>
                                        </li>
                                    </ul>
                                </Transition>
                            </template>
                            <template v-else>
                                    <Link
                                    :href="item.href"
                                    class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200"
                                    :class="item.current 
                                        ? 'bg-gradient-to-r from-blue-600/20 to-purple-600/20 text-slate-900 dark:text-white border border-blue-500/30' 
                                        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white'"
                                >
                                    <component :is="item.icon" class="h-5 w-5 shrink-0" />
                                    {{ item.name }}
                                </Link>
                            </template>
                        </li>
                    </ul>
                </nav>
            </div>
        </Transition>

        <!-- Desktop sidebar -->
        <div 
            class="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:flex-col transition-all duration-300 print:hidden"
            :class="collapsed ? 'lg:w-20' : 'lg:w-64'"
        >
            <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-slate-950 border-r border-white/5 transition-all duration-300 relative">
                <!-- Sidebar Branding Glow -->
                <div class="absolute top-0 right-0 w-[150px] h-[150px] bg-cyan-500/5 rounded-full blur-[60px] pointer-events-none"></div>
                <div class="absolute bottom-0 left-0 w-[150px] h-[150px] bg-blue-500/5 rounded-full blur-[60px] pointer-events-none"></div>
                <div class="flex h-16 shrink-0 items-center border-b border-white/5 transition-all duration-300 relative overflow-hidden" :class="collapsed ? 'justify-center px-0' : 'px-6'">
                    <!-- Neon Line at bottom of header -->
                    <div class="absolute bottom-0 left-0 w-full h-[1px] bg-gradient-to-r from-transparent via-cyan-500 to-transparent opacity-30"></div>
                    
                    <div class="flex items-center gap-3 relative z-10">
                        <div class="group w-10 h-10 rounded-xl bg-slate-900 border border-white/10 flex items-center justify-center shadow-[0_0_15px_rgba(6,182,212,0.3)] shrink-0 transition-all duration-300 hover:shadow-[0_0_25px_rgba(6,182,212,0.6)] hover:border-cyan-500/50 overflow-hidden">
                             <!-- Logo Image will be placed here -->
                             <img :src="$page.props.company?.logo || '/images/jicos.png'" alt="Logo" class="w-full h-full object-cover" />
                        </div>
                        <div v-show="!collapsed" class="transition-opacity duration-200" :class="collapsed ? 'opacity-0 w-0 overflow-hidden' : 'opacity-100'">
                            <span class="text-4xl font-black italic tracking-[0.2em] text-transparent bg-clip-text bg-gradient-to-b from-white to-cyan-400 whitespace-nowrap drop-shadow-[0_0_10px_rgba(6,182,212,0.6)]" style="font-family: 'Segoe UI', sans-serif;">{{ $page.props.company?.name || 'JICOS' }}</span>
                        </div>
                    </div>
                </div>
                <nav class="flex flex-1 flex-col px-4">
                    <ul class="space-y-1">
                        <li v-for="item in filteredNavigation" :key="item.name">
                            <template v-if="item.children">
                                <div class="relative">
                                    <button
                                        @click="collapsed ? (expandedMenus[item.name] = !expandedMenus[item.name]) : toggleMenu(item.name)"
                                        class="group flex w-full items-center gap-3 rounded-xl py-2.5 text-sm font-medium transition-all duration-300 relative overflow-hidden"
                                        :class="[
                                            item.current 
                                                ? 'text-cyan-400 bg-cyan-950/30 border border-cyan-500/30 shadow-[0_0_15px_rgba(6,182,212,0.15)]' 
                                                : 'text-slate-400 hover:text-cyan-300 hover:bg-slate-800/50',
                                            collapsed ? 'justify-center px-0' : 'px-3'
                                        ]"
                                    >
                                        <div v-if="item.current" class="absolute inset-0 bg-gradient-to-r from-cyan-500/10 to-transparent opacity-50"></div>
                                        <div v-if="item.current" class="absolute left-0 top-0 bottom-0 w-1 bg-cyan-500 shadow-[0_0_10px_#06b6d4]"></div>

                                        <component 
                                            :is="item.icon" 
                                            class="h-5 w-5 shrink-0 transition-all duration-300 relative z-10"
                                            :class="item.current ? 'text-cyan-400 drop-shadow-[0_0_8px_rgba(34,211,238,0.8)]' : 'group-hover:text-cyan-400 group-hover:drop-shadow-[0_0_5px_rgba(34,211,238,0.5)]'" 
                                        />
                                        <template v-if="!collapsed">
                                            <span class="relative z-10">{{ item.name }}</span>
                                            <ChevronDownIcon 
                                                class="ml-auto h-4 w-4 transition-transform duration-200 relative z-10"
                                                :class="[
                                                    expandedMenus[item.name] ? 'rotate-180' : '',
                                                    item.current ? 'text-cyan-500' : ''
                                                ]"
                                            />
                                        </template>
                                    </button>
                                    
                                    <!-- Collapsed popover menu for children -->
                                    <div v-if="collapsed && expandedMenus[item.name]" class="absolute left-full top-0 ml-2 w-48 rounded-xl bg-slate-900 border border-white/10 shadow-xl z-50 py-1">
                                        <div class="px-4 py-2 text-xs font-semibold text-slate-500 border-b border-white/5 mb-1">
                                            {{ item.name }}
                                        </div>
                                        <component
                                            v-for="child in item.children" 
                                            :key="child.name"
                                            :is="child.target ? 'a' : Link"
                                            :href="child.href"
                                            :target="child.target"
                                            class="flex items-center gap-3 px-4 py-2 text-sm text-slate-400 hover:bg-slate-800 hover:text-white transition-colors group"
                                        >
                                            <component :is="child.icon" v-if="child.icon" class="h-4 w-4 shrink-0 transition-colors group-hover:text-white" />
                                            {{ child.name }}
                                        </component>
                                    </div>

                                    <Transition
                                        v-if="!collapsed"
                                        enter-active-class="transition ease-out duration-200"
                                        enter-from-class="opacity-0 -translate-y-1"
                                        enter-to-class="opacity-100 translate-y-0"
                                        leave-active-class="transition ease-in duration-150"
                                        leave-from-class="opacity-100 translate-y-0"
                                        leave-to-class="opacity-0 -translate-y-1"
                                    >
                                        <ul v-if="expandedMenus[item.name]" class="mt-1 space-y-1 pl-4">
                                            <li v-for="child in item.children" :key="child.name">
                                                <component
                                                    :is="child.target ? 'a' : Link"
                                                    :href="child.href"
                                                    :target="child.target"
                                                    class="group flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-slate-400 hover:bg-slate-800/50 hover:text-white transition-colors"
                                                >
                                                    <component :is="child.icon" v-if="child.icon" class="h-4 w-4 shrink-0 transition-colors group-hover:text-white" />
                                                    <span :class="child.icon ? '' : 'pl-7'">{{ child.name }}</span>
                                                    <span v-if="getBadge(child)" class="ml-auto inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold text-white bg-red-500 rounded-full min-w-[18px] shadow-lg shadow-red-500/30 animate-pulse">{{ getBadge(child) }}</span>
                                                </component>
                                            </li>
                                        </ul>
                                    </Transition>
                                </div>
                            </template>
                            <template v-else>
                                <Link
                                    :href="item.href"
                                    class="group flex items-center gap-3 rounded-xl py-2.5 text-sm font-medium transition-all duration-300 relative overflow-hidden"
                                    :class="[
                                        item.current 
                                            ? 'text-cyan-400 bg-cyan-950/30 border border-cyan-500/30' 
                                            : 'text-slate-400 hover:text-cyan-300 hover:bg-slate-800/50',
                                        collapsed ? 'justify-center px-0' : 'px-3'
                                    ]"
                                >
                                    <div v-if="item.current" class="absolute inset-0 bg-gradient-to-r from-cyan-500/10 to-transparent opacity-50"></div>
                                    <div v-if="item.current" class="absolute left-0 top-0 bottom-0 w-1 bg-cyan-500 shadow-[0_0_10px_#06b6d4]"></div>

                                    <component 
                                        :is="item.icon" 
                                        class="h-5 w-5 shrink-0 transition-all duration-300 relative z-10"
                                        :class="item.current ? 'text-cyan-400 drop-shadow-[0_0_8px_rgba(34,211,238,0.8)]' : 'group-hover:text-cyan-400 group-hover:drop-shadow-[0_0_5px_rgba(34,211,238,0.5)]'"
                                    />
                                    <span v-if="!collapsed" class="relative z-10">{{ item.name }}</span>
                                </Link>
                            </template>
                        </li>
                    </ul>
                </nav>
                <div class="border-t border-white/5 p-4" :class="collapsed ? 'flex justify-center' : ''">
                    <div class="flex items-center gap-3 rounded-xl bg-slate-900 border border-white/5 p-3 transition-all duration-300 shadow-sm" :class="collapsed ? 'justify-center px-0 bg-transparent border-0 shadow-none' : ''">
                        <img 
                            v-if="$page.props.auth.user?.profile_photo_path" 
                            :src="'/storage/' + $page.props.auth.user.profile_photo_path" 
                            alt="User" 
                            class="w-10 h-10 rounded-full object-cover border border-white/10 shadow-lg"
                        />
                        <div v-else class="w-10 h-10 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shrink-0 border border-white/10 shadow-lg">
                            <span class="text-sm font-bold text-white">{{ $page.props.auth.user?.name?.charAt(0).toUpperCase() || 'U' }}</span>
                        </div>
                        <div v-if="!collapsed" class="flex-1 min-w-0 transition-opacity duration-200">
                            <p class="text-sm font-medium text-white truncate">{{ $page.props.auth.user?.name || 'User' }}</p>
                            <p class="text-xs text-slate-500 truncate">{{ $page.props.auth.user?.email || '' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <div class="transition-all duration-300" :class="collapsed ? 'lg:pl-20' : 'lg:pl-64'">
            <!-- Top bar -->
            <div class="sticky top-0 z-40 lg:z-[60] flex h-16 shrink-0 items-center gap-x-4 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 transition-colors duration-300 px-4 shadow-sm dark:shadow-lg sm:gap-x-6 sm:px-6 lg:px-8 relative pointer-events-auto print:hidden">
                <!-- Futuristic Background Animation -->
                <TechnoHeaderBg />
                
                <button 
                    type="button" 
                    class="-m-2.5 p-2.5 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white lg:hidden relative z-10"
                    @click="sidebarOpen = true"
                >
                    <Bars3Icon class="h-6 w-6" />
                </button>

                <!-- Desktop Sidebar Toggle -->
                <button 
                    type="button" 
                    class="hidden lg:block -ml-4 p-2.5 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition-colors relative z-10"
                    @click="toggleDesktopSidebar"
                >
                    <Bars3BottomLeftIcon class="h-6 w-6 transform transition-transform duration-300" :class="collapsed ? 'rotate-180' : ''" />
                </button>

                <!-- Separator -->
                <div class="h-6 w-px bg-slate-200 dark:bg-slate-700 lg:hidden relative z-10" />

                <div class="flex flex-1 items-center gap-x-4 self-stretch lg:gap-x-6 relative z-10">
                    <!-- Tech Date Display -->
                    <div class="flex flex-1 items-center justify-center lg:justify-start">
                        <div class="hidden md:flex flex-row items-center lg:items-start gap-3 group">
                            <!-- Digital Icon -->
                            <div class="h-8 w-1 bg-gradient-to-b from-cyan-400 to-blue-600 rounded-full animate-pulse shadow-[0_0_10px_#06b6d4]"></div>
                            
                            <!-- Date with Neon Effect -->
                            <div class="flex flex-col justify-center h-full">
                                <div class="text-lg md:text-xl font-bold tracking-widest text-cyan-400 dark:text-cyan-400" 
                                     style="font-family: 'Orbitron', sans-serif; text-shadow: 0 0 10px rgba(34, 211, 238, 0.6);">
                                    {{ currentDate || 'LOADING SYSTEM...' }}
                                </div>
                                <div class="h-[1px] w-full bg-gradient-to-r from-cyan-500/50 to-transparent mt-1"></div>
                            </div>
                        </div>
                    </div>

                <!-- Right Side Actions (Notifications & Profile) -->
                <div class="flex items-center gap-x-4 lg:gap-x-6 ml-auto relative z-50">
                        <!-- PWA Install Button -->
                        <button 
                            v-if="canInstall && !isInstalled"
                            @click="installApp"
                            class="relative -m-2.5 p-2.5 text-emerald-400 hover:text-emerald-300 transition-colors group"
                            title="Install App"
                        >
                            <span class="absolute inset-0 flex items-center justify-center">
                                <span class="animate-ping absolute h-8 w-8 rounded-full bg-emerald-400 opacity-30"></span>
                            </span>
                            <ArrowDownOnSquareIcon class="h-6 w-6 relative z-10" />
                        </button>

                        <!-- Notifications -->
                        <div class="relative">
                            <!-- Fullscreen Toggle -->
                            <button 
                                type="button" 
                                @click="toggleFullscreen"
                                class="relative -m-2.5 p-2.5 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition-colors mr-2"
                                title="Toggle Fullscreen"
                            >
                                <component :is="isFullscreen ? ArrowsPointingInIcon : ArrowsPointingOutIcon" class="h-6 w-6" />
                            </button>

                            <!-- Theme Toggle -->
                            <button 
                                type="button" 
                                @click="toggleTheme"
                                class="relative -m-2.5 p-2.5 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition-all mr-2"
                                :title="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
                            >
                                <SunIcon v-if="isDark" class="h-6 w-6" />
                                <MoonIcon v-else class="h-6 w-6" />
                            </button>

                            <button 
                                type="button" 
                                @click="toggleNotifications"
                                class="relative -m-2.5 p-2.5 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition-colors"
                            >
                                <BellIcon class="h-6 w-6" />
                                <span v-if="unreadCount > 0" class="absolute top-1.5 right-1.5 flex h-2.5 w-2.5">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-red-500"></span>
                                </span>
                            </button>

                            <Transition
                                enter-active-class="transition ease-out duration-100"
                                enter-from-class="transform opacity-0 scale-95"
                                enter-to-class="transform opacity-100 scale-100"
                                leave-active-class="transition ease-in duration-75"
                                leave-from-class="transform opacity-100 scale-100"
                                leave-to-class="transform opacity-0 scale-95"
                            >
                                <div 
                                    v-if="notificationsOpen"
                                    class="absolute right-0 mt-2 w-80 origin-top-right rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-xl ring-1 ring-black ring-opacity-5 focus:outline-none z-50"
                                >
                                    <div class="p-4 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center">
                                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Notifications</h3>
                                        <Link href="/notifications" class="text-xs text-blue-600 dark:text-blue-400 hover:underline">View All</Link>
                                    </div>
                                    <div class="max-h-96 overflow-y-auto">
                                        <div v-if="recentNotifications.length > 0">
                                            <div 
                                                v-for="notification in recentNotifications" 
                                                :key="notification.id"
                                                class="px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-700/50 border-b border-slate-100 dark:border-slate-700/50 last:border-0"
                                            >
                                                <p class="text-sm text-slate-900 dark:text-white font-medium">{{ notification.data.title }}</p>
                                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ notification.data.message }}</p>
                                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-2">{{ new Date(notification.created_at).toLocaleDateString() }}</p>
                                            </div>
                                        </div>
                                        <div v-else class="px-4 py-8 text-center text-slate-400 dark:text-slate-500 text-sm">
                                            No new notifications
                                        </div>
                                    </div>
                                    <div class="p-2 border-t border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 rounded-b-xl">
                                        <Link 
                                            href="/notifications/mark-all-read" 
                                            method="post" 
                                            as="button"
                                            class="w-full text-center text-xs text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white py-1"
                                        >
                                            Mark all as read
                                        </Link>
                                    </div>
                                </div>
                            </Transition>
                        </div>

                        <!-- Separator -->
                        <div class="hidden lg:block lg:h-6 lg:w-px lg:bg-slate-200 dark:lg:bg-slate-700" />

                        <!-- Profile dropdown -->
                        <div class="relative">
                            <button 
                                @click="userMenuOpen = !userMenuOpen"
                                class="flex items-center gap-3 rounded-xl p-1.5 hover:bg-slate-100 dark:hover:bg-slate-800/50 transition-colors"
                            >
                                <img 
                                    v-if="$page.props.auth.user?.profile_photo_path" 
                                    :src="'/storage/' + $page.props.auth.user.profile_photo_path" 
                                    alt="User" 
                                    class="w-8 h-8 rounded-full object-cover"
                                />
                                <div v-else class="w-8 h-8 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center">
                                    <span class="text-xs font-bold text-white">{{ $page.props.auth.user?.name?.charAt(0).toUpperCase() || 'U' }}</span>
                                </div>
                                <span class="hidden lg:block text-sm font-medium text-slate-700 dark:text-white">{{ $page.props.auth.user?.name || 'User' }}</span>
                                <ChevronDownIcon class="hidden lg:block h-4 w-4 text-slate-400" />
                            </button>
                            
                            <Transition
                                enter-active-class="transition ease-out duration-100"
                                enter-from-class="transform opacity-0 scale-95"
                                enter-to-class="transform opacity-100 scale-100"
                                leave-active-class="transition ease-in duration-75"
                                leave-from-class="transform opacity-100 scale-100"
                                leave-to-class="transform opacity-0 scale-95"
                            >
                                <div 
                                    v-if="userMenuOpen"
                                    class="absolute right-0 mt-2 w-48 origin-top-right rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-xl ring-1 ring-black ring-opacity-5 focus:outline-none"
                                >
                                    <div class="py-1">
                                        <Link href="/profile" class="flex items-center gap-2 px-4 py-2 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/50">
                                            <UserCircleIcon class="h-4 w-4" />
                                            Your Profile
                                        </Link>
                                        <Link href="/settings" class="flex items-center gap-2 px-4 py-2 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/50">
                                            <Cog6ToothIcon class="h-4 w-4" />
                                            Settings
                                        </Link>
                                        <hr class="my-1 border-slate-100 dark:border-slate-700" />
                                        <Link href="/logout" method="post" as="button" class="flex items-center gap-2 px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-slate-100 dark:hover:bg-slate-700/50 w-full text-left">
                                            <ArrowRightStartOnRectangleIcon class="h-4 w-4" />
                                            Sign out
                                        </Link>
                                    </div>
                                </div>
                            </Transition>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Page content -->
            <div class="pt-8 pb-24 lg:pb-8 px-4 sm:px-6 lg:px-8 relative">


            <!-- Page header -->
            <div v-if="renderHeader" class="mb-8 print:hidden">
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ title }}</h1>
            </div>

            <!-- Page Content -->
            <main>
                <slot />
            </main>

            <div class="fixed bottom-0 left-0 right-0 z-[70] lg:hidden print:hidden">
                <div class="mx-auto max-w-screen-sm px-3 pb-3">
                    <div class="grid grid-cols-5 gap-2 rounded-2xl border border-white/10 bg-slate-950/90 backdrop-blur shadow-xl shadow-black/30 px-2 py-2">
                        <template v-for="item in mobileNavItems" :key="item.name">
                            <button
                                v-if="item.action === 'openMenu'"
                                type="button"
                                @click="sidebarOpen = true"
                                class="flex flex-col items-center justify-center gap-1 rounded-xl px-2 py-2 text-[10px] font-bold tracking-widest uppercase transition"
                                :class="'text-slate-300 hover:bg-white/5'"
                            >
                                <component :is="item.icon" class="h-6 w-6" />
                                <span class="truncate max-w-full">{{ item.name }}</span>
                            </button>

                            <Link
                                v-else
                                :href="item.href"
                                class="flex flex-col items-center justify-center gap-1 rounded-xl px-2 py-2 text-[10px] font-bold tracking-widest uppercase transition"
                                :class="isMobileNavActive(item.href) ? 'bg-cyan-500/10 text-cyan-300 border border-cyan-500/20' : 'text-slate-300 hover:bg-white/5'"
                            >
                                <component :is="item.icon" class="h-6 w-6" />
                                <span class="truncate max-w-full">{{ item.name }}</span>
                            </Link>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Flash Notifications -->
            <div 
                v-if="showFlash" 
                class="fixed bottom-4 right-4 z-[100] max-w-sm w-full bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-100 dark:border-slate-700 overflow-hidden transform transition-all duration-300 ease-in-out"
                :class="showFlash ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
            >
                <div class="p-4 flex items-start gap-3">
                    <div class="flex-shrink-0">
                        <CheckCircleIcon v-if="flashSuccess" class="h-6 w-6 text-emerald-500" />
                        <ShieldExclamationIcon v-if="flashError" class="h-6 w-6 text-red-500" />
                    </div>
                    <div class="flex-1 pt-0.5">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                            {{ flashSuccess ? 'Berhasil' : 'Perhatian' }}
                        </h3>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 font-medium leading-relaxed">
                            {{ flashSuccess || flashError }}
                        </p>
                    </div>
                    <button @click="showFlash = false" class="flex-shrink-0 text-slate-400 hover:text-slate-500 dark:hover:text-slate-300">
                        <XMarkIcon class="h-5 w-5" />
                    </button>
                </div>
                <div class="h-1 w-full bg-slate-100 dark:bg-slate-700">
                    <div 
                        class="h-full transition-all duration-[5000ms] ease-linear w-0"
                        :class="[flashSuccess ? 'bg-emerald-500' : 'bg-red-500', showFlash ? 'w-full' : 'w-0']"
                    ></div>
                </div>
            </div>
        </div>
    </div>
</div>
</template>
