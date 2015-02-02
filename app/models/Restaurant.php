<?php

use Eleme\Zeus\Collection;
use Geohash\Geohash;

class Restaurant extends Model
{
    protected $service = 'ers';

    protected $visible = array(
        'id', 'name_for_url', 'image_path', 'name', 'phone', 'description', 'address', 'promotion_info',
        'is_premium', 'is_time_ensure', 'minimum_order_amount', 'minimum_order_description', 'order_lead_time', 'month_sales',
        'flavors', 'rating', 'status', 'is_in_book_time', 'opening_hours', 'is_free_delivery',
        'is_support_invoice', 'is_online_payment', 'minimum_free_delivery_amount', 'delivery_fee', 'time_ensure_full_description',
        'minimum_invoice_amount', 'is_new', 'is_third_party_delivery', 'certification_type', 'latitude', 'longitude'
    );

    protected $visibleRelations = array('food_activity', 'restaurant_activity', 'city', 'order_count');

    protected $visibleBy = array('ids', 'name_for_url', 'geohash', 'user_favor', 'user_favor_in_geohash');

    protected $mutators = array('is_premium', 'is_time_ensure');

    protected $appends = array(
        'image_path', 'rating', 'is_free_delivery', 'minimum_order_amount', 'order_lead_time', 'is_support_invoice', 'is_new',
        'is_third_party_delivery', 'is_in_book_time', 'rating_count', 'address', 'month_sales', 'delivery_fee', 'minimum_free_delivery_amount', 'minimum_order_description',
        'minimum_invoice_amount', 'is_open_time', 'opening_hours', 'book_times', 'is_online_payment', 'status'
    );

    const ORDER_MODE_PHONE = 1;
    const ORDER_MODE_ELEME_PROCESS = 2;
    const ORDER_MODE_PURE_NETWORK = 3;
    const ORDER_MODE_WIRELESS_PRINTER = 4;
    const ORDER_MODE_TPD = 5;
    const ORDER_MODE_OPENAPI = 6;
    const ORDER_MODE_TPD_ELEME = 7;
    const ORDER_MODE_TPD_NAPOS = 8;
    const ORDER_MODE_NAPOS_MOBILE_ANDROID = 9;
    const ORDER_MODE_NAPOS_MOBILE_IOS = 10;

    const BUSY_LEVEL_FREE = 0;
    const BUSY_LEVEL_CLOSED = 2;
    const BUSY_LEVEL_NETWORK_UNSTABLE = 3;
    const BUSY_LEVEL_HOLIDAY = 4;

    const TOTAL_STATUS_OPEN = 1; // can deliver now
    const TOTAL_STATUS_CLOSED = 2; // too busy to get order
    const TOTAL_STATUS_NETWORK_UNSTABLE = 3; // get order by phone temporarily
    const TOTAL_STATUS_RESTING = 4; // closed, out of service
    const TOTAL_STATUS_BOOKONLY = 5;
    const TOTAL_STATUS_ORDER_BY_PHONE = 6; // only get order by phone
    const TOTAL_STATUS_HOLIDAY = 8;

    const ATTRIBUTE_MUST_PAY_ONLINE = 'must_pay_online';

    public static function get($id)
    {
        return self::factory()->call('get')->with($id)->cache(1)->get();
    }

    public static function queryByIds($ids)
    {
        return self::factory()->call('mget')->with($ids)->query();
    }

    public static function queryByNameForUrl($nameForUrl)
    {
        return self::factory()->call('get_by_name_for_url')->with($nameForUrl)->cache(1)->query();
    }

    public static function queryByPsn($psn, $isPremium = null, $offset = 0, $limit = 300)
    {
        if (null === $isPremium) {
            $premiumRestaurants = self::factory()->call('query_premium_by_psn')->with($psn, $offset, $limit)->query();
            $restaurants = self::factory()->call('query_by_psn')->with($psn, $offset, $limit)->query();
            return $premiumRestaurants->merge($restaurants);
        } else {
            $method = $isPremium ? 'query_premium_by_psn' : 'query_by_psn';
            return self::factory()->call($method)->with($psn, $offset, $limit)->query();
        }
    }

    public static function mgetMap($ids)
    {
        $restaurants = self::queryByIds($ids); $restaurantMaps = array();
        foreach ($restaurants as $restaurant) {
            $restaurantMaps[$restaurant->id] = $restaurant;
        }
        return $restaurantMaps;
    }

    public static function queryByGeohash($geohash, $isPremium = null, $offset = 0, $limit = 300)
    {
        list($latitude, $longitude) = Geohash::decode($geohash);
        $poi = Poi::queryByLocation($latitude, $longitude);
        return $poi->isEmpty() ? new Collection : self::queryByPsn($poi->first()->psn, $isPremium, $offset, $limit);
    }

    public static function queryByUserFavor($userId)
    {
        return self::factory()->call('query_favor')->with($userId)->query();
    }

    public static function queryByUserFavorInGeohash($userId, $geohash)
    {
        return self::factory()->call('query_favor_by_geohash')->with($userId, $geohash)->query();
    }

    public static function initFoodActivity($collection)
    {
        $restaurantDict = array();
        foreach ($collection as $restaurant) {
            $restaurant->setRelation('food_activity', new Collection);
            $restaurantDict[$restaurant->id] = $restaurant;
        }
        $foodActivityMapList = FoodActivity::queryByRestaurantIds(array_keys($restaurantDict));
        foreach ($foodActivityMapList as $foodActivityMap) {
            foreach ($foodActivityMap['restaurant_ids'] as $restaurantId) {
                $restaurantDict[$restaurantId]->getRelation('food_activity')->push($foodActivityMap['food_activity']);
            }
        }
    }

    public static function initRestaurantActivity($collection)
    {
        $restaurantDict = array();
        foreach ($collection as $restaurant) {
            $restaurant->setRelation('restaurant_activity', new Collection);
            $restaurantDict[$restaurant->id] = $restaurant;
        }
        $restaurantActivityMapList = RestaurantActivity::queryByRestaurantIds(array_keys($restaurantDict));
        foreach ($restaurantActivityMapList as $restaurantActivityMap) {
            foreach ($restaurantActivityMap['restaurant_ids'] as $restaurantId) {
                $restaurantDict[$restaurantId]->getRelation('restaurant_activity')->push($restaurantActivityMap['restaurant_activity']);
            }
        }
    }

    public static function initCity($collection)
    {
        $cityIds = $collection->lists('city_id');
        $cityMap= City::mgetMap($cityIds);
        foreach ($collection as $restaurant) {
            $city = array_key_exists($restaurant->city_id, $cityMap) ? $cityMap[$restaurant->city_id] : null;
            $restaurant->setRelation('city', $city);
        }
    }

    public static function initOrderCount($collection)
    {
        $restaurantIds = $collection->lists('id');
        if ($restaurantIds) {
            $orderCountWithIds = Order::queryRecentOrderCountFromRedis($restaurantIds);
            foreach ($orderCountWithIds as $id => $count) {
                foreach ($collection as $restaurant) {
                    if($restaurant->id === $id) {
                        $restaurant->setRelation('order_count', $count);
                    }
                }
            }
        }
    }

    public function getMinimumFreeDeliveryAmountAttribute()
    {
        return $this->no_agent_fee_total;
    }

    public function getDeliveryFeeAttribute()
    {
        return $this->agent_fee;
    }

    public function getIsPremiumAttribute($isPremium)
    {
        return (bool)$isPremium;
    }

    public function getIsTimeEnsureAttribute($isTimeEnsure)
    {
        return (bool)$isTimeEnsure;
    }

    public function getAddressAttribute()
    {
        return $this->address_text;
    }

    public function getMinimumOrderDescriptionAttribute()
    {
        return $this->deliver_description;
    }

    public function getMonthSalesAttribute()
    {
        return $this->recent_order_num;
    }

    public function getOrderLeadTimeAttribute()
    {
        $coef1 = $this->speed_coef1;
        $coef2 = $this->speed_coef2;
        $coef3 = $this->speed_coef3;
        if ($coef3 != 0) {
            $orderCount = $this->order_count;
            $max_dot = -0.5 * $coef2 / $coef3;
            $orderCount = $coef3 < 0 ? min($max_dot, $orderCount) : max($max_dot, $orderCount);
            $time = $coef1 + $coef2 * $orderCount + $coef3 * pow($orderCount, 2);
            $time = round($time, 0);
        } else {
            $time = $this->deliver_spent;
        }
        return $time;
    }

    public function getImagePathAttribute()
    {
        return $this->image_hash ? preg_replace('/^(.)(..)(.{29}(.+))/', '/$1/$2/$3.$4', $this->image_hash) : '';
    }

    public function getRatingAttribute()
    {
        $ratingCount = array_sum($this->num_ratings);
        if ($ratingCount == 0) {
            return 3;
        }

        $rating = 0;
        for ($i = 1; $i <= 5; $i++) {
            $rating += $i * $this->num_ratings[$i - 1] / $ratingCount;
        }
        return round($rating, 1);
    }

    public function getStatusAttribute()
    {
        if ($this->busy_level === self::BUSY_LEVEL_CLOSED) {
            return self::TOTAL_STATUS_CLOSED;
        }

        if ($this->busy_level === self::BUSY_LEVEL_HOLIDAY) {
            return self::TOTAL_STATUS_HOLIDAY;
        }

        if ($this->order_mode === self::ORDER_MODE_PHONE) {
            if (!$this->is_open_time) {
                return self::TOTAL_STATUS_RESTING;
            }
            return self::TOTAL_STATUS_ORDER_BY_PHONE;
        }

        if ($this->busy_level === self::BUSY_LEVEL_NETWORK_UNSTABLE) {
            return self::TOTAL_STATUS_NETWORK_UNSTABLE;
        }

        if (!$this->is_open_time) {
            if ($this->is_in_book_time) {
                return self::TOTAL_STATUS_BOOKONLY;
            }
            return self::TOTAL_STATUS_RESTING;
        }

        return self::TOTAL_STATUS_OPEN;
    }

    public function getIsInBookTimeAttribute()
    {
        $date = getdate();
        if ($date['hours'] < 6 || $date['hours'] >= 23) {
            return false;
        }
        $time = (int) ($date['hours'] * 12 + $date['minutes'] / 5);
        return false !== strpos($this->book_time_bitmap, '1', $time + 6);
    }

    public function getBookTimesAttribute()
    {
        $bitmap = $this->book_time_bitmap;
        $bookTimeArray = array();
        $today = strtotime('today');
        $begin = ((int) ceil((time() - $today) / 900) + 2) * 3;
        $begin = $begin > 6 * 12 ? $begin : 6 * 12;
        $end = 23 * 12;
        for ($i = $begin; $i < $end; $i += 3) {
            if ($bitmap[$i] == 1) {
                $bookTimeArray[] = date('H:i:s', $today + $i * 300);
            }
        }
        return $bookTimeArray;
    }

    public function getIsOnlinePaymentAttribute()
    {
        return $this->online_payment && $this->isOnlinePaymentMode();
    }

    public function getMinimumInvoiceAmountAttribute()
    {
        return (float)$this->invoice_min_amount;
    }

    public function getMinimumOrderAmountAttribute()
    {
        return (int)$this->deliver_amount;
    }

    public function getRatingCountAttribute()
    {
        return array_sum($this->num_ratings);
    }

    public function isOnlinePaymentMode()
    {
        return in_array($this->order_mode, array(
            self::ORDER_MODE_PURE_NETWORK,
            self::ORDER_MODE_ELEME_PROCESS,
            self::ORDER_MODE_NAPOS_MOBILE_ANDROID,
            self::ORDER_MODE_NAPOS_MOBILE_IOS,
            self::ORDER_MODE_WIRELESS_PRINTER,
            self::ORDER_MODE_TPD_ELEME
        ));
    }

    public function getIsFreeDeliveryAttribute()
    {
        return $this->agent_fee <= 0;
    }

    public function getIsSupportInvoiceAttribute()
    {
        return $this->invoice >= 1;
    }

    public function getIsNewAttribute()
    {
        return time() - $this->created_at < 3600*24*30;
    }

    public function getIsThirdPartyDeliveryAttribute()
    {
        return $this->order_mode == self::ORDER_MODE_TPD;
    }

    public function getIsOpenTimeAttribute()
    {
        $date = getdate();
        $time = (int) ($date['hours'] * 12 + $date['minutes'] / 5);
        return (bool) $this->open_time_bitmap[$time];
    }

    public function getopeningHoursAttribute()
    {
        $bitmap = $this->open_time_bitmap;
        $prev = '0';
        $openTimeArray = array();
        $tempTime = '';
        for ($i = 0, $len = strlen($bitmap); $i < $len; $i++) {
            $current = $bitmap[$i];
            $next = $i < $len - 1 ? $bitmap[$i+1] : '0';
            if ($current == '1' && $prev == '0') {
                $currentString = sprintf('%02d:%02d', (int)(($i)/12), ($i%12)*5);
                $tempTime = $currentString;
            }
            if ($current == '1' && $next == '0') {
                $currentString = sprintf('%02d:%02d', (int)(($i+1)/12), (($i+1)%12)*5);
                $tempTime .= '/' . $currentString;
                array_push($openTimeArray, $tempTime);
            }
            $prev = $current;
        }
        return $openTimeArray;
    }

}
