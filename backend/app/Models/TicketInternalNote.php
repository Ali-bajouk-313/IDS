<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketInternalNote extends Model
{
    use HasFactory;

    protected $table = 'ticket_internal_notes';

    protected $fillable = [
        'ticket_id',
        'user_id',
        'note',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
