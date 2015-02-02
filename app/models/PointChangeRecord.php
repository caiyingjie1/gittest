<?php

use EUS\TWallePointChangeQuery;

class PointChangeRecord extends Model
{
    protected $service = 'eus';

    protected $visible = array(
        'id', 'created_at', 'delta', 'reason', 'relevant_id', 'change_type'
    );

    protected $mutators = array('created_at');

    public static function queryByUserId($userId, $datetime = null, $limit = 10, $offset = 0)
    {
        $queryArray = array(
            'user_id' => $userId,
            'offset' => $offset,
            'limit' => $limit,
        );
        if (is_null($datetime)) {
            $queryArray['from_datetime'] = time() - 30 * 24 * 60 * 60;
        } else {
            list($fromDatetime, $toDatetime) = explode(',', $datetime);
            $queryArray['from_datetime'] = empty($fromDatetime) ? null : strtotime($fromDatetime);
            $queryArray['to_datetime'] = empty($toDatetime) ? null : strtotime($toDatetime);
        }
        return self::factory()->call('walle_query_point_change')->with(new TWallePointChangeQuery($queryArray))->query();
    }

    public function getCreatedAtAttribute($createdAt)
    {
        return date(DATE_ISO8601, $createdAt);
    }
}
