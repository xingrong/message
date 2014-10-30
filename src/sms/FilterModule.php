<?php
# Copyright 2012 Jike.com Inc. All Rights Reserved.
# Author: xingrong@jike.com (Xing Rong)

chdir(dirname(__FILE__));

require_once('/var/gs/message/src/base/Feedback.php');
require_once('/var/gs/message/src/base/NotiException.php');
require_once('/var/gs/message/src/base/SMSer.php');
require_once('/var/gs/message/src/base/Logger.php');
require_once('/var/gs/message/src/base/Monitor.php');
require_once('/var/gs/message/src/base/MessageCenterCon.php');
require_once(__DIR__ . '/conf/filterArray.php');
require_once(__DIR__ . "/conf/mysqlInfo.php");
require_once(__DIR__ . "/conf/thirdSMS.php");

//建立对象
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
     * @brief    FilterModule类的本地日志文件名，用于初始化Logger类对象_logger
     * @var string
     */
    private $_log_file;

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
     * @brief    新插入的短信在Mysql数据库中的id
     * @var interger
     */
    private $_retID;

    /**
     * @brief    Feedback类对象，用于发送反馈邮件给管理员和用户
     * @var object
     */
    private $_feedback; //Feedback对象

    /**
     * @brief    SMSer类对象，用于发送短信
     * @var object
     */
    private $_smser;

    /**
     * @brief    Logger类对象，用于记录日志
     * @var object
     */
    private $_logger;

    /**
     * @brief    Monitor类对象，用于监控和报警
     * @var object
     */
    private $_monitor;

    /**
     * @brief    Mysql数据库message_center连接信息
     * @var array
     */
    private $_mysqlInfo;

    /**
     * @brief    第三方短信接口信息
     * @var array
     */
    private $_thirdSMS;

    /**
     * @brief    第三方短信余额监控报警阀值
     * @var interger
     */
    private $_balanceThreshold;

    /**
     * @brief    MessageCenterCon类对象，用于记录日志至Mysql
     * @var object
     */
    private $_con_mc;

    /**
     * @brief    __construct 构造函数
     *
     * @param    $notification array 待过滤的消息
     *
     * @return   void
     */
    function __construct($notification) {
        $this->_debug = false;
        $this->_log_file = 'SMS_FilterModule.log';
        $this->_notification = json_decode($notification,true);
        $this->_feedback = new Feedback($this->_notification);
        $this->_smser = new SMSer();
        $this->_logger = new Logger($this->_log_file);
        $this->_monitor = new Monitor();
        global $mysqlInfo;
        $this->_mysqlInfo = $mysqlInfo;
        global $thirdSMS;
        $this->_thirdSMS = $thirdSMS;
        $this->_ret = $this->_notification;
        $this->_retID = -1;
        $this->_balanceThreshold = 20000;
        $this->_con_mc = new MessageCenterCon();

        $this->doFilter();
        $this->sendSMS();
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
     * @brief    doFilter 消息过滤
     *
     * @return   void
     */
    private function doFilter() {
        global $filterArray;
        if(array_key_exists(strval($this->_notification['filter']),$filterArray)) {
            $filter = $filterArray[strval($this->_notification['filter'])];

            try {
                $filtering = new $filter($this->_notification,$this->_mysqlInfo);
                $this->_ret = $filtering->getRet();

                $this->_retID = $filtering->getRetID();
            }
            catch(Exception $e) {
                $service = 'SMS';
                $level = 'error';
                $param = "function: doFilter";
                $logArray = array(
                    'exception' => $e->getMessage(),
                    'NOW_TIME' => time(),
                    'FEEDBACK_POST' => $this->_ret['FEEDBACK_POST'],
                    'FEEDBACK_TIME' => $this->_ret['FEEDBACK_TIME'],
                );
                $log = json_encode($logArray);
                $this->_logger->writeLog($service,$level,$param,$log);
                $this->_monitor->sendAlarm($log.$param);
            }
        }
        else {
            $this->_feedback->sendMailFeedback('Sorry! There is no filter named ' . strval($this->_notification['filter']) . '.');
            die();
        }
    }

    /**
     * @brief    sendSMS 发送短信
     *
     * @return   boolean true表示短信发送成功
     */
    private function sendSMS() {
        //过滤之后消息为空
        if(empty($this->_ret)) {
            return true;
        }

        $smsRet = $this->_smser->SendSMS(
            $this->_ret['phone'],
            $this->_ret['content']
        );

        //connect to mysql:smsinfo
        $con = mysql_connect($this->_mysqlInfo['host'],$this->_mysqlInfo['username'],$this->_mysqlInfo['password']);
        if(!$con) {
            $service = 'SMS';
            $level = 'error';
            $param = "function: sendSMS";
            $logArray = array(
                'mysql_error' => mysql_error(),
                'NOW_TIME' => time(),
                'FEEDBACK_POST' => $this->_ret['FEEDBACK_POST'],
                'FEEDBACK_TIME' => $this->_ret['FEEDBACK_TIME'],
            );
            $log = json_encode($logArray);
            $this->_logger->writeLog($service,$level,$param,$log);
            $this->_monitor->sendAlarm($log.$param);
            return false;
        }
        mysql_select_db($this->_mysqlInfo['database'],$con);

        //重发机制，重发5次，间隔5s
        $reSMSCount = 5;
        $reSMSSleep = 5;
        while(false === $smsRet && $reSMSCount--) {
            sleep($reSMSSleep);
            $smsRet = $this->_smser->SendSMS(
                $this->_ret['phone'],
                $this->_ret['content']
            );
        }

        //发送失败自动报警
        if($smsRet === false) {
            $service = 'SMS';
            $level = 'error';
            $param = json_encode($this->_ret);
            $logArray = array(
                'smsRet' => $this->_smser->PrintRes(),
                'NOW_TIME' => time(),
                'FEEDBACK_POST' => $this->_ret['FEEDBACK_POST'],
                'FEEDBACK_TIME' => $this->_ret['FEEDBACK_TIME'],
            );
            $log = json_encode($logArray);
            $this->_logger->writeLog($service,$level,$param,$log);
            $this->_monitor->sendAlarm($log.$param);

            $sql_update = "update smsinfo set is_sent=2 where id={$this->_retID}";
            $result = mysql_query($sql_update);
            return false;
        }
        else {
            //时延
            if(!empty($this->_ret['START_TIME'])) {
                $diffTime = time() - $this->_ret['START_TIME'];
                if($diffTime > 600) {
                    $this->_monitor->timeoutAlarm('sms/FilterModule.php/sendSMS timeout!',$diffTime);
                }
            }
            else {
                $service = 'SMS';
                $level = 'error';
                $param = "function: sendSMS";
                $logArray = array(
                    'error_msg' => "timestamp does not exist!",
                    'NOW_TIME' => time(),
                    'FEEDBACK_POST' => $this->_ret['FEEDBACK_POST'],
                    'FEEDBACK_TIME' => $this->_ret['FEEDBACK_TIME'],
                );
                $log = json_encode($logArray);
                $this->_logger->writeLog($service,$level,$param,$log);
                $this->_monitor->sendAlarm($log.$param);
            }
            //修改Mysql标识
            $sql_update = "update smsinfo set is_sent=1 where id={$this->_retID}";
            $result = mysql_query($sql_update);
        }
        mysql_close($con);
        //获取余额
        $this->getBalance();
        return $smsRet;
    }

    /**
     * @brief    getBalance 获取第三方短信余额，当达到阀值时自动报警
     *
     * @return   void
     */
    private function getBalance() {
        $flag = 0;
        //要post的数据 
        $argv = array(
            'sn' => $this->_thirdSMS['thirdSN'], //提供的账号
            'pwd' => $this->_thirdSMS['thirdPWD'], //此处密码需要加密 加密方式为 md5(sn+password) 32位大写
        );
        //构造要post的字符串
        $params = '';
        foreach ($argv as $key=>$value) {
            if ($flag!=0) {
                $params .= "&";
                $flag = 1;
            }
            $params.= $key."="; $params.= urlencode($value);
            $flag = 1;
        }
        $length = strlen($params);
        //创建socket连接
        $fp = fsockopen("sdk2.entinfo.cn",80,$errno,$errstr,10) or exit($errstr."--->".$errno);
        //构造post请求的头
        $header = "POST /webservice.asmx/GetBalance HTTP/1.1\r\n";
        $header .= "Host:sdk2.entinfo.cn\r\n";
        $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $header .= "Content-Length: ".$length."\r\n";
        $header .= "Connection: Close\r\n\r\n";
        //添加post的字符串
        $header .= $params."\r\n";
        //发送post的数据
        fputs($fp,$header);
        $inheader = 1;
        while (!feof($fp)) {
            $line = fgets($fp,1024); //去除请求包的头只显示页面的返回数据
            if ($inheader && ($line == "\n" || $line == "\r\n")) {
                $inheader = 0;
            }
            if ($inheader == 0) {
            }
        }
        fclose($fp);
        file_put_contents(__DIR__ . '/stat/getBalance.xml',$line);
        //余额报警
        $xmlDoc = new DOMDocument();
        $xmlDoc->load(__DIR__ . '/stat/getBalance.xml');
        $balanceArray = $xmlDoc->getElementsByTagName( "string" );
        $balanceStr = $balanceArray->item(0)->nodeValue;
        $balanceInt = intval($balanceStr);
        if($balanceInt > $this->_balanceThreshold || $balanceInt < 1) {
        }
        else {
            $this->_monitor->balanceAlarm($balanceInt);
        }
    }
}
?>
