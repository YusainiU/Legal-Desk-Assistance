<?php

namespace Tests\Feature;

use App\Models\FirmSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CaseActivityAuditAdministrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function actor(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    private function audit(array $attributes = []): int
    {
        return DB::table('case_activity_audits')->insertGetId(array_merge([
            'actor_id' => null,
            'matter_id' => 987,
            'record_type' => 'Matter',
            'record_id' => 987,
            'action' => 'updated',
            'changed_fields' => json_encode(['title']),
            'occurred_at' => now(),
        ], $attributes));
    }

    public function test_guest_cannot_view_or_change_retention(): void
    {
        $this->get(route('admin.case-activity-audit'))->assertRedirect('/login');
        $this->put(route('admin.case-activity-audit.retention'), ['retention_days' => 90])->assertRedirect('/login');
        $this->assertSame(0, FirmSetting::caseAuditRetentionDays());
    }

    public function test_only_active_super_admin_can_access_audit(): void
    {
        foreach ([[], ['is_admin' => true], ['is_super_admin' => true, 'has_access' => false]] as $attributes) {
            $this->actingAs($this->actor($attributes));
            $this->get(route('admin.case-activity-audit'))->assertForbidden();
            $this->put(route('admin.case-activity-audit.retention'), ['retention_days' => 90])->assertForbidden();
        }
        $this->assertSame(0, FirmSetting::caseAuditRetentionDays());
        $this->actingAs($this->actor(['is_super_admin' => true]))
            ->get(route('admin.case-activity-audit'))->assertOk();
    }

    public function test_filters_and_pagination(): void
    {
        $admin = $this->actor(['is_super_admin' => true]);
        $actor = $this->actor();
        $this->travelTo(now()->startOfDay());
        for ($i = 0; $i < 21; $i++) {
            $this->audit(['actor_id' => $actor->id]);
        }
        $this->audit(['actor_id' => $admin->id]);
        $this->audit(['actor_id' => $actor->id, 'action' => 'created']);
        $this->audit(['actor_id' => $actor->id, 'matter_id' => 988]);
        $this->audit(['actor_id' => $actor->id, 'occurred_at' => now()->subDay()]);
        $filters = ['user_id' => $actor->id, 'matter_id' => 987, 'case_reference' => '#987', 'action' => 'updated', 'from' => now()->toDateString(), 'to' => now()->toDateString()];
        $this->actingAs($admin)->get(route('admin.case-activity-audit', $filters))
            ->assertOk()->assertViewHas('audits', fn ($rows) => $rows->total() === 21 && $rows->count() === 20);
        $this->get(route('admin.case-activity-audit', $filters + ['page' => 2]))
            ->assertOk()->assertViewHas('audits', fn ($rows) => $rows->count() === 1);
    }

    public function test_retention_validation_and_update(): void
    {
        $this->actingAs($this->actor(['is_super_admin' => true]));
        $url = route('admin.case-activity-audit.retention');
        $this->putJson($url, ['retention_days' => 1])->assertUnprocessable();
        $this->assertSame(0, FirmSetting::caseAuditRetentionDays());
        $this->put($url, ['retention_days' => 90])->assertRedirect(route('admin.case-activity-audit'));
        $this->assertSame(90, FirmSetting::caseAuditRetentionDays());
        $this->put($url, ['retention_days' => 0])->assertRedirect(route('admin.case-activity-audit'));
        $this->assertSame(0, FirmSetting::caseAuditRetentionDays());
    }

    public function test_cleanup_preserves_indefinite_and_dry_run_and_cutoff(): void
    {
        $this->travelTo(now()->startOfDay());
        $old = $this->audit(['occurred_at' => now()->subDays(91)]);
        $boundary = $this->audit(['occurred_at' => now()->subDays(90)]);
        $recent = $this->audit();
        $this->artisan('legal-desk:case-audit-cleanup')->assertExitCode(0);
        $this->assertDatabaseHas('case_activity_audits', ['id' => $old]);
        FirmSetting::setCaseAuditRetentionDays(90);
        $this->artisan('legal-desk:case-audit-cleanup', ['--dry-run' => true])->assertExitCode(0);
        $this->assertDatabaseHas('case_activity_audits', ['id' => $old]);
        $this->artisan('legal-desk:case-audit-cleanup')->assertExitCode(0);
        $this->assertDatabaseMissing('case_activity_audits', ['id' => $old]);
        $this->assertDatabaseHas('case_activity_audits', ['id' => $boundary]);
        $this->assertDatabaseHas('case_activity_audits', ['id' => $recent]);
    }
}
