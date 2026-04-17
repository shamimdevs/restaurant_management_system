<?php

use App\Http\Controllers\AccountingController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\KitchenController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TableController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| All routes are prefixed with /api
| Authenticated routes require Sanctum token (Authorization: Bearer <token>)
*/

// ── Auth (public) ─────────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('login',        [AuthController::class, 'login']);
    Route::post('login/pin',    [AuthController::class, 'loginWithPin']);
});

// ── QR Order (public — scanned by customer) ───────────────────────────────
Route::get('qr/{code}/menu',        [TableController::class, 'qrMenuData']);
Route::post('qr/{code}/order',      [PosController::class, 'placeQrOrder']);

// ── Authenticated Routes ──────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // ── Auth ─────────────────────────────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('logout',          [AuthController::class, 'logout']);
        Route::get('me',               [AuthController::class, 'me']);
        Route::put('change-password',  [AuthController::class, 'changePassword']);
    });

    // ── POS ──────────────────────────────────────────────────────────────
    Route::prefix('pos')->group(function () {
        Route::get('items',                [PosController::class, 'getMenuItems']);   // unified browse+search
        Route::get('menu-items',           [PosController::class, 'getMenuItems']);   // alias
        Route::get('today-orders',         [PosController::class, 'todayOrders']);
        Route::post('order',               [PosController::class, 'placeOrder']);     // singular alias (frontend uses this)
        Route::post('orders',              [PosController::class, 'placeOrder']);
        Route::post('orders/{order}/pay',  [PosController::class, 'processPayment']);
        Route::post('orders/{order}/split',[PosController::class, 'splitOrder']);
        Route::post('orders/{order}/void', [PosController::class, 'voidOrder']);
        Route::post('apply-coupon',        [PosController::class, 'applyCoupon']);
    });

    // ── Menu Management ───────────────────────────────────────────────────
    Route::prefix('menu')->group(function () {
        Route::get('items',                    [MenuController::class, 'getItems']);
        Route::post('items',                   [MenuController::class, 'store']);
        Route::put('items/{menuItem}',         [MenuController::class, 'update']);
        Route::delete('items/{menuItem}',      [MenuController::class, 'destroy']);
        Route::patch('items/{menuItem}/toggle',[MenuController::class, 'toggleAvailability']);
        Route::post('categories',              [MenuController::class, 'storeCategory']);
        Route::get('modifier-groups',          [MenuController::class, 'getModifierGroups']);
    });

    // ── Tables & QR ───────────────────────────────────────────────────────
    Route::prefix('tables')->group(function () {
        Route::get('/',                          [TableController::class, 'index']);
        Route::post('/',                         [TableController::class, 'store']);
        Route::put('{table}',                    [TableController::class, 'update']);
        Route::delete('{table}',                 [TableController::class, 'destroy']);
        Route::post('{table}/regenerate-qr',     [TableController::class, 'regenerateQr']);
        Route::post('{table}/open-session',      [TableController::class, 'openSession']);
        Route::post('sessions/{session}/close',  [TableController::class, 'closeSession']);
    });

    // ── Kitchen Display ───────────────────────────────────────────────────
    Route::prefix('kitchen')->group(function () {
        Route::get('tickets',                               [KitchenController::class, 'getTickets']);
        Route::patch('tickets/{ticket}/start',              [KitchenController::class, 'startCooking']);
        Route::patch('tickets/{ticket}/ready',              [KitchenController::class, 'markReady']);
        Route::patch('tickets/{ticket}/served',             [KitchenController::class, 'markServed']);
        Route::patch('tickets/{ticket}/items/{itemId}',     [KitchenController::class, 'updateItemStatus']);
    });

    // ── Inventory ─────────────────────────────────────────────────────────
    Route::prefix('inventory')->group(function () {
        Route::get('ingredients',                          [InventoryController::class, 'getIngredients']);
        Route::post('ingredients',                         [InventoryController::class, 'storeIngredient']);
        Route::put('ingredients/{ingredient}',             [InventoryController::class, 'updateIngredient']);
        Route::get('recipes',                              [InventoryController::class, 'getRecipes']);
        Route::post('recipes',                             [InventoryController::class, 'storeRecipe']);
        Route::put('recipes/{recipe}',                     [InventoryController::class, 'updateRecipe']);
        Route::get('movements',                            [InventoryController::class, 'getMovements']);
        Route::post('adjustments',                         [InventoryController::class, 'createAdjustment']);
        Route::post('adjustments/{adjustment}/approve',    [InventoryController::class, 'approveAdjustment']);
        Route::get('units',                                [InventoryController::class, 'getUnits']);
        Route::get('alerts',                               [InventoryController::class, 'getAlerts']);
        Route::patch('alerts/{alert}/resolve',             [InventoryController::class, 'resolveAlert']);
    });

    // ── Customers / CRM ───────────────────────────────────────────────────
    Route::prefix('customers')->group(function () {
        Route::get('/',                              [CustomerController::class, 'list']);
        Route::post('/',                             [CustomerController::class, 'store']);
        Route::get('search',                         [CustomerController::class, 'searchByPhone']);
        Route::get('stats',                          [CustomerController::class, 'stats']);
        Route::get('{customer}',                     [CustomerController::class, 'show']);
        Route::put('{customer}',                     [CustomerController::class, 'update']);
        Route::post('{customer}/feedback',           [CustomerController::class, 'storeFeedback']);
    });

    // ── Employees & HR ────────────────────────────────────────────────────
    Route::prefix('employees')->group(function () {
        Route::get('/',                              [EmployeeController::class, 'index']);
        Route::post('/',                             [EmployeeController::class, 'store']);
        Route::get('leave-types',                    [EmployeeController::class, 'getLeaveTypes']);
        Route::get('leaves',                         [EmployeeController::class, 'getLeaves']);
        Route::get('payroll',                        [EmployeeController::class, 'getPayroll']);
        Route::get('{employee}',                     [EmployeeController::class, 'show']);
        Route::put('{employee}',                     [EmployeeController::class, 'update']);
        Route::post('attendance',                    [EmployeeController::class, 'markAttendance']);
        Route::get('attendance/report',              [EmployeeController::class, 'getAttendance']);
        Route::post('leave',                         [EmployeeController::class, 'applyLeave']);
        Route::patch('leave/{leave}/action',         [EmployeeController::class, 'approveLeave']);
        Route::post('payroll/generate',              [EmployeeController::class, 'generatePayroll']);
        Route::post('payroll/process',               [EmployeeController::class, 'processPayroll']);
    });

    // ── Orders ───────────────────────────────────────────────────────────
    Route::prefix('orders')->group(function () {
        Route::get('today-stats',          [OrderController::class, 'todayStats']);
        Route::get('/',                    [OrderController::class, 'list']);
        Route::get('{order}',              [OrderController::class, 'show']);
        Route::patch('{order}/status',     [OrderController::class, 'updateStatus']);
    });

    // ── Reports ───────────────────────────────────────────────────────────
    Route::prefix('reports')->group(function () {
        Route::get('sales',             [ReportController::class, 'sales']);
        Route::get('top-items',         [ReportController::class, 'topItems']);
        Route::get('expenses',          [ReportController::class, 'expenses']);
        Route::get('profit-loss',       [ReportController::class, 'profitLoss']);
        Route::get('vat',               [ReportController::class, 'vatReport']);
        Route::get('branch-performance',[ReportController::class, 'branchPerformance']);
    });

    // ── Accounting ────────────────────────────────────────────────────────
    Route::prefix('accounting')->group(function () {
        Route::get('expenses',                    [AccountingController::class, 'getExpenses']);
        Route::post('expenses',                   [AccountingController::class, 'storeExpense']);
        Route::get('expense-categories',          [AccountingController::class, 'getExpenseCategories']);
        Route::get('journals',                    [AccountingController::class, 'getJournalEntries']);
        Route::get('accounts',                    [AccountingController::class, 'getAccounts']);
        Route::get('trial-balance',               [AccountingController::class, 'trialBalance']);
        Route::get('profit-loss',                 [AccountingController::class, 'profitLoss']);
    });

    // ── Promotions ────────────────────────────────────────────────────────
    Route::prefix('promotions')->group(function () {
        Route::post('/',                          [PromotionController::class, 'storePromotion']);
        Route::put('{promotion}',                 [PromotionController::class, 'updatePromotion']);
        Route::patch('{promotion}/toggle',        [PromotionController::class, 'togglePromotion']);
        Route::get('coupons',                     [PromotionController::class, 'getCoupons']);
        Route::post('coupons',                    [PromotionController::class, 'storeCoupon']);
        Route::patch('coupons/{coupon}/toggle',   [PromotionController::class, 'toggleCoupon']);
        Route::post('loyalty',                    [PromotionController::class, 'updateLoyalty']);
        Route::get('generate-code',               [PromotionController::class, 'generateCode']);
    });

    // ── Settings ──────────────────────────────────────────────────────────
    Route::prefix('settings')->group(function () {
        Route::put('/',                           [SettingsController::class, 'update']);
        Route::post('tax-rates',                  [SettingsController::class, 'storeTaxRate']);
        Route::put('tax-rates/{taxRate}',         [SettingsController::class, 'updateTaxRate']);
        Route::post('branches',                   [SettingsController::class, 'storeBranch']);
    });

    // ── Branches (for branch selector) ────────────────────────────────────
    Route::get('branches', [SettingsController::class, 'getBranches']);
});
