<?php

use App\Http\Controllers\CaseDocumentController;
use App\Http\Controllers\UserController;
use App\Livewire\Workspace;
use Illuminate\Support\Facades\Route;

Route::get('/', Workspace::class)->middleware('auth')->name('workspace');

Route::resource('users', UserController::class)
    ->except('show')
    ->middleware(['auth', 'can:manage-users']);

Route::middleware('auth')->prefix('case-documents')->name('case-documents.')->group(function () {
    Route::get('/{document}/preview', [CaseDocumentController::class, 'preview'])->name('preview');
    Route::get('/{document}/download', [CaseDocumentController::class, 'download'])->name('download');
});
