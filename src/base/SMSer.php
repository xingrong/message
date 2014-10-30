<?php
# Copyright 2012 Jike.com Inc. All Rights Reserved.
# Author: xingrong@jike.com (Xing Rong)

chdir(dirname(__FILE__));

require_once(__DIR__ . "/conf/thirdSMS.php");

/**
 * @brief    SMSer类的主要功能是发送短信
 */
class SMSer {

    /**
     * @brief    标识是否处于debug模式
     * @var boolean
     */
    private $_debug;

    /**
     * @brief    第三方短信设置
     * @var array
     */
    private $_thirdSMS;

    /**
     * @brief    短信发送结果信息
     * @var array
     */
    private $_res;

    /**
     * @brief    __construct 构造函数
     *
     * @return   void
     */
    function __construct() {
        $this->_debug = false;
        $this->_thirdSMS = $GLOBALS['THIRD_SMS'];
        $this->_res = array();
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
     * @brief    SMSError 返回短信发送结果
     *
     * @param    $status string 返回代码
     * @param    $msg string 返回信息
     *
     * @return   array
     */
    private function SMSError($status,$msg) {
        $this->_res = array('status'=>$status,'msg'=>$msg);
        return $this->_res;
    }

    /**
     * @brief    PrintRes 返回短信发送结果字符串
     *
     * @return   string
     */
    public function PrintRes() {
        return json_encode($this->_res);
    }

    /**
     * @brief    sendCatSMS 通过短信猫发送短信
     *
     * @param    $phone string 手机号码
     * @param    $content string 短信内容
     *
     * @return   boolean true表示短信发送成功
     */
    private function sendCatSMS($phone,$content) {
        $ch = curl_init();
        $msgURL = "http://192.168.10.25/database.php?phoneno=" . urlencode($phone) . "&content=" . urlencode($content);
        curl_setopt($ch, CURLOPT_URL, $msgURL);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $curlRet = curl_exec($ch);
        if(false === $curlRet) {
            $this->SMSError($curlRet,'catSMS return false!');
            return false;
        }
        curl_close($ch);
        if((!strstr($curlRet,"insert")) && (!strstr($curlRet,"sendFetion"))) {
            $this->SMSError($curlRet,'The return of catSMS is wrong!');
            return false;
        }
        else {
            return true;
        }
    }

    /**
     * @brief    isContentLenValid 检查短信内容长度是否合法并截断舍弃非法长度内容
     *
     * @param    &$content string 短信内容，引用传值，保存返回的短信内容
     *
     * @return   boolean true表示长度合法
     */
    private function isContentLenValid(&$content) {
        $contentLen = mb_strlen($content,'utf-8');
        $chCut = 128;
        $enCut = 154;
        if(preg_match("/[\x7f-\xff]/", $content)) { //含中文字符，超过134字符截断
            if($contentLen > $chCut) { //考虑[JIKE]
                $content = mb_substr($content,0,$chCut,'utf-8');
                return false;
            }
            return true;
        }
        else { //纯英文字符，超过160字符截断
            if($contentLen > $enCut) { //考虑[JIKE]
                $content = mb_substr($content,0,$enCut,'utf-8');
                return false;
            }
            return true;
        }
    }

    /**
     * @brief    sendThirdSMS 通过第三方接口发送短信
     *
     * @param    $phone string 手机号码
     * @param    $content string 短信内容
     *
     * @return   boolean true表示短信发送成功
     */
    private function sendThirdSMS($phone,$content) {
        //截断字符
        $contentLen = mb_strlen($content,'utf-8');
        //if('18910075646' == $phone || $this->isContentLenValid(&$content) === false) {
            //$this->SMSError($contentLen,'contentLen invalid!');
        //}
        $this->isContentLenValid(&$content);
        //send
        $flag = 0;
        //要post的数据 
        $argv = array(
            'sn' => $this->_thirdSMS['thirdSN'], //提供的账号
            'pwd' => strtoupper(md5($this->_thirdSMS['thirdSN'] . $this->_thirdSMS['thirdPWD'])), //此处密码需要加密 加密方式为 md5(sn+password) 32位大写
            'mobile' => $phone,//手机号 多个用英文的逗号隔开 post理论没有长度限制.推荐群发一次小于等于10000个手机号
            'content' => iconv("UTF-8","GB2312//IGNORE",strip_tags($content)."[JIKE]") ,//短信内容
            'ext' => '',
            'rrid' => 'success',//默认空 如果空返回系统生成的标识串 如果传值保证值唯一 成功则返回传入的值
            'stime' => ''//定时时间 格式为2011-6-29 11:09:21
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
        $fp = fsockopen("sdk2.entinfo.cn",80,$errno,$errstr,10);// or exit($errstr."--->".$errno);
        if(!$fp) {
            $this->SMSError("fsockopen",$errstr."--->".$errno);
            return false;
        }
        //构造post请求的头
        $header = "POST /z_mdsmssend.aspx HTTP/1.1\r\n";
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
        if('success' == $line) {
            return true;
        }
        else {
            $this->SMSError($line,'The return of thirdSMS is wrong!');
            return false;
        }
    }

    /**
     * @brief    sendSMS 发送短信，首先通过第三方接口发送，失败后采用短信猫发送
     *
     * @param    $phone 手机号码
     * @param    $content 短信内容
     *
     * @return   boolean true表示短信发送成功
     */
    public function sendSMS($phone,$content) {
        if(!$this->sendThirdSMS($phone,$content)) {
        //if(true) {
            if(!$this->sendCatSMS($phone,$content)) {
                return false;
            }
        }
        return true;
    }
}
?>
