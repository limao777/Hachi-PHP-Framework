<?php

class Controllers_Test extends Controllers_BaseController
{

    public function __construct($ctx, $sw = NULL)
    {
        parent::init($ctx, $sw);
        
        $this->_form_validation_rule = array(
            'login' => array(
                'name|用户名' => 'required|max_length[64]',
                'pass|密码' => 'required',
                'scode|验证码' => '',
                'rememberme|记住该帐号' => 'max_length[20]',
                'redirect_uri|跳转地址' => 'max_length[255]'
            )
        );
    }

    public function info()
    {
        phpinfo();
    }

    public function ip()
    {
        var_dump($this->ctx->getClientIp());
    }
    
    public function index()
    {
        $fv = new Lib_FormValidation($this->_form_validation_rule, $this->ctx->getQuery());
        $ret = $fv->validate('login');
        if ($ret === FALSE) {
            $this->_ajax_failed($fv->error());
        }
        
        echo '表单验证通过';
    }
    
    public function config()
    {
    
        var_dump(Config::get('FILE'));
    }
    
    public function query()
    {
        var_dump($this->ctx->getquery());
        var_dump($this->ctx->query);
    }
    
    public function routegroup()
    {
        var_dump($this->ctx->getRouteGroup());
    }
    
    
    public function url()
    {
        var_dump($this->ctx->geturl());
        var_dump($this->ctx->getController());
        var_dump($this->ctx->getAction());
    }
    
    public function db()
    {
        $a = Model_Admin::getInstance();
        $b = $a->select([
            'id' => 1
        ]);
        var_dump($b);
    }

    public function cookies()
    {
        $this->ctx->setcookie("abc", 'cba');
        
        var_dump($this->ctx->getCookie('abc'));
    }

    public function cache()
    {
        
        //redis
        $cache = Lib_Cache_Redis::getInstance();
        
        echo 'redis set、get:<br />';
        $cache->setex('re', 1, 'redis');
        var_dump($cache->get('re'));
        sleep(2);
        var_dump($cache->get('re'));
        
        echo '<br /><br />redis list:<br />';
        $cache->lPush('list', '1');
        $cache->lPush('list', '2');
        $cache->lPush('list', 3);
        $cache->lPush('list', '4');
        
        var_dump($cache->lPop('list'));
        var_dump($cache->rPop('list'));
        var_dump($cache->lPop('list'));
        var_dump($cache->lPop('list'));
        
        // memcached
        echo '<br /><br />memcahed set、get:<br />';
        $cache = Lib_Cache_Memcached::getInstance();
        $cache->set('a', 'b', 2);
        $c = $cache->get('a');
        var_dump($c);
        sleep(3); // 当然，正式开发并且以app模式部署是禁止使用sleep的
        $c = $cache->get('a');
        var_dump($c);
    }

    public function redirect()
    {
        $this->ctx->redirect('http://www.baidu.com');
    }

    public function end()
    {
        $this->ctx->end('就是看看能否end成功');
        echo '如果看到这句话就是end失败';
    }

    public function task()
    {
        $tasks[] = [
            'class' => 'Helper_Http',
            'method' => 'curl'
        ]; // 任务1
                                                                   
        // 等待所有Task结果返回，超时为10s
        $results = $this->sw->taskWaitMulti($tasks, 10.0);
        
        if (! isset($results[0])) {
            echo "任务1执行超时了\n";
        } else {
            echo "任务1的执行结果为{$results[0]}\n";
        }
        if (isset($results[1])) {
            echo "任务2的执行结果为{$results[1]}\n";
        }
        if (isset($results[2])) {
            echo "任务3的执行结果为{$results[2]}\n";
        }
    }

    public function http()
    {
        $httpclient = new Swoole\Coroutine\Http\Client('172.19.3.65', 80);
        $httpclient->setHeaders([
            'Host' => "www.onenetv3.com",
            "User-Agent" => 'Chrome/49.0.2587.3',
            'Accept' => 'text/html,application/xhtml+xml,application/xml',
            'Accept-Encoding' => 'gzip'
        ]);
        $httpclient->set([
            'timeout' => 1
        ]);
        $httpclient->get('/about');
        $http_res = $httpclient->body;
        var_dump($http_res);
    }
}