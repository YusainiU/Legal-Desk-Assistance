<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class WelcomePageTest extends TestCase
{
    public function test_serves_public_welcome_page_to_guests(): void
    {
        $this->withoutVite();
        $this->get('/')->assertOk()
            ->assertSee('Legal Desk Assistance')
            ->assertSee('AI Legal Search')
            ->assertSee('From paper files');
    }

    public function test_keeps_case_workspace_behind_authentication(): void
    {
        $this->assertSame('workspace', Route::getRoutes()->getByName('workspace')->uri());
        $this->get('/workspace')->assertRedirect('/login');
    }
}
