<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\KitchenController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ReportController;
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
        Route::get('menu-items',           [PosController::class, 'getMenuItems']);
        Route::get('search-items',         [PosController::class, 'searchItems']);
        Route::get('today-orders',         [PosController::class, 'todayOrders']);
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
        Route::get('movements',                            [InventoryController::class, 'getMovements']);
        Route::post('adjustments',                         [InventoryController::class, 'createAdjustment']);
        Route::post('adjustments/{adjustment}/approve',    [InventoryController::class, 'approveAdjustment']);
        Route::get('alerts',                               [InventoryController::class, 'getAlerts']);
        Route::patch('alerts/{alert}/resolve',             [InventoryController::class, 'resolveAlert']);
    });

    // ── Customers / CRM ───────────────────────────────────────────────────
    Route::prefix('customers')->group(function () {
        Route::get('/',                              [CustomerController::class, 'list']);
        Route::post('/',                             [CustomerController::class, 'store']);
        Route::get('search',                         [CustomerController::class, 'searchByPhone']);
        Route::get('{customer}',                     [CustomerController::class, 'show']);
        Route::put('{customer}',                     [CustomerController::class, 'update']);
        Route::post('{customer}/feedback',           [CustomerController::class, 'storeFeedback']);
    });

    // ── Employees & HR ────────────────────────────────────────────────────
    Route::prefix('employees')->group(function () {
        Route::get('/',                              [EmployeeController::class, 'index']);
        Route::post('/',                             [EmployeeController::class, 'store']);
        Route::get('{employee}',                     [EmployeeController::class, 'show']);
        Route::put('{employee}',                     [EmployeeController::class, 'update']);
        Route::post('attendance',                    [EmployeeController::class, 'markAttendance']);
        Route::get('attendance/report',              [EmployeeController::class, 'getAttendance']);
        Route::post('leave',                         [EmployeeController::class, 'applyLeave']);
        Route::patch('leave/{leave}/action',         [EmployeeController::class, 'approveLeave']);
        Route::post('payroll/generate',              [EmployeeController::class, 'generatePayroll']);
        Route::post('payroll/process',               [EmployeeController::class, 'processPayroll']);
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
});
