# Wsdebug
>Wsdebug for Hyperf

## 安装组件:
>composer require firstphp/wsdebug

## 安装 WebSocket 服务:
>[详见 Hyperf 官方文档](https://doc.hyperf.io/#/zh/websocket-server)

## 发布配置:
>php bin/hyperf.php vendor:publish firstphp/wsdebug

## 注意事项：
-配置文件路径：/web/hyperf/config/autoload/wsdebug.php
-默认地址是 ws://127.0.0.1:9505 ，需修改成服务实际配置地址

## 使用方法：
>1.通过自定义路由 Router 添加输出页面

编辑路由文件 /hyperf/config/routes.php ，添加如下内容：

```php
Router::addRoute(['GET', 'POST', 'HEAD'], '/wsdebug', function() {
    $wsdebug = new \Firstphp\Wsdebug\Wsdebug();
    $response = new \Hyperf\HttpServer\Response();
    return $response->raw($wsdebug->getHtml())->withHeader('content-type', 'text/html; charset=utf-8');
});

Router::addServer('ws', function () {
    Router::get('/', Firstphp\Wsdebug\Wsdebug::class);
});
```

>2.Hyperf Demo
```php
<?php
namespace App\HttpController;

use Hyperf\Di\Annotation\Inject;
use Firstphp\Wsdebug\Wsdebug;

class TestContrller 
{
    /**
     * @Inject
     * @var Wsdebug
     */
	protected $debug;

	public function test()
	{
		$userData = [
		    'uid' => 1,
		    'username' => 'wsdebug',
		];
		$this->debug->send($userData);
	}
}
```

>3.访问调试地址
http://127.0.0.1:9501/wsdebug

![img](http://static.firstphp.com/WSDEBUG-WX20191106-152056.png)

### 鸣谢
[韩博文](https://github.com/hanwenbo)
[程立弘](https://github.com/lsclh)
[半山](https://github.com/dwdcth)

### 最后
初见 wsdebug ，是前两位分别开发的基于 easyswoole 的调试组件，顿觉思路新颖，大开调试方便之门，遂同好友半山相聊，半山有心，改造后适配了 hyperf ，由此契机，遂综合三位作者思路，封装了当前组件，以享诸君！






