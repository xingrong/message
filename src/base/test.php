<?php
$tmp = $argv[1];
isContentLenValid($tmp);
echo $tmp."\n";
echo mb_strlen($tmp,'utf-8')."\n";
function isContentLenValid(&$content) {
    $contentLen = mb_strlen($content,'utf-8');
    echo $contentLen."\n";
    $chCut = 128;
    $enCut = 154;
    if(preg_match("/[\x7f-\xff]/", $content)) { //含中文字符，超过134字符截断
        if($contentLen > $chCut) { //考虑[JIKE]
            $content = mb_substr($content,0,$chCut,'utf-8');
            return false;
        }
        return true;
    }
    else { //纯英文字符，超过160字符截断
        if($contentLen > $enCut) { //考虑[JIKE]
            $content = mb_substr($content,0,$enCut,'utf-8');
            return false;
        }
        return true;
    }
}
?>
