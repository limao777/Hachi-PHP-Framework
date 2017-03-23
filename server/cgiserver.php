<?php

/**
 * 
 * @filesource 
 * @author limao (limao777@126.com)
 * @date 2017
 */
class CgiServer
{

    public static $instance;

    public $f = []; // 储存所有new出来的变量

    public function __construct()
    {}

    public function CHttpServer()
    {
        $http = new swoole_http_server("0.0.0.0", empty(Config::get("Server.http_port")) ? 10080 : Config::get("Server.http_port"));
        $http->set(array(
            'worker_num' => 16,
            'daemonize' => true,
            'max_request' => 10000,
            'dispatch_mode' => 1,
            'log_file' => SERVERLOGFILE,
            'log_level' => 2
        )); // 小于INFO不打印日志

        
        $http->on('Start', array(
            $this,
            'onHTTPMasterStart'
        ));
        $http->on('WorkerStart', array(
            $this,
            'onHTTPWorkerStart'
        ));
        
        $http->on('request', function ($request, $response)
        {
            
            ob_start();
            try {
                $routes_conf = [
                    'group' => Routes::getGroup(),
                    're_method' => Routes::getRegexRoute()
                ];
                $this->f['ctx'] = new \Hachi\Ctx();
                $route_uri = $this->f['ctx']->init('C', $routes_conf, $request, $response);
                
                if ($route_uri['code'] === 0) {
                    $use_controller = $route_uri['controller'];
                    $this->f['controller'] = new $use_controller($this->f['ctx']);
                    $use_action = $route_uri['action'];
                    if (method_exists($this->f['controller'], $use_action)) {
                        $this->f['controller']->$use_action();
                    } else {
                        echo 'no this action';
                    }
                } else {
                    echo 'routes error';
                }
            } catch (Exception $e) {
                if (! empty($e->getMessage())) {
                    echo $e->getMessage();
                } else {
                    var_dump($e);
                }
            }
            
            $result = ob_get_contents();
            
            $response->end($result);
            
            // 清理所有对象
            foreach ($this->f as $p => $v) {
                $this->f[$p] = NULL;
            }
            
            ob_end_clean();
        });
        
        $http->start();
    }

    public function onHTTPMasterStart($http)
    {
        file_put_contents(MASTERHTTPPIDFILE, $http->master_pid);
        file_put_contents(MANAGERHTTPPIDFILE, $http->manager_pid);
        echo 'HTTP running' . PHP_EOL;
    }

    public function onHTTPWorkerStart()
    {
        require_once ROOTSERVER . '/Logging.php';
    }

    public static function getInstance()
    {
        if (! self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}


