<?php

use App\Http\Controllers\KitchenController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TableController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ── Public ────────────────────────────────────────────────────────────────
Route::get('/', fn () => Inertia::render('Auth/Login'))->name('home');

// ── QR Order (public page) ────────────────────────────────────────────────
Route::get('/order/{code}', [TableController::class, 'qrOrderPage'])->name('qr.order');

// ── Auth ──────────────────────────────────────────────────────────────────
Route::get('/login',  fn () => Inertia::render('Auth/Login'))->name('login');
Route::get('/logout', fn () => redirect('/login'))->name('logout.get');

// ── Authenticated Web Pages (Inertia SSR routes) ─────────────────────────
Route::middleware(['auth:sanctum', 'web'])->group(function () {

    Route::get('/dashboard',  fn () => Inertia::render('Dashboard/Index'))->name('dashboard');

    // POS
    Route::get('/pos',       [PosController::class, 'index'])->name('pos.index');

    // Menu
    Route::get('/menu',      [MenuController::class, 'index'])->name('menu.index');

    // Tables
    Route::get('/tables',    [TableController::class, 'index'])->name('tables.index');

    // Kitchen
    Route::get('/kitchen',   [KitchenController::class, 'index'])->name('kitchen.index');

    // Reports
    Route::get('/reports',   [ReportController::class, 'dashboard'])->name('reports.index');

    // Static Inertia pages
    Route::get('/inventory',  fn () => Inertia::render('Inventory/Index'))->name('inventory.index');
    Route::get('/customers',  fn () => Inertia::render('Customers/Index'))->name('customers.index');
    Route::get('/employees',  fn () => Inertia::render('Employees/Index'))->name('employees.index');
    Route::get('/accounting', fn () => Inertia::render('Accounting/Index'))->name('accounting.index');
    Route::get('/settings',   fn () => Inertia::render('Settings/Index'))->name('settings.index');
});
