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
for cmd in git php composer npm node mysqldump tar flock realpath; do command -v "$cmd" >/dev/null || die "Missing $cmd"; done
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
mkdir -p -- "$backup_root"
backup=$(mktemp -d "$backup_root/legal-desk-$(date -u +%Y%m%dT%H%M%SZ)-XXXXXX")
printf '%s\n' "$old" > "$backup/previous-commit"
printf '%s\n' "$target" > "$backup/target-commit"
# Register cleanup before creating temporary credentials.
trap 'rm -f -- "$backup/mysql.cnf"' EXIT
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
# From this point a failure may leave maintenance mode enabled.
trap 'rm -f -- "$backup/mysql.cnf"; echo "Update interrupted; check maintenance mode. Backup directory: $backup" >&2' EXIT
php artisan down
cp -p .env "$backup/environment"
chmod 600 "$backup/environment"
mysqldump --defaults-extra-file="$backup/mysql.cnf" --single-transaction --quick --no-tablespaces --triggers --databases "$db" > "$backup/database.sql"
rm -f -- "$backup/mysql.cnf"
test -s "$backup/database.sql"
tar -czf "$backup/storage.tar.gz" storage/app
tar -tzf "$backup/storage.tar.gz" >/dev/null
cmp -s .env "$backup/environment"
echo "Backups created: $backup"
git checkout --detach "$target"
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
php artisan migrate --force
php artisan config:cache
php artisan view:cache
php artisan migrate:status
php artisan schedule:list
php artisan queue:restart
cmp -s .env "$backup/environment" || die 'Environment changed unexpectedly'
php artisan up
trap - EXIT
echo "Updated to $target. Resume scheduler/workers and verify HTTPS, authentication, documents and email."
echo "Retain protected backups at $backup according to firm policy."
