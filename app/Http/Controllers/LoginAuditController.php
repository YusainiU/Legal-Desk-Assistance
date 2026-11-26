<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LoginAuditController
{
    public function index(Request $request): View
    {
        Gate::authorize('view-login-audit');

        $search = Str::limit(trim($request->string('search')->toString()), 254, '');
        $state = $request->string('state')->toString();
        $state = in_array($state, ['success', 'failed'], true) ? $state : '';
        $totals = DB::table('login_audits')->selectRaw('state, COUNT(*) as total')->groupBy('state')->pluck('total', 'state');
        $audits = DB::table('login_audits')
            ->when($search !== '', fn ($query) => $query->where(fn ($match) => $match->whereLike('username', '%'.$search.'%')->orWhereLike('ip_address', '%'.$search.'%')))
            ->when($state !== '', fn ($query) => $query->where('state', $state))
            ->select('username', 'state', 'ip_address')
            ->selectRaw('COUNT(*) as attempts, MIN(attempted_at) as first_attempt, MAX(attempted_at) as last_attempt')
            ->groupBy('username', 'state', 'ip_address')
            ->orderByDesc('last_attempt')->orderBy('username')->orderBy('state')->orderBy('ip_address')
            ->paginate(15)->withQueryString();

        return view('admin.login-audit', compact('audits', 'totals', 'search', 'state'));
    }
}
