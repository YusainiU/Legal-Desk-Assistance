#!/usr/bin/env bash
# Shared functions; no side effects when sourced.
trim_input(){ local v=$1; v="${v#"${v%%[![:space:]]*}"}"; v="${v%"${v##*[![:space:]]}"}"; printf '%s' "$v"; }
valid_database_inputs(){
 [[ $DB_DATABASE =~ ^[a-zA-Z0-9_-]{1,64}$ && $DB_USERNAME =~ ^[a-zA-Z0-9_.-]{1,32}$ && $DB_HOST =~ ^[a-zA-Z0-9_.:-]+$ && $DB_PORT =~ ^[0-9]{1,5}$ && -n $DB_PASSWORD ]] || { echo 'Invalid database settings or empty password.' >&2; return 1; }
 ((10#$DB_PORT >= 1 && 10#$DB_PORT <= 65535)) || return 1
}
verify_database_connection(){
 valid_database_inputs || return 1
 DB_DATABASE="$DB_DATABASE" DB_USERNAME="$DB_USERNAME" DB_PASSWORD="$DB_PASSWORD" DB_HOST="$DB_HOST" DB_PORT="$DB_PORT" php <<'PHP'
<?php
try {
 $p=new PDO('mysql:host='.getenv('DB_HOST').';port='.getenv('DB_PORT').';dbname='.getenv('DB_DATABASE'),getenv('DB_USERNAME'),getenv('DB_PASSWORD'),[PDO::ATTR_TIMEOUT=>5]);
 if($p->query('SHOW TABLES')->fetch()){fwrite(STDERR,"Database is not empty.\n");exit(1);}
} catch(PDOException $e){
 $code=$e->errorInfo[1]??0;
 $message=match($code){1045=>'Database authentication failed.',1049=>'Database does not exist.',1044=>'Database access denied.',2002,2003=>'Cannot reach MySQL server.',default=>'Database connection failed.'};
 fwrite(STDERR,$message."\n");exit(1);
}
PHP
}
provision_database(){
 local db user answer method admin existing password
 local -a mysql_cmd
 command -v openssl >/dev/null || { echo 'Install openssl first.' >&2; return 1; }
 read -rp 'New database name [legal_desk_assistance]: ' db || return 1
 db=$(trim_input "$db"); db=${db:-legal_desk_assistance}
 read -rp 'New database username [legal_desk_user]: ' user || return 1
 user=$(trim_input "$user"); user=${user:-legal_desk_user}
 [[ $db =~ ^[a-zA-Z0-9_]{1,64}$ && $user =~ ^[a-zA-Z0-9_]{1,32}$ ]] || { echo 'Use letters, numbers and underscores only.' >&2; return 1; }
 while true; do
  printf '%s\n' '1. Linux socket (sudo mysql)' '2. MySQL root password' '3. Custom MySQL administrator' '4. Cancel'
  read -rp 'Authentication method: ' method || return 1
  case "$method" in
   1) mysql_cmd=(sudo mysql --batch --skip-column-names);;
   2) mysql_cmd=(mysql --user=root --password --batch --skip-column-names);;
   3) read -rp 'Administrator username: ' admin || return 1
      admin=$(trim_input "$admin")
      [[ $admin =~ ^[a-zA-Z0-9_.-]+$ ]] || continue
      mysql_cmd=(mysql "--user=$admin" --password --batch --skip-column-names);;
   4) return 1;;
   *) continue;;
  esac
  # mysql itself prompts silently on the terminal; no administrator secret is stored.
  if existing=$("${mysql_cmd[@]}" -e "SELECT (SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='$db') + (SELECT COUNT(*) FROM mysql.user WHERE User='$user');"); then break; fi
  read -rp 'Administrator authentication or permission check failed. Retry? [yes/no]: ' answer || return 1
  [[ $answer == yes ]] || return 1
 done
 [[ $existing == 0 ]] || { echo 'Database or username already exists; no changes made.' >&2; return 1; }
 read -rp "Create NEW local database $db and user $user? [yes/no]: " answer || return 1
 [[ $answer == yes ]] || return 1
 password=$(openssl rand -hex 32) || return 1
 # A second native password prompt is intentional. Never pass administrator secrets as arguments.
 if ! printf "CREATE USER '%s'@'localhost' IDENTIFIED BY '%s';\nCREATE DATABASE \`%s\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\nGRANT ALL PRIVILEGES ON \`%s\`.* TO '%s'@'localhost';\n" "$user" "$password" "$db" "$db" "$user" | "${mysql_cmd[@]}"; then
  echo "Provisioning failed; partial objects may exist. Ask administrator to inspect $db and $user before retrying. No automatic deletion." >&2
  return 1
 fi
 DB_DATABASE=$db DB_USERNAME=$user DB_PASSWORD=$password DB_HOST=localhost DB_PORT=3306
 verify_database_connection || { echo 'Objects created but application login failed; inspect them before retrying.' >&2; return 1; }
 echo 'Database created and connection verified.'
}
if [[ ${BASH_SOURCE[0]} == "$0" ]]; then
 set -Eeuo pipefail
 [[ $(id -u) != 0 && -t 0 ]] || { echo 'Run interactively as deployment user'; exit 1; }
 provision_database
 printf 'Database: %s\nUser: %s\nHost: %s\nPassword (store securely): %s\n' "$DB_DATABASE" "$DB_USERNAME" "$DB_HOST" "$DB_PASSWORD"
 unset DB_PASSWORD
fi
