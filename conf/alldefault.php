<?php
Config::add([

'Server'=>[
    'spl_exclude' => ['smarty'],
    'server' => ROOTSERVER,
    'www' => WWW,
    'logfile' => SERVERLOGFILE,
    'MASTERHTTPPIDFILE' => MASTERHTTPPIDFILE,
    'MANAGERHTTPPIDFILE' => MANAGERHTTPPIDFILE,
    'http_port' => 10080,
],

]);