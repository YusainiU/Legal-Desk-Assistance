<?php

use App\Livewire\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::view('/login', 'login')->middleware('guest')->name('login');
Route::post('/login', function (Request $request) {
    $credentials = $request->validate(['email' => 'required|email', 'password' => 'required|string']);

    if (! Auth::attempt($credentials)) {
        return back()->withErrors(['email' => 'Check your email and password.'])->onlyInput('email');
    }

    $request->session()->regenerate();

    return redirect()->intended(route('workspace'));
})->middleware(['guest', 'throttle:8,1']);
Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

Route::get('/', Workspace::class)->middleware('auth')->name('workspace');
