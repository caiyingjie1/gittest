<?php

use Mockery as m;
use Eleme\Testing\TestCase as BaseTestCase;
use Eleme\Zeus\ZeusManager;
use Eleme\Zeus\Client;

class TestCase extends BaseTestCase
{
    protected $attributesType = array();

    public function setUp()
    {
        if (!$this->app) {
            $this->refreshApplication();
        }
        $clients = array();
        foreach (array('ers', 'eos', 'eus', 'sms', 'geos', 'fuss') as $key) {
            $clients[$key] = m::mock(new Client(null, $key));
        }
        $this->clients = $clients;
        ZeusManager::setClients($this->clients);
    }

    public function assertAttributesType(array $model)
    {
        foreach ($this->attributesType as $key => $type) {
            $this->assertInternalType($type, $model[$key]);
        }
    }

    protected function client($service)
    {
        return $this->clients[$service];
    }

    protected function createApplication()
    {
        putenv("ELEME_ENV=testing");
        return require __DIR__.'/../start.php';
    }
}
