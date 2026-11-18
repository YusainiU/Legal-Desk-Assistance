<?php

namespace App\Livewire;

use App\Models\CaseDocument;
use App\Models\Matter;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

#[Layout('components.layouts.app')]
class Workspace extends Component
{
    use WithFileUploads;

    #[Locked]
    public ?int $selected = null;

    public string $screen = 'register';

    public string $search = '';

    public string $status = '';

    public array $form = [];

    public string $decision = '';

    public string $body = '';

    public string $assignee = '';

    public string $due = '';

    public string $kind = 'Note';

    public $document;

    public ?string $documentStatus = null;

    public function show(string $screen): void
    {
        abort_unless(in_array($screen, ['register', 'guide', 'new'], true), 404);
        $this->reset('selected', 'form', 'decision', 'body', 'assignee', 'due', 'document', 'documentStatus');
        $this->resetValidation();
        $this->screen = $screen;
    }

    public function open(int $id): void
    {
        $matter = $this->matters()->findOrFail($id);
        $this->show('register');
        $this->selected = $matter->id;
        $this->screen = 'detail';
        $this->assignee = $matter->owner;
    }

    public function selectCreatedContact(int $contactId): void
    {
        if ($this->screen !== 'new') {
            return;
        }

        $contact = auth()->user()->contacts()->findOrFail($contactId);
        $this->form['contact_id'] = (string) $contact->id;
        $this->resetValidation('form.contact_id');
    }

    public function createMatter(): void
    {
        $data = $this->validate([
            'form.title' => 'required|string|max:160',
            'form.contact_id' => ['required', 'integer', Rule::exists('contacts', 'id')->where('user_id', auth()->id())],
            'form.party' => 'nullable|string|max:160',
            'form.owner' => 'required|string|max:160',
            'form.practice' => ['required', Rule::in(array_keys(config('matterdesk.practices')))],
        ])['form'];

        $matter = DB::transaction(function () use ($data) {
            $contact = auth()->user()->contacts()->findOrFail($data['contact_id']);
            unset($data['contact_id']);

            $matter = Matter::create($data + ['user_id' => auth()->id(), 'client' => $contact->name]);
            $matter->contacts()->attach($contact->id);

            return $matter;
        });
        $this->open($matter->id);
    }

    public function toggleCheck(int $expectedStage, int $index, bool $checked): void
    {
        $this->change(function (Matter $matter) use ($expectedStage, $index, $checked) {
            abort_unless($matter->stage === $expectedStage && isset(config('matterdesk.stages')[$matter->stage][2][$index]), 422);
            $checks = $matter->checks ?? [];
            $key = 'stage_'.$matter->stage;
            $checks[$key][$index] = $checked;
            $matter->update(['checks' => $checks]);
        });
    }

    public function recordConflict(string $result): void
    {
        abort_unless(in_array($result, ['Pending', 'Cleared', 'Escalated'], true), 422);
        $this->change(function (Matter $matter) use ($result) {
            abort_unless(in_array($matter->stage, [1, 2], true), 422);
            if ($matter->conflict !== $result) {
                $matter->update(['conflict' => $result]);
                $this->log($matter, 'Conflict review marked '.strtolower($result).'.');
            }
        });
    }

    public function advance(int $expectedStage): void
    {
        $this->change(function (Matter $matter) use ($expectedStage) {
            $stages = config('matterdesk.stages');
            $last = count($stages) - 1;

            if (! $matter->contacts()->exists()) {
                $this->addError('workflow', 'Link a contact before advancing this case.');

                return;
            }

            if ($matter->stage !== $expectedStage || ! $this->canAdvance($matter)) {
                $this->addError('workflow', 'Review the current checklist, conflict decision or outstanding tasks.');

                return;
            }

            if ($matter->stage === 2) {
                $this->validate(['decision' => 'required|string|max:4000']);
                $this->log($matter, 'Case accepted — '.$this->decision);
            }

            if ($matter->stage === $last) {
                $matter->update(['status' => 'Closed']);
                $this->log($matter, 'File closed.');
            } else {
                $matter->update(['stage' => $matter->stage + 1]);
                $this->log($matter, 'Advanced to '.$stages[$matter->stage][0].'.');
            }

            $this->reset('decision');
            $this->resetValidation('workflow');
        });
    }

    public function decline(): void
    {
        $this->validate(['decision' => 'required|string|max:4000']);
        $this->change(function (Matter $matter) {
            abort_unless($matter->stage === 2, 422);

            if (! $matter->contacts()->exists()) {
                $this->addError('workflow', 'Link a contact before deciding whether to take the case.');

                return;
            }

            $matter->update(['status' => 'Declined']);
            $this->log($matter, 'Case declined — '.$this->decision);
        });
        $this->reset('decision');
    }

    public function addEntry(): void
    {
        $data = $this->validate([
            'kind' => ['required', Rule::in(['Note', 'Task', 'Client update', 'Billing'])],
            'body' => 'required|string|max:4000',
            'assignee' => 'required_if:kind,Task|nullable|string|max:160',
            'due' => 'nullable|date_format:Y-m-d',
        ]);

        $this->change(function (Matter $matter) use ($data) {
            $entry = ['kind' => $data['kind'], 'body' => $data['body']];
            if ($data['kind'] === 'Task') {
                $entry += ['assignee' => $data['assignee'], 'due_on' => $data['due'] ?: null];
            }
            $matter->entries()->create($entry);
        });
        $this->reset('body', 'due');
    }

    public function uploadDocument(): void
    {
        $matter = $this->matters()->findOrFail($this->selected);
        abort_unless($matter->status === 'Open', 422);

        $this->documentStatus = null;
        $extensions = implode(',', CaseDocument::EXTENSIONS);
        $this->validate(['document' => ['required', 'file', 'max:10240', 'mimes:'.$extensions, 'extensions:'.$extensions]]);

        $document = $this->document;
        $original = basename(str_replace('\\', '/', $document->getClientOriginalName()));
        $stem = preg_replace('/\p{C}+/u', '', pathinfo($original, PATHINFO_FILENAME));
        $name = (Str::limit(trim($stem ?? ''), 180, '') ?: 'document').'.'.strtolower($document->getClientOriginalExtension());
        $mimeType = $document->getMimeType();
        $size = $document->getSize();
        $path = $document->store('case-documents/'.$matter->id, 'local');

        if (! $path) {
            $this->addError('document', 'The file could not be stored. Please try again.');

            return;
        }

        try {
            $this->change(fn (Matter $matter) => $matter->documents()->create([
                'name' => $name,
                'path' => $path,
                'mime_type' => $mimeType,
                'size' => $size,
            ]));
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);

            throw $exception;
        }

        $this->reset('document');
        $this->documentStatus = 'Document uploaded.';
    }

    public function deleteDocument(int $id): void
    {
        $this->documentStatus = null;
        $errorKey = 'documentDeletion.'.$id;
        $this->resetValidation($errorKey);

        $this->change(function (Matter $matter) use ($id, $errorKey) {
            $document = $matter->documents()->findOrFail($id);

            $document->delete();

            if (! Storage::disk('local')->delete($document->path)) {
                throw ValidationException::withMessages([
                    $errorKey => 'The file could not be removed. Please try again.',
                ]);
            }
        });

        $this->documentStatus = 'Document deleted.';
    }

    public function toggleTask(int $id, bool $checked): void
    {
        $this->change(function (Matter $matter) use ($id, $checked) {
            $task = $matter->entries()->where('kind', 'Task')->findOrFail($id);
            $task->update(['done' => $checked]);
        });
    }

    public function render()
    {
        $matter = $this->selected === null ? null : $this->matters()->with(['entries', 'documents'])->withExists('contacts')->findOrFail($this->selected);
        $register = collect();
        if ($this->screen === 'register') {
            $register = $this->matters()
                ->when(in_array($this->status, ['Open', 'Closed', 'Declined'], true), fn (Builder $query) => $query->where('status', $this->status))
                ->when(trim($this->search) !== '', function (Builder $query) {
                    $term = '%'.trim($this->search).'%';
                    $query->where(fn (Builder $match) => $match->whereLike('title', $term)->orWhereLike('client', $term)->orWhereLike('party', $term)->orWhereLike('owner', $term));
                })
                ->withMin(['entries as next_due' => fn (Builder $query) => $query->where('kind', 'Task')->where('done', false)], 'due_on')
                ->latest('id')->get();
        }

        return view('livewire.workspace', [
            'register' => $register,
            'matter' => $matter,
            'stages' => config('matterdesk.stages'),
            'practices' => config('matterdesk.practices'),
            'canMove' => $matter && $matter->status === 'Open' && $matter->contacts_exists && $this->canAdvance($matter),
            'contactOptions' => $this->screen === 'new' ? auth()->user()->contacts()->orderBy('name')->get() : collect(),
        ]);
    }

    private function matters(): Builder
    {
        abort_unless(auth()->check(), 403);

        return Matter::query()->where('user_id', auth()->id());
    }

    private function change(Closure $callback): void
    {
        DB::transaction(function () use ($callback) {
            $matter = $this->matters()->lockForUpdate()->findOrFail($this->selected);
            abort_unless($matter->status === 'Open', 422);
            $callback($matter);
        }, 3);
    }

    private function canAdvance(Matter $matter): bool
    {
        $stages = config('matterdesk.stages');
        $checks = $matter->checks['stage_'.$matter->stage] ?? [];

        foreach ($stages[$matter->stage][2] as $index => $description) {
            if (! ($checks[$index] ?? false)) {
                return false;
            }
        }

        return ($matter->stage !== 2 || $matter->conflict === 'Cleared')
            && ($matter->stage !== count($stages) - 1 || ! $matter->entries()->where('kind', 'Task')->where('done', false)->exists());
    }

    private function log(Matter $matter, string $body): void
    {
        $matter->entries()->create(['kind' => 'Activity', 'body' => $body]);
    }
}
