<?php

use Eleme\Validation\ValidationException;

class UserAddressController extends UserResourceController
{
    protected static $model = 'Address';

    public function __construct() {
        $this->beforeFilter(function () {
            $rules = array(
                'address' => 'required',
                'phone' => "required|regex:/^1[34578]\d{9}$/",
                'name' => 'required',
            );
            $validation = Validator::make(Input::json()->all(), $rules);
            if ($validation->fails()) {
                throw new ValidationException($validation->messages());
            }
        }, array('only' => array('store', 'update')));

        parent::__construct();
    }

    public function store($userId)
    {
        $addressId = Address::add(
            $userId,
            Input::json('address'),
            Input::json('phone'),
            Input::json('phone_bk', ''),
            Input::json('name'),
            Input::json('geohash', '')
        );
        return Response::json(array('address_id' => $addressId));
    }

    public function update($userId, $addressId)
    {
        Address::update(
            $userId,
            $addressId,
            Input::json('address'),
            Input::json('phone'),
            Input::json('phone_bk', ''),
            Input::json('name')
        );
        return Response::json('', 204);
    }

    public function destroy($userId, $addressId)
    {
        Address::destroy($userId, $addressId);
        return Response::json('', 204);
    }
}
