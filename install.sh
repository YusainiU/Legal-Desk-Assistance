#!/usr/bin/env bash
set -Eeuo pipefail
umask 077
trap 'printf "Installation stopped at line %s. Existing files were retained.\n" "$LINENO" >&2' ERR
repo=https://github.com/YusainiU/Legal-Desk-Assistance.git
mode=local
ref=main
check=0
noninteractive=0
die(){ printf '%s\n' "$*" >&2; exit 1; }
while (($#)); do
 case "$1" in
 --help) echo 'Usage: bash install.sh [--check-only] [--non-interactive] [--ref REF] [--mode local|production]'; exit 0;;
 --check-only) check=1;;
 --non-interactive) noninteractive=1;;
 --ref) ref="${2:?Missing ref}"; shift;;
 --mode) mode="${2:?Missing mode}"; shift;;
 *) die "Unknown option: $1";;
 esac
 shift
done
[[ $mode == local || $mode == production ]] || die 'Invalid mode'
[[ $(id -u) != 0 ]] || die 'Run as an unprivileged deployment user.'
[[ $(uname -s) == Linux ]] || die 'Use Linux or WSL Ubuntu.'
missing=0
for cmd in git php composer node npm mysql; do
 if ! command -v "$cmd" >/dev/null; then echo "Missing: $cmd"; missing=1; fi
done
((missing == 0)) || die 'Install missing requirements and rerun.'
php -r 'exit(version_compare(PHP_VERSION,"8.4.1",">=")?0:1);' || die 'PHP 8.4.1+ required'
node -e 'let [a,b]=process.versions.node.split(".").map(Number);process.exit(a>22||a===22&&b>=12?0:1)' || die 'Node 22.12+ required'
for ext in pdo_mysql mbstring dom curl openssl fileinfo tokenizer zip; do
 php -r 'exit(extension_loaded($argv[1])?0:1);' "$ext" || die "Missing PHP extension: $ext"
done
composer --version | grep -q 'Composer version 2\.' || die 'Composer 2 required'
echo 'Host prerequisites passed.'
((check == 0)) || exit 0
if ((noninteractive)); then
 : "${INSTALL_DIR:?Set INSTALL_DIR}" "${APP_URL:?Set APP_URL}" "${DB_DATABASE:?Set DB_DATABASE}" "${DB_USERNAME:?Set DB_USERNAME}" "${DB_PASSWORD:?Set DB_PASSWORD}"
else
 read -rp 'Installation directory [legal-desk-assistance]: ' INSTALL_DIR
 INSTALL_DIR=${INSTALL_DIR:-legal-desk-assistance}
 read -rp 'Application URL [http://localhost:8000]: ' APP_URL
 APP_URL=${APP_URL:-http://localhost:8000}
fi
[[ ! -e $INSTALL_DIR && ! -L $INSTALL_DIR ]] || die 'Destination already exists; refusing overwrite.'
[[ $mode != production || $APP_URL == https://* ]] || die 'Production requires an HTTPS URL.'
[[ $ref != -* ]] || die 'Invalid ref'
# Clone selected release first so standalone downloads use its matching database helper.
git clone -- "$repo" "$INSTALL_DIR"
cd -- "$INSTALL_DIR"
git checkout --detach "$ref" || git checkout --detach "origin/$ref"
git rev-parse HEAD
[[ -f public/.htaccess && -f composer.lock && -f package-lock.json ]] || die 'Required release files missing.'
# shellcheck source=installer/database.sh
source installer/database.sh
APP_URL=$(trim_input "$APP_URL")
[[ $APP_URL =~ ^https?://[^[:space:]]+$ ]] || die 'Invalid application URL'
if ((noninteractive)); then
 DB_HOST=${DB_HOST:-127.0.0.1} DB_PORT=${DB_PORT:-3306}
 verify_database_connection || die 'Use a reachable dedicated empty database.'
else
 while true; do
  printf '%s\n' '1. Use existing empty database' '2. Create new local database' '3. Cancel'
  read -rp 'Database option: ' choice
  case "$choice" in
   1)
    read -rp 'Database name: ' DB_DATABASE
    read -rp 'Database user: ' DB_USERNAME
    while true; do
     read -rsp 'Database password: ' DB_PASSWORD; echo
     [[ -n $DB_PASSWORD ]] && break
     echo 'Password must not be empty.'
    done
    read -rp 'Database host [127.0.0.1]: ' DB_HOST
    read -rp 'Database port [3306]: ' DB_PORT
    DB_DATABASE=$(trim_input "$DB_DATABASE")
    DB_USERNAME=$(trim_input "$DB_USERNAME")
    DB_HOST=$(trim_input "$DB_HOST"); DB_HOST=${DB_HOST:-127.0.0.1}
    DB_PORT=$(trim_input "$DB_PORT"); DB_PORT=${DB_PORT:-3306}
    if verify_database_connection; then break; fi
    read -rp 'Retry database configuration? [yes/no]: ' answer
    [[ $answer == yes ]] || die 'Cancelled; cloned files retained.'
    ;;
   2) provision_database || die 'Provisioning stopped; cloned files retained.'; break;;
   3) die 'Cancelled; cloned files retained.';;
   *) echo 'Choose 1, 2 or 3.';;
  esac
 done
fi
export APP_URL DB_DATABASE DB_USERNAME DB_PASSWORD DB_HOST DB_PORT
export INSTALL_MODE=$mode
# shellcheck source=installer/environment.sh
source installer/environment.sh
# shellcheck source=installer/dependencies.sh
source installer/dependencies.sh
# shellcheck source=installer/verify.sh
source installer/verify.sh
configure_environment
unset DB_PASSWORD
install_dependencies "$mode"
if [[ $mode == production ]]; then
 if ((noninteractive)); then [[ ${CONFIRM_MIGRATE:-} == yes ]] || die 'Set CONFIRM_MIGRATE=yes to authorize migrations.'
 else read -rp 'Run production migrations? [yes/no]: ' answer; [[ $answer == yes ]] || die 'Migration declined.'; fi
 php artisan migrate --force
else
 php artisan migrate
fi
if ((noninteractive)); then echo 'Administrator setup deferred: run php artisan legal-desk:setup-super-admin interactively.'
else php artisan legal-desk:setup-super-admin; fi
verify_installation "$mode"
echo "Application files installed at $PWD"
if [[ $mode == local ]]; then echo 'Run php artisan serve to start the application.'
else
 if ((noninteractive == 0)); then
  read -rp 'Configure SMTP and approved AI services now? [yes/no]: ' answer
  if [[ $answer == yes ]]; then bash installer/services.sh; fi
  read -rp 'Run optional production deployment wizard now? [yes/no]: ' answer
  if [[ $answer == yes ]]; then bash installer/deployment.sh; fi
 fi
 echo 'Complete Apache, permissions, TLS, ClamAV, SMTP and scheduler setup using Docs/Installation-Guide.md before opening access.'
fi
