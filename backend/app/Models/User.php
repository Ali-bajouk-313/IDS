<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $primaryKey = 'id';

    public $timestamps = false;


    protected $fillable = [
        'roleId',
        'departmentId',
        'fullName',
        'email',
        'password',
        'phone',
        'status',
        'createdAt',
        'updatedAt'
    ];


    protected $hidden = [
        'password',
    ];


    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'createdAt' => 'datetime',
            'updatedAt' => 'datetime',
        ];
    }


    // JWT Configuration

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }


    public function getJWTCustomClaims()
    {
        return [];
    }


    // Relationship with Role table

    public function role()
    {
        return $this->belongsTo(
            Role::class,
            'roleId',
            'id'
        );
    }

    public function assignedTickets()
    {
        return $this->hasMany(Ticket::class, 'assignedTo', 'id');
    }

    public function comments()
    {
        return $this->hasMany(TicketComment::class, 'userId', 'id');
    }

    public function internalNotes()
    {
        return $this->hasMany(TicketInternalNote::class, 'user_id', 'id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'user_id', 'id');
    }
}