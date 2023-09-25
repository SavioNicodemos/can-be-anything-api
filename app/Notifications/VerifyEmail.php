<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as VerifyEmailNotification;
use URL;

class VerifyEmail extends VerifyEmailNotification
{
    protected function verificationUrl($notifiable): string
    {
        // Replace 'frontend-app-url' with the URL of your frontend application
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification())
            ]
        );
    }
}
