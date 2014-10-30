<?php
$str = $argv[1];
echo $str."\n";
$res = isContentLenValid(&$str);
echo $str."\n";
echo mb_strlen($str,'utf-8')."\n";

function isContentLenValid(&$content) {
    $contentLen = mb_strlen($content,'utf-8');
    echo $contentLen."\n";
    if(preg_match("/[\x7f-\xff]/", $content)) { //含中文字符，超过134字符截断
        if($contentLen > 134) {
            $content = mb_substr($content,0,134,'utf-8');
            return false;
        }
        return true;
    }
    else { //纯英文字符，超过160字符截断
        if($contentLen > 160) {
            $content = mb_substr($content,0,160,'utf-8');
            return false;
        }
        return true;
    }
}
