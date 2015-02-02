<?php

class OrderRefunding extends Model
{
    const ASC = 0;

    const REFUND_STATUS_LATER_REFUND_REQUEST = 2;
    const REFUND_STATUS_LATER_REFUND_RESPONSE = 3;
    const REFUND_STATUS_LATER_REFUND_ARBITRATING = 4;
    const REFUND_STATUS_LATER_REFUND_FAIL = 5;
    const REFUND_STATUS_LATER_REFUND_SUCCESS = 6;

    const REFUND_TYPE_SLOW = 'slow';
    const REFUND_TYPE_TIMEOUT = 'timeout';
    const REFUND_TYPE_QUALTITY_PROBLEM = 'quality_problem';
    const REFUND_TYPE_OTHER = 'other';

    private static $refundType = array(
        self::REFUND_TYPE_SLOW => "送餐速度过慢",
        self::REFUND_TYPE_TIMEOUT => "外卖质量问题",
        self::REFUND_TYPE_QUALTITY_PROBLEM => "送餐速度过慢",
        self::REFUND_TYPE_OTHER => "其它",
    );

    protected $service = 'eos';

    protected $visible = array('process_group', 'to_status', 'content', 'resource', 'created_at');

    protected $mutators = array('created_at', 'content');

    public static function query($orderId)
    {
        return self::factory()->call('query_refund_record')->with(array($orderId), self::ASC)->query();
    }

    public static function apply($userId, $orderId, $type, $reason, $resource = '')
    {
        $content = json_encode(array('type' => self::$refundType[$type], 'reason' => $reason));
        return self::factory()->call('refund_apply')->with($userId, $orderId, $content, $resource)->run();
    }

    public static function cancel($userId, $orderId, $password)
    {
        return self::factory()->call('refund_cancel')->with($userId, $orderId, $password)->run();
    }

    public static function arbirtate($userId, $orderId, $reason)
    {
        $content = json_encode(array('reason' => $reason));
        return self::factory()->call('refund_arbitrate')->with($userId, $orderId, $content)->run();
    }

    public function getCreatedAtAttribute($createdAt)
    {
        return date(DATE_ISO8601, $createdAt);
    }

    public function getContentAttribute($content)
    {
        return json_decode($content);
    }
}
