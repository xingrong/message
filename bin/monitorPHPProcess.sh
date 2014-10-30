#!/bin/bash

phpProcess=0
phpProcess=`ps -ef | grep /var/gs/local/php/etc/php-fpm | grep -v grep | wc -l`
if [ "$phpProcess" == 0 ];then
    echo "PHP is down!"
    php /var/gs/message/src/base/monitorPHPProcess.php 'php down'
    /var/gs/local/php/sbin/php-fpm
    exit 2
fi
if [ "$phpProcess" == 1 ];then
    echo "php is OK!"
    exit 0
fi
echo "php more than 1!"
php /var/gs/message/src/base/monitorPHPProcess.php 'php more than 1'
exit 1
