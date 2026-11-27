# Sourced by install.sh
configure_environment(){
 php <<'PHP'
<?php
$values=['APP_URL'=>getenv('APP_URL'),'APP_ENV'=>getenv('INSTALL_MODE')==='production'?'production':'local','APP_DEBUG'=>getenv('INSTALL_MODE')==='production'?'false':'true','DB_HOST'=>getenv('DB_HOST'),'DB_PORT'=>getenv('DB_PORT'),'DB_DATABASE'=>getenv('DB_DATABASE'),'DB_USERNAME'=>getenv('DB_USERNAME'),'DB_PASSWORD'=>getenv('DB_PASSWORD'),'AI_CASE_ASSISTANT_ENABLED'=>'false','MAIL_MAILER'=>'log','SESSION_SECURE_COOKIE'=>getenv('INSTALL_MODE')==='production'?'true':'false'];
$text=file_get_contents('.env.example');
foreach($values as $key=>$value){
 if(preg_match('/[\x00-\x1f"\\\\$]/',$value)) {fwrite(STDERR,"Unsupported characters in $key; configure manually.\n");exit(1);}
 $line=$key.'="'. $value .'"';
 $pattern='/^'.preg_quote($key,'/').'=.*$/m';
 $text=preg_match($pattern,$text)?preg_replace_callback($pattern,fn()=>$line,$text):$text."\n".$line;
}
$f=fopen('.env','x');if(!$f)exit(1);fwrite($f,$text);fclose($f);chmod('.env',0600);
PHP
}
