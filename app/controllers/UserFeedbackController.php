<?php

use Eleme\Validation\ValidationException;
use Geohash\Geohash;

class UserFeedbackController extends UserResourceController
{

    protected static $model = 'Feedback';

    protected $defaultQueryMethod = 'queryByUserId';

    public function count($userId)
    {
        return Response::json(array('count' => Feedback::count($userId)));
    }

    public function store($userId)
    {
        $rules = array(
            'content' => 'required',
            'type' => 'required|numeric|in:1,2,3,4'
        );
        $validation = Validator::make(Input::json()->all(), $rules);
        if ($validation->fails()) {
            throw new ValidationException($validation->messages());
        }
        $cityId = 0;
        $geohash = Input::json('geohash','');
        if ($geohash !== '') {
            list($latitude, $longitude) = Geohash::decode($geohash);
            $poi = Poi::queryByLocation($latitude, $longitude)->first();
            if ($poi) {
                $city = City::queryByAreaCode(json_decode($poi->info_json,true)['citycode'])->first();;
                $cityId = $city->id;
            }
        }
        $feedbackId = Feedback::add(
            $userId,
            Input::json('content'),
            Input::json('type'),
            $cityId,
            $geohash,
            Request::header('user-agent')
        );
        return Response::json(array('id' => $feedbackId));
    }
}
