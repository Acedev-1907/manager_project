<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class ResetPasswordMail extends Mailable
{
    public $user;
    public $resetUrl;

    public function __construct($user, $resetUrl)
    {
        $this->user = $user;
        $this->resetUrl = $resetUrl;
    }

    public function build()
    {
        return $this->subject('Reset your password')
            ->view('email.reset-password')
            ->with([
                'user' => $this->user,
                'resetUrl' => $this->resetUrl,
            ]);
    }
}
