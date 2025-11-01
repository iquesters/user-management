<?php

use Iquesters\UserManagement\Http\Controllers\PermissionController;
use Iquesters\UserManagement\Http\Controllers\RoleController;
use Iquesters\UserManagement\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Iquesters\UserManagement\Http\Controllers\DashboardController;

Route::middleware('web')->group(function () {
    Route::middleware(['auth'])->group(function () {
        Route::prefix('admin')->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'adminDashboard'])->name('admin.dashboard');
            
            // Role Management
            Route::resource('roles', RoleController::class);
            
            // Custom route to display a role's permissions
            Route::get('roles/{role}/permissions', [RoleController::class, 'permissions'])
            ->name('roles.permissions');
            
            // Permission Management
            Route::resource('permissions', PermissionController::class);
            
            // User Management
            Route::resource('users', UserController::class);
        });
    });
});