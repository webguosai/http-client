<h1 align="center">http client</h1>

<p align="center">
<a href="https://packagist.org/packages/webguosai/http-client"><img src="https://poser.pugx.org/webguosai/http-client/v/stable" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/webguosai/http-client"><img src="https://poser.pugx.org/webguosai/http-client/downloads" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/webguosai/http-client"><img src="https://poser.pugx.org/webguosai/http-client/v/unstable" alt="Latest Unstable Version"></a>
<a href="https://packagist.org/packages/webguosai/http-client"><img src="https://poser.pugx.org/webguosai/http-client/license" alt="License"></a>
</p>


## 运行环境

- php >= 5.6
- composer

## 安装

```Shell
$ composer require webguosai/http-client -vvv
```

## 使用
### 初始化
```php
$options = [
    //超时(单位秒)
    'timeout'     => 3,

    //代理ip池(允许填写多个,会随机使用一组)
    'proxyIps'    => [
        //格式为【ip:端口】
        '0.0.0.0:8888'
    ],

    //重定向、及最多重定向跳转次数
    'redirects'   => false,
    'maxRedirect' => 5,
    
    //cookie自动保存路径
    'cookieJarFile' => 'cookie.txt',

    //ca证书路径
    'caFile'        => __DIR__.'/cacert/cacert.pem',
];
$http = new \Webguosai\HttpClient($options);
```

### 请求
```php
$headers = [
    'User-Agent' => 'http-client browser',
    'cookie' => 'login=true'
];
$data = ['data' => '111', 'data2' => '222'];

//所有方法
$response = $http->get($url, $data, $headers);
$response = $http->post($url, $data, $headers);
$response = $http->put($url, $data, $headers);
$response = $http->delete($url, $data, $headers);
$response = $http->head($url, $data, $headers);
$response = $http->options($url, $data, $headers);
```

### 响应
```php
$response->request; //请求
$response->headers; //响应头
$response->body; //响应body
$response->httpStatus; //http状态码
$response->contentType; //内容类型
$response->info; //其它信息
$response->info['url'];//最终请求的地址
$response->getHtml(); //获取html
$response->getChatset(); //编码
$response->json(); //json
$response->xml(); //xml
$response->ok();//http=200返回真
$response->getErrorMsg(); //错误信息
```

### data 传值方式
```php
// multipart/form-data
$data = ['data' => '111', 'data2' => '222'];

// application/x-www-form-urlencoded
$data = http_build_query($data); 

// application/json
$data = json_encode($data); 

$response = $http->post($url, $data);
```

### headers 传值方式
```php
//数组传递 
$headers = [
    'User-Agent: chrome',
    'User-Agent' => 'chrome',
];

//纯字符串 (一般为从浏览器复制)
$headers = 'User-Agent: chrome
Referer: https://www.x.com
Cookie: cookie=6666666';

$response = $http->post($url, $data, $headers);
```

## 实操
```php
$options = [
    'timeout'   => 3,
];
$http    = new \Webguosai\HttpClient($options);
$response = $http->get('http://www.baidu.com');
if ($response->ok()) {
    var_dump($response->body);
    //var_dump($response->json());
} else {
    var_dump($response->getErrorMsg());
}
```

## License

MIT
