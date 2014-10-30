<?php
# Author: xingrong0804@163.com (Xing Rong)

chdir(dirname(__FILE__));

require_once(__DIR__ . "/Mailer.php");
require_once(__DIR__ . "/Logger.php");

/**
 * @brief    Feedback类主要功能是给用户通知中心的服务反馈，
 *           包括两个方面：一是restful接口处的打印到标准输出的反馈，二是通过反馈邮箱给用户的邮件反馈;
 */
class Feedback {

    /**
     * @brief    标记是否启用debug模式，默认为false，即非debug模式
     * @var boolean
     */
    private $_debug;

    /**
     * @brief    Feedback类的本地日志文件名，用于初始化Logger类对象_logger
     * @var string
     */
    private $_log_file;

    /**
     * @brief    可接受string类型和array类型，反馈信息，被构造函数的行参初始化
     * @var mixed
     */
    private $_notification;

    /**
     * @brief    反馈邮箱，被初始化为反馈信息中的反馈邮箱值，如没有则默认为admin邮箱
     * @var string
     */
    private $_feedback;

    /**
     * @brief    管理员邮箱，默认为xingrong@jike.com
     * @var string
     */
    private $_admin;

    /**
     * @brief    Mailer类对象，用于发送反馈邮件
     * @var object
     */
    private $_mailer;

    /**
     * @brief    Logger类对象，用于记录Feedback.php的本地日志
     * @var object
     */
    private $_logger;

    /**
        * @brief    __construct 构造函数
        *
        * @param    $notification mixed 反馈信息，可接受string类型和array类型
        *
        * @return   void
     */
    function __construct($notification = "feedback") {
        $this->_debug = false;
        $this->_log_file = "feedback.log";
        $this->_notification = $notification;
        $this->_admin = 'xingrong0804@163.com';
        if(is_array($this->_notification)) {
            $this->_feedback = $this->_notification['feedback'];
        }
        else {
            $this->_feedback = $this->_admin;
        }
        $this->_mailer = new Mailer();
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
        * @brief    printFeedback 打印反馈信息至标准输出
        *
        * @param    $info array 反馈信息数组
        *
        * @return   string 反馈信息字符串
     */
    public function printFeedback($info) {
        return json_encode($info) . "\n";
    }

    /**
        * @brief    sendMailFeedback 发送反馈信息邮件
        *
        * @param    $info array 反馈信息数组
        *
        * @return   boolean true表示邮件发送成功
     */
    public function sendMailFeedback($info,$body='',$attachments='') {
        $mailRet = $this->_mailer->SendMail(
            $this->_feedback,
            'message@message.com',
            $this->_admin,
            '',
            'Message Send Failed from message',
            $info."<br>".$body,
            $attachments
        );
        if($mailRet === false) {
            $service = 'sendMailFeedback';
            $level = 'fatal';
            $param = $this->_mailer->PrintRes();
            $log = "feedback_msg: {$body}";
            $this->_logger->writeLog($service,$level,$param,$log);
            return false;
        }
        return true;
    }
}
?>
