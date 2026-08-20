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
use Iquesters\UserManagement\Http\Controllers\Auth\OtpController;
use Iquesters\UserManagement\Http\Controllers\Auth\SetupController;
use Iquesters\UserManagement\Http\Controllers\Auth\UnifiedAuthController;

use Iquesters\UserManagement\Http\Controllers\ProfileController;
use Iquesters\UserManagement\Http\Controllers\MediaController;

Route::middleware('web')->group(function () {
    
    Route::middleware('guest')->group(function () {

        Route::get('/setup', [SetupController::class, 'show'])->name('ui.setup');
        Route::post('/setup', [SetupController::class, 'store'])->name('ui.setup.store');
        
        // Register & Login
        Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
        Route::post('register', [RegisteredUserController::class, 'store']);
        
        Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('login', [AuthenticatedSessionController::class, 'store']);
        Route::get('auth/unified', [UnifiedAuthController::class, 'show'])->name('auth.unified');
        Route::get('auth/unified/country', [UnifiedAuthController::class, 'country'])->name('auth.unified.country');
        Route::post('auth/identify', [UnifiedAuthController::class, 'identify'])->name('auth.unified.identify');
        Route::post('auth/otp/send', [UnifiedAuthController::class, 'sendOtp'])->name('auth.unified.otp.send');
        Route::post('auth/otp/verify', [UnifiedAuthController::class, 'verifyOtp'])->name('auth.unified.otp.verify');
        Route::post('auth/otp/resend', [UnifiedAuthController::class, 'resendOtp'])->name('auth.unified.otp.resend');
        Route::post('auth/register/complete', [UnifiedAuthController::class, 'completeRegistration'])->name('auth.unified.register.complete');

        // Forgot/Reset Password
        Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
        Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
        Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
        Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');

        // 🔹 Google Login Routes
        Route::get('auth/google/redirect', [GoogleController::class, 'google_redirect'])->name('google.redirect');
        Route::get('auth/google/callback', [GoogleController::class, 'google_callback'])->name('google.callback');
        Route::post('auth/google/onetap', [GoogleController::class, 'google_onetap_callback'])->name('google.onetap');

        // WhatsApp OTP Login Routes
        Route::post('auth/whatsapp/send-otp', [OtpController::class, 'sendOtp'])->name('whatsapp.otp.send');
        Route::post('auth/whatsapp/verify-otp', [OtpController::class, 'verifyOtp'])->name('whatsapp.otp.verify');
        Route::post('auth/whatsapp/resend-otp', [OtpController::class, 'resendOtp'])->name('whatsapp.otp.resend');
        Route::post('auth/whatsapp/register/send-otp', [OtpController::class, 'sendRegistrationOtp'])->name('whatsapp.register.send');
        Route::post('auth/whatsapp/register/verify-otp', [OtpController::class, 'verifyRegistrationOtp'])->name('whatsapp.register.verify');
        Route::post('auth/whatsapp/register/complete', [OtpController::class, 'completeRegistration'])->name('whatsapp.register.complete');
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
            Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
            Route::post('/create-organisation', [DashboardController::class, 'createOrganisation'])->name('dashboard.create-organisation');
        });
        Route::get('/profile-image', [DashboardController::class, 'profileImage'])->name('profile-image');

        // User Profile Update
        Route::patch('/profile/update', [ProfileController::class, 'update'])->name('profile.update');

        Route::get('/settings', [ProfileController::class, 'settings'])->name('settings');
        Route::get('/myprofile', [ProfileController::class, 'myprofile'])->name('myprofile');
        Route::post('/remove-profile-picture', [MediaController::class, 'removeProfilePicture']);

        Route::get('/media/library', [MediaController::class, 'library'])->name('media.library');
        // Media Related
        Route::get('/media/download', [MediaController::class, 'download'])->name('media.download');
        Route::post('/media/upload', [MediaController::class, 'upload'])->name('media.upload');
    });
});
