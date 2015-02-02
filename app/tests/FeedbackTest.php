<?php

use EUS\TFeedbackQuery;
use EUS\TUser;
use EUS\TCFeedbackWithReplies;
use EUS\TFeedback;
use EUS\TFeedbackReply;
use GEOS\TPoi;
use ERS\TCity;

class FeedBackTest extends TestCase
{
    
    public function testData()
    {
        $user = require_once 'seeds/user_485450.php';
        $feedback = require_once 'seeds/feedback_1.php';
        $poi = require_once 'seeds/poi_wtw27pq8n5t.php';
        $city = require_once 'seeds/city_1.php';
        $replies = require_once 'seeds/feedbackReplies.php';
        return array($user, $feedback, $replies, $poi, $city);
    }

    /**
     * @depends testData
     */
    public function testByUserId($data)
    {
        $queryArray = array(
            'username' => 'lllasdf',
            'limit' => 1,
            'offset' => 2
        );
        list($user, $feedback, $replies) = $data;
        $feedbackWithReplies = array('feedback' => new TFeedback($feedback), 'feedback_replies' => array(new TFeedbackReply($replies)));
        $this->client('eus')->shouldReceive('request')->with('get', [485007])->once()->andReturn(new TUser($user));       
        $this->client('eus')->shouldReceive('request')->with('query_feedback_with_replies', [new TFeedbackQuery($queryArray)])->once()->andReturn([new TCFeedbackWithReplies($feedbackWithReplies)]);
        $response = $this->call('GET', '/v1/users/485007/feedbacks?limit=1&offset=2');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $records = json_decode($response->getContent(), true);
        $this->assertAttributesType($records);
    }

    /**
     * @depends testData
     */
    public function testCount($data)
    {
        $queryArray = array(
            'username' => 'lllasdf',
        );
        list($user, $feedback) = $data;
        $this->client('eus')->shouldReceive('request')->with('count_feedback', [new TFeedbackQuery($queryArray)])->once()->andReturn(100);
        $this->client('eus')->shouldReceive('request')->with('get', [485007])->once()->andReturn(new TUser($user));       
        $response = $this->call('GET', '/v1/users/485007/feedbacks/count');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $result = json_decode($response->getContent(), true);
        $this->assertEquals('100', $result['count']);

    }

    /**
     * @depends testData
     */
    public function testStore($data)
    {
        list($user, $feedback, $replies, $poi, $city) = $data;
        $this->client('geos')->shouldReceive('request')->with('get_poi_by_loc', [31.02129, 121.43009])->once()->andReturn(new TPoi($poi));
        $this->client('ers')->shouldReceive('request')->with('get_city_by_area_code', ['021'])->once()->andReturn(new TCity($city));
        $this->client('eus')->shouldReceive('request')->with('add_feedback', [485007, 'ffffffffffff', 1, 0, 0, 0, 1, 'wtw27pq8n5t', 'Symfony2 BrowserKit'])->once()->andReturn(1);
        $response = $this->call('POST', '/v1/users/485007/feedbacks', json_encode(array('content' => 'ffffffffffff', 'type'=>1, 'geohash'=>'wtw27pq8n5t')));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $result = json_decode($response->getContent(), true);
        $this->assertEquals(1, $result['id']);
    }

}
