<?php

use App\Http\Controllers\CaseDocumentController;
use App\Http\Controllers\CaseSummaryController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\FirmSettingsController;
use App\Http\Controllers\UserController;
use App\Livewire\Workspace;
use Illuminate\Support\Facades\Route;

Route::get('/', Workspace::class)->middleware('auth')->name('workspace');
Route::view('/getting-started', 'getting-started')->middleware('auth')->name('getting-started');

Route::get('/cases/{matter}/summary', [CaseSummaryController::class, 'show'])
    ->whereNumber('matter')->middleware('auth')->name('cases.summary');

Route::resource('contacts', ContactController::class)
    ->only(['index', 'edit', 'update'])
    ->middleware('auth');

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
