<?php

use Illuminate\Support\Facades\Route;
use Modules\StoreBranch\Http\Controllers\BranchController;

Route::middleware('role:super_admin')
    ->prefix('branches')
    ->name('branches.')
    ->controller(BranchController::class)
    ->group(function () 
{
    Route::get('/', 'index')->name('index');
    Route::get('/create', 'create')->name('create');
    Route::post('/', 'store')->name('store');
    Route::get('/{branch:uuid}/edit', 'edit')->name('edit');
    Route::put('/{branch:uuid}', 'update')->name('update');
    Route::delete('/{branch:uuid}', 'destroy')->name('destroy');
});