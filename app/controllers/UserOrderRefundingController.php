<?php

use Symfony\Component\HttpKernel\Exception\HttpException;
use Eleme\Validation\ValidationException;
use Eleme\Error\AppException;

class UserOrderRefundingController extends UserResourceController
{
    public static $model = 'OrderRefunding';

    public function store($userId, $orderId)
    {
        $user = UserProfile::get($userId);
        if (!$user->is_mobile_valid) {
            throw new AppException('403', 'NO_VALID_MOBILE');
        }

        $order= Order::get($orderId);
        if ($order && $order->user_id === $user->user_id) {
            if (!$order->getIsRefundValidAttribute()) {
                throw new AppException('403', 'NON_REFUNDABLE_ORDER');
            }
        }

        $rules = array(
            'refunding_action' => 'required|in:apply,cancel,arbirate',
            'type' => 'required_if:refunding_action,apply|in:slow,timeout,quality_problem,other',
            'reason' => 'required_if:refunding_action,apply|required_if:refunding_action,arbirate',
            'password' => 'required_if:refunding_action,cancel',
        );
        $validation = Validator::make(Input::json()->all(), $rules);
        if ($validation->fails()) {
            throw new ValidationException($validation->messages());
        }

        switch (Input::json('refunding_action')) {
            case 'apply':
                OrderRefunding::apply($userId, $orderId, Input::json('type'), Input::json('reason'));
                break;
            case 'arbirate':
                OrderRefunding::arbirtate($userId, $orderId, Input::json('reason'));
                break;
            case 'cancel':
                OrderRefunding::cancel($userId, $orderId, Input::json('password'));
                break;
        }
        return Response::json('', 204);
    }
}
