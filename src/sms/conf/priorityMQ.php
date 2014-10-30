<?php
# Author: xingrong0804@163.com (Xing Rong)

chdir(dirname(__FILE__));

$priorityMQ=array();
$priorityMQ['serverAddress'] = array(                           //serverAddress
    array('host'=>'ip1','port'=>5672),
    array('host'=>'ip2','port'=>5672),
);
$priorityMQ['USER'] = 'noticenter';                             //User name
$priorityMQ['PASS'] = 'noticenter';		                        //User password
$priorityMQ['VHOST'] = '/noticenter';			                //Virtual Host
$priorityMQ['EXCHANGE'] = 'noticenter_exchange';	            //Exchange Name
$priorityMQ['TOPIC'] = array(                                   //Topic
    '1'=>array('noticenter_sms_1_1',),
    '2'=>array('noticenter_sms_1_1',),
);
$priorityMQ['TIMER'] = array(
    '0'=>2, //默认timer
    '1'=>2,
    '2'=>2,
);
?>
