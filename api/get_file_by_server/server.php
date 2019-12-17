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
     * 错误代码
     */
    private $ERRORCODE = [
        100 => 'success file',//100是文件内容
        101 => 'success file list',//101是文件列表
        102 => 'success file limit',//102是文件分页内容
        103 => 'success file search',//103是文件搜索
        200 => 'success redis',//200是redis列表
        300 => 'file cant read',//300文件不可读
        301 => 'file cant download',//301文件不可下载，需要配置后缀参数FILESUFFIX
        404 => 'sign error',//签名错误
        405 => 'page error',//超出页数范围
        1000 => '',//try里的报错
    ];

    /**
     * 允许读取的文件后缀
     */
    private $FILESUFFIX = [
        '.log',
        '.logs',
        '.php',
        '.txt',
    ];

    /**
     * 初始化判断签名
     */
    public function initialize(){
        $path = input('path','');
        $sign = input('sign','');
        $time = input('time','');
        if($this->checkSign($path,$sign,$time)) die($this->returnJosn(404));
    }

    /**
     * 获取当前服务器的文件目录
     * @param code 100是文件内容
     * @param code 101是文件列表
     * @param list 要删除的文件列表
     * 测试在本地的tp5.1，php7.1版本
     */
    public function getFileList($path = '',$list = '')
    {
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
                foreach ($this->FILESUFFIX as $key) {
                    if(strstr($path,$key)){
                        $response = file_get_contents($root);//只能获取2G以内的数据
                        return $this->returnJosn(100,$response);
                    }
                }
            }
            $response = $this->checkdir($root);
            return $this->returnJosn(101,$response);
        }catch(\Exception $e){
            return $this->returnJosn(1000,$e->getMessage());
        }
    }


    /**
     * 获取redis里的数据
     */
    public function getRedis($list = '')
    {
        $config = config()['cache'];
        $redis = new \think\cache\driver\Redis($config);
        if($list){
            $list = json_decode($list,true);
            $response = $redis->del($list);
        }
        //获取配置
        $prefix = $config['prefix'];
        $redisList = $redis->keys($prefix.'*');
        return $this->returnJosn(200,$redisList);
    }

    /**
     * 读取特定行数，用于大文件读取
     * @param $path 文件路径
     * @param $page 开始的页数
     * @param $limit 读取的行数
     */
    public function readFileByLimit($path = '',$page = 1,$limit = 1000)
    {
        $path = urldecode($path);
        $file = Env::get('root_path').$path;
        $count = $this->getFileCount($file);
        if($page>ceil($count/$limit)) return $this->returnJosn(405);
        $response = $this->readFile($file);
        $begin = ($page-1) * $limit;
        $i = 0;
        $txt = '';
        foreach ($response as $key => $value) {
            if($key<$begin) continue;
            if($i>=$limit) break;
            $txt .= $value."\n";
            $i++;
        }
        return $this->returnJosn(102,$txt);
    }

    /**
     * 搜索文件
     * @param $path 文件路径
     * @param $search 要搜索的字符
     * @param $role 读取规则：是否区分大小写role['Aa'],JSON化传递数据
     * @param $limit 读取的行数,备用
     */
    public function searchFile($path = '',$search = "",$role = [],$limit = 1000)
    {
        $path = urldecode($path);
        $file = Env::get('root_path').$path;
        if(empty($search)) return false;
        try{
            $role = json_decode($role,true);
            $response = $this->readFile($file);
            $txt = '';
            $searchType = isset($role['Aa']) && !empty($role['Aa'])?'strstr':'stristr';//前者区分大小写，后者不区分
            foreach ($response as $key => $value) {
                if($searchType($value,$search)){
                    $item = $key + 1;
                    $txt .= "[$item]:".$value."\n";
                }
            }
            return $this->returnJosn(103,$txt);
        }catch(\Exception $e){
            return $this->returnJosn(1000,$e->getMessage());
        }
    }


    /**
     * 文件下载
     * @param $path 文件路径
     * @param int $readBuffer //分段下载 每次下载的字节数 默认1024bytes
     */
    public function downloadFile($path = '',$readBuffer = 1024)
    {
        $path = urldecode($path);
        $file = Env::get('root_path').$path;
        //检测下载文件是否存在 并且可读
        if (!is_file($file) && !is_readable($file)) {
            return $this->returnJosn(300);
        }
        //检测文件类型是否允许下载
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array('.'.$ext,$this->FILESUFFIX)) {
            return $this->returnJosn(301);
        }
        //设置头信息
        //声明浏览器输出的是字节流
        header('Content-Type: application/octet-stream');
        //声明浏览器返回大小是按字节进行计算
        header('Accept-Ranges:bytes');
        //告诉浏览器文件的总大小
        $fileSize = filesize($file);//坑 filesize 如果超过2G 低版本php会返回负数
        // echo $fileSize;die;
        header('Content-Length:' . $fileSize); //注意是'Content-Length:' 非Accept-Length
        //声明下载文件的名称
        header('Content-Disposition:attachment;filename=' . basename($file));//声明作为附件处理和下载后文件的名称
        //获取文件内容
        $handle = fopen($file, 'rb');//二进制文件用‘rb’模式读取
        while (!feof($handle) ) { //循环到文件末尾 规定每次读取（向浏览器输出为$readBuffer设置的字节数）
            echo fread($handle, $readBuffer);
        }
        fclose($handle);//关闭文件句柄
        exit;

    }

    /**
     * 获取文件有多少行
     * @param $path 文件路径
     * @param $returnType 返回类型,备用
     */
    private function getFileCount($file = '',$returnType = false)
    {
        $line = 0;
        try{
            $fp = fopen($file , 'r');
            while(stream_get_line($fp,8192,"\n")){
                $line++;
            }
            fclose($fp);//关闭文件
            return $line;
        }catch(\Exception $e){
            return $e->getMessage();
        }
    }

    /**
     * 遍历文件
     */
    private function checkdir($basedir)
    {
        $return = $top = $list = [];
        if ($dh = opendir($basedir)) {
            while (($file = readdir($dh)) !== false) {
                $temp = $while = [];
                /**
                 * type类型，1文件夹 2文件 0上一页？
                 */
                if ($file != '.' && $file != '..'){
                    if (!is_dir($basedir."/".$file)) {
                        $temp = [
                            'name' => $file,
                            'type' => 2,
                            'size' => round(filesize($basedir."/".$file)/1024,2).'KB',
                            'time' => date("Y-m-d H:i:s",filemtime($basedir."/".$file)),
                            'count' => $this->getFileCount($basedir."/".$file),
                        ];
                    }else{
                        $while = [
                            'name' => $file,
                            'type' => 1,
                            'size' => '-',
                            'time' => date("Y-m-d H:i:s",filemtime($basedir."/".$file)),
                            'count' => '-',
                        ];
                    }
                }elseif($file == '..'){
                    $top = [
                        'name' => '../',
                        'type' => 0,
                        'size' => 0,
                        'time' => '-',
                        'count' => '-',
                    ];
                }
                if($while) array_unshift($return,$while);
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
    private function returnJosn($code,$data = '')
    {
        $returnCode = [
            'msg'   => $code == 1000?$data:$this->ERRORCODE[$code],
            'code'  => $code,
            'data'  => $code == 1000?[]:$data,
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
