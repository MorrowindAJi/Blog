<?php
/* *
 * 功能：支付宝服务器异步通知页面
 * 版本：2.0
 * 修改日期：2017-05-01
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。

 *************************页面功能说明*************************
 * 创建该页面文件时，请留心该页面文件中无任何HTML代码及空格。
 * 该页面不能在本机电脑测试，请到服务器上做测试。请确保外部可以访问该页面。
 * 如果没有收到该页面返回的 success 信息，支付宝会在24小时内按一定的时间策略重发通知
 */

require_once 'config.php';
require_once 'pagepay/service/AlipayTradeService.php';
$alipaySevice = new AlipayTradeService($config); 
$alipaySevice->writeLog('=======================alipay_notify begin=======================');
$alipaySevice->writeLog(var_export($_POST,true));
$result = @$alipaySevice->check($arr);
/* 实际验证过程建议商户添加以下校验。
1、商户需要验证该通知数据中的out_trade_no是否为商户系统中创建的订单号，
2、判断total_amount是否确实为该订单的实际金额（即商户订单创建时的金额），
3、校验通知中的seller_id（或者seller_email) 是否为out_trade_no这笔单据的对应的操作方（有的时候，一个商户可能有多个seller_id/seller_email）
4、验证app_id是否为该商户本身。
*/
//if(1) {//验证成功
if($result) {//验证成功
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//请在这里加上商户的业务逻辑程序代

	
	//——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
	
    //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
	
	//商户订单号

	$out_trade_no = $_POST['out_trade_no'];

	//支付宝交易号

	$trade_no = $_POST['trade_no'];

	//交易状态
	$trade_status = $_POST['trade_status'];
	
	$alipaySevice->writeLog("out_trade_no:".$out_trade_no);
	$alipaySevice->writeLog("trade_no:".$trade_no);
	$alipaySevice->writeLog("trade_status:".$trade_status);
	
	
	
	$conn = new mysqli('mysql-dev.intexh.com', 'yingli_intexh', 'T2nRcRfJ6mWejDaxfNA', 'yingli_intexh_com');

    if($_POST['trade_status'] == 'TRADE_FINISHED') {

		//判断该笔订单是否在商户网站中已经做过处理
			//如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
			//请务必判断请求时的total_amount与通知时获取的total_fee为一致的
			//如果有做过处理，不执行商户的业务程序
				
		//注意：
		//退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知
    }
    else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
		//判断该笔订单是否在商户网站中已经做过处理
			//如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
			//请务必判断请求时的total_amount与通知时获取的total_fee为一致的
			//如果有做过处理，不执行商户的业务程序			
		//注意：
		//付款完成后，支付宝系统发送该交易状态通知
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
		//更新订单状态
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
		echo "success";	//请不要修改或删除
    }
	//——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
}else {
    //验证失败
    echo "fail";

}
?>