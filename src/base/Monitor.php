<?php
# Author: xingrong0804@163.com (Xing Rong)

chdir(dirname(__FILE__));

require_once(__DIR__ . "/Mailer.php");
require_once(__DIR__ . "/SMSer.php");
require_once(__DIR__ . "/MessageCenterCon.php");
require_once(__DIR__ . "/Logger.php");

/**
    * @brief    Monitor类的主要功能是监控通知中心的服务状态，异常报警并记录日志
 */
class Monitor {

    /**
     * @brief    标记是否处于debug模式
     * @var boolean
     */
    private $_debug;

    /**
     * @brief    Monitor类的本地日志文件名
     * @var string
     */
    private $_log_file;

    /**
     * @brief    管理员联系方式
     * @var array
     */
    private $_admin;

    /**
     * @brief    Mailer类对象，用于发送邮件报警
     * @var object
     */
    private $_mailer;

    /**
     * @brief    SMSer类对象，用于发送短信报警
     * @var object
     */
    private $_smser;

    /**
     * @brief    MessageCenterCon类对象，用于记录日志至Mysql
     * @var object
     */
    private $_con_mc;

    /**
     * @brief    Logger类对象，用于记录日志至本地文件
     * @var object
     */
    private $_logger;

    /**
        * @brief    __construct 构造函数
        *
        * @return   void
     */
    function __construct() {
        $this->_debug = false;
        $this->_log_file = "monitor.log";
        $this->_admin = array(
            'mail' => 'xingrong0804@163.com',
            'phone' => '13512341234',
        );
        $this->_mailer = new Mailer();
        $this->_smser = new SMSer();
        $this->_con_mc = new MessageCenterCon();
        $this->_logger = new Logger($this->_log_file);
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
        * @brief    sendMailAlarm 发送邮件报警
        *
        * @param    $alarm_msg string 报警信息
        * @param    $cc 抄送报警人
        *
        * @return   boolean true表示邮件报警发送成功
     */
    public function sendMailAlarm($alarm_msg,$cc='') {
        $mailRet = $this->_mailer->SendMail(
            $this->_admin['mail'],
            'message@message.com',
            $cc,
            '',
            'Alarm from messageCenter',
            htmlspecialchars($alarm_msg),
            ''
        );
        if($mailRet === false) {
            $service = 'sendMailAlarm';
            $level = 'fatal';
            $param = $this->_mailer->PrintRes();
            $log = "alarm_msg: {$alarm_msg}";
            $this->_logger->writeLog($service,$level,$param,$log);
            return false;
        }
        return true;
    }

    /**
        * @brief    sendSMSAlarm 发送短信报警
        *
        * @param    $alarm_msg string 报警信息
        * @param    $phone string 报警人手机号码
        *
        * @return   boolean true表示短信报警发送成功
     */
    public function sendSMSAlarm($alarm_msg,$phone) {
        $smsRet = $this->_smser->SendSMS(
            $phone,
            $alarm_msg
        );
        if($smsRet === false) {
            $service = 'sendSMSAlarm';
            $level = 'fatal';
            $param = $this->_smser->PrintRes();
            $log = "alarm_msg: {$alarm_msg}";
            $this->_logger->writeLog($service,$level,$param,$log);
            $this->sendMailAlarm($log.$param);
            return false;
        }
        return true;
    }

    /**
        * @brief    sendAlarm 发送报警，包括邮件报警和短信报警
        *
        * @param    $alarm_msg string 报警信息
        * @param    $cc array 抄送报警人联系方式
        *
        * @return   void
     */
    public function sendAlarm($alarm_msg,$cc=array()) {
        if(isset($cc['phone'])) {
            $this->sendSMSAlarm($alarm_msg,$this->_admin['phone']);
            $this->sendSMSAlarm($alarm_msg,$cc['phone']);
        }
        else {
            $this->sendSMSAlarm($alarm_msg,$this->_admin['phone']);
        }

        if(isset($cc['mail'])) {
            $this->sendMailAlarm($alarm_msg,$cc['mail']);
        }
        else {
            $this->sendMailAlarm($alarm_msg);
        }
    }

    /**
        * @brief    MQCountAlarm 消息队列的消息数目阀值报警
        *
        * @param    $mq string 消息队列名
        * @param    $count interger 消息数目
        *
        * @return   void
     */
    public function MQCountAlarm($mq,$count) {
        $service = 'MQCountAlarm';
        $level = 'alarm';
        $param = $mq;
        $log = "The count of message in {$mq} is {$count}";
        if($this->_con_mc->isAlarmValid($service,$param) === true) { //每30分钟报警一次
            $this->_logger->writeLog($service,$level,$param,$log);
            $this->sendAlarm($log);
        }
    }

    /**
        * @brief    smsSimulationAlarm 通知中心短信服务模拟监控报警
        *
        * @param    $center_ip string 通知中心IP
        * @param    $status string 模拟监控状态信息
        *
        * @return   void
     */
    public function smsSimulationAlarm($center_ip,$status) {
        $service = 'smsSimulationAlarm';
        $level = 'alarm';
        $param = $center_ip.$status;
        $log = "smsSimualtion Alarm({$center_ip}): " . $status;
        if($this->_con_mc->isAlarmValid($service,$param) === true) { //每30分钟报警一次
            $this->_logger->writeLog($service,$level,$param,$log);
            $this->sendAlarm($log);
        }
    }

    /**
        * @brief    mailSimulationAlarm 通知中心邮件服务模拟监控报警
        *
        * @param    $center_ip string 通知中心IP
        * @param    $status string 模拟监控状态信息
        *
        * @return   void
     */
    public function mailSimulationAlarm($center_ip,$status) {
        $service = 'mailSimulationAlarm';
        $level = 'alarm';
        $param = $center_ip.$status;
        $log = "mailSimulation Alarm({$center_ip}): " . $status;
        if($this->_con_mc->isAlarmValid($service,$param) === true) { //每30分钟报警一次
            $this->_logger->writeLog($service,$level,$param,$log);
            $this->sendAlarm($log);
        }
    }

    /**
        * @brief    receiveSMSProcessAlarm ReceiveSMS进程监控报警
        *
        * @param    $center_ip string 通知中心IP
        * @param    $status string 进程监控状态信息
        *
        * @return   void
     */
    public function receiveSMSProcessAlarm($center_ip,$status) {
        $service = 'receiveSMSProcessAlarm';
        $level = 'alarm';
        $param = $center_ip.$status;
        $log = "receiveSMSProcess Alarm({$center_ip}): " . $status;
        if($this->_con_mc->isAlarmValid($service,$param) === true) { //每30分钟报警一次
            $this->_logger->writeLog($service,$level,$param,$log);
            $this->sendAlarm($log);
        }
    }

    /**
        * @brief    receiveMailProcessAlarm ReceiveMail进程监控报警
        *
        * @param    $center_ip string 通知中心IP
        * @param    $status string 进程监控状态信息
        *
        * @return   void
     */
    public function receiveMailProcessAlarm($center_ip,$status) {
        $service = 'receiveMailProcessAlarm';
        $level = 'alarm';
        $param = $center_ip.$status;
        $log = "receiveMailProcess Alarm({$center_ip}): " . $status;
        if($this->_con_mc->isAlarmValid($service,$param) === true) { //每30分钟报警一次
            $this->_logger->writeLog($service,$level,$param,$log);
            $this->sendAlarm($log);
        }
    }

    /**
        * @brief    phpProcessAlarm PHP进程监控报警
        *
        * @param    $center_ip string 通知中心IP
        * @param    $status string 进程监控状态信息
        *
        * @return   void
     */
    public function phpProcessAlarm($center_ip,$status) {
        $service = 'phpProcessAlarm';
        $level = 'alarm';
        $param = $center_ip.$status;
        $log = "phpProcess Alarm({$center_ip}): " . $status;
        if($this->_con_mc->isAlarmValid($service,$param) === true) { //每30分钟报警一次
            $this->_logger->writeLog($service,$level,$param,$log);
            $this->sendAlarm($log);
        }
    }

    /**
        * @brief    keepalivedProcessAlarm Keepalived进程监控报警
        *
        * @param    $center_ip string 通知中心IP
        * @param    $status string 进程监控状态信息
        *
        * @return   void
     */
    public function keepalivedProcessAlarm($center_ip,$status) {
        $service = 'keepalivedProcessAlarm';
        $level = 'alarm';
        $param = $center_ip.$status;
        $log = "keepalivedProcess Alarm({$center_ip}): " . $status;
        if($this->_con_mc->isAlarmValid($service,$param) === true) { //每30分钟报警一次
            $this->_logger->writeLog($service,$level,$param,$log);
            $this->sendAlarm($log);
        }
    }

    /**
        * @brief    nginxProcessAlarm Nginx进程监控报警
        *
        * @param    $center_ip string 通知中心IP
        * @param    $status string 进程监控状态信息
        *
        * @return   void
     */
    public function nginxProcessAlarm($center_ip,$status) {
        $service = 'nginxProcessAlarm';
        $level = 'alarm';
        $param = $center_ip.$status;
        $log = "nginxProcess Alarm({$center_ip}): " . $status;
        if($this->_con_mc->isAlarmValid($service,$param) === true) { //每30分钟报警一次
            $this->_logger->writeLog($service,$level,$param,$log);
            $cc = array(
                'phone' => '13512221222',
                'mail' => 'admin1@message.com',
            );
            $this->sendAlarm($log,$cc);
        }
    }

    /**
        * @brief    timeoutAlarm 超时监控报警
        *
        * @param    $info string 超时信息
        * @param    $param string 超时参数
        *
        * @return   void
     */
    public function timeoutAlarm($info,$param='') {
        $service = 'timeoutAlarm';
        $level = 'alarm';
        $log = 'Timeout Alarm: ' . $info;
        if($this->_con_mc->isAlarmValid($service,$param) === true) { //每30分钟报警一次
            $this->_logger->writeLog($service,$level,$param,$log);
            $this->sendAlarm($log);
        }
    }

    /**
        * @brief    balanceAlarm 通知中心短信服务余额监控报警
        *
        * @param    $nowBalance interger 当前余额
        * @param    $param string 余额报警参数
        *
        * @return   void
     */
    public function balanceAlarm($nowBalance,$param='') {
        $service = 'balanceAlarm';
        $level = 'alarm';
        $log = 'Balance Alarm: ' . $nowBalance;
        if($this->_con_mc->isAlarmValid($service,$param,43200) === true) { //每十二小时报警一次
            $this->_logger->writeLog($service,$level,$param,$log);
            $cc = array(
                'phone' => '12511111111',
                'mail' => 'adminofbalance@message.com',
            );
            $this->sendAlarm($log,$cc);
        }
    }

    /**
        * @brief    postFileRenameAlarm 通知中心邮件服务上传文件重命名监控报警
        *
        * @param    $info string 报警信息
        * @param    $param string 报警参数
        *
        * @return   void
     */
    public function postFileRenameAlarm($info,$param='') {
        $service = 'postFileRenameAlarm';
        $level = 'alarm';
        $log = 'postFileRename Alarm: ' . $info . $param;
        if($this->_con_mc->isAlarmValid($service,$param) === true) { //每30分钟报警一次
            $this->_logger->writeLog($service,$level,$param,$log);
            $this->sendAlarm($log);
        }
    }

}
?>
