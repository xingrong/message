<?php
# Copyright 2012 Jike.com Inc. All Rights Reserved.
# Author: xingrong@jike.com (Xing Rong)

chdir(dirname(__FILE__));

require_once(__DIR__ . '/conf/version.php');

//newest version
global $version;

//return
system("echo \"Your version of logtool is \033[0;31;1m\"".$_POST['version']."\033[0m");
if($version != $_POST['version']) {
    //low
    system("echo \"Sorry!\"");
    system("echo \"Your version of logtool is low!\"");
    system("echo \"Please download the newest version from\"");
    system("echo \"http://confluence.goso.cn/pages/viewpage.action?pageId=12602868\"");
}
else {
    system("echo \"Congratulations!\"");
    system("echo \"Your logtool is the newest version!\"");
}
