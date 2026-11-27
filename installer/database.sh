#!/usr/bin/env bash
set -Eeuo pipefail
[[ $(id -u) != 0 && -t 0 ]] || { echo 'Run interactively as deployment user'; exit 1; }
read -rp 'New database name: ' db
read -rp 'New database username: ' user
[[ $db =~ ^[a-zA-Z0-9_]{1,64}$ && $user =~ ^[a-zA-Z0-9_]{1,32}$ ]] || { echo 'Use letters, numbers and underscores only'; exit 1; }
read -rp "Create NEW local database $db and user $user using sudo mysql? [yes/no]: " answer
[[ $answer == yes ]] || exit 1
password=$(openssl rand -hex 32)
# No IF NOT EXISTS: never silently reuse or modify existing accounts.
existing=$(sudo mysql --batch --skip-column-names -e "SELECT (SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='$db') + (SELECT COUNT(*) FROM mysql.user WHERE User='$user');")
[[ $existing == 0 ]] || { echo 'Database or username already exists'; exit 1; }
printf "CREATE USER '%s'@'localhost' IDENTIFIED BY '%s';\nCREATE DATABASE \`%s\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\nGRANT ALL PRIVILEGES ON \`%s\`.* TO '%s'@'localhost';\n" "$user" "$password" "$db" "$db" "$user" | sudo mysql
printf 'Database: %s\nUser: %s\nHost: localhost\nPassword (store securely): %s\n' "$db" "$user" "$password"
unset password
