<?php
# Author: xingrong0804@163.com (Xing Rong)

chdir(dirname(__FILE__));

require_once '/var/gs/message/src/base/Monitor.php';
require_once '/var/gs/message/src/base/conf/centerConf.php';

$monitor = new Monitor();

$postData = array(
    'feedback'=>'xingrong0804@163.com',
    'phone'=>'13512341234',
    'content'=>'test',
);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://message.ip.cn/sms');
curl_setopt($ch, CURLOPT_HTTPHEADER,array('Host:message.cn'));
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
$curlRet = curl_exec($ch);

$messageRet = json_decode($curlRet,true);
if('00000' != $messageRet['status'] || false === $curlRet) {
    $monitor->smsSimulationAlarm($GLOBALS['CENTER_IP'],"smsSimulation(message.ip.cn)failed: ".$curlRet);
}
?>
