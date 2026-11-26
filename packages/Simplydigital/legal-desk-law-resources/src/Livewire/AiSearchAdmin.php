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
    public bool $enabled = true;
    public ?string $notice = null;

    public function mount(AiSearchSettings $settings): void
    {
        Gate::authorize('manage-users');
        $this->location = $settings->location();
        $this->enabled = $settings->enabled();
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
        ]);
        $settings->save($this->location, $this->enabled);
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
