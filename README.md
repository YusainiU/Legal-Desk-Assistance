# Legal Desk Assistance

An authenticated legal matter workflow prototype built with Laravel 13, Livewire 4, Tailwind CSS 4, and MySQL. The matter register keeps a case history, checklists, tasks, deadlines, client updates, and billing records for each signed-in account.

See the [user tutorial](Docs/User-Tutorial.md) for a walkthrough from sign-in to file closure.

## Run locally

Install PHP 8.3 or newer with the Laravel extensions and `pdo_mysql`, Composer, Node.js 22.12 or newer, and a MySQL server. Create a MySQL database and user with access to that database. Configure the database settings in `.env` before running the migration.

```sh
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
npm install
npm run build
php artisan matterdesk:user demo@example.test --demo
php artisan serve
```

Open http://localhost:8000 and use the account and password entered at the prompt. The optional `--demo` flag adds a fictional sample matter. To work on the styles with Vite hot reload, run `npm run dev` in a separate terminal. For a hosted environment, point the web root at `public/`, set `APP_ENV=production` and `APP_DEBUG=false`, and serve it over HTTPS.

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

Deleting an account blocks sign-in and removes it from the Users page while retaining its existing case records. Use **Revoked** to suspend an account you may need to reactivate. Cases remain scoped to their owning account. Public self-registration is disabled.

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

The Laravel application starts with its own database. The existing static demonstration uses browser storage. The Laravel port does not import those records. Access is account-scoped, and assigning a task records an assignee name. Shared teams, delegated access, and live client records are outside this prototype's scope.
