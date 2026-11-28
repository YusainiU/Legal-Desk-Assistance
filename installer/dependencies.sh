install_dependencies(){
 local mode="${1:?Installation mode required}"
 local args=(--prefer-dist --no-interaction)
 if [[ $mode == production ]]; then args+=(--no-dev --optimize-autoloader); fi
 mkdir -p storage/app/private storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
 composer install "${args[@]}"
 if [[ $mode == production ]]; then composer check-platform-reqs --no-dev; else composer check-platform-reqs; fi
 php artisan key:generate --force
 npm ci
 npm run build
}
