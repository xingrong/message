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
switch($_GET['sent']) {
    case 0:
        $sent_status = "<font color=\"blue\">待发送</font>";
        break;
    case 1:
        $sent_status = "<font color=\"green\">发送成功</font>";
        break;
    case 2:
        $sent_status = "<font color=\"red\">发送失败</font>";
        break;
    default:
        die("Wrong expression!!!\n");
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
<h4><?php echo $search_time . " " . $sent_status?>的短信状态</h4>
<hr />
<div class="divLeft">
    <input type="button" value="返回首页" onClick="window.location = 'http://message.goso.cn/sms/status.php?get_time=<?php echo $get_time;?>'" value="" />
</div>
<?php
require_once(__DIR__ . "/conf/mysqlInfo.php");
$con = mysql_connect($mysqlInfo['host'],$mysqlInfo['username'],$mysqlInfo['password']);
if(!$con) {
    die('Could not connect: ' . mysql_error());
}
mysql_select_db($mysqlInfo['database'],$con);
$sql_select = "SELECT username,phone,time,content FROM smsinfo WHERE is_sent=\"" . $_GET['sent'] . "\" AND datediff('" . $search_time . "', time)=0 ORDER BY time DESC"; 
$result = mysql_query($sql_select);

echo "<table border='1'>
    <tr>
    <th class=\"thId\">序号</th>
    <th class=\"thUsername\">用户名</th>
    <th class=\"thPhone\">手机号</th>
    <th class=\"thTime\">时间</th>
    <th class=\"thContent\">短信内容</th>
    </tr>";
$count = 0;
while($row = mysql_fetch_array($result)) {
    ++$count;
    echo "<tr>";
    echo "<td>" . $count . "</td>";
    echo "<td>" . $row['username'] . "</td>";
    echo "<td>" . $row['phone'] . "</td>";
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
