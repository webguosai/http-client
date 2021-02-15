<?php

namespace Webguosai\HttpClient;

/**
 * 创建自已使用的http请求轮子
 * Class Http
 * @package Webguosai\HttpClient
 *
 * 使用：
 *
//超时
timeout(1)
//使用header头
withHeaders(['user-agent' => 'chrome'])
//使用代理ip
withProxy($proxy)
//允许重定向
withoutRedirecting()
//自动加载cookie
withAutoLoadCookie()
//转换编码
withIconv
//发送post格式的文档类型
asForm()
//发送json格式的文档类型
asJson()
//发送纯文本格式的文档类型
asPlain()
//发送get请求
get($url)
//发送post请求
post($url, ['username' => '1111'])
$response = $http->timeout(1)->
                    withHeaders($headers)->
                    withProxy($proxy)->
                    withoutRedirecting()->
                    //withAutoLoadCookie('cookie.txt')->
                    withIconv()->
                    get($url);
                    //asForm()->
                    //post($url, $posts);

//状态检测
dump($response->ok());//curl无错误且http状态为200
dump($response->curlError());//curl错误
dump($response->errorMsg());//错误信息
dump($response->clientError());//http在400 - 500之间，返回true
dump($response->serverError());//http大于 500，返回true
dump($response->responseContentType);//响应的文档类型
dump($response->responseErrorCode); //curl状态码

//响应
dump($response->charset());//目标网站的编码
dump($response->body());//body
dump($response->json());//返回json数组
dump($response->headers());//响应的header头
dump($response->response());//响应的内容
dump($response->httpStatus()); //响应的http状态
dump($response->info()); //响应的其它数据
 */
class HttpClient
{
    protected $version = '1.0';
    //超时(0表示无限制)
    protected $timeOut = 5;
    //header数据
    protected $headers = [];
    //post数据
    protected $post = [];
    //是否允许重定向
    protected $redirect = false;
    //请求最多重定向的次数
    protected $redirectMaxNum = 5;
    //自动加载cookie
    protected $autoLoadCookie = false;
    //自动加载cookie后保存的目录
    protected $cookieSavePath = '';
    //代理数据
    protected $proxy = '';
    //是否转换编码
    protected $isIconv = false;
    //当前编码
    protected $defaultCharset = 'utf-8';

    //响应的内容
    protected $response;
    //响应的信息
    protected $responseInfo;
    //响应的body内容
    protected $responseBody;
    //响应的header头
    protected $responseHeaders;
    //响应的http状态
    protected $responseHttpStatus;
    //curl返回的状态码
    public $responseErrorCode = 0;
    //响应的文档类型
    public $responseContentType;

    /**
     * GET请求
     */
    public function get(string $url)
    {
        $this->send($url);
        return $this;
    }

    /**
     * POST请求
     */
    public function post(string $url, $post)
    {
        if (is_array($post)) {
            $post = http_build_query($post);
        }

        $this->post = $post;

        $this->send($url);
        return $this;
    }

    public function timeout($second)
    {
        $this->timeOut = $second;
        return $this;
    }

    //加载header头
    public function withHeaders($headers)
    {
        if (is_string($headers)) {
            //字符串(一般是从浏览器中复制)
            $headers = explode("\n", $headers);
        }

        //将所有header转换为:['user-agent' => 'chrome'] 这种格式
        foreach ($headers as $key => $value) {
            if (is_int($key)) {
                if (stripos($value, ':') !== false) {
                    list($a, $b) = explode(":", $value);
                    unset($headers[$key]);
                    $headers[$a] = $b;
                }
            }
        }
        //var_dump($headers);

        $this->headers = array_merge($headers, $this->headers);
        return $this;
    }

    //允许重定向
    public function withoutRedirecting()
    {
        $this->redirect = true;
        return $this;
    }

    //使用代理ip
    public function withProxy($proxy)
    {
        $this->proxy = $proxy;
        return $this;
    }

    //自动载入cookie
    public function withAutoLoadCookie($cookiePath)
    {
        //if (file_exists($cookiePath)) {
        if ($cookiePath) {
            $this->autoLoadCookie = true;
            $this->cookieSavePath = $cookiePath;
        }

        return $this;
    }

    //自动转换编码
    public function withIconv()
    {
        $this->isIconv = true;
        return $this;
    }

    public function asForm()
    {
        $this->withHeaders(['Content-Type' => 'application/x-www-form-urlencoded']);
        return $this;
    }

    public function asJson()
    {
        $this->withHeaders(['Content-Type' => 'application/json']);
        return $this;
    }

    public function asPlain()
    {
        $this->withHeaders(['Content-Type' => 'text/plain']);
        return $this;
    }

    public function body()
    {
        $body = $this->responseBody;

        //自动转换编码
        if ($this->isIconv) {

            $charset = $this->charset();
            //var_dump($charset);

            if (empty($charset)) {
                //如果没有从header、html标签中获取到字符集，则自动判断字符集并转码
                $body = $this->autoCharset($body, $this->defaultCharset);
            } elseif ($charset != $this->defaultCharset) {
                //不是默认的字符集，再转换{test/tools/curl_char/}

                //var_dump($content_char);exit;
                //过滤一下iconv只允许的转换字符集
                //mb_list_encodings()可打印出php所有字符集
                //$allows = array_map('strtolower',mb_list_encodings());
                //var_dump($allows);exit;
                //$allows[] = 'gb2312';//常见的字符集有:UTF-8，GBK，GB2312，*ISO-8859-1，*UTF-16
                //if (in_array($content_char, $allows)) {
                //开始转码,
                //gbk gb2312,iso-8859-1,us-ascii
                //指定的才转码，否则会出错
                if (in_array($charset, array('gbk', 'gb2312', 'iso-8859-1', 'us-ascii'))) {
                    $body = iconv($charset, $this->defaultCharset . '//IGNORE', $body);
                }

                //}
            }

            //转换后，需要修改body中的<meta>编码标签
            $metaRex = '#(<meta[^>]+charset=["\']*?)([^ "\'>]+)([^>]*>)#i';// {/test/html/charset.html}
            if (preg_match($metaRex, $body)) {
                $body = preg_replace($metaRex, '${1}' . $this->defaultCharset . '${3}', $body);
            } else {
                //没有前端字符集标签(生成到head结束标签的前面)
                //$body = preg_replace('#</head>#i', "<meta charset=\"".$this->defaultCharset."\">\n$0", $body);
            }
        }

        return $body;
    }

    public function json()
    {
        $json = json_decode($this->body(), true);

        return $json;
    }

    public function headers()
    {
        return $this->responseHeaders;
    }

    public function httpStatus()
    {
        return $this->responseHttpStatus;
    }

    //获取文档编码
    public function charset()
    {
        $headerRex = '#charset=([^ ;,\r\n]+)#i';
        $htmlRex   = '#<meta[^>]+charset=["\']*?([^ "\'>]+)[^>]*>#i';

        if (preg_match($headerRex, $this->responseContentType, $mat)) {
            //以header头的字符集优先
            $charset = trim($mat[1]);
        } elseif (preg_match($htmlRex, $this->responseBody, $mat)) {
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

    public function response()
    {
        return $this->response;
    }

    public function info()
    {
        return $this->responseInfo;
    }


    //解析发送的header头
    public function parseHeaders()
    {
        //将所有header转换为curl能识别的格式，如：
        // [0=>'user-agent:chrome', 1=>...]
        foreach ($this->headers as $key => $value) {
            if (is_string($key)) {
                $headers[$key] = $key . ':' . $value;
            }
        }

        //去重，并移除key
        $headers = array_unique(array_values($headers));

        $this->headers = $headers;
        //return $headers;
    }

    //curl没有错误、且http状态返回200表示成功
    public function ok()
    {
        if ($this->responseErrorCode === 0 && $this->responseHttpStatus === 200) {
            return true;
        }
        return false;
    }

    public function errorMsg()
    {
        if ($this->responseErrorCode !== 0) {
            $curlMsg = $this->curlCodeList()[$this->responseErrorCode];
            return "curl错误: {$curlMsg}[{$this->responseErrorCode}]";
        }

        if ($this->responseHttpStatus !== 200) {
            $httpStatusMsg = $this->httpStatusList()[$this->responseHttpStatus];
            return "响应的http状态错误: {$httpStatusMsg}[{$this->responseHttpStatus}]";
        }

        return '';
    }

    public function curlError()
    {
        if ($this->responseErrorCode === 0) {
            return false;
        }
        return true;
    }

    //http 400 - 500
    public function clientError()
    {
        return $this->httpStatus() >= 400 && $this->httpStatus() < 500;
    }

    //http 大于 500
    public function serverError()
    {
        return $this->httpStatus() >= 500;
    }


    public function send($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeOut);//超时

        //代理
        if ($this->proxy) {
            //var_dump( $this->proxy);exit;
            curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
        }

        //浏览器标识
        //curl_setopt($ch, CURLOPT_USERAGENT, $params['user_agent']);

        //来源
        //curl_setopt($ch, CURLOPT_REFERER, $params['referer']);

        //当http状态为301 302重定向的时候。会进行跳转
        if ($this->redirect) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            //设置请求最多重定向的次数
            curl_setopt($ch, CURLOPT_MAXREDIRS, $this->redirectMaxNum);
        }

        //cookie
        if ($this->autoLoadCookie) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieSavePath);//存放Cookie信息的文件名称
            curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieSavePath);//读取上面所储存的Cookie信息
        }

        //自定义headers
        if ($this->headers) {

            //转换一下header数据
            $this->parseHeaders();
            //var_dump($this->headers);

            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        }

        //post
        if ($this->post) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->post);
        }

        //支持https
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//不直接输出响应数据
        curl_setopt($ch, CURLOPT_ENCODING, '');//编码
        curl_setopt($ch, CURLOPT_HEADER, true);//获取header

        //发送请求
        $response = curl_exec($ch);

        //响应的curl错误代码、http状态、文档类型、info
        $errorCode   = curl_errno($ch);
        $httpCode    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $info        = curl_getinfo($ch);

        //获取响应的header头、body数据
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers    = array_filter(explode("\r\n", substr($response, 0, $headerSize)));
        $body       = substr($response, $headerSize, strlen($response));
        //list($header, $body) = explode("\r\n\r\n", response, 2);

        $this->response            = $response;
        $this->responseBody        = $body;
        $this->responseHeaders     = $headers;
        $this->responseContentType = $contentType;
        $this->responseHttpStatus  = $httpCode;
        $this->responseErrorCode   = $errorCode;
        $this->responseInfo        = $info;
    }

    /**
     * 自动转码
     *
     * @access     public
     * @param string $str 要转码的内容
     * @param string $charset 转码后的编码
     * @return     string
     */
    protected function autoCharset($str, $charset = 'UTF-8')
    {
        $mb_charset = mb_detect_encoding($str, array('UTF-8', 'GBK', 'LATIN1', 'BIG5', 'ISO-8859-1'));
        if (strtolower($mb_charset) != strtolower($charset)) {
            return mb_convert_encoding($str, $charset, $mb_charset);
        }
        return $str;
    }

    protected function httpStatusList()
    {
        return array(
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
        );
    }

    protected function curlCodeList()
    {
        return array(
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
        );
    }

}
