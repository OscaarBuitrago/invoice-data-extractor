<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\ClientCompanies\ClientCompanyController;
use App\Http\Controllers\Web\Consultancies\ConsultancyController;
use App\Http\Controllers\Web\Context\CompanyContextController;
use App\Http\Controllers\Web\Exports\SageExportController;
use App\Http\Controllers\Web\Invoices\InvoiceController;
use App\Http\Controllers\Web\Invoices\UploadBatchController;
use App\Http\Controllers\Web\Users\UserController;
use App\Http\Middleware\RequiresCompanyContext;
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

Route::middleware('auth')->group(function () {
    Route::get('/seleccionar-empresa', [CompanyContextController::class, 'select'])->name('context.select');
    Route::post('/seleccionar-empresa', [CompanyContextController::class, 'store'])->name('context.store');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified', RequiresCompanyContext::class])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('consultancies', ConsultancyController::class)->only(['index', 'create', 'store']);
    Route::resource('users', UserController::class)->only(['index', 'create', 'store', 'edit', 'update']);
    Route::resource('client-companies', ClientCompanyController::class)->only(['index', 'create', 'store']);

    Route::middleware(RequiresCompanyContext::class)->group(function (): void {
        Route::post('/sage-exports', [SageExportController::class, 'store'])->name('sage-exports.store');
        Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/invoices/upload', [UploadBatchController::class, 'create'])->name('invoices.upload.create');
        Route::post('/invoices/upload', [UploadBatchController::class, 'store'])->name('invoices.upload.store');
        Route::get('/invoices/batches/{batch}/progress', [UploadBatchController::class, 'progress'])->name('invoices.batches.progress');
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::put('/invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
    });

    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
    Route::get('/invoices/batches/{batch}/status', [UploadBatchController::class, 'status'])->name('invoices.batches.status');
});

require __DIR__.'/auth.php';
