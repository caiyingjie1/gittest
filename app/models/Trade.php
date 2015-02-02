<?php

use EUS\TTradeRecordQuery;

class Trade extends Model
{
    const TYPE_CHARGE = 0;
    const TYPE_CONSUME = 1;
    const TYPE_REFUND = 2;

    const STATUS_PROCESSING = 0;
    const STATUS_SUCCESS = 1;
    const STATUS_FAIL = 2;

    protected $service = 'eus';

    protected $visible = array('type', 'trade_no', 'amount', 'status', 'remark', 'created_at');

    protected $mutators = array('created_at', 'remark');

    public static function count($userId, $tradeType = null, $days = null, $status = null)
    {
        $queryArray = self::getQueryArray($userId, $tradeType, $days, $status);
        return self::factory()->call('count_trade_record')->with(new TTradeRecordQuery($queryArray))->run();
    }

    public static function queryByUserId($userId, $limit = 10, $offset = 0, $tradeType = null, $days= null, $status = null)
    {
        $queryArray = self::getQueryArray($userId, $tradeType, $days, $status);
        $queryArray['limit'] = $limit;
        $queryArray['offset'] = $offset;
        return self::factory()->call('query_trade_record')->with(new TTradeRecordQuery($queryArray))->query();
    }

    public function getCreatedAtAttribute($createdAt)
    {
        return date(DATE_ISO8601, $createdAt);
    }

    public function getRemarkAttribute($remark)
    {
        return json_decode($remark);
    }

    private static function getQueryArray($userId, $tradeType, $days, $status)
    {
        $queryArray = array(
            'user_id' => $userId,
            'categories' => $tradeType,
            'statuses' => $status
        );
        if ($days) {
            $queryArray['from_datetime'] = time() - $days * 24 * 60 * 60;
        }
        $queryArray['to_datetime'] = time();
        return $queryArray;
    }
}
