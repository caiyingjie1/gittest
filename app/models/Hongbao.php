<?php

use EUS\THongbaoQuery;

class Hongbao extends Model
{
    protected $service = 'eus';

    public static $defaultQueryMethod = 'queryByUserId';

    protected $visible = array(
        'id', 'sn', 'amount', 'used_amount', 'used_at', 'begin_date',
        'end_date', 'sum_condition', 'status', 'name', 'source'
    );

    protected $mutators = array(
        'begin_date', 'used_at', 'end_date'
    );

    public static function queryByUserId($userId, $beginDate = null, $endDate = null, $limit = 10, $offset = 0, $status = null)
    {
        $queryArray = self::getCommentQueryArray($userId, $beginDate, $endDate, $status);
        $queryArray['limit'] = $limit;
        $queryArray['offset'] = $offset;
        return self::factory()->call('query_hongbao')->with(new THongbaoQuery($queryArray))->query();
    }

    public static function count($userId, $beginDate = null, $endDate = null, $status = null)
    {
        $queryArray = self::getCommentQueryArray($userId, $beginDate, $endDate, $status);
        return self::factory()->call('count_hongbao')->with(new THongbaoQuery($queryArray))->result(0);
    }

    public static function exchange($userId, $exchangeCode)
    {
        return self::factory()->call('exchange_hongbao')->with($userId, $exchangeCode)->run();
    }

    public function getBeginDateAttribute($beginDate)
    {
        return date(DATE_ISO8601, strtotime($beginDate));
    }

    public function getUsedAtAttribute($usedAt)
    {
        return date(DATE_ISO8601, $usedAt);
    }

    public function getEndDateAttribute($endDate)
    {
        return date(DATE_ISO8601, strtotime($endDate));
    }

    private static function getCommentQueryArray($userId, $beginDate, $endDate, $status)
    {
        $queryArray = array(
            'user_id' => $userId,
            'statuses' => $status,
        );
        if ($beginDate !== null) {
            list($beginDateFrom, $beginDateTo) = explode(',', $beginDate);
            $queryArray['begin_date_from'] = empty($beginDateFrom) ? null : date('Y-m-d', strtotime($beginDateFrom));
            $queryArray['begin_date_to'] = empty($beginDateTo) ? null : date('Y-m-d', strtotime($beginDateTo));
        }
        if ($endDate !== null) {
            list($endDateFrom, $endDateTo) = explode(',', $endDate);
            $queryArray['end_date_from'] = empty($endDateFrom) ? null : date('Y-m-d', strtotime($endDateFrom));
            $queryArray['end_date_to'] = empty($endDateTo) ? null : date('Y-m-d', strtotime($endDateTo));
        }
        return $queryArray;
    }
}
