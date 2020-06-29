<?php

$file_path = realpath(__DIR__.'/../../.secret/config.json');
$json = json_decode(file_get_contents($file_path), true);

return [
    
    'default' => 'mysql',
    'migrations' => 'migrations',
    'connections' => [

        'mysql' => [
            'driver'    => 'mysql',
            'host'      => $json["database"]["host"],
            'port'      => $json["database"]["port"],
            'database'  => $json["database"]["name"],
            'username'  => $json["database"]["user"],
            'password'  => $json["database"]["pass"],
            'charset'   => 'utf8',
            'collation' => 'utf8_general_ci',
            'prefix'    => '',
            'strict'    => false,
        ],

    ],
];
