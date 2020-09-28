<?php
date_default_timezone_set('Asia/Shanghai');//'Asia/Shanghai'   亚洲/上海
require_once "log.php";
require_once "config.php";

$data = $_GET;
$server_id = $data['server_id'];
$notify_url = urldecode($data['notify_url']);
$key = $data['key'];
if($key != "LFwfweigaNWfwszl44dfwe6234KFwLVVwBWSe"){
    write_log("invalid key=".$key);
    echo "failed";
    return;
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PWD, DB_NAME);
write_log("sync notify url connect db success ");
if ($conn->connect_error) {
    write_log("Connection failed: " . $conn->connect_error);
    echo "failed";
    return;
}

$query = $conn->prepare("replace into server_notify_lists(server_id,notify_url) values(?,?)");
$query->bind_param("ss", $server_id, $notify_url);
$ret = $query->execute();
write_log(json_encode($query->error));
if ($query->error == ""){
    echo "success";
    return;
}
echo "failed";
?>