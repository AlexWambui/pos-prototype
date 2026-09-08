<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\Users\UserController;
use App\Http\Controllers\Users\ShiftController;
use App\Http\Controllers\Products\ProductCategoryController;
use App\Http\Controllers\Products\ProductController;

Route::get('/', function() {
    return redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/shifts/open', [ShiftController::class, 'open'])->name('shifts.open');
    Route::post('/shifts', [ShiftController::class, 'store'])->name('shifts.store');
    Route::get('/shifts/close', [ShiftController::class, 'close'])->name('shifts.close');
    Route::put('/shifts/update', [ShiftController::class, 'update'])->name('shifts.update');
    Route::get('/shifts', [ShiftController::class, 'index'])->name('shifts.index');
});

Route::middleware(['auth', 'role:super_admin'])->group(function () {
    Route::resource('branches', BranchController::class)->except('show');
    Route::resource('users', UserController::class)->except('show');
});

Route::middleware(['auth', 'role:super_admin,admin'])->group(function () {
    Route::resource('product-categories', ProductCategoryController::class)->except('show');
    Route::resource('products', ProductController::class)->except('show');
    Route::put('products/{product}/toggle-status', [ProductController::class, 'toggleStatus'])->name('products.toggle-status');
});

require __DIR__.'/settings.php';
