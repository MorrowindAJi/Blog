<?php

/**
 * curl
 * post方式
 * @param $url 格式为 协议+ip+路径，如http://127.0.0.1/server.php，在方法二中，URL为正常url，http://域名/server.php
 * @param $postData 需要传递的参数
 * @param $curlUrl 重要！格式为数组，如 ["Host:域名"] ，在方法二中，curlUrl为 域名：端口：ip
 * @param $ssl 是否开启SSL
 * 方法二需要curl>7.24版本
 */
function apiPost($url,$postData='',$curlUrl = '',$ssl = false)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    // curl_setopt($ch, CURLOPT_HTTPHEADER, $curlUrl); 方法1
    // if($curlUrl) curl_setopt($ch, CURLOPT_RESOLVE, [$curlUrl]); 方法二
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $ssl);
    $result = curl_exec($ch);
    $resinfo = curl_getinfo($ch);
    $apierror = curl_error($ch);
    curl_close($ch);
    unset($ch);
    return array('data' => $result, 'info' => $resinfo, 'error'=>$apierror);
}
