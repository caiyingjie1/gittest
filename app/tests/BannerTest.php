<?php

use ERS\TActivityQuery;

class BannerTest extends TestCase
{
    protected $attributesType = array(
        'image_path' => 'string',  //广告图片路径
        'link' => 'string'  //广告链接
    );

    public function testData()
    {
        return require_once 'seeds/banner_16.php';
    }

    /**
     * @depends testData
     */
    public function testByGeohash($data)
    {
        $this->client('ers')->shouldReceive('request')->once()->andReturn([$data]);
        $response = $this->call('GET', '/v1/banners?type=geohash&geohash=wtw27pq8n5t');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $banners = json_decode($response->getContent(), true);
        $this->assertCount(1, $banners);
        $banner = reset($banners);
        $this->assertEmpty(array_diff(array_keys($this->attributesType), array_keys($banner)));
        $this->assertAttributesType($banner);
    }
}
