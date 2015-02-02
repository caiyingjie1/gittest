<?php

class MobileVerifyCodeTest extends TestCase
{
    public function testSend()
    {
        $this->client('sms')->shouldReceive('request')->with('hermes_send_verify_code', ['sms_sender', 'fake_mobile', false])->once()->andReturn('fake_token');
        Predis::shouldReceive('set')->once()->andReturn(true);
        $response = $this->call('POST', '/v1/mobile/verify_code/send', json_encode(array('type' => 'sms', 'mobile' => 'fake_mobile')));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $result = json_decode($response->getContent(), true);
        $this->assertEquals('fake_token', $result['validate_token']);
    }

    public function testValidate()
    {
        // 输入无效的验证令牌
        Predis::shouldReceive('get')->with('hermes_token:fake_token')->once()->andReturn(null);
        $response = $this->call('POST', '/v1/mobile/verify_code/validate', json_encode(array('validate_token' => 'fake_token', 'validate_code' => 'fake_code')));
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $error = json_decode($response->getContent(), true);
        $this->assertEquals('INVALID_VALIDATE_TOKEN', $error['name']);


        // 输入有效的验证令牌，但是验证码错误
        Predis::shouldReceive('get')->with('hermes_token:fake_token')->once()->andReturn(json_encode(array('type' => 'sms', 'mobile' => 'fake_mobile')));
        $this->client('sms')->shouldReceive('request')->with('hermes_validate_verify_code_with_hash', ['sms_sender', 'fake_token', 'fake_code'])->once()->andReturn(false);
        $response = $this->call('POST', '/v1/mobile/verify_code/validate', json_encode(array('validate_token' => 'fake_token', 'validate_code' => 'fake_code')));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $result = json_decode($response->getContent(), true);
        $this->assertFalse($result['validate']);
        $this->assertNull($result['mobile_token']);

        // 输入有效的验证令牌，且验证码正确
        Predis::shouldReceive('get')->with('hermes_token:fake_token')->once()->andReturn(json_encode(array('type' => 'sms', 'mobile' => 'fake_mobile')));
        $this->client('sms')->shouldReceive('request')->with('hermes_validate_verify_code_with_hash', ['sms_sender', 'fake_token', 'fake_code'])->once()->andReturn(true);
        Predis::shouldReceive('set')->once()->andReturn(true);
        $response = $this->call('POST', '/v1/mobile/verify_code/validate', json_encode(array('validate_token' => 'fake_token', 'validate_code' => 'fake_code')));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $result = json_decode($response->getContent(), true);
        $this->assertTrue($result['validate']);
        $this->assertInternalType('string', $result['mobile_token']);
    }
}
