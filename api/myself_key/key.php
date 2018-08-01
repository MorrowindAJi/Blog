<?php
/**
 * 加密算法-生成私钥
 * 传入要加密的字符串，用Php的方式进行加密
 * 加密方式为
 * 1、将数字X变为*X*
 * 2、将小写字母a变为/a/
 * 3、将所有字符串全部变为大写并拼上一些随机数做base64转码
 * 4、再次base64转码，然后将=号改为*
 * 5、再加密后的字符串前加上*
 */
 function myencrypt($key=''){
	$len = strlen($key);
	$len_begin = 0;
	$private_key = '';
	while($len){
		if(is_numeric($key{$len_begin})){
      //第一步
			$tmp = str_replace(array('0','1','2','3','4','5','6','7','8','9'),array('*0*','*1*','*2*','*3*','*4*','*5*','*6*','*7*','*8*','*9*'),$key{$len_begin});
			$private_key .= $tmp;
		}else{
      //第二步
			if(ord($key{$len_begin}) >=97 && ord($key{$len_begin})<=122){
				$private_key.= "/".$key{$len_begin}."/";
			}else{
				$private_key.=$key{$len_begin};
			}
		}
		$len = $len - 1;
		$len_begin++;
	}
  //第三步,这里拼上的随机数做了md5加密，所以长度为32位，如果改变，需要在解密函数里进行修改
	$private_key = base64_encode(strtoupper($private_key).time()).md5(time().rand(10000, 99999));
  //第四步
	$private_key = base64_encode($private_key);
	$private_key = str_replace("=","*",$private_key);
  //第五步
	return  "*".$private_key;
 }

/**
 * 解密匹配
 *  注意！生成的公钥文件可以给客户端，并告知他们如何生成签名
 * 私钥文件存在服务器上，不能随意给人
 * 客户端生成方法如下：
 * 1、32位随机数或字母+时间戳+公钥
 * 2、base64加密
 * 3、将‘=’改为'*'
 * 4、在生成的字段前面加*
 * 例子：
 * 公钥为：dnnMu67rN8jjUuMWxbaqFbww86bd6111
 * 1、dnnMu67rN8jjUuMWxbaqFbww86bd6r71（随机字段）1527502595（时间戳）dnnMu67rN8jjUuMWxbaqFbww86bd6111（公钥）
 * 2、ZG5uTXU2N3JOOGpqVXVNV3hiYXFGYnd3ODZiZDZyNzExNTI3NTAyNTk1ZG5uTXU2N3JOOGpqVXVNV3hiYXFGYnd3ODZiZDYxMTE=
 * 3、ZG5uTXU2N3JOOGpqVXVNV3hiYXFGYnd3ODZiZDZyNzExNTI3NTAyNTk1ZG5uTXU2N3JOOGpqVXVNV3hiYXFGYnd3ODZiZDYxMTE*
 * 4、*ZG5uTXU2N3JOOGpqVXVNV3hiYXFGYnd3ODZiZDZyNzExNTI3NTAyNTk1ZG5uTXU2N3JOOGpqVXVNV3hiYXFGYnd3ODZiZDYxMTE*
 * 则sign=*ZG5uTXU2N3JOOGpqVXVNV3hiYXFGYnd3ODZiZDZyNzExNTI3NTAyNTk1ZG5uTXU2N3JOOGpqVXVNV3hiYXFGYnd3ODZiZDYxMTE*
 */
function mydencrypt($sign = ""){
	$sign = base64_decode(str_replace("*","=",substr($sign, 1)));
	$time = substr($sign, 32,-32);
  /**
   *  这里进行签名的判断，客户端传递的秘钥如果大于1分钟，则秘钥失效
   *  具体情况可以根据需求进行修改
   */
	if(time()-$time>60) return FALSE;
	$sign = substr($sign, -32);
  //读取私钥进行批匹配
	$key = file_get_contents(__DIR__.'/private_key.txt');
	$key = substr($key, 1);
	$key = base64_decode(str_replace("*","=",$key));
  //这里为随机数的长度判断，如果加密时不为32的随机数，则需要进行修改
	$key = base64_decode(substr($key, 0,-32));
	$key = substr($key, 0,-10);
	$key = str_replace("*","",$key);
	$len = strlen($key);
	$len_begin = 0;
	$char = '';
	while($len){
		if($key[$len_begin] == "/"){
			$char .= strtolower($key[$len_begin+1]);
			$len = $len - 3;
			$len_begin = $len_begin+3;
		}else{
			$char .=$key[$len_begin];
			$len = $len - 1;
			$len_begin++;
		}
	}
	if($sign == $char){
		return TRUE;
	}else{
		return FALSE;
	}
}

/**
 * 加密算法-公钥
 * 生成任意长度的随机数
 * 默认为32位长度
 */
function randomOp($length=32){
	$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
	$char = '';
	while($length){
		$char.=$chars{rand(0, 61)};
		$length = $length - 1;
	}
	return $char;
}
?>
