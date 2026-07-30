<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketAssignment extends Model
{
    use HasFactory;

    protected $table = 'ticket_assignments';

    protected $fillable = [
        'ticket_id',
        'old_assigned_to',
        'new_assigned_to',
        'assigned_by',
        'action',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id', 'id');
    }

    public function oldAssignedUser()
    {
        return $this->belongsTo(User::class, 'old_assigned_to', 'id');
    }

    public function newAssignedUser()
    {
        return $this->belongsTo(User::class, 'new_assigned_to', 'id');
    }

    public function assignedByUser()
    {
        return $this->belongsTo(User::class, 'assigned_by', 'id');
    }
}
