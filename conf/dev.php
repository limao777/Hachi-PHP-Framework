<?php
Config::add([

'memcached'=>[
['127.0.0.1', 11211, 50],
['127.0.0.1', 11211, 50],
],

'redis'=>[
'127.0.0.1',
6379,
1,
100
],

'DB_CONFIG'=>[
'local' => [
    'main' => [
        'host' => '127.0.0.1',
        'user' => 'postgres',
        'password' => '123456',
        'database' => 'test',
        'charset' => 'utf8',
        'port' => '5432'
    ],
    'query' => [
        [
            'host' => '127.0.0.1',
            'user' => 'postgres',
            'password' => '123456',
            'database' => 'test',
            'charset' => 'utf8',
            'port' => '5432'
        ],
    ], //end query
]
],



]);