<?php

namespace Bleuren\SocialiteUnify\Models;

use Illuminate\Database\Eloquent\Model;

class SocialAccount extends Model
{
    protected $fillable = [
        'user_id',
        'provider_name',
        'provider_id',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
