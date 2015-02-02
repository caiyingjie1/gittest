<?php

use Eleme\Validation\ValidationException;
use Eleme\Error\AppException;

class MobileController extends Controller
{
    public function sendVerifyCode()
    {
        $rules = array(
            'type' => 'in:sms,audio|required',
            'mobile' => 'required',
        );
        $validation = Validator::make(Input::json()->all(), $rules);
        if ($validation->fails()) {
            throw new ValidationException($validation->messages());
        }

        $mobile = Input::json('mobile');
        $type = Input::json('type');
        $senderKey = Config::get('hermes')[$type];
        $audio = $type === 'audio';

        $token = Sms::send($senderKey, $mobile, $audio);

        $storageKey = "hermes_token:".$token;
        Predis::set($storageKey, json_encode(array('mobile' => $mobile, 'type' => $type)), 'NX', 'EX', 300);

        return Response::json(array('validate_token' => $token));
    }

    public function validateVerifyCode()
    {
        $rules = array(
            'validate_token' => 'required',
            'validate_code' => 'required',
        );
        $validation = Validator::make(Input::json()->all(), $rules);
        if ($validation->fails()) {
            throw new ValidationException($validation->messages(), 403);
        }

        $token = Input::json('validate_token');
        $code = Input::json('validate_code');

        $storageKey = "hermes_token:".$token;
        $data = Predis::get($storageKey);
        if ($data === null) {
            throw new AppException(403, 'INVALID_VALIDATE_TOKEN');
        }
        $data = json_decode($data, true);
        if ($data === false) {
            throw new AppException(403, 'INVALID_VALIDATE_TOKEN');
        }

        $type = $data['type'];
        $senderKey = Config::get('hermes')[$type];
        $success = Sms::validateWithHash($senderKey, $token, $code);

        $mobileToken = null;
        if ($success) {
            $mobileToken = sha1(Config::get('secret.mobile_token').$token);
            $storageKey = "mobile_token:".$mobileToken;
            Predis::set($storageKey, json_encode($data), 'NX', 'EX', 600);
        }

        return Response::json(array('validate' => $success, 'mobile_token' => $mobileToken));
    }
}
