<?php
return array(
    'defaults' => array(
        'supportsCredentials' => true,
        'allowedOrigins' => array(
            'http://m.elenet.me',
            'https://m.elenet.me',
        ),
        'allowedHeaders' => array('*'),
        'allowedMethods' => array('*'),
        'maxAge' => 3600,
    ),
);
