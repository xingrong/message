<?php
# Copyright 2012 Jike.com Inc. All Rights Reserved.
# Author: xingrong@jike.com (Xing Rong)

chdir(dirname(__FILE__));

/**
 * @brief    默认过滤策略
 */
class DefaultFilter {

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
     * @brief    新插入的短信在Mysql数据库中的id
     * @var interger
     */
    private $_retID; //短信在mysql里的id

    /**
     * @brief    Mysql数据库message_center连接信息
     * @var array
     */
    private $_mysqlInfo;

    /**
     * @brief    DefaultFilter类的本地日志文件名，用于初始化Logger类对象_logger
     * @var string
     */
    private $_log_file;

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
     * @brief    __construct 构造函数
     *
     * @param    $notification array 待过滤的消息
     * @param    $mysqlInfo array Mysql连接信息
     *
     * @return   void
     */
    function __construct($notification,$mysqlInfo) {
        $this->_debug = false;
        $this->_notification = $notification;
        $this->_ret = $this->_notification;
        $this->_retID = -1;
        $this->_mysqlInfo = $mysqlInfo;
        $this->_log_file = 'SMS_DefaultFilter.log';
        $this->_logger = new Logger($this->_log_file);
        $this->_monitor = new Monitor();

        $this->doFilter();
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
     * @brief    getRet 返回过滤之后的消息
     *
     * @return   array
     */
    public function getRet() {
        return $this->_ret;
    }

    /**
     * @brief    getRetID 获取新插入消息在Mysql中的id
     *
     * @return   interger
     */
    public function getRetID() {
        return $this->_retID;
    }

    /**
     * @brief    doFilter 默认过滤处理
     *
     * @return   boolean true标识过滤完成
     */
    private function doFilter() {
        //测试simulation短信
        if('15210651786' == $this->_notification['phone'] && 
            'test' == $this->_notification['content']) {
                $this->_ret = '';
                return true;
            }
        //过滤重复短信
        $con = mysql_connect($this->_mysqlInfo['host'],$this->_mysqlInfo['username'],$this->_mysqlInfo['password']);
        if(!$con) {
            $service = 'SMS';
            $level = 'error';
            $param = "DefaultFilter.php/function: doFilter";
            $logArray = array(
                'FEEDBACK_TIME' => $this->_ret['FEEDBACK_TIME'],
                'FEEDBACK_POST' => $this->_ret['FEEDBACK_POST'],
                'NOW_TIME' => time(),
                'mysql_error' => mysql_error(),
            );
            $log = json_encode($logArray);
            $this->_logger->writeLog($service,$level,$param,$log);
            $this->_monitor->sendAlarm($log.$param);
            return false;
        }
        mysql_select_db($this->_mysqlInfo['database'],$con);
        $sql_select = "select id,time from smsinfo 
            where phone = '" . $this->_notification['phone'] . "' 
            and content = '" . $this->_notification['content'] . "' 
            order by time desc limit 1";
        if(!mysql_ping($con)) {
            mysql_close($con);
            $con = mysql_connect($this->_mysqlInfo['host'],$this->_mysqlInfo['username'],$this->_mysqlInfo['password']);;
        }

        $result = mysql_query($sql_select, $con);
        $sql_array = mysql_fetch_array($result);
        $sql_time = $sql_array['time'];
        $this->_retID = $sql_array['id'];
        if(!strtotime($sql_time)) {
            $sql_insert = "insert into smsinfo (username,phone,content,feedback,user_ip) 
                values ('" . $this->_notification['username'] . "','" . $this->_notification['phone'] . "','" . $this->_notification['content'] . "','" . $this->_notification['feedback'] . "','" . $this->_notification['VISITOR_IP'] . "')";
            if(!mysql_ping($con)) {
                mysql_close($con);
                $con = mysql_connect($this->_mysqlInfo['host'],$this->_mysqlInfo['username'],$this->_mysqlInfo['password']);;
            }

            mysql_query($sql_insert);
            $this->_retID = mysql_insert_id($con);
            return true;
        }
        $diff_time = time() - strtotime($sql_time);
        if($diff_time > 300) {
            $sql_insert = "insert into smsinfo (username,phone,content,feedback,user_ip) 
                values ('" . $this->_notification['username'] . "','" . $this->_notification['phone'] . "','" . $this->_notification['content'] . "','" . $this->_notification['feedback'] . "','" . $this->_notification['VISITOR_IP'] . "')";
            if(!mysql_ping($con)) {
                mysql_close($con);
                $con = mysql_connect($this->_mysqlInfo['host'],$this->_mysqlInfo['username'],$this->_mysqlInfo['password']);;
            }

            mysql_query($sql_insert);
            $this->_retID = mysql_insert_id($con);
            return true;
        }
        else if($diff_time >= 0) {
            $sql_update = "update smsinfo set repeat_num = repeat_num + 1 where id={$this->_retID}";
            if(!mysql_ping($con)) {
                mysql_close($con);
                $con = mysql_connect($this->_mysqlInfo['host'],$this->_mysqlInfo['username'],$this->_mysqlInfo['password']);;
            }

            mysql_query($sql_update);
            $this->_ret = '';
            return true;
        }
        else {
            //由于消息队列PHP客户端目前存在消息存取乱序的问题，所以此处监控取消。
            /*
            $service = 'SMS';
            $level = 'error';
            $param = "DefaultFilter.php/function: doFilter";
            $logArray = array(
                'difftime_error' => $diff_time,
                'NOW_TIME' => time(),
                'FEEDBACK_POST' => $this->_ret['FEEDBACK_POST'],
                'FEEDBACK_TIME' => $this->_ret['FEEDBACK_TIME'],
            );
            $log = json_encode($logArray);
            $this->_logger->writeLog($service,$level,$param,$log);
            $this->_monitor->sendAlarm($log.$param);
            return false;
             */
        }
    }
}
?>
