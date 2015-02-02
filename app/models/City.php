<?php

use ERS\TCityQuery;

class City extends Model
{
    const DEFAULT_CITY_ID = 1;

    protected $service = 'ers';

    protected $visible = array('id', 'name', 'abbr', 'area_code', 'sort', 'is_map', 'pinyin');

    public static function get($id = self::DEFAULT_CITY_ID)
    {
        return self::factory()->call('get_city')->with($id)->get();
    }

    public static function queryByAreaCode($areaCode)
    {
        return self::factory()->call('get_city_by_area_code')->with($areaCode)->query();
    }

    public static function query($offset, $limit)
    {
        return self::factory()->call('query_city')->with(new TCityQuery(array('is_valid' => true, 'offset' => $offset, 'limit' => $limit)))->query();
    }

    public static function queryByIds($ids)
    {
        return (new self)->newCollection(array_values(self::mgetMap($ids)));
    }

    public static function mgetMap($ids)
    {
        $TCityMap = self::factory()->call('mget_city')->with($ids)->result(array());
        $cityMap = array();
        foreach ($TCityMap as $id => $TCity) {
            $cityMap[$id] = new self($TCity);
        }
        return $cityMap;
    }
}
