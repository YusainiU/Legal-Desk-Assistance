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
        if (! $user->has_access || $user->trashed() || ! isset(self::PAGES[$page])) {
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
