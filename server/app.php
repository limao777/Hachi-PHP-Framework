<?php
define('ROOTSERVER', realpath(dirname(__FILE__)));
define('ROOTCONFIG', realpath(dirname(__FILE__)) . '/../conf');
define('MASTERHTTPPIDFILE', realpath(ROOTSERVER . '/../storage/log') . '/HTTPmasterId');
define('MANAGERHTTPPIDFILE', realpath(ROOTSERVER . '/../storage/log') . '/HTTPmanagerId');
define('SERVERLOGFILE', realpath(ROOTSERVER . '/../storage/log') . '/serverLog');
define('WWW', realpath(ROOTSERVER . '/../www'));
date_default_timezone_set("Asia/Chongqing");

require_once ROOTSERVER . '/cgiserver.php';
require_once ROOTSERVER . '/Routes.php';
require_once ROOTCONFIG . '/Config.php';
Config::getInstance();

$a = new Hachi\App();
$a->init(Config::get('Server'));

