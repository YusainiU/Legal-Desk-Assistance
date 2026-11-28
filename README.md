# Legal Desk Assistance

A self-hosted, single-firm legal case management application built with Laravel 13, Livewire 4, Flux UI, Tailwind CSS 4 and MySQL. Authenticated staff share the case register and contacts. It is not a multi-tenant application or a substitute for professional legal advice.

See the [user tutorial](Docs/User-Tutorial.md) for a walkthrough from sign-in to file closure.

## Run locally

Install PHP 8.4.1 or newer (required by the current lockfile) with the Laravel extensions and `pdo_mysql`, Composer, Node.js 22.12 or newer, and a MySQL server. Create a MySQL database and user with access to that database. Configure the database settings in `.env` before running the migration.

```sh
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
npm ci
npm run build
php artisan legal-desk:setup-super-admin
php artisan serve
```

Open http://localhost:8000 and sign in as `legaldesk@admin.test` with the password set at the prompt. Create staff accounts through Users. Demo data is optional and intended only for local/testing environments. To work on the styles with Vite hot reload, run `npm run dev` in a separate terminal. For a hosted environment, point the web root at `public/`, set `APP_ENV=production` and `APP_DEBUG=false`, and serve it over HTTPS.

## Production deployment (Ubuntu / Apache / MySQL)

This is a self-hosted, single-firm application. Staff share case data; it is not a multi-tenant service. Public repository visibility does not constitute a production security certification; see the Licence section for licensing terms. Review the licence terms, [security audit](Docs/Security-Audit.md) and [release checklist](Docs/Public-Release-Checklist.md) before deploying real client data.

The following example assumes a dedicated Ubuntu server, Apache, PHP-FPM (or an appropriately configured Apache PHP handler), MySQL and a dedicated HTTPS hostname. Replace example paths, hostnames and account names with your own. Use a staging installation first.

### 1. Prepare the server

- Install a supported PHP version compatible with the lockfile (PHP 8.4.1 or newer), matching CLI and web PHP versions, Composer 2, Node.js 22.12 or newer, npm, Git, Apache and MySQL.
- Enable PHP extensions required by Composer and Laravel, including PDO MySQL, mbstring, XML/DOM, cURL, OpenSSL, fileinfo, tokenizer and ZIP. SQLite is needed for the development test suite.
- Configure DNS, HTTPS, firewall rules and security updates. Restrict MySQL to trusted hosts and do not expose it publicly.
- Use an unprivileged deployment account and a separate web-service account/group. Do not run Composer, npm or Artisan as root.

### 2. Clone the reviewed release

```sh
git clone https://github.com/YusainiU/Legal-Desk-Assistance.git
cd Legal-Desk-Assistance
git rev-parse HEAD
```

Choose a reviewed release tag or commit before deployment; the default branch may not contain all recent changes. Record the deployed commit. Composer must be able to access the Rich HTML Editor dependency and the bundled local package. Do not bypass platform checks or substitute an unreviewed dependency if installation fails.

### 3. Create a dedicated database

Connect as a MySQL administrator and adapt the following example. Replace the password placeholder with a unique strong secret; do not reuse it elsewhere.

```sql
CREATE DATABASE legal_desk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'legal_desk'@'localhost' IDENTIFIED BY 'REPLACE_WITH_UNIQUE_SECRET';
GRANT ALL PRIVILEGES ON legal_desk.* TO 'legal_desk'@'localhost';
```

These privileges are scoped to the application database and allow migrations. Where operationally feasible, use a separate migration account and reduce runtime privileges after testing.

### 4. Configure the production environment

```sh
cp .env.example .env
chmod 600 .env
```

Ensure the web PHP process can read the environment file through appropriately restricted ownership/group permissions (for example mode 640 with a dedicated trusted group). Never commit it. Edit it before installation:

```dotenv
APP_NAME="Legal Desk Assistance"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://legal.example.com
APP_KEY=

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=legal_desk
DB_USERNAME=legal_desk
DB_PASSWORD="REPLACE_WITH_UNIQUE_SECRET"

SESSION_DRIVER=file
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
CACHE_STORE=file
QUEUE_CONNECTION=sync

AI_CASE_ASSISTANT_ENABLED=false
AI_SEARCH_LOCATION=Malaysia
AI_MANAGEMENT_PROVIDER=openai
AI_MANAGEMENT_MODEL=gpt-4o-mini
AI_CASE_ASSISTANT_ENABLED=true
OPENAI_BASE_URL=https://api.openai.com/v1
OPENAI_API_KEY=

DOCUMENT_SCAN_ENABLED=true
DOCUMENT_SCAN_BINARY=/usr/bin/clamscan
DOCUMENT_SCAN_TIMEOUT=60

LOGIN_AUDIT_RETENTION_DAYS=90

CASE_EMAIL_USER_MINUTE=5
CASE_EMAIL_USER_DAILY=50
CASE_EMAIL_FIRM_DAILY=500


# SMTP email delivery (replace placeholders with your provider settings)
# For local development without sending email, use MAIL_MAILER=log.
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=smtp.example.test
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=hello@example.test
MAIL_FROM_NAME="${APP_NAME}"

```

Choose audit retention according to your firm's obligations, not merely the example. Install and verify ClamAV before accepting uploads. Configure a real SMTP transport before enabling staff correspondence or password reset email; the log transport is not delivery and may retain sensitive mail content. Keep external AI disabled until explicitly approved in AI Search Settings, including provider confidentiality terms and quotas.

### 5. Install and initialize

From the application root, as the deployment account:

```sh
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
composer check-platform-reqs --no-dev
php artisan key:generate --force
php artisan migrate --force
npm ci
npm run build
php artisan legal-desk:setup-super-admin
```

Generate APP_KEY only on a fresh installation; preserve it securely for upgrades and disaster recovery. Never run migrate:fresh, migrate:refresh or demo seeders on production data. The setup command prompts for a new password for legaldesk@admin.test; use a unique strong password. Create staff accounts through Users, grant only required access and configure MFA where supported. Review the reserved administrator email and recovery arrangements before opening access.

### 6. Configure filesystem permissions and Apache

Place the application at a path such as /var/www/legal-desk-assistance. The web process needs read access to application code and write access to storage and bootstrap/cache only. Configure ownership and group permissions for your service accounts; never use chmod 777. Keep private documents under storage/app/private and outside the public web root. Do not expose private storage through a symlink or Apache alias.

Example HTTPS virtual host (assuming TLS certificates already provisioned and PHP handling configured):

```apache
<VirtualHost *:443>
    ServerName legal.example.com
    DocumentRoot /var/www/legal-desk-assistance/public
    SSLEngine on
    SSLCertificateFile /path/to/fullchain.pem
    SSLCertificateKeyFile /path/to/privkey.pem

    <Directory /var/www/legal-desk-assistance/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Enable rewrite and SSL modules, verify the Laravel public/.htaccess exists (restore the standard Laravel front-controller rewrite rules if missing), configure HTTP-to-HTTPS redirection, then validate and reload Apache:

```sh
sudo a2enmod rewrite ssl
sudo apache2ctl configtest
sudo systemctl reload apache2
```

For a subdirectory deployment, configure an Alias to public/ only and set APP_URL to the full HTTPS subpath. Verify Livewire requests, assets and authentication redirects. Do not use php artisan serve or npm run dev as production servers.

### 7. Enable document scanning and scheduled maintenance

```sh
sudo apt install clamav clamav-freshclam
clamscan --version
sudo systemctl status clamav-freshclam
php artisan optimize:clear
php artisan schedule:list
```

Ensure current signatures and scanner access from the web PHP account. Scanner errors reject uploads when scanning is enabled. Configure PHP and web-server request limits for 10 MB attachments plus multipart overhead.

Add one cron entry under the application service account (adjust paths and PHP executable):

```cron
* * * * * cd /var/www/legal-desk-assistance && /usr/bin/php artisan schedule:run
```

Route scheduler output to monitored logs with rotation. Scheduled tasks include document deletion retries, email quota cleanup and audit retention cleanup. Review retention before enabling cron. With QUEUE_CONNECTION=sync no persistent queue worker is required; if switching to an asynchronous queue, configure a supervised worker and restart it after deployments.

### 8. Cache configuration and verify before go-live

```sh
php artisan optimize:clear
php artisan config:cache
php artisan view:cache
php artisan migrate:status
php artisan schedule:list
```

In a separate staging/development environment with development dependencies and SQLite installed, run ./vendor/bin/phpunit. The suite passed 17 tests on PHP 8.4.28 during local verification; this is not a substitute for staging tests with MySQL.

- Verify HTTPS, login/logout, password recovery, MFA, staff permissions and direct unauthorized requests.
- Verify case workflow, private upload/download, malware scanner failure handling, PDF exports, email delivery and audit entries.
- Verify that .env, private documents, Git metadata and logs cannot be fetched directly over HTTP.
- Configure Firm Settings, permission groups and audit retention. Keep AI disabled unless approved.
- Establish encrypted, access-controlled backups of MySQL, private storage and required secrets; test restoration. Monitor errors, disk space, scheduler, mail and security updates.
- Review confidentiality, data protection and legal retention requirements before entering real client information.

### 9. Maintain and upgrade

Back up database and private files before every upgrade. Review release notes and dependency advisories, test the selected revision in staging, and schedule downtime where needed. Deploy as the unprivileged application operator:

```sh
php artisan down
# Fetch and check out the reviewed revision using your deployment process.
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan view:cache
php artisan up
```

Restart supervised queue workers if configured. Verify functionality and monitor logs. If a step fails, keep maintenance mode enabled until recovery; do not blindly roll back code after database changes. Use a tested rollback and restore plan. Run Composer and npm vulnerability audits regularly and apply reviewed updates. Never regenerate the existing application key or reseed production.


## Features

- Dashboard with open case counts, task deadlines and workflow stage totals.
- Nine-stage case register, archive and restore, ownership and incomplete task views.
- Task deadlines with configurable warning hours, contacts, teams, private documents and time charges.
- Knowledge base, judgment library, AI-assisted legal research and case references.
- Final decisions, printable case summaries and standalone legal reference PDF reports.
- Centralized legal HTML sanitization, optional malware scanning and recoverable document cleanup.
- User administration, configurable page access for individual users and permission groups, firm settings and Super Admin login audit log.

AI research is optional and requires provider configuration in .env. Verify citations and avoid submitting confidential client information without appropriate authorization and safeguards.

See the [Knowledge Base guide](docs/knowledge-base-user-guide.md), [security policy](SECURITY.md), [contribution guide](CONTRIBUTING.md) and [release checklist](Docs/Public-Release-Checklist.md).

## Licence

Legal Desk Assistance and its bundled legal resources package are licensed under the GNU General Public License, version 3 only (GPL-3.0-only). See [LICENSE](LICENSE) for the full licence text. Third-party dependencies remain subject to their respective licences. The software is provided without warranty as described in the licence. Public availability does not constitute a production security certification.

## Application structure

Run Composer, Artisan, and Vite from this repository’s root. The root contains the Laravel app, Fortify configuration, Flux views, and MySQL migrations.

Fortify’s authentication views live in `resources/views/login.blade.php` and `resources/views/auth/`. For local password reset testing, the example environment writes mail to the Laravel log. Configure your mail transport before sending reset links to real users.

## Case contacts and email

Create an address-book contact before opening a case. A contact can have an email address, a phone number, or both. Existing cases stay in place. Link a contact from **Case contacts** before advancing an older case that has no linked contact.

To send email from an open case, configure a delivery mailer in `.env`, such as `MAIL_MAILER=smtp` with your provider’s host, port, credentials, and verified `MAIL_FROM_ADDRESS`. Run `php artisan config:clear` after changing the environment. The local `log` and `array` transports do not send case email. Test with a mailbox you control. A **Sent to mail provider** entry confirms handoff to the mailer. If the attempt is unconfirmed, check with your provider before composing a new email.

## Case documents

Attachments are stored on Laravel’s private local disk under `storage/app/private/case-documents`. Use persistent storage and back up this directory alongside MySQL. Keep it outside the public web root. Each upload is limited to 10 MB. Set PHP’s `upload_max_filesize` and `post_max_size` high enough to accept files and the multipart request overhead. Apply equivalent request-size limits in your web server if applicable.

## Final decisions and PDF reports

Record the outcome in the **Final Decision** tab before closing a case. Save the decision and optionally publish it to the Knowledge Base. Legal HTML is sanitized centrally; embedded images are not supported. Attach supporting images under Documents instead. Existing stored HTML is not automatically rewritten.

**Print case summary** includes the final decision as plain text with paragraph breaks, completed stages and history. **Legal References → Print references** previews, prints or downloads all references for the case, not only the current page. It excludes the case's published final-decision reference. Reports include citation, jurisdiction, source URL and applicability where recorded. Printing works for open and closed cases subject to permissions. The separate `workspace.references-export` permission controls reference reports; the existing case export permission controls summaries.

## Time charges

Record minutes and hourly rates by stage under **Time & charges**. Each charge is rounded to the nearest cent or equivalent minor unit before case totals are added. Closing a case freezes the total and records it in Case history. The running total can be used when preparing the final invoice.

## User administration

The migration reserves `legaldesk@admin.test` as the default **Super Admin**. If the account is new, set its password and enable sign-in from the server:

```sh
php artisan legal-desk:setup-super-admin
```

This command prompts for a password and confirmation. On fresh migrations, an existing account at that address is not automatically promoted. The setup command requires explicit confirmation, replaces the password, invalidates reset and remember tokens, and removes database-backed sessions. Other session stores require separate invalidation. Previously applied migrations are not rerun; review existing privileged accounts manually. Use the command to reset its password if needed.

Sign in as Super Admin, select **Users**, and set a user’s role to **Administrator** to grant admin access. Administrators can manage staff accounts and view **Firm settings**. Only the Super Admin can grant the Administrator role or change Firm settings. New users need **Granted** workspace access to sign in.

Deleting an account blocks sign-in and removes it from the Users page while retaining its existing case records. Use **Revoked** to suspend an account you may need to reactivate. Case and contact records are shared across authenticated firm staff. Case ownership identifies responsibility, not an access boundary. Public self-registration is disabled.

## Page access and permissions

Sign in as an active Super Admin and open **Administration → Page Access & Permissions**. Create permission groups, select their members, and save them. Groups are independent of operational teams; users can belong to multiple groups.

Under **Page permissions**, expand a page and choose **All eligible active users** (the default) or **Selected users and groups only**. In restricted mode, select permitted groups and/or individual users, then select **Save permissions**. Either a direct grant or membership in an allowed group is sufficient. An empty restricted list denies ordinary users and administrators. Active Super Admins retain access.

Configurable pages include Case Register, Contacts, Teams, Build Resources, Library, Court Judgement Builder, Search AI Resources, AI Search Settings, Users, Firm Settings, Login Audit Log, Knowledge Base Guide and Getting Started. Existing role requirements still apply; a page grant does not make a staff member an administrator. Dashboard, authentication and permission administration remain available according to existing account and role rules.

Restricted sidebar links are hidden and direct requests are guarded server-side, including associated document and export routes. The Dashboard hides case statistics when Case Register access is denied. Page permissions do not isolate individual cases or prevent related information appearing in other permitted modules.

For an existing installation, run `php artisan migrate` and `php artisan optimize:clear` after updating. See the [user tutorial](Docs/User-Tutorial.md#13-manage-page-access-and-permissions) and [technical permission guide](Docs/Page-Permissions.md).

## Teams

Administrators can open **Teams → New team**, enter the required name and description, and select members. Use **Edit team** to update the team or change its membership.

Assign an optional team when creating a case or select **Assign Team** or the assigned team’s name on an open case. The case retains its owner. Tasks have a separate optional team field, so a task can be assigned to a different team from its case.

The **Teams** page lists members, assigned open cases, and incomplete tasks on open cases. Staff see the teams they belong to. Administrators can see all teams. Team membership does not isolate the shared case register.

## Archive and restore cases

Archiving removes cases from the Case Register without deleting their records or changing their status or workflow stage. Open, Closed and Declined cases can all be archived.

1. In **Case register**, tick the checkbox beside one or more cases.
2. Select **Archive** and confirm. The selected cases move to the Archive list.
3. Open the **Archive** tab to search or filter archived cases, or select a case title to review its details.
4. Tick one or more archived cases and select **Restore** to return them to the Case Register.

Both actions are recorded in **Case history** with the name of the user who performed them. Restoring a closed or declined case does not reopen it. Archiving is not file closure and does not complete outstanding tasks.

## Workflow

1. Interview the client and review urgent dates.
2. Analyse the facts and record preliminary checks.
3. Accept or decline the case, with reasons. Acceptance requires a cleared conflict review.
4. Record warrant execution, the retainer request, and payment.
5. Attend to the case using guidance for Civil, Litigation, Conveyancing, Criminal, Syariah, or the selected practice area.
6. Record interim invoices and payments.
7. Log progress shared with the client.
8. Record the outcome and issuance of the final invoice.
9. Close the file once the closure checks and open tasks are complete.

Client updates and billing entries can be recorded throughout an open matter. The app records milestones and notes for work handled externally, including signing, issuing invoices, collecting payments, and communicating with clients.

The Laravel application starts with its own database. The existing static demonstration uses browser storage. The Laravel port does not import those records. Case access is shared within the firm. Task assignees are recorded by name, and team assignments appear in the Teams page.

### Granular operation permissions

In Administration → Page Access & Permissions, each module now has a View rule and separate operation rules where applicable. Existing page grants remain unchanged. Unrestricted operations inherit View access; restricted operations require a direct or group grant in addition to View. To configure view-only access, grant View and restrict every operation, leaving its grant list empty or granting it only to other groups. To allow creation but not editing, grant Create and restrict Update. Case Register has separate rules for tasks, documents, charges, references, workflow, email and final decisions. Existing roles and workflow restrictions still apply. Active Super Admins bypass these restrictions. Operation rules reuse the existing permission tables; no additional migration is required after the original page permissions migration. Controls may still appear when an operation is denied; the server rejects guarded actions with HTTP 403. This is not a confidential-case boundary. Test each operation and cross-module path before production deployment.

Expand the application page under Page permissions, then click View or a granular operation to open its permission dialog. Select the access mode, groups and individual users, and save. Use the user search to filter the list; selected users remain selected when filtered.

## AI research security and configuration

Configure the provider credentials in the environment using the supplied .env.example. Open **AI Search Settings** as an authorized Administrator or Super Admin. Select the jurisdiction, explicitly enable external AI research and analysis, configure the quotas, and save.

| Shared quota | Default |
| --- | ---: |
| Per user per minute | 5 |
| Per user per day | 50 |
| Per installation per day | 500 |

Both public AI search and indexed judgment analysis consume these database-backed quotas. Fixed windows reset on UTC minute/day boundaries; failed provider attempts count. These are request counts, not token or spending limits. Without a saved enablement setting, AI is disabled regardless of the old environment flag. Existing saved settings are retained. Disabling AI does not remove saved research.

Before submitting, acknowledge the confidentiality notice. Public search sends the question and filters to the configured provider; indexed analysis sends the question and selected judgment excerpts to OpenAI. Do not submit privileged, confidential or personal information without authorization. Review provider privacy terms and independently verify citations and legal propositions. Successful requests reset acknowledgement. Provider failures are presented as controlled messages; guarded diagnostics omit raw provider responses and question text. Expired quota records currently have no scheduled cleanup.

Run `php artisan migrate` after upgrading. Verify disabled access, missing acknowledgement, shared quota exhaustion and safe provider failures before production use.

## Document security and maintenance

Case attachments are private. Optional ClamAV scanning checks temporary uploads before permanent storage or database registration. Scanning is disabled by default; enable it for deployments handling real documents.

On Ubuntu:

```sh
sudo apt update
sudo apt install clamav clamav-freshclam
clamscan --version
sudo systemctl status clamav-freshclam
```

Keep virus signatures current and ensure the PHP process can execute the trusted scanner and read temporary files. Configure .env:

```dotenv
DOCUMENT_SCAN_ENABLED=true
DOCUMENT_SCAN_BINARY=/usr/bin/clamscan
DOCUMENT_SCAN_TIMEOUT=60
```

Run `php artisan config:clear` (and rebuild the production config cache if used). When enabled, detection, scanner errors and timeouts reject uploads. Existing documents are not retrospectively scanned. Rejected temporary files are handled by Livewire temporary cleanup; there is no persistent quarantine or administrator release workflow. Antivirus is not a guarantee of safety.

Document deletion records are committed with the database deletion. Physical cleanup runs after commit; failed cleanup remains recorded for retry. Run:

```sh
php artisan migrate
php artisan legal-desk:document-cleanup
php artisan legal-desk:document-cleanup --orphans
php artisan schedule:list
```

The orphan option reports unreferenced files older than 24 hours; it does not delete them. Review retention obligations and backups before manual removal. Cleanup is scheduled hourly, provided the Laravel scheduler is running. For example, configure the application account's cron with the correct deployment path and PHP executable:

```cron
* * * * * cd /var/www/legal-desk-assistance && php artisan schedule:run >> /dev/null 2>&1
```

Do not duplicate an existing scheduler entry. Scanner rejection/failure and cleanup exceptions have controlled application log entries. Verify clean uploads, scanner failure, deletion rollback and cleanup in a test environment before production use.

## Verification status

The recent security controls and PDF enhancements have been implemented, but runtime, browser and security regression verification remains outstanding. See [Security Audit](Docs/Security-Audit.md) for limitations and release gates.


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


### Professional case correspondence

Outgoing case emails use a reusable HTML letterhead with the firm name from Firm Settings, the entered subject and message, and a confidentiality notice. A plain-text alternative is included. Enter the greeting and message yourself; the application preserves your line breaks and treats the message as plain text rather than executable HTML. Both email formats automatically append Kind regards, the authenticated sender's display name and the firm name. Omit your manual sign-off to avoid duplication. The case history retains the entered message rather than the rendered signature. No case details are added automatically. Existing permissions, quotas, delivery status and case history are unchanged. Templates are in `resources/views/emails/case-correspondence.blade.php` and `case-correspondence-text.blade.php`. Authentication emails are unchanged.


### Login audit retention (S-13)

Login audit persistence failures no longer interrupt authentication; monitor application error logs for audit gaps. `LOGIN_AUDIT_RETENTION_DAYS=90` sets retention (minimum one day). Choose retention according to firm policy before enabling the scheduler. `php artisan legal-desk:login-audit-cleanup` permanently deletes expired records and runs daily through the scheduler. Existing Login/Failed event coverage is retained; throttled attempts and unsuccessful second-factor/passkey challenges are not guaranteed to be captured. Runtime verification remains outstanding.


### Structured case activity audit (S-14)

Run `php artisan migrate` before using the upgraded application. The `case_activity_audits` table records actor ID (nullable for system operations), case ID, record type and ID, action, changed field names and timestamp. Eloquent observers cover create/update/delete events for matters, entries (including tasks and email), documents, references and time charges. Authorized document preview and download response preparation is recorded separately; this does not prove receipt of the file. No field values or document contents are copied. Audit inserts participate in existing database transactions; operations outside a transaction are not guaranteed atomic. Bulk query updates, pivot changes, package models and administrative changes are not comprehensively covered. S-16 adds a restricted audit viewer and configurable retention cleanup. Tamper-resistant external storage is not provided. Restrict database access and establish a retention policy. Runtime verification remains outstanding.


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

See [the user tutorial](Docs/User-Tutorial.md) for operational guidance and [the security audit](Docs/Security-Audit.md) for release verification and limitations.

#### Verification before public release

Install development dependencies locally, clear cached configuration, and run the focused tests and full suite:

```bash
composer install
php artisan config:clear
./vendor/bin/phpunit --filter CaseActivityAuditAdministrationTest
./vendor/bin/phpunit
```

The committed PHPUnit configuration uses an in-memory SQLite database, array sessions/cache/mail and a test-only application key. PHP SQLite support is required. Verify migration compatibility and test isolation in a development environment; do not run tests against production data. The tests cover guest and role restrictions, combined filters, pagination, retention validation, indefinite retention, dry-run and expiry boundaries. Also manually verify the Flux details modal, navigation, date validation and scheduler on a staging installation using the production database engine.

Implementation is committed, but runtime tests, migration compatibility and staging verification remain outstanding. The audit is not tamper-resistant or immutable; restrict database and log access. Bulk changes and other S-14 coverage gaps remain. Document preview and download response preparation are recorded. Actual retention changes are recorded transactionally as firm-wide retention_changed events, with the actor and changed field name but not previous or new values. These events are subject to the same retention policy. Runtime verification remains required. Do not treat this feature as proof of complete regulatory compliance.


### Temporary demo accounts (Super Admin only)

After running migrations, open Users → New user, enter a unique password, select Staff and Granted access, and set Demo account expiry in the displayed application timezone. Leave expiry blank for a regular account. Only Super Admins can create or manage demo accounts. Demo accounts cannot be promoted to Administrator or have their expiry removed.

At expiry the active-user provider denies access even if scheduled cleanup is delayed. The scheduler runs `legal-desk:expire-demo-users` every minute to rotate the password to an undisclosed random value, revoke access, invalidate reset/remember credentials, remove passkeys and two-factor credentials, and delete database sessions when configured. Demo web sessions are checked against a password fingerprint. Existing case data is preserved. To renew, edit the account, select a future expiry, enter a new password and grant access. Do not reuse previous demo passwords. Re-enrol authentication factors if needed.

Verify the scheduler with `php artisan schedule:list`; run `php artisan legal-desk:expire-demo-users` manually to process due accounts. Test login, expiry, renewal, Livewire, passkeys and session invalidation in staging before public use. Demo accounts are not isolated from other permitted case data: use a separate demonstration installation containing fictional data only.

## Guided installer and updater (experimental)

See [installation guide](Docs/Installation-Guide.md) and [installer FAQ and release checklist](Docs/Installer-FAQ.md). Review scripts before execution. During branch testing use `bash install.sh --ref feature/guided-installer`; run `bash install.sh --check-only` first. Interactive production installation offers optional services and deployment helpers after application setup. Database provisioning is available interactively or as a separate pre-install step. Apache activation, certificates and PHP-FPM pool setup remain manual. For existing installations use `bash update.sh REVIEWED_REF /absolute/private/backup-directory` only after staging and restore tests. Run `bash tests/installer/smoke.sh` for non-destructive validation. CI and real Ubuntu/MySQL deployment and recovery results must be verified before release.

### Phase 4.1: database setup

Interactive install.sh now clones the selected release before presenting the database menu. Choose an existing empty database or provision a new local database. For provisioning choose sudo socket authentication, root password, or a custom local administrator with access to mysql.user and schema creation/grant privileges. The MySQL client prompts for the administrator password twice (validation and creation); it is not stored by the installer. The application password is generated automatically and stored in the restricted .env on successful configuration. Standalone bash installer/database.sh remains available and displays the generated application password. Existing database credentials can be retried; empty passwords are rejected. Non-interactive mode requires an existing database. Provisioning failures may leave partial objects and failed installations retain the cloned directory; inspect both before retrying, never delete existing data blindly. Automatic provisioning is local only. Test all authentication paths on disposable MySQL before release.

Database provisioning reports existing database names and usernames separately. Choose to retry with alternative names without cloning again, or cancel. No existing objects are deleted or overwritten. Password-based administrators may be prompted again for each conflict check.
