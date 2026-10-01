<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['original_size' => 'integer', 'stored_size' => 'integer', 'is_compressed' => 'boolean'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function canBeDeletedBy(User $user): bool
    {
        return $user->is_admin || $this->user_id === $user->id;
    }
}
