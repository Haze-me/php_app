<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $table = 'posts';

    protected $fillable = [
        'channel_id',
        'sub_channel_id',
        'uuid',
        'poster_id',
        'post_title',
        'post_body',
        'post_images',
        'viewType',
        'users_viewed',
    ];

    protected $casts = [
        'deleted' => 'boolean',
        'post_images' => 'array',
    ];

    public function channel()
    {
        return $this->belongsTo(Channel::class, 'channel_id');
    }

    public function sub_channel()
    {
        return $this->belongsTo(SubChannel::class, 'sub_channel_id');
    }

    public function poster()
    {
        return $this->belongsTo(User::class, 'poster_id');
    }
}
