<h2>干什么用？</h2>

提供RESTful接口，通过163邮件系统发送邮件。

1.监控邮件发送，异常自动报警;

2.可以自定义过滤策略;

3.提供优先级控制发送;

<h2>怎么用？</h2>

<h3>SHELL</h3>

通知中心邮件服务SHELL示例

//没有附件

curl -d 'from=xingrong@163.com' -d 'to=xingrong@163.com' -d 'subject=test' -d 'body=<body>test</body>' http://message.cn/mail

<br />
//有附件

curl -F 'from=xingrong@163.com' -F 'to=xingrong@163.com;test@163.com' -F 'cc=xingrong@163.com' -F 'subject=test for mailservice!' -F 'attachments_1=@/home/xingrong/ymake' -F 'attachments_2=@/home/xingrong/test' -F 'body=@/home/xingrong/test' http://message.cn/mail


示例仅供参考，对curl命令使用有疑问，可访问http://curl.haxx.se/获得帮助

<h3>PHP</h3>

邮件服务PHP示例

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

<h3>Python</h3>

通知中心邮件服务Python示例

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

<h2>有哪些参数？</h2>

<table class="confluenceTable">
<tbody>
<tr>
<th class="confluenceTh">参数</th>
<th class="confluenceTh">是否必选</th>
<th class="confluenceTh">参数说明</th>
</tr>
<tr>
<td colspan="1" class="confluenceTd">priority</td>
<td colspan="1" class="confluenceTd">false</td>
<td colspan="1" class="confluenceTd">优先级，默认为2，目前可选择1和2</td>
</tr>
<tr>
<td colspan="1" class="confluenceTd">filter</td>
<td colspan="1" class="confluenceTd">false</td>
<td colspan="1" class="confluenceTd">自定义过滤策略，默认为0</td>
</tr>
<tr>
<td class="confluenceTd">from</td>
<td class="confluenceTd"><span style="color: rgb(255,0,0);">true</span></td>
<td class="confluenceTd">发件人</td>
</tr>
<tr>
<td colspan="1" class="confluenceTd">to</td>
<td colspan="1" class="confluenceTd"><span style="color: rgb(255,0,0);">true</span></td>
<td colspan="1" class="confluenceTd">收件人列表，使用分号隔开</td>
</tr>
<tr>
<td colspan="1" class="confluenceTd">cc</td>
<td colspan="1" class="confluenceTd">false</td>
<td colspan="1" class="confluenceTd">抄送列表，使用分号隔开</td>
</tr>
<tr>
<td colspan="1" class="confluenceTd">bcc</td>
<td colspan="1" class="confluenceTd">false</td>
<td colspan="1" class="confluenceTd">暗送列表，使用分号隔开</td>
</tr>
<tr>
<td colspan="1" class="confluenceTd">subject</td>
<td colspan="1" class="confluenceTd">false</td>
<td colspan="1" class="confluenceTd">邮件主题</td>
</tr>
<tr>
<td colspan="1" class="confluenceTd">body</td>
<td colspan="1" class="confluenceTd"><span style="color: rgb(255,0,0);">true</span></td>
<td colspan="1" class="confluenceTd">邮件正文，支持文件导入</td>
</tr>
<tr>
<td colspan="1" class="confluenceTd">附件</td>
<td colspan="1" class="confluenceTd">false</td>
<td colspan="1" class="confluenceTd">根据input name区别附件名，支持多附件发送</td>
</tr>
</tbody>
</table>

<h2>返回什么？</h2>

返回示例（JSON格式）：

{

    "status":"10204",

    "msg":"Parameter from's value invalid!"

}

status和msg的变量类型为string

<h3>返回对照表</h3>

<h4>成功返回</h4>

<table class="confluenceTable">
<tbody>
<tr>
<th class="confluenceTh">status</th>
<th class="confluenceTh">msg</th>
<th class="confluenceTh">返回说明</th>
</tr>
<tr>
<td class="confluenceTd">&quot;00000&quot;</td>
<td class="confluenceTd">&quot;Mail Send Success!&quot;</td>
<td class="confluenceTd">邮件发送成功</td>
</tr>
</tbody>
</table>

<h4>服务级错误返回</h4>

<table class="confluenceTable">
<tbody>
<tr>
<th class="confluenceTh">返回代码</th>
<th class="confluenceTh">返回信息</th>
<th class="confluenceTh">详细描述</th>
</tr>
<tr>
<td class="confluenceTd">&quot;10103&quot;</td>
<td class="confluenceTd">&quot;To parameter is null!&quot;</td>
<td class="confluenceTd">to参数为空</td>
</tr>
<tr>
<td colspan="1" class="confluenceTd">&quot;10104&quot;</td>
<td colspan="1" class="confluenceTd">&quot;Body parameter is null!&quot;</td>
<td colspan="1" class="confluenceTd">body参数为空</td>
</tr>
<tr>
<td colspan="1" class="confluenceTd">&quot;10105&quot;</td>
<td colspan="1" class="confluenceTd">&quot;From parameter is null!&quot;</td>
<td colspan="1" class="confluenceTd">from参数为空</td>
</tr>
<tr>
<td colspan="1" class="confluenceTd">&quot;10202&quot;</td>
<td colspan="1" class="confluenceTd">&quot;Parameter priority's value invalid!&quot;</td>
<td colspan="1" class="confluenceTd">priority参数非法</td>
</tr>
<tr>
<td colspan="1" class="confluenceTd">&quot;10204&quot;</td>
<td colspan="1" class="confluenceTd">&quot;Parameter from's value invalid!&quot;</td>
<td colspan="1" class="confluenceTd">from参数非法</td>
</tr>
<tr>
<td colspan="1" class="confluenceTd">&quot;10205&quot;</td>
<td colspan="1" class="confluenceTd">&quot;Parameter to's value invalid!&quot;</td>
<td colspan="1" class="confluenceTd">to参数非法</td>
</tr>
<tr>
<td colspan="1" class="confluenceTd">&quot;10206&quot;</td>
<td colspan="1" class="confluenceTd">&quot;Parameter cc's value invalid!&quot;</td>
<td colspan="1" class="confluenceTd">cc参数非法</td>
</tr>
<tr>
<td colspan="1" class="confluenceTd">&quot;10207&quot;</td>
<td colspan="1" class="confluenceTd">&quot;Parameter bcc's value invalid!&quot;</td>
<td colspan="1" class="confluenceTd">bcc参数非法</td>
</tr>
<tr>
<td colspan="1" class="confluenceTd">&quot;10301&quot;</td>
<td colspan="1" class="confluenceTd">&quot;Attachment invalid!&quot;</td>
<td colspan="1" class="confluenceTd">附件非文件</td>
</tr>
<tr>
<td colspan="1" class="confluenceTd">&quot;10401&quot;</td>
<td colspan="1" class="confluenceTd">&quot;Recipients unreachable: xxx&quot;</td>
<td colspan="1" class="confluenceTd">邮件不可达</td>
</tr>
<tr>
<td colspan="1" class="confluenceTd">&quot;10402&quot;</td>
<td colspan="1" class="confluenceTd">&quot;BlackList: xxx&quot;</td>
<td colspan="1" class="confluenceTd">黑名单</td>
</tr>
</tbody>
</table> 

<h4>系统级错误返回</h4>

<table class="confluenceTable">
<tbody>
<tr>
<th class="confluenceTh">返回代码</th>
<th class="confluenceTh">返回信息</th>
<th class="confluenceTh">详细描述</th>
</tr>
<tr>
<td class="confluenceTd">&quot;30101&quot;</td>
<td class="confluenceTd">Open body file failed!</td>
<td class="confluenceTd">body文件打开失败</td>
</tr>
<tr>
<td colspan="1" class="confluenceTd">&quot;30102&quot;</td>
<td colspan="1" class="confluenceTd">Save body file failed!</td>
<td colspan="1" class="confluenceTd">body文件保存失败</td>
</tr>
<tr>
<td colspan="1" class="confluenceTd">&quot;30103&quot;</td>
<td colspan="1" class="confluenceTd">Save attachment failed!</td>
<td colspan="1" class="confluenceTd">附件保存失败</td>
</tr>
<tr>
<td colspan="1" class="confluenceTd">&quot;30201&quot;</td>
<td colspan="1" class="confluenceTd">Message Send Failed!</td>
<td colspan="1" class="confluenceTd">消息发送失败</td>
</tr>
</tbody>
</table>

<h2>还有什么？</h2>

1.附件的input name不可设为body；

2.邮件正文文件不能为空文件，导入邮件正文功能不支持多文件合并导入；

3.按照php的默认设置，上传文件总量的上限为2M；

4.按照RabbitMQ Client的默认设置，post data总量的上限为128K，上传文件除外;
