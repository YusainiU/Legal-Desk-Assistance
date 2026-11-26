<?php

namespace App\Livewire;

use App\Models\Contact;
use App\Models\Matter;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class ContactAddress extends Component
{
    private const FIELDS = [
        'address_line_1' => ['Address 1', 160],
        'address_line_2' => ['Address 2', 160],
        'address_line_3' => ['Address 3', 160],
        'town' => ['Town', 120],
        'postcode' => ['Postcode', 32],
        'region' => ['State / region / province', 120],
        'country' => ['Country', 120],
    ];

    #[Locked]
    public ?int $matterId = null;

    #[Locked]
    public ?int $contactId = null;

    public bool $showModal = false;

    public array $address = [];

    public bool $saved = false;

    #[On('show-contact-details')]
    public function showContact(int $contactId): void
    {
        $contact = $this->resolveContact($contactId);
        $this->contactId = $contact->id;
        $this->address = $contact->only(array_keys(self::FIELDS));
        $this->saved = false;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        \Illuminate\Support\Facades\Gate::authorize('access-page', 'contacts.update');
        abort_unless($this->showModal && $this->contactId !== null, 404);
        $contact = $this->resolveContact($this->contactId);
        $this->saved = false;

        $rules = ['address' => 'array:'.implode(',', array_keys(self::FIELDS))];
        foreach (self::FIELDS as $field => [, $max]) {
            $rules["address.$field"] = ['nullable', 'string', 'max:'.$max];
        }

        $data = $this->validate($rules)['address'];
        $address = array_map(static function (?string $value): ?string {
            return $value === null || trim($value) === '' ? null : trim($value);
        }, $data);

        $contact->forceFill($address)->save();
        $this->address = $contact->only(array_keys(self::FIELDS));
        $this->saved = true;
    }

    public function render()
    {
        return view('livewire.contact-address', [
            'contact' => $this->showModal && $this->contactId !== null ? $this->resolveContact($this->contactId) : null,
            'fields' => self::FIELDS,
        ]);
    }

    private function resolveContact(int $id): Contact
    {
        $owner = auth()->user();
        abort_unless($owner, 403);

        if ($this->matterId !== null) {
            return Matter::query()->findOrFail($this->matterId)->contacts()->findOrFail($id);
        }

        return Contact::query()->findOrFail($id);
    }
}
