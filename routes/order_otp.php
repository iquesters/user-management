<?php

use Illuminate\Support\Facades\Route;
use Iquesters\UserManagement\Http\Controllers\OrderOtpController;
use Iquesters\UserManagement\Http\Middleware\RequireOrderOtpToken;

// Registered under API middleware by UserManagementServiceProvider::boot.
// No public login/session/CSRF bypass and no user-facing OTP endpoint.
Route::middleware([RequireOrderOtpToken::class, 'throttle:30,1'])->group(function () {
    Route::post('/issue', [OrderOtpController::class, 'issue']);
    Route::post('/verify', [OrderOtpController::class, 'verify']);
});
