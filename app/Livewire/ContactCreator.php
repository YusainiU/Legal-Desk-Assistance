<?php

namespace App\Livewire;

use App\Models\Matter;
use App\Support\ContactDetails;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ContactCreator extends Component
{
    #[Locked]
    public bool $returnToIndex = false;

    #[Locked]
    public ?int $matterId = null;

    public bool $showModal = false;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $organisation = '';

    public function save(): void
    {
        $owner = auth()->user();
        abort_unless($owner, 403);

        $details = ContactDetails::validate([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'organisation' => $this->organisation,
        ], $owner);

        $contact = DB::transaction(function () use ($owner, $details) {
            $matter = $this->matterId === null ? null : Matter::query()
                ->lockForUpdate()
                ->findOrFail($this->matterId);

            abort_if($matter && ! $matter->allowsContactLink(), 422);

            $contact = $owner->contacts()->create($details);
            $matter?->contacts()->attach($contact->id);

            return $contact;
        }, 3);

        $this->reset('name', 'email', 'phone', 'organisation', 'showModal');
        $this->resetValidation();

        if ($this->returnToIndex) {
            session()->flash('status', 'Contact created.');
            $this->redirectRoute('contacts.index');

            return;
        }

        $this->dispatch('contact-created', contactId: $contact->id);
    }

    public function render()
    {
        return view('livewire.contact-creator');
    }
}
