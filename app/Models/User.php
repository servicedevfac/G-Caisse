<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
class User extends Authenticatable
{
    use Notifiable;
    protected $attributes = ['is_admin' => false, 'is_active' => true];
    protected $fillable = ['name', 'email', 'password', 'is_admin', 'is_active', 'invitation_pending'];
    protected $hidden = ['password', 'remember_token'];
    protected function casts(): array { return ['password' => 'hashed', 'is_admin' => 'boolean', 'is_active' => 'boolean', 'invitation_pending' => 'boolean']; }
    public function transactions() { return $this->hasMany(Transaction::class); }
    public function invitation() { return $this->hasOne(UserInvitation::class); }
}
