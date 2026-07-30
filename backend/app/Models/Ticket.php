<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $table = 'tickets';
    protected $primaryKey = 'id';

    public const CREATED_AT = 'createdAt';
    public const UPDATED_AT = 'updatedAt';

    protected $fillable = [
        'ticketNumber',
        'title',
        'description',
        'categoryId',
        'createdBy',
        'assignedTo',
        'priority',
        'status',
        'closedAt',
    ];

    protected function casts(): array
    {
        return [
            'createdAt' => 'datetime',
            'updatedAt' => 'datetime',
            'closedAt' => 'datetime',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'categoryId', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'createdBy', 'id');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assignedTo', 'id');
    }

    public function assignments()
    {
        return $this->hasMany(TicketAssignment::class, 'ticket_id', 'id');
    }

    public function comments()
    {
        return $this->hasMany(TicketComment::class, 'ticketId', 'id');
    }

    public function internalNotes()
    {
        return $this->hasMany(TicketInternalNote::class, 'ticket_id', 'id');
    }
}
