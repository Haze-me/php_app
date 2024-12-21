<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubChannel extends Model
{
    use HasFactory;

    protected $table = 'sub_channels';

    protected $fillable = [
        'name',
        'profileImage',
        'description',
        'type',
        'category',
        'targetAudience',
        'admin_id',
        'subscribers',
        'subchannelWebsite',
        'status',
        'deleted',
        'primary_institution_id',
        'topic_name',
        'channel_id',
    ];

    public $timestamps = false;

    protected $casts = [
        'deleted' => 'boolean',
        'status' => 'integer',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function channel()
    {
        return $this->belongsTo(Channel::class, 'channel_id');
    }

}
