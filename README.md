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

## User administration

After migrating, grant administrator access to an existing account:

```sh
php artisan legal-desk:grant-admin demo@example.test
```

Use your actual account email if you did not create the demo account. Sign in and select **Users** in the sidebar. Administrators can create and edit accounts, grant or revoke workspace access, and select the **Staff** or **Administrator** role. Newly created accounts need **Granted** workspace access before they can sign in. The application does not email credentials. Users can use **Forgot password?** when mail is configured.

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
