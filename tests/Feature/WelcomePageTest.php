<?php

use App\Livewire\Workspace;
use Illuminate\Support\Facades\Route;

it('serves the public welcome page to guests', function () {
    $this->get('/')->assertOk()
        ->assertSee('Legal Desk Assistance')
        ->assertSee('AI Legal Search')
        ->assertSee('From paper files');
});

it('keeps the case workspace behind authentication', function () {
    expect(Route::getRoutes()->getByName('workspace')->uri())->toBe('workspace');
    $this->get('/workspace')->assertRedirect('/login');
});
