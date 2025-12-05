<?php
 
use Illuminate\Support\Facades\Route;
 
use Iquesters\UserManagement\Http\Controllers\Auth\RegisteredUserController;
use Iquesters\UserManagement\Http\Controllers\Auth\OtpController;
 
Route::post('register', [RegisteredUserController::class, 'store']);
Route::post('sendOtp', [OtpController::class, 'sendOtp']);