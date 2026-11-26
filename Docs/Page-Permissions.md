# Page Access & Permissions

Super Admins can manage permission groups and page grants under Administration. Groups are separate from teams and can overlap. By default all pages retain existing role restrictions. Restrict a page to require a direct user grant OR membership in a granted group. An empty restricted list denies everyone except active Super Admins. Grants do not elevate existing administrative roles. Dashboard, authentication and permission administration are not configurable to prevent lockout.

Named routes and associated HTTP actions are guarded by EnforcePageAccess, registered as persistent Livewire middleware. Sidebar links use the same gate. Dashboard hides case statistics when Case Register access is denied. This is module access, not record confidentiality: information shared through another permitted module may remain visible. Case and document policies remain independent.

Run php artisan migrate before serving the new code. Verify restricted routes, direct URLs, Livewire requests after revocation, group membership, and existing role restrictions before production use.
