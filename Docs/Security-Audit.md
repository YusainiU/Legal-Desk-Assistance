# Pre-publication security review

Date: 2026-11-26
Reviewed branch: feature/extract-law-resources-package
Snapshot: 34358a19750ca8735d8247907c711a34c4d601fb (file inspection performed against the branch; no runtime verification).

## Scope and limitations
Targeted static inspection of authentication, user administration, document access/upload, case tasks, references, final decisions, email, CSV exports, login audit, and the extracted legal resources package. Filename inspection of 194 tracked files found no obvious credential/database/log files except the intended .env.example. This is not an exhaustive source or history scan, penetration test, dependency audit, or certification.

## Findings and remediation
| ID | Priority | Finding | Recommended action |
|---|---|---|---|
| S-01 | High design risk | CaseDocumentController relies on authenticated shared access, not case-specific policies | Centralize case/document authorization; document single-firm scope |
| S-02 | Medium | Package case selectors and references retain creator scoping | Define shared case versus private research access explicitly |
| S-03 | Medium | CaseFinalDecision and display allow remote images | Remove image allowance consistently |
| S-04 | Low | HTMLPurifier configuration duplicated | Centralize sanitization |
| S-05 | High | No application-specific AI search/analysis quota | Enforce per-user and firm-wide limits on both AI entry points |
| S-06 | High | External AI receives questions; indexed analysis also sends passages | Explicit opt-in, confidentiality notice, provider privacy review |
| S-07 | Medium | Provider error text returned/logged | Generic messages and controlled diagnostics |
| S-08 | Safeguard | AI output marked unverified | Retain independent citation review |
| S-09 | Medium | Uploads lack malware scanning | Optional antivirus/quarantine |
| S-10 | Low | Filesystem deletion cannot roll back with database | Deferred deletion and cleanup |
| S-11 | Safeguard | Fresh super admin has random password and disabled access | Retain explicit setup; review existing-account promotion |
| S-12 | Medium | Fixed demo password, environment guard only | Random or installer-provided demo passwords |
| S-13 | Medium | Login audit coverage and retention incomplete | Verify events, retention, failure handling |
| S-14 | Medium | Case activity audit incomplete | Structured actor/action/record/time audit |
| S-15 | Medium | Case email lacks explicit send quota | Rate limit and monitor outbound delivery |

## Existing controls
Fortify login throttling and 2FA support; active-user provider; explicit admin gates; locked Livewire identifiers; private document storage, extension/MIME/size validation and restrictive preview headers; HTMLPurifier and safe Markdown options in inspected views; CSV formula-injection mitigation; linked-contact email validation and duplicate-send token. These controls require integration tests.

## Release gates
- Scan all Git refs/history and working tree with a secret scanner; rotate any exposed secrets.
- Run composer validate --strict, composer audit, composer check-platform-reqs, npm audit, npm ci and npm run build.
- Review binary documentation metadata and redistribution rights.
- Verify authentication, revoked sessions, role boundaries, Livewire actions, document access, XSS, AI quotas, and task deadline behavior using automated tests and a clean deployment.
- Confirm production HTTPS, APP_DEBUG=false, secure session cookies, private backups and data retention.
- Resolve high-priority findings or document accepted risks before public release.

No application code was modified by this review. No credentials, repository visibility or history were changed.

### S-03 / S-04 remediation

Centralized legal HTML sanitization in the law resources package (Support/LegalHtmlSanitizer). Final decision save, editor initialization, publication, case closure validation, summary and Library rendering use the shared sanitizer. AI Markdown display also passes through it to remove embedded images. Existing stored HTML is not rewritten automatically; legacy images are removed on these read paths and on subsequent saves. Supporting images should be attached as case documents. Package declares HTMLPurifier explicitly; refresh the Composer lock for the path package. Unit tests added; runtime and browser verification pending. This does not certify unrelated HTML rendering or the rich editor's own upload endpoints.

### AI security (S-05–S-07)
Run `php artisan migrate` before use. AI Search administration explicitly enables external AI search and indexed analysis; without a saved enablement setting both are disabled, even if the old environment flag is true. Existing saved enablement is retained. Default shared quotas: 5 requests/user/minute, 50/user/day, 500/installation/day (fixed UTC windows). Failed attempts count; these are request counts, not token or monetary limits. Both AI actions require confidentiality acknowledgement; successful requests reset it. Indexed analysis sends the question and selected passages to OpenAI, while public search sends the question and filters to the configured provider. Review provider terms and authorization before enabling. No raw provider errors are intentionally logged by these guarded paths. Existing research remains available. Quota rows are retained; periodic cleanup is outstanding. Runtime, concurrency, and browser testing remain outstanding.

### Document security (S-09 / S-10)
Run php artisan migrate. Optional ClamAV scanning is disabled by default; install ClamAV, maintain current signatures and set DOCUMENT_SCAN_ENABLED=true in .env, then clear configuration cache. DOCUMENT_SCAN_BINARY must point to a trusted clamscan executable. Uploads are scanned in temporary storage before permanent storage or database registration; detection, timeout or scanner failure rejects the upload. Rejected temporary files remain subject to Livewire temporary upload cleanup; no persistent quarantine or release workflow is provided. Existing documents are not retrospectively scanned. Ensure temporary and permanent storage are private. Deletion records are committed with document deletion, then filesystem cleanup is attempted after commit. Run php artisan legal-desk:document-cleanup to retry; hourly scheduling requires Laravel scheduler running. Add --orphans to report unreferenced files older than 24 hours; this does not delete them. Review backups and retention requirements before manual removal. Runtime tests and scanner verification remain outstanding.

### Secure demo accounts (S-12)

Demo seeding is restricted to local and testing environments. Run `php artisan db:seed --class=DemoDataSeeder` only in a disposable development or test database. Newly seeded demo users receive unique random passwords and revoked sign-in access. To use one, sign in as an authorized administrator, open Users, set a new password and grant access. Existing accounts are not changed by reseeding; review and rotate or revoke any previously seeded credentials, especially before deployment. The `matterdesk:user {email} --demo` command is also restricted to local/testing and requires an operator-supplied password of at least 12 characters and at most 72 bytes. It does not use the seeder's random-password workflow. Keep APP_ENV=production on production servers. Historical commits may still contain the former demo password; it must not be reused. Runtime verification remains outstanding.


### Case email quotas (S-15)

Run `php artisan migrate` after upgrading. Case correspondence requires existing send-email permission, an open case, a linked contact and a configured delivery mailer. Set the following in .env to override defaults:

```dotenv
CASE_EMAIL_USER_MINUTE=5
CASE_EMAIL_USER_DAILY=50
CASE_EMAIL_FIRM_DAILY=500
```

Clear or rebuild configuration cache after changes. Zero blocks new case email attempts. Limits apply across cases, use fixed UTC minute/day windows, and count reserved delivery attempts including unconfirmed delivery. Duplicate existing email tokens do not reserve another quota. The quota reservation and pending correspondence entry share a database transaction. When a limit is reached, wait until the relevant window resets or contact an administrator. Do not repeatedly resend unconfirmed messages; check with the mail provider first. Logs record entry ID, actor ID and outcome, not message contents or recipient in the new diagnostic event. Case history intentionally retains correspondence. These quotas do not cover authentication or other framework mail. The scheduler runs `legal-desk:email-quota-cleanup` daily to remove counters expired more than two days ago; the command can also be run manually. Runtime and concurrency testing remain outstanding.


### Login audit retention (S-13)

Login audit persistence failures no longer interrupt authentication; monitor application error logs for audit gaps. `LOGIN_AUDIT_RETENTION_DAYS=90` sets retention (minimum one day). Choose retention according to firm policy before enabling the scheduler. `php artisan legal-desk:login-audit-cleanup` permanently deletes expired records and runs daily through the scheduler. Existing Login/Failed event coverage is retained; throttled attempts and unsuccessful second-factor/passkey challenges are not guaranteed to be captured. Runtime verification remains outstanding.


### Structured case activity audit (S-14)

Run `php artisan migrate` before using the upgraded application. The `case_activity_audits` table records actor ID (nullable for system operations), case ID, record type and ID, action, changed field names and timestamp. Eloquent observers cover create/update/delete events for matters, entries (including tasks and email), documents, references and time charges. Authorized document preview response preparation is recorded separately; this does not prove receipt of the file. The current download controller does not record a download audit event. No field values or document contents are copied. Audit inserts participate in existing database transactions; operations outside a transaction are not guaranteed atomic. Bulk query updates, pivot changes, package models and administrative changes are not comprehensively covered. S-16 adds a restricted audit viewer and configurable retention cleanup. Tamper-resistant external storage is not provided. Restrict database access and establish a retention policy. Runtime verification remains outstanding.


### Case Activity Audit administration (S-16)

After upgrading, run `php artisan migrate` and clear or rebuild application configuration and route caches. Active Super Administrators can open **Administration → Case Activity Audit**. Ordinary users, Admins, disabled accounts and guests cannot access the audit viewer or change its retention policy. This permission is deliberately not delegable through Page Access & Permissions.

The viewer lists 20 records per page, newest first. Filter by user, exact case ID, activity, or inclusive date range; the case reference search accepts a numeric ID (optionally prefixed with #) or case title. Filters can be combined and are preserved across pagination. Select **View details** for actor ID, case ID, record type and ID, action, timestamp and changed field names. Names and titles reflect current records, not historical snapshots; unavailable records retain their stored IDs. Field values and document contents are not displayed. Individual audit records cannot be edited or deleted through this page.

The retention policy is stored in firm settings. The default is indefinite (no automatic deletion). Available finite periods are 90, 180, 365, 730, 1825 and 2555 days. Year labels represent fixed day counts, not calendar anniversaries. A Super Administrator can select a period and save it after confirming the warning. Finite retention permits permanent deletion of expired records. Review legal holds, professional obligations and backup policies first; there are no per-case retention exemptions. Keep indefinite retention if records must be preserved.

The Laravel scheduler registers `legal-desk:case-audit-cleanup` daily with overlap prevention. Ensure the existing scheduler is active and verify with `php artisan schedule:list`. To preview eligible records without deletion:

```bash
php artisan legal-desk:case-audit-cleanup --dry-run
```

To execute cleanup manually, only after approving the policy:

```bash
php artisan legal-desk:case-audit-cleanup
```

Cleanup deletes only audit rows strictly older than the cutoff, in batches of up to 500. It checks the current retention policy under a database lock before each deletion batch and stops remaining processing if the policy changes. Cleanup results are written to application logs. Indefinite or unsupported stored retention settings do not trigger deletion. No underlying case or document is deleted.

#### Verification before public release

Install development dependencies locally, clear cached configuration, and run the focused tests and full suite:

```bash
composer install
php artisan config:clear
./vendor/bin/phpunit --filter CaseActivityAuditAdministrationTest
./vendor/bin/phpunit
```

The committed PHPUnit configuration uses an in-memory SQLite database, array sessions/cache/mail and a test-only application key. PHP SQLite support is required. Verify migration compatibility and test isolation in a development environment; do not run tests against production data. The tests cover guest and role restrictions, combined filters, pagination, retention validation, indefinite retention, dry-run and expiry boundaries. Also manually verify the Flux details modal, navigation, date validation and scheduler on a staging installation using the production database engine.

Implementation is committed, but runtime tests, migration compatibility and staging verification remain outstanding. The audit is not tamper-resistant or immutable; restrict database and log access. Bulk changes and other S-14 coverage gaps remain. Document preview is recorded, but the current download controller does not record a download event; correct and test that gap before public release. Retention policy changes are not independently recorded in the case audit table. Do not treat this feature as proof of complete regulatory compliance.
