<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoAccountExpiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_demo_is_excluded_by_provider_before_cleanup(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['demo_expires_at' => now()->subMinute()])->save();

        $this->assertNull(Auth::guard('web')->getProvider()->retrieveById($user->id));
        $this->assertNull(Auth::guard('web')->getProvider()->retrieveByCredentials(['email' => $user->email]));
    }

    public function test_cleanup_rotates_password_once_and_preserves_regular_users(): void
    {
        $demo = User::factory()->create(['password' => 'demo-password-123']);
        $demo->forceFill(['demo_expires_at' => now()->subMinute()])->save();
        $regular = User::factory()->create();
        $regularHash = $regular->password;

        $this->artisan('legal-desk:expire-demo-users')->assertSuccessful();
        $demo->refresh();
        $this->assertFalse($demo->has_access);
        $this->assertNotNull($demo->demo_expired_at);
        $this->assertFalse(Hash::check('demo-password-123', $demo->password));
        $hash = $demo->password;
        $this->artisan('legal-desk:expire-demo-users')->assertSuccessful();
        $this->assertSame($hash, $demo->fresh()->password);
        $this->assertSame($regularHash, $regular->fresh()->password);
    }

    public function test_admin_cannot_create_demo_account(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Demo', 'email' => 'demo@example.test',
            'password' => 'demo-password-123', 'password_confirmation' => 'demo-password-123',
            'has_access' => 1, 'demo_expires_at' => now()->addMonth()->format('Y-m-d\TH:i'),
        ])->assertForbidden();
    }

    public function test_super_admin_can_create_demo_account(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);
        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Demo', 'email' => 'demo@example.test',
            'password' => 'demo-password-123', 'password_confirmation' => 'demo-password-123',
            'has_access' => 1, 'is_admin' => 0, 'demo_expires_at' => now()->addMonth()->format('Y-m-d\TH:i'),
        ])->assertRedirect(route('users.index'));
        $this->assertNotNull(User::where('email', 'demo@example.test')->firstOrFail()->demo_expires_at);
    }
}
