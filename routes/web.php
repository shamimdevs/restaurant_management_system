<?php

use App\Http\Controllers\AccountingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\KitchenController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TableController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ── Public ────────────────────────────────────────────────────────────────
Route::get('/', fn () => Inertia::render('Auth/Login'))->name('home');

// ── QR Order (public page — scanned by customer) ──────────────────────────
Route::get('/order/{code}', [TableController::class, 'qrOrderPage'])->name('qr.order');

// ── Auth ──────────────────────────────────────────────────────────────────
Route::get('/login',  fn () => Inertia::render('Auth/Login'))->name('login');
Route::post('/login', function (\Illuminate\Http\Request $request) {
    $credentials = $request->validate([
        'email'    => 'required|email',
        'password' => 'required|string',
    ]);

    if (! Auth::attempt($credentials, $request->boolean('remember'))) {
        return back()->withErrors(['email' => 'The provided credentials are incorrect.'])->onlyInput('email');
    }

    $request->session()->regenerate();

    return redirect()->intended('/dashboard');
})->name('login.post');

Route::post('/logout', function () {
    auth()->guard('web')->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/login');
})->name('logout');
Route::get('/logout', fn () => redirect('/login'))->name('logout.get');

// ── Authenticated Web Pages ───────────────────────────────────────────────
Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // POS
    Route::get('/pos', [PosController::class, 'index'])->name('pos.index');

    // Menu Management
    Route::get('/menu', [MenuController::class, 'index'])->name('menu.index');

    // Table & Floor Plan
    Route::get('/tables', [TableController::class, 'index'])->name('tables.index');

    // Order History
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');

    // Kitchen Display
    Route::get('/kitchen', [KitchenController::class, 'index'])->name('kitchen.index');

    // Inventory
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');

    // Customers / CRM
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');

    // Employees / HR
    Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');

    // Accounting
    Route::get('/accounting', [AccountingController::class, 'index'])->name('accounting.index');

    // Promotions
    Route::get('/promotions', [PromotionController::class, 'index'])->name('promotions.index');

    // Reports
    Route::get('/reports', [ReportController::class, 'dashboard'])->name('reports.index');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
});
