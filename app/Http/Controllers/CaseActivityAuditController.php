<?php

namespace App\Http\Controllers;

use App\Models\FirmSetting;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CaseActivityAuditController
{
    public const ACTIONS = ['created', 'updated', 'deleted', 'preview', 'download'];

    public function index(Request $request): View
    {
        Gate::authorize('view-case-activity-audit');

        $filters = $request->validate([
            'user_id' => ['nullable', 'integer', 'min:1'],
            'matter_id' => ['nullable', 'integer', 'min:1'],
            'case_reference' => ['nullable', 'string', 'max:150'],
            'action' => ['nullable', Rule::in(self::ACTIONS)],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', Rule::when($request->filled('from'), ['after_or_equal:from'])],
        ]);

        $audits = DB::table('case_activity_audits as audit')
            ->leftJoin('users as actor', 'actor.id', '=', 'audit.actor_id')
            ->leftJoin('matters as matter', 'matter.id', '=', 'audit.matter_id')
            ->when($filters['user_id'] ?? null, fn ($q, $id) => $q->where('audit.actor_id', $id))
            ->when($filters['matter_id'] ?? null, fn ($q, $id) => $q->where('audit.matter_id', $id))
            ->when(trim($filters['case_reference'] ?? '') !== '', function ($q) use ($filters) {
                $term = trim($filters['case_reference']);
                $id = ltrim($term, '#');
                $q->where(function ($query) use ($term, $id) {
                    if (ctype_digit($id) && filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false) {
                        $query->orWhere('audit.matter_id', (int) $id);
                    }
                    $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';
                    $query->orWhereRaw("matter.title LIKE ? ESCAPE '!'", [$pattern]);
                });
            })
            ->when($filters['action'] ?? null, fn ($q, $action) => $q->where('audit.action', $action))
            ->when($filters['from'] ?? null, fn ($q, $date) => $q->where('audit.occurred_at', '>=', $date.' 00:00:00'))
            ->when($filters['to'] ?? null, fn ($q, $date) => $q->where('audit.occurred_at', '<', Carbon::parse($date)->addDay()->toDateString().' 00:00:00'))
            ->select('audit.*', 'actor.name as actor_name', 'matter.title as matter_title')
            ->orderByDesc('audit.occurred_at')
            ->orderByDesc('audit.id')
            ->paginate(20)->appends($filters);

        return view('admin.case-activity-audit', [
            'audits' => $audits,
            'filters' => $filters,
            'users' => DB::table('users')->select('id', 'name')->orderBy('name')->get(),
            'actions' => self::ACTIONS,
            'retentionDays' => FirmSetting::caseAuditRetentionDays(),
        ]);
    }

    public function updateRetention(Request $request): RedirectResponse
    {
        Gate::authorize('view-case-activity-audit');

        $data = $request->validate([
            'retention_days' => ['required', 'integer', Rule::in(array_keys(FirmSetting::CASE_AUDIT_RETENTION))],
        ]);

        FirmSetting::setCaseAuditRetentionDays((int) $data['retention_days']);

        return redirect()->route('admin.case-activity-audit')
            ->with('status', 'Audit retention policy saved.');
    }
}
