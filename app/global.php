<?php

use Illuminate\Cache\Repository;
use Eleme\Cache\RedisStore;

Log::getMonolog()->pushProcessor(function (array $record) {
    $record['extra']['session_id'] = session_id();
    $record['extra']['request_id'] = Request::header('x-request-id');
    return $record;
});

App::before(function ($request) {
    $sessionName = 'eleme__'.strtr(Config::get('host.root'), '.', '_');
    $secret = Cookie::get($sessionName);
    $signMethod = function ($id, $secret) {
        return sha1($id.':'.$secret);
    };
    if ($secret !== null && strpos($secret , ':') !== false) {
        list($id, $signature) = explode(':', $secret, 2);
        if ($signature === $signMethod($id, Config::get('session.secret'))) {
            session_id($id);
        }
    } elseif (!$request->isMethodSafe()) {
        $id = md5(uniqid("session", true));
        session_id($id);
        Cookie::queue($sessionName, $id.':'.$signMethod($id, Config::get('session.secret')), 10080);
    }
});

App::finish(function ($request, $response) {
    $statusCode = $response->getStatusCode();
    $level = 'info';
    if ($statusCode >= 500) {
        $level = 'error';
    } elseif ($statusCode >= 400) {
        $level = 'warning';
    }
    $message =
        '@ '.$request->ip()
        .' '.$request->getMethod()
        .' '.$request->getHttpHost()
        .' '.$request->getRequestUri()
        .' '.$response->getStatusCode()
        .' '.$request->header('user-agent');
    Log::$level($message);
});

Cache::extend('redis.cache', function ($app) {
    return new Repository(new RedisStore($app['redis'], $app['config']['cache.prefix'], $app['config']['cache.connection']));
});
