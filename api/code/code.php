<?php
error_reporting(E_ERROR);
require_once 'phpqrcode.php';
$url = urldecode($_GET["data"]);
$errorCorrectionLevel = "L";
$matrixPointSize = 8;
QRcode::png($url,FALSE,$errorCorrectionLevel,$matrixPointSize);

//=====================如果框架需要从后端传递图片到前端，需要使用缓冲区
$errorCorrectionLevel = "L";
$matrixPointSize = 8;
$url = urldecode($_GET["data"]);
ob_start();
QRcode::png($url,FALSE,$errorCorrectionLevel,$matrixPointSize);
$img = ob_get_contents();//获取缓冲区内容
ob_end_clean();//清除缓冲区内容
$imgInfo = 'data:png;base64,' . chunk_split(base64_encode($img));//转base64
ob_flush();
//然后输出到前端imginfo