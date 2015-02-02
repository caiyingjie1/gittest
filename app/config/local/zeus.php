<?php
return array(
    'defaults' => array(),
    'connections' => array(
        'eus' => array(
            'client' => 'EUS\ElemeUserServiceClient',
            'server' => 'testingservice',
            'port' => '29098',
            'persist' => true,
            'receive_timeout' => 10000,
            'send_timeout' => 1000,
            'authorizations' => array(
                'auth', 'pay_for_order_new', 'renren_purify', 'reset_forgetted_password', 'reset_password', 'signup', 'update_password'
            ),
        ),
        'ers' => array(
            'client' => 'ERS\ElemeRestaurantServiceClient',
            'server' => 'testingservice',
            'port' => '29091',
            'persist' => true,
            'receive_timeout' => 10000,
            'send_timeout' => 1000,
        ),
        'eos' => array(
            'client' => 'EOS\ElemeOrderServiceClient',
            'server' => 'testingservice',
            'port' => '29090',
            'persist' => true,
            'receive_timeout' => 10000,
            'send_timeout' => 1000,
            'authorizations' => array(
                'refund_cancel'
            ),
        ),
        'geos' => array(
            'client' => 'GEOS\GeohashServiceClient',
            'server' => 'testingservice',
            'port' => '29081',
            'persist' => true,
            'receive_timeout' => 10000,
            'send_timeout' => 1000,
        ),
        'sms' => array(
            'client' => 'SMS\ShortMessageServiceClient',
            'server' => 'testingservice',
            'port' => '29093',
            'persist' => true,
            'receive_timeout' => 10000,
            'send_timeout' => 1000,
        ),
        'fuss' => array(
            'client' => 'fuss\FussServiceClient',
            'server' => 'testing',
            'port' => 9093,
            'persist' => true,
            'receive_timeout' => 10000,
            'send_timeout' => 1000,
        ),
    ),
);
