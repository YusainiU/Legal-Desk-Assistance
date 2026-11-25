<?php

namespace App\Http\Controllers;

use App\Models\FirmSetting;
use App\Models\Matter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CaseSummaryController
{
    public function show(Request $request, int $matter): JsonResponse
    {
        $case = Matter::query()
            ->with(['entries' => fn ($entries) => $entries->where('kind', '!=', 'Task')->latest('created_at')->latest('id'), 'tasks'])
            ->findOrFail($matter);

        $completed = [];
        $stages = config('matterdesk.stages');
        foreach ($stages as $index => [$name, $description, $checklist]) {
            if (! $case->hasCompletedStage($index)) {
                continue;
            }

            $checks = $case->checks['stage_'.$index] ?? [];
            $completed[] = [
                'number' => $index + 1,
                'name' => $name,
                'description' => $description,
                'checklist' => collect($checklist)->map(fn ($item, $key) => [
                    'label' => $item,
                    'done' => (bool) ($checks[$key] ?? false),
                ])->values(),
                'tasks' => $case->tasks->where('task_stage', $index)->map(fn ($task) => [
                    'title' => $task->body,
                    'assignee' => $task->assignee,
                    'due' => $task->due_on?->format('j M Y'),
                    'done' => $task->done,
                ])->values(),
            ];
        }

        return response()->json([
            'firm' => FirmSetting::displayName(),
            'generated' => now()->format('j M Y'),
            'case' => [
                'reference' => 'MD-'.$case->id,
                'title' => $case->title,
                'status' => $case->status,
                'stage' => $stages[$case->stage][0] ?? null,
                'client' => $case->client,
                'otherParty' => $case->party,
                'owner' => $case->owner,
                'description' => $case->description,
            ],
            'stages' => $completed,
            'history' => $case->entries->map(fn ($entry) => [
                'kind' => $entry->kind,
                'date' => $entry->created_at->format('j M Y, H:i'),
                'body' => $entry->body,
                'emailStatus' => $entry->kind === 'Email' ? match ($entry->email_status) {
                    'sent' => 'Sent to mail provider',
                    'pending' => 'Awaiting confirmation',
                    default => 'Delivery unconfirmed',
                } : null,
            ])->values(),
        ], headers: ['Cache-Control' => 'private, no-store']);
    }
}
