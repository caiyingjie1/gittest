<?php

use ERS\TFoodSearchQuery;
use Eleme\Zeus\Collection;

class Food extends Model
{
    protected $service = 'ers';

    protected $visible = array(
        'id', 'name', 'image_path', 'description', 'original_price', 'price', 'recent_popularity', 'is_valid',
        'restaurant', 'restaurant_id', 'food_activity',
    );

    protected $visibleRelations = array('restaurant', 'food_activity');

    protected $visibleBy = array('ids', 'search');

    protected $mutators = array();

    protected $appends = array('image_path');

    public static function queryByIds($ids)
    {
        return self::factory()->call('mget_food')->with($ids)->query();
    }

    public static function queryByUserFavor($userId, $offset = 0, $limit = 10)
    {
        return self::factory()->call('query_favor_food_by_user')->with($userId, $offset, $limit)->query();
    }

    public static function queryBySearch($geohash, $keyword, $limit = 5)
    {
        $ids = array();
        $allRestaurant = Restaurant::queryByGeohash($geohash);
        $ids = $allRestaurant->lists('id');
        if (!$ids) {
            return new Collection;
        }
        $foods = self::search($ids, $keyword, $limit);
        if (!$foods) {
            return new Collection;
        }
        $foodIds = array();
        foreach ($foods as $food) {
            $foodIds[]= $food->id;
        }
        return self::queryByIds($foodIds);
    }

    public static function search($restaurantIds, $keyword, $limit = 5)
    {
        $result = array();
        if (!$restaurantIds || !$keyword) {
            return $result;
        }
        $searchQuery = array(
            'restaurant_ids' => $restaurantIds,
            'keyword' => $keyword,
            'size' => $limit,
        );
        $foods = self::factory()->call('search_food')->with(new TFoodSearchQuery($searchQuery))->result("{}");
        if (!isset(json_decode($foods)->hits)) {
            return $result;
        }
        foreach (json_decode($foods)->hits as $food) {
            $result[] = $food->_source;
        }
        return $result;
    }

    public static function initRestaurant($collection)
    {
        $restaurantIds = $collection->lists('restaurant_id');
        $restaurantMaps = Restaurant::mgetMap($restaurantIds);
        foreach ($collection as $favorFood) {
            $restaurant = array_key_exists($favorFood->restaurant_id, $restaurantMaps) ? $restaurantMaps[$favorFood->restaurant_id] : '';
            $favorFood->setRelation('restaurant', $restaurant);
        }
    }

    public static function initFoodActivity($collection)
    {
        $foodDict = array();
        foreach ($collection as $food) {
            $food->setRelation('food_activity', new Collection);
            $foodDict[$food->id] = $food;
        }
        $foodActivityMapList = FoodActivity::queryByFoodIds(array_keys($foodDict));
        foreach ($foodActivityMapList as $foodId => $foodActivity) {
            if (!$foodActivity->isEmpty()) {
                $foodDict[$foodId]->getRelation('food_activity')->push($foodActivity);
            }
        }
    }

    public function getImagePathAttribute()
    {
        return $this->image_hash ? preg_replace('/^(.)(..)(.{29}(.+))/', '/$1/$2/$3.$4', $this->image_hash) : '';
    }
}
