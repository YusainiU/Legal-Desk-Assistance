# Centralized case authorization

Phase 1 retains the single-firm shared case model. MatterPolicy controls case permissions and CaseDocumentPolicy delegates to the parent case. Matter::accessibleTo() is the shared query boundary. Existing workflow state checks remain separate from authorization: in particular, description edits and publishing a final decision retain their previous status rules. Permanent case deletion is denied by policy.

The package's configured matter model must implement accessibleTo(User) and have an appropriate registered policy. Case references and case selectors now follow shared case access; personal research and saved AI notes retain creator scoping. This intentionally makes case references visible to active colleagues, consistent with shared cases.

Before adding confidential cases, extend both the query scope and record policy consistently, review all relationship queries, research copies, exports and derived data, and add integration tests. Phase 1 is not a confidentiality barrier between active staff.

Run php artisan test --filter=MatterAuthorizationTest and verify actual routes and Livewire actions in a clean installation. No schema changes are required.
