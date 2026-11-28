# Guided installation (experimental)

Use Ubuntu or WSL with Bash, PHP 8.4.1+, Composer 2, Node 22.12+, npm, Git, MySQL client and a reachable MySQL server. Install missing requirements manually. Create a dedicated empty database and scoped database user first. Run as an unprivileged deployment user.

During branch testing, download install.sh from feature/guided-installer and run:

```bash
bash install.sh --check-only
bash install.sh --ref feature/guided-installer
```

After merge, main is the default ref. Prefer a reviewed release tag or commit. The script clones the selected revision and loads its installer modules. Do not execute unreviewed revisions.

For production use --mode production and an HTTPS URL. The installer prepares application files but does not change system services or Apache. Follow the production deployment section of README.md for restricted ownership, PHP-FPM, public-only document root or Alias, HTTPS, ClamAV, SMTP, cron, backups and go-live checks. Do not expose the application before these steps. AI is disabled by default. Log mail is for testing only and can retain sensitive content.

Non-interactive mode accepts INSTALL_DIR, APP_URL, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME and DB_PASSWORD through the environment. Supply secrets securely, not in shell history. Production additionally requires CONFIRM_MIGRATE=yes. Administrator initialization is deferred to the interactive Artisan setup command. Passwords containing quotes, dollar signs, backslashes or control characters are currently rejected by the environment writer; use a strong compatible secret or manual installation.

The installer refuses existing directories and nonempty databases. On failure it retains files for diagnosis; do not rerun against a partially migrated database. Review errors and use a fresh empty database or complete setup manually. Never delete existing client data. This is not an upgrade tool. Follow the README upgrade and backup procedure for existing installations.

Run php artisan serve for local testing. Verify authentication, assets, private document access and database operations before use.

## Phase 2: optional production helpers

Before installation, download and review installer/database.sh from the selected branch and run it with Bash if you need a new local MySQL database. It requires sudo mysql socket authentication and OpenSSL. It prints a generated password to your terminal; store securely and avoid recording the session. Existing database names and usernames are rejected. If provisioning fails midway, inspect partial objects manually; no automatic deletion is attempted.

After installation, from the application directory:

```bash
bash installer/services.sh
bash installer/deployment.sh
```

The services helper configures SMTP and optional approved OpenAI access. Special characters unsupported by the environment writer must be configured manually. The deployment helper requires an existing PHP-FPM installation, dedicated service account/pool, Apache, TLS certificates, ACL utilities and a path under /var/www. Verify that the selected PHP-FPM socket actually belongs to the intended pool and that Apache can traverse the public path. It offers ACL changes, ClamAV installation and a cron entry separately. Review existing scheduler entries before accepting. It generates an Apache candidate for a dedicated host or an Alias inside an existing HTTPS virtual host; it does not install or enable the candidate or obtain certificates. Review and apply manually, enable required Apache modules, run configtest and reload. Verify scanner signatures and enable document scanning manually. Complete the README go-live checklist. All helpers are experimental and require staging tests.

## Phase 3: upgrading an existing installation (experimental)

The updater is intended for a clean Git checkout of a production installation with MySQL, local storage/app documents, installed Composer dependencies and an unprivileged deployment user. It requires Bash, Git, PHP, Composer, npm, Node, mysqldump, tar, flock and realpath. It supports a simple MySQL connection with host/port, not DB_URL, socket or read/write splitting. Confirm that mysqldump is compatible with the server and that the account has backup privileges. Use transactional tables. External storage, external databases and custom deployment layouts require separate backup arrangements.

Review the target commit or release in staging. Stop scheduled tasks and supervised queue workers, drain active requests and prevent other processes or hosts from writing to the database and storage. Maintenance mode alone does not guarantee consistency. Ensure enough disk space and a protected backup directory outside the application and all public web roots. Do not run as root. If update.sh is not present in your current release, download the reviewed script into the application root and keep the Git working tree clean (for example, place it there as a locally excluded file after reviewing it).

```bash
bash update.sh REVIEWED_REF /absolute/private/backup-directory
```

The script fetches origin, requires a forward-only target, locks against another updater, prompts for confirmation, enables maintenance mode, copies .env, dumps MySQL, archives storage/app and records the old and target commit. It checks backup files before changing code. It checks out the target detached, installs locked production dependencies, builds assets, checks that .env has not changed, migrates, rebuilds caches, requests queue restart and exits maintenance mode. It never regenerates APP_KEY or seeds data. Resume scheduler and supervised workers only after verifying the application. A queue restart signal is not a substitute for restarting stopped workers.

Backups contain confidential client information and secrets. Local permissions are restrictive but backups are not encrypted by this script. Use encrypted storage, restricted access, off-host copies, retention policies and tested restores. The SQL dump does not include MySQL users, server configuration, routines or events. Back up any required external data separately. Backup existence and archive readability are not proof of restorability.

### Failure and recovery

If a failure occurs after maintenance mode starts, leave the application offline. Record the error, inspect the backup directory and check whether migrations ran. A failed backup may be incomplete; never restore from it without validation. Do not rerun blindly, reset the Git tree or run migrate:fresh. If the database has changed, rolling back code alone may be incompatible.

For a full rollback, keep all writers stopped, verify the backup and restore it into an isolated test environment first. Have the database administrator restore the verified SQL dump to the intended database (including removing incompatible post-upgrade objects as appropriate), restore storage/app and the original .env with restricted permissions, and check out the recorded previous commit. Reinstall locked dependencies and rebuild assets for that revision. Rebuild Laravel caches, verify migrations and functionality, then run php artisan up and resume services. Never overwrite newer client data without explicit approval and a recovery plan. If the failure occurred before any database or storage changes, a reviewed code-only recovery may suffice.

No automatic destructive rollback is performed. Runtime and recovery tests on disposable Ubuntu/MySQL installations remain required before production use.

## Guided installer and updater (experimental)

See [installation guide](Installation-Guide.md) and [installer FAQ and release checklist](Installer-FAQ.md). Review scripts before execution. During branch testing use `bash install.sh --ref feature/guided-installer`; run `bash install.sh --check-only` first. Interactive production installation offers optional services and deployment helpers after application setup. Database provisioning is available interactively or as a separate pre-install step. Apache activation, certificates and PHP-FPM pool setup remain manual. For existing installations use `bash update.sh REVIEWED_REF /absolute/private/backup-directory` only after staging and restore tests. Run `bash tests/installer/smoke.sh` for non-destructive validation. CI and real Ubuntu/MySQL deployment and recovery results must be verified before release.

### Phase 4.1: database setup

Interactive install.sh now clones the selected release before presenting the database menu. Choose an existing empty database or provision a new local database. For provisioning choose sudo socket authentication, root password, or a custom local administrator with access to mysql.user and schema creation/grant privileges. The MySQL client prompts for the administrator password twice (validation and creation); it is not stored by the installer. The application password is generated automatically and stored in the restricted .env on successful configuration. Standalone bash installer/database.sh remains available and displays the generated application password. Existing database credentials can be retried; empty passwords are rejected. Non-interactive mode requires an existing database. Provisioning failures may leave partial objects and failed installations retain the cloned directory; inspect both before retrying, never delete existing data blindly. Automatic provisioning is local only. Test all authentication paths on disposable MySQL before release.
