#!/bin/bash

nginxProcess=0
nginxProcess=`ps -ef | grep /var/gs/local/nginx/sbin/nginx | grep -v grep | wc -l`
if [ "$nginxProcess" == 0 ];then
    echo "nginx is down!"
    php /var/gs/message/src/base/monitorNginxProcess.php 'nginx down'
    /var/gs/local/nginx/sbin/nginx
    exit 2
fi
if [ "$nginxProcess" == 1 ];then
    echo "nginx is OK!"
    exit 0
fi
echo "nginx more than 1!"
php /var/gs/message/src/base/monitorNginxProcess.php 'nginx more than 1'
exit 1
