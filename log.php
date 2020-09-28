<?php
function write_log($content)
{
    $file  = 'log';//要写入文件的文件名（可以是任意文件名），如果文件不存在，将会创建一个
    file_put_contents($file, strftime(@$_SERVER['REMOTE_HOST']."%Y-%m-%d %H:%M:%S",time()).": ".$content."\n",FILE_APPEND);
}
?>