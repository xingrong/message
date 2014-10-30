<?php
# Author: xingrong0804@163.com (Xing Rong)

chdir(dirname(__FILE__));

require_once __DIR__ . '/Monitor.php';
require_once __DIR__ . '/conf/centerConf.php';

$monitor = new Monitor();
$monitor->receiveMailProcessAlarm($GLOBALS['CENTER_IP'],$argv[1]);
?>
