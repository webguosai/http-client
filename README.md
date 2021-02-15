<h1 align="center">Laravel ocpc</h1>

<p align="center">
<a href="https://packagist.org/packages/webguosai/laravel-ocpc"><img src="https://poser.pugx.org/webguosai/laravel-ocpc/v/stable" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/webguosai/laravel-ocpc"><img src="https://poser.pugx.org/webguosai/laravel-ocpc/downloads" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/webguosai/laravel-ocpc"><img src="https://poser.pugx.org/webguosai/laravel-ocpc/v/unstable" alt="Latest Unstable Version"></a>
<a href="https://packagist.org/packages/webguosai/laravel-ocpc"><img src="https://poser.pugx.org/webguosai/laravel-ocpc/license" alt="License"></a>
</p>


## 运行环境

- php >= 7.0
- composer
- laravel >= 7.30

## 安装

```Shell
$ composer require webguosai/laravel-ocpc -vvv
```

### 添加 service provider（optional. if laravel < 5.5）

```PHP
// laravel < 5.5
Webguosai\LaravelOcpc\OcpcServiceProvider::class,
```

### 添加 alias（optional. if laravel < 5.5）

```PHP
'Ocpc' => Webguosai\LaravelOcpc\Facades\Ocpc::class,
```

### 配置文件

```Shell
$ php artisan vendor:publish --provider="Webguosai\LaravelOcpc\OcpcServiceProvider"
```


## 使用方法
```php
use Ocpc;

$a = Ocpc::test('guosaibaba');
return view('Ocpc::ocpc',['msg' => $a]);
```

## License

MIT
