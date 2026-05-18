<?php

use App\Http\Controllers\Api\CarerLoginController;
use App\Http\Controllers\Api\CarerChangePasswordController;
use App\Http\Controllers\Api\CarerClientsController;
use App\Http\Controllers\Api\CarerIssueReportController;
use App\Http\Controllers\Api\CarerTasksController;
use App\Http\Controllers\Api\CarerTodayController;
use App\Http\Controllers\Api\CarerVisitsController;
use App\Http\Controllers\Api\FamilyLoginController;
use App\Http\Controllers\Api\FamilyChangePasswordController;
use App\Http\Controllers\Api\FamilyPortalController;
use Illuminate\Support\Facades\Route;

Route::post('/carer/login', CarerLoginController::class)->name('api.carer.login');
Route::post('/carer/change-password', CarerChangePasswordController::class)->name('api.carer.change-password');
Route::get('/carer/clients', CarerClientsController::class)->name('api.carer.clients');
Route::get('/carer/tasks', CarerTasksController::class)->name('api.carer.tasks');
Route::post('/carer/issue-reports', CarerIssueReportController::class)->name('api.carer.issue-reports');
Route::get('/carer/today', [CarerTodayController::class, 'show'])->name('api.carer.today');
Route::get('/carer/visits', CarerVisitsController::class)->name('api.carer.visits');
Route::post('/carer/visits/{visit}/check-in', [CarerTodayController::class, 'checkIn'])->name('api.carer.visits.check-in');
Route::post('/carer/visits/{visit}/check-out', [CarerTodayController::class, 'checkOut'])->name('api.carer.visits.check-out');
Route::post('/carer/visits/{visit}/notes', [CarerTodayController::class, 'recordNotes'])->name('api.carer.visits.notes');
Route::post('/carer/visits/{visit}/tasks', [CarerTodayController::class, 'recordTask'])->name('api.carer.visits.tasks');
Route::post('/carer/visits/{visit}/vitals', [CarerTodayController::class, 'recordVitals'])->name('api.carer.visits.vitals');
Route::post('/carer/visits/{visit}/evidence', [CarerTodayController::class, 'recordEvidence'])->name('api.carer.visits.evidence');
Route::post('/carer/visits/{visit}/location-event', [CarerTodayController::class, 'locationEvent'])->name('api.carer.visits.location-event');
Route::post('/family/login', FamilyLoginController::class)->name('api.family.login');
Route::post('/family/change-password', FamilyChangePasswordController::class)->name('api.family.change-password');
Route::get('/family/portal', [FamilyPortalController::class, 'show'])->name('api.family.portal');
