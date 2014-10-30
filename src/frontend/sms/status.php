<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link href="css/main.css" rel="stylesheet" type="text/css"/>
<link href="css/backToTop.css" rel="stylesheet" type="text/css"/>
<title>通知中心短信服务</title>
</head>
<script language="JavaScript">
function myrefresh()
{
    window.location.reload();
}
setTimeout('myrefresh()',60000);
</script>
<body>

<script type="text/javascript" src="js/jquery-1.7.min.js"></script>
<script type="text/javascript" src="js/backToTop.js"></script>
<script type="text/javascript" src="js/clock.js"></script>

<div class="divMain">

<h1>通知中心短信服务</h1>
<hr />
<?php
if(empty($_GET['get_time'])) {
    $get_time = time();
}
else {
    $get_time = $_GET['get_time'];
}
$array_time = getdate($get_time);
$search_time = $array_time['year'] . "-" . $array_time['mon'] . "-" . $array_time['mday'];
require_once(__DIR__ . "/conf/mysqlInfo.php");
$con = mysql_connect($mysqlInfo['host'],$mysqlInfo['username'],$mysqlInfo['password']);
if(!$con) {
    die('Could not connect: ' . mysql_error());
}
mysql_select_db($mysqlInfo['database'],$con);

//统计信息
$sql_sent_0 = "SELECT count(phone) FROM smsinfo WHERE datediff('" . $search_time . "', time)=0 AND is_sent=0";
$sql_sent_1 = "SELECT count(phone) FROM smsinfo WHERE datediff('" . $search_time . "', time)=0 AND is_sent=1";
$sql_sent_2 = "SELECT count(phone) FROM smsinfo WHERE datediff('" . $search_time . "', time)=0 AND is_sent=2";
$sql_sum_repeat = "SELECT sum(repeat_num) FROM smsinfo WHERE datediff('" . $search_time . "', time)=0";

$queueCountJson = file_get_contents("/var/gs/message/src/sms/queueCount/noticenter_sms_1_1");
$queueCountArray = json_decode($queueCountJson,true);
$queueCount = $queueCountArray['message_count'];

$result_sent_0 = mysql_query($sql_sent_0);
$result_sent_1 = mysql_query($sql_sent_1);
$result_sent_2 = mysql_query($sql_sent_2);
$result_sum_repeat = mysql_query($sql_sum_repeat);

$row_sent_0 = mysql_fetch_array($result_sent_0);
$row_sent_1 = mysql_fetch_array($result_sent_1);
$row_sent_2 = mysql_fetch_array($result_sent_2);
$row_sum_repeat = mysql_fetch_array($result_sum_repeat);
echo "<table>
    <tr>
    <th width=\"570\">日期</th>
    <th width=\"200\"><font color=\"green\">发送成功总数</font></th>
    <th width=\"200\"><font color=\"red\">发送失败总数</font></th>
    <th width=\"200\"><font color=\"blue\">队列等待总数</font></th>
    <th width=\"200\"><font color=\"gray\">过滤短信总数</font></th>
    </tr>";
echo "<tr>";
echo "<td><input type=\"button\" value=\"前一天\" onClick=\"window.location = 'http://message.goso.cn/sms/status.php?get_time=" . strtotime("-1 day",$get_time) . "'\" value=\"\" />
    " . " " .  $search_time . " " . "<input type=\"button\" value=\"后一天\" onClick=\"window.location = 'http://message.goso.cn/sms/status.php?get_time=" . strtotime("+1 day",$get_time) . "'\" value=\"\" />
    <input type=\"button\" value=\"今天\" onClick=\"window.location = 'http://message.goso.cn/sms/status'\" value=\"\" /></td>";
echo "<td>" . $row_sent_1['count(phone)'] . "<a title=\"" . $row_sent_1['count(phone)'] . "\" href=\"searchBySent.php?sent=1&get_time={$get_time}\">  查看</td>";
echo "<td>" . $row_sent_2['count(phone)'] . "<a title=\"" . $row_sent_2['count(phone)'] . "\" href=\"searchBySent.php?sent=2&get_time={$get_time}\">  查看</td>";
echo "<td>" . $queueCount . "</td>";
echo "<td>" . $row_sum_repeat['sum(repeat_num)'] . "<a title=\"" . $row_sum_repeat['sum(repeat_num)'] . "\" href=\"searchByFilter.php?get_time={$get_time}\">  查看</td>";
echo "</tr>";
echo "</table>";

echo "<hr />";
?>
<div class="divLeft">
    <input type="button" value="短信猫" onClick="window.location = 'http://192.168.10.27/show.php?a=a'" value="" />
    <input type="button" value="用户手册" onClick="window.location = 'http://confluence.goso.cn/pages/viewpage.action?pageId=11442975'" value="" />
    <input type="button" value="需求征集" onClick="window.location = 'http://confluence.goso.cn/pages/viewpage.action?pageId=11440687'" value="" />
    <input type="button" value="邮件服务" onClick="window.location = 'http://message.goso.cn/mail/status'" value="" />
</div>
<?php
//具体短信信息
$sql_select = "SELECT username,phone,is_sent,time,content FROM smsinfo WHERE datediff('" . $search_time . "', time)=0 ORDER BY time DESC"; 
$result_select = mysql_query($sql_select);
echo "<table>
    <tr>
    <th class=\"thId\">序号</th>
    <th class=\"thUsername\">用户名</th>
    <th class=\"thPhone\">手机号</th>
    <th class=\"thStatus\">状态</th>
    <th class=\"thTime\">时间</th>
    <th class=\"thContent\">短信内容</th>
    </tr>";
$count = 0;
while($row = mysql_fetch_array($result_select)) {
    ++$count;
    if(0 == $row['is_sent']) {
        $sent_status = "<font color=\"gray\">未发送</font>";
    }
    else if(1 == $row['is_sent']) {
        $sent_status = "<font color=\"green\">已发送</font>";
    }
    else {
        $sent_status = "<font color=\"red\">发送失败</font>";
    }
    echo "<tr>";
    echo "<td>" . $count . "</td>";
    echo "<td>" . $row['username'] . "</td>";
    echo "<td>" . $row['phone'] . "<a title=\"" . $row['phone'] . "\" href=\"searchByPhone.php?username=" . $row['username'] . "&phone=" . $row['phone'] . "&get_time=" . $get_time . "\">  查看</td>";
    echo "<td>" . $sent_status . "</td>";
    echo "<td>" . $row['time'] . "</td>";
    echo "<td>" . $row['content'] . "</td>";
    echo "</tr>";
}
echo "</table>";

mysql_close($con);
?>
</div>
</body>
    </html>
