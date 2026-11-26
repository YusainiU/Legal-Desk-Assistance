<?php

use App\Http\Controllers\CaseDocumentController;
use App\Http\Controllers\CaseSummaryController;
use App\Http\Controllers\CaseTimeChargeExportController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\FirmSettingsController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\UserController;
use App\Livewire\Workspace;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('welcome');
Route::get('/dashboard', \App\Livewire\Dashboard::class)->middleware(['auth', \App\Http\Middleware\EnforcePageAccess::class])->name('dashboard');
Route::get('/workspace', Workspace::class)->middleware(['auth', \App\Http\Middleware\EnforcePageAccess::class])->name('workspace');
Route::view('/getting-started', 'getting-started')->middleware(['auth', \App\Http\Middleware\EnforcePageAccess::class])->name('getting-started');
Route::view('/knowledge-base/guide', 'knowledge-base-guide')->middleware(['auth', \App\Http\Middleware\EnforcePageAccess::class])->name('knowledge-base.guide');

Route::get('/cases/{matter}/references-report', [\App\Http\Controllers\CaseReferenceExportController::class, 'show'])
    ->whereNumber('matter')->middleware(['auth', \App\Http\Middleware\EnforcePageAccess::class])->name('cases.references-report');

Route::get('/cases/{matter}/summary', [CaseSummaryController::class, 'show'])
    ->whereNumber('matter')->middleware(['auth', \App\Http\Middleware\EnforcePageAccess::class])->name('cases.summary');

Route::get('/cases/{matter}/time-charges.csv', [CaseTimeChargeExportController::class, 'show'])
    ->whereNumber('matter')->middleware(['auth', \App\Http\Middleware\EnforcePageAccess::class])->name('cases.time-charges.csv');

Route::resource('contacts', ContactController::class)
    ->only(['index', 'edit', 'update'])
    ->middleware(['auth', \App\Http\Middleware\EnforcePageAccess::class]);

Route::get('/teams', [TeamController::class, 'index'])->middleware(['auth', \App\Http\Middleware\EnforcePageAccess::class])->name('teams.index');
Route::resource('teams', TeamController::class)->only(['create', 'store', 'edit', 'update'])
    ->middleware(['auth', \App\Http\Middleware\EnforcePageAccess::class, 'can:manage-users']);

Route::resource('users', UserController::class)
    ->except('show')
    ->middleware(['auth', \App\Http\Middleware\EnforcePageAccess::class, 'can:manage-users']);

Route::middleware(['auth', \App\Http\Middleware\EnforcePageAccess::class])->prefix('case-documents')->name('case-documents.')->group(function () {
    Route::get('/{document}/preview', [CaseDocumentController::class, 'preview'])->name('preview');
    Route::get('/{document}/download', [CaseDocumentController::class, 'download'])->name('download');
});

Route::middleware(['auth', \App\Http\Middleware\EnforcePageAccess::class, 'can:manage-users'])->group(function () {
    Route::get('/settings/firm', [FirmSettingsController::class, 'edit'])->name('settings.firm.edit');
    Route::put('/settings/firm', [FirmSettingsController::class, 'update'])->middleware('can:update-firm-settings')->name('settings.firm.update');
});

Route::get('/admin/login-audit', [\App\Http\Controllers\LoginAuditController::class, 'index'])
    ->middleware(['auth', \App\Http\Middleware\EnforcePageAccess::class, 'can:view-login-audit'])->name('admin.login-audit');

Route::middleware(['auth', 'can:manage-page-permissions'])->prefix('admin/page-permissions')->name('admin.page-permissions.')->group(function () {
    Route::get('/', [\App\Http\Controllers\PagePermissionController::class, 'index'])->name('index');
    Route::put('/pages/{page}', [\App\Http\Controllers\PagePermissionController::class, 'update'])->name('update');
    Route::post('/groups', [\App\Http\Controllers\PagePermissionController::class, 'saveGroup'])->name('groups.store');
    Route::put('/groups/{group}', [\App\Http\Controllers\PagePermissionController::class, 'saveGroup'])->whereNumber('group')->name('groups.update');
    Route::delete('/groups/{group}', [\App\Http\Controllers\PagePermissionController::class, 'deleteGroup'])->whereNumber('group')->name('groups.delete');
});
