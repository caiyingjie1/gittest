<?php
return array(
    'defaults' => array(),
    'connections' => array(
        'eus' => array(
            'client' => 'EUS\ElemeUserServiceClient',
            'server' => 'xg-d102-eus-server.elenet.me',
            'port' => '29098',
            'persist' => false,
            'receive_timeout' => 8000,
            'send_timeout' => 1000,
            'authorizations' => array(
                'auth', 'pay_for_order_new', 'renren_purify', 'reset_forgetted_password', 'reset_password', 'signup', 'update_password'
            ),
        ),
        'ers' => array(
            'client' => 'ERS\ElemeRestaurantServiceClient',
            'server' => 'xg-d102-ers-server.elenet.me',
            'port' => '29091',
            'persist' => false,
            'receive_timeout' => 8000,
            'send_timeout' => 1000,
        ),
        'eos' => array(
            'client' => 'EOS\ElemeOrderServiceClient',
            'server' => 'xg-d102-eos-server.elenet.me',
            'port' => '29090',
            'persist' => false,
            'receive_timeout' => 8000,
            'send_timeout' => 1000,
            'authorizations' => array(
                'refund_cancel'
            ),
        ),
        'geos' => array(
            'client' => 'GEOS\GeohashServiceClient',
            'server' => 'xg-d102-geos-server.elenet.me',
            'port' => '29081',
            'persist' => false,
            'receive_timeout' => 8000,
            'send_timeout' => 1000,
        ),
        'sms' => array(
            'client' => 'SMS\ShortMessageServiceClient',
            'server' => 'xg-d102-sms-server.elenet.me',
            'port' => '29093',
            'persist' => false,
            'receive_timeout' => 8000,
            'send_timeout' => 1000,
        ),
        'fuss' => array(
            'client' => 'fuss\FussServiceClient',
            'server' => 'xg-d102-fuss-server.elenet.me',
            'port' => 9093,
            'persist' => false,
            'receive_timeout' => 8000,
            'send_timeout' => 1000,
        ),
    ),
);
