<?php

class OrderRate extends Model
{
    protected $service = 'eos';

    protected $visible = array('id', 'order_id', 'time_spent', 'service_rating', 'service_rating_text');

    public static function mgetMap(array $orderIds)
    {
        $orderRates = self::query($orderIds);
        $orderRateMaps = array();
        foreach ($orderRates as $orderRate) {
            $orderRateMaps[$orderRate->order_id] = $orderRate;
        }
        return $orderRateMaps;
    }

    public static function query(array $orderIds)
    {
        return self::factory()->call('query_order_rate')->with($orderIds)->query(array());
    }

    public static function rateDeliverTimeSpent($orderId, $userId, $timeSpent)
    {
        return self::factory()->call('rate_deliver_time_spent')->with($orderId, $userId, $timeSpent)->run();
    }

    public static function rateService($orderId, $userId, $value, $ratingText)
    {
        return self::factory()->call('rate_service')->with($orderId, $userId, $value, $ratingText)->run();
    }
}
