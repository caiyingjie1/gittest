<?php

use EUS\THongbaoQuery;

class HongbaoTest extends TestCase
{
    protected $attributesType = array(
        'id' => 'int',              //红包id
        'sn' => 'string',           //红包sn
        'amount' => 'float',        //红包面值
        'used_amount' => 'float',   //已使用金额
        'used_at' => 'string',      //使用时间
        'begin_date' => 'string',   //红包可使用的开始时间
        'end_date' => 'string',     //红包过期时间
        'sum_condition' => 'int',   //订单金额限制
        'status' => 'int',          //红包状态, 0:未使用，需判断过期时间; 1:已使用
        'name' => 'string',         //红包名称
        'source' => 'string'        //红包来源, new_user:新用户红包; charge:充值红包; bind_mobile:绑定手机红包; refer_mobile_from:邀请好友红包; refer_mobile_to:双享红包; refer_order_from:邀请好友红包; refer_order_to:邀请好友红包; activity:活动红包; exchange:兑换红包; restaurant_activity_order_hongbao:下单红包; weixin_share_hongbao:微信分享红包;
    );

    public function testData()
    {
        return require_once 'seeds/hongbao_20852.php';
    }

    /**
     * @depends testData
     */
    public function testByUserId($data)
    {
        $queryArray = array(
            'user_id' => 1741148,
            'statuses' => null,
            'offset' => 0,
            'limit' => 1
        );
        $this->client('eus')->shouldReceive('request')->with('query_hongbao', [new THongbaoQuery($queryArray)])->once()->andReturn([$data]);
        $response = $this->call('GET', '/v1/users/1741148/hongbao?limit=1');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $hongbaos = json_decode($response->getContent(), true);
        $this->assertEmpty(array_diff(array_keys($this->attributesType), array_keys($hongbaos[0])));
        $this->assertAttributesType($hongbaos[0]);
    }

    /**
     * @depends testData
     */
    public function testExchange($data)
    {
        //接口没有传递兑换码
        $response = $this->call('POST', '/v1/users/1741148/hongbao/exchange');
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $result = json_decode($response->getContent(), true);
        $this->assertEquals('VALIDATION_FAILED', $result['name']);

        //接口传递了兑换码
        $this->client('eus')->shouldReceive('request')->with('exchange_hongbao', [1741148, 2222])->once()->andReturn('abcdefg');
        $response = $this->call('POST', '/v1/users/1741148/hongbao/exchange', json_encode(array('exchange_code' => '2222')));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $result = json_decode($response->getContent(), true);
        $this->assertEquals('abcdefg', $result['hongbao_sn']);
    }

    /**
     * @depends testData
     */
    public function testCount($data)
    {
        $queryArray = array(
            'user_id' => 1741148,
            'statuses' => null,
            'begin_date_from' => '2010-10-10',
            'begin_date_to' => '2015-11-11'
        );
        $this->client('eus')->shouldReceive('request')->with('count_hongbao', [new THongbaoQuery($queryArray)])->once()->andReturn(15);
        $response = $this->call('GET', '/v1/users/1741148/hongbao/count?begin_date=2010-10-10T00%3A00%3A00%2B0800%2C2015-11-11T00%3A00%3A00%2B0800');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $result = json_decode($response->getContent(), true);
        $this->assertEquals('15', $result['count']);

        // 接口传递的日期格式出现错误的情况
        $response = $this->call('GET', '/v1/users/1741148/hongbao/count?begin_date=2010-10-10,2015-1-20');
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $result = json_decode($response->getContent(), true);
        $this->assertEquals('VALIDATION_FAILED', $result['name']);
    }
}
