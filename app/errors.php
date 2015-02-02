<?php

use Eleme\Error\AppException;
use Eleme\Zeus\UserException;
use Eleme\Auth\AuthException;
use Eleme\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

App::error(function (Exception $exception, $code) {
    Log::debug($exception);
    Log::error($exception->getMessage());
    $msg = Config::get('app.debug') ? $exception->getMessage() : '服务器未知错误';
    return Response::json(array('message' => $msg, 'name' => 'SERVER_UNKNOWN_ERROR'), $code);
});

App::error(function (UserException $exception) {
    Log::debug($exception);
    Log::warning($exception->getMessage());
    return Response::json(array('message' => $exception->getMessage(), 'name' => $exception->getZeusErrorName()), 400);
});

App::error(function (HttpException $exception, $code) {
    Log::debug($exception);
    $level = $code >= 500 ? 'error' : 'warning';
    $message = $exception->getMessage() ?: status_code_2_text($code);
    Log::$level($message);
    return Response::json(array('message' => $message, 'name' => status_code_2_name($code)), $code);
});

App::error(function (AppException $exception, $code) {
    Log::debug($exception);
    $level = $code >= 500 ? 'error' : 'warning';
    Log::$level($exception->getMessage());
    return Response::json(array('message' => $exception->getMessage(), 'name' => $exception->getErrorName()), $code);
});

App::error(function (ValidationException $exception, $code) {
    Log::debug($exception);
    Log::warning($exception->getMessage());
    return Response::json(array('message' => $exception->getValidationMessages(), 'name' => $exception->getName()), $code);
});

AppException::$translations = Config::get('exc');
