<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
        'created_by',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // Mutator for password
    public function setPasswordAttribute($value)
    {
        if ($value && !\Illuminate\Support\Str::startsWith($value, '$2y$')) {
            $this->attributes['password'] = bcrypt($value);
        } else {
            $this->attributes['password'] = $value;
        }
    }

    // Relationship: creator
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relationship: users created by this user
    public function createdUsers()
    {
        return $this->hasMany(User::class, 'created_by');
    }

    // Accessor for full avatar URL
    public function getAvatarUrlAttribute()
    {
        return $this->avatar ? asset('storage/' . $this->avatar) : null;
    }
}