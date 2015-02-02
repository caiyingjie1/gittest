<?php

use Eleme\Validation\ValidationException;

class UserTradeController extends UserResourceController
{
    protected static $model = 'Trade';

    public function count($userId)
    {
        $rules = array(
            'trade_type' => 'sometimes|required|array',
            'days' => 'sometimes|required|numeric|between:1,30',
            'status' => 'sometimes|required|array'
        );
        $validation = Validator::make(Input::all(), $rules);
        if ($validation->fails()) {
            throw new ValidationException($validation->messages());
        }
        return Response::json(
            array('count' => Trade::count($userId, Input::query('trade_type', null), Input::query('days', null), Input::query('status', null)))
        );
    }
}
