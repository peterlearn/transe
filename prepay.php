<?php
date_default_timezone_set('Asia/Shanghai');//'Asia/Shanghai'   亚洲/上海
require_once "log.php";
require_once "config.php";
require_once "common.php";

$data = $_GET;
write_log("request ".json_encode($data));

$role_id = $data['role_id'];
$server_id = $data['server_id'];
$type = $data['type'];
$type_key = $data['type_key'];
$money = $data['money'];
// $openid = $data['openid'];
$key = "fsdlfGwfgkGBwgljl234LFwgwfwgll";
$conn = new mysqli(DB_HOST, DB_USER, DB_PWD, DB_NAME);
if ($conn->connect_error) {
    write_log("Connection failed: " . $conn->connect_error);
    return;
}

$query = $conn->prepare("select notify_url from server_notify_lists where server_id=?");
$query->bind_param("s", $server_id);
$query->execute();
$query->store_result();
$query->bind_result($notify_url);

$url = "order";
while($query->fetch()){
    $url = $notify_url.$url;
    break;
}

$url = $url."?role_id=".$role_id."&type=".$type."&type_key=".$type_key."&money=".$money."&key=".$key;
write_log("url=".$url);
$ret = json_decode(http_get($url), true);
if($ret['ret'] != "ok"){
    echo "failed";
    return;
}

$openid = $ret['openid'];
$query = $conn->prepare("insert into prepay(role_id,order_id,server_id,`type`,`type_key`,money,openid) values(?,?,?,?,?,?,?)");
$query->bind_param("sssssss", $role_id, $ret['order_id'], $server_id, $type, $type_key, $money, $openid);
$ret = $query->execute();
if ($query->error == "")
{
    echo "success";
}else{
    write_log(json_encode($query->error));
    echo "failed";
}
?>