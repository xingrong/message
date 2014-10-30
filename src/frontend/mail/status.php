<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link href="css/main.css" rel="stylesheet" type="text/css"/>
<link href="css/backToTop.css" rel="stylesheet" type="text/css"/>
<title>通知中心邮件服务</title>
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

<h1>通知中心邮件服务</h1>
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
$sql_sent_0 = "SELECT count(id) FROM mailinfo WHERE datediff('" . $search_time . "', time)=0 AND is_sent=0";
$sql_sent_1 = "SELECT count(id) FROM mailinfo WHERE datediff('" . $search_time . "', time)=0 AND is_sent=1";
$sql_sent_2 = "SELECT count(id) FROM mailinfo WHERE datediff('" . $search_time . "', time)=0 AND is_sent=2";

$queueCountJson = file_get_contents("/var/gs/message/src/mail/queueCount/noticenter_mail_1_1");
$queueCountArray = json_decode($queueCountJson,true);
$queueCount = $queueCountArray['message_count'];

$result_sent_0 = mysql_query($sql_sent_0);
$result_sent_1 = mysql_query($sql_sent_1);
$result_sent_2 = mysql_query($sql_sent_2);

$row_sent_0 = mysql_fetch_array($result_sent_0);
$row_sent_1 = mysql_fetch_array($result_sent_1);
$row_sent_2 = mysql_fetch_array($result_sent_2);
echo "<table>
    <tr>
    <th width=\"570\">日期</th>
    <th width=\"200\"><font color=\"green\">发送成功总数</font></th>
    <th width=\"200\"><font color=\"red\">发送失败总数</font></th>
    <th width=\"200\"><font color=\"blue\">队列等待总数</font></th>
    </tr>";
echo "<tr>";
echo "<td><input type=\"button\" value=\"前一天\" onClick=\"window.location = 'http://message.cn/mail/status.php?get_time=" . strtotime("-1 day",$get_time) . "'\" value=\"\" />
    " . " " .  $search_time . " " . "<input type=\"button\" value=\"后一天\" onClick=\"window.location = 'http://message.cn/mail/status.php?get_time=" . strtotime("+1 day",$get_time) . "'\" value=\"\" />
    <input type=\"button\" value=\"今天\" onClick=\"window.location = 'http://message.cn/mail/status'\" value=\"\" /></td>";
echo "<td>" . $row_sent_1['count(id)'] . "<a title=\"" . $row_sent_2['count(id)'] . "\" href=\"searchBySent.php?sent=1&get_time={$get_time}\">  查看</td>";
echo "<td>" . $row_sent_2['count(id)'] . "<a title=\"" . $row_sent_2['count(id)'] . "\" href=\"searchBySent.php?sent=2&get_time={$get_time}\">  查看</td>";
echo "<td>" . $queueCount . "</td>";
echo "</tr>";
echo "</table>";

echo "<hr />";
?>

<div class="divLeft">
    <input type="button" value="用户手册" onClick="window.location = 'http://confluence.cn/pages/viewpage.action?pageId=11442972'" value="" />
    <input type="button" value="需求征集" onClick="window.location = 'http://confluence.cn/pages/viewpage.action?pageId=11440687'" value="" />
    <input type="button" value="短信服务" onClick="window.location = 'http://message.cn/sms/status'" value="" />
</div>

<?php
//具体短信信息
$sql_select = "SELECT mailfrom,mailto,is_sent,time,subject FROM mailinfo WHERE datediff('" . $search_time . "', time)=0 ORDER BY time DESC"; 
$result_select = mysql_query($sql_select);
echo "<table>
    <tr>
    <th class=\"thId\">序号</th>
    <th class=\"thUsername\">发件人</th>
    <th class=\"thPhone\">收件人</th>
    <th class=\"thStatus\">状态</th>
    <th class=\"thTime\">时间</th>
    <th class=\"thContent\">邮件主题</th>
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
    echo "<td>" . $row['mailfrom'] . "</td>";
    echo "<td>" . $row['mailto'] . "</td>";
    echo "<td>" . $sent_status . "</td>";
    echo "<td>" . $row['time'] . "</td>";
    echo "<td>" . htmlspecialchars($row['subject']) . "</td>";
    echo "</tr>";
}
echo "</table>";

mysql_close($con);
?>

</div>
</body>
    </html>
