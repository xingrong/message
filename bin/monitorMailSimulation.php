<?php
# Author: xingrong0804@163.com (Xing Rong)

chdir(dirname(__FILE__));

require_once '/var/gs/message/src/base/Monitor.php';
require_once '/var/gs/message/src/base/conf/centerConf.php';

$monitor = new Monitor();

$postData = array(
    'from'=>'xingrong0804@163.com',
    'to'=>'xingrong0804@163.com',
    'subject'=>'test',
    'body'=>'test',
);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://message.ip.cn/mail');
curl_setopt($ch, CURLOPT_HTTPHEADER,array('Host:message.test.cn'));
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
$curlRet = curl_exec($ch);

$messageRet = json_decode($curlRet,true);
if('00000' != $messageRet['status'] || false === $curlRet) {
    $monitor->mailSimulationAlarm($GLOBALS['CENTER_IP'],"mailSimulation(message.ip.cn)failed: ".$curlRet);
}
?>
