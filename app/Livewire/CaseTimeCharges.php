<?php

namespace App\Livewire;

use App\Models\Matter;
use App\Support\CaseBilling;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class CaseTimeCharges extends Component
{
    use WithPagination;

    #[Locked]
    public int $matterId;

    #[Locked]
    public int $stage;

    #[Locked]
    public ?int $editingChargeId = null;

    public bool $showEditor = false;

    public string $entryStage = '';

    public string $description = '';

    public string $minutes = '';

    public string $hourlyRate = '';

    public string $currency = '';

    public ?string $notice = null;

    public function mount(int $matterId): void
    {
        $this->matterId = $matterId;
        $this->stage = $this->caseRecord()->stage;
    }

    public function newCharge(): void
    {
        $matter = $this->caseRecord();
        $this->ensureEditable($matter);

        $this->reset('editingChargeId', 'description', 'minutes', 'hourlyRate');
        $this->entryStage = (string) $matter->stage;
        $this->currency = $matter->billing_currency ?? '';
        $this->resetValidation();
        $this->showEditor = true;
    }

    public function editCharge(int $id): void
    {
        $matter = $this->caseRecord();
        $this->ensureEditable($matter);
        $charge = $matter->timeCharges()->findOrFail($id);

        $this->editingChargeId = $charge->id;
        $this->entryStage = (string) $charge->stage;
        $this->description = $charge->description;
        $this->minutes = (string) $charge->minutes;
        $this->hourlyRate = CaseBilling::decimal($charge->hourly_rate_minor);
        $this->currency = $matter->billing_currency;
        $this->resetValidation();
        $this->showEditor = true;
    }

    public function saveCharge(): void
    {
        $editing = $this->editingChargeId !== null;
        $rules = [
            'description' => 'required|string|max:255',
            'minutes' => 'required|integer|min:1|max:10080',
            'hourlyRate' => ['required', 'string', 'regex:/\A[0-9]{1,6}(?:\.[0-9]{1,2})?\z/'],
        ];
        if (! $editing) {
            $rules['entryStage'] = 'required|integer|min:0|max:'.$this->stage;
        }

        $data = $this->validate($rules, [
            'hourlyRate.regex' => 'Enter an hourly rate with up to six digits and two decimal places.',
        ]);

        $this->change(function (Matter $matter) use ($data, $editing) {
            if ($matter->billing_currency === null) {
                $currency = $this->validate(['currency' => ['required', Rule::in(array_keys(CaseBilling::CURRENCIES))]])['currency'];
                $matter->forceFill(['billing_currency' => $currency])->save();
            } elseif ($this->currency !== $matter->billing_currency) {
                throw ValidationException::withMessages(['currency' => 'The case currency has changed. Reopen the entry to use the current currency.']);
            }

            $minutes = (int) $data['minutes'];
            $rate = CaseBilling::parseRate($data['hourlyRate']);
            $attributes = [
                'description' => trim($data['description']),
                'minutes' => $minutes,
                'hourly_rate_minor' => $rate,
                'amount_minor' => CaseBilling::amount($minutes, $rate),
            ];

            if ($editing) {
                $matter->timeCharges()->findOrFail($this->editingChargeId)->update($attributes);

                return;
            }

            $matter->timeCharges()->create($attributes + ['stage' => (int) $data['entryStage']]);
        });

        $this->reset('showEditor', 'editingChargeId', 'entryStage', 'description', 'minutes', 'hourlyRate', 'currency');
        if (! $editing) {
            $this->resetPage('chargePage');
        }
        $this->notice = $editing ? 'Time charge updated.' : 'Time charge recorded.';
    }

    public function deleteCharge(int $id): void
    {
        $this->change(fn (Matter $matter) => $matter->timeCharges()->findOrFail($id)->delete());
        $this->resetPage('chargePage');
        $this->notice = 'Time charge deleted.';
    }

    public function render()
    {
        $matter = $this->caseRecord();
        $stageTotals = $matter->timeCharges()
            ->selectRaw('stage, SUM(minutes) AS minutes, SUM(amount_minor) AS amount_minor')
            ->groupBy('stage')->orderBy('stage')->get();

        return view('livewire.case-time-charges', [
            'stages' => config('matterdesk.stages'),
            'charges' => $matter->timeCharges()->latest('id')->paginate(15, pageName: 'chargePage'),
            'stageTotals' => $stageTotals,
            'caseCurrency' => $matter->billing_currency,
            'closed' => $matter->status === 'Closed',
            'totalMinutes' => $matter->final_charge_minutes ?? $stageTotals->sum('minutes'),
            'totalMinor' => $matter->final_charge_minor ?? $stageTotals->sum('amount_minor'),
            'active' => $matter->status === 'Open' && $matter->stage === $this->stage,
        ]);
    }

    private function change(Closure $callback): void
    {
        DB::transaction(function () use ($callback) {
            $matter = $this->caseRecord(lock: true);
            \Illuminate\Support\Facades\Gate::authorize('update', $matter);
            $this->ensureEditable($matter);
            $callback($matter);
        }, 3);
    }

    private function caseRecord(bool $lock = false): Matter
    {
        abort_unless(auth()->check(), 403);

        $matter = Matter::query()
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->findOrFail($this->matterId);
        \Illuminate\Support\Facades\Gate::authorize('view', $matter);
        return $matter;
    }

    private function ensureEditable(Matter $matter): void
    {
        abort_unless($matter->status === 'Open' && $matter->stage === $this->stage, 409, 'The case has changed. Reopen it to continue.');
    }
}
