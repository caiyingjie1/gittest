<?php
return array(
    'defaults' => array(
        'supportsCredentials' => true,
        'allowedOrigins' => array(
            'http://m.ele.me',
            'https://m.ele.me',
        ),
        'allowedHeaders' => array('*'),
        'allowedMethods' => array('*'),
        'maxAge' => 3600,
    ),
);
