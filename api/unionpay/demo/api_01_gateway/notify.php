<?php
include_once $_SERVER ['DOCUMENT_ROOT'] . '/unionpay/sdk/acp_service.php';
/**
 * 交易说明：	前台类交易成功才会发送后台通知。后台类交易（有后台通知的接口）交易结束之后成功失败都会发通知。
 *              为保证安全，涉及资金类的交易，收到通知后请再发起查询接口确认交易成功。不涉及资金的交易可以以通知接口respCode=00判断成功。
 *              未收到通知时，查询接口调用时间点请参照此FAQ：https://open.unionpay.com/ajweb/help/faq/list?id=77&level=0&from=0
 */
error_reporting(0);
$logger = com\unionpay\acp\sdk\LogUtil::getLogger();
$logger->LogInfo("receive back notify: " . com\unionpay\acp\sdk\createLinkString ( $_POST, false, true ));
$logger->LogInfo("receive back notify json: " . json_encode($_POST));
//$_POST = '{"accNo":"6221558812340000","accessType":"0","bizType":"000201","currencyCode":"156","encoding":"utf-8","merId":"700000000000001","orderId":"201807251037450000267624","queryId":"181807261119545250028","respCode":"00","respMsg":"\u6210\u529f[0000000]","settleAmt":"136","settleCurrencyCode":"156","settleDate":"0726","signMethod":"01","signPubKeyCert":"-----BEGIN CERTIFICATE-----\r\nMIIEQzCCAyugAwIBAgIFEBJJZVgwDQYJKoZIhvcNAQEFBQAwWDELMAkGA1UEBhMC\r\nQ04xMDAuBgNVBAoTJ0NoaW5hIEZpbmFuY2lhbCBDZXJ0aWZpY2F0aW9uIEF1dGhv\r\ncml0eTEXMBUGA1UEAxMOQ0ZDQSBURVNUIE9DQTEwHhcNMTcxMTAxMDcyNDA4WhcN\r\nMjAxMTAxMDcyNDA4WjB3MQswCQYDVQQGEwJjbjESMBAGA1UEChMJQ0ZDQSBPQ0Ex\r\nMQ4wDAYDVQQLEwVDVVBSQTEUMBIGA1UECxMLRW50ZXJwcmlzZXMxLjAsBgNVBAMU\r\nJTA0MUBaMjAxNy0xMS0xQDAwMDQwMDAwOlNJR05AMDAwMDAwMDEwggEiMA0GCSqG\r\nSIb3DQEBAQUAA4IBDwAwggEKAoIBAQDDIWO6AESrg+34HgbU9mSpgef0sl6avr1d\r\nbD\/IjjZYM63SoQi3CZHZUyoyzBKodRzowJrwXmd+hCmdcIfavdvfwi6x+ptJNp9d\r\nEtpfEAnJk+4quriQFj1dNiv6uP8ARgn07UMhgdYB7D8aA1j77Yk1ROx7+LFeo7rZ\r\nDdde2U1opPxjIqOPqiPno78JMXpFn7LiGPXu75bwY2rYIGEEImnypgiYuW1vo9UO\r\nG47NMWTnsIdy68FquPSw5FKp5foL825GNX3oJSZui8d2UDkMLBasf06Jz0JKz5AV\r\nblaI+s24\/iCfo8r+6WaCs8e6BDkaijJkR\/bvRCQeQpbX3V8WoTLVAgMBAAGjgfQw\r\ngfEwHwYDVR0jBBgwFoAUz3CdYeudfC6498sCQPcJnf4zdIAwSAYDVR0gBEEwPzA9\r\nBghggRyG7yoBATAxMC8GCCsGAQUFBwIBFiNodHRwOi8vd3d3LmNmY2EuY29tLmNu\r\nL3VzL3VzLTE0Lmh0bTA5BgNVHR8EMjAwMC6gLKAqhihodHRwOi8vdWNybC5jZmNh\r\nLmNvbS5jbi9SU0EvY3JsMjQ4NzIuY3JsMAsGA1UdDwQEAwID6DAdBgNVHQ4EFgQU\r\nmQQLyuqYjES7qKO+zOkzEbvdFwgwHQYDVR0lBBYwFAYIKwYBBQUHAwIGCCsGAQUF\r\nBwMEMA0GCSqGSIb3DQEBBQUAA4IBAQAujhBuOcuxA+VzoUH84uoFt5aaBM3vGlpW\r\nKVMz6BUsLbIpp1ho5h+LaMnxMs6jdXXDh\/du8X5SKMaIddiLw7ujZy1LibKy2jYi\r\nYYfs3tbZ0ffCKQtv78vCgC+IxUUurALY4w58fRLLdu8u8p9jyRFHsQEwSq+W5+bP\r\nMTh2w7cDd9h+6KoCN6AMI1Ly7MxRIhCbNBL9bzaxF9B5GK86ARY7ixkuDCEl4XCF\r\nJGxeoye9R46NqZ6AA\/k97mJun\/\/gmUjStmb9PUXA59fR5suAB5o\/5lBySZ8UXkrI\r\npp\/iLT8vIl1hNgLh0Ghs7DBSx99I+S3VuUzjHNxL6fGRhlix7Rb8\r\n-----END CERTIFICATE-----","traceNo":"525002","traceTime":"0726111954","txnAmt":"136","txnSubType":"01","txnTime":"20180726111954","txnType":"01","version":"5.1.0","signature":"UgnADAGlPQlBTqbAoksmswhY6Z5Vfuio3pikTPGsX9jl1Ag7AexQ8D74Dxnmnqhq8\/86YKb63wffjGKvsgZWJr22tULEv1f6ENP4+WPSVetBn9wxD392P0KTnnKfQvyZcYZ\/QHQ0maZoG9Y1+sX7mtV\/iWMkH9XQAfDLC5xDpyxnz0YvrOocP5OomZzCUCxnLRWWR0vbPXchANNpU2DuspdeP0CmyzCqNPWQio6+XB58rsDBckGhoJZKjt2mK38fFRqMwUjiFe2UzhXVb\/c+SPeeNA9ywbN5\/FtvAzVqtfRK\/LhUwxr2dpmXWNFM0D5lUC5uVwvvkPxofqacOeUhZw=="}';
//	$_POST = json_decode($_POST,TRUE);
	if (isset ( $_POST ['signature'] )) {
		$flag = com\unionpay\acp\sdk\AcpService::validate ( $_POST );
		$orderId = $_POST ['orderId']; //其他字段也可用类似方式获取
		$respCode = $_POST ['respCode'];
		$queryId = $_POST ['queryId'];
	    //判断respCode=00、A6后，对涉及资金类的交易，请再发起查询接口查询，确定交易成功后更新数据库。
		if($respCode=="00" && $flag){			
			//订单号
			$out_trade_no = $orderId;
			//交易号
			$trade_no = $queryId;
			
			//查询订单，查看是否交易成功
			//略
			$time = time();
			
			$conn = new mysqli('mysql-dev.intexh.com', 'yingli_intexh', 'T2nRcRfJ6mWejDaxfNA', 'yingli_intexh_com');
			
			$sql = "select * from yl_order where order_id='$out_trade_no'";
			$logger->LogInfo("sql:".$sql);
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
			$logger->LogInfo("update_sql:".$sql);
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
			echo 'success';
		}else{
			echo 'fail';
		}
	}
			