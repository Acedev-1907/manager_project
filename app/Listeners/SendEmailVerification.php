<?php

namespace App\Listeners;

use App\Events\NewUserCreated;
use App\Mail\SendMail;
use Illuminate\Contracts\Queue\ShouldQueue;
// use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendEmailVerification implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(NewUserCreated $event): void
    {
        // Xếp mailable vào hàng đợi để xử lý bởi worker (Redis)
        Mail::to($event->user->email)->queue(new SendMail($event->user));
    }
}
