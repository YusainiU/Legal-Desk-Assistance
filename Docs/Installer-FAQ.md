# Installer FAQ and release validation

## Why does the installer stop on an existing directory or database?

It is for fresh installations only. Use a new empty database and destination. Do not delete an existing installation to satisfy the installer.

## Why is the Apache configuration not activated automatically?

Existing sites, aliases, TLS certificates and PHP-FPM pools may conflict. Review the generated candidate, verify the pool user and socket, run apache2ctl configtest and reload only after approval.

## Why does a password fail environment validation?

The current writer rejects quotes, dollar signs, backslashes and control characters. Configure those values manually using valid Laravel dotenv syntax; never weaken a password to satisfy the installer.

## Why is the application still offline after an upgrade?

A failed update deliberately retains maintenance mode. Consult Installation-Guide.md, validate backups and investigate migrations before restoring service. Never run migrate:fresh on client data.

## Why are my new files not writable after an update?

Review deployment and PHP-FPM ownership and ACLs, including new files and directories. Never use chmod 777. Confirm the service account can read configuration and write only required runtime directories.

## Release checklist (not yet verified)

- [ ] GitHub Actions syntax, ShellCheck and smoke tests pass.
- [ ] Fresh Ubuntu 24.04 and 26.04 installations with supported PHP and Node.
- [ ] WSL local installation and login.
- [ ] Production dedicated hostname and existing HTTPS Alias deployments.
- [ ] Existing directory/database rejection and invalid credentials.
- [ ] SMTP, AI opt-in, ClamAV signatures and private document access.
- [ ] Scheduler and demo expiry with dedicated service identity.
- [ ] Forward upgrade with fictional case records and documents; compare APP_KEY and data.
- [ ] Failed backup, dependency build and migration scenarios; verify maintenance mode.
- [ ] Restore SQL, documents and environment into isolated environment; verify old release.
- [ ] Verify new files' ACLs, web access restrictions and application health.
- [ ] Review release checklist and obtain approval before merging.

Run local non-destructive checks with bash tests/installer/smoke.sh. These do not exercise real MySQL, Apache, privileged changes or Laravel migrations. Use disposable environments and fictional data for integration tests.
