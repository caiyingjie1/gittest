<?php

class UserGift extends Model
{
    protected $service = 'eus';

    protected $visible = array('gift_id');

    public static function queryByUserId($userId)
    {
        return self::factory()->call('query_user_gift')->with($userId)->query();
    }

    public static function exchange($userId, $giftId, $userName, $userAddress, $userPhone, $userNote)
    {
        self::factory()->call('exchange_gift')->with($userId, $giftId, $userName, $userAddress, $userPhone, $userNote)->run();
    }
}
