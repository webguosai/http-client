<?php

//报告E_NOTICE之外的所有错误(可解决变量不存在导致的错误，如GET、POST数据)
error_reporting(E_ALL & ~E_NOTICE);

/* 跳转的测试 */
//$a = empty($_GET['a']) ? 0 : intval($_GET['a']);
//if ($a <= 5) {
//    $url = "?a=".($a+1);
//    header("Location: {$url}");
//    echo '要跳转到'.$url;
//    exit;
//}

/** cookie设置 **/
if (!$_COOKIE['cookie']) {
    setcookie('cookie', 'cookie666');
}

/** http状态 **/
//header("HTTP/1.0 404 Not Found");
//header("HTTP/1.0 502 Bad Gateway");

$data = [
    'method'  => $_SERVER['REQUEST_METHOD'],
    'headers' => getAllHeaders(),
    'get'     => $_GET,
    'post'    => $_POST,
    'put'     => json_decode(file_get_contents('php://input'), true),
    'request' => $_REQUEST,
    'file'    => $_FILES,
    'cookie'  => $_COOKIE,
    'ip'      => $_SERVER['REMOTE_ADDR'],
    'php_input' => file_get_contents('php://input'),
    'php_input_json' => json_decode(file_get_contents('php://input'), true),
    'server'  => $_SERVER,
];

//output json
//header('Content-type: application/json; charset=utf-8');
//echo json_encode($data);

//output xml
echo <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<note>
    <code>0</code>
    <message>ok</message>
    <data>
        
    </data>
</note>
EOF;




//function getAllHeaders2()
//{
//    foreach ($_SERVER as $name => $value)
//    {
//        if (substr($name, 0, 5) == 'HTTP_')
//        {
//            $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
//        }
//    }
//    return $headers;
//}