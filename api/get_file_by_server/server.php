<?php


/**
 * 开发工具-如日志等
 */
namespace app\development\controller;

use think\Controller;
use think\facade\Env;

class DevelopmentServer extends Controller
{


    /**
     * 私钥，额外验证传递访问的签名而已
     */
    private  $privateKey = 'XXXX';


    /**
     * 获取当前服务器的文件目录
     * @param code 100是文件内容
     * @param code 101是文件列表
     * @param list 要删除的文件列表
     * 测试在本地的tp5.1，php7.1版本
     */
    public function getFileList($path = '',$sign = '',$time = '',$list = '')
    {
        if($this->checkSign($path,$sign,$time)) return $this->returnJosn(404,'sign error');
        $path = urldecode($path);
        $root = Env::get('root_path').$path;
        try{
            if(!empty($list)){
                $list = json_decode($list,true);
                foreach ($list as $k => $v) {
                    if($v == '../') continue;
                    if(is_dir($root.'/'.$v)){
                        $this->deldir($root.'/'.$v);
                    }else{
                        unlink($root.'/'.$v);
                    }
                }
            }else{
                $suffix = ['.log','.logs','.php','.txt'];
                foreach ($suffix as $key) {
                    if(strstr($path,$key)){
                        $response = file_get_contents($root);//只能获取2G以内的数据
                        return $this->returnJosn(100,'success',$response);
                    }
                }
            }
            $response = $this->checkdir($root);
            return $this->returnJosn(101,'success',$response);
        }catch(\Exception $e){
            phplogs($e->getMessage());
            return $this->returnJosn(404,$e->getMessage());
        }
    }


     /**
     * 获取redis里的数据
     */
    public function getRedis($path = '',$sign = '',$time = '',$list = '')
    {
        if($this->checkSign($path,$sign,$time)) return $this->returnJosn(404,'sign error');
        $config = config()['cache'];
        $redis = new \think\cache\driver\Redis($config);
        if($list){
            $list = json_decode($list,true);
            $response = $redis->del($list);
        }
        //获取配置
        $redisList = $redis->keys('*');
        return $this->returnJosn(200,'success',$redisList);
    }



    /**
     * 遍历文件
     */
    private function checkdir($basedir)
    {
        $return = $top = [];
        if ($dh = opendir($basedir)) {
            while (($file = readdir($dh)) !== false) {
                $temp = [];
                /**
                 * type类型，1文件夹 2文件 0上一页？
                 */
                if ($file != '.' && $file != '..'){
                    if (!is_dir($basedir."/".$file)) {
                        $temp['name'] = $file;
                        $temp['type'] = 2;
                        $temp['size'] = round(filesize($basedir."/".$file)/1024,2).'KB';
                    }else{
                        $temp['name'] = $file;
                        $temp['type'] = 1;
                        $temp['size'] = '-';
                    }
                }elseif($file == '..'){
                    $top['name'] = '../';
                    $top['type'] = 0;
                    $top['size'] = 0;
                }
                if($temp) array_push($return,$temp);
            }
            if($top) array_unshift($return,$top);
            closedir($dh);
        }
        return $return;
    }

    /**
     * 遍历文件
     */
    private function deldir($basedir)
    {
        $return = [];
        if ($dh = opendir($basedir)) {
            while (($file = readdir($dh)) !== false) {
                if ($file != '.' && $file != '..'){
                    if (!is_dir($basedir."/".$file)) {
                        unlink($basedir."/".$file);
                    }else{
                        $this->deldir($basedir."/".$file);
                    }
                }
            }
            closedir($dh);
        }
        rmdir($basedir);
        return 1;
    }

    /**
     * 使用生成器读取大文件
     * 测试发现6M文件打开变2S
     * 38M的打开25S，用file_get_contents用45S
     */
    private function readFile($path)
    {
        $handle = fopen($path, "r");
        while(!feof($handle)) {
            yield trim(fgets($handle));
        }
        fclose($handle);
    }



    /**
     * 返回数据
     * 100是文件内容
     * 101是文件列表
     * 200是redis列表
     * 404是错误
     */
    private function returnJosn($code,$msg,$data = '')
    {
        $returnCode = [
            'msg'   => $msg,
            'code'  => $code,
            'data'  => $data
        ];
        return json_encode($returnCode);
    }
    /**
     * 验证签名
     */
    private function checkSign($path,$sign,$time)
    {
        if(!$path || !$sign ||!$time) return true;
        $checkSign = strtoupper(md5($path.$this->privateKey.$time));
        if($checkSign != $sign) return true;
        return false;
    }

}
