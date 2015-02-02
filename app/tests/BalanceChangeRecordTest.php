<?php

use Symfony\Component\BrowserKit\Cookie;
use EUS\TWalleBalanceChangeQuery;
use EUS\TCWalleBalanceChange ;

class BalanceChangeRecordTest extends TestCase
{
    protected $attributesType = array(
        'id' => 'int', //记录id
        'balance' => 'float', //余额
        'balance_change' => 'float', //余额变更值
        'trade_type' => 'int', //交易类型, 0:饿了么账户充值; 1:余额消费; 2:订单收入; 3:订单退款; 4:申请提现; 5:提现失败; 6:(废弃); 7:用户提现; 8:支付失败退款; 9: 三方支付消费; 10:合同付费; 11:订单取消扣款; 12:匿名用户提现; 13:匿名用户退款; 14:(废弃); 15:合同退款;
        'created_at' => 'string', //创建时间
    );

    public function testData()
    {
        return require_once 'seeds/balance_change_record_1.php';
    }

    /**
     * @depends testData
     */
    public function testByUserId($data)
    {
        $queryArray = array(
            'user_id' => '485450',
            'from_datetime' => strtotime('2010-10-10T00:00:00+0800'),
            'to_datetime' => strtotime('2010-11-11T00:00:00+0800'),
            'trade_types' => array(1),
            'limit' => 10,
            'offset' => 0
        );
        $this->client('eus')->shouldReceive('request')->with('walle_query_balance_change', [new TWalleBalanceChangeQuery($queryArray)])->once()->andReturn([new TCWalleBalanceChange($data)]);
        $response = $this->call('GET', '/v1/users/485450/balance_records?trade_type[]=1&datetime=2010-10-10T00%3A00%3A00%2B0800%2C2010-11-11T00%3A00%3A00%2B0800');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $records = json_decode($response->getContent(), true);
        $this->assertCount(1, $records);
        $record = reset($records);
        $this->assertEmpty(array_diff(array_keys($this->attributesType), array_keys($data)));
        $this->assertAttributesType($record);
    }
}
