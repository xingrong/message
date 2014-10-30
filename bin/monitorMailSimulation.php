<?php
# Copyright 2012 Jike.com Inc. All Rights Reserved.
# Author: xingrong@jike.com (Xing Rong)

chdir(dirname(__FILE__));

require_once '/var/gs/message/src/base/Monitor.php';
require_once '/var/gs/message/src/base/conf/centerConf.php';

$monitor = new Monitor();

$postData = array(
    'from'=>'xingrong@jike.com',
    'to'=>'xingrong@jike.com',
    'subject'=>'test',
    'body'=>'test',
);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://10.100.66.4/mail');
curl_setopt($ch, CURLOPT_HTTPHEADER,array('Host:message.goso.cn'));
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
$curlRet = curl_exec($ch);

$messageRet = json_decode($curlRet,true);
if('00000' != $messageRet['status'] || false === $curlRet) {
    $monitor->mailSimulationAlarm($GLOBALS['CENTER_IP'],"mailSimulation(10.100.66.4)failed: ".$curlRet);
}
?>
