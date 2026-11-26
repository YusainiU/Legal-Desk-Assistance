<?php

namespace App\Livewire;

use App\Models\Contact;

use App\Models\CaseDocument;
use App\Models\Matter;
use App\Models\Team;
use App\Models\User;
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
use Livewire\WithPagination;
use Livewire\WithoutUrlPagination;
use Throwable;

#[Layout('components.layouts.app')]
class Workspace extends Component
{
    use WithFileUploads, WithPagination, WithoutUrlPagination;

    private const CASES_PER_PAGE = 15;

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

    public array $selectedCases = [];

    public ?string $archiveNotice = null;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->reset('selectedCases');
        $this->resetPage('casePage');
    }

    public function updatedStatus(): void
    {
        $this->reset('selectedCases');
        $this->resetPage('casePage');
    }

    public function archiveSelected(): void
    {
        $this->changeArchiveState(true);
    }

    public function restoreSelected(): void
    {
        $this->changeArchiveState(false);
    }

    private function changeArchiveState(bool $archive): void
    {
        abort_unless($this->screen === ($archive ? 'register' : 'archive'), 422);

        $ids = $this->validate([
            'selectedCases' => 'required|array|min:1',
            'selectedCases.*' => 'required|integer|min:1|distinct',
        ])['selectedCases'];

        $count = DB::transaction(function () use ($ids, $archive) {
            $cases = $this->matters()->whereIn('id', $ids)
                ->when($archive, fn (Builder $query) => $query->whereNull('archived_at'),
                    fn (Builder $query) => $query->whereNotNull('archived_at'))
                ->orderBy('id')->lockForUpdate()->get();

            abort_unless($cases->count() === count($ids), 422);

            foreach ($cases as $matter) {
                $matter->forceFill(['archived_at' => $archive ? now() : null])->save();
                $this->log($matter, ($archive ? 'Case archived' : 'Case restored to the register').' by '.auth()->user()->name.'.');
            }

            return $cases->count();
        }, 3);

        $this->reset('selectedCases');
        $this->resetPage('casePage');
        $this->archiveNotice = $count.' case(s) '.($archive ? 'archived.' : 'restored.');
    }

    public string $status = '';

    public array $form = [];

    public string $caseDescription = '';

    public bool $showDescriptionEditor = false;

    public bool $descriptionSaved = false;

    public string $caseTeamId = '';

    public bool $showTeamEditor = false;

    public string $caseOwnerId = '';

    public bool $showOwnerEditor = false;

    public string $decision = '';

    public string $stageNote = '';

    public bool $stageNoteSaved = false;

    public string $body = '';

    public string $kind = 'Note';

    public $document;

    public ?string $documentStatus = null;

    public function show(string $screen): void
    {
        abort_unless(in_array($screen, ['register', 'archive', 'owners', 'guide', 'new'], true), 404);
        $this->reset('selectedCases', 'archiveNotice', 'selected', 'linkedTaskId', 'reviewingStage', 'form', 'caseTeamId', 'showTeamEditor', 'caseOwnerId', 'showOwnerEditor', 'caseDescription', 'showDescriptionEditor', 'descriptionSaved', 'decision', 'stageNote', 'stageNoteSaved', 'body', 'document', 'documentStatus', 'showCaseSummary', 'summaryMatterId');
        $this->resetValidation();
        $this->resetPage('casePage');
        $this->screen = $screen;
    }

    #[Locked]
    public ?int $linkedTaskId = null;

    public function mount(): void
    {
        if (! request()->has('case')) {
            $status = request()->query('status');
            if (in_array($status, ['Open', 'Closed', 'Declined'], true)) {
                $this->status = $status;
            }
            return;
        }

        $data = request()->validate([
            'case' => 'required|integer|min:1',
            'task' => 'nullable|integer|min:1',
        ]);

        $this->open((int) $data['case']);

        if (isset($data['task'])) {
            $task = $this->matters()->findOrFail($this->selected)->tasks()->findOrFail($data['task']);
            $this->linkedTaskId = $task->id;
        }
    }

    public function open(int $id): void
    {
        $matter = $this->matters()->findOrFail($id);
        $this->show('register');
        $this->selected = $matter->id;
        $this->screen = 'detail';
    }

    public function editCaseOwner(): void
    {
        $matter = $this->matters()->findOrFail($this->selected);
        abort_unless($matter->status === 'Open', 422);

        $matches = User::query()->where('has_access', true)->where('name', $matter->owner)->limit(2)->pluck('id');
        $this->caseOwnerId = $matches->count() === 1 ? (string) $matches->first() : '';
        $this->resetValidation('caseOwnerId');
        $this->showOwnerEditor = true;
    }

    public function saveCaseOwner(): void
    {
        $data = $this->validate(['caseOwnerId' => ['required', 'integer', Rule::exists('users', 'id')->where('has_access', true)->whereNull('deleted_at')]]);

        $this->change(function (Matter $matter) use ($data) {
            $owner = User::query()->where('has_access', true)->findOrFail($data['caseOwnerId']);
            $previous = $matter->owner;
            $matter->update(['owner' => $owner->name]);
            if ($previous !== $owner->name) {
                $this->log($matter, 'Case owner changed from '.$previous.' to '.$owner->name.' by '.auth()->user()->name.'.');
            }
        });

        $this->reset('showOwnerEditor', 'caseOwnerId');
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

        $contact = Contact::query()->findOrFail($contactId);
        $this->form['contact_id'] = (string) $contact->id;
        $this->resetValidation('form.contact_id');
    }

    public function createMatter(): void
    {
        $data = $this->validate([
            'form.title' => 'required|string|max:160',
            'form.team_id' => 'nullable|integer|min:1',
            'form.description' => 'required|string|max:4000',
            'form.contact_id' => ['required', 'integer', Rule::exists('contacts', 'id')],
            'form.party' => 'nullable|string|max:160',
            'form.owner' => 'required|string|max:160',
            'form.practice' => ['required', Rule::in(array_keys(config('matterdesk.practices')))],
        ])['form'];

        $matter = DB::transaction(function () use ($data) {
            $contact = Contact::query()->findOrFail($data['contact_id']);
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
        if (in_array($this->screen, ['register', 'archive'], true)) {
            $register = $this->matters()
                ->when($this->screen === 'archive', fn (Builder $query) => $query->whereNotNull('archived_at'), fn (Builder $query) => $query->whereNull('archived_at'))
                ->when(in_array($this->status, ['Open', 'Closed', 'Declined'], true), fn (Builder $query) => $query->where('status', $this->status))
                ->when(trim($this->search) !== '', function (Builder $query) {
                    $term = '%'.trim($this->search).'%';
                    $query->where(fn (Builder $match) => $match->whereLike('title', $term)->orWhereLike('client', $term)->orWhereLike('party', $term)->orWhereLike('owner', $term));
                })
                ->withMin(['tasks as next_due' => fn (Builder $query) => $query->where('done', false)], 'due_on')
                ->latest('id')->paginate(self::CASES_PER_PAGE, pageName: 'casePage');
        }

        return view('livewire.workspace', [
            'register' => $register,
            'ownerGroups' => $this->screen === 'owners'
                ? $this->matters()->whereNull('archived_at')->where('status', 'Open')->orderBy('owner')->orderBy('title')->orderBy('id')->get(['id', 'owner', 'title', 'client', 'stage'])->groupBy('owner')
                : collect(),
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
            'ownerOptions' => $this->showOwnerEditor ? User::query()->where('has_access', true)->orderBy('name')->orderBy('id')->get(['id', 'name', 'email']) : collect(),
            'contactOptions' => $this->screen === 'new' ? Contact::query()->orderBy('name')->get() : collect(),
        ]);
    }

    private function matters(): Builder
    {
        abort_unless(auth()->check(), 403);

        return Matter::query();
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
