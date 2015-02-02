<?php

class FavorFood extends Model
{
    protected $service = 'ers';

    public static function add($userId, $foodId)
    {
        return self::factory()->call('add_favor_food')->with($userId, $foodId)->run();
    }

    public static function remove($userId, $foodId)
    {
        return self::factory()->call('remove_favor_food')->with($userId, $foodId)->run();
    }

    public static function count($userId)
    {
        $favorFoodIds = self::factory()->call('query_favor_food_ids_by_user')->with($userId)->result(array());
        return count($favorFoodIds);
    }
}
