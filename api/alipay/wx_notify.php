<?php
/**
 *  支付移动异步通知
 */
//加载工具文件
header("Content-type: text/html; charset=utf-8");
require_once 'config.php';
require_once 'pagepay/service/AlipayTradeService.php';
$alipaySevice = new AlipayTradeService($config); 
$alipaySevice->writeLog('=======================wx_notify begin=======================');
//error_reporting(0);
 //参数建议写在配置文件
$wxpay_config = array(
	array(
	    //APP支付的配置
		'wxpay_appid'=>'1',//TODO 微信appid  如：wxeae261b8d513e5c1
		'wxpay_partnerid'=>'2',//TODO 微信商户号  如:1483834061
		'wxpay_partnerkey'=>'3'//TODO api密钥key 如:0CB831966A67C46351A9CB7627660C31
		),
	array(
		//网页支付配置
		'wxpay_appid'=>'wxa9e0ad4ac7faf996',//TODO 微信appid  如：wxeae261b8d513e5c1
		'wxpay_partnerid'=>'1508247391',//TODO 微信商户号  如:1483834061
		'wxpay_partnerkey'=>'4409496bf9e14ff10b34b61cfa5a4e9a'//TODO api密钥key 如:0CB831966A67C46351A9CB7627660C31
		),
);
$alipaySevice->writeLog("data:".json_encode($_REQUEST));
$callback_info = getNotifyInfo($wxpay_config,$alipaySevice);
if ($callback_info) {
	
	$out_trade_no = $callback_info['out_trade_no'];
	//订单好
	$trade_no = $callback_info['trade_no'];
	//交易号
	$time = time();
	
	$conn = new mysqli('mysql-dev.intexh.com', 'yingli_intexh', 'T2nRcRfJ6mWejDaxfNA', 'yingli_intexh_com');
	
	
	//判断订单类型
	$sql = "select * from yl_order where order_id='$out_trade_no'";
	$alipaySevice->writeLog("sql:".$sql);
	$order_data =  $conn->query($sql);
	$order_data = mysqli_fetch_array($order_data,MYSQL_ASSOC);
	//验证订单是否是 我们的
	if(empty($order_data['id'])){
		echo 'fail';die;
	}
	//验证订单是否已支付
	if($order_data['order_status']!=10){
		echo 'fail';die;
	}
	$time = time();
	$sql = "update yl_order set paytime='$time',order_status=20 where order_id='$out_trade_no'";
	$alipaySevice->writeLog("update_sql:".$sql);
	$order_update =  $conn->query($sql);
	//更新优惠券状态
	if(!empty($order_data['voucher_id'])){
		
	}
	
	//判断支付类型
		if($order_data['order_type']=='user'){
			$cid = $order_data['channel_id'];
			$sql = "select * from yl_channels where id=".$cid;
			$channel_data =  $conn->query($sql);
			$channel_data = mysqli_fetch_array($channel_data,MYSQL_ASSOC);
			$company_id = $channel_data['company_id'];
			$user_id = $order_data['user_id'];
			$money = $order_data['pay_amount'];
			//用户支付进入直播间
			$msg = '用户进入直播间支付，订单号为'.$out_trade_no;
			$sql = "INSERT INTO `yl_order_log` (order_id,cid,user_id,company_id,num,type,addtime,directions) VALUES ('$out_trade_no', '$cid', '$user_id', '$company_id',  '$money', '1', '$time', '$msg')";
			$conn->query($sql);
			$sql = "update yl_company set balance=(balance)+$money where id='$company_id'";
			$conn->query($sql);
		}elseif($order_data['order_type']=='company' && !empty($order_data['channel_id'])){
			$cid = $order_data['channel_id'];
			$company_id = $order_data['company_id'];
			$user_id = $order_data['user_id'];
			$money = $order_data['pay_amount'];
			//企业支付-流量支付
			$msg = '企业支付直播间流量费用，订单号为'.$out_trade_no;
			$sql = "INSERT INTO `yl_order_log` (order_id,cid,user_id,company_id,num,type,addtime,directions) VALUES ('$out_trade_no', '$cid', '$user_id', '$company_id',  '-$money', '0', '$time', '$msg')";
			$conn->query($sql);
		}elseif($order_data['order_type']=='wallet'){
			$cid = $order_data['channel_id'];
			$company_id = $order_data['company_id'];
			$user_id = $order_data['user_id'];
			$money = $order_data['pay_amount'];
			//充值余额
			$msg = '充值余额，订单号为'.$out_trade_no;
			$sql = "INSERT INTO `yl_order_log` (order_id,cid,user_id,company_id,num,type,addtime,directions) VALUES ('$out_trade_no', '$cid', '$user_id', '$company_id',  '$money', '1', '$time', '$msg')";
			$conn->query($sql);
			$sql = "update yl_company set wallet=(wallet)+$money where id='$company_id'";
			$conn->query($sql);
		}
	echo 'success';die;
} else {
	echo 'fail';die ;
}

/**
 * 获取notify信息
 */
function getNotifyInfo($wxpay_config,$log) {
	$result = _verify3($wxpay_config,$log);
	
	
	if ($result) {
		return array(
			//商户订单号
			'out_trade_no' => $result['out_trade_no'],
			//微信支付交易号
			'trade_no' => $result['transaction_id'], 
			'attach' => $result['attach'],
			'total_fee' => $result['total_fee']
		);
	}

	return false;
}

/**
 * 验证返回信息(v3)
 */
function _verify3($wxpay_config,$log) {
	if (empty($wxpay_config)) {
		return false;
	}

	$xml = file_get_contents("php://input") ? file_get_contents("php://input") : @$GLOBALS['HTTP_RAW_POST_DATA'];
	$xml="<xml><appid><![CDATA[wx64e34eb99d171cb8]]></appid>
<attach><![CDATA[虚拟商品购买]]></attach>
<bank_type><![CDATA[CFT]]></bank_type>
<cash_fee><![CDATA[1]]></cash_fee>
<fee_type><![CDATA[CNY]]></fee_type>
<is_subscribe><![CDATA[N]]></is_subscribe>
<mch_id><![CDATA[1498838912]]></mch_id>
<nonce_str><![CDATA[f563c985e896b6055e2ea1971b6d66bc]]></nonce_str>
<openid><![CDATA[o-UQs1cjZ3GCWYhknLSH92vUrMFY]]></openid>
<out_trade_no><![CDATA[260573409102067069]]></out_trade_no>
<result_code><![CDATA[SUCCESS]]></result_code>
<return_code><![CDATA[SUCCESS]]></return_code>
<sign><![CDATA[47B5E35C891A844E7AFFF3E2694BAD1C]]></sign>
<time_end><![CDATA[20180303161833]]></time_end>
<total_fee>1</total_fee>
<trade_type><![CDATA[APP]]></trade_type>
<transaction_id><![CDATA[4200000084201803031807277629]]></transaction_id>
</xml>
	";
//	$array = simplexml_load_string($xml);
	$array = FromXml($xml);
	if(!is_array($array)){
		die("fail");
	}
	$param = array();
	foreach ($array as $key => $value) {
		$param[$key] = (string)$value;
	}
	ksort($param);
	$hash_temp = '';
	foreach ($param as $key => $value) {
		if ($key != 'sign') {
			$hash_temp .= $key . '=' . $value . '&';
		}
	}
	if($param['appid']==$wxpay_config[0]['wxpay_appid'] && $param['mch_id']==$wxpay_config[0]['wxpay_partnerid']){
		$paykey = $wxpay_config[0]['wxpay_partnerkey'];
	}elseif($param['appid']==$wxpay_config[1]['wxpay_appid'] && $param['mch_id']==$wxpay_config[1]['wxpay_partnerid']){
		$paykey = $wxpay_config[0]['wxpay_partnerkey'];
	}else{
		echo 'fail';die;
	}
	$hash_temp .= 'key' . '=' . $paykey;
	$hash = strtoupper(md5($hash_temp));
	if ($hash == $param['sign']) {
		return array(
			'out_trade_no' => $param['out_trade_no'],
			'transaction_id' => $param['transaction_id'],
			'attach' => $param['attach'],
			'total_fee'=>$param['total_fee']
		);
	} else {
		return false;
	}
}

//订单逻辑处理
function pay_notice_call($payInfo, $ok_msg = 'success', $err_msg = 'fail') {
	//订单逻辑处理
	$result = true;
	if (empty($result)) {
		exit($ok_msg);
	} else {
		exit($result['err_msg']);
	}

}

function FromXml($xml)
{	
	if(!$xml){
		die('fail');
	}
    //将XML转为array
    //禁止引用外部xml实体
    libxml_disable_entity_loader(true);
    $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);		
	return $values;
}
