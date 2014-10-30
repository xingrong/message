<?php
# Copyright 2012 Jike.com Inc. All Rights Reserved.
# Author: xingrong@jike.com (Xing Rong)

chdir(dirname(__FILE__));

require_once __DIR__ . '/Monitor.php';
require_once __DIR__ . '/conf/centerConf.php';

$monitor = new Monitor();
$monitor->receiveSMSProcessAlarm($GLOBALS['CENTER_IP'],$argv[1]);
?>
