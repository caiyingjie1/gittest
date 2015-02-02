<?php

return array(

    'debug' => false,

    'timezone' => 'Asia/Shanghai',

    'locale' => 'zh-CN',

    'fallback_locale' => 'zh-CN',

    'providers' => array(
        'Illuminate\Cache\CacheServiceProvider',
        'Illuminate\Routing\ControllerServiceProvider',
        'Illuminate\Cookie\CookieServiceProvider',
        'Illuminate\Encryption\EncryptionServiceProvider',
        'Illuminate\Filesystem\FilesystemServiceProvider',
        'Illuminate\Log\LogServiceProvider',
        'Illuminate\Redis\RedisServiceProvider',
        'Illuminate\Translation\TranslationServiceProvider',
        'Eleme\Validation\ValidationServiceProvider',
        'Eleme\Zeus\ZeusServiceProvider',
        'Eleme\Auth\AuthServiceProvider',
        'Barryvdh\Cors\CorsServiceProvider',
        'Eleme\HttpMiddleware\HttpMiddlewareServiceProvider',
    ),

    'manifest' => storage_path().'/meta',

    'aliases' => array(
        'App'               => 'Illuminate\Support\Facades\App',
        'Cache'             => 'Illuminate\Support\Facades\Cache',
        'Config'            => 'Illuminate\Support\Facades\Config',
        'Cookie'            => 'Illuminate\Support\Facades\Cookie',
        'Crypt'             => 'Illuminate\Support\Facades\Crypt',
        'Event'             => 'Illuminate\Support\Facades\Event',
        'File'              => 'Illuminate\Support\Facades\File',
        'Input'             => 'Illuminate\Support\Facades\Input',
        'Log'               => 'Illuminate\Support\Facades\Log',
        'Redirect'          => 'Illuminate\Support\Facades\Redirect',
        'Predis'             => 'Illuminate\Support\Facades\Redis',
        'Request'           => 'Illuminate\Support\Facades\Request',
        'Response'          => 'Illuminate\Support\Facades\Response',
        'Route'             => 'Illuminate\Support\Facades\Route',
        'Str'               => 'Illuminate\Support\Str',
        'URL'               => 'Illuminate\Support\Facades\URL',
        'Validator'         => 'Illuminate\Support\Facades\Validator',
        'Auth'              => 'Eleme\Auth\AuthFacade',
    ),
);
