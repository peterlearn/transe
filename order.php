<?php
include_once("log.php");
include_once("config.php");

$key = "VWegweNCZBCSwiyerwoe*233()$$^2awiegawef23FFWf";
$data = $_GET;

if($key = $data['key']){
    write_log("invalid request");
    return;
}

$orderid = $data['orderid'];
$role_id = $data['role_id'];
$server_id = $data['server_id'];
$notify_url = $data['notify_url'];



?>