<?php

use App\Http\Controllers\UserController;
use App\Livewire\Workspace;
use Illuminate\Support\Facades\Route;

Route::get('/', Workspace::class)->middleware('auth')->name('workspace');

Route::resource('users', UserController::class)
    ->except('show')
    ->middleware(['auth', 'can:manage-users']);
