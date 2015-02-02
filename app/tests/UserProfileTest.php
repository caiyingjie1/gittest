<?php

use Symfony\Component\BrowserKit\Cookie;
use EUS\TUser;
use EUS\TUserProfile;
use EUS\TFullUser;
use fuss\FussFile;

class UserProfileTest extends TestCase
{
    protected $attributesType = array(
        'user_id' => 'int', //用户id
        'avatar' => 'string', //头像路径
        'balance' => 'float', //账户余额
        'current_address_id' => 'int', //当前使用的地址id
        'current_invoice_id' => 'int', //当前使用的发票信息id
        'email' => 'string', //邮箱
        'is_email_valid' => 'int', //email是否激活
        'is_mobile_valid' => 'int', //是否绑定手机
        'mobile' => 'string', //手机号
        'payment_quota' => 'int', //在线支付额度
        'point' => 'int', //积分
        'username' => 'string', //名字
        'referal_code' => 'string', //邀请好友邀请码
        'is_active' => 'int', //账号是否有效
    );

    public function setUp()
    {
        parent::setUp();
        $this->client->getCookieJar()->set(new Cookie('SID', 'fake_sid'));
        $this->client->setServerParameter('REMOTE_ADDR', 'fake_ip');
        $this->client->setServerParameter('HTTP_USER_AGENT', 'fake_ua');
    }

    public function testData()
    {
        $user = require_once 'seeds/user_485007.php';
        $profile = require_once 'seeds/user_profile_485007.php';
        return array($user, $profile);
    }

    /**
     * @depends testData
     */
    public function testGet($data)
    {
        // 未通过验证
        $this->client('eus')->shouldReceive('request')->with('sso_check', ['fake_sid', 101, json_encode(array('useragent' => 'fake_ua')), 'fake_ip'])->once()->andReturn(0);
        $response = $this->call('GET', '/v1/user');
        $this->assertEquals(401, $response->getStatusCode());

        // 通过验证
        list($user, $profile) = $data;
        $this->client('eus')->shouldReceive('request')->with('sso_check', ['fake_sid', 101, json_encode(array('useragent' => 'fake_ua')), 'fake_ip'])->once()->andReturn(485007);
        $this->client('eus')->shouldReceive('request')->with('get_profile', [485007])->once()->andReturn(new TUserProfile($profile));
        $this->client('eus')->shouldReceive('request')->with('get', [485007])->once()->andReturn(new TUser($user));
        $response = $this->call('GET', '/v1/user');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $profile = json_decode($response->getContent(), true);
        $this->assertEmpty(array_diff(array_keys($this->attributesType), array_keys($profile)));
        $this->assertAttributesType($profile);
    }

    public function testChangePassword()
    {
        $this->client('eus')->shouldReceive('request')->with('sso_check', ['fake_sid', 101, json_encode(array('useragent' => 'fake_ua')), 'fake_ip'])->once()->andReturn(485450);
        $this->client('eus')->shouldReceive('request')->with('update_password', [485450, 123123, 456456, ''])->once()->andReturn();
        $response = $this->call('PUT', '/v1/user/password', json_encode(array('old_password' => 123123, 'new_password' => 456456)));
        $this->assertEquals(204, $response->getStatusCode());
    }

    public function testSetDefaultAddress()
    {
        $this->client('eus')->shouldReceive('request')->with('sso_check', ['fake_sid', 101, json_encode(array('useragent' => 'fake_ua')), 'fake_ip'])->once()->andReturn(485450);
        $this->client('eus')->shouldReceive('request')->with('set_default_address', [485450, 123456])->once()->andReturn();
        $response = $this->call('PUT', '/v1/user/address', json_encode(array('address_id' => 123456)));
        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @depends testData
     */
    public function testUpdateMobile($data)
    {
        // 用户未绑定手机，传入的手机令牌无效
        list($user, $profile) = $data;
        $this->client('eus')->shouldReceive('request')->with('sso_check', ['fake_sid', 101, json_encode(array('useragent' => 'fake_ua')), 'fake_ip'])->once()->andReturn(485007);
        $profile['is_mobile_valid'] = 0;
        $this->client('eus')->shouldReceive('request')->with('get_profile', [485007])->once()->andReturn(new TUserProfile($profile));
        Predis::shouldReceive('get')->with('mobile_token:fake_mobile_token')->once()->andReturn(null);
        $response = $this->call('PUT', '/v1/user/mobile', json_encode(array('mobile_token' => 'fake_mobile_token')));
        $this->assertEquals(403, $response->getStatusCode());
        $error = json_decode($response->getContent(), true);
        $this->assertEquals('INVALID_MOBILE_TOKEN', $error['name']);

        // 用户未绑定手机，传入的手机令牌有效，手机未被任何人绑定
        list($user, $profile) = $data;
        $this->client('eus')->shouldReceive('request')->with('sso_check', ['fake_sid', 101, json_encode(array('useragent' => 'fake_ua')), 'fake_ip'])->once()->andReturn(485007);
        $profile['is_mobile_valid'] = 0;
        $this->client('eus')->shouldReceive('request')->with('get_profile', [485007])->once()->andReturn(new TUserProfile($profile));
        Predis::shouldReceive('get')->with('mobile_token:fake_mobile_token')->once()->andReturn(json_encode(array('type' => 'sms', 'mobile' => 'fake_mobile')));
        $this->client('eus')->shouldReceive('request')->with('get_full_by_valid_mobile', ['fake_mobile'])->once()->andReturn(null);
        $this->client('eus')->shouldReceive('request')->with('bind_mobile', [485007, 'fake_mobile'])->once()->andReturn(null);
        $response = $this->call('PUT', '/v1/user/mobile', json_encode(array('mobile_token' => 'fake_mobile_token')));
        $this->assertEquals(204, $response->getStatusCode());

        // 用户未绑定手机，传入的手机令牌有效，手机被无余额的账户绑定
        list($user, $profile) = $data;
        $this->client('eus')->shouldReceive('request')->with('sso_check', ['fake_sid', 101, json_encode(array('useragent' => 'fake_ua')), 'fake_ip'])->once()->andReturn(485007);
        $profile['is_mobile_valid'] = 0;
        $this->client('eus')->shouldReceive('request')->with('get_profile', [485007])->once()->andReturn(new TUserProfile($profile));
        Predis::shouldReceive('get')->with('mobile_token:fake_mobile_token')->once()->andReturn(json_encode(array('type' => 'sms', 'mobile' => 'fake_mobile')));
        $profile['balance'] = 0;
        $TFullUser = new TFullUser(array('user' => new TUser($user), 'profile' => new TUserProfile($profile)));
        $this->client('eus')->shouldReceive('request')->with('get_full_by_valid_mobile', ['fake_mobile'])->once()->andReturn($TFullUser);
        $response = $this->call('PUT', '/v1/user/mobile', json_encode(array('mobile_token' => 'fake_mobile_token')));
        $this->assertEquals(403, $response->getStatusCode());
        $error = json_decode($response->getContent(), true);
        $this->assertEquals('MOBILE_OCCUPIED_WITHOUT_BALANCE', $error['name']);

        // 用户未绑定手机，传入的手机令牌有效，手机被无余额的账户绑定，用户传入强制绑定参数
        list($user, $profile) = $data;
        $this->client('eus')->shouldReceive('request')->with('sso_check', ['fake_sid', 101, json_encode(array('useragent' => 'fake_ua')), 'fake_ip'])->once()->andReturn(485007);
        $profile['is_mobile_valid'] = 0;
        $this->client('eus')->shouldReceive('request')->with('get_profile', [485007])->once()->andReturn(new TUserProfile($profile));
        Predis::shouldReceive('get')->with('mobile_token:fake_mobile_token')->once()->andReturn(json_encode(array('type' => 'sms', 'mobile' => 'fake_mobile')));
        $profile['balance'] = 0;
        $profile['user_id'] = 485008;
        $TFullUser = new TFullUser(array('user' => new TUser($user), 'profile' => new TUserProfile($profile)));
        $this->client('eus')->shouldReceive('request')->with('get_full_by_valid_mobile', ['fake_mobile'])->once()->andReturn($TFullUser);
        $this->client('eus')->shouldReceive('request')->with('walle_unbind_mobile', [485008])->once()->andReturn(null);
        $this->client('eus')->shouldReceive('request')->with('bind_mobile', [485007, 'fake_mobile'])->once()->andReturn(null);
        $response = $this->call('PUT', '/v1/user/mobile', json_encode(array('mobile_token' => 'fake_mobile_token', 'force' => true)));
        $this->assertEquals(204, $response->getStatusCode());

        // 用户未绑定手机，传入的手机令牌有效，手机被有余额的账户绑定
        list($user, $profile) = $data;
        $this->client('eus')->shouldReceive('request')->with('sso_check', ['fake_sid', 101, json_encode(array('useragent' => 'fake_ua')), 'fake_ip'])->once()->andReturn(485007);
        $profile['is_mobile_valid'] = 0;
        $this->client('eus')->shouldReceive('request')->with('get_profile', [485007])->once()->andReturn(new TUserProfile($profile));
        Predis::shouldReceive('get')->with('mobile_token:fake_mobile_token')->once()->andReturn(json_encode(array('type' => 'sms', 'mobile' => 'fake_mobile')));
        $profile['balance'] = 100.0;
        $TFullUser = new TFullUser(array('user' => new TUser($user), 'profile' => new TUserProfile($profile)));
        $this->client('eus')->shouldReceive('request')->with('get_full_by_valid_mobile', ['fake_mobile'])->once()->andReturn($TFullUser);
        $response = $this->call('PUT', '/v1/user/mobile', json_encode(array('mobile_token' => 'fake_mobile_token')));
        $this->assertEquals(403, $response->getStatusCode());
        $error = json_decode($response->getContent(), true);
        $this->assertEquals('MOBILE_OCCUPIED_WITH_BALANCE', $error['name']);

        // 用户未绑定手机，传入的手机令牌有效，手机被有余额的账户绑定，用户传入强制绑定参数
        list($user, $profile) = $data;
        $this->client('eus')->shouldReceive('request')->with('sso_check', ['fake_sid', 101, json_encode(array('useragent' => 'fake_ua')), 'fake_ip'])->once()->andReturn(485007);
        $profile['is_mobile_valid'] = 0;
        $this->client('eus')->shouldReceive('request')->with('get_profile', [485007])->once()->andReturn(new TUserProfile($profile));
        Predis::shouldReceive('get')->with('mobile_token:fake_mobile_token')->once()->andReturn(json_encode(array('type' => 'sms', 'mobile' => 'fake_mobile')));
        $profile['balance'] = 100.0;
        $TFullUser = new TFullUser(array('user' => new TUser($user), 'profile' => new TUserProfile($profile)));
        $this->client('eus')->shouldReceive('request')->with('get_full_by_valid_mobile', ['fake_mobile'])->once()->andReturn($TFullUser);
        $response = $this->call('PUT', '/v1/user/mobile', json_encode(array('mobile_token' => 'fake_mobile_token')));
        $this->assertEquals(403, $response->getStatusCode());
        $error = json_decode($response->getContent(), true);
        $this->assertEquals('MOBILE_OCCUPIED_WITH_BALANCE', $error['name']);

        // 用户绑定了手机，未传入用户手机令牌
        list($user, $profile) = $data;
        $this->client('eus')->shouldReceive('request')->with('sso_check', ['fake_sid', 101, json_encode(array('useragent' => 'fake_ua')), 'fake_ip'])->once()->andReturn(485007);
        $this->client('eus')->shouldReceive('request')->with('get_profile', [485007])->once()->andReturn(new TUserProfile($profile));
        $response = $this->call('PUT', '/v1/user/mobile', json_encode(array('mobile_token' => 'fake_mobile_token')));
        $this->assertEquals(403, $response->getStatusCode());
        $error = json_decode($response->getContent(), true);
        $this->assertEquals('VALIDATION_FAILED', $error['name']);

        // 用户绑定了手机，传入了无效的用户手机令牌
        list($user, $profile) = $data;
        $this->client('eus')->shouldReceive('request')->with('sso_check', ['fake_sid', 101, json_encode(array('useragent' => 'fake_ua')), 'fake_ip'])->once()->andReturn(485007);
        $this->client('eus')->shouldReceive('request')->with('get_profile', [485007])->once()->andReturn(new TUserProfile($profile));
        Predis::shouldReceive('get')->with('mobile_token:fake_user_mobile_token')->once()->andReturn(json_encode(array('type' => 'sms', 'mobile' => 'different_with_user_mobile')));
        $response = $this->call('PUT', '/v1/user/mobile', json_encode(array('mobile_token' => 'fake_mobile_token', 'user_mobile_token' => 'fake_user_mobile_token')));
        $this->assertEquals(403, $response->getStatusCode());
        $error = json_decode($response->getContent(), true);
        $this->assertEquals('INVALID_USER_MOBILE_TOKEN', $error['name']);

        // 用户绑定了手机，传入了有效的用户手机令牌，传入有效的手机令牌，手机未被任何人绑定
        list($user, $profile) = $data;
        $this->client('eus')->shouldReceive('request')->with('sso_check', ['fake_sid', 101, json_encode(array('useragent' => 'fake_ua')), 'fake_ip'])->once()->andReturn(485007);
        $this->client('eus')->shouldReceive('request')->with('get_profile', [485007])->once()->andReturn(new TUserProfile($profile));
        Predis::shouldReceive('get')->with('mobile_token:fake_user_mobile_token')->once()->andReturn(json_encode(array('type' => 'sms', 'mobile' => $profile['mobile'])));
        Predis::shouldReceive('get')->with('mobile_token:fake_mobile_token')->once()->andReturn(json_encode(array('type' => 'sms', 'mobile' => 'fake_mobile')));
        $this->client('eus')->shouldReceive('request')->with('get_full_by_valid_mobile', ['fake_mobile'])->once()->andReturn(null);
        $this->client('eus')->shouldReceive('request')->with('bind_mobile', [485007, 'fake_mobile'])->once()->andReturn(null);
        $response = $this->call('PUT', '/v1/user/mobile', json_encode(array('mobile_token' => 'fake_mobile_token', 'user_mobile_token' => 'fake_user_mobile_token')));
        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @depends testData
     */
    public function testUpdateQuota($data)
    {
        //用户未绑定手机
        list($user, $profile) = $data;
        $profile['is_mobile_valid'] = 0;
        $this->client('eus')->shouldReceive('request')->with('sso_check', ['fake_sid', 101, json_encode(array('useragent' => 'fake_ua')), 'fake_ip'])->once()->andReturn(485007);
        $this->client('eus')->shouldReceive('request')->with('get_profile', [485007])->once()->andReturn(new TUserProfile($profile));
        $response = $this->call('PUT', '/v1/user/quota', json_encode(array('user_mobile_token' => 'fake_mobile_token', 'quota' => 100)));
        $this->assertEquals(403, $response->getStatusCode());
        $error = json_decode($response->getContent(), true);
        $this->assertEquals('NO_VALID_MOBILE', $error['name']);

        //输入的手机令牌无效
        list($user, $profile) = $data;
        $this->client('eus')->shouldReceive('request')->with('sso_check', ['fake_sid', 101, json_encode(array('useragent' => 'fake_ua')), 'fake_ip'])->once()->andReturn(485007);
        $this->client('eus')->shouldReceive('request')->with('get_profile', [485007])->once()->andReturn(new TUserProfile($profile));
        Predis::shouldReceive('get')->with('mobile_token:fake_mobile_token')->once()->andReturn(null);
        $response = $this->call('PUT', '/v1/user/quota', json_encode(array('user_mobile_token' => 'fake_mobile_token', 'quota' => 100)));
        $this->assertEquals(403, $response->getStatusCode());
        $error = json_decode($response->getContent(), true);
        $this->assertEquals('INVALID_USER_MOBILE_TOKEN', $error['name']);

        //输入的手机令牌有效, token信息中mobile与用户绑定的手机号不对应
        list($user, $profile) = $data;
        $this->client('eus')->shouldReceive('request')->with('sso_check', ['fake_sid', 101, json_encode(array('useragent' => 'fake_ua')), 'fake_ip'])->once()->andReturn(485007);
        $this->client('eus')->shouldReceive('request')->with('get_profile', [485007])->once()->andReturn(new TUserProfile($profile));
        Predis::shouldReceive('get')->with('mobile_token:fake_mobile_token')->once()->andReturn(json_encode(array('type' => 'sms', 'mobile' => '13812345678')));
        $response = $this->call('PUT', '/v1/user/quota', json_encode(array('user_mobile_token' => 'fake_mobile_token', 'quota' => 100)));
        $this->assertEquals(403, $response->getStatusCode());
        $error = json_decode($response->getContent(), true);
        $this->assertEquals('INVALID_USER_MOBILE_TOKEN', $error['name']);

        //输入的手机令牌有效, token信息中mobile与用户绑定的手机号对应
        list($user, $profile) = $data;
        $this->client('eus')->shouldReceive('request')->with('sso_check', ['fake_sid', 101, json_encode(array('useragent' => 'fake_ua')), 'fake_ip'])->once()->andReturn(485007);
        $this->client('eus')->shouldReceive('request')->with('get_profile', [485007])->once()->andReturn(new TUserProfile($profile));
        $this->client('eus')->shouldReceive('request')->with('modify_payment_quota', [485007, 100, 'fake_ip'])->once()->andReturn();
        Predis::shouldReceive('get')->with('mobile_token:fake_mobile_token')->once()->andReturn(json_encode(array('type' => 'sms', 'mobile' => '18621795462')));
        $response = $this->call('PUT', '/v1/user/quota', json_encode(array('user_mobile_token' => 'fake_mobile_token', 'quota' => 100)));
        $this->assertEquals(204, $response->getStatusCode());
    }

    public function testCharge()
    {
        $this->client('eus')->shouldReceive('request')->with('sso_check', ['fake_sid', 101, json_encode(array('useragent' => 'fake_ua')), 'fake_ip'])->once()->andReturn(485450);

        //主站充值请求
        $this->client('eus')->shouldReceive('request')->with('pay_record_make_new', [485450, 1, 1, 'directPay', 50.0])->once()->andReturn('123456');
        $response = $this->call('POST', '/v1/user/balance/charge', json_encode(array('come_from' => 'web', 'company_id' => 1, 'pay_bank' => 'directPay', 'total_fee' => 50)));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($response->getContent(), json_encode(array('charge_url' => '/pay/123456')));

        //M站充值请求
        $this->client('eus')->shouldReceive('request')->with('pay_record_make_new', [485450, 1, 2, 'directPay', 100])->once()->andReturn('123456');
        $response = $this->call('POST', '/v1/user/balance/charge', json_encode(array('come_from' => 'web_mobile', 'company_id' => 1, 'pay_bank' => 'directPay', 'total_fee' => 100)));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($response->getContent(), json_encode(array('charge_url' => '/alipay/wap/123456')));
    }

    public function testIsWithdrawCheck()
    {
        $this->client('eus')->shouldReceive('request')->with('sso_check', ['fake_sid', 101, json_encode(array('useragent' => 'fake_ua')), 'fake_ip'])->once()->andReturn(485450);
        $this->client('eus')->shouldReceive('request')->with('is_user_drawback_out_of_limit', [485450])->once()->andReturn(false);
        $response = $this->call('GET', '/v1/user/balance/withdraw/check');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($response->getContent(), json_encode(array('is_withdraw_valid' => true)));
    }

    public function testWithDrawReply()
    {
        $this->client('eus')->shouldReceive('request')->with('sso_check', ['fake_sid', 101, json_encode(array('useragent' => 'fake_ua')), 'fake_ip'])->once()->andReturn(485450);
        $this->client('eus')->shouldReceive('request')->with('withdraw_user_manually_drawback', [485450, 100])->once()->andReturn();
        $response = $this->call('POST', '/v1/user/balance/withdraw', json_encode(array('total_fee' => 100)));
        $this->assertEquals(204, $response->getStatusCode());
    }

    public function testSetAvatar()
    {
        $parameters = array('x' => 0, 'y' => 0, 'w' => 50, 'h' => '50');
        $imageSource = imagecreatetruecolor(900, 900);
        imagepng($imageSource, '/tmp/fake_image.png', 1);
        imagepng($imageSource, '/tmp/fake_large_image.png', 0);
        file_put_contents('/tmp/fake_text.txt', 'fake_content');

        //无效的图片
        $this->client('eus')->shouldReceive('request')->with('sso_check', ['fake_sid', 101, json_encode(array('useragent' => 'fake_ua')), 'fake_ip'])->once()->andReturn(485450);
        $fakeInvalidAvatar = array('avatar' => array( 'name' => 'fake_image.png', 'type' => 'image/png', 'size' => 1000, 'tmp_name' => '/tmp/fake_image.png', 'error' => 1));
        $response = $this->call('POST', '/v1/user/avatar', null, $parameters, $fakeInvalidAvatar);
        $this->assertEquals(400, $response->getStatusCode());
        $error = json_decode($response->getContent(), true);
        $this->assertEquals('INVALID_IMAGE', $error['name']);

        //无效的文件类型
        $this->client('eus')->shouldReceive('request')->with('sso_check', ['fake_sid', 101, json_encode(array('useragent' => 'fake_ua')), 'fake_ip'])->once()->andReturn(485450);
        $fakeInvalidAvatar = array('avatar' => array( 'name' => 'fake_text.txt', 'type' => 'text/plain', 'size' => 1000, 'tmp_name' => '/tmp/fake_text.txt', 'error' => 0));
        $response = $this->call('POST', '/v1/user/avatar', null, $parameters, $fakeInvalidAvatar);
        $this->assertEquals(415, $response->getStatusCode());
        $error = json_decode($response->getContent(), true);
        $this->assertEquals('INVALID_IMAGE_FILE_TYPE', $error['name']);

        //图片大小大于2M
        $this->client('eus')->shouldReceive('request')->with('sso_check', ['fake_sid', 101, json_encode(array('useragent' => 'fake_ua')), 'fake_ip'])->once()->andReturn(485450);
        $fakeInvalidAvatar = array('avatar' => array( 'name' => 'fake_large_image.png', 'type' => 'image/png', 'size' => 1000, 'tmp_name' => '/tmp/fake_large_image.png', 'error' => 0));
        $response = $this->call('POST', '/v1/user/avatar', null, $parameters, $fakeInvalidAvatar);
        $this->assertEquals(413, $response->getStatusCode());
        $error = json_decode($response->getContent(), true);
        $this->assertEquals('IMAGE_LARGER_THAN_2M', $error['name']);

        //有效的的图片、图片类型、图片大小
        $this->client('eus')->shouldReceive('request')->with('sso_check', ['fake_sid', 101, json_encode(array('useragent' => 'fake_ua')), 'fake_ip'])->once()->andReturn(485450);
        $fakeInvalidAvatar = array('avatar' => array( 'name' => 'fake_image.png', 'type' => 'image/png', 'size' => 1000, 'tmp_name' => '/tmp/fake_image.png', 'error' => 0));
        $fussFile = array('content' => Avatar::createImage('/tmp/fake_image.png', 'png', $parameters['x'], $parameters['y'], $parameters['w'], $parameters['h']), 'extension' => Avatar::AVATAR_IMAGE_EXTENSION, 'category' => '');
        $this->client('fuss')->shouldReceive('request')->with('avatar_upload', [new FussFile($fussFile)])->once()->andReturn('de9fda35a9cd26829b1539bdc98269b3jpg');
        $this->client('eus')->shouldReceive('request')->with('set_avatar', [485450, 'de9fda35a9cd26829b1539bdc98269b3jpg']);
        $response = $this->call('POST', '/v1/user/avatar', null, $parameters, $fakeInvalidAvatar);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($response->getContent(), json_encode(array('avatar_path' => "/d/e9/fda35a9cd26829b1539bdc98269b3jpg.jpg")));

        unlink('/tmp/fake_image.png');
        unlink('/tmp/fake_large_image.png');
        unlink('/tmp/fake_text.txt');
    }
}
