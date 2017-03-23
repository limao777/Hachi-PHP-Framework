<?php
Config::add([

'Memcached'=>[
['127.0.0.1', 11211, 50],
['127.0.0.1', 11211, 50],
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