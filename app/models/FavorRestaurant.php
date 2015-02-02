<?php

class FavorRestaurant extends Model
{
    protected $service = 'ers';

    public static function add($userId, $restaurantId)
    {
        return self::factory()->call('add_favored')->with($userId, $restaurantId)->run();
    }

    public static function remove($userId, $restaurantId)
    {
        return self::factory()->call('remove_favored')->with($userId, $restaurantId)->run();
    }

    public static function count($userId)
    {
        return self::factory()->call('count_favored')->with($userId)->result(0);
    }
}
