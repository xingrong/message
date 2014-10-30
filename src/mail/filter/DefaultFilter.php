<?php
# Author: xingrong0804@163.com (Xing Rong)

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
     * @brief    新插入的邮件在Mysql数据库中的id
     * @var interger
     */
    private $_retID; //邮件在mysql里的id

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
        $this->_notification = $notification;
        $this->_ret = $this->_notification;
        $this->_retID = -1;
        $this->_log_file = 'Mail_DefaultFilter.log';
        $this->_logger = new Logger($this->_log_file);
        $this->_monitor = new Monitor();
        $this->_con_mc = new MessageCenterCon();

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
        if('xingrong@message.com' == $this->_notification['to'] && 
            'xingrong@message.com' == $this->_notification['from'] && 
            'test' == $this->_notification['body']
        ) {
            $this->_ret = '';
            return true;
        }

        if(!empty($this->_notification['bodyFile'])) {
            $this->_notification['body'] = file_get_contents($this->_notification['bodyFile']);
        }

        $this->_retID = $this->_con_mc->insertIntoMailinfo(
            $this->_notification['from'],
            $this->_notification['to'],
            $this->_notification['subject'],
            $this->_notification['body'],
            $this->_notification['VISITOR_IP'],
            $this->_notification['priority'],
            $this->_notification['filter'],
            $this->_notification['cc'],
            $this->_notification['bcc']
        );
        return true;
    }
}
?>
