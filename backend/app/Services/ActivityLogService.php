<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ActivityLogService
{
    public function logLogin(User $user, ?string $ipAddress): void
    {
        $this->insert($user->id, 'LOGIN', "{$user->role->roleName} {$user->fullName} logged in", $ipAddress);
    }

    public function logLogout(User $user, ?string $ipAddress): void
    {
        $this->insert($user->id, 'LOGOUT', "{$user->role->roleName} {$user->fullName} logged out", $ipAddress);
    }

    public function logRegister(User $user, ?string $ipAddress): void
    {
        $this->insert($user->id, 'REGISTER', "{$user->fullName} registered a new account", $ipAddress);
    }

    public function logTicketCreated(User $user, Ticket $ticket, ?string $ipAddress): void
    {
        $this->insert($user->id, 'CREATE_TICKET', "{$user->role->roleName} created Ticket #{$ticket->id}", $ipAddress);
    }

    public function logTicketUpdated(User $user, Ticket $ticket, ?string $ipAddress): void
    {
        $this->insert($user->id, 'UPDATE_TICKET', "{$user->role->roleName} updated Ticket #{$ticket->id}", $ipAddress);
    }

    public function logTicketDeleted(User $user, Ticket $ticket, ?string $ipAddress): void
    {
        $this->insert($user->id, 'DELETE_TICKET', "{$user->role->roleName} deleted Ticket #{$ticket->id}", $ipAddress);
    }

    public function logTicketAssigned(User $user, Ticket $ticket, string $assignedToName, ?string $ipAddress): void
    {
        $this->insert($user->id, 'ASSIGN_TICKET', "{$user->role->roleName} assigned Ticket #{$ticket->id} to {$assignedToName}", $ipAddress);
    }

    public function logTicketReassigned(User $user, Ticket $ticket, string $fromName, string $toName, ?string $ipAddress): void
    {
        $this->insert($user->id, 'REASSIGN_TICKET', "{$user->role->roleName} reassigned Ticket #{$ticket->id} from {$fromName} to {$toName}", $ipAddress);
    }

    public function logTicketUnassigned(User $user, Ticket $ticket, ?string $ipAddress): void
    {
        $this->insert($user->id, 'UNASSIGN_TICKET', "{$user->role->roleName} unassigned Ticket #{$ticket->id}", $ipAddress);
    }

    public function logStatusChanged(User $user, Ticket $ticket, string $oldStatus, string $newStatus, ?string $ipAddress): void
    {
        $this->insert($user->id, 'STATUS_CHANGED', "{$user->role->roleName} changed Ticket #{$ticket->id} status from {$oldStatus} to {$newStatus}", $ipAddress);
    }

    public function logPriorityChanged(User $user, Ticket $ticket, string $oldPriority, string $newPriority, ?string $ipAddress): void
    {
        $this->insert($user->id, 'PRIORITY_CHANGED', "{$user->role->roleName} changed Ticket #{$ticket->id} priority from {$oldPriority} to {$newPriority}", $ipAddress);
    }

    public function logCommentAdded(User $user, Ticket $ticket, ?string $ipAddress): void
    {
        $this->insert($user->id, 'COMMENT_ADDED', "{$user->role->roleName} added comment to Ticket #{$ticket->id}", $ipAddress);
    }

    public function logInternalNoteAdded(User $user, Ticket $ticket, ?string $ipAddress): void
    {
        $this->insert($user->id, 'INTERNAL_NOTE_ADDED', "Internal note added to Ticket #{$ticket->id}", $ipAddress);
    }

    protected function insert(int $userId, string $action, string $description, ?string $ipAddress): void
    {
        if (!Schema::hasTable('activitylogs')) {
            return;
        }

        DB::table('activitylogs')->insert([
            'userId' => $userId,
            'action' => $action,
            'description' => $description,
            'ipAddress' => $ipAddress,
            'createdAt' => now(),
        ]);
    }
}
