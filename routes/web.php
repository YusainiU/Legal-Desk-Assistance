<?php

use App\Http\Controllers\CaseDocumentController;
use App\Http\Controllers\CaseSummaryController;
use App\Http\Controllers\CaseTimeChargeExportController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\FirmSettingsController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\UserController;
use App\Livewire\KnowledgeBase;
use App\Livewire\Workspace;
use Illuminate\Support\Facades\Route;

Route::get('/', Workspace::class)->middleware('auth')->name('workspace');
Route::view('/getting-started', 'getting-started')->middleware('auth')->name('getting-started');
Route::get('/knowledge-base', KnowledgeBase::class)->middleware('auth')->name('knowledge-base');

Route::get('/cases/{matter}/summary', [CaseSummaryController::class, 'show'])
    ->whereNumber('matter')->middleware('auth')->name('cases.summary');

Route::get('/cases/{matter}/time-charges.csv', [CaseTimeChargeExportController::class, 'show'])
    ->whereNumber('matter')->middleware('auth')->name('cases.time-charges.csv');

Route::resource('contacts', ContactController::class)
    ->only(['index', 'edit', 'update'])
    ->middleware('auth');

Route::get('/teams', [TeamController::class, 'index'])->middleware('auth')->name('teams.index');
Route::resource('teams', TeamController::class)->only(['create', 'store', 'edit', 'update'])
    ->middleware(['auth', 'can:manage-users']);

Route::resource('users', UserController::class)
    ->except('show')
    ->middleware(['auth', 'can:manage-users']);

Route::middleware('auth')->prefix('case-documents')->name('case-documents.')->group(function () {
    Route::get('/{document}/preview', [CaseDocumentController::class, 'preview'])->name('preview');
    Route::get('/{document}/download', [CaseDocumentController::class, 'download'])->name('download');
});

Route::middleware(['auth', 'can:manage-users'])->group(function () {
    Route::get('/settings/firm', [FirmSettingsController::class, 'edit'])->name('settings.firm.edit');
    Route::put('/settings/firm', [FirmSettingsController::class, 'update'])->middleware('can:update-firm-settings')->name('settings.firm.update');
});
