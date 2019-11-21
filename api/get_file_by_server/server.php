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
    private  $privateKey = 'XXXXX';

    /**
     * 获取当前服务器的文件目录
     * @param code 100是文件内容
     * @param code 101是文件列表
     * 测试在本地的tp5.1，php7.1版本
     */
    public function getFileList($path = '/runtime/log/test.txt',$sign = '',$time = '')
    {
        if(!$path || !$sign ||!$time) return json_encode(['msg'=>'error','code'=>404]);
        try{
            $checkSign = strtoupper(md5($path.$this->privateKey.$time));
            if($checkSign != $sign) return json_encode(['msg'=>'error sign','code'=>404]);
            $path = urldecode($path);
            $root = Env::get('root_path').$path;
            $suffix = ['.log','.logs','.php','.txt'];
            foreach ($suffix as $key) {
                if(strstr($path,$key)){
                    $response = file_get_contents($root);//只能获取2G以内的数据
                    // foreach ($this->readFile($root) as $key => $value) {
                    //     array_push($response,$value);
                    //     // echo "<pre>";
                    //     // var_dump($value);
                    // } 
                    // $response = $this->readFile($root);
                    return json_encode(['data'=>$response,'code'=>100]);
                }
            }
            $response = $this->checkdir($root);
            return json_encode(['data'=>$response,'code'=>101]);
        }catch(\Exception $e){
            phplogs($e->getMessage());
            return json_encode(['msg'=>'error','code'=>404]);
        }
    }

    
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
     * 使用生成器读取大文件
     * 测试发现6M文件打开变2S
     * 
     */
    private function readFile($path)
    {
        $handle = fopen($path, "r");
        while(!feof($handle)) {
            yield trim(fgets($handle));
        }
        fclose($handle);
    }

}
