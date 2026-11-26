<?php

namespace App\Livewire;

use App\Models\Contact;

use App\Models\Matter;
use App\Models\MatterEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Throwable;

class CaseContacts extends Component
{
    #[Locked]
    public int $matterId;

    #[Locked]
    public string $messageToken;

    public string $linkContactId = '';

    public string $recipientId = '';

    public string $subject = '';

    public string $message = '';

    public ?string $linkStatus = null;

    public ?string $messageStatus = null;

    public function mount(int $matterId): void
    {
        $this->caseQuery()->findOrFail($matterId);
        $this->matterId = $matterId;
        $this->messageToken = (string) Str::uuid();
    }

    public function linkContact(): void
    {
        $this->validate(['linkContactId' => ['required', 'integer']]);

        DB::transaction(function () {
            $matter = $this->caseQuery()->lockForUpdate()->findOrFail($this->matterId);
            \Illuminate\Support\Facades\Gate::authorize('update', $matter);
            abort_unless($matter->allowsContactLink(), 422);

            $contact = Contact::query()->findOrFail($this->linkContactId);
            $matter->contacts()->syncWithoutDetaching([$contact->id]);
        }, 3);

        $this->reportLinkedContact('Contact linked.');
    }

    public function contactCreated(int $contactId): void
    {
        $this->caseQuery()->findOrFail($this->matterId)
            ->contacts()->findOrFail($contactId);

        $this->reportLinkedContact('Contact created and linked to this case.');
    }

    public function sendMessage(): void
    {
        $this->messageStatus = null;
        $this->resetValidation('delivery');

        $mailer = $this->deliveryMailer();
        if ($mailer === null) {
            $this->addError('delivery', 'Configure a delivery mailer before sending case email.');

            return;
        }

        $data = $this->validate([
            'recipientId' => ['required', 'integer'],
            'subject' => ['required', 'string', 'max:160', 'regex:/\A[^\r\n]+\z/u'],
            'message' => ['required', 'string', 'max:10000'],
        ]);

        [$entry, $email, $shouldSend] = DB::transaction(function () use ($data) {
            $matter = $this->caseQuery()->lockForUpdate()->findOrFail($this->matterId);
            \Illuminate\Support\Facades\Gate::authorize('update', $matter);
            abort_unless($matter->status === 'Open', 422);

            $existing = $matter->entries()->where('email_token', $this->messageToken)->first();
            if ($existing) {
                return [$existing, null, false];
            }

            $contact = $matter->contacts()->find($data['recipientId']);
            if (! $contact || ! $contact->email) {
                throw ValidationException::withMessages(['recipientId' => 'Choose a linked contact with an email address.']);
            }

            $entry = $matter->entries()->make([
                'kind' => 'Email',
                'body' => "To: {$contact->name} <{$contact->email}>\nSubject: {$data['subject']}\n\n{$data['message']}",
            ]);
            $entry->forceFill(['email_token' => $this->messageToken, 'email_status' => 'pending'])->save();

            return [$entry, $contact->email, true];
        }, 3);

        if ($shouldSend) {
            $this->deliver($entry, $mailer, $email, $data['subject'], $data['message']);
        }
        $this->dispatch('case-contacts-changed');

        if ($entry->email_status !== 'sent') {
            $this->messageStatus = $entry->email_status;
            $this->addError('delivery', 'Email delivery was not confirmed. Check with your mail provider before sending it again.');

            return;
        }

        $this->resetMessage();
        $this->messageStatus = 'sent';
    }

    public function resetMessage(): void
    {
        $this->reset('subject', 'message', 'messageStatus');
        $this->resetValidation(['recipientId', 'subject', 'message', 'delivery']);
        $this->messageToken = (string) Str::uuid();
    }

    public function render()
    {
        $matter = $this->caseQuery()->findOrFail($this->matterId);
        $contacts = $matter->contacts()->orderBy('contacts.name')->get();
        $canLink = $matter->status === 'Open' || $contacts->isEmpty();

        return view('livewire.case-contacts', [
            'contacts' => $contacts,
            'availableContacts' => $canLink
                ? Contact::query()->whereNotIn('id', $contacts->modelKeys())->orderBy('name')->get()
                : collect(),
            'emailContacts' => $contacts->filter(fn ($contact) => filled($contact->email)),
            'canLink' => $canLink,
            'canEmail' => $matter->status === 'Open',
            'mailReady' => $this->deliveryMailer() !== null,
        ]);
    }

    private function reportLinkedContact(string $message): void
    {
        $this->reset('linkContactId');
        $this->linkStatus = $message;
        $this->dispatch('case-contacts-changed');
    }

    private function caseQuery(): Builder
    {
        abort_unless(auth()->check(), 403);

        return Matter::query()->accessibleTo(auth()->user());
    }

    private function deliveryMailer(): ?string
    {
        $mailer = config('mail.default');
        $transport = config('mail.mailers.'.$mailer.'.transport');

        return in_array($transport, ['smtp', 'sendmail', 'ses', 'ses-v2', 'postmark', 'resend', 'mailgun'], true)
            ? $mailer
            : null;
    }

    private function deliver(MatterEntry $entry, string $mailer, string $email, string $subject, string $body): void
    {
        $status = 'unconfirmed';

        try {
            $sent = Mail::mailer($mailer)->raw($body, fn (Message $message) => $message->to($email)->subject($subject));
            if ($sent !== null) {
                $status = 'sent';
            }
        } catch (Throwable $exception) {
            Log::warning('Case email delivery was not confirmed.', ['entry_id' => $entry->id, 'exception' => $exception::class]);
        }

        $entry->forceFill(['email_status' => $status])->save();
    }
}
