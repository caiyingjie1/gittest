<?php

class Address extends Model
{
    protected $service = 'eus';

    protected $visible = array(
        'id', 'address', 'phone', 'is_valid', 'created_at', 'phone_bk', 'name'
    );

    public static function get($id)
    {
        return self::factory()->call('get_address')->with($id)->get();
    }

    public static function queryByUserId($userId)
    {
        return self::factory()->call('query_address_by_user')->with($userId)->query();
    }

    public static function add($userId, $address, $phone, $phoneBk, $name, $geohash = '')
    {
        return self::factory()->call('add_address_new')->with($userId, $address, $phone, $phoneBk, $name, $geohash)->run();
    }

    public static function update($userId, $addressId, $address, $phone, $phoneBk, $name)
    {
        return self::factory()->call('update_address_new')->with($userId, $addressId, $address, $phone, $phoneBk, $name)->run();
    }

    public static function destroy($userId, $addressId)
    {
        return self::factory()->call('delete_address')->with($userId, $addressId)->run();
    }
}
