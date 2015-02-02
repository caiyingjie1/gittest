<?php

use EUS\TGiftQuery;
use Eleme\Zeus\Collection;

class Gift extends Model
{
    protected $service = 'eus';

    protected $visible = array(
        'id', 'name', 'description', 'price', 'amount', 'image_path', 'thumb_path'
    );

    protected $appends = array('image_path', 'thumb_path');

    const MAX_LIST_SIZE = 200;

    public static function query()
    {
        $giftQuery = new TGiftQuery();
        $giftQuery->is_valid = true;
        $giftQuery->min_amount = 1;
        $giftQuery->limit = self::MAX_LIST_SIZE;

        return self::factory()->call('query_gift')->with($giftQuery)->query();
    }

    public static function getGiftsByIds($ids)
    {
        return is_array($ids) ? self::factory()->call('mget_gift')->with($ids)->query() : new Collection;
    }

    public static function getGift($giftId)
    {
        return self::factory()->call('get_gift')->with($giftId)->get();
    }

    public function getImagePathAttribute()
    {
        return $this->image_hash ? preg_replace('/^(.)(..)(.{29}(.+))/', '/$1/$2/$3.$4', $this->image_hash) : '';
    }

    public function getThumbPathAttribute()
    {
        return $this->thumb_hash ? preg_replace('/^(.)(..)(.{29}(.+))/', '/$1/$2/$3.$4', $this->thumb_hash) : '';
    }
}
