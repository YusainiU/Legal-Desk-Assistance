<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\PageAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PagePermissionController
{
    public function index()
    {
        return view('admin.page-permissions', [
            'pages' => PageAccess::PAGES,
            'actions' => PageAccess::ACTIONS,
            'users' => User::where('has_access', true)->orderBy('name')->get(),
            'groups' => DB::table('permission_groups')->orderBy('name')->get(),
            'rules' => DB::table('page_permissions')->pluck('restricted', 'page'),
            'userGrants' => DB::table('page_permission_user')->get()->groupBy('page'),
            'groupGrants' => DB::table('page_permission_group')->get()->groupBy('page'),
            'members' => DB::table('permission_group_user')->get()->groupBy('permission_group_id'),
        ]);
    }

    public function update(Request $request, string $page)
    {
        abort_unless(PageAccess::validKey($page), 404);
        $data = $request->validate([
            'restricted' => 'required|boolean',
            'users' => 'nullable|array',
            'users.*' => ['integer', 'distinct', Rule::exists('users', 'id')->whereNull('deleted_at')->where('has_access', true)],
            'groups' => 'nullable|array',
            'groups.*' => 'integer|distinct|exists:permission_groups,id',
        ]);
        DB::transaction(function () use ($page, $data) {
            DB::table('page_permissions')->upsert([['page' => $page, 'restricted' => $data['restricted']]], ['page'], ['restricted']);
            foreach (['users' => ['page_permission_user', 'user_id'], 'groups' => ['page_permission_group', 'permission_group_id']] as $key => [$table, $column]) {
                DB::table($table)->where('page', $page)->delete();
                foreach ($data[$key] ?? [] as $id) {
                    DB::table($table)->insert(['page' => $page, $column => $id]);
                }
            }
        });
        \Illuminate\Support\Facades\Log::notice('Permission rule changed', ['actor_id' => $request->user()->id, 'rule' => $page, 'restricted' => $data['restricted'], 'users' => $data['users'] ?? [], 'groups' => $data['groups'] ?? []]);
        return back()->with('status', 'Page permissions saved.')->with('permission_page', explode('.', $page, 2)[0]);
    }

    public function saveGroup(Request $request, ?int $group = null)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('permission_groups', 'name')->ignore($group)],
            'users' => 'nullable|array',
            'users.*' => ['integer', 'distinct', Rule::exists('users', 'id')->whereNull('deleted_at')->where('has_access', true)],
        ]);
        DB::transaction(function () use ($group, $data) {
            if ($group === null) {
                $group = DB::table('permission_groups')->insertGetId(['name' => $data['name']]);
            } else {
                abort_unless(DB::table('permission_groups')->where('id', $group)->lockForUpdate()->first(), 404);
                DB::table('permission_groups')->where('id', $group)->update(['name' => $data['name']]);
            }
            DB::table('permission_group_user')->where('permission_group_id', $group)->delete();
            foreach ($data['users'] ?? [] as $id) {
                DB::table('permission_group_user')->insert(['permission_group_id' => $group, 'user_id' => $id]);
            }
        });
        return back()->with('status', 'Permission group saved.');
    }

    public function deleteGroup(int $group)
    {
        DB::table('permission_groups')->where('id', $group)->delete();
        return back()->with('status', 'Permission group deleted.');
    }
}
