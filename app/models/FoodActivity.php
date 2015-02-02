<?php

class FoodActivity extends Model
{
    protected $service = 'ers';

    protected $visible = array('id', 'name', 'description', 'icon_name', 'image_text', 'image_text_color');

    public static function queryByRestaurantIds($ids)
    {
        $TActivityWithIds = self::factory()->call('get_food_activity_with_restaurant_ids')->with($ids, [])
            ->result(array());

        $activityWithIds = [];
        foreach ($TActivityWithIds as $TActivityWithId) {
            $activityWithIds[] = [
                'food_activity' => new self($TActivityWithId->food_activity),
                'restaurant_ids' => $TActivityWithId->restaurant_ids
            ];
        }
        return $activityWithIds;
    }

    public static function queryByIds($ids)
    {
        return self::factory()->call('mget_food_activity')->with($ids)->query();
    }

    public static function queryByFoodIds($ids)
    {
        $acrivityWithIds = self::factory()->call('get_food_activity_id_map')->with($ids)->result(array());
        if ($acrivityWithIds) {
            $ids = array_values($acrivityWithIds);
            $activities = self::queryByIds($ids);
            foreach ($acrivityWithIds as $foodId => $activityId) {
                foreach ($activities as $activity) {
                    if ($activity->id === $activityId) {
                        $acrivityWithIds[$foodId] = $activity;
                        break;
                    }
                }
                $acrivityWithIds[$foodId] = new FoodActivity;
            }
        }
        return $acrivityWithIds;
    }
}
