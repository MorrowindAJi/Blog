<?php
error_reporting(E_ERROR);
require_once 'phpqrcode.php';
$url = urldecode($_GET["data"]);
$errorCorrectionLevel = "L";
$matrixPointSize = 8;
QRcode::png($url,FALSE,$errorCorrectionLevel,$matrixPointSize);