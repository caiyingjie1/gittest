<?php
use Eleme\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Support\MessageBag;

Route::filter('get_resource', function ($route, $request, $model) {
    $instance = new $model;
    if ($fields = Input::query('fields', array())) {
        if (!is_array($fields)) {
            throw new ValidationException(new MessageBag(array('fields' => array('fields 传入的数据类型非法'))));
        } elseif ($diff = array_diff($fields, $instance->getVisible())) {
            throw new ValidationException(new MessageBag(array('fields' => array('fields 不支持 '.implode('/', $diff)))));
        }
    }
    if ($extras = Input::query('extras', array())) {
        if (!is_array($extras)) {
            throw new ValidationException(new MessageBag(array('extras' => array('extras 传入的数据类型非法'))));
        } elseif ($diff = array_diff($extras, $instance->getVisibleRelations())) {
            throw new ValidationException(new MessageBag(array('extras' => array('extras 不支持 '.implode('/', $diff)))));
        }
    }
});

Route::filter('get_resource_collection', function ($route, $request, $model, $defaultQueryMethod) {
    $instance = new $model;
    $rules = array(
        'limit' => 'numeric|between:1,1000',
        'offset' => 'numeric|between:0,9999',
        'type' => 'in:'.implode(',', $instance->getVisibleBy()),
    );
    $class = new ReflectionClass($model);
    if (!$class->hasMethod($defaultQueryMethod)) {
        $rules['type'] .= '|required';
    }
    $validation = Validator::make(Input::all(), $rules);
    if ($validation->fails()) {
        throw new ValidationException($validation->messages());
    }

    $by = Input::query('type', '');
    $method = $by ? 'queryBy' . studly_case($by) : $defaultQueryMethod;
    $queryRules = array();
    $function = $class->getMethod($method);
    $parameters = $function->getParameters();
    foreach ($parameters as $parameter) {
        $key = snake_case($parameter->getName());
        if (!$parameter->isOptional()) {
            $queryRules[$key] = 'required';
        }
    }
    $validation = Validator::make(array_merge(Input::query(), $route->parameters()), $queryRules);
    if ($validation->fails()) {
        throw new ValidationException($validation->messages());
    }
});

Route::filter('auth', function ($route, $request) {
    $userId = trim($route->getParameter('user_id'));
    if (!Cookie::has('SID') || !Auth::verify($userId , Cookie::get('SID'), Request::header('user-agent'), Request::ip())) {
        throw new UnauthorizedHttpException(null, '用户信息验证失败');
    }
});
