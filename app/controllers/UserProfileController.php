<?php

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Eleme\Validation\ValidationException;
use Eleme\Error\AppException;

class UserProfileController extends UserResourceController
{
    protected static $model = 'UserProfile';

    private static $avatarAllowType = array(
        'image/png' => 'png',
        'image/x-png' => 'png',
        'image/jpg' => 'jpeg',
        'image/jpeg' => 'jpeg',
        'image/gif' => 'gif'
    );

    private static $payComeFromNameToId = array(
        'web' => PayRecord::COME_FROM_WEB,
        'web_mobile' => PayRecord::COME_FROM_WEB_MOBILE
    );

    private $userId;

    public function __construct()
    {
        if (Cookie::has('SID')) {
            $userIdGetBySID = User::ssoCheck(Cookie::get('SID'), Request::header('user-agent'), Request::ip());
            if ($userIdGetBySID !== 0) {
                $this->userId = $userIdGetBySID;
            } else {
                throw new UnauthorizedHttpException(null, "用户验证失败");
            }
        } else {
            throw new UnauthorizedHttpException(null, "用户验证失败");
        }
    }

    public function getPresent()
    {
        return UserProfile::get($this->userId);
    }

    public function changePassword()
    {
        $rules = array(
            'old_password' => 'required',
            'new_password' => 'required|min:6',
        );
        $validation = Validator::make(Input::json()->all(), $rules);
        if ($validation->fails()) {
            throw new ValidationException($validation->messages());
        }
        $oldPassword = Input::json('old_password');
        $newPassword = Input::json('new_password');
        UserProfile::changePassword($this->userId, $oldPassword, $newPassword);
        return Response::json(null, 204);
    }

    public function setDefaultAddress()
    {
        $rules = array(
            'address_id' => 'numeric|min:1'
        );
        $validation = Validator::make(Input::json()->all(), $rules);
        if ($validation->fails()) {
            throw new ValidationException($validation->messages());
        }
        UserProfile::setDefaultAddress($this->userId, Input::json('address_id'));
        return Response::json('', 204);
    }

    public function updateMobile()
    {
        $rules = array(
            'mobile_token' => 'required',
        );

        $userProfile = UserProfile::get($this->userId);
        if ($userProfile->is_mobile_valid) {
            $rules['user_mobile_token'] = 'required';
        }

        $validation = Validator::make(Input::json()->all(), $rules);
        if ($validation->fails()) {
            throw new ValidationException($validation->messages(), 403);
        }

        if ($userProfile->is_mobile_valid) {
            $userMobileToken = Input::json('user_mobile_token');
            $storageKey = 'mobile_token:'.$userMobileToken;
            $data = Predis::get($storageKey);
            if ($data === null) {
                throw new AppException(403, 'INVALID_USER_MOBILE_TOKEN');
            }
            $data = json_decode($data, true);
            if ($data === false) {
                throw new AppException(403, 'INVALID_USER_MOBILE_TOKEN');
            }
            if ($userProfile->mobile !== $data['mobile']) {
                throw new AppException(403, 'INVALID_USER_MOBILE_TOKEN');
            }
        }

        $mobileToken = Input::json('mobile_token');
        $storageKey = 'mobile_token:'.$mobileToken;
        $data = Predis::get($storageKey);
        if ($data === null) {
            throw new AppException(403, 'INVALID_MOBILE_TOKEN');
        }
        $data = json_decode($data, true);
        if ($data === false) {
            throw new AppException(403, 'INVALID_MOBILE_TOKEN');
        }
        $mobile = $data['mobile'];

        $profile = UserProfile::getByMobile($mobile);
        if ($profile) {
            if ($profile->balance > 0) {
                throw new AppException(403, 'MOBILE_OCCUPIED_WITH_BALANCE');
            }
            if (!Input::json('force', false)) {
                throw new AppException(403, "MOBILE_OCCUPIED_WITHOUT_BALANCE");
            }
            UserProfile::unbindMobile($profile->user_id);
        }
        UserProfile::bindMobile($this->userId, $mobile);
        return Response::json(null, 204);
    }

    public function updateQuota()
    {
        $rules = array(
            'user_mobile_token' => 'required',
            'quota' => 'required|integer|between:0,10000',
        );

        $validation = Validator::make(Input::json()->all(), $rules);
        if ($validation->fails()) {
            throw new ValidationException($validation->messages());
        }

        $userProfile = UserProfile::get($this->userId);
        if (!$userProfile->is_mobile_valid) {
            throw new AppException(403, 'NO_VALID_MOBILE');
        }

        $userMobileToken = Input::json('user_mobile_token');
        $storageKey = 'mobile_token:'.$userMobileToken;
        $data = Predis::get($storageKey);
        if ($data === null) {
            throw new AppException(403, 'INVALID_USER_MOBILE_TOKEN');
        }
        $data = json_decode($data, true);
        if ($data === false) {
            throw new AppException(403, 'INVALID_USER_MOBILE_TOKEN');
        }
        if ($userProfile->mobile !== $data['mobile']) {
            throw new AppException(403, 'INVALID_USER_MOBILE_TOKEN');
        }
        UserProfile::modifyPaymentQuota($this->userId, Input::json('quota'), Request::ip());
        return Response::json(null, 204);
    }

    public function charge()
    {
        $rules = array(
            'come_from' => 'required|in:web,web_mobile',
            'company_id' => 'required|in:1,3,5,8,10',
            'pay_bank' => 'required_if:company_id,1|required_if:company_id,5|in:directPay,BOCB2C,ICBCB2C,CCB,ABC,CMB',
            'total_fee' => 'required|numeric|in:50,100,200,300,500'
        );
        $validation = Validator::make(Input::json()->all(), $rules);
        if ($validation->fails()) {
            throw new ValidationException($validation->messages());
        }

        $outTradeNo = PayRecord::payRecordMakeNew(
            $this->userId,
            Input::json('company_id'),
            self::$payComeFromNameToId[Input::json('come_from')],
            Input::json('pay_bank', ''),
            (double) Input::json('total_fee')
        );
        switch (Input::json('come_from')) {
            case 'web':
                $redirectURL = '/pay/' . $outTradeNo;
                break;
            case 'web_mobile':
                $redirectURL = '/alipay/wap/' . $outTradeNo;
                break;
        }
        return Response::json(array('charge_url' => $redirectURL), 200);
    }

    public function isWithdrawValid()
    {
        return Response::json(array('is_withdraw_valid' => ! UserProfile::isUserWithdrawOutOfLimit($this->userId)));
    }

    public function withdrawApply()
    {
        $rules = array(
            'total_fee' => 'required|numeric|min:1'
        );
        $validation = Validator::make(Input::json()->all(), $rules);
        if ($validation->fails()) {
            throw new ValidationException($validation->messages());
        }

        UserProfile::applyWithdraw($this->userId, Input::json('total_fee'));
        return Response::json('', 204);
    }

    public function setAvatar()
    {
        $rules = array(
            'x' => 'required|numeric',
            'y' => 'required|numeric',
            'w' => 'required|numeric',
            'h' => 'required|numeric',
        );
        $validation = Validator::make(Input::all(), $rules);
        if ($validation->fails()) {
            throw new ValidationException($validation->messages());
        }

        if (!Input::file('avatar')->isValid()) {
            throw new AppException(400, "INVALID_IMAGE");
        }
        $avatarImage = Input::file('avatar');
        if (!array_key_exists($avatarImage->getMimeType(), self::$avatarAllowType)) {
            throw new AppException(415, "INVALID_IMAGE_FILE_TYPE");
        }
        if (!Avatar::checkSize($avatarImage->getSize())) {
            throw new AppException(413, "IMAGE_LARGER_THAN_2M");
        }
        $avatarHash = Avatar::save($avatarImage->getRealPath(), self::$avatarAllowType[$avatarImage->getMimeType()], Input::get('x'), Input::get('y'), Input::get('w'), Input::get('h'));
        UserProfile::setAvatar($this->userId, $avatarHash);
        return Response::json(
            array('avatar_path' => preg_replace('/^(.)(..)(.{29}(.+))/', '/$1/$2/$3.$4', $avatarHash)),
            200
        );
    }
}
