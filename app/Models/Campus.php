<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Campus extends Model
{
  use HasFactory;

  protected $fillable = [
    'name',
    'logo',
    'website',
    'institution_id',
  ];

  public function institution(): BelongsTo
  {
    return $this->belongsTo(Institution::class);
  }
}
