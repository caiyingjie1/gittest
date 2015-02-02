<?php

class User extends Model
{
    const SSO_APP_ELEME = 101;

    protected $service = 'eus';

    protected $visible = array('id', 'username', 'is_active');

    public function verify($userId, $ssoId, $userAgent, $ip)
    {
        $userIdGetBySID = self::ssoCheck($ssoId, $userAgent, $ip);
        return (0 !== $userIdGetBySID)  && (string) $userId === (string) $userIdGetBySID;
    }

    public static function get($userId)
    {
        return self::factory()->call('get')->with($userId)->cache(10)->get();
    }

    public static function getByMobile($mobile)
    {
        return self::factory()->call('get_by_mobile')->with($mobile)->get();
    }

    public static function ssoCheck($ssoId, $userAgent, $ip)
    {
        return self::factory()
            ->call('sso_check')
            ->with($ssoId, self::SSO_APP_ELEME, self::formatInfoRaw($userAgent), $ip)
            ->result(0);
    }

    private static function formatInfoRaw($userAgent)
    {
        return json_encode(array('useragent' => $userAgent));
    }
}
