<?php

namespace App\Livewire;

use App\Models\FirmSetting;
use App\Models\Matter;
use App\Models\MatterEntry;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Dashboard extends Component
{
    public function render()
    {
        \Illuminate\Support\Facades\Gate::authorize('viewAny', Matter::class);

        if (! auth()->user()->can('access-page', 'workspace')) {
            return view('livewire.dashboard-restricted');
        }
        $openCases = Matter::query()->accessibleTo(auth()->user())->whereNull('archived_at')->where('status', 'Open');
        $stageCounts = (clone $openCases)->selectRaw('stage, COUNT(*) as total')
            ->groupBy('stage')->pluck('total', 'stage');

        $warningHours = FirmSetting::taskWarningHours();
        $now = now();
        $limit = $now->copy()->addHours($warningHours);
        $incomplete = $soon = $expired = 0;

        $tasks = MatterEntry::query()->where('kind', 'Task')->where('done', false)
            ->whereIn('matter_id', (clone $openCases)->select('id'));

        foreach ($tasks->select(['id', 'due_on', 'due_at'])->lazyById(1000) as $task) {
            $incomplete++;
            $deadline = $task->deadline();
            if ($deadline === null) {
                continue;
            }
            if ($deadline->lte($now)) {
                $expired++;
            } elseif ($deadline->lte($limit)) {
                $soon++;
            }
        }

        return view('livewire.dashboard', [
            'openCount' => $stageCounts->sum(),
            'stageCounts' => $stageCounts,
            'stages' => config('matterdesk.stages'),
            'incomplete' => $incomplete,
            'soon' => $soon,
            'expired' => $expired,
            'warningHours' => $warningHours,
        ]);
    }
}
