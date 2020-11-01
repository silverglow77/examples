<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Orchid\Screen\AsSource;
use Carbon\Carbon;


class VisardoPost extends Model
{
    use AsSource;

    const CREATED_AT = 'post_date';
    const UPDATED_AT = 'post_modified';

    public $timestamps = true;

    protected $primaryKey = 'ID';

    protected $fillable
        = [
            'post_author',
            'post_date',
            'post_date_gmt',
            'post_content',
            'post_title',
            'post_excerpt',
            'post_status',
            'comment_status',
            'ping_status',
            'post_password',
            'post_name',
            'to_ping',
            'pinged',
            'post_modified',
            'post_modified_gmt',
            'post_content_filtered',
            'post_parent',
            'guid',
            'menu_order',
            'post_type',
            'post_mime_type',
            'comment_count'
        ];

    public function setPostModifiedGmtAttribute($value)
    {
        $this->attributes['post_modified_gmt'] = Carbon::now()->timezone('UTC');
    }

    public function setPostDateGmtAttribute($value)
    {

        $this->attributes['post_date_gmt'] = Carbon::now()->timezone('UTC');
    }


    public function getPostModifiedAttribute($value)
    {
        if ($value)
            if ($this->attributes['post_modified']) {
                    return Carbon::createFromTimestamp(strtotime($value))
                        ->timezone('Europe/Moscow')
                        ->toDateTimeString();
            }

        return Carbon::now()->timezone('Europe/Moscow');
    }

    public function getPostDateAttribute($value)
    {

        if ($value)
            if ($this->attributes['post_date']) {
                return Carbon::createFromTimestamp(strtotime($value))
                    ->timezone('Europe/Moscow')
                    ->toDateTimeString();
            }

        return Carbon::now()->timezone('Europe/Moscow');
    }

    /* Attched pic located in some table with others attached files
    */
    public function attachments()
    {
        $ret = $this->hasMany(VisardoPost::class, 'post_parent','ID');
        return $ret;
    }

    public function attachedPic()
    {
        $ret = $this->belongsTo(VisardoPost::class, 'post_parent','ID');
        return $ret;
    }
}
