<?php
# Author: xingrong0804@163.com (Xing Rong)

chdir(dirname(__FILE__));

require_once(__DIR__ . '/conf/priorityMQ.php');
require_once(__DIR__ . '/conf/blackList.php');
require_once(__DIR__ . '/FilterModule.php');
require_once('/var/gs/message/src/base/lib/RabbitMQClient.inc');
require_once('/var/gs/message/src/base/NotiException.php');

/**
    * @brief    SendPriority类的主要功能是检查用户POST参数的合法性并发送消息至消息队列
 */
class SendPriority{

    /**
     * @brief    待发送至消息队列的消息
     * @var array
     */
    private $_notification;

    /**
     * @brief    RabbitMQ的PHP客户端对象
     * @var object
     */
    private $_client;

    /**
     * @brief    标记是否处于debug模式
     * @var boolean
     */
    private $_debug;

    /**
     * @brief    优先级消息队列设置
     * @var array
     */
    private $_priorityMQ;

    /**
     * @brief    默认优先级
     * @var interger
     */
    private $_priority;

    /**
     * @brief    默认过滤策略
     * @var interger
     */
    private $_filter;

    /**
     * @brief    用户POST参数
     * @var array
     */
    private $_paramArray;

    /**
     * @brief    标记消息是否发送成功
     * @var boolean
     */
    private $_sendFlag;

    /**
        * @brief    __construct 构造函数
        *
        * @param    $paramArray array 用户POST参数
        *
        * @return   void
     */
    function __construct($paramArray) {
        $this->_paramArray = $paramArray;
        global $priorityMQ;
        $this->_priorityMQ = $priorityMQ;
        try {
            //$this->_client = new RabbitMQClient($this->_priorityMQ);
        }
        catch(Exception $e) {
            //throw new NotiException($e->getMessage(),'11111');
        }
        $this->_debug = false; //默认关闭debug模式
        $this->_priority = 2; //默认优先级
        $this->_filter = 0; //默认过滤策略
        $this->_sendFlag = false; //默认失败

        $this->buildNotification();
        $this->sendNotification();
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
        * @brief    buildNotification 检查用户POST参数的合法性并构造待发送的消息
        *
        * @return   void
     */
    private function buildNotification() {
        //优先级
        if(!empty($this->_paramArray['priority'])) {
            $this->_notification['priority'] = $this->_paramArray['priority'];
        }
        else {
            $this->_notification['priority'] = $this->_priority;
        }
        //过滤策略
        if(!empty($this->_paramArray['filter'])) {
            $this->_notification['filter'] = $this->_paramArray['filter'];
        }
        else {
            $this->_notification['filter'] = $this->_filter;
        }
        //发件人
        if(!empty($this->_paramArray['from'])) {
            $this->_notification['from'] = $this->_paramArray['from'];
            //反馈邮箱
            $this->_notification['feedback'] = $this->_paramArray['from'];
        }
        else {
            throw new NotiException('From parameter is null!','10105');
        }
        if(!filter_var($this->_notification['from'], FILTER_VALIDATE_EMAIL)) {
            throw new NotiException('Parameter from\'s value invalid!','10204');
        }
        //收件人列表
        if(!empty($this->_paramArray['to'])) {
            $this->_notification['to'] = $this->_paramArray['to'];
        }
        else {
            throw new NotiException('To parameter is null!','10103');
        }
        $toEmail = explode(';',$this->_notification['to']);
        foreach($toEmail AS $key=>$val){
            $blackAdd = $this->inBlackList($val);
            if($blackAdd) {
                throw new NotiException("BlackList: {$blackAdd}",'10402'); 
            }

            if(!filter_var($val, FILTER_VALIDATE_EMAIL)) {
                throw new NotiException('Parameter to\'s value invalid!','10205');
            }
        }
        //邮件正文
        if(!empty($this->_paramArray['bodyFile'])) { //使用empty判断，拒绝空文件
            $this->_notification['bodyFile'] = $this->_paramArray['bodyFile'];
        }
        else {
            if(!empty($this->_paramArray['body'])) {
                $this->_notification['body'] = $this->_paramArray['body'];
            }
            else {
                throw new NotiException('Body parameter is null!','10104');
            }
        }
        //检测cc和bcc格式
        if(!empty($this->_paramArray['cc'])) {
            $ccEmail = explode(';',$this->_paramArray['cc']);
            foreach($ccEmail AS $key=>$val){
                $blackAdd = $this->inBlackList($val);
                if($blackAdd) {
                    throw new NotiException("BlackList: {$blackAdd}",'10402'); 
                }

                if(!filter_var($val, FILTER_VALIDATE_EMAIL)) {
                    throw new NotiException('Parameter cc\'s value invalid!','10206');
                }
            }
        } 
        else {
            $this->_paramArray['cc'] = '';
        }
        if(!empty($this->_paramArray['bcc'])) {
            $bccEmail = explode(';',$this->_paramArray['bcc']);
            foreach($bccEmail AS $key=>$val){
                $blackAdd = $this->inBlackList($val);
                if($blackAdd) {
                    throw new NotiException("BlackList: {$blackAdd}",'10402'); 
                }

                if(!filter_var($val, FILTER_VALIDATE_EMAIL)) {
                    throw new NotiException('Parameter bcc\'s value invalid!','10207');
                }
            }
        }
        else {
            $this->_paramArray['bcc'] = '';
        }
        //copy其他信息
        $this->_notification = $this->_notification + $this->_paramArray;
    }

    /**
        * @brief    inBlackList 检查用户POST参数是否在黑名单中
        *
        * @param    $address string 邮箱地址
        *
        * @return   mixed 返回黑名单地址或者返回false表示参数合法
     */
    private function inBlackList($address) {
        foreach($GLOBALS['BLACK_LIST'] as $bl_k => $bl_v) {
            if($address == $bl_v) {
                return $bl_v;
            }
        }
        return false;
    }

    /**
        * @brief    sendNotification 发送消息至优先级消息队列
        *
        * @return   void
     */
    private function sendNotification() {
        //转换为JSON格式
        $msg = json_encode($this->_notification,JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        //首先判断队列消息数目
        $queueCountJson = file_get_contents("/var/gs/message/src/mail/queueCount/noticenter_mail_1_0");
        $queueCountArray = json_decode($queueCountJson,true);
        $queueCount = $queueCountArray['message_count'];
        //if($queueCount > 1000) {
        if(true) {
            system("php ".__DIR__."/FilterModule.php '" . $msg . "'" . " > /dev/null 2>&1 &");
            $this->_sendFlag = true;
            return true;
        }
        //发送消息到优先级队列
        if(array_key_exists(strval($this->_notification['priority']),$this->_priorityMQ['TOPIC'])) {
            $topicArray = $this->_priorityMQ['TOPIC'][strval($this->_notification['priority'])];
            $topic = $topicArray[array_rand($topicArray)]; //负载均衡
            try {
                $this->_sendFlag = $this->_client->publish($topic, $msg, true);
            }
            catch(Exception $e) {
                try {
                    $filterModule = new FilterModule($msg);
                    $this->_sendFlag = $filterModule->isSent();
                }
                catch(Exception $filterError) {
                    throw new NotiException($filterError->getMessage(),'11111');
                }
            }
            if($this->_sendFlag) {
                //return true;
            }
            $filterModule = new FilterModule($msg);
            $this->_sendFlag = $filterModule->isSent();
            if(!$this->_sendFlag) {
                throw new NotiException('Message Send Failed!','30201');
            }
        }
        else {
            throw new NotiException('Parameter priority\'s value invalid!!','10202');
        }

    }
}
