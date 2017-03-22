# Hachi-PHP-Framework
基于PHP扩展和PHP做的web框架，支持接入swoole以及传统的LAMP/LNMP架构，支持PHP5.5+、PHP7+

## 特性
使用了超级变量，HTTP协议相关的数据全部可以使用它

集成了get/post参数获取、cookies、HTTP header、HTTP raw body、跳转等方法。在不使用swoole的定时器、任务等功能时，app部署方式与传统部署方式可以无缝转换

以app方式部署性能超级高

+缓存处理，支持memcached以及redis

+SQL型数据库orm，支持mysql、postgres

+表单验证表达式，预留支持多语言方法

+邮件发送功能，支持sendmail以及SMTP方式

+日志功能

+smarty

符合psr0风格的路由风格，轻松扩展其他组件，感觉支持psr4完全暂时没必要

## 使用禁忌
### 传统模式
传统模式下无任何禁忌，就像开发普通php一样，当然，推荐使用集成的方式处理get/post数据、cookies、header等，这样以后需要使用app模式部署基本不用改代码
### app模式
不能使用die()、exit等直接结束程序的方法，而应该使用集成的end方法代替

传统的$_GET、setckkie等方法将失效，应该使用集成的方法代替

## 传统方式部署：
### Nginx示例：
```
server{
listen 80;
    server_name www.aaa.om;
 
    charset utf-8;

  root   /web/hachi/server;


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
需要安装swoole扩展，1.8+版本即可
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

array

### 待补全