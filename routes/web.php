<?php

use App\Http\Controllers\DashboardController;
use App\Livewire\OrdersTable;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::livewire('orders', OrdersTable::class)->name('orders.index');
});

require __DIR__.'/settings.php';
