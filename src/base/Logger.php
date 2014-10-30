<?php
# Copyright 2012 Jike.com Inc. All Rights Reserved.
# Author: xingrong@jike.com (Xing Rong)

chdir(dirname(__FILE__));

require_once __DIR__ . '/MessageCenterCon.php';

/**
 * @brief    Logger类主要功能是记录日志，
 *           包括两个方面，一是记录至文件，二是记录至Mysql
 */
class Logger {

    /**
     * @brief    通知中心日志文件的默认存放路径
     * @var string
     */
    private $_log_path;

    /**
     * @brief    具体的日志文件名，默认为_log_path下的Default.log文件
     * @var string
     */
    private $_log_file;

    /**
     * @brief    MessageCenterCon类对象，用于记录日志至Mysql
     * @var object
     */
    private $_con_mc;

    /**
        * @brief    __construct 构造函数
        *
        * @param    $file string 具体的日志文件名
        *
        * @return   void
     */
    function __construct($file='') {
        $this->_log_path = '/var/gs/message/logs/';
        if(empty($file)) {
            $this->_log_file = $this->_log_path . 'Default.log';
        }
        else {
            $this->_log_file = $this->_log_path . $file;
        }
        $this->_con_mc = new MessageCenterCon();

        date_default_timezone_set('Asia/Shanghai');
        error_reporting(E_ALL);
    }

    /**
        * @brief    putLogIntoFile 记录日志至本地文件
        *
        * @param    $log_msg array 日志信息
        *
        * @return   void
     */
    private function putLogIntoFile($log_msg) {
        $log = date("Y-m-d H:i:s") . json_encode($log_msg) . "\n";
        error_log($log,3,$this->_log_file);
    }

    /**
        * @brief    putLogIntoMysql 记录日志至Mysql
        *
        * @param    $service string 服务类型
        * @param    $level string 日志级别
        * @param    $param string 日志参数
        * @param    $log string 日志信息
        *
        * @return   interger Mysql插入id
     */
    private function putLogIntoMysql($service,$level,$param,$log) {
        $insert_id = $this->_con_mc->insertIntoLog($service,$level,$param,$log);
        return $insert_id;
    }

    /**
        * @brief    writeLog 记录日志至本地文件和Mysql
        *
        * @param    $service string 服务类型
        * @param    $level string 日志级别
        * @param    $param string 日志参数
        * @param    $log string 日志信息
        *
        * @return   void
     */
    public function writeLog($service,$level,$param,$log) {
        $log_msg = array(
            'param' => $param,
            'log' => $log,
        );
        $this->putLogIntoFile($log_msg);
        $this->putLogIntoMysql($service,$level,$param,$log);
    }
}
?>
