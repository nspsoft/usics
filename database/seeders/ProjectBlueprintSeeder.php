<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProjectBlueprint;
use Illuminate\Support\Facades\DB;

class ProjectBlueprintSeeder extends Seeder
{
    public function run(): void
    {
        // Bersihkan data lama agar id terurut apabila seeder dijalankan ulang
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        ProjectBlueprint::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $data = [
            [
                'title' => 'Executive Summary',
                'order_index' => 1,
                'content' => "### USICS ERP Implementation for PT JIDOKA\n\n<div class=\"space-y-6\">\n    <div class=\"bg-indigo-900/50 p-6 rounded-xl border border-indigo-500/30\">\n        <h3 class=\"text-xl font-bold text-indigo-300 mb-2\">Visi Digitalisasi</h3>\n        <p class=\"text-gray-300\">\n            Transformasi total operasional PT JIDOKA dari manual menjadi terintegrasi penuh, \n            mendukung standar <strong>High-Precision Packaging</strong> untuk industri otomotif (OEM).\n        </p>\n    </div>\n    <div class=\"grid grid-cols-2 gap-4\">\n        <div class=\"bg-gray-800 p-4 rounded-lg\">\n            <div class=\"text-sm text-gray-400\">Target Go-Live</div>\n            <div class=\"text-2xl font-bold text-white\">01 March 2026</div>\n        </div>\n        <div class=\"bg-gray-800 p-4 rounded-lg\">\n            <div class=\"text-sm text-gray-400\">Compliance Standard</div>\n            <div class=\"text-2xl font-bold text-white\">IATF 16949</div>\n        </div>\n    </div>\n</div>"
            ],
            [
                'title' => 'Organization Chart',
                'order_index' => 2,
                'content' => "### 2025 Packaging Division Structure\n\n```mermaid\nflowchart TD\n    %% Styles\n    classDef default fill:#1e293b,stroke:#94a3b8,color:#fff,stroke-width:1px;\n    classDef root fill:#0f172a,stroke:#06b6d4,color:#22d3ee,stroke-width:2px;\n    classDef dept fill:#1e293b,stroke:#f59e0b,color:#fbbf24,stroke-width:2px;\n    classDef subteam fill:#0f172a,stroke:#64748b,color:#94a3b8,stroke-width:1px,stroke-dasharray: 5 5;\n\n    ROOT[PACKAGING DIVISION]:::root\n    \n    %% Departments\n    ROOT --> PURCH[PURCHASING, GA & HRD]:::dept\n    ROOT --> MITSUBISHI[MITSUBISHI GROUP<br/>INTERNAL SALES]:::dept\n    ROOT --> HONDA[HONDA GROUP<br/>INTERNAL SALES]:::dept\n    ROOT --> OTHERS[OTHERS GROUP<br/>INTERNAL SALES]:::dept\n    ROOT --> MKT[MKT & ENGINEERING<br/>DEVELOPMENT]:::dept\n    ROOT --> ADMIN[ADMINISTRATION &<br/>FAKTUR PAJAK]:::dept\n    \n    %% Teams Purchasing\n    PURCH --> P_CS[Control Stock Cons.<br/>Part & GA]:::subteam\n    PURCH --> P_DR[Driver Team]:::subteam\n```"
            ],
            [
                'title' => 'System Flow Chart',
                'order_index' => 3,
                'content' => "### Inventory Flow with Customer Order\n\n```mermaid\ngraph TD\n    %% Styles\n    classDef default fill:#1e293b,stroke:#94a3b8,color:#fff,stroke-width:1px;\n    classDef decision fill:#0f172a,stroke:#06b6d4,color:#22d3ee,stroke-width:2px;\n    classDef terminal fill:#0f172a,stroke:#4ade80,color:#4ade80,stroke-width:2px,stroke-dasharray: 5 5;\n    classDef document fill:#1e293b,stroke:#f59e0b,color:#fbbf24,stroke-width:1px;\n    \n    %% Nodes\n    start([Customer Purchase Order]):::terminal --> SO[Sales Order]\n    SO --> pop{POP Decision:<br/>Pick, Order, Produce?}:::decision\n    \n    %% Pick Branch\n    pop -- Pick --> pick_ticket[Create 'Pick Ticket']:::document\n    pick_ticket --> pick_item[Pick up items from<br/>current inventory]\n    \n    %% Order Branch\n    pop -- Order --> det_order_qty[Determine Order Qty]\n    det_order_qty --> create_po[Create Purchase Order<br/>and Send]:::document\n    create_po --> receive_item[Receive Items]\n    \n    %% Produce Branch\n    pop -- Produce --> det_prod_qty[Determine Quantity]\n    det_prod_qty --> create_wo[Create Work Order]:::document\n    create_wo --> manufacture[Assemble or<br/>Manufacture Items]\n    \n    %% Convergence\n    pick_item --> box[Box and package<br/>the items]\n    receive_item --> box\n    manufacture --> box\n    \n    box --> docs[Create Delivery Order<br/>and Invoice]:::document\n    docs --> ship([Ship to Customer]):::terminal\n```"
            ],
            [
                'title' => 'Business Process (BPD)',
                'order_index' => 4,
                'content' => "### As-Is (Manual) vs To-Be (Digital USICS)\n\n<div class=\"space-y-6\">\n    <div class=\"grid grid-cols-1 md:grid-cols-2 gap-6\">\n        <div class=\"bg-red-900/20 p-6 rounded-xl border border-red-500/30\">\n            <h3 class=\"text-lg font-bold text-red-400 mb-4\">AS-IS (Lama)</h3>\n            <ul class=\"list-disc pl-5 space-y-2 text-gray-300\">\n                <li>Pencatatan manual di Excel/Buku Tulis.</li>\n                <li>Cek stok fisik memakan waktu lama.</li>\n                <li>Traceability Lot sulit dilacak saat audit.</li>\n            </ul>\n        </div>\n        <div class=\"bg-emerald-900/20 p-6 rounded-xl border border-emerald-500/30\">\n            <h3 class=\"text-lg font-bold text-emerald-400 mb-4\">TO-BE (USICS)</h3>\n            <ul class=\"list-disc pl-5 space-y-2 text-gray-300\">\n                <li>Digital SPK & Dashboard Real-time.</li>\n                <li>Stok otomatis berkurang saat produksi.</li>\n                <li><strong>Traceability QR Code</strong> instan.</li>\n            </ul>\n        </div>\n    </div>\n</div>"
            ],
            [
                'title' => 'Functional Req (FRD)',
                'order_index' => 5,
                'content' => "### Precision Packaging Module Logic\n\n<div class=\"space-y-4\">\n    <div class=\"bg-slate-800 p-5 rounded-lg\">\n        <h4 class=\"font-bold text-white mb-2\">Material-to-Carton Logic</h4>\n        <p class=\"text-gray-400 text-sm\">Sistem menghitung konversi otomatis dari Sheet Board menjadi Carton Box berdasarkan:</p>\n        <ul class=\"mt-2 space-y-1 text-sm text-cyan-400\">\n            <li>• <strong>Up Value:</strong> Jumlah box per sheet.</li>\n            <li>• <strong>Production Waste:</strong> Sisa potongan (scrap).</li>\n            <li>• <strong>Material Yield:</strong> Efisiensi penggunaan board.</li>\n        </ul>\n    </div>\n</div>"
            ],
            [
                'title' => 'Database Structure',
                'order_index' => 6,
                'content' => "### Entity Relationship Diagram (ERD)\n\n```mermaid\nerDiagram\n    COMPANIES ||--o{ PRODUCTS : owns\n    COMPANIES ||--o{ CUSTOMERS : manages\n    \n    CUSTOMERS ||--o{ SALES_ORDERS : places\n    SALES_ORDERS {\n        bigint id PK\n        string number\n        date date\n        string status\n    }\n    \n    SALES_ORDERS ||--o{ SALES_ORDER_ITEMS : contains\n    SALES_ORDER_ITEMS {\n        bigint id PK\n        bigint product_id FK\n        decimal quantity\n        decimal price\n    }\n    \n    PRODUCTS ||--o{ SALES_ORDER_ITEMS : sold_as\n    PRODUCTS ||--o{ PRODUCT_STOCKS : stored_in\n    \n    WAREHOUSES ||--o{ PRODUCT_STOCKS : holds\n    PRODUCT_STOCKS {\n        bigint id PK\n        decimal quantity\n        string location_bin\n    }\n    \n    WORK_ORDERS ||--o{ PRODUCTION_ENTRIES : generates\n    WORK_ORDERS {\n        string number\n        string status\n        datetime start_time\n    }\n    \n    PROJECTS ||--o{ PROJECT_TASKS : tracks\n```"
            ]
        ];

        foreach ($data as $item) {
            ProjectBlueprint::create($item);
        }
    }
}
