<?php

namespace App\Mail;

use App\UserAdded;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class UserAddedToHouse extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(UserAdded $userAddedInfo)
    {
        $this->userAdded = $userAddedInfo;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('hello@mystudent.house')
                ->view('emails.useraddedtohouse')
                ->with([
                    'name' => $this->userAdded->name,
                    'email' => $this->userAdded->email,
                    'houseName' => $this->userAdded->houseName,
                    'nameAdded' => $this->userAdded->nameAdded, // Name of the person who added `name`
                ]);
    }
}
