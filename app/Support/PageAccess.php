<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PageAccess
{
    public const PAGES = [
        'workspace' => ['Case Register', ['workspace', 'cases.*', 'case-documents.*']],
        'contacts' => ['Contacts', ['contacts.*']],
        'teams' => ['Teams', ['teams.*']],
        'knowledge-base' => ['Build Resources', ['knowledge-base']],
        'library' => ['Library', ['library']],
        'legal-research' => ['Court Judgement Builder', ['legal-research*']],
        'ai-resource-search' => ['Search AI Resources', ['ai-resource-search']],
        'ai-search-admin' => ['AI Search Settings', ['ai-search-admin']],
        'users' => ['Users', ['users.*']],
        'firm-settings' => ['Firm Settings', ['settings.firm.*']],
        'login-audit' => ['Login Audit Log', ['admin.login-audit']],
        'knowledge-guide' => ['Knowledge Base Guide', ['knowledge-base.guide']],
        'getting-started' => ['Getting Started', ['getting-started']],
    ];

    // Action rules reuse the existing grant tables, keyed by page.action.
    public const ACTIONS = [
        'workspace' => ['create','update','archive','restore','advance-stage','reinstate-stage','close','assign-owner','assign-team','export','tasks-create','tasks-update','tasks-delete','tasks-complete','documents-create','documents-delete','documents-download','charges-create','charges-update','charges-delete','charges-export','references-create','references-update','references-delete','contacts-link','send-email','decision-update','decision-publish'],
        'contacts' => ['create','update'],
        'teams' => ['create','update'],
        'users' => ['create','update','delete'],
        'firm-settings' => ['update'],
        'library' => ['create','delete'],
        'legal-research' => ['create','execute','attach'],
        'ai-resource-search' => ['execute','create'],
        'ai-search-admin' => ['update'],
    ];

    public static function validKey(string $key): bool
    {
        if (isset(self::PAGES[$key])) return true;
        [$page, $action] = array_pad(explode('.', $key, 2), 2, '');
        return in_array($action, self::ACTIONS[$page] ?? [], true);
    }

    public static function actionForRoute(?string $route): ?string
    {
        return match ($route) {
            'cases.summary' => 'workspace.export',
            'cases.time-charges.csv' => 'workspace.charges-export',
            'case-documents.download' => 'workspace.documents-download',
            'contacts.edit', 'contacts.update' => 'contacts.update',
            'teams.create', 'teams.store' => 'teams.create',
            'teams.edit', 'teams.update' => 'teams.update',
            'users.create', 'users.store' => 'users.create',
            'users.edit', 'users.update' => 'users.update',
            'users.destroy' => 'users.delete',
            'settings.firm.update' => 'firm-settings.update',
            'legal-research.import' => 'legal-research.create',
            default => null,
        };
    }

    public static function pageForRoute(?string $route): ?string
    {
        foreach (self::PAGES as $page => [, $patterns]) {
            if ($route && Str::is($patterns, $route)) {
                return $page;
            }
        }
        return null;
    }

    public static function allows(User $user, string $page): bool
    {
        if (! $user->has_access || $user->trashed() || ! self::validKey($page)) {
            return false;
        }
        if (str_contains($page, '.') && ! self::allows($user, explode('.', $page, 2)[0])) {
            return false;
        }
        if ($user->is_super_admin) {
            return true;
        }
        if (! DB::table('page_permissions')->where('page', $page)->where('restricted', true)->exists()) {
            return true;
        }
        return DB::table('page_permission_user')->where('page', $page)->where('user_id', $user->id)->exists()
            || DB::table('page_permission_group')->where('page', $page)
                ->whereIn('permission_group_id', DB::table('permission_group_user')->where('user_id', $user->id)->select('permission_group_id'))->exists();
    }
}
