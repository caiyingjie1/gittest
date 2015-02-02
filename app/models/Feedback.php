<?php

use EUS\TFeedbackQuery;
use Eleme\Zeus\Collection;

class Feedback extends Model
{
    protected $service = 'eus';

    public static $defaultQueryMethod = 'queryByUserId';

    protected $visible = array('user_id', 'username', 'created_at', 'content', 'is_processed', 'type', 'replies');

    protected $mutators = array('created_at');

    public static function queryByUserId($userId, $limit = 10, $offset = 0)
    {
        $queryArray = self::getCommentQueryArray($userId);
        $queryArray['limit'] = $limit;
        $queryArray['offset'] = $offset;
        $feedbackWithRepliesList = self::factory()->call('query_feedback_with_replies')->with(new TFeedbackQuery($queryArray))->query();
        $collection = new Collection;
        foreach ($feedbackWithRepliesList as $feedbackWithReplies) {
            $model = new static($feedbackWithReplies->feedback);
            $model->setAttribute('replies', FeedbackReply::getCollection($feedbackWithReplies->feedback_replies));
            $collection->push($model);          
        }
        return $collection;
    }

    public static function count($userId)
    {
        $queryArray = self::getCommentQueryArray($userId);
        return self::factory()->call('count_feedback')->with(new TFeedbackQuery($queryArray))->result(0);
    }

    public static function add($userId, $content, $type, $cityId, $geohash, $userAgent)
    {
        return self::factory()->call('add_feedback')->with($userId, $content, $type, 0, 0, 0, $cityId, $geohash, $userAgent)->run();
    }

    public function getCreatedAtAttribute($createdAt)
    {
        return date(DATE_ISO8601, $createdAt);
    }

    private static function getCommentQueryArray($userId)
    {
        $queryArray = array();
        $user = User::get($userId);
        $queryArray['username'] = $user->username;
        return $queryArray;
    }
}
