#!/bin/bash
#
# Copyright 2012 Jike Inc. All Rights Reserved.
# Author: xingrong@jike.com  (Xing Rong)

usage="usage: start_receiveSMS.bash (start|stop|restart)"

if [ $# -lt 1 ]; then
  echo $usage
  exit 2
fi

operation=$1
BIN_DIR="/var/gs/message/src/sms"
BIN_NAME="ReceiveSMS.php"
LOG_DIR="/var/gs/message/logs"
LOG_NAME="receiveSMS_`date +%F_%H-%M-%S`.log"

cmd="nohup php $BIN_DIR/$BIN_NAME > $LOG_DIR/$LOG_NAME 2>&1 &"

stop() {
  echo "pid=$(ps aux | grep $BIN_NAME | grep -v grep | awk '{print $2}')"
  pid=$(ps aux | grep $BIN_NAME | grep -v grep | awk '{print $2}')
  echo pid:$pid
  if [ "$pid" == "" ]; then
    return
  fi
  kill -USR1 $pid
  while [ 1 ]; do
    if [ ! -e "/proc/$pid" ]; then
      break
    fi
  done
}

start() {
  echo $cmd
  eval $cmd
  sleep 1
  while [ 1 ]; do
    cnt=$(ps aux | grep $BIN_NAME | grep -v grep | wc -l)
    if [ $cnt != 0 ]; then
      break
    fi
  done
}

restart() {
  stop
  sleep 1
  start
}

if [ $operation == "start" ]; then
  start
elif [ $operation == "stop" ]; then
  stop
elif [ $operation == "restart" ]; then
  restart
else
  echo $usage
fi

succ=$?
if [ $succ -eq 0 ]; then
echo receiveSMS $operation accomplished
else
echo receiveSMS $operation failed
fi
