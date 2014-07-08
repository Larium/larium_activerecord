<?php

use Larium\Database\Mysql\Adapter;

$config = array(
    'host'     => 'localhost',
    'port'     => 3306,
    'adapter'  => 'Mysql',
    'database' => 'active_record',
    'username' => 'root',
    'password' => 'kollaros',
    'charset'  => 'utf8',
    'fetch'    => Adapter::FETCH_OBJ,
);
