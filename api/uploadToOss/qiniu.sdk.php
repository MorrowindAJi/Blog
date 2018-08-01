<?php

final class Config {
	const SDK_VER  = '7.0.2';

	const IO_HOST  = 'http://iovip-z2.qbox.me';            // 七牛源站Host
	const RS_HOST  = 'http://rs.qbox.me';               // 文件元信息管理操作Host
	const RSF_HOST = 'http://rsf.qbox.me';              // 列举操作Host
	const API_HOST = 'http://api.qiniu.com';            // 数据处理操作Host

//	const UPAUTO_HOST   = 'http://up.qiniu.com';        // 默认上传Host
	const UPAUTO_HOST   = 'http://up-z2.qiniu.com';        // 默认上传Host
	const UPDX_HOST     = 'http://updx.qiniu.com';      // 电信上传Host
	const UPLT_HOST     = 'http://uplt.qiniu.com';      // 联通上传Host
	const UPYD_HOST     = 'http://upyd.qiniu.com';      // 移动上传Host
	const UPBACKUP_HOST = 'http://upload.qiniu.com';    // 备用上传Host

	const BLOCK_SIZE = 4194304; //4*1024*1024 分块上传块大小，该参数为接口规格，暂不支持修改

	public static $defaultHost = self::UPAUTO_HOST;     // 设置为默认上传Host
}


if (!defined('QINIU_FUNCTIONS_VERSION')) {
	define('QINIU_FUNCTIONS_VERSION', Config::SDK_VER);

	/**
	 * 计算文件的crc32检验码:
	 *
	 * @param $file string  待计算校验码的文件路径
	 *
	 * @return //文件内容的crc32校验码
	 */
	function crc32_file($file) {
		$hash = hash_file('crc32b', $file);
		$array = unpack('N', pack('H*', $hash));

		return sprintf('%u', $array[1]);
	}

	/**
	 * 计算输入流的crc32检验码
	 *
	 * @param $data //待计算校验码的字符串
	 *
	 * @return //输入字符串的crc32校验码
	 */
	function crc32_data($data) {
		$hash = hash('crc32b', $data);
		$array = unpack('N', pack('H*', $hash));

		return sprintf('%u', $array[1]);
	}

	/**
	 * 对提供的数据进行urlsafe的base64编码。
	 *
	 * @param string $data 待编码的数据，一般为字符串
	 *
	 * @return string 编码后的字符串
	 * @link http://developer.qiniu.com/docs/v6/api/overview/appendix.html#urlsafe-base64
	 */
	function base64_urlSafeEncode($data) {
		$find = array('+', '/');
		$replace = array('-', '_');

		return str_replace($find, $replace, base64_encode($data));
	}

	/**
	 * 对提供的urlsafe的base64编码的数据进行解码
	 *
	 * @param string $data 待解码的数据，一般为字符串
	 *
	 * @return string 解码后的字符串
	 */
	function base64_urlSafeDecode($str) {
		$find = array('-', '_');
		$replace = array('+', '/');

		return base64_decode(str_replace($find, $replace, $str));
	}

	/**
	 * 计算七牛API中的数据格式
	 *
	 * @param $bucket //待操作的空间名
	 * @param $key    //待操作的文件名
	 *
	 * @return /符合七牛API规格的数据格式
	 * @link http://developer.qiniu.com/docs/v6/api/reference/data-formats.html
	 */
	function entry($bucket, $key) {
		$en = $bucket;
		if (!empty($key)) {
			$en = $bucket . ':' . $key;
		}

		return base64_urlSafeEncode($en);
	}

}


final class Auth {
	private $accessKey;
	private $secretKey;

	public function __construct($accessKey, $secretKey) {
		$this->accessKey = $accessKey;
		$this->secretKey = $secretKey;
	}

	public function sign($data) {
		$hmac = hash_hmac('sha1', $data, $this->secretKey, true);

		return $this->accessKey . ':' . base64_urlSafeEncode($hmac);
	}

	public function signWithData($data) {
		$data = base64_urlSafeEncode($data);

		return $this->sign($data) . ':' . $data;
	}

	public function signRequest($urlString, $body, $contentType = null) {
		$url = parse_url($urlString);
		$data = '';
		if (isset($url['path'])) {
			$data = $url['path'];
		}
		if (isset($url['query'])) {
			$data .= '?' . $url['query'];
		}
		$data .= "\n";

		if ($body != null &&
			($contentType == 'application/x-www-form-urlencoded') || $contentType == 'application/json'
		) {
			$data .= $body;
		}

		return $this->sign($data);
	}

	public function verifyCallback($contentType, $originAuthorization, $url, $body) {
		$authorization = 'QBox ' . $this->signRequest($url, $body, $contentType);

		return $originAuthorization === $authorization;
	}

	public function privateDownloadUrl($baseUrl, $expires = 3600) {
		$deadline = time() + $expires;

		$pos = strpos($baseUrl, '?');
		if ($pos !== false) {
			$baseUrl .= '&e=';
		} else {
			$baseUrl .= '?e=';
		}
		$baseUrl .= $deadline;

		$token = $this->sign($baseUrl);

		return "$baseUrl&token=$token";
	}

	public function uploadToken($bucket, $key = null, $expires = 3600, $policy = null, $strictPolicy = true) {
		$deadline = time() + $expires;
		$scope    = $bucket;
		if ($key != null) {
			$scope .= ':' . $key;
		}
		$args = array();
		$args = self::copyPolicy($args, $policy, $strictPolicy);
		$args['scope'] = $scope;
		$args['deadline'] = $deadline;
		$b = json_encode($args);

		return $this->signWithData($b);
	}

	/**
	 *上传策略，参数规格详见
	 *http://developer.qiniu.com/docs/v6/api/reference/security/put-policy.html
	 */
	private static $policyFields = array(
		'callbackUrl',
		'callbackBody',
		'callbackHost',
		'callbackBodyType',
		'callbackFetchKey',

		'returnUrl',
		'returnBody',

		'endUser',
		'saveKey',
		'insertOnly',

		'detectMime',
		'mimeLimit',
		'fsizeLimit',

		'persistentOps',
		'persistentNotifyUrl',
		'persistentPipeline',
	);

	private static $deprecatedPolicyFields = array(
		'asyncOps',
	);

	private static function copyPolicy(&$policy, $originPolicy, $strictPolicy) {
		if ($originPolicy == null) {
			return array();
		}
		foreach ($originPolicy as $key => $value) {
			if (in_array($key, self::$deprecatedPolicyFields)) {
				throw new \InvalidArgumentException("{$key} has deprecated");
			}
			if (!$strictPolicy || in_array($key, self::$policyFields)) {
				$policy[$key] = $value;
			}
		}

		return $policy;
	}

	public function authorization($url, $body = null, $contentType = null) {
		$authorization = 'QBox ' . $this->signRequest($url, $body, $contentType);

		return array('Authorization' => $authorization);
	}
}


final class Etag {
	private static function packArray($v, $a) {
		return call_user_func_array('pack', array_merge(array($v), (array)$a));
	}

	private static function blockCount($fsize) {
		return (($fsize + (Config::BLOCK_SIZE - 1)) / Config::BLOCK_SIZE);
	}

	private static function calcSha1($data) {
		$sha1Str = sha1($data, true);
		$err = error_get_last();
		if ($err != null) {
			return array(null, $err);
		}
		$byteArray = unpack('C*', $sha1Str);

		return array($byteArray, null);
	}

	public static function sum($filename) {
		$fhandler = fopen($filename, 'r');
		$err = error_get_last();
		if ($err != null) {
			return array(null, $err);
		}

		$fstat = fstat($fhandler);
		$fsize = $fstat['size'];
		if ($fsize == 0) {
			fclose($fhandler);

			return array('Fto5o-5ea0sNMlW_75VgGJCv2AcJ', null);
		}
		$blockCnt = self::blockCount($fsize);
		$sha1Buf = array();

		if ($blockCnt <= 1) {
			array_push($sha1Buf, 0x16);
			$fdata = fread($fhandler, Config::BLOCK_SIZE);
			if ($err != null) {
				fclose($fhandler);

				return array(null, $err);
			}
			list($sha1Code, $err) = self::calcSha1($fdata);
			$sha1Buf = array_merge($sha1Buf, $sha1Code);
		} else {
			array_push($sha1Buf, 0x96);
			$sha1BlockBuf = array();
			for ($i = 0; $i < $blockCnt; $i++) {
				$fdata = fread($fhandler, Config::BLOCK_SIZE);
				list($sha1Code, $err) = self::calcSha1($fdata);
				if ($err != null) {
					fclose($fhandler);

					return array(null, $err);
				}
				$sha1BlockBuf = array_merge($sha1BlockBuf, $sha1Code);
			}
			$tmpData = self::packArray('C*', $sha1BlockBuf);
			list($sha1Final, $_err) = self::calcSha1($tmpData);
			$sha1Buf = array_merge($sha1Buf, $sha1Final);
		}
		$etag = base64_urlSafeEncode(self::packArray('C*', $sha1Buf));

		return array($etag, null);
	}
}


final class Client {
	public static function get($url, array $headers = array()) {
		$request = new Request('GET', $url, $headers);

		return self::sendRequest($request);
	}

	public static function post($url, $body, array $headers = array()) {
		$request = new Request('POST', $url, $headers, $body);

		return self::sendRequest($request);
	}

	public static function multipartPost($url, $fields, $name, $fileName, $fileBody, $mimeType = null, array $headers = array()) {
		$data = array();
		$mimeBoundary = md5(microtime());

		foreach ($fields as $key => $val) {
			array_push($data, '--' . $mimeBoundary);
			array_push($data, "Content-Disposition: form-data; name=\"$key\"");
			array_push($data, '');
			array_push($data, $val);
		}

		array_push($data, '--' . $mimeBoundary);
		$mimeType = empty($mimeType) ? 'application/octet-stream' : $mimeType;
		$fileName = self::escapeQuotes($fileName);
		array_push($data, "Content-Disposition: form-data; name=\"$name\"; filename=\"$fileName\"");
		array_push($data, "Content-Type: $mimeType");
		array_push($data, '');
		array_push($data, $fileBody);

		array_push($data, '--' . $mimeBoundary . '--');
		array_push($data, '');

		$body = implode("\r\n", $data);
		$contentType = 'multipart/form-data; boundary=' . $mimeBoundary;
		$headers['Content-Type'] = $contentType;
		$request = new Request('POST', $url, $headers, $body);

		return self::sendRequest($request);
	}

	private static function userAgent() {
		$sdkInfo = "QiniuPHP/" . Config::SDK_VER;

		$systemInfo = php_uname("s");
		$machineInfo = php_uname("m");

		$envInfo = "($systemInfo/$machineInfo)";

		$phpVer = phpversion();

		$ua = "$sdkInfo $envInfo PHP/$phpVer";

		return $ua;
	}

	private static function sendRequest($request) {
		$t1 = microtime(true);
		$ch = curl_init();
		$options = array(
			CURLOPT_USERAGENT      => self::userAgent(),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_HEADER         => true,
			CURLOPT_NOBODY         => false,
			CURLOPT_CUSTOMREQUEST  => $request->method,
			CURLOPT_URL            => $request->url
		);

		if (!empty($request->headers)) {
			$headers = array();
			foreach ($request->headers as $key => $val) {
				array_push($headers, "$key: $val");
			}
			$options[CURLOPT_HTTPHEADER] = $headers;
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));

		if (!empty($request->body)) {
			$options[CURLOPT_POSTFIELDS] = $request->body;
		}
		curl_setopt_array($ch, $options);
		$result = curl_exec($ch);
		$t2 = microtime(true);
		$duration = round($t2 - $t1, 3);
		$ret = curl_errno($ch);
		if ($ret !== 0) {
			$r = new Response(-1, $duration, array(), null, curl_error($ch));
			curl_close($ch);

			return $r;
		}
		$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$headers = self::parseHeaders(substr($result, 0, $header_size));
		$body = substr($result, $header_size);
		curl_close($ch);

		return new Response($code, $duration, $headers, $body, null);
	}

	private static function parseHeaders($raw) {
		$headers = array();
		$headerLines = explode("\r\n", $raw);
		foreach ($headerLines as $line) {
			$headerLine = trim($line);
			$kv = explode(':', $headerLine);
			if (count($kv) > 1) {
				$headers[$kv[0]] = trim($kv[1]);
			}
		}

		return $headers;
	}

	private static function escapeQuotes($str) {
		$find = array("\\", "\"");
		$replace = array("\\\\", "\\\"");

		return str_replace($find, $replace, $str);
	}
}


final class Request {
	public $url;
	public $headers;
	public $body;
	public $method;

	public function __construct($method, $url, array $headers = array(), $body = null) {
		$this->method = strtoupper($method);
		$this->url = $url;
		$this->headers = $headers;
		$this->body = $body;
	}
}


final class Response {
	public  $statusCode;
	public  $headers;
	public  $body;
	public  $error;
	private $jsonData;
	public  $duration;

	/** @var array Mapping of status codes to reason phrases */
	private static $statusTexts = array(
		100 => 'Continue',
		101 => 'Switching Protocols',
		102 => 'Processing',
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		207 => 'Multi-Status',
		208 => 'Already Reported',
		226 => 'IM Used',
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		307 => 'Temporary Redirect',
		308 => 'Permanent Redirect',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',
		422 => 'Unprocessable Entity',
		423 => 'Locked',
		424 => 'Failed Dependency',
		425 => 'Reserved for WebDAV advanced collections expired proposal',
		426 => 'Upgrade required',
		428 => 'Precondition Required',
		429 => 'Too Many Requests',
		431 => 'Request Header Fields Too Large',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		506 => 'Variant Also Negotiates (Experimental)',
		507 => 'Insufficient Storage',
		508 => 'Loop Detected',
		510 => 'Not Extended',
		511 => 'Network Authentication Required',
	);

	public function __construct($code, $duration, array $headers = array(), $body = null, $error = null) {
		$this->statusCode   = $code;
		$this->duration     = $duration;
		$this->headers      = $headers;
		$this->body         = $body;
		$this->error        = $error;
		$this->jsonData     = null;
		if ($error != null) {
			return;
		}

		if ($body == null) {
			if ($code >= 400) {
				$this->error = self::$statusTexts[$code];
			}

			return;
		}
		if (self::isJson($headers)) {
			try {
				$jsonData = self::bodyJson($body);
				if ($code >= 400) {
					if ($jsonData['error'] != null) {
						$this->error = $jsonData['error'];
					} else {
						$this->error = $body;
					}
				}
				$this->jsonData = $jsonData;
			} catch (\InvalidArgumentException $e) {
				if ($code >= 200 && $code < 300) {
					$this->error = $e->getMessage();
				} else {
					$this->error = $body;
				}
			}
		} elseif ($code >= 400) {
			$this->error = $body;
		}

		return;
	}

	public function json() {
		return $this->jsonData;
	}

	private static function bodyJson($body, array $config = array()) {
		return json_decode((string)$body, isset($config['object']) ? !$config['object'] : true, 512, isset($config['big_int_strings']) ? JSON_BIGINT_AS_STRING : 0);
	}

	public function xVia() {
		$via = $this->headers['X-Via'];
		if ($via == null) {
			$via = $this->headers['X-Px'];
		}
		if ($via == null) {
			$via = $this->headers['Fw-Via'];
		}

		return $via;
	}

	public function xLog() {
		return $this->headers['X-Log'];
	}

	public function xReqId() {
		return $this->headers['X-Reqid'];
	}

	public function ok() {
		return $this->statusCode >= 200 && $this->statusCode < 300 && $this->error == null;
	}

	public function needRetry() {
		$code = $this->statusCode;
		if ($code < 0 || ($code / 100 == 5 and $code != 579) || $code == 996) {
			return true;
		}
	}

	private static function isJson($headers) {
		return isset($headers['Content-Type']) &&
		strpos($headers['Content-Type'], 'application/json') === 0;
	}
}


final class QiniuError {
	private $url;
	private $response;

	public function __construct($url, $response) {
		$this->url = $url;
		$this->response = $response;
	}

	public function code() {
		return $this->response->statusCode;
	}

	public function getResponse() {
		return $this->response;
	}

	public function message() {
		return $this->response->error;
	}
}


final class Operation {
	private $auth;
	private $token_expire;
	private $domain;

	public function __construct($domain, $auth = null, $token_expire = 3600) {
		$this->auth         = $auth;
		$this->domain       = $domain;
		$this->token_expire = $token_expire;
	}


	/**
	 * 对资源文件进行处理
	 *
	 * @param $key    //待处理的资源文件名
	 * @param $fops   string|array  fop操作，多次fop操作以array的形式传入。
	 *                eg. imageView2/1/w/200/h/200, imageMogr2/thumbnail/!75px
	 *
	 * @return array[] 文件处理后的结果及错误。
	 *
	 * @link http://developer.qiniu.com/docs/v6/api/reference/fop/
	 */
	public function execute($key, $fops) {
		$url = $this->buildUrl($key, $fops);
		$resp = Client::get($url);
		if (!$resp->ok()) {
			return array(null, new QiniuError($url, $resp));
		}
		if ($resp->json() != null) {
			return array($resp->json(), null);
		}

		return array($resp->body, null);
	}

	public function buildUrl($key, $fops) {
		if (is_array($fops)) {
			$fops = implode('|', $fops);
		}

		$url = "http://$this->domain/$key?$fops";
		if ($this->auth !== null) {
			$url = $this->auth->privateDownloadUrl($url, $this->token_expire);
		}

		return $url;
	}
}


final class PersistentFop {
	/**
	 * @var //账号管理密钥对，Auth对象
	 */
	private $auth;

	/**
	 * @var //操作资源所在空间
	 */
	private $bucket;

	/**
	 * @var //多媒体处理队列，详见 https://portal.qiniu.com/mps/pipeline
	 */
	private $pipeline;

	/**
	 * @var //持久化处理结果通知URL
	 */
	private $notify_url;

	public function __construct($auth, $bucket, $pipeline = null, $notify_url = null, $force = false) {
		$this->auth         = $auth;
		$this->bucket       = $bucket;
		$this->pipeline     = $pipeline;
		$this->notify_url   = $notify_url;
		$this->force        = $force;
	}

	/**
	 * 对资源文件进行异步持久化处理
	 *
	 * @param $key    //待处理的源文件
	 * @param $fops   string|array  待处理的pfop操作，多个pfop操作以array的形式传入。
	 *                eg. avthumb/mp3/ab/192k, vframe/jpg/offset/7/w/480/h/360
	 *
	 * @return array[] 返回持久化处理的persistentId, 和返回的错误。
	 *
	 * @link http://developer.qiniu.com/docs/v6/api/reference/fop/
	 */
	public function execute($key, $fops) {
		if (is_array($fops)) {
			$fops = implode(';', $fops);
		}

		$params = array('bucket' => $this->bucket, 'key' => $key, 'fops' => $fops);
		if (!empty($this->pipeline)) {
			$params['pipeline'] = $this->pipeline;
		}
		if (!empty($this->notify_url)) {
			$params['notifyURL'] = $this->notify_url;
		}
		if ($this->force) {
			$params['force'] = 1;
		}
		$data = http_build_query($params);
		$url = Config::API_HOST . '/pfop/';
		$headers = $this->auth->authorization($url, $data, 'application/x-www-form-urlencoded');
		$headers['Content-Type'] = 'application/x-www-form-urlencoded';
		$response = Client::post($url, $data, $headers);
		if (!$response->ok()) {
			return array(null, new QiniuError($url, $response));
		}
		$r = $response->json();
		$id = $r['persistentId'];

		return array($id, null);
	}

	public static function status($id) {
		$url = Config::API_HOST . "/status/get/prefop?id=$id";
		$response = Client::get($url);
		if (!$response->ok()) {
			return array(null, new QiniuError($url, $response));
		}

		return array($response->json(), null);
	}
}


final class BucketManager {
	/**
	 * @var Auth 账号管理密钥对
	 */
	private $auth;

	public function __construct(Auth $auth) {
		$this->auth = $auth;
	}

	/**
	 * 获取指定账号下所有的空间名。
	 *
	 * @return string[] 包含所有空间名
	 */
	public function buckets() {
		return $this->rsget('/buckets');
	}

	/**
	 * 列取空间的文件列表
	 *
	 * @param $bucket     //空间名
	 * @param $prefix     //列举前缀
	 * @param $marker     //列举标识符
	 * @param $limit      //单次列举个数限制
	 * @param $delimiter  //指定目录分隔符
	 *
	 * @return array[]    包含文件信息的数组，类似：[
	 *                                              {
	 *                                                 "hash" => "<Hash string>",
	 *                                                  "key" => "<Key string>",
	 *                                                  "fsize" => "<file size>",
	 *                                                  "putTime" => "<file modify time>"
	 *                                              },
	 *                                              ...
	 *                                            ]
	 * @link  http://developer.qiniu.com/docs/v6/api/reference/rs/list.html
	 */
	public function listFiles($bucket, $prefix = null, $marker = null, $limit = 1000, $delimiter = null) {
		$query = array('bucket' => $bucket);
		if (!empty($prefix)) {
			$query['prefix'] = $prefix;
		}
		if (!empty($marker)) {
			$query['marker'] = $marker;
		}
		if (!empty($limit)) {
			$query['limit'] = $limit;
		}
		if (!empty($delimiter)) {
			$query['delimiter'] = $delimiter;
		}
		$url = Config::RSF_HOST . '/list?' . http_build_query($query);
		list($ret, $error) = $this->get($url);
		if ($ret == null) {
			return array(null, null, $error);
		}
		$marker = isset($ret['marker']) ? $ret['marker'] : null;

		return array($ret['items'], $marker, null);
	}

	/**
	 * 获取资源的元信息，但不返回文件内容
	 *
	 * @param $bucket     //待获取信息资源所在的空间
	 * @param $key        //待获取资源的文件名
	 *
	 * @return array[]    包含文件信息的数组，类似：
	 *                                              [
	 *                                                  "hash" => "<Hash string>",
	 *                                                  "key" => "<Key string>",
	 *                                                  "fsize" => "<file size>",
	 *                                                  "putTime" => "<file modify time>"
	 *                                              ]
	 *
	 * @link  http://developer.qiniu.com/docs/v6/api/reference/rs/stat.html
	 */
	public function stat($bucket, $key) {
		$path = '/stat/' . entry($bucket, $key);

		return $this->rsGet($path);
	}

	/**
	 * 删除指定资源
	 *
	 * @param $bucket     //待删除资源所在的空间
	 * @param $key        //待删除资源的文件名
	 *
	 * @return //成功返回NULL，失败返回对象{"error" => "<errMsg string>", ...}
	 * @link  http://developer.qiniu.com/docs/v6/api/reference/rs/delete.html
	 */
	public function delete($bucket, $key) {
		$path = '/delete/' . entry($bucket, $key);
		list($_, $error) = $this->rsPost($path);

		return $error;
	}


	/**
	 * 给资源进行重命名，本质为move操作。
	 *
	 * @param $bucket     //待操作资源所在空间
	 * @param $oldname    //待操作资源文件名
	 * @param $newname    //目标资源文件名
	 *
	 * @return //成功返回NULL，失败返回对象{"error" => "<errMsg string>", ...}
	 */
	public function rename($bucket, $oldname, $newname) {
		return $this->move($bucket, $oldname, $bucket, $newname);
	}

	/**
	 * 给资源进行重命名，本质为move操作。
	 *
	 * @param $from_bucket     //待操作资源所在空间
	 * @param $from_key        //待操作资源文件名
	 * @param $to_bucket       //目标资源空间名
	 * @param $to_key          //目标资源文件名
	 *
	 * @return //成功返回NULL，失败返回对象{"error" => "<errMsg string>", ...}
	 * @link  http://developer.qiniu.com/docs/v6/api/reference/rs/copy.html
	 */
	public function copy($from_bucket, $from_key, $to_bucket, $to_key) {
		$from = entry($from_bucket, $from_key);
		$to = entry($to_bucket, $to_key);
		$path = '/copy/' . $from . '/' . $to;
		list($_, $error) = $this->rsPost($path);

		return $error;
	}

	/**
	 * 将资源从一个空间到另一个空间
	 *
	 * @param $from_bucket     //待操作资源所在空间
	 * @param $from_key        //待操作资源文件名
	 * @param $to_bucket       //目标资源空间名
	 * @param $to_key          //目标资源文件名
	 *
	 * @return //成功返回NULL，失败返回对象{"error" => "<errMsg string>", ...}
	 * @link  http://developer.qiniu.com/docs/v6/api/reference/rs/move.html
	 */
	public function move($from_bucket, $from_key, $to_bucket, $to_key) {
		$from = entry($from_bucket, $from_key);
		$to   = entry($to_bucket, $to_key);
		$path = '/move/' . $from . '/' . $to;
		list($_, $error) = $this->rsPost($path);

		return $error;
	}

	/**
	 * 主动修改指定资源的文件类型
	 *
	 * @param $bucket     //待操作资源所在空间
	 * @param $key        //待操作资源文件名
	 * @param $mime       //待操作文件目标mimeType
	 *
	 * @return //成功返回NULL，失败返回对象{"error" => "<errMsg string>", ...}
	 * @link  http://developer.qiniu.com/docs/v6/api/reference/rs/chgm.html
	 */
	public function changeMime($bucket, $key, $mime) {
		$resource = entry($bucket, $key);
		$encode_mime = base64_urlSafeEncode($mime);
		$path = '/chgm/' . $resource . '/mime/' . $encode_mime;
		list($_, $error) = $this->rsPost($path);

		return $error;
	}

	/**
	 * 从指定URL抓取资源，并将该资源存储到指定空间中
	 *
	 * @param $url        //指定的URL
	 * @param $bucket     //目标资源空间
	 * @param $key        //目标资源文件名
	 *
	 * @return array[]    包含已拉取的文件信息。
	 *                         成功时：  [
	 *                                          [
	 *                                              "hash" => "<Hash string>",
	 *                                              "key" => "<Key string>"
	 *                                          ],
	 *                                          null
	 *                                  ]
	 *
	 *                         失败时：  [
	 *                                          null,
	 *                                         Qiniu/Http/Error
	 *                                  ]
	 * @link  http://developer.qiniu.com/docs/v6/api/reference/rs/fetch.html
	 */
	public function fetch($url, $bucket, $key) {

		$resource = base64_urlSafeEncode($url);
		$to = entry($bucket, $key);
		$path = '/fetch/' . $resource . '/to/' . $to;

		return $this->ioPost($path);
	}

	/**
	 * 从镜像源站抓取资源到空间中，如果空间中已经存在，则覆盖该资源
	 *
	 * @param $bucket     //待获取资源所在的空间
	 * @param $key        //代获取资源文件名
	 *
	 * @return //成功返回NULL，失败返回对象{"error" => "<errMsg string>", ...}
	 * @link  http://developer.qiniu.com/docs/v6/api/reference/rs/prefetch.html
	 */
	public function prefetch($bucket, $key) {
		$resource = entry($bucket, $key);
		$path = '/prefetch/' . $resource;
		list($_, $error) = $this->ioPost($path);

		return $error;
	}

	/**
	 * 在单次请求中进行多个资源管理操作
	 *
	 * @param $operations     //资源管理操作数组
	 *
	 * @return   //每个资源的处理情况，结果类似：
	 *              [
	 *                   { "code" => <HttpCode int>, "data" => <Data> },
	 *                   { "code" => <HttpCode int> },
	 *                   { "code" => <HttpCode int> },
	 *                   { "code" => <HttpCode int> },
	 *                   { "code" => <HttpCode int>, "data" => { "error": "<ErrorMessage string>" } },
	 *                   ...
	 *               ]
	 * @link http://developer.qiniu.com/docs/v6/api/reference/rs/batch.html
	 */
	public function batch($operations) {
		$params = 'op=' . implode('&op=', $operations);

		return $this->rsPost('/batch', $params);
	}

	private function rsPost($path, $body = null) {
		$url = Config::RS_HOST . $path;

		return $this->post($url, $body);
	}

	private function rsGet($path) {
		$url = Config::RS_HOST . $path;

		return $this->get($url);
	}

	private function ioPost($path, $body = null) {
		$url = Config::IO_HOST . $path;

		return $this->post($url, $body);
	}

	private function get($url) {
		$headers = $this->auth->authorization($url);
		$ret = Client::get($url, $headers);
		if (!$ret->ok()) {
			return array(null, new QiniuError($url, $ret));
		}

		return array($ret->json(), null);
	}

	private function post($url, $body) {
		$headers = $this->auth->authorization($url, $body, 'application/x-www-form-urlencoded');
		$ret = Client::post($url, $body, $headers);
		if (!$ret->ok()) {
			return array(null, new QiniuError($url, $ret));
		}
		$r = $ret->body == null ? array() : $ret->json();

		return array($r, null);
	}

	public static function buildBatchCopy($source_bucket, $key_pairs, $target_bucket) {
		return self::twoKeyBatch('copy', $source_bucket, $key_pairs, $target_bucket);
	}


	public static function buildBatchRename($bucket, $key_pairs) {
		return self::buildBatchMove($bucket, $key_pairs, $bucket);
	}


	public static function buildBatchMove($source_bucket, $key_pairs, $target_bucket) {
		return self::twoKeyBatch('move', $source_bucket, $key_pairs, $target_bucket);
	}


	public static function buildBatchDelete($bucket, $keys) {
		return self::oneKeyBatch('delete', $bucket, $keys);
	}


	public static function buildBatchStat($bucket, $keys) {
		return self::oneKeyBatch('stat', $bucket, $keys);
	}

	private static function oneKeyBatch($operation, $bucket, $keys) {
		$data = array();
		foreach ($keys as $key) {
			array_push($data, $operation . '/' . entry($bucket, $key));
		}

		return $data;
	}

	private static function twoKeyBatch($operation, $source_bucket, $key_pairs, $target_bucket) {
		if ($target_bucket == null) {
			$target_bucket = $source_bucket;
		}
		$data = array();
		foreach ($key_pairs as $from_key => $to_key) {
			$from = entry($source_bucket, $from_key);
			$to = entry($target_bucket, $to_key);
			array_push($data, $operation . '/' . $from . '/' . $to);
		}

		return $data;
	}
}


final class FormUploader {
	public static function put($upToken, $key, $data, $params, $mime, $checkCrc) {
		$fields = array('token' => $upToken);
		if ($key === null) {
			$fname = 'filename';
		} else {
			$fname = $key;
			$fields['key'] = $key;
		}
		if ($checkCrc) {
			$fields['crc32'] = crc32_data($data);
		}
		if ($params) {
			foreach ($params as $k => $v) {
				$fields[$k] = $v;
			}
		}

		$response = Client::multipartPost(Config::$defaultHost, $fields, 'file', $fname, $data, $mime);
		if (!$response->ok()) {
			return array(null, new QiniuError(Config::$defaultHost, $response));
		}

		return array($response->json(), null);
	}

	public static function putFile($upToken, $key, $filePath, $params, $mime, $checkCrc) {
		$fields = array('token' => $upToken, 'file' => self::createFile($filePath, $mime));
		if ($key === null) {
			$fname = 'filename';
		} else {
			$fname = $key;
			$fields['key'] = $key;
		}
		if ($checkCrc) {
			$fields['crc32'] = crc32_file($filePath);
		}
		if ($params) {
			foreach ($params as $k => $v) {
				$fields[$k] = $v;
			}
		}
		$headers = array('Content-Type' => 'multipart/form-data');
		$response = client::post(Config::$defaultHost, $fields, $headers);
		if (!$response->ok()) {
			return array(null, new QiniuError(Config::$defaultHost, $response));
		}

		return array($response->json(), null);
	}

	private static function createFile($filename, $mime) {
		// PHP 5.5 introduced a CurlFile object that deprecates the old @filename syntax
		// See: https://wiki.php.net/rfc/curl-file-upload
		if (function_exists('curl_file_create')) {
			return curl_file_create($filename, $mime);
		}

		// Use the old style if using an older version of PHP
		$value = "@{$filename}";
		if (!empty($mime)) {
			$value .= ';type=' . $mime;
		}

		return $value;
	}
}


final class ResumeUploader {
	private $upToken;
	private $key;
	private $inputStream;
	private $size;
	private $params;
	private $mime;
	private $progressHandler;
	private $contexts;
	private $host;
	private $currentUrl;

	/**
	 * 上传二进制流到七牛
	 *
	 * @param $upToken     //上传凭证
	 * @param $key         //上传文件名
	 * @param $inputStream //上传二进制流
	 * @param $size        //上传流的大小
	 * @param $params      //自定义变量
	 * @param $mime        //上传数据的mimeType
	 *
	 * @link http://developer.qiniu.com/docs/v6/api/overview/up/response/vars.html#xvar
	 */
	public function __construct($upToken, $key, $inputStream, $size, $params, $mime) {
		$this->upToken = $upToken;
		$this->key = $key;
		$this->inputStream = $inputStream;
		$this->size = $size;
		$this->params = $params;
		$this->mime = $mime;
		$this->host = Config::$defaultHost;
		$this->contexts = array();
	}


	/**
	 * 上传操作
	 */
	public function upload() {
		$uploaded = 0;
		while ($uploaded < $this->size) {
			$blockSize = $this->blockSize($uploaded);
			$data = fread($this->inputStream, $blockSize);
			if ($data === false) {
				fclose($this->inputStream);
				throw new \Exception("file read failed", 1);
			}
			$crc = crc32_data($data);
			$response = $this->makeBlock($data, $blockSize);
			$ret = null;
			if ($response->ok() && $response->json() != null) {
				$ret = $response->json();
			}
			if ($response->statusCode < 0) {
				$this->host = Config::UPBACKUP_HOST;
			}
			if ($response->needRetry() || !isset($ret['crc32']) || $crc != $ret['crc32']) {
				$response = $this->makeBlock($data, $blockSize);
				$ret = $response->json();
			}

			if (!$response->ok() || !isset($ret['crc32']) || $crc != $ret['crc32']) {
				fclose($this->inputStream);

				return array(null, new QiniuError($this->currentUrl, $response));
			}
			array_push($this->contexts, $ret['ctx']);
			$uploaded += $blockSize;
		}
		fclose($this->inputStream);

		return $this->makeFile();
	}

	/**
	 * 创建块
	 */
	private function makeBlock($block, $blockSize) {
		$url = $this->host . '/mkblk/' . $blockSize;

		return $this->post($url, $block);
	}

	private function fileUrl() {
		$url = $this->host . '/mkfile/' . $this->size;
		$url .= '/mimeType/' . base64_urlSafeEncode($this->mime);
		if ($this->key != null) {
			$url .= '/key/' . base64_urlSafeEncode($this->key);
		}
		if (!empty($this->params)) {
			foreach ($this->params as $key => $value) {
				$val = base64_urlSafeEncode($value);
				$url .= "/$key/$val";
			}
		}

		return $url;
	}

	/**
	 * 创建文件
	 */
	private function makeFile() {
		$url = $this->fileUrl();
		$body = implode(',', $this->contexts);
		$response = $this->post($url, $body);
		if ($response->needRetry()) {
			$response = $this->post($url, $body);
		}
		if (!$response->ok()) {
			return array(null, new QiniuError($this->currentUrl, $response));
		}

		return array($response->json(), null);
	}

	private function post($url, $data) {
		$this->currentUrl = $url;
		$headers = array('Authorization' => 'UpToken ' . $this->upToken);

		return Client::post($url, $data, $headers);
	}

	private function blockSize($uploaded) {
		if ($this->size < $uploaded + Config::BLOCK_SIZE) {
			return $this->size - $uploaded;
		}

		return Config::BLOCK_SIZE;
	}
}


final class UploadManager {
	public function __construct() {}

	/**
	 * 上传二进制流到七牛
	 *
	 * @param $upToken    //上传凭证
	 * @param $key        //上传文件名
	 * @param $data       //上传二进制流
	 * @param $params     //自定义变量，规格参考
	 *                    http://developer.qiniu.com/docs/v6/api/overview/up/response/vars.html#xvar
	 * @param $mime       //上传数据的mimeType
	 * @param $checkCrc   //是否校验crc32
	 *
	 * @return array[]    包含已上传文件的信息，类似：
	 *                                              [
	 *                                                  "hash" => "<Hash string>",
	 *                                                  "key" => "<Key string>"
	 *                                              ]
	 */
	public function put($upToken, $key, $data, $params = null, $mime = 'application/octet-stream', $checkCrc = false) {
		$params = self::trimParams($params);

		return FormUploader::put($upToken, $key, $data, $params, $mime, $checkCrc);
	}


	/**
	 * 上传文件到七牛
	 *
	 * @param $upToken    //上传凭证
	 * @param $key        //上传文件名
	 * @param $filePath   //上传文件的路径
	 * @param $params     //自定义变量，规格参考
	 *                    http://developer.qiniu.com/docs/v6/api/overview/up/response/vars.html#xvar
	 * @param $mime       //上传数据的mimeType
	 * @param $checkCrc   //是否校验crc32
	 *
	 * @return array[]    包含已上传文件的信息，类似：
	 *                                              [
	 *                                                  "hash" => "<Hash string>",
	 *                                                  "key" => "<Key string>"
	 *                                              ]
	 */
	public function putFile($upToken, $key, $filePath, $params = null, $mime = 'application/octet-stream', $checkCrc = false) {
		$file = fopen($filePath, 'rb');
		if ($file === false) {
			throw new \Exception("file can not open", 1);
		}
		$params = self::trimParams($params);
		$stat = fstat($file);
		$size = $stat['size'];
		if ($size <= Config::BLOCK_SIZE) {
			$data = fread($file, $size);
			fclose($file);
			if ($data === false) {
				throw new \Exception("file can not read", 1);
			}

			return FormUploader::put(
				$upToken,
				$key,
				$data,
				$params,
				$mime,
				$checkCrc
			);
		}
		$up = new ResumeUploader(
			$upToken,
			$key,
			$file,
			$size,
			$params,
			$mime,
			$checkCrc
		);

		return $up->upload();
	}

	public static function trimParams($params) {
		if ($params == null) {
			return null;
		}
		$ret = array();
		foreach ($params as $k => $v) {
			$pos = strpos($k, 'x:');
			if ($pos === 0 && !empty($v)) {
				$ret[$k] = $v;
			}
		}

		return $ret;
	}
}