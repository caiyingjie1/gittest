<?php

class UserProfile extends Model
{
    protected $service = 'eus';

    protected $visible = array('current_address_id', 'balance', 'payment_quota',
        'point', 'email', 'is_email_valid', 'mobile', 'is_mobile_valid', 'user_id',
        'avatar', 'current_invoice_id', 'username', 'is_active', 'favor_foods_count',
        'favor_restaurants_count', 'orders_count', 'referal_code'
    );

    protected $visibleRelations = array('favor_foods_count', 'favor_restaurants_count', 'orders_count');

    protected $mutators = array('referal_code', 'avatar');

    protected $appends = array('username', 'is_active');

    private $user;

    public static function get($userId)
    {
        return self::factory()->call('get_profile')->with($userId)->get();
    }

    public static function getUserReferalCode($userId)
    {
        return self::factory()->call('get_user_referal_code')->with($userId)->run();
    }

    public static function getByMobile($mobile)
    {
        $full = self::factory()->call('get_full_by_valid_mobile')->with($mobile)->result();
        if (empty($full)) {
            return null;
        }
        $profile = new self($full->profile);
        $profile->user = $full->user;
        return $profile;
    }

    public static function setDefaultAddress($userId, $addressId)
    {
        return self::factory()->call('set_default_address')->with($userId, $addressId)->run();
    }

    public static function initFavorFoodsCount($collection)
    {
        foreach ($collection as $profile) {
            $profile->setRelation('favor_foods_count', FavorFood::count($profile->user_id));
        }
    }

    public static function initFavorRestaurantsCount($collection)
    {
        foreach ($collection as $profile) {
            $profile->setRelation('favor_restaurants_count', FavorRestaurant::count($profile->user_id));
        }
    }

    public static function initOrdersCount($collection)
    {
        foreach ($collection as $profile) {
            $profile->setRelation('orders_count', Order::count($profile->user_id));
        }
    }

    public static function changePassword($userId, $oldPassword, $newPassword)
    {
        self::factory()->call('update_password')->with($userId, $oldPassword, $newPassword, session_id())->run();
    }

    public static function unbindMobile($userId)
    {
        self::factory()->call('walle_unbind_mobile')->with($userId)->run();
    }

    public static function bindMobile($userId, $mobile)
    {
        self::factory()->call('bind_mobile')->with($userId, $mobile)->run();
    }

    public static function modifyPaymentQuota($userId, $quota, $ip)
    {
        self::factory()->call('modify_payment_quota')->with($userId, $quota, $ip)->run();
    }

    public static function isUserWithdrawOutOfLimit($userId)
    {
        return self::factory()->call('is_user_drawback_out_of_limit')->with($userId)->result(true);
    }

    public static function applyWithdraw($userId, $amount)
    {
        self::factory()->call('withdraw_user_manually_drawback')->with($userId, $amount)->run();
    }

    public static function setAvatar($userId, $imagesHash)
    {
        return self::factory()->call('set_avatar')->with($userId, $imagesHash)->run();
    }

    public function getReferalCodeAttribute($referalCode)
    {
        if (empty($referalCode)) {
            return $this->referal_code = self::getUserReferalCode($this->user_id);
        } else {
            return $referalCode;
        }
    }

    public function getAvatarAttribute($avatar)
    {
        return empty($avatar) ? '' : preg_replace('/^(.)(..)(.{29}(.+))/', '/$1/$2/$3.$4', $avatar);
    }

    public function getUsernameAttribute()
    {
        if (empty($this->user)) {
            $this->user = User::get($this->user_id);
        }
        return $this->user->username;
    }

    public function getIsActiveAttribute()
    {
        if (empty($this->user)) {
            $this->user = User::get($this->user_id);
        }
        return $this->user->is_active;
    }
}
