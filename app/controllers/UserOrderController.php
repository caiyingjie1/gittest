<?php

use Eleme\Validation\ValidationException;
use Eleme\Error\AppException;

class UserOrderController extends UserResourceController
{
    protected $defaultQueryMethod = 'queryByLastMouth';

    protected static $model = 'Order';

    public function count($userId)
    {
        $rules = array(
            'type' => 'required|in:last_mouth,before_mouth'
        );
        $validation = Validator::make(Input::all(), $rules);
        if ($validation->fails()) {
            throw new ValidationException($validation->messages());
        }
        return Response::json(
            array('count' => Order::count($userId, Input::query('type')))
        );
    }

    public function rate($userId, $orderId)
    {
        $rules = array(
            'rating_type' => 'required|in:time,service',
            'spent_time' => 'required_if:rating_type,time|numeric|between:5,55',
            'rating_value' => 'required_if:rating_type,service|numeric|between:1,3',
        );
        $validation = Validator::make(Input::json()->all(), $rules);
        if ($validation->fails()) {
            throw new ValidationException($validation->messages());
        }
        switch (Input::json('rating_type')) {
            case 'time':
                OrderRate::rateDeliverTimeSpent($orderId, $userId, Input::json('spent_time'));
                break;
            case 'service':
                OrderRate::rateService($orderId, $userId, Input::json('rating_value'), Input::json('rating_text', ''));
                break;
        }
        return Response::json('', 204);
    }

    public function rateItem($userId, $orderId, $itemId)
    {
        $rules = array(
            'rating_value' => 'required|numeric|between:1,5',
        );
        $validation = Validator::make(Input::json()->all(), $rules);
        if ($validation->fails()) {
            throw new ValidationException($validation->messages());
        }
        OrderItemRating::rateOrderItem($itemId, $userId, Input::json('rating_value'), Input::json('rating_text', ''));
        return Response::json('', 204);
    }

    public function addComplaint($userId, $orderId)
    {
        $rules = array(
            'type' => 'required|between:0,2',
            'content' => 'required_if:type,2'
        );
        $validation = Validator::make(Input::json()->all(), $rules);
        if ($validation->fails()) {
            throw new ValidationException($validation->messages());
        }
        $order = Order::get($orderId);
        if ($order->complaint_status === Order::ORDER_COMPLAINT_SUPPORT) {
            Order::addOrderComplaint($userId, $orderId, Input::json('type'), Input::json('content', ''));
        } else {
            throw new AppException(403, "INVALID_COMPLAINT_ORDER");
        }
        return Response::json('',204);
    }
}
