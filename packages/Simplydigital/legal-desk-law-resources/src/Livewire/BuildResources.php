<?php

namespace Simplydigital\LegalDeskLawResources\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class BuildResources extends Component
{
    public function render()
    {
        return view('legal-desk-law-resources::livewire.build-resources');
    }
}
