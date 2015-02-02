<?php

use Eleme\Zeus\Collection;

class Message extends Model
{
    protected $service = 'eus';

    protected $visible = array(
        'id', 'abstract', 'content', 'url', 'created_at'
    );

    protected $visibleRelations = array();

    protected $visibleBy = array();

    protected $mutators = array('created_at');

    protected $appends = array('abstract');

    public static function queryUnReadByUserId($userId)
    {
        return self::factory()->call('query_unread_user_message')->with($userId)->query();
    }

    public static function markAsRead($userId, $messageId)
    {
        return self::factory()->call('mark_message_as_read')->with($userId, $messageId)->run();
    }

    public static function markAllAsRead($userId)
    {
        return self::factory()->call('mark_all_message_as_read')->with($userId)->run();
    }

    public static function countByUserId($userId)
    {
        return self::factory()->call('count_unread_message')->with($userId)->result(0);
    }

    public function getCreatedAtAttribute($createdAt)
    {
        return Date(DATE_ISO8601, $createdAt);
    }

    public function getAbstractAttribute()
    {
        return $this->msg_abstract;
    }
}
