@php
$features = [
    [
        'title' => 'Sales & CRM',
        'icon' => 'CurrencyDollarIcon',
        'description' => 'Maximize revenue with integrated sales and customer relationship management.',
        'color' => 'from-blue-500 to-cyan-400',
        'items' => [
            'WhatsApp Center Integration',
            'Sales Hub Dashboard',
            'Quotations & Sales Orders',
            'Delivery Orders & Invoices',
            'CRM Intelligence & Leads',
            'AI PO Extractor (Auto-Order)',
        ]
    ],
    [
        'title' => 'Purchasing',
        'icon' => 'ShoppingCartIcon',
        'description' => 'Streamline procurement and manage supplier relationships efficiently.',
        'color' => 'from-emerald-500 to-teal-400',
        'items' => [
            'Procurement Operations',
            'Supplier Management',
            'Purchase Requests & Orders',
            'Goods Receipts (QR Support)',
            'AI Generated Receipt (OCR)',
            'Purchase Invoices & Returns',
        ]
    ],
    [
        'title' => 'Inventory',
        'icon' => 'CubeIcon',
        'description' => 'Real-time stock visibility and advanced warehouse control.',
        'color' => 'from-orange-500 to-amber-400',
        'items' => [
            'Inventory Command Center',
            'Multi-Warehouse Management',
            'Stock Movements & History',
            'Digital Stock Opname',
            'Unit Management',
            'Safety Stock Alerts',
        ]
    ],
    [
        'title' => 'Manufacturing',
        'icon' => 'CpuChipIcon',
        'description' => 'Smart production planning and shop floor control.',
        'color' => 'from-indigo-500 to-purple-400',
        'items' => [
            'Intelligence Hub (OEE)',
            'Bill of Materials (BOM)',
            'Work Orders (SPK)',
            'Production Routing',
            'Machine Management',
            'Subcontract Orders',
        ]
    ],
    [
        'title' => 'Quality Control',
        'icon' => 'CheckBadgeIcon',
        'description' => 'Ensure product excellence with integrated QA checks.',
        'color' => 'from-rose-500 to-pink-400',
        'items' => [
            'Incoming Material Inspection',
            'In-Process Quality Checks',
            'Quality Checklists',
            'Defect Tracking',
            'Inspection Certificates',
        ]
    ],
    [
        'title' => 'Maintenance',
        'icon' => 'WrenchScrewdriverIcon',
        'description' => 'Keep your assets running with preventive maintenance.',
        'color' => 'from-gray-600 to-gray-400',
        'items' => [
            'Preventive Schedules',
            'Breakdown Logging',
            'Spareparts Inventory',
            'Technician Assignment',
            'MTBF & MTTR Analysis',
        ]
    ],
    [
        'title' => 'Logistics',
        'icon' => 'TruckIcon',
        'description' => 'Optimize fleet utilization and on-time deliveries.',
        'color' => 'from-cyan-600 to-blue-500',
        'items' => [
            'Logistics Hub Dashboard',
            'Delivery Route Planning',
            'Vehicle Fleet Management',
            'Driver Assignment',
            'Fuel Monitoring',
        ]
    ],
    [
        'title' => 'Finance & Costing',
        'icon' => 'BanknotesIcon',
        'description' => 'Accurate financial tracking and production costing.',
        'color' => 'from-yellow-500 to-amber-500',
        'items' => [
            'General Ledger',
            'Profit & Loss Reports',
            'AP & AR Monitoring',
            'Production Costing (HPP)',
            'Overhead Allocation',
            'Profitability Analytics',
        ]
    ],
    [
        'title' => 'Human Resources',
        'icon' => 'UsersIcon',
        'description' => 'Manage your workforce, attendance, and payroll.',
        'color' => 'from-pink-500 to-rose-400',
        'items' => [
            'Employee Directory',
            'Digital Attendance',
            'Automated Payroll',
            'Shift Management',
            'Performance Tracking',
        ]
    ],
     [
        'title' => 'System & Settings',
        'icon' => 'Cog6ToothIcon',
        'description' => 'Configure the system to match your business rules.',
        'color' => 'from-slate-500 to-slate-400',
        'items' => [
            'User & Role Management',
            'Approval Workflows',
            'WhatsApp Bot Config',
            'Activity Logs (Audit)',
            'Database Backup/Restore',
            'AI Configuration',
        ]
    ],
     [
        'title' => 'External Portals',
        'icon' => 'GlobeAltIcon',
        'description' => 'Secure access for your suppliers and customers.',
        'color' => 'from-violet-500 to-fuchsia-400',
        'items' => [
            'Supplier Portal (Self-Service)',
            'Customer Portal (Order Tracking)',
            'Digital Invoice Submission',
            'Real-time Status Updates',
            'Secure Document Sharing',
        ]
    ],
];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Features - {{ config('app.name', 'USICS ERP') }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    
    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Instrument Sans', 'sans-serif'],
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'spin-slow': 'spin 12s linear infinite',
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-20px)' },
                        }
                    }
                }
            }
        }
    </script>
    <style>
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #0f172a; 
        }
        ::-webkit-scrollbar-thumb {
            background: #334155; 
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #475569; 
        }

        .glass-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .feature-card:hover .icon-bg {
            transform: scale(1.1) rotate(5deg);
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.5);
            border-color: rgba(255, 255, 255, 0.1);
        }

        .hero-pattern {
            background-image: radial-gradient(rgba(255, 255, 255, 0.1) 1px, transparent 1px);
            background-size: 30px 30px;
        }
    </style>
    
    <!-- Heroicons via CDN (since we aren't compiling assets here to be safe) -->
    <!-- Using SVG strings directly in loop for simplicity and performance without JS dependencies -->
</head>
<body class="bg-slate-950 text-slate-200 antialiased overflow-x-hidden selection:bg-cyan-500 selection:text-white">

    <!-- Background Effects -->
    <div class="fixed inset-0 z-0 pointer-events-none">
        <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-blue-600/10 rounded-full blur-[100px] animate-pulse-slow"></div>
        <div class="absolute bottom-0 left-0 w-[500px] h-[500px] bg-purple-600/10 rounded-full blur-[100px] animate-pulse-slow" style="animation-delay: 2s;"></div>
        <div class="absolute inset-0 hero-pattern opacity-20"></div>
    </div>

    <!-- Navigation -->
    <nav class="fixed top-0 w-full z-50 glass-card border-b border-white/5 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <div class="flex-shrink-0 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center shadow-lg shadow-cyan-500/20">
                        <span class="text-white font-bold text-lg">J</span>
                    </div>
                    <span class="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-white to-slate-400">USICS ERP</span>
                </div>
                
                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="{{ route('welcome') }}" class="text-slate-400 hover:text-white transition-colors text-sm font-medium">Home</a>
                    <a href="#" class="text-white font-medium text-sm border-b-2 border-cyan-500 pb-0.5">Features</a>
                    <a href="https://cloud.laravel.com" target="_blank" class="text-slate-400 hover:text-white transition-colors text-sm font-medium">Deploy</a>
                </div>

                <!-- Action Button -->
                <div class="flex items-center gap-4">
                    <a href="{{ route('login') }}" class="text-slate-400 hover:text-white transition-colors text-sm font-medium hidden sm:block">Log in</a>
                    <a href="{{ route('register') }}" class="px-4 py-2 bg-white text-slate-950 rounded-lg text-sm font-bold hover:bg-cyan-50 transition-all transform hover:scale-105 shadow-lg shadow-white/10">
                        Get Started
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="relative z-10 pt-32 pb-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Hero Section -->
            <div class="text-center max-w-3xl mx-auto mb-20 animate-float">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-cyan-950/50 border border-cyan-500/30 text-cyan-400 text-xs font-semibold mb-6">
                    <span class="relative flex h-2 w-2">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-cyan-400 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-2 w-2 bg-cyan-500"></span>
                    </span>
                    POWERED BY ARTIFICIAL INTELLIGENCE
                </div>
                <h1 class="text-5xl md:text-6xl font-black text-white tracking-tight mb-6 leading-tight">
                    The Operating System for <br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 via-blue-500 to-purple-600">Modern Manufacturing</span>
                </h1>
                <p class="text-lg text-slate-400 mb-10 leading-relaxed max-w-2xl mx-auto">
                    A comprehensive, modular ERP designed to streamline your entire production lifecycle. From raw materials to finished goods, USICS brings clarity to chaos.
                </p>
                <div class="flex justify-center gap-4">
                     <a href="#modules" class="px-8 py-3 bg-gradient-to-r from-cyan-600 to-blue-600 text-white rounded-xl font-bold hover:shadow-lg hover:shadow-cyan-500/30 transition-all transform hover:-translate-y-1">
                        Explore Modules
                    </a>
                    <a href="https://youtu.be/dummy-video" target="_blank" class="px-8 py-3 glass-card text-white rounded-xl font-bold hover:bg-white/10 transition-all flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Watch Demo
                    </a>
                </div>
            </div>

            <!-- Features Grid -->
            <div id="modules" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach ($features as $feature)
                    <div class="group feature-card glass-card rounded-2xl p-8 transition-all duration-300 relative overflow-hidden">
                        <!-- Glow Effect -->
                        <div class="absolute -right-20 -top-20 w-40 h-40 bg-gradient-to-br {{ $feature['color'] }} opacity-10 blur-[50px] group-hover:opacity-20 transition-opacity"></div>

                        <!-- Icon -->
                        <div class="icon-bg w-14 h-14 rounded-xl bg-gradient-to-br {{ $feature['color'] }} flex items-center justify-center mb-6 shadow-lg shadow-white/5 transition-transform duration-500 ease-out">
                            <!-- Simple SVG Icons mapping -->
                            @if ($feature['icon'] == 'CurrencyDollarIcon')
                                <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            @elseif ($feature['icon'] == 'ShoppingCartIcon')
                                <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                            @elseif ($feature['icon'] == 'CubeIcon')
                                <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                            @elseif ($feature['icon'] == 'CpuChipIcon')
                                <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" /></svg>
                            @elseif ($feature['icon'] == 'CheckBadgeIcon')
                                <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            @elseif ($feature['icon'] == 'WrenchScrewdriverIcon')
                                <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            @elseif ($feature['icon'] == 'TruckIcon')
                                <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" /></svg>
                             @elseif ($feature['icon'] == 'BanknotesIcon')
                                <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            @elseif ($feature['icon'] == 'UsersIcon')
                                <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                            @elseif ($feature['icon'] == 'GlobeAltIcon')
                                <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" /></svg>
                            @else
                                <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                            @endif
                        </div>

                        <!-- Card Content -->
                        <h3 class="text-2xl font-bold text-white mb-3 group-hover:text-cyan-400 transition-colors">{{ $feature['title'] }}</h3>
                        <p class="text-slate-400 mb-6 text-sm leading-relaxed">{{ $feature['description'] }}</p>

                        <!-- Feature List -->
                        <ul class="space-y-3">
                            @foreach ($feature['items'] as $item)
                                <li class="flex items-start gap-3 text-sm text-slate-300">
                                    <svg class="w-5 h-5 text-emerald-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    {{ $item }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>

            <!-- CTA Footer -->
            <div class="mt-20 text-center glass-card rounded-3xl p-12 relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r from-cyan-600/20 to-blue-600/20 pointer-events-none"></div>
                <h2 class="text-3xl font-bold text-white mb-4 relative z-10">Ready to transform your factory?</h2>
                <p class="text-slate-400 mb-8 max-w-xl mx-auto relative z-10">Join forward-thinking manufacturers who have optimized their production with USICS ERP.</p>
                <div class="flex justify-center gap-4 relative z-10">
                    <a href="{{ route('register') }}" class="px-8 py-3 bg-white text-slate-900 rounded-xl font-bold hover:bg-cyan-50 transition-colors">
                        Start Free Trial
                    </a>
                    <a href="#" class="px-8 py-3 border border-slate-600 text-white rounded-xl font-bold hover:bg-slate-800 transition-colors">
                        Contact Sales
                    </a>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="mt-12 text-center border-t border-slate-800 pt-8">
                <p class="text-slate-500 text-sm">&copy; {{ date('Y') }} USICS ERP. All rights reserved.</p>
            </div>
        </div>
    </div>

</body>
</html>
