# Hachi-PHP-Framework
基于PHP扩展和PHP做的web框架，支持接入swoole以及传统的LAMP/LNMP架构，支持PHP5.5+、PHP7+

## 当前进度
V0.0.2已完成！

后续将加强路由，增加路由前缀、路由分组等功能

对超级变量增加签名的cookies，加密的cookies方法

## 特性
使用了超级变量，HTTP协议相关的数据全部可以使用它

集成了get/post参数获取、cookies、HTTP header、HTTP raw body、跳转等方法。在不使用swoole的定时器、任务等功能时，app部署方式与传统部署方式可以无缝转换

以app方式部署性能超级高，支持swoole所有特性，如定时器、任务等、协程等功能

+缓存处理封装

+SQL型数据库orm

+表单验证表达式，预留支持多语言方法

+邮件发送功能，支持sendmail以及SMTP方式

+日志功能

+smarty

符合psr0的路由风格，轻松扩展其他组件，感觉支持psr4完全暂时没必要

## 使用说明
### 传统模式
传统模式下无任何禁忌，就像开发普通php一样，当然，推荐使用集成的方式处理get/post数据、cookies、header等，这样以后需要使用app模式部署基本不用改代码
### app模式
不能使用die()、exit等直接结束程序的方法，而应该使用集成的end方法代替

传统的$_GET、setcookie等方法将失效，应该使用集成的方法代替

## 传统方式部署：
需要安装doc目录下的扩展，phpize、./configure、make、makeinstall
### Nginx示例：
```
server{
listen 80;
    server_name www.aaa.om;
 
    charset utf-8;

  root   /web/hachi/public;


    location / {
        index  index.html index.htm index.php;
                if (!-e $request_filename) {
            rewrite ^/(.*)  /index.php?$1 last;
        }
        location ~ \.php$ {
                        fastcgi_pass    127.0.0.1:9001;
                        fastcgi_index   index.php;
                        fastcgi_param   SCRIPT_FILENAME  $request_filename;
                        include         fastcgi_params;
        }
    }


    #error_page  404              /404.html;

    # redirect server error pages to the static page /50x.html
    #
    error_page   500 502 503 504  /50x.html;
    location = /50x.html {
        #root   /usr/share/nginx/html;
        proxy_pass http://$host/error/index; 
    }

}
```
### Apache部署示例：
Apache需要将.htaccess放在server目录下，内容：
```
RewriteEngine On

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php/$1 [L]
```

## app方式部署：
需要安装doc目录下的扩展以及安装swoole扩展，1.8+版本即可
### 运行：
```
切换到server目录下
php app.php start
```

### 结束运行：
```
切换到server目录下
php app.php stop
```

## 已知问题
app方式部署使用smarty可能会产生内存溢出

不支持windows（windows可以用docker）

app方式部署如果在较大并发下出现连接数问题，可以将ulimit改为65535

## 超级变量功能列表
### 结束当前会话
METHOD: end(string msg)

| 参数名称  | 说明 |
| ------------- | :-----: |
| msg  | 可选，结束后要发送到浏览器的文字，默认发送“NULL”字符串 |

返回值

(无)

-----

### 获取get+post参数
METHOD: getQuery()

返回值

array，如果get、post有相同的参数，post覆盖get

-----

### 获取get参数
METHOD: getQueryGet()

返回值

array

-----

### 获取post参数
METHOD: getQueryPost()

返回值

array

-----

### 获取route group参数
METHOD: getRouteGroup()

返回值

string

-----

### 设置cookie
METHOD: setCookie(string key, string value="", long expire=0, string path="/", string domain="", bool secure=false, bool httponly=false)

| 参数名称  | 说明 |
| ------------- | :-----: |
| key  | cookie的key |
| value  | cookie的值 |
| expire  | 设置时长，秒，默认为session |
| path  | cookie的path |
| domain  | cookie的域 |
| secure  | 是否启用安全策略 |
| httponly  | 是否httponly |

返回值

1

-----

### 获取cookie
METHOD:  getCookie(string key)

| 参数名称  | 说明 |
| ------------- | :-----: |
| key  | cookie的key |

返回值

string

-----

### 跳转
METHOD:  redirect(string uri, int status_code = 302)

| 参数名称  | 说明 |
| ------------- | :-----: |
| uri  | 跳转的地址 |
| status_code  | HTTP的code 301/302等 |

返回值

0

-----

### 获取HTTP header信息
METHOD:  getHeader()

返回值

array，返回值兼容swoole写法，全部小写，"_"转“-”，自定义参数去除了HTTP前缀

-----

### 设置HTTP header
METHOD:  setHeader(string key, string value)

| 参数名称  | 说明 |
| ------------- | :-----: |
| key  | key |
| value  | value |

返回值

1

-----

### 获取HTTP原生的body
METHOD:  getRawContent()

返回值

string

-----

### 获取HTTP文件
METHOD:  getFiles()

返回值

array，同$_FILES