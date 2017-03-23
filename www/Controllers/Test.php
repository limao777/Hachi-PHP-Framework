<?php

class Controllers_Test extends Controllers_BaseController{
  
    public function __construct($ctx){
        
        parent::init($ctx);
        
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
    
    
    public function index(){
        
        $fv = new Lib_FormValidation($this->_form_validation_rule, $this->ctx->getQuery());
        $ret = $fv->validate('login');
        if ($ret === FALSE) {
            $this->_ajax_failed($fv->error());
        }
        
        echo '表单验证通过';
    }
    
    public function db(){
        $a=Model_Admin::getInstance();
        $b = $a->select(['id'=>1]);
        var_dump($b);
    }
    
    public function cookies(){
    
        $this->ctx->setcookie("abc",'cba');
        
        var_dump($this->ctx->getCookie('abc'));
    }
    
    public function cache(){

        //memcached
        $cache = Lib_Cache_Memcached::getInstance();
        $cache->set('a', 'b', 2);
        $c = $cache->get('a');
        var_dump($c);
        sleep(3);   //当然，正式开发并且以app模式部署是禁止使用sleep的
        $c = $cache->get('a');
        var_dump($c);
    }
    
    
    public function redirect(){
       
        $this->ctx->redirect('http://www.baidu.com');
        
    }
    
    public function end(){
         
        $this->ctx->end('就是看看能否end成功');
        echo '如果看到造个句话就是end失败';
    
    }
    
 public function http(){
                 $httpclient = new Swoole\Coroutine\Http\Client('172.19.3.65', 80);
                 $httpclient->setHeaders(['Host' => "www.onenetv3.com",
                                          "User-Agent" => 'Chrome/49.0.2587.3',
                                          'Accept' => 'text/html,application/xhtml+xml,application/xml',
                                          'Accept-Encoding' => 'gzip',
                 ]);
                 $httpclient->set([ 'timeout' => 1]);
                 $httpclient->get('/about');
                 $http_res  = $httpclient->body;
                 var_dump($http_res);
 }
    
}