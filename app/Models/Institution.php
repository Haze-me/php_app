<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Institution extends Model
{
    use HasFactory;
    protected $table = 'institutions';

    protected $fillable =[ 'name', 'image', 'website', 'admin_id'];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    public $timestamps = false;

    public function campuses(): HasMany
    {
      return $this->hasMany(Campus::class);
    }

    public function users(): HasMany
    {
      return $this->hasMany(User::class);
    }
}
