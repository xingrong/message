<?php
# Author: xingrong0804@163.com (Xing Rong)

chdir(dirname(__FILE__));

require_once(__DIR__ . '/SendPriority.php');
require_once('/var/gs/message/src/base/Feedback.php');
require_once('/var/gs/message/src/base/NotiException.php');
require_once('/var/gs/message/src/base/Logger.php');

//返回信息对象
$feedback = new Feedback();
$logger = new Logger("sms.log");
//获取POST参数
$paramArray = $_POST;
//访问者IP
if(isset($_SERVER['REMOTE_ADDR'])) {
    $paramArray['VISITOR_IP'] = $_SERVER['REMOTE_ADDR'];
}
else {
    $paramArray['VISITOR_IP'] = 'Unknown';
}
//记录开始时间，timeout监控使用
$paramArray['START_TIME'] = time();
//Feedback
date_default_timezone_set("Asia/Shanghai");
$paramArray['FEEDBACK_TIME'] = date('Y-m-d H:i:s');
$paramArray['FEEDBACK_POST'] = json_encode($_POST);
//发送给优先级队列
try {
    $sendPriority = new SendPriority($paramArray);
    if($sendPriority->isSent()) {
        echo json_encode(array('status'=>'00000','msg'=>'Message Send Success!')) . "\n";
    }
    else {
        $service = "SMS";
        $level = "error";
        $param = json_encode($paramArray);
        $log = "sms.php feedback: ".$feedback->printFeedback(array('status'=>'30201','msg'=>'Message Send Failed!'));
        $logger->writeLog($service,$level,$param,$log);
        die($feedback->printFeedback(array('status'=>'30201','msg'=>'Message Send Failed!')));
    }
}
catch(NotiException $e) {
    $service = "SMS";
    $level = "error";
    $param = json_encode($paramArray);
    $log = "sms.php feedback: ".$feedback->printFeedback($e->getInformation());
    $logger->writeLog($service,$level,$param,$log);
    die($feedback->printFeedback($e->getInformation()));
}
