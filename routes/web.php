<?php

use App\Http\Controllers\HR\PayrollController;
use App\Http\Controllers\HR\AttendanceController;
use App\Http\Controllers\HR\PayrollSettingController;

// Public Payroll Validation (No login required)
Route::get('/favicon.png', [App\Http\Controllers\FaviconController::class, 'show'])->name('favicon');

Route::middleware('throttle:public-validate-view')->group(function () {
    Route::get('/v/p/{uuid}', [PayrollController::class, 'publicValidate'])->name('payroll.public-validate');
    Route::get('/v/pr/{uuid}', [App\Http\Controllers\Purchasing\PurchaseRequestController::class, 'publicValidate'])->name('purchasing.requests.public-validate');
    Route::get('/v/po/{uuid}', [App\Http\Controllers\Purchasing\PurchaseOrderController::class, 'publicValidate'])->name('purchasing.orders.public-validate');
    Route::get('/v/grn/{uuid}', [App\Http\Controllers\Purchasing\GoodsReceiptController::class, 'publicValidate'])->name('purchasing.receipts.public-validate');
    Route::get('/v/wo/{uuid}', [App\Http\Controllers\Manufacturing\WorkOrderController::class, 'publicValidate'])->name('manufacturing.work-orders.public-validate');
    Route::get('/v/sco/{uuid}', [App\Http\Controllers\Manufacturing\SubcontractOrderController::class, 'publicValidate'])->name('manufacturing.subcontract-orders.public-validate');
    Route::get('/v/scsj/{uuid}', [App\Http\Controllers\Manufacturing\SubcontractOrderController::class, 'publicValidateSJ'])->name('manufacturing.subcontract-orders.public-validate-sj');
    Route::get('/v/scgr/{uuid}', [App\Http\Controllers\Manufacturing\SubcontractOrderController::class, 'publicValidateGRN'])->name('manufacturing.subcontract-orders.public-validate-grn');
    Route::get('/v/q/{uuid}', [App\Http\Controllers\Sales\QuotationController::class, 'publicValidate'])->name('sales.quotations.public-validate');
    Route::get('/v/inv/{uuid}', [App\Http\Controllers\Sales\SalesInvoiceController::class, 'publicValidate'])->name('sales.invoices.public-validate');
    Route::get('/v/do/{uuid}', [App\Http\Controllers\Sales\DeliveryOrderController::class, 'publicValidate'])->name('sales.deliveries.public-validate');
    Route::get('/v/ret/{uuid}', [App\Http\Controllers\Sales\SalesReturnController::class, 'publicValidate'])->name('sales.returns.public-validate');
});

Route::middleware('throttle:public-validate-action')->group(function () {
    Route::post('/v/grn/{uuid}/confirm', [App\Http\Controllers\Purchasing\GoodsReceiptController::class, 'publicConfirmReceive'])->name('purchasing.receipts.public-confirm');
});
use App\Http\Controllers\HR\EmployeeController;
use App\Http\Controllers\Inventory\ProductPartnerController;
use App\Http\Controllers\Inventory\ProductController;
use App\Http\Controllers\Inventory\WarehouseController;
use App\Http\Controllers\Manufacturing\BomController;
use App\Http\Controllers\Manufacturing\MachineController;
use App\Http\Controllers\Manufacturing\RoutingController;
use App\Http\Controllers\Manufacturing\ShiftController;
use App\Http\Controllers\Manufacturing\SubcontractOrderController;
use App\Http\Controllers\Manufacturing\WorkOrderController;
use App\Http\Controllers\Purchasing\PurchaseOrderController;
use App\Http\Controllers\Purchasing\PurchaseReturnController;
use App\Http\Controllers\Purchasing\SupplierController;
use App\Http\Controllers\Sales\CustomerController;
use App\Http\Controllers\Sales\SalesOrderController;
use App\Http\Controllers\Sales\SalesReturnController;
use App\Http\Controllers\CRM\LeadController;
use App\Http\Controllers\CRM\OpportunityController;
use App\Http\Controllers\CRM\CampaignController;
use App\Http\Controllers\Project\ProjectController;
use App\Http\Controllers\Project\ProjectTaskController;
use App\Http\Controllers\Project\ProjectMemberController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Auth Routes
Route::get('/login', [App\Http\Controllers\Auth\AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [App\Http\Controllers\Auth\AuthController::class, 'login']);
Route::get('/register', [App\Http\Controllers\Auth\AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [App\Http\Controllers\Auth\AuthController::class, 'register']);

// Dashboard
// Landing Page
Route::get('/', [App\Http\Controllers\WelcomeController::class, 'index'])->name('welcome');
Route::view('/features', 'features')->name('features');

// Public WhatsApp Webhook (Exempt from CSRF in bootstrap/app.php)
Route::post('/whatsapp/webhook', [App\Http\Controllers\Api\WhatsappWebhookController::class, 'handle'])->name('whatsapp.webhook');

use App\Http\Controllers\BlueprintController;

// Dashboard (Protected)
Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard')->middleware(['auth']);
Route::get('/project/blueprint', [BlueprintController::class, 'index'])->name('blueprint.index')->middleware(['auth']);

// Inventory Module
Route::prefix('inventory')->name('inventory.')->middleware(['auth'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Inventory\InventoryDashboardController::class, 'index'])->name('dashboard');
    Route::resource('categories', App\Http\Controllers\Inventory\CategoryController::class);
    Route::get('/stocks', [App\Http\Controllers\Inventory\CurrentStockController::class, 'index'])->name('stocks.index');
    Route::resource('products', ProductController::class);
    Route::post('/products/{product}/partners', [App\Http\Controllers\Inventory\ProductPartnerController::class, 'store'])->name('products.partners.store');
    Route::delete('/products/partners/{partner}', [App\Http\Controllers\Inventory\ProductPartnerController::class, 'destroy'])->name('products.partners.destroy');
    Route::get('/products/{product}/usage', [ProductController::class, 'usage'])->name('products.usage');
    Route::resource('projects', ProjectController::class);
    Route::resource('units', App\Http\Controllers\Inventory\UnitController::class);
    Route::get('/products-export', [ProductController::class, 'export'])->name('products.export');
    // Product Partner Aliases Import
    Route::get('/product-aliases/template', [ProductPartnerController::class, 'template'])->name('product-aliases.template');
    Route::post('/product-aliases/import', [ProductPartnerController::class, 'import'])->name('product-aliases.import');

    Route::post('/products-import', [ProductController::class, 'import'])->name('products.import');
    Route::get('/products-template', [ProductController::class, 'template'])->name('products.template');
    Route::resource('warehouses', WarehouseController::class);

    Route::get('/movements', [App\Http\Controllers\Inventory\StockMovementController::class, 'index'])->name('movements.index');

    // Stock Movements
    Route::delete('/movements/reset', [App\Http\Controllers\Inventory\StockMovementController::class, 'reset'])->name('movements.reset');
    Route::resource('movements', App\Http\Controllers\Inventory\StockMovementController::class);

    // Stock Adjustments
    Route::resource('adjustments', App\Http\Controllers\Inventory\StockAdjustmentController::class);
    Route::post('/adjustments/{adjustment}/complete', [App\Http\Controllers\Inventory\StockAdjustmentController::class, 'complete'])->name('adjustments.complete');
    Route::get('/stock-check', [App\Http\Controllers\Inventory\StockAdjustmentController::class, 'getStock'])->name('stock.check');

    // Stock Opname
    Route::resource('opname', App\Http\Controllers\Inventory\StockOpnameController::class);
    Route::post('/opname/{opname}/populate', [App\Http\Controllers\Inventory\StockOpnameController::class, 'populate'])->name('opname.populate');
    Route::put('/opname/{opname}/items', [App\Http\Controllers\Inventory\StockOpnameController::class, 'updateItems'])->name('opname.update-items');
    Route::put('/opname/{opname}/item', [App\Http\Controllers\Inventory\StockOpnameController::class, 'updateSingleItem'])->name('opname.update-item');
    Route::post('/opname/{opname}/complete', [App\Http\Controllers\Inventory\StockOpnameController::class, 'complete'])->name('opname.complete');
    // Reports
    Route::get('/reports', [App\Http\Controllers\ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/inventory-balance', [App\Http\Controllers\ReportController::class, 'inventoryBalance'])->name('reports.inventory-balance');
    Route::get('/reports/stock-card', [App\Http\Controllers\ReportController::class, 'stockCard'])->name('reports.stock-card');
    Route::get('/reports/sales-summary', [App\Http\Controllers\ReportController::class, 'salesSummary'])->name('reports.sales-summary');
    Route::get('/reports/purchase-summary', [App\Http\Controllers\ReportController::class, 'purchaseSummary'])->name('reports.purchase-summary');
    Route::get('/reports/production-summary', [App\Http\Controllers\ReportController::class, 'productionSummary'])->name('reports.production-summary');

    Route::get('/reports/export/sales', [App\Http\Controllers\ReportController::class, 'exportSales'])->name('reports.export.sales');
    Route::get('/reports/export/purchase', [App\Http\Controllers\ReportController::class, 'exportPurchase'])->name('reports.export.purchase');
    Route::get('/reports/export/production', [App\Http\Controllers\ReportController::class, 'exportProduction'])->name('reports.export.production');

    // Notifications
    Route::get('/notifications', [App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/mark-all-read', [App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
});

// Purchasing Module
Route::prefix('purchasing')->name('purchasing.')->middleware(['auth'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Purchasing\PurchasingDashboardController::class, 'index'])->name('dashboard');
    Route::get('/delivery-schedule', [App\Http\Controllers\Purchasing\DeliveryScheduleController::class, 'index'])->name('delivery-schedule');
    Route::get('/procurement-forecast', [App\Http\Controllers\Purchasing\ProcurementForecastController::class, 'index'])->name('procurement-forecast');
    Route::get('/supplier-scorecard', [App\Http\Controllers\Purchasing\SupplierScorecardController::class, 'index'])->name('supplier-scorecard');
    Route::resource('suppliers', SupplierController::class);
    Route::get('/suppliers-export', [SupplierController::class, 'export'])->name('suppliers.export');
    Route::post('/suppliers-import', [SupplierController::class, 'import'])->name('suppliers.import');
    Route::get('/suppliers-contacts-export', [SupplierController::class, 'exportContacts'])->name('suppliers.contacts.export');
    Route::post('/suppliers-contacts-import', [SupplierController::class, 'importContacts'])->name('suppliers.contacts.import');
    Route::get('/suppliers-template', [SupplierController::class, 'template'])->name('suppliers.template');
    Route::get('/suppliers-contacts-template', [SupplierController::class, 'templateContacts'])->name('suppliers.contacts.template');
    Route::get('/orders/export', [App\Http\Controllers\Purchasing\PurchaseOrderController::class, 'export'])->name('orders.export');
    Route::get('/orders/template', [App\Http\Controllers\Purchasing\PurchaseOrderController::class, 'template'])->name('orders.template');
    Route::post('/orders/import', [App\Http\Controllers\Purchasing\PurchaseOrderController::class, 'import'])->name('orders.import');
    Route::resource('orders', PurchaseOrderController::class);
    Route::post('/orders/{order}/submit', [PurchaseOrderController::class, 'submit'])->name('orders.submit');
    Route::post('/orders/{order}/approve', [PurchaseOrderController::class, 'approve'])->name('orders.approve');
    Route::post('/orders/{order}/mark-ordered', [PurchaseOrderController::class, 'markOrdered'])->name('orders.mark-ordered');
    Route::post('/orders/{order}/cancel', [PurchaseOrderController::class, 'cancel'])->name('orders.cancel');
    Route::put('/orders/items/{item}/qty', [PurchaseOrderController::class, 'updateItemQty'])->name('orders.update-item-qty');
    Route::get('/orders/{order}/print', [PurchaseOrderController::class, 'print'])->name('orders.print');
    Route::get('/orders/create', [App\Http\Controllers\Purchasing\PurchaseOrderController::class, 'create'])->name('orders.create');
    // Purchase Requests
    Route::get('/requests/export', [App\Http\Controllers\Purchasing\PurchaseRequestController::class, 'export'])->name('requests.export');
    Route::get('/requests/template', [App\Http\Controllers\Purchasing\PurchaseRequestController::class, 'template'])->name('requests.template');
    Route::post('/requests/import', [App\Http\Controllers\Purchasing\PurchaseRequestController::class, 'import'])->name('requests.import');
    Route::get('/requests/create', [App\Http\Controllers\Purchasing\PurchaseRequestController::class, 'create'])->name('requests.create');
    Route::post('/requests', [App\Http\Controllers\Purchasing\PurchaseRequestController::class, 'store'])->name('requests.store');
    Route::get('/requests', [App\Http\Controllers\Purchasing\PurchaseRequestController::class, 'index'])->name('requests.index');
    
    // Purchase Request Item/Detail Routes (Wildcards)
    Route::get('/requests/{request}/edit', [App\Http\Controllers\Purchasing\PurchaseRequestController::class, 'edit'])->name('requests.edit');
    Route::put('/requests/{request}', [App\Http\Controllers\Purchasing\PurchaseRequestController::class, 'update'])->name('requests.update');
    Route::delete('/requests/{request}', [App\Http\Controllers\Purchasing\PurchaseRequestController::class, 'destroy'])->name('requests.destroy');
    Route::get('/requests/{request}', [App\Http\Controllers\Purchasing\PurchaseRequestController::class, 'show'])->name('requests.show');
    Route::post('/requests/{request}/approve', [App\Http\Controllers\Purchasing\PurchaseRequestController::class, 'approve'])->name('requests.approve');
    Route::post('/requests/{request}/reject', [App\Http\Controllers\Purchasing\PurchaseRequestController::class, 'reject'])->name('requests.reject');
    Route::get('/requests/{request}/print', [App\Http\Controllers\Purchasing\PurchaseRequestController::class, 'print'])->name('requests.print');
    Route::get('/receipts/po-items/{order}', [App\Http\Controllers\Purchasing\GoodsReceiptController::class, 'getPoItems'])->name('receipts.po-items');
        Route::get('receipts/scan', [\App\Http\Controllers\Purchasing\GoodsReceiptController::class, 'scan'])->name('receipts.scan');
        Route::post('receipts/scan/process', [\App\Http\Controllers\Purchasing\GoodsReceiptController::class, 'processScan'])->name('receipts.scan-process');
        Route::get('receipts/{receipt}/check', [\App\Http\Controllers\Purchasing\GoodsReceiptController::class, 'check'])->name('receipts.check');
        Route::post('receipts/{receipt}/confirm', [\App\Http\Controllers\Purchasing\GoodsReceiptController::class, 'confirmReceive'])->name('receipts.confirm');
        
        // Goods Receipts Export/Import
        Route::get('/receipts/export', [App\Http\Controllers\Purchasing\GoodsReceiptController::class, 'export'])->name('receipts.export');
        Route::get('/receipts/template', [App\Http\Controllers\Purchasing\GoodsReceiptController::class, 'template'])->name('receipts.template');
        Route::post('/receipts/import', [App\Http\Controllers\Purchasing\GoodsReceiptController::class, 'import'])->name('receipts.import');

        Route::resource('receipts', App\Http\Controllers\Purchasing\GoodsReceiptController::class);
    Route::post('/receipts/{receipt}/complete', [App\Http\Controllers\Purchasing\GoodsReceiptController::class, 'complete'])->name('receipts.complete');
    Route::get('/receipts/{receipt}/print', [App\Http\Controllers\Purchasing\GoodsReceiptController::class, 'print'])->name('receipts.print');
    Route::get('/returns/po-items/{order}', [PurchaseReturnController::class, 'getReturnableItems'])->name('purchase-returns.po-items');
    Route::resource('returns', PurchaseReturnController::class)->names([
        'index' => 'purchase-returns.index',
        'create' => 'purchase-returns.create',
        'store' => 'purchase-returns.store',
        'show' => 'purchase-returns.show',
        'update' => 'purchase-returns.update',
        'destroy' => 'purchase-returns.destroy',
        'edit' => 'purchase-returns.edit',
    ]);
    // AI Delivery Note Extractor
    Route::get('/dn-extractor', [App\Http\Controllers\Purchasing\DNExtractorController::class, 'index'])->name('dn-extractor');
    Route::post('/dn-extract', [App\Http\Controllers\Purchasing\DNExtractorController::class, 'extract'])->name('dn-extractor.extract');
    Route::post('/dn-create-gr', [App\Http\Controllers\Purchasing\DNExtractorController::class, 'storeGR'])->name('dn-extractor.store-gr');

    // Purchase Invoices
    Route::resource('invoices', App\Http\Controllers\Purchasing\PurchaseInvoiceController::class);
    Route::post('/invoices/{invoice}/payment', [App\Http\Controllers\Purchasing\PurchaseInvoiceController::class, 'recordPayment'])->name('invoices.payment');
    Route::delete('/invoices/{invoice}/payment/{payment}', [App\Http\Controllers\Purchasing\PurchaseInvoiceController::class, 'deletePayment'])->name('invoices.payment.delete');
    
    // Information
    Route::get('/information', [App\Http\Controllers\Purchasing\PurchasingInformationController::class, 'index'])->name('information');
});

// Sales Module
Route::prefix('sales')->name('sales.')->middleware(['auth'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Sales\SalesDashboardController::class, 'index'])->name('dashboard');
    Route::resource('customers', CustomerController::class);
    Route::get('/customers-export', [CustomerController::class, 'export'])->name('customers.export');
    Route::post('/customers-import', [CustomerController::class, 'import'])->name('customers.import');
    Route::get('/customers-contacts-export', [CustomerController::class, 'exportContacts'])->name('customers.contacts.export');
    Route::post('/customers-contacts-import', [CustomerController::class, 'importContacts'])->name('customers.contacts.import');
    Route::get('/customers-template', [CustomerController::class, 'template'])->name('customers.template');
    // Sales Order Items Report
    Route::get('/orders/items', [App\Http\Controllers\Sales\SalesOrderItemController::class, 'index'])->name('orders.items');
    Route::get('/orders/items/export', [App\Http\Controllers\Sales\SalesOrderItemController::class, 'export'])->name('orders.items.export');

    Route::get('/customers-contacts-template', [CustomerController::class, 'templateContacts'])->name('customers.contacts.template');
    Route::resource('orders', SalesOrderController::class);
    Route::post('/orders/{order}/confirm', [SalesOrderController::class, 'confirm'])->name('orders.confirm');
    Route::post('/orders/{order}/cancel', [SalesOrderController::class, 'cancel'])->name('orders.cancel');
    Route::put('/orders/items/{item}/qty', [SalesOrderController::class, 'updateItemQty'])->name('orders.update-item-qty');
    Route::get('/orders/{order}/print', [SalesOrderController::class, 'print'])->name('orders.print');
    Route::post('/orders/create-from-ai', [SalesOrderController::class, 'createFromAi'])->name('orders.create-from-ai');
    Route::get('/orders/check-po', [SalesOrderController::class, 'checkPo'])->name('orders.check-po');
    Route::post('/orders/{order}/delivery', [SalesOrderController::class, 'createDelivery'])->name('orders.create-delivery');
    Route::post('/orders/{order}/invoice', [SalesOrderController::class, 'createInvoice'])->name('orders.create-invoice');
    Route::get('/orders-export', [SalesOrderController::class, 'export'])->name('orders.export');
    Route::post('/orders-import', [SalesOrderController::class, 'import'])->name('orders.import');
    Route::get('/orders-template', [SalesOrderController::class, 'template'])->name('orders.template');
    Route::post('/orders/ai-extract', [App\Http\Controllers\Sales\POImportController::class, 'extract'])->name('orders.ai-extract');
    Route::post('/orders/analyze-fulfillment', [App\Http\Controllers\Sales\FulfillmentAnalysisController::class, 'analyze'])->name('orders.analyze-fulfillment');
    Route::get('/information', [App\Http\Controllers\Sales\SalesInformationController::class, 'index'])->name('sales.information');
    Route::get('/po-tracking', [App\Http\Controllers\Sales\PoTrackingController::class, 'index'])->name('sales.po-tracking');
    
    // Sales Planning
    Route::prefix('planning')->name('planning.')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\Sales\Planning\SalesPlanningController::class, 'dashboard'])->name('dashboard');
        Route::get('/dashboard/details', [App\Http\Controllers\Sales\Planning\SalesPlanningController::class, 'details'])->name('dashboard.details');
        Route::get('/forecast', [App\Http\Controllers\Sales\Planning\SalesForecastController::class, 'index'])->name('forecast.index');
        Route::post('/forecast/import', [App\Http\Controllers\Sales\Planning\SalesForecastController::class, 'import'])->name('forecast.import');
        Route::get('/forecast/template', [App\Http\Controllers\Sales\Planning\SalesForecastController::class, 'template'])->name('forecast.template');
        Route::get('/forecast/export', [App\Http\Controllers\Sales\Planning\SalesForecastController::class, 'export'])->name('forecast.export');
        Route::get('/forecast/chart-data', [App\Http\Controllers\Sales\Planning\SalesForecastController::class, 'forecastChart'])->name('forecast.chart-data');
        Route::post('/forecast/analyze', [App\Http\Controllers\Sales\Planning\SalesForecastController::class, 'analyzeAccuracy'])->name('forecast.analyze');
        Route::delete('/forecast/{forecast}', [App\Http\Controllers\Sales\Planning\SalesForecastController::class, 'destroy'])->name('forecast.destroy');
        Route::post('/forecast/bulk-delete', [App\Http\Controllers\Sales\Planning\SalesForecastController::class, 'bulkDelete'])->name('forecast.bulk-delete');
        Route::get('/schedule', [App\Http\Controllers\Sales\Planning\DeliveryScheduleController::class, 'index'])->name('schedule.index');
        Route::post('/schedule/import', [App\Http\Controllers\Sales\Planning\DeliveryScheduleController::class, 'import'])->name('schedule.import');
        Route::get('/schedule/template', [App\Http\Controllers\Sales\Planning\DeliveryScheduleController::class, 'template'])->name('schedule.template');
        Route::get('/schedule/export', [App\Http\Controllers\Sales\Planning\DeliveryScheduleController::class, 'export'])->name('schedule.export');
        Route::get('/schedule/comparison', [App\Http\Controllers\Sales\Planning\DeliveryScheduleController::class, 'comparison'])->name('schedule.comparison');
        Route::get('/schedule/print', [App\Http\Controllers\Sales\Planning\DeliveryScheduleController::class, 'printSchedule'])->name('schedule.print');
        Route::get('/schedule/chart-data', [App\Http\Controllers\Sales\Planning\DeliveryScheduleController::class, 'comparisonChart'])->name('schedule.chart-data');
    });

    // AI PO Extractor Page
    Route::get('/po-extractor', [App\Http\Controllers\Sales\POExtractorController::class, 'index'])->name('po-extractor');
    Route::post('/po-extractor/store-product', [App\Http\Controllers\Sales\POImportController::class, 'storeProduct'])->name('po-extractor.store-product');
    Route::post('/po-extractor/store-product-bulk', [App\Http\Controllers\Sales\POImportController::class, 'storeProductBulk'])->name('po-extractor.store-product-bulk');
    Route::post('/po-extractor/export', [App\Http\Controllers\Sales\POExtractorController::class, 'export'])->name('po-extractor.export');
    Route::post('/po-extractor/store-customer', [App\Http\Controllers\Sales\POExtractorController::class, 'storeCustomer'])->name('po-extractor.store-customer');
    Route::post('/po-extractor/store-unit', [App\Http\Controllers\Sales\POExtractorController::class, 'storeUnit'])->name('po-extractor.store-unit');

    Route::get('/quotations/next-number', [App\Http\Controllers\Sales\QuotationController::class, 'generateNextNumber'])->name('quotations.next-number');
    Route::resource('quotations', App\Http\Controllers\Sales\QuotationController::class);
    Route::post('/quotations/{quotation}/send', [App\Http\Controllers\Sales\QuotationController::class, 'send'])->name('quotations.send');
    Route::post('/quotations/{quotation}/accept', [App\Http\Controllers\Sales\QuotationController::class, 'accept'])->name('quotations.accept');
    Route::post('/quotations/{quotation}/reject', [App\Http\Controllers\Sales\QuotationController::class, 'reject'])->name('quotations.reject');
    Route::get('/quotations/{quotation}/print', [App\Http\Controllers\Sales\QuotationController::class, 'print'])->name('quotations.print');
    Route::post('/quotations/{quotation}/convert', [App\Http\Controllers\Sales\QuotationController::class, 'convertToSO'])->name('quotations.convert');
    Route::get('/quotations-export', [App\Http\Controllers\Sales\QuotationController::class, 'export'])->name('quotations.export');
    Route::post('/quotations-import', [App\Http\Controllers\Sales\QuotationController::class, 'import'])->name('quotations.import');
    Route::get('/quotations-template', [App\Http\Controllers\Sales\QuotationController::class, 'template'])->name('quotations.template');
    Route::get('/deliveries/items', [App\Http\Controllers\Sales\DeliveryOrderItemController::class, 'index'])->name('deliveries.items');
    Route::get('/deliveries/items/export', [App\Http\Controllers\Sales\DeliveryOrderItemController::class, 'export'])->name('deliveries.items.export');

    Route::get('/deliveries', [App\Http\Controllers\Sales\DeliveryOrderController::class, 'index'])->name('deliveries.index');
    Route::get('/deliveries/create', [App\Http\Controllers\Sales\DeliveryOrderController::class, 'create'])->name('deliveries.create');
    Route::get('/deliveries/so-items/{sales_order}', [App\Http\Controllers\Sales\DeliveryOrderController::class, 'getSoItems'])->name('deliveries.so-items');
    Route::post('/deliveries', [App\Http\Controllers\Sales\DeliveryOrderController::class, 'store'])->name('deliveries.store');
    Route::get('/deliveries/{delivery_order}', [App\Http\Controllers\Sales\DeliveryOrderController::class, 'show'])->name('deliveries.show');
    Route::put('/deliveries/{delivery_order}/items', [App\Http\Controllers\Sales\DeliveryOrderController::class, 'updateItems'])->name('deliveries.update-items');
    Route::delete('/deliveries/items/{item}', [App\Http\Controllers\Sales\DeliveryOrderController::class, 'destroyItem'])->name('deliveries.destroy-item');
    Route::delete('/deliveries/{delivery_order}', [App\Http\Controllers\Sales\DeliveryOrderController::class, 'destroy'])->name('deliveries.destroy');
    Route::post('/deliveries/{delivery_order}/complete', [App\Http\Controllers\Sales\DeliveryOrderController::class, 'complete'])->name('deliveries.complete');
    Route::patch('/deliveries/{delivery_order}/status', [App\Http\Controllers\Sales\DeliveryOrderController::class, 'updateStatus'])->name('deliveries.update-status');
    Route::get('/deliveries/{delivery_order}/print', [App\Http\Controllers\Sales\DeliveryOrderController::class, 'print'])->name('deliveries.print');
    Route::post('/deliveries/{delivery_order}/invoice', [App\Http\Controllers\Sales\DeliveryOrderController::class, 'createInvoice'])->name('deliveries.create-invoice');
    Route::post('/deliveries/bulk-invoice', [App\Http\Controllers\Sales\DeliveryOrderController::class, 'bulkInvoice'])->name('deliveries.bulk-invoice');
    Route::post('/deliveries/bulk-invoice-preview', [App\Http\Controllers\Sales\DeliveryOrderController::class, 'bulkInvoicePreview'])->name('deliveries.bulk-invoice-preview');
    Route::get('/deliveries-export', [App\Http\Controllers\Sales\DeliveryOrderController::class, 'export'])->name('deliveries.export');


    Route::post('/deliveries-import', [App\Http\Controllers\Sales\DeliveryOrderController::class, 'import'])->name('deliveries.import');
    Route::get('/deliveries-template', [App\Http\Controllers\Sales\DeliveryOrderController::class, 'template'])->name('deliveries.template');
    Route::get('/invoices', [App\Http\Controllers\Sales\SalesInvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/{sales_invoice}', [App\Http\Controllers\Sales\SalesInvoiceController::class, 'show'])->name('invoices.show');
    Route::delete('/invoices/{sales_invoice}', [App\Http\Controllers\Sales\SalesInvoiceController::class, 'destroy'])->name('invoices.destroy');
    Route::post('/invoices/{sales_invoice}/confirm', [App\Http\Controllers\Sales\SalesInvoiceController::class, 'confirm'])->name('invoices.confirm');
    Route::post('/invoices/{sales_invoice}/update-tax', [App\Http\Controllers\Sales\SalesInvoiceController::class, 'updateTaxAmount'])->name('invoices.update-tax');
    Route::post('/invoices/{sales_invoice}/revise', [App\Http\Controllers\Sales\SalesInvoiceController::class, 'revise'])->name('invoices.revise');
    Route::post('/invoices/{sales_invoice}/pay', [App\Http\Controllers\Sales\SalesInvoiceController::class, 'recordPayment'])->name('invoices.pay');
    Route::get('/invoices/{sales_invoice}/print', [App\Http\Controllers\Sales\SalesInvoiceController::class, 'print'])->name('invoices.print');
    Route::get('/invoices/{sales_invoice}/print-v2', [App\Http\Controllers\Sales\SalesInvoiceController::class, 'printV2'])->name('invoices.print-v2');
    Route::post('/invoices/{sales_invoice}/stamp-emeterai', [App\Http\Controllers\Sales\SalesInvoiceController::class, 'stampEmeterai'])->name('invoices.stamp-emeterai');
    Route::get('/invoices-export', [App\Http\Controllers\Sales\SalesInvoiceController::class, 'export'])->name('invoices.export');
    Route::post('/invoices-import', [App\Http\Controllers\Sales\SalesInvoiceController::class, 'import'])->name('invoices.import');
    Route::get('/invoices-template', [App\Http\Controllers\Sales\SalesInvoiceController::class, 'template'])->name('invoices.template');
    Route::get('/returns/so-items/{sales_order}', [App\Http\Controllers\Sales\SalesReturnController::class, 'getReturnableItems'])->name('returns.so-items');
    Route::get('/returns/{sales_return}/print', [App\Http\Controllers\Sales\SalesReturnController::class, 'print'])->name('returns.print');
    Route::resource('returns', SalesReturnController::class);
    Route::post('/returns/{sales_return}/confirm', [SalesReturnController::class, 'confirm'])->name('returns.confirm');

    // WhatsApp Center
    Route::get('/whatsapp', [App\Http\Controllers\Sales\WhatsappCenterController::class, 'index'])->name('whatsapp.index');
    Route::get('/whatsapp/history/{phone}', [App\Http\Controllers\Sales\WhatsappCenterController::class, 'history'])->name('whatsapp.history');
    Route::delete('/whatsapp/history/{phone}', [App\Http\Controllers\Sales\WhatsappCenterController::class, 'destroy'])->name('whatsapp.destroy');
    Route::post('/whatsapp/send', [App\Http\Controllers\Sales\WhatsappCenterController::class, 'send'])->name('whatsapp.send');
    Route::get('/whatsapp/unread-count', [App\Http\Controllers\Sales\WhatsappCenterController::class, 'unreadCount'])->name('whatsapp.unread-count');

    // WhatsApp Templates
    Route::post('/whatsapp/templates', [App\Http\Controllers\Sales\WhatsappCenterController::class, 'storeTemplate'])->name('whatsapp.templates.store');
    Route::put('/whatsapp/templates/{template}', [App\Http\Controllers\Sales\WhatsappCenterController::class, 'updateTemplate'])->name('whatsapp.templates.update');
    Route::delete('/whatsapp/templates/{template}', [App\Http\Controllers\Sales\WhatsappCenterController::class, 'destroyTemplate'])->name('whatsapp.templates.destroy');

    // WhatsApp Labels
    Route::post('/whatsapp/labels', [App\Http\Controllers\Sales\WhatsappCenterController::class, 'addLabel'])->name('whatsapp.labels.store');
    Route::delete('/whatsapp/labels/{label}', [App\Http\Controllers\Sales\WhatsappCenterController::class, 'removeLabel'])->name('whatsapp.labels.destroy');

    // AI Email Inbox
    Route::get('/emails', [App\Http\Controllers\Sales\EmailInboxController::class, 'index'])->name('emails.index');
    Route::get('/emails/sync', [App\Http\Controllers\Sales\EmailInboxController::class, 'sync'])->name('emails.sync');
    Route::get('/emails/{email}', [App\Http\Controllers\Sales\EmailInboxController::class, 'show'])->name('emails.show');
    Route::post('/emails/send', [App\Http\Controllers\Sales\EmailInboxController::class, 'store'])->name('emails.store');
    Route::delete('/emails/{email}', [App\Http\Controllers\Sales\EmailInboxController::class, 'destroy'])->name('emails.destroy');
});

// Manufacturing Module
Route::prefix('manufacturing')->name('manufacturing.')->middleware(['auth'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Manufacturing\ProductionDashboardController::class, 'index'])->name('dashboard');
    Route::resource('boms', BomController::class);
    Route::post('/boms/{bom}/activate', [BomController::class, 'activate'])->name('boms.activate');
    Route::post('/boms/{bom}/archive', [BomController::class, 'archive'])->name('boms.archive');

    Route::resource('work-orders', WorkOrderController::class);
    Route::post('/work-orders/{workOrder}/confirm', [WorkOrderController::class, 'confirm'])->name('work-orders.confirm');
    Route::post('/work-orders/{workOrder}/revert-to-draft', [WorkOrderController::class, 'revertToDraft'])->name('work-orders.revert-to-draft');
    Route::post('/work-orders/{workOrder}/start', [WorkOrderController::class, 'start'])->name('work-orders.start');
    Route::post('/work-orders/{workOrder}/complete', [WorkOrderController::class, 'complete'])->name('work-orders.complete');
    Route::post('/work-orders/{workOrder}/cancel', [WorkOrderController::class, 'cancel'])->name('work-orders.cancel');
    Route::get('/work-orders/{workOrder}/print', [WorkOrderController::class, 'print'])->name('work-orders.print');
    Route::get('/work-orders/{workOrder}/record-production', [WorkOrderController::class, 'recordProductionForm'])->name('work-orders.record-production-form');
    Route::post('/work-orders/{workOrder}/record-production', [WorkOrderController::class, 'recordProduction'])->name('work-orders.record-production');
    
    Route::get('/production-entry', [WorkOrderController::class, 'productionEntryIndex'])->name('production-entry.index');
    Route::resource('shifts', ShiftController::class);
    Route::resource('machines', MachineController::class);
    Route::resource('subcontract-orders', SubcontractOrderController::class);
    Route::post('/subcontract-orders/{subcontractOrder}/dispatch', [SubcontractOrderController::class, 'dispatchMaterials'])->name('subcontract-orders.dispatch');
    Route::post('/subcontract-orders/{subcontractOrder}/return-materials', [SubcontractOrderController::class, 'returnMaterials'])->name('subcontract-orders.return-materials');
    Route::post('/subcontract-orders/{subcontractOrder}/receive', [SubcontractOrderController::class, 'receiveGoods'])->name('subcontract-orders.receive');
    Route::get('/subcontract-orders/{subcontractOrder}/print', [SubcontractOrderController::class, 'print'])->name('subcontract-orders.print');
    Route::get('/subcontract-orders/{subcontractOrder}/print-delivery-note', [SubcontractOrderController::class, 'printDeliveryNote'])->name('subcontract-orders.print-delivery-note');
    Route::get('/subcontract-orders/{movement}/print-grn', [SubcontractOrderController::class, 'printGrn'])->name('subcontract-orders.print-grn');
    Route::post('/subcontract-orders/{subcontractOrder}/generate-po', [SubcontractOrderController::class, 'generatePurchaseOrder'])->name('subcontract-orders.generate-po');

    Route::get('/production', [\App\Http\Controllers\Manufacturing\ProductionController::class, 'index'])->name('production.index');
    Route::get('/routing', [RoutingController::class, 'index'])->name('routing.index');
});

// Quality Control
Route::middleware(['auth'])->prefix('qc')->name('qc.')->group(function () {
    Route::get('/incoming', [App\Http\Controllers\QualityControl\IncomingInspectionController::class, 'index'])->name('incoming.index');
    Route::get('/incoming/{id}', [App\Http\Controllers\QualityControl\IncomingInspectionController::class, 'show'])->name('incoming.show');
    Route::post('/incoming/{id}', [App\Http\Controllers\QualityControl\IncomingInspectionController::class, 'store'])->name('incoming.store');
    Route::get('/in-process', [App\Http\Controllers\QualityControl\InProcessInspectionController::class, 'index'])->name('in-process.index');
    Route::get('/in-process/{id}', [App\Http\Controllers\QualityControl\InProcessInspectionController::class, 'show'])->name('in-process.show');
    Route::post('/in-process/{id}', [App\Http\Controllers\QualityControl\InProcessInspectionController::class, 'store'])->name('in-process.store');
    Route::get('/dashboard', [App\Http\Controllers\QualityControl\QcDashboardController::class, 'index'])->name('dashboard');
    // Route::get('/checklists', fn () => Inertia::render('Blueprints/QC', ['title' => 'Quality Checklists']))->name('checklists');
    
    Route::get('/coa/create', [App\Http\Controllers\QualityControl\CoaController::class, 'create'])->name('coa.create');
    Route::post('/coa', [App\Http\Controllers\QualityControl\CoaController::class, 'store'])->name('coa.store');
    Route::get('/coa/{id}', [App\Http\Controllers\QualityControl\CoaController::class, 'show'])->name('coa.show');
    Route::get('/coa/{id}/print', [App\Http\Controllers\QualityControl\CoaController::class, 'print'])->name('coa.print');

    Route::get('/ncr', [App\Http\Controllers\QualityControl\NcrController::class, 'index'])->name('ncr.index');
    Route::get('/ncr/{id}', [App\Http\Controllers\QualityControl\NcrController::class, 'show'])->name('ncr.show');
    Route::put('/ncr/{id}', [App\Http\Controllers\QualityControl\NcrController::class, 'update'])->name('ncr.update');

    // Master Data
    Route::resource('master-points', App\Http\Controllers\QualityControl\QcMasterPointController::class);
});

// Maintenance
Route::middleware(['auth'])->prefix('maintenance')->name('maintenance.')->group(function () {
    // Schedule
    Route::get('/schedule', [App\Http\Controllers\Maintenance\MaintenanceScheduleController::class, 'index'])->name('schedule');
    Route::post('/schedule', [App\Http\Controllers\Maintenance\MaintenanceScheduleController::class, 'store'])->name('schedule.store');
    Route::post('/schedule/{schedule}/complete', [App\Http\Controllers\Maintenance\MaintenanceScheduleController::class, 'complete'])->name('schedule.complete');

    // Breakdown Logs
    Route::get('/breakdown', [App\Http\Controllers\Maintenance\MaintenanceLogController::class, 'index'])->name('breakdown');
    Route::post('/breakdown', [App\Http\Controllers\Maintenance\MaintenanceLogController::class, 'store'])->name('breakdown.store');
    Route::put('/breakdown/{log}', [App\Http\Controllers\Maintenance\MaintenanceLogController::class, 'update'])->name('breakdown.update');

    // Spareparts
    Route::get('/spareparts', [App\Http\Controllers\Maintenance\MaintenanceSparepartController::class, 'index'])->name('spareparts');
    Route::post('/spareparts', [App\Http\Controllers\Maintenance\MaintenanceSparepartController::class, 'store'])->name('spareparts.store');
    Route::put('/spareparts/{sparepart}', [App\Http\Controllers\Maintenance\MaintenanceSparepartController::class, 'update'])->name('spareparts.update');
});

// Finance
Route::middleware(['auth'])->prefix('finance')->name('finance.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Finance\FinanceDashboardController::class, 'index'])->name('dashboard');
    Route::get('/ledger', [App\Http\Controllers\Finance\FinanceLedgerController::class, 'index'])->name('ledger');
    Route::get('/reports', [App\Http\Controllers\Finance\FinanceReportController::class, 'profitAndLoss'])->name('reports');
    Route::get('/payment-monitoring', [App\Http\Controllers\Finance\FinanceMonitoringController::class, 'index'])->name('payment-monitoring');
});

// HR
Route::middleware(['auth'])->prefix('hr')->name('hr.')->group(function () {
    Route::get('/employees-export', [EmployeeController::class, 'export'])->name('employees.export');
    Route::get('/employees-template', [EmployeeController::class, 'template'])->name('employees.template');
    Route::post('/employees-import', [EmployeeController::class, 'import'])->name('employees.import');
    Route::resource('employees', EmployeeController::class);
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance-template', [AttendanceController::class, 'template'])->name('attendance.template');
    Route::post('/attendance-import', [AttendanceController::class, 'import'])->name('attendance.import');
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clock-in');
    Route::post('/attendance/clock-out/{attendance}', [AttendanceController::class, 'clockOut'])->name('attendance.clock-out');
    
    Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
    Route::get('/payroll/settings', [PayrollSettingController::class, 'index'])->name('payroll.settings');
    Route::post('/payroll/settings', [PayrollSettingController::class, 'update'])->name('payroll.settings.update');
    Route::post('/payroll/generate', [PayrollController::class, 'generate'])->name('payroll.generate');
    Route::get('/payroll/{payroll}', [PayrollController::class, 'show'])->name('payroll.show');
    Route::get('/payroll/{payroll}/print', [PayrollController::class, 'print'])->name('payroll.print');
    Route::put('/payroll/{payroll}/status', [PayrollController::class, 'updateStatus'])->name('payroll.update-status');
});

// Warehouse (Loading Queue for Warehouse Staff)
Route::middleware(['auth'])->prefix('warehouse')->name('warehouse.')->group(function () {
    Route::get('/loading', [App\Http\Controllers\Warehouse\WarehouseLoadingController::class, 'index'])->name('loading.index');
    Route::patch('/loading/{deliveryOrder}/status', [App\Http\Controllers\Warehouse\WarehouseLoadingController::class, 'updateStatus'])->name('loading.update-status');
    Route::put('/loading/{deliveryOrder}/item-qty', [App\Http\Controllers\Warehouse\WarehouseLoadingController::class, 'updateItemQty'])->name('loading.update-item-qty');
    Route::patch('/loading/{deliveryOrder}/toggle-item-loaded', [App\Http\Controllers\Warehouse\WarehouseLoadingController::class, 'toggleItemLoaded'])->name('loading.toggle-item-loaded');
});

// Logistics
Route::middleware(['auth'])->prefix('logistics')->name('logistics.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Logistics\LogisticsDashboardController::class, 'index'])->name('dashboard');
    Route::get('/planning', [App\Http\Controllers\Logistics\LogisticsController::class, 'index'])->name('planning');
    Route::post('/planning/assign', [App\Http\Controllers\Logistics\LogisticsController::class, 'assignVehicle'])->name('planning.assign');

    // Dispatch Panel
    Route::get('/dispatch', [App\Http\Controllers\Logistics\DispatchController::class, 'index'])->name('dispatch.index');
    Route::patch('/dispatch/{deliveryOrder}/ship', [App\Http\Controllers\Logistics\DispatchController::class, 'dispatch'])->name('dispatch.ship');
    
    Route::get('/fleet/export', [App\Http\Controllers\Logistics\VehicleController::class, 'export'])->name('fleet.export');
    Route::get('/fleet/template', [App\Http\Controllers\Logistics\VehicleController::class, 'template'])->name('fleet.template');
    Route::post('/fleet/import', [App\Http\Controllers\Logistics\VehicleController::class, 'import'])->name('fleet.import');
    Route::get('/fleet', [App\Http\Controllers\Logistics\VehicleController::class, 'index'])->name('fleet.index');
    Route::post('/fleet', [App\Http\Controllers\Logistics\VehicleController::class, 'store'])->name('fleet.store');
    Route::get('/fleet/{vehicle}', [App\Http\Controllers\Logistics\VehicleController::class, 'show'])->name('fleet.show');
    Route::put('/fleet/{vehicle}', [App\Http\Controllers\Logistics\VehicleController::class, 'update'])->name('fleet.update');
    Route::delete('/fleet/{vehicle}', [App\Http\Controllers\Logistics\VehicleController::class, 'destroy'])->name('fleet.destroy');
});

// Driver Mobile Module
Route::middleware(['auth'])->prefix('driver')->name('driver.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Driver\DriverController::class, 'dashboard'])->name('dashboard');
    Route::get('/scan', [App\Http\Controllers\Driver\DriverController::class, 'scan'])->name('scan');
    Route::post('/lookup', [App\Http\Controllers\Driver\DriverController::class, 'lookupDo'])->name('lookup');
    Route::patch('/confirm/{deliveryOrder}', [App\Http\Controllers\Driver\DriverController::class, 'confirmArrival'])->name('confirm');
});

// CRM
Route::middleware(['auth'])->prefix('crm')->name('crm.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\CRM\CrmDashboardController::class, 'index'])->name('dashboard');
    Route::resource('leads', LeadController::class);
    Route::resource('opportunities', OpportunityController::class);
    Route::resource('campaigns', CampaignController::class);
});

// Project Management Module
Route::prefix('projects')->name('projects.')->middleware(['auth'])->group(function () {
    Route::get('/', [ProjectController::class, 'index'])->name('index');
    Route::get('/create', [ProjectController::class, 'create'])->name('create');
    Route::post('/', [ProjectController::class, 'store'])->name('store');
    Route::get('/{project}', [ProjectController::class, 'show'])->name('show');
    Route::put('/{project}', [ProjectController::class, 'update'])->name('update');
    Route::delete('/{project}', [ProjectController::class, 'destroy'])->name('destroy');

    // Tasks
    Route::post('/{project}/tasks', [ProjectTaskController::class, 'store'])->name('tasks.store');
    Route::put('/tasks/{task}', [ProjectTaskController::class, 'update'])->name('tasks.update');
    Route::delete('/tasks/{task}', [ProjectTaskController::class, 'destroy'])->name('tasks.destroy');
    Route::post('/tasks/{task}/attachments', [ProjectTaskController::class, 'storeAttachment'])->name('tasks.attachments.store');
    Route::delete('/tasks/attachments/{attachment}', [ProjectTaskController::class, 'destroyAttachment'])->name('tasks.attachments.destroy');

    // Members
    Route::post('/{project}/members', [ProjectMemberController::class, 'store'])->name('members.store');
    Route::delete('/{project}/members/{user}', [ProjectMemberController::class, 'destroy'])->name('members.destroy');
});

// Costing
Route::middleware(['auth'])->prefix('costing')->name('costing.')->group(function () {
    Route::get('/production', fn () => Inertia::render('Blueprints/Costing', ['title' => 'Production Costing']))->name('production');
    Route::get('/overhead', fn () => Inertia::render('Blueprints/Costing', ['title' => 'Overhead Allocation']))->name('overhead');
    Route::get('/profitability', fn () => Inertia::render('Blueprints/Costing', ['title' => 'Profitability Analytic']))->name('profitability');
});

// Portal
Route::middleware(['auth'])->prefix('portal')->name('portal.')->group(function () {
    Route::get('/supplier', fn () => Inertia::render('Blueprints/Portal', ['title' => 'Supplier Portal']))->name('supplier');
    Route::get('/customer', fn () => Inertia::render('Blueprints/Portal', ['title' => 'Customer Portal']))->name('customer');
    Route::get('/settings', fn () => Inertia::render('Blueprints/Portal', ['title' => 'Portal Settings']))->name('settings');
});

// Settings
Route::middleware(['auth'])->prefix('settings')->name('settings.')->group(function () {
    Route::get('/', fn () => Inertia::render('Settings/Index'))->name('index');
    Route::get('/users', [App\Http\Controllers\Settings\UserController::class, 'index'])->name('users');
    Route::post('/users', [App\Http\Controllers\Settings\UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [App\Http\Controllers\Settings\UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [App\Http\Controllers\Settings\UserController::class, 'destroy'])->name('users.destroy');
    Route::get('/roles', [App\Http\Controllers\Settings\RoleController::class, 'index'])->name('roles');
    Route::post('/roles', [App\Http\Controllers\Settings\RoleController::class, 'store'])->name('roles.store');
    Route::put('/roles/{role}', [App\Http\Controllers\Settings\RoleController::class, 'update'])->name('roles.update');
    Route::delete('/roles/{role}', [App\Http\Controllers\Settings\RoleController::class, 'destroy'])->name('roles.destroy');

    // UAT Scenarios
    Route::get('/uat', [App\Http\Controllers\Settings\UatController::class, 'index'])->name('uat');
    Route::get('/uat/export', [App\Http\Controllers\Settings\UatController::class, 'export'])->name('uat.export');
    Route::put('/uat/{id}', [App\Http\Controllers\Settings\UatController::class, 'update'])->name('uat.update');
    Route::get('/company', [App\Http\Controllers\Settings\CompanyController::class, 'index'])->name('company');
    Route::post('/company', [App\Http\Controllers\Settings\CompanyController::class, 'update'])->name('company.update');
    Route::get('/ai', [App\Http\Controllers\Settings\CompanyController::class, 'aiSettings'])->name('ai.index');
    Route::post('/ai', [App\Http\Controllers\Settings\CompanyController::class, 'updateAiSettings'])->name('ai.update');
    // Document Numbering
    Route::get('/numbering', [App\Http\Controllers\Settings\DocumentNumberingController::class, 'index'])->name('numbering');
    Route::post('/numbering', [App\Http\Controllers\Settings\DocumentNumberingController::class, 'store'])->name('numbering.store');
    Route::put('/numbering/{documentNumbering}', [App\Http\Controllers\Settings\DocumentNumberingController::class, 'update'])->name('numbering.update');
    Route::get('/numbering/preview/{code}', [App\Http\Controllers\Settings\DocumentNumberingController::class, 'preview'])->name('numbering.preview');
    
    // Regional & Tax Settings
    Route::get('/regional', [App\Http\Controllers\Settings\RegionalTaxController::class, 'index'])->name('regional');
    Route::post('/regional/tax-rates', [App\Http\Controllers\Settings\RegionalTaxController::class, 'storeTaxRate'])->name('regional.tax-rates.store');
    Route::put('/regional/tax-rates/{taxRate}', [App\Http\Controllers\Settings\RegionalTaxController::class, 'updateTaxRate'])->name('regional.tax-rates.update');
    Route::delete('/regional/tax-rates/{taxRate}', [App\Http\Controllers\Settings\RegionalTaxController::class, 'deleteTaxRate'])->name('regional.tax-rates.delete');
    Route::put('/regional/settings', [App\Http\Controllers\Settings\RegionalTaxController::class, 'updateSettings'])->name('regional.settings.update');
    
    // System Preferences
    Route::get('/preferences', [App\Http\Controllers\Settings\SystemPreferencesController::class, 'index'])->name('preferences');
    Route::put('/preferences', [App\Http\Controllers\Settings\SystemPreferencesController::class, 'update'])->name('preferences.update');
    
    // Workflow Approval
    Route::get('/workflow', [App\Http\Controllers\Settings\WorkflowController::class, 'index'])->name('workflow');
    Route::post('/workflow', [App\Http\Controllers\Settings\WorkflowController::class, 'store'])->name('workflow.store');
    Route::put('/workflow/{workflow}', [App\Http\Controllers\Settings\WorkflowController::class, 'update'])->name('workflow.update');
    Route::delete('/workflow/{workflow}', [App\Http\Controllers\Settings\WorkflowController::class, 'destroy'])->name('workflow.destroy');
    Route::post('/workflow/{workflow}/toggle', [App\Http\Controllers\Settings\WorkflowController::class, 'toggle'])->name('workflow.toggle');
    
    // Import & Export
    Route::get('/io', [App\Http\Controllers\Settings\ImportExportController::class, 'index'])->name('io');
    Route::get('/io/template/{type}', [App\Http\Controllers\Settings\ImportExportController::class, 'downloadTemplate'])->name('io.template');
    Route::get('/io/export/{type}', [App\Http\Controllers\Settings\ImportExportController::class, 'export'])->name('io.export');
    Route::post('/io/import/{type}', [App\Http\Controllers\Settings\ImportExportController::class, 'import'])->name('io.import');
    Route::post('/io/preview/{type}', [App\Http\Controllers\Settings\ImportExportController::class, 'preview'])->name('io.preview');
    
    // Database Management
    Route::get('/database', [App\Http\Controllers\Settings\DatabaseManagementController::class, 'index'])->name('database');
    Route::post('/database/backup', [App\Http\Controllers\Settings\DatabaseManagementController::class, 'backup'])->name('database.backup');
    Route::get('/database/download/{filename}', [App\Http\Controllers\Settings\DatabaseManagementController::class, 'download'])->name('database.download');
    Route::delete('/database/backup/{filename}', [App\Http\Controllers\Settings\DatabaseManagementController::class, 'deleteBackup'])->name('database.delete');
    Route::post('/database/restore', [App\Http\Controllers\Settings\DatabaseManagementController::class, 'restore'])->name('database.restore');
    Route::post('/database/upload-restore', [App\Http\Controllers\Settings\DatabaseManagementController::class, 'uploadRestore'])->name('database.upload-restore');
    Route::post('/database/soft-reset', [App\Http\Controllers\Settings\DatabaseManagementController::class, 'softReset'])->name('database.soft-reset');
    Route::post('/database/hard-reset', [App\Http\Controllers\Settings\DatabaseManagementController::class, 'hardReset'])->name('database.hard-reset');
    Route::post('/database/module-reset', [App\Http\Controllers\Settings\DatabaseManagementController::class, 'moduleReset'])->name('database.module-reset');
    Route::get('/database/backup-info/{filename}', [App\Http\Controllers\Settings\DatabaseManagementController::class, 'backupInfo'])->name('database.backup-info');
    // System Maintenance (In-App Artisan Commands)
    Route::post('/database/sync-permissions', [App\Http\Controllers\Settings\DatabaseManagementController::class, 'syncPermissions'])->name('database.sync-permissions');
    Route::post('/database/sync-numbering', [App\Http\Controllers\Settings\DatabaseManagementController::class, 'syncDocumentNumbering'])->name('database.sync-numbering');
    Route::post('/database/run-migrations', [App\Http\Controllers\Settings\DatabaseManagementController::class, 'runMigrations'])->name('database.run-migrations');
    Route::post('/database/clear-cache', [App\Http\Controllers\Settings\DatabaseManagementController::class, 'clearCache'])->name('database.clear-cache');
    Route::get('/database/info/{filename}', [App\Http\Controllers\Settings\DatabaseManagementController::class, 'backupInfo'])->name('database.info');

    // WhatsApp / Fonnte Settings
    Route::get('/whatsapp', [App\Http\Controllers\Settings\WhatsappSettingController::class, 'index'])->name('whatsapp.index');
    Route::post('/whatsapp', [App\Http\Controllers\Settings\WhatsappSettingController::class, 'update'])->name('whatsapp.update');
    Route::post('/whatsapp/test', [App\Http\Controllers\Settings\WhatsappSettingController::class, 'testConnection'])->name('whatsapp.test');
});

// Admin
Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    Route::get('/activity-logs/export', [App\Http\Controllers\Admin\ActivityLogController::class, 'export'])->name('activity-logs.export');
    Route::post('/activity-logs/reset', [App\Http\Controllers\Admin\ActivityLogController::class, 'reset'])->name('activity-logs.reset');
    Route::resource('activity-logs', App\Http\Controllers\Admin\ActivityLogController::class)->only(['index', 'show']);
});

// Profile
Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'show'])->name('profile.show');
Route::put('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
Route::put('/profile/password', [App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('profile.password');
Route::post('/logout', [App\Http\Controllers\ProfileController::class, 'logout'])->name('logout');

// Approvals
Route::middleware(['auth'])->prefix('approvals')->name('approvals.')->group(function () {
    Route::get('/pending', [App\Http\Controllers\ApprovalController::class, 'pending'])->name('pending');
    Route::get('/history/{documentType}/{documentId}', [App\Http\Controllers\ApprovalController::class, 'history'])->name('history');
    Route::post('/{approvalRequest}/approve', [App\Http\Controllers\ApprovalController::class, 'approve'])->name('approve');
    Route::post('/{approvalRequest}/reject', [App\Http\Controllers\ApprovalController::class, 'reject'])->name('reject');
});

// Supplier Portal Routes
Route::middleware([
    'auth',
    config('jetstream.auth_session'),
    'verified',
])->prefix('portal')->name('portal.')->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [App\Http\Controllers\Portal\PortalDashboardController::class, 'index'])->name('dashboard');

    // Purchase Orders
    Route::resource('purchase-orders', App\Http\Controllers\Portal\PortalPurchaseOrderController::class)->only(['index', 'show']);
    Route::post('purchase-orders/{order}/acknowledge', [App\Http\Controllers\Portal\PortalPurchaseOrderController::class, 'acknowledge'])->name('purchase-orders.acknowledge');
    Route::post('purchase-orders/{order}/reject', [App\Http\Controllers\Portal\PortalPurchaseOrderController::class, 'reject'])->name('purchase-orders.reject');

    // Deliveries (Surat Jalan)
    Route::get('deliveries/create/{order}', [App\Http\Controllers\Portal\PortalDeliveryController::class, 'create'])->name('deliveries.create');
    Route::get('deliveries/{delivery}/print', [\App\Http\Controllers\Portal\PortalDeliveryController::class, 'print'])->name('deliveries.print');
    Route::resource('deliveries', \App\Http\Controllers\Portal\PortalDeliveryController::class);

    // Invoices
    Route::resource('invoices', App\Http\Controllers\Portal\PortalInvoiceController::class);

    // Schedule
    Route::get('schedule', [App\Http\Controllers\Portal\PortalScheduleController::class, 'index'])->name('schedule.index');

    // Analytics
    Route::get('analytics', [App\Http\Controllers\Portal\PortalAnalyticsController::class, 'index'])->name('analytics.index');

    // Documents
    Route::get('documents', [App\Http\Controllers\Portal\PortalDocumentController::class, 'index'])->name('documents.index');
    Route::post('documents', [App\Http\Controllers\Portal\PortalDocumentController::class, 'store'])->name('documents.store');
    Route::get('documents/{document}/download', [App\Http\Controllers\Portal\PortalDocumentController::class, 'download'])->name('documents.download');
    Route::delete('documents/{document}', [App\Http\Controllers\Portal\PortalDocumentController::class, 'destroy'])->name('documents.destroy');

    // Notifications
    Route::get('notifications', [App\Http\Controllers\Portal\PortalNotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/{id}/read', [App\Http\Controllers\Portal\PortalNotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('notifications/mark-all-read', [App\Http\Controllers\Portal\PortalNotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');

    // Returns
    Route::get('returns', [App\Http\Controllers\Portal\PortalReturnController::class, 'index'])->name('returns.index');
    Route::get('returns/{return}', [App\Http\Controllers\Portal\PortalReturnController::class, 'show'])->name('returns.show');

    // RFQ
    Route::get('rfq', [App\Http\Controllers\Portal\PortalRfqController::class, 'index'])->name('rfq.index');
    Route::get('rfq/{id}', [App\Http\Controllers\Portal\PortalRfqController::class, 'show'])->name('rfq.show');
    // Help / User Guide
    Route::get('help', [App\Http\Controllers\Portal\PortalHelpController::class, 'index'])->name('help.index');

});
