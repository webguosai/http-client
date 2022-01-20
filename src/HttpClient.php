<?php

namespace Webguosai;

use Webguosai\Http\Consts;

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
    /** 请求 **/
    public $request = [
        'headers' => [],
        'proxyIp' => '',
    ];

    protected $initHeaders = [];
    //自定义的header头
    protected $customHeaders = [
        'User-Agent: guosai browser',
    ];

    /** 响应 **/
    public $body; //响应的body内容
    public $headers; //响应的header头信息
    public $httpStatus; //http状态
    public $errorCode = 0; //curl错误码
    public $contentType; //文档类型
    public $info;//所有信息
//    public $isUpload = false;

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
        $this->handleHeaders($headers);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->request['headers']);

        //设置请求方式
        $method = strtoupper($method);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($method == 'HEAD') {
            curl_setopt($ch, CURLOPT_NOBODY, true);
        }

//        if ($this->isUpload) {
//            curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);
//        }

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
            $curlMsg = Consts::CURL_ERRORS[$this->errorCode];
            return "curl错误: {$curlMsg}[{$this->errorCode}]";
        }

        if ($this->httpStatus !== 200) {
            $httpStatusMsg = Consts::HTTP_STATUS[$this->httpStatus];
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
        if ($type == 'form-data') {
            $contentType = 'multipart/form-data';
        } elseif (in_array($type, ['json', 'xml', 'x-www-form-urlencoded'])) {
            $contentType = 'application/' . $type;
        } else {
            $contentType = 'text/plain';
        }

        $this->initHeaders([
            'Content-type: ' . $contentType,
        ]);


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

    //处理headers
    protected function handleHeaders($headers)
    {
        // 解析填写的headers
        $parseHeaders = $this->parseHeaders($headers);

        $this->request['headers'] = [];
        foreach ($this->initHeaders as $header) {
            $this->request['headers'][] = $header;
        }

        foreach ($parseHeaders as $header) {
            $this->request['headers'][] = $header;
        }

    }

    //初始化header,将默认的header和代码内部加入的header头合并
    protected function initHeaders($headers)
    {
        $this->initHeaders = $this->customHeaders;

        foreach ($headers as $header) {
            $this->initHeaders[] = $header;
        }
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

        return $parseHeaders;
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
