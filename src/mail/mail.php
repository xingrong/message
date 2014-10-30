<?php
# Author: xingrong0804@163.com (Xing Rong)

chdir(dirname(__FILE__));

require_once(__DIR__ . '/SendPriority.php');
require_once('/var/gs/message/src/base/Feedback.php');
require_once('/var/gs/message/src/base/NotiException.php');
require_once('/var/gs/message/src/base/Logger.php');
require_once('/var/gs/message/src/base/Monitor.php');

//返回信息对象
$feedback = new Feedback();
$logger = new Logger("mail.log");
$monitor = new Monitor();
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
//附件文件和邮件正文文件
$paramArray['attachments'] = array();
$basepath = __DIR__ . '/upload/';
foreach($_FILES AS $k=>$val){
    //处理正文文件
    if('body' == $k) {
        if(postFileExists($basepath . $val['name'])) {
            $monitor->postFileRenameAlarm("body file exists!",$basepath . $val['name']);
            if(move_uploaded_file($val['tmp_name'],$basepath . basename($val['tmp_name']) . "_" . $val['name'])) {
                $path = $basepath  . basename($val['tmp_name']) . "_" . $val['name'];
                $paramArray['bodyFile'] = $path;
            }
            else {
                die($feedback->printFeedback(array('status'=>'30102','msg'=>'Save body file failed!')));
            }
        }
        else {
            if(move_uploaded_file($val['tmp_name'],$basepath . $val['name'])) {
                $path = $basepath . $val['name'];
                $paramArray['bodyFile'] = $path;
            }
            else {
                die($feedback->printFeedback(array('status'=>'30102','msg'=>'Save body file failed!')));
            }
        }
    }
    //处理附件
    else{
        if(postFileExists($basepath . $val['name'])) {
            $monitor->postFileRenameAlarm("attachment file exists!",$basepath . $val['name']);
            if(move_uploaded_file($val['tmp_name'],$basepath . basename($val['tmp_name']) . "_" . $val['name'])) {
                $path = $basepath . basename($val['tmp_name']) . "_" . $val['name'];
                $paramArray['attachments'][strval($k)] = $path;
            }
            else {
                die($feedback->printFeedback(array('status'=>'30103','msg'=>'Save attachment file failed!')));
            }
        }
        else {
            if(move_uploaded_file($val['tmp_name'],$basepath . $val['name'])) {
                $path = $basepath . $val['name'];
                $paramArray['attachments'][strval($k)] = $path;
            }
            else {
                die($feedback->printFeedback(array('status'=>'30103','msg'=>'Save attachment file failed!')));
            }
        }
    }
}
//Feedback
date_default_timezone_set("Asia/Shanghai");
$paramArray['FEEDBACK_TIME'] = date('Y-m-d H:i:s');
$paramPost = $_POST;
if(!empty($_POST['body'])) {
    $paramPost['body'] = substr($_POST['body'],0,100);
}
$paramArray['FEEDBACK_POST'] = json_encode($paramPost);
//发送给优先级队列
try {
    $sendPriority = new SendPriority($paramArray);
    if($sendPriority->isSent()) {
        echo json_encode(array('status'=>'00000','msg'=>'Message Send Success!')) . "\n";
    }
    else {
        $service = "Mail";
        $level = "error";
        $param = json_encode($paramArray);
        $log = "mail.php feedback: ".$feedback->printFeedback(array('status'=>'30201','msg'=>'Message Send Failed!'));
        $logger->writeLog($service,$level,$param,$log);
        die($feedback->printFeedback(array('status'=>'30201','msg'=>'Message Send Failed!')));
    }
}
catch(NotiException $e) {
    $service = "Mail";
    $level = "error";
    $param = json_encode($paramArray);
    $log = "mail.php feedback: ".$feedback->printFeedback($e->getInformation());
    $logger->writeLog($service,$level,$param,$log);
    die($feedback->printFeedback($e->getInformation()));
}

/**
 * @brief    postFileExists 检测post上传文件是否存在
 *
 * @param    $file string 文件完整路径
 *
 * @return   boolean true表示文件存在
 */
function postFileExists($file) {
    $count = 0;
    $timeout = 2;
    $sleeptime = 1;
    while($count < $timeout && file_exists($file)) {
        $count++;
        sleep($sleeptime);
    }
    if($count < $timeout) {
        return false;
    }
    else {
        return true;
    }
}

