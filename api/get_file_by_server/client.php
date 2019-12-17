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
    if(!$ssl){
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }
    // curl_setopt($ch, CURLOPT_HTTPHEADER, $curlUrl); 方法1
    // if($curlUrl) curl_setopt($ch, CURLOPT_RESOLVE, [$curlUrl]); 方法二
    $result = curl_exec($ch);
    $resinfo = curl_getinfo($ch);
    $apierror = curl_error($ch);
    curl_close($ch);
    unset($ch);
    return array('data' => $result, 'info' => $resinfo, 'error'=>$apierror);
}


/**
 * 下载使用
 * 使用 CURLOPT_WRITEFUNCTION 闭包函数，在请求成功后，在浏览器进行输出，已达到下载的效果
 */
function apiPostDownload($url,$postData='',$curlUrl = '',$isSSL = true)
{
    $file = 'XXX';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $curlUrl);
    if($isSSL){
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }
    $flag = 0;
    //很关键的下载回调
    curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch ,$str) use (&$flag,$file){
        $len = strlen($str);
        $flag++;
        $type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        if($flag==1){
            $size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
            $type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $httpcode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            header("HTTP/1.1 ".$httpcode);
            header("Content-Type: ".$type);
            header("Content-Length: ".$size);
            header('Content-Transfer-Encoding: binary');
            header('Cache-Control:max-age=2592000');
            header('Content-Disposition:attachment;filename=' . basename($file));
        }
        echo $str;
        return $len;
    });
    $result = curl_exec($ch);
    $resinfo = curl_getinfo($ch);
    $apierror = curl_error($ch);
    curl_close($ch);
    unset($ch);
    exit;
}
