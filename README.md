###################
PHP版本要求:>=7.1
###################

###################
本地开发前须知:
###################

1.在本地开发时 define('APP_DEBUG', true);

2..htaccess的目录记得修改:    RewriteRule ^(.*)$ /本地目录/index.php/$1 [QSA,PT,L]

###################
提交上线前须知:
###################
1.在提交上线时 define('APP_DEBUG', false);

2..htaccess的目录记得修改: RewriteRule ^(.*)$ /index.php/$1 [QSA,PT,L]
