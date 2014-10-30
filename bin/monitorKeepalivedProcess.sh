#!/bin/bash
# Author: xingrong0804@163.com (Xing Rong)

keepalivedProcess=0
keepalivedProcess=`ps -ef | grep /usr/sbin/keepalived | grep -v grep | wc -l`
if [ "$keepalivedProcess" == 0 ];then
    echo "keepalived is down!"
    php /var/gs/message/src/base/monitorKeepalivedProcess.php 'keepalived down'
    service keepalived start
    exit 2
fi
if [ "$keepalivedProcess" == 3 ];then
    echo "keepalived is OK!"
    exit 0
fi
echo "the num of keepalived process is wrong!"
php /var/gs/message/src/base/monitorKeepalivedProcess.php "the num of keepalived process is wrong! ==> $keepalivedProcess"
exit 1
