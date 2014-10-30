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

<?php
if(empty($_GET['phone'])) {
    die("手机号码为空！");
}
if(empty($_GET['get_time'])) {
    $get_time = time();
}
else {
    $get_time = $_GET['get_time'];
}
$array_time = getdate($get_time);
$search_time = $array_time['year'] . "-" . $array_time['mon'] . "-" . $array_time['mday'];
?>
<h1>通知中心短信服务</h1>
<hr />
<h4><?php echo $search_time . " 发送给<font color=\"blue\">" . $_GET['username'] . "(" . $_GET['phone'] . ")";?></font>的短信状态</h4>
<hr />
<?php
require_once(__DIR__ . "/conf/mysqlInfo.php");
$con = mysql_connect($mysqlInfo['host'],$mysqlInfo['username'],$mysqlInfo['password']);
if(!$con) {
    die('Could not connect: ' . mysql_error());
}
mysql_select_db($mysqlInfo['database'],$con);
//统计信息
$sql_sent_0 = "SELECT count(phone) FROM smsinfo WHERE datediff('" . $search_time . "', time)=0 AND is_sent=0 AND phone=\"" . $_GET['phone'] . "\"";
$sql_sent_1 = "SELECT count(phone) FROM smsinfo WHERE datediff('" . $search_time . "', time)=0 AND is_sent=1 AND phone=\"" . $_GET['phone'] . "\"";
$sql_sent_2 = "SELECT count(phone) FROM smsinfo WHERE datediff('" . $search_time . "', time)=0 AND is_sent=2 AND phone=\"" . $_GET['phone'] . "\"";
$sql_sum_repeat = "SELECT sum(repeat_num) FROM smsinfo WHERE datediff('" . $search_time . "', time)=0 AND phone=\"" . $_GET['phone'] . "\"";

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
    <th width=\"6%\">日期</th>
    <th width=\"10%\"><font color=\"green\">发送成功数目</font></th>
    <th width=\"10%\"><font color=\"red\">发送失败数目</font></th>
    <th width=\"10%\"><font color=\"gray\">过滤重复短信数目</font></th>
    </tr>";
echo "<tr>";
echo "<td>" . $search_time . "</td>";
echo "<td>" . $row_sent_1['count(phone)'] . "</td>";
echo "<td>" . $row_sent_2['count(phone)'] . "</td>";
echo "<td>" . $row_sum_repeat['sum(repeat_num)'] . "</td>";
echo "</tr>";
echo "</table>";

echo "<hr />";
?>
<div class="divLeft">
    <input type="button" value="返回首页" onClick="window.location = 'http://message.cn/sms/status.php?get_time=<?php echo $get_time;?>'" value="" />
</div>
<?php
//短信信息
$sql_select = "SELECT username,phone,is_sent,time,content FROM smsinfo WHERE phone=\"" . $_GET['phone'] . "\" AND datediff('" . $search_time . "', time)=0 ORDER BY time DESC"; 
$result = mysql_query($sql_select);

echo "<table border='1'>
    <tr>
    <th class=\"thId\">序号</th>
    <th class=\"thUsername\">用户名</th>
    <th class=\"thPhone\">手机号</th>
    <th class=\"thStatus\">状态</th>
    <th class=\"thTime\">时间</th>
    <th class=\"thContent\">短信内容</th>
    </tr>";
$count = 0;
while($row = mysql_fetch_array($result)) {
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
    echo "<td>" . $row['phone'] . "</td>";
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
