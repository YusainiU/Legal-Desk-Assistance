<?php

namespace Tests\Feature;

use App\Models\CaseDocument;
use App\Models\Matter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CaseDocumentDownloadTest extends TestCase
{
    use RefreshDatabase;

    private function document(): CaseDocument
    {
        Storage::fake('local');
        $matter = Matter::factory()->create();
        $document = new CaseDocument([
            'name' => 'test.txt',
            'path' => 'case-documents/test.txt',
            'mime_type' => 'text/plain',
            'size' => 4,
        ]);
        $document->matter()->associate($matter);
        $document->save();
        Storage::disk('local')->put($document->path, 'test');

        return $document;
    }

    public function test_authorized_download_is_private_and_audited(): void
    {
        $document = $this->document();
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('case-documents.download', $document));
        $response->assertOk()->assertDownload('test.txt')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Type', 'application/octet-stream');
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertDatabaseHas('case_activity_audits', [
            'actor_id' => $user->id,
            'matter_id' => $document->matter_id,
            'record_type' => 'CaseDocument',
            'record_id' => $document->id,
            'action' => 'download',
        ]);
        $this->assertSame(1, DB::table('case_activity_audits')->where('action', 'download')->count());
    }

    public function test_guest_and_disabled_user_cannot_download(): void
    {
        $document = $this->document();
        $url = route('case-documents.download', $document);
        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['has_access' => false]))->get($url)->assertForbidden();
        $this->assertDatabaseMissing('case_activity_audits', ['action' => 'download']);
    }

    public function test_restricted_download_permission_is_enforced(): void
    {
        $document = $this->document();
        DB::table('page_permissions')->insert(['page' => 'workspace.documents-download', 'restricted' => true]);
        $this->actingAs(User::factory()->create())
            ->get(route('case-documents.download', $document))->assertForbidden();
        $this->assertDatabaseMissing('case_activity_audits', ['action' => 'download']);
    }

    public function test_missing_file_does_not_create_download_event(): void
    {
        $document = $this->document();
        Storage::disk('local')->delete($document->path);
        $this->actingAs(User::factory()->create())
            ->get(route('case-documents.download', $document))->assertNotFound();
        $this->assertDatabaseMissing('case_activity_audits', ['action' => 'download']);
    }
}
