<?php

use Illuminate\Support\Facades\Route;
use Iquesters\UserManagement\Http\Controllers\DashboardController;
use Iquesters\UserManagement\Http\Controllers\PermissionController;
use Iquesters\UserManagement\Http\Controllers\RoleController;
use Iquesters\UserManagement\Http\Controllers\RoleFormController;
use Iquesters\UserManagement\Http\Controllers\UserController;

Route::middleware('web')->group(function () {
    Route::middleware(['auth', 'ensure.admin'])->group(function () {
        Route::prefix('admin')->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'adminDashboard'])->name('admin.dashboard');

            // "role-details" form schema: role + permissions as JSON (view / edit).
            Route::post('roles/details', [RoleFormController::class, 'store'])->name('roles.details.store');
            Route::get('roles/{role}/details', [RoleFormController::class, 'show'])->name('roles.details');
            Route::put('roles/{role}/details', [RoleFormController::class, 'update'])->name('roles.details.update');
            Route::get('permission-options', [RoleFormController::class, 'permissionOptions'])->name('permissions.options');

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
