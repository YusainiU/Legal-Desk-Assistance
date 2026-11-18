<?php

use App\Livewire\Workspace;
use Illuminate\Support\Facades\Route;

Route::get('/', Workspace::class)->middleware('auth')->name('workspace');
