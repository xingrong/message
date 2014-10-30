<?php
# Author: xingrong0804@163.com (Xing Rong)

chdir(dirname(__FILE__));

require_once("/var/gs/message/src/base/conf/thirdSMS.php");
global $thirdSMS;

$flag = 0;
//要post的数据 
$argv = array(
    'sn' => $thirdSMS['thirdSN'], //提供的账号
    'pwd' => $thirdSMS['thirdPWD'], //此处密码需要加密 加密方式为 md5(sn+password) 32位大写
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
echo $params;
$length = strlen($params);
//创建socket连接
$fp = fsockopen("sdk2.test.cn",80,$errno,$errstr,10) or exit($errstr."--->".$errno);
//构造post请求的头
$header = "POST /webservice.asmx/GetBalance HTTP/1.1\r\n";
$header .= "Host:sdk2.test.cn\r\n";
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
echo $line;
file_put_contents(__DIR__ . '/getBalance.xml',$line);
//余额报警
$xmlDoc = new DOMDocument();
$xmlDoc->load(__DIR__ . '/getBalance.xml');
$balanceArray = $xmlDoc->getElementsByTagName( "string" );
$balanceStr = $balanceArray->item(0)->nodeValue;
$balanceInt = intval($balanceStr);
if($balanceInt > $balanceThreshold || $balanceInt < 1) {
}
else {
    //$monitor->balanceAlarm($balanceInt);
}
?>
