<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tracking_id',
        'firstname',
        'lastname',
        'email',
        'user_type',
        'username',
        'password',
        'provider',
        'primary_institution_id',
        'channels_subscribed',
        'subchannels_subscribed',
        'otp',
        'saved_posts',
        'reset_password',
        'device_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'provider',
        'remember_token',
        'otp',
        'device_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'channels_subscribed' => 'array',
        'subchannels_subscribed' => 'array',
        'saved_posts' => 'array',
        'reset_password' => 'boolean',
    ];

    protected $dates = [
        // other dates...
        'email_verified_at',
    ];

    public function institution()
    {
        return $this->belongsTo(Institution::class, 'primary_institution_id');
    }

    public function channels()
    {
        return $this->hasMany(Channel::class, 'super_admin_id');
    }

    public function subChannels()
    {
        return $this->hasMany(SubChannel::class, 'admin_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'poster_id');
    }

    public function generateVerificationToken()
    {
        $this->verification_token = Str::random(64);
    }

    /**
     * Set the value of the "channels_subscribed" attribute as JSON-encoded.
     *
     * @param mixed $value
     * @return void
     */
    public function setChannelsSubscribedAttribute($value)
    {
        $this->attributes['channels_subscribed'] = json_encode($value);
    }

    public function setSubChannelsSubscribedAttribute($value)
    {
        $this->attributes['subchannels_subscribed'] = json_encode($value);
    }
}
