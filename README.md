# nknkn 妮可妮可妮~

将以下包组合成小框架，轻量+自定义组合各个模块，定制属于你的小框架

```
baagee/async-task: php process task library https://github.com/baagee/async-task.git
baagee/php-config: PHP Config library https://github.com/baagee/php-config.git
baagee/php-cookie: PHP Cookie library https://github.com/baagee/php-cookie.git
baagee/php-curl-request: PHP Curl Request library https://github.com/baagee/php-curl-request.git
baagee/php-event: PHP event library https://github.com/baagee/php-event.git
baagee/php-log: PHP Log library https://github.com/baagee/php-log.git
baagee/php-mysql: PHP mysql library https://github.com/baagee/php-mysql.git
baagee/php-onion: PHP onion layer https://github.com/baagee/php-onion.git
baagee/php-params-validator: PHP Params Validator Library https://github.com/baagee/php-params-validator.git
baagee/php-router: PHP Router library https://github.com/baagee/php-router.git
baagee/php-session: PHP Session library https://github.com/baagee/php-session.git
baagee/php-template: PHP Template library https://github.com/baagee/php-template.git
baagee/wtf-error: What the fuck! PHP error handler https://github.com/baagee/wtf-error.git
psr/container: Common Container Interface (PHP FIG PSR-11) https://github.com/php-fig/container.git
```

## 快速开始
通过 `php ./vendor/bin/creater composer.json_dir app_name` 来快速在vendor同级目录创建app相关目录和示例代码

例如
```
$> php ./vendor/bin/creater ./ appName                                                                                                                                                                                                                [11:05:52]
创建文件夹：app 成功
创建文件夹：app/Action 成功
创建文件夹：app/Controller 成功
创建文件夹：app/Event 成功
创建文件夹：app/Library 成功
创建文件夹：app/Middleware 成功
创建文件夹：app/Model 成功
创建文件夹：app/Script 成功
创建文件夹：app/View 成功
创建文件夹：config 成功
创建文件夹：runtime 成功
创建文件夹：public 成功
public/index.php 创建成功
composer.json 添加自动加载命名空间App
app/routes.php 创建成功
app/Middleware/ReturnJson.php 创建成功
app/Action/Test/Hello.php 创建成功
尝试访问一下 /api/hello
OVER
```

## app目录结构
必须的目录在vendor同级别有
```
app// 项目具体代码 里面有控制器，中间件，模型等类
public // webroot目录 index.php所在目录
config // 配置文件所在目录
runtime // 运行时缓存等目录，要保证可写
vendor // composer安装文件夹
comspoer.json
```
至于目录下的子目录，自己自定义就行

详细参考结构请看示例
[【sql-profiling】mysql sql语句性能分析平台](https://github.com/baagee/sql-profiling "sql-profiling")

## 框架运行流程
```
app初始化
触发app初始化之后的事件
触发路由初始化之前事件
路由初始化
触发路由初始化之后事件
触发路由匹配之前事件
if 路由匹配成功
    触发路由匹配之后事件
        Cookie中间件开始
            Session中间件开始
                自定义中间件开始
                    Action执行业务逻辑
                自定义中间件结束
            Session中间件结束
        Cookie中间件结束
else
    http_response_code(404)
请求结束输出响应
```

## 支持自定义Log存储方式，默认文件
只需要继承并实现`BaAGee\Log\Base\LogHandlerAbstract`，并在log.php配置文件指定handler类名即可

## 支持自定义Log格式
只需要继承并重写`BaAGee\Log\Base\LogFormatter`的`getLogString`方法即可，并在log.php配置文件指定formatter类名即可

## 支持中间件
框架默认中间件有Session,Cookie，并能根据有无对应配置文件判断是否开启,用户可自定义中间件，比如验证登陆，权限之类的

## 支持事件触发，框架内置的事件有
```php
// app初始化后
BaAGee\NkNkn\Constant\CoreEventList::APP_AFTER_INIT_EVENT
// 路由初始化前
BaAGee\NkNkn\Constant\CoreEventList::ROUTER_BEFORE_INIT_EVENT
// 路由初始化后
BaAGee\NkNkn\Constant\CoreEventList::ROUTER_AFTER_INIT_EVENT
// 路由匹配执行前
BaAGee\NkNkn\Constant\CoreEventList::ROUTER_BEFORE_DISPATCH_EVENT
// 路由匹配执行后
BaAGee\NkNkn\Constant\CoreEventList::ROUTER_AFTER_DISPATCH_EVENT 
```

## session支持file,memcache,redis储存
通过修改session.php配置文件的`handler`类名来修改储存方式，为空表示file

## 其他强大的功能？自己实现或者借助composer吧

## 使用示例代码
[【sql-profiling】mysql sql语句性能分析平台](https://github.com/baagee/sql-profiling "sql-profiling")
