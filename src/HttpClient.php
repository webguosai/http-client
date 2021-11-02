<?php

namespace Webguosai;

/**
 * 创建自已使用的http请求轮子
 *
 * @package Webguosai
 *
 */
class HttpClient
{
    /** 请求 **/
    public $request = [
        'headers' => [
            //'User-Agent: guosai browser',
            //'X-HTTP-Method-Override: POST',
        ],
        'proxyIp' => '',
    ];

    /** 响应 **/
    //响应的内容(不作编码处理)
    public $body;
    //响应的http状态
    public $httpStatus;
    public $errorCode;
    public $contentType;

    /** 配置 **/
    public $options = [
        //超时
        'timeout'     => 3,

        //代理ip池
        'proxyIps'    => [],

        //允许重定向
        'redirects'   => false,
        'redirectMax' => 5,

        'cookieJarFile' => '',
    ];


    public function __construct($options)
    {
        $this->options = array_merge($this->options, $options);
        //var_dump($this->options); exit;
    }

    public function get($url, $data, $headers = [])
    {
        return $this->request($url, 'GET', $data, $headers);
    }

    public function post($url, $data, $headers = [])
    {
        return $this->request($url, 'POST', $data, $headers);
    }
    //这里好像必须是string
    public function put($url, $data = '', $headers = [])
    {
        $this->request['headers'][] = 'Content-Type: application/json';
        return $this->request($url, 'PUT', $data, $headers);
    }

    public function delete($url, $data, $headers = [])
    {
    }

    public function head($url, $data, $headers = [])
    {
    }

    public function options()
    {
    }

    public function request($url, $method = 'GET', $data, $headers = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->options['timeout']);//超时

        //代理
        if (!empty($this->options['proxyIps'])) {
            $this->request['proxyIp'] = array_rand($this->options['proxyIps']);
            curl_setopt($ch, CURLOPT_PROXY, $this->request['proxyIp']);
        }

        //当http状态为301 302重定向的时候。会进行跳转
        if ($this->options['redirects']) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

            //设置请求最多重定向的次数
            curl_setopt($ch, CURLOPT_MAXREDIRS, $this->options['redirectMax']);
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
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        //post
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
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

        $this->body        = $body;
        $this->httpStatus  = $httpCode;
        $this->errorCode   = $errorCode;
        $this->contentType = $contentType;
        $this->info        = $info;

        return $this;
    }

    public function json()
    {
        return @json_decode($this->body, true);
    }

    //解析请求的header头
    protected function parseHeaders($headers)
    {
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
                $this->request['headers'][] = $key . ':' . $value;
            } else {
                $this->request['headers'][] = $value;
            }
        }

        //去重，并移除key
        $this->request['headers'] = array_unique(array_values($this->request['headers']));
    }

    public function ok()
    {
        if ($this->errorCode === 0 && $this->httpStatus === 200) {
            return true;
        }
        return false;
    }

}
