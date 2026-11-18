<?php

namespace App\Livewire;

use App\Support\ContactDetails;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ContactCreator extends Component
{
    #[Locked]
    public bool $returnToIndex = false;

    public bool $showModal = false;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $organisation = '';

    public function save(): void
    {
        $owner = auth()->user();
        abort_unless($owner, 403);

        $owner->contacts()->create(ContactDetails::validate([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'organisation' => $this->organisation,
        ], $owner));

        $this->reset('name', 'email', 'phone', 'organisation', 'showModal');
        $this->resetValidation();

        if ($this->returnToIndex) {
            session()->flash('status', 'Contact created.');
            $this->redirectRoute('contacts.index');

            return;
        }

        $this->dispatch('contact-created');
    }

    public function render()
    {
        return view('livewire.contact-creator');
    }
}
