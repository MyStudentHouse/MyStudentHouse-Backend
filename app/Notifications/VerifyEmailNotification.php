<?php

namespace App\Notifications;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Auth\Notifications\VerifyEmail as VerifyEmailBase;

class VerifyEmailNotification extends VerifyEmailBase
{
    /**
    * Get the verification URL for the given notifiable.
    *
    * @param mixed $notifiable
    * @return string
    */
    protected function verificationUrl($notifiable)
    {
        /* Create a temporary signed API route with an asbolute path */
        $url = URL::temporarySignedRoute('verification.verify', Carbon::now()->addMinutes(60), ['id' => $notifiable->getKey()]);

        /* Parse the URL */
        $query_str = parse_url($url, PHP_URL_QUERY);
        parse_str($query_str, $query_params);

        /* Return the full path to be used in the email (for the front-end) */
        return config('app.url') ."/verify/". $notifiable->getKey() ."/". $query_params['expires'] ."/". $query_params['signature'];
    }
}
