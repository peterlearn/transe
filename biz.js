const URL_RECHARGE = "https://wxsg3-forall-clb.sihai-inc.com/sanguo/recharge/";  //充值中心
const PAY_DESC = "三国挂机英雄充值";

let _urlParams = null;//参数信息
let _payIdParams = null;//支付参数

let _wxPayData = null;//支付订单信息，通过服务器获取
let _isConfigReady = false;//微信接口是否已初始化

main();
function main(){
	let args = getUrlParams();
	if(args.pay && args.code){
		_urlParams = args;
		let params = args.pay.split("_");
		if(params.length > 2){
			let playerId = params[0];
			let payId = params[1];
			let chargeNum = params[2];
			if(playerId > 0 && payId > 0 && chargeNum > 0){
				_payIdParams = params;
				//提前侦听初始化信息
				wx.ready(function () {
					_isConfigReady = true;
					// config信息验证后会执行ready方法，所有接口调用都必须在config接口获得结果之后，config是一个客户端的异步操作，所以如果需要在页面加载时就调用相关接口，则须把相关接口放在ready函数中调用来确保正确执行。对于用户触发时才调用的接口，则可以直接调用，不需要放在ready函数中。
					callWXPay();
				});
				wx.error(function (res) {
					// console.error("config error", res);// config信息验证失败会执行error函数，如签名过期导致验证失败，具体错误信息可以打开config的debug模式查看，也可以在返回的res参数中查看，对于SPA可以在这里更新签名。
					//跳转到充值页面
					jumpToWebPay("微信初始化失败");
				});
				verifyInfo(playerId);
				return;
			}
		}
	}
	//跳转到充值页面
	jumpToWebPay();
}

//发起微信支付
function callWXPay(){
	if(!_isConfigReady || !_wxPayData){
		return;
	}
	let payInfo = {};
	payInfo.appId = _wxPayData.appid;          //公众号名称，由商户传入
	payInfo.timeStamp = ""+_wxPayData.timestamp;  //时间戳，自1970年以来的秒数
	payInfo.nonceStr = _wxPayData.nonce_str;   //随机串
	payInfo.package = _wxPayData.package;
	payInfo.signType = _wxPayData.sign_type;   //微信签名方式：
	payInfo.paySign = _wxPayData.signature;    //微信签名
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

//请求订单
function requestPayInfo(userInfo) {
	let payData = {};
	payData.playerId = userInfo.playerId;         //玩家ID
	payData.serverId = userInfo.loginServerId;    //服务器区号
	payData.openid = userInfo.openid; //微信openid
	payData.rechargeId = _payIdParams[1];            //商品ID
	payData.total_fee = parseInt(_payIdParams[2])*100;//充值金额分
	payData.body = PAY_DESC;
	payData.os = 5;  //充值平台  1:小游戏内支付  2:web充值支付  3:公众号互通账号支付  4:公众号H5账号支付  5:客服快速充值
	// alert("请求订单数据" + JSON.stringify(payData))
	httpRequest(URL_RECHARGE+"order_id", "POST", ret =>{
		if(ret.errcode == 0){
			if(ret.data.errorCode){
				jumpToWebPay(ret.data.message);
			}else {
				_wxPayData = ret.data;
				callWXPay();
			}
		}
		else{
			jumpToWebPay(ret.data.message);
		}
	}, payData);
}

//校验参数信息
function verifyInfo(playerId){
	//校验playerId
	verifyPlayerId(playerId, ret=>{
		if(ret.errcode == 0){
			//请求支付信息
			requestPayInfo(ret.data);
			return;
		}
		jumpToWebPay("用户不存在");
	})
}

//验证用户id
function verifyPlayerId(playerId, callfunc) {
	let playerInfo = {};
	playerInfo.playerId = playerId; //玩家游戏ID
	playerInfo.body = PAY_DESC;      //支付类型描述
	playerInfo.code = _urlParams.code || "";    //微信code
	playerInfo.openid = "";
	httpRequest(URL_RECHARGE+"player", "POST", ret => {
		if(ret.errcode == 0){
			let rspData = ret.data;
			//初始化WX接口
			wx.config({
				debug: false, // 开启调试模式,调用的所有api的返回值会在客户端alert出来，若要查看传入的参数，可以在pc端打开，参数信息会通过log打出，仅在pc端时才会打印。
				appId: rspData.appid, // 必填，公众号的唯一标识
				timestamp: rspData.timestamp, // 必填，生成签名的时间戳
				nonceStr: rspData.nonce_str, // 必填，生成签名的随机串
				signature: rspData.signature,// 必填，签名
				jsApiList: ["chooseWXPay", "getBrandWCPayRequest"] // 必填，需要使用的JS接口列表
			});
		}
		callfunc && callfunc(ret);
	}, playerInfo);
}

/////////////////////////////////////////////////////////////////////////////////////////

function jumpToWebPay(msg=""){
	// alert("跳转index="+index)
	if(msg != ""){
		alert(msg);
	}
	//跳转到充值页面
	window.location.href = "https://wxsg3-forall-clb.sihai-inc.com/webpay/sggjyx.html";
}

function httpRequest(requestURL, requestType, handler=null, data={}) {
	let xhr = new window.XMLHttpRequest();
	xhr.timeout = 8000;
	xhr.open(requestType, requestURL, true);
	onEventListener(xhr, handler);
	xhr.send(JSON.stringify(data));
	return xhr;
}

function onEventListener(xhr, handler){
	xhr.onreadystatechange = function () {
		if (xhr.readyState === 4) {
			if (xhr.status >= 200 && xhr.status < 300) {
				// console.log(xhr.responseText)
				let ret = JSON.parse(xhr.responseText);
				if (handler) {
					handler({errcode:0, data:ret});
				}
			}
			else {
				let result = {};
				result.errcode = -2;
				result.errmsg = 'XMLHttpRequest Error';
				handler(result);
			}
		} else if (xhr.readyState === 2 && xhr.status >= 400) {
			handler({ errcode: xhr.status, errmsg: xhr.status });
		}
	};

	xhr.addEventListener('abort', () => {
		handler({ errcode: -101, errmsg: "XMLHttpRequest abort"});
	});
	xhr.addEventListener('error', () => {
		handler({ errcode: -102, errmsg: "XMLHttpRequest error" });
	});
	xhr.addEventListener('timeout', () => {
		handler({ errcode: -103, errmsg: "XMLHttpRequest timeout"});
	});
}

function getUrlParams() {
	let args = {};
	if (location && location.search && location.search.length && location.search.length > 1) {
		let query = location.search.substring(1);//获取查询串
		let pairs = query.split("&");
		for (let i = 0; i < pairs.length; i++) {
			let pos = pairs[i].indexOf('=');//查找name=value
			if (pos == -1) {//如果没有找到就跳过
				continue;
			}
			let argname = pairs[i].substring(0, pos);//提取name
			let value = pairs[i].substring(pos + 1);//提取value
			args[argname] = escUrl(unescape(value));//存为属性
		}
	}
	return args;
}

function escUrl(str) {
	return str.replace(/\+/g, "%2B");
}