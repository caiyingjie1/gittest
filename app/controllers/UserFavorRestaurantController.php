<?php

use Eleme\Validation\ValidationException;

class UserFavorRestaurantController extends UserResourceController
{
    protected $defaultQueryMethod = 'queryByUserFavor';

    protected static $model = 'Restaurant';

    public function store($userId, $restaurantId)
    {
        $validation = Validator::make(array('restaurant_id' => $restaurantId), array('restaurant_id' => 'required|numeric|min:1'));
        if ($validation->fails()) {
            throw new ValidationException($validation->messages());
        }
        FavorRestaurant::add($userId, $restaurantId);
        return Response::json('', 204);
    }

    public function destroy($userId, $restaurantId)
    {
        $validation = Validator::make(array('restaurant_id' => $restaurantId), array('restaurant_id' => 'required|numeric|min:1'));
        if ($validation->fails()) {
            throw new ValidationException($validation->messages());
        }
        FavorRestaurant::remove($userId, $restaurantId);
        return Response::json('', 204);
    }
}
