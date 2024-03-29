<?php

require_once '../vendor/autoload.php';

// 使用socks5
$url = 'https://www.google.com.hk/';
$response = (new \Webguosai\HttpClient([
    'timeout' => 5,
    'useSocks5' => true,
    'proxyIps' => ['127.0.0.1:9527']
]))->get($url);

if ($response->ok()) {
    dump($response->body);
} else {
    dump($response->getErrorMsg());
}


exit;


$client  = new \Webguosai\HttpClient([
    'timeout' => 5,
]);

$url = 'http://test.com/test/http-client/test/server.php?a=1';
$headers = 'baba:111';
$response = $client->post($url, ['a' => 'aaa'], $headers);

dump($response->request['headers']);


$headers = [
    'baba' => 222
];
$response = $client->post($url, json_encode(['a' => 'bbb']), $headers);
dump($response->request['headers']);


exit;

$options = [
    'timeout'   => 10,
    'redirects' => true,
    'maxRedirect' => 10,
    'proxyIps'    => [
        //'163.125.114.179:8088'
    ],
    //'cookieJarFile' => 'D:\www\test\http-client\example\cookie.txt',
];
$http    = new \Webguosai\HttpClient($options);

$url = 'http://test.com/test/http-client/test/server.php?a=1';
//$url = 'http://test.com/test/task/test/charset/bom/';
//$url = 'http://test.com/test/task/test/charset/gbk/';
//$url = 'http://127.0.0.1:10453/think';
//$url = 'http://waophp.com/api/test';
//$url = 'https://www.qq.com/';
//$url = 'https://www.kuaidaili.com/free/';
//$url = 'https://www.kancloud.cn/';

//数组传递
$headers = [
    'User-Agent: my browser',//重复的
    'Diy1: 111',//无key的解析
    'Diy2' => '222',

    'Cookie: cookie=6666666',
];
//纯字符串(\r\n)
$headers2 = 'Accept-Language: zh-CN,zh;q=0.9
Cache-Control: max-age=0
Connection: keep-alive';
//纯字符串(\n)
$headers3 = "Accept-Language: zh-CN,zh;q=0.9\nCache-Control: max-age=0\nConnection: keep-alive";

$response = $http->get($url, ['get'=>'111'], $headers);
//$response = $http->get($url, 'get=111&get2=222', $headers);

//$response = $http->post($url, ['post'=>'111'], $headers);
//$response = $http->post($url, '{"post":"222"}', $headers);
//$response = $http->post($url, 'post1=111&post2=222', $headers);
//$response = $http->post($url, '{"post":"222"}', $headers);

//$response = $http->put($url, ['put' => '111'], $headers);
//$response = $http->put($url, '{"put":"111"}', $headers);
//$response = $http->put($url, '{"put":"111"}', $headers);

//$response = $http->delete($url, ['delete' => '111'], $headers);
//$response = $http->delete($url, '{"delete":"111"}', $headers);

//$response = $http->head($url, ['head' => '111'], $headers);
//$response = $http->head($url, '{"head":"111"}', $headers);

//$response = $http->options($url, ['options' => '111'], $headers);
//$response = $http->options($url, '{"options":"111"}', $headers);

/**
 * form_data = multipart/form-data
 * x-www-form-urlencoded = application/x-www-form-urlencoded
 * json = application/json
 * xml = application/xml
 * raw = text/plain
 */

//dump($response->request); //请求
//dump($response->headers); //响应头
dump($response->body); //响应body
//dump($response->httpStatus); //http状态码
//dump($response->contentType); //内容类型
//dump($response->info); //其它信息
//dump($response->info['url']);//最终请求的地址
dump($response->getHtml()); //获取html
//dump($response->getChatset()); //编码
//dump($response->json()); //json
//dump($response->xml()); //xml
//dump($response->ok());//http=200返回真
//dump($response->getErrorMsg()); //错误信息





