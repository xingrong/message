#!/bin/bash
# Author: xingrong0804@163.com (Xing Rong)

receiveMailProcess=0
receiveMailProcess=`ps -ef | grep /var/gs/message/src/mail/ReceiveMail.php | grep -v grep | wc -l`
if [ "$receiveMailProcess" == 0 ];then
    echo "ReceiveMail is down!"
    php /var/gs/message/src/base/monitorReceiveMailProcess.php 'ReceiveMail down'
    /var/gs/message/bin/run_receiveMail.bash start
    exit 2
fi
echo "ReceiveMail is OK!"
exit 0
