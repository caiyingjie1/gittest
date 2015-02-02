<?php

use Eleme\Zeus\ResourceInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ResourceController extends Controller
{
    protected $defaultQueryMethod = 'query';

    public function __construct()
    {
        $this->beforeFilter('get_resource:'.static::$model, array('only' => array('index', 'show')));
        $this->beforeFilter('get_resource_collection:'.static::$model.','.$this->defaultQueryMethod, array('only' => array('index')));
    }

    public function index()
    {
        $by = Input::query('type', '');
        $method = $by ? 'queryBy' . studly_case($by) : $this->defaultQueryMethod;
        $args = array();
        $class = new ReflectionClass(static::$model);
        $function = $class->getMethod($method);
        $parameters = $function->getParameters();
        foreach ($parameters as $parameter) {
            $key = snake_case($parameter->getName());
            $value = Route::input($key, Input::query($key, $parameter->isOptional() ? $parameter->getDefaultValue() : null));
            array_push($args, $value);
        }
        return forward_static_call_array(array(static::$model, $method), $args);
    }

    public function show()
    {
        return forward_static_call(array(static::$model, 'get'), last(Route::getCurrentRoute()->parameters()));
    }

    public function callAction($method, $parameters)
    {
        $response = parent::callAction($method, $parameters);
        if (is_null($response)) {
            throw new NotFoundHttpException('Resource Not Found', null, 404);
        }
        if ($response instanceof ResourceInterface && !$response->isEmpty()) {
            if ($extras = Input::query('extras', array())) $response = $response->extras($extras);
            if ($fields = Input::query('fields', array())) $response = $response->fields($fields);
        }
        return $response;
    }
}
