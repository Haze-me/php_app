<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PendingAdmin extends Model
{
    use HasFactory;

    protected $table = 'pending_admins';

    protected $fillable = [
        'email',
        'sub_channel_id',
        'channel_id',
        'uuid',
    ];

    public function subChannel()
    {
        return $this->belongsTo(SubChannel::class, 'sub_channel_id');
    }

    public function channel()
    {
        return $this->belongsTo(Channel::class, 'channel_id');
    }

}
