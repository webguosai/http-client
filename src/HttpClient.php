<?php

namespace Webguosai\HttpClient;

/**
 * ��������ʹ�õ�http��������
 * Class Http
 * @package Webguosai\HttpClient
 *
 * ʹ�ã�
 *
//��ʱ
timeout(1)
//ʹ��headerͷ
withHeaders(['user-agent' => 'chrome'])
//ʹ�ô���ip
withProxy($proxy)
//�����ض���
withoutRedirecting()
//�Զ�����cookie
withAutoLoadCookie()
//ת������
withIconv
//����post��ʽ���ĵ�����
asForm()
//����json��ʽ���ĵ�����
asJson()
//���ʹ��ı���ʽ���ĵ�����
asPlain()
//����get����
get($url)
//����post����
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

//״̬���
dump($response->ok());//curl�޴�����http״̬Ϊ200
dump($response->curlError());//curl����
dump($response->errorMsg());//������Ϣ
dump($response->clientError());//http��400 - 500֮�䣬����true
dump($response->serverError());//http���� 500������true
dump($response->responseContentType);//��Ӧ���ĵ�����
dump($response->responseErrorCode); //curl״̬��

//��Ӧ
dump($response->charset());//Ŀ����վ�ı���
dump($response->body());//body
dump($response->json());//����json����
dump($response->headers());//��Ӧ��headerͷ
dump($response->response());//��Ӧ������
dump($response->httpStatus()); //��Ӧ��http״̬
dump($response->info()); //��Ӧ����������
 */
class HttpClient
{
    protected $version = '1.0';
    //��ʱ(0��ʾ������)
    protected $timeOut = 5;
    //header����
    protected $headers = [];
    //post����
    protected $post = [];
    //�Ƿ������ض���
    protected $redirect = false;
    //��������ض���Ĵ���
    protected $redirectMaxNum = 5;
    //�Զ�����cookie
    protected $autoLoadCookie = false;
    //�Զ�����cookie�󱣴��Ŀ¼
    protected $cookieSavePath = '';
    //��������
    protected $proxy = '';
    //�Ƿ�ת������
    protected $isIconv = false;
    //��ǰ����
    protected $defaultCharset = 'utf-8';

    //��Ӧ������
    protected $response;
    //��Ӧ����Ϣ
    protected $responseInfo;
    //��Ӧ��body����
    protected $responseBody;
    //��Ӧ��headerͷ
    protected $responseHeaders;
    //��Ӧ��http״̬
    protected $responseHttpStatus;
    //curl���ص�״̬��
    public $responseErrorCode = 0;
    //��Ӧ���ĵ�����
    public $responseContentType;

    /**
     * GET����
     */
    public function get(string $url)
    {
        $this->send($url);
        return $this;
    }

    /**
     * POST����
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

    //����headerͷ
    public function withHeaders($headers)
    {
        if (is_string($headers)) {
            //�ַ���(һ���Ǵ�������и���)
            $headers = explode("\n", $headers);
        }

        //������headerת��Ϊ:['user-agent' => 'chrome'] ���ָ�ʽ
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

    //�����ض���
    public function withoutRedirecting()
    {
        $this->redirect = true;
        return $this;
    }

    //ʹ�ô���ip
    public function withProxy($proxy)
    {
        $this->proxy = $proxy;
        return $this;
    }

    //�Զ�����cookie
    public function withAutoLoadCookie($cookiePath)
    {
        //if (file_exists($cookiePath)) {
        if ($cookiePath) {
            $this->autoLoadCookie = true;
            $this->cookieSavePath = $cookiePath;
        }

        return $this;
    }

    //�Զ�ת������
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

        //�Զ�ת������
        if ($this->isIconv) {

            $charset = $this->charset();
            //var_dump($charset);

            if (empty($charset)) {
                //���û�д�header��html��ǩ�л�ȡ���ַ��������Զ��ж��ַ�����ת��
                $body = $this->autoCharset($body, $this->defaultCharset);
            } elseif ($charset != $this->defaultCharset) {
                //����Ĭ�ϵ��ַ�������ת��{test/tools/curl_char/}

                //var_dump($content_char);exit;
                //����һ��iconvֻ�����ת���ַ���
                //mb_list_encodings()�ɴ�ӡ��php�����ַ���
                //$allows = array_map('strtolower',mb_list_encodings());
                //var_dump($allows);exit;
                //$allows[] = 'gb2312';//�������ַ�����:UTF-8��GBK��GB2312��*ISO-8859-1��*UTF-16
                //if (in_array($content_char, $allows)) {
                //��ʼת��,
                //gbk gb2312,iso-8859-1,us-ascii
                //ָ���Ĳ�ת�룬��������
                if (in_array($charset, array('gbk', 'gb2312', 'iso-8859-1', 'us-ascii'))) {
                    $body = iconv($charset, $this->defaultCharset . '//IGNORE', $body);
                }

                //}
            }

            //ת������Ҫ�޸�body�е�<meta>�����ǩ
            $metaRex = '#(<meta[^>]+charset=["\']*?)([^ "\'>]+)([^>]*>)#i';// {/test/html/charset.html}
            if (preg_match($metaRex, $body)) {
                $body = preg_replace($metaRex, '${1}' . $this->defaultCharset . '${3}', $body);
            } else {
                //û��ǰ���ַ�����ǩ(���ɵ�head������ǩ��ǰ��)
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

    //��ȡ�ĵ�����
    public function charset()
    {
        $headerRex = '#charset=([^ ;,\r\n]+)#i';
        $htmlRex   = '#<meta[^>]+charset=["\']*?([^ "\'>]+)[^>]*>#i';

        if (preg_match($headerRex, $this->responseContentType, $mat)) {
            //��headerͷ���ַ�������
            $charset = trim($mat[1]);
        } elseif (preg_match($htmlRex, $this->responseBody, $mat)) {
            //headerͷû�о��ж�html�е��ַ�����ǩ
            $charset = trim($mat[1]);
        } else {
            //��û����header���ҵ�,Ҳû����body���ҵ�
            return '';
        }

        //תΪСд,���滻����
        $charset = str_replace(array('"', "'"), array('', ''), strtolower($charset));

        //ĳЩ��վʹ�ô���ı���
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


    //�������͵�headerͷ
    public function parseHeaders()
    {
        //������headerת��Ϊcurl��ʶ��ĸ�ʽ���磺
        // [0=>'user-agent:chrome', 1=>...]
        foreach ($this->headers as $key => $value) {
            if (is_string($key)) {
                $headers[$key] = $key . ':' . $value;
            }
        }

        //ȥ�أ����Ƴ�key
        $headers = array_unique(array_values($headers));

        $this->headers = $headers;
        //return $headers;
    }

    //curlû�д�����http״̬����200��ʾ�ɹ�
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
            return "curl����: {$curlMsg}[{$this->responseErrorCode}]";
        }

        if ($this->responseHttpStatus !== 200) {
            $httpStatusMsg = $this->httpStatusList()[$this->responseHttpStatus];
            return "��Ӧ��http״̬����: {$httpStatusMsg}[{$this->responseHttpStatus}]";
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

    //http ���� 500
    public function serverError()
    {
        return $this->httpStatus() >= 500;
    }


    public function send($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeOut);//��ʱ

        //����
        if ($this->proxy) {
            //var_dump( $this->proxy);exit;
            curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
        }

        //�������ʶ
        //curl_setopt($ch, CURLOPT_USERAGENT, $params['user_agent']);

        //��Դ
        //curl_setopt($ch, CURLOPT_REFERER, $params['referer']);

        //��http״̬Ϊ301 302�ض����ʱ�򡣻������ת
        if ($this->redirect) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            //������������ض���Ĵ���
            curl_setopt($ch, CURLOPT_MAXREDIRS, $this->redirectMaxNum);
        }

        //cookie
        if ($this->autoLoadCookie) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieSavePath);//���Cookie��Ϣ���ļ�����
            curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieSavePath);//��ȡ�����������Cookie��Ϣ
        }

        //�Զ���headers
        if ($this->headers) {

            //ת��һ��header����
            $this->parseHeaders();
            //var_dump($this->headers);

            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        }

        //post
        if ($this->post) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->post);
        }

        //֧��https
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//��ֱ�������Ӧ����
        curl_setopt($ch, CURLOPT_ENCODING, '');//����
        curl_setopt($ch, CURLOPT_HEADER, true);//��ȡheader

        //��������
        $response = curl_exec($ch);

        //��Ӧ��curl������롢http״̬���ĵ����͡�info
        $errorCode   = curl_errno($ch);
        $httpCode    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $info        = curl_getinfo($ch);

        //��ȡ��Ӧ��headerͷ��body����
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
     * �Զ�ת��
     *
     * @access     public
     * @param string $str Ҫת�������
     * @param string $charset ת���ı���
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
            0  => '��������',
            1  => '�����Э��',
            2  => '��ʼ������ʧ��',
            3  => 'URL��ʽ����ȷ',
            4  => '����Э�����',
            5  => '�޷���������',
            6  => '�޷�����������ַ',
            7  => '�޷����ӵ�����',
            8  => 'Զ�̷�����������',
            9  => '������Դ����',
            11 => 'FTP�������',
            13 => '�������',
            14 => 'FTP��ӦPASV����',
            15 => '�ڲ�����',
            17 => '���ô���ģʽΪ������',
            18 => '�ļ�����̻����Ԥ��',
            19 => 'RETR��������',
            21 => '����ɹ����',
            22 => '��������',
            23 => '����д��ʧ��',
            25 => '�޷������ϴ�',
            26 => '�ص�����',
            27 => '�ڴ��������ʧ��',
            28 => '���ʳ�ʱ',
            30 => 'FTP�˿ڴ���',
            31 => 'FTP����',
            33 => '��֧������',
            34 => '�ڲ���������',
            35 => 'SSL/TLS����ʧ��',
            36 => '�����޷��ָ�',
            37 => '�ļ�Ȩ�޴���',
            38 => 'LDAP��û��Լ����',
            39 => 'LDAP����ʧ��',
            41 => '����û���ҵ�',
            42 => '��ֹ�Ļص�',
            43 => '�ڲ�����',
            45 => '�ӿڴ���',
            47 => '������ض���',
            48 => '�޷�ʶ��ѡ��',
            49 => 'TELNET��ʽ����',
            51 => 'Զ�̷�������SSL֤��',
            52 => '�������޷�������',
            53 => '��������δ�ҵ�',
            54 => '�趨Ĭ��SSL����ʧ��',
            55 => '�޷�������������',
            56 => '˥�߽�����������',
            58 => '���ؿͻ���֤��',
            59 => '�޷�ʹ������',
            60 => 'ƾ֤�޷���֤',
            61 => '�޷�ʶ��Ĵ������',
            62 => '��Ч��LDAP URL',
            63 => '�ļ���������С',
            64 => 'FTPʧ��',
            65 => '��������ʧ��',
            66 => 'SSL����ʧ��',
            67 => '�������ܾ���¼',
            68 => 'δ�ҵ��ļ�',
            69 => '��Ȩ��',
            70 => '�������������̿ռ�',
            71 => '�Ƿ�TFTP����',
            72 => 'δ֪TFTP�����ID',
            73 => '�ļ��Ѿ�����',
            74 => '����TFTP������',
            75 => '�ַ�ת��ʧ��',
            76 => '�����¼�ص�',
            77 => 'CA֤��Ȩ��',
            78 => 'URL��������Դ������',
            79 => '��������SSH�Ự',
            80 => '�޷��ر�SSL����',
            81 => '����δ׼��',
            82 => '�޷�����CRL�ļ�',
            83 => '�����˼��ʧ��',
        );
    }

}
