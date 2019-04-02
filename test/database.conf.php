<?php

use Larium\Database\Mysql\Adapter;

$config = array(
    'host'     => '192.168.56.110',
    'port'     => 3306,
    'adapter'  => 'Mysql',
    'database' => 'active_record',
    'username' => 'admin',
    'password' => 'kollaros',
    'charset'  => 'utf8',
    'fetch'    => Adapter::FETCH_OBJ,
);
