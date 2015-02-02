<?php

use ERS\TActivityQuery;

class Banner extends Model
{
    protected $service = 'ers';

    protected $visible = array(
        'image_path', 'link'
    );

    protected $visibleRelations = array();

    protected $visibleBy = array('geohash');

    protected $mutators = array();

    protected $appends = array('image_path');

    public static function queryByGeohash($geohash)
    {
        $currentDate = Date("Y-m-d");
        $currentWeek = Date("w");

        $queryStruct = new TActivityQuery();
        $queryStruct->is_valid = true;
        $queryStruct->start_date = $currentDate;
        $queryStruct->end_date = $currentDate;
        $queryStruct->weekday = $currentWeek;
        $queryStruct->type = 1;
        $queryStruct->geohash = geohash2Number($geohash);

        return self::factory()->call('query_activity')->with($queryStruct)->query();
    }

    public function getImagePathAttribute()
    {
        return $this->image_hash ? preg_replace('/^(.)(..)(.{29}(.+))/', '/$1/$2/$3.$4', $this->image_hash) : '';
    }
}
