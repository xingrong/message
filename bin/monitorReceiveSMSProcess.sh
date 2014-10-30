#!/bin/bash
# Author: xingrong0804@163.com (Xing Rong)

receiveSMSProcess=0
receiveSMSProcess=`ps -ef | grep /var/gs/message/src/sms/ReceiveSMS.php | grep -v grep | wc -l`
if [ "$receiveSMSProcess" == 0 ];then
    echo "ReceiveSMS is down!"
    php /var/gs/message/src/base/monitorReceiveSMSProcess.php 'ReceiveSMS down'
    /var/gs/message/bin/run_receiveSMS.bash start
    exit 2
fi
echo "ReceiveSMS is OK!"
exit 0
