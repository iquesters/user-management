<?php

use Illuminate\Support\Facades\Route;
use Iquesters\UserManagement\Http\Controllers\Auth\AuthenticatedSessionController;
use Iquesters\UserManagement\Http\Controllers\Auth\ConfirmablePasswordController;
use Iquesters\UserManagement\Http\Controllers\DashboardController;
use Iquesters\UserManagement\Http\Controllers\Auth\EmailVerificationNotificationController;
use Iquesters\UserManagement\Http\Controllers\Auth\EmailVerificationPromptController;
use Iquesters\UserManagement\Http\Controllers\Auth\NewPasswordController;
use Iquesters\UserManagement\Http\Controllers\Auth\PasswordController;
use Iquesters\UserManagement\Http\Controllers\Auth\PasswordResetLinkController;
use Iquesters\UserManagement\Http\Controllers\Auth\RegisteredUserController;
use Iquesters\UserManagement\Http\Controllers\Auth\VerifyEmailController;
use Iquesters\UserManagement\Http\Controllers\Auth\GoogleController;

Route::middleware('web')->group(function () {
    
    Route::middleware('guest')->group(function () {
        // Register & Login
        Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
        Route::post('register', [RegisteredUserController::class, 'store']);
        
        Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('login', [AuthenticatedSessionController::class, 'store']);

        // Forgot/Reset Password
        Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
        Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
        Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
        Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');

        // 🔹 Google Login Routes
        Route::get('auth/google/redirect', [GoogleController::class, 'google_redirect'])->name('google.redirect');
        Route::get('auth/google/callback', [GoogleController::class, 'google_callback'])->name('google.callback');
        Route::post('auth/google/onetap', [GoogleController::class, 'google_onetap_callback'])->name('google.onetap');
    });

    Route::middleware('auth')->group(function () {
        // Email Verification
        Route::get('verify-email', EmailVerificationPromptController::class)->name('verification.notice');
        Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
            ->middleware(['signed', 'throttle:6,1'])
            ->name('verification.verify');
        Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
            ->middleware('throttle:6,1')
            ->name('verification.send');

        // Password & Logout
        Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])->name('password.confirm');
        Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);
        Route::put('password', [PasswordController::class, 'update'])->name('password.update');
        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
            
        // Dashboard
        Route::prefix('dashboard')->group(function () {
            Route::get('/show', [DashboardController::class, 'index'])->name('dashboard');
        });
        Route::get('/profile-image', [DashboardController::class, 'profileImage'])->name('profile-image');
    });
});