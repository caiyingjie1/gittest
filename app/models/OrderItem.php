<?php

class OrderItem extends Model
{
    protected $service = 'eos';

    protected $visible = array(
        'id', 'order_id', 'name', 'quantity', 'price', 'rating'
    );

    protected $visibleRelations = array('rating');

    public static function mgetMap(array $orderIds, $extras = array())
    {
        $orderItems = self::mget($orderIds);
        if ($extras) $orderItems->extras($extras);
        $orderItemMaps = array();
        foreach ($orderItems as $orderItem) {
            $orderItemMaps[$orderItem->order_id][] = $orderItem;
        }
        return $orderItemMaps;
    }

    public static function mget(array $orderIds)
    {
        return self::factory()->call('mget_order_item_by_order_id')->with($orderIds)->query();
    }

    public static function initRating($collection)
    {
        $orderItemIds = $collection->lists('id');
        $orderItemRatingsMap = OrderItemRating::mget($orderItemIds);
        foreach ($collection as $orderItem) {
            $orderItemRating = array_key_exists($orderItem->id, $orderItemRatingsMap) ? $orderItemRatingsMap[$orderItem->id] : null;
            $orderItem->setRelation('rating', $orderItemRating);
        }
    }
}
