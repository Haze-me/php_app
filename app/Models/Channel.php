<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Channel extends Model
{
    use HasFactory;

    protected $table = 'channels';

    protected $fillable = [
        'name',
        'profileImage',
        'description',
        'type',
        'rating',
        'subscribers',
        'super_admin_id',
        'channelWebsite',
        'sub_admins',
        'sub_channels',
        'suspended_admins',
        'pending_admins',
        'removed_admins',
        'institution_id',
        'is_primary',
        'topic_name',
    ];

    protected $casts = [
        'sub_admins' => 'array',
        'sub_channels' => 'array',
        'suspended_admins' => 'array',
        'pending_admins' => 'array',
        'removed_admins' => 'array',
        'is_primary' => 'boolean',

    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'super_admin_id');
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }
}
