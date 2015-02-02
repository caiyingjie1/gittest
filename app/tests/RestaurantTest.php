<?php

use ERS\TRestaurant;

class RestaurantTest extends TestCase
{
    protected $attributesType = array(
        'id' => 'int', //餐厅id
        'name' => 'string', //餐厅名称
        'name_for_url' => 'string', //餐厅链接名（e.g. bigmama）
        'address' => 'string', //餐厅地址
        'latitude' => 'float', //餐厅纬度
        'longitude' => 'float', //餐厅经度
        'phone' => 'string', //餐厅电话
        'rating' => 'float', //餐厅评级（零到五颗星，使用浮点数，保留一位小数）
        'flavors' => 'string', //口味（e.g. 中式,西式,日式）
        'order_lead_time' => 'int', //送餐时间
        'minimum_order_amount' => 'int', //最小起送价
        'minimum_order_description' => 'string', //起送价（文字描述）
        'opening_hours' => 'array', //营业时间（时间段）
        'description' => 'string', //简介
        'promotion_info' => 'string', //公告
        'month_sales' => 'int', //月销量
        'status' => 'int', //营业状态 （1：可以现在配送 2：当前过于繁忙，不支持下单 3：暂时只能通过手机订购 4：已经关闭，不再提供服务 5：只可以预定，不能支付 6：只能通过手机订购，8：休息中）
        'image_path' => 'string', //餐馆图片路径
        'certification_type' => 'int', //餐馆认证, 0:没有，1:个人，2:公司
        'is_premium' => 'bool', //是否是品牌馆
        'is_time_ensure' => 'bool', //是否有超时赔付活动
        'is_free_delivery' => 'bool', //是否需配送费
        'is_in_book_time' => 'bool', //当前时间是否接受预定
        'is_support_invoice' => 'bool', //是否支持开发票
        'is_new' => 'bool', //是否是新开餐厅
        'is_third_party_delivery' => 'bool', //是否第三方配送
        'is_online_payment' => 'bool', //是否接受在线支付
        'delivery_fee' => 'int', //配送费（获取餐馆列表时本数据用于 is_agent_restaurant 判断后的描述信息中）
        'minimum_invoice_amount' => 'float', //开票订单金额（获取餐馆列表时本数据用于 is_support_invoice 判断后的描述信息中）
        'time_ensure_full_description' => 'string', //超时赔付的描述信息（获取餐馆列表时本数据用于 is_time_ensure 判断后的描述信息中）
        'minimum_free_delivery_amount' => 'int', //满多少钱免除配送费（获取餐馆列表时本数据用于 is_agent_restaurant 判断后的描述信息中）
    );

    public function testData()
    {
        return require 'seeds/restaurant_1.php';
    }

    /**
     * @depends testData
     */
    public function testShow($data)
    {
        $this->client('ers')->shouldReceive('request')->with('get', ['1'])->once()->andReturn(new TRestaurant($data));
        $this->client('eos')->shouldReceive('request')->with('redis_mcount_order', [['1']])->once()->andReturn([1]);
        $response = $this->call('GET', '/v1/restaurants/1');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $restaurant = json_decode($response->getContent(), true);
        $this->assertEmpty(array_diff(array_keys($this->attributesType), array_keys($restaurant)));
        $this->assertAttributesType($restaurant);
    }

    /**
     * @depends testData
     */
    public function testByIds($data)
    {
        $this->client('ers')->shouldReceive('request')->with('mget', [['1']])->once()->andReturn([new TRestaurant($data)]);
        $this->client('eos')->shouldReceive('request')->with('redis_mcount_order', [['1']])->once()->andReturn([1]);
        $response = $this->call('GET', '/v1/restaurants?type=ids&ids[]=1');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $restaurants = json_decode($response->getContent(), true);
        $this->assertCount(1, $restaurants);
        $restaurant = reset($restaurants);
        $this->assertEmpty(array_diff(array_keys($this->attributesType), array_keys($restaurant)));
    }
}
