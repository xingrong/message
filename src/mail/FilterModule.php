<?php
# Copyright 2012 Jike.com Inc. All Rights Reserved.
# Author: xingrong@jike.com (Xing Rong)

chdir(dirname(__FILE__));

require_once('/var/gs/message/src/base/Feedback.php');
require_once('/var/gs/message/src/base/Logger.php');
require_once('/var/gs/message/src/base/Monitor.php');
require_once('/var/gs/message/src/base/NotiException.php');
require_once('/var/gs/message/src/base/Mailer.php');
require_once('/var/gs/message/src/base/MessageCenterCon.php');
require_once(__DIR__ . '/conf/filterArray.php');
require_once(__DIR__ . "/conf/mysqlInfo.php");

//建立对象
if(!isset($argc)) {
    return -1;
}
if(1 == $argc) {
}
else if(2 == $argc) {
    $filterModule = new FilterModule($argv[1]);
}
else {
    $feedback = new Feedback();
    $feedback->sendMailFeedback("\$argc=>" . $argc . " , \$argv=>" . json_encode($argv));
}

//动态加载过滤策略
function __autoload($filter) {
    require_once(__DIR__ . '/filter/' . $filter . '.php');
}

/**
 * @brief    FilterModule类的主要功能是调用相应的过滤策略过滤消息，
 *           并完成最终的发送，异常信息自动反馈给管理员和用户
 */
class FilterModule {

    /**
     * @brief    标记是否启用debug模式，默认为false，即非debug模式
     * @var boolean
     */
    private $_debug;

    /**
     * @brief    待过滤的消息
     * @var array
     */
    private $_notification; 

    /**
     * @brief    过滤之后的消息
     * @var array
     */
    private $_ret;

    /**
     * @brief    Feedback类对象，用于发送反馈邮件给管理员和用户
     * @var object
     */
    private $_feedback; //Feedback对象

    /**
     * @brief    Monitor类对象，用于监控和报警
     * @var object
     */
    private $_monitor;

    /**
     * @brief    Mailer类对象，用于发送邮件
     * @var object
     */
    private $_mailer;

    /**
     * @brief    FilterModule类的本地日志文件名，用于初始化Logger类对象_logger
     * @var string
     */
    private $_log_file;

    /**
     * @brief    Logger类对象，用于记录日志
     * @var object
     */
    private $_logger;

    /**
     * @brief    Mysql数据库message_center连接信息
     * @var array
     */
    private $_mysqlInfo;

    /**
     * @brief    MessageCenterCon类对象，用于记录邮件信息至Mysql
     * @var object
     */
    private $_con_mc;

    /**
     * @brief    标记消息是否发送成功
     * @var boolean
     */
    private $_sendFlag;

    /**
     * @brief    __construct 构造函数
     *
     * @param    $notification array 待过滤的消息
     *
     * @return   void
     */
    function __construct($notification) {
        $this->_debug = false;
        $this->_notification = json_decode($notification,true);
        $this->_feedback = new Feedback($this->_notification);
        $this->_monitor = new Monitor();
        $this->_mailer = new Mailer();
        $this->_log_file = 'Mail_FilterModule.log';
        $this->_logger = new Logger($this->_log_file);
        global $mysqlInfo;
        $this->_mysqlInfo = $mysqlInfo;
        $this->_con_mc = new MessageCenterCon();
        $this->_sendFlag = false;

        $this->doFilter();
        $this->sendService();
    }

    /**
     * @brief    isDebug 是否处于debug模式
     *
     * @return   boolean true表示处于debug模式
     */
    private function isDebug() {
        return $this->_debug;
    }

    /**
     * @brief    isSent 是否发送成功
     *
     * @return   boolean true表示发送成功
     */
    public function isSent() {
        return $this->_sendFlag;
    }

    /**
     * @brief    doFilter 消息过滤
     *
     * @return   void
     */
    private function doFilter() {
        global $filterArray;
        if(array_key_exists(strval($this->_notification['filter']),$filterArray)) {
            $filter = $filterArray[strval($this->_notification['filter'])];
            try {
                $filtering = new $filter($this->_notification);
                $this->_ret = $filtering->getRet();
                $this->_retID = $filtering->getRetID();
            }
            catch(Exception $e) {
                $service = 'Mail';
                $level = 'error';
                $param = "function: doFilter";
                $logArray = array(
                    'exception' => $e->getMessage().$param,
                    'FEEDBACK_POST' => $this->_ret['FEEDBACK_POST'],
                    'FEEDBACK_TIME' => $this->_ret['FEEDBACK_TIME'],
                    'NOW_TIME' => time(),
                );
                $log = json_encode($logArray);
                $this->_logger->writeLog($service,$level,$param,$log);
                $this->_monitor->sendAlarm($log.$param);
            }
        }
        else {
            $errMsg = 'Sorry! There is no filter named ' . strval($this->_notification['filter']) . '.';
            $this->_monitor->sendAlarm($errMsg);
            $this->_feedback->sendMailFeedback($errMsg);
            die();
        }
    }

    /**
     * @brief    sendService 发送邮件
     *
     * @return   boolean true表示邮件发送成功
     */
    private function sendService() {
        //过滤之后消息为空
        if(empty($this->_ret)) {
            $this->_sendFlag = true;
            return true;
        }

        if(!empty($this->_ret['bodyFile'])) {
            $this->_ret['body'] = file_get_contents($this->_ret['bodyFile']);
        }

        $mailRet = $this->_mailer->SendMail(
            $this->_ret['to'],
            $this->_ret['from'],
            $this->_ret['cc'],
            $this->_ret['bcc'],
            $this->_ret['subject'],
            $this->_ret['body'],
            $this->_ret['attachments']
        );

        //删除post上传文件
        if(!empty($this->_ret['bodyFile'])) {
            if(!unlink($this->_ret['bodyFile'])) {
                $service = 'Mail';
                $level = 'error';
                $param = "function: sendService";
                $logArray = array(
                    'mysql_error' => "unlink body file failed! " . $this->_ret['bodyFile'],
                    'FEEDBACK_POST' => $this->_ret['FEEDBACK_POST'],
                    'FEEDBACK_TIME' => $this->_ret['FEEDBACK_TIME'],
                    'NOW_TIME' => time(),
                );
                $log = json_encode($logArray);
                $this->_logger->writeLog($service,$level,$param,$log);
                $this->_monitor->sendAlarm($log.$param);
            }
        }
        if(!empty($this->_ret['attachments'])) {
            foreach($this->_ret['attachments'] as $att_key => $att_val) {
                if(!unlink($att_val)) {
                    $service = 'Mail';
                    $level = 'error';
                    $param = "function: sendService";
                    $logArray = array(
                        'mysql_error' => "unlink attachment file failed! " . $attFile,
                        'FEEDBACK_POST' => $this->_ret['FEEDBACK_POST'],
                        'FEEDBACK_TIME' => $this->_ret['FEEDBACK_TIME'],
                        'NOW_TIME' => time(),
                    );
                    $log = json_encode($logArray);
                    $this->_logger->writeLog($service,$level,$param,$log);
                    $this->_monitor->sendAlarm($log.$param);
                }
            }
        }

        //connect to mysql:mailinfo
        $con = mysql_connect($this->_mysqlInfo['host'],$this->_mysqlInfo['username'],$this->_mysqlInfo['password']);
        if(!$con) {
            $service = 'Mail';
            $level = 'error';
            $param = "function: sendService";
            $logArray = array(
                'mysql_error' => mysql_error().$param,
                'FEEDBACK_POST' => $this->_ret['FEEDBACK_POST'],
                'FEEDBACK_TIME' => $this->_ret['FEEDBACK_TIME'],
                'NOW_TIME' => time(),
            );
            $log = json_encode($logArray);
            $this->_logger->writeLog($service,$level,$param,$log);
            $this->_monitor->sendAlarm($log.$param);
            return false;
        }
        mysql_select_db($this->_mysqlInfo['database'],$con);

        //重发机制，重发5次，间隔5s
        $reMailCount = 5;
        $reMailSleep = 5;
        while(!$mailRet && stripos($this->_mailer->PrintRes(),'30201') && $reMailCount--) {
            sleep($reMailSleep);
            $mailRet = $this->_mailer->SendMail(
                $this->_ret['to'],
                $this->_ret['from'],
                $this->_ret['cc'],
                $this->_ret['bcc'],
                $this->_ret['subject'],
                $this->_ret['body'],
                $this->_ret['attachments']
            );
        }

        //发送失败报警，统一邮件报警，如果是30201异常，则短信报警
        if(!$mailRet) {
            $service = 'Mail';
            $level = 'error';
            $param = $this->_mailer->PrintRes();
            $logArray = array(
                'mailRet' => $param,
                'mailto' => $this->_ret['to'],
                'mailcc' => $this->_ret['cc'],
                'mailbcc' => $this->_ret['bcc'],
                'mailsubject' => $this->_ret['subject'],
            );
            $log = json_encode($logArray);
            $this->_logger->writeLog($service,$level,$param,$log);
            if(stripos($param,'30201') && !stripos($param,'The following From address failed')) {
                $this->_monitor->sendAlarm($log);
            }
            $this->_feedback->sendMailFeedback($log,$this->_ret['body'],$this->_ret['attachments']);

            $sql_update = "update mailinfo set is_sent=2 where id={$this->_retID}";
            $result = mysql_query($sql_update);
            $this->_sendFlag = true;
            return false;
        }
        //发送成功
        //时延
        if(!empty($this->_ret['START_TIME'])) {
            $diffTime = time() - $this->_ret['START_TIME'];
            if($diffTime > 600) {
                $this->_monitor->timeoutAlarm('mail/FilterModule.php/sendService timeout!',$diffTime);
            }
        }
        else {
            $service = 'Mail';
            $level = 'error';
            $param = "function: sendService";
            $logArray = array(
                'error_msg' => "timestamp does not exist! ".$param,
                'FEEDBACK_POST' => $this->_ret['FEEDBACK_POST'],
                'FEEDBACK_TIME' => $this->_ret['FEEDBACK_TIME'],
                'NOW_TIME' => time(),
            );
            $log = json_encode($logArray);
            $this->_logger->writeLog($service,$level,$param,$log);
            $this->_monitor->sendAlarm($log.$param);
        }

        //修改Mysql标识
        $sql_update = "update mailinfo set is_sent=1 where id={$this->_retID}";
        $result = mysql_query($sql_update);

        mysql_close($con);
        $this->_sendFlag = true;
        return true;
    }
}
?>
