<?php
/**
*
* example目录下为简单的支付样例，仅能用于搭建快速体验微信支付使用
* 样例的作用仅限于指导如何使用sdk，在安全上面仅做了简单处理， 复制使用样例代码时请慎重
* 请勿直接直接使用样例对外提供服务
* 
**/
date_default_timezone_set('Asia/Shanghai');//'Asia/Shanghai'   亚洲/上海
require_once "./lib/WxPay.Api.php";
require_once './lib/WxPay.Notify.php';
require_once "./example/WxPay.Config.php";
require_once 'log.php';
require_once "config.php";
require_once "common.php";

// //初始化日志
// $logHandler= new CLogFileHandler("../logs/".date('Y-m-d').'.log');
// $log = Log::Init($logHandler, 15);

class PayNotifyCallBack extends WxPayNotify
{
	//查询订单
	public function Queryorder($transaction_id)
	{
		$input = new WxPayOrderQuery();
		$input->SetTransaction_id($transaction_id);

		$config = new WxPayConfig();
		$result = WxPayApi::orderQuery($config, $input);
		write_log("query:" . json_encode($result));
		if(array_key_exists("return_code", $result)
			&& array_key_exists("result_code", $result)
			&& $result["return_code"] == "SUCCESS"
			&& $result["result_code"] == "SUCCESS")
		{
			return true;
		}
		return false;
	}

	/**
	*
	* 回包前的回调方法
	* 业务可以继承该方法，打印日志方便定位
	* @param string $xmlData 返回的xml参数
	*
	**/
	public function LogAfterProcess($xmlData)
	{
		write_log("call back， return xml:" . $xmlData);
		return;
	}
	
	//重写回调处理函数
	/**
	 * @param WxPayNotifyResults $data 回调解释出的参数
	 * @param WxPayConfigInterface $config
	 * @param string $msg 如果回调处理失败，可以将错误信息输出到该方法
	 * @return true回调出来完成不需要继续回调，false回调处理未完成需要继续回调
	 */
	public function NotifyProcess($objData, $config, &$msg)
	{
		$data = $objData->GetValues();
		//TODO 1、进行参数校验
		if(!array_key_exists("return_code", $data) 
			||(array_key_exists("return_code", $data) && $data['return_code'] != "SUCCESS")) {
			//TODO失败,不是支付成功的通知
			//如果有需要可以做失败时候的一些清理处理，并且做一些监控
			$msg = "异常异常";
			return false;
		}
		if(!array_key_exists("transaction_id", $data)){
			$msg = "输入参数不正确";
			return false;
		}

		//TODO 2、进行签名验证
		try {
			$checkResult = $objData->CheckSign($config);
			if($checkResult == false){
				//签名错误
				write_log("签名错误...");
				return false;
			}
		} catch(Exception $e) {
			write_log(json_encode($e));
		}

		//TODO 3、处理业务逻辑
		write_log("call back:" . json_encode($data));
		if($this->notify_order($data) != true){
			return false;
		}

		// $notfiyOutput = array();
		//查询订单，判断订单真实性
		if(!$this->Queryorder($data["transaction_id"])){
			$msg = "订单查询失败";
			return false;
		}
		return true;
	}

	private function notify_order($data)
	{
		$key = "fsdlfGwfgkGBwgljl234LFwgwfwgll";
		$conn = new mysqli(DB_HOST, DB_USER, DB_PWD, DB_NAME);
		if ($conn->connect_error) {
			write_log("Connection failed: " . $conn->connect_error);
			return;
		}
		
		$query = $conn->prepare("select server_id from prepay where order_id=?");
		$query->bind_param("s", $data['out_trade_no']);
		$query->execute();
		$query->store_result();
		$query->bind_result($server_id);
		if($query->fetch()){
			$sid = $server_id;
		}else{
			write_log("prepay order not exist data=".json_encode($data));
			return false;
		}

		$query = $conn->prepare("select notify_url from server_notify_lists where server_id=?");
		$query->bind_param("s", $sid);
		$query->execute();
		$query->store_result();
		$query->bind_result($notify_url);
		
		$url = "pay_notify";
		if($query->fetch()){
			$url = $notify_url.$url;
		}else{
			write_log("server url not exist data=".json_encode($data));
			return false;
		}
		
		// $callback_data = json_encode($data);
		$money = intval($data['cash_fee']) / 100;
		$url = $url."?out_order_no=".$data['out_trade_no']."&order_no=".$data['transaction_id']."&pay_time=".$data['time_end']."&amount=".strval($money)."&key=".$key;
		write_log("pay notify url=".$url);
		$ret = json_decode(http_get($url), true);
		if($ret['ret'] != "0"){
			write_log("pay notify failed data=".json_encode($data)." ret=".$ret);
			return false;
		}		
		return true;
	}
}

$config = new WxPayConfig();
write_log("begin notify");
$notify = new PayNotifyCallBack();
$notify->Handle($config, false);
