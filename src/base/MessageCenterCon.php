<?php
# Copyright 2012 Jike.com Inc. All Rights Reserved.
# Author: xingrong@jike.com (Xing Rong)

chdir(dirname(__FILE__));
require_once __DIR__ . '/conf/mysqlConf.php';
require_once __DIR__ . '/conf/centerConf.php';

/**
 * @brief    MessageCenterCon类主要功能是于Mysql数据库message_center交互
 */
class MessageCenterCon {

    /**
     * @brief    Mysql连接标识
     * @var object
     */
    private $_con;

    /**
     * @brief    通知中心IP
     * @var string
     */
    private $_center_ip;

    /**
     * @brief    __construct 构造函数
     *
     * @return   void
     */
    function __construct() {
        $this->_log_file = __DIR__ . '/logs/MessageCenterCon.log';
        $this->_center_ip = $GLOBALS['CENTER_IP'];

        $this->connectDB();
    }

    /**
     * @brief    __destruct 析构函数
     *
     * @return   void
     */
    function __destruct() {
    }

    /**
     * @brief    connectDB 连接到Mysql数据库message_center
     *
     * @return   void
     */
    private function connectDB() {
        $this->_con = mysql_connect(
            $GLOBALS['MC_MYSQL']['HOST'],$GLOBALS['MC_MYSQL']['USER'],$GLOBALS['MC_MYSQL']['PSWD']
        );
        if(!$this->_con) {
            $log = date("Y-m-d H:i:s") . " mysql_connect of MessageCenterCon failed!" . mysql_error() . "\n";
            error_log($log,3,$this->_log_file);
            return false;
        }
        mysql_select_db($GLOBALS['MC_MYSQL']['DB'],$this->_con);
    }

    /**
     * @brief    reconnectDB 重新连接到Mysql数据库message_center
     *
     * @return   void
     */
    private function reconnectDB() {
        if(!mysql_ping($this->_con)) {
            mysql_close($this->_con);
            $this->connectDB();
        }
    }
    /**
     * @brief    insertIntoLog 插入新日志
     *
     * @param    $service string 服务类型
     * @param    $level string 日志级别
     * @param    $param string 日志参数
     * @param    $log string 日志信息
     *
     * @return   interger 插入新日志id
     */
    public function insertIntoLog(
        $service,
        $level,
        $param,
        $log
    ) {
        $logSqlArray['center_ip'] = $this->_center_ip;
        $logSqlArray['service'] = $service;
        $logSqlArray['level'] = $level;
        $logSqlArray['param'] = $param;
        $logSqlArray['log'] = $log;

        $start = true;
        $key = "";
        $val = "";
        foreach($logSqlArray as $k=>$v) {
            if(isset($v)) {
                if($start) {
                    $key .= $k;
                    $val .= "'" . $v . "'";
                    $start = false;
                }
                else {
                    $key .= "," . $k;
                    $val .= "," . $this->check_input($v);
                }
            }
        }

        $sql_insert = "insert into log(" . $key
            . ") values (" . $val . ")";
        $this->reconnectDB();
        $result = mysql_query($sql_insert,$this->_con);
        if(!$result) {
            $log = date("Y-m-d H:i:s") . "mysql_insert of InsertIntoLog failed!" . mysql_error() . "\n" . $sql_insert;
            error_log($log,3,$this->_log_file);
            return false;
        }
        return mysql_insert_id($this->_con);
    }

    /**
     * @brief    insertIntoSMSinfo 插入新短信信息
     *
     * @param    $username string 用户名
     * @param    $phone string 手机号码
     * @param    $content string 短信内容
     * @param    $feedback string 反馈邮箱
     * @param    $user_ip string 访问者IP
     * @param    $priority string 优先级
     * @param    $filter string 过滤策略
     *
     * @return   interger 插入新短信信息id
     */
    public function insertIntoSMSinfo(
        $username,
        $phone,
        $content,
        $feedback,
        $user_ip,
        $priority,
        $filter
    ) {
        $smsSqlArray['username'] = $username;
        $smsSqlArray['phone'] = $phone;
        $smsSqlArray['content'] = $content;
        $smsSqlArray['feedback'] = $feedback;
        $smsSqlArray['user_ip'] = $user_ip;
        $smsSqlArray['priority'] = $priority;
        $smsSqlArray['filter'] = $filter;

        $start = true;
        $key = "";
        $val = "";
        foreach($smsSqlArray as $k=>$v) {
            if(isset($v)) {
                if($start) {
                    $key .= $k;
                    $val .= "'" . $v . "'";
                    $start = false;
                }
                else {
                    $key .= "," . $k;
                    $val .= "," . $this->check_input($v);
                }
            }
        }

        $sql_insert = "insert into smsinfo(" . $key
            . ") values (" . $val . ")";
        $this->reconnectDB();
        $result = mysql_query($sql_insert,$this->_con);
        if(!$result) {
            $log = date("Y-m-d H:i:s") . "mysql_insert of InsertIntoSMSinfo failed!" . mysql_error() . "\n" . $sql_insert;
            error_log($log,3,$this->_log_file);
            return false;
        }
        return mysql_insert_id($this->_con);
    }

    /**
     * @brief    insertIntoMailinfo 插入新邮件信息
     *
     * @param    $mailfrom string 发件人
     * @param    $mailto string 收件人
     * @param    $subject string 邮件主题
     * @param    $body string 邮件内容
     * @param    $user_ip string 访问者IP
     * @param    $priority string 优先级
     * @param    $filter string 过滤策略
     * @param    $cc string 抄送人
     * @param    $bcc string 暗送人
     *
     * @return   interger 插入新邮件信息id
     */
    public function insertIntoMailinfo(
        $mailfrom,
        $mailto,
        $subject,
        $body,
        $user_ip,
        $priority,
        $filter,
        $cc,
        $bcc
    ) {
        $mailSqlArray['mailfrom'] = $mailfrom;
        $mailSqlArray['mailto'] = $mailto;
        $mailSqlArray['subject'] = $subject;
        $mailSqlArray['body'] = $body;
        $mailSqlArray['user_ip'] = $user_ip;
        $mailSqlArray['priority'] = $priority;
        $mailSqlArray['filter'] = $filter;
        $mailSqlArray['cc'] = $cc;
        $mailSqlArray['bcc'] = $bcc;

        $start = true;
        $key = "";
        $val = "";
        foreach($mailSqlArray as $k=>$v) {
            if(isset($v)) {
                if($start) {
                    $key .= $k;
                    $val .= "'" . $v . "'";
                    $start = false;
                }
                else {
                    $key .= "," . $k;
                    $val .= "," . $this->check_input($v);
                }
            }
        }

        $sql_insert = "insert into mailinfo(" . $key
            . ") values (" . $val . ")";
        $this->reconnectDB();
        $result = mysql_query($sql_insert,$this->_con);
        if(!$result) {
            $log = date("Y-m-d H:i:s") . "mysql_insert of InsertIntoMailinfo failed!" . mysql_error() . "\n" . $sql_insert;
            error_log($log,3,$this->_log_file);
            return false;
        }
        return mysql_insert_id($this->_con);
    }

    /**
     * @brief    isAlarmValid 检查报警是否合法，目前只检查间隔时间的合法性
     *
     * @param    $service string 服务类似
     * @param    $param string 日志参数
     * @param    $interval interger 报警间隔，默认为30分钟
     *
     * @return   boolean true表示报警合法
     */
    public function isAlarmValid($service,$param,$interval=1800) {
        $sql_select = "SELECT id,max(time) FROM log WHERE 
            service='".$service."' and level='alarm' and param='".$param."'";
        $this->reconnectDB();
        $result = mysql_query($sql_select,$this->_con);
        if(!$result) {
            $log = date("Y-m-d H:i:s") . "select of isAlarmValid failed!" . mysql_error() . "\n";
            error_log($log,3,$this->_log_file);
            return -1;
        }
        $sql_array = mysql_fetch_array($result);
        $sql_time = $sql_array['max(time)'];
        $sql_id = $sql_array['id'];
        if(empty($sql_id)) {
            return true;
        }
        else {
            $diff_time = time() - strtotime($sql_time);
            if($diff_time > $interval) {
                return true;
            }
            else return false;
        }
    }

    /**
     * @brief    check_input 检查Mysql参数合法性并转义非法字符
     *
     * @param    $value string Mysql参数
     *
     * @return   string 转换后的Mysql参数
     */
    private function check_input($value) {
        // 去除斜杠
        if (get_magic_quotes_gpc())
        {
            $value = stripslashes($value);
        }
        // 如果不是数字则加引号
        if (!is_numeric($value))
        {
            $value = "'" . mysql_real_escape_string($value,$this->_con) . "'";
        }
        return $value;
    }
}
