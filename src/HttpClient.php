<?php

namespace Webguosai;

/**
 * Http客户端
 *
 * @package Webguosai
 *
 * @method put(string $url, string|array $data, string|array $headers)
 * @method delete(string $url, string|array $data, string|array $headers)
 * @method head(string $url, string|array $data, string|array $headers)
 * @method options(string $url, string|array $data, string|array $headers)
 */
class HttpClient
{
    private $version = '2.1.1';

    /** 请求 **/
    public $request = [
        'headers' => [
            //'User-Agent: guosai browser',
        ],
        'proxyIp' => '',
    ];

    /** 响应 **/
    public $body; //响应的body内容
    public $headers; //响应的header头信息
    public $httpStatus; //http状态
    public $errorCode = 0; //curl错误码
    public $contentType; //文档类型
    public $info;//所有信息

    /** 配置 **/
    public $options = [
        //超时
        'timeout'       => 3,

        //代理ip池
        'proxyIps'      => [],

        //允许重定向及重定向次数
        'redirects'     => false,
        'maxRedirect'   => 5,

        //保存cookie的文件路径
        'cookieJarFile' => '',

        //ca证书路径
        //下载：https://curl.se/ca/cacert.pem
        //'caFile' => __DIR__.'/../cacert/cacert.pem',
        'caFile'        => '',
    ];

    //是否设置过content-type头
    private $isSetContentType = false;

    //http状态列表
    private $httpStatusList = [
        // Informational 1xx
        100 => 'Continue',
        101 => 'Switching Protocols',

        // Success 2xx
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',

        // Redirection 3xx
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',  // 1.1
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        // 306 is deprecated but reserved
        307 => 'Temporary Redirect',

        // Client Error 4xx
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

        // Server Error 5xx
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        509 => 'Bandwidth Limit Exceeded'
    ];

    //curl错误列表
    private $curlErrorList = [
        0  => '正常访问',
        1  => '错误的协议',
        2  => '初始化代码失败',
        3  => 'URL格式不正确',
        4  => '请求协议错误',
        5  => '无法解析代理',
        6  => '无法解析主机地址',
        7  => '无法连接到主机',
        8  => '远程服务器不可用',
        9  => '访问资源错误',
        11 => 'FTP密码错误',
        13 => '结果错误',
        14 => 'FTP回应PASV命令',
        15 => '内部故障',
        17 => '设置传输模式为二进制',
        18 => '文件传输短或大于预期',
        19 => 'RETR命令传输完成',
        21 => '命令成功完成',
        22 => '返回正常',
        23 => '数据写入失败',
        25 => '无法启动上传',
        26 => '回调错误',
        27 => '内存分配请求失败',
        28 => '访问超时',
        30 => 'FTP端口错误',
        31 => 'FTP错误',
        33 => '不支持请求',
        34 => '内部发生错误',
        35 => 'SSL/TLS握手失败',
        36 => '下载无法恢复',
        37 => '文件权限错误',
        38 => 'LDAP可没有约束力',
        39 => 'LDAP搜索失败',
        41 => '函数没有找到',
        42 => '中止的回调',
        43 => '内部错误',
        45 => '接口错误',
        47 => '过多的重定向',
        48 => '无法识别选项',
        49 => 'TELNET格式错误',
        51 => '远程服务器的SSL证书',
        52 => '服务器无返回内容',
        53 => '加密引擎未找到',
        54 => '设定默认SSL加密失败',
        55 => '无法发送网络数据',
        56 => '衰竭接收网络数据',
        58 => '本地客户端证书',
        59 => '无法使用密码',
        60 => '凭证无法验证',
        61 => '无法识别的传输编码',
        62 => '无效的LDAP URL',
        63 => '文件超过最大大小',
        64 => 'FTP失败',
        65 => '倒带操作失败',
        66 => 'SSL引擎失败',
        67 => '服务器拒绝登录',
        68 => '未找到文件',
        69 => '无权限',
        70 => '超出服务器磁盘空间',
        71 => '非法TFTP操作',
        72 => '未知TFTP传输的ID',
        73 => '文件已经存在',
        74 => '错误TFTP服务器',
        75 => '字符转换失败',
        76 => '必须记录回调',
        77 => 'CA证书权限',
        78 => 'URL中引用资源不存在',
        79 => '错误发生在SSH会话',
        80 => '无法关闭SSL连接',
        81 => '服务未准备',
        82 => '无法载入CRL文件',
        83 => '发行人检查失败',
    ];

    public function __construct($options = [])
    {
        $this->options = array_merge($this->options, $options);
    }

    public function get(string $url, $data = [], $headers = [])
    {
        /** 拼接url中的get参数 **/
        if (!empty($data)) {
            if (is_string($data)) {
                parse_str($data, $data);
            }

            if (strpos($url, '?') === false) {
                $url .= '?' . http_build_query($data);
            } else {
                $url .= '&' . http_build_query($data);
            }
        }

        return $this->request($url, 'GET', [], $headers);
    }

    public function post(string $url, $data = [], $headers = [])
    {
        if (is_string($data)) {
            if (!is_null(json_decode($data))) {
                $this->setContentType('json');
            } else {
                $this->setContentType('x-www-form-urlencoded');
            }
        } elseif (is_array($data)) {
            $this->setContentType('form-data');
        } else {
            $this->setContentType('text');
        }

        return $this->request($url, 'POST', $data, $headers);
    }

    public function __call($name, $args)
    {
        if (in_array($name, ['put', 'delete', 'head', 'options'])) {
            $url     = $args[0];
            $method  = $name;
            $data    = empty($args[1]) ? [] : $args[1];
            $headers = empty($args[2]) ? [] : $args[2];

            if (is_array($data)) {
                $data = json_encode($data);
            }

            $this->setContentType('json');
            return $this->request($url, $method, $data, $headers);
        }
        return $this;
    }

    public function request(string $url, $method = 'GET', $data = [], $headers = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->options['timeout']);//超时

        //代理
        if (!empty($this->options['proxyIps'])) {
            $this->request['proxyIp'] = $this->options['proxyIps'][array_rand($this->options['proxyIps'])];
            curl_setopt($ch, CURLOPT_PROXY, $this->request['proxyIp']);
        }

        //当http状态为301 302重定向的时候。会进行跳转
        if ($this->options['redirects']) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

            //设置请求最多重定向的次数
            curl_setopt($ch, CURLOPT_MAXREDIRS, $this->options['maxRedirect']);
        }

        //cookie
        if ($this->options['cookieJarFile']) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $this->options['cookieJarFile']);//存放Cookie信息的文件名称
            curl_setopt($ch, CURLOPT_COOKIEFILE, $this->options['cookieJarFile']);//读取上面所储存的Cookie信息
        }

        //自定义headers
        if ($headers) {

            //转换一下header数据
            $this->parseHeaders($headers);

            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->request['headers']);
        }

        //设置请求方式
        $method = strtoupper($method);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($method == 'HEAD') {
            curl_setopt($ch, CURLOPT_NOBODY, true);
        }

        //post
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        //支持https
        if (file_exists($this->options['caFile'])) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_CAINFO, $this->options['caFile']);

            //证书
//            curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
//            curl_setopt($ch, CURLOPT_SSLCERT, $this->options['caFile']);
            //秘钥
            //curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
            //curl_setopt($ch, CURLOPT_SSLKEY, '');
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//不直接输出响应数据
        curl_setopt($ch, CURLOPT_ENCODING, '');//编码
        curl_setopt($ch, CURLOPT_HEADER, true);//获取header

        //发送请求
        $response = curl_exec($ch);

        //响应的curl错误代码、http状态、文档类型、info
        $errorCode   = curl_errno($ch);
        $httpCode    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $contentType = strtolower(explode(';', preg_replace("#\s#", '', $contentType))[0]);
        $info        = curl_getinfo($ch);

        //获取响应的header头、body数据
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers    = array_filter(explode("\r\n", substr($response, 0, $headerSize)));
        $body       = substr($response, $headerSize, strlen($response));
        //list($header, $body) = explode("\r\n\r\n", response, 2);

        $this->body        = $this->clearBom($body);
        $this->httpStatus  = $httpCode;
        $this->errorCode   = $errorCode;
        $this->contentType = $contentType;
        $this->headers     = $headers;
        $this->info        = $info;

        return $this;
    }

    /**
     * 解析json返回数组
     * @return mixed
     */
    public function json()
    {
        return @json_decode($this->body, true);
    }

    /**
     * 解析xml返回数组
     * @return mixed
     */
    public function xml()
    {
        libxml_disable_entity_loader(true);
        $xml = simplexml_load_string($this->body, 'SimpleXMLElement', LIBXML_NOCDATA);
        return json_decode(json_encode($xml), TRUE);
    }

    /**
     * curl没有错误、且http状态返回200表示成功
     * @return bool
     */
    public function ok()
    {
        if ($this->errorCode === 0 && $this->httpStatus === 200) {
            return true;
        }
        return false;
    }

    //获取编码
    public function getChatset()
    {
        $headerRex = '#charset=([^ ;,\r\n]+)#i';
        $htmlRex   = '#<meta[^>]+charset=["\']*?([^ "\'>]+)[^>]*>#i';

        if (preg_match($headerRex, $this->info['content_type'], $mat)) {
            //以header头的字符集优先
            $charset = trim($mat[1]);
        } elseif (preg_match($htmlRex, $this->body, $mat)) {
            //header头没有就判断html中的字符集标签
            $charset = trim($mat[1]);
        } else {
            //既没有在header中找到,也没有在body中找到
            return '';
        }

        //转为小写,并替换引号
        $charset = str_replace(array('"', "'"), array('', ''), strtolower($charset));

        //某些网站使用错误的别名
        if (in_array($charset, array('utf8')) !== false) {
            $charset = 'utf-8';
        }

        return $charset;
    }

    /**
     * 获取转码后的html
     * @param string $outCharset 编码
     * @return string
     */
    public function getHtml($outCharset = 'utf-8')
    {
        //请求的内容编码
        $charset = $this->getChatset();

        $body = $this->body;

        if (empty($charset)) {
            //如果没有从header、html标签中获取到字符集，则自动判断字符集并转码
            $body = $this->autoCharset($body, $outCharset);
        } elseif ($charset != $outCharset) {
            //指定的才转码，否则会出错
            if (in_array($charset, array('gbk', 'gb2312', 'iso-8859-1', 'us-ascii'))) {
                $body = iconv($charset, $outCharset . '//IGNORE', $body);
            }
        }

        //转换后，需要修改body中的<meta>编码标签
        $metaRex = '#(<meta[^>]+charset=["\']*?)([^ "\'>]+)([^>]*>)#i';// {/test/html/charset.html}
        if (preg_match($metaRex, $body)) {
            $body = preg_replace($metaRex, '${1}' . $outCharset . '${3}', $body);
        } else {
            //没有前端字符集标签(生成到head结束标签的前面)
            //$body = preg_replace('#</head>#i', "<meta charset=\"".$outCharset."\">\n$0", $body);
        }
        return $body;
    }

    /**
     * 判断是否为图片
     * @return bool
     */
    public function isImg()
    {
        //从文档中判断
        if (stripos($this->contentType, 'image/') !== false) {
            return true;
        }

        //从内容的前两个字节判断
        $strInfo  = @unpack("C2chars", substr($this->body, 0, 2));
        $typeCode = intval($strInfo['chars1'] . $strInfo['chars2']);
        if ($typeCode == 255216 /*jpg*/ || $typeCode == 7173 /*gif*/ || $typeCode == 13780 /*png*/) {
            return true;
        }
        return false;
    }

    /**
     * 错误信息
     * @return string
     */
    public function getErrorMsg()
    {
        if ($this->errorCode !== 0) {
            $curlMsg = $this->curlErrorList[$this->errorCode];
            return "curl错误: {$curlMsg}[{$this->errorCode}]";
        }

        if ($this->httpStatus !== 200) {
            $httpStatusMsg = $this->httpStatusList[$this->httpStatus];
            return "响应的http状态错误: {$httpStatusMsg}[{$this->httpStatus}]";
        }

        return '';
    }


    /**
     * 设置content-type类型
     * @param string $type (raw,json,form-data)
     * @return $this
     */
    protected function setContentType($type = 'raw')
    {
        /**
         * form_data = multipart/form-data
         * raw = text/plain
         *
         * x-www-form-urlencoded = application/x-www-form-urlencoded
         * json = application/json
         * xml = application/xml
         *
         */
        if (!$this->isSetContentType) {
            if ($type == 'form-data') {
                $contentType = 'multipart/form-data';
            } elseif (in_array($type, ['json', 'xml', 'x-www-form-urlencoded'])) {
                $contentType = 'application/' . $type;
            } else {
                $contentType = 'text/plain';
            }

            $this->appendHeaders([
                'Content-type: ' . $contentType,
            ]);

            $this->isSetContentType = true;
        }

        return $this;
    }

    /**
     * 自动转码
     *
     * @param string $str 要转码的内容
     * @param string $charset 转码后的编码
     * @return string
     */
    protected function autoCharset($str = '', $charset = 'UTF-8')
    {
        $mb_charset = mb_detect_encoding($str, array('UTF-8', 'GBK', 'LATIN1', 'BIG5', 'ISO-8859-1'));
        if (strtolower($mb_charset) != strtolower($charset)) {
            return mb_convert_encoding($str, $charset, $mb_charset);
        }
        return $str;
    }

    /**
     * 追加header数据
     * @param array $headers
     */
    protected function appendHeaders($headers = [])
    {
        foreach ($headers as $header) {
            //去重，并移除key
            $this->request['headers'][] = $header;
        }

        //去重，并移除key
        $this->request['headers'] = array_unique(array_values($this->request['headers']));
    }

    /**
     * 解析请求的header
     * @param $headers
     */
    protected function parseHeaders($headers)
    {
        $parseHeaders = [];

        //字符串先转换为数组(一般由浏览器复制而来)
        if (is_string($headers)) {
            $headers = array_map(function ($data) {
                return trim($data);
            }, explode("\n", $headers));
        }

        //数组，这里分两种情况
        //将所有header转换为curl能识别的格式
        foreach ($headers as $key => $value) {
            if (is_string($key)) {
                $parseHeaders[] = $key . ':' . $value;
            } else {
                $parseHeaders[] = $value;
            }
        }

        $this->appendHeaders($parseHeaders);
    }

    /**
     * 清除BOM头 (清除body头部中的utf-8签名)
     * @param string $html
     * @return string
     */
    protected function clearBom($html = '')
    {
        $bom = array(
            ord(substr($html, 0, 1)),
            ord(substr($html, 1, 1)),
            ord(substr($html, 2, 1))
        );

        if ($bom[0] == 239 && $bom[1] == 187 && $bom[2] == 191) {
            $html = substr($html, 3);
        }
        return $html;
    }

    /**
     * 是否为curl错误
     *
     * @return bool
     */
//    public function isCurlError()
//    {
//        if ($this->errorCode === 0) {
//            return false;
//        }
//        return true;
//    }

    /**
     * 是否为客户端错误 (http状态 400 ~ 500)
     *
     * @return bool
     */
//    public function isClientError()
//    {
//        return $this->httpStatus >= 400 && $this->httpStatus < 500;
//    }

    /**
     * 是否为服务端错误 (http状态 大于 500)
     *
     * @return bool
     */
//    public function isServerError()
//    {
//        return $this->httpStatus >= 500;
//    }

}
