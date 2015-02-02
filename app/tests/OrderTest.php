<?php

use EOS\TOrder;
use EOS\TOrderItem;
use EOS\TElemeOrderRate;
use EOS\TOrderItemRating;
use ERS\TRestaurant;
use EUS\TOrderPaymentConstitution;

class OrderTest extends TestCase
{
    protected $attributesType = array(
        'id' => 'int', //饿单id
        'restaurant_id' => 'int', //餐厅id
        'restaurant_name' => 'string', //餐厅名
        'user_id' => 'int', //用户id
        'user_name' => 'string', //用户名
        'detail_json' => 'string', //饿单详细信息
        'total' => 'float', //总金额
        'deliver_fee' => 'float', //配送费
        'is_online_paid' => 'int', //是否已经在线支付
        'settled_at' => 'string', //饿单完成时间
        'address' => 'string', //饿单地址
        'phone' => 'string', //联系电话
        'description' => 'string', //饿单备注
        'unique_id' => 'string', //饿单号
        'status_code' => 'int', //饿单状态, -5:未支付;-4:支付失败;-3:正在支付;-2:等待审核;-1:无效;0:未处理;1:处理中;2:已经处理并有效订单;3:退单中;
        'refund_status' => 'int', //饿单退单状态, 0:未申请退单;5:退单失败;6:退单成功;
        'is_book' => 'int', //是否预定饿单
        'deliver_time' => 'string', //预定饿单配送时间
        'created_at' => 'string', //饿单生成时间
        'invoice' => 'string', //发票抬头
        'active_at' => 'string', //支付完成时间
        'complaint_status' => 'int', //订单投诉状态, 0:可投诉; 1:已投诉; 2:不可投诉;
        'is_refund_valid' => 'int', //是否可退单
        'is_phone_refund_valid' => 'int', //是否可以电话退单
    );

    public function testData()
    {
        $order = require 'seeds/order_12447593057023691.php';
        $restaurant = require 'seeds/restaurant_1.php';
        $orderRate = require 'seeds/order_rating_1.php';
        $orderItem = require 'seeds/order_item_1.php';
        $orderItemRating = require 'seeds/order_item_rating_1.php';
        $paymentConstitution = require 'seeds/payment_constitution_1.php';
        return array($order, $restaurant, $orderRate, $orderItem, $orderItemRating, $paymentConstitution);
    }

    /**
     * @depends testData
     */
    public function testShow($data)
    {
        list($order, $restaurant, $orderRate, $orderItem, $orderItemRating, $paymentConstitution) = $data;
        $this->client('eos')->shouldReceive('request')->with('get', [12447593057023691])->once()->andReturn(new TOrder($order));
        $this->client('eos')->shouldReceive('request')->with('check_order_complaint_existed', [12447593057023691])->once()->andReturn(true);
        $this->client('ers')->shouldReceive('request')->with('mget', [[1]])->once()->andReturn(array(new TRestaurant($restaurant)));
        $this->client('eos')->shouldReceive('request')->with('redis_mcount_order', [['1']])->once()->andReturn([1]);
        $this->client('eos')->shouldReceive('request')->with('query_order_rate', [[12447593057023691]])->once()->andReturn([new TElemeOrderRate($orderRate)]);
        $this->client('eus')->shouldReceive('request')->with('get_order_payment_constitution_map', [[12447593057023691]])->once()->andReturn([new TOrderPaymentConstitution($paymentConstitution)]);
        $this->client('eos')->shouldReceive('request')->with('mget_order_item_by_order_id', [[12447593057023691]])->once()->andReturn([new TOrderItem($orderItem)]);
        $this->client('eos')->shouldReceive('request')->with('mget_order_item_rating', [[1]])->once()->andReturn([new TOrderItemRating($orderItemRating)]);
        $response = $this->call('GET', '/v1/users/485450/orders/12447593057023691?extras[]=order_hash_key&extras[]=restaurant&extras[]=order_rate&extras[]=order_item&extras[]=payment_constitution');
        $this->assertEquals(200, $response->getStatusCode());
        $order = json_decode($response->getContent(), true);
        $this->assertEmpty(array_diff(array_keys($this->attributesType), array_keys($order)));
        $this->assertAttributesType($order);
        $this->assertEquals($order['order_hash_key'], md5($order['unique_id'].Order::HASH_SECRET));
        $this->assertArrayHasKey('restaurant', $order);
        $this->assertArrayHasKey('order_rate', $order);
        $this->assertArrayHasKey('order_item', $order);
        $this->assertArrayHasKey('payment_constitution', $order);
    }

    public function testRate()
    {
        //点评送餐时间
        $this->client('eos')->shouldReceive('request')->with('rate_deliver_time_spent', [12447593057023691, 485450, 20])->once()->andReturn();
        $response = $this->call('POST', '/v1/users/485450/orders/12447593057023691/rating', json_encode(array('rating_type' => 'time', 'spent_time' => 20)));
        $this->assertEquals(204, $response->getStatusCode());

        //点评餐厅服务
        $this->client('eos')->shouldReceive('request')->with('rate_service', [12447593057023691, 485450, 3, 'good'])->once()->andReturn();
        $response = $this->call('POST', '/v1/users/485450/orders/12447593057023691/rating', json_encode(array('rating_type' => 'service', 'rating_value' => 3, 'rating_text' => 'good')));
        $this->assertEquals(204, $response->getStatusCode());
    }

    public function testRateItem()
    {
        $this->client('eos')->shouldReceive('request')->with('rate_order_item', [1, 485450, 3, 'good'])->once()->andReturn();
        $response = $this->call('POST', '/v1/users/485450/orders/12447593057023691/items/1/rating', json_encode(array('rating_value' => 3, 'rating_text' => 'good')));
        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @depends testData
     */
    public function testAddComplaint($data)
    {
        //订单不再可投诉状态
        list($order, $restaurant, $orderRate, $orderItem, $orderItemRating, $paymentConstitution) = $data;
        $this->client('eos')->shouldReceive('request')->with('get', [12447593057023691])->once()->andReturn(new TOrder($order));
        $this->client('eos')->shouldReceive('request')->with('check_order_complaint_existed', [12447593057023691])->once()->andReturn(true);
        $response = $this->call('POST', '/v1/users/485450/orders/12447593057023691/complaint', json_encode(array('type' => '2', 'content' => 'fake_content')));
        $this->assertEquals('403', $response->getStatusCode());
        $error = json_decode($response->getContent(), true);
        $this->assertEquals('INVALID_COMPLAINT_ORDER', $error['name']);

        //订单可投诉
        list($order, $restaurant, $orderRate, $orderItem, $orderItemRating, $paymentConstitution) = $data;
        $order['created_at'] = time() - 10 * 60;
        $order['status_code'] = 2;
        $this->client('eos')->shouldReceive('request')->with('get', [12447593057023691])->once()->andReturn(new TOrder($order));
        $this->client('eos')->shouldReceive('request')->with('check_order_complaint_existed', [12447593057023691])->once()->andReturn(false);
        $this->client('eos')->shouldReceive('request')->with('add_order_complaint', [485450, 12447593057023691, 2, 'fake_content']);
        $response = $this->call('POST', '/v1/users/485450/orders/12447593057023691/complaint', json_encode(array('type' => '2', 'content' => 'fake_content')));
        $this->assertEquals('204', $response->getStatusCode());
    }
}
