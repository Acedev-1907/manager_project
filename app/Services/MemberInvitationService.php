<?php

namespace App\Services;

use App\Repositories\MemberInvitationRepository;
use App\Models\User;
use App\Models\Member;
use App\Events\MemberEvent;
use Illuminate\Support\Facades\Log;

class MemberInvitationService
{
    protected $repo;
    public function __construct(MemberInvitationRepository $repo)
    {
        $this->repo = $repo;
    }

    public function sendInvitation($sender, $friendCode)
    {
        $receiver = User::where('friend_code', $friendCode)->first();
        if (!$receiver) return ['error' => 'User not found with this friend code'];
        if ($receiver->id == $sender->id) return ['error' => 'You cannot invite yourself'];
        $exists = $this->repo->existsPending($sender->id, $receiver->id);
        if ($exists) return ['error' => 'Invitation already sent'];
        $invitation = $this->repo->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'status' => 'pending',
        ]);
        // Trước khi gửi notification, kiểm tra đã có notification 'sent' chưa đọc chưa
        $oldNoti = $receiver->notifications()
            ->whereRaw("data->>'invitation_id' = ?", [$invitation->id])
            ->whereRaw("data->>'type' = 'sent'")
            ->whereNull('read_at')
            ->first();
        if (!$oldNoti) {
            // Gửi notification cho receiver
            $receiver->notify(new \App\Notifications\MemberInvitationNotification($invitation));
        }
        // Broadcast event tới receiver
        broadcast(new MemberEvent($receiver->id, $sender->id, 'invitation_sent', [
            'invitation_id' => $invitation->id,
            'sender' => [
                'id' => $sender->id,
                'name' => $sender->name,
                'email' => $sender->email,
                'avatar' => $sender->avatar,
            ]
        ]));
        // Broadcast event tới sender (người gửi) để realtime tab Sent Invitations
        $invitationArr = $invitation->toArray();
        $invitationArr['sender'] = [
            'id' => $sender->id,
            'name' => $sender->name,
            'email' => $sender->email,
            'avatar' => $sender->avatar,
        ];
        $invitationArr['receiver'] = [
            'id' => $receiver->id,
            'name' => $receiver->name,
            'email' => $receiver->email,
            'avatar' => $receiver->avatar,
        ];
        // Đảm bảo có đủ các trường cần thiết cho FE
        if (!isset($invitationArr['status'])) $invitationArr['status'] = $invitation->status;
        if (!isset($invitationArr['sender_id'])) $invitationArr['sender_id'] = $invitation->sender_id;
        if (!isset($invitationArr['receiver_id'])) $invitationArr['receiver_id'] = $invitation->receiver_id;
        if (!isset($invitationArr['created_at'])) $invitationArr['created_at'] = $invitation->created_at;
        // Gửi object invitation đầy đủ cho cả receiver và sender
        broadcast(new MemberEvent($receiver->id, $sender->id, 'invitation_sent', [
            'invitation_id' => $invitation->id,
            'invitation' => $invitationArr,
        ]));
        broadcast(new MemberEvent($sender->id, $receiver->id, 'invitation_sent', [
            'invitation_id' => $invitation->id,
            'invitation' => $invitationArr,
        ]));
        return ['invitation' => $invitation];
    }

    public function getReceived($user)
    {
        return $this->repo->getPendingReceived($user->id);
    }

    public function getSent($user)
    {
        return $this->repo->getPendingSent($user->id);
    }

    public function accept($user, $id)
    {
        $invitation = $this->repo->findPendingByIdAndReceiver($id, $user->id);
        if (!$invitation) return ['error' => 'Invitation not found'];
        $invitation->status = 'accepted';
        $invitation->save();
        Member::create(['user_id' => $invitation->sender_id, 'member_id' => $user->id]);
        Member::create(['user_id' => $user->id, 'member_id' => $invitation->sender_id]);
        $receiverUser = User::find($user->id);
        $senderUser = User::find($invitation->sender_id);
        // Update notification cũ cho receiver
        $oldNoti = $receiverUser->notifications()
            ->whereRaw("data->>'invitation_id' = ?", [$invitation->id])
            ->whereRaw("data->>'type' = 'sent'")
            ->first();
        if ($oldNoti) {
            $data = $oldNoti->data;
            $data['type'] = 'accepted';
            // KHÔNG set message tĩnh
            if (!isset($data['sender_name'])) $data['sender_name'] = $receiverUser->name;
            if (!isset($data['invitation_id'])) $data['invitation_id'] = $invitation->id;
            $oldNoti->data = $data;
            $oldNoti->save();
        }
        // Gửi notification cho sender (người gửi) - thông báo đã được accept
        $senderUser->notify(\App\Notifications\MemberInvitationNotification::createAcceptedNotification($invitation, $receiverUser));
        // Không gửi notification mới cho receiver nữa
        // Broadcast event tới sender (người gửi)
        broadcast(new MemberEvent($invitation->sender_id, $user->id, 'invitation_accepted', [
            'invitation_id' => $invitation->id,
            'member' => [
                'id' => $receiverUser->id,
                'name' => $receiverUser->name,
                'email' => $receiverUser->email,
                'avatar' => $receiverUser->avatar,
            ]
        ]));
        // Broadcast event tới receiver (người nhận) - chính là user vừa accept
        broadcast(new MemberEvent($user->id, $invitation->sender_id, 'invitation_accepted', [
            'invitation_id' => $invitation->id,
            'member' => [
                'id' => $senderUser->id,
                'name' => $senderUser->name,
                'email' => $senderUser->email,
                'avatar' => $senderUser->avatar,
            ]
        ]));
        return ['message' => 'Invitation accepted'];
    }

    public function decline($user, $id)
    {
        $invitation = $this->repo->findPendingByIdAndReceiver($id, $user->id);
        if (!$invitation) return ['error' => 'Invitation not found'];
        $invitation->status = 'declined';
        $invitation->save();
        $receiverUser = User::find($user->id);
        $senderUser = User::find($invitation->sender_id);
        // Update notification cũ cho receiver
        $oldNoti = $receiverUser->notifications()
            ->whereRaw("data->>'invitation_id' = ?", [$invitation->id])
            ->whereRaw("data->>'type' = 'sent'")
            ->first();
        if ($oldNoti) {
            $data = $oldNoti->data;
            $data['type'] = 'declined';
            // KHÔNG set message tĩnh
            if (!isset($data['sender_name'])) $data['sender_name'] = $receiverUser->name;
            if (!isset($data['invitation_id'])) $data['invitation_id'] = $invitation->id;
            $oldNoti->data = $data;
            $oldNoti->save();
        }
        // Gửi notification cho sender (người gửi) - thông báo đã bị decline
        $senderUser->notify(\App\Notifications\MemberInvitationNotification::createDeclinedNotification($invitation, $receiverUser));
        // Không gửi notification mới cho receiver nữa
        // Broadcast event tới sender
        broadcast(new MemberEvent($invitation->sender_id, $user->id, 'invitation_declined', [
            'invitation_id' => $invitation->id,
            'receiver' => [
                'id' => $receiverUser->id,
                'name' => $receiverUser->name,
                'email' => $receiverUser->email,
                'avatar' => $receiverUser->avatar,
            ]
        ]));
        // Broadcast event tới receiver (người nhận) - chính là user vừa decline
        broadcast(new MemberEvent($user->id, $invitation->sender_id, 'invitation_declined', [
            'invitation_id' => $invitation->id,
            'receiver' => [
                'id' => $receiverUser->id,
                'name' => $receiverUser->name,
                'email' => $receiverUser->email,
                'avatar' => $receiverUser->avatar,
            ]
        ]));
        return ['message' => 'Invitation declined'];
    }

    public function cancel($user, $id)
    {
        $invitation = $this->repo->findPendingByIdAndSender($id, $user->id);
        if (!$invitation) return ['error' => 'Invitation not found'];
        $receiverId = $invitation->receiver_id;
        $receiver = User::find($receiverId);
        $invitation->delete();
        // Broadcast event tới receiver
        broadcast(new MemberEvent($receiverId, $user->id, 'invitation_cancelled', [
            'invitation_id' => $id,
            'sender' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
            ]
        ]));
        // Broadcast event tới sender (chính mình) để realtime tab Sent Invitations
        broadcast(new MemberEvent($user->id, $receiverId, 'invitation_cancelled', [
            'invitation_id' => $id,
            'receiver' => [
                'id' => $receiver ? $receiver->id : null,
                'name' => $receiver ? $receiver->name : null,
                'email' => $receiver ? $receiver->email : null,
                'avatar' => $receiver ? $receiver->avatar : null,
            ]
        ]));
        return ['message' => 'Invitation cancelled'];
    }
}
