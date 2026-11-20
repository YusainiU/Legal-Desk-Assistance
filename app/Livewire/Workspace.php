<?php

namespace App\Livewire;

use App\Models\CaseDocument;
use App\Models\Matter;
use App\Models\Team;
use App\Support\CaseBilling;
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

    #[Locked]
    public ?int $reviewingStage = null;

    public bool $showCaseSummary = false;

    #[Locked]
    public ?int $summaryMatterId = null;

    public function openCaseSummary(int $id): void
    {
        $this->summaryMatterId = $this->matters()->findOrFail($id)->id;
        $this->showCaseSummary = true;
    }

    public string $screen = 'register';

    public string $search = '';

    public string $status = '';

    public array $form = [];

    public string $caseDescription = '';

    public bool $showDescriptionEditor = false;

    public bool $descriptionSaved = false;

    public string $caseTeamId = '';

    public bool $showTeamEditor = false;

    public string $decision = '';

    public string $stageNote = '';

    public bool $stageNoteSaved = false;

    public string $body = '';

    public string $kind = 'Note';

    public $document;

    public ?string $documentStatus = null;

    public function show(string $screen): void
    {
        abort_unless(in_array($screen, ['register', 'guide', 'new'], true), 404);
        $this->reset('selected', 'reviewingStage', 'form', 'caseTeamId', 'showTeamEditor', 'caseDescription', 'showDescriptionEditor', 'descriptionSaved', 'decision', 'stageNote', 'stageNoteSaved', 'body', 'document', 'documentStatus', 'showCaseSummary', 'summaryMatterId');
        $this->resetValidation();
        $this->screen = $screen;
    }

    public function open(int $id): void
    {
        $matter = $this->matters()->findOrFail($id);
        $this->show('register');
        $this->selected = $matter->id;
        $this->screen = 'detail';
    }

    public function editCaseTeam(): void
    {
        $matter = $this->matters()->findOrFail($this->selected);
        abort_unless($matter->status === 'Open', 422);

        $this->caseTeamId = (string) ($matter->team_id ?? '');
        $this->resetValidation('caseTeamId');
        $this->showTeamEditor = true;
    }

    public function saveCaseTeam(): void
    {
        $data = $this->validate(['caseTeamId' => 'nullable|integer|min:1']);

        $this->change(function (Matter $matter) use ($data) {
            $team = Team::assignmentId(auth()->user(), $data['caseTeamId'] ?? null, 'caseTeamId', $matter->team_id);
            $matter->update(['team_id' => $team]);
        });

        $this->reset('showTeamEditor', 'caseTeamId');
    }

    public function editDescription(): void
    {
        $matter = $this->matters()->findOrFail($this->selected);
        $this->caseDescription = $matter->description ?? '';
        $this->descriptionSaved = false;
        $this->resetValidation('caseDescription');
        $this->showDescriptionEditor = true;
    }

    public function saveDescription(): void
    {
        $matter = $this->matters()->findOrFail($this->selected);
        $data = $this->validate(['caseDescription' => 'required|string|max:4000']);

        $matter->update(['description' => trim($data['caseDescription'])]);
        $this->reset('showDescriptionEditor', 'caseDescription');
        $this->descriptionSaved = true;
    }

    public function viewCompletedStage(int $stage): void
    {
        $matter = $this->matters()->findOrFail($this->selected);
        abort_unless($matter->hasCompletedStage($stage), 404);

        $this->reviewingStage = $stage;
        $this->resetValidation('stageReview');
    }

    public function returnToWorkflow(): void
    {
        $this->reset('reviewingStage');
        $this->resetValidation('stageReview');
    }

    public function reinstateStage(int $expectedStage): void
    {
        $this->change(function (Matter $matter) use ($expectedStage) {
            $stage = $this->reviewingStage;

            if ($matter->stage !== $expectedStage || $stage === null || ! $matter->hasCompletedStage($stage)) {
                $this->addError('stageReview', 'The workflow has changed. Open the completed stage again before reinstating it.');

                return;
            }

            if (filled($this->stageNote)) {
                $this->recordStageNote($matter);
            }

            $stages = config('matterdesk.stages');
            $from = $stages[$matter->stage][0];
            $matter->update(['stage' => $stage]);
            $this->log($matter, "Reinstated {$stages[$stage][0]} from {$from}.");
            $this->finishStageChange();
        });
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
            'form.team_id' => 'nullable|integer|min:1',
            'form.description' => 'required|string|max:4000',
            'form.contact_id' => ['required', 'integer', Rule::exists('contacts', 'id')->where('user_id', auth()->id())],
            'form.party' => 'nullable|string|max:160',
            'form.owner' => 'required|string|max:160',
            'form.practice' => ['required', Rule::in(array_keys(config('matterdesk.practices')))],
        ])['form'];

        $matter = DB::transaction(function () use ($data) {
            $contact = auth()->user()->contacts()->findOrFail($data['contact_id']);
            unset($data['contact_id']);
            $data['team_id'] = Team::assignmentId(auth()->user(), $data['team_id'] ?? null, 'form.team_id');
            $data['description'] = trim($data['description']);

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
        $this->resetValidation('stageTasks');

        $this->change(function (Matter $matter) use ($expectedStage) {
            $stages = config('matterdesk.stages');
            $last = count($stages) - 1;

            if (! $matter->contacts()->exists()) {
                $this->addError('workflow', 'Link a contact before advancing this case.');

                return;
            }

            if ($matter->stage !== $expectedStage || ! $this->canAdvance($matter)) {
                $this->addError('workflow', 'Review the current checklist and conflict decision.');

                return;
            }

            if ($matter->incompleteTasksThroughCurrentStage()->exists()) {
                $this->addError('stageTasks', 'Complete or delete the incomplete tasks before continuing.');

                return;
            }

            if ($matter->stage === 2) {
                $this->validate(['decision' => 'required|string|max:4000']);
            }

            if (filled($this->stageNote)) {
                $this->recordStageNote($matter);
            }

            if ($matter->stage === 2) {
                $this->log($matter, 'Case accepted — '.$this->decision);
            }

            if ($matter->stage === $last) {
                $this->closeFile($matter);
            } else {
                $matter->update(['stage' => $matter->stage + 1]);
                $this->log($matter, 'Advanced to '.$stages[$matter->stage][0].'.');
            }

            $this->finishStageChange();
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

            if (filled($this->stageNote)) {
                $this->recordStageNote($matter);
            }

            $matter->update(['status' => 'Declined']);
            $this->log($matter, 'Case declined — '.$this->decision);
            $this->finishStageChange();
        });
    }

    public function saveStageNote(int $expectedStage): void
    {
        $this->stageNoteSaved = false;

        $this->change(function (Matter $matter) use ($expectedStage) {
            if ($matter->stage !== $expectedStage) {
                $this->addError('stageNote', 'The stage changed. Review your draft before saving it to the current stage.');

                return;
            }

            $this->recordStageNote($matter);
            DB::afterCommit(function () {
                $this->reset('stageNote');
                $this->stageNoteSaved = true;
            });
        });
    }

    public function addEntry(): void
    {
        $data = $this->validate([
            'kind' => ['required', Rule::in(['Note', 'Client update', 'Billing'])],
            'body' => 'required|string|max:4000',
        ]);

        $this->change(fn (Matter $matter) => $matter->entries()->create($data));
        $this->reset('body');
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

    public function render()
    {
        $matter = $this->selected === null ? null : $this->matters()->with(['entries' => fn ($query) => $query->where('kind', '!=', 'Task'), 'documents', 'team'])
            ->withExists('contacts')
            ->findOrFail($this->selected);
        $reviewedStage = $matter && $this->reviewingStage !== null && $matter->hasCompletedStage($this->reviewingStage)
            ? $this->reviewingStage
            : null;
        $register = collect();
        if ($this->screen === 'register') {
            $register = $this->matters()
                ->when(in_array($this->status, ['Open', 'Closed', 'Declined'], true), fn (Builder $query) => $query->where('status', $this->status))
                ->when(trim($this->search) !== '', function (Builder $query) {
                    $term = '%'.trim($this->search).'%';
                    $query->where(fn (Builder $match) => $match->whereLike('title', $term)->orWhereLike('client', $term)->orWhereLike('party', $term)->orWhereLike('owner', $term));
                })
                ->withMin(['tasks as next_due' => fn (Builder $query) => $query->where('done', false)], 'due_on')
                ->latest('id')->get();
        }

        return view('livewire.workspace', [
            'register' => $register,
            'summaryMatter' => $this->showCaseSummary && $this->summaryMatterId !== null
                ? $this->matters()->with(['entries' => fn ($query) => $query->where('kind', '!=', 'Task')->latest('created_at')->latest('id')])->findOrFail($this->summaryMatterId)
                : null,
            'matter' => $matter,
            'reviewedStage' => $reviewedStage,
            'reviewedTasks' => $reviewedStage === null ? collect() : $matter->tasks()->where('task_stage', $reviewedStage)->latest('id')->get(),
            'openTaskCount' => $matter && $matter->status === 'Open' ? $matter->incompleteTasksThroughCurrentStage()->count() : 0,
            'stages' => config('matterdesk.stages'),
            'practices' => config('matterdesk.practices'),
            'canMove' => $matter && $matter->status === 'Open' && $matter->contacts_exists && $this->canAdvance($matter),
            'teamOptions' => ($this->screen === 'new' || $this->showTeamEditor)
                ? Team::assignmentOptions(auth()->user(), $matter?->team_id)
                : collect(),
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

        return $matter->stage !== 2 || $matter->conflict === 'Cleared';
    }

    private function closeFile(Matter $matter): void
    {
        if (blank($matter->final_decision)) {
            throw ValidationException::withMessages(['workflow' => 'Save the Final Decision in its tab before closing this case.']);
        }

        $charges = $matter->timeCharges()->lockForUpdate()->get(['minutes', 'amount_minor']);
        $minutes = $charges->sum('minutes');
        $amount = $charges->sum('amount_minor');

        $matter->forceFill([
            'status' => 'Closed',
            'final_charge_minutes' => $minutes,
            'final_charge_minor' => $amount,
        ])->save();

        if ($minutes > 0) {
            $matter->entries()->create([
                'kind' => 'Billing',
                'body' => 'Final time charges · '.CaseBilling::money($matter->billing_currency, $amount)
                    ."\nTotal recorded time · ".CaseBilling::duration($minutes),
            ]);
        }

        $this->log($matter, 'File closed.');
    }

    private function finishStageChange(): void
    {
        DB::afterCommit(function () {
            $this->reset('decision', 'stageNote', 'stageNoteSaved', 'reviewingStage');
            $this->resetValidation(['workflow', 'stageNote', 'stageTasks', 'stageReview']);
            $this->dispatch('case-stage-changed');
        });
    }

    private function recordStageNote(Matter $matter): void
    {
        $note = $this->validate(['stageNote' => 'required|string|max:4000'])['stageNote'];
        $stage = config('matterdesk.stages')[$matter->stage][0];

        $matter->entries()->create([
            'kind' => 'Note',
            'body' => "Stage · {$stage}\n\n".trim($note),
        ]);
    }

    private function log(Matter $matter, string $body): void
    {
        $matter->entries()->create(['kind' => 'Activity', 'body' => $body]);
    }
}
