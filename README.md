# nknkn 妮可妮可妮~

将以下包组合成小框架，轻量+自定义组合各个模块，定制属于你的小框架

```
"baagee/php-onion" // 洋葱模型，提供中间件支持
"baagee/php-params-validator" // 参数验证类
"baagee/php-mysql" // 操作mysql类
"baagee/php-router" // 路由类
"baagee/php-config" // config配置获取类
"baagee/php-log" // Log日志类
"baagee/wtf-error" // 友好的开发错误展示类
"baagee/php-template" // html模板引擎
"baagee/php-session" // Session管理类
"baagee/php-cookie" //Cookie管理类
"baagee/php-event" //事件定义触发类
"baagee/php-curl-request" //Curl请求类
"baagee/async-task" //异步执行任务脚本类
"baagee/redis-tools" //redis分布式抢占锁和限速器
```

## 框架目录结构
```
$ tree src
src
├── App.php // App类
├── AppEnv.php // App目录环境等信息配置与获取
├── Base
│   ├── ActionAbstract.php // 当控制器比较大时，可以把每个action独立成一个类的父类
│   ├── ControllerAbstract.php // 控制器父类
│   ├── EventAbstract.php // 事件父类
│   ├── HttpServiceAbstract.php // 封装的调用第三方Http服务时的Curl类
│   ├── MiddlewareAbstract.php // 中间件父类
│   ├── ModelAbstract.php // Model父类
│   ├── ParamsValidatorTrait.php // 参数批量验证
│   └── TimerTrait.php // 在计时器中运行，会返回运行时间
├── Constant
│   ├── CoreEventList.php // 系统事件名
│   └── CoreNoticeCode.php // 系统错误码
├── IdGenerator.php // 基于Redis的Id生成器
├── LogFormatter.php //默认的 Log格式化 json字符串
├── Middleware
│   ├── CookieInit.php // cookie初始化中间件
│   └── SessionInit.php // Session初始化中间件
├── Redis.php // 包装的Redis客户端
├── Router.php // 路由类
├── UploadFile.php // 上传文件类
└── UserNotice.php // 需要提示用户的Exception类
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
