<?php

return array(
    'redis' => array(
        'cluster' => false,
        'default' => array(
            'host' => 'xg-d102-redis-db.elenet.me',
            'port' => '6380',
            'database' => 0,
        ),
        'cache' => array(
            'host' => 'xg-d102-redis-cache.elenet.me',
            'port' => '6300',
            'database' => 0,
        ),
    ),
);
