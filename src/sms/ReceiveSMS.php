<?php
# Copyright 2012 Jike.com Inc. All Rights Reserved.
# Author: xingrong@jike.com (Xing Rong)

chdir(dirname(__FILE__));

require_once(__DIR__ . '/conf/priorityMQ.php');
require_once(__DIR__ . '/FilterModule.php');
require_once('/var/gs/message/src/base/lib/RabbitMQClient.inc');
require_once('/var/gs/message/src/base/Monitor.php');

$monitor = new Monitor();
//每个队列建立一个客户端，一对一订阅
$topicArray = $priorityMQ['TOPIC'];
foreach($topicArray as $priority => $queueArray) {
    try {
        $$priority = new RabbitMQClient($priorityMQ);
    }
    catch(Exception $e) {
        $monitor->sendAlarm($e->getMessage());
    }
}
//根据优先级分时控制
while(1) {
    //遍历每个优先级，确定分配时间
    foreach($topicArray as $priority => $queueArray) {
        if(array_key_exists($priority,$priorityMQ['TIMER'])) {
            $timer = $priorityMQ['TIMER'][strval($priority)];
        }
        else {
            $timer = $priorityMQ['TIMER']['0']; //默认timer
        }
        $start = time();
        $end = intval($start) + intval($timer);
        //在分配时间内对该优先级处理
        while(intval(time()) < intval($end)) {
            $isGet = false;
            //处理同一优先级的多个队列
            foreach($queueArray as $queue) {
                try {
                    $notification = $$priority->get($queue);
                }
                catch(Exception $e) {
                    $monitor->sendAlarm($e->getMessage());
                }
                if(!empty($notification)) {
                    $isGet = true;
                    $countStr = json_encode(array('queue'=>$queue,'message_count'=>$notification->delivery_info['message_count']));
                    file_put_contents(__DIR__ . "/queueCount/{$queue}",$countStr);
                    if(($notification->delivery_info['message_count']) > 1000) {
                        $monitor->MQCountAlarm($queue,$notification->delivery_info['message_count']);
                    }
                    usleep(500000);
                    system("php ".__DIR__."/FilterModule.php '" . $notification->body . "'" . " >> /var/gs/message/logs/SMS_FilterModule.log 2>&1 &");
                }
            }
            //如果该优先级没有消息，轮询一遍所有优先级，从高到低
            if(!$isGet) {
                $topicArrayTmp = $topicArray;
                foreach($topicArrayTmp as $priorityTmp => $queueArrayTmp) {
                    foreach($queueArrayTmp as $queueTmp) {
                        try {
                            $notification = $$priority->get($queueTmp);
                        }
                        catch(Exception $e) {
                            $monitor->sendAlarm($e->getMessage());
                        }
                        if(!empty($notification)) {
                            $isGet = true;
                            $countStr = json_encode(array('queue'=>$queue,'message_count'=>$notification->delivery_info['message_count']));
                            file_put_contents(__DIR__ . "/queueCount/{$queue}",$countStr);
                            if(($notification->delivery_info['message_count']) > 1000) {
                                $monitor->MQCountAlarm($queue,$notification->delivery_info['message_count']);
                            }
                            usleep(500000);
                            system("php ".__DIR__."/FilterModule.php '" . $notification->body . "'" . " >> /var/gs/message/logs/SMS_FilterModule.log 2>&1 &");
                            break;
                        }
                    }
                }
            }
            //如果所有优先级都没有消息，sleep1秒
            if(!$isGet) {
                sleep(1);
            }
        }
    }
}
?>
