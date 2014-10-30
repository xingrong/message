<?php
# Author: xingrong0804@163.com (Xing Rong)

chdir(dirname(__FILE__));

require_once(__DIR__ . '/lib/class.phpmailer.php');
require_once(__DIR__ . '/lib/class.smtp.php');

/**
 * @brief    Mailer类的主要功能是发送邮件
 */
class Mailer{

    /**
     * @brief    默认SMTP设置
     * @var array
     */
    private $_defaultConf;

    /**
     * @brief    PHPMailer对象
     * @var object
     */
    private $_mailer;

    /**
     * @brief    邮件发送返回结果
     * @var array
     */
    private $_res;

    /**
     * @brief    标记是否处于debug模式
     * @var boolean
     */
    private $_debug;

    /**
     * @brief    __construct 构造函数
     *
     * @return   void
     */
    function __construct() {
        date_default_timezone_set('Asia/Shanghai');//设定时区东八区
        $this->_defaultConf = array(
            'Host'=>'smtp.163.com',
            'Port'=>25,
        );
        $this->_mailer = new PHPMailer(true);//捕获异常
        $this->_debug = false; //默认关闭debug模式

        $this->SetSMTP();
    }

    /**
     * @brief    是否处于debug模式
     *
     * @return   boolean true表示处于debug模式
     */
    public function IsDebug() {
        return $this->_debug;
    }

    /**
     * SMTP设置函数
     * 
     * @param $host string SMTP服务器地址
     * @param $port string SMTP服务器端口
     *
     * @return void
     */
    private function SetSMTP($host = '',$port = '') {
        $this->_mailer->CharSet ='UTF-8';//设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
        $this->_mailer->IsSMTP(); // 设定使用SMTP服务
        // SMTP服务器的地址
        if (trim($host)==='') {
            $this->_mailer->Host = $this->_defaultConf['Host'];
        }
        else {
            $this->_mailer->Host = $host;
        }
        // SMTP服务器的端口号
        if (trim($port)==='') {
            $this->_mailer->Port = $this->_defaultConf['Port'];
        }
        else {
            $this->_mailer->Port = $port;
        }
    }

    /**
     * 错误信息函数
     * 
     * @param $status string 错误代码
     * @param $msg string 错误信息
     *
     * @return array('status'=>,'msg'=>)
     */
    private function MailError($status,$msg) {
        $this->_res = array('status'=>$status,'msg'=>$msg);
        return $this->_res;
    }

    /**
     * @brief    PrintRes 返回邮件发送结果
     *
     * @return   string
     */
    public function PrintRes() {
        return json_encode($this->_res);
    }

    /**
     * 邮件发送函数
     * 
     * @param $to string 收信人列表
     * @param $from string 发件人列表
     * @param $cc string 抄送列表
     * @param $bcc string 暗送列表
     * @param $subject string 邮件主题
     * @param $body string 邮件正文
     * @param $attachments array(strings) 邮件附件
     *
     * @return boolean
     */
    public function SendMail($to,$from,$cc = '',$bcc = '',$subject = '',$body = '',$attachments = array()){
        //收件人
        if (NULL==$to || trim($to)==='') {
            $this->MailError('10101','To parameter is null!');
            return false;
        }
        else {
            try {
                $toEmail = explode(';',$to);
                foreach($toEmail AS $k=>$val){                
                    $toNAME = explode('@',$val);
                    $this->_mailer->AddAddress($val, $toNAME[0]);
                }
            }
            catch(phpmailerException $e) {
                if('invalid_address' == $e->getMessage()) {
                    $this->MailError('10201','Parameter to\'s value invalid!');
                    return false;
                }
            }
        }
        //发件人
        if (NULL==$from || trim($from)==='') {
            $this->MailError('10103','From parameter is null!');
            return false;
        }
        else {
            try {
                $fromNAME = explode('@',$from);
                $this->_mailer->SetFrom($from, $fromNAME[0]);
            }
            catch(phpmailerException $e) {
                $this->MailError('10204','Parameter from\'s value invalid!');
                return false;
            }           
        }
        //抄送
        if (trim($cc)==='') {
        }
        else {
            try {
                $ccEmail = explode(';',$cc);
                foreach($ccEmail AS $k=>$val){                
                    $ccNAME = explode('@',$val);
                    $this->_mailer->AddCC($val, $ccNAME[0]);
                }
            }
            catch(phpmailerException $e) {
                if('invalid_address' == $e->getMessage()) {
                    $this->MailError('10202','Parameter cc\'s value invalid!');
                    return false;
                }
            }
        }
        //暗送
        if (trim($bcc)==='') {
        }
        else {
            try {
                $bccEmail = explode(';',$bcc);
                foreach($bccEmail AS $k=>$val){                
                    $bccNAME = explode('@',$val);
                    $this->_mailer->AddBCC($val, $bccNAME[0]);
                }
            }
            catch(phpmailerException $e) {
                if('invalid_address' == $e->getMessage()) {
                    $this->MailError('10203','Parameter bcc\'s value invalid!');
                    return false;
                }
            }
        }
        //邮件主题
        $this->_mailer->Subject = $subject;
        //备用信息
        $this->_mailer->AltBody = 'To view the message, please use an HTML compatible email viewer!';
        //使用HTML发送
        $this->_mailer->MsgHTML($body);
        //附件
        try {
            if(!empty($attachments)) {
                foreach($attachments AS $k=>$val) {
                    $this->_mailer->AddAttachment($val);
                }
            }
        }
        catch(phpmailerException $e) {
            $this->MailError('10301','Attachment invalid!');
            return false;
        }
        //发送邮件
        try {
            if ($this->_mailer->Send()) {
                $this->MailError('00000','Mail Send Success!');
                return true;
            }
        }
        catch(phpmailerException $e) {
            //发送未送达
            if('12306' == $e->getCode()) {
                $this->MailError('10401',"Recipients unreachable:{$e->getMessage()}");
                return false;
            }
            //body为空
            if('empty_message' == $e->getMessage()) {
                $this->MailError('10102','Body parameter is null!');
                return false;
            }
            $this->MailError('30201','Mail Send Failed! '.$e->getMessage());
            return false;
        }
    }
}
