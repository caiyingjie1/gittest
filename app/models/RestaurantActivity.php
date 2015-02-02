<?php

use ERS\RestaurantActivityConst;

class RestaurantActivity extends Model
{
    protected $service = 'ers';

    protected $visible = array('id', 'type', 'name', 'attribute', 'description', 'icon_color', 'icon_name');

    protected $visibleRelations = array();

    protected $visibleBy = array();

    protected $mutators = array('description');

    protected $appends = array('name', 'icon_color', 'icon_name');

    private static $nameMap = array(
        RestaurantActivityConst::TYPE_COUPON => '抵价券',
        RestaurantActivityConst::TYPE_NEW_USER_DISCOUNT => '新用户优惠',
        RestaurantActivityConst::TYPE_EXTRA_DISCOUNT => '满立减',
        RestaurantActivityConst::TYPE_OLPAYMENT_REDUCE => '在线支付优惠',
        RestaurantActivityConst::TYPE_NEW_USER_DISCOUNT_EXCLUSIVE => '新用户优惠',
        RestaurantActivityConst::TYPE_ADVANCED_DISCOUNT => '满减优惠',
        RestaurantActivityConst::TYPE_ORDER_HONGBAO => '下单返红包',
    );

    private static $classMap = array(
        RestaurantActivityConst::TYPE_COUPON => 'coupon',
        RestaurantActivityConst::TYPE_NEW_USER_DISCOUNT => 'new-user-discount',
        RestaurantActivityConst::TYPE_EXTRA_DISCOUNT => 'extra-discount',
        RestaurantActivityConst::TYPE_OLPAYMENT_REDUCE => 'more-discount',
        RestaurantActivityConst::TYPE_NEW_USER_DISCOUNT_EXCLUSIVE => 'new-user-discount',
        RestaurantActivityConst::TYPE_ADVANCED_DISCOUNT => 'extra-discount',
        RestaurantActivityConst::TYPE_ORDER_HONGBAO => 'get-hongbao',
    );
    private static $iconNameMap = array(
        RestaurantActivityConst::TYPE_COUPON => '抵',
        RestaurantActivityConst::TYPE_NEW_USER_DISCOUNT => '首',
        RestaurantActivityConst::TYPE_EXTRA_DISCOUNT => '减',
        RestaurantActivityConst::TYPE_OLPAYMENT_REDUCE => '减',
        RestaurantActivityConst::TYPE_NEW_USER_DISCOUNT_EXCLUSIVE => '首',
        RestaurantActivityConst::TYPE_ADVANCED_DISCOUNT => '减',
        RestaurantActivityConst::TYPE_ORDER_HONGBAO => '返',
    );
    private static $iconColorMap = array(
        RestaurantActivityConst::TYPE_COUPON => '4CB4D4',
        RestaurantActivityConst::TYPE_NEW_USER_DISCOUNT => '6E782F',
        RestaurantActivityConst::TYPE_EXTRA_DISCOUNT => 'DC2100',
        RestaurantActivityConst::TYPE_OLPAYMENT_REDUCE => 'C518DA',
        RestaurantActivityConst::TYPE_NEW_USER_DISCOUNT_EXCLUSIVE => '6E782F',
        RestaurantActivityConst::TYPE_ADVANCED_DISCOUNT => '679D3C',
        RestaurantActivityConst::TYPE_ORDER_HONGBAO => 'FF0000',
    );

    public static function queryByRestaurantIds($ids)
    {
        $tActivityWithIds = self::factory('ers')->call('get_restaurant_activity_with_restaurant_ids')
            ->with($ids, [])
            ->result([]);
        $activityWithIds= [];
        foreach ($tActivityWithIds as $tActivityWithId) {
            $activityWithIds[] = [
                'restaurant_activity' => new self($tActivityWithId->restaurant_activity),
                    'restaurant_ids' => $tActivityWithId->restaurant_ids
                    ];
        }
        return $activityWithIds;
    }

    public static function queryByRestaurantId($id)
    {
        return self::factory()->call('query_restaurant_activity_by_restaurant')->with($id)->query();
    }

    public function getNameAttribute()
    {
        return self::$nameMap[$this->type];
    }

    public function getIconColorAttribute()
    {
        return self::$iconColorMap[$this->type];
    }

    public function getIconNameAttribute()
    {
        return self::$iconNameMap[$this->type];
    }

    public function getDescriptionAttribute()
    {
        switch ($this->type) {
            case RestaurantActivityConst::TYPE_COUPON:
                return sprintf(
                    '在该餐厅用抵价券订餐可抵%d元(抵价券不可与其他活动同时享用)',
                    $this->attribute
                );
            case RestaurantActivityConst::TYPE_NEW_USER_DISCOUNT:
                return sprintf('饿了么新用户首次订餐，可立减%d元', $this->attribute);
            case RestaurantActivityConst::TYPE_NEW_USER_DISCOUNT_EXCLUSIVE:
                return sprintf(
                    '饿了么新用户首次订餐，可立减%d元。(不与其他活动同享)',
                    $this->attribute
                );
            case RestaurantActivityConst::TYPE_EXTRA_DISCOUNT:
                $discounts = json_decode($this->attribute);
                $discountInfoArray = array();
                foreach ($discounts as $require => $discount) {
                    $discountInfoArray[] = sprintf('满%s元立减%s元', $require, $discount);
                }
                return sprintf('该餐厅支持立减优惠，每单%s', implode('，', $discountInfoArray));
            case RestaurantActivityConst::TYPE_ORDER_HONGBAO:
                if (is_numeric($this->attribute)) {
                    $description = sprintf('下单即返最高%d元红包', $this->attribute);
                } else {
                    $discounts = json_decode($this->attribute);
                    $discountInfoArray = array();
                    foreach ($discounts as $require => $discount) {
                        $discountInfoArray[] = sprintf('满%s元返%s元红包', $require, $discount);
                    }
                    $description = sprintf('在该餐厅下单%s', implode('，', $discountInfoArray));
                }
                return $description;
            case RestaurantActivityConst::TYPE_OLPAYMENT_REDUCE:
                if (is_numeric($this->attribute)) {
                    $description = sprintf('在线支付再减%d元', $this->attribute);
                } else {
                    $discounts = json_decode($this->attribute);
                    $discountInfoArray = array();
                    foreach ($discounts as $require => $discount) {
                        $discountInfoArray[] = sprintf('满%s元立减%s元', $require, $discount);
                    }
                    $description = sprintf('在线支付%s', implode('，', $discountInfoArray));
                }
                return $description;
            case RestaurantActivityConst::TYPE_ADVANCED_DISCOUNT:
                return implode(',', explode(',', $this->description, -1));
            default:
                return '';
        }
    }
}
