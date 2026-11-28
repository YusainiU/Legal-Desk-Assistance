#!/usr/bin/env bash
set -Eeuo pipefail
umask 077
cd -- "$(dirname -- "$0")"
die(){ printf '%s\n' "$*" >&2; exit 1; }
[[ $(id -u) != 0 ]] || die 'Run as deployment user, not root.'
[[ $# == 2 ]] || die 'Usage: bash update.sh REVIEWED_REF ABSOLUTE_BACKUP_DIRECTORY'
ref=$1
backup_root=$2
[[ $ref != -* && $backup_root == /* ]] || die 'Invalid arguments'
[[ -f artisan && -f .env && -f vendor/autoload.php && -d .git ]] || die 'Existing Git installation required'
for cmd in git php composer npm node mysqldump tar flock realpath sha256sum; do command -v "$cmd" >/dev/null || die "Missing $cmd"; done
exec 9>.git/legal-desk-update.lock
flock -n 9 || die 'Another updater is running'
[[ -z $(git status --porcelain) ]] || die 'Working tree is not clean; review changes first'
root=$(pwd -P)
backup_root=$(realpath -m "$backup_root")
[[ $backup_root != "$root" && $backup_root != "$root/"* ]] || die 'Backups must be outside application tree'
[[ ! -f storage/framework/down ]] || die 'Application already in maintenance mode; investigate first'
git fetch --tags origin
target=$(git rev-parse --verify "refs/remotes/origin/$ref^{commit}" 2>/dev/null || git rev-parse --verify "$ref^{commit}")
old=$(git rev-parse HEAD)
git merge-base --is-ancestor "$old" "$target" || die 'Target is not a forward upgrade'
git cat-file -e "$target:composer.lock"
git cat-file -e "$target:package-lock.json"
printf 'Current: %s\nTarget: %s\nBackup directory: %s\n' "$old" "$target" "$backup_root"
read -rp 'Confirm reviewed target, tested restore plan, and paused scheduler/queue workers on all hosts [yes/no]: ' answer
[[ $answer == yes ]] || die 'Cancelled'
# Missing storage is ambiguous: never silently assume that documents are absent.
if [[ ! -d storage/app ]]; then
 echo 'storage/app is missing. Verify configured document storage and confirm no files are missing.'
 read -rp 'Confirmed empty local storage; create required directories? [yes/no]: ' answer
 [[ $answer == yes ]] || die 'Storage validation cancelled'
 mkdir -p storage/app/private storage/app/public
fi
[[ -r storage/app && -x storage/app && -r .env ]] || die 'Backup source not readable'
mkdir -p -- "$backup_root"
[[ -w $backup_root ]] || die 'Backup directory not writable'
backup=$(mktemp -d "$backup_root/legal-desk-$(date -u +%Y%m%dT%H%M%SZ)-XXXXXX")
printf '%s\n' "$old" > "$backup/previous-commit"
printf '%s\n' "$target" > "$backup/target-commit"
phase=preflight
maintenance=0
printf '%s\n' INCOMPLETE > "$backup/STATUS"
cleanup(){
 local rc=$?
 trap - EXIT
 rm -f -- "$backup/mysql.cnf"
 if ((rc != 0)); then
  printf 'Update failed during %s. Backup directory: %s\n' "$phase" "$backup" >&2
  if [[ $phase == preflight || $phase == backup ]]; then
   echo 'No application upgrade started. Backup may be incomplete.' >&2
   if ((maintenance)); then
    if php artisan up; then echo 'Application access restored; inspect backup and retry.' >&2
    else echo 'Could not restore access; investigate and run php artisan up when safe.' >&2; fi
   fi
  else
   echo 'Keep application offline; inspect failure and verified backup before recovery. Do not blindly rerun or migrate.' >&2
  fi
 fi
 exit "$rc"
}
trap cleanup EXIT
trap 'exit 130' INT
trap 'exit 143' TERM
# Use effective Laravel configuration, including cached configuration.
php <<'PHP' > "$backup/mysql.cnf"
<?php
require 'vendor/autoload.php';
$app=require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$c=config('database.connections.'.config('database.default'));
if(($c['driver']??null)!=='mysql'||!empty($c['url'])||!empty($c['unix_socket'])||!empty($c['read'])||!empty($c['write']))exit(1);
foreach(['host','port','database','username','password'] as $k){
 if(!isset($c[$k])||!is_scalar($c[$k])||preg_match('/[\x00-\x1f]/',(string)$c[$k]))exit(1);
}
if(!preg_match('/^[A-Za-z0-9_]+$/',$c['database']))exit(1);
echo "[client]\n";
foreach(['host'=>'host','port'=>'port','username'=>'user','password'=>'password'] as $k=>$opt){
 echo $opt.'="'.addcslashes((string)$c[$k],"\\\"")."\"\n";
}
PHP
db=$(php -r 'require "vendor/autoload.php";$a=require "bootstrap/app.php";$a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();echo config("database.connections.".config("database.default").".database");')
[[ $db =~ ^[A-Za-z0-9_]+$ ]] || die 'Unsupported database name'
phase=backup
maintenance=1
php artisan down
echo 'Backing up environment...'
cp -p .env "$backup/environment"
chmod 600 "$backup/environment"
echo 'Backing up database...'
mysqldump --defaults-extra-file="$backup/mysql.cnf" --single-transaction --quick --no-tablespaces --triggers --databases "$db" > "$backup/database.sql"
rm -f -- "$backup/mysql.cnf"
test -s "$backup/database.sql"
echo 'Backing up application storage...'
tar -czf "$backup/storage.tar.gz" storage/app
tar -tzf "$backup/storage.tar.gz" >/dev/null
cmp -s .env "$backup/environment"
echo 'Verifying backup checksums...'
(cd "$backup" && sha256sum database.sql environment storage.tar.gz previous-commit target-commit > SHA256SUMS && sha256sum -c SHA256SUMS)
printf '%s\n' COMPLETE > "$backup/STATUS"
echo "Backups created: $backup"
phase=checkout
git checkout --detach "$target"
phase=dependencies
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
composer check-platform-reqs --no-dev
npm ci
npm run build
test -s public/build/manifest.json
cmp -s .env "$backup/environment" || die 'Environment changed unexpectedly'
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear
phase=migration
php artisan migrate --force
phase=verification
php artisan config:cache
php artisan view:cache
php artisan migrate:status
php artisan schedule:list
php artisan queue:restart
cmp -s .env "$backup/environment" || die 'Environment changed unexpectedly'
php artisan up
maintenance=0
trap - EXIT INT TERM
echo "Updated to $target. Resume scheduler/workers and verify HTTPS, authentication, documents and email."
echo "Retain protected backups at $backup according to firm policy."
