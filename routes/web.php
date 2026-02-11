<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'throttle:60,1'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Clients Resource
    Route::resource('clients', \App\Http\Controllers\ClientController::class);

    // Quotations Resource
    Route::resource('quotations', \App\Http\Controllers\QuotationController::class);
    Route::post('quotations/{quotation}/approve', [\App\Http\Controllers\QuotationController::class, 'approve'])
        ->name('quotations.approve');
    Route::post('quotations/{quotation}/reject', [\App\Http\Controllers\QuotationController::class, 'reject'])
        ->name('quotations.reject');

    // Invoices Resource
    Route::resource('invoices', \App\Http\Controllers\InvoiceController::class);
    Route::post('invoices/{invoice}/issue', [\App\Http\Controllers\InvoiceController::class, 'issue'])
        ->name('invoices.issue');
    Route::post('invoices/{invoice}/cancel', [\App\Http\Controllers\InvoiceController::class, 'cancel'])
        ->name('invoices.cancel');
    Route::post('invoices/from-quotation', [\App\Http\Controllers\InvoiceController::class, 'createFromQuotation'])
        ->name('invoices.from-quotation');

    // Payments Resource
    Route::resource('payments', \App\Http\Controllers\PaymentController::class);
});

require __DIR__.'/auth.php';

// Accounting Export
Route::middleware(['auth', 'throttle:60,1'])->prefix('accounting')->group(function () {
    Route::get('/export/freee', [\App\Http\Controllers\AccountingExportController::class, 'exportFreee'])
        ->name('accounting.export.freee')
        ->middleware('can:view-reports');
    
    Route::get('/export/moneyforward', [\App\Http\Controllers\AccountingExportController::class, 'exportMoneyForward'])
        ->name('accounting.export.moneyforward')
        ->middleware('can:view-reports');
});
