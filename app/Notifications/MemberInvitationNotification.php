<?php

namespace App\Notifications;

use App\Models\MemberInvitation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class MemberInvitationNotification extends Notification implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $invitation;
    public $receiverId;
    public $message;
    public $senderAvatar;
    public $senderName;
    public $type; // Added for tracking notification type (accepted/declined)

    /**
     * Create a new notification instance.
     */
    public function __construct(MemberInvitation $invitation)
    {
        $this->invitation = $invitation;
        $this->receiverId = $invitation->receiver_id;
        $sender = $invitation->sender;
        $this->senderAvatar = $sender ? $sender->avatar : null;
        $this->senderName = $sender ? $sender->name : null;
        $this->message = $this->senderName . ' has sent you a friend invitation.';
        $this->type = 'sent'; // Default type
    }

    public static function createAcceptedNotification($invitation, $acceptor)
    {
        $notification = new static($invitation);
        $notification->receiverId = $invitation->sender_id;
        $notification->senderAvatar = $acceptor->avatar;
        $notification->senderName = $acceptor->name;
        $notification->message = $acceptor->name . ' has accepted your friend invitation.';
        $notification->type = 'accepted';
        return $notification;
    }

    public static function createDeclinedNotification($invitation, $decliner)
    {
        $notification = new static($invitation);
        $notification->receiverId = $invitation->sender_id;
        $notification->senderAvatar = $decliner->avatar;
        $notification->senderName = $decliner->name;
        $notification->message = $decliner->name . ' has declined your friend invitation.';
        $notification->type = 'declined';
        return $notification;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the array representation of the notification for database.
     */
    public function toDatabase(): array
    {
        return [
            'message' => $this->message,
            'invitation_id' => $this->invitation->id,
            'sender_avatar' => $this->senderAvatar,
            'sender_name' => $this->senderName,
            'type' => $this->type, // Add type to database representation
        ];
    }

    /**
     * Get the array representation of the notification for broadcast.
     */
    public function toBroadcast(): array
    {
        $unreadCount = 0;
        if ($this->receiverId) {
            $userModel = \App\Models\User::find($this->receiverId);
            if ($userModel) {
                $unreadCount = $userModel->unreadNotifications()->count();
            }
        }
        return [
            'message' => $this->message,
            'invitation_id' => $this->invitation->id,
            'unread_count' => $unreadCount,
            'sender_avatar' => $this->senderAvatar,
            'sender_name' => $this->senderName,
            'type' => $this->type, // Add type to broadcast representation
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('user-notification.' . $this->receiverId)];
    }
}
