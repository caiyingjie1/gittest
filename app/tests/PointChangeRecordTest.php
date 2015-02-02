<?php

use Symfony\Component\BrowserKit\Cookie;
use EUS\TWallePointChangeQuery;
use EUS\TPointChangeRecord;

class PointChangeRecordTest extends TestCase
{
    protected $attributesType = array(
        'id' => 'int', //积分变更记录id
        'created_at' => 'string', //记录生成时间
        'delta' => 'int', //积分变更值
        'reason' => 'string', //变更原因
        'relevant_id' => 'int', //相关id(如：订单id)
        'change_type' => 'int', //变更类型
    );

    public function testData()
    {
        return require_once 'seeds/point_change_record_1.php';
    }

    /**
     * @depends testData
     */
    public function testQueryByUserId($data)
    {
        $queryArray = array(
            'user_id' => 485450,
            'limit' => 10,
            'offset' => 0,
            'from_datetime' => strtotime('2010-10-10T00:00:00+0800'),
            'to_datetime' => strtotime('2010-11-11T00:00:00+0800')
        );
        $this->client('eus')->shouldReceive('request')->with('walle_query_point_change', [new TWallePointChangeQuery($queryArray)])->once()->andReturn([new TPointChangeRecord($data)]);
        $response = $this->call('GET', '/v1/users/485450/point_change_records?datetime=2010-10-10T00%3A00%3A00%2B0800%2C2010-11-11T00%3A00%3A00%2B0800');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $records = json_decode($response->getContent(), true);
        $this->assertCount(1, $records);
        $record = reset($records);
        $this->assertEmpty(array_diff(array_keys($this->attributesType), array_keys($record)));
        $this->assertAttributesType($record);
    }
}
