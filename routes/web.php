<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IdentityAccess\AuthenticatedSessionController;
use App\Http\Controllers\IdentityAccess\EmailVerificationController;
use App\Http\Controllers\IdentityAccess\EmailVerificationNoticeController;
use App\Http\Controllers\IdentityAccess\EmailVerificationNotificationController;
use App\Http\Controllers\IdentityAccess\NewPasswordController;
use App\Http\Controllers\IdentityAccess\PasswordResetLinkController;
use App\Http\Controllers\IdentityAccess\RegisteredOrganizationUserController;
use App\Http\Controllers\Localization\LocaleController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::post('/locale', LocaleController::class)
    ->middleware('throttle:20,1')
    ->name('locale.update');

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisteredOrganizationUserController::class, 'create'])
        ->name('register.create');
    Route::post('/register', [RegisteredOrganizationUserController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('register.store');

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])
        ->name('login.create');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->name('login.store');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:password-reset-link')
        ->name('password.email');

    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:password-reset')
        ->name('password.update');
});

Route::get('/email/verify/{organizationUser}/{token}', EmailVerificationController::class)
    ->middleware('throttle:6,1')
    ->whereUuid('organizationUser')
    ->name('verification.verify');

Route::middleware(['auth', 'auth.version'])->group(function (): void {
    Route::get('/email/verify', EmailVerificationNoticeController::class)
        ->name('verification.notice');
    Route::post('/email/verification-notification', EmailVerificationNotificationController::class)
        ->middleware('throttle:3,1')
        ->name('verification.send');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');

    Route::get('/dashboard', DashboardController::class)
        ->middleware('verified')
        ->name('dashboard');
});
