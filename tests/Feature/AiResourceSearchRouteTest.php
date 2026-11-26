<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiResourceSearchRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_open_ai_resource_search(): void
    {
        $this->withoutVite();
        $user = User::factory()->create(['has_access' => true]);

        $this->actingAs($user)
            ->get(route('ai-resource-search'))
            ->assertOk()
            ->assertSee('Search AI Resources');
    }
}
