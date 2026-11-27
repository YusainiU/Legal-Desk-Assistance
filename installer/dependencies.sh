install_dependencies(){
 local args=(--prefer-dist --no-interaction)
 if [[ $mode == production ]]; then args+=(--no-dev --optimize-autoloader); fi
 composer install "${args[@]}"
 if [[ $mode == production ]]; then composer check-platform-reqs --no-dev; else composer check-platform-reqs; fi
 php artisan key:generate --force
 npm ci
 npm run build
}
