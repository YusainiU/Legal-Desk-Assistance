verify_installation(){
 test -s public/build/manifest.json
 test -w storage
 test -w bootstrap/cache
 php artisan migrate:status
 php artisan schedule:list
 if [[ $mode == production ]]; then
  php artisan config:cache
  php artisan view:cache
 fi
}
