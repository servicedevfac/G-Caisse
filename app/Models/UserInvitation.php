<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserInvitation extends Model
{
    protected $fillable = ['user_id', 'token_hash', 'expires_at'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
