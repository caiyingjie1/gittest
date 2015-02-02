<?php

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserResourceController extends ResourceController
{
    protected $defaultQueryMethod = 'queryByUserId';

    public function show()
    {
        $userId = Route::getCurrentRoute()->getParameter('user_id');
        $resource = forward_static_call(array(static::$model, 'get'), last(Route::getCurrentRoute()->parameters()));
        if (is_null($resource) || ((int) $resource->getAttribute('user_id') !== (int) $userId)) {
            throw new NotFoundHttpException('Resource Not Found', null, 404);
        }
        return $resource;
    }
}
