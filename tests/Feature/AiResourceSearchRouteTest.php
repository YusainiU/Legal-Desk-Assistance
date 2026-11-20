<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiResourceSearchRouteTest extends TestCase
{
    public function test_authenticated_user_can_open_ai_resource_search(): void
    {
        $user = User::firstOrFail();

        $this->actingAs($user)
            ->get(route('ai-resource-search'))
            ->assertOk()
            ->assertSee('Search AI Resources');
    }
}
