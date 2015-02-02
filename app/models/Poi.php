<?php

use Geohash\Geohash;

class Poi extends Model
{
    protected $service = 'geos';

    protected $visible = array(
        'name', 'address', 'pguid', 'latitude', 'longitude', 'psn', 'city'
    );

    protected $visibleRelations = array();

    protected $visibleBy = array('psn', 'location');

    protected $mutators = array();

    protected $appends = array('geohash_number');

    public static function queryByPsn($psn)
    {
        return self::factory()->call('get_poi_by_psn')->with($psn)->query();
    }

    public static function queryByLocation($latitude, $longitude)
    {
        return self::factory()->call('get_poi_by_loc')->with($latitude, $longitude)->query();
    }

    public function getGeohashNumberAttribute()
    {
        $geohash = Geohash::encode($this->latitude, $this->longitude);
        return geohash2number($geohash);
    }
}
