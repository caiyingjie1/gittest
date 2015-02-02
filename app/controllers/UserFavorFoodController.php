<?php

use Eleme\Validation\ValidationException;

class UserFavorFoodController extends UserResourceController
{
    protected $defaultQueryMethod = 'queryByUserFavor';

    protected static $model = 'Food';

    public function store($userId, $foodId)
    {
        $validation = Validator::make(array('food_id' => $foodId), array('food_id' => 'required|numeric|min:1'));
        if ($validation->fails()) {
            throw new ValidationException($validation->messages());
        }
        FavorFood::add($userId, $foodId);
        return Response::json('', 204);
    }

    public function destroy($userId, $foodId)
    {
        $validation = Validator::make(array('food_id' => $foodId), array('food_id' => 'required|numeric|min:1'));
        if ($validation->fails()) {
            throw new ValidationException($validation->messages());
        }
        FavorFood::remove($userId, $foodId);
        return Response::json('', 204);
    }
}
