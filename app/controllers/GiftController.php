<?php

use Eleme\Validation\ValidationException;

class GiftController extends ResourceController
{
    public static $model = 'Gift';

    public function getUserGifts($userId)
    {
        $userGifts = UserGift::queryByUserId($userId);
        $ids = array();
        foreach ($userGifts as $userGift) {
             $id = $userGift->gift_id;
             $ids[] = $id;
        }
        return Gift::getGiftsByIds($ids);
    }

    public function exchange($userId, $giftId)
    {
        $gift = Gift::getGift($giftId);
        if (!$gift) {
            throw new ValidationException(array('gift' => array('该礼品不存在')));
        }
        $rules = array(
            'user_name' => 'required',
            'user_address' => 'required',
            'user_phone' => 'required'
        );
        $validation = Validator::make(Input::all(), $rules);
        if ($validation->fails()) {
            throw new ValidationException($validation->messages());
        }
        $userName = Input::get('user_name');
        $userAddress = Input::get('user_address');
        $userPhone = Input::get('user_phone');
        $userNote = Input::get('user_note', '');
        UserGift::exchange($userId, $giftId, $userName, $userAddress, $userPhone, $userNote);
        return Response::json(null, 204);
    }
}
