<?php

class OrderItemRating extends Model
{
    protected $service = 'eos';

    protected $visible = array(
        'rating', 'rating_text'
    );

    public static function get($orderItemId)
    {
        return self::factory()->call('get_order_item_rating')->with($orderItemId)->get();
    }

    public static function mget($orderItemIds)
    {
        return self::factory()->call('mget_order_item_rating')->with($orderItemIds)->query();
    }

    public static function mgetMap($orderItemIds)
    {
        $orderItemRatings = self::mget($orderItemIds);
        $orderItemRatingsMap = array();
        foreach ($orderItemRatings as $orderItemRating) {
            $orderItemRatingsMap[$orderItemRating->order_item_id] = $orderItemRating;
        }
        return $orderItemRatingsMap;
    }

    public static function rateOrderItem($orderItemId, $userId, $ratingValue, $ratingText)
    {
        return self::factory()->call('rate_order_item')->with($orderItemId, $userId, $ratingValue, $ratingText)->run();
    }
}
