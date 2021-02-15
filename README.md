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

## 使用方法
```php
use Webguosai;
$http = new HttpClient();
$response = $http->timeout(1)->get('http://www.baidu.com');
var_dump($response->body());

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
                    //withAutoLoadCookie('F:\www\la\jfl\app\Lib\Http\cookie.txt')->
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
```

## License

MIT
