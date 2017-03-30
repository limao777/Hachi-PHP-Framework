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

    public function WebsocketServer()
    {
        echo '需要再写，格式是一样的';
    }

    public function HttpServer()
    {
        $http = new swoole_http_server("0.0.0.0", empty(Config::get("Server.http_port")) ? 10080 : Config::get("Server.http_port"));
        $http->set(array(
            'worker_num' => 16,
            'daemonize' => true,
            'max_request' => 10000,
            'dispatch_mode' => 1,
            'log_file' => SERVERLOGFILE,
            'log_level' => 2,
            'task_worker_num' => 100
        )); // 小于INFO不打印日志
        
        $http->on('Start', array(
            $this,
            'onHTTPMasterStart'
        ));
        $http->on('WorkerStart', array(
            $this,
            'onHTTPWorkerStart'
        ));
        $http->on('Task', array(
            $this,
            'onHTTPTask'
        ));
        $http->on('Finish', array(
            $this,
            'onHTTPFinish'
        ));
        
        $http->on('request', function ($request, $response) use($http)
        {
            
            ob_start();
            try {
                $routes_conf = [
                    'group' => Routes::getGroup(),
                    're_method' => Routes::getRegexRoute()
                ];
                $this->f['ctx'] = new \Hachi\Ctx();
                $route_uri = $this->f['ctx']->initCGI($routes_conf, $request, $response);
                
                if ($route_uri['code'] === 0) {
                    $use_controller = $route_uri['controller'];
                    $this->f['controller'] = new $use_controller($this->f['ctx'], $http);
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

    public function onHTTPWorkerStart($http, $id)
    {
        require_once ROOTSERVER . '/Logging.php';
        
        //定时器
        $http->tick(2000, function () use($http, $id)
        {
            // var_dump($id);
        });
    }

    public function onHTTPTask($http, $task_id, $worker_id, $data)
    {
        $ret = 0;
        try {
            $task_class = $data['class'];
            $task_method = $data['method'];
            $task = new $task_class($data);
            $ret = $task->$task_method();
            $task = NULL;
        } catch (Exception $e) {
            $task = NULL;
            if (! empty($e->getMessage())) {
                $ret = $e->getMessage();
            }
        } finally {
            return $ret;
        }
    }

    public function onHTTPFinish($http, $task_id, $task_return_data)
    {
        return $task_return_data;
    }

    public static function getInstance()
    {
        if (! self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}


