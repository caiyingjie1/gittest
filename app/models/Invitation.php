<?php

use EUS\TReferQuery;

class Invitation extends Model
{
    const STATUS_WAITING = 0;
    const STATUS_VALID = 1;
    const STATUS_AWARDED = 2;
    const STATUS_INVALID = 3;
    const STATUS_REFERER_AWARDED = 4;
    const STATUS_BAD_REFER = 5;
    const STATUS_NOT_IN_SERVICE = 6;

    protected $service = 'eus';

    protected $visible = array('to_username', 'created_at', 'is_award');

    protected $mutators = array('created_at');

    protected $appends = array('is_award');

    public static function queryByUserId($userId, $offset = 0, $limit = 10)
    {
        $queryArray = array(
            'offset' => $offset,
            'limit' => $limit,
            'from_user_id' => $userId
        );
        return self::factory()->call('query_refer')->with(new TReferQuery($queryArray))->query();
    }

    public function getCreatedAtAttribute($createdAt)
    {
        return date(DATE_ISO8601, $createdAt);
    }

    public function getIsAwardAttribute()
    {
        return in_array($this->mobile_status, array(self::STATUS_AWARDED, self::STATUS_INVALID, self::STATUS_REFERER_AWARDED)) ? 1 : 0;
    }
}
