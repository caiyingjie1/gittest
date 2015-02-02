<?php

use Eleme\Validation\ValidationException;

class UserMessageController extends UserResourceController
{
    public static $model = 'Message';

    protected $defaultQueryMethod = 'queryUnReadByUserId';

    public function markAsRead($userId, $messageId)
    {
        $rules = array(
            'is_read' => 'required|numeric|size:1'
        );
        $validation = Validator::make(Input::json()->all(), $rules);
        if ($validation->fails()) {
            throw new ValidationException($validation->messages());
        }
        Message::markAsRead($userId, $messageId);
        return Response::json(null, 204);
    }

    public function markAllAsRead($userId)
    {
        Message::markAllAsRead($userId);
        return Response::json(null, 204);
    }

    public function countByUserId($userId)
    {
        return array('count' => Message::countByUserId($userId));
    }
}
