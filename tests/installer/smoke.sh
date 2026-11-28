#!/usr/bin/env bash
set -Eeuo pipefail
cd "$(dirname "$0")/../.."
for script in install.sh update.sh installer/*.sh; do bash -n "$script"; done
bash install.sh --help | grep -q -- '--check-only'
if bash install.sh --unknown-option; then echo 'Unknown option accepted' >&2; exit 1; fi
if bash install.sh --mode invalid; then echo 'Invalid mode accepted' >&2; exit 1; fi
if bash update.sh; then echo 'Missing updater arguments accepted' >&2; exit 1; fi
if bash update.sh main relative-backup; then echo 'Relative backup accepted' >&2; exit 1; fi
tmp=$(mktemp -d)
trap 'rm -rf -- "$tmp"' EXIT
(
 cd "$tmp"
 printf 'APP_ENV=local\nAPP_DEBUG=true\nDB_PASSWORD=old\n' > .env.example
 export APP_URL=https://example.test INSTALL_MODE=production DB_HOST=127.0.0.1 DB_PORT=3306 DB_DATABASE=test DB_USERNAME=test DB_PASSWORD='safe-test-secret'
 source "$OLDPWD/installer/environment.sh"
 configure_environment
 grep -qx 'APP_ENV="production"' .env
 grep -qx 'APP_DEBUG="false"' .env
 grep -qx 'DB_PASSWORD="safe-test-secret"' .env
 [[ $(stat -c %a .env) == 600 ]]
 cp .env original
 if (configure_environment); then echo 'Existing environment overwritten' >&2; exit 1; fi
 cmp .env original
)
# shellcheck source=installer/database.sh
source installer/database.sh
[[ $(trim_input '  example  ') == example ]]
DB_DATABASE=test DB_USERNAME=test DB_HOST=localhost DB_PORT=3306 DB_PASSWORD=''
if valid_database_inputs; then echo 'Empty password accepted' >&2; exit 1; fi
DB_PASSWORD=test-secret
valid_database_inputs
DB_PORT=65536
if valid_database_inputs; then echo 'Invalid port accepted' >&2; exit 1; fi
DB_PORT=3306 DB_HOST='localhost;dbname=other'
if valid_database_inputs; then echo 'Invalid host accepted' >&2; exit 1; fi
# Simulate conflict checks without a real database or administrator password.
(
 mysql(){
  case "$*" in
   *"SCHEMA_NAME='taken_db'"*"User='taken_user'"*) printf '1\t1\n';;
   *"SCHEMA_NAME='taken_db'"*) printf '1\t0\n';;
   *"User='taken_user'"*) printf '0\t1\n';;
   *) printf '0\t0\n';;
  esac
 }
 output=$(provision_database 2>&1 <<'INPUT' || true
2
taken_db
taken_user
yes
taken_db
free_user
yes
free_db
taken_user
yes
free_db
free_user
no
INPUT
 )
 grep -q 'Database already exists: taken_db' <<< "$output"
 grep -q 'Username already exists: taken_user' <<< "$output"
 [[ $(grep -c 'Database already exists:' <<< "$output") == 2 ]]
 [[ $(grep -c 'Username already exists:' <<< "$output") == 2 ]]
)
echo 'Installer smoke checks passed'
