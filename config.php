<?php
    return array(
                'templates.path'    => __DIR__ . '/templates/',
                'debug' => false,
                'mode' => 'testing',
                'log.enabled' => true,
                'log.writer' => new \Yee\Log\FileLogger(
                    array(
                        'path' => __DIR__.'/logs',
                        'name_format' => 'Y-m-d',
                        'message_format' => '%label% - %date% - %message%'
                    )
                ),
                'database' => array(
                    'cassandra' => array(
                        'database.type'       => 'cassandra',
                        'database.seeds'      => array('10.10.10.100'),
                        'database.port'       => 9042,
                        'database.keyspace'   => 'eq',
                        'database.user'       => 'ph_usr',
                        'database.pass'       => 'a7290c5a'
                    ),
                    'mysql' => array(
                        'database.type' => "mysql",
                        'database.host' => '10.10.10.110',
                        'database.name' => 'eq',
                        'database.user' => 'panayot',
                        'database.pass' => '987654321!',
                        'database.port' => 3306
                    ),
                ),
                'session' => 'php',   // php, database or memcached
                'cache.path'=> __DIR__ . '/cache',
                'cache.timeout'=> 1800,
    );
