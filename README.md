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

The application and bundled legal resources package currently declare a proprietary licence. No open-source licence has been granted. Repository visibility does not change those terms.

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
