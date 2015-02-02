<?php

use EUS\TWalleBalanceChangeQuery;

class BalanceChangeRecord extends Model
{
    const TRADE_TYPE_CHARGE = 0;
    const TRADE_TYPE_CONSUME = 1;
    const TRADE_TYPE_PRODUCE = 2;
    const TRADE_TYPE_REFUND = 3;
    const TRADE_TYPE_WITHDRAW_APPLY = 4;
    const TRADE_TYPE_WITHDRAW_FAIL = 5;

    protected $service = 'eus';

    protected $visible = array('id', 'balance', 'balance_change', 'trade_type', 'created_at');

    protected $mutators = array('created_at');

    public static function queryByUserId($userId, $datetime = null, $limit = 10, $offset = 0, $tradeType = null)
    {
        $queryArray = array(
            'user_id' => $userId,
            'trade_types' => $tradeType,
            'limit' => $limit,
            'offset' => $offset
        );
        if (is_null($datetime)) {
            $queryArray['from_datetime'] = time() - 30 * 24 * 60 * 60;
        } else {
            list($fromDatetime, $toDatetime) = explode(',', $datetime);
            $queryArray['from_datetime'] = empty($fromDatetime) ? null : strtotime($fromDatetime);
            $queryArray['to_datetime'] = empty($toDatetime) ? null : strtotime($toDatetime);
        }
        return self::factory()->call('walle_query_balance_change')->with(new TWalleBalanceChangeQuery($queryArray))->query(array());
    }

    public function getCreatedAtAttribute($createdAt)
    {
        return date(DATE_ISO8601, $createdAt);
    }
}
