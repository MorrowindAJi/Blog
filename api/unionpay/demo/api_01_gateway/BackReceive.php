<?php
include_once $_SERVER ['DOCUMENT_ROOT'] . '/unionpay/sdk/acp_service.php';
/**
 * 交易说明：	前台类交易成功才会发送后台通知。后台类交易（有后台通知的接口）交易结束之后成功失败都会发通知。
 *              为保证安全，涉及资金类的交易，收到通知后请再发起查询接口确认交易成功。不涉及资金的交易可以以通知接口respCode=00判断成功。
 *              未收到通知时，查询接口调用时间点请参照此FAQ：https://open.unionpay.com/ajweb/help/faq/list?id=77&level=0&from=0
 */

$logger = com\unionpay\acp\sdk\LogUtil::getLogger();
$logger->LogInfo("receive back notify: " . com\unionpay\acp\sdk\createLinkString ( $_POST, false, true ));
$logger->LogInfo("receive back notify json: " . json_encode($_POST));
//$_POST = "3accNo=6221558812340000&accessType=0&bizType=000201&currencyCode=156&encoding=utf-8&merId=700000000000001&orderId=201807251814000000175473&queryId=951807261002324791038&respCode=00&respMsg=%E6%88%90%E5%8A%9F%5B0000000%5D&settleAmt=136&settleCurrencyCode=156&settleDate=0726&signMethod=01&signPubKeyCert=-----BEGIN+CERTIFICATE-----%0D%0AMIIEQzCCAyugAwIBAgIFEBJJZVgwDQYJKoZIhvcNAQEFBQAwWDELMAkGA1UEBhMC%0D%0AQ04xMDAuBgNVBAoTJ0NoaW5hIEZpbmFuY2lhbCBDZXJ0aWZpY2F0aW9uIEF1dGhv%0D%0Acml0eTEXMBUGA1UEAxMOQ0ZDQSBURVNUIE9DQTEwHhcNMTcxMTAxMDcyNDA4WhcN%0D%0AMjAxMTAxMDcyNDA4WjB3MQswCQYDVQQGEwJjbjESMBAGA1UEChMJQ0ZDQSBPQ0Ex%0D%0AMQ4wDAYDVQQLEwVDVVBSQTEUMBIGA1UECxMLRW50ZXJwcmlzZXMxLjAsBgNVBAMU%0D%0AJTA0MUBaMjAxNy0xMS0xQDAwMDQwMDAwOlNJR05AMDAwMDAwMDEwggEiMA0GCSqG%0D%0ASIb3DQEBAQUAA4IBDwAwggEKAoIBAQDDIWO6AESrg%2B34HgbU9mSpgef0sl6avr1d%0D%0AbD%2FIjjZYM63SoQi3CZHZUyoyzBKodRzowJrwXmd%2BhCmdcIfavdvfwi6x%2BptJNp9d%0D%0AEtpfEAnJk%2B4quriQFj1dNiv6uP8ARgn07UMhgdYB7D8aA1j77Yk1ROx7%2BLFeo7rZ%0D%0ADdde2U1opPxjIqOPqiPno78JMXpFn7LiGPXu75bwY2rYIGEEImnypgiYuW1vo9UO%0D%0AG47NMWTnsIdy68FquPSw5FKp5foL825GNX3oJSZui8d2UDkMLBasf06Jz0JKz5AV%0D%0AblaI%2Bs24%2FiCfo8r%2B6WaCs8e6BDkaijJkR%2FbvRCQeQpbX3V8WoTLVAgMBAAGjgfQw%0D%0AgfEwHwYDVR0jBBgwFoAUz3CdYeudfC6498sCQPcJnf4zdIAwSAYDVR0gBEEwPzA9%0D%0ABghggRyG7yoBATAxMC8GCCsGAQUFBwIBFiNodHRwOi8vd3d3LmNmY2EuY29tLmNu%0D%0AL3VzL3VzLTE0Lmh0bTA5BgNVHR8EMjAwMC6gLKAqhihodHRwOi8vdWNybC5jZmNh%0D%0ALmNvbS5jbi9SU0EvY3JsMjQ4NzIuY3JsMAsGA1UdDwQEAwID6DAdBgNVHQ4EFgQU%0D%0AmQQLyuqYjES7qKO%2BzOkzEbvdFwgwHQYDVR0lBBYwFAYIKwYBBQUHAwIGCCsGAQUF%0D%0ABwMEMA0GCSqGSIb3DQEBBQUAA4IBAQAujhBuOcuxA%2BVzoUH84uoFt5aaBM3vGlpW%0D%0AKVMz6BUsLbIpp1ho5h%2BLaMnxMs6jdXXDh%2Fdu8X5SKMaIddiLw7ujZy1LibKy2jYi%0D%0AYYfs3tbZ0ffCKQtv78vCgC%2BIxUUurALY4w58fRLLdu8u8p9jyRFHsQEwSq%2BW5%2BbP%0D%0AMTh2w7cDd9h%2B6KoCN6AMI1Ly7MxRIhCbNBL9bzaxF9B5GK86ARY7ixkuDCEl4XCF%0D%0AJGxeoye9R46NqZ6AA%2Fk97mJun%2F%2FgmUjStmb9PUXA59fR5suAB5o%2F5lBySZ8UXkrI%0D%0App%2FiLT8vIl1hNgLh0Ghs7DBSx99I%2BS3VuUzjHNxL6fGRhlix7Rb8%0D%0A-----END+CERTIFICATE-----&traceNo=479103&traceTime=0726100232&txnAmt=136&txnSubType=01&txnTime=20180726100232&txnType=01&version=5.1.0&signature=Q%2FusKVYN70HrAun5WFUvYpDeE3NtRb0gC3lL38qXrIMIGbutpECmjI%2BuB5X9GQbkPcJreRVz%2Bb9ZErH2L3alPsVUitgDY3gZLnLRqEmiIreHAG4Th%2FeOml626aNPiAJtjjCMbmmMzilLUrakcpK0zVREjaB%2BsMF2XYVbIAegzHgy1CQgjyEImarx92zchzesmpA%2F3JA%2BK0h937B%2Fx1gFvmL0POcO4RpVI%2Fkb%2FxU5TjM2fQlZkFKX40dQrQB6EPjNxnqwZ3eVm4p2Q8L1llZyiat9wq91CU9Ku7X5dIQDbE5JleWzNlC0ikkRVoxWnqkIFlGeP2%2BITk9%2BHEtI%2B0uHuA%3D%3D";
?>
<!DOCTYPE unspecified PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>银联在线交易测试-结果</title>
<style type="text/css">
body table tr td {
	font-size: 14px;
	word-wrap: break-word;
	word-break: break-all;
	empty-cells: show;
}
</style>
</head>
<body>
	<table width="800px" border="1" align="center">
		<tr>
			<th colspan="2" align="center">银联在线交易测试-交易结果</th>
		</tr>
			<?php
			foreach ( $_POST as $key => $val ) {
				?>
			<tr>
			<td width='30%'><?php echo isset($mpi_arr[$key]) ?$mpi_arr[$key] : $key ;?></td>
			<td><?php echo $val ;?></td>
		</tr>
			<?php }?>
			<tr>
			<td width='30%'>验证签名</td>
			<td><?php			
			if (isset ( $_POST ['signature'] )) {
				
				echo com\unionpay\acp\sdk\AcpService::validate ( $_POST ) ? '验签成功' : '验签失败';
				$orderId = $_POST ['orderId']; //其他字段也可用类似方式获取
				$respCode = $_POST ['respCode'];
                //判断respCode=00、A6后，对涉及资金类的交易，请再发起查询接口查询，确定交易成功后更新数据库。

			} else {
				echo '签名为空';
			}
			?></td>
		</tr>
	</table>
	<?php 
		//如果卡号我们业务配了会返回且配了需要加密的话，请按此方法解密
// 		if(array_key_exists ("accNo", $_POST)){
// 			$accNo = com\unionpay\acp\sdk\AcpService::decryptData($_POST["accNo"]);
// 			echo  "accNo=" . $accNo . "<br>\n";
// 		}
	?>
</body>
</html>