##干什么用？
提供RESTful接口，通过163邮件系统发送邮件。
* 监控邮件发送，异常自动报警;
* 可以自定义过滤策略;
* 提供优先级控制发送;

##怎么用？
###SHELL
####通知中心邮件服务SHELL示例
#####//没有附件
```Shell
curl -d 'from=xingrong@163.com' -d 'to=xingrong@163.com' -d 'subject=test' -d 'body=<body>test</body>' http://message.cn/mail
```
#####//有附件
```Shell
curl -F 'from=xingrong@163.com' -F 'to=xingrong@163.com;test@163.com' -F 'cc=xingrong@163.com' -F 'subject=test for mailservice!' -F 'attachments_1=@/home/xingrong/ymake' -F 'attachments_2=@/home/xingrong/test' -F 'body=@/home/xingrong/test' http://message.cn/mail
```
>示例仅供参考，对curl命令使用有疑问，可访问 http://curl.haxx.se/ 获得帮助

###PHP
####邮件服务PHP示例
```php
<?php
$post_data = array(
    'from' => 'xingrong@163.com',
    'to' => 'xingrong@163.com',
    'subject' => 'test',
    'body' => '<body>test</body>',
    );
$mcURL = "http://message.cn/mail";
$ch = curl_init();
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_URL, $mcURL);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
$curlRet = curl_exec($ch);
curl_close($ch);
if(false === $curlRet) {
    //TODO
}
echo $curlRet;
//TODO $curlRet with jsondecode()
?>
```

###Python
####通知中心邮件服务Python示例
```python
import urllib
import urllib2
import httplib
def sendMail():
    post_data = urllib.urlencode({
      'from' : 'xingrong@163.com',
      'to' : 'xingrong@163.com',
      'subject' : 'test',
      'body' : '<body>test</body>',
      })
    request = urllib2.Request("http://message.cn/mail", post_data)
    ret = urllib2.urlopen(request)
    print ret.read()
if __name__ == "__main__":
    sendMail()
```

##有哪些参数？
参数|是否必选|参数说明
----|----|----
priority|false|优先级，默认为2，目前可选择1和2
filter|false|自定义过滤策略，默认为0
from|**true**|发件人
to|**true**|收件人列表，使用分号隔开
cc|false|抄送列表，使用分号隔开
bcc|false|暗送列表，使用分号隔开
subject|false|邮件主题
body|**true**|邮件正文，支持文件导入
附件|false|根据input name区别附件名，支持多附件发送

##返回什么？
###返回示例（JSON格式）：
```json
{

    "status":"10204",

    "msg":"Parameter from's value invalid!"

}
```
status和msg的变量类型为**string**

###返回对照表
####成功返回
status|msg|返回说明
----|----|----
"00000"|"Mail Send Success!"|邮件发送成功

####服务级错误返回
返回代码|返回信息|详细描述
----|----|----
"10103"|"To parameter is null!"|to参数为空
"10104"|"Body parameter is null!"|body参数为空
"10105"|"From parameter is null!"|from参数为空
"10202"|"Parameter priority's value invalid!"|priority参数非法
"10204"|"Parameter from's value invalid!"|from参数非法
"10205"|"Parameter to's value invalid!"|to参数非法
"10206"|"Parameter cc's value invalid!"|cc参数非法
"10207"|"Parameter bcc's value invalid!"|bcc参数非法
"10301"|"Attachment invalid!"|附件非文件
"10401"|"Recipients unreachable: xxx"|邮件不可达
"10402"|"BlackList: xxx"|黑名单

####系统级错误返回
返回代码|返回信息|详细描述
----|----|----
"30101"|"Open body file failed!"|body文件打开失败
"30102"|"Save body file failed!"|body文件保存失败
"30103"|"Save attachment failed!"|附件保存失败
"30201"|"Message Send Failed!"|消息发送失败

##还有什么？
* 附件的input name不可设为body；
* 邮件正文文件不能为空文件，导入邮件正文功能不支持多文件合并导入；
* 按照php的默认设置，上传文件总量的上限为2M；
* 按照RabbitMQ Client的默认设置，post data总量的上限为128K，上传文件除外;
