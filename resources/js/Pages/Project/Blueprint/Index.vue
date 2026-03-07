<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import mermaid from 'mermaid';
import { 
    BookOpenIcon, 
    CpuChipIcon, 
    ShieldCheckIcon, 
    CircleStackIcon, 
    DocumentCheckIcon,
    BeakerIcon,
    ArrowPathIcon,
    PresentationChartLineIcon,
    ShareIcon,
    UsersIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    project: Object,
});

const activeTab = ref('overview');

const menuItems = [
    { id: 'overview', label: 'Executive Summary', icon: PresentationChartLineIcon },
    { id: 'manpower', label: 'Organization Chart', icon: UsersIcon },
    { id: 'flowchat', label: 'System Flow Chart', icon: ShareIcon },
    { id: 'bpd', label: 'Business Process (BPD)', icon: ArrowPathIcon },
    { id: 'frd', label: 'Functional Req (FRD)', icon: CpuChipIcon },
    { id: 'database', label: 'Database Structure', icon: CircleStackIcon },
    { id: 'security', label: 'Security Matrix', icon: ShieldCheckIcon },
    { id: 'integration', label: 'Integration Specs', icon: BookOpenIcon },
    { id: 'qc', label: 'Quality Control', icon: BeakerIcon },
    { id: 'uat', label: 'UAT Scenarios', icon: DocumentCheckIcon },
];

const content = {
    overview: {
        title: 'Executive Summary',
        subtitle: 'JICOS ERP Implementation for PT JIDOKA',
        body: `
            <div class="space-y-8 animate-fade-in-up">
                <!-- Hero Section / Vision -->
                <div class="relative overflow-hidden bg-gradient-to-br from-indigo-900/80 to-slate-900 p-8 rounded-2xl border border-indigo-500/30 shadow-2xl">
                    <div class="absolute top-0 right-0 -mt-10 -mr-10 w-40 h-40 bg-cyan-500/20 rounded-full blur-2xl"></div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="p-2 bg-indigo-500/20 rounded-lg text-indigo-300">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                            </div>
                            <h3 class="text-2xl font-black text-white tracking-tight">Visi Digitalisasi</h3>
                        </div>
                        <p class="text-slate-300 leading-relaxed text-lg max-w-3xl">
                            Mewujudkan transformasi total operasional <span class="text-white font-bold">PT JIDOKA</span> dari pencatatan manual/tradisional menuju ekosistem digital yang terintegrasi penuh. Sistem ini dirancang khusus untuk mendukung kepatuhan standar <span class="text-cyan-400 font-bold border-b border-cyan-400/30 pb-0.5">High-Precision Packaging</span> demi memenuhi tuntutan ketat industri otomotif (OEM) global.
                        </p>
                    </div>
                </div>

                <!-- Key Project Metrics -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-slate-800/60 backdrop-blur-md p-6 rounded-xl border border-slate-700/50 hover:border-cyan-500/30 transition-colors group">
                        <div class="flex justify-between items-start mb-4">
                            <div class="text-sm font-semibold text-slate-400 uppercase tracking-wider">Target Go-Live</div>
                            <div class="p-1.5 bg-green-500/10 text-green-400 rounded-md group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            </div>
                        </div>
                        <div class="text-3xl font-black text-white mb-1">1 April 2026</div>
                        <div class="text-xs text-green-400 font-medium flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse"></span> On Schedule
                        </div>
                    </div>

                    <div class="bg-slate-800/60 backdrop-blur-md p-6 rounded-xl border border-slate-700/50 hover:border-purple-500/30 transition-colors group">
                        <div class="flex justify-between items-start mb-4">
                            <div class="text-sm font-semibold text-slate-400 uppercase tracking-wider">Compliance Standard</div>
                            <div class="p-1.5 bg-purple-500/10 text-purple-400 rounded-md group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" /></svg>
                            </div>
                        </div>
                        <div class="text-3xl font-black text-white mb-1">IATF 16949</div>
                        <div class="text-xs text-slate-400 font-medium">Automotive Quality Management</div>
                    </div>

                    <div class="bg-slate-800/60 backdrop-blur-md p-6 rounded-xl border border-slate-700/50 hover:border-amber-500/30 transition-colors group">
                        <div class="flex justify-between items-start mb-4">
                            <div class="text-sm font-semibold text-slate-400 uppercase tracking-wider">System Modules</div>
                            <div class="p-1.5 bg-amber-500/10 text-amber-400 rounded-md group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                            </div>
                        </div>
                        <div class="text-3xl font-black text-white mb-1">8+ Core</div>
                        <div class="text-xs text-slate-400 font-medium">Fully Integrated Ecosystem</div>
                    </div>
                </div>

                <!-- Scope & Capabilities -->
                <div class="bg-[#0a0a1a] p-8 rounded-2xl border border-slate-800">
                    <h4 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
                        <svg class="w-5 h-5 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                        Main Capabilities & Scope
                    </h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-8">
                        <!-- Item 1 -->
                        <div class="flex gap-4">
                            <div class="mt-1 flex-shrink-0 w-8 h-8 rounded-full bg-blue-900/30 border border-blue-500/30 flex items-center justify-center text-blue-400 font-bold text-xs">01</div>
                            <div>
                                <h5 class="text-sm font-bold text-slate-200 mb-1">Traceability Lot End-to-End</h5>
                                <p class="text-xs text-slate-400 leading-relaxed">Pelacakan material 100% menggunakan sistem QR Code dari kedatangan (Receive) hingga pengiriman (Dispatch).</p>
                            </div>
                        </div>
                        <!-- Item 2 -->
                        <div class="flex gap-4">
                            <div class="mt-1 flex-shrink-0 w-8 h-8 rounded-full bg-emerald-900/30 border border-emerald-500/30 flex items-center justify-center text-emerald-400 font-bold text-xs">02</div>
                            <div>
                                <h5 class="text-sm font-bold text-slate-200 mb-1">Lean Manufacturing Execution</h5>
                                <p class="text-xs text-slate-400 leading-relaxed">Digitalisasi Work Order Sheet (WOS) & SPK untuk meminimalisir pemborosan waktu dan kertas di area produksi.</p>
                            </div>
                        </div>
                        <!-- Item 3 -->
                        <div class="flex gap-4">
                            <div class="mt-1 flex-shrink-0 w-8 h-8 rounded-full bg-amber-900/30 border border-amber-500/30 flex items-center justify-center text-amber-400 font-bold text-xs">03</div>
                            <div>
                                <h5 class="text-sm font-bold text-slate-200 mb-1">Quality Control Automation</h5>
                                <p class="text-xs text-slate-400 leading-relaxed">Sistem inspeksi langsung dan input mandiri (Self-Check Operator) untuk cegah lolosnya material cacat (NG).</p>
                            </div>
                        </div>
                        <!-- Item 4 -->
                        <div class="flex gap-4">
                            <div class="mt-1 flex-shrink-0 w-8 h-8 rounded-full bg-purple-900/30 border border-purple-500/30 flex items-center justify-center text-purple-400 font-bold text-xs">04</div>
                            <div>
                                <h5 class="text-sm font-bold text-slate-200 mb-1">Real-time Notification (WA / Email)</h5>
                                <p class="text-xs text-slate-400 leading-relaxed">Peringatan otomatis untuk persetujuan (Approval), Breakdown Mesin darurat, dan Minimum Stock pengadaan barang.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `
    },
    manpower: {
        title: 'Mapping Distribusi Manpower JRI',
        subtitle: '2025 Packaging Division Structure',
        isMermaid: true,
        body: `
            flowchart TD
                %% Styles
                classDef default fill:#1e293b,stroke:#94a3b8,color:#fff,stroke-width:1px;
                classDef root fill:#0f172a,stroke:#06b6d4,color:#22d3ee,stroke-width:2px;
                classDef dept fill:#1e293b,stroke:#f59e0b,color:#fbbf24,stroke-width:2px;
                classDef subteam fill:#0f172a,stroke:#64748b,color:#94a3b8,stroke-width:1px,stroke-dasharray: 5 5;

                ROOT[PACKAGING DIVISION]:::root
                
                %% Departments
                ROOT --> PURCH[PURCHASING, GA & HRD]:::dept
                ROOT --> MITSUBISHI[MITSUBISHI GROUP<br/>INTERNAL SALES]:::dept
                ROOT --> HONDA[HONDA GROUP<br/>INTERNAL SALES]:::dept
                ROOT --> OTHERS[OTHERS GROUP<br/>INTERNAL SALES]:::dept
                ROOT --> MKT[MKT & ENGINEERING<br/>DEVELOPMENT]:::dept
                ROOT --> ADMIN[ADMINISTRATION &<br/>FAKTUR PAJAK]:::dept
                
                %% Teams Purchasing
                PURCH --> P_CS[Control Stock Cons.<br/>Part & GA]:::subteam
                PURCH --> P_DR[Driver Team]:::subteam
                
                %% Teams Mitsubishi
                MITSUBISHI --> M_PP[Partition & Pad]:::subteam
                MITSUBISHI --> M_CB[Cart Box, RPD DLL]:::subteam
                
                %% Teams Honda
                HONDA --> H_IMP[Impraboard]:::subteam
                HONDA --> H_CB[Carton Box DLL]:::subteam
                
                %% Teams Others
                OTHERS --> O_SL[Sliter]:::subteam
                OTHERS --> O_CBL[Carton Box, Layer<br/>Partition DLL]:::subteam
        `,
        details: `
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-8">
                <!-- Group 1: Purchasing -->
                <div class="bg-[#0a0a1a]/50 border border-slate-800 rounded-xl p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-lg bg-blue-900/30 flex items-center justify-center text-blue-400 font-bold">HRD</div>
                        <div>
                            <h3 class="font-bold text-white text-sm">PURCHASING, GA & HRD</h3>
                            <p class="text-xs text-slate-400">Ely Susanti, Agus Supriyanto</p>
                        </div>
                    </div>
                    <div class="space-y-3 text-xs text-slate-300 pl-13">
                        <div class="pl-4 border-l-2 border-blue-900/50">
                            <strong class="text-blue-400 block mb-1">Control Stock & GA</strong>
                            <p>Ahmad Mulyana</p>
                        </div>
                        <div class="pl-4 border-l-2 border-blue-900/50">
                            <strong class="text-blue-400 block mb-1">Drivers</strong>
                            <p>Moh Chanifudin, Eci Nugraha, Rustam Nawawi, Yasin Bin Enin, Panji Agus P.</p>
                        </div>
                    </div>
                </div>

                <!-- Group 2: Mitsubishi -->
                <div class="bg-[#0a0a1a]/50 border border-slate-800 rounded-xl p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-lg bg-red-900/30 flex items-center justify-center text-red-400 font-bold">MITS</div>
                        <div>
                            <h3 class="font-bold text-white text-sm">MITSUBISHI GROUP</h3>
                            <p class="text-xs text-slate-400">Nanang Mulyana, Sarif Hidayat</p>
                        </div>
                    </div>
                    <div class="space-y-3 text-xs text-slate-300">
                        <div class="pl-4 border-l-2 border-red-900/50">
                            <strong class="text-red-400 block mb-1">Sub-Teams</strong>
                            <ul class="list-disc pl-4 space-y-1">
                                <li><strong>Partition & Pad:</strong> Suhoeru -> Sliter (Syahrir + PKL), Pad Stempel (M. A Rohman + PKL), DXC20/26 (New Operator + PKL)</li>
                                <li><strong>Cart Box:</strong> Tri Setiono -> PS823 & PS824 (Akbar Yana + PKL)</li>
                            </ul>
                        </div>
                        <div class="mt-4 pt-4 border-t border-slate-800">
                            <strong class="text-slate-500 block mb-2 text-[10px] uppercase tracking-wider">Key Customers</strong>
                            <p class="leading-relaxed">Echo Advace Technology, Fujitrans Logistics, C&B Indonesia, Leoco Indonesia, Mitsubishi Motors Krama Yudha Ind, SK Metalindo, United Steel Center, Bina Kemas Persada.</p>
                        </div>
                    </div>
                </div>

                <!-- Group 3: Honda -->
                <div class="bg-[#0a0a1a]/50 border border-slate-800 rounded-xl p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-lg bg-yellow-900/30 flex items-center justify-center text-yellow-400 font-bold">HND</div>
                        <div>
                            <h3 class="font-bold text-white text-sm">HONDA GROUP</h3>
                            <p class="text-xs text-slate-400">Aang Kunaepi, Andi Suhandi</p>
                        </div>
                    </div>
                    <div class="space-y-3 text-xs text-slate-300">
                        <div class="pl-4 border-l-2 border-yellow-900/50">
                            <strong class="text-yellow-400 block mb-1">Sub-Teams</strong>
                            <ul class="list-disc pl-4 space-y-1">
                                <li><strong>Impraboard:</strong> M Fahrul Rozy, New Operator, PKL</li>
                                <li><strong>Carton Box:</strong> M Ardiansyah, New Operator, PKL</li>
                            </ul>
                        </div>
                        <div class="mt-4 pt-4 border-t border-slate-800">
                            <strong class="text-slate-500 block mb-2 text-[10px] uppercase tracking-wider">Key Customers</strong>
                            <p class="leading-relaxed">Anzen Pakarindo, Honda Prospect Motor, LogisALL Global, NX Shoji, Sakae Riken, Sekisui Kasae, Mics Steel, Jaya Victori Cemerlang.</p>
                        </div>
                    </div>
                </div>

                <!-- Group 4: Others -->
                <div class="bg-[#0a0a1a]/50 border border-slate-800 rounded-xl p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-lg bg-emerald-900/30 flex items-center justify-center text-emerald-400 font-bold">OTH</div>
                        <div>
                            <h3 class="font-bold text-white text-sm">OTHERS GROUP</h3>
                            <p class="text-xs text-slate-400">Amur, Irwan S.</p>
                        </div>
                    </div>
                    <div class="space-y-3 text-xs text-slate-300">
                        <div class="pl-4 border-l-2 border-emerald-900/50">
                            <strong class="text-emerald-400 block mb-1">Sub-Teams</strong>
                            <ul class="list-disc pl-4 space-y-1">
                                <li><strong>Sliter:</strong> Firman Chani, Galih Sunarya</li>
                                <li><strong>Carton Box Layer:</strong> Khoirul Anam, New Operators (2), PKL (2)</li>
                            </ul>
                        </div>
                        <div class="mt-4 pt-4 border-t border-slate-800">
                            <strong class="text-slate-500 block mb-2 text-[10px] uppercase tracking-wider">Key Customers</strong>
                            <p class="leading-relaxed">Citra Plastik Makmur, Dharma Precision Parts, Kojima Auto Tech, Kyoraku Blowmolding, Nippon Konpo, Progress Diecast, Trustindo Mekatronics, Yuju Indonesia, Origin Durachem, Adharco Jaya Selaras.</p>
                        </div>
                    </div>
                </div>

                <!-- Specialized: MKT & Admin -->
                <div class="bg-[#0a0a1a]/50 border border-slate-800 rounded-xl p-6 lg:col-span-2 grid grid-cols-2 gap-4">
                     <div>
                        <div class="flex items-center gap-3 mb-2">
                             <div class="p-2 bg-purple-900/30 rounded text-purple-400"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg></div>
                             <div>
                                 <h3 class="font-bold text-white text-xs">MKT & ENGINEERING</h3>
                                 <p class="text-xs text-slate-400">Ahmad Rubangi</p>
                             </div>
                        </div>
                     </div>
                     <div>
                        <div class="flex items-center gap-3 mb-2">
                             <div class="p-2 bg-pink-900/30 rounded text-pink-400"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg></div>
                             <div>
                                 <h3 class="font-bold text-white text-xs">ADMIN & TAX</h3>
                                 <p class="text-xs text-slate-400">Ahmad Hasanudin</p>
                             </div>
                        </div>
                     </div>
                </div>
            </div>
        `
    },
    flowchat: {
        title: 'System Process Flow',
        subtitle: 'Inventory Flow with Customer Order',
        isMermaid: true,
        body: `
            graph TD
                %% Styles
                classDef default fill:#1e293b,stroke:#94a3b8,color:#fff,stroke-width:1px;
                classDef decision fill:#0f172a,stroke:#06b6d4,color:#22d3ee,stroke-width:2px;
                classDef terminal fill:#0f172a,stroke:#4ade80,color:#4ade80,stroke-width:2px,stroke-dasharray: 5 5;
                classDef document fill:#1e293b,stroke:#f59e0b,color:#fbbf24,stroke-width:1px;
                
                %% Nodes
                start([Customer Purchase Order]):::terminal --> SO[Sales Order]
                SO --> pop{POP Decision:<br/>Pick, Order, Produce?}:::decision
                
                %% Pick Branch
                pop -- Pick --> pick_ticket[Create 'Pick Ticket']:::document
                pick_ticket --> pick_item[Pick up items from<br/>current inventory]
                
                %% Order Branch
                pop -- Order --> det_order_qty[Determine Order Qty]
                det_order_qty --> create_po[Create Purchase Order<br/>and Send]:::document
                create_po --> receive_item[Receive Items]
                
                %% Produce Branch
                pop -- Produce --> det_prod_qty[Determine Quantity]
                det_prod_qty --> create_wo[Create Work Order]:::document
                create_wo --> manufacture[Assemble or<br/>Manufacture Items]
                
                %% Convergence
                pick_item --> box[Box and package<br/>the items]
                receive_item --> box
                manufacture --> box
                
                box --> docs[Create Delivery Order<br/>and Invoice]:::document
                docs --> ship([Ship to Customer]):::terminal
        `,
        details: `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-12 border-t border-slate-800 pt-8 bg-[#0a0a1a]/50 p-8 rounded-xl">
                <div class="space-y-4">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-2 bg-blue-900/30 rounded-lg text-blue-400">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                        </div>
                        <h3 class="text-lg font-bold text-white">INTERNAL SALES GROUP</h3>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="bg-slate-800/50 p-4 rounded-lg border border-slate-700/50">
                            <strong class="text-cyan-400 block mb-2 text-xs uppercase tracking-wider">Preparation</strong>
                            <ol class="list-decimal pl-4 space-y-1 text-sm text-slate-300">
                                <li>Membuat List Master Product (Finished Goods)</li>
                                <li>Membuat List Master Customer</li>
                            </ol>
                        </div>
                        <div class="bg-slate-800/50 p-4 rounded-lg border border-slate-700/50">
                            <strong class="text-cyan-400 block mb-2 text-xs uppercase tracking-wider">Regular Flow</strong>
                            <ol class="list-decimal pl-4 space-y-1 text-sm text-slate-300" start="3">
                                <li>Input Forecast Customer ke System</li>
                                <li>Input PO Customer ke System sebagai SO</li>
                                <li>Input Schedule Delivery ke System</li>
                                <li>Membuat Monthly Production Planning (MPP)</li>
                                <li>Membuat Work Order Sheet (WOS)</li>
                                <li>Input Output Produksi sebagai GR dari WOS</li>
                                <li>Membuat Delivery Order (Surat Jalan)</li>
                                <li>Membuat Invoice</li>
                            </ol>
                        </div>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-2 bg-emerald-900/30 rounded-lg text-emerald-400">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                        </div>
                        <h3 class="text-lg font-bold text-white">PURCHASING, GA & HRD TEAM</h3>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="bg-slate-800/50 p-4 rounded-lg border border-slate-700/50">
                            <strong class="text-emerald-400 block mb-2 text-xs uppercase tracking-wider">Preparation</strong>
                            <ol class="list-decimal pl-4 space-y-1 text-sm text-slate-300">
                                <li>Membuat List Master Product (Raw Material)</li>
                                <li>Membuat List Master Supplier</li>
                            </ol>
                        </div>
                        <div class="bg-slate-800/50 p-4 rounded-lg border border-slate-700/50">
                            <strong class="text-emerald-400 block mb-2 text-xs uppercase tracking-wider">Regular Flow</strong>
                            <ol class="list-decimal pl-4 space-y-1 text-sm text-slate-300" start="3">
                                <li>Membuat PO Supplier (Material)</li>
                                <li>Membuat PO Subcont (Jasa Process)</li>
                                <li>Melakukan Good Received (GR) Material</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        `
    },
    bpd: {
        title: 'Business Process Document',
        subtitle: 'As-Is (Manual) vs To-Be (Digital JICOS)',
        body: `
            <div class="space-y-8 animate-fade-in-up">
                
                <!-- Main Header Concept -->
                <div class="bg-gradient-to-r from-[#0a0a1a] to-slate-900 p-6 rounded-2xl border border-slate-700/50 shadow-lg relative overflow-hidden">
                    <div class="absolute right-0 top-0 w-1/3 h-full bg-gradient-to-l from-cyan-500/10 to-transparent"></div>
                    <div class="relative z-10">
                        <h3 class="text-xl font-bold text-white flex items-center gap-2 mb-2">
                            <svg class="w-6 h-6 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                            Core Process Transformation
                        </h3>
                        <p class="text-slate-400 text-sm max-w-2xl">Perubahan radikal dari eksekusi manual yang bertumpu pada operator menjadi eksekusi digital yang diotomatiskan (Automated Trigger) melalui standarisasi alur kerja JICOS ERP.</p>
                    </div>
                </div>

                <!-- Comparison Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    
                    <!-- AS-IS Block -->
                    <div class="flex flex-col h-full bg-red-900/10 rounded-2xl border border-red-900/50 p-6 relative group hover:bg-red-900/20 transition-all duration-300">
                        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                            <svg class="w-24 h-24 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        
                        <div class="flex items-center gap-3 mb-6 relative z-10">
                            <div class="p-3 bg-red-950/50 rounded-xl text-red-500 border border-red-900/50">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21l9-5-9-5-9 5 9 5z" /></svg>
                            </div>
                            <div>
                                <h4 class="text-xl font-black text-red-400 tracking-tight">AS-IS Model</h4>
                                <span class="text-xs font-semibold text-red-500/70 uppercase tracking-widest">Sistem Lama (Manual)</span>
                            </div>
                        </div>

                        <div class="space-y-4 flex-1 relative z-10">
                            <!-- Pain point 1 -->
                            <div class="bg-slate-900/50 p-4 rounded-xl border border-red-900/30">
                                <div class="flex gap-3">
                                    <div class="mt-0.5 text-red-500"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></div>
                                    <div>
                                        <div class="text-sm font-bold text-slate-300 mb-1">Fragmented Data Entry</div>
                                        <div class="text-xs text-slate-500 leading-relaxed">Pencatatan produksi, stok, dan QC dilakukan secara terpisah menggunakan form kertas & Microsoft Excel. Membutuhkan rekapitulasi ulang oleh admin.</div>
                                    </div>
                                </div>
                            </div>
                            <!-- Pain point 2 -->
                            <div class="bg-slate-900/50 p-4 rounded-xl border border-red-900/30">
                                <div class="flex gap-3">
                                    <div class="mt-0.5 text-red-500"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></div>
                                    <div>
                                        <div class="text-sm font-bold text-slate-300 mb-1">Blind Spot traceability</div>
                                        <div class="text-xs text-slate-500 leading-relaxed">Sangat sulit & memakan waktu berhari-hari untuk melacak akar masalah material lot saat terjadi klaim pelanggan/audit IATF.</div>
                                    </div>
                                </div>
                            </div>
                            <!-- Pain point 3 -->
                            <div class="bg-slate-900/50 p-4 rounded-xl border border-red-900/30">
                                <div class="flex gap-3">
                                    <div class="mt-0.5 text-red-500"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></div>
                                    <div>
                                        <div class="text-sm font-bold text-slate-300 mb-1">Delayed Decision Making</div>
                                        <div class="text-xs text-slate-500 leading-relaxed">Informasi real-time status mesin dan inventori tidak akurat karena delay input data fisik ke sistem accounting.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TO-BE Block -->
                    <div class="flex flex-col h-full bg-emerald-900/10 rounded-2xl border border-emerald-500/50 p-6 shadow-[0_0_30px_rgba(16,185,129,0.1)] relative group hover:bg-emerald-900/20 transition-all duration-300">
                        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-30 transition-opacity">
                            <svg class="w-24 h-24 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                        </div>

                        <div class="flex items-center gap-3 mb-6 relative z-10">
                            <div class="p-3 bg-emerald-900/50 rounded-xl text-emerald-400 border border-emerald-500/50 shadow-[0_0_15px_rgba(16,185,129,0.4)]">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" /></svg>
                            </div>
                            <div>
                                <h4 class="text-xl font-black text-emerald-400 tracking-tight">TO-BE Model</h4>
                                <span class="text-xs font-semibold text-emerald-500 uppercase tracking-widest">JICOS Digital Ecosystem</span>
                            </div>
                        </div>

                        <div class="space-y-4 flex-1 relative z-10">
                            <!-- Benefit 1 -->
                            <div class="bg-slate-900/80 p-4 rounded-xl border border-emerald-900/50 hover:border-emerald-500/50 transition-colors">
                                <div class="flex gap-3">
                                    <div class="mt-0.5 text-emerald-400"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></div>
                                    <div>
                                        <div class="text-sm font-bold text-white mb-1">Single Source of Truth</div>
                                        <div class="text-xs text-slate-400 leading-relaxed">Digital SPK (Surat Perintah Kerja) dengan validasi langsung di lantai pabrik. Data stok terpotong otomatis *(backflush)* saat produksi disahkan.</div>
                                    </div>
                                </div>
                            </div>
                            <!-- Benefit 2 -->
                            <div class="bg-slate-900/80 p-4 rounded-xl border border-emerald-900/50 hover:border-emerald-500/50 transition-colors">
                                <div class="flex gap-3">
                                    <div class="mt-0.5 text-cyan-400"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" /></svg></div>
                                    <div>
                                        <div class="text-sm font-bold text-white mb-1">Traceability QR-Code Instan</div>
                                        <div class="text-xs text-slate-400 leading-relaxed">Setiap palet Finish Good mendapat label QR Code unik. Cukup scan, sistem menampilkan silsilah lengkap (Lot Material, Operator, Hasil QC).</div>
                                    </div>
                                </div>
                            </div>
                            <!-- Benefit 3 -->
                            <div class="bg-slate-900/80 p-4 rounded-xl border border-emerald-900/50 hover:border-emerald-500/50 transition-colors">
                                <div class="flex gap-3">
                                    <div class="mt-0.5 text-purple-400"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg></div>
                                    <div>
                                        <div class="text-sm font-bold text-white mb-1">Executive Dashboard & Alert</div>
                                        <div class="text-xs text-slate-400 leading-relaxed">Matriks performa divisualisasikan real-time. Jika OEE (Overall Equipment Effectiveness) turun, bot WhatsApp langsung kirim notifikasi ke Manajer.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Workflow Step Evolution -->
                <div class="mt-4 bg-[#0a0a1a] rounded-2xl border border-slate-800 p-8">
                    <h4 class="text-lg font-bold text-white mb-8 border-b border-slate-800 pb-4">Evolusi Alur Kerja Order-to-Cash</h4>
                    
                    <div class="relative">
                        <!-- Horizontal connector line -->
                        <div class="absolute top-[30px] left-8 right-8 h-0.5 bg-slate-800 hidden md:block"></div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 relative">
                            <!-- Step 1 -->
                            <div class="relative flex flex-col items-center group">
                                <div class="w-16 h-16 rounded-full bg-slate-800 border-4 border-[#0a0a1a] flex items-center justify-center text-slate-400 z-10 group-hover:bg-cyan-900 group-hover:text-cyan-400 transition-colors shadow-lg">
                                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                </div>
                                <div class="mt-4 text-center">
                                    <h5 class="text-sm font-bold text-white mb-1">1. Sales Order</h5>
                                    <p class="text-[11px] text-slate-500">Automasi dari PO Pelanggan. Pengecekan stok ghoib hilang.</p>
                                </div>
                            </div>
                            
                            <!-- Step 2 -->
                            <div class="relative flex flex-col items-center group">
                                <div class="w-16 h-16 rounded-full bg-slate-800 border-4 border-[#0a0a1a] flex items-center justify-center text-slate-400 z-10 group-hover:bg-cyan-900 group-hover:text-cyan-400 transition-colors shadow-lg">
                                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                </div>
                                <div class="mt-4 text-center">
                                    <h5 class="text-sm font-bold text-white mb-1">2. Prod. Planning</h5>
                                    <p class="text-[11px] text-slate-500">Kalkulasi Material Requirement Planning (MRP) Otomatis.</p>
                                </div>
                            </div>
                            
                            <!-- Step 3 -->
                            <div class="relative flex flex-col items-center group">
                                <div class="w-16 h-16 rounded-full bg-slate-800 border-4 border-[#0a0a1a] flex items-center justify-center text-slate-400 z-10 group-hover:bg-cyan-900 group-hover:text-cyan-400 transition-colors shadow-lg">
                                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
                                </div>
                                <div class="mt-4 text-center">
                                    <h5 class="text-sm font-bold text-white mb-1">3. Execution & QC</h5>
                                    <p class="text-[11px] text-slate-500">Pengurangan defect karena Validasi Gatekeeper mandiri.</p>
                                </div>
                            </div>
                            
                            <!-- Step 4 -->
                            <div class="relative flex flex-col items-center group">
                                <div class="w-16 h-16 rounded-full bg-slate-800 border-4 border-[#0a0a1a] flex items-center justify-center text-slate-400 z-10 group-hover:bg-emerald-900 group-hover:text-emerald-400 transition-colors shadow-lg shadow-emerald-900/20">
                                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                                </div>
                                <div class="mt-4 text-center">
                                    <h5 class="text-sm font-bold text-white mb-1">4. Delivery & Invoice</h5>
                                    <p class="text-[11px] text-slate-500">Scan QR Code Palet -> SJ & Faktur Terbit Akurat.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `
    },
    frd: {
        title: 'Functional Requirements',
        subtitle: 'Precision Packaging Module Logic',
        body: `
            <div class="space-y-8 animate-fade-in-up">
                
                <!-- FRD Header Module -->
                <div class="bg-gradient-to-r from-indigo-900/40 to-slate-900 p-6 rounded-2xl border border-indigo-500/30 flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2 mb-2">
                            <svg class="w-6 h-6 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" /></svg>
                            Core System Algorithms (FRD)
                        </h3>
                        <p class="text-slate-400 text-sm max-w-2xl">Penjabaran logika komputasi internal, batasan sistem operasional (constraints), dan rumus konversi material pada industri manufaktur kemasan (Packaging).</p>
                    </div>
                    <div class="hidden md:flex p-3 bg-indigo-500/10 rounded-xl border border-indigo-500/20">
                        <svg class="w-12 h-12 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
                    </div>
                </div>

                <!-- Material to Carton Logic -->
                <div class="bg-[#0a0a1a] p-6 rounded-2xl border border-slate-800 shadow-xl">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-2 bg-cyan-900/30 rounded-lg text-cyan-400 border border-cyan-500/30">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" /></svg>
                        </div>
                        <h4 class="text-lg font-black text-white">1. Material-to-Carton Logic</h4>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <p class="text-slate-400 text-sm leading-relaxed mb-4">Sistem bertugas menghitung konversi otomatis raw material (Sheet Board/Impraboard) menjadi Finish Good (Carton Box) berlandaskan perhitungan yield efisiensi:</p>
                            
                            <div class="space-y-3">
                                <div class="bg-slate-900/50 p-3 rounded-lg border border-slate-800 flex justify-between items-center group hover:border-cyan-500/30 transition-colors">
                                    <span class="text-slate-300 font-medium text-sm flex items-center gap-2">
                                        <svg class="w-4 h-4 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" /></svg>
                                        Formula Up Value
                                    </span>
                                    <span class="text-xs text-cyan-400 font-mono bg-cyan-900/30 px-2 py-1 rounded">Sheet / Box</span>
                                </div>
                                <div class="bg-slate-900/50 p-3 rounded-lg border border-slate-800 flex justify-between items-center group hover:border-red-500/30 transition-colors">
                                    <span class="text-slate-300 font-medium text-sm flex items-center gap-2">
                                        <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                                        Production Waste
                                    </span>
                                    <span class="text-xs text-red-400 font-mono bg-red-900/30 px-2 py-1 rounded">% Scrap</span>
                                </div>
                                <div class="bg-slate-900/50 p-3 rounded-lg border border-slate-800 flex justify-between items-center group hover:border-emerald-500/30 transition-colors">
                                    <span class="text-slate-300 font-medium text-sm flex items-center gap-2">
                                        <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                                        Material Yield
                                    </span>
                                    <span class="text-xs text-emerald-400 font-mono bg-emerald-900/30 px-2 py-1 rounded">Pcs / Raw</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Mini Interactive Calculator Mockup -->
                        <div class="bg-slate-900 border border-slate-700/50 rounded-xl p-5 relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-cyan-900/20 blur-xl rounded-full"></div>
                            <h5 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4">Simulasi Perhitungan Validasi (Kode: JICOS-FRD-01)</h5>
                            
                            <div class="space-y-2 font-mono text-sm relative z-10">
                                <div class="flex justify-between border-b border-slate-800 pb-2">
                                    <span class="text-slate-400">Target Output SPK:</span>
                                    <span class="text-white">1,000 Box</span>
                                </div>
                                <div class="flex justify-between border-b border-slate-800 pb-2">
                                    <span class="text-slate-400">Up Value (Box/Sheet):</span>
                                    <span class="text-white">4</span>
                                </div>
                                <div class="flex justify-between pt-1">
                                    <span class="text-cyan-400 font-bold">REQ. MATERIAL:</span>
                                    <span class="text-cyan-400 font-bold">250 Sheets</span>
                                </div>
                                <div class="mt-4 pt-4 border-t border-dashed border-slate-700">
                                     <span class="flex items-center gap-2 text-xs text-amber-500">
                                         <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                         Jika aktual &gt; 250 sheet, alert "High Scrap Ratio" terpicu.
                                     </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Business Rules & Constraints -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-[#0a0a1a] p-6 rounded-2xl border border-slate-800 hover:border-purple-500/30 transition-colors">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="p-2 bg-purple-900/20 rounded-lg text-purple-400">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                            </div>
                            <h4 class="font-bold text-white">2. Backflush Validation Constraints</h4>
                        </div>
                        <ul class="space-y-3">
                            <li class="flex items-start gap-2 text-sm text-slate-300">
                                <svg class="w-4 h-4 mt-0.5 text-slate-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                Operator dilarang melakukan Post (Sahkan) SPK apabila bahan baku ghoib (stok di sistem bernilai minus).
                            </li>
                            <li class="flex items-start gap-2 text-sm text-slate-300">
                                <svg class="w-4 h-4 mt-0.5 text-slate-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                SPK harus terlebih dahulu mengalami proses 'Material Picking' secara scan QR.
                            </li>
                        </ul>
                    </div>

                    <div class="bg-[#0a0a1a] p-6 rounded-2xl border border-slate-800 hover:border-amber-500/30 transition-colors">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="p-2 bg-amber-900/20 rounded-lg text-amber-400">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <h4 class="font-bold text-white">3. Toleransi Pengiriman (Delivery)</h4>
                        </div>
                        <ul class="space-y-3">
                            <li class="flex items-start gap-2 text-sm text-slate-300">
                                <svg class="w-4 h-4 mt-0.5 text-slate-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                Sistem menolak Surat Jalan jika (Kuantitas Dikirim &gt; Kuantitas Sales Order) tanpa persetujuan (Approval) Manager.
                            </li>
                            <li class="flex items-start gap-2 text-sm text-slate-300">
                                <svg class="w-4 h-4 mt-0.5 text-slate-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                Toleransi kelebihan kirim (Over-delivery tolerance) dapat diubah setting di Master Customer (Default: Max 5%).
                            </li>
                        </ul>
                    </div>
                </div>

            </div>
        `
    },
    database: {
        title: 'Database Structure',
        subtitle: 'Entity Relationship Diagram (ERD)',
        isMermaid: true,
        body: `
            erDiagram
                COMPANIES ||--o{ PRODUCTS : owns
                COMPANIES ||--o{ CUSTOMERS : manages
                
                CUSTOMERS ||--o{ SALES_ORDERS : places
                SALES_ORDERS {
                    bigint id PK
                    string number
                    date date
                    string status
                }
                
                SALES_ORDERS ||--o{ SALES_ORDER_ITEMS : contains
                SALES_ORDER_ITEMS {
                    bigint id PK
                    bigint product_id FK
                    decimal quantity
                    decimal price
                }
                
                PRODUCTS ||--o{ SALES_ORDER_ITEMS : sold_as
                PRODUCTS ||--o{ PRODUCT_STOCKS : stored_in
                
                WAREHOUSES ||--o{ PRODUCT_STOCKS : holds
                PRODUCT_STOCKS {
                    bigint id PK
                    decimal quantity
                    string location_bin
                }
                
                WORK_ORDERS ||--o{ PRODUCTION_ENTRIES : generates
                WORK_ORDERS {
                    string number
                    string status
                    datetime start_time
                }
                
                PROJECTS ||--o{ PROJECT_TASKS : tracks
        `,
        details: `
            <div class="mt-8">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-indigo-900/30 rounded-lg text-indigo-400 border border-indigo-500/30">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                    </div>
                    <h4 class="text-xl font-black text-white">Data Dictionary</h4>
                </div>
                
                <div class="space-y-6">
                    <!-- Core Setup Tables -->
                    <div class="bg-[#0a0a1a] rounded-xl border border-slate-800 overflow-hidden">
                        <div class="bg-indigo-900/20 px-6 py-4 border-b border-indigo-900/50 flex justify-between items-center">
                            <h5 class="font-bold text-indigo-400">Core Configuration Entities</h5>
                            <span class="text-xs bg-indigo-900/50 text-indigo-300 px-2 py-1 rounded">Setup/References</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="text-xs text-slate-400 uppercase bg-slate-900/50 border-b border-slate-800">
                                    <tr>
                                        <th class="px-6 py-3 font-medium">Table Name</th>
                                        <th class="px-6 py-3 font-medium">Key Attributes</th>
                                        <th class="px-6 py-3 font-medium">Description</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800 text-slate-300 bg-slate-900/20">
                                    <tr class="hover:bg-slate-800/50 transition-colors">
                                        <td class="px-6 py-4 font-mono text-cyan-400">products</td>
                                        <td class="px-6 py-4"><span class="bg-slate-800 px-2 py-1 rounded border border-slate-700 text-xs">name</span> <span class="bg-slate-800 px-2 py-1 rounded border border-slate-700 text-xs">sku</span> <span class="bg-slate-800 px-2 py-1 rounded border border-slate-700 text-xs">type (RM/FG)</span></td>
                                        <td class="px-6 py-4 text-slate-400">Master Data seluruh material, mulai dari Biji Plastik hingga Carton Box siap jual.</td>
                                    </tr>
                                    <tr class="hover:bg-slate-800/50 transition-colors">
                                        <td class="px-6 py-4 font-mono text-cyan-400">customers</td>
                                        <td class="px-6 py-4"><span class="bg-slate-800 px-2 py-1 rounded border border-slate-700 text-xs">company_id</span> <span class="bg-slate-800 px-2 py-1 rounded border border-slate-700 text-xs">name</span> <span class="bg-slate-800 px-2 py-1 rounded border border-slate-700 text-xs">sales_pic</span></td>
                                        <td class="px-6 py-4 text-slate-400">Direktori Klien/Pembeli dengan detail pajak (NPWP/NIK) dan alamat pengiriman gabungan.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Warehouse & Stock -->
                    <div class="bg-[#0a0a1a] rounded-xl border border-slate-800 overflow-hidden">
                        <div class="bg-emerald-900/20 px-6 py-4 border-b border-emerald-900/50 flex justify-between items-center">
                            <h5 class="font-bold text-emerald-400">Warehouse & Inventory Management</h5>
                            <span class="text-xs bg-emerald-900/50 text-emerald-300 px-2 py-1 rounded">Transaction Log</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="text-xs text-slate-400 uppercase bg-slate-900/50 border-b border-slate-800">
                                    <tr>
                                        <th class="px-6 py-3 font-medium">Table Name</th>
                                        <th class="px-6 py-3 font-medium">Key Attributes</th>
                                        <th class="px-6 py-3 font-medium">Description</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800 text-slate-300 bg-slate-900/20">
                                    <tr class="hover:bg-slate-800/50 transition-colors">
                                        <td class="px-6 py-4 font-mono text-emerald-400">product_stocks</td>
                                        <td class="px-6 py-4"><span class="bg-slate-800 px-2 py-1 rounded border border-slate-700 text-amber-500 text-xs text-xs">product_id</span> <span class="bg-slate-800 px-2 py-1 rounded border border-slate-700 text-xs text-amber-500">warehouse_id</span> <span class="bg-slate-800 px-2 py-1 rounded border border-slate-700 text-emerald-400 text-xs">quantity</span></td>
                                        <td class="px-6 py-4 text-slate-400">Pivot mutakhir kuantitas fisik. Dipotong oleh Produksi, ditambah oleh Good Receipt/Pembelian.</td>
                                    </tr>
                                    <tr class="hover:bg-slate-800/50 transition-colors">
                                        <td class="px-6 py-4 font-mono text-emerald-400">traceability_lots</td>
                                        <td class="px-6 py-4"><span class="bg-slate-800 px-2 py-1 rounded border border-slate-700 text-xs">qr_code_id</span> <span class="bg-slate-800 px-2 py-1 rounded border border-slate-700 text-xs">parent_lot (RM)</span> <span class="bg-slate-800 px-2 py-1 rounded border border-slate-700 text-xs">operator_id</span></td>
                                        <td class="px-6 py-4 text-slate-400">Silsilah genetika produksi. Menyimpan korelasi Pallet Jadi dengan Material Lot Pembentuknya.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        `
    },
    security: {
        title: 'Security Matrix',
        subtitle: 'Role-Based Access Control (RBAC)',
        body: `
            <div class="space-y-6 animate-fade-in-up">
                
                <!-- Matrix Header Concept -->
                <div class="bg-gradient-to-r from-red-900/20 to-[#0a0a1a] p-6 rounded-2xl border border-red-900/50 shadow-lg relative overflow-hidden">
                    <div class="absolute right-0 top-0 w-1/3 h-full bg-gradient-to-l from-red-500/10 to-transparent"></div>
                    <div class="relative z-10">
                        <h3 class="text-xl font-bold text-white flex items-center gap-2 mb-2">
                            <svg class="w-6 h-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                            Role-Based Access Control (RBAC)
                        </h3>
                        <p class="text-slate-400 text-sm max-w-2xl">Penetapan matriks hak guna otoritas setiap departemen di dalam JICOS ERP untuk memastikan prinsip <strong class="text-white">Least Privilege</strong> dan menghindari kebocoran maupun manipulasi data lintas sektoral.</p>
                    </div>
                </div>

                <!-- Legends -->
                <div class="flex flex-wrap gap-4 text-xs font-semibold">
                    <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-emerald-500"></span><span class="text-slate-300">Full Access (Create, Edit, Delete)</span></div>
                    <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-cyan-500"></span><span class="text-slate-300">View & Approve</span></div>
                    <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-slate-500"></span><span class="text-slate-300">Read / View Only</span></div>
                    <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-red-500/50"></span><span class="text-slate-300">No Access</span></div>
                </div>

                <!-- Matrix Table -->
                <div class="bg-[#0a0a1a] rounded-2xl border border-slate-800 shadow-2xl overflow-hidden mt-2">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs uppercase bg-slate-900 border-b border-slate-800">
                                <tr>
                                    <th class="px-6 py-4 font-black flex items-center gap-2 text-white">
                                        <svg class="w-4 h-4 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                                        Core Modules
                                    </th>
                                    <th class="px-6 py-4 font-bold text-center text-slate-300 border-l border-slate-800">Production<br><span class="text-[10px] text-slate-500 font-normal">Operator / SPV</span></th>
                                    <th class="px-6 py-4 font-bold text-center text-slate-300 border-l border-slate-800">Sales & MKT<br><span class="text-[10px] text-slate-500 font-normal">Marketing Staff</span></th>
                                    <th class="px-6 py-4 font-bold text-center text-slate-300 border-l border-slate-800">Purchasing<br><span class="text-[10px] text-slate-500 font-normal">Buyer / Admin</span></th>
                                    <th class="px-6 py-4 font-bold text-center text-slate-300 border-l border-slate-800">Finance<br><span class="text-[10px] text-slate-500 font-normal">Accounting / Tax</span></th>
                                    <th class="px-6 py-4 font-bold text-center text-cyan-400 border-l border-slate-800">Manager<br><span class="text-[10px] text-cyan-900 font-normal">All Dept Heads</span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800 text-slate-400 bg-slate-900/30">
                                <!-- Modul Sales -->
                                <tr class="hover:bg-slate-800/50 transition-colors">
                                    <td class="px-6 py-4 font-bold text-white border-r border-slate-800/50">Sales (SO, Quotation)</td>
                                    <td class="px-6 py-4 text-center border-r border-slate-800/50"><span class="w-full inline-block px-2 py-1 rounded text-xs font-bold bg-slate-500/20 text-slate-400 border border-slate-600/50">Read Only</span></td>
                                    <td class="px-6 py-4 text-center border-r border-slate-800/50"><span class="w-full inline-block px-2 py-1 rounded text-xs font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 shadow-[0_0_10px_rgba(16,185,129,0.15)]">Full Access</span></td>
                                    <td class="px-6 py-4 text-center border-r border-slate-800/50"><span class="w-full inline-block px-2 py-1 rounded text-xs font-bold bg-slate-500/20 text-slate-400 border border-slate-600/50">Read Only</span></td>
                                    <td class="px-6 py-4 text-center border-r border-slate-800/50"><span class="w-full inline-block px-2 py-1 rounded text-xs font-bold bg-slate-500/20 text-slate-400 border border-slate-600/50">Read Only</span></td>
                                    <td class="px-6 py-4 text-center"><span class="w-full inline-block px-2 py-1 rounded text-xs font-bold bg-cyan-500/20 text-cyan-400 border border-cyan-500/30">View & Approve</span></td>
                                </tr>
                                <!-- Modul Inventory / Gudang -->
                                <tr class="hover:bg-slate-800/50 transition-colors">
                                    <td class="px-6 py-4 font-bold text-white border-r border-slate-800/50">Inventory (WH/Stock)</td>
                                    <td class="px-6 py-4 text-center border-r border-slate-800/50"><span class="w-full inline-block px-2 py-1 rounded text-xs font-bold bg-slate-500/20 text-slate-400 border border-slate-600/50">Read Only</span></td>
                                    <td class="px-6 py-4 text-center border-r border-slate-800/50"><span class="w-full inline-block px-2 py-1 rounded text-xs font-bold bg-slate-500/20 text-slate-400 border border-slate-600/50">Read Only</span></td>
                                    <td class="px-6 py-4 text-center border-r border-slate-800/50"><span class="w-full inline-block px-2 py-1 rounded text-xs font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 shadow-[0_0_10px_rgba(16,185,129,0.15)]">Full Access</span></td>
                                    <td class="px-6 py-4 text-center border-r border-slate-800/50"><span class="w-full inline-block px-2 py-1 rounded text-xs font-bold bg-slate-500/20 text-slate-400 border border-slate-600/50">Read Only</span></td>
                                    <td class="px-6 py-4 text-center"><span class="w-full inline-block px-2 py-1 rounded text-xs font-bold bg-cyan-500/20 text-cyan-400 border border-cyan-500/30">View & Approve</span></td>
                                </tr>
                                <!-- Modul Produksi -->
                                <tr class="hover:bg-slate-800/50 transition-colors">
                                    <td class="px-6 py-4 font-bold text-white border-r border-slate-800/50">Production (SPK, WOS)</td>
                                    <td class="px-6 py-4 text-center border-r border-slate-800/50"><span class="w-full inline-block px-2 py-1 rounded text-xs font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 shadow-[0_0_10px_rgba(16,185,129,0.15)]">Full Access</span></td>
                                    <td class="px-6 py-4 text-center border-r border-slate-800/50"><span class="w-full inline-block px-2 py-1 rounded text-xs font-bold bg-slate-500/20 text-slate-400 border border-slate-600/50">Read Only</span></td>
                                    <td class="px-6 py-4 text-center border-r border-slate-800/50"><span class="w-full inline-block px-2 py-1 rounded text-xs font-bold bg-slate-500/20 text-slate-400 border border-slate-600/50">Read Only</span></td>
                                    <td class="px-6 py-4 text-center border-r border-slate-800/50"><span class="w-full inline-block px-2 py-1 rounded text-xs font-bold bg-red-500/10 text-red-500/50 border border-red-500/20">No Access</span></td>
                                    <td class="px-6 py-4 text-center"><span class="w-full inline-block px-2 py-1 rounded text-xs font-bold bg-cyan-500/20 text-cyan-400 border border-cyan-500/30">View & Approve</span></td>
                                </tr>
                                <!-- Modul Keuangan -->
                                <tr class="hover:bg-slate-800/50 transition-colors">
                                    <td class="px-6 py-4 font-bold text-white border-r border-slate-800/50">Finance (Invoice)</td>
                                    <td class="px-6 py-4 text-center border-r border-slate-800/50"><span class="w-full inline-block px-2 py-1 rounded text-xs font-bold bg-red-500/10 text-red-500/50 border border-red-500/20">No Access</span></td>
                                    <td class="px-6 py-4 text-center border-r border-slate-800/50"><span class="w-full inline-block px-2 py-1 rounded text-xs font-bold bg-slate-500/20 text-slate-400 border border-slate-600/50">Read Only</span></td>
                                    <td class="px-6 py-4 text-center border-r border-slate-800/50"><span class="w-full inline-block px-2 py-1 rounded text-xs font-bold bg-slate-500/20 text-slate-400 border border-slate-600/50">Read Only</span></td>
                                    <td class="px-6 py-4 text-center border-r border-slate-800/50"><span class="w-full inline-block px-2 py-1 rounded text-xs font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 shadow-[0_0_10px_rgba(16,185,129,0.15)]">Full Access</span></td>
                                    <td class="px-6 py-4 text-center"><span class="w-full inline-block px-2 py-1 rounded text-xs font-bold bg-cyan-500/20 text-cyan-400 border border-cyan-500/30">View & Approve</span></td>
                                </tr>
                                <!-- Global Config -->
                                <tr class="hover:bg-slate-800/50 transition-colors bg-red-900/5">
                                    <td class="px-6 py-4 font-bold text-red-400 border-r border-slate-800/50">System Settings</td>
                                    <td class="px-6 py-4 text-center border-r border-slate-800/50"><span class="w-full inline-block px-2 py-1 rounded text-xs font-bold bg-red-500/10 text-red-500/50 border border-red-500/20">No Access</span></td>
                                    <td class="px-6 py-4 text-center border-r border-slate-800/50"><span class="w-full inline-block px-2 py-1 rounded text-xs font-bold bg-red-500/10 text-red-500/50 border border-red-500/20">No Access</span></td>
                                    <td class="px-6 py-4 text-center border-r border-slate-800/50"><span class="w-full inline-block px-2 py-1 rounded text-xs font-bold bg-red-500/10 text-red-500/50 border border-red-500/20">No Access</span></td>
                                    <td class="px-6 py-4 text-center border-r border-slate-800/50"><span class="w-full inline-block px-2 py-1 rounded text-xs font-bold bg-red-500/10 text-red-500/50 border border-red-500/20">No Access</span></td>
                                    <td class="px-6 py-4 text-center"><span class="w-full inline-block px-2 py-1 rounded text-xs font-bold bg-red-500/20 text-red-400 border border-red-500/40 shadow-[0_0_10px_rgba(239,68,68,0.2)]">Super Admin</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `
    },
    integration: {
        title: 'Integrations',
        subtitle: 'Internal Data Flow & WhatsApp Bot',
        body: `
            <div class="space-y-8 animate-fade-in-up">
                <p class="text-slate-300 leading-relaxed max-w-3xl">
                    Arsitektur JICOS dirancang untuk dapat berkomunikasi secara *seamless* dengan berbagai ekosistem eksternal maupun internal perusahaan melalui API, Webhook, dan protokol koneksi langsung perangkat keras.
                </p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- WhatsApp Gateway -->
                    <div class="group bg-gradient-to-br from-[#0a0a1a] to-[#0a1515] p-6 rounded-2xl border border-emerald-900/30 hover:border-emerald-500/50 shadow-lg hover:shadow-[0_0_30px_rgba(16,185,129,0.15)] transition-all duration-300 relative overflow-hidden">
                        <div class="absolute -right-10 -top-10 w-40 h-40 bg-emerald-500/5 rounded-full blur-3xl group-hover:bg-emerald-500/10 transition-colors"></div>
                        <div class="flex items-start gap-4 relative z-10">
                            <div class="p-3 bg-emerald-500/10 rounded-xl text-emerald-400 border border-emerald-500/20 group-hover:scale-110 transition-transform">
                                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            </div>
                            <div>
                                <h4 class="text-lg font-bold text-white mb-1">WhatsApp Official API</h4>
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-300">Webhook</span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-800 text-slate-300">Real-time Push</span>
                                </div>
                                <ul class="space-y-2 text-sm text-slate-400">
                                    <li class="flex items-center gap-2"><svg class="w-3 h-3 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Pengiriman OTP login untuk keamanan ganda.</li>
                                    <li class="flex items-center gap-2"><svg class="w-3 h-3 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Notifikasi kritis saat level stok material menyentuh batas &lt; Reorder Point.</li>
                                    <li class="flex items-center gap-2"><svg class="w-3 h-3 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Laporan Breakdown/Kerusakan Mesin instan ke tim Maintenance.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Thermal Print / Hardware -->
                    <div class="group bg-gradient-to-br from-[#0a0a1a] to-[#1a1015] p-6 rounded-2xl border border-pink-900/30 hover:border-pink-500/50 shadow-lg hover:shadow-[0_0_30px_rgba(236,72,153,0.15)] transition-all duration-300 relative overflow-hidden">
                        <div class="absolute -right-10 -top-10 w-40 h-40 bg-pink-500/5 rounded-full blur-3xl group-hover:bg-pink-500/10 transition-colors"></div>
                        <div class="flex items-start gap-4 relative z-10">
                            <div class="p-3 bg-pink-500/10 rounded-xl text-pink-400 border border-pink-500/20 group-hover:scale-110 transition-transform">
                                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                            </div>
                            <div>
                                <h4 class="text-lg font-bold text-white mb-1">Direct Hardware Print</h4>
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-pink-500/20 text-pink-300">ZPL / ESC/POS</span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-800 text-slate-300">Local Network</span>
                                </div>
                                <ul class="space-y-2 text-sm text-slate-400">
                                    <li class="flex items-center gap-2"><svg class="w-3 h-3 text-pink-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Cetak label QR Code industri (Zebra / TSC) secara otomatis pasca-produksi.</li>
                                    <li class="flex items-center gap-2"><svg class="w-3 h-3 text-pink-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Desain kustom koordinat print label untuk ID Operator, Part No, dan Lot Number.</li>
                                    <li class="flex items-center gap-2"><svg class="w-3 h-3 text-pink-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Integrasi Scanner Barcode Bluetooth Android (Keyboard Wedge).</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Email Relays -->
                    <div class="group bg-gradient-to-br from-[#0a0a1a] to-[#0a101a] p-6 rounded-2xl border border-blue-900/30 hover:border-blue-500/50 shadow-lg hover:shadow-[0_0_30px_rgba(59,130,246,0.15)] transition-all duration-300 relative overflow-hidden">
                        <div class="absolute -right-10 -top-10 w-40 h-40 bg-blue-500/5 rounded-full blur-3xl group-hover:bg-blue-500/10 transition-colors"></div>
                        <div class="flex items-start gap-4 relative z-10">
                            <div class="p-3 bg-blue-500/10 rounded-xl text-blue-400 border border-blue-500/20 group-hover:scale-110 transition-transform">
                                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                            </div>
                            <div>
                                <h4 class="text-lg font-bold text-white mb-1">Email Exchange (SMTP)</h4>
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-500/20 text-blue-300">SMTP TLS</span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-800 text-slate-300">Background Job</span>
                                </div>
                                <ul class="space-y-2 text-sm text-slate-400">
                                    <li class="flex items-center gap-2"><svg class="w-3 h-3 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Pengiriman otomatis dokumen Surat Jalan (Delivery Note) ber-PDF ke pelanggan.</li>
                                    <li class="flex items-center gap-2"><svg class="w-3 h-3 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Distribusi E-Faktur dan Invoice penagihan tiap akhir bulan.</li>
                                    <li class="flex items-center gap-2"><svg class="w-3 h-3 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Rekapitulasi Laporan Produksi harian langsung ke email jajaran Direksi.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- External APIs / EDI -->
                    <div class="group bg-gradient-to-br from-[#0a0a1a] to-[#150a15] p-6 rounded-2xl border border-purple-900/30 hover:border-purple-500/50 shadow-lg hover:shadow-[0_0_30px_rgba(168,85,247,0.15)] transition-all duration-300 relative overflow-hidden">
                        <div class="absolute -right-10 -top-10 w-40 h-40 bg-purple-500/5 rounded-full blur-3xl group-hover:bg-purple-500/10 transition-colors"></div>
                        <div class="flex items-start gap-4 relative z-10">
                            <div class="p-3 bg-purple-500/10 rounded-xl text-purple-400 border border-purple-500/20 group-hover:scale-110 transition-transform">
                                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" /></svg>
                            </div>
                            <div>
                                <h4 class="text-lg font-bold text-white mb-1">RESTful API / EDI</h4>
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-500/20 text-purple-300">JSON/XML</span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-800 text-slate-300">Secured OAuth</span>
                                </div>
                                <ul class="space-y-2 text-sm text-slate-400">
                                    <li class="flex items-center gap-2"><svg class="w-3 h-3 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Endpoint sinkronisasi data master (Customer & Produk) dengan portal eksternal.</li>
                                    <li class="flex items-center gap-2"><svg class="w-3 h-3 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Persiapan integrasi Electronic Data Interchange (EDI) ke vendor tier-1 (Honda/Mitsubishi).</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `
    },
    qc: {
        title: 'Quality Control',
        subtitle: 'Operator Self-Check Strategy',
        body: `
            <div class="space-y-8 animate-fade-in-up">
                
                <!-- Hero concept: Gatekeeper System -->
                <div class="bg-gradient-to-r from-indigo-900/40 to-[#0a0a1a] p-6 rounded-2xl border border-indigo-500/30 relative shadow-[0_0_30px_rgba(99,102,241,0.1)] overflow-hidden">
                    <div class="absolute right-0 top-0 w-64 h-full bg-indigo-500/10 blur-3xl rounded-full"></div>
                    <div class="relative z-10 flex flex-col md:flex-row gap-6 items-center">
                        <div class="p-4 bg-indigo-500/20 rounded-2xl flex-shrink-0 border border-indigo-500/30 shadow-inner">
                            <svg class="w-12 h-12 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-black text-white mb-2 tracking-tight">Lean QC: Gatekeeper System</h3>
                            <p class="text-slate-300 leading-relaxed text-sm">
                                Paradigma kontrol kualitas bergeser dari sekadar Inspeksi Akhir menjadi kontrol pada Sumbernya (<em>Quality at the Source</em>). 
                                Sistem <strong>Gatekeeper</strong> mewajibkan Operator Mesin untuk melakukan <strong class="text-indigo-400">Self-Check & Validation</strong>,
                                mengunggah foto <em>First Article</em> via Tablet di Terminal Produksi, sebelum diizinkan mencetak Label lot Produksi atau melanjutkan ke palet berikutnya.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Three Stages of QC -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 relative">
                    <!-- Connecting line background (Visible on large screens) -->
                    <div class="hidden lg:block absolute top-12 left-[16%] right-[16%] h-1 bg-gradient-to-r from-slate-800 via-indigo-900/50 to-slate-800 z-0 rounded-full"></div>
                    
                    <!-- 1. IQC -->
                    <div class="bg-[#0a0a1a] rounded-2xl border border-slate-800 hover:border-blue-500/50 p-6 relative z-10 transition-all duration-300 group hover:-translate-y-1 shadow-lg">
                        <div class="w-12 h-12 rounded-full bg-slate-900 border-2 border-slate-700 group-hover:border-blue-500 flex items-center justify-center text-blue-400 font-black text-xl mb-4 shadow-[0_0_15px_rgba(59,130,246,0)] group-hover:shadow-[0_0_15px_rgba(59,130,246,0.3)] transition-all mx-auto lg:mx-0">
                            1
                        </div>
                        <h4 class="text-lg font-bold text-white text-center lg:text-left mb-1">Incoming (IQC)</h4>
                        <p class="text-[10px] text-blue-400 text-center lg:text-left uppercase font-bold tracking-wider mb-4">Inspeksi Material Datang</p>
                        <ul class="space-y-3 text-sm text-slate-400">
                            <li class="flex items-start gap-2"><svg class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Pengecekan visual & dimensi material mentah (Impraboard/Carton).</li>
                            <li class="flex items-start gap-2"><svg class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Validasi <em>Certificate of Analysis</em> (CoA) dari Supplier.</li>
                            <li class="flex items-start gap-2"><svg class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Penetapan status Lot: <span class="text-green-400 font-bold border border-green-500/30 px-1 rounded text-xs bg-green-500/10">PASS</span> atau <span class="text-red-400 font-bold border border-red-500/30 px-1 rounded text-xs bg-red-500/10">REJECT</span>.</li>
                        </ul>
                    </div>

                    <!-- 2. IPQC -->
                    <div class="bg-[#0a0a1a] rounded-2xl border border-slate-800 hover:border-amber-500/50 p-6 relative z-10 transition-all duration-300 group hover:-translate-y-1 shadow-lg">
                        <div class="w-12 h-12 rounded-full bg-slate-900 border-2 border-slate-700 group-hover:border-amber-500 flex items-center justify-center text-amber-400 font-black text-xl mb-4 shadow-[0_0_15px_rgba(245,158,11,0)] group-hover:shadow-[0_0_15px_rgba(245,158,11,0.3)] transition-all mx-auto lg:mx-0">
                            2
                        </div>
                        <h4 class="text-lg font-bold text-white text-center lg:text-left mb-1">In-Process (IPQC)</h4>
                        <p class="text-[10px] text-amber-400 text-center lg:text-left uppercase font-bold tracking-wider mb-4">Kendali Mutu Produksi</p>
                        <ul class="space-y-3 text-sm text-slate-400">
                            <li class="flex items-start gap-2"><svg class="w-4 h-4 text-amber-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Validasi <em>First Article</em> setiap awal Shift atau Ganti Model (SPK).</li>
                            <li class="flex items-start gap-2"><svg class="w-4 h-4 text-amber-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Patroli inspeksi keliling tiap 2 jam (Sampling).</li>
                            <li class="flex items-start gap-2"><svg class="w-4 h-4 text-amber-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Identifikasi Cacat & Input jumlah <span class="text-amber-400 italic">Scrap/NG</span> (Not Good) langsung ke ERP.</li>
                        </ul>
                    </div>

                    <!-- 3. OQA -->
                    <div class="bg-[#0a0a1a] rounded-2xl border border-slate-800 hover:border-emerald-500/50 p-6 relative z-10 transition-all duration-300 group hover:-translate-y-1 shadow-lg">
                        <div class="w-12 h-12 rounded-full bg-slate-900 border-2 border-slate-700 group-hover:border-emerald-500 flex items-center justify-center text-emerald-400 font-black text-xl mb-4 shadow-[0_0_15px_rgba(16,185,129,0)] group-hover:shadow-[0_0_15px_rgba(16,185,129,0.3)] transition-all mx-auto lg:mx-0">
                            3
                        </div>
                        <h4 class="text-lg font-bold text-white text-center lg:text-left mb-1">Outgoing (OQA)</h4>
                        <p class="text-[10px] text-emerald-400 text-center lg:text-left uppercase font-bold tracking-wider mb-4">Inspeksi Pra-Pengiriman</p>
                        <ul class="space-y-3 text-sm text-slate-400">
                            <li class="flex items-start gap-2"><svg class="w-4 h-4 text-emerald-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Verifikasi Kesesuaian Fisik dengan Surat Jalan (Delivery Note).</li>
                            <li class="flex items-start gap-2"><svg class="w-4 h-4 text-emerald-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Final check kebersihan, keutuhan label QR, dan standar <em>packaging</em>.</li>
                            <li class="flex items-start gap-2"><svg class="w-4 h-4 text-emerald-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Approval pelepasan barang ke Ekspedisi/Konsumen.</li>
                        </ul>
                    </div>
                </div>
            </div>
        `
    },
    uat: {
        title: 'System Testing',
        subtitle: 'Gateway to Interactive UAT Module',
        body: `
            <div class="space-y-6 animate-fade-in-up flex justify-center items-center py-6">
                <div class="w-full max-w-4xl bg-gradient-to-br from-blue-900/40 to-[#0a0a1a] p-10 rounded-3xl border border-blue-500/30 text-center relative overflow-hidden shadow-[0_0_50px_rgba(59,130,246,0.1)] group">
                    <!-- Background Glow -->
                    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-blue-500/10 blur-[100px] rounded-full pointer-events-none group-hover:bg-blue-500/20 transition-all duration-700"></div>
                    
                    <div class="relative z-10 flex flex-col items-center">
                        <div class="w-20 h-20 bg-[#0a0a1a] rounded-2xl border flex items-center justify-center mb-6 shadow-[0_0_30px_rgba(6,182,212,0.3)] border-cyan-500/50 group-hover:scale-110 transition-transform duration-500">
                            <svg class="w-10 h-10 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        
                        <h3 class="text-3xl font-black text-white mb-4 tracking-tight">Dedicated UAT Workspace</h3>
                        <p class="text-slate-300 max-w-2xl mx-auto mb-10 leading-relaxed">
                            Berdasarkan kompleksitas arsitektur JICOS, <strong>User Acceptance Testing (UAT)</strong> memiliki ratusan skenario end-to-end yang harus dilacak status eksekusinya (Passed/Failed). Kami telah menyediakan modul khusus (Dedicated) yang lebih interaktif alih-alih merangkumnya di dokumen statis ini.
                        </p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 w-full mb-10 text-left">
                            <div class="bg-slate-900/50 p-4 rounded-xl border border-slate-700/50 hover:border-emerald-500/30 transition-colors">
                                <h5 class="text-emerald-400 font-bold mb-1">01. Test Cases</h5>
                                <p class="text-xs text-slate-400">Ratusan skenario validasi bisnis harian.</p>
                            </div>
                            <div class="bg-slate-900/50 p-4 rounded-xl border border-slate-700/50 hover:border-blue-500/30 transition-colors">
                                <h5 class="text-blue-400 font-bold mb-1">02. Progress Tracking</h5>
                                <p class="text-xs text-slate-400">Persentase Live rasio Passed vs Failed.</p>
                            </div>
                            <div class="bg-slate-900/50 p-4 rounded-xl border border-slate-700/50 hover:border-purple-500/30 transition-colors">
                                <h5 class="text-purple-400 font-bold mb-1">03. Issue Logger</h5>
                                <p class="text-xs text-slate-400">Pencatatan temuan Bug & Follow-Up.</p>
                            </div>
                        </div>

                        <!-- Real Button linking to UAT module -->
                        <a href="/settings/uat" target="_blank" class="inline-flex items-center gap-3 px-8 py-4 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white font-bold rounded-xl shadow-[0_0_20px_rgba(6,182,212,0.4)] transition-all hover:scale-105 cursor-pointer ring-1 ring-white/20">
                            Buka Menu System Testing (UAT)
                            <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                        </a>
                        <p class="text-xs text-slate-500 mt-4 font-mono">Navigasi Sidebar: <span class="text-slate-400">Documentation &gt; System Testing (UAT)</span></p>
                    </div>
                </div>
            </div>
        `
    }
};

const currentContent = computed(() => content[activeTab.value]);

onMounted(() => {
    mermaid.initialize({ 
        startOnLoad: false, 
        theme: 'dark',
        securityLevel: 'loose',
    });
    renderMermaid();
});

const renderMermaid = async () => {
    // Wait for the next tick to ensure DOM is updated
    await nextTick();
    
    // Slight delay to ensure transition stability if needed
    // setTimeout is not ideal but sometimes helps with mermaid's measurement logic in hidden containers
    
    const element = document.querySelector('.mermaid-graph');
    
    // Only proceed if element exists and content is mermaid type
    if (element && currentContent.value.isMermaid) {
        // Reset content to loading state if re-rendering (optional, but good for UX)
        // element.innerHTML = 'Loading Visualizer...'; 
        
        try {
            // Generate unique ID for each render to prevent caching issues
            const graphId = 'mermaid-svg-' + activeTab.value + '-' + Date.now();
            const { svg } = await mermaid.render(graphId, currentContent.value.body);
            element.innerHTML = svg;
        } catch (e) {
            console.error('Mermaid render error:', e);
            element.innerHTML = `<div class="text-red-500 font-mono text-sm p-4 bg-red-900/20 rounded">
                <p class="font-bold">Diagram Render Error:</p>
                <p>${e.message}</p>
            </div>`;
        }
    }
};

// No need to watch activeTab separately if we use @after-enter, 
// BUT @after-enter only fires on transitions. 
// Initial load needs onMounted.
</script>

<template>
    <Head title="Project Blueprint" />

    <AppLayout title="Project Blueprint">
        <div class="flex h-[calc(100vh-4rem)] bg-[#050510] overflow-hidden">
            <!-- Sidebar -->
            <aside class="w-72 bg-[#0a0a1a] border-r border-slate-800 flex flex-col z-20">
                <div class="p-6 border-b border-slate-800">
                    <h2 class="text-xl font-black text-white tracking-tight">BLUEPRINT<span class="text-cyan-500">.HUB</span></h2>
                    <p class="text-xs text-slate-500 mt-1">Strategic Documentation JICOS</p>
                </div>
                <nav class="flex-1 overflow-y-auto p-4 space-y-1">
                    <button 
                        v-for="item in menuItems" 
                        :key="item.id"
                        @click="activeTab = item.id"
                        :class="[
                            'w-full flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-lg transition-all duration-200',
                            activeTab === item.id 
                                ? 'bg-cyan-500/10 text-cyan-400 border border-cyan-500/20 shadow-[0_0_15px_rgba(6,182,212,0.15)]' 
                                : 'text-slate-400 hover:text-white hover:bg-white/5'
                        ]"
                    >
                        <component :is="item.icon" class="w-5 h-5" />
                        {{ item.label }}
                    </button>

                    <div class="mt-8 pt-6 border-t border-slate-800/80">
                        <a href="/Blueprint_ERP.html" target="_blank" class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white text-sm font-bold rounded-xl shadow-[0_0_15px_rgba(6,182,212,0.3)] hover:shadow-[0_0_20px_rgba(6,182,212,0.5)] transition-all duration-300">
                            <DocumentCheckIcon class="w-5 h-5" />
                            Versi Cetak (Web/PDF)
                        </a>
                        <p class="text-[10px] text-slate-500 text-center mt-3 px-2">Lihat file lengkap secara utuh termasuk Tabel & Skema ERD.</p>
                    </div>
                </nav>
            </aside>

            <!-- Main Content -->
            <main class="flex-1 overflow-y-auto relative perspective-1000">
                <div class="max-w-6xl mx-auto p-12">
                    <Transition 
                        enter-active-class="transition-all duration-500 ease-out"
                        enter-from-class="opacity-0 translate-y-10 scale-95"
                        enter-to-class="opacity-100 translate-y-0 scale-100"
                        leave-active-class="transition-all duration-300 ease-in absolute top-12 left-12 right-12"
                        leave-from-class="opacity-100 translate-y-0"
                        leave-to-class="opacity-0 -translate-y-10"
                        mode="out-in"
                        @after-enter="renderMermaid"
                    >
                        <div :key="activeTab" class="bg-[#0f0f25] border border-slate-800 rounded-2xl p-8 shadow-2xl relative overflow-hidden group min-h-[600px]">
                            <!-- Background Decor -->
                            <div class="absolute top-0 right-0 -mr-20 -mt-20 w-64 h-64 bg-cyan-500/10 rounded-full blur-3xl pointer-events-none group-hover:bg-cyan-500/20 transition-all duration-1000"></div>

                            <div class="relative z-10 h-full flex flex-col">
                                <h1 class="text-4xl font-black text-white mb-2 tracking-tight">{{ currentContent.title }}</h1>
                                <p class="text-lg text-cyan-400 mb-8 font-light border-b border-white/5 pb-4">{{ currentContent.subtitle }}</p>
                                
                                <div class="flex-1 overflow-auto">
                                    <div v-if="currentContent.isMermaid" class="mermaid-container pb-12">
                                        <div class="mermaid-graph flex justify-center p-4 bg-slate-900 rounded-lg min-h-[300px]">
                                            <!-- Mermaid Diagram Rendered Here -->
                                            Loading Visualizer...
                                        </div>
                                        <!-- Render additional details if available (for Flowchart side notes) -->
                                        <div v-if="currentContent.details" v-html="currentContent.details" class="animate-fade-in-up"></div>
                                    </div>
                                    <div v-else class="prose prose-invert max-w-none prose-headings:font-bold prose-p:text-slate-300 prose-li:text-slate-300" v-html="currentContent.body"></div>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>
            </main>
        </div>
    </AppLayout>
</template>

<style>
/* Mermaid custom styling override */
.mermaid-graph svg {
    max-width: 100%;
    height: auto;
}
</style>

<style scoped>
.perspective-1000 {
    perspective: 1000px;
}
/* Custom Scrollbar */
::-webkit-scrollbar {
    width: 6px;
}
::-webkit-scrollbar-track {
    background: #0a0a1a; 
}
::-webkit-scrollbar-thumb {
    background: #1e293b; 
    border-radius: 3px;
}
::-webkit-scrollbar-thumb:hover {
    background: #334155; 
}
</style>
