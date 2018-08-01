<?php
/**
 * 这是聚合支付的二维码生成页面
 */
$url = "https://www.baidu.com/s?ie=UTF-8&wd=微信生成二维码接口";
$url = urlencode($url);
echo 
"<script>
	window.location.href='http://". $_SERVER['HTTP_HOST']."/code/code.php?data=$url'
</script>"; 

