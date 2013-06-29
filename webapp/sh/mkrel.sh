#!/bin/sh

# tar it
tar -cvf rel.tar *.php *.ini .htaccess crossdomain.xml favicon.ico js sh mic upload sql
gzip -S .zip rel.tar

# move it
gzip -S .zip -d rel.tar.zip
tar -xvf rel.tar

mysql -uroot -e 'drop database WCFields; source ../sql/WCFields.sql'

crontab -l
nohup ~/www/html/sh/forfifo.sh &
touch html/upload/pitch/fifo
chmod -R augo+wx html

# update selinux whenever www root is replaced
su chcon -R -t httpd_sys_rw_content_t html

exit
# Do This ONCE
cat php5.ini >> /etc/php.ini
setsebool allow_httpd_anon_write true
setsebool httpd_enable_homedirs true
setsebool httpd_can_sendmail true
setsebool httpd_tmp_exec true

