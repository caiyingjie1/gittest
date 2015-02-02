<?php

use Eleme\Validation\ValidationException;

class UserHongbaoController extends UserResourceController
{
    protected static $model = 'Hongbao';

    public function count($userId)
    {
        $rules = array(
            'status' => 'sometimes|array',
            'begin_date' => 'date_section',
            'end_date' => 'date_section',
        );
        $validation = Validator::make(Input::all(), $rules);
        if ($validation->fails()) {
            throw new ValidationException($validation->messages());
        }
        return Response::json(
            array(
                'count' => Hongbao::count($userId, Input::query('begin_date'), Input::query('end_date'), Input::query('status', null))
            )
        );
    }

    public function exchange($userId)
    {
        $rules = array(
            'exchange_code' => 'required'
        );
        $validation = Validator::make(Input::json()->all(), $rules);
        if ($validation->fails()) {
            throw new ValidationException($validation->messages(), 403);
        }
        return Response::json(array('hongbao_sn' => Hongbao::exchange($userId, Input::json('exchange_code'))));
    }
}
