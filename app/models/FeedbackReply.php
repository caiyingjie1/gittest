<?php

use Eleme\Zeus\Collection;

class FeedbackReply extends Model
{
    protected $service = 'eus';

    protected $visible = array('id', 'feedback_id', 'user_id', 'username', 'created_at', 'content', 'is_from_admin');

    protected $mutators = array('created_at');

    public static function getCollection($results)
    {
        $tobjects = is_array($results) ? $results : array($result);
        $collection = new Collection;
        foreach ($tobjects as $tobject) {
            $model = new static($tobject);
            $collection->push($model);
        }
        return $collection;
    }

    public function getCreatedAtAttribute($createdAt)
    {
        return date(DATE_ISO8601, $createdAt);
    }
}
