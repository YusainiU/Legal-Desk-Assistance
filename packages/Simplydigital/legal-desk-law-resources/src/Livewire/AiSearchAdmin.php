<?php

namespace Simplydigital\LegalDeskLawResources\Livewire;

use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Simplydigital\LegalDeskLawResources\Services\AiSearchSettings;

#[Layout('components.layouts.app')]
class AiSearchAdmin extends Component
{
    public string $location = '';
    public bool $enabled = false;
    public int $userMinute = 5;
    public int $userDaily = 50;
    public int $firmDaily = 500;
    public ?string $notice = null;

    public function mount(AiSearchSettings $settings): void
    {
        Gate::authorize('manage-users');
        $this->location = $settings->location();
        $this->enabled = $settings->enabled();
        foreach ($settings->limits() as $key => $value) $this->{$key} = $value;
    }

    public function save(AiSearchSettings $settings): void
    {
        if (\Illuminate\Support\Facades\Gate::has('access-page')) {
            \Illuminate\Support\Facades\Gate::authorize('access-page', 'ai-search-admin.update');
        }
        Gate::authorize('manage-users');
        $this->validate([
            'location' => ['required', 'string', 'min:2', 'max:160'],
            'enabled' => ['required', 'boolean'],
            'userMinute' => ['required', 'integer', 'min:1', 'max:60'],
            'userDaily' => ['required', 'integer', 'min:1', 'max:10000'],
            'firmDaily' => ['required', 'integer', 'min:1', 'max:100000'],
        ]);
        $settings->save($this->location, $this->enabled, $this->only(['userMinute', 'userDaily', 'firmDaily']));
        $this->notice = 'AI Search settings saved.';
    }

    public function render()
    {
        Gate::authorize('manage-users');
        return view('legal-desk-law-resources::livewire.ai-search-admin', [
            'defaultLocation' => config('ai-management.search_location', 'Malaysia'),
        ]);
    }
}
