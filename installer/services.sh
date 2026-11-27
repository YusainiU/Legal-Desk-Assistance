#!/usr/bin/env bash
set -Eeuo pipefail
umask 077
cd -- "$(dirname -- "$0")/.."
[[ $(id -u) != 0 && -t 0 && -f .env ]] || exit 1
unset MAIL_HOST MAIL_PORT MAIL_SCHEME MAIL_USERNAME MAIL_PASSWORD MAIL_FROM_ADDRESS MAIL_MAILER AI_SEARCH_LOCATION OPENAI_API_KEY AI_MANAGEMENT_PROVIDER AI_CASE_ASSISTANT_ENABLED
read -rp 'Configure SMTP delivery? [yes/no]: ' smtp
if [[ $smtp == yes ]]; then
 read -rp 'SMTP host: ' MAIL_HOST
 read -rp 'SMTP port: ' MAIL_PORT
 read -rp 'SMTP scheme [smtp/smtps]: ' MAIL_SCHEME
 [[ $MAIL_SCHEME == smtp || $MAIL_SCHEME == smtps ]] || exit 1
 read -rp 'SMTP username: ' MAIL_USERNAME
 read -rsp 'SMTP password: ' MAIL_PASSWORD; echo
 read -rp 'Verified sender address: ' MAIL_FROM_ADDRESS
 export MAIL_HOST MAIL_PORT MAIL_SCHEME MAIL_USERNAME MAIL_PASSWORD MAIL_FROM_ADDRESS MAIL_MAILER=smtp
fi
read -rp 'Configure OpenAI research? [yes/no]: ' ai
if [[ $ai == yes ]]; then
 echo 'Only enable after firm approval of confidentiality, provider terms and quotas.'
 read -rp 'Confirm approval [yes/no]: ' approved
 [[ $approved == yes ]] || exit 1
 read -rp 'Research jurisdiction: ' AI_SEARCH_LOCATION
 read -rsp 'OpenAI API key: ' OPENAI_API_KEY; echo
 export AI_SEARCH_LOCATION OPENAI_API_KEY AI_MANAGEMENT_PROVIDER=openai AI_CASE_ASSISTANT_ENABLED=true
fi
php <<'PHP'
<?php
$keys=['MAIL_HOST','MAIL_PORT','MAIL_SCHEME','MAIL_USERNAME','MAIL_PASSWORD','MAIL_FROM_ADDRESS','MAIL_MAILER','AI_SEARCH_LOCATION','OPENAI_API_KEY','AI_MANAGEMENT_PROVIDER','AI_CASE_ASSISTANT_ENABLED'];
$text=file_get_contents('.env');
foreach($keys as $key){
 $value=getenv($key);
 if($value===false)continue;
 if(preg_match('/[\x00-\x1f"\\\\$]/',$value)){fwrite(STDERR,"Unsupported characters in $key; edit manually.\n");exit(1);}
 $line=$key.'="'.$value.'"';
 $pattern='/^'.preg_quote($key,'/').'=.*$/m';
 $text=preg_match($pattern,$text)?preg_replace_callback($pattern,fn()=>$line,$text):$text."\n".$line."\n";
}
if(file_put_contents('.env',$text,LOCK_EX)===false)exit(1);
PHP
unset MAIL_PASSWORD OPENAI_API_KEY
php artisan config:clear
php artisan config:cache
echo 'Settings saved. Verify SMTP delivery and AI permissions before use.'
