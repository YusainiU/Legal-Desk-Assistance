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
