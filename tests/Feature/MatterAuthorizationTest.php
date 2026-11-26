<?php
namespace Tests\Feature;

use App\Models\Matter;
use App\Models\CaseDocument;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class MatterAuthorizationTest extends TestCase
{
    public function test_shared_case_and_document_permissions(): void
    {
        $user = new User;
        $user->forceFill(['id' => 2, 'has_access' => true]);
        $matter = new Matter(['user_id' => 1, 'status' => 'Open']);
        $document = new CaseDocument;
        $document->setRelation('matter', $matter);
        $gate = Gate::forUser($user);
        $this->assertTrue($gate->allows('view', $matter));
        $this->assertTrue($gate->allows('download', $document));
        $this->assertTrue($gate->allows('create', [CaseDocument::class, $matter]));
        $this->assertFalse($gate->allows('delete', $matter));
        $matter->status = 'Closed';
        $this->assertTrue($gate->allows('preview', $document));
        $this->assertFalse($gate->allows('delete', $document));
        $user->has_access = false;
        $this->assertFalse($gate->allows('view', $matter));
        $this->assertFalse($gate->allows('download', $document));
        $this->assertFalse($gate->allows('viewAny', Matter::class));
    }
}
