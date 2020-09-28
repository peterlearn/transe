<?php
error_reporting(E_ERROR);

require_once "log.php";
require_once "config.php";
require_once "./example/WxPay.Config.php";

$data = $_GET;
$openid = $data['openid'];

write_log("openid = ".$openid);
$conn = new mysqli(DB_HOST, DB_USER, DB_PWD, DB_NAME);
if ($conn->connect_error) {
    write_log("Connection failed: " . $conn->connect_error);
    return;
}

$skey = "VwsewfwJFKWFwefkwgawefgLKFWfwgegwe234LFwg";
$query = $conn->prepare("select order_id,money from prepay where openid=? order by time desc limit 1");
$query->bind_param("s", $openid);
$query->execute();
$query->store_result();
$query->bind_result($order_id, $money);

while($query->fetch()){
    // $ret = "https://jsapi.sxwj.1cent.xyz/bizpay/index.html?role_id=".$role_id."&server_id=".$server_id."&type=".$type."&type_key=".$type_key."&money=".$money;
    $sign = md5($order_id.$skey.$money);
    $url = "https://jsapi.sxwj.1cent.xyz/bizpay/auth.php?order_id=".$order_id."&mdata=".$money."&s=".$sign;
    $config = new WxPayConfig();
    $appid = $config->GetAppId();
    $ret =  "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$appid."&mdata=".$money."&redirect_uri=".urlencode($url)."&response_type=code&scope=snsapi_base&state=123#wechat_redirect";
    echo $ret;
    return;
}
?>
