<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ModuleController;
use App\Http\Controllers\Admin\UploadController;
use App\Http\Controllers\ApartmentSearchController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InformationController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\ReservationController;
use App\Support\AdminModules;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('information/{information:slug}', [InformationController::class, 'show'])->name('information.show');

Route::get('apartments', [ApartmentSearchController::class, 'index'])->name('apartments.index');
Route::get('apartments/{apartment:slug}', [ApartmentSearchController::class, 'show'])->name('apartments.show');
Route::post('apartments/{apartment:slug}/availability', [ApartmentSearchController::class, 'availability'])->name('apartments.availability');
Route::get('apartments/{apartment}/reserve', [ReservationController::class, 'create'])->name('reservations.create');
Route::post('apartments/{apartment}/coupon', [ReservationController::class, 'coupon'])->name('reservations.coupon');
Route::post('apartments/{apartment}/reserve', [ReservationController::class, 'store'])->name('reservations.store');
Route::post('reservations/payment-confirm', [ReservationController::class, 'confirmPayment'])->name('reservations.payment-confirm');
Route::get('reservations/payment-return', [ReservationController::class, 'paymentReturn'])->name('reservations.payment-return');
Route::get('reservations/receipt', [ReservationController::class, 'receiptByReference'])->name('reservations.receipt-reference');
Route::get('reservations/{invoice}/receipt', [ReservationController::class, 'receipt'])->name('reservations.receipt');
Route::post('webhook/payment', PaymentWebhookController::class)->name('webhooks.paystack');
Route::post('webhooks/paystack', PaymentWebhookController::class);

Route::get('login', [LoginController::class, 'create'])->name('login');
Route::post('login', [LoginController::class, 'store'])->name('login.store');
Route::redirect('admin/login', '/login');

Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::post('upload/image', [UploadController::class, 'image'])->name('upload.image');
    Route::post('apartments/check-availability', [ModuleController::class, 'checkApartmentAvailability'])->name('apartments.check-availability');

    Route::get('{module}', [ModuleController::class, 'index'])
        ->where('module', AdminModules::allowedSlugs())
        ->name('modules.show');

    Route::post('{module}', [ModuleController::class, 'store'])
        ->where('module', AdminModules::allowedSlugs())
        ->name('modules.store');

    Route::get('{module}/create', [ModuleController::class, 'create'])
        ->where('module', AdminModules::allowedSlugs())
        ->name('modules.create');

    Route::delete('{module}/bulk-destroy', [ModuleController::class, 'bulkDestroy'])
        ->where('module', AdminModules::allowedSlugs())
        ->name('modules.bulk-destroy');

    Route::get('{module}/{record}', [ModuleController::class, 'show'])
        ->where('module', AdminModules::allowedSlugs())
        ->name('modules.record.show');

    Route::get('{module}/{record}/edit', [ModuleController::class, 'edit'])
        ->where('module', AdminModules::allowedSlugs())
        ->name('modules.record.edit');

    Route::match(['put', 'patch'], '{module}/{record}', [ModuleController::class, 'update'])
        ->where('module', AdminModules::allowedSlugs())
        ->name('modules.record.update');

    Route::delete('{module}/{record}', [ModuleController::class, 'destroy'])
        ->where('module', AdminModules::allowedSlugs())
        ->name('modules.record.destroy');
});
