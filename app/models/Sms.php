<?php

class Sms extends Model
{
    protected $service = 'sms';

    public static function send($senderKey, $mobile, $viaAudio)
    {
        return self::factory()->call('hermes_send_verify_code')->with($senderKey, $mobile, $viaAudio)->run();
    }

    public static function validateWithReceiver($senderKey, $mobile, $code, $default = false)
    {
        return self::factory()->call('hermes_validate_verify_code_with_receiver')->with($senderKey, $mobile, $code)->result($default);
    }

    public static function validateWithHash($senderKey, $hash, $code, $default = false)
    {
        return self::factory()->call('hermes_validate_verify_code_with_hash')->with($senderKey, $hash, $code)->result($default);
    }
}
