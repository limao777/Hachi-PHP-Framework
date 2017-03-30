<?php
/**
 * 
 * @filesource 
 * @author limao (limao777@126.com)
 * @date 2017
 */
define('PUBSERVER', realpath(dirname(__FILE__)));
define('ROOTSERVER', realpath(PUBSERVER . '/../server'));
define('ROOTCONFIG', realpath(dirname(__FILE__)) . '/../conf');
define('SERVERLOGFILE', realpath(ROOTSERVER . '/../storage/log') . '/serverLog');
define('WWW', realpath(ROOTSERVER . '/../www'));
define('MASTERHTTPPIDFILE', realpath(ROOTSERVER . '/../storage/log') . '/HTTPmasterId');
define('MANAGERHTTPPIDFILE', realpath(ROOTSERVER . '/../storage/log') . '/HTTPmanagerId');
define('MASTERWEBSOCKETPIDFILE', realpath(ROOTSERVER . '/../storage/log') . '/WEBSOCKETmasterId');
define('MANAGERWEBSOCKETPIDFILE', realpath(ROOTSERVER . '/../storage/log') . '/WEBSOCKETmanagerId');
date_default_timezone_set("Asia/Chongqing");

require_once ROOTSERVER . '/Routes.php';
require_once ROOTCONFIG . '/Config.php';
require_once ROOTSERVER . '/Logging.php';
Config::getInstance();

$a = new Hachi\App();
$a->init(Config::get('Server'), TRUE);


$routes_conf = [
    'group' => Routes::getGroup(),
    're_method' => Routes::getRegexRoute(),
];
$ctx = new \Hachi\Ctx();
$route_uri = $ctx->initFPM($routes_conf, $_SERVER['REQUEST_URI']);

if ($route_uri['code'] === 0) {
    $use_controller = $route_uri['controller'];
    $ctx = new $use_controller($ctx);
    $use_action = $route_uri['action'];
    if (method_exists($ctx, $use_action)) {
        $ctx->$use_action();
    } else {
        echo 'no this action';
    }
} else {
    echo 'routes error';
}


