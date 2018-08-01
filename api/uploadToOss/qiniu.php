<?php

final class qiniu
{
    private static $accessKey = ''; //请自行去七牛申请
    private static $secretKey = ''; //请自行去七牛申请
    private static $bucket = '';    //你的七牛管理后台的某个空间名
    private static $domain = '';//你的七牛管理后台的分配给你的域名，位于 空间设置->域名设置->七牛域名
    private static $returnUrl = '';//上传成功后的回调地址
    private static $QiniuAuth;

    private static function _init()
    {
        require_once('qiniu.sdk.php');
//      require_once('conf.inc.php');
        self::$accessKey = C('qiniu.accesskey'); //请自行去七牛申请
        self::$secretKey =C('qiniu.secretkey'); //请自行去七牛申请
        self::$bucket = C('qiniu.bucket');    //你的七牛管理后台的某个空间名
        self::$domain = C('qiniu.img_url');//你的七牛管理后台的分配给你的域名，位于 空间设置->域名设置->七牛域名

        self::$QiniuAuth = new Auth(self::$accessKey, self::$secretKey);
    }

    //上传（模板文件见附件）
    /**
     * @param null $key 自定义的名字，如果不设置，就跟hash相同
     * @return array
     */
    public static function getToken($key = null)
    {
        self::_init();
        $auth = self::$QiniuAuth;
        $bucket = self::$bucket;            // 要上传的空间
        $token = $auth->uploadToken($bucket, $key); // 生成上传 Token
        return array('token' => $token, 'domain' => self::$domain);
    }

    /**
     * @param string $key 上传文件FILE的key
     * @param string $filePath 上传文件地址
     * @return bool|string
     */
    public static function upload($key = 'data', $filePath = '')
    {
        self::_init();
        $auth = self::$QiniuAuth;
        $bucket = self::$bucket;            // 要上传的空间
        if (empty($filePath)) {
            $filePath = $_FILES[$key]['tmp_name'];
        }
        $type = $_FILES[$key]['type'];
        $token = $auth->uploadToken($bucket); // 生成上传 Token
        $uploadMgr = new UploadManager(self::$accessKey, self::$secretKey);
        try {
            list($ret, $err) = $uploadMgr->putFile($token, null, $filePath, null, $type, false);
            if ($err) {
                return false;
            } else {
                //$ret = array(2) {["hash"]=>string(28) "FuE7K2N4E8dXMXoE6q6oF5OfL6sb"["key"]=>string(28) "FuE7K2N4E8dXMXoE6q6oF5OfL6sb"}
                return self::$domain . '/' . $ret['hash'];
            }

        } catch (Exception $ex) {
            return false;
//            die($ex->getMessage());
        }
    }

}
	