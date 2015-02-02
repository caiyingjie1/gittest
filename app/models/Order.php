<?php

use EOS\TOrderQuery;

class Order extends Model
{
    const ORDER_TYPE_LASTMONTH = 1;
    const ORDER_TYPE_BEFOREMONTH = 2;
    const ORDER_TYPE_UNRATED = 3;
    const ORDER_TYPE_REFUNDING = 4;

    const STATUS_NOT_PAID = -5;
    const STATUS_PAYMENT_FAILED = -4;
    const STATUS_PAYING = -3;
    const STATUS_PENDING = -2;
    const STATUS_INVALID = -1;
    const STATUS_UNPROCESSED = 0;
    const STATUS_PROCESSING = 1;
    const STATUS_PROCESSED_AND_VALID = 2;
    const STATUS_REFUNDING = 3;
    const STATUS_COMPLETE = 4; //status not in database

    const REFUND_STATUS_NO_REFUND = 0;
    const REFUND_STATUS_LATER_REFUND_FAIL = 5;
    const REFUND_STATUS_LATER_REFUND_SUCCESS = 6;

    const ORDER_BOOK_REFUND_EXPIRE = 3600;
    const ORDER_REFUND_EXPIRE = 86400;

    const CATEGORY_ALL_ORDER = -1;
    const CATEGORY_ELEME_ORDER = 1;
    const CATEGORY_DINE_ORDER = 2;
    const CATEGORY_PHONE_ORDER = 3;
    const CATEGORY_XPFOOD_ORDER = 4;
    const CATEGORY_NAPOS_WAIMAI_ORDER = 5;
    const CATEGORY_NAPOS_TANGCHI_ORDER = 6;
    const CATEGORY_BIYUN_ORDER = 7;

    const ORDER_MODE_ALL = -1;
    const ORDER_MODE_PHONE = 1;
    const ORDER_MODE_ELEME_PROCESS = 2;
    const ORDER_MODE_PURE_NETWORK = 3;
    const ORDER_MODE_WIRELESS_PRINTER = 4;
    const ORDER_MODE_THIRD_PARTY_DELIVERY = 5;

    const HASH_SECRET = '$ecret';

    const ORDER_COMPLAINT_SUPPORT = 0;
    const ORDER_COMPLAINT_EXISTED = 1;
    const ORDER_COMPLAINT_STATUS_ERROR = 2;

    private static $liveStatus = array(
        self::STATUS_UNPROCESSED,
        self::STATUS_PROCESSING,
        self::STATUS_PROCESSED_AND_VALID,
    );

    protected $service = 'eos';

    protected $visible = array(
        'id', 'restaurant_id', 'restaurant_name', 'user_id', 'user_name', 'detail_json', 'total',
        'deliver_fee', 'is_online_paid', 'settled_at', 'address', 'phone', 'description', 'unique_id',
        'status_code', 'refund_status', 'is_book', 'deliver_time', 'created_at', 'invoice', 'active_at',
        'complaint_status', 'is_refund_valid', 'is_phone_refund_valid'
    );

    protected $visibleRelations = array('order_hash_key', 'restaurant', 'order_item', 'order_rate', 'payment_constitution');

    protected $visibleBy = array('last_mouth', 'before_mouth', 'unrated', 'refunding');

    protected $mutators = array('created_at', 'active_at', 'settled_at', 'deliver_time');

    protected $appends = array('complaint_status', 'is_refund_valid', 'is_phone_refund_valid');

    public static function queryRecentOrderCountFromRedis($restaurantIds)
    {
        $result = self::factory()->call('redis_mcount_order')->with($restaurantIds)->result(array());
        return $result ? array_combine(array_values($restaurantIds), array_values($result)) : array();
    }

    public static function get($id)
    {
        return self::factory()->call('get')->with($id)->get();
    }

    public static function queryByLastMouth($userId, $limit = 10, $offset = 0)
    {
        $queryArray = self::getCommentQuery($userId, $limit, $offset);
        $queryArray['from_datetime'] = time() - 30 * 24 * 60 * 60;
        return self::factory()->call('query_order')->with(new TOrderQuery($queryArray))->query(array());
    }

    public static function queryByBeforeMouth($userId, $limit = 10, $offset = 0)
    {
        $queryArray = self::getCommentQuery($userId, $limit, $offset);
        return self::factory()->call('query_old_order')->with(new TOrderQuery($queryArray))->query(array());
    }

    public static function queryByUnrated($userId)
    {
        return self::factory()->call('query_rateable_orders_new')->with($userId)->query();
    }

    public static function queryByRefunding($userId, $limit = 10, $offset = 0)
    {
        $queryArray = self::getCommentQuery($userId, $limit, $offset);
        $queryArray['statuses'] = array(self::ORDER_TYPE_REFUNDING);
        return self::factory()->call('query_order')->with(new TOrderQuery($queryArray))->query(array());
    }

    public static function count($userId, $type = null)
    {
        $queryArray = array(
            'user_id' => $userId,
            'category_id' => self::CATEGORY_ELEME_ORDER
        );
        if ($type == 'last_mouth') {
            $queryArray['from_datetime'] = time() - 30 * 24 * 60 * 60;
            return self::factory()->call('count_order')->with(new TOrderQuery($queryArray))->result(0);
        }
        if ($type == 'before_mouth') {
            return self::factory()->call('count_old_order')->with(new TOrderQuery($queryArray))->result(0);
        }
        return 0;
    }

    public static function checkOrderComplaintExisted($orderId)
    {
        return self::factory()->call('check_order_complaint_existed')->with($orderId)->result(true);
    }

    public static function addOrderComplaint($userId, $orderId, $type, $content)
    {
        return self::factory()->call('add_order_complaint')->with($userId, $orderId, $type, $content)->run();
    }

    public static function initOrderHashKey($collection)
    {
        foreach ($collection as $order) {
            $order->setRelation('order_hash_key', md5($order->unique_id . self::HASH_SECRET));
        }
    }

    public static function initRestaurant($collection)
    {
        $restaurantIds = $collection->lists('restaurant_id');
        $restaurantMaps = Restaurant::mgetMap($restaurantIds);
        foreach ($collection as $order) {
           $restaurant = array_key_exists($order->restaurant_id, $restaurantMaps) ? $restaurantMaps[$order->restaurant_id] : null;
           $order->setRelation('restaurant', $restaurant);
        }
    }

    public static function initOrderItem($collection)
    {
        $orderIds = $collection->lists('unique_id');
        $orderItemMaps = OrderItem::mgetMap($orderIds, array('rating'));
        foreach ($collection as $order) {
            $orderItem = array_key_exists($order->unique_id, $orderItemMaps) ? $orderItemMaps[$order->unique_id] : array();
            $order->setRelation('order_item', $orderItem);
        }
    }

    public static function initOrderRate($collection)
    {
        $orderIds = $collection->lists('unique_id');
        $orderRateMaps = OrderRate::mgetMap($orderIds);
        foreach ($collection as $order) {
            $orderRate = array_key_exists($order->unique_id, $orderRateMaps) ? $orderRateMaps[$order->unique_id] : null;
            $order->setRelation('order_rate', $orderRate);
        }
    }

    public static function initPaymentConstitution($collection)
    {
        $orderIds = $collection->lists('unique_id');
        $paymentConstitutionMaps = PaymentConsitution::getPreferentialConstitutionMap($orderIds);
        foreach ($collection as $order) {
            $order->setRelation('payment_constitution', $paymentConstitutionMaps[$order->unique_id]);
        }
    }

    public function getCreatedAtAttribute($createdAt)
    {
        return date(DATE_ISO8601, $createdAt);
    }

    public function getActiveAtAttribute($activeAt)
    {
        return date(DATE_ISO8601, $activeAt);
    }

    public function getSettledAtAttribute($settleAt)
    {
        return date(DATE_ISO8601, $settleAt);
    }

    public function getDeliverTimeAttribute($deliverTime)
    {
        return date(DATE_ISO8601, $deliverTime);
    }

    public function getComplaintStatusAttribute()
    {
        if (self::checkOrderComplaintExisted($this->unique_id)) {
            return self::ORDER_COMPLAINT_EXISTED;
        } elseif ($this->status_code === self::STATUS_PROCESSED_AND_VALID
            && time() - strtotime($this->created_at)  < 30 * 24 * 60 * 60
        ) {
            return self::ORDER_COMPLAINT_SUPPORT;
        } else {
            return self::ORDER_COMPLAINT_STATUS_ERROR;
        }
    }

    public function getIsRefundValidAttribute()
    {
        $status = $this->is_online_paid && !$this->settled_at && in_array($this->status_code, self::$liveStatus)
            && $this->order_mode !== self::ORDER_MODE_THIRD_PARTY_DELIVERY && $this->refund_status === REFUND_STATUS_NO_REFUND;
        $isTimeValid = false;
        if ($this->is_book) {
            $isTimeValid = (time() - $this->deliver_time) <= self::ORDER_BOOK_REFUND_EXPIRE;
        } else {
            $isTimeValid = (time() - $this->created_at) <= self::ORDER_REFUND_EXPIRE;
        }
        return (int) ($status && $isTimeValid);
    }

    public function getIsPhoneRefundValidAttribute()
    {
        return (int) (!$this->settled_at && in_array($this->status_code,self::$liveStatus));
    }

    private static function getCommentQuery($userId, $limit, $offset)
    {
        return array(
            'user_id' => $userId,
            'limit' => $limit,
            'offset' => $offset,
            'category_id' => self::CATEGORY_ELEME_ORDER
        );
    }
}
