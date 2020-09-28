<?php
date_default_timezone_set('Asia/Shanghai');//'Asia/Shanghai'   亚洲/上海
require_once "./lib/jssdk/jssdk.php";
require_once "./lib/WxPay.Api.php";
require_once "./example/WxPay.JsApiPay.php";
require_once "./example/WxPay.Config.php";
require_once 'log.php';

function chinese_json_encode($json)
{
        return preg_replace("#\\\u([0-9a-f]{4})#ie", "iconv('UCS-2BE', 'UTF-8', pack('H4', '\\1'))", $json);
}

$skey = "VwsewfwJFKWFwefkwgawefgLKFWfwgegwe234LFwg";
$order_id = $_GET['order_id'];
$money = $_GET['mdata'];
$sign = $_GET['s'];
if(md5($order_id.$skey.$money) != $sign)
{
  write_log("invalid sign ".json_encode($_GET));
  return;
}

$jssdk = new JSSDK("wx85e8e6fce747468a", "096402c504cb79a0cfb3648213a76462");
$signPackage = $jssdk->GetSignPackage();
write_log(chinese_json_encode(json_encode($signPackage)));

try{

	$tools = new JsApiPay();
	$openId = $tools->GetOpenid();

  $now = time();
  $time_expire = date("YmdHis", $now + 600);
	//②、统一下单
	$input = new WxPayUnifiedOrder();
	$input->SetBody("test");
	$input->SetAttach("test");
  $input->SetOut_trade_no($order_id);
  $real_money = intval($money) * 100;
	$input->SetTotal_fee(strval($real_money));
	$input->SetTime_start(date("YmdHis"));
	$input->SetTime_expire($time_expire);
	$input->SetGoods_tag("test");
	$input->SetNotify_url("https://jsapi.sxwj.1cent.xyz/bizpay/notify.php");
	$input->SetTrade_type("JSAPI");
	$input->SetOpenid($openId);
	$config = new WxPayConfig();
  $order = WxPayApi::unifiedOrder($config, $input);
  
	// echo '<font color="#f00"><b>统一下单支付单信息</b></font><br/>';
  // printf_info($order);
  write_log(chinese_json_encode(json_encode($order)));
  $jsApiParameters = $tools->GetJsApiParameters($order);
  write_log($jsApiParameters);
	$params = json_decode($jsApiParameters, true);
} catch(Exception $e) {
	write_log(json_encode($e));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title></title>
</head>
<body>
</body>
<script src="https://res.wx.qq.com/open/js/jweixin-1.0.0.js"></script>
<script>
  /*
   * 注意：
   * 1. 所有的JS接口只能在公众号绑定的域名下调用，公众号开发者需要先登录微信公众平台进入“公众号设置”的“功能设置”里填写“JS接口安全域名”。
   * 2. 如果发现在 Android 不能分享自定义内容，请到官网下载最新的包覆盖安装，Android 自定义分享接口需升级至 6.0.2.58 版本及以上。
   * 3. 常见问题及完整 JS-SDK 文档地址：http://mp.weixin.qq.com/wiki/7/aaa137b55fb2e0456bf8dd9148dd613f.html
   *
   * 开发中遇到问题详见文档“附录5-常见错误及解决办法”解决，如仍未能解决可通过以下渠道反馈：
   * 邮箱地址：weixin-open@qq.com
   * 邮件主题：【微信JS-SDK反馈】具体问题
   * 邮件内容说明：用简明的语言描述问题所在，并交代清楚遇到该问题的场景，可附上截屏图片，微信团队会尽快处理你的反馈。
   */

   //发起微信支付
  function callWXPay(){
    let payInfo = {};
    payInfo.appId = '<?php echo $params['appId'];?>';          //公众号名称，由商户传入
    payInfo.timeStamp = '<?php echo $params['timeStamp'];?>';  //时间戳，自1970年以来的秒数
    payInfo.nonceStr = '<?php echo $params['nonceStr'];?>';   //随机串
    payInfo.package = '<?php echo $params['package'];?>';
    payInfo.signType = '<?php echo $params['signType'];?>';   //微信签名方式：
    payInfo.paySign = '<?php echo $params['paySign'];?>';    //微信签名
    // alert("当前支付数据：" + JSON.stringify(payInfo));
    WeixinJSBridge.invoke(
      'getBrandWCPayRequest', payInfo,
      function (res) {
        // alert("充值回调结果：" + JSON.stringify(res));
        if (res.err_msg === "get_brand_wcpay_request:ok") {
          alert("充值成功,请到邮箱查收！");
          // 使用以上方式判断前端返回,微信团队郑重提示：
          //res.err_msg将在用户支付成功后返回ok，但并不保证它绝对可靠。
          //关闭
          WeixinJSBridge.call('closeWindow');
        }
      }
    );
  }

  wx.config({
    debug: false,
    appId: '<?php echo $signPackage["appId"];?>',
    timestamp: <?php echo $signPackage["timestamp"];?>,
    nonceStr: '<?php echo $signPackage["nonceStr"];?>',
    signature: '<?php echo $signPackage["signature"];?>',
    jsApiList: [
      // 所有要调用的 API 都要加到这个列表中
      "chooseWXPay", "getBrandWCPayRequest"
    ]
  });
  wx.ready(function () {
    // 在这里调用 API
    // alert("微信初始化成功");
    callWXPay();
  });
</script>
</html>
