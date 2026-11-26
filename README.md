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
- User administration, firm settings and Super Admin login audit log.

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

## Time charges

Record minutes and hourly rates by stage under **Time & charges**. Each charge is rounded to the nearest cent or equivalent minor unit before case totals are added. Closing a case freezes the total and records it in Case history. The running total can be used when preparing the final invoice.

## User administration

The migration reserves `legaldesk@admin.test` as the default **Super Admin**. If the account is new, set its password and enable sign-in from the server:

```sh
php artisan legal-desk:setup-super-admin
```

This command prompts for a password and confirmation. An existing enabled account at that address keeps its password and gains the Super Admin role on migration. Use the command to reset its password if needed.

Sign in as Super Admin, select **Users**, and set a user’s role to **Administrator** to grant admin access. Administrators can manage staff accounts and view **Firm settings**. Only the Super Admin can grant the Administrator role or change Firm settings. New users need **Granted** workspace access to sign in.

Deleting an account blocks sign-in and removes it from the Users page while retaining its existing case records. Use **Revoked** to suspend an account you may need to reactivate. Case and contact records are shared across authenticated firm staff. Case ownership identifies responsibility, not an access boundary. Public self-registration is disabled.

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
