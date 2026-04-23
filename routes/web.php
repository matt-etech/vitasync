<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CarePlanController;
use App\Http\Controllers\ClientAssessmentController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HomeUserController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VisitController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::delete('/impersonation', [ImpersonationController::class, 'destroy'])->name('impersonation.destroy');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('homes', HomeController::class)->except(['show'])->middleware('permission:homes.manage');
    Route::prefix('homes/{home}')->name('homes.')->middleware('permission:home_users.manage')->group(function (): void {
        Route::post('/users/{user}/impersonate', [ImpersonationController::class, 'store'])
            ->name('users.impersonate')
            ->middleware('permission:users.impersonate');
        Route::resource('users', HomeUserController::class)->except(['show'])->names('users');
    });
    Route::resource('clients', ClientController::class)->middleware('permission:clients.manage');
    Route::resource('care-plans', CarePlanController::class)->only(['index', 'store', 'update', 'destroy'])->middleware('permission:care_plans.manage');
    Route::resource('visits', VisitController::class)->only(['index', 'store', 'update', 'destroy'])->middleware('permission:clients.manage');
    Route::prefix('clients/{client}/assessment')->name('clients.assessments.')->middleware('permission:clients.manage')->group(function (): void {
        Route::get('/', [ClientAssessmentController::class, 'edit'])->name('edit');
        Route::put('/', [ClientAssessmentController::class, 'update'])->name('update');
        Route::post('/submit', [ClientAssessmentController::class, 'submit'])->name('submit');
        Route::post('/approve', [ClientAssessmentController::class, 'approve'])->name('approve');
        Route::post('/decline', [ClientAssessmentController::class, 'decline'])->name('decline');
    });

    Route::resource('roles', RoleController::class)->except(['show'])->middleware('permission:roles.manage');
    Route::resource('permissions', PermissionController::class)->except(['show'])->middleware('permission:permissions.manage');
    Route::resource('users', UserController::class)->except(['show'])->middleware('permission:users.manage');
});
